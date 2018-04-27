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
 * @copyright Copyright 2017 University of Calgary. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_GROUPS"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->amIAllowed('group', 'delete', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 5000)";

    add_error(sprintf($translate->_("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%s\"> %s </a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

	$URL = ENTRADA_URL."/admin/$MODULE";

	$GROUP_IDS = array();
	$MEMBERS = 0;

	$post_action = isset($_POST["coa"]) ? $_POST["coa"] : false;

	// Error Checking
	switch($STEP) {
		case 2 :
			if ((isset($_POST["name"])) && isset($_POST["group_id"]) && ((int) trim($_POST["group_id"]))) { //Rename
				$GROUP_ID = (int) trim($_POST["group_id"]);
				$edit = "?section=edit&id=$GROUP_ID";
				break;
			}
		case 1 :
		default :
			$edit = "?section=edit";						
			if ((isset($_GET["gids"])) && ((int) trim($_GET["gids"])))  { // Rename cohort
				$GROUP_ID = (int) trim($_GET["gids"]);
				$edit = "?section=edit&id=$GROUP_ID";
			} elseif ((isset($_GET["mids"])) && ((int) trim($_GET["mids"])))  { // Delete Learner
				$MEMBERS = 1;
				$GROUP_IDS[] = (int) trim($_GET["mids"]);
			} elseif (isset($_GET["ids"])) {  // Delete groups
				$GROUP_IDS = array(htmlentities($_GET["ids"]));
			} elseif((!isset($_POST["checked"])) || (!is_array($_POST["checked"])) || (!@count($_POST["checked"]))) {
				header("Location: $URL");
				exit;
			} else {
				foreach($_POST["checked"] as $group_id) {
					$group_id = (int) trim($group_id);
					if($group_id) {
						$GROUP_IDS[] = $group_id;
					}
				}
				if(!@count($GROUP_IDS)) {
					if(isset($_POST["members"])) {
						add_error($translate->_("There were no valid group member identifiers provided to delete. Please ensure that you access this section through the member index."));
						
					} else {
						add_error($translate->_("There were no valid cohort identifiers provided to delete. Please ensure that you access this section through the group index."));
					}
				} elseif(isset($_POST["members"])) { 
					$MEMBERS = count($GROUP_IDS);
				}
			}

			if($ERROR) {
				$STEP = 1;
			}
			if (strlen($edit)) {
				$BREADCRUMB[] = array("url" => "${URL}${edit}", "title" => $translate->_("Edit Cohort"));
			}
		break;
	}

	// Display Page
	switch($STEP) {
		case 2 :
			if (isset($_POST["name"])) {  // Rename group
				$group_name = clean_input($_POST["name"], array("notags", "trim"));
				$old_name = Models_Group::getName($GROUP_ID);
				if (strlen($group_name) && strcmp($group_name,$_POST["group_name"])) {
					if (Models_Group::updateName($GROUP_ID, $group_name)) {
						add_success(sprintf($translate->_("Successfully renamed group <b>%s</b> to <b>%s</b>."), $old_name, $group_name));
					} else {
						add_error($translate->_("Could not rename group."));
					}
				}
			} elseif ($MEMBERS)  {  // Delete members
				foreach($GROUP_IDS as $gmember_id) {
					$name = Models_Group_Member::doAction($gmember_id, $post_action);
                    add_success(sprintf($translate->_("Successfully <b>%s</b> <b>%s</b>."), $post_action . "d", $name));
                }

			} else { // Delete groups
				foreach($GROUP_IDS as $group_id) {
					if ($group_id = (int) $group_id) {
						$name = Models_Group::doAction($group_id, $post_action);
						if (isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["ids"])&&!strcmp($post_action,"delete")) {
							$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["ids"] = array_diff($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["ids"],array($group_id));
                        }
                        add_success(sprintf($translate->_("Successfully %s <b>%s</b>. You will now be redirected to group index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"%s\" style=\"font-weight: bold\">click here</a> to continue."), $post_action . "d", $name, $url));
						application_log("success", "Group $name was" . $post_action . "d.");
                    }
                }
				if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["ids"]) || !count($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["ids"])) { // All group(s) deleted
                    $edit = "";
				}
			}
			$ONLOAD[]	= "setTimeout('window.location=\\'${URL}${edit}\\'',3000)";
			if ($ERROR) {
				echo display_error();
			}
            echo display_success();

		break;
		case 1 :
		default :

			if($ERROR) {
				echo display_error();
			} elseif (isset($GROUP_ID) && $GROUP_ID) { // Rename group
				$BREADCRUMB[]	= array("url" => "", "title" => $translate->_("Manage Cohort Name"));

				echo "<h1>" . $translate->_("Manage Cohort Name") . "</h1>";

				$name	= Models_Group::getName($GROUP_ID);
				if($name) {
					echo display_notice($translate->_("Please choose a new name for the group."));
					?>
					<form class="form-horizontal" action="<?php echo $URL; ?>?section=manage&amp;step=2" method="post">
						<input type="hidden" id="group_name" name="group_name" value="<?php echo $name;?>" />
						<input type="hidden" id="group_id" name="group_id" value="<?php echo $GROUP_ID;?>" />

						<div class="control-group">
							<label class="form-required control-label" for="name"><?php echo $translate->_("Cohort Name"); ?>:</label>
							<div class="control">
								<input class="offset1 span4" type="text" id="name" name="name" value="<?php echo html_encode($name); ?>" />
							</div>
						</div>
						<!--- End control-group ---->

                        <div class="pull-right"><input type="submit" class="btn btn-primary" value=<?php echo $translate->_("Rename"); ?> /></div>
					</form>
					<?php }
	
			} elseif ($MEMBERS) {  // Delete members
				$BREADCRUMB[]	= array("url" => "", "title" => $translate->_("Manage Members"));
				
				echo "<h1>". (strcmp($post_action,"activate") ? $translate->_("De/Activate or Delete") : $DEFAULT_TEXT_LABELS["activate"]) .  sprintf($translate->_(" Learner%s"), $MEMBERS > 1 ? "s" : "") ."</h1>";

				$members = Models_Group_Member::getListMembers($ENTRADA_USER->getActiveOrganisation(),$GROUP_IDS);
				if($members) {
					echo display_notice(sprintf($translate->_("Please review the following <b>learner%s</b> to ensure that you wish to, deactivate, activate or <strong>permanently delete</strong> them from the group or cohort."), $MEMBERS > 1 ? "s" : ""));
					?>
					<form id="memberDelete" action="<?php echo $URL; ?>?section=manage&amp;step=2" method="post">
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
									<td class="community_title" style="font-size: 12px"><?php echo $translate->_("Name"); ?></td>
									<td class="community_shortname" style="font-size: 12px"><?php echo $translate->_("Cohort"); ?></td>
									<td class="community_shortname" style="font-size: 12px"><?php echo $translate->_("Group & Role"); ?></td>
									<td class="attachment" style="font-size: 12px">&nbsp;</td>
								</tr>
							</thead>
							<tbody>
							<?php
								if($ENTRADA_ACL->amIAllowed('group', 'delete')) {
									foreach ($members as $member) {
										$url 	= "${URL}?section=edit&amp;ids=".$member["group_id"];
								
										echo "<tr id=\"group-".$member["group_id"]."\" class=\"event".(!$member["member_active"] ? " na" : "")."\">\n";
											echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"checked[]\" value=\"".$member["gmember_id"]."\" checked=\"checked\" /></td>\n";
											echo "	<td class=\"community_title\"><a href=\"" . ENTRADA_URL . "/people?profile=" . $member["username"] . "\" >" . html_encode($member["fullname"]) . "</a></td>\n";
											echo "	<td class=\"community_shortname\"><a href=\"${url}\" title=\"Cohort Name: ".html_encode($member["group_name"])."\">".html_encode($member["group_name"])."</a></td>\n";
											echo "	<td class=\"date\"><a href=\"${url}\" title=\"Role: ".html_encode($member["grouprole"])."\">".html_encode($member["grouprole"])."</a></td>\n";
											echo "	<td class=\"attachment\"> <a href=\"${url}\"><img src=\"".ENTRADA_URL."/images/action-edit.gif\" width=\"16\" height=\"16\" alt=\"Manage Cohort\" title=\"".$translate->_("Manage Cohort")."\" border=\"0\" /></a>";
										echo "</tr>\n";
									}
								}
							?>
							</tbody>
						</table>
						<div class="row-fluid">
                            <?php
                                switch ($post_action) {
                                    case 'activate':
                                        ?>
								        <input type="submit" class="btn btn-success pull-right" value="<?php echo $DEFAULT_TEXT_LABELS["activate"]; ?>" onClick="$('coa').value='activate'" />
                                        <?php
                                        break;
                                    case 'delete':
                                        ?>
									    <input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo $URL . $edit; ?>'" />
                                        <div class="pull-right">
                                            <input type="submit" class="btn btn-primary" value="<?php echo $DEFAULT_TEXT_LABELS["deactivate"]; ?>" onClick="$('coa').value='deactivate'" />
                                            <input type="submit" class="btn btn-danger" value="<?php echo $DEFAULT_TEXT_LABELS["btn_delete"]; ?>" onClick="$('coa').value='delete'" />
                                        </div>
                                        <?php
                                        break;
                                    default:
                                        ?>
                                        <input type="submit" class="btn btn-success" value="<?php echo $DEFAULT_TEXT_LABELS["activate"]; ?>" onClick="$('coa').value='activate'" />
                                        <input type="button" class="btn" value="<?php echo $DEFAULT_TEXT_LABELS["btn_cancel"]; ?>" onclick="window.location='<?php echo $URL . $edit; ?>'" />
                                        <div class="pull-right">
                                            <input type="submit" class="btn btn-primary" value="<?php echo $DEFAULT_TEXT_LABELS["deactivate"]; ?>" onClick="$('coa').value='deactivate'" />
                                            <input type="submit" class="btn btn-danger" value="<?php echo $DEFAULT_TEXT_LABELS["btn_delete"]; ?>" onClick="$('coa').value='delete'" />
                                        </div>
                                        <?php
                                        break;
                                }
                            ?>
						</div>
					</form>
				<?php
				}
			} else {
				$BREADCRUMB[]	= array("url" => "", "title" => $translate->_("De/Activate or Delete"));
				echo "<h1>". $translate->_("De/Activate or Delete") ." ". $translate->_("Cohort") . "</h1>";

				$total_groups	= count($GROUP_IDS);

				$groups = Models_Group::fetchGroupsInList($GROUP_IDS);

				if($groups) {
					echo display_notice(sprintf($translate->_("Please review the following group%s to ensure that you wish to activate, deactivate or <strong>permanently delete</strong> %s."), $total_groups>1?"s":"", $total_groups>1?"them":"it"));
					echo display_error($translate->_("Deleting a group will also <b>delete</b> it's group members and this action <strong>can not</strong> be undone."));
					?>
					<form action="<?php echo $URL; ?>?section=manage&amp;step=2" method="post">
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
									<td class="community_title" style="font-size: 12px"><?php echo $translate->_("Cohort Name"); ?></td>
									<td class="community_shortname" style="font-size: 12px"><?php echo $translate->_("Number of learners"); ?></td>
									<td class="community_opened" style="font-size: 12px"><?php echo $translate->_("Updated Date"); ?></td>
									<td class="attachment" style="font-size: 12px">&nbsp;</td>
								</tr>
							</thead>
							<tbody>
							<?php
								foreach($groups as $group) {
									$members = $group->getTotalGroupMembers();
                                    $members = $members["total_row"];
									$url			= "";

									if($ENTRADA_ACL->amIAllowed('group', 'delete')) {
										$url 	= "${URL}?section=edit&amp;id=".$group->getID();
								
										echo "<tr id=\"group-".$group->getID()."\" class=\"event".((!$url) ? " np" : ((!$group->getGroupActive()) ? " na" : ""))."\">\n";
										echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"checked[]\" value=\"".$group->getID()."\" checked=\"checked\" /></td>\n";
										echo "	<td class=\"community_title".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"".$translate->_("Cohort Name").": ".html_encode($group->getGroupName())."\">" : "").html_encode($group->getGroupName()).(($url) ? "</a>" : "")."</td>\n";
										echo "	<td class=\"community_shortname".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"".$translate->_("Number of learners").": ".$members."\">" : "").$members.(($url) ? "</a>" : "")."</td>\n";
										echo "	<td class=\"date".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"".$translate->_("Updated Date")."\">" : "").date("M jS Y", $group->getUpdatedDate()).(($url) ? "</a>" : "")."</td>\n";
										echo "	<td class=\"attachment\">".(($url) ? "<a href=\"${URL}?section=edit&amp;ids=".$group->getID()."\"><img src=\"".ENTRADA_URL."/images/action-edit.gif\" width=\"16\" height=\"16\" alt=\"".$translate->_("Manage Cohort")."\" title=\"".$translate->_("Manage Cohort")."\" border=\"0\" /></a>" : "<img src=\"".ENTRADA_URL."/images/pixel.gif\" width=\"16\" height=\"16\" alt=\"\" title=\"\" />")."</td>\n";
										echo "</tr>\n";
									}
								}
							?>
							</tbody>
						</table>
						<div class="row-fluid">
							<input type="submit" class="btn btn-primary" value="<?php echo $DEFAULT_TEXT_LABELS["activate"]; ?>" onClick="$('coa').value='activate';" />
                            <input type="submit" class="btn btn-info offset8" value="<?php echo $DEFAULT_TEXT_LABELS["deactivate"]; ?>" onClick="$('coa').value='deactivate';" />
                            <input type="submit" class="btn btn-danger" value="<?php echo $DEFAULT_TEXT_LABELS["btn_delete"]; ?>" onClick="$('coa').value='delete';" />
 						</div>
					</form>
					<?php
				} else {
					header("Location: $URL");
					exit;	
				}
			}
		break;
	}
}
