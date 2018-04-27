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

class Models_Assessments_AssessmentTarget extends Models_Base {
    protected $atarget_id, $dassessment_id, $adistribution_id, $target_type, $target_value, $delegation_list_id, $associated_schedules, $created_date, $created_by, $updated_date, $updated_by, $deleted_date, $deleted_by, $deleted_reason_id, $deleted_reason_notes, $visible, $task_type;
    protected static $table_name = "cbl_distribution_assessment_targets";
    protected static $primary_key = "atarget_id";
    protected static $default_sort_column = "atarget_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->atarget_id;
    }

    public function getDassessmentID() {
        return $this->dassessment_id;
    }

    public function getADistributionID() {
        return $this->adistribution_id;
    }

    public function getTargetType() {
        return $this->target_type;
    }

    public function getTaskType() {
        return $this->task_type;
    }

    public function getTargetValue() {
        return $this->target_value;
    }

    public function getDelegationListID() {
        return $this->delegation_list_id;
    }

    public function getAssociatedSchedules() {
        return $this->associated_schedules;
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

    public function getDeletedBy() {
        return $this->deleted_by;
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

    public function setUpdatedDate ($date) {
        $this->updated_date = $date;
    }

    public function setUpdatedBy ($id) {
        $this->updated_by = $id;
    }

    public function setDeletedBy ($id) {
        $this->deleted_by = $id;
    }

    public function setDeletedDate ($deleted_date) {
        $this->deleted_date = $deleted_date;
    }

    public function setDeletedReasonID ($deleted_reason_id) {
        $this->deleted_reason_id = $deleted_reason_id;
    }

    public function setDeletedReasonNotes ($deleted_reason_notes) {
        $this->deleted_reason_notes = $deleted_reason_notes;
    }

    public function setVisible ($visible) {
        $this->visible = $visible;
    }

    public static function fetchRowByID($atarget_id, $deleted_date = null, $ignore_deleted_date = false) {
        $self = new self();
        $constraints = array(
            array("key" => "atarget_id", "value" => $atarget_id, "method" => "=")
        );
        if (!$ignore_deleted_date) {
            $constraints [] = array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"));
        }
        return $self->fetchRow($constraints);
    }

    public static function fetchAllByDistributionIDTargetTypeTargetValueAssessmentID ($distribution_id = null, $target_type = "proxy_id", $target_value = null, $dassessment_id = null, $deleted_date = null) {
        $self = new self();
        return $self->fetchAll(array(
                array("key" => "adistribution_id", "method" => "=", "value" => $distribution_id),
                array("key" => "target_type", "method" => "=", "value" => $target_type),
                array("key" => "target_value", "method" => "=", "value" => $target_value),
                array("key" => "dassessment_id", "method" => "=", "value" => $dassessment_id),
                array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
            )
        );
    }

	public static function fetchRowByDistributionIDTargetValue ($distribution_id = null, $target_value = null, $deleted_date = null) {
		$self = new self();
		$fetch = array(
			array("key" => "adistribution_id", "method" => "=", "value" => $distribution_id),
			array("key" => "target_value", "method" => "=", "value" => $target_value),
			array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
		);
		return $self->fetchRow($fetch);
	}

	public static function fetchAllByDistributionIDAssessmentID ($distribution_id = null, $assessment_id = null, $deleted_date = null) {
		$self = new self();
		$fetch = array(
			array("key" => "adistribution_id", "method" => "=", "value" => $distribution_id),
			array("key" => "dassessment_id", "method" => "=", "value" => $assessment_id),
			array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
		);
		return $self->fetchAll($fetch);
	}

    public static function fetchAllByDistributionID ($distribution_id = null, $deleted_date = null, $use_delegation_list_id = false) {
        $self = new self();
        $fetch = array(
            array("key" => "adistribution_id", "method" => "=", "value" => $distribution_id),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        );
        if ($use_delegation_list_id) {
            $fetch[] = array("key" => "delegation_list_id", "method" => "IS NOT", "value" => null);
        }
        return $self->fetchAll($fetch);
    }

    public static function fetchAllByDassessmentID ($dassessment_id, $delegation_list_id = NULL, $deleted_date = NULL) {
        $self = new self();
        $fetch_params = array();
        $fetch_params[] = array("key" => "dassessment_id", "method" => "=", "value" => $dassessment_id);
        if ($delegation_list_id) {
            $fetch_params[] = array("key" => "delegation_list_id", "method" => ($delegation_list_id ? "=" : "IS NOT"), "value" => ($delegation_list_id ? $delegation_list_id : NULL));
        }
        $fetch_params[] = array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"));

        return $self->fetchAll($fetch_params);
    }

    public static function fetchAllByDassessmentIDIncludeDeleted($dassessment_id) {
        $self = new self();
        $fetch_params = array();
        $fetch_params[] = array("key" => "dassessment_id", "method" => "=", "value" => $dassessment_id);
        return $self->fetchAll($fetch_params);
    }

    public static function fetchRowByTarget_value ($target_value = null, $deleted_date = null) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "target_value", "method" => "=", "value" => $target_value),
                array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
            )
        );
    }

    public static function fetchRowByDAssessmentIDTargetTypeTargetValue ($dassessment_id = null, $target_type = null, $target_value = null, $deleted_date = null) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "dassessment_id", "method" => "=", "value" => $dassessment_id),
                array("key" => "target_value", "method" => "=", "value" => $target_value),
                array("key" => "target_type", "method" => "=", "value" => $target_type),
                array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
            )
        );
    }

    public static function fetchRowByDAssessmentIDTargetTypeTargetValueIncludeDeleted ($dassessment_id = null, $target_type = null, $target_value = null) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "dassessment_id", "method" => "=", "value" => $dassessment_id),
                array("key" => "target_value", "method" => "=", "value" => $target_value),
                array("key" => "target_type", "method" => "=", "value" => $target_type)
            )
        );
    }

    public function getTargetName() {
        global $db;
        $name = "N/A";
        $query = "SELECT `firstname`, `lastname` FROM `" . AUTH_DATABASE . "`.`user_data` WHERE id = ?";

        $result = $db->GetRow($query, array($this->gettargetValue()));
        if ($result) {
            $name = $result["firstname"] . " " . $result["lastname"];
        }

        return $name;
    }

    public function getTargetEmail() {
        global $db;
        $email = "N/A";
        $query = "SELECT `email` FROM `" . AUTH_DATABASE . "`.`user_data` WHERE id = ?";

        $result = $db->GetRow($query, array($this->gettargetValue()));
        if ($result) {
            $email = $result["email"];
        }

        return $email;
    }

    public static function fetchAllByDistributionIDTargetTypeTargetValue ($distribution_id = null, $target_type = "proxy_id", $target_value = null, $deleted_date = null) {
        $self = new self();
        return $self->fetchAll(array(
                array("key" => "adistribution_id", "method" => "=", "value" => $distribution_id),
                array("key" => "target_type", "method" => "=", "value" => $target_type),
                array("key" => "target_value", "method" => "=", "value" => $target_value),
                array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
            )
        );
    }

    public static function getAllByTargetValue($target_id, $group_by_distribution = true) {
        global $db;
        $assessment_targets = array();

        $GROUP_BY_distribution = "";
        if ($group_by_distribution) {
            $GROUP_BY_distribution = " GROUP BY `adistribution_id`";
        }

        $query = "SELECT * FROM `cbl_distribution_assessment_targets` 
                  WHERE `target_value` = ? 
                  $GROUP_BY_distribution ";

        $results = $db->GetAll($query, array($target_id));
        if ($results) {
            foreach ($results as $result) {
                $assessment_targets[] = new self($result);
            }
        }

        return $assessment_targets;
    }

    public static function fetchAllPendingTasksByTargetTypeTargetValueSortDeliveryDateRotationDatesDesc($target_type, $target_value, $current_section = "assessments", $filters = array(), $search_value = null, $start_date = null, $end_date = null, $limit = 0, $offset = 0) {
        global $db;

        $assesments = array();
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
                $AND_course_in = "  AND b.`course_id` IN (" . implode(",", array_keys($filters["program"])) . ") ";
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

        $query = "          SELECT a.*, b.*, d.* FROM `cbl_distribution_assessment_targets` AS a
                            JOIN `cbl_assessment_distributions` AS b
                            ON a.`adistribution_id` = b.`adistribution_id`                            
                            JOIN `courses` AS c
                            ON b.`course_id` = c.`course_id` 
                            JOIN `cbl_distribution_assessments` AS d
                            ON d.`dassessment_id` = a.`dassessment_id`
                            LEFT JOIN `cbl_assessment_progress` AS e 
                            ON (e.`dassessment_id` = a.`dassessment_id` AND e.`target_type` = a.`target_type` AND e.`target_record_id` = a.`target_value`)
                            WHERE e.`aprogress_id` IS NULL
                            AND a.`target_type` = ?
                            AND a.`target_value` = ?
                            AND a.`deleted_date` IS NULL
                            AND b.`deleted_date` IS NULL
                            AND d.`deleted_date` IS NULL
                            AND b.`visibility_status` = 'visible'
                            $AND_course_in
                            $AND_course_filter_in
                            $AND_cperiod_in
                            $AND_title_like
                            $AND_date_greater
                            $AND_date_less

                            UNION
                            
                            SELECT a.*, b.*, d.* FROM `cbl_distribution_assessment_targets` AS a
                            JOIN `cbl_assessment_distributions` AS b
                            ON a.`adistribution_id` = b.`adistribution_id`                            
                            JOIN `courses` AS c
                            ON b.`course_id` = c.`course_id` 
                            JOIN `cbl_distribution_assessments` AS d
                            ON d.`dassessment_id` = a.`dassessment_id`
                            JOIN `cbl_assessment_progress` AS e 
                            ON (e.`dassessment_id` = a.`dassessment_id` AND e.`target_type` = a.`target_type` AND e.`target_record_id` = a.`target_value`)
                            WHERE e.`progress_value` = 'inprogress'
                            AND e.`target_type` = ?
                            AND e.`target_record_id` = ?
                            AND a.`target_type` = ?
                            AND a.`target_value` = ?
                            AND a.`deleted_date` IS NULL
                            AND b.`deleted_date` IS NULL
                            AND d.`deleted_date` IS NULL
                            AND b.`visibility_status` = 'visible'
                            $AND_course_in
                            $AND_course_filter_in
                            $AND_cperiod_in
                            $AND_title_like
                            $AND_date_greater
                            $AND_date_less                            
                              
                            ORDER BY 66 DESC,
                            67 DESC,
                            68 DESC  
                            
                            $LIMIT $OFFSET
                            ";

        // The order by columns correspond to delivery_date, rotation_start_date, rotation_end_date
        $results = $db->GetAll($query, array($target_type, $target_value, $target_type, $target_value, $target_type, $target_value));

        if ($results) {
            foreach ($results as $task) {

                $assessor_name = false;
                $assessor_group = false;
                $assessor_role = false;
                if ($task["assessor_type"] == "external") {
                    $assessor_details = Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($task["assessor_value"]);
                    $assessor_group = "external";
                } else {
                    $assessor_details = Models_User::fetchRowByID($task["assessor_value"]);
                    $access = Models_User_Access::fetchAllByUserIDOrganisationID($task["assessor_value"], $task["organisation_id"]);
                    if ($access) {
                        $assessor_group = end($access)->getGroup();
                        $assessor_role = end($access)->getRole();
                    }
                }

                if ($assessor_details) {
                    $assessor_name = $assessor_details->getFirstname() . " " . $assessor_details->getLastName();
                }

                $schedule_details = false;
                if ($task["associated_record_type"] == "schedule_id" && $task["associated_record_id"]) {
                    $schedule = Models_Schedule::fetchRowByID($task["associated_record_id"]);
                    $schedule_details = Entrada_Assessments_Base::getConcatenatedBlockString(($task["dassessment_id"] ? $task["dassessment_id"] : false), ($schedule ? $schedule : false), $task["start_date"], $task["end_date"], $task["organisation_id"]);
                }

                $task_data = new Entrada_Utilities_Assessments_DeprecatedAssessmentTask(array(
                    "dassessment_id" => $task["dassessment_id"],
                    "published" => $task["published"],
                    "type" => "assessment",
                    "title" => $task["title"],
                    "schedule_details" => ($schedule_details ? $schedule_details : ""),
                    "url" => false,
                    "target_id" => $target_value,
                    "assessor" => $assessor_name,
                    "assessor_type" => $task["assessor_type"],
                    "assessor_value" => $task["assessor_value"],
                    "start_date" => $task["start_date"],
                    "end_date" => $task["end_date"],
                    "rotation_start_date" => $task["rotation_start_date"],
                    "rotation_end_date" => $task["rotation_end_date"],
                    "delivery_date" => $task["delivery_date"],
                    "adistribution_id" => $task["adistribution_id"],
                    "group" => $assessor_group,
                    "role" => $assessor_role ? str_replace("_", " ",$assessor_role) : "",
                    "target_info" => array(array("atarget_id" => $task["atarget_id"])),
                ));

                $assesments[] = $task_data;
            }
        }

        return $assesments;
    }

    public static function fetchCountDeletedTargets($dassessment_id) {
        global $db;
        $sql = "SELECT COUNT(*) AS cnt FROM `cbl_distribution_assessment_targets` WHERE `dassessment_id` = ? AND `deleted_date` IS NOT NULL";
        $res =  $db->GetOne($sql, array($dassessment_id));
        return $res;
    }

    /**
     * Fetch all users with tasks completed on them that are proxy_id targets for a distribution reviewer.
     *
     * @param $reviewer_id
     * @param $organisation_id
     * @param null $course_ids
     * @param null $start_date
     * @param null $end_date
     * @param null $search_value
     * @param null $limit
     * @param null $offset
     * @return mixed
     */
    public function fetchAllProxyIDTargetsForDistributionReviewer($reviewer_id, $organisation_id, $course_ids = null, $start_date = null, $end_date = null, $search_value = null, $limit = null, $offset = null) {
        global $db;

        $query = "  SELECT ud.*, ud.`id` AS `proxy_id`, CONCAT(ud.`firstname`, ' ', ud.`lastname`) AS `fullname`
                    FROM `cbl_assessment_distribution_reviewers` AS dr 
                    JOIN `cbl_assessment_distributions` AS ad 
                    ON ad.`adistribution_id` = dr.`adistribution_id` 
                    JOIN `courses` AS c 
                    ON c.`course_id` = ad.`course_id`
                    JOIN `cbl_assessment_progress` AS ap
                    ON ap.`adistribution_id` = ad.`adistribution_id`
                    JOIN `cbl_distribution_assessments` AS da 
                    ON da.`adistribution_id` = ad.`adistribution_id`
                    JOIN `cbl_distribution_assessment_targets` AS at 
                    ON at.`dassessment_id` = da.`dassessment_id`
                    JOIN `".AUTH_DATABASE."`.`user_data` AS ud 
                    ON ud.`id` = at.`target_value` AND at.`target_type` = 'proxy_id'
                    WHERE dr.`proxy_id` = ?
                    AND ad.`organisation_id` = ?
                    AND ap.`progress_value` = 'complete'
                    AND dr.`deleted_date` IS NULL
                    AND ap.`deleted_date` IS NULL";


        $search_value = clean_input($search_value, array("trim", "striptags"));
        if ($search_value) {
            $query .= " AND (`fullname` LIKE (". $db->qstr("%". $search_value ."%") .") OR ud.`email` LIKE (". $db->qstr("%". $search_value ."%") ."))";
        }

        $start_date = clean_input($start_date, array("int"));
        if ($start_date) {
            $query .= " AND da.`delivery_date` >= " . $db->qstr($start_date);
        }

        $end_date = clean_input($end_date, array("int"));
        if ($end_date) {
            $query .= " AND da.`delivery_date` <= " . $db->qstr($end_date);
        }

        $courses_string = Entrada_Utilities::sanitizeArrayAndImplode($course_ids, array("int"));
        if ($courses_string) {
            $query .= " AND (da.`course_id` IN ({$courses_string}) OR ad.`course_id` IN ({$courses_string}))";
        }

        $query .= " GROUP BY at.`target_type`, at.`target_value`
                    ORDER BY `fullname` ASC";

        if (!empty($limit)) {
            $query .= " LIMIT $limit";
        }
        if (!empty($offset)) {
            $query .= " OFFSET $offset";
        }

        return $db->GetAll($query, array($reviewer_id, $organisation_id));
    }

}