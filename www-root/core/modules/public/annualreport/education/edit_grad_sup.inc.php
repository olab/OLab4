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
	$GRADUATE_SUPERVISION_ID = $_GET["rid"];
	// This grid should be expanded upon redirecting back to the education index.
	$_SESSION["education_expand_grid"] = "graduate_supervision_grid";
	if($GRADUATE_SUPERVISION_ID) {
		$query	= "SELECT * FROM `ar_graduate_supervision` WHERE `graduate_supervision_id`=".$db->qstr($GRADUATE_SUPERVISION_ID)." AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId());
		$result	= $db->GetRow($query);
		if($result) {
			$BREADCRUMB[]	= array("url" => ENTRADA_URL."/annualreport/education?section=edit_grad_sup", "title" => "Edit Graduate Supervision");
			echo "<h1>Edit Graduate Supervision</h1>\n";

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
					 * Required field "active" / Active / Inactive.
					 */
					if((isset($_POST["active"])) && ($active = clean_input($_POST["active"], array("notags", "trim")))) {
						$PROCESSED["active"] = $active;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Active / Inactive</strong> field is required.";
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
					 * Required field "year_started" / Year Started.
					 */
					if((isset($_POST["year_started"])) && ($year_started = clean_input($_POST["year_started"], array("int")))) {
						$PROCESSED["year_started"] = $year_started;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Year Started</strong> field is required.";
					}
					/**
					 * Required field "thesis_defended" / Supervision
					 */
					if((isset($_POST["thesis_defended"])) && ($thesis_defended = clean_input($_POST["thesis_defended"], array("notags", "trim")))) {
						$PROCESSED["thesis_defended"] = $thesis_defended;
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Thesis Successfully Defended</strong> field is required.";
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
						
						if($db->AutoExecute("ar_graduate_supervision", $PROCESSED, "UPDATE", "`graduate_supervision_id`=".$db->qstr($GRADUATE_SUPERVISION_ID))) {
								switch($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
									case "new" :
										$url 	= ENTRADA_URL."/annualreport/education?section=add_grad_sup";
										$msg	= "You will now be redirected to add more Graduate Supervision; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
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
		
								application_log("success", "Edited Graduate Supervision [".$GRADUATE_SUPERVISION_ID."] in the system.");					
		
						} else {
							$ERROR++;
							$ERRORSTR[] = "There was a problem editing this Graduate Supervision record in the system. The MEdIT Unit was informed of this error; please try again later.";
		
							application_log("error", "There was an error editing the Graduate Supervision. Database said: ".$db->ErrorMsg());
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
						$graduateSupQuery = "SELECT * FROM `ar_graduate_supervision` WHERE `graduate_supervision_id` = ".$db->qstr($GRADUATE_SUPERVISION_ID);						
						$graduateSupResult = $db->GetRow($graduateSupQuery);
					}
					
					if($ERROR) {
						echo display_error();
					}
					?>
					<form action="<?php echo ENTRADA_URL; ?>/annualreport/education?section=edit_grad_sup&amp;step=2&amp;rid=<?php echo $GRADUATE_SUPERVISION_ID;?>" method="post">
					<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Editing Graduate Supervision">
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
						<td><input type="text" id="student_name" name="student_name" value="<?php echo ((isset($graduateSupResult["student_name"])) ? html_encode($graduateSupResult["student_name"]) : html_encode($PROCESSED["student_name"])); ?>" maxlength="255" style="width: 95%" /></td>
					</tr>									
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="degree" class="form-required">Degree</label></td>				
						<td><select name="degree" id="degree" style="vertical-align: middle">
						<option value=""></option>
						<?php
							$degreeArray = getDegreeTypes();
							foreach($degreeArray as $degreeListValue) {
								if($ENTRADA_USER->getClinical() && $degreeListValue["visible"] == '1') {
									echo "<option value=\"".$degreeListValue["degree_type"]."\"".((($graduateSupResult["degree"] == $degreeListValue["degree_type"]) || ($PROCESSED["degree"] == $degreeListValue["degree_type"])) ? " selected=\"selected\"" : "").">".$degreeListValue["degree_type"]."</option>\n";
								} else if(!$ENTRADA_USER->getClinical() && ($degreeListValue["visible"] == '1' || $degreeListValue["visible"] == '2')) {
									echo "<option value=\"".$degreeListValue["degree_type"]."\"".((($graduateSupResult["degree"] == $degreeListValue["degree_type"]) || ($PROCESSED["degree"] == $degreeListValue["degree_type"])) ? " selected=\"selected\"" : "").">".$degreeListValue["degree_type"]."</option>\n";
								}
							}
							echo "</select>";
						?>
						</td>				
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="active" class="form-required">Active / Inactive</label></td>
						<td><select name="active" id="active" style="vertical-align: middle">
						<option value=""></option>
						<?php
						echo "<option value=\"Active\"".((($graduateSupResult["active"] == "Active") || ($PROCESSED["active"] == "Active")) ? " selected=\"selected\"" : "").">Active</option>\n";
						echo "<option value=\"Inactive\"".((($graduateSupResult["active"] == "Inactive") || ($PROCESSED["active"] == "Inactive")) ? " selected=\"selected\"" : "").">Inactive</option>\n";
						echo "</select>";
						?>
						</td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="supervision" class="form-required">Supervision</label></td>
						<td><select name="supervision" id="supervision" style="vertical-align: middle">
						<option value=""></option>
						<?php
							$supervisionArray = getSupervisionTypes();
							foreach($supervisionArray as $supervisionListValue) {
								echo "<option value=\"".$supervisionListValue["supervision_type"]."\"".((($graduateSupResult["supervision"] == $supervisionListValue["supervision_type"]) || ($PROCESSED["supervision"] == $supervisionListValue["supervision_type"])) ? " selected=\"selected\"" : "").">".$supervisionListValue["supervision_type"]."</option>\n";
							}
							echo "</select>";					
						?>
						</td>
					</tr>
					<tr>
						<td></td>
						<td><label for="year_started" class="form-required">Year Started</label></td>
						<td><select name="year_started" id="year_started" style="vertical-align: middle">
						<?php					
							for($i=1990; $i<=$AR_FUTURE_YEARS; $i++)
							{
								echo "<option value=\"".$i."\"".((($graduateSupResult["year_started"] == $i) || ($PROCESSED["year_started"] == $i)) ? " selected=\"selected\"" : "").">".$i."</option>\n";
							}
							echo "</select>";
						?>
						</td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="thesis_defended" class="form-required">Thesis Defended</label></td>
						<td><select name="thesis_defended" id="thesis_defended" style="vertical-align: middle">
						<option value=""></option>
						<?php
							echo "<option value=\"Yes\"".(($graduateSupResult["thesis_defended"] == "Yes" || $PROCESSED["thesis_defended"] == "Yes") ? " selected=\"selected\"" : "").">Yes</option>\n";
							echo "<option value=\"No\"".(($graduateSupResult["thesis_defended"] == "No" || $PROCESSED["thesis_defended"] == "No") ? " selected=\"selected\"" : "").">No</option>\n";
							echo "</select>";
						?>
						</td>
					</tr>
					<tr>
						<td></td>
						<td style="vertical-align: top"><label for="comments" class="form-nrequired">Comments</label></td>
						<td><textarea id="comments" name="comments" style="width: 95%" rows="4"><?php echo ((isset($graduateSupResult["comments"])) ? html_encode($graduateSupResult["comments"]) : html_encode($PROCESSED["comments"])); ?></textarea></td>
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
							displayARYearReported($graduateSupResult["year_reported"], $AR_CUR_YEAR, $AR_PAST_YEARS, $AR_FUTURE_YEARS, false);
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
			$ERRORSTR[] = "In order to edit a graduate teaching record you must provide a valid graduate teaching record identifier. The provided ID does not exist in this system.";

			echo display_error();

			application_log("notice", "Failed to provide a valid graduate teaching record identifer when attempting to edit a graduate teaching record.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "In order to edit a graduate teaching record you must provide the graduate teaching record identifier.";

		echo display_error();

		application_log("notice", "Failed to provide graduate teaching record identifer when attempting to edit a graduate teaching record.");
	}
}
?>
