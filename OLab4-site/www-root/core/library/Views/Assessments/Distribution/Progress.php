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

    public static function renderProgressSection($SUBMODULE_TEXT, $PREFERENCES, $DISTRIBUTION_ID, $distribution, $details, $pending, $in_progress, $complete, $assessment_permissions, $disable_pdf_button = false, $form_action = "", $target_list = array(), $start_date = 0, $end_date = 0) {
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
            <input class="search-icon" type="text" id="target-progress-search-input" placeholder="<?php echo $translate->_("Search Targets...") ?>"/>
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
                        <li>
                            <a href="#add-task-modal" title="<?php echo $translate->_("Add Task To Distribution"); ?>" data-toggle="modal" data-adistribution-id="<?php echo html_encode($DISTRIBUTION_ID) ?>"><?php echo $translate->_("Add a Task") ?></a>
                        </li>
                        <li>
                            <a href="#reminder-modal" title="<?php echo $translate->_("Send Reminder to Assessor"); ?>" data-toggle="modal" data-adistribution-id="<?php echo html_encode($DISTRIBUTION_ID) ?>"><?php echo $translate->_("Send Reminders") ?></a>
                        </li>
                    </ul>
                </div>
            </div>
            <?php
        }

        if (isset($details) && isset($details["pending"])) { ?>
            <div id="targets-pending-table" class="target-table <?php echo(!isset($PREFERENCES["target_view"]) ? "" : (isset($PREFERENCES["target_view"]) && $PREFERENCES["target_view"] === "list" ? "" : "hide")); ?>">
                <?php
                foreach ($details["pending"] as $type) {
                    foreach ($type as $detail) {
                        Views_Assessments_Distribution_Progress::renderAssessorTable($detail, "pending", $DISTRIBUTION_ID);
                    }
                }
                if ($pending == 0) { ?>
                    <p id="no-pending-targets" class="no-targets"><?php echo $translate->_("There are currently no assessments pending."); ?></p>
                <?php } ?>
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
                        <li>
                            <a href="#add-task-modal" title="<?php echo $translate->_("Add Task To Distribution"); ?>" data-toggle="modal" data-adistribution-id="<?php echo html_encode($DISTRIBUTION_ID) ?>"><?php echo $translate->_("Add a Task") ?></a>
                        </li>
                        <?php if ($disable_pdf_button): ?>
                            <li>
                                <a id="generate-pdf-btn" class="generate-pdf-btn" href="#" title="<?php echo $translate->_("Download PDF"); ?>" data-pdf-unavailable="1" data-adistribution-id="<?php echo html_encode($DISTRIBUTION_ID) ?>"><?php echo $translate->_("Download PDF") ?></a>
                            </li>
                        <?php else: ?>
                            <li>
                                <a id="generate-pdf-btn" class="generate-pdf-btn" href="#generate-pdf-modal" title="<?php echo $translate->_("Download PDF"); ?>" data-pdf-unavailable="0" data-adistribution-id="<?php echo html_encode($DISTRIBUTION_ID) ?>"><?php echo $translate->_("Download PDF") ?></a>
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
                <?php
                foreach ($details["inprogress"] as $type) {
                    foreach ($type as $detail) {
                        Views_Assessments_Distribution_Progress::renderAssessorTable($detail, "inprogress", $DISTRIBUTION_ID);
                    }
                }
                if ($in_progress == 0) { ?>
                    <p id="no-inprogress-targets" class="no-targets"><?php echo $translate->_("There are currently no assessments in progress."); ?></p>
                    <?php
                } ?>
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
                <form <?php echo ($form_action ? "action=\"" . html_encode($form_action) . "\"" : "") ?> method="post" target="_blank">
                    <input type="hidden" name="parent_id_container" id="targets-complete-container" value="targets-complete-container" />
                    <?php if ($distribution) : ?>
                        <div id="report-msgs"></div>
                        <a id="csv-report" href="<?php echo ENTRADA_URL . "/admin/assessments/distributions?section=api-distributions&method=get-distribution-csv-report&form_id=" . html_encode($distribution->getFormID()) . "&adistribution_id=" . $DISTRIBUTION_ID ?>" class="btn btn-default space-left"><i class="fa fa-download" aria-hidden="true"></i> <?php echo $SUBMODULE_TEXT["weighted_csv_report"]; ?></a>
                        <?php if ($distribution->getAssessmentType() == "evaluation" && $form_action && $form_action != "") : ?>
                            <input class="btn btn-default space-left medium" type="submit" value="<?php echo $translate->_("Aggregated Report") ?>" />
                            <?php if ($target_list) : ?>
                                <?php foreach ($target_list as $target) : ?>
                                    <input type="hidden" name="targets[]" value="<?php echo $target["proxy_id"] ?>" />
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <input type="hidden" name="form_id" value="<?php echo $distribution->getFormID(); ?>" />
                            <input type="hidden" name="course_id" value="<?php echo $distribution->getCourseID(); ?>" />
                            <input type="hidden" name="adistribution_id" value="<?php echo $distribution->getID(); ?>" />
                            <input type="hidden" name="start_date" value="<?php echo html_encode($start_date) ?>" />
                            <input type="hidden" name="end_date" value="<?php echo html_encode($end_date) ?>" />
                        <?php endif; ?>
                    <?php endif; ?>
                    <a href="#delete-tasks-modal" class="btn btn-danger pull-left" title="<?php echo $translate->_("Delete Task(s) From Distribution"); ?>" data-toggle="modal" data-adistribution-id="<?php echo html_encode($DISTRIBUTION_ID) ?>"><i class="icon-trash icon-white"></i> <?php echo $translate->_("Delete Task(s)") ?></a>

                    <div class="assessment-distributions-container btn-group pull-right">
                        <button class="btn btn-primary dropdown-toggle no-printing" data-toggle="dropdown" title="<?php echo $translate->_("Manage Distribution Details"); ?>">
                            <i class="icon-pencil icon-white"></i> <?php echo $translate->_("Manage Distribution") ?>
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="<?php echo ENTRADA_URL . "/admin/assessments/distributions?section=form&adistribution_id=" . html_encode($distribution->getID()); ?>" class="edit-distribution" title="<?php echo $translate->_("Edit Distribution Details"); ?>" data-adistribution-id="<?php echo html_encode($DISTRIBUTION_ID) ?>"><?php echo $translate->_("Edit Distribution") ?></a>
                            </li>
                            <li>
                                <a href="#add-task-modal" title="<?php echo $translate->_("Add Task To Distribution"); ?>" data-toggle="modal" data-adistribution-id="<?php echo html_encode($DISTRIBUTION_ID) ?>"><?php echo $translate->_("Add a Task") ?></a>
                            </li>
                            <?php if ($disable_pdf_button): ?>
                                <li>
                                    <a id="generate-pdf-btn" class="generate-pdf-btn" href="#" title="<?php echo $translate->_("Download PDF"); ?>" data-pdf-unavailable="1" data-adistribution-id="<?php echo html_encode($DISTRIBUTION_ID) ?>"><?php echo $translate->_("Download PDF") ?></a>
                                </li>
                            <?php else: ?>
                                <li>
                                    <a id="generate-pdf-btn" class="generate-pdf-btn" href="#generate-pdf-modal" title="<?php echo $translate->_("Download PDF"); ?>" data-pdf-unavailable="0" data-adistribution-id="<?php echo html_encode($DISTRIBUTION_ID) ?>"><?php echo $translate->_("Download PDF") ?></a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </form>
            </div>
            <?php
        }

        if (isset($details) && isset($details["complete"])) {
            ?>
            <div id="targets-complete-table" class="target-table <?php echo(!isset($PREFERENCES["target_view"]) ? "" : (isset($PREFERENCES["target_view"]) && $PREFERENCES["target_view"] === "list" ? "" : "hide")); ?>">
                <?php
                foreach ($details["complete"] as $type) {
                    foreach ($type as $detail) {
                        Views_Assessments_Distribution_Progress::renderAssessorTable($detail, "complete", $DISTRIBUTION_ID, false);
                    }
                }
                if ($complete == 0) { ?>
                    <p id="no-complete-targets" class="no-targets"><?php echo $translate->_("There are currently no assessments completed."); ?></p>
                    <?php
                } ?>
            </div>
            <?php
        } else { ?>
            <div>
                <p id="no-completed-targets" class="no-targets"><?php echo $translate->_("There are currently no assessments completed."); ?></p>
            </div>
        <?php }
        echo "</div>";
    }

    public static function renderAssessorTable($detail, $progress_value, $DISTRIBUTION_ID = 0, $show_notification_checkbox = true) {
        global $translate;
        ?>
        <table class="table table-striped table-bordered assessment-distribution-progress-table">
            <thead>
            <tr class="table-header-assessor-row">
                <th colspan="5">
                    <div>
                        <?php echo $translate->_("Assessor: "); ?>
                        <strong>
                            <?php echo(isset($detail["assessor"]["name"]) ? html_encode($detail["assessor"]["name"]) : "N/A"); ?>
                        </strong>
                        <?php
                        if ($detail["assessor"]["email"]) {
                            echo "<a href=\"mailto:" . html_encode($detail["assessor"]["email"]) . "\" target=\"_top\">" . html_encode($detail["assessor"]["email"]) . "</a>";
                        }
                        ?>
                    </div>
                </th>
            </tr>
            <tr>
                <th width="44%"><?php echo $translate->_("Target(s)"); ?></th>
                <th width="41%"><?php echo $translate->_("Delivery Date"); ?></th>
                <th width="5%" class="heading-icon"><i class="icon-bell"></th>
                <th width="5%" class="heading-icon"><i class="icon-download-alt"></th>
                <th width="5%" class="heading-icon"><i class="icon-trash"></th>
            </tr>
            </thead>
            <tbody>
            <?php
            if (is_array($detail["targets"])) {
                foreach ($detail["targets"] as $target) {
                    $progress = (!empty($target["progress"][$progress_value]) ? end($target["progress"][$progress_value]) : false);
                    ?>
                    <tr class="target-block<?php echo(!$target["should_exist"] && $progress_value != "complete" ? " deleted" : ""); ?>">
                        <td class="target-block-target-details">
                            <strong>
                                <?php
                                if (!$target["should_exist"] && $progress_value != "complete") {
                                    echo html_encode($target["target_name"]);
                                    echo "<div class=\"task-deleted\"><i class=\"icon-trash\"></i>" . $translate->_(" (Deleted)") . "</div>";
                                } else {
                                    if ($target["dassessment_id"]) {
                                        $assessment_api = new Entrada_Assessments_Assessment(array("limit_dataset" => array("targets"), "fetch_deleted_targets" => true));
                                        $url = $assessment_api->getAssessmentURL($target["target_value"], $target["target_type"], false, $target["dassessment_id"], $progress ? $progress->getID() : null);
                                        echo "<a href=\"" . html_encode($url) . "\" alt=\"View Form for " . html_encode($target["target_name"]) . " \" title=\"View Form for " . html_encode($target["target_name"]) . "\">" . html_encode($target["target_name"]) . "</a>";
                                    } else {
                                        echo html_encode($target["target_name"]);
                                    }
                                }
                                ?>
                            </strong>
                            <div class="schedule-details">
                            <?php
                            switch ($target["target_type"]) {
                                case "schedule_id":
                                    echo $translate->_("Schedule");
                                    break;
                                case "course_id":
                                    echo $translate->_("Course");
                                    break;
                                case "cgroup_id":
                                    echo $translate->_("Course Group");
                                    break;
                            } ?>
                            </div>
                        </td>
                        <td>
                            <?php
                            echo "<div><strong>";
                            if (isset($target["meta"]["delivery_date"]) && $target["meta"]["delivery_date"]) {
                                echo html_encode(date("Y-m-d", $target["meta"]["delivery_date"]));
                            } else {
                                echo "N/A";
                            }
                            echo "</strong></div>";
                            echo "<div class=\"schedule-details\">";
                            switch ($target["target_type"]) {
                                case "proxy_id":
                                default:
                                    // This progress page is for date range or schedule based distributions. If an associated record is set for a schedule based assessment, it will be the ID of the schedule record.
                                    if ($target["meta"]["associated_record_type"] == "schedule_id" && $target["meta"]["associated_record_id"]) {
                                        $schedule = Models_Schedule::fetchRowByID($target["meta"]["associated_record_id"]);
                                        echo Entrada_Assessments_Base::getConcatenatedBlockString(($target["dassessment_id"] ? $target["dassessment_id"] : false), ($schedule ? $schedule : false), $target["meta"]["start_date"], $target["meta"]["end_date"], $target["meta"]["organisation_id"]);
                                    }
                                    break;
                            }
                            echo "</div>";
                            ?>
                        </td>
                        <td>
                            <?php if ($show_notification_checkbox && $target["should_exist"] && $target["meta"]["delivery_date"] <= time() && $target["dassessment_id"]) { ?>
                                <div>
                                    <input class="remind" type="checkbox" name="remind[]"
                                           data-assessor-name="<?php echo html_encode($detail["assessor"]["name"]) ?>"
                                           data-dassessment-id="<?php echo html_encode($target["dassessment_id"]) ?>"
                                           value="<?php echo html_encode($detail["assessor"]["assessor_value"]) ?>"/>
                                </div>
                            <?php } ?>
                        </td>
                        <td>
                            <?php if ($progress && ($target["should_exist"] || $progress_value == "complete")) { ?>
                                <div>
                                    <input class="generate-pdf" type="checkbox" name="generate-pdf[]"
                                           data-target-name="<?php echo html_encode($target["target_name"]) ?>"
                                           data-target-id="<?php echo html_encode($target["target_value"]) ?>"
                                           data-assessor-name="<?php echo html_encode($detail["assessor"]["name"]) ?>"
                                           data-assessor-value="<?php echo html_encode($detail["assessor"]["assessor_value"]) ?>"
                                           data-dassessment-id="<?php echo html_encode(($target["dassessment_id"] ? $target["dassessment_id"] : 0)) ?>"
                                           data-aprogress-id="<?php echo html_encode($progress ? $progress->getID() : null) ?>"
                                           data-distribution-id="<?php echo html_encode($DISTRIBUTION_ID) ?>"
                                           value="<?php echo html_encode($target["meta"]["delivery_date"]) ?>"/>
                                </div>
                            <?php } ?>
                        </td>
                        <td>
                            <?php if ($target["should_exist"] && $progress_value != "complete") { ?>
                                <div>
                                    <?php
                                    if (!empty($target["current_record"])) { ?>
                                        <input class="delete" type="checkbox" name="delete[]"
                                               data-target-name="<?php echo html_encode($target["target_name"]); ?>"
                                               data-assessor-name="<?php echo html_encode($detail["assessor"]["name"]); ?>"
                                               data-atarget-id="<?php echo html_encode($target["current_record"][0]->getID()); ?>"
                                               value="<?php echo html_encode($target["meta"]["delivery_date"]); ?>"/>
                                    <?php } else { ?>
                                        <input class="delete" type="checkbox" name="delete[]"
                                               data-target-name="<?php echo html_encode($target["target_name"]); ?>"
                                               data-target-id="<?php echo html_encode($target["target_value"]); ?>"
                                               data-target-type="<?php echo html_encode($target["target_type"]); ?>"
                                               data-assessor-name="<?php echo html_encode($detail["assessor"]["name"]); ?>"
                                               data-assessor-value="<?php echo html_encode($detail["assessor"]["assessor_value"]); ?>"
                                               data-assessor-type="<?php echo html_encode($detail["assessor"]["assessor_type"]); ?>"
                                               data-form-id="<?php echo html_encode($target["meta"]["form_id"]); ?>"
                                               data-organisation-id="<?php echo html_encode($target["meta"]["organisation_id"]); ?>"
                                               data-delivery-date="<?php echo html_encode($target["meta"]["delivery_date"]); ?>"
                                               data-feedback-required="<?php echo html_encode($target["meta"]["feedback_required"]); ?>"
                                               data-min-submittable="<?php echo html_encode($target["meta"]["min_submittable"]); ?>"
                                               data-max-submittable="<?php echo html_encode($target["meta"]["max_submittable"]); ?>"
                                               data-start-date="<?php echo html_encode($target["meta"]["start_date"]); ?>"
                                               data-end-date="<?php echo html_encode($target["meta"]["end_date"]); ?>"
                                               data-rotation-start-date="<?php echo html_encode($target["meta"]["rotation_start_date"]); ?>"
                                               data-rotation-end-date="<?php echo html_encode($target["meta"]["rotation_end_date"]); ?>"
                                               data-associated-record-type="<?php echo html_encode($target["meta"]["associated_record_type"]); ?>"
                                               data-associated-record-id="<?php echo html_encode($target["meta"]["associated_record_id"]); ?>"
                                               data-additional-task="<?php echo html_encode(isset($target["meta"]["additional"]) ? 1 : 0); ?>"
                                               data-task-type="<?php echo html_encode($target["task_type"]); ?>"
                                               value="<?php echo html_encode($target["meta"]["delivery_date"]); ?>"/>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </td>
                    </tr>
                    <?php
                }
            }
            ?>
            <tr class="hide no-search-targets">
                <td colspan="5"><?php echo $translate->_("No targets found matching your search criteria."); ?></td>
            </tr>
            </tbody>
        </table>
        <?php
    }

    public static function renderHead($active_tab = "") {
        global $HEAD;
        $HEAD[] = "<script type=\"text/javascript\">var active_tab = '". $active_tab ."';</script>";
    }
}