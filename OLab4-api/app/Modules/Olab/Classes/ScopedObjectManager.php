<?php
/**
 * OpenLabyrinth [ http://www.openlabyrinth.ca ]
 *
 * OpenLabyrinth is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenLabyrinth is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenLabyrinth.  If not, see <http://www.gnu.org/licenses/>.
 *
 * A class to manage scoped objects from the database
 *
 * @author Organisation: Cumming School of Medicine, University of Calgary
 * @author Developer: Corey Wirun (corey@cardinalcreek.ca)
 * @copyright Copyright 2017 University of Calgary. All Rights Reserved.
 */

namespace Entrada\Modules\Olab\Classes;

use Auth;
use \Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Entrada\Modules\Olab\Models\MapNodeTypes;
use Entrada\Modules\Olab\Models\QuestionTypes;
use Entrada\Modules\Olab\Models\Constants;
use Entrada\Modules\Olab\Models\Counters;
use Entrada\Modules\Olab\Models\UserState;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\MapNodes;
use Entrada\Modules\Olab\Models\Themes;
use Entrada\Modules\Olab\Models\Servers;
use Entrada\Http\Controllers\Controller;
use Entrada\Modules\Olab\Classes\Autoload\MimeTypes\OlabMimeTypeBase;
use Entrada\Modules\Olab\Classes\CustomAssetManager;
use Entrada\Modules\Olab\Classes\HostSystemApi;

/**
 * Utility class that handles the management of scoped Olab objects
 * (questions, files, constants, etc).
 */
class ScopedObjectManager {

  const SCOPE_LEVEL_SERVER = "server";
  const SCOPE_LEVEL_MAP = "map";
  const SCOPE_LEVEL_NODE = "node";
  const SCOPE_LEVEL_COURSE = "course";
  const SCOPE_LEVEL_GLOBAL = "global";

  const SCOPE_TYPE_CONSTANTS = 'constants';
  const SCOPE_TYPE_SCRIPTS = 'scripts';
  const SCOPE_TYPE_FILES = 'files';
  const SCOPE_TYPE_QUESTIONS = 'questions';
  const SCOPE_TYPE_COUNTERS = 'counters';
  const SCOPE_TYPE_THEMES = 'themes';

  const RELATION_NAME_CONSTANTS = 'Constants';
  const RELATION_NAME_SCRIPTS = 'Scripts';
  const RELATION_NAME_THEMES = 'Themes';
  const RELATION_NAME_FILES = 'Files';
  const RELATION_NAME_QUESTIONS = 'Questions';
  const RELATION_NAME_AVATARS = 'Avatars';
  const RELATION_NAME_COUNTERS = 'Counters';

  // TODO: coalesce these duplicates against OLabNodeController
  const PAYLOAD_INDEXNAME_MAP = "map";
  const PAYLOAD_INDEXNAME_SERVER = "server";
  const PAYLOAD_INDEXNAME_AUTOLOADER = "scripts";
  const PAYLOAD_INDEXNAME_NODE = "node";
  const PAYLOAD_INDEXNAME_COURSE = "course";
  const PAYLOAD_INDEXNAME_GLOBAL = "global";
  const PAYLOAD_NODE_INFO = "info";
  const PAYLOAD_INDEXNAME_HEADER = "header";
  const PAYLOAD_INDEXNAME_FOOTER = "footer";
  const PAYLOAD_INDEXNAME_STATE = "state";

  //const RESERVED_CONST_TIME = "SystemTime";
  //const RESERVED_CONST_MAPID = "MapId";
  //const RESERVED_CONST_NODEID = "NodeId";
  //const RESERVED_CONST_MAPNAME = "MapName";
  //const RESERVED_CONST_NODENAME = "NodeName";

  private $iMapId;
  private $iNodeId;
  private $iServerId;

  private $iIndex = -1;

  private $oNode;
  private $oMap;
  private $oServer;

  protected $aaGlobalObjects;
  protected $aaServerObjects;
  protected $aaCourseObjects;
  protected $aaMapObjects;
  protected $aaNodeObjects;

  protected $aaStaticObjects;

