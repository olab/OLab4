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
 * @author Organisation: University of Ottawa
 * @author Unit: Faculty of Medicine - Medtech
 * @author Developer: Yacine Ghomri <yghomri@uottawa.ca>
 * @copyright Copyright 2017 University of Ottawa. All Rights Reserved.
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
if((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
   http_response_code(403);
   exit;
} elseif (!$ENTRADA_ACL->amIAllowed("objective", "read", false)) {
    add_error(sprintf($translate->_("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
    http_response_code(403);
    exit;
} else {
    ob_clear_open_buffers();
    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));
    $request = ${"_" . $request_method};
    $tmp_input = clean_input($request["method"], array("trim", "striptags"));
    if ( isset($request["method"]) && $tmp_input ) {
        $method = $tmp_input;
    } else {
        add_error($translate->_("No method supplied."));
    }
    switch ($request_method) {
        case "GET" :
            switch ($method) {
                case "get-objective" :
                    if ($request["data"]) {
                        $decoded_html = html_decode($request["data"]);
                        $decoded_json = json_decode($request["data"]);
                        $arr = get_object_vars($decoded_json);
                        $obj = Models_Objective::fetchRow($arr['id'], 1 , $ENTRADA_USER->getActiveOrganisation());
                        $facultyCode = Models_Objective::fetchRowByCode(FACULTY_OBJECTIVE_CODE, 1, $ENTRADA_USER->getActiveOrganisation());
                        if ( empty($obj) || $obj->getParent() != $facultyCode->getID() ) {
                            echo false;
                        } else {
                            echo true;
                        }
                    }
                break;

                case "get-events" :
                    if ($request["data"]) {
                        $decoded_html = html_decode($request["data"]);
                        $decoded_json = json_decode($decoded_html);
                        $arr = get_object_vars($decoded_json);

                        $events = Models_Event::fetchAllByCourseID($arr['CourseId']);
                        if ( isset($events) && is_array($events) ) {
                            $data = array();
                            foreach ($events as $event) {
                                $event_data = array(
                                    "event_id" => $event->getID(),
                                    "event_title" => $event->getEventTitle()
                                );
                                $data[] = $event_data;
                            }
                            echo json_encode($data);
                        } else {
                            http_response_code(500);
                        }
                    }
                break;

                default:
                    http_response_code(405);
                exit;
            }
        break;
        default:
            http_response_code(405);
        exit;
    }
    exit;
}