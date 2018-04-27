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
        return $this->validateIsSet($options, array("action_url", "form_id", "fref", "rating_scale_types"));
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
        $rating_scale_types = $options["rating_scale_types"];
        ?>
        <div id="create-attach-rubric-modal" class="modal hide fade">
            <form id="create-attach-rubric-modal-form" class="form-horizontal no-margin" action="<?php echo $action_url; ?>" method="POST">
                <div class="modal-header"><h1><?php echo $translate->_("Create and Attach Grouped Item"); ?></h1></div>
                <div class="modal-body overflow-inherit">
                    <div id="create-attach-rubric-msgs"></div>
                    <div class="control-group">
                        <label class="control-label form-required" for="new-rubric-title"><?php echo $translate->_("New Grouped Item Title"); ?></label>
                        <div class="controls">
                            <input type="text" name="new-rubric-title" id="new-rubric-title" />
                        </div>
                    </div>
                    <?php
                    $scale_search = new Views_Assessments_Forms_Controls_ScaleSelectorSearch();
                    $scale_search->render(array(
                        "width" => 350,
                        "parent_selector" => "create-attach-rubric-modal-form",
                        "search_selector" => "item-rating-scale-btn",
                        "submodule" => "rubrics", // Doesn't necessarily have to be rubrics, but it just has to not be "scales"
                        "scale_type_datasource" => $rating_scale_types
                    )); ?>
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