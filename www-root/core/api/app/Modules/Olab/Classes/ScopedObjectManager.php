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

namespace App\Modules\Olab\Classes;

use Auth;
use \Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Modules\Olab\Models\MapNodeTypes;
use App\Modules\Olab\Models\QuestionTypes;
use App\Modules\Olab\Models\Constants;
use App\Modules\Olab\Models\UserState;
use App\Modules\Olab\Models\Maps;
use App\Modules\Olab\Models\MapNodes;
use App\Modules\Olab\Models\Servers;
use App\Http\Controllers\Controller;
use App\Modules\Olab\Classes\Autoload\MimeTypes\OlabMimeTypeBase;

/**
 * Utility class that handles the management of scoped Olab objects
 * (questions, files, constants, etc).
 */
class ScopedObjectManager {

    const SCOPE_LEVEL_SERVER = "server";
    const SCOPE_LEVEL_MAP = "map";
    const SCOPE_LEVEL_NODE = "node";

    const SCOPE_TYPE_CONSTANTS = 'constants';
    const SCOPE_TYPE_FILES = 'files';
    const SCOPE_TYPE_QUESTIONS = 'questions';
    const SCOPE_TYPE_COUNTERS = 'counters';

    const RELATION_NAME_CONSTANTS = 'Constants';
    const RELATION_NAME_FILES = 'Files';
    const RELATION_NAME_QUESTIONS = 'Questions';
    const RELATION_NAME_COUNTERS = 'Counters';

    const RESERVED_CONST_TIME = "SystemTime";
    const RESERVED_CONST_MAPID = "MapId";
    const RESERVED_CONST_NODEID = "NodeId";
    const RESERVED_CONST_MAPNAME = "MapName";
    const RESERVED_CONST_NODENAME = "NodeName";

    private $iMapId;
    private $iNodeId;
    private $iServerId;

    private $oNode;
    private $oMap;
    private $oServer;

    protected $aaServerObjects;
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

            // if all args supplied, then save the various parameters
            if ( $oNode != null ) {

                $this->oServer = $oServer;
                $this->iServerId = $oServer->id;

                $this->oMap = $oMap;
                $this->iMapId = $oMap->id;

                $this->oNode = $oNode;
                $this->iNodeId = $oNode->id;
            }

            // initialize the object arrays
            $this->aaServerObjects[self::SCOPE_TYPE_CONSTANTS] = array();
            $this->aaServerObjects[self::SCOPE_TYPE_COUNTERS] = array();
            $this->aaServerObjects[self::SCOPE_TYPE_FILES] = array();
            $this->aaServerObjects[self::SCOPE_TYPE_QUESTIONS] = array();

            $this->aaMapObjects[self::SCOPE_TYPE_CONSTANTS] = array();
            $this->aaMapObjects[self::SCOPE_TYPE_COUNTERS] = array();
            $this->aaMapObjects[self::SCOPE_TYPE_FILES] = array();
            $this->aaMapObjects[self::SCOPE_TYPE_QUESTIONS] = array();

            $this->aaNodeObjects[self::SCOPE_TYPE_CONSTANTS] = array();
            $this->aaNodeObjects[self::SCOPE_TYPE_COUNTERS] = array();
            $this->aaNodeObjects[self::SCOPE_TYPE_FILES] = array();
            $this->aaNodeObjects[self::SCOPE_TYPE_QUESTIONS] = array();


