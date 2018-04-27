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
 *  This file declares the Duty Hours Model
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Joabe Mendes <jm409@queensu.ca>
 * @copyright Copyright 2018 Queen's University. All Rights Reserved.
 *
 */

class Models_Duty_Hours extends Models_Base {

    protected $dhentry_id, $proxy_id, $encounter_date, $updated_date, $llocation_id, $lsite_id, $comments, $entry_active, $course_id, $cperiod_id, $hours, $hours_type;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "duty_hours_entries";
    protected static $primary_key = "dhentry_id";
    protected static $default_sort_column = "encounter_date";
    protected static $sort_order = "DESC";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->dhentry_id;
    }

    public function getDhentryID() {
        return $this->dhentry_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getEncounterDate() {
        return $this->encounter_date;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getLlocationID() {
        return $this->llocation_id;
    }

    public function getLsiteID() {
        return $this->lsite_id;
    }

    public function getComments() {
        return $this->comments;
    }

    public function getEntryActive() {
        return $this->entry_active;
    }

    public function getCourseID() {
        return $this->course_id;
    }

    public function getCurriculumPeriodID() {
        return $this->cperiod_id;
    }

    public function getHours() {
        return $this->hours;
    }

    // ua-msf : Case 2220 : Add types of hours (on_duty, off_duty, and absence)
    public function getHoursType() {
        return $this->hours_type;
    }

    public static function getHoursTypeLabel($hours_type) {
        global $translate;

        $hours_type_label = "";
        // could do translations here
        switch ($hours_type) {
            case "on_duty" :
                $hours_type_label = $translate->_("On Duty");
                break;
            case "off_duty" :
                $hours_type_label = $translate->_("Off Duty");
                break;
            case "absence" :
                $hours_type_label = $translate->_("Absence");
                break;
        }
        return $hours_type_label;
    }

    public static function fetchRowByID($dhentry_id) {
        $self = new self();

        $output = $self->fetchRow(array(
            array("key" => "dhentry_id", "value" => $dhentry_id, "method" => "=")
        ));

        if (isset($output)) {
            return $output;
        } else {
            return false;
        }
    }

    public static function fetchRowByColumn($column, $value, $entry_active) {
        $self = new self();

        $output = $self->fetchRow(array(
            array("key" => $column, "value" => $value, "method" => "="),
            array("key" => "entry_active", "value" => $entry_active, "method" => "=")
        ));

        if (isset($output)) {
            return $output;
        } else {
            return false;
        }
    }

    public static function fetchAllRecords($entry_active) {
        $self = new self();

        $output = $self->fetchAll(
            array(
                array(
                    "key" => "entry_active",
                    "value" => $entry_active,
                    "method" => "="
                )
            )
        );

        if (isset($output) && count($output) > 0) {
            return $output;
        } else {
            return false;
        }

    }

    public static function fetchAllRecordsByColumn($column, $value, $entry_active) {
        $self = new self();
        $output = $self->fetchAll(array(
            array("key" => $column, "value" => $value, "method" => "="),
            array("key" => "entry_active", "value" => $entry_active, "method" => "=")
        ));

        if (isset($output) && count($output) > 0) {
            return $output;
        } else {
            return false;
        }
    }

    public static function fetchRecordsByTimeRange($proxy_id, $course_id, $cperiod_id, $dhentry_id, $start_time, $hours, $entry_active) {
        global $db;

        $add_seconds = $hours * 60 * 60;
        $end_time = $start_time + $add_seconds;

        if (!isset($dhentry_id)) {
            $dhentry_id = 0;
        }

        $query = "SELECT `dhentry_id`"
            . " FROM `duty_hours_entries`"
            . " WHERE"
            . " ("
            . "   ((" . $db->qstr($start_time) . " >= `encounter_date`) AND (" . $db->qstr($start_time) . " < (`encounter_date` + (`hours` * 60 * 60))))"
            . "   OR"
            . "   ((" . $db->qstr($end_time) . " > `encounter_date`) AND (" . $db->qstr($end_time) . " <= (`encounter_date` + (`hours` * 60 * 60))))"
            . " )"
            . " AND `proxy_id` = " . $db->qstr($proxy_id) . " AND `course_id` = " . $db->qstr($course_id) . " AND `cperiod_id` = " . $db->qstr($cperiod_id)
            . " AND `dhentry_id` <> " . $db->qstr($dhentry_id) . " AND `entry_active` = " . $db->qstr($entry_active);

        $output = $db->GetAll($query);

        if (isset($output) && count($output) > 0) {
            return $output;
        } else {
            return false;
        }
    }


    public static function fetchAllRecordsByColumnWithMultipleCriteria() {
        $self = new self();

        $numargs = (func_num_args() - 1) / 2;
        $last_arg = func_num_args() - 1;
        $arg_list = func_get_args();
        $fetch_array = array();
        $i = 0;

        while ($i <= $numargs) {
            $query_array = array();
            $column_index = $i;
            $value_index = $i + 1;
            $query_array["key"] = $arg_list[$column_index];
            $query_array["value"] = $arg_list[$value_index];
            $query_array["method"] = "=";
            $fetch_array[] = $query_array;
            $i = $i + 2;
        }

        $query_array["key"] = "entry_active";
        $query_array["value"] = $arg_list[$last_arg];
        $query_array["method"] = "=";
        $query_array["sort_column"] = "encounter_date";
        $query_array["sort_order"] = "DESC";

        $fetch_array[] = $query_array;
        $output = $self->fetchAll($fetch_array, "=", "AND", "encounter_date", "DESC");

        if (isset($output) && count($output) > 0) {
            return $output;
        } else {
            return false;
        }
    }

    /**
     * validation
     *
     * Validate entry data before insert or update
     *
     * 1) Date must not be empty
     * 2) Entry times must not overlap
     * 3) 'Hours' value must not be 0 unless it's an 'OFF' day
     *
     * @param $arr_parms
     *                  [dhentry_id] => 31
     *                  [proxy_id] => 337
     * [encounter_date] => 1487203200
     * [updated_date] => 1487342669
     * [llocation_id] => 0
     * [lsite_id] => 0
     * [course_id] => 0
     * [cperiod_id] => 0
     * [hours] => 7.5
     * [hours_type] => on_duty
     * [entry_active] => 1
     * [comments] =>
     * @return string
     */
    public static function validation($arr_parms) {

        global $translate;

        $retVal = "";

        // Date must not be empty. This is not likely because the date picker defaults to today.
        if (empty($arr_parms["encounter_date"])) {
            $retVal .= $translate->_("You must enter a valid date.");
        } else {
            // Entry must occur during rotation (course/curriculum period)
            $rotation_error = $translate->_("There is a problem with this rotation's setup. Please contact an administrator.");
            if (isset($arr_parms["course_id"]) && isset($arr_parms["cperiod_id"]) && Models_Duty_Hours::isUserInCourseAudience(null, $arr_parms["course_id"])) {
                $curriculum_period = Models_Curriculum_Period::fetchRowByID($arr_parms["cperiod_id"]);
                if ($curriculum_period) {
                    $rotation_start_date = $curriculum_period->getStartDate();
                    $rotation_finish_date = $curriculum_period->getFinishDate();
                    if (isset($rotation_start_date) && isset($rotation_finish_date)) {
                        if ($arr_parms["encounter_date"] < $rotation_start_date || $arr_parms["encounter_date"] > $rotation_finish_date) {
                            $start_fmt = date(DEFAULT_DATE_FORMAT, $rotation_start_date);
                            $finish_fmt = date(DEFAULT_DATE_FORMAT, $rotation_finish_date);
                            $retVal .= "Logged entry date must be with in the rotation start (" . $start_fmt . ") and finish (" . $finish_fmt . ") dates.";
                        }
                    } else {
                        $retVal .= $rotation_error;
                    }
                } else {
                    $retVal .= $rotation_error;
                }
            } else {
                $retVal .= $rotation_error;
            }
        }

        // ua-msf : Case 2477 : hours only needed for on_duty entries
        if ($arr_parms["hours_type"] == "on_duty") {
            // the "hours" values must be a number between 0 and 28 with max of 2 decimals
            $number_format_error = "<br>" . $translate->_("Hours must be a valid number > 0 and < or = 24 with a max of 2 decimals.");
            if (!filter_var($arr_parms["hours"], FILTER_VALIDATE_FLOAT)) {
                $retVal .= $number_format_error;
            } else if (($arr_parms["hours"] < 0) || ($arr_parms["hours"] > 24)) {
                $retVal .= $number_format_error;
            } else if (!preg_match("/^[0-9]+(\.[0-9]{1,2})?$/", $arr_parms["hours"])) {
                $retVal .= $number_format_error;
            }

            // ua-msf : Case 2348 : log entry can"t span multiple calendar days
            if (!empty($arr_parms["encounter_date"]) && !empty($arr_parms["hours"]) && filter_var($arr_parms["hours"], FILTER_VALIDATE_FLOAT)) {
                // start time plus hours logged can"t exceed 24
                $logged_minutes = $arr_parms["hours"] * 60;
                $start_hr = date("G", $arr_parms["encounter_date"]);
                $start_min = date("i", $arr_parms["encounter_date"]);
                $start_minutes_in_day = ($start_hr * 60) + $start_min;
                if (($start_minutes_in_day + $logged_minutes) > 24 * 60) {
                    $retVal .= "<br>" . $translate->_("Your log entry spans multiple calendar days. Please create separate log entries for each day.");
                }
            }
        }

        // Encounter times must not overlap during record update.
        // if (isset($arr_parms["dhentry_id"])){
        if ($retVal == "") {
            $dhentry_id = (isset($arr_parms["dhentry_id"])) ? $arr_parms["dhentry_id"] : null;
            if (self::findDutyHoursEntryForOverlap($arr_parms["proxy_id"], $arr_parms["course_id"], $arr_parms["cperiod_id"], $dhentry_id, $arr_parms["encounter_date"], $arr_parms["hours"])) {
                $retVal = $translate->_("The date, time and hours you entered overlaps with an existing entry.");
            }
        }


        return $retVal;
    }

    /**
     * findDutyHoursEntryForOverlap
     *
     * If user is entering a time that overlaps with an existing entry time, we return true which
     * indicates such an overlap was found. The 'validation' method will then interpret that as a violation.
     *
     * @param $proxy_id
     * @param $course_id
     * @param $cperiod_id
     * @param $new_entry_id
     * @param $new_start_time
     * @param $new_hours
     * @return bool
     */
    public static function findDutyHoursEntryForOverlap($proxy_id, $course_id, $cperiod_id, $new_entry_id, $new_start_time, $new_hours) {

        $retVal = false;
        $results = self::fetchRecordsByTimeRange($proxy_id, $course_id, $cperiod_id, $new_entry_id, $new_start_time, $new_hours, 1);

        if ($results) {
            $retVal = true;
        }
        // 'true' if we found overlap
        return $retVal;
    }

    /**
     * fetchRecordsByCourse
     *
     * Return all the entries a user has logged for a specific rotation.
     *
     * @param $proxy_id
     * @param $course_id
     * @param $cperiod_id
     * @return array of Models_Duty_Hours
     */
    public static function fetchRecordsByCourse($proxy_id, $course_id, $cperiod_id) {
        return Models_Duty_Hours::fetchAllRecordsByColumnWithMultipleCriteria(
            "proxy_id", $proxy_id,
            "course_id", $course_id,
            "cperiod_id", $cperiod_id, 1
        );
    }

    /**
     * getLoggedHoursThisWeek
     *
     * Return the number of hours a user has logged this week for a specific rotation.
     *
     * @param $proxy_id
     * @param $course_id
     * @param $cperiod_id
     * @return number
     */
    public static function getLoggedHoursThisWeek($proxy_id, $course_id, $cperiod_id) {
        global $db;

        $hours = 0.0;

        if (isset($proxy_id) && isset($course_id) && isset($cperiod_id)) {
            $query = "SELECT COALESCE(SUM(`dh`.`hours`),0) AS logged_hours_this_week"
                . " FROM `duty_hours_entries` `dh`"
                . " WHERE `dh`.`hours_type` = 'on_duty'"
                . " AND `dh`.`entry_active` = 1"
                . " AND `dh`.`proxy_id` = ?"
                . " AND `dh`.`course_id` = ?"
                . " AND `dh`.`cperiod_id` = ?"
                . " AND YEARWEEK(FROM_UNIXTIME(`dh`.`encounter_date`),1) = YEARWEEK(CURDATE(),1)";
            $hours = $db->GetOne($query, array($proxy_id, $course_id, $cperiod_id));
        }
        return $hours;
    }

    /**
     * getDutyHoursForCourse
     *
     * Return the number of hours a user has logged for a specific rotation.
     *
     * @param $proxy_id
     * @param $course_id
     * @param $cperiod_id
     * @return number
     */
    public static function getDutyHoursForCourse($proxy_id, $course_id, $cperiod_id) {
        global $db;

        $hours = 0.0;

        if (isset($proxy_id) && isset($course_id) && isset($cperiod_id)) {
            $curriculum_period = Models_Curriculum_Period::fetchRowByID($cperiod_id);
            if ($curriculum_period) {
                $rotation_start_date = $curriculum_period->getStartDate();
                $rotation_finish_date = $curriculum_period->getFinishDate();
                if (isset($rotation_start_date) && isset($rotation_finish_date)) {
                    // only include hours to current date if course/rotation has not finished
                    $end_date = (time() < $rotation_finish_date) ? time() : $rotation_finish_date;
                    $query = "SELECT SUM(`duty_hours_entries`.`hours`) AS `logged_hours`"
                        . " FROM `duty_hours_entries`"
                        . " WHERE `duty_hours_entries`.`proxy_id` = ?"
                        . " AND `duty_hours_entries`.`course_id` = ?"
                        . " AND `duty_hours_entries`.`cperiod_id` = ?"
                        . " AND `duty_hours_entries`.`hours_type` = 'on_duty'"
                        . " AND `duty_hours_entries`.`encounter_date` BETWEEN ? AND ?"
                        . " AND `duty_hours_entries`.`entry_active` = 1";
                    $hours = $db->GetOne($query, array($proxy_id, $course_id, $cperiod_id, $rotation_start_date, $end_date));
                }
            }
        }

        return $hours;
    }

    /**
     * getAverageDutyHoursPerWeek
     *
     * Return the number of hours a user has logged for a specific rotation.
     *
     * @param $proxy_id
     * @param $course_id
     * @param $cperiod_id
     * @return number
     */
    public static function getAverageDutyHoursPerWeek($proxy_id, $course_id, $cperiod_id) {
        global $db;

        $average = 0.0;
        if (isset($proxy_id) && isset($course_id) && isset($cperiod_id)) {
            $total_hours = Models_Duty_Hours::getDutyHoursForCourse($proxy_id, $course_id, $cperiod_id);
            $curriculum_period = Models_Curriculum_Period::fetchRowByID($cperiod_id);
            if ($curriculum_period) {
                $rotation_start_date = $curriculum_period->getStartDate();
                $rotation_finish_date = $curriculum_period->getFinishDate();
                if (isset($rotation_start_date) && isset($rotation_finish_date)) {
                    // only include hours to current date if course/rotation has not finished
                    $end_date = (time() < $rotation_finish_date) ? time() : $rotation_finish_date;
                    // dates are unix timestamps (seconds since some date way back)
                    // calculate weeks = finish - start / 86400 (seconds/day) / 7 (days/week)
                    $week_count = ($end_date - $rotation_start_date) / 86400 / 7;
                    if ($week_count > 0) {
                        $average = number_format(($total_hours / $week_count), 2);
                    }
                }
            }
        }

        return $average;
    }

    /**
     * getUserClerkshipCourses
     *
     * Return a list of clerkship courses for the user/student
     *
     */
    public static function getUserClerkshipCourses($proxy_id = null) {
        global $db, $ENTRADA_ACL, $ENTRADA_USER, $translate;

        $proxy_id = (isset($proxy_id)) ? $proxy_id : $ENTRADA_USER->getID();

        $query = "	SELECT *"
            . "     FROM `courses`"
            . "     LEFT JOIN `curriculum_lu_types`"
            . "       ON `courses`.`curriculum_type_id` = `curriculum_lu_types`.`curriculum_type_id`"
            . "     LEFT JOIN `course_audience`"
            . "       ON `courses`.`course_id` = `course_audience`.`course_id`"
            . "     LEFT JOIN `curriculum_periods`"
            . "       ON `course_audience`.`cperiod_id` = `curriculum_periods`.`cperiod_id`"
            . "     LEFT JOIN `groups`"
            . "       ON `course_audience`.`audience_type` = 'group_id'"
            . "       AND `course_audience`.`audience_value` = `groups`.`group_id`"
            . "     LEFT JOIN `group_members`"
            . "       ON `group_members`.`group_id` = `groups`.`group_id`"
            . "    WHERE `curriculum_lu_types`.`curriculum_type_name` = " . $db->qstr($translate->_("Clerkship"))
            . "       AND `organisation_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation())
            . "       AND `courses`.`course_active` = '1'"
            . "       AND ( `group_members`.`proxy_id` = " . $db->qstr($proxy_id)
            . "		        OR ( `course_audience`.`audience_type` = 'proxy_id'"
            . "                    AND `course_audience`.`audience_value` = " . $db->qstr($proxy_id)
            . "                  )"
            . "	          )";

        $courses = $db->GetAll($query);
        $available_courses = array();
        if ($courses) {
            foreach ($courses as $course) {
                if ($ENTRADA_ACL->amIAllowed(new CourseResource($course["course_id"], $course["organisation_id"]), "read")) {
                    $available_courses[] = $course;
                }
            }
        }
        return (count($available_courses) > 0) ? $available_courses : false;
    }

    /**
     *  isUserCourseAudience
     *
     *  Returns if a user is part of a course audience or not
     *
     * @return boolean
     *
     */
    public static function isUserInCourseAudience($proxy_id = null, $course_id) {
        global $db, $ENTRADA_ACL, $ENTRADA_USER, $translate;

        $proxy_id = (isset($proxy_id)) ? $proxy_id : $ENTRADA_USER->getID();

        $query = "	SELECT * FROM `courses`"
            . "     LEFT JOIN `curriculum_lu_types`"
            . "       ON `courses`.`curriculum_type_id` = `curriculum_lu_types`.`curriculum_type_id`"
            . "     LEFT JOIN `course_audience`"
            . "       ON `courses`.`course_id` = `course_audience`.`course_id`"
            . "     LEFT JOIN `curriculum_periods`"
            . "       ON `course_audience`.`cperiod_id` = `curriculum_periods`.`cperiod_id`"
            . "     LEFT JOIN `groups`"
            . "       ON `course_audience`.`audience_type` = 'group_id'"
            . "       AND `course_audience`.`audience_value` = `groups`.`group_id`"
            . "     LEFT JOIN `group_members`"
            . "       ON `group_members`.`group_id` = `groups`.`group_id`"
            . "    WHERE `curriculum_lu_types`.`curriculum_type_name` = " . $db->qstr($translate->_("Clerkship"))
            . "       AND `courses`.`course_id` = " . $db->qstr($course_id)
            . "       AND `organisation_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation())
            . "       AND `courses`.`course_active` = '1'"
            . "       AND ( `group_members`.`proxy_id` = " . $db->qstr($proxy_id)
            . "		        OR ( `course_audience`.`audience_type` = 'proxy_id'"
            . "                    AND `course_audience`.`audience_value` = " . $db->qstr($proxy_id)
            . "                  )"
            . "	          )";

        $courses = $db->GetAll($query);
        if ($courses) {
            foreach ($courses as $course) {
                if ($ENTRADA_ACL->amIAllowed(new CourseResource($course["course_id"], $course["organisation_id"]), "read")) {
                    return true;
                }
            }
        }
        return false;
    }
}