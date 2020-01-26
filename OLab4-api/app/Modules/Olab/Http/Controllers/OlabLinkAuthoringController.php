<?php

namespace Entrada\Modules\Olab\Http\Controllers;

use \Exception;
use Illuminate\Support\Facades\Log;
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
use Entrada\Modules\Olab\Models\BaseModel;
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
use Entrada\Modules\Olab\Classes\OlabObjectNotFoundException;

class OlabLinkAuthoringController extends OlabAuthoringController
{
  // override for abstract class to provide url subpath
  public function get_object_url_subpath() { return "links"; }

  public function getTemplate( Request $request ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $oNodeLink = new MapNodeLinks();
      $aNodeLink = $oNodeLink->toArray();
      
      $payload = OLabUtilities::make_api_return( null, $tracer, $aNodeLink );
    }
    catch (Exception $exception) {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );
    }

    return response()->json($payload);

  }

  /**
   * Gets a link
   * @param Request $request 
   * @param int $map_id 
   * @param int $node_id 
   * @param int $link_id 
   */
  public function getSingle( Request $request, int $map_id, int $node_id, int $link_id ) {

    // test if $link_id == 0, meaning we are creating a new link
    //if ( $link_id == 0 ) {    
    //  return $this->createLink( $request, $map_id, $node_id );
    //}

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($map_id, $node_id, $link_id)" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $oMap = $this->get_map( $map_id );
      $oSourceNode = $this->get_node( $oMap, $node_id );

      $oLink = $this->get_link( $oMap, $link_id );

      $payload = OLabUtilities::make_api_return( null, $tracer, $oLink->toArray() );
    }
    catch (Exception $exception) {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );
    }

    return response()->json($payload);

  }

  /**
   * Creates a new link
   * @param Request $request 
   * @param int $map_id 
   * @param int $node_id 
   */
  public function create( Request $request, int $map_id, int $node_id ) {
    
    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($map_id, $node_id)" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $oMap = $this->get_map( $map_id );
      $oSourceNode = $this->get_node( $oMap, $node_id );

      // get destination_id with validation, then get desintation node
      $this->oPostData->get_integer( $destination_id, 'destinationId' );
      $oDestinationNode = $this->get_node( $oMap, $destination_id );

      // create and save a new node link
      $oLink = new MapNodeLinks();
      $oLink->map_id = $map_id;
      $oLink->node_id_1 = $node_id;
      $oLink->node_id_2 = $oDestinationNode->id;

      Log::info( "created new link. id = " . $oLink->id );

      $oLink->save();

      // if it makes it here, then there's no error
      $payload = OLabUtilities::make_api_return( null, $tracer, $oLink->toArray() );
    }
    catch (Exception $exception) {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );
    }

    return response()->json($payload);

  }

  /**
   * Edits a map node link
   * @param Request $request 
   * @param int $map_id 
   * @param int $node_id 
   * @param int $link_id 
   */
  public function edit( Request $request, int $map_id, int $node_id, int $link_id ) {
    
    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($map_id, $node_id, $link_id)" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $oMap = $this->get_map( $map_id );
      $oSourceNode = $oMap->MapNodes()->find( $node_id );
      if ( $oSourceNode == null ) {
        throw new OlabObjectNotFoundException("MapNode", $node_id );
      }

      $oObj = $this->get_link( $oMap, $link_id );

      $this->oPostData->get_integer_optional( $oObj, 'sourceId');
      $this->oPostData->get_integer_optional( $oObj, 'destinationId');
      $this->oPostData->get_integer_optional( $oObj, 'imageId');
      $this->oPostData->get_text_optional(    $oObj, 'text');
      $this->oPostData->get_integer_optional( $oObj, 'order');
      $this->oPostData->get_integer_optional( $oObj, 'probability');
      $this->oPostData->get_integer_optional( $oObj, 'hidden');

      $this->oPostData->get_integer_optional( $oObj, 'thickness');
      $this->oPostData->get_color_optional(   $oObj, 'color');
      $this->oPostData->get_integer_optional( $oObj, 'lineType');
      $this->oPostData->get_integer_optional( $oObj, 'linkStyleId');
      $this->oPostData->get_integer_optional( $oObj, 'followOnce');

      $this->write_object( $oObj );    

      // if it makes it here, then there's no error
      $payload = OLabUtilities::make_api_return( null, $tracer );
    }
    catch (Exception $exception) {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );
    }

    return response()->json($payload);

  }

  /**
   * Deletes a map node link
   * @param Request $request 
   * @param int $map_id 
   * @param int $node_id 
   * @param int $link_id 
   */
  public function delete( Request $request, int $map_id, int $node_id, int $link_id ) {
    
    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($map_id, $node_id, $link_id)" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $oMap = $this->get_map( $map_id );
      $oSourceNode = $this->get_node( $oMap, $node_id );
      $oLink = $this->get_link( $oMap, $link_id );

      $oLink->delete();

      // if it makes it here, then there's no error
      $payload = OLabUtilities::make_api_return( null, $tracer );
    }
    catch (Exception $exception) {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );
    }

    return response()->json($payload);

  }
}