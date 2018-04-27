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
 * View class for rendering feedback capture elements on a form.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Controls_ElementHeaderControls extends Views_Assessments_Forms_Controls_Base {

    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("itemtype_shortname", "element_id"));
    }

    protected function renderError() {
        global $translate; ?>
        <div class="btn-group">
            <span class="btn alert-danger"><?php echo $translate->_("Unable to render form item controls"); ?></span>
        </div>
        <?php
    }

    /**
     * Render view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {

        $controls_type    = $options["itemtype_shortname"];
        $element_id       = $options["element_id"];
        $only_edit_pencil = Entrada_Utilities::arrayValueOrDefault($options, "only_edit_pencil", false);
        $form_id          = Entrada_Utilities::arrayValueOrDefault($options, "form_id");
        $afelement_id     = Entrada_Utilities::arrayValueOrDefault($options, "afelement_id");
        $attributes       = Entrada_Utilities::arrayValueOrDefault($options, "attributes", array());
        $referrer         = Entrada_Utilities::arrayValueOrDefault($options, "referrer_hash");
        $item_is_deleted  = Entrada_Utilities::arrayValueOrDefault($options, "deleted_date", false) ? true : false;
        $edit_url         = Entrada_Utilities_FormStorageSessionHelper::buildRefURL(ENTRADA_URL . "/admin/assessments/items?section=edit-item&item_id=$element_id", $referrer);

        switch ($controls_type) {
            case "horizontal_multiple_choice_single":
            case "vertical_multiple_choice_single":
            case "horizontal_multiple_choice_multiple":
            case "vertical_multiple_choice_multiple":
            case "selectbox_single":
            case "selectbox_multiple":
            case "numeric":
            case "date":
            case "scale":
            case "free_text":
            case "rubric_line":
            case "individual":
                $this->renderDefaultControlBar($form_id, $afelement_id, $edit_url, $item_is_deleted, $only_edit_pencil, $attributes);
                break;
            default:
                $this->renderError();
                break;
        }
    }

    private function renderDefaultControlBar($form_id, $afelement_id, $edit_url, $item_is_deleted = false, $only_edit_pencil = false, $attributes = array()) {
        global $translate;
        $disable_edit = false;
        $disable_selection_checkbox = false;
        $source_objective_id = null;
        $source_form_id = null;
        $source_type = null;
        $source_rating_scale_id = null;
        $source_comment_type = null;
        $source_flagged_descriptors = array();
        $source_item_text = null;

        if (is_array($attributes)) {
            $source_form_id = Entrada_Utilities::arrayValueOrDefault($attributes, "source_form");
            $source_objective_id = Entrada_Utilities::arrayValueOrDefault($attributes, "source_objective");
            $mutators = Entrada_Utilities::arrayValueOrDefault($attributes, "mutators", array());
            $source_type = Entrada_Utilities::arrayValueOrDefault($attributes, "source_type");
            $source_rating_scale_id = Entrada_Utilities::arrayValueOrDefault($attributes, "source_rating_scale_id");
            $source_comment_type = Entrada_Utilities::arrayValueOrDefault($attributes, "source_comment_type");
            $source_flagged_descriptors = Entrada_Utilities::arrayValueOrDefault($attributes, "source_flagged_descriptors", array());
            $source_item_text = Entrada_Utilities::arrayValueOrDefault($attributes, "source_item_text");
            
            $disable_edit = in_array("disable_header_edit", $mutators); // We always obey the disable edit mutator if present, regardless of context.
            $disable_selection_checkbox = in_array("disable_header_selection", $mutators); // We only disable the selection checkbox if the source form ID matches
            if ($disable_selection_checkbox) {
                if ($source_form_id && $source_form_id == $form_id) {
                    $disable_selection_checkbox = true;
                } else {
                    $disable_selection_checkbox = false;
                }
            }
        }
        ?>
        <?php if ($item_is_deleted):?>
            <div class="btn-group">
                <span class="rubric-header-warning-text">
                    <?php echo $translate->_("This item has been deleted and can not be edited."); ?>
                </span>
            </div>
        <?php endif; ?>

        <?php if ($only_edit_pencil): ?>
            <div class="btn-group">
                <?php if (!$item_is_deleted && !$disable_edit):?>
                    <a class="btn edit-item" title="<?php echo $translate->_("Edit Item"); ?>" href="<?php echo $edit_url ?>">
                        <i class="icon-pencil"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php if (!$disable_selection_checkbox): ?>
                <div class="btn-group">
                    <span class="btn"><input type="checkbox" value="<?php echo $afelement_id; ?>" name="delete[]" class="delete"></span>
                </div>
            <?php endif; ?>
            <div class="btn-group">
                <?php if (!$item_is_deleted && !$disable_edit): ?>
                    <a class="btn edit-item" title="<?php echo $translate->_("Edit Item"); ?>" href="<?php echo $edit_url ?>">
                        <i class="icon-pencil"></i>
                    </a>
                    <a class="btn item-details" title="<?php echo $translate->_("View Item Details"); ?>" href="#">
                        <i class="icon-eye-open"></i>
                    </a>
                <?php endif; ?>
                <a class="btn always-enabled" id="move-form-item" title="<?php echo $translate->_("Move"); ?>"><i class="icon-move"></i></a>
            </div>
        <?php endif;

        $source_form_tag = ($source_form_id) ? "data-source-form-id=\"" . $source_form_id . "\"" : "";
        if ($source_objective_id): ?>
            <input type="hidden" <?php echo $source_form_tag; ?> class="form-item-source-objective" value="<?php echo $source_objective_id; ?>" />
        <?php endif;
        if ($source_type): ?>
            <input type="hidden" <?php echo $source_form_tag; ?> class="form-item-source-type" value="<?php echo $source_type; ?>" />
        <?php endif;
        if ($source_rating_scale_id): ?>
            <input type="hidden" <?php echo $source_form_tag; ?> class="form-item-source-rating-scale" value="<?php echo $source_rating_scale_id; ?>" />
        <?php endif;
        if ($source_comment_type): ?>
            <input type="hidden" <?php echo $source_form_tag; ?> class="form-item-source-comment-type" value="<?php echo $source_comment_type; ?>" />
        <?php endif;
        if (count($source_flagged_descriptors)):
            foreach ($source_flagged_descriptors as $source_flagged_descriptor) : ?>
                <input type="hidden" <?php echo $source_form_tag; ?> class="form-item-source-flagged-descriptor" value="<?php echo $source_flagged_descriptor; ?>" />
            <?php endforeach;
        endif;
        if ($source_item_text): ?>
            <input type="hidden" <?php echo $source_form_tag; ?> class="form-item-source-item-text" value="<?php echo $source_item_text; ?>" />
        <?php endif;
    }
}