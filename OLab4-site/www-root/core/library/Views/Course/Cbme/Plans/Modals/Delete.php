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
 * View class for rendering the assessment plan exporter
 *
 * @author Organization: Queen's University.
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Course_Cbme_Plans_Modals_Delete extends Views_HTML {
    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("action_url", "heading_text", "delete_confirmation_text"));
    }

    protected function renderView($options = array()) {
        global $translate;
        $action_url = $options["action_url"]; ?>
        <div id="delete-plan-modal" class="modal fade" style="display:none;">
            <form id="delete-plan-form" class="form-horizontal no-margin" action="<?php echo $action_url; ?>" method="POST">
                <input type="hidden" name="step" value="2" />
                <div class="modal-header"><h1><?php echo $options["heading_text"]; ?></h1></div>
                <div class="modal-body">
                    <div id="no-plans-selected" class="hide">
                        <p><?php echo $translate->_("No assessment plans selected to delete."); ?></p>
                    </div>
                    <div id="plans-selected" class="hide">
                        <p><?php echo $options["delete_confirmation_text"] ?></p>
                        <ul id="delete-plans-list"></ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
                        <input id="delete-forms-modal-delete" type="submit" name="remove_plan" class="btn btn-danger" value="<?php echo $translate->_("Delete"); ?>" />
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
}