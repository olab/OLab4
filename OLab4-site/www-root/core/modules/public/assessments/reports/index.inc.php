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
 * This is the pseudo-controller page for the assessment reports page.
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
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "read", false)) {
    $ONLOAD[] = "setTimeout('window.location=\\'" . ENTRADA_URL . "/" . $MODULE . "\\'', 15000)";
    $ERROR++;
    $ERRORSTR[] = "Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:" . html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" . html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance.";
    echo display_error();
    application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] do not have access to this module [" . $MODULE . "]");
} else {
    $validated_inputs = false;
    $specified_proxy_id = 0;
    $specified_cperiod_id = 0;
    $specified_target_role = "learner";

    // Get our $_GET variables
    if (isset($_GET["proxy_id"]) && ($tmp_input = clean_input($_GET["proxy_id"], array("trim", "int")))) {
        $specified_proxy_id = $tmp_input;
    }
    if (isset($_GET["cperiod_id"]) && ($tmp_input = clean_input($_GET["cperiod_id"], array("trim", "int")))) {
        $specified_cperiod_id = $tmp_input;
    }
    if (isset($_GET["role"]) && ($tmp_input = clean_input($_GET["role"], array("trim", "notags", "lowercase")))) {
        $specified_target_role = $tmp_input;
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

    // Make sure proxy exists. Course is optional (will be inferred)
    if ($specified_proxy_id && ($specified_target_role == "learner" || $specified_target_role == "faculty" || $specified_target_role == "target")) {
        $validated_inputs = true;
    }

    // Attempt to render
    if ($validated_inputs) {
        $assessment_user = Models_User::fetchRowByID($specified_proxy_id);
        if ($assessment_user) {
            if ($specified_target_role == "faculty") {
                $override_permission = Entrada_Utilities_Assessments_DeprecatedAssessmentTask::getFacultyAccessOverrideByCourseFacultyOrWhitelist($ENTRADA_USER, $specified_proxy_id);
            } else {
                $override_permission = null;
            }
            if (Entrada_Utilities_Assessments_Reports::hasReportAccess($ENTRADA_ACL, $ENTRADA_USER, $specified_proxy_id, $specified_target_role, $override_permission)) {
                $breadcrumb_title = sprintf($translate->_("Reporting for %s"), "{$assessment_user->getFirstname()} {$assessment_user->getLastname()}");
                $BREADCRUMB[] = array("url" => "", "title" => html_encode($breadcrumb_title));
                $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = '" . ENTRADA_URL . "';</script>";
                $HEAD[] = "<script type=\"text/javascript\">var proxy_id = '" . $specified_proxy_id . "';</script>";
                $HEAD[] = "<script type=\"text/javascript\">sidebarBegone();</script>";
                $JQUERY[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/assessment-index.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
                $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.timepicker.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
                $HEAD[] = "<link href=\"" . ENTRADA_URL . "/css/assessments/assessment-public-index.css?release=" . html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
                ?>
                <script type="text/javascript">
                    var assessment_reports = {};
                    assessment_reports.default_error_message = "<?php echo $translate->_("Unable to fetch report data.")?>";
                    assessment_reports.pdf_unavailable = "<?php echo $translate->_("PDF download is currently unavailable. PDF generator library is not configured.")?>";

                    jQuery(function($) {
                        $(".datepicker").datepicker({
                            dateFormat: "yy-mm-dd",
                            minDate: "",
                            maxDate: ""
                        });

                        $(".add-on").on("click", function () {
                            if ($(this).siblings("input").is(":enabled")) {
                                $(this).siblings("input").focus();
                            }
                        });
                    });
                </script>
                <?php

                $target_role = $specified_target_role;
                $group_by_distribution = ($target_role == "target" ? 1 : Entrada_Utilities_Assessments_Reports::getPreferenceFromSession("group_by_distribution"));

                // Create the reporting utility object
                $construction = array(
                    "organisation_id" => $ENTRADA_USER->getActiveOrganisation(), // Limit to this org
                    "target_value" => $specified_proxy_id, // Limit to this proxy
                    "target_type" => "proxy_id",
                    "group_by_distribution" => $group_by_distribution,
                    "start_date" => $PROCESSED["start-date"],
                    "end_date" => $PROCESSED["end-date"],
                    "course_id" => Models_Course::getActiveUserCoursesIDList()
                );

                $reporting_utility = new Entrada_Utilities_Assessments_Reports($construction);

                // Fetch the summary of forms submitted on the user (form title, times submitted, for what cperiod)
                $completed_tasks = array();
                $completed_tasks_meta_by_cperiod = $reporting_utility->fetchCompletedAssessmentsMeta($target_role);
                $completed_tasks_meta = array();
                foreach ($completed_tasks_meta_by_cperiod as $group) {
                    foreach ($group as $form_meta) {
                        if (empty($PROCESSED["curriculum_periods"]) || in_array($form_meta["cperiod_id"] , $PROCESSED["curriculum_periods"])) {
                            $form_meta["cperiod_title"] = date("Y-m-d", $form_meta["cperiod_start"]) . " - " . date("Y-m-d", $form_meta["cperiod_end"]);
                            $completed_tasks_meta[] = $form_meta;
                        }
                    }
                }

                // Draw the page header
                $target_name = ($ENTRADA_USER->getActiveID() == $specified_proxy_id)
                    ? $translate->_("Me")
                    : "{$assessment_user->getFirstname()} {$assessment_user->getLastname()}";
                $header_view = new Views_Assessments_Reports_Header();
                $header_view->render(
                    array(
                        "target_name" => $target_name,
                    )
                );
                ?>

                <form class="form-horizontal" id="evaluation-form">
                    <div class="control-group">
                        <label class="control-label" for="report-start-date"><?php echo $translate->_("Report Date Range:"); ?></label>
                        <div class="controls">
                            <div class="input-append space-right">
                                <input id="report-start-date" placeholder="<?php echo $translate->_("Report Start..."); ?>" type="text" class="input-small datepicker" <?php echo (!is_null($PROCESSED["start-date"])) ? "value=\"" . date("Y-m-d", $PROCESSED["start-date"]) . "\"" : ""; ?>  name="report-start-date"/>
                                <span class="add-on pointer"><i class="icon-calendar"></i></span>
                            </div>
                            <div class="input-append">
                                <input id="report-end-date" placeholder="<?php echo $translate->_("Report End..."); ?>" type="text" class="input-small datepicker" <?php echo (!is_null($PROCESSED["end-date"])) ? "value=\"" . date("Y-m-d", $PROCESSED["end-date"]) . "\"" : ""; ?> name="report-end-date"/>
                                <span class="add-on pointer"><i class="icon-calendar"></i></span>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="specified_proxy_id" name="specified_proxy_id" value="<?php echo $specified_proxy_id; ?>"/>
                    <input type="hidden" id="specified_target_role" name="specified_target_role" value="<?php echo $specified_target_role; ?>"/>
                </form>

                <?php
                $render_options = array(
                    "completed_tasks" => $completed_tasks_meta,
                    "target_role" => $target_role,
                    "group_by_distribution" => $group_by_distribution,
                    "start_date" => $PROCESSED["start-date"],
                    "end_date" => $PROCESSED["end-date"]
                );

                // Draw the summary table for the given cperiod (can be empty)
                $learner_task_table = new Views_Assessments_Reports_FormSummaryTable(array("class" => "clearfix"));
                $learner_task_table->render($render_options);

            } else {
                add_error($translate->_("Unfortunately, you do not have permission to access this report."));
                echo display_error();
            }
        } else {
            add_error($translate->_("User not found."));
            echo display_error();
        }
    } else {
        add_error($translate->_("Please ensure you provide a valid user id."));
        echo display_error();
    }
}