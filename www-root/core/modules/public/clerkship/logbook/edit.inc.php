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
 * @copyright Copyright 2009 Queen's University. All Rights Reserved.
 *
 * @version $Id: index.php 600 2009-08-12 15:19:17Z simpson $
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

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]." and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	if (isset($_GET["id"]) && (clean_input($_GET["id"], "int"))) {
		$RECORD_ID = clean_input($_GET["id"], "int");
	} elseif (isset($_POST["id"]) && (clean_input($_POST["id"], "int"))) {
		$RECORD_ID = clean_input($_POST["id"], "int");
	}
	if ($RECORD_ID) {
		$PROCESSED = $db->GetRow("SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_entries` WHERE `lentry_id` = ".$db->qstr($RECORD_ID)." AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID())." AND `entry_active` = 1");
		if ($PROCESSED) {
			$PROCESSED_OBJECTIVES = $db->GetAll("SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_objectives` WHERE `lentry_id` = ".$db->qstr($RECORD_ID));
			$PROCESSED_PROCEDURES = $db->GetAll("SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_procedures` WHERE `lentry_id` = ".$db->qstr($RECORD_ID));

			$BREADCRUMB[]	= array("url" => ENTRADA_URL."/clerkship/logbook", "title" => "Manage Logbook");
			$BREADCRUMB[]	= array("url" => ENTRADA_URL."/clerkship/logbook?section=add", "title" => "Editing Patient Encounter");
		
			echo "<h1>Editing Patient Encounter</h1>\n";
			if ((isset($_POST["event_id"])) && ($event_id = clean_input($_POST["event_id"], "int"))) {
				$PROCESSED["rotation_id"] = $event_id;
			}
            // Error Checking
            switch ($STEP) {
                case 2 :	
                /**
                 * Required field "rotation" / Rotation.
                 */
                if (!isset($PROCESSED["rotation_id"]) || !$PROCESSED["rotation_id"]) {
                    $ERROR++;
                    $ERRORSTR[] = "The <strong>Rotation</strong> field is required.";
                }
		
				/**
				 * Non-required field "patient" / Patient.
				 */
				if ((isset($_POST["patient_id"])) && ($patient_id = clean_input($_POST["patient_id"], Array("notags","trim")))) {
					$PROCESSED["patient_info"] = $patient_id;
				}
                
                /**
                 * Required field "gender" / Gender.
                 */
                if ((isset($_POST["gender"])) && ($gender = ($_POST["gender"] == "m" ? "m" : "f"))) {
                    $PROCESSED["gender"] = $gender;
                } else {
                    $PROCESSED["gender"] = "";
                }

                /**
                 * Required field "agerange" / Age Range.
                 */
                if ((isset($_POST["agerange"])) && ($agerange = clean_input($_POST["agerange"], "int"))) {
                    $PROCESSED["agerange_id"] = $agerange;
                } else {
                    $ERROR++;
                    $ERRORSTR[] = "The <strong>Age Range</strong> field is required.";
                }

                /**
                 * Required field "institution" / Institution.
                 */
                if ((isset($_POST["institution_id"])) && ($institution_id = clean_input($_POST["institution_id"], "int"))) {
                    $PROCESSED["lsite_id"] = $institution_id;
                } else {
                    $ERROR++;
                    $ERRORSTR[] = "The <strong>Institution</strong> field is required.";
                }

                /**
                 * Required field "location" / Location.
                 */
                if ((isset($_POST["llocation_id"])) && ($location_id = clean_input($_POST["llocation_id"], "int"))) {
                    $PROCESSED["llocation_id"] = $location_id;
                } else {
                    $ERROR++;
                    $ERRORSTR[] = "The <strong>Setting</strong> field is required.";
                }

                /**
                 * Required field "reflection" / Reflection on learning experience.
                 */
                if ((isset($_POST["reflection"])) && ($reflection = clean_input($_POST["reflection"], Array("trim", "notags")))) {
                    $PROCESSED["reflection"] = $reflection;
                } else {
                    $ERROR++;
                    $ERRORSTR[] = "The <strong>Reflection on learning experience</strong> field is required. Please include at least a short description of this encounter before continuing.";
                }

                /**
                 * Non-required field "comments" / Comments.
                 */
                if ((isset($_POST["comments"])) && ($comments = clean_input($_POST["comments"], Array("trim", "notags")))) {
                    $PROCESSED["comments"] = $comments;
                } else {
                    $PROCESSED["comments"] = "";
                }

                /**
                 * Required field "objectives" / Objectives
                 */
                $PROCESSED_OBJECTIVES = Array();
                if (is_array($_POST["objectives"]) && count($_POST["objectives"])) {
                    foreach ($_POST["objectives"] as $objective_id) {
                        $PROCESSED_OBJECTIVES[] = Array ("objective_id" => $objective_id);
                    }
                } else {
                    $ERROR++;
                    $ERRORSTR[] = "The <strong>Objectives</strong> field is required. Please include at least one Clerkship Presentation / Objective in this encounter before continuing.";
                }

                /**
                 * Non-required field "procedures" / procedures
                 */
                $PROCESSED_PROCEDURES = Array();
                if (isset($_POST["procedures"]) && count($_POST["procedures"]) && (@count($_POST["procedures"]) == @count($_POST["proc_participation_level"]))) {
                    foreach ($_POST["procedures"] as $procedure_id) {
                        $PROCESSED_PROCEDURES[] = Array (	"lprocedure_id" => $procedure_id, 
                                                        "level" => $_POST["proc_participation_level"][$procedure_id]
                                                      );
                    }
                }

                $encounter_date = validate_calendar("", "encounter", true);	
                if ((isset($encounter_date)) && ((int) $encounter_date)) {
                    $PROCESSED["encounter_date"]	= (int) $encounter_date;
                } else {
                    $PROCESSED["encounter_date"]	= 0;
                }
                if (isset($_POST["post_action"])) {
                    switch ($_POST["post_action"]) {
                        case "new" :
                            $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "new";
                        break;
                        case "index" :
                            $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "index";
                        break;
                        case "entries" :
                        default :
                            $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "entries";
                        break;
                    }
                } else {
                    $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "entries";
                }

				if (!$ERROR && (!isset($_POST["allow_save"]) || $_POST["allow_save"])) {
					
					$PROCESSED["proxy_id"] = $ENTRADA_USER->getID();
					
					if ($db->AutoExecute("`".CLERKSHIP_DATABASE."`.`logbook_entries`", $PROCESSED, "UPDATE", "`lentry_id` = ".$db->qstr($RECORD_ID))) {
						$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["chosen_institution"] = $PROCESSED["lsite_id"];
						$db->Execute("DELETE FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_objectives` WHERE `lentry_id` = ".$db->qstr($RECORD_ID));
						foreach ($PROCESSED_OBJECTIVES as $objective) {
							$objective["lentry_id"] = $RECORD_ID;
							$db->AutoExecute("`".CLERKSHIP_DATABASE."`.`logbook_entry_objectives`", $objective, "INSERT");
						}
						$db->Execute("DELETE FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_procedures` WHERE `lentry_id` = ".$db->qstr($RECORD_ID));
						foreach ($PROCESSED_PROCEDURES as $procedure) {
							$procedure["lentry_id"] = $RECORD_ID;
							$db->AutoExecute("`".CLERKSHIP_DATABASE."`.`logbook_entry_procedures`", $procedure, "INSERT");
						}
						
						$url = ENTRADA_URL."/".$MODULE."/logbook";
						$SUCCESS++;
						$SUCCESSSTR[]  	= "You have successfully edited this <strong>Patient Encounter</strong> in the system.<br /><br />Please <a href=\"".$url."\">click here</a> to proceed to the Logged Encounters page or you will be automatically forwarded in 5 seconds.";
						$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000)";
		
						application_log("success", "Patient encounter [".$RECORD_ID."] updated in the system.");
					} else {
						$ERROR++;
						$ERRORSTR[] = "There was a problem editing this patient encounter in the system. The MEdTech Unit was informed of this error; please try again later.";
		
						application_log("error", "There was an error editing a clerkship logbook entry. Database said: ".$db->ErrorMsg());
					}
				}
                
                if ($ERROR || (isset($_POST["allow_save"]) && !$_POST["allow_save"])) {
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
                    require_once(ENTRADA_ABSOLUTE."/javascript/logbook.js.php");			
                    if ($ERROR && (!isset($_POST["allow_save"]) || $_POST["allow_save"])) {
                        echo display_error();
                    }
                    ?>
                    <div id="hoverbox" style="display: none;">
                        <span class="content-small">
                            This reflection should be a short entry explaining your experiences with the patient. 
                            It should be no more than approximately 100 words, and you may use initials to refer to the patient, 
                            but no complete data such as their name or record number.
                            <br /><br />
                            For example: 
                            <br /><br />
                            I spent the evening following Ms. J's labour and participated in her delivery. I was able to do 
                            a cervical exam and now feel much more confident in my ability to do this task.  I found that 
                            reviewing my Phase II E notes about normal delivery was very useful to reinforce this experience 
                            and have also read the relevant chapter in the recommended text again following this L+D shift.
                        </span>
                    </div>
                    <form id="addEncounterForm" action="<?php echo ENTRADA_URL; ?>/clerkship/logbook?<?php echo replace_query(array("step" => 2)); ?>" method="post">
                        <input type="hidden" value="1" name="allow_save" id="allow_save" />
                        <div class="row-fluid">
                            <h2>Encounter Details</h2>
                        </div>
                        <table>
                        <?php
                            echo generate_calendar("encounter", "Encounter Date", true, ((isset($PROCESSED["encounter_date"])) ? $PROCESSED["encounter_date"] : time()), true, true);
                        ?>
                        </table>
                        <br />
                        <div class="control-group row-fluid">
                            <label for="rotation_id" class="form-required span3">Rotation</label>
                            <span class="controls span8 offset1">
                                <?php 
                                $query	= "SELECT a.* FROM `".CLERKSHIP_DATABASE."`.`events` AS a 
                                            LEFT JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS b 
                                            ON a.`event_id` = b.`event_id` 
                                            WHERE b.`etype_id` = ".$db->qstr($ENTRADA_USER->getID())." 
                                            AND a.`event_id` = ".$db->qstr(((int)$PROCESSED["rotation_id"]))." 
                                            AND a.`event_type` = 'clinical'";
                                $found	= ($db->GetRow($query) ? true : false);
                                ?>
                                <select id="rotation_id" name="event_id" style="width: 100%<?php echo ($found ? "; display: none" : ""); ?>" onchange="$('allow_save').value = '0';$('addEncounterForm').submit();">
                                <option value="0">-- Select Rotation --</option>
                                <?php
                                $query		= "SELECT a.* FROM `".CLERKSHIP_DATABASE."`.`events` AS a 
                                                LEFT JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS b 
                                                ON a.`event_id` = b.`event_id` 
                                                WHERE b.`etype_id` = ".$db->qstr($ENTRADA_USER->getID())." 
                                                AND a.`event_type` = 'clinical'";
                                $results	= $db->GetAll($query);
                                if ($results) {
                                    foreach ($results as $result) {
                                        echo "<option value=\"".(int) $result["event_id"]."\"".(isset($PROCESSED["rotation_id"]) && $PROCESSED["rotation_id"] == (int)$result["event_id"] ? " selected=\"selected\"" : "").">".$result["event_title"]."</option>\n";
                                        if (isset($PROCESSED["rotation_id"]) && $PROCESSED["rotation_id"] == (int)$result["event_id"]) {
                                            $rotation_title = $result["event_title"];
                                            $event_id = $result["event_id"];
                                        }
                                    }
                                }
                                ?>
                                </select>
                                <?php
                                if ($found && isset($rotation_title) && $rotation_title) {
                                    echo "<div id=\"rotation-title\" style=\"width: 100%\"><span>".$rotation_title."</span><img src=\"".ENTRADA_URL."/images/action-edit.gif\" style=\"float: right; cursor: pointer\" onclick=\"$('rotation-title').hide(); $('rotation_id').show();\"/></div>\n";
                                    echo "<input type=\"hidden\" value=\"".$event_id."\" name=\"rotation_id\" />";
                                }
                                ?>
                            </span>
                        </div>
                        <div class="control-group row-fluid">
                            <label for="institution_id" class="form-required span3">Institution</label>
                            <span class="controls span8 offset1">
                                <select id="institution_id" name="institution_id" style="width: 100%">
                                <option value="0">-- Select Institution --</option>
                                <?php
                                $query		= "SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_sites` 
                                                WHERE `site_type` = ".$db->qstr(CLERKSHIP_SITE_TYPE);
                                $results	= $db->GetAll($query);
                                if ($results) {
                                    foreach ($results as $result) {
                                        echo "<option value=\"".(int) $result["lsite_id"]."\"".((isset($PROCESSED["lsite_id"]) && $PROCESSED["lsite_id"] == $result["lsite_id"]) || (!isset($PROCESSED["lsite_id"]) && isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["chosen_institution"]) && $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["chosen_institution"] == $result["lsite_id"]) ? " selected=\"selected\"" : "").">".$result["site_name"]."</option>\n";
                                    }
                                }
                                ?>
                                </select>
                            </span>
                        </div>
                        <div class="control-group row-fluid">
                            <label for="llocation_id" class="form-required span3">Setting</label>
                            <span class="controls span8 offset1">
                                <select id="llocation_id" name="llocation_id" style="width: 100%">
                                <option value="0">-- Select Setting --</option>
                                <?php
                                    $query	= "SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types`";
                                    $location_types	= $db->GetAll($query);
                                    if ($location_types) {
                                        foreach ($location_types as $location_type) {
                                            echo "<optgroup label=\"".html_encode($location_type["location_type"])."\">\n";
                                            $query		= "SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_locations` AS a
                                                            JOIN `".CLERKSHIP_DATABASE."`.`logbook_location_types` AS b
                                                            ON a.`llocation_id` = b.`llocation_id`
                                                            WHERE b.`lltype_id` = ".$db->qstr($location_type["lltype_id"]);
                                    $results	= $db->GetAll($query);
                                    if ($results) {
                                        foreach ($results as $result) {
                                            echo "<option value=\"".(int) $result["llocation_id"]."\"".(isset($PROCESSED["llocation_id"]) && $PROCESSED["llocation_id"] == (int)$result["llocation_id"] ? " selected=\"selected\"" : "").">".$result["location"]."</option>\n";
                                        }
                                    }
                                            echo "</optgroup>\n";
                                        }
                                }
                                ?>
                                </select>
                            </span>
                        </div>
                        <br />
						<div class="control-group row-fluid">
							<label for="patient_id" class="form-nrequired span3">Patient ID</label>
							<span class="controls span8">
								<input type="text" id="patient_id" name="patient_id" value="<?php echo html_encode((isset($PROCESSED["patient_info"]) && $PROCESSED["patient_info"] ? $PROCESSED["patient_info"] : "")); ?>" maxlength="50" />
							</span>
						</div>
                        <div class="control-group row-fluid">
                            <label for="agerange" class="form-required span3">Patient Age Range</label>
                            <span class="controls span8 offset1">
                                <select id="agerange" name="agerange" style="width: 257px">
                                <?php
                                if (((int)$_GET["event"]) || $PROCESSED["rotation_id"]) {
                                    $query = "SELECT `category_type` FROM `".CLERKSHIP_DATABASE."`.`categories` AS a 
                                                LEFT JOIN `".CLERKSHIP_DATABASE."`.`events` AS b 
                                                ON a.`category_id` = b.`category_id` 
                                                WHERE b.`event_id` = ".$db->qstr((((int)$_GET["event"]) ? ((int)$_GET["event"]) : $PROCESSED["rotation_id"]));
                                    $category_type = $db->GetOne($query);
                                    if ($category_type == "family medicine") {
                                        $agerange_cat = "5";
                                    } else {
                                        $agerange_cat = "0";
                                    }
                                } else {
                                    $agerange_cat = "0";
                                }
                                $query		= "SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_agerange` 
                                                WHERE `rotation_id` = ".$db->qstr($agerange_cat);
                                $results	= $db->GetAll($query);
                                if ($results) {
                                    echo "<option value=\"0\"".((!isset($PROCESSED["agerange_id"])) ? " selected=\"selected\"" : "").">-- Select Age Range --</option>\n";
                                    foreach ($results as $result) {
                                        echo "<option value=\"".(int) $result["agerange_id"]."\"".(isset($PROCESSED["agerange_id"]) && $PROCESSED["agerange_id"] == (int)$result["agerange_id"] ? " selected=\"selected\"" : "").">".$result["age"]."</option>\n";
                                    }
                                } else {
                                    echo "<option value=\"0\"".((!isset($PROCESSED["agerange_id"])) ? " selected=\"selected\"" : "").">-- Age Range --</option>\n";
                                }
                                ?>
                                </select>
                            </span>
                        </div>
                        <br />
                        <div class="control-group row-fluid">
                            <label for="gender" class="form-required span3">Patient Gender</label>
                            <span class="controls span8 offset1">
                                <input type="radio" name="gender" id="gender_female" value="f"<?php echo (((!isset($PROCESSED["gender"])) || ((isset($PROCESSED["gender"])) && ($PROCESSED["gender"]) == "f")) ? " checked=\"checked\"" : ""); ?> /> <label for="gender_female">Female</label><br />
                                <input type="radio" name="gender" id="gender_male" value="m"<?php echo (((isset($PROCESSED["gender"])) && $PROCESSED["gender"] == "m") ? " checked=\"checked\"" : ""); ?> /> <label for="gender_male">Male</label>
                            </span>
                        </div>
                        <br />
                        <div class="control-group row-fluid">
                            <span class="span3">
                                <label for="objective_id" class="form-required">Clinical Presentations</label>
                                <br /><br /><span style="display: none;" id="objective-loading" class="content-small">Loading... <img src="<?php echo ENTRADA_URL; ?>/images/indicator.gif" style="vertical-align: middle;" /></span>
                            </span>
                            <span class="controls span8 offset1">
                                <?php
                                $query		= "SELECT c.`rotation_id`, a.`rotation_id` as `event_rotation_id` 
												FROM `".CLERKSHIP_DATABASE."`.`events` AS a
												JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS b
												ON b.`event_id` = a.`event_id`
												LEFT JOIN `".CLERKSHIP_DATABASE."`.`categories` AS c
												ON a.`category_id` = c.`category_id`
												WHERE b.`econtact_type` = 'student'
												AND b.`etype_id` = ".$db->qstr($ENTRADA_USER->getID())."
												AND a.`event_finish` < ".$db->qstr(time())."
												GROUP BY c.`rotation_id`";
								$rotations = $db->GetAll($query);
								if ($rotations) {
									$past_rotations = "";
									foreach ($rotations as $row) {
										if ($row["rotation_id"]) {
											if ($past_rotations) {
												$past_rotations .= ",".$db->qstr($row["rotation_id"]);
											} else {
												$past_rotations = $db->qstr($row["rotation_id"]);
											}
										} elseif ($row["event_rotation_id"]) {
											if ($past_rotations) {
												$past_rotations .= ",".$db->qstr($row["event_rotation_id"]);
											} else {
												$past_rotations = $db->qstr($row["event_rotation_id"]);
											}
										}
									}

									$query = "	SELECT a.`objective_id`, COUNT(b.`objective_id`) AS `recorded`, a.`number_required` AS `required`
												FROM `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objectives` AS a
												LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_entry_objectives` AS b
												ON a.`objective_id` = b.`objective_id`
												AND b.`lentry_id` NOT IN 
												(
													SELECT `lentry_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_entries`
													WHERE `entry_active` = '0' 
													AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
												)
												AND b.`lentry_id` IN
												(
													SELECT `lentry_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_entries`
													WHERE `entry_active` = '1' 
													AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
												)
												WHERE a.`rotation_id` IN (".$past_rotations.")
												GROUP BY a.`objective_id`";
									$results = $db->GetAll($query);
									if ($results) {
										foreach ($results as $objective) {
											if ($objective["required"] > $objective["recorded"] && $objective["objective_id"]) {
												if ($objective_ids_string) {
													$objective_ids_string .= ",".$db->qstr($objective["objective_id"]);
												} else {
													$objective_ids_string = $db->qstr($required_objective["objective_id"]);
												}
											}
										}
									}
									$query = "	SELECT a.`lprocedure_id`, COUNT(b.`lprocedure_id`) AS `recorded`, a.`number_required` AS `required`
												FROM `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedures` AS a
												LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_entry_procedures` AS b
												ON a.`lprocedure_id` = b.`lprocedure_id`
												AND b.`lentry_id` NOT IN 
												(
													SELECT `lentry_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_entries`
													WHERE `entry_active` = '0' 
													AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
												)
												AND b.`lentry_id` IN
												(
													SELECT `lentry_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_entries`
													WHERE `entry_active` = '1' 
													AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
												)
												WHERE a.`rotation_id` IN (".$past_rotations.")
												AND a.`grad_year_min` <= ".$db->qstr(get_account_data("grad_year", $ENTRADA_USER->getID()))."
												AND (a.`grad_year_max` = 0 OR a.`grad_year_max` >= ".$db->qstr(get_account_data("grad_year", $ENTRADA_USER->getID())).")
												GROUP BY a.`lprocedure_id`";
									$results = $db->GetAll($query);
									if ($results) {
										foreach ($results as $procedure) {
											if ($procedure["required"] > $procedure["recorded"]) {
												if ($procedure_ids) {
													$procedure_ids .= ",".$db->qstr($required_procedure["lprocedure_id"]);
												} else {
													$procedure_ids = $db->qstr($required_procedure["lprocedure_id"]);
												}
											}
										}
									}
								}
								$query = "SELECT * FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations` 
											WHERE `rotation_id` = (SELECT `rotation_id` 
											FROM `".CLERKSHIP_DATABASE."`.`events` 
											WHERE `event_id` = ".$db->qstr($PROCESSED["rotation_id"]).")";
								$rotation = $db->GetRow($query);
								if ($rotation) {
									$rotation_id = $rotation["rotation_id"];
									?>
									<input type="radio" name="objective_display_type" id="objective_display_type_rotation" onclick="showRotationObjectives()" checked="checked" /> <label for="objective_display_type_rotation">Show only clinical presentations for <span id="rotation_title_display" style="font-weight: bold"><?php echo $rotation["rotation_title"]; ?></span></label><br />
									<input type="radio" name="objective_display_type" id="objective_display_type_all" onclick="showAllObjectives()" /> <label for="objective_display_type_all">Show all clinical presentations</label><br />
									<?php
									if (isset($objective_ids_string) && $objective_ids_string) {
									?> 
										<input type="radio" name="objective_display_type" id="objective_display_type_deficient" onclick="showDeficientObjectives()" /> <label for="objective_display_type_deficient">Show only clinical presentations which are deficient from past rotations.</label>
									<?php
									}
									?>
									<br /><br />
									<?php
								} elseif (isset($objective_ids_string) && $objective_ids_string) {
									?>
									<input type="radio" name="objective_display_type" id="objective_display_type_all" onclick="showAllObjectives()" checked="checked" /> <label for="objective_display_type_all">Show all clinical presentations</label><br />
									<input type="radio" name="objective_display_type" id="objective_display_type_deficient" onclick="showDeficientObjectives()" /> <label for="objective_display_type_deficient">Show only clinical presentations which are deficient from past rotations.</label>
									<br /><br />
									<?php
								}
								echo "<select id=\"rotation_objective_id\" name=\"rotation_objective_id\" onchange=\"addObjective(this.value, 0)\" style=\"width: 95%;".(!$rotation || $rotation["rotation_id"] == 12 ? " display: none;" : "")."\">\n";
								echo "<option value=\"0\"".((!isset($PROCESSED["objective_id"])) ? " selected=\"selected\"" : "").">-- Select Clinical Presentation --</option>\n";
								$query		= "SELECT DISTINCT a.* FROM `global_lu_objectives` AS a
												JOIN `objective_organisation` AS b
												ON a.`objective_id` = b.`objective_id`
												WHERE a.`objective_parent` = '200' 
												AND a.`objective_active` = '1'
												AND 
												(
													a.`objective_id` IN 
													(
														SELECT `objective_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objectives` 
														WHERE `rotation_id` = ".$db->qstr($rotation_id)." 
														AND `grad_year_min` <= ".$db->qstr(get_account_data("grad_year", $ENTRADA_USER->getID()))."
														AND (`grad_year_max` = 0 OR `grad_year_max` >= ".$db->qstr(get_account_data("grad_year", $ENTRADA_USER->getID())).")
													)
												)
												AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
												ORDER BY a.`objective_name`";
								$results	= $db->GetAll($query);
								if ($results) {
									foreach ($results as $result) {
                                        $locations = false;
									    $query = "SELECT c.* FROM `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objectives` AS a
									    			JOIN `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objective_locations` AS b
									    			ON a.`lmobjective_id` = b.`lmobjective_id`
									    			JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types` AS c
									    			ON b.`lltype_id` = c.`lltype_id`
									    			WHERE a.`objective_id` = ".$db->qstr($result["objective_id"])."
									    			AND a.`rotation_id` = ".$db->qstr($rotation_id)." 
													AND a.`grad_year_min` <= ".$db->qstr(get_account_data("grad_year", $ENTRADA_USER->getID()))."
													AND (a.`grad_year_max` = 0 OR a.`grad_year_max` >= ".$db->qstr(get_account_data("grad_year", $ENTRADA_USER->getID())).")
													GROUP BY c.`lltype_id`";
									    $locations = $db->GetAll($query);
                                        if (!$locations) {
                                            $query = "SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types`";
                                            $locations = $db->GetAll($query);
                                        }
									    $location_string = "";
									    foreach ($locations as $location) {
									    	$location_string .= ($location_string ? "/" : "").html_encode($location["location_type_short"]);
									    }
										echo "<option id=\"rotation-obj-item-".$result["objective_id"]."\" value=\"".(int) $result["objective_id"]."\">".html_encode($result["objective_name"]." (".$location_string.")")."</option>\n";
										$query = "SELECT a.* FROM `global_lu_objectives` AS a
													JOIN `objective_organisation` AS b
													ON a.`objective_id` = b.`objective_id`
													WHERE a.`objective_parent` = ".$db->qstr($result["objective_id"])."
													AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
													AND a.`objective_active` = '1'";
										$children = $db->GetAll($query);
										if ($children) {
											foreach ($children as $child) {
												echo "<option id=\"rotation-obj-item-".$child["objective_id"]."\" value=\"".(int) $child["objective_id"]."\">".html_encode($child["objective_name"])."</option>\n";
											}
										}
									}
								}
								echo "</select>\n";
								
								echo "<select id=\"deficient_objective_id\" name=\"deficient_objective_id\" onchange=\"addObjective(this.value, 0)\" style=\"width: 95%; display: none;\">\n";
								echo "<option value=\"0\"".((!isset($PROCESSED["objective_id"])) ? " selected=\"selected\"" : "").">-- Select Clinical Presentation --</option>\n";
								
								$query		= "SELECT DISTINCT a.* FROM `global_lu_objectives` AS a
												JOIN `objective_organisation` AS b
												ON a.`objective_id` = b.`objective_id`
												WHERE a.`objective_parent` = '200' 
												AND a.`objective_active` = '1'
												AND 
												(
													a.`objective_id` IN (".$objective_ids_string.")
												)
												AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
												ORDER BY a.`objective_name`";
								$results	= $db->GetAll($query);
								if ($results) {
									foreach ($results as $result) {
										echo "<option id=\"deficient-obj-item-".$result["objective_id"]."\" value=\"".(int) $result["objective_id"]."\">".html_encode($result["objective_name"])."</option>\n";
										$query = "SELECT * FROM `global_lu_objectives` AS a
													JOIN `objective_organisation` AS b
													ON a.`objective_id` = b.`objective_id`
													WHERE a.`objective_parent` = ".$db->qstr($result["objective_id"])."
													AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
													AND a.`objective_active` = '1'";
										$children = $db->GetAll($query);
										if ($children) {
											foreach ($children as $child) {
												echo "<option id=\"deficient-obj-item-".$child["objective_id"]."\" value=\"".(int) $child["objective_id"]."\">".html_encode($child["objective_name"])."</option>\n";
											}
										}
									}
								}
								echo "</select>\n";
								echo "<select id=\"all_objective_id\" name=\"all_objective_id\" onchange=\"addObjective(this.value, 0)\" style=\"width: 95%;".($rotation && $rotation["rotation_id"] != 12 ? " display: none;" : "")."\">\n";
								echo "<option value=\"0\"".((!isset($PROCESSED["objective_id"])) ? " selected=\"selected\"" : "").">-- Select Clinical Presentation --</option>\n";
								$query		= "SELECT a.* FROM `global_lu_objectives` AS a
												JOIN `objective_organisation` AS b
												ON a.`objective_id` = b.`objective_id`
												WHERE a.`objective_parent` = '200' 
												AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
												AND a.`objective_active` = '1'
												ORDER BY a.`objective_name`";
								$results	= $db->GetAll($query);
								if ($results) {
									foreach ($results as $result) {
										echo "<option id=\"all-obj-item-".$result["objective_id"]."\" value=\"".(int) $result["objective_id"]."\">".html_encode($result["objective_name"])."</option>\n";
										$query = "SELECT a.* FROM `global_lu_objectives` AS a
													JOIN `objective_organisation` AS b
													ON a.`objective_id` = b.`objective_id`
													WHERE a.`objective_parent` = ".$db->qstr($result["objective_id"])."
													AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
													AND a.`objective_active` = '1'";
										$children = $db->GetAll($query);
										if ($children) {
											foreach ($children as $child) {
												echo "<option id=\"all-obj-item-".$child["objective_id"]."\" value=\"".(int) $child["objective_id"]."\">".html_encode($child["objective_name"])."</option>\n";
											}
										}
									}
								}
								echo "</select>\n";
                                ?>
                            </span>
                        </div>
                        <div class="control-group row-fluid" id="objective-container"<?php echo !(isset($PROCESSED_OBJECTIVES) || !@count($PROCESSED_OBJECTIVES) ? " style=\"display: none;\"" : ""); ?>>
                            <span class="span3">&nbsp;</span>
                            <span class="offset1 span8">
                                <div id="objective-list" class="border-bottom margin-bottom-sm">
                                    <?php 
                                    if (isset($PROCESSED_OBJECTIVES) && count($PROCESSED_OBJECTIVES)) { 
                                        foreach ($PROCESSED_OBJECTIVES as $objective_id) {
                                            $query = "	SELECT a.* FROM `global_lu_objectives` AS a
                                                        JOIN `objective_organisation` AS b
                                                        WHERE a.`objective_id` = ".$db->qstr($objective_id["objective_id"])." 
                                                        AND a.`objective_active` = '1'
                                                        AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
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
                                                <div class="row-fluid" id="objective_<?php echo $objective_id["objective_id"]; ?>_row">
                                                    <span class="span1">
                                                        <input type="checkbox" class="objective_delete" value="<?php echo $objective_id["objective_id"]; ?>" />
                                                    </span>
                                                    <label class="offset1 span10" for="delete_objective_<?php echo $objective_id["objective_id"]; ?>">
                                                        <?php echo $objective["objective_name"]?>
                                                    </label>
                                                    <input type="hidden" name="objectives[<?php echo $objective_id["objective_id"]; ?>]" value="<?php echo $objective_id["objective_id"]; ?>" />
                                                </div>
                                            <?php 
                                            }
                                        }
                                    } 
                                    ?>
                                </div>
                                <input type="button" class="btn btn-danger" value="Remove Selected" onclick="removeObjectives()"/>
                            </span>
                        </div>
                        <div class="control-group row-fluid">
                            <span class="span3">
                                <label for="procedure_id" class="form-required">Clinical Tasks</label>
                                <br /><br />
                                <span style="display: none;" id="procedure-loading" class="content-small">Loading... <img src="<?php echo ENTRADA_URL; ?>/images/indicator.gif" style="vertical-align: middle;" /></span>                    </span>
                            <span class="controls span8 offset1">
                                <input type="hidden" id="default_procedure_involvement" value="Assisted" />
                                <?php
                                    $deficient_procedures = false;
                                    if (!empty($procedure_ids)) {
                                        $query = "SELECT DISTINCT a.* FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_procedures` AS a
                                                LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedures` AS b
                                                ON b.`lprocedure_id` = a.`lprocedure_id`
                                                WHERE a.`lprocedure_id` IN (".$procedure_ids.")
                                                AND b.`grad_year_min` <= ".$db->qstr(get_account_data("grad_year", $ENTRADA_USER->getID()));
                                        $deficient_procedures = $db->GetAll($query);
                                    }
                                    if ($rotation) {
                                        $query = "SELECT DISTINCT a.* FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_procedures` AS a
                                                    LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedures` AS b
                                                    ON b.`lprocedure_id` = a.`lprocedure_id`
                                                    WHERE b.`rotation_id` = ".$db->qstr($rotation_id)."
                                                    AND b.`grad_year_min` <= ".$db->qstr(get_account_data("grad_year", $ENTRADA_USER->getID()));
                                        $preferred_procedures = $db->GetAll($query);
                                        if ($preferred_procedures) {
                                            ?>
                                            <input type="radio" name="procedure_display_type" id="procedure_display_type_rotation" onclick="showRotationProcedures()" checked="checked" /> <label for="procedure_display_type_rotation">Show only clinical tasks for <span id="rotation_title_display" style="font-weight: bold"><?php echo $rotation["rotation_title"]; ?></span></label><br />
                                            <input type="radio" name="procedure_display_type" id="procedure_display_type_all" onclick="showAllProcedures()" /> <label for="procedure_display_type_all">Show all clinical tasks</label><br />
                                            <?php
                                            if ($deficient_procedures) {
                                            ?>
                                                <input type="radio" name="procedure_display_type" id="procedure_display_type_deficient" onclick="showDeficientProcedures()" /> <label for="procedure_display_type_deficient">Show only clinical tasks which are deficient from past rotations.</label>
                                            <?php
                                            }
                                            ?>
                                            <br /><br />
                                            <?php
                                        } elseif ($deficient_procedures) {
                                        ?>
                                            <input type="radio" name="procedure_display_type" id="procedure_display_type_all" onclick="showAllProcedures()" checked="checked" /> <label for="procedure_display_type_all">Show all clinical tasks</label><br />
                                            <input type="radio" name="procedure_display_type" id="procedure_display_type_deficient" onclick="showDeficientProcedures()" /> <label for="procedure_display_type_deficient">Show only clinical tasks which are deficient from past rotations.</label>
                                        <?php
                                        }
                                    } elseif ($deficient_procedures) {
                                    ?>
                                        <input type="radio" name="procedure_display_type" id="procedure_display_type_all" onclick="showAllProcedures()" checked="checked" /> <label for="procedure_display_type_all">Show all clinical tasks</label><br />
                                        <input type="radio" name="procedure_display_type" id="procedure_display_type_deficient" onclick="showDeficientProcedures()" /> <label for="procedure_display_type_deficient">Show only clinical tasks which are deficient from past rotations.</label>
                                    <?php
                                    }
                                echo "<select id=\"rotation_procedure_id\" name=\"rotation_procedure_id\" onchange=\"addProcedure(this.value, 0)\" style=\"width: 100%;".(!isset($preferred_procedures) || !$preferred_procedures ? " display: none;" : "")."\">\n";
                                echo "<option value=\"0\"".((!isset($PROCESSED["objective_id"])) ? " selected=\"selected\"" : "").">-- Select Clinical Tasks --</option>\n";
                                if (!empty($preferred_procedures)) {
                                    foreach ($preferred_procedures as $result) {
                                        $locations = false;
									    $query = "SELECT c.* FROM `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedures` AS a
									    			JOIN `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedure_locations` AS b
									    			ON a.`lpprocedure_id` = b.`lpprocedure_id`
									    			JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types` AS c
									    			ON b.`lltype_id` = c.`lltype_id`
									    			WHERE a.`lprocedure_id` = ".$db->qstr($result["lprocedure_id"])."
									    			AND a.`rotation_id` = ".$db->qstr($rotation_id)." 
													AND a.`grad_year_min` <= ".$db->qstr(get_account_data("grad_year", $ENTRADA_USER->getID()))."
													AND (a.`grad_year_max` = 0 OR a.`grad_year_max` >= ".$db->qstr(get_account_data("grad_year", $ENTRADA_USER->getID())).")
													GROUP BY c.`lltype_id`";
									    $locations = $db->GetAll($query);
                                        if (!$locations) {
                                            $query = "SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types`";
                                            $locations = $db->GetAll($query);
                                        }
									    $location_string = "";
									    foreach ($locations as $location) {
									    	$location_string .= ($location_string ? "/" : "").html_encode($location["location_type_short"]);
									    }
                                        echo "<option id=\"rotation-proc-item-".$result["lprocedure_id"]."\" value=\"".(int) $result["lprocedure_id"]."\">".html_encode($result["procedure"]." (".$location_string.")")."</option>\n";
                                    }
                                }
                                echo "</select>\n";
                                echo "<select id=\"deficient_procedure_id\" name=\"deficient_procedure_id\" onchange=\"addProcedure(this.value, 0)\" style=\"width: 100%; display: none;\">\n";
                                echo "<option value=\"0\"".((!isset($PROCESSED["objective_id"])) ? " selected=\"selected\"" : "").">-- Select Clinical Tasks --</option>\n";
                                if ($deficient_procedures) {
                                    foreach ($deficient_procedures as $result) {
                                        echo "<option id=\"deficient-proc-item-".$result["lprocedure_id"]."\" value=\"".(int) $result["lprocedure_id"]."\">".html_encode($result["procedure"])."</option>\n";
                                    }
                                }
                                echo "</select>\n";
                                $query = "SELECT a.* FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_procedures` AS a
                                            LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedures` AS b
                                            ON b.`lprocedure_id` = a.`lprocedure_id`
                                            WHERE b.`grad_year_min` <= ".$db->qstr(get_account_data("grad_year", $ENTRADA_USER->getID()))."
                                            GROUP BY a.`lprocedure_id`
                                            ORDER BY a.`procedure`";
                                $results = $db->GetAll($query);
                                echo "<select id=\"all_procedure_id\" style=\"width: 100%;".(isset($preferred_procedures) && $preferred_procedures ? " display: none;" : "")."\" name=\"all_procedure_id\" onchange=\"addProcedure(this.value, 0)\">\n";
                                echo "<option value=\"0\"".((!isset($PROCESSED["procedure_id"])) ? " selected=\"selected\"" : "").">-- Select Clinical Tasks --</option>\n";
                                if ($results) {
                                    foreach ($results as $result) {
                                        echo "<option id=\"all-proc-item-".$result["lprocedure_id"]."\" value=\"".(int) $result["lprocedure_id"]."\">".html_encode($result["procedure"])."</option>\n";
                                    }
                                }
                                echo "</select>\n";
                                ?>
                            </span>
                        </div>
                        <div class="control-group row-fluid" id="procedure-container"<?php echo !(isset($PROCESSED_PROCEDURES) || !@count($PROCESSED_PROCEDURES) ? " style=\"display: none;\"" : ""); ?>>
                            <span class="span3">&nbsp;</span>
                            <span class="offset1 span8">
                                <div id="procedure-list" class="border-bottom margin-bottom-sm">
                                    <?php 
                                    if (isset($PROCESSED_PROCEDURES) && count($PROCESSED_PROCEDURES)) { 
                                        foreach ($PROCESSED_PROCEDURES as $procedure_id) {
                                            $procedure = $db->GetRow("SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_procedures` WHERE `lprocedure_id` = ".$db->qstr($procedure_id["lprocedure_id"])." ORDER BY `procedure`");
                                            if ($procedure) {
                                            ?>
                                                <div class="row-fluid" id="procedure_<?php echo $procedure_id["lprocedure_id"]; ?>_row">
                                                    <span class="span1">
                                                        <input type="checkbox" class="procedure_delete" value="<?php echo $procedure_id["lprocedure_id"]; ?>" />
                                                    </span>
                                                    <label class="span6" for="delete_procedure_<?php echo $procedure_id["lprocedure_id"]; ?>">
                                                        <?php echo $procedure["procedure"]?>
                                                    </label>
                                                    <span class="span5">
                                                        <input type="hidden" name="procedures[<?php echo $procedure_id["lprocedure_id"]; ?>]" value="<?php echo $procedure_id["lprocedure_id"]; ?>" />
                                                        <select name="proc_participation_level[<?php echo $procedure_id["lprocedure_id"]; ?>]" id="proc_<?php echo $procedure_id["lprocedure_id"]; ?>_participation_level" style="width: 150px" class="pull-right">
                                                            <option value="1" <?php echo ($procedure_id["level"] == 1 || (!$procedure_id["level"]) ? "selected=\"selected\"" : ""); ?>>Observed</option>
                                                            <option value="2" <?php echo ($procedure_id["level"] == 2 ? "selected=\"selected\"" : ""); ?>>Performed with help</option>
                                                            <option value="3" <?php echo ($procedure_id["level"] == 3 ? "selected=\"selected\"" : ""); ?>>Performed independently</option>
                                                        </select>
                                                    </span>
                                                </div>
                                            <?php 
                                            }
                                        }
                                    } 
                                    ?>
                                </div>
                                <input type="button" class="btn btn-danger" value="Remove Selected" onclick="removeProcedures()"/>
                            </span>
                        </div>
                        <br />
                        <div class="control-group row-fluid">
                                <label for="reflection" class="form-required span3">Reflection on learning experience<a style="position: absolute; margin-left: 10px;" id="tooltip" href="#hoverbox"><img style="border: none;" src="<?php echo ENTRADA_URL; ?>/images/btn_help.gif"/></a></label>
                            <span class="controls span8 offset1">
                                <textarea id="reflection" name="reflection" class="expandable" style="width: 100%"><?php echo ((isset($PROCESSED["reflection"])) ? html_encode($PROCESSED["reflection"]) : ""); ?></textarea>
                            </span>
                        </div>
                        <br />
                        <div class="control-group row-fluid">
                            <label for="comments" class="form-nrequired span3">Additional Comments </label>
                            <span class="controls span8 offset1">
                                <textarea id="comments" name="comments" class="expandable" style="width: 100%"><?php echo ((isset($PROCESSED["comments"])) ? html_encode($PROCESSED["comments"]) : ""); ?></textarea>
                            </span>
                        </div>
                        <div class="row-fluid">
                            <span class="span3">
                                <input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/clerkship'" />
                            </span>
                            <span class="span7">
                                <span class="pull-right">
                                    <span class="content-small">After saving:</span>
                                    <select name="post_action" id="post_action" style="width: 200px; margin-right: 20px;">
                                        <option value="entries"<?php echo (((!isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"])) || ($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "entries")) ? " selected=\"selected\"" : ""); ?>>View your logbook entries</option>
                                        <option value="new"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "new") ? " selected=\"selected\"" : ""); ?>>Add another entry</option>
                                        <option value="index"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "index") ? " selected=\"selected\"" : ""); ?>>Return to clerkship index</option>
                                    </select>
                                </span>
                            </span>
                            <span class="span2">
                                <input type="submit" class="btn btn-primary pull-right" value="Submit" />
                            </span>
                        </div>
                    </form>
                    <?php
                break;
            }
		} else {
			$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

			$ERROR++;
			$ERRORSTR[]	= "This Entry ID is not valid.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";
		
			echo display_error();
		
			application_log("error", "Error, invalid Entry ID [".$RECORD_ID."] supplied for editing a clerkship elective in module [".$MODULE."].");
		}
	} else {
		$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

		$ERROR++;
		$ERRORSTR[]	= "You must provide a valid Entry ID.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";
	
		echo display_error();
	
		application_log("error", "Error, invalid Entry ID [".$RECORD_ID."] supplied for clerkship logbook in module [".$MODULE."].");
	}
}
?>