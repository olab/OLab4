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

class Views_Assessments_Forms_Templates_ScaleResponseRow extends Views_Assessments_Forms_Base {

    protected function renderView($options = array()) {
        global $translate; ?>
        <script type="text/html" id="response-row-template">
            <td class="text-center">
                <label data-template-bind='[{"attribute": "for", "value":"tpl_response_element_id"}]'
                       data-content="tpl_response_label"
                       class="item-response-label form-required"></label>
            </td>
            <td class="entrada-search-widget">
                <input type="hidden"
                       class="hide"
                       data-template-bind='
                           [{"attribute": "name",       "value" : "tpl_response_hidden_input_name"},
                            {"attribute": "id",         "value" : "tpl_response_hidden_input_id"},
                            {"attribute": "data-label", "value" : "tpl_response_hidden_input_label"},
                            {"attribute": "value",      "value" : "tpl_response_hidden_input_value"}]'
                />
                <button data-template-bind='[{"attribute": "name", "value": "tpl_ardescriptor_name"}]'
                         data-id="tpl_descriptor_id"
                         class="btn btn-search-filter text-left"><?php echo $translate->_("Browse Descriptors"); ?><i class="icon-chevron-down btn-icon pull-right"></i>
                </button>
            </td>
        </script>
        <?php
    }
}

