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
	$PAGE_META["title"]			= "Add Research Grant";
	$PAGE_META["description"]	= "Research portion of your annual report should be entered / located here.";
	$PAGE_META["keywords"]		= "";
	
	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/annualreport/research?section=add", "title" => "Add Research Grant");
	
	// This grid should be expanded upon redirecting back to the research index.
	$_SESSION["research_expand_grid"] = "research_grid";
	
	echo "<h1>Add Research</h1>";
	// Error Checking
	switch($STEP) {
		case 2 :
			$ENDERROR = false;
			if($ENTRADA_USER->getClinical()) {
				/**
				 * Required field "status" / Status
				 */
				if((isset($_POST["status"])) && ($status = clean_input($_POST["status"], array("notags", "trim")))) {
					$PROCESSED["status"] = $status;
				} else {
					$ERROR++;
					$ERRORSTR[] = "The <b>Status</b> field is required.";
				}
			}
			/**
			 * Required field "grant_title" / Grant Title.
			 */
			if((isset($_POST["grant_title"])) && ($grant_title = clean_input($_POST["grant_title"], array("notags", "trim")))) {
				$PROCESSED["grant_title"] = $grant_title;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <b>Title of Research / Grant</b> field is required.";
			}
			/**
			 * Required field "type" / Type
			 */
			if((isset($_POST["type"])) && ($type = clean_input($_POST["type"], array("notags", "trim")))) {
				$PROCESSED["type"] = $type;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <b>Type</b> field is required.";
			}
			if($ENTRADA_USER->getClinical()) {
				/**
				 * Required field "category" / Category
				 */
				if((isset($_POST["location"])) && ($location = clean_input($_POST["location"], array("notags", "trim")))) {
					$PROCESSED["location"] = $location;
				} else {
					$ERROR++;
					$ERRORSTR[] = "The <b>Category</b> field is required.";
				}
				/**
				 * Required field "multiinstitutional" / Multi-Institutional
				 */
				if((isset($_POST["multiinstitutional"])) && ($multiinstitutional = clean_input($_POST["multiinstitutional"], array("notags", "trim")))) {
					$PROCESSED["multiinstitutional"] = $multiinstitutional;
				} else {
					$ERROR++;
					$ERRORSTR[] = "The <b>Multi-Institutional</b> field is required.";
				}
			}
			/**
			 * Dependent field "agency" / Sponsoring Agency.
			 */
			if((isset($_POST["agency"])) && ($agency = clean_input($_POST["agency"], array("notags", "trim"))) && $_POST["funding_status"] != "unfunded") {
				$PROCESSED["agency"] = $agency;
			} else if($_POST["funding_status"] != "unfunded") {
				$ERROR++;
				$ERRORSTR[] = "The <b>Sponsoring Agency</b> field is required.";
			} else if($_POST["funding_status"] == "unfunded" && (isset($_POST["agency"])) && ($agency = clean_input($_POST["agency"], array("notags", "trim")))) {
				$PROCESSED["agency"] = $agency;
				$ERROR++;
				$ERRORSTR[] = "You have entered <b>Sponsoring Agency</b> but the project is unfunded.";
			}
			else {
				$PROCESSED["agency"] = $agency;
			}
			/**
			 * Required field "role" / Role
			 */
			if((isset($_POST["role"])) && ($role = clean_input($_POST["role"], array("notags", "trim")))) {
				$PROCESSED["role"] = $role;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <b>Role</b> field is required.";
			}
			/**
			 * Required field "principal_investigator" / Principal Investigator
			 */
			if((isset($_POST["principal_investigator"])) && ($principal_investigator = clean_input($_POST["principal_investigator"], array("notags", "trim")))) {
				$PROCESSED["principal_investigator"] = $principal_investigator;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <b>Principal Investigator</b> field is required.";
			}

			/**
			 * Non-required field "co_investigator_list" / Co-Investigator List			 
			 */
			if((isset($_POST["co_investigator_list"])) && ($co_investigator_list = clean_input($_POST["co_investigator_list"], array("notags", "trim")))) {
				$PROCESSED["co_investigator_list"] = $co_investigator_list;
			}

			/**
			 * Dependent field "amount_received" / Amount Received
			 */
			if(isset($_POST["funding_status"]) && $_POST["funding_status"] != "unfunded")
			{
				if((isset($_POST["amount_received"])) && ($amount_received = clean_input($_POST["amount_received"], array("notags", "money", "float")))) {
				    if((int)$amount_received == 0)
					{
					    $PROCESSED["amount_received"] = 0.00;
					}
					else
					{
				        $PROCESSED["amount_received"] = $amount_received;
					}				    
				} else if($PROCESSED["role"] == "Principal-Investigator") {
					$ERROR++;
					$ERRORSTR[] = "The <b>Amount Received</b> field is required.";				
				} else if (!isset($_POST["funding_status"])) {
					$PROCESSED["amount_received"] = 0.00;	
				}
			} elseif(isset($_POST["funding_status"]) && $_POST["funding_status"] == "unfunded") {
				
				if((isset($_POST["amount_received"])) && ($amount_received = clean_input($_POST["amount_received"], array("notags", "float"))) && $amount_received != 0.00) {
					$PROCESSED["amount_received"] = $amount_received;
					$ERROR++;
					$ERRORSTR[] = "You have entered a value into the <b>Amount Received</b> field, however, the project is <b>Unfunded</b>.";
				}
				else 
				{
					$PROCESSED["amount_received"] = 0.00;
				}
			} elseif ($_POST["role"] == "Principal Investigator" && (!(isset($_POST["amount_received"])) || $_POST["amount_received"] == 0)) {
				$ERROR++;
				$ERRORSTR[] = "You are the Principal Investigator, you must enter a value into the <b>Amount Received</b> field.";
			}
			
			/**
			 * Required field "start_month" / Start			 
			 */
			if((isset($_POST["start_month"])) && ($start_month= clean_input($_POST["start_month"], array("int")))) {				
				$PROCESSED["start_month"] = $start_month;
			} else {
				$ERROR++;
				$ERRORSTR[] = "Both <b>Start</b> fields are required.";
				$STARTERROR = true;
			}
			/**
			 * Required field "start_year" / Start			 
			 */
			if((isset($_POST["start_year"])) && ($start_year= clean_input($_POST["start_year"], array("int")))) {
				$PROCESSED["start_year"] = $start_year;
			} else if(!$STARTERROR){
				$ERROR++;
				$ERRORSTR[] = "Both <b>Start</b> fields are required.";
				$STARTERROR = true;
			}			
			/**
			 * Non-Required field "end_month" / End			 
			 */
			if(((isset($_POST["end_month"])) && ($end_month= clean_input($_POST["end_month"], array("int")))) 
			&& (isset($_POST["end_year"]) && ($end_year= clean_input($_POST["end_year"], array("int"))))) {
				$PROCESSED["end_month"] = $end_month;
				$PROCESSED["end_year"] = $end_year;
			} else if(((isset($_POST["end_month"])) && ($end_month= clean_input($_POST["end_month"], array("int")))) && (!isset($_POST["end_year"]) || $_POST["end_year"] == '')) {
				$PROCESSED["end_month"] = $end_month;
				
				$ERROR++;
				$ERRORSTR[] = "Both <b>End</b> fields are required to be entered or left blank for ongoing commitments.";
				$ENDERROR = true;
			}
			else if(((isset($_POST["end_year"])) && ($end_year= clean_input($_POST["end_year"], array("int")))) && (!isset($_POST["end_month"]) || $_POST["end_month"] == '')) {
				$PROCESSED["end_year"] = $end_year;
				
				$ERROR++;
				$ERRORSTR[] = "Both <b>End</b> fields are required to be entered or left blank for ongoing commitments.";
				$ENDERROR = true;
			}
			else 
			{
				$PROCESSED["end_month"] = '';
				$PROCESSED["end_year"] = '';
			}
			/**
			 * Check to make sure years are in order
			 */
			if((isset($_POST["end_year"]) && $_POST["end_year"] != '') && isset($_POST["start_year"]) && ($_POST["start_year"] > $_POST["end_year"]))
			{
				$ERROR++;
				$ERRORSTR[] = "<b>Start</b> year cannot be greater than <b>End</b> year.";				
			}			
			/**
			 * Required field "funding_status" / Funding Status.
			 */
			if((isset($_POST["funding_status"])) && ($funding_status = clean_input($_POST["funding_status"], array("notags", "trim")))) {
				$PROCESSED["funding_status"] = $funding_status;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <b>Funding Status</b> field is required.";
			}			
			/**
			 * Required field "year_reported" / Year Reported.
			 */
			if((isset($_POST["year_reported"])) && ($year_reported = clean_input($_POST["year_reported"], array("notags", "trim")))) {
				$PROCESSED["year_reported"] = $year_reported;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <b>Year Reported</b> field is required.";
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
				
				if($db->AutoExecute("ar_research", $PROCESSED, "INSERT")) {
					$EVENT_ID = $db->Insert_Id();
						switch($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
							case "new" :
								$url 	= ENTRADA_URL."/annualreport/research?section=add";
								$msg	= "You will now be redirected to add another new record; this will happen <b>automatically</b> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
							break;
							case "index" :
							default :
								$url 	= ENTRADA_URL."/annualreport/research";
								$msg	= "You will now be redirected to the research page; this will happen <b>automatically</b> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
							break;
						}

						$SUCCESS++;
						$SUCCESSSTR[]  = "You have successfully added <b>".html_encode($PROCESSED["grant_title"])."</b> to the system.<br /><br />".$msg;
						$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000);";

						application_log("success", "Grant Title [".$EVENT_ID."] added to the system.");					

				} else {
					$ERROR++;
					$ERRORSTR[] = "There was a problem inserting this event into the system. The MEdIT Unit was informed of this error; please try again later.";

					application_log("error", "There was an error inserting a event. Database said: ".$db->ErrorMsg());
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
			<form action="<?php echo ENTRADA_URL; ?>/annualreport/research?section=add&amp;step=2" method="post">
			<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Adding Research">
			<colgroup>
				<col style="width: 3%" />
				<col style="width: 20%" />
				<col style="width: 77%" />
			</colgroup>
			<tr>
				<td colspan="3"><h2>Details</h2></td>
			</tr>
			<?php
				if($ENTRADA_USER->getClinical()) {
			?>
			<tr>
				<td></td>
				<td style="vertical-align: top"><label for="status" class="form-required">Status</label></td>
				<td>
				<?php
					if($PROCESSED["status"] == "Ongoing") {
						echo "<input type=\"radio\" id=\"status\" name=\"status\" value=\"New\"/> New<br>
						<input type=\"radio\" id=\"status\" name=\"status\" value=\"Ongoing\"/ CHECKED> Ongoing<br>
						<input type=\"radio\" id=\"status\" name=\"status\" value=\"Renewed\"/> Renewed";
					} else if($PROCESSED["status"] == "Renewed") {
						echo "<input type=\"radio\" id=\"status\" name=\"status\" value=\"New\"/> New<br>
						<input type=\"radio\" id=\"status\" name=\"status\" value=\"Ongoing\"/> Ongoing<br>
						<input type=\"radio\" id=\"status\" name=\"status\" value=\"Renewed\" CHECKED/> Renewed";
					} else {
						echo "<input type=\"radio\" id=\"status\" name=\"status\" value=\"New\" CHECKED /> New<br>
						<input type=\"radio\" id=\"status\" name=\"status\" value=\"Ongoing\"/> Ongoing<br>
						<input type=\"radio\" id=\"status\" name=\"status\" value=\"Renewed\"/> Renewed";
					}
				?>
				</td>
			</tr>
			<tr>
				<td colspan="3">&nbsp;</td>
			</tr>
			<?php
				}
			?>
			<tr>
				<td></td>
				<td><label for="grant_title" class="form-required">Title of Research / Grant</label></td>
				<td><input type="text" id="grant_title" name="grant_title" value="<?php echo html_encode($PROCESSED["grant_title"]); ?>" maxlength="255" style="width: 95%" /></td>
			</tr>
			<tr>
				<td></td>
				<td><label for="type" class="form-required">Type</label></td>
				<td>
					<?php
						echo "<select id=\"type\" name=\"type\" style=\"width: 95%\">\n
						<option value=\"\"></option>";		
						$researchTypeArray = getResearchTypes();
						foreach($researchTypeArray as $type) {
							echo "<option value=\"".$type["research_type"]."\"".(($PROCESSED["type"] == $type["research_type"]) ? " selected=\"selected\"" : "").">".html_encode($type["research_type"])."</option>\n";
						}
						echo "</select>\n";
					?>
				</td>
			</tr>
			<tr>
				<td colspan="3">&nbsp;</td>
			</tr>
			<?php
				if($ENTRADA_USER->getClinical()) {
			?>
			<tr>
				<td></td>
				<td style="vertical-align: top"><label for="category" class="form-required">Category</label></td>
				<td>
				<?php
					if($PROCESSED["location"] == "External") {
						echo "<input type=\"radio\" id=\"location\" name=\"location\" value=\"Internal\"/> Internal to Queen's<br>
						<input type=\"radio\" id=\"location\" name=\"location\" value=\"External\" CHECKED /> External";
					} else {
						echo "<input type=\"radio\" id=\"location\" name=\"location\" value=\"Internal\" CHECKED /> Internal to Queen's<br>
						<input type=\"radio\" id=\"location\" name=\"location\" value=\"External\"/> External";
					}
				?>
				</td>
			</tr>
			<tr>
				<td colspan="3">&nbsp;</td>
			</tr>
			<tr>
				<td></td>
				<td style="vertical-align: top"><label for="multiinstitutional" class="form-required">Multi-Institutional</label></td>
				<td>
				<?php
					if($PROCESSED["multiinstitutional"] == "Yes") {
						echo "<input type=\"radio\" id=\"multiinstitutional\" name=\"multiinstitutional\" value=\"Yes\" CHECKED/> Yes<br>
						<input type=\"radio\" id=\"multiinstitutional\" name=\"multiinstitutional\" value=\"No\"/> No";
					} else {
						echo "<input type=\"radio\" id=\"multiinstitutional\" name=\"multiinstitutional\" value=\"Yes\"/> Yes<br>
						<input type=\"radio\" id=\"multiinstitutional\" name=\"multiinstitutional\" value=\"No\" CHECKED/> No";
					}
				?>
				</td>
			</tr>
			<tr>
				<td colspan="3">&nbsp;</td>
			</tr>
			<?php
				}
			?>
			<tr>
				<td></td>
				<td style="vertical-align: top"><label for="agency" class="form-nrequired">Sponsoring Agency</label></td>
				<td><input type="text" id="agency" name="agency" value="<?php echo html_encode($PROCESSED["agency"]); ?>" maxlength="255" style="width: 95%" /></td>
			</tr>
			<tr>
				<td colspan="3">&nbsp;</td>
			</tr>
			<tr>
				<td></td>
				<td style="vertical-align: top"><label for="role" class="form-required">Role</label></td>
				<td>
				<?php
					if($PROCESSED["role"] == "Co-Investigator") {
						echo "<input type=\"radio\" id=\"role\" name=\"role\" value=\"Principal Investigator\"/> Funded<br>
						<input type=\"radio\" id=\"role\" name=\"role\" value=\"Co-Investigator\" CHECKED /> Co-Investigator";
					} else {
						echo "<input type=\"radio\" id=\"role\" name=\"role\" value=\"Principal Investigator\" CHECKED /> Principal Investigator<br>
						<input type=\"radio\" id=\"role\" name=\"role\" value=\"Co-Investigator\"/> Co-Investigator";
					}
				?>
				</td>
			</tr>
			<tr>
				<td colspan="3">&nbsp;</td>
			</tr>
			<tr>
				<td></td>
				<td style="vertical-align: top"><label for="principal_investigator" class="form-required">Principal Investigator</label></td>
				<td><input type="text" id="principal_investigator" name="principal_investigator" value="<?php echo html_encode($PROCESSED["principal_investigator"]); ?>" maxlength="255" style="width: 95%" /></td>
			</tr>
			<tr>
				<td></td>
				<td style="vertical-align: top"><label for="co_investigator_list" class="form-nrequired">Co-Investigator(s)</label></td>
				<td><textarea id="co_investigator_list" name="co_investigator_list" style="width: 95%" rows="4"><?php echo html_encode($PROCESSED["co_investigator_list"]); ?></textarea></td>
			</tr>
			<tr>
				<td></td>
				<td style="vertical-align: top"><label for="amount_received" class="form-nrequired">Amount Received / Requested</label></td>
				<td><input type="text" id="amount_received" name="amount_received" value="<?php echo html_encode($PROCESSED["amount_received"]); ?>" maxlength="18" style="width: 175px" />
				<div id="amount_note" class="content-small" style="display: inline;"> <br />
					For Funded research grants, report only the portion of the total grant received for the reporting year. <br />
					For Submitted research grants, report the total grant applied for.<br />
					For Unfunded research grants, report n/a.
				</div>
				</td>
			</tr>
			<tr>
				<td colspan="3">&nbsp;</td>
			</tr>
			<tr>
				<td></td>
				<td><label for="start_month" class="form-required">Start (MMYYYY)</label></td>
				<td><select name="start_month" id="start_month" style="vertical-align: middle">
				<option value = ""></option>
				<?php					
					for($i=1; $i<13; $i++)
					{
						echo "<option value=\"".$i."\"".(($PROCESSED["start_month"] == $i) ? " selected=\"selected\"" : "").">".$i."</option>\n";
					}
					echo "</select> /&nbsp
					<select name=\"start_year\" id=\"start_year\" style=\"vertical-align: middle\">
					<option value = \"\"></option>";
					
					for($i=$AR_PAST_YEARS; $i<$AR_FUTURE_YEARS; $i++)
					{
						echo "<option value=\"".$i."\"".(($PROCESSED["start_year"] == $i) ? " selected=\"selected\"" : "").">".$i."</option>\n";
					}
					echo "</select>";
				?>
				</td>
			</tr>
			<tr>
				<td></td>
				<td><label for="end_month" class="form-nrequired">End (MMYYYY)</label></td>
				<td><select name="end_month" id="end_month" style="vertical-align: middle">
				<option value = ""></option>
				<?php
					for($i=1; $i<13; $i++)
					{
						echo "<option value=\"".$i."\"".(($PROCESSED["end_month"] == $i) ? " selected=\"selected\"" : "").">".$i."</option>\n";
					}
					echo "</select> /&nbsp
					<select name=\"end_year\" id=\"end_year\" style=\"vertical-align: middle\">
					<option value = \"\"></option>";
					
					for($i=$AR_PAST_YEARS; $i<$AR_FUTURE_YEARS; $i++)
					{
						echo "<option value=\"".$i."\"".(($PROCESSED["end_year"] == $i) ? " selected=\"selected\"" : "").">".$i."</option>\n";
					}
					echo "</select>";
				?>
				</td>
			</tr>			
			<tr>
				<td colspan="3">&nbsp;</td>
			</tr>
			<tr>
				<td></td>
				<td style="vertical-align: top"><label for="funding_status" class="form-required">Funding Status</label></td>
				<td>
				<?php
					if($PROCESSED["funding_status"] == "funded")
					{
						echo "<input type=\"radio\" id=\"funding_status\" name=\"funding_status\" value=\"funded\"/ CHECKED> Funded<br>
						<input type=\"radio\" id=\"funding_status\" name=\"funding_status\" value=\"submitted\"/> Submitted<br>
						<input type=\"radio\" id=\"funding_status\" name=\"funding_status\" value=\"unfunded\"/> Unfunded<br>";
					}
					elseif($PROCESSED["funding_status"] == "submitted")
					{
						echo "<input type=\"radio\" id=\"funding_status\" name=\"funding_status\" value=\"funded\"/> Funded<br>
						<input type=\"radio\" id=\"funding_status\" name=\"funding_status\" value=\"submitted\"/ CHECKED> Submitted<br>
						<input type=\"radio\" id=\"funding_status\" name=\"funding_status\" value=\"unfunded\"/> Unfunded<br>";
					}
					elseif($PROCESSED["funding_status"] == "unfunded")
					{
						echo "<input type=\"radio\" id=\"funding_status\" name=\"funding_status\" value=\"funded\"/> Funded<br>
						<input type=\"radio\" id=\"funding_status\" name=\"funding_status\" value=\"submitted\"/> Submitted<br>
						<input type=\"radio\" id=\"funding_status\" name=\"funding_status\" value=\"unfunded\"/ CHECKED> Unfunded<br>";
					}
					else
					{
						echo "<input type=\"radio\" id=\"funding_status\" name=\"funding_status\" value=\"funded\"/> Funded<br>
						<input type=\"radio\" id=\"funding_status\" name=\"funding_status\" value=\"submitted\"/> Submitted<br>
						<input type=\"radio\" id=\"funding_status\" name=\"funding_status\" value=\"unfunded\"/> Unfunded<br>";
					}
				?>
				</td>
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
							<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/annualreport/research'" />
						</td>
						<td style="width: 75%; text-align: right; vertical-align: middle">
							<span class="content-small">After saving:</span>
							<select id="post_action" name="post_action">							
							<option value="new"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "add") ? " selected=\"selected\"" : ""); ?>>Add More Research</option>
							<option value="index"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "index") ? " selected=\"selected\"" : ""); ?>>Return to Research list</option>
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