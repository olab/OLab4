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
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_ASSESSMENT"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "read", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%s\">%s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));
    echo display_error();
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

    if (isset($_GET["adistribution_id"]) && $tmp_input = clean_input($_GET["adistribution_id"], array("trim", "int"))) {
        $DISTRIBUTION_ID = $PROCESSED["adistribution_id"] = $tmp_input;
    } else {
        add_error($translate->_("No distribution identifier provided."));
        echo display_error();
    }

    if (isset($_GET["addelegation_id"]) && $tmp_input = clean_input($_GET["addelegation_id"], array("trim", "int"))) {
        $DELEGATION_ID = $PROCESSED["addelegation_id"] = $tmp_input;
    } else {
        add_error($translate->_("No delegation identifier provided."));
        echo display_error();
    }

    if ((isset($_POST["date_range_start"])) && ((int)trim($_POST["date_range_start"]))) {
        $posted_date_range_start = strtotime($_POST["date_range_start"] . " 00:00:00");
    } else {
        $posted_date_range_start = false;
    }

    if ((isset($_POST["date_range_end"])) && ((int)trim($_POST["date_range_end"]))) {
        $posted_date_range_end = strtotime($_POST["date_range_end"] . " 23:59:59");
    } else {
        $posted_date_range_end = false;
    }

    $posted_current_block = (isset($_GET["block"]) ? $_GET["block"] : 0);

    if (isset($_GET["dtype"]) && $tmp_input = clean_input($_GET["dtype"], array("trim", "striptags"))) {
        $DTYPE = $tmp_input;
    } else {
        $DTYPE = "";
    }

    if (!$ERROR) {
        $distribution = Models_Assessments_Distribution::fetchRowByID($DISTRIBUTION_ID);
        $delegation = Models_Assessments_Distribution_Delegation::fetchRowByID($DELEGATION_ID);

        if (!$delegation) {
            echo display_error(array($translate->_("No delegation task found.")));
        } else if (!$distribution) {
            echo display_error(array($translate->_("No distribution found.")));
        } else if ($delegation->getDeletedDate()) {
            echo display_error(array($translate->_("This delegation task was deleted.")));
        } else if ($delegation->getAdistributionID() != $distribution->getID()) {
            echo display_error(array($translate->_("Delegation task does not correspond with distribution.")));
        } else {
            $BREADCRUMB[] = array("url" => ENTRADA_URL . "/$MODULE/$SUBMODULE/$SECTION", "title" => ($distribution ? sprintf($translate->_("%s - Select Targets"), $distribution->getTitle()) : "Delegation"));
            $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = '" . ENTRADA_URL . "'</script>";
            $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/delegation.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
            $HEAD[] = "<link href=\"" . ENTRADA_URL . "/css/assessments/delegation.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";

            $show_block_controls = false; // In the future, if the block controls are required, toggle this to true to enable them. Currently, although supported and functional, they are not used on this page.

            if ($posted_date_range_end === false || $posted_date_range_start === false) {
                $date_range_start = $delegation->getStartDate();
                $date_range_end = ($delegation->getEndDate()) ? $delegation->getEndDate() : PHP_INT_MAX;
            } else {
                $date_range_start = $posted_date_range_start;
                $date_range_end = $posted_date_range_end;
            }

            $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($distribution->getID());
            $distribution_delegation = new Entrada_Utilities_Assessments_DistributionDelegation(array("adistribution_id" => $distribution->getID(), "addelegation_id" => $delegation->getID()));
            $delegation_status = $distribution_delegation->getDelegationStatus();

            $next_step_url = ENTRADA_URL . "/assessments/delegation?section=selection&addelegation_id=$DELEGATION_ID&adistribution_id=$DISTRIBUTION_ID";
            ?>
            <script type="text/javascript">
                var delegation_summary_msgs = {};
                delegation_summary_msgs.error_default = "<?php echo $translate->_("Unknown error, please try again later."); ?>";
                delegation_summary_msgs.error_select_targets = "<?php echo $translate->_("Please select one or more targets."); ?>";
                delegation_summary_msgs.error_add_removal_reason = "<?php echo $translate->_("Please add a reason for removal."); ?>";
            </script>
            <div class="delegation-interface-container">
                <h1 class="center no-margin">
                    <?php echo $distribution->getTitle(); ?> &mdash; <?php echo $translate->_("Delegation"); ?>
                </h1>
                <?php
                $displayed_block = "";
                if ($distribution_schedule) {
                    $rotation_schedule = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID());
                    $children = $rotation_schedule->getChildren();
                    $child_count = count($children);
                    $previous_block = 0;
                    $parent_schedule = null;

                    if ($child_count == 0) {
                        $block_name = $rotation_schedule->getTitle();
                        $parent_schedule = Models_Schedule::fetchRowByID($rotation_schedule->getScheduleParentID());
                    }

                    $displayed_title_text = $distribution_delegation->getConcatenatedBlockOrDateString($date_range_start, $date_range_end, $rotation_schedule);
                    $block_count = 0;
                    $current_block = 0;
                    if ($posted_current_block) {
                        $current_block = $posted_current_block;
                    }
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
                } else {
                    $displayed_title_text = $distribution_delegation->getConcatenatedBlockOrDateString($date_range_start, $date_range_end);
                }
                ?>

                <?php if ($displayed_title_text): ?>
                    <h2 id="delegation-assignment-title" class="center no-margin clearfix">
                        <strong><?php echo $displayed_title_text; ?></strong>
                        <span>
                            <p><?php echo strftime("%Y-%m-%d", $date_range_start) . $translate->_(" to ") . strftime("%Y-%m-%d", $date_range_end);?></p>
                        </span>
                    </h2>
                <?php endif; ?>

                <div id="msgs" class=""></div>

                <div id="distribution-information" class="space-above">
                    <?php if ($distribution_schedule && $show_block_controls): // If the distribution is based on a schedule, retrieve the schedule to display block information and navigation/filtering. ?>
                        <div class="row-fluid clear">
                            <div class="span3 pull-left">
                                <div class="btn-group">
                                    <a class="btn<?php echo($DTYPE == "block" ? " active" : " "); ?>" alt="<?php echo $translate->_("Show by Block"); ?>" title="<?php echo $translate->_("Show by Block"); ?>" href="<?php echo "?" . replace_query(array("dtype" => "block", "block" => $current_block)); ?>"><?php echo $translate->_("Block") ?></a>
                                    <a class="btn<?php echo($DTYPE == "date_range" || $DTYPE == "" ? " active" : " "); ?>" alt="<?php echo $translate->_("Show by Date Range"); ?>" title="<?php echo $translate->_("Show by Date Range"); ?>" href="<?php echo "?" . replace_query(array("dtype" => "date_range")); ?>"><?php echo $translate->_("Date Range") ?></a>
                                </div>
                            </div>
                            <div class="span9 pull-right">
                                <?php if ($DTYPE == "block"): ?>
                                    <div class="distribution-block-select btn-group span12">
                                        <a class="pull-right btn btn-default span2 <?php echo ($child_count == 0) ? "disabled" : ""; ?>" href="<?php echo(($DTYPE == "date_range") ? ((isset($tstamp) && $tstamp > 0) ? "?" . replace_query(array("tstamp" => ($current_tstamp))) : "?" . replace_query(array("tstamp" => ($current_tstamp)))) : ((isset($max_block_value) && $max_block_value == $current_block) ? "?" . replace_query(array("block" => ($current_block))) : "?" . replace_query(array("block" => ($current_block + 1))))); ?>" alt="<?php $translate->_("Next"); ?>" title="<?php $translate->_("Next"); ?>"><i class="icon-chevron-right" <?php echo((isset($max_block_value) && $max_block_value == $current_block) ? "class=\"faded\"" : ""); ?>></i></a>
                                        <a class="pull-right btn btn-default span7" alt="<?php $translate->_("Current"); ?>" title="<?php $translate->_("Current"); ?>"><?php echo($DTYPE == "block" ? html_encode($block_name) : $translate->_(" Date Range")) ?></a>
                                        <a class="pull-right btn btn-default span2 <?php echo ($child_count == 0) ? "disabled" : ""; ?>" href="<?php echo(($DTYPE == "date_range") ? ((isset($tstamp) && $tstamp > 0) ? "?" . replace_query(array("tstamp" => ($current_tstamp))) : "?" . replace_query(array("tstamp" => ($current_tstamp)))) : ((isset($min_block_value) && $min_block_value == $current_block) ? "?" . replace_query(array("block" => ($current_block))) : "?" . replace_query(array("block" => ($current_block - 1))))); ?>" alt="<?php $translate->_("Previous"); ?>" title="<?php $translate->_("Previous"); ?>"><i class="icon-chevron-left" <?php echo((isset($min_block_value) && $min_block_value == $current_block) ? "class=\"faded\"" : ""); ?>></i></a>
                                    </div>
                                <?php else: ?>
                                    <form name="date_range_from" id="date_range_from" class="pull-right no-margin" method="POST">
                                        <div class="controls">
                                            <label class="control-label form-nrequired" for="date-range-start"><strong><?php echo $translate->_("Start"); ?>:</strong></label>
                                            <div class="input-append">
                                                <input id="date-range-start" name="date_range_start" class="input-small datepicker" type="text" value="<?php echo isset($date_range_start) ? html_encode(date("Y-m-d", $date_range_start)) : ""; ?>"/>
                                                <span class="add-on pointer"><i class="icon-calendar"></i></span>
                                            </div>

                                            <label class="control-label form-nrequired" for="date-range-end"><strong><?php echo $translate->_("End"); ?>:</strong></label>
                                            <div class="input-append">
                                                <input id="date-range-end" name="date_range_end" class="input-small datepicker" type="text" value="<?php echo isset($date_range_end) ? html_encode(date("Y-m-d", $date_range_end)) : ""; ?>"/>
                                                <span class="add-on pointer"><i class="icon-calendar"></i></span>
                                            </div>

                                            <input type="submit" id="submit" class="btn btn-success pull-right space-left" value="<?php echo $translate->_("Filter"); ?>"/>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php
                        if ($child_count) {
                            foreach ($children as $child) {
                                $child_schedule_id = $child->getScheduleID();
                                $block_start = $child->getStartDate();
                                $block_end = $child->getEndDate();
                                $block_name = $child->getTitle();
                                if ($DTYPE == "block") {
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
                                        echo "<h4 class=\"center\">" . html_encode($block_name . ": " . date("D M d Y", $block_start) . " to " . date("D M d Y", $block_end)) . "</h4>";
                                        if (isset($percentage) && $percentage > 0) { ?>
                                            <div class="row-fluid clear">
                                                <div class="progress span12 no-margin">
                                                    <div class="bar" style="padding-top:5px; width: <?php echo $percentage; ?>%;">
                                                        <?php echo $translate->_("Block " . round($percentage, 0) . "% Complete") ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                    }
                                }
                            }
                        } else {
                            echo '<h2 class="center space-below">';
                            echo html_encode($parent_schedule->getTitle() . " - " . $rotation_schedule->getTitle() . ": ");
                            echo date("D M d Y", $rotation_schedule->getStartDate()) . " to " . date("D M d Y", $rotation_schedule->getEndDate()) . "</h2>";
                        }
                        ?>
                    <?php endif; ?>
                </div>

                <div class="space-above">
                    <div id="delegation-mark-as-complete-btn">
                        <a href="#" class="btn pull-right">
                            <?php if ($delegation_status["completed"]): ?>
                                <i class="icon-check icon-black"></i>&nbsp;<?php echo $translate->_("This Delegation is Complete"); ?>
                            <?php else: ?>
                                <i class="icon-edit icon-black"></i>&nbsp;<?php echo $translate->_("Mark as Complete"); ?>
                            <?php endif; ?>
                        </a>
                    </div>
                    <div id="target-search-block" class="clearfix space-below">
                        <input class="search-icon" type="text" id="target-search-input" placeholder="<?php echo $translate->_("Search Targets...") ?>"/>
                    </div>
                </div>

                <div id="targets-pending-container" class="targets-container">
                    <?php $targets_and_assessors = $distribution_delegation->getDelegationTargetsAndAssessors($date_range_start, $date_range_end); ?>
                    <h2><?php echo $translate->_("Targets") ?></h2>
                    <?php
                        // It is possible that, due to the date range specified, a delegation could have no targets (since none are scheduled in that rotation, for example)
                        // So we check for valid targets before showing the table.
                        $empty_targets_and_assessors = false;
                        if (empty($targets_and_assessors)) {
                            $empty_targets_and_assessors = true;
                        } else {
                            $empty_groupings = 0;
                            foreach ($targets_and_assessors as $ta) {
                                if ($ta["no_targets"]) {
                                    $empty_groupings++;
                                }
                                $empty_targets_and_assessors = ($empty_groupings == count($targets_and_assessors)) ? true : false;
                            }
                        }
                    ?>
                    <?php if ($empty_targets_and_assessors): ?>
                        <p id="no-pending-targets" class="no-targets"><?php echo $translate->_("There are currently no targets pending.") ?></p>
                    <?php else: ?>
                        <div id="targets-pending-table-container">
                            <table class="target-table table table-striped table-bordered">
                                <thead>
                                <tr>
                                    <th width="5%"><?php echo $translate->_("Add"); ?></th>
                                    <th width="35%"><?php echo $translate->_("Targets"); ?></th>
                                    <th width="30%"><?php echo $translate->_("Assessors"); ?></th>
                                    <th width="25%"><?php echo $translate->_("Added On"); ?></th>
                                    <th width="5%"><?php echo $translate->_("Remove"); ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($targets_and_assessors as $i => $target_data): ?>
                                        <?php if ($target_data["no_targets"]) continue; // There are no targets, so skip it. ?>
                                        <tr class="target-block">
                                            <?php if (isset($target_data["use_members"]) && $target_data["use_members"]): // users with photos ?>
                                            <td class="middle">
                                                <label class="checkbox">
                                                    <input id="<?php echo "target-checkbox-id-$i"?>" name="target_assign[]" type="checkbox" value="<?php echo "{$target_data["type"]}-{$target_data["scope"]}-{$target_data["member_id"]}"; ?>"/>
                                                </label>
                                            </td>
                                            <td class="target-block-target-details">
                                                <div class="userAvatar target-selection-avatar pull-left delegation-target-img" data-target-checkbox-id="<?php echo "target-checkbox-id-$i"; ?>">
                                                    <img src="<?php echo webservice_url("photo", array($target_data["member_id"], "official")); ?>" width="42" height="42" alt="<?php echo html_encode($target_data["member_fullname"]) ?>" class="img-circle" />
                                                </div>
                                                <div class="pull-left">
                                                    <p>
                                                        <a href="#" class="delegation-target-td" data-target-checkbox-id="<?php echo "target-checkbox-id-$i"; ?>">
                                                            <strong><?php echo html_encode($target_data["member_fullname"]); ?></strong>
                                                        </a>
                                                    </p>
                                                    <p>
                                                        <a href="mailto:<?php echo html_encode($target_data["member_email"]); ?>" class="delegation-target-td" data-target-checkbox-id="<?php echo "target-checkbox-id-$i"; ?>"><?php echo html_encode($target_data["member_email"]); ?></a>
                                                    </p>
                                                </div>
                                            </td>
                                            <?php else: // a non-user entity ?>
                                            <td class="middle">
                                                <label class="checkbox">
                                                    <input id="<?php echo "target-checkbox-id-$i"?>" name="target_assign[]" type="checkbox" value="<?php echo "{$target_data["type"]}-{$target_data["scope"]}-{$target_data["id"]}"; ?>"/>
                                                </label>
                                            </td>
                                            <td class="target-block-target-details">
                                                <div class="pull-left">
                                                    <a href="#" class="delegation-target-td" data-target-checkbox-id="<?php echo "target-checkbox-id-$i"; ?>"><?php echo html_encode($target_data["entity_name"]); ?></a>
                                                </div>
                                            </td>
                                            <?php endif; ?>
                                                <?php if (!empty($target_data["assessors"])): ?>
                                                    <td colspan="3">
                                                        <table class="targets-sub-table">
                                                            <tbody>
                                                                <?php foreach ($target_data["assessors"] as $assessor): ?>
                                                                <tr>
                                                                    <td width="48%" class="middle"><?php echo html_encode("{$assessor["firstname"]} {$assessor["lastname"]}"); ?></td>
                                                                    <td width="44%" class="middle"><?php echo strftime("%Y/%m/%d", $assessor["created_date"]); ?></td>
                                                                    <td width="8%" class="middle">
                                                                        <?php
                                                                        $use_name = $target_data["entity_name"];
                                                                        if ($target_data["use_members"]) {
                                                                            if ($target_data["entity_name"] != $target_data["member_fullname"]) {
                                                                                $use_name = $target_data["member_fullname"];
                                                                            }
                                                                        } ?>
                                                                        <div id="remove-assessor-<?php echo $assessor["assessor_value"] ?>"
                                                                             data-target-id="<?php echo html_encode($assessor["target_value"])?>"
                                                                             data-target-name="<?php echo html_encode($use_name)?>"
                                                                             data-target-type="<?php echo html_encode($assessor["target_type"])?>"
                                                                             data-addassignment-id="<?php echo html_encode($assessor["addassignment_id"])?>"
                                                                             data-assessor-id="<?php echo html_encode($assessor["assessor_value"])?>"
                                                                             data-assessor-type="<?php echo html_encode($assessor["assessor_type"])?>"
                                                                             data-assessor-name="<?php echo html_encode("{$assessor["firstname"]} {$assessor["lastname"]}") ?>"
                                                                             data-assessment-id="<?php echo html_encode($assessor["dassessment_id"]);?>"
                                                                             data-is-duplicate="<?php echo html_encode($assessor["is_duplicate"])?>"
                                                                             class="target-list-remove-assessor">
                                                                            <span class="targets-sub-table-remove close">&times</span>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                <?php else: ?>
                                                    <td class="middle" colspan="3">
                                                        <p class="help-inline"><?php echo $translate->_("No assessors assigned."); ?></p>
                                                    </td>
                                                <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="hide no-search-targets">
                                        <td colspan="3"><?php echo $translate->_("No targets found matching your search criteria."); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div id="targets-pending-table-container-no-search" class="hide no-search-targets">
                            <p><?php echo $translate->_("No targets found matching your search criteria."); ?></p>
                        </div>
                        <i><?php echo $translate->_("Task delivered on");?>: <?php echo strftime("%Y-%m-%d", $delegation->getDeliveryDate()) ?></i>
                        <a id="delegation-add-assessors-btn" class="btn btn-success pull-right" href="#"><?php echo $translate->_("Select Assessors"); ?></a>

                        <form id="delegation-add-targets-form" name="add_targets_form" class="hide" action="<?php echo $next_step_url ?>" method="post">
                            <input type="hidden" name="adistribution_id" value="<?php echo $DISTRIBUTION_ID; ?>">
                            <input type="hidden" name="addelegation_id" value="<?php echo $DELEGATION_ID; ?>">
                            <input type="hidden" name="date_range_start" value="<?php echo $date_range_start; ?>">
                            <input type="hidden" name="date_range_end" value="<?php echo $date_range_end; ?>">
                            <input type="hidden" name="all_targets_selected" id="all_targets_selected" value="0">
                        </form>

                    <?php endif; ?>
                </div>
            </div>

            <!-------- Mark as complete modal --------->

            <div id="mark-as-complete-modal" class="modal delegation-modal fade hide">

                <div class="modal-header text-center">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <?php if ($delegation_status["completed"]): ?>
                        <h2><?php echo $translate->_("Update Completion Status"); ?></h2>
                    <?php else: ?>
                        <h2><?php echo $translate->_("Mark As Complete"); ?></h2>
                    <?php endif; ?>
                </div>

                <div class="modal-body space-below medium">
                    <?php if ($delegation_status["completed"]): ?>
                        <p class="text-center"><?php echo $translate->_("This delegation is marked as complete. You may update the reason for completion here.");?></p>
                    <?php else: ?>
                        <p class="text-center"><?php echo $translate->_("This will mark the delegation task as complete, removing it from your active task list.");?></p>
                    <?php endif; ?>

                    <div class="complete-modal-options">
                        <label class="radio" for="completion-reason">
                            <input type="radio" id="completion-reason" name="comment_type" value="completed"><?php echo $translate->_("All assessment tasks have been delegated"); ?>
                        </label>
                        <label class="radio" for="completion-reason-other">
                            <input type="radio" id="completion-reason-other" name="comment_type" value="completed"><?php echo $translate->_("Other:"); ?>
                        </label>
                        <textarea id="completion-reason-text" class="disabled full-width" disabled data-text-modified="false"><?php echo $translate->_("Enter other reason for completion");?></textarea>
                    </div>
                </div>

                <div class="modal-footer text-center">
                    <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
                    <?php if ($delegation_status["completed"]): ?>
                        <a href="#" class="btn btn-primary pull-right disabled" id="modal-delegation-complete-btn"><?php echo $translate->_("Update"); ?></a>
                    <?php else: ?>
                        <a href="#" class="btn btn-primary pull-right disabled" id="modal-delegation-complete-btn"><?php echo $translate->_("Mark as Complete"); ?></a>
                    <?php endif; ?>
                </div>

            </div>

            <!-------- Remove assessor modal --------->

            <?php $deleted_reasons = Models_Assessments_TaskDeletedReason::fetchAllRecords(); ?>
            <div id="remove-assessor-confirm-modal" class="modal delegation-modal fade hide">

                <div class="modal-header text-center">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h2><?php echo $translate->_("Remove Assessor"); ?></h2>
                </div>

                <div class="modal-body">
                    <input type="hidden" class="hide" id="remove-assessor-info" value="">
                    <input type="hidden" class="hide" id="default-removal-text" value="<?php echo $translate->_("Enter reason...");?>">
                    <p class="text-center">
                        <?php echo sprintf($translate->_("This action will remove %s as an assessor for %s."),
                            "<strong><span id='remove-assessor-assessor-name'></span></strong>",
                            "<strong><span id='remove-assessor-target-name'></span></strong>"
                        ); ?>
                    </p>
                    <div class="removal-modal-options">
                        <p><strong><?php echo $translate->_("Please select a reason for removal:");?></strong></p>
                        <select id="delete-tasks-reason" name="delete-tasks-reason" class="space-below">
                            <?php foreach ($deleted_reasons as $reason): ?>
                                <option value="<?php echo $reason->getID(); ?>"><?php echo $reason->getDetails(); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p><strong><?php echo $translate->_("Notes:");?></strong></p>
                        <textarea id="removal-reason-text" class="full-width" data-text-modified="false"></textarea>
                    </div>
                    <p class="text-center space-above medium space-below"><?php echo $translate->_("The assessor will be notified via email."); ?></p>
                </div>

                <div class="modal-footer text-center">
                    <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
                    <a href="#" class="btn btn-danger pull-right" id="modal-remove-assessor-btn"><?php echo $translate->_("Confirm"); ?></a>
                </div>

            </div>
            <?php
        } // END if distribution
    } // END if !ERROR
} // END authorized