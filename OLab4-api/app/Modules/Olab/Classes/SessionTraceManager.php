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
 * Manages user activity held within OLab sessions
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
use Entrada\Modules\Olab\Models\UserSessions;
use Entrada\Modules\Olab\Classes\OlabExceptionHandler;
use Entrada\Modules\Olab\Classes\HostSystemApi;
use Entrada\Modules\Olab\Classes\OlabCodeTracer;

class SessionTraceManager
{
    public function __construct() {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

    }

    /**
     * Creates a new session
     * @param integer $user_id 
     * @param integer $map_id 
     * @return UserSessions New session
     */
    public function Create( $user_id, $map_id ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "( $user_id, $map_id )" );

        // add record to user_sessions 
        $oRecord = UserSessions::CreateFrom( $user_id, $map_id );
        $oRecord->save();

        Log::debug( "Created new session: id = " . $oRecord->id );

        return $oRecord;
    }

    /**
     * Get the uuid for a given session id
     * @param mixed $session_id 
     * @return mixed
     */
    public function GetUuid( $session_id ) {
        
        $oRec = UserSessions::At( $session_id );
        if ( $oRec != null )
            return $oRec->uuid;

        return null;
    }

}