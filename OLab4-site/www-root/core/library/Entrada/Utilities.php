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
 * Entrada_Utilities
 *
 * The Entrada Utilities class holds all of the globally accessible functions used
 * throughout Entrada. Many of these methods were migrated from functions.inc.php.
 *
 * All methods in this class MUST be public static functions.
 *
 * @author Organisation: Queen's University
 * @author Unit: Faculty of Health Sciences
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */
class Entrada_Utilities {
    /**
     * Determines whether or not a PHP session is available.
     *
     * @return bool
     */
    public static function is_session_started() {
        if ( php_sapi_name() !== "cli" ) {
            if ( version_compare(phpversion(), "5.4.0", ">=") ) {
                return session_status() === PHP_SESSION_ACTIVE ? true : false;
            } else {
                return session_id() === "" ? false : true;
            }
        }

        return false;
    }

    /**
     * This function is used to generate the standard start / finish calendars within forms.
     *
     * @param $fieldname
     * @param string $display_name
     * @param bool $show_start
     * @param bool $start_required
     * @param int $current_start
     * @param bool $show_finish
     * @param bool $finish_required
     * @param int $current_finish
     * @param bool $use_times
     * @param bool $add_line_break
     * @param string $display_name_start_suffix
     * @param string $display_name_finish_suffix
     * @return string
     */
    public static function generate_calendars($fieldname, $display_name = "", $show_start = false, $start_required = false, $current_start = 0, $show_finish = false, $finish_required = false, $current_finish = 0, $use_times = true, $add_line_break = false, $display_name_start_suffix = " Start", $display_name_finish_suffix = " Finish") {
        if (!$display_name) {
            $display_name = ucwords(strtolower($fieldname));
        }

        $output = "";

        if ($show_start) {
            $output .= self::generate_calendar($fieldname."_start", $display_name.$display_name_start_suffix, $start_required, $current_start, $use_times, $add_line_break);
        }

        if ($show_finish) {
            $output .= self::generate_calendar($fieldname."_finish", $display_name.$display_name_finish_suffix, $finish_required, $current_finish, $use_times, $add_line_break);
        }

        return $output;
    }

    /**
     * This function is used to generate a calendar with an optional time selector in a form.
     *
     * @param $fieldname
     * @param string $display_name
     * @param bool $required
     * @param int $current_time
     * @param bool $use_times
     * @param bool $add_line_break
     * @param bool $auto_end_date
     * @param bool $disabled
     * @param bool $optional
     * @return string
     */
    public static function generate_calendar($fieldname = "", $display_name = "", $required = false, $current_time = 0, $use_times = true, $add_line_break = false, $auto_end_date = false, $disabled = false, $optional = true) {
        global $ONLOAD;

        if (!$display_name) {
            $display_name = ucwords(strtolower($fieldname));
        }

        $output = "";

        if ($use_times) {
            $ONLOAD[] = "updateTime('".$fieldname."')";
        }

        if ($optional) {
            $ONLOAD[] = "dateLock('".$fieldname."')";
        }

        if ($current_time) {
            $time = 1;
            $time_date = date("Y-m-d", $current_time);
            $time_hour = (int) date("G", $current_time);
            $time_min = (int) date("i", $current_time);
        } else {
            $time = (($required) ? 1 : 0);
            $time_date = "";
            $time_hour = 0;
            $time_min = 0;
        }

        if ($auto_end_date) {
            $readonly = "disabled=\"disabled\"";
        } else {
            $readonly = "";
        }

        $output .= "<div class=\"control-group\">";
        $output .= "    <label id=\"".$fieldname."_text\" for=\"".$fieldname."\" class=\"control-label ".($required ? "form-required" : "form-nrequired")."\">".html_encode($display_name)."</label>";

        $output .= "	<div id=\"".$fieldname."_row\" class=\"controls\">";
        if ($required) {
            $output .= "    <input type=\"hidden\" name=\"" . $fieldname . "\" id=\"" . $fieldname . "\" value=\"1\" checked=\"checked\" />";
        } else {
            $output .= "    <input type=\"checkbox\" name=\"" . $fieldname . "\" id=\"" . $fieldname . "\" value=\"1\"" . (($time) ? " checked=\"checked\"" : "") . " onclick=\"dateLock('" . $fieldname . "')\" />";
        }

        $output .= "        <div class=\"input-append\">";
        $output .= "		    <input placeholder=\"YYYY-MM-DD\" type=\"text\" class=\"input-small\" name=\"".$fieldname."_date\" id=\"".$fieldname."_date\" value=\"".$time_date."\" $readonly autocomplete=\"off\" ".(!$disabled ? "onfocus=\"showCalendar('', this, this, '', '".$fieldname."_date', 0, 20, 1)\"" : "")." style=\"padding-left: 10px\" />&nbsp;";

        if (!$disabled) {
            $output .= "	    <a class=\"btn\" href=\"javascript: showCalendar('', document.getElementById('".$fieldname."_date'), document.getElementById('".$fieldname."_date'), '', '".$fieldname."_date', 0, 20, 1)\" title=\"Show Calendar\" onclick=\"if (!document.getElementById('".$fieldname."').checked) { return false; }\"><i class=\"icon-calendar\"></i></a>";
        }
        $output .= "        </div>";

        if ($use_times) {
            $output .= "	&nbsp;".(((bool) $add_line_break) ? "<br />" : "");
            $output .= "	<select class=\"input-mini\" name=\"".$fieldname."_hour\" id=\"".$fieldname."_hour\" onchange=\"updateTime('".$fieldname."')\">\n";
            foreach (range(0, 23) as $hour) {
                $output .= "	<option value=\"".(($hour < 10) ? "0" : "").$hour."\"".(($hour == $time_hour) ? " selected=\"selected\"" : "").">".(($hour < 10) ? "0" : "").$hour."</option>\n";
            }

            $output .= "	</select>\n";
            $output .= "	:";
            $output .= "	<select class=\"input-mini\" name=\"".$fieldname."_min\" id=\"".$fieldname."_min\" onchange=\"updateTime('".$fieldname."')\">\n";
            foreach (range(0, 59) as $minute) {
                $output .= "	<option value=\"".(($minute < 10) ? "0" : "").$minute."\"".(($minute == $time_min) ? " selected=\"selected\"" : "").">".(($minute < 10) ? "0" : "").$minute."</option>\n";
            }
            $output .= "	</select>\n";
            $output .= "	<span class=\"time-wrapper\">&nbsp;( <span class=\"content-small\" id=\"".$fieldname."_display\"></span> )</span>\n";
        }

        if ($auto_end_date) {
            $output .= "    <div id=\"auto_end_date\" class=\"content-small\" style=\"display: none\"></div>";
        }

        $output .= "	</div>\n";
        $output .= "</div>\n";

        return $output;
    }

