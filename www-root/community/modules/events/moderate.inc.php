<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to moderate events from a particular community.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_EVENTS"))) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

if ($RECORD_ID) {

	/**
	 * Check if pv variable is set and see if it's a valid page, other wise page 1 it is.
	 */
	if (isset($_GET["pv"])) {
		$PAGE_CURRENT = (int) trim($_GET["pv"]);
	} else {
		$PAGE_CURRENT = 0;
	}

	$query					= "	SELECT * FROM `community_events`
								WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." 
								AND `cpage_id` = ".$db->qstr($PAGE_ID)."
								AND `pending_moderation` = '1'
								AND `event_active` = '1'
								AND `cevent_id` = ".$db->qstr($RECORD_ID);
	$event_record	= $db->GetRow($query);
	if ($event_record) {
		$query				= "	UPDATE `community_events`
								SET `pending_moderation` = 0
								WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)."
								AND `cpage_id` = ".$db->qstr($PAGE_ID)."
								AND `cevent_id` = ".$db->qstr($RECORD_ID)." LIMIT 1";
		if (!$db->Execute($query)) {
			application_log("error", "Failed to release [".$RECORD_ID."] event in community. Database said: ".$db->ErrorMsg());
		}
	} else {
		application_log("error", "The provided event record [".$RECORD_ID."] was invalid.");
	}
	header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL.(($PAGE_CURRENT) ? "?section=moderate&pv=".$PAGE_CURRENT : "?section=moderate"));
	exit;
} else {

	echo "<h1>Moderating events</h1>";

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
			$sort_by = "a.`event_title` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"]).", a.`release_date` ASC";
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
	$query	= "
			SELECT COUNT(*) AS `total_rows`
			FROM `community_events`
			WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)."
			AND `event_active` = '1'
			AND `pending_moderation` = '1'
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
	<script type="text/javascript">
		function eventRelease(id) {
			Dialog.confirm('Do you really wish to release \''+ $('event-' + id + '-title').innerHTML +'\' on this page?',
				{
					id:				'requestDialog',
					width:			350,
					height:			100,
					title:			'Release Confirmation',
					className:		'medtech',
					okLabel:		'Yes',
					cancelLabel:	'No',
					closable:		'true',
					buttonClass:	'btn',
					ok:				function(win) {
										window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?<?php echo (($page_current > 1) ? "pv=".$page_current."&" : ""); ?>action=moderate&id='+id;
										return true;
									}
				}
			);
		}


<?php
/**
 * Add the javascript for deleting.
 */
if (communities_module_access($COMMUNITY_ID, $MODULE_ID, "delete")) {
	?>
		function eventDelete(id) {
			Dialog.confirm('Do you really wish to delete '+ $('event-' + id + '-title').innerHTML +' from this community?',
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
	<?php
}
?>
	</script>
    <div id="module-header">
        <?php
        if ($total_pages > 1) {
            echo $pagination->GetPageBar("normal", "right");
        }
        ?>
    </div>
	<?php
		$query		= "	SELECT a.*, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`username`
						FROM `community_events` AS a
						LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
						ON a.`proxy_id` = b.`id`
						WHERE a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
						AND a.`event_active` = '1'
						AND a.`pending_moderation` = '1'
						AND a.`cpage_id` = ".$db->qstr($PAGE_ID)."
						ORDER BY %s
						LIMIT %s, %s";
		$query		= sprintf($query, $sort_by, $limit_parameter, $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]);
		$results	= $db->GetAll($query);
		if ($results) {
			$last_date 		= 0;
			$total_events	= @count($results);

			echo "<table class=\"calendar\" style=\"width: 99%\">\n";
			echo "<colgroup>\n";
			echo "	<col style=\"width: 30%\" />\n";
			echo "	<col style=\"width: 70%\" />\n";
			echo "</colgroup>\n";
			echo "<tbody>\n";
			foreach ($results as $key => $result) {
				if (($last_date < strtotime("00:00:00", $result["event_start"])) || ($last_date > strtotime("23:59:59", $result["event_start"]))) {
					$last_date = $result["event_start"];
					echo "<tr>\n";
					echo "	<td colspan=\"2\" style=\"border: none\"><h3 style=\"border: none\">".date("l F dS Y", $result["event_start"])."</h3></td>\n";
					echo "</tr>\n";
				}
				echo "<tr>\n";
				echo "	<td style=\"font-family: monospace\">\n";
					if (strtotime("00:00:00", $result["event_start"]) != strtotime("00:00:00", $result["event_finish"])) {
						echo date(DEFAULT_DATE_FORMAT, $result["event_start"])."<br />";
						echo date(DEFAULT_DATE_FORMAT, $result["event_finish"]);
					} else {
						echo date("H:i", $result["event_start"])." - ".date("H:i", $result["event_finish"]);
					}
					if (isset($result["event_location"]) && trim($result["event_location"]) != "") {
						echo "\n<br /><br />Location: ".$result["event_location"];
					}
				$RECORD_AUTHOR = $result["proxy_id"];
				echo "	</td>\n";
				echo "	<td style=\"padding-bottom: 15px\">\n";
				echo "		<a href=\"".COMMUNITY_RELATIVE.$COMMUNITY_URL.":".$PAGE_URL."?id=".$result["cevent_id"]."\" id=\"event-".$result["cevent_id"]."-title\">".html_encode($result["event_title"])."</a>\n";
				echo 	" (<a class=\"action\" href=\"javascript:eventRelease('".$result["cevent_id"]."')\">release</a>)";
				echo 	((communities_module_access($COMMUNITY_ID, $MODULE_ID, "edit")) ? " (<a class=\"action\" href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=edit&amp;id=".$result["cevent_id"]."\">edit</a>)" : "");
				echo 	((communities_module_access($COMMUNITY_ID, $MODULE_ID, "delete")) ? " (<a class=\"action\" href=\"javascript:eventDelete('".$result["cevent_id"]."')\">delete</a>)" : "");
				echo "		<div class=\"content-small\">".limit_chars(strip_tags(str_replace("<br />", " ", $result["event_description"])), 150)."</div>";
				echo "	</td>\n";
				echo "</tr>\n";
			}
			echo "</tbody>\n";
			echo "</table>\n";
		} else {
			$NOTICE++;
			$NOTICESTR[] = "<strong>No Events Requiring Moderation</strong><br />There have been no events posted by the users to this page which require moderation, please check again later.";

			echo display_notice();
		}

}
?>