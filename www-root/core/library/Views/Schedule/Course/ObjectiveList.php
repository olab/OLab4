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
 * View class for rendering a table of rotation course objectives.
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Belanger <jb301@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Schedule_Course_ObjectiveList extends Views_HTML {

    protected function validateOptions($options = array()) {
        if (!$this->validateIsSet(
            $options,
            array(
                "title",
                "mapped_percentage",
                "course_id",
                "schedule_id"
            )
        )) {
            return false;
        }
        if (!$this->validateArray(
            $options,
            array(
                "objectives",
                "likelihood_datasource"
            )
        )) {
            return false;
        }
        return true;
    }

    protected function renderView($options = array()) {
        global $translate;

        // Overall list options.
        $id = array_key_exists("id", $options) ? $options["id"] : "rotation-objectives-table";
        $class = array_key_exists("class", $options) ? $options["class"] : "table table-bordered";

        // Card specific options.
        $objective_list_class = array_key_exists("objective_list_class", $options) && $options["class"] ? $options["objective_list_class"] : null;
        $no_results = array_key_exists("no_results_label", $options) ? $options["no_results_label"] : $translate->_("No objectives.");
        $progress_label = array_key_exists("progress_bar_label", $options) ? $options["progress_bar_label"] : $translate->_("Mapped objectives progress");

        ?>

        <h2 class="title"><?php echo html_encode($options["title"]); ?></h2>


        <?php if (sizeof($options["objectives"]) > 0): ?>
            <div class="alert alert-info text-center">
                <?php echo $translate->_("Changes to EPA counts and badges will be reflected upon refreshing the page."); ?>
            </div>

            <div id="objectives-mapping-results" class="hide"></div>

            <div class="row-fluid clear">
                <strong class="objective-mapping-progress-message">
                    <?php echo html_encode($progress_label); ?>
                </strong>
                <div class="progress">
                    <div class="bar" style="width: <?php echo $options["mapped_percentage"]; ?>%;"></div>
                </div>
            </div>

            <table<?php echo ($id ? " id=\"{$id}\"" : "") . ($class ? " class=\"{$class}\"" : ""); ?>>

                <thead>
                <tr>
                    <th>&nbsp;</th>
                    <th><?php echo $translate->_("Objective") ?></th>
                    <th><?php echo $translate->_("Priority") ?></th>
                    <th><?php echo $translate->_("Likelihood") ?></th>
                </tr>
                </thead>

                <tbody>
                <?php
                foreach ($options["objectives"] as $key => $course_objective):

                    $course_objective["id"] = $course_objective["objective_id"];
                    $course_objective["class"] = $objective_list_class;

                    $course_objective["likelihood_datasource"] = $options["likelihood_datasource"];

                    $course_objective["schedule_id"] = $options["schedule_id"];

                    $card = new Views_Schedule_Course_Objective(array());
                    $card->render($course_objective);
                endforeach; ?>
                </tbody>

            </table>
            <?php
        else: ?>
            <div class="alert alert-danger text-center space-top">
                <h3><?php echo html_encode($no_results); ?></h3>
            </div>
        <?php endif; ?>

        <?php
    }

}