    /**
     * Function will validate the calendar that is generated by generate_calendars().
     * @param $fieldname
     * @param bool $require_start
     * @param bool $require_finish
     * @param bool $use_times
     * @return array
     */
    public static function validate_calendars($fieldname = "", $require_start = true, $require_finish = true, $use_times = true) {
        $timestamp_start = 0;
        $timestamp_finish = 0;

        if (($require_start) && ((!isset($_POST[$fieldname."_start"])) || (!$_POST[$fieldname."_start_date"]))) {
            add_error("You must select a start date for the ".$fieldname." calendar entry.");
        } elseif (isset($_POST[$fieldname."_start"]) && $_POST[$fieldname."_start"] == "1") {
            if ((!isset($_POST[$fieldname."_start_date"])) || (!trim($_POST[$fieldname."_start_date"]))) {
                add_error("You have checked <strong>".ucwords(strtolower($fieldname))." Start</strong> but not selected a calendar date.");
            } else {
                if (($use_times) && ((!isset($_POST[$fieldname."_start_hour"])))) {
                    add_error("You have checked <strong>".ucwords(strtolower($fieldname))." Start</strong> but not selected an hour of the day.");
                } else {
                    if (($use_times) && ((!isset($_POST[$fieldname."_start_min"])))) {
                        add_error("You have checked <strong>".ucwords(strtolower($fieldname))." Start</strong> but not selected a minute of the hour.");
                    } else {
                        $pieces	= explode("-", $_POST[$fieldname."_start_date"]);
                        $hour = (($use_times) ? (int) trim($_POST[$fieldname."_start_hour"]) : 0);
                        $minute	= (($use_times) ? (int) trim($_POST[$fieldname."_start_min"]) : 0);
                        $second	= 0;
                        $month = (int) trim($pieces[1]);
                        $day = (int) trim($pieces[2]);
                        $year = (int) trim($pieces[0]);

                        if (checkdate($month, $day, $year)) {
                            $timestamp_start = mktime($hour, $minute, $second, $month, $day, $year);
                        } else {
                            add_error("Invalid format for calendar date.");
                        }
                    }
                }
            }
        }

        if (($require_finish) && ((!isset($_POST[$fieldname."_finish"])) || (!$_POST[$fieldname."_finish_date"]))) {
            add_error("You must select a finish date for the ".$fieldname." calendar entry.");
        } elseif (isset($_POST[$fieldname."_finish"]) && $_POST[$fieldname."_finish"] == "1") {
            if ((!isset($_POST[$fieldname."_finish_date"])) || (!trim($_POST[$fieldname."_finish_date"]))) {
                add_error("You have checked <strong>".ucwords(strtolower($fieldname))." Finish</strong> but not selected a calendar date.");
            } else {
                if (($use_times) && ((!isset($_POST[$fieldname."_finish_hour"])))) {
                    add_error("You have checked <strong>".ucwords(strtolower($fieldname))." Finish</strong> but not selected an hour of the day.");
                } else {
                    if (($use_times) && ((!isset($_POST[$fieldname."_finish_min"])))) {
                        add_error("You have checked <strong>".ucwords(strtolower($fieldname))." Finish</strong> but not selected a minute of the hour.");
                    } else {
                        $pieces	= explode("-", trim($_POST[$fieldname."_finish_date"]));
                        $hour = (($use_times) ? (int) trim($_POST[$fieldname."_finish_hour"]) : 23);
                        $minute	= (($use_times) ? (int) trim($_POST[$fieldname."_finish_min"]) : 59);
                        $second	= ((($use_times) && ((int) trim($_POST[$fieldname."_finish_min"]))) ? 59 : 0);
                        $month = (int) trim($pieces[1]);
                        $day = (int) trim($pieces[2]);
                        $year = (int) trim($pieces[0]);

                        if (checkdate($month, $day, $year)) {
                            $timestamp_finish = mktime($hour, $minute, $second, $month, $day, $year);
                        } else {
                            add_error("Invalid format for calendar date.");
                        }
                    }
                }
            }
        }

        if (($timestamp_start) && ($timestamp_finish) && ($timestamp_finish < $timestamp_start)) {
            add_error("The <strong>".ucwords(strtolower($fieldname))." Finish</strong> date &amp; time you have selected is before the <strong>".ucwords(strtolower($fieldname))." Start</strong> date &amp; time you have selected.");
        }

        return array("start" => $timestamp_start, "finish" => $timestamp_finish);
    }

