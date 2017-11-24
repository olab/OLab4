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
 * This API file returns an HTML table of the possible events for undergraduate
 * learners from the entrada_clerkship.events table.
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
} elseif (!$ENTRADA_ACL->amIAllowed("regionaled", "update")) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] do not have access to this module [".$MODULE."]");
} else {
	/**
	 * Clears all open buffers so we can return a plain response for the Javascript.
	 */
	ob_clear_open_buffers();

	$proxy_id = 0;
	$region_id = 0;
	$event_id = 0;

	if (isset($_POST["proxy_id"]) && ($tmp_input = clean_input($_POST["proxy_id"], "int"))) {
		$proxy_id = $tmp_input;
	}
	
	if (isset($_POST["region_id"]) && ($tmp_input = clean_input($_POST["region_id"], "int"))) {
		$region_id = $tmp_input;
	}

	if (isset($_POST["event_id"]) && ($tmp_input = clean_input($_POST["event_id"], "int"))) {
		$event_id = $tmp_input;
	}

	if ($proxy_id && $region_id) {
		$query = "	SELECT a.`event_id`, a.`rotation_id`, a.`event_title`, a.`event_start`, a.`event_finish`, c.`region_name`, d.`rotation_title`, e.`firstname`, e.`lastname`
					FROM `".CLERKSHIP_DATABASE."`.`events` AS a
					LEFT JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS b
					ON b.`event_id` = a.`event_id`
					LEFT JOIN `".CLERKSHIP_DATABASE."`.`regions` AS c
					ON c.`region_id` = a.`region_id`
					LEFT JOIN `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS d
					ON d.`rotation_id` = a.`rotation_id`
					LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS e
					ON e.`id` = ".$db->qstr($proxy_id)."
					WHERE a.`event_finish` > ".$db->qstr(time())."
					AND a.`event_status` = 'published'
					AND a.`requires_apartment` = '1'
					AND c.`manage_apartments` = '1'
					AND b.`etype_id` = ".$db->qstr($proxy_id)."
					AND a.`region_id` = ".$db->qstr($region_id)."
					ORDER BY a.`event_start`, a.`category_id`, c.`region_name` ASC";
		$results = $db->GetAll($query);
		if ($results) {
			?>
			<ul class="event-list">
				<li>
					<label for="event_id_0" class="form-nrequired"><input type="radio" id="event_id_0" name="event_id" value="0" checked="checked" /> This accommodation assignment <strong>is not associated</strong> with any event listed below.</label>
				</li>
				<?php
				foreach ($results as $result) {
					$click_url = ENTRADA_URL."/admin/regionaled?section=assign&id=".(int) $result["event_id"];
					echo "<li>\n";
					echo "	<input type=\"radio\" id=\"event_id_".$result["event_id"]."\" name=\"event_id\" value=\"".(int) $result["event_id"]."\" onclick=\"updateDates('".date("Y-m-d", $result["event_start"])."', '".date("Y-m-d", $result["event_finish"])."')\"".(($event_id == $result["event_id"]) ? " checked=\"checked\"" : "")." />";
					echo "	<label for=\"event_id_".$result["event_id"]."\" class=\"form-nrequired\">".html_encode($result["firstname"]." ".$result["lastname"]."'s ".$result["rotation_title"])." ".(($result["event_type"] == "elective") ? "Elective (Approved)" : "Core Rotation")." in ".html_encode($result["region_name"])."</label>\n";
					echo "	<div class=\"content-small\" style=\"margin-left: 20px;\"><strong>Dates:</strong> ".date("D M d/y", $result["event_start"])." - ".date("D M d/y", $result["event_finish"])."</div>\n";
					echo "</li>\n";
				}
				?>
			</ul>
			<?php
		}
	}
	exit;
}