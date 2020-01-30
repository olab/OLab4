<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * The default file that is loaded when /admin/communities is accessed.
 *
 * @author Organisation: Univeristy of Calgary
 * @author Unit: Faculty of Medicine
 * @author Developer: Howard Lu <yhlu@ucalgary.ca>
 * @copyright Copyright 2010 University of Calgary. All Rights Reserved.
 *
*/

if (!defined("IN_COMMUNITIES")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("communityadmin", "read", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] does not have access to this module [".$MODULE."]");
} else {
    Entrada_Utilities_Flashmessenger::displayMessages($MODULE);

	/**
	 * Update requested column to sort by.
	 * Valid: director, name
	 */

	$search_type		= "browse-newest";
	$browse_number		= 25;
	$results_per_page	= 25;
	$search_query		= "";
	$search_query_text	= "";
	$query_counter		= "";
	$query_search		= "";
	$show_results		= false;

	$admin_wording = "Administrator View";
	$admin_url = ENTRADA_URL."/admin/communities";

	$sidebar_html  = "<ul class=\"menu\">\n";
	$sidebar_html .= "	<li class=\"off\"><a href=\"".ENTRADA_URL."/communities"."\">Learner View</a></li>\n";
	if (($admin_wording) && ($admin_url)) {
		$sidebar_html .= "<li class=\"on\"><a href=\"".$admin_url."\">".html_encode($admin_wording)."</a></li>\n";
	}
	$sidebar_html .= "</ul>\n";

	new_sidebar_item("Display Style", $sidebar_html, "display-style", "open");
	/**
	 * Determine the type of search that is requested.
	 */
	if ((isset($_GET["type"])) && (in_array(trim($_GET["type"]), array("search", "browse-group", "browse-dept")))) {
		$search_type = clean_input($_GET["type"], "trim");
	}

	if (isset($_GET["sb"])) {
		if (@in_array(trim($_GET["sb"]), array("community_title", "community_opened", "category_title"))) {
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]	= trim($_GET["sb"]);
		}

		$_SERVER["QUERY_STRING"] = replace_query(array("sb" => false));
	} else {
		if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"])) {
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] = "name";
		}
	}

	/**
	 * Update requested order to sort by.
	 * Valid: asc, desc
	 */
	if (isset($_GET["so"])) {
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"] = ((strtolower($_GET["so"]) == "desc") ? "desc" : "asc");

		$_SERVER["QUERY_STRING"] = replace_query(array("so" => false));
	} else {
		if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"])) {
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"] = "asc";
		}
	}

	/**
	 * Update requsted number of rows per page.
	 * Valid: any integer really.
	 */
	if ((isset($_GET["pp"])) && ((int) trim($_GET["pp"]))) {
		$integer = (int) trim($_GET["pp"]);

		if (($integer > 0) && ($integer <= 250)) {
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"] = $integer;
		}

		$_SERVER["QUERY_STRING"] = replace_query(array("pp" => false));
	} else {
		if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"])) {
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"] = DEFAULT_ROWS_PER_PAGE;
		}
	}

	?>
	<h1><?php echo $translate->_("Manage Communities"); ?></h1>
	<?php
	/**
	 * Update requested order to sort by.
	 * Valid: asc, desc
	 */
	if(isset($_GET["so"])) {
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"] = ((strtolower($_GET["so"]) == "desc") ? "DESC" : "ASC");
	} else {
		if(!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"])) {
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"] = "ASC";
		}
	}

	$scheduler_communities = array(
				"duration_start" => 0,
				"duration_end" => 0,
				"total_rows" => 0,
				"total_pages" => 0,
				"page_current" => 0,
				"page_previous" => 0,
				"page_next" => 0,
				"communities" => array()
			);

	/**
	 * Provide the queries with the columns to order by.
	 */
	switch ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]) {
		case "community_title" :
			$sort_by = "a.`community_title` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["communities"]["so"]).", a.`community_title` ASC";
		break;
		case "community_opened" :
			$sort_by = "a.`community_opened` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["communities"]["so"]).", a.`community_opened` ASC";
		break;
		case "category_title" :
		default :
			$sort_by = "b.`category_title` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["communities"]["so"]).", b.`category_title` ASC";
		break;
	}
	
	/**** Query ***/
	$query_count = "SELECT COUNT(`community_id`) AS `total_rows`
				FROM `communities` AS a
				LEFT JOIN `communities_categories` AS b
				ON a.`category_id` = b.`category_id`
				WHERE `community_active` = '1'";

	$query_communities = "	SELECT a.`community_id`, a.`community_opened`, a.`community_title`, a.`community_shortname`, b.`category_title`
				FROM `communities` AS a
				LEFT JOIN `communities_categories` AS b
				ON a.`category_id` = b.`category_id`
				WHERE `community_active` = '1'";

	switch ($search_type) {
		case "browse-newest" :
			if ((isset($_GET["n"])) && ($number = clean_input($_GET["n"], array("trim", "int"))) && ($number > 0) && ($number <= 100)) {
				$browse_number = $number;
			}

			if (!$ERROR) {
				$search_query_text = "Newest ".(int) $browse_number." User".(($browse_number != 1) ? "s" : "");

				$query_counter	= "SELECT ".(int) $browse_number." AS `total_rows`";
				$query_search	= "	SELECT a.*, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, b.`account_active`, b.`access_starts`, b.`access_expires`, b.`last_login`, b.`role`, b.`group`
									FROM `".AUTH_DATABASE."`.`user_data` AS a
									LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
									ON b.`user_id` = a.`id`
									AND b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
									WHERE b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
									ORDER BY `id` DESC
									LIMIT 0, ".(int) $browse_number;
			}
		break;
		case "search" :
		default :
			if ((isset($_GET["q"])) && ($query = clean_input($_GET["q"], array("trim", "notags")))) {
				$search_query = $query;
				$search_query_text = html_encode($query);
			}

			$sql_ext = " and (a.`community_title` LIKE ".$db->qstr("%%".str_replace("%", "", $search_query)."%%")."
					OR b.`category_title` LIKE ".$db->qstr("%%".str_replace("%", "", $search_query)."%%").")";
			$query_count = $query_count.$sql_ext;
			$query_communities = $query_communities.$sql_ext;
		break;
	}


	$query_communities = $query_communities."ORDER BY %s LIMIT %s, %s";
	//Zend_Debug::dump($query_communities);


	/**
	 * Get the total number of results using the generated queries above and calculate the total number
	 * of pages that are available based on the results per page preferences.
	 */
	$result_count = $db->GetRow($query_count);

	if ($result_count) {
		$scheduler_communities["total_rows"] = (int) $result_count["total_rows"];

		if ($scheduler_communities["total_rows"] <= $_SESSION[APPLICATION_IDENTIFIER]["communities"]["pp"]) {
			$scheduler_communities["total_pages"] = 1;
		} elseif (($scheduler_communities["total_rows"] % $_SESSION[APPLICATION_IDENTIFIER]["communities"]["pp"]) == 0) {
			$scheduler_communities["total_pages"] = (int) ($scheduler_communities["total_rows"] / $_SESSION[APPLICATION_IDENTIFIER]["communities"]["pp"]);
		} else {
			$scheduler_communities["total_pages"] = (int) ($scheduler_communities["total_rows"] / $_SESSION[APPLICATION_IDENTIFIER]["communities"]["pp"]) + 1;
		}
	} else {
		$scheduler_communities["total_rows"] = 0;
		$scheduler_communities["total_pages"] = 1;
	}
	
	/**
	 * Check if pv variable is set and see if it's a valid page, other wise page 1 it is.
	 */
	if (isset($_GET["pv"])) {
		$scheduler_communities["page_current"] = (int) trim($_GET["pv"]);

		if (($scheduler_communities["page_current"] < 1) || ($scheduler_communities["page_current"] > $scheduler_communities["total_pages"])) {
			$scheduler_communities["page_current"] = 1;
		}
	} else {
		$scheduler_communities["page_current"] = 1;
	}

	$scheduler_communities["page_previous"] = (($scheduler_communities["page_current"] > 1) ? ($scheduler_communities["page_current"] - 1) : false);
	$scheduler_communities["page_next"] = (($scheduler_communities["page_current"] < $scheduler_communities["total_pages"]) ? ($scheduler_communities["page_current"] + 1) : false);

	/**
	 * Provides the first parameter of MySQLs LIMIT statement by calculating which row to start results from.
	 */
	$limit_parameter = (int) (($_SESSION[APPLICATION_IDENTIFIER]["communities"]["pp"] * $scheduler_communities["page_current"]) - $_SESSION[APPLICATION_IDENTIFIER]["communities"]["pp"]);

	/**
	 * Provide the previous query so we can have previous / next event links on the details page.
	 */
	$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["communities"]["previous_query"]["query"] = $query_communities;
	$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["communities"]["previous_query"]["total_rows"] = $scheduler_communities["total_rows"];

	$query_communities = sprintf($query_communities, $sort_by, $limit_parameter, $_SESSION[APPLICATION_IDENTIFIER]["communities"]["pp"]);
	$scheduler_communities["communities"] = $db->GetAll($query_communities);
	?>

	<h2><?php echo $translate->_("Community Search"); ?></h2>
	<div class="well">
		<form action="<?php echo ENTRADA_URL; ?>/admin/communities" method="get" class="form-horizontal">
			<input type="hidden" name="type" value="search" />
			<div class="control-group">
				<label for="q" class="form-required control-label"><?php echo $translate->_("Community Search"); ?>:</label>
				<div class="controls">
					<input type="text" id="q" name="q" value="<?php echo html_encode($search_query); ?>" />
					<input type="submit" class="btn btn-primary" value="Search" />
					<?php
					if ($search_query != "") {
					?>
						<input type="button" class="btn" value="Show All"  onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/communities'"/>
					<?php
					}
					?>
					<div class="content-small" style="margin-top: 10px">
                        <?php echo $translate->_("<strong>Note:</strong> You can search for community title, or Category title."); ?>
					</div>
				</div>
			</div>
		</form>
	</div>
	<?php
	if ($scheduler_communities["total_pages"] > 1) {
        $pagination = new Entrada_Pagination($scheduler_communities["page_current"], $_SESSION[APPLICATION_IDENTIFIER]["communities"]["pp"], $scheduler_communities["total_rows"], ENTRADA_URL."/admin/".$MODULE, replace_query());
        echo $pagination->GetPageBar();
        echo $pagination->GetResultsLabel();
	}

	if (count($scheduler_communities["communities"])) {
		if ($ENTRADA_ACL->amIAllowed("communityadmin", "delete", false)) : ?>
		<form action="<?php echo ENTRADA_URL; ?>/admin/communities?section=deactivate" method="post">
		<?php endif; ?>
		<table class="table" summary="List of communities">
			<colgroup>
				<col class="modified" />
				<col class="title" />
				<col class="title" />
				<col class="date" />
				<col class="attachment" />
				<col class="attachment" />
			</colgroup>
			<thead>
				<tr>
					<th class="modified">&nbsp;</th>
					<th class="title<?php echo (($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "community_title") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]) : ""); ?>"><?php echo admin_order_link("community_title", "Community Title"); ?></th>
					<th class="title<?php echo (($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "category_title") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]) : ""); ?>"><?php echo admin_order_link("category_title", "Category"); ?></th>
					<th class="date<?php echo (($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "community_opened") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]) : ""); ?>"><?php echo admin_order_link("community_opened", "Creation Date"); ?></th>
					<th class="attachment">&nbsp;</th>
					<th class="attachment">&nbsp;</th>
				</tr>
			</thead>
			<?php if ($ENTRADA_ACL->amIAllowed("communityadmin", "delete", false)) : ?>
			<tfoot>
				<tr>
					<td></td>
					<td colspan="5" style="padding-top: 10px">
						<input type="submit" class="btn btn-danger" value="Deactivate" />
					</td>
				</tr>
			</tfoot>
			<?php endif; ?>
			<tbody>
			<?php

			foreach ($scheduler_communities["communities"] as $result) {
				$url = ENTRADA_URL."/communities?section=modify&community=".$result["community_id"];

				echo "<tr id=\"community-".$result["community_id"]."\">\n";
				echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"checked[]\" value=\"".$result["community_id"]."\" /></td>\n";
				echo "	<td class=\"title\"><a href=\"".$url."\">".html_encode($result["community_title"])."</a></td>\n";
				echo "	<td class=\"title\"><a href=\"".$url."\">".html_encode($result["category_title"])."</a></td>\n";
				echo "	<td class=\"date\"><a href=\"".$url."\">".date(DEFAULT_DATETIME_FORMAT, $result["community_opened"])."</a></td>\n";
				echo "  <td class=\"attachment\">";

                echo "<div class=\"btn-group\">
                      <button class=\"btn btn-small dropdown-toggle\" data-toggle=\"dropdown\">
                        <i class=\"fa fa-cog\" aria-hidden=\"true\"></i>
                      </button>
                      <ul class=\"dropdown-menu toggle-left\">
                        <li><a href=\"".ENTRADA_URL."/communities?section=members&community=".$result["community_id"]."\" title=\"Manage Community Members\">Manage Community Members</a></li>
                        <li><a href=\"".ENTRADA_URL."/communities?section=modify&community=".$result["community_id"]."\" title=\"Manage Community\">Manage Community</a></li>";

                        $community_member = Models_Community_Member::fetchRowByProxyIDCommunityID($ENTRADA_USER->getID(), $result["community_id"]);
                        if ($community_member && $community_member->getMemberActive() && $community_member->getMemberACL()) {
                            echo "<li><a href=\"".ENTRADA_URL."/community/" . $result['community_shortname'] . ":pages\" title=\"Manage Pages\">Manage Pages</a></li>";
                        }

                echo "  </ul></div>";

				echo "  </td>";
                echo "</tr>\n";
			}
			?>
			</tbody>
		</table>
		<?php if ($ENTRADA_ACL->amIAllowed("communityadmin", "delete", false)) : ?>
		</form>
		<?php
		endif;
	} else {
		?>
		<div style="overflow:hidden;">
			<ul class="page-action" style="float: right;">
				<li><a href="<?php echo ENTRADA_URL; ?>/communities?section=create"><?php echo $translate->_("Add New Community"); ?></a></li>
			</ul>
		</div>
		<div class="display-notice">
			<h3><?php echo $translate->_("No Available communities"); ?></h3>
            <?php echo $translate->_("There are currently no available communities in the system. To begin click the <strong>Add New Community</strong> link above."); ?>
		</div>
		<?php
	}

	echo "<form action=\"\" method=\"get\">\n";
	echo "<input type=\"hidden\" id=\"dstamp\" name=\"dstamp\" value=\"".html_encode($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"])."\" />\n";
	echo "</form>\n";



	$ONLOAD[] = "initList()";
}