    /**
     * Function will validate the calendar that is generated by generate_calendar().
     *
     * @param string $label
     * @param string $fieldname
     * @param bool $use_times
     * @param bool $required
     * @return int|void
     */
    public static function validate_calendar($label = "", $fieldname = "", $use_times = true, $required = true) {
        if ((!isset($_POST[$fieldname."_date"])) || (!trim($_POST[$fieldname."_date"]))) {
            if ($required) {
                add_error("<strong>".$label."</strong> date not entered.");
            } else {
                return;
            }
        } elseif (!checkDateFormat($_POST[$fieldname."_date"])) {
            add_error("Invalid format for <strong>".$label."</strong> date.");
        } else {
            if (($use_times) && ((!isset($_POST[$fieldname."_hour"])))) {
                add_error("<strong>".$label."</strong> hour not entered.");
            } else {
                if (($use_times) && ((!isset($_POST[$fieldname."_min"])))) {
                    add_error("<strong>".$label."</strong> minute not entered.");
                } else {
                    $pieces	= explode("-", $_POST[$fieldname."_date"]);
                    $hour	= (($use_times) ? (int) trim($_POST[$fieldname."_hour"]) : 0);
                    $minute	= (($use_times) ? (int) trim($_POST[$fieldname."_min"]) : 0);
                    $second	= 0;
                    $month	= (int) trim($pieces[1]);
                    $day	= (int) trim($pieces[2]);
                    $year	= (int) trim($pieces[0]);

                    $timestamp = mktime($hour, $minute, $second, $month, $day, $year);
                }
            }
        }

        return $timestamp;
    }

    public static function fetchUserPhotoDetails($proxy_id, $privacy_level = 1, $photo_access_override = false) {
        global $ENTRADA_ACL, $db;

        $photo_details                      = array();
        $photo_details["official_active"]	= false;
        $photo_details["official_url"]	    = false;
        $photo_details["uploaded_active"]	= false;
        $photo_details["uploaded_url"]	    = false;
        $photo_details["default_photo"]     = "official";

        /**
         * If the photo file actually exists, and either
         * 	If the user is in an administration group, or
         *  If the user is trying to view their own photo, or
         *  If the proxy_id has their privacy set to "Any Information"
         */
        if ($photo_access_override) {
            $acl_allowed = true;
            $acl_upload_check_passed = true;
        } else {
            $acl_allowed = false;
            $acl_upload_check_passed = true;
            if ($ENTRADA_ACL) {
                $acl_allowed = ($ENTRADA_ACL->amIAllowed(new PhotoResource($proxy_id, (int)$privacy_level, "official"), "read"));
                $acl_upload_check_passed = ($ENTRADA_ACL->amIAllowed(new PhotoResource($proxy_id, (int) $privacy_level, "upload"), "read"));
            }
        }
        if ((@file_exists(STORAGE_USER_PHOTOS."/".$proxy_id."-official")) && $acl_allowed) {
            $photo_details["official_active"]	= true;
            $photo_details["official_url"]= webservice_url("photo", array($proxy_id, "official"));
        } else {
            $photo_details["official_url"] = ENTRADA_URL."/images/headshot-male.gif";
        }
        /**
         * If the photo file actually exists, and
         * If the uploaded file is active in the user_photos table, and
         * If the proxy_id has their privacy set to "Basic Information" or higher.
         */
        $query = "SELECT `photo_active` FROM `".AUTH_DATABASE."`.`user_photos` WHERE `photo_type` = '1' AND `photo_active` = '1' AND `proxy_id` = ".$db->qstr($proxy_id);
        $photo_active = $db->GetOne($query);
        if ((@file_exists(STORAGE_USER_PHOTOS."/".$proxy_id."-upload")) &&
            ($photo_active) &&
            $acl_upload_check_passed
        ) {
            $photo_details["uploaded_active"] = true;
            $photo_details["uploaded_url"] = webservice_url("photo", array($proxy_id, "upload"));
            if (!$photo_details["official_active"]) {
                $photo_details["default_photo"] = "uploaded";
            }
        }
        return $photo_details;
    }

