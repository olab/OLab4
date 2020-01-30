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
 * View for modal allowing users to delete form items.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Modals_RemoveFormItems extends Views_Assessments_Forms_Base {

    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("action_url"));
    }

    protected function renderView($options = array()) {
        global $translate; ?>
        <div id="delete-form-items-modal" class="modal hide fade">
            <form id="delete-form-items-modal-form" class="form-horizontal no-margin" action="<?php echo $options["action_url"]; ?>" method="POST">
                <input type="hidden" name="step" value="2"/>
                <div class="modal-header"><h1><?php echo $translate->_("Remove Items"); ?></h1></div>
                <div class="modal-body">
                    <div id="no-form-items-selected" class="hide">
                        <?php echo display_notice($translate->_("No forms items selected to delete.")); ?>
                    </div>
                    <div id="form-items-selected" class="hide">
                        <?php echo display_notice($translate->_("Please confirm you would like to remove the selected <span></span> Form Item(s).")); ?>
                    </div>
                    <div id="form-items-success" class="hide">
                        <?php echo display_success($translate->_("You have successfully removed the selected <span></span> Form Item(s).")); ?>
                    </div>
                    <div id="form-items-error" class="hide">
                        <?php echo display_error($translate->_("Unfortunately, an error was encountered while attempting to remove the selected <span></span> Form Item(s).")); ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Close"); ?></a>
                        <input id="delete-form-items-modal-delete" type="submit" class="btn btn-primary" value="<?php echo $translate->_("Delete"); ?>"/>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
}
