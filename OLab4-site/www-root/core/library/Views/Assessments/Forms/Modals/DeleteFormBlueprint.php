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
 * View for modal allowing users to delete form blueprints.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Modals_DeleteFormBlueprint extends Views_Assessments_Forms_Base {

    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("action_url"));
    }

    protected function renderView($options = array()) {
        global $translate;
        $action_url = $options["action_url"]; ?>
        <div id="delete-form-blueprint-modal" class="modal hide fade">
            <form id="delete-form-blueprint-modal-form" class="form-horizontal no-margin" action="<?php echo $action_url; ?>" method="POST">
                <input type="hidden" name="step" value="2" />
                <div class="modal-header"><h1><?php echo $translate->_("Delete Form Blueprints"); ?></h1></div>
                <div class="modal-body">
                    <div id="no-form-blueprints-selected" class="hide">
                        <p><?php echo $translate->_("No blueprints selected to delete."); ?></p>
                    </div>
                    <div id="form-blueprints-selected" class="hide">
                        <p><?php echo $translate->_("Please confirm you would like to delete the selected template(s)"); ?></p>
                        <div id="delete-form-blueprints-container"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
                        <input id="delete-form-blueprints-modal-delete" type="submit" class="btn btn-primary" value="<?php echo $translate->_("Delete"); ?>" />
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
}
