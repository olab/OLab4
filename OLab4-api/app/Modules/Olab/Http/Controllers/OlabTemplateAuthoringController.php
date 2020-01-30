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

use \H5PCore;
use Entrada\Modules\Olab\Classes\h5p\H5PPlugin;
use Entrada\Modules\Olab;
use Entrada\Modules\Olab\Models\UserState;
use Entrada\Modules\Olab\Models\Node;
use Entrada\Modules\Olab\Models\NodeCounter;
use Entrada\Modules\Olab\Models\Servers;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\MapTemplates;
use Entrada\Modules\Olab\Models\MapNodeLinks;
use Entrada\Modules\Olab\Models\H5pResults;
use Entrada\Modules\Olab\Models\MapNodes;
use Entrada\Modules\Olab\Classes\GlobalObjectManager;
use Entrada\Modules\Olab\Classes\PostDataHandler;
use \Ds\Map;

class OlabTemplateAuthoringController extends OlabAuthoringController
{
  // override for abstract class to provide url subpath
  public function get_object_url_subpath() { return "templates"; }

  public function getMany( Request $request ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      // get all templates
      $aoTemplates = MapTemplates::Active()->get(['id', 'name', 'abstract', 'renderer_version'] );
      $aaItems = array();

      // test access control context based on object type to evaluate.
      // in this case, it's a collection of maps.
      $oAccessControl = AccessControlBase::classFactory( $aoTemplates );

      // loop through maps. if map is listable, add it to list for return
      foreach ($aoTemplates as $oTemplate) {

        // test if have list access to map
        if ( $oAccessControl->isListable( $oTemplate->id )) {
          $aTemplate = array( 'id' => $oTemplate->id,
                        'name' => $oTemplate->name,
                        'description' => $oTemplate->abstract,
                        'url' => $this->get_object_url( $oTemplate->id ) );

          array_push( $aaItems, $aTemplate );

        }
      }

      Log::debug("found " . sizeof( $aaItems ) . " templates to add to index list" );

      $payload = OLabUtilities::make_api_return( null, $tracer, $aaItems );

    }
    catch (Exception $exception) {

      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );

    }

    return response()->json($payload);

  }

  public function get( Request $request, int $template_id ) {

    // test if $template_id == 0, meaning we are creating a new template from a map
    //if ( $template_id == 0 ) {    
    //  return $this->createFromMap( $request );
    //}

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($template_id)" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $oObj = $this->get_template( $template_id );

      $aData = $oObj->toArray();

      //OLabUtilities::safe_rename( $aData, 'nodes' );
      //OLabUtilities::safe_rename( $aData, 'links' );

      $aData['nodeCount'] = $oObj->MapNodes()->count();
      $aData['linkCount'] = $oObj->MapNodeLinks()->count();
      $aData['counterCount'] = $oObj->Counters()->count();
      $aData['questionCount'] = $oObj->Questions()->count();

      $payload = OLabUtilities::make_api_return( null, $tracer, $aData );

    }
    catch (Exception $exception) {

      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );

    }

    return response()->json($payload);

  }

  public function delete( Request $request, int $template_id ) {
    
    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($template_id)" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $oMap = $this->get_map( $template_id );

      DB::beginTransaction();
      $oMap->delete();
      DB::commit();
      
      Log::info( "deleted template. id = " . $template_id );

      // if it makes it here, then there's no error
      $payload = OLabUtilities::make_api_return( null, $tracer );

    }
    catch (Exception $exception) {
      DB::rollback();
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );
    }

    return response()->json($payload);
  }

  public function create( Request $request ) {
    
    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $aData = array();
      
      $oPostData = new PostDataHandler( $request );

      // test if creating map from template, or creating new map
      $oPostData->get_integer( $map_id, 'mapId' );
      $oPostData->get_text( $template_name, 'name' );

      DB::beginTransaction();
 
      // test if name does not exist already
      if ( $this->template_exists( $template_name )) {
        throw new Exception("Template already exists");   
      }

      // get source map
      $oMap = $this->get_map( $map_id );

      // create destination template
      $oTemplate = $this->create_template( $template_name, $oMap );

      // clone the map nodes and links
      $this->copy_map_to_template( $oMap, $oTemplate );

      // add node to map
      $aData = array();
      $aData['id'] = $oTemplate->id;

      DB::commit();

      $payload = OLabUtilities::make_api_return( null, $tracer, $aData );

    }
    catch (Exception $exception) {
      DB::rollback();
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );
    }

    return $payload;

  }

  public function cloneInsertNodes( Request $request, int $template_id ) {
    
    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($template_id)" );
    $nodeIdMap = new Map([]);
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $oMap = null;

      $oPostData = new PostDataHandler( $request );

      // test if a target map_id parameter is provided
      $oPostData->get_integer( $map_id, 'map_id', false );

      if ( $map_id != PostDataHandler::NOT_FOUND ) {
        $oMap = $this->get_map( $map_id );
      }
      else {
        throw new Exception("Missing map_id parameter");
      }

      $oTemplate = $this->get_template( $template_id );

      DB::beginTransaction();

      // get and copy/save nodes from template
      $aoNodes = $oTemplate->MapNodes()->get();
      foreach ($aoNodes as $oItem ) {

        Log::debug( "copying node. id = " . $oItem->id );

        $oNewNode = MapNodes::createFrom( $oItem );
        unset( $oNewNode->id );
        $oNewNode->map_id = $oMap->id;
        $oNewNode->save();

        // save old node_id -> new node_id in a map because we will need to
        // use the map to remap node links to the new nodes
        $this->$nodeIdMap->put( $oItem->id, $oNewNode->id );

      }

      // get and copy node links from template
      $aoLinks = $oTemplate->MapNodeLinks()->get();
      foreach ($aoLinks as $oItem ) {

        Log::debug( "copying node link. id = " . $oItem->id );

        $oNewNodeLink = MapNodes::createFrom( $oItem );
        unset( $oNewNodeLink->id );

        // remap link from old id to cloned node ids
        $oNewNodeLink->node_id_1 = $nodeIdMap->get( $oNewNodeLink->node_id_1 );
        $oNewNodeLink->node_id_2 = $nodeIdMap->get( $oNewNodeLink->node_id_2 );
        $oNewNodeLink->map_id = $oMap->id;
        $oNewNodeLink->save();

      }

      DB::commit();

      $payload = OLabUtilities::make_api_return( null, $tracer );

    }
    catch (Exception $exception) {
      DB::rollback();
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );
    }

    return response()->json($payload);

  }
}