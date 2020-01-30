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
 * View class for rendering outstanding assessments, appended
 * to the assessment and evaluation dashboard.
 *
 * @author Organization: Queen's University.
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Dashboard_OutstandingTasks extends Views_Assessments_Base {

    protected function validateOptions($options = array()) {
        if (!isset($options["task_type"])) {
            return false;
        }
        return true;
    }

    protected function renderView($options = array()) {
        global $translate; ?>

        <div class="row-fluid space-above space-below">
            <input type="text" placeholder="<?php echo $translate->_("Search Tasks..."); ?>" class="task-table-search search-icon space-left <?php echo html_encode($options["task_type"]); ?>-outstanding-tasks-search">
            <span class="badge assessment-badge hide <?php echo html_encode($options["task_type"]); ?>-outstanding-tasks-search-results-found"></span>
            <button class="btn disabled pull-left task-btn <?php echo html_encode($options["task_type"]); ?>-outstanding-tasks-previous"><i class="icon-chevron-left"></i> <?php echo $translate->_("Previous"); ?></button>
            <button class="btn space-left pull-left task-btn <?php echo html_encode($options["task_type"]); ?>-outstanding-tasks-next"><?php echo $translate->_("Next"); ?> <i class="icon-chevron-right"></i></button>
            <a href="#reminder-modal" class="btn btn-primary reminder-btn pull-right space-right" title="<?php echo $translate->_("Send Reminder to Assessor"); ?>" data-toggle="modal"><i class="icon-bell icon-white"></i> <?php echo $translate->_("Send Reminders") ?></a>
        </div>
        <div class="fixed-tab-height task-table-wrap">
            <table class="table table-striped table-bordered task-table <?php echo html_encode($options["task_type"]); ?>-outstanding-tasks">
                <thead>
                    <th style="width: 50%;"><?php echo $translate->_("Task"); ?></th>
                    <th style="width: 30%;"><?php echo $translate->_("Owner"); ?></th>
                    <th class="dashboard-center-info" style="width: 10%;"><?php echo $translate->_("Targets"); ?></th>
                    <th class="dashboard-center-info select-all-reminders" style="width: 5%;"><i class="icon-bell"></i></th>
                    <th class="dashboard-center-info" style="width: 5%;"><i class="icon-trash"></i></th>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
        <?php
    }
}