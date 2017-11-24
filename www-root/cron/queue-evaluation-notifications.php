<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Cron job responsible for sending pending notifications.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
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

//queue notifications for each user with the evaluations which have opened for them in the last 24 hours.
$query = "SELECT *, '0' AS `event_id` FROM `evaluations` AS a
			JOIN `evaluation_forms` AS b
			ON a.`eform_id` = b.`eform_id`
			JOIN `evaluations_lu_targets` AS c
			ON b.`target_id` = c.`target_id`
			WHERE a.`evaluation_start` >= ".$db->qstr(strtotime("-1 day"))."
			AND a.`evaluation_start` <= ".$db->qstr(time())."
			AND a.`evaluation_finish` >= ".$db->qstr((time() - (ONE_WEEK * 10)))."
			AND c.`target_shortname` NOT IN ('preceptor', 'rotation_core', 'rotation_elective')
			
			UNION
			
			SELECT a.*, b.*, c.*, e.`event_id` FROM `evaluations` AS a
			JOIN `evaluation_forms` AS b
			ON a.`eform_id` = b.`eform_id`
			JOIN `evaluations_lu_targets` AS c
			ON b.`target_id` = c.`target_id`
			AND c.`target_shortname` IN ('preceptor', 'rotation_core', 'rotation_elective')
			JOIN `evaluation_targets` AS d
			ON a.`evaluation_id` = d.`evaluation_id`
			AND d.`target_type` = 'rotation_id'
			JOIN `".CLERKSHIP_DATABASE."`.`events` AS e
			ON d.`target_value` = e.`rotation_id`
			AND a.`evaluation_start` <= e.`event_finish`
			AND a.`evaluation_finish` >= e.`event_finish`
			JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS f
			ON e.`event_id` = f.`event_id`
			JOIN `evaluation_evaluators` AS g
			ON a.`evaluation_id` = g.`evaluation_id`
			AND g.`evaluator_type` = 'cohort'
			JOIN `group_members` AS h
			ON g.`evaluator_value` = h.`group_id`
			AND h.`proxy_id` = f.`etype_id`
			AND h.`member_active`
			WHERE e.`event_finish` <= ".$db->qstr(strtotime("+5 days"))."
			AND e.`event_finish` >= ".$db->qstr(strtotime("-36 hours"))."
			
			
			UNION
			
			SELECT a.*, b.*, c.*, e.`event_id` FROM `evaluations` AS a
			JOIN `evaluation_forms` AS b
			ON a.`eform_id` = b.`eform_id`
			JOIN `evaluations_lu_targets` AS c
			ON b.`target_id` = c.`target_id`
			AND c.`target_shortname` IN ('preceptor', 'rotation_core', 'rotation_elective')
			JOIN `evaluation_targets` AS d
			ON a.`evaluation_id` = d.`evaluation_id`
			AND d.`target_type` = 'rotation_id'
			JOIN `".CLERKSHIP_DATABASE."`.`events` AS e
			ON d.`target_value` = e.`rotation_id`
			AND a.`evaluation_start` <= e.`event_finish`
			AND a.`evaluation_finish` >= e.`event_finish`
			JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS f
			ON e.`event_id` = f.`event_id`
			JOIN `evaluation_evaluators` AS g
			ON a.`evaluation_id` = g.`evaluation_id`
			AND g.`evaluator_type` = 'cgroup_id'
			JOIN `course_group_audience` AS h
			ON g.`evaluator_value` = h.`cgroup_id`
			AND h.`proxy_id` = f.`etype_id`
			AND h.`active` = 1
			WHERE e.`event_finish` <= ".$db->qstr(strtotime("+5 days"))."
			AND e.`event_finish` >= ".$db->qstr(strtotime("-36 hours"))."
			
			
			UNION
			
			SELECT a.*, b.*, c.*, e.`event_id` FROM `evaluations` AS a
			JOIN `evaluation_forms` AS b
			ON a.`eform_id` = b.`eform_id`
			JOIN `evaluations_lu_targets` AS c
			ON b.`target_id` = c.`target_id`
			AND c.`target_shortname` IN ('preceptor', 'rotation_core', 'rotation_elective')
			JOIN `evaluation_targets` AS d
			ON a.`evaluation_id` = d.`evaluation_id`
			AND d.`target_type` = 'rotation_id'
			JOIN `".CLERKSHIP_DATABASE."`.`events` AS e
			ON d.`target_value` = e.`rotation_id`
			AND a.`evaluation_start` <= e.`event_finish`
			AND a.`evaluation_finish` >= e.`event_finish`
			JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS f
			ON e.`event_id` = f.`event_id`
			JOIN `evaluation_evaluators` AS g
			ON a.`evaluation_id` = g.`evaluation_id`
			AND g.`evaluator_type` = 'proxy_id'
			AND g.`evaluator_value` = f.`etype_id`
			WHERE e.`event_finish` <= ".$db->qstr(strtotime("+5 days"))."
			AND e.`event_finish` >= ".$db->qstr(strtotime("-36 hours"));
