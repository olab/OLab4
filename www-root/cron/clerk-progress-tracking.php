<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Cron job responsible for notifying clerks that they are behind in their logging.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2011 Queen's University. All Rights Reserved.
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

$query 	= "SELECT * FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS a
		LEFT JOIN `courses` AS b
		ON a.`course_id` = b.`course_id`
		WHERE a.`rotation_id` < ".$db->qstr(MAX_ROTATION);
$rotations = $db->GetAll($query);
if ($rotations) {
	foreach ($rotations as $rotation) {
		$query = "SELECT `proxy_id` FROM `course_contacts` 
					WHERE `course_id` = ".$db->qstr($rotation["course_id"])." 
					AND `contact_type` = 'director' 
					ORDER BY `contact_order` ASC 
					LIMIT 0, 1";
		$director_id = $db->GetOne($query);
		if ($director_id) {
			$rotation["director_id"] = $director_id;
		} else {
			$rotation["director_id"] = $rotation["pcoord_id"];
		}
		$query		= "SELECT a.*, b.`etype_id` as `proxy_id`, c.*, CONCAT_WS(' ', e.`firstname`, e.`lastname`) as `fullname`, e.`email`, MIN(a.`event_start`) as `start`, MAX(a.`event_finish`) AS `finish`, g.`clerk_accepted`, g.`administrator_accepted`
					FROM `".CLERKSHIP_DATABASE."`.`events` AS a
					JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS b
					ON b.`event_id` = a.`event_id`
					JOIN `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS c
					ON a.`rotation_id` = c.`rotation_id`
					JOIN `".AUTH_DATABASE."`.`user_data` AS e
					ON b.`etype_id` = e.`id`
					JOIN `".AUTH_DATABASE."`.`user_access` AS f
					ON e.`id` = f.`user_id`
					AND f.`app_id` = '".AUTH_APP_ID."'
                    AND f.`organisation_id` = '1'
					LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_deficiency_plans` AS g
					ON b.`etype_id` = g.`proxy_id`
					AND a.`rotation_id` = g.`rotation_id`
					WHERE b.`econtact_type` = 'student'
                    AND a.`event_status` != 'trash'
					AND a.`event_start` > ".$db->qstr(strtotime("December 25th, 2009"))."
					AND f.`group` >= 'student'
					AND f.`role` >= ".$db->qstr(CLERKSHIP_FIRST_CLASS)."
					AND c.`rotation_id` = ".$db->qstr($rotation["rotation_id"])."
					GROUP BY b.`etype_id`, a.`rotation_id`
					ORDER BY `fullname` ASC";
		$results = $db->GetAll($query);
		if ($results) {
			$query = "DELETE FROM `".CLERKSHIP_DATABASE."`.`logbook_overdue` WHERE `rotation_id` = ".$db->qstr($rotation["rotation_id"]);
			$db->Execute($query);
			$count = 0;
			foreach ($results as $clerk) {
                if ($clerk["rotation_id"] && ($clerk["finish"] > strtotime("-12 months")) && ($clerk["administrator_accepted"] != 1)) {
                    if ($clerk["start"] < time()) {
                        if (time() >= ($clerk["finish"] + (ONE_WEEK * 6))) {
                            clerkship_progress_send_notice(CLERKSHIP_SIX_WEEKS_PAST, $rotation, $clerk);
                        } elseif (time() >= $clerk["finish"]) {
                            clerkship_progress_send_notice(CLERKSHIP_ROTATION_ENDED, $rotation, $clerk);
                        } elseif ((time() - $clerk["start"]) >= (($clerk["finish"] - $clerk["start"]) - ONE_WEEK)) {
                            clerkship_progress_send_notice(CLERKSHIP_ONE_WEEK_PRIOR, $rotation, $clerk);
                        } elseif ((time() - $clerk["start"]) >= (($clerk["finish"] - $clerk["start"]) * $rotation["percent_period_complete"] / 100)) {
                            clerkship_progress_send_notice(CLERKSHIP_ROTATION_PERIOD, $rotation, $clerk);
                        }
                    }
                }
			}
		} else {
			echo $db->ErrorMsg();
		}
		clerkship_send_queued_notifications($rotation["rotation_id"], $rotation["rotation_title"], $rotation["director_id"]);
	}
} else {
	echo $db->ErrorMsg();
}
