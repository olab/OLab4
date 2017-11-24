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
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CLERKSHIP"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('logbook', 'read')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	if (isset($_GET["entry_id"]) && (clean_input($_GET["entry_id"], "int"))) {
		$RECORD_ID = clean_input($_GET["entry_id"], "int");
	}
	if ($RECORD_ID) {
		$PROCESSED = $db->GetRow("SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_entries` WHERE `lentry_id` = ".$db->qstr($RECORD_ID)." AND `entry_active` = 1");
		if ($PROCESSED) {
			$BREADCRUMB[]	= array("url" => ENTRADA_URL."/clerkship?section=clerk&ids=".$PROCESSED["proxy_id"], "title" => "Clerk Management");
			$BREADCRUMB[]	= array("url" => ENTRADA_URL."/clerkship/logbook?".replace_query(array("section" => "flag")), "title" => "Flag Logbook Entry");
			echo "<h1>Deactivating Clerkship Logbook Entry</h1>\n";
			switch($STEP) {
				case 2 :
					$query = "UPDATE `".CLERKSHIP_DATABASE."`.`logbook_entries` SET `entry_active` = 0 WHERE `lentry_id`=".$db->qstr($RECORD_ID);
					if ($db->Execute($query)) {
						$url = ENTRADA_URL."/clerkship?section=clerk&ids=".$PROCESSED["proxy_id"];
						
						$msg	= " You will now be redirected to the clerkship logbook index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
						
						$SUCCESS++;
						$SUCCESSSTR[]  	= "You have successfully flagged this <strong>Patient Encounter</strong> in the system.<br /><br />".$msg;
						$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000)";
						
						application_log("success", "Logbook entry [".$RECORD_ID."] deactivated in the system.");
						
						echo display_success();
					}
					break;
				default:
					$PROCESSED_OBJECTIVES = $db->GetAll("SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_objectives` WHERE `lentry_id` = ".$db->qstr($RECORD_ID));
					$PROCESSED_PROCEDURES = $db->GetAll("SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_procedures` WHERE `lentry_id` = ".$db->qstr($RECORD_ID));
					?>
					<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Adding Patient Encounter">
					<colgroup>
						<col style="width: 3%" />
						<col style="width: 20%" />
						<col style="width: 77%" />
					</colgroup>
					<tfoot>
						<tr>
							<td colspan="3" style="padding-top: 25px">
								<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
								<tr>
									<td style="width: 25%; text-align: left">
										<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/clerkship?section=clerk&ids=<?php echo $PROCESSED["proxy_id"]; ?>'" />
									</td>
									<td style="width: 75%; text-align: right; vertical-align: middle">
										<form action="<?php echo ENTRADA_URL ?>/clerkship/logbook?section=flag&<?php echo replace_query(array ("step" => 2)); ?>" method="POST">
											<input type="hidden" value="<?php echo $RECORD_ID; ?>" name="entry_id" />
											<input type="submit" class="btn btn-danger" value="Confirm Deactivate" />
										</form>
									</td>
								</tr>
								</table>
							</td>
						</tr>
					</tfoot>
					<tbody>
						<tr>
							<td colspan="3"><h2>Encounter Details</h2></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>Encounter Date</td>
							<td>
							<?php
								echo  date("F d, Y, g:i a", $PROCESSED["encounter_date"]);
							?>
							</td>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td></td>
							<td><label for="rotation_id" class="form-nrequired">Rotation</label></td>
							<td>
								<?php 
								$query	= "SELECT a.* FROM `".CLERKSHIP_DATABASE."`.`events` AS a LEFT JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS b ON a.`event_id` = b.`event_id` WHERE b.`etype_id` = ".$db->qstr($PROCESSED["proxy_id"])." AND a.`event_id` = ".$db->qstr(((int)$PROCESSED["rotation_id"]));
								$found	= $db->GetRow($query);
								$rotation_title = $found["event_title"];
								?>
								<?php
								if ($found && isset($rotation_title) && $rotation_title) {
									echo "<div id=\"rotation-title\" style=\"width: 95%\"><span>".$rotation_title."</span></div>";
								}
								?>
							</td>
						</tr>
						<tr>
							<td></td>
							<td><label for="institution_id" class="form-nrequired">Institution</label></td>
							<td>
								<?php
								$query		= "SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_sites` WHERE `site_type` = ".$db->qstr(CLERKSHIP_SITE_TYPE)." AND `lsite_id` = ".$db->qstr($PROCESSED["lsite_id"]);
								$result	= $db->GetRow($query);
								if ($result) {
									echo $result["site_name"];
								}
								?>
							</td>
						</tr>
						<tr>
							<td></td>
							<td><label for="llocation_id" class="form-nrequired">Setting</label></td>
							<td>
								<?php
								$query	= "SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_locations` WHERE `llocation_id` = ".$db->qstr($PROCESSED["llocation_id"]);
								$result	= $db->GetRow($query);
								if ($result) {
									echo $result["location"];
								}
								?>
							</td>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr<?php echo ($PROCESSED["patient_info"] ? "" : " style=\"display: none;\""); ?>>
							<td></td>
							<td><label for="patient_id" class="form-nrequired">Patient ID</label></td>
							<td>
								<?php echo html_encode($PROCESSED["patient_info"]); ?>
							</td>
						</tr>
						<tr>
							<td></td>
							<td><label for="agerange" class="form-nrequired">Patient Age Range</label></td>
							<td>
								<?php
								$query	= "SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_agerange` WHERE `agerange_id` = ".$db->qstr($PROCESSED["agerange_id"]);
								$result	= $db->GetRow($query);
								if ($result) {
									echo $result["age"];
								}
								?>
								</select>
							</td>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="gender" class="form-nrequired">Patient Gender</label></td>
							<td style="vertical-align: top">
								<?php echo (($PROCESSED["gender"]) == "f" ? "Female" : "Male"); ?> 
							</td>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<?php
						if ($PROCESSED["participation_level"]) {
							?>
							<tr>
								<td></td>
								<td style="vertical-align: top"><label for="participation_level" class="form-nrequired">Objective Participation Level</label></td>
								<td style="vertical-align: top">
									<?php echo (($PROCESSED["participation_level"]) == "1" ? "Assisted" : "Participated"); ?> 
								</td>
							</tr>
							<tr>
								<td colspan="3">&nbsp;</td>
							</tr>
							<?php
						}
						?>
						<tr>
							<td colspan="2">&nbsp;</td>
							<td>
								<table class="tableList objectives"<?php echo !is_array($PROCESSED_OBJECTIVES) || !count($PROCESSED_OBJECTIVES) ? " style=\"display: none;\"" : ""; ?> cellspacing="0" cellpadding="0" border="0" id="objective-list">
								<colgroup>
									<col style="width: 4%" />
									<col style="width: 96%" />
								</colgroup>
								<thead>
									<tr>
										<td colspan="2" style="padding-left: 25px;">
											Learning Objectives
										</td>
									</tr>
								</thead>
								<tbody id="objective-list">
								<?php 
								if (is_array($PROCESSED_OBJECTIVES) && count($PROCESSED_OBJECTIVES)) { 
									foreach ($PROCESSED_OBJECTIVES as $objective_id) {
										$query = "SELECT * FROM `global_lu_objectives` AS a
													JOIN `objective_organisation` AS b
													ON a.`objective_id` = b.`objective_id`
													AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
													WHERE a.`objective_id` = ".$db->qstr($objective_id["objective_id"])." 
													AND a.`objective_active` = '1'
													AND 
													(
														a.`objective_parent` = '200' 
														OR a.`objective_parent` IN 
														(
															SELECT `objective_id` FROM `global_lu_objectives` 
															WHERE `objective_parent` = '200'
															AND `objective_active` = '1'
														)
													)";
										$objective = $db->GetRow($query);
										if ($objective) {
										?>
											<tr id="objective_<?php echo $objective_id["objective_id"]; ?>_row">
												<td>&nbsp;</td>
												<td><label for="delete_objective_<?php echo $objective_id["objective_id"]; ?>"><?php echo $objective["objective_name"]?></label></td>
											</tr>
										<?php 
										}
									}
								} 
								?>
								</tbody>
								</table>
							</td>
						</tr>
						<tr>
							<td colspan="2">&nbsp;</td>
							<td>
								<table class="tableList procedures"<?php echo !is_array($PROCESSED_PROCEDURES) || !count($PROCESSED_PROCEDURES) ? " style=\"display: none;\"" : ""; ?> cellspacing="0" cellpadding="0" border="0" id="procedure-list">
								<colgroup>
									<col style="width: 4%" />
									<col style="width: 61%" />
									<col style="width: 35%" />
								</colgroup>
								<thead>
									<tr>
										<td colspan="3" style="padding-left: 25px;">
											Clinical Procedures
										</td>
									</tr>
								</thead>
								<tbody id="procedure-list">
								<?php 
								if (is_array($PROCESSED_PROCEDURES) && count($PROCESSED_PROCEDURES)) { 
									foreach ($PROCESSED_PROCEDURES as $procedure_id) {
										$procedure = $db->GetRow("SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_procedures` WHERE `lprocedure_id` = ".$db->qstr($procedure_id["lprocedure_id"]));
										if ($procedure) {
										?>
											<tr id="procedure_<?php echo $procedure_id["lprocedure_id"]; ?>_row">
												<td>&nbsp;</td>
												<td class="left"><label for="delete_procedure_<?php echo $procedure_id["lprocedure_id"]; ?>"><?php echo $procedure["procedure"]?></label></td>
												<td style="text-align: right">
													<?php echo ($procedure_id["level"] == 1 || (!$procedure_id["level"]) ? "Observed" : ($procedure_id["level"] == 2 ? "Performed with help" : "Performed independently")); ?>
													<?php //echo ($procedure_id["level"] == 1 || (!$procedure_id["level"]) ? "Performed with help" : "Performed independently"); ?>
												</td>
											</tr>
										<?php 
										}
									}
								} 
								?>
								</tbody>
								</table>
							</td>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="reflection" class="form-nrequired">Reflection</label></td>
							<td>
								<div id="reflection" class="reflection" style="width: 95%"><?php echo ((isset($PROCESSED["reflection"]) && $PROCESSED["reflection"]) ? html_encode($PROCESSED["reflection"]) : "<span class=\"content-small\">No reflection on learning experience recorded for this entry.</span>"); ?></div>
							</td>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td></td>
							<td style="vertical-align: top"><label for="comments" class="form-nrequired">Additional comments </label></td>
							<td>
								<div id="comments" class="comments" style="width: 95%"><?php echo ((isset($PROCESSED["comments"]) && $PROCESSED["comments"]) ? html_encode($PROCESSED["comments"]) : "<span class=\"content-small\">No comments recorded for this entry.</span>"); ?></div>
							</td>
						</tr>			
					</tbody>
					</table>
					<?php
				break;
			}
		} else {
			$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

			$ERROR++;
			$ERRORSTR[]	= "This Entry ID is not valid<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";
		
			echo display_error();
		
			application_log("error", "Error, invalid Entry ID [".$RECORD_ID."] supplied for deleting a clerkship logbook entry in module [".$MODULE."].");
		}
	} else {
		$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

		$ERROR++;
		$ERRORSTR[]	= "You must provide a valid Entry ID<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";
	
		echo display_error();
	
		application_log("error", "Error, invalid Entry ID [".$RECORD_ID."] supplied for clerkship logbook entry in module [".$MODULE."].");
	}
}
