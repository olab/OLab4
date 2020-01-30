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

class Models_Objective_TagLevel extends Models_Base {
    protected $otag_level_id, $objective_set_id, $level, $label, $updated_date, $updated_by;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "objective_tag_levels";
    protected static $primary_key = "otag_level_id";
    protected static $default_sort_column = "objective_set_id";

    public function getID() {
        return $this->otag_level_id;
    }

    public function getOtagLevelId() {
        return $this->otag_level_id;
    }

    public function getObjectiveSetId() {
        return $this->objective_set_id;
    }

    public function getLevel() {
        return $this->level;
    }

    public function getLabel() {
        return $this->label;
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
                array("key" => "objective_set_id", "method" => "=", "value" => $objective_set_id)
            )
        );
    }

    public static function fetchRowByObjectiveSetIdLevel($objective_set_id, $level) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "objective_set_id", "method" => "=", "value" => $objective_set_id),
            array("key" => "level", "method" => "=", "value" => $level)
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