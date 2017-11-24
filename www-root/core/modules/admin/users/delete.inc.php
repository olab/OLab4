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
 * Allows administrators to remove user access from the entrada_auth.user_access table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_USERS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("user", "delete", false)) {
	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[] = array("url" => "", "title" => "Delete Users");
	$PROXY_IDS = array();

	echo "<h1>Delete Users</h1>";

	// Error Checking
	switch ($STEP) {
		case 2 :
		case 1 :
		default :
			if ((!isset($_POST["delete"])) || (!is_array($_POST["delete"])) || (!count($_POST["delete"]))) {
				$ERROR++;
				$ERRORSTR[] = "You must select at least 1 user to delete by checking the checkbox to the left their name.";

				application_log("notice", "Users delete page accessed without providing any user id's to delete.");
			} else {
				foreach ($_POST["delete"] as $proxy_id) {
					if ($proxy_id = (int) trim($proxy_id)) {
						$PROXY_IDS[] = $proxy_id;
					}
				}

				if (!count($PROXY_IDS)) {
					$ERROR++;
					$ERRORSTR[] = "There were no valid user identifiers provided to delete. Please ensure that you access this section through the user index.";
				}
			}

			if ($ERROR) {
				$STEP = 1;
			}
		break;
	}

	// Display Page
	switch ($STEP) {
		case 2 :
			/**
			 * @todo Add option of deleting or suspending Google account if it exists:
			 */
			foreach ($PROXY_IDS as $proxy_id) {
				$query	= "SELECT `id`, CONCAT_WS(', ', `lastname`, `firstname`) AS `fullname`, `organisation_id`  FROM `".AUTH_DATABASE."`.`user_data` WHERE `id` = ".$db->qstr($proxy_id);
				$result	= $db->GetRow($query);
				if ($result) {
					if ($ENTRADA_ACL->amIAllowed(new UserResource($proxy_id, $result['organisation_id']), 'delete')) {
						$query = "DELETE FROM `".AUTH_DATABASE."`.`user_access` WHERE `user_id` = ".$db->qstr($proxy_id)." AND `app_id` = ".$db->qstr(AUTH_APP_ID)." LIMIT 1";
						if ($db->Execute($query)) {
							$SUCCESS++;
							$SUCCESSSTR[] = "Successfully removed ".html_encode($result["fullname"])."'s access from this application.";

							application_log("success", "Proxy ID [".$ENTRADA_USER->getID()."] removed [".$result["fullname"]."] from user_access table for app_id [".AUTH_APP_ID."].");
						} else {
							$ERROR++;
							$ERRORSTR[] = "Unable to remove ".html_encode($result["fullname"])."'s access from this application at this time.";

							application_log("error", "Unable to remove user_id [".$proxy_id."] entry from user_access table. Database said: ".$db->ErrorMsg());
						}
					} else {
						$ERROR++;
						$ERRORSTR[] = "Unable to remove ".html_encode($result["fullname"])."'s access from this application at this time because you do not have permissions to delete this user.";

						application_log("error", "Unable to remove user_id [".$proxy_id."] entry due to permissions error.");
					}
				} else {
					$ERROR++;
					$ERRORSTR[] = "Unable to remove <strong>".html_encode($proxy_id)."</strong> access from this application at this time.";

					application_log("error", "Proxy ID [".$ENTRADA_USER->getID()."] tried to remove proxy_id [".$proxy_id."], but the database query failed. Database said: ".$db->ErrorMsg());
				}
			}

			$url			= ENTRADA_URL."/admin/users";

			$SUCCESS++;
			$SUCCESSSTR[]	= "You will now be redirected to the users index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";

			$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000)";

			if ($SUCCESS) {
				echo display_success();
			}

			if ($ERROR) {
				echo display_error();
			}
		break;
		case 1 :
		default :
			if ($NOTICE) {
				echo display_notice();
			}

			if ($ERROR) {
				echo display_error();
			}

			if (count($PROXY_IDS)) {
				$query	= "
							SELECT a.*, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, b.`account_active`, b.`access_starts`, b.`access_expires`, b.`last_login`, b.`role`, b.`group`
							FROM `".AUTH_DATABASE."`.`user_data` AS a
							LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
							ON b.`user_id` = a.`id`
							AND b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
							WHERE b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
							AND a.id IN (".implode(", ", $PROXY_IDS).")
							ORDER BY `fullname` ASC";
				$results	= $db->GetAll($query);
				if ($results) {
					echo display_notice(array("Please review the following users to ensure that you wish to remove their access from this system."));
					?>
					<form action="<?php echo ENTRADA_URL; ?>/admin/users?section=delete&amp;step=2" method="post">
						<table class="tableList" cellspacing="0" summary="List of Users To Delete">
							<colgroup>
								<col class="modified" />
								<col class="title" />
								<col class="general" />
								<col class="general" />
								<col class="date" />
							</colgroup>
							<thead>
								<tr>
									<td class="modified">&nbsp;</td>
									<td class="title sortedASC" style="font-size: 12px"><div class="noLink">Full Name</div></td>
									<td class="general" style="font-size: 12px">Username</td>
									<td class="general" style="font-size: 12px">Group &amp; Role</td>
									<td class="date" style="font-size: 12px">Last Login</td>
								</tr>
							</thead>
							<tfoot>
								<tr>
									<td></td>
									<td colspan="4" style="padding-top: 10px">
										<input type="submit" class="btn btn-danger" value="Delete Selected" />
									</td>
								</tr>
							</tfoot>
							<tbody>
							<?php
							foreach ($results as $result) {
								$can_login = true;
								$url = ENTRADA_URL."/admin/users?section=edit&amp;id=".$result["id"];

								if ($result["account_active"] == "false") {
									$can_login = false;
								}

								if (($access_starts = (int) $result["access_starts"]) && ($access_starts > time())) {
									$can_login = false;
								}
								if (($access_expires = (int) $result["access_expires"]) && ($access_expires < time())) {
									$can_login = false;
								}

								echo "<tr class=\"user".((!$can_login) ? " na" : "")."\">\n";
								echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"delete[]\" value=\"".$result["id"]."\" checked=\"checked\" /></td>\n";
								echo "	<td class=\"title\">".(($url) ? "<a href=\"".$url."\" title=\"Edit Account: ".html_encode($result["fullname"])."\">" : "").html_encode($result["fullname"]).(($url) ? "</a>" : "")."</td>\n";
								echo "	<td class=\"general\">".(($url) ? "<a href=\"".$url."\" title=\"Edit Account: ".html_encode($result["fullname"])."\">" : "").html_encode($result["username"]).(($url) ? "</a>" : "")."</td>\n";
								echo "	<td class=\"general\">".(($url) ? "<a href=\"".$url."\" title=\"Edit Account: ".html_encode($result["fullname"])."\">" : "").ucwords($result["group"])." &rarr; ".ucwords($result["role"]).(($url) ? "</a>" : "")."</td>\n";
								echo "	<td class=\"date\">".(($url) ? "<a href=\"".$url."\" title=\"Edit Account: ".html_encode($result["fullname"])."\">" : "").(((int) $result["last_login"]) ? date(DEFAULT_DATE_FORMAT, (int) $result["last_login"]) : "Never Logged In").(($url) ? "</a>" : "")."</td>\n";
								echo "</tr>\n";
							}
							?>
							</tbody>
						</table>
					</form>
					<?php
				}
			}
		break;
	}
}