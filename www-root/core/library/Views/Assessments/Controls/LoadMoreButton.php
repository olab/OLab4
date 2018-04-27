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
 * View class for rendering a load more button with built-in spinner
 * and data attributes.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Controls_LoadMoreButton extends Views_HTML {

    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("limit", "offset", "append_to"));
    }

    protected function renderView($options = array()) {
        global $translate;
        ?>
        <button class="btn btn-block load-more-tasks-button"
            <?php foreach ($options as $option_type => $option_value) : ?>
                data-<?php echo str_replace("_", "-", $option_type); ?>="<?php echo $option_value; ?>"
            <?php endforeach; ?>>
            <?php echo $translate->_("Load More Tasks"); ?>
            <img class="hide load-more-tasks-spinner" src="<?php echo ENTRADA_URL ?>/images/indicator.gif"/>
        </button>
        <?php
    }


}