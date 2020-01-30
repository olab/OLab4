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
 * List of student's rotations and duty hours logged for each
 * User: mikeflores
 * Date: 12/11/2017
 * Time: 9:39 PM
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Joabe Mendes <jm409@queensu.ca>
 * @copyright Copyright 2018 Queen's University. All Rights Reserved.
 *
 */

global $translate;

?>
<div class="control-group row-fluid">
    <table class="tableList table-striped">
        <thead>
        <tr>
            <th class="pull-left"><?php echo $translate->_("Rotation"); ?></th>
            <th><?php echo $translate->_("To Date"); ?></th>
            <th><?php echo $translate->_("Weekly Avg"); ?></th>
            <th><?php echo $translate->_("This Week"); ?></th>
            <th></th>
        </tr>
        </thead>

        <tbody>
        <?php
        foreach ($this->data["rotations"] as $r) {
            ?>
            <tr>
                <td>
                    <a href="dutyhours/rotation?id=<?php echo $r["id"] ?>&cperiod_id=<?php echo $r["cperiod_id"] ?>"><?php echo $r["name"] ?></a>
                </td>
                <td class="center"><?php echo $r["logged_hours"] ?></td>
                <td class="center"><?php echo $r["hours_per_week"] ?></td>
                <td class="center"><?php echo $r["logged_hours_this_week"] ?></td>
                <td class="no-padding alignRight">
                    <?php if ($r["can_edit"]) { ?>
                        <a href="dutyhours/add?course_id=<?php echo $r["id"] ?>&cperiod_id=<?php echo $r["cperiod_id"] ?>"
                           class="btn btn-primary btn-large no-margin">
                            <i class="fa fa-plus-circle"></i>
                            <?php echo $translate->_("Log Hours"); ?>
                        </a>
                    <?php } ?>
                </td>
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>
</div>
