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
 * This model handles assessment plans
 *
 * @author Organisation: Queens University
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2018 Queens University. All Rights Reserved.
 */
class Models_Assessments_Plan extends Models_Base {
    protected $assessment_plan_id, $title, $description, $assessment_plan_container_id, $objective_id, $minimum_assessments, $valid_from, $valid_until, $published, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;
    protected static $table_name = "cbl_assessment_plans";
    protected static $primary_key = "assessment_plan_id";
    protected static $default_sort_column = "assessment_plan_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->assessment_plan_id;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getAssessmentPlanContainerID() {
        return $this->assessment_plan_container_id;
    }

    public function getObjectiveID() {
        return $this->objective_id;
    }

    public function getValidFrom() {
        return $this->valid_from;
    }

    public function getValidUntil() {
        return $this->valid_until;
    }

    public function getPublished() {
        return $this->published;
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

    public function fetchRowByObjectiveID($objective_id = 0, $deleted_date = null) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "objective_id", "value" => $objective_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }
}