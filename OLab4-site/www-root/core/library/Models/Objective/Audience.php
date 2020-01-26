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

class Models_Objective_Audience extends Models_Base {
    protected $oaudience_id, $objective_id, $organisation_id, $audience_type, $audience_value, $updated_date, $updated_by;

    protected static $table_name = "objective_audience";
    protected static $primary_key = "oaudience_id";
    protected static $default_sort_column = "oaudience_id";

    public function getID () {
        return $this->oaudience_id;
    }

    public function getObjectiveID () {
        return $this->objective_id;
    }

    public function getOrganisationID () {
        return $this->organisation_id;
    }

    public function getAudienceType () {
        return $this->audience_type;
    }

    public function getAudienceValue () {
        return $this->audience_value;
    }

    public function getUpdatedDate () {
        return $this->updated_date;
    }

    public function getUpdatedBy () {
        return $this->updated_by;
    }

    public static function fetchAllByObjectiveIDAudienceType($objective_id, $audience_type) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "objective_id", "value" => $objective_id, "method" => "="),
            array("key" => "audience_type", "value" => $audience_type, "method" => "=")
            )
        );
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