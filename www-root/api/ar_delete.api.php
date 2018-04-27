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
 * Delete the record - used by the annualreport module.
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

if ((isset($_SESSION["isAuthorized"])) && ((bool) $_SESSION["isAuthorized"]) && $ENTRADA_ACL->amIAllowed('annualreport', 'delete')) {

    // proxy_id of the records to be deleted
    if (isset($_GET['id']) && ($tmp_input = clean_input($_GET['id'], array("int")))) {
        $proxy_id = $tmp_input;
    } else {
        $proxy_id = 0;
    }

    // table name and fields
    $args = "";
    $table = "";
    $table_index = "";
    if (isset($_GET['t']) && ($tmp_input = clean_input($_GET['t'], array("notags")))) {
        $args = explode(",", $tmp_input);
        $table = $args[0];
        $table_index = $args[1];
    }

    // record_id's to be deleted, from the table above, using the index field in the second index
    $ids = [];
    if (isset($_GET["rid"]) && ($tmp_input = clean_input($_GET["rid"], array("notags")))) {
        $rid = $tmp_input;
        $ids = explode("|", $rid);
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

    if (!$error && !empty($ids)) {

        $prepared_ids = Entrada_Utilities::sanitizeArrayAndImplode($ids, ["int"]);

        $query = "DELETE FROM `".DATABASE_NAME."`.`".$table."` 
		          WHERE `proxy_id` = ?  AND `".$table_index."` IN (".$prepared_ids.")";
        if(!$result = $db->Execute($query, [$proxy_id])) {
            echo $db->ErrorMsg();
            exit;
        }
        echo '({"total":"' . $result->recordCount() .'", "results":[]})';
    } else {
        application_log("error", "Delete Annual Report Record (ar_delete) API accessed with incorrect arguments");
        http_response_code(404);
    }
} else {
    application_log("error", "Delete Annual Report Record (ar_delete) API accessed without authorization");
    http_response_code(403);
    exit;
}