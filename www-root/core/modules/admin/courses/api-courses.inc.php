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
 * API to handle interaction with Courses.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Eugene Bivol <ebivol@gmail.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
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
                    case "get-courses" :

                        $column_check_array = array ("type", "name", "code");
                        $direction_check_array = array ("asc", "desc");


                        if (isset($request["search_term"]) && $tmp_input = clean_input(strtolower($request["search_term"]), array("trim", "striptags"))) {
                            $PROCESSED["search_term"] = "%".$tmp_input."%";
                        } else {
                            $PROCESSED["search_term"] = "";
                        }
                        
                        if (isset($request["limit"]) && $tmp_input = clean_input(strtolower($request["limit"]), array("trim", "int"))) {
                            $PROCESSED["limit"] = $tmp_input;
                        } else {
                            $PROCESSED["limit"] = 25;
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
                                $PROCESSED["sort_column"] = "type";
                            }
                        } else {
                            $PROCESSED["sort_column"] = "type";
                        }

                        if (isset($request["ord"]) && $tmp_input = clean_input(strtolower($request["ord"]),  array("trim", "striptags"))) {
                            if (in_array($tmp_input,$direction_check_array)) {
                                $PROCESSED["sort_direction"] = $tmp_input;
                            } else {
                                $PROCESSED["sort_direction"] = "asc";
                            }
                        } else {
                            $PROCESSED["sort_direction"] = "asc";
                        }

                        $courses_object = new Models_Course();


                        if ($ENTRADA_ACL->amIAllowed('course', 'update', false)) {
                            $courses = $courses_object->getReadOnlyCourses($ENTRADA_USER->getActiveOrganisation(),$PROCESSED["search_term"],$PROCESSED["offset"], $PROCESSED["limit"], $PROCESSED["sort_column"], $PROCESSED["sort_direction"]);
                            $total_courses = $courses_object->getTotalReadOnlyCourses($ENTRADA_USER->getActiveOrganisation(),$PROCESSED["search_term"]);
                        } else {
                            $courses = $courses_object->getFullAccessCourses($ENTRADA_USER->getActiveId(),$ENTRADA_USER->getActiveOrganisation(),$PROCESSED["search_term"],$PROCESSED["offset"], $PROCESSED["limit"], $PROCESSED["sort_column"], $PROCESSED["sort_direction"]);
                            $total_courses = $courses_object->getTotalFullAccessCourses($ENTRADA_USER->getActiveId(),$ENTRADA_USER->getActiveOrganisation(),$PROCESSED["search_term"]);
                        }


                        if ($courses) {
                            $data = array();
                            foreach ($courses as $course) {

                                $course_data = array(
                                    "course_id"           => $course["course_id"],
                                    "course_code"         => ($course["course_code"] && !empty($course["course_code"]) ? $course["course_code"] : "N/A"),
                                    "course_name"         => $course["course_name"],
                                    "curriculum_type"     => $course["curriculum_type_name"]
                                );

                                $course_data["course_permission"] = false;
                                $course_data["course_content_permission"] = false;
                                $course_data["course_gradebook"] = false;

                                if($ENTRADA_ACL->amIAllowed(new CourseResource($course["course_id"], $ENTRADA_USER->getActiveOrganisation()), "update")) {
                                    $course_data["course_permission"] = true;
                                }
                                if($ENTRADA_ACL->amIAllowed(new CourseContentResource($course["course_id"], $ENTRADA_USER->getActiveOrganisation()), "read")) {
                                    $course_data["course_content_permission"] = true;
                                }
                                if($ENTRADA_ACL->amIAllowed(new GradebookResource($course["course_id"], $ENTRADA_USER->getActiveOrganisation()), "read")) {
                                    $course_data["course_gradebook"] = true;
                                }

                                $data[] = $course_data;
                            }
                            echo json_encode(array("results" => count($courses), "data" => array("total_courses" => $total_courses["total_rows"], "courses" => $data)));
                        } else {
                            echo json_encode(array("results" => "0", "data" => array($translate->_("No Items Found."))));
                        }
                    break;
                    default:
                        echo json_encode(array("status" => "error", "data" => $translate->_("Invalid GET method.")));
                    break;
                }
            break;    
            case "POST" :
                switch ($method) {
                    case "delete-courses" :
                        $PROCESSED["delete_ids"] = array();
                        if (isset($request["delete_ids"]) && is_array($request["delete_ids"])) {
                            foreach ($request["delete_ids"] as $course_id) {
                                $tmp_input = clean_input($course_id, "int");
                                if ($tmp_input) {
                                    $PROCESSED["delete_ids"][] = $tmp_input;
                                }
                            }
                        }

                        if (!empty($PROCESSED["delete_ids"])) {
                            $deleted_courses = array();
                            $course_object = new Models_Course();
                            foreach ($PROCESSED["delete_ids"] as $course_id) {
                                $course = $course_object->get($course_id);
                                if ($course) {
                                    if (!$course->fromArray(array("course_active" => "0" ))->update()) {
                                        add_error($translate->_("Unable to delete a Course"));
                                    } else {
                                        $ENTRADA_LOGGER->log("", "delete", "course_id", $course_id, 4, __FILE__, $ENTRADA_USER->getID());
                                        $deleted_courses[] = $course_id;
                                    }
                                }
                            }
                            if (!$ERROR) {
                                echo json_encode(array("status" => "success", "msg" => sprintf($translate->_("Successfully deleted %d Course(s)."), count($deleted_courses)), "course_ids" => $deleted_courses));
                            } else {
                                echo json_encode(array("status" => "error", "msg" => $translate->_("There was an error when attempting to delete an Course.")));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("No Courses were selected for deletion.")));
                        }
                    break;
                }
            break;
        }
    } else {
        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
    }
    exit;
}