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
 * @author Unit: School of Medicine
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_GRADEBOOK"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("gradebook", "delete", false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	if ($COURSE_ID) {
		$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/gradebook/assignments?".replace_query(array("step" => false)), "title" => "Delete Assignment");
		$query			= "	SELECT * FROM `courses` 
							WHERE `course_id` = ".$db->qstr($COURSE_ID)."
							AND `course_active` = '1'";
		$course_details	= $db->GetRow($query);
		if ($course_details && $ENTRADA_ACL->amIAllowed(new GradebookResource($course_details["course_id"], $course_details["organisation_id"]), "update")) {		
			
			courses_subnavigation($course_details);
			
			$curriculum_path = curriculum_hierarchy($COURSE_ID);
			if ((is_array($curriculum_path)) && (count($curriculum_path))) {
				echo "<h1>" . implode(": ", $curriculum_path) . " Gradebook </h1>";
			}
			echo "<br />";
			$ASSIGNMENT_IDS	= array();
			$INDEX_URL = ENTRADA_URL."/admin/gradebook?".replace_query(array("section" => "view", "step" => false));
			// Error Checking
			switch($STEP) {
				case 2 :
				case 1 :
				default :
					if((!isset($_GET["delete"])) || !($assignment_id = ((int)$_GET["delete"]))) {
						$ERROR++;
						$ERRORSTR[] = "You must select an assignment to delete from the assignments page.";

						application_log("notice", "Assignment delete page accessed without providing any assignment id's to delete.");
					} else {
                        $assignment_id = (int) trim($assignment_id);
                        if($assignment_id) {
                            $ASSIGNMENT_IDS[] = $assignment_id;
                        }

						if(!@count($ASSIGNMENT_IDS)) {
							$ERROR++;
							$ERRORSTR[] = "There were no valid assignment identifiers provided to delete. Please ensure that you access this section through the assignment index.";
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
					$query = "UPDATE `assignments` SET `assignment_active` = 0 WHERE `assignment_id` IN (".implode(", ", $ASSIGNMENT_IDS).")";
					if($db->Execute($query)) {
						$ONLOAD[]	= "setTimeout('window.location=\\'".$INDEX_URL."\\'', 5000)";

                        if($total_removed = $db->Affected_Rows()) {

                            $SUCCESS++;
                            $SUCCESSSTR[]  = "You have successfully removed ".$total_removed." assignment".(($total_removed != 1) ? "s" : "")." from the system.<br /><br />You will be automatically redirected to the event index in 5 seconds, or you can <strong><a href=\"".$INDEX_URL."\">click here</a></strong> to go there now.";

                            echo display_success();


                        } else {
                                $ERROR++;
                                $ERRORSTR[] = "We were unable to remove the requested assignments from the system. The MEdTech Unit has been informed of this issue and will address it shortly; please try again later.";

                                echo display_error();

                                application_log("error", "Failed to remove any assignment ids: ".implode(", ", $ASSIGNMENT_IDS).". Database said: ".$db->ErrorMsg());
                        }
					} else {
						$ERROR++;
						$ERRORSTR[] = "We were unable to remove the requested assignments from the system. The MEdTech Unit has been informed of this issue and will address it shortly; please try again later.";

						echo display_error();

						application_log("error", "Failed to execute remove query for assignment ids: ".implode(", ", $ASSIGNMENT_IDS).". Database said: ".$db->ErrorMsg());
					}
				break;
				case 1 :
				default :
			
					// Fetch all associated assessments
					$query = "SELECT a.`assignment_id`, a.`assignment_title`, b.`name`, a.`assessment_id` 
								FROM `assignments` AS a
								JOIN `assessments` AS b
								ON a.`assessment_id` = b.`assessment_id`
								WHERE a.`course_id` = ".$db->qstr($COURSE_ID)."
								AND b.`active` = 1
								AND a.`assignment_active` = 1
								AND a.`assignment_id` IN (".implode(", ", $ASSIGNMENT_IDS).")
								ORDER BY a.`assignment_title` ASC";
					$assignments = 	$db->GetAll($query);
					if($assignments) {
						echo display_notice(array("Please review the following assignments to ensure that you wish to permanently delete them. This action cannot be undone."));
						echo "<form action=\"".ENTRADA_URL . "/admin/gradebook/assignments?".replace_query(array("section" => "delete", "step"=>2))."\" method=\"post\">";
						
						?>
						
						<table class="tableList" cellspacing="0" summary="List of Assignments">
						<colgroup>
							<col class="modified" />
							<col class="title" />
							<col class="title" />
						</colgroup>
						<thead>
							<tr>
								<td class="modified">&nbsp;</td>
								<td class="title sortedASC">Assignment Title</td>
								<td class="title">Associated Assessment</td>
							</tr>
						</thead>
						<tfoot>
							<tr>
								<td></td>
								<td colspan="2" style="padding-top: 10px">
									<input type="submit" class="btn btn-danger" value="Delete Selected" />
								</td>
							</tr>
						</tfoot>
						<tbody>
							<?php
							foreach($assignments as $key => $assignment) {
								$url = ENTRADA_URL."/admin/gradebook/assignments?section=edit&amp;id=".$COURSE_ID."&amp;assignment_id=".$assignment["assignment_id"];
								$assessment_url = ENTRADA_URL."/admin/gradebook/assessments?section=edit&amp;id=".$COURSE_ID."&amp;assessment_id=".$assignment["assessment_id"];
								echo "<tr id=\"assignment-".$assignment["assessment_id"]."\">";
								echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"delete[]\" checked=\"checked\" value=\"".$assignment["assignment_id"]."\" /></td>\n";
								echo "	<td class=\"title\"><a href=\"$url\">".$assignment["assignment_title"]."</a></td>";
								echo "	<td class=\"general\">".(isset($assignment["name"])?"<a href=\"$assessment_url\">".$assignment["name"]."</a>":"No Assessment")."</td>";
								echo "</tr>";
								//((!isset($assignment["name"]) || $assignment["name"] == null)?$assignment["name"]:"No Assessment")
							}
							?>
						</tbody>
					</table>
					</form>
					<?php
					} else {
						// No assessments in this course.
						?>
						<div class="display-notice">
							<h3>No Assignments to delete for <?php echo $course_details["course_name"]; ?></h3>
							You must select some assignments to delete for this course
						</div>
						<?php
					}
				break;
			}
		} else {
			$ERROR++;
			$ERRORSTR[] = "In order to edit a course you must provide a valid course identifier. The provided ID does not exist in this system.";

			echo display_error();

			application_log("notice", "Failed to provide a valid course identifer when attempting to view a gradebook");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "In order to edit a course you must provide the courses identifier.";

		echo display_error();

		application_log("notice", "Failed to provide course identifer when attempting to view a gradebook");
	}
}
?>
