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

class Models_Community_Acl_Group extends Models_Base {
    protected   $id,
                $cgroup_id,
                $resource_type,
                $resource_value,
                $create,
                $read,
                $update,
                $delete;

    protected static $table_name = "community_acl_groups";
    protected static $default_sort_column = "id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->id;
    }

    public function getCGroupID() {
        return $this->cgroup_id;
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

    public function insert() {
        global $db;

        if ($db->AutoExecute($this->table_name, $this->toArray(), "INSERT")) {
            return $this;
        } else {
            return false;
        }
    }

    public static function fetchAllByTypeValue($resource_type, $resource_value) {
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

        $objs = $self->fetchAll($constraints, "=", "AND", $sort_col, $sort_order);
        $output = array();

        if (!empty($objs)) {
            foreach ($objs as $o) {
                $output[] = $o;
            }
        }

        return $output;
    }

    public static function fetchRowByTypeValueCGroup($resource_type, $resource_value, $cgroup_id) {
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
            ),
            array(
                "key"       => "cgroup_id",
                "value"     => $cgroup_id,
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
?>