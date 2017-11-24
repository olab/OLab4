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
 * This file is used to view history (i.e. goals, objectives, file resources
 * etc.) within a learning event from the entrada.events table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EVENTS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('eventcontent', 'update', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

	if ($EVENT_ID) {
		$query		= "	SELECT a.*, b.`organisation_id`
					FROM `events` AS a
					LEFT JOIN `courses` AS b
					ON b.`course_id` = a.`course_id`
					WHERE a.`event_id` = ".$db->qstr($EVENT_ID);
		$event_info	= $db->GetRow($query);

		if ($event_info) {
			if (!$ENTRADA_ACL->amIAllowed(new EventContentResource($event_info["event_id"], $event_info["course_id"], $event_info["organisation_id"]), "update")) {
				application_log("error", "Someone attempted to view history for an event [".$EVENT_ID."] that they were not the coordinator for.");

				header("Location: ".ENTRADA_URL."/admin/".$MODULE);
				exit;
			} else {
				$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/events?".replace_query(array("section" => "history", "id" => $EVENT_ID)), "title" => "Event History");

				$LASTUPDATED = $event_info["updated_date"];

				/**
				 * Fetch event content history
				 * If no history just display the creation information
				 */
				$query = "	SELECT a.`history_message` AS message, a.`history_timestamp` AS timestamp, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`
						FROM `event_history` AS a
						LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
						ON a.`proxy_id` = b.`id`
						WHERE a.`event_id`  = ".$db->qstr($EVENT_ID)."
						ORDER BY `history_timestamp` DESC, `history_message` ASC";
				$history = $db->GetAll($query);

				if (!$history) {
					$query = "	SELECT CONCAT_WS(' ', `firstname`, `lastname`) AS `fullname`,
							$LASTUPDATED AS timestamp, 'created this learning event.' AS message
							FROM `".AUTH_DATABASE."`.`user_data`
							WHERE `id`  = ".$db->qstr($event_info["updated_by"]);
					$history = $db->GetAll($query);
				}

				if ($history) {
					events_subnavigation($event_info,'history');
					?>
					<h2 title="Event History Section">Event History</h2>
					<p>
					<?php
					$previous_day = 0;
					foreach ($history as $key => $result) {
						$current_day = mktime(0, 0, 0, date("m",$result["timestamp"]), date("d", $result["timestamp"]), date("Y", $result["timestamp"]));
						if ($current_day != $previous_day) {
							$previous_day = $current_day;
							if ($key > 0) {
								echo "</ul></p>";
						        }
						        echo "<strong>".date("F j, Y",$current_day)."</strong><ul class=\"history\">\n";
						}
						echo "<li>".date("g:ia ", $result["timestamp"]).$result["fullname"]." ".$result["message"]."</li>";
					}
					?>
					</ul></p>
					<?php
				}
			}
		} else {
			$ERROR++;
			$ERRORSTR[] = "In order to view event update history you must provide a valid event identifier. The provided ID does not exist in this system.";

			echo display_error();

			application_log("notice", "Failed to provide a valid event identifer when attempting to view event updates.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "In order to view event update history you must provide the events identifier.";

		echo display_error();

		application_log("notice", "Failed to provide event identifer when attempting to view history of an event.");
	}
}