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
 * View for modal allowing users to remove rubric items from forms.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Modals_RemoveRubric extends Views_Assessments_Forms_Base {

    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("action_url", "rubric_id"));
    }

    protected function renderView($options = array()) {
        global $translate;
        $action_url = $options["action_url"];
        $rubric_id = $options["rubric_id"];
        ?>
        <div id="delete-rubric-item-modal" class="modal hide fade">
            <form id="delete-rubric-item-form-modal" class="form-horizontal no-margin" action="<?php echo $action_url ?>" method="POST">
                <input id="aritem_id" type="hidden" value=""/>
                <div class="modal-header"><h1><?php echo $translate->_("Remove Item"); ?></h1></div>
                <div class="modal-body">
                    <div id="rubric-item-selected">
                        <p><?php echo $translate->_("Are you sure you want to remove this attached Item?"); ?></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" id="delete-rubric-cancel" class="btn btn-default pull-left"
                           data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
                        <input type="submit" id="delete-rubric-item-modal-delete" class="btn btn-primary" data-rubric-id="<?php echo $rubric_id ?>" value="<?php echo $translate->_("Remove"); ?>"/>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
}

