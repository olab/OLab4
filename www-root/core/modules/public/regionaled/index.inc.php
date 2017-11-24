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
 * This file is used when a learner reviewing a complete list of accommodations
 * they have been assigned by the Regional Education office.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */

if (!defined("PARENT_INCLUDED")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("regionaled", "read")) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."\\'', 15000)";

	$NOTICE++;
	$NOTICESTR[]	= "You are not scheduled into any accommodations.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_notice();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	?>

	<h1>Regional Accommodations</h1>
	
	<?php
	$query = "	SELECT a.*, b.`apartment_title`, c.`region_name`, d.`id` AS `proxy_id`, d.`firstname`, d.`lastname`, IF(e.`group` = 'student', 'Clerk', 'Resident') AS `learner_type`
				FROM `".CLERKSHIP_DATABASE."`.`apartment_schedule` AS a
				LEFT JOIN `".CLERKSHIP_DATABASE."`.`apartments` AS b
				ON b.`apartment_id` = a.`apartment_id`
				LEFT JOIN `".CLERKSHIP_DATABASE."`.`regions` AS c
				ON c.`region_id` = b.`region_id`
				LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS d
				ON d.`id` = a.`proxy_id`
				LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS e
				ON e.`user_id` = d.`id`
				AND e.`app_id` = ".$db->qstr(AUTH_APP_ID)."
				AND e.`organisation_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation()) . "
				WHERE a.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
				ORDER BY a.`confirmed` ASC, a.`inhabiting_start` ASC";
	
	$results = $db->GetAll($query);
	if ($results) {
		?>
		<div class="display-generic">
			The <?php echo $APARTMENT_INFO["department_title"]; ?> Office has assigned you the following regional accommodations.
		</div>
		<table class="tableList" summary="List of accommodations been assigned to me by the Regional Education office.">
			<colgroup>
				<col class="modified" />
				<col class="title" />
				<col class="region" />
				<col class="date-smallest" />
				<col class="date-smallest" />
			</colgroup>
			<thead>
				<tr>
					<td class="modified">&nbsp;</td>
					<td class="title">Apartment Title</td>
					<td class="region">Region</td>
					<td class="date-smallest">Start Date</td>
					<td class="date-smallest">Finish Date</td>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ($results as $result) {
					if (!(int) $result["confirmed"]) {
						$confirmed = false;
					} else {
						$confirmed = true;
					}

					$click_url = ENTRADA_URL."/regionaled/view?id=".$result["aschedule_id"];
					echo "<tr".(!$confirmed ? " class=\"modified\"" : "").">\n";
					echo "	<td class=\"modified\">".($confirmed ?  "<img src=\"".ENTRADA_URL."/images/accept.png\" width=\"16\" height=\"16\" alt=\"Confirmed\" title=\"Confirmed\" />" : "<img src=\"".ENTRADA_URL."/images/exclamation.png\" width=\"16\" height=\"16\" alt=\"Requires Confirmation\" title=\"Requires Confirmation\" />")."</td>\n";
					echo "	<td class=\"title\"><a href=\"".$click_url."\" style=\"font-size: 11px\"".(!$confirmed ? " class=\"bold\"" : "").">".(!$confirmed ? "(Requires Your Confirmation) " : "").html_encode($result["apartment_title"])."</a></td>\n";
					echo "	<td class=\"region\"><a href=\"".$click_url."\" style=\"font-size: 11px\">".html_encode($result["region_name"])."</a></td>\n";
					echo "	<td class=\"date-smallest\"><a href=\"".$click_url."\" style=\"font-size: 11px\">".date("D M d/y", $result["inhabiting_start"])."</a></td>\n";
					echo "	<td class=\"date-smallest\"><a href=\"".$click_url."\" style=\"font-size: 11px\">".date("D M d/y", $result["inhabiting_finish"])."</a></td>\n";
					echo "</tr>\n";
				}
				?>
			</tbody>
		</table>
		<?php
	} else {
		$NOTICE++;
		$NOTICESTR[] = "You have not been assigned any accommodations.";
		echo display_notice();
	}
}