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
 * API to handle interaction with discussion boards components
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2016 Regents of The University of California. All Rights Reserved.
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
    header("Location: ".COMMUNITY_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("discussion", "read", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    ob_clear_open_buffers();

    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));

    $request = ${"_" . $request_method};

    if ($ENTRADA_USER->getActiveRole() == "admin") {
        if (isset($request["proxy_id"]) && $tmp_input = clean_input($request["proxy_id"], "int")) {
            $PROCESSED["proxy_id"] = $tmp_input;
        } else {
            $PROCESSED["proxy_id"] = $ENTRADA_USER->getActiveID();
        }
    } else {
        $PROCESSED["proxy_id"] = $ENTRADA_USER->getActiveID();
    }

    $TEXT = $translate->_("community");
    $MODULE_TEXT = $TEXT["discussion"];
    switch ($request_method) {
        case "POST" :
            switch ($request["method"]) {
                case "save-opened":

                    if (isset($request["community_id"])) {
                        $tmp_input = clean_input($request["community_id"], "int");
                        $PROCESSED["community_id"] = $tmp_input;
                    }

                    if (isset($request["page_id"])) {
                        $tmp_input = clean_input($request["page_id"], "int");
                        $PROCESSED["page_id"] = $tmp_input;
                    }
                    if (isset($request["discussion_open"])) {
                        $tmp_input = clean_input($request["discussion_open"], "trim");
                        $PROCESSED["discussion_open"] = $tmp_input;
                    }

                    $PROCESSED["proxy_id"] = $ENTRADA_USER->getActiveId();

                    if (isset($PROCESSED["community_id"]) && isset($PROCESSED["discussion_open"])) {
                        $opened_discussions = Models_Community_Discussion_Open::fetchRowByProxyIdPageId($PROCESSED["proxy_id"], $PROCESSED["page_id"]);
                        if ($opened_discussions && is_object($opened_discussions)) {
                            $opened_discussions->setDiscussionOpen($PROCESSED["discussion_open"]);
                            if (!$opened_discussions->update()) {
                                echo json_encode(array("status" => "error", "data" => $MODULE_TEXT["error_open"]));
                            }
                        } else {
                            $opened_discussions = new Models_Community_Discussion_Open($PROCESSED);
                            if (!$opened_discussions->insert()) {
                                echo json_encode(array("status" => "error", "data" => $MODULE_TEXT["error_open"]));
                            }
                        }
                    }

                    break;
                default:
                    echo json_encode(array("status" => "error", "data" => $translate->_("Invalid POST method.")));
                    break;

            }
            break;
        case "GET" :
            switch ($request["method"]) {
                case "get-opened":
                    $proxy_id = $ENTRADA_USER->getActiveId();
                    $return = "";

                    if (isset($request["community_id"])) {
                        $tmp_input = clean_input($request["community_id"], "int");
                        $PROCESSED["community_id"] = $tmp_input;
                    }

                    if (isset($request["page_id"])) {
                        $tmp_input = clean_input($request["page_id"], "int");
                        $PROCESSED["page_id"] = $tmp_input;
                    }

                    $opened_discussions = Models_Community_Discussion_Open::fetchRowByProxyIdPageId($proxy_id, $PROCESSED["page_id"]);
                    if ($opened_discussions && is_object($opened_discussions)) {
                        $category_open = $opened_discussions->getDiscussionOpen();
                        $return = $category_open;
                    }

                    echo json_encode(array("status" => "success", "data" => $return));
                    break;
                default :
                    echo json_encode(array("status" => "error", "data" => $MODULE_TEXT["error_request"]));
                    break;
            }
    }

    exit;

}