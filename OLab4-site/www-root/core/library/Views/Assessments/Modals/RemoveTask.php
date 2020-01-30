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
 * View class for modal window to delete tasks.
 *
 * @author Organization: Queen's University.
 * @author Developer: joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Modals_RemoveTask extends Views_Assessments_Base
{
    /**
     * Validate our options array.
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return $this->validateArrayNotEmpty($options, array("deletion_reasons"));
    }

    /**
     * Render the modal view.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        global $translate; ?>
        <div id="remove-tasks-modal" class="modal fade" style="display: none;">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h3><?php echo $translate->_("Reason to Remove Task"); ?></h3>
            </div>
            <div class="modal-body">
                <input type="hidden" id="removetask-removing-reason" name="removetask_removing_reason" value="" />
                <input type="hidden" id="removetask-removing-reason-id" name="removetask_removing_reason_id" value="" />
                <input type="hidden" id="removetask-task-data" name="removetask_task_data" value="" />
                <div id="remove-msgs"></div>
                <form class="form-horizontal">
                    <div class="control-group">
                        <label class="control-label"><?php echo $translate->_("Reason for removal:"); ?></label>
                        <div class="controls">
                            <?php foreach ($options["deletion_reasons"] as $reason) : ?>
                                <label class="radio">
                                    <input data-reason="<?php echo html_encode($reason->getDetails()); ?>"
                                           type="radio"
                                           name="removetask_reason"
                                           data-notes-required="<?php echo $reason->getNotesRequired(); ?>"
                                           value="<?php echo $reason->getID();?>"
                                    />
                                    <?php echo html_encode($reason->getDetails()); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div id="other-reason-div" class="control-group space-above medium">
                            <div class="controls">
                                <textarea title="" rows="4" class="span10" id="removetask-other-reason" name="removetask_other_reason"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <input type="button" class="btn pull-left" id="removetask-cancel" data-dismiss="modal" value="<?php echo $translate->_("Cancel"); ?>" />
                <input type="button" class="btn btn-danger" id="removetask-confirm" value="<?php echo $translate->_("Remove Task"); ?>"/>
            </div>
        </div>
        <?php
    }
}