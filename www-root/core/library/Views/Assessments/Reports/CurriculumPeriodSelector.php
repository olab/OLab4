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
 * Curriculum period selector widget.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Reports_CurriculumPeriodSelector extends Views_Assessments_Base
{
    /**
     * Perform options validation
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        // Validate that we have proper curriculum periods
        if (!isset($options["curriculum_periods"])) {
            return false;
        }
        // Passed
        return true;
    }

    /**
     * Render the table.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $curriculum_periods = $options["curriculum_periods"];

        $selected_cperiod_id = @$options["selected_cperiod_id"]; // optional
        $read_only = @$options["read_only"];
        $text_only = @$options["text_only"];
        ?>
        <div <?php echo $this->getClassString() ?>>
            <h3 class="no-margin"><?php echo $translate->_("Selected Curriculum Period") ?>:</h3>
            <?php if (empty($curriculum_periods)): ?>
                <p><?php echo $translate->_("There are no curriculum periods to display.");?></p>
            <?php else: ?>
                <?php if ($text_only): ?>

                    <?php foreach ($curriculum_periods as $curriculum_period): ?>
                        <?php if ($curriculum_period["cperiod_id"] == $selected_cperiod_id): ?>
                            <p><?php echo strftime("%Y-%m-%d", $curriculum_period["start_date"]) ?> <?php echo html_encode($translate->_("to"))?> <?php echo strftime("%Y-%m-%d", $curriculum_period["finish_date"]) ?></p>
                        <?php endif; ?>
                    <?php endforeach; ?>

                <?php else: ?>

                    <select class="select-box curriculum-period-selector" <?php echo ($read_only) ? "disabled" : "" ?>>
                        <?php foreach ($curriculum_periods as $curriculum_period):
                            $is_selected = ($curriculum_period["cperiod_id"] == $selected_cperiod_id) ? ' selected="selected"' : "";
                            ?>
                            <option value="<?php echo $curriculum_period["cperiod_id"]?>"<?php echo $is_selected ?>>
                                <?php echo strftime("%Y-%m-%d", $curriculum_period["start_date"]) ?> <?php echo html_encode($translate->_("to"))?> <?php echo strftime("%Y-%m-%d", $curriculum_period["finish_date"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }
}