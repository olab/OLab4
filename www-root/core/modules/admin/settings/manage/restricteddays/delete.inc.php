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
 * This file displays the delete restricted days interface.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_CONFIGURATION"))) {
	exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
		header("Location: ".ENTRADA_URL);
		exit;
} elseif (!$ENTRADA_ACL->amIAllowed('configuration', 'delete', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/manage/restricteddays?org=".$ORGANISATION_ID."&section=delete", "title" => "Delete Restricted Days");
	
	echo "<h1>Delete Restricted Days</h1>";
	
	if (isset($_POST["remove_ids"])) {
		foreach ($_POST["remove_ids"] as $day_id) {
			if ($tmp_input = clean_input($day_id, "numeric")) {
				$PROCESSED["delete"][] = $tmp_input;
				$days[] = Models_RestrictedDays::fetchRow($tmp_input);
			}
		}
	}
	
	switch ($STEP) {
		case 2 :
			foreach ($days as $day) {
				$day_data = $day->toArray();
				$day_data["day_active"] = 0;
				if ($day->fromArray($day_data)->update()) {
					add_statistic("restricted_days", "delete", "orday_id", $day->getID(), $ENTRADA_USER->getID());
					if (!$ERROR) {
						add_success("Successfully deleted the restricted day '<strong>".$day->getName()."</strong>'. You will now be redirected to the restricted days index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".ENTRADA_URL."/admin/settings/manage/restricteddays?org=".$ORGANISATION_ID."\"><strong>click here</strong></a> to continue.");
					}
				} else {
					add_error("Failed to delete the restricted day '<strong>".$day->getName()."</strong>', an Administrator has been informed, please try again later. You will now be redirected to the restricted days index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".ENTRADA_URL."/admin/settings/manage/restricteddays?org=".$ORGANISATION_ID."\"><strong>click here</strong></a> to continue.");
					application_log("Failed to delete restricted day, DB said: ".$db->ErrorMsg());
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
			$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings/manage/restricteddays?org=".$ORGANISATION_ID."\\'', 5000)";
		break;
		case 1 :
		default :
		
			if (isset($days) && is_array($days)) { ?>
				<div class="alert alert-info">You have selected the following restricted days to be deleted. Please confirm below that you would like to delete them.</div>
				<form action="<?php echo html_encode(ENTRADA_URL); ?>/admin/settings/manage/restricteddays?org=<?php echo $ORGANISATION_ID; ?>&section=delete&step=2" method="POST" id="restricted-days-list">
					<table class="table table-striped table-bordered" width="100%" cellpadding="0" cellspacing="0" border="0">
						<thead>
							<tr>
								<th width="5%"></th>
								<th>Restricted Day</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($days as $day) { ?>
							<tr class="restricted-day" data-id="<?php echo html_encode($day->getID()); ?>">
								<td><input class="delete" type="checkbox" name="remove_ids[<?php echo html_encode($day->getID()); ?>]" value="<?php echo html_encode($day->getID()); ?>" <?php echo html_encode((in_array($day->getID(), $PROCESSED["delete"]) ? "checked=\"checked\"" : "")); ?> /></td>
								<td class="name"><a href="<?php echo html_encode(ENTRADA_URL); ?>/admin/settings/manage/restricteddays?org=<?php echo $ORGANISATION_ID; ?>&section=edit&day_id=<?php echo html_encode($day->getID()); ?>"><?php echo html_encode($day->getName()); ?></a></td>
							</tr>
							<?php } ?>
						</tbody>
					</table>
					<div class="row-fluid">
						<a href="<?php echo html_encode(ENTRADA_URL); ?>/admin/settings/manage/restricteddays?org=<?php echo $ORGANISATION_ID; ?>" class="btn" role="button">Cancel</a>
						<input type="submit" class="btn btn-primary pull-right" value="Delete" />
					</div>
				</form>
			<?php } else { ?>
				<div class="alert alert-info">No restricted days have been selected to be deleted. Please <a href="<?php echo html_encode(ENTRADA_URL); ?>/admin/settings/manage/restricteddays?org=<?php echo $ORGANISATION_ID; ?>">click here</a> to return to the restricted days index.</div>
			<?php }

		break;
	}
}