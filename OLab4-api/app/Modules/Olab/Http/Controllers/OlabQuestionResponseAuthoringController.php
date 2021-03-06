<?php

namespace Entrada\Modules\Olab\Http\Controllers;

use \Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use Entrada\Http\Controllers\Controller;
use Entrada\Modules\Olab\Classes\OlabCodeTracer;
use Entrada\Modules\Olab\Classes\OlabQuestionResponses;
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

class OlabQuestionResponseAuthoringController extends OlabScopedObjectAuthoringController
{
  // override for abstract class to provide url subpath
  public function get_object_url_subpath() { return "questionresponses"; }

  public function create( Request $request ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $aData = array();

      // create and save a new object
      $oObj = new QuestionResponses();

      $oPostData = new PostDataHandler( $request );

      $oPostData->get_integer( $oObj, 'questionId');
      $oPostData->get_text( $oObj, 'response');
      $oPostData->get_text_optional( $oObj, 'feedback');
      $oPostData->get_integer_optional( $oObj, 'order');
      $oPostData->get_integer_optional( $oObj, 'isCorrect');
      $oPostData->get_integer_optional( $oObj, 'score');

      $oQuestion = $this->get_question( $oObj->question_id );

      // test access control context based on object type to evaluate.
      $oAccessControl = AccessControlBase::classFactory( $oQuestion );
      if ( !$oAccessControl->isWriteable( $oObj->question_id )) {
        throw new OlabAccessDeniedException("question = $oObj->question_id");
      }

      $oObj->save();

      $aData['id'] = $oObj->id;

      Log::info( "created new question_response. id = " . $oObj->id );

      $payload = OLabUtilities::make_api_return( null, $tracer, $aData );

    }
    catch (Exception $exception) {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );
    }

    return response()->json($payload);
  }

  public function getMany( Request $request, int $question_id ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $oQuestion = $this->get_question_deep( $question_id );

      // test access control context based on object type to evaluate.
      $oAccessControl = AccessControlBase::classFactory( $oQuestion );
      if ( !$oAccessControl->isListable( $question_id )) {
        throw new OlabAccessDeniedException("question = $question_id");
      }

      $aoResponses = $oQuestion->QuestionResponses()->get();

      // loop through items. add to payload array
      foreach ($aoResponses as $record) {

        $item = $record->toArray();

        OLabUtilities::safe_rename( $item, 'score' );
        OLabUtilities::safe_rename( $item, 'order' );
        OLabUtilities::safe_rename( $item, 'isCorrect' );

        $item['url'] = OLabUtilities::get_path_info()['apiBaseUrl'] .
          "questions/$question_id/" . $this->get_object_url( $record->id );

        array_push( $payload, $item );

        //}
      }

      Log::debug("found " . sizeof( $aoResponses ) . " question_responses to add to index list" );

      $payload = OLabUtilities::make_api_return( null, $tracer, $payload );

    }
    catch (Exception $exception) {

      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );

    }

    return response()->json($payload);

  }

  public function getSingle( Request $request, int $response_id ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($response_id)" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $oResponse = $this->get_question_response( $response_id );
      $aResponse = $oResponse->toArray();

      $question_id = $aResponse['questionId'];
      $oQuestion = $this->get_question( $question_id );

      // test access control context based on object type to evaluate.
      $oAccessControl = AccessControlBase::classFactory( $oQuestion );
      if ( !$oAccessControl->isListable( $question_id )) {
        throw new OlabAccessDeniedException("question = $question_id");
      }

      $payload = OLabUtilities::make_api_return( null, $tracer, $aResponse );
    }
    catch (Exception $exception) {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );
    }

    return response()->json($payload);


  }

  public function delete( Request $request, int $response_id ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($response_id)" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $oResponse = $this->get_question_response( $response_id );
      $aResponse = $oResponse->toArray();

      $question_id = $aResponse['questionId'];
      $oQuestion = $this->get_question( $question_id );

      // test access control context based on object type to evaluate.
      $oAccessControl = AccessControlBase::classFactory( $oQuestion );
      if ( !$oAccessControl->isListable( $question_id )) {
          throw new OlabAccessDeniedException("question = $question_id");
      }

      $this->delete_scoped_object( $oResponse );

      $payload = OLabUtilities::make_api_return( null, $tracer );

    }
    catch (Exception $exception) {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );
    }

    return response()->json($payload);
  }

  public function edit( Request $request, int $response_id ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($response_id)" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $oResponse = $this->get_question_response( $response_id );
      $aResponse = $oResponse->toArray();

      $question_id = $aResponse['questionId'];
      $oQuestion = $this->get_question( $question_id );

      // test access control context based on object type to evaluate.
      $oAccessControl = AccessControlBase::classFactory( $oQuestion );
      if ( !$oAccessControl->isListable( $question_id )) {
          throw new OlabAccessDeniedException("question = $question_id");
      }

      // get/update title field
      $this->oPostData->get_integer_optional( $oResponse, 'isCorrect');
      $this->oPostData->get_integer_optional( $oResponse, 'order');
      $this->oPostData->get_integer_optional( $oResponse, 'questionType');
      $this->oPostData->get_integer_optional( $oResponse, 'score');
      $this->oPostData->get_text_optional( $oResponse, 'description');
      $this->oPostData->get_text_optional( $oResponse, 'name');
      $this->oPostData->get_text_optional( $oResponse, 'response');
      $this->oPostData->get_text_optional( $oResponse, 'text');

      $this->write_object( $oResponse );

      $payload = OLabUtilities::make_api_return( null, $tracer );

    }
    catch (Exception $exception) {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );
    }

    return response()->json($payload);

  }

}