$new_evaluations = $db->GetAll($query);

$pending_evaluations = array();

if ($new_evaluations) {
    foreach ($new_evaluations as $evaluation) {
        $pending_evaluations[$evaluation["evaluation_id"].(isset($evaluation["event_id"]) && $evaluation["event_id"] ? "-".$evaluation["event_id"] : "")] = Classes_Evaluation::getEvaluationsPending($evaluation, true);
    }
}
foreach ($pending_evaluations as $pending_evaluation_users) {
    foreach ($pending_evaluation_users as $pending_evaluation) {
        $evaluation_id = $pending_evaluation["evaluation_id"];
        require_once("Classes/notifications/NotificationUser.class.php");
        require_once("Classes/notifications/Notification.class.php");
        $proxy_id = $pending_evaluation["user"]["id"];
        $notification_user = NotificationUser::get($proxy_id, "evaluation", $evaluation_id, $proxy_id);
        if (!$notification_user) {
            $notification_user = NotificationUser::add($proxy_id, "evaluation", $evaluation_id, $proxy_id);
        }
        $query = "SELECT * FROM `notifications` 
                    WHERE `nuser_id` = ".$db->qstr($notification_user->getID())." 
                    AND `proxy_id` = ".$db->qstr($proxy_id)."
                    AND (`sent_date` = 0 OR `sent_date` >= ".$db->qstr(strtotime("-7 days")).")";
        $recent_notifications = $db->GetAll($query);
        if (!isset($recent_notifications) || !$recent_notifications) {
            Notification::add($notification_user->getID(), $proxy_id, $evaluation_id, (isset($pending_evaluation["event_id"]) && $pending_evaluation["event_id"] ? $pending_evaluation["event_id"] : $pending_evaluation["event_id"]));
        }
    }
}

