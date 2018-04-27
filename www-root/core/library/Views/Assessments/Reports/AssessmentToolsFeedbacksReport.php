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
 * Display the assessment tools feedbacks report
 *
 * @author Organization: Queen's University.
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Reports_AssessmentToolsFeedbacksReport extends Views_Assessments_Base
{
    /**
     * Perform options validation
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return true;
    }

    /**
     * Render the table.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        global $translate;

        $this->renderFilters($options);

        ?>
        <table id="assessment-tools-feedbacks-report-table" class="table table-bordered table-striped">
            <thead>
            <tr>
                <th width="15%"><?php echo $translate->_("Date"); ?></th>
                <th width="20%"><?php echo $translate->_("Assessor"); ?></th>
                <th width="20%"><?php echo $translate->_("Tool"); ?></th>
                <th width="40%"><?php echo $translate->_("Feedback"); ?></th>
                <th width="5%"></th>
            </tr>
            </thead>
            <tbody>
            </tbody>
        </table>

        <div id="assessment-tools-feedbacks-loading" class="hide">
            <p><?php echo $translate->_("Loading Assessment Tools Feedback..."); ?></p>
            <img src="<?php echo ENTRADA_URL; ?>/images/loading.gif">
        </div>
        <a id="load-feedbacks" class="btn btn-block hide"></a>

        <?php

        $this->renderRowTemplate();
        $this->renderJquery();
    }


    private function renderRowTemplate() {
        ?>
        <script type="text/html" id="assessment-tools-feedbacks-report-table-row">
            <td nowrap="nowrap" data-content="tools-feedback-date"></td>
            <td nowrap="nowrap" data-content="tools-feedback-assessor"></td>
            <td data-content="tools-feedback-tool"></td>
            <td data-content="tools-feedback-feedback"></td>
            <td><a data-href="assessment-url" class="btn btn-default" href="#"><i class="icon-file"></i></a></td>
        </script>
        <?php
    }

    private function renderFilters($options) {
        global $translate;

        $start_date = isset($options["start_date"]) ? $options["start_date"] : null;
        $end_date = isset($options["end_date"]) ? $options["end_date"] : null;
        $courses = isset($options["courses"]) ? $options["courses"] : null;
        $tools = isset($options["tools"]) ? $options["tools"] : null;

        ?>
        <form class="form-horizontal space-above large" id="assessment-tools-feedbacks-form">
            <div class="control-group" id="report-date-range-div">
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
            <div class="control-group" id="select-course-div">
                <label for="select-course-btn" class="control-label" for="select-course-btn"><?php echo $translate->_("Select Course:"); ?></label>
                <div class="controls">
                    <button id="select-course-btn" class="btn"><?php echo $translate->_("Browse Courses "); ?><i class="icon-chevron-down"></i></button>
                </div>
            </div>
            <div class="control-group" id="select-tools-div">
                <label for="select-tools-btn" class="control-label" for="select-tool-btn"><?php echo $translate->_("Select Tool(s):"); ?></label>
                <div class="controls">
                    <button id="select-tools-btn" class="btn"><?php echo $translate->_("Browse Tools "); ?><i class="icon-chevron-down"></i></button>
                    <?php if ($tools && is_array($tools) && count($tools)): ?>
                        <ul id="tools_list_container" class="selected-items-list">
                            <?php foreach ($tools as $tool): ?>
                            <li class="tools_target_item tools_<?php echo $tool["form_id"]; ?>" data-id="1442">
                                <span class="selected-list-container">
                                    <span class="selected-list-item"><?php echo $translate->_("Tool(s)"); ?></span><span class="remove-selected-list-item remove-target-toggle" data-id="<?php echo $tool["form_id"]; ?>" data-filter="tools">×</span>
                                </span><?php echo html_encode($tool["title"]); ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <a class="btn btn-success space-above" id="apply-filter-btn" title="<?php echo $translate->_("Apply Filters"); ?>" ><?php echo $translate->_("Apply Filters") ?></a>
            <a class="btn btn-default space-above space-left" id="reset-filter-btn" title="<?php echo $translate->_("Reset Filters"); ?>" ><?php echo $translate->_("Reset Filters") ?></a>
            <a class="btn btn-default space-above pull-right"
               id="generate-pdf-btn"
               href="<?php echo ENTRADA_URL . "/admin/assessments?section=api-tools-feedbacks-reports&method=get-assessments-tools-feedbacks-pdf"; ?>"
               title="<?php echo $translate->_("Download PDF(s)"); ?>">
                <?php echo $translate->_("Download PDF(s)") ?>
            </a>
            <input id="report-offset" type="hidden" name="offset" value="0" />
            <input id="report-limit" type="hidden" name="limit" value="25" />

            <?php
            if ($courses) :
                foreach ($courses AS $course): ?>
                    <input type="hidden" name="courses[]" id="courses_<?php echo html_encode($course->getID()); ?>" data-label="<?php echo html_encode($course->getCourseName()); ?>" value="<?php echo html_encode($course->getID()); ?>" class="search-target-control courses_search_target_control" />
                <?php endforeach;
             endif;
             ?>

            <?php
            if ($tools && is_array($tools) && count($tools)) :
                foreach ($tools AS $tool): ?>
                    <input type="hidden"
                           data-label="<?php echo html_encode($tool["title"]); ?>"
                           name="tools[]"
                           id="tools_<?php echo $tool["form_id"]; ?>"
                           value="<?php echo $tool["form_id"]; ?>"
                           class="search-target-control tools_search_target_control" />
                <?php endforeach;
            endif;
            ?>

        </form>
        <?php
    }

    private function renderJquery() {
        global $translate;
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $("#select-course-btn").advancedSearch({
                    api_url : "<?php echo ENTRADA_URL; ?>/admin/assessments?section=api-tools-feedbacks-reports",
                    resource_url: ENTRADA_URL,
                    filters : {
                        courses : {
                            label : "<?php echo $translate->_("Course(s)"); ?>",
                            data_source : "get-user-courses",
                            mode: "checkbox"
                        }
                    },
                    list_selections: false,
                    no_results_text: "<?php echo $translate->_("No course found matching the search criteria."); ?>",
                    parent_form: $("#assessment-tools-feedbacks-form"),
                    control_class: "course-selector",
                    width: 350
                });

                $("#select-tools-btn").advancedSearch({
                    api_url : "<?php echo ENTRADA_URL; ?>/admin/assessments?section=api-tools-feedbacks-reports",
                    resource_url: ENTRADA_URL,
                    filters : {
                        tools : {
                            label : "<?php echo $translate->_("Tool(s)"); ?>",
                            data_source : "get-user-tools",
                            mode: "checkbox"
                        }
                    },
                    no_results_text: "<?php echo $translate->_("No tools found matching the search criteria."); ?>",
                    parent_form: $("#assessment-tools-feedbacks-form"),
                    control_class: "tools-selector",
                    width: 350
                });

                jQuery(".datepicker").datepicker({
                    dateFormat: "yy-mm-dd",
                    minDate: "",
                    maxDate: ""
                });
            });
        </script>
        <?php
    }
}