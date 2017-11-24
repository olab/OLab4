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
 * @author Developer: Harry Brundage <hbrundage@qmed.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
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
			$ASSESSMENT_IDS	= array();
			$INDEX_URL = ENTRADA_URL."/admin/gradebook?".replace_query(array("section" => "view", "step" => false));
			// Error Checking
			switch($STEP) {
				case 2 :
				case 1 :
				default :
					if((!isset($_POST["delete"])) || (!is_array($_POST["delete"])) || (!@count($_POST["delete"]))) {
						$ERROR++;
						$ERRORSTR[] = "You must select at least 1 assessment to delete by checking the checkbox to the left the assessment.";

						application_log("notice", "Assessment delete page accessed without providing any assessment id's to delete.");
					} else {
						foreach($_POST["delete"] as $assessment_id) {
							$assessment_id = (int) trim($assessment_id);
							if($assessment_id) {
								$ASSESSMENT_IDS[] = $assessment_id;
							}
						}

						if(!@count($ASSESSMENT_IDS)) {
							$ERROR++;
							$ERRORSTR[] = "There were no valid assessment identifiers provided to delete. Please ensure that you access this section through the assessment index.";
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
                    $total_removed_assessments = 0;
                    foreach ($ASSESSMENT_IDS as $assessment_id) {
                        $assessment = Models_Gradebook_Assessment::fetchRowByID($assessment_id);
                        if ($assessment) {
                            if ($assessment->fromArray(array("active" => 0))->update()) {
                                $total_removed_assessments++;
                                if (!$db->AutoExecute ("assignments", array("assignment_active" => 0), "UPDATE", "`assessment_id` = ".$assessment_id)) {
                                    application_log("error", "Successfully removed assessment id: [".$assessment_id."] but was unable to remove the assignments pertaining to them.");
                                }
                            }
                        }
                    }
                    
                    if($total_removed_assessments == count($ASSESSMENT_IDS)) {
                        add_success("You have successfully removed ".$total_removed_assessments." assessment".(($total_removed_assessments != 1) ? "s" : "")." from the system.<br /><br />You will be automatically redirected to the Assessment index in 5 seconds, or you can <strong><a href=\"".$INDEX_URL."\">click here</a></strong> to go there now.");
                        echo display_success();
                    } else {
                        add_error("We were unable to remove the requested assessments from the system. The MEdTech Unit has been informed of this issue and will address it shortly; please try again later.");
                        echo display_error();
                        application_log("error", "Failed to execute remove query for assessment ids: ".implode(", ", $ASSESSMENT_IDS).". Database said: ".$db->ErrorMsg());
                    }
				break;
				case 1 :
				default :
			
					// Fetch all associated assessments
					$query = "SELECT a.`assessment_id`, a.`cohort`, a.`name`, a.`type`, b.`group_name` AS `cohort_name`  
								FROM `assessments` AS a
								JOIN `groups` AS b
								ON a.`cohort` = b.`group_id`
								WHERE a.`course_id` = ".$db->qstr($COURSE_ID)."
								AND a.`active` = '1'
								AND a.`assessment_id` IN (".implode(", ", $ASSESSMENT_IDS).")
								ORDER BY a.`name` ASC";
					$assessments = 	$db->GetAll($query);
					if($assessments) {
						echo display_notice(array("Please review the following notices to ensure that you wish to permanently delete them. This action cannot be undone."));
						echo "<form action=\"".ENTRADA_URL . "/admin/gradebook/assessments?".replace_query(array("section" => "delete", "step"=>2))."\" method=\"post\">";
						
						?>
						
						<table class="tableList" cellspacing="0" summary="List of Assessments">
						<colgroup>
							<col class="modified" />
							<col class="title" />
							<col class="general" />
							<col class="general" />
							<col class="general" />
						</colgroup>
						<thead>
							<tr>
								<td class="modified">&nbsp;</td>
								<td class="title sortedASC">Name</td>
								<td class="general">Graduating Year</td>
								<td class="general">Assessment Type</td>
								<td class="general">Grades Entered</td>
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
							foreach($assessments as $key => $assessment) {
								$url = ENTRADA_URL."/admin/gradebook/assessments?section=edit&amp;id=".$COURSE_ID."&amp;assessment_id=".$assessment["assessment_id"];
						
								echo "<tr id=\"assessment-".$assessment["assessment_id"]."\">";
								echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"delete[]\" checked=\"checked\" value=\"".$assessment["assessment_id"]."\" /></td>\n";
								echo "	<td class=\"title\"><a href=\"$url\">".$assessment["name"]."</a></td>";
								echo "	<td class=\"general\"><a href=\"$url\">".$assessment["cohort_name"]."</a></td>";
								echo "	<td class=\"general\"><a href=\"$url\">".$assessment["type"]."</a></td>";
								echo "	<td class=\"general\">"."&nbsp;"."</td>";
								echo "</tr>";
							}
							?>
						</tbody>
					</table>
					</form>
					<?php
					} else {
						// No assessments in this course.
						$ONLOAD[]	= "setTimeout('window.location=\\'".$INDEX_URL."\\'', 5000)";
						?>
						<div class="display-notice">
							<h3>No Assessments to delete for <?php echo $course_details["course_name"]; ?></h3>
							You must select some assessments to delete for this course.<br /><br />
							You will be automatically redirected to the Assessment index in 5 seconds, or you can <strong><a href="<?php echo $INDEX_URL ?>">click here</a></strong> to go there now.
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
