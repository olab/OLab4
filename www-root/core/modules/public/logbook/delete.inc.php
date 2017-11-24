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
 * This file displays the delete entry interface.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_ENCOUNTER_TRACKING"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
		header("Location: ".ENTRADA_URL);
		exit;
} elseif (!$ENTRADA_ACL->amIAllowed('encounter_tracking', 'delete', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

	$BREADCRUMB[] = array("url" => ENTRADA_URL."/logbook?section=delete", "title" => "Delete Logbook Entry");
	
	echo "<h1>Delete Logbook Entry</h1>";
	
	if (isset($_POST["delete"])) {
		foreach ($_POST["delete"] as $entry_id) {
			if ($tmp_input = clean_input($entry_id, "numeric")) {
				$PROCESSED["delete"][] = $tmp_input;
				$entries[] = Models_Logbook_Entry::fetchRow($tmp_input);
			}
		}
	}
	
	switch ($STEP) {
		case 2 :
			foreach ($entries as $entry) {
				$entry_data = $entry->toArray();
				$entry_data["entry_active"] = 0;
				if ($entry->fromArray($entry_data)->update()) {
					add_statistic("encounter_tracking", "delete", "lentry_id", $entry->getID(), $ENTRADA_USER->getID());
					if (!$ERROR) {
						add_success("Successfully deleted a <strong>Logbook Entry</strong>. You will now be redirected to the logbook index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".ENTRADA_URL."/logbook\"><strong>click here</strong></a> to continue.");
					}
				} else {
					add_error("Failed to delete a <strong>Logbook Entry</strong>, an Administrator has been informed, please try again later. You will now be redirected to the logbook index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".ENTRADA_URL."/logbook\"><strong>click here</strong></a> to continue.");
					application_log("Failed to delete logbook entry, DB said: ".$db->ErrorMsg());
				}
			}
		break;
	}
	
	switch ($STEP) {
		case 2 :
			if ($ERROR) {
				echo display_error();
			}
			if ($SUCCESS) {
				echo display_success();
			}
			$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/logbook\\'', 5000)";
		break;
		case 1 :
		default :
		
			if (isset($entries) && is_array($entries)) { ?>
				<div class="alert alert-info">You have selected the following entries to be deleted. Please confirm below that you would like to delete them.</div>
				<form action="<?php echo html_encode(ENTRADA_URL); ?>/logbook?section=delete&step=2" method="POST" id="logbook-entry-list">
					<table class="table table-striped table-bordered" width="100%" cellpadding="0" cellspacing="0" border="0">
						<thead>
							<tr>
								<th width="5%"></th>
                                <th class="course">Course</th>
                                <th class="date">Encounter Date</th>
                                <th class="institution">Institution</th>
                                <th class="location">Setting</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($entries as $entry) { ?>
							<tr class="logbook-entry" data-id="<?php echo html_encode($entry->getID()); ?>">
								<td><input class="delete" type="checkbox" name="delete[<?php echo html_encode($entry->getID()); ?>]" value="<?php echo html_encode($entry->getID()); ?>" <?php echo html_encode((in_array($entry->getID(), $PROCESSED["delete"]) ? "checked=\"checked\"" : "")); ?> /></td>
								<td class="course"><a href="<?php echo html_encode(ENTRADA_URL); ?>/logbook?section=edit&entry_id=<?php echo html_encode($entry->getID()); ?>"><?php echo html_encode($entry->getCourseName()); ?></a></td>
								<td class="date"><a href="<?php echo html_encode(ENTRADA_URL); ?>/logbook?section=edit&entry_id=<?php echo html_encode($entry->getID()); ?>"><?php echo html_encode(date("F jS, Y", $entry->getEncounterDate())); ?></a></td>
								<td class="institution"><a href="<?php echo html_encode(ENTRADA_URL); ?>/logbook?section=edit&entry_id=<?php echo html_encode($entry->getID()); ?>"><?php echo html_encode($entry->getInstitution()); ?></a></td>
								<td class="location"><a href="<?php echo html_encode(ENTRADA_URL); ?>/logbook?section=edit&entry_id=<?php echo html_encode($entry->getID()); ?>"><?php echo html_encode($entry->getLocation()); ?></a></td>
							</tr>
							<?php } ?>
						</tbody>
					</table>
					<div class="row-fluid">
						<a href="<?php echo html_encode(ENTRADA_URL); ?>/logbook" class="btn" role="button">Cancel</a>
						<input type="submit" class="btn btn-primary pull-right" value="Delete" />
					</div>
				</form>
			<?php } else { ?>
				<div class="alert alert-info">No logbook entries have been selected to be deleted. Please <a href="<?php echo html_encode(ENTRADA_URL); ?>/logbook">click here</a> to return to the logbook index.</div>
			<?php }

		break;
	}
}