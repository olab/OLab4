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
namespace Entrada\Modules\Olab\Http\Controllers;

use \Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use Entrada\Http\Controllers\Controller;
use Entrada\Modules\Olab\Classes\OlabCodeTracer;
use Entrada\Modules\Olab\Classes\OlabConstants;
use Entrada\Modules\Olab\Classes\OLabUtilities;
use Entrada\Modules\Olab\Classes\UserStateHandler;
use Entrada\Modules\Olab\Classes\OlabObjectNotFoundException;
use Entrada\Modules\Olab\Classes\ScopedObjectManager;
use Entrada\Modules\Olab\Classes\Autoload\AccessControl\AccessControlBase;
use Entrada\Modules\Olab\Classes\OlabAccessDeniedException;
use Entrada\Modules\Olab\Classes\OlabServerException;
use Entrada\Modules\Olab\Classes\Autoload\Events\EventHandler;
use Entrada\Modules\Olab\Classes\OlabExceptionHandler;
use Entrada\Modules\Olab\Classes\Autoload\WikiTags\OlabTagBase;
use Entrada\Modules\Olab\Classes\HostSystemApi;
use Entrada\Modules\Olab\Classes\CounterManager;
use Entrada\Modules\Olab\Classes\SecurityContext;
use Entrada\Modules\Olab\Classes\CustomAssetManager;
use Entrada\Modules\Olab\Models\BaseModel;
use Entrada\Modules\Olab\Models\Map;
use Entrada\Modules\Olab\Models\Node;
use Entrada\Modules\Olab\Models\MapNodeLinks;
use Entrada\Modules\Olab\Models\NodeCounter;
use Entrada\Modules\Olab\Models\Servers;
use Entrada\Modules\Olab\Models\UserState;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\MapNodes;
use Entrada\Modules\Olab\Classes\GlobalObjectManager;

class OLabNodeController extends OlabController
{
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
    const INCLUDE_STATIC_QSNAME = "includeMapData";
    const RESUME_QSOPTION = "resume";
    const SHOWWIKI_QSOPTION = "showWiki";

    protected $jwt;
    private $oServer;
    private $oNode;
    private $oMap;
    private $isAdmin;

    /**
     * Standard constructor
     * @param JWTAuth $jwt
     */
    public function __construct(JWTAuth $jwt) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        $this->jwt = $jwt;

        // run common controller initialization
        $this->initialize();

        // get the server, including related entities
        $this->oServer = Servers::At( Servers::DEFAULT_LOCAL_ID );

        $this->isAdmin = HostSystemApi::isAdmin();

