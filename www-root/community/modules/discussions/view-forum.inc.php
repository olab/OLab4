<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Used to view all available posts within a particular discussion forum in
 * a community.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_DISCUSSIONS"))) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

if ($RECORD_ID) {
	$query = "SELECT * FROM `community_discussions` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `cdiscussion_id` = ".$db->qstr($RECORD_ID);
	$discussion_record = $db->GetRow($query);
	if ($discussion_record) {
            
        $isCommunityCourse = Models_Community_Course::is_community_course($COMMUNITY_ID);

        $create_allowed = discussions_module_access($RECORD_ID, "add-post");
        $read_allowed = discussions_module_access($RECORD_ID, "view-post");

        if ($read_allowed) {
			$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-forum&id=".$RECORD_ID, "title" => $discussion_record["forum_title"]);

			/**
			 * Update requested sort column.
			 * Valid: date, title
			 */
			if (isset($_GET["sb"])) {
				if (@in_array(trim($_GET["sb"]), array("date", "title", "poster", "replies"))) {
					$_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"] = trim($_GET["sb"]);
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
                    $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"] = 100;
				}
			}

			/**
			 * Provide the queries with the columns to order by.
			 */
			switch($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"]) {
				case "title" :
					$sort_by	= "a.`topic_title` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"]);
				break;
				case "replies" :
					$sort_by	= "COUNT(b.`cdtopic_id`) ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"]);
				break;
				case "poster" :
					$sort_by	= "CONCAT_WS(', ', c.`lastname`, c.`firstname`) ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"]);
				break;
				case "date" :
				default :
					$sort_by	= "`latest_activity` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"]);
				break;
			}

			/**
			 * Get the total number of results using the generated queries above and calculate the total number
			 * of pages that are available based on the results per page preferences.
			 */
			$query	= "SELECT  COUNT(*) AS `total_rows`
						FROM `community_discussion_topics`
						WHERE `cdiscussion_id` = ".$db->qstr($RECORD_ID)."
						AND `community_id` = ".$db->qstr($COMMUNITY_ID)."
						AND `topic_active` = '1'
						AND `cdtopic_parent` = '0'
						".((!$COMMUNITY_ADMIN) ? ($LOGGED_IN ? " AND ((`proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId()).") OR " : " AND (")."(`release_date` = '0' OR `release_date` <= ".$db->qstr(time()).") AND (`release_until` = '0' OR `release_until` > ".$db->qstr(time())."))" : "");
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
				$total_rows		= 0;
				$total_pages	= 1;
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
			?>
			<h1><?php echo html_encode($discussion_record["forum_title"]); ?></h1>

			<div id="module-header">
				<?php
                if ($total_pages > 1) {
                    echo $pagination->GetPageBar("normal", "right");
                }
				?>

				<div class="pull-left">
                    <a href="<?php echo COMMUNITY_URL."/feeds".$COMMUNITY_URL.":".$PAGE_URL."/rss:".$PRIVATE_HASH."?id=".$RECORD_ID; ?>" title="Subscribe to RSS"><i class="fa fa-rss-square fa-lg fa-fw"></i></a>
                    <?php
                    if (COMMUNITY_NOTIFICATIONS_ACTIVE && $LOGGED_IN && $_SESSION["details"]["notifications"]) {
                        ?>
                        <div id="notifications-toggle"></div>
                        <script type="text/javascript">
                            function promptNotifications(enabled) {
                                Dialog.confirm('Do you really wish to '+ (enabled == 1 ? "disable" : "enable") +' notifications for this forum?',
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
                                                    url:			'<?php echo ENTRADA_URL."/api/notifications.api.php?community_id=".$COMMUNITY_ID."&id=".$RECORD_ID; ?>&type=post&action=edit&active='+(enabled == 1 ? '0' : '1'),
                                                    onClose:			function () {
                                                        new Ajax.Updater('notifications-toggle', '<?php echo ENTRADA_URL."/api/notifications.api.php?community_id=".$COMMUNITY_ID."&id=".$RECORD_ID; ?>&type=post&action=view');
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
                        $ONLOAD[] = "new Ajax.Updater('notifications-toggle', '".ENTRADA_URL."/api/notifications.api.php?community_id=".$COMMUNITY_ID."&id=".$RECORD_ID."&type=post&action=view')";
                    }
                    ?>
				</div>

				<?php
                //Check if community is connected to a course
				if ($create_allowed) {
				?>
					<div style="float: right;">
						<ul class="page-action">
                            <li><a href="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add-post&id=<?php echo $RECORD_ID; ?>" class="btn btn-success">New Post</a></li>
                        </ul>
                    </div>
                    <div style="clear: both"></div>
                    <?php
                }
                ?>
            </div>

            <div class="forum-content">
                <?php
                $query = "SELECT a.*, COUNT(b.`cdtopic_id`) AS `total_replies`, IF(MAX(b.`updated_date`) IS NOT NULL, MAX(b.`updated_date`), a.`updated_date`) AS `latest_activity`,
							c.`firstname`, c.`lastname`, CONCAT_WS(' ', c.`firstname`, c.`lastname`) AS `original_poster_fullname`, c.`username` AS `original_poster_username`
							FROM `community_discussion_topics` AS a
							LEFT JOIN `community_discussion_topics` AS b
							ON a.`cdtopic_id` = b.`cdtopic_parent`
							AND b.`community_id` = ".$db->qstr($COMMUNITY_ID)."
							AND b.`topic_active` = '1'
							LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS c
							ON a.`proxy_id` = c.`id`
							WHERE a.`cdiscussion_id` = ".$db->qstr($RECORD_ID)."
							AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
							AND a.`topic_active` = '1'
							AND (b.`topic_active` IS NULL OR b.`topic_active` = '1')
							AND a.`cdtopic_parent` = '0'
							".((!$COMMUNITY_ADMIN) ? ($LOGGED_IN ? " AND ((a.`proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId()).") OR " : " AND (")."(a.`release_date` = '0' OR a.`release_date` <= ".$db->qstr(time()).") AND (a.`release_until` = '0' OR a.`release_until` > ".$db->qstr(time())."))" : "")."
							GROUP BY a.`cdtopic_id`
							ORDER BY %s, b.`updated_date` DESC
							LIMIT %s, %s";
                $query = sprintf($query, $sort_by, $limit_parameter, $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]);
                $results = $db->GetAll($query);
                if ($results) {
                    ?>
                    <table class="table table-striped table-bordered">
                    <colgroup>
                        <col style="width: 45%" />
						<col style="width: 9%" />
						<col style="width: 22%" />
                        <col style="width: 24%" />
                    </colgroup>
                    <thead>
                        <tr>
                            <td<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"] == "title") ? " class=\"sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"])."\"" : ""); ?>><?php echo communities_order_link("title", "Topic Title"); ?></td>
                            <td<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"] == "replies") ? " class=\"sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"])."\"" : ""); ?> style="border-left: none; text-align: left"><?php echo communities_order_link("replies", "Replies"); ?></td>
                            <td<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"] == "poster") ? " class=\"sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"])."\"" : ""); ?> style="border-left: none"><?php echo communities_order_link("poster", "Topic Starter"); ?></td>
                            <td<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"] == "date") ? " class=\"sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"])."\"" : ""); ?> style="border-left: none"><?php echo communities_order_link("date", "Latest Action"); ?></td>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach($results as $key => $result) {
                        $accessible	= true;

                        if ((($result["release_date"]) && ($result["release_date"] > time())) || (($result["release_until"]) && ($result["release_until"] < time()))) {
                            $accessible = false;
                        }

                        // Get the last poster user info
                        if ($result["total_replies"] > 0) {
                            $query = "SELECT cdt.`updated_date`, cdt.`updated_by`, cdt.`anonymous`, cdt.`proxy_id`, user_data.`firstname`, user_data.`lastname`, user_data.`username`
                                        FROM `community_discussion_topics` as cdt
                                        LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS user_data
                                        ON cdt.`proxy_id` = user_data.`id`
                                        WHERE cdt.`cdtopic_parent` = " . $db->qstr($result['cdtopic_id']) . "
                                        ORDER BY cdt.`updated_date` DESC
                                        LIMIT 1";
                            $last_poster = $db->GetRow($query);
                        } else {
							$last_poster = false;
						}

                        if (defined('COMMUNITY_DISCUSSIONS_ANON') && COMMUNITY_DISCUSSIONS_ANON && !$COMMUNITY_ADMIN && isset($result["anonymous"]) && $result["anonymous"]){
                            $original_display = "Anonymous";
                        } else {
                            $original_display = '<a href="'.ENTRADA_URL.'/people?profile='.html_encode($result["original_poster_username"]).'" style="font-size: 10px">'.html_encode($result["original_poster_fullname"]).'</a>';
                        }

                        if ($result['total_replies'] > 0) {
                            $latest_activity = trim($last_poster["updated_date"]);
                            if (!$last_poster['anonymous']) {
                                //last post
                                $latest_poster_display = '<a href="'.ENTRADA_URL.'/people?profile='.html_encode($last_poster['username']).'" style="font-size: 10px">'.html_encode($last_poster['firstname'] . ' ' . $last_poster['lastname']).'</a>';
                            } elseif (defined('COMMUNITY_DISCUSSIONS_ANON') && COMMUNITY_DISCUSSIONS_ANON && !$COMMUNITY_ADMIN ) {
                                //anoymous
                                $latest_poster_display = "Anonymous";
                            }
                        } else {
                            //orginal post
                            $latest_activity = trim($result["updated_date"]);
                            $latest_poster_display = $original_display;
                        }

                        echo "<tr".((!$accessible) ? " class=\"na\"" : "").">\n";
                        echo "	<td>\n";
                        echo "		<a id=\"topic-".(int) $result["cdtopic_id"]."-title\" href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-post&amp;id=".$result["cdtopic_id"]."\" style=\"font-weight: bold\">".limit_chars(html_encode($result["topic_title"]), 65, true)."</a>\n";
                        echo "	</td>\n";
                        echo "	<td>".(int) $result["total_replies"]."</td>\n";
                        echo "	<td style=\"font-size: 10px; white-space: nowrap; overflow: hidden\">".$original_display."</a></td>\n";
                        echo "	<td style=\"font-size: 10px; white-space: nowrap; overflow: hidden\">\n";
                        echo "		".date(DEFAULT_DATETIME_FORMAT, $latest_activity)."<br />\n";
                        echo "		<strong>By:</strong> ".$latest_poster_display."\n";
                        echo "	</td>\n";
                        echo "</tr>\n";

                    }
                    ?>
                    </tbody>
                    </table>
                    <?php
                } else {
                    $NOTICE++;
					$NOTICESTR[] = "<strong>No topics in this forum.</strong><br /><br />".(($create_allowed) ? "If you would like to create a new post, <a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=add-post&id=".$RECORD_ID."\">click here</a>." : "Please check back later.");

                    echo display_notice();
                }
                ?>
            </div>
        	<?php
        } else {
            $ERROR++;
            $ERRORSTR[] = "You do not have access to this discussion forum.<br /><br />If you believe there has been a mistake, please contact a community administrator for assistance.";

            if ($ERROR) {
                echo display_error();
            }
            if ($NOTICE) {
                echo display_notice();
            }
        }

        if ($LOGGED_IN) {
            add_statistic("community:".$COMMUNITY_ID.":discussions", "forum_view", "cdiscussion_id", $RECORD_ID);
        }

	} else {
		application_log("error", "The provided discussion forum id was invalid [".$RECORD_ID."] (View Forum).");

		header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
		exit;
	}
} else {
	application_log("error", "No discussion forum id was provided to view. (View Forum)");

	header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
	exit;
}
?>