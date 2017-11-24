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
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
if (!defined("IN_ASSESSMENTS_REPORTS")) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("academicadvisor", "read", false)) {
    $ONLOAD[] = "setTimeout('window.location=\\'" . ENTRADA_URL . "/" . $MODULE . "\\'', 15000)";
    $ERROR++;
    $ERRORSTR[] = "Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:" . html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" . html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance.";
    echo display_error();
    application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] do not have access to this module [" . $MODULE . "]");
} else {
    $validated_inputs = false;
    $specified_role = null;
    $specified_proxy_id = 0;
    $specified_cperiod_id = 0;
    $specified_form_id = 0;
    $specified_distribution_id = 0;

    // Get our $_GET variables
    if (isset($_GET["target_id"]) && ($tmp_input = clean_input($_GET["target_id"], array("trim", "int")))) {
        $specified_proxy_id = $tmp_input;
    }
    if (isset($_GET["adistribution_id"]) && ($tmp_input = clean_input($_GET["adistribution_id"], array("trim", "int")))) {
        $specified_distribution_id = $tmp_input;
    }
    if (isset($_GET["cperiod_id"]) && ($tmp_input = clean_input($_GET["cperiod_id"], array("trim", "int")))) {
        $specified_cperiod_id = $tmp_input;
    }
    if (isset($_GET["form_id"]) && ($tmp_input = clean_input($_GET["form_id"], array("trim", "int")))) {
        $specified_form_id = $tmp_input;
    }
    if (isset($_GET["role"]) && ($tmp_input = clean_input($_GET["role"], array("trim", "notags")))) {
        $specified_role = $tmp_input;
    }
    if (isset($_GET["start-date"]) && $tmp_input = clean_input($_GET["start-date"], array("nows", "notags"))) {
        $date_format = DateTime::createFromFormat("Y-m-d", $tmp_input);
        if ($date_format && $date_format->format("Y-m-d") === $tmp_input) {
            $PROCESSED["start-date"] = strtotime($tmp_input);
        } else {
            $PROCESSED["start-date"] = null;
        }
    } else {
        $PROCESSED["start-date"] = null;
    }
    if (isset($_GET["end-date"]) && $tmp_input = clean_input($_GET["end-date"], array("nows", "notags"))) {
        $date_format = DateTime::createFromFormat("Y-m-d", $tmp_input);
        if ($date_format && $date_format->format("Y-m-d") === $tmp_input) {
            $PROCESSED["end-date"] = strtotime($tmp_input);
        } else {
            $PROCESSED["end-date"] = null;
        }
    } else {
        $PROCESSED["end-date"] = null;
    }

    // Perform simple validation on them.
    if (($specified_role == "learner" ||
            $specified_role == "faculty") &&
        $specified_proxy_id &&
        $specified_form_id) {
        $validated_inputs = true;
    }

    // Attempt to render.
    if ($validated_inputs) {
        $assessment_user = Models_User::fetchRowByID($specified_proxy_id);
        $specified_form = Models_Assessments_Form::fetchRowByID($specified_form_id);
        if ($assessment_user && $specified_form) {
            if ($specified_role == "faculty") {
                $override_permission = Entrada_Utilities_Assessments_AssessmentTask::getFacultyAccessOverrideByCourseFacultyOrWhitelist($ENTRADA_USER, $specified_proxy_id);
            } else {
                $override_permission = null;
            }
            if (Entrada_Utilities_Assessments_Reports::hasReportAccess($ENTRADA_ACL, $ENTRADA_USER, $specified_proxy_id, $specified_role, $override_permission)) {
                $breadcrumb_title = sprintf($translate->_("Assessments List For %s For %s"), $specified_form->getTitle(), "{$assessment_user->getFirstname()} {$assessment_user->getLastname()}");
                $BREADCRUMB[] = array("url" => "", "title" => html_encode($breadcrumb_title));
                $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = '" . ENTRADA_URL . "';</script>";
                $HEAD[] = "<script type=\"text/javascript\">var proxy_id = '" . $specified_proxy_id . "';</script>";
                $HEAD[] = "<script type=\"text/javascript\">sidebarBegone();</script>";
                $JQUERY[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/assessment-index.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
                $HEAD[] = "<link href=\"" . ENTRADA_URL . "/css/assessments/assessment-public-index.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";

                $group_by_distribution = Entrada_Utilities_Assessments_Reports::getPreferenceFromSession("group_by_distribution");
                $distribution = false;
                $distribution_name = false;

                // Create the reporting utility object
                $construction = array(
                    "organisation_id" => $ENTRADA_USER->getActiveOrganisation(), // Limit to this org
                    "target_value" => $specified_proxy_id, // Limit to this user
                    "target_type" => "proxy_id",
                    "form_id" => $specified_form_id, // Limit to this form
                    "group_by_distribution" => $group_by_distribution,
                    "cperiod_id" => $specified_cperiod_id,
                    "start_date" => $PROCESSED["start-date"],
                    "end_date" => $PROCESSED["end-date"],
                    "course_id" => Models_Course::getActiveUserCoursesIDList()
                );
                if ($specified_distribution_id) {
                    $construction["adistribution_id"] = $specified_distribution_id; // optionally limit to this distribution
                    // Fetch distribution name to display on header
                    if ($distribution = Models_Assessments_Distribution::fetchRowByID($specified_distribution_id)) {
                        $distribution_name = $distribution->getTitle();
                    }
                }
                $reporting_utility = new Entrada_Utilities_Assessments_Reports($construction);

                // Fetch the list of completed assessments
                $completed_assessments = $reporting_utility->fetchCompletedAssessmentsList();

                // Draw the header
                $header_view = new Views_Assessments_Reports_Header();
                $header_view->render(
                    array(
                        "target_name" => "{$assessment_user->getFirstname()} {$assessment_user->getLastname()}",
                        "form_name" => $specified_form->getTitle(),
                        "distribution_name" => $distribution_name,
                        "use_assessments_title" => true
                    )
                );

                // Draw assessments list
                $assessment_table = new Views_Assessments_Reports_AssessmentsTable();
                $assessment_table->render(
                    array(
                        "completed_assessments" => $completed_assessments,
                        "form_name" => $specified_form->getTitle(),
                        "target_name" => "{$assessment_user->getFirstname()} {$assessment_user->getLastname()}",
                    )
                );

            } else {
                add_error($translate->_("Unfortunately, you do not have permission to access this report."));
                echo display_error();
            }
        } else {
            add_error($translate->_("Please ensure you have specified a valid user and form."));
            echo display_error();
        }
    } else {
        add_error($translate->_("Please ensure you provide a valid user id."));
        echo display_error();
    }
}