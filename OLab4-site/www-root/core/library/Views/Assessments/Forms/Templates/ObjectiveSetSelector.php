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
 * Objective set selector render template.
 *
 * @author Organization: Queen's University.
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Forms_Templates_ObjectiveSetSelector extends Views_Assessments_Forms_Base {

    protected function validateOptions($options = array()) {
        return true;
    }

    protected function renderView($options = array()) {
        ?>
        <script type="text/html" id="objective-set-template" class="objective-set-container">
            <div class="objective-select objective-set"
                 data-template-bind='[
                        {"attribute": "id", "value": "tpl_objective_id"},
                        {"attribute": "data-node-id", "value": "tpl_objective_node_id"},
                        {"attribute": "data-objective-id", "value": "tpl_objective_id"},
                        {"attribute": "data-objective-code", "value": "tpl_objective_code"},
                        {"attribute": "data-objective-title", "value": "tpl_objective_title"},
                        {"attribute": "data-depth", "value": "tpl_objective_depth"},
                        {"attribute": "data-course-id", "value": "tpl_objective_course_id"}
                    ]'>
                <span class="objective-set-title" data-content="tpl_objective_title"></span>
                <div class="child-objectives"></div>
            </div>
        </script>
        <?php
    }
}

