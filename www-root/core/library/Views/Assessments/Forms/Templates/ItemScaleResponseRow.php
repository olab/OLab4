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

class Views_Assessments_Forms_Templates_ItemScaleResponseRow extends Views_Assessments_Forms_Base {
    protected function validateOptions($options = array()) {
        if (!$this->validateIsSet($options, array("custom_flags"))) {
            return false;
        }

        return true;
    }

    protected function renderView($options = array()) {
        global $translate;
        $custom_flags = $options["custom_flags"];
        ?>
        <script type="text/html" id="itemscale-response-row-template">
            <td>
                <label data-template-bind='[{"attribute": "for", "value":"tpl_response_element_id"}]'
                       data-content="tpl_response_label"
                       class="item-response-label form-required"></label>
            </td>
            <td>
                <textarea data-template-bind='[{"attribute": "name", "value": "tpl_item_responses_name"}]'
                          data-id="tpl_response_element_id"
                          class="response-input expandable"></textarea>
            </td>
            <td class="entrada-search-widget">
                <a href="#" class="btn disabled full-width no-padding-right no-padding-left" data-content="tpl_ardescriptor_value"></a>
                <input data-template-bind='[{"attribute": "name", "value":"tpl_ardescriptor_name"},{"attribute": "value", "value":"tpl_ardescriptor_id_value"}]' type="hidden">
                <input data-template-bind='[{"attribute": "name", "value":"tpl_selected_ardescriptor_name"},{"attribute": "value", "value":"tpl_selected_ardescriptor_id"}]' type="hidden">
            </td>
            <td>
                <?php if ($custom_flags): ?>
                    <button data-id="tpl_flag_id" class="btn btn-search-filter text-left">
                        <?php echo $translate->_("Not Flagged"); ?><i class="icon-chevron-down btn-icon pull-right"></i>
                    </button>
                <?php else: ?>
                    <input data-template-bind='[{"attribute": "name", "value":"tpl_flag_response"}]' type="checkbox"  value="1">
                <?php endif; ?>
            </td>
            <td class="default_selection_column hide">
                <input data-template-bind='[{"attribute": "name", "value": "tpl_default_response"}]'
                       data-value="tpl_response_number"
                       type="radio">
            </td>
            <td class="hide">
                &nbsp;
            </td>
            <td class="hide">
                &nbsp;
            </td>
        </script>
        <?php
    }
}

