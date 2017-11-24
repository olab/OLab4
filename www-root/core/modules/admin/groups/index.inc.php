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
 * The default file that is loaded when /admin/groups is accessed.
 *
 * @author Organisation: Univeristy of Calgary
 * @author Unit: Faculty of Medicine
 * @author Developer: Doug Hall <yhlu@ucalgary.ca>
 * @copyright Copyright 2010 University of Calgary. All Rights Reserved.
 *
*/
if (!defined("IN_GROUPS")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("group", "update", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] does not have access to this module [".$MODULE."]");
} else {
	/**
	 * Update requested column to sort by.
	 * Valid: director, name
	 */

	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/tabpane/tabpane.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
	$HEAD[] = "<link href=\"".ENTRADA_URL."/css/tabpane.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />\n";

	$search_type		= "browse-newest";
	$browse_number		= 25;
	$results_per_page	= 25;
	$search_query		= "";
	$search_query_text	= "";
	$query_counter		= "";
	$query_search		= "";
	$show_results		= false;

	$admin_wording = "Administrator View";
	$admin_url = ENTRADA_URL."/admin/groups";

	/**
	 * Determine the type of search that is requested.
	 */
	if ((isset($_GET["type"])) && (in_array(trim($_GET["type"]), array("search", "browse-group", "browse-dept")))) {
		$search_type = clean_input($_GET["type"], "trim");
	}

	if (isset($_GET["sb"])) {
		if (@in_array(trim($_GET["sb"]), array("group_name", "members", "updated_date"))) {
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]	= trim($_GET["sb"]);
		}

		$_SERVER["QUERY_STRING"] = replace_query(array("sb" => false));
	} else {
		if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"])) {
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] = "group_name";
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

	/**
	 * Check if preferences need to be updated on the server at this point.
	 */
	preferences_update($MODULE, $PREFERENCES);

	?>
	<h1><?php echo $translate->_("Manage Cohorts"); ?></h1>

	<div class="row-fluid">
		<div class="pull-right">
			<a class="btn btn-success" href="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>?section=add"><i class="icon-plus-sign icon-white"></i> <?php echo $translate->_("Add New Cohort"); ?></a>
		</div>
	</div>

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

	$scheduler_groups = array(
				"duration_start" => 0,
				"duration_end" => 0,
				"total_rows" => 0,
				"total_pages" => 0,
				"page_current" => 0,
				"page_previous" => 0,
				"page_next" => 0,
				"groups" => array()
			);

	/**
	 * Provide the queries with the columns to order by.
	 */
	switch ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]) {
		case "group_name" :
			$sort_by = "a.`group_name` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["groups"]["so"]).", a.`group_name` ASC";
		break;
		case "members" :
			$sort_by = "`members` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["groups"]["so"]).", `members` ASC";
		break;
		case "updated_date" :
		default :
			$sort_by = "a.`updated_date` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["groups"]["so"]).", a.`updated_date` ASC";
		break;
	}
	
	$query_count = "	SELECT COUNT(a.`group_id`) AS `total_rows`
						FROM `groups` AS a
						JOIN `group_organisations` AS b
						ON b.`group_id` = a.`group_id`
						WHERE a.`group_active` = '1'
						AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation());

	$query_groups = "	SELECT a.*, COUNT(b.`gmember_id`) AS members, CASE WHEN (MIN(b.`member_active`) = 0) THEN 1 ELSE 0 END AS `inactive`
						FROM `groups` AS a
						LEFT JOIN `group_members` b
						ON b.`group_id` = a.`group_id`
						AND b.`member_active` = 1
						JOIN `group_organisations` AS c
						ON c.`group_id` = a.`group_id`
						WHERE a.`group_active` = '1'
						AND c.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation());
	
	switch ($search_type) {
		case "search" :
		default :
			if ((isset($_GET["q"])) && ($query = clean_input($_GET["q"], array("trim", "notags")))) {
				$search_query = $query;
				$search_query_text = html_encode($query);
			}

			$sql_ext = "	and (`group_name` LIKE ".$db->qstr("%%".str_replace("%", "", $search_query)."%%").")  ";
			$query_count = $query_count.$sql_ext;
			$query_groups = $query_groups.$sql_ext;
		break;
	}

	$query_groups = $query_groups." GROUP By a.`group_id` ORDER BY %s LIMIT %s, %s";

	/**
	 * Get the total number of results using the generated queries above and calculate the total number
	 * of pages that are available based on the results per page preferences.
	 */
	$result_count = $db->GetRow($query_count);

	if ($result_count) {
		$scheduler_groups["total_rows"] = (int) $result_count["total_rows"];

		if ($scheduler_groups["total_rows"] <= $_SESSION[APPLICATION_IDENTIFIER]["groups"]["pp"]) {
			$scheduler_groups["total_pages"] = 1;
		} elseif (($scheduler_groups["total_rows"] % $_SESSION[APPLICATION_IDENTIFIER]["groups"]["pp"]) == 0) {
			$scheduler_groups["total_pages"] = (int) ($scheduler_groups["total_rows"] / $_SESSION[APPLICATION_IDENTIFIER]["groups"]["pp"]);
		} else {
			$scheduler_groups["total_pages"] = (int) ($scheduler_groups["total_rows"] / $_SESSION[APPLICATION_IDENTIFIER]["groups"]["pp"]) + 1;
		}
	} else {
		$scheduler_groups["total_rows"] = 0;
		$scheduler_groups["total_pages"] = 1;
	}
	/**
	 * Check if pv variable is set and see if it's a valid page, other wise page 1 it is.
	 */
	if (isset($_GET["pv"])) {
		$scheduler_groups["page_current"] = (int) trim($_GET["pv"]);

		if (($scheduler_groups["page_current"] < 1) || ($scheduler_groups["page_current"] > $scheduler_groups["total_pages"])) {
			$scheduler_groups["page_current"] = 1;
		}
	} else {
		$scheduler_groups["page_current"] = 1;
	}

	$scheduler_groups["page_previous"] = (($scheduler_groups["page_current"] > 1) ? ($scheduler_groups["page_current"] - 1) : false);
	$scheduler_groups["page_next"] = (($scheduler_groups["page_current"] < $scheduler_groups["total_pages"]) ? ($scheduler_groups["page_current"] + 1) : false);

	/**
	 * Provides the first parameter of MySQLs LIMIT statement by calculating which row to start results from.
	 */
	$limit_parameter = (int) (($_SESSION[APPLICATION_IDENTIFIER]["groups"]["pp"] * $scheduler_groups["page_current"]) - $_SESSION[APPLICATION_IDENTIFIER]["groups"]["pp"]);

	/**
	 * Provide the previous query so we can have previous / next event links on the details page.
	 */
	$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["groups"]["previous_query"]["query"] = $query_groups;
	$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["groups"]["previous_query"]["total_rows"] = $scheduler_groups["total_rows"];

	$query_groups = sprintf($query_groups, $sort_by, $limit_parameter, $_SESSION[APPLICATION_IDENTIFIER]["groups"]["pp"]);
	$scheduler_groups["groups"] = $db->GetAll($query_groups);
