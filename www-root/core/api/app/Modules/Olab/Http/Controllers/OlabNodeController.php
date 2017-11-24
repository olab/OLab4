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
 * A controller for node functionality
 *
 * @author Organisation: Cumming School of Medicine, University of Calgary
 * @author Developer: Corey Wirun (corey@cardinalcreek.ca)
 * @copyright Copyright 2017 University of Calgary. All Rights Reserved.
 */

namespace App\Modules\Olab\Http\Controllers;

use \Exception;
use App\Modules\Olab\Models\Map;
use App\Modules\Olab\Models\Node;
use App\Modules\Olab\Models\NodeCounter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Modules\Olab\Classes\OlabCodeTracer;
use App\Modules\Olab\Classes\UserStateHandler;
use App\Modules\Olab\Classes\ScopedObjectManager;
use App\Modules\Olab\Classes\CounterManager;
use App\Modules\Olab\Classes\OlabExceptionHandler;
use App\Modules\Olab\Models\Servers;
use App\Modules\Olab\Models\Maps;
use App\Modules\Olab\Models\MapNodes;
use App\Modules\Olab\Classes\Autoload\WikiTags\OlabTagBase;
use App\Modules\Olab\Classes\HostSystemApi;

class OLabNodeController extends Controller
{
    const PAYLOAD_INDEXNAME_MAP = "map";
    const PAYLOAD_INDEXNAME_SERVER = "server";
    const PAYLOAD_INDEXNAME_AUTOLOADER = "Scripts";
    const PAYLOAD_INDEXNAME_NODE = "node";
    const PAYLOAD_INDEXNAME_STATE = "state";
    const INCLUDE_STATIC_QSNAME = "includeMapData";
    const DEBUG_QSNAME = "showWiki";

    protected $jwt;
    protected $oUserState;

    /**
     * Standard constructor
     * @param JWTAuth $jwt
     */
    public function __construct(JWTAuth $jwt) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        $this->jwt = $jwt;

