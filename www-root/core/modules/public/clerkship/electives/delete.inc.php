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
 * Allows students to delete an elective in the system if it has not yet been approved.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Andrew Dos-Santos <andrew.dos-santos@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CLERKSHIP"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('clerkship', 'read')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	if ((isset($_GET["id"])) && ((int) trim($_GET["id"]))) {
		$EVENT_ID = (int) trim($_GET["id"]);
	}

	if ($EVENT_ID) {
		
		$nameArray 	= clerkship_student_name($EVENT_ID);
		
		$query		= "SELECT *
					FROM `".CLERKSHIP_DATABASE."`.`events`, `".CLERKSHIP_DATABASE."`.`electives`, `".CLERKSHIP_DATABASE."`.`event_contacts`
					WHERE `".CLERKSHIP_DATABASE."`.`events`.`event_id` = ".$db->qstr($EVENT_ID)."
					AND `".CLERKSHIP_DATABASE."`.`events`.`event_id` = `".CLERKSHIP_DATABASE."`.`electives`.`event_id`
					AND `".CLERKSHIP_DATABASE."`.`events`.`event_id` = `".CLERKSHIP_DATABASE."`.`event_contacts`.`event_id`
					AND `".CLERKSHIP_DATABASE."`.`event_contacts`.`etype_id` = ".$db->qstr($ENTRADA_USER->getActiveId());
		
		if ($event_info	= $db->GetRow($query)) 
		{
			$query = "	SELECT `countries_id`, `prov_state`, `region_name`
						FROM `".CLERKSHIP_DATABASE."`.`regions`
						WHERE `region_id` = ".$event_info["region_id"];
			$event_info["prov_state"] 	= "";
			$event_info["countries_id"] = 1;
			$event_info["city"] 		= "";
			if ($region_info = $db->GetRow($query)) {
				$event_info["prov_state"] = $region_info["prov_state"];
				$event_info["countries_id"] = $region_info["countries_id"];
				$event_info["city"] = $region_info["region_name"];
			}
			$PROCESSED = $event_info;
			if ($event_info["event_status"] == "approval") {
				$cancel	  = "Cancel";
				$BREADCRUMB[]	= array("url" => ENTRADA_URL."/public/clerkship/electives?".replace_query(array("section" => "edit")), "title" => "Canceling Elective Approval Request");
				$header_output = "<h1>Canceling Elective Approval Request</h1>\n";
				
				switch($STEP) {
					case 2 :
						$query = "UPDATE `".CLERKSHIP_DATABASE."`.`events` SET `event_status` = 'trash' WHERE `event_id`=".$db->qstr($EVENT_ID);
						if ($db->Execute($query)) {
                            $url = ENTRADA_URL."/clerkship/electives";

                            $msg	= " You will now be redirected to the clerkship index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";

                            $SUCCESS++;
                            $SUCCESSSTR[]  	= "You have successfully cancelled your request for this <strong>".html_encode($PROCESSED["geo_location"])."</strong> elective in the system.<br /><br />".$msg;
                            $ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000)";

							application_log("success", "User [".$ENTRADA_USER->getActiveId()."] removed their own elective [".$EVENT_ID."] from the system.");

                            echo display_success();
						}
						break;
					default:
					?>
					<div class="display-notice" style="vertical-align: middle; padding: 15px;">
						<strong>Confirmation:</strong> Are you sure you want to cancel this request? <input type="button" class="btn btn-danger" value="Confirm" style="margin-left: 15px" onclick="window.location='<?php echo ENTRADA_URL; ?>/clerkship/electives?section=delete&step=2&id=<?php echo $EVENT_ID; ?>'" />
					</div>
					<?php
					$student_name	= get_account_data("firstlast", $event_info["etype_id"]);
					echo $header_output;
					?>
					<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Viewing Elective">
						<colgroup>
							<col style="width: 25%" />
							<col style="width: 75%" />
						</colgroup>
						<tfoot>
							<tr>
								<td style="width: 25%; text-align: left">
									<input type="button" class="btn" value="Back" onclick="window.location='<?php echo ENTRADA_URL; ?>/clerkship/electives?section=edit&id=<?php echo $EVENT_ID; ?>/'" />
								</td>
								<td style="width: 75%; text-align: right">&nbsp;</td>
							</tr>
						</tfoot>
						<tbody>
						<tr>
							<td colspan="2"><h2>Elective Details</h2></td>
						</tr>
						<tr>
							<td style="width: 25%">Geographic Location</td>
							<td style="width: 75%"><?php echo $PROCESSED["geo_location"]; ?></td>
						</tr>
						<tr>
							<td style="width: 25%">Elective Period</td>
							<td style="width: 75%"><?php echo $PROCESSED["event_title"]; ?></td>
						</tr>
						<tr>
							<td style="width: 25%">Elective Department</td>
							<?php
							$query		= "	SELECT `category_name`
							FROM `".CLERKSHIP_DATABASE."`.`categories`
							WHERE `category_id` = ".$db->qstr($PROCESSED["department_id"]);
							
							$result	= $db->GetRow($query);
							
							?>
							<td style="width: 75%"><?php echo $result["category_name"]; ?></td>
						</tr>
						<tr>
							<td style="width: 25%">Elective Discipline</td>
							<td style="width: 75%"><?php echo clerkship_fetch_specific_discipline($PROCESSED["discipline_id"]); ?></td>
						</tr>
						<tr>
							<td style="width: 25%">Sub-Discipline</td>
							<td style="width: 75%"><?php echo (!isset($PROCESSED["sub_discipline"]) || $PROCESSED["sub_discipline"] == "" ? "N/A" : $PROCESSED["sub_discipline"]); ?></td>
						</tr>
						<tr>
							<td style="width: 25%">Host School</td>
							<td style="width: 75%"><?php echo clerkship_fetch_specific_school($PROCESSED["schools_id"]); ?></td>
						</tr>
						<?php
							if(isset($PROCESSED["other_medical_school"]) && $PROCESSED["other_medical_school"] != "") {
								?>
								<tr>
									<td style="width: 25%">Other School</td>
									<td style="width: 75%"><?php echo $PROCESSED["other_medical_school"]; ?></td>
								</tr>
								<?php
							}
						?>
						<tr>
							<td colspan="2">&nbsp;</td>
						</tr>
						<tr>
							<td style="width: 25%">Start Date</td>
							<td style="width: 75%"><?php echo date("Y-m-d", $PROCESSED["event_start"]); ?></td>
						</tr>
						<tr>
							<td style="width: 25%">End Date</td>
							<td style="width: 75%"><?php echo date("Y-m-d", $PROCESSED["event_finish"]); ?></td>
						</tr>
						<?php 
							$duration = ceil(($PROCESSED["event_finish"] - $PROCESSED["event_start"]) / 604800);
						?>
						<tr>
							<td style="width: 25%">Elective Weeks</td>
							<td style="width: 75%"><?php echo $duration; ?></td>
						</tr>
						<tr>
							<td colspan="2">&nbsp;</td>
						</tr>
						<tr>
							<td style="width: 25%">Planned Experience</td>
							<td style="width: 75%"><?php echo $PROCESSED["objective"]; ?></td>
						</tr>
						<tr>
							<td colspan="2" style="padding-top: 15px"><h2>Preceptor Details</h2></td>
						</tr>
						<tr>
							<td style="width: 25%">Preceptor First Name</td>
							<td style="width: 75%"><?php echo (isset($PROCESSED["preceptor_first_name"]) && $PROCESSED["preceptor_first_name"] != "" ? $PROCESSED["preceptor_first_name"] : "N/A"); ?></td>
						</tr>
						<tr>
							<td style="width: 25%">Preceptor Last Name</td>
							<td style="width: 75%"><?php echo $PROCESSED["preceptor_last_name"]; ?></td>
						</tr>
						<tr>
							<td colspan="2">&nbsp;</td>
						</tr>
						<tr>
							<td style="width: 25%">Country</td>
							<td style="width: 75%"><?php echo fetch_specific_country($PROCESSED["countries_id"]); ?></td>
						</tr>
						<tr>
							<td style="width: 25%">Province</td>
							<td style="width: 75%"><?php echo $PROCESSED["prov_state"]; ?></td>
						</tr>
						<tr>
							<td style="width: 25%">City</td>
							<td style="width: 75%"><?php echo $PROCESSED["city"]; ?></td>
						</tr>
						<tr>
							<td style="width: 25%">Address</td>
							<td style="width: 75%"><?php echo $PROCESSED["address"]; ?></td>
						</tr>
						<tr>
							<td style="width: 25%">Postal / Zip Code</td>
							<td style="width: 75%"><?php echo $PROCESSED["postal_zip_code"]; ?></td>
						</tr>
						
						<tr>
							<td colspan="2">&nbsp;</td>
						</tr>
						<tr>
							<td style="width: 25%">Phone</td>
							<td style="width: 75%"><?php echo $PROCESSED["phone"]; ?></td>
						</tr>
						<tr>
							<td style="width: 25%">Fax</td>
							<td style="width: 75%"><?php echo $PROCESSED["fax"]; ?></td>
						</tr>
						<tr>
							<td style="width: 25%">Email</td>
							<td style="width: 75%"><?php echo $PROCESSED["email"]; ?></td>
						</tr>
						<tr>
							<td colspan="2">&nbsp;</td>
						</tr>
					</tbody>
					</table>
					<?php
					break;
				}
			} else {
				$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

				$ERROR++;
				$ERRORSTR[]	= "You cannot cancel this request as it has already been acted upon by the Administrators of this sytem.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";
			
				echo display_error();
			
				application_log("error", "Error, invalid Event ID [".$EVENT_ID."] supplied for clerkship elective in module [".$MODULE."].");
			}
		} else {
			$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

			$ERROR++;
			$ERRORSTR[]	= "This Event ID is not valid<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";
		
			echo display_error();
		
			application_log("error", "Error, invalid Event ID [".$EVENT_ID."] supplied for canceling a clerkship elective in module [".$MODULE."].");
		}
	} else {
		$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

		$ERROR++;
		$ERRORSTR[]	= "You must provide a valid Event ID<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";
	
		echo display_error();
	
		application_log("error", "Error, invalid Event ID [".$EVENT_ID."] supplied for clerkship elective in module [".$MODULE."].");
	}
}
