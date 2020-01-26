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
 * API to handle fetching an organisation's groups and roles.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Travis Obregon <travismobregon@gmail.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_USERS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
}

ob_clear_open_buffers();

$request_method = strtoupper(clean_input($_SERVER["REQUEST_METHOD"], "alpha"));

$request = ${"_" . $request_method};

switch ($request_method) {
    case "GET" :
        switch ($request["method"]) {
            case "get-organisation-groups" :
                if (isset($_GET["organisation_id"]) && $tmp_input = clean_input($_GET["organisation_id"], array("trim", "int"))) {
                    $organisation_id = $tmp_input;
                } else {
                    $organisation_id = 0;
                }

                $data = array();

                $query = "	SELECT g.`id`, g.`group_name`
                            FROM `" . AUTH_DATABASE . "`.`system_groups` g,
                                 `" . AUTH_DATABASE . "`.`organisations` o,
                                 `" . AUTH_DATABASE . "`.`system_group_organisation` gho
                            WHERE g.`visible`
                            AND o.`organisation_id` = gho.`organisation_id`
                            AND gho.`groups_id` = g.`id`
                            AND o.`organisation_id` = " . $db->qstr($organisation_id) . "
                            ORDER BY `group_name`";

                $groups = $db->GetAll($query);
                $user_group_role = $ENTRADA_USER->getActiveGroup() . ":" . $ENTRADA_USER->getActiveRole();
                if ($groups) {
                    foreach ($groups as $group) {
                        if (($group["group_name"] == "medtech" && $user_group_role == "medtech:admin") || $group["group_name"] != "medtech") {
                            $data[] = array("id" => $group["id"], "group_name" => ucfirst($group["group_name"]));
                        }
                    }
                }

                echo json_encode($data);
            break;
            case "get-organisation-roles" :
                if (isset($_GET["organisation_id"]) && $tmp_input = clean_input($_GET["organisation_id"], array("trim", "int"))) {
                    $organisation_id = $tmp_input;
                } else {
                    $organisation_id = 0;
                }

                if (isset($_GET["group_id"]) && $tmp_input = clean_input($_GET["group_id"], array("trim", "int"))) {
                    $group_id = $tmp_input;
                }

                $data = array();

                $query = "	SELECT r.`id`, r.`role_name`
                            FROM `".AUTH_DATABASE."`.`system_groups` g, 
                                 `".AUTH_DATABASE."`.`system_roles` r,
                                 `".AUTH_DATABASE."`.`organisations` o, 
                                 `".AUTH_DATABASE."`.`system_group_organisation` gho
                            WHERE g.id = r.groups_id
                            AND o.`organisation_id` = gho.`organisation_id`
                            AND gho.`groups_id` = g.`id`
                            AND o.`organisation_id` = " . $db->qstr($organisation_id) . "
                            AND g.`id` = " . $db->qstr($group_id) . "
                            ORDER BY g.`group_name`, r.`role_name`";

                $roles = $db->GetAll($query);

                if ($roles) {
                    // Reverse the order of role_names if they are numeric values (years).
                    if (is_numeric($roles[0]["role_name"])) {
                        usort($roles, function($a, $b) {
                            return $b["role_name"] - $a["role_name"];
                        });
                    }

                    foreach ($roles as $role) {
                        $data[] = array("id" => $role["id"], "role_name" => ucfirst($role["role_name"]));
                    }
                }

                echo json_encode($data);
            break;
        }
    break;
}
exit;
