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
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Distribution_Target extends Models_Base {
    protected $adtarget_id, $adistribution_id, $target_type, $target_scope, $target_role, $target_id;

    protected static $table_name = "cbl_assessment_distribution_targets";
    protected static $primary_key = "adtarget_id";
    protected static $default_sort_column = "adtarget_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->adtarget_id;
    }

    public function getAdistributionID() {
        return $this->adistribution_id;
    }

    public function getTargetType() {
        return $this->target_type;
    }

    public function getTargetScope() {
        return $this->target_scope;
    }

    public function getTargetRole() {
        return $this->target_role;
    }

    public function getTargetId() {
        return $this->target_id;
    }

    public static function fetchRowByID($adtarget_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "adtarget_id", "value" => $adtarget_id, "method" => "=")
        ));
    }

    public static function fetchRowByDistributionID($adistribution_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "adtarget_id", "value" => 0, "method" => ">=")));
    }

    public static function fetchAllByDistributionID($adistribution_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "=")
        ));
    }

    public static function fetchAllByTargetTypeTargetScopeTargetRoleTargetID($target_type, $target_scope, $target_role, $target_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "target_type", "value" => $target_type, "method" => "="),
            array("key" => "target_scope", "value" => $target_scope, "method" => "="),
            array("key" => "target_role", "value" => $target_role, "method" => "="),
            array("key" => "target_id", "value" => $target_id, "method" => "=")
        ));
    }

    public static function fetchAllByTargetID($target_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "target_id", "value" => $target_id, "method" => "=")
        ));
    }

    public static function fetchAllByDistributionIDTargetType($adistribution_id = null, $proxy_id = null) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "="),
            array("key" => "target_type", "value" => $proxy_id, "method" => "=")
        ));
    }

    public static function fetchRowByDistributionIDTargetID($adistribution_id, $target_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "="),
            array("key" => "target_id", "value" => $target_id, "method" => "=")
        ));
    }

    public static function getDistributionlessAssessmentTargets($dassessment_id, $assessor_id = null, $is_external = false) {
        global $db;
        $assessor_type = $is_external ? "external" : "internal";
        $AND_assessor_clause = "";
        if ($assessor_id) {
            $AND_assessor_clause = "AND da.`assessor_value` = ? AND da.`assessor_type` = '$assessor_type' ";
        }
        $sql = "SELECT * FROM `cbl_distribution_assessments` AS da
                JOIN `cbl_distribution_assessment_targets` AS dat ON (dat.`dassessment_id` = da.`dassessment_id`)
                WHERE da.`dassessment_id` = ?
                $AND_assessor_clause
                AND da.`deleted_date` IS NULL
                AND dat.`deleted_date` IS NULL";
        $constraints = array($dassessment_id);
        if ($AND_assessor_clause) {
            $constraints[] = $assessor_id;
        }
        $results = $db->GetAll($sql, $constraints);
        return $results;
    }

    /**
     * Fetches the related proxy IDs (or the entity itself) based on target type and return it in an array.
     * For example, a type "course_id" with scope "internal_learners" will return an array with all of the proxy IDs in that course.
     * A type "course_id" with scope "self" will return the course itself.
     *
     * @param $adistribution_id
     * @param $target_value
     * @param $target_type
     * @param $target_scope
     * @param $target_role
     * @return array|bool
     */
    public static function getDistributionAssessmentTargetsByScope($adistribution_id, $target_value, $target_type, $target_scope, $target_role) {
        global $db;
        $auth_database = AUTH_DATABASE; // Defined to allow in-line evaluation
        if (!$distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($adistribution_id)) {
            return false;
        }
        $assessment_targets = array();
        $default_struct = array(
            "name" => "",
            "adistribution_id" => $adistribution_id,
            "target_type" => "proxy_id",
            "target_value" => null,
            "target_role" => $target_role
        );
        switch ($target_type) {
            case "eventtype_id":
            default:
                // Not supported from this method.
                break;

            case "self":
                switch ($target_scope) {
                    case "self" :
                        $query = "  SELECT a.*, b.`id`, b.`firstname`, b.`lastname`, b.`email`, b.`number` 
                                    FROM `cbl_assessment_distribution_targets` AS a
                                    JOIN `$auth_database`.`user_data` AS b ON b.`id` = ?
                                    JOIN `$auth_database`.`user_access` AS c ON b.`id` = c.`user_id`
                                    WHERE a.`adistribution_id` = ?
                                    AND a.`target_type` = 'self'
                                    AND a.`target_id` = ?
                                    GROUP BY b.`id`";
                        $results = $db->GetAll($query, array($target_value, $adistribution_id, $target_value));
                        if (!empty($results)) {
                            foreach ($results as $result) {

                                // Check to see if the target has any user access record as faculty for the organisation. If so, this must take precedence to ensure we do not display an anonymous evaluation.
                                $group = "";
                                $access = Models_User_Access::fetchAllByUserIDOrganisationID($result["target_id"], $distribution->getOrganisationID());
                                if ($access) {
                                    $faculty = false;
                                    foreach ($access as $group) {
                                        if ($group->getGroup() == "faculty") {
                                            $faculty = true;
                                        }
                                        $group = $group->getGroup();
                                    }
                                    if ($faculty) {
                                        $group = "faculty";
                                    }
                                }

                                $new_target = $default_struct;
                                $new_target["target_value"] = $result["target_id"];
                                $new_target["target_type"] = "proxy_id";
                                $new_target["target_group"] = $group;
                                $new_target["name"] = "{$result["firstname"]} {$result["lastname"]}";
                                $assessment_targets[] = $new_target;
                            }
                        }
                        break;
                }
                break;

            case "proxy_id" :
                $query = "  SELECT a.*, b.`id`, b.`firstname`, b.`lastname`, b.`email`, b.`number`
                            FROM `cbl_assessment_distribution_targets` AS a
                            JOIN `$auth_database`.`user_data` AS b ON a.`target_id` = b.`id`
                            JOIN `$auth_database`.`user_access` AS c ON b.`id` = c.`user_id`
                            WHERE a.`adistribution_id` = ?
                            AND a.`target_type` = 'proxy_id'
                            AND a.`target_id` = ?
                            GROUP BY b.`id`";
                $results = $db->GetAll($query, array($adistribution_id, $target_value));
                if (!empty($results)) {
                    foreach ($results as $result) {
                        // Check to see if the target has any user access record as faculty for the organisation. If so, this must take precedence to ensure we do not display an anonymous evaluation.
                        $group = "";
                        $access = Models_User_Access::fetchAllByUserIDOrganisationID($result["target_id"], $distribution->getOrganisationID());
                        if ($access) {
                            $faculty = false;
                            foreach ($access as $group) {
                                if ($group->getGroup() == "faculty") {
                                    $faculty = true;
                                }
                                $group = $group->getGroup();
                            }
                            if ($faculty) {
                                $group = "faculty";
                            }
                        }

                        $new_target = $default_struct;
                        $new_target["target_value"] = $result["target_id"];
                        $new_target["target_type"] = "proxy_id";
                        $new_target["target_group"] = $group;
                        $new_target["name"] = "{$result["firstname"]} {$result["lastname"]}";
                        $assessment_targets[] = $new_target;
                    }
                }
                break;

            case "schedule_id" :
                if ($target_scope == "self") {
                    $rotation = Models_Schedule::fetchRowByID($target_value);
                    if ($rotation) {
                        $name = "";
                        if ($rotation->getScheduleParentID()) {
                            $parent_schedule = Models_Schedule::fetchRowByID($rotation->getScheduleParentID());
                            if ($parent_schedule) {
                                $name .= "{$parent_schedule->getTitle() }";
                            }
                        }
                        $name .= $rotation->getTitle();

                        $new_target = $default_struct;
                        $new_target["name"] = $rotation->getTitle();
                        $new_target["target_type"] = "schedule_id";
                        $new_target["target_value"] = $target_value;
                        $new_target["name"] = $name;
                        $new_target["target_group"] = "schedule";
                        $assessment_targets[] = $new_target;
                    }
                    break;
                } else {
                    $slot_type = false;
                    $AND_SLOT_TYPE = "";
                    switch ($target_scope) {
                        case "internal_learners" :
                            $slot_type = 1;
                            break;
                        case "external_learners" :
                            $slot_type = 2;
                            break;
                    }
                    if ($slot_type) {
                        $AND_SLOT_TYPE = " AND c.`slot_type_id` = {$slot_type} ";
                    }

                    $query = "  SELECT a.`schedule_id`, a.`start_date`, a.`schedule_parent_id`, a.`end_date`, b.*, c.`slot_type_id`, d.`id` AS `proxy_id`, d.`firstname`, d.`lastname`, d.`email`, d.`number` FROM `cbl_schedule`  AS a
                                JOIN `cbl_schedule_audience` AS b
                                ON a.`schedule_id` =  b.`schedule_id`
                                JOIN `cbl_schedule_slots` AS c
                                ON b.`schedule_slot_id` = c.`schedule_slot_id`
                                JOIN `$auth_database`.`user_data` AS d
                                ON b.`audience_value` =  d.`id`
                                WHERE a.`deleted_date` IS NULL
                                AND b.`audience_type` = 'proxy_id'
                                AND b.`deleted_date` IS NULL
                                AND (a.`schedule_id` = ? OR a.`schedule_parent_id` = ?)

                                {$AND_SLOT_TYPE}
                                
                                GROUP BY b.`audience_value`
                                ORDER BY d.`lastname`";
                    $results = $db->GetAll($query, array($target_value, $target_value));
                    if ($results) {
                        foreach ($results as $target) {
                            // Check to see if the target has any user access record as faculty for the organisation. If so, this must take precedence to ensure we do not display an anonymous evaluation.
                            $group = "";
                            $access = Models_User_Access::fetchAllByUserIDOrganisationID($target["proxy_id"], $distribution->getOrganisationID());
                            if ($access) {
                                $faculty = false;
                                foreach ($access as $group) {
                                    if ($group->getGroup() == "faculty") {
                                        $faculty = true;
                                    }
                                    $group = $group->getGroup();
                                }
                                if ($faculty) {
                                    $group = "faculty";
                                }
                            }

                            $new_target = $default_struct;
                            $new_target["name"] = "{$target["firstname"]} {$target["lastname"]}";
                            $new_target["target_type"] = "proxy_id";
                            $new_target["target_value"] = $target["proxy_id"];
                            $new_target["target_group"] = $group;
                            $assessment_targets[] = $new_target;
                        }
                    }
                }
                break;

            case "group_id" :
                switch ($target_scope) {
                    case "self":
                        if ($group = Models_Group::fetchRowByID($target_value)) {
                            $new_target = $default_struct;
                            $new_target["name"] = $group->getGroupName();
                            $new_target["target_type"] = "group_id";
                            $new_target["target_value"] = $target_value;
                            $new_target["target_group"] = "group";
                            $assessment_targets[] = $new_target;
                        }
                        break;
                    default:
                        if ($targets = Models_Group_Member::getAssessmentGroupMembers($distribution->getOrganisationID(), $target_value)) {
                            foreach ($targets as $target) {
                                // Check to see if the target has any user access record as faculty for the organisation. If so, this must take precedence to ensure we do not display an anonymous evaluation.
                                $group = "";
                                $access = Models_User_Access::fetchAllByUserIDOrganisationID($target["proxy_id"], $distribution->getOrganisationID());
                                if ($access) {
                                    $faculty = false;
                                    foreach ($access as $group) {
                                        if ($group->getGroup() == "faculty") {
                                            $faculty = true;
                                        }
                                        $group = $group->getGroup();
                                    }
                                    if ($faculty) {
                                        $group = "faculty";
                                    }
                                }

                                $new_target = $default_struct;
                                $new_target["name"] = $target["name"];
                                $new_target["target_type"] = "proxy_id";
                                $new_target["target_value"] = $target["proxy_id"];
                                $new_target["target_group"] = $group;
                                $assessment_targets[] = $new_target;
                            }
                        }
                        break;
                }
                break;

            case "cgroup_id" :
                if ($course_group = Models_Course_Group::fetchRowByID($target_value)) {
                    switch ($target_scope) {
                        case "self":
                            $new_target = $default_struct;
                            $new_target["name"] = $course_group->getGroupName();
                            $new_target["target_type"] = "cgroup_id";
                            $new_target["target_value"] = $target_value;
                            $new_target["target_group"] = "course_group";
                            $assessment_targets[] = $new_target;
                            break;
                        default:
                            if ($targets = $course_group->getAudience($course_group->getID())) {
                                foreach ($targets as $target) {
                                    // Check to see if the target has any user access record as faculty for the organisation. If so, this must take precedence to ensure we do not display an anonymous evaluation.
                                    $group = "";
                                    $access = Models_User_Access::fetchAllByUserIDOrganisationID($target["proxy_id"], $distribution->getOrganisationID());
                                    if ($access) {
                                        $faculty = false;
                                        foreach ($access as $group) {
                                            if ($group->getGroup() == "faculty") {
                                                $faculty = true;
                                            }
                                            $group = $group->getGroup();
                                        }
                                        if ($faculty) {
                                            $group = "faculty";
                                        }
                                    }

                                    $new_target = $default_struct;
                                    $new_target["name"] = "{$target["firstname"]} {$target["lastname"]}";
                                    $new_target["target_type"] = "proxy_id";
                                    $new_target["target_value"] = $target["proxy_id"];
                                    $new_target["target_group"] = $group;
                                    $assessment_targets[] = $new_target;
                                }
                            }
                            break;
                    }
                }
                break;

            case "course_id" :
                if ($course = Models_Course::fetchRowByID($target_value)) {
                    switch ($target_scope) {
                        case "self" :
                            $new_target = $default_struct;
                            $new_target["name"] = $course->getCourseName();
                            $new_target["target_type"] = "course_id";
                            $new_target["target_value"] = $target_value;
                            $new_target["target_group"] = "course";
                            $assessment_targets[] = $new_target;
                            break;
                        case "faculty" :
                            $query = "SELECT a.*, b.`id`, b.`firstname`, b.`lastname`, b.`email`, b.`number`, c.`group`, c.`role`
                                FROM `course_contacts` AS a
                                JOIN `$auth_database`.`user_data` AS b ON a.`proxy_id` = b.`id`
                                JOIN `$auth_database`.`user_access` AS c ON b.`id` = c.`user_id`
                                WHERE a.`course_id` = ?
                                AND c.`account_active` = 'true'
                                AND (c.`access_starts` = '0' OR c.`access_starts` <= ?)
                                AND (c.`access_expires` = '0' OR c.`access_expires` > ?)
                                AND c.`organisation_id` = ?";
                            if ($results = $db->GetAll($query, array($course->getID(), time(), time(), $distribution->getOrganisationID()))) {
                                foreach ($results as $target) {
                                    $new_target = $default_struct;
                                    $new_target["name"] = "{$target["firstname"]} {$target["lastname"]}";
                                    $new_target["target_value"] = $target["id"];
                                    $new_target["target_type"] = "proxy_id";
                                    $new_target["target_group"] = "faculty";
                                    $assessment_targets[] = $new_target;
                                }
                            }
                            break;
                        case "internal_learners" :
                            $curriculum_periods = Models_Curriculum_Period::fetchAllByCurriculumTypeID($course->getCurriculumTypeID());
                            if ($curriculum_periods) {
                                $cperiod_id = false;
                                $date = strtotime("now");
                                foreach ($curriculum_periods as $curriculum_period) {
                                    if ($date >= $curriculum_period->getStartDate() && $date <= $curriculum_period->getFinishDate()) {
                                        $cperiod_id = $curriculum_period->getID();
                                    }
                                }
                                if (!$cperiod_id) {
                                    $curriculum_period = Models_Curriculum_Period::fetchLastActiveByCurriculumTypeID($course->getCurriculumTypeID(), $date);
                                    if ($curriculum_period) {
                                        $cperiod_id = $curriculum_period->getID();
                                    }
                                }
                                if ($cperiod_id) {
                                    $targets = $course->getAllMembers($distribution->getCPeriodID());
                                    if ($targets) {
                                        foreach ($targets as $target) {
                                            // Check to see if the target has any user access record as faculty for the organisation. If so, this must take precedence to ensure we do not display an anonymous evaluation.
                                            $group = "";
                                            $access = Models_User_Access::fetchAllByUserIDOrganisationID($target["id"], $distribution->getOrganisationID());
                                            if ($access) {
                                                $faculty = false;
                                                foreach ($access as $group) {
                                                    if ($group->getGroup() == "faculty") {
                                                        $faculty = true;
                                                    }
                                                    $group = $group->getGroup();
                                                }
                                                if ($faculty) {
                                                    $group = "faculty";
                                                }
                                            }

                                            $new_target = $default_struct;
                                            $new_target["name"] = "{$target["firstname"]} {$target["lastname"]}";
                                            $new_target["target_value"] = $target["id"];
                                            $new_target["target_type"] = "proxy_id";
                                            $new_target["target_group"] = $group;
                                            $assessment_targets[] = $new_target;
                                        }
                                    }
                                }
                            }
                            break;
                        case "external_learners" :
                            break;
                        case "all_learners" :
                            $curriculum_periods = Models_Curriculum_Period::fetchAllByCurriculumTypeID($course->getCurriculumTypeID());
                            if ($curriculum_periods) {
                                $cperiod_id = false;
                                $date = strtotime("now");
                                foreach ($curriculum_periods as $curriculum_period) {
                                    if ($date >= $curriculum_period->getStartDate() && $date <= $curriculum_period->getFinishDate()) {
                                        $cperiod_id = $curriculum_period->getID();
                                    }
                                }
                                if (!$cperiod_id) {
                                    $curriculum_period = Models_Curriculum_Period::fetchLastActiveByCurriculumTypeID($course->getCurriculumTypeID(), $date);
                                    if ($curriculum_period) {
                                        $cperiod_id = $curriculum_period->getID();
                                    }
                                }
                                if ($cperiod_id) {
                                    $targets = $course->getAllMembers($distribution->getCPeriodID());
                                    if ($targets) {
                                        foreach ($targets as $target) {
                                            // Check to see if the target has any user access record as faculty for the organisation. If so, this must take precedence to ensure we do not display an anonymous evaluation.
                                            $group = "";
                                            $access = Models_User_Access::fetchAllByUserIDOrganisationID($target->getID(), $distribution->getOrganisationID());
                                            if ($access) {
                                                $faculty = false;
                                                foreach ($access as $group) {
                                                    if ($group->getGroup() == "faculty") {
                                                        $faculty = true;
                                                    }
                                                    $group = $group->getGroup();
                                                }
                                                if ($faculty) {
                                                    $group = "faculty";
                                                }
                                            }

                                            $new_target = $default_struct;
                                            $new_target["name"] = "{$target->getFirstname()} {$target->getLastname()}";
                                            $new_target["target_value"] = $target->getID();
                                            $new_target["target_type"] = "proxy_id";
                                            $new_target["target_group"] = $group;
                                            $assessment_targets[] = $new_target;
                                        }
                                    }
                                }
                            }
                            break;
                    }
                }
                break;

            case "external_hash" :
                $course_contact_model = new Models_Assessments_Distribution_CourseContact();
                $external_target = $course_contact_model->fetchRowByAssessorValueAssessorType($target_value, "external");

                if ($external_target) {
                    $entrada_base = new Entrada_Assessments_Base();
                    $user = $entrada_base->getUserByType($external_target->getAssessorValue(), $external_target->getAssessorType());

                    if ($user) {
                        $new_target = $default_struct;
                        $new_target["name"] = "{$user->getFirstname()} {$user->getLastname()}";
                        $new_target["target_type"] = "external_hash";
                        $new_target["target_value"] = $target_value;
                        $new_target["target_group"] = "external";
                        $assessment_targets[] = $new_target;
                    }
                }
                break;
        }
        return $assessment_targets;
    }

    // DEPRECATED
    public static function getAssessmentTargets($distribution_id = null, $dassessment_id = null, $assessor_id = null, $user_id = null, $external_assessor = false, $filter_start_date = NULL, $filter_end_date = NULL, $distributionless = false) {
        global $db;
        $assessment_targets = array();

        if ($external_assessor == true) {
            $assessor_type = $internal_external = "external";
        } else {
            $assessor_type = $internal_external = "internal";
        }

        $assessment = Models_Assessments_Assessor::fetchRowByID($dassessment_id);
        if (!$assessment) {
            return array(); // No targets if there's no assessment.
        }

        $targets_data = array();
        if ($distribution_id) {
            $distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($distribution_id);
            $target_records = Models_Assessments_Distribution_Target::fetchAllByDistributionID($distribution_id);
        } else {
            $distribution = null;
            $targets_data = self::getDistributionlessAssessmentTargets($dassessment_id, $assessor_id, $external_assessor);
        }

        if ($distributionless) {
            // Distribution-less only supports proxy_id
            foreach ($targets_data as $target_record_data) {
                switch ($target_record_data["target_type"]) {
                    case "proxy_id":
                        if (!$is_deleted = Models_Assessments_DeletedTask::fetchRowByADistributionIDAssessorTypeAssessorValueTargetIDDeliveryDate(null, $assessor_type, $assessor_id, $target_record_data["target_value"], $assessment->getDeliveryDate())) {
                            $target = Models_User::fetchRowByID($target_record_data["target_value"]);
                            if (!$target) {
                                // The target was not found
                                break;
                            }
                            $target = $target->toArray();
                            $target_data = array(
                                "adtarget_id" => $target_record_data["atarget_id"],
                                "name" => "{$target["firstname"]} {$target["lastname"]}",
                                "proxy_id" => $target_record_data["target_value"],
                                "number" => $target["number"],
                                "target_record_id" => $target_record_data["target_value"],
                                "email" => $target["email"],
                                "progress" => array()
                            );
                            $progress_records = Models_Assessments_Progress::fetchAllByAdistributionIDAssessorTypeAssessorValueTargetRecordID(null, $assessor_type, $user_id, $target["id"], null, $dassessment_id);
                            if ($progress_records) {
                                foreach ($progress_records as $progress_record) {
                                    $target_data["aprogress_id"] = $progress_record->getID();
                                    if ($progress_record->getProgressValue() == "complete") {
                                        $target_data["completed_aprogress_id"] = $progress_record->getID();
                                        if (!isset($target_data["completed_attempts"])) {
                                            $target_data["completed_attempts"] = 0;
                                        }
                                        $target_data["completed_attempts"]++;
                                    } elseif ($progress_record->getProgressValue() == "inprogress") {
                                        $target_data["inprogress_aprogress_id"] = $progress_record->getID();
                                    }
                                    if (!in_array($progress_record->getProgressValue(), $target_data["progress"])) {
                                        $target_data["progress"][] = $progress_record->getProgressValue();
                                    }
                                }
                            } else {
                                $target_data["progress"][] = "pending";
                            }
                            $assessment_targets[] = $target_data;                            
                        }
                        break;
                }
            }

        } else if ($distribution) {
            // If there is an assessment task and it was created only for an additional target (via distribution progress), do not add all other distribution targets.
            if ((!isset($assessment) || !$assessment) || (isset($assessment) && $assessment && !$assessment->getAdditionalAssessment())) {

                $schedule_cut_off_date = (is_null($distribution->getReleaseDate()) ? 0 : (int)$distribution->getReleaseDate());
                if ($target_records && $distribution) {
                    foreach ($target_records as $target_record) {
                        switch ($target_record->getTargetType()) {
                            case "proxy_id" :
                                $delegator = Models_Assessments_Distribution_Delegator::fetchRowByDistributionID($target_record->getAdistributionID());
                                if ($delegator && !is_null($assessor_id)) {
                                    $query = "  SELECT a.target_value AS `target_id`, b.`id`, b.`firstname`, b.`lastname`, b.`email`, b.`number` FROM `cbl_distribution_assessment_targets` AS a
                                        JOIN `" . AUTH_DATABASE . "`.`user_data` AS b
                                        ON a.`target_value` = b.`id`
                                        JOIN `" . AUTH_DATABASE . "`.`user_access` AS c
                                        ON b.`id` = c.`user_id`
                                        WHERE a.`dassessment_id` = ?
                                        AND a.`target_type` = 'proxy_id'
                                        AND a.`target_value` = ?
                                        GROUP BY b.`id`";
                                    $results = $db->GetAll($query, array($dassessment_id, $target_record->getTargetID()));
                                } else {
                                    $query = "  SELECT a.*, b.`id`, b.`firstname`, b.`lastname`, b.`email`, b.`number` FROM `cbl_assessment_distribution_targets` AS a
                                        JOIN `" . AUTH_DATABASE . "`.`user_data` AS b
                                        ON a.`target_id` = b.`id`
                                        JOIN `" . AUTH_DATABASE . "`.`user_access` AS c
                                        ON b.`id` = c.`user_id`
                                        WHERE a.`adistribution_id` = ?
                                        AND a.`target_type` = 'proxy_id'
                                        AND a.`target_id` = ?
                                        GROUP BY b.`id`";
                                    $results = $db->GetAll($query, array($target_record->getAdistributionID(), $target_record->getTargetID()));
                                }

                                if ($results) {
                                    foreach ($results as $target) {
                                        // Ensure the task has not been deleted via distribution progress.
                                        $deleted_task = false;
                                        if ($assessment) {
                                            $deleted_task = Models_Assessments_DeletedTask::fetchRowByADistributionIDAssessorTypeAssessorValueTargetIDDeliveryDate($distribution_id, $internal_external, $user_id, $target["target_id"], $assessment->getDeliveryDate());
                                        }
                                        if (!$deleted_task) {
                                            $target_data = array("adtarget_id" => $target_record->getID(), "name" => $target["firstname"] . " " . $target["lastname"], "proxy_id" => $target["target_id"], "number" => $target["number"], "target_record_id" => $target["target_id"], "email" => $target["email"], "progress" => array());
                                            $progress_records = Models_Assessments_Progress::fetchAllByAdistributionIDAssessorTypeAssessorValueTargetRecordID($target_record->getAdistributionID(), $internal_external, $user_id, $target["id"], null, $dassessment_id);
                                            if ($progress_records) {
                                                foreach ($progress_records as $progress_record) {
                                                    $target_data["aprogress_id"] = $progress_record->getID();
                                                    if ($progress_record->getProgressValue() == "complete") {
                                                        $target_data["completed_aprogress_id"] = $progress_record->getID();
                                                        if (!isset($target_data["completed_attempts"])) {
                                                            $target_data["completed_attempts"] = 0;
                                                        }
                                                        $target_data["completed_attempts"]++;
                                                    } elseif ($progress_record->getProgressValue() == "inprogress") {
                                                        $target_data["inprogress_aprogress_id"] = $progress_record->getID();
                                                    }
                                                    if (!in_array($progress_record->getProgressValue(), $target_data["progress"])) {
                                                        $target_data["progress"][] = $progress_record->getProgressValue();
                                                    }
                                                }
                                            } else {
                                                $target_data["progress"][] = "pending";
                                            }
                                            $target_data["distribution_target_id"] = $target_record->getTargetID();
                                            $target_data["distribution_target_type"] = $target_record->getTargetType();
                                            $target_data["distribution_target_scope"] = $target_record->getTargetScope();
                                            $target_data["distribution_target_role"] = $target_record->getTargetRole();
                                            $assessment_targets[] = $target_data;
                                        }
                                    }
                                }
                                break;
                            case "group_id" :
                                $targets = Models_Group_Member::getAssessmentGroupMembers($distribution->getOrganisationID(), $target_record->getTargetId());
                                if ($targets) {
                                    foreach ($targets as $target) {
                                        // Ensure the task has not been deleted via distribution progress.
                                        $deleted_task = false;
                                        if ($assessment) {
                                            $deleted_task = Models_Assessments_DeletedTask::fetchRowByADistributionIDAssessorTypeAssessorValueTargetIDDeliveryDate($distribution_id, $internal_external, $user_id, $target["proxy_id"], $assessment->getDeliveryDate());
                                        }
                                        if (!$deleted_task) {
                                            $target_data = array("adtarget_id" => $target_record->getID(), "name" => $target["name"], "lastname" => $target["lastname"], "proxy_id" => $target["proxy_id"], "number" => $target["number"], "target_record_id" => $target["proxy_id"], "email" => $target["email"], "progress" => array());
                                            $progress_records = Models_Assessments_Progress::fetchAllByAdistributionIDAssessorTypeAssessorValueTargetRecordID($target_record->getAdistributionID(), $internal_external, $user_id, $target["proxy_id"], null, $dassessment_id);
                                            if ($progress_records) {
                                                foreach ($progress_records as $progress_record) {
                                                    $target_data["aprogress_id"] = $progress_record->getID();
                                                    if ($progress_record->getProgressValue() == "complete") {
                                                        $target_data["completed_aprogress_id"] = $progress_record->getID();
                                                        if (!isset($target_data["completed_attempts"])) {
                                                            $target_data["completed_attempts"] = 0;
                                                        }
                                                        $target_data["completed_attempts"]++;
                                                    } elseif ($progress_record->getProgressValue() == "inprogress") {
                                                        $target_data["inprogress_aprogress_id"] = $progress_record->getID();
                                                    }
                                                    if (!in_array($progress_record->getProgressValue(), $target_data["progress"])) {
                                                        $target_data["progress"][] = $progress_record->getProgressValue();
                                                    }
                                                }
                                            } else {
                                                $target_data["progress"][] = "pending";
                                            }
                                            $target_data["distribution_target_id"] = $target_record->getTargetID();
                                            $target_data["distribution_target_type"] = $target_record->getTargetType();
                                            $target_data["distribution_target_scope"] = $target_record->getTargetScope();
                                            $target_data["distribution_target_role"] = $target_record->getTargetRole();
                                            $assessment_targets[] = $target_data;
                                        }
                                    }
                                }
                                break;
                            case "cgroup_id" :
                                /*$query = "  SELECT a.*, b.*, c.`id`, c.`firstname`, c.`lastname`, c.`email` FROM `course_groups` AS a
                                            JOIN `course_group_audience` AS b
                                            ON a.`cgroup_id` = b.`cgroup_id`
                                            JOIN `" . AUTH_DATABASE . "`.`user_data` AS c
                                            ON b.`proxy_id` = c.`id`
                                            WHERE a.`active` = ?
                                            AND b.`active` = ?";*/
                                break;
                            case "schedule_id" :
                                $delegator = Models_Assessments_Distribution_Delegator::fetchRowByDistributionID($target_record->getAdistributionID());
                                if ($delegator && is_null($assessor_id)) {
                                    $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($target_record->getAdistributionID());
                                    if ($distribution_schedule) {
                                        $query = "  SELECT a.`schedule_id`, a.`start_date`, a.`schedule_parent_id`, a.`end_date`, b.*, c.`slot_type_id`, d.`id`, d.`firstname`, d.`lastname`, d.`email`, d.`number` FROM `cbl_schedule`  AS a
                                            JOIN `cbl_schedule_audience` AS b
                                            ON a.`schedule_id` =  b.`schedule_id`
                                            JOIN `cbl_schedule_slots` AS c
                                            ON b.`schedule_slot_id` = c.`schedule_slot_id`
                                            JOIN `" . AUTH_DATABASE . "`.`user_data` AS d
                                            ON b.`audience_value` =  d.`id`
                                            WHERE " . ($distribution_schedule->getScheduleType() == "rotation" ? " a.`schedule_parent_id` = ?" : " a.`schedule_parent_id` = ?") . "
                                            AND a.`start_date` >= ?
                                            " . ($filter_start_date && $filter_end_date ? "
                                            AND (
                                                (a.`start_date` >= ? AND a.`start_date` <= ?)
                                                OR (a.`end_date` >= ? AND a.`end_date` <= ?)
                                                OR (a.`start_date` <= ? AND a.`end_date` >= ?)
                                            )" : "") . "
                                            AND a.`deleted_date` IS NULL
                                            AND b.`audience_type` = 'proxy_id'
                                            AND b.`deleted_date` IS NULL
                                            GROUP BY b.`audience_value`
                                            ORDER BY d.`lastname`";

                                        switch ($target_record->getTargetScope()) {
                                            case "self" :
                                                $rotation = Models_Schedule::fetchRowByID($target_record->getTargetId());
                                                if ($rotation) {
                                                    // Ensure the task has not been deleted via distribution progress.
                                                    $deleted_task = false;
                                                    if ($assessment) {
                                                        $deleted_task = Models_Assessments_DeletedTask::fetchRowByADistributionIDAssessorTypeAssessorValueTargetIDDeliveryDate($distribution_id, $internal_external, $user_id, $rotation->getID(), $assessment->getDeliveryDate());
                                                    }
                                                    if (!$deleted_task) {
                                                        $target_data = array("adtarget_id" => $target_record->getID(), "name" => $rotation->getTitle(), "target_record_id" => $rotation->getID(), "progress" => array());
                                                        $progress_records = Models_Assessments_Progress::fetchAllByAdistributionIDAssessorTypeAssessorValueTargetRecordID($target_record->getAdistributionID(), $internal_external, $user_id, $target_data["target_record_id"], null, $dassessment_id);
                                                        if ($progress_records) {
                                                            foreach ($progress_records as $progress_record) {
                                                                $target_data["aprogress_id"] = $progress_record->getID();
                                                                if ($progress_record->getProgressValue() == "complete") {
                                                                    $target_data["completed_aprogress_id"] = $progress_record->getID();
                                                                    if (!isset($target_data["completed_attempts"])) {
                                                                        $target_data["completed_attempts"] = 0;
                                                                    }
                                                                    $target_data["completed_attempts"]++;
                                                                } elseif ($progress_record->getProgressValue() == "inprogress") {
                                                                    $target_data["inprogress_aprogress_id"] = $progress_record->getID();
                                                                }
                                                                if (!in_array($progress_record->getProgressValue(), $target_data["progress"])) {
                                                                    $target_data["progress"][] = $progress_record->getProgressValue();
                                                                }
                                                            }
                                                        } else {
                                                            $target_data["progress"][] = "pending";
                                                        }
                                                        $target_data["distribution_target_id"] = $target_record->getTargetID();
                                                        $target_data["distribution_target_type"] = $target_record->getTargetType();
                                                        $target_data["distribution_target_scope"] = $target_record->getTargetScope();
                                                        $target_data["distribution_target_role"] = $target_record->getTargetRole();
                                                        $assessment_targets[] = $target_data;
                                                    }
                                                }

                                                break;
                                            case "internal_learners" :
                                                $query = "  SELECT a.`schedule_id`, a.`start_date`, a.`schedule_parent_id`, a.`end_date`, b.*, c.`slot_type_id`, d.`id`, d.`firstname`, d.`lastname`, d.`email`, d.`number` FROM `cbl_schedule`  AS a
                                                    JOIN `cbl_schedule_audience` AS b
                                                    ON a.`schedule_id` =  b.`schedule_id`
                                                    JOIN `cbl_schedule_slots` AS c
                                                    ON b.`schedule_slot_id` = c.`schedule_slot_id`
                                                    JOIN `" . AUTH_DATABASE . "`.`user_data` AS d
                                                    ON b.`audience_value` =  d.`id`
                                                    WHERE " . ($distribution_schedule->getScheduleType() == "rotation" ? " a.`schedule_parent_id` = ?" : " a.`schedule_parent_id` = ?") . "
                                                    AND a.`start_date` >= ?
                                                    " . ($filter_start_date && $filter_end_date ? "
                                                    AND (
                                                        (a.`start_date` >= ? AND a.`start_date` <= ?)
                                                        OR (a.`end_date` >= ? AND a.`end_date` <= ?)
                                                        OR (a.`start_date` <= ? AND a.`end_date` >= ?)
                                                    )" : "") . "
                                                    AND a.`deleted_date` IS NULL
                                                    AND b.`audience_type` = 'proxy_id'
                                                    AND b.`deleted_date` IS NULL
                                                    AND c.`slot_type_id` = 1
                                                    GROUP BY b.`audience_value`
                                                    ORDER BY d.`lastname`";
                                                break;
                                            case "external_learners" :
                                                $query = "  SELECT a.`schedule_id`, a.`start_date`, a.`schedule_parent_id`, a.`end_date`, b.*, c.`slot_type_id`, d.`id`, d.`firstname`, d.`lastname`, d.`email`, d.`number` FROM `cbl_schedule`  AS a
                                                    JOIN `cbl_schedule_audience` AS b
                                                    ON a.`schedule_id` =  b.`schedule_id`
                                                    JOIN `cbl_schedule_slots` AS c
                                                    ON b.`schedule_slot_id` = c.`schedule_slot_id`
                                                    JOIN `" . AUTH_DATABASE . "`.`user_data` AS d
                                                    ON b.`audience_value` =  d.`id`
                                                    WHERE " . ($distribution_schedule->getScheduleType() == "rotation" ? " a.`schedule_parent_id` = ?" : " a.`schedule_parent_id` = ?") . "
                                                    AND a.`start_date` >= ?
                                                    " . ($filter_start_date && $filter_end_date ? "
                                                    AND (
                                                        (a.`start_date` >= ? AND a.`start_date` <= ?)
                                                        OR (a.`end_date` >= ? AND a.`end_date` <= ?)
                                                        OR (a.`start_date` <= ? AND a.`end_date` >= ?)
                                                    )" : "") . "
                                                    AND a.`deleted_date` IS NULL
                                                    AND b.`audience_type` = 'proxy_id'
                                                    AND b.`deleted_date` IS NULL
                                                    AND c.`slot_type_id` = 2
                                                    GROUP BY b.`audience_value`
                                                    ORDER BY d.`lastname`";
                                                break;
                                        }

                                        if ($filter_start_date && $filter_end_date) {
                                            $prepared_variables = array($distribution_schedule->getScheduleID(), $schedule_cut_off_date, $filter_start_date, $filter_end_date, $filter_start_date, $filter_end_date, $filter_start_date, $filter_end_date);
                                        } else {
                                            $prepared_variables = array($distribution_schedule->getScheduleID(), $schedule_cut_off_date);
                                        }

                                        $results = $db->GetAll($query, $prepared_variables);
                                        if ($results) {
                                            foreach ($results as $target) {
                                                // Ensure the task has not been deleted via distribution progress.
                                                $deleted_task = false;
                                                if ($assessment) {
                                                    $deleted_task = Models_Assessments_DeletedTask::fetchRowByADistributionIDAssessorTypeAssessorValueTargetIDDeliveryDate($distribution_id, $internal_external, $user_id, $target["id"], $assessment->getDeliveryDate());
                                                }
                                                if (!$deleted_task) {
                                                    $target_data = array("adtarget_id" => $target_record->getID(), "name" => $target["firstname"] . " " . $target["lastname"], "proxy_id" => $target["id"], "number" => $target["number"], "target_record_id" => $target["id"], "email" => $target["email"], "progress" => array());
                                                    $target_data["distribution_target_id"] = $target_record->getTargetID();
                                                    $target_data["distribution_target_type"] = $target_record->getTargetType();
                                                    $target_data["distribution_target_scope"] = $target_record->getTargetScope();
                                                    $target_data["distribution_target_role"] = $target_record->getTargetRole();
                                                    $assessment_targets[] = $target_data;
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    switch ($target_record->getTargetScope()) {
                                        case "self" :
                                            $rotation = Models_Schedule::fetchRowByID($target_record->getTargetID());
                                            if ($rotation) {
                                                // Ensure the task has not been deleted via distribution progress.
                                                $deleted_task = false;
                                                if ($assessment) {
                                                    $deleted_task = Models_Assessments_DeletedTask::fetchRowByADistributionIDAssessorTypeAssessorValueTargetIDDeliveryDate($distribution_id, $internal_external, $user_id, $rotation->getID(), $assessment->getDeliveryDate());
                                                }
                                                if (!$deleted_task) {
                                                    $target_data = array("adtarget_id" => $target_record->getID(), "name" => $rotation->getTitle(), "target_record_id" => $rotation->getID(), "progress" => array());
                                                    $progress_records = Models_Assessments_Progress::fetchAllByAdistributionIDAssessorTypeAssessorValueTargetRecordID($target_record->getAdistributionID(), $internal_external, $user_id, $rotation->getID(), null, $dassessment_id);
                                                    if ($progress_records) {
                                                        foreach ($progress_records as $progress_record) {
                                                            $target_data["aprogress_id"] = $progress_record->getID();
                                                            if ($progress_record->getProgressValue() == "complete") {
                                                                $target_data["completed_aprogress_id"] = $progress_record->getID();
                                                                if (!isset($target_data["completed_attempts"])) {
                                                                    $target_data["completed_attempts"] = 0;
                                                                }
                                                                $target_data["completed_attempts"]++;
                                                            } elseif ($progress_record->getProgressValue() == "inprogress") {
                                                                $target_data["inprogress_aprogress_id"] = $progress_record->getID();
                                                            }
                                                            if (!in_array($progress_record->getProgressValue(), $target_data["progress"])) {
                                                                $target_data["progress"][] = $progress_record->getProgressValue();
                                                            }
                                                        }
                                                    } else {
                                                        $target_data["progress"][] = "pending";
                                                    }
                                                    $target_data["distribution_target_id"] = $target_record->getTargetID();
                                                    $target_data["distribution_target_type"] = $target_record->getTargetType();
                                                    $target_data["distribution_target_scope"] = $target_record->getTargetScope();
                                                    $target_data["distribution_target_role"] = $target_record->getTargetRole();
                                                    $assessment_targets[] = $target_data;
                                                }
                                            }
                                            break;
                                        default :
                                            $query = "  SELECT a.`dassessment_id`, a.`target_type`, a.`target_value`, b.`id`, b.`firstname`, b.`lastname`, b.`email`, b.`number`
                                        FROM `cbl_distribution_assessment_targets` AS a
                                        JOIN `" . AUTH_DATABASE . "`.`user_data` AS b
                                        ON a.`target_value` = b.`id`
                                        WHERE a.`dassessment_id` = ?
                                        AND a.`deleted_date` IS NULL
                                        ORDER BY b.`lastname`";

                                            $results = $db->GetAll($query, array($dassessment_id));
                                            if ($results) {
                                                foreach ($results as $target) {
                                                    // Ensure the task has not been deleted via distribution progress.
                                                    $deleted_task = false;
                                                    if ($assessment) {
                                                        $deleted_task = Models_Assessments_DeletedTask::fetchRowByADistributionIDAssessorTypeAssessorValueTargetIDDeliveryDate($distribution_id, $internal_external, $user_id, $target["id"], $assessment->getDeliveryDate());
                                                    }
                                                    if (!$deleted_task) {
                                                        $target_data = array("adtarget_id" => $target_record->getID(), "name" => $target["firstname"] . " " . $target["lastname"], "proxy_id" => $target["id"], "number" => $target["number"], "target_record_id" => $target["id"], "email" => $target["email"], "progress" => array());
                                                        $progress_records = Models_Assessments_Progress::fetchAllByAdistributionIDAssessorTypeAssessorValueTargetRecordID($target_record->getAdistributionID(), $internal_external, $user_id, $target["id"], null, $dassessment_id);
                                                        if ($progress_records) {
                                                            foreach ($progress_records as $progress_record) {
                                                                $target_data["aprogress_id"] = $progress_record->getID();
                                                                if ($progress_record->getProgressValue() == "complete") {
                                                                    $target_data["completed_aprogress_id"] = $progress_record->getID();
                                                                    if (!isset($target_data["completed_attempts"])) {
                                                                        $target_data["completed_attempts"] = 0;
                                                                    }
                                                                    $target_data["completed_attempts"]++;
                                                                } elseif ($progress_record->getProgressValue() == "inprogress") {
                                                                    $target_data["inprogress_aprogress_id"] = $progress_record->getID();
                                                                }
                                                                if (!in_array($progress_record->getProgressValue(), $target_data["progress"])) {
                                                                    $target_data["progress"][] = $progress_record->getProgressValue();
                                                                }
                                                            }
                                                        } else {
                                                            $target_data["progress"][] = "pending";
                                                        }
                                                        $target_data["distribution_target_id"] = $target_record->getTargetID();
                                                        $target_data["distribution_target_type"] = $target_record->getTargetType();
                                                        $target_data["distribution_target_scope"] = $target_record->getTargetScope();
                                                        $target_data["distribution_target_role"] = $target_record->getTargetRole();
                                                        $assessment_targets[] = $target_data;
                                                    }
                                                }
                                            }
                                            break;
                                    }
                                }
                                break;
                            case "course_id" :
                                $course = Models_Course::fetchRowByID($target_record->getTargetId());
                                switch ($target_record->getTargetScope()) {
                                    case "self" :
                                        if ($course) {
                                            // Ensure the task has not been deleted via distribution progress.
                                            $deleted_task = false;
                                            if ($assessment) {
                                                $deleted_task = Models_Assessments_DeletedTask::fetchRowByADistributionIDAssessorTypeAssessorValueTargetIDDeliveryDate($distribution_id, $internal_external, $user_id, $course->getID(), $assessment->getDeliveryDate());
                                            }
                                            if (!$deleted_task) {
                                                $target_data = array("adtarget_id" => $target_record->getID(), "name" => $course->getCourseName(), "target_record_id" => $course->getID(), "progress" => array());
                                                $progress_records = Models_Assessments_Progress::fetchAllByAdistributionIDAssessorTypeAssessorValueTargetRecordID($target_record->getAdistributionID(), $internal_external, $user_id, $target_record->getTargetID(), null, $dassessment_id);
                                                if ($progress_records) {
                                                    foreach ($progress_records as $progress_record) {
                                                        $target_data["aprogress_id"] = $progress_record->getID();
                                                        if ($progress_record->getProgressValue() == "complete") {
                                                            $target_data["completed_aprogress_id"] = $progress_record->getID();
                                                            if (!isset($target_data["completed_attempts"])) {
                                                                $target_data["completed_attempts"] = 0;
                                                            }
                                                            $target_data["completed_attempts"]++;
                                                        } elseif ($progress_record->getProgressValue() == "inprogress") {
                                                            $target_data["inprogress_aprogress_id"] = $progress_record->getID();
                                                        }
                                                        if (!in_array($progress_record->getProgressValue(), $target_data["progress"])) {
                                                            $target_data["progress"][] = $progress_record->getProgressValue();
                                                        }
                                                    }
                                                } else {
                                                    $target_data["progress"][] = "pending";
                                                }
                                                $target_data["distribution_target_id"] = $target_record->getTargetID();
                                                $target_data["distribution_target_type"] = $target_record->getTargetType();
                                                $target_data["distribution_target_scope"] = $target_record->getTargetScope();
                                                $target_data["distribution_target_role"] = $target_record->getTargetRole();
                                                $assessment_targets[] = $target_data;
                                            }
                                        }
                                        break;
                                    case "faculty" :
                                        $query = "  SELECT a.*, b.`id`, b.`firstname`, b.`lastname`, b.`email`, b.`number`, c.`group`, c.`role` FROM `course_contacts` AS a
                                    JOIN `" . AUTH_DATABASE . "`.`user_data` AS b
                                    ON a.`proxy_id` = b.`id`
                                    JOIN `" . AUTH_DATABASE . "`.`user_access` AS c
                                    ON b.`id` = c.`user_id`
                                    WHERE a.`course_id` = ?
                                    AND c.`account_active` = 'true'
                                    AND (c.`access_starts` = '0' OR c.`access_starts` <= ?)
                                    AND (c.`access_expires` = '0' OR c.`access_expires` > ?)
                                    AND c.`organisation_id` = ?";

                                        $results = $db->GetAll($query, array($course->getID(), time(), time(), $distribution->getOrganisationID()));
                                        if ($results) {
                                            foreach ($results as $target) {
                                                // Ensure the task has not been deleted via distribution progress.
                                                $deleted_task = false;
                                                if ($assessment) {
                                                    $deleted_task = Models_Assessments_DeletedTask::fetchRowByADistributionIDAssessorTypeAssessorValueTargetIDDeliveryDate($distribution_id, $internal_external, $user_id, $target["proxy_id"], $assessment->getDeliveryDate());
                                                }
                                                if (!$deleted_task) {
                                                    $target_data = array("adtarget_id" => $target_record->getID(), "name" => $target["firstname"] . " " . $target["lastname"], "proxy_id" => $target["target_id"], "number" => $target["number"], "target_record_id" => $target["target_id"], "email" => $target["email"], "progress" => array());
                                                    $progress_records = Models_Assessments_Progress::fetchAllByAdistributionIDAssessorTypeAssessorValueTargetRecordID($target_record->getAdistributionID(), $internal_external, $user_id, $target["proxy_id"], null, $dassessment_id);
                                                    if ($progress_records) {
                                                        foreach ($progress_records as $progress_record) {
                                                            $target_data["aprogress_id"] = $progress_record->getID();
                                                            if ($progress_record->getProgressValue() == "complete") {
                                                                $target_data["completed_aprogress_id"] = $progress_record->getID();
                                                                if (!isset($target_data["completed_attempts"])) {
                                                                    $target_data["completed_attempts"] = 0;
                                                                }
                                                                $target_data["completed_attempts"]++;
                                                            } elseif ($progress_record->getProgressValue() == "inprogress") {
                                                                $target_data["inprogress_aprogress_id"] = $progress_record->getID();
                                                            }
                                                            if (!in_array($progress_record->getProgressValue(), $target_data["progress"])) {
                                                                $target_data["progress"][] = $progress_record->getProgressValue();
                                                            }
                                                        }
                                                    } else {
                                                        $target_data["progress"][] = "pending";
                                                    }
                                                    $target_data["distribution_target_id"] = $target_record->getTargetID();
                                                    $target_data["distribution_target_type"] = $target_record->getTargetType();
                                                    $target_data["distribution_target_scope"] = $target_record->getTargetScope();
                                                    $target_data["distribution_target_role"] = $target_record->getTargetRole();
                                                    $assessment_targets[] = $target_data;
                                                }
                                            }
                                        }
                                        break;
                                    case "internal_learners" :
                                        if ($course) {
                                            $curriculum_periods = Models_Curriculum_Period::fetchAllByCurriculumType($course->getCurriculumTypeID());
                                            if ($curriculum_periods) {
                                                $cperiod_id = false;
                                                $date = strtotime("now");

                                                foreach ($curriculum_periods as $curriculum_period) {
                                                    if ($date >= $curriculum_period->getStartDate() && $date <= $curriculum_period->getFinishDate()) {
                                                        $cperiod_id = $curriculum_period->getID();
                                                    }
                                                }

                                                if (!$cperiod_id) {
                                                    $curriculum_period = Models_Curriculum_Period::fetchLastActiveByCurriculumTypeID($course->getCurriculumTypeID(), $date);
                                                    if ($curriculum_period) {
                                                        $cperiod_id = $curriculum_period->getID();
                                                    }
                                                }

                                                if ($cperiod_id) {
                                                    $targets = $course->getAllMembers();
                                                    if ($targets) {
                                                        foreach ($targets as $target) {
                                                            // Ensure the task has not been deleted via distribution progress.
                                                            $deleted_task = false;
                                                            if ($assessment) {
                                                                $deleted_task = Models_Assessments_DeletedTask::fetchRowByADistributionIDAssessorTypeAssessorValueTargetIDDeliveryDate($distribution_id, $internal_external, $user_id, $target->getActiveID(), $assessment->getDeliveryDate());
                                                            }
                                                            if (!$deleted_task) {
                                                                $target_data = array("adtarget_id" => $target_record->getID(), "name" => $target->getFirstname() . " " . $target->getLastname(), "proxy_id" => $target->getActiveID(), "number" => $target->getNumber(), "target_record_id" => $target->getActiveID(), "email" => $target->getEmail(), "progress" => array());
                                                                $progress_records = Models_Assessments_Progress::fetchAllByAdistributionIDAssessorTypeAssessorValueTargetRecordID($target_record->getAdistributionID(), $internal_external, $user_id, $target->getActiveID(), null, $dassessment_id);
                                                                if ($progress_records) {
                                                                    foreach ($progress_records as $progress_record) {
                                                                        $target_data["aprogress_id"] = $progress_record->getID();
                                                                        if ($progress_record->getProgressValue() == "complete") {
                                                                            $target_data["completed_aprogress_id"] = $progress_record->getID();
                                                                            if (!isset($target_data["completed_attempts"])) {
                                                                                $target_data["completed_attempts"] = 0;
                                                                            }
                                                                            $target_data["completed_attempts"]++;
                                                                        } elseif ($progress_record->getProgressValue() == "inprogress") {
                                                                            $target_data["inprogress_aprogress_id"] = $progress_record->getID();
                                                                        }
                                                                        if (!in_array($progress_record->getProgressValue(), $target_data["progress"])) {
                                                                            $target_data["progress"][] = $progress_record->getProgressValue();
                                                                        }
                                                                    }
                                                                } else {
                                                                    $target_data["progress"][] = "pending";
                                                                }
                                                                $target_data["distribution_target_id"] = $target_record->getTargetID();
                                                                $target_data["distribution_target_type"] = $target_record->getTargetType();
                                                                $target_data["distribution_target_scope"] = $target_record->getTargetScope();
                                                                $target_data["distribution_target_role"] = $target_record->getTargetRole();
                                                                $assessment_targets[] = $target_data;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        break;
                                    case "external_learners" :
                                        break;
                                    case "all_learners" :
                                        if ($course) {
                                            $curriculum_periods = Models_Curriculum_Period::fetchAllByCurriculumType($course->getCurriculumTypeID());
                                            if ($curriculum_periods) {
                                                $cperiod_id = false;
                                                $date = strtotime("now");

                                                foreach ($curriculum_periods as $curriculum_period) {
                                                    if ($date >= $curriculum_period->getStartDate() && $date <= $curriculum_period->getFinishDate()) {
                                                        $cperiod_id = $curriculum_period->getID();
                                                    }
                                                }

                                                if (!$cperiod_id) {
                                                    $curriculum_period = Models_Curriculum_Period::fetchLastActiveByCurriculumTypeID($course->getCurriculumTypeID(), $date);
                                                    if ($curriculum_period) {
                                                        $cperiod_id = $curriculum_period->getID();
                                                    }
                                                }

                                                if ($cperiod_id) {
                                                    $targets = $course->getAllMembers();
                                                    if ($targets) {
                                                        foreach ($targets as $target) {
                                                            // Ensure the task has not been deleted via distribution progress.
                                                            $deleted_task = false;
                                                            if ($assessment) {
                                                                $deleted_task = Models_Assessments_DeletedTask::fetchRowByADistributionIDAssessorTypeAssessorValueTargetIDDeliveryDate($distribution_id, $internal_external, $user_id, $target->getActiveID(), $assessment->getDeliveryDate());
                                                            }
                                                            if (!$deleted_task) {
                                                                $target_data = array("adtarget_id" => $target_record->getID(), "name" => $target->getFirstname() . " " . $target->getLastname(), "proxy_id" => $target->getActiveID(), "number" => $target->getNumber(), "target_record_id" => $target->getActiveID(), "email" => $target->getEmail(), "progress" => array());
                                                                $progress_records = Models_Assessments_Progress::fetchAllByAdistributionIDAssessorTypeAssessorValueTargetRecordID($target_record->getAdistributionID(), $internal_external, $user_id, $target->getActiveID(), null, $dassessment_id);
                                                                if ($progress_records) {
                                                                    foreach ($progress_records as $progress_record) {
                                                                        $target_data["aprogress_id"] = $progress_record->getID();
                                                                        if ($progress_record->getProgressValue() == "complete") {
                                                                            $target_data["completed_aprogress_id"] = $progress_record->getID();
                                                                            if (!isset($target_data["completed_attempts"])) {
                                                                                $target_data["completed_attempts"] = 0;
                                                                            }
                                                                            $target_data["completed_attempts"]++;
                                                                        } elseif ($progress_record->getProgressValue() == "inprogress") {
                                                                            $target_data["inprogress_aprogress_id"] = $progress_record->getID();
                                                                        }
                                                                        if (!in_array($progress_record->getProgressValue(), $target_data["progress"])) {
                                                                            $target_data["progress"][] = $progress_record->getProgressValue();
                                                                        }
                                                                    }
                                                                } else {
                                                                    $target_data["progress"][] = "pending";
                                                                }
                                                                $target_data["distribution_target_id"] = $target_record->getTargetID();
                                                                $target_data["distribution_target_type"] = $target_record->getTargetType();
                                                                $target_data["distribution_target_scope"] = $target_record->getTargetScope();
                                                                $target_data["distribution_target_role"] = $target_record->getTargetRole();
                                                                $assessment_targets[] = $target_data;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        break;
                                }
                                break;
                            case "self" :
                                if ($internal_external == "internal") {
                                    $query = "  SELECT a.*, b.`id`, b.`firstname`, b.`lastname`, b.`email`, b.`number` FROM `cbl_assessment_distribution_targets` AS a
                                    JOIN `" . AUTH_DATABASE . "`.`user_data` AS b
                                    ON b.`id` = ?
                                    JOIN `" . AUTH_DATABASE . "`.`user_access` AS c
                                    ON b.`id` = c.`user_id`
                                    WHERE a.`adistribution_id` = ?
                                    AND a.`target_type` = 'self'
                                    GROUP BY b.`id`";

                                    $results = $db->GetAll($query, array($user_id, $target_record->getAdistributionID()));
                                    if ($results) {
                                        foreach ($results as $target) {
                                            // Ensure the task has not been deleted via distribution progress.
                                            $deleted_task = false;
                                            if ($assessment) {
                                                $deleted_task = Models_Assessments_DeletedTask::fetchRowByADistributionIDAssessorTypeAssessorValueTargetIDDeliveryDate($distribution_id, $internal_external, $user_id, $target["id"], $assessment->getDeliveryDate());
                                            }
                                            if (!$deleted_task) {
                                                $target_data = array("adtarget_id" => $target_record->getID(), "name" => $target["firstname"] . " " . $target["lastname"], "proxy_id" => $target["target_id"], "number" => $target["number"], "target_record_id" => $target["id"], "email" => $target["email"], "progress" => array());
                                                $progress_records = Models_Assessments_Progress::fetchAllByAdistributionIDAssessorTypeAssessorValueTargetRecordID($target_record->getAdistributionID(), "internal", $user_id, $target["id"], null, $dassessment_id);
                                                if ($progress_records) {
                                                    foreach ($progress_records as $progress_record) {
                                                        $target_data["aprogress_id"] = $progress_record->getID();
                                                        if ($progress_record->getProgressValue() == "complete") {
                                                            $target_data["completed_aprogress_id"] = $progress_record->getID();
                                                            if (!isset($target_data["completed_attempts"])) {
                                                                $target_data["completed_attempts"] = 0;
                                                            }
                                                            $target_data["completed_attempts"]++;
                                                        } elseif ($progress_record->getProgressValue() == "inprogress") {
                                                            $target_data["inprogress_aprogress_id"] = $progress_record->getID();
                                                        }
                                                        if (!in_array($progress_record->getProgressValue(), $target_data["progress"])) {
                                                            $target_data["progress"][] = $progress_record->getProgressValue();
                                                        }
                                                    }
                                                } else {
                                                    $target_data["progress"][] = "pending";
                                                }
                                                $target_data["distribution_target_id"] = $target_record->getTargetID();
                                                $target_data["distribution_target_type"] = $target_record->getTargetType();
                                                $target_data["distribution_target_scope"] = $target_record->getTargetScope();
                                                $target_data["distribution_target_role"] = $target_record->getTargetRole();
                                                $assessment_targets[] = $target_data;
                                            }
                                        }
                                    }
                                } else {
                                    $query = "  SELECT a.*, b.`eassessor_id`, b.`firstname`, b.`lastname`, b.`email` FROM `cbl_assessment_distribution_targets` AS a
                                    JOIN `cbl_external_assessors` AS b
                                    ON b.`eassessor_id` = ?
                                    WHERE a.`adistribution_id` = ?
                                    AND a.`target_type` = 'self'
                                    GROUP BY b.`eassessor_id`";

                                    $results = $db->GetAll($query, array($user_id, $target_record->getAdistributionID()));
                                    if ($results) {
                                        foreach ($results as $target) {
                                            // Ensure the task has not been deleted via distribution progress.
                                            $deleted_task = false;
                                            if ($assessment) {
                                                $deleted_task = Models_Assessments_DeletedTask::fetchRowByADistributionIDAssessorTypeAssessorValueTargetIDDeliveryDate($distribution_id, $internal_external, $user_id, $target["eassessor_id"], $assessment->getDeliveryDate());
                                            }
                                            if (!$deleted_task) {
                                                $target_data = array("adtarget_id" => $target_record->getID(), "name" => $target["firstname"] . " " . $target["lastname"], "proxy_id" => $target["eassessor_id"], "target_record_id" => $target["eassessor_id"], "email" => $target["email"], "progress" => array());
                                                $progress_records = Models_Assessments_Progress::fetchAllByAdistributionIDAssessorTypeAssessorValueTargetRecordID($target_record->getAdistributionID(), "external", $user_id, $target["eassessor_id"], null, $dassessment_id);
                                                if ($progress_records) {
                                                    foreach ($progress_records as $progress_record) {
                                                        $target_data["aprogress_id"] = $progress_record->getID();
                                                        if ($progress_record->getProgressValue() == "complete") {
                                                            $target_data["completed_aprogress_id"] = $progress_record->getID();
                                                            if (!isset($target_data["completed_attempts"])) {
                                                                $target_data["completed_attempts"] = 0;
                                                            }
                                                            $target_data["completed_attempts"]++;
                                                        } elseif ($progress_record->getProgressValue() == "inprogress") {
                                                            $target_data["inprogress_aprogress_id"] = $progress_record->getID();
                                                        }
                                                        if (!in_array($progress_record->getProgressValue(), $target_data["progress"])) {
                                                            $target_data["progress"][] = $progress_record->getProgressValue();
                                                        }
                                                    }
                                                } else {
                                                    $target_data["distribution_target_id"] = $target_record->getTargetID();
                                                    $target_data["distribution_target_type"] = $target_record->getTargetType();
                                                    $target_data["distribution_target_scope"] = $target_record->getTargetScope();
                                                    $target_data["distribution_target_role"] = $target_record->getTargetRole();
                                                    $target_data["progress"][] = "pending";
                                                }
                                                $assessment_targets[] = $target_data;
                                            }
                                        }
                                    }
                                }
                                break;
                            case "eventtype_id":
                                // Instantiate the helper object to find the learning event assessment targets.
                                $learning_event_helper = new Entrada_Utilities_Assessments_DistributionLearningEvent(array("adistribution_id" => $distribution_id));
                                $assessment_targets = $learning_event_helper->getLearningEventAssessmentTargets($distribution_id, $internal_external, $user_id, $assessment, $target_record);
                                break;
                        }
                    }
                }
            }
        }

        // Check for additional assessment tasks added via distribution progress.
        if ($assessment) {
            $additional_tasks = Models_Assessments_AdditionalTask::fetchAllByADistributionIDAssessorTypeAssessorValueDeliveryDate($distribution_id, $internal_external, $user_id, $assessment->getDeliveryDate());
            if ($additional_tasks) {
                foreach ($additional_tasks as $additional_task) {
                    // Ensure the task has not been deleted via distribution progress.
                    $deleted_task = Models_Assessments_DeletedTask::fetchRowByADistributionIDAssessorTypeAssessorValueTargetIDDeliveryDate($distribution_id, $internal_external, $user_id, $additional_task->getTargetID(), $assessment->getDeliveryDate());
                    if (!$deleted_task) {
                        $target_user = Models_User::fetchRowByID($additional_task->getTargetID());
                        $target_data = array("adtarget_id" => 0, "name" => $target_user->getFullname(false), "proxy_id" => $additional_task->getTargetID(), "target_record_id" => $additional_task->getTargetID(), "number" => $target_user->getNumber(), "email" => $target_user->getEmail(), "progress" => array());
                        $progress_records = Models_Assessments_Progress::fetchAllByAdistributionIDAssessorTypeAssessorValueTargetRecordID($distribution_id, $additional_task->getAssessorType(), $user_id, $additional_task->getTargetID(), null, $assessment->getID());
                        if ($progress_records) {
                            foreach ($progress_records as $progress_record) {
                                $target_data["aprogress_id"] = $progress_record->getID();
                                if ($progress_record->getProgressValue() == "complete") {
                                    $target_data["completed_aprogress_id"] = $progress_record->getID();
                                    if (!isset($target_data["completed_attempts"])) {
                                        $target_data["completed_attempts"] = 0;
                                    }
                                    $target_data["completed_attempts"]++;
                                } elseif ($progress_record->getProgressValue() == "inprogress") {
                                    $target_data["inprogress_aprogress_id"] = $progress_record->getID();
                                }
                                if (!in_array($progress_record->getProgressValue(), $target_data["progress"])) {
                                    $target_data["progress"][] = $progress_record->getProgressValue();
                                }
                            }
                        } else {
                            $target_data["progress"][] = "pending";
                        }
                        $target_data["distribution_target_scope"] = "additional";
                        $target_data["distribution_target_type"] = "additional";
                        $target_data["distribution_target_role"] = "any";
                        $target_data["distribution_target_id"] = null;
                        $assessment_targets[] = $target_data;
                    }
                }
            }
        }

        return $assessment_targets;
    }

    public function getPendingTargets ($distribution_id = null, $proxy_id = null, $target_type = null, $targets = array()) {
        $pending_targets = array();
        if (!is_null($target_type) && !is_null($distribution_id) && !is_null($proxy_id) && $targets) {
            foreach ($targets as $target) {
                switch ($target_type) {
                    case "group_id" :
                    case "cgroup_id" :
                    case "proxy_id" :
                    case "schedule_id" :
                        $target_record_id = $target["proxy_id"];
                        break;
                    case "course_id" :
                    default:
                        $target_record_id = $target["target_record_id"];
                        break;
                }

                // If user is not logged in, assume they are an external assessor.
                if ((isset($_SESSION["isAuthorized"]) && ($_SESSION["isAuthorized"]))) {
                    $internal_external = "internal";
                } else {
                    $internal_external = "external";
                }

                $target_progress_record = Models_Assessments_Progress::fetchRowByAdistributionIDAssessorTypeAssessorValueTargetRecordIDLearningContextID($distribution_id, $internal_external, $proxy_id, $target_record_id, NULL);
                if (!$target_progress_record) {
                    $pending_targets[] = $target;
                }
            }
        }

        return $pending_targets;
    }

    public function getInprogressTargets ($distribution_id = null, $proxy_id = null, $target_type = null, $targets = array()) {
        $inprogress_targets = array();

        if (!is_null($target_type) && !is_null($distribution_id) && !is_null($proxy_id) && $targets) {
            foreach ($targets as $target) {
                switch ($target_type) {
                    case "group_id" :
                    case "cgroup_id" :
                    case "proxy_id" :
                    case "schedule_id" :
                        $target_record_id = $target["proxy_id"];
                        break;
                    case "course_id" :
                    default:
                        $target_record_id = $target["target_record_id"];
                        break;
                }

                // If user is not logged in, assume they are an external assessor.
                if ((isset($_SESSION["isAuthorized"]) && ($_SESSION["isAuthorized"]))) {
                    $internal_external = "internal";
                } else {
                    $internal_external = "external";
                }

                $target_progress_record = Models_Assessments_Progress::fetchRowByAdistributionIDAssessorTypeAssessorValueTargetRecordIDLearningContextID($distribution_id, $internal_external, $proxy_id, $target_record_id, NULL);
                if ($target_progress_record) {
                    $target["aprogress_id"] = $target_progress_record->getID();
                    if ($target_progress_record->getProgressValue() === "inprogress") {
                        $inprogress_targets[] = $target;
                    }
                }
            }
        }

        return $inprogress_targets;
    }

    public function getCompleteTargets ($distribution_id = null, $proxy_id = null, $target_type = null, $targets = array()) {
        $complete_targets = array();

        if (!is_null($target_type) && !is_null($distribution_id) && !is_null($proxy_id) && $targets) {
            foreach ($targets as $target) {
                switch ($target_type) {
                    case "group_id" :
                    case "cgroup_id" :
                    case "proxy_id" :
                    case "schedule_id" :
                        $target_record_id = $target["proxy_id"];
                        break;
                    case "course_id" :
                        $target_record_id = $target["target_record_id"];
                        break;
                }

                // If user is not logged in, assume they are an external assessor.
                if ((isset($_SESSION["isAuthorized"]) && ($_SESSION["isAuthorized"]))) {
                    $internal_external = "internal";
                } else {
                    $internal_external = "external";
                }

                $target_progress_record = Models_Assessments_Progress::fetchRowByAdistributionIDAssessorTypeAssessorValueTargetRecordIDLearningContextID($distribution_id, $internal_external, $proxy_id, $target_record_id, NULL);
                if ($target_progress_record) {
                    if ($target_progress_record->getProgressValue() === "complete") {
                        $complete_targets[] = $target;
                    }
                }
            }
        }

        return $complete_targets;
    }

    public function getTargetName($target_record_id) {
        $output = "N/A";

        if (in_array($this->getTargetType(), array("proxy_id", "group_id", "cgroup_id", "course_id", "schedule_id", "organisation_id")) && (in_array($this->getTargetScope(), array("faculty","internal_learners","external_learners","all_learners")) || $this->getTargetType() == "proxy_id")) {
            $user = Models_User::fetchRowByID($target_record_id);
            if ($user) {
                $output = $user->getFirstname() . " " . $user->getLastname();
            }
        } elseif ($this->getTargetType() == "schedule_id" && in_array($this->getTargetScope(), array("self", "children"))) {
            $schedule = Models_Schedule::fetchRowByID($target_record_id);
            if ($schedule) {
                $output = $schedule->getTitle();
            }
        }

        return $output;
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