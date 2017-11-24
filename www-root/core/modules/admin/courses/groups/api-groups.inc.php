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
                    case "get-groups" :

                        $column_check_array = array ("name");
                        $direction_check_array = array ("asc", "desc");


                        if (isset($request["course"]) && $tmp_input = clean_input(strtolower($request["course"]), array("trim", "int"))) {
                            $PROCESSED["course"] = $tmp_input;
                        } else {
                            $PROCESSED["course"] = 0;
                        }

                        if (isset($request["cperiod_id"]) && $tmp_input = clean_input(strtolower($request["cperiod_id"]), array("trim", "int"))) {
                            $PROCESSED["cperiod_id"] = $tmp_input;
                        } else {
                            $PROCESSED["cperiod_id"] = 0;
                        }

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
                                $PROCESSED["sort_column"] = "name";
                            }
                        } else {
                            $PROCESSED["sort_column"] = "name";
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

                        $groups_object = new Models_Course_Group();
                        
                        $_SESSION[APPLICATION_IDENTIFIER]["courses"]["selected_curriculum_period"] = $PROCESSED["cperiod_id"];

                        if ($ENTRADA_ACL->amIAllowed('group', 'update', false) && $PROCESSED["course"]) {
                            $groups = $groups_object->getGroupsByCourseID($PROCESSED["course"],$PROCESSED["cperiod_id"],$PROCESSED["search_term"],$PROCESSED["offset"], $PROCESSED["limit"], $PROCESSED["sort_column"], $PROCESSED["sort_direction"]);
                            $total_groups = $groups_object->getTotalCourseGroups($PROCESSED["course"],$PROCESSED["cperiod_id"],$PROCESSED["search_term"]);
                        }

                        if ($groups) {
                            $data = array();
                            foreach ($groups as $group) {

                                $group_data = array(
                                    "course_id"           => $group["course_id"],
                                    "group_id"           => $group["cgroup_id"],
                                    "cperiod_id"           => $group["cperiod_id"],
                                    "group_name"         => $group["group_name"],
                                    "members"         => $group["members"],
                                    "active"         => $group["active"],
                                );

                                $data[] = $group_data;
                            }
                            echo json_encode(array("results" => count($groups), "data" => array("total_groups" => $total_groups["total_rows"], "groups" => $data)));
                        } else {
                            echo json_encode(array("results" => "0", "data" => array($translate->_("No Groups Found."))));
                        }
                        preferences_update("courses");
                        break;
                    default:
                        echo json_encode(array("status" => "error", "data" => $translate->_("Invalid GET method.")));
                    break;
                }
            break;    
            case "POST" :
                switch ($method) {
                    case "delete-groups" :
                    $PROCESSED["delete_ids"] = array();
                    if (isset($request["delete_ids"]) && is_array($request["delete_ids"])) {
                        foreach ($request["delete_ids"] as $group_id) {
                            $tmp_input = clean_input($group_id, "int");
                            if ($tmp_input) {
                                $PROCESSED["delete_ids"][] = $tmp_input;
                            }
                        }
                    }

                    if (!empty($PROCESSED["delete_ids"])) {
                        $deleted_groups = array();
                        $group_object = new Models_Course_Group();
                        $group_audience_object = new Models_Course_Group_Audience();

                        foreach ($PROCESSED["delete_ids"] as $group_id) {
                            $group = Models_Course_Group::fetchRowByID($group_id);

                            if ($group) {
                                $group_audience = Models_Course_Group_Audience::fetchAllByCGroupID($group_id);
                                foreach($group_audience as $user) {
                                    if (!$user->fromArray(array("active" => "0" ))->update()) {
                                        add_error($translate->_("Unable to delete a Group Audience"));
                                    }
                                }

                                if(!$group->fromArray(array("active" => "0" ))->update()) {
                                    add_error($translate->_("Unable to delete a Group"));
                                } else {
                                    $deleted_groups[] = $group_id;
                                }

                            } else {
                                add_error($translate->_("Unable to delete a Group"));
                            }
                        }
                        if (!$ERROR) {
                            echo json_encode(array("status" => "success", "msg" => sprintf($translate->_("Successfully deleted %d Group(s)."), count($deleted_groups)), "group_ids" => $deleted_groups));
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("There was an error when attempting to delete a Group.")));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "msg" => $translate->_("No Groups were selected for deletion.")));
                    }
                    break;
                    case "assign-groups" :
                        $PROCESSED["assign_ids"] = array();
                        if (isset($request["assign_ids"]) && is_array($request["assign_ids"])) {
                            foreach ($request["assign_ids"] as $group_id) {
                                $tmp_input = clean_input($group_id, "int");
                                if ($tmp_input) {
                                    $PROCESSED["assign_ids"][] = $tmp_input;
                                }
                            }
                        }

                        if (isset($request["cperiod_id"]) && $tmp_input = clean_input($request["cperiod_id"], array("trim", "int"))) {
                            $PROCESSED["cperiod_id"] = $tmp_input;
                        } else {
                            $PROCESSED["cperiod_id"] = 0;
                        }


                        if (!empty($PROCESSED["assign_ids"]) && $PROCESSED["cperiod_id"]) {
                            $assigned_groups = array();
                            $group_object = new Models_Course_Group();

                            foreach ($PROCESSED["assign_ids"] as $group_id) {
                                $group = Models_Course_Group::fetchRowByID($group_id);

                                if ($group) {
                                    if(!$group->fromArray(array("cperiod_id" => $PROCESSED["cperiod_id"] ))->update()) {
                                        add_error($translate->_("Unable to delete a Group"));
                                    } else {
                                        $deleted_groups[] = $group_id;
                                    }

                                } else {
                                    add_error($translate->_("Unable to delete a Group"));
                                }
                            }
                            if (!$ERROR) {
                                echo json_encode(array("status" => "success", "msg" => sprintf($translate->_("Successfully deleted %d Group(s)."), count($deleted_groups)), "group_ids" => $deleted_groups));
                            } else {
                                echo json_encode(array("status" => "error", "msg" => $translate->_("There was an error when attempting to delete a Group.")));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("No Groups were selected for deletion.")));
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