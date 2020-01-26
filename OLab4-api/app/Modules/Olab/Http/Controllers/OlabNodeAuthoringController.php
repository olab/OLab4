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
use Entrada\Modules\Olab\Models\Map;
use Entrada\Modules\Olab\Models\Node;
use Entrada\Modules\Olab\Models\NodeCounter;
use Entrada\Modules\Olab\Models\Servers;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\MapNodeLinks;
use Entrada\Modules\Olab\Models\H5pResults;
use Entrada\Modules\Olab\Models\MapNodes;
use Entrada\Modules\Olab\Classes\GlobalObjectManager;
use Entrada\Modules\Olab\Classes\PostDataHandler;

class OlabNodeAuthoringController extends OlabAuthoringController
{
  // override for abstract class to provide url subpath
  public function get_object_url_subpath() { return "nodes"; }

  /**
   * Get list of nodes for a map
   * @param Request $request 
   * @param int $map_id 
   * @return array
   */
  public function getMany( Request $request, int $map_id ) {
    
    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($map_id)" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $aData = array();

      $oMap = $this->get_deep_map( $map_id );

      // build nodes array
      $aMap = $oMap->toArray();

      $aData['nodes'] = array();

      foreach ( $aMap['nodes'] as $aNode ) {

        $aNode['url'] = OLabUtilities::get_path_info()['apiBaseUrl'] . "/maps/" . $map_id . 
                        "/" . $this->get_object_url_subpath() . "/" . $aNode['id'];

        $aData['nodes'][] = $aNode;
      }

      // build node links array

      $aaLinks = $aMap['links'];

      $aData['links'] = array();

      foreach ($aaLinks as $aLink ) {

        $aLink['url'] = OLabUtilities::get_path_info()['apiBaseUrl'] . "/maps/" . $map_id . 
                                                                       "/nodes/" . $aLink['id'] . 
                                                                       "/links/" . $aLink['sourceId'];
        $aData['links'][] = $aLink;
      }

      // detect and hook up 'reverse' links
      foreach ($aData['links'] as $aOuterItem ) {

        foreach ($aData['links'] as $aInnerItem ) {

          if ( ( $aInnerItem['sourceId'] == $aOuterItem['destinationId'] ) &&
               ( $aInnerItem['destinationId'] == $aOuterItem['sourceId'] ) ) {
            $aOuterItem['reverse_id'] = $aInnerItem['id'];
          }

        }

      }

      $payload = OLabUtilities::make_api_return( null, $tracer, $aData );


    }
    catch (Exception $exception) {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );
    }

    return response()->json($payload);

  }

  public function getTemplate( Request $request ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $oNode = new MapNodes();
      $aNode = $oNode->toArray();
      
      $payload = OLabUtilities::make_api_return( null, $tracer, $aNode );
    }
    catch (Exception $exception) {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );
    }

    return response()->json($payload);

  }

  public function get( Request $request, int $map_id, int $node_id ) {
    
    // test if map_id == 0, meaning we are creating a new map
    if ( $node_id == 0 ) {    
      return $this->createNode( $request, $map_id );
    }

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($map_id, $node_id)" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $oMap = $this->get_map( $map_id );
      $oNode = $this->get_node( $oMap, $node_id );

      $aNode = $oNode->toArray();
      
      // build node links array

      $aoLinks = $oMap->MapNodeLinks()->get();
      $aNode['links'] = array();

      foreach ($aoLinks as $oItem ) {

        // if link does not apply to current node, skip it
        if ( $oItem->node_id_2 != $oNode->id ) {
          continue;
        }

      	$aLink = array();
        $aLink['id'] = $oItem->id;
        $aLink['destinationId'] = $oItem->node_id_2;
        $aLink['sourceId'] = $oItem->node_id_1;
        $aLink['color'] = $oItem->color;

        $aNode['links'][] = $aLink;

      }

      $payload = OLabUtilities::make_api_return( null, $tracer, $aNode );
    }
    catch (Exception $exception) {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );
    }

    return response()->json($payload);

  }

  public function create( Request $request, int $map_id ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($map_id)" );
    $payload = array();
    $aData = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $source_id = 0;
      $x = 0;
      $y = 0;

      $this->oPostData = new PostDataHandler( $request );

      // get optional source node id
      $source_id = $this->oPostData->get_integer_optional( $source_id, 'sourceId' );

      // get coordinates parameters
      $this->oPostData->get_integer( $x, 'x' );
      $this->oPostData->get_integer( $y, 'y' );
      
      DB::beginTransaction();

      $oMap = $this->get_map( $map_id );

      // create and save a new node
      $oNode = new MapNodes();
      $oNode->map_id = $oMap->id;
      $oNode->x = $x;
      $oNode->y = $y;

      $oNode->save();
      $aData['id'] = $oNode->id;
     
      Log::info( "created new node. id = " . $oNode->id );

      // if source node specified, create a node link connecting
      // the source to the new node
      if ( $source_id != PostDataHandler::NOT_FOUND ) {

        // verify can get link's source node
        $oLinkSourceNode = $this->get_node( $oMap, $source_id );

        $oLink = new MapNodeLinks();
        $oLink->map_id = $oMap->id;
        $oLink->node_id_1 = $oLinkSourceNode->id;
        $oLink->node_id_2 = $oNode->id;

        $oLink->save();

        Log::info( "created new node link. id = " . $oLink->id );

        $aData['links'] = array();
        $aData['links']['id'] = $oLink->id;

      }

      DB::commit();

      $payload = OLabUtilities::make_api_return( null, $tracer, $aData );

    }
    catch (Exception $exception) {
      DB::rollback();
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );
    }

    return response()->json($payload);
  }

  public function edits( Request $request, int $map_id ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($map_id)" );
    $payload = array();
    $errors = false;

    try {

      // run common controller initialization
      $this->initialize( $request );

      $count = $this->oPostData->count();
      DB::beginTransaction();

      for ($i = 0; $i < $count; $i++) {

        $item_payload = array();

        try
        {
          $id = 0;

          $this->oPostData->get_at( $i );
          $this->oPostData->get_integer( $id, 'id' );

          $oMap = $this->get_map( $map_id );
          $this->get_node( $oMap, $id );

          $this->_internalEdit( $request, $map_id, $id );

        }
        catch (Exception $exception)
        {
          OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
          $item_payload = OLabUtilities::make_api_return( $exception, $tracer );
          $errors = true;
        }

        array_push( $payload, $item_payload );

      }

      $payload = OLabUtilities::make_api_return( null, $tracer, $payload );

      if ( $errors ) {
        $payload['status'] = 1;
        $payload['message'] = "Error(s) occurred.  Check inner data.";
        DB::rollback();
      }
      else {
        DB::commit();
      }

    }
    catch (Exception $exception) {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );
    }

    return response()->json($payload);

  }

  /**
   * Edits a map node
   * @param Request $request 
   * @param int $map_id 
   * @param int $node_id 
   * @param int $link_id 
   */
  public function edit( Request $request, int $map_id, int $node_id, bool $reThrowException = false ) {
    
    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($map_id, $node_id)" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $this->_internalEdit( $request, $map_id, $node_id );

      // if it makes it here, then there's no error
      $payload = OLabUtilities::make_api_return( null, $tracer );
    }
    catch (Exception $exception) {

      if ( $reThrowException ) {
        throw $exception;
      }

      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );
    }

    return response()->json($payload);

  }

  private function _internalEdit( Request $request, int $map_id, int $node_id ) {
    
    $oMap = $this->get_map( $map_id );
    $oObj = $this->get_node( $oMap, $node_id );

    // get/update title field
    $this->oPostData->get_text_optional( $oObj, 'title');
    $this->oPostData->get_text_optional( $oObj, 'text');
    $this->oPostData->get_integer_optional( $oObj, 'typeId');
    $this->oPostData->get_integer_optional( $oObj, 'probability');
    $this->oPostData->get_integer_optional( $oObj, 'priorityId');
    $this->oPostData->get_text_optional( $oObj, 'conditional');
    $this->oPostData->get_text_optional( $oObj, 'info');
    $this->oPostData->get_integer_optional( $oObj, 'isPrivate');
    $this->oPostData->get_integer_optional( $oObj, 'linkStyleId');
    $this->oPostData->get_integer_optional( $oObj, 'linkTypeId');
    $this->oPostData->get_integer_optional( $oObj, 'kfp');
    $this->oPostData->get_integer_optional( $oObj, 'undo');
    $this->oPostData->get_integer_optional( $oObj, 'end');
    $this->oPostData->get_integer_optional( $oObj, 'height');
    $this->oPostData->get_integer_optional( $oObj, 'width');
    $this->oPostData->get_integer_optional( $oObj, 'locked');
    $this->oPostData->get_integer_optional( $oObj, 'collapsed');
    $this->oPostData->get_integer_optional( $oObj, 'isEnd');
    $this->oPostData->get_integer_optional( $oObj, 'x');
    $this->oPostData->get_integer_optional( $oObj, 'y');
    $this->oPostData->get_color_optional( $oObj, 'color');
    $this->oPostData->get_text_optional( $oObj, 'annotation');
    $this->oPostData->get_integer_optional( $oObj, 'visitOnce');

    // ensure we have node text
    if ( $oObj->text == null ) {
      $oObj->text  = "";
    }

    // HACK: temporary fix to correct malformed <span style=""> attribute,
    // which is malformed.
    $this->fix_node_text( $oObj );

    $this->write_object( $oObj );  
  }

  public function delete( Request $request, int $map_id, int $node_id ) {
    
    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($map_id, $node_id)" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $oMap = $this->get_map( $map_id );
      $oNode = $oMap->MapNodes()->findOrFail( $node_id );

      DB::beginTransaction();
      $oNode->delete();
      DB::commit();
      
      Log::info( "deleted node. id = " . $node_id );

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

  protected function fix_node_text( &$oObj ) {
    
    if ( $oObj->text == null )
      return;

    if ( strlen( $oObj->text ) == 0 )
      return;

    $oObj->text = str_replace( "\"Segoe UI\"", "Segoe UI", $oObj->text );
    $oObj->text = str_replace( "\"Fira Sans\"", "Fira Sans", $oObj->text );
    $oObj->text = str_replace( "\"Droid Sans\"", "Droid Sans", $oObj->text );
    $oObj->text = str_replace( "\"Helvetica Neue\"", "Helvetica Neue", $oObj->text );
 
  }
}