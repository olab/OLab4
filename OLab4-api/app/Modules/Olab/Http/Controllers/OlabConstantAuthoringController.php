<?php

namespace Entrada\Modules\Olab\Http\Controllers;

use \Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use Entrada\Http\Controllers\Controller;
use Entrada\Modules\Olab\Classes\OlabCodeTracer;
use Entrada\Modules\Olab\Classes\OlabConstants;
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
use Entrada\Modules\Olab\Models\Constants;
use Entrada\Modules\Olab\Models\Servers;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\MapNodeLinks;
use Entrada\Modules\Olab\Models\H5pResults;
use Entrada\Modules\Olab\Models\MapNodes;
use Entrada\Modules\Olab\Classes\GlobalObjectManager;
use Entrada\Modules\Olab\Classes\PostDataHandler;

class OlabConstantAuthoringController extends OlabScopedObjectAuthoringController 
{
  // override for abstract class to provide url subpath
  public function get_object_url_subpath() { return "constants"; }

  private function get_constant( int $id ) {
    
    $oObj = Constants::find( $id );

    if ( $oObj == null ) {
      throw new OlabObjectNotFoundException("Constant", $id );
    }

    return $oObj;
  }

  public function create( Request $request ) {
    
    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $aData = array();

      // create and save a new node
      $oObj = new Constants();

      // test access control context based on object type to evaluate.
      $oAccessControl = AccessControlBase::classFactory( $oObj );
      if ( !$oAccessControl->isWriteable( null )) {
        throw new OlabAccessDeniedException("permission denied creating constant");
      }

      $oPostData = new PostDataHandler( $request );

      $oPostData->get_text( $oObj, 'name');
      $oPostData->get_text_optional( $oObj, 'description');
      $oPostData->get_text( $oObj, 'value');
      $oPostData->get_text( $oObj, 'scopeLevel');
      $oPostData->get_integer( $oObj, 'parentId');

      // verify we have a valid parent object
      $pParentObj = OLabUtilities::get_parent_object( $oObj );

      $oObj->save();
      
      $aData['id'] = $oObj->id;

      Log::info( "created new constant. id = " . $oObj->id );

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

      // get all constants
      $records = Constants::get()->sortByDesc("id");

      $oPostData = new PostDataHandler( $request );

      // test access control context based on object type to evaluate.
      // in this case, it's a collection of maps.
      $oAccessControl = AccessControlBase::classFactory( $records );

      // loop through maps. if map is listable, add it to list for return
      foreach ($records as $record) {

        // test if have list access to map
        if ( $oAccessControl->isListable( $record->id )) {

          $item = $record->toArray();

          if ( $item['isSystem'] != 1 ) {
            $item['url'] = $this->get_object_url( $record->id );
          }

          array_push( $payload, $item );

        }
      }

      Log::debug("found " . sizeof( $records ) . " constants to add to index list" );

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

      $oObj = $this->get_constant( $id );

      // test access control context based on object type to evaluate.
      $oAccessControl = AccessControlBase::classFactory( $oObj );
      if ( !$oAccessControl->isListable( $id )) {
        throw new OlabAccessDeniedException("counter_id = $id");
      }

      $aData = $oObj->toArray();
      $this->build_scoped_object( $aData, "CONST", "scopedobjects/constants" );    

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

      $oObj = $this->get_constant( $id );

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

      $oObj = $this->get_constant( $id );

      // get/update title field
      $this->oPostData->get_text_optional( $oObj, 'name');
      $this->oPostData->get_text_optional( $oObj, 'description');
      $this->oPostData->get_text_optional( $oObj, 'value');
      $this->oPostData->get_text_optional( $oObj, 'scopeLevel');
      $this->oPostData->get_integer_optional( $oObj, 'parentId');
      $this->oPostData->get_integer_optional( $oObj, 'isSystem');

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