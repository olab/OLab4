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

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_COURSE_GROUPS"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->amIAllowed(new CourseResource($COURSE_ID, $ENTRADA_USER->getActiveOrganisation()), 'update', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 5000)";

	$ERROR++;
    $ERRORSTR[]    = "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {	
    $BREADCRUMB[]    = array("url" => "", "title" => "Edit Course Group");
	$GROUP_IDS = array();
	$MEMBERS = 0;
	$course_details = $db->GetRow("SELECT * FROM `courses` WHERE `course_id` = ".$db->qstr($COURSE_ID));
	// Error Checking
	switch($STEP) {
		case 2 :
			if ((isset($_POST["name"])) && isset($_POST["group_id"]) && ((int) trim($_POST["group_id"]))) { //Rename
				$GROUP_ID = (int) trim($_POST["group_id"]);
				break;
			}
			if((isset($_POST["add_group_id"])) && ((int) trim($_POST["add_group_id"])) && strlen($_POST["group_members"])) {
				$PROCESSED["cgroup_id"] = (int) trim($_POST["add_group_id"]);
			}
			if ((isset($_POST["associated_tutor"]))) {
				$associated_tutors = explode(",", $_POST["associated_tutor"]);
				foreach($associated_tutors as $contact_order => $proxy_id) {
					if ($proxy_id = clean_input($proxy_id, array("trim", "int"))) {
						$PROCESSED["associated_tutors"][(int) $contact_order] = $proxy_id;	
					}
				}
			}
		case 1 :
		default :
			switch ($ACTION) {
				case 'delmember':
					if ((isset($_GET["mids"])) && ((int) trim($_GET["mids"])))  {
						$MEMBERS = 1;
						$GROUP_IDS[] = (int) trim($_GET["mids"]);
					} elseif (isset($_POST["checked"])) {
						foreach($_POST["checked"] as $group_id) {
							$group_id = (int) trim($group_id);
							if($group_id) {
								$GROUP_IDS[] = $group_id;
							}
							if(!@count($GROUP_IDS)) {
								add_error("There were no valid group member identifiers provided to delete. Please ensure that you access this section through the member index.");
							} else { 
								$MEMBERS = count($GROUP_IDS);
							}													
						}
					}
					break;
				case 'delete':
					if (isset($_GET["ids"])) { 
						$GROUP_IDS = array(htmlentities($_GET["ids"]));
					} elseif(isset($_POST["checked"])) {
						foreach($_POST["checked"] as $group_id) {
							$group_id = (int) trim($group_id);
							if($group_id) {
								$GROUP_IDS[] = $group_id;
							}
						}
					} elseif($GROUP_ID) {
						$GROUP_IDS[] = $GROUP_ID;
					}
					if(!@count($GROUP_IDS)) {
						add_error("There were no valid group identifiers provided to delete. Please ensure that you access this section through the group index.");
					}
					break;
                default:
                    break;
				} 
			
			if($ERROR) {
				$STEP = 1;
			}
		break;
	}
	
	// Display Page
	switch($STEP) {
		case 2 :
			switch ($ACTION) {
			case 'rename':
				if (isset($_POST["name"])) {  // Rename group
					$group_name = clean_input($_POST["name"], array("notags", "trim"));
					if (strlen($group_name) && strcmp($group_name,$_POST["group_name"])) {
						$result	= $db->GetOne("	SELECT `cgroup_id` FROM `course_groups`
								WHERE `group_name` = '".$group_name."'
								AND `course_id` = ".$db->qstr($COURSE_ID));
						if ($result) {
							add_error("The group name already exists in system.");
							$wait = 10000;
						} else {
							$db->Execute("UPDATE `course_groups` SET `group_name`='".$group_name."' WHERE `cgroup_id` = ".$db->qstr($GROUP_ID));
							add_success("Successfully renamed course group.");
						}
					}
					$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/courses/groups?section=manage&id=".$COURSE_ID."&gid=".$GROUP_ID."\\'', 5000)";
				} 
				break;
			case 'delmember':
				if ($MEMBERS)  {  // Delete members
					foreach($GROUP_IDS as $cgaudience_id) {
						switch ($_POST["coa"]) {
							case "deactivate":
								$db->Execute("UPDATE `course_group_audience` SET `active`='0' WHERE `cgaudience_id` = ".$db->qstr($cgaudience_id));
							break;
							case "activate":
								$db->Execute("UPDATE `course_group_audience` SET `active`='1' WHERE `cgaudience_id` = ".$db->qstr($cgaudience_id));
							break;
							case "delete":
								$db->Execute("DELETE FROM `course_group_audience` WHERE `cgaudience_id` = ".$db->qstr($cgaudience_id));
							break;
						}
						add_success("Successfully ".$_POST["coa"]."d the selected group member.");
					}
				} 
					$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/courses/groups?section=manage&id=".$COURSE_ID."&gid=".$GROUP_ID."\\'', 5000)";				
				break;
			case 'managetutors':
				$query = "DELETE FROM `course_group_contacts` WHERE `cgroup_id` = ".$db->qstr($GROUP_ID);
				if ($db->Execute($query)) {
					if ((is_array($PROCESSED["associated_tutors"])) && (count($PROCESSED["associated_tutors"]))) {
						foreach($PROCESSED["associated_tutors"] as $contact_order => $proxy_id) {
							$contact_details =  array(	"cgroup_id" => $GROUP_ID, 
														"proxy_id" => $proxy_id, 
														"contact_order" => (int) $contact_order, 
														"updated_date" => time(), 
														"updated_by" => $ENTRADA_USER->getID());
							if (!$db->AutoExecute("course_group_contacts", $contact_details, "INSERT")) {
								add_error("There was an error while trying to attach an <strong>Associated Tutor</strong> to this course group.<br /><br />The system administrator was informed of this error; please try again later.");

								application_log("error", "Unable to insert a new course_group_contact record while managing a course group. Database said: ".$db->ErrorMsg());
							}
						}
						if (!has_error()) {
							add_success("Successfully updated the tutors for the selected course group.");
						}
					} else {
						add_success("Successfully removed all tutors from the selected course group.");
					}
				} else {
					add_error("There was an error while trying to remove the <strong>Associated Tutor(s)</strong> from this course group.<br /><br />The system administrator was informed of this error; please try again later.");

					application_log("error", "Unable to remove a course_group_contact record while managing a course group. Database said: ".$db->ErrorMsg());
				}
				$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/courses/groups?section=manage&id=".$COURSE_ID."&gid=".$GROUP_ID."\\'', 5000)";				
				break;
			case 'delete':
				$removed = array();
				foreach($GROUP_IDS as $group_id) {
					if($group_id = (int) $group_id) {
						switch ($_POST["coa"]) {
							case "deactivate":
								$db->Execute("UPDATE `course_groups` SET `active`='0' WHERE `cgroup_id` = ".$db->qstr($group_id));
							break;
							case "activate":
								$db->Execute("UPDATE `course_groups` SET `active`='1' WHERE `cgroup_id` = ".$db->qstr($group_id));
							break;
							case "delete":
								$query	= "	SELECT `cgroup_id`,  `group_name`
											FROM `course_groups`
											WHERE `cgroup_id` = ".$db->qstr($group_id);
								$result	= $db->GetRow($query);
								if ($result) {
									/**
									 * Remove all records from group_members table.
									 */
									$query = "DELETE FROM `course_group_audience` WHERE `cgroup_id` = ".$db->qstr($group_id);
									$db->Execute($query);
									$removed[$group_id]["group_name"] = $result["group_name"];
								}
								/**
								 * Remove group_id record from groups table.
								 */
								$query = "DELETE FROM `course_groups` WHERE `cgroup_id` = ".$db->qstr($group_id);
								break;
						}
						$db->Execute($query);
						if ($_POST["coa"] != "delete") {
							add_success("Successfully ".$_POST["coa"]."d the course group.");
						}
					}
				}
				$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/courses/groups?id=".$COURSE_ID."\\'', 5000)";


				if (!strcmp($_POST["coa"],"delete")) {
					if($total_removed = @count($removed)) {
						$SUCCESS++;
						$SUCCESSSTR[$SUCCESS]  = "You have successfully removed ".$total_removed." group".(($total_removed != 1) ? "s" : "")." from the system:";
						$SUCCESSSTR[$SUCCESS] .= "<div style=\"padding-left: 15px; padding-bottom: 15px; font-family: monospace\">\n";
						foreach($removed as $result) {
							$SUCCESSSTR[$SUCCESS] .= html_encode($result["group_name"])."<br />";
						}
						$SUCCESSSTR[$SUCCESS] .= "</div>\n";
						$SUCCESSSTR[$SUCCESS] .= "You will be automatically redirected to the group index in 5 seconds, or you can <a href=\"".ENTRADA_URL."/admin/courses/groups?id=".$COURSE_ID."\">click here</a> if you do not wish to wait.";

						application_log("success", "Successfully removed group ids: ".implode(", ", $GROUP_IDS));
					} else {
						$ERROR++;
						$ERRORSTR[] = "Unable to remove the requested groups from the system.<br /><br />The system administrator has been informed of this issue and will address it shortly; please try again later.";
						application_log("error", "Failed to remove all groups from the remove request. Database said: ".$db->ErrorMsg());
					}
				}
				
				break;
			}
			if ($ERROR) {
				echo display_error();
			}
			if($SUCCESS){
				echo display_success();
			}
		break;
		case 1 :
		default :
			if($ERROR) {
				echo display_error();
			} 			
			courses_subnavigation($course_details,"groups");			
			switch ($ACTION) {
			case 'rename':
				if ($GROUP_ID) { // Rename group				
					echo "<h1>Rename Group</h1>";
					$result	= $db->GetOne("	SELECT `group_name` FROM `course_groups` WHERE `cgroup_id` =	".$db->qstr($GROUP_ID));
					if($result) {
						echo display_notice(array("Please choose a new name for the group"));
						?>
						<form action="<?php echo ENTRADA_URL; ?>/admin/courses/groups?section=edit&amp;action=rename&amp;step=2&amp;id=<?php echo $COURSE_ID; ?>&amp;gid=<?php echo $GROUP_ID;?>" method="post">
							<input type="hidden" id="group_name" name="group_name" value="<?php echo $result;?>" />
							<input type="hidden" id="gid" name="gid" value="<?php echo $GROUP_ID;?>" />
							<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Member">
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
										<td><label for="prefix" class="form-required">Group Name:</label></td>
										<td><input type="text" id="name" name="name" value="<?php echo html_encode($result); ?>" maxlength="255" style="width: 45%" /></td>
									</tr>
								</tbody>
							</table>
						</form>
						<?php }
						}
						break;
			case 'delmember':// Delete members
						if($MEMBERS){
						echo "<h1>De/Activate or Delete Member".($MEMBERS>1?"s":"")."</h1>";


						$results = $db->getAll ("SELECT c.`cgaudience_id`, CONCAT_WS(' ', a.`firstname`, a.`lastname`) AS `fullname`,
												CONCAT_WS(':', b.`group`, b.`role`) AS `grouprole`, c.`cgroup_id`, d.`group_name`, c.`active`
												FROM `".AUTH_DATABASE."`.`user_data` AS a
												LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
												ON a.`id` = b.`user_id`
												INNER JOIN `course_group_audience` c ON a.`id` = c.`proxy_id`
												INNER JOIN `course_groups` d ON c.`cgroup_id` = d.`cgroup_id`
												WHERE c.`cgaudience_id`  IN (".implode(", ", $GROUP_IDS).")
												ORDER by `grouprole`, `lastname`, `firstname`");
						if($results) {
							echo display_notice(array("Please review the following member".($MEMBERS>1?"s":"")." to ensure that you wish to, deactivate, activate or <strong>permanently delete</strong> them from the group"));
							?>
							<form id="memberDelete" action="<?php echo ENTRADA_URL; ?>/admin/courses/groups?section=edit&amp;action=delmember&amp;step=2&amp;id=<?php echo $COURSE_ID; ?>&amp;gid=<?php echo $GROUP_ID;?>" method="post">
								<input type="hidden" name="gid" value="<?php echo $GROUP_ID;?>"/>
								<input type="hidden" name="members" value="1" />
								<input type="hidden" name="coa" id="coa" value="deactivate" />
								<table class="tableList" cellspacing="0" summary="List of Member">
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
											<td class="community_title" style="font-size: 12px">Name</td>
											<td class="community_shortname" style="font-size: 12px">Group</td>
											<td class="community_shortname" style="font-size: 12px">Role</td>
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
                                                <input type="submit" class="btn btn-warning" value="Deactivate" />
                                                <input type="submit" class="btn btn-danger" value="Delete" onClick="$('coa').value='delete'" />
                                            </td>
										</tr>
									</tfoot>
									<tbody>
									<?php
										$url			= "";
											foreach ($results as $result) {
												$url 	= ENTRADA_URL."/admin/courses/groups?section=edit&amp;gid=".$result["group_id"]."&amp;id=".$COURSE_ID;
											
												echo "<tr id=\"group-".$result["cgroup_id"]."\" class=\"event".((!$url) ? " np" : ((!$result["active"]) ? " na" : ""))."\">\n";
												echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"checked[]\" value=\"".$result["cgaudience_id"]."\" checked=\"checked\" /></td>\n";
												echo "	<td class=\"community_title".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Name: ".html_encode($result["fullname"])."\">" : "").html_encode($result["fullname"]).(($url) ? "</a>" : "")."</td>\n";
												echo "	<td class=\"community_shortname".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Group Name: ".html_encode($result["group_name"])."\">" : "").html_encode($result["group_name"]).(($url) ? "</a>" : "")."</td>\n";
												echo "	<td class=\"date".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Role: ".html_encode($result["grouprole"])."\">" : "").html_encode($result["grouprole"]).(($url) ? "</a>" : "")."</td>\n";
												echo "	<td class=\"attachment\">".(($url) ? "<a href=\"".ENTRADA_URL."/admin/courses/groups?section=edit&amp;gids=".$result["group_id"]."&amp;id=".$COURSE_ID."\"><img src=\"".ENTRADA_URL."/images/action-edit.gif\" width=\"16\" height=\"16\" alt=\"Edit Group\" title=\"Manage Group\" border=\"0\" /></a>" : "<img src=\"".ENTRADA_URL."/images/pixel.gif\" width=\"16\" height=\"16\" alt=\"\" title=\"\" />")."</td>\n";
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
			case 'delete':
			default:
						echo "<h1>De/Activate or Delete Groups</h1>";
							$total_groups	= count($GROUP_IDS);

							$query = "	SELECT * FROM `course_groups`
										WHERE `cgroup_id` IN (".implode(", ", $GROUP_IDS).")
										ORDER BY `group_name` ASC";
							$results	= $db->GetAll($query);
							if($results) {
								echo display_notice(array("Please review the following group".(($total_groups != 1) ? "s" : "")." to ensure that you wish to activate, deactivate or <strong>permanently delete</strong> ".(($total_groups != 1) ? "them" : "it").".<br /><br />Deleting will also remove any group members and this action cannot be undone."));
								?>
								<form action="<?php echo ENTRADA_URL; ?>/admin/courses/groups?section=edit&amp;action=delete&amp;step=2&amp;id=<?php echo $COURSE_ID; ?>&amp;gid=<?php echo $GROUP_ID;?>" method="post">
									<input type="hidden" name="gid" value="<?php echo $GROUP_ID;?>" />
									<input type="hidden" name="coa" id="coa" value="deactivate" />
									<table class="tableList" cellspacing="0" summary="List of Groups">
										<colgroup>
											<col class="modified" />
											<col class="community_title" />
											<col class="community_shortname" />
											<col class="attachment" />
										</colgroup>
										<thead>
											<tr>
												<td class="modified" style="font-size: 12px">&nbsp;</td>
												<td class="community_title" style="font-size: 12px">Group Name</td>
												<td class="community_shortname" style="font-size: 12px">Number of members</td>
												<td class="attachment" style="font-size: 12px">&nbsp;</td>
											</tr>
										</thead>
										<tfoot>
											<tr>
												<td style="padding-top: 10px" colspan="2">
													<input type="submit" class="btn btn-primary" value="Activate" onClick="$('coa').value='activate'" />
												</td>
												<td style="padding-top: 10px" align="right" colspan="2">
													<input type="submit" class="btn btn-warning" value="Deactivate" />
													<input type="submit" class="btn btn-danger" value="Delete" onClick="$('coa').value='delete'" />
												</td>
											</tr>
										</tfoot>
										<tbody>
										<?php
											foreach($results as $result) {
												$result["members"] = $db->GetOne("SELECT COUNT(*) AS members FROM  `course_group_audience` WHERE `cgroup_id` = ".$db->qstr($result["cgroup_id"]));

												$url 	= ENTRADA_URL."/admin/courses/groups?section=edit&amp;action=rename&amp;gid=".$result["cgroup_id"]."&amp;id=".$COURSE_ID;

												echo "<tr id=\"group-".$result["cgroup_id"]."\" class=\"event".((!$url) ? " np" : ((!$result["active"]) ? " na" : ""))."\">\n";
												echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"checked[]\" value=\"".$result["cgroup_id"]."\" checked=\"checked\" /></td>\n";
												echo "	<td class=\"community_title".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Group Name: ".html_encode($result["group_name"])."\">" : "").html_encode($result["group_name"]).(($url) ? "</a>" : "")."</td>\n";
												echo "	<td class=\"community_shortname".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Number of sembers: ".$result["members"]."\">" : "").$result["members"].(($url) ? "</a>" : "")."</td>\n";
												echo "	<td class=\"attachment\">".(($url) ? "<a href=\"".$url."\"><img src=\"".ENTRADA_URL."/images/action-edit.gif\" width=\"16\" height=\"16\" alt=\"Manage Group\" title=\"Manage Group\" border=\"0\" /></a>" : "<img src=\"".ENTRADA_URL."/images/pixel.gif\" width=\"16\" height=\"16\" alt=\"\" title=\"\" />")."</td>\n";
												echo "</tr>\n";
											}
										?>
										</tbody>
									</table>
								</form>
								<?php
							} else {
								application_log("error", "The confirmation of removal query returned no results... curious Database said: ".$db->ErrorMsg());
								header("Location: ".ENTRADA_URL."/admin/courses/groups?id=".$COURSE_ID);
								exit;
							}
						break;
			}
			break;
	}
}
	