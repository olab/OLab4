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
 * A model for handling gradebook assessment options
 *
 * @author Organisation: Queen's University
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Gradebook_Assessment_Option extends Models_Base {
    protected $aoption_id, $assessment_id, $option_id, $option_active;

    protected static $table_name = "assessment_options";
    protected static $default_sort_column = "assessment_id";
    protected static $primary_key = "aoption_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->aoption_id;
    }

    public function getAoptionID() {
        return $this->aoption_id;
    }

    public function getAssessmentID() {
        return $this->assessment_id;
    }

    public function getOptionID() {
        return $this->option_id;
    }

    public function getOptionActive() {
        return $this->option_active;
    }

    public static function fetchRowByID($aoption_id, $option_active) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "aoption_id", "value" => $aoption_id, "method" => "="),
            array("key" => "option_active", "value" => $option_active, "method" => "=")
        ));
    }

    public static function fetchAllByAssessmentID($assessment_id, $option_active = null) {
        $self = new self();

        $active_query = $option_active ? array("key" => "option_active", "value" => $option_active, "method" => "=") : null;

        return $self->fetchAll(array(
            array("key" => "assessment_id", "value" => $assessment_id, "method" => "="),
            $active_query
        ));
    }

    public function insert() {
        global $db;

        if ($db->AutoExecute(static::$table_name, $this->toArray(), "INSERT")) {
            $this->aoption_id = $db->Insert_ID();
            return $this;
        } else {
            return false;
        }

    }

    public function update() {
        global $db;

        if ($db->AutoExecute(static::$table_name, $this->toArray(), "UPDATE", "`aoption_id` = ".$this->aoption_id)) {
            return $this;
        } else {
            return false;
        }

    }
}