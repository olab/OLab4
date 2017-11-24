<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Displays accommodation details to the user based on a particular event_id.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2008 Queen's University. All Rights Reserved.
 *
 * @version $Id: details.inc.php 621 2009-08-17 20:42:04Z hbrundage $
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CLERKSHIP"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
}

if ($EVENT_ID) {
	switch ($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]) {
		case "student" :
			/**
			 * Add the accommodation issue sidebar item.
			 */
			$sidebar_html = "<strong>Having issues?</strong> If you are having any problems with your accommodations that you would like to report please <a href=\"javascript:sendAccommodation('".ENTRADA_URL."/agent-accommodation.php')\" style=\"font-size: 11px; font-weight: bold\">click here</a>.\n";
			
			new_sidebar_item("Issue Reporting", $sidebar_html, "page-clerkship", "open");
			
			$query	= "	SELECT *
						FROM `".CLERKSHIP_DATABASE."`.`events` AS a
						LEFT JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS b
						ON b.`event_id` = a.`event_id`
						LEFT JOIN `".CLERKSHIP_DATABASE."`.`regions` AS c
						ON c.`region_id` = a.`region_id`
						LEFT JOIN `".CLERKSHIP_DATABASE."`.`apartment_schedule` AS d
						ON d.`event_id` = a.`event_id`
						LEFT JOIN `".CLERKSHIP_DATABASE."`.`apartments` AS e
						ON e.`apartment_id` = d.`apartment_id`
						WHERE a.`event_id` = ".$db->qstr($EVENT_ID)."
						AND a.`event_status` = 'published'
						AND b.`econtact_type` = 'student'
						AND b.`etype_id` = ".$db->qstr($ENTRADA_USER->getActiveId())."
						AND d.`proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId())."
						AND d.`aschedule_status` = 'published'";
			$result	= $db->GetRow($query);
			if ($result) {
				$BREADCRUMB[]	= array("url" => "", "title" => "Rotation Details");

				$max_occupants	= (int) $result["max_occupants"];
				$apt_occupants	= regionaled_apartment_occupants($result["apartment_id"], $result["event_start"], $result["event_finish"]);
				$num_occupants	= ((is_array($apt_occupants)) ? count($apt_occupants) : 0);

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
						aptLatitude		= '<?php echo $result["apartment_latitude"]; ?>';
						aptLongitude	= '<?php echo $result["apartment_longitude"]; ?>';

						if ((aptLatitude != '') && (aptLongitude != '') && (GBrowserIsCompatible())) {
							map		= new GMap2($('mapData'));
							point	= new GLatLng(aptLatitude, aptLongitude)

							map.setCenter(point, 15);
							map.setUIToDefault();
							map.enableGoogleBar();
							
							var icon				= new GIcon();
							icon.image				= '<?php echo ENTRADA_URL; ?>/images/icon-apartment.gif';
							icon.shadow				= '<?php echo ENTRADA_URL; ?>/images/icon-apartment-shadow.png';
							icon.iconSize			= new GSize(25, 34);
							icon.shadowSize			= new GSize(35, 34);
							icon.iconAnchor			= new GPoint(25, 34);
							icon.infoWindowAnchor	= new GPoint(15, 5);

							var marker = new GMarker(point, icon);
							map.addOverlay(marker);

							marker.openInfoWindowHtml('<div style="font-family: monospace"><?php echo ((trim($result["apartment_number"]) != "") ? html_encode($result["apartment_number"])." - " : "").html_encode($result["apartment_address"])."<br />".html_encode($result["apartment_city"]).", ".html_encode($result["apartment_province"])."<br />".html_encode($result["apartment_postcode"]).", ".html_encode($result["apartment_country"]).((trim($result["apartment_phone"]) != "") ? "<br /><br />Telephone: ".html_encode($result["apartment_phone"]) : ""); ?></div>');
						}
					}
					</script>
					<?php
				}
				?>
				<h1><?php echo clean_input($result["event_title"], array("htmlbrackets", "trim")); ?></h1>

				<h2 title="Rotation Information Section">Rotation Information</h2>

				<div id="rotation-information-section">
					<table style="width: 100%; margin-bottom: 10px" cellspacing="0" cellpadding="2" border="0" summary="Rotation Information">
					<colgroup>
						<col style="width: 20%" />
						<col style="width: 80%" />
					</colgroup>
					<tbody>
						<tr>
							<td>Rotation Name:</td>
							<td><?php echo clean_input($result["event_title"], array("htmlbrackets", "trim")); ?></td>
						</tr>
						<?php if (trim($result["event_desc"])) : ?>
							<tr>
								<td style="vertical-align: top">Rotation Notes:</td>
								<td><?php echo html_encode($result["event_desc"]); ?></td>
							</tr>
						<?php endif; ?>
						<tr>
							<td>Rotation Region:</td>
							<td><?php echo html_encode(clerkship_region_name($result["region_id"])); ?></td>
						</tr>
						<tr>
							<td>Rotation Starts:</td>
							<td><?php echo date(DEFAULT_DATE_FORMAT, $result["event_start"]); ?></td>
						</tr>
						<tr>
							<td>Rotation Ends:</td>
							<td><?php echo date(DEFAULT_DATE_FORMAT, $result["event_finish"]); ?></td>
						</tr>
					</tbody>
					</table>
				</div>

				<div style="float: left; width: 40%">
					<h2 title="Accommodation Details Section">Accommodation Details</h2>

					<div id="accommodation-details-section">
						<?php
						echo "<div class=\"display-generic\" style=\"font-family: Monospace; font-size: 12px; white-space: nowrap\">\n";
						echo 	((trim($result["apartment_number"]) != "") ? html_encode($result["apartment_number"])." - " : "").html_encode($result["apartment_address"])."<br />";
						echo 	html_encode($result["apartment_city"]).", ".html_encode($result["apartment_province"])."<br />";
						echo 	html_encode($result["apartment_postcode"]).", ".html_encode($result["apartment_country"]);
						echo "</div>\n";
						echo "<table style=\"width: 100%\" cellspacing=\"0\" cellpadding=\"2\" border=\"0\" summary=\"Apartment Contact Information\">\n";
						echo "<colgroup>\n";
						echo "	<col style=\"width: 40%\" />\n";
						echo "	<col style=\"width: 60%\" />\n";
						echo "</colgroup>\n";
						echo "<tbody>\n";
						if (trim($result["apartment_phone"])) {
							echo "<tr>\n";
							echo "	<td>Telephone:</td>\n";
							echo "	<td>".html_encode($result["apartment_phone"])."</td>\n";
							echo "</tr>\n";
						}
						if (trim($result["apartment_email"])) {
							echo "<tr>\n";
							echo "	<td>E-Mail Address:</td>\n";
							echo "	<td><a href=\"mailto:".html_encode($result["apartment_email"])."\">".html_encode(limit_chars($result["apartment_email"], 30))."</a></td>\n";
							echo "</tr>\n";
						}
						echo "<tr>\n";
						echo "	<td>Max Occupants:</td>\n";
						echo "	<td>".$max_occupants." Student".(($max_occupants != 1) ? "s" : "")."</td>\n";
						echo "</tr>\n";
						echo "</tbody>\n";
						echo "</table>\n";
						?>
					</div>
				</div>
				<div style="float: right; width: 58%">
					<h2 title="Accommodation Occupants Section">Accommodation Occupants</h2>

					<div id="accommodation-occupants-section">
						<?php
						if ($num_occupants) {
							echo "		<table class=\"tableList\" cellspacing=\"0\" cellpadding=\"2\" border=\"0\" summary=\"List of Roommates\">\n";
							echo "		<colgroup>\n";
							echo "			<col class=\"modified\" />\n";
							echo "			<col class=\"title\" />\n";
							echo "			<col class=\"date-smallest\" />\n";
							echo "			<col class=\"date-smallest\" />\n";
							echo "		</colgroup>\n";
							echo "		<thead>\n";
							echo "			<tr>\n";
							echo "				<td class=\"modified\">&nbsp;</td>\n";
							echo "				<td class=\"title\">Full Name</td>\n";
							echo "				<td class=\"date-smallest\">Moves In</td>\n";
							echo "				<td class=\"date-smallest\">Moves Out</td>\n";
							echo "			</tr>\n";
							echo "		</thead>\n";
							echo "		<tbody>\n";
							if (is_array($apt_occupants)) {
								foreach ($apt_occupants as $occupant) {
									echo "		<tr>\n";
									echo "			<td class=\"modified\"><img src=\"".ENTRADA_URL."/images/display-gender-".html_encode(display_gender($occupant["gender"])).".gif\" width=\"14\" height=\"14\" alt=\"Gender: ".ucwords($occupant["gender"])."\" title=\"Gender: ".ucwords($occupant["gender"])."\" /></td>\n";
									echo "			<td class=\"title\"><a href=\"".ENTRADA_URL."/people?profile=".html_encode($occupant["username"])."\" target=\"_blank\">".html_encode($occupant["fullname"])."</a></td>\n";
									echo "			<td class=\"date-smallest\">".date("D M d/y", $occupant["inhabiting_start"])."</td>\n";
									echo "			<td class=\"date-smallest\">".date("D M d/y", $occupant["inhabiting_finish"])."</td>\n";
									echo "		</tr>\n";
								}
							}
							echo "		</tbody>\n";
							echo "		</table>\n";
						} else {
							$NOTICE++;
							$NOTICESTR[] = "You are the only person in the apartment during this time period at this moment.";

							echo display_notice();
						}
						?>
					</div>
				</div>
				<div style="clear: both"></div>
				<?php
				if (($show_google_map) && ($result["apartment_latitude"]) && ($result["apartment_longitude"])) {
					?>
					<h2 title="Accommodation Map Section">Accommodation Map</h2>

					<div id="accommodation-map-section">
						<div id="mapData" class="display-map"></div>
					</div>
					<?php
				}
			} else {
				$ONLOAD[]	= "setTimeout('window.location=\'".ENTRADA_URL."/".$MODULE."\'', 10000)";
				
				$ERROR++;
				$ERRORSTR[] = "The apartment details that you are trying to load are currently notavailable.<br /><br />You will be returned to the Clerkship tab in 10 seconds, or <a href=\"".ENTRADA_URL."/".$MODULE."\">click here</a> to continue immediately.";
		
				echo display_error($ERRORSTR);
			
				application_log("error", "Failed to load a Clerkship apartment details page. Database said: ".$db->ErrorMsg());
			}
		break;
		default :
			/**
			 * This is in here, because I am sure at some point I will need to put
			 * in support for access to this information from other groups.
			 */
			application_log("error", "Someone other than a student attempted to access a Clerkship rotation details.");
			
			header("Location: ".ENTRADA_URL."/".$MODULE);
			exit;
		break;
	}
} else {
	$ONLOAD[]	= "setTimeout('window.location=\'".ENTRADA_URL."/".$MODULE."\'', 10000)";

	$ERROR++;
	$ERRORSTR[] = "The apartment details that you are trying to load are currently notavailable.<br /><br />You will be returned to the Clerkship tab in 10 seconds, or <a href=\"".ENTRADA_URL."/".$MODULE."\"></a> to continue immediately.";

	echo display_error($ERRORSTR);

	application_log("error", "Failed to load a Clerkship apartment details page because there was no event_id specified.");
}