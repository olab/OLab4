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
 * This report tracks the leave of residents within a rotation
 * Filtered by date range, and resident(multiple).
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Alex Ash <aa121@queensu.ca>
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
    $BREADCRUMB[] = array("url" => "", "title" => $translate->_("Rotation Leave Tracking"));
    $HEAD[] = "<script type=\"text/javascript\"> var ENTRADA_URL = '" . ENTRADA_URL . "';</script>";
    $JQUERY[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/evaluation-reports.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.timepicker.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<link href=\"" . ENTRADA_URL . "/css/assessments/evaluation-reports.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";

    if (isset($_SESSION[APPLICATION_IDENTIFIER]["rotation-leave"]["evaluation"]["start_date"])) {
        $start_date = $_SESSION[APPLICATION_IDENTIFIER]["rotation-leave"]["evaluation"]["start_date"];
    } else {
        $start_date = null;
    }

    if (isset($_SESSION[APPLICATION_IDENTIFIER]["rotation-leave"]["evaluation"]["end_date"])) {
        $end_date = $_SESSION[APPLICATION_IDENTIFIER]["rotation-leave"]["evaluation"]["end_date"];
    } else {
        $end_date = null;
    }
    ?>
    <script type="text/javascript">
        jQuery(function($) {
            var start_date = new Date($("#report-start-date").val()).getTime()/1000;
            var end_date = new Date($("#report-end-date").val()).getTime()/1000;
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
            $("#additional-comments").show();
            $("#additional-description").show();
            $("#choose-resident-btn").advancedSearch({
                api_url : "<?php echo ENTRADA_URL . "/admin/assessments?section=api-evaluation-reports"; ?>",
                resource_url: ENTRADA_URL,
                filters : {
                    target : {
                        label : "<?php echo $translate->_("Resident(s)"); ?>",
                        data_source : "get-residents",
                        api_params: {
                            start_date: start_date,
                            end_date: end_date
                        }
                    }
                },
                no_results_text: "<?php echo $translate->_("No Resident(s) found matching the search criteria."); ?>",
                parent_form: $("#rotation-leave-form"),
                control_class: "form-selector",
                width: 350,
                lazyload: true,
                select_all_enabled : true
            });
        });
    </script>

    <h1><?php echo $translate->_("Rotation Leave Tracking"); ?></h1>
    <div id="msgs"></div>
    <form class="form-horizontal" id="rotation-leave-form">
        <div class="control-group">
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
        <div class="control-group <?php echo is_null($start_date) && is_null($end_date) ? "hide" : ""; ?>" id="evaluation-search">
            <label class="control-label form-required" for="choose-resident-btn"><?php echo $translate->_("Select Resident(s):"); ?></label>
            <div class="controls">
                <a href="#" id="choose-resident-btn" class="btn" type="button"><?php echo $translate->_("Browse Resident(s) "); ?><i class="icon-chevron-down"></i></a>
            </div>
        </div>
        <div class="control-group" id="additional-description">
            <label class="control-label" for="include-description"><?php echo $translate->_("Include Description:"); ?></label>
            <div class="controls">
                <input type="checkbox" id="include-description" for="description-text">
            </div>
            <div class="controls space-above">
                <textarea id="description-text" class="expandable hide"></textarea>
            </div>
        </div>
        <div class="control-group space-above" id="additional-comments">
            <label class="control-label" for="include-comments"><?php echo $translate->_("Include Comments:"); ?></label>
            <div class="controls">
                <input type="checkbox" id="include-comments">
            </div>
        </div>
        <input type="button" class="btn btn-primary pull-right" id="generate-rotation-leave-report" value="<?php echo $translate->_("Generate Report"); ?>" />
        <input type="hidden" name="current-page" id="current-page" value="rotation-leave"/>
    </form>
<?php
}