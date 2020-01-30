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
	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/annualreport/activityprofile?section=edit_profile", "title" => "Edit Activity Profile");
	
	// This grid should be expanded upon redirecting back to the profile index.
	$_SESSION["profile_expand_grid"] = "profile_grid";
	
	echo "<h1>Edit Activity Profile</h1>\n";
	
	if(!$ENTRADA_USER->getClinical()) {
		$PROFILE_ID = $_GET["rid"];
		if($PROFILE_ID) {
			$query	= "SELECT * FROM `ar_profile` WHERE `profile_id`=".$db->qstr($PROFILE_ID)." AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId());
			$result	= $db->GetRow($query);
			if($result) {
				// Error Checking
				switch($STEP) {
					case 2 :
					/**
					 * Required field "education" / Education
					 */			
					if((isset($_POST["percentage_education"])) && ($education = clean_input($_POST["percentage_education"], array("float", "trim")))) {				
						$PROCESSED["education"] = $education;				
					} else {
						$PROCESSED["education"] = 0.00;
					}
					/**
					 * Required field "research" / Scholarship / Research
					 */
					if((isset($_POST["percentage_research"])) && (($research = clean_input($_POST["percentage_research"], array("float", "trim"))))) {
						$PROCESSED["research"] = $research;
					} else {
						$PROCESSED["research"] = 0.00;
					}
					/**
					 * Required field "service" / Service / Administration
					 */
					if((isset($_POST["percentage_service"])) && ($service = clean_input($_POST["percentage_service"], array("float", "trim")))) {
						$PROCESSED["service"] = $service;
					} else {
						$PROCESSED["service"] = 0.00;
					}
					/**
					 * Required field "total" / Total
					 */
					if((isset($_POST["total"])) && ($total = clean_input($_POST["total"], array("float", "trim")))) {
						if($total != 100.00)
						{
							$missing = round((100.00 - $total), 2);
							$ERROR++;
							$ERRORSTR[] = "The <strong>% Total</strong> must equal 100.00 ($missing unaccounted for).";
						}
						$PROCESSED["total"] = $total;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Total</strong> field is required.";
					}
					/**
					 * Required field "consistent" / Consistent?
					 */
					if((isset($_POST["consistent"])) && ($consistent = clean_input($_POST["consistent"], array("notags", "trim")))) {
						$PROCESSED["consistent"] = $consistent;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Consistent?</strong> field is required.";
					}
					/**
					 * Non-Required field "consistent_comments" / Consistent Comments.
					 */
					if((isset($_POST["consistent_comments"])) && ($consistent_comments = clean_input($_POST["consistent_comments"], array("notags", "trim")))) {
						$PROCESSED["consistent_comments"] = $consistent_comments;
					} else if($PROCESSED["consistent"] == "No") {
							$ERROR++;
							$ERRORSTR[] = "The Consistent? <strong>Comments</strong> field is required because you answered \"No\" to <strong>Consistent?</strong>.";
					} else if($PROCESSED["consistent"] == "Yes" && ($consistent_comments == "" || !isset($_POST["consistent_comments"]))) {
						$PROCESSED["consistent_comments"] = "";
					}
					/**
					 * Required field "career_goals" / In Keeping?
					 */
					if((isset($_POST["career_goals"])) && ($career_goals = clean_input($_POST["career_goals"], array("notags", "trim")))) {
						$PROCESSED["career_goals"] = $career_goals;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>In Keeping?</strong> field is required.";
					} 
					/**
					 * Non-Required field "career_comments" / Career Comments.
					 */
					if((isset($_POST["career_comments"])) && ($career_comments = clean_input($_POST["career_comments"], array("notags", "trim")))) {
						$PROCESSED["career_comments"] = $career_comments;
					} else if($PROCESSED["career_goals"] == "No") {
							$ERROR++;
							$ERRORSTR[] = "The In Keeping? <strong>Comments</strong> field is required because you answered \"No\" to <strong>In Keeping?</strong>.";
					} else if($PROCESSED["career_goals"] == "Yes" && ($career_comments == "" || !isset($_POST["career_comments"]))) {
						$PROCESSED["career_comments"] = "";
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
					if((isset($_POST["year_reported"])) && ($year_reported = clean_input($_POST["year_reported"], array("int")))) {
						$PROCESSED["year_reported"] = $year_reported;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Year Reported</strong> field is required.";
					}
					/**
					 * Required field "department" / Departmental Association:
					 */
					if((isset($_POST["department"])) && ($department = clean_input($_POST["department"], array("notags", "trim")))) {
						$PROCESSED["department"] = $department;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Departmental Association</strong> field is required.";
					}
					/**
					 * Non-Required field "cross_department" / Cross Department(s):
					 */
					if((isset($_POST["cross_department"])) && ($cross_department = clean_input($_POST["cross_department"], array("notags", "trim")))) {
						$PROCESSED["cross_department"] = $cross_department;
					} else {
						$PROCESSED["cross_department"] = "";
					}
					/**
					 * Required field "report_completed" / Report Completed?.
					 */
					if((isset($_POST["report_completed"])) && ($report_completed = clean_input($_POST["report_completed"], array("notags", "trim")))) {
						$PROCESSED["report_completed"] = $report_completed;
					} else {
						$ERROR++;
						$ERRORSTR[] = "Please indicate whether you have finished <strong>Reporting</strong> for this activity profile year.  
						You can still come back to add information later this is just so that the Academic Affairs Office can know who has completed the report.";
					}
					/**
					 * Duplicate Check (based on reporting year, only one per year).
					 */
					$sql = "SELECT * 
					FROM ar_profile 
					WHERE proxy_id = ".$ENTRADA_USER->getActiveId()."
					AND year_reported = $year_reported
					AND profile_id != $PROFILE_ID";
					
					$result = $db->GetRow($sql);
					
					if($result)
					{
						$ERROR++;
						$ERRORSTR[] = "The <strong>Year Reported</strong> (".$year_reported.") already has a record associated with it.  You cannot have multiple profiles per year.";
					}	
					
					$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "index";
					
						if(!$ERROR) {
							$PROCESSED["updated_date"]	= time();
							$PROCESSED["updated_by"]	= $ENTRADA_USER->getID();
							$PROCESSED["proxy_id"]		= $ENTRADA_USER->getActiveId();
							
							if($db->AutoExecute("ar_profile", $PROCESSED, "UPDATE", "`profile_id`=".$db->qstr($PROFILE_ID))) {
									switch($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
										case "new" :
											$url 	= ENTRADA_URL."/annualreport/activityprofile?section=add_profile";
											$msg	= "You will now be redirected to add another Activity Profile; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
										break;
										case "index" :
										default :
											$url 	= ENTRADA_URL."/annualreport/activityprofile";
											$msg	= "You will now be redirected to the Activity Profile page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
										break;
									}
			
									$SUCCESS++;
									$SUCCESSSTR[]  = "You have successfully edited your <strong>Activity Profile for ".html_encode($PROCESSED["year_reported"])."</strong> in the system.<br /><br />".$msg;
									$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000);";
			
									application_log("success", "Edited Activity Profile [".$PROFILE_ID."] in the system.");					
			
							} else {
								$ERROR++;
								$ERRORSTR[] = "There was a problem editing this Activity Profile record in the system. The MEdTech Unit was informed of this error; please try again later.";
			
								application_log("error", "There was an error editing the Activity Profile. Database said: ".$db->ErrorMsg());
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
							$profileQuery = "SELECT * FROM `ar_profile` WHERE `profile_id` ='$PROFILE_ID'";						
							$profileResult = $db->GetRow($profileQuery);
						}
						$HEAD[]		= "<script language=\"JavaScript\" type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calc_total.js\"></script>\n";
						
						if($ERROR) {
							echo display_error();
						}
						?>
						<form action="<?php echo ENTRADA_URL; ?>/annualreport/activityprofile?section=edit_profile&amp;step=2&amp;rid=<?php echo $PROFILE_ID;?>" method="post">
						<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Editing Activity Profile">
						<colgroup>
							<col style="width: 3%" />
							<col style="width: 37%" />
							<col style="width: 60%" />
						</colgroup>
						<tr>
							<td colspan="3"><h2>Details</h2></td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="percentage_education" class="form-required">% Education</label></td>
							<td><input type="text" id="percentage_education" name="percentage_education" onBlur="javascript:while(''+this.value.charAt(0)==' ')this.value=this.value.substring(1,this.value.length);while(''+this.value.charAt(this.value.length-1)==' ')this.value=this.value.substring(0,this.value.length-1);CalculateTotal(this.form)"
							value="<?php echo ((isset($profileResult["education"])) ? html_encode($profileResult["education"]) : html_encode($PROCESSED["education"])); ?>" maxlength="6" style="width: 50px" /></td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="percentage_research" class="form-required">% Scholarship / Research</label></td>
							<td><input type="text" id="percentage_research" name="percentage_research" onBlur="javascript:while(''+this.value.charAt(0)==' ')this.value=this.value.substring(1,this.value.length);while(''+this.value.charAt(this.value.length-1)==' ')this.value=this.value.substring(0,this.value.length-1);CalculateTotal(this.form)"
							value="<?php echo ((isset($profileResult["research"])) ? html_encode($profileResult["research"]) : html_encode($PROCESSED["research"])); ?>" maxlength="6" style="width: 50px" /></td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="percentage_service" class="form-required">% Service / Administration</label></td>
							<td><input type="text" id="percentage_service" name="percentage_service" onBlur="javascript:while(''+this.value.charAt(0)==' ')this.value=this.value.substring(1,this.value.length);while(''+this.value.charAt(this.value.length-1)==' ')this.value=this.value.substring(0,this.value.length-1);CalculateTotal(this.form)"
							value="<?php echo ((isset($profileResult["service"])) ? html_encode($profileResult["service"]) : html_encode($PROCESSED["service"])); ?>" maxlength="6" style="width: 50px" /></td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="total" class="form-required">% Total</label></td>
							<td><input type="text" id="total" name="total" readonly 
							value="<?php echo ((isset($profileResult["total"])) ? html_encode($profileResult["total"]) : html_encode($PROCESSED["total"])); ?>" maxlength="6" style="width: 50px" /></td>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top" colspan="2"><label for="consistent_text" class="form-nrequired">
								Are these percentages consistent with the current workload standard of your 
								Department / School / Unit and your assigned role for the calendar year? If no, please comment.
							</label></td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="consistent" class="form-required">Consistent?</label></td>				
							<td><select name="consistent" id="consistent" style="vertical-align: middle">
							<option value=""></option>
							<?php
								echo "<option value=\"Yes\"".(($profileResult["consistent"] == "Yes" || $PROCESSED["consistent"] == "Yes") ? " selected=\"selected\"" : "").">Yes</option>\n";
								echo "<option value=\"No\"".(($profileResult["consistent"] == "No" || $PROCESSED["consistent"] == "No") ? " selected=\"selected\"" : "").">No</option>\n";
								echo "</select>";
							?>
							</td>				
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="consistent_comments" class="form-nrequired">Comments (required if previous answer is "No")</label></td>
							<td><textarea id="consistent_comments" name="consistent_comments" style="width: 95%" rows="4"><?php echo ((isset($profileResult["consistent_comments"])) ? html_encode($profileResult["consistent_comments"]) : html_encode($PROCESSED["consistent_comments"])); ?></textarea></td>				
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top" colspan="2"><label for="career_goals_text" class="form-nrequired">
								Are these percentages in keeping with your overall career goals? If no, please comment.
							</label></td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="career_goals" class="form-required">In Keeping?</label></td>				
							<td><select name="career_goals" id="career_goals" style="vertical-align: middle">
							<option value=""></option>
							<?php
								echo "<option value=\"Yes\"".(($profileResult["career_goals"] == "Yes" || $PROCESSED["career_goals"] == "Yes") ? " selected=\"selected\"" : "").">Yes</option>\n";
								echo "<option value=\"No\"".(($profileResult["career_goals"] == "No" || $PROCESSED["career_goals"] == "No") ? " selected=\"selected\"" : "").">No</option>\n";
								echo "</select>";
							?>
							</td>				
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="career_comments" class="form-nrequired">Comments (required if previous answer is "No")</label></td>
							<td><textarea id="career_comments" name="career_comments" style="width: 95%" rows="4"><?php echo ((isset($profileResult["career_comments"])) ? html_encode($profileResult["career_comments"]) : html_encode($PROCESSED["career_comments"])); ?></textarea></td>				
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="comments" class="form-nrequired">Additional Comments</label></td>
							<td><textarea id="comments" name="comments" style="width: 95%" rows="4"><?php echo ((isset($profileResult["comments"])) ? html_encode($profileResult["comments"]) : html_encode($PROCESSED["comments"])); ?></textarea></td>
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
								displayARYearReported($profileResult["year_reported"], $AR_CUR_YEAR, $AR_PAST_YEARS, $AR_FUTURE_YEARS, false);
							}
							?>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="department" class="form-required">Departmental Association</label></td>
							<td><input type="text" id="department" name="department" 
							value="<?php echo (html_encode($profileResult["department"]) != "" ? $profileResult["department"] : $PROCESSED["department"]); ?>" style="width: 500px" /></td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="cross_department" class="form-nrequired">Cross Department(s)</label></td>
							<td><input type="text" id="cross_department" name="cross_department" 
							value="<?php echo (html_encode($profileResult["cross_department"]) != "" ? $profileResult["cross_department"] : $PROCESSED["cross_department"]); ?>" style="width: 500px" />
							<div class="content-small" style="width: 95%"><strong>Note:</strong> The department fields have been populated for you, you can change these if you so desire. This is what will appear on the cover of your annual report.</div></td>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td></td>
							<td><label for="report_completed" class="form-required">Report Completed For Current Activity Profile Year?</label></td>
							<td>
							<?php
								if(!isset($PROCESSED["report_completed"]) || $PROCESSED["report_completed"] == '')
								{
									$report_completed = $profileResult['report_completed'];
								}
								else 
								{
									$report_completed = $PROCESSED['report_completed'];
								}
								
								if($report_completed == "yes")
								{
									echo "<input type=\"radio\" id=\"report_completed\" name=\"report_completed\" value=\"yes\"/ CHECKED> Yes (you can still add information later if you say yes)<br>
									<input type=\"radio\" id=\"report_completed\" name=\"report_completed\" value=\"no\"/> No<br>";
								}
								elseif($report_completed == "no")
								{
									echo "<input type=\"radio\" id=\"report_completed\" name=\"report_completed\" value=\"yes\"/> Yes (you can still add information later if you say yes)<br>
									<input type=\"radio\" id=\"report_completed\" name=\"report_completed\" value=\"no\"/ CHECKED> No<br>";
								}
								else
								{
									echo "<input type=\"radio\" id=\"report_completed\" name=\"report_completed\" value=\"yes\"/> Yes (you can still add information later if you say yes)<br>
									<input type=\"radio\" id=\"report_completed\" name=\"report_completed\" value=\"no\"/> No<br>";
								}
							?>
							</td>
						</tr>
						<tr>
							<td colspan="3" style="padding-top: 25px">
								<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
								<tr>
									<td style="width: 25%; text-align: left">
										<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/annualreport/activityprofile'" />	
									</td>
									<td style="width: 75%; text-align: right; vertical-align: middle">
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
				$ERRORSTR[] = "In order to edit a Activity Profile record you must provide a valid Activity Profile identifier. The provided ID does not exist in this system.";
	
				echo display_error();
	
				application_log("notice", "Failed to provide a valid Activity Profile identifer when attempting to edit a Activity Profile record.");
			}
		} else {
			$ERROR++;
			$ERRORSTR[] = "In order to edit a Activity Profile record you must provide the Activity Profile identifier.";
	
			echo display_error();
	
			application_log("notice", "Failed to provide Activity Profile identifer when attempting to edit a Activity Profile record.");
		}
	} else {
		$PROFILE_ID = $_GET["rid"];
		if($PROFILE_ID) {
			$query	= "SELECT * FROM `ar_profile` WHERE `profile_id`=".$db->qstr($PROFILE_ID);
			$result	= $db->GetRow($query);
			if($result) {
				// Error Checking
				switch($STEP) {
					case 2 :
					/**
					 * Required field "education" / Education Outside Clinical Setting
					 */			
					if((isset($_POST["percentage_education"])) && ($education = clean_input($_POST["percentage_education"], array("float", "trim")))) {				
						$PROCESSED["education"] = $education;				
					} else {
						$PROCESSED["education"] = 0.00;	
					}
					/**
					 * Required field "research" / Scholarship / Research
					 */
					if((isset($_POST["percentage_research"])) && (($research = clean_input($_POST["percentage_research"], array("float", "trim"))))) {
						$PROCESSED["research"] = $research;
					} else {
						$PROCESSED["research"] = 0.00;	
					}
					/**
					 * Required field "clinical" / Non-Teaching Clinical Activity
					 */
					if((isset($_POST["percentage_clinical"])) && ($clinical = clean_input($_POST["percentage_clinical"], array("float", "trim")))) {
						$PROCESSED["clinical"] = $clinical;
					} else {
						$PROCESSED["clinical"] = 0.00;	
					}
					/**
					 * Required field "combined" / Combined Clinical / Education Activity
					 */
					if((isset($_POST["percentage_combined"])) && ($combined = clean_input($_POST["percentage_combined"], array("float", "trim")))) {
						$PROCESSED["combined"] = $combined;
					} else {
						$PROCESSED["combined"] = 0.00;	
					}
					/**
					 * Required field "service" / Service / Administration
					 */
					if((isset($_POST["percentage_service"])) && ($service = clean_input($_POST["percentage_service"], array("float", "trim")))) {
						$PROCESSED["service"] = $service;
					} else {
						$PROCESSED["service"] = 0.00;	
					}
					/**
					 * Required field "total" / Total
					 */
					if((isset($_POST["total"])) && ($total = clean_input($_POST["total"], array("float", "trim")))) {
						if($total != 100.00)
						{
							$missing = round((100.00 - $total), 2);
							$ERROR++;
							$ERRORSTR[] = "The <strong>% Total</strong> must equal 100.00 ($missing unaccounted for).";
						}
						$PROCESSED["total"] = $total;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Total</strong> field is required.";
					}
					/**
					 * Required field "hospital_hours" / Average Hospital Hours / Week
					 */
					if((isset($_POST["hospital_hours"])) && ($hospital_hours = clean_input($_POST["hospital_hours"], array("int")))) {
						$PROCESSED["hospital_hours"] = $hospital_hours;
					} else {
						if(trim($_POST["hospital_hours"] === "0"))
						{
							$PROCESSED["hospital_hours"] = trim($_POST["hospital_hours"]);
						}
						else if(trim($_POST["hospital_hours"]) == '')
						{
							$PROCESSED["hospital_hours"] = "0";
						}
						else {
							$ERROR++;
							$ERRORSTR[] = "The <strong>Average Hospital Hours / Week</strong> field is required.";
						}
					}
					/**
					 * Required field "on_call_hours" / Average On-Call Hours / Week
					 */
					if((isset($_POST["on_call_hours"])) && ($on_call_hours = clean_input($_POST["on_call_hours"], array("int")))) {
						$PROCESSED["on_call_hours"] = $on_call_hours;
					} else {
						if(trim($_POST["on_call_hours"] === "0"))
						{
							$PROCESSED["on_call_hours"] = trim($_POST["on_call_hours"]);
						}
						else if(trim($_POST["on_call_hours"]) == '')
						{
							$PROCESSED["on_call_hours"] = "0";
						}
						else {
							$ERROR++;
							$ERRORSTR[] = "The <strong>Average On-Call Hours / Week</strong> field is required.";
						}
					}
					/**
					 * Required field "consistent" / Consistent?
					 */
					if((isset($_POST["consistent"])) && ($consistent = clean_input($_POST["consistent"], array("notags", "trim")))) {
						$PROCESSED["consistent"] = $consistent;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Consistent?</strong> field is required.";
					}
					/**
					 * Non-Required field "consistent_comments" / Consistent Comments.
					 */
					if((isset($_POST["consistent_comments"])) && ($consistent_comments = clean_input($_POST["consistent_comments"], array("notags", "trim")))) {
						$PROCESSED["consistent_comments"] = $consistent_comments;
					} else if($PROCESSED["consistent"] == "No") {
							$ERROR++;
							$ERRORSTR[] = "The Consistent? <strong>Comments</strong> field is required because you answered \"No\" to <strong>Consistent?</strong>.";
					} else {
						$PROCESSED["consistent_comments"] = "";
					}
					/**
					 * Required field "career_goals" / In Keeping?
					 */
					if((isset($_POST["career_goals"])) && ($career_goals = clean_input($_POST["career_goals"], array("notags", "trim")))) {
						$PROCESSED["career_goals"] = $career_goals;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>In Keeping?</strong> field is required.";
					}			
					/**
					 * Non-Required field "career_comments" / Career Comments.
					 */
					if((isset($_POST["career_comments"])) && ($career_comments = clean_input($_POST["career_comments"], array("notags", "trim")))) {
						$PROCESSED["career_comments"] = $career_comments;
					} else if($PROCESSED["career_goals"] == "No") {
							$ERROR++;
							$ERRORSTR[] = "The In Keeping? <strong>Comments</strong> field is required because you answered \"No\" to <strong>In Keeping?</strong>.";
					} else {
						$PROCESSED["career_comments"] = "";
					}
					/**
					 * Required field "roles" / Role(s)?
					 */
					if(count($_POST["roles"]) > 0) {
						for($i=0; $i < count($_POST["roles"]); $i++) {
							$PROCESSED["roles"][] = clean_input($_POST["roles"][$i], array("notags", "trim"));
						}
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Role(s)?</strong> field is required.";
					}
					/**
					 * Required field "roles_compatible" / Compatible?
					 */
					if((isset($_POST["roles_compatible"])) && ($roles_compatible = clean_input($_POST["roles_compatible"], array("notags", "trim")))) {
						$PROCESSED["roles_compatible"] = $roles_compatible;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Compatible?</strong> field is required.";
					}			
					/**
					 * Non-Required field "roles_compatible" / Compatible? Comments
					 */
					if((isset($_POST["roles_comments"])) && ($roles_compatible = clean_input($_POST["roles_comments"], array("notags", "trim")))) {
						$PROCESSED["roles_comments"] = $roles_compatible;
					} else if($PROCESSED["roles_compatible"] == "No") {
							$ERROR++;
							$ERRORSTR[] = "The Compatible? <strong>Comments</strong> field is required because you answered \"No\" to <strong>Compatible?</strong>.";
					} else {
						$PROCESSED["roles_comments"] = "";
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
					 * Non-Required field "education_comments" / Education Comments.
					 */
					if((isset($_POST["education_comments"])) && ($comments = clean_input($_POST["education_comments"], array("notags", "trim")))) {
						$PROCESSED["education_comments"] = $comments;
					} else {
						$PROCESSED["education_comments"] = "";
					}
					/**
					 * Non-Required field "research_comments" / Research Comments.
					 */
					if((isset($_POST["research_comments"])) && ($comments = clean_input($_POST["research_comments"], array("notags", "trim")))) {
						$PROCESSED["research_comments"] = $comments;
					} else {
						$PROCESSED["research_comments"] = "";
					}
					
					/**
					 * Non-Required field "clinical_comments" / Clincal Comments.
					 */
					if((isset($_POST["clinical_comments"])) && ($comments = clean_input($_POST["clinical_comments"], array("notags", "trim")))) {
						$PROCESSED["clinical_comments"] = $comments;
					} else {
						$PROCESSED["clinical_comments"] = "";
					}			
					
					/**
					 * Non-Required field "service_comments" / Service Comments.
					 */
					if((isset($_POST["service_comments"])) && ($comments = clean_input($_POST["service_comments"], array("notags", "trim")))) {
						$PROCESSED["service_comments"] = $comments;
					} else {
						$PROCESSED["service_comments"] = "";
					}			
					/**
					 * Required field "year_reported" / Year Reported.
					 */
					if((isset($_POST["year_reported"])) && ($year_reported = clean_input($_POST["year_reported"], array("int")))) {
						$PROCESSED["year_reported"] = $year_reported;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Year Reported</strong> field is required.";
					}
					/**
					 * Required field "department" / Departmental Association:
					 */
					if((isset($_POST["department"])) && ($department = clean_input($_POST["department"], array("notags", "trim")))) {
						$PROCESSED["department"] = $department;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Departmental Association</strong> field is required.";
					}
					/**
					 * Non-Required field "cross_department" / Cross Department(s):
					 */
					if((isset($_POST["cross_department"])) && ($cross_department = clean_input($_POST["cross_department"], array("notags", "trim")))) {
						$PROCESSED["cross_department"] = $cross_department;
					} else {
						$PROCESSED["cross_department"] = "";
					}
					/**
					 * Required field "report_completed" / Report Completed?.
					 */
					if((isset($_POST["report_completed"])) && ($report_completed = clean_input($_POST["report_completed"], array("notags", "trim")))) {
						$PROCESSED["report_completed"] = $report_completed;
					} else {
						$ERROR++;
						$ERRORSTR[] = "Please indicate whether you have finished <strong>Reporting</strong> for this activity profile year.  
						You can still come back to add information later this is just so that the Academic Affairs Office can know who has completed the report.";
					}
						
					/**
					 * Duplicate Check (based on reporting year, only one per year).
					 */
					$sql = "SELECT * 
					FROM ar_profile 
					WHERE proxy_id = ".$ENTRADA_USER->getActiveId()."
					AND year_reported = $year_reported
					AND profile_id != $PROFILE_ID";
					
					$result = $db->GetRow($sql);
					
					if($result)
					{
						$ERROR++;
						$ERRORSTR[] = "The <strong>Year Reported</strong> (".$year_reported.") already has a record associated with it.  You cannot have multiple profiles per year.";
					}
					$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "index";
						
						if(!$ERROR) {
							$PROCESSED["updated_date"]	= time();
							$PROCESSED["updated_by"]	= $ENTRADA_USER->getID();
							$PROCESSED["proxy_id"]		= $ENTRADA_USER->getActiveId();
							
							$PROCESSED["roles"] 		= implode(", ", $PROCESSED["roles"]);
							
							if($db->AutoExecute("ar_profile", $PROCESSED, "UPDATE", "`profile_id`=".$db->qstr($PROFILE_ID))) {
									switch($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
										case "new" :
											$url 	= ENTRADA_URL."/annualreport/activityprofile?section=add_profile";
											$msg	= "You will now be redirected to add another Activity Profile; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
										break;
										case "index" :
										default :
											$url 	= ENTRADA_URL."/annualreport/activityprofile";
											$msg	= "You will now be redirected to the Activity Profile page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
										break;
									}
			
									$SUCCESS++;
									$SUCCESSSTR[]  = "You have successfully edited your <strong>Activity Profile for ".html_encode($PROCESSED["year_reported"])."</strong> in the system.<br /><br />".$msg;
									$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000);";
			
									application_log("success", "Edited Activity Profile [".$PROFILE_ID."] in the system.");					
			
							} else {
								$ERROR++;
								$ERRORSTR[] = "There was a problem editing this Activity Profile record in the system. The MEdTech Unit was informed of this error; please try again later.";
			
								application_log("error", "There was an error editing the Activity Profile. Database said: ".$db->ErrorMsg());
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
							$profileQuery = "SELECT * FROM `ar_profile` WHERE `profile_id` = ".$db->qstr($PROFILE_ID);						
							$profileResult = $db->GetRow($profileQuery);
							
							//Have to explode roles here for multiple select box (stored in DB as comma seperated with a space for outputting purposes)
							$profileResult["roles"] = explode(", ", $profileResult["roles"]);
						}
						
						$HEAD[]		= "<script language=\"JavaScript\" type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calc_total.js\"></script>\n";
						
						if($ERROR) {
							echo display_error();
						}
						?>
						<form action="<?php echo ENTRADA_URL; ?>/annualreport/activityprofile?section=edit_profile&amp;step=2&amp;rid=<?php echo $PROFILE_ID;?>" method="post">
						<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Editing Activity Profile">
						<colgroup>
							<col style="width: 3%" />
							<col style="width: 37%" />
							<col style="width: 60%" />
						</colgroup>
						<tr>
							<td colspan="3"><h2>Details</h2></td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="percentage_education" class="form-required">% Education Outside Clinical Setting</label></td>
							<td><input type="text" id="percentage_education" name="percentage_education" onBlur="javascript:while(''+this.value.charAt(0)==' ')this.value=this.value.substring(1,this.value.length);while(''+this.value.charAt(this.value.length-1)==' ')this.value=this.value.substring(0,this.value.length-1);CalculateTotal(this.form)"
							value="<?php echo ((isset($profileResult["education"])) ? html_encode($profileResult["education"]) : html_encode($PROCESSED["education"])); ?>" maxlength="6" style="width: 50px" /></td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="percentage_research" class="form-required">% Scholarship / Research</label></td>
							<td><input type="text" id="percentage_research" name="percentage_research" onBlur="javascript:while(''+this.value.charAt(0)==' ')this.value=this.value.substring(1,this.value.length);while(''+this.value.charAt(this.value.length-1)==' ')this.value=this.value.substring(0,this.value.length-1);CalculateTotal(this.form)"
							value="<?php echo ((isset($profileResult["research"])) ? html_encode($profileResult["research"]) : html_encode($PROCESSED["research"])); ?>" maxlength="6" style="width: 50px" /></td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="percentage_clinical" class="form-required">% Non-Teaching Clinical Activity</label></td>
							<td><input type="text" id="percentage_clinical" name="percentage_clinical" onBlur="javascript:while(''+this.value.charAt(0)==' ')this.value=this.value.substring(1,this.value.length);while(''+this.value.charAt(this.value.length-1)==' ')this.value=this.value.substring(0,this.value.length-1);CalculateTotal(this.form)"
							value="<?php echo ((isset($profileResult["clinical"])) ? html_encode($profileResult["clinical"]) : html_encode($PROCESSED["clinical"])); ?>" maxlength="6" style="width: 50px" /></td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="percentage_combined" class="form-required">% Combined Clinical / Education Activity</label></td>
							<td><input type="text" id="percentage_combined" name="percentage_combined" onBlur="javascript:while(''+this.value.charAt(0)==' ')this.value=this.value.substring(1,this.value.length);while(''+this.value.charAt(this.value.length-1)==' ')this.value=this.value.substring(0,this.value.length-1);CalculateTotal(this.form)"
							value="<?php echo ((isset($profileResult["combined"])) ? html_encode($profileResult["combined"]) : html_encode($PROCESSED["combined"])); ?>" maxlength="6" style="width: 50px" /></td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="percentage_service" class="form-required">% Service / Administration</label></td>
							<td><input type="text" id="percentage_service" name="percentage_service" onBlur="javascript:while(''+this.value.charAt(0)==' ')this.value=this.value.substring(1,this.value.length);while(''+this.value.charAt(this.value.length-1)==' ')this.value=this.value.substring(0,this.value.length-1);CalculateTotal(this.form)"
							value="<?php echo ((isset($profileResult["service"])) ? html_encode($profileResult["service"]) : html_encode($PROCESSED["service"])); ?>" maxlength="6" style="width: 50px" /></td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="total" class="form-required">% Total</label></td>
							<td><input type="text" id="total" name="total" readonly 
							value="<?php echo ((isset($profileResult["total"])) ? html_encode($profileResult["total"]) : html_encode($PROCESSED["total"])); ?>" maxlength="6" style="width: 50px" /></td>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="hospital_hours" class="form-required">Average Hospital Hours / Week</label></td>
							<td><input type="text" id="hospital_hours" name="hospital_hours" value="<?php echo ((isset($profileResult["hospital_hours"])) ? html_encode($profileResult["hospital_hours"]) : html_encode($PROCESSED["hospital_hours"])); ?>" maxlength="6" style="width: 50px" /></td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="on_call_hours" class="form-required">Average On-Call Hours / Week</label></td>
							<td><input type="text" id="on_call_hours" name="on_call_hours" value="<?php echo ((isset($profileResult["on_call_hours"])) ? html_encode($profileResult["on_call_hours"]) : html_encode($PROCESSED["on_call_hours"])); ?>" maxlength="6" style="width: 50px" /></td>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top" colspan="2"><label for="consistent_text" class="form-nrequired">
								Are these percentages consistent with the current workload standard of your 
								Department / School / Unit and your assigned role for the calendar year? If no, please comment.
							</label></td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="consistent" class="form-required">Consistent?</label></td>				
							<td><select name="consistent" id="consistent" style="vertical-align: middle">
							<option value=""></option>
							<?php
								echo "<option value=\"Yes\"".(($profileResult["consistent"] == "Yes" || $PROCESSED["consistent"] == "Yes") ? " selected=\"selected\"" : "").">Yes</option>\n";
								echo "<option value=\"No\"".(($profileResult["consistent"] == "No" || $PROCESSED["consistent"] == "No") ? " selected=\"selected\"" : "").">No</option>\n";
								echo "</select>";
							?>
							</td>				
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="consistent_comments" class="form-nrequired">Comments (required if previous answer is "No")</label></td>
							<td><textarea id="consistent_comments" name="consistent_comments" style="width: 95%" rows="4"><?php echo ((isset($profileResult["consistent_comments"])) ? html_encode($profileResult["consistent_comments"]) : html_encode($PROCESSED["consistent_comments"])); ?></textarea></td>				
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top" colspan="2"><label for="career_goals_text" class="form-nrequired">
								Are these percentages in keeping with your overall career goals? If no, please comment.
							</label></td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="career_goals" class="form-required">In Keeping?</label></td>				
							<td><select name="career_goals" id="career_goals" style="vertical-align: middle">
							<option value=""></option>
							<?php
								echo "<option value=\"Yes\"".(($profileResult["career_goals"] == "Yes" || $PROCESSED["career_goals"] == "Yes") ? " selected=\"selected\"" : "").">Yes</option>\n";
								echo "<option value=\"No\"".(($profileResult["career_goals"] == "No" || $PROCESSED["career_goals"] == "No") ? " selected=\"selected\"" : "").">No</option>\n";
								echo "</select>";
							?>
							</td>				
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="career_comments" class="form-nrequired">Comments (required if previous answer is "No")</label></td>
							<td><textarea id="career_comments" name="career_comments" style="width: 95%" rows="4"><?php echo ((isset($profileResult["career_comments"])) ? html_encode($profileResult["career_comments"]) : html_encode($PROCESSED["career_comments"])); ?></textarea></td>				
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top" colspan="2"><label for="roles_text" class="form-nrequired">
								What role(s) do you see yourself in? Are your current professional activities compatible with that (those) role(s)?  If no, please comment.
							</label></td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="roles" class="form-required">Role(s)?</label></td>				
							<td><select multiple name="roles[]" size="3" id="roles[]" style="vertical-align: middle">
							<?php
							$profileRoleArray = getProfileRoles();
							if(isset($PROCESSED["roles"])) {
								foreach($profileRoleArray as $profileRoleListValue) {
									echo "<option value=\"".$profileRoleListValue["profile_role"]."\"".(in_array($profileRoleListValue["profile_role"], $profileResult["roles"]) || in_array($profileRoleListValue["profile_role"], $PROCESSED["roles"]) ? " selected=\"selected\"" : "").">".html_encode($profileRoleListValue["profile_role"])."</option>\n";
								}
							} else {
								foreach($profileRoleArray as $profileRoleListValue) {
									echo "<option value=\"".$profileRoleListValue["profile_role"]."\"".(in_array($profileRoleListValue["profile_role"], $profileResult["roles"]) ? " selected=\"selected\"" : "").">".html_encode($profileRoleListValue["profile_role"])."</option>\n";
								}
							}
								echo "</select>";					
							?>
							</td>				
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="roles_compatible" class="form-required">Compatible?</label></td>				
							<td><select name="roles_compatible" id="roles_compatible" style="vertical-align: middle">
							<option value=""></option>
							<?php
								echo "<option value=\"Yes\"".(($profileResult["roles_compatible"] == "Yes" || $PROCESSED["roles_compatible"] == "Yes") ? " selected=\"selected\"" : "").">Yes</option>\n";
								echo "<option value=\"No\"".(($profileResult["roles_compatible"] == "No" || $PROCESSED["roles_compatible"] == "No") ? " selected=\"selected\"" : "").">No</option>\n";
								echo "</select>";
							?>
							</td>				
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="roles_comments" class="form-nrequired">Comments (required if previous answer is "No")</label></td>
							<td><textarea id="roles_comments" name="roles_comments" style="width: 95%" rows="4"><?php echo ((isset($profileResult["roles_comments"])) ? html_encode($profileResult["roles_comments"]) : html_encode($PROCESSED["roles_comments"])); ?></textarea></td>				
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="comments" class="form-nrequired">Additional Comments</label></td>
							<td><textarea id="comments" name="comments" style="width: 95%" rows="4"><?php echo ((isset($profileResult["comments"])) ? html_encode($profileResult["comments"]) : html_encode($PROCESSED["comments"])); ?></textarea></td>
						</tr>
						<tr>
							<td colspan="3"><h2>Role Definitions and Expectations</h2></td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="education_comments" class="form-nrequired">Education</label></td>
							<td><textarea id="education_comments" name="education_comments" style="width: 95%" rows="4"><?php echo ((isset($profileResult["education_comments"])) ? html_encode($profileResult["education_comments"]) : html_encode($PROCESSED["education_comments"])); ?></textarea></td>				
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="research_comments" class="form-nrequired">Research / Scholarship</label></td>
							<td><textarea id="research_comments" name="research_comments" style="width: 95%" rows="4"><?php echo ((isset($profileResult["research_comments"])) ? html_encode($profileResult["research_comments"]) : html_encode($PROCESSED["research_comments"])); ?></textarea></td>
						</tr>
						
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="clinical_comments" class="form-nrequired">Clinical Activity</label></td>
							<td><textarea id="clinical_comments" name="clinical_comments" style="width: 95%" rows="4"><?php echo ((isset($profileResult["clinical_comments"])) ? html_encode($profileResult["clinical_comments"]) : html_encode($PROCESSED["clinical_comments"])); ?></textarea></td>
						</tr>
						
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="service_comments" class="form-nrequired">Service / Administration</label></td>
							<td><textarea id="service_comments" name="service_comments" style="width: 95%" rows="4"><?php echo ((isset($profileResult["service_comments"])) ? html_encode($profileResult["service_comments"]) : html_encode($PROCESSED["service_comments"])); ?></textarea></td>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="department" class="form-required">Departmental Association</label></td>
							<td><input type="text" id="department" name="department" 
							value="<?php echo (html_encode($profileResult["department"]) != "" ? $profileResult["department"] : $PROCESSED["department"]); ?>" style="width: 500px" /></td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="cross_department" class="form-nrequired">Cross Department(s)</label></td>
							<td><input type="text" id="cross_department" name="cross_department" 
							value="<?php echo (html_encode($profileResult["cross_department"]) != "" ? $profileResult["cross_department"] : $PROCESSED["cross_department"]); ?>" style="width: 500px" />
							<div class="content-small" style="width: 95%"><strong>Note:</strong> The department fields have been populated for you, you can change these if you so desire. This is what will appear on the cover of your annual report.</div></td>
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
								displayARYearReported($profileResult["year_reported"], $AR_CUR_YEAR, $AR_PAST_YEARS, $AR_FUTURE_YEARS, false);
							}
							?>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td></td>
							<td><label for="report_completed" class="form-required">Report Completed For Current Activity Profile Year?</label></td>
							<td>
							<?php
								if(!isset($PROCESSED["report_completed"]) || $PROCESSED["report_completed"] == '')
								{
									$report_completed = $profileResult['report_completed'];
								}
								else 
								{
									$report_completed = $PROCESSED['report_completed'];
								}
								
								if($report_completed == "yes")
								{
									echo "<input type=\"radio\" id=\"report_completed\" name=\"report_completed\" value=\"yes\"/ CHECKED> Yes (you can still add information later if you say yes)<br>
									<input type=\"radio\" id=\"report_completed\" name=\"report_completed\" value=\"no\"/> No<br>";
								}
								elseif($report_completed == "no")
								{
									echo "<input type=\"radio\" id=\"report_completed\" name=\"report_completed\" value=\"yes\"/> Yes (you can still add information later if you say yes)<br>
									<input type=\"radio\" id=\"report_completed\" name=\"report_completed\" value=\"no\"/ CHECKED> No<br>";
								}
								else
								{
									echo "<input type=\"radio\" id=\"report_completed\" name=\"report_completed\" value=\"yes\"/> Yes (you can still add information later if you say yes)<br>
									<input type=\"radio\" id=\"report_completed\" name=\"report_completed\" value=\"no\"/> No<br>";
								}
							?>
							</td>
						</tr>
						<tr>
							<td colspan="3" style="padding-top: 25px">
								<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
								<tr>
									<td style="width: 25%; text-align: left">
										<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/annualreport/activityprofile'" />	
									</td>
									<td style="width: 75%; text-align: right; vertical-align: middle">
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
				$ERRORSTR[] = "In order to edit a Activity Profile record you must provide a valid Activity Profile identifier. The provided ID does not exist in this system.";
	
				echo display_error();
	
				application_log("notice", "Failed to provide a valid Activity Profile identifer when attempting to edit a Activity Profile record.");
			}
		} else {
			$ERROR++;
			$ERRORSTR[] = "In order to edit a Activity Profile record you must provide the Activity Profile identifier.";
	
			echo display_error();
	
			application_log("notice", "Failed to provide Activity Profile identifer when attempting to edit a Activity Profile record.");
		}
	}
}
?>