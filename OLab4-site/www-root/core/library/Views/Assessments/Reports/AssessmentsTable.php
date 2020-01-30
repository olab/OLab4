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
 * A table listing assessments for the given parameters.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Reports_AssessmentsTable extends Views_Assessments_Base
{
    /**
     * Perform options validation
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        // Ensure the assessments list is set and is an array.
        if (!isset($options["completed_assessments"])) {
            return false;
        }
        return true;
    }

    /**
     * Render the table.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $assessments_list = $options["completed_assessments"];
        ?>
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th><?php echo $translate->_("Assessor"); ?></th>
                    <th><?php echo $translate->_("Completed On"); ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($assessments_list)): ?>
                <tr>
                    <td colspan="3">
                        <p class="no-search-targets space-above space-below medium"><?php echo $translate->_("No completed assessments."); ?></p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($assessments_list as $assessment): ?>
                    <tr>
                        <td><?php echo $assessment["assessor_name"]; ?></td>
                        <td><?php echo strftime("%Y-%m-%d", $assessment["completed_on"]); ?></td>
                        <td><a href="<?php echo $assessment["url"]?>" target="_blank"><?php echo $translate->_("View Assessment"); ?></a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
        <?php
    }
}