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
if (defined("NOTIFICATIONS_ACTIVE") && NOTIFICATIONS_ACTIVE) {
	require_once("Classes/notifications/NotificationUser.class.php");
	require_once("Classes/notifications/Notification.class.php");
	
	$query = "SELECT `nuser_id` FROM `notification_users` 
				WHERE `next_notification_date` <> 0 
				AND `next_notification_date` < ".$db->qstr(time())."
				AND `notify_active` = 1";
	
	$nuser_ids = $db->GetAll($query);
	if ($nuser_ids) {
		foreach ($nuser_ids as $nuser_id) {
			$nuser_id = $nuser_id["nuser_id"];
			$notification_user = NotificationUser::getByID($nuser_id);
			if ($notification_user) {
				$query = "SELECT `notification_id` FROM `notifications` 
							WHERE `nuser_id` = ".$db->qstr($nuser_id)."
							AND `sent` = 0";
				$notification_ids = $db->GetAll($query);
				if ($notification_ids) {
					if ($notification_user->getDigestMode()) {
						$notification = Notification::addDigest($nuser_id);
						$notification->send();
					} else {
						foreach ($notification_ids as $notification_id) {
							$notification_id = $notification_id["notification_id"];
							$notification = Notification::get($notification_id);
							$notification->send();
						}
					}
				}
				$notification_user->clearNextNotificationDate();
			}
		}
	}
}
?>