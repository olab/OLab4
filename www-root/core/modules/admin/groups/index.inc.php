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
 * @copyright Copyright 2017 University of Calgary. All Rights Reserved.
 *
*/
if (!defined("IN_GROUPS")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("group", "update", false)) {
    add_error(sprintf($translate->_("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%s\"> %s </a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] does not have access to this module [".$MODULE."]");
} else {

	function in_array_r($array, $fields, $find){  // table class table-striped not working with error and warning clasess
		foreach($array as $item){
			foreach($fields as $field) {
			    if($item[$field] == $find) {
				    return true;
			    }
			}
		}
		return false;
	}

	/**
	 * Update requested column to sort by.
	 * Valid: director, name
	 */

	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/tabpane/tabpane.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
	$HEAD[] = "<link href=\"".ENTRADA_URL."/css/tabpane.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />\n";

	$search_query		= "";

	/**
	 * Determine the type of search that is requested.
	 */

	if (isset($_GET["sb"])) {
		if (@in_array(trim($_GET["sb"]), array("group_name", "group_type", "members", "updated_date"))) {
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
			$sort_by = "a.`group_name` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]).", a.`group_name` ASC";
		break;
        case "group_type" :
            $sort_by = "a.`group_type` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]).", a.`group_type` ASC";
            break;
		case "members" :
			$sort_by = "`members` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]).", `members` ASC";
		break;
		case "updated_date" :
		default :
			$sort_by = "a.`updated_date` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]).", a.`updated_date` ASC";
		break;
	}
	
	if (!isset($_GET["active"]) || $_GET["active"]==1) {
		$active = 1;
	} else {
		$active = 0;
	}
	
	if ((isset($_GET["q"])) && ($query = clean_input($_GET["q"], array("trim", "notags")))) {
		$search_query = html_encode($query);
	} else {
		$query = false;
	}

	/**
	 * Get the total number of results using the generated queries above and calculate the total number
	 * of pages that are available based on the results per page preferences.
	 */
	$result_count = Models_Group::getCountAllGroups($ENTRADA_USER->getActiveOrganisation(),$active,$query);

	if ($result_count) {
		$scheduler_groups["total_rows"] = $result_count;

		if ($scheduler_groups["total_rows"] <= $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"]) {
			$scheduler_groups["total_pages"] = 1;
		} elseif (($scheduler_groups["total_rows"] % $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"]) == 0) {
			$scheduler_groups["total_pages"] = (int) ($scheduler_groups["total_rows"] / $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"]);
		} else {
			$scheduler_groups["total_pages"] = (int) ($scheduler_groups["total_rows"] / $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"]) + 1;
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
	$limit_parameter = (int) (($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"] * $scheduler_groups["page_current"]) - $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"]);

	$scheduler_groups["groups"] = Models_Group::getAllGroups($ENTRADA_USER->getActiveOrganisation(),$active, $query, $sort_by, $limit_parameter, $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"]);

	?>
    <form action="<?php echo ENTRADA_URL; ?>/admin/groups" method="get" class="form-inline">
        <input type="text" id="q" name="q" value="<?php echo $search_query; ?>" placeholder="<?php echo $translate->_("Search in Cohort Name"); ?>" class="input-large search-icon">
        <input type="submit" class="btn btn-primary" value="Search" />
        <label for="active">
            <input type="radio" name="active" id="active" value=1 <?php echo ($active == 1 ? "checked=\"checked\"" : ""); ?> />
            <?php echo $translate->_("Active");?>
        </label>
        <label for="all" class="space-right">
            <input type="radio" name="active" id="all" value=0 <?php echo ($active == 0 ? "checked=\"checked\"" : "") ?>/>
            <?php echo $translate->_("All");?>
        </label>
    </form>

    <div id="modal-import-csv" class="modal fade hide">
        <form enctype="multipart/form-data" id="import-form" name="import-form" method="POST">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                        <h4 id="email-rpnow-view-modal-heading" class="modal-title"> <?php echo $translate->_("Import Cohorts from CSV") ?></h4>
                    </div>
                    <div class="modal-body">
                        <div id="msgs"></div>
                        <div>
                            <input type="hidden" id="method" name="method" value="import">
                            <div id="display-notice-box" class="display-notice">
                                <a href="<?php echo ENTRADA_URL; ?>/admin/groups?section=csv&method=demo">
                                    <img style="border: none;" src="<?php echo ENTRADA_URL; ?>/images/btn_help.gif" />
                                    <label><?php echo $translate->_("Download sample CSV file"); ?></label>
                                </a>
                            </div>
                            <input type="file" id="file" name="file" style="padding:5px;" />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Close") ?></button>
                        <button type="submit" id="submit-btn" class="btn btn-primary"><?php echo $translate->_("Import CSV") ?></button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $("#import-csv-button").on("click", function (event) {
                $("#msgs").empty();
                $("#modal-import-csv").modal("show");
                $("#modal-import-csv").on("hidden", function() {
                    window.location.reload();
                })
            });

            $("#import-form").submit(function(e) {
                $("#msgs").empty();
                var formData = new FormData($(this)[0]);
                $("#submit-btn").attr("disabled", "disabled");
                $.ajax({
                    type: "POST",
                    url: ENTRADA_URL + "/admin/groups?section=csv",
                    data: formData,
                    contentType: false,
                    cache: false,
                    processData: false,
                    success: function (data) {
                        var jsonResponse = JSON.parse(data);
                        if (jsonResponse.status === "success") {
                            if (jsonResponse.dataNotice.length > 0) {
                                display_notice(jsonResponse.dataNotice, "#msgs");
                            }
                            if (jsonResponse.dataSuccess.length > 0) {
                                display_success(jsonResponse.dataSuccess, "#msgs");
                            }
                            if (jsonResponse.dataError.length > 0) {
                                display_error(jsonResponse.dataError, "#msgs");
                            }
                        }
                        $("#submit-btn").removeAttr("disabled");
                    }
                });
                e.preventDefault();
            });
        });
    </script>
	<?php
	echo "<p />";
	if ($scheduler_groups["groups"] && count($scheduler_groups["groups"])) {
		unset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["ids"]);
        ?>
		<form id="frmSelect"  action="<?php echo ENTRADA_URL; ?>/admin/groups?section=manage" method="post">
            <div class="row-fluid">
                <?php
                if ($ENTRADA_ACL->amIAllowed("group", "read", false)) {
                    ?>
                    <div class="btn-group">
                        <a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
                            <?php echo $translate->_("Import / Export"); ?>
                            <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="#modal-import-csv" id="import-csv-button" role="button">
                                    <?php echo $translate->_("Import Cohorts from CSV file"); ?>
                                </a>
                            </li>
                            <li>
                                <a href="#" id="export-csv-button" role="button" onClick="$('frmSelect').action ='<?php echo ENTRADA_URL; ?>/admin/groups?section=csv&method=export';$('frmSelect').submit();">
                                    <?php echo $translate->_("Export Selected"); ?>
                                </a>
                                <input type="hidden" id="method" name="method" value="export">
                            </li>
                        </ul>
                    </div>

                    <?php
                }
                //delete
                if ($ENTRADA_ACL->amIAllowed("group", "delete", false)) {
                    ?>
                    <input type="submit" class="btn btn-danger pull-right space-left" value="<?php echo $translate->_("Delete Selected"); ?>"  onClick="$('frmSelect').action ='<?php echo ENTRADA_URL; ?>/admin/groups?section=manage'" />
                    <?php
                }
                //update
                if ($ENTRADA_ACL->amIAllowed("group", "update", false)) {
                    ?>
                    <input type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("Edit Selected"); ?>" onClick="$('frmSelect').action ='<?php echo ENTRADA_URL; ?>/admin/groups?section=edit'" />
                    <?php
                }

                ?>
            </div>
            <p class="muted text-center">
                <small>
                    <?php
                    echo sprintf($translate->_("Found %d cohorts or course lists."), $result_count);
                    ?>
                </small>
            </p>
            <table class="table table-bordered table-striped <?php echo (in_array_r($scheduler_groups["groups"], array("expired","inactive"),"1") ? "table-hover" : "table-striped")?>" summary="List of Cohorts">
                <thead>
                    <tr>
                        <th width="5%"></th>
                        <th width="35%" class="general<?php echo (($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "group_name") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]) : ""); ?>"><?php echo admin_order_link("group_name", $translate->_("Cohort Name")); ?></th>
                        <th width="20%" class="general<?php echo (($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "group_type") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]) : ""); ?>"><?php echo admin_order_link("group_type", $translate->_("Group Type")); ?></th>
                        <th width="15%" class="general<?php echo (($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "members") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]) : ""); ?>"><?php echo admin_order_link("members", $translate->_("Learners")); ?></th>
                        <th width="25%" class="general<?php echo (($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "updated_date") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]) : ""); ?>"><?php echo admin_order_link("updated_date", $translate->_("Last Updated")); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                foreach ($scheduler_groups["groups"] as $result) {
                    $url = ENTRADA_URL."/admin/groups?section=edit&ids=".$result["group_id"];

                    echo "<tr id=\"group-".$result["group_id"]."\" class=\" ".((!$result["group_active"]) ? "error" : (($result["inactive"]) ? "warning" : (($result["expired"]) ? "success" : "")))."\">\n";
                    echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"checked[]\" value=\"".$result["group_id"]."\" /></td>\n";
                    echo "	<td class=\"title\"><a href=\"".$url."\">".html_encode($result["group_name"])."</a></td>\n";
                    echo "	<td class=\"title\"><a href=\"".$url."\">".html_encode(ucwords(str_replace("_", " ", $result["group_type"])))."</a></td>\n";
                    echo "	<td><a href=\"".$url."\">".$result["members"]."</a></td>\n";
                    echo "	<td class=\"date\"><a href=\"".$url."\">".date("M jS Y", $result["updated_date"])."</a></td>\n";
                    echo "</tr>\n";
                }
                ?>
                </tbody>
            </table>
		</form>
		<?php
	} else {
		?>
		<div class="display-notice">
			<h3><?php echo $translate->_("No Available Cohorts."); ?></h3>
			<?php echo sprintf($translate->_("There are currently no available groups in the system. To begin click the <strong>%s</strong> link above."), $translate->_("Add New Cohort")); ?>
		</div>
		<?php
	}

	if ($scheduler_groups["total_pages"] > 1) {
        $pagination = new Entrada_Pagination($scheduler_groups["page_current"], $_SESSION[APPLICATION_IDENTIFIER]["groups"]["pp"], $scheduler_groups["total_rows"], ENTRADA_URL."/admin/".$MODULE, replace_query());
        echo $pagination->GetPageBar();
    }

	$ONLOAD[] = "initList()";
}
