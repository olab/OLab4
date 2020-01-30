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
class Views_Course_Cbme_Plans_Templates_ContextualVariableResponse extends Views_HTML {
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
        <script type="text/html" id="assessment-plan-cv-response-item-template">
            <div class="list-set-item-cell full-width"><label data-template-bind='[{"attribute": "for", "value": "contextual_variable_response_identifier"}]' data-content="contextual_variable_response" class="control-label"></label></div>

            <div class="list-set-item-cell">
                <label data-template-bind='[{"attribute": "for", "value": "contextual_variable_response_identifier"}]' class="control-label form-required nowrap"><?php echo $translate->_("Minimum Number"); ?></label>
            </div>

            <div class="list-set-item-cell">
                <div class="controls">
                    <input data-template-bind='[{"attribute": "id", "value": "contextual_variable_response_identifier"}, {"attribute": "name", "value": "contextual_variable_response_name"}]' type="text" class="input-small" />
                </div>
            </div>

            <div class="list-set-item-cell remove-contextual-variable-response-container">
                <a href="#" class="remove-contextual-variable-response-btn" data-template-bind='[{"attribute": "data-form-id", "value": "form_id"}, {"attribute": "data-contextual-variable-id", "value": "contextual_variable_id"}, {"attribute": "data-contextual-variable-response-id", "value": "contextual_variable_response_id"}]'>
                    <i class="fa fa-times" aria-hidden="true"></i>
                </a>
            </div>
        </script>
        <?php
    }

    /**
     *
     * Adds required CSS and JS files to the $HEAD array and adds entry to the $BREADCRUMB array for this view.
     */
    protected function renderHead () {
        Entrada_Utilities_jQueryHelper::addScriptsToHead();
    }
}