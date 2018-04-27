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
 * View class for rendering current users that are on leave, appended
 * to the assessment and evaluation dashboard.
 *
 * @author Organization: Queen's University.
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Dashboard_CurrentAndUpcomingLeave extends Views_Assessments_Base {
    protected function validateOptions($options = array()) {
        if (!isset($options["tracked_vacation"]) || !is_array($options["tracked_vacation"])) {
            return false;
        }
        return true;
    }

    protected function renderView($options = array()) {
        global $translate; ?>
        <div class="pull-right quadrant-wrapper">
            <h2 class="header-nospace"><?php echo $translate->_("Current and Upcoming Leave"); ?></h2>
            <?php if (!empty($options["tracked_vacation"])) : ?>
                <div class="card-deck leave-deck">
                    <?php foreach ($options["tracked_vacation"] as $vacation) : ?>
                        <a href="<?php echo $vacation["url"]; ?>" class="leave-link">
                            <div class="leave-details">
                                <strong><?php echo $vacation["full_name"]; ?></strong> • <?php echo $vacation["leave_type"]; ?><br>
                                <span><?php echo date("Y-m-d", $vacation["start_date"]); ?></span><?php echo $translate->_(" to "); ?><span><?php echo date("Y-m-d", $vacation["end_date"]); ?></span>
                            </div>
                            <div class="leave-count">
                                <h4 class="absent-label"><?php echo $translate->_("Total Days"); ?></h4>
                                <div class="large-bold-font">
                                    <?php echo $vacation["days_used"]; ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
            <div class="alert no-margin-bottom"><?php echo $translate->_("No current or upcoming leave."); ?></div>
        <?php endif;?>
        </div><?php
    }
}