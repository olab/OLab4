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
 * This file is used when a learner viewing an assigned accommodation request.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */

if (!defined("IN_REGIONALED_VIEW")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("regionaled", "read")) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[] = array("url" => "", "title" => "Accommodation Details");

	$max_occupants = (int) $APARTMENT_INFO["max_occupants"];
	$apt_occupants = regionaled_apartment_occupants($APARTMENT_INFO["apartment_id"], $APARTMENT_INFO["inhabiting_start"], $APARTMENT_INFO["inhabiting_finish"]);
	$num_occupants = ((is_array($apt_occupants)) ? count($apt_occupants) : 0);

	$apartment_address  = (($APARTMENT_INFO["apartment_number"] != "") ? html_encode($APARTMENT_INFO["apartment_number"])."-" : "").html_encode($APARTMENT_INFO["apartment_address"])."<br />\n";
	$apartment_address .= html_encode($APARTMENT_INFO["region_name"]).($APARTMENT_INFO["province"] ? ", ".html_encode($APARTMENT_INFO["province"]) : "")."<br />\n";
	$apartment_address .= html_encode($APARTMENT_INFO["apartment_postcode"]).", ".html_encode($APARTMENT_INFO["country"]);

	/**
	 * Only allow the confirm / reject code to run if the accommodation is
	 * not already confirmed.
	 */
	if (!$APARTMENT_INFO["confirmed"]) {
		switch ($ACTION) {
			case "accept" :
				if ($db->AutoExecute(CLERKSHIP_DATABASE.".apartment_schedule", array("confirmed" => 1), "UPDATE", "`aschedule_id` = ".$db->qstr($ASCHEDULE_ID)." AND `apartment_id` = ".$db->qstr($APARTMENT_INFO["apartment_id"])." AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID()))) {
					$SUCCESS++;
					$SUCCESSSTR[] = "You have successfully confirmed your accommodation in this apartment from ".date(DEFAULT_DATE_FORMAT, $APARTMENT_INFO["inhabiting_start"])." until ".date(DEFAULT_DATE_FORMAT, $APARTMENT_INFO["inhabiting_finish"]).".";

					$APARTMENT_INFO["confirmed"] = 1;
					
					application_log("success", "Proxy_id [".$ENTRADA_USER->getID()."] successfully confirmed their accommodation in aschedule_id [".$ASCHEDULE_ID."].");
				} else {
					application_log("error", "Unable to set confirmed 1 for aschedule_id [".$ASCHEDULE_ID."] after proxy_id [".$ENTRADA_USER->getID()."] confirmed the accommodation. Database said: ".$db->ErrorMsg());

					$ERROR++;
					$ERRORSTR[] = "We were unable to confirm your apartment accommodation at this time. The system administrator was notified of the issue, please try again in a few moments.";
				}
			break;
			case "reject" :
				if (isset($_POST["confirm"]) && ((int) $_POST["confirm"] == 1)) {
					if (isset($_POST["details"]) && ($tmp_input = clean_input($_POST["details"], array("notags", "trim")))) {
						$rejection_reason = $tmp_input;
					} else {
						$rejection_reason = false;

						$ERROR++;
						$ERRORSTR[] = "In order to reject an accommodation you must provide a detailed explanation as to why this accommodation is unacceptable to you.";
					}

					if (!$ERROR) {
						$query = "DELETE FROM `".CLERKSHIP_DATABASE."`.`apartment_schedule` WHERE `aschedule_id` = ".$db->qstr($ASCHEDULE_ID)." AND `apartment_id` = ".$db->qstr($APARTMENT_INFO["apartment_id"])." AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID());
						if ($db->Execute($query) && ($db->Affected_Rows() == 1)) {
							if ($EVENT_INFO && $EVENT_INFO["event_id"]) {
								/**
								 * Reset the requires_apartment flag so this person is put back on the Regional Education dashboard.
								 */
								if (!$db->AutoExecute(CLERKSHIP_DATABASE.".events", array("requires_apartment" => 1), "UPDATE", "event_id=".$db->qstr($EVENT_INFO["event_id"]))) {
									application_log("error", "Unable to set requires_apartment to 1 for event_id [".$EVENT_INFO["event_id"]."] after proxy_id [".$ENTRADA_USER->getID()."] rejeceted aschedule_id [".$ASCHEDULE_ID."]. Database said: ".$db->ErrorMsg());
								}
							}

							$message_variables = array (
								"to_fullname" => $AGENT_CONTACTS["agent-regionaled"][$APARTMENT_INFO["department_id"]]["name"],
								"from_firstname" => $_SESSION["details"]["firstname"],
								"from_lastname" => $_SESSION["details"]["lastname"],
								"region" => $APARTMENT_INFO["region_name"],
								"reason" => $rejection_reason,
								"apartment_address" => strip_tags($apartment_address),
								"inhabiting_start" => date(DEFAULT_DATE_FORMAT, $APARTMENT_INFO["inhabiting_start"]),
								"inhabiting_finish" => date(DEFAULT_DATE_FORMAT, $APARTMENT_INFO["inhabiting_finish"]),
								"application_name" => APPLICATION_NAME,
								"department_id" => $APARTMENT_INFO["department_id"]								
							);

							$recipient = array (
								"email" => $AGENT_CONTACTS["agent-regionaled"][$APARTMENT_INFO["department_id"]]["email"],
								"firstname" => $AGENT_CONTACTS["agent-regionaled"][$APARTMENT_INFO["department_id"]]["name"],
								"lastname" => "Contact"
							);

							regionaled_apartment_notification("rejected", $recipient, $message_variables);
						} else {
							application_log("error", "Unable to delete aschedule_id [".$ASCHEDULE_ID."] from the database when an accommodation was rejected. Database said: ".$db->ErrorMsg());

							$ERROR++;
							$ERRORSTR[] = "We were unable to complete the accommodation rejection at this time. The system administrator was notified of this error, please try again later or contact the " . $APARTMENT_INFO["department_title"] . " Office directly.";
						}
					}

					if (!$ERROR) {
						header("Location: ".ENTRADA_URL."/regionaled");
						exit;
					}
				}
			break;
			default:
				continue;
			break;
		}
	}

	/**
	 * Determine whether the Google map can be shown.
	 */
	if ((defined("GOOGLE_MAPS_API")) && (GOOGLE_MAPS_API != "")) {
		$show_google_map = true;
	} else {
		$show_google_map = false;
	}

	if ($show_google_map) {
		$HEAD[]		= "<script type=\"text/javascript\" src=\"".GOOGLE_MAPS_API."\"></script>";
		$ONLOAD[]	= "displayGoogleMap()";
		?>
		<script type="text/javascript">
		var map = null;
		function displayGoogleMap() {
			aptLatitude = '<?php echo $APARTMENT_INFO["apartment_latitude"]; ?>';
			aptLongitude = '<?php echo $APARTMENT_INFO["apartment_longitude"]; ?>';

			if ((aptLatitude != '') && (aptLongitude != '') && (GBrowserIsCompatible())) {
				map = new GMap2($('mapData'));
				point = new GLatLng(aptLatitude, aptLongitude)
				address = '<div><?php echo str_replace("\n", "\\n", $apartment_address); ?></div>';

				map.setCenter(point, 15);
				map.setUIToDefault();
				map.enableGoogleBar();

				var icon = new GIcon();
				icon.image = '<?php echo ENTRADA_URL; ?>/images/icon-apartment.gif';
				icon.shadow = '<?php echo ENTRADA_URL; ?>/images/icon-apartment-shadow.png';
				icon.iconSize = new GSize(25, 34);
				icon.shadowSize = new GSize(35, 34);
				icon.iconAnchor = new GPoint(25, 34);
				icon.infoWindowAnchor = new GPoint(15, 5);

				var marker = new GMarker(point, icon);

				marker.openInfoWindowHtml(address);

				map.addOverlay(marker);

				GEvent.addListener(marker, "click", function() {
					marker.openInfoWindowHtml(address);
				});
			}
		}
		</script>
		<?php
	}
	?>
	<h1>Accommodation Details</h1>

	<?php
	display_status_messages();

	if (!$APARTMENT_INFO["confirmed"]) {
		?>
		<div class="display-notice">
			<h3>Confirmation Required</h3>
			<ul>
				<li>The <?php echo $APARTMENT_INFO["department_title"]; ?> Office has scheduled you to reside in this accommodation from <strong><?php echo date("l, F j, Y", $APARTMENT_INFO["inhabiting_start"]); ?></strong> until <strong><?php echo date("l, F j, Y", $APARTMENT_INFO["inhabiting_finish"]); ?></strong>. You must indicate whether you accept or reject these accommodations.</li>
			</ul>
			<div>
				<a class="btn btn-danger" style="float:left" href="#reject-accommodation-box" id="reject-accommodation-button"><div>Reject</div></a>
				<a class="btn btn-primary" style="float:right" href="<?php echo ENTRADA_RELATIVE; ?>/regionaled/view?id=<?php echo $ASCHEDULE_ID; ?>&action=accept"><div>Accept</div></a>
			</div>
			<div class="clear"></div>
		</div>
		<?php
	}

	if (strip_tags(trim($APARTMENT_INFO["apartment_information"]))) {
		echo clean_input($APARTMENT_INFO["apartment_information"], "nicehtml");
	}
	?>

	<div>
		<div style="float: left; width: 40%">
			<h2>Contact Information</h2>
			<?php
			echo "<table style=\"width: 100%\" cellspacing=\"0\" cellpadding=\"2\" border=\"0\" summary=\"Apartment Contact Information\">\n";
			echo "	<colgroup>\n";
			echo "		<col style=\"width: 40%\" />\n";
			echo "		<col style=\"width: 60%\" />\n";
			echo "	</colgroup>\n";
			echo "	<tbody>\n";
			echo "		<tr>\n";
			echo "			<td style=\"vertical-align: top\">Address:</td>\n";
			echo "			<td>".$apartment_address."</td>\n";
			echo "		</tr>\n";
			if (trim($APARTMENT_INFO["apartment_phone"])) {
				echo "	<tr>\n";
				echo "		<td>Telephone:</td>\n";
				echo "		<td>".html_encode($APARTMENT_INFO["apartment_phone"])."</td>\n";
				echo "	</tr>\n";
			}
			if (trim($APARTMENT_INFO["apartment_email"])) {
				echo "	<tr>\n";
				echo "		<td>E-Mail Address:</td>\n";
				echo "		<td><a href=\"mailto:".html_encode($APARTMENT_INFO["apartment_email"])."\">".html_encode(limit_chars($APARTMENT_INFO["apartment_email"], 30))."</a></td>\n";
				echo "	</tr>\n";
			}
			if (trim($APARTMENT_INFO["super_firstname"])) {
				echo "		<tr>\n";
				echo "			<td colspan=\"2\">&nbsp;</td>\n";
				echo "		</tr>\n";
				echo "		<tr>\n";
				echo "			<td style=\"vertical-align: top\">Superintendent:</td>\n";
				echo "			<td>".html_encode($APARTMENT_INFO["super_firstname"]." ".$APARTMENT_INFO["super_lastname"])."</td>\n";
				echo "		</tr>\n";
				if (trim($APARTMENT_INFO["super_phone"])) {
					echo "	<tr>\n";
					echo "		<td>Contact Number:</td>\n";
					echo "		<td>".html_encode($APARTMENT_INFO["super_phone"])."</td>\n";
					echo "	</tr>\n";
				}
				if (trim($APARTMENT_INFO["super_email"])) {
					echo "	<tr>\n";
					echo "		<td>Contact E-Mail:</td>\n";
					echo "		<td><a href=\"mailto:".html_encode($APARTMENT_INFO["super_email"])."\">".html_encode(limit_chars($APARTMENT_INFO["super_email"], 30))."</a></td>\n";
					echo "	</tr>\n";
				}
			}
			if (trim($APARTMENT_INFO["keys_firstname"])) {
				echo "		<tr>\n";
				echo "			<td colspan=\"2\">&nbsp;</td>\n";
				echo "		</tr>\n";
				echo "		<tr>\n";
				echo "			<td style=\"vertical-align: top\">Key Contact:</td>\n";
				echo "			<td>".html_encode($APARTMENT_INFO["keys_firstname"]." ".$APARTMENT_INFO["keys_lastname"])."</td>\n";
				echo "		</tr>\n";
				if (trim($APARTMENT_INFO["keys_phone"])) {
					echo "	<tr>\n";
					echo "		<td>Contact Number:</td>\n";
					echo "		<td>".html_encode($APARTMENT_INFO["keys_phone"])."</td>\n";
					echo "	</tr>\n";
				}
				if (trim($APARTMENT_INFO["keys_email"])) {
					echo "	<tr>\n";
					echo "		<td>Contact E-Mail:</td>\n";
					echo "		<td><a href=\"mailto:".html_encode($APARTMENT_INFO["keys_email"])."\">".html_encode(limit_chars($APARTMENT_INFO["keys_email"], 30))."</a></td>\n";
					echo "	</tr>\n";
				}
			}
			
			echo "	</tbody>\n";
			echo "</table>\n";
			?>
		</div>
		<div style="float: right; width: 58%">
			<h2>Other Occupants During My Stay</h2>
			<?php
			if ($num_occupants) {
				echo "<ul class=\"menu\">\n";
				foreach ($apt_occupants as $result) {
					echo "<li class=\"".(($result["group"] == "student") ? "undergrad" : "postgrad")."\">\n";
					echo	(($result["fullname"]) ? (($result["gender"]) ? ($result["gender"] == 1 ? "F: " : "M: ") : "")."<a href=\"".ENTRADA_URL."/people?profile=".html_encode($result["username"])."\" target=\"_blank\">".$result["fullname"]."</a>" : $result["occupant_title"]);
					echo "	<div class=\"content-small\">".date(DEFAULT_DATE_FORMAT, $result["inhabiting_start"])." until ".date(DEFAULT_DATE_FORMAT, $result["inhabiting_finish"])."</div>";
					echo "</li>";
				}
				echo "</ul>\n";

				$sidebar_html  = "<ul class=\"menu\">\n";
				$sidebar_html .= "	<li class=\"undergrad\">Undergraduate Learner</li>\n";
				$sidebar_html .= "	<li class=\"postgrad\">Postgraduate Learner</li>\n";
				$sidebar_html .= "	<li class=\"other\">Other Occupancy</li>\n";
				$sidebar_html .= "</ul>\n";

				new_sidebar_item("Occupant Type Legend", $sidebar_html, "occupant-type-legend", "open");
			} else {
				?>
				<div class="display-generic">
					You are currently the only occupant in this apartment during this time period.
				</div>
				<?php
			}
			?>
			<div class="content-small" style="margin-top: 15px">
				The maximum possible simultaneous occupants is <strong><?php echo $max_occupants; ?></strong> <?php echo (($max_occupants != 1) ? "people" : "person"); ?>.
			</div>
		</div>
		<div style="clear: both"></div>
	</div>
	<?php
	if (($show_google_map) && ($APARTMENT_INFO["apartment_latitude"]) && ($APARTMENT_INFO["apartment_longitude"])) {
		?>
		<h2 title="Accommodation Map Section">Location Map &amp; Search</h2>

		<div id="accommodation-map-section">
			<div id="mapData" class="display-map"></div>
		</div>
		<?php
	}

	if (!$APARTMENT_INFO["confirmed"]) {
		?>
		<form id="reject-accommodation-form" action="<?php echo ENTRADA_URL; ?>/regionaled/view?id=<?php echo $ASCHEDULE_ID; ?>&action=reject" method="post">
			<input type="hidden" id="reject-accommodation-el-confirm" name="confirm" value="0" />
			<input type="hidden" id="reject-accommodation-el-details" name="details" value="" />
		</form>

		<div id="reject-accommodation-box" class="modal-confirmation" style="height: 300px">
			<h1>Reject <strong>Accommodation</strong> Confirmation</h1>
			<div class="display-notice">
				Please confirm that you <strong>do not</strong> wish to reside in this apartment between <?php echo date(DEFAULT_DATE_FORMAT, $APARTMENT_INFO["inhabiting_start"]); ?> and <?php echo date(DEFAULT_DATE_FORMAT, $APARTMENT_INFO["inhabiting_finish"]); ?>.
			</div>
			<p>
				<label for="reject-accommodation-details" class="form-required">Please provide an explanation for this decision:</label><br />
				<textarea id="reject-accommodation-details" name="reject_accommodation_details" style="width: 99%; height: 75px" cols="45" rows="5"></textarea>
			</p>
			<div class="footer">
				<button class="btn" onclick="Control.Modal.close()">Close</button>
				<button class="btn btn-danger pull-right" id="reject-accommodation-confirm">Reject</button>
			</div>
		</div>

		<script type="text/javascript">
		Event.observe(window, 'load', function() {
			new Control.Modal('reject-accommodation-button', {
				overlayOpacity:	0.75,
				closeOnClick:	'overlay',
				className:		'modal-confirmation',
				fade:			true,
				fadeDuration:	0.30
			});

			Event.observe('reject-accommodation-confirm', 'click', function() {
				$('reject-accommodation-el-confirm').setValue('1');

				if ($('reject-accommodation-details')) {
					$('reject-accommodation-el-details').setValue($('reject-accommodation-details').getValue());
				}
				$('reject-accommodation-form').submit();
			});
		});
		</script>
		<?php
	}
}