  /**
   * System object wrapper (questions, files, etc)
   * @param Servers $oServer
   * @param Maps $oMap
   * @param MapNodes $oNode
   */
  public function __construct( $oServer = null, $oMap = null, $oNode = null ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

    try {

      // if args supplied, then save the various parameters

      if ( $oServer != null ) {

        $this->oServer = $oServer;
        $this->iServerId = $oServer->id;

      }

      if ( $oMap != null ) {

        $this->oMap = $oMap;
        $this->iMapId = $oMap->id;

      }

      if ( $oNode != null ) {

        $this->oNode = $oNode;
        $this->iNodeId = $oNode->id;
      }

      // initialize the object arrays
      self::InitializeLevel( $this->aaGlobalObjects );
      self::InitializeLevel( $this->aaServerObjects );
      self::InitializeLevel( $this->aaCourseObjects );
      self::InitializeLevel( $this->aaMapObjects );
      self::InitializeLevel( $this->aaNodeObjects );

      $this->Initialize();
    }
    catch (Exception $exception) {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception );
    }

  }

  private static function InitializeLevel( &$target ) {
    
    $target[self::SCOPE_TYPE_CONSTANTS] = array();
    $target[self::SCOPE_TYPE_SCRIPTS] = array();
    $target[self::SCOPE_TYPE_COUNTERS] = array();
    $target[self::SCOPE_TYPE_FILES] = array();
    $target[self::SCOPE_TYPE_QUESTIONS] = array();
    $target[self::SCOPE_TYPE_THEMES] = array();
  }

  /**
   * Updates counter values in scoped objects from user state
   * @param UserState $oState 
   */
  public function UpdateFromState( UserState $oState ) {

    $aaStateData = json_decode( $oState->state_data, true );

    // update server-level counters
    foreach ($aaStateData["server"] as $item) {
      $counter = &$this->getCounter( $item["id"] );
      if ( $counter != null ) {
        $counter["value"] = $item["value"];
      }
    }

    // update map-level counters
    foreach ($aaStateData["map"] as $item) {
      $counter = &$this->getCounter( $item["id"] );
      if ( $counter != null ) {
        $counter["value"] = $item["value"];
      }
    }

    // update node-level counters
    foreach ($aaStateData["node"] as $item) {
      $counter = &$this->getCounter( $item["id"] );
      if ( $counter != null ) {
        $counter["value"] = $item["value"];
      }
    }

  }

  /**
   * Add reserved-word map-level scoped object values
   */
  private function setMapLevelReservedValues() {


    foreach ( $this->aaMapObjects[self::SCOPE_TYPE_CONSTANTS] as &$aItem ) {
      
      if ( $aItem['name'] == Constants::RESERVED_CONST_MAPID ) {
        if ( $this->oMap != null ) {
          $aItem['value'] = $this->oMap->id;
        }
      }

      else if ( $aItem['name'] == Constants::RESERVED_CONST_MAPNAME ) {
        if ( $this->oMap != null ) {
          $aItem['value'] = $this->oMap->name;
        }
      }

    }

  }

    /**
   * Add reserved-word node-level scoped object values
   */
  private function setNodeLevelReservedValues() {


    foreach ( $this->aaNodeObjects[self::SCOPE_TYPE_CONSTANTS] as &$aItem ) {
      
      if ( $aItem['name'] == Constants::RESERVED_CONST_NODEID ) {
        if ( $this->oNode != null ) {
          $aItem['value'] = $this->oNode->id;
        }
      }

      else if ( $aItem['name'] == Constants::RESERVED_CONST_NODENAME ) {
        if ( $this->oNode != null ) {
          $aItem['value'] = $this->oNode->title;
        }
      }

    }

  }

  /**
   * Read in and attach any embedded resources from files
   */
  private function attachEmbeddedResources() {

    // loop through all the file resources
    foreach ($this->aaStaticObjects[self::SCOPE_TYPE_FILES] as &$aFile ) {

      if ( $aFile["is_embedded"] === 1 ) {
        // build base64 encoded content mime string
        $aFile["encoded_content"] = OlabMimeTypeBase::GetEncodedContentString( $aFile );
      }
    }

  }

  /**
   * Get a constant for an index
   * @param $id mixed Id of object to find (null = all)
   * @param $flatten boolean flatten results into single array or by level
   * @return \Entrada\Modules\Olab\Models\Counters
   */
  public function &getConstant( $id = null, $flatten = true ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($id)" );

    try
    {
      // if no id, then we are returning all
      if ( $id == null ) {

        $aaItems = array();

        if ( $flatten == true ) {
          $aaItems = $this->aaServerObjects[self::SCOPE_TYPE_CONSTANTS];
          $aaItems = array_merge( $aaItems,
                                $this->aaMapObjects[self::SCOPE_TYPE_CONSTANTS]);
          $aaItems = array_merge( $aaItems,
                                $this->aaNodeObjects[self::SCOPE_TYPE_CONSTANTS]);
        }
        else {
          $aaItems[self::SCOPE_LEVEL_SERVER] = $this->aaServerObjects[self::SCOPE_TYPE_CONSTANTS];
          $aaItems[self::SCOPE_LEVEL_MAP] = $this->aaMapObjects[self::SCOPE_TYPE_CONSTANTS];
          $aaItems[self::SCOPE_LEVEL_NODE] = $this->aaNodeObjects[self::SCOPE_TYPE_CONSTANTS];
        }

        return $aaItems;
      }

      $aResult = array();

      if ( $aResult == null ) {
        foreach( $this->aaServerObjects[self::SCOPE_TYPE_CONSTANTS] as &$aItem ) {
          if ( ( $aItem['id'] == $id ) || ( $aItem['name'] == $id ) ){
            return $aItem;
          }
        }
      }

      if ( $aResult == null ) {
        foreach( $this->aaMapObjects[self::SCOPE_TYPE_CONSTANTS] as &$aItem ) {
          if ( ( $aItem['id'] == $id ) || ( $aItem['name'] == $id ) ){
            return $aItem;
          }
        }
      }

      if ( $aResult == null ) {
        foreach( $this->aaNodeObjects[self::SCOPE_TYPE_CONSTANTS] as &$aItem ) {
          if ( ( $aItem['id'] == $id ) || ( $aItem['name'] == $id ) ){
            return $aItem;
          }
        }
      }

      if ( $aResult == null ) {
        Log::error( $tracer->sBlockName . ": failed to find counter id = " . $id );
      }

      return $aResult;
    }
    catch (Exception $exception)
    {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
    }

    return null;

  }

  /**
   * Get a counter for an index
   * @param $id mixed Id of object to find (null = all)
   * @param $flatten boolean flatten results into single array or by level
   * @return \Entrada\Modules\Olab\Models\Counters
   */
  public function &getCounter( $id = null, $flatten = true ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($id)" );

    try
    {
      // if no id, then we are returning all
      if ( $id == null ) {

        $aaItems = array();

        if ( $flatten == true ) {
          $aaItems = $this->aaServerObjects[self::SCOPE_TYPE_COUNTERS];
          $aaItems = array_merge( $aaItems,
                                $this->aaMapObjects[self::SCOPE_TYPE_COUNTERS]);
          $aaItems = array_merge( $aaItems,
                                $this->aaNodeObjects[self::SCOPE_TYPE_COUNTERS]);
        }
        else {
          $aaItems[self::SCOPE_LEVEL_SERVER] = $this->aaServerObjects[self::SCOPE_TYPE_COUNTERS];
          $aaItems[self::SCOPE_LEVEL_MAP] = $this->aaMapObjects[self::SCOPE_TYPE_COUNTERS];
          $aaItems[self::SCOPE_LEVEL_NODE] = $this->aaNodeObjects[self::SCOPE_TYPE_COUNTERS];
        }

        self::dumpCounterArray( $this->aaServerObjects[self::SCOPE_TYPE_COUNTERS] );
        self::dumpCounterArray( $this->aaMapObjects[self::SCOPE_TYPE_COUNTERS] );
        self::dumpCounterArray( $this->aaNodeObjects[self::SCOPE_TYPE_COUNTERS] );

        return $aaItems;
      }

      $aResult = array();

      if ( $aResult == null ) {
        foreach( $this->aaServerObjects[self::SCOPE_TYPE_COUNTERS] as &$aItem ) {
          if ( ( $aItem['id'] == $id ) || ( $aItem['name'] == $id ) ){
            return $aItem;
          }
        }
      }

      if ( $aResult == null ) {
        foreach( $this->aaMapObjects[self::SCOPE_TYPE_COUNTERS] as &$aItem ) {
          if ( ( $aItem['id'] == $id ) || ( $aItem['name'] == $id ) ){
            return $aItem;
          }
        }
      }

      if ( $aResult == null ) {
        foreach( $this->aaNodeObjects[self::SCOPE_TYPE_COUNTERS] as &$aItem ) {
          if ( ( $aItem['id'] == $id ) || ( $aItem['name'] == $id ) ){
            return $aItem;
          }
        }
      }

      if ( $aResult == null ) {
        Log::error( $tracer->sBlockName . ": failed to find counter id = " . $id );
      }
      else {
        Log::debug( Counters::toString( $aResult ));
      }

      return $aResult;
    }
    catch (Exception $exception)
    {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
    }

    return null;

  }

  /**
   * Get a file for an index
   * @param $id int Id of object to find (null = all)
   * @param $flatten boolean flatten results into single array or by level
   * @return \Entrada\Modules\Olab\Models\Files as an array
   */
  public function &getFile( $id = null, $flatten = true ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($id)" );

    try
    {
      // if no id, then we are returning all
      if ( $id == null ) {

        $aaItems = array();

        if ( $flatten == true ) {
          $aaItems = $this->aaServerObjects[self::SCOPE_TYPE_FILES];
          $aaItems = array_merge( $aaItems,
                                $this->aaMapObjects[self::SCOPE_TYPE_FILES]);
          $aaItems = array_merge( $aaItems,
                                $this->aaNodeObjects[self::SCOPE_TYPE_FILES]);
        }
        else {
          $aaItems[self::SCOPE_LEVEL_SERVER] = $this->aaServerObjects[self::SCOPE_TYPE_FILES];
          $aaItems[self::SCOPE_LEVEL_MAP] = $this->aaMapObjects[self::SCOPE_TYPE_FILES];
          $aaItems[self::SCOPE_LEVEL_NODE] = $this->aaNodeObjects[self::SCOPE_TYPE_FILES];
        }

        return $aaItems;
      }

      $aResult = array();

      if ( $aResult == null ) {
        foreach( $this->aaServerObjects[self::SCOPE_TYPE_FILES] as &$aItem ) {
          if ( ( $aItem['id'] == $id ) || ( $aItem['name'] == $id ) ) {
            return $aItem;
          }
        }
      }

      if ( $aResult == null ) {
        foreach( $this->aaMapObjects[self::SCOPE_TYPE_FILES] as &$aItem ) {
          if ( ( $aItem['id'] == $id ) || ( $aItem['name'] == $id ) ){
            return $aItem;
          }
        }
      }

      if ( $aResult == null ) {
        foreach( $this->aaNodeObjects[self::SCOPE_TYPE_FILES] as &$aItem ) {
          if ( ( $aItem['id'] == $id ) || ( $aItem['name'] == $id ) ){
            return $aItem;
          }
        }
      }

      if ( $aResult == null ) {
        Log::error( $tracer->sBlockName . ": failed to find resource with id = " . $id );
      }

      return $aResult;
    }
    catch (Exception $exception)
    {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
    }

    return null;
  }

  /**
   * Get a question for an index
   * @param $id int Id of object to find (null = all)
   * @param $flatten boolean flatten results into single array or by level
   * @return \Entrada\Modules\Olab\Models\Questions
   */
  public function &getQuestion( $id = null, $flatten = true ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($id)" );

    try
    {
      // if no id, then we are returning all
      if ( $id == null ) {

        $aaItems = array();

        if ( $flatten == true ) {
          $aaItems = $this->aaServerObjects[self::SCOPE_TYPE_QUESTIONS];
          $aaItems = array_merge( $aaItems,
                                $this->aaMapObjects[self::SCOPE_TYPE_QUESTIONS]);
          $aaItems = array_merge( $aaItems,
                                $this->aaNodeObjects[self::SCOPE_TYPE_QUESTIONS]);
        }
        else {
          $aaItems[self::SCOPE_LEVEL_SERVER] = $this->aaServerObjects[self::SCOPE_TYPE_QUESTIONS];
          $aaItems[self::SCOPE_LEVEL_MAP] = $this->aaMapObjects[self::SCOPE_TYPE_QUESTIONS];
          $aaItems[self::SCOPE_LEVEL_NODE] = $this->aaNodeObjects[self::SCOPE_TYPE_QUESTIONS];
        }

        return $aaItems;
      }

      $aResult = array();

      if ( $aResult == null ) {
        foreach( $this->aaServerObjects[self::SCOPE_TYPE_QUESTIONS] as &$aItem ) {
          if ( ( $aItem['id'] == $id ) || ( $aItem['name'] == $id ) ){
            return $aItem;
          }
        }
      }

      if ( $aResult == null ) {
        foreach( $this->aaMapObjects[self::SCOPE_TYPE_QUESTIONS] as &$aItem ) {
          if ( ( $aItem['id'] == $id ) || ( $aItem['name'] == $id ) ){
            return $aItem;
          }
        }
      }

      if ( $aResult == null ) {
        foreach( $this->aaNodeObjects[self::SCOPE_TYPE_QUESTIONS] as &$aItem ) {
          if ( ( $aItem['id'] == $id ) || ( $aItem['name'] == $id ) ){
            return $aItem;
          }
        }
      }

      if ( $aResult == null ) {
        Log::error( $tracer->sBlockName . ": failed to find question id = " . $id );
      }

      return $aResult;
    }
    catch (Exception $exception)
    {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
    }

    return array();
  }

  public function getThemeList() {
    
    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );
    $aTheme = array();

    try
    {

      $aaItems = array();

      // add items in ascending order to the array
      $aaItems = array_merge( $aaItems,
                            $this->aaNodeObjects[self::SCOPE_TYPE_THEMES]);
      $aaItems = array_merge( $aaItems,
                            $this->aaMapObjects[self::SCOPE_TYPE_THEMES]);
      $aaItems = array_merge( $aaItems,
                            $this->aaCourseObjects[self::SCOPE_TYPE_THEMES]);
      $aaItems = array_merge( $aaItems,
                            $this->aaServerObjects[self::SCOPE_TYPE_THEMES]);
      $aaItems = array_merge( $aaItems,
                            $this->aaGlobalObjects[self::SCOPE_TYPE_THEMES]);

      if ( sizeof( $aaItems ) == 0 )
        return $aTheme;

      // return a picklist-friendly array of objects
      return Themes::getPickList( $aaItems );

    }
    catch (Exception $exception)
    {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
    }

    return array();

  }

  /**
   * Get a theme from scoped objects
   * @return array
   */
  public function &getTheme() {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );
    $aTheme = array();

    try
    {

      $aaItems = array();

      // add items in ascending order to the array
      $aaItems = array_merge( $aaItems,
                            $this->aaNodeObjects[self::SCOPE_TYPE_THEMES]);
      $aaItems = array_merge( $aaItems,
                            $this->aaMapObjects[self::SCOPE_TYPE_THEMES]);
      $aaItems = array_merge( $aaItems,
                            $this->aaCourseObjects[self::SCOPE_TYPE_THEMES]);
      $aaItems = array_merge( $aaItems,
                            $this->aaServerObjects[self::SCOPE_TYPE_THEMES]);
      $aaItems = array_merge( $aaItems,
                            $this->aaGlobalObjects[self::SCOPE_TYPE_THEMES]);

      if ( sizeof( $aaItems ) == 0 )
        return null;

      // seed the returning record
      $aTheme = $aaItems[0];

      // traverse from low to high scope and attempt to 
      // fill in all the theme text areas if they are spread 
      // over multiple scope levels
      for ($i = 0; $i < sizeof($aaItems); $i++)
      {
      	if ( strlen( $aaItems[$i]["header_text"] ) != 0  ) {
          $aTheme["header_text"] = $aaItems[$i]["header_text"];
        }

      	if ( strlen( $aaItems[$i]["left_text"] ) != 0  ) {
          $aTheme["left_text"] = $aaItems[$i]["left_text"];
        }

        if ( strlen( $aaItems[$i]["right_text"] ) != 0  ) {
          $aTheme["right_text"] = $aaItems[$i]["right_text"];
        }

      	if ( strlen( $aaItems[$i]["footer_text"] ) != 0  ) {
          $aTheme["footer_text"] = $aaItems[$i]["footer_text"];
        }

      	if ( ( strlen( $aTheme["header_text"] ) > 0  ) &&
      	     ( strlen( $aTheme["left_text"] ) > 0  ) &&
      	     ( strlen( $aTheme["right_text"] ) > 0  ) &&
      	     ( strlen( $aTheme["footer_text"] ) > 0  ) )
          break;
      }
      
      return $aTheme;

    }
    catch (Exception $exception)
    {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
    }

    return $aTheme;
  }

  /**
   * Load the scoped object handler from a user state object
   * @param UserState|array $userState
   */
  public function loadFromUserState( $userState ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

    try
    {
      if ( gettype( $userState ) == "array") {
        $aaStateData = json_decode( $userState["state_data"], true );
      }
      else {
        // convert object to array
        $aaStateData = json_decode( $userState->state_data, true );
      }
      
      if ( isset( $aaStateData[self::SCOPE_LEVEL_SERVER] ) ) {
        foreach ($aaStateData[self::SCOPE_LEVEL_SERVER] as $aCounter ) {
          array_push( $this->aaServerObjects[self::SCOPE_TYPE_COUNTERS], $aCounter );
        }
      }

      if ( isset( $aaStateData[self::SCOPE_LEVEL_MAP] ) ) {
        foreach ($aaStateData[self::SCOPE_LEVEL_MAP] as $aCounter ) {
          array_push( $this->aaMapObjects[self::SCOPE_TYPE_COUNTERS], $aCounter );
        }
      }

      if ( isset( $aaStateData[self::SCOPE_LEVEL_NODE] ) ) {
        foreach ($aaStateData[self::SCOPE_LEVEL_NODE] as $aCounter ) {
          array_push( $this->aaNodeObjects[self::SCOPE_TYPE_COUNTERS], $aCounter );
        }
      }
    }
    catch (Exception $exception)
    {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception );
    }

  }

  public function extractCounters( &$aaPayload ) {
    
    // add node scoped object to counter payload and remove from node object
    $aaPayload["counters"][self::PAYLOAD_INDEXNAME_NODE] = 
      $this->aaNodeObjects[ScopedObjectManager::SCOPE_TYPE_COUNTERS];

    // add map scoped object to counter payload and remove from map object
    $aaPayload["counters"][self::PAYLOAD_INDEXNAME_MAP] = 
      $this->aaMapObjects[ScopedObjectManager::SCOPE_TYPE_COUNTERS];

    // add server scoped object to counter payload and remove from server object
    $aaPayload["counters"][self::PAYLOAD_INDEXNAME_SERVER] = 
      $this->aaServerObjects[ScopedObjectManager::SCOPE_TYPE_COUNTERS];

  }

  private function extractScopedObjects( $aaSource, &$aDestination ) {

    $aDestination[self::SCOPE_TYPE_CONSTANTS] = $aaSource[self::SCOPE_TYPE_CONSTANTS];
    //$aDestination[self::SCOPE_TYPE_SCRIPTS] = $aaSource[self::SCOPE_TYPE_SCRIPTS];
    $aDestination[self::SCOPE_TYPE_COUNTERS] = $aaSource[self::SCOPE_TYPE_COUNTERS];
    $aDestination[self::SCOPE_TYPE_FILES] = $aaSource[self::SCOPE_TYPE_FILES];
    $aDestination[self::SCOPE_TYPE_QUESTIONS] = $aaSource[self::SCOPE_TYPE_QUESTIONS];

  }

  public function extractNodeScopedObjects( &$aDestination ) {    
    $this->extractScopedObjects( $this->aaNodeObjects, $aDestination );
  }

  public function extractMapScopedObjects( &$aDestination ) {
    $this->extractScopedObjects( $this->aaMapObjects, $aDestination );
  }

  public function extractServerScopedObjects( &$aDestination ) {
    $this->extractScopedObjects( $this->aaServerObjects, $aDestination );   
  }

  public function extractGlobalScopedObjects( &$aDestination ) {
    $this->extractScopedObjects( $this->aaGlobalObjects, $aDestination );   
  }

  public function extractCourseScopedObjects( &$aDestination ) {
    $this->extractScopedObjects( $this->aaCourseObjects, $aDestination );   
  }

  private function loadScopedObjects( $aSource, &$aDestination ) {
    
    if ( isset( $aSource[self::SCOPE_TYPE_CONSTANTS] ) ) {
      foreach ($aSource[self::SCOPE_TYPE_CONSTANTS] as $item) {

        // adjust counter to remap <<BASEURL>> to the website root url
        $item['value'] = str_replace("<<BASEURL>>", HostSystemApi::getRootUrl(), $item['value'] );
      	$aDestination[self::SCOPE_TYPE_CONSTANTS][] = $item;
      }
     
    }

    if ( isset( $aSource[self::SCOPE_TYPE_SCRIPTS] ) ) {
      foreach ($aSource[self::SCOPE_TYPE_SCRIPTS] as $item) {
      	$aDestination[self::SCOPE_TYPE_SCRIPTS][] = $item;
      }    
    }

    if ( isset( $aSource[self::SCOPE_TYPE_QUESTIONS] ) ) {
      foreach ($aSource[self::SCOPE_TYPE_QUESTIONS] as $item) {
      	$aDestination[self::SCOPE_TYPE_QUESTIONS][] = $item;
      }      
    }

    if ( isset( $aSource[self::SCOPE_TYPE_FILES] ) ) {
      foreach ($aSource[self::SCOPE_TYPE_FILES] as $item) {
      	$aDestination[self::SCOPE_TYPE_FILES][] = $item;
      }      
    }

    if ( isset( $aSource[self::SCOPE_TYPE_THEMES] ) ) {
      foreach ($aSource[self::SCOPE_TYPE_THEMES] as $item) {
      	$aDestination[self::SCOPE_TYPE_THEMES][] = $item;
      }
     
    }

    if ( isset( $aSource[self::SCOPE_TYPE_COUNTERS] ) ) {
      foreach ($aSource[self::SCOPE_TYPE_COUNTERS] as $item) {
        // add additional check if value is null, initialize it with 'start_value'
        if ( $item['value'] == null ) {
          $item['value'] = $item['startValue']; 
        }
      	$aDestination[self::SCOPE_TYPE_COUNTERS][] = $item;
      }      
    }

    foreach ($aDestination[self::SCOPE_TYPE_SCRIPTS] as $item) {

      $handle = '';
      if ( strlen( $item['name'] > 0 )) {
        $handle .= $item['name'];
      }
      else {
        $handle .= $item['id'];            
      }

      if ( $item['is_raw'] == 1 ) {
        $handle = "raw" . $handle;
        CustomAssetManager::addRawScript( $handle, $item['source'] );
      }
      else {
        CustomAssetManager::addScript( $handle, $item['source'] );
      }
    }

  }

  /**
   * Get map-scoped objects
   */
  private function loadMapLevelObjects( $oMap, $only_system = false  ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

    if ( $oMap == null ) {
      return;
    }

    $aMap = $oMap->toArray();

    $this->loadScopedObjects( $aMap, $this->aaMapObjects );

    // add server-level reserved objects
    $oObj = new Constants();
    $oObj->id = $this->iIndex--;
    $oObj->name = Constants::RESERVED_CONST_MAPID;
    $oObj->description = "Current map id";
    $oObj->is_system = 1;
    $oObj->imageable_type = Maps::IMAGEABLE_TYPE;
    $oObj->imageable_id = $this->oMap->id;
    $oObj->value = $this->oMap->id;

    $this->aaMapObjects[self::SCOPE_TYPE_CONSTANTS][] = $oObj->toArray();

    $oObj = new Constants();
    $oObj->id = $this->iIndex--;
    $oObj->name = Constants::RESERVED_CONST_MAPNAME;
    $oObj->description = "Current map name";
    $oObj->is_system = 1;
    $oObj->imageable_type = Maps::IMAGEABLE_TYPE;
    $oObj->imageable_id = $this->oMap->id;
    $oObj->value = $this->oMap->name;

    $this->aaMapObjects[self::SCOPE_TYPE_CONSTANTS][] = $oObj->toArray();

    self::dumpCounterArray( $this->aaMapObjects[self::SCOPE_TYPE_COUNTERS] );

  }

  /**
   * Get node-scoped objects
   */
  private function loadNodeLevelObjects( $oNode, $only_system = false  ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

    if ( $oNode == null ) {
      return;
    }

    // attach embedded file resources
    // loop through all the file resources
    foreach ($oNode->Files as &$oFile ) {

      if ( $oFile->is_embedded === 1 ) {
        // build base64 encoded content mime string
        $oFile->encoded_content = OlabMimeTypeBase::GetEncodedContentString( $oFile->toArray() );
      }
    }

    $aNode = $oNode->toArray(true);

    $this->loadScopedObjects( $aNode, $this->aaNodeObjects );

    // add server-level reserved objects
    $oObj = new Constants();
    $oObj->id = $this->iIndex--;
    $oObj->name = Constants::RESERVED_CONST_NODEID;
    $oObj->description = "Current node id";
    $oObj->is_system = 1;
    $oObj->imageable_type = MapNodes::IMAGEABLE_TYPE;
    $oObj->imageable_id = $this->oNode->id;
    $oObj->value = $this->oNode->id;

    $this->aaNodeObjects[self::SCOPE_TYPE_CONSTANTS][] = $oObj->toArray();

    $oObj = new Constants();
    $oObj->id = $this->iIndex--;
    $oObj->name = Constants::RESERVED_CONST_NODENAME;
    $oObj->description = "Current node name";
    $oObj->is_system = 1;
    $oObj->imageable_type = MapNodes::IMAGEABLE_TYPE;
    $oObj->imageable_id = $this->oNode->id;
    $oObj->value = $this->oNode->title;

    $this->aaNodeObjects[self::SCOPE_TYPE_CONSTANTS][] = $oObj->toArray();

    $this->loadScopedObjects( $aNode, $this->aaNodeObjects );

    self::dumpCounterArray( $this->aaNodeObjects[self::SCOPE_TYPE_COUNTERS] );

  }

  /**
   * Get server-scoped objects
   */
  private function loadServerLevelObjects( $oServer, $only_system = false ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

    if ( $oServer == null ) {
      return;
    }

    $aServer = $oServer->toArray();

    // add server-level reserved objects
    $oObj = new Constants();
    $oObj->id = $this->iIndex--;
    $oObj->name = Constants::RESERVED_CONST_TIME;
    $oObj->description = "System time";
    $oObj->is_system = 1;
    $oObj->imageable_type = Servers::IMAGEABLE_TYPE;
    $oObj->imageable_id = Servers::DEFAULT_LOCAL_ID;
    $oObj->value = date(DATE_RFC2822);        
    $aServer[self::SCOPE_TYPE_CONSTANTS][] = $oObj->toArray();

    $this->loadScopedObjects( $aServer, $this->aaServerObjects );

    self::dumpCounterArray( $this->aaServerObjects[self::SCOPE_TYPE_COUNTERS] );
  }

  /**
   * Loads the objects
   */
  private function Initialize() {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

    // extract server-scoped objects
    $this->loadServerLevelObjects( $this->oServer );

    // extract map-scoped objects
    $this->loadMapLevelObjects( $this->oMap );

    // extract node-scoped objects
    $this->loadNodeLevelObjects( $this->oNode );

  }

  public static function dumpCounterArray( $aaCounters ) {
    
    try
    {
      foreach ($aaCounters as $aCounter ) {
        Log::debug( "  " . Counters::toString( $aCounter ) );   	
      }
    	
    }
    catch (Exception $exception)
    {
      // eat all exceptions
      Log::error($exception);
    }
    
  }

}