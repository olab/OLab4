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
 * View for modal allowing users to copy items.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Modals_CopyAttachItem extends Views_Assessments_Forms_Base {

    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("action_url", "item_id"));
    }

    protected function renderView($options = array()) {
        global $translate;
        $action_url = $options["action_url"];
        $item_id = $options["item_id"];
        $prepopulate_text = @$options["prepopulate_text"];
        ?>
        <div id="copy-attach-item-modal" class="modal fade hide">
            <form class="form-horizontal no-margin" action="<?php echo $action_url ?>" method="POST">
                <div class="modal-header"><h1><?php echo $translate->_("Copy & Attach Item"); ?></h1></div>
                <div class="modal-body">
                    <div id="copy-attach-item-msgs"></div>
                    <div class="alert alert-warning">
                        <ul>
                            <li><?php echo $translate->_("This item will replace the one on the associated form with a new copy.") ?></li>
                        </ul>
                    </div>
                    <div class="control-group no-margin">
                        <label class="control-label form-required" for="new-item-title"><?php echo $translate->_("New Item Text"); ?></label>
                        <div class="controls">
                            <textarea name="new-copy-attached-item-title" id="new-copy-attached-item-title" class="expandable"><?php echo $prepopulate_text ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
                        <input type="submit" data-item-id="<?php echo $item_id ?>" class="btn btn-primary" id="copy-attach-item" value="<?php echo $translate->_("Copy & Attach Item"); ?>"/>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
}

