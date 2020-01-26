<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Serves a particular calendar in either JSON or ICS depending on the extension of the $_GET["request"];
 * http://www.yourschool.ca/calendars/username.json
 * http://www.yourschool.ca/calendars/username.ics
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
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

if ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed('course', 'update', false)) {
    $ERROR++;
    $ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    $request = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));
    $request_var = "_".$request;
    $method = clean_input(${$request_var}["method"], array("trim", "striptags"));

    switch ($request) {
        case "POST" :
            switch ($method) {
                case "add-delete":
                    if (isset(${$request_var}["org_id"]) && $tmp_input = clean_input(${$request_var}["org_id"], "int")) {
                        $org_id = $tmp_input;
                    }

                    if (isset(${$request_var}["level_id"]) && $tmp_input = clean_input(${$request_var}["level_id"], "int")) {
                        $level_id = $tmp_input;
                    }

                    if (isset(${$request_var}["checked"]) && $tmp_input = clean_input(${$request_var}["checked"], "int")) {
                        $checked = $tmp_input;
                    }


            }

            break;
        case "GET" :
            break;
    }
}