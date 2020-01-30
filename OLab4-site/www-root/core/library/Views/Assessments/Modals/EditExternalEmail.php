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
 * View class for rendering the edit external email modal
 *
 * @author Organization: Queen's University.
 * @author Developer: Jordan L <jl250@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Modals_EditExternalEmail extends Views_Assessments_Base
{
    /**
     * Render the modal view.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        global $translate;
        ?>
        <div class="modal fade" id="edit-external-modal" style="display: none">
            <form name="edit-external-modal-form" id="edit-external-modal-form" method="POST" action="#">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h3><?php echo $translate->_("Update External Email Address"); ?></h3>
                </div>
                <div class="modal-body">
                    <div id="edit-external-success" class="space-above space-below hide">
                        <?php echo display_success($translate->_("Successfully updated email.")) ?>
                    </div>
                    <div id="edit-external-error"></div>
                    <div id="edit-external-details-section">
                        <label for="edit-external-email"><?php echo $translate->_("External email:"); ?></label>
                        <input type="text" id="edit-external-email" class="input-xlarge" name="edit-external-email"/>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" id="edit-external-close-button" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Close"); ?></a>
                        <input type="button" id="save-external-email-confirm" class="btn btn-success pull-right" data-external-id="" name="save-external-email-confirm" value="<?php echo $translate->_("Save Email"); ?>"/>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
}