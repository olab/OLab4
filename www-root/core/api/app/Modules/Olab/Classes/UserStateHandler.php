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

class UserStateHandler {

    public function __construct() {

    }

    /**
     * Creates a new user state
     * @param mixed $iUserId
     * @param mixed $iMapId
     * @param mixed $iNodeId
     * @param \App\Modules\Olab\Classes\ScopedObjectManager $oScopedObjects
     */
    public function Create( $oScopedObjects, $iUserId, $iMapId = null, $iNodeId = null) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($iUserId, $iMapId, $iNodeId)" );

        try
        {

            // test if have user state for this user/map/node. if so, delete it.
            if ( $this->Get( $iUserId, $iMapId, null ) ) {
                $this->Delete( $iUserId, $iMapId, null  );
                Log::debug( "deleting existing state" );
            }

            // get all active counters
            $aaCounters = $oScopedObjects->getCounter( null, false );
            $jCounters = json_encode( $aaCounters );

            $oState = UserState::create(['user_id' => $iUserId,
                               'map_id' => $iMapId,
                               'map_node_id' => $iNodeId,
                               'state_data' => $jCounters ] );

            return $oState;

        }
        catch (Exception $exception)
        {
            OlabExceptionHandler::LogException( $tracer->sBlockName, $exception );
        }

        return null;
    }

    /**
     * Deletes state for a user/map
     * @param mixed $iUserId
     * @param mixed $iMapId
     * @param mixed $iNodeId
     */
    public function Delete( $iUserId, $iMapId, $iNodeId = null ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($iUserId, $iMapId, $iNodeId)" );

        try
        {

            $aoStates = array();

            if ( $iNodeId == null ) {
                // get all states for the map
                $aoStates = UserState::byMap( $iUserId, $iMapId )->get();
            }
            else {
                // get state for the node
                $aoStates = UserState::byNode( $iUserId, $iMapId, $iNodeId )->get();
            }

            // for each state found, delete it
            foreach ($aoStates as $oState ) {
                $oState->delete();
            }

        }
        catch (Exception $exception)
        {
            OlabExceptionHandler::LogException( $tracer->sBlockName, $exception );
        }

    }

    /**
     * Get the requested state as an object
     * @param mixed $iUserId
     * @param mixed $iServerId
     * @param mixed $iMapId
     * @param mixed $iNodeId
     */
    public function Get( $iUserId, $iServerId = null, $iMapId = null, $iNodeId = null ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($iUserId, $iMapId, $iNodeId)" );

        try
        {
            $oState = null;

            if ( ( $iMapId == null ) && ( $iNodeId == null ) ) {
                $oState = UserState::byUser( $iUserId )->first();
            }

            else if ( ( $iMapId != null ) && ( $iNodeId == null ) ) {
                $oState = UserState::byMap( $iUserId, $iMapId )->first();
            }

            else if ( ( $iMapId != null ) && ( $iNodeId != null ) ) {
                $oState = UserState::byNode( $iUserId, $iMapId, $iNodeId )->first();
            }

            return $oState;
        }
        catch (Exception $exception)
        {
            OlabExceptionHandler::LogException( $tracer->sBlockName, $exception );
        }

        return null;

    }

    /**
     * Updates a state record
     * @param mixed $oState
     */
    public function Update( $oState ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        try
        {
            $oState->save();
        }
        catch (Exception $exception)
        {
            OlabExceptionHandler::LogException( $tracer->sBlockName, $exception );
        }

    }

    public function UpdateCounterValue( $counterAction ) {

    }

}
?>