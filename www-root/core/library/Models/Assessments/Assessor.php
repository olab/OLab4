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
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Assessor extends Models_Base {
    protected $dassessment_id, $adistribution_id, $assessor_type, $assessor_value, $audience_value;
    protected $number_submitted, $min_submittable, $max_submittable, $published;
    protected $start_date, $end_date, $delivery_date, $rotation_start_date, $rotation_end_date;
    protected $external_hash, $additional_assessment, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;
    protected $associated_record_id, $associated_record_type;

    protected static $table_name = "cbl_distribution_assessments";
    protected static $primary_key = "dassessment_id";
    protected static $default_sort_column = "dassessment_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->dassessment_id;
    }

    public function getADistributionID() {
        return $this->adistribution_id;
    }

    public function getAssociatedRecordType() {
        return $this->associated_record_type;
    }

    public function getAssociatedRecordID() {
        return $this->associated_record_id;
    }

    public function getAssessorType() {
        return $this->assessor_type;
    }

    public function getAssessorValue() {
        return $this->assessor_value;
    }

    public function getNumberSubmitted() {
        return $this->number_submitted;
    }
    
    public function getMinSubmittable() {
        return $this->min_submittable;
    }
    
    public function getMaxSubmittable() {
        return $this->max_submittable;
    }

    public function getPublished() {
        return $this->published;
    }

    public function getStartDate() {
        return $this->start_date;
    }

    public function getEndDate() {
        return $this->end_date;
    }

    public function getDeliveryDate() {
        return $this->delivery_date;
    }

    public function getRotationStartDate() {
        return $this->rotation_start_date;
    }

    public function getRotationEndDate() {
        return $this->rotation_end_date;
    }

    public function getExternalHash() {
        return $this->external_hash;
    }

    public function getAdditionalAssessment() {
        return $this->additional_assessment;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;
        return $this;
    }

    public function setUpdatedDate($date) {
        $this->updated_date = $date;
    }

    public function setUpdatedBy($id) {
        $this->updated_by = $id;
    }

    public static function fetchRowByID ($dassessment_id, $deleted_date = NULL, $ignore_deleted = false) {
        $self = new self();
        $fetch = array(
            array("key" => "dassessment_id", "method" => "=", "value" => $dassessment_id)
        );
        if (!$ignore_deleted) {
            $fetch[] = array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"));
        }
        return $self->fetchRow($fetch);
    }

    public static function fetchAllByDistributionID ($adistribution_id, $published = NULL, $deleted_date = NULL, $start_date = null, $end_date = null) {
        $self = new self();
        $constraints = array(
            array("key" => "adistribution_id", "method" => "=", "value" => $adistribution_id),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        );

        if ($published || $published === false) {
            $constraints[] = array("key" => "published", "value" => ($published ? "1" : "0"), "method" => "=");
        }

        if (!is_null($start_date)) {
            $constraints[] = array("key" => "delivery_date", "value" => $start_date, "method" => ">=");
        }

        if (!is_null($end_date)) {
            $constraints[] = array("key" => "delivery_date", "value" => $end_date, "method" => "<=");
        }

        return $self->fetchAll($constraints);
    }

    /**
     * Fetch all by distribution ID, regardless of deleted/published or anything else.
     *
     * @param $adistribution_id
     * @return array
     */
    public static function fetchAllRecordsByDistributionID ($adistribution_id) {
        $self = new self();
        $constraints = array(
            array("key" => "adistribution_id", "method" => "=", "value" => $adistribution_id),
        );
        return $self->fetchAll($constraints);
    }

    public static function fetchAllByDeliveryDateRange($delivery_start, $delivery_end, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "delivery_date", "method" => ">=", "value" => $delivery_start),
            array("key" => "delivery_date", "method" => "<=", "value" => $delivery_end),
            array("key" => "published", "method" => "=", "value" => 1),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ),
            "=",
            "AND",
            "delivery_date",
            "DESC"
        );

    }

    public static function fetchAllByAssessorValue($assessor_value, $current_section = "assessments", $is_external = false, $filters = array(), $search_value = null, $start_date = null, $end_date = null, $limit = 0, $offset = 0) {
        global $db;

        $assessments = false;

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
            $AND_date_greater = "   AND a.`delivery_date` >= ". $db->qstr($start_date) . "";
        }

        if ($end_date != "" && $end_date != null) {
            $AND_date_less = "      AND a.`delivery_date` <= ". $db->qstr($end_date) . "";
        }

        if ($limit) {
            $LIMIT = " LIMIT $limit";
        }

        if ($offset) {
            $OFFSET = " OFFSET $offset";
        }

        $assessor_type = ($is_external) ? 'external' : 'internal';

        $query = "          SELECT a.* FROM `cbl_distribution_assessments` AS a
                            JOIN `cbl_assessment_distributions` AS b
                            ON a.`adistribution_id` = b.`adistribution_id`                  
                            JOIN `courses` AS c
                            ON b.`course_id` = c.`course_id`                        
                            WHERE a.`assessor_value` = ?
                            AND a.`assessor_type` = ?
                            AND a.`published` = 1          
                            AND a.`deleted_date` IS NULL
                            AND b.`deleted_date` IS NULL
                            
                            $AND_course_in
                            $AND_course_filter_in
                            $AND_cperiod_in
                            $AND_title_like
                            $AND_date_greater
                            $AND_date_less
                            
                            ORDER BY a.`delivery_date` DESC, b.`title` ASC
                            $LIMIT $OFFSET
                            ";

        $results = $db->GetAll($query, array($assessor_value, $assessor_type));

        if ($results) {
            foreach ($results as $result) {
                $assessments[] = new self($result);
            }
        }

        return $assessments;
    }
    
    public static function fetchCompleteByProxyID($proxy_id = NULL) {
        global $db;
        $assessor_records = false;
        
        $query = "  SELECT a.`adistribution_id`, b.* FROM `cbl_assessment_distributions` AS a
                    JOIN `cbl_distribution_assessors` AS b
                    ON a.`adistribution_id` = b.`adistribution_id`
                    WHERE a.`deleted_date` IS NULL
                    AND b.`proxy_id` = ?
                    AND b.`number_submitted` > 0
                    AND b.`deleted_date` IS NULL";
        
        $results = $db->GetAll($query, array($proxy_id));
        if ($results) {
            foreach ($results as $assessor_record) {
                $assessor = new self(
                    array(
                        "dassessment_id"    => $assessor_record["dassessment_id"],
                        "adistribution_id"  => $assessor_record["adistribution_id"],
                        "proxy_id"          => $assessor_record["proxy_id"],
                        "number_submitted"  => $assessor_record["number_submitted"],
                        "start_date"        => $assessor_record["start_date"],
                        "end_date"          => $assessor_record["end_date"],
                        "deleted_date"      => $assessor_record["deleted_date"]
                    )
                );
                
                $assessor_records[] = $assessor;
            }
        }
        return $assessor_records;
    }

    public static function fetchRowByADistributionIDAssessorTypeAssessorValueDeliveryDate ($distribution_id, $assessor_type, $assessor_value, $delivery_date) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "adistribution_id", "method" => "=", "value" => $distribution_id),
                array("key" => "assessor_type", "method" => "=", "value" => $assessor_type),
                array("key" => "assessor_value", "method" => "=", "value" => $assessor_value),
                array("key" => "delivery_date", "method" => "=", "value" => $delivery_date)
            )
        );
    }

    public static function fetchRowByADistributionIDAssessorTypeAssessorValueDeliveryDateAssociatedRecordIDAssociatedRecordType ($distribution_id, $assessor_type, $assessor_value, $delivery_date, $assoc_id, $assoc_type) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "adistribution_id", "method" => "=", "value" => $distribution_id),
                array("key" => "assessor_type", "method" => "=", "value" => $assessor_type),
                array("key" => "assessor_value", "method" => "=", "value" => $assessor_value),
                array("key" => "delivery_date", "method" => "=", "value" => $delivery_date),
                array("key" => "associated_record_type", "method" => "=", "value" => $assoc_type),
                array("key" => "associated_record_id", "method" => "=", "value" => $assoc_id),
            )
        );
    }

    public static function fetchRowByAssessorTypeAssessorValueEndDate ($assessor_type, $assessor_value, $distribution_id, $end_date) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "adistribution_id", "method" => "=", "value" => $distribution_id),
                array("key" => "assessor_type", "method" => "=", "value" => $assessor_type),
                array("key" => "assessor_value", "method" => "=", "value" => $assessor_value),
                array("key" => "end_date", "method" => "=", "value" => $end_date)
            )
        );
    }

    public static function fetchRowByADistributionIDAssessorTypeAssessorValueStartDate ($distribution_id, $assessor_type, $assessor_value, $start_date) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "adistribution_id", "method" => "=", "value" => $distribution_id),
                array("key" => "assessor_type", "method" => "=", "value" => $assessor_type),
                array("key" => "assessor_value", "method" => "=", "value" => $assessor_value),
                array("key" => "start_date", "method" => "=", "value" => $start_date)
            )
        );
    }

    public static function fetchRowByADistributionIDAssessorTypeAssessorValueStartDateEndDate ($distribution_id, $assessor_type, $assessor_value, $start_date, $end_date) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "adistribution_id", "method" => "=", "value" => $distribution_id),
                array("key" => "assessor_type", "method" => "=", "value" => $assessor_type),
                array("key" => "assessor_value", "method" => "=", "value" => $assessor_value),
                array("key" => "start_date", "method" => "=", "value" => $start_date),
                array("key" => "end_date", "method" => "=", "value" => $end_date),
                array("key" => "deleted_date", "value" => NULL, "method" => "IS")
            )
        );
    }

    public static function fetchRowByAssessorTypeAssessorValueStartDateEndDate ($assessor_type, $assessor_value, $distribution_id, $start_date, $end_date) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "adistribution_id", "method" => "=", "value" => $distribution_id),
                array("key" => "assessor_type", "method" => "=", "value" => $assessor_type),
                array("key" => "assessor_value", "method" => "=", "value" => $assessor_value),
                array("key" => "start_date", "method" => "=", "value" => $start_date),
                array("key" => "end_date", "method" => "=", "value" => $end_date)
            )
        );
    }

    public static function fetchAllByDistributionIDAssessorTypeAssessorValue ($distribution_id, $assessor_type, $assessor_value, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "adistribution_id", "method" => "=", "value" => $distribution_id),
            array("key" => "assessor_type", "method" => "=", "value" => $assessor_type),
            array("key" => "assessor_value", "method" => "=", "value" => $assessor_value),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ),
            "=",
            "AND",
            "end_date",
            "DESC"
        );
    }

    public static function fetchRowByADistributionIDDeliveryDate ($adistribution_id, $delivery_date, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "adistribution_id", "method" => "=", "value" => $adistribution_id),
                array("key" => "delivery_date", "method" => "=", "value" => $delivery_date),
                array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
            )
        );
    }

    public static function fetchAllNotCreatedByProxyID ($proxy_id) {
        $self = new self();
        return $self->fetchAll(array(
                array("key" => "created_by", "method" => "!=", "value" => $proxy_id)
            )
        );
    }

    public function getOverallProgressDetails($proxy_id, $external = false) {
        $distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($this->getADistributionID());

        $output = array();
        $output["delegator"] = Models_Assessments_Distribution_Delegator::fetchRowByDistributionID($this->getADistributionID());

        if ($output["delegator"]) {
            $output["targets"] = Models_Assessments_Distribution_Target::getAssessmentTargets($this->getADistributionID(), $this->getID(), $proxy_id, $proxy_id, $external);
        } else {
            $output["targets"] = Models_Assessments_Distribution_Target::getAssessmentTargets($this->getADistributionID(), $this->getID(), null, $proxy_id, $external);
        }

        $output["max_overall_attempts"] = 0;
        $output["overall_attempts_completed"] = 0;
        $output["max_individual_attempts"] = 0;
        $output["targets_pending"] = 0;
        $output["targets_inprogress"] = 0;
        $output["targets_complete"] = 0;

        if ($output["targets"]) {
            if ($distribution->getSubmittableByTarget()) {
                $output["max_individual_attempts"] = $distribution->getMaxSubmittable();
                $output["max_overall_attempts"] = ($output["max_individual_attempts"] * (@count($output["targets"])));
            } else {
                if ($distribution->getRepeatTargets()) {
                    $output["max_individual_attempts"] = $distribution->getMaxSubmittable();
                } else {
                    $output["max_individual_attempts"] = 1;
                }
                $output["max_overall_attempts"] = $distribution->getMaxSubmittable();
            }

            foreach ($output["targets"] as $target) {
                $output["overall_attempts_completed"] += (isset($target["completed_attempts"]) && $target["completed_attempts"] ? $target["completed_attempts"] : 0);
                if (array_key_exists("progress", $target) && is_array($target["progress"]) && in_array("pending", $target["progress"])) {
                    if (!in_array("inprogress", $target["progress"]) && (!isset($target["completed_attempts"]) || !$target["completed_attempts"] || $output["max_individual_attempts"] > $target["completed_attempts"])) {
                        $output["targets_pending"]++;
                    }
                }
                if (array_key_exists("progress", $target) &&  is_array($target["progress"]) && in_array("inprogress", $target["progress"])) {
                    $output["targets_inprogress"]++;
                }
                if (array_key_exists("progress", $target) &&  is_array($target["progress"]) && in_array("complete", $target["progress"])) {
                    $output["targets_complete"]++;
                }
            }
        }
        return $output;
    }

    public static function fetchAllByAssessorTypeAssessorValueSyncDate ($assessor_type, $assessor_value, $published = true, $sync_date = null, $deleted_date = NULL) {
        global $db;
        $assessments = false;

        if ($published) {
            $published_flag = "1";
        } else {
            $published_flag = "0";
        }

        $query = "  SELECT * FROM `". static::$table_name ."` WHERE `assessor_type` = ? AND `assessor_value` = ? AND `published` = ? AND (`created_date` > ? OR `updated_date` > ?) AND `deleted_date` IS NULL ORDER BY end_date DESC";
        $results = $db->GetAll($query, array($assessor_type, $assessor_value, $published_flag, $sync_date, $sync_date));
        if ($results) {
            foreach ($results as $result) {
                $assessments[] = new self($result);
            }
        }

        return $assessments;
    }

    public static function fetchAllPreviousAssessments ($assessor_type, $assessor_value, $published = true, $sync_date = null, $deleted_date = NULL) {
        global $db;
        $assessments = false;

        if ($published) {
            $published_flag = "1";
        } else {
            $published_flag = "0";
        }

        $query = "  SELECT * FROM `". static::$table_name ."` WHERE `assessor_type` = ? AND `assessor_value` = ? AND `published` = ? AND `created_date` < ? AND `deleted_date` IS NULL ORDER BY end_date DESC";
        $results = $db->GetAll($query, array($assessor_type, $assessor_value, $published_flag, $sync_date));
        if ($results) {
            foreach ($results as $result) {
                $assessments[] = new self($result);
            }
        }

        return $assessments;
    }

    /**
     * Find the assessment IDs associated with any of the forms given.
     * This returns all of the dassessment_ids for any assessment that uses any of the form_ids specified.
     *
     * @param array $form_ids
     * @return bool|array
     */
    public static function fetchDassessmentIDsByFormIDs($form_ids) {
        global $db;
        $assessment_ids = array();
        $clean_form_ids = array_map(function($v){ return clean_input($v, array("trim", "int")); }, $form_ids);
        if (!is_array($clean_form_ids) || empty($clean_form_ids)) {
            return false;
        }
        $ids_string = implode(',', $clean_form_ids);
        $sql = "SELECT    DISTINCT(a.`dassessment_id`)
                FROM      `cbl_distribution_assessments` AS a
                LEFT JOIN `cbl_assessment_distributions` d ON a.`adistribution_id` = d.`adistribution_id`
                WHERE     d.`form_id` IN({$ids_string})
                AND       a.`deleted_date` IS NULL";
        $assessments = $db->GetAll($sql);
        if (is_array($assessments)) {
            foreach ($assessments as $assessment) {
                $assessment_ids[] = $assessment["dassessment_id"];
            }
        }
        return $assessment_ids;
    }
}