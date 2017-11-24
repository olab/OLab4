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
	$CLINICS_ID = $_GET["rid"];
	
	// This grid should be expanded upon redirecting back to the clinical index.
	$_SESSION["clinical_expand_grid"] = "clinic_grid";
	
	if($CLINICS_ID) {
		$query	= "SELECT * FROM `ar_clinics` WHERE `clinics_id`=".$db->qstr($CLINICS_ID)." AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId());
		$result	= $db->GetRow($query);
		if($result) {
			$BREADCRUMB[]	= array("url" => ENTRADA_URL."/annualreport/clinical?section=edit_clinic", "title" => "Edit Clinic");
			
			echo "<h1>Edit Clinic</h1>\n";

			// Error Checking
			switch($STEP) {
				case 2 :
					/**
					 * Required field "clinic" / Clinic			 
					 */
					if((isset($_POST["clinic"])) && ($clinic = clean_input($_POST["clinic"], array("notags", "trim")))) {
						$PROCESSED["clinic"] = $clinic;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Clinic</strong> field is required.";
					}
					/**
					 * Required field "patients" / Patients
					 */
					if((isset($_POST["patients"])) && ($patients = clean_input($_POST["patients"], array("int")))) {
						$PROCESSED["patients"] = $patients;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Patients</strong> field is required.";
					}
					/**
					 * Required field "half_days" / Half Days
					 */
					if((isset($_POST["half_days"])) && ($half_days = clean_input($_POST["half_days"], array("int")))) {
						$PROCESSED["half_days"] = $half_days;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Half Days</strong> field is required.";
					}
					/**
					 * Required field "new_repeat" / New / Repeat
					 */
					if((isset($_POST["new_repeat"])) && ($new_repeat = clean_input($_POST["new_repeat"], array("notags", "trim")))) {
						$PROCESSED["new_repeat"] = $new_repeat;
						if(strpos($PROCESSED["new_repeat"], ":") === FALSE) {
							$ERROR++;
							$ERRORSTR[] = "The <strong>New / Repeat</strong> value must be a ratio (i.e 10:1).";
						}
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>New / Repeat</strong> field is required.";
					}
					/**
					 * Required field "weeks" / Number Of Weeks / Year
					 */
					if((isset($_POST["weeks"])) && ($weeks = clean_input($_POST["weeks"], array("int")))) {
						$PROCESSED["weeks"] = $weeks;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Number Of Weeks / Year</strong> field is required.";
					}
					/**
					 * Required field "average_clerks" / Average Clerks
					 */
					if((isset($_POST["average_clerks"])) && ($average_clerks = clean_input($_POST["average_clerks"], array("int")))) {
						$PROCESSED["average_clerks"] = $average_clerks;
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
						
						if($db->AutoExecute("ar_clinics", $PROCESSED, "UPDATE", "`clinics_id`=".$db->qstr($CLINICS_ID))) {
								switch($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
									case "new" :
										$url = ENTRADA_URL."/annualreport/clinical?section=add_clinic";
										$msg	= "You will now be redirected to add another Clinic; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
									break;
									case "index" :
									default :
										$url = ENTRADA_URL."/annualreport/clinical";
										$msg	= "You will now be redirected to the clinical page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
									break;
								}
		
								$SUCCESS++;
								$SUCCESSSTR[]  = "You have successfully edited <strong>".html_encode($PROCESSED["clinic"])."</strong> in the system.<br /><br />".$msg;
								$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000);";
		
								application_log("success", "Edited Clinic [".$CLINICS_ID."] in the system.");					
		
						} else {
							$ERROR++;
							$ERRORSTR[] = "There was a problem editing this Clinic record in the system. The MEdIT Unit was informed of this error; please try again later.";
		
							application_log("error", "There was an error editing the Clinic. Database said: ".$db->ErrorMsg());
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
						$clinicQuery = "SELECT * FROM `ar_clinics` WHERE `clinics_id` ='$CLINICS_ID'";						
						$clinicResult = $db->GetRow($clinicQuery);
					}
					
					if($ERROR) {
						echo display_error();
					}
					?>
					<form action="<?php echo ENTRADA_URL; ?>/annualreport/clinical?section=edit_clinic&amp;step=2&amp;rid=<?php echo $CLINICS_ID;?>" method="post">
					<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Adding Clinic">
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
						<td><label for="clinic" class="form-required">Clinic</label></td>
						<td><input type="text" id="clinic" name="clinic" value="<?php echo ((isset($clinicResult["clinic"])) ? html_encode($clinicResult["clinic"]) : html_encode($PROCESSED["clinic"])); ?>" maxlength="255" style="width: 95%" /></td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="patients" class="form-required">Patients</label></td>				
						<td><input type="text" id="patients" name="patients" value="<?php echo ((isset($clinicResult["patients"])) ? html_encode($clinicResult["patients"]) : html_encode($PROCESSED["patients"])); ?>" maxlength="5" style="width: 40px" /></td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="half_days" class="form-required">Half Days</label></td>				
						<td><input type="text" id="half_days" name="half_days" value="<?php echo ((isset($clinicResult["half_days"])) ? html_encode($clinicResult["half_days"]) : html_encode($PROCESSED["half_days"])); ?>" maxlength="2" style="width: 40px" /></td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="new_repeat" class="form-required">New / Repeat (Ratio)</label></td>				
						<td><input type="text" id="new_repeat" name="new_repeat" value="<?php echo ((isset($clinicResult["new_repeat"])) ? html_encode($clinicResult["new_repeat"]) : html_encode($PROCESSED["new_repeat"])); ?>" maxlength="5" style="width: 40px" /></td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="weeks" class="form-required">Number Of Weeks / Year</label></td>				
						<td><input type="text" id="weeks" name="weeks" value="<?php echo ((isset($clinicResult["weeks"])) ? html_encode($clinicResult["weeks"]) : html_encode($PROCESSED["weeks"])); ?>" maxlength="2" style="width: 40px" /></td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="average_clerks" class="form-nrequired">Average Clerks/Residents</label></td>				
						<td><input type="text" id="average_clerks" name="average_clerks" value="<?php echo ((isset($clinicResult["average_clerks"])) ? html_encode($clinicResult["average_clerks"]) : html_encode($PROCESSED["average_clerks"])); ?>" maxlength="5" style="width: 40px" /></td>
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
							displayARYearReported($clinicResult["year_reported"], $AR_CUR_YEAR, $AR_PAST_YEARS, $AR_FUTURE_YEARS, false);
						}
						?>
					</tr>
					<tr>
						<td colspan="3" style="padding-top: 25px">
							<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
							<tr>
								<td style="width: 25%; text-align: left">
									<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/annualreport/clinical'" />
								</td>
								<td style="width: 75%; text-align: right; vertical-align: middle">
									<span class="content-small">After saving:</span>
									<select id="post_action" name="post_action">							
									<option value="new"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "add") ? " selected=\"selected\"" : ""); ?>>Add More Clinical</option>
									<option value="index"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "index") ? " selected=\"selected\"" : ""); ?>>Return to Clinical list</option>
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
			$ERRORSTR[] = "In order to edit a Clinic record you must provide a valid Clinic identifier. The provided ID does not exist in this system.";

			echo display_error();

			application_log("notice", "Failed to provide a valid Clinic identifer when attempting to edit a Clinic record.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "In order to edit a Clinic record you must provide the Clinic identifier.";

		echo display_error();

		application_log("notice", "Failed to provide Clinic identifer when attempting to edit a Clinic record.");
	}
}
?>