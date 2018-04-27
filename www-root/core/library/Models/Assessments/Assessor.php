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
    protected $start_date, $end_date, $delivery_date, $encounter_date, $rotation_start_date, $rotation_end_date, $expiry_date, $expiry_notification_date;

    protected $external_hash, $additional_assessment, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;
    protected $forwarded_from_assessment_id, $forwarded_date, $forwarded_by;
    protected $associated_record_id, $associated_record_type, $form_id, $course_id, $assessment_type_id, $assessment_method_id, $assessment_method_data, $target_viewable, $feedback_required, $organisation_id;

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

    public function getFormID() {
        return $this->form_id;
    }

    public function getCourseID() {
        return $this->course_id;
    }

    public function getAssessmentTypeID() {
        return $this->assessment_type_id;
    }

    public function getAssessmentMethodID() {
        return $this->assessment_method_id;
    }

    public function getAssessmentMethodData() {
        return $this->assessment_method_data;
    }

    public function getOrganisationID() {
        return $this->organisation_id;
    }

    public function getFeedbackRequired() {
        return $this->feedback_required;
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

    public function getDateOfEncounter() {
        return $this->encounter_date;
    }

    public function setDateOfEncounter($encounter_date) {
        $this->encounter_date = $encounter_date;
    }

    public function getRotationStartDate() {
        return $this->rotation_start_date;
    }

    public function getRotationEndDate() {
        return $this->rotation_end_date;
    }

    public function getExpiryDate() {
        return $this->expiry_date;
    }

    public function getNotificationExpiryDate() {
        return $this->expiry_notification_date;
    }

    /**
     * @param mixed $expiry_date
     */
    public function setExpiryDate($expiry_date) {
        $this->expiry_date = $expiry_date;
    }

    /**
     * @param mixed $expiry_notification_date
     */
    public function setExpiryNotificationDate($expiry_notification_date) {
        $this->expiry_notification_date = $expiry_notification_date;
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

    public function getForwardedFromAssessmentID() {
         return $this->forwarded_from_assessment_id;
    }

    public function getForwardedDate() {
        return $this->forwarded_date;
    }

    public function getForwardedBy() {
        return $this->forwarded_by;
    }

    public static function fetchRowByID ($dassessment_id, $deleted_date = NULL, $ignore_deleted_date_field = false) {
        $self = new self();
        $fetch = array(
            array("key" => "dassessment_id", "method" => "=", "value" => $dassessment_id)
        );
        if (!$ignore_deleted_date_field) {
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

    public static function fetchAllByAssessorValue($assessor_value, $current_section = "assessments", $is_external = false, $filters = array(), $search_value = null, $start_date = null, $end_date = null, $exclude_completed, $limit = 0, $offset = 0) {
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

        $query = "  SELECT a.* FROM `cbl_distribution_assessments` AS a
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
                    AND b.`visibility_status` = 'visible'
                    
                    ORDER BY a.`delivery_date` DESC, a.`rotation_start_date` DESC, a.`rotation_end_date` DESC
                    $LIMIT $OFFSET
                    ";

        $results = $db->GetAll($query, array($assessor_value, $assessor_type));

        $query = "  SELECT a.* FROM `cbl_distribution_assessments` AS a
                    JOIN `cbl_assessment_lu_methods` AS b
                    ON a.`assessment_method_id` = b.`assessment_method_id`
                    LEFT JOIN `cbl_assessment_progress` AS c
                    ON a.`dassessment_id` = c.`dassessment_id`
                    WHERE a.`assessor_value` = ?
                    AND a.`assessor_type` = 'internal'
                    AND a.`deleted_date` IS NULL
                    AND b.`deleted_date` IS NULL
                    AND c.`deleted_date` IS NULL
                    AND (
                      b.`shortname` = 'complete_and_confirm_by_email' 
                      OR b.`shortname` = 'complete_and_confirm_by_pin' 
                      OR b.`shortname` = 'send_blank_form' 
                      OR b.`shortname` = 'double_blind_assessment'
                      OR b.`shortname` = 'faculty_triggered_assessment')";
        if ($exclude_completed) {
            $query .= " AND (c.`progress_value` != 'complete' OR c.`progress_value` IS NULL)";
        }

        $cbme_results = $db->GetAll($query, array($assessor_value));
        if ($cbme_results) {
            foreach ($cbme_results as $cbme_result) {
                $assessments[] = new self($cbme_result);
            }
        }

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

    public static function fetchRowByADistributionIDAssessorTypeAssessorValueAssociatedRecordIDAssociatedRecordType ($distribution_id, $assessor_type, $assessor_value, $assoc_id, $assoc_type) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "adistribution_id", "method" => "=", "value" => $distribution_id),
                array("key" => "assessor_type", "method" => "=", "value" => $assessor_type),
                array("key" => "assessor_value", "method" => "=", "value" => $assessor_value),
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

        if ($output["delegator"] && $this->getADistributionID()) {
            $output["targets"] = Models_Assessments_Distribution_Target::getAssessmentTargets($this->getADistributionID(), $this->getID(), $proxy_id, $proxy_id, $external);
        } else if ($this->getADistributionID()) {
            $output["targets"] = Models_Assessments_Distribution_Target::getAssessmentTargets($this->getADistributionID(), $this->getID(), null, $proxy_id, $external);
        } else {
            // No distribution
            $output["targets"] = Models_Assessments_Distribution_Target::getAssessmentTargets(null, $this->getID(), null, $proxy_id, $external, null, null, true);
        }

        $output["max_overall_attempts"] = 0;
        $output["overall_attempts_completed"] = 0;
        $output["max_individual_attempts"] = 0;
        $output["targets_pending"] = 0;
        $output["targets_inprogress"] = 0;
        $output["targets_complete"] = 0;

        if ($output["targets"]) {
            if ($this->getADistributionID()) {
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
            } else {
                $output["max_overall_attempts"] = $output["max_individual_attempts"] = $this->getMaxSubmittable();
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
     * Takes deleted_dates into account.
     *
     * A form ID is considered in use by an assessment when:
     *  - The distribution_assessment record's form_id = the given form ID
     *  - The distribution_assessment record is not deleted
     *
     * Note: The targets are not taken into account in this query.
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
        $sql = "
            SELECT DISTINCT(da.`dassessment_id`)
            FROM `cbl_distribution_assessments` AS da
            WHERE da.`form_id` IN ({$ids_string})
            AND da.`deleted_date` IS NULL";

        $assessments = $db->GetAll($sql);
        if (is_array($assessments)) {
            foreach ($assessments as $assessment) {
                $assessment_ids[] = $assessment["dassessment_id"];
            }
        }
        return $assessment_ids;
    }

    public static function fetchAllAdditionalTasksForDistributions() {
        global $db;
        $query = "SELECT *
                  FROM `cbl_distribution_assessments` AS a
                  JOIN `cbl_assessment_distributions` AS d ON d.`adistribution_id` = a.`adistribution_id`
                  WHERE a.`additional_assessment` = 1
                  AND d.`adistribution_id` IS NOT NULL
                  AND d.`deleted_date` IS NULL
                  AND a.`deleted_date` IS NULL
                  AND d.`visibility_status` = 'visible'
        ";
        $results = $db->GetAll($query);
        if (empty($results)) {
            return array();
        }
        return $results;

    }

    /**
     * Fetch all assessments that were forwarded from the specified assessment ID.
     *
     * @param int $dassessment_id
     * @param int $deleted_date
     * @return array
     */
    public static function fetchAllRecordsForwardedFromAssessmentID($dassessment_id, $deleted_date = null) {
        $self = new self();
        $constraints = array(
            array("key" => "forwarded_from_assessment_id", "method" => "=", "value" => $dassessment_id),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        );
        return $self->fetchAll($constraints);
    }

    /**
     * Fetch the relevant data for assessments that were forwarded for the given assessment ID.
     *
     * @param $dassessment_id
     * @return mixed
     */
    public static function fetchForwardedAssessmentData($dassessment_id) {
        global $db;
        $query = "SELECT dassessment_id, assessor_value, assessor_type, forwarded_date, forwarded_by 
                  FROM `cbl_distribution_assessments`AS a 
                  WHERE a.`forwarded_from_assessment_id` = ? 
                  AND a.`deleted_date` IS NULL";
        return $db->GetAll($query, array($dassessment_id));
    }

    /**
     * Fetch a list of assessments for a list of distributions, optionally filtered by the associated record types.
     * This is useful for fetching all learning events that were evaluated, for example.
     *
     * @param array $adistribution_ids
     * @param null|string $associated_record_type
     *
     * @return bool|array
     */
    public static function fetchAllByADistributionIDsAssociatedRecordType($adistribution_ids = array(), $associated_record_type = null) {
        if (!$adistribution_ids || empty($adistribution_ids)) {
            return false;
        }

        global $db;
        $DISTRIBUTION_ID_LIST = implode(",", $adistribution_ids);

        $AND_associated_record_type = "";
        if ($associated_record_type) {
            $AND_associated_record_type = " AND `associated_record_type` = '{$associated_record_type}'";
        }

        $query = "SELECT * FROM `cbl_distribution_assessments`
                  WHERE `adistribution_id` IN ($DISTRIBUTION_ID_LIST)
                  $AND_associated_record_type
                  AND `deleted_date` IS NULL";
        $results = $db->GetAll($query);

        return $results;
    }

    /**
     * Fetch all assessments by form_id
     * @param $form_id
     * @param null $published
     * @param null $deleted_date
     * @param null $start_date
     * @param null $end_date
     * @return array
     */
    public function fetchAllByFormID ($form_id, $published = NULL, $deleted_date = NULL, $start_date = null, $end_date = null) {
        $self = new self();
        $constraints = array(
            array("key" => "form_id", "method" => "=", "value" => $form_id),
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
     * Fetch all tasks that have expired on or before the provided timestamp
     *
     * @param int $timestamp
     * @param int|null $deleted_date
     *
     * @return bool|array
     */
    public function fetchAllExpiredTasks($timestamp, $deleted_date = null) {
        $self = new self();
        $constraints = array(
            array("key" => "expiry_date", "method" => "<=", "value" => $timestamp),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        );
        return $self->fetchAll($constraints);
    }

    public function fetchAllByExpiryNotificationDate($timestamp, $deleted_date = null) {
        $self = new self();
        $constraints = array(
            array("key" => "expiry_notification_date", "method" => "<=", "value" => $timestamp),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        );
        return $self->fetchAll($constraints);
    }

    public static function fetchAllByDistributionIDAssociatedRecordTypeAssociatedRecordID($adistribution_id, $associated_record_type, $associated_record_id){
        $constraints = array(
            array("key" => "adistribution_id", "method" => "=", "value" => $adistribution_id),
            array("key" => "associated_record_type", "method" => "=", "value" => $associated_record_type),
            array("key" => "associated_record_id", "method" => "=", "value" => $associated_record_id),
        );

        return self::fetchAll($constraints);
    }

    /**
     * Fetch tasks completed progress records, optionally limiting to a list of courses,
     * associated records, and dates.
     *
     * @param null $search_value
     * @param null $course_ids
     * @param null $form_ids
     * @param null $start_date
     * @param null $end_date
     * @param null $associated_record_type
     * @param null $associated_record_ids
     * @param null $assessment_type_ids
     * @return mixed
     */
    public function fetchAllWithCompletedProgressByFormIDsCourseIDsAssociatedRecords($search_value = null, $course_ids = null, $form_ids = null, $start_date = null, $end_date = null, $associated_record_type = null, $associated_record_ids = null, $assessment_type_ids = null, $distributionless = false) {
        global $db;

        $constraints = array();

        $query = "  SELECT da.* 
                    FROM `cbl_distribution_assessments` AS da
                    JOIN `cbl_assessment_progress` AS ap
                    ON ap.`dassessment_id` = da.`dassessment_id`
                    WHERE ap.`progress_value` = 'complete'
                    AND ap.`deleted_date` IS NULL";

        $search_value = clean_input($search_value, array("trim", "striptags"));
        if ($search_value) {
            $query .= " AND (af.`title` LIKE (". $db->qstr("%". $search_value ."%") .") OR af.`description` LIKE (". $db->qstr("%". $search_value ."%") ."))";
        }

        $start_date = clean_input($start_date, array("int"));
        if ($start_date) {
            $query .= " AND da.`delivery_date` >= ?";
            $constraints[] = $start_date;
        }

        $end_date = clean_input($end_date, array("int"));
        if ($end_date) {
            $query .= " AND da.`delivery_date` <= ?";
            $constraints[] = $end_date;
        }

        $courses_string = Entrada_Utilities::sanitizeArrayAndImplode($course_ids, array("int"));
        if ($courses_string) {
            $query .= " AND da.`course_id` IN ({$courses_string})";
        }

        $forms_string = Entrada_Utilities::sanitizeArrayAndImplode($form_ids, array("int"));
        if ($forms_string) {
            $query .= " AND da.`form_id` IN ({$forms_string})";
        }

        $associated_record_type = clean_input($associated_record_type, array("trim", "striptags"));
        if ($associated_record_type) {
            $query .= " AND da.`associated_record_type` = " . $db->qstr($associated_record_type);
        }

        $associated_record_ids_string = Entrada_Utilities::sanitizeArrayAndImplode($associated_record_ids, array("int"));
        if ($associated_record_type && $associated_record_ids_string) {
            $query .= " AND (da.`associated_record_type` = " . $db->qstr($associated_record_type) . " AND ap.`associated_record_record_id` IN ({$associated_record_ids_string}))";
        }

        $assessment_types_string = Entrada_Utilities::sanitizeArrayAndImplode($assessment_type_ids, array("int"));
        if ($assessment_types_string) {
            $query .= " AND da.`assessment_type_id` IN ({$assessment_types_string})";
        }

        if ($distributionless) {
            $query .= " AND da.`adistribution_id` IS NULL";
        }

        return $db->GetAll($query, $constraints);
    }
}