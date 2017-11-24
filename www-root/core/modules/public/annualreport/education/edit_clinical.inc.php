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
	$CLINICAL_EDUCATION_ID = $_GET["rid"];
	// This grid should be expanded upon redirecting back to the education index.
	$_SESSION["education_expand_grid"] = "clinical_education_grid";
	if($CLINICAL_EDUCATION_ID) {
		$query	= "SELECT * FROM `ar_clinical_education` WHERE `clinical_education_id`=".$db->qstr($CLINICAL_EDUCATION_ID)." AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId());
		$result	= $db->GetRow($query);
		if($result) {
			$BREADCRUMB[]	= array("url" => ENTRADA_URL."/annualreport/education?section=edit_clinical", "title" => "Edit Clinical Education");
			echo "<h1>Edit Clinical Education</h1>\n";

			// Error Checking
			switch($STEP) {
				case 2 :
					/**
					 * Required field "level" / Level of Trainees.
					 */
					$levelDesc = clean_input($_POST["level_description"], array("notags", "trim"))	;
					$PROCESSED["level_description"] = $levelDesc;
					
					if((isset($_POST["level"])) && ($location = clean_input($_POST["level"], array("notags", "trim")))) {
						
						$PROCESSED["level"] = $location;
						if(strpos($PROCESSED["level"], "(specify)") === FALSE && ($_POST["level_description"] != "" || $PROCESSED["level_description"] != "" )) {
							$ERROR++;
							$ERRORSTR[] = "If you wish to enter data in the <strong>Level Description</strong> field then you must select a level that has the word \"(specify)\" as a <strong>Level of Trainees</strong>
							  Otherwise clear the <strong>Level Description</strong> field and resubmit.";
						} else if(strpos($PROCESSED["level"], "(specify)") !== FALSE && ($_POST["level_description"] == "" && $PROCESSED["level_description"] == "" )) {
							$level_specific = str_replace(" (specify)", "", $PROCESSED["level"]);
							$ERRORSTR[] = "Please specify the <strong>\"".$level_specific."\" Level</strong> in the <strong>Level Description</strong> field.";
						}
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Level of Trainees</strong> field is required.";
					}
					/**
					 * Required field "location" / Location
					 */
					$locationDesc = clean_input($_POST["location_description"], array("notags", "trim"))	;
					$PROCESSED["location_description"] = $locationDesc;
					if((isset($_POST["location"])) && ($location = clean_input($_POST["location"], array("notags", "trim")))) {
						
						$PROCESSED["location"] = $location;
						
						if($PROCESSED["location"] != "Other (specify)" && ($_POST["location_description"] != "" || $PROCESSED["location_description"] != "" )) {
							$ERROR++;
							$ERRORSTR[] = "If you wish to enter data in the <strong>Location Description</strong> field then you must select \"Other (specify)\" as a <strong>Location</strong>
							  Otherwise clear the <strong>Location Description</strong> field and resubmit.";
						} else if($PROCESSED["location"] == "Other (specify)" && ($_POST["location_description"] == "" && $PROCESSED["location_description"] == "" )) {
							$ERROR++;
							$ERRORSTR[] = "Please specify the \"Other\" <strong>Location</strong> in the <strong>Location Description</strong> field.";
						}
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Location</strong> field is required.";
					}
					/**
					 * Required field "average_hours" / Average Hours
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
					 * Non-required field "research_percentage" / Research Percentage.
					 */
					if((isset($_POST["research_percentage"])) && ($research_percentage = clean_input($_POST["research_percentage"], array("notags", "trim")))) {
						if($research_percentage) {
							$PROCESSED["research_percentage"] = 1;
						} else {
							$PROCESSED["research_percentage"] = 0;
						}
					} else {
						$PROCESSED["research_percentage"] = 0;
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
						
						if($db->AutoExecute("ar_clinical_education", $PROCESSED, "UPDATE", "`clinical_education_id`=".$db->qstr($CLINICAL_EDUCATION_ID))) {
								switch($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
									case "new" :
										$url 	= ENTRADA_URL."/annualreport/education?section=add_clinical";
										$msg	= "You will now be redirected to add more Clinical Education; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
									break;
									case "index" :
									default :
										$url 	= ENTRADA_URL."/annualreport/education";
										$msg	= "You will now be redirected to the education page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
									break;
								}
		
								$SUCCESS++;
								$SUCCESSSTR[]  = "You have successfully edited <strong>".html_encode($PROCESSED["level"])."</strong> in the system.<br /><br />".$msg;
								$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000);";
		
								application_log("success", "Edited Clinical Education [".$CLINICAL_EDUCATION_ID."] in the system.");					
		
						} else {
							$ERROR++;
							$ERRORSTR[] = "There was a problem editing this Clinical Education record in the system. The MEdIT Unit was informed of this error; please try again later.";
		
							application_log("error", "There was an error editing the Clinical Education. Database said: ".$db->ErrorMsg());
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
						$clinicalEducationQuery = "SELECT * FROM `ar_clinical_education` WHERE `clinical_education_id` = ".$db->qstr($CLINICAL_EDUCATION_ID);
						$clinicalEducationResult = $db->GetRow($clinicalEducationQuery);
					}
					
					if($ERROR) {
						echo display_error();
					}
					?>
					<form action="<?php echo ENTRADA_URL; ?>/annualreport/education?section=edit_clinical&amp;step=2&amp;rid=<?php echo $CLINICAL_EDUCATION_ID;?>" method="post">
					<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Editing Clinical Education">
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
						<td><label for="level" class="form-required">Level of Trainees</label></td>
						<td><select name="level" id="level" style="vertical-align: middle">
						<option value=""></option>
						<?php
							$traineeLevelArray = getTraineeLevels();
							foreach($traineeLevelArray as $traineeLevelListValue) {
								echo "<option value=\"".$traineeLevelListValue["trainee_level"]."\"".((($clinicalEducationResult["level"] == $traineeLevelListValue["trainee_level"]) || ($PROCESSED["level"] == $traineeLevelListValue["trainee_level"])) ? " selected=\"selected\"" : "").">".$traineeLevelListValue["trainee_level"]."</option>\n";
							}
						?>
						</select>
						<script>
					    	jQuery(function($) {
									jQuery('#level').change(function() {
										if(jQuery(':selected', this).text() == "Clinical Research Fellow") {
											jQuery('#research_percentage_details').show();
										} else {
											jQuery('#research_percentage_details').hide();
											jQuery('input[name=research_percentage]').attr('checked', false);
										}
									}).trigger('change');
					    	});
						</script>
						</td>
					</tr>
					<?php
						if(isset($PROCESSED["research_percentage"]) && $PROCESSED["research_percentage"]) {
							$checked = "checked=\"checked\"";
							$display = "";					
						} else if($clinicalEducationResult["research_percentage"]) {
							$checked = "checked=\"checked\"";
							$display = "";
						} else {
							$checked = "";
							$display = "style=\"display: none;\"";
						}
					?>
					<tr id="research_percentage_details" <?php echo $display; ?>>
						<td></td>
						<td><label for="research_percentage">Research > 75%</label></td>
						<td>
							<input type="checkbox" id="research_percentage" name="research_percentage" style="vertical-align:0px;" <?php echo $checked; ?>>
							<span class="content-small">Check this box when clinical trainee devotes 75% or more of their time to research</span>
						</td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="level_description" class="form-nrequired">Level Description</label></td>				
						<td><input type="text" id="level_description" name="level_description" value="<?php echo ((isset($clinicalEducationResult["level_description"])) ? html_encode($clinicalEducationResult["level_description"]) : html_encode($PROCESSED["level_description"])); ?>" maxlength="255" style="width: 95%" /></td>
					</tr>
					<tr>
						<td colspan="3">&nbsp;</td>
					</tr>					
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="location" class="form-required">Location</label></td>				
						<td><select name="location" id="location" style="vertical-align: middle">
						<option value=""></option>
						<?php
							$locationArray = getEducationLocations();
							foreach($locationArray as $locationListValue) {
								echo "<option value=\"".$locationListValue["education_location"]."\"".((($clinicalEducationResult["location"] == $locationListValue["education_location"]) || ($PROCESSED["location"] == $locationListValue["education_location"])) ? " selected=\"selected\"" : "").">".$locationListValue["education_location"]."</option>\n";
							}
						?>
						</select>
						</td>				
					</tr>									
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="location_description" class="form-nrequired">Location Description</label></td>				
						<td><input type="text" id="location_description" name="location_description" value="<?php echo ((isset($clinicalEducationResult["location_description"])) ? html_encode($clinicalEducationResult["location_description"]) : html_encode($PROCESSED["location_description"])); ?>" maxlength="255" style="width: 95%" /></td>
					</tr>
					<tr>
						<td colspan="3">&nbsp;</td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="average_hours" class="form-required">Average Hours / Week</label></td>
						<td><input type="text" id="average_hours" name="average_hours" value="<?php echo ((isset($clinicalEducationResult["average_hours"])) ? html_encode($clinicalEducationResult["average_hours"]) : html_encode($PROCESSED["average_hours"])); ?>" maxlength="255" style="width: 40px" /></td>
					</tr>								
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="description" class="form-required">Description</label></td>			
						<td><textarea id="description" name="description" style="width: 95%" rows="4"><?php echo ((isset($clinicalEducationResult["description"])) ? html_encode($clinicalEducationResult["description"]) : html_encode($PROCESSED["description"])); ?></textarea></td>
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
							displayARYearReported($clinicalEducationResult["year_reported"], $AR_CUR_YEAR, $AR_PAST_YEARS, $AR_FUTURE_YEARS, false);
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
			$ERRORSTR[] = "In order to edit a clinical education record you must provide a valid clinical education record identifier. The provided ID does not exist in this system.";

			echo display_error();

			application_log("notice", "Failed to provide a valid clinical education record identifer when attempting to edit a clinical education record.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "In order to edit a clinical education record you must provide the clinical education record identifier.";

		echo display_error();

		application_log("notice", "Failed to provide clinical education record identifer when attempting to edit a clinical education record.");
	}
}
?>