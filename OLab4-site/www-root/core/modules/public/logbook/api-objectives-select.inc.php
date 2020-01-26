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
 * Outputs a list of objectives available for logging if a course id has been selected.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_ENCOUNTER_TRACKING"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('encounter_tracking', 'read') && !$ENTRADA_ACL->amIAllowed("academicadvisor", "read", false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    $ajax = (isset($_GET["ajax"]) && $_GET["ajax"] ? true : false);
    
	if ($ajax) {
        ob_clear_open_buffers();
    } else {
        ?>
        <div class="control-group row-fluid">
            <label for="objective_id" class="form-required span3">Procedural Skills</label>
            <span class="controls span8" id="objectives-list-container">
        <?php
    }
    
    if (isset($_POST["course_id"]) && ((int)$_POST["course_id"])) {
        $course_id = ((int) $_POST["course_id"]);
    }
    
    if (isset($_POST["entry_id"]) && ((int)$_POST["entry_id"])) {
        $entry_id = ((int) $_POST["entry_id"]);
    }
    
    if (isset($_POST["objective_ids"]) && ($temp_objective_ids = explode(",", $_POST["objective_ids"])) && @count($temp_objective_ids)) {
        $objective_ids = $temp_objective_ids;
    }
    
    if (isset($course_id) && $course_id) {
        if (!isset($entry)) {
            if (isset($entry_id) && $entry_id) {
                $entry = Models_Logbook_Entry::fetchRow($entry_id);
                $entry->setCourseID($course_id);
            } else {
                $entry = new Models_Logbook_Entry();
                $entry->setCourseID($course_id);
            }
        }
        if (isset($objective_ids) && $objective_ids) {
            foreach ($objective_ids as $objective_id) {
                $entry->addObjective($objective_id);
            }
        }
        $objectives = $entry->getCourseObjectives();
        if (@count($objectives["required"]) || @count($objectives["logged"]) || @count($objectives["disabled"])) {
            ?>
            <select id="objective_id" name="objective_id" onchange="addObjective(this.options[this.selectedIndex].value, 3)">
                <option value="" selected="selected">--- Select an Objective ---</option>
                <?php
                if (@count($objectives["required"])) {
                    echo "<optgroup label=\"Required Objectives\" id=\"objectives-required\">\n";
                    foreach ($objectives["required"] as $objective) {
                        echo "<option id=\"objective-item-".$objective->getID()."\" value=\"".$objective->getID()."\">".html_encode($objective->getName())."</option>\n";
                    }
                    echo "</optgroup>\n";
                }
                if (@count($objectives["logged"])) {
                    echo "<optgroup label=\"Already Logged Objectives\" id=\"objectives-logged\">\n";
                    foreach ($objectives["logged"] as $objective) {
                        echo "<option id=\"objective-item-".$objective->getID()."\" class=\"logged\" value=\"".$objective->getID()."\">".html_encode($objective->getName())."</option>\n";
                    }
                    echo "</optgroup>\n";
                }
                echo "<optgroup label=\"Objectives Logged in this Entry\" id=\"objectives-entry\">\n";
                if (@count($objectives["disabled"])) {
                    foreach ($objectives["disabled"] as $objective) {
                        echo "<option id=\"objective-item-".$objective->getID()."\" value=\"".$objective->getID()."\" disabled=\"disabled\">".html_encode($objective->getName())."</option>\n";
                    }
                }
                echo "</optgroup>\n";
                ?>
            </select>
            <?php
        } else {
            add_notice("An issue was encountered while attempting to fetch the available logbook objectives for the selected course.");
            echo display_notice();
        }
    } else {
        add_notice("Please select a <strong>Rotation</strong> from list above to begin adding logbook objectives to this entry.");
        echo display_notice();
    }    
	if ($ajax) {
        exit;
    } else {
        ?>
            </span>
        </div>
        <?php
    }
}