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
 * View for modal allowing users to delete rubric items.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Modals_CopyRubric extends Views_Assessments_Forms_Base {

    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("action_url", "rubric_id"));
    }

    protected function renderView($options = array()) {
        global $translate;
        $action_url = $options["action_url"];
        $rubric_id = $options["rubric_id"];
        $form_id = @$options["form_id"] ? (int)$options["form_id"] : 0;
        $prepopulated_text = @$options["prepopulated_text"];
        $copy_and_attach = @$options["copy_and_attach"];
        $has_deleted_items = @$options["contains_deleted_items"];
        $copy_text = ($copy_and_attach) ? $translate->_("Copy & Attach Grouped Item") : $translate->_("Copy Grouped Item");
        ?>
        <div id="copy-rubric-modal" class="modal hide fade">
            <form class="form-horizontal no-margin" action="<?php echo $action_url ?>" method="POST">
                <div class="modal-header">
                    <h1><?php echo $copy_text; ?></h1>
                </div>
                <div class="modal-body">
                    <?php if ($copy_and_attach): ?>
                        <div id="display-notice-box" class="alert alert-block">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <ul>
                                <li><?php echo $translate->_("A copy of this rubric will be created and attached to the associated form, replacing the existing version."); ?></li>
                                <?php if ($has_deleted_items): ?>
                                    <li class="space-above"><?php echo $translate->_("Any deleted items will not be copied to the new version."); ?></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php else: ?>
                        <?php if ($has_deleted_items): ?>
                            <div id="display-notice-box" class="alert alert-block">
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                                <ul>
                                    <li><?php echo $translate->_("A copy of this rubric will be created. The deleted items that are part of this rubric will not be included in the copy."); ?></li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <div id="copy-rubric-msgs"></div>
                    <div class="control-group no-margin">
                        <label class="control-label form-required" for="new-rubric-title"><?php echo $translate->_("New Grouped Item Title"); ?></label>
                        <div class="controls">
                            <input type="text" name="new-rubric-title" id="new-rubric-title" value="<?php echo $prepopulated_text ?>"/>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
                        <input type="submit"
                               data-form-id="<?php echo $form_id ?>"
                               data-rubric-id="<?php echo $rubric_id ?>"
                               class="btn btn-primary"
                               id="<?php echo $copy_and_attach ? "copy-attach-rubric" : "copy-rubric"; ?>"
                               value="<?php echo $copy_text; ?>"
                        />
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
}