            // if all args supplied, then load the scoped objects from the database objects
            if ( $oNode != null ) {
                $this->Initialize();
            }
        }
        catch (Exception $exception) {
            OlabExceptionHandler::LogException( $tracer->sBlockName, $exception );
        }

    }

    /**
     * Add reserved-word map-level constants
     * @param mixed $oMap
     */
    private function addReservedMapObjects() {

        $oConst = new Constants();
        $oConst->id = -1;
        $oConst->name = self::RESERVED_CONST_MAPID;
        $oConst->value = $this->oMap->id;

        $this->oMap->Constants->prepend( $oConst );

        $oConst = new Constants();
        $oConst->id = -2;
        $oConst->name = self::RESERVED_CONST_MAPNAME;
        $oConst->value = $this->oMap->name;

        $this->oMap->Constants->prepend( $oConst );
    }

    /**
     * Add reserved-word node-level constants
     * @param mixed $oNode
     */
    private function addReservedNodeObjects() {

        $oConst = new Constants();
        $oConst->id = -1;
        $oConst->name = self::RESERVED_CONST_NODEID;
        $oConst->value = $this->oNode->id;

        $this->oNode->Constants->prepend( $oConst );

        $oConst = new Constants();
        $oConst->id = -2;
        $oConst->name = self::RESERVED_CONST_NODENAME;
        $oConst->value = $this->oNode->title;

        $this->oNode->Constants->prepend( $oConst );
    }

    /**
     * Add reserved-word server-level constants
     * @param mixed $oMap
     */
    private function addReservedServerObjects() {

        $oConst = new Constants();
        $oConst->id = -1;
        $oConst->name = self::RESERVED_CONST_TIME;
        $oConst->value = date(DATE_RFC2822);

        $this->oServer->Constants->prepend( $oConst );

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
     * Get a counter for an index
     * @param $id mixed Id of object to find (null = all)
     * @param $flatten boolean flatten results into signal array or by level
     * @return \App\Modules\Olab\Models\Counters
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

                return $aaItems;
            }

            $aResult = array();

            if ( $aResult == null ) {
                foreach( $this->aaServerObjects[self::SCOPE_TYPE_COUNTERS] as &$aItem ) {
                    if ( $aItem['id'] == $id ) {
                        return $aItem;
                    }
                }
            }

            if ( $aResult == null ) {
                foreach( $this->aaMapObjects[self::SCOPE_TYPE_COUNTERS] as &$aItem ) {
                    if ( $aItem['id'] == $id ) {
                        return $aItem;
                    }
                }
            }

            if ( $aResult == null ) {
                foreach( $this->aaNodeObjects[self::SCOPE_TYPE_COUNTERS] as &$aItem ) {
                    if ( $aItem['id'] == $id ) {
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
            OlabExceptionHandler::LogException( $tracer->sBlockName, $exception, false );
        }

        return null;

    }

    /**
     * Get a file for an index
     * @param $id int Id of object to find (null = all)
     * @param $flatten boolean flatten results into signal array or by level
     * @return \App\Modules\Olab\Models\Files as an array
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
                    if ( $aItem['id'] == $id ) {
                        return $aItem;
                    }
                }
            }

            if ( $aResult == null ) {
                foreach( $this->aaMapObjects[self::SCOPE_TYPE_FILES] as &$aItem ) {
                    if ( $aItem['id'] == $id ) {
                        return $aItem;
                    }
                }
            }

            if ( $aResult == null ) {
                foreach( $this->aaNodeObjects[self::SCOPE_TYPE_FILES] as &$aItem ) {
                    if ( $aItem['id'] == $id ) {
                        return $aItem;
                    }
                }
            }

            if ( $aResult == null ) {
                Log::error( $tracer->sBlockName . ": failed to find file id = " . $id );
            }

            return $aResult;
        }
        catch (Exception $exception)
        {
            OlabExceptionHandler::LogException( $tracer->sBlockName, $exception, false );
        }

        return null;
    }

    /**
     * Get a question for an index
     * @param $id int Id of object to find (null = all)
     * @param $flatten boolean flatten results into signal array or by level
     * @return \App\Modules\Olab\Models\Questions
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
                    if ( $aItem['id'] == $id ) {
                        return $aItem;
                    }
                }
            }

            if ( $aResult == null ) {
                foreach( $this->aaMapObjects[self::SCOPE_TYPE_QUESTIONS] as &$aItem ) {
                    if ( $aItem['id'] == $id ) {
                        return $aItem;
                    }
                }
            }

            if ( $aResult == null ) {
                foreach( $this->aaNodeObjects[self::SCOPE_TYPE_QUESTIONS] as &$aItem ) {
                    if ( $aItem['id'] == $id ) {
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
            OlabExceptionHandler::LogException( $tracer->sBlockName, $exception, false );
        }

        return array();
    }

    /**
     * Load the scoped object handler from a user state object
     * @param mixed $userState
     */
    public function loadFromUserState( $userState ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        try
        {
            if ( gettype( $userState ) == "array") {
                $aaStateData = $userState;
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
            OlabExceptionHandler::LogException( $tracer->sBlockName, $exception );
        }

    }

    /**
     * Get map-scoped objects
     */
    private function loadMapLevelObjects( $oMap ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        $aSource = $oMap->toArray();

        // get server-level constants and append to system objects array
        $this->aaMapObjects[self::SCOPE_TYPE_CONSTANTS] = $aSource[self::RELATION_NAME_CONSTANTS];

        // get server-level questions and append to system objects array
        $this->aaMapObjects[self::SCOPE_TYPE_QUESTIONS] = $aSource[self::RELATION_NAME_QUESTIONS];

        // get server-level files and append to system objects array
        $this->aaMapObjects[self::SCOPE_TYPE_FILES] = $aSource[self::RELATION_NAME_FILES];

        // get server-level counters and append to system objects array
        $this->aaMapObjects[self::SCOPE_TYPE_COUNTERS] = $aSource[self::RELATION_NAME_COUNTERS];

    }

    /**
     * Get node-scoped objects
     */
    private function loadNodeLevelObjects( $oNode ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        // attach embedded file resources
        // loop through all the file resources
        foreach ($oNode->Files as &$oFile ) {

            if ( $oFile->is_embedded === 1 ) {
                // build base64 encoded content mime string
                $oFile->encoded_content = OlabMimeTypeBase::GetEncodedContentString( $oFile->toArray() );
            }
        }

        $aSource = $oNode->toArray();

        if ( !array_key_exists( self::RELATION_NAME_CONSTANTS, $aSource ) )
            $aSource[self::RELATION_NAME_CONSTANTS] = array();

        // get node-level constants and add them system objects array
        $this->aaNodeObjects[self::SCOPE_TYPE_CONSTANTS] = $aSource[self::RELATION_NAME_CONSTANTS];

        if ( !array_key_exists( self::RELATION_NAME_QUESTIONS, $aSource ) )
            $aSource[self::RELATION_NAME_QUESTIONS] = array();

        // get node-level questions and add them system objects array
        $this->aaNodeObjects[self::SCOPE_TYPE_QUESTIONS] = $aSource[self::RELATION_NAME_QUESTIONS];

        if ( !array_key_exists( self::RELATION_NAME_FILES, $aSource ) )
            $aSource[self::RELATION_NAME_FILES] = array();

        // get node-level files and add them system objects array
        $this->aaNodeObjects[self::SCOPE_TYPE_FILES] = $aSource[self::RELATION_NAME_FILES];

        if ( !array_key_exists( self::RELATION_NAME_COUNTERS, $aSource ) )
            $aSource[self::RELATION_NAME_COUNTERS] = array();

        // get node-level counters and add them system objects array
        $this->aaNodeObjects[self::SCOPE_TYPE_COUNTERS] = $aSource[self::RELATION_NAME_COUNTERS];

    }

    /**
     * Get server-scoped objects
     */
    private function loadServerLevelObjects( $oServer ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        $aSource = $oServer->toArray();

        // get server-level constants and append to system objects array
        $this->aaServerObjects[self::SCOPE_TYPE_CONSTANTS] = $aSource[self::RELATION_NAME_CONSTANTS];

        // get server-level questions and append to system objects array
        $this->aaServerObjects[self::SCOPE_TYPE_QUESTIONS] = $aSource[self::RELATION_NAME_QUESTIONS];

        // get server-level files and append to system objects array
        $this->aaServerObjects[self::SCOPE_TYPE_FILES] = $aSource[self::RELATION_NAME_FILES];

        // get server-level counters and append to system objects array
        $this->aaServerObjects[self::SCOPE_TYPE_COUNTERS] = $aSource[self::RELATION_NAME_COUNTERS];
    }

    /**
     * Loads the objects
     */
    private function Initialize() {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        // extract node-scoped objects
        $this->loadNodeLevelObjects( $this->oNode );

        // extract map-scoped objects
        $this->loadMapLevelObjects( $this->oMap );

        // extract server-scoped objects
        $this->loadServerLevelObjects( $this->oServer );

        $this->addReservedServerObjects();
        $this->addReservedMapObjects();
        $this->addReservedNodeObjects();
    }

}