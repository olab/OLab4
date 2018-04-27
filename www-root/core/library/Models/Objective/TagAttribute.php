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
 * A model for handling Objective Audiences
 *
 * @author Organisation: Queen's University
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Objective_TagAttribute extends Models_Base {
    protected $otag_attribute_id, $objective_set_id, $target_objective_set_id, $updated_date, $updated_by;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "objective_tag_attributes";
    protected static $primary_key = "otag_attribute_id";
    protected static $default_sort_column = "objective_set_id";

    public function getID() {
        return $this->otag_attribute_id;
    }

    public function getOtagAttributeId() {
        return $this->otag_attribute_id;
    }

    public function getObjectiveSetId()
    {
        return $this->objective_set_id;
    }

    public function getTargetObjectiveSetId()
    {
        return $this->target_objective_set_id;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;
    }

    public static function fetchAllByObjectiveSetID($objective_set_id) {
        $self = new self();
        return $self->fetchAll(array(
                array("key" => "objective_set_id", "value" => $objective_set_id, "method" => "=")
            )
        );
    }

    public static function fetchRowByObjectiveSetIdTargetObjectiveSetID($objective_set_id, $target_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "objective_set_id", "method" => "=", "value" => $objective_set_id),
            array("key" => "target_objective_set_id", "method" => "=", "value" => $target_id),
        ));
    }

    public function delete() {
        global $db;
        if ($db->Execute("DELETE FROM `".static::$table_name."` WHERE `".static::$primary_key."` = ".$this->getID())) {
            return $this;
        } else {
            application_log("error", "Error deleting  ".get_called_class()." id[" . $this->{static::$primary_key} . "]. DB Said: " . $db->ErrorMsg());
            return false;
        }
    }
}