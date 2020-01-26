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
 * List of logged rotaion/duty hours for a student
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

// handle view data
$ENTRADA_USER = (isset($this->data["ENTRADA_USER"])) ? $this->data["ENTRADA_USER"] : null;
$course_id = (isset($this->data["course_id"])) ? $this->data["course_id"] : null;
$cperiod_id = (isset($this->data["cperiod_id"])) ? $this->data["cperiod_id"] : null;
$can_edit = (isset($this->data["can_edit"])) ? $this->data["can_edit"] : null;
$entries = (isset($this->data["entries"])) ? $this->data["entries"] : null;

?>
<?php if (Models_Duty_Hours::isUserInCourseAudience(null, $course_id)) { ?>
    <?php if ($can_edit) { ?>
        <div class="control-group row-fluid">
            <a href="add?course_id=<?php echo $course_id ?>&cperiod_id=<?php echo $cperiod_id ?>"
               class="btn btn-primary btn-large pull-right">
                <i class="fa fa-plus-circle"></i>
                <?php echo $translate->_("Log Hours"); ?>
            </a>
        </div>
    <?php } ?>
    <?php if (!empty($entries)) { ?>
        <div class="control-group row-fluid">
            <table class="table table-striped">
                <thead>
                <th class="span1"></th>
                <th class="span5 pull-left"><?php echo $translate->_("Date"); ?></th>
                <th class="span2"><?php echo $translate->_("Hours"); ?></th>
                <th class="span4 pull-left"><?php echo $translate->_("Notes"); ?></th>
                </thead>

                <tbody>
                <?php
                foreach ($entries as $entry) {
                    $view_edit_link = ($can_edit) ? "edit?id=" . $entry->getID() : "view?id=" . $entry->getID();
                    ?>
                    <tr>
                        <td class="alignRight">
                            <a href="<?php echo $view_edit_link ?>" class="">
                                <i class="icon-edit"></i>
                            </a>
                        </td>
                        <td>
                            <a href="<?php echo $view_edit_link ?>">
                                <?php
                                //ua-msf : Case 2477 : no hours for off_duty and absences entry types
                                if ($entry->getHoursType() == "on_duty") {
                                    // ua-msf : Case 2249 : Display start to end times for log entries list
                                    $start_date = date(DEFAULT_DATETIME_FORMAT, $entry->getEncounterDate());
                                    $minutes = (int)($entry->getHours() * 60);
                                    // user defined time formats can break the strtotime calculation
                                    $start_date_simple = date("Y/m/d H:i:s", $entry->getEncounterDate());
                                    $end_time = strtotime("{$start_date_simple} +{$minutes} minutes");
                                    $end_date = date(DEFAULT_DATETIME_FORMAT, $end_time);
                                    echo $start_date . " - " . $end_date;
                                } else {
                                    echo date(DEFAULT_DATETIME_FORMAT, $entry->getEncounterDate());
                                }
                                ?>
                            </a>
                        </td>
                        <td class="center">
                            <?php echo ($entry->getHoursType() == "on_duty") ? $entry->getHours() : $entry->getHoursTypeLabel($entry->getHoursType()); ?>
                        </td>
                        <td class=""><?php echo $entry->getComments() ?></td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
        </div>
    <?php } else { ?>
        <div class="display-notice">
            <p><?php echo $translate->_("No hours were logged for this course yet."); ?></p>
        </div>
    <?php } ?>
<?php } else { ?>
    <div class="display-notice">
        <p>
            <?php echo "Your account does not have the permissions required to use this feature of this module." ?>
        </p>
    </div>
<?php } ?>
