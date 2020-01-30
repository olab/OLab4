<?php

/**
 * OpenLabyrinth [ http://www.openlabyrinth.ca ]
 *
 * OpenLabyrinth is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenLabyrinth is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenLabyrinth.  If not, see <http://www.gnu.org/licenses/>.
 *
 * A controller for sysetm question functionality
 *
 * @author Organisation: Cumming School of Medicine, University of Calgary
 * @author Developer: Corey Wirun (corey@cardinalcreek.ca)
 * @copyright Copyright 2017 University of Calgary. All Rights Reserved.
 */

namespace Entrada\Modules\Olab\Http\Controllers;

use Auth;
use \Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\JWTAuth;

use App\Http\Controllers\Controller;

use Entrada\Modules\Olab\Classes\OlabExceptionHandler;
use Entrada\Modules\Olab\Classes\OlabObjectNotFoundException;
use Entrada\Modules\Olab\Classes\SiteFileHandler;
use Entrada\Modules\Olab\Classes\OlabCodeTracer;
use Entrada\Modules\Olab\Classes\CounterManager;
use Entrada\Modules\Olab\Classes\UserStateHandler;
use Entrada\Modules\Olab\Classes\HostSystemApi;
use Entrada\Modules\Olab\Classes\OlabConstants;
use Entrada\Modules\Olab\Classes\ScopedObjectManager;

use Entrada\Modules\Olab\Models\MapNodeTypes;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\UserState;
use Entrada\Modules\Olab\Models\Files;
use Entrada\Modules\Olab\Models\SystemSettings;
use Entrada\Modules\Olab\Models\QuestionResponses;
use Entrada\Modules\Olab\Models\Questions;
use Entrada\Modules\Olab\Models\Counters;
use Entrada\Modules\Olab\Models\MapNodes;
use Entrada\Modules\Olab\Models\Servers;
use Entrada\Modules\Olab\Classes\GlobalObjectManager;
use Entrada\Modules\Olab\Classes\Autoload\Events\EventHandler;

class QuestionResponseHelper extends OlabController {

  public $response_id;
  public $jUser_state;

  public $oScopedObjects;
  private $oServer;
  public $oNode;
  public $oState;
  public $oMap;
  public $oResponse;

  public $oCounter;

  /**  var Request $request **/
  public function __construct( Request $request, int $node_id ) {

    $this->initialize( $request );

    $this->oPostData->get_integer( $this->response_id, 'responseId' );
    Log::debug( "nResponseId = " . $this->response_id );

    // get the server, including related entities
    $this->oServer = Servers::At( Servers::DEFAULT_LOCAL_ID );

    $this->oNode = MapNodes::At( $node_id );
    if ( $this->oNode == null ) {
      throw new OlabObjectNotFoundException("MapNode", $node_id );
    }

    // get the map, including related entities
    $this->oMap = Maps::active()->Abbreviated()->WithObjects()->At( $this->oNode->map_id );
    if ( $this->oMap == null ) {
      throw new OlabObjectNotFoundException("Map", $this->oNode->map_id );
    }

    // get any user state for the current map
    $this->oState = GlobalObjectManager::Get( GlobalObjectManager::USER_STATE )
                      ->Get( $this->user_id, 
                             Servers::DEFAULT_LOCAL_ID, 
                             $this->oMap->id );

    $this->oScopedObjects = new ScopedObjectManager( $this->oServer,
                                                     $this->oMap,
                                                     $this->oNode );

    $this->oResponse = QuestionResponses::find( $this->response_id );
    if ( $this->oResponse == null ) {
      throw new OlabObjectNotFoundException("QuestionResponse", $this->response_id );
    }

    // see if counter attached to response
    $this->oCounter = $this->oResponse->Question->Counter;
  }

}

class OlabQuestionController extends OlabController
{
  /**
   * @var \Tymon\JWTAuth\JWTAuth
   */
  protected $jwt;

  /**
   * Map state data
   * @var mixed
   */
  protected $state_data;

