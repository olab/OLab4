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
 * Delete functionality of the admin section of
 * the Courses module.
 *
 * @author Organisation: Queen's University
 * @author Unit: Medical Education Technology Unit
 * @author Director: Dr. Benjamin Chen <bhc@post.queensu.ca>
 * @author Developer: Matt Simpson <simpson@post.queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @version 3.0
 * @copyright Copyright 2010 Queen's University, MEdTech Unit
 *
 * $Id: delete.inc.php 1169 2010-05-01 14:18:49Z simpson $
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_COURSES"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->amIAllowed('course', 'delete', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[]	= array("url" => "", "title" => "Delete " . $translate->_("courses"));

	echo "<h1>Delete " . $translate->_("courses") . "</h1>";

	$COURSE_IDS = array();

	// Error Checking
	switch($STEP) {
		case 2 :
		case 1 :
		default :
			if((!isset($_POST["delete"])) || (!is_array($_POST["delete"])) || (!@count($_POST["delete"]))) {
				$ERROR++;
				$ERRORSTR[] = "You must select at least 1 course to delete by checking the checkbox to the left course name.";

				application_log("notice", "Course delete page accessed without providing any course id's to delete.");
			} else {
				foreach($_POST["delete"] as $course_id) {
					if($course_id = (int) trim($course_id)) {
						$COURSE_IDS[] = $course_id;
					}
				}

				if(!@count($COURSE_IDS)) {
					$ERROR++;
					$ERRORSTR[] = "There were no valid course identifiers provided to delete. Please ensure that you access this section through the course index.";
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
			$removed	= array();

			foreach($COURSE_IDS as $course_id) {
				
				/**
				 * Removal of course.
				 */
				$query	= "	SELECT * FROM `courses` 
							WHERE `course_id`=".$db->qstr($course_id)."
							AND `course_active` = '1'";
				$results	= $db->GetAll($query);
				if($results) {
					foreach($results as $result) {
						$removed[$course_id]["course_name"] = $result["course_name"];
					}
					/**
					 * Deactivation of course-communities which are linked
					 * to only this course, and removal of connection if
					 * they are linked to additional courses.
					 */
					$query = "	SELECT `community_id` FROM `community_courses` 
								WHERE `course_id` = ".$db->qstr($course_id);
					$communities = $db->GetAll($query);
					if ($communities) {
						foreach ($communities as $community) {
							$query = "	SELECT COUNT(`course_id`) as `courses_count` FROM `community_courses`
										GROUP BY `community_id`
										HAVING `community_id` = ".$db->qstr($community["community_id"]);
							$courses_count = $db->GetOne($query);
							if ($courses_count) {
								if ($courses_count > 1) {
									//Remove this course's community link because others exist
									$query = "	DELETE FROM `community_courses` 
												WHERE `course_id` = ".$db->qstr($course_id)."
												AND `community_id` = ".$db->qstr($community["community_id"]);
									$db->Execute($query);
								} else {
									//Deactivate the course-community as this is the only linked course
									$query = "	UPDATE `communities`
												SET `community_active` = '0'
												WHERE `community_id` = ".$db->qstr($community["community_id"]);
									$db->Execute($query);
								}
							}
						}
					}
					
					$query = "UPDATE `courses` SET `course_active` = '0' WHERE `course_id` = ".$db->qstr($course_id);
					$db->Execute($query);
										
				}
			}

			$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 5000)";

			if($total_removed = @count($removed)) {
				$SUCCESS++;
				$SUCCESSSTR[$SUCCESS]  = "You have successfully removed ".$total_removed." " . (($total_removed != 1) ? $translate->_("courses") : $translate->_("course"))." from the system.";
				$SUCCESSSTR[$SUCCESS] .= "<br /><br />You will be automatically redirected to the course index in 5 seconds, or you can <a href=\"".ENTRADA_URL."/admin/".$MODULE."\">click here</a> if you do not wish to wait.";

				echo display_success();

				application_log("success", "Successfully removed course ids: ".implode(", ", $COURSE_IDS));
			} else {
				$ERROR++;
				$ERRORSTR[] = "We were unable to remove the requested courses from the system. The MEdTech Unit has been informed of this issue and will address it shortly; please try again later.";

				echo display_error();

				application_log("error", "Failed to remove course ids: ".implode(", ", $COURSE_IDS).". Database said: ".$db->ErrorMsg());
			}
		break;
		case 1 :
		default :
			if($ERROR) {
				echo display_error();
			} else {
				$query		= "	SELECT a.`course_id`, a.`course_name`, a.`course_url`, CONCAT_WS(', ', c.`lastname`, c.`firstname`) AS `fullname`
								FROM `courses` AS a
								LEFT JOIN `course_contacts` AS b
								ON a.`course_id` = b.`course_id`
								AND b.`contact_type` = 'director'
								AND b.`contact_order` = '0'
								LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS c
								ON c.`id` = b.`proxy_id`
								WHERE a.`course_id` IN (".implode(", ", $COURSE_IDS).")
								AND a.`course_active` = '1'
								GROUP BY a.`course_id`
								ORDER BY a.`course_name` ASC";
				$results	= $db->GetAll($query);
				if ($results) {
					echo display_notice(array("Please review the following course".((count($COURSE_IDS) != 1) ? "s" : "")." to ensure that you wish to <strong>permanently delete</strong> ".((count($COURSE_IDS) != 1) ? "them" : "it")." and <strong>unlink any events</strong> currently associated to ".((count($COURSE_IDS) != 1) ? "these courses" : "this course").".<br /><br /><strong>Important:</strong> If you remove ".((count($COURSE_IDS) != 1) ? "these courses" : "this course")." all associated events will be disassociated with a course."));
					?>
					<form action="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>?section=delete&amp;step=2" method="post">
					<table class="tableList" cellspacing="0" summary="List of Courses">
					<colgroup>
						<col class="modified" />
						<col class="title" />
						<col class="teacher" />
						<col class="general" />
						<col class="attachment" />
					</colgroup>
					<thead>
						<tr>
							<td class="modified">&nbsp;</td>
							<td class="title sortedASC" style="font-size: 12px"><div class="noLink"><?php echo $translate->_("course"); ?> Name</div></td>
							<td class="teacher" style="font-size: 12px"><?php echo $translate->_("course"); ?>Director</td>
							<td class="general" style="font-size: 12px">Associated Events</td>
							<td class="attachment">&nbsp;</td>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<td></td>
							<td colspan="4" style="padding-top: 10px">
								<input type="submit" class="btn btn-danger" value="Confirm Removal" />
							</td>
						</tr>
					</tfoot>
					<tbody>
						<?php
						foreach($results as $result) {
							$url	= ENTRADA_URL."/admin/".$MODULE."?section=edit&amp;id=".$result["course_id"];
							$events	= courses_count_associated_events($result["course_id"]);
							
							echo "<tr id=\"course-".$result["course_id"]."\" class=\"course\">\n";
							echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"delete[]\" value=\"".$result["course_id"]."\" checked=\"checked\" /></td>\n";
							echo "	<td class=\"title\"><a href=\"".$url."\" title=\"Course Name: ".html_encode($result["course_name"])."\">".html_encode($result["course_name"])."</a></td>\n";
							echo "	<td class=\"teacher\"><a href=\"".$url."\" title=\"Course Director: ".html_encode($result["fullname"])."\">".html_encode($result["fullname"])."</a></td>\n";
							echo "	<td class=\"general\">".$events." event".(($events != 1) ? "s" : "")."</td>\n";
							echo "	<td class=\"attachment\"><a href=\"".ENTRADA_URL."/admin/".$MODULE."?section=content&amp;id=".$result["course_id"]."\"><img src=\"".ENTRADA_URL."/images/event-contents.gif\" width=\"16\" height=\"16\" alt=\"Manage " . $translate->_("course") . " Content\" title=\"Manage " . $translate->_("course") . " Content\" border=\"0\" /></a></td>\n";
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