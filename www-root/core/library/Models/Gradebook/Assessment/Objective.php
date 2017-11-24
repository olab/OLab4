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
 * A model for handling objectives associated with gradebook assessments.
 *
 * @author Organisation: Queen's University
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Gradebook_Assessment_Objective extends Models_Base {
    protected $aobjective_id, $assessment_id, $objective_id, $importance, $objective_details, $objective_type, $updated_date, $updated_by;

    protected static $table_name = "assessment_objectives";
    protected static $default_sort_column = "assessment_id";
    protected static $primary_key = "aobjective_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->aobjective_id;
    }

    public function getAobjectiveID() {
        return $this->aobjective_id;
    }

    public function getAssessmentID() {
        return $this->assessment_id;
    }

    public function getObjectiveID() {
        return $this->objective_id;
    }

    public function getImportance() {
        return $this->importance;
    }

    public function getObjectiveDetails() {
        return $this->objective_details;
    }

    public function getObjectiveType() {
        return $this->objective_type;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public static function fetchRowByID($aobjective_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "aobjective_id", "value" => $aobjective_id, "method" => "=")
        ));
    }

    public static function fetchAllByAssessmentID($assessment_id) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "assessment_id", "value" => $assessment_id, "method" => "=")));
    }

    public function insert() {
        global $db;

        if ($db->AutoExecute(static::$table_name, $this->toArray(), "INSERT")) {
            $this->aobjective_id = $db->Insert_ID();
            return $this;
        } else {
            return false;
        }

    }

    public function update() {
        global $db;

        if ($db->AutoExecute(static::$table_name, $this->toArray(), "UPDATE", "`aobjective_id` = ".$this->aobjective_id)) {
            return $this;
        } else {
            return false;
        }

    }
}