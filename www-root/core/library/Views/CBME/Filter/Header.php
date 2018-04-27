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
 * A view for rendering the CBME filter search bar header
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_CBME_Filter_Header extends Views_HTML {
    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("filter_label", "filters", "apply_button_text", "all_filters_preference"));
    }

    /**
     * Render the view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate; ?>
        <div class="list-filter-search">
            <div class="list-filter-cell list-filter-title">
                <label for="assessment-search"><?php echo html_encode($options["filter_label"]) ?></label>
            </div>
            <div class="list-filter-cell full-width">
                <label class="sr-only"><?php echo $translate->_("Search") ?></label>
                <input id="assessment-search" class="full-width" type="search" name="search_term" placeholder="Search" value="<?php echo (isset($options["filters"]["search_term"]) ? html_encode($options["filters"]["search_term"]) : "") ?>" />
            </div>
            <div class="list-filter-cell">
                <button id="select-user-btn" class="btn btn-default nowrap"><?php echo $translate->_("Select Assessor") ?> <i class="fa fa-angle-down"></i></button>
            </div>
            <div class="list-filter-cell">
                <a href="#" class="toggle-all-filters <?php echo ($options["all_filters_preference"] == "expanded" ? "open" : "") ?>" data-filter-type="all_filters">
                    <span class="fa fa-chevron-down list-filter-icon"></span>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Render a custom error message for this view.
     */
    protected function renderError() {
        global $translate;?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render filter header."); ?></strong>
        </div>
        <?php
    }
}