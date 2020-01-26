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
 * View class for rendering the list item header template that is used by .loadTemplate in jQuery
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
class Views_Course_Cbme_MapCurriculumTags_ListItemHeaderTemplate extends Views_HTML {

    /**
     * Render the list item header template.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        ?>
        <script type="text/html" id="entrada-select-list-item-header">
            <div data-content="objective_code"></div>
        </script>
        <?php
    }
}