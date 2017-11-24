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
 * Generates static ICS calendar files for courses within an organization.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2006 Queen's University. All Rights Reserved.
 *
 */

@set_time_limit(0);
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
require_once("Entrada/icalendar/class.ical.inc.php");

$org_id = 0;

if (isset($_SERVER["argv"][1])) {
    $org_id = clean_input($_SERVER["argv"][1], "int");
}

if ($org_id) {
	$cohorts = groups_get_active_cohorts($org_id);
    if ($cohorts) {
        foreach ($cohorts as $cohort) {
            $query = "SELECT a.*,IF(a.`room_id` IS NULL, a.`event_location`, CONCAT(e.`building_code`, '-', d.`room_number`)) AS `event_location`, c.`proxy_id`, CONCAT_WS(' ', d.`firstname`, d.`lastname`) AS `fullname`, d.`email`
                        FROM `events` AS a
                        LEFT JOIN `event_audience` AS b
                        ON b.`event_id` = a.`event_id`
                        LEFT JOIN `event_contacts` AS c
                        ON c.`event_id` = a.`event_id`
                        LEFT JOIN `" . AUTH_DATABASE . "`.`user_data` AS d
                        ON d.`id` = c.`proxy_id`
                        LEFT JOIN `global_lu_rooms` AS d
                        ON d.`room_id` = a.`room_id`
                        LEFT JOIN `global_lu_buildings` AS e
                        ON e.`building_id` = d.`building_id`
                        WHERE b.`audience_type` = 'cohort'
                        AND b.`audience_value` = " . $db->qstr($cohort["group_id"]) . "
                        AND (c.`contact_order` IS NULL OR c.`contact_order` = '0')
                        ORDER BY a.`event_start` ASC";
            $results = $db->GetAll($query);
            if ($results) {
                $ical = new iCal("-//" . html_encode($_SERVER["HTTP_HOST"]) . "//iCal" . (($cohort["group_name"]) ? html_encode($cohort["group_name"]) : "") . " Learning Events Calendar MIMEDIR//EN", 1, ENTRADA_ABSOLUTE . "/calendars/", (($cohort) ? clean_input($cohort["group_name"], "numeric") : "all_cohorts")); // (ProgrammID, Method (1 = Publish | 0 = Request), Download Directory)
                foreach ($results as $result) {
                    $ical->addEvent(
                        array((($result["fullname"] != "") ? $result["fullname"] : ""), (($result["email"]) ? $result["email"] : "")), // Organizer
                        (int)$result["event_start"], // Start Time (timestamp; for an allday event the startdate has to start at YYYY-mm-dd 00:00:00)
                        (int)$result["event_finish"], // End Time (write 'allday' for an allday event instead of a timestamp)
                        $result["event_location"], // Location
                        1, // Transparancy (0 = OPAQUE | 1 = TRANSPARENT)
                        array("Phase " . $result["event_phase"], html_encode($cohort["group_name"])), // Array with Strings
                        strip_tags($result["event_message"]), // Description
                        strip_tags($result["event_title"]), // Title
                        1, // Class (0 = PRIVATE | 1 = PUBLIC | 2 = CONFIDENTIAL)
                        array(), // Array (key = attendee name, value = e-mail, second value = role of the attendee [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON])
                        5, // Priority = 0-9
                        0, // frequency: 0 = once, secoundly - yearly = 1-7
                        0, // recurrency end: ('' = forever | integer = number of times | timestring = explicit date)
                        0, // Interval for frequency (every 2,3,4 weeks...)
                        array(), // Array with the number of the days the event accures (example: array(0,1,5) = Sunday, Monday, Friday
                        1, // Startday of the Week ( 0 = Sunday - 6 = Saturday)
                        "", // exeption dates: Array with timestamps of dates that should not be includes in the recurring event
                        0,  // Sets the time in minutes an alarm appears before the event in the programm. no alarm if empty string or 0
                        1, // Status of the event (0 = TENTATIVE, 1 = CONFIRMED, 2 = CANCELLED)
                        str_replace("http://", "https://", ENTRADA_URL) . "/events?id=" . (int)$result["event_id"], // optional URL for that event
                        "en", // Language of the Strings
                        md5((int) $result["event_id"])
                    );
                }

                $ical->writeFile();
            }
        }
    }
} else {
    echo "When running the calender generation cron job an invalid org_id was provided, or no org_id was provided.";
}
