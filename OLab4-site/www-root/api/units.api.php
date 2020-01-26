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
 * @author Unit: Faculty of Medicine - Med IT
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 *
 */

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

if (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: ".ENTRADA_URL);
    exit;
} else {
    $request_method = strtoupper(clean_input($_SERVER["REQUEST_METHOD"], "alpha"));

    $request = ${"_" . $request_method};

    if ($ENTRADA_USER->getActiveRole() == "admin") {
        if (isset($request["proxy_id"]) && $tmp_input = clean_input($request["proxy_id"], "int")) {
            $PROCESSED["proxy_id"] = $tmp_input;
        } else {
            $PROCESSED["proxy_id"] = $ENTRADA_USER->getActiveID();
        }
    } else {
        $PROCESSED["proxy_id"] = $ENTRADA_USER->getActiveID();
    }

    switch ($request_method) {
        case "GET" :
            switch ($request["method"]) {
                case "get-by-cohort":
                    $UNIT_LIST = array();

                    if ($request["cohort_id"] > 0) {
                        $cohort_id = $request["cohort_id"];
                    } else {
                        $cohort_id = $ENTRADA_USER->getCohort();
                    }

                    $units = Models_Course_Unit::getByCohort($cohort_id);

                    if ($units) {
                        foreach ($units as $curriculum_type_name => $units_by_week) {
                            foreach ($units_by_week as $week_title => $units_by_course) {
                                if (count($units_by_course) == 1) {
                                    $course_code = key($units_by_course);
                                    $unit = current($units_by_course);

                                    if ($cohort_id == 0) {
                                        $UNIT_LIST[$unit->getID()] = $unit->curriculum_period_title . ' ' . $week_title;
                                    } else {
                                        $UNIT_LIST[$unit->getID()] = $week_title;
                                    }
                                } else {
                                    foreach ($units_by_course as $course_code => $unit) {
                                        if ($cohort_id == 0) {
                                            $unit_option_title = html_encode($unit->curriculum_period_title." ".$unit->getUnitText()) . (($unit->getUnitCode() == "") ? " (" . html_encode($course_code) . ")" : "");
                                        } else {
                                            $unit_option_title = html_encode($unit->getUnitText()) . (($unit->getUnitCode() == "") ? " (" . html_encode($course_code) . ")" : "");
                                        }

                                        $UNIT_LIST[$unit->getID()] = $unit_option_title;
                                    }
                                }
                            }
                        }
                    }

                    echo json_encode($UNIT_LIST);
                    break;
                default:
                    header("HTTP/1.0 501 Not Implemented");
                    echo json_encode(array("status" => "error", "data" => $request_method . " not supported"));
                    break;
            }
    }
}