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
 * A class to handle reporting on assessment tools feedbacks.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */
class Entrada_Utilities_Assessments_ToolsFeedbackReport extends Entrada_Utilities_Assessments_Base {
    // Class properties set at construction time.
    protected $tools = null,
              $start_date = null,
              $end_date = null,
              $courses = null,
              $limit_start,
              $limit;

    /**
     * Entrada_Utilities_Assessments_ToolsFeedbackReport constructor.
     *
     * @param null|array() $arr
     */
    public function __construct($arr = null) {
        parent::__construct($arr);

        $this->start_date = $this->getPreferenceFromSession("start_date");
        $this->end_date = ($this->getPreferenceFromSession("end_date") == "" ? 0 : (strtotime("23:59:59", $this->getPreferenceFromSession("end_date"))));
        $this->tools = $this->getPreferenceFromSession("tools");
        $this->courses = $this->getPreferenceFromSession("courses");
    }

    //--- Getters/Setters ---//
    public function getTools() {
        return $this->tools;
    }

    public function setTools($tools) {
        $this->tools = $tools;
    }

    public function getStartDate() {
        return $this->start_date;
    }

    public function setStartDate($start_date) {
        $this->start_date = $start_date;
    }

    public function getEndDate() {
        return $this->end_date;
    }

    public function setEndDate($end_date) {
        $this->end_date = $end_date;
    }

    public function setLimitStart($limit_start) {
        $this->limit_start = $limit_start;
    }

    public function getLimitStart() {
        return $this->limit_start;
    }

    public function setLimit($limit) {
        $this->limit = $limit;
    }

    public function getLimit() {
        return $this->limit;
    }

    //--- Public functionality ---//

