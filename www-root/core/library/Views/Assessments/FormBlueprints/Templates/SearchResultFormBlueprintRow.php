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
 * JavaScript template file for the forms index page AJAX search results.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_FormBlueprints_Templates_SearchResultFormBlueprintRow extends Views_Assessments_Forms_Base {

    protected function renderView($options = array()) {
        ?>
        <script id="form-blueprint-search-result-row-template" type="text/html">
            <td>
                <input data-value="tpl_formblueprint_id" class="add-form" name="forms[]" type="checkbox">
            </td>
            <td>
                <a data-template-bind='[{"attribute": "href", "value": "tpl_formblueprint_url"}, {"attribute": "target", "value": "tpl_url_target"}]' data-id="tpl_formblueprint_link_id" data-content="tpl_formblueprint_title"></a>
            </td>
            <td>
                <a data-template-bind='[{"attribute": "href", "value": "tpl_formblueprint_url"}, {"attribute": "target", "value": "tpl_url_target"}]' data-content="tpl_formblueprint_created"></a>
            </td>
            <td>
                <a data-template-bind='[{"attribute": "href", "value": "tpl_formblueprint_url"}, {"attribute": "target", "value": "tpl_url_target"}]' data-content="tpl_formblueprint_type"></a>
            </td>
            <td>
                <a data-template-bind='[{"attribute": "href", "value": "tpl_formblueprint_url"}, {"attribute": "target", "value": "tpl_url_target"}]' data-content="tpl_formblueprint_item_count"></a>
            </td>
        </script>
        <?php
    }
}

