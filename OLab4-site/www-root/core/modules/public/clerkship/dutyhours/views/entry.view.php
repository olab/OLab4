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

// handle view data
$ENTRADA_USER = (isset($this->data["ENTRADA_USER"])) ? $this->data["ENTRADA_USER"] : null;
$entry = (isset($this->data["entry"])) ? $this->data["entry"] : null;

?>

<!-- Provide a display for any notices, warnings or errors zto the user -->
<div id="message-box" class="alert alert-block hide">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <p id="message-text"></p>
</div>

<div id="entry_form_controls" class="">
    <div class="control-group controls-row">
        <label for="hours_type" class="control-label">
            <?php echo $translate->_("Hours Type"); ?>
        </label>
        <div class="controls">
            <?php echo Models_Duty_Hours::getHoursTypeLabel($entry->getHoursType()) ?>
        </div>
    </div>
    <div class="control-group controls-row">
        <label for="hours_type" class="control-label">
            <?php echo $translate->_("Encounter Date"); ?>
        </label>
        <div class="controls">
            <?php echo date(DEFAULT_DATETIME_FORMAT, $entry->getEncounterDate()) ?>
        </div>
    </div>
    <div class="control-group controls-row">
        <label for="hours_type" class="control-label">
            <?php echo $translate->_("Hours"); ?>
        </label>
        <div class="controls">
            <?php echo $entry->getHours() ?>
        </div>
    </div>
    <div class="control-group controls-row">
        <label for="hours_type" class="control-label">
            <?php echo $translate->_("Comments"); ?>
        </label>
        <div class="controls">
            <?php echo $entry->getComments() ?>
        </div>
    </div>
</div>


