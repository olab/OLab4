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
 * Modal for getting objective status update input.
 *
 * @author Organization: Queen's University.
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_CBME_Modals_ObjectiveStatusToggle extends Views_HTML {

    /**
     * Validate our options array.
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("proxy_id"));
    }

    /**
     * Render default error
     */
    protected function renderError() {
        global $translate;
        ?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render Toggle Objective Status Modal"); ?></strong>
        </div>
        <?php
    }

    /**
     * Render the modal view.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        global $translate;
        ?>
        <div class="modal fade" id="modal-objective-status-toggle" style="display: none">
            <div class="form-horizontal objective-status-toggle-modal-container">
                <input id="objective-status-toggle-proxy-id" name="proxy_id" value="<?php echo $options["proxy_id"]; ?>" type="hidden" />
                <input id="objective-status-toggle-objective-id" name="objective_id" value="" type="hidden" />
                <input id="objective-status-toggle-action" name="action" value="" type="hidden" />
                <input id="objective-status-toggle-objective-set" name="objective_set" value="" type="hidden" />
                <div class="modal-header">
                    <button type="button" class="close clear-objective-status-toggle-fields" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h3 id="objective-status-toggle-h3-complete"><?php echo $translate->_("Update completion status to completed?"); ?></h3>
                    <h3 id="objective-status-toggle-h3-incomplete"><?php echo $translate->_("Update completion status to incomplete?"); ?></h3>
                </div>
                <div class="modal-body">
                    <div id="objective-status-toggle-msgs"></div>
                    <div id="objective-status-toggle-submission-wait" class="hide">
                        <img src="<?php echo ENTRADA_URL . "/images/loading.gif"; ?>"/>
                        <p id="objective-status-toggle-submission-wait-msg"></p>
                    </div>
                    <div id="objective-status-toggle-submission-body">
                        <label id="reason-label" for="objective-status-toggle-reason"><?php echo $translate->_("Reason:"); ?>
                            <span id="reason-label-optional"> (Optional)</span>
                        </label>
                        <textarea id="objective-status-toggle-reason" name="update_reason" class="full-width"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a id="epa-status-history-link" href="<?php echo ENTRADA_URL . "/assessments/learner/cbme/epastatushistory?proxy_id=" . $options["proxy_id"] . "&objective_id="; ?>" class="pull-left tiny-padding clear-objective-status-toggle-fields"><?php echo $translate->_("View History"); ?></a>
                        <a href="#" class="btn btn-default clear-objective-status-toggle-fields space-right" data-dismiss="modal"><?php echo $translate->_("Close"); ?></a>
                        <a href="#" id="objective-status-toggle-confirm" name="add-modal-task-confirm" class="btn btn-info pull-right"><?php echo $translate->_("Confirm"); ?></a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}