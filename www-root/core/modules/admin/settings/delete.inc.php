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
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "delete", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else{
	
	echo "<h1>" . $translate->_("Delete Organisations") . "</h1>";
	
	if (isset($_POST["remove_ids"]) && is_array($_POST["remove_ids"]) && !empty($_POST["remove_ids"])) {
		
		if (count($_POST["remove_ids"]) > 0) {
			foreach ($_POST["remove_ids"] as $id) {
				if ($tmp = clean_input($id, "numeric")) {
					$PROCESSED["remove_ids"][] = $tmp;
				}
			}
		} else {
			$STEP = 1;
		}
		
		if (strtolower($ENTRADA_USER->getActiveGroup()) == "medtech" && strtolower($ENTRADA_USER->getActiveRole()) == "admin") {
			$query = "	SELECT a.*
						FROM `" . AUTH_DATABASE . "`.`organisations` AS a
						WHERE a.`organisation_active` = '1'
						AND a.`organisation_id` IN ('".implode("','",$PROCESSED["remove_ids"])."')
						ORDER BY a.`organisation_title` ASC";
		} else {
			$query = "	SELECT a.*
						FROM `".AUTH_DATABASE."`.`organisations` AS a
						JOIN `".AUTH_DATABASE."`.`user_access` AS b
						ON a.`organisation_id` = b.`organisation_id`
						WHERE b.`user_id` = ".$db->qstr($ENTRADA_USER->getID())."
						AND a.`organisation_active` = '1'
						AND a.`organisation_id` IN ('".implode("','",$PROCESSED["remove_ids"])."')
						GROUP BY a.`organisation_id`
						ORDER BY a.`organisation_title` ASC";
		}
		
		if ($organisations = $db->GetAssoc($query)) {
			switch ($STEP) {
				case "2" :
					foreach ($PROCESSED["remove_ids"] as $id) {
						$id = (int) $id;
						if ($id && $ENTRADA_ACL->amIAllowed(new ConfigurationResource($id), "delete") && ($ENTRADA_USER->GetActiveOrganisation() != $id)) {
							if (!$db->AutoExecute("`".AUTH_DATABASE."`.`organisations`", array("organisation_active" => "0"), "UPDATE", "`organisation_id` = ".$id)) {
								application_log("error", "Unable to deactivate organisation_id [".$id."]. Database said: ".$db->ErrorMsg());
								$ERROR++;
							}
						} else {
							application_log("error", "Unable to deactivate organisation_id [".$id."], user did not have delete permission.");
							$ERROR++;
						}
					}
					if (!$ERROR) {
						$success_string = "<br /><div style=\"padding-left: 15px; padding-bottom: 15px; font-family: monospace\">\n";
						foreach($organisations as $organisation_id => $result) {
							if (in_array($organisation_id, $PROCESSED["remove_ids"])) {
								$success_string .= html_encode($result["organisation_title"])."<br />";
							}
						}
						$success_string .= "</div>\n";
						add_success("You have successfully removed the following organisations from the system:<br />".$success_string."You will be automatically redirected to the event index in 5 seconds, or you can <a href=\"".ENTRADA_URL."/admin/settings\">click here</a> if you do not wish to wait.");
						echo display_success();
						$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings\'', 5000)";
					} else {
						add_error("An error occurred when trying to delete an organisation, a system administrator has been informed.");
						echo display_error();
						$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings\'', 5000)";
					}
				break;
				default :
					add_notice("Please review the following organisations to ensure that you wish to <strong>permanently delete</strong> them.");
					echo display_notice();
					?>
						<div id="organisations-section">
							<form action="<?php echo ENTRADA_URL; ?>/admin/settings?section=delete" method="POST">
								<input type="hidden" name="step" value="2" />
								<table class="tableList" cellspacing="0" cellpadding="1" border="0" summary="List of Organisations">
									<colgroup>
										<col class="modified" />
										<col class="title" />
									</colgroup>
									<thead>
										<tr>
											<td class="modified">&nbsp;</td>
											<td class="title">Title</td>
										</tr>
									</thead>
									<tbody>
										<?php
										foreach ($organisations as $organisation_id => $result) {
											$url = ENTRADA_URL."/admin/settings/manage?org=".(int) $organisation_id;

											echo "<tr>\n";
											echo "	<td><input type=\"checkbox\" name = \"remove_ids[]\" value = \"".html_encode($organisation_id)."\"".($ENTRADA_USER->GetActiveOrganisation() == $organisation_id ? " disabled=\"disabled\"" : "")." ".(in_array($organisation_id, $PROCESSED["remove_ids"]) ? " checked=\"checked\"" : "")."/></td>\n";
											echo "	<td><a href=\"".$url."\">".html_encode($result["organisation_title"])."</a></td>\n";
											echo "</tr>\n";
										}
										?>
									</tbody>
								</table><br />
								<input type="submit" class="btn btn-danger" value="<?php echo $translate->_("Delete Selected"); ?>" />
							</form>
						</div>
					<?php
				break;
			}
		}
	} else {
		add_error("No organisations have been selected for deletion. You will be redirected to the system settings page in 5 seconds, or you may <a href=\"".ENTRADA_URL."/admin/settings\">click here</a> if you do not wish to wait.");
		echo display_error();
		$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings\'', 5000)";
	}


	
}