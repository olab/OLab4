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
	$PAGE_META["title"]			= "Add Undergraduate Supervision";
	$PAGE_META["description"]	= "Undergraduate Supervision portion of your annual report should be entered / located here.";
	$PAGE_META["keywords"]		= "";
	
	// This grid should be expanded upon redirecting back to the education index.
	$_SESSION["education_expand_grid"] = "undergraduate_supervision_grid";
	
	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/annualreport/education?".replace_query(array("section" => "add_undergrad_sup")), "title" => "Add Undergraduate Supervision");
	echo "<h1>Add Undergraduate Supervision</h1>";
	// Error Checking
	switch($STEP) {
		case 2 :
			$ENDERROR = false;
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
				
				if($db->AutoExecute("ar_undergraduate_supervision", $PROCESSED, "INSERT")) {
					$EVENT_ID = $db->Insert_Id();
						switch($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
							case "new" :
								$url	= ENTRADA_URL."/annualreport/education?section=add_undergrad_sup";
								$msg	= "You will now be redirected to add another new record; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
							break;
							case "index" :
							default :
								$url 	= ENTRADA_URL."/annualreport/education";
								$msg	= "You will now be redirected to the education page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
							break;
						}

						$SUCCESS++;
						$SUCCESSSTR[]  = "You have successfully added information pertaining to supervision of undergraduate student <strong>".html_encode($PROCESSED["student_name"])."</strong> to the system.<br /><br />".$msg;
						$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000);";

						application_log("success", "Undergraduate Supervision [".$EVENT_ID."] added to the system.");					

				} else {
					$ERROR++;
					$ERRORSTR[] = "There was a problem inserting this record into the system. The MEdIT Unit was informed of this error; please try again later.";

					application_log("error", "There was an error inserting an undergraduate supervision record. Database said: ".$db->ErrorMsg());
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
			<form action="<?php echo ENTRADA_URL; ?>/annualreport/education?section=add_undergrad_sup&amp;step=2" method="post">
			<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Adding Undergraduate Supervision">
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
				<td><input type="text" id="student_name" name="student_name" value="<?php echo html_encode($PROCESSED["student_name"]); ?>" maxlength="255" style="width: 95%" /></td>
			</tr>									
			<tr>
				<td></td>
				<td style="vertical-align: top"><label for="degree" class="form-required">Degree</label></td>				
				<td><select name="degree" id="degree" style="vertical-align: middle">
				<option value=""></option>
				<?php
					$degreeArray = getDegreeTypes();
					foreach($degreeArray as $degreeListValue) {
						echo "<option value=\"".$degreeListValue["degree_type"]."\"".(($PROCESSED["degree"] == $degreeListValue["degree_type"]) ? " selected=\"selected\"" : "").">".html_encode($degreeListValue["degree_type"])."</option>\n";
					}
					echo "</select>";
				?>
				</td>				
			</tr>
			<tr>
				<td></td>
				<td><label for="course_number" class="form-nrequired">Course Number</label></td>
				<td><input type="text" id="course_number" name="course_number" value="<?php echo html_encode($PROCESSED["course_number"]); ?>" maxlength="25" style="width: 125px" /></td>
			</tr>
			<tr>
				<td></td>
				<td style="vertical-align: top"><label for="supervision" class="form-required">Supervision</label></td>
				<td><select name="supervision" id="supervision" style="vertical-align: middle">
				<option value=""></option>
				<?php
					$supervisionArray = getSupervisionTypes();
					foreach($supervisionArray as $supervisionListValue) {
						echo "<option value=\"".$supervisionListValue["supervision_type"]."\"".(($PROCESSED["supervision"] == $supervisionListValue["supervision_type"]) ? " selected=\"selected\"" : "").">".html_encode($supervisionListValue["supervision_type"])."</option>\n";
					}
					echo "</select>";					
				?>
				</td>
			</tr>
			<tr>
				<td></td>
				<td style="vertical-align: top"><label for="comments" class="form-nrequired">Comments</label></td>
				<td><textarea id="comments" name="comments" style="width: 95%" rows="4"><?php echo html_encode($PROCESSED["comments"]); ?></textarea></td>				
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
}
?>