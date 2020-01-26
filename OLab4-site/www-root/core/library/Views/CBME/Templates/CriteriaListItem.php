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
 * This view is used in the cbme dashboard to show the breakdown of assessments
 * for a specific EPA
 *
 * @author Organization: Queen's University.
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_CBME_Templates_CriteriaListItem extends Views_HTML {

    /**
     * Validate our options array.
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return true;
    }

    /**
     * Render the modal view.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $this->addHeadScripts();
        //Global Entrustment Scale
        ?>
        <script type="text/html" id="criteria-list-template">
            <div class="list-criteria-wrapper">
                <div class="inline-block epa-code" data-content="epa-code"></div>
                <div class="inline-block pull-right epa-criteria-toggle space-right">
                    <i class="fa fa-chevron-down"></i>
                </div>
                <div class="criteria-info-container collapsed" style="display: none;">
                    <div class="procedure-details-container">
                    </div>
                    <button type="button" data-template-bind='[{"attribute": "data-objective-id", "value": "objective-id"}, {"attribute": "data-objective-name", "value": "objective-name"}, {"attribute": "data-objective-code", "value": "objective-code"}]' class="btn btn-default inline-block pull-right space-right space-below replace-specific-epa"><?php echo $translate->_("Replace Criteria"); ?></button>
                </div>
            </div>
        </script>

        <?php //Criteria Details ?>
        <script type="text/html" id="criteria-details-template">
            <div data-content="procedure-name"></div>
            <div class="bold" data-content="procedure-title"></div>
            <ul class="criteria-list"></ul>
        </script>

        <script type="text/html" id="criteria-procedures-template">
            <div data-content="description"></div>
        </script>

        <?php //Selected EPA List Item ?>
        <script type="text/html" id="selected-epa-template">
            <li data-class="epa-target-class" data-template-bind='[{"attribute": "data-id", "value": "objective-id"}]' data-content-append="objective_code_name">
                <span class="selected-list-container">
                    <span class="selected-list-item"><?php echo $translate->_("EPA"); ?></span><span class="remove-selected-list-item remove-target-toggle" data-template-bind='[{"attribute": "data-id", "value": "objective-id"}]' data-filter="selected_epa">×</span>
                </span>
            </li>
        </script>
        <?php
    }

    /**
     *
     * Adds required CSS and JS files to the $HEAD array and adds entry to the $BREADCRUMB array for this view.
     */
    protected function addHeadScripts () {
        Entrada_Utilities_jQueryHelper::addScriptsToHead();
    }
}
