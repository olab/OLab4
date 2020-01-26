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
 * API to handle interaction with learning object repository.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
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
		case "POST" :
            switch ($request["method"]) {
                default:
                    continue;
                break;
            }
        break;
        case "GET" :
            switch ($request["method"]) {
                case "get-objective-details" :
                    if ($request["objective_id"] && $tmp_input = clean_input($request["objective_id"], array("int"))) {
                        $PROCESSED["objective_id"] = $tmp_input;
                    }
                    
                    $objective = Models_Objective::fetchRow($PROCESSED["objective_id"])->toArray();
                    if ($objective) {
                        $output = array();
                        if (!empty($objective["objective_name"])) {
                            $output[] = "<h4>" . $objective["objective_name"] . "</h4>";
                        }
                        if (!empty($objective["objective_description"])) {
                            $output[] = "<p>" . $objective["objective_description"] . "</p>";
                        }
                        echo implode("", $output);
                    }
                break;
                case "get-curriculum-matrix-level" :
                    if ($request["objective_id"] && $tmp_input = clean_input($request["objective_id"], array("int"))) {
                        $PROCESSED["objective_id"] = $tmp_input;
                        $objective_set = fetch_objective_set_for_objective_id($PROCESSED["objective_id"]);
                        $_SESSION[APPLICATION_IDENTIFIER][$ENTRADA_USER->getActiveID()]["matrix"]["objective_id"] = $objective_set["objective_id"];
                    }    
                    
                    if (isset($request["depth"]) && $request["depth"] && $tmp_input = clean_input($request["depth"], array("int"))) {
                        $PROCESSED["depth"] = $tmp_input;
                    } else {
                        $PROCESSED["depth"] = 1;
                    }
                    
                    if (isset($PROCESSED["depth"])) {
                        $_SESSION[APPLICATION_IDENTIFIER][$ENTRADA_USER->getActiveID()]["matrix"]["depth"] = $PROCESSED["depth"];
                    }
                    
                    $curriculum_matrix = false;
                    if ($PROCESSED["objective_id"]) {
                        $curriculum_matrix = Entrada_Curriculum_Matrix::getCurriculumMatrixData($PROCESSED["objective_id"], $PROCESSED["depth"]);
                    }
                    if ($curriculum_matrix && !empty($curriculum_matrix["objectives"])) {
                        echo json_encode(array("status" => "success", "data" => $curriculum_matrix));
                    } else {
                        echo json_encode(array("status" => "success", "data" => "bottom_level"));
                    }
                break;
                case "get-objective-children" :
                    if ($request["objective_id"] && $tmp_input = clean_input($request["objective_id"], array("int"))) {
                        $PROCESSED["objective_id"] = $tmp_input;
                        $objective_set = fetch_objective_set_for_objective_id($PROCESSED["objective_id"]);
                        $_SESSION[APPLICATION_IDENTIFIER][$ENTRADA_USER->getActiveID()]["matrix"]["objective_id"] = $objective_set["objective_id"];
                    }    
                    
                    $PROCESSED["depth"] = 1;

                    if (isset($PROCESSED["depth"])) {
                        $_SESSION[APPLICATION_IDENTIFIER][$ENTRADA_USER->getActiveID()]["matrix"]["depth"] = $PROCESSED["depth"];
                    }
                    
                    $curriculum_matrix = false;
                    if ($PROCESSED["objective_id"]) {
                        $curriculum_matrix = Entrada_Curriculum_Matrix::getCurriculumMatrixData($PROCESSED["objective_id"], $PROCESSED["depth"]);
                    }
                    if ($curriculum_matrix && !empty($curriculum_matrix["objectives"])) {
                        echo json_encode(array("status" => "success", "data" => $curriculum_matrix));
                    } else {
                        echo json_encode(array("status" => "success", "data" => "bottom_level"));
                    }
                break;
                case "get-mapping" :
                    if ($request["objective_id"] && $tmp_input = clean_input($request["objective_id"], array("int"))) {
                        $PROCESSED["objective_id"] = $tmp_input;
                    }
                    
                    if ($request["course_id"] && $tmp_input = clean_input($request["course_id"], array("int"))) {
                        $PROCESSED["course_id"] = $tmp_input;
                    }
                    
                    if ($PROCESSED["objective_id"] && $PROCESSED["course_id"]) {
                        echo implode("", Entrada_Curriculum_Matrix::getMapping($PROCESSED["course_id"], $PROCESSED["objective_id"]));
                    }
                break;
                default :
                    continue;
                break;
            }
        break;
    }
}