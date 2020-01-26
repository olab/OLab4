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
 * Modal for getting PIN input.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Modals_EnterPIN extends Views_HTML {

    /**
     * Validate our options array.
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("pin_assessor_id"));
    }

    /**
     * Render default error
     */
    protected function renderError() {
        global $translate;
        ?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render PIN Modal"); ?></strong>
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
        <div class="modal fade text-center" id="modal-enter-pin" style="display: none">
            <div class="form-horizontal assessor-pin-modal-container">
                <input id="pin-assessor-id" name="pin_assessor_id" value="<?php echo $options["pin_assessor_id"] ?>" type="hidden" class="hide" />
                <div class="modal-header">
                    <button type="button" class="close clear-password-fields" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h3><?php echo $translate->_("Enter Your PIN"); ?></h3>
                </div>
                <div class="modal-body">
                    <div id="pin-msgs"></div>
                    <div id="pin-submission-wait" class="hide">
                        <img src="<?php echo ENTRADA_URL . "/images/loading.gif"; ?>"/>
                        <p id="pin-submission-wait-msg"></p>
                    </div>
                    <div id="pin-submission-body">
                        <p><?php echo $translate->_("Please enter your PIN number to confirm submission:"); ?></p>
                        <input type="password" placeholder="<?php echo $translate->_("Enter your PIN")?>" name="pin_assessor_pin" id="pin-assessor-pin" value="" autocomplete="off"/>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" class="btn btn-default pull-left clear-password-fields" data-dismiss="modal"><?php echo $translate->_("Close"); ?></a>
                        <a href="#" id="assessment-enter-pin-confirm" name="add-modal-task-confirm" class="btn btn-info pull-right"><?php echo $translate->_("Confirm PIN"); ?></a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}