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
 * View class for modal window to manage the milestones for EPAs.
 *
 * @author Organization: Queen's University.
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Modals_EPAMilestones extends Views_Assessments_Base
{
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
        $title = array_key_exists("objectives_title", $options) ? $options["objectives_title"] : "milestones";
        ?>
        <div class="modal fade" id="epa-milestones-modal" style="display:none">
            <input type="hidden" id="epa-milestones-epa-id" name="epa_id" value=""/>
            <input type="hidden" id="epa-milestones-objective-title" name="objective_title" value="<?php echo html_encode($title); ?>"/>
            <input type="hidden" id="epa-milestones-component-id" name="component_id" value=""/>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h3><?php echo $translate->_("Available " . $title); ?></h3>
            </div>
            <div class="modal-body">
                <div id="epa-milestones-body" class="epa-milestones-body">
                    <a class="btn btn-small" id="epa-milestones-check-all" href="javascript://"><?php echo $translate->_("Check All"); ?></a>&nbsp;
                    <a class="btn btn-small" id="epa-milestones-uncheck-all" href="javascript://"><?php echo $translate->_("Uncheck All"); ?></a>
                    <br /><br />
                    <div id="epa-milestones-list"></div>
                </div>
                <div id="no-epa-milestones-available" class="hide">
                    <?php echo $translate->_("No {$title} found for that EPA."); ?>
                </div>
            </div>
            <div class="modal-footer">
                <div class="row-fluid">
                    <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
                    <a href="javascript://" id="epa-milestones-modal-confirm" name="epa-milestones-confirm" class="btn btn-info pull-right"><?php echo $translate->_("Save and Close"); ?></a>
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
        <script type="text/html" id="epa-milestones-template">
            <label class="checkbox" data-template-bind='[{"attribute": "for", "value":"tpl_epa_milestones_element_id"}]'>
                <input type="checkbox" name="epa_milestones[]" data-template-bind='[{"attribute": "value", "value":"tpl_epa_milestones_element_value"},{"attribute": "id", "value":"tpl_epa_milestones_element_id"}]'>
                <span data-content="tpl_epa_milestones_label_content"></span>
            </label>
        </script>
        <?php
    }
}