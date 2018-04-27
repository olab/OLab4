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
 * @author Organisation: University of British Columbia
 * @author Unit: Faculty of Medicine
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
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
} elseif (!$ENTRADA_ACL->amIAllowed("unitcontent", "update", false)) {
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
    echo json_encode(array("status" => "error", "msg" => "Permission denied"));
    exit;
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
                    case "get-units" :
                        $column_check_array = array("order", "title");
                        $direction_check_array = array("asc", "desc");

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
                                $PROCESSED["sort_column"] = "order";
                            }
                        } else {
                            $PROCESSED["sort_column"] = "order";
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

                        $_SESSION[APPLICATION_IDENTIFIER]["courses"]["selected_curriculum_period"] = $PROCESSED["cperiod_id"];

                        try {
                            $units = Models_Course_Unit::getUnitsByCourseID($PROCESSED["course"], $PROCESSED["cperiod_id"], $PROCESSED["offset"], $PROCESSED["limit"], $PROCESSED["sort_column"], $PROCESSED["sort_direction"]);
                            $total_units = Models_Course_Unit::getTotalCourseUnits($PROCESSED["course"], $PROCESSED["cperiod_id"]);
                            if ($units) {
                                $data = array();
                                foreach ($units as $unit_array) {
                                    $unit = new Models_Course_Unit($unit_array);
                                    $unit_data = array(
                                        "course_id" => $unit->getCourseID(),
                                        "cunit_id" => $unit->getID(),
                                        "cperiod_id" => $unit->getCperiodID(),
                                        "unit_title" => $unit->getUnitText(),
                                    );
                                    $data[] = $unit_data;
                                }
                                echo json_encode(array("results" => count($units), "data" => array("total_units" => $total_units["total_rows"], "units" => $data)));
                            } else {
                                echo json_encode(array("results" => "0", "msg" => $translate->_("No Units Found.")));
                            }
                        } catch (Exception $e) {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("Error fetching units.")));
                            error_log($e->getMessage());
                        }
                        break;
                    default:
                        echo json_encode(array("status" => "error", "msg" => $translate->_("Invalid GET method.")));
                        break;
                }
            break;
            case "POST" :
                switch ($method) {
                    case "delete-units" :
                        $PROCESSED["delete_ids"] = array();
                        if (isset($request["delete_ids"]) && is_array($request["delete_ids"])) {
                            foreach ($request["delete_ids"] as $cunit_id) {
                                $tmp_input = clean_input($cunit_id, "int");
                                if ($tmp_input) {
                                    $PROCESSED["delete_ids"][] = $tmp_input;
                                }
                            }
                        }

                        $has_permission = true;
                        foreach ($PROCESSED["delete_ids"] as $delete_id) {
                            if (!$ENTRADA_ACL->amIAllowed(new CourseUnitResource($delete_id), "update", true)) {
                                $has_permission = false;
                            }
                        }

                        if ($has_permission) {
                            if (!empty($PROCESSED["delete_ids"])) {
                                try {
                                    $deleted_units = Models_Course_Unit::removeAllByIDs($PROCESSED["delete_ids"]);
                                    echo json_encode(array("status" => "success", "msg" => sprintf($translate->_("Successfully deleted %d Unit(s)."), $deleted_units), "unit_ids" => $PROCESSED["delete_ids"]));
                                } catch (Exception $e) {
                                    echo json_encode(array("status" => "error", "msg" => $translate->_("There was an error when attempting to delete the Units. Deleted no Unit(s).")));
                                    error_log($e->getMessage());
                                }
                            } else {
                                echo json_encode(array("status" => "error", "msg" => $translate->_("No Units were selected for deletion.")));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("You do not have permission to delete this Unit.")));
                        }
                    break;
                    case "assign-units" :
                        $PROCESSED["assign_ids"] = array();
                        if (isset($request["assign_ids"]) && is_array($request["assign_ids"])) {
                            foreach ($request["assign_ids"] as $cunit_id) {
                                $tmp_input = clean_input($cunit_id, "int");
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

                        if ($PROCESSED["cperiod_id"]) {
                            if (!empty($PROCESSED["assign_ids"])) {
                                $assigned_units = array();
                                $unassigned_units = array();
                                foreach ($PROCESSED["assign_ids"] as $cunit_id) {
                                    if ($ENTRADA_ACL->amIAllowed(new CourseUnitResource($cunit_id), "update", true)) {
                                        $unit = Models_Course_Unit::fetchRowByID($cunit_id);
                                        if ($unit) {
                                            if(!$unit->fromArray(array("cperiod_id" => $PROCESSED["cperiod_id"] ))->update()) {
                                                $unassigned_units[] = $cunit_id;
                                            } else {
                                                $assigned_units[] = $cunit_id;
                                            }
                                        } else {
                                            $unassigned_units[] = $cunit_id;
                                        }
                                    } else {
                                        $unassigned_units[] = $cunit_id;
                                    }
                                }
                                if (empty($unassigned_units)) {
                                    echo json_encode(array("status" => "success", "msg" => sprintf($translate->_("Successfully assigned %d Unit(s)."), count($assigned_units)), "unit_ids" => $assigned_units));
                                } else {
                                    echo json_encode(array("status" => "error", "msg" => sprintf($translate->_("There was an error when attempting to assign the Units to a curriculum period. Assigned %d Unit(s)."), count($assigned_units)), "unit_ids" => $assigned_units));
                                }
                            } else {
                                echo json_encode(array("status" => "error", "msg" => $translate->_("No Units were selected for assignment.")));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("No Curriculum Period was selected for assignment.")));
                        }
                    break;
                    default:
                        echo json_encode(array("status" => "error", "msg" => $translate->_("Invalid POST method.")));
                        break;
                }
            break;
            default:
                echo json_encode(array("status" => "error", "msg" => $translate->_("Invalid method.")));
                break;
        }
    } else {
        echo json_encode(array("status" => "error", "msg" => $ERRORSTR));
    }
    exit;
}
/* vim: set expandtab: */
