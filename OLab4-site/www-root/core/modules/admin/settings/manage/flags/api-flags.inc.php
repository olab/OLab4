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
 * API to handle interaction with form components
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>, Adrian Mellognio <adrian.mellogno@queensu.ca>
 * @copyright Copyright 2014, 2016 Queen's University. All Rights Reserved.
 *
 */
if((!defined("PARENT_INCLUDED")) || (!defined("IN_CONFIGURATION"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "update", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));
    echo display_error();
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    ob_clear_open_buffers();

    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));
    $request = ${"_" . $request_method};

    switch ($request_method) {
        case "POST" :
            break;
        case "GET" :
            switch ($request["method"]) {
                case "update_flags_ordering" :
                    if (isset($request["ids_list"])) {
                        $PROCESSED["ids_list"] = array_map("intval", $request["ids_list"]);
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No flags specified")));
                        break;
                    }

                    foreach ($PROCESSED["ids_list"] as $flag_id) {
                        if (isset($request["id_" . $flag_id]) && $ordering = clean_input($request["id_" . $flag_id], array("trim", "int"))) {
                            Models_Assessments_Flag::updateOrdering($flag_id, $ordering);
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No ordering specified for flag with ID: ".$flag_id)));
                            break;
                        }
                    }

                    echo json_encode(array("status" => "success"));
                    break;

                default:
                    echo json_encode(array("status" => "error", "data" => $translate->_("Invalid GET method.")));
                    break;
            }
            break;
        default :
            echo json_encode(array("status" => "error", "data" => $translate->_("Invalid request method.")));
            break;
    }

    exit;

}