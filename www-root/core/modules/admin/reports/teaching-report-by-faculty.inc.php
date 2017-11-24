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
 * This file is used to add events to the entrada.events table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_REPORTS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('report', 'read', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[]	= array("url" => "", "title" => "Teaching Report By Faculty Member" );
	?>
	<div class="no-printing">
		<form action="<?php echo ENTRADA_URL; ?>/admin/reports?section=<?php echo $SECTION; ?>&step=2" method="post">
		<table style="width: 100%" cellspacing="0" cellpadding="2" border="0">
		<colgroup>
			<col style="width: 3%" />
			<col style="width: 20%" />
			<col style="width: 77%" />
		</colgroup>
		<tbody>
			<tr>
				<td colspan="3"><h2>Reporting Dates</h2></td>
			</tr>
			<?php echo generate_calendars("reporting", "Reporting Date", true, true, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"], true, true, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]); ?>
			<tr>
				<td colspan="3" style="text-align: right; padding-top: 10px"><input type="submit" class="btn btn-primary" value="Create Report" /></td>
			</tr>
		</tbody>
		</table>
		</form>
	</div>
	<?php
	if ($STEP == 2) {
		
	$int_use_cache	= true;

	$report_results	= array();

	$organisation_where = " AND (a.`organisation_id` = ".$ENTRADA_USER->getActiveOrganisation().") ";

	$query	= "	SELECT a.`id` AS `proxy_id`, a.`number` AS `staff_number`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, a.`email`
				FROM `".AUTH_DATABASE."`.`user_data` AS a
				LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
				ON b.`user_id` = a.`id`
				AND b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
				WHERE  b.`app_id` = ".$db->qstr(AUTH_APP_ID).$organisation_where."
				AND b.`group` = 'faculty'
				GROUP BY a.`id`
				ORDER BY `fullname`";
	if ($int_use_cache) {
		$results	= $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
	} else {
		$results	= $db->GetAll($query);
	}
	if ($results) {
		$event_ids = array();
		$report_results["courses"]["events"] = array("total_events" => 0, "total_minutes" => 0, "events_calculated" => 0, "events_minutes" => 0);
		foreach ($results as $result) {
			$query	= "	SELECT a.`event_id`, a.`event_title`, a.`course_id`, a.`event_duration`
						FROM `events` AS a
						LEFT JOIN `event_contacts` AS b
						ON b.`event_id` = a.`event_id`
						WHERE b.`proxy_id` = ".$db->qstr($result["proxy_id"])."
						AND b.`contact_role` = 'teacher' 
						AND (a.`event_start` BETWEEN ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"])." AND ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]).")";
			if ($int_use_cache) {
				$sresults	= $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
			} else {
				$sresults	= $db->GetAll($query);
			}
			if ($sresults) {
				$i = @count($report_results["people"]);
				$report_results["people"][$i]["fullname"] = $result["fullname"];
				$report_results["people"][$i]["number"] = $result["staff_number"];
				$report_results["people"][$i]["events"]	= array("total_events" => 0, "total_minutes" => 0);

				foreach ($sresults as $sresult) {
					if (!in_array($sresult["event_id"], $event_ids)) {
						$event_ids[] = $sresult["event_id"];
						$increment_total = true;
					} else {
						$increment_total = false;
					}

                    $report_results["people"][$i]["events"]["total_events"] += 1;
                    $report_results["people"][$i]["events"]["total_minutes"] += (int) $sresult["event_duration"];

					if ($increment_total) {
                        $report_results["courses"]["events"]["total_events"] += 1;
                        $report_results["courses"]["events"]["total_minutes"] += (int) $sresult["event_duration"];
                    }

                    $report_results["courses"]["events"]["events_calculated"] += 1;
                    $report_results["courses"]["events"]["events_minutes"] += (int) $sresult["event_duration"];
				}
			}
		}
	}

	echo "<h1>Teaching Report By Faculty Member (hourly)</h1>";
	echo "<div class=\"content-small\" style=\"margin-bottom: 10px\">\n";
	echo "	<strong>Date Range:</strong> ".date(DEFAULT_DATE_FORMAT, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"])." <strong>to</strong> ".date(DEFAULT_DATE_FORMAT, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]);
	echo "</div>";
	?>
	<table class="tableList" cellspacing="0" summary="System Report">
	<colgroup>
		<col class="general" />
		<col class="report-hours" />
		<col class="report-hours" />
	</colgroup>
	<thead>
		<tr>
			<td class="general borderl">Full Name</td>
			<td class="report-hours">Total Events</td>
			<td class="report-hours">Total Hours</td>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="3" style="padding-top: 10px">
				<input type="button" class="btn" value="Refresh" onclick="window.location.href = window.location" />
			</td>
		</tr>
	</tfoot>
	<tbody>
	<?php
	if ((is_array($report_results["people"])) && (count($report_results["people"]))) {
		$i = 0;
		foreach ($report_results["people"] as $result) {
			$duration_event = $result["events"]["total_minutes"];
			if ($duration_event) {
				?>
				<tr<?php echo (($i % 2) ? " class=\"odd\"" : ""); ?>>
					<td class="general"><?php echo html_encode($result["fullname"]); ?></td>
					<td class="report-hours"><?php echo $result["events"]["total_events"]; ?></td>
					<td class="report-hours"><?php echo display_hours($duration_event); ?></td>
				</tr>
				<?php
			}
			$i++;
		}
	}

    if ((is_array($report_results["courses"])) && (count($report_results["courses"]))) {
		$total_event = $report_results["courses"]["events"]["events_minutes"];
		if ($total_event) {
			?>
			<tr>
				<td colspan="3">&nbsp;</td>
			</tr>
			<tr class="modified">
				<td class="general">Final Totals:</td>
				<td class="report-hours"><?php echo $report_results["courses"]["events"]["total_events"]; ?></td>
				<td class="report-hours"><?php echo display_hours($total_event); ?></td>
			</tr>
			<?php
		}
	}
	?>
	</tbody>
	</table>
	<?php
	}
}