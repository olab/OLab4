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
 * View for modal allowing users to delete form item responses.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Modals_DeleteItemResponse extends Views_Assessments_Forms_Base {

    protected function renderView($options = array()) {
        global $translate; ?>
        <div id="delete-item-response-modal" class="modal hide fade">
            <div class="modal-header">
                <h1><?php echo $translate->_("Delete Item Response"); ?></h1>
            </div>
            <div class="modal-body">
                <p><?php echo $translate->_("The selected item response will be removed."); ?></p>
                <span id="delete-item-response-text"></span>
            </div>
            <div class="modal-footer">
                <div class="row-fluid">
                    <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
                    <a href="#" class="btn btn-primary" id="delete-item-response-delete-btn" type="submit"><?php echo $translate->_("Delete"); ?></a>
                </div>
            </div>
        </div>
        <?php
    }
}
