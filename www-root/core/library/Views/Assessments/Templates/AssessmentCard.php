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
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Templates_AssessmentCard extends Views_HTML {

    /**
     * Validate our options array.
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return true;
    }

    /**
     * Render the modal view.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $this->addHeadScripts();
        ?>
        <script type="text/html" id="assessment-card">
            <li>
                <div class="assessment-task">
                    <div class="assessment-task-wrapper">
                        <div class="distribution">
                            <div class="assessment-task-title-div">
                                <span class="assessment-task-title" data-content="assessment_task_title"></span>
                            </div>
                            <div class="label assessment-task-event-info-badge" data-content="event_details_label"></div>
                            <div class="label assessment-task-schedule-info-badge" data-content="schedule_details"></div>
                            <div class="label assessment-task-delegation-badge" data-content="delegation_label"></div>
                            <div class="label assessment-task-release-schedule-info-badge" data-content="approver_label"></div>
                            <div class="assessment-task-date-range">
                                <em data-content="date_range"></em>
                            </div>
                            <div class="assessment-task-date-range event-date-range" data-content="event_date_range"></div>
                            <div class="assessment-task-date assessment-delivery-date" data-content="delivery_date"></div>
                            <div class="assessment-task-date completed_date" data-content="completed_date"></div>
                            <div class="assessment-task-date delegated-by" data-content="delegated_by"></div>
                            <div class="assessment-task-date delegated-date" data-content="delegated_date"></div>
                        </div>
                        <div class="assessment-progress">
                            <span class="progress-title" data-content="progress_text"></span>
                            <span class="pending">
                                <a class="progress-circle tooltip-tag" href="#" data-placement="bottom" data-toggle="tooltip" data-template-bind='[{"attribute": "title", "value": "progress_title_pending"}, {"attribute": "data-original-title", "value": "progress_title_pending"}, {"attribute": "href", "value": "task_url"}]'>
                                    <div class="pending-attempts-text" data-content="pending_attempts"></div>
                                </a>
                            </span>
                            <span class="inprogress">
                                <a class="progress-circle tooltip-tag" href="#" data-placement="bottom" data-toggle="tooltip" data-template-bind='[{"attribute": "title", "value": "progress_title_inprogress"}, {"attribute": "data-original-title", "value": "progress_title_inprogress"}, {"attribute": "href", "value": "task_url"}]'>
                                    <div class="inprogress-attempts-text" data-content="inprogress_attempts"></div>
                                </a>
                            </span>
                            <span class="complete">
                                <a class="progress-circle tooltip-tag" href="#" data-placement="bottom" data-toggle="tooltip" data-template-bind='[{"attribute": "title", "value": "progress_title_complete"}, {"attribute": "data-original-title", "value": "progress_title_complete"}, {"attribute": "href", "value": "task_url"}]'>
                                    <div data-content="complete_attempts"></div>
                                </a>
                            </span>
                            <div class="clearfix"></div>
                        </div>
                        <div class="details" data-content="task_details"></div>
                        <div class="task-description" data-content="task_description"></div>
                        <div class="assessor hide">
                            <div  data-content="assessor_data" class="assessor-data hide"></div>
                            <span data-content="assessor_group_role" class="label assessment-task-meta assessor-group-role hide"></span>
                            <div  data-content="external_badge" class="label assessment-task-meta assessor-external-badge"></div>
                        </div>
                        <div class="assessment-task-select task-reminder">
                            <div class="fa-wrapper">
                                <span class="fa fa-bell"></span>
                            </div>
                            <label class="remind-label checkbox">
                                <input class="remind" type="checkbox" name="remind[]" data-template-bind='[{"attribute": "data-assessor-name", "value": "data_assessor_name"}, {"attribute": "data-assessor-id", "value": "data_assessor_id"}, {"attribute": "value", "value": "data_assessment_id"}, {"attribute": "data-task-type", "value": "data_task_type"}, {"attribute": "data-addelegation-id", "value": "data_assessment_id"}, {"attribute": "data-adistribution-id", "value": "data_adistribution_id"}]'>
                                <span class="send-reminder-text" data-content="send_reminder_text"></span>
                            </label>
                        </div>
                        <div class="assessment-task-select pdf-download">
                            <div class="fa-wrapper">
                                <span class="fa fa-download"></span>
                            </div>
                            <label class="checkbox">
                                <input class="generate-pdf" type="checkbox" name="generate-pdf[]" data-template-bind='[{"attribute": "data-assessor-name", "value": "data_assessor_name"}, {"attribute": "data-assessor-value", "value": "data_assessor_value"}, {"attribute": "data-targets", "value": "data_targets"}, {"attribute": "data-assessment-id", "value": "data_assessment_id"}, {"attribute": "data-adistribution-id", "value": "data_adistribution_id"}, {"attribute": "value", "value": "value"}]'>
                                <span class="generate-pdf-text" data-content="generate_pdf_text"></span>
                            </label>
                        </div>
                    </div>
                    <div class="assessment-task-link btn-group">
                        <a class="view-task-link" data-href="task_url"><?php echo $translate->_("View Task") ?> &rtrif;</a>
                        <span class="remove" data-assessment="" data-toggle="modal" data-target="#remove_form_modal" data-template-bind='[{"attribute": "data-assessor-type", "value": "data_assessor_type"}, {"attribute": "data-assessor-value", "value": "data_assessor_value"}, {"attribute": "data-target-id", "value": "data_target_id"}, {"attribute": "data-distribution-id", "value": "data_adistribution_id"}, {"attribute": "data-assessment-id", "value": "data_assessment_id"}, {"attribute": "data-delivery-date", "value": "value"}, {"attribute": "data-task-type", "value": "data_task_type"}]'>
                            <a class="remove-task-link" href="#remove_form_modal" data-content="remove_task_text"></a>
                        </span>
                    </div>
                </div>
            </li>
        </script>
    <?php
    }

    /**
     * @param string $entrada_url
     * @param int $course_id
     * @param string $module
     *
     * Adds required CSS and JS files to the $HEAD array and adds entry to the $BREADCRUMB array for this view.
     */
    protected function addHeadScripts () {
        global $HEAD;
        $HEAD[] = Entrada_Utilities_jQueryHelper::addjQuery();
        $HEAD[] = Entrada_Utilities_jQueryHelper::addjQueryLoadTemplate();
    }
}