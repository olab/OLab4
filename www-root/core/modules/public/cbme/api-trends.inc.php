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
 * This API file returns Assessor records in the format:
 *
 * $assessments = array(
 * 	0 => array(
 * 		"id" 		     => 16,
 * 		"title" 	     => "Winter 2014 Assessment",
 * 		"link"           => "http://www.onefortyfiveapp.com/login?assessmentid=16",
 * 		"startDate"      => 1361517660,
 * 		"endDate"        => 1393053660,
 * 		"gracePeriodEnd" => 1395472860,
 * 		"program"        => array(
 * 			"id"   => 122,
 * 			"name" => "MEDS244 - Clinical & Communication Skills 3"
 * 		),
 * 		"status"         => PRECEPTOR_ASSESSEVAL_STATUS_CLOSED,
 * 		"dataSource"     => PRECEPTOR_ASSESSEVAL_DATASOURCE_ONEFORTYFIVE,
 * 		"targets"         => array(
 * 			array(
 * 				"id"   => 153,
 * 				"name" => "David Erikson"
 * 			),
 * 			array(
 * 				"id"   => 525,
 * 				"name" => "Arthur Hardy"
 * 			),
 * 			array(
 * 			    "id"   => 15,
 * 			    "name" => "Andrea D. McMillan"
 * 			)
 * 		)
 * 	)
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Don Zuiker <don.zuiker@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
 */

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

if((!defined("PARENT_INCLUDED"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed('assessments', 'read', false)) {
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
    exit;
} else {
    ob_clear_open_buffers();
    $request = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));
    $request_var = "_".$request;
    $method = clean_input(${$request_var}["method"], array("trim", "striptags"));

    switch ($request) {
        case "POST":
            switch ($method) {
                case "set-trends-tab-preference":
                    if (isset(${$request_var}["tab"]) && $tmp_input = clean_input(${$request_var}["tab"], array("trim", "striptags"))) {
                        $tab = $tmp_input;
                    } else {
                        add_error($translate->_("No tab selected."));
                        echo json_encode(array("status" => "error", "data" => $translate->_("No tab specified")));
                        exit;
                    }

                    $PREFERENCES = preferences_load("cbme_assessments");
                    $_SESSION[APPLICATION_IDENTIFIER]["cbme_assessments"]["trends_selected_tab"] = $tab;
                    preferences_update("cbme_assessments", $PREFERENCES);
                    echo json_encode(array("status" => "success"));

                    break;
                default:
                    echo json_encode(array("status" => "error", "data" => array("No Assessments Available.")));
                    break;
            }
            break;
        case "GET":
            switch ($method) {
                case "get-scale-data":
                    if (isset(${$request_var}["proxy_id"]) && $tmp_input = clean_input(${$request_var}["proxy_id"], array("trim", "int"))) {
                        $PROCESSED["proxy_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No learner identifier provided"));
                    }

                    if (!$ERROR) {
                        $PREFERENCES = preferences_load("cbme_assessments");
                        $labels_array = array();

                        $course_utility = new Models_CBME_Course();
                        $cperiods = $course_utility->getCurrentCPeriodIDs($ENTRADA_USER->getActiveOrganisation());
                        $courses = $course_utility->getActorCourses(
                            $ENTRADA_USER->getActiveGroup(),
                            $ENTRADA_USER->getActiveRole(),
                            $ENTRADA_USER->getActiveOrganisation(),
                            $ENTRADA_USER->getActiveId(),
                            $PROCESSED["proxy_id"],
                            $cperiods
                        );

                        if (isset(${$request_var}["course_id"]) && $tmp_input = clean_input(${$request_var}["course_id"], array("trim", "int"))) {
                            $PROCESSED["course_id"] = $tmp_input;
                        } else {
                            add_error($translate->_("No course provided."));
                        }

                        if (isset(${$request_var}["scale_id"]) && $tmp_input = clean_input(${$request_var}["scale_id"], array("trim", "int"))) {
                            $PROCESSED["scale_id"] = $tmp_input;
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No scale id provided.")));
                            exit();
                        }

                        if (isset(${$request_var}["limit"]) && $tmp_input = clean_input(${$request_var}["limit"], array("trim", "int"))) {
                            $PROCESSED["limit"] = $tmp_input;
                        } else {
                            $PROCESSED["limit"] = 0;
                        }

                        if (isset(${$request_var}["offset"]) && $tmp_input = clean_input(${$request_var}["offset"], array("trim", "int"))) {
                            $PROCESSED["offset"] = $tmp_input;
                        } else {
                            $PROCESSED["offset"] = 0;
                        }

                        if (isset(${$request_var}["epa_id"]) && $tmp_input = clean_input(${$request_var}["epa_id"], array("trim", "int"))) {
                            $PROCESSED["epa_id"] = $tmp_input;
                        } else {
                            $PROCESSED["epa_id"] = 0;
                        }

                        $filters = array();
                        parse_str(${$request_var}["filters"], $filters);

                        // Overide the scale
                        $filters["rating_scale_id"] = $PROCESSED["scale_id"];
                        if ($PROCESSED["epa_id"]) {
                            $filters["epas"] = array($PROCESSED["epa_id"]);
                        }
                        /**
                         * Instantiate the CBME visualization abstraction layer
                         */
                        $cbme_progress_api = new Entrada_CBME_Visualization(array(
                            "actor_proxy_id" => $PROCESSED["proxy_id"],
                            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                            "datasource_type" => "progress",
                            "filters" => $filters,
                            "limit_dataset" => array(
                                "rating_scales_charts"
                            ),
                            "query_offset" => $PROCESSED["offset"],
                            "query_limit" => $PROCESSED["limit"],
                            "courses" => $courses
                        ));

                        /**
                         * Fetch the dataset that will be used by the view
                         */
                        if (!$dataset = $cbme_progress_api->fetchData()) {
                            echo json_encode(array("status" => "error", "data" => "The query returned an empty dataset."));
                            exit();
                        }

                        if (!$chart_data = reset($dataset["rating_scales_charts"])) {
                            echo json_encode(array("status" => "error", "data" => "No rating scale charts data returned."));
                            exit();
                        }

                        if (!$chart_data = reset($chart_data["charts"])) {
                            echo json_encode(array("status" => "error", "data" => "No rating scale charts data returned2."));
                            exit();
                        }

                        foreach($chart_data["labels"] as $index => $label) {
                            $labels_array[] = sprintf($translate->_("%s <br>Encounter date %s"), $label, $chart_data["chart_dates"][$index]);
                        }
                        $chart_data["xval_label"] = $labels_array;
                        $chart_data["xaxis_label"] = sprintf($translate->_("Assessments (%s to %s)"), $chart_data["chart_dates"][0], $chart_data["chart_dates"][sizeof($chart_data["chart_dates"]) - 1]);

                        echo json_encode(array("status" => "success", "data" => $chart_data));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }

                    break;
            }
            break;
    }

    exit;
}