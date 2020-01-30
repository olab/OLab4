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
 * View class for rendering task progress clearing modal.
 *
 * @author Organization: Queen's University.
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Modals_ClearTaskProgress extends Views_Assessments_Base {

    protected function validateOptions($options = array()) {
        if (!isset($options["aprogress_id"])) {
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
        global $translate;
        ?>
        <div class="modal fade" id="clear-task-progress-modal">
            <form name="clear-task-progress-modal-form" id="clear-task-progress-modal-form" method="POST" action="#">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h3><?php echo $translate->_("Clear Task Progress"); ?></h3>
                </div>
                <div class="modal-body">
                    <div id="clear-task-progress-success" class="space-above space-below hide">
                        <?php echo display_success($translate->_("Task progress successfully cleared.")) ?>
                    </div>
                    <div id="clear-task-progress-error"></div>
                    <div id="clear-task-progress-details-section">
                        <strong><?php echo $translate->_("This will clear all responses for this attempt. The attempt itself will remain un-deleted."); ?></strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" id="clear-task-progress-close-button" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Close"); ?></a>
                        <input type="button" id="clear-task-progress-confirm" class="btn btn-primary pull-right" name="clear-task-progress-confirm" data-aprogress-id="<?php echo $options["aprogress_id"]; ?>" value="<?php echo $translate->_("Clear Progress"); ?>"/>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
}