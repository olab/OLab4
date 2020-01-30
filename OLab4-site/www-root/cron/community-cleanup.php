<?php

/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Cron job responsible for deactivating inactive communities.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
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
require_once("Classes/utility/Template.class.php");
require_once("Classes/utility/TemplateMailer.class.php");
$SEND_EMAIL_NOTIFICATIONS = false;
$SET_INACTIVE_COMMUNITIES = false;

$mail = new TemplateMailer(new Zend_Mail());
$query = "  SELECT a.`community_id`, a.`community_title`, a.`community_url`, a.`community_active`, b.`history_timestamp`, c.`timestamp`
			FROM `communities` AS a
			LEFT JOIN `community_history` AS b
			ON a.`community_id` = b.`community_id`
			AND b.`history_timestamp` >=" . $db->qstr(strtotime("-6 months +1 day 00:00:00")). "
			LEFT JOIN `statistics` AS c
			ON c.`module` LIKE CONCAT('community:', a.`community_id`, '%')
			AND c.`timestamp` >=" . $db->qstr(strtotime("-6 months +1 day 00:00:00")) . "
			WHERE a.`community_id` NOT IN (SELECT `community_id` FROM `community_courses`)
			AND a.`community_active` = '1'
			AND b.`history_timestamp` IS NULL
			AND c.`timestamp` IS NULL
			GROUP BY a.`community_id`";
