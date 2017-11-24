<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to display the main / default page of a particular community.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_DASHBOARD"))) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

$query	= "
		SELECT *
		FROM `community_pages`
		WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)."
		AND `page_type` = 'home'";

$content	= $db->GetRow($query);

if ($content) {
	if (isset($content["page_title"]) && trim($content["page_title"]) != "") {
		echo "<h1>".html_encode($content["page_title"])."</h1>\n";
	}
	
	if (isset($content["page_content"]) && trim($content["page_content"]) != "") {
		echo "<p>".$content["page_content"]."</p>";
	}
}

/**
 * If the announcement module is enabled, display the announcements details on this Dashboard.
 */
$query	= "	SELECT a.* FROM `community_modules` as a
			LEFT JOIN `community_page_options` as b
			ON a.`community_id` = b.`community_id`
			WHERE a.`community_id` = ".$db->qstr($COMMUNITY_ID)." 
			AND a.`module_id` = '1' 
			AND a.`module_active` = '1'
			AND b.`option_title` = 'show_announcements'
			AND b.`option_value` = '1'";
$result	= $db->GetRow($query);
if ($result) {
	
	$community_announcements	= "";

	/**
	 * Fetch all community announcements and put the HTML output in a variable.
	 */
	$query		= "
				SELECT a.*, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`username`, c.`page_url`
				FROM `community_announcements` AS a
				LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
				ON a.`proxy_id` = b.`id`
				LEFT JOIN `community_pages` AS c
				ON c.`cpage_id` = a.`cpage_id`
				WHERE a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
				AND c.`cpage_id` IN ('".implode("', '", $COMMUNITY_PAGES["available_ids"])."')
				AND a.`announcement_active` = '1'
				AND (a.`release_date` = '0' OR a.`release_date` <= '".time()."')
				AND (a.`release_until` = '0' OR a.`release_until` > '".time()."')
				ORDER BY a.`release_date` DESC
				LIMIT 0, 10";
	$results	= $db->GetAll($query);
	if ($results) {
		$community_announcements .= "<h1 style=\"font-size: 16px;\">Latest Announcements</h1>\n";
		$last_date = 0;
		foreach($results as $key => $result) {
			if (($last_date < strtotime("00:00:00", $result["release_date"])) || ($last_date > strtotime("23:59:59", $result["release_date"]))) {
				if ($last_date > 0) {
					$community_announcements .= "</ul>\n";
				}
	
				$last_date = $result["release_date"];
				$community_announcements .= "<h3 class=\"announcement-date\">".date("l F dS Y", $result["release_date"])."</h3>\n";
				$community_announcements .= "<ul class=\"announcements\">\n";
			}
			$community_announcements .= "<li".(!($key % 2) ? " class=\"odd-announcement\"" : "")."><a href=\"".COMMUNITY_RELATIVE.$COMMUNITY_URL.":".$result["page_url"]."?id=".$result["cannouncement_id"]."\">".html_encode($result["announcement_title"])."</a></li>\n";
		}
		$community_announcements .= "</ul>\n";
	}
	
	
	/**
	 * Determine what and how to display the announcements information.
	 */
	if ($community_announcements) {
		echo "<table style=\"width: 49%; table-layout: fixed; float: left;\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">\n";
		echo "<colgroup>\n";
		echo 	"<col style=\"width: 100%\" />\n";
		echo "</colgroup>\n";
		echo "<tbody>\n";
		echo "	<tr>\n";
		if ($community_announcements) {
			echo "	<td style=\"width: 100%; vertical-align: top; padding-right: 5px\">\n";
			echo 		$community_announcements;
			echo "	</td>\n";
		}
		echo "	</tr>\n";
		echo "</tbody>\n";
		echo "</table>\n";
	}
}

/**
 * If the events module is enabled, display the event details on this Dashboard.
 */
