<?php
/**
 * OLab [ http://openlabyrinth.ca/ ]
 *
 * OLab is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OLab is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OLab.  If not, see <http://www.gnu.org/licenses/>.
 *
 * A model for OLab maps.
 *
 * @author Organisation: Cumming School of Medicine, University of Calgary
 * @author Developer: Corey Wirun (corey@cardinalcreek.ca)
 * @copyright Copyright 2017 University of Calgary. All Rights Reserved.
 */

namespace Entrada\Modules\Olab\Http\Controllers;

use \Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use Entrada\Http\Controllers\Controller;
use Entrada\Modules\Olab\Classes\OlabCodeTracer;
use Entrada\Modules\Olab\Classes\OlabQuestions;
use Entrada\Modules\Olab\Classes\UserStateHandler;
use Entrada\Modules\Olab\Classes\ScopedObjectManager;
use Entrada\Modules\Olab\Classes\Autoload\AccessControl\AccessControlBase;
use Entrada\Modules\Olab\Classes\Autoload\Events\EventHandler;
use Entrada\Modules\Olab\Classes\OlabExceptionHandler;
use Entrada\Modules\Olab\Classes\Autoload\WikiTags\OlabTagBase;
use Entrada\Modules\Olab\Classes\HostSystemApi;
use Entrada\Modules\Olab\Classes\CounterManager;
use Entrada\Modules\Olab\Classes\OLabUtilities;
use Entrada\Modules\Olab\Classes\SecurityContext;
use Entrada\Modules\Olab\Http\Controllers\OlabScopedObjectAuthoringController;
use Entrada\Modules\Olab\Classes\OlabAccessDeniedException;
use Entrada\Modules\Olab\Classes\OlabObjectNotFoundException;

use \H5PCore;
use Entrada\Modules\Olab\Classes\h5p\H5PPlugin;
use Entrada\Modules\Olab;
use Entrada\Modules\Olab\Models\UserState;
use Entrada\Modules\Olab\Models\Map;
use Entrada\Modules\Olab\Models\Node;
use Entrada\Modules\Olab\Models\NodeCounter;
use Entrada\Modules\Olab\Models\Questions;
use Entrada\Modules\Olab\Models\QuestionResponses;
use Entrada\Modules\Olab\Models\Servers;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\MapNodeLinks;
use Entrada\Modules\Olab\Models\H5pResults;
use Entrada\Modules\Olab\Models\MapNodes;
use Entrada\Modules\Olab\Classes\GlobalObjectManager;
use Entrada\Modules\Olab\Classes\PostDataHandler;

class OlabQuestionAuthoringController extends OlabScopedObjectAuthoringController
{
  // override for abstract class to provide url subpath
  public function get_object_url_subpath() { return "questions"; }

  public function create( Request $request ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );
      $aData = array();

      // create and save a new node
      $oObj = new Questions();

      $this->oPostData = new PostDataHandler( $request );
      $this->oPostData->get_integer( $oObj, 'questionType');
      $this->oPostData->get_integer_optional( $oObj, 'height');
      $this->oPostData->get_integer_optional( $oObj, 'isPrivate');
      $this->oPostData->get_integer_optional( $oObj, 'layoutType');
      $this->oPostData->get_integer_optional( $oObj, 'layoutType');
      $this->oPostData->get_integer_optional( $oObj, 'order');
      $this->oPostData->get_integer_optional( $oObj, 'showAnswer');
      $this->oPostData->get_integer_optional( $oObj, 'showSubmit');
      $this->oPostData->get_integer_optional( $oObj, 'width');
      $this->oPostData->get_text( $oObj, 'name');
      $this->oPostData->get_text( $oObj, 'stem');
      $this->oPostData->get_text_optional( $oObj, 'description');
      $this->oPostData->get_text_optional( $oObj, 'feedback');
      $this->oPostData->get_text_optional( $oObj, 'prompt');

      $this->oPostData->get_text( $oObj, 'scopeLevel');
      $this->oPostData->get_integer( $oObj, 'parentId');

      // verify we have a valid parent object
      OLabUtilities::get_parent_object( $oObj );

      // made it this far, meaning have access to parent object,
      // now write it to database
      $this->write_object( $oObj );

      $aData['id'] = $oObj->id;

      Log::info( "created new question. id = " . $oObj->id );

      $payload = OLabUtilities::make_api_return( null, $tracer, $aData );

    }
    catch (Exception $exception) {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );
    }

    return response()->json($payload);
  }

  public function getMany( Request $request ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      // get all active maps
      $records = Questions::get()->sortByDesc("id");

      // test access control context based on object type to evaluate.
      // in this case, it's a collection of maps.
      $oAccessControl = AccessControlBase::classFactory( $records );

      // loop through maps. if map is listable, add it to list for return
      foreach ($records as $record) {

        // test if have list access to map
        if ( $oAccessControl->isListable( $record->id )) {

          $item = $record->toArray();
          $item['url'] = $this->get_object_url( $record->id );
          array_push( $payload, $item );

        }
      }

      Log::debug("found " . sizeof( $records ) . " questions to add to index list" );

      $payload = OLabUtilities::make_api_return( null, $tracer, $payload );

    }
    catch (Exception $exception) {

      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );

    }

    return response()->json($payload);

  }

  public function getSingle( Request $request, int $id ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($id)" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $aData = array();

      $oObj = $this->get_question_deep( $id );

      // test access control context based on object type to evaluate.
      $oAccessControl = AccessControlBase::classFactory( $oObj );
      if ( !$oAccessControl->isListable( $id )) {
        throw new OlabAccessDeniedException("question_id = $id");
      }

      $aData = $oObj->toArray();
      $this->build_scoped_object( $aData, Questions::WIKI_TAG_QUESTION, "scopedobjects/questions" );

      $payload = OLabUtilities::make_api_return( null, $tracer, $aData );

    }
    catch (Exception $exception) {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );
    }

    return response()->json($payload);

  }

  public function delete( Request $request, int $id ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($id)" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $oObj = $this->get_question( $id );
      $this->delete_scoped_object( $oObj );

      $payload = OLabUtilities::make_api_return( null, $tracer );

    }
    catch (Exception $exception) {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );
    }

    return response()->json($payload);
  }

  public function edit( Request $request, int $id ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($id)" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $oObj = $this->get_question( $id );

      // get/update title field
      $this->oPostData->get_text_optional( $oObj, 'name');
      $this->oPostData->get_text_optional( $oObj, 'description');
      $this->oPostData->get_text_optional( $oObj, 'stem');
      $this->oPostData->get_integer_optional( $oObj, 'questionType');
      $this->oPostData->get_integer_optional( $oObj, 'width');
      $this->oPostData->get_integer_optional( $oObj, 'height');
      $this->oPostData->get_text_optional( $oObj, 'feedback');
      $this->oPostData->get_text_optional( $oObj, 'placeholder');
      $this->oPostData->get_integer_optional( $oObj, 'showAnswer');
      $this->oPostData->get_integer_optional( $oObj, 'layoutType');
      $this->oPostData->get_integer_optional( $oObj, 'showSubmit');
      $this->oPostData->get_integer_optional( $oObj, 'order');
      $this->oPostData->get_integer_optional( $oObj, 'layoutType');

      $this->write_object( $oObj );

      $payload = OLabUtilities::make_api_return( null, $tracer );

    }
    catch (Exception $exception) {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );
    }

    return response()->json($payload);

  }

}