//queue notifications for each user with the evaluations which have closed for them in the last 24 hours.
$query = "SELECT *, '0' AS `event_id` FROM `evaluations` AS a
			JOIN `evaluation_forms` AS b
			ON a.`eform_id` = b.`eform_id`
			JOIN `evaluations_lu_targets` AS c
			ON b.`target_id` = c.`target_id`
			WHERE a.`evaluation_start` <= ".$db->qstr(strtotime("-1 day"))."
			AND a.`evaluation_finish` >= ".$db->qstr(strtotime("-10 weeks"))."
			AND a.`evaluation_finish` <= ".$db->qstr(time())."
			AND c.`target_shortname` NOT IN ('preceptor', 'rotation_core', 'rotation_elective')
			
			UNION
			
			SELECT a.*, b.*, c.*, e.`event_id` FROM `evaluations` AS a
			JOIN `evaluation_forms` AS b
			ON a.`eform_id` = b.`eform_id`
			JOIN `evaluations_lu_targets` AS c
			ON b.`target_id` = c.`target_id`
			AND c.`target_shortname` IN ('preceptor', 'rotation_core', 'rotation_elective')
			JOIN `evaluation_targets` AS d
			ON a.`evaluation_id` = d.`evaluation_id`
			AND d.`target_type` = 'rotation_id'
			JOIN `".CLERKSHIP_DATABASE."`.`events` AS e
			ON d.`target_value` = e.`rotation_id`
			AND a.`evaluation_start` <= e.`event_finish`
			AND a.`evaluation_finish` >= e.`event_finish`
			JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS f
			ON e.`event_id` = f.`event_id`
			JOIN `evaluation_evaluators` AS g
			ON a.`evaluation_id` = g.`evaluation_id`
			AND g.`evaluator_type` = 'cohort'
			JOIN `group_members` AS h
			ON g.`evaluator_value` = h.`group_id`
			AND h.`proxy_id` = f.`etype_id`
			AND h.`member_active`
			WHERE a.`evaluation_start` <= ".$db->qstr(strtotime("-1 day"))."
			AND e.`event_finish` >= ".$db->qstr(time() - CLERKSHIP_EVALUATION_LOCKOUT)."
			AND e.`event_finish` <= ".$db->qstr(time() - CLERKSHIP_EVALUATION_TIMEOUT)."
			
			UNION
			
			SELECT a.*, b.*, c.*, e.`event_id` FROM `evaluations` AS a
			JOIN `evaluation_forms` AS b
			ON a.`eform_id` = b.`eform_id`
			JOIN `evaluations_lu_targets` AS c
			ON b.`target_id` = c.`target_id`
			AND c.`target_shortname` IN ('preceptor', 'rotation_core', 'rotation_elective')
			JOIN `evaluation_targets` AS d
			ON a.`evaluation_id` = d.`evaluation_id`
			AND d.`target_type` = 'rotation_id'
			JOIN `".CLERKSHIP_DATABASE."`.`events` AS e
			ON d.`target_value` = e.`rotation_id`
			AND a.`evaluation_start` <= e.`event_finish`
			AND a.`evaluation_finish` >= e.`event_finish`
			JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS f
			ON e.`event_id` = f.`event_id`
			JOIN `evaluation_evaluators` AS g
			ON a.`evaluation_id` = g.`evaluation_id`
			AND g.`evaluator_type` = 'cgroup_id'
			JOIN `course_group_audience` AS h
			ON g.`evaluator_value` = h.`cgroup_id`
			AND h.`proxy_id` = f.`etype_id`
			AND h.`active` = 1
			WHERE a.`evaluation_start` <= ".$db->qstr(strtotime("-1 day"))."
			AND e.`event_finish` >= ".$db->qstr(time() - CLERKSHIP_EVALUATION_LOCKOUT)."
			AND e.`event_finish` <= ".$db->qstr(time() - CLERKSHIP_EVALUATION_TIMEOUT)."
			
			UNION
			
			SELECT a.*, b.*, c.*, e.`event_id` FROM `evaluations` AS a
			JOIN `evaluation_forms` AS b
			ON a.`eform_id` = b.`eform_id`
			JOIN `evaluations_lu_targets` AS c
			ON b.`target_id` = c.`target_id`
			AND c.`target_shortname` IN ('preceptor', 'rotation_core', 'rotation_elective')
			JOIN `evaluation_targets` AS d
			ON a.`evaluation_id` = d.`evaluation_id`
			AND d.`target_type` = 'rotation_id'
			JOIN `".CLERKSHIP_DATABASE."`.`events` AS e
			ON d.`target_value` = e.`rotation_id`
			AND a.`evaluation_start` <= e.`event_finish`
			AND a.`evaluation_finish` >= e.`event_finish`
			JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS f
			ON e.`event_id` = f.`event_id`
			JOIN `evaluation_evaluators` AS g
			ON a.`evaluation_id` = g.`evaluation_id`
			AND g.`evaluator_type` = 'proxy_id'
			AND g.`evaluator_value` = f.`etype_id`
			WHERE a.`evaluation_start` <= ".$db->qstr(strtotime("-1 day"))."
			AND e.`event_finish` >= ".$db->qstr(time() - CLERKSHIP_EVALUATION_LOCKOUT)."
			AND e.`event_finish` <= ".$db->qstr(time() - CLERKSHIP_EVALUATION_TIMEOUT);
$ended_evaluations = $db->GetAll($query);
if ($ended_evaluations) {
    foreach ($ended_evaluations as $evaluation) {
        $overdue_evaluations[$evaluation["evaluation_id"].(isset($evaluation["event_id"]) && $evaluation["event_id"] ? "-".$evaluation["event_id"] : "")] = Classes_Evaluation::getOverdueEvaluations($evaluation);
    }
}
foreach ($overdue_evaluations as $overdue_evaluation_users) {
    foreach ($overdue_evaluation_users as $overdue_evaluation) {
        $evaluation_id = $overdue_evaluation["evaluation_id"];
        require_once("Classes/notifications/NotificationUser.class.php");
        require_once("Classes/notifications/Notification.class.php");
        $proxy_id = $overdue_evaluation["user"]["id"];
        $notification_user = NotificationUser::get($proxy_id, "evaluation_overdue", $evaluation_id, $proxy_id);
        if (!$notification_user) {
            $notification_user = NotificationUser::add($proxy_id, "evaluation_overdue", $evaluation_id, $proxy_id);
        }
        $query = "SELECT * FROM `notifications` 
                    WHERE `nuser_id` = ".$db->qstr($notification_user->getID())." 
                    AND `proxy_id` = ".$db->qstr($proxy_id)."
                    AND (`sent_date` = 0 OR `sent_date` >= ".$db->qstr(strtotime("-7 days")).")";
        $recent_notifications = $db->GetAll($query);
        if (!isset($recent_notifications) || !$recent_notifications) {
            Notification::add($notification_user->getID(), $proxy_id, $evaluation_id, (isset($overdue_evaluation["event_id"]) && $overdue_evaluation["event_id"] ? $overdue_evaluation["event_id"] : $overdue_evaluation["event_id"]));
        }
    }
}

?>