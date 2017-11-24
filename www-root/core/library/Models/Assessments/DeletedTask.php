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
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Assessments_DeletedTask extends Models_Base {
    protected $deleted_task_id, $adistribution_id, $target_id, $assessor_value, $assessor_type, $delivery_date, $deleted_reason_id, $deleted_reason_notes, $visible, $created_by, $created_date, $deleted_by, $deleted_date;

    protected static $table_name = "cbl_assessment_deleted_tasks";
    protected static $primary_key = "deleted_task_id";
    protected static $default_sort_column = "deleted_task_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->deleted_task_id;
    }

    public function getADistributionID() {
        return $this->adistribution_id;
    }

    public function getTargetID() {
        return $this->target_id;
    }

    public function getADAssessorID() {
        return $this->assessor_value;
    }

    public function getAssessorType() {
        return $this->assessor_type;
    }

    public function getAssessorValue() {
        return $this->assessor_value;
    }

    public function getDeliveryDate() {
        return $this->delivery_date;
    }

    public function getDeletedReasonID() {
        return $this->deleted_reason_id;
    }

    public function getDeletedReasonNotes() {
        return $this->deleted_reason_notes;
    }

    public function getVisible() {
        return $this->visible;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function getDeletedBy() {
        return $this->deleted_by;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public static function fetchRowByID($deleted_task_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "deleted_task_id", "value" => $deleted_task_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllByAdistributionID($adistribution_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }


    public static function fetchRowByADistributionIDAssessorTypeAssessorValueTargetIDDeliveryDate($adistribution_id, $assessor_type, $assessor_value, $target_id, $delivery_date, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "="),
            array("key" => "assessor_type", "value" => $assessor_type, "method" => "="),
            array("key" => "assessor_value", "value" => $assessor_value, "method" => "="),
            array("key" => "target_id", "value" => $target_id, "method" => "="),
            array("key" => "delivery_date", "value" => $delivery_date, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))));
    }

    public static function fetchAllByCreatedBy($proxy_id) {
        $self = new self();
        return $self->fetchAll(array(
           array("key" => "created_by", "value" => $proxy_id, "method" => "=")
        ));
    }

    public static function getAllDeletedTasksForAssociatedLearnersAssociatedFaculty($task_type, $offset = 0, $limit = 10, $count = false, $search_value = null, $start_date = null, $end_date = null) {
        global $db;

        $AND_date_greater = "";
        $AND_date_less = "";
        $LIMIT = "";
        $OFFSET = "";
        $AND_TITLE_LIKE = "";

        if ($start_date != "" && $start_date != null) {
            $AND_date_greater = "   AND b.`delivery_date` >= ". $db->qstr($start_date) . "";
        }

        if ($end_date != "" && $end_date != null) {
            $AND_date_less = "      AND b.`delivery_date` <= ". $db->qstr($end_date) . "";
        }

        if ($limit && !$count) {
            $LIMIT = " LIMIT $limit";
        }

        if ($offset && !$count) {
            $OFFSET = " OFFSET $offset";
        }

        if (!is_null($search_value) && $search_value != "") {
            $LIMIT = "";
            $OFFSET = "";
            $AND_TITLE_LIKE = " AND (b.`title` LIKE (". $db->qstr("%". $search_value ."%") .") OR a.`deleted_reason_notes` LIKE (". $db->qstr("%". $search_value ."%") .") OR d.`reason_details` LIKE (". $db->qstr("%". $search_value ."%") .") )";
        }

        $SELECT = " SELECT a.`deleted_task_id`, b.`title`, CONCAT(c.`firstname`, ' ', c.`lastname`) AS internal_full_name, CONCAT(f.`firstname`, ' ', f.`lastname`) AS external_full_name, a.`assessor_type`, a.`assessor_value`, a.`adistribution_id`, a.`deleted_reason_notes`, d.`reason_details`, a.`delivery_date` ";
        if ($count) {
            $SELECT = " SELECT COUNT(*) ";
        }

        $USER_ID_LIST = Entrada_Utilities_Assessments_AssessmentTask::getAssociatedLearnerFacultyProxyList();
        $EXTERNAL_ID_LIST = Entrada_Utilities_Assessments_AssessmentTask::getAssociatedExternalIDList();

        $COURSE_ID_LIST = null;
        $AND_ASSESSOR = null;

        $active_user_course_id_list = Models_Course::getActiveUserCoursesIDList();
        if ($active_user_course_id_list) {
            $COURSE_ID_LIST = implode(",", $active_user_course_id_list);
        }

        if (!empty($USER_ID_LIST) && $USER_ID_LIST != "" && !empty($EXTERNAL_ID_LIST) && $EXTERNAL_ID_LIST != "") {
            $AND_ASSESSOR = " AND ((a.`assessor_value` IN ({$USER_ID_LIST}) AND a.`assessor_type` = 'internal' OR a.`assessor_value` IN ({$EXTERNAL_ID_LIST}) AND a.`assessor_type` = 'external') OR a.`target_id` IN ({$USER_ID_LIST}))";
        } else {
            if (!empty($USER_ID_LIST) && $USER_ID_LIST != "") {
                $AND_ASSESSOR = " AND (a.`assessor_value` IN ({$USER_ID_LIST}) AND a.`assessor_type` = 'internal') OR a.`target_id` IN ({$USER_ID_LIST})";
            }

            if (!empty($EXTERNAL_ID_LIST) && $EXTERNAL_ID_LIST != "") {
                $AND_ASSESSOR = " AND (a.`assessor_value` IN ({$EXTERNAL_ID_LIST}) AND a.`assessor_type` = 'external') OR a.`target_id` IN ({$USER_ID_LIST})";
            }
        }

        // We can only reliably fetch proxy_id targets by their ID seeing as we don't have a target_type to check, so join on the distribution targets to make sure
        // it is not a course, group, schedule, etc.
        if ($AND_ASSESSOR && $COURSE_ID_LIST) {
            $query = "  
                $SELECT
                
                FROM `cbl_assessment_deleted_tasks` AS a
                JOIN `cbl_assessment_distributions` AS b
                ON a.`adistribution_id` = b.`adistribution_id`   
                LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS c
                ON a.`assessor_value` = c.`id`
                LEFT JOIN `cbl_external_assessors` AS f
                ON a.`assessor_value` = f.`eassessor_id` 
                JOIN `cbl_assessment_lu_task_deleted_reasons` AS d 
                ON a.`deleted_reason_id` = d.`reason_id`
                WHERE a.`adistribution_id` IN 
                (
                    SELECT e.`adistribution_id` FROM `cbl_assessment_distribution_targets` as e 
                    WHERE e.`adistribution_id` = a.`adistribution_id`     
                    AND NOT (e.`target_type` = 'schedule_id' AND e.`target_scope` = 'self')
                    AND NOT (e.`target_type` = 'course_id' AND e.`target_scope` = 'self')
                    AND NOT (e.`target_scope` = 'self' AND e.`target_role` = 'any')
                )
                AND b.`assessment_type` = '$task_type'
                AND a.`visible` = 1
                AND b.`visibility_status` = 'visible'
                AND b.`course_id` IN ({$COURSE_ID_LIST})
               
                $AND_ASSESSOR

                $AND_TITLE_LIKE
                $AND_date_greater
                $AND_date_less
               
                ORDER BY a.`created_date` DESC
                
                $LIMIT $OFFSET
                ";

            return $db->GetAll($query);
        }
        return array();
    }
}