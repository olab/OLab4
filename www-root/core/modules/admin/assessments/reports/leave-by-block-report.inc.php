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
    $BREADCRUMB[] = array("url" => "", "title" => $translate->_("Leave by Block Report"));
    $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = '" . ENTRADA_URL . "';</script>";
    $JQUERY[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/evaluation-reports.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<link href=\"" . ENTRADA_URL . "/css/assessments/evaluation-reports.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";

    $assessments_base = new Entrada_Utilities_Assessments_Base();
    $assessments_base->getAssessmentPreferences("leave_by_block");

    $selected_cperiod = null;
    if (isset($_SESSION[APPLICATION_IDENTIFIER]["leave_by_block"]["cperiod"])) {
        $selected_cperiod = $_SESSION[APPLICATION_IDENTIFIER]["leave_by_block"]["cperiod"];
    }

    $curriculum_types = Models_Curriculum_Type::fetchAllByOrg($ENTRADA_USER->getActiveOrganisation());
    $curriculum_periods = false;
    if ($curriculum_types) {
        foreach ($curriculum_types as $curriculum_type) {
            $periods = Models_Curriculum_Period::fetchAllByCurriculumType($curriculum_type->getID());
            if ($periods) {
                foreach ($periods as $period) {
                    $curriculum_periods[] = $period;
                }
            }
        }
    }
    ?>

    <script type="text/javascript">
        jQuery(function($) {
            $("#select-block-btn").advancedSearch({
                api_url : "<?php echo ENTRADA_URL . "/admin/assessments?section=api-evaluation-reports"; ?>",
                resource_url: ENTRADA_URL,
                filters : {
                    block : {
                        label : "<?php echo $translate->_("Block"); ?>",
                        data_source : "get-schedule-blocks",
                        api_params: {
                            cperiod_id: <?php echo is_null($selected_cperiod) ? reset($curriculum_periods)->getID() : $selected_cperiod; ?>
                        }
                    }
                },
                no_results_text: "<?php echo $translate->_("No blocks found matching the search criteria."); ?>",
                parent_form: $("#assessment-form"),
                control_class: "block-selector",
                width: 350,
                select_all_enabled : true
            });

            $("#select-learners-btn").advancedSearch({
                api_url : "<?php echo ENTRADA_URL . "/admin/assessments?section=api-evaluation-reports"; ?>",
                resource_url: ENTRADA_URL,
                filters : {
                    learner : {
                        label : "<?php echo $translate->_("Learner"); ?>",
                        data_source : "get-user-learners",
                        api_params: {
                            cperiod_ids: <?php echo is_null($selected_cperiod) ? reset($curriculum_periods)->getID() : $selected_cperiod; ?>
                        }
                    }
                },
                no_results_text: "<?php echo $translate->_("No learners found matching the search criteria."); ?>",
                parent_form: $("#assessment-form"),
                control_class: "form-selector",
                width: 350,
                select_all_enabled : true
            });
        });
    </script>
    <h1><?php echo $translate->_("Leave by Block Report"); ?></h1>
    <div id="msgs"></div>
    <form class="form-horizontal" id="assessment-form">
        <div class="control-group" id="curriculum-period-select-div">
            <label class="control-label" for="curriculum-period-select"><?php echo $translate->_("Select Curriculum Period: "); ?></label>
            <div class="controls">
                <select id="curriculum-period-select">
                    <?php if ($curriculum_periods) : ?>
                        <?php foreach ($curriculum_periods as $curriculum_period) : ?>
                            <option value="<?php echo $curriculum_period->getCperiodID(); ?>" <?php echo !is_null($selected_cperiod) && $curriculum_period->getCperiodID() == $selected_cperiod ? "selected" : ""; ?>>
                                <?php echo date("Y-m-d", $curriculum_period->getStartDate()) . " - " . date("Y-m-d", $curriculum_period->getFinishDate()) . ($curriculum_period->getCurriculumPeriodTitle() ? " " . $curriculum_period->getCurriculumPeriodTitle() : ""); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>
        <div class="control-group" id="select-block-div">
            <label class="control-label" for="select-block-btn"><?php echo $translate->_("Select Blocks:"); ?></label>
            <div class="controls">
                <a href="#" id="select-block-btn" class="btn" type="button"><?php echo $translate->_("Browse Blocks "); ?><i class="icon-chevron-down"></i></a>
            </div>
        </div>
        <div class="control-group hide" id="select-learners-div">
            <label class="control-label" for="select-learners-btn"><?php echo $translate->_("Select Learners:"); ?></label>
            <div class="controls">
                <a href="#" id="select-learners-btn" class="btn" type="button"><?php echo $translate->_("Browse Learners "); ?><i class="icon-chevron-down"></i></a>
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
        <a class="btn btn-default space-above space-left hide" id="generate-pdf-btn" href="#generate-pdf-modal" title="<?php echo $translate->_("Download PDF"); ?>" data-pdf-unavailable="0" data-toggle="modal"><?php echo $translate->_("Download PDF") ?></a>
        <input type="hidden" name="current-page" id="current-page" value="leave_by_block"/>
    </form>
    <?php
    $pdf_modal = new Views_Assessments_Modals_GeneratePDF();
    $pdf_modal->render(array(
        "action_url" => ENTRADA_URL . "/admin/assessments?section=api-evaluation-reports",
        "label" => $translate->_("Please confirm that you want to download the Leave by Block Report PDF.")
    ));
}