    /**
     * Fetch a session-level preference setting. This is specific to the reporting summary being accessed from the assessments module.
     *
     * @param $specified_preference
     * @return null
     */
    public function getPreferenceFromSession($specified_preference) {
        if (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["assessment_tools_feedback_report"][$specified_preference])) {
            return $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["assessment_tools_feedback_report"][$specified_preference];
        } else {
            return null;
        }
    }

    public static function hasReportAccess(&$ENTRADA_ACL, &$ENTRADA_USER, $proxy_id, $role, $override_acl = null) {

        if ($override_acl !== null) {
            return $override_acl;

        } else if ($role == "learner" && ($ENTRADA_ACL->amIAllowed(new AcademicAdvisorResource($proxy_id), "read", true) || $ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true))) {
            return true;

        } else if ($role == "faculty") {
            $course_owner = false;
            $faculty_is_associated_with_course = false;

            // Check whether the person attempting to view the course is a PA/PD
            $courses = Models_Course::getUserCourses($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation());
            if (is_array($courses)) {
                foreach ($courses as $course) {
                    if (CourseOwnerAssertion::_checkCourseOwner($ENTRADA_USER->getActiveID(), $course->getID())) {
                        $course_owner = true;
                    }
                }
            }

            $admin = $ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true);

            // Check if the user is associated with the given facutly
            $assessment_user = new Entrada_Utilities_AssessmentUser();
            $associated_faculty = $assessment_user->getFaculty($ENTRADA_USER->getActiveId(), $ENTRADA_USER->getActiveOrganisation(), $admin, ENTRADA_URL);
            foreach ($associated_faculty as $faculty_person) {
                if ($faculty_person->getProxyID() == $proxy_id) {
                    $faculty_is_associated_with_course = true;
                }
            }

            if ($course_owner && $faculty_is_associated_with_course) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    public function getReportData($admin = false, $offset = 0, $limit = null, $ordering = "apr.`created_date`", $sort_dir = "DESC") {
        global $db;

        $params = array();
        $where = array();
        $join =array();

        if ($this->start_date) {
            $where[] = " AND apr.`created_date` >= ?";
            $params[] = $this->start_date;
        }

        if ($this->end_date) {
            $where[] = " AND apr.`created_date` <= ?";
            $params[] = $this->end_date;
        }

        if ($this->tools && is_array($this->tools) && count($this->tools)) {
            $where[] = " AND fe.`form_id` IN(" . implode(",", $this->tools) . ")";
        }

        if ($this->courses && is_array($this->courses) && count($this->courses)) {
            $where[] = " AND c.`course_id` IN(" . implode(",", $this->courses) . ")";
        }

        $AND_course_permission = "";
        if (!$admin) {

            $AND_course_permission = "
                AND (
                    SELECT 1 FROM `course_contacts` cc
                    WHERE cc.`course_id` = da.`course_id` 
                      AND (((cc.`contact_type` = 'director' OR cc.`contact_type` = 'ccoordinator' OR cc.`contact_type` = 'pcoordinator') AND cc.`proxy_id` = ?) OR (c.`pcoord_id` = ?))
                    LIMIT 1
                )";
            $params[] = $this->actor_proxy_id;
            $params[] = $this->actor_proxy_id;
        }

        $where = (count($where) ? implode(" ", $where) : "");
        $join = (count($join) ? implode(" ", $join) : "");

        $limit_offset = "";
        if ($limit) {
            $limit_offset = " LIMIT {$limit} OFFSET {$offset}";
        }

        $query ="
            SELECT  
                apr.`comments`,
                DATE_FORMAT(FROM_UNIXTIME(apr.`created_date`), '%Y-%m-%d %H:%i') AS `date`,
                ap.`dassessment_id`, 
                u.`firstname`, u.`lastname`, u.`email`, 
                f.`title`, f.`form_id`
            
            FROM `cbl_assessments_lu_items` AS i
            JOIN `cbl_assessments_lu_item_groups` AS ig ON ig.`item_group_id` = i.`item_group_id`  
            JOIN `cbl_assessment_form_elements` AS fe ON fe.`element_id` = i.`item_id` AND fe.`element_type` = 'item'
            JOIN `cbl_assessments_lu_forms` AS f ON fe.`form_id` = f.`form_id`
            JOIN `cbl_assessment_progress_responses` apr ON apr.`afelement_id` = fe.`afelement_id`
            JOIN `cbl_assessment_progress` AS ap ON ap.`aprogress_id` = apr.`aprogress_id`
            JOIN `cbl_distribution_assessments` AS da ON da.`dassessment_id` = ap.`dassessment_id`
            JOIN `cbl_distribution_assessment_targets` AS dat ON da.`dassessment_id` = dat.`dassessment_id`   
            JOIN `".AUTH_DATABASE."`.`user_data` AS u ON ap.`assessor_value` = u.`id` 
            JOIN `courses` AS c ON c.`course_id` = da.`course_id` 
            {$join}
            
            WHERE 1
            
                {$where}
                AND ig.`shortname` IN ('cbme_supervisor_feedback', 'cbme_fieldnote_feedback', 'cbme_procedure_feedback', 'cbme_ppa_feedback', 'cbme_rubric_feedback')
                AND apr.`comments` IS NOT NULL 
                AND apr.`comments` <> ''
                AND apr.`deleted_date` IS NULL
                AND ap.`progress_value` = 'complete'
                AND dat.`target_type` = 'proxy_id' 
                AND dat.`target_value` <> da.`assessor_value`
                AND dat.`deleted_date` IS NULL
                $AND_course_permission
                ORDER BY {$ordering} {$sort_dir} 
                $limit_offset
        ";

        $results = $db->getAll($query, $params);

        return $results;
    }

    public function getAssessmentTools($admin = false) {
        global $db;

        $params = array();
        $where = array();
        $join = array();
        if ($this->start_date) {
            $where[] = " AND a.`created_date` >= ?";
            $params[] = $this->start_date;
        }

        if ($this->end_date) {
            $where[] = " AND a.`created_date` <= ?";
            $params[] = $this->end_date;
        }

        $AND_course_permission = "";
        if (!$admin) {

            $AND_course_permission = "
                AND (
                    SELECT 1 FROM `course_contacts` cc
                    WHERE cc.`course_id` = da.`course_id` 
                      AND (((cc.`contact_type` = 'director' OR cc.`contact_type` = 'ccoordinator' OR cc.`contact_type` = 'pcoordinator') AND cc.`proxy_id` = ?) OR (c.`pcoord_id` = ?))
                    LIMIT 1
                )";
            $params[] = $this->actor_proxy_id;
            $params[] = $this->actor_proxy_id;
        }

        $where = (count($where) ? implode(" ", $where) : "");
        $join = (count($join) ? implode(" ", $join) : "");

        $query = "
            SELECT DISTINCT f.`form_id` AS `target_id`, f.`title` AS `target_label`
            
            FROM `cbl_assessments_lu_items` AS i
            JOIN `cbl_assessments_lu_item_groups` AS ig ON ig.`item_group_id` = i.`item_group_id`  
            JOIN `cbl_assessment_form_elements` AS fe ON fe.`element_id` = i.`item_id` AND fe.`element_type` = 'item'
            JOIN `cbl_assessments_lu_forms` AS f ON fe.`form_id` = f.`form_id`
            JOIN `cbl_assessment_progress_responses` apr ON apr.`afelement_id` = fe.`afelement_id`
            JOIN `cbl_assessment_progress` AS ap ON ap.`aprogress_id` = apr.`aprogress_id`
            JOIN `cbl_distribution_assessments` AS da ON da.`dassessment_id` = ap.`dassessment_id` 
            JOIN `".AUTH_DATABASE."`.`user_data` AS u ON ap.`assessor_value` = u.`id` 
            JOIN `courses` AS c ON c.`course_id` = da.`course_id` 
            {$join}
            
            WHERE 1
            
                {$where}
                AND ig.`shortname` IN ('cbme_supervisor_feedback', 'cbme_fieldnote_feedback', 'cbme_procedure_feedback')
                AND apr.`comments` IS NOT NULL
                AND apr.`deleted_date` IS NULL
                AND ap.`progress_value` = 'complete'
                $AND_course_permission
                ORDER BY `target_label` ASC
        ";
        $results = $db->getAll($query, $params);

        return $results;
    }

    public function getReportDataCount($admin = false) {
        if ($result = $this->getReportData($admin)) {
            return count($result);
        }

        return 0;
    }
}