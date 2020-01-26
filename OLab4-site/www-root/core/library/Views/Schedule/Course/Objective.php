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
 * View class for rendering a row for a rotation objective.
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Belanger <jb301@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Schedule_Course_Objective extends Views_HTML {

    protected function validateOptions($options = array()) {
        if (!$this->validateIsSet(
            $options,
            array(
                "objective_id",
                "objective_name",
                "objective_code",
                "course_id",
                "schedule_id",
                "mapped",
                "selected_likelihood_id",
                "selected_likelihood_title"
            )
        )) {
            return false;
        }
        if (!$this->validateArray(
            $options,
            array(
                "rotations",
                "likelihood_datasource"
            )
        )) {
            return false;
        }
        return true;
    }

    protected function renderView($options = array()) {
        global $translate;
        $title = $options["objective_code"] ? $options["objective_code"] : $options["objective_name"];

        ?>
        <tr class="rotation-objective-row <?php echo $options["mapped"] ? "objective-mapped" : "objective-unmapped"; ?>"
            data-objective-id="<?php echo html_encode($options["objective_id"]); ?>">

            <td class="rotation-mapped-cell">
                <?php
                if ($options["mapped"]) {
                    $tooltip = $translate->_("Click to Un-Map this Objective");
                } else {
                    $tooltip = $translate->_("Click to Map this Objective");
                }
                ?>
                <button data-objective-id="<?php echo html_encode($options["objective_id"]); ?>"
                        class="btn map-objective-button<?php echo $options["mapped"] ? " objective-mapped" : " objective-unmapped"; ?>"
                        data-toggle="tooltip"
                        title="<?php echo html_encode($tooltip); ?>">

                    <i class="fa <?php echo $options["mapped"] ? "fa-check" : "fa-plus" ?>"
                       aria-hidden="true">
                    </i>

                </button>
            </td>

            <td class="rotation-objective-cell">

                <strong>
                    <?php echo html_encode($title); ?>
                </strong>

                <?php if (count($options["rotations"]) > 0):
                    $tooltip = "";
                    foreach ($options["rotations"] as $key => $rotation):
                        $tooltip .= html_encode($rotation["title"]);
                        if ($key < (count($options["rotations"]) - 1)) {
                            $tooltip .= ", ";
                        }
                    endforeach; ?>

                    <div class="label tooltip-tag objective-mapped-rotations-badge" data-toggle="tooltip" title="<?php echo html_encode($tooltip); ?>">
                        <?php echo sprintf($translate->_("Mapped to %d rotation(s)"), count($options["rotations"])); ?>
                    </div>
                <?php endif; ?>
            </td>

            <td class="rotation-priority-cell">
                <?php
                $disabled = false;
                $selected = false;
                if ($options["mapped"]) {
                    if ($options["priority"]) {
                        $selected = true;
                    }
                } else {
                    $disabled = true;
                }
                ?>

                <button data-objective-id="<?php echo html_encode($options["objective_id"]); ?>"
                        class="btn objective-priority-button<?php echo($selected ? " active objective-mapped" : ($disabled ? " disabled objective-unmapped" : "")); ?>"
                        data-toggle="tooltip"
                        title="<?php echo $translate->_("Click to Prioritize this Objective"); ?>"
                    <?php echo($selected ? " selected" : ($disabled ? " disabled" : "")); ?> >
                    <?php if ($disabled) : ?>
                        <i class="fa fa-exclamation-circle"
                           aria-hidden="true">
                        </i>
                    <?php elseif ($selected) : ?>
                        <i class="fa fa-exclamation-circle"
                           aria-hidden="true">
                        </i>
                    <?php else : ?>
                        <i class="fa fa-exclamation-circle"
                           aria-hidden="true">
                        </i>
                    <?php endif ?>
                </button>

            </td>

            <td class="rotation-likelihood-cell">
                <div class="btn-group">

                    <?php foreach ($options["likelihood_datasource"] as $likelihood):
                        $disabled = false;
                        $selected = false;

                        if ($options["mapped"]) :
                            if ($options["selected_likelihood_id"] && $options["selected_likelihood_id"] == $likelihood["likelihood_id"]) :
                                $selected = true;
                            endif;
                        else :
                            $disabled = true;
                        endif;
                        $class = "";
                        switch($likelihood["shortname"]) {
                            case "unlikely":
                                if ($disabled) {
                                    $class = "unlikely-disabled";
                                } elseif ($selected) {
                                    $class = "unlikely-active";
                                } else {
                                    $class = "unlikely";
                                }
                                break;
                            case "likely":
                                if ($disabled) {
                                    $class = "likely-disabled";
                                } elseif ($selected) {
                                    $class = "likely-active";
                                } else {
                                    $class = "likely";
                                }
                                break;
                            case "very_likely":
                                if ($disabled) {
                                    $class = "very-likely-disabled";
                                } elseif ($selected) {
                                    $class = "very-likely-active";
                                } else {
                                    $class = "very-likely";
                                }
                                break;
                        }?>

                        <button data-likelihood-id="<?php echo html_encode($likelihood["likelihood_id"]); ?>"
                                data-likelihood-shortname="<?php echo html_encode($likelihood["shortname"]); ?>"
                                data-objective-id="<?php echo html_encode($options["objective_id"]); ?>"
                                class="btn objective-likelihood-button<?php echo($selected ? " active objective-mapped " : ($disabled ? " disabled objective-unmapped " : " ")); echo $class; ?>"
                                data-toggle="tooltip"
                                title="<?php echo html_encode($likelihood["title"]); ?>"
                            <?php echo($selected ? " selected" : ($disabled ? " disabled" : "")); ?>></button>
                    <?php endforeach; ?>

                </div>
            </td>

        </tr>
        <?php
    }

}