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
 * @author Developer: Joshua Belanher <jb301@queensu.ca>
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
    $BREADCRUMB[] = array("url" => "", "title" => $translate->_("Distribution Delivery Schedule"));
    $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = '" . ENTRADA_URL . "';</script>";
    $JQUERY[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/evaluation-reports.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.timepicker.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<link href=\"" . ENTRADA_URL . "/css/assessments/evaluation-reports.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";

    $assessments_base = new Entrada_Utilities_Assessments_Base();
    $assessments_base->getAssessmentPreferences("distribution_schedule");

    if (isset($_SESSION[APPLICATION_IDENTIFIER]["distribution-schedule"]["evaluation"]["start_date"])) {
        $start_date = $_SESSION[APPLICATION_IDENTIFIER]["distribution-schedule"]["evaluation"]["start_date"];
    } else {
        $start_date = null;
    }

    if (isset($_SESSION[APPLICATION_IDENTIFIER]["distribution-schedule"]["evaluation"]["end_date"])) {
        $end_date = $_SESSION[APPLICATION_IDENTIFIER]["distribution-schedule"]["evaluation"]["end_date"];
    } else {
        $end_date = null;
    }

    $task_types = array();
    $task_types[] = array("target_id" => "all", "target_label" => $translate->_("All Task Types"));
    $task_types[] = array("target_id" => "learner_assessment_by_faculty", "target_label" => $translate->_("Learner Assessments By Faculty"));
    $task_types[] = array("target_id" => "faculty_evaluation_by_learners", "target_label" => $translate->_("Faculty Evaluations By Learners"));
    $task_types[] = array("target_id" => "course_evaluation", "target_label" => $translate->_("Course") . " " . $translate->_("Evaluation"));
    $task_types[] = array("target_id" => "rotation_evaluation", "target_label" => $translate->_("Rotation") . " " . $translate->_("Evaluation"));
    ?>
    <script type="text/javascript">
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

            $("#select-course-btn").advancedSearch({
                api_url : "<?php echo ENTRADA_URL . "/admin/assessments?section=api-evaluation-reports"; ?>",
                resource_url: ENTRADA_URL,
                filters : {
                    course : {
                        label : "<?php echo $translate->_("Course"); ?>",
                        data_source : "get-user-courses",
                        mode: "checkbox"
                    }
                },
                no_results_text: "<?php echo $translate->_("No course found matching the search criteria."); ?>",
                parent_form: $("#assessment-form"),
                control_class: "course-selector",
                width: 350,
                select_all_enabled: true
            });

            $("#choose-evaluation-btn").advancedSearch({
                filters: {
                    target: {
                        label: "<?php echo $translate->_("Task Type"); ?>",
                        data_source: <?php echo json_encode($task_types); ?>,
                        mode: "radio",
                        selector_control_name: "task_type",
                        search_mode: false
                    }
                },
                no_results_text: "<?php echo $translate->_("No task type found matching the search criteria."); ?>",
                parent_form: $("#assessment-form"),
                control_class: "task-type-selector",
                width: 350
            });

            $("#select-distribution-btn").advancedSearch({
                filters: {
                    distribution: {
                        label: "<?php echo $translate->_("Distributions"); ?>",
                        data_source: [],
                        mode: "checkbox"
                    }
                },
                no_results_text: "<?php echo $translate->_("No distributions found matching the search criteria."); ?>",
                parent_form: $("#assessment-form"),
                control_class: "distribution-selector",
                width: 350,
                select_all_enabled: true
            });
        });
    </script>
    <h1><?php echo $translate->_("Distribution Delivery Schedule"); ?></h1>
    <div id="msgs"></div>
    <form class="form-horizontal" id="assessment-form">
        <div id="distribution-schedule-options-container" class="space-above">
            <label class="control-label form-required"><?php echo $translate->_("Report Type"); ?></label>
            <div class="controls" id="distribution-schedule-report-type-container">
                <label class="radio" for="distribution-schedule-report-type-summary">
                    <input type="radio" name="distribution-schedule-report-type" id="distribution-schedule-report-type-summary" value="summary" checked>
                    <?php echo $translate->_("Summary of distributions"); ?>
                </label>
                <label class="radio" for="distribution-schedule-report-type-individual">
                    <input type="radio" name="distribution-schedule-report-type" id="distribution-schedule-report-type-individual" value="individual">
                    <?php echo $translate->_("Individual tasks"); ?>
                </label>
            </div>
        </div>
        <div class="control-group space-above" id="select-course-div">
            <label class="control-label form-required" for="select-course-btn"><?php echo $translate->_("Select Course(s):"); ?></label>
            <div class="controls">
                <a href="#" id="select-course-btn" class="btn" type="button"><?php echo $translate->_("Browse Courses "); ?><i class="icon-chevron-down"></i></a>
            </div>
        </div>
        <div class="control-group hide" id="report-date-range-div">
            <label class="control-label form-required" for="report-start-date"><?php echo $translate->_("Report Date Range:"); ?></label>
            <div class="controls">
                <div class="input-append space-right">
                    <input id="report-start-date" placeholder="<?php echo $translate->_("Report Start..."); ?>" type="text" class="input-small datepicker" <?php echo ($start_date) ? "value=\"" . date("Y-m-d", $start_date) . "\"" : ""; ?>  name="report-start-date"/>
                    <span class="add-on pointer"><i class="icon-calendar"></i></span>
                </div>
                <div class="input-append">
                    <input id="report-end-date" placeholder="<?php echo $translate->_("Report End..."); ?>" type="text" class="input-small datepicker" <?php echo ($end_date) ? "value=\"" . date("Y-m-d", $end_date) . "\"" : ""; ?> name="report-end-date"/>
                    <span class="add-on pointer"><i class="icon-calendar"></i></span>
                </div>
            </div>
        </div>
        <div class="control-group form-required hide" id="evaluation-search">
            <label class="control-label" for="choose-evaluation-btn"><?php echo $translate->_("Select Task Type:"); ?></label>
            <div class="controls">
                <a href="#" id="choose-evaluation-btn" class="btn" type="button"><?php echo $translate->_("Browse Task Types "); ?><i class="icon-chevron-down"></i></a>
            </div>
        </div>
        <div class="control-group form-required hide space-above" id="distribution-search">
            <label class="control-label" for="select-distribution-btn"><?php echo $translate->_("Select Distribution(s):"); ?></label>
            <div class="controls">
                <a href="#" id="select-distribution-btn" class="btn" type="button"><?php echo $translate->_("Browse Distributions "); ?><i class="icon-chevron-down"></i></a>
            </div>
        </div>
        <div class="control-group hide" id="additional-description">
            <label class="control-label" for="include-description"><?php echo $translate->_("Include Description:"); ?></label>
            <div class="controls">
                <input type="checkbox" id="include-description" for="description-text">
            </div>
            <div class="controls space-above">
                <textarea id="description-text" class="expandable hide"></textarea>
            </div>
        </div>
        <input type="button" class="btn btn-primary pull-right hide" id="generate-distribution-schedule-report" value="<?php echo $translate->_("Generate Report"); ?>" />
        <input type="hidden" name="current-page" id="current-page" value="distribution-schedule"/>
    </form>
    <?php
    $pdf_modal = new Views_Assessments_Modals_GeneratePDF();
    $pdf_modal->render(array(
        "action_url" => ENTRADA_URL . "/admin/assessments?section=api-evaluation-reports"
    ));
}