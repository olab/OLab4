
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
 * This file support deleting a department from the `departments` table.
 *
 * @author Organisation: Queen's University
 * @author Unit: MEdTech Unit
 * @author Developer: Don Zuiker <don.zuiker@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 *
*/

if (!defined("PARENT_INCLUDED") || !defined("IN_CONFIGURATION")) {
	exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "delete", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
?>
<h1>Deactivate a Department</h1>
<?php
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/manage/departments?section=delete&amp;org=".$ORGANISATION['organisation_id'], "title" => "Delete Departments");

	if (isset($_POST["remove_ids"]) && is_array($_POST["remove_ids"]) && !empty($_POST["remove_ids"])) {
		foreach ($_POST["remove_ids"] as $id) {
			$PROCESSED["remove_ids"][] = (int)$id;			
		}
	}
	
	
	if ($PROCESSED["remove_ids"]) {
		switch ($STEP) {
			case 2:
			 foreach ($PROCESSED["remove_ids"] as $id) {
					$query = "	UPDATE `" . AUTH_DATABASE . "`.`departments`
									SET	`department_active`=0,
									`updated_date`=" . $db->qstr(time()) . ",
									`updated_by`=" . $db->qstr($ENTRADA_USER->getID()) . "
									WHERE `department_id` = " . $db->qstr($id);
					if ($db->Execute($query)) {
						$SUCCESS++;
						$SUCCESSSTR[] = "Successfully removed Department [" . $id . "] from your the system.<br />You will now be redirected to the Department index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"" . ENTRADA_URL . "/admin/settings/manage/departments/?org=" . $ORGANISATION_ID . "\" style=\"font-weight: bold\">click here</a> to continue.";
					}
				}

				if ($SUCCESS) {
					echo display_success();
				}
				if ($NOTICE) {
					echo display_notice();
				}
				
				$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings/manage/departments/?org=".$ORGANISATION_ID."\\'', 5000)";
				break;
			case 1:
			default:
				
				add_notice("Please review the following Departments to ensure that you wish to <strong>delete</strong> them.");
				echo display_notice();
			?>


			<form action ="<?php echo ENTRADA_URL."/admin/settings/manage/departments/?section=delete&org=".$ORGANISATION_ID."&step=2";?>" method="post">
					<table class="tableList" cellspacing="0" summary="List of Departments">
						<colgroup>
							<col class="modified"/>
							<col class="title"/>
						</colgroup>
						<thead>
							<tr>
								<td class="modified">&nbsp;</td>
								<td class="title">Department</td>
							</tr>
						</thead>
						<tbody>
							<?php 
							foreach($PROCESSED["remove_ids"] as $id){
								$query = "SELECT * FROM `" . AUTH_DATABASE . "`.`departments` WHERE `department_id` = ".$db->qstr($id);
								$type = $db->GetRow($query);
							?>
							<tr>
								<td><input type="checkbox" value="<?php echo $id;?>" name ="remove_ids[]" checked="checked"/></td>
								<td><?php echo $type["department_title"];?></td>
							</tr>
							<?php } ?>
						</tbody>
					</table>
					<br />
					<input type="submit" value="Confirm Delete" class="btn btn-danger"/>
				</form>
		<?php
				break;
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "No Departments were selected to be deleted. You will now be redirected to the Department index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".ENTRADA_URL."/admin/settings/manage/departments/?org=".$ORGANISATION_ID."\" style=\"font-weight: bold\">click here</a> to continue.";

		echo display_error();
	}
	
}
