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
 * API to handle interaction with adding members to groups
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Travis Obregon <travismobregon@gmail.com>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_GROUPS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("group", "update", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    ob_clear_open_buffers();

    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));

    $request = ${"_" . $request_method};

    switch ($request_method) {
        case "GET" :
            switch ($request["method"]) {
                case "get-groups" :
                    $data = array();

                    $groups = Models_System_Group::fetchAllByOrganisationID($ENTRADA_USER->getActiveOrganisation());
                    if ($groups) {
                        foreach ($groups as $group) {
                            $data[] = array("target_id" => $group["id"], "target_label" => $translate->_(ucfirst($group["group_name"])), "target_children" => 1);
                        }
                    }

                    if ($data) {
                        echo json_encode(array("status" => "success", "data" => $data));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No System Groups were found.")));
                    }
                    break;
                case "get-roles" :
                    $data = array();

                    if (isset($request["group_id"]) && $tmp_input = clean_input($request["group_id"], "int")) {
                        $PROCESSED["group_id"] = $tmp_input;
                    } else {
                        $PROCESSED["group_id"] = 0;
                    }

                    $roles = Models_System_Role::fetchAllByGroupID($PROCESSED["group_id"], $ENTRADA_USER->getActiveOrganisation(), 1, false);
                    if ($roles) {
                        foreach ($roles as $role) {
                            $data[] = array("target_id" => $role["id"], "target_label" => $translate->_(ucfirst($role["role_name"])), "target_children" => 1, "level_selectable" => false);
                        }
                    }

                    if ($data) {
                        echo json_encode(array("status" => "success", "data" => $data, "parent_id" => $PROCESSED["group_id"], "parent_name" => "Groups", "level_selectable" => false, "no_back_btn" => true));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Users found")));
                    }
                    break;

                case "get-role-members" :
                    $data = array();

                    if (isset($request["parent_id"]) && $tmp_input = clean_input(strtolower($request["parent_id"]), array("trim", "int"))) {
                        $PROCESSED["parent_id"] = $tmp_input;
                    } else {
                        $PROCESSED["parent_id"] = 0;
                    }

                    if (isset($PROCESSED["parent_id"]) && $PROCESSED["parent_id"] != 0) {
                        $role = Models_System_Role::fetchRowByID($PROCESSED["parent_id"]);
                        if ($role) {
                            $group = Models_System_Group::fetchRowByID($role->getGroupsID());
                            if ($group) {
                                $users = Models_User_Access::getGroupRoleMembers($ENTRADA_USER->getActiveOrganisation(),
                                    $group->getGroupName(), $role->getRoleName());
                                if ($users) {
                                    foreach ($users as $user) {
                                        $data[] = array(
                                            "target_id" => $user["id"],
                                            "target_label" => $user["firstname"] . " " . $user["lastname"],
                                            "role" => $translate->_(ucfirst($user["role"])),
                                            "email" => $user["email"],
                                            "parent_id" => 0
                                        );
                                    }
                                }
                            }
                        }
                    } else {
                        $roles = Models_System_Role::fetchAllByOrganisationID($ENTRADA_USER->getActiveOrganisation(), 1);
                        if ($roles) {
                            foreach ($roles as $role) {
                                $data[] = array("target_id" => $role["id"], "target_label" => $translate->_(ucfirst($role["role_name"])), "target_children" => 1, "level_selectable" => false, "no_back_btn" => true);
                            }
                        }
                    }

                    if ($data) {
                        echo json_encode(array("status" => "success", "data" => $data, "parent_id" => 0, "parent_name" => "0", "no_back_btn" => true));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Users found")));
                    }
                    break;
            }
        break;
    }
    exit;
}