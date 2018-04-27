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
 * View class for rendering task forwarding modal.
 *
 * @author Organization: Queen's University.
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Modals_ForwardTask extends Views_Assessments_Base {
    /**
     * Render the modal view.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        global $translate;
        ?>
        <div class="modal fade" id="forward-task-modal" style="display: none">
            <form name="forward-task-modal-form" id="forward-task-modal-form" method="POST" action="#">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h3><?php echo $translate->_("Forward Task"); ?></h3>
                </div>
                <div class="modal-body">
                    <div id="forward-task-success" class="space-above space-below hide">
                        <?php echo display_success($translate->_("Task successfully forwarded.")) ?>
                    </div>
                    <div id="forward-task-error"></div>
                    <div id="forward-task-details-section">
                        <div id="select-forward-assessor" class="control-group target-option">
                            <p>
                                <strong>
                                    <?php
                                    if (!$options["viewer_is_assessor"]):
                                        echo $translate->_("The current assessor will receive an email informing them of the deletion. ");
                                    endif;
                                    echo $translate->_("The new assessor will receive an email informing them of the newly created task."); ?>
                                </strong>
                            </p>
                            <label for="choose-forward-assessor-btn" class="control-label form-required"><?php echo $translate->_("Select New Assessor"); ?></label>
                            <div class="controls">
                                <button id="choose-forward-assessor-btn"
                                        class="btn btn-search-filter"><?php echo $translate->_("Browse Faculty Members"); ?>
                                    <i class="icon-chevron-down btn-icon pull-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" id="forward-task-close-button" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Close"); ?></a>
                        <input type="button" id="forward-task-confirm" class="btn btn-primary pull-right" name="forward-task-confirm" value="<?php echo $translate->_("Forward Task"); ?>"/>
                    </div>
                </div>
                <input type="hidden" id="task-info"
                       data-dassessment-id="<?php echo(isset($options["dassessment_id"]) ? $options["dassessment_id"] : ""); ?>"
                       data-target-type="<?php echo(isset($options["target_type"]) ? $options["target_type"] : "") ?>"
                       data-target-record-id="<?php echo(isset($options["target_record_id"]) ? $options["target_record_id"] : "") ?>"
                />
            </form>
        </div>
        <?php
    }
}