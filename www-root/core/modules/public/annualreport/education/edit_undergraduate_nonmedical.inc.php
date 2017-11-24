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
	$UNDERGRADUATE_NONMEDICAL_TEACHING_ID = $_GET["rid"];
	// This grid should be expanded upon redirecting back to the education index.
	$_SESSION["education_expand_grid"] = "undergraduate_nonmedical_grid";
	
	if($UNDERGRADUATE_NONMEDICAL_TEACHING_ID) {
		$query	= "SELECT * FROM `ar_undergraduate_nonmedical_teaching` WHERE `undergraduate_nonmedical_teaching_id`=".$db->qstr($UNDERGRADUATE_NONMEDICAL_TEACHING_ID)." AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId());
		$result	= $db->GetRow($query);
		if($result) {
			$BREADCRUMB[]	= array("url" => ENTRADA_URL."/annualreport/education?section=edit_undergaduate_nonmedical", "title" => "Edit Undergraduate Other Teaching");
			
			echo "<h1>Edit Undergraduate Other Teaching</h1>\n";

			// Error Checking
			switch($STEP) {
				case 2 :
					$ENDERROR = false;
					/**
					 * Non Required field "course_number" / Course Number
					 */
					if((isset($_POST["course_number"])) && ($course_number = clean_input($_POST["course_number"], array("notags", "trim")))) {
						$PROCESSED["course_number"] = $course_number;
						
						// Ensure this course code belongs here and not under undergraduate supervision
						$supervisionCourseListArray = getUndergraduateSupervisionCourses();
			
						foreach($supervisionCourseListArray as $supervisionCourseValue) {
							$supervisionCourseArray[] = $supervisionCourseValue["undergarduate_supervision_course"];
						}
						
						if(in_array(strtoupper($PROCESSED["course_number"]), $supervisionCourseArray)) {
							$ERROR++;
							$ERRORSTR[] = "This <strong>Course Number</strong> belongs <a href=\"".ENTRADA_URL."/annualreport?section=add_undergrad_sup\"><u>here</u></a>.";
						}
					} else {
						$PROCESSED["course_number"] = "";
					}
					/**
					 * Required field "course_name" / Course Name
					 */
					if((isset($_POST["course_name"])) && ($course_name = clean_input($_POST["course_name"], array("notags", "trim")))) {
						$PROCESSED["course_name"] = $course_name;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Course Name</strong> field is required.";
					}
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
					 * Required field "lec_enrollment" / Lecture Enrollment			 
					 */
					if((isset($_POST["lec_enrollment"])) && ($lec_enrollment = clean_input($_POST["lec_enrollment"], array("int")))) {
						$PROCESSED["lec_enrollment"] = $lec_enrollment;
					} else {
						if(trim($_POST["lec_enrollment"] === "0"))
						{
							$PROCESSED["lec_enrollment"] = trim($_POST["lec_enrollment"]);
						}
						else if(trim($_POST["lec_enrollment"]) == '')
						{
							$PROCESSED["lec_enrollment"] = "0";
						}
						else {
							$ERROR++;
							$ERRORSTR[] = "The <strong>Lecture Enorllment</strong> field is required.";
						}
					}
					
					/**
					 * Required field "lec_hours" / Lecture Hours
					 */
					if((isset($_POST["lec_hours"])) && ($lec_hours = clean_input($_POST["lec_hours"], array("int")))) {
						$PROCESSED["lec_hours"] = $lec_hours;
					} else {
						if(trim($_POST["lec_hours"] === "0"))
						{
							$PROCESSED["lec_hours"] = trim($_POST["lec_hours"]);
						}
						else if(trim($_POST["lec_hours"]) == '')
						{
							$PROCESSED["lec_hours"] = "0";
						}
						else {
							$ERROR++;
							$ERRORSTR[] = "The <strong>Lecture Hours</strong> field is required.";
						}
					}	
					/**
					 * Required field "lab_enrollment" / Lab Enrollment			 
					 */
					if((isset($_POST["lab_enrollment"])) && ($lab_enrollment = clean_input($_POST["lab_enrollment"], array("int")))) {				
						$PROCESSED["lab_enrollment"] = $lab_enrollment;
					} else {
						if(trim($_POST["lab_enrollment"] === "0"))
						{
							$PROCESSED["lab_enrollment"] = trim($_POST["lab_enrollment"]);
						}
						else if(trim($_POST["lab_enrollment"]) == '')
						{
							$PROCESSED["lab_enrollment"] = "0";
						}
						else {
							$ERROR++;
							$ERRORSTR[] = "The <strong>Lab Enrollment</strong> field is required.";
						}
					}			
					/**
					 * Required field "lab_hours" / Lab Hours		 
					 */
					if((isset($_POST["lab_hours"])) && ($lab_hours = clean_input($_POST["lab_hours"], array("int")))) {
						$PROCESSED["lab_hours"] = $lab_hours;
					} else {
						if(trim($_POST["lab_hours"] === "0"))
						{
							$PROCESSED["lab_hours"] = trim($_POST["lab_hours"]);
						}
						else if(trim($_POST["lab_hours"]) == '')
						{
							$PROCESSED["lab_hours"] = "0";
						}
						else {
							$ERROR++;
							$ERRORSTR[] = "The <strong>Lab Hours</strong> field is required.";
						}
					}
					/**
					 * Required field "tut_enrollment" / Tutorial Enrollment			 
					 */
					if((isset($_POST["tut_enrollment"])) && ($tut_enrollment= clean_input($_POST["tut_enrollment"], array("int")))) {
						$PROCESSED["tut_enrollment"] = $tut_enrollment;
					} else {
						if(trim($_POST["tut_enrollment"] === "0"))
						{
							$PROCESSED["tut_enrollment"] = trim($_POST["tut_enrollment"]);
						}
						else if(trim($_POST["tut_enrollment"]) == '')
						{
							$PROCESSED["tut_enrollment"] = "0";
						}
						else {
							$ERROR++;
							$ERRORSTR[] = "Both <strong>Tutorial Enrollment</strong> fields are required.";
						}
					}
					/**
					 * Required field "tut_hours" / Tutorial Hours			 
					 */
					if((isset($_POST["tut_hours"])) && ($tut_hours= clean_input($_POST["tut_hours"], array("int")))) {
						$PROCESSED["tut_hours"] = $tut_hours;
					} else {
						if(trim($_POST["tut_hours"] === "0"))
						{
							$PROCESSED["tut_hours"] = trim($_POST["tut_hours"]);
						}
						else if(trim($_POST["tut_hours"]) == '')
						{
							$PROCESSED["tut_hours"] = "0";
						}
						else {
							$ERROR++;
							$ERRORSTR[] = "Both <strong>Tutorial Hours</strong> fields are required.";
						}
					}
					/**
					 * Required field "sem_enrollment" / Seminar Enrollment.
					 */
					if((isset($_POST["sem_enrollment"])) && ($sem_enrollment = clean_input($_POST["sem_enrollment"], array("int")))) {
						$PROCESSED["sem_enrollment"] = $sem_enrollment;
					} else {
						if(trim($_POST["sem_enrollment"] === "0"))
						{
							$PROCESSED["sem_enrollment"] = trim($_POST["sem_enrollment"]);
						}
						else if(trim($_POST["sem_enrollment"]) == '')
						{
							$PROCESSED["sem_enrollment"] = "0";
						}
						else {
							$ERROR++;
							$ERRORSTR[] = "The <strong>Seminar Enrollment</strong> field is required.";
						}
					}
					/**
					 * Required field "sem_hours" / Seminar Hours.
					 */
					if((isset($_POST["sem_hours"])) && ($sem_hours = clean_input($_POST["sem_hours"], array("int")))) {
						$PROCESSED["sem_hours"] = $sem_hours;
					} else {
						if(trim($_POST["sem_hours"] === "0"))
						{
							$PROCESSED["sem_hours"] = trim($_POST["sem_hours"]);
						}
						else if(trim($_POST["sem_hours"]) == '')
						{
							$PROCESSED["sem_hours"] = "0";
						}
						else {
							$ERROR++;
							$ERRORSTR[] = "The <strong>Seminar Hours</strong> field is required.";
						}
					}
					if($ENTRADA_USER->getClinical()) {
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
					}
					/**
					 * Required field "pbl_hours" / PBL Hours.
					 */
					if((isset($_POST["pbl_hours"])) && ($pbl_hours = clean_input($_POST["pbl_hours"], array("int")))) {
						$PROCESSED["pbl_hours"] = $pbl_hours;
					} else {
						if(trim($_POST["pbl_hours"] === "0"))
						{
							$PROCESSED["pbl_hours"] = trim($_POST["pbl_hours"]);
						}
						else if(trim($_POST["pbl_hours"]) == '')
						{
							$PROCESSED["pbl_hours"] = "0";
						}
						else {
							$ERROR++;
							$ERRORSTR[] = "The <strong>PBL Hours</strong> field is required.";
						}
					}			
					/**
					 * Non-Required field "comments" / Comments.
					 */
					if((isset($_POST["comments"])) && ($comments = clean_input($_POST["comments"], array("notags", "trim")))) {
						$PROCESSED["comments"] = $comments;
					} else {
						$PROCESSED["comments"] = "";
					}		
					/**
					 * Required field "year_reported" / Year Reported.
					 */
					if((isset($_POST["year_reported"])) && ($year_reported = clean_input($_POST["year_reported"], array("notags", "trim")))) {
						$PROCESSED["year_reported"] = $year_reported;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Year Reported</strong> field is required.";
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
						
						if($db->AutoExecute("ar_undergraduate_nonmedical_teaching", $PROCESSED, "UPDATE", "`undergraduate_nonmedical_teaching_id`=".$db->qstr($UNDERGRADUATE_NONMEDICAL_TEACHING_ID))) {
								switch($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
									case "new" :
										$url 	= ENTRADA_URL."/annualreport/education?section=add_external";
										$msg	= "You will now be redirected to add more Graduate Teaching; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
									break;
									case "index" :
									default :
										$url 	= ENTRADA_URL."/annualreport/education";
										$msg	= "You will now be redirected to the education page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
									break;
								}
		
								$SUCCESS++;
								$SUCCESSSTR[]  = "You have successfully edited <strong>".html_encode($PROCESSED["course_name"])."</strong> in the system.<br /><br />".$msg;
								$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000);";
		
								application_log("success", "Edited Undergraduate Other Teaching [".$UNDERGRADUATE_NONMEDICAL_TEACHING_ID."] in the system.");					
		
						} else {
							$ERROR++;
							$ERRORSTR[] = "There was a problem editing this Graduate Teaching record in the system. The MEdIT Unit was informed of this error; please try again later.";
		
							application_log("error", "There was an error editing the Graduate Teaching. Database said: ".$db->ErrorMsg());
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
						$graduateTeachingQuery = "SELECT * FROM `ar_undergraduate_nonmedical_teaching` WHERE `undergraduate_nonmedical_teaching_id` = ".$db->qstr($UNDERGRADUATE_NONMEDICAL_TEACHING_ID);
						$undergraduateTeachingResult = $db->GetRow($graduateTeachingQuery);
					}
					
					if($ERROR) {
						echo display_error();
					}
					?>
					<form action="<?php echo ENTRADA_URL; ?>/annualreport/education?section=edit_undergraduate_nonmedical&amp;step=2&amp;rid=<?php echo $UNDERGRADUATE_NONMEDICAL_TEACHING_ID;?>" method="post">					
					<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Editing Education">
						<colgroup>
							<col style="width: 3%" />
							<col style="width: 20%" />
							<col style="width: 77%" />
						</colgroup>
						<tr>
							<td colspan="3"><h2>Details</h2></td>
						</tr>
						<tr>
							<td></td>
							<td><label for="course_number" class="form-nrequired">Course Number</label></td>
							<td><input type="text" id="course_number" name="course_number" value="<?php echo ((isset($undergraduateTeachingResult["course_number"])) ? html_encode($undergraduateTeachingResult["course_number"]) : html_encode($PROCESSED["course_number"])); ?>" maxlength="255" style="width: 20%" /></td>
						</tr>						
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="course_name" class="form-required">Course Name</label></td>
							<td><input type="text" id="course_name" name="course_name" value="<?php echo ((isset($undergraduateTeachingResult["course_name"])) ? html_encode($undergraduateTeachingResult["course_name"]) : html_encode($PROCESSED["course_name"])); ?>" maxlength="255" style="width: 95%" /></td>
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
							<td style="vertical-align: top"><label for="lec_enrollment" class="form-required">Lecture Enrollment</label></td>
							<td><input type="text" id="lec_enrollment" name="lec_enrollment" value="<?php echo ((isset($undergraduateTeachingResult["lec_enrollment"])) ? html_encode($undergraduateTeachingResult["lec_enrollment"]) : html_encode($PROCESSED["lec_enrollment"])); ?>" maxlength="255" style="width: 40px" /></td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="lec_hours" class="form-required">Lecture Hours</label></td>
							<td><input type="text" id="lec_hours" name="lec_hours" value="<?php echo ((isset($undergraduateTeachingResult["lec_hours"])) ? html_encode($undergraduateTeachingResult["lec_hours"]) : html_encode($PROCESSED["lec_hours"])); ?>" maxlength="255" style="width: 40px" /></td>
						</tr>
						<tr>
							<td></td>
							<td><label for="lab_enrollment" class="form-required">Lab Enrollment</label></td>
							<td><input type="text" id="lab_enrollment" name="lab_enrollment" value="<?php echo ((isset($undergraduateTeachingResult["lab_enrollment"])) ? html_encode($undergraduateTeachingResult["lab_enrollment"]) : html_encode($PROCESSED["lab_enrollment"])); ?>" maxlength="255" style="width: 40px" /></td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="lab_hours" class="form-required">Lab Hours</label></td>
							<td><input type="text" id="lab_hours" name="lab_hours" value="<?php echo ((isset($undergraduateTeachingResult["lab_hours"])) ? html_encode($undergraduateTeachingResult["lab_hours"]) : html_encode($PROCESSED["lab_hours"])); ?>" maxlength="255" style="width: 40px" /></td>
						</tr>
						<tr>
							<td></td>
							<td><label for="tut_enrollment" class="form-required">Tutorial Enrollment</label></td>							
							<td><input type="text" id="tut_enrollment" name="tut_enrollment" value="<?php echo ((isset($undergraduateTeachingResult["tut_enrollment"])) ? html_encode($undergraduateTeachingResult["tut_enrollment"]) : html_encode($PROCESSED["tut_enrollment"])); ?>" maxlength="255" style="width: 40px" /></td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="tut_hours" class="form-required">Tutorial Hours</label></td>							
							<td><input type="text" id="tut_hours" name="tut_hours" value="<?php echo ((isset($undergraduateTeachingResult["tut_hours"])) ? html_encode($undergraduateTeachingResult["tut_hours"]) : html_encode($PROCESSED["tut_hours"])); ?>" maxlength="255" style="width: 40px" /></td>
						</tr>			
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="sem_enrollment" class="form-required">Seminar Enrollment</label></td>							
							<td><input type="text" id="sem_enrollment" name="sem_enrollment" value="<?php echo ((isset($undergraduateTeachingResult["sem_enrollment"])) ? html_encode($undergraduateTeachingResult["sem_enrollment"]) : html_encode($PROCESSED["sem_enrollment"])); ?>" maxlength="255" style="width: 40px" /></td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="sem_hours" class="form-required">Seminar Hours</label></td>
							<td><input type="text" id="sem_hours" name="sem_hours" value="<?php echo ((isset($undergraduateTeachingResult["sem_hours"])) ? html_encode($undergraduateTeachingResult["sem_hours"]) : html_encode($PROCESSED["sem_hours"])); ?>" maxlength="255" style="width: 40px" /></td>
						</tr>
						<?php if($ENTRADA_USER->getClinical()) { ?>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="coord_enrollment" class="form-required">Coordinator Enrollment</label></td>
							<td><input type="text" id="coord_enrollment" name="coord_enrollment" value="<?php echo ((isset($undergraduateTeachingResult["coord_enrollment"])) ? html_encode($undergraduateTeachingResult["coord_enrollment"]) : html_encode($PROCESSED["coord_enrollment"])); ?>" maxlength="5" style="width: 40px" /></td>
						</tr>
						<?php } ?>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="pbl_hours" class="form-required">PBL Hours</label></td>							
							<td><input type="text" id="pbl_hours" name="pbl_hours" value="<?php echo ((isset($undergraduateTeachingResult["pbl_hours"])) ? html_encode($undergraduateTeachingResult["pbl_hours"]) : html_encode($PROCESSED["pbl_hours"])); ?>" maxlength="255" style="width: 40px" /></td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="comments" class="form-nrequired">Comments</label></td>
							<td><textarea id="comments" name="comments" style="width: 95%" rows="4"><?php echo ((isset($undergraduateTeachingResult["comments"])) ? html_encode($undergraduateTeachingResult["comments"]) : html_encode($PROCESSED["comments"])); ?></textarea></td>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td></td>
							<td><label for="year_reported" class="form-required">Report Year</label></td>
							<?php
							if((isset($PROCESSED["year_reported"]) && $PROCESSED["year_reported"] != "")) {
								displayARYearReported($PROCESSED["year_reported"], $AR_CUR_YEAR, $AR_PAST_YEARS, $AR_FUTURE_YEARS, true);
							} else {
								displayARYearReported($undergraduateTeachingResult["year_reported"], $AR_CUR_YEAR, $AR_PAST_YEARS, $AR_FUTURE_YEARS, false);
							}
							?>
						</tr>
						<tr>
							<td colspan="3" style="padding-top: 25px">
								<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
								<tr>
									<td style="width: 25%; text-align: left">
										<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/annualreport/education'" />
									</td>
									<td style="width: 75%; text-align: right; vertical-align: middle">
										<span class="content-small">After saving:</span>
										<select id="post_action" name="post_action">							
										<option value="new"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "add") ? " selected=\"selected\"" : ""); ?>>Add More Education</option>
										<option value="index"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "index") ? " selected=\"selected\"" : ""); ?>>Return to Education list</option>
										</select>
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
			$ERRORSTR[] = "In order to edit a teaching record you must provide a valid teaching identifier. The provided ID does not exist in this system.";

			echo display_error();

			application_log("notice", "Failed to provide a valid teaching identifer when attempting to edit a graduate teaching.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "In order to edit a teaching record you must provide the teaching identifier.";

		echo display_error();

		application_log("notice", "Failed to provide teaching identifer when attempting to edit a teaching record.");
	}
}
?>