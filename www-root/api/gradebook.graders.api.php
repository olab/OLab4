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
 * Primary controller file for the Events module.
 * /admin/events
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

if((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("gradebook", "update", false)) {
    $ERROR++;
    $ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    define("IN_GRADEBOOK", true);

    $request = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));

    $request_var = "_" . $request;

    $method = clean_input(${$request_var}["method"], array("trim", "striptags"));

    if (isset(${$request_var}["assessment_id"]) && $tmp_input = clean_input(${$request_var}["assessment_id"], "int")) {
        $assessment_id = $tmp_input;
    }

    if (isset(${$request_var}["cperiod_id"]) && $tmp_input = clean_input(${$request_var}["cperiod_id"], "int")) {
        $cperiod_id = $tmp_input;
    }

    switch ($request) {
        case "POST" :
            switch ($method) {
                 case "delete_grader" :
                    if (isset(${$request_var}["grader_id"]) && $tmp_input = clean_input(${$request_var}["grader_id"], "int")) {
                        $grader_id = $tmp_input;
                    }

                    if (Models_Gradebook_Assessment_Graders::deleteGraderForAssessment($grader_id, $assessment_id)) {
                        echo json_encode(array("status" => "success", "data" => ""));
                    } else {
                        echo json_encode(array("status" => "error", "data" => "There was an error while removing the specified grader from the assessment."));
                    }
                    break;
            }
            break;
        case "GET" :
            switch ($method) {
                case "grader_list" :
                    if (isset(${$request_var}["course_id"]) && $tmp_input = clean_input(${$request_var}["course_id"], "int")) {
                        $course_id = $tmp_input;
                    }

                    $graders = Models_Gradebook_Assessment_Graders::fetchGradersForGradersList($assessment_id, $course_id);
                    $graders_list = new Views_Gradebook_Assessments_Graders_List(array(
                        "assessment_id" => $assessment_id,
                        "graders" => $graders
                    ));

                    $graders_list->render();
                    break;
                case "get_learners" :
                    if (isset(${$request_var}["grader_id"]) && $tmp_input = clean_input(${$request_var}["grader_id"], "int")) {
                        $grader_id = $tmp_input;
                    }
                    
                    $learners_ids = Models_Gradebook_Assessment_Graders::fetchLearnersByAssessmentGrader($assessment_id, $grader_id);
                    $learners = array();
                    foreach ($learners_ids as $learner_id) {
                        $learner = Models_User::fetchRowByID($learner_id);
                        $learners[] = $learner->getFullname();
                    }
                    echo json_encode(array("status" => "success", "data" => $learners));

                    break;

                case "get_groups" :

                    $PROCESSED = Entrada_Utilities::getCleanUrlParams(array("course_id" => "int", "cperiod_id" => "int"));

                    $course_audience_model = new Models_Course_Audience(array("course_id" => $PROCESSED["course_id"], "cperiod_id" => $PROCESSED["cperiod_id"]));
                    $groups = $course_audience_model->getAllWithGroupNameByCourseID();

                    echo json_encode(array("status" => "success", "data" => $groups));

                    break;

                case "get_assigned_learners":
                    $student_ids = Models_Gradebook_Assessment_Graders::fetchLearnersProxyIdByAssessmentGrader($assessment_id);

                    if ($student_ids) {
                        echo json_encode(array("status" => "success", "data" => $student_ids));
                    } else {
                        echo json_encode(array("status" => "error"));
                    }

                    break;

            }
        break;
    }
}