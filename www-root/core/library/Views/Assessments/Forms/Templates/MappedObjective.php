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
 * Selected objective renderer.
 *
 * @author Organization: Queen's University.
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Templates_MappedObjective extends Views_Assessments_Forms_Base {

    protected function validateOptions($options = array()) {
        return true;
    }

    protected function renderView($options = array()) {
        ?>
        <script type="text/html" id="mapped-objective-template" class="mapped-objective-container">
            <div data-template-bind='[
                    {"attribute": "data-id", "value": "tpl_objective_id"},
                    {"attribute": "name", "value": "tpl_objective_name"},
                    {"attribute": "class", "value": "tpl_objective_class"},
                    {"attribute": "data-title", "value": "tpl_objective_title"},
                    {"attribute": "data-description", "value": "tpl_objective_description"}
                 ]'>

                <span class="objective-breadcrumb" data-content="tpl_objective_breadcrumb"></span>

                <div class="assessment-item-objective-controls">
                    <i class="fa fa-close pull-right remove-mapped-objective list-cancel-image"
                       data-template-bind='[
                            {"attribute": "data-id", "value": "tpl_objective_id"},
                            {"attribute": "data-tree-id", "value": "tpl_objective_tree_id"}
                        ]'></i>
                </div>

                <strong class="objective-title" data-content="tpl_objective_title"></strong>

                <div class="objective-description" data-content="tpl_objective_description"></div>

                <input type="hidden" data-template-bind='[
                        {"attribute": "data-id", "value": "tpl_objective_id"},
                        {"attribute": "value", "value": "tpl_objective_id"},
                        {"attribute": "name", "value": "tpl_objective_name"},
                        {"attribute": "class", "value": "tpl_objective_class"}
                    ]'
                />

                <input type="hidden" data-template-bind='[
                        {"attribute": "id", "value": "tpl_node_input_id"},
                        {"attribute": "value", "value": "tpl_objective_tree_id"},
                        {"attribute": "name", "value": "tpl_objective_tree_name"}
                    ]'
                />

                <input type="hidden" data-template-bind='[
                        {"attribute": "value", "value": "tpl_objective_breadcrumb"},
                        {"attribute": "name", "value": "tpl_breadcrumb_input_name"}
                    ]'
                />
            </div>
        </script>
        <?php
    }
}


