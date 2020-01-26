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
 * View class for rendering the sidebar target information, appended
 * to the assessments sidebar.
 *
 * @author Organization: Queen's University.
 * @author Developer: Jordan L <jl250@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Modals_HideAssessmentTask extends Views_Assessments_Base {

    /**
     * Render the modal view.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        global $translate;
        ?>
        <div class="modal fade" id="hide-assessment-task-modal">
            <form name="hide-assessment-task-modal-form" id="hide-assessment-task-modal-form" method="POST" action="#">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h3><?php echo $translate->_("Hide Assessment Task"); ?></h3>
                </div>
                <div class="modal-body">
                    <div id="hide-assessment-success" class="space-above space-below hide">
                        <?php echo display_success($translate->_("Assessment task successfully hidden.")) ?>
                    </div>
                    <div id="hide-assessment-error"></div>
                    <div id="hide-assessment-details-section">
                        <strong><?php echo $translate->_("Please enter why you want to hide this assessment:"); ?></strong>
                        <div id="hide-assessment-details" class="space-below">
                            <textarea id="hide-assessment-comments" name="hide-assessment-comments" class="expandable"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" id="hide-assessment-close-button" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Close"); ?></a>
                        <input type="button" id="hide_form_confirm" class="btn btn-warning pull-right" name="hide_form_confirm" value="<?php echo $translate->_("Hide Form"); ?>"/>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
}