    /**
     * Using the user photo details, return which photo is the current default.
     * Returns empty string when unable to determine a default.
     *
     * Specifying true for photo_access_override will ignore ACL check that
     * fetchUserPhotoDetails is dependent on.
     *
     * @param int $proxy_id
     * @param int $privacy_level
     * @param bool $photo_access_override
     * @return string
     */
    public static function fetchUserDefaultPhotoURL($proxy_id, $privacy_level, $photo_access_override = false) {
        $user_photo_details = self::fetchUserPhotoDetails($proxy_id, $privacy_level, $photo_access_override);
        if (is_array($user_photo_details) && !empty($user_photo_details) && array_key_exists("default_photo", $user_photo_details)) {
            $photo_key = "{$user_photo_details["default_photo"]}_url";
            if (array_key_exists($photo_key, $user_photo_details)) {
                return $user_photo_details[$photo_key];
            }
        }
        return "";
    }

    
     public static function myEntradaSidebar($returnHtml = NULL) {
        global $translate, $ENTRADA_USER, $ENTRADA_CACHE;

        $my_entrada = $translate->_("My_Entrada");

        $exam_badge = "";
        $grade_badge = "";
        $assignment_badge = "";
        $exam_count = 0;

        /**
         * Gets Exams
         */
        if (!isset($ENTRADA_CACHE) || !$ENTRADA_CACHE->test("exams_outstanding_"  . AUTH_APP_ID . "_" . $ENTRADA_USER->getID())) {
            if (!$ENTRADA_CACHE->test("exam_posts_"  . AUTH_APP_ID . "_" . $ENTRADA_USER->getID())) {
                $posts = Models_Exam_Post::fetchAllEventExamsByProxyID($ENTRADA_USER->getID(), true, true);

                if (isset($ENTRADA_CACHE)) {
                    $ENTRADA_CACHE->save($posts, "exam_posts_" . AUTH_APP_ID . "_" . $ENTRADA_USER->getID(), array(), 300);
                }
            } else {
                $posts = $ENTRADA_CACHE->load("exam_posts_" . AUTH_APP_ID . "_" . $ENTRADA_USER->getID());
            }

            $un_submitted_exams = array();
            if (isset($posts) && is_array($posts) && !empty($posts)) {
                foreach ($posts as $post) {
                    if (isset($post) && is_object($post)) {
                        $start_valid        = $post->isAfterUserStartTime($ENTRADA_USER);
                        $end_valid          = $post->isBeforeUserEndTime($ENTRADA_USER);
                        $submission_valid   = $post->isSubmitAttemptAllowedByUser($ENTRADA_USER);
                        $post_id            = (int)$post->getID();
                        $proxy_id           = (int)$ENTRADA_USER->getID();

                        $progress           = Models_Exam_Progress::fetchAllByPostIDProxyIDProgressValue($post_id, $proxy_id, "submitted");
                        if ($progress && is_array($progress) && !empty($progress)) {

                        } else {
                            $progress       = Models_Exam_Progress::fetchAllByPostIDProxyIDProgressValue($post_id, $proxy_id, "inprogress");
                            if ($progress && is_array($progress) && !empty($progress)) {
                                if ($submission_valid) {
                                    $exam_exception = Models_Exam_Post_Exception::fetchRowByPostIdProxyIdExcluded($post->getID(), $ENTRADA_USER->getID());
                                    if (!$exam_exception) {
                                        $un_submitted_exams[] = $post;
                                    }
                                }
                            } else {
                                if ($start_valid && $end_valid && $submission_valid) {
                                    $exam_exception = Models_Exam_Post_Exception::fetchRowByPostIdProxyIdExcluded($post->getID(), $ENTRADA_USER->getID());
                                    if (!$exam_exception) {
                                        $un_submitted_exams[] = $post;
                                    }
                                }
                            }
                        }
                    }
                }
                $exam_count = count($un_submitted_exams);
            }

            if (isset($ENTRADA_CACHE)) {
                $ENTRADA_CACHE->save($exam_count, "exams_outstanding_" . AUTH_APP_ID . "_" . $ENTRADA_USER->getID());
            }
        } else {
            $exam_count = $ENTRADA_CACHE->load("exams_outstanding_" . AUTH_APP_ID . "_" . $ENTRADA_USER->getID());
        }

        if ($exam_count) {
            $exam_badge = "<span class=\"badge badge-success\"><small>".$exam_count."</small></span>";
        }

        /**
         * Gets Assignments
         */

        if (!isset($ENTRADA_CACHE) || !$ENTRADA_CACHE->test("assignment_outstanding_"  . AUTH_APP_ID . "_" . $ENTRADA_USER->getID())) {

            /**
             * We want to cache this query to make the navigation work faster
             */

            if (!$ENTRADA_CACHE->test("course_ids_"  . AUTH_APP_ID . "_" . $ENTRADA_USER->getID())) {
                $courses = courses_fetch_courses(true, true);

                if ($courses && is_array($courses)) {
                    foreach ($courses as $course) {
                        $course_ids[] = $course["course_id"];
                    }
                }

                if (isset($ENTRADA_CACHE)) {
                    $ENTRADA_CACHE->save($course_ids, "course_ids_" . AUTH_APP_ID . "_" . $ENTRADA_USER->getID(), array(), 600);
                }
            } else {
                $course_ids = $ENTRADA_CACHE->load("course_ids_" . AUTH_APP_ID . "_" . $ENTRADA_USER->getID());
            }

            if (!$ENTRADA_CACHE->test("assignment_ids_"  . AUTH_APP_ID . "_" . $ENTRADA_USER->getID())) {
                $assessment_ids_array = array();

                // this array is used to hold information about assessments a student is
                /*
                 * This section disabled
                 * until gradebook is updated

                $assessment_audiences_array = Models_Gradebook_Assessment::buildAssessmentByCourseFromProxyId($course_ids, $ENTRADA_USER->getID());
                if ($assessment_audiences_array && is_array($assessment_audiences_array)) {
                    foreach ($assessment_audiences_array as $grade_book_audience) {
                        if ($grade_book_audience && is_object($grade_book_audience)) {
                            if (!array_key_exists($grade_book_audience->getAssessmentID(), $assessment_ids_array)) {
                                $assessment_ids_array[$grade_book_audience->getAssessmentID()] = $grade_book_audience->getAssessmentID();
                            }
                        }
                    }
                }
                */

                if (isset($ENTRADA_CACHE)) {
                    $ENTRADA_CACHE->save($assessment_ids_array, "assignment_ids_" . AUTH_APP_ID . "_" . $ENTRADA_USER->getID(), array(), 600);
                }
            } else {
                $assessment_ids_array = $ENTRADA_CACHE->load("assignment_ids_" . AUTH_APP_ID . "_" . $ENTRADA_USER->getID());
            }
                    /*
                 * This section disabled
                 * until gradebook is updated
            $assignments = Models_Gradebook_Assessment::fetchAllAssignmentsByAssessmentIdsProxyIdCourseIdsNotSubmitted($assessment_ids_array, $ENTRADA_USER, $course_ids, "a.`assignment_title` DESC");
            $assignment_count = ($assignments ? count($assignments) : 0);
                    */

            $assignment_count = 0;

            if (isset($ENTRADA_CACHE)) {
                $ENTRADA_CACHE->save($assignment_count, "assignment_outstanding_" . AUTH_APP_ID . "_" . $ENTRADA_USER->getID());
            }
        } else {
            $assignment_count = $ENTRADA_CACHE->load("assignment_outstanding_" . AUTH_APP_ID . "_" . $ENTRADA_USER->getID());
        }

        if ($assignment_count) {
            $assignment_badge = "<span class=\"badge badge-success\">";
            $assignment_badge .= "<small>";
            $assignment_badge .= $assignment_count;
            $assignment_badge .= "</small>";
            $assignment_badge .= "</span>";
        }

        $sidebar_html = "<ul class=\"nav nav-list\">\n";
        $sidebar_html .= "<li><a href=\"" . ENTRADA_RELATIVE . "/exams" . "\">" . $translate->_("My Exams") . "</a>" . $exam_badge . "</li>\n";
        $sidebar_html .= "<li><a href=\"" . ENTRADA_RELATIVE . "/profile/gradebook" . "\">" . $translate->_("My Gradebook") . "</a></li>\n";
        $sidebar_html .= "<li><a href=\"" . ENTRADA_RELATIVE . "/profile/gradebook/assignments" . "\">" .$translate->_("My Assignments") . "</a>" . $assignment_badge . "</li>\n";
//        $sidebar_html .= "<li><a href=\"" . ENTRADA_RELATIVE . "/evaluations" . "\">" . $translate->_("My Clerkship Evaulations") . "</a>" . $evaluation_badge . "</li>\n";
//        $sidebar_html .= "<li><a href=\"" . ENTRADA_RELATIVE . "/assessments" . "\">" . $translate->_("My Assessments & Evaulation") . "</a>" . $evaluation_badge . "</li>\n";
        $sidebar_html .= "</ul>\n";

        if ($returnHtml === true) {
            if ($ENTRADA_USER->getActiveGroup() === "student" || $ENTRADA_USER->getActiveGroup() === "resident") {
                $export_html = "<div summary=\"my_entrada\" id=\"my_entrada\" class=\"panel\">";
                $export_html .= "<div class=\"panel-head\">";
                $export_html .= "<h3>" . $translate->_("My Entrada") . "</h3>";
                $export_html .= "</div>";
                $export_html .= "<div class=\"clearfix panel-body\">";
                $export_html .= $sidebar_html;
                $export_html .= "</div>";
                $export_html .= "</div>";
                return $export_html;
            }
        }

        if ($ENTRADA_USER->getActiveGroup() === "student" || $ENTRADA_USER->getActiveGroup() === "resident") {
            new_sidebar_item($translate->_("My Entrada"), $sidebar_html, "my_entrada", "open", 1);
        }
    }

