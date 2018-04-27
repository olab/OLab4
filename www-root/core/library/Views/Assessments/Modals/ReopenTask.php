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
 * View class for rendering task reopening modal.
 *
 * @author Organization: Queen's University.
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Modals_ReopenTask extends Views_Assessments_Base {

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
        <div class="modal fade" id="reopen-task-modal">
            <form name="reopen-task-modal-form" id="reopen-task-modal-form" method="POST" action="#">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h3><?php echo $translate->_("Reopen Task"); ?></h3>
                </div>
                <div class="modal-body">
                    <div id="reopen-task-success" class="space-above space-below hide">
                        <?php echo display_success($translate->_("Task successfully reopened.")) ?>
                    </div>
                    <div id="reopen-task-error"></div>
                    <div id="reopen-task-details-section">
                        <strong><?php echo $translate->_("This will set the task back to in-progress. Any previous notifications have already been sent."); ?></strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" id="reopen-task-close-button" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Close"); ?></a>
                        <input type="button" id="reopen-task-confirm" class="btn btn-primary pull-right" name="reopen-task-confirm" data-aprogress-id="<?php echo $options["aprogress_id"]; ?>" value="<?php echo $translate->_("Reopen Task"); ?>"/>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
}