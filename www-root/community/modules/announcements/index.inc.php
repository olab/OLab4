<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to list announcements on are particular page in a particular community.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_ANNOUNCEMENTS"))) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

if (!$RECORD_ID) {
	/**
	 * Update requested sort column.
	 * Valid: date, title
	 */
	if (isset($_GET["sb"])) {
		if (@in_array(trim($_GET["sb"]), array("date", "title"))) {
			$_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"]	= trim($_GET["sb"]);
		}

		$_SERVER["QUERY_STRING"]	= replace_query(array("sb" => false));
	} else {
		if (!isset($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"])) {
			$_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"] = "date";
		}
	}

	/**
	 * Update requested order to sort by.
	 * Valid: asc, desc
	 */
	if (isset($_GET["so"])) {
		$_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"] = ((strtolower($_GET["so"]) == "desc") ? "desc" : "asc");

		$_SERVER["QUERY_STRING"]	= replace_query(array("so" => false));
	} else {
		if (!isset($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"])) {
			$_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"] = "desc";
		}
	}

	/**
	 * Update requsted number of rows per page.
	 * Valid: any integer really.
	 */
	if ((isset($_GET["pp"])) && ((int) trim($_GET["pp"]))) {
		$integer = (int) trim($_GET["pp"]);

		if (($integer > 0) && ($integer <= 250)) {
			$_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"] = $integer;
		}

		$_SERVER["QUERY_STRING"] = replace_query(array("pp" => false));
	} else {
		if (!isset($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"])) {
			$_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"] = 5;
		}
	}

	/**
	 * Provide the queries with the columns to order by.
	 */
	switch ($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"]) {
		case "title" :
			$sort_by = "a.`announcement_title` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"]).", a.`release_date` ASC";
		break;
		case "date" :
		default :
			$sort_by	= "a.`release_date` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"]);
		break;
	}
	
	/**
	 * Get the total number of results using the generated queries above and calculate the total number
	 * of pages that are available based on the results per page preferences.
	 */
	$query	= "SELECT COUNT(*) AS `total_rows`
                FROM `community_announcements`
                WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)."
                AND `announcement_active` = '1'
                ".( $PAGE_OPTIONS["moderate_posts"] == 1 ? "AND `pending_moderation` = '0'" : "")."
                ".((!$COMMUNITY_ADMIN) ? " AND (`release_date` = '0' OR `release_date` <= ".$db->qstr(time()).") AND (`release_until` = '0' OR `release_until` > ".$db->qstr(time()).")" : "")."
                AND `cpage_id` = ".$db->qstr($PAGE_ID);
	$result	= $db->GetRow($query);
	if ($result) {
		$total_rows	= $result["total_rows"];

		if ($total_rows <= $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]) {
			$total_pages = 1;
		} elseif (($total_rows % $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]) == 0) {
			$total_pages = (int) ($total_rows / $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]);
		} else {
			$total_pages = (int) ($total_rows / $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]) + 1;
		}
	} else {
		$total_rows = 0;
		$total_pages = 1;
	}

	/**
	 * Check if pv variable is set and see if it's a valid page, other wise page 1 it is.
	 */
	if (isset($_GET["pv"])) {
		$page_current = (int) trim($_GET["pv"]);

		if (($page_current < 1) || ($page_current > $total_pages)) {
			$page_current = 1;
		}
	} else {
		$page_current = 1;
	}

	if ($total_pages > 1) {
		$pagination = new Entrada_Pagination($page_current, $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"], $total_rows, COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL, replace_query());
	}

	/**
	 * Provides the first parameter of MySQLs LIMIT statement by calculating which row to start results from.
	 */
	$limit_parameter = (int) (($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"] * $page_current) - $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]);
}

/**
 * Add the javascript for deleting announcements.
 */
if (communities_module_access($COMMUNITY_ID, $MODULE_ID, "delete")) {
	?>
	<script>
		function announcementDelete(id) {
			Dialog.confirm('Do you really wish to delete '+ $('announcement-' + id + '-title').innerHTML +' from this community?',
				{
					id:				'requestDialog',
					width:			350,
					height:			100,
					title:			'Delete Confirmation',
					className:		'medtech',
					okLabel:		'Yes',
					cancelLabel:	'No',
					closable:		'true',
					buttonClass:	'btn',
					ok:				function(win) {
										window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?<?php echo (($page_current > 1) ? "pv=".$page_current."&" : ""); ?>action=delete&id='+id;
										return true;
									}
				}
			);
		}
	</script>
	<?php
}
?>

<div id="module-header">
	<?php
    if (isset($total_pages) && $total_pages > 1) {
        echo $pagination->GetPageBar("normal", "right");
    }
	?>

	<div class="pull-left">
		<a href="<?php echo COMMUNITY_URL."/feeds".$COMMUNITY_URL.":".$PAGE_URL."/rss:".$PRIVATE_HASH; ?>" title="Subscribe to RSS"><i class="fa fa-rss-square fa-lg fa-fw"></i></a>
		<?php if (COMMUNITY_NOTIFICATIONS_ACTIVE && $LOGGED_IN && $_SESSION["details"]["notifications"]) { ?>
			<div id="notifications-toggle"></div>
			<script type="text/javascript">
			function promptNotifications(enabled) {
				Dialog.confirm('Do you really wish to '+ (enabled == 1 ? "stop" : "begin") +' receiving notifications for new announcements in this community?',
					{
						id:				'requestDialog',
						width:			350,
						height:			100,
						title:			'Notification Confirmation',
						className:		'medtech',
						okLabel:		'Yes',
						cancelLabel:	'No',
						closable:		'true',
						buttonClass:	'btn',
						destroyOnClose:	true,
						ok:				function(win) {
											new Window(	{
															id:				'resultDialog',
															width:			350,
															height:			100,
															title:			'Notification Result',
															className:		'medtech',
															okLabel:		'close',
															buttonClass:	'btn',
															resizable:		false,
															draggable:		false,
															minimizable:	false,
															maximizable:	false,
															recenterAuto:	true,
															destroyOnClose:	true,
															url:			'<?php echo ENTRADA_URL."/api/notifications.api.php?community_id=".$COMMUNITY_ID."&id=".$COMMUNITY_ID; ?>&type=announcement&action=edit&active='+(enabled == 1 ? '0' : '1'),
															onClose:			function () {
																				new Ajax.Updater('notifications-toggle', '<?php echo ENTRADA_URL."/api/notifications.api.php?community_id=".$COMMUNITY_ID."&id=".$COMMUNITY_ID; ?>&type=announcement&action=view');
																			}
														}
											).showCenter();
											return true;
										}
					}
				);
			}

			</script>
			<?php
			$ONLOAD[] = "new Ajax.Updater('notifications-toggle', '".ENTRADA_URL."/api/notifications.api.php?community_id=".$COMMUNITY_ID."&id=".$COMMUNITY_ID."&type=announcement&action=view')";
		}
		?>
	</div>
	<div class="pull-right">
		<?php
		if (communities_module_access($COMMUNITY_ID, $MODULE_ID, "add")) {
			?>
            <ul class="page-action">
                <li><a href="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add" class="btn btn-success">Add Announcement</a></li>
            </ul>
			<?php
		}
		?>
	</div>
</div>

<div style="padding-top: 10px; clear: both;">
	<?php
	if ($RECORD_ID) {
		$query	= "	SELECT a.*, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`username`
					FROM `community_announcements` AS a
					LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
					ON a.`proxy_id` = b.`id`
					WHERE a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
					AND a.`cpage_id` = ".$db->qstr($PAGE_ID)."
					".( $PAGE_OPTIONS["moderate_posts"] == 1 ? "AND a.`pending_moderation` = '0'" : "")."
					AND a.`announcement_active` = '1'
					AND a.`cannouncement_id` = ".$db->qstr($RECORD_ID);
		$result	= $db->GetRow($query);
		if ($result) {
			$allow_to_load = true;

			if (!$COMMUNITY_ADMIN) {
				if ((!$release_date = (int) $result["release_date"]) || ($release_date <= time())) {
					if ((!$release_until = (int) $result["release_until"]) || ($release_until > time())) {
						/**
						 * You're good to go, no further checks at this time.
						 * If you need to add more checks, this is there they would go.
						 */
					} else {
						add_notice("This announcement was only accessible until <strong>".date(DEFAULT_DATE_FORMAT, $release_until)."</strong>.<br /><br />Please contact your community administrators for further assistance.");

						$allow_to_load	= false;
					}
				} else {
					add_notice("This announcement will not be accessible until <strong>".date(DEFAULT_DATE_FORMAT, $release_date)."</strong>.<br /><br />Please check back at this time, thank-you.");

					$allow_to_load	= false;
				}
			}

			if (!$allow_to_load) {
				echo display_notice();
			} else {
				$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?id=".$RECORD_ID, "title" => limit_chars($result["announcement_title"], 32));

				/**
				 * If there is time release properties, display them to the browsing users.
				 */
				if (($release_date = (int) $result["release_date"]) && ($release_date > time())) {
					add_notice("This announcement will not be accessible to others until <strong>".date(DEFAULT_DATE_FORMAT, $release_date)."</strong>.");
				} elseif ($release_until = (int) $result["release_until"]) {
					if ($release_until > time()) {
						add_notice("This announcement will be accessible until <strong>".date(DEFAULT_DATE_FORMAT, $release_until)."</strong>.");
					} else {
						/**
						 * Only administrators or people who wrote the post will get this.
						 */
						add_notice("This announcement was only accessible until <strong>".date(DEFAULT_DATE_FORMAT, $release_until)."</strong> by others.");
					}
				}

				if ($NOTICE) {
					echo display_notice();
				}

				$RECORD_AUTHOR = $result["proxy_id"];

				echo "<div id=\"announcement-".(int) $result["cannouncement_id"]."\" class=\"announcement\">\n";
				echo "    <a name=\"announcement-".(int) $result["cannouncement_id"]."\"></a>\n";
				echo "    <h2 id=\"announcement-".(int) $result["cannouncement_id"]."-title\">".html_encode($result["announcement_title"])."</h2>\n";
				echo "    <div>\n";
				echo "        <div class=\"tagline\">\n";
				echo "	          Released ".date("F dS, Y", $result["release_date"])." by <strong>".html_encode($result["fullname"])."</strong>";
				echo 	          ((communities_module_access($COMMUNITY_ID, $MODULE_ID, "edit")) ? " (<a class=\"action\" href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=edit&amp;id=".$result["cannouncement_id"]."\">edit</a>)" : "");
				echo 	          ((communities_module_access($COMMUNITY_ID, $MODULE_ID, "delete")) ? " (<a class=\"action\" href=\"javascript:announcementDelete('".$result["cannouncement_id"]."')\">delete</a>)" : "");
				echo "        </div>\n";
				echo          strip_tags($result["announcement_description"], $ALLOWED_HTML_TAGS);
				echo "    </div>\n";
				echo "</div>";
			}
		} else {
			add_error("The announcement that you are looking for does not exist on this page.");

			echo display_error();
		}
	} else {
		if ($COMMUNITY_ADMIN && ($PAGE_OPTIONS["moderate_posts"] == 1)) {
			$query		= "	SELECT COUNT(`cannouncement_id`)
							FROM `community_announcements`
							WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)."
							AND `announcement_active` = '1'
							AND `pending_moderation` = '1'
							AND `cpage_id` = ".$db->qstr($PAGE_ID);

			$pending_moderation = $db->GetOne($query);
			if ($pending_moderation) {
				add_notice((($pending_moderation > 1) ? ((int)$pending_moderation)." announcements are" : ((int)$pending_moderation)." announcement is")." pending moderation. Click <a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=moderate\">here</a> to begin moderating.");
				echo display_notice();

                $NOTICE--;
				array_pop($NOTICESTR);
			}
		}

		$query = "	SELECT a.*, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`username`
                    FROM `community_announcements` AS a
                    LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
                    ON a.`proxy_id` = b.`id`
                    WHERE a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
                    AND a.`announcement_active` = '1'
                    ".( $PAGE_OPTIONS["moderate_posts"] == 1 ? "AND a.`pending_moderation` = '0'" : "")."
                    ".((!$COMMUNITY_ADMIN) ? " AND (a.`release_date` = '0' OR a.`release_date` <= ".$db->qstr(time()).") AND (a.`release_until` = '0' OR a.`release_until` > ".$db->qstr(time()).")" : "")."
                    AND a.`cpage_id` = ".$db->qstr($PAGE_ID)."
                    ORDER BY %s
                    LIMIT %s, %s";
		$query = sprintf($query, $sort_by, $limit_parameter, $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]);
		$results = $db->GetAll($query);
		if ($results) {
			foreach ($results as $result) {
				$accessible = true;
				if ((($result["release_date"]) && ($result["release_date"] > time())) || (($result["release_until"]) && ($result["release_until"] < time()))) {
					$accessible = false;
				}

				$RECORD_AUTHOR = $result["proxy_id"];

				echo "<div id=\"announcement-".(int) $result["cannouncement_id"]."\" class=\"announcement".((!$accessible) ? " na" : "")."\">\n";
				echo "	<a name=\"announcement-".(int) $result["cannouncement_id"]."\"></a>\n";
				echo "	<a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?id=".$result["cannouncement_id"]."\" id=\"announcement-".(int) $result["cannouncement_id"]."-title\" class=\"title\">".html_encode($result["announcement_title"])."</a>\n";
				echo "	<div>\n";
				echo "		<div class=\"tagline\">\n";
				echo "			Released ".date("F dS, Y", $result["release_date"])." by <strong>".html_encode($result["fullname"])."</strong>";
				echo			((communities_module_access($COMMUNITY_ID, $MODULE_ID, "edit")) ? " (<a class=\"action\" href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=edit&amp;id=".$result["cannouncement_id"]."\">edit</a>)" : "");
				echo			((communities_module_access($COMMUNITY_ID, $MODULE_ID, "delete")) ? " (<a class=\"action\" href=\"javascript:announcementDelete('".$result["cannouncement_id"]."')\">delete</a>)" : "");
				echo "		</div>\n";
				echo		strip_tags($result["announcement_description"], $ALLOWED_HTML_TAGS);
				echo "	</div>\n";
				echo "</div>\n";
				echo "<hr />";

				if ($LOGGED_IN) {
					add_statistic("community:".$COMMUNITY_ID.":announcements", "view", "cannouncement_id", $result["cannouncement_id"]);
				}
			}
		} else {
			add_notice("<strong>No Announcements Available</strong><br />There have been no announcements posted by the administrators of this community, please check again later.");

			echo display_notice();
		}
	}
	?>
</div>