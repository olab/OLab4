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
 * Allows the student to view their logbook details.
 *
 * @author Organisation: University of Calgary
 * @author Unit: Undergraduate Medical Education
 * @author Developer: Doug Hall <hall@ucalgary.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2009 University of Calgary. All Rights Reserved.
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
	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/public/clerkship/logbook?section=view", "title" => "View Patient Encounters");
	
    if(isset($_GET["core"])) {
		$rotation_id = clean_input($_GET["core"], "int");
    } else {
		$rotation_id = 0;
    }
    $clinical_rotation	 = clerkship_get_rotation($rotation_id);
    
    if (isset($_GET["id"]) && $_GET["id"]) {
    	$PROXY_ID = $_GET["id"];
    	$student = false;
    } else {
    	$PROXY_ID = $ENTRADA_USER->getID();
    	$student = true;
    }
    
    if (!$student) {
    	$accessible_rotation_ids = clerkship_rotations_access();
    }
	if (($student && $_SESSION["details"]["group"] == "student") || array_search($rotation_id, $accessible_rotation_ids) !== false) {
		// Error Checking
		if ($STEP == 2) {
            if (isset($_POST["discussion_comment"]) && ($new_comments = clean_input($_POST["discussion_comment"], array("trim", "notags")))) {
                $PROCESSED["comments"] = $new_comments;
                $PROCESSED["clerk_id"] = $PROXY_ID;
                $PROCESSED["proxy_id"] = $ENTRADA_USER->getID();
                $PROCESSED["rotation_id"] = $rotation_id;
                $PROCESSED["updated_date"] = time();

                if ($db->AutoExecute(CLERKSHIP_DATABASE.".logbook_rotation_comments", $PROCESSED, "INSERT")) {
                    if (defined("NOTIFICATIONS_ACTIVE") && NOTIFICATIONS_ACTIVE) {
                        $lrcomment_id = $db->Insert_Id();
                        require_once("Classes/notifications/NotificationUser.class.php");
                        $notification_user = NotificationUser::get($PROXY_ID, "logbook_rotation", $rotation_id, $PROXY_ID);
                        if (!$notification_user) {
                            $notification_user = NotificationUser::add($PROXY_ID, "logbook_rotation", $rotation_id, $PROXY_ID);
                        }
                        NotificationUser::addAllNotifications("logbook_rotation", $rotation_id, $PROXY_ID, $ENTRADA_USER->getID(), $lrcomment_id);
                    }
                    $SUCCESS++;
                    $SUCCESSSTR[] = "You have succesfully added a comment to this rotation".($student ? "" : " for ".get_account_data("firstlast", $PROXY_ID)).".";
                } else {
                    $ERROR++;
                    $ERRORSTR[] = "There was an issue while attempting to add your comment to the system. <br /><br />If you if this error persists, please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";
                    application_log("error", "There was an error adding a clerkship rotation comment entry. Database said: ".$db->ErrorMsg());
                }
            }
            $STEP = 1;
		}
		
		// Display Content
		switch ($STEP) {
			case 2 :
			break;
			case 1 :
			default :
				$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/windows/window.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
				$HEAD[] = "<link href=\"".ENTRADA_RELATIVE."/css/windows/default.css\" rel=\"stylesheet\" type=\"text/css\" />";
				$HEAD[] = "<link href=\"".ENTRADA_RELATIVE."/css/windows/medtech.css\" rel=\"stylesheet\" type=\"text/css\" />";
				?>
				<script type="text/javascript">
				document.observe('dom:loaded',function(){
					$$(".setting-tooltip").each(function (e) {
						new Control.Window($(e.id),{  
							position: 'relative',  
							hover: true,  
							offsetLeft: 125,  
							width: 250,  
							className: 'setting-tooltip-box'  
						});
					});
				}); 
				</script>
				<?php
				$grad_year = get_account_data("grad_year", $PROXY_ID);
				
			    $clinical_rotation	 = clerkship_get_rotation($rotation_id);
			    $fullname = $db->GetOne("SELECT CONCAT_WS(' ', `firstname`, `lastname`) FROM `".AUTH_DATABASE."`.`user_data` WHERE `id` = ".$db->qstr($PROXY_ID));
			    echo "<h1>".$clinical_rotation["title"]." Rotation Patient Encounters Report</h1>\n";
			    echo "<h2 style=\"border: none;\">For: ".$fullname."</h2>";
			
				if ($SUCCESS) {
					echo display_success();
				}
						
				if ($NOTICE) {
					echo display_notice();
				}
						
				if ($ERROR) {
					echo display_error();
				}
				// Collect objectives seen within the rotation: 1 indicates mandatories, 2 indicates non mandatories
				
				$LOCATIONS = array();
				$show_legend = false;
				
				$query = "SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_locations`";
				$results = $db->GetAll($query);
				if ($results) {
					foreach ($results as $result) {
						$location_types_string = "";
						$location_types_string_long = "";
						$location_types_array = array();
						
						$query = "SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_location_types` AS a
									JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types` AS b
									ON a.`lltype_id` = b.`lltype_id`
									WHERE a.`llocation_id` = ".$db->qstr($result["llocation_id"]);
						$location_types = $db->GetAll($query);
						if ($location_types) {
						    foreach ($location_types as $location_type) {
						    	$location_types_array[] = $location_type["location_type_short"];
						    	$location_types_string .= ($location_types_string ? "/" : "").html_encode($location_type["location_type_short"]);
						    	$location_types_string_long .= ($location_types_string_long ? "/" : "").html_encode($location_type["location_type"]);
						    }
						}
					    
						$LOCATIONS[$result["llocation_id"]] = $result;
						$LOCATIONS[$result["llocation_id"]]["location_types_string"] = $location_types_string;
						$LOCATIONS[$result["llocation_id"]]["location_types_string_long"] = $location_types_string_long;
						$LOCATIONS[$result["llocation_id"]]["location_types_array"] = $location_types_array;
					}
				}
				
				$query = "SELECT COUNT(DISTINCT a.`leobjective_id`) AS `count`, a.`objective_id`, b.`llocation_id`, e.`number_required` AS `required`, i.`location_type_short` AS `required_location`, k.`location_type_short` AS `logged_location`, m.`location_type_short` AS `backup_logged_location`, f.`objective_name` AS `objective`
							FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_objectives` AS a
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_entries` AS b
							ON b.`lentry_id` = a.`lentry_id`
							AND b.`entry_active` = 1
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`events` AS c
							ON b.`rotation_id` = c.`event_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objectives` AS e
							ON e.`rotation_id` = c.`rotation_id`
							AND e.`objective_id` = a.`objective_id`
							AND e.`grad_year_min` <= ".$db->qstr($grad_year)."
							AND (e.`grad_year_max` >= ".$db->qstr($grad_year)." OR e.`grad_year_max` = 0)
							LEFT JOIN `global_lu_objectives` AS f
							ON f.`objective_id` = a.`objective_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objective_locations` AS g
							ON e.`lmobjective_id` = g.`lmobjective_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_location_types` AS h
							ON g.`lltype_id` = h.`lltype_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types` AS i
							ON h.`lltype_id` = i.`lltype_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_location_types` AS j
							ON b.`llocation_id` = j.`llocation_id`
							AND i.`lltype_id` = j.`lltype_id`
							AND (j.`lltype_id` IN (SELECT `lltype_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objective_locations` WHERE `lmobjective_id` = g.`lmobjective_id`))
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types` AS k
							ON j.`lltype_id` = k.`lltype_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_location_types` AS l
							ON b.`llocation_id` = l.`llocation_id`
							AND j.`lltype_id` IS NULL
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types` AS m
							ON l.`lltype_id` = m.`lltype_id`
							WHERE c.`rotation_id` = ".$db->qstr($rotation_id)."
							AND b.`proxy_id` = ".$PROXY_ID."
							AND f.`objective_active` = '1'
							GROUP BY a.`objective_id`".(CLERKSHIP_SETTINGS_REQUIREMENTS ? ", b.`llocation_id`" : "")."
							ORDER BY a.`objective_id`";
				$results = $db->GetAll($query);
			    if ($results) {
					$tooltip_locations_array = array();
					?>
					<br />
					<table class="table table-striped" cellspacing="0" summary="Clinical Presentations Encountered in <?php echo $clinical_rotation["title"]; ?>">
					    <colgroup>
							<col style="width:3%" />
							<col style="width:<?php echo (CLERKSHIP_SETTINGS_REQUIREMENTS ? 52 : 67); ?>%;"/>
							<col style="width:15%;"  />
                            <?php
                            if (CLERKSHIP_SETTINGS_REQUIREMENTS) {
                                ?>
							<col style="width:15%;"  />
                                <?php
                            }
                            ?>
							<col style="width:15%;" />
					    </colgroup>
					    <thead>
						<tr>
						    <th colspan="2">Clinical Presentations Encountered in <?php echo $clinical_rotation["title"]; ?></th>
						    <th>Logged</th>
                            <?php
                            if (CLERKSHIP_SETTINGS_REQUIREMENTS) {
                                ?>
                                <th>Setting</th>
                                <?php
                            }
                            ?>
						    <th>Required</th>
						</tr>
					    </thead>
					    <tbody>
					    <?php
						foreach ($results as $result) {
							$result["logged_location"] = (!isset($result["logged_location"]) || !$result["logged_location"] ? $result["backup_logged_location"] : $result["logged_location"]);
						    $query = "SELECT c.* FROM `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objectives` AS a
						    			JOIN `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objective_locations` AS b
						    			ON a.`lmobjective_id` = b.`lmobjective_id`
						    			JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types` AS c
						    			ON b.`lltype_id` = c.`lltype_id`
						    			WHERE a.`objective_id` = ".$db->qstr($result["objective_id"])."
						    			AND a.`rotation_id` = ".$db->qstr($rotation_id)." 
										AND a.`grad_year_min` <= ".$db->qstr($grad_year)."
										AND (a.`grad_year_max` = 0 OR a.`grad_year_max` >= ".$db->qstr($grad_year).")
										GROUP BY c.`lltype_id`";
						    $locations = $db->GetAll($query);
						    $locations_array = array();
						    $location_string = "";
						    $location_string_long = "";
							$count = 1;
						    foreach ($locations as $location) {
						    	$locations_array[] = $location["location_type_short"];
						    	$location_string .= ($location_string ? "/" : "").html_encode($location["location_type_short"]);
						    	$location_string_long .= ($location_string_long ? ($count < count($locations) ? ", " : " or ") : "").html_encode($location["location_type"]);
								$count++;
						    }
							
							$correct_location = (CLERKSHIP_SETTINGS_REQUIREMENTS && array_search($result["logged_location"], $locations_array) === false && $location_string ? false : true);
							if (!$correct_location) {
								$tooltip_locations_array[$result["objective_id"]."-".$result["llocation_id"]] = array("required" => $location_string_long, "logged" => $LOCATIONS[$result["llocation_id"]]["location_types_string_long"]);
							}
							
							echo "<tr>";
							echo "<td>&nbsp;</td>";
						    echo "<td>".$result["objective"]."</td>";
							echo "<td>".$result["count"]."</td>";
                            if (CLERKSHIP_SETTINGS_REQUIREMENTS) {
                                echo "<td".($correct_location ? "" : " class=\"incorrect-setting setting-tooltip\" id=\"tooltip-".$result["objective_id"]."-".$result["llocation_id"]."\" href=\"#locations-cp-".$result["objective_id"]."-".$result["llocation_id"]."\"").">".$LOCATIONS[$result["llocation_id"]]["location_types_string"].($correct_location ? "" : "")."</td>";
                            }
						    echo "<td>".(!$correct_location ? "0" : $result["required"])."</td>";
						    echo "</tr>";
						}
					    ?>
					    </tbody>
					</table>
				    <br />
				    <?php
					if ($tooltip_locations_array && count($tooltip_locations_array)) {
						$show_legend = true;
						foreach ($tooltip_locations_array as $key => $tooltip_locations) {
							?>
							<div id="locations-cp-<?php echo $key; ?>" style="display: none;">
								<span class="content-small">
									<strong style="font-size: 9pt;">Setting mismatch:</strong>
									<p>
										This Clinical Procedure was logged in a <strong><?php echo $tooltip_locations["logged"]; ?></strong> Setting, but was required to be logged in a <strong><?php echo $tooltip_locations["required"]; ?></strong> Setting for this Rotation.
									</p>
								</span>
							</div>
							<?php
						}
					}
			    }
				$query = "SELECT COUNT(DISTINCT a.`leobjective_id`) AS `count`, a.`objective_id`, b.`llocation_id`, e.`objective_name` AS `objective`, k.`location_type_short` AS `logged_location`, m.`location_type_short` AS `backup_logged_location`, f.`number_required` as `required`
							FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_objectives` AS a
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_entries` AS b
							ON b.`lentry_id` = a.`lentry_id`
							AND b.`entry_active` = 1
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`events` AS c
							ON b.`rotation_id` = c.`event_id`
							LEFT JOIN `global_lu_objectives` AS e
							ON e.`objective_id` = a.`objective_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objectives` AS f
							ON e.`objective_id` = f.`objective_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objective_locations` AS g
							ON f.`lmobjective_id` = g.`lmobjective_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_location_types` AS h
							ON g.`lltype_id` = h.`lltype_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types` AS i
							ON h.`lltype_id` = i.`lltype_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_location_types` AS j
							ON b.`llocation_id` = j.`llocation_id`
							AND i.`lltype_id` = j.`lltype_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types` AS k
							ON j.`lltype_id` = k.`lltype_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_location_types` AS l
							ON b.`llocation_id` = l.`llocation_id`
							AND (i.`lltype_id` != l.`lltype_id` OR i.`lltype_id` IS NULL)
							AND j.`lltype_id` IS NULL
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types` AS m
							ON l.`lltype_id` = m.`lltype_id`
							WHERE b.`proxy_id` = ".$PROXY_ID."
							AND c.`rotation_id` != ".$db->qstr($rotation_id)."
							AND e.`objective_active` = '1'
							AND a.`objective_id` IN (
								SELECT `objective_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objectives` 
								WHERE `rotation_id` = ".$db->qstr($rotation_id)."
								AND `grad_year_min` <= ".$db->qstr($grad_year)."
								AND (`grad_year_max` >= ".$db->qstr($grad_year)." OR `grad_year_max` = 0)
							)
							".(CLERKSHIP_SETTINGS_REQUIREMENTS ? "AND i.`lltype_id` = j.`lltype_id`" : "")."
							GROUP BY a.`objective_id`".(CLERKSHIP_SETTINGS_REQUIREMENTS ? ", h.`lltype_id`" : "");
				$results = $db->GetAll($query);
			    if ($results) {
                    $tooltip_locations_array = array();
					?>
					<br />
					<table class="table table-striped" cellspacing="0" summary="Clinical Presentations for <?php echo $clinical_rotation["title"]; ?> encountered in other rotations">
					    <colgroup>
							<col style="width:3%" />
							<col style="width:<?php echo (CLERKSHIP_SETTINGS_REQUIREMENTS ? 52 : 67); ?>%;"/>
							<col style="width:15%;"  />
                            <?php
                            if (CLERKSHIP_SETTINGS_REQUIREMENTS) {
                                ?>
                                <col style="width:15%;"  />
                                <?php
                            }
                            ?>
							<col style="width:15%;" />
					    </colgroup>
					    <thead>
						<tr>
						    <th colspan="2">Clinical Presentations for <?php echo $clinical_rotation["title"]; ?> encountered in other rotations</th>
						    <th>Logged</th>
                            <?php
                            if (CLERKSHIP_SETTINGS_REQUIREMENTS) {
                                ?>
                                <th>Setting</th>
                                <?php
                            }
                            ?>
						    <th>Required</th>
						</tr>
					    </thead>
					    <tbody>
					    <?php
						foreach ($results as $result) {
                            $result["logged_location"] = (!isset($result["logged_location"]) || !$result["logged_location"] ? $result["backup_logged_location"] : $result["logged_location"]);
                            $query = "SELECT c.* FROM `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objectives` AS a
						    			JOIN `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objective_locations` AS b
						    			ON a.`lmobjective_id` = b.`lmobjective_id`
						    			JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types` AS c
						    			ON b.`lltype_id` = c.`lltype_id`
						    			WHERE a.`objective_id` = ".$db->qstr($result["objective_id"])."
						    			AND a.`rotation_id` = ".$db->qstr($rotation_id)."
										AND a.`grad_year_min` <= ".$db->qstr($grad_year)."
										AND (a.`grad_year_max` = 0 OR a.`grad_year_max` >= ".$db->qstr($grad_year).")
										GROUP BY c.`lltype_id`";
                            $locations = $db->GetAll($query);
                            $locations_array = array();
                            $location_string = "";
                            $location_string_long = "";
                            $count = 1;
                            foreach ($locations as $location) {
                                $locations_array[] = $location["location_type_short"];
                                $location_string .= ($location_string ? "/" : "").html_encode($location["location_type_short"]);
                                $location_string_long .= ($location_string_long ? ($count < count($locations) ? ", " : " or ") : "").html_encode($location["location_type"]);
                                $count++;
                            }

                            $correct_location = (CLERKSHIP_SETTINGS_REQUIREMENTS && array_search($result["logged_location"], $locations_array) === false && $location_string ? false : true);
                            if (!$correct_location) {
                                $tooltip_locations_array[$result["objective_id"]."-".$result["llocation_id"]] = array("required" => $location_string_long, "logged" => $LOCATIONS[$result["llocation_id"]]["location_types_string_long"]);
                            }
							echo "<tr>";
							echo "<td>&nbsp;</td>";
						    echo "<td>".$result["objective"]."</td>";
							echo "<td>".$result["count"]."</td>";
                            if (CLERKSHIP_SETTINGS_REQUIREMENTS) {
                                echo "<td".($correct_location ? "" : " class=\"incorrect-setting setting-tooltip\" id=\"tooltip-".$result["objective_id"]."-".$result["llocation_id"]."\" href=\"#locations-cp-".$result["objective_id"]."-".$result["llocation_id"]."\"").">".$LOCATIONS[$result["llocation_id"]]["location_types_string"].($correct_location ? "" : "")."</td>";
                            }
                            echo "<td>".(!$correct_location ? "0" : $result["required"])."</td>";
						    echo "</tr>";
						}
					    ?>
					    </tbody>
					</table>
				    <br />
				    <?php
                    if ($tooltip_locations_array && count($tooltip_locations_array)) {
                        $show_legend = true;
                        foreach ($tooltip_locations_array as $key => $tooltip_locations) {
                            ?>
                            <div id="locations-cp-<?php echo $key; ?>" style="display: none;">
								<span class="content-small">
									<strong style="font-size: 9pt;">Setting mismatch:</strong>
									<p>
                                        This Clinical Procedure was logged in a <strong><?php echo $tooltip_locations["logged"]; ?></strong> Setting, but was required to be logged in a <strong><?php echo $tooltip_locations["required"]; ?></strong> Setting for this Rotation.
                                    </p>
								</span>
                            </div>
                        <?php
                        }
                    }
			    }
			    $query = "SELECT COUNT(DISTINCT a.`leprocedure_id`) AS `count`, a.`lprocedure_id`, b.`llocation_id`, e.`number_required` AS `required`, f.`procedure`, i.`location_type_short` AS `required_location`, k.`location_type_short` AS `logged_location`, a.`level`
							FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_procedures` AS a
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_entries` AS b
							ON b.`lentry_id` = a.`lentry_id`
							AND b.`entry_active` = 1
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`events` AS c
							ON b.`rotation_id` = c.`event_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedures` AS e
							ON e.`rotation_id` = c.`rotation_id`
							AND e.`lprocedure_id` = a.`lprocedure_id`
							AND e.`grad_year_min` <= ".$db->qstr($grad_year)."
							AND (e.`grad_year_max` >= ".$db->qstr($grad_year)." OR e.`grad_year_max` = 0)
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_procedures` AS f
							ON f.`lprocedure_id` = a.`lprocedure_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedure_locations` AS g
							ON e.`lpprocedure_id` = g.`lpprocedure_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_location_types` AS h
							ON g.`lltype_id` = h.`lltype_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types` AS i
							ON h.`lltype_id` = i.`lltype_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_location_types` AS j
							ON b.`llocation_id` = j.`llocation_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types` AS k
							ON j.`lltype_id` = k.`lltype_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_location_types` AS l
							ON b.`llocation_id` = l.`llocation_id`
							AND (i.`lltype_id` NOT IN (SELECT `lltype_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedure_locations` WHERE `lpprocedure_id` = g.`lpprocedure_id`))
							AND (i.`lltype_id` != l.`lltype_id` OR i.`lltype_id` IS NULL)
							AND j.`lltype_id` IS NULL
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types` AS m
							ON l.`lltype_id` = m.`lltype_id`
							WHERE c.`rotation_id` = ".$db->qstr($rotation_id)."
							AND b.`proxy_id` = ".$PROXY_ID."
							GROUP BY a.`lprocedure_id`".(CLERKSHIP_SETTINGS_REQUIREMENTS ? ", m.`lltype_id`" : "");
			    $results = $db->GetAll($query);
			
			    if ($results) {
					$tooltip_locations_array = array();
				//     <div class="content-heading">Procedures List</div>
					?>
					<br />
					<table class="table table-striped" cellspacing="0" summary="Procedures List">
					    <colgroup>
							<col style="width:3%" />
							<col style="width:<?php echo (CLERKSHIP_SETTINGS_REQUIREMENTS ? 52 : 67); ?>%;"/>
							<col style="width:15%;"  />
                            <?php
                            if (CLERKSHIP_SETTINGS_REQUIREMENTS) {
                                ?>
                                <col style="width:15%;"  />
                                <?php
                            }
                            ?>
							<col style="width:15%;" />
					    </colgroup>
					    <thead>
						<tr>
						    <th colspan="2">Tasks Completed in <?php echo $clinical_rotation["title"]?></th>
						    <th>Logged</th>
                            <?php
                            if (CLERKSHIP_SETTINGS_REQUIREMENTS) {
                                ?>
                                <th>Setting</th>
                                <?php
                            }
                            ?>
						    <th>Required</th>
						</tr>
					    </thead>
					    <tbody>
					    <?php
						foreach ($results as $result) {
						    $query = "SELECT c.* FROM `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedures` AS a
						    			JOIN `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedure_locations` AS b
						    			ON a.`lpprocedure_id` = b.`lpprocedure_id`
						    			JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types` AS c
						    			ON b.`lltype_id` = c.`lltype_id`
						    			WHERE a.`lprocedure_id` = ".$db->qstr($result["lprocedure_id"])."
						    			AND a.`rotation_id` = ".$db->qstr($rotation_id)." 
										AND a.`grad_year_min` <= ".$db->qstr($grad_year)."
										AND (a.`grad_year_max` = 0 OR a.`grad_year_max` >= ".$db->qstr($grad_year).")
										GROUP BY c.`lltype_id`";
						    $locations = $db->GetAll($query);
						    $locations_array = array();
						    $location_string = "";
						    $location_string_long = "";
						    foreach ($locations as $location) {
						    	$locations_array[] = $location["location_type_short"];
						    	$location_string .= ($location_string ? "/" : "").html_encode($location["location_type_short"]);
						    	$location_string_long .= ($location_string_long ? ($count < count($locations) ? ", " : " or ") : "").html_encode($location["location_type"]);
						    }
							$correct_location = (CLERKSHIP_SETTINGS_REQUIREMENTS && array_search($result["logged_location"], $locations_array) === false && $location_string ? false : true);
							if (!$correct_location) {
								$tooltip_locations_array[$result["lprocedure_id"]."-".$result["llocation_id"]] = array("required" => $location_string_long, "logged" => $LOCATIONS[$result["llocation_id"]]["location_types_string_long"]);
							}
						    echo "<tr>";
						    echo "<td>&nbsp;</td>";
						    echo "<td>".$result["procedure"]."</td>";
							echo "<td>".$result["count"]."</td>";
                            if (CLERKSHIP_SETTINGS_REQUIREMENTS) {
    							echo "<td".($correct_location ? "" : " class=\"incorrect-setting setting-tooltip\" id=\"tooltip-".$result["lprocedure_id"]."-".$result["llocation_id"]."\" href=\"#locations-ct-".$result["lprocedure_id"]."-".$result["llocation_id"]."\"").">".$LOCATIONS[$result["llocation_id"]]["location_types_string"]."</td>";
                            }
						    echo "<td>".($correct_location ? $result["required"] : "0" )."</td>";
						    echo "</tr>";
						}
					    ?>
					    </tbody>
					</table>
				    <br />
				    <?php
					if ($tooltip_locations_array && count($tooltip_locations_array)) {
						$show_legend = true;
						foreach ($tooltip_locations_array as $key => $tooltip_locations) {
							?>
							<div id="locations-ct-<?php echo $key; ?>" style="display: none;">
								<span class="content-small">
									<strong style="font-size: 9pt;">Setting mismatch:</strong>
									<p>
										This Clinical Task was logged in a <strong><?php echo $tooltip_locations["logged"]; ?></strong> Setting, but was required to be logged in a <strong><?php echo $tooltip_locations["required"]; ?></strong> Setting for this Rotation.
									</p>
								</span>
							</div>
							<?php
						}
					}
			    }
				
				if ($show_legend) {
					$sidebar_html  = "<span class=\"content-small\">\n";
					$sidebar_html .= "Settings highlighted in this colour do not match the requirements for this Rotation: <img src=\"".ENTRADA_RELATIVE."/images/legend-not-accessible.gif\" alt=\"\" /><br /><br />\n";
					$sidebar_html .= "To view which setting type the Task or Presentation <em>is</em> required in, hover your mouse over the highlighted area.";
					$sidebar_html .= "</span>\n";

					new_sidebar_item("Setting Mismatches", $sidebar_html, "setting-legend", "open");
				}
				
				$query = "SELECT COUNT(a.`lprocedure_id`) AS `count`, a.`lprocedure_id`, b.`llocation_id`, e.`procedure`, f.`number_required`, k.`location_type_short` AS `logged_location`
							FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_procedures` AS a
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_entries` AS b
							ON b.`lentry_id` = a.`lentry_id`
							AND b.`entry_active` = 1
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`events` AS c
							ON b.`rotation_id` = c.`event_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_procedures` AS e
							ON e.`lprocedure_id` = a.`lprocedure_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedures` AS f
							ON e.`lprocedure_id` = f.`lprocedure_id`
							AND c.`rotation_id` = f.`rotation_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedure_locations` AS g
							ON f.`lpprocedure_id` = g.`lpprocedure_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_location_types` AS h
							ON g.`lltype_id` = h.`lltype_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types` AS i
							ON h.`lltype_id` = i.`lltype_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_location_types` AS j
							ON b.`llocation_id` = j.`llocation_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types` AS k
							ON j.`lltype_id` = k.`lltype_id`
							WHERE b.`proxy_id` = ".$PROXY_ID."
							AND f.`grad_year_min` <= ".$db->qstr($grad_year)."
							AND (f.`grad_year_max` = 0 OR f.`grad_year_max` >= ".$db->qstr($grad_year).")
							AND c.`rotation_id` != ".$db->qstr($rotation_id)."
							AND a.`lprocedure_id` IN 
							(
								SELECT `lprocedure_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedures` WHERE `rotation_id` = ".$db->qstr($rotation_id)."
							)
							".(CLERKSHIP_SETTINGS_REQUIREMENTS ? "AND i.`lltype_id` = j.`lltype_id`" : "")."
							GROUP BY a.`lprocedure_id`, k.`location_type_short`";
			    $results = $db->GetAll($query);
			
			    if ($results) {
				//     <div class="content-heading">Procedures List</div>
					?>
					<br />
					<table class="table table-striped" cellspacing="0" summary="Procedures List">
					    <colgroup>
							<col style="width:3%" />
							<col style="width:<?php echo (CLERKSHIP_SETTINGS_REQUIREMENTS ? 52 : 67); ?>%;"/>
							<col style="width:15%;"  />
                            <?php
                            if (CLERKSHIP_SETTINGS_REQUIREMENTS) {
                                ?>
                                <col style="width:15%;"  />
                                <?php
                            }
                            ?>
							<col style="width:15%;" />
					    </colgroup>
					    <thead>
						<tr>
						    <th colspan="2">Tasks Completed for <?php echo $clinical_rotation["title"]?> in other rotations</th>
						    <th>Logged</th>
                            <?php
                            if (CLERKSHIP_SETTINGS_REQUIREMENTS) {
                                ?>
                                <th>Setting</th>
                                <?php
                            }
                            ?>
						    <th>Required</th>
						</tr>
					    </thead>
					    <tbody>
					    <?php
						foreach ($results as $result) {
						    echo "<tr>";
						    echo "<td>&nbsp;</td>";
						    echo "<td>".$result["procedure"]."</td>";
						    echo "<td>".$result["count"]."</td>";
                            if (CLERKSHIP_SETTINGS_REQUIREMENTS) {
    						    echo "<td>".$LOCATIONS[$result["llocation_id"]]["location_types_string"]."</td>";
                            }
						    echo "<td>".$result["number_required"]."</td>";
						    echo "</tr>";
						}
					    ?>
					    </tbody>
					</table>
				    <br />
				    <?php
			    }
			    $procedures_required = 0;
			    $objectives_required = 0;
			    $objectives_recorded = 0;
			    $procedures_recorded = 0;
			    
				$query = "SELECT `objective_id`, `lmobjective_id`, MAX(`number_required`) AS `required`
							FROM `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objectives`
							WHERE `rotation_id` = ".$db->qstr($rotation_id)."
							AND `grad_year_min` <= ".$db->qstr($grad_year)."
							AND (`grad_year_max` >= ".$db->qstr($grad_year)." OR `grad_year_max` = 0)
							GROUP BY `objective_id`";
				$required_objectives = $db->GetAll($query);
				$lmobjective_ids = array();
                $objective_ids = "";
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
							if (isset($objective_ids) && $objective_ids) {
								$objective_ids .= ",".$db->qstr($required_objective["objective_id"]);
							} else {
								$objective_ids = $db->qstr($required_objective["objective_id"]);
							}
						}
					}
				}
				if (isset($objective_ids) && $objective_ids) {
					$query  = "SELECT * FROM `".DATABASE_NAME."`.`global_lu_objectives`
							WHERE `objective_id` IN (".$objective_ids.")
							AND `objective_active` = '1'
							ORDER BY `objective_name`";
					$results = $db->GetAll($query);
				} else {
					$results = false;
				}
				if ($results) {
					?>
					<br />
					<table class="table table-striped" cellspacing="0" summary="Missing Objectives">
					    <colgroup>
						<col style="width: 3%;"/>
						<col style="width: <?php echo (CLERKSHIP_SETTINGS_REQUIREMENTS ? 67 : 82); ?>%;"/>
                        <?php
                        if (CLERKSHIP_SETTINGS_REQUIREMENTS) {
                            ?>
                            <col style="width: 15%;"/>
                            <?php
                        }
                        ?>
						<col style="width: 15%;"/>
						<col/>
					    </colgroup>
					    <thead>
						<tr>
						    <th colspan="2">Missing Clinical Presentations</th>
                            <?php
                            if (CLERKSHIP_SETTINGS_REQUIREMENTS) {
                                ?>
                                <th>Setting</th>
                                <?php
                            }
                            ?>
						    <th>Number Missing</th>
						</tr>
					    </thead>
					    <tbody>
					<?php
						foreach ($results as $result) {
						    $click_url	= ENTRADA_URL."/clerkship?core=".$rotation_id;
                            if (isset($lmobjective_ids[$result["objective_id"]])) {
                                $query = "SELECT b.* FROM `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objective_locations` AS a
                                            JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types` AS b
                                            ON a.`lltype_id` = b.`lltype_id`
                                            WHERE a.`lmobjective_id` = ".$db->qstr($lmobjective_ids[$result["objective_id"]]);
                                $locations = $db->GetAll($query);
                                if (!$locations) {
                                    $query = "SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types`";
                                    $locations = $db->GetAll($query);
                                }
                                $location_string = "";
                                if ($locations) {
                                    foreach ($locations as $location) {
                                        $location_string .= ($location_string ? "/" : "").html_encode($location["location_type_short"]);
                                    }
                                }
                                echo "<tr>";
                                echo "<td>&nbsp;</td>";
                                echo "<td>".($result["objective_name"] ? limit_chars(html_decode($result["objective_name"]), 55, true, false) : "&nbsp;")."</td>";
                                if (CLERKSHIP_SETTINGS_REQUIREMENTS) {
                                    echo "<td>".($location_string ? $location_string."" : "")."</td>";
                                }
                                echo "<td>".$number_required[$result["objective_id"]]."</td>";
                                echo "</tr>";
                            }
						}
					    ?>
						</tbody>
					</table>
				    <br />
				    <?php
			    }
				$query = "SELECT `lprocedure_id`, `lpprocedure_id`, MAX(`number_required`) AS `required`
							FROM `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedures`
							WHERE `rotation_id` = ".$db->qstr($rotation_id)."
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
									".(CLERKSHIP_SETTINGS_REQUIREMENTS ? "
									JOIN `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedure_locations` AS b
									ON b.`lpprocedure_id` = ".$db->qstr($required_procedure["lpprocedure_id"])."
									JOIN `".CLERKSHIP_DATABASE."`.`logbook_location_types` AS c
									ON b.`lltype_id` = c.`lltype_id`
									AND a.`llocation_id` = c.`llocation_id`
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
                if (isset($procedure_ids) && $procedure_ids) {
                    $query  = "	SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_lu_procedures`
                                WHERE `lprocedure_id` IN (".$procedure_ids.")
                                ORDER BY `procedure`";
                    $results = $db->GetAll($query);

                    if ($results) {
                        ?>
                        <br />
                        <table class="table table-striped" cellspacing="0" summary="Missing procedures">
                            <colgroup>
                            <col style="width: 3%;"/>
                            <col style="width: <?php echo (CLERKSHIP_SETTINGS_REQUIREMENTS ? 67 : 82); ?>%;"/>
                            <?php
                            if (CLERKSHIP_SETTINGS_REQUIREMENTS) {
                                ?>
                                <col style="width: 15%;"/>
                                <?php
                            }
                            ?>
                            <col style="width: 15%;"/>
                            </colgroup>
                            <thead>
                            <tr>
                                <th colspan="2">Missing Clinical Tasks</th>
                                <?php
                                if (CLERKSHIP_SETTINGS_REQUIREMENTS) {
                                    ?>
                                    <th>Setting</th>
                                    <?php
                                }
                                ?>
                                <th>Number Missing</th>
                            </tr>
                            </thead>
                            <tbody>
                        <?php
                            foreach ($results as $result) {
                                $query = "SELECT b.* FROM `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedure_locations` AS a
                                            JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_location_types` AS b
                                            ON a.`lltype_id` = b.`lltype_id`
                                            WHERE a.`lpprocedure_id` = ".$db->qstr($lpprocedure_ids[$result["lprocedure_id"]]);
                                $locations = $db->GetAll($query);
                                $location_string = "";
                                foreach ($locations as $location) {
                                    $location_string .= ($location_string ? "/" : "").html_encode($location["location_type_short"]);
                                }
                                $click_url	= ENTRADA_URL."/clerkship?core=".$rotation_id;
                                echo "<tr>";
                                echo "<td>&nbsp;</td>";
                                echo "<td class=\"phase\">".limit_chars(html_decode($result["procedure"]), 55, true, false)."</td>";
                                if (CLERKSHIP_SETTINGS_REQUIREMENTS) {
                                    echo "<td>".($location_string ? $location_string."" : "")."</td>";
                                }
                                echo "<td>".$number_required[$result["lprocedure_id"]]."</td>";
                                echo "</tr>";
                            }
                            ?>
                            </tbody>
                        </table>
                        <br />
                        <?php
                    }
                }
				
				?>
                <h3>Summary</h3>
                <div style="width: 80%;">
                    <?php if (isset($_GET["id"]) && $_GET["id"]) { ?>
                        <?php echo $fullname; ?> has logged <?php echo $objectives_recorded; ?> of the <?php echo $objectives_required; ?> required <strong>Clinical Presentations</strong> and <?php echo $procedures_recorded; ?> of the <?php echo $procedures_required; ?> required <strong>Clinical Tasks</strong> for this rotation. 
                    <?php } else { ?>
                        You have logged <?php echo $objectives_recorded; ?> of the <?php echo $objectives_required; ?> required <strong>Clinical Presentations</strong> and <?php echo $procedures_recorded; ?> of the <?php echo $procedures_required; ?> required <strong>Clinical Tasks</strong> for this rotation. 
                    <?php } ?>
                </div>
                <br />
				<?php
				echo "<h2 title=\"Rotation Comments Section\">Discussions &amp; Comments</h2>\n";
				
				if (defined("NOTIFICATIONS_ACTIVE") && NOTIFICATIONS_ACTIVE) {
					?>
					<div id="notifications-toggle" style="display: inline; padding-top: 4px; width: 100%; text-align: right;"></div>
					<br /><br />
					<script type="text/javascript">
					function promptNotifications(enabled) {
						Dialog.confirm('Do you really wish to '+ (enabled == 1 ? "stop" : "begin") +' receiving notifications when new comments are made on this rotation?',
							{
								id:				'requestDialog',
								width:			350,
								height:			75,
								title:			'Notification Confirmation',
								className:		'medtech',
								okLabel:		'Yes',
								cancelLabel:	'No',
								closable:		'true',
								buttonClass:	'btn btn-small',
								destroyOnClose:	true,
								ok:				function(win) {
													new Window(	{
																	id:				'resultDialog',
																	width:			350,
																	height:			75,
																	title:			'Notification Result',
																	className:		'medtech',
																	okLabel:		'close',
																	buttonClass:	'btn btn-small',
																	resizable:		false,
																	draggable:		false,
																	minimizable:	false,
																	maximizable:	false,
																	recenterAuto:	true,
																	destroyOnClose:	true,
																	url:			'<?php echo ENTRADA_URL."/api/notifications.api.php?record_id=".$rotation_id."&record_proxy_id=".$PROXY_ID; ?>&content_type=logbook_rotation&action=edit&active='+(enabled == 1 ? '0' : '1'),
																	onClose:			function () {
																						new Ajax.Updater('notifications-toggle', '<?php echo ENTRADA_URL."/api/notifications.api.php?record_id=".$rotation_id."&record_proxy_id=".$PROXY_ID; ?>&content_type=logbook_rotation&action=view');
																					}
																}
													).showCenter();
													return true;
												}
							}
						);
					}
					</script>
					<?php
					$ONLOAD[] = "new Ajax.Updater('notifications-toggle', '".ENTRADA_URL."/api/notifications.api.php?record_id=".$rotation_id."&record_proxy_id=".$PROXY_ID."&content_type=logbook_rotation&action=view')";
				}
				echo "<div id=\"rotation-comments-section\">\n";
	
				$query = "	SELECT * FROM `".CLERKSHIP_DATABASE."`.`logbook_rotation_comments`
							WHERE `clerk_id` = ".$db->qstr($PROXY_ID)."
							AND `comments` <> ''
							AND `comment_active` = '1'
							AND `rotation_id` = ".$db->qstr($rotation_id)."
							ORDER BY `lrcomment_id` ASC";
				
				$ROTATION_DISCUSSION = $db->GetAll($query);
				
				$editable	= false;
				$edit_ajax	= array();
				if($ROTATION_DISCUSSION) {
					$i = 0;
					foreach($ROTATION_DISCUSSION as $result) {
						$poster_name = get_account_data("firstlast", $result["proxy_id"]);
	
						echo "<div class=\"discussion\"".(($i % 2) ? " style=\"background-color: #F3F3F3\"" : "").">\n";
						echo "	<div class=\"content-small\"><strong>".get_account_data("firstlast", $result["proxy_id"])."</strong>, ".date(DEFAULT_DATE_FORMAT, $result["updated_date"])."</div>\n";
						echo "	<div class=\"discussion-comment\" id=\"discussion_comment_".$result["lrcomment_id"]."\">".html_encode($result["comments"])."</div>\n";
						echo "</div>\n";
	
						$i++;
					}
				} else {
					echo "<div class=\"content-small\">There are no comments or discussions on this event. <strong>Start a conversation</strong>, leave your comment below.</div>\n";
				}
				echo "	<br /><br />";
				echo "	<div class=\"no-printing\">\n";
				echo "		<form action=\"".ENTRADA_URL."/clerkship/logbook?".replace_query(array("step" => 2))."\" method=\"post\">\n";
				echo "			<label for=\"discussion_comment\" class=\"content-subheading\">Leave a Comment</label>\n";
				echo "			<div class=\"content-small\">Posting comment as <strong>".$_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]."</strong></div>\n";
				echo "			<textarea id=\"discussion_comment\" name=\"discussion_comment\" cols=\"85\" rows=\"10\" style=\"width: 100%; height: 135px\"></textarea>\n";
				echo "			<div style=\"text-align: right; padding-top: 8px\"><input type=\"submit\" class=\"btn btn-primary\" value=\"Submit\" /></div>\n";
				echo "		</form>\n";
				echo "	</div>\n";
				echo "</div>\n";
			break;
		}
	} else {
		$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";
	
		$ERROR++;
		$ERRORSTR[]	= "Your account does not have the permissions required to view clerk information for this rotation.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";
	
		echo display_error();
	
		application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this rotation [".$rotation_id."] in this module [".$MODULE."]");
	}
}
?>