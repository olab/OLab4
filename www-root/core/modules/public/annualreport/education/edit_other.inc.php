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
	$OTHER_ID = $_GET["rid"];
	// This grid should be expanded upon redirecting back to the education index.
	$_SESSION["education_expand_grid"] = "other_grid";
	if($OTHER_ID) {
		$query	= "SELECT * FROM `ar_other` WHERE `other_id`=".$db->qstr($OTHER_ID)." AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId());
		$result	= $db->GetRow($query);
		if($result) {
			$BREADCRUMB[]	= array("url" => ENTRADA_URL."/annualreport/education?section=edit_other", "title" => "Edit Other Education");
			
			echo "<h1>Edit Other Education</h1>\n";

			// Error Checking
			switch($STEP) {
				case 2 :
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
					 * Required field "type" / Type			 
					 */
					if((isset($_POST["type"])) && ($type = clean_input($_POST["type"], array("notags", "trim")))) {
						$PROCESSED["type"] = $type;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Type</strong> field is required.";
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
						
						if($db->AutoExecute("ar_other", $PROCESSED, "UPDATE", "`other_id`=".$db->qstr($OTHER_ID))) {
								switch($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
									case "new" :
										$url 	= ENTRADA_URL."/annualreport/education?section=add_graduate";
										$msg	= "You will now be redirected to add more Other Education; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
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
		
								application_log("success", "Edited Other Education [".$OTHER_ID."] in the system.");					
		
						} else {
							$ERROR++;
							$ERRORSTR[] = "There was a problem editing this Other Education record in the system. The MEdIT Unit was informed of this error; please try again later.";
		
							application_log("error", "There was an error editing the Other Education. Database said: ".$db->ErrorMsg());
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
						$otherQuery = "SELECT * FROM `ar_other` WHERE `other_id` = ".$db->qstr($OTHER_ID);						
						$otherResult = $db->GetRow($otherQuery);
					}
					
					if($ERROR) {
						echo display_error();
					}
					?>
					<form action="<?php echo ENTRADA_URL; ?>/annualreport/education?section=edit_other&amp;step=2&amp;rid=<?php echo $OTHER_ID;?>" method="post">			
					<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Editing Other Education">
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
							<td style="vertical-align: top"><label for="course_name" class="form-required">Course Name</label></td>
							<td><input type="text" id="course_name" name="course_name" value="<?php echo ((isset($otherResult["course_name"])) ? html_encode($otherResult["course_name"]) : html_encode($PROCESSED["course_name"])); ?>" maxlength="255" style="width: 95%" /></td>
						</tr>									
						<tr>
							<td></td>
							<td><label for="type" class="form-required">Type</label></td>
							<td><select name="type" id="type" style="vertical-align: middle">
							<option value=""></option>
							<?php
								$activityTypeArray = getActivityTypes();
								foreach($activityTypeArray as $activityTypeListValue) {
									echo "<option value=\"".$activityTypeListValue["activity_type"]."\"".(($otherResult["type"] == $activityTypeListValue["activity_type"] || $PROCESSED["type"] == $activityTypeListValue["activity_type"]) ? " selected=\"selected\"" : "").">".html_encode($activityTypeListValue["activity_type"])."</option>\n";
								}
							?>
							</select>
							</td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="description" class="form-nrequired">Description</label></td>
							<td><textarea id="description" name="description" style="width: 95%" rows="4"><?php echo ((isset($otherResult["description"])) ? html_encode($otherResult["description"]) : html_encode($PROCESSED["description"])); ?></textarea></td>
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
			$ERRORSTR[] = "In order to edit a other record you must provide a valid other identifier. The provided ID does not exist in this system.";

			echo display_error();

			application_log("notice", "Failed to provide a valid other identifer when attempting to edit an other record.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "In order to edit an other record you must provide the other identifier.";

		echo display_error();

		application_log("notice", "Failed to provide other identifer when attempting to edit an other record.");
	}
}
?>