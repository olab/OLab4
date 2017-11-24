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
	$PAGE_META["title"]			= "Add Membership on Graduate Committees";
	$PAGE_META["description"]	= "Membership on Graduate Committees portion of your annual report should be entered / located here.";
	$PAGE_META["keywords"]		= "";
	
	// This grid should be expanded upon redirecting back to the education index.
	$_SESSION["education_expand_grid"] = "memberships_grid";
	
	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/annualreport/education?".replace_query(array("section" => "add_membership")), "title" => "Add Membership on Graduate Committees");
	
	echo "<h1>Add Membership on Graduate Committees</h1>";
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
			 * Required field "department" / Department
			 */
			if((isset($_POST["department"])) && ($department = clean_input($_POST["department"], array("notags", "trim")))) {
				$PROCESSED["department"] = $department;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Department</strong> field is required.";
			}
			/**
			 * Required field "university" / University.
			 */
			if((isset($_POST["university"])) && ($university = clean_input($_POST["university"], array("notags", "trim")))) {
				$PROCESSED["university"] = $university;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>University</strong> field is required.";
			}
			/**
			 * Required field "role" / Role.
			 */
			if((isset($_POST["role"])) && ($role = clean_input($_POST["role"], array("notags", "trim")))) {
				$PROCESSED["role"] = $role;
				if($PROCESSED["role"] != "Other (specify)" && ($_POST["role_description"] != "" || $PROCESSED["role_description"] != "" )) {
					$ERROR++;
					$ERRORSTR[] = "If you wish to enter data in the <strong>Role Description</strong> field then you must select \"Other (specify)\" as a <strong>Role</strong>
					  Otherwise clear the <strong>Role Description</strong> field and resubmit.";
				}
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Role</strong> field is required.";
			}
			/**
			 * Required field (if other selected above) "role_description" / Role Description.
			 */
			if((isset($_POST["role_description"])) && ($role = clean_input($_POST["role_description"], array("notags", "trim")))) {
				$PROCESSED["role_description"] = $role;
			} else {
				if($PROCESSED["role"] == "Other (specify)")
				{
					$ERROR++;
					$ERRORSTR[] = "The <strong>Role Description</strong> field is required when \"Other (specify)\" is selected as a <strong>Role</strong>.";
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
				
				if($db->AutoExecute("ar_memberships", $PROCESSED, "INSERT")) {
					$EVENT_ID = $db->Insert_Id();
						switch($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
							case "new" :
								$url 	= ENTRADA_URL."/annualreport/education?section=add_membership";
								$msg	= "You will now be redirected to add another new record; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
							break;
							case "index" :
							default :
								$url 	= ENTRADA_URL."/annualreport/education";
								$msg	= "You will now be redirected to the education page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
							break;
						}

						$SUCCESS++;
						$SUCCESSSTR[]  = "You have successfully added membership information pertaining to <strong>".html_encode($PROCESSED["student_name"])."</strong> to the system.<br /><br />".$msg;
						$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000);";

						application_log("success", "Membership on Graduate Committees [".$EVENT_ID."] added to the system.");					

				} else {
					$ERROR++;
					$ERRORSTR[] = "There was a problem inserting this record into the system. The MEdIT Unit was informed of this error; please try again later.";

					application_log("error", "There was an error inserting a membership record. Database said: ".$db->ErrorMsg());
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
			<form action="<?php echo ENTRADA_URL; ?>/annualreport/education?section=add_membership&amp;step=2" method="post">
			<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Adding Membership on Graduate Committees">
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
				<td style="vertical-align: top"><label for="department" class="form-required">Department</label></td>
				<td><input type="text" id="department" name="department" value="<?php echo html_encode($PROCESSED["department"]); ?>" maxlength="150" style="width: 95%" /></td>
			</tr>
			<tr>
				<td></td>
				<td style="vertical-align: top"><label for="university" class="form-required">University</label></td>
				<td><input type="text" id="university" name="university" value="<?php echo html_encode($PROCESSED["university"]); ?>" maxlength="255" style="width: 95%" /></td>
			</tr>
			<tr>
				<td></td>
				<td><label for="role" class="form-required">Role</label></td>
				<td><select name="role" id="role" style="vertical-align: middle">
				<option value=""></option>
				<?php
					$membershipRoleArray = getMembershipRoles();
					foreach($membershipRoleArray as $membershipRoleListValue) {
						echo "<option value=\"".$membershipRoleListValue["membership_role"]."\"".(($PROCESSED["role"] == $membershipRoleListValue["membership_role"]) ? " selected=\"selected\"" : "").">".html_encode($membershipRoleListValue["membership_role"])."</option>\n";
					}
					echo "</select>";
				?>
				</td>
			</tr>
			<tr>
				<td></td>
				<td style="vertical-align: top"><label for="role_description" class="form-nrequired">Role Description</label></td>
				<td><input type="text" id="role_description" name="role_description" value="<?php echo html_encode($PROCESSED["role_description"]); ?>" style="width: 95%" /></td>
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