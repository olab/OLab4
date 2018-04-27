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
 * A model for handling assessor's permissions
 *
 * @author Organisation: Queen's University
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Assessor_Role_Permissions extends Models_Base {
    protected $role_permission_id, $role_id, $aftype_id, $created_date, $created_by, $updated_date, $updated_by, $deleted_date, $deleted_by;

    protected static $table_name = "cbl_assessor_role_permissions";
    protected static $primary_key = "role_permission_id";
    protected static $default_sort_column = "role_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->role_permission_id;
    }

    public function getRolePermissionID() {
        return $this->role_permission_id;
    }

    public function getRoleID() {
        return $this->role_id;
    }

    public function getAftypeID() {
        return $this->aftype_id;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function getDeletedBy() {
        return $this->deleted_by;
    }

    public static function fetchRowByID($role_permission_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "role_permission_id", "value" => $role_permission_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "role_permission_id", "value" => 0, "method" => ">=")));
    }
}