    public static function orgSelectorSidebar() {
        global $translate, $ENTRADA_USER;

        $route = new Entrada_Router();
        $route->setBasePath(ENTRADA_CORE.DIRECTORY_SEPARATOR."modules".(defined("IN_ADMIN") && IN_ADMIN ? DIRECTORY_SEPARATOR."admin" : DIRECTORY_SEPARATOR."public"));
        $route->initRoute();

        $organisations = $ENTRADA_USER->getAllOrganisations();

        if ($organisations && count($organisations) && $ENTRADA_USER->getOrganisationGroupRole() && ((count($organisations) > 1) || (max(array_map("count", $ENTRADA_USER->getOrganisationGroupRole())) > 1))) {
            $org_group_role = $ENTRADA_USER->getOrganisationGroupRole();

            $sidebar_html = "<ul class=\"menu org-selector\">\n";

            foreach ($organisations as $key => $organisation_title) {
                $sidebar_html .= "<li class=\"nav-header\">" . $organisation_title . "</li>\n";

                if ($org_group_role && !empty($org_group_role)) {
                    $groups = [];
                    foreach ($org_group_role[$key] as $group_role) {
                        $groups[] = "<a href=\"" . ENTRADA_RELATIVE . "/dashboard?organisation_id=" . $key . "&ua_id=" . $group_role["access_id"] . "\"" . (($group_role["access_id"] == $ENTRADA_USER->getAccessId()) ? " class=\"bold\"" : "") . ">" . $translate->_($group_role["group"]) . "</a>";
                    }

                    $sidebar_html .= "<li>" . implode(", ", $groups). "</li>";
                }
            }
            $sidebar_html .= "</ul>\n";

            new_sidebar_item($translate->_("My Organisations"), $sidebar_html, "org-switch", "open", SIDEBAR_PREPEND);
        }
    }

    /**
     * Return the URL of the current page. This will be constructed using the ENTRADA_URL constant and the
     * $SERVER["REQUEST_URI"] with any unwanted tokens removed (defined in setting BOOKMARK_REMOVE_URL_TOKENS).
     */
    public static function getCurrentUrl() {
        global $BOOKMARK_REMOVE_URL_TOKENS;

        $request_uri = $_SERVER["REQUEST_URI"];
        $tokens_to_remove = $BOOKMARK_REMOVE_URL_TOKENS;

        if ($tokens_to_remove) {
            $uri_pieces = explode("/", $request_uri);

            $filtered_uri_pieces = array_filter($uri_pieces, function($token) use ($tokens_to_remove) {
                return !in_array($token, $tokens_to_remove);
            });

            $request_uri = $filtered_uri_pieces ? $request_uri = implode("/", $filtered_uri_pieces) : "";
        }

        $url = ENTRADA_URL . $request_uri;

        return $url;
    }

    /**
     * Given an associative array of URL params and filters, output the sanitized version of each param
     * @param  array  $params_to_clean  array("id" => "int", "encoded_param" => "decode")
     * @return array                    array("id" => 123, "encoded_param" => "now decoded")
     */
    public static function getCleanUrlParams($params_to_clean = array()) {
        // Get URL params, clean each then return as variable name ready for use
        parse_str($_SERVER['QUERY_STRING'], $url_params);

        $clean_params = array();

        // clean each requested param using the associated filter
        foreach($params_to_clean as $param => $filter) {
            if (isset($url_params[$param]) && $cleaned_param = clean_input($url_params[$param], $filter)) {
                $clean_params[$param] = $cleaned_param;
            }
        }

        // return array of param => cleaned_value
        return $clean_params;
    }

