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
 * View class for rendering the delegation task deletion modal
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Modals_DeleteTasks extends Views_Assessments_Base
{
    /**
     * Validate our options array.
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        if (!isset($options["action_url"])) {
            return false;
        }
        if (!isset($options["deleted_reasons"]) || empty($options["deleted_reasons"])) {
            return false;
        }

        if (!isset($options["modal_mode"])) {
            return false;
        }

        if ($options["modal_mode"] != "assessment" &&
            $options["modal_mode"] != "delegation") {
            return false;
        }
        // Passed all tests.
        return true;
    }

    /**
     * Main render logic for this view. This view renders the HTML for either an assessment or delegation task
     * deletion modal window. The type of modal rendered is specified in the options parameter.
     *
     * @param mixed $options
     * @return bool
     */
    protected function renderView($options = array()) {
        if ($options["modal_mode"] == "assessment") {
            $this->renderDeleteAssessmentTaskModal($options["action_url"], $options["deleted_reasons"]);
        } else if ($options["modal_mode"] == "delegation"){
            $this->renderDeleteDelegationTaskModal($options["action_url"], $options["deleted_reasons"]);
        } else {
            $this->renderError();
        }
    }

    /**
     * Render an error message.
     */
    protected function renderError() {
        global $translate; ?>
        <div class="alert alert-danger"><?php echo $translate->_("Unable to render delete tasks modal."); ?></div>
        <?php
    }

    /**
     * Render the assessment task deletion modal.
     *
     * @param string $action_url
     * @param array $deleted_reasons
     */
    private function renderDeleteAssessmentTaskModal($action_url, $deleted_reasons) {
        global $translate;
        ?>
        <div class="modal hide fade" id="delete-tasks-modal" data-backdrop="static" data-keyboard="false">
            <form name="delete-tasks-modal-form" id="delete-tasks-modal-form" method="POST" action="<?php echo $action_url?>">
                <input type="hidden" name="step" value="2"/>
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h3><?php echo $translate->_("Delete Task(s)"); ?></h3>
                </div>
                <div class="modal-body">
                    <div id="tasks-success" class="space-above space-below hide">
                        <?php echo display_success($translate->_("Task(s) deleted successfully.")) ?>
                    </div>
                    <div id="tasks-error" class="hide">
                    </div>
                    <div id="delete-tasks-details-section" class="hide">
                        <strong><?php echo $translate->_("Please confirm that you want to delete the following assessment task(s):"); ?></strong>
                        <div id="target-details" class="space-below space-left hide">
                            <table id="delete-tasks-details-table" class="table table-striped table-bordered space-above">
                                <thead>
                                <th><?php echo $translate->_("Assessor"); ?></th>
                                <th><?php echo $translate->_("Target"); ?></th>
                                <th><?php echo $translate->_("Delivery Date"); ?></th>
                                </thead>
                                <tbody>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div id="no-tasks-selected" class="hide">
                        <?php echo $translate->_("No tasks selected for deletion.") ?>
                    </div>
                    <div id="delete-tasks-reason-section" class="hide">
                        <label for="delete-tasks-reason" class="control-label form-required"><strong><?php echo $translate->_("Reasons for Removal"); ?>: </strong></label>
                        <div class="controls">
                            <select id="delete-tasks-reason" name="delete-tasks-reason">
                                <?php foreach ($deleted_reasons as $reason): ?>
                                    <option value="<?php echo $reason->getID(); ?>"><?php echo $reason->getDetails(); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="delete-tasks-other-reason" class="control-label space-above"><strong><?php echo $translate->_("Notes"); ?>: </strong></label>
                            <textarea id="delete-tasks-other-reason" class="expandable" name="delete-tasks-other-reason"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" id="delete-tasks-close-button" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Close"); ?></a>
                        <a href="#" id="delete-tasks-modal-confirm" name="delete-tasks-confirm" class="btn btn-danger pull-right"><?php echo $translate->_("Confirm Deletion"); ?></a>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Render the delegation task deletion modal.
     *
     * @param string $action_url
     * @param array $deleted_reasons
     */
    private function renderDeleteDelegationTaskModal($action_url, $deleted_reasons) {
        global $translate;
        ?>
        <div class="modal hide fade" id="delete-delegation-tasks-modal">
            <form name="delete-tasks-modal-form" id="delete-tasks-modal-form" method="POST" action="<?php echo $action_url?>">
                <input type="hidden" name="step" value="2"/>
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h3><?php echo $translate->_("Delete Delegated Task(s)"); ?></h3>
                </div>
                <div class="modal-body">
                    <div id="tasks-success" class="space-above space-below hide">
                        <div class="alert alert-block alert-success" id="display-success-box">
                            <ul>
                                <li><?php echo $translate->_("Delegation assignment and task(s) deleted successfully."); ?></li>
                            </ul>
                        </div>
                    </div>
                    <div id="tasks-error" class="hide">
                    </div>
                    <div id="delete-tasks-details-section" class="hide">
                        <strong><?php echo $translate->_("Please confirm that you want to delete the following delegation assignment(s) and related assessment task(s):"); ?></strong>
                        <div id="target-details" class="space-below space-left hide">
                            <table id="delete-tasks-details-table" class="table table-striped table-bordered space-above">
                                <thead>
                                    <th><?php echo $translate->_("Target"); ?></th>
                                    <th><?php echo $translate->_("Assessor"); ?></th>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div id="no-tasks-selected" class="hide">
                        <?php echo $translate->_("No tasks selected for deletion.") ?>
                    </div>
                    <div id="delete-tasks-reason-section" class="hide">
                        <label for="delete-tasks-reason" class="control-label form-required"><strong><?php echo $translate->_("Reasons for Removal"); ?>: </strong></label>
                        <div class="controls">
                            <select id="delete-tasks-reason" name="delete-tasks-reason">
                                <?php foreach ($deleted_reasons as $reason): ?>
                                    <option value="<?php echo $reason->getID(); ?>"><?php echo $reason->getDetails(); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="delete-tasks-other-reason" class="control-label space-above"><strong><?php echo $translate->_("Notes"); ?>: </strong></label>
                            <textarea id="delete-tasks-other-reason" class="expandable" name="delete-tasks-other-reason"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Close"); ?></a>
                        <a href="#" id="delete-delegation-tasks-modal-confirm" name="delete-tasks-confirm" class="btn btn-danger pull-right hide"><?php echo $translate->_("Confirm Deletion"); ?></a>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
}