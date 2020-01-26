<?php

namespace Entrada\Modules\Olab\Http\Controllers;

use \Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use Entrada\Http\Controllers\Controller;
use Entrada\Modules\Olab\Classes\OlabCodeTracer;
use Entrada\Modules\Olab\Classes\OlabCounters;
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
use Entrada\Modules\Olab\Models\Counters;
use Entrada\Modules\Olab\Models\Servers;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\MapNodeLinks;
use Entrada\Modules\Olab\Models\H5pResults;
use Entrada\Modules\Olab\Models\MapNodes;
use Entrada\Modules\Olab\Classes\GlobalObjectManager;
use Entrada\Modules\Olab\Classes\PostDataHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class OlabCounterAuthoringController extends OlabScopedObjectAuthoringController 
{
  // override for abstract class to provide url subpath
  public function get_object_url_subpath() { return "counters"; }

  private function get_counter( int $id ) {
    
    $oObj = Counters::find( $id );

    if ( $oObj == null ) {
      throw new OlabObjectNotFoundException("Counter", $id );
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
      $oObj = new Counters();

      // test access control context based on object type to evaluate.
      $oAccessControl = AccessControlBase::classFactory( $oObj );
      if ( !$oAccessControl->isWriteable( null )) {
        throw new OlabAccessDeniedException("permission denied creating counter");
      }

      $this->oPostData->get_text( $oObj, 'name');
      $this->oPostData->get_text_optional( $oObj, 'description');
      $this->oPostData->get_text( $oObj, 'startValue');
      $this->oPostData->get_integer_optional( $oObj, 'visible');
      $this->oPostData->get_text( $oObj, 'scopeLevel');
      $this->oPostData->get_integer( $oObj, 'parentId');

      // verify we have a valid parent object
      $pParentObj = OLabUtilities::get_parent_object( $oObj );

      $oObj->save();
      
      $aData['id'] = $oObj->id;

      Log::info( "created new counter. id = " . $oObj->id );

      $payload = OLabUtilities::make_api_return( null, $tracer, $oObj );

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
      $records = Counters::get()->sortByDesc("id");

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

      Log::debug("found " . sizeof( $records ) . " counters to add to index list" );

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

      $oObj = $this->get_counter( $id );

      // test access control context based on object type to evaluate.
      $oAccessControl = AccessControlBase::classFactory( $oObj );
      if ( !$oAccessControl->isListable( $id )) {
        throw new OlabAccessDeniedException("counter_id = $id");
      }

      $aItem = $oObj->toArray();
      $this->build_scoped_object( $aItem, Counters::WIKI_TAG_COUNTER, "scopedobjects/counters" );    

      $payload = OLabUtilities::make_api_return( null, $tracer, $aItem );

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

      $oObj = $this->get_counter( $id );

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

      $oObj = $this->get_counter( $id );

      // get/update title field
      $this->oPostData->get_text_optional( $oObj, 'name');
      $this->oPostData->get_text_optional( $oObj, 'description');
      $this->oPostData->get_text_optional( $oObj, 'startValue');
      $this->oPostData->get_integer_optional( $oObj, 'iconId');
      $this->oPostData->get_text_optional( $oObj, 'prefix');
      $this->oPostData->get_text_optional( $oObj, 'suffix');
      $this->oPostData->get_integer_optional( $oObj, 'visible');
      $this->oPostData->get_integer_optional( $oObj, 'outOf');
      $this->oPostData->get_integer_optional( $oObj, 'status');
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