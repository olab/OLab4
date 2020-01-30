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
 * API file for assessment tools feedbacks report
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jordan L <jl250@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
if((!defined("PARENT_INCLUDED")) || (!defined("IN_ASSESSMENTS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
    exit;
} else {
    ob_clear_open_buffers();

    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));

    $request = ${"_" . $request_method};



    function validateDateSingle ($date) {
        $date_format = DateTime::createFromFormat("Y-m-d", $date);
        return $date_format && $date_format->format("Y-m-d") === $date;
    }

    switch ($request_method) {
        case "POST" :
            switch ($request["method"]) {
                case "update-filters":
                    if (isset($request["limit"]) && $tmp_input = clean_input(strtolower($request["limit"]), array("int"))) {
                        $PROCESSED["limit"] = $tmp_input;
                    } else {
                        $PROCESSED["limit"] = null;
                    }

                    if (isset($request["offset"]) && $tmp_input = clean_input($request["offset"], array("int"))) {
                        $PROCESSED["offset"] = $tmp_input;
                    } else {
                        $PROCESSED["offset"] = 0;
                    }

                    if (isset($request["report-start-date"]) && $tmp_input = clean_input($request["report-start-date"], array("trim", "striptags"))) {
                        $PROCESSED["report-start-date"] = strtotime($tmp_input);
                    } else {
                        $PROCESSED["report-start-date"] = '';
                    }

                    if (isset($request["report-end-date"]) && $tmp_input = clean_input($request["report-end-date"], array("trim", "striptags"))) {
                        $PROCESSED["report-end-date"] = strtotime($tmp_input);
                    } else {
                        $PROCESSED["report-end-date"] = '';
                    }

                    if (isset($request["courses"]) && is_array($request["courses"])) {
                        $tmp_input = array_map(function($course) {
                            return clean_input($course, array("int"));
                        }, $request["courses"]);

                        $PROCESSED["courses"] = $tmp_input;
                    } else {
                        $PROCESSED["courses"] = '';
                    }

                    if (isset($request["tools"]) && is_array($request["tools"])) {
                        $tmp_input = array_map(function($tool) {
                            return clean_input($tool, array("int"));
                        }, $request["tools"]);

                        $PROCESSED["tools"] = $tmp_input;
                    } else {
                        $PROCESSED["tools"] = '';
                    }

                    $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["assessment_tools_feedback_report"]["courses"] = $PROCESSED["courses"];
                    $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["assessment_tools_feedback_report"]["tools"] = $PROCESSED["tools"];
                    $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["assessment_tools_feedback_report"]["start_date"] = $PROCESSED["report-start-date"];
                    $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["assessment_tools_feedback_report"]["end_date"] = $PROCESSED["report-end-date"];

                    echo json_encode(array("status" => "success"));

                    break;
            }

            break;
        case "GET" :
            switch ($request["method"]) {
                case "get-assessments-tools-feedbacks":
                    if (isset($request["limit"]) && $tmp_input = clean_input(strtolower($request["limit"]), array("int"))) {
                        $PROCESSED["limit"] = $tmp_input;
                    } else {
                        $PROCESSED["limit"] = null;
                    }

                    if (isset($request["offset"]) && $tmp_input = clean_input(strtolower($request["offset"]), array("int"))) {
                        $PROCESSED["offset"] = $tmp_input;
                    } else {
                        $PROCESSED["offset"] = 0;
                    }

                    $feedback_report = new Entrada_Utilities_Assessments_ToolsFeedbackReport(array(
                        "actor_proxy_id" => $ENTRADA_USER->getActiveID(),
                        "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()
                    ));
                    $admin = ($ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true) || $ENTRADA_USER->getActiveRole() == "admin");
                    if ($data = $feedback_report->getReportData($admin, $PROCESSED["offset"], $PROCESSED["limit"])) {
                        $total = $feedback_report->getReportDataCount($admin);
                        echo json_encode(array("status" => "success", "total" => $total, "data" => $data));
                    } else {
                        echo json_encode(array("status" => "success", "total" => 0, "data" => array()));
                    };

                    break;

                case "get-assessments-tools-feedbacks-pdf":
                    $feedback_report = new Entrada_Utilities_Assessments_ToolsFeedbackReport(array(
                        "actor_proxy_id" => $ENTRADA_USER->getActiveID(),
                        "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()
                    ));
                    $admin = ($ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true) || $ENTRADA_USER->getActiveRole() == "admin");
                    if ($data = $feedback_report->getReportData($admin)) {
                        $tbody = array();
                        $tbody[] = "<table class=\"table table-striped table-bordered\"><thead>";
                        $tbody[] = "<tr>";
                        $tbody[] = "<th>".$translate->_("Date")."</th>";
                        $tbody[] = "<th>".$translate->_("Assessor"). "</th>";
                        $tbody[] = "<th>".$translate->_("Tool")."</th>";
                        $tbody[] = "<th>".$translate->_("Feedback")."</th>";
                        $tbody[] = "</tr></thead>";
                        $tbody[] = "<tbody>";

                        foreach ($data as $line) {
                            $tbody[] = "<tr>";
                            $tbody[] = "<td style=\"padding: 15px 5px 15px 5px;\" width=\"15%\">".$line["date"]."</td>";
                            $tbody[] = "<td style=\"padding: 15px 5px 15px 5px;\" width=\"30%\">".$line["firstname"] . " " . $line["lastname"] . "<br />".$line["email"]. "</td>";
                            $tbody[] = "<td style=\"padding: 15px 5px 15px 5px;\" width=\"15%\">".$line["title"] ."</td>";
                            $tbody[] = "<td style=\"padding: 15px 5px 15px 5px;\" width=\"40%\">".$line["comments"]."</td>";
                            $tbody[] = "</tr>";
                        }
                        $tbody[] = "</tbody></table>";

                        $tbody = implode("\n", $tbody);

                        $report = new Entrada_Utilities_Assessments_HTMLForPDFGenerator();
                        $html = $report->generateAssessmentReportHTML($tbody);

                        if ($report->configure()) {
                            $report->send("feedback-report.pdf", $html);
                        }
                    } else {
                        echo json_encode(array("status" => "success", "total" => 0, "data" => array()));
                    };
                    break;

                case "get-user-courses" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = null;
                    }

                    $data = array();
                    $courses = Models_Course::getUserCourses($ENTRADA_USER->getActiveId(), $ENTRADA_USER->getActiveOrganisation(), $PROCESSED["search_value"]);

                    if (!empty($courses)) {
                        foreach ($courses as $course) {
                            $data[] = array("target_id" => $course->getID(), "target_label" => $course->getCourseName());
                        }
                    }

                    if (!empty($data)) {
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No courses were found.")));
                    }
                    break;

                case "get-user-tools":
                    $feedback_report = new Entrada_Utilities_Assessments_ToolsFeedbackReport(array(
                        "actor_proxy_id" => $ENTRADA_USER->getActiveID(),
                        "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()
                    ));

                    $admin = ($ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true) || $ENTRADA_USER->getActiveRole() == "admin");

                    if ($data = $feedback_report->getAssessmentTools($admin)) {
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No tools were found.")));
                    }
                    break;
            }
            break;
    }
    exit;
}