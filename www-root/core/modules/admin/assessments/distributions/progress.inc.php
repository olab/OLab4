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
 * This file is used to review the progress of distributions.
 *
 * @author Organisation: Queen's univeristy
 * @author Unit: Education Technology unit
 * @author Developer:  Devin Monroe <dm149@queensu.ca>, Joshua Belanger <jb301@queensu.ca>, Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
 */
if (!defined("IN_DISTRIBUTIONS")) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "read", false)) {
    $ERROR++;
    $ERRORSTR[] = $translate->_("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:" . html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" . html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance.");
    echo display_error();
    application_log("error", "Group [" . $GROUP . "] and role [" . $ROLE . "] do not have access to this module [" . $MODULE . "]");
} else {
    $DISTRIBUTION_ID = 0;
    $PREFERENCES = preferences_load($MODULE);

    if (isset($_GET["adistribution_id"]) && $tmp_input = clean_input($_GET["adistribution_id"], array("trim", "int"))) {
        $DISTRIBUTION_ID = $tmp_input;
    } elseif (isset($_POST["adistribution_id"]) && $tmp_input = clean_input($_POST["adistribution_id"], array("trim", "int"))) {
        $DISTRIBUTION_ID = $tmp_input;
    }

    if (isset($_GET["dtype"]) && $tmp_input = clean_input($_GET["dtype"], array("trim", "striptags"))) {
        $DTYPE = $tmp_input;
    } else {
        $DTYPE = "";
    }

    if (isset($_GET["mode"]) && $tmp_input = clean_input($_GET["mode"], array("trim", "striptags"))) {
        $mode = $tmp_input;
    } else {
        $mode = "";
    }

    $delegator = false;
    $distribution_methods = array();
    $distribution_methods[] = array("target_id" => "date_range", "target_label" => $translate->_("Date Range"));
    $distribution_methods[] = array("target_id" => "rotation_schedule", "target_label" => $translate->_("Rotation Schedule"));
    $distribution_methods[] = array("target_id" => "delegation", "target_label" => $translate->_("Delegation"));

    $PREFERENCES["target_view"] = "list";
    if (!isset($PREFERENCES["target_status_view"])) {
        $PREFERENCES["target_status_view"] = "pending";
    }

    // Save current URL to return to from editor.
    $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["distributions"]["distribution_editor_referrer"] = array(
        "url" => html_encode(ENTRADA_URL . "/admin/assessments/distributions?section=progress&adistribution_id=" . $DISTRIBUTION_ID),
        "from_index" => false,
        "adistribution_id" => $DISTRIBUTION_ID);

    if ($DISTRIBUTION_ID) {
        $BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/assessments/distributions?" . replace_query(array("section" => "edit", "id" => $DISTRIBUTION_ID)), "title" => "Show Progress");
        if ($NOTICE) {
            echo display_notice();
        }
        if ($ERROR) {
            echo display_error();
        }

        $HEAD[] = "<script type=\"text/javascript\" >var ENTRADA_URL = '" . ENTRADA_URL . "';</script>";
        $HEAD[] = "<script type=\"text/javascript\" >var MODULE = '" . $MODULE . "';</script>";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/" . $MODULE . "/" . $MODULE . ".css\" />";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/" . $MODULE . "/distribution-progress.css\" />";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.advancedsearch.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/assessment-targets.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/distributions/index.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/distributions/progress.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.timepicker.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.advancedsearch.css\" />";
        $HEAD[] = "<script type=\"text/javascript\" >var internal_assessor_label = '" . $translate->_("Internal assessor") . "';</script>";
        $HEAD[] = "<script type=\"text/javascript\" >var external_assessor_label = '" . $translate->_("External assessor") . "';</script>";
        $HEAD[] = "<script type=\"text/javascript\" >var individual_author_label = '" . $translate->_("Individual") . "';</script>";
        $HEAD[] = "<script type=\"text/javascript\" >var course_author_label = '" . $translate->_("Course") . "';</script>";
        $HEAD[] = "<script type=\"text/javascript\" >var organisation_author_label = '" . $translate->_("Organisation") . "';</script>";
        $HEAD[] = "<link href=\"" . ENTRADA_URL . "/css/assessments/assessment-public-index.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
        ?>
        <script type="text/javascript">
            var progress_page_translations = {};
            progress_page_translations.pdf_unavailable = "<?php echo $translate->_("PDF download is currently unavailable. PDF generator library is not configured.")?>";
        </script>
        <?php
        if (!$ERROR) {
            $HEAD[] = "<script type=\"text/javascript\">var distribution_id =\"" . $DISTRIBUTION_ID . "\"</script>";
            $distribution = Models_Assessments_Distribution::fetchRowByID($DISTRIBUTION_ID);
            $date_range_start = 0;
            $date_range_end = 0;
            $details = array();

            if ($distribution) { ?>
                <div class="row-fluid clear center">
                    <h1 class="space-below"><?php echo $translate->_("Progress for ") . $distribution->getTitle(); ?></h1>
                </div>
                <?php
                if ($delegator = Models_Assessments_Distribution_Delegator::fetchRowByDistributionID($distribution->getID())) {
                    // Assemble the data used by this view
                    $view_options = array();

                    $view_options["active_tab"] = "pending";
                    if (isset($PREFERENCES["target_status_view"])) {
                        $view_options["active_tab"] = $PREFERENCES["target_status_view"];
                    }

                    // Create distribution delegation helper object
                    $distribution_delegation = new Entrada_Utilities_Assessments_DistributionDelegation(array("adistribution_id" => $distribution->getID()));

                    $view_options["distribution_id"] = $distribution->getID();
                    $view_options["distribution_data"] = $distribution_delegation->getAllDistributionData($distribution->getID(), false, true, true);
                    $view_options["delegator_data"] = $distribution_delegation->getUserByType($delegator->getDelegatorID(), $delegator->getDelegatorType());
                    $view_options["all_possible_assessors"] = $distribution_delegation->getPossibleAssessors();
                    $view_options["delegation_summary"] = $distribution_delegation->getDelegationSummary();

                    if (isset($view_options["distribution_data"]["delegations"])) {
                        $view_options["assignments_summary"] = $distribution_delegation->getAssignmentsSummary($view_options["distribution_data"]["delegations"]);
                    } else {
                        $view_options["assignments_summary"] = false;
                    }

                    // build the list of all delegation tasks, past, present, and future. Note: this populates the object's internal task list.
                    $view_options["all_possible_tasks_list"] = $distribution_delegation->buildDelegationTaskList();

                    // Fetch the actual complete delegation tasks, regardless of whether they are potentially existing or not
                    $view_options["all_completed_tasks"] = $distribution_delegation->fetchCompletedTasks();

                    // Of the delegation tasks in the list, determine which ones are "upcoming" (uses the internal list of the object)
                    $view_options["upcoming_delegations"] = $distribution_delegation->determineUpcomingDelegations();

                    // Clear the task list (cleans up memory a bit) and store the clean object.
                    $distribution_delegation->clearTaskList();
                    $view_options["distribution_delegation_utility"] = $distribution_delegation;

                    // If there are tasks (actual or potential), then count them up.
                    $pending_delegation_count = 0;
                    if (!empty($view_options["all_possible_tasks_list"]) && !empty($view_options["distribution_data"])) {
                        foreach ($view_options["all_possible_tasks_list"][$distribution->getID()] as $all_task) {
                            if (empty($all_task["targets"]) || empty($all_task["assessors"])) {
                                continue; // sanity check: ignore invalid tasks
                            }
                            if ($all_task["meta"]["should_exist"] && empty($all_task["current_record"])) {
                                $pending_delegation_count++;
                            } else if ($all_task["meta"]["should_exist"] && !empty($all_task["current_record"])) {
                                if (!$all_task["current_record"]->getCompletedDate()) {
                                    $pending_delegation_count++;
                                }
                            }
                        }
                    }

                    // Store the counts
                    $view_options["delegation_count_pending"] = $pending_delegation_count;
                    $view_options["delegation_count_upcoming"] = count($view_options["upcoming_delegations"]);
                    $view_options["delegation_count_completed"] = count($view_options["all_completed_tasks"]);

                    // Create our Delegation Progress view object (with default html container options)
                    $delegation_progress_view = new Views_Assessments_Distribution_DelegationProgress(
                        array(
                            "id" => "delegation-progress-container",
                            "class" => "row-fluid delegation-progress space-above space-below medium"
                        )
                    );

                    // Render the view, using the delegation data
                    $delegation_progress_view->render($view_options, false, true);

                } else { // Non delegation based progress page

                    if ($distribution_eventtype = Models_Assessments_Distribution_Eventtype::fetchAllByAdistributionID($distribution->getID())) {

                        echo "<div class=\"alert alert-notice space-above\">{$translate->_("The distribution progress page does not currently display progress for learning event based distributions. Please visit the <strong>My Assessments</strong> page for information on learning event based assessment tasks.")}</div>";

                    } else {

                        if ($distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($DISTRIBUTION_ID)) {

                            // If the distribution is based on a schedule, retrieve the schedule to display block information and navigation/filtering.
                            $rotation_schedule = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID());
                            $rotation_start = $rotation_schedule->getStartDate();
                            $rotation_end = $rotation_schedule->getEndDate();
                            $children = $rotation_schedule->getChildren();
                            $child_count = count($children);
                            $previous_block = 0;
                            $current_block = (isset($_GET["block"]) ? $_GET["block"] : 0);
                            $block_count = 0;

                            foreach ($children as $child) {
                                $block_count++;
                                $child_schedule_id = $child->getScheduleID();
                                if ($child_schedule_id == $current_block) {
                                    $block_name = $child->getTitle();
                                }
                                if ($block_count == 1) {
                                    if (!$current_block) {
                                        $current_block = $child->getScheduleID();
                                    }
                                    $min_block_value = $child->getScheduleID();
                                }
                                if ($block_count == $child_count) {
                                    $max_block_value = $child->getScheduleID();
                                }
                            }
                            ?>
                            <br>
                            <input type="hidden" class="view-toggle active hide" value="list" data-view="list">
                            <input type="hidden" class="view-toggle active hide" name="view" value="view_as">
                            <input type="hidden" name="progress-distribution-id" value="<?php echo $distribution->getID(); ?>">
                            <div class="row-fluid space-below">
                                <div class="span4">
                                    <div class="btn-group">
                                        <a class="btn<?php echo($DTYPE == "block" ? " active" : " "); ?>" alt="<?php echo $translate->_("Show by Block"); ?>" title="<?php echo $translate->_("Show by Block"); ?>" href="<?php echo "?" . replace_query(array("dtype" => "block", "block" => $current_block)); ?>"><?php echo $translate->_("Block") ?></a>
                                        <a class="btn<?php echo($DTYPE == "date_range" || $DTYPE == "" ? " active" : " "); ?>" alt="<?php echo $translate->_("Show by Date Range"); ?>" title="<?php echo $translate->_("Show by Date Range"); ?>" href="<?php echo "?" . replace_query(array("dtype" => "date_range")); ?>"><?php echo $translate->_("Date Range") ?></a>
                                    </div>
                                </div>
                                <div class="span8">
                                    <?php if ($DTYPE == "block") { ?>
                                        <div class="btn-group span10 pull-right">
                                            <a class="btn btn-default span2" href="<?php echo(($DTYPE == "date_range") ? ((isset($tstamp) && $tstamp > 0) ? "?" . replace_query(array("tstamp" => ($current_tstamp))) : "?" . replace_query(array("tstamp" => ($current_tstamp)))) : ((isset($min_block_value) && $min_block_value == $current_block) ? "?" . replace_query(array("block" => ($current_block))) : "?" . replace_query(array("block" => ($current_block - 1))))); ?>"
                                               alt="<?php $translate->_("Previous"); ?>" title="<?php $translate->_("Previous"); ?>"><i class="icon-chevron-left" <?php echo((isset($min_block_value) && $min_block_value == $current_block) ? "class=\"faded\"" : ""); ?>></i></a>
                                            <a class="btn btn-default span8" alt="<?php $translate->_("Current"); ?>" title="<?php $translate->_("Current"); ?>"><?php echo($DTYPE == "block" ? html_encode($block_name) : $translate->_(" Date Range")) ?></a>
                                            <a class="btn btn-default span2" href="<?php echo(($DTYPE == "date_range") ? ((isset($tstamp) && $tstamp > 0) ? "?" . replace_query(array("tstamp" => ($current_tstamp))) : "?" . replace_query(array("tstamp" => ($current_tstamp)))) : ((isset($max_block_value) && $max_block_value == $current_block) ? "?" . replace_query(array("block" => ($current_block))) : "?" . replace_query(array("block" => ($current_block + 1))))); ?>"
                                               alt="<?php $translate->_("Next"); ?>" title="<?php $translate->_("Next"); ?>"><i class="icon-chevron-right" <?php echo((isset($max_block_value) && $max_block_value == $current_block) ? "class=\"faded\"" : ""); ?>></i></a>
                                        </div>
                                    <?php } else {
                                        if ((isset($_POST["date_range_start"])) && ((int)trim($_POST["date_range_start"]))) {
                                            $date_range_start = strtotime($_POST["date_range_start"] . " 00:00:00");
                                        } else {
                                            $date_range_start = $rotation_start;
                                        }

                                        if ((isset($_POST["date_range_end"])) && ((int)trim($_POST["date_range_end"]))) {
                                            $date_range_end = strtotime($_POST["date_range_end"] . " 23:59:59");
                                        } else {
                                            $date_range_end = $rotation_end;
                                        }
                                        ?>
                                    <form name="date_range_from" id="date_range_from" class="form-inline" method="POST">
                                        <label class="control-label form-nrequired span5 text-right" for="date-range-start">
                                            <strong><?php echo $translate->_("Start"); ?>: </strong>
                                            <div class="input-append">
                                                <input id="date-range-start" name="date_range_start" class="input-small datepicker" type="text" value="<?php echo isset($date_range_start) ? html_encode(date("Y-m-d", $date_range_start)) : ""; ?>"/>
                                                <span class="add-on pointer"><i class="icon-calendar"></i></span>
                                            </div>
                                        </label>

                                        <label class="control-label form-nrequired span5 text-right" for="date-range-end">
                                            <strong><?php echo $translate->_("End"); ?>: </strong>
                                            <div class="input-append">
                                                <input id="date-range-end" name="date_range_end" class="input-small datepicker" type="text" value="<?php echo isset($date_range_end) ? html_encode(date("Y-m-d", $date_range_end)) : ""; ?>"/>
                                                <span class="add-on pointer"><i class="icon-calendar"></i></span>
                                            </div>
                                        </label>
                                        <div class="span2">
                                            <button type="submit" id="submit" class="btn btn-success pull-right space-left"><?php echo $translate->_("Filter"); ?></button>
                                        </div>
                                    </form>
                                    <?php } ?>
                                </div>
                            </div>
                            <?php
                            foreach ($children as $child) {
                                $child_schedule_id = $child->getScheduleID();
                                $block_start = $child->getStartDate();
                                $block_end = $child->getEndDate();
                                $block_name = $child->getTitle();
                                switch ($DTYPE) {
                                    case "block":
                                        if ($child_schedule_id == $current_block) {
                                            $date_range_start = $block_start;
                                            $date_range_end = $block_end;

                                            // Calculations for progress bar.
                                            $time_from_start = time() - $block_start;
                                            $block_total = $block_end - $block_start;
                                            if ($time_from_start > $block_total) {
                                                $percentage = 100;
                                            } else {
                                                if (time() > $block_start) {
                                                    $percentage = $time_from_start / $block_total * 100;
                                                } else {
                                                    $percentage = 0;
                                                }
                                            }

                                            echo "<h2 class=\"center\">" . html_encode($rotation_schedule->getTitle() . " - " . $block_name . ": " . date("D M d Y", $block_start) . " to " . date("D M d Y", $block_end)) . "</h2>";
                                            if (isset($percentage) && $percentage > 0) { ?>
                                                <div class="row-fluid clear">
                                                    <div class="progress span12">
                                                        <div class="bar" style="width: <?php echo $percentage; ?>%;">
                                                            <?php echo $translate->_("Block " . round($percentage, 0) . "% Complete") ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php
                                            };
                                        }
                                }
                            }

                        } else { ?>
                            <div class="row-fluid clear center space-below">
                                <h2><?php echo sprintf($translate->_("Date Range: %s to %s"), html_encode(date("Y-m-d", $distribution->getStartDate())), html_encode(date("Y-m-d", $distribution->getEndDate()))) ?></h2>
                            </div>
                            <?php
                        }

                        // Display the cutoff date if it is set.
                        $release_date = (is_null($distribution->getReleaseDate()) ? 0 : (int)$distribution->getReleaseDate());
                        if ($release_date) { ?>
                            <div class="alert alert-info">
                                <?php echo sprintf($translate->_("A <strong>cutoff date</strong> of <strong>%s</strong> has been set on this distribution. This means that only assessment tasks delivered <strong>after</strong> this date will be released."), html_encode(date("Y-m-d", $release_date))); ?>
                            </div>
                            <?php
                        }

                        $progress_object = new Entrada_Utilities_DistributionProgress();
                        $details = $progress_object->getDistributionProgress($DISTRIBUTION_ID, $date_range_start, $date_range_end);

                        $pending = 0;
                        if (isset($details["pending"])) {
                            foreach ($details["pending"] as $assessor_types) {
                                foreach ($assessor_types as $assessor) {
                                    foreach ($assessor["targets"] as $target) {
                                        $pending++;
                                    }
                                }
                            }
                        }
                        $in_progress = 0;
                        if (isset($details["inprogress"])) {
                            foreach ($details["inprogress"] as $assessor_types) {
                                foreach ($assessor_types as $assessor) {
                                    foreach ($assessor["targets"] as $target) {
                                        $in_progress++;
                                    }
                                }
                            }
                        }
                        $complete = 0;
                        if (isset($details["complete"])) {
                            foreach ($details["complete"] as $assessor_types) {
                                foreach ($assessor_types as $assessor) {
                                    foreach ($assessor["targets"] as $target) {
                                        $complete++;
                                    }
                                }
                            }
                        }
                        $assessment_permissions = $ENTRADA_ACL->amIAllowed("assessments", "update", false);
                        $pdf_generator = new Entrada_Utilities_Assessments_HTMLForPDFGenerator();
                        $disable_pdf_button = !$pdf_generator->configure();

                        if ($details) {
                            Views_Assessments_Distribution_Progress::renderProgressSection($PREFERENCES, $DISTRIBUTION_ID, $distribution, $details, $pending, $in_progress, $complete, $assessment_permissions, $disable_pdf_button);
                        } else {
                            echo "<div class=\"alert alert-danger\">" . $translate->_("There were no targets found for this distribution.") . "</div>";
                        }
                    }
                }
            } else {
                echo "<div class=\"alert alert-danger\">" . $translate->_("There was no distribution found with the given ID.") . "</div>";
            }
        }
    } else {
        application_log("error", "User tried to manage members a distribution without providing a distribution_id.");
        header("Location: " . ENTRADA_URL . "/admin/assessments/distributions");
        exit;
    }

    /** Render the required modals for this page here **/

    // Render the Delete Tasks modal (it is context sensitive; delegation or non-delegation based deletion)
    $delete_task_modal = new Views_Assessments_Modals_DeleteTasks();
    $delete_task_modal->render(array(
        "action_url" => ENTRADA_URL . "/assessments/assessment?section=api-assessment&adistribution_id=$DISTRIBUTION_ID",
        "modal_mode" => ($delegator)? "delegation" : "assessment",
        "deleted_reasons" => Models_Assessments_TaskDeletedReason::fetchAllRecordsOrderByOrderID()
    ));

    $reminder_modal = new Views_Assessments_Modals_SendReminders();
    $reminder_modal->render(array(
        "action_url" => ENTRADA_URL . "/assessments/assessment?section=api-assessment&adistribution_id=$DISTRIBUTION_ID"
    ));

    $pdf_modal = new Views_Assessments_Modals_GeneratePDF();
    $pdf_modal->render(array(
        "action_url" => ENTRADA_URL . "/admin/assessments/distributions?section=api-distributions&current-location=distributions"
    ));

    if (isset($details) && array_key_exists("distribution_target_type", $details) && $details["distribution_target_type"] == "proxy_id") {
        $add_task_modal = new Views_Assessments_Modals_AddTask();
        $add_task_modal->render(array(
            "action_url" => ENTRADA_URL . "/assessments/assessment?section=api-assessment&adistribution_id=$DISTRIBUTION_ID"
        ));
    }
}