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
} elseif (!$ENTRADA_ACL->amIAllowed("course", "read", false)) {
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
                    case "get-units-by-course-date":
                        if (isset($request["course"]) && $tmp_input = clean_input(strtolower($request["course"]), array("trim", "int"))) {
                            $PROCESSED["course"] = $tmp_input;
                        } else {
                            add_error("Missing course");
                        }

                        if (isset($request["date"]) && $tmp_input = clean_input(strtolower($request["date"]), array("trim", "notags"))) {
                            if (strtotime($tmp_input) !== false) {
                                $PROCESSED["date"] = strtotime($tmp_input);
                            } else {
                                add_error("Invalid date ");
                            }
                        } else {
                            add_error("Missing date ");
                        }

                        if (!$ERROR) {
                            try {
                                $curriculum_periods = Models_Curriculum_Period::fetchAllByDateCourseID($PROCESSED["date"], $PROCESSED["course"]);
                                if ($curriculum_periods) {
                                    $curriculum_period = $curriculum_periods[0];
                                    $units = Models_Course_Unit::fetchAllByCourseIDCperiodID($PROCESSED["course"], $curriculum_period->getCperiodID());
                                    $data = array();
                                    foreach ($units as $unit) {
                                        $data[] = array(
                                            "cunit_id" => $unit->getID(),
                                            "unit_title" => $unit->getUnitText(),
                                            "cperiod_id" => $unit->getCperiodID(),
                                            "course_id" => $unit->getCourseID(),
                                            "week_id" => $unit->getWeekID(),
                                        );
                                    }
                                    echo json_encode(array("status" => "success", "data" => $data));
                                } else {
                                    echo json_encode(array("status" => "success", "data" => array()));
                                }
                            } catch (Exception $e) {
                                echo json_encode(array("status" => "error", "msg" => $translate->_("Error fetching units.")));
                                error_log($e->getMessage());
                            }
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $ERRORSTR, "request" => $request));
                        }
                        break;
                    default:
                        echo json_encode(array("status" => "error", "msg" => $translate->_("Invalid GET method.")));
                        break;
                }
            break;
            case "POST" :
                switch ($method) {
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
