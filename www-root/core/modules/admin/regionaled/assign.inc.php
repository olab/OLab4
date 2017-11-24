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
 * This file is used when a learner is being assigned to an available apartment.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */

if (!defined("IN_REGIONALED")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("regionaled", "update", false)) {
	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/regionaled", "title" => "Assign Accommodations");
	?>
	<h1>Assign Accommodations</h1>
	<?php

	$event_id = 0;

	if (isset($_GET["id"]) && ($tmp_input = clean_input($_GET["id"], array("trim", "int")))) {
		$event_id = $tmp_input;
	}

	if ($event_id) {
		$query = "	SELECT a.*, b.`etype_id` AS `proxy_id`, d.`region_name`, e.`number`, e.`prefix`, e.`firstname`, e.`lastname`, e.`email`, e.`gender`, e.`privacy_level`, f.`group`, f.`role`, IF(f.`group` = 'student', 'Clerk', 'Resident') AS `learner_type`
					FROM `".CLERKSHIP_DATABASE."`.`events` AS a
					LEFT JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS b
					ON b.`event_id` = a.`event_id`
					LEFT JOIN `".CLERKSHIP_DATABASE."`.`apartment_schedule` AS c
					ON c.`event_id` = a.`event_id`
					LEFT JOIN `".CLERKSHIP_DATABASE."`.`regions` AS d
					ON d.`region_id` = a.`region_id`
					LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS e
					ON e.`id` = b.`etype_id`
					LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS f
					ON f.`user_id` = e.`id`
					AND f.`app_id` = ".$db->qstr(AUTH_APP_ID)."
					WHERE a.`event_id` = ".$db->qstr($event_id);
		$event_info = $db->GetRow($query);
		if ($event_info) {

			$apartment_ids = array();
			
			$query = "	SELECT a.`apartment_id`
						FROM `".CLERKSHIP_DATABASE."`.`apartments` a						
						JOIN `".CLERKSHIP_DATABASE."`.`apartment_contacts` b
						ON b.`apartment_id` = a.`apartment_id`
						WHERE a.`region_id` = ".$db->qstr($event_info["region_id"])."
						AND (a.`available_start` = '0' OR a.`available_start` <= ".$db->qstr(time()).")
						AND (a.`available_finish` = '0' OR a.`available_finish` > ".$db->qstr(time()).")
						AND b.`proxy_id` = " . $db->qstr($ENTRADA_USER->getId());
			$apartments = $db->GetAll($query);
			if ($apartments) {
				foreach ($apartments as $apartment) {
					$apartment_ids[] = $apartment["apartment_id"];
				}
			}

			switch ($STEP) {
				case 2 :
					if (isset($_POST["require_confirmation"]) && ((int) $_POST["require_confirmation"] == 1)) {
						$PROCESSED["confirmed"] = 0;
					} else {
						$PROCESSED["confirmed"] = 1;
					}

					if (isset($_POST["apartment_id"]) && ($tmp_input = clean_input($_POST["apartment_id"], "int"))) {
						$PROCESSED["apartment_id"] = $tmp_input;

						$query = "	SELECT a.*, g.`department_id`, g.`department_title`
									FROM `".CLERKSHIP_DATABASE."`.`apartments` a
									JOIN `".CLERKSHIP_DATABASE."`.`apartment_contacts` AS f
									ON a.`apartment_id` = f.`apartment_id`
									JOIN `".AUTH_DATABASE."`.`departments` AS g
									ON f.`department_id` = g.`department_id`
									WHERE a.`apartment_id` = ".$db->qstr($PROCESSED["apartment_id"]) . "
									AND f.`proxy_id` = " . $db->qstr($ENTRADA_USER->getId());
						$APARTMENT_INFO = $db->GetRow($query);
						if ($APARTMENT_INFO) {
							if ($APARTMENT_INFO["region_id"] == $event_info["region_id"]) {
								$availability = regionaled_apartment_availability($PROCESSED["apartment_id"], $event_info["event_start"], $event_info["event_finish"]);
								if ($availability["openings"] <= 0) {
									$ERROR++;
									$ERRORSTR[]	= "The selected apartment has no availability between <strong>".date("Y-m-d", $event_info["event_start"])."</strong> and <strong>".date("Y-m-d", $event_info["event_finish"])."</strong>.";
								}
							} else {
								$ERROR++;
								$ERRORSTR[]	= "The selected apartment is not in the same region as the event the learner is scheduled for.";
							}
						}
					} else {
						$ERROR++;
						$ERRORSTR[] = "Please select the apartment that you would like ".html_encode($event_info["firstname"]." ".$event_info["lastname"])." to be assigned to.";
					}

					if (!$ERROR) {
						$PROCESSED["event_id"] = $event_info["event_id"];
						$PROCESSED["proxy_id"] = $event_info["proxy_id"];
						$PROCESSED["occupant_type"] = (($event_info["group"] == "student") ? "undergrad" : (($event_info["group"] == "resident") ? "postgrad" : "other"));

						$PROCESSED["cost_recovery"] = 0;
						$PROCESSED["inhabiting_start"] = $event_info["event_start"];
						$PROCESSED["inhabiting_finish"] = $event_info["event_finish"];
						$PROCESSED["updated_last"] = time();
						$PROCESSED["updated_by"] = $ENTRADA_USER->getID();
						$PROCESSED["aschedule_status"] = "published";

						if ($db->AutoExecute(CLERKSHIP_DATABASE.".apartment_schedule", $PROCESSED, "INSERT") && ($aschedule_id = $db->Insert_Id())) {
							if (!$db->AutoExecute(CLERKSHIP_DATABASE.".events", array("requires_apartment" => 0), "UPDATE", "event_id=".$db->qstr($event_info["event_id"]))) {
								$NOTICE++;
								$NOTICESSTR[] = "We were unable to remove this learners entry from the " . $APARTMENT_INFO["department_title"] . " dashboard.";

								application_log("error", "Unable to set requires_apartment to 0 for event_id [".$PROCESSED["event_id"]."] after an apartment had been assigned. Database said: ".$db->ErrorMsg());
							}

							/**
							 * Send notification to the learner that they are required to confirm their apartment status.
							 */
							if ($PROCESSED["proxy_id"] && !$PROCESSED["confirmed"]) {
								$recipient = array (
									"email" => $event_info["email"],
									"firstname" => $event_info["firstname"],
									"lastname" => $event_info["lastname"]
								);

								$message_variables = array (
									"to_firstname" => $recipient["firstname"],
									"to_lastname" => $recipient["lastname"],
									"from_firstname" => $_SESSION["details"]["firstname"],
									"from_lastname" => $_SESSION["details"]["lastname"],
									"region" => $event_info["region_name"],
									"confirmation_url" => ENTRADA_URL."/regionaled/view?id=".$aschedule_id,
									"application_name" => APPLICATION_NAME,
									"department_title" => $APARTMENT_INFO["department_title"],
									"department_id" => $APARTMENT_INFO["department_id"]
								);

								regionaled_apartment_notification("confirmation", $recipient, $message_variables);
							}

						} else {
							$ERROR++;
							$ERRORSTR[] = "Unable to schedule this occupant into this apartment at this time. The system administrator has been informed of the error, please try again later.";

							application_log("error", "Unable to schedule an occupant into apartment_id [".$APARTMENT_ID."]. Database said: ".$db->ErrorMsg());
						}
					}

					if ($ERROR) {
						$STEP = 1;
					}
				break;
				default :
					continue;
				break;
			}

			// Page Dipslay
			switch ($STEP) {
				case 2 :
					$ONLOAD[] = "setTimeout('window.location=\'".ENTRADA_URL."/admin/regionaled\'', 5000)";

					$SUCCESS++;
					$SUCCESSSTR[] = "You have successfully assigned <strong>".html_encode($event_info["firstname"]." ".$event_info["lastname"])."</strong> to <strong>".html_encode($APARTMENT_INFO["apartment_title"])."</strong> during the <strong>".html_encode($event_info["event_title"])."</strong> rotation.<br /><br />You will be automatically redirected back to the " . $APARTMENT_INFO["department_title"] . " dashboard in 5 seconds, or <a href=\"".ENTRADA_URL."/admin/regionaled\">click here</a> if you do not wish to wait.";

					echo display_success();
				break;
				default :
					if($ERROR) {
						echo display_error($ERRORSTR);
					}

					if (count($apartment_ids)) {
						/**
						 * Check to ensure the availability still exists.
						 */
						$available_apartments = regionaled_apartment_availability($apartment_ids, $event_info["event_start"], $event_info["event_finish"]);
						if (is_array($available_apartments) && ($available_apartments["openings"] > 0)) {
							?>
							<div class="userProfile">
								<div class="head">
									<div>Learner Profile</div>
								</div>
								<div class="body">
									<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
										<tr>
											<td style="width: 110px; vertical-align: top; padding-left: 10px">
												<div style="position: relative">
													<?php
													$query = "SELECT `photo_active` FROM `".AUTH_DATABASE."`.`user_photos` WHERE `photo_type` = 1 AND `proxy_id` = ".$db->qstr($event_info["proxy_id"]);
													$uploaded_file_active = $db->GetOne($query);

													echo "<div id=\"img-holder-".$event_info["proxy_id"]."\" class=\"img-holder\">\n";

													$offical_file_active = false;
													$uploaded_file_active = false;

													/**
													 * If the photo file actually exists
													 */
													if (@file_exists(STORAGE_USER_PHOTOS."/".$event_info["proxy_id"]."-official")) {
														$offical_file_active = true;
													}

													/**
													 * If the photo file actually exists, and
													 * If the uploaded file is active in the user_photos table, and
													 * If the proxy_id has their privacy set to "Basic Information" or higher.
													 */
													if ((@file_exists(STORAGE_USER_PHOTOS."/".$event_info["proxy_id"]."-upload")) && ($db->GetOne("SELECT `photo_active` FROM `".AUTH_DATABASE."`.`user_photos` WHERE `photo_type` = '1' AND `photo_active` = '1' AND `proxy_id` = ".$db->qstr($event_info["proxy_id"]))) && ((int) $event_info["privacy_level"] >= 2)) {
														$uploaded_file_active = true;
													}

													if ($offical_file_active) {
														echo "	<img id=\"official_photo_".$event_info["proxy_id"]."\" class=\"official\" src=\"".webservice_url("photo", array($event_info["proxy_id"], "official"))."\" width=\"72\" height=\"100\" alt=\"".html_encode($event_info["prefix"]." ".$event_info["firstname"]." ".$event_info["lastname"])."\" title=\"".html_encode($event_info["prefix"]." ".$event_info["firstname"]." ".$event_info["lastname"])."\" />\n";
													}

													if ($uploaded_file_active) {
														echo "	<img id=\"uploaded_photo_".$event_info["proxy_id"]."\" class=\"uploaded\" src=\"".webservice_url("photo", array($event_info["proxy_id"], "upload"))."\" width=\"72\" height=\"100\" alt=\"".html_encode($event_info["prefix"]." ".$event_info["firstname"]." ".$event_info["lastname"])."\" title=\"".html_encode($event_info["prefix"]." ".$event_info["firstname"]." ".$event_info["lastname"])."\" />\n";
													}

													if (($offical_file_active) || ($uploaded_file_active)) {
														echo "	<a id=\"zoomin_photo_".$event_info["proxy_id"]."\" class=\"zoomin\" onclick=\"growPic($('official_photo_".$event_info["proxy_id"]."'), $('uploaded_photo_".$event_info["proxy_id"]."'), $('official_link_".$event_info["proxy_id"]."'), $('uploaded_link_".$event_info["proxy_id"]."'), $('zoomout_photo_".$event_info["proxy_id"]."'));\">+</a>";
														echo "	<a id=\"zoomout_photo_".$event_info["proxy_id"]."\" class=\"zoomout\" onclick=\"shrinkPic($('official_photo_".$event_info["proxy_id"]."'), $('uploaded_photo_".$event_info["proxy_id"]."'), $('official_link_".$event_info["proxy_id"]."'), $('uploaded_link_".$event_info["proxy_id"]."'), $('zoomout_photo_".$event_info["proxy_id"]."'));\"></a>";
													} else {
														echo "	<img src=\"".ENTRADA_URL."/images/headshot-male.gif\" width=\"72\" height=\"100\" alt=\"No Photo Available\" title=\"No Photo Available\" />\n";
													}

													if (($offical_file_active) && ($uploaded_file_active)) {
														echo "	<a id=\"official_link_".$event_info["proxy_id"]."\" class=\"img-selector one\" onclick=\"showOfficial($('official_photo_".$event_info["proxy_id"]."'), $('official_link_".$event_info["proxy_id"]."'), $('uploaded_link_".$event_info["proxy_id"]."'));\" href=\"javascript: void(0);\">1</a>";
														echo "	<a id=\"uploaded_link_".$event_info["proxy_id"]."\" class=\"img-selector two\" onclick=\"hideOfficial($('official_photo_".$event_info["proxy_id"]."'), $('official_link_".$event_info["proxy_id"]."'), $('uploaded_link_".$event_info["proxy_id"]."'));\" href=\"javascript: void(0);\">2</a>";
													}
													echo "</div>\n";
													?>
												</div>
											</td>
											<td style="width: 100%; vertical-align: top; padding-left: 5px">
												<table width="100%" cellspacing="0" cellpadding="1" border="0">
													<tr>
														<td style="width: 20%">Full Name:</td>
														<td style="width: 80%"><?php echo html_encode($event_info["prefix"]." ".$event_info["firstname"]." ".$event_info["lastname"]); ?></td>
													</tr>
													<tr>
														<td>Gender:</td>
														<td><?php echo display_gender($event_info["gender"]); ?></td>
													</tr>
													<tr>
														<td>Student Type:</td>
														<td><?php echo html_encode($event_info["learner_type"]); ?></td>
													</tr>
													<tr>
														<td>Student Number:</td>
														<td><?php echo html_encode($event_info["number"]); ?></td>
													</tr>
													<tr>
														<td>E-Mail Address:</td>
														<td><a href="mailto:<?php echo html_encode($event_info["email"]); ?>"><?php echo html_encode($event_info["email"]); ?></a></td>
													</tr>
												</table>
											</td>
										</tr>
									</table>
								</div>
							</div>
							<?php
							$total_apartments = count($available_apartments["apartments"]);
							echo "<div class=\"display-generic\">\n";
							echo "	There ".($available_apartments["openings"] != 1 ? "are" : "is")." currently <strong>".$available_apartments["openings"]." room".($available_apartments["openings"] != 1 ? "s" : "")."</strong> available in <strong>".$total_apartments." apartment".($total_apartments != 1 ? "s" : "")."</strong> in <strong>".html_encode($event_info["region_name"])."</strong> from <strong>".date("Y-m-d", $event_info["event_start"])."</strong> to <strong>".date("Y-m-d", $event_info["event_finish"])."</strong>. Please select which accommodation you would like ".html_encode($event_info["firstname"]." ".$event_info["lastname"])." to be assigned to.";
							echo "</div>";
							?>
							<form action="<?php echo ENTRADA_URL; ?>/admin/regionaled?section=assign&id=<?php echo $event_id; ?>" method="post">
								<input type="hidden" name="step" value="2" />
								<table class="tableList" cellspacing="0" cellpadding="1" border="0" summary="List of Available Accommodations">
									<colgroup>
										<col class="modified" />
										<col class="title" />
										<col class="title" />
									</colgroup>
									<tfoot>
										<tr>
											<td colspan="3">&nbsp;</td>
										</tr>
										<tr>
											<td><input type="checkbox" id="require_confirmation" name="require_confirmation" value="1"<?php echo ((!isset($PROCESSED["confirmed"]) || !$PROCESSED["confirmed"]) ? " checked=\"checked\"" : ""); ?> /></td>
											<td colspan="2">
												<label for="require_confirmation" class="form-nrequired">Send an e-mail requiring <strong><?php echo html_encode($event_info["firstname"]); ?></strong> to confirm these accommodations.</label>
											</td>
										</tr>
										<tr>
											<td colspan="3" style="padding-top: 15px; text-align: right">
												<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/regionaled'" />
												<input type="submit" class="btn btn-primary" value="Proceed" />
											</td>
										</tr>
									</tfoot>
									<tbody>
									<?php
									foreach ($available_apartments["apartments"] as $apartment) {
										?>
										<tr>
											<td class="modified" style="vertical-align: top"><input type="radio" id="apartment_id_<?php echo $apartment["details"]["apartment_id"]; ?>" name="apartment_id" value="<?php echo $apartment["details"]["apartment_id"]; ?>" /></td>
											<td class="title" style="vertical-align: top">
												<label for="apartment_id_<?php echo $apartment["details"]["apartment_id"]; ?>" style="font-weight: 700"><?php echo html_encode($apartment["details"]["apartment_title"]); ?></label>
												<div class="content-small">
													<?php
													echo (($apartment["details"]["apartment_number"] != "") ? html_encode($apartment["details"]["apartment_number"])."-" : "").html_encode($apartment["details"]["apartment_address"]);
													echo html_encode($apartment["details"]["region_name"]).($apartment["details"]["province"] ? ", ".html_encode($apartment["details"]["province"]) : "")."<br />";
													echo html_encode($apartment["details"]["apartment_postcode"]).", ".html_encode($apartment["details"]["country"])."<br /><br />";
													?>
													Max Concurrent Occupants: <?php echo $apartment["details"]["max_occupants"]; ?>
												</div>
											</td>
											<td class="title" style="vertical-align: top">
												<?php
												if ($apartment["occupants"] && count($apartment["occupants"])) {
													echo "<ul class=\"menu\">\n";
													foreach ($apartment["occupants"] as $result) {
														echo "<li class=\"".(($result["group"] == "student") ? "undergrad" : "postgrad")."\">\n";
														echo	(($result["fullname"]) ? (($result["gender"]) ? ($result["gender"] == 1 ? "F: " : "M: ") : "")."<a href=\"".ENTRADA_URL."/people?profile=".html_encode($result["username"])."\" target=\"_blank\">".$result["fullname"]."</a>" : $result["occupant_title"]);
														echo "	<div class=\"content-small\">".date(DEFAULT_DATE_FORMAT, $result["inhabiting_start"])." until ".date(DEFAULT_DATE_FORMAT, $result["inhabiting_finish"])."</div>";
														echo "</li>";
													}
													echo "</ul>\n";
												} else {
													echo "No other occupants in this accommodation during their stay.";
												}
												?>
											</td>
										</tr>
										<?php
									}
									?>
									</tbody>
								</table>
							</form>
							<?php

							$sidebar_html  = "<ul class=\"menu\">\n";
							$sidebar_html .= "	<li class=\"undergrad\">Undergraduate Learner</li>\n";
							$sidebar_html .= "	<li class=\"postgrad\">Postgraduate Learner</li>\n";
							$sidebar_html .= "	<li class=\"other\">Other Occupancy</li>\n";
							$sidebar_html .= "</ul>\n";

							new_sidebar_item("Occupant Type Legend", $sidebar_html, "occupant-type-legend", "open");
						} else {
							$NOTICE++;
							$NOTICESTR[] = "Unfortunately there are <strong>no available apartments</strong> in <strong>".html_encode($event_info["region_name"])."</strong> at this time.";

							echo display_notice();
						}
					} else {
						$NOTICE++;
						$NOTICESTR[] = "Unfortunately there are <strong>no active apartments</strong> in <strong>".html_encode($event_info["region_name"])."</strong> at this time.";

						echo display_notice();
					}
				break;
			}
		} else {
			$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 5000)";

			$ERROR++;
			$ERRORSTR[] = "The event id that was provided was not found. Please select a new event from the " . $APARTMENT_INFO["department_title"] . " dashboard.";

			echo display_error();

			application_log("notice", "Failed to provide a valid event identifer [".$event_id."] when attempting to add an accommodation.");
		}
	} else {
		application_log("notice", "Failed to provide an event identifer when attempting to add an accommodation.");

		header("Location: ".ENTRADA_URL."/admin/regionaled");
		exit;
	}
}