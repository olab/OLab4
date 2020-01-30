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
use Entrada\Modules\Olab\Classes\NodalBoundingBox;
use Entrada\Modules\Olab\Classes\OlabAccessDeniedException;
use Entrada\Modules\Olab\Classes\OlabObjectNotFoundException;

use \H5PCore;
use Entrada\Modules\Olab\Classes\h5p\H5PPlugin;
use Entrada\Modules\Olab;
use Entrada\Modules\Olab\Models\UserState;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\MapNodeTypes;
use Entrada\Modules\Olab\Models\Node;
use Entrada\Modules\Olab\Models\NodeCounter;
use Entrada\Modules\Olab\Models\Counters;
use Entrada\Modules\Olab\Models\Servers;
use Entrada\Modules\Olab\Models\CounterActions;
use Entrada\Modules\Olab\Models\MapTemplates;
use Entrada\Modules\Olab\Models\MapNodeLinks;
use Entrada\Modules\Olab\Models\H5pResults;
use Entrada\Modules\Olab\Models\MapNodes;
use Entrada\Modules\Olab\Classes\GlobalObjectManager;
use Entrada\Modules\Olab\Classes\PostDataHandler;
use \Ds\Map;

class OlabMapAuthoringController extends OlabAuthoringController
{
  private $x_insert_coord = 0;
  private $y_insert_coord = 0;

  // override for abstract class to provide url subpath
  public function get_object_url_subpath() { return "maps"; }

  public function create( Request $request ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );
    $payload = array();

