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
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Modals_CreateAttachRubric extends Views_Assessments_Forms_Base {

    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("action_url", "form_id", "fref"));
    }

    /**
     * Render view
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $action_url = $options["action_url"];
        $form_id = $options["form_id"];
        $fref = $options["fref"];
        ?>
        <div id="create-attach-rubric-modal" class="modal hide fade">
            <form class="form-horizontal no-margin" action="<?php echo $action_url; ?>" method="POST">
                <div class="modal-header"><h1><?php echo $translate->_("Create and Attach Grouped Item"); ?></h1></div>
                <div class="modal-body">
                    <div id="create-attach-rubric-msgs"></div>
                    <div class="control-group no-margin">
                        <label class="control-label form-required" for="new-rubric-title"><?php echo $translate->_("New Grouped Item Title"); ?></label>
                        <div class="controls">
                            <input type="text" name="new-rubric-title" id="new-rubric-title" />
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
                        <input type="submit" data-form-id="<?php echo $form_id ?>" data-fref="<?php echo $fref ?>" class="btn btn-primary" id="create-attach-rubric" value="<?php echo $translate->_("Create and Attach"); ?>" />
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
}