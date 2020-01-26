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
 * View class for modal window to manage the responses for the contextual variables.
 *
 * @author Organization: Queen's University.
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Modals_ContextualVariableResponses extends Views_Assessments_Base {

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
        global $translate; ?>
        <div class="modal fade" id="contextual-variable-responses-modal" style="display:none">
            <input type="hidden" id="cvar-response-cvar-id" name="cvar_id" value=""/>
            <input type="hidden" id="cvar-response-set-no" name="set_no" value=""/>
            <input type="hidden" id="cvar-response-component-id" name="component_id" value=""/>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h3><?php echo $translate->_("Available Responses"); ?></h3>
            </div>
            <div class="modal-body">
                <div id="contextual-variables-response-body" class="contextual-variables-response-body">
                    <a class="btn btn-small" id="contextual-variables-responses-check-all" href="javascript://"><?php echo $translate->_("Check All"); ?></a>&nbsp;
                    <a class="btn btn-small" id="contextual-variables-responses-uncheck-all" href="javascript://"><?php echo $translate->_("Uncheck All"); ?></a>
                    <br /><br />
                    <div id="contextual-variables-response-list"></div>
                </div>
                <div id="no-contextual-variables-available" class="hide">
                    <?php echo $translate->_("No responses found for that contextual variable."); ?>
                </div>
            </div>
            <div class="modal-footer">
                <div class="row-fluid">
                    <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
                    <a href="javascript://" id="contextual-variable-responses-modal-confirm" name="contextual-variable-responses-confirm" class="btn btn-info pull-right"><?php echo $translate->_("Save and Close"); ?></a>
                </div>
            </div>
        </div>
        <?php
        $this->renderElementTemplate();
    }

    /**
     * Render contextual variable element template.
     */
    protected function renderElementTemplate() {
        ?>
        <script type="text/html" id="contextual-vars-responses-template">
            <label class="checkbox" data-template-bind='[{"attribute": "for", "value":"tpl_cvars_response_element_id"}]'>
                <input type="checkbox" name="cvar_response[]" data-template-bind='[{"attribute": "value", "value":"tpl_cvars_response_element_value"},{"attribute": "id", "value":"tpl_cvars_response_element_id"}]'>
                <span data-content="tpl_cvars_response_label_content"></span>
            </label>
        </script>
        <?php
    }
}