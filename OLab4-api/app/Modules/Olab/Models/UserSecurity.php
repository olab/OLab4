<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * A model for OLab maps.
 *
 * @author Organisation: Cumming School of Medicine, University of Calgary
 * @author Developer: Corey Wirun (corey@cardinalcreek.ca)
 * @copyright Copyright 2017 University of Calgary. All Rights Reserved.
 */

namespace Entrada\Modules\Olab\Models;
use Entrada\Modules\Olab\Models\BaseModel;
use Entrada\Modules\Olab\Models\SecurityBase;
use Illuminate\Support\Facades\Log;

/**
 * @property integer $id
 * @property string $name
 * @property integer $imageable_id
 * @property string $imageable_type
 * @property string $acl
 */

class UserSecurity extends SecurityBase {

    protected $table = 'security_users';

    /**
     * Return the ACL for any user for any type of object
     * @param mixed $query
     * @param mixed $object_type
     * @return mixed
     */
    public function scopeAnyUserAnyObjectOfType( $query, $object_type ) {
        return $query->where( [ ['name', '=', '*' ] ,
                                ['imageable_id', '=', 0 ] ,
                                ['imageable_type' , '=', $object_type ]
                              ]);
    }

    /**
     * Return the ACL for named user of any id of named object type
     * @param mixed $query
     * @param mixed $group_role_name
     * @param mixed $object_type
     * @return mixed
     */
    public function scopeAclNamedUserAnyIdNamedType( $query, $name, $object_type ) {

        return $query->where( [ ['name', '=', $name ],
                                ['imageable_type', '=', $object_type ] ] )
                     ->orderBy( 'imageable_id' );
    }

    /**
     * Return ACL for user and specific map
     * @param mixed $query
     * @param mixed $name
     * @param mixed $map_id
     * @return mixed
     */
    public function scopeAclNamedUserNamedIdNamedType( $query, $name, $id ) {

        return $query->where( [ ['name', '=', $name ] ,
                                ['imageable_id', '=', $id ] ,
                                ['imageable_type' , '=', 'Maps' ] ] );
    }

    /**
     * Return ACL for user and specific object
     * @param mixed $query
     * @param mixed $name
     * @param mixed $object_type
     * @param mixed $id
     * @return mixed
     */
    public function scopeByUserByObject( $query, $name, $object_type, $id ) {

        return $query->where( [ ['name', '=', $name ] ,
                                ['imageable_id', '=', $id ] ,
                                ['imageable_type' , '=', $object_type ] ] );
    }

    /**
     * Return ACL for user and any object
     * @param mixed $query
     * @param mixed $name
     * @param mixed $object_type
     * @return mixed
     */
    public function scopeByUserAnyObjectOfType( $query, $name, $object_type ) {

        return $query->where( [ ['name', '=', $name ] ,
                                ['imageable_id', '=', 0 ] ,
                                ['imageable_type' , '=', $object_type ] ] );
    }

    /**
     * Return the ACL for any user for any node
     * @param mixed $query
     * @param mixed $id
     * @return mixed
     */
    public function scopeAnyUserAnyNode( $query ) {
        return $query->where( [ ['name', '=', '*' ] ,
                                ['imageable_id', '=', 0 ] ,
                                ['imageable_type' , '=', 'Nodes' ] ] );
    }

    /**
     * Return the ACL for any user for any map
     * @param mixed $query
     * @param mixed $id
     * @return mixed
     */
    public function scopeAnyUserAnyMap( $query ) {
        return $query->where( [ ['name', '=', '*' ] ,
                                ['imageable_id', '=', 0 ] ,
                                ['imageable_type' , '=', 'Maps' ]
                              ]);
    }

    /**
     * Return ACL for user and specific node
     * @param mixed $query
     * @param mixed $name
     * @param mixed $node_id
     * @return mixed
     */
    public function scopeByUserByNode( $query, $name, $node_id ) {

        return $query->where( [ ['name', '=', $name ] ,
                                ['imageable_id', '=', $node_id ] ,
                                ['imageable_type' , '=', 'Nodes' ] ] );
    }

    /**
     * Retrieves ACL's for all maps for user
     * @param mixed $query
     * @param mixed $name
     * @return mixed
     */
    public function scopeMapsByUser( $query, $name ) {

        return $query->where( [ ['name', '=', $name ],
                                ['imageable_type', '=', 'Maps' ] ] )
                     ->orderBy( 'id' );
    }

}