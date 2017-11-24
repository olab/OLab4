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
 * Serves as a detection method for conflicting events.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

@set_include_path(implode(PATH_SEPARATOR, array(
					dirname(__FILE__) . "/../core",
					dirname(__FILE__) . "/../core/includes",
					dirname(__FILE__) . "/../core/library",
                    dirname(__FILE__) . "/../core/library/vendor",
					get_include_path(),
				)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

if ((isset($_SESSION["isAuthorized"])) && ((bool) $_SESSION["isAuthorized"])) {
	
	if (isset($_POST) && isset($_POST["duration_segment"]) && is_array($_POST["duration_segment"]) && !empty($_POST["duration_segment"])) {
		$date = validate_calendars("event", true, false);
		
		$length = 0;
	
		foreach ($_POST["duration_segment"] as $segment) {
			$segment = clean_input($segment, array("trim", "int"));
			if ($segment) {
				$length += $segment;
			}
		}
		
		$start_time = $date["start"];
		$finish_time = $start_time + ($length * 60);
		
		$audience_type = clean_input($_POST["event_audience_type"], array("notags","trim"));
		$event_id = (int) $_POST["event_id"];
		
		$query = "	SELECT * FROM `events`  AS a JOIN `event_audience` AS b ON a.`event_id` = b.`event_id`
					WHERE (" . $start_time . " BETWEEN `event_start` AND `event_finish` 
					OR " . $finish_time . " BETWEEN `event_start` AND `event_finish`)";
		
		switch ($audience_type) {
			case "cohort" :
				$cohort = isset($_POST["associated_cohort"]) ? clean_input($_POST["associated_cohort"], array("int")) : 0;
				$query .= "AND b.audience_type = 'cohort' AND b.`audience_value` = ".$db->qstr($cohort);
			break;
			case "proxy_id" :
				$proxy_ids = isset($_POST["associated_student"]) ? clean_input($_POST["associated_student"], array("notags","trim")) : 0;
				$query .= "AND b.audience_type = 'proxy_id' AND b.`audience_value` IN(".$proxy_ids.")";
			break;
			case "organisation_id" :
				$org_id = isset($_POST["associated_organisation_id"]) ? clean_input($_POST["associated_organisation_id"], array("notags","trim")) : 0;
				$query .= "AND b.audience_type = 'organisation_id' AND b.`audience_value` IN(".$org_id.")";
			break;
			default :
				$query .= "AND 1=2";
			break;
		}
		
		$query .= " AND a.`event_id` != ".$event_id;
		
		$results = $db->GetAll($query);
	
		if ($results) {
			echo "This date is in conflict with existing events being attended by your selected audience. Please ensure you still want to select this timeframe.<br />";
			foreach ($results as $result) {
				echo "<a href=\"".ENTRADA_RELATIVE."/events?id=" . $result["event_id"] . "\">" . $result["event_title"] . "</a><br />";
			}
		}
	}
}
