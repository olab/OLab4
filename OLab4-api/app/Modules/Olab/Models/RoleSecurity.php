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
use Entrada\Modules\Olab\Models\SecurityBase;

/**
 * @property integer $id
 * @property string $group_role_name
 * @property integer $imageable_id
 * @property string $imageable_type
 * @property string $acl
 */

class RoleSecurity extends SecurityBase {

    protected $table = 'security_roles';

    /**
     * Return the ACL for named role on any id of any object type
     * @param mixed $query
     * @param mixed $object_type
     * @return mixed
     */
    public function scopeAclNamedRoleAnyIdAnyType( $query, $group_role_name ) {
        return $query->where( [ ['name', '=', $group_role_name ] ,
                                ['imageable_id', '=', 0 ] ,
                                ['imageable_type' , '=', "*" ] ] );
    }

    /**
     * Return the ACL for any role on any id of named object type
     * @param mixed $query
     * @param mixed $object_type
     * @return mixed
     */
    public function scopeAclAnyRoleAnyIdNamedType( $query, $object_type ) {
        return $query->where( [ ['name', '=', "*" ] ,
                                ['imageable_id', '=', 0 ] ,
                                ['imageable_type' , '=', $object_type ] ] );
    }

    /**
     * Return the ACL for named role of any id of named object type
     * @param mixed $query
     * @param mixed $group_role_name
     * @param mixed $object_type
     * @return mixed
     */
    public function scopeAclNamedRoleAnyIdNamedType( $query, $group_role_name, $object_type ) {

        return $query->where( [ ['name', '=', $group_role_name ] ,
                                ['imageable_id', '=', 0 ] ,
                                ['imageable_type' , '=', $object_type ] ] );
    }

    /**
     * Retrieves ACL's for specific role on named instances of a type of object
     * @param mixed $query
     * @param mixed $group_role_name
     * @param mixed $object_type
     * @return mixed
     */
    public function scopeAclNamedRoleNamedType( $query, $group_role_name, $object_type ) {

        return $query->where( [ ['name', '=', $group_role_name ],
                                ['imageable_id', '<>', 0 ],
                                ['imageable_type', '=', $object_type ] ] )
                     ->orderBy( 'imageable_id' );
    }    

}