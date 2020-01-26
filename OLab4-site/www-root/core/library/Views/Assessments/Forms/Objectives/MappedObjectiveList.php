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
 * Selected objective list renderer.
 *
 * @author Organization: Queen's University.
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Forms_Objectives_MappedObjectiveList extends Views_Assessments_Forms_Base {

    protected function validateOptions($options = array()) {
        if (!$this->validateArray($options, array("objectives"))) {
            return false;
        }
        return true;
    }

    protected function renderView($options = array()) {
        global $translate;
        ?>
        <div id="assessment-item-list-wrapper" class="pull-right">
            <a name="assessment-item-objective-list"></a>
            <h2 id="assessment-item-toggle" title="<?php echo $translate->_("Assessment Item Objective List"); ?>" class="list-heading nocollapse"><?php echo $translate->_("Associated Curriculum Tags"); ?></h2>
            <div id="assessment-item-objective-list">
                <div class="objective-list mapped-list mapped_assessment_item_objectives">
                    <?php if (!empty($options["objectives"])) :
                        foreach ($options["objectives"] as $objective) :
                            $mapped_objective_view = new Views_Assessments_Forms_Objectives_MappedObjective();
                            $mapped_objective_view->render($objective);
                        endforeach;
                    endif; ?>
                </div>
                <div class="objectives-empty-notice<?php echo !empty($options["objectives"]) ? " hide" : ""; ?>">
                    <div id="display-notice-box" class="alert alert-block">
                        <ul>
                            <li><?php echo $translate->_("There are no curriculum tags currently linked to this item."); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}


