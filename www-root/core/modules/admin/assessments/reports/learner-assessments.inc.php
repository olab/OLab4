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
 * @author Developer: Jordan L <jl250@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
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
    $BREADCRUMB[] = array("url" => "", "title" => $translate->_("Learner Assessments"));
    $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = '" . ENTRADA_URL . "';</script>";
    $JQUERY[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/reports/learner-assessments.es6.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.timepicker.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<link href=\"" . ENTRADA_URL . "/css/assessments/evaluation-reports.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";

    $assessments_base = new Entrada_Utilities_Assessments_Base();
    $assessments_base->getAssessmentPreferences("learner-assessments");

    Entrada_Utilities::addJavascriptTranslation("Unable to save curriculum period preference.", "cperiod_error");

    if (isset($_SESSION[APPLICATION_IDENTIFIER]["learner-assessments"]["evaluation"]["start_date"])) {
        $start_date = $_SESSION[APPLICATION_IDENTIFIER]["learner-assessments"]["evaluation"]["start_date"];
    } else {
        $start_date = null;
    }

    if (isset($_SESSION[APPLICATION_IDENTIFIER]["learner-assessments"]["evaluation"]["end_date"])) {
        $end_date = $_SESSION[APPLICATION_IDENTIFIER]["learner-assessments"]["evaluation"]["end_date"];
    } else {
        $end_date = null;
    }
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

            $("#select_course_btn").advancedSearch({
                api_url : "<?php echo ENTRADA_URL . "/admin/assessments?section=api-assessment-reports"; ?>",
                resource_url: ENTRADA_URL,
                filters : {
                    course : {
                        label : "<?php echo $translate->_("Course"); ?>",
                        data_source : "get-user-courses",
                        mode: "checkbox"
                    }
                },
                no_results_text: "<?php echo $translate->_("No course found matching the search criteria."); ?>",
                parent_form: $("#report-form"),
                control_class: "course-selector",
                width: 350,
                select_all_enabled: true
            });

            $("#select_course_group_btn").advancedSearch({
                api_url : "<?php echo ENTRADA_URL . "/admin/assessments?section=api-assessment-reports"; ?>",
                resource_url: ENTRADA_URL,
                filters : {
                    course_group : {
                        label : "<?php echo $translate->_("Group"); ?>",
                        data_source : "get-course-groups",
                        mode: "checkbox"
                    }
                },
                no_results_text: "<?php echo $translate->_("No group found matching the search criteria."); ?>",
                parent_form: $("#report-form"),
                control_class: "course-group-selector",
                width: 350,
                select_all_enabled: true
            });

            $("#select_learner_btn").advancedSearch({
                api_url : "<?php echo ENTRADA_URL . "/admin/assessments?section=api-assessment-reports"; ?>",
                resource_url: ENTRADA_URL,
                filters : {
                    learner : {
                        label : "<?php echo $translate->_("Learner(s)"); ?>",
                        data_source : "get-user-learners",
                        mode: "checkbox"
                    }
                },
                no_results_text: "<?php echo $translate->_("No learner(s) found matching the search criteria."); ?>",
                parent_form: $("#report-form"),
                control_class: "learner-selector",
                width: 350,
                lazyload: true,
                select_all_enabled : true
            });

            $("#select_form_btn").advancedSearch({
                api_url : "<?php echo ENTRADA_URL . "/admin/assessments?section=api-assessment-reports"; ?>",
                resource_url: ENTRADA_URL,
                filters: {
                    form: {
                        label: "<?php echo $translate->_("Forms"); ?>",
                        data_source: "get-forms-for-reporting",
                        mode: "checkbox"
                    }
                },
                no_results_text: "<?php echo $translate->_("No forms found matching the search criteria."); ?>",
                parent_form: $("#report-form"),
                control_class: "form-selector",
                width: 350,
                select_all_enabled : true
            });
        });
    </script>
    <h1><?php echo $translate->_("Learner Assessments"); ?></h1>
    <?php if (isset($_GET["error"])) { ?>
        <div class="alert alert-error">
            <button type="button" class="close" data-dismiss="alert">×</button>
            <?php echo $translate->_("No tasks found for the selected learner(s) within the set date range.");?>
        </div>
    <?php } ?>
    <div id="msgs"></div>
    <form class="form-horizontal" id="report-form">
        <div class="control-group space-above" id="select_course_div">
            <label class="control-label form-required" for="select_course_btn"><?php echo $translate->_("select_course"); ?></label>
            <div class="controls">
                <button id="select_course_btn" data-control="course" class="report-control btn" type="button"><?php echo $translate->_("browse_course"); ?> <i class="icon-chevron-down"></i></button>
            </div>
        </div>
        <div id="report-date-range-div" class="control-group">
            <label class="control-label form-required" for="report_start_date"><?php echo $translate->_("Report Date Range:"); ?></label>
            <div class="controls">
                <div class="input-append space-right">
                    <input id="report_start_date" data-control="start_date" placeholder="<?php echo $translate->_("Report Start..."); ?>" type="text" class="report-control input-small datepicker" <?php echo ($start_date) ? "value=\"" . date("Y-m-d", $start_date) . "\"" : ""; ?>  name="report-start-date"/>
                    <span class="add-on pointer"><i class="icon-calendar"></i></span>
                </div>
                <div class="input-append">
                    <input id="report_end_date" data-control="end_date" placeholder="<?php echo $translate->_("Report End..."); ?>" type="text" class="report-control input-small datepicker" <?php echo ($end_date) ? "value=\"" . date("Y-m-d", $end_date) . "\"" : ""; ?> name="report-end-date"/>
                    <span class="add-on pointer"><i class="icon-calendar"></i></span>
                </div>
            </div>
        </div>
        <div class="control-group hide" id="select_course_group_div">
            <label class="control-label" for="select_course_group_btn"><?php echo $translate->_("(Optional) Select Course Groups:"); ?></label>
            <div class="controls">
                <button id="select_course_group_btn" data-control="course_group" class="report-control btn" type="button"><?php echo $translate->_("Browse Group(s) "); ?><i class="icon-chevron-down"></i></button>
            </div>
        </div>
        <div class="control-group hide" id="select_learner_div">
            <label class="control-label form-required" for="select_learner_btn"><?php echo $translate->_("Select Learner(s):"); ?></label>
            <div class="controls">
                <button id="select_learner_btn" data-control="learner" class="report-control btn" type="button"><?php echo $translate->_("Browse Learner(s) "); ?><i class="icon-chevron-down"></i></button>
            </div>
        </div>
        <div class="control-group hide space-above" id="select_form_div">
            <label class="control-label" for="select_form_btn"><?php echo $translate->_("(Optional) Select Form:"); ?></label>
            <div class="controls">
                <button id="select_form_btn" data-control="form" class="report-control btn" type="button"><?php echo $translate->_("Browse Forms "); ?><i class="icon-chevron-down"></i></button>
            </div>
        </div>

        <a class="btn btn-default space-above space-left hide" id="generate-pdf-btn" href="#generate-pdf-modal" title="<?php echo $translate->_("Download PDF(s)"); ?>" data-pdf-unavailable="0" data-toggle="modal"><?php echo $translate->_("Download PDF(s)") ?></a>
        <input type="hidden" name="current-page" id="current-page" value="learner-assessments"/>
    </form>
    <?php
    $pdf_modal = new Views_Assessments_Modals_GeneratePDF();
    $pdf_modal->render(array(
        "hide_task_table" => true,
        "action_url" => ENTRADA_URL . "/admin/assessments?section=api-assessment-reports"
    ));
}