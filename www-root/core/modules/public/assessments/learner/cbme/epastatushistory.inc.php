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
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

if(!defined("PARENT_INCLUDED")) {
    exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("academicadvisor", "read", false) && !$ENTRADA_ACL->amIAllowed("competencycommittee", "read", false)) {
    $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."\\'', 15000)";
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%s\">%s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));
    echo display_error();
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    /**
     * Load module preferences
     */
    $PREFERENCES = preferences_load("cbme_assessments");

    /**
     * Javascript headers
     */
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/cbme/date-filter.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/cbme/epa-status-history.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.animated-notices.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";

    /**
     * CSS headers
     */
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/cbme/cbme.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.animated-notices.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";

    if ((isset($_GET["proxy_id"])) && ($proxy_id = clean_input($_GET["proxy_id"], array("int", "trim")))) {
        $PROCESSED["proxy_id"] = $proxy_id;
        if ($PROCESSED["proxy_id"]) {
            /**
             * Check to see if the proxy id that was passed in is a valid learner for the current user.
             */
            $assessment_user = new Entrada_Utilities_AssessmentUser();
            $admin = $ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true);
            $learners = $assessment_user->getMyLearners($ENTRADA_USER->getActiveId(), $ENTRADA_USER->getActiveOrganisation(), $admin, null);
            $valid_learner = false;
            foreach ($learners as $learner) {
                if ($learner["proxy_id"] == $PROCESSED["proxy_id"]) {
                    $valid_learner = true;
                }
            }
            if (!$valid_learner) {
                add_error(
                    sprintf(
                        $translate->_("Your account does not have the permissions required to view this learners dashboard. Click <a href='%s'>here</a> to return to your dashboard."),
                        ENTRADA_URL . "/dashboard"
                    )
                );
            }
        }
    } else {
        add_error(
            sprintf(
                $translate->_("There was no learner found. Click <a href='%s'>here</a> to return to your dashboard"),
                ENTRADA_URL ."/dashboard"
            )
        );
    }

    if ((isset($_GET["objective_id"])) && ($objective_id = clean_input($_GET["objective_id"], array("int", "trim")))) {
        $PROCESSED["objective_id"] = $objective_id;
    } else {
        add_error($translate->_("Please provide an objective id"));
    }

    if ((isset($_GET["course_id"])) && ($course_id = clean_input($_GET["course_id"], array("int", "trim")))) {
        $PROCESSED["course_id"] = $course_id;
    } else {
        add_error($translate->_("Please provide a course id"));
    }

    $PROCESSED["start_date"] = null;
    $PROCESSED["finish_date"] = null;
    if ($filter_start_timestamp = Entrada_Utilities::arrayValueOrDefault($_GET, "start_date")) {
        $tmp_input = clean_input($filter_start_timestamp . " 00:00:00", array("trim", "striptags"));
        $dt = DateTime::createFromFormat("Y-m-d H:i:s", $tmp_input);
        if ($dt !== false && !array_sum($dt->getLastErrors())) {
            $PROCESSED["start_date"] = $dt->getTimestamp();
        }
    }
    if ($filter_end_timestamp = Entrada_Utilities::arrayValueOrDefault($_GET, "finish_date")) {
        $tmp_input = clean_input($filter_end_timestamp . " 23:59:59", array("trim", "striptags"));
        $dt = DateTime::createFromFormat("Y-m-d H:i:s", $tmp_input);
        if ($dt !== false && !array_sum($dt->getLastErrors())) {
            $PROCESSED["finish_date"] = $dt->getTimestamp();
        }
    }
    if (!$ERROR) {
        $learner = Models_User::fetchRowByID($PROCESSED["proxy_id"]);
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/assessments/learner/cbme?proxy_id=".$PROCESSED["proxy_id"], "title" => sprintf($translate->_("%s's Dashboard"), $learner->getFirstname()." ".$learner->getLastname()));
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/assessments/learner/cbme/cbmestatushistory?proxy_id=".$PROCESSED["proxy_id"]."&objective_id=".$PROCESSED["objective_id"], "title" => $translate->_("EPA Status History"));
        ?>
        <h1><?php echo $translate->_("EPA Status History"); ?></h1>
        <?php

        $filter_options = array(
            "reset_button_text"  => $translate->_("Reset"),
            "apply_button_text"  => $translate->_("Apply"),
            "form_reset_url"     => ENTRADA_URL . "/assessments/learner/cbme/epastatushistory?proxy_id=" . $PROCESSED["proxy_id"] . "&objective_id=" . $PROCESSED["objective_id"] . "&course_id=" . $PROCESSED["course_id"],
            "form_action_url"    => ENTRADA_URL . "/assessments/learner/cbme/epastatushistory?proxy_id=" . $PROCESSED["proxy_id"],
            "proxy_id"           => $PROCESSED["proxy_id"],
            "course_id"          => $PROCESSED["course_id"],
            "objective_id"       => $PROCESSED["objective_id"],
            "filter_start_date"  => $PROCESSED["start_date"],
            "filter_finish_date" => $PROCESSED["finish_date"],
        );
        $filter_list_view = new Views_CBME_Filter_Date();
        $filter_list_view->render($filter_options);

        $completion_model = new Models_Objective_Completion();
        $completed_objective = $completion_model->fetchRowByObjectiveID($PROCESSED["objective_id"], $PROCESSED["course_id"], $PROCESSED["proxy_id"]);

        $epa_status_card_view = new Views_CBME_StatusCard();
        if ($PROCESSED["start_date"] || $PROCESSED["finish_date"]) {
            $objectives = $completion_model->fetchAllByObjectiveIDAndDate($PROCESSED["objective_id"], $PROCESSED["course_id"], $PROCESSED["proxy_id"], $PROCESSED["start_date"], $PROCESSED["finish_date"]);
        } else {
            $objectives = $completion_model->fetchAllByObjectiveID($PROCESSED["objective_id"], $PROCESSED["course_id"], $PROCESSED["proxy_id"]);
        }

        $learning_objective = Models_Objective::fetchRow($PROCESSED["objective_id"]);

        ?>
        <h2><?php echo $translate->_("Current Status"); ?></h2>
        <div class="bordered-container">
            <?php if ($completed_objective) : ?>
                <i class="fa fa-check-circle-o list-item-status-complete large-icon"></i>
                <span class="middle-align"><strong><?php echo $translate->_("Completed"); ?></strong></span>
            <?php else : ?>
                <i class="fa fa-circle-o list-item-status-incomplete large-icon"></i>
                <span class="middle-align"><strong><?php echo $translate->_("In Progress"); ?></strong></span>
            <?php endif; ?>
        </div>
        <h2><?php echo $translate->_("Status History") ?></h2>
        <?php
        if ($objectives) {
            foreach ($objectives as $objective) {
                $creator = Models_User::fetchRowByID($objective->getCreatedBy());
                if ($creator) {
                    $creator_name = $creator->getFirstname() . " " . $creator->getLastname();
                } else {
                    $creator_name = "";
                }
                $objective_completed = new Models_Objective_Completion();
                $objective_completed->setLoCompletionID($objective->getLoCompletionID());
                $objective_completed->setProxyID($objective->getProxyID());
                $objective_completed->setCreatedDate($objective->getCreatedDate());
                $objective_completed->setCreatedBy($objective->getCreatedBy());
                $objective_completed->setCreatedReason($objective->getCreatedReason());
                if ($objective->getDeletedBy()) {
                    $deletor = Models_User::fetchRowByID($objective->getDeletedBy());
                    $deletor_name = $deletor->getFirstname() . " " . $deletor->getLastname();
                }
                if ($objective->getDeletedDate()) {
                    if (isset($PROCESSED["start_date"]) && isset($PROCESSED["finish_date"])) {
                        if ($PROCESSED["start_date"] <= $objective->getDeletedDate() && $PROCESSED["finish_date"] >= $objective->getDeletedDate()) {
                            $deletor = Models_User::fetchRowByID($objective->getDeletedBy());
                            $deletor_name = $deletor->getFirstname() . " " . $deletor->getLastname();
                            $epa_status_card_view->render(array("objective" => $objective, "creator_name" => $deletor_name));
                            $epa_status_card_view->render(array("objective" => $objective_completed, "creator_name" => $creator_name));
                        } else {
                            $epa_status_card_view->render(array("objective" => $objective_completed, "creator_name" => $creator_name));
                        }
                    } else {
                        $epa_status_card_view->render(array("objective" => $objective, "creator_name" => $deletor_name));
                        $epa_status_card_view->render(array("objective" => $objective_completed, "creator_name" => $creator_name));
                    }
                } else {
                    $epa_status_card_view->render(array("objective" => $objective, "creator_name" => $creator_name));
                }
            }
        } else {
            ?>
            <div class="alert alert-info"><?php echo $translate->_("There were no EPA status changes found"); ?></div>
            <?php
        }
        ?>
        <h2><?php echo $translate->_("Update Status"); ?></h2>
        <div class="status-card">
            <div class="status-card-header">
                <?php if ($completed_objective) : ?>
                    <h3 class="inline-block no-margin"><?php echo $translate->_("Update status to In Progress"); ?></h3>
                    <i class="pull-right fa fa-circle-o list-item-status-incomplete large-icon inline-block"></i>
                <?php else : ?>
                    <h3 class="inline-block no-margin"><?php echo $translate->_("Update status to Complete"); ?></h3>
                    <i class="pull-right fa fa-check-circle-o list-item-status-complete large-icon inline-block"></i>
                <?php endif; ?>
            </div>
            <div class="status-card-body">
                <form class="no-margin" id="update-epa-status-form">
                    <?php if ($completed_objective) : ?>
                        <div><span class="muted"><?php echo $translate->_("Comment (Required)"); ?></span></div>
                    <?php else: ?>
                        <div><span class="muted"><?php echo $translate->_("Comment (Optional)"); ?></span></div>
                    <?php endif ?>
                    <div>
                        <textarea name="reason" id="reason-description" rows="3" class="full-width" form="update-epa-status-form"></textarea>
                    </div>
                    <div class="inline-block full-width">
                        <?php if ($completed_objective) : ?>
                            <input type="submit" disabled class="btn btn-primary space-above submit-progress-change incomplete-objective" value="<?php echo $translate->_("Mark as In Progress"); ?>" />
                        <?php else: ?>
                            <input type="submit" class="btn btn-primary space-above submit-progress-change" value="<?php echo $translate->_("Mark as Complete"); ?>" />
                        <?php endif ?>
                    </div>
                    <input type="hidden" name="proxy_id" value="<?php echo $PROCESSED["proxy_id"]; ?>" />
                    <input type="hidden" name="objective_id" value="<?php echo $PROCESSED["objective_id"]; ?>" />
                    <input type="hidden" name="objective_set" value="<?php echo $translate->_("EPA"); ?>">
                    <?php if ($completed_objective) : ?>
                        <input type="hidden" name="action" value="<?php echo $translate->_("incomplete"); ?>" />
                    <?php else: ?>
                        <input type="hidden" name="action" value="<?php echo $translate->_("complete"); ?>" />
                    <?php endif ?>
                </form>
            </div>
        </div>
        <?php
    } else {
        echo display_error();
    }
}