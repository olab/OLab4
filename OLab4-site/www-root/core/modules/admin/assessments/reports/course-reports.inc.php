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
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
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

    $MODULE_TEXT = $translate->_($MODULE);
    $SUBMODULE_TEXT = $MODULE_TEXT[$SUBMODULE];

    $BREADCRUMB[] = array("url" => "", "title" => $translate->_("course_reports"));
    $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = '" . ENTRADA_URL . "';</script>";
    $JQUERY[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/reports/course-reports.es6.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.timepicker.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<link href=\"" . ENTRADA_URL . "/css/assessments/evaluation-reports.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";

    $assessments_base = new Entrada_Utilities_Assessments_Base();
    $assessments_base->getAssessmentPreferences("course");

    Entrada_Utilities::addJavascriptTranslation("Unable to save curriculum period preference.", "cperiod_error");

    if (isset($_SESSION[APPLICATION_IDENTIFIER]["course"]["evaluation"]["start_date"])) {
        $start_date = $_SESSION[APPLICATION_IDENTIFIER]["course"]["evaluation"]["start_date"];
    } else {
        $start_date = null;
    }

    if (isset($_SESSION[APPLICATION_IDENTIFIER]["course"]["evaluation"]["end_date"])) {
        $end_date = $_SESSION[APPLICATION_IDENTIFIER]["course"]["evaluation"]["end_date"];
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

            $("#select_distribution_btn").advancedSearch({
                api_url : "<?php echo ENTRADA_URL . "/admin/assessments?section=api-assessment-reports"; ?>",
                resource_url: ENTRADA_URL,
                filters: {
                    distribution: {
                        label: "<?php echo $translate->_("Distributions"); ?>",
                        data_source: "get-distributions-for-reporting",
                        mode: "checkbox"
                    }
                },
                no_results_text: "<?php echo $translate->_("No distributions found matching the search criteria."); ?>",
                parent_form: $("#report-form"),
                control_class: "form-selector",
                width: 350,
                select_all_enabled : true
            });
        });
    </script>
    <h1><?php echo $translate->_("course_reports"); ?></h1>
    <div id="msgs">
        <?php
        Entrada_Utilities_Flashmessenger::displayMessages($MODULE);
        ?>
    </div>
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
        <div class="control-group hide space-above" id="select_form_div">
            <label class="control-label form-required" for="select_form_btn"><?php echo $translate->_("Select Forms:"); ?></label>
            <div class="controls">
                <button id="select_form_btn" data-control="form" class="report-control btn" type="button"><?php echo $translate->_("Browse Forms "); ?><i class="icon-chevron-down"></i></button>
            </div>
        </div>
        <div class="control-group hide space-above" id="select_distribution_div">
            <label class="control-label" for="select_distribution_btn"><?php echo $translate->_("(Optional) Select Distributions:"); ?></label>
            <div class="controls">
                <button id="select_distribution_btn" data-control="distribution" class="report-control btn" type="button"><?php echo $translate->_("Browse Distributions "); ?><i class="icon-chevron-down"></i></button>
            </div>
        </div>

        <div class="control-group hide space-above" id="report_options_div" data-control="report_options">
            <div id="additional-comments">
                <div class="control-group">
                    <label class="control-label" for="include_comments"><?php echo $translate->_("Include Comments:"); ?></label>
                    <div class="controls">
                        <input type="checkbox" id="include_comments" data-control="include_comments" class="report-control" checked>
                    </div>
                </div>
                <div class="control-group" id="commenter_id_controls">
                    <label class="control-label" for="include_commenter_id" data-toggle="tooltip" title="<?php echo $translate->_("This option will include a set of characters unique to each assessor alongside each comment."); ?>"><?php echo $translate->_("Unique Commenter ID:"); ?> <i class="icon-question-sign"></i></label>
                    <div class="controls">
                        <input type="checkbox" id="include_commenter_id" data-control="include_commenter_id" class="report-control" />
                    </div>
                </div>
                <div class="control-group" id="commenter_name_controls">
                    <label class="control-label" for="include_commenter_name" data-toggle="tooltip"><?php echo $translate->_("Include Commenter Name:"); ?></label>
                    <div class="controls">
                        <input type="checkbox" id="include_commenter_name" data-control="include_commenter_name" class="report-control" />
                    </div>
                </div>
            </div>
            <div class="control-group" id="additional-description">
                <label class="control-label" for="include_description"><?php echo $translate->_("Include Description:"); ?></label>
                <div class="controls">
                    <input type="checkbox" id="include_description" data-control="include_description" for="description_text" class="report-control">
                </div>
                <div class="controls space-above">
                    <textarea id="description_text" data-control="description_text" class="report-control expandable hide"></textarea>
                </div>
            </div>
            <div id="additional-statistics">
                <div class="control-group">
                    <label class="control-label" for="include_statistics" data-toggle="tooltip" title="<?php echo $translate->_("This will include an automatically calculated average, weighted in ascending order. Descriptors such as 'N/A' are excluded."); ?>"><?php echo $translate->_("Include Average:"); ?> <i class="icon-question-sign"></i></label>
                    <div class="controls">
                        <input type="checkbox" id="include_statistics" data-control="include_statistics" class="report-control">
                    </div>
                </div>
                <div id="include_positivity_controls" class="control-group hide">
                    <label class="control-label" for="include_positivity" data-toggle="tooltip" title="<?php echo $SUBMODULE_TEXT["positive_negative_tooltip"]; ?>""><?php echo $translate->_("Include Aggregate Scoring:"); ?> <i class="icon-question-sign"></i></label>
                    <div class="controls">
                        <input type="checkbox" id="include_positivity" data-control="include_positivity" class="report-control">
                    </div>
                </div>
            </div>
        </div>

        <a class="btn btn-default space-above space-left hide" id="generate-pdf-btn" data-control="generate" title="<?php echo $translate->_("Download PDF(s)"); ?>" data-pdf-unavailable="0"><?php echo $translate->_("Download PDF(s)") ?></a>
        <input type="hidden" name="current-page" id="current-page" value="course"/>
    </form>
    <?php
}