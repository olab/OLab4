
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
 * @author Organisation: Queen's University
 * @author Unit: MEdTech Unit
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
 * @copyright Copyright 2011 Queen's University. All Rights Reserved.
 *
*/

if (!defined("PARENT_INCLUDED") || !defined("IN_CONFIGURATION")) {
	exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "delete",false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
?>
<h1>Delete <?php echo $translate->_("Event Type"); ?></h1>
<?php
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/manage/eventtypes?section=delete&amp;org=".$ORGANISATION['organisation_id'], "title" => "Delete " . $translate->_("Event Type"));

	if (isset($_POST["remove_ids"]) && is_array($_POST["remove_ids"]) && !empty($_POST["remove_ids"])) {
		foreach ($_POST["remove_ids"] as $id){
			$PROCESSED["remove_ids"][] = (int) $id;
		}
	}
	
	if ($PROCESSED["remove_ids"]) {
		switch($STEP){
			case 2:
				foreach($_POST["remove_ids"] as $id){

					$query = "SELECT COUNT(*) FROM `eventtype_organisation` WHERE `eventtype_id` = ".$db->qstr($id);

					$num_uses = $db->GetOne($query);

					$query = "DELETE FROM `eventtype_organisation` WHERE `eventtype_id` = ".$db->qstr($id);
					if($num_uses > 1)
						$query .= " AND	`organisation_id` = ".$db->qstr($ORGANISATION_ID);
					if($db->Execute($query)){
						$SUCCESS++;
						$SUCCESSSTR[] = "Successfully removed " . $translate->_("Event Type") . " [".$id."] from your organisation.<br />";
					}
					if($num_uses > 1){
						$NOTICE++;
						$NOTICESTR[] = "This " . $translate->_("Event Type") . " still exists in the system because other Organisations were using it.<br />You will now be redirected to the " . $translate->_("Learning Event Types") . " index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".ENTRADA_URL."/admin/settings/manage/eventtypes/?org=".$ORGANISATION_ID."\" style=\"font-weight: bold\">click here</a> to continue.";
					}
					else{
						$query = "UPDATE `events_lu_eventtypes` SET	`eventtype_active`=0 WHERE `eventtype_id` = ".$db->qstr($id);
						if($db->Execute($query)){
							$SUCCESS++;
							$SUCCESSSTR[] = "Successfully removed " . $translate->_("Event Type") . " [".$id."] from your the system.<br />You will now be redirected to the " . $translate->_("Event Type") . " index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".ENTRADA_URL."/admin/settings/manage/eventtypes/?org=".$ORGANISATION_ID."\" style=\"font-weight: bold\">click here</a> to continue.";
						}
						else{
							$ERROR++;
							$ERRORSTR[] = "An error occurred while removing the " . $translate->_("Event Type") . " [".$id."] from the system. The system administrator has been notified.You will now be redirected to the " . $translate->_("Learning Event Types") . " index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".ENTRADA_URL."/admin/settings/manage/eventtypes/?org=".$ORGANISATION_ID."\" style=\"font-weight: bold\">click here</a> to continue.";
							application_log("error", "An error occurred while removing the Event Type [".$id."] from the system. ");
						}
					}
				}


				if($SUCCESS)
					echo display_success();
				if($NOTICE)
					echo display_notice();
				$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings/manage/eventtypes/?org=".$ORGANISATION_ID."\\'', 5000)";
				break;
			case 1:
			default:

						
				add_notice("Please review the following " . $translate->_("Learning Event Types") . " to ensure that you wish to <strong>permanently delete</strong> them.");
				echo display_notice();
			?>


			<form action ="<?php echo ENTRADA_URL."/admin/settings/manage/eventtypes/?section=delete&org=".$ORGANISATION_ID."&step=2";?>" method="post">
					<table class="tableList" cellspacing="0" summary="List of Curriculum Layout">
						<colgroup>
							<col class="modified"/>
							<col class="title"/>
						</colgroup>
						<thead>
							<tr>
								<td class="modified">&nbsp;</td>
								<td class="title"><?php echo $translate->_("Event Type"); ?></td>
							</tr>
						</thead>
						<tbody>
							<?php 
								foreach ($PROCESSED["remove_ids"] as $id) {
									$t = Models_EventType::get($id);
                                    if ($t) {
                                        $type = $t->toArray();
								?>
							<tr>
								<td><input type="checkbox" value="<?php echo $id;?>" name ="remove_ids[]" checked="checked"/></td>
								<td><?php echo $type["eventtype_title"];?></td>
							</tr>
							<?php 
                                    }
                                } ?>
						</tbody>
					</table>
					<br />
					<input type="submit" value="Confirm Delete" class="btn btn-danger"/>
				</form>
				<?php

					break;				
		}

			
	}
	else{
		$ERROR++;
		$ERRORSTR[] = "No " . $translate->_("Learning Event Types") . " were selected to be deleted. You will now be redirected to the " . $translate->_("Learning Event Types") . " index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".ENTRADA_URL."/admin/settings/manage/eventtypes/?org=".$ORGANISATION_ID."\" style=\"font-weight: bold\">click here</a> to continue.";

		echo display_error();
	}
}