        // spin up global object handler if one doesn't exist yet
        GlobalObjectManager::Initialize();
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
            $data = MapNodes::with('counters')->findOrFail($id);
            return response()->json($data);
        }
        catch (Exception $exception)
        {
            OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
            return OlabExceptionHandler::restApiError( $exception );
        }

        return null;
    }

    /**
     * Play node's info page
     * @param Request $request 
     * @param mixed $map_id 
     * @param mixed $node_id 
     */
    public function info( Request $request, $map_id, $node_id = null) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "(" . $map_id . "," . $node_id . ")" );
        $aaPayload = array();

        try {

            // get map (throws if no access)
            $oMap = $this->get_map( $map_id, AccessControlBase::ACL_READ_ACCESS );

            // get node (throws if no access)
            $oNode = $this->get_node( $oMap, $node_id, AccessControlBase::ACL_READ_ACCESS );

            // add node to play payload
            $aaPayload[self::PAYLOAD_INDEXNAME_NODE] = array();
            $aaPayload[self::PAYLOAD_INDEXNAME_NODE][self::PAYLOAD_NODE_INFO] = $oNode->info;

            return response()->json( $aaPayload );

        }
        catch (Exception $exception) {
            OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
            return OlabExceptionHandler::restApiError( $exception );
        }

    }

    protected function get_node( Maps $oMap, &$node_id, $acl = AccessControlBase::ACL_NO_ACCESS ) {

        // if '0' passed in, then new play, meaning get root node
        if ( $node_id == "0") {

          // get the root (or first defined) node and save the node id
          $oNode = $this->oMap->NodeAt( null );
          $node_id = $oNode->id;
        }
        // an explicit node id was passed in, read from database
        else {
          $oNode = $this->oMap->NodeAt( $node_id );
        }

        $oNode = parent::get_node( $this->oMap, $oNode, AccessControlBase::ACL_EXECUTE_ACCESS );
        
        // TODO - apply security on MapNodeLinks
        foreach ($oNode->MapNodeLinks as $oMapLink ) {
        }

        // if not an admin, remove any annotations from the node
        if ( !$this->isAdmin ) {
            while ( $oNode->Notes->count() > 0 ) {
                $oNode->Notes->pop();
            }

            $oNode->annotation = null;
        }

        return $oNode;
    }

    public function play( Request $request, $map_id, $node_id = null) {

        $resume = ( $request->query->get(self::RESUME_QSOPTION) == '1' );
        return $this->internal_play( $resume, $request, $map_id, $node_id );
    }

    /**
     * Get node from map
     * @param mixed $request HTTP Request
     * @param $map_id integer Id
     * @param $node_id integer Id
     * @return \Illuminate\Http\JsonResponse|null
     */
    private function internal_play( bool $resume, Request $request, $map_id, $node_id ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "(" . $resume . "Request, " . 
                                                  $map_id . "," . $node_id . ")" );
        $aaPayload = array();

        try {

            // test if new play (no node passed in)
            $is_new_play = ( $node_id == "0" );
            Log::debug( "is_new_play = " . $is_new_play );

            // test if including all data
            $include_full_data = ( $request->query->get(self::INCLUDE_STATIC_QSNAME) == '1' );
            Log::debug( "include_full_data = " . $include_full_data );

            // get map (throws if no access)
            $this->oMap = $this->get_map( $map_id, AccessControlBase::ACL_EXECUTE_ACCESS );

            // get node (throws if no access)
            $this->oNode = $this->get_node( $this->oMap, $node_id, AccessControlBase::ACL_EXECUTE_ACCESS );

            // load all the scoped objects for all scope levels, as they 
            // exist in the database
            $oScopedObjects = new ScopedObjectManager( $this->oServer,
                                                       $this->oMap,
                                                       $this->oNode );
            if ( $is_new_play ) {

                // create new user state
                $oState = GlobalObjectManager::Get( GlobalObjectManager::USER_STATE )
                  ->Create( $oScopedObjects, $this->user_id, $request->query->get("contextId"), $map_id, $node_id );

                // create new session and save id as part of user state
                $oSession = GlobalObjectManager::Get( GlobalObjectManager::SESSION )
                                                ->Create( HostSystemApi::getUserId(), $this->oMap->id );
                $oState->session_id = $oSession->id;

                // fire onSessionInitialized event
                GlobalObjectManager::Get( GlobalObjectManager::EVENT )
                                                ->FireEvent( EventHandler::ON_MAP_STARTED, 
                                                             array( $oState, $this->oNode ) );
            }
            else {

                // get any existing user state for the current map
                $oState = GlobalObjectManager::Get( GlobalObjectManager::USER_STATE )
                  ->Get( $this->user_id, Servers::DEFAULT_LOCAL_ID, $map_id );

                // update state with new node
                $oState->map_node_id = $node_id;

                // is resuming, fire onSessionResumedEvent
                if ( $resume ) {
                    GlobalObjectManager::Get( GlobalObjectManager::EVENT )
                      ->FireEvent( EventHandler::ON_MAP_RESUMED, array( $oState, $this->oNode ) );
                }

            }

            // test if node is an end node, meaning the onMapCompleted event can be fired
            if ( $this->oNode->IsExitNode() ) {

                // on exit node = fire finished map event
                GlobalObjectManager::Get( GlobalObjectManager::EVENT )
                  ->FireEvent( EventHandler::ON_MAP_COMPLETED, array( $oState, $this->oMap ) );
            }

            else {

                // not on exit node = fire 'regular' node arrived event
                // only if we aren't 'resuming'
                if ( !$resume ) {

                    GlobalObjectManager::Get( GlobalObjectManager::EVENT )
                      ->FireEvent( EventHandler::ON_NODE_ARRIVED, array( $oState, $this->oNode ) );

                }
            }

            // perform any OnNodeOpen event actions on counters and update
            // user state with any changes
            CounterManager::onNodeOpen( $this->oNode, $oScopedObjects, $oState );

            // add user state to play payload
            $aaPayload[self::PAYLOAD_INDEXNAME_STATE] = $oState->toArray();

            // add node to play payload
            $aaPayload[self::PAYLOAD_INDEXNAME_NODE] = $this->oNode->toArray();

            // test for show wikitag option
            $show_wiki = $request->query->get(self::SHOWWIKI_QSOPTION);
            if ( $show_wiki == null ) {
                $show_wiki = OlabConstants::RENDER_MARKUP;
            }

            // replace any server-processed wiki tags in the node with html markup
            $this->deWikifyNode( $show_wiki, $oState, $oScopedObjects, $aaPayload[self::PAYLOAD_INDEXNAME_NODE] );

            // add any script assets to the payload
            $aaPayload = CustomAssetManager::loadAssets($aaPayload, HostSystemApi::getRelativePath());

            // conditionlly load full data (server, map, node if root node), or just node
            // if past root node.
            $this->buildMapStaticData( $include_full_data, $oScopedObjects, $aaPayload );

            // evaluate any affects that wiki tags can have on the node play payload
            $this->adjustPayloadFromWikiTags( $oState, $oScopedObjects, $aaPayload );

            // evaluate any affects that the request can have on the node play payload
            $this->adjustPayloadFromRequest( $request, $oState, $oScopedObjects, $aaPayload );

            return response()->json( $aaPayload );

        }
        catch (Exception $exception) {
            OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
            return OlabExceptionHandler::restApiError( $exception );
        }

    }

    /**
     * Adjust node play payload based on current node 
     * @param UserState $oState 
     * @param ScopedObjectManager $oScopedObjects 
     * @param mixed $aaPayload 
     */
    private function adjustPayloadFromCurrentNode( UserState $oState, ScopedObjectManager $oScopedObjects, &$aaPayload ) {
        
    }

    /**
     * Adjust node play payload based on HTTP request
     * @param Request $request 
     * @param UserState $oState 
     * @param ScopedObjectManager $oScopedObjects 
     * @param array $aaPayload 
     * @return void
     */
    private function adjustPayloadFromRequest( Request $request, UserState $oState, ScopedObjectManager $oScopedObjects, &$aaPayload ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        try {

            $oStateHandler = new UserStateHandler();
            $oStateHandler->Load( $oState );

            $aState = &$oStateHandler->State();

            // test if current node is 'visit-once' meaning we may save 
            // the node id to the user state so we can ignore it later
            if ( $this->oNode->visit_once == 1 ) {
                
                Log::debug( "Adding visit_once node id: " . $this->oNode->id );

                // append visit-once node id to the nodes list 
                array_push( $aState[UserStateHandler::PAYLOAD_VISIT_ONCE], $this->oNode->id );                
            }

            // test if a link was followed to start this play()
            $link_id = ( int )$request->query('linkId', null );
            if ( $link_id == null ) {
                return;
            }

            $oLink = MapNodeLinks::At( $link_id );
            if ( $oLink == null ) {
                return;
            }

            // nothing more to do if link isn't a 'follow-once'
            if ( $oLink->follow_once === 0 ) {
                return;
            }

            // node link is a follow-once, meaning we need to save it in the user state
            // as 'visited' so it cannot be displayed again

            Log::debug( "Adding follow_once link id: " . $link_id );

            // append follow-once link id to the links list
            array_push( $aState[UserStateHandler::PAYLOAD_FOLLOW_ONCE], $link_id );

            // save updated state back to database
            $oState->state_data = json_encode( $aState );
            $oState->save();

            // update payload with current state
            $aaPayload['state']['state_data'] = $oState->state_data;
        }
        catch (Exception $exception) {
            OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
        }

    }

    /**
     * Adjust node play payload based on node text wikitags
     * @param UserState $oState 
     * @param ScopedObjectManager $oScopedObjects 
     * @param array $aaPayload 
     */
    private function adjustPayloadFromWikiTags( UserState $oState, ScopedObjectManager $oScopedObjects, &$aaPayload ) {
        
        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        try {

            $oTemplate = $this->oMap->Themes()->first();
            if ( $oTemplate != null ) {
                $this->adjustPayloadFromNodeText( $oState, $oScopedObjects, $aaPayload, $oTemplate->header_text );
                $this->adjustPayloadFromNodeText( $oState, $oScopedObjects, $aaPayload, $oTemplate->footer_text );
            }

            $this->adjustPayloadFromNodeText( $oState, $oScopedObjects, $aaPayload, $this->oNode->text );

            $oUserStateHandler = new UserStateHandler();
            $oUserStateHandler->Load( $oState );

            $aVisitedNodeIds = $oUserStateHandler->State()[UserStateHandler::PAYLOAD_VISIT_ONCE];
            $aFollowedLinkIds = $oUserStateHandler->State()[UserStateHandler::PAYLOAD_FOLLOW_ONCE];

            $aNewLinks = array();

            $num_node_links = sizeof( $aaPayload['node']['MapNodeLinks'] );
            for ( $j = 0; $j < $num_node_links;  $j++ ) {

                $bShowLink = true;

                $aLink = $aaPayload['node']['MapNodeLinks'][ $j ];

                for ( $a = 0; $a < sizeof( $aFollowedLinkIds ); $a++ ) {

                    // remove any links that were followed before (follow-once)
                    if ( $aLink['id'] == $aFollowedLinkIds[ $a ] ) {
                        Log::debug( "Skipping link id: " . $aLink['id'] . " followed before." );
                        $bShowLink = false;
                    }

                }

                for ( $a = 0; $a < sizeof( $aVisitedNodeIds ); $a++ ) {

                    // remove any links to nodes where node visited before (visit-once)
                    if ( $aLink['node_id_2'] == $aVisitedNodeIds[ $a ] ) {
                        Log::debug( "Skipping link to node id : " . $aLink['node_id_2'] . " visited before." );
                        $bShowLink = false;
                    }

                }

                // if passed the aobe tests, then add to new displayable links array
                if ( $bShowLink ) {
                    $aNewLinks[] = $aLink;          
                }


            }

            // update user state with new array of links
            $aaPayload['node']['MapNodeLinks'] = $aNewLinks;

        }
        catch (Exception $exception) {
            OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
        }

    }

    /**
     * Adjust payload based on node text
     * @param UserState $oState 
     * @param ScopedObjectManager $oScopedObjects 
     * @param array $aPayload 
     * @param string $wiki_text 
     */
    private function adjustPayloadFromNodeText( UserState $oState, ScopedObjectManager $oScopedObjects, array &$aPayload, $wiki_text ) {
        
        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        // do validity check that a null string has to have 'something' in it
        if ( $wiki_text == null ) {
            $wiki_text = "";
        }

        // extract any WIKI tags in the node markup
        $asWikiTags = OlabTagBase::ExtractWikiTags( $wiki_text );

        // loop through all the tags, try and autoload render class for tag, then invoke that renderer
        foreach ( $asWikiTags as $sWikiTag ) {
            try
            {
                // instantiate Wiki tag renderer class
                $oTagRenderer = OlabTagBase::ClassFactory( $sWikiTag );

                // if got class, run it's adjuster
                if ( $oTagRenderer != null ) {
                    Log::debug( "Adjusting wikiTag: " . $sWikiTag );
                    $oTagRenderer->AdjustProperties( $aPayload, $oState, $oScopedObjects );
                }

            }
            catch (Exception $exception)
            {
                OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
            }
        }
    }

    /**
     * Get node from map
     * @param mixed $request HTTP Request
     * @param $map_id integer Id
     * @param $node_id integer Id
     * @return \Illuminate\Http\JsonResponse|null
     */
    public function suspend( Request $request, int $map_id, int $node_id ) {
        
        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "(" . $map_id . "," . $node_id . ")" );
        $aaPayload = array();

        try
        {
            return response()->json( $aaPayload );        	
        }
        catch (Exception $exception) {
            OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
            return OlabExceptionHandler::restApiError( $exception );
        }
        
    }

    /**
     * Gets list of map-level javascript files to autoload
     * @param mixed $server_id
     * @param mixed $map_id
     * @return array of script files relative to autoloader root
     */
    public static function getMapScriptFiles( $server_id, $map_id, &$results = array() ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );
        try
        {
            // get map-level files
            $dir = HostSystemApi::getScriptAutoloadRootPath() . "/script/" . $server_id . "/" . $map_id;
            if ( is_dir( $dir ) ) {
                $files = scandir( $dir );
                foreach($files as $key => $value){
                    $path = $dir.DIRECTORY_SEPARATOR.$value;
                    if(!is_dir($path)) {
                        $results[] = str_replace( $dir, "/" . $server_id . "/" . $map_id, $path );
                        Log::debug("adding map-level script from file: " . $path );
                    }
                }
            }
        }
        catch (Exception $exception) {
            return OlabExceptionHandler::restApiError( $exception );
        }
        return $results;
    }

    /**
     * Gets list of server-level javascript files to autoload
     * @param mixed $server_id
     * @return array of script files relative to autoloader root
     */
    public static function getServerScriptFiles( $server_id ) {
        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );
        $results = array();
        try
        {
            // get server-level files
            $dir = HostSystemApi::getScriptAutoloadRootPath() . "/script/" . $server_id;
            if ( is_dir( $dir ) ) {
                $files = scandir( $dir );
                foreach($files as $key => $value){
                    $path = $dir.DIRECTORY_SEPARATOR.$value;
                    if(!is_dir($path)) {
                        $results[] = str_replace( $dir, "/" . $server_id, $path );
                        Log::debug("adding server-level script from file: " . $path );
                    }
                }
            }
        }
        catch (Exception $exception) {
            return OlabExceptionHandler::restApiError( $exception );
        }
        return $results;
    }

    private function buildMapStaticData( $include_static_data, $oScopedObjects, &$aaPayload ) {

        // load the node with all the scoped objects since the scope manager may add
        // system-generated ones that are not in the database
        $oScopedObjects->extractNodeScopedObjects( $aaPayload[self::PAYLOAD_INDEXNAME_NODE] );

        if ( $include_static_data ) {

            // add map to play payload
            $aaPayload[self::PAYLOAD_INDEXNAME_MAP] = $this->oMap->toArray();
            // load the map with all the scoped objects since the scope manager may add
            // system-generated ones that are not in the database
            $oScopedObjects->extractMapScopedObjects( $aaPayload[self::PAYLOAD_INDEXNAME_MAP] );

            // add server to play payload
            $aaPayload[self::PAYLOAD_INDEXNAME_SERVER] = $this->oServer->toArray();
            // load the server with all the scoped objects since the scope manager may add
            // system-generated ones that are not in the database
            $oScopedObjects->extractServerScopedObjects( $aaPayload[self::PAYLOAD_INDEXNAME_SERVER] );

            // graft server-level script autoloader information to server info play payload
            $aaPayload[self::PAYLOAD_INDEXNAME_SERVER][self::PAYLOAD_INDEXNAME_AUTOLOADER] = 
                self::getServerScriptFiles( $this->oServer->id );
            // graft map-level script autoloader information to map info play payload
            $aaPayload[self::PAYLOAD_INDEXNAME_MAP][self::PAYLOAD_INDEXNAME_AUTOLOADER] = 
                self::getMapScriptFiles( $this->oServer->id, $this->oMap->id );

            $aTemplate = $oScopedObjects->getTheme();

            // add any available map-level template info to node payload
            if ( $aTemplate != null ) {

                Log::debug( "Found/loaded template markup for map = " . $this->oMap->id );
                $aaPayload[self::PAYLOAD_INDEXNAME_MAP][self::PAYLOAD_INDEXNAME_HEADER] = 
                    $aTemplate["header_text"];
                $aaPayload[self::PAYLOAD_INDEXNAME_MAP][self::PAYLOAD_INDEXNAME_FOOTER] = 
                    $aTemplate["footer_text"];
            }

        }

        // extract all the local and shared counters from the scoped objects
        // so they are stored in their own array
        $oScopedObjects->extractCounters( $aaPayload );

        // clear out counters from the objects data since we've extracted 
        // them already into their own array above
        if ( $include_static_data ) {

            unset( $aaPayload[self::PAYLOAD_INDEXNAME_NODE][ScopedObjectManager::SCOPE_TYPE_COUNTERS] );
            unset( $aaPayload[self::PAYLOAD_INDEXNAME_MAP][ScopedObjectManager::SCOPE_TYPE_COUNTERS] );
            unset( $aaPayload[self::PAYLOAD_INDEXNAME_SERVER][ScopedObjectManager::SCOPE_TYPE_COUNTERS] );

        }

    }

    private function testWikiTagAccess( $wiki_tag ) {

        // let's disable this for now.
        return true;

        $id = null;
        $asParts = explode( ":", $wiki_tag );

        // if a unary wiki-tag (i.e. nothing after wikitag), we allow rendering 
        if ( sizeof( $asParts ) < 2 ) { 
            return true;
        }

        $tag = $asParts[0];

        if ( sizeof( $asParts ) >= 2 ) {
            $id = $asParts[1];
        }

        $object = BaseModel::GetObjectFromWiki( $tag, $id );
        if ( $object == null ) {
            return true;
        }

        // check object-level access control from wikitag
        $oAccessObject = AccessControlBase::classFactory( $object );
        if ( $oAccessObject == null ) {
            return true;
        }

        return $oAccessObject->isExecutable( $object->id );

    }

    /**
     * Convert node Wiki tags to HTML
     * @param int $show_wiki 
     * @param UserState $oState 
     * @param ScopedObjectManager $oScopedObjects 
     * @param array $aNode 
     * @return void
     */
    private function deWikifyNode( int $show_wiki, UserState $oState, ScopedObjectManager $oScopedObjects, &$aNode ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        // test if disabling server-side wiki tag
        if ( $show_wiki == OlabConstants::NORENDER_MARKUP ) {
            return;
        }

        $this->deWikifyText( $show_wiki, $oState, $oScopedObjects, $aNode["text"]  );
        $this->deWikifyText( $show_wiki, $oState, $oScopedObjects, $aNode[self::PAYLOAD_INDEXNAME_HEADER] );
        $this->deWikifyText( $show_wiki, $oState, $oScopedObjects, $aNode[self::PAYLOAD_INDEXNAME_FOOTER] );

        return $aNode;
    }

    /**
     * Convert node test tags to HTML
     * @param int $show_wiki 
     * @param UserState $oState 
     * @param ScopedObjectManager $oScopedObjects 
     * @param mixed $sRawText 
     */
    private function deWikifyText( int $show_wiki, UserState $oState, ScopedObjectManager $oScopedObjects, &$sRawText ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        // extract any WIKI tags in the markup
        $asWikiTags = OlabTagBase::ExtractWikiTags( $sRawText );
        $asH5PTags = [];

        // loop through all the tags, try and autoload render class for tag, then invoke that renderer
        foreach ( $asWikiTags as $sWikiTag ) {

            try
            {
                // skip over H5P tags until second look
                if ( strpos( $sWikiTag, "H5P") !== false ) {
                    $asH5PTags[] = $sWikiTag;
                    continue;
                }

                Log::debug( "Processing wikiTag: " . $sWikiTag );

                // test if allowed to render the wiki tag
                if ( !$this->testWikiTagAccess( $sWikiTag )) {

                    Log::debug( "No access to render '" . $sWikiTag . "'" );

                    $sRawText = str_replace( "[[" . $sWikiTag . "]]", "", $sRawText );
                    continue;
                }
                
                // instantiate Wiki tag renderer class
                $oTagRenderer = OlabTagBase::ClassFactory( $sWikiTag );

                // if got class, run it's renderer
                if ( $oTagRenderer != null ) {
                    $sRawText = $oTagRenderer->Render( $show_wiki, $oState, $sRawText, $oScopedObjects, $sWikiTag );
                }
                else {
                    Log::info( "No server-side wiki tag renderer found for " . $sWikiTag );
                }
            }
            catch (Exception $exception)
            {
                OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
            }
        }

        // loop through all H5P tags then invoke that renderer
        foreach ( $asH5PTags as $sH5PTag ) {

            try
            {
                Log::debug( "Processing wikiTag: " . $sH5PTag );

                // instantiate Wiki tag renderer class
                $oTagRenderer = OlabTagBase::ClassFactory( $sH5PTag );
                $sRawText = $oTagRenderer->Render( $sRawText, $oScopedObjects, $sH5PTag );
            }
            catch (Exception $exception)
            {
                OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
            }

        }
    }
}