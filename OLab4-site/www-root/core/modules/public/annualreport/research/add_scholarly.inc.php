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
	$PAGE_META["title"]			= "Add Scholarly Research Activity";
	$PAGE_META["description"]	= "Research portion of your annual report should be entered / located here.";
	$PAGE_META["keywords"]		= "";
	
	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/annualreport/research?section=add_scholarly", "title" => "Add Scholarly Research Activity");
	
	// This grid should be expanded upon redirecting back to the education index.
	$_SESSION["research_expand_grid"] = "scholarly_grid";
	
	echo "<h1>Add Scholarly Research Activity</h1>";
	switch($STEP) {
		case 2 :
			$ENDERROR = false;
			/**
			 * Required field "scholarly_activity_type" / Scholarly Activity Type.
			 */			
			if((isset($_POST["scholarly_activity_type"])) && ($scholarly_activity_type = clean_input($_POST["scholarly_activity_type"], array("notags", "trim")))) {
				$PROCESSED["scholarly_activity_type"] = $scholarly_activity_type;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Scholarly Activity Type</strong> field is required.";
			}
			/**
			 * Required field "description" / Description.
			 */
			if((isset($_POST["description"])) && ($description = clean_input($_POST["description"], array("notags", "trim")))) {
				$PROCESSED["description"] = $description;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Description</strong> field is required.";
			}
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
				
				if($db->AutoExecute("ar_scholarly_activity", $PROCESSED, "INSERT")) {
						$EVENT_ID = $db->Insert_Id();
						switch($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
							case "new" :
								$url = ENTRADA_URL."/annualreport/research?section=add_scholarly";
								$msg	= "You will now be redirected to add another record; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
							break;
							case "index" :
							default :
								$url = ENTRADA_URL."/annualreport/research#other_scholarly_activity";
								$msg	= "You will now be redirected to the research page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
							break;
						}

						$SUCCESS++;
						$SUCCESSSTR[]  = "You have successfully added <strong>".html_encode($PROCESSED["description"])."</strong> to the system.<br /><br />".$msg;
						$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000);";

						application_log("success", "New Scholarly Activity [".$EVENT_ID."] added to the system.");					

				} else {
					$ERROR++;
					$ERRORSTR[] = "There was a problem inserting this event into the system. The MEdIT Unit was informed of this error; please try again later.";

					application_log("error", "There was an error inserting a Scholarly Activity. Database said: ".$db->ErrorMsg());
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
			$HEAD[] = "<link href=\"".WEBSITE_URL."/javascript/calendar/css/xc2_default.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
			$HEAD[] = "<script language=\"javascript\" type=\"text/javascript\" src=\"".WEBSITE_URL."/javascript/calendar/config/xc2_default.js\"></script>\n";
			$HEAD[] = "<script language=\"javascript\" type=\"text/javascript\" src=\"".WEBSITE_URL."/javascript/calendar/script/xc2_inpage.js\"></script>\n";
			$HEAD[] = "<script language=\"javascript\" type=\"text/javascript\" src=\"".WEBSITE_URL."/javascript/calendar/script/xc2_timestamp.js\"></script>\n";
			
			if($ERROR) {
				echo display_error();
			}
			?>
			<form action="<?php echo ENTRADA_URL; ?>/annualreport/research?section=add_scholarly&amp;step=2" method="post">
			<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Adding Scholarly Activity">
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
				<td><label for="scholarly_activity_type" class="form-required">Type Of Activity</label></td>
				<td>
					<?php
						echo "<select id=\"scholarly_activity_type\" name=\"scholarly_activity_type\" style=\"width: 95%\">\n
						<option value=\"\"></option>";
						$scholarlyTypeArray = getScholarlyTypes();
						foreach($scholarlyTypeArray as $scholarly_activity_type) {
							echo "<option value=\"".$scholarly_activity_type["scholarly_type"]."\"".(($PROCESSED["scholarly_activity_type"] == $scholarly_activity_type["scholarly_type"]) ? " selected=\"selected\"" : "").">".html_encode($scholarly_activity_type["scholarly_type"])."</option>\n";
						}
						echo "</select>\n";
					?>
				</td>
			</tr>
			<tr>
				<td colspan="3">&nbsp;</td>
			</tr>
			<tr>
				<td></td>
				<td style="vertical-align: top"><label for="description" class="form-required">Description</label></td>
				<td><input type="text" id="description" name="description" value="<?php echo html_encode($PROCESSED["description"]); ?>" maxlength="255" style="width: 95%" /></td>
			</tr>
			<tr>
				<td colspan="3">&nbsp;</td>
			</tr>
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
							<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/annualreport/research/'" />
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