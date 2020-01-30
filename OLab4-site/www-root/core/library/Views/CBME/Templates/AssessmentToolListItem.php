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
 * This view is used in the milestone report to show the list of tools available
 *
 * @author Organization: Queen's University.
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_CBME_Templates_AssessmentToolListItem extends Views_HTML {
    /**
     * Validate our options array.
     * Since we do not use the options array return true
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return true;
    }

    /**
     * Render the modal view.
     * @param $options
     */
    protected function renderView($options = array()) {
        $this->addHeadScripts();

        ?>
        <script type="text/html" id="assessment-tool-list-template">
            <div class="milestone-report-control-container">
                <input type="checkbox" data-id="assessment_tool_id" class="assessment-tool-checkbox" name="form_ids[]" data-template-bind='[{"attribute": "value", "value": "form_id"}]' />
                <label data-template-bind='[{"attribute": "for", "value": "assessment_tool_id"}]' ><span data-content="objective_code" class="label d-stage space-right"></span></label>
            </div>
            <div class="milestone-report-title-container">
                <label data-template-bind='[{"attribute": "for", "value": "assessment_tool_id"}]' >
                    <span data-content="title" class="milestone-report-title"></span>
                </label>
            </div>
            <div class="milestone-report-date-container">
                <span class="pull-right muted space-above milestone-report-date" data-content="tool_created_date"></span>
            </div>
        </script>

        <script type="text/html" id="assessment-tool-list-error-template">
            <div class="assessment-tool-list-container inline-block space-left">
                <div class="assessment-tool-header inline-block">
                    <h3 data-content="title" class="no-margin"></h3>
                </div>
            </div>
        </script>

        <?php
    }

    /**
     * Adds required CSS and JS files to the $HEAD array and adds entry to the $BREADCRUMB array for this view.
     */
    protected function addHeadScripts () {
        Entrada_Utilities_jQueryHelper::addScriptsToHead();
    }

}
