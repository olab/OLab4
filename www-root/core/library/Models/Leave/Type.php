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
 * A model to handle leave types for leave tracking
 *
 * @author Organisation: Queen's University
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 */

class Models_Leave_Type extends Models_Base {
    protected $type_id, $type_value, $updated_date, $updated_by, $created_date, $created_by, $deleted_date;

    protected static $table_name = "cbl_lu_leave_tracking_types";
    protected static $primary_key = "type_id";
    protected static $default_sort_column = "type_value";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->type_id;
    }

    public function getTypeValue() {
        return $this->type_value;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public static function fetchRowByID($type_id, $created_by = NULL, $deleted_date = NULL) {
        $self = new self();

        $params = array(
            array("key" => "type_id", "value" => $type_id, "method" => "="),
            array("key" => "deleted_date", "value" => $deleted_date, "method" => "IS")
        );

        if (!is_null($created_by)) {
            $params[] = array("key" => "created_by", "value" => $created_by, "method" => "=");
        }

        return $self->fetchRow($params);
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "type_id", "value" => 0, "method" => ">=")), "=", "AND", "type_value");
    }
}