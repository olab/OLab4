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
 * View class for rendering assessments in progress on the main dashboard.
 *
 * @author Organization: Queen's University.
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Dashboard_TasksInProgress extends Views_Assessments_Base {

    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("in_progress_count"));
    }

    protected function renderView($options = array()) {
        global $translate;

        $in_progress_count = intval($options["in_progress_count"]);

        // Not rendering if no task are in progress
        if ($in_progress_count < 1) {
            return true;
        }
        ?>
        <div class="alert alert-info">
            <?php
            if ($in_progress_count > 1) {
                echo sprintf(
                    $translate->_("You have %s%s assessments%s currently in progress. Click here to view it."),
                    "<strong><a style='font-weight: bold' href='#' id='view-in-progress-assessments'>",
                    $in_progress_count,
                    "</a></strong>"
                );
            } else {
                echo sprintf(
                    $translate->_("You have %s1 assessment%s currently in progress. Click here to view it."),
                    "<strong><a href='#' id='view-in-progress-assessments'>",
                    "</a></strong>"
                );
            }
            ?>
        </div>

        <?php
    }

    protected function renderError() {
        global $translate;?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render in progress assessments count."); ?></strong>
        </div>
        <?php
    }
}