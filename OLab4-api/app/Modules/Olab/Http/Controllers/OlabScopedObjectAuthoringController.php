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
use Entrada\Modules\Olab\Models\BaseModel;
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
use Entrada\Modules\Olab\Models\Questions;
use Entrada\Modules\Olab\Models\Counters;
use Entrada\Modules\Olab\Models\Servers;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\Files;
use Entrada\Modules\Olab\Models\MapNodeLinks;
use Entrada\Modules\Olab\Models\H5pResults;
use Entrada\Modules\Olab\Models\MapNodes;
use Entrada\Modules\Olab\Classes\GlobalObjectManager;
use Entrada\Modules\Olab\Classes\PostDataHandler;

class OlabScopedObjectAuthoringController extends OlabAuthoringController
{
  // override for abstract class to provide url subpath
  public function get_object_url_subpath() { return ""; }

  private function orderByIdDescending( $a, $b ) {
  
    if ( $a['id'] == $b['id'] ) {
      return 0;
    }

    return ($a < $b) ? 1 : -1;
  }

  public static function toArrayAbbreviated( array $aSource ) {
    
    $aObj = array();
    $aObj['id'] = $aSource['id'];
    $aObj['name'] = $aSource['name'];
    $aObj['description'] = $aSource['description'];
    $aObj['scopeLevel'] = $aSource['scopeLevel'];
    $aObj['parentId'] = $aSource['parentId'];
    $aObj['wiki'] = $aSource['wiki'];
    $aObj['acl'] = $aSource['acl'];

    if ( array_key_exists( 'isSystem', $aSource ))
      $aObj['isSystem'] = $aSource['isSystem'];

    if ( isset( $aSource['url'] )) {
      $aObj['url'] = $aSource['url'];
    }

    return $aObj;
  }

  /**
   * Get list of nodes for a map
   * @param Request $request 
   * @param int $map_id 
   * @return array
   */
  public function getMapScopedObjects( Request $request, int $map_id ) {
    
    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($map_id)" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $aData = array();

      $oMap = $this->get_deep_map( $map_id );
      $oNode = null;  // TODO: add support for node-level scoped object queries

      $this->append_scoped_objects( $oMap, $oNode, $aData );

      $payload = OLabUtilities::make_api_return( null, $tracer, $aData );

    }
    catch (Exception $exception) {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );
    }

    return response()->json($payload);

  }

  /**
   * Get list of nodes for a node
   * @param Request $request 
   * @param int $map_id 
   * @return array
   */
  public function getNodeScopedObjects( Request $request, int $map_id, int $node_id ) {
    
    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($map_id, $node_id)" );
    $payload = array();

    try {

      // run common controller initialization
      $this->initialize( $request );

      $aData = array();

      $oMap = $this->get_deep_map( $map_id );
      $oNode = $this->get_deep_node( $oMap, $node_id );

      $this->append_scoped_objects( $oMap, $oNode, $aData );

      $payload = OLabUtilities::make_api_return( null, $tracer, $aData );

    }
    catch (Exception $exception) {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      $payload = OLabUtilities::make_api_return( $exception, $tracer );
    }

    return response()->json($payload);

  }

  private function assign_acl( &$aItem ) {
    
    if ( $aItem['id'] < 0 ) {     
      $aItem['acl'] = "R";
    }

    else if ( $aItem['scopeLevel'] == "Servers" ) {     
      $aItem['acl'] = "R";
    }

    else {      
      $aItem['acl'] = "RW";
    }

  }

  protected function delete_scoped_object( $oObj ) {
    
    // test access control context based on object type to evaluate.
    $oAccessControl = AccessControlBase::classFactory( $oObj );
    if ( !$oAccessControl->isDeletable( $oObj->id )) {
      throw new OlabAccessDeniedException( $oObj, $oObj->id );
    }

    $oObj->delete();
  }

  protected function build_scoped_object( &$aItem, string $wikiPrefix, string $url ) {
    
    $aItem['wiki'] = "[[" . $wikiPrefix . ":" . $aItem['id'] . "]]";

    if ( strlen( $aItem['name'] ) > 0 ) {
      $aItem['wiki'] = "[[" . $wikiPrefix . ":" . $aItem['name'] . "]]";
    }

    if ( $aItem['id'] > 0 ) {
      $aItem['url'] = OLabUtilities::get_path_info()['apiBaseUrl'] . "/" . $url . "/" . $aItem['id'];
    }

    $this->assign_acl( $aItem );

  }

  /**
   * Added scoped objects to array
   * @param Olab\Models\Maps $oMap 
   * @param array $aData 
   */
  private function append_scoped_objects( Maps $oMap, $oNode, array &$aData ) {
    
    $oServer = Servers::At( Servers::DEFAULT_LOCAL_ID );
    $oScopedObject = new ScopedObjectManager( $oServer, $oMap, $oNode );

    $aData[ ScopedObjectManager::SCOPE_TYPE_QUESTIONS ] = $oScopedObject->getQuestion();
    usort( $aData[ ScopedObjectManager::SCOPE_TYPE_QUESTIONS ], array( $this, "orderByIdDescending") );

    foreach ($aData[ ScopedObjectManager::SCOPE_TYPE_QUESTIONS] as &$aItem ) {
      $this->build_scoped_object( $aItem, Questions::WIKI_TAG_QUESTION, "scopedobjects/questions" );    	
      $aItem = self::toArrayAbbreviated( $aItem );
    }
    
    $aData[ ScopedObjectManager::SCOPE_TYPE_COUNTERS ] = $oScopedObject->getCounter();
    usort( $aData[ ScopedObjectManager::SCOPE_TYPE_COUNTERS ], array( $this, "orderByIdDescending") );

    foreach ($aData[ ScopedObjectManager::SCOPE_TYPE_COUNTERS] as &$aItem ) {
      $this->build_scoped_object( $aItem, Counters::WIKI_TAG_COUNTER, "scopedobjects/counters" );    
      $aItem = self::toArrayAbbreviated( $aItem );
    }

    $aData[ ScopedObjectManager::SCOPE_TYPE_FILES ] = $oScopedObject->getFile();
    usort( $aData[ ScopedObjectManager::SCOPE_TYPE_FILES ], array( $this, "orderByIdDescending") );

    foreach ($aData[ ScopedObjectManager::SCOPE_TYPE_FILES] as &$aItem ) {
      $this->build_scoped_object( $aItem, Files::WIKI_TAG_MEDIA_RESOURCE, "scopedobjects/files" );    	
      $aItem = self::toArrayAbbreviated( $aItem );
    }

    $aData[ ScopedObjectManager::SCOPE_TYPE_CONSTANTS ] = $oScopedObject->getConstant();
    usort( $aData[ ScopedObjectManager::SCOPE_TYPE_CONSTANTS ], array( $this, "orderByIdDescending") );

    foreach ($aData[ ScopedObjectManager::SCOPE_TYPE_CONSTANTS] as &$aItem ) {
      $this->build_scoped_object( $aItem, Constants::WIKI_TAG_CONSTANT, "scopedobjects/constants" );    	
      $aItem = self::toArrayAbbreviated( $aItem );
    }

  }

}