$query	= "	SELECT a.*, b.* FROM `community_modules` as a
			LEFT JOIN `community_page_options` as b
			ON a.`community_id` = b.`community_id`
			WHERE a.`community_id` = ".$db->qstr($COMMUNITY_ID)." 
			AND a.`module_id` = '1' 
			AND a.`module_active` = '1'
			AND b.`option_title` = 'show_events'
			AND b.`option_value` = '1'";

$result	= $db->GetRow($query);
if ($result) {
	$community_events	= "";

	/**
	 * Fetch all community events and put the HTML output in a variable.
	 */
	$query		= "
				SELECT a.*, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`username`, c.`page_url`
				FROM `community_events` AS a
				LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
				ON a.`proxy_id` = b.`id`
				LEFT JOIN `community_pages` AS c
				ON c.`cpage_id` = a.`cpage_id`
				WHERE a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
				AND c.`cpage_id` IN ('".implode("', '", $COMMUNITY_PAGES["available_ids"])."')
				AND a.`event_active` = '1'
				AND (a.`release_date` = '0' OR a.`release_date` <= '".time()."')
				AND (a.`release_until` = '0' OR a.`release_until` > '".time()."')
				AND (a.`event_finish` >= '".time()."')
				AND (a.`event_start` <= '".strtotime("+1 month")."')
				ORDER BY a.`event_start` DESC
				LIMIT 0, 10";
	$results	= $db->GetAll($query);
	if ($results) {
		$community_events .= "<h1>Upcoming Events</h1>\n";
		$last_date = 0;
		foreach($results as $key => $result) {
			if (($last_date < strtotime("00:00:00", $result["event_start"])) || ($last_date > strtotime("23:59:59", $result["event_start"]))) {
				if ($last_date > 0) {
					$community_events .= "</ul>\n";
				}
	
				$last_date = $result["event_start"];
				$community_events .= "<h3 class=\"announcement-date\">".date("l F dS Y", $result["event_start"])."</h3>\n";
				$community_events .= "<ul class=\"announcements\">\n";
			}
			$community_events .= "<li".(!($key % 2) ? " class=\"odd-announcement\"" : "")."><a href=\"".COMMUNITY_RELATIVE.$COMMUNITY_URL.":".$result["page_url"]."?id=".$result["cevent_id"]."\">".html_encode($result["event_title"])."</a></li>\n";
		}
		$community_events .= "</ul>\n";
	}
	
	
	/**
	 * Determine what and how to display the events information.
	 */
	if ($community_events) {
		echo "<table style=\"width: ".($community_announcements ? "49" : "98")."%; table-layout: fixed; float: right;\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">\n";
		echo "<colgroup>\n";
		echo 	"<col style=\"width: 100%\" />\n";
		echo "</colgroup>\n";
		echo "<tbody>\n";
		echo "	<tr>\n";
		if ($community_events) {
			echo "	<td style=\"width: 100%; vertical-align: top; padding-right: 5px\">\n";
			echo 		$community_events;
			echo "	</td>\n";
		}
		echo "	</tr>\n";
		echo "</tbody>\n";
		echo "</table>\n";
	}
}

if (!$community_announcements && !$community_events && ((isset($content["page_content"]) && trim($content["page_content"]) == "") || !isset($content["page_content"]))) {
	if ((isset($content["page_title"]) && trim($content["page_title"]) == "") || !isset($content["page_title"])) {
		echo "<h1>Your new community!</h1>";
	}
	echo "	<p>	
					Welcome to your new community! This is where users will first view your community when 
					joining or browsing from ".APPLICATION_NAME.". This message is displayed because no content
					is currently being displayed on this page - this can be remedied by manually creating content
					by browsing to the 'Manage Pages' section in the top right or by creating an announcement or event
					within the appropriate page. We suggest that you create content for the page within the 'Manage
					Pages' section as well as creating a new announcement for members to view when they join.
			</p>";
}
?>