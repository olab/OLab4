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

if((!defined("PARENT_INCLUDED")) || (!defined("IN_REPORTS"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->amIAllowed('report', 'read', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[]	= array("url" => "", "title" => "Open Incidents By Follow-Up Date");

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
	if($STEP == 2) {
	$int_use_cache	= true;

	$report_results	= array();

	$organisation_where = " AND (b.`organisation_id` = ".$ENTRADA_USER->getActiveOrganisation().") ";
	
	$query	= "
			SELECT a.*, CONCAT_WS(', ', b.`lastname`, b.`firstname`) AS `fullname`
			FROM `".AUTH_DATABASE."`.`user_incidents` AS a
			LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
			ON b.`id` = a.`proxy_id`
			LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS c
			ON b.`id` = c.`user_id`
			WHERE c.`app_id` = ".$db->qstr(AUTH_APP_ID)."
			AND (a.`follow_up_date` BETWEEN ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"])." AND ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]).$organisation_where.")
			ORDER BY a.`follow_up_date` ASC";
	if($int_use_cache) {
		$results	= $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
	} else {
		$results	= $db->GetAll($query);
	}

	echo "<h1>Open Incidents By Follow-Up Date</h1>";
	echo "<div class=\"content-small\" style=\"margin-bottom: 10px\">\n";
	echo "	<strong>Date Range:</strong> ".date(DEFAULT_DATE_FORMAT, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_start"])." <strong>to</strong> ".date(DEFAULT_DATE_FORMAT, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["reporting_finish"]);
	echo "</div>";
	?>
	<table class="tableList" cellspacing="0" summary="Open Incidents By Follow-Up Date">
	<colgroup>
		<col class="general" />
		<col class="title" />
		<col class="date" />
	</colgroup>
	<tfoot>
		<tr>
			<td colspan="3" style="padding-top: 10px">
				<input style="no-printing" type="button" class="btn" value="Refresh" onclick="window.location.href = window.location" />
			</td>
		</tr>
	</tfoot>
	<thead>
		<tr>
			<td class="general" style="border-left: 1px #999999 solid">Student Name</td>
			<td class="date sortedASC"><a>Follow-up Date</a></td>
			<td class="title">Title</td>
		</tr>
	</thead>
	<tbody>
	<?php
	foreach($results as $result) {
		$url = ENTRADA_URL."/admin/users/manage/incidents?section=edit&id=".$result["proxy_id"]."&incident-id=".$result["incident_id"];
		echo "<tr>\n";
		echo "	<td class=\"general\"><a href=\"".$url."\" title=\"Student Name: ".html_encode($result["fullname"])."\">".html_encode($result["fullname"])."</a></td>\n";
		echo "	<td class=\"date\"><a href=\"".$url."\" title=\"Incident Follow-Up Date\">".(isset($result["follow_up_date"]) && ((int)$result["follow_up_date"]) ? date(DEFAULT_DATE_FORMAT, $result["follow_up_date"]) : "")."</a></td>\n";
		echo "	<td class=\"title\"><a href=\"".$url."\" title=\"Incident Title: ".html_encode($result["incident_title"])."\">[".html_encode($result["incident_severity"])."] ".html_encode(limit_chars($result["incident_title"], 40))."</a></td>\n";
		echo "</tr>\n";
	}
	?>
	</tbody>
	</table>
	<?php
	}
}
?>