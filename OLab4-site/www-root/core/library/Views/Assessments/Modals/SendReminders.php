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
 * View class for modal window to display and confirm sending of reminders
 * for assessments.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Modals_SendReminders extends Views_Assessments_Base
{
    /**
     * Validate our options array.
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        if (!isset($options["action_url"])) {
            return false;
        }
        return true;
    }

    /**
     * Render the modal view.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        global $translate; ?>
        <div class="modal hide fade" id="reminder-modal">
            <form name="reminder-modal-form" id="reminder-modal-form" method="POST" action="<?php echo $options["action_url"] ?>">
                <input type="hidden" name="step" value="2"/>
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h3><?php echo $translate->_("Send Reminders"); ?></h3>
                </div>
                <div class="modal-body">
                    <div id="reminders-success" class="space-above space-below hide">
                        <div class="alert alert-block alert-success" id="display-success-box">
                            <ul>
                                <li><?php echo $translate->_("Reminders sent successfully")?>.</li>
                            </ul>
                        </div>
                    </div>
                    <div id="reminders-error" class="hide">

                    </div>
                    <div id="reminder-details-section" class="hide">
                        <strong><?php echo $translate->_("Reminders will be sent for the following assessor(s):"); ?></strong>
                        <div id="reminder-details" class="space-below space-left hide">
                            <ul id="reminder-details-list"></ul>
                        </div>
                    </div>
                    <div id="reminder-details-section-delegators" class="hide">
                        <strong><?php echo $translate->_("Reminders will be sent to the delegator:"); ?></strong>
                        <div class="space-below space-left">
                            <ul>
                                <li id="reminder-details-section-delegator-name"></li>
                            </ul>
                        </div>
                    </div>
                    <div id="no-reminders-selected" class="hide">
                        <?php echo $translate->_("No tasks selected for reminders."); ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Close"); ?></a>
                        <a href="#" id="reminder-modal-confirm" name="reminder-confirm" class="btn btn-info pull-right"><?php echo $translate->_("Confirm Reminders"); ?></a>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
}