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
                    case "get-enrolment" :

                        if (isset($request["cperiod_id"]) && $tmp_input = clean_input(strtolower($request["cperiod_id"]), array("trim", "int"))) {
                            $PROCESSED["cperiod_id"] = $tmp_input;
                        } else {
                            $PROCESSED["cperiod_id"] = 0;
                        }

                        if (isset($request["course_id"]) && $tmp_input = clean_input(strtolower($request["course_id"]), array("trim", "int"))) {
                            $PROCESSED["course_id"] = $tmp_input;
                        } else {
                            $PROCESSED["course_id"] = 0;
                        }

                        $course_audience_object = new Models_Course_Audience();
                        if ($ENTRADA_ACL->amIAllowed('group', 'update', false) && $PROCESSED["cperiod_id"] &&  $PROCESSED["course_id"]) {
                            $audience_result = $course_audience_object->fetchAllByCourseIDCperiodID($PROCESSED["course_id"], $PROCESSED["cperiod_id"]);
                        }

                        $_SESSION[APPLICATION_IDENTIFIER]["courses"]["selected_curriculum_period"] = $PROCESSED["cperiod_id"];

                        if ($audience_result) {
                            $data = array();
                            $enrolment_total = 0;
                            foreach ($audience_result as $audience_object) {
                                $audience = $audience_object->toArray();

                                switch ($audience["audience_type"]) {
                                    case 'group_id':
                                        $group_object = new Models_Group();
                                        $group = Models_Group::fetchRowByID($audience["audience_value"]);
                                        $result = $group_object->getTotalGroupMembers($audience["audience_value"], 1);
                                        $total = $result['total_row'];
                                        $name = $group->getGroupName();
                                        $type = "group_id";
                                    break;
                                    case 'proxy_id':
                                        $student = Models_User::fetchRowByID($audience["audience_value"]);
                                        $name = $student->getFullname();
                                        $total = 1;
                                        $type = "proxy_id";
                                    break;
                                }

                                    $member_data = array(
                                    "name"           => $name,
                                    "total"         => $total,
                                    "type"         => $type,
                                    "type_value"         => $audience["audience_value"],
                                );
                                $enrolment_total += $total;
                                $data[] = $member_data;
                            }
                            echo json_encode(array("results" => count($enrolment_total), "data" => array("total_students" => $enrolment_total, "enrolment" => $data)));
                        } else {
                            echo json_encode(array("results" => "0", "data" => array($translate->_("No Groups Found."))));
                        }
                    break;

                    case "search-enrolment" :
                        if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                            $PROCESSED["search_value"] = "%". $tmp_input . "%";
                        } else {
                            $PROCESSED["search_value"] = "";
                        }

                        if (isset($request["cperiod_id"]) && $tmp_input = clean_input(strtolower($request["cperiod_id"]), array("trim", "int"))) {
                            $PROCESSED["cperiod_id"] = $tmp_input;
                        } else {
                            $PROCESSED["cperiod_id"] = 0;
                        }

                        if (isset($request["course_id"]) && $tmp_input = clean_input(strtolower($request["course_id"]), array("trim", "int"))) {
                            $PROCESSED["course_id"] = $tmp_input;
                        } else {
                            $PROCESSED["course_id"] = 0;
                        }

                        if (isset($request["cgroup_id"]) && $tmp_input = clean_input(strtolower($request["cgroup_id"]), array("trim", "int"))) {
                            $PROCESSED["cgroup_id"] = $tmp_input;
                        } else {
                            $PROCESSED["cgroup_id"] = 0;
                        }

                        if (isset($request["organisation_id"]) && $tmp_input = clean_input(strtolower($request["organisation_id"]), array("trim", "int"))) {
                            $PROCESSED["organisation_id"] = $tmp_input;
                        } else {
                            $PROCESSED["organisation_id"] = 0;
                        }

                        // get all the users enrolled in this course and cperiod
                        $audience_object = new Models_Course_Audience();
                        $members_results = $audience_object->getAllUsersByCourseIDCperiodIDOrganisationID($PROCESSED["course_id"],$PROCESSED["cperiod_id"], $PROCESSED["organisation_id"], $PROCESSED["search_value"]);

                        // get all the members of the current group
                        $current_member_list = array();
                        $results = Models_Course_Group_Audience::fetchAllByCGroupID($PROCESSED["cgroup_id"], 1);
                        if($results) {
                            foreach($results as $result_object) {
                                $result = $result_object->toArray();
                                if($proxy_id = (int) $result["proxy_id"]) {
                                    $current_member_list[] = $proxy_id;
                                }
                            }
                        }

                        $data = array();
                        if (!empty($members_results)) {
                            foreach ($members_results as $member) {
                                if (!in_array($member["proxy_id"], $current_member_list)) {
                                    $data[] = array("target_id" => $member["proxy_id"], "target_label" => $member["fullname"]);
                                }
                            }
                        }

                        if (count($data) > 0) {
                            echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => true));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No Search Results Found")));
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