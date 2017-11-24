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
 * @author Organisation: University of Calgary
 * @author Unit: Faculty of Medicine
 * @author Developer: Doug Hall<hall@ucalgary.ca>
 * @copyright Copyright 2011 University of Calgary. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_GROUPS"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->amIAllowed('group', 'delete', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 5000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[]	= array("url" => "", "title" => $translate->_("Manage Cohorts Learners"));

	$GROUP_IDS = array();
	$MEMBERS = 0;

	// Error Checking
	switch($STEP) {
		case 2 :
			if ((isset($_POST["name"])) && isset($_POST["group_id"]) && ((int) trim($_POST["group_id"]))) { //Rename
				$GROUP_ID = (int) trim($_POST["group_id"]);
				break;
			}
		case 1 :
		default :
			if ((isset($_GET["gids"])) && ((int) trim($_GET["gids"])))  { // Rename cohort
				$GROUP_ID = (int) trim($_GET["gids"]);
			} elseif ((isset($_GET["mids"])) && ((int) trim($_GET["mids"])))  { // Delete Learner
				$MEMBERS = 1;
				$GROUP_IDS[] = (int) trim($_GET["mids"]);
			} elseif (isset($_GET["ids"])) {  // Delete groups
				$GROUP_IDS = array(htmlentities($_GET["ids"]));
			} elseif((!isset($_POST["checked"])) || (!is_array($_POST["checked"])) || (!@count($_POST["checked"]))) {
				header("Location: ".ENTRADA_URL."/admin/groups");
				exit;
			} else {
				foreach($_POST["checked"] as $group_id) {
					$group_id = (int) trim($group_id);
					if($group_id) {
						$GROUP_IDS[] = $group_id;
					}
				}
				if(!@count($GROUP_IDS)) {
					$ERROR++;
					if(isset($_POST["members"])) {
						$ERRORSTR[] = "There were no valid group member identifiers provided to delete. Please ensure that you access this section through the member index.";
						
					} else {
						$ERRORSTR[] = "There were no valid cohort identifiers provided to delete. Please ensure that you access this section through the group index.";
					}
				} elseif(isset($_POST["members"])) { 
					$MEMBERS = count($GROUP_IDS);
				}
			}

			if($ERROR) {
				$STEP = 1;
			}
		break;
	}

	// Display Page
	switch($STEP) {
		case 2 :
			if (isset($_POST["name"])) {  // Rename group
				$group_name = clean_input($_POST["name"], array("notags", "trim"));
				if (strlen($group_name) && strcmp($group_name,$_POST["group_name"])) {
					$result	= $db->GetOne("	SELECT `group_id` FROM `groups`
							WHERE `group_name` = '".$group_name."'");
					if ($result) {
						$ERROR++;
						$ERRORSTR[] = "The group name already exists in system.";
						$wait = 5000;
					} else {
						$db->Execute("UPDATE `groups` SET `group_name`='".$group_name."' WHERE `group_id` = ".$db->qstr($GROUP_ID));
					}
				}
				$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/groups?section=edit&ids=".implode(",", $_SESSION["ids"])."\\'', ".(isset($wait)?$wait:0).")";
			} elseif ($MEMBERS)  {  // Delete members
				foreach($GROUP_IDS as $gmember_id) {
                    $gmember_name = $db->GetOne("SELECT CONCAT_WS(', ', b.`lastname`, b.`firstname`) AS `fullname`
                                                 FROM `group_members` AS a
                                                 JOIN `".AUTH_DATABASE."`.`user_data` AS b
                                                 ON b.`id`=a.`proxy_id`
                                                 WHERE a.`gmember_id`=".$db->qstr($gmember_id));
					switch ($_POST["coa"]) {
						case "deactivate":
							$db->Execute("UPDATE `group_members` SET `member_active`='0' WHERE `gmember_id` = ".$db->qstr($gmember_id));
						break;
						case "activate":
							$db->Execute("UPDATE `group_members` SET `member_active`='1' WHERE `gmember_id` = ".$db->qstr($gmember_id));
						break;
						case "delete":
							$db->Execute("DELETE FROM `group_members` WHERE `gmember_id` = ".$db->qstr($gmember_id));
						break;
					}
                    add_success("Successfully ".$_POST["coa"]."d $gmember_name.");
				}
				$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/groups?section=edit&ids=".implode(",", $_SESSION["ids"])."\\'', 5000)";

			} else { // Delete groups
				$removed = array();

				foreach($GROUP_IDS as $group_id) {
					if($group_id = (int) $group_id) {
						switch ($_POST["coa"]) {
							case "deactivate":
								$db->Execute("UPDATE `groups` SET `group_active`='0' WHERE `group_id` = ".$db->qstr($group_id));
							break;
							case "activate":
								$db->Execute("UPDATE `groups` SET `group_active`='1' WHERE `group_id` = ".$db->qstr($group_id));
							break;
							case "delete":
								$query	= "	SELECT `group_id`,  `group_name`
											FROM `groups`
											WHERE `group_id` = ".$db->qstr($group_id);
								$result	= $db->GetRow($query);
								if ($result) {
									/**
									 * Remove all records from group_members table.
									 */
									$query = "DELETE FROM `group_members` WHERE `group_id` = ".$db->qstr($group_id);
									$db->Execute($query);
									$removed[$group_id]["group_name"] = $result["group_name"];
								}
								/**
								 * Remove group_id record from groups table.
								 */
								$query = "DELETE FROM `groups` WHERE `group_id` = ".$db->qstr($group_id);
								break;
						}
						$db->Execute($query);
						add_success("Successfully ".$_POST["coa"]."d the selected group.");
					}
				}
				$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/groups\\'', 5000)";
			}
			if ($ERROR) {
				echo display_error();
			}
            echo display_success();

		break;
		case 1 :
		default :

			
			if($ERROR) {
				echo display_error();
			} elseif ($GROUP_ID) { // Rename group
				echo "<h1>" . $translate->_("Rename Cohort") . "</h1>";

				$result	= $db->GetOne("	SELECT `group_name` FROM `groups`
							WHERE `group_id` =	".$db->qstr($GROUP_ID));
				if($result) {
					echo display_notice(array("Please choose a new name for the group"));
					?>
					<form action="<?php echo ENTRADA_URL; ?>/admin/groups?section=manage&amp;step=2" method="post">
						<input type="hidden" id="group_name" name="group_name" value="<?php echo $result;?>" />
						<input type="hidden" id="group_id" name="group_id" value="<?php echo $GROUP_ID;?>" />
						<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Learner">
							<colgroup>
								<col style="width: 3%" />
								<col style="width: 20%" />
								<col style="width: 77%" />
							</colgroup>
							<tfoot>
								<tr>
									<td colspan="2" />
									<td style="padding-top: 10px">
										<input type="submit" class="btn" value="Rename" />
									</td>
								</tr>
							</tfoot>
							<tbody>
								<tr>
									<td colspan="3" />
								</tr>
								<tr>
									<td></td>
									<td><label for="prefix" class="form-required"><?php echo $translate->_("Cohort"); ?> Name:</label></td>
									<td><input type="text" id="name" name="name" value="<?php echo html_encode($result); ?>" maxlength="255" style="width: 45%" /></td>
								</tr>
							</tbody>
						</table>
					</form>
					<?php }
	
			} elseif ($MEMBERS) {  // Delete members
				echo "<h1>De/Activate or Delete Learner".($MEMBERS>1?"s":"")."</h1>";

				$results = $db->getAll ("SELECT c.`gmember_id`, CONCAT_WS(' ', a.`firstname`, a.`lastname`) AS `fullname`,
										CONCAT_WS(':', b.`group`, b.`role`) AS `grouprole`, c.`group_id`, d.`group_name`, c.`member_active`
										FROM `".AUTH_DATABASE."`.`user_data` AS a
										LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
										ON a.`id` = b.`user_id`
                                        AND b.`app_id` IN (".AUTH_APP_IDS_STRING.")
										INNER JOIN `group_members` c ON a.`id` = c.`proxy_id`
										INNER JOIN `groups` d ON c.`group_id` = d.`group_id`
										WHERE c.`gmember_id`  IN (".implode(", ", $GROUP_IDS).")
                                        GROUP BY a.`id`
										ORDER by `grouprole`, `lastname`, `firstname`");
				if($results) {
					echo display_notice(array("Please review the following learner".($MEMBERS>1?"s":"")." to ensure that you wish to, deactivate, activate or <strong>permanently delete</strong> them from the cohort"));
					?>
					<form id="memberDelete" action="<?php echo ENTRADA_URL; ?>/admin/groups?section=manage&amp;step=2" method="post">
						<input type="hidden" name="members" value="1" />
						<input type="hidden" name="coa" id="coa" value="deactivate" />
						<table class="tableList" cellspacing="0" summary="List of Learner">
							<colgroup>
								<col class="modified" />
								<col class="community_title" />
								<col class="community_shortname" />
								<col class="community_shortname" />
								<col class="attachment" />
							</colgroup>
							<thead>
								<tr>
									<td class="modified" style="font-size: 12px">&nbsp;</td>
									<td class="community_title style="font-size: 12px">Name</td>
									<td class="community_shortname style="font-size: 12px"><?php echo $translate->_("Cohort"); ?></td>
									<td class="community_shortname style="font-size: 12px">Application Group & Role</td>
									<td class="attachment" style="font-size: 12px">&nbsp;</td>
								</tr>
							</thead>
							<tfoot>
								<tr>
                                    <?php
                                    
                                    $post_action = isset($_POST['coa']) ? $_POST['coa'] : false;
                                    switch ($post_action) {
                                        case 'activate':
                                            ?>
                                            <td style="padding-top: 10px" align="right" colspan="5">
                                                <input type="submit" class="btn btn-success" value="Activate" onClick="$('coa').value='activate'" />
                                            </td>
                                            <?php
                                        break;
                                        case 'delete':
                                            ?>
                                            <td style="padding-top: 10px" align="right" colspan="5">
                                                <input type="submit" class="btn btn-warning" value="Deactivate" onClick="$('coa').value='deactivate'" />
                                                <input type="submit" class="btn btn-danger" value="Delete" onClick="$('coa').value='delete'" />
                                            </td>
                                            <?php
                                        break;
                                        default:
                                            ?>
                                            <td style="padding-top: 10px" colspan="3">
                                                <input type="submit" class="btn btn-success" value="Activate" onClick="$('coa').value='activate'" />
                                            </td>
                                            <td style="padding-top: 10px" align="right" colspan="2">
                                                <input type="submit" class="btn btn-warning" value="Deactivate" onClick="$('coa').value='deactivate'" />
                                                <input type="submit" class="btn btn-danger" value="Delete" onClick="$('coa').value='delete'" />
                                            </td>
                                            <?php
                                        break;
                                    }
                                    
                                    ?>
								</tr>
							</tfoot>
							<tbody>
							<?php
								$url			= "";

								if($ENTRADA_ACL->amIAllowed('group', 'delete')) {
									foreach ($results as $result) {
										$url 	= ENTRADA_URL."/admin/groups?section=edit&amp;id=".$result["group_id"];
								
										echo "<tr id=\"group-".$result["group_id"]."\" class=\"event".((!$url) ? " np" : ((!$result["member_active"]) ? " na" : ""))."\">\n";
										echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"checked[]\" value=\"".$result["gmember_id"]."\" checked=\"checked\" /></td>\n";
										echo "	<td class=\"community_title".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Name: ".html_encode($result["fullname"])."\">" : "").html_encode($result["fullname"]).(($url) ? "</a>" : "")."</td>\n";
										echo "	<td class=\"community_shortname".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Cohort Name: ".html_encode($result["group_name"])."\">" : "").html_encode($result["group_name"]).(($url) ? "</a>" : "")."</td>\n";
										echo "	<td class=\"date".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Role: ".html_encode($result["grouprole"])."\">" : "").html_encode($result["grouprole"]).(($url) ? "</a>" : "")."</td>\n";
										echo "	<td class=\"attachment\">".(($url) ? "<a href=\"".ENTRADA_URL."/admin/groups?section=edit&amp;ids=".$result["group_id"]."\"><img src=\"".ENTRADA_URL."/images/action-edit.gif\" width=\"16\" height=\"16\" alt=\"Manage Cohort\" title=\"Manage Cohort\" border=\"0\" /></a>" : "<img src=\"".ENTRADA_URL."/images/pixel.gif\" width=\"16\" height=\"16\" alt=\"\" title=\"\" />")."</td>\n";
										echo "</tr>\n";
									}
								}
							?>
							</tbody>
						</table>
					</form>
				<?php
				}
			} else {
				echo "<h1>De/Activate or Delete " . $translate->_("Cohort") . "</h1>";

				$total_groups	= count($GROUP_IDS);

				$query = "	SELECT * FROM `groups`
							WHERE `group_id` IN (".implode(", ", $GROUP_IDS).")
							ORDER BY `group_name` ASC";

				$results	= $db->GetAll($query);

				if($results) {
					echo display_notice(array("Please review the following group".(($total_groups != 1) ? "s" : "")." to ensure that you wish to activate, deactivate or <strong>permanently delete</strong> ".(($total_groups != 1) ? "them" : "it").".<br /><br />Deleting will also remove any group members and this action cannot be undone."));
					?>
					<form action="<?php echo ENTRADA_URL; ?>/admin/groups?section=manage&amp;step=2" method="post">
						<input type="hidden" name="coa" id="coa" value="deactivate" />
						<table class="tableList" cellspacing="0" summary="List of Cohorts">
							<colgroup>
								<col class="modified" />
								<col class="community_title" />
								<col class="community_shortname" />
								<col class="community_opened" />
								<col class="attachment" />
							</colgroup>
							<thead>
								<tr>
									<td class="modified" style="font-size: 12px">&nbsp;</td>
									<td class="community_title style="font-size: 12px"><?php echo $translate->_("Cohort Name"); ?></td>
									<td class="community_shortname style="font-size: 12px">Number of learners</td>
									<td class="community_opened style="font-size: 12px">Updated Date</td>
									<td class="attachment" style="font-size: 12px">&nbsp;</td>
								</tr>
							</thead>
							<tfoot>
								<tr>
									<td />
                                    <td style="padding-top: 10px" colspan="2">
                                        <input type="submit" class="btn btn-primary" value="Activate" onClick="$('coa').value='activate'" />
                                    </td>
                                    <td style="padding-top: 10px" align="right" colspan="2">
                                        <input type="submit" class="btn btn-warning" value="Deactivate" onClick="$('coa').value='deactivate'" />
                                        <input type="submit" class="btn btn-danger" value="Delete" onClick="$('coa').value='delete'" />
                                    </td>
								</tr>
							</tfoot>
							<tbody>
							<?php
								foreach($results as $result) {
									$result["members"] = $db->GetOne("SELECT COUNT(*) AS members FROM  `group_members` WHERE `group_id` = ".$db->qstr($result["group_id"]));

									$url			= "";

									if($ENTRADA_ACL->amIAllowed('group', 'delete')) {
										$url 	= ENTRADA_URL."/admin/groups?section=edit&amp;id=".$result["group_id"];
								
										echo "<tr id=\"group-".$result["group_id"]."\" class=\"event".((!$url) ? " np" : ((!$result["group_active"]) ? " na" : ""))."\">\n";
										echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"checked[]\" value=\"".$result["group_id"]."\" checked=\"checked\" /></td>\n";
										echo "	<td class=\"community_title".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Cohort Name: ".html_encode($result["group_name"])."\">" : "").html_encode($result["group_name"]).(($url) ? "</a>" : "")."</td>\n";
										echo "	<td class=\"community_shortname".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Number of sembers: ".$result["members"]."\">" : "").$result["members"].(($url) ? "</a>" : "")."</td>\n";
										echo "	<td class=\"date".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Updated Date\">" : "").date("M jS Y", $result["updated_date"]).(($url) ? "</a>" : "")."</td>\n";
										echo "	<td class=\"attachment\">".(($url) ? "<a href=\"".ENTRADA_URL."/admin/groups?section=edit&amp;ids=".$result["group_id"]."\"><img src=\"".ENTRADA_URL."/images/action-edit.gif\" width=\"16\" height=\"16\" alt=\"Manage Cohort\" title=\"Manage Cohort\" border=\"0\" /></a>" : "<img src=\"".ENTRADA_URL."/images/pixel.gif\" width=\"16\" height=\"16\" alt=\"\" title=\"\" />")."</td>\n";
										echo "</tr>\n";
									}
								}
							?>
							</tbody>
						</table>
					</form>
					<?php
				} else {
					application_log("error", "The confirmation of removal query returned no results... curious Database said: ".$db->ErrorMsg());
					
					header("Location: ".ENTRADA_URL."/admin/groups");
					exit;	
				}
			}
		break;
	}
}
