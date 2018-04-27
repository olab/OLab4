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

    $MODULE_TEXT = $translate->_($MODULE);
    $SUBMODULE_TEXT = $MODULE_TEXT[$SUBMODULE];

    $BREADCRUMB[] = array("url" => "", "title" => $translate->_("Learning Event Evaluations"));
    $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = '" . ENTRADA_URL . "';</script>";
    $JQUERY[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/evaluation-reports.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.timepicker.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<link href=\"" . ENTRADA_URL . "/css/assessments/evaluation-reports.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";

    $assessments_base = new Entrada_Utilities_Assessments_Base();
    $assessments_base->getAssessmentPreferences("learning-events");

    if (isset($_SESSION[APPLICATION_IDENTIFIER]["learning-events"]["evaluation"]["start_date"])) {
        $start_date = $_SESSION[APPLICATION_IDENTIFIER]["learning-events"]["evaluation"]["start_date"];
    } else {
        $start_date = null;
    }

    if (isset($_SESSION[APPLICATION_IDENTIFIER]["learning-events"]["evaluation"]["end_date"])) {
        $end_date = $_SESSION[APPLICATION_IDENTIFIER]["learning-events"]["evaluation"]["end_date"];
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

            $("#choose-evaluation-btn").advancedSearch({
                api_url : "<?php echo ENTRADA_URL . "/admin/assessments?section=api-evaluation-reports"; ?>",
                resource_url: ENTRADA_URL,
                filters : {
                    target : {
                        label : "<?php echo $translate->_("Events Types"); ?>",
                        data_source : "get-user-learning-events"
                    }
                },
                no_results_text: "<?php echo $translate->_("No learning event types found matching the search criteria."); ?>",
                parent_form: $("#evaluation-form"),
                width: 350
            });

            $("#choose-form-btn").advancedSearch({
                api_url : "<?php echo ENTRADA_URL . "/admin/assessments?section=api-evaluation-reports"; ?>",
                resource_url: ENTRADA_URL,
                filters: {
                    form: {
                        label: "<?php echo $translate->_("Forms"); ?>",
                        data_source: "get-user-forms",
                        mode: "radio"
                    }
                },
                no_results_text: "<?php echo $translate->_("No forms found matching the search criteria."); ?>",
                parent_form: $("#evaluation-form"),
                control_class: "form-selector",
                width: 300
            });

            $("#choose-individual-events-btn").advancedSearch({
                filters: {
                    learning_events: {
                        label: "<?php echo $translate->_("Events"); ?>",
                        data_source: <?php echo json_encode(array()); ?>,
                        mode: "checkbox",
                        selector_control_name: "individual-events"
                    }
                },
                control_class: "individual-events-selector",
                no_results_text: "<?php echo $translate->_("No Events found."); ?>",
                parent_form: $("#evaluation-form"),
                width: 300,
                modal: false,
                select_all_enabled: true
            });
        });
    </script>
    <h1><?php echo $translate->_("Learning Event Evaluations"); ?></h1>
    <div id="msgs"></div>
    <form class="form-horizontal" id="evaluation-form">
        <div class="control-group">
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
        <div class="control-group <?php echo is_null($start_date) && is_null($end_date) ? "hide" : ""; ?>" id="individual-event-section">
            <label class="control-label" for="select-individual-events-checkbox"><?php echo $translate->_("Select Individual Events"); ?></label>
            <div class="controls">
                <input type="checkbox" id="select-individual-events-checkbox" />
            </div>
        </div>
        <div class="control-group <?php echo is_null($start_date) && is_null($end_date) ? "hide" : ""; ?> " id="evaluation-search">
            <label class="control-label" for="choose-evaluation-btn"><?php echo $translate->_("Select Events Types:"); ?></label>
            <div class="controls">
                <a href="#" id="choose-evaluation-btn" class="btn" type="button"><?php echo $translate->_("Browse Events Types "); ?><i class="icon-chevron-down"></i></a>
            </div>
        </div>
        <div id="evaluation-subtypes"></div>
        <div class="control-group hide space-above" id="individual-events-list">
            <label class="control-label" for="choose-individual-events-btn"><?php echo $translate->_("Select Learning Events:"); ?></label>
            <div class="controls">
                <a href="#" id="choose-individual-events-btn" class="btn" type="button"><?php echo $translate->_("Browse Learning Events "); ?><i class="icon-chevron-down"></i></a>
            </div>
        </div>
        <div class="control-group hide space-above" id="form-selector">
            <label class="control-label" for="choose-form-btn"><?php echo $translate->_("Select Form:"); ?></label>
            <div class="controls">
                <a href="#" id="choose-form-btn" class="btn" type="button"><?php echo $translate->_("Browse Forms "); ?><i class="icon-chevron-down"></i></a>
            </div>
        </div>
        <div class="hide" id="additional-comments">
            <div class="control-group">
                <label class="control-label" for="include-comments"><?php echo $translate->_("Include Comments:"); ?></label>
                <div class="controls">
                    <input type="checkbox" id="include-comments" checked>
                </div>
            </div>
            <div class="control-group" id="commenter-id-controls">
                <label class="control-label" for="include-commenter-id" data-toggle="tooltip" title="<?php echo $translate->_("This option will include a set of characters unique to each assessor alongside each comment."); ?>"><?php echo $translate->_("Unique Commenter ID:"); ?> <i class="icon-question-sign"></i></label>
                <div class="controls">
                    <input type="checkbox" id="include-commenter-id"/>
                </div>
            </div>
            <div class="control-group" id="commenter-name-controls">
                <label class="control-label" for="include-commenter-name" data-toggle="tooltip"><?php echo $translate->_("Include Commenter Name:"); ?></label>
                <div class="controls">
                    <input type="checkbox" id="include-commenter-name"/>
                </div>
            </div>
        </div>
        <div class="control-group hide" id="output-options">
            <label class="control-label" for="output-associated-records"><?php echo $translate->_("Event Info Subheader:"); ?></label>
            <div class="controls">
                <input type="checkbox" id="output-associated-records" />
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
        <div class=hide" id="additional-statistics">
            <div class="control-group">
                <label class="control-label" for="include-statistics" data-toggle="tooltip" title="<?php echo $translate->_("This will include an automatically calculated average, weighted in ascending order. Descriptors such as 'N/A' are excluded."); ?>"><?php echo $translate->_("Include Average:"); ?> <i class="icon-question-sign"></i></label>
                <div class="controls">
                    <input type="checkbox" id="include-statistics">
                </div>
            </div>
            <div id="include-positivity-controls" class="control-group hide">
                <label class="control-label" for="include-positivity" data-toggle="tooltip" title="<?php echo $SUBMODULE_TEXT["positive_negative_tooltip"]; ?>""><?php echo $translate->_("Include Aggregate Scoring:"); ?> <i class="icon-question-sign"></i></label>
                <div class="controls">
                    <input type="checkbox" id="include-positivity">
                </div>
            </div>
        </div>
        <input type="button" class="btn btn-primary hide pull-right" id="generate-report" value="<?php echo $translate->_("Generate Report"); ?>" />
        <input type="hidden" name="current-page" id="current-page" value="learning-events"/>
    </form>
    <?php
}