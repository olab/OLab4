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
 * View class for rendering recently deleted assessments and evaluations, appended
 * to the assessment and evaluation dashboard.
 *
 * @author Organization: Queen's University.
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Dashboard_DeletedTasks extends Views_Assessments_Base {

    protected function validateOptions($options = array()) {
        if (!isset($options["task_type"])) {
            return false;
        }
        return true;
    }

    protected function renderView($options = array()) {
        global $translate; ?>
        <div class="row-fluid space-above space-below">
            <button class="btn disabled pull-left task-btn <?php echo html_encode($options["task_type"]); ?>-deleted-tasks-previous"><i class="icon-chevron-left"></i> <?php echo $translate->_("Previous"); ?></button>
            <button class="btn space-left pull-left task-btn <?php echo html_encode($options["task_type"]); ?>-deleted-tasks-next"><?php echo $translate->_("Next"); ?> <i class="icon-chevron-right"></i></button>
            <input placeholder="<?php echo $translate->_("Search Tasks..."); ?>" type="text" class="task-table-search search-icon space-left <?php echo html_encode($options["task_type"]); ?>-deleted-tasks-search">
            <span class="badge assessment-badge hide <?php echo html_encode($options["task_type"]); ?>-deleted-tasks-search-results-found"></span>
            <button class="btn btn-primary pull-right space-right hide-tasks"><i class="icon-eye-close icon-white"></i> <?php echo $translate->_("Hide Selected Tasks"); ?></button>
        </div>
        <div class="fixed-tab-height task-table-wrap">
            <table class="table table-striped table-bordered task-table <?php echo html_encode($options["task_type"]); ?>-deleted-tasks">
                <thead>
                    <th style="width: 35%;"><?php echo $translate->_("Task"); ?></th>
                    <th style="width: 20%;"><?php echo $translate->_("Owner"); ?></th>
                    <th style="width: 35%;"><?php echo $translate->_("Reason"); ?></th>
                    <th class="dashboard-center-info select-all-deleted-tasks" style="width: 10%;"><?php echo $translate->_("Hide"); ?></th>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
        <?php
    }
}