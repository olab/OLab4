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
 * The default file that is loaded when /admin/regionaled/apartments/manage is accessed successfully.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */

if (!defined("IN_MANAGE")) {
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
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/script/xc2_timestamp.js\"></script>\n";
	$HEAD[] = "<link href=\"".ENTRADA_RELATIVE."/css/calendar.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";

	/**
	 * Check to see if user is requesting the calendar show a specific date.
	 */
	if (isset($_GET["dstamp"]) && ($tmp_input = clean_input($_GET["dstamp"], array("nows", "int")))) {
		$timestamp = fetch_timestamps("month", $tmp_input);
	} else {
		$timestamp = fetch_timestamps("month", time());
	}

	$calendar = new Entrada_Calendar();
	$calendar->setBaseUrl(ENTRADA_URL."/admin/regionaled/apartments/manage");
	$calendar->setCharset(DEFAULT_CHARSET);

	$schedule = regionaled_apartment_occupants($APARTMENT_INFO["apartment_id"], $timestamp["start"], $timestamp["end"]);
	if ($schedule) {
		foreach ($schedule as $result) {
			$event = array();
			$event["timestamp_start"] = $result["inhabiting_start"];
			$event["timestamp_end"] = $result["inhabiting_finish"];

			switch ($result["occupant_type"]) {
				case "undergrad" :
					$event["calendar_id"] = 1;
				break;
				case "postgrad" :
					$event["calendar_id"] = 2;
				break;
				case "other" :
				default :
					$event["calendar_id"] = 0;
				break;
			}

			$event["event_id"] = ($result["event_id"] ? $result["event_id"] : uniqid());
			$event["event_title"] = (($result["fullname"]) ? (($result["gender"]) ? ($result["gender"] == 1 ? "F: " : "M: ") : "").$result["fullname"] : $result["occupant_title"]);
			$event["event_link"] = ENTRADA_URL."/admin/regionaled/apartments/manage/schedule?id=".$APARTMENT_ID."&sid=".$result["aschedule_id"];
			$event["event_misc"] = array("fullname" => $result["fullname"], "gender" => $result["gender"]);

			$calendar->newEvent($event);
		}
	}

	if ($APARTMENT_EXPIRED) {
		if ($APARTMENT_INFO["available_finish"] <= time()) {
			echo display_notice("This apartment expired on <strong>".date(DEFAULT_DATE_FORMAT, $APARTMENT_INFO["available_finish"])."</strong> and is no longer available for future occupants. It remains in the system in an <strong>expired state</strong> so that historical records can be retained.");
		}
	}

	$sidebar_html  = "<ul class=\"menu\">\n";
	$sidebar_html .= "	<li class=\"undergrad\">Undergraduate Learner</li>\n";
	$sidebar_html .= "	<li class=\"postgrad\">Postgraduate Learner</li>\n";
	$sidebar_html .= "	<li class=\"other\">Other Occupancy</li>\n";
	$sidebar_html .= "</ul>\n";

	new_sidebar_item("Occupant Type Legend", $sidebar_html, "occupant-type-legend", "open");

	?>
	<script type="text/javascript">
	function setDateValue(field, date) {
		timestamp = getMSFromDate(date);
		if(field.value != timestamp) {
			window.location = '<?php echo ENTRADA_URL."/admin/regionaled/apartments/manage?".replace_query(array("dstamp" => false)); ?>&dstamp=' + timestamp;
		}

		return;
	}
	</script>
	<?php
	if (!$APARTMENT_EXPIRED && $ENTRADA_ACL->amIAllowed("regionaled", "create", false)) {
		?>
		<div style="float: right">
			<ul class="page-action">
				<li><a href="<?php echo ENTRADA_URL; ?>/admin/regionaled/apartments/manage?<?php echo replace_query(array("section" => "add")); ?>" class="strong-green">Add New Occupant</a></li>
			</ul>
		</div>
		<div style="clear: both"></div>
		<?php
	}
	?>
	<table style="width: 100%" cellspacing="0" cellpadding="0" border="0" summary="Monthly Apartment Schedule">
		<tr>
			<td style="text-align: left; vertical-align: middle; white-space: nowrap">
				<table style="width: 375px; height: 23px" cellspacing="0" cellpadding="0" border="0">
				<tr>
					<td style="width: 22px; height: 23px"><a href="<?php echo ENTRADA_URL."/admin/regionaled/apartments/manage?".replace_query(array("dstamp" => ($timestamp["start"] - 1))); ?>"><img src="<?php echo ENTRADA_URL; ?>/images/cal-back.gif" width="22" height="23" alt="Previous Month" title="Previous Month" border="0" /></a></td>
					<td style="width: 271px; height: 23px; background: url('<?php echo ENTRADA_URL; ?>/images/cal-table-bg.gif'); text-align: center; font-size: 10px; color: #666666">
						<?php echo date("F Y", $timestamp["start"]); ?>
					</td>
					<td style="width: 22px; height: 23px"><a href="<?php echo ENTRADA_URL."/admin/regionaled/apartments/manage?".replace_query(array("dstamp" => ($timestamp["end"] + 1))); ?>"><img src="<?php echo ENTRADA_URL; ?>/images/cal-next.gif" width="22" height="23" alt="Next Month" title="Next Month" border="0" /></a></td>
					<td style="width: 30px; height: 23px; text-align: right"><a href="<?php echo ENTRADA_URL."/admin/regionaled/apartments/manage?".replace_query(array("dstamp" => time())); ?>"><img src="<?php echo ENTRADA_URL; ?>/images/cal-home.gif" width="23" height="23" alt="Reset to this month" title="Reset to this month" border="0" /></a></td>
					<td style="width: 30px; height: 23px; text-align: right"><img src="<?php echo ENTRADA_URL; ?>/images/cal-calendar.gif" width="23" height="23" alt="Show Calendar" title="Show Calendar" onclick="showCalendar('', $('dstamp'), $('dstamp'), '<?php echo html_encode($timestamp["start"]); ?>', 'calendar-holder', 8, 8, 1)" style="cursor: pointer" id="calendar-holder" /></td>
				</tr>
				</table>
			</td>
			<td style="text-align: right; vertical-align: middle; white-space: nowrap">
				<h1 style="margin: 8px 0; font-size: 21px;"><strong><?php echo date("F", $timestamp["start"]); ?></strong> <?php echo date("Y", $timestamp["start"]); ?></h1>
			</td>
		</tr>
	</table>
	<div style="border: 1px #EEE solid">
		<?php echo $calendar->displayCalendar($timestamp["start"]); ?>
	</div>
	<form action="" method="get">
	<input type="hidden" id="dstamp" name="dstamp" value="<?php echo html_encode($timestamp["start"]); ?>" />
	</form>
	<?php
}