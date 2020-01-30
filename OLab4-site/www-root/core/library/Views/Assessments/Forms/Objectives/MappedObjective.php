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
class Views_Assessments_Forms_Objectives_MappedObjective extends Views_Assessments_Forms_Base {

    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("objective_id", "objective_code", "objective_name", "objective_description", "objective_metadata"));
    }

    protected function renderView($options = array()) {
        $title = ($options["objective_code"] ? $options["objective_code"] . ": " . $options["objective_name"] : $options["objective_name"]);
        $metadata = json_decode($options["objective_metadata"]);

        $breadcrumb = (isset($metadata->breadcrumb)) ? $metadata->breadcrumb : "";
        $tree_node_id = (isset($metadata->tree_node_id)) ? $metadata->tree_node_id : null;

        ?>
        <div class="mapped-objective mapped_objective_<?php echo $options["objective_id"]; ?>"
                name="mapped_objectives[<?php echo $options["objective_id"]; ?>]"
                data-id="<?php echo $options["objective_id"] ?>"
                data-title="<?php echo html_encode($title) ?>"
                data-description="<?php echo htmlentities($options["objective_description"]); ?>">
            <span class="objective-breadcrumb"><?php echo html_decode($breadcrumb); ?></span>
            <div class="assessment-item-objective-controls">
                <i class="fa fa-close pull-right remove-mapped-objective list-cancel-image" id="objective_remove_<?php echo $options["objective_id"]; ?>" data-id="<?php echo $options["objective_id"]; ?>" data-tree-id="<?php echo $tree_node_id; ?>"></i>
            </div>
            <strong class="objective-title">
                <?php echo html_encode($title); ?>
            </strong>

            <div class="objective-description">
                <?php //echo htmlentities($options["objective_description"]); ?>
            </div>

            <input type="hidden"
                   name="mapped_objective_ids[<?php echo $options["objective_id"]; ?>]"
                   value="<?php echo $options["objective_id"]; ?>"
                   class="mapped-objective mapped_objective_<?php echo $options["objective_id"]; ?>"
                   data-id="<?php echo $options["objective_id"] ?>"
            />

        <?php if ($tree_node_id): ?>
            <input type="hidden"
                   id="mapped_objective_tree_ids_<?php echo $tree_node_id; ?>"
                   name="mapped_objective_tree_ids_<?php echo $options["objective_id"]; ?>[]"
                   value="<?php echo $tree_node_id; ?>"
            />

            <input type="hidden"
                   name="mapped_objective_breadcrumbs[<?php echo $tree_node_id; ?>]"
                   value="<?php echo html_encode($breadcrumb); ?>"
            />
        <?php endif; ?>

        </div>
        <?php
    }
}


