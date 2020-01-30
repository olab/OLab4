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
class Models_Assessments_FutureTaskSnapshot extends Models_Base {
    protected   $future_task_id, $adistribution_id, $assessor_type, $assessor_value, $task_type,
                $target_type, $target_value, $title, $rotation_start_date, $rotation_end_date, $delivery_date,
                $schedule_details, $created_date, $created_by, $form_id, $assessment_type_id, $organisation_id,
                $associated_record_id, $associated_record_type, $min_submittable, $max_submittable,
                $feedback_required, $start_date, $end_date, $additional_assessment, $deleted_date, $deleted_by;

    protected static $table_name = "cbl_assessment_ss_future_tasks";
    protected static $primary_key = "future_task_id";
    protected static $default_sort_column = "future_task_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->future_task_id;
    }

    public function getDistributionID() {
        return $this->adistribution_id;
    }

    public function getAssessorType() {
        return $this->assessor_type;
    }

    public function getAssessorValue() {
        return $this->assessor_value;
    }

    public function getTaskType() {
        return $this->task_type;
    }

    public function getTargetType() {
        return $this->target_type;
    }

    public function getTargetValue() {
        return $this->target_value;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getRotationStartDate() {
        return $this->rotation_start_date;
    }

    public function getRotationEndDate() {
        return $this->rotation_end_date;
    }

    public function getDeliveryDate() {
        return $this->delivery_date;
    }

    public function getScheduleDetails() {
        return $this->schedule_details;
    }

    public function getTarget($target_value = null, $target_type = null) {
        if (!is_null($target_value) && !is_null($target_type)) {
            $this->target_value = $target_value;
            $this->target_type = $target_type;
        }

        if (!is_null($this->target_value) && !is_null($this->target_type)) {
            switch ($this->target_type) {
                case "proxy_id":
                    $member_details = Models_User::fetchRowByID($this->target_value);
                    if ($member_details) {
                        $prefix = $member_details->getPrefix();
                        $target_name = (($prefix) ? $prefix . " " : "") . $member_details->getFirstname() . " " . $member_details->getLastname();
                    } else {
                        $target_name = "N/A";
                    }
                    break;
                case "schedule_id":
                    $schedule = Models_Schedule::fetchRowByID($this->target_value);
                    if ($schedule) {
                        $target_name = $schedule->getTitle();
                    } else {
                        $target_name = "N/A";
                    }
                    break;
                case "course_id":
                    $course = Models_Course::fetchRowByID($this->target_value);
                    if ($course) {
                        $target_name = $course->getCourseName() . " (" . $course->getCourseCode() . ")";
                    } else {
                        $target_name = "N/A";
                    }
                    break;
                default:
                    $target_name = "N/A";
                    break;
            }

            return $target_name;
        } else {
            return false;
        }
    }

    public function getTargetGroup($organisation_id) {
        if ($this->getTargetValue()) {
            global $db;
            $query = "  SELECT `group`
                        FROM `" . AUTH_DATABASE . "`.`user_access`
                        WHERE `user_id` = ?
                        AND `organisation_id` = ?";
            $results = $db->GetRow($query, array($this->getTargetValue(), $organisation_id));
            if ($results) {
                return $results["group"];
            }
        }
        return false;
    }

    public function getTargetRole($organisation_id) {
        if ($this->getTargetValue()) {
            global $db;
            $query = "  SELECT `role`
                        FROM `" . AUTH_DATABASE . "`.`user_access`
                        WHERE `user_id` = ?
                        AND `organisation_id` = ?";
            $results = $db->GetRow($query, array($this->getTargetValue(), $organisation_id));
            if ($results) {
                return $results["role"];
            }
        }
        return false;
    }

    public function getAssessorGroup($organisation_id) {
        if ($this->getAssessorValue()) {
            global $db;
            $query = "  SELECT `group`
                        FROM `" . AUTH_DATABASE . "`.`user_access`
                        WHERE `user_id` = ?
                        AND `organisation_id` = ?";
            $results = $db->GetRow($query, array($this->getAssessorValue(), $organisation_id));
            if ($results) {
                return $results["group"];
            }
        }
        return false;
    }

    public function getAssessorRole($organisation_id) {
        if ($this->getAssessorValue()) {
            global $db;
            $query = "  SELECT `role`
                        FROM `" . AUTH_DATABASE . "`.`user_access`
                        WHERE `user_id` = ?
                        AND `organisation_id` = ?";
            $results = $db->GetRow($query, array($this->getAssessorValue(), $organisation_id));
            if ($results) {
                return $results["role"];
            }
        }
        return false;
    }

    public function setDeletedBy ($id) {
        $this->deleted_by = $id;
    }

    public function setDeletedDate ($deleted_date) {
        $this->deleted_date = $deleted_date;
    }

    public static function fetchRowByID($id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "future_task_id", "value" => $id, "method" => "=")
        ));
    }

    public static function fetchRowByDistributionIDAssessorTypeAssessorValueTargetTypeTargetValueDeliveryDate($distribution_id, $assessor_type, $assessor_value, $target_type, $target_value, $delivery_date, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "adistribution_id", "value" => $distribution_id, "method" => "="),
            array("key" => "assessor_type", "value" => $assessor_type, "method" => "="),
            array("key" => "assessor_value", "value" => $assessor_value, "method" => "="),
            array("key" => "target_type", "value" => $target_type, "method" => "="),
            array("key" => "target_value", "value" => $target_value, "method" => "="),
            array("key" => "delivery_date", "value" => $delivery_date, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllByAssessorTypeAssessorValueSortDeliveryDateRotationDatesDesc($assessor_value, $current_section = "assessments", $filters = array(), $search_value = null, $start_date = null, $end_date = null, $is_external = false, $limit = 0, $offset = 0) {
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
        $query = "  SELECT a.*, b.`assessment_type` 
                    FROM `cbl_assessment_ss_future_tasks` AS a
                    JOIN `cbl_assessment_distributions` AS b
                    ON a.`adistribution_id` = b.`adistribution_id`                    
                    JOIN `courses` AS c
                    ON b.`course_id` = c.`course_id`                            
                    WHERE a.`assessor_type` = ?
                    AND a.`assessor_value` = ?
                    AND a.`deleted_date` IS NULL
                    AND b.`deleted_date` IS NULL
                    AND b.`visibility_status` = 'visible'
                    
                    $AND_course_in
                    $AND_course_filter_in
                    $AND_cperiod_in
                    $AND_title_like
                    $AND_date_greater
                    $AND_date_less
                    
                    ORDER BY a.`delivery_date` DESC, 
                    a.`rotation_start_date` DESC,
                    a.`rotation_end_date` DESC
                    $LIMIT $OFFSET
                    ";

        $results = $db->GetAll($query, array($assessor_type, $assessor_value));
        if ($results) {
            foreach ($results as $result) {
                $assessments[] = new self($result);
            }
        }

        return $assessments;
    }

    public static function fetchAllByTargetTypeTargetValueSortDeliveryDateRotationDatesDesc($target_type, $target_value, $current_section = "assessments", $filters = array(), $search_value = null, $start_date = null, $end_date = null, $limit = 100, $offset = 0) {
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
            $AND_date_greater = "   AND b.`delivery_date` >= ". $db->qstr($start_date) . "";
        }

        if ($end_date != "" && $end_date != null) {
            $AND_date_less = "      AND b.`delivery_date` <= ". $db->qstr($end_date) . "";
        }

        if ($limit) {
            $LIMIT = " LIMIT $limit";
        }

        if ($offset) {
            $OFFSET = " OFFSET $offset";
        }

        $query = "  SELECT a.* 
                    FROM `cbl_assessment_ss_future_tasks` AS a
                    JOIN `cbl_assessment_distributions` AS b
                    ON a.`adistribution_id` = b.`adistribution_id`                            
                    JOIN `courses` AS c
                    ON b.`course_id` = c.`course_id`                         
                    WHERE a.`target_type` = ?
                    AND a.`target_value` = ?
                    AND a.`deleted_date` IS NULL
                    AND b.`deleted_date` IS NULL
                    AND b.`visibility_status` = 'visible'
                    
                    $AND_course_in
                    $AND_course_filter_in
                    $AND_cperiod_in
                    $AND_title_like
                    $AND_date_greater
                    $AND_date_less
                    
                    ORDER BY a.`delivery_date` DESC, 
                    a.`rotation_start_date` DESC,
                    a.`rotation_end_date` DESC
                    $LIMIT $OFFSET
                    ";

        $results = $db->GetAll($query, array($target_type, $target_value));
        if ($results) {
            foreach ($results as $result) {
                $assessments[] = new self($result);
            }
        }

        return $assessments;
    }

    public static function fetchAllFutureTasksForAssociatedLearnersAssociatedFaculty($task_type, $organisation_id, $offset = 0, $limit = 10, $count = false, $search_value = null, $start_date = null, $end_date = null) {
        global $db;
        $tasks = array();

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
            $AND_TITLE_LIKE = " AND b.`title` LIKE (". $db->qstr("%". $search_value ."%") .")";
        }

        $SELECT = " SELECT a.* ";
        if ($count) {
            $SELECT = " SELECT COUNT(*) ";
        }

        $USER_ID_LIST = Entrada_Utilities_Assessments_DeprecatedAssessmentTask::getAssociatedLearnerFacultyProxyList();
        $EXTERNAL_ID_LIST = Entrada_Utilities_Assessments_DeprecatedAssessmentTask::getAssociatedExternalIDList();
        $COURSE_ID_LIST = null;
        $AND_ASSESSOR = null;

        $active_user_course_id_list = Models_Course::getActiveUserCoursesIDList();
        if ($active_user_course_id_list) {
            $COURSE_ID_LIST = implode(",", $active_user_course_id_list);
        }

        if (!empty($USER_ID_LIST) && $USER_ID_LIST != "" && $COURSE_ID_LIST) {
            if (!empty($USER_ID_LIST) && $USER_ID_LIST != "" && !empty($EXTERNAL_ID_LIST) && $EXTERNAL_ID_LIST != "") {
                $AND_ASSESSOR = " AND ((a.`assessor_value` IN ({$USER_ID_LIST}) AND a.`assessor_type` = 'internal' OR a.`assessor_value` IN ({$EXTERNAL_ID_LIST}) AND a.`assessor_type` = 'external') OR a.`target_value` IN ({$USER_ID_LIST}))";
            } else {
                if (!empty($USER_ID_LIST) && $USER_ID_LIST != "") {
                    $AND_ASSESSOR = " AND ((a.`assessor_value` IN ({$USER_ID_LIST}) AND a.`assessor_type` = 'internal') OR (a.`target_type` = 'proxy_id' && a.`target_value` IN ({$USER_ID_LIST})))";
                }

                if (!empty($EXTERNAL_ID_LIST) && $EXTERNAL_ID_LIST != "") {
                    $AND_ASSESSOR = " AND ((a.`assessor_value` IN ({$EXTERNAL_ID_LIST}) AND a.`assessor_type` = 'external') OR (a.`target_type` = 'proxy_id' && a.`target_value` IN ({$USER_ID_LIST})))";
                }
            }

            $query = " 
                    $SELECT 
                    FROM `cbl_assessment_ss_future_tasks` AS a
                    JOIN `cbl_assessment_distributions` AS b
                    ON a.`adistribution_id` = b.`adistribution_id`                    
                    WHERE a.`deleted_date` IS NULL
                    AND a.`organisation_id` = ?
                    AND b.`deleted_date` IS NULL
                    AND b.`visibility_status` = 'visible'
                    AND b.`assessment_type` = '$task_type'
                    AND b.`course_id` IN ({$COURSE_ID_LIST})
                    $AND_ASSESSOR

                    $AND_TITLE_LIKE
                    $AND_date_greater
                    $AND_date_less
                    
                    ORDER BY a.`delivery_date` ASC
                    
                    $LIMIT $OFFSET
                    ";

            $results = $db->GetAll($query, array($organisation_id));

            if ($count) {
                return $results;
            } else if ($results) {
                foreach ($results as $result) {
                    $tasks[] = new self($result);
                }
            }
        }

        return $tasks;
    }

    public static function truncate() {
        global $db;
        $query = "TRUNCATE `" . static::$table_name . "`";
        if (!$db->Execute($query)) {
            application_log("error", "Unable to truncate " . static::$table_name . ". DB said: " . $db->ErrorMsg());
        }
    }

    public function delete() {
        global $db;

        $query = "DELETE FROM " . static::$table_name . " WHERE `future_task_id` = ?";
        $result = $db->Execute($query, array($this->getID()));

        return $result;
    }
}