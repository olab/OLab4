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
 * A model for handling Communities
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2014 Regents of The University of California. All Rights Reserved.
 */

class Models_Community_Acl extends Models_Base {
    protected   $id,
                $resource_type,
                $resource_value,
                $create,
                $read,
                $update,
                $delete,
                $assertion;

    protected static $table_name = "community_acl";
    protected static $default_sort_column = "id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->id;
    }

    public function getResourceType() {
        return $this->resource_type;
    }

    public function getResourceValue() {
        return $this->resource_value;
    }

    public function getCreate() {
        return $this->create;
    }

    public function getRead() {
        return $this->read;
    }

    public function getUpdate() {
        return $this->update;
    }

    public function getDelete() {
        return $this->delete;
    }

    public function getAssertion() {
        return $this->assertion;
    }

    public function insert() {
        global $db;

        if ($db->AutoExecute($this->table_name, $this->toArray(), "INSERT")) {
            return $this;
        } else {
            return false;
        }
    }

    public static function fetchRowByTypeValue($resource_type, $resource_value) {
        $self = new self();

        $constraints = array(
            array(
                "key"       => "resource_type",
                "value"     => $resource_type,
                "method"    => "="
            ),
            array(
                "key"       => "resource_value",
                "value"     => $resource_value,
                "method"    => "="
            )
        );
        $row = $self->fetchRow($constraints);
        if ($row) {
            return $row;
        }
        return false;
    }
}