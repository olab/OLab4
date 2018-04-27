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
 * This view is used in sidebars and headers, to display assessment
 * related delivery information.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Templates_AssessmentCard extends Views_HTML {

    /**
     * Render the template.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        global $translate;
        Entrada_Utilities_jQueryHelper::addScriptsToHead();
        ?>
        <script type="text/html" id="assessment-card">
            <div class="assessment-task">
                <div class="assessment-task-wrapper">
                    <div class="distribution">
                        <div>
                            <span class="assessment-task-title" data-content="task_title"></span>
                        </div>

                        <!-- Badge labels -->
                        <div class="hide tpl-task-badge-text label assessment-task-schedule-info-badge" data-content="task_badge_text"></div>
                        <div class="hide tpl-schedule-badge-text label assessment-task-schedule-info-badge" data-content="schedule_badge_text"></div>
                        <div class="hide tpl-event-badge-text label assessment-task-event-info-badge" data-content="event_badge_text"></div>
                        <div class="hide tpl-delegation-badge-text label assessment-task-delegation-badge"><?php echo html_encode($translate->_("Delegation Task")); ?></div>
                        <div class="hide tpl-reviewer-badge-text label assessment-task-release-schedule-info-badge"><?php echo html_encode($translate->_("Reviewer Task")); ?></div>

                        <!-- Event time frame-->
                        <div class="hide tpl-event-timeframe assessment-task-date-range">
                            <em>
                                <?php
                                echo sprintf(
                                    $translate->_("%s to %s"),
                                    "<span data-content='event_timeframe_start'></span>",
                                    "<span data-content='event_timeframe_end'></span>");
                                ?>
                            </em>
                        </div>

                        <!-- Rotation schedule dates -->
                        <div class="hide tpl-rotation-dates assessment-task-date-range">
                            <em>
                                <?php
                                echo sprintf(
                                    $translate->_("%s to %s"),
                                    "<span data-content='rotation_start_date'></span>",
                                    "<span data-content='rotation_end_date'></span>");
                                ?>
                            </em>
                        </div>

                        <!-- Standard date range -->
                        <div class="hide tpl-date-range assessment-task-date-range">
                            <em>
                                <?php
                                echo sprintf(
                                    $translate->_("%s to %s"),
                                    "<span data-content='task_start_date'></span>",
                                    "<span data-content='task_end_date'></span>");
                                ?>
                            </em>
                        </div>

                        <div class="hide tpl-future-delivery-date assessment-task-date">
                            <?php echo sprintf($translate->_("Will be delivered on <strong>%s</strong>"), "<span data-content='delivery_date'></span>"); ?>
                        </div>

                        <div class="hide tpl-delivery-date assessment-task-date">
                            <?php echo sprintf($translate->_("Delivered on <strong>%s</strong>"), "<span data-content='delivery_date'></span>"); ?>
                        </div>

                        <div class="hide tpl-completion-date assessment-task-date">
                            <?php echo sprintf($translate->_("Completed on <strong>%s</strong>"), "<span data-content='task_completion_date'></span>"); ?>
                        </div>

                        <div class="hide tpl-delegator-name assessment-task-date">
                            <?php echo sprintf($translate->_("Delegated by <strong>%s</strong>"), "<span data-content='delegator_name'></span>"); ?>
                        </div>
                    </div>
                    <div class="hide tpl-progress-section assessment-progress">
                        <span class="progress-title"><?php echo $translate->_("Progress"); ?></span>
                        <span class="hide tpl-progress-pending pending">
                                <a class="progress-circle tooltip-tag assessment-card-target-circle"
                                   title="<?php echo $translate->_("Loading Targets..."); ?>"
                                   data-toggle="tooltip"
                                   data-placement="bottom"
                                   data-progress-type="pending"
                                   data-targets-loaded="0"
                                   data-href="task_url"
                                   data-template-bind='[
                                       {"attribute": "data-dassessment-id", "value": "dassessment_id"}
                                   ]'>
                                    <div data-content="targets_pending"></div>
                                </a>
                            </span>
                        <span class="hide tpl-progress-inprogress inprogress">
                                <a class="progress-circle tooltip-tag assessment-card-target-circle"
                                   title="<?php echo $translate->_("Loading Targets..."); ?>"
                                   data-href="task_url"
                                   data-toggle="tooltip"
                                   data-placement="bottom"
                                   data-progress-type="inprogress"
                                   data-targets-loaded="0"
                                   data-template-bind='[
                                       {"attribute": "data-dassessment-id", "value": "dassessment_id"}
                                   ]'>
                                    <div data-content="targets_in_progress"></div>
                                </a>
                            </span>
                        <span class="hide tpl-progress-complete complete">
                                <a class="progress-circle tooltip-tag assessment-card-target-circle"
                                   title="<?php echo $translate->_("Loading Targets..."); ?>"
                                   data-href="task_url"
                                   data-toggle="tooltip"
                                   data-placement="bottom"
                                   data-progress-type="complete"
                                   data-targets-loaded="0"
                                   data-template-bind='[
                                       {"attribute": "data-dassessment-id", "value": "dassessment_id"}
                                   ]'>
                                    <div data-content="targets_completed"></div>
                                </a>
                            </span>
                        <div class="clearfix"></div>
                    </div>
                    <div class="hide tpl-task-details details" data-content="task_details"></div>

                    <!-- For a single target -->
                    <div class="hide tpl-task-target assessor">
                        <div>
                            <?php echo sprintf($translate->_("Target: <strong>%s</strong>"), "<span data-content='single_target_name'></span>"); ?>
                        </div>
                        <div class="hide tpl-task-target-group-role label assessment-task-meta">
                            <span data-content="single_target_group"></span>&nbsp;&bull;&nbsp;<span data-content="single_target_role"></span>
                        </div>
                        <div class="hide tpl-task-target-role label assessment-task-meta">
                            <span data-content="single_target_role"></span>
                        </div>
                        <div class="hide tpl-task-target-group label assessment-task-meta">
                            <span data-content="single_target_group"></span>
                        </div>
                    </div>

                    <!-- For an assessor -->
                    <div class="hide tpl-task-assessor assessor">
                        <div>
                            <?php echo sprintf($translate->_("Assessor: <strong>%s</strong>"), "<span data-content='assessor_name'></span>"); ?>
                        </div>
                        <div class="hide tpl-task-assessor-group-role label assessment-task-meta">
                            <span data-content="assessor_group"></span>&nbsp;&bull;&nbsp;<span data-content="assessor_role"></span>
                        </div>
                        <div class="hide tpl-task-assessor-role label assessment-task-meta">
                            <span data-content="assessor_role"></span>
                        </div>
                        <div class="hide tpl-task-assessor-group label assessment-task-meta">
                            <span data-content="assessor_group"></span>
                        </div>
                    </div>

                    <!-- Reminder checkbox -->
                    <div class="hide tpl-task-reminder assessment-task-select task-reminder">
                        <div class="fa-wrapper">
                            <span class="fa fa-bell"></span>
                        </div>
                        <label class="remind-label checkbox">
                            <input class="remind"
                                   type="checkbox"
                                   name="remind[]"
                                   value="1"
                                   data-template-bind='[
                                       {"attribute": "data-task-id", "value": "task_id"},
                                       {"attribute": "data-task-type", "value": "task_type"},
                                       {"attribute": "data-assessor-name", "value": "assessor_name"},
                                       {"attribute": "data-assessor-value", "value": "assessor_value"},
                                       {"attribute": "data-dassessment-id", "value": "dassessment_id"},
                                       {"attribute": "data-adistribution-id", "value": "adistribution_id"},
                                       {"attribute": "data-aprogress-id", "value": "aprogress_id"}
                                   ]'
                            />
                            <?php echo $translate->_(" Select and click the <strong>Send Reminders</strong> button above to send a reminder for all selected tasks."); ?>
                        </label>
                    </div>

                    <!-- PDF Download checkbox -->
                    <div class="hide tpl-task-pdf-download assessment-task-select pdf-download">
                        <div class="fa-wrapper">
                            <span class="fa fa-download"></span>
                        </div>
                        <label class="checkbox">
                            <input class="generate-pdf"
                                   type="checkbox"
                                   name="generate-pdf[]"
                                   value="1"
                                   data-template-bind='[
                                       {"attribute": "data-dassessment-id", "value": "dassessment_id"},
                                       {"attribute": "data-aprogress-id", "value": "aprogress_id"}
                                   ]'
                            />
                            <?php echo $translate->_(" Select and click on the <strong>Download PDF(s)</strong> button above to download a PDF of all selected tasks."); ?>
                        </label>
                    </div>
                </div>
                <div class="assessment-task-link btn-group">
                    <a class="hide tpl-task-view view-task-link full-width" data-href="task_url"><?php echo $translate->_("View Task") ?> &rtrif;</a>
                    <a class="hide tpl-task-remove remove full-width"
                       href="#"
                       data-template-bind='[
                           {"attribute": "data-task-id", "value": "task_id"},
                           {"attribute": "data-task-type", "value": "task_type"},
                           {"attribute": "data-target-list", "value": "task_targets"},
                           {"attribute": "data-adistribution-id", "value": "adistribution_id"},
                           {"attribute": "data-dassessment-id", "value": "dassessment_id"}
                       ]'>
                        <?php echo $translate->_("Remove Task"); ?>
                    </a>
                </div>
            </div>
        </script>
        <?php
    }
}