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
 * View class for modal window to display and confirm the addition of
 * assessment tasks to distributions.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Modals_AddTask extends Views_Assessments_Base
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
        return true;
    }

    /**
     * Render the modal view.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        global $translate; ?>
        <div class="modal fade text-center" id="add-task-modal" style="display: none">
            <form class="form-horizontal" name="distribution-data-form" id="distribution-data-form" method="POST" action="<?php echo $options["action_url"] ?>">
                <input type="hidden" name="step" value="2"/>
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times</button>
                    <h3><?php echo $translate->_("Add Task"); ?></h3>
                </div>
                <div class="modal-body">
                    <div id="msgs"></div>
                    <div id="add-task-delivery-notice" class="alert alert-notice hide">
                        <?php echo $translate->_("Please note, this assessment may take up to 24 hours before it becomes available."); ?>
                    </div>
                    <div id="add-task-success" class="space-above space-below hide">
                        <?php echo display_success($translate->_("Tasks added successfully.")); ?>
                    </div>
                    <div id="add-task-error" class="hide">
                    </div>
                    <div id="distribution-loading" class="hide">
                        <img src="<?php echo ENTRADA_URL . "/images/loading.gif"; ?>"/>
                        <p id="distribution-loading-msg"></p>
                    </div>
                    <div id="add-task-controls">
                        <div class="control-group">
                            <label for="add-task-delivery-date" class="control-label form-required"><?php echo $translate->_("Delivery Date"); ?></label>
                            <div class="controls input-append no-space-above pull-left" id="date-selector">
                                <input id="add-task-delivery-date" class="input-small datepicker" type="text" value="<?php echo date("Y-m-d"); ?>" name="add_task_delivery_date"/>
                                <span class="add-on pointer"><i class="icon-calendar"></i></span>
                            </div>
                        </div>
                        <div id="distribution-delegation-warning" class="alert alert-info hide">
                            <?php echo $translate->_("<strong>Note</strong>: <strong>Delegation</strong> was the selected distribution method, users selected in this step will be presented as a list to the delegator, who will then select the assessors from that list."); ?>
                        </div>
                        <div id="rs-select-assessors-individual" class="assessor-type-selector assessor-option">
                            <div class="control-group">
                                <label for="rs-external-assessors-search" class="control-label form-required"><?php echo $translate->_("Select Assessors"); ?></label>
                                <div id="rs-autocomplete-container" class="controls">
                                    <input id="rs-external-assessors-search" type="text" class="form-control search pull-left" name="external_assessors_search" placeholder="<?php echo $translate->_("Type to search for assessors..."); ?>"/>
                                    <div id="rs-autocomplete">
                                        <div id="rs-autocomplete-list-container"></div>
                                    </div>
                                </div>
                            </div>
                            <div id="rs-assessor-lists">
                                <div id="rs-assessor-list-internal" class="hide">
                                    <h3 id="rs-selected-assessors-list-heading"><?php echo $translate->_("Assessors"); ?></h3>
                                    <div id="rs-internal-assessors-list-container"></div>
                                </div>
                            </div>
                            <div id="rs-external-assessors-controls" class="hide">
                                <div>
                                    <div class="control-group">
                                        <label for="rs-assessor-firstname" class="control-label form-required"><?php echo $translate->_("First Name"); ?></label>
                                        <div class="controls">
                                            <input id="rs-assessor-firstname" name="assessor_firstname" class="form-control input-large pull-left" type="text"/>
                                        </div>
                                    </div>
                                    <div class="control-group">
                                        <label for="rs-assessor-lastname" class="control-label form-required"><?php echo $translate->_("Last Name"); ?></label>
                                        <div class="controls">
                                            <input id="rs-assessor-lastname" name="assessor_lastname" class="form-control input-large pull-left" type="text"/>
                                        </div>
                                    </div>
                                    <div class="control-group">
                                        <label for="rs-assessor-email" class="control-label form-required"><?php echo $translate->_("Email Address"); ?></label>
                                        <div class="controls">
                                            <input id="rs-assessor-email" name="assessor_email" class="form-control input-large pull-left" type="text"/>
                                        </div>
                                    </div>
                                    <a id="rs-add-external-user-btn" href="#" class="btn btn-mini btn-success space-below"><?php echo $translate->_("Add Assessor"); ?></a>
                                    <a id="rs-cancel-assessor-btn" href="#" class="btn btn-mini space-below"><?php echo $translate->_("Cancel"); ?></a>
                                </div>
                            </div>
                        </div>
                        <div id="specific_dates_target_options" class="target-options">
                            <div id="select-targets-individual" class="targets-type-selector targets-option">
                                <div class="control-group">
                                    <label for="targets-search" class="control-label form-required"><?php echo $translate->_("Select Targets"); ?></label>
                                    <div id="autocomplete-container" class="controls">
                                        <input id="targets-search" type="text" class="form-control search pull-left" name="targets_search" placeholder="<?php echo $translate->_("Type to search for targets..."); ?>"/>
                                        <div id="autocomplete">
                                            <div id="target-autocomplete-list-container"></div>
                                        </div>
                                    </div>
                                </div>
                                <div id="target-lists">
                                    <div id="target-list-internal" class="hide">
                                        <h3 id="selected-targets-list-heading"><?php echo $translate->_("Targets"); ?></h3>
                                        <div id="internal-targets-list-container"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Close"); ?></a>
                        <a href="#" id="add-task-modal-confirm" name="add-modal-task-confirm" class="btn btn-info pull-right"><?php echo $translate->_("Confirm Task"); ?></a>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
}