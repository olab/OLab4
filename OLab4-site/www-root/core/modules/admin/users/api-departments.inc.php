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
 * API to handle fetching departments for an organisation.
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
            case "get-organisation-departments" :
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
                
                $departments = Models_Department::fetchAllByOrganisationID($PROCESSED["organisation_id"], $PROCESSED["search_value"]);

                $data = array();

                if ($departments) {
                    foreach ($departments as $department) {
                        $entity_title = $department["entity_title"] ? " (" . $department["entity_title"] . ")" : "";

                        $data[] = array("target_id" => $department["department_id"], "target_label" => $department["department_title"] . $entity_title);
                    }
                }

                if ($data) {
                    echo json_encode(array("status" => "success", "data" => $data));
                } else {
                    echo json_encode(array("status" => "error", "data" => $translate->_("No Departments Found")));
                }
            break;
            case "get-departments-by-search-value" :
                if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                    $PROCESSED["search_value"] = $tmp_input;
                } else {
                    $PROCESSED["search_value"] = "";
                }

                $departments = Models_Department::fetchAllRecordsBySearchValue($PROCESSED["search_value"]);

                $data = array();

                if ($departments) {
                    foreach ($departments as $department) {
                        $data[] = array("target_id" => $department->getID(), "target_label" => $department->getDepartmentTitle());
                    }
                }

                if ($data) {
                    echo json_encode(array("status" => "success", "data" => $data));
                } else {
                    echo json_encode(array("status" => "error", "data" => $translate->_("No Departments Found")));
                }

            break;
            case "get-organisation-filter-name" :
                if (isset($request["org_id"]) && $tmp_input = clean_input(strtolower($request["org_id"]), array("trim", "int"))) {
                    $PROCESSED["org_id"] = $tmp_input;
                } else {
                    $PROCESSED["org_id"] = 0;
                }

                $organisation_filter_name = "";
                $organisation = Models_Organisation::fetchRowByID($PROCESSED["org_id"]);

                if ($organisation) {
                    $organisation_filter_name = str_replace(" ", "_", $organisation->getOrganisationTitle());
                }

                echo json_encode(array("organisation_filter_name" => $organisation_filter_name));
            break;
            case "get-organisation-title-by-dept-id" :
                if (isset($request["dept_id"]) && $tmp_input = clean_input(strtolower($request["dept_id"]), array("trim", "int"))) {
                    $PROCESSED["dept_id"] = $tmp_input;
                } else {
                    $PROCESSED["dept_id"] = 0;
                }
                
                $organisation_title = Models_Department::fetchOrganisationTitleByDepartmentID($PROCESSED["dept_id"]) ?: "";
                $department = Models_Department::fetchRowByID($PROCESSED["dept_id"]);
                echo json_encode(array("organisation_title" => $organisation_title, "department_name" => ($department ? $department->getDepartmentTitle() : "")));
            break;
        }
    break;
}
exit;
