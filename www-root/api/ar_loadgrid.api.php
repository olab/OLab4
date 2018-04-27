<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Serves the categories list up in a select box.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Andrew Dos-Santos <andrew.dos-santos@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

/**
 * Load the grid - used by the annualreport module.
 */

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

if ((isset($_SESSION["isAuthorized"])) && ((bool) $_SESSION["isAuthorized"]) && $ENTRADA_ACL->amIAllowed('annualreport', 'read')) {

    $proxy_id = $ENTRADA_USER->getActiveId();

    // table name and fields
    $args = "";
    $table = "";
    $table_index = "";
    $fields = [];
    if (isset($_GET['t']) && ($tmp_input = clean_input($_GET['t'], array("notags")))) {
        $args = explode(",", $tmp_input);
        if (count($args) > 2) {
            $table = $args[0];
            $table_index = $args[1];
            $fields = array_slice($args, 2);
        }
    }

    if (isset($_POST["sortname"]) && ($tmp_input = clean_input($_POST["sortname"], array("trim", "notags")))) {
        $sort = $tmp_input;
    } else {
        $sort = 'year_reported';
    }

    if (isset($_POST["sortorder"]) && ($tmp_input = clean_input($_POST["sortorder"], array("trim", "notags")))) {
        $dir = $tmp_input;
    } else {
        $dir = 'DESC';
    }

    if (isset($_POST["rp"]) && ($tmp_input = clean_input($_POST["rp"], array("trim", "notags")))) {
        $limit = $tmp_input;
    } else {
        $limit = '10';
    }

    if (isset($_POST["page"]) && ($tmp_input = clean_input($_POST["page"], array("trim", "notags")))) {
        $page = $tmp_input;
        if($page == 1) {
            $start = '0';
        } else {
            $start = ((int)$page * (int)$limit) - (int)$limit;
        }
    } else {
        $page = '1';
        $start = '0';
    }

    if (isset($_POST["query"]) && ($tmp_input = clean_input($_POST["query"], array("trim", "notags")))) {
        $search_term = $tmp_input;
    } else {
        $search_term = "";
    }

    if (isset($_POST["qtype"]) && ($tmp_input = clean_input($_POST["qtype"], array("trim", "notags")))) {
        $search_field = $tmp_input;
    } else {
        $search_field = "";
    }

    if (!empty($search_field) && !empty($search_term)) {
        $where = " AND " . $search_field . " LIKE '%" . $db->qstr($search_term) . "%' ";
    } else {
        $where = "";
    }

    $error = false;

    // this is only for Annual Report tables.
    $starts_with = "ar_";
    if (substr($table, 0, strlen($starts_with)) !== $starts_with) {
        $error = true;
    }

    // validate there is a table in the database with the table name, and that the table_index and proxy_id columns exist
    $db_info = new Models_Base();
    if (!$db_info->tableExists(DATABASE_NAME, $table) ||
        !$db_info->columnExists(DATABASE_NAME, $table, $table_index) ||
        !$db_info->columnExists(DATABASE_NAME, $table, "proxy_id")) {
        $error = true;
    }

    if ($error) {
        application_log("error", "Annual Report grid loader (ar_loadgrid) API accessed with incorrect arguments");
        http_response_code(404);
    }

    $query = "SELECT COUNT(proxy_id) AS total
	          FROM `".$table."` 
	          WHERE `proxy_id` = ? "
              . $where;

    if ($result = $db->GetRow($query, [$proxy_id])) {
        $total = $result["total"];
    } else {
        $total = 0;
    }

    $query = "SELECT *
	          FROM `".$table."` 
	          WHERE `proxy_id` = ? ". $where ."
              ORDER BY ". $sort . " " . $dir . "
              LIMIT " . $start . " , " . $limit;

    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
    header("Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . "GMT" );
    header("Cache-Control: no-cache, must-revalidate" );
    header("Pragma: no-cache" );
    header("Content-type: text/json");

    if ($results = $db->GetAll($query, [$proxy_id])) {
		$data['page'] = $page;
		$data['total'] = $total;
		foreach ($results as $row) {
            $cell = [];
            foreach ($fields as $field) {
                // Replace all line returns as to not break JSON output (grid will not load otherwise)
                $row[$field] = str_replace("\r\n", " ", $row[$field]);
                $row[$field] = str_replace("\n", " ", $row[$field]);
                $row[$field] = str_replace("\r", " ", $row[$field]);

                if ($row[$field] == "") {
                    $row[$field] = addslashes("N/A");
                }
                $cell[] = $row[$field];
            }

            $rows[] = [
                "id" => $row[$table_index],
                "cell" => $cell
            ];
        }

		$data['rows'] = $rows;
        $data['params'] = [
            "sortname" => $sort,
            "sortorder" => $dir,
            "rp" => $limit,
            "page" => $page,
            "query" => $search_term,
            "qtype" => $search_field
        ];

		echo json_encode($data); 
	} else {
		$data['page'] = 1;
		$data['total'] = 0;
		$rows[] = array();
		$data['rows'] = $rows;
		echo json_encode($data);
	}
} else {
    application_log("error", "Annual Report grid loader (ar_loadgrid) API accessed without authorization");
    http_response_code(403);
    exit;
}