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
	$PAGE_META["title"]			= "Add Invited Lectures / Conference Papers";
	$PAGE_META["description"]	= "Invited Lectures / Conference Papers portion of your annual report should be entered / located here.";
	$PAGE_META["keywords"]		= "";
	
	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/annualreport/research?section=add_conference_papers", "title" => "Add Invited Lectures / Conference Papers");
	
	// This grid should be expanded upon redirecting back to the education index.
	$_SESSION["research_expand_grid"] = "conference_papers_grid";
	
	echo "<h1>Add Invited Lectures / Conference Papers</h1>";
	// Error Checking
	switch($STEP) {
		case 2 :			
			/**
			 * Required field "lectures_papers_list" / Invited Lectures / Conference Papers
			 */
			if((isset($_POST["lectures_papers_list"])) && ($lectures_papers_list = clean_input($_POST["lectures_papers_list"], array("notags", "trim")))) {
				$PROCESSED["lectures_papers_list"] = $lectures_papers_list;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Invited Lectures / Conference Papers</strong> field is required.";
			}
			/**
			 * Required field "status" / Status
			 */
			if((isset($_POST["status"])) && ($status = clean_input($_POST["status"], array("notags", "trim")))) {
				$PROCESSED["status"] = $status;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Status</strong> field is required.";
			}
			/**
			 * Required field "institution" / Institution.
			 */
			if((isset($_POST["institution"])) && ($institution = clean_input($_POST["institution"], array("notags", "trim")))) {
				$PROCESSED["institution"] = $institution;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Institution</strong> field is required.";
			}
			/**
			 * Required field "countries_id" / Country.
			 */
			if ((isset($_POST["countries_id"])) && ($countries_id = clean_input($_POST["countries_id"], "int"))) {
				$PROCESSED["countries_id"] = $countries_id;
				//Province is required if the `countries_id` has provinces related to it in the database.
				$query = "	SELECT count(`province`) FROM `global_lu_provinces`
							WHERE `country_id` = ".$db->qstr($PROCESSED["countries_id"])."
							GROUP BY `country_id`";
				$province_required = ($db->GetOne($query) ? true : false);
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Country</strong> field is required.";
				$province_required = false;
			}
			/**
			 * Required field "city" / City.
			 */
			if ((isset($_POST["city"])) && ($city = clean_input($_POST["city"], array("notags", "trim"))) && strpos($city, ",") === false) {
				$PROCESSED["city"] = $city;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>City</strong> field is required, and should not contain either the province/state or any commas.";
			}
			/**
			 * Required field "prov_state" / Prov / State.
			 */
			if ((isset($_POST["prov_state"])) && ($prov_state = clean_input($_POST["prov_state"], array("notags", "trim")))) {
				$PROCESSED["prov_state"] = htmlentities($prov_state);
				if (strlen($prov_state) > 100)
				{
					$ERROR++;
					$ERRORSTR[] = "The <strong>Prov / State</strong> can only contain a maximum of 100 characters.";
				}
			} elseif ($province_required) {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Prov / State</strong> field is required.";
			}
			/**
			 * Required field "type" / Type.
			 */
			if((isset($_POST["type"])) && ($type = clean_input($_POST["type"], array("notags", "trim")))) {
				$PROCESSED["type"] = $type;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Type</strong> field is required.";
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
				
				if($db->AutoExecute("ar_conference_papers ", $PROCESSED, "INSERT")) {
					$EVENT_ID = $db->Insert_Id();
						switch($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
							case "new" :
								$url = ENTRADA_URL."/annualreport/research?section=add_conference_papers";
								$msg	= "You will now be redirected to add another new record; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
							break;
							case "index" :
							default :
								$url = ENTRADA_URL."/annualreport/research#conference_papers";
								$msg	= "You will now be redirected to the research page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
							break;
						}

						$SUCCESS++;
						$SUCCESSSTR[]  = "You have successfully added information pertaining to location Invited Lecture / Conference Paper <strong>".html_encode($PROCESSED["lectures_papers_list"])."</strong> to the system.<br /><br />".$msg;
						$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000);";

						application_log("success", "Invited Lectures / Conference Papers [".$EVENT_ID."] added to the system.");					

				} else {
					$ERROR++;
					$ERRORSTR[] = "There was a problem inserting this record into the system. The MEdIT Unit was informed of this error; please try again later.";

					application_log("error", "There was an error inserting an Invited Lecture / Conference Paper record. Database said: ".$db->ErrorMsg());
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
			$HEAD[]			= "<script type=\"text/javascript\">
			var updater = null;
			function provStateFunction(countries_id)
			{	
				var url='".webservice_url("clerkship_prov")."';
				url=url+'?countries_id='+countries_id+'&prov_state=".rawurlencode((isset($_POST["prov_state"]) ? clean_input($_POST["prov_state"], array("notags", "trim")) : $PROCESSED["prov_state"]))."';
		    	new Ajax.Updater($('prov_state_div'), url, 
					{ 
						method:'get',
						onComplete: function () {
							generateAutocomplete();
							if ($('prov_state').selectedIndex || $('prov_state').selectedIndex === 0) {
								$('prov_state_label').removeClassName('form-nrequired');
								$('prov_state_label').addClassName('form-required');
							} else {
								$('prov_state_label').removeClassName('form-required');
								$('prov_state_label').addClassName('form-nrequired');
							}
						}
					});
			}
			
			function generateAutocomplete() {
				if (updater != null) {
					updater.url = '".ENTRADA_URL."/api/cities-by-country.api.php?countries_id='+$('countries_id').options[$('countries_id').selectedIndex].value+'&prov_state='+($('prov_state') !== null ? ($('prov_state').selectedIndex || $('prov_state').selectedIndex === 0 ? $('prov_state').options[$('prov_state').selectedIndex].value : $('prov_state').value) : '');
				} else {
					updater = new Ajax.Autocompleter('city', 'city_auto_complete', 
						'".ENTRADA_URL."/api/cities-by-country.api.php?countries_id='+$('countries_id').options[$('countries_id').selectedIndex].value+'&prov_state='+($('prov_state') !== null ? ($('prov_state').selectedIndex || $('prov_state').selectedIndex === 0 ? $('prov_state').options[$('prov_state').selectedIndex].value : $('prov_state').value) : ''), 
						{
							frequency: 0.2, 
							minChars: 2
						});
				}
			}
			</script>\n";
			$ONLOAD[]		= "provStateFunction(\$F($('addConferencePaperForm')['countries_id']))";
			?>
			<form id="addConferencePaperForm" action="<?php echo ENTRADA_URL; ?>/annualreport/research?section=add_conference_papers&amp;step=2" method="post">
			<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Adding Invited Lectures / Conference Papers">
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
				<td style="vertical-align: top"><label for="lectures_papers_list" class="form-required">Invited Lectures / Conference Papers</label></td>
				<td><textarea id="lectures_papers_list" name="lectures_papers_list" style="width: 95%" rows="4"><?php echo html_encode($PROCESSED["lectures_papers_list"]); ?></textarea></td>
			</tr>									
			<tr>
				<td></td>
				<td style="vertical-align: top"><label for="status" class="form-required">Status</label></td>				
				<td><select name="status" id="status" style="vertical-align: middle">
				<option value=""></option>
				<?php
					$statusArray = getPulicationStatuses();
					foreach($statusArray as $statusValue) {
						echo "<option value=\"".$statusValue["publication_status"]."\"".(($PROCESSED["status"] == $statusValue["publication_status"]) ? " selected=\"selected\"" : "").">".html_encode($statusValue["publication_status"])."</option>\n";
					}
					echo "</select>";
				?>
				</td>				
			</tr>
			<tr>
				<td></td>
				<td style="vertical-align: top"><label for="institution" class="form-required">Institution</label></td>
				<td><input type="text" id="institution" name="institution" value="<?php echo html_encode($PROCESSED["institution"]); ?>" maxlength="255" style="width: 95%" /></td>
			</tr>
			<tr>
				<td></td>
				<td><label for="countries_id" class="form-required">Country</label></td>
				<td>
					<?php
						if (@count($countries = fetch_countries()) > 0) {
							echo "<select id=\"countries_id\" name=\"countries_id\" style=\"width: 90%\" onchange=\"provStateFunction(this.value);\">\n";
							echo "<option value=\"0\"".((!isset($PROCESSED["countries_id"])) ? " selected=\"selected\"" : "").">-- Country --</option>\n";
							foreach ($countries as $value) {
								echo "<option value=\"".(int) $value["countries_id"]."\"".(($PROCESSED["countries_id"] == $value["countries_id"]) ? " selected=\"selected\"" : (!isset($PROCESSED["countries_id"]) && $value["countries_id"] == DEFAULT_COUNTRY_ID) ? " selected=\"selected\"" : "").">".html_encode($value["country"])."</option>\n";
							}
							echo "</select>\n";
							} else {
								echo "<input type=\"hidden\" id=\"countries_id\" name=\"countries_id\" value=\"0\" />\n";
								echo "Country Information Not Available\n";
							}
					?>
				</td>
			</tr>
			<tr>
				<td></td>
				<td><label id="prov_state_label" for="prov_state_div" class="form-required">Prov / State</label></td>
				<td>
					<div id="prov_state_div" style="display: inline">Select a Country above</div>
				</td>
			</tr>
			<tr>
				<td></td>
				<td><label for="city" class="form-required">City</label></td>
				<td>
					<input type="text" id="city" name="city" size="100" autocomplete="off" style="width: 250px; vertical-align: middle" value="<?php echo $PROCESSED["city"]; ?>"/>
					<script type="text/javascript">
						$('city').observe('keypress', function(event){
							if(event.keyCode == Event.KEY_RETURN) {
								Event.stop(event);
							}
						});
					</script>
					<div class="autocomplete" id="city_auto_complete"></div>
				</td>
			</tr>
			<tr>
				<td></td>
				<td><label for="type" class="form-required">Type</label></td>
				<td><select name="type" id="type" style="vertical-align: middle">
				<option value=""></option>
				<?php
					$paperTypeArray = getConferencePaperTypes();
					foreach($paperTypeArray as $paperTypeValue) {
						echo "<option value=\"".$paperTypeValue["conference_paper_type"]."\"".(($PROCESSED["type"] == $paperTypeValue["conference_paper_type"]) ? " selected=\"selected\"" : "").">".html_encode($paperTypeValue["conference_paper_type"])."</option>\n";
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
							<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/annualreport/research/#location'" />
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