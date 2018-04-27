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
 * View class for rendering a list of Views_Learner_Level
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Belanger <jb301@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_Learner_Level_List extends Views_HTML {

    protected function validateOptions($options = array()) {
        if (!$this->validateIsSet($options, array("list_title"))) {
            return false;
        }
        if (!$this->validateArray($options, array("levels"))) {
            return false;
        }
        return true;
    }

    protected function renderView($options = array()) {
        global $translate;

        // Overall list options.
        $id = array_key_exists("id", $options) ? $options["id"] : null;
        $class = array_key_exists("class", $options) ? $options["class"] : null;

        // Level specific options.
        $level_class = array_key_exists("level_class", $options) && $options["class"] ? $options["level_class"] : null;
        $level_mode = array_key_exists("level_mode", $options) && $options["level_mode"] ? $options["level_mode"] : "editor";
        $level_state = array_key_exists("level_state", $options) && $options["level_state"] ? $options["level_state"] : "editor-readonly";
        $no_results = array_key_exists("no_results_label", $options) ? $options["no_results_label"] : $translate->_("No learner levels found");
        $stages_datasource = array_key_exists("stages_datasource", $options) && $options["stages_datasource"] ? $options["stages_datasource"] : null;

        ?>
        <h2 class="title"><?php echo $translate->_($options["list_title"]); ?></h2>

        <div<?php echo ($id ? " id=\"{$id}\"" : "") . ($class ? " class=\"{$class}\"" : ""); ?>>
        <?php if (sizeof($options["levels"]) > 0):
            foreach ($options["levels"] as $level) :
                $level["id"] = $level["user_learner_level_id"];
                $level["class"] = $level_class;
                $level["stages_datasource"] = $stages_datasource;
                $level["selected_stage_id"] = $level["stage_objective_id"];
                $card = new Views_Learner_Level(array("mode" => $level_mode, "state" => $level_state));
                $card->render($level);
            endforeach;
        else: ?>
           <h3><?php echo html_encode($no_results); ?></h3>
        <?php endif; ?>
        </div>
        <?php
    }

}