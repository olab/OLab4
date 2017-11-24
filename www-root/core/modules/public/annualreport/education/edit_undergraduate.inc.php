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
 * @author Developer: Andrew Dos-Santos <andrew.dos-santos@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/
if((!defined("PARENT_INCLUDED")) || (!defined("IN_ANNUAL_REPORT"))) {
	exit;
} else if((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL.((isset($_SERVER["REQUEST_URI"])) ? "?url=".rawurlencode(clean_input($_SERVER["REQUEST_URI"], array("nows", "url"))) : ""));
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('annualreport', 'update')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	$UNDERGRADUATE_TEACHING_ID = $_GET["rid"];
	// This grid should be expanded upon redirecting back to the education index.
	$_SESSION["education_expand_grid"] = "undergraduate_medical_teaching_grid";
	
	if($UNDERGRADUATE_TEACHING_ID) {
		$query	= "SELECT * FROM `ar_undergraduate_teaching` WHERE `undergraduate_teaching_id` = ".$db->qstr($UNDERGRADUATE_TEACHING_ID)." AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId());
		$result = $db->GetRow($query);
		if($result) {
			$BREADCRUMB[]	= array("url" => ENTRADA_URL."/annualreport/education?section=edit_undergraduate", "title" => "Edit Undergraduate Teaching");
			
			echo "<h1>Edit Undergraduate Teaching</h1>\n";
			
			// Error Checking
			switch($STEP) {
				case 2 :
					/**
					 * Required field "assigned" / Assigned
					 */
					if((isset($_POST["assigned"])) && ($assigned = clean_input($_POST["assigned"], array("notags", "trim")))) {
						$PROCESSED["assigned"] = $assigned;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Assigned</strong> field is required.";
					}
					/**
					 * Required field "coord_enrollment" / Coordinator Enrollment.
					 */
					if((isset($_POST["coord_enrollment"])) && ($coord_enrollment = clean_input($_POST["coord_enrollment"], array("int")))) {
						$PROCESSED["coord_enrollment"] = $coord_enrollment;
					} else {
						if(trim($_POST["coord_enrollment"] === "0"))
						{
							$PROCESSED["coord_enrollment"] = trim($_POST["coord_enrollment"]);
						}
						else if(trim($_POST["coord_enrollment"]) == '')
						{
							$PROCESSED["coord_enrollment"] = "0";
						}
						else {
							$ERROR++;
							$ERRORSTR[] = "The <strong>Coordinator Enrollment</strong> field is required.";
						}
					}
					/**
					 * Non-Required field "comments" / Comments.
					 */
					if((isset($_POST["comments"])) && ($comments = clean_input($_POST["comments"], array("notags", "trim")))) {
						$PROCESSED["comments"] =  clean_input($_POST["comments"], array("notags", "trim"));
					} else {
						$PROCESSED["comments"] = "";
					}
					
					if(isset($_POST["post_action"])) {
						switch($_POST["post_action"]) {							
							case "new" :
								$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "new";
							break;
							case "index" :
							default :
								$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "index";
							break;
						}
					} else {
						$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "index";
					}
							
					if(!$ERROR) {
						$PROCESSED["updated_date"]	= time();
						$PROCESSED["updated_by"]	= $ENTRADA_USER->getID();
						$PROCESSED["proxy_id"]		= $ENTRADA_USER->getActiveId();
						
						$undergraduateTeachingQuery = "SELECT `course_name` FROM `ar_undergraduate_teaching` WHERE `undergraduate_teaching_id` = ".$db->qstr($UNDERGRADUATE_TEACHING_ID);
						$undergraduateTeachingResult = $db->GetRow($undergraduateTeachingQuery);
						$undergraduateTeachingResult["course_name"];
						
						if($db->AutoExecute("ar_undergraduate_teaching", $PROCESSED, "UPDATE", "`undergraduate_teaching_id`=".$db->qstr($UNDERGRADUATE_TEACHING_ID))) {
								$url 	= ENTRADA_URL."/annualreport/education";
								$msg	= "You will now be redirected to the education page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
								
								$SUCCESS++;
								$SUCCESSSTR[]  = "You have successfully edited <strong>".html_encode($undergraduateTeachingResult["course_name"])."</strong> in the system.<br /><br />".$msg;
								$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000);";
		
								application_log("success", "Edited Undergraduate Teaching [".$UNDERGRADUATE_TEACHING_ID."] in the system.");					
		
						} else {
							$ERROR++;
							$ERRORSTR[] = "There was a problem editing this Undergraduate Teaching record in the system. The MEdIT Unit was informed of this error; please try again later.";
		
							application_log("error", "There was an error editing the Undergraduate Teaching. Database said: ".$db->ErrorMsg());
						}
					} else {
						$STEP = 1;
					}
				break;
				case 1 :
				default :
					continue;
				break;
			}
			
			// Display Content
			switch($STEP) {
				case 2 :
					if($SUCCESS) {
						echo display_success();
					}
					if($NOTICE) {
						echo display_notice();
					}
					if($ERROR) {
						echo display_error();
					}
				break;
				case 1 :
				default :
					if(!isset($PROCESSED) || count($PROCESSED) <= 0)
					{	
						$undergraduateTeachingQuery = "SELECT * FROM `ar_undergraduate_teaching` WHERE `undergraduate_teaching_id` = ".$db->qstr($UNDERGRADUATE_TEACHING_ID);
						$undergraduateTeachingResult = $db->GetRow($undergraduateTeachingQuery);
					}
					
					if($ERROR) {
						echo display_error();
					}
					?>
					<form action="<?php echo ENTRADA_URL; ?>/annualreport/education?section=edit_undergraduate&amp;step=2&amp;rid=<?php echo $UNDERGRADUATE_TEACHING_ID;?>" method="post">					
					<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Editing Education">
						<colgroup>
							<col style="width: 3%" />
							<col style="width: 20%" />
							<col style="width: 77%" />
						</colgroup>
						<tr>
							<td colspan="3"><h2>Details - Editable</h2></td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="assigned" class="form-required">Assigned</label></td>				
							<td><select name="assigned" id="assigned" style="vertical-align: middle">
							<option value=""></option>
							<?php
								if(isset($PROCESSED["assigned"]) || isset($undergraduateTeachingResult["assigned"]))
								{
									if($PROCESSED["assigned"] == "Yes" || $undergraduateTeachingResult["assigned"] == "Yes")
									{
										echo "<option value=\"Yes\" SELECTED>Yes</option>\n";
										echo "<option value=\"No\">No</option>\n";
									}
									else 
									{
										echo "<option value=\"Yes\">Yes</option>\n";
										echo "<option value=\"No\" SELECTED>No</option>\n";
									}
								}
								else 
								{
									echo "<option value=\"Yes\">Yes</option>\n";
									echo "<option value=\"No\">No</option>\n";
								}
								echo "</select>";
							?>
							</td>				
						</tr>
						<tr>
							<td></td>
							<td><label for="coord_enrollment" class="form-required">Coordinator Enrollment</label></td>
							<td><input type="text" id="coord_enrollment" name="coord_enrollment" value="<?php echo ((isset($undergraduateTeachingResult["coord_enrollment"])) ? html_encode($undergraduateTeachingResult["coord_enrollment"]) : html_encode($PROCESSED["coord_enrollment"])); ?>" maxlength="5" style="width: 40px" /></td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="comments" class="form-nrequired">Comments</label></td>
							<td><textarea id="comments" name="comments" style="width: 95%" rows="4"><?php echo ((isset($undergraduateTeachingResult["comments"])) ? html_encode($undergraduateTeachingResult["comments"]) : html_encode($PROCESSED["comments"])); ?></textarea></td>
						</tr>
						<tr>
							<td colspan="3"><h2>Course Information - Not Editable</h2></td>
						</tr>
						<tr>
							<td></td>
							<td><label for="course_number" class="form-required">Course Number</label></td>
							<td><?php echo ((isset($undergraduateTeachingResult["course_number"])) ? html_encode($undergraduateTeachingResult["course_number"]) : html_encode($PROCESSED["course_number"])); ?></td>
						</tr>						
						<tr>
							<td></td>
							<td><label for="lecture_phase" class="form-nrequired">Lecture Phase</label></td>
							<td><?php echo ((isset($undergraduateTeachingResult["lecture_phase"])) ? html_encode($undergraduateTeachingResult["lecture_phase"]) : html_encode($PROCESSED["lecture_phase"])); ?></td>
						</tr>						
						<tr>
							<td></td>
							<td><label for="course_name" class="form-required">Course Name</label></td>
							<td><?php echo ((isset($undergraduateTeachingResult["course_name"])) ? html_encode($undergraduateTeachingResult["course_name"]) : html_encode($PROCESSED["course_name"])); ?></td>
						</tr>
						<tr>
							<td colspan="3"><h2>Hourly Breakdown - Not Editable</h2></td>
						</tr>
						<tr>
							<td></td>
							<td><label for="lecture_hours" class="form-required">Lecture</label></td>
							<td><?php echo ((isset($undergraduateTeachingResult["lecture_hours"])) ? html_encode($undergraduateTeachingResult["lecture_hours"]) : html_encode($PROCESSED["lecture_hours"])); ?></td>
						</tr>
						<tr>
							<td></td>
							<td><label for="lab_hours" class="form-required">Lab</label></td>
							<td><?php echo ((isset($undergraduateTeachingResult["lab_hours"])) ? html_encode($undergraduateTeachingResult["lab_hours"]) : html_encode($PROCESSED["lab_hours"])); ?></td>
						</tr>
						<tr>
							<td></td>
							<td><label for="small_group_hours" class="form-required">Small Group</label></td>							
							<td><?php echo ((isset($undergraduateTeachingResult["small_group_hours"])) ? html_encode($undergraduateTeachingResult["small_group_hours"]) : html_encode($PROCESSED["small_group_hours"])); ?></td>
						</tr>			
						<tr>
							<td></td>
							<td><label for="patient_contact_session_hours" class="form-required">Patient Contact Session</label></td>
							<td><?php echo ((isset($undergraduateTeachingResult["patient_contact_session_hours"])) ? html_encode($undergraduateTeachingResult["patient_contact_session_hours"]) : html_encode($PROCESSED["patient_contact_session_hours"])); ?></td>
						</tr>						
						<tr>
							<td></td>
							<td><label for="symposium_hours" class="form-required">Symposium</label></td>							
							<td><?php echo ((isset($undergraduateTeachingResult["symposium_hours"])) ? html_encode($undergraduateTeachingResult["symposium_hours"]) : html_encode($PROCESSED["symposium_hours"])); ?></td>
						</tr>					
						<tr>
							<td></td>
							<td><label for="directed_independant_learning_hours" class="form-required">Directed Independant Learning</label></td>							
							<td><?php echo ((isset($undergraduateTeachingResult["directed_independant_learning_hours"])) ? html_encode($undergraduateTeachingResult["directed_independant_learning_hours"]) : html_encode($PROCESSED["directed_independant_learning_hours"])); ?></td>
						</tr>					
						<tr>
							<td></td>
							<td><label for="review_feedback_session_hours" class="form-required">Review / Feedback Session</label></td>							
							<td><?php echo ((isset($undergraduateTeachingResult["review_feedback_session_hours"])) ? html_encode($undergraduateTeachingResult["review_feedback_session_hours"]) : html_encode($PROCESSED["review_feedback_session_hours"])); ?></td>
						</tr>					
						<tr>
							<td></td>
							<td><label for="examination_hours" class="form-required">Examination</label></td>							
							<td><?php echo ((isset($undergraduateTeachingResult["examination_hours"])) ? html_encode($undergraduateTeachingResult["examination_hours"]) : html_encode($PROCESSED["examination_hours"])); ?></td>
						</tr>
						<tr>
							<td></td>
							<td><label for="clerkship_seminar_hours" class="form-required">Clerkship Seminar</label></td>							
							<td><?php echo ((isset($undergraduateTeachingResult["clerkship_seminar_hours"])) ? html_encode($undergraduateTeachingResult["clerkship_seminar_hours"]) : html_encode($PROCESSED["clerkship_seminar_hours"])); ?></td>
						</tr>
						<tr>
							<td></td>
							<td><label for="other_hours" class="form-required">Other</label></td>							
							<td><?php echo ((isset($undergraduateTeachingResult["other_hours"])) ? html_encode($undergraduateTeachingResult["other_hours"]) : html_encode($PROCESSED["other_hours"])); ?></td>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="year_reported" class="form-required">Report Year</label></td>
							<td><?php echo ((isset($undergraduateTeachingResult["year_reported"])) ? html_encode($undergraduateTeachingResult["year_reported"]) : html_encode($PROCESSED["year_reported"])); ?></td>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td colspan="3" style="padding-top: 25px">
								<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
								<tr>
									<td style="width: 25%; text-align: left">
										<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/annualreport/education'" />
									</td>
									<td style="width: 75%; text-align: right; vertical-align: middle">
										<span class="content-small">&nbsp;</span>
										<input type="submit" class="btn btn-primary" value="Save" />
									</td>
								</tr>
								</table>
							</td>
						</tr>
						</table>
					</form>
					<br /><br />
					<?php
				break;
			}
		} else {
			$ERROR++;
			$ERRORSTR[] = "In order to edit a undergraduate teaching record you must provide a valid undergraduate teaching record identifier. The provided ID does not exist in this system.";

			echo display_error();

			application_log("notice", "Failed to provide a valid undergraduate teaching record identifer when attempting to edit a undergraduate teaching record.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "In order to edit a teaching record you must provide the teaching identifier.";

		echo display_error();

		application_log("notice", "Failed to provide teaching identifer when attempting to edit a teaching record.");
	}
}
?>