  public function __construct( JWTAuth $jwt ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

    $this->jwt = $jwt;

    // spin up global object handler if one doesn't exist yet
    GlobalObjectManager::Initialize();
  }

  /**
   * Process a dropdown response message
   * @param Request $request
   * @param integer $node_id Current node
   * @param integer $response_id Question response id
   * @param mixed $response_id Question response value
   */
  public function postDropdownResponse( Request $request, $node_id ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "( $node_id )" );
    $aPayload = array();

    try
    {
      $common = new QuestionResponseHelper( $request, $node_id );

      $previous_id = $request->all()['previousId'];
      Log::debug( "nPreviousId = " . $previous_id );

      // test if have a previous selection, meaning we back it out
      if ( $previous_id != "null" ) {

        // get response record
        $oPreviousResponse = QuestionResponses::find( $previous_id );
        if ( $oPreviousResponse == null ) {
          throw new OlabObjectNotFoundException("Question Response", $previous_id );
        }

        // get corresponding question for response
        $oCounter = $oPreviousResponse->Question->Counter;
        if ( $oCounter != null ) {

          // invert the response expression value
          $value = -$oPreviousResponse->score;

          // update counter, reversing previous value
          CounterManager::executeExpression( $oCounter, $value, 
                                             $common->oScopedObjects,
                                             $oCommon->oState );
        }
      }

      // see if counter attached to response
      if ( $common->oCounter != null ) {

        // update counters with question response score expression
        CounterManager::executeExpression( $common->oCounter, 
                                           $common->oResponse->score, 
                                           $common->oScopedObjects,
                                           $oCommon->oState );

        // create/update new user state (which will delete any existing states)
        GlobalObjectManager::Get( GlobalObjectManager::USER_STATE )
            ->Update( $common->oState, 
                      $common->oScopedObjects );

        // fire onQuestionResponded event
        GlobalObjectManager::Get( GlobalObjectManager::EVENT )
            ->FireEvent( EventHandler::ON_QUESTION_RESPONDED, 
                         array( $common->oState, 
                                $common->oNode, 
                                $common->oResponse ) );
      }

      $common->jUserState = json_encode( $common->oScopedObjects->getCounter( null, false ) );

      $aPayload[OlabConstants::PAYLOAD_STATE_INDEX_NAME]['state_data'] = $common->jUserState;
      $aPayload['parameters'] = $request->all();

      return response()->json( $aPayload );

    }
    catch (Exception $exception) {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      return OlabExceptionHandler::restApiError( $exception );
    }

  }

  /**
   * Process a multichoice question response message
   * @param Request $request
   * @param integer $node_id Current node
   * @param integer $response_id Question response id
   * @param mixed $response_id Question response value
   */
  public function postMultichoiceResponse( Request $request, $node_id ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "( $node_id )" );
    $aPayload = array();

    try
    {
      $oCommon = new QuestionResponseHelper( $request, $node_id );

      $is_checked = null;
      $oCommon->oPostData->get_text( $is_checked, "value" );
      $is_checked = ( $is_checked == "on" );

      Log::debug( "value = " . $is_checked );

      if ( $oCommon->oCounter != null ) {

        // get current response score expression
        $value = $oCommon->oResponse->score;

        // invert the counter value if the checkbox was unchecked
        if ( !$is_checked ) {
          $value = -$value;
        }

        // update counter
        CounterManager::executeExpression( $oCommon->oCounter, 
                                           $value, 
                                           $oCommon->oScopedObjects,
                                           $oCommon->oState );

        // update user state 
        GlobalObjectManager::Get( GlobalObjectManager::USER_STATE )
            ->Update( $oCommon->oState, 
                      $oCommon->oScopedObjects );

        // fire onQuestionResponded event
        GlobalObjectManager::Get( GlobalObjectManager::EVENT )                   
            ->FireEvent( EventHandler::ON_QUESTION_RESPONDED, 
                         array( $oCommon->oState, 
                                $oCommon->oNode, 
                                $oCommon->oResponse ) );
      }

      // extract shared counter data into payload
      $oCommon->oScopedObjects->extractCounters( $aPayload );
     
      // update state data with non-shared counters
      $this->updateStateDataCounters( $aPayload["counters"], 
                                      $oCommon->oState );

      // set state data into payload
      $aPayload[OlabConstants::PAYLOAD_STATE_INDEX_NAME]['state_data'] = $oCommon->oState->state_data;

      $request_data = $request->all()['data'];
      $aPayload['parameters'] = $request_data;

      return response()->json( $aPayload );

    }
    catch (Exception $exception) {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      return OlabExceptionHandler::restApiError( $exception );
    }

  }

  /**
   * Process a slider question response message
   * @param Request $request
   * @param integer $node_id Current node
   * @param integer $response_id Question response id
   * @param mixed $response_id Question response value
   */
  public function postSliderResponse( Request $request, $node_id ) {
    
    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "( $node_id )" );
    $aPayload = array();

    try {

      return response()->json( $aPayload );
      
    }
    catch (Exception $exception) {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      return OlabExceptionHandler::restApiError( $exception );
    }

  }

  /**
   * Process a radio question response message
   * @param Request $request
   * @param integer $node_id Current node
   * @param integer $response_id Question response id
   * @param mixed $response_id Question response value
   */
  public function postRadioResponse( Request $request, $node_id ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "( $node_id )" );
    $aPayload = array();

    try
    {
      $oCommon = new QuestionResponseHelper( $request, $node_id );

      $previous_id = null;
      $oCommon->oPostData->get_text( $previous_id, "previousId" );

      Log::debug( "nPreviousId = " . $previous_id );

      // test if have a previous selection, meaning we back it out, counter-wise
      if ( $previous_id != "null" ) {

        // get response record
        $oPreviousResponse = QuestionResponses::find( $previous_id );
        if ( $oPreviousResponse == null ) {
            throw new OlabObjectNotFoundException("Question Response", $previous_id );
        }

        if ( $oCommon->oCounter != null ) {

          // invert the previous response expression value
          $value = -$oPreviousResponse->score;

          // update counter, reversing previous value
          CounterManager::executeExpression( $oCommon->oCounter, 
                                             $value, 
                                             $oCommon->oScopedObjects,
                                             $oCommon->oState );
        }
      }

      if ( $oCommon->oCounter != null ) {

        // update counters with question response score expression
        CounterManager::executeExpression( $oCommon->oCounter, 
                                           $oCommon->oResponse->score, 
                                           $oCommon->oScopedObjects,
                                           $oCommon->oState );

        // fire onQuestionResponded event
        GlobalObjectManager::Get( GlobalObjectManager::EVENT )
            ->FireEvent( EventHandler::ON_QUESTION_RESPONDED, 
                         array( $oCommon->oState, 
                                $oCommon->oNode, 
                                $oCommon->oResponse ) );
      }
     
      // extract shared counter data into payload
      $oCommon->oScopedObjects->extractCounters( $aPayload );
     
      // update state data with non-shared counters
      $this->updateStateDataCounters( $aPayload["counters"], 
                                      $oCommon->oState );

      // set state data into payload
      $aPayload[OlabConstants::PAYLOAD_STATE_INDEX_NAME]['state_data'] 
          = $oCommon->oState->state_data;

      $request_data = $request->all()['data'];
      $aPayload['parameters'] = $request_data;

      return response()->json( $aPayload );

    }
    catch (Exception $exception) {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      return OlabExceptionHandler::restApiError( $exception );
    }

  }

  private function updateStateDataCounters( $aaPayload, 
                                            &$oState ) {

    $state_data = json_decode( $oState->state_data, true );

    $state_data[ScopedObjectManager::PAYLOAD_INDEXNAME_MAP] = $aaPayload[ScopedObjectManager::PAYLOAD_INDEXNAME_MAP];
    $state_data[ScopedObjectManager::PAYLOAD_INDEXNAME_NODE] = $aaPayload[ScopedObjectManager::PAYLOAD_INDEXNAME_NODE];

    $oState->state_data = json_encode( $state_data );

    // update database with state change
    $oState->save();
  }
}