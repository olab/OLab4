<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * This is the index file of each community when there has been no module requested.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_DEFAULT"))) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

$query	= "	SELECT *
			FROM `community_pages`
			WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)."
			AND `page_url` = ".$db->qstr(((isset($PAGE_URL) && ($PAGE_URL)) ? $PAGE_URL : ""))."
			AND `page_active` = '1'";
$result	= $db->GetRow($query);
if ($result) {

	Entrada_Utilities_Flashmessenger::displayMessages($MODULE);

	echo "<a id=\"community-edit-button\" href=\"". COMMUNITY_URL.$COMMUNITY_URL .":pages?action=edit&amp;page=". ($result["page_url"] != "" ? $result["cpage_id"] : "home") ."\" class=\"btn btn-primary pull-right\">Edit Page</a>";

	if (isset($result["page_title"]) && trim($result["page_title"]) != "") {
		echo "<h1>".html_encode($result["page_title"])."</h1>\n";
	}
	
	if ($ERROR) {
		echo display_error();
	}
	
	if ($result["page_type"] == "url") {
		echo display_success();
	} else {
        if ($result["page_content"]) {
            echo "<div class=\"community-page-content\">";
            echo 	$result["page_content"];
            echo "</div>";
        }
	}

    $history_messages = "";

	if ($result["page_url"] == "") {
		
		/**
		 * Add the RSS feed version of the page to the <head></head> tags.
		 */
		$HEAD[] = "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"%TITLE% RSS 2.0\" href=\"".COMMUNITY_URL."/feeds".$COMMUNITY_URL."/rss20\" />";
		$HEAD[] = "<link rel=\"alternate\" type=\"text/xml\" title=\"%TITLE% RSS 0.91\" href=\"".COMMUNITY_URL."/feeds".$COMMUNITY_URL."/rss\" />";

		
		$community_announcements	= "";
		$community_events			= "";
			
		/**
		 * If the events module is enabled, display the event details on this Dashboard.
		 */
		$query			= "	SELECT a.*, b.* FROM `community_modules` as a
							LEFT JOIN `community_page_options` as b
							ON a.`community_id` = b.`community_id`
							WHERE a.`community_id` = ".$db->qstr($COMMUNITY_ID)." 
							AND a.`module_id` = '1' 
							AND a.`module_active` = '1'
							AND b.`option_title` = 'show_events'
							AND b.`option_value` = '1'";
		$events_enabled	= $db->GetRow($query);
		if ($events_enabled) {
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
						AND c.`page_active` = '1'
						AND c.`cpage_id` IN ('".implode("', '", $COMMUNITY_PAGES["available_ids"])."')
						AND a.`event_active` = '1'
						AND (a.`release_date` = '0' OR a.`release_date` <= '".time()."')
						AND (a.`release_until` = '0' OR a.`release_until` > '".time()."')
						AND (a.`event_finish` >= '".time()."')
						AND (a.`event_start` <= '".strtotime("+1 month")."')
						ORDER BY a.`event_start` DESC
						LIMIT 0, 10";
			$events	= $db->GetAll($query);
		}
		/**
		 * If the announcement module is enabled, display the announcements details on this Dashboard.
		 */
		$query					= "	SELECT a.* FROM `community_modules` as a
									LEFT JOIN `community_page_options` as b
									ON a.`community_id` = b.`community_id`
									WHERE a.`community_id` = ".$db->qstr($COMMUNITY_ID)." 
									AND a.`module_id` = '1' 
									AND a.`module_active` = '1'
									AND b.`option_title` = 'show_announcements'
									AND b.`option_value` = '1'";
		$announcements_enabled	= $db->GetRow($query);
		if ($announcements_enabled) {
			/**
			 * Fetch all community announcements and put the HTML output in a variable.
			 */
			$query			= "	SELECT a.*, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`username`, c.`page_url`
								FROM `community_announcements` AS a
								LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
								ON a.`proxy_id` = b.`id`
								LEFT JOIN `community_pages` AS c
								ON c.`cpage_id` = a.`cpage_id`
								WHERE a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
								AND c.`page_active` = '1'
								AND c.`cpage_id` IN ('".implode("', '", $COMMUNITY_PAGES["available_ids"])."')
								AND a.`announcement_active` = '1'
								AND (a.`release_date` = '0' OR a.`release_date` <= '".time()."')
								AND (a.`release_until` = '0' OR a.`release_until` > '".time()."')
								ORDER BY a.`release_date` DESC
								LIMIT 0, 10";
			$announcements	= $db->GetAll($query);
			if ($announcements) {
				$community_announcements .= "<h1 class=\"announcement-title\">Latest Announcements</h1>\n";
				$last_date = 0;
				foreach ($announcements as $key => $announcement) {
					if (($last_date < strtotime("00:00:00", $announcement["release_date"])) || ($last_date > strtotime("23:59:59", $announcement["release_date"]))) {
						if ($last_date > 0) {
							$community_announcements .= "</ul>\n";
						}
			
						$last_date = $announcement["release_date"];
						$community_announcements .= "<h3 class=\"announcement-date\">".date("l F dS Y", $announcement["release_date"])."</h3>\n";
						$community_announcements .= "<ul class=\"announcements\">\n";
					}
					$community_announcements .= "<li".(!($key % 2) ? " class=\"odd-announcement\"" : "")."><a href=\"".COMMUNITY_RELATIVE.$COMMUNITY_URL.":".$announcement["page_url"]."?id=".$announcement["cannouncement_id"]."\">".html_encode(limit_chars($announcement["announcement_title"], (isset($events) && $events ? 46: 108)))."</a></li>\n";
				}
				$community_announcements .= "</ul>\n";
			}
			

		}
		
		if ($events_enabled) {
			if ($events) {
				$community_events .= "<h1 class=\"announcement-title\">Upcoming Events</h1>\n";
				$last_date = 0;
				
				foreach ($events as $key => $event) {
					if (($last_date < strtotime("00:00:00", $event["event_start"])) || ($last_date > strtotime("23:59:59", $event["event_start"]))) {
						if ($last_date > 0) {
							$community_events .= "</ul>\n";
						}
			
						$last_date = $event["event_start"];
						$community_events .= "<h3 class=\"announcement-date\">".date("l F dS Y", $event["event_start"])."</h3>\n";
						$community_events .= "<ul class=\"announcements\">\n";
					}
					$community_events .= "<li".(!($key % 2) ? " class=\"odd-announcement\"" : "")."><a href=\"".COMMUNITY_RELATIVE.$COMMUNITY_URL.":".$event["page_url"]."?id=".$event["cevent_id"]."\">".html_encode($event["event_title"])."</a></li>\n";
				}

				$community_events .= "</ul>\n";
			}
		}
		
		/**
		 * Determine what and how to display the announcements information.
		 */
		if ($community_announcements) {
			$community_announcements_width = ($community_events ? "49" : "98");
			echo "<div style=\"width: ". $community_announcements_width ."%; float: left; padding-right: 5px\">\n";
			echo $community_announcements;
			echo "</div>\n";
		}
		
		/**
		 * Determine what and how to display the events information.
		 */
		if ($community_events) {
			$community_events_width = ($community_announcements ? "49" : "98");
			echo "<div style=\"width: ". $community_events_width ."%; float: left\">\n";
			echo $community_events;
			echo "</div>\n";
		}
		
		/**
		 * If the events module is enabled, display the event details on this Dashboard.
		 */
		$query			= "	SELECT * FROM `community_page_options`
							WHERE `option_title` = 'show_history'
							AND `community_id` = ".$db->qstr($COMMUNITY_ID)."
							AND `option_value` = '1'";
		$history_enabled	= $db->GetRow($query);
		if ($history_enabled) {
			/**
			 * Fetch all community events and put the HTML output in a variable.
			 */
			$query		= "	SELECT *
							FROM `community_history`
							WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)."
							AND `history_display` = '1'
							ORDER BY `history_timestamp` DESC
							LIMIT 0, 15";
			$results	= $db->CacheGetAll(CACHE_TIMEOUT, $query);
			if($results) {
				$history_messages = "<ul class=\"history-list\">";
				foreach($results as $key => $result) {
					if ((int)$result["cpage_id"] && ($result["history_key"] != "community_history_activate_module")) {
						$query = "SELECT `page_url` FROM `community_pages` WHERE `cpage_id` = ".$db->qstr($result["cpage_id"])." AND `community_id` = ".$db->qstr($result["community_id"]);
						$page_url = $db->GetOne($query);
					} elseif ($result["history_key"] == "community_history_activate_module") {
						$query = "SELECT a.`page_url` FROM `community_pages` as a JOIN `communities_modules` as b ON b.`module_shortname` = a.`page_type` WHERE b.`module_id` = ".$db->qstr($result["record_id"])." AND a.`community_id` = ".$db->qstr($result["community_id"])." AND a.`page_active` = '1'";
						$page_url = $db->GetOne($query);
					}
					
					if ($result["history_key"]) {
						$history_message = $translate->_($result["history_key"]);
						$record_title = "";
						$parent_id = (int)$result["record_parent"];
						community_history_record_title($result["history_key"], $result["record_id"], $result["cpage_id"], $result["community_id"], $result["proxy_id"]);

					} else {
						$history_message = $result["history_message"];
					}
					
					$content_search = array("%SITE_COMMUNITY_URL%", "%SYS_PROFILE_URL%", "%PAGE_URL%", "%RECORD_ID%", "%RECORD_TITLE%", "%PARENT_ID%", "%PROXY_ID%");
					$content_replace = array(COMMUNITY_URL.$COMMUNITY_URL, ENTRADA_URL."/people", $page_url, $result["record_id"], $record_title, $parent_id, $result["proxy_id"]);
					$history_message = str_replace($content_search, $content_replace, $history_message);
					$history_messages .= "<li>".strip_tags($history_message, "<a>")."</li>";
				}
				$history_messages .= "</ul>";
			}
			if ($history_messages) {
				?>
				<div style="clear:both"></div>
				<section>
					<h2 class="history">Community History</h2>
					<?php echo $history_messages; ?>
				</section>
				<?php
			}
		}
		/**
		 * @todo: 	This page needs to be designed to be visually appealing and clear to administrators who create new
		 * 		  	communities and need to know how to take the next step in terms of attaching content to the home-page,
		 * 		  	in addition to having a page designed generically to fit on all communities which users will see until
		 * 		  	administrators create their own content to display.
		 */
		if ((!$community_announcements) && (!$community_events) && (!$history_messages) && ((isset($result["page_content"]) && trim($result["page_content"]) == "") || (!isset($result["page_content"])))) {
			if ($COMMUNITY_ADMIN) {
				echo "	<div class=\"tutorial\">
							<p class=\"lead\">Welcome to your new community! This is where users will first view your community when
							joining or browsing from ".APPLICATION_NAME.". This message is displayed because no content
							is currently being displayed on this page - this can be remedied by in a few different ways:</p>
							<ul>
								<li>Creating announcements or events within the appropriate pages which will display on this page.<br /><br /></li>
								<li>Browsing to the 'Manage Pages' link found to the upper-right of the page, and creating a blurb which all users will see they first come to the community.<br /><br /></li>
								<li>Browsing to the 'Manage Pages' link and changing the type of page which this will be and creating appropriate content based on that. ie. Discussion Forums or Photo Galleries for users to view.</li>
							</ul>
						</div>";
			} else {
				echo "<p class=\"lead\">";
				echo "	Welcome to our new <strong>".APPLICATION_NAME." Community</strong>. We appear to be just getting things setup and underway now, but we hope to be in full operation shortly.";
				echo "</p>";
			}
		}
	}
} else {
	$query	= "	SELECT *
				FROM `community_pages`
				WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)."
				AND `page_type` = ".$db->qstr($PAGE_URL)."
				AND `page_active` = '1'";
	$result	= $db->GetRow($query);
	if ($result) {
		header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$result["page_url"]."?".replace_query());
		exit;
	} else {
		Entrada_Utilities_Flashmessenger::addMessage($translate->_("The page you have requested does not currently exist within this community."), "error", $MODULE);
		application_log("error", "Community default content page not found [".$PAGE_URL."] in community_id [".$COMMUNITY_ID."].");

		$url = COMMUNITY_URL . $COMMUNITY_URL;
		header("Location: " . $url);
		exit;
	}
}
