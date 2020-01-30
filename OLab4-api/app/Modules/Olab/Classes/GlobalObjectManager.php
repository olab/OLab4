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
 * Manages global objects within OLab sessions
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
use Entrada\Modules\Olab\Classes\Autoload\Events\EventHandler;
use Entrada\Modules\Olab\Classes\SessionTraceManager;
use Entrada\Modules\Olab\Classes\UserStateHandler;

use \Ds\Map;

class GlobalObjectManager
{
    private $object_map;

    const EVENT = "EventHandler";
    const USER_STATE = "UserStateHandler";
    const SESSION = "SessionTraceManager";

    public function __construct() {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        $this->object_map = new Map([]);

        $this->object_map->put( self::EVENT, new EventHandler() );
        $this->object_map->put( self::USER_STATE, new UserStateHandler() );
        $this->object_map->put( self::SESSION, new SessionTraceManager() );
    }

    /**
     * Direct accessor to the underlying object map
     * @return \DS\Map
     */
    public function ObjectMap() {
        return $this->object_map;
    }

    /**
     * Intializes a new instance
     */
    public static function Initialize() {
        self::Instance();    
    }

    /**
     * Singleton object generator
     * @return GlobalObjectManager|null
     */
    public static function Instance() {

        static $inst = null;

        if ($inst === null) {
            $inst = new GlobalObjectManager();
            Log::debug( "Created new " . __CLASS__ );
        }

        return $inst;        
    }

    /**
     * Get an object contained in the map
     * @param mixed $object_name 
     * @return mixed Object instance
     */
    public static function Get( $object_name ) {

        $obj = self::Instance()->ObjectMap();

        if ( $obj->hasKey( $object_name ) ) {
            return $obj->get( $object_name );
        }

        throw new Exception("Global object key '" . $object_name . "' not found.");

    }

}