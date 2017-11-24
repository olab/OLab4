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
 * This file is used to copy an existing evaluation form.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
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
    $url = ENTRADA_URL."/admin/gradebook?section=view&id=".((int)$COURSE_ID);
    if ($COURSE_ID) {
        $query = "SELECT * FROM `courses`
					WHERE `course_id` = ".$db->qstr($COURSE_ID)."
					AND `course_active` = '1'";
        $course_details	= $db->GetRow($query);
        if (isset($_POST["course_list"]) && ($tmp_input = clean_input($_POST["course_list"], array("int")))) {

            $query = "SELECT *
                        FROM `groups`
                        WHERE `group_type` = 'course_list'
                        AND `group_value` = ".$db->qstr($COURSE_ID)."
                        AND `group_active` = '1'
                        AND `group_id` = ".$db->qstr($tmp_input)."
                        ORDER BY `group_name`";
            $course_list = $db->GetRow($query);
            if ($course_list) {
                $group_name = $course_list["group_name"];
                $group_id = $tmp_input;
            } else {
                add_error("Please ensure you select a course list associated with <strong>".html_encode($course_details["course_name"])."</strong> for the audience of the copied assessments.");
            }
        } else {
            if ((isset($_POST["cohort"])) && ($cohort = clean_input($_POST["cohort"], "int"))) {
                $active_cohorts = groups_get_all_cohorts($ENTRADA_USER->getActiveOrganisation());
                foreach ($active_cohorts as $active_cohort) {
                    if ($cohort == $active_cohort["group_id"]) {
                        $group_name = $active_cohort["group_name"];
                        $group_id = $cohort;
                    }
                }
                if (!isset($group_id)) {
                    add_error("Please ensure you select a currently active cohort for the audience of the copied assessments.");
                }
            } else {
                add_error("You must select an <strong>Audience</strong> for this assessment.");
            }
        }
        if (!has_error()) {
            $assessment_ids = array();
            if (isset($_POST["assessment_ids"]) && is_array($_POST["assessment_ids"]) && @count($_POST["assessment_ids"])) {
                foreach ($_POST["assessment_ids"] as $assessment_id) {
                    $assessment_ids[] = $assessment_id;
                }
            }
            if (isset($group_id) && $group_id) {
                if ($assessment_ids) {
                    $assessments_copied = 0;
                    $order = Models_Gradebook_Assessment::fetchNextOrder($COURSE_ID, $group_id);
                    foreach ($assessment_ids as $assessment_id) {
                        $assessment = Models_Gradebook_Assessment::fetchRowByID($assessment_id);
                        $assessment_array = $assessment->toArray();
                        unset($assessment_array["assessment_id"]);
                        unset($assessment_array["release_date"]);
                        unset($assessment_array["release_until"]);
                        $assessment_array["show_learner"] = 0;
                        $assessment_array["order"] = $order;
                        $order++;
                        $assessment_array["cohort"] = $group_id;
                        $assessment = new Models_Gradebook_Assessment($assessment_array);
                        if ($assessment->insert()) {
                            $assessments_copied++;
                            $assessment_objectives = Models_Gradebook_Assessment_Objective::fetchAllByAssessmentID($assessment_id);
                            if ($assessment_objectives && @count($assessment_objectives) >= 1) {
                                foreach ($assessment_objectives as $assessment_objective) {
                                    $assessment_objective_array = $assessment_objective->toArray();
                                    unset($assessment_objective_array["aobjective_id"]);
                                    $assessment_objective_array["assessment_id"] = $assessment->getAssessmentID();
                                    $assessment_objective = new Models_Gradebook_Assessment_Objective($assessment_objective_array);
                                    if (!$assessment_objective->insert()) {
                                        add_error("An issue was encountered while attempting to copy forward the objectives associated with this assessment [".$assessment->getName()."].");
                                        echo display_error();
                                        application_log("error", "Unable to create an assessment objective when copying an assessment. Database said: ".$db->ErrorMsg());
                                    }
                                }
                            }
                            $assessment_options = Models_Gradebook_Assessment_Option::fetchAllByAssessmentID($assessment_id);
                            if ($assessment_options && @count($assessment_options) >= 1) {
                                foreach ($assessment_options as $assessment_option) {
                                    $old_option_id = $assessment_option->getID();
                                    $assessment_option_array = $assessment_option->toArray();
                                    unset($assessment_option_array["aoption_id"]);
                                    $assessment_option_array["assessment_id"] = $assessment->getAssessmentID();
                                    $assessment_option = new Models_Gradebook_Assessment_Option($assessment_option_array);
                                    if ($assessment_option->insert()) {
                                        $assessment_option_values = Models_Gradebook_Assessment_Option_Value::fetchAllByOptionID($old_option_id);
                                        if ($assessment_option_values && @count($assessment_option_values) >= 1) {
                                            foreach ($assessment_option_values as $assessment_option_value) {
                                                $assessment_option_value_array = $assessment_option_value->toArray();
                                                unset($assessment_option_value_array["aovalue_id"]);
                                                $assessment_option_value_array["option_id"] = $assessment_option->getID();
                                                $assessment_option_value = new Models_Gradebook_Assessment_Option_Value($assessment_option_value_array);
                                                if (!$assessment_option_value->insert()) {
                                                    add_error("An issue was encountered while attempting to copy forward details about an option associated with this assessment [".$assessment->getName()."].");
                                                    echo display_error();
                                                    application_log("error", "Unable to create an assessment option value when copying an assessment. Database said: ".$db->ErrorMsg());
                                                }
                                            }
                                        }
                                    } else {
                                        add_error("An issue was encountered while attempting to copy forward an option associated with this assessment [".$assessment->getName()."].");
                                        echo display_error();
                                        application_log("error", "Unable to create an assessment option when copying an assessment. Database said: ".$db->ErrorMsg());
                                    }
                                }
                            }
                        } else {
                            add_error("An issue was encountered while attempting to copy forward an assessment [".$assessment->getName()."].");
                            echo display_error();
                            application_log("error", "Unable to create an assessment when copying. Database said: ".$db->ErrorMsg());
                        }
                    }
                    $url = ENTRADA_URL."/admin/gradebook?section=view&id=".((int)$COURSE_ID)."&cohort=".((int)$group_id);
                    add_success("You have successfully copied ".((int)$assessments_copied)." assessment".($assessments_copied > 1 ? "s" : "")." for <strong>".html_encode($group_name)."</strong>.<br /><br />You will now be redirected to the list of assessments for ".html_encode($group_name)." in this course; this will happen <strong>automatically</strong> in 10 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.");
                    add_notice("Please note that ".($assessments_copied > 1 ? "each of the assessments" : "the assessment")." has been hidden from the learners' gradebook, and any quizzes or events attached to the assessment".($assessments_copied > 1 ? "s" : "")." have been removed.");
                    echo display_success();
                    echo display_notice();
                    $ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 10000)";
                } else {
                    add_error("In order to copy assessments you must provide at least one assessment identifier.<br /><br />You will now be redirected to the list of assessments for this course; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.");

                    echo display_error();

                    application_log("notice", "User failed to provide one or more assessment identifiers to copy assessments.");
                    $ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
                }
            } else {
                add_error("In order to copy assessments you must provide a valid audience.<br /><br />You will now be redirected to the list of assessments for this course; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.");

                echo display_error();

                application_log("notice", "User failed to provide a cohort or course list identifier to copy assessments.");
                $ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
            }
        } else {
            echo display_error();
        }
    } else {
        add_error("In order to copy assessments you must provide a course identifier.<br /><br />You will now be redirected to the list of assessments for this course; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.");

        echo display_error();

        application_log("notice", "User failed to provide a course identifier to copy assessments.");
        $ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
    }
}