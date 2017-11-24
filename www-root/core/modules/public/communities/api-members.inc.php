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
 * API to handle interaction with adding members to communities
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Travis Obregon <travismobregon@gmail.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_COMMUNITIES"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
}

ob_clear_open_buffers();

$request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));

$request = ${"_" . $request_method};

switch ($request_method) {
    case "GET" :
        switch ($request["method"]) {
            case "get-users-by-group" :
                if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                    $PROCESSED["search_value"] = $tmp_input;
                } else {
                    $PROCESSED["search_value"] = "";
                }

                if (isset($request["group"]) && $tmp_input = clean_input(strtolower($request["group"]), array("trim", "striptags"))) {
                    $PROCESSED["group"] = $tmp_input;
                } else {
                    $PROCESSED["group"] = "";
                }

                if (isset($request["excluded_target_ids"]) && $tmp_input = clean_input(strtolower($request["excluded_target_ids"]), array("trim", "striptags"))) {
                    $PROCESSED["excluded_target_ids"] = $tmp_input;
                } else {
                    $PROCESSED["excluded_target_ids"] = 0;
                }

                $users = User::fetchUsersByGroups($PROCESSED["search_value"], $PROCESSED["group"], null, null, $PROCESSED["excluded_target_ids"]);

                $data = array();

                if ($users) {
                    foreach ($users as $user) {
                        $data[] = array("target_id" => $user["proxy_id"], "target_label" => $user["firstname"] . " " . $user["lastname"], "lastname" => $user["lastname"], "role" => $translate->_(ucfirst($user["role"])), "email" => $user["email"]);
                    }
                }

                if ($data) {
                    echo json_encode(array("status" => "success", "data" => $data));
                } else {
                    echo json_encode(array("status" => "error", "data" => $translate->_("No Users found")));
                }
            break;
            case "get-residents" :
                if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                    $PROCESSED["search_value"] = $tmp_input;
                } else {
                    $PROCESSED["search_value"] = "";
                }

                if (isset($request["excluded_target_ids"]) && $tmp_input = clean_input(strtolower($request["excluded_target_ids"]), array("trim", "striptags"))) {
                    $PROCESSED["excluded_target_ids"] = $tmp_input;
                } else {
                    $PROCESSED["excluded_target_ids"] = 0;
                }

                $users = User::fetchAllResidents($PROCESSED["search_value"], $PROCESSED["excluded_target_ids"]);

                $data = array();

                if ($users) {
                    foreach ($users as $user) {
                        $data[] = array("target_id" => $user["proxy_id"], "target_label" => $user["firstname"] . " " . $user["lastname"], "lastname" => $user["lastname"], "role" => $translate->_(ucfirst($user["role"])), "email" => $user["email"]);
                    }
                }

                if ($data) {
                    echo json_encode(array("status" => "success", "data" => $data));
                } else {
                    echo json_encode(array("status" => "error", "data" => $translate->_("No Residents found")));
                }
            break;
            case "group-get-cohorts" :
                if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                    $PROCESSED["search_value"] = $tmp_input;
                } else {
                    $PROCESSED["search_value"] = "";
                }

                if (isset($request["included_group_ids"]) && $tmp_input = clean_input(strtolower($request["included_group_ids"]), array("trim", "striptags"))) {
                    $PROCESSED["included_group_ids"] = $tmp_input;
                } else {
                    $PROCESSED["included_group_ids"] = 0;
                }

                $group_ids = explode(",", $PROCESSED["included_group_ids"]);

                if ($group_ids) {
                    $data = array();

                    foreach ($group_ids as $group_id) {
                        $group = Models_Group::fetchRowByID($group_id);

                        if ($group) {
                            $data[] = array("target_id" => $group->getID(), "target_parent" => "0", "target_label" => $group->getGroupName(), "target_children" => "1");
                        }
                    }

                    if ($data) {
                        echo json_encode(array("status" => "success", "data" => $data, "parent_id" => "0", "parent_name" => "0", "level_selectable" => false));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Students found")));
                    }
                } else {
                    echo json_encode(array("status" => "error", "data" => $translate->_("No Students found")));
                }
            break;
            case "group-get-students" :
                if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                    $PROCESSED["search_value"] = $tmp_input;
                } else {
                    $PROCESSED["search_value"] = "";
                }

                if (isset($request["parent_id"]) && $tmp_input = clean_input(strtolower($request["parent_id"]), array("trim", "int"))) {
                    $PROCESSED["parent_id"] = $tmp_input;
                } else {
                    $PROCESSED["parent_id"] = "0";
                }

                if (isset($request["included_group_ids"]) && $tmp_input = clean_input(strtolower($request["included_group_ids"]), array("trim", "striptags"))) {
                    $PROCESSED["included_group_ids"] = $tmp_input;
                } else {
                    $PROCESSED["included_group_ids"] = 0;
                }

                if (isset($request["excluded_target_ids"]) && $tmp_input = clean_input(strtolower($request["excluded_target_ids"]), array("trim", "striptags"))) {
                    $PROCESSED["excluded_target_ids"] = $tmp_input;
                } else {
                    $PROCESSED["excluded_target_ids"] = 0;
                }

                if ($PROCESSED["parent_id"] != "0") {
                    $group = Models_Group::fetchRowByID($PROCESSED["parent_id"]);
                    $users = false;

                    if ($group) {
                        $users = Models_Group_Member::getUsersByGroupIDWithoutAppID($PROCESSED["parent_id"], $PROCESSED["search_value"], 1, $PROCESSED["excluded_target_ids"]);
                    }

                    if ($users) {
                        $data = array();

                        foreach ($users as $user) {
                            $data[] = array("target_id" => $user->getProxyId(), "target_parent" => strval($PROCESSED["parent_id"]), "target_label" => $user->getFirstname() . " " . $user->getLastname(), "target_children" => "0", "lastname" => $user->getLastname(), "role" => $translate->_("Learner"), "email" => $user->getEmail());
                        }

                        echo json_encode(array("status" => "success", "data" => $data, "parent_id" => "0", "parent_name" => $group->getGroupName()));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Students found"), "parent_id" => "0", "parent_name" => $group->getGroupName()));
                    }
                } else {
                    $group_ids = explode(",", $PROCESSED["included_group_ids"]);

                    if ($group_ids) {
                        $data = array();

                        foreach ($group_ids as $group_id) {
                            $group = Models_Group::fetchRowByID($group_id);

                            $data[] = array("target_id" => $group->getID(), "target_parent" => "0", "target_label" => $group->getGroupName(), "target_children" => "1");
                        }

                        echo json_encode(array("status" => "success", "data" => $data, "parent_id" => "0", "parent_name" => "0", "level_selectable" => false));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Students found")));
                    }
                }
            break;
            case "get-students" :
                if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                    $PROCESSED["search_value"] = $tmp_input;
                } else {
                    $PROCESSED["search_value"] = "";
                }

                if (isset($request["organisation_id"]) && $tmp_input = clean_input(strtolower($request["organisation_id"]), array("trim", "int"))) {
                    $PROCESSED["organisation_id"] = $tmp_input;
                } else {
                    $PROCESSED["organisation_id"] = 0;
                }

                if (isset($request["excluded_target_ids"]) && $tmp_input = clean_input(strtolower($request["excluded_target_ids"]), array("trim", "striptags"))) {
                    $PROCESSED["excluded_target_ids"] = $tmp_input;
                } else {
                    $PROCESSED["excluded_target_ids"] = 0;
                }

                if ($PROCESSED["organisation_id"] != "0") {
                    $users = Models_Organisation::fetchOrganisationUsersWithoutAppID($PROCESSED["search_value"], $PROCESSED["organisation_id"], "student", $PROCESSED["excluded_target_ids"]);
                    $data = array();

                    if ($users) {
                        foreach ($users as $user) {
                            $data[] = array("target_id" => $user["proxy_id"], "target_label" => $user["firstname"] . " " . $user["lastname"]);
                        }
                    }
                    if ($data) {
                        echo json_encode(array("status" => "success", "data" => $data));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Users found")));
                    }
                } else {
                    echo json_encode(array("status" => "error", "data" => $translate->_("No Organisations found")));
                }
            break;
        }
    break;
}
exit;
