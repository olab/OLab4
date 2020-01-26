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
 * @author Unit: School of Medicine
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_GRADEBOOK"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("gradebook", "create", false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	if ($COURSE_ID) {

        $PROCESSED = array();
        $PROCESSED_NOTICE = array();

        $course = Models_Course::fetchRowByID($COURSE_ID);

		$BREADCRUMB[] = array("url" => "", "title" => "Add Drop Box");

        if ((isset($_GET["assessment_id"])) && ($tmp_input = clean_input($_GET["assessment_id"], array("nows", "int")))) {
            $ASSESSMENT_ID = $tmp_input;
        } else {
            $ASSESSMENT_ID = 0;
        }

        if ($ASSESSMENT_ID) {
            $assessment_object = Models_Gradebook_Assessment::fetchRowByID($ASSESSMENT_ID);
            $assessment = $assessment_object->toArray();
            if ($assessment) {
                $assignment = new Models_Assignment(["assessment_id" => $assessment["assessment_id"]]);
                $assignment->fetchRowByAssessmentID();

                if ($assignment->getID()) {
                    add_error("In order to add an Assignment to a Gradebook Assessment you must provide a valid assessment identifier. The provided assessment identifier is already has an assignment.");
                } else {
                    $PROCESSED["assessment_id"] = $assessment["assessment_id"];
                    $PROCESSED["assessment_title"] = $assessment["name"];
                }
            } else {
                add_error("In order to add an Assignment to a Gradebook Assessment you must provide a valid assessment identifier. The provided assessment identifier does not exist.");
            }
        } else {
            add_error("In order to add an Assignment to a Gradebook Assessment you must provide a valid assessment identifier.");
        }

        $cperiod_id = $assessment["cperiod_id"];
        $curriculum_period = Models_Curriculum_Period::fetchRowByID($cperiod_id);

        $period_selector = new Views_Gradebook_Period(array(
            "id" => "select-period",
            "course" => $course,
            "class" => "pull-right content-small margin-top-20",
            "label" => $translate->_("Period:"),
            "curriculum_periods" => $curriculum_period)
        );

        if ($PROCESSED["assessment_id"]) {
            $course_details	= $course->toArray();

            if ($course_details) {
                if ($ENTRADA_ACL->amIAllowed(new GradebookResource($course_details["course_id"], $course_details["organisation_id"]), "update")) {
                    // Error Checking
                    switch($STEP) {
                        case 2 :
                            /**
                             * TODO: Remove assignment titles references
                             */
                            $PROCESSED["assignment_title"] = "";
                            /*
                            if (isset($_POST["assignment_title"]) && $tmp_title = clean_input($_POST["assignment_title"],array("trim","notags"))){
                                $PROCESSED["assignment_title"] = $tmp_title;
                            } else {
                                $ERROR++;
                                $ERRORSTR[] = "Assignment Title is a required Field.";
                            }
                            */

                            if (isset($_POST["assignment_description"]) && $tmp_desc = clean_input($_POST["assignment_description"],array("trim","notags"))){
                                $PROCESSED["assignment_description"] = $tmp_desc;
                            } else {
                                $PROCESSED["assignment_description"] = "";
                            }

                            if (isset($_POST["assignment_uploads"]) && $tmp_uploads = clean_input($_POST["assignment_uploads"],array("trim","int"))){
                                $PROCESSED["assignment_uploads"] = $tmp_uploads;
                            } else {
                                $PROCESSED["assignment_uploads"] = 0;
                            }
                        
                            if (isset($_POST["allow_multiple_files"]) && $_POST["allow_multiple_files"]
                                    && isset($_POST["num_files_allowed"]) && ($max_file_uploads = (int) $_POST["num_files_allowed"]) > 0) {
                                $PROCESSED["max_file_uploads"] = $max_file_uploads;
                            } else {
                                $PROCESSED["max_file_uploads"] = 1;
                            }

                            if (isset($_POST["anonymous_marking"]) && clean_input($_POST["anonymous_marking"], array("trim", "int"))) {
                                $PROCESSED["anonymous_marking"] = 0;
                            } else {
                                $PROCESSED["anonymous_marking"] = 1;
                            }

                            /**
                             * Required field "event_start" / Event Date & Time Start (validated through validate_calendars function).
                             */
                            $release_date = validate_calendars("viewable", false, false,true);
                            $due_date = validate_calendars("due", false, false,true);

                            if ((isset($release_date["start"])) && ((int) $release_date["start"])) {
                                $PROCESSED["release_date"] = (int) $release_date["start"];
                            } else {
                                $PROCESSED["release_date"] = 0;
                            }

                            if ((isset($release_date["finish"])) && ((int) $release_date["finish"])) {
                                $PROCESSED["release_until"] = (int) $release_date["finish"];
                            } else {
                                $PROCESSED["release_until"] = 0;
                            }

                            if (isset($assessment["due_date"]) && intval($assessment["due_date"])) {
                                $PROCESSED["due_date"] = $assessment["due_date"];
                            } else {
                                if ((isset($due_date["finish"])) && ((int)$due_date["finish"])) {
                                    $PROCESSED["due_date"] = (int)$due_date["finish"];
                                } else {
                                    $PROCESSED["due_date"] = 0;
                                }
                            }

                            if (isset($_POST["notice_enabled"]) && $_POST["notice_enabled"]) {
                                $notice_enabled = true;
                                if ((isset($_POST["notice_summary"])) && ($notice_summary = strip_tags(clean_input($_POST["notice_summary"], "trim"), "<a><br><p>"))) {
                                    $PROCESSED_NOTICE["notice_summary"] = $notice_summary;
                                } else {
                                    add_error("You must provide a notice summary.");
                                }
                                if (isset($_POST["custom_notice_display"]) && $_POST["custom_notice_display"]) {
                                    $custom_notice_display = true;
                                    if (isset($_POST["notice_display_start"]) && ($tmp_date = clean_input($_POST["notice_display_start"], array("trim", "notags")))) {
                                        if (isset($_POST["notice_display_start_time"]) && ($tmp_time = clean_input($_POST["notice_display_start_time"], array("trim", "notags")))) {
                                            $PROCESSED_NOTICE["display_from"] = strtotime($tmp_date . " " . $tmp_time);
                                            if (!$PROCESSED_NOTICE["display_from"]) {
                                                add_error("The custom notice display start date you have entered is not valid. Please re-enter the <strong>Notice Release Start</strong> to continue.");
                                            }
                                        } else {
                                            add_error("You chose to enter a custom notice display start date, but never entered a time for the <strong>Notice Release Start</strong>. Please enter a time to continue.");
                                        }
                                    } else {
                                        add_error("You chose to enter a custom notice display start date, but never entered a <strong>Notice Release Start</strong>. Please enter a date to continue.");
                                    }
                                    if (isset($_POST["notice_display_finish"]) && ($tmp_date = clean_input($_POST["notice_display_finish"], array("trim", "notags")))) {
                                        if (isset($_POST["notice_display_finish_time"]) && ($tmp_time = clean_input($_POST["notice_display_finish_time"], array("trim", "notags")))) {
                                            $PROCESSED_NOTICE["display_until"] = strtotime($tmp_date . " " . $tmp_time);
                                            if (!$PROCESSED_NOTICE["display_until"]) {
                                                add_error("The custom notice display finish date you have entered is not valid. Please re-enter the <strong>Notice Release Finish</strong> to continue.");
                                            }
                                        } else {
                                            add_error("You chose to enter a custom notice display finish date, but never entered a time for the <strong>Notice Release Finish</strong>. Please enter a time to continue.");
                                        }
                                    } else {
                                        add_error("You chose to enter a custom notice display finish date, but never entered a <strong>Notice Release Finish</strong>. Please enter a date to continue.");
                                    }
                                } else {
                                    $custom_notice_display = false;
                                    $PROCESSED_NOTICE["display_from"] = ($PROCESSED["release_date"] ? $PROCESSED["release_date"] : time());
                                    $PROCESSED_NOTICE["display_until"] = $PROCESSED_NOTICE["display_from"] + 604800; //One week in seconds
                                }
                            } else {
                                $notice_enabled = false;
                            }
                            if (!$ERROR){

                                $PROCESSED["updated_date"] = time();
                                $PROCESSED["updated_by"] = $ENTRADA_USER->getID();
                                $PROCESSED["course_id"] = $COURSE_ID;
                                $PROCESSED["assignment_active"] = 1;

                                if ($db->AutoExecute("assignments", $PROCESSED, "INSERT")) {
                                    if ($ASSIGNMENT_ID = $db->Insert_Id()) {

                                        $PROCESSED["assignment_id"] = $ASSIGNMENT_ID;
                                        $PROCESSED["proxy_id"] = $ENTRADA_USER->getID();
                                        $PROCESSED["contact_order"] = 0;
                                        $PROCESSED["updated_date"]	= time();
                                        $PROCESSED["updated_by"] = $ENTRADA_USER->getID();
                                        if ($db->AutoExecute("assignment_contacts", $PROCESSED, "INSERT")) {
                                            $course_audience = new Models_Course_Audience();
                                            $audience_records = $course_audience->fetchAllByCourseIDCperiodID($course->getID(), $cperiod_id);
                                            /**
                                             * only process notices if there is a course audience to receive them :)
                                             */
                                            if ($notice_enabled && $audience_records) {
                                                $PROCESSED_NOTICE["target"] = "updated";
                                                $PROCESSED_NOTICE["organisation_id"] = $course_details["organisation_id"];
                                                $PROCESSED_NOTICE["updated_date"] = time();
                                                $PROCESSED_NOTICE["updated_by"] = $ENTRADA_USER->getID();
                                                $PROCESSED_NOTICE["created_by"] = $ENTRADA_USER->getID();
                                                $PROCESSED_NOTICE_AUDIENCE = array();
                                                $search = array(
                                                    "%assignment_submission_url%",
                                                    "%assignment_title%",
                                                    "%course_code%",
                                                    "%course_name%",
                                                    "%due_date%",
                                                    "%assignment_description%"
                                                );
                                                $replace = array(
                                                    ENTRADA_URL . "/profile/gradebook/assignments?section=view&assignment_id=" . $ASSIGNMENT_ID,
                                                    html_encode($PROCESSED["assignment_title"]),
                                                    html_encode($course_details["course_code"]),
                                                    html_encode($course_details["course_name"]),
                                                    ($PROCESSED["due_date"] ? date("l, F jS, Y", $PROCESSED["due_date"]) : "No due date provided"),
                                                    (isset($PROCESSED["assignment_description"]) && $PROCESSED["assignment_description"] ? nl2br(html_encode($PROCESSED["assignment_description"])) : "No assignment description provided")
                                                );
                                                $PROCESSED_NOTICE["notice_summary"] = str_ireplace($search, $replace, $PROCESSED_NOTICE["notice_summary"]);
                                                if ($db->AutoExecute("notices", $PROCESSED_NOTICE, "INSERT") && $NOTICE_ID = $db->Insert_Id()) {
                                                    $PROCESSED["notice_id"] = $NOTICE_ID;
                                                    // update the assignment with the notice ID, now that we have it.
                                                    if ($db->AutoExecute("assignments", $PROCESSED, "UPDATE", "`assignment_id` = ".$db->qstr($ASSIGNMENT_ID))) {
                                                        $PROCESSED_NOTICE_AUDIENCE["notice_id"] = $NOTICE_ID;
                                                        $PROCESSED_NOTICE_AUDIENCE["updated_by"] = $ENTRADA_USER->getID();
                                                        $PROCESSED_NOTICE_AUDIENCE["updated_date"] = time();
                                                        // course audience may contain multiple records. Put each of them into the notice audience, with the corresponding type
                                                        foreach ($audience_records as $audience) {
                                                            $PROCESSED_NOTICE_AUDIENCE["audience_type"] = "";
                                                            $PROCESSED_NOTICE_AUDIENCE["audience_value"] = $audience->getAudienceValue();
                                                            if ($audience->getAudienceType() == "proxy_id") {
                                                                $PROCESSED_NOTICE_AUDIENCE["audience_type"] = "students";
                                                            }
                                                            if ($audience->getAudienceType() == "group_id") {
                                                                // is it a cohort or a class list?
                                                                $group = Models_Group::fetchRowByID($audience->getAudienceValue());
                                                                if ($group) {
                                                                    $PROCESSED_NOTICE_AUDIENCE["audience_type"] = $group->getGroupType();
                                                                }
                                                            }
                                                            if (!empty($PROCESSED_NOTICE_AUDIENCE["audience_type"])) {
                                                                if (!$db->AutoExecute("notice_audience", $PROCESSED_NOTICE_AUDIENCE, "INSERT")) {
                                                                    application_log("error", "An error was encountered while attempting to create a `notice_audience` record for a new assignment [" . $ASSIGNMENT_ID . "] notice [" . $NOTICE_ID . "]. DB Said: " . $db->ErrorMsg());
                                                                }
                                                            } else {
                                                                application_log("error", "An error was encountered while attempting to create a `notice_audience` record for a new assignment [" . $ASSIGNMENT_ID . "] notice [" . $NOTICE_ID . "]. DB Said: " . $db->ErrorMsg());
                                                            }
                                                        }
                                                    } else {
                                                        application_log("error", "An error was encountered while attempting to set the `notice_id` field for a new assignment [".$ASSIGNMENT_ID."] after creating a notice [".$NOTICE_ID."] for it. DB Said: ".$db->ErrorMsg());
                                                    }
                                                } else {
                                                    application_log("error", "An error was encountered while attempting to create a `notice` record for a new assignment [".$ASSIGNMENT_ID."]. DB Said: ".$db->ErrorMsg());
                                                }
                                            }
                                            if ((isset($_POST["associated_director"])) && ($associated_directors = explode(",", $_POST["associated_director"])) && (@is_array($associated_directors)) && (@count($associated_directors))) {
                                                $order = 0;
                                                foreach ($associated_directors as $proxy_id) {
                                                    if ($proxy_id = clean_input($proxy_id, array("trim", "int"))) {
                                                        if ($proxy_id != $ENTRADA_USER->getID()){
                                                            if (!$db->AutoExecute("assignment_contacts", array("assignment_id" => $ASSIGNMENT_ID, "proxy_id" => $proxy_id, "contact_order" => $order+1, "updated_date"=>time(),"updated_by"=>$ENTRADA_USER->getID()), "INSERT")) {
                                                                add_error("There was an error when trying to insert a &quot;" . $translate->_("course") . " Director&quot; into the system. The system administrator was informed of this error; please try again later.");

                                                                application_log("error", "Unable to insert a new course_contact to the database when updating an event. Database said: ".$db->ErrorMsg());
                                                            } else {
                                                                $order++;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                            application_log("success", "Successfully added assignment ID [".$ASSIGNMENT_ID."].");
                                        } else {
                                            application_log("error", "Unable to add you as an assignment contact. Please contact a system administrator.");
                                        }
                                    } else {
                                        application_log("error", "Unable to fetch the newly inserted assignment identifier for this assignment.");
                                    }

                                    $url = ENTRADA_URL."/admin/gradebook/assessments?".replace_query(array("section" => "grade", "assessment_id" => $ASSESSMENT_ID, "step" => false));
                                    $msg = "You will now be redirected to the <strong>Grading Assessment</strong> page for \"<strong>" . $PROCESSED["assessment_title"] . "</strong>\"; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";

                                    $SUCCESS++;
                                    $SUCCESSSTR[] 	= $msg;
                                    $ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000)";
                                } else {
                                    application_log("error", "Unable to fetch the newly inserted assignment identifier for this assignment.");
                                }
                            }
                            if ($ERROR) {
                                $STEP = 1;
                            }
                        break;
                        case 1 :

                        default :
                            continue;
                        break;
                    }

                    // Display Content
                    switch ($STEP) {
                        case 2 :
                            if ($SUCCESS) {
                                echo display_success();
                            }
                            if ($NOTICE) {
                                echo display_notice();
                            }
                            if ($ERROR) {
                                echo display_error();
                            }
                        break;
                        case 1 :
                        default :
                            $assignment_directors = array();
                            $query	= "	SELECT `".AUTH_DATABASE."`.`user_data`.`id` AS `proxy_id`, CONCAT_WS(', ', `".AUTH_DATABASE."`.`user_data`.`lastname`, `".AUTH_DATABASE."`.`user_data`.`firstname`) AS `fullname`, `".AUTH_DATABASE."`.`organisations`.`organisation_id`
                                        FROM `".AUTH_DATABASE."`.`user_data`
                                        LEFT JOIN `".AUTH_DATABASE."`.`user_access`
                                        ON `".AUTH_DATABASE."`.`user_access`.`user_id` = `".AUTH_DATABASE."`.`user_data`.`id`
                                        LEFT JOIN `".AUTH_DATABASE."`.`organisations`
                                        ON `".AUTH_DATABASE."`.`user_data`.`organisation_id` = `".AUTH_DATABASE."`.`organisations`.`organisation_id`
                                        WHERE
                                        (
                                            `".AUTH_DATABASE."`.`user_access`.`group` = 'faculty' OR
                                            `".AUTH_DATABASE."`.`user_access`.`group` = 'staff' OR
                                            (
                                                `".AUTH_DATABASE."`.`user_access`.`group` = 'resident'
                                                AND `".AUTH_DATABASE."`.`user_access`.`role` = 'lecturer'
                                            )
                                            OR `".AUTH_DATABASE."`.`user_access`.`group` = 'medtech'
                                        )
                                        AND `".AUTH_DATABASE."`.`user_access`.`app_id` = '".AUTH_APP_ID."'
                                        AND `".AUTH_DATABASE."`.`user_access`.`account_active` = 'true'
                                        AND `".AUTH_DATABASE."`.`user_access`.`organisation_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation()) . "
                                        ORDER BY `fullname` ASC";
                            $results = ((USE_CACHE) ? $db->CacheGetAll(AUTH_CACHE_TIMEOUT, $query) : $db->GetAll($query));
                            if ($results) {
                                foreach ($results as $result) {
                                    $assignment_directors[$result["proxy_id"]] = array('proxy_id'=>$result["proxy_id"], 'fullname'=>$result["fullname"], 'organisation_id'=>$result['organisation_id']);
                                }
                                $DIRECTOR_LIST = $assignment_directors;
                            }

                            /**
                             * Non-required field "associated_faculty" / Associated Faculty (array of proxy ids).
                             * This is actually accomplished after the event is inserted below.
                             */
                            if ((isset($_POST["associated_director"]))) {
                                $associated_director = explode(',', $_POST["associated_director"]);
                                foreach($associated_director as $contact_order => $proxy_id) {
                                    if ($proxy_id = clean_input($proxy_id, array("trim", "int")) && array_key_exists($proxy_id, $DIRECTOR_LIST)) {
                                        $chosen_course_directors[(int) $contact_order] = $proxy_id;
                                    }
                                }
                            }

                            /**
                             * Load the rich text editor.
                             */
                            load_rte("minimal");
                            $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.timepicker.js\"></script>\n";
                            $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/gradebook/assignments.js\"></script>\n";

                            if ($ERROR) {
                                echo display_error();
                            }
                            ?>
                            <script type="text/javascript">
                                var ENTRADA_URL = '<?php echo ENTRADA_URL; ?>';
                                var COURSE_ID = '<?php echo $COURSE_ID; ?>';
                                var CPERIOD_ID = '<?php echo $cperiod_id; ?>';
                                var ASSIGN_TO_GRADER_TEXT = '<?php echo $translate->_("Assign to Grader"); ?>'; 
                            </script>

                            <?php
                            $page_title = $translate->_("Add Assignment Drop Box");
                            $page_header = new Views_Gradebook_PageHeader(array("course" => $course, "module" => "gradebook", "page_title" => $page_title));
                            $page_header->render();

                            ?>
                            <div class="row-fluid">
                                <div class="span8">
                                    <h1><?php echo $page_title; ?></h1>
                                </div>
                                <div class="span4">
                                    <?php $period_selector->render(); ?>
                                </div>
                            </div>
                            <form id="assignment-form" action="<?php echo ENTRADA_URL; ?>/admin/gradebook/assignments?<?php echo replace_query(array("step" => 2)); ?>" method="post" class="form-horizontal">
                                <h2><?php echo $translate->_("Assignment Drop Box"); ?></h2>

                                <div class="control-group">
                                    <label class="control-label form-nrequired" style="text-align: left;"><?php echo $translate->_("Submission Options");?>:</label>
                                    <div class="controls">
                                        <label for="allow_multiple_files" class="pad-above-small"><input type="checkbox" name="allow_multiple_files" value="1" id="allow_multiple_files"<?php echo (isset($PROCESSED["allow_multiple_files"]) && $PROCESSED["allow_multiple_files"]  ? " checked=\"checked\"" : ""); ?>/> <?php echo $translate->_("Allow learners to upload <strong>more than one file</strong>."); ?></label><br />
                                        <label for="notice_enabled" class="pad-above-small"><input type="checkbox" name="notice_enabled" value="1" id="notice_enabled"<?php echo (isset($notice_enabled) && $notice_enabled ? " checked=\"checked\"" : "" ); ?>/> <?php echo $translate->_("Add a <strong>dashboard notice</strong> for learners who are required to submit."); ?></label><br />
                                        <label for="assignment_uploads" class="pad-above-small"><input type="checkbox" name="assignment_uploads" value="1" id="assignment_uploads"<?php echo (!isset($PROCESSED["assignment_uploads"]) || $PROCESSED["assignment_uploads"] ? " checked=\"checked\"" : ""); ?>/> <?php echo $translate->_("Allow learners to upload <strong>new revisions</strong> after their initial upload."); ?></label><br />
                                        <label for="anonymous_marking" class="pad-above-small"><input type="checkbox" name="anonymous_marking" value="1" id="anonymous_marking"<?php echo (isset($PROCESSED["anonymous_marking"]) && $PROCESSED["anonymous_marking"] ? "" : " checked=\"checked\""); ?>/> <?php echo $translate->_("Allow graders to see the name of the learner they are grading."); ?></label><br />
                                    </div>
                                </div>

                                <div class="control-group" id="num_files_allowed_wrapper"<?php echo isset($_POST["allow_multiple_files"]) ? "" : " style=\"display: none\""; ?>>
                                    <label for="num_file_allowed" class="form-required control-label"><?php echo $translate->_("Max Files Allowed"); ?>:</label>
                                    <div class="controls">
                                        <input type="text" name="num_files_allowed" id="num_files_allowed" value="<?php echo isset($PROCESSED["max_file_uploads"]) ? (int) $PROCESSED["max_file_uploads"] : 3; ?>" class="span1" maxlength="3" />
                                        <span class="help-inline content-small"><?php echo $translate->_("The maximum number of files a learner can upload."); ?></span>
                                    </div>
                                </div>

                                <div class="control-group" id="notice-dates"<?php echo (isset($notice_enabled) && $notice_enabled ? "" : " style=\"display: none;\"" ); ?>>
                                    <label for="notice_summary" class="form-required control-label"><?php echo $translate->_("Dashboard Notice"); ?>:</label>
                                    <div class="controls">
                                        <textarea id="notice_summary" name="notice_summary" rows="10"><?php echo ((isset($PROCESSED_NOTICE["notice_summary"])) ? html_encode(trim($PROCESSED_NOTICE["notice_summary"])) : $translate->_("assignment_notice")); ?></textarea>
                                    </div>
                                    <div class="content-small controls space-below">
                                        <strong><?php echo $translate->_("Available Variables"); ?>:</strong> %assignment_submission_url%, %assignment_title%, %course_code%, %course_name%, %due_date%, %assignment_title%, %assignment_description%
                                    </div>

                                    <label class="control-label form-nrequired">Release Dates:</label>
                                    <div class="controls">
                                        <label class="radio" for="notice_display_default">
                                            <input type="radio" value="0" name="custom_notice_display" id="notice_display_default" onclick="jQuery('#custom_notice_display_date').hide()" <?php echo (!isset($custom_notice_display) || !$custom_notice_display ? "checked=\"checked\" " : ""); ?>/>
                                            <?php echo $translate->_("Release notice on <strong>Viewable Start</strong> (immediately if no date set), for one week."); ?>
                                        </label>

                                        <label class="radio" for="custom_notice_display">
                                            <input type="radio" value="1" name="custom_notice_display" id="custom_notice_display" onclick="jQuery('#custom_notice_display_date').show()" <?php echo (isset($custom_notice_display) && $custom_notice_display ? "checked=\"checked\" " : ""); ?>/>
                                            <?php echo $translate->_("Release notice on a custom defined date for a specified period of time."); ?>
                                        </label>

                                        <div id="custom_notice_display_date"<?php echo (!isset($custom_notice_display) || !$custom_notice_display ? " style=\"display: none;\"" : "" ); ?>>
                                            <div class="row-fluid">
                                                <label class="span3 offset1" for="notice_display_start"><?php echo $translate->_("Notice Display Start"); ?>:</label>
                                                <span class="span8">
                                                    <div class="input-append">
                                                        <input type="text" class="input-small datepicker" value="<?php echo (isset($PROCESSED_NOTICE["display_from"]) && $PROCESSED_NOTICE["display_from"] ? date("Y-m-d", $PROCESSED_NOTICE["display_from"]) : ""); ?>" name="notice_display_start" id="notice_display_start" />
                                                        <span class="add-on pointer"><i class="icon-calendar"></i></span>
                                                    </div>
                                                    <div class="input-append">
                                                        <input type="text" class="input-mini timepicker" value="<?php echo (isset($PROCESSED_NOTICE["display_from"]) && $PROCESSED_NOTICE["display_from"] ? date("H:i", $PROCESSED_NOTICE["display_from"]) : ""); ?>" name="notice_display_start_time" id="notice_display_start_time" />
                                                        <span class="add-on pointer inpage-add-on"><i class="icon-time"></i></span>
                                                    </div>
                                                </span>
                                            </div>
                                            <div class="row-fluid">
                                                <label class="span3 offset1" for="notice_display_finish"><?php echo $translate->_("Notice Display Finish"); ?>:</label>
                                                <span class="span8">
                                                    <div class="input-append">
                                                        <input type="text" class="input-small datepicker" value="<?php echo (isset($PROCESSED_NOTICE["display_until"]) && $PROCESSED_NOTICE["display_until"] ? date("Y-m-d",  $PROCESSED_NOTICE["display_until"]) : ""); ?>" name="notice_display_finish" id="notice_display_finish" />
                                                        <span class="add-on pointer"><i class="icon-calendar"></i></span>
                                                    </div>
                                                    <div class="input-append">
                                                        <input type="text" class="input-mini timepicker" value="<?php echo (isset($PROCESSED_NOTICE["display_until"]) && $PROCESSED_NOTICE["display_until"] ? date("H:i", $PROCESSED_NOTICE["display_until"]) : ""); ?>" name="notice_display_finish_time" id="notice_display_finish_time" />
                                                        <span class="add-on pointer inpage-add-on"><i class="icon-time"></i></span>
                                                    </div>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                if (isset($assessment["due_date"]) && intval($assessment["due_date"])) { ?>
                                    <label  class="control-label"><?php echo $translate->_("Submission Due Date"); ?>:</label>
                                    <div class="controls" style="padding-top: 5px;">
                                        <?php echo date("Y-m-d h:i A", $assessment["due_date"]); ?> <i><?php echo $translate->_("(From assessment due date)");?></i>
                                    </div>
                                <?php } else { ?>
                                    <div class="control-group">
                                        <table>
                                            <?php echo generate_calendars("due", $translate->_("Submission Due Date:"), false, false, 0, true, true, ((isset($PROCESSED["due_date"])) ? $PROCESSED["due_date"] : 0), true, false, "", ""); ?>
                                        </table>
                                    </div>
                                <?php } ?>

                                <div class="control-group">
                                    <h2>Time Release Options</h2>
                                    <table>
                                        <?php echo generate_calendars("viewable", "", true, false, ((isset($PROCESSED["release_date"])) ? $PROCESSED["release_date"] : 0), true, false, ((isset($PROCESSED["release_until"])) ? $PROCESSED["release_until"] : 0)); ?>
                                    </table>
                                </div>
                                <div style="padding-top: 25px">
                                    <table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
                                        <tr>
                                            <td style="width: 25%; text-align: left">
                                                <input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/gradebook?<?php echo replace_query(array("step" => false, "section" => "view", "assessment_id" => false)); ?>'" />
                                            </td>
                                            <td style="width: 75%; text-align: right; vertical-align: middle">
                                                <input type="submit" class="btn btn-primary" value="Save" />
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </form>


                            <?php
                        break;
                    }
                } else {
                    $ERROR++;
                    $ERRORSTR[] = $translate->_("In order to add an assignment to a gradebook you must provide a valid course identifier. The provided ID does not exist in this system.");

                    echo display_error();

                    application_log("notice", $translate->_("Failed to provide a valid course identifier when attempting to add an assignment"));
                }
            } else {

            }
        } else {
            echo display_error();
            
            application_log("notice", $translate->_("Failed to provide assessment identifier when attempting to add an assignment"));
        }
	} else {
		$ERROR++;
		$ERRORSTR[] = $translate->_("In order to add an assignment to a gradebook you must provide a valid course identifier. The provided ID does not exist in this system.");

		echo display_error();

		application_log("notice", $translate->_("Failed to provide course identifier when attempting to add an assignment"));
	}
}