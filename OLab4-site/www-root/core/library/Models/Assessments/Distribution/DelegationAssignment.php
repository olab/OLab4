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
 * @author Organisation: Queen's University
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Distribution_DelegationAssignment extends Models_Base {
    protected $addassignment_id, $addelegation_id, $adistribution_id, $dassessment_id, $delegator_id, $deleted_date, $deleted_reason_id, $deleted_reason, $assessor_type, $assessor_value, $target_type, $target_value, $created_date, $created_by, $updated_date, $updated_by;

    protected static $table_name = "cbl_assessment_distribution_delegation_assignments";
    protected static $primary_key = "addassignment_id";
    protected static $default_sort_column = "addassignment_id";

    public function __construct ($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID () {
        return $this->addassignment_id;
    }

    public function getDelegatorID () {
        return $this->delegator_id;
    }

    public function getDelegationID () {
        return $this->addelegation_id;
    }

    public function getAdistributionID () {
        return $this->adistribution_id;
    }

    public function getDassessmentID () {
        return $this->dassessment_id;
    }

    public function getAssessorType () {
        return $this->assessor_type;
    }

    public function getAssessorValue () {
        return $this->assessor_value;
    }

    public function getTargetType () {
        return $this->target_type;
    }

    public function getTargetValue () {
        return $this->target_value;
    }

    public function getCreatedDate () {
        return $this->created_date;
    }

    public function getCreatedBy () {
        return $this->created_by;
    }

    public function getUpdatedDate () {
        return $this->updated_date;
    }

    public function getUpdatedBy () {
        return $this->updated_by;
    }

    public function setUpdatedBy ($id) {
        $this->updated_by = $id;
    }

    public function setCreatedBy ($id) {
        $this->created_by = $id;
    }

    public function setUpdatedDate ($updated = null) {
        $this->updated_date = ($updated) ? $updated : time();
    }

    public function setCreatedDate () {
        $this->created_date = time();
    }

    public function setDeletedReasonID($id) {
        $this->deleted_reason_id = $id;
    }

    public function getDeletedReasonID() {
        return $this->deleted_reason_id;
    }

    public function getDeletedDate () {
        return $this->deleted_date;
    }

    public function setDeletedDate ($deleted_date = null) {
        $this->deleted_date = ($deleted_date === null) ? time() : $deleted_date;
    }

    public function getDeletedReason() {
        return $this->deleted_reason;
    }

    public function setDeletedReason ($deleted_reason) {
        $this->deleted_reason = $deleted_reason;
    }

    public static function fetchRowByID ($id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "addassignment_id", "value" => $id, "method" => "=")
        ));
    }

    public static function fetchRowByDistributionIDAssessmentIDAssessorValueTargetValue($distribution_id, $assessment_id, $assessor_value, $target_value, $delivery_date, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "adistribution_id", "value" => $distribution_id, "method" => "="),
            array("key" => "dassessment_id", "value" => $assessment_id, "method" => "="),
            array("key" => "assessor_value", "value" => $assessor_value, "method" => "="),
            array("key" => "target_value", "value" => $target_value, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllByDelegationID ($addelegation_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "addelegation_id", "value" => $addelegation_id, "method" => "="),
            array("key" => "deleted_date", "value" => null, "method" => "IS")
        ));
    }

    public static function fetchAllByDistributionID ($adistribution_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "="),
            array("key" => "deleted_date", "value" => null, "method" => "IS")
        ));
    }

    public static function fetchAllByDistributionIDIgnoreDeletedDate ($adistribution_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "=")
        ));
    }

    public static function getCountByAssessmentID ($assessment_id, $exclude_deleted = true) {
        global $db;
        $deleted_subq = ($exclude_deleted) ? "AND deleted_date IS NULL" : "";
        $query = "SELECT `dassessment_id` FROM " . static::$table_name . " WHERE `dassessment_id` = ? $deleted_subq";
        $result = $db->Execute($query, array($assessment_id));
        if ($result) {
            return $result->RecordCount();
        }
        return false;
    }

    public static function fetchAllByAssessmentID ($assessment_id, $assessor_type = null, $assessor_value = null, $target_type = null, $target_value = null, $addelegation_id = null) {
        $self = new self();

        $fetch = array(
            array("key" => "dassessment_id", "value" => $assessment_id, "method" => "="),
            array("key" => "deleted_date", "value" => null, "method" => "IS")
        );

        if ($assessor_value) {
            $fetch[] = array("key" => "assessor_value", "value" => $assessor_value, "method" => "=");
        }
        if ($assessor_type) {
            $fetch[] = array("key" => "assessor_type", "value" => $assessor_type, "method" => "=");
        }
        if ($target_type) {
            $fetch[] = array("key" => "target_type", "value" => $target_type, "method" => "=");
        }
        if ($target_value) {
            $fetch[] = array("key" => "target_value", "value" => $target_value, "method" => "=");
        }
        if ($addelegation_id) {
            $fetch[] = array("key" => "addelegation_id", "value" => $addelegation_id, "method" => "=");
        }

        return $self->fetchAll($fetch);

    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "addelegation_id", "value" => 0, "method" => ">=")));
    }

    public static function fetchAllByAddelegatorID ($proxy_id = null) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "addelegator_id", "value" => $proxy_id, "method" => "=")));
    }

    public static function fetchAllByTargetValue ($proxy_id, $current_section = "assessments", $filters = array(), $search_value = null, $start_date = null, $end_date = null, $limit = 0, $offset = 0) {
        global $db;
        $delegation_assignments = false;

        $course_id_list = Models_Course::getActiveUserCoursesIDList();

        $AND_course_in = ($current_section == "assessments" || empty($course_id_list) ? " " : "  AND b.`course_id` IN (" . implode(",", $course_id_list) . ") ");
        $AND_cperiod_in = "";
        $AND_course_filter_in = "";
        $AND_title_like = "";
        $AND_date_greater = "";
        $AND_date_less = "";
        $LIMIT = "";
        $OFFSET = "";

        if ($filters) {
            if (array_key_exists("cperiod", $filters)) {
                $AND_cperiod_in = " AND b.`cperiod_id` IN (" . implode(",", array_keys($filters["cperiod"])) . ") ";
            }

            if (array_key_exists("program", $filters)) {
                $AND_course_filter_in = "  AND b.`course_id` IN (" . implode(",", array_keys($filters["program"])) . ") ";
            }
        }

        if ($search_value != "" && $search_value != null) {
            $AND_title_like = "     AND b.`title` LIKE (". $db->qstr("%". $search_value ."%") .") ";
        }

        if ($start_date != "" && $start_date != null) {
            $AND_date_greater = "   AND d.`delivery_date` >= ". $db->qstr($start_date) . "";
        }

        if ($end_date != "" && $end_date != null) {
            $AND_date_less = "      AND d.`delivery_date` <= ". $db->qstr($end_date) . "";
        }

        if ($limit) {
            $LIMIT = " LIMIT $limit";
        }

        if ($offset) {
            $OFFSET = " OFFSET $offset";
        }

        $query = "          SELECT a.* FROM `cbl_assessment_distribution_delegation_assignments` AS a
                            JOIN `cbl_assessment_distributions` AS b
                            ON a.`adistribution_id` = b.`adistribution_id`                          
                            JOIN `courses` AS c
                            ON b.`course_id` = c.`course_id` 
                            JOIN `cbl_distribution_assessments` AS d
                            ON a.`dassessment_id` = d.`dassessment_id`
                            WHERE a.`target_value` = ?
                            AND a.`deleted_date` IS NULL
                            AND b.`deleted_date` IS NULL
                            
                            $AND_course_in
                            $AND_course_filter_in
                            $AND_cperiod_in
                            $AND_title_like
                            $AND_date_greater
                            $AND_date_less
                            
                            ORDER BY a.`created_date` DESC
                            $LIMIT $OFFSET
                            ";

        $results = $db->GetAll($query, $proxy_id);
        if ($results) {
            foreach ($results as $result) {
                $delegation_assignments[] = new self($result);
            }
        }

        return $delegation_assignments;
    }

    public function update() {
        global $db;

        return ($db->AutoExecute(static::$table_name, $this->toArray(), "UPDATE", "`addassignment_id` = ".$this->addassignment_id));
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