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
* View class for rendering upcoming rotation schedule changes. Appended
* to the assessment and evaluation dashboard.
*
* @author Organization: Queen's University.
* @author Developer: Alex Ash <aa121@queensu.ca>
* @copyright Copyright 2016 Queen's University. All Rights Reserved.
*
*/

class Views_Assessments_Dashboard_ScheduleChanges extends Views_Assessments_Base {
    protected function validateOptions($options = array()) {
        if (!isset($options["schedule_data"]) || !is_array($options["schedule_data"])) {
            return false;
        }
        return true;
    }

    protected function renderView($options = array()) {
        global $translate; ?>
        <div class="quadrant-wrapper">
            <h2 class="header-nospace"><?php echo $translate->_("Rotation Changes"); ?></h2>
            <?php if (!empty($options["schedule_data"])) : ?>
                <div class="schedule-deck">
                    <div class="schedule-header">
                        <div><?php echo $translate->_("Current Rotation"); ?></div>
                        <div></div>
                        <div><?php echo $translate->_("Next Rotation"); ?></div>
                    </div>
                    <div class="schedule-info">
                        <?php foreach ($options["schedule_data"] as $schedule_data) : ?>
                        <div class="audience">
                            <div class="audience-name">
                                <strong><?php echo $schedule_data["audience_full_name"]; ?></strong>
                            </div>
                            <div class="schedule-details">
                                <div>
                                    <a href="<?php echo $schedule_data["schedule_old_url"] ?>">
                                        <span><strong><?php echo $schedule_data["schedule_title_before"]; ?></strong></span><br>
                                        <span><?php echo date("Y-m-d", $schedule_data["schedule_start_before"]); ?></span><br>
                                        <span><?php echo date("Y-m-d", $schedule_data["schedule_end_before"]); ?></span><br>
                                    </a>
                                </div>
                                <div>
                                    <i class="fa fa-arrow-right schedule-arrow" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <a href="<?php echo $schedule_data["schedule_new_url"] ?>">
                                        <span id="rotation-next-title"><strong><?php echo $schedule_data["schedule_title_after"]; ?></strong></span><br>
                                        <span><?php echo date("Y-m-d", $schedule_data["schedule_start_after"]); ?></span><br>
                                        <span><?php echo date("Y-m-d", $schedule_data["schedule_end_after"]); ?></span><br>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else : ?>
                <div class="alert no-margin-bottom"><?php echo $translate->_("No upcoming rotation changes."); ?></div>
            <?php endif; ?>
        </div>
        <?php
    }
}