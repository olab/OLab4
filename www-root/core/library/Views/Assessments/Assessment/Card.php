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
 * This class renders a single card representing an assessment task.
 * An assessment task can be a distribution-based or triggered
 * assessment, an Approver task, or a Delegation task.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Assessment_Card extends Views_HTML {
    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("task_url", "task_title", "task_scope", "task_type", "task_id"));
    }

    /**
     * Render the meta related data.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $task_url               = $options["task_url"];
        $task_title             = $options["task_title"];
        $task_scope             = $options["task_scope"]; // target or assessor
        $task_type              = $options["task_type"];
        $task_id                = $options["task_id"];
        $form_type_category     = array_key_exists("form_type_category", $options) ? $options["form_type_category"] : NULL;
        $form_type_title        = array_key_exists("form_type_title", $options) ? $options["form_type_title"] : "";
        $task_details           = array_key_exists("task_details", $options) ? $options["task_details"] : null;
        $dassessment_id         = array_key_exists("dassessment_id", $options) ? $options["dassessment_id"] : null;
        $aprogress_id           = array_key_exists("aprogress_id", $options) ? $options["aprogress_id"] : null;
        $adistribution_id       = array_key_exists("adistribution_id", $options) ? $options["adistribution_id"] : null;
        $delegated_by           = array_key_exists("delegator_name", $options) ? $options["delegator_name"] : null;

        // Badges that appear under the task title
        $task_badge             = array_key_exists("task_badge_text", $options) ? $options["task_badge_text"] : null;
        $schedule_badge         = array_key_exists("schedule_badge_text", $options) ? $options["schedule_badge_text"] : null;
        $event_badge            = array_key_exists("event_badge_text", $options) ? $options["event_badge_text"] : null;

        // Pre-formatted event time strings
        $event_timeframe_start  = array_key_exists("event_timeframe_start", $options) ? $options["event_timeframe_start"] : null;
        $event_timeframe_end    = array_key_exists("event_timeframe_end", $options) ? $options["event_timeframe_end"] : null;

        // Unix time values
        $delivery_date          = array_key_exists("delivery_date", $options) ? $options["delivery_date"] : null;
        $task_completion_date   = array_key_exists("task_completion_date", $options) ? $options["task_completion_date"] : null;
        $task_start_date        = array_key_exists("task_start_date", $options) ? $options["task_start_date"] : null;
        $task_end_date          = array_key_exists("task_end_date", $options) ? $options["task_end_date"] : null;
        $rotation_start_date    = array_key_exists("rotation_start_date", $options) ? $options["rotation_start_date"] : null;
        $rotation_end_date      = array_key_exists("rotation_end_date", $options) ? $options["rotation_end_date"] : null;

        // Target values
        $target_list            = array_key_exists("task_targets", $options) ? $options["task_targets"] : "";
        $total_targets          = array_key_exists("total_targets", $options) ? $options["total_targets"] : 0;
        $targets_pending        = array_key_exists("targets_pending", $options) ? $options["targets_pending"] : 0;
        $targets_in_progress    = array_key_exists("targets_pending", $options) ? $options["targets_in_progress"] : 0;
        $targets_completed      = array_key_exists("targets_completed", $options) ? $options["targets_completed"] : 0;

        // Assessor values
        $assessor_name          = array_key_exists("assessor_name", $options) ? $options["assessor_name"] : "";
        $assessor_type          = array_key_exists("assessor_type", $options) ? $options["assessor_type"] : "";
        $assessor_scope         = array_key_exists("assessor_scope", $options) ? $options["assessor_scope"] : "";
        $assessor_value         = array_key_exists("assessor_value", $options) ? $options["assessor_value"] : null;
        $assessor_group         = array_key_exists("assessor_group", $options) ? $options["assessor_group"] : null;
        $assessor_role          = array_key_exists("assessor_role", $options) ? $options["assessor_role"] : null;

        // Single target values (only optionally used when there's one target)
        $single_target_name     = array_key_exists("single_target_name", $options) ? $options["single_target_name"] : "";
        $single_target_type     = array_key_exists("single_target_type", $options) ? $options["single_target_type"] : "";
        $single_target_scope    = array_key_exists("single_target_scope", $options) ? $options["single_target_scope"] : "";
        $single_target_value    = array_key_exists("single_target_value", $options) ? $options["single_target_value"] : null;
        $single_target_group    = array_key_exists("single_target_group", $options) ? $options["single_target_group"] : null;
        $single_target_role     = array_key_exists("single_target_role", $options) ? $options["single_target_role"] : null;
        
        // State flags
        $has_progress               = array_key_exists("has_progress", $options) ? $options["has_progress"] : true;
        $show_progress_section      = array_key_exists("show_progress_section", $options) ? $options["show_progress_section"] : true;
        $show_assessor_details      = array_key_exists("show_assessor_details", $options) ? $options["show_assessor_details"] : false;
        $show_single_target_details = array_key_exists("show_single_target_details", $options) ? $options["show_single_target_details"] : false;
        $show_delegation_badge      = array_key_exists("show_delegation_badge", $options) ? $options["show_delegation_badge"] : false;
        $show_reviewer_badge        = array_key_exists("show_reviewer_badge", $options) ? $options["show_reviewer_badge"] : false;
        $show_remove_button         = array_key_exists("show_remove_button", $options) ? $options["show_remove_button"] : true;
        $show_view_button           = array_key_exists("show_view_button", $options) ? $options["show_view_button"] : true;
        $show_download_pdf          = array_key_exists("show_download_pdf", $options) ? $options["show_download_pdf"] : true;
        $show_send_reminders        = array_key_exists("show_send_reminders", $options) ? $options["show_send_reminders"] : false;

        // URL post-fix for multiple targets
        $pending_postfix = "";
        $inprogress_postfix = "";
        $completed_postfix = "";
        if ($task_scope == "assessor") {
            if ($total_targets > 1) {
                if (strstr($task_url, "&section=targets") === false) {
                    $pending_postfix = "&section=targets&target_status_view=pending";
                    $inprogress_postfix = "&section=targets&target_status_view=inprogress";
                    $completed_postfix = "&section=targets&target_status_view=complete";
                } else {
                    $pending_postfix = "&target_status_view=pending";
                    $inprogress_postfix = "&target_status_view=inprogress";
                    $completed_postfix = "&target_status_view=complete";
                }
            }
        }

        // Don't render the assessor section if there's no assessor
        if (!$assessor_name && !$assessor_value) {
            $show_assessor_details = false;
        }

        // Only render the date section if there's something to render
        $show_related_date_range = false;
        if ($task_start_date || $task_end_date || $event_timeframe_start || $event_timeframe_end || $rotation_start_date || $rotation_end_date) {
            $show_related_date_range = true;
        }
        ?>
        <div class="assessment-task">
            <div class="assessment-task-wrapper">
                <div class="distribution">
                    <div>
                        <span class="assessment-task-title"><?php echo html_encode($task_title); ?></span>
                    </div>

                    <?php if ($task_badge): ?>
                        <div class="label assessment-task-schedule-info-badge"><?php echo html_encode($task_badge); ?></div>
                    <?php endif; ?>

                    <?php if ($schedule_badge): ?>
                        <div class="label assessment-task-schedule-info-badge"><?php echo html_encode($schedule_badge); ?></div>
                    <?php endif; ?>

                    <?php if ($event_badge): ?>
                        <div class="label assessment-task-event-info-badge"><?php echo html_encode($event_badge); ?></div>
                    <?php endif; ?>

                    <?php if ($show_delegation_badge): ?>
                        <div class="label assessment-task-delegation-badge"><?php echo html_encode($translate->_("Delegation Task")); ?></div>
                    <?php endif; ?>

                    <?php if ($show_reviewer_badge): ?>
                        <div class="label assessment-task-release-schedule-info-badge"><?php echo html_encode($translate->_("Reviewer Task")); ?></div>
                    <?php endif; ?>

                    <?php if ($show_related_date_range): ?>
                        <div class="assessment-task-date-range">
                            <?php if ($event_timeframe_start || $event_timeframe_end) : ?>
                                <em><?php echo Entrada_Assessments_Tasks::generateDateRangeString($event_timeframe_start, $event_timeframe_end); ?></em>
                            <?php elseif ($rotation_start_date || $rotation_end_date) : ?>
                                <em><?php echo Entrada_Assessments_Tasks::generateDateRangeString($rotation_start_date, $rotation_end_date, "M j, Y"); ?></em>
                            <?php elseif ($task_start_date || $task_end_date) : ?>
                                <em><?php echo Entrada_Assessments_Tasks::generateDateRangeString($task_start_date, $task_end_date, "M j, Y"); ?></em>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($delivery_date): ?>
                        <?php if ($delivery_date > time()): ?>
                            <div class="assessment-task-date">
                                <?php echo sprintf($translate->_("Will be delivered on <strong>%s</strong>"), Entrada_Assessments_Tasks::generateDateRangeString($delivery_date, null,"M j, Y")); ?>
                            </div>
                        <?php else: ?>
                            <div class="assessment-task-date">
                                <?php echo sprintf($translate->_("Delivered on <strong>%s</strong>"), Entrada_Assessments_Tasks::generateDateRangeString($delivery_date, null,"M j, Y")); ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($task_completion_date): ?>
                        <div class="assessment-task-date">
                            <?php echo sprintf($translate->_("Completed on <strong>%s</strong>"), Entrada_Assessments_Tasks::generateDateRangeString($task_completion_date, null,"M j, Y")); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($delegated_by): ?>
                        <div class="assessment-task-date">
                            <?php echo sprintf($translate->_("Delegated by <strong>%s</strong>"), $delegated_by);?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if ($show_progress_section): ?>
                    <div class="assessment-progress">
                        <?php if ($has_progress): ?>
                            <span class="progress-title"><?php echo $translate->_("Progress"); ?></span>
                            <?php if ($targets_pending > 0): ?>
                                <span class="pending">
                                    <a class="progress-circle tooltip-tag assessment-card-target-circle"
                                       href="<?php echo $task_url . $pending_postfix; ?>"
                                       data-toggle="tooltip"
                                       data-placement="bottom"
                                       data-progress-type="pending"
                                       data-dassessment-id="<?php echo $dassessment_id ?>"
                                       data-targets-loaded="0"
                                       title="<?php echo $translate->_("Loading Targets..."); ?>">
                                        <div><?php echo $targets_pending; ?></div>
                                    </a>
                                </span>
                            <?php endif; ?>
                            <?php if ($targets_in_progress > 0): ?>
                                <span class="inprogress">
                                    <a class="progress-circle tooltip-tag assessment-card-target-circle"
                                       href="<?php echo $task_url . $inprogress_postfix; ?>"
                                       data-toggle="tooltip"
                                       data-placement="bottom"
                                       data-progress-type="inprogress"
                                       data-dassessment-id="<?php echo $dassessment_id ?>"
                                       data-targets-loaded="0"
                                       title="<?php echo $translate->_("Loading Targets..."); ?>">
                                        <div><?php echo $targets_in_progress; ?></div>
                                    </a>
                                </span>
                            <?php endif; ?>
                            <?php if ($targets_completed > 0): ?>
                                <span class="complete">
                                    <a class="progress-circle tooltip-tag assessment-card-target-circle"
                                       href="<?php echo $task_url . $completed_postfix; ?>"
                                       data-toggle="tooltip"
                                       data-placement="bottom"
                                       data-progress-type="complete"
                                       data-dassessment-id="<?php echo $dassessment_id ?>"
                                       data-targets-loaded="0"
                                       title="<?php echo $translate->_("Loading Targets..."); ?>">
                                        <div><?php echo $targets_completed ?></div>
                                    </a>
                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="progress-title">
                                <?php echo $translate->_("Progress"); ?>
                                <strong><?php echo $translate->_("N/A"); ?></strong>
                            </span>
                        <?php endif; ?>
                        <div class="clearfix"></div>
                    </div>
                <?php endif; ?>
                <?php if ($task_details): ?>
                    <div class="details">
                        <?php echo $task_details; ?>
                    </div>
                <?php endif; ?>
                <?php if ($show_single_target_details): // single target's details ?>
                    <div class="assessor">
                        <div><?php echo sprintf($translate->_("Target: <strong>%s</strong>"), html_encode($single_target_name)); ?></div>
                        <?php if ($single_target_group && $single_target_role) : ?>
                            <div class="label assessment-task-meta">
                                <?php if ($single_target_group && $single_target_role) {
                                    echo html_encode($single_target_group);
                                    echo "&nbsp;&bull;&nbsp;";
                                    echo html_encode($single_target_role);
                                } else if ($single_target_role) {
                                    echo html_encode($single_target_role);
                                } else if ($single_target_group) {
                                    echo html_encode($single_target_group);
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if ($show_assessor_details): ?>
                    <div class="assessor">
                        <div><?php echo sprintf($translate->_("Assessor: <strong>%s</strong>"), html_encode($assessor_name)); ?></div>
                        <?php if ($assessor_group || $assessor_role): ?>
                            <div class="label assessment-task-meta">
                                <?php if ($assessor_group && $assessor_role) {
                                    echo html_encode($assessor_group);
                                    echo "&nbsp;&bull;&nbsp;";
                                    echo html_encode($assessor_role);
                                } else if ($assessor_role) {
                                    echo html_encode($assessor_role);
                                } else if ($assessor_group) {
                                    echo html_encode($assessor_group);
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if ($show_send_reminders): ?>
                <div class="assessment-task-select">
                    <div class="fa-wrapper">
                        <span class="fa fa-bell"></span>
                    </div>
                    <label class="checkbox">
                        <input class="remind"
                               type="checkbox"
                               name="remind[]"
                               data-task-id="<?php echo $task_id; ?>"
                               data-task-type="<?php echo $task_type;?>"
                               data-assessor-name="<?php echo $assessor_name ?>"
                               data-assessor-id="<?php echo $assessor_value ?>"
                               data-dassessment-id="<?php echo $dassessment_id ?>"
                               data-aprogress-id="<?php echo $aprogress_id ?>"
                               data-adistribution-id="<?php echo $adistribution_id; ?>"
                               value="1"
                        />
                        <?php echo $translate->_("Select and click the <strong>Send Reminders</strong> button above to send a reminder for all selected tasks."); ?>
                    </label>
                </div>
                <?php endif; ?>
                <?php if ($show_download_pdf): ?>
                    <div class="assessment-task-select">
                        <div class="fa-wrapper">
                            <span class="fa fa-download"></span>
                        </div>
                        <label class="checkbox">
                            <input class="generate-pdf"
                                   type="checkbox"
                                   name="generate-pdf[]"
                                   data-dassessment-id="<?php echo $dassessment_id; ?>"
                                   data-aprogress-id="<?php echo $aprogress_id; ?>"
                                   value="1"
                            />
                            <?php echo $translate->_("Select and click on the <strong>Download PDF(s)</strong> button above to download a PDF of all selected tasks."); ?>
                        </label>
                    </div>
                <?php endif; ?>
            </div>
            <div class="assessment-task-link btn-group">
                <?php if ($show_view_button): ?>
                    <a href="<?php echo $task_url; ?>" class="<?php echo (!$show_remove_button) ? "full-width" : ""; ?>"><?php echo $translate->_("View Task"); ?>&nbsp;&rtrif;</a>
                <?php endif; ?>
                <?php if ($show_remove_button): ?>
                    <a class="remove <?php echo (!$show_view_button) ? "full-width" : ""; ?>"
                       data-task-id="<?php echo $task_id; ?>"
                       data-task-type="<?php echo $task_type;?>"
                       data-target-list="<?php echo $target_list; ?>"
                       data-adistribution-id="<?php echo $adistribution_id; ?>"
                       data-dassessment-id="<?php echo $dassessment_id; ?>"
                       href="#">
                        <?php echo $translate->_("Remove Task"); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}