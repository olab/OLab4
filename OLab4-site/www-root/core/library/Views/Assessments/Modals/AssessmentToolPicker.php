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
 * Modal for selecting an assessment tool.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Modals_AssessmentToolPicker extends Views_HTML {

    protected function validateOptions($options = array()) {
        return true;
    }

    /**
     * Render the modal view.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        $view_mode = array_key_exists("mode", $options) ? $options["mode"] : "markup";

        switch ($view_mode) {
            case "template":
                Entrada_Utilities_jQueryHelper::addScriptsToHead(); // add loadTemplate to global $HEAD to allow this template to be useful
                $this->renderTemplate($options);
                break;
            case "markup":
            default:
                $this->renderMarkup($options);
                break;
        }
    }

    private function renderTemplate($options) {
        ?>
        <script type="text/html" id="assessment-tool-picker-list-item-template">
            <label class="radio" data-template-bind='[{"attribute": "for", "value": "tpl_tool_selector"}]'>
                <input data-id="tpl_tool_selector"
                       data-template-bind='[
                            {"attribute": "name", "value": "tpl_tool_selection"},
                            {"attribute": "data-form-id", "value": "tpl_form_id"}
                       ]'
                       value="1"
                       type="radio"
                />
                <span class="assessment-type-title" data-content="tpl_tool_title"></span>
                <span class="assessment-type-description muted hide" data-content="tpl_tool_description"></span>
            </label>
        </script>
        <?php
    }

    private function renderMarkup($options) {
        global $translate;
        ?>
        <div class="modal fade" id="assessment-tool-picker-modal">
            <form name="assessment-tool-picker-modal-form" id="assessment-tool-picker-modal-form" method="POST" action="">
                <input type="hidden" class="hide" id="assessment-tool-picker-form-type" value="">
                <input type="hidden" class="hide" id="assessment-tool-picker-trigger-action" value="">
                <input type="hidden" class="hide" id="assessment-tool-picker-form-id" value="">
                <input type="hidden" class="hide" id="assessment-tool-picker-assessor-value" value="">
                <input type="hidden" class="hide" id="assessment-tool-picker-assessment-method-id" value="">
                <input type="hidden" class="hide" id="assessment-tool-picker-target-record-id" value="">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h3><?php echo sprintf($translate->_("Select a")); ?>&nbsp;<span id="assessment-tool-picker-form-type-label"></span></h3>
                </div>
                <div class="modal-body">
                    <div id="modal-error-msgs"></div>
                    <div id="tool-picker-loading-msg"></div>
                    <div id="tool-picker-loading" class="text-center">
                        <img src="<?php echo ENTRADA_URL . "/images/loading.gif"; ?>"/>
                    </div>
                    <div id="assessment-tool-picker-container" class="hide">
                        <ul id="assessment-tool-picker-list">
                            <!-- loadTemplate content goes here -->
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
                        <a href="#" id="assessment-tool-selected" class="btn btn-primary pull-right"><?php echo $translate->_("Select"); ?></a>
                    </div>
                </div>
            </form>
        </div>
        <?php

    }
}