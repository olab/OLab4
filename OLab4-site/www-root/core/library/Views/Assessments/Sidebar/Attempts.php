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
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Sidebar_Attempts extends Views_HTML {

    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("min_attempts", "max_attempts", "assessment_uri"));
    }

    protected function renderView($options = array()) {
        global $translate;
        $completed_attempts = $options["completed_attempts"];
        $min_attempts       = $options["min_attempts"];
        $max_attempts       = $options["max_attempts"];
        $assessment_uri     = $options["assessment_uri"];
        $aprogress_id       = Entrada_Utilities::arrayValueOrDefault($options, "aprogress_id");
        $progress           = Entrada_Utilities::arrayValueOrDefault($options, "progress", array());
        $current_target     = Entrada_Utilities::arrayValueOrDefault($options, "current_target", array());
        $target_is_deleted  = Entrada_Utilities::arrayValueOrDefault($current_target, "deleted_date", true);

        $has_inprogress = false;
        if ($max_attempts > 1 && !empty($progress)): ?>
            <div class="assessment-delivery-detail well">
                <h3 class="heading"><?php echo $translate->_("Attempts") ?></h3>
                <ul class="menu none">
                    <?php foreach ($progress as $progress_data):
                        $selected_tag_start = ($progress_data["aprogress_id"] == $aprogress_id) ? "<strong>" : "";
                        $selected_tag_end = ($progress_data["aprogress_id"] == $aprogress_id) ? "</strong>" : "";
                        $assessment_link = "$assessment_uri&atarget_id={$progress_data["atarget_id"]}&aprogress_id={$progress_data["aprogress_id"]}";
                        ?>
                        <?php if ($progress_data["progress_value"] == "complete" && !$progress_data["deleted_date"]): ?>
                            <li><?php echo $selected_tag_start ?><a href="<?php echo $assessment_link ?>"><?php echo sprintf($translate->_("Completed %s"), date("M d/y h:i a", $progress_data["updated_date"])) ?></a><?php echo $selected_tag_end ?></li>
                        <?php elseif (!$target_is_deleted && $progress_data["progress_value"] == "inprogress" && !$progress_data["deleted_date"]): $has_inprogress = true; ?>
                            <li><?php echo $selected_tag_start ?><a href="<?php echo $assessment_link ?>"><?php echo sprintf($translate->_("Started %s"), date("M d/y h:i a", $progress_data["created_date"])) ?></a><?php echo $selected_tag_end ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if (!$target_is_deleted && $completed_attempts < $max_attempts && !$has_inprogress): ?>
                    <li>
                        <a id="assessment-begin-new-attempt" href="#">
                            <?php echo $translate->_("Begin new attempt"); ?>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif;
    }
}