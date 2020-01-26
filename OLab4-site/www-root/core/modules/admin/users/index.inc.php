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
 * The default file that is loaded when /admin/users is accessed.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if (!defined("PARENT_INCLUDED") || !defined("IN_USERS")) {
	exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !$_SESSION["isAuthorized"]) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("user", "update", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	add_manage_user_sidebar();
	/**
	 * Add this for the tabs.
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

	/**
	 * Determine the type of search that is requested.
	 */
	if (isset($_GET["type"]) && in_array(trim($_GET["type"]), array("search", "browse-group", "browse-dept"))) {
		$search_type = clean_input($_GET["type"], "trim");
	}

    $HEAD[] = "<script type=\"text/javascript\" >var ENTRADA_URL = '". ENTRADA_URL ."';</script>\n";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/selectchained.js\"></script>\n";
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/picklist.js\"></script>\n";
	$HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js\"></script>\n";
	$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />\n";

	$i = count($HEAD);
	$HEAD[$i]  = "<script type=\"text/javascript\">\n";
	$HEAD[$i] .= "addListGroup('account_type', 'cs-top');\n";
	if (is_array($SYSTEM_GROUPS)) {
		$item = 1;
		foreach ($SYSTEM_GROUPS as $group => $roles) {
			$HEAD[$i] .= "addList('cs-top', '".ucwords($group)."', '".$group."', 'cs-sub-".$item."', ".(((isset($_GET["g"])) && ($_GET["g"] == $group)) ? "1" : "0").");\n";
			$HEAD[$i] .= "addOption('cs-sub-".$item."', '-- Any --', 'any', ".(((!isset($_GET["r"])) || ((isset($_GET["r"])) && ($_GET["r"] == 'any'))) ? "1" : "0").");\n";
			if (is_array($roles) && count($roles)) {
				foreach ($roles as $role) {
					$HEAD[$i] .= "addOption('cs-sub-".$item."', '".ucwords($role)."', '".$role."', ".(((isset($_GET["r"])) && ($_GET["r"] == $role)) ? "1" : "0").");\n";
				}
			}
			$item++;
		}
	}
	$HEAD[$i] .= "</script>\n";

	$ONLOAD[] = "initListGroup('account_type', jQuery('#group')[0], jQuery('#role')[0])";
	
	/**
	 * Set default sort values
	 */
	if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"])) {
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] = "fullname";
	}
	if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"])) {
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"] = "asc";
	}

	/**
	 * Update with custom sort values if given
	 */
	if (isset($_GET["sb"])) {
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] = $_GET["sb"];
	}
	if (isset($_GET["so"])) {
		$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"] = $_GET["so"];
	}
	
	switch ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]) {
		case "fullname":
			$order_by = "ORDER BY `fullname` ";
		break;
		case "username":
			$order_by = "ORDER BY a.`username` ";
		break;
		case "role":
			$order_by = "ORDER BY CONCAT(b.`group`, b.`role`) ";
		break;
		case "login":
			$order_by = "ORDER BY b.`last_login` ";
		break;
		default:
			$order_by = "ORDER BY a.`id` ";
		break;
	}
	
	switch ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]) {
		case "desc":
			$order_by .= "DESC";
		break;
		case "asc":
		default:
			$order_by .= "ASC";
		break;
	}
	
	switch ($search_type) {
		case "browse-group" :
			$browse_group	= false;
			$browse_role	= false;

			if ((isset($_GET["g"])) && (isset($SYSTEM_GROUPS[$group = clean_input($_GET["g"], "credentials")]))) {
				$browse_group = $group;
				$search_query_text	= html_encode(ucwords($group));
				if ((isset($_GET["r"])) && (@in_array($role = clean_input($_GET["r"], "credentials"), $SYSTEM_GROUPS[$browse_group]))) {
					$browse_role = $role;
					$search_query_text.= " &rarr; ".html_encode(ucwords($role));
				} else {
					$search_query_text.= " &rarr; Any Class";
				}
			} else {
				add_error("To browse a group, you must select a group from the group select list.");
			}

			if (!$ERROR) {
				$query_counter	= "	SELECT COUNT(DISTINCT(a.`id`)) AS `total_rows`
									FROM `".AUTH_DATABASE."`.`user_data` AS a
									LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
									ON b.`user_id` = a.`id`
									AND b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
									WHERE b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
									AND b.`group` = ".$db->qstr($browse_group)."
									AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
									".(($browse_role) ? "AND b.`role` = ".$db->qstr($browse_role) : "");
				$query_search	= "	SELECT a.*, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, b.`account_active`, b.`access_starts`, b.`access_expires`, b.`last_login`, b.`role`, b.`group`
									FROM `".AUTH_DATABASE."`.`user_data` AS a
									LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
									ON b.`user_id` = a.`id`
									AND b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
									WHERE b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
									AND b.`group` = ".$db->qstr($browse_group)."
									AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
									".(($browse_role) ? "AND b.`role` = ".$db->qstr($browse_role) : "")."
									GROUP BY a.`id`
									$order_by
									LIMIT ?, ?";
			}
		break;
		case "browse-dept" :
			$browse_dept = 0;

			if ((isset($_GET["d"])) && ($department = clean_input($_GET["d"], array("trim", "int")))) {
				$query = "SELECT * FROM `".AUTH_DATABASE."`.`departments` WHERE `department_id` = ".$db->qstr($department);
				$result = $db->GetRow($query);
				if ($result) {
					$browse_department = $department;
					$search_query_text = html_encode($result["department_title"]);
				} else {
					add_error("The department you have provided does not exist. Please ensure that you select a valid department from the department list.");
				}
			} elseif (isset($_GET["browse_departments"])) {
				add_error("To browse a department, you must select a department from the department selection list.");
			}

			if (!$ERROR) {
				$query_counter = "	SELECT COUNT(DISTINCT(a.`id`)) AS `total_rows`
									FROM `".AUTH_DATABASE."`.`user_data` AS a
									LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
									ON b.`user_id` = a.`id`
									AND b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
									LEFT JOIN `".AUTH_DATABASE."`.`user_departments` AS c
									ON c.`user_id` = a.`id`
									WHERE b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
									AND c.`dep_id` = ".$db->qstr($browse_department);
				$query_search = "	SELECT a.*, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, b.`account_active`, b.`access_starts`, b.`access_expires`, b.`last_login`, b.`role`, b.`group`
									FROM `".AUTH_DATABASE."`.`user_data` AS a
									LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
									ON b.`user_id` = a.`id`
									AND b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
									LEFT JOIN `".AUTH_DATABASE."`.`user_departments` AS c
									ON c.`user_id` = a.`id`
									WHERE b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
									AND c.`dep_id` = ".$db->qstr($browse_department)."
									GROUP BY a.`id`
									$order_by
									LIMIT ?, ?";
			} else {
                echo display_error();
            }
		break;
		case "browse-newest" :
            if ((isset($_GET["n"])) && ($number = clean_input($_GET["n"], array("trim", "int"))) && ($number > 0) && ($number <= 100)) {
                $browse_number = $number;
                $results_per_page = $browse_number;
            }

            if (!$ERROR) {
                $search_query_text = "Newest ".(int) $browse_number." User".(($browse_number != 1) ? "s" : "");
                $query_counter = "	SELECT
				                        IF(
				                            COUNT(DISTINCT(a.`id`)) <= ".((int)$browse_number).",
				                                COUNT(DISTINCT(a.`id`)),
				                                ".$db->qstr((int)$browse_number)."
                                        ) AS `total_rows`
									FROM `".AUTH_DATABASE."`.`user_data` AS a
									JOIN `".AUTH_DATABASE."`.`user_access` AS b
									ON b.`user_id` = a.`id`
									AND b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
									ORDER BY a.`id` DESC
									LIMIT 0, ".((int)$browse_number);
                $query_search = "	SELECT a.*, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, b.`account_active`, b.`access_starts`, b.`access_expires`, b.`last_login`, b.`role`, b.`group`
									FROM `".AUTH_DATABASE."`.`user_data` AS a
									JOIN `".AUTH_DATABASE."`.`user_access` AS b
									ON b.`user_id` = a.`id`
									AND b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
                                    GROUP BY a.`id`
									ORDER BY a.`id` DESC
									LIMIT 0, ".((int)$browse_number);
            }
		break;
		case "search" :
		default :
			if ((isset($_GET["q"])) && ($query = clean_input($_GET["q"], array("trim", "notags")))) {
				$search_query = $query;
				$search_query_text = html_encode($query);
			}

			if (isset($_GET["search-type"]) && $_GET["search-type"]) {

			    $search_type = $_GET["search-type"];
                $query_search = Models_User::get_search_query_sql($search_type, $search_query, $order_by);
                $query_counter = Models_User::get_search_counter_sql($search_type, $search_query);

				$sidebar_html  = "<div style=\"margin: 2px 0px 10px 3px; font-size: 10px\">\n";
				$sidebar_html .= "	<div><img src=\"".ENTRADA_URL."/images/legend-active-member.gif\" width=\"14\" height=\"14\" alt=\"\" title=\"\" style=\"vertical-align: middle\" /> Active Member</div>\n";
				$sidebar_html .= "	<div><img src=\"".ENTRADA_URL."/images/legend-inactive-member.gif\" width=\"14\" height=\"14\" alt=\"\" title=\"\" style=\"vertical-align: middle\" /> Inactive Member</div>\n";
				$sidebar_html .= "	<div><img src=\"".ENTRADA_URL."/images/legend-non-member.gif\" width=\"14\" height=\"14\" alt=\"\" title=\"\" style=\"vertical-align: middle\" /> Non-Member</div>\n";
				$sidebar_html .= "</div>\n";

				new_sidebar_item("Members Legend", $sidebar_html, "member-legend", "open");
			}
		break;
	}

	/**
	 * Get the total number of results using the generated queries above and calculate the total number
	 * of pages that are available based on the results per page preferences.
	 */
	$result = ((USE_CACHE) ? $db->CacheGetRow(CACHE_TIMEOUT, $query_counter) : $db->GetRow($query_counter));

	if ($result) {
		$total_rows	= $result["total_rows"];

		if ($total_rows <= $results_per_page) {
			$total_pages = 1;
		} elseif (($total_rows % $results_per_page) == 0) {
			$total_pages = (int) ($total_rows / $results_per_page);
		} else {
			$total_pages = (int) ($total_rows / $results_per_page) + 1;
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

	$page_previous = (($page_current > 1) ? ($page_current - 1) : false);
	$page_next = (($page_current < $total_pages) ? ($page_current + 1) : false);
	?>

	<h1><?php echo $MODULES[strtolower($MODULE)]["title"]; ?></h1>

    <?php
    Entrada_Utilities_Flashmessenger::displayMessages($MODULE);
    ?>

    <div class="row-fluid">
        <div class="pull-right">
            <a href="#import-csv" class="btn btn-default" data-toggle="modal"><i class="fa fa-upload"></i> <?php echo $translate->_("Import From CSV"); ?></a>
            <a href="<?php echo ENTRADA_URL; ?>/admin/users?section=add" class="btn btn-success"><i class="icon-plus-sign icon-white"></i> <?php echo $translate->_("Add New User"); ?></a>
        </div>
    </div>

	<style type="text/css">
        .departments-advanced-search {
            width: 100%;
            text-align: left;
            margin: 0px 0px 20px;
        }
	</style>

    <div class="modal hide fade" id="import-csv">
        <form id="csv-form" action="<?php echo ENTRADA_URL; ?>/admin/users?section=csv-import" enctype="multipart/form-data" method="POST">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h3>Import CSV</h3>
            </div>
            <div class="modal-body">
                <div id="display-notice-box" class="display-notice">
                    <ul>
                        <li>
                            <strong><?php echo $translate->_("Important Notes:") ?></strong>
                            <p><?php echo $translate->_("Upon uploading a CSV you will be prompted to confirm the association between column headings and their data points."); ?></p>
                            <a href="<?php echo ENTRADA_URL; ?>/admin/users?section=csv-import&step=3">
                                <img style="border: none;" src="<?php echo ENTRADA_URL; ?>/images/btn_help.gif" />
                                <label><?php echo $translate->_("Download sample CSV file"); ?></label>
                            </a>
                        </li>
                    </ul>
                </div>
                <input type="file" name="csv_file" />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Close") ?></button>
                <button type="submit" id="submit-btn" class="btn btn-primary"><?php echo $translate->_("Import CSV") ?></button>
            </div>
        </form>
    </div>

	<div class="tab-pane" id="user-tabs">
		<div class="tab-page">
			<h3 class="tab">Newest Users</h3>
			<form class="form" action="<?php echo ENTRADA_URL; ?>/admin/users" method="get">
                <input type="hidden" name="type" value="browse-newest" />
                <label class="control-label form-required" for="number">Number of Newest Users:</label>
                <select class="span12" id="number" name="n">
                    <option value="25"<?php echo (isset($browse_number) && $browse_number == 25 ? " selected=\"selected\"" : ""); ?>>25</option>
                    <option value="50"<?php echo (isset($browse_number) && $browse_number == 50 ? " selected=\"selected\"" : ""); ?>>50</option>
                    <option value="75"<?php echo (isset($browse_number) && $browse_number == 75 ? " selected=\"selected\"" : ""); ?>>75</option>
                    <option value="100"<?php echo (isset($browse_number) && $browse_number == 100 ? " selected=\"selected\"" : ""); ?>>100</option>
                </select>
                <input type="hidden" name="type" value="browse-newest" />
                <button type="submit" class="btn btn-primary">Show</button>
			</form>
		</div>
		<div class="tab-page">
			<h3 class="tab">User Search</h3>
			<form class="form" action="<?php echo ENTRADA_URL; ?>/admin/users" method="get">
                <input type="hidden" name="type" value="search" />
                <label for="q" class="form-required control-label">User Search:</label>
                <input class="span12" type="text" id="q" name="q" placeholder="You can search for name, username, e-mail address or staff / student number." value="<?php echo html_encode($search_query); ?>" />
                <label for="search-type" class="form-required control-label">Search Type:</label>
                <select name="search-type" class="span12">
                    <option value="all" <?php echo ((isset($_GET["search-type"]) && $_GET["search-type"] == "all") || (!isset($_GET["search-type"])) ? "selected=\"true\" " : ""); ?>>All Users</option>
                    <option value="active" <?php echo (isset($_GET["search-type"]) && $_GET["search-type"] == "active" ? "selected=\"true\" " : ""); ?>>Users With Active Membership</option>
                    <option value="inactive" <?php echo (isset($_GET["search-type"]) && $_GET["search-type"] == "inactive" ? "selected=\"true\" " : ""); ?>>Users With Inactive Membership </option>
                    <option value="new" <?php echo (isset($_GET["search-type"]) && $_GET["search-type"] == "new" ? "selected=\"true\" " : ""); ?>>Users With No Membership</option>
                </select>
                <button class="btn btn-primary" type="submit">Search</button>
			</form>
		</div>
		<div class="tab-page">
			<h3 class="tab">Browse Groups</h3>
			<form class="form" action="<?php echo ENTRADA_URL; ?>/admin/users" method="get">
			    <input type="hidden" name="type" value="browse-group" />
                <label for="group" class="form-required control-label">Browse Group:</label>
                <select id="group" name="g" class="span12"></select>
                <label for="role" class="form-nrequired control-label">Browse Role:</label>
                <select id="role" name="r" class="span12"></select>
                <button class="btn btn-primary" type="submit">Browse</button>
			</form>
		</div>
		<div class="tab-page">
			<h3 class="tab">Browse Departments</h3>

			<form id="browse-departments-form" class="form" action="<?php echo ENTRADA_URL; ?>/admin/users" method="get">
				<input type="hidden" name="type" value="browse-dept" />
					<label for="departments-advanced-search" class="form-required control-label">Department:</label>
					<button id="departments-advanced-search" class="btn btn-search-filter departments-advanced-search">
                        <?php echo $translate->_("Browse Departments"); ?>
                        <i class="icon-chevron-down pull-right btn-icon"></i>
                    </button>
                    <button type="submit" class="btn btn-primary" name="browse_departments">Browse</button>
                <input id="department-id" type="hidden" name="d">
			</form>
		</div>
	</div>

	<script type="text/javascript">
        setupAllTabs(true);

        jQuery(document).ready(function($) {
            var current_height = parseInt($(".tab-page").css("height"));
            var organisations = <?php echo json_encode(Models_Organisation::fetchOrganisationsWithDepartments()); ?>;
            var filters = {};

            for (var i = 0; i < organisations.length; i++) {
                var filter_name = organisations[i].organisation_title.split(" ").join("_");

                filters[filter_name] = {
                    data_source: "get-organisation-departments",
                    label: organisations[i].organisation_title,
                    mode: "radio",
                    set_button_text_to_selected_option: true,
                    api_params: {
                        organisation_id: organisations[i].organisation_id
                    }
                };
            }
            
            $("#departments-advanced-search").advancedSearch({
                api_url: "<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=api-departments"; ?>",
                async: true,
                resource_url: ENTRADA_URL,
                filters: filters,
				filter_component_label: "Departments",
                parent_form: $("#browse-departments-form"),
                width: 487
            });

            $("#departments-advanced-search").on("click", function () {
				if ($(".entrada-search-widget .filter-menu").length) {
					var menu_height = parseInt($(".entrada-search-widget .filter-menu").css("height"));

					$(".tab-page").css("min-height", current_height + menu_height + "px");
				} else {
					var overlay_height = parseInt($(".entrada-search-widget .search-overlay").css("height"));

					$(".tab-page").css("min-height", current_height + overlay_height + "px");				}
            });

            $(".entrada-search-widget").on("click", ".filter-list-item", function () {
                var overlay_height = parseInt($(".entrada-search-widget .search-overlay").css("height"));

                $(".tab-page").css("min-height", current_height + overlay_height + "px");
            });

            $("#browse-departments-form").on("change", ".search-target-input-control", function () {
                $("#department-id").val($(this).val());

				$(".tab-page").css("min-height", current_height + "px");

				var current_filter = $(this).attr("data-filter");
                
                $("#browse-departments-form").find(".search-target-control").not("." + current_filter + "_search_target_control").remove();
            });
        });
    </script>
	<?php
	if ($search_type && !$ERROR) {
		if ($total_pages > 1) {
            $pagination = new Entrada_Pagination($page_current, $results_per_page, $total_rows, ENTRADA_URL."/admin/".$MODULE, replace_query());
            echo $pagination->GetPageBar();
            echo $pagination->GetResultsLabel();
		}
		/**
		 * Provides the first parameter of MySQLs LIMIT statement by calculating which row to start results from.
		 */

        $results = Models_User::search_user($query_search, $page_current, $results_per_page, $search_type);

		if ($results) {
			?>
			<form action="<?php echo ENTRADA_URL; ?>/admin/users?section=delete" method="post">
			<table class="table table-bordered table-striped" cellspacing="0" summary="List of Users">
			<colgroup>
				<col class="modified" />
				<col class="title" />
				<col class="general" />
				<col class="general" />
				<col class="date" />
			</colgroup>
			<thead>
				<tr>
					<th class="modified">&nbsp;</th>
					<?php if ($search_type == "browse-newest"): ?>
						<th class="title">Full Name</th>
						<th class="username">Username</th>
						<th class="role">Group &amp; Role</th>
						<th class="last-login">Last Login</th>
					<?php else: ?>
						<th class="title<?php echo (($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "fullname") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]) : ""); ?>"><?php echo admin_order_link("fullname", "Full Name"); ?></th>
						<th class="username<?php echo (($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "username") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]) : ""); ?>"><?php echo admin_order_link("username", "Username"); ?></th>
						<th class="role<?php echo (($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "role") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]) : ""); ?>"><?php echo admin_order_link("role", "Group &amp; Role"); ?></th>
						<th class="last-login<?php echo (($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "login") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]) : ""); ?>"><?php echo admin_order_link("login", "Last Login"); ?></th>
					<?php endif; 
					if ($ENTRADA_ACL->amIAllowed("masquerade", "read")) {
						echo "<th>Login As</th>\n";
					}                    
                    ?>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td></td>
					<td colspan="<?php echo ($ENTRADA_ACL->amIAllowed("masquerade", "read") ? 5 : 4); ?>" style="padding-top: 10px">
						<input type="submit" class="btn btn-danger" value="Delete Selected" />
					</td>
				</tr>
			</tfoot>
			<tbody>
				<?php
				foreach ($results as $result) {
					$can_login	= true;
					$url		= ENTRADA_URL."/admin/users/manage?id=".$result["id"];
                    $add_url	= ENTRADA_URL."/admin/users/manage?section=edit&amp;id=".$result["id"];

                    $permission_to_delete = false;

					if ($result["account_active"] == "false") {
						$can_login = false;
					}

					if (($access_starts = (int) $result["access_starts"]) && ($access_starts > time())) {
						$can_login = false;
					}
					if (($access_expires = (int) $result["access_expires"]) && ($access_expires < time())) {
						$can_login = false;
					}
					if ($result["account_active"]) {
                        $query = "SELECT * FROM `".AUTH_DATABASE."`.`user_access`
                                    WHERE `user_id` = ".$db->qstr($result["id"])."
                                    AND `app_id` = ".$db->qstr(AUTH_APP_ID)."
                                    AND `account_active` = 'true'";
                        $access_records = $db->getAll($query);
                        if ($access_records) {
                            $permission_to_delete = true;
                            foreach ($access_records as $access_record) {
                                if (!$ENTRADA_ACL->amIAllowed(new UserResource($result["id"], $access_record["organisation_id"]), 'delete')) {
                                    $permission_to_delete = false;
                                    break;
                                }
                            }
                        }
						echo "<tr class=\"user".((!$can_login) ? " na" : "")."\">\n";
						echo "	<td class=\"modified\">".($permission_to_delete ? "<input type=\"checkbox\" name=\"delete[]\" value=\"".$result["id"]."\" />" : '')."</td>\n";
						echo "	<td class=\"title\">".(($url) ? "<a href=\"".$url."\" title=\"Edit Account: ".html_encode($result["fullname"])."\">" : "").html_encode($result["fullname"]).(($url) ? "</a>" : "")."</td>\n";
						echo "	<td class=\"general\">".(($url) ? "<a href=\"".$url."\" title=\"Edit Account: ".html_encode($result["fullname"])."\">" : "").html_encode($result["username"]).(($url) ? "</a>" : "")."</td>\n";
						echo "	<td class=\"general\">".(($url) ? "<a href=\"".$url."\" title=\"Edit Account: ".html_encode($result["fullname"])."\">" : "").ucwords($result["group"])." &rarr; ".ucwords($result["role"]).(($url) ? "</a>" : "")."</td>\n";
						echo "	<td class=\"date\">".(($url) ? "<a href=\"".$url."\" title=\"Edit Account: ".html_encode($result["fullname"])."\">" : "").(((int) $result["last_login"]) ? date(DEFAULT_DATETIME_FORMAT, (int) $result["last_login"]) : "Never Logged In").(($url) ? "</a>" : "")."</td>\n";
						if ($ENTRADA_ACL->amIAllowed("masquerade", "read")) {
							if ($result["id"] != $_SESSION["details"]["id"]) {
								echo "	<td><a href=\"".ENTRADA_URL."/admin/users?section=masquerade&id=".$result["id"]."\">Login as</a></td>\n";
							} else {
								echo "	<td>&nbsp;</td>\n";
							}
						}
						echo "</tr>\n";
					} else {
						echo "<tr class=\"user disabled\">\n";
						echo "	<td class=\"modified\">".($ENTRADA_ACL->amIAllowed(new UserResource($result["id"], $ENTRADA_USER->getActiveOrganisation()), 'create') ? "<a class=\"strong-green\" href=\"".$add_url."\" ><img style=\"border: none;\" src=\"".ENTRADA_URL."/images/btn_add.gif\" /></a>" : '')."</td>\n";
						echo "	<td class=\"title content-small\">".html_encode($result["fullname"])."</td>\n";
						echo "	<td class=\"general content-small\">".html_encode($result["username"])."</td>\n";
						echo "	<td class=\"general\">&nbsp;</td>\n";
						echo "	<td class=\"date\">&nbsp;</td>\n";
                        if ($ENTRADA_ACL->amIAllowed("masquerade", "read")) {
                            echo "	<td>&nbsp;</td>\n";
                        }
						echo "</tr>\n";
					}
				}
				?>
			</tbody>
			</table>
			</form>
			<?php
		} else {
			echo "<div class=\"display-notice\">\n";
			echo "	<h3>No Matching People</h3>\n";
			echo "	There are no people in the system found which contain matches to &quot;<strong>".($search_query_text)."</strong>&quot;.<br /><br />";
			echo "	You can add a new users by clicking the <strong>Add New User</strong> link.\n";
			echo "</div>\n";
		}
	}
}