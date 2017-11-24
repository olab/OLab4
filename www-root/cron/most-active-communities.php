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
 * Cron job responsible for managing in the communities_most_active.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <jellis@queensu.ca>
 * @copyright Copyright 2009 Queen's University. All Rights Reserved.
 *
*/

@set_time_limit(0);

@set_include_path(implode(PATH_SEPARATOR, array(
    __DIR__ . "/../core",
    __DIR__ . "/../core/includes",
    __DIR__ . "/../core/library",
    __DIR__ . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

$query = "SELECT a.`community_id`,
            COUNT(DISTINCT(c.`proxy_id`)) AS `total_members`,
            COUNT(DISTINCT(b.`chistory_id`)) AS `history_records`,
            ((COUNT(a.`community_id`)) * (COUNT(DISTINCT(b.`chistory_id`)) / COUNT(DISTINCT(c.`proxy_id`)))) AS `activity_rating`
            FROM `communities` AS a
            LEFT JOIN `community_history` AS b
            ON a.`community_id` = b.`community_id`
            LEFT JOIN `community_members` AS c
            ON a.`community_id` = c.`community_id`
            WHERE a.`community_active` = '1'
            AND b.`history_timestamp` >= '?'
            AND c.`member_active` = '1'
            GROUP BY a.`community_id`
            ORDER BY `activity_rating` DESC
            LIMIT 0, 10";
$results = $db->GetAll($query, array(strtotime("-60 days", strtotime("00:00:00"))));
if ($results) {
	$db->Execute("TRUNCATE TABLE `communities_most_active`");

	foreach ($results as $count => $result) {
		if ($result["community_id"]) {
			$db->AutoExecute("communities_most_active", array("community_id" => $result["community_id"], "activity_order" => $count), "INSERT");
		}
	}
}
