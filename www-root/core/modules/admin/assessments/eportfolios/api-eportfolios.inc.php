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
 * API to handle interaction with form components
 *
 * @author Organisation: Washington State University
 * @author Unit: Elson S. Floyd College of Medicine
 * @author Developer: Sean Girard <sean.girard@queensu.ca>
 * @copyright Copyright 2017, Washington State University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EPORTFOLIOS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false) &&
    !(isset($_GET["assessment_id"]) && isset($_GET["proxy_id"]) && ($assessment_id = clean_input($_GET["assessment_id"], array("trim", "int"))) && ($proxy_id = clean_input($_GET["proxy_id"], array("trim", "int"))) && Models_Gradebook_Assessment_Graders::canGradeAssessment($proxy_id, $assessment_id))) {

    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

    ob_clear_open_buffers();


    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));

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
                    echo json_encode(array("status" => "error", "data" => $translate->_("Invalid POST method.")));
                    break;
            }
            break;
        case "GET" :
            switch ($request["method"]) {
                case "get-eportfolios" :
                    if (isset($request["course_id"]) && $tmp_input = clean_input(strtolower($request["course_id"]), array("trim", "int"))) {
                        $PROCESSED["course_id"] = $tmp_input;
                    } else {
                        $PROCESSED["course_id"] = null;
                    }

                    if (isset($request["cperiod_id"]) && $tmp_input = clean_input(strtolower($request["cperiod_id"]), array("trim", "int"))) {
                        $PROCESSED["cperiod_id"] = $tmp_input;
                    } else {
                        $PROCESSED["cperiod_id"] = null;
                    }

                    if ($PROCESSED["course_id"] && $PROCESSED["cperiod_id"]) {
                        $course_audience_model = new Models_Course_Audience();
                        $course_audience = $course_audience_model->fetchAllByCourseIDCperiodID($PROCESSED["course_id"], $PROCESSED["cperiod_id"]);

                        $data = [];
                        foreach ($course_audience as $audience) {
                            $portfolio = Models_Eportfolio::fetchRowByGroupID($audience->getAudienceValue());
                            if ( $portfolio ) {
                                $data[] = ['portfolio_id' => $portfolio->getID(),
                                            'portfolio_name' => $portfolio->getPortfolioName(),
                                            'portfolio_start_date' => date('Y-m-d', $portfolio->getStartDate()),
                                            'portfolio_finish_date' => date('Y-m-d', $portfolio->getFinishDate())];
                            }
                        }
                        echo json_encode(array("results" => count($data), "data" => $data));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("Course ID and/or Curriculum Period ID not supplied")));
                    }
                    break;
                default:
                    echo json_encode(array("status" => "error", "data" => $translate->_("Invalid GET method.")));
                    break;
            }
            break;
        default :
            echo json_encode(array("status" => "error", "data" => $translate->_("Invalid request method.")));
            break;
    }

    exit;

}
