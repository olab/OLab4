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
 * HTML view for an assessment form item. This view acts as a form item
 * view factory, instantiating item views based on the specified type.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Item extends Views_Assessments_Forms_Base {

    protected function validateOptions($options = array()) {
        if (!$this->validateIsSet($options, array("itemtype_shortname"))) {
            return false;
        }
        return true;
    }

    protected function renderView($options = array()) {

        $mode = $this->getMode();

        $afelement_id = @$options["afelement_id"];

        switch ($options["itemtype_shortname"]) {

            case "rubric_line":
            case "horizontal_multiple_choice_single":
            case "scale":
                $view_item = new Views_Assessments_Forms_Items_HorizontalMultipleChoiceSingle(array("mode" => $mode));
                break;

            case "vertical_multiple_choice_single":
                $view_item = new Views_Assessments_Forms_Items_VerticalMultipleChoiceSingle(array("mode" => $mode));
                break;

            case "selectbox_single":
                $view_item = new Views_Assessments_Forms_Items_SelectboxSingle(array("mode" => $mode));
                break;

            case "horizontal_multiple_choice_multiple":
                $view_item = new Views_Assessments_Forms_Items_HorizontalMultipleChoiceMultiple(array("mode" => $mode));
                break;

            case "vertical_multiple_choice_multiple":
                $view_item = new Views_Assessments_Forms_Items_VerticalMultipleChoiceMultiple(array("mode" => $mode));
                break;

            case "selectbox_multiple":
                $view_item = new Views_Assessments_Forms_Items_SelectboxMultiple(array("mode" => $mode));
                break;

            case "free_text":
                $view_item = new Views_Assessments_Forms_Items_FreeTextComment(array("mode" => $mode));
                break;

            case "date":
                $view_item = new Views_Assessments_Forms_Items_Date(array("mode" => $mode));
                break;

            case "numeric":
                $view_item = new Views_Assessments_Forms_Items_Numeric(array("mode" => $mode));
                break;

            case "user":
                $view_item = new Views_Assessments_Forms_Items_User(array("mode" => $mode));
                break;

            case "fieldnote":
                $view_item = new Views_Assessments_Forms_Items_FieldNote(array("mode" => $mode));
                break;

            default:
                // Other types are not supported
                $view_item = null;
                break;
        }

        if ($afelement_id) {
            // Wrap in an afelement ID container div
            echo "<div class='form-item' data-afelement-id='$afelement_id'>";
        }

        // Render the view item
        if ($view_item) {
            $view_item->render($options);
        }

        if ($afelement_id) {
            // Wrap in an afelement ID container div
            echo "</div>";
        }

    }

    /**
     * Render a generic error message.
     */
    protected function renderError() {
        global $translate;?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render item"); ?></strong>
        </div>
        <?php
    }

}