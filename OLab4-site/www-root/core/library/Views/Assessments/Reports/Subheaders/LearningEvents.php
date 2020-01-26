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
 * Simple view to render the report subheader for learning events.
 *
 * @author Organization: Queen's University.
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Reports_Subheaders_LearningEvents extends Views_Assessments_Base {

    /**
     * Validate
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        if (!isset($options["events"])) {
            return false;
        }
        return true;
    }

    /**
     * Render the html.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        ?>
        <div id="subheader-learning-events" class="report-subheader space-above">
            <h2><?php echo $translate->_("Learning Events:") ?></h2>
            <table class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th><?php echo $translate->_("Event Title") ?></th>
                    <th><?php echo $translate->_("Event Date") ?></th>
                    <th><?php echo $translate->_("Teachers") ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($options["events"] as $event): ?>
                    <tr>
                        <td><?php echo html_encode($event["title"]); ?></td>
                        <td><?php echo html_encode($event["date"]); ?></td>
                        <td>
                            <?php foreach ($event["teachers"] as $teacher): ?>
                                <div class="learning-events-subheader-teacher">
                                    <?php echo html_encode($teacher); ?>
                                </div>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

}