$results = $db->GetAll($query);
if ($results) {
	foreach ($results as $result) {
		$query = "  SELECT a.`community_id`, MAX(a.`history_timestamp`) AS `history_timestamp`, MAX(b.`timestamp`) AS `timestamp`
					FROM `community_history` AS a
					LEFT JOIN statistics AS b
					ON b.`module` LIKE CONCAT('community:', a.`community_id`, '%')
					WHERE a.`community_id` =" . $db->qstr($result["community_id"]) . "
					GROUP BY `community_id`";
		$history_timestamps = $db->GetAll($query);
		if ($history_timestamps) {
			foreach ($history_timestamps as $history_timestamp) {
				//of the two timestamps returned check which is most recent
				if ($history_timestamp["history_timestamp"] > $history_timestamp["timestamp"]) {
					$timestamp = $history_timestamp["history_timestamp"];
				} else if ($history_timestamp["history_timestamp"] < $history_timestamp["timestamp"]) {
					$timestamp = $history_timestamp["timestamp"];
				} else if ($history_timestamp["history_timestamp"] == $history_timestamp["timestamp"]) {
					$timestamp = $history_timestamp["history_timestamp"];
				}

				$query = "  SELECT a.`community_id`, a.`proxy_id`, a.`member_acl`, b.`id`, b.`firstname`, b.`lastname`, b.`email`
							FROM `community_members` AS a
							LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
							ON a.`proxy_id` = b.`id`
							WHERE a.`community_id` = " . $db->qstr($history_timestamp["community_id"]) ."
							AND a.member_acl = '1'";
				if (($timestamp >= strtotime("-6 months 00:00:00") && $timestamp <= strtotime("-6 months 23:59:59")))  {
					$admin_info_results = $db->GetAll($query);
					if ($admin_info_results) {
						$xml_file = $ENTRADA_TEMPLATE->absolute()."/email/community-cleanup.xml";
						$template = new Template($xml_file);
						foreach ($admin_info_results as $admin_info) {
							$keywords = array (
								"application_name" => APPLICATION_NAME,
								"to_fullname" => $admin_info["firstname"]. " " . $admin_info["lastname"],
								"time_inactive" => "6 months,",
								"community_title" => $result["community_title"],
								"message" => "if you wish to deactivate the community you can do so by clicking the following link",
								"community_url" => ENTRADA_URL."/communities?section=modify&community=".$result["community_id"] . "&deactivate"
							);

							$from = array("email" => $AGENT_CONTACTS["administrator"]["email"], "firstname" => $AGENT_CONTACTS["administrator"]["firstname"], "lastname" => $AGENT_CONTACTS["administrator"]["lastname"]);
							$to = array("email" => $admin_info["email"], "firstname" => $admin_info["firstname"], "lastname" => $admin_info["lastname"]);
							if ($SEND_EMAIL_NOTIFICATIONS) {
								if ($mail->send($template, $to, $from, DEFAULT_LANGUAGE, $keywords)) {
									application_log("cron", "Sent 6 month community inactivity e-mail to admin: [" . $admin_info["email"]. "]" . " community " . $admin_info["community_id"]);
								} else {
									application_log("error", "Unable to send e-mail notification to admin [". $admin_info["email"]. "]" . " community " . $admin_info["community_id"]);
								}
							} else {
								application_log("cron", "Action: Send 6 month community inactivity e-mail to admin [". $admin_info["email"]. "]" . " community " . $admin_info["community_id"]);
							}
						}
					}
				} elseif (($timestamp >= strtotime("-12 months 00:00:00") && $timestamp <= strtotime("-12 months 23:59:59"))) {
					$admin_info_results = $db->GetAll($query);
					if ($admin_info_results) {
						$xml_file = $ENTRADA_TEMPLATE->absolute()."/email/community-cleanup.xml";
						$template = new Template($xml_file);
						foreach ($admin_info_results as $admin_info) {
							$keywords = array (
								"application_name" => APPLICATION_NAME,
								"to_fullname" => $admin_info["firstname"]. " " . $admin_info["lastname"],
								"time_inactive" => "12 months,",
								"community_title" => $result["community_title"],
								"message" => "and it will be automatically deactivated in 7 days unless content on the site is updated in some way. If you wish to deactivate the community you can do so by clicking the following link",
								"community_url" => ENTRADA_URL."/communities?section=modify&community=".$result["community_id"] . "&deactivate"
							);
							$from = array("email" => $AGENT_CONTACTS["administrator"]["email"], "firstname" => $AGENT_CONTACTS["administrator"]["firstname"], "lastname" => $AGENT_CONTACTS["administrator"]["lastname"]);
							$to = array("email" => $admin_info["email"], "firstname" => $admin_info["firstname"], "lastname" => $admin_info["lastname"]);

							if ($SEND_EMAIL_NOTIFICATIONS) {
								if ($mail->send($template, $to, $from, DEFAULT_LANGUAGE, $keywords)) {
									application_log("cron", "Sent 12 month community inactivity e-mail to admin: [" . $admin_info["email"]. "]" . " community " . $admin_info["community_id"]);
								} else {
									application_log("error", "Unable to send e-mail notification to admin [". $admin_info["email"]. "]" . " community " . $admin_info["community_id"]);
								}
							} else {
								application_log("cron", "Action: Send 12 month community inactivity e-mail to admin [". $admin_info["email"]. "]" . " community " . $admin_info["community_id"]);
							}
						}
					}
				} elseif (($timestamp >= strtotime("-12 months -7 days 00:00:00") && $timestamp <= strtotime("-12 months -7 days 23:59:59")))  {
					$query = "  SELECT * FROM `communities`
								WHERE `community_id` = " . $db->qstr($history_timestamp["community_id"]);
					$inactive_community_results = $db->GetAll($query);
					if ($inactive_community_results) {
						foreach($inactive_community_results as $inactive_community) {
							$query = "  UPDATE `communities` SET `community_active` = '0'
										WHERE `community_id` =" . $db->qstr($inactive_community["community_id"]);
							if ($SET_INACTIVE_COMMUNITIES) {
								$db->Execute($query);
								if ($query) {
									application_log("cron", "Community ". $history_timestamp["community_id"]. " inactive for 12 months 7 days. Set to inactive.");
								} else {
									application_log("error", "Unable to deactivate community ". $history_timestamp["community_id"]);
								}
							}
							else {
								application_log("cron", "Action: Community ". $history_timestamp["community_id"]. " inactive for 12 months 7 days. Set to inactive.");
							}
						}
					}
				} elseif (($timestamp < strtotime("-12 months -7 days 00:00:00"))) {
					$query = "  SELECT * FROM `communities`
								WHERE `community_id` = " . $db->qstr($history_timestamp["community_id"]);
					$inactive_community_results = $db->GetAll($query);
					if ($inactive_community_results) {
						foreach($inactive_community_results as $inactive_community) {
							echo "\n";
							echo $history_timestamp["history_timestamp"]."\n";
							echo $history_timestamp["timestamp"]."\n";
							$query = "  UPDATE `communities` SET `community_active` = '0'
										WHERE `community_id` =" . $db->qstr($inactive_community["community_id"]);
							if ($SET_INACTIVE_COMMUNITIES) {
								$db->Execute($query);
								if ($query) {
									application_log("cron", "Community ". $history_timestamp["community_id"]. " inactive for more than 12 months 7 days. Set to inactive.");
								} else {
									application_log("error", "Unable to deactivate community ". $history_timestamp["community_id"]);
								}
							}
							else {
								application_log("cron", "Action: Community ". $history_timestamp["community_id"]. " " . $history_timestamp["history_timestamp"]. " " ."inactive for more than 12 months 7 days. Set to inactive.");
							}
						}
					}
				}
			}
		}
	}
}
?>