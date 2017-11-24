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
	$UNDERGRADUATE_SUPERVISION_ID = $_GET["rid"];
	// This grid should be expanded upon redirecting back to the education index.
	$_SESSION["education_expand_grid"] = "undergraduate_supervision_grid";
	if($UNDERGRADUATE_SUPERVISION_ID) {
		$query	= "SELECT * FROM `ar_undergraduate_supervision` WHERE `undergraduate_supervision_id`=".$db->qstr($UNDERGRADUATE_SUPERVISION_ID)." AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId());
		$result	= $db->GetRow($query);
		if($result) {
			$BREADCRUMB[]	= array("url" => ENTRADA_URL."/annualreport/education?section=edit_undergrad_sup", "title" => "Edit Undergraduate Supervision");
			echo "<h1>Edit Undergraduate Supervision</h1>\n";

			// Error Checking
			switch($STEP) {
				case 2 :					
					/**
					 * Required field "student_name" / Student Name
					 */
					if((isset($_POST["student_name"])) && ($student_name = clean_input($_POST["student_name"], array("notags", "trim")))) {
						$PROCESSED["student_name"] = $student_name;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Student Name</strong> field is required.";
					}
					/**
					 * Required field "degree" / Degree
					 */
					if((isset($_POST["degree"])) && ($degree = clean_input($_POST["degree"], array("notags", "trim")))) {
						$PROCESSED["degree"] = $degree;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Degree</strong> field is required.";
					}
					/**
					 * Non-Required field "course_number" / Course Number.
					 */
					if((isset($_POST["course_number"])) && ($course_number = clean_input($_POST["course_number"], array("notags", "trim")))) {
						$PROCESSED["course_number"] = $course_number;
					} else {
						$PROCESSED["course_number"] = "";
					}		
					/**
					 * Required field "supervision" / Supervision
					 */
					if((isset($_POST["supervision"])) && ($supervision = clean_input($_POST["supervision"], array("notags", "trim")))) {
						$PROCESSED["supervision"] = $supervision;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Supervision</strong> field is required.";
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
						
						if($db->AutoExecute("ar_undergraduate_supervision", $PROCESSED, "UPDATE", "`undergraduate_supervision_id`=".$db->qstr($UNDERGRADUATE_SUPERVISION_ID))) {
								switch($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
									case "new" :
										$url 	= ENTRADA_URL."/annualreport/education?section=add_undergrad_sup";
										$msg	= "You will now be redirected to add more Undergraduate Supervision; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
									break;
									case "index" :
									default :
										$url 	= ENTRADA_URL."/annualreport/education";
										$msg	= "You will now be redirected to the education page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
									break;
								}
		
								$SUCCESS++;
								$SUCCESSSTR[]  = "You have successfully edited <strong>".html_encode($PROCESSED["student_name"])."</strong> in the system.<br /><br />".$msg;
								$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000);";
		
								application_log("success", "Edited Undergraduate Supervision [".$UNDERGRADUATE_SUPERVISION_ID."] in the system.");					
		
						} else {
							$ERROR++;
							$ERRORSTR[] = "There was a problem editing this Undergraduate Supervision record in the system. The MEdIT Unit was informed of this error; please try again later.";
		
							application_log("error", "There was an error editing the Undergraduate Supervision. Database said: ".$db->ErrorMsg());
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
						$undergraduateSupQuery = "SELECT * FROM `ar_undergraduate_supervision` WHERE `undergraduate_supervision_id` = ".$db->qstr($UNDERGRADUATE_SUPERVISION_ID);
						$undergraduateSupResult = $db->GetRow($undergraduateSupQuery);
					}
					
					if($ERROR) {
						echo display_error();
					}
					?>					
					<form action="<?php echo ENTRADA_URL; ?>/annualreport/education?section=edit_undergrad_sup&amp;step=2&amp;rid=<?php echo $UNDERGRADUATE_SUPERVISION_ID;?>" method="post">
					<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Editing Undergraduate Supervision">
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
						<td style="vertical-align: top"><label for="student_name" class="form-required">Student Name</label></td>
						<td><input type="text" id="student_name" name="student_name" value="<?php echo ((isset($undergraduateSupResult["student_name"])) ? html_encode($undergraduateSupResult["student_name"]) : html_encode($PROCESSED["student_name"])); ?>" maxlength="255" style="width: 95%" /></td>
					</tr>									
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="degree" class="form-required">Degree</label></td>				
						<td><select name="degree" id="degree" style="vertical-align: middle">
						<option value=""></option>
						<?php
							$degreeArray = getDegreeTypes();
							foreach($degreeArray as $degreeListValue) {
								echo "<option value=\"".$degreeListValue["degree_type"]."\"".((($undergraduateSupResult["degree"] == $degreeListValue["degree_type"]) || ($PROCESSED["degree"] == $degreeListValue["degree_type"])) ? " selected=\"selected\"" : "").">".$degreeListValue["degree_type"]."</option>\n";
							}
							echo "</select>";
						?>
						</td>				
					</tr>
					<tr>
						<td></td>
						<td><label for="course_number" class="form-nrequired">Course Number</label></td>
						<td><input type="text" id="course_number" name="course_number" value="<?php echo ((isset($undergraduateSupResult["course_number"])) ? html_encode($undergraduateSupResult["course_number"]) : html_encode($PROCESSED["course_number"])); ?>" maxlength="255" style="width: 20%" /></td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="supervision" class="form-required">Supervision</label></td>
						<td><select name="supervision" id="supervision" style="vertical-align: middle">
						<option value=""></option>
						<?php
							$supervisionArray = getSupervisionTypes();
							foreach($supervisionArray as $supervisionListValue) {
								echo "<option value=\"".$supervisionListValue["supervision_type"]."\"".((($undergraduateSupResult["supervision"] == $supervisionListValue["supervision_type"]) || ($PROCESSED["supervision"] == $supervisionListValue["supervision_type"])) ? " selected=\"selected\"" : "").">".$supervisionListValue["supervision_type"]."</option>\n";
							}
							echo "</select>";					
						?>
						</td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="comments" class="form-nrequired">Comments</label></td>
						<td><textarea id="comments" name="comments" style="width: 95%" rows="4"><?php echo ((isset($undergraduateSupResult["comments"])) ? html_encode($undergraduateSupResult["comments"]) : html_encode($PROCESSED["comments"])); ?></textarea></td>
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
							displayARYearReported($undergraduateSupResult["year_reported"], $AR_CUR_YEAR, $AR_PAST_YEARS, $AR_FUTURE_YEARS, false);
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
			$ERRORSTR[] = "In order to edit a undergraduate supervision record you must provide a valid undergraduate supervision record identifier. The provided ID does not exist in this system.";

			echo display_error();

			application_log("notice", "Failed to provide a valid undergraduate supervision record identifer when attempting to edit a undergraduate supervision record.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "In order to edit a undergraduate supervision record you must provide the undergraduate supervision record identifier.";

		echo display_error();

		application_log("notice", "Failed to provide undergraduate supervision record identifer when attempting to edit a undergraduate supervision record.");
	}
}
?>