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

namespace App\Modules\Olab\Http\Controllers;

use Auth;
use \Exception;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Olab\Models\MapNodeTypes;
use App\Modules\Olab\Models\Maps;
use App\Modules\Olab\Models\UserState;
use App\Modules\Olab\Models\Files;
use App\Modules\Olab\Models\SystemSettings;
use App\Modules\Olab\Models\QuestionResponses;
use App\Modules\Olab\Models\Questions;
use App\Modules\Olab\Models\Counters;
use App\Modules\Olab\Models\MapNodes;
use App\Modules\Olab\Models\Servers;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\JWTAuth;
use Tymon\JWTAuth\Payload;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\PayloadFactory;
use Tymon\JWTAuth\Facades\JWTFactory;
use Tymon\JWTAuth\Token;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Modules\Olab\Classes\OlabExceptionHandler;
use App\Modules\Olab\Classes\SiteFileHandler;
use App\Modules\Olab\Classes\OlabCodeTracer;
use App\Modules\Olab\Classes\CounterManager;
use App\Modules\Olab\Classes\UserStateHandler;
use App\Modules\Olab\Classes\HostSystemApi;
use App\Modules\Olab\Classes\OlabConstants;
use App\Modules\Olab\Classes\ScopedObjectManager;

class OlabQuestionController extends Controller
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

        // create user state handler class
        $this->oUserState = new UserStateHandler();
    }

    /**
     * Process a dropdown response message
     * @param Request $request
     * @param integer $nNodeId Current node
     * @param integer $nResponseId Question response id
     * @param mixed $nResponseId Question response value
     */
    public function postDropdownResponse( Request $request, $nNodeId ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "( $nNodeId )" );

        $aPayload = array();

        try
        {
            $nResponseId = $request->all()['responseId'];
            $jUserState = $request->all()['state_data'];
            $nPreviousId = $request->all()['previousId'];

            Log::debug( "nPreviousId = " . $nPreviousId . " nResponseId = " . $nResponseId );

            // get the current user
            $nUserId = HostSystemApi::GetUserId();

            $oScopedObjects = new ScopedObjectManager();
            $oScopedObjects->loadFromUserState( $jUserState );

            $oNode = MapNodes::At( $nNodeId );
            if ( $oNode == null ) {
                throw new Exception("Cannot find node for index: " . $nNodeId );
            }

            // get the map, including related entities
            $oMap = Maps::Active()->Abbreviated()->WithObjects()->At( $oNode->map_id );
            if ( $oMap == null ) {
                throw new Exception("Cannot find map for index: " . $oNode->map_id );
            }

            // test if have a previous selection, meaning we back it out
            if ( $nPreviousId != "null" ) {

                // get response record
                $oResponse = QuestionResponses::find( $nPreviousId );
                if ( $oResponse == null ) {
                    throw new Exception("Cannot find question response for index: " . $nResponseId );
                }

                // get corresponding question for response
                $oCounter = $oResponse->Question->Counter;
                if ( $oCounter != null ) {

                    // invert the response expression value
                    $value = -$oResponse->score;

                    // update counter, reversing previous value
                    CounterManager::executeExpression( $oCounter->id, $value, $oScopedObjects );
                }


            }

            $oResponse = QuestionResponses::find( $nResponseId );
            if ( $oResponse == null ) {
                throw new Exception("Cannot find question response for index: " . $nResponseId );
            }

            $oCounter = $oResponse->Question->Counter;
            if ( $oCounter != null ) {
                // update counters with question response score expression
                CounterManager::executeExpression( $oCounter->id, $oResponse->score, $oScopedObjects );

                // create/update new user state (which will delete any existing states)
                $this->oUserState->Create( $oScopedObjects, $nUserId, $oMap->id, $nNodeId );
            }

            $jUserState = json_encode( $oScopedObjects->getCounter( null, false ) );

            $aPayload[OlabConstants::PAYLOAD_STATE_INDEX_NAME]['state_data'] = $jUserState;

        }
        catch (Exception $exception) {
            return OlabExceptionHandler::RestApiError( $exception );
        }

        return response()->json( $aPayload );
    }

    /**
     * Process a multichoice question response message
     * @param Request $request
     * @param integer $nNodeId Current node
     * @param integer $nResponseId Question response id
     * @param mixed $nResponseId Question response value
     */
    public function postMultichoiceResponse( Request $request, $nNodeId ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "( $nNodeId )" );

        $aPayload = array();

        try
        {
            $nResponseId = $request->all()['responseId'];
            $bChecked = $request->all()['value'] == "true" ? true : false;
            $jUserState = $request->all()['state_data'];

            Log::debug( "nResponseId = " . $nResponseId . ". value = " . $bChecked );

            // get the current user
            $nUserId = HostSystemApi::GetUserId();

            $oScopedObjects = new ScopedObjectManager();
            $oScopedObjects->loadFromUserState( $jUserState );

            $oNode = MapNodes::At( $nNodeId );
            if ( $oNode == null ) {
                throw new Exception("Cannot find node for index: " . $nNodeId );
            }

            // get the map, including related entities
            $oMap = Maps::Active()->Abbreviated()->WithObjects()->At( $oNode->map_id );
            if ( $oMap == null ) {
                throw new Exception("Cannot find map for index: " . $oNode->map_id );
            }

            $oResponse = QuestionResponses::find( $nResponseId );
            if ( $oResponse == null ) {
                throw new Exception("Cannot find question response for index: " . $nResponseId );
            }

            $oCounter = $oResponse->Question->Counter;
            if ( $oCounter != null ) {

                $value = $oResponse->score;

                // invert the counter value if the checkbox was unchecked
                if ( !$bChecked ) {
                    $value = -$value;
                }

                // update counter
                CounterManager::executeExpression( $oCounter->id, $value, $oScopedObjects );

                // create/update new user state (which will delete any existing states)
                $this->oUserState->Create( $oScopedObjects, $nUserId, $oMap->id, $nNodeId );

            }

            $jUserState = json_encode( $oScopedObjects->getCounter( null, false ) );

            $aPayload[OlabConstants::PAYLOAD_STATE_INDEX_NAME]['state_data'] = $jUserState;

        }
        catch (Exception $exception) {
            return OlabExceptionHandler::RestApiError( $exception );
        }

        return response()->json( $aPayload );

    }

    /**
     * Process a radio question response message
     * @param Request $request
     * @param integer $nNodeId Current node
     * @param integer $nResponseId Question response id
     * @param mixed $nResponseId Question response value
     */
    public function postRadioResponse( Request $request, $nNodeId ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "( $nNodeId )" );

        $aPayload = array();

        try
        {
            $nResponseId = $request->all()['responseId'];
            $jUserState = $request->all()['state_data'];
            $nPreviousId = $request->all()['previousId'];

            Log::debug( "nPreviousId = " . $nPreviousId . " nResponseId = " . $nResponseId );

            // get the current user
            $nUserId = HostSystemApi::GetUserId();

            $oScopedObjects = new ScopedObjectManager();
            $oScopedObjects->loadFromUserState( $jUserState );

            $oNode = MapNodes::At( $nNodeId );
            if ( $oNode == null ) {
                throw new Exception("Cannot find node for index: " . $nNodeId );
            }

            // get the map, including related entities
            $oMap = Maps::Active()->Abbreviated()->WithObjects()->At( $oNode->map_id );
            if ( $oMap == null ) {
                throw new Exception("Cannot find map for index: " . $oNode->map_id );
            }

            // test if have a previous selection, meaning we back it out
            if ( $nPreviousId != "null" ) {

                // get response record
                $oResponse = QuestionResponses::find( $nPreviousId );
                if ( $oResponse == null ) {
                    throw new Exception("Cannot find question response for index: " . $nResponseId );
                }

                // get corresponding question for response
                $oCounter = $oResponse->Question->Counter;
                if ( $oCounter != null ) {

                    // invert the response expression value
                    $value = -$oResponse->score;

                    // update counter, reversing previous value
                    CounterManager::executeExpression( $oCounter->id, $value, $oScopedObjects );
                }

            }

            $oResponse = QuestionResponses::find( $nResponseId );
            if ( $oResponse == null ) {
                throw new Exception("Cannot find question response for index: " . $nResponseId );
            }

            $oCounter = $oResponse->Question->Counter;
            if ( $oCounter != null ) {
                // update counters with question response score expression
                CounterManager::executeExpression( $oCounter->id, $oResponse->score, $oScopedObjects );

                // create/update new user state (which will delete any existing states)
                $this->oUserState->Create( $oScopedObjects, $nUserId, $oMap->id, $nNodeId );
            }

            $jUserState = json_encode( $oScopedObjects->getCounter( null, false ) );

            $aPayload[OlabConstants::PAYLOAD_STATE_INDEX_NAME]['state_data'] = $jUserState;

        }
        catch (Exception $exception) {
            return OlabExceptionHandler::RestApiError( $exception );
        }

        return response()->json( $aPayload );
    }


}