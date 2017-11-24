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
	$OTHER_ACTIVITY_ID = $_GET["rid"];
	
	// This grid should be expanded upon redirecting back to the clinical index.
	$_SESSION["clinical_expand_grid"] = "other_grid";
	
	if($OTHER_ACTIVITY_ID) {
		$query	= "SELECT * FROM `ar_other_activity` WHERE `other_activity_id`=".$db->qstr($OTHER_ACTIVITY_ID)." AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId());
		$result	= $db->GetRow($query);
		if($result) {
			$BREADCRUMB[]	= array("url" => ENTRADA_URL."/annualreport/clinical?section=edit_other", "title" => "Edit Other Professional Activity");
			
			echo "<h1>Edit Other Professional Activity</h1>\n";

			// Error Checking
			switch($STEP) {
				case 2 :
					/**
					 * Required field "site" / Site
					 */
					$siteDesc = clean_input($_POST["site_description"], array("notags", "trim"))	;
					$PROCESSED["site_description"] = $siteDesc;
					if((isset($_POST["site"])) && ($site = clean_input($_POST["site"], array("notags", "trim")))) {
						
						$PROCESSED["site"] = $site;
						
						if($PROCESSED["site"] != "Other (specify)" && ($_POST["site_description"] != "" || $PROCESSED["site_description"] != "" )) {
							$ERROR++;
							$ERRORSTR[] = "If you wish to enter data in the <strong>Site Description</strong> field then you must select \"Other (specify)\" as a <strong>Site</strong>
							  Otherwise clear the <strong>Site Description</strong> field and resubmit.";
						} else if($PROCESSED["site"] == "Other (specify)" && ($_POST["site_description"] == "" && $PROCESSED["site_description"] == "" )) {
							$ERROR++;
							$ERRORSTR[] = "Please specify the \"Other\" <strong>Site</strong> in the <strong>Site Description</strong> field.";
						}
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Site</strong> field is required.";
					}
					/**
					 * Required field "average_hours" / Average Hours
					 */
					if((isset($_POST["average_hours"])) && ($average_hours = clean_input($_POST["average_hours"], array("int")))) {
						$PROCESSED["average_hours"] = $average_hours;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Average Hours</strong> field is required.";
					}
					/**
					 * Required field "special_features" / Special Features			 
					 */
					if((isset($_POST["special_features"])) && ($special_features = clean_input($_POST["special_features"], array("notags", "trim")))) {
						$PROCESSED["special_features"] = $special_features;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Special Features</strong> field is required.";
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
						
						if($db->AutoExecute("ar_other_activity", $PROCESSED, "UPDATE", "`other_activity_id`=".$db->qstr($OTHER_ACTIVITY_ID))) {
								switch($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
									case "new" :
										$url = ENTRADA_URL."/annualreport/clinical?section=add_other";
										$msg	= "You will now be redirected to add more Other Professional Activity; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
									break;
									case "index" :
									default :
										$url = ENTRADA_URL."/annualreport/clinical";
										$msg	= "You will now be redirected to the clinical page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
									break;
								}
		
								$SUCCESS++;
								$SUCCESSSTR[]  = "You have successfully edited <strong>".html_encode($PROCESSED["site"])."</strong> in the system.<br /><br />".$msg;
								$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000);";
		
								application_log("success", "Edited Other Professional Activity [".$OTHER_ACTIVITY_ID."] in the system.");					
		
						} else {
							$ERROR++;
							$ERRORSTR[] = "There was a problem editing this Other Professional Activity record in the system. The MEdIT Unit was informed of this error; please try again later.";
		
							application_log("error", "There was an error editing the Other Professional Activity. Database said: ".$db->ErrorMsg());
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
						$otherQuery = "SELECT * FROM `ar_other_activity` WHERE `other_activity_id` ='$OTHER_ACTIVITY_ID'";						
						$otherResult = $db->GetRow($otherQuery);
					}
					
					if($ERROR) {
						echo display_error();
					}
					?>
					<form action="<?php echo ENTRADA_URL; ?>/annualreport/clinical?section=edit_other&amp;step=2&amp;rid=<?php echo $OTHER_ACTIVITY_ID;?>" method="post">
					<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Adding Other Professional Activity">
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
						<td><label for="site" class="form-required">Site</label></td>
						<td><select name="site" id="site" style="vertical-align: middle">
						<option value=""></option>
						<?php
							$otherLocationArray = getOtherLocations();
							foreach($otherLocationArray as $otherLocationListValue) {
								echo "<option value=\"".$otherLocationListValue["other_location"]."\"".(($otherResult["site"] == $otherLocationListValue["other_location"] || $PROCESSED["site"] == $otherLocationListValue["other_location"]) ? " selected=\"selected\"" : "").">".html_encode($otherLocationListValue["other_location"])."</option>\n";
							}
						?>
						</select>
						</td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="site_description" class="form-nrequired">Site Description</label></td>				
						<td><input type="text" id="site_description" name="site_description" value="<?php echo ((isset($otherResult["site_description"])) ? html_encode($otherResult["site_description"]) : html_encode($PROCESSED["site_description"])); ?>" style="width: 95%" /></td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="average_hours" class="form-required">Average Hours / Week</label></td>				
						<td><input type="text" id="average_hours" name="average_hours" value="<?php echo ((isset($otherResult["average_hours"])) ? html_encode($otherResult["average_hours"]) : html_encode($PROCESSED["average_hours"])); ?>" maxlength="5" style="width: 40px" /></td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="special_features" class="form-required">Special Features</label></td>				
						<td><textarea id="special_features" name="special_features" style="width: 95%" rows="4"><?php echo ((isset($otherResult["special_features"])) ? html_encode($otherResult["special_features"]) : html_encode($PROCESSED["special_features"])); ?></textarea></td>
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
							displayARYearReported($otherResult["year_reported"], $AR_CUR_YEAR, $AR_PAST_YEARS, $AR_FUTURE_YEARS, false);
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
			$ERRORSTR[] = "In order to edit a Other Professional Activity record you must provide a valid Other Professional Activity identifier. The provided ID does not exist in this system.";

			echo display_error();

			application_log("notice", "Failed to provide a valid Other Professional Activity identifer when attempting to edit a Other Professional Activity record.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "In order to edit a Other Professional Activity record you must provide the Other Professional Activity identifier.";

		echo display_error();

		application_log("notice", "Failed to provide Other Professional Activity identifer when attempting to edit a Other Professional Activity record.");
	}
}
?>