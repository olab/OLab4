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
use Entrada\Modules\Olab\Classes\OlabAccessDeniedException;
use Entrada\Modules\Olab\Classes\OlabObjectNotFoundException;

use \H5PCore;
use Entrada\Modules\Olab\Classes\h5p\H5PPlugin;
use Entrada\Modules\Olab;
use Entrada\Modules\Olab\Models\UserState;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\MapTemplates;
use Entrada\Modules\Olab\Models\Questions;
use Entrada\Modules\Olab\Models\Globals;
use Entrada\Modules\Olab\Models\Courses;
use Entrada\Modules\Olab\Models\QuestionResponses;
use Entrada\Modules\Olab\Models\Node;
use Entrada\Modules\Olab\Models\Counters;
use Entrada\Modules\Olab\Models\Servers;
use Entrada\Modules\Olab\Models\MapNodeLinks;
use Entrada\Modules\Olab\Models\H5pResults;
use Entrada\Modules\Olab\Models\MapNodes;
use Entrada\Modules\Olab\Classes\GlobalObjectManager;
use Entrada\Modules\Olab\Classes\PostDataHandler;
use \Ds\Map;

abstract class OlabAuthoringController extends OlabController
{
  // derived objects are expected to provide the url subpath
  abstract public function get_object_url_subpath();

  /**
   * Build the object url
   * @param mixed $id 
   * @return string
   */
  protected function get_object_url( $id ) {    
    return OLabUtilities::get_path_info()['apiBaseUrl'] . "/" . $this->get_object_url_subpath() . "/" . $id;
  }

  /**
   * Test if template (by name) exists
   * @param string $name 
   */
  protected function template_exists( string $name ) {
    
    $oObj = MapTemplates::ByName( $name )->first();
    return $oObj != null;

  }

  protected function get_template( int $id ) {
    
    $oObj = MapTemplates::active()
              ->with( 'MapNodes')
              ->with( 'MapNodeLinks')
              ->find($id);

    if ( $oObj == null ) {
      throw new OlabObjectNotFoundException("Template", $id );
    }

    return $oObj;
  }

  /**
   * Get map object
   * @param mixed $id 
   * @throws OlabAccessDeniedException 
   * @return Maps
   */
  protected function get_map( $id, $acl = AccessControlBase::ACL_NO_ACCESS  ) {
    
    $oObj = Maps::active()
              ->with( 'MapNodes')
              ->with( 'MapNodeLinks')
              ->find($id);

    if ( $oObj == null ) {
      throw new OlabObjectNotFoundException("Map", $id );
    }

    // test for access, throws exception if not
    $this->test_access( $oObj, AccessControlBase::ACL_AUTHORABLE_ACCESS );

    return $oObj;
  }

  protected function get_deep_map( $id ) {
    
    $oObj = Maps::active()
              ->with( 'MapNodes')
              ->with( 'MapNodes.' . ScopedObjectManager::RELATION_NAME_COUNTERS )
              ->with( 'MapNodes.' . ScopedObjectManager::RELATION_NAME_CONSTANTS )
              ->with( 'MapNodeLinks')
              ->with( 'MapNodeJumps')
              ->with( ScopedObjectManager::RELATION_NAME_FILES )
              ->with( ScopedObjectManager::RELATION_NAME_COUNTERS )
              ->with( ScopedObjectManager::RELATION_NAME_CONSTANTS )
              ->with( ScopedObjectManager::RELATION_NAME_QUESTIONS )
              ->with( ScopedObjectManager::RELATION_NAME_THEMES )
              ->find($id);

    if ( $oObj == null ) {
      throw new OlabObjectNotFoundException("Map", $id );
    }

    // test access control context based on object type to evaluate.
    $oAccessControl = AccessControlBase::classFactory( $oObj );
    if ( !$oAccessControl->isWriteable( $id )) {
      throw new OlabAccessDeniedException("map = $id");
    }

    return $oObj;
  }

  protected function get_deep_node( Maps $oMap, int $node_id ) {

    $oNode = MapNodes::with( ScopedObjectManager::RELATION_NAME_COUNTERS )
              ->with( ScopedObjectManager::RELATION_NAME_CONSTANTS )
              ->with( ScopedObjectManager::RELATION_NAME_QUESTIONS )
              ->findOrFail( $node_id );

    if ( $oNode->map_id != $oMap->id ) {
      throw new Exception( "Node $node_id is not part of Map $oMap->id");
    }

    // test access control context based on object type to evaluate.
    $oAccessControl = AccessControlBase::classFactory( $oNode );
    if ( !$oAccessControl->isWriteable( $node_id )) {
      throw new OlabAccessDeniedException("node_id = $node_id");
    }

    return $oNode;
  }

  protected function create_template( string $template_name, Maps $oMap = null ) {
    
    $oTemplate = new MapTemplates();

    if ( $oMap == null ) {

      $oTemplate->is_template = 1;
    }
    else {
      $oTemplate = MapTemplates::createFrom( $oMap );
    }

    $oTemplate->name = $template_name;
    $oTemplate->save();

    Log::info( "created new template. id = " . $oTemplate->id . ", name = " . $template_name );

    // re-read the map so we get all the relations, etc., set.
    $oTemplate = $this->get_template( $oTemplate->id );
    return $oTemplate;
  }

