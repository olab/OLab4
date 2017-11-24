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
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CLERKSHIP")) || (!defined("IN_ELECTIVES"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('electives', 'update')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	// ERROR CHECKING
	switch ($STEP) {
		case "2" :
			if ($_POST) {
				// Required
				if ((!@is_array($_POST["ids"])) && (@count($_POST["ids"]) == 0)) {
					$ERROR++;
					$ERRORSTR[] = "You must select a user to add this event to. Please be sure that you select at least one user to add this event to from the interface.";
				}
				
				if (strlen(trim($_POST["category_id"])) < 1) {
					$ERROR++;
					$ERRORSTR[] = "You must select a child category for this event to take place in.";	
				} else {
					if (clerkship_categories_children_count(trim($_POST["category_id"])) > 0) {
						$ERROR++;
						$ERRORSTR[] = "The category that you have selected for this event to take place in is a parent category, meaning it has further categories underneath it (see -- Select Category -- box). Please make sure the category that you select is a child category.";
					} else {
						$PROCESSED["category_id"] = trim($_POST["category_id"]);
					}
				}

				if (isset($_POST["rotation_id"]) && ($rotation_id = clean_input($_POST["rotation_id"], array("trim", "int")))) {
					$query = "SELECT `rotation_id` FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations` WHERE `rotation_id` = ".$db->qstr($rotation_id);
					$result	= $db->GetRow($query);
					if ($result) {
						$PROCESSED["rotation_id"] = (int) $result["rotation_id"];
					} else {
						$ERROR++;
						$ERRORSTR[] = "We were unable to locate the event rotation you've selected.";
					}
				} else {
					$ERROR++;
					$ERRORSTR[] = "You must select a event rotation to continue..";
				}

				if (isset($_POST["region_id"]) && ($region_id = clean_input($_POST["region_id"], array("trim", "int")))) {
					$query = "SELECT * FROM `".CLERKSHIP_DATABASE."`.`regions` WHERE `region_id` = ".$db->qstr($region_id);
					$result	= $db->GetRow($query);
					if ($result) {
						$PROCESSED["region_id"] = (int) $result["region_id"];

						if ((int) $result["manage_apartments"]) {
							$PROCESSED["requires_apartment"] = 1;
						} else {
							$PROCESSED["requires_apartment"] = 0;
						}
					} else {
						$ERROR++;
						$ERRORSTR[] = "We were unable to locate the event location you selected. Please select a valid location or if your desired location is not in the list visit the <a href=\"".ENTRADA_URL."/admin/regionaled/regions\" target=\"_blank\">Manage Regions</a> section and locate the region in that list, click on it, and then select the &quot;Clerkship core rotations can take place in this region&quot; check box.";
					}
				} else {
					$ERROR++;
					$ERRORSTR[] = "You must select an event location for this event.";
				}
				
				if (strlen(trim($_POST["event_title"])) < 1) {
					$ERROR++;
					$ERRORSTR[] = "You must enter a title for this event or choose the auto generated one.";	
				} else {
					$PROCESSED["event_title"] = trim($_POST["event_title"]);
				}

				// Not Required
				if (isset($_POST["event_desc"]) && strlen(trim($_POST["event_desc"])) > 0) {
					$PROCESSED["event_desc"] = trim($_POST["event_desc"]);
				} else {
					$PROCESSED["event_desc"] = "";
				}

				$event_dates = validate_calendars("event", true, true);
				if ((isset($event_dates["start"])) && ((int) $event_dates["start"])) {
					$PROCESSED["event_start"] = (int) $event_dates["start"];
				} else {
					$ERROR++;
					$ERRORSTR[] = "The <strong>Event Start</strong> field is required if this is to appear on the calendar.";
				}
		
				if ((isset($event_dates["finish"])) && ((int) $event_dates["finish"])) {
					$PROCESSED["event_finish"] = (int) $event_dates["finish"];
				} else {
					$ERROR++;
					$ERRORSTR[] = "The <strong>Event Finish</strong> field is required if this is to appear on the calendar.";
				}

				if (strlen(trim($_POST["event_status"])) < 1) {
					$ERROR++;
					$ERRORSTR[] = "Please select the status of this category after you have saved it.";
				} else {
					if (!@array_key_exists($_POST["event_status"], $CLERKSHIP_FIELD_STATUS)) {
						$ERROR++;
						$ERRORSTR[] = "The category &quot;Save State&quot; that you've selected no longer exists as an acceptable state. Please choose a new state for this category.";
					} else {
						$PROCESSED["event_status"] = $_POST["event_status"];
					}
				}

				if (!$ERROR) {
					$PROCESSED["modified_last"]	= time();
					$PROCESSED["modified_by"] = $ENTRADA_USER->getID();
                    foreach($_POST["ids"] as $user_id) {
                        if (!$db->AutoExecute("`".CLERKSHIP_DATABASE."`.`events`", $PROCESSED, "INSERT")) {
                            $ERROR++;
                            $ERRORSTR[]	= "Failed insert this event into the database. Please contact a system administrator if this problem persists.";
                            application_log("error", "Error while inserting clerkship event into database. Database server said: ".$db->ErrorMsg());
                            $STEP		= 1;
                        } else {
                            $EVENT_ID = $db->Insert_Id();
                            if ($EVENT_ID) {
                                if (!$db->AutoExecute("`".CLERKSHIP_DATABASE."`.`event_contacts`", array("event_id" => $EVENT_ID, "econtact_type" => "student", "etype_id" => $user_id), "INSERT")) {
                                    $ERROR++;
                                    $ERRORSTR[]	= "Failed to assign this event to user ID ".$user_id.". Please contact a system administrator if this problem persists.";
                                    application_log("error", "Error while inserting clerkship event contact into database. Database server said: ".$db->ErrorMsg());
                                    $STEP		= 1;
                                }
                            } else {
                                $ERROR++;
                                $ERRORSTR[]	= "Failed insert this event into the database. Please contact a system administrator if this problem persists.";
                                application_log("error", "Error while inserting clerkship event into database. Database server said: ".$db->ErrorMsg());
                                $STEP		= 1;
                            }
                        }
                    }
				} else {
					$STEP = 1;
				}
			}
		break;
		default :
			// No error checking for step 1.
		break;	
	}
	
	// PAGE DISPLAY
	switch ($STEP) {
		case "2" :			// Step 2
			$ONLOAD[] = "setTimeout('window.location=\'".ENTRADA_URL."/admin/clerkship/clerk?ids=".$_POST["ids"][0]."\'', 5000)";

			$SUCCESS++;
			$SUCCESSSTR[] = "You have successfully added this event to ".@count($_POST["ids"])." student".((@count($_POST["ids"]) != "1") ? "s calendars.<br /><br />You will now be redirected to the first students calendar." : " and you're being redirected back to their calendar.")."<br /><br />If you do not wish to wait, please <a href=\"".ENTRADA_URL."/admin/clerkship/clerk?ids=".$_POST["ids"][0]."\">click here</a>.";

			echo display_success($SUCCESSSTR);
		break;
		default :			// Step 1
			$user_ids = array();
			if (isset($_GET["ids"])) {
				if (strlen(trim($_GET["ids"])) > 0) {
					$tmp_ids = explode(",", trim($_GET["ids"]));
					foreach($tmp_ids as $tmp_id) {
						$user_ids[] = (int) trim($tmp_id);	
					}
				}
			} elseif (@is_array($_POST["ids"])) {
				foreach($_POST["ids"] as $tmp_id) {
					$user_ids[] = (int) trim($tmp_id);	
				}
			}

			if (count($user_ids) == 1) {
				$student_name = get_account_data("firstlast", $user_ids[0]);
			} else {
				$student_name = "Multiple Students";
			}

			$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/clerkship".(count($user_ids) == 1 ? "/clerk?ids=".$user_ids[0] : ""), "title" => $student_name);
			$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/clerkship/electives?section=add_core&ids=".(isset($_GET["ids"]) && $_GET["ids"] ? $_GET["ids"] : (is_array($_POST["ids"]) ? implode(",", $_POST["ids"]) : $_POST["ids"])), "title" => "Add Core");
			
			if (isset($_POST["category_id"])) {
				$CATEGORY_ID	= (int) trim($_POST["category_id"]);
			} elseif (isset($_COOKIE["calendar_management"]["category_id"])) {
				$CATEGORY_ID	= (int) trim($_COOKIE["calendar_management"]["category_id"]);
			} else {
				$CATEGORY_ID	= 0;
			}
			
			$HEAD[]	= "	<script type=\"text/javascript\">
						function selectCategory(category_id) {
							new Ajax.Updater('selectCategoryField', '".ENTRADA_URL."/api/category-in-select.api.php', {parameters: {'cid': category_id}});
                            new Ajax.Updater('hidden_rotation_id', '".ENTRADA_URL."/api/category-rotation.api.php', 
                            {
                                parameters: 
                                {
                                    'cid': category_id
                                }, 
                                onComplete: function()
                                { 
                                    jQuery(\"#rotation option[value=\'\"+$('hidden_rotation_id').innerHTML+\"\']\").attr('selected', 'selected');
                                }
                            });

                            return;
						}
						</script>";

			$DECODE_HTML_ENTITIES = true;
			
			$ONLOAD[] = "selectCategory(".($CATEGORY_ID ? $CATEGORY_ID : "0").")";
			
			?>
			<span class="content-heading">Adding Core Rotation</span>
			<br /><br />
			<?php echo (($ERROR) ? display_error($ERRORSTR) : ""); ?>

			<form action="<?php echo ENTRADA_URL; ?>/admin/clerkship/electives?section=add_core&step=2" method="post" id="addEventForm">
				<input type="hidden" id="step" name="step" value="1" />
				<input type="hidden" id="category_id" name="category_id" value="" />
				<table width="100%" cellspacing="0" cellpadding="2" border="0">
					<colgroup>
						<col style="width: 3%" />
						<col style="width: 20%" />
						<col style="width: 77%" />
					</colgroup>
					<tbody>
						<tr>
							<td style="vertical-align: top; border-right: 10px #CCCCCC solid" colspan="2"><span class="form-nrequired">Student Name<?php echo ((@count($user_ids) != 1) ? "s" : ""); ?></span></td>
							<td style="width: 75%; padding-left: 5px">
								<?php
								foreach($user_ids as $user_id) {
									$query	= "SELECT CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname` 
                                                    FROM `".AUTH_DATABASE."`.`user_data` AS a
                                                    LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
                                                    ON b.`user_id` = a.`id` 
                                                    AND b.`group` = 'student'
                                                    WHERE a.`id` = ".$db->qstr($user_id);
									$result	= $db->GetRow($query);
									if ($result) {
										echo "<a href=\"".ENTRADA_URL."/admin/clerkship/clerk?ids=".$user_id."\" style=\"font-weight: bold\">".html_encode($result["fullname"])."</a><br />";
										echo "<input type=\"hidden\" name=\"ids[]\" value=\"".$user_id."\" />\n";
									}
								}
								?>
							</td>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td colspan="2" style="vertical-align: top"><label for="region_id" class="form-required">Event Region</label></td>
							<td>
								<select id="region_id" name="region_id" style="width: 75%">
								<option value="">-- Select Rotation Location --</option>
								<?php
								$region_query = "	SELECT a.`region_id`, a.`region_name`, b.`province`, c.`country`
													FROM `".CLERKSHIP_DATABASE."`.`regions` AS a
													LEFT JOIN `global_lu_provinces` AS b
													ON b.`province_id` = a.`province_id`
													LEFT JOIN `global_lu_countries` AS c
													ON c.`countries_id` = a.`countries_id`
													WHERE a.`is_core` = '1'
													AND a.`region_active` = '1'
													GROUP BY a.`region_name`
													ORDER BY a.`region_name` ASC";
								$region_results	= $db->GetAll($region_query);
								if ($region_results) {
									foreach($region_results as $region_result) {
										$region_name = array();
										$region_name[] = $region_result["region_name"];

										if ($region_result["province"]) {
											$region_name[] = $region_result["province"];
										}

										if ($region_result["country"]) {
											$region_name[] = $region_result["country"];
										}

										echo "<option value=\"".(int) $region_result["region_id"]."\"".((isset($_POST["region_id"]) && $_POST["region_id"] == $region_result["region_id"]) ? " selected=\"selected\"" : "").">".html_encode(implode(", ", $region_name))."</option>\n";
									}
								}
								?>
								</select>
								<div class="content-small" style="margin-top: 5px">
									<strong>Please Note:</strong> If the location is not in this list, visit the <a href="<?php echo ENTRADA_URL; ?>/admin/regionaled/regions" target="_blank" style="font-size: 11px">Manage Regions</a> section and locate the region in that list, click on it, and then select the &quot;Clerkship core rotations can take place in this region&quot; check box.
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td colspan="2"><label for="event_title" class="form-required">Event Title:</label></td>
							<td><input type="text" id="event_title" name="event_title" style="width: 75%" value="<?php echo html_decode((isset($PROCESSED["event_title"]) && $PROCESSED["event_title"] ? $PROCESSED["event_title"] : "")); ?>" /><div style="display: none;" id="hidden_event_title"><?php echo html_decode($PROCESSED["event_title"]) ?></div></td>
						</tr>
						<tr>
							<td colspan="2"><label class="form-required">Event Rotation</label></td>
							<td>
								<select id="rotation" name="rotation_id" style="width: 75%">
								<?php
								$query = "	SELECT *
											FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations`
											ORDER BY `rotation_title` ASC";
								$results = $db->GetAll($query);
								if ($results) {
									foreach($results as $result) {
										echo "<option value=\"".(int) $result["rotation_id"]."\"".((isset($_POST["rotation_id"]) && $_POST["rotation_id"] == $result["rotation_id"]) ? " selected=\"selected\"" : "").">".html_encode($result["rotation_title"])."</option>\n";
									}
								}
								?>
								</select>
								<div id="hidden_rotation_id" style="display: none;"><?php echo $PROCESSED["rotation_id"]; ?>"</div>
							</td>
						</tr>
						<tr>
							<td style="vertical-align: top; padding-top: 15px" colspan="2"><label for="category_id" class="form-required">Event Takes Place In:</label></td>
							<td style="vertical-align: top"><div id="selectCategoryField"></div></td>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<?php
							if (isset($event_dates)) {
								$event_start = $PROCESSED["event_start"];
								$event_finish = $PROCESSED["event_finish"];
							}

							echo generate_calendars("event", "", true, true, ((isset($event_start)) ? $event_start : time()), true, true, ((isset($event_finish)) ? $event_finish : 0));
						?>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td colspan="3" style="vertical-align: top"><label for="event_desc" class="form-nrequired">Administrative notes about this rotation</label></td>
						</tr>
						<tr>
							<td colspan="3"><textarea id="event_desc" name="event_desc" style="width: 82%; height: 75px"><?php echo (isset($PROCESSED["event_desc"]) && $PROCESSED["event_desc"] ? html_encode($PROCESSED["event_desc"]) : ""); ?></textarea></td>
						</tr>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td colspan="2"><label for="event_status" class="form-required">Save State</label></td>
							<td>
								<select id="event_status" name="event_status" style="width: 150px">
									<?php
									foreach($CLERKSHIP_FIELD_STATUS as $key => $status) {
										echo (($status["visible"]) ? "<option value=\"".$key."\"".((isset($_POST["event_status"]) && $_POST["event_status"] == $key) ? " selected=\"selected\"" : "").">".$status["name"]."</option>\n" : "");
									}
									?>
								</select>
							</td>
						</tr>
						<?php if (@count($_POST["ids"]) > 1) : ?>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td style="vertical-align: top" colspan="2"><span class="form-required">Addition Style:</span></td>
							<td>
								<input type="radio" id="add_type_m" name="add_type" value="multiple" style="vertical-align: middle" checked="checked" /> <label for="add_type_m" class="form-nrequired">Add new event for every student.</label><br />
								<input type="radio" id="add_type_s" name="add_type" value="single" style="vertical-align: middle" /> <label for="add_type_s" class="form-nrequired">Add all students to the same event.</label>
							</td>
						</tr>
						<?php endif; ?>
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<td colspan="3" style="text-align: right">
								<input type="button" value="Cancel" class="btn" onClick="window.location='<?php echo ENTRADA_URL; ?>/admin/clerkship/clerk?ids=<?php echo $user_ids[0] ?>'" />
								<input type="submit" value="Save" class="btn btn-primary" />
							</td>
						</tr>
					</tbody>
				</table>
			</form>
			<?php
		break;	
	}
}