    /** 
     * Retrieves the value of a param coming from among $_GET variables or from module session
     * @param  string       $param
     * @return $param|false
     */
    public static function getSessionParam($param, $filter = 'int') {
        if ($_GET[$param]) {
            return clean_input($_GET[$param], $filter);
        }
        elseif (isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE][$param]) && $_SESSION[APPLICATION_IDENTIFIER][$MODULE][$param]) {
            return $_SESSION[APPLICATION_IDENTIFIER][$MODULE][$param];
        }

        return false;
    }

    /**
     * Sets a session variable in the current module
     * @param string  $param
     * @param any     $value 
     */
    public static function setSessionParam($param, $value) {
        $_SESSION[APPLICATION_IDENTIFIER][$MODULE][$param] = $value;
    }

    /**
     * Get calculated grade from the response items
     * @param array  $items 
     */
    public static function calculate_grade_from_items($items) {
        $weightedScore = 0.0;

        foreach($items as $item) {
            $multi_types = array("DropdownMultipleResponse", "HorizontalMultipleChoiceMultipleResponse", "VerticalMultipleChoiceMultipleResponse");
            $item_type = in_array($item["details"]["type"], $multi_types) ? "multi" : "single";
            $highest = 0;
            $checked_score = 0;
            $score = 0;
            
            foreach($item["item"]["item_responses"] AS $response) {
                $score = (!is_null($response['proxy_score']) ? intval($response["proxy_score"]) : intval($response["item_response_score"]));
                $highest = ($item_type == "single" ? max($score, $highest) : $highest + $score);
                
                if (!is_null($response["score"])) { 
                    $checked_score += $score; 
                }
            }
            $weight = intval($item['item']['weight']);
            $weightedItemScore = ($highest > 0 ? ($checked_score / $highest) * $weight : 0); 
            $weightedScore += $weightedItemScore;
        }

        return round($weightedScore, 2);
    }

    public static function buildInsertUpdateDelete($array) {
        $return = array(
            "insert" => "",
            "update" => "",
            "delete" => ""
        );

        if (isset($array) && is_array($array)) {
            $insert = array();
            $update = array();
            $delete = array();
            $add    = $array["add"];
            $remove = $array["remove"];
            if (isset($add) && is_array($add) && !empty($add)) {
                foreach($add as $key => $item) {
                    // If the key is not set in the remove
                    if (is_array($remove)) {
                        if (!array_key_exists($key, $remove)) {
                            $insert[$key] = unserialize($item);
                        } else {
                            $update[$key] = unserialize($item);
                            unset($remove[$key]);
                        }
                    }
                }
                if (is_array($remove) && !empty($remove)) {
                    foreach ($remove as $key => $item) {
                        $delete[$key] = unserialize($item);
                    }
                }
            } else if (isset($remove) && is_array($remove) && !empty($remove)) {
                // There is no add and there are some to remove, so they're delete not update
                foreach($remove as $key => $item) {
                    $delete[$key] = unserialize($item);
                }
            }
            $return = array(
                "insert" => $insert,
                "update" => $update,
                "delete" => $delete
            );
        }
        return $return;
    }

    public static function buildAudienceArray($audience_array) {
        if (isset($audience_array) && is_array($audience_array)) {
            $new_audience = array(
                "audience_type"     => $audience_array["audience_type"],
                "audience_value"    => (int)$audience_array["audience_value"],
                "custom_time"       => (int)$audience_array["custom_time"],
                "custom_time_start" => (int)$audience_array["custom_time_start"],
                "custom_time_end"   => (int)$audience_array["custom_time_end"]
            );
        }
        return $new_audience;
    }


    /**
     * Turn the output from print_r into a collapsible tree.
     * Optionally include a title and wrap in <pre> tags.
     *
     * @param mixed $data
     * @param string $title
     * @param bool $include_pre
     */
    public static function print_r_tree($data, $title = "", $include_pre = true) {
        $js = '<script language="Javascript">function jsPrintRTreeToggleDisplay(id) { document.getElementById(id).style.display = (document.getElementById(id).style.display === "block") ? "none" : "block";}</script>';

        // Generate print_r output
        $output = print_r($data, true);

        // Replace something like '[element] => <newline> (' with <a href="javascript:jsPrintRTreeToggleDisplay('...');">...</a><div id="..." style="display: none;">
        $output = preg_replace_callback(
            '/([ \t]*)(\[[^\]]+\][ \t]*\=\>[ \t]*[a-z0-9 \t_]+)\n[ \t]*\(/iU',
            function ($matches) {
                $id = substr(md5(rand(). $matches[0]), 0, 7);
                $replaced = "{$matches[1]}<a href=\"javascript:jsPrintRTreeToggleDisplay('$id');\">{$matches[2]}</a><div id='$id' style='display: none;'>";
                return $replaced;
            },
            $output
        );

        // replace ')' on its own on a new line with '</div>'
        $output = preg_replace('/^\s*\)\s*$/m', '</div>', $output);

        // Replace the final </div> with ')'
        $search = '</div>';
        $replacement = "\n)";
        $replaced = strrev(implode(strrev($replacement), explode(strrev($search), strrev($output), 2)));

        echo ($include_pre) ? '<pre>' : '';
        echo $js;
        echo $title ? "$title\n" : '';
        echo $replaced;
        echo ($include_pre) ? '</pre>' : '';
    }

    /**
     * Adds a single localized string to the $JAVASCRIPT_TRANSLATIONS global array.
     *
     * The default variable it adds is called "javascript_translations". This can be referenced in any javascript (where this function is called on the
     * respective index page) via "javascript_translations.your_message_name". This outputs an html_encoded string declaration of a localized string.
     * The resulting variable usable in javascript will have a property based on $message_name, lower-cased, underscore separated.
     *
     * Usage:
     * This string being localized should be defined in the language file for the related template.
     * To use the localized string in JavaScript, first, in PHP, define your localization in your relevant file.
     *
     * For example, in your index, add:
     *     Entrada_Utilities::addJavascriptTranslation("I want <stong>this</strong> text translated", "Translate This"); // "Translate This" will be converted
     *
     * Then in JavaScript, you can reference the variable like so:
     *     alert(javascript_translations.translate_this);
     *
     * This particular JavaScript would create an alert box with "I want &lt;strong&gt;this&lt;strong&gt; text translated". If the original string was
     * is in the language file, the localized version would appear as the string, html_encoded after translation.
     *
     * @param string $message_text
     * @param string $message_name
     * @param string $translation_variable
     * @param bool $html_encode
     */
    public static function addJavascriptTranslation($message_text, $message_name, $translation_variable = "javascript_translations", $html_encode = true) {
        global $JAVASCRIPT_TRANSLATIONS, $translate;
        if (!$translation_variable || !is_string($translation_variable)) {
            $translation_variable = "javascript_translations";
        }
        $message_name = clean_input($message_name, array("trim", "notags", "lowercase", "underscores", "module"));
        $message_enc = $translate->_($message_text); // Translate the given string
        if ($html_encode) {
            // HTML encode it if specified
            $message_enc = html_encode($message_enc);
        }
        // Add the usable javascript object (if not already defined)
        $translation_decl = "var $translation_variable = {};";
        if (!in_array($translation_decl, $JAVASCRIPT_TRANSLATIONS)) {
            $JAVASCRIPT_TRANSLATIONS[] = $translation_decl;
        }
        // Add this property to the object
        $JAVASCRIPT_TRANSLATIONS[] = "{$translation_variable}.{$message_name} = \"$message_enc\";";
    }

    /**
     * Return the value of an array for a given index; if the source is not an array or the index does not exist, use a default value instead.
     * The default value is null, unless otherwise specified.
     *
     * @param array|mixed $source_array
     * @param string $index
     * @param mixed|null $default_value
     * @return mixed
     */
    public static function arrayValueOrDefault(&$source_array, $index, $default_value = null) {
        if (is_array($source_array)) {
            if (array_key_exists($index, $source_array)) {
                return $source_array[$index];
            }
        }
        return $default_value;
    }

    /**
     * For the given source array, check if the value at the given index is an array and return it, otherwise, return an empty array.
     * The default can be overridden; it may be useful to return false instead of empty array in some situations.
     *
     * @param mixed $source_array
     * @param string $index
     * @param mixed|array $default_value
     * @return array
     */
    public static function arrayValueArrayOrEmpty(&$source_array, $index, $default_value = array()) {
        $return_value = $default_value;
        if (is_array($source_array)) {
            $return_value = array_key_exists($index, $source_array)
                ? is_array($source_array[$index])
                    ? $source_array[$index]
                    : $default_value
                : $default_value;
        }
        return $return_value;
    }

    /**
     * Drill down into a multi level array and determine if the given indecies exist in order.
     * This function requires at least 3 parameters; the source to examine, the default, and the index of the source.
     * Any number of parameters can be specified.
     *
     * Examples:
     *
     *    $source_array = array("1" => array("2" => array("3" => "Value we want")));
     *    $result = multidimensionalArrayValue($source_array, false, "1", "2", "3");   // $result = "Value we want"
     *    $result = multidimensionalArrayValue($source_array, false, "1", "2");        // $result = array("3" => "Value we want")
     *    $result = multidimensionalArrayValue($source_array, false, "1");             // $result = array("2" => array("3" => "Value we want"))
     *    $result = multidimensionalArrayValue($source_array, false, "z");             // $result = false
     *    $result = multidimensionalArrayValue($source_array, false);                  // $result = false
     *    $result = multidimensionalArrayValue($source_array, false, "1", "b");        // $result = false
     *    $result = multidimensionalArrayValue($source_array, false, "b", "2");        // $result = false
     *
     *    $source_array = array();
     *    $result = multidimensionalArrayValue($source_array, false, "b", "2");        // $result = false
     *    $result = multidimensionalArrayValue($source_array);                         // $result = null
     *
     *    $source_array = array("abc");
     *    $result = multidimensionalArrayValue($source_array, false, "b", "2");        // $result = false
     *    $result = multidimensionalArrayValue($source_array, false, 0);               // $result = "abc"
     *
     *    $source_array = "string";
     *    $result = multidimensionalArrayValue($source_array, false, "b", "2");        // $result = false
     *    $result = multidimensionalArrayValue($source_array, false, 0);               // $result = false
     *
     * @param mixed $source
     * @param null $default_value
     * @return mixed
     */
    public static function multidimensionalArrayValue(&$source, $default_value = null) {
        $args = func_get_args();
        if (count($args) == 1) {
            return null;
        }
        if (count($args) == 2) {
            return $default_value;
        }
        if (count($args) >= 3) {
            // Remove the first two arguments (source and default value)
            array_shift($args);
            array_shift($args);

            // Get the first argument
            $index = array_shift($args);
            $array_pointer = &$source;
            $search_complete = false;

            // Iterate through the arguments, checking the array for those indecies at each level of the array.
            while (!$search_complete) {
                if (is_array($array_pointer)
                    && array_key_exists($index, $array_pointer)
                ) {
                    if (empty($args)) {
                        $search_complete = true;
                    }
                    // Move to the next level
                    $array_pointer = &$array_pointer[$index];
                    if (!empty($args)) {
                        $index = array_shift($args);
                    }
                } else {
                    // Not found, so return the default
                    return $default_value;
                }
            }
            // We got this far, meaning the search is complete.
            // We would have quit if we previously didn't find anything when we expected to, so we can safely return the value at current array pointer.
            return $array_pointer;
        }
        return $default_value;
    }

    /**
     * Determine if a value is in both arrays.
     *
     * @param string $value
     * @param array $first_array
     * @param array $second_array
     * @return bool
     */
    public static function inBothArrays($value, &$first_array, &$second_array) {
        if (is_array($first_array)
            && is_array($second_array)
        ) {
            if (in_array($value, $first_array)
                && in_array($value, $second_array)
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the current logged in user is considered a super-user/super-admin.
     *
     * Additional ACL checks can be passed along that will grant Super Admin access when passed.
     * The additional_acl_checks describes the acl resource; the structure of the array matches the amIAllowed function signature.
     *
     * $additional_acl_checks should be defined (similarly) as:
     *
     *      $checks = array(
     *          array(
     *              "resource" => "assessmentreportadmin",
     *              "action" => "read",
     *              "assert" => true
     *          )
     *      );
     *
     * Then, executing this method as follows will return a boolean result that can be used to determine if the
     * logged in user is a super admin:
     *      Entrada_Utilities::isCurrentUserSuperAdmin($checks);
     *
     * Each entry in the array describes one ACL check. Action and Assert are optional (they use $default_action and $default_assert
     * respectively when not set); the only required definition is the resource.
     *
     * For example, this would be a valid array (assuming the resource names or objects are valid and defined in the database):
     *
     *      $checks = array(
     *          array("resource" => "reportadmin"),
     *          array("resource" => "secondaryadministrator", "assert" => "false"),
     *          array("resource" => "useradmin", "action" => "read", "assert" => false),
     *          array("resource" => new ACLResourceObject(), "action" => "write", "assert" => true)
     *      );
     *
     * @param array $additional_acl_checks
     * @param string $default_action
     * @param bool $default_assert
     * @return bool
     */
    public static function isCurrentUserSuperAdmin($additional_acl_checks = array(), $default_action = "read", $default_assert = true) {
        global $ENTRADA_ACL, $ENTRADA_USER;
        if ($ENTRADA_USER->getActiveRole() == "admin" && $ENTRADA_USER->getActiveGroup() == "medtech") {
            return true;
        }
        $default_resource_info = array("resource" => "", "action" => $default_action, "assert" => $default_assert);
        if (is_array($additional_acl_checks)) {
            foreach ($additional_acl_checks as $acl_resource_info) {
                if (array_key_exists("resource", $acl_resource_info)) {
                    $resource_info = array_merge($default_resource_info, $acl_resource_info);
                    if ($resource_info["resource"]) {
                        if ($ENTRADA_ACL->amIAllowed($resource_info["resource"], $resource_info["action"], $resource_info["assert"])) {
                            return true;
                        }
                    }
                }
            }
        }
        // Didn't explicitly pass, so the user is not a super user.
        return false;
    }
  
    /**
     * Validate and sanitize the provided array with each of the sanitization flags provided.
     *
     * @param $array
     * @param $sanitization_params
     * @param $glue
     * @return string
     */
    public static function sanitizeArrayAndImplode($array, $sanitization_params, $glue = ",") {
        if (!is_array($array) || empty($array)) {
            return false;
        }
        $data_array = array_map(function ($v) use ($sanitization_params) {
            return clean_input($v, $sanitization_params);
        }, $array);
        if (!$data_array) {
            return false;
        }
        $string = implode($glue, array_filter($data_array));
        return $string;
    }

    /**
     * Build a translate object for use with a specific organisation.
     * Defaults to "default" if the organisation is not found.
     * This function is useful in contexts where organisations are iterated on, such as cron jobs.
     *
     * @param $organisation_id
     * @return Entrada_Translate
     */
    public static function buildTranslateByOrganisation($organisation_id) {
        $template = "default";
        if ($organisation = Models_Organisation::fetchRowByID($organisation_id)) {
            $template = $organisation->getTemplate();
        }
        $translate = new Entrada_Translate(
            array (
                "adapter" => "array",
                "disableNotices" => (DEVELOPMENT_MODE ? false : true)
            )
        );
        $translate->addTranslation(
            array(
                'adapter' => 'array',
                'content' => ENTRADA_ABSOLUTE . "/templates/{$template}/languages",
                'locale'  => 'auto',
                "scan" => Entrada_Translate::LOCALE_FILENAME
            )
        );
        return $translate;
    }

    /**
     * Return the timestamp for a specific time of the day of a given timestamp.
     * Useful for checking start/end/middle of day using DateTime object with timezones.
     *
     * Example: assuming "America/Toronto" timezone, this:
     *    createTimestampForDay(1509983275); // Timestamp = 2016-11-06 10:47:55 AM
     *
     * Returns:
     *    1509944400 (2017-11-06 12:00:00 AM)
     *
     * And this:
     *    createTimestampForDay(1509983275, 23, 59, 59);
     *
     * Returns:
     *    1510030799 (2017-11-06 11:59:59 PM)
     *
     * @param int $timestamp
     * @param int $hour
     * @param int $minute
     * @param int $second
     * @param null|string $timezone
     * @return string
     */
    public static function createTimestampForDay($timestamp, $hour = 0, $minute = 0, $second = 0, $timezone = null) {
        if (!$timezone) {
            $timezone = DEFAULT_TIMEZONE;
        }
        $dt = DateTime::createFromFormat("U", (int)$timestamp);
        $dt->setTimeZone(new DateTimeZone($timezone))->format("Y-m-d");
        $dt->setTime($hour, $minute, $second);
        return $dt->format("U");
    }

    /**
     * Get exclusive access to a resource via mysql lock mechanism.
     * Attempt to get exclusive access using the named lock, waiting for a specific amount of
     * time (defaults to waiting 0 seconds) before giving up.
     *
     * @param string $named_lock
     * @param int $timeout
     * @return mixed
     */
    public static function obtainExclusiveAccess($named_lock = "default_lockname", $timeout = 0) {
        global $db;
        return $db->GetOne("SELECT GET_LOCK(?,?)", array($named_lock, $timeout));
    }

    /**
     * Release the exclusive access lock.
     * This occurs automatically when the DB connection is closed.
     *
     * @param string $named_lock
     * @return mixed
     */
    public static function releaseExclusiveAccess($named_lock = "default_lockname") {
        global $db;
        return $db->GetOne("SELECT RELEASE_LOCK(?)", array($named_lock));
    }

    /**
     * This function will search an array for all occurrences of a string and return an array with those locations
     * @param array $haystack
     * @param string $needle
     * @return array
     */
    public static function strpos_all($haystack, $needle) {
        $offset = 0;
        $allpos = array();
        while (($pos = strpos($haystack, $needle, $offset)) !== FALSE) {
            $offset   = $pos + 1;
            $allpos[] = $pos;
        }
        return $allpos;
    }
}
