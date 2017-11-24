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
 * API to handle interaction with the Assessment Item Bank.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

if((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("course", "read", false)) {
    add_error(sprintf($translate->_("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));
    echo display_error();
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    ob_clear_open_buffers();
    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));
    $request = ${"_" . $request_method};

    if (isset($request["method"]) && $tmp_input = clean_input($request["method"], array("trim", "striptags"))) {
        $method = $tmp_input;
    } else {
        add_error($translate->_("No method supplied."));
    }

    if (!$ERROR) {
        switch ($request_method) {
            case "GET" :
                switch ($method) {
                    case "get-gradebooks" :

                        $column_check_array = array ("type", "name", "code");
                        $direction_check_array = array ("asc", "desc");

                        if (isset($request["search_term"]) && $tmp_input = clean_input($request["search_term"], array("trim", "striptags"))) {
                            $PROCESSED["search_term"] = $tmp_input;
                        } else {
                            $PROCESSED["search_term"] = null;
                        }

                        if (isset($request["limit"]) && $tmp_input = clean_input(strtolower($request["limit"]), array("trim", "int"))) {
                            $PROCESSED["limit"] = $tmp_input;
                        } else {
                            $PROCESSED["limit"] = ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"] ?: 25);
                        }

                        if (isset($request["offset"]) && $tmp_input = clean_input(strtolower($request["offset"]), array("trim", "int"))) {
                            $PROCESSED["offset"] = $tmp_input;
                        } else {
                            $PROCESSED["offset"] = 0;
                        }

                        if (isset($request["col"]) && $tmp_input = clean_input(strtolower($request["col"]),  array("trim", "striptags"))) {
                            if (in_array($tmp_input,$column_check_array)) {
                                $PROCESSED["sort_column"] = $tmp_input;
                            } else {
                                $PROCESSED["sort_column"] = ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] ?: "code"); /* "type" => "`curriculum_type_name`","name" => "`course_name`","code" => "`course_code`" */
                            }
                        } else {
                            $PROCESSED["sort_column"] = ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] ?: "code");
                        }

                        if (isset($request["ord"]) && $tmp_input = clean_input(strtolower($request["ord"]),  array("trim", "striptags"))) {
                            if (in_array($tmp_input,$direction_check_array)) {
                                $PROCESSED["sort_direction"] = $tmp_input;
                            } else {
                                $PROCESSED["sort_direction"] = ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"] ?: "asc");
                            }
                        } else {
                            $PROCESSED["sort_direction"] = ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"] ?: "asc");
                        }

                        // update the preferences
                        $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] = $PROCESSED["sort_column"];
                        $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"] = $PROCESSED["sort_direction"];
                        $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["pp"] = $PROCESSED["limit"];
                        preferences_update($MODULE);

                        $gradebook_object = new Models_Course();

                        if ($ENTRADA_ACL->amIAllowed('course', 'update', false)) {
                            $gradebooks = $gradebook_object->fetchCourseForContact($ENTRADA_USER->getActiveOrganisation(),$PROCESSED["sort_column"],$PROCESSED["sort_direction"],
                                $PROCESSED["limit"], $PROCESSED["offset"], $PROCESSED["search_term"]);
                            $total_gradebooks = $gradebook_object->fetchTotalCountCourseForContact($ENTRADA_USER->getActiveOrganisation(), $PROCESSED["search_term"]);
                        } else {
                            $gradebooks = $gradebook_object->fetchCourseForGraderAssignmentContact($ENTRADA_USER->getActiveId(),$ENTRADA_USER->getActiveOrganisation(),
                                $PROCESSED["sort_column"], $PROCESSED["sort_direction"], $PROCESSED["limit"], $PROCESSED["offset"], $PROCESSED["search_term"]);
                            $total_gradebooks = $gradebook_object->fetchTotalCountCourseForGraderAssignmentContact($ENTRADA_USER->getActiveId(),$ENTRADA_USER->getActiveOrganisation(), $PROCESSED["search_term"]);
                        }

                        if ($gradebooks) {
                            $data = array();
                            foreach ($gradebooks as $gradebook) {
                                $gradebook_data = array(
                                    "course_id"           => $gradebook["course_id"],
                                    "course_code"         => $gradebook["course_code"],
                                    "course_name"         => $gradebook["course_name"],
                                    "curriculum_type"     => $gradebook["curriculum_type_name"]
                                );
                                
                                $data[] = $gradebook_data;
                            }
                            echo json_encode(array("data" => array("total_gradebooks" => intval($total_gradebooks[0]["total"]), "gradebooks" => $data)));
                        } else {
                            echo json_encode(array("data" => array("total_gradebooks" => 0, "gradebooks" => $translate->_("No Items Found."))));
                        }
                        
                        break;
                    default:
                        echo json_encode(array("status" => "error", "data" => $translate->_("Invalid GET method.")));
                        break;
                }
                break;
        }
    } else {
        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
    }
    exit;
}