        try {

            // create user state handler class
            $this->oUserState = new UserStateHandler();

        }
        catch (TokenExpiredException $exception) {
            OlabExceptionHandler::LogException( $tracer->sBlockName, $exception );
        }
        catch (TokenInvalidException $exception) {
            OlabExceptionHandler::LogException( $tracer->sBlockName, $exception );
        }
        catch (Exception $exception) {
            OlabExceptionHandler::LogException( $tracer->sBlockName, $exception );
        }

    }

    /**
     * Convert any server-side Wiki tags to HTML
     */
    private function deWikifyMarkup( $oSystemObjects, &$aNode ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        // extract any WIKI tags in the markup
        $asWikiTags = OlabTagBase::ExtractWikiTags( $aNode["text"] );

        // loop through all the tags, try and autoload render class for tag, then invoke that renderer
        foreach ( $asWikiTags as $sWikiTag ) {

            try
            {
                // instantiate Wiki tag renderer class
                $oTagRenderer = OlabTagBase::ClassFactory( $sWikiTag );

                // if got class, run it's renderer
                if ( $oTagRenderer != null ) {
                    $aNode["text"] = $oTagRenderer->Render( $aNode["text"], $oSystemObjects, $sWikiTag );
                }
            }
            catch (Exception $exception)
            {
                OlabExceptionHandler::LogException( $tracer->sBlockName, $exception, false );
            }

        }

        return $aNode;
    }

    /**
     * Get single node with associated components
     * @param mixed $id map id
     * @return \Illuminate\Http\JsonResponse
     */
    public function get($id)
    {
        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        try
        {
            $data = Node::with('counters')->findOrFail($id);
            return response()->json($data);
        }
        catch (Exception $exception)
        {
            OlabExceptionHandler::LogException( $tracer->sBlockName, $exception );
        }

        return null;
    }

    /**
     * Gets list of map-level javascript files to autoload
     * @param mixed $nServerId
     * @param mixed $nMapId
     * @return array of script files relative to autoloader root
     */
    public static function getMapScriptFiles( $nServerId, $nMapId, &$results = array() ) {

      // spin up a function tracer.  Handles entry/exit/timing messages
      $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

      try
      {

        // get map-level files
        $dir = HostSystemApi::GetScriptAutoloadRootPath() . "/" . $nServerId . "/" . $nMapId;
        if ( is_dir( $dir ) ) {

          $files = scandir( $dir );

          foreach($files as $key => $value){
            $path = $dir.DIRECTORY_SEPARATOR.$value;
            if(!is_dir($path)) {
              $results[] = str_replace( $dir, "/" . $nServerId . "/" . $nMapId, $path );
            }
          }
        }
      }
      catch (Exception $exception) {
        return OlabExceptionHandler::RestApiError( $exception );
      }

      return $results;

    }

    /**
     * Gets list of server-level javascript files to autoload
     * @param mixed $nServerId
     * @return array of script files relative to autoloader root
     */
    public static function getServerScriptFiles( $nServerId ) {

      // spin up a function tracer.  Handles entry/exit/timing messages
      $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

      $results = array();

      try
      {

        // get server-level files
        $dir = HostSystemApi::GetScriptAutoloadRootPath() . "/" . $nServerId;

        if ( is_dir( $dir ) ) {

          $files = scandir( $dir );

          foreach($files as $key => $value){
            $path = $dir.DIRECTORY_SEPARATOR.$value;
            if(!is_dir($path)) {
              $results[] = str_replace( $dir, "/" . $nServerId, $path );
            }
          }
        }

      }
      catch (Exception $exception) {
        return OlabExceptionHandler::RestApiError( $exception );
      }

      return $results;

    }

    /**
     * Get node from map
     * @param mixed $request HTTP Request
     * @param $nMapId integer Id
     * @param $nNodeId integer Id
     * @return \Illuminate\Http\JsonResponse|null
     */
    public function play( Request $request, $nMapId, $nNodeId = null) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($nMapId, $nNodeId)" );

        $aaPayload = array();

        try {

            // test if new play (no node passed in )
            $bNewPlay = ( $nNodeId == "0" );

            // get the current user
            $nUserId = HostSystemApi::GetUserId();

            // get the server, including related entities
            $oServer = Servers::At( Servers::LOCAL_SERVER_ID );

            // get the map, including related entities
            $oMap = Maps::Active()->Abbreviated()->WithObjects()->At( $nMapId );
            if ( $oMap == null ) {
                throw new Exception("Cannot find map for index: " + $nMapId );
            }

            // test if including map data
            $bIncludeStaticData = ( $request->query->get(self::INCLUDE_STATIC_QSNAME) == '1' );

            // get any user state for the current map
            $oState = $this->oUserState->Get( $nUserId, Servers::LOCAL_SERVER_ID, $nMapId );

            // if '0' passed in, then new play, meaning get root node
            if ( $nNodeId == "0") {

                // get the root node and save the node id
                $oNode = $oMap->NodeAt();
                $nNodeId = $oNode->id;
            }

            // an explicit node id was passed in, read from database
            else {

                $oNode = $oMap->NodeAt( $nNodeId );
            }

            if ( $oNode == null ) {
                throw new Exception("Cannot find map node for index: " + $nNodeId );
            }

            // load all the system objects for node, map, server
            $oScopedObjects = new ScopedObjectManager( $oServer,
                                                       $oMap,
                                                       $oNode );

            // add node to play payload
            $aaPayload[self::PAYLOAD_INDEXNAME_NODE] = $oNode->toArray();

            // if including map metadata meaning return map (or higher) info to caller. usually means
            // this is the start of a session, or it can be from user doing browser refresh
            if ( $bIncludeStaticData ) {

                // add map to play payload
                $aaPayload[self::PAYLOAD_INDEXNAME_MAP] = $oMap->toArray();
                // add server to play payload
                $aaPayload[self::PAYLOAD_INDEXNAME_SERVER] = $oServer->toArray();

                // graft server-level script autoloader information to server info play payload
                $aaPayload[self::PAYLOAD_INDEXNAME_SERVER][self::PAYLOAD_INDEXNAME_AUTOLOADER]
                  = self::getServerScriptFiles( $oServer->id );

                // graft map-level script autoloader information to map info play payload
                $aaPayload[self::PAYLOAD_INDEXNAME_MAP][self::PAYLOAD_INDEXNAME_AUTOLOADER]
                  = self::getMapScriptFiles( $oServer->id, $oMap->id );

            }

            // act on the various combinations of new play, node play, and whether or not
            // found state for map.  This can be optimized, but each combination if handled
            // individually to maintain the granularity of being able to change the behavior
            // in isolation

            // if new play wipe state for user and create new one for node
            // since this is a new play
            if ( $bNewPlay ) {

                // create new user state (which will delete any existing states)
                $oState = $this->oUserState->Create( $oScopedObjects, $nUserId, $nMapId, $nNodeId );
            }

            // if not new play and found previous state, update state with new node id
            else if ( !$bNewPlay && ( $oState != null ) ) {

                $oState->map_node_id = $nNodeId;
            }

            // if not new play and no previous state, create state with new node id and save it
            else if ( !$bNewPlay && ( $oState == null ) ) {

                // create new user state (which will delete any existing states)
                $oState = $this->oUserState->Create( $oScopedObjects, $nUserId, $nMapId, $nNodeId );
            }

            // perform any OnNodeOpen event actions
            CounterManager::onNodeOpen( $oNode, $oState );

            // save the potentially modified state back to the database
            $this->oUserState->Update( $oState );

            // add user state to play payload
            $aaPayload[self::PAYLOAD_INDEXNAME_STATE] = $oState->toArray();

            // replace the server-processed wiki tags with html markup
            if ( $request->query->get(self::DEBUG_QSNAME) != '1' ) {
                $this->deWikifyMarkup( $oScopedObjects, $aaPayload[self::PAYLOAD_INDEXNAME_NODE] );
            }
        }
        catch (Exception $exception) {
            return OlabExceptionHandler::RestApiError( $exception );
        }

        return response()->json( $aaPayload );
    }
}