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
 * View class for adding a new objective to a form.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Modals_AddObjective extends Views_Assessments_Forms_Base {

    protected function validateOptions($options = array()) {
        if (!$this->validateIsSet($options, array("objective_ids", "organisation_id"))) {
            return false;
        }
        return true;
    }

    /**
     * Render view
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $objective_ids = $options["objective_ids"];
        $item_org_id = $options["organisation_id"];
        ?>
        <div id="objective-modal" class="modal hide fade">
            <div class="modal-header"><h1><?php echo $translate->_("Curriculum Tags"); ?></h1></div>
            <div class="modal-body">
                <?php echo Views_Deprecated_Objective::renderObjectiveControls($item_org_id, 1, $objective_ids); // TODO: Move this to non-deprecated version ?>
            </div>
            <div class="modal-footer">
                <div class="row-fluid">
                    <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Done"); ?></a>
                </div>
            </div>
        </div>
        <?php
   }
}