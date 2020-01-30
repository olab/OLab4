<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to list all members of a community.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_MEMBERS"))) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

if (($LOGGED_IN) && (!$COMMUNITY_MEMBER)) {
	$NOTICE++;
	$NOTICESTR[] = "You are not currently a member of this community, <a href=\"".ENTRADA_URL."/communities?section=join&community=".$COMMUNITY_ID."&step=2\" style=\"font-weight: bold\">want to join?</a>";

	echo display_notice();
}

$query				= "SELECT * FROM `communities` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID);
$community_record	= $db->GetRow($query);
if ($community_record) {
	$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL, "title" => "Community Members List");

	/**
	 * Update requested sort column.
	 * Valid: date, title
	 */
	if (isset($_GET["sb"])) {
		if (in_array(trim($_GET["sb"]), array("fullname", "membership", "date"))) {
			$_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"] = trim($_GET["sb"]);
		}

		$_SERVER["QUERY_STRING"]	= replace_query(array("sb" => false));
	} else {
		if (!isset($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"])) {
			$_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"] = "fullname";
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
	 * Update requested number of rows per page.
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
			$_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"] = 15;
		}
	}

	/**
	 * Provide the queries with the columns to order by.
	 */
	switch($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"]) {
		case "fullname" :
			$SORT_BY	= "CONCAT_WS(', ', b.`lastname`, b.`firstname`) ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"]).", a.`member_joined` DESC";
		break;
		case "membership" :
			$SORT_BY	= "a.`member_acl` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"]).", CONCAT_WS(', ', b.`lastname`, b.`firstname`) ASC, a.`member_joined` DESC";
		break;
		case "date" :
		default :
			$SORT_BY	= "a.`member_joined` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"]);
		break;
	}

	/**
	 * Get the total number of results using the generated queries above and calculate the total number
	 * of pages that are available based on the results per page preferences.
	 */
	$query	= "
			SELECT COUNT(*) AS `total_rows`
			FROM `community_members` AS a
			JOIN `".AUTH_DATABASE."`.`user_data` AS b
			ON a.`proxy_id` = b.`id`
			WHERE a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
			AND a.`member_active` = '1'";
	$result	= $db->GetRow($query);
	if ($result) {
		$TOTAL_ROWS	= $result["total_rows"];

		if ($TOTAL_ROWS <= $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]) {
			$TOTAL_PAGES = 1;
		} elseif (($TOTAL_ROWS % $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]) == 0) {
			$TOTAL_PAGES = (int) ($TOTAL_ROWS / $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]);
		} else {
			$TOTAL_PAGES = (int) ($TOTAL_ROWS / $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]) + 1;
		}

		if ($TOTAL_PAGES > 1) {
			$pagination = new Entrada_Pagination($PAGE_CURRENT, $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"], $TOTAL_ROWS, COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL, replace_query());
		}
	} else {
		$TOTAL_ROWS		= 0;
		$TOTAL_PAGES	= 1;
	}
	
	if ($TOTAL_PAGES > 1) {
		$pagination = new Entrada_Pagination($PAGE_CURRENT, $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"], $TOTAL_ROWS, COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL, replace_query());
	}

	/**
	 * Check if pv variable is set and see if it's a valid page, other wise page 1 it is.
	 */
	if (isset($_GET["pv"])) {
		$PAGE_CURRENT = (int) trim($_GET["pv"]);

		if (($PAGE_CURRENT < 1) || ($PAGE_CURRENT > $TOTAL_PAGES)) {
			$PAGE_CURRENT = 1;
		}
	} else {
		$PAGE_CURRENT = 1;
	}

	$PAGE_PREVIOUS	= (($PAGE_CURRENT > 1) ? ($PAGE_CURRENT - 1) : false);
	$PAGE_NEXT		= (($PAGE_CURRENT < $TOTAL_PAGES) ? ($PAGE_CURRENT + 1) : false);

	/**
	 * Provides the first parameter of MySQLs LIMIT statement by calculating which row to start results from.
	 */
	$limit_parameter = (int) (($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"] * $PAGE_CURRENT) - $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]);
	?>
	<h1><?php echo html_encode($community_record["community_title"]); ?></h1>
	<div style="margin-bottom: 15px">
		<?php echo nl2br(html_encode($community_record["community_description"])); ?>
	</div>
	<div id="module-header">
		<?php
		if ($TOTAL_PAGES > 1) {
            echo $pagination->GetPageBar("normal", "right");
		}
		?>
	</div>
	<div style="padding-top: 10px; clear: both">
		<?php
		$query	= "	SELECT a.*, CONCAT_WS(', ', b.`lastname`, b.`firstname`) AS `fullname`, b.`username`, MAX(c.`timestamp`) as `timestamp`
					FROM `community_members` AS a
					LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
					ON a.`proxy_id` = b.`id`
					LEFT JOIN `users_online` AS c
					ON c.`proxy_id` = a.`proxy_id`
					LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS d
					ON d.`user_id` = b.`id`
					WHERE a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
					AND a.`member_active` = '1'
					AND d.`app_id` IN (".AUTH_APP_IDS_STRING.")
					AND d.`account_active` = 'true'
					AND (d.`access_starts` = '0' OR d.`access_starts` <= ".$db->qstr(time()).")
					AND (d.`access_expires` = '0' OR d.`access_expires` > ".$db->qstr(time()).")
					GROUP BY a.`proxy_id`
					ORDER BY %s
					LIMIT %s, %s";

		$query		= sprintf($query, $SORT_BY, $limit_parameter, $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]);
		$results	= $db->GetAll($query);
		if ($results) {
			$column	= 0;
			?>
			<table class="table table-striped table-bordered" summary="List of Users">
			<colgroup>
				<col class="status" />
				<col class="fullname" />
				<col class="membership" />
				<col class="date" />
			</colgroup>
			<thead>
				<tr>
					<td class="status" id="status">Status</td>
					<td class="fullname<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"] == "fullname") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"]) : ""); ?>" style="border-left: 0px;"><?php echo community_order_link("fullname", "Full Name"); ?></td>
					<td class="membership<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"] == "membership") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"]) : ""); ?>" style="border-left: 0px;"><?php echo community_order_link("membership", "Role"); ?></td>
					<td class="date<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"] == "date") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"]) : ""); ?>" style="border-left: 0px;" id="colDate"><?php echo community_order_link("date", "Date Joined"); ?></td>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach($results as $result) {
				$url = ENTRADA_URL."/people?profile=".$result["username"];
				echo "<tr>\n";
				echo "	<td class=\"status\"><img src=\"".(((int) $result["timestamp"]) ? (((int) $result["timestamp"] < (time() - 600)) ? "../images/list-status-away.gif" : "../images/list-status-online.gif") : "../images/list-status-offline.gif")."\"></td>\n";
				echo "	<td class=\"fullname\"><a href=\"".$url."\" title=\"Name: ".html_encode($result["fullname"])."\">".html_encode($result["fullname"])."</a></td>\n";
				echo "	<td class=\"membership\"><a href=\"".$url."\" title=\"Community Role\">".($result["member_acl"] ? "Administrator" : "Member")."</a></td>\n";
				echo "	<td class=\"date\"><a href=\"".$url."\" title=\"Date Joined\">".date(DEFAULT_DATETIME_FORMAT, $result["member_joined"])."</a></td>\n";
				echo "</tr>\n";
			}
			?>
			</tbody>
			</table>
			<?php
		} else {
			$NOTICE++;
			$NOTICESTR[] = "<strong>No members in this community.</strong><br /><br /> Please check back later.";

			echo display_notice();
		}
		?>
	</div>
	<?php
} else {
	application_log("error", "The provided community id was invalid [".$COMMUNITY_ID."] (Members List).");

	header("Location: ".COMMUNITY_URL.$COMMUNITY_URL);
	exit;
}

?>