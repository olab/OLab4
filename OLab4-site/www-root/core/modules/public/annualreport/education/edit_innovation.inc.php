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
	$INNOVATION_ID = $_GET["rid"];
	// This grid should be expanded upon redirecting back to the education index.
	$_SESSION["education_expand_grid"] = "innovation_grid";
	if($INNOVATION_ID) {
		$query	= "SELECT * FROM `ar_innovation` WHERE `innovation_id`=".$db->qstr($INNOVATION_ID)." AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId());
		$result	= $db->GetRow($query);
		if($result) {
			$BREADCRUMB[]	= array("url" => ENTRADA_URL."/annualreport/education?section=edit_innovation", "title" => "Edit Innovation in Education");
			echo "<h1>Edit Innovation in Education</h1>\n";

			// Error Checking
			switch($STEP) {
				case 2 :
					$ENDERROR = false;
					/**
					 * Required field "course_number" / Course Number.
					 */
					if((isset($_POST["course_number"])) && ($course_number = clean_input($_POST["course_number"], array("notags", "trim")))) {
						$PROCESSED["course_number"] = $course_number;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Course Number</strong> field is required.";
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
					 * Required field "type" / Type
					 */
					if((isset($_POST["type"])) && ($type = clean_input($_POST["type"], array("notags", "trim")))) {
						$PROCESSED["type"] = $type;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Type</strong> field is required.";
					}
					/**
					 * Non-Required field "description" / Description.
					 */
					if((isset($_POST["description"])) && ($description = clean_input($_POST["description"], array("notags", "trim")))) {
						$PROCESSED["description"] = $description;
					} else {
						$PROCESSED["description"] = "";
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
						
						if($db->AutoExecute("ar_innovation", $PROCESSED, "UPDATE", "`innovation_id`=".$db->qstr($INNOVATION_ID))) {
								switch($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
									case "new" :
										$url 	= ENTRADA_URL."/annualreport/education?section=add_innovation";
										$msg	= "You will now be redirected to add more Innovation in Education; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
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
		
								application_log("success", "Edited Innovation in Education [".$INNOVATION_ID."] in the system.");					
		
						} else {
							$ERROR++;
							$ERRORSTR[] = "There was a problem editing this Innovation in Education record in the system. The MEdIT Unit was informed of this error; please try again later.";
		
							application_log("error", "There was an error editing the Innovation in Education. Database said: ".$db->ErrorMsg());
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
						$innovationQuery = "SELECT * FROM `ar_innovation` WHERE `innovation_id` = ".$db->qstr($INNOVATION_ID);						
						$innovationResult = $db->GetRow($innovationQuery);
					}
					
					if($ERROR) {
						echo display_error();
					}
					?>
					<form action="<?php echo ENTRADA_URL; ?>/annualreport/education?section=edit_innovation&amp;step=2&amp;rid=<?php echo $INNOVATION_ID;?>" method="post">
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
							<td><label for="course_number" class="form-required">Course Number</label></td>
							<td><input type="text" id="course_number" name="course_number" value="<?php echo ((isset($innovationResult["course_number"])) ? html_encode($innovationResult["course_number"]) : html_encode($PROCESSED["course_number"])); ?>" maxlength="255" style="width: 20%" /></td>
						</tr>						
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="course_name" class="form-required">Course Name</label></td>
							<td><input type="text" id="course_name" name="course_name" value="<?php echo ((isset($innovationResult["course_name"])) ? html_encode($innovationResult["course_name"]) : html_encode($PROCESSED["course_name"])); ?>" maxlength="255" style="width: 95%" /></td>
						</tr>									
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="type" class="form-required">Type</label></td>				
							<td><select name="type" id="type" style="vertical-align: middle">
							<option value=""></option>
							<?php
								$innovationArray = getInnovationTypes();
								foreach($innovationArray as $innovationListValue) {
									echo "<option value=\"".$innovationListValue["innovation_type"]."\"".((($innovationResult["type"] == $innovationListValue["innovation_type"]) || ($PROCESSED["type"] == $innovationListValue["innovation_type"])) ? " selected=\"selected\"" : "").">".$innovationListValue["innovation_type"]."</option>\n";
								}
								echo "</select>";
							?>
							</td>				
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="description" class="form-nrequired">Description</label></td>
							<td><textarea id="description" name="description" style="width: 95%" rows="4"><?php echo ((isset($innovationResult["description"])) ? html_encode($innovationResult["description"]) : html_encode($PROCESSED["description"])); ?></textarea></td>
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
								displayARYearReported($innovationResult["year_reported"], $AR_CUR_YEAR, $AR_PAST_YEARS, $AR_FUTURE_YEARS, false);
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
			$ERRORSTR[] = "In order to edit a innovation record you must provide a valid innovation identifier. The provided ID does not exist in this system.";

			echo display_error();

			application_log("notice", "Failed to provide a valid innovation identifer when attempting to edit an innovation record.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "In order to edit an innovation record you must provide the innovation identifier.";

		echo display_error();

		application_log("notice", "Failed to provide innovation identifer when attempting to edit an innovation record.");
	}
}
?>