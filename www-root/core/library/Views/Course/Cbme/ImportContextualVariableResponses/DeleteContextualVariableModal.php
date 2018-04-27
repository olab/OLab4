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
 * View class for rendering the Contextual Variable Response deletion modal
 *
 * @author Organization: Queen's University.
 * @author Developer: Jordan L <jl250@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_Course_Cbme_ImportContextualVariableResponses_DeleteContextualVariableModal extends Views_Assessments_Base {

    protected function validateOptions($options = array()) {
        return true;
    }

    protected function renderView($options = array()) {
        global $translate; ?>
        <div class="modal hide fade" id="delete-contextual-variable-response-modal" data-backdrop="static" data-keyboard="false">
            <form id="delete-contextual-variable-response-modal-form">
                <input type="hidden" id="hidden-objective-id"/>
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h3><?php echo $translate->_("Contextual Variable Response"); ?></h3>
                </div>
                <div class="modal-body">
                    <strong><?php echo $translate->_("Please confirm that you want to delete this Contextual Variable Response."); ?></strong>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" id="delete-contextual-variable-response-close-button" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Close"); ?></a>
                        <a href="#" id="delete-contextual-variable-response-modal-confirm" class="btn btn-danger pull-right"><?php echo $translate->_("Confirm Deletion"); ?></a>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
}