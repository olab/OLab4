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
 * Allows students to add electives to the system which still need to be approved.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
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
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	if (isset($_GET["rotation"]) && (clean_input($_GET["rotation"], "int"))) {
		$ROTATION_ID = clean_input($_GET["rotation"], "int");
	} elseif (isset($_POST["id"]) && (clean_input($_POST["id"], "int"))) {
		$ROTATION_ID = clean_input($_POST["rotation"], "int");
	}
	
	if (isset($_GET["id"]) && (clean_input($_GET["id"], "int"))) {
		$query		= "	SELECT a.`rotation_id`, a.`rotation_title`, a.`course_id`, b.`organisation_id`
						FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS a
						LEFT JOIN `".DATABASE_NAME."`.`courses` AS b
						ON a.`course_id` = b.`course_id`
						WHERE a.`rotation_id` = ".$db->qstr($ROTATION_ID)."
						AND b.`course_active` = '1'";
		$result = $db->GetRow($query);
		if ($ENTRADA_ACL->amIAllowed(new CourseContentResource($result["course_id"], $result["organisation_id"]), 'update')) {
			$PROXY_ID = clean_input($_GET["id"], "int");
			$administrator = true;
		} else {
			$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

			$ERROR++;
			$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";
		
			application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
		}
	} elseif (isset($_POST["id"]) && (clean_input($_POST["id"], "int"))) {
		$query		= "	SELECT a.`rotation_id`, a.`rotation_title`, a.`course_id`, b.`organisation_id`
						FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS a
						LEFT JOIN `".DATABASE_NAME."`.`courses` AS b
						ON a.`course_id` = b.`course_id`
						WHERE a.`rotation_id` = ".$db->qstr($ROTATION_ID)."
						AND b.`course_active` = '1'";
		$result = $db->GetAll($query);
		if ($ENTRADA_ACL->amIAllowed(new CourseContentResource($result["course_id"], $result["organisation_id"]), 'update')) {
			$PROXY_ID = clean_input($_POST["id"], "int");
			$administrator = true;
		} else {
			$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

			$ERROR++;
			$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";
		
			application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
		}
	} else {
		$PROXY_ID = $ENTRADA_USER->getID();
		$administrator = false;
	}
	
	$query = "	SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_deficiency_plans`
				WHERE `rotation_id` = ".$db->qstr($ROTATION_ID)."
				AND `proxy_id` = ".$db->qstr($PROXY_ID);
	$PROCESSED = $db->GetRow($query);
	$existing_plan = true;
	if (!$PROCESSED) {
		if (!$administrator) {
			$existing_plan = false;
			$PROCESSED = array();
		} else {
			if (!$ERROR) {
				$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";
				$ERROR++;
				$ERRORSTR[]	= "The clerk you have selected does not have the a deficiency plan completed for this rotation, please try again later.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";
			
				application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
			}
		}
	} elseif ($administrator && !$PROCESSED["clerk_accepted"]) {
		$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";
		$ERROR++;
		$ERRORSTR[]	= "The clerk you have selected does not have the a deficiency plan completed for this rotation, please try again later.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";
	
		application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
	}
	$ROTATION_TITLE = $db->GetOne("	SELECT `rotation_title` FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations`
									WHERE `rotation_id` = ".$db->qstr($ROTATION_ID));
	
	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/clerkship/logbook?section=add", "title" => "Clerkship Log deficiency plan");

	$query = 	"SELECT `lentry_id` 
				FROM `".CLERKSHIP_DATABASE."`.`logbook_entries` 
				WHERE `proxy_id` = ".$db->qstr($PROXY_ID)." 
				AND `entry_active` = '1'";
	$entry_ids = $db->GetAll($query);
	$entry_ids_string = "";
	foreach ($entry_ids as $entry_id) {
		if ($entry_ids_string) {
			$entry_ids_string .= ", ".$db->qstr($entry_id["lentry_id"]);
		} else {
			$entry_ids_string = $db->qstr($entry_id["lentry_id"]);
		}
	}
	
	echo "<h1>Clerkship Log deficiency plan</h1>\n";
	if ((isset($ROTATION_ID)) && ($ROTATION_ID)) {
		$PROCESSED["rotation_id"] = $ROTATION_ID;
	}
	if (!$ERROR) {
		if (!isset($PROCESSED["administrator_accepted"]) || !$PROCESSED["administrator_accepted"] || $administrator) {
			// Error Checking
			switch ($STEP) {
				case 2 :	
				/**
				 * Logic for student submitting plan vs administrator reviewing and commenting on it:
				 */
				if ($administrator) {
					if ((isset($_POST["administrator_comments"])) && ($administrator_comments = clean_input($_POST["administrator_comments"], Array("notags", "trim")))) {
						$PROCESSED["administrator_comments"] = $administrator_comments;
					}
					
					if (isset($PROCESSED["administrator_accepted"]) && ($administrator_accepted = ($_POST["administrator_accepted"] ? true : false))) {
						$PROCESSED["administrator_accepted"] = $administrator_accepted;
					} elseif ((!isset($PROCESSED["administrator_accepted"]) || !$administrator_accepted) && !isset($PROCESSED["administrator_comments"])) {
						$PROCESSED["administrator_accepted"] = false;
						$ERROR++;
						$ERRORSTR[] = "The <strong>Administrator Comments</strong> field is required to detail what is wrong with the clerk's deficiency plan so they may make the appropriate changes.";
					} else {
						$PROCESSED["administrator_accepted"] = false;
						$PROCESSED["clerk_accepted"] = false;
					}
					if (!$ERROR) {
						$PROCESSED["administrator_id"] = $ENTRADA_USER->getID();
						if ($db->AutoExecute("`".CLERKSHIP_DATABASE."`.`logbook_deficiency_plans`", $PROCESSED, "UPDATE", "`ldeficiency_plan_id` = ".$db->qstr($PROCESSED["ldeficiency_plan_id"]))) {
							$PLAN_ID = $PROCESSED["ldeficiency_plan_id"];
							@clerkship_deficiency_notifications($PROXY_ID, $ROTATION_ID, false, $PROCESSED["administrator_accepted"], $PROCESSED["administrator_comments"]);
							$url = ENTRADA_URL;
							$SUCCESS++;
							$SUCCESSSTR[]  	= "You have successfully updated this <strong>Deficiency Plan</strong> in the system.<br /><br />Please <a href=\"".$url."\">click here</a> to proceed to the Dashboard or you will be automatically forwarded in 5 seconds.";
							$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000)";
				
							application_log("success", "Updated deficiency plan [".$PLAN_ID."] in the system.");
						} else {
							$ERROR++;
							$ERRORSTR[] = "There was a problem editing this deficiency plan in the system. The MEdTech Unit was informed of this error; please try again later.";
				
							application_log("error", "There was an error editing a deficiency plan for Proxy ID [".$PROXY_ID."]. Database said: ".$db->ErrorMsg());
						}
					}
				} else {
					if ((isset($_POST["plan_body"])) && ($plan_body = clean_input($_POST["plan_body"], Array("notags", "trim")))) {
						$PROCESSED["plan_body"] = $plan_body;
					}
					
					$timeline_dates = validate_calendars("timeline", true, true);
					if ((isset($timeline_dates["start"])) && ((int) $timeline_dates["start"])) {
						$PROCESSED["timeline_start"] = (int) $timeline_dates["start"];
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Timeline Start</strong> field is required to detail when your plan to attain deficiencies will begin.";
					}
			
					if ((isset($timeline_dates["finish"])) && ((int) $timeline_dates["finish"])) {
						$PROCESSED["timeline_finish"] = (int) $timeline_dates["finish"];
					} else {
						$ERROR++;
						$ERRORSTR[] = "The <strong>Timeline Finish</strong> field is required to detail when your plan to attain deficiencies will end.";
					}
					
					if (!isset($PROCESSED["clerk_accepted"]) || !$PROCESSED["clerk_accepted"]) {
						if (isset($_POST["clerk_accepted"]) && ($clerk_accepted = ($_POST["clerk_accepted"] ? true : false))) {
							$PROCESSED["clerk_accepted"] = $clerk_accepted;
						} else {
							$PROCESSED["clerk_accepted"] = false;
						}
					}
					
					$PROCESSED["rotation_id"] = $ROTATION_ID;
					$PROCESSED["proxy_id"] = $PROXY_ID;
					if (!$existing_plan) {
						if ($db->AutoExecute("`".CLERKSHIP_DATABASE."`.`logbook_deficiency_plans`", $PROCESSED, "INSERT") && $PLAN_ID = $db->Insert_Id()) {
							if ($PROCESSED["clerk_accepted"]) {
								@clerkship_deficiency_notifications($PROXY_ID, $ROTATION_ID, true, false, false);
							}
							$url = ENTRADA_URL."/".$MODULE."/logbook";
							$SUCCESS++;
							$SUCCESSSTR[]  	= "You have successfully created a <strong>Deficiency Plan</strong> in the system.<br /><br />Please <a href=\"".$url."\">click here</a> to proceed to the clerkship logbook index or you will be automatically forwarded in 5 seconds.";
							$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000)";
				
							application_log("success", "New deficiency plan [".$PLAN_ID."] added to the system.");
						} else {
							$ERROR++;
							$ERRORSTR[] = "There was a problem creating this deficiency plan in the system. The MEdTech Unit was informed of this error; please try again later.";
				
							application_log("error", "There was an error creating a deficiency plan for Proxy ID [".$PROXY_ID."]. Database said: ".$db->ErrorMsg());
						}
					} else {
						if ($db->AutoExecute("`".CLERKSHIP_DATABASE."`.`logbook_deficiency_plans`", $PROCESSED, "UPDATE", "`ldeficiency_plan_id` = ".$db->qstr($PROCESSED["ldeficiency_plan_id"]))) {
							if ($PROCESSED["clerk_accepted"]) {
								@clerkship_deficiency_notifications($PROXY_ID, $ROTATION_ID, true, false, false);
							}
							$PLAN_ID = $PROCESSED["ldeficiency_plan_id"];
							$url = ENTRADA_URL;
							$SUCCESS++;
							$SUCCESSSTR[]  	= "You have successfully updated this <strong>Deficiency Plan</strong> in the system.<br /><br />Please <a href=\"".$url."\">click here</a> to proceed to the Dashboard or you will be automatically forwarded in 5 seconds.";
							$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000)";
				
							application_log("success", "Updated deficiency plan [".$PLAN_ID."] in the system.");
						} else {
							$ERROR++;
							$ERRORSTR[] = "There was a problem editing this deficiency plan in the system. The MEdTech Unit was informed of this error; please try again later.";
				
							application_log("error", "There was an error editing a deficiency plan for Proxy ID [".$PROXY_ID."]. Database said: ".$db->ErrorMsg());
						}
					}
				}
				if ($ERROR) {
					$STEP = 1;
				}
				
				break;
				case 1 :
				default :
					continue;
				break;
			}
			
			// Display Content
			switch ($STEP) {
				case 2 :
					if ($SUCCESS) {
						echo display_success();
					}
							
					if ($NOTICE) {
						echo display_notice();
					}
							
					if ($ERROR) {
						echo display_error();
					}
				break;
				case 1 :
				default :
					
					$HEAD[] 		= "<link href=\"".ENTRADA_URL."/javascript/calendar/css/xc2_default.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
					$HEAD[] 		= "<script language=\"javascript\" type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/config/xc2_default.js\"></script>\n";
					$HEAD[] 		= "<script language=\"javascript\" type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/script/xc2_inpage.js\"></script>\n";
					if ($ERROR) {
						echo display_error();
					}
					?>
					<form id="logbookDeficiencyForm" action="<?php echo ENTRADA_URL; ?>/clerkship/logbook?<?php echo replace_query(array("step" => 2)); ?>" method="post">
					<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Clerkship Log deficiency plan">
					<colgroup>
						<col style="width: 3%" />
						<col style="width: 20%" />
						<col style="width: 77%" />
					</colgroup>
					<tfoot>
					<?php if (!isset($PROCESSED["clerk_accepted"]) || !$PROCESSED["clerk_accepted"] || $PROXY_ID != $ENTRADA_USER->getID()) { ?>
						<tr>
							<td colspan="3" style="padding-top: 25px">
								<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
								<tr>
									<td style="width: 25%; text-align: left">
										<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/clerkship'" />
									</td>
									<td style="width: 75%; text-align: right; vertical-align: middle">
										<input type="submit" class="btn btn-primary" value="Submit" />
									</td>
								</tr>
								</table>
							</td>
						</tr>
					<?php } else { ?>
						<tr>
							<td colspan="3" style="padding-top: 25px">
								<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
								<tr>
									<td style="width: 25%; text-align: left">
										<input type="button" class="btn" value="Go Back" onclick="window.location='<?php echo ENTRADA_URL; ?>/clerkship'" />
									</td>
								</tr>
								</table>
							</td>
						</tr>
					<?php } ?>
					</tfoot>
					<tbody>
						<tr>
							<td>&nbsp;</td>
							<td><label class="form-nrequired">Student</label></td>
							<td><?php echo get_account_data("firstlast", $PROXY_ID); ?></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td><label class="form-nrequired">Course</label></td>
							<td><?php echo $ROTATION_TITLE; ?></td>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<?php
						$grad_year = get_account_data("grad_year", $PROXY_ID);
						$objective_ids = "";
                        
						$query = "SELECT `objective_id`, `lmobjective_id`, MAX(`number_required`) AS `required`
									FROM `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objectives`
									WHERE `rotation_id` = ".$db->qstr($ROTATION_ID)."
									AND `grad_year_min` <= ".$db->qstr($grad_year)."
									AND (`grad_year_max` >= ".$db->qstr($grad_year)." OR `grad_year_max` = 0)
									GROUP BY `objective_id`";
						$required_objectives = $db->GetAll($query);
						$lmobjective_ids = array();
						if ($required_objectives) {
							foreach ($required_objectives as $required_objective) {
								$lmobjective_ids[$required_objective["objective_id"]] = $required_objective["lmobjective_id"];
								$objectives_required += $required_objective["required"];
								$number_required[$required_objective["objective_id"]] = $required_objective["required"];
								$query = "SELECT COUNT(`objective_id`) AS `recorded`
											FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_objectives`
											WHERE `lentry_id` IN
											(
												SELECT `lentry_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_entries` AS a
												".(CLERKSHIP_SETTINGS_REQUIREMENTS ? "
												JOIN `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objective_locations` AS b
												ON b.`lmobjective_id` = ".$db->qstr($required_objective["lmobjective_id"])."
												JOIN `".CLERKSHIP_DATABASE."`.`logbook_location_types` AS c
												ON b.`lltype_id` = c.`lltype_id`
												AND a.`llocation_id` = c.`llocation_id`" : "")."
												WHERE a.`entry_active` = '1' 
												AND a.`proxy_id` = ".$db->qstr($PROXY_ID)."
												
											)
											AND `objective_id` = ".$db->qstr($required_objective["objective_id"])."
											GROUP BY `objective_id`";
								$recorded = $db->GetOne($query);
								
								if ($recorded) {
									if ($required_objective["required"] > $recorded) {
										if ($objective_ids) {
											$objective_ids .= ",".$db->qstr($required_objective["objective_id"]);
										} else {
											$objective_ids = $db->qstr($required_objective["objective_id"]);
										}
										$number_required[$required_objective["objective_id"]] -= $recorded;
									}
									$objectives_recorded += ($recorded <= $required_objective["required"] ? $recorded : $required_objective["required"]);
								} else {
									if ($objective_ids) {
										$objective_ids .= ",".$db->qstr($required_objective["objective_id"]);
									} else {
										$objective_ids = $db->qstr($required_objective["objective_id"]);
									}
								}
							}
						}
						
						$query  = "SELECT * FROM `".DATABASE_NAME."`.`global_lu_objectives`
									WHERE `objective_id` IN (".$objective_ids.")
									AND `objective_active` = '1'
									ORDER BY `objective_name`";
						$objectives = $db->CacheGetAll($query);
						if ($objectives && count($objectives)) {
							?>
							<tr>
								<td>&nbsp;</td>
								<td style="vertical-align: top"><label for="deficiencies" class="form-nrequired">Deficient objectives</label></td>
								<td>
									<?php
										echo "<ul style=\"list-style:none; margin-top: 0px; padding-left: 0px;\">";
										foreach ($objectives as $objective) {
										    $query = "SELECT b.* FROM `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objective_locations` AS a
										    			JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types` AS b
										    			ON a.`lltype_id` = b.`lltype_id`
										    			WHERE a.`lmobjective_id` = ".$db->qstr($lmobjective_ids[$objective["objective_id"]]);
										    $locations = $db->GetAll($query);
										    $location_string = "";
										    foreach ($locations as $location) {
										    	$location_string .= ($location_string ? "/" : "").html_encode($location["location_type_short"]);
										    }
											echo "<li>".$objective["objective_name"].(CLERKSHIP_SETTINGS_REQUIREMENTS && $location_string ? " (".$location_string.")" : "")."</li>";
										}
										echo "</ul>";
									?>
								</td>
							</tr>
							<?php
						}
						$query = "SELECT `lprocedure_id`, `lpprocedure_id`, MAX(`number_required`) AS `required`
									FROM `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedures`
									WHERE `rotation_id` = ".$db->qstr($ROTATION_ID)."
									AND `grad_year_min` <= ".$db->qstr($grad_year)."
									AND (`grad_year_max` >= ".$db->qstr($grad_year)." OR `grad_year_max` = 0)
									GROUP BY `lprocedure_id`";
						$required_procedures = $db->GetAll($query);
						if ($required_procedures) {
							foreach ($required_procedures as $required_procedure) {
								$lpprocedure_ids[$required_procedure["lprocedure_id"]] = $required_procedure["lpprocedure_id"];
								$procedures_required += $required_procedure["required"];
								$number_required[$required_procedure["lprocedure_id"]] = $required_procedure["required"];
								$query = "SELECT COUNT(`lprocedure_id`) AS `recorded`
										FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_procedures`
										WHERE `lentry_id` IN
										(
											SELECT `lentry_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_entries` AS a
											JOIN `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedure_locations` AS b
											ON b.`lpprocedure_id` = ".$db->qstr($required_procedure["lpprocedure_id"])."
											JOIN `".CLERKSHIP_DATABASE."`.`logbook_location_types` AS c
											ON b.`lltype_id` = c.`lltype_id`
											AND a.`llocation_id` = c.`llocation_id`
											".(CLERKSHIP_SETTINGS_REQUIREMENTS ? "
											JOIN `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedure_locations` AS d
											ON d.`lpprocedure_id` = ".$db->qstr($required_procedure["lpprocedure_id"])."
											JOIN `".CLERKSHIP_DATABASE."`.`logbook_location_types` AS e
											ON d.`lltype_id` = e.`lltype_id`
											AND a.`llocation_id` = e.`llocation_id`" : "")."
											WHERE a.`entry_active` = '1' 
											AND a.`proxy_id` = ".$db->qstr($PROXY_ID)."
										)
										AND `lprocedure_id` = ".$db->qstr($required_procedure["lprocedure_id"])."
										GROUP BY `lprocedure_id`";
								$recorded = $db->GetOne($query);
								
								if ($recorded) {
									if ($required_procedure["required"] > $recorded) {
										if ($procedure_ids) {
											$procedure_ids .= ",".$db->qstr($required_procedure["lprocedure_id"]);
										} else {
											$procedure_ids = $db->qstr($required_procedure["lprocedure_id"]);
										}
										$number_required[$required_procedure["lprocedure_id"]] -= $recorded;
									}
									$procedures_recorded += ($recorded <= $required_procedure["required"] ? $recorded : $required_procedure["required"]);
								} else {
									if (isset($procedure_ids) && $procedure_ids) {
										$procedure_ids .= ",".$db->qstr($required_procedure["lprocedure_id"]);
									} else {
										$procedure_ids = $db->qstr($required_procedure["lprocedure_id"]);
									}
								}
							}
						}
					    $query  = "	SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_procedures`
									WHERE `lprocedure_id` IN (".$procedure_ids.")
									ORDER BY `procedure`";
						$procedures = $db->CacheGetAll($query);
						if ($procedures && count($procedures)) {
						?>
							<tr>
								<td>&nbsp;</td>
								<td style="vertical-align: top"><label for="deficiencies" class="form-nrequired">Deficient tasks</label></td>
								<td>
									<?php
										echo "<ul style=\"list-style:none; margin-top: 0px; padding-left: 0px;\">";
										foreach ($procedures as $procedure) {
										    $query = "SELECT b.* FROM `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objective_locations` AS a
										    			JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types` AS b
										    			ON a.`lltype_id` = b.`lltype_id`
										    			WHERE a.`lpprocedure_id` = ".$db->qstr($lpprocedure_ids[$procedure["lprocedure"]]);
										    $locations = $db->GetAll($query);
										    $location_string = "";
										    foreach ($locations as $location) {
										    	$location_string .= ($location_string ? "/" : "").html_encode($location["location_type_short"]);
										    }
											echo "<li>".$procedure["procedure"].(CLERKSHIP_SETTINGS_REQUIREMENTS && $location_string ? " (".$location_string.")" : "")."</li>";
										}
										echo "</ul>";
									?>
								</td>
							</tr>
							<tr>
								<td colspan="3">&nbsp;</td>
							</tr>
						<?php 
						}
						if (isset($PROCESSED["administrator_comments"]) && $PROCESSED["administrator_comments"]) {
						?>
							<tr>
								<td></td>
								<td style="vertical-align: top"><label for="administrator_comments" class="form-required">Administrator comments </label></td>
								<td>
									<div id="administrator_comments" name="administrator_comments" style="width: 95%"><?php echo ((isset($PROCESSED["administrator_comments"])) ? html_encode($PROCESSED["administrator_comments"]) : ""); ?></div>
								</td>
							</tr>
							<tr>
								<td colspan="3">&nbsp;</td>
							</tr>
						<?php
						}
						if (!isset($PROCESSED["clerk_accepted"]) || !$PROCESSED["clerk_accepted"]) { 
							?>
							<tr>
								<td></td>
								<td style="vertical-align: top"><label for="plan_body" class="form-required">Plan to achieve deficient objectives/tasks </label></td>
								<td>
									<textarea id="plan_body" name="plan_body" class="expandable"  maxlength="300" style="width: 95%"<?php echo (isset($PROCESSED["clerk_accepted"]) && $PROCESSED["clerk_accepted"] ? " disabled=\"disabled\"" : ""); ?>><?php echo ((isset($PROCESSED["plan_body"])) ? html_encode($PROCESSED["plan_body"]) : ""); ?></textarea>
								</td>
							</tr>
							<?php
						} else {
							?>
							<tr>
								<td></td>
								<td style="vertical-align: top"><label for="plan_body" class="form-nrequired">Plan to achieve deficient objectives/tasks </label></td>
								<td>
									<div id="plan_body" name="plan_body" style="width: 95%"><?php echo ((isset($PROCESSED["plan_body"])) ? html_encode($PROCESSED["plan_body"]) : ""); ?></div>
								</td>
							</tr>
							<?php
						}
						?>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<?php 
						if (!isset($PROCESSED["clerk_accepted"]) || !$PROCESSED["clerk_accepted"]) {
							echo generate_calendars("timeline", "", true, true, ((isset($PROCESSED["timeline_start"])) ? $PROCESSED["timeline_start"] : 0), true, true, ((isset($PROCESSED["timeline_finish"])) ? $PROCESSED["timeline_finish"] : 0)); 
						} else {
							?>
							<tr>
								<td></td>
								<td><label class="form-nrequired">Timeline Start</label></td>
								<td><?php echo date(DEFAULT_DATE_FORMAT, $PROCESSED["timeline_start"]); ?></td>
							</tr>
							<tr>
								<td></td>
								<td><label class="form-nrequired">Timeline Finish</label></td>
								<td><?php echo date(DEFAULT_DATE_FORMAT, $PROCESSED["timeline_finish"]); ?></td>
							</tr>
							<?php
						}
						?>	
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td><input type="checkbox" id="clerk_accepted" name="clerk_accepted"<?php echo (isset($PROCESSED["clerk_accepted"]) && $PROCESSED["clerk_accepted"] ? " checked=\"checked\" disabled=\"disabled\"" : ""); ?> /></td>
							<?php
							if (!isset($PROCESSED["clerk_accepted"]) || !$PROCESSED["clerk_accepted"]) {
								?>
								<td><label for="clerk_accepted" class="form-required">Confirm completion of deficiency plan</label></td>
								<td><span class="content-small">Check this box to confirm you are satisfied with your deficiency plan. This will cause the Course Director for this rotation to be notified and asked to review the plan. This confirmation cannot be reversed once given, so please ensure you thoroughly review the plan before selecting this option.</span></td>
								<?php
							} else {
								?>
								<td><label for="clerk_accepted" class="form-nrequired">Plan confirmed by clerk</label></td>
								<td><span class="content-small">This deficiency plan has been confirmed by the <?php echo get_account_data("firstlast", $PROXY_ID) ?>.</span></td>
								<?php
							}
							?>
						</tr>
						<?php 
						if ($PROXY_ID != $ENTRADA_USER->getID()) { 
							?>
							<tr>
								<td colspan="3">&nbsp;</td>
							</tr>
							<tr>
								<td><input type="checkbox" id="administrator_accepted" name="administrator_accepted"<?php echo (isset($PROCESSED["administrator_accepted"]) && $PROCESSED["administrator_accepted"] ? " checked=\"checked\"" : ""); ?> /></td>
								<td><label for="administrator_accepted" class="form-required">Confirm completion of deficiency plan</label></td>
								<td><span class="content-small">Select this once the clerk's deficiency plan meets all required criteria to attain deficiencies from this rotation. Otherwise, please write a message to the clerk in the comment box below and the clerk's confirmation will be reversed until they review your comments and amend the plan.</span></td>
							</tr>	
							<tr>
								<td colspan="3">&nbsp;</td>
							</tr>
							<tr>
								<td></td>
								<td style="vertical-align: top"><label for="administrator_comments" class="form-required">Administrator comments </label></td>
								<td>
									<textarea id="administrator_comments" name="administrator_comments" class="expandable"  maxlength="300" style="width: 95%"><?php echo ((isset($PROCESSED["administrator_comments"])) ? html_encode($PROCESSED["administrator_comments"]) : ""); ?></textarea>
								</td>
							</tr>
							<?php
						}
						?>
						</tbody>
					</table>
					</form>
					<?php
				break;
			}
		} else {
			?>
			<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Clerkship Log deficiency plan">
					<colgroup>
						<col style="width: 3%" />
						<col style="width: 37%" />
						<col style="width: 60%" />
					</colgroup>
					<tbody>
						<tr>
							<td>&nbsp;</td>
							<td><label class="form-nrequired">Student</label></td>
							<td><?php echo get_account_data("firstlast", $PROXY_ID); ?></td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td><label class="form-nrequired">Course</label></td>
							<td><?php echo $ROTATION_TITLE; ?></td>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<?php
						$grad_year = get_account_data("grad_year", $PROXY_ID);
						
						$query = "SELECT `objective_id`, `lmobjective_id`, MAX(`number_required`) AS `required`
									FROM `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objectives`
									WHERE `rotation_id` = ".$db->qstr($ROTATION_ID)."
									AND `grad_year_min` <= ".$db->qstr($grad_year)."
									AND (`grad_year_max` >= ".$db->qstr($grad_year)." OR `grad_year_max` = 0)
									GROUP BY `objective_id`";
						$required_objectives = $db->GetAll($query);
						$lmobjective_ids = array();
						if ($required_objectives) {
							foreach ($required_objectives as $required_objective) {
								$lmobjective_ids[$required_objective["objective_id"]] = $required_objective["lmobjective_id"];
								$objectives_required += $required_objective["required"];
								$number_required[$required_objective["objective_id"]] = $required_objective["required"];
								$query = "SELECT COUNT(`objective_id`) AS `recorded`
											FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_objectives`
											WHERE `lentry_id` IN
											(
												SELECT `lentry_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_entries` AS a
												".(CLERKSHIP_SETTINGS_REQUIREMENTS ? "
												JOIN `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objective_locations` AS b
												ON b.`lmobjective_id` = ".$db->qstr($required_objective["lmobjective_id"])."
												JOIN `".CLERKSHIP_DATABASE."`.`logbook_location_types` AS c
												ON b.`lltype_id` = c.`lltype_id`
												AND a.`llocation_id` = c.`llocation_id`" : "")."
												WHERE a.`entry_active` = '1' 
												AND a.`proxy_id` = ".$db->qstr($PROXY_ID)."
												
											)
											AND `objective_id` = ".$db->qstr($required_objective["objective_id"])."
											GROUP BY `objective_id`";
								$recorded = $db->GetOne($query);
								
								if ($recorded) {
									if ($required_objective["required"] > $recorded) {
										if ($objective_ids) {
											$objective_ids .= ",".$db->qstr($required_objective["objective_id"]);
										} else {
											$objective_ids = $db->qstr($required_objective["objective_id"]);
										}
										$number_required[$required_objective["objective_id"]] -= $recorded;
									}
									$objectives_recorded += ($recorded <= $required_objective["required"] ? $recorded : $required_objective["required"]);
								} else {
									if ($objective_ids) {
										$objective_ids .= ",".$db->qstr($required_objective["objective_id"]);
									} else {
										$objective_ids = $db->qstr($required_objective["objective_id"]);
									}
								}
							}
						}
						
						$query  = "SELECT * FROM `".DATABASE_NAME."`.`global_lu_objectives`
									WHERE `objective_id` IN (".$objective_ids.")
									AND `objective_active` = '1'
									ORDER BY `objective_name`";
						$objectives = $db->GetAll($query);
						if ($objectives && count($objectives)) {
							?>
							<tr>
								<td>&nbsp;</td>
								<td style="vertical-align: top"><label for="deficiencies" class="form-nrequired">Deficient objectives</label></td>
								<td>
									<?php
										echo "<ul style=\"list-style:none; margin-top: 0px; padding-left: 0px;\">";
										foreach ($objectives as $objective) {
										    $query = "SELECT b.* FROM `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objective_locations` AS a
										    			JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types` AS b
										    			ON a.`lltype_id` = b.`lltype_id`
										    			WHERE a.`lmobjective_id` = ".$db->qstr($lmobjective_ids[$objective["objective_id"]]);
										    $locations = $db->GetAll($query);
										    $location_string = "";
										    foreach ($locations as $location) {
										    	$location_string .= ($location_string ? "/" : "").html_encode($location["location_type_short"]);
										    }
											echo "<li>".$objective["objective_name"].(CLERKSHIP_SETTINGS_REQUIREMENTS && $location_string ? " (".$location_string.")" : "")."</li>";
										}
										echo "</ul>";
									?>
								</td>
							</tr>
							<?php
						}
						$query = "SELECT `lprocedure_id`, `lpprocedure_id`, MAX(`number_required`) AS `required`
									FROM `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedures`
									WHERE `rotation_id` = ".$db->qstr($ROTATION_ID)."
									AND `grad_year_min` <= ".$db->qstr($grad_year)."
									AND (`grad_year_max` >= ".$db->qstr($grad_year)." OR `grad_year_max` = 0)
									GROUP BY `lprocedure_id`";
						$required_procedures = $db->GetAll($query);
						if ($required_procedures) {
							foreach ($required_procedures as $required_procedure) {
								$lpprocedure_ids[$required_procedure["lprocedure_id"]] = $required_procedure["lpprocedure_id"];
								$procedures_required += $required_procedure["required"];
								$number_required[$required_procedure["lprocedure_id"]] = $required_procedure["required"];
								$query = "SELECT COUNT(`lprocedure_id`) AS `recorded`
										FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_procedures`
										WHERE `lentry_id` IN
										(
											SELECT `lentry_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_entries` AS a
											JOIN `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedure_locations` AS b
											ON b.`lpprocedure_id` = ".$db->qstr($required_procedure["lpprocedure_id"])."
											JOIN `".CLERKSHIP_DATABASE."`.`logbook_location_types` AS c
											ON b.`lltype_id` = c.`lltype_id`
											AND a.`llocation_id` = c.`llocation_id`
											".(CLERKSHIP_SETTINGS_REQUIREMENTS ? "
											JOIN `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedure_locations` AS d
											ON d.`lpprocedure_id` = ".$db->qstr($required_procedure["lpprocedure_id"])."
											JOIN `".CLERKSHIP_DATABASE."`.`logbook_location_types` AS e
											ON d.`lltype_id` = e.`lltype_id`
											AND a.`llocation_id` = e.`llocation_id`" : "")."
											WHERE a.`entry_active` = '1' 
											AND a.`proxy_id` = ".$db->qstr($PROXY_ID)."
										)
										AND `lprocedure_id` = ".$db->qstr($required_procedure["lprocedure_id"])."
										GROUP BY `lprocedure_id`";
								$recorded = $db->GetOne($query);
								
								if ($recorded) {
									if ($required_procedure["required"] > $recorded) {
										if ($procedure_ids) {
											$procedure_ids .= ",".$db->qstr($required_procedure["lprocedure_id"]);
										} else {
											$procedure_ids = $db->qstr($required_procedure["lprocedure_id"]);
										}
										$number_required[$required_procedure["lprocedure_id"]] -= $recorded;
									}
									$procedures_recorded += ($recorded <= $required_procedure["required"] ? $recorded : $required_procedure["required"]);
								} else {
									if (isset($procedure_ids) && $procedure_ids) {
										$procedure_ids .= ",".$db->qstr($required_procedure["lprocedure_id"]);
									} else {
										$procedure_ids = $db->qstr($required_procedure["lprocedure_id"]);
									}
								}
							}
						}
					    $query  = "	SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_procedures`
									WHERE `lprocedure_id` IN (".$procedure_ids.")
									ORDER BY `procedure`";
						$procedures = $db->GetAll($query);
						if ($procedures && count($procedures)) {
						?>
							<tr>
								<td>&nbsp;</td>
								<td style="vertical-align: top"><label for="deficiencies" class="form-nrequired">Deficient tasks</label></td>
								<td>
									<?php
										echo "<ul style=\"list-style:none; margin-top: 0px; padding-left: 0px;\">";
										foreach ($procedures as $procedure) {
										    $query = "SELECT b.* FROM `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objective_locations` AS a
										    			JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types` AS b
										    			ON a.`lltype_id` = b.`lltype_id`
										    			WHERE a.`lpprocedure_id` = ".$db->qstr($lpprocedure_ids[$procedure["lprocedure"]]);
										    $locations = $db->GetAll($query);
										    $location_string = "";
										    foreach ($locations as $location) {
										    	$location_string .= ($location_string ? "/" : "").html_encode($location["location_type_short"]);
										    }
											echo "<li>".$procedure["procedure"].(CLERKSHIP_SETTINGS_REQUIREMENTS && $location_string ? " (".$location_string.")" : "")."</li>";
										}
										echo "</ul>";
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
							<td style="vertical-align: top"><label for="plan_body" class="form-nrequired">Plan to achieve deficient objectives/tasks </label></td>
							<td>
								<div id="plan_body" name="plan_body" style="width: 95%"><?php echo ((isset($PROCESSED["plan_body"])) ? html_encode($PROCESSED["plan_body"]) : ""); ?></div>
							</td>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td></td>
							<td><label class="form-nrequired">Timeline Start</label></td>
							<td><?php echo date(DEFAULT_DATE_FORMAT, $PROCESSED["timeline_start"]); ?></td>
						</tr>
						<tr>
							<td></td>
							<td><label class="form-nrequired">Timeline Finish</label></td>
							<td><?php echo date(DEFAULT_DATE_FORMAT, $PROCESSED["timeline_finish"]); ?></td>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td><input type="checkbox" id="clerk_accepted" name="clerk_accepted"<?php echo (isset($PROCESSED["clerk_accepted"]) && $PROCESSED["clerk_accepted"] ? " checked=\"checked\" disabled=\"disabled\"" : ""); ?> /></td>
							<td><label for="clerk_accepted" class="form-nrequired">Plan confirmed by clerk</label></td>
							<td><span class="content-small">You have already confirmed this deficiency plan.</span></td>
						</tr>
							<tr>
								<td colspan="3">&nbsp;</td>
							</tr>
							<tr>
								<td><input type="checkbox" id="administrator_accepted" name="administrator_accepted" checked="checked" disabled="disabled" /></td>
								<td><label for="administrator_accepted" class="form-nrequired">Confirm completion of deficiency plan</label></td>
								<td><span class="content-small">This deficiency plan has been confirmed by the <?php echo get_account_data("firstlast", $PROCESSED["administrator_id"]) ?>.</span></td>
							</tr>	
							<tr>
								<td colspan="3">&nbsp;</td>
							</tr>
							<tr>
								<td></td>
								<td style="vertical-align: top"><label for="administrator_comments" class="form-nrequired">Administrator comments </label></td>
								<td>
									<div id="administrator_comments" name="administrator_comments" style="width: 95%"><?php echo ((isset($PROCESSED["administrator_comments"])) ? html_encode($PROCESSED["administrator_comments"]) : ""); ?></div>
								</td>
							</tr>
						</tbody>
					</table>			
			<?php
		}
	} else {
			echo display_error();
	}
}
?>