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
 * Objective set selector renderer.
 *
 * @author Organization: Queen's University.
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Objectives_ObjectiveSetSelector extends Views_Assessments_Forms_Base {

    protected function validateOptions($options = array()) {
        if (!$this->validateArray($options, array("objective_sets"))) {
            return false;
        }
        return true;
    }

    protected function renderView($options = array()) {
        foreach ($options["objective_sets"] as $objective_set) {
            $title = ($objective_set["objective_code"]
                ? $objective_set["objective_code"] . ": " . $objective_set["objective_name"]
                : $objective_set["objective_name"]
            );
            ?>
            <div class="objective-set-container">
                <div class="objective-select objective-set<?php echo $objective_set["has_children"] ? " has-child-objectives child-objectives-hidden" : "" ?>"
                     id="objective-<?php echo $objective_set["objective_id"] ?>"
                     data-objective-id="<?php echo $objective_set["objective_id"] ?>"
                     data-objective-code="<?php echo $objective_set["objective_code"] ?>"
                     data-objective-title="<?php echo $objective_set["objective_name"] ?>"
                     data-node-id="<?php echo $objective_set["node_id"] ?>"
                     data-depth="<?php echo $objective_set["depth"] ?>"
                     data-course-id="<?php echo $objective_set["course_id"] ?>">
                    <span class="objective-set-title"><?php echo html_encode($title); ?></span>
                    <div class="child-objectives"></div>
                </div>
            </div>
            <?php
        }
    }
}


