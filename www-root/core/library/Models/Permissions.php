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
 * A model for handling course contacts.
 *
 * @author Organisation: Queen's University
 * @author Developer: Eugene Bivol <ebivol@gmail.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Permissions extends Models_Base {
    protected $permission_id, $assigned_by, $assigned_to, $valid_from, $valid_until;

    protected static $table_name = "permissions";
    protected static $primary_key = "permission_id";
    protected static $default_sort_column = "permission_id";


    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->permission_id;
    }

    public function getAssignedBy() {
        return $this->assigned_by;
    }

    public function getAssignedTo() {
        return $this->assigned_to;
    }

    public function getValidFrom() {
        return $this->valid_from;
    }

    public function getValidUntil() {
        return $this->valid_until;
    }

    public function fetchRowByID($id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "permission_id", "value" => $id, "method" => "=")
        ));
    }

    public function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "permission_id", "value" => 0, "method" => ">=")));
    }

    public static function fetchOneByAssignedByAssignedTo ($assigned_by, $assigned_to) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "assigned_by", "value" => $assigned_by, "method" => "="),
            array("key" => "assigned_to", "value" => $assigned_to, "method" => "=")
        ));
    }

}