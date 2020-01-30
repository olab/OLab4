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
    $BREADCRUMB[] = array("url" => "", "title" => $translate->_("Timeliness of Completion Report"));
    $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = '" . ENTRADA_URL . "';</script>";
    $JQUERY[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/evaluation-reports.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.timepicker.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<link href=\"" . ENTRADA_URL . "/css/assessments/evaluation-reports.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";

    $assessments_base = new Entrada_Utilities_Assessments_Base();
    $assessments_base->getAssessmentPreferences("completion");

    if (isset($_SESSION[APPLICATION_IDENTIFIER]["completion"]["evaluation"]["start_date"])) {
        $start_date = $_SESSION[APPLICATION_IDENTIFIER]["completion"]["evaluation"]["start_date"];
    } else {
        $start_date = null;
    }

    if (isset($_SESSION[APPLICATION_IDENTIFIER]["completion"]["evaluation"]["end_date"])) {
        $end_date = $_SESSION[APPLICATION_IDENTIFIER]["completion"]["evaluation"]["end_date"];
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

            $("#select-course-btn").advancedSearch({
                api_url : "<?php echo ENTRADA_URL . "/admin/assessments?section=api-evaluation-reports"; ?>",
                resource_url: ENTRADA_URL,
                filters : {
                    course : {
                        label : "<?php echo $translate->_("Course"); ?>",
                        data_source : "get-user-courses",
                        mode: "radio"
                    }
                },
                no_results_text: "<?php echo $translate->_("No course found matching the search criteria."); ?>",
                parent_form: $("#assessment-form"),
                control_class: "course-selector",
                width: 350
            });

            $("#select-faculty-btn").advancedSearch({
                api_url : "<?php echo ENTRADA_URL . "/admin/assessments?section=api-evaluation-reports"; ?>",
                resource_url: ENTRADA_URL,
                filters : {
                    faculty : {
                        label : "<?php echo $translate->_("Faculty"); ?>",
                        data_source : "get-assessor-faculty"
                    }
                },
                no_results_text: "<?php echo $translate->_("No faculty found matching the search criteria."); ?>",
                parent_form: $("#assessment-form"),
                control_class: "faculty-selector",
                width: 350,
                select_all_enabled : true
            });
        });
    </script>
    <h1><?php echo $translate->_("Timeliness of Completion Report"); ?></h1>
    <div id="msgs"></div>
    <form class="form-horizontal" id="assessment-form">
        <div class="control-group" id="select-course-div">
            <label class="control-label" for="select-course-btn"><?php echo $translate->_("Select Course:"); ?></label>
            <div class="controls">
                <a href="#" id="select-course-btn" class="btn" type="button"><?php echo $translate->_("Browse Courses "); ?><i class="icon-chevron-down"></i></a>
            </div>
        </div>
        <div class="control-group hide" id="report-date-range-div">
            <label class="control-label" for="report-start-date"><?php echo $translate->_("Report Date Range:"); ?></label>
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
        <div class="control-group hide" id="include-externals-div">
            <label class="control-label"><?php echo $translate->_("Include Externals:"); ?></label>
            <div class="controls">
                <label class="radio inline">
                    <input type="radio" id="include-externals-yes" name="include-externals" value="yes"/><?php echo $translate->_("Yes"); ?>
                </label>
                <label class="radio inline">
                    <input type="radio" id="include-externals-no" name="include-externals" value="no"/><?php echo $translate->_("No"); ?>
                </label>
            </div>
        </div>
        <div class="control-group hide" id="select-faculty-div">
            <label class="control-label" for="select-faculty-btn"><?php echo $translate->_("Select Faculty:"); ?></label>
            <div class="controls">
                <a href="#" id="select-faculty-btn" class="btn" type="button"><?php echo $translate->_("Browse Faculty "); ?><i class="icon-chevron-down"></i></a>
            </div>
        </div>
        <div class="control-group hide" id="add-average-delivery-date">
            <label class="control-label" for="add-average-delivery-date-checkbox"><?php echo $translate->_("Include Average Delivery Date:"); ?></label>
            <div class="controls">
                <input type="checkbox" id="add-average-delivery-date-checkbox">
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
        <a class="btn btn-default space-above space-left hide" id="generate-pdf-btn" href="#generate-pdf-modal" title="<?php echo $translate->_("Download PDF(s)"); ?>" data-pdf-unavailable="0" data-toggle="modal"><?php echo $translate->_("Download PDF(s)") ?></a>
        <input type="hidden" name="current-page" id="current-page" value="completion"/>
    </form>
    <?php
    $pdf_modal = new Views_Assessments_Modals_GeneratePDF();
    $pdf_modal->render(array(
        "action_url" => ENTRADA_URL . "/admin/assessments?section=api-evaluation-reports"
    ));
}