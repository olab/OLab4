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
	$WARD_SUPERVISION_ID = $_GET["rid"];
	
	// This grid should be expanded upon redirecting back to the clinical index.
	$_SESSION["clinical_expand_grid"] = "ward_grid";
	
	if($WARD_SUPERVISION_ID) {
		$query	= "SELECT * FROM `ar_ward_supervision` WHERE `ward_supervision_id`=".$db->qstr($WARD_SUPERVISION_ID)." AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId());
		$result	= $db->GetRow($query);
		if($result) {
			$BREADCRUMB[]	= array("url" => ENTRADA_URL."/annualreport/clinical?section=edit_ward", "title" => "Edit Ward Supervision");
			
			echo "<h1>Edit Ward Supervision</h1>\n";

			// Error Checking
			switch($STEP) {
				case 2 :					
					/**
					 * Required field "service" / Service			 
					 */
					if((isset($_POST["service"])) && ($service = clean_input($_POST["service"], array("notags", "trim")))) {
						$PROCESSED["service"] = $service;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Service</strong> field is required.";
					}
					/**
					 * Required field "average_patients" / Average Patients
					 */
					if((isset($_POST["average_patients"])) && ($average_patients = clean_input($_POST["average_patients"], array("int")))) {
						$PROCESSED["average_patients"] = $average_patients;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Average Patients</strong> field is required.";
					}
					/**
					 * Required field "months" / Months
					 */
					if((isset($_POST["months"])) && ($months = clean_input($_POST["months"], array("int")))) {
						$PROCESSED["months"] = $months;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Months</strong> field is required.";
					}
					/**
					 * Required field "average_clerks" / Average Clerks
					 */
					if((isset($_POST["average_clerks"])) && ($average_clerks = clean_input($_POST["average_clerks"], array("int")))) {
						$PROCESSED["average_clerks"] = $average_clerks;
					} else {
    					if(trim($_POST["average_clerks"] === "0"))
        				{
        					$PROCESSED["average_clerks"] = trim($_POST["average_clerks"]);
        				}
        				else if(trim($_POST["average_clerks"]) == '')
        				{
        					$PROCESSED["average_clerks"] = "0";
        				}
        				else {
        					$ERROR++;
        					$ERRORSTR[] = "The <strong>Average Clerks</strong> field is required.";
        				}
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
						
						if($db->AutoExecute("ar_ward_supervision", $PROCESSED, "UPDATE", "`ward_supervision_id`=".$db->qstr($WARD_SUPERVISION_ID))) {
								switch($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
									case "new" :
										$url = ENTRADA_URL."/annualreport/clinical?section=add_ward";
										$msg	= "You will now be redirected to add more Ward Supervision; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
									break;
									case "index" :
									default :
										$url = ENTRADA_URL."/annualreport/clinical";
										$msg	= "You will now be redirected to the clinical page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
									break;
								}
		
								$SUCCESS++;
								$SUCCESSSTR[]  = "You have successfully edited <strong>".html_encode($PROCESSED["service"])."</strong> in the system.<br /><br />".$msg;
								$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000);";
		
								application_log("success", "Edited Ward Supervision [".$WARD_SUPERVISION_ID."] in the system.");					
		
						} else {
							$ERROR++;
							$ERRORSTR[] = "There was a problem editing this Ward Supervision record in the system. The MEdIT Unit was informed of this error; please try again later.";
		
							application_log("error", "There was an error editing the Ward Supervision. Database said: ".$db->ErrorMsg());
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
						$wardQuery = "SELECT * FROM `ar_ward_supervision` WHERE `ward_supervision_id` ='$WARD_SUPERVISION_ID'";						
						$wardResult = $db->GetRow($wardQuery);
					}
					
					if($ERROR) {
						echo display_error();
					}
					?>
					<form action="<?php echo ENTRADA_URL; ?>/annualreport/clinical?section=edit_ward&amp;step=2&amp;rid=<?php echo $WARD_SUPERVISION_ID;?>" method="post">
					<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Adding Ward Supervision">
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
						<td><label for="service" class="form-required">Service</label></td>
						<td><input type="text" id="service" name="service" value="<?php echo ((isset($wardResult["service"])) ? html_encode($wardResult["service"]) : html_encode($PROCESSED["service"])); ?>" maxlength="150" style="width: 95%" /></td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="average_patients" class="form-required">Average Patients</label></td>				
						<td><input type="text" id="average_patients" name="average_patients" value="<?php echo ((isset($wardResult["average_patients"])) ? html_encode($wardResult["average_patients"]) : html_encode($PROCESSED["average_patients"])); ?>" maxlength="5" style="width: 40px" /></td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="months" class="form-required">Months</label></td>				
						<td><input type="text" id="months" name="months" value="<?php echo ((isset($wardResult["months"])) ? html_encode($wardResult["months"]) : html_encode($PROCESSED["months"])); ?>" maxlength="2" style="width: 40px" /></td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="average_clerks" class="form-required">Average Clerks/Residents</label></td>				
						<td><input type="text" id="average_clerks" name="average_clerks" value="<?php echo ((isset($wardResult["average_clerks"])) ? html_encode($wardResult["average_clerks"]) : html_encode($PROCESSED["average_clerks"])); ?>" maxlength="5" style="width: 40px" /></td>
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
							displayARYearReported($wardResult["year_reported"], $AR_CUR_YEAR, $AR_PAST_YEARS, $AR_FUTURE_YEARS, false);
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
			$ERRORSTR[] = "In order to edit a Ward Supervision record you must provide a valid Ward Supervision identifier. The provided ID does not exist in this system.";

			echo display_error();

			application_log("notice", "Failed to provide a valid Ward Supervision identifer when attempting to edit a Ward Supervision record.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "In order to edit a Ward Supervision record you must provide the Ward Supervision identifier.";

		echo display_error();

		application_log("notice", "Failed to provide Ward Supervision identifer when attempting to edit a Ward Supervision record.");
	}
}
?>