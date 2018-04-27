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
 * View class for rendering the assessment plans interface
 *
 * @author Organization: Queen's University.
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Course_Cbme_Plans_Templates_AssessmentTool extends Views_HTML {
    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return true;
    }

    /**
     * Render the template
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $this->renderHead(); ?>
        <script type="text/html" id="assessment-plan-tool-template">
            <h3 data-content="assessment_tool_title"></h3>

            <div class="control-group clearfix">
                <div class="inline-control-wrapper">
                    <label data-template-bind='[{"attribute": "for", "value": "minimum_identifier"}]' class="control-label form-required"><?php echo $translate->_("Minimum number of assessments") ?></label>
                    <div class="controls">
                        <input data-template-bind='[{"attribute": "id", "value": "minimum_identifier"}, {"attribute": "name", "value": "minimum_name"}]' type="text" class="input-small" />
                    </div>
                </div>

                <div class="inline-control-wrapper">
                    <label data-template-bind='[{"attribute": "for", "value": "form_rating_scale_response_identifier"}]' class="control-label form-required"><?php echo $translate->_("With a global assessment rating equal to or higher than") ?></label>
                    <div class="controls">
                        <button data-id="form_rating_scale_response_identifier" class="btn btn-success rating-scale-response-btn"><?php echo $translate->_("Rating scale responses") ?> <span class="fa fa-chevron-down"></span></button>
                    </div>
                </div>
            </div>

            <div class="control-group">
                <label data-template-bind='[{"attribute": "for", "value": "minimum_assessors_identifier"}]' class="control-label form-required"><?php echo $translate->_("Minimum number of assessors") ?></label>
                <div class="controls">
                    <input type="text" data-template-bind='[{"attribute": "id", "value": "minimum_assessors_identifier"}, {"attribute": "name", "value": "minimum_assessors_name"}]' class="input-small" />
                </div>
            </div>

            <div class="control-group space-below medium">
                <label for="toolname_contextual_variables" class="control-label form-required" data-template-bind='[{"attribute": "for", "value": "cv_advanced_search_id"}]'><?php echo $translate->_("Contextual Variables") ?></label>
                <div class="controls">
                    <button data-id="cv_advanced_search_id" class="btn btn-success"><?php echo $translate->_("Contextual Variables") ?> <span class="fa fa-chevron-down"></span></button>
                </div>
            </div>

            <div class="pull-right remove-form-container">
                <a href="#" class="btn btn-danger remove-form-btn" data-template-bind='[{"attribute": "data-form-id", "value": "form_id"}]'><i class="fa fa-times" aria-hidden="true"></i> <?php echo $translate->_("Remove Tool"); ?></a>
            </div>
        </script>
    <?php
    }

    /**
     *
     * Adds required CSS and JS files to the $HEAD array and adds entry to the $BREADCRUMB array for this view.
     */
    protected function renderHead () {
        global $HEAD;
        Entrada_Utilities_jQueryHelper::addScriptsToHead();
    }
}