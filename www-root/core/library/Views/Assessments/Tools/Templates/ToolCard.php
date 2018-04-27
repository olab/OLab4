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
 * HTML view for CBME assessment tool cards.
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Tools_Templates_ToolCard extends Views_HTML {
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
     * Render the curriculum mapping form.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $only_begin_assessment = true;
        if (array_key_exists("administrator_options", $options) && is_array($options["administrator_options"])) {
            $only_begin_assessment = false;
            // Set other options for admin
            $show_deliver_assessment = array_key_exists("show_deliver_assessment", $options["administrator_options"])
                ? $options["administrator_options"]["show_deliver_assessment"]
                : true;
            $show_form_preview = array_key_exists("show_form_preview", $options["administrator_options"])
                ? $options["administrator_options"]["show_form_preview"]
                : true;
        }
        ?>
        <script type="text/html" id="assessment-tool-template">
            <div>
                <span class="assessor-type-badge pull-right"><i class="icon-time"></i> <span data-content="form_average_time"></span></span>
            </div>
            <div class="user-card-container">
                <h3 data-content="title"></h3>
                <p><strong class="muted" data-content="form_type"></strong></p>
                <p class="muted"><span data-content="completed_count">0</span> assessments completed on this learner.</p>
            </div>
            <?php if ($only_begin_assessment): ?>
                <div class="user-card-parent">
                    <a href="#" data-template-bind='[
                        {"attribute": "data-form-id", "value": "form_id"},
                        {"attribute":"data-form-count", "value": "form_count"},
                        {"attribute":"data-epa-objective-id", "value": "epa_objective_id"},
                        {"attribute":"data-form-type", "value": "form_type"}
                    ]' class="all-assessments preview-assessment-tool-form" data-trigger-action="preview"><?php echo $translate->_("Preview this Form") ?></a>
                    
                    <a href="#" data-template-bind='[
                        {"attribute": "data-form-id", "value": "form_id"},
                        {"attribute":"data-form-count", "value": "form_count"},
                        {"attribute":"data-epa-objective-id", "value": "epa_objective_id"},
                        {"attribute":"data-form-type", "value": "form_type"}
                    ]' class="all-assessments assessment-tool-btn trigger-assessment-template-btn" data-trigger-action="begin"><?php echo $translate->_("Begin Assessment ▸") ?></a>
                    <a href="#assessment-cue-modal" data-template-bind='[
                        {"attribute": "data-form-id", "value": "form_id"},
                        {"attribute":"data-form-count", "value": "form_count"},
                        {"attribute":"data-epa-objective-id", "value": "epa_objective_id"},
                        {"attribute":"data-form-type", "value": "form_type"}
                    ]' class="all-assessments hide send-assessment-template-btn" data-toggle="modal"><?php echo $translate->_("Send Assessment ▸") ?></a>
                </div>
            <?php else: ?>
                <div class="user-card-parent">
                    <?php if ($show_form_preview): ?>
                        <div class="user-card-child card-child-divider">
                            <a href="#" data-template-bind='[
                                {"attribute": "data-form-id", "value": "form_id"},
                                {"attribute":"data-form-count", "value": "form_count"},
                                {"attribute":"data-epa-objective-id", "value": "epa_objective_id"},
                                {"attribute":"data-form-type", "value": "form_type"}
                            ]' class="all-assessments assessment-tool-btn" data-trigger-action="preview"><?php echo $translate->_("Form Preview") ?></a>
                        </div>
                    <?php endif; ?>
                    <?php if ($show_deliver_assessment): ?>
                        <div class="user-card-child">
                            <a href="#" data-template-bind='[
                                {"attribute": "data-form-id", "value": "form_id"},
                                {"attribute":"data-form-count", "value": "form_count"},
                                {"attribute":"data-epa-objective-id", "value": "epa_objective_id"},
                                {"attribute":"data-form-type", "value": "form_type"}
                            ]' class="all-assessments assessment-tool-btn" data-trigger-action="deliver"><?php echo $translate->_("Deliver Assessment ▸") ?></a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </script>
        <?php
    }

    /**
     * Render a generic error message.
     */
    protected function renderError() {
        global $translate; ?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render assessment tool"); ?></strong>
        </div>
        <?php
    }
}