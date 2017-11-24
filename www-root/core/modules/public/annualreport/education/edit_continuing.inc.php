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
	$CONTINUING_EDUCATION_ID = $_GET["rid"];
	// This grid should be expanded upon redirecting back to the education index.
	$_SESSION["education_expand_grid"] = "continuing_education_grid";
	if($CONTINUING_EDUCATION_ID) {
		$query	= "SELECT * FROM `ar_continuing_education` WHERE `continuing_education_id`=".$db->qstr($CONTINUING_EDUCATION_ID)." AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId());
		$result	= $db->GetRow($query);
		if($result) {
			$BREADCRUMB[]	= array("url" => ENTRADA_URL."/annualreport/education?section=edit_continuing", "title" => "Edit Continuing Education");
			echo "<h1>Edit Continuing Education</h1>\n";

			// Error Checking
			switch($STEP) {
				case 2 :					
					/**
					 * Required field "unit" / Unit.
					 */
					if((isset($_POST["unit"])) && ($unit = clean_input($_POST["unit"], array("notags", "trim")))) {						
						$PROCESSED["unit"] = $unit;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Unit</strong> field is required.";
					}
					/**
					 * Required field "location" / Location
					 */
					if((isset($_POST["location"])) && ($unit = clean_input($_POST["location"], array("notags", "trim")))) {						
						$PROCESSED["location"] = $unit;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Location</strong> field is required.";
					}
					/**
					 * Required field "average_hours" / Average Hours / Week
					 */
					if((isset($_POST["average_hours"])) && ($average_hours = clean_input($_POST["average_hours"], array("int")))) {
						$PROCESSED["average_hours"] = $average_hours;
					} else {
						if(trim($_POST["average_hours"] === "0"))
						{
							$PROCESSED["average_hours"] = trim($_POST["average_hours"]);
						}
						else if(trim($_POST["average_hours"]) == '')
						{
							$PROCESSED["average_hours"] = "0";
						}
						else {
							$ERROR++;
							$ERRORSTR[] = "The <strong>Average Hours / Week</strong> field is required.";
						}
					}
					/**
					 * Required field "description" / Description			 
					 */
					if((isset($_POST["description"])) && ($description = clean_input($_POST["description"], array("notags", "trim")))) {
						$PROCESSED["description"] = $description;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Description</strong> field is required.";
					}	
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
					 * Required field "end_month" / End			 
					 */
					if((isset($_POST["end_month"])) && ($end_month= clean_input($_POST["end_month"], array("int")))) {
						$PROCESSED["end_month"] = $end_month;
					} else {
						$ERROR++;
						$ERRORSTR[] = "Both <strong>End</strong> fields are required.";
						$ENDERROR = true;
					}
					/**
					 * Required field "end_year" / End			 
					 */
					if((isset($_POST["end_year"])) && ($end_year= clean_input($_POST["end_year"], array("int")))) {
						$PROCESSED["end_year"] = $end_year;
					} else if(!$ENDERROR){
						$ERROR++;
						$ERRORSTR[] = "Both <strong>End</strong> fields are required.";
						$ENDERROR = true;
					}
					/**
					 * Check to make sure years are in order
					 */
					if(isset($_POST["end_year"]) && isset($_POST["start_year"]) && ($_POST["start_year"] > $_POST["end_year"]))
					{
						$ERROR++;
						$ERRORSTR[] = "<strong>Start</strong> year cannot be greater than <strong>End</strong> year.";				
					}
					/**
					 * Required field "total_hours" / Total Hours
					 */
					if((isset($_POST["total_hours"])) && ($total_hours = clean_input($_POST["total_hours"], array("int")))) {
						$PROCESSED["total_hours"] = $total_hours;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Total Hours</strong> field is required.";
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
						
						if($db->AutoExecute("ar_continuing_education", $PROCESSED, "UPDATE", "`continuing_education_id`=".$db->qstr($CONTINUING_EDUCATION_ID))) {
								switch($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
									case "new" :
										$url 	= ENTRADA_URL."/annualreport/education?section=add_continuing";
										$msg	= "You will now be redirected to add more Continuing Education; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
									break;
									case "index" :
									default :
										$url 	= ENTRADA_URL."/annualreport/education";
										$msg	= "You will now be redirected to the education page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
									break;
								}
		
								$SUCCESS++;
								$SUCCESSSTR[]  = "You have successfully edited <strong>".html_encode($PROCESSED["description"])."</strong> in the system.<br /><br />".$msg;
								$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000);";
		
								application_log("success", "Edited Continuing Education [".$CONTINUING_EDUCATION_ID."] in the system.");					
		
						} else {
							$ERROR++;
							$ERRORSTR[] = "There was a problem editing this Continuing Education record in the system. The MEdIT Unit was informed of this error; please try again later.";
		
							application_log("error", "There was an error editing the Continuing Education. Database said: ".$db->ErrorMsg());
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
						$continuingEducationQuery = "SELECT * FROM `ar_continuing_education` WHERE `continuing_education_id` = ".$db->qstr($CONTINUING_EDUCATION_ID);
						$continuingEducationResult = $db->GetRow($continuingEducationQuery);
					}
					
					if($ERROR) {
						echo display_error();
					}
					?>
					<form action="<?php echo ENTRADA_URL; ?>/annualreport/education?section=edit_continuing&amp;step=2&amp;rid=<?php echo $CONTINUING_EDUCATION_ID;?>" method="post">
					<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Editing Continuing Education">
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
						<td><label for="unit" class="form-required">Unit</label></td>
						<td><input type="text" id="unit" name="unit" value="<?php echo ((isset($continuingEducationResult["unit"])) ? html_encode($continuingEducationResult["unit"]) : html_encode($PROCESSED["unit"])); ?>" maxlength="255" style="width: 95%" /></td>
					</tr>					
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="location" class="form-required">Location</label></td>				
						<td><input type="text" id="location" name="location" value="<?php echo ((isset($continuingEducationResult["location"])) ? html_encode($continuingEducationResult["location"]) : html_encode($PROCESSED["location"])); ?>" maxlength="255" style="width: 95%" /></td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="average_hours" class="form-required">Average Hours / Week</label></td>				
						<td><input type="text" id="average_hours" name="average_hours" value="<?php echo ((isset($continuingEducationResult["average_hours"])) ? html_encode($continuingEducationResult["average_hours"]) : html_encode($PROCESSED["average_hours"])); ?>" maxlength="255" style="width: 40px" /></td>
					</tr>								
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="description" class="form-required">Description</label></td>				
						<td><textarea id="description" name="description" style="width: 95%" rows="4"><?php echo ((isset($continuingEducationResult["description"])) ? html_encode($continuingEducationResult["description"]) : html_encode($PROCESSED["description"])); ?></textarea></td>
					</tr>
					<tr>
						<td></td>
						<td><label for="start_month" class="form-required">Start (MMYYYY)</label></td>
						<td><select name="start_month" id="start_month" style="vertical-align: middle">
						<option value = ""></option>
						<?php					
							for($i=1; $i<13; $i++)
							{
								echo "<option value=\"".$i."\"".((($continuingEducationResult["start_month"] == $i) || ($PROCESSED["start_month"] == $i)) ? " selected=\"selected\"" : "").">".$i."</option>\n";
							}
							echo "</select> /&nbsp
							<select name=\"start_year\" id=\"start_year\" style=\"vertical-align: middle\">
							<option value = \"\"></option>";
							
							for($i=$AR_PAST_YEARS; $i<$AR_FUTURE_YEARS; $i++)
							{
								echo "<option value=\"".$i."\"".((($continuingEducationResult["start_year"] == $i) || ($PROCESSED["start_year"] == $i)) ? " selected=\"selected\"" : "").">".$i."</option>\n";								
							}
							echo "</select>";
						?>
						</td>
					</tr>
					<tr>
						<td></td>
						<td><label for="end_month" class="form-required">End (MMYYYY)</label></td>
						<td><select name="end_month" id="end_month" style="vertical-align: middle">
						<option value = ""></option>
						<?php
							for($i=1; $i<13; $i++)
							{
								echo "<option value=\"".$i."\"".((($continuingEducationResult["end_month"] == $i) || ($PROCESSED["end_month"] == $i)) ? " selected=\"selected\"" : "").">".$i."</option>\n";
							}
							echo "</select> /&nbsp
							<select name=\"end_year\" id=\"end_year\" style=\"vertical-align: middle\">
							<option value = \"\"></option>";
							
							for($i=$AR_PAST_YEARS; $i<$AR_FUTURE_YEARS; $i++)
							{
								echo "<option value=\"".$i."\"".((($continuingEducationResult["end_year"] == $i) || ($PROCESSED["end_year"] == $i)) ? " selected=\"selected\"" : "").">".$i."</option>\n";
							}
							echo "</select>";
						?>
						</td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="total_hours" class="form-required">Total Hours</label></td>				
						<td><input type="text" id="total_hours" name="total_hours" value="<?php echo ((isset($continuingEducationResult["total_hours"])) ? html_encode($continuingEducationResult["total_hours"]) : html_encode($PROCESSED["total_hours"])); ?>" maxlength="255" style="width: 40px" /></td>
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
							displayARYearReported($continuingEducationResult["year_reported"], $AR_CUR_YEAR, $AR_PAST_YEARS, $AR_FUTURE_YEARS, false);
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
			$ERRORSTR[] = "In order to edit a continuing education record you must provide a valid continuing education record identifier. The provided ID does not exist in this system.";

			echo display_error();

			application_log("notice", "Failed to provide a valid continuing education record identifer when attempting to edit a continuing education record.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "In order to edit a continuing education record you must provide the continuing education record identifier.";

		echo display_error();

		application_log("notice", "Failed to provide continuing education record identifer when attempting to edit a continuing education record.");
	}
}
?>