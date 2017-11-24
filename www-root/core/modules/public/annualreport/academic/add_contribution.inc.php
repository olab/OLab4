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
	// Meta information for this page.
	$PAGE_META["title"]			= "Add Service Contributions on Behalf of Queen's University";
	$PAGE_META["description"]	= "Service Contributions on Behalf of Queen's University portion of your annual report should be entered / located here.";
	$PAGE_META["keywords"]		= "";
	$ONLOAD[]					= "setMaxLength();";
	
	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/annualreport/academic?section=add_contribution", "title" => "Add Service Contributions on Behalf of Queen's University");
	
	// This grid should be expanded upon redirecting back to the academic index.
	$_SESSION["academic_expand_grid"] = "internal_grid";
	
	echo "<h1>Add Service Contributions on Behalf of Queen's University</h1>";
	// Error Checking
	switch($STEP) {
		case 2 :
			$ENDERROR 	= false;
			$STARTERROR 	= false;
			/**
			 * Required field "activity_type" / Activity Type.
			 */
			$activity_type_description = clean_input($_POST["activity_type_description"], array("notags", "trim"))	;
			$PROCESSED["activity_type_description"] = $activity_type_description;
			if((isset($_POST["activity_type"])) && ($activity_type = clean_input($_POST["activity_type"], array("notags", "trim")))) {
						
				$PROCESSED["activity_type"] = $activity_type;
				
				if(strpos($PROCESSED["activity_type"], "(specify)") === FALSE && ($_POST["activity_type_description"] != "" || $PROCESSED["activity_type_description"] != "" )) {
					$ERROR++;
					$ERRORSTR[] = "If you wish to enter data in the <strong>Activity Type Description</strong> field then you must select an activity type that has the word \"(specify)\" as a <strong>Activity Type</strong>
					  Otherwise clear the <strong>Activity Type Description</strong> field and resubmit.";
				} else if(strpos($PROCESSED["activity_type"], "(specify)") !== FALSE && ($_POST["activity_type_description"] == "" && $PROCESSED["activity_type_description"] == "" )) {
					$ERROR++;
					$activity_type_specific = str_replace(" (specify)", "", $PROCESSED["activity_type"]);
					$ERRORSTR[] = "Please specify the \"".$activity_type_specific."\" <strong>Activity Type</strong> in the <strong>Activity Type Description</strong> field.";
				}
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Activity Type</strong> field is required.";
			}
			/**
			 * Required field "role" / Role.
			 */
			$role_description = clean_input($_POST["role_description"], array("notags", "trim"))	;
			$PROCESSED["role_description"] = $role_description;
			if((isset($_POST["role"])) && ($role = clean_input($_POST["role"], array("notags", "trim")))) {
				
				$PROCESSED["role"] = $role;
				
				if(strpos($PROCESSED["role"], "(specify)") === FALSE && ($_POST["role_description"] != "" || $PROCESSED["role_description"] != "")) {
					$ERROR++;
					$ERRORSTR[] = "If you wish to enter data in the <strong>Role Description</strong> field then you must select \"Other (specify)\" as a <strong>Role</strong>
					  Otherwise clear the <strong>Role Description</strong> field and resubmit.";
				} else if(strpos($PROCESSED["role"], "(specify)") !== FALSE && ($_POST["role_description"] == "" && $PROCESSED["role_description"] == "" )) {
					$ERROR++;
					$ERRORSTR[] = "Please specify the \"Other\" <strong>Role</strong> in the <strong>Role Description</strong> field.";
				}
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Role</strong> field is required.";
			}
			/**
			 * Required field "description" / Description			 
			 */
			$PROCESSED["description"] = clean_input($_POST["description"], array("notags", "trim"));
			if((isset($_POST["description"])) && ($description = clean_input($_POST["description"], array("notags", "trim"))) && strlen(trim($_POST["description"])) < 300) {
				$PROCESSED["description"] = $description;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Description</strong> field is required and must be less than 300 characters in length.";
			}
			
			$PROCESSED["commitment_type"] = clean_input($_POST["commitment_type"], array("notags", "trim"));
			
			/**
			 * Required field "time_commitment" / Hours Committed unless variable
			 */
			if((isset($_POST["time_commitment"])) && ($time_commitment = clean_input($_POST["time_commitment"], array("int")))) {
				$PROCESSED["time_commitment"] = $time_commitment;
			} else if($PROCESSED["commitment_type"] != "variable") {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Hours Committed</strong> field is required.";
			}
			
			if($ENTRADA_USER->getClinical()) {
				/**
				 * Required field "start_month" / Start			 
				 */
				if((isset($_POST["start_month"])) && ($start_month= clean_input($_POST["start_month"], array("int")))) {				
					$PROCESSED["start_month"] = $start_month;
				} else {
					$ERROR++;
					$ERRORSTR[] = "Both <strong>Start</strong> fields are required.";
					$STARTERROR = true;
				}
				/**
				 * Required field "start_year" / Start			 
				 */
				if((isset($_POST["start_year"])) && ($start_year= clean_input($_POST["start_year"], array("int")))) {
					$PROCESSED["start_year"] = $start_year;
				} else if(!$STARTERROR){
					$ERROR++;
					$ERRORSTR[] = "Both <strong>Start</strong> fields are required.";
					$STARTERROR = true;
				}
				/**
				 * Non-Required field "end_month" / End			 
				 */
				if(((isset($_POST["end_month"])) && ($end_month= clean_input($_POST["end_month"], array("int")))) 
				&& (isset($_POST["end_year"]) && ($end_year= clean_input($_POST["end_year"], array("int"))))) {
					$PROCESSED["end_month"] = $end_month;
					$PROCESSED["end_year"] = $end_year;
				} else if(((isset($_POST["end_month"])) && ($end_month= clean_input($_POST["end_month"], array("int")))) && (!isset($_POST["end_year"]) || $_POST["end_year"] == '')) {
					$PROCESSED["end_month"] = $end_month;
					
					$ERROR++;
					$ERRORSTR[] = "Both <strong>End</strong> fields are required to be entered or left blank for ongoing commitments.";
					$ENDERROR = true;
				}
				else if(((isset($_POST["end_year"])) && ($end_year= clean_input($_POST["end_year"], array("int")))) && (!isset($_POST["end_month"]) || $_POST["end_month"] == '')) {
					$PROCESSED["end_year"] = $end_year;
					
					$ERROR++;
					$ERRORSTR[] = "Both <strong>End</strong> fields are required to be entered or left blank for ongoing commitments.";
					$ENDERROR = true;
				}
				else 
				{
					$PROCESSED["end_month"] = '';
					$PROCESSED["end_year"] = '';
				}
				/**
				 * Check to make sure years are in order
				 */
				if((isset($_POST["end_year"]) && $_POST["end_year"] != '') && isset($_POST["start_year"]) && ($_POST["start_year"] > $_POST["end_year"]))
				{
					$ERROR++;
					$ERRORSTR[] = "<strong>Start</strong> year cannot be greater than <strong>End</strong> year.";				
				}
				$PROCESSED["meetings_attended"] = '';
			} else {
				/**
				 * Required field "meetings_attended" / % Meetings Attended			 
				 */
				if((isset($_POST["meetings_attended"])) && ($meetings_attended= clean_input($_POST["meetings_attended"], array("int")))) {
					if($meetings_attended < 0 || $meetings_attended > 100) {
						$ERROR++;
						$ERRORSTR[] = "The <strong>% Meetings Attended</strong> field must be a percentage (0 - 100).";
					} else {
						$PROCESSED["meetings_attended"] = $meetings_attended;
						$PROCESSED["start_year"] 	= '';
						$PROCESSED["start_month"] 	= '';					
						$PROCESSED["end_month"] 	= '';
						$PROCESSED["end_year"] 		= '';
					}
				} else {
					$ERROR++;
					$ERRORSTR[] = "The <strong>% Meetings Attended</strong> field is required.";
				}
			}
			/**
			 * Required field "type" / Contribution Type			 
			 */
			if((isset($_POST["type"])) && ($type = clean_input($_POST["type"], array("notags", "trim")))) {
				$PROCESSED["type"] = $type;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Contribution Type</strong> field is required.";
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
				
				if($PROCESSED["commitment_type"] == "variable") {
					$PROCESSED["time_commitment"] = 0;
				}				
				
				if($db->AutoExecute("ar_internal_contributions", $PROCESSED, "INSERT")) {
					$EVENT_ID = $db->Insert_Id();
						switch($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
							case "new" :
								$url 	= ENTRADA_URL."/annualreport/academic?section=add_contribution";
								$msg	= "You will now be redirected to add another new record; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
							break;
							case "index" :
							default :
								$url 	= ENTRADA_URL."/annualreport/academic";
								$msg	= "You will now be redirected to the academic page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
							break;
						}

						$SUCCESS++;
						$SUCCESSSTR[]  = "You have successfully added information pertaining to internal contribution <strong>".html_encode($PROCESSED["activity_type"])."</strong> to the system.<br /><br />".$msg;
						$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000);";

						application_log("success", "Service Contributions on Behalf of Queen's University [".$EVENT_ID."] added to the system.");					

				} else {
					$ERROR++;
					$ERRORSTR[] = "There was a problem inserting this record into the system. The MEdIT Unit was informed of this error; please try again later.";

					application_log("error", "There was an error inserting an internal contribution record. Database said: ".$db->ErrorMsg());
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
			if($ERROR) {
				echo display_error();
			}
			?>
			<form action="<?php echo ENTRADA_URL; ?>/annualreport/academic?section=add_contribution&amp;step=2" method="post" id="addContributionForm">
			<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Adding Service Contributions on Behalf of Queen's University">
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
				<td style="vertical-align: top"><label for="activity_type" class="form-required">Activity Type</label></td>				
				<td><select name="activity_type" id="activity_type" style="vertical-align: middle">
				<option value=""></option>
				<?php
					$contributionTypeArray = getContributionTypes();
					foreach($contributionTypeArray as $contributionTypeListValue) {
						echo "<option value=\"".$contributionTypeListValue["contribution_type"]."\"".(($PROCESSED["activity_type"] == $contributionTypeListValue["contribution_type"]) ? " selected=\"selected\"" : "").">".html_encode($contributionTypeListValue["contribution_type"])."</option>\n";
					}
					echo "</select>";
				?>
				</td>				
			</tr>
			<tr>
				<td></td>
				<td style="vertical-align: top"><label for="activity_type_description" class="form-nrequired">Activity Type Description</label></td>				
				<td><input type="text" id="activity_type_description" name="activity_type_description" value="<?php echo html_encode($PROCESSED["activity_type_description"]); ?>" style="width: 95%" /></td>			
			</tr>
			<tr>
				<td></td>
				<td style="vertical-align: top"><label for="role" class="form-required">Role</label></td>				
				<td><select name="role" id="role" style="vertical-align: middle">
				<option value=""></option>
				<?php
					$contributionRoleArray = getContributionRoles();
					foreach($contributionRoleArray as $contributionRoleListValue) {
						echo "<option value=\"".$contributionRoleListValue["contribution_role"]."\"".(($PROCESSED["role"] == $contributionRoleListValue["contribution_role"]) ? " selected=\"selected\"" : "").">".html_encode($contributionRoleListValue["contribution_role"])."</option>\n";
					}
					echo "</select>";
				?>
				</td>
			</tr>
			<tr>
				<td></td>
				<td style="vertical-align: top"><label for="role_description" class="form-nrequired">Role Description</label></td>				
				<td><input type="text" id="role_description" name="role_description" value="<?php echo html_encode($PROCESSED["role_description"]); ?>" style="width: 95%" /></td>			
			</tr>
			<tr>
				<td></td>
				<td style="vertical-align: top"><label for="description" class="form-required">Description</label></td>				
				<td><textarea id="description" name="description" style="width: 95%" rows="4" maxlength="300"><?php echo html_encode($PROCESSED["description"]); ?></textarea></td>
			</tr>
			<tr>
				<td></td>
				<td style="vertical-align: top"><label for="time_commitment" class="form-required">Hours Committed</label></td>
				<td><input type="text" id="time_commitment" name="time_commitment" value="<?php echo (($PROCESSED["commitment_type"] == "variable") ? "N/A" : html_encode($PROCESSED["time_commitment"])); ?>" <?php echo (($PROCESSED["commitment_type"] == "variable") ? "disabled = true" : ""); ?> maxlength="5" style="width: 40px" />
				<input type="radio" id="commitment_type_week" name="commitment_type" value="week"<?php echo ((!isset($PROCESSED["commitment_type"]) || $PROCESSED["commitment_type"] == "week") ? " checked=\"checked\"" : ""); ?> onclick="setUnsetResults(this)" /> Per Week 
				<input type="radio" id="commitment_type_month" name="commitment_type" value="month"<?php echo (($PROCESSED["commitment_type"] == "month") ? " checked=\"checked\"" : ""); ?> onclick="setUnsetResults(this)" /> Per Month 
				<input type="radio" id="commitment_type_year" name="commitment_type" value="year"<?php echo (($PROCESSED["commitment_type"] == "year") ? " checked=\"checked\"" : ""); ?> onclick="setUnsetResults(this)" /> Per Year
				<input type="radio" id="commitment_type_variable" name="commitment_type" value="variable"<?php echo (($PROCESSED["commitment_type"] == "variable") ? " checked=\"checked\"" : ""); ?> onclick="setUnsetResults(this)" /> Variable</td>
			</tr>
			<?php
			if($ENTRADA_USER->getClinical()) {
			?>
			<tr>
				<td></td>
				<td><label for="start_month" class="form-required">Start (MMYYYY)</label></td>
				<td><select name="start_month" id="start_month" style="vertical-align: middle">
				<option value = ""></option>
				<?php					
					for($i=1; $i<13; $i++)
					{
						echo "<option value=\"".$i."\"".(($PROCESSED["start_month"] == $i) ? " selected=\"selected\"" : "").">".$i."</option>\n";
					}
					echo "</select> /&nbsp
					<select name=\"start_year\" id=\"start_year\" style=\"vertical-align: middle\">
					<option value = \"\"></option>";
					
					for($i=$AR_PAST_YEARS; $i<$AR_FUTURE_YEARS; $i++)
					{
						echo "<option value=\"".$i."\"".(($PROCESSED["start_year"] == $i) ? " selected=\"selected\"" : "").">".$i."</option>\n";
					}
					echo "</select>";
				?>
				</td>
			</tr>
			<tr>
				<td></td>
				<td><label for="end_month" class="form-nrequired">End (MMYYYY)</label></td>
				<td><select name="end_month" id="end_month" style="vertical-align: middle">
				<option value = ""></option>
				<?php
					for($i=1; $i<13; $i++)
					{
						echo "<option value=\"".$i."\"".(($PROCESSED["end_month"] == $i) ? " selected=\"selected\"" : "").">".$i."</option>\n";
					}
					echo "</select> /&nbsp
					<select name=\"end_year\" id=\"end_year\" style=\"vertical-align: middle\">
					<option value = \"\"></option>";
					
					for($i=$AR_PAST_YEARS; $i<$AR_FUTURE_YEARS; $i++)
					{
						echo "<option value=\"".$i."\"".(($PROCESSED["end_year"] == $i) ? " selected=\"selected\"" : "").">".$i."</option>\n";
					}
					echo "</select>";
				?>
				</td>
			</tr>
			<?php
			} else {
			?>
			<tr>
				<td></td>
				<td style="vertical-align: top"><label for="meetings_attended" class="form-required">% of Meetings Attended</label></td>				
				<td><input type="text" id="meetings_attended" name="meetings_attended" value="<?php echo (!isset($PROCESSED["meetings_attended"]) || html_encode($PROCESSED["meetings_attended"]) == "" ? "100" : html_encode($PROCESSED["meetings_attended"])); ?>" maxlength="3" style="width: 40px"  />
				<div id="meetings_note" class="content-small" style="display: inline;"> Leave as 100 if N/A</div>
				</td>
			</tr>
			<?php
			}
			?>
			<tr>
				<td colspan="3">&nbsp;</td>
			</tr>
			<tr>
				<td></td>
				<td style="vertical-align: top"><label for="type" class="form-required">Contribution Type</label></td>
				<td>
					<input type="radio" id="type" name="type" value="University"<?php echo (($PROCESSED["type"] == "University") ? " checked=\"CHECKED\"" : ""); ?>/> University<br>
					<input type="radio" id="type" name="type" value="Faculty"<?php echo (($PROCESSED["type"] == "Faculty") ? " checked=\"CHECKED\"" : ""); ?>/> Faculty<br>
					<input type="radio" id="type" name="type" value="Department/School"<?php echo (($PROCESSED["type"] == "Department/School") ? " checked=\"CHECKED\"" : ""); ?>/> Department/School<br>
					<input type="radio" id="type" name="type" value="Other"<?php echo (($PROCESSED["type"] == "Other") ? " checked=\"CHECKED\"" : ""); ?>/> Other (Hospital/Health Agency)
					<div id="type_note" class="content-small" style="display: inline;"> Please specify Institution / Agency in Description field.</div>
				</td>
			</tr>
			<tr>
				<td colspan="3">&nbsp;</td>
			</tr>
			<tr>
				<td></td>
				<td><label for="year_reported" class="form-required">Report Year</label></td>
				<td><select name="year_reported" id="year_reported" style="vertical-align: middle">
				<?php
					for($i=$AR_PAST_YEARS; $i<=$AR_FUTURE_YEARS; $i++)
					{
						if(isset($PROCESSED["year_reported"]) && $PROCESSED["year_reported"] != '')
						{
							$defaultYear = $PROCESSED["year_reported"];
						}
						else 
						{
							$defaultYear = $AR_CUR_YEAR;
						}
						echo "<option value=\"".$i."\"".(($defaultYear == $i) ? " selected=\"selected\"" : "").">".$i."</option>\n";
					}
					echo "</select>";
				?>
				</td>
			</tr>
			<tr>
				<td colspan="3" style="padding-top: 25px">
					<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
					<tr>
						<td style="width: 25%; text-align: left">
							<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/annualreport/academic'" />
						</td>
						<td style="width: 75%; text-align: right; vertical-align: middle">
							<span class="content-small">After saving:</span>
							<select id="post_action" name="post_action">							
							<option value="new"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "add") ? " selected=\"selected\"" : ""); ?>>Add More Service</option>
							<option value="index"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "index") ? " selected=\"selected\"" : ""); ?>>Return to Service list</option>
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
}
?>