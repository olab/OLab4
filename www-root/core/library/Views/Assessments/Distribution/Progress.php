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
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */
class Views_Assessments_Distribution_Progress extends Views_Assessments_Base {

    public static function renderProgressSection($PREFERENCES, $DISTRIBUTION_ID, $distribution, $details, $pending, $in_progress, $complete, $assessment_permissions, $disable_pdf_button = false) {
        global $translate;
        ?>
        <div id="assessment-block" class="clearfix space-below">
            <div id="targets-pending-card" class="span4 assessment-card">
                <h4 class="pending"><?php echo $translate->_("Not Started"); ?></h4>
                <div class="assessment-card-count pending"><?php echo(isset($pending) && $pending > 0 ? ($pending < 10 ? "0" . $pending : $pending) : "0"); ?></div>
                <p class="assessment-card-description item pending"><?php echo sprintf($translate->_("There are %s form(s) that have not been started."), (isset($pending) && $pending ? $pending : "0")); ?></p>
                <a class="target-status-btn <?php echo(!isset($PREFERENCES["target_status_view"]) ? "active" : (isset($PREFERENCES["target_status_view"]) && $PREFERENCES["target_status_view"] === "pending" ? "active" : "")); ?>" id="targets-pending-btn" data-target-status="pending" href="#"><?php echo $translate->_("Pending Assessments"); ?></a>
            </div>
            <div id="targets-inprogress-card" class="span4 assessment-card">
                <h4 class="inprogress"><?php echo $translate->_("In Progress"); ?></h4>
                <div class="assessment-card-count inprogress"><?php echo(isset($in_progress) && $in_progress > 0 ? ($in_progress < 10 ? "0" . $in_progress : $in_progress) : "0"); ?></div>
                <p class="assessment-card-description item inprogress"><?php echo sprintf($translate->_("There are %s form(s) that are in progress but not complete."), (isset($in_progress) && $in_progress ? $in_progress : "0")); ?></p>
                <a class="target-status-btn <?php echo(isset($PREFERENCES["target_status_view"]) && $PREFERENCES["target_status_view"] === "inprogress" ? "active" : "") ?>" id="targets-inprogress-btn" data-target-status="inprogress" href="#"><?php echo $translate->_("Assessments In Progress"); ?></a>
            </div>
            <div id="targets-complete-card" class="span4 assessment-card">
                <h4 class="complete"><?php echo $translate->_("Completed"); ?></h4>
                <div class="assessment-card-count complete"><?php echo(isset($complete) && $complete > 0 ? ($complete < 10 ? "0" . $complete : $complete) : "0"); ?> </div>
                <p class="assessment-card-description item complete"><?php echo sprintf($translate->_("There are %s form(s) that are complete."), (isset($complete) && $complete ? $complete : "0")); ?></p>
                <a class="target-status-btn <?php echo(isset($PREFERENCES["target_status_view"]) && $PREFERENCES["target_status_view"] === "complete" ? "active" : "") ?>" id="targets-complete-btn" data-target-status="complete" href="#"><?php echo $translate->_("Completed Assessments"); ?></a>
            </div>
        </div>
        <div id="target-search-block" class="clearfix space-below medium">
            <input class="search-icon" type="text" id="target-search-input" placeholder="<?php echo $translate->_("Search Assessors...") ?>"/>
        </div>
        <div id="distribution-load-error" class="alert alert-block alert-danger hide">
            <button type="button" class="close distribution-load-error-msg">&times;</button>
            <p><?php echo $translate->_("Failed to load distribution data. Please try again."); ?></p>
        </div>
        <?php

        echo "<div id=\"targets-pending-container\" class=\"targets-container " . (isset($PREFERENCES["target_status_view"]) && $PREFERENCES["target_status_view"] === "pending" ? "" : "hide") . "\">";
        echo "<h2>" . $translate->_("Pending Assessments") . " </h2>";
        if ($assessment_permissions && isset($details) && isset($details["pending"])) { ?>
            <div class="clearfix space-below medium">
                <a href="#delete-tasks-modal" class="btn btn-danger pull-left" title="<?php echo $translate->_("Delete Task(s) From Distribution"); ?>" data-toggle="modal" data-adistribution-id="<?php echo html_encode($DISTRIBUTION_ID) ?>"><i class="icon-trash icon-white"></i> <?php echo $translate->_("Delete Task(s)") ?>
                </a>
                <div class="assessment-distributions-container btn-group pull-right">
                    <button class="btn btn-primary dropdown-toggle no-printing" data-toggle="dropdown" title="<?php echo $translate->_("Manage Distribution Details"); ?>">
                        <i class="icon-pencil icon-white"></i> <?php echo $translate->_("Manage Distribution") ?>
                        <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="<?php echo ENTRADA_URL . "/admin/assessments/distributions?section=form&adistribution_id=" . html_encode($distribution->getID()); ?>" class="edit-distribution" title="<?php echo $translate->_("Edit Distribution Details"); ?>" data-adistribution-id="<?php echo html_encode($DISTRIBUTION_ID) ?>"><?php echo $translate->_("Edit Distribution") ?></a>
                        </li>
                        <?php if ($details["distribution_target_type"] == "proxy_id"): ?>
                            <li>
                                <a href="#add-task-modal" title="<?php echo $translate->_("Add Task To Distribution"); ?>" data-toggle="modal" data-adistribution-id="<?php echo html_encode($DISTRIBUTION_ID) ?>"><?php echo $translate->_("Add a Task") ?></a>
                            </li>
                        <?php endif; ?>
                        <li>
                            <a href="#reminder-modal" title="<?php echo $translate->_("Send Reminder to Assessor"); ?>" data-toggle="modal" data-adistribution-id="<?php echo html_encode($DISTRIBUTION_ID) ?>"><?php echo $translate->_("Send Reminders") ?></a>
                        </li>
                    </ul>
                </div>
            </div>
            <?php
        }

        if (isset($details) && isset($details["pending"])) {
            ?>
            <div id="targets-pending-table" class="target-table <?php (isset($PREFERENCES["target_view"]) && $PREFERENCES["target_view"] === "list" ? "" : "hide"); ?>">
                <table id="assessment-tasks-table" class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th width="35%"><?php echo $translate->_("Assessor"); ?></th>
                        <th width="35%"><?php echo $translate->_("Target(s)"); ?></th>
                        <th width="20%"><?php echo $translate->_("Delivery Date"); ?></th>
                        <th class="heading-icon"><i class="icon-bell"></th>
                        <th class="heading-icon"><i class="icon-trash"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($details["pending"] as $type) {
                        foreach ($type as $detail) {
                            ?>
                            <tr class="target-pending-block target-block">
                                <?php Views_Assessments_Distribution_Progress::renderTaskRow($detail, $DISTRIBUTION_ID); ?>
                            </tr>
                            <tr class="hide no-search-targets">
                                <td colspan="5"><?php echo $translate->_("No targets found matching your search criteria."); ?></td>
                            </tr>
                            <?php
                        }
                    }
                    if ($pending == 0) { ?>
                        <tr>
                            <td colspan="5">
                                <p id="no-pending-targets" class="no-targets"><?php echo $translate->_("There are currently no assessments pending."); ?></p>
                            </td>
                        </tr>
                    <?php }
                    ?>
                    </tbody>
                </table>
            </div>
            <?php
        } else { ?>
            <div>
                <p id="no-pending-targets" class="no-targets"><?php echo $translate->_("There are currently no assessments pending."); ?></p>
            </div>
        <?php }
        echo "</div>";

        echo "<div id=\"targets-inprogress-container\" class=\"targets-container " . (isset($PREFERENCES["target_status_view"]) && $PREFERENCES["target_status_view"] === "inprogress" ? "" : "hide") . "\">";
        echo "<h2>" . $translate->_("Assessments In Progress") . "</h2>";
        if ($assessment_permissions && isset($details) && isset($details["inprogress"])) { ?>
            <div class="clearfix space-below medium">
                <a href="#delete-tasks-modal" class="btn btn-danger pull-left" title="<?php echo $translate->_("Delete Task(s) From Distribution"); ?>" data-toggle="modal" data-adistribution-id="<?php echo html_encode($DISTRIBUTION_ID) ?>"><i class="icon-trash icon-white"></i> <?php echo $translate->_("Delete Task(s)") ?>
                </a>
                <div class="assessment-distributions-container btn-group pull-right">
                    <button class="btn btn-primary dropdown-toggle no-printing" data-toggle="dropdown" title="<?php echo $translate->_("Manage Distribution Details"); ?>">
                        <i class="icon-pencil icon-white"></i> <?php echo $translate->_("Manage Distribution") ?>
                        <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="<?php echo ENTRADA_URL . "/admin/assessments/distributions?section=form&adistribution_id=" . html_encode($distribution->getID()); ?>" class="edit-distribution" title="<?php echo $translate->_("Edit Distribution Details"); ?>" data-adistribution-id="<?php echo html_encode($DISTRIBUTION_ID) ?>"><?php echo $translate->_("Edit Distribution") ?></a>
                        </li>
                        <?php if ($details["distribution_target_type"] == "proxy_id"): ?>
                            <li>
                                <a href="#add-task-modal" title="<?php echo $translate->_("Add Task To Distribution"); ?>" data-toggle="modal" data-adistribution-id="<?php echo html_encode($DISTRIBUTION_ID) ?>"><?php echo $translate->_("Add a Task") ?></a>
                            </li>
                        <?php endif; ?>
                        <li>
                            <a href="#reminder-modal" title="<?php echo $translate->_("Send Reminder to Assessor"); ?>" data-toggle="modal" data-adistribution-id="<?php echo html_encode($DISTRIBUTION_ID) ?>"><?php echo $translate->_("Send Reminders") ?></a>
                        </li>
                    </ul>
                </div>
            </div>
            <?php
        }

        if (isset($details) && isset($details["inprogress"])) {
            ?>
            <div id="targets-inprogress-table" class="target-table <?php echo(!isset($PREFERENCES["target_view"]) ? "" : (isset($PREFERENCES["target_view"]) && $PREFERENCES["target_view"] === "list" ? "" : "hide")); ?>">
                <table id="assessment-tasks-table" class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th width="35%"><?php echo $translate->_("Assessor"); ?></th>
                        <th width="35%"><?php echo $translate->_("Target(s)"); ?></th>
                        <th width="20%"><?php echo $translate->_("Delivery Date"); ?></th>
                        <th class="heading-icon"><i class="icon-bell"></th>
                        <th class="heading-icon"><i class="icon-trash"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($details["inprogress"] as $type) {
                        foreach ($type as $detail) {
                            ?>
                            <tr class="target-inprogress-block target-block">
                                <?php Views_Assessments_Distribution_Progress::renderTaskRow($detail, $DISTRIBUTION_ID); ?>
                            </tr>
                            <tr class="hide no-search-targets">
                                <td colspan="5"><?php echo $translate->_("No targets found matching your search criteria."); ?></td>
                            </tr>
                            <?php
                        }
                    }
                    if ($in_progress == 0) { ?>
                        <tr>
                            <td colspan="5">
                                <p id="no-inprogress-targets" class="no-targets"><?php echo $translate->_("There are currently no assessments in progress."); ?></p>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
            <?php
        } else { ?>
            <div>
                <p id="no-inprogress-targets" class="no-targets"><?php echo $translate->_("There are currently no assessments in progress."); ?></p>
            </div>
        <?php }
        echo "</div>";

        echo "<div id=\"targets-complete-container\" class=\"targets-container " . (isset($PREFERENCES["target_status_view"]) && $PREFERENCES["target_status_view"] === "complete" ? "" : "hide") . "\">";
        echo "<h2>" . $translate->_("Assessments Completed") . "</h2>";
        echo "<div id=\"assessment-error\" class=\"hide\"></div>";
        if ($assessment_permissions && isset($details) && isset($details["complete"])) { ?>
            <div class="clearfix space-below medium">
                <a href="#delete-tasks-modal" class="btn btn-danger pull-left" title="<?php echo $translate->_("Delete Task(s) From Distribution"); ?>" data-toggle="modal" data-adistribution-id="<?php echo html_encode($DISTRIBUTION_ID) ?>"><i class="icon-trash icon-white"></i> <?php echo $translate->_("Delete Task(s)") ?>
                </a>
                <div class="assessment-distributions-container btn-group pull-right">
                    <button class="btn btn-primary dropdown-toggle no-printing" data-toggle="dropdown" title="<?php echo $translate->_("Manage Distribution Details"); ?>">
                        <i class="icon-pencil icon-white"></i> <?php echo $translate->_("Manage Distribution") ?>
                        <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="<?php echo ENTRADA_URL . "/admin/assessments/distributions?section=form&adistribution_id=" . html_encode($distribution->getID()); ?>" class="edit-distribution" title="<?php echo $translate->_("Edit Distribution Details"); ?>" data-adistribution-id="<?php echo html_encode($DISTRIBUTION_ID) ?>"><?php echo $translate->_("Edit Distribution") ?></a>
                        </li>
                        <?php if ($details["distribution_target_type"] == "proxy_id"): ?>
                            <li>
                                <a href="#add-task-modal" title="<?php echo $translate->_("Add Task To Distribution"); ?>" data-toggle="modal" data-adistribution-id="<?php echo html_encode($DISTRIBUTION_ID) ?>"><?php echo $translate->_("Add a Task") ?></a>
                            </li>
                        <?php endif; ?>
                        <?php if ($disable_pdf_button): ?>
                            <li>
                                <a id="generate-pdf-btn" href="#" title="<?php echo $translate->_("Download PDF"); ?>" data-pdf-unavailable="1" data-adistribution-id="<?php echo html_encode($DISTRIBUTION_ID) ?>"><?php echo $translate->_("Download PDF") ?></a>
                            </li>
                        <?php else: ?>
                            <li>
                                <a id="generate-pdf-btn" href="#generate-pdf-modal" title="<?php echo $translate->_("Download PDF"); ?>" data-pdf-unavailable="0" data-toggle="modal" data-adistribution-id="<?php echo html_encode($DISTRIBUTION_ID) ?>"><?php echo $translate->_("Download PDF") ?></a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <?php
        }

        if (isset($details) && isset($details["complete"])) {
            ?>
            <div id="targets-complete-table" class="target-table <?php echo(!isset($PREFERENCES["target_view"]) ? "" : (isset($PREFERENCES["target_view"]) && $PREFERENCES["target_view"] === "list" ? "" : "hide")); ?>">
                <table id="assessment-tasks-table" class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th width="30%"><?php echo $translate->_("Assessor"); ?></th>
                        <th width="35%"><?php echo $translate->_("Target(s)"); ?></th>
                        <th width="20%"><?php echo $translate->_("Delivery Date"); ?></th>
                        <th class="heading-icon"><i class="icon-download-alt"></th>
                        <th class="heading-icon"><i class="icon-trash"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($details["complete"] as $type) {
                        foreach ($type as $detail) {
                            ?>
                            <tr class="target-complete-block target-block">
                                <?php Views_Assessments_Distribution_Progress::renderTaskRow($detail, $DISTRIBUTION_ID, false); ?>
                            </tr>
                            <tr class="hide no-search-targets">
                                <td colspan="5"><?php echo $translate->_("No targets found matching your search criteria."); ?></td>
                            </tr>
                            <?php
                        }
                    }
                    if ($complete == 0) { ?>
                        <tr>
                            <td colspan="5">
                                <p id="no-complete-targets" class="no-targets"><?php echo $translate->_("There are currently no assessments completed."); ?></p>
                            </td>
                        </tr>
                        <?php
                    } ?>
                    </tbody>
                </table>
            </div>
            <?php
        } else { ?>
            <div>
                <p id="no-completed-targets" class="no-targets"><?php echo $translate->_("There are currently no assessments completed."); ?></p>
            </div>
        <?php }
        echo "</div>";
    }

    public static function renderTaskRow($detail, $DISTRIBUTION_ID = 0, $show_notification_checkbox = true) {
        global $translate;
        ?>
        <td>
            <div>
                <strong>
                    <?php echo(isset($detail["assessor_name"]) ? html_encode($detail["assessor_name"]) : "N/A"); ?>
                </strong>
                <?php
                if ($detail["assessor_email"]) {
                    echo "<a href=\"mailto:" . html_encode($detail["assessor_email"]) . "\" target=\"_top\">" . html_encode($detail["assessor_email"]) . "</a>";
                }
                ?>
            </div>
        </td>
        <td>
            <?php
            if (is_array($detail["targets"])) {
                foreach ($detail["targets"] as $target) {
                    echo "<div class=\"distribution-progress-row\"><strong>";
                    $anonymous = true;
                    if ($target["target_group"] && $target["target_group"] != "faculty") {
                        $anonymous = false;
                    }
                    if ($target["dassessment_id"] && !$anonymous) {
                        $progress = Models_Assessments_Progress::fetchRowByID($target["aprogress_id"]);
                        if ($detail["assessor_type"] == "external" && ($progress && $progress->getProgressValue() != "complete" ? true : (!$progress ? true : false))) {
                            $url = ENTRADA_URL . "/assessment?adistribution_id=" . $DISTRIBUTION_ID . "&target_record_id=" . $target["target_id"] . "&dassessment_id=" . $target["dassessment_id"] . "&assessor_value=" . $detail["assessor_value"] . ($target["aprogress_id"] ? "&aprogress_id=" . $target["aprogress_id"] : "") . "&external_hash=" . $target["external_hash"] . "&from=progress";
                        } else {
                            $url = ENTRADA_URL . "/assessments/assessment?adistribution_id=" . $DISTRIBUTION_ID . "&target_record_id=" . $target["target_id"] . "&dassessment_id=" . $target["dassessment_id"] . "&assessor_id=" . $detail["assessor_value"] . ($target["aprogress_id"] ? "&aprogress_id=" . $target["aprogress_id"] : "") . "&view=view_as";
                        }
                        echo "<a href=\"" . html_encode($url) . "\" alt=\"View Form for " . html_encode($target["target_name"]) . " \" title=\"View Form for " . html_encode($target["target_name"]) . "\">" . html_encode($target["target_name"]) . "</a>";
                    } else {
                        echo html_encode($target["target_name"]);
                    }
                    echo "</strong>";
                    switch ($target["target_type"]) {
                        case "schedule_id":
                            echo "<div class=\"schedule-details\">" . $translate->_("Schedule");
                            /*
                            if ($target["child_schedules"]) {
                                echo ": ";
                                $last_index = sizeof($target["child_schedules"]) - 1;
                                foreach ($target["child_schedules"] as $key => $child_schedule) {
                                    echo html_encode($child_schedule["title"]) . ($key < $last_index ? ", " : "");
                                }
                                echo "<div class=\"schedule-details\">";
                                echo html_encode(date("Y-m-d", $target["child_schedules"][0]["start_date"]) . " to " . date("Y-m-d", $target["child_schedules"][$last_index]["end_date"]));
                                echo "</div>";
                            }
                            */
                            echo "</div>";
                            break;
                        case "course_id":
                            echo "<div class=\"schedule-details\">" . $translate->_("Course") . "</div>";
                            break;
                        case "proxy_id":
                        default:
                            echo "<div class=\"schedule-details\">";
                            if ($target["parent_schedule"]) {
                                echo $target["parent_schedule"]["title"];
                                /*
                                if ($target["child_schedules"]) {
                                    $last_index = sizeof($target["child_schedules"]) - 1;
                                    echo " - ";
                                    foreach ($target["child_schedules"] as $key => $child_schedule) {
                                        echo html_encode($child_schedule["title"]) . ($key < $last_index ? ", " : "");
                                    }
                                    echo "<div class=\"schedule-details\">";
                                    echo html_encode(date("Y-m-d", $target["child_schedules"][0]["start_date"]) . " to " . date("Y-m-d", $target["child_schedules"][$last_index]["end_date"]));
                                    echo "</div>";
                                }
                                */
                            } else {
                                echo "<div class=\"schedule-details\">" . $translate->_("Individual") . "</div>";
                            }
                            echo "</div>";
                            break;
                    }
                    echo "</div>";
                }
            } ?>
        </td>
        <td>
            <strong>
                <?php
                if (is_array($detail["targets"])) {
                    foreach ($detail["targets"] as $target) {
                        echo "<div class=\"distribution-progress-row\">";
                        if (isset($target["delivery_date"]) && $target["delivery_date"]) {
                            echo html_encode(date("Y-m-d", $target["delivery_date"]));
                        } else {
                            echo "N/A";
                        }
                        echo "</div>";
                    }
                } ?>
            </strong>
        </td>
        <?php if ($show_notification_checkbox) { ?>
            <td>
                <?php
                if (is_array($detail["targets"])) {
                    foreach ($detail["targets"] as $target) { ?>
                        <div class="distribution-progress-row">
                            <?php
                            if ($target["delivery_date"] <= time() && $target["dassessment_id"]) {
                                ?>
                                <input class="remind" type="checkbox" name="remind[]"
                                       data-assessor-name="<?php echo html_encode($detail["assessor_name"]) ?>"
                                       data-assessment-id="<?php echo html_encode(($target["dassessment_id"] ? $target["dassessment_id"] : 0)) ?>"
                                       value="<?php echo html_encode($detail["assessor_value"]) ?>"/>
                                <?php
                            } ?>
                        </div>
                        <?php
                    }
                } ?>
            </td>
        <?php } ?>
        <?php if (!$show_notification_checkbox) { ?>
            <td>
            <?php
            if (is_array($detail["targets"])) {
                foreach ($detail["targets"] as $target) {
                    ?>
                    <div class="distribution-progress-row">
                        <input class="generate-pdf" type="checkbox" name="generate-pdf[]"
                               data-target-name="<?php echo html_encode($target["target_name"]) ?>"
                               data-target-id="<?php echo html_encode($target["target_id"]) ?>"
                               data-assessor-name="<?php echo html_encode($detail["assessor_name"]) ?>"
                               data-assessor-value="<?php echo html_encode($detail["assessor_value"]) ?>"
                               data-assessment-id="<?php echo html_encode(($target["dassessment_id"] ? $target["dassessment_id"] : 0)) ?>"
                               data-progress-id="<?php echo html_encode($target["aprogress_id"]) ?>"
                               data-distribution-id="<?php echo html_encode($DISTRIBUTION_ID) ?>"
                               value="<?php echo html_encode($target["delivery_date"]) ?>"/>
                    </div>
                    <?php
                }
            } ?>
            </td>
        <?php } ?>
            <td>
            <?php
            if (is_array($detail["targets"])) {
                foreach ($detail["targets"] as $target) {
                    ?>
                    <div class="distribution-progress-row">
                        <input class="delete" type="checkbox" name="delete[]"
                               data-target-name="<?php echo html_encode($target["target_name"]) ?>"
                               data-target-id="<?php echo html_encode($target["target_id"]) ?>"
                               data-assessor-name="<?php echo html_encode($detail["assessor_name"]) ?>"
                               data-assessor-value="<?php echo html_encode($detail["assessor_value"]) ?>"
                               data-assessor-type="<?php echo html_encode($detail["assessor_type"]) ?>"
                               data-assessment-id="<?php echo html_encode(($target["dassessment_id"] ? $target["dassessment_id"] : 0)) ?>"
                               value="<?php echo html_encode($target["delivery_date"]) ?>"/>
                    </div>
                    <?php
                }
            } ?>
            </td>
        </div>
        <?php
    }

}