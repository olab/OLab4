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
 * A class to manage user state
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
use Entrada\Modules\Olab\Models\UserSessions;
use Entrada\Modules\Olab\Models\QuestionTypes;
use Entrada\Modules\Olab\Models\Constants;
use Entrada\Modules\Olab\Models\UserState;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\MapNodes;
use Entrada\Modules\Olab\Models\Servers;
use Entrada\Http\Controllers\Controller;

class UserStateHandler {

    const PAYLOAD_FOLLOW_ONCE = "followOnce";
    const PAYLOAD_VISIT_ONCE = "visitOnce";

    private $aState = array();

    public function __construct() {

    }

    public function Load( UserState $oUserState ) {
      
      $oState = json_decode( $oUserState->state_data );
      $this->aState = json_decode(json_encode( $oState ), true);

    }

    public function &State() {
      return $this->aState;
    }

    private function removeSystemObjects( $aaCounters ) {
      
      $items = array();

      // remove any system counters since they will be provided
      // by the renderer, and not stored in user state
      for ( $i = 0; $i < sizeof( $aaCounters ); $i++ ) {

        $item = $aaCounters[ $i ];

        // skip over system-reserved object
        if ( $item['isSystem'] == "1" ) {
          continue;
        }

        // add map-level object
        if ( $item['scopeLevel'] == Maps::IMAGEABLE_TYPE ) {
          $items[] = $item;
        }

        // add node-level object
        if ( $item['scopeLevel'] == MapNodes::IMAGEABLE_TYPE ) {
          $items[] = $item;
        }

      }

      return $items;
    }

    /**
     * Creates a new user state
     * @param mixed $user_id
     * @param mixed $map_id
     * @param mixed $node_id
     * @param \Entrada\Modules\Olab\Classes\ScopedObjectManager $oScopedObjects
     */
    public function Create( $oScopedObjects, $user_id, $context_id, $map_id = null, $node_id = null) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($user_id, $map_id, $node_id)" );

        try
        {

            // test if have user state for this user/map/node. if so, delete it.
            if ( $this->Get( $user_id, $map_id, null ) ) {
                $this->Delete( $user_id, $map_id, null  );
            }

            // create a new user session for the current user and map
            $oUserSession = UserSessions::CreateFrom( $user_id, $map_id );

            $aaStateData = array();

            // get all active counters
            $aaCounters = $oScopedObjects->getCounter( null, false );

            // rebuild counters array - minus the system counters, which are 
            // populated by the renderer
            unset( $aaStateData['server'] );
            $aaStateData['node'] = $this->removeSystemObjects( $aaCounters['node'] );
            $aaStateData['map'] = array(); // $this->removeSystemObjects( $aaCounters['map'] );
            $aaStateData['contextId'] = $context_id;

            $aaStateData[self::PAYLOAD_FOLLOW_ONCE] = array();
            $aaStateData[self::PAYLOAD_VISIT_ONCE] = array();

            $jUserState = json_encode( $aaStateData );

            $oState = new UserState();
            $oState->user_id = $user_id;
            $oState->map_id = $map_id;
            $oState->map_node_id = $node_id;
            $oState->state_data = $jUserState;
            $oState->session_id = $oUserSession->id;
            $oState->save();

            Log::debug( "created user state. user_id: $user_id, map_id: $map_id " );

            return $oState;

        }
        catch (Exception $exception)
        {
            OlabExceptionHandler::logException( $tracer->sBlockName, $exception );
        }

        return null;
    }

    /**
     * Deletes state for a user/map
     * @param mixed $user_id
     * @param mixed $map_id
     * @param mixed $node_id
     */
    public function Delete( $user_id, $map_id, $node_id = null ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($user_id, $map_id, $node_id)" );

        try
        {

            $aoStates = array();

            if ( $node_id == null ) {
                // get all states for the map
                $aoStates = UserState::byMap( $user_id, $map_id )->get();
            }
            else {
                // get state for the node
                $aoStates = UserState::byNode( $user_id, $map_id, $node_id )->get();
            }

            // for each state found, delete it
            foreach ($aoStates as $oState ) {
                Log::debug( "deleting user state. user_id: $oState->user_id, map_id: $oState->map_id" );
                $oState->delete();
            }

        }
        catch (Exception $exception)
        {
            OlabExceptionHandler::logException( $tracer->sBlockName, $exception );
        }

    }

    /**
     * Gets session state by id
     * @param int $session_id
     * 
     */
    public function GetById( $session_id ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($session_id)" );

        try
        {
            $oState = UserState::bySessionId( $session_id )->first();
            return $oState;
        }
        catch (Exception $exception)
        {
            OlabExceptionHandler::logException( $tracer->sBlockName, $exception );
        }

        return null;
    }

    /**
     * Get the requested state as an object
     * @param mixed $user_id
     * @param mixed $iServerId
     * @param mixed $map_id
     * @param mixed $node_id
     */
    public function Get( $user_id, $iServerId = null, $map_id = null, $node_id = null ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($user_id, $map_id, $node_id)" );

        try
        {
            $oState = null;

            if ( ( $map_id == null ) && ( $node_id == null ) ) {
                $oState = UserState::byUser( $user_id )->first();
            }

            else if ( ( $map_id != null ) && ( $node_id == null ) ) {
                $oState = UserState::byMap( $user_id, $map_id )->first();
            }

            else if ( ( $map_id != null ) && ( $node_id != null ) ) {
                $oState = UserState::byNode( $user_id, $map_id, $node_id )->first();
            }

            if ( $oState != null ) {

              $aaStateData = json_decode($oState->state_data, true);

              Log::debug( "loaded user state. user_id: $user_id, map_id: $map_id, node_id: $node_id " );
              //ScopedObjectManager::dumpCounterArray( $aaStateData['server'] );
              ScopedObjectManager::dumpCounterArray( $aaStateData['map'] );
              ScopedObjectManager::dumpCounterArray( $aaStateData['node'] );
            }

            return $oState;
        }
        catch (Exception $exception)
        {
            OlabExceptionHandler::logException( $tracer->sBlockName, $exception );
        }

        return null;

    }

    /**
     * Updates a state record
     * @param UserState $oState
     * @param ScopedObjectManager $oScopedObjects
     * 
     */
    public function Update( &$oState,  $oScopedObjects = null ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        try
        {
          // test if updating UserState with new/updated counter values
          if ( $oScopedObjects != null ) {

            // get all active counters
            $aaCounters = $oScopedObjects->getCounter( null, false );
            $jCounters = json_encode( $aaCounters );

            $oState->state_data = $jCounters;
          }

          $aaStateData = json_decode($oState->state_data, true);

          Log::debug( "updating user state. user_id: $oState->user_id, map_id: $oState->map_id" );
          //ScopedObjectManager::dumpCounterArray( $aaStateData['server'] );
          ScopedObjectManager::dumpCounterArray( $aaStateData['map'] );
          ScopedObjectManager::dumpCounterArray( $aaStateData['node'] );

          $oState->save();

          return $oState;
        }
        catch (Exception $exception)
        {
            OlabExceptionHandler::logException( $tracer->sBlockName, $exception );
        }

    }

    public function UpdateCounterValue( $counterAction ) {

    }

}
?>