  protected function create_map( int $user_id, MapTemplates $oTemplate = null ) {

    $oMap = new Maps();

    if ( $oTemplate != null ) {
      $oMap = Maps::createFrom( $oTemplate );
    }

    $oMap->author_id = $user_id;
    $oMap->is_template = 0;

    $oMap->save();

    Log::info( "created new map. id = " . $oMap->id . ", name = " . $oMap->name );

    // re-read the map so we get all the relations, etc., set.
    $oMap = $this->get_map( $oMap->id );
    return $oMap;
  }

  /**
   * Clone scoped objects from template into map
   * @param MapTemplates $oTemplate 
   * @param Maps $oDestinationMap 
   */
  protected function copy_scopeds_to_map( MapTemplates $oTemplate, Maps $oDestinationMap ) {

    $oScopedObject = new ScopedObjectManager( null, $oDestinationMap, null );

    $aoCounters = $oScopedObject->getCounter();
    $aoQuestions = $oScopedObject->getQuestion();

    foreach ($aoCounters as $aCounter ) {
    	$oNew = new Counters();

    }
    
  }

  /**
   * Clone map into template
   * @param Maps $oSourceMap 
   * @param MapTemplates $oDestinationTemplate 
   */
  protected function copy_map_to_template( Maps $oSourceMap, MapTemplates &$oDestinationTemplate ) {
    
    // DS map to hold old id to new node id translation
    $nodeIdMap = new Map([]);

    // get nodes from source. copy and assign to new map
    $aoNodes = $oSourceMap->MapNodes()->get();
    foreach ($aoNodes as $oNode ) {

      $oNewNode = MapNodes::createFrom( $oNode );
      unset( $oNewNode->id );
      $oNewNode->map_id = $oDestinationTemplate->id;
      $oNewNode->save();

      Log::debug( "copying node. id = " . $oNode->id . " -> " . $oNewNode->id );

      // save old node_id -> new node_id in a map because we will need to
      // use the map to remap node links to the new nodes
      $nodeIdMap->put( $oNode->id, $oNewNode->id );

    }

    // get and copy node links from template
    $aoLinks = $oSourceMap->MapNodeLinks()->get();
    foreach ($aoLinks as $oItem ) {

      $oNewNodeLink = MapNodeLinks::createFrom( $oItem );
      unset( $oNewNodeLink->id );

      // remap link from old id to cloned node ids
      $oNewNodeLink->node_id_1 = $nodeIdMap->get( $oNewNodeLink->node_id_1 );
      $oNewNodeLink->node_id_2 = $nodeIdMap->get( $oNewNodeLink->node_id_2 );
      $oNewNodeLink->map_id = $oDestinationTemplate->id;
      $oNewNodeLink->save();

      Log::debug( "copying node link. id = " . $oItem->id . " -> " . $oNewNodeLink->id );

    }

  }

  protected function get_link( $oMap, $id ) {
    
    $oObj = $oMap->MapNodeLinks()->find( $id );
    if ( $oObj == null ) {
      throw new OlabObjectNotFoundException("MapNodeLink", $id );
    }

    return $oObj;
  }

  protected function get_question( int $id ) {
    
    $oObj = Questions::find( $id );

    if ( $oObj == null ) {
      throw new OlabObjectNotFoundException("Question", $id );
    }

    return $oObj;
  }

  protected function get_question_deep( int $id ) {
    
    $oObj = Questions::with('QuestionResponses')->find( $id );

    if ( $oObj == null ) {
      throw new OlabObjectNotFoundException("Question", $id );
    }

    return $oObj;
  }

  /**
   * Get question response object from map
   * @param mixed $oQuestion 
   * @param mixed $id 
   * @throws Exception 
   * @return MapNodes
   */
  protected function get_question_response( $id ) {
    
    $oObj = QuestionResponses::find( $id );

    if ( $oObj == null ) {
      throw new OlabObjectNotFoundException("QuestionResponse", $id );
    }

    // test access control context based on object type to evaluate.
    //$oAccessControl = AccessControlBase::classFactory( $oObj );
    //if ( !$oAccessControl->isWriteable( $id )) {
    //  throw new OlabAccessDeniedException( $oObj, $id );
    //}

    return $oObj;
  }

  /**
   * Get node object from map
   * @param mixed $oMap 
   * @param mixed $id 
   * @throws Exception 
   * @return MapNodes
   */
  protected function get_node( $oMap, &$id, $acl = AccessControlBase::ACL_NO_ACCESS ) {
    
    $oObj = $oMap->MapNodes()->find( $id );

    if ( $oObj == null ) {
      throw new OlabObjectNotFoundException("MapNode", $id );
    }

    // test for access on owning map, throws exception if not
    $this->test_access( $oMap, AccessControlBase::ACL_AUTHORABLE_ACCESS );

    return $oObj;
  }

}