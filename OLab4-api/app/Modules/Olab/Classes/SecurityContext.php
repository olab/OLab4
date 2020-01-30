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
 * A class to manage external file stores
 *
 * @author Organisation: Cumming School of Medicine, University of Calgary
 * @author Developer: Corey Wirun (corey@cardinalcreek.ca)
 * @copyright Copyright 2017 University of Calgary. All Rights Reserved.
 */

namespace Entrada\Modules\Olab\Classes;

use Illuminate\Support\Facades\Log;
use Entrada\Modules\Olab\Models\SystemSettings;
use Entrada\Modules\Olab\Models\MapNodes;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\Servers;
use Entrada\Modules\Olab\Classes\Autoload\AccessControl\AccessControlBase;
use Entrada\Modules\Olab\Models\SecurityBase;
use Entrada\Modules\Olab\Models\UserSecurity;
use Entrada\Modules\Olab\Models\RoleSecurity;
use Entrada\Modules\Olab\Classes\HostSystemApi;
use \Ds\Map;

use \Exception;

/**
 * Security context helper class
 *
 * SecurityContext description.
 *
 * @version 1.0
 * @author wirunc
 */
class SecurityContext
{
    private $aMapSecurity;

    public function __construct() {
        $this->aMapSecurity = new Map([]);
    }

    public function setMode( $object ) {
        $t = get_class( $object );
        Log::debug( "object type = " . $t );
    }

    /**
     * Loads map-level security
     */
    public function loadMapSecurity() {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        try
        {
            $this->initializeMapAclCache();
            $this->applyUserMapAcls();


        }
        catch (Exception $exception)
        {
            OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
        }
    }

    /**
     * Initialize map ACL's from 'default' ACL
     */
    private function initializeMapAclCache() {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        $this->aMapSecurity->clear();

        // get list of all maps that are active
        $active_maps = Maps::activeIds()->get();
        // get default map acl
        $default_map_acl = UserSecurity::anyUserAnyMap()->first();

        Log::debug( "Default map acl = " . $default_map_acl->acl );

        // build map with all map ids and default acl
        foreach( $active_maps as $active_map ) {
            $this->aMapSecurity->put( $active_map->id, $default_map_acl->acl );
        }

    }

    /**
     * Apply user-specific ACL's to map list
     */
    private function applyUserMapAcls() {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        // get all user specific records that apply to maps
        $acls = UserSecurity::mapsByUser( HostSystemApi::getUserLogin() )->get();

        if ( $acls->count() === 0 ) {
            Log::debug( "no user-specific acls for maps" );
            return;
        }

        // test if there's an acl for this user that applies to all maps
        if (self::IsAnyMap( $acls[0] ) ) {

            // loop and assign user map default to all maps
            $default_acl = $acls[0]->acl;
            foreach ( $this->aMapSecurity->keys() as $key ) {
                $this->aMapSecurity->put( $key, $default_acl );
                //Log::debug( "user default map " . $key . " acl = " . $default_acl );
            }
        }

        // loop through all map-specific acls for user and assign them to map list
        foreach( $acls as $acl ) {

            // skip over already processed all maps acl, if we see it again
            if (self::IsAnyMap( $acl ) ) {
                continue;
            }

            $this->aMapSecurity->put( self::ObjectId( $acl ), $acl->acl );
            Log::debug( "user map " . self::ObjectId( $acl ) .
                        " acl = " . $this->aMapSecurity->get( self::ObjectId( $acl ) ) );
        }

    }

    /**
     * Get list of allowed maps
     */
    public function getListableMaps() {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );
        $aData = array();

        try
        {
            // traverase all ACL's and test if have list access, if so then add to list
            $this->aMapSecurity->map( function( $key, $value ) {
                if ( self::HasAccess( $value, AccessControlBase::ACL_READ_ACCESS ) ) {
                    array_push( $aData, $key );
                }
            });
        }
        catch (Exception $exception)
        {
            OlabExceptionHandler::logException( $tracer->sBlockName, $exception );
            $aData = array();
        }

        return $aData;
    }

    /**
     * Test if allowed to list specified map
     * @param mixed $map_id
     */
    public function isMapListable( $map_id ) {
        return self::HasAccess( $this->aMapSecurity->get( $map_id ),
                                AccessControlBase::ACL_READ_ACCESS );
    }

    /**
     * Tests if allowed to play specified map
     * @param mixed $map_id
     */
    public function isMapPlayable( $map_id ) {
        return self::HasAccess( $this->aMapSecurity->get( $map_id ),
                                AccessControlBase::ACL_EXECUTE_ACCESS );
    }

    /**
     * Tests if allowed to write/author specified map
     * @param mixed $map_id
     */
    public function isMapAuthorable( $map_id ) {
        return self::HasAccess( $this->aMapSecurity->get( $map_id ),
                                AccessControlBase::ACL_AUTHORABLE_ACCESS );
    }

    /**
     * Test if ACL has requested access
     * @param mixed $acl
     * @param mixed $requested_acl
     * @return boolean
     */
    public static function HasAccess( $acl, $requested_acl ) {
        return ( strpos( $acl, $requested_acl ) !== false );
    }

    /**
     * Test if acl is for any map
     * @param mixed $acl
     * @return boolean
     */
    private static function IsAnyMap( $acl ) {
        return ( ( $acl->imageable_id === 0 ) && ( $acl->imageable_type === "Maps" ) );
    }

    /**
     * Get object type from record
     * @param mixed $acl
     * @return mixed
     */
    private static function ObjectType ( $acl ) {
        return $acl->imageable_type;
    }

    /**
     * Get object id from record
     * @param mixed $acl
     * @return mixed
     */
    private static function ObjectId ( $acl ) {
        return $acl->imageable_id;
    }

    /**
     * Get ACL field from record
     * @param mixed $acl
     * @return string
     */
    private static function Acl( $acl ) {
        return $acl->acl;
    }

    /**
     * Test if acl is for any map
     * @param mixed $acl
     * @return boolean
     */
    private static function IsAnyNode( $acl ) {
        return $acl->imageable_id === 0;
    }

    /**
     * Test if acl is for any name (user or role)
     * @param mixed $acl
     * @return boolean
     */
    private static function IsAnyOwner( $acl ) {
        return $acl->name === null;
    }

}