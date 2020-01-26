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
 * View class for rendering all of the assessment views in a tabbed layout. Appended
 * to the assessment and evaluation dashboard.
 *
 * @author Organization: Queen's University.
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Dashboard_TaskLists extends Views_Assessments_Base {

    protected function renderView($options = array()) {
        global $translate, $HEAD;
        $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
        $task_types = array("assessment", "evaluation"); ?>

        <div class="tabbable task-list-tabs">
            <ul class="nav nav-tabs">
                <li id="assessment-tab" class="active"><a href="#assessment-tab-pane" data-toggle="tab"><?php echo $translate->_("Assessments"); ?></a></li>
                <li id="evaluation-tab"><a href="#evaluation-tab-pane" data-toggle="tab"><?php echo $translate->_("Evaluations"); ?></a></li>
                <li id="report-tab"><a href="#evaluation-reports" data-toggle="tab"><?php echo $translate->_("Reports"); ?></a></li>
            </ul>
        </div>

        <div class="tab-content tasks-tabs">
            <?php foreach ($task_types as $task_type) :
                $filter_types = array();
                $filter_types[] =  array("target_id" => "all", "target_label" => $translate->_("All"));
                $filter_types[] =  array("target_id" => "distribution", "target_label" => $translate->_("Distribution Based"));
                $filter_types[] =  array("target_id" => "triggered", "target_label" => $translate->_("Triggered"));

                ?>
                <script type="text/javascript">
                    jQuery(document).ready(function ($) {
                        $("#filter-<?php echo $task_type; ?>-btn").advancedSearch({
                            filters: {
                                <?php echo $task_type ?>_schedule_type: {
                                    label: "<?php echo $translate->_("Delivery Type Filters"); ?>",
                                    data_source: <?php echo json_encode($filter_types); ?>,
                                    mode: "radio",
                                    selector_control_name: "<?php echo $task_type; ?>_filter_schedule_type",
                                    search_mode: false
                                }
                            },
                            control_class: "filter-outstanding-tasks-selector",
                            no_results_text: "<?php echo $translate->_(""); ?>",
                            parent_form: $("#filter-<?php echo $task_type; ?>-form"),
                            width: 300,
                            modal: false
                        });
                    });
                </script>

                <div class="tab-pane <?php echo $task_type == "assessment" ? "active" : ""; ?>" id="<?php echo $task_type; ?>-tab-pane">
                    <div class="half-wrapper">
                        <h2 class="inline-title"><?php echo sprintf($translate->_("%s Tasks") , ucfirst($task_type)); ?></h2>
                        <div class="full-width space-below">
                            <form id="filter-<?php echo $task_type; ?>-form">
                                <a id="trigger-assessment" href="<?php echo $options["log_assessment_url"] ?>" class="btn btn-success block"><?php echo $translate->_("Record Assessment"); ?></a>
                                <button id="filter-<?php echo $task_type; ?>-btn" class="btn filter-tasks-btn space-left"><?php echo $translate->_("Delivery Type Filters"); ?> <i class="icon-chevron-down btn-icon pull-right"></i></button>
                            </form>
                        </div>
                        <div>
                            <div class="tabbable tab-title-div">
                                <ul class="nav nav-pills">
                                    <li class="active">
                                        <a class="task-tab" style="padding: 10px;" href="#tab-<?php echo $task_type; ?>-outstanding-tasks" data-toggle="tab"><?php echo $translate->_("Outstanding"); ?>
                                            <span class="badge assessment-badge hide"><?php echo isset($options["task_count"]["incomplete-" . $task_type][0]["COUNT(*)"]) ? $options["task_count"]["incomplete-" . $task_type][0]["COUNT(*)"] : "0"; ?></span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="task-tab" href="#tab-<?php echo $task_type; ?>-upcoming-tasks" data-toggle="tab"><?php echo $translate->_("Upcoming"); ?>
                                            <span class="badge assessment-badge"><?php echo isset($options["task_count"]["upcoming-" . $task_type][0]["COUNT(*)"]) ? $options["task_count"]["upcoming-" . $task_type][0]["COUNT(*)"] : "0"; ?></span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="task-tab" href="#tab-<?php echo $task_type; ?>-deleted-tasks" data-toggle="tab"><?php echo $translate->_("Deleted"); ?>
                                            <span class="badge assessment-badge"><?php echo isset($options["task_count"]["deleted-" . $task_type][0]["COUNT(*)"]) ? $options["task_count"]["deleted-" . $task_type][0]["COUNT(*)"] : "0"; ?></span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="tab-content">
                            <div class="tab-pane active" id="tab-<?php echo $task_type; ?>-outstanding-tasks">
                                <?php
                                $outstanding_assessments = new Views_Assessments_Dashboard_OutstandingTasks();
                                $outstanding_assessments->render(array("tasks" => $options[$task_type]["outstanding_tasks"] = array(), "task_type" => $task_type));
                                ?>
                            </div>
                            <div class="tab-pane" id="tab-<?php echo $task_type; ?>-upcoming-tasks">
                                <?php
                                $upcoming_assessments = new Views_Assessments_Dashboard_UpcomingTasks();
                                $upcoming_assessments->render(array("tasks" => $options[$task_type]["upcoming_tasks"] = array(), "task_type" => $task_type));
                                ?>
                            </div>
                            <div class="tab-pane" id="tab-<?php echo $task_type; ?>-deleted-tasks">
                                <?php
                                $recently_deleted = new Views_Assessments_Dashboard_DeletedTasks();
                                $recently_deleted->render(array("tasks" => $options[$task_type]["deleted_tasks"] = array(), "task_type" => $task_type));
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="tab-pane" id="evaluation-reports">
                <h2><?php echo $translate->_("Evaluations"); ?></h2>
                <ul>
                    <li><a href="<?php echo ENTRADA_URL; ?>/admin/assessments/reports?section=rotation-evaluations"><?php echo $translate->_("Rotation Evaluations (Aggregated)"); ?></a></li>
                    <li><a href="<?php echo ENTRADA_URL; ?>/admin/assessments/reports?section=learning-event-evaluations"><?php echo $translate->_("Learning Event Evaluations (Aggregated)"); ?></a></li>
                    <li><a href="<?php echo ENTRADA_URL; ?>/admin/assessments/reports?section=learning-event-feedback-evaluations"><?php echo $translate->_("Learning Event Feedback Forms (Aggregated)"); ?></a></li>
                    <li><a href="<?php echo ENTRADA_URL; ?>/admin/assessments/reports?section=faculty-reports"><?php echo $translate->_("Faculty Reports (Aggregated)"); ?></a></li>
                    <li><a href="<?php echo ENTRADA_URL; ?>/admin/assessments/reports?section=course-reports"><?php echo $translate->_("course_reports") . $translate->_(" (Aggregated)"); ?></a></li>
                </ul>
                <h2><?php echo $translate->_("Assessments"); ?></h2>
                <ul>
                    <li><a href="<?php echo ENTRADA_URL; ?>/admin/assessments/reports?section=learner-assessments"><?php echo $translate->_("Learner Assessments"); ?></a></li>
                    <li><a href="<?php echo ENTRADA_URL; ?>/admin/assessments/reports?section=learner-reports"><?php echo $translate->_("Learner Reports (Aggregated)"); ?></a></li>
                    <li><a href="<?php echo ENTRADA_URL; ?>/admin/assessments/reports?section=timeliness-of-completion-report"><?php echo $translate->_("Timeliness of Completion Report"); ?></a></li>
                    <li><a href="<?php echo ENTRADA_URL; ?>/admin/assessments/reports?section=assessment-tools-feedbacks"><?php echo $translate->_("Assessment Tools Feedback Report"); ?></a></li>
                </ul>
                <h2><?php echo $translate->_("Leave"); ?></h2>
                <ul>
                    <li><a href="<?php echo ENTRADA_URL; ?>/admin/assessments/reports?section=leave-by-block-report"><?php echo $translate->_("Leave by Block Report"); ?></a></li>
                    <li><a href="<?php echo ENTRADA_URL; ?>/admin/assessments/reports?section=rotation-leave-report"><?php echo $translate->_("Rotation Leave Report"); ?></a></li>
                </ul>
                <h2><?php echo $translate->_("Distribution"); ?></h2>
                <ul>
                    <li><a href="<?php echo ENTRADA_URL; ?>/admin/assessments/reports?section=distribution-schedule-report"><?php echo $translate->_("Distribution Delivery Schedule"); ?></a></li>
                    <li><a href="<?php echo ENTRADA_URL; ?>/admin/assessments/reports?section=distribution-reviewer"><?php echo $translate->_("Distribution Reviewer"); ?></a></li>
                </ul>
            </div>
        </div>

        <div id="reminder-modal" class="modal hide fade">
            <form name="reminder-modal-form" id="reminder-modal-form" method="POST" action="<?php echo ENTRADA_URL ?>/assessments/assessment?section=api-assessment">
                <input type="hidden" name="step" value="2"/>
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times</button>
                    <h3><?php echo $translate->_("Send Reminders"); ?></h3>
                </div>
                <div class="modal-body">
                    <div id="reminders-success" class="space-above space-below hide">
                        <?php echo display_success($translate->_("Reminders sent successfully.")); ?>
                    </div>
                    <div id="reminders-error" class="hide">
                        <?php echo $translate->_("No tasks selected for reminders."); ?>
                    </div>
                    <div id="reminder-details-section" class="hide">
                        <strong><?php echo $translate->_("A reminder will be sent for the following assessor(s):"); ?></strong>
                        <div id="reminder-details" class="space-below space-above hide">
                            <table id="reminder-summary-table" class="table table-striped table-bordered">
                                <thead>
                                <tr>
                                    <th width="70%"><?php echo $translate->_("Assessor Name") ?></th>
                                    <th width="30%"><?php echo $translate->_("Number of Notifications") ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div id="no-reminders-selected" class="hide">
                        <?php echo $translate->_("No tasks selected for reminders."); ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Close"); ?></a>
                        <a href="#" id="reminder-modal-confirm" name="reminder-confirm" class="btn btn-info pull-right"><?php echo $translate->_("Confirm Reminders"); ?></a>
                    </div>
                </div>
            </form>
        </div>

        <?php $options = Models_Assessments_TaskDeletedReason::fetchAllRecordsOrderByOrderID(); ?>
        <div id="remove_form_modal" class="modal hide fade">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h3><?php echo $translate->_("Remove Task"); ?></h3>
            </div>
            <div class="modal-body">
                <input type="hidden" id="removing_reason" name="removing_reason" value="" />
                <input type="hidden" id="removing_reason_id" name="removing_reason_id" value="" />
                <input type="hidden" id="current_record_data" name="current_record_data" value="" />
                <div id="remove-msgs"></div>
                <form class="form-horizontal">
                    <div class="control-group">
                        <label class="control-label"><?php echo $translate->_("Reason to remove:"); ?></label>
                        <div class="controls">
                            <?php
                            foreach ($options as $option) { ?>
                                <label class="radio">
                                    <input data-reason="<?php echo html_encode($option->getDetails()) ?>" type="radio" name="reason" value="<?php echo $option->getID();?>" />
                                    <?php echo html_encode($option->getDetails()) ?>
                                </label>
                                <?php
                            }
                            ?>
                        </div>
                        <label class="control-label"><?php echo $translate->_("Notes:"); ?></label>
                        <div id="other_reason_div" class="control-group space-above medium">
                            <div class="controls">
                                <textarea rows="4" class="span10" id="other_reason" name="other_reason"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <input type="button" class="btn pull-left" id="cancel_remove" data-dismiss="modal" value="Cancel"/>
                <input type="button" class="btn btn-danger" id="save_remove" value="<?php echo $translate->_("Remove Task"); ?>"/>
            </div>
        </div>
        <?php
    }
}