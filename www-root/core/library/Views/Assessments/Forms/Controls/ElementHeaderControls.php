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
        $controls_type   = $options["itemtype_shortname"];
        $element_id      = $options["element_id"];
        $afelement_id    = $options["afelement_id"];
        $referrer        = @$options["referrer_hash"];
        $item_is_deleted = @$options["deleted_date"] ? true : false;
        $edit_url        = Entrada_Utilities_FormStorageSessionHelper::buildRefURL(ENTRADA_URL . "/admin/assessments/items?section=edit-item&item_id=$element_id", $referrer);

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
                $this->renderDefaultControlBar($afelement_id, $edit_url, $item_is_deleted);
                break;
            default:
                $this->renderError();
                break;
        }
    }

    private function renderDefaultControlBar($afelement_id, $edit_url, $item_is_deleted = false) {
        global $translate; ?>

        <?php if ($item_is_deleted):?>
            <div class="btn-group">
                <span class="rubric-header-warning-text">
                    <?php echo $translate->_("This item has been deleted and can not be edited."); ?>
                </span>
            </div>
        <?php endif; ?>

        <div class="btn-group">
            <span class="btn"><input type="checkbox" value="<?php echo $afelement_id; ?>" name="delete[]" class="delete"></span>
        </div>
        <div class="btn-group">
            <?php if (!$item_is_deleted):?>
                <a class="btn edit-item" title="<?php echo $translate->_("Edit Item"); ?>" href="<?php echo $edit_url ?>">
                    <i class="icon-pencil"></i>
                </a>
                <a class="btn item-details" title="<?php echo $translate->_("View Item Details"); ?>" href="#">
                    <i class="icon-eye-open"></i>
                </a>
            <?php endif; ?>

            <a class="btn move" title="<?php echo $translate->_("Move"); ?>" href="#">
                <i class="icon-move"></i>
            </a>
        </div>
        <?php
    }
}