//	Zend_Debug::dump($scheduler_groups);

	?>

    <form action="<?php echo ENTRADA_URL; ?>/admin/groups" method="get" class="form-inline">
        <input type="hidden" name="type" value="search" />
        <label for="q" class="form-required control-label">Cohort Search:</label>
        <input type="text" id="q" name="q" value="<?php echo html_encode($search_query); ?>" style="width: 350px" placeholder="<?php echo $translate->_("Search in Cohort Name"); ?>" />
        <input type="submit" class="btn btn-primary" value="Search" />
    </form>

	<?php
	echo "<p />";
	if ($scheduler_groups["total_pages"] > 1) {
        $pagination = new Entrada_Pagination($scheduler_groups["page_current"], $_SESSION[APPLICATION_IDENTIFIER]["groups"]["pp"], $scheduler_groups["total_rows"], ENTRADA_URL."/admin/".$MODULE, replace_query());
        echo $pagination->GetPageBar();
	}

	if ($scheduler_groups["groups"] && count($scheduler_groups["groups"])) {
        ?>
		<form id="frmSelect"  action="<?php echo ENTRADA_URL; ?>/admin/groups?section=manage" method="post">
            <table class="table table-striped" cellspacing="0" cellpadding="1" summary="List of Cohorts">
                <colgroup>
                    <col class="modified" />
                    <col class="community_title" />
                    <col class="community_shortname" />
                </colgroup>
                <thead>
                    <tr>
                        <th class="modified">&nbsp;</th>
                        <th class="community_title<?php echo (($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "group_name") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]) : ""); ?>"><?php echo admin_order_link("group_name", "Cohort Name"); ?></th>
                        <th class="community_shortname<?php echo (($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "members") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]) : ""); ?>"><?php echo admin_order_link("members", "Number of Learners"); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                foreach ($scheduler_groups["groups"] as $result) {
                    $url = ENTRADA_URL."/admin/groups?section=edit&ids=".$result["group_id"];

                    echo "<tr id=\"group-".$result["group_id"]."\" class=\"group".((!$result["group_active"]) ? " na" : (($result["inactive"]) ? " np" : ""))."\">\n";
                    echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"checked[]\" value=\"".$result["group_id"]."\" /></td>\n";
                    echo "	<td class=\"community_title\"><a href=\"".$url."\">".html_encode($result["group_name"])."</a></td>\n";
                    echo "	<td class=\"community_shortname\"><a href=\"".$url."\">".$result["members"]."</a></td>\n";
                    echo "</tr>\n";
                }
                ?>
                </tbody>
            </table>
            <div class="row-fluid" style="margin-top:10px;">
                <?php
                //delete
                if ($ENTRADA_ACL->amIAllowed("group", "delete", false)) {
                    ?>
                    <input type="submit" class="btn btn-danger" value="Delete Selected"  onClick="$('frmSelect').action ='<?php echo ENTRADA_URL; ?>/admin/groups?section=manage'" />
                    <?php
                }
                //update
                if ($ENTRADA_ACL->amIAllowed("group", "update", false)) {
                    ?>
                    <input type="submit" class="btn" value="Edit Selected" onClick="$('frmSelect').action ='<?php echo ENTRADA_URL; ?>/admin/groups?section=edit'" />
                    <?php
                }
                if ($ENTRADA_ACL->amIAllowed("group", "read", false)) {
                    ?>
                    <input type="submit" class="btn pull-right" value="Export Selected" onClick="$('frmSelect').action ='<?php echo ENTRADA_URL; ?>/admin/groups?section=csv'" />
                    <?php
                }
                ?>
            </div>
		</form>
		<?php
	} else {
		?>
		<div class="display-notice">
			<h3><?php echo $translate->_("No Available Cohorts"); ?></h3>
			There are currently no available small groups in the system. To begin click the <strong><?php echo $translate->_("Add Cohort"); ?></strong> link above.
		</div>
		<?php
	}

	echo "<form action=\"\" method=\"get\">\n";
	echo "<input type=\"hidden\" id=\"dstamp\" name=\"dstamp\" value=\"".html_encode($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"])."\" />\n";
	echo "</form>\n";

	$ONLOAD[] = "initList()";
}
