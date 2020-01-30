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

    /**
     * Iterate POST and build an array with advancedSearch appropriate data
     */
    $target_list = array();
    if (isset($_POST["targets"]) && is_array($_POST["targets"])) {
        foreach ($_POST["targets"] as $target) {
            if ($tmp_input = clean_input($target, array("trim", "int"))) {
                $user = Models_User::fetchRowByID($tmp_input);
                if ($user) {
                    $user_fullname = $user->getFullname(false);
                    $target_list[] = array("proxy_id" => $tmp_input, "target_name" => $user_fullname);
                }
            }
        }
    }

    $form_id = 0;
    $form = false;
    if (isset($_POST["form_id"]) && $tmp_input = clean_input($_POST["form_id"], array("trim", "int"))) {
        $form_id = $tmp_input;
        $form = Models_Assessments_Form::fetchRowByID($form_id);
    }

    $course_id = 0;
    $course = false;
    if (isset($_POST["course_id"]) && $tmp_input = clean_input($_POST["course_id"], array("trim", "int"))) {
        $course_id = $tmp_input;
        $course = Models_Course::fetchRowByID($course_id);
    }
    $adistribution_id = 0;
    $distribution = false;
    if (isset($_POST["adistribution_id"]) && $tmp_input = clean_input($_POST["adistribution_id"], array("trim", "int"))) {
        $adistribution_id = $tmp_input;
        $distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($adistribution_id);
    }
    $BREADCRUMB[] = array("url" => "", "title" => $translate->_("Faculty Reports"));
    $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = '" . ENTRADA_URL . "';</script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.timepicker.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/reports/faculty-evaluation.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/evaluation-reports.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<link href=\"" . ENTRADA_URL . "/css/assessments/evaluation-reports.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";

    $assessments_base = new Entrada_Utilities_Assessments_Base();
    $assessments_base->getAssessmentPreferences("faculty");

    if (isset($_SESSION[APPLICATION_IDENTIFIER]["faculty"]["evaluation"]["start_date"])) {
        $start_date = $_SESSION[APPLICATION_IDENTIFIER]["faculty"]["evaluation"]["start_date"];
    } else {
        $start_date = null;
    }

    if (isset($_SESSION[APPLICATION_IDENTIFIER]["faculty"]["evaluation"]["end_date"])) {
        $end_date = $_SESSION[APPLICATION_IDENTIFIER]["faculty"]["evaluation"]["end_date"];
    } else {
        $end_date = null;
    }

    if (isset($_POST["start_date"]) && $tmp_input = clean_input($_POST["start_date"], array("trim", "int"))) {
        $start_date = $tmp_input;
    }

    if (isset($_POST["end_date"]) && $tmp_input = clean_input($_POST["end_date"], array("trim", "int"))) {
        $end_date = $tmp_input;
    }
    ?>
    <h1><?php echo $translate->_("Faculty Reports"); ?></h1>
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
        <div class="control-group <?php echo is_null($start_date) && is_null($end_date) ? "hide" : ""; ?> " id="evaluation-search">
            <label class="control-label" for="choose-evaluation-btn"><?php echo $translate->_("Select Faculty:"); ?></label>
            <div class="controls">
                <a href="#" id="choose-evaluation-btn" class="btn" type="button"><?php echo $translate->_("Browse Faculty "); ?><i class="icon-chevron-down"></i></a>
                <?php if ($target_list) : ?>
                    <ul id="target_list_container" class="selected-items-list">
                    <?php foreach ($target_list as $target) : ?>
                        <li class="target_target_item target_<?php echo $target["proxy_id"] ?>" data-id="<?php echo $target["proxy_id"] ?>">
                            <span class="selected-list-container">
                                <span class="selected-list-item"><?php echo $translate->_("Faculty") ?></span><span class="remove-selected-list-item remove-target-toggle" data-filter="target" data-id="<?php echo $target["proxy_id"] ?>">×</span>
                            </span>
                            <?php echo html_encode($target["target_name"]) ?>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        <div id="evaluation-subtypes"></div>
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
                <label class="control-label" for="include-positivity" data-toggle="tooltip" title="<?php echo $SUBMODULE_TEXT["positive_negative_tooltip"]; ?>"><?php echo $translate->_("Include Aggregate Scoring:"); ?> <i class="icon-question-sign"></i></label>
                <div class="controls">
                    <input type="checkbox" id="include-positivity">
                </div>
            </div>
        </div>
        <a class="btn btn-default space-above space-left hide" id="generate-pdf-btn" href="#generate-pdf-modal" title="<?php echo $translate->_("Download PDF(s)"); ?>" data-pdf-unavailable="0" data-toggle="modal"><?php echo $translate->_("Download PDF(s)") ?></a>
        <input type="hidden" name="current-page" id="current-page" value="faculty"/>
        <?php if ($target_list) : ?>
            <?php foreach ($target_list as $target) : ?>
                <input type="hidden" name="target[]" value="<?php echo $target["proxy_id"] ?>" id="target_<?php echo $target["proxy_id"] ?>" data-label="<?php echo html_encode($target["target_name"]) ?>" class="search-target-control target_search_target_control" />
            <?php endforeach; ?>
        <?php endif; ?>
        <?php if ($form) : ?>
            <input type="hidden" id="form_<?php echo $form->getID() ?>" value="<?php echo $form->getID() ?>" data-label="<?php echo html_encode($form->getTitle()) ?>" class="search-target-control form_search_target_control form-selector" />
        <?php endif; ?>
        <?php if ($course) : ?>
            <input type="hidden" id="course-override" name="course_override" value="<?php echo $course->getID() ?>" />
        <?php endif; ?>
        <?php if ($distribution) : ?>
            <input type="hidden" id="distribution-override" name="distribution_override" value="<?php echo $distribution->getID() ?>" />
        <?php endif; ?>
    </form>
    <?php
    $pdf_modal = new Views_Assessments_Modals_GeneratePDF();
    $pdf_modal->render(array(
        "action_url" => ENTRADA_URL . "/admin/assessments?section=api-evaluation-reports"
    ));
}