    try
    {

      $oMap = new Maps();

      $this->oPostData = new PostDataHandler( $request );

      // test if creating map from template, or creating new map
      $template_id = $this->oPostData->get_integer_optional( $template_id, 'templateId' );

      if ( $template_id != PostDataHandler::NOT_FOUND ) {
        $payload['map'] = $this->_createFromTemplate( $request, $oMap, $template_id );        
      }
      else {
        $payload['map'] = $this->_createNewMap( $request, $oMap );
      }    	

      $oServer = Servers::default();
      $oScopedObject = new ScopedObjectManager( $oServer, $oMap, null );     

      $payload['map']['themes'] = $oScopedObject->getThemeList();

      $payload = OLabUtilities::make_api_return( null, $tracer, $payload );

    }
    catch (Exception $exception)
    {
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

      // get all active maps
      $records = Maps::active()->get(['id', 'name', 'abstract', 'renderer_version'] );
      $mapData = array();

      // test access control context based on object type to evaluate.
      // in this case, it's a collection of maps.
      $oAccessControl = AccessControlBase::classFactory( $records );

      // loop through maps. if map is listable, add it to list for return
      foreach ($records as $record) {

        // test if have list access to map
        if ( $oAccessControl->isListable( $record->id )) {
          $map = array( 'id' => $record->id,
                                         'name' => $record->name,
                                         'description' => $record->abstract,
                                         'url' => $this->get_object_url( $record->id ));

          array_push( $mapData, $map );

        }
      }

      Log::debug("found " . sizeof( $mapData ) . " maps to add to index list" );

      $payload = OLabUtilities::make_api_return( null, $tracer, $mapData );

    }
    catch (Exception $exception) {

      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );

    }

    return response()->json($payload);

  }

  public function getSingle( Request $request, int $map_id ) {

    //// test if map_id == 0, meaning we are creating a new map
    //if ( $map_id == 0 ) {    
    //  return $this->createMap( $request );
    //}

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($map_id)" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );             
      //$oMap = Maps::active()->with( ScopedObjectManager::RELATION_NAME_THEMES)
      //                      ->find($map_id);
      $oMap = $this->get_deep_map( $map_id );

      $aData = array();
      $aData['map'] = $oMap->toArray();

      $aData['nodeCount'] = $oMap->MapNodes()->count();
      $aData['linkCount'] = $oMap->MapNodeLinks()->count();
      $aData['counterCount'] = $oMap->Counters()->count();
      $aData['questionCount'] = $oMap->Questions()->count();
      $aData['map']['author'] = HostSystemApi::getUser( $aData['map']['author']);

      $oServer = Servers::default();
      $oScopedObject = new ScopedObjectManager( $oServer, $oMap, null );     

      $aData['map']['themes'] = $oScopedObject->getThemeList();

      $payload = OLabUtilities::make_api_return( null, $tracer, $aData );

    }
    catch (Exception $exception) {

      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );

    }

    return response()->json($payload);

  }

  public function delete( Request $request, int $map_id ) {
    
    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($map_id)" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $oMap = $this->get_map( $map_id );

      DB::beginTransaction();
      $oMap->delete();
      DB::commit();
      
      Log::info( "deleted map. id = " . $map_id );

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

  /**
   * Edits a map
   * @param Request $request 
   * @param int $map_id 
   */
  public function edit( Request $request, $map_id ) {
    
    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($map_id)" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $oObj = $this->get_map( $map_id );

      $this->oPostData->get_text_optional( $oObj, 'name' );
      $this->oPostData->get_text_optional( $oObj, 'description' );
      $this->oPostData->get_text_optional( $oObj, 'keywords' );
      $this->oPostData->get_integer_optional( $oObj, 'enabled');    
      $this->oPostData->get_text_optional( $oObj, 'notes');

      // package individual flag fields into validation column
      $aVerification = json_decode($oObj->verification, true);
      // if verification not set, initialize it with an empty JSON string
      // and try decoding it again.
      if ( $aVerification == null ) {
        $aVerification = json_decode('{}', true);
      }

      $this->oPostData->get_integer_optional( $aVerification, 'linkLogicVerified', 0 );
      $this->oPostData->get_integer_optional( $aVerification, 'nodeContentVerified', 0 );
      $this->oPostData->get_integer_optional( $aVerification, 'mediaContentVerified', 0 );
      $this->oPostData->get_integer_optional( $aVerification, 'mediaContentComplete' , 0 );
      $this->oPostData->get_integer_optional( $aVerification, 'mediaCopyrightVerified', 0 );
      $this->oPostData->get_integer_optional( $aVerification, 'instructorGuideComplete' , 0 );
      $oObj->verification = json_encode( $aVerification );

      $this->oPostData->get_integer_optional( $oObj, 'sendXapiStatements');
      $this->oPostData->get_integer_optional( $oObj, 'securityType');
      $this->oPostData->get_integer_optional( $oObj, 'themeId');


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

  private function _createNewMap( Request $request, Maps &$oMap ) {
    
    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      DB::beginTransaction();

      // create and save a new map
      $oMap = new Maps();
      $oMap->author_id = $this->user_id;
      $oMap->save();
      Log::info( "created new map. id = " . $oMap->id );

      // create and save a new (root) node
      $oNode = new MapNodes();
      $oNode->map_id = $oMap->id;
      $oNode->type_id = 1;
      $oNode->save();
      Log::info( "created initial node. id = " . $oNode->id );

      // add node to map
      $aData = array();
      $aData = $oMap->toArray();
      $aData['nodes'] = array();
      $aData['nodes'][] = $oNode->toArray();

      DB::commit();

      //$payload = OLabUtilities::make_api_return( null, $tracer, $aData );
      $payload = $aData;

    }
    catch (Exception $exception) {
      DB::rollback();
      throw $exception;
    }

    return $payload;
  }

  private function _createFromTemplate( Request $request, Maps &$oMap , int $template_id ) {
    
    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $aData = array();
      
      DB::beginTransaction();
 
      // get source template
      $oTemplate= $this->get_template( $template_id );

      // create destination template
      $oMap = $this->create_map( $this->user_id, $oTemplate );

      // clone the map nodes and links
      $this->copy_template_to_map( false, $oTemplate, $oMap );

      // clone the map-level scoped objects
      $this->copy_scopeds_to_map( $oTemplate, $oMap );

      $oMap = $this->get_deep_map( $oMap->id );

      // add node to map
      $aData = $oMap->toArray();

      DB::commit();

      //$payload = OLabUtilities::make_api_return( null, $tracer, $aData );
      $payload = $aData;

    }
    catch (Exception $exception) {
      DB::rollback();
      throw $exception;
    }

    return $payload;

  }

  /**
   * Clone template into map
   * @param MapTemplates $oSourceTemplate 
   * @param Maps $oDestinationMap 
   */
  private function copy_template_to_map( bool $suppressRootNodes, MapTemplates $oSourceTemplate, Maps &$oDestinationMap ) {
    
    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

    $map_has_nodes = $oDestinationMap->MapNodes()->count() > 0;

    // if target map has nodes, meaning we need to transform the
    // template nodes 'apart' from the map nodes
    if ( $map_has_nodes ) {

      $oMapBox = new NodalBoundingBox( $oDestinationMap );
      $aoNodes = $oDestinationMap->MapNodes()->get();
      Log::debug("transforming " . sizeof( $aoNodes ) . " map nodes" );

      // transform the map nodes to origin coordinates
      foreach ( $aoNodes as &$oNode ) {
        $oMapBox->transform_to_origin( $oNode );
        $oNode->save();
      }

      if ( $this->have_insert_coord ) {
        // build a vector based on the passed-in insertion point coord
        $transform_vector['x'] = $this->x_insert_coord;
        $transform_vector['y'] = $this->y_insert_coord;
      }
      else {
        // build a vector based on the bounding box on the map nodes
        $transform_vector['x'] = $oMapBox->transformedBox['lowerRight']['x'] + 350;
        $transform_vector['y'] = 0;
      }

      Log::debug("transform_vector: (" . $transform_vector['x'] . ", " . 
                                         $transform_vector['y']  . ")" );

    }

    // DS map to hold old id to new node id translation
    $nodeIdMap = new Map([]);

    $oTemplateBox = new NodalBoundingBox( $oSourceTemplate );
    $aoNodes = $oSourceTemplate->MapNodes()->get();
    Log::debug("transforming " . sizeof( $aoNodes ) . " template nodes" );

    foreach ($aoNodes as $oNode ) {

      $oNewNode = MapNodes::createFrom( $oNode );
      unset( $oNewNode->id );

      if ( $map_has_nodes ) {

        $oTemplateBox->transform_to_origin( $oNewNode );
        $oTemplateBox->transform( $oNewNode, $transform_vector );

      }

      // test if resetting root nodes in template to 'child' nodes.
      if ( $suppressRootNodes ) {

        // change any root nodes in inserted template to 'child'
        if ( $oNewNode->IsRootNode() ) {
          Log::debug("remapped node: " . $oNewNode->title . " from root to child" );
          $oNewNode->type_id = MapNodeTypes::ChildTypeId();
        }

      }

      $oNewNode->map_id = $oDestinationMap->id;
      $oNewNode->save();

      Log::debug( "copied node. id = " . $oNode->id . " -> " . $oNewNode->id );

      // save old node_id -> new node_id in a map because we will need to
      // use the map to remap node links to the new nodes
      $nodeIdMap->put( $oNode->id, $oNewNode->id );

    }

    // get and copy node links from template
    $aoLinks = $oSourceTemplate->MapNodeLinks()->get();
    foreach ($aoLinks as $oItem ) {

      $oNewNodeLink = MapNodeLinks::createFrom( $oItem );
      unset( $oNewNodeLink->id );

      // remap link from old id to cloned node ids
      $oNewNodeLink->node_id_1 = $nodeIdMap->get( $oNewNodeLink->node_id_1 );
      $oNewNodeLink->node_id_2 = $nodeIdMap->get( $oNewNodeLink->node_id_2 );
      $oNewNodeLink->map_id = $oDestinationMap->id;
      $oNewNodeLink->save();

      Log::debug( "copied node link. id = " . $oItem->id . " -> " . $oNewNodeLink->id );

    }

  }

  public function insertFromTemplate( Request $request, int $map_id ) {
    
    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $aData = array();
      
      DB::beginTransaction();
 
      $this->oPostData = new PostDataHandler( $request );

      // test if creating map from template, or creating new map
      $template_id = $this->oPostData->get_integer( $template_id, 'templateId' );

      // get optional target insertion point coordinates
      $this->x_insert_coord = $this->oPostData->get_integer_optional( $this->x_insert_coord, 'x' );
      $this->y_insert_coord = $this->oPostData->get_integer_optional( $this->y_insert_coord, 'y' );

      $this->have_insert_coord = ( ( $this->x_insert_coord != PostDataHandler::NOT_FOUND ) && 
                                   ( $this->y_insert_coord != PostDataHandler::NOT_FOUND ) );

      // get source template
      $oTemplate = $this->get_template( $template_id );          
      // get source map
      $oMap = $this->get_deep_map( $map_id );

      // clone and save the the map nodes and links
      $this->copy_template_to_map( true, $oTemplate, $oMap );

      DB::commit();

      // re-read the map with the added nodes
      $oMap = $this->get_deep_map( $map_id );
      $aData = $oMap->toArray();

      $payload = OLabUtilities::make_api_return( null, $tracer, $aData );

    }
    catch (Exception $exception) {
      DB::rollback();
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );
    }

    return $payload;

  }

  public function getCounterActions( Request $request, int $map_id ) {
    
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($map_id)" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $payload['counters'] = Counters::ByMapId( $map_id )->select('id', 'name', 'description')->get()->toArray();
      $payload['nodes'] = MapNodes::ByMapId( $map_id )->select('id', 'title')->get()->toArray();
      $payload['actions'] = CounterActions::ByMap( $map_id );

      $payload = OLabUtilities::make_api_return( null, $tracer, $payload );

    }
    catch (Exception $exception) {

      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );

    }

    return response()->json($payload);

  }

  public function editCounterActions( Request $request, int $map_id ) {
    
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($map_id)" );
    $payload = array();
    $errors = false;

    try {

      // run common controller initialization
      $this->initialize( $request );

      $count = $this->oPostData->count();

      for ($i = 0; $i < $count; $i++) {
    
        $item_payload = array();
        
        try
        {
          $id = 0;

          $this->oPostData->get_at( $i );
          $this->oPostData->get_integer( $id, 'id' );

          $oObj = CounterActions::where( [['map_id', '=', $map_id], [ 'id', '=', $id ]] )->first();

          if ( $oObj == null ) {
            throw new Exception( "Unable to find id " . $id );
          }

          $this->oPostData->get_text_optional( $oObj, 'expression' );
          $this->oPostData->get_integer_optional( $oObj, 'visible');

          $oObj->save();        	
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
      }

    }
    catch (Exception $exception) {

      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );

    }

    return response()->json($payload);

  }

}