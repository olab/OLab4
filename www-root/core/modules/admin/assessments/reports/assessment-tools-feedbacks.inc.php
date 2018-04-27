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
 * Reports on Assessment tools feedbacks
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_ASSESSMENTS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $BREADCRUMB[] = array("url" => "", "title" => $translate->_("Assessment Tools Feedback Report"));
    $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = '" . ENTRADA_URL . "';</script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.timepicker.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/tools-feedbacks-report.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/assessments/assessment-tool-feedback.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
    $HEAD[] = Entrada_Utilities_jQueryHelper::addjQuery();
    $HEAD[] = Entrada_Utilities_jQueryHelper::addjQueryLoadTemplate();

    $options = array();

    if (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["assessment_tools_feedback_report"]["start_date"])) {
        $options['start_date'] = $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["assessment_tools_feedback_report"]["start_date"];
    }

    if (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["assessment_tools_feedback_report"]["end_date"])) {
        $options['end_date'] = $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["assessment_tools_feedback_report"]["end_date"];
    }

    if (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["assessment_tools_feedback_report"]["courses"])) {
        $options['courses'] = $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["assessment_tools_feedback_report"]["courses"];
    }

    if (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["assessment_tools_feedback_report"]["tools"])) {
        $tools = Models_Assessments_Form::fetchFormsTitleByFormIDs($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["assessment_tools_feedback_report"]["tools"]);
        $options['tools'] = $tools;
    }

    $courses = array();
    $course_model = new Models_Course();
    if (array_key_exists("courses", $options) && is_array($options["courses"])) {
        foreach ($options["courses"] as $course_id) {
            if ($course_id != 0) {
                $course = $course_model->fetchRowByID($course_id);
                if ($course) {
                    $courses[] = $course;
                }
            }
        }
    }

    $options["courses"] = $courses;
    ?>
    <h1><?php echo $translate->_("Assessment Tools Feedback Report"); ?></h1>
    <div id="msgs"></div>
    <?php

    $report_view = new Views_Assessments_Reports_AssessmentToolsFeedbacksReport();
    $report_view->render($options);
}