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
 * Form for adding and editing duty hours
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
// set defaults for a new log entry
$hours = 0;
$encounter_date = 0;
$comments = "";
$dhentry_id = "";
$active = 1;
$hours_type = "on_duty";

// handle view data
$ENTRADA_USER = (isset($this->data["ENTRADA_USER"])) ? $this->data["ENTRADA_USER"] : null;
$entry = (isset($this->data["entry"])) ? $this->data["entry"] : null;
// for new entries
$course_id = (isset($this->data["course_id"])) ? $this->data["course_id"] : null;
$cperiod_id = (isset($this->data["cperiod_id"])) ? $this->data["cperiod_id"] : null;

// if there is a log entry get its values
if (isset($entry)) {
    $encounter_date = $entry->getEncounterDate();
    $hours = $entry->getHours();
    $comments = $entry->getComments();
    $dhentry_id = $entry->getID();
    $course_id = $entry->getCourseID();
    $cperiod_id = $entry->getCurriculumPeriodID();
    $active = $entry->getEntryActive();
    $hours_type = $entry->getHoursType();
}
?>

<!-- Provide a display for any notices, warnings or errors zto the user -->
<div id="message-box" class="alert alert-block hide">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <p id="message-text"></p>
</div>

<form class="form" id="entry-form">
    <input type="hidden" id="entry_id" class="entry_id" name="entry_id" value="<?php echo $dhentry_id; ?>"/>
    <input type="hidden" id="student_id" class="student_id" name="student_id"
           value="<?php echo $ENTRADA_USER->getId(); ?>"/>
    <input type="hidden" id="course_id" class="course_id" name="course_id" value="<?php echo $course_id; ?>"/>
    <input type="hidden" id="cperiod_id" class="cperiod_id" name="cperiod_id" value="<?php echo $cperiod_id; ?>"/>
    <input type="hidden" id="active" class="active" name="active" value="<?php echo $active; ?>"/>

    <div id="entry_form_controls" class="">
        <script type="application/javascript">

            jQuery(function ($) {

                $(".add-on.date").on("click", function () {
                    $("#encounter_date").focus();
                });

                $(".datepicker").datepicker({
                    dateFormat: "yy-mm-dd"
                });

                $(".add-on.time").on("click", function () {
                    $("#encounter_time").focus();
                });

                jQuery(".timepicker").timepicker({
                    showPeriodLabels: false
                });
            });

        </script>
        <div class="control-group controls-row">
            <label for="hours_type" class="control-label form-required">
                <?php echo $translate->_("Hours Type"); ?>
            </label>
            <div class="btn-group btn-group-lg pull-left">
                <label for="on_duty_type" class="btn btn-default">
                    <input type="radio" name="hours_type" value="on_duty" id="on_duty_type" <?php echo ($hours_type == "on_duty") ? "checked" : ""; ?> />
                    <?php echo Models_Duty_Hours::getHoursTypeLabel("on_duty"); ?>
                </label>
                <label for="off_duty_type" class="btn btn-default">
                    <input type="radio" name="hours_type" value="off_duty" id="off_duty_type" <?php echo ($hours_type == "off_duty") ? "checked" : ""; ?> />
                    <?php echo Models_Duty_Hours::getHoursTypeLabel("off_duty"); ?>
                </label>
                <label for="absence_type" class="btn btn-default">
                    <input type="radio" name="hours_type" value="absence" id="absence_type" <?php echo ($hours_type == "absence") ? "checked" : ""; ?> />
                    <?php echo Models_Duty_Hours::getHoursTypeLabel("absence"); ?>
                </label>
            </div>
        </div>
        <?php
        ?>
        <div class="control-group">
            <label for="encounter_date" class="form-required control-label"><?php echo $translate->_("Encounter Date:"); ?> </label>
            <div class="input-append">
                <input id="encounter_date" type="text" class="input-small datepicker"
                       value="<?php echo ($encounter_date) ? date("Y-m-d", $encounter_date) : date("Y-m-d", time()); ?>"
                       name="encounter_date"/>
                <span class="add-on date pointer"><i class="icon-calendar"></i></span>
            </div>
            <div class="input-append">
                <input type="text" class="input-mini timepicker"
                       value="<?php echo ($encounter_date) ? date("H:i", $encounter_date) : date("H:i", time()); ?>"
                       name="encounter_time" id="encounter_time"/>
                <span class="add-on time pointer"><i class="icon-time"></i></span>
            </div>
        </div>
        <div class="control-group" id="hours-control-group">
            <label for="hours" class="control-label form-required">
                <?php echo $translate->_("Hours"); ?>
            </label>
            <div class="controls">
                <input type="text" id="hours" name="hours" class="numbers-only input-mini"
                       value="<?php echo $hours; ?>"/>
            </div>
        </div>
        <div class="control-group">
            <label for="comments" class="control-label">
                <?php echo $translate->_("Comments"); ?>
            </label>
            <div class="controls">
                <textarea class="full-width expandable" id="comments" name="comments"
                          placeholder="add comments here"><?php echo $comments; ?></textarea>
            </div>
        </div>
        <div class="control-group">
            <div class="controls">
                <!-- ua-msf : Case 2216 : Use jQuery to save/delete to allow better field validation -->
                <?php if ($dhentry_id == "") { ?>
                    <a href="rotation?id=<?php echo $course_id ?>&cperiod_id=<?php echo $cperiod_id ?>"
                       class="btn btn-default btn-large pull-left"><?php echo $translate->_("Cancel"); ?></a>
                <?php } else { ?>
                    <button id="duty_hours_delete" type="button" class="btn btn-danger btn-large pull-left"><?php echo $translate->_("Delete"); ?></button>
                <?php } ?>
                <button id="duty_hours_save" type="button" class="btn btn-primary btn-large pull-right"><?php echo $translate->_("Submit"); ?></button>
            </div>
        </div>
    </div>

</form>

