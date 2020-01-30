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
 * This model handles assessment plan forms
 *
 * @author Organisation: Queens University
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2018 Queens University. All Rights Reserved.
 */
class Models_Assessments_Plan_Form extends Models_Base {
    protected $assessment_plan_form_id, $assessment_plan_id, $form_id, $minimum_assessments, $iresponse_id, $minimum_assessors, $created_date, $created_by, $update_date, $updated_by, $deleted_date;
    protected static $table_name = "cbl_assessment_plan_forms";
    protected static $primary_key = "assessment_plan_form_id";
    protected static $default_sort_column = "assessment_plan_form_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->assessment_plan_form_id;
    }

    public function getAssessmentPlanID() {
        return $this->assessment_plan_id;
    }

    public function getFormID() {
        return $this->form_id;
    }

    public function getMinimumAssessments() {
        return $this->minimum_assessments;
    }

    public function getIresponseID() {
        return $this->iresponse_id;
    }

    public function getMinimumAssessors() {
        return $this->minimum_assessors;
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

    public function fetchAllByAssessmentPlanID($assessment_plan_id = 0, $deleted_date = null) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "assessment_plan_id", "value" => $assessment_plan_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public function fetchRowByAssessmentPlanIDFormID($assessment_plan_id = 0, $form_id = 0, $deleted_date = null) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "assessment_plan_id", "value" => $assessment_plan_id, "method" => "="),
            array("key" => "form_id", "value" => $form_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }
}