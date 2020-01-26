<?php

namespace Entrada\Modules\Olab\Http\Controllers;

use \Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use Entrada\Http\Controllers\Controller;
use Entrada\Modules\Olab\Classes\OlabCodeTracer;
use Entrada\Modules\Olab\Classes\OlabFiles;
use Entrada\Modules\Olab\Classes\UserStateHandler;
use Entrada\Modules\Olab\Classes\ScopedObjectManager;
use Entrada\Modules\Olab\Classes\Autoload\AccessControl\AccessControlBase;
use Entrada\Modules\Olab\Classes\Autoload\Events\EventHandler;
use Entrada\Modules\Olab\Classes\OlabExceptionHandler;
use Entrada\Modules\Olab\Classes\Autoload\WikiTags\OlabTagBase;
use Entrada\Modules\Olab\Classes\HostSystemApi;
use Entrada\Modules\Olab\Classes\SiteFileHandler;
use Entrada\Modules\Olab\Classes\FileManager;
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
use Entrada\Modules\Olab\Models\Files;
use Entrada\Modules\Olab\Models\Servers;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\MapNodeLinks;
use Entrada\Modules\Olab\Models\H5pResults;
use Entrada\Modules\Olab\Models\MapNodes;
use Entrada\Modules\Olab\Classes\GlobalObjectManager;
use Entrada\Modules\Olab\Classes\PostDataHandler;

class OlabFileAuthoringController extends OlabScopedObjectAuthoringController 
{
  // override for abstract class to provide url subpath
  public function get_object_url_subpath() { return "files"; }

  private function get_file( int $id ) {
    
    $oObj = Files::find( $id );

    if ( $oObj == null ) {
      throw new OlabObjectNotFoundException("File", $id );
    }

    return $oObj;
  }

  public function create( Request $request ) {
    
    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );
    $payload = array();

    DB::beginTransaction();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $aData = array();

      // create and save a new node
      $oFile = new Files();

      // test access control context based on object type to evaluate.
      $oAccessControl = AccessControlBase::classFactory( $oFile );
      if ( !$oAccessControl->isWriteable( null )) {
        throw new OlabAccessDeniedException("permission denied creating file");
      }

      $this->oPostData->get_text( $oFile, 'name');
      $this->oPostData->get_text_optional( $oFile, 'description');
      $this->oPostData->get_text( $oFile, 'contents');

      $this->oPostData->get_integer_optional( $oFile, 'type');
      $this->oPostData->get_integer_optional( $oFile, 'height');
      $this->oPostData->get_text_optional( $oFile, 'heightType');
      $this->oPostData->get_integer_optional( $oFile, 'width');
      $this->oPostData->get_text_optional( $oFile, 'widthType');
      $this->oPostData->get_text_optional( $oFile, 'copyright');
      $this->oPostData->get_text_optional( $oFile, 'originUrl');

      $this->oPostData->get_text( $oFile, 'scopeLevel');
      $this->oPostData->get_integer( $oFile, 'parentId');

      $this->oPostData->get_text_optional( $oFile, 'fileName');

      // verify we have a valid parent object
      $pParentObj = OLabUtilities::get_parent_object( $oFile );

      $oFile->path = SiteFileHandler::createFile( $oFile );
      $oFile->file_size = SiteFileHandler::getFileSize( $oFile );

      // don't need encoded content anymore
      $oFile->encoded_content = null;

      $this->write_object( $oFile );    

      DB::commit();

      Log::info( "created new file. id = " . $oFile->id );

      $payload = OLabUtilities::make_api_return( null, $tracer, $oFile );

    }
    catch (Exception $exception) {

      DB::rollback();

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
      $records = Files::get()->sortByDesc("id");

      $this->oPostData = new PostDataHandler( $request );

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

      Log::debug("found " . sizeof( $records ) . " files to add to index list" );

      $payload = OLabUtilities::make_api_return( null, $tracer, $payload );

    }
    catch (Exception $exception) {

      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );

    }

    return response()->json($payload);

  }

  public function get( Request $request, int $id ) {
    
    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($id)" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $aData = array();

      $oFile = $this->get_file( $id );

      // test access control context based on object type to evaluate.
      $oAccessControl = AccessControlBase::classFactory( $oFile );
      if ( !$oAccessControl->isListable( $id )) {
        throw new OlabAccessDeniedException("file_id = $id");
      }

      $aData = $oFile->toArray();
      $this->build_scoped_object( $aData, Files::WIKI_TAG_MEDIA_RESOURCE, "scopedobjects/files" );    

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

    DB::beginTransaction();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $oObj = $this->get_file( $id );
      $this->delete_scoped_object( $oObj );    

      $payload = OLabUtilities::make_api_return( null, $tracer );
      DB::commit();

    }
    catch (Exception $exception) {

      DB::rollback();

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

      $oObj = $this->get_file( $id );

      // get/update title field
      $this->oPostData->get_text( $oObj, 'name');
      $this->oPostData->get_text_optional( $oObj, 'description');
      $this->oPostData->get_text_optional( $oObj, 'encoded_content');

      $this->oPostData->get_integer_optional( $oObj, 'type');
      $this->oPostData->get_integer_optional( $oObj, 'height');
      $this->oPostData->get_text_optional( $oObj, 'height_type');
      $this->oPostData->get_integer_optional( $oObj, 'width');
      $this->oPostData->get_text_optional( $oObj, 'width_type');
      $this->oPostData->get_text_optional( $oObj, 'copyright');
      $this->oPostData->get_text_optional( $oObj, 'origin_url');

      $this->oPostData->get_text_optional( $oObj, 'scopeLevel');
      $this->oPostData->get_integer_optional( $oObj, 'parentId');

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