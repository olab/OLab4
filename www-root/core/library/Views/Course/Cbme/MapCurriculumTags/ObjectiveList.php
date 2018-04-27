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
 * View class for rendering objective lists in the curriculum mapping interface
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
class Views_Course_Cbme_MapCurriculumTags_ObjectiveList extends Views_HTML {
    protected $objectives = array();

    /**
     * Validate: ensure all attributes that the view requires are passed to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        $options_valid = true;

        if (!isset($options["objective_list_id"])) {
            $options_valid = false;
        }

        if (!isset($options["objective_set"])) {
            $options_valid = false;
        }

        if (!isset($options["objective_set_search_input_id"])) {
            $options_valid = false;
        }

        if (!isset($options["objective_set_search_input_name"])) {
            $options_valid = false;
        }

        if (!isset($options["objective_set_search_placeholder"])) {
            $options_valid = false;
        }

        if (!isset($options["objective_set_list_id"])) {
            $options_valid = false;
        }

        if (!isset($options["multi_select"])) {
            $options_valid = false;
        }

        if (!isset($options["no_objectives_text"])) {
            $options_valid = false;
        }

        if (!isset($options["objective_class_string"])) {
            $options_valid = false;
        }

        if (!isset($options["load_more_text"])) {
            $options_valid = false;
        }

        if (!isset($options["populates"])) {
            $options_valid = false;
        }

        if (!isset($options["ajax_load"])) {
            $options_valid = false;
        }

        if (!isset($options["active"])) {
            $options_valid = false;
        }

        if (!isset($options["load_more"])) {
            $options_valid = false;
        }

        if (!isset($options["final_node"])) {
            $options_valid = false;
        }

        return $options_valid;
    }
    /**
     * Render the objective list.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        global $translate; ?>
        <div id="<?php echo html_encode($options["objective_list_id"]) ?>" class="entrada-select-list-container" data-objective-set="<?php echo html_encode($options["objective_set"]) ?>" data-populates="<?php echo html_encode($options["populates"]) ?>" data-multi-select="<?php echo html_encode($options["multi_select"]) ?>" data-ajax-load="<?php echo html_encode($options["ajax_load"]) ?>" data-active="<?php echo html_encode($options["active"]) ?>" data-final-node="<?php echo html_encode($options["final_node"]) ?>">
        <div class="entrada-select-list-search">
            <input type="text" id="<?php echo html_encode($options["objective_set_search_input_id"]) ?>"
                   name="<?php echo html_encode($options["objective_set_search_input_name"]) ?>"
                   class="objective-set-search"
                   placeholder="<?php echo $translate->_($options["objective_set_search_placeholder"]) ?>" <?php echo($options["active"] ? "" : "disabled=\"disabled\"") ?> />
        </div>
        <ul id="<?php echo html_encode($options["objective_set_list_id"]) ?>" class="entrada-select-list">
            <li class="entrada-search-list-empty <?php echo($options["active"] ? "hide" : "") ?>">
                <?php echo $translate->_($options["no_objectives_text"]) ?>
            </li>
            <?php
            if ($this->objectives) {
                foreach ($this->objectives as $objective) { ?>
                    <li class="<?php echo html_encode($options["objective_class_string"]) ?> <?php echo($options["active"] ? "" : "hide") ?>"
                        data-id="<?php echo html_encode($objective->getID()) ?>"
                        data-parent="<?php echo html_encode($objective->getParent()) ?>"
                        data-code="<?php echo html_encode($objective->getCode()) ?>">
                        <div class="entrada-select-list-title">
                            <span class="objective-code"><?php echo html_encode($objective->getCode()) ?></span>
                            <span><?php echo html_encode($objective->getName()) ?></span>
                        </div>
                    </li>
                    <?php
                }
            }
            ?>
        </ul>
        <?php
        if ($options["load_more"]) { ?>
        <div class="entrada-search-list-load-button clear_both">
            <a href="#"><?php echo $translate->_($options["load_more_text"]) ?></a>
        </div>
        <?php
        }
        ?>
        </div>
        <?php
    }
}