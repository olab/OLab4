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
 * API to gather assessment and evaluation reporting information
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

    function stats_standard_deviation(array $a, $sample = false) {
        $n = count($a);

        if ($n === 0) {
            return 0;
        }

        if ($sample && $n === 1) {
            return 0;
        }

        $mean = array_sum($a) / $n;
        $carry = 0.0;

        foreach ($a as $val) {
            $d = ((double) $val) - $mean;
            $carry += $d * $d;
        }

        if ($sample) {
            --$n;
        }

        return sqrt($carry / $n);
    }

    function datesOverlap ($start_one, $end_one, $start_two, $end_two) {
        if ($start_one <= $end_two && $end_one >= $start_two) {
            return false;
        }
        return true;
    }

    function validateDateSingle ($date) {
        $date_format = DateTime::createFromFormat("Y-m-d", $date);
        return $date_format && $date_format->format("Y-m-d") === $date;
    }

    switch ($request_method) {
        case "POST" :
            switch ($request["method"]) {
                case "set-date-range":
                    if (isset($request["start_date"]) && !is_null($request["start_date"]) && $tmp_input = clean_input(strtotime($request["start_date"]), "int")) {
                        if ($tmp_input) {
                            $PROCESSED["start_date"] = $tmp_input;
                        } else {
                            $PROCESSED["start_date"] = null;
                        }
                    } else {
                        $PROCESSED["start_date"] = null;
                    }

                    if (isset($request["end_date"]) && !is_null($request["end_date"]) && $tmp_input = clean_input(strtotime($request["end_date"]  . " 23:59:59"), "int")) {
                        if ($tmp_input) {
                            $PROCESSED["end_date"] = $tmp_input;
                        } else {
                            $PROCESSED["end_date"] = null;
                        }
                    } else {
                        $PROCESSED["end_date"] = null;
                    }

                    if (isset($request["current_page"]) && $tmp_input = clean_input(strtolower($request["current_page"]), array("trim", "striptags"))) {
                        $PROCESSED["current_page"] = $tmp_input;
                    } else {
                        $PROCESSED["current_page"] = null;
                    }

                    if (!is_null($PROCESSED["current_page"])) {
                        $assessments_base = new Entrada_Utilities_Assessments_Base();
                        $_SESSION[APPLICATION_IDENTIFIER][$PROCESSED["current_page"]]["evaluation"]["start_date"] = $PROCESSED["start_date"];
                        $_SESSION[APPLICATION_IDENTIFIER][$PROCESSED["current_page"]]["evaluation"]["end_date"] = $PROCESSED["end_date"];
                        $assessments_base->updateAssessmentPreferences($PROCESSED["current_page"]);
                    }

                    if (!is_null($PROCESSED["start_date"])) {
                        if (validateDateSingle(date("Y-m-d", $PROCESSED["start_date"]))) {
                            $PROCESSED["start_date"] = date("Y-m-d", $PROCESSED["start_date"]);
                        }
                    }

                    if (!is_null($PROCESSED["end_date"])) {
                        if (validateDateSingle(date("Y-m-d", $PROCESSED["end_date"]))) {
                            $PROCESSED["end_date"] = date("Y-m-d", $PROCESSED["end_date"]);
                        }
                    }

                    $curriculum_periods = array();
                    $curriculum_types = Models_Curriculum_Type::fetchAllByOrg($ENTRADA_USER->getActiveOrganisation());
                    if ($curriculum_types) {
                        foreach ($curriculum_types as $curriculum_type) {
                            $cperiods = Models_Curriculum_Period::fetchAllByCurriculumType($curriculum_type->getID());
                            if ($cperiods) {
                                foreach ($cperiods as $curriculum_period) {
                                    $curriculum_periods[$curriculum_period->getCperiodID()] = array("cperiod_start_date" => date("Y-m-d", $curriculum_period->getStartDate()), "cperiod_end_date" => date("Y-m-d", $curriculum_period->getFinishDate()));
                                }
                            }
                        }
                    }

                    if (!is_null($PROCESSED["start_date"]) || !is_null($PROCESSED["end_date"])) {
                        foreach ($curriculum_periods as $key => $curriculum_period) {
                            if (!is_null($PROCESSED["start_date"]) && !is_null($PROCESSED["end_date"])) {
                                if (datesOverlap($PROCESSED["start_date"], $PROCESSED["end_date"], $curriculum_period["cperiod_start_date"], $curriculum_period["cperiod_end_date"])) {
                                    unset($curriculum_periods[$key]);
                                }
                            } else {
                                if (!is_null($PROCESSED["start_date"])) {
                                    if ($PROCESSED["start_date"] > $curriculum_period["cperiod_start_date"] && $PROCESSED["start_date"] > $curriculum_period["cperiod_end_date"]) {
                                        unset($curriculum_periods[$key]);
                                    }
                                } else {
                                    if ($PROCESSED["end_date"] < $curriculum_period["cperiod_start_date"] && $PROCESSED["end_date"] < $curriculum_period["cperiod_end_date"]) {
                                        unset($curriculum_periods[$key]);
                                    }
                                }
                            }
                        }
                    }

                    if (!empty($curriculum_periods)) {
                        echo json_encode(array("status" => "success", "data" => $curriculum_periods));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No curriculum periods were found.")));
                    }
                    break;
                case "generate-pdf-bulk":
                    if (isset($_POST["proxy_id"])) {
                        if (isset($_POST["start_date"]) && !is_null($_POST["start_date"]) && $tmp_input = clean_input(strtotime($_POST["start_date"]), "int")) {
                            if ($tmp_input) {
                                $PROCESSED["start_date"] = $tmp_input;
                            } else {
                                $PROCESSED["start_date"] = null;
                            }
                        } else {
                            $PROCESSED["start_date"] = null;
                        }

                        if (isset($_POST["end_date"]) && !is_null($_POST["end_date"]) && $tmp_input = clean_input(strtotime($_POST["end_date"]  . " 23:59:59"), "int")) {
                            if ($tmp_input) {
                                $PROCESSED["end_date"] = $tmp_input;
                            } else {
                                $PROCESSED["end_date"] = null;
                            }
                        } else {
                            $PROCESSED["end_date"] = null;
                        }

                        if (isset($_GET["current-location"]) && $tmp_input = clean_input(strtolower($_GET["current-location"]), array("trim", "striptags"))) {
                            $PROCESSED["current_location"] = $tmp_input;
                        } else {
                            $PROCESSED["current_location"] = "";
                        }

                        $PROCESSED["description"] = null;
                        if (isset($_POST["description"]) && $tmp_input = clean_input($_POST["description"], array("trim", "striptags"))) {
                            $PROCESSED["description"] = $tmp_input;
                        }

                        $task_ctr = 0;
                        $last_task_for_user = array();
                        foreach ($_POST["proxy_id"] as $proxy_id) {
                            $tasks = Entrada_Utilities_Assessments_DeprecatedAssessmentTask::getAssessmentProgressOnUser($proxy_id, $ENTRADA_USER->getActiveOrganisation(), "reports", "approved", array(), null, $PROCESSED["start_date"], $PROCESSED["end_date"]);

                            if (!empty($tasks["complete"])) {
                                $ctr = 0;
                                foreach ($tasks["complete"] as $task) {
                                    $PROCESSED["task_data"][$task_ctr]["target_id"] = $task->getTargetID();
                                    $PROCESSED["task_data"][$task_ctr]["dassessment_id"] = $task->getDassessmentID();
                                    $PROCESSED["task_data"][$task_ctr]["assessor_value"] = $task->getAssessorValue();
                                    $PROCESSED["task_data"][$task_ctr]["assessor_name"] = $task->getAssessor();
                                    $PROCESSED["task_data"][$task_ctr]["target_name"] = $task->getTargetNamesComplete(false);
                                    $PROCESSED["task_data"][$task_ctr]["aprogress_id"] = $task->getProgressID();
                                    $PROCESSED["task_data"][$task_ctr++]["adistribution_id"] = $task->getDistributionID();
                                    $last_task_for_user[] = count($tasks["complete"]) == ++$ctr;
                                }
                            } else {
                                add_error($translate->_("No tasks complete on target."));
                            }
                        }

                        if (!empty($PROCESSED["task_data"])) {
                            $pdf_generator = new Entrada_Utilities_Assessments_PDFDownload();
                            $pdf_generator->prepareDownloadMultiple($PROCESSED, $last_task_for_user);
                        } else {
                            add_error($translate->_("No target data provided."));
                            header('Location: ' . $_SERVER['HTTP_REFERER'] . "&error=true");
                        }
                    } else {
                        add_error($translate->_("No target data provided."));
                        header('Location: ' . $_SERVER['HTTP_REFERER'] . "&error=true");
                    }
                    break;
                case "generate-pdf-for-tasks-bulk":
                    if (isset($_POST["proxy_id"])) {
                        if (isset($_POST["start_date"]) && !is_null($_POST["start_date"]) && $tmp_input = clean_input(strtotime($_POST["start_date"]), "int")) {
                            if ($tmp_input) {
                                $PROCESSED["start_date"] = $tmp_input;
                            } else {
                                $PROCESSED["start_date"] = null;
                            }
                        } else {
                            $PROCESSED["start_date"] = null;
                        }

                        if (isset($_POST["end_date"]) && !is_null($_POST["end_date"]) && $tmp_input = clean_input(strtotime($_POST["end_date"]  . " 23:59:59"), "int")) {
                            if ($tmp_input) {
                                $PROCESSED["end_date"] = $tmp_input;
                            } else {
                                $PROCESSED["end_date"] = null;
                            }
                        } else {
                            $PROCESSED["end_date"] = null;
                        }

                        if (isset($_GET["current-location"]) && $tmp_input = clean_input(strtolower($_GET["current-location"]), array("trim", "striptags"))) {
                            $PROCESSED["current_location"] = $tmp_input;
                        } else {
                            $PROCESSED["current_location"] = "";
                        }

                        $PROCESSED["description"] = null;
                        if (isset($_POST["description"]) && $tmp_input = clean_input($_POST["description"], array("trim", "striptags"))) {
                            $PROCESSED["description"] = $tmp_input;
                        }

                        @set_time_limit(0);
                        @ini_set("memory_limit", "2048M");

                        $ctr = 0;
                        foreach ($_POST["proxy_id"] as $proxy_id) {
                            $tasks = Entrada_Utilities_Assessments_DeprecatedAssessmentTask::getAssessmentProgressOnUser($proxy_id, $ENTRADA_USER->getActiveOrganisation(), "reports", "approved", array(), null, $PROCESSED["start_date"], $PROCESSED["end_date"]);

                            if (!empty($tasks["complete"])) {
                                foreach ($tasks["complete"] as $task) {
                                    $PROCESSED["task_data"][$ctr]["target_id"] = $task->getTargetID();
                                    $PROCESSED["task_data"][$ctr]["dassessment_id"] = $task->getDassessmentID();
                                    $PROCESSED["task_data"][$ctr]["assessor_value"] = $task->getAssessorValue();
                                    $PROCESSED["task_data"][$ctr]["assessor_name"] = $task->getAssessor();
                                    $PROCESSED["task_data"][$ctr]["target_name"] = $task->getTargetNamesComplete(false);
                                    $PROCESSED["task_data"][$ctr]["aprogress_id"] = $task->getProgressID();
                                    $PROCESSED["task_data"][$ctr]["adistribution_id"] = $task->getDistributionID();
                                    $ctr++;
                                }
                            } else {
                                add_error($translate->_("No tasks complete on target."));
                            }
                        }

                        if (!empty($PROCESSED["task_data"])) {
                            $pdf_generator = new Entrada_Utilities_Assessments_PDFDownload();
                            $pdf_generator->prepareDownloadSingle($PROCESSED);
                        } else {
                            add_error($translate->_("No target data provided."));
                            header('Location: ' . $_SERVER['HTTP_REFERER'] . "&error=true");
                        }
                    } else {
                        add_error($translate->_("No target data provided."));
                        header('Location: ' . $_SERVER['HTTP_REFERER'] . "&error=true");
                    }
                    break;
                case "generate-pdf-bulk-reports":
                    $PROCESSED["proxy_id_list"] = array();
                    if (isset($request["proxy_id"]) && is_array($request["proxy_id"]) && !empty($request["proxy_id"])) {
                        $PROCESSED["proxy_id_list"] = array_map(
                            function ($val) {
                                return clean_input($val, array("trim", "int"));
                            },
                            $request["proxy_id"]
                        );
                    }

                    $PROCESSED["course_id"] = 0;
                    if (isset($_POST["course_id"]) && $tmp_input = clean_input($_POST["course_id"], array("trim", "int"))) {
                        $PROCESSED["course_id"] = $tmp_input;
                    }

                    $PROCESSED["course_id_override"] = 0;
                    if (isset($_POST["course_id_override"]) && $tmp_input = clean_input($_POST["course_id_override"], array("trim", "int"))) {
                        $PROCESSED["course_id_override"] = $tmp_input;
                    }

                    $PROCESSED["distribution_id_override"] = 0;
                    if (isset($_POST["distribution_id_override"]) && $tmp_input = clean_input($_POST["distribution_id_override"], array("trim", "int"))) {
                        $PROCESSED["distribution_id_override"] = $tmp_input;
                    }

                    if (!$PROCESSED["proxy_id_list"] && !$PROCESSED["course_id"]) {
                        add_error($translate->_("No target data provided."));
                    }

                    if (isset($_POST["form_id"]) && $tmp_input = clean_input($_POST["form_id"], array("trim", "int"))) {
                        $PROCESSED["form_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No form specified."));
                    }

                    if (isset($_POST["start_date"]) && !is_null($_POST["start_date"]) && $tmp_input = clean_input(strtotime($_POST["start_date"]), "int")) {
                        if ($tmp_input) {
                            $PROCESSED["start_date"] = $tmp_input;
                        } else {
                            $PROCESSED["start_date"] = null;
                        }
                    } else {
                        $PROCESSED["start_date"] = null;
                    }

                    if (isset($_POST["end_date"]) && !is_null($_POST["end_date"]) && $tmp_input = clean_input(strtotime($_POST["end_date"]  . " 23:59:59"), "int")) {
                        if ($tmp_input) {
                            $PROCESSED["end_date"] = $tmp_input;
                        } else {
                            $PROCESSED["end_date"] = null;
                        }
                    } else {
                        $PROCESSED["end_date"] = null;
                    }

                    $PROCESSED["curriculum_periods"] = array();

                    if (isset($_POST["cperiod_ids"]) && is_array($_POST["cperiod_ids"]) && !empty($_POST["cperiod_ids"])) {

                        $PROCESSED["curriculum_periods"] = array_map(
                            function ($val) {
                                return clean_input($val, array("trim", "int"));
                            },
                            $_POST["cperiod_ids"]
                        );
                    }

                    $PROCESSED["include_comments"] = false;
                    if (isset($_POST["include_comments"])) {
                        $PROCESSED["include_comments"] = ($_POST["include_comments"] === "true") ? true : false;
                    }

                    $PROCESSED["include_commenter_id"] = false;
                    if ($PROCESSED["include_comments"]) {
                        if (isset($_POST["include_commenter_id"])) {
                            $PROCESSED["include_commenter_id"] = ($_POST["include_commenter_id"] === "true") ? true : false;
                        }
                    }

                    $PROCESSED["description"] = null;
                    if (isset($_POST["description"]) && $tmp_input = clean_input($_POST["description"], array("trim", "striptags"))) {
                        $PROCESSED["description"] = $tmp_input;
                    }

                    $PROCESSED["include_statistics"] = false;
                    if (isset($_POST["include_statistics"])) {
                        $PROCESSED["include_statistics"] = ($_POST["include_statistics"] === "true") ? true : false;
                    }

                    $PROCESSED["include_positivity"] = false;
                    if ($PROCESSED["include_statistics"]) {
                        if (isset($_POST["include_positivity"])) {
                            $PROCESSED["include_positivity"] = ($_POST["include_positivity"] === "true") ? true : false;
                        }
                    }

                    $report_html = array();
                    $user_name = array();
                    $form_title = null;

                    if (!$ERROR) {
                        $full_target_list_details = "";
                        if ($PROCESSED["distribution_id_override"]) {
                            $distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($PROCESSED["distribution_id_override"]);
                            if ($distribution) {
                                $full_target_list_details .= "<li>{$distribution->getTitle()}</li>";
                            }
                        }
                        $full_target_list_details .= "<li>" . $translate->_("Report Start Date: ") . (is_null($PROCESSED["start_date"]) ? $translate->_("Not Set") : date("Y-m-d", $PROCESSED["start_date"])) . " " . $translate->_("Report End Date: ") . (is_null($PROCESSED["end_date"]) ? $translate->_("Not Set") : date("Y-m-d", $PROCESSED["end_date"])) . "</li>";

                        $form = Models_Assessments_Form::fetchRowByID($PROCESSED["form_id"]);
                        if ($form) {
                            $form_title = $form->getTitle();
                        }

                        if (!$PROCESSED["course_id"]) {
                            foreach ($PROCESSED["proxy_id_list"] as $proxy_id) {
                                $user = Models_User::fetchRowByID($proxy_id);

                                if ($user) {
                                    $user_name[] = $user->getFullname(false);
                                    $construction = array(
                                        "organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                                        "target_value" => $proxy_id,
                                        "target_type" => "proxy_id",
                                        "form_id" => $PROCESSED["form_id"],
                                        "cperiod_id" => ($PROCESSED["start_date"] || $PROCESSED["end_date"]) ? null : $PROCESSED["curriculum_periods"],
                                        "start_date" => $PROCESSED["start_date"],
                                        "end_date" => $PROCESSED["end_date"],
                                        "course_id" => ($PROCESSED["course_id_override"] ? array($PROCESSED["course_id_override"]) : Models_Course::getActiveUserCoursesIDList())
                                    );
                                    if ($PROCESSED["distribution_id_override"]) {
                                        $construction["adistribution_id"] = $PROCESSED["distribution_id_override"];
                                    }

                                    $reporting_utility = new Entrada_Utilities_Assessments_Reports($construction);
                                    $report_data = $reporting_utility->generateReport();

                                    $header_view = new Views_Assessments_Reports_Header(array("class" => "space-below medium"));
                                    $report_header_html = $header_view->render(
                                        array(
                                            "target_name" => $user->getFullname(false),
                                            "form_name" => $form_title,
                                            "enable_pdf_button" => false,
                                            "list_info" => $full_target_list_details,
                                            "description" => $PROCESSED["description"]
                                        ),
                                        false
                                    );

                                    $report_view = new Views_Assessments_Reports_AssessmentReport(array("class" => "space-above space-below medium clearfix"));
                                    $report_body_html = $report_view->render(
                                        array(
                                            "report_data" => $report_data,
                                            "strip_comments" => !$PROCESSED["include_comments"],
                                            "include_commenter_id" => $PROCESSED["include_commenter_id"],
                                            "is_evaluation" => true,
                                            "additional_statistics" => $PROCESSED["include_statistics"],
                                            "include_positivity" => $PROCESSED["include_positivity"]
                                        ),
                                        false
                                    );

                                    $report_html[] = $report_header_html . $report_body_html;
                                }
                            }
                        } else {
                            $construction = array(
                                "target_value" => $PROCESSED["course_id"],
                                "target_type" => "course_id",
                                "form_id" => $PROCESSED["form_id"],
                                "start_date" => $PROCESSED["start_date"],
                                "end_date" => $PROCESSED["end_date"]
                            );

                            $reporting_utility = new Entrada_Utilities_Assessments_Reports($construction);
                            $report_data = $reporting_utility->generateReport();

                            $course = Models_Course::fetchRowByID($PROCESSED["course_id"]);
                            $user_name[] = $course->getCourseName();

                            $header_view = new Views_Assessments_Reports_Header(array("class" => "space-below medium"));
                            $report_header_html = $header_view->render(
                                array(
                                    "target_name" => $course->getCourseName(),
                                    "form_name" => $form_title,
                                    "enable_pdf_button" => false,
                                    "list_info" => $full_target_list_details,
                                    "description" => $PROCESSED["description"]
                                ),
                                false
                            );

                            $report_view = new Views_Assessments_Reports_AssessmentReport(array("class" => "space-above space-below medium clearfix"));
                            $report_body_html = $report_view->render(
                                array(
                                    "report_data" => $report_data,
                                    "strip_comments" => !$PROCESSED["include_comments"],
                                    "include_commenter_id" => $PROCESSED["include_commenter_id"],
                                    "is_evaluation" => true,
                                    "additional_statistics" => $PROCESSED["include_statistics"],
                                    "include_positivity" => $PROCESSED["include_positivity"]
                                ),
                                false
                            );

                            $report_html[] = $report_header_html . $report_body_html;
                        }
                    }

                    if (!empty($report_html)) {
                        $pdf_generator = new Entrada_Utilities_Assessments_PDFDownload();
                        $pdf_generator->prepareDownloadMultipleReports($report_html, $form_title, $user_name, $PROCESSED["include_comments"], $PROCESSED["include_commenter_id"]);
                    } else {
                        add_error($translate->_("No target data provided."));
                        display_error();
                        header('Location: ' . $_SERVER['HTTP_REFERER'] . "&error=true");
                    }
                break;
                case "generate-pdf-completion-report":
                    $PROCESSED["proxy_id_list"] = array();
                    if (isset($request["proxy_id"]) && is_array($request["proxy_id"]) && !empty($request["proxy_id"])) {
                        $PROCESSED["proxy_id_list"] = array_map(
                            function ($val) {
                                return clean_input($val, array("trim", "int"));
                            },
                            $request["proxy_id"]
                        );
                    }

                    if (empty($PROCESSED["proxy_id_list"])) {
                        add_error($translate->_("No target data provided."));
                    }

                    if (isset($_POST["start_date"]) && !is_null($_POST["start_date"]) && $tmp_input = clean_input(strtotime($_POST["start_date"]), "int")) {
                        if ($tmp_input) {
                            $PROCESSED["start_date"] = $tmp_input;
                        } else {
                            $PROCESSED["start_date"] = null;
                        }
                    } else {
                        $PROCESSED["start_date"] = null;
                    }

                    if (isset($_POST["end_date"]) && !is_null($_POST["end_date"]) && $tmp_input = clean_input(strtotime($_POST["end_date"]  . " 23:59:59"), "int")) {
                        if ($tmp_input) {
                            $PROCESSED["end_date"] = $tmp_input;
                        } else {
                            $PROCESSED["end_date"] = null;
                        }
                    } else {
                        $PROCESSED["end_date"] = null;
                    }

                    $PROCESSED["include_average_delivery_date"] = false;
                    if (isset($_POST["include_average_delivery_date"])) {
                        $PROCESSED["include_average_delivery_date"] = ($_POST["include_average_delivery_date"] === "true") ? true : false;
                    }

                    $PROCESSED["description"] = null;
                    if (isset($_POST["description"]) && $tmp_input = clean_input($_POST["description"], array("trim", "striptags"))) {
                        $PROCESSED["description"] = $tmp_input;
                    }

                    $data = array();

                    $overall_average_completion = 0;
                    $overall_average_completion_time = 0;
                    $overall_average_completion_ctr = 0;
                    $overall_rotation_average_completion = 0;
                    $overall_rotation_average_completion_time = 0;
                    $overall_rotation_average_completion_ctr = 0;

                    if (!$ERROR) {
                        $date_details = "<div class=\"form-heading\">" . $translate->_("Report Start Date: ") . (is_null($PROCESSED["start_date"]) ? $translate->_("Not Set") : date("Y-m-d", $PROCESSED["start_date"])) . "<br>" . $translate->_("Report End Date: ") . (is_null($PROCESSED["end_date"]) ? $translate->_("Not Set") : date("Y-m-d", $PROCESSED["end_date"])) . "</div>";
                        $course_contact_model = new Models_Assessments_Distribution_CourseContact();

                        foreach ($PROCESSED["proxy_id_list"] as $distribution_course_contact_id) {
                            $course_contact = $course_contact_model->fetchRowByID($distribution_course_contact_id);
                            if ($course_contact) {
                                $entrada_base = new Entrada_Assessments_Base();
                                $user = $entrada_base->getUserByType($course_contact->getAssessorValue(), $course_contact->getAssessorType());

                                if ($user) {
                                    $all_tasks = Entrada_Utilities_Assessments_DeprecatedAssessmentTask::getAllTasksByAssessorIDAssessorTypeTaskType($course_contact->getAssessorValue(), $course_contact->getAssessorType(), "assessment", $PROCESSED["start_date"], $PROCESSED["end_date"], false, false, true);
                                    $completion_ctr = $completion_time = $average_completion_time = 0;
                                    $rotation_completion_ctr = $rotation_completion_time = $rotation_average_completion_time = 0;
                                    $all_tasks_based_off_schedule = true;

                                    if ($all_tasks) {
                                        foreach ($all_tasks as $task) {
                                            if ($task["progress_value"] == "complete" && $task["delivery_date"] > 0 && $task["progress_updated_date"] > 0) {
                                                $completion_ctr++;
                                                $start_date = new DateTime();
                                                $end_date = new DateTime();
                                                $start_date->setTimestamp($task["delivery_date"]);
                                                $end_date->setTimestamp($task["progress_updated_date"]);
                                                $date_interval = $start_date->diff($end_date);
                                                $completion_time += $date_interval->format("%a");

                                                if ($task["rotation_end_date"] > 0) {
                                                    $rotation_completion_ctr++;
                                                    $start_date = new DateTime();
                                                    $end_date = new DateTime();
                                                    $start_date->setTimestamp($task["rotation_end_date"]);
                                                    $end_date->setTimestamp($task["progress_updated_date"]);
                                                    $date_interval = $start_date->diff($end_date);
                                                    $rotation_completion_time += $date_interval->format("%a");
                                                } else {
                                                    $all_tasks_based_off_schedule = false;
                                                }
                                            }
                                        }

                                        if ($completion_ctr > 0 && $completion_time > 0) {
                                            $overall_average_completion_ctr++;
                                            $average_completion_time = $completion_time / $completion_ctr;
                                            $overall_average_completion_time += $average_completion_time;
                                        }

                                        if ($rotation_completion_ctr > 0 && $rotation_completion_time > 0) {
                                            $overall_rotation_average_completion_ctr++;
                                            $rotation_average_completion_time = $rotation_completion_time / $rotation_completion_ctr;
                                            $overall_rotation_average_completion_time += $rotation_average_completion_time;
                                        }

                                        $data[] = array(
                                            "proxy_id" => $course_contact->getAssessorValue(),
                                            "user_name" => $user->getFirstname() . " " . $user->getLastname(),
                                            "total_delivered_assessments" => count($all_tasks),
                                            "completed_assessments" => $completion_ctr,
                                            "all_tasks_based_off_schedule" => $all_tasks_based_off_schedule,
                                            "average_completion_time" => $average_completion_time < 1 ? $translate->_("N/A") : round($average_completion_time) . $translate->_(" days"),
                                            "rotation_average_completion_time" => $rotation_average_completion_time < 1 ? $translate->_("N/A") : round($rotation_average_completion_time) . $translate->_(" days")
                                        );
                                    }
                                }
                            }
                        }

                        $overall_average_completion = $overall_average_completion_time > 0 ? round(($overall_average_completion_time / $overall_average_completion_ctr)) . $translate->_(" days") : "N/A";
                        $overall_rotation_average_completion = $overall_rotation_average_completion_time > 0 ? round(($overall_rotation_average_completion_time / $overall_rotation_average_completion_ctr)) . $translate->_(" days") : "N/A";
                    }

                    if (!empty($data)) {
                        $pdf_generator = new Entrada_Utilities_Assessments_PDFDownload();
                        $pdf_generator->prepareDownloadSingleCompletionReport($data, $date_details, $PROCESSED["include_average_delivery_date"], $PROCESSED["description"], $overall_average_completion, $overall_rotation_average_completion);
                    } else {
                        add_error($translate->_("No target data provided."));
                        display_error();
                        header('Location: ' . $_SERVER['HTTP_REFERER'] . "&error=true");
                    }
                    break;
                case "generate-pdf-leave-by-block-report":
                    $PROCESSED["proxy_id_list"] = array();
                    if (isset($request["proxy_id"]) && is_array($request["proxy_id"]) && !empty($request["proxy_id"])) {
                        $PROCESSED["proxy_id_list"] = array_map(
                            function ($val) {
                                return clean_input($val, array("trim", "int"));
                            },
                            $request["proxy_id"]
                        );
                    }

                    $PROCESSED["schedule_id_list"] = array();
                    if (isset($request["schedule_id"]) && is_array($request["schedule_id"]) && !empty($request["schedule_id"])) {
                        $PROCESSED["schedule_id_list"] = array_map(
                            function ($val) {
                                return clean_input($val, array("trim", "int"));
                            },
                            $request["schedule_id"]
                        );
                    }

                    if (empty($PROCESSED["proxy_id_list"])) {
                        add_error($translate->_("No target data provided."));
                    }

                    if (empty($PROCESSED["schedule_id_list"])) {
                        add_error($translate->_("No block data provided."));
                    }

                    $PROCESSED["description"] = null;
                    if (isset($_POST["description"]) && $tmp_input = clean_input($_POST["description"], array("trim", "striptags"))) {
                        $PROCESSED["description"] = $tmp_input;
                    }

                    $data = array();

                    if (!$ERROR) {
                        foreach ($PROCESSED["schedule_id_list"] as $schedule_id) {
                            $schedule = Models_Schedule::fetchRowByID($schedule_id);

                            if ($schedule) {
                                $leave_start_list = $leave_end_list = array();
                                $data[$schedule_id] = array(
                                    "schedule_start" => $schedule->getStartDate(),
                                    "schedule_end" => $schedule->getEndDate(),
                                    "schedule_title" => $schedule->getTitle()
                                );

                                foreach ($PROCESSED["proxy_id_list"] as $proxy_id) {
                                    $user = Models_User::fetchRowByID($proxy_id);

                                    if ($user) {
                                        $user_leave_list = Models_Leave_Tracking::fetchAllByProxyID($proxy_id);

                                        if ($user_leave_list) {
                                            foreach ($user_leave_list as $user_leave) {
                                                $leave_type = Models_Leave_Type::fetchRowByID($user_leave->getTypeID());

                                                if ($leave_type) {
                                                    if ($user_leave->getStartDate() <= $schedule->getEndDate() && $schedule->getStartDate() <= $user_leave->getEndDate()) {
                                                        $leave_start_list[] = $user_leave->getStartDate();
                                                        $leave_end_list[] = $user_leave->getEndDate();

                                                        $data[$schedule_id]["proxy_list"][] = array(
                                                            "proxy_id" => $proxy_id,
                                                            "user_name" => $user->getFirstname() . " " . $user->getLastname(),
                                                            "leave_start" => $user_leave->getStartDate(),
                                                            "leave_end" => $user_leave->getEndDate(),
                                                            "leave_title" => $leave_type->getTypeValue()
                                                        );
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }

                                if (!empty($leave_start_list) && !empty($leave_end_list)) {
                                    array_multisort($data[$schedule_id]["proxy_list"], SORT_NUMERIC, $leave_start_list, SORT_NUMERIC, $leave_end_list);
                                }
                            }
                        }
                    }

                    if (!empty($data)) {
                        $pdf_generator = new Entrada_Utilities_Assessments_PDFDownload();
                        $pdf_generator->prepareDownloadSingleLeaveByBlockReport($data, $PROCESSED["description"]);
                    } else {
                        add_error($translate->_("No data provided."));
                        display_error();
                        header('Location: ' . $_SERVER['HTTP_REFERER'] . "&error=true");
                    }
                    break;

                case "generate-pdf-rotation-leave-report" :
                    $specified_target_ids = array();
                    $target_names = array();
                    $target_header = "";

                    if (isset($request["target_id"])) {
                        if (is_array($request["target_id"])) {
                            $specified_target_ids = array_map(
                                function ($val) {
                                    return clean_input($val, array("trim", "int"));
                                },
                                $request["target_id"]
                            );
                        }
                    } else {
                        $validated_inputs = false;
                    }


                    if (isset($request["previous_page"]) && $tmp_input = clean_input(strtolower($request["previous_page"]), array("trim", "striptags"))) {
                        $PROCESSED["previous_page"] = $tmp_input;
                    } else {
                        $validated_inputs = false;
                    }

                    if (isset($request["start-date"]) && $tmp_input = clean_input($request["start-date"], array("nows", "notags"))) {
                        $date_format = DateTime::createFromFormat("Y-m-d", $tmp_input);
                        if ($date_format && $date_format->format("Y-m-d") === $tmp_input) {
                            $PROCESSED["start-date"] = strtotime($tmp_input);
                        } else {
                            $PROCESSED["start-date"] = null;
                        }
                    } else {
                        $PROCESSED["start-date"] = null;
                    }

                    if (isset($request["end-date"]) && $tmp_input = clean_input($request["end-date"], array("nows", "notags"))) {
                        $date_format = DateTime::createFromFormat("Y-m-d", $tmp_input);
                        if ($date_format && $date_format->format("Y-m-d") === $tmp_input) {
                            $PROCESSED["end-date"] = strtotime($tmp_input);
                        } else {
                            $PROCESSED["end-date"] = null;
                        }
                    } else {
                        $PROCESSED["end-date"] = null;
                    }

                    if (isset($request["comments"]) && ($tmp_input = clean_input($request["comments"], array("trim", "int")))) {
                        $PROCESSED["comments"] = $tmp_input;
                    } else {
                        $PROCESSED["comments"] = "";
                    }

                    $PROCESSED["description"] = null;
                    if (isset($request["description"]) && $tmp_input = clean_input(strtolower($request["description"]), array("trim", "striptags"))) {
                        $PROCESSED["description"] = $tmp_input;
                    }

                    if(!$ERROR) {


                        foreach ($specified_target_ids as $target_id) {
                            if ($target_id) {
                                $user = Models_User::fetchRowByID($target_id);
                                if ($user) {
                                    $target_names[$target_id] = $user->getFullname(false);
                                    $target_header .= $target_names[$target_id] . ", ";

                                }
                                $leave[$target_id] = Models_Leave_Tracking::fetchAllByProxyID($target_id, $PROCESSED["start-date"], $PROCESSED["end-date"]);
                            }
                        }
                    }
                    $target_header = rtrim($target_header, ", ");

                    if (!empty($leave)) {
                        $pdf_generator = new Entrada_Utilities_Assessments_PDFDownload();
                        $pdf_generator->prepareDownloadSingleRotationLeaveReport($leave, $PROCESSED["description"], $target_header, $target_names, $PROCESSED["start-date"], $PROCESSED["end-date"], $PROCESSED["comments"]);
                    } else {
                        add_error($translate->_("No data provided."));
                        display_error();
                        header('Location: ' . $_SERVER['HTTP_REFERER'] . "&error=true");
                    }
                    break;

                case "set-cperiod-preferences":
                    $PROCESSED["cperiod"] = null;

                    if (isset($request["cperiod"]) && !is_null($request["cperiod"]) && $tmp_input = clean_input($request["cperiod"], "int")) {
                        if ($tmp_input) {
                            $PROCESSED["cperiod"] = $tmp_input;
                        }
                    }

                    $PROCESSED["current_page"] = null;
                    if (isset($request["current_page"]) && $tmp_input = clean_input(strtolower($request["current_page"]), array("trim", "striptags"))) {
                        $PROCESSED["current_page"] = $tmp_input;
                    }

                    if (!is_null($PROCESSED["current_page"])) {
                        $assessments_base = new Entrada_Utilities_Assessments_Base();
                        $_SESSION[APPLICATION_IDENTIFIER][$PROCESSED["current_page"]]["cperiod"] = $PROCESSED["cperiod"];
                        $assessments_base->updateAssessmentPreferences($PROCESSED["current_page"]);
                    }
                    break;
            }
            break;
        case "GET" :
            switch ($request["method"]) {
                case "download-rotation-evaluation-csv" :
                    $PROCESSED["schedule_id_list"] = array();
                    if (isset($request["schedule_id"])) {
                        $temp_list = explode(",", $request["schedule_id"]);

                        foreach ($temp_list as $schedule_id) {
                            if ($tmp_input = clean_input($schedule_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["schedule_id_list"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    $PROCESSED["form_id"] = null;
                    if (isset($request["form_id"]) && $tmp_input = clean_input($request["form_id"], array("trim", "int"))) {
                        $PROCESSED["form_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No form specified."));
                    }

                    $PROCESSED["curriculum_periods"] = array();
                    if (isset($request["cperiod_id"])) {
                        $temp_list = explode(",", $request["cperiod_id"]);

                        foreach ($temp_list as $cperiod_id) {
                            if ($tmp_input = clean_input($cperiod_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["curriculum_periods"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    if (empty($PROCESSED["curriculum_periods"])) {
                        add_error($translate->_("No curriculum period(s) specified."));
                    }

                    if (!$ERROR) {
                        $output = array();
                        $form = Models_Assessments_Form::fetchRowByID($PROCESSED["form_id"]);

                        if ($form) {
                            $afelement_id_list = $rubric_info = array();
                            $header = array($translate->_("Name"), $translate->_("N"));
                            $form_elements = Models_Assessments_Form_Element::fetchAllByFormIDRubricIDNotNull($form->getID());

                            if ($form_elements) {
                                foreach ($form_elements as $form_element) {
                                    $item = Models_Assessments_Item::fetchRowByID($form_element->getElementID());
                                    if ($item) {
                                        $afelement_id_list[] = $form_element->getAfelementID();
                                        $header[] = $item->getItemText();

                                        $item_responses = Models_Assessments_Item_Response::fetchAllRecordsByItemID($item->getID());
                                        if ($item_responses) {
                                            foreach ($item_responses as $item_response) {
                                                $response_descriptor = Models_Assessments_Response_Descriptor::fetchRowByID($item_response->getARDescriptorID());
                                                if ($response_descriptor) {
                                                    if (!array_key_exists($form_element->getRubricID(), $rubric_info)) {

                                                        $rubric_info[$form_element->getRubricID()] = array(
                                                            "modifier" => 0
                                                        );

                                                        // N/A, Not Applicable and Not Observed are excluded from responses when determining average. A modifier will be set if needed, which will adjust later calculations appropriately.
                                                        if (strtolower($response_descriptor->getDescriptor()) == $translate->_("n/a") ||
                                                            strtolower($response_descriptor->getDescriptor()) == $translate->_("not applicable") ||
                                                            strtolower($response_descriptor->getDescriptor()) == $translate->_("did not attend") ||
                                                            strtolower($response_descriptor->getDescriptor()) == $translate->_("not observed")
                                                        ) {
                                                            $rubric_info[$form_element->getRubricID()]["modifier"] = (int)($item_response->getOrder() <= 1) ? -1 : 0;
                                                            $rubric_info[$form_element->getRubricID()]["exclude_ardescriptor_id"] = $response_descriptor->getID();
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            $header[] = $translate->_("Overall");
                            $header[] = $translate->_("Overall Sample Standard Deviation");
                            $output[] = $header;

                            foreach ($PROCESSED["schedule_id_list"] as $schedule_id) {
                                $schedule = Models_Schedule::fetchRowByID($schedule_id);

                                if ($schedule) {
                                    $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByID($schedule->getID());
                                    if ($distribution_schedule) {
                                        $distribution = Models_Assessments_Distribution::fetchRowByID($distribution_schedule->getAdistributionID());
                                        if ($distribution) {
                                            $grouped_progress_responses = $assessors = array();
                                            $number_of_users = 0;

                                            $progress_responses = Models_Assessments_Progress_Response::fetchAllByFormIDDistributionIDAfelementID($PROCESSED["form_id"], $distribution->getID(), $afelement_id_list);
                                            foreach ($progress_responses as $progress_response) {
                                                if (!isset($assessors[$progress_response->getAssessorValue()]) || $assessors[$progress_response->getAssessorValue()] != $progress_response->getAssessorType()) {
                                                    $assessors[$progress_response->getAssessorValue()] = $progress_response->getAssessorType();
                                                    $number_of_users++;
                                                }
                                                $grouped_progress_responses[$progress_response->getAfelementID()][] = $progress_response;
                                            }

                                            $row = array($distribution->getTitle());

                                            $overall = array();
                                            if ($grouped_progress_responses) {

                                                foreach ($grouped_progress_responses as $grouped_progress_response) {
                                                    $order_total = $ctr = 0;

                                                    foreach ($grouped_progress_response as $progress_response) {
                                                        $form_element = Models_Assessments_Form_Element::fetchRowByID($progress_response->getAfelementID());
                                                        if ($form_element) {
                                                            $item_response = Models_Assessments_Item_Response::fetchRowByID($progress_response->getIresponseID());
                                                            if ($item_response) {
                                                                $response_descriptor = Models_Assessments_Response_Descriptor::fetchRowByID($item_response->getARDescriptorID());
                                                                if ($response_descriptor) {

                                                                    if (!array_key_exists($form_element->getRubricID(), $rubric_info) ||
                                                                        !array_key_exists("exclude_ardescriptor_id", $rubric_info[$form_element->getRubricID()]) ||
                                                                        $rubric_info[$form_element->getRubricID()]["exclude_ardescriptor_id"] != $response_descriptor->getID()
                                                                    ) {
                                                                        $offset = 0;
                                                                        $items = Models_Assessments_Item_Response::fetchAllRecordsByItemID($item_response->getItemID());
                                                                        if ($items) {
                                                                            $last_item = end($items);
                                                                            $offset = count($items) - $last_item->getOrder();
                                                                        }
                                                                        $order_total += (int)$item_response->getOrder() + $offset + $rubric_info[$form_element->getRubricID()]["modifier"];
                                                                        $ctr++;
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }

                                                    $column_total = round($order_total / ($ctr == 0 ? 1 : $ctr), 1, PHP_ROUND_HALF_UP);
                                                    $row[] = $column_total;
                                                    $overall[] = $column_total;
                                                }

                                                $row[] = round(array_sum($overall) / count($overall), 1, PHP_ROUND_HALF_UP);
                                                $row[] = round(stats_standard_deviation($overall), 1, PHP_ROUND_HALF_UP);
                                                array_splice($row, 1, 0, $number_of_users);
                                                $output[] = $row;
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        if ($output) {
                            ob_clear_open_buffers();

                            header("Pragma: public");
                            header("Expires: 0");
                            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                            header("Content-Type: application/force-download");
                            header("Content-Type: application/octet-stream");
                            header("Content-Type: text/csv");
                            header("Content-Disposition: attachment; filename=rotation-evaluation.csv");
                            header("Content-Transfer-Encoding: binary");

                            $fp = fopen("php://output", "w");

                            foreach ($output as $row) {
                                fputcsv($fp, $row);
                            }

                            fclose($fp);
                            exit;
                        }
                    }
                    break;

                case "get-user-rotations" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $PROCESSED["curriculum_periods"] = array();

                    if (isset($request["cperiod_ids"])) {
                        $temp_list = explode(",", $request["cperiod_ids"]);

                        foreach ($temp_list as $cperiod_id) {
                            if ($tmp_input = clean_input($cperiod_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["curriculum_periods"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    if (isset($request["course_list"]) && $tmp_input = clean_input($request["course_list"], array("trim", "int"))) {
                        $PROCESSED["course_list"] = array($tmp_input);
                    } else {
                        $PROCESSED["course_list"] = Models_Course::getActiveUserCoursesIDList();
                    }

                    $schedule_code_list = array();
                    $data = array();

                    foreach ($PROCESSED["curriculum_periods"] as $curriculum_period_id) {
                        $schedules = Models_Schedule::fetchAllByCourseIDScheduleTypeCperiod($PROCESSED["course_list"], $PROCESSED["search_value"], "rotation_stream", $curriculum_period_id);

                        if ($schedules) {
                            foreach ($schedules as $schedule) {
                                if (in_array($schedule->getCode(), $schedule_code_list)) {
                                    $existing_schedule_id = 0;
                                    $found_key = -1;

                                    foreach ($data as $key => $row) {
                                        if ($row["target_code"] == $schedule->getCode()) {
                                            $existing_schedule_id = $row["target_id"];
                                            $found_key = $key;
                                            break;
                                        }
                                    }

                                    if ($existing_schedule_id) {
                                        $existing_schedule = Models_Schedule::fetchRowByID($existing_schedule_id);
                                        $new_schedule = Models_Schedule::fetchRowByID($schedule->getID());

                                        $existing_cperiod = Models_Curriculum_Period::fetchRowByID($existing_schedule->getCperiodID());
                                        $new_cperiod = Models_Curriculum_Period::fetchRowByID($new_schedule->getCperiodID());

                                        if ($new_cperiod->getStartDate() > $existing_cperiod->getStartDate()) {
                                            unset($data[$found_key]);
                                            $data[] = array("target_id" => $schedule->getID(), "target_label" => $schedule->getTitle(), "target_code" => $schedule->getCode());
                                        }
                                    }
                                } else {
                                    $schedule_code_list[] = $schedule->getCode();
                                    $data[] = array("target_id" => $schedule->getID(), "target_label" => $schedule->getTitle(), "target_code" => $schedule->getCode());
                                }
                            }
                        }
                    }

                    if (!empty($data)) {
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No rotations were found.")));
                    }
                    break;
                case "update-subtypes-rotations" :
                    $PROCESSED["target_id_list"] = array();

                    if (isset($request["target_id_list"]) && is_array($request["target_id_list"]) && !empty($request["target_id_list"])) {

                        $PROCESSED["target_id_list"] = array_map(
                            function ($val) {
                                return clean_input($val, array("trim", "int"));
                            },
                            $request["target_id_list"]
                        );
                    }

                    $PROCESSED["curriculum_periods"] = array();

                    if (isset($request["cperiod_ids"]) && is_array($request["cperiod_ids"]) && !empty($request["cperiod_ids"])) {

                        $PROCESSED["curriculum_periods"] = array_map(
                            function ($val) {
                                return clean_input($val, array("trim", "int"));
                            },
                            $request["cperiod_ids"]
                        );
                    }

                    if (isset($request["course_list"]) && $tmp_input = clean_input($request["course_list"], array("trim", "int"))) {
                        $PROCESSED["course_list"] = array($tmp_input);
                    } else {
                        $PROCESSED["course_list"] = Models_Course::getActiveUserCoursesIDList();
                    }

                    $data = array();

                    foreach ($PROCESSED["target_id_list"] as $schedule_id) {
                        $current_schedule = Models_Schedule::fetchRowByID($schedule_id);
                        $schedules = array();

                        if ($current_schedule) {
                            $schedules = Models_Schedule::fetchAllByCourseIDCourseCodeScheduleType($PROCESSED["course_list"], $current_schedule->getCode(), "rotation_stream");
                        }

                        if ($schedules) {
                            foreach ($schedules as $schedule) {
                                $distribution_schedules = Models_Assessments_Distribution_Schedule::fetchAllByScheduleID($schedule->getID());

                                if ($distribution_schedules) {
                                    foreach ($distribution_schedules as $distribution_schedule) {
                                        $distribution = Models_Assessments_Distribution::fetchRowByIDCourseIDFormIDTaskType($distribution_schedule->getAdistributionID(), $PROCESSED["course_list"], null,"evaluation");
                                        if ($distribution) {
                                            if (in_array($distribution->getCperiodID(), $PROCESSED["curriculum_periods"])) {
                                                $schedule_cperiod = Models_Curriculum_Period::fetchRowByID($distribution->getCperiodID());
                                                $cperiod_label = $distribution->getTitle();

                                                if ($schedule_cperiod) {
                                                    if ($schedule_cperiod->getCurriculumPeriodTitle()) {
                                                        $cperiod_label .= " " . $schedule_cperiod->getCurriculumPeriodTitle();
                                                    }

                                                    if ($schedule_cperiod->getStartDate() && $schedule_cperiod->getFinishDate()) {
                                                        $cperiod_label .= " " . date("Y-m-d", $schedule_cperiod->getStartDate()) . " - " . date("Y-m-d", $schedule_cperiod->getFinishDate());
                                                    }
                                                }

                                                $data[] = array("target_id" => $distribution_schedule->getAdscheduleID(), "target_label" => $cperiod_label);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if (!empty($data)) {
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No distribution rotations were found.")));
                    }
                    break;
                case "get-user-forms-for-reporting" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $PROCESSED["target_id_list"] = array();

                    if (isset($request["target_id_list"])) {
                        $temp_list = explode(",", $request["target_id_list"]);

                        foreach ($temp_list as $target_id) {
                            if ($tmp_input = clean_input($target_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["target_id_list"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    $PROCESSED["curriculum_periods"] = array();

                    if (isset($request["cperiod_ids"])) {
                        $temp_list = explode(",", $request["cperiod_ids"]);

                        foreach ($temp_list as $cperiod_id) {
                            if ($tmp_input = clean_input($cperiod_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["curriculum_periods"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    $data = array();
                    $course_list = Models_Course::getActiveUserCoursesIDList();

                    foreach ($PROCESSED["target_id_list"] as $target_id) {
                        $distributions = Models_Assessments_Progress::fetchAllTargetsByID($target_id);
                        if (!empty($distributions)) {
                            foreach ($distributions as $distribution_id) {
                                $distribution = Models_Assessments_Distribution::fetchRowByIDCourseIDFormIDTaskType($distribution_id["adistribution_id"], $course_list);
                                $form_id = null;

                                $valid = false;
                                if ($distribution && in_array($distribution->getCperiodID(), $PROCESSED["curriculum_periods"])) {
                                    $valid = true;
                                    $form_id = $distribution->getFormID();
                                }
                                if (!$valid) {
                                    $assessment = Models_Assessments_Assessor::fetchRowByID($distribution_id["dassessment_id"], null, true);
                                    if ($assessment) {
                                        foreach ($PROCESSED["curriculum_periods"] as $curriculum_period_id) {
                                            $cperiod = Models_CurriculumPeriod::fetchRowByID($curriculum_period_id);
                                            if ($cperiod &&
                                                ($assessment->getDeliveryDate() >= $cperiod->getStartDate()
                                                 && $assessment->getDeliveryDate() <= $cperiod->getFinishDate())
                                            ) {
                                                $valid = true;
                                                $form_id = $assessment->getFormID();
                                            }
                                        }
                                    }
                                }

                                if ($valid) {
                                    $form = Models_Assessments_Form::fetchRowByIDSearchTerm($form_id, $PROCESSED["search_value"]);
                                    if ($form) {
                                        $distinct_form = true;

                                        foreach ($data as $row) {
                                            if (in_array($form->getID(), $row)) {
                                                $distinct_form = false;
                                            }
                                        }

                                        if ($distinct_form) {
                                            $data[] = array("target_id" => $form->getID(), "target_label" => $form->getTitle());
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if (!empty($data)) {
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No forms were found.")));
                    }
                    break;
                case "get-user-forms" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $PROCESSED["target_id_list"] = array();

                    if (isset($request["target_id_list"])) {
                        $temp_list = explode(",", $request["target_id_list"]);

                        foreach ($temp_list as $target_id) {
                            if ($tmp_input = clean_input($target_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["target_id_list"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    if (isset($request["current_page"]) && $tmp_input = clean_input(strtolower($request["current_page"]), array("trim", "striptags"))) {
                        $PROCESSED["current_page"] = $tmp_input;
                    } else {
                        $PROCESSED["current_page"] = null;
                    }

                    $data = array();
                    $course_list = Models_Course::getActiveUserCoursesIDList();

                    if (!is_null($PROCESSED["current_page"])) {
                        foreach ($PROCESSED["target_id_list"] as $target_id) {
                            $target = false;

                            if ($PROCESSED["current_page"] == "rotations") {
                                $target = Models_Assessments_Distribution_Schedule::fetchRowByID($target_id);
                            } else {
                                $target = Models_Assessments_Distribution_Eventtype::fetchRowByID($target_id);
                                $target_id = $target->getEventtypeID();
                            }

                            if ($target) {
                                $distribution_targets = Models_Assessments_Distribution_Target::fetchAllByDistributionID($target->getAdistributionID());
                                $distribution_assessors = Models_Assessments_Distribution_Assessor::fetchAllByDistributionID($target->getAdistributionID());

                                if (is_array($distribution_targets) && !empty($distribution_targets) && is_array($distribution_assessors) && !empty($distribution_assessors)) {
                                    $distribution = Models_Assessments_Distribution::fetchRowByIDCourseIDFormIDTaskType($target->getAdistributionID(), $course_list);

                                    if ($distribution) {
                                        $form = Models_Assessments_Form::fetchRowByIDSearchTerm($distribution->getFormID(), $PROCESSED["search_value"]);
                                        if ($form) {
                                            $distinct_form = true;

                                            foreach ($data as $row) {
                                                if (in_array($form->getID(), $row)) {
                                                    $distinct_form = false;
                                                }
                                            }

                                            if ($distinct_form) {
                                                $data[] = array("target_id" => $form->getID(), "target_label" => $form->getTitle());
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if (!empty($data)) {
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No forms were found.")));
                    }
                    break;
                case "get-user-learning-events" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $data = array();
                    $event_types = Models_EventType::fetchAllByOrganisationID($ENTRADA_USER->getActiveOrganisation(), 1, $PROCESSED["search_value"]);
                    if ($event_types) {
                        foreach ($event_types as $event_type) {
                            $data[] = array("target_id" => $event_type->getID(), "target_label" => $event_type->getEventTypeTitle());
                        }
                    }

                    if (!empty($data)) {
                        echo json_encode(array("status" => "success", "data" => $data));
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("No Event Types found"))));
                    }
                    break;
                case "get-individual-learning-events-by-adistribution-ids" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $PROCESSED["adistribution_ids"] = array();
                    if (isset($request["adistribution_ids"]) && is_array($request["adistribution_ids"]) && !empty($request["adistribution_ids"])) {
                        $PROCESSED["adistribution_ids"] = array_map(
                            function ($val) {
                                return clean_input($val, array("trim", "int"));
                            },
                            $request["adistribution_ids"]
                        );
                    } else {
                        add_error($translate->_("No distributions were provided."));
                    }

                    if (has_error()) {
                        echo json_encode(array("status" => "error", "data" => array($ERRORSTR)));
                        exit;
                    }

                    $data = array();

                    $event_assessments = Models_Assessments_Assessor::fetchAllByADistributionIDsAssociatedRecordType($PROCESSED["adistribution_ids"], "event_id");
                    if ($event_assessments) {
                        foreach ($event_assessments as $event_assessment) {
                            // Ensure the event still exists.
                            $event = Models_Event::fetchRowByID($event_assessment["associated_record_id"]);
                            if ($event) {
                                $title = $event->getEventTitle();

                                // Output event teachers as part of the label.
                                $event_contacts = Models_Event_Contacts::fetchAllByEventID($event->getID());
                                if ($event_contacts) {

                                    $teacher_string = false;
                                    foreach ($event_contacts as $event_contact) {
                                        if ($event_contact->getContactRole() != "auditor") {
                                            $teacher_user = Models_User::fetchRowByID($event_contact->getProxyID());
                                            if ($teacher_user) {
                                                $teacher_string .= "{$teacher_user->getFullname(false)}, ";

                                            }
                                        }
                                    }

                                    if ($teacher_string) {
                                        $teacher_string = rtrim($teacher_string, ", ");
                                        $title .= $translate->_(" ( Event Teachers: {$teacher_string} )");
                                    }
                                }

                                $data[$event->getID()] = array("target_id" => $event->getID(), "target_label" => $title);
                            }
                        }
                    }

                    if (!empty($data)) {
                        echo json_encode(array("status" => "success", "data" => $data));
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("No Events found"))));
                    }
                    break;
                case "update-subtypes-learning-events" :
                    $PROCESSED["target_id_list"] = array();

                    if (isset($request["target_id_list"]) && is_array($request["target_id_list"]) && !empty($request["target_id_list"])) {

                        $PROCESSED["target_id_list"] = array_map(
                            function ($val) {
                                return clean_input($val, array("trim", "int"));
                            },
                            $request["target_id_list"]
                        );
                    }

                    $PROCESSED["curriculum_periods"] = array();

                    if (isset($request["cperiod_ids"]) && is_array($request["cperiod_ids"]) && !empty($request["cperiod_ids"])) {

                        $PROCESSED["curriculum_periods"] = array_map(
                            function ($val) {
                                return clean_input($val, array("trim", "int"));
                            },
                            $request["cperiod_ids"]
                        );
                    }

                    $data = array();
                    $distinct_distributions = array();
                    $course_list = Models_Course::getActiveUserCoursesIDList();

                    foreach ($PROCESSED["target_id_list"] as $event_type_id) {
                        $events = Models_Assessments_Distribution_Eventtype::fetchAllByEventTypeIDGroupedByDistributionID($event_type_id);
                        if ($events) {
                            foreach ($events as $event) {

                                if (!in_array($event->getAdistributionID(), $distinct_distributions)) {
                                    $distinct_distributions[] = $event->getAdistributionID();
                                    $distribution = Models_Assessments_Distribution::fetchRowByIDCourseIDFormIDTaskType($event->getAdistributionID(), $course_list, null,"evaluation");
                                    if ($distribution) {

                                        // Don't bother returning a distribution that has no progress.
                                        $progress = Models_Assessments_Progress::fetchAllByDistributionID($distribution->getID());
                                        if ($progress) {

                                            if (in_array($distribution->getCperiodID(), $PROCESSED["curriculum_periods"])) {
                                                $cperiod = Models_Curriculum_Period::fetchRowByID($distribution->getCperiodID());
                                                $cperiod_label = $distribution->getTitle();

                                                if ($cperiod) {
                                                    if ($cperiod->getCurriculumPeriodTitle()) {
                                                        $cperiod_label .= " " . $cperiod->getCurriculumPeriodTitle();
                                                    }

                                                    if ($cperiod->getStartDate() && $cperiod->getFinishDate()) {
                                                        $cperiod_label .= " " . date("Y-m-d", $cperiod->getStartDate()) . " - " . date("Y-m-d", $cperiod->getFinishDate());
                                                    }
                                                }

                                                $data[] = array("target_id" => $event->getID(), "target_label" => $cperiod_label, "adistribution_id" => $event->getAdistributionID());
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if (!empty($data)) {
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No learning event distributions were found.")));
                    }
                    break;
                case "update-subtypes-distribution-schedule" :

                    if (isset($request["task_type"]) && $tmp_input = clean_input(strtolower($request["task_type"]), array("trim", "striptags"))) {
                        $PROCESSED["task_type"] = $tmp_input;
                    } else {
                        add_error($translate->_("You must provide a task type."));
                    }

                    $PROCESSED["curriculum_periods"] = array();
                    if (isset($request["cperiod_ids"]) && is_array($request["cperiod_ids"]) && !empty($request["cperiod_ids"])) {

                        $PROCESSED["curriculum_periods"] = array_map(
                            function ($val) {
                                return clean_input($val, array("trim", "int"));
                            },
                            $request["cperiod_ids"]
                        );
                    }

                    $PROCESSED["course_list"] = array();
                    if (isset($request["target_id_list"]) && is_array($request["target_id_list"]) && !empty($request["target_id_list"])) {

                        $PROCESSED["course_list"] = array_map(
                            function ($val) {
                                return clean_input($val, array("trim", "int"));
                            },
                            $request["target_id_list"]
                        );
                    } else {
                        $PROCESSED["course_list"] = Models_Course::getActiveUserCoursesIDList();
                    }

                    if (has_error()) {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        exit;
                    }

                    $data = array();

                    $distribution_model = new Models_Assessments_Distribution();
                    $distributions = $distribution_model->fetchAllByTaskTypeCourseIDs($PROCESSED["task_type"], $PROCESSED["course_list"], $PROCESSED["curriculum_periods"]);

                    if ($distributions) {
                        foreach ($distributions as $distribution) {

                            $cperiod_label = ($distribution["deleted_date"] ? "(" . $translate->_("Deleted") . ") " : "") . $distribution["title"];

                            $cperiod = Models_Curriculum_Period::fetchRowByID($distribution["cperiod_id"]);
                            if ($cperiod) {
                                if ($cperiod->getCurriculumPeriodTitle()) {
                                    $cperiod_label .= " " . $cperiod->getCurriculumPeriodTitle();
                                }
                                if ($cperiod->getStartDate() && $cperiod->getFinishDate()) {
                                    $cperiod_label .= " " . date("Y-m-d", $cperiod->getStartDate()) . " - " . date("Y-m-d", $cperiod->getFinishDate());
                                }
                            }

                            $course = Models_Course::fetchRowByID($distribution["course_id"]);
                            if ($course) {
                                $cperiod_label = "({$course->getCourseCode()}) $cperiod_label";
                            }

                            $data[] = array("target_id" => $distribution["adistribution_id"], "target_label" => $cperiod_label, "adistribution_id" => $distribution["adistribution_id"]);
                        }
                    }

                    if (!empty($data)) {
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No distributions were found.")));
                    }
                    break;
                case "validate-subtype-by-form" :
                    $PROCESSED["target_id_subtype_list"] = array();

                    if (isset($request["target_id_subtype_list"]) && is_array($request["target_id_subtype_list"]) && !empty($request["target_id_subtype_list"])) {

                        $PROCESSED["target_id_subtype_list"] = array_map(
                            function ($val) {
                                return clean_input($val, array("trim", "int"));
                            },
                            $request["target_id_subtype_list"]
                        );
                    }

                    if (isset($request["form_id"]) && $tmp_input = clean_input($request["form_id"], array("trim", "int"))) {
                        if ($tmp_input) {
                            $PROCESSED["form_id"] = $tmp_input;
                        }
                    } else {
                        $PROCESSED["form_id"] = null;
                    }

                    if (isset($request["current_page"]) && $tmp_input = clean_input(strtolower($request["current_page"]), array("trim", "striptags"))) {
                        $PROCESSED["current_page"] = $tmp_input;
                    } else {
                        $PROCESSED["current_page"] = null;
                    }

                    $data = array();
                    $course_list = Models_Course::getActiveUserCoursesIDList();

                    if ($PROCESSED["current_page"] == "rotations") {
                        foreach ($PROCESSED["target_id_subtype_list"] as $aschedule_id) {
                            $schedule = Models_Assessments_Distribution_Schedule::fetchRowByID($aschedule_id);
                            if ($schedule) {
                                $distribution = Models_Assessments_Distribution::fetchRowByIDCourseIDFormIDTaskType($schedule->getAdistributionID(), $course_list, $PROCESSED["form_id"]);
                                if ($distribution) {
                                    if (!in_array($aschedule_id, $data)) {
                                        $data[] = $aschedule_id;
                                    }
                                }
                            }
                        }
                    } else {
                        foreach ($PROCESSED["target_id_subtype_list"] as $event_type_id) {
                            $event_type = Models_Assessments_Distribution_Eventtype::fetchRowByID($event_type_id);
                            if ($event_type) {
                                $distribution = Models_Assessments_Distribution::fetchRowByIDCourseIDFormIDTaskType($event_type->getAdistributionID(), $course_list, $PROCESSED["form_id"]);
                                if ($distribution) {
                                    if (!in_array($event_type_id, $data)) {
                                        $data[] = $event_type_id;
                                    }
                                }
                            }
                        }
                    }

                    if (!empty($data)) {
                        echo json_encode(array("status" => "success", "data" =>  $data));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No rotation subtypes were selected.")));
                    }
                    break;
                case "validate-subtype-by-evaluations" :
                    $PROCESSED["target_id_list"] = array();

                    if (isset($request["target_id_list"]) && is_array($request["target_id_list"]) && !empty($request["target_id_list"])) {

                        $PROCESSED["target_id_list"] = array_map(
                            function ($val) {
                                return clean_input($val, array("trim", "int"));
                            },
                            $request["target_id_list"]
                        );
                    }

                    if (isset($request["current_page"]) && $tmp_input = clean_input(strtolower($request["current_page"]), array("trim", "striptags"))) {
                        $PROCESSED["current_page"] = $tmp_input;
                    } else {
                        $PROCESSED["current_page"] = null;
                    }

                    $data = array();

                    if (!is_null($PROCESSED["current_page"])) {
                        foreach ($PROCESSED["target_id_list"] as $target_id) {
                            $target_id_backup = $target_id;
                            $target = false;

                            if ($PROCESSED["current_page"] == "rotations") {
                                $target = Models_Assessments_Distribution_Schedule::fetchRowByID($target_id);
                            } else if ($PROCESSED["current_page"] == "distribution_schedule") {

                            } else {
                                $target = Models_Assessments_Distribution_Eventtype::fetchRowByID($target_id);
                                $target_id = $target->getEventtypeID();
                            }

                            if ($target) {
                                $distribution_targets = Models_Assessments_Distribution_Target::fetchAllByDistributionID($target->getAdistributionID());
                                $distribution_assessors = Models_Assessments_Distribution_Assessor::fetchAllByDistributionID($target->getAdistributionID());

                                if (is_array($distribution_targets) && !empty($distribution_targets) && is_array($distribution_assessors) && !empty($distribution_assessors)) {
                                    $data[] = $target_id_backup;
                                }
                            }
                        }
                    }

                    if (!empty($data)) {
                        $data = array_unique($data);
                        echo json_encode(array("status" => "success", "data" => $data));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $PROCESSED["current_page"] == "rotations" ? $translate->_("No distribution rotations were found.") : $translate->_("No learning event distributions were found.")));
                    }
                    break;
                case "get-user-faculty" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = null;
                    }

                    $data = array();
                    $admin = $ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true);

                    $assessment_user = new Entrada_Utilities_AssessmentUser();
                    $faculty = $assessment_user->getFaculty($ENTRADA_USER->getActiveId(), $ENTRADA_USER->getActiveOrganisation(), $admin, null, $PROCESSED["search_value"], true);
                    if (!empty($faculty)) {
                        foreach ($faculty as $faculty_member) {
                            if (is_object($faculty_member) && $faculty_member->getProxyID() != $ENTRADA_USER->getActiveID()) {
                                $data[] = array("target_id" => $faculty_member->getProxyID(), "target_label" => $faculty_member->getFirstname() . " " . $faculty_member->getLastname());
                            }
                        }
                    }

                    if (!empty($data)) {
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No faculty were found.")));
                    }
                    break;
                case "get-assessor-faculty" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = null;
                    }

                    $PROCESSED["add_externals"] = false;
                    if (isset($request["add_externals"])) {
                        $tmp_input = $request["add_externals"] === "true";
                        $PROCESSED["add_externals"] = clean_input($tmp_input, array("trim", "bool"));
                    }

                    $data = array();

                    $assessment_user = new Entrada_Utilities_AssessmentUser();
                    $faculty = $assessment_user->getAssessorFacultyList($ENTRADA_USER->getActiveId(), $ENTRADA_USER->getActiveOrganisation(), $PROCESSED["search_value"], $PROCESSED["add_externals"], true);

                    if (!empty($faculty)) {
                        foreach ($faculty as $key => $faculty_member) {
                            $faculty_details = $PROCESSED["add_externals"] ?  " - <strong>" . ucfirst($faculty_member["type"]) . "</strong>" : "";
                            $data[] = array("target_id" => $faculty_member["course_contact_id"], "target_label" => $faculty_member["firstname"] . " ". $faculty_member["lastname"] . $faculty_details);
                        }
                    }

                    if (!empty($data)) {
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No faculty were found.")));
                    }
                    break;
                case "get-user-learners" :
                    $PROCESSED["curriculum_periods"] = array();

                    if (isset($request["cperiod_ids"])) {
                        $temp_list = explode(",", $request["cperiod_ids"]);

                        foreach ($temp_list as $cperiod_id) {
                            if ($tmp_input = clean_input($cperiod_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["curriculum_periods"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = null;
                    }
                    if (isset($request["limit"]) && $tmp_input = clean_input(strtolower($request["limit"]), array("trim", "int"))) {
                        $PROCESSED["limit"] = $tmp_input;
                    } else {
                        $PROCESSED["limit"] = "";
                    }
                    if (isset($request["offset"]) && $tmp_input = clean_input(strtolower($request["offset"]), array("trim", "int"))) {
                        $PROCESSED["offset"] = $tmp_input;
                    } else {
                        $PROCESSED["offset"] = "";
                    }

                    $data = array();
                    $assessment_user = new Entrada_Utilities_AssessmentUser();
                    $admin = $ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true);
                    $learners = $assessment_user->getMyLearners($ENTRADA_USER->getActiveId(), $ENTRADA_USER->getActiveOrganisation(), $admin, $PROCESSED["search_value"], null, $PROCESSED["limit"], $PROCESSED["offset"]);
                    if (!empty($PROCESSED["curriculum_periods"]) && !empty($learners)) {
                        foreach ($learners as $learner) {
                            $learner_full_name = $learner["firstname"] . " " . $learner["lastname"];
                            $cperiods_matching = array_intersect($learner["cperiod_ids"], $PROCESSED["curriculum_periods"]);
                            if (count($cperiods_matching) > 0 && (is_null($PROCESSED["search_value"]) || strpos(strtolower($learner_full_name), strtolower($PROCESSED["search_value"])) !== false)) {
                                $data[] = array("target_id" => $learner["proxy_id"], "target_label" => $learner_full_name);
                            }
                        }
                    }

                    if (!empty($data)) {
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No learners were found.")));
                    }
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
                            $data[] = array("target_id" => $course->getID(), "target_label" => "{$course->getCourseCode()}: {$course->getCourseName()}");
                        }
                    }

                    if (!empty($data)) {
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No courses were found.")));
                    }
                    break;
                case "get-course-forms-for-reporting" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $PROCESSED["course_id"] = 0;
                    if (isset($request["target_id_list"]) && $tmp_input = clean_input($request["target_id_list"], array("trim", "int"))) {
                        $PROCESSED["course_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Course ID not provided."));
                    }

                    $data = array();

                    if (!$ERROR && $PROCESSED["course_id"]) {
                        $distributions = null;
                        $distribution_targets = Models_Assessments_Distribution_Target::fetchAllByTargetTypeTargetScopeTargetRoleTargetID("course_id", "self", "any", $PROCESSED["course_id"]);
                        if ($distribution_targets) {
                            foreach ($distribution_targets as $distribution_target) {
                                $distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($distribution_target->getAdistributionID());
                                if ($distribution) {
                                    $form = Models_Assessments_Form::fetchRowByIDSearchTerm($distribution->getFormID(), $PROCESSED["search_value"]);
                                    if ($form) {
                                        $distinct_form = true;

                                        foreach ($data as $row) {
                                            if (in_array($form->getID(), $row)) {
                                                $distinct_form = false;
                                            }
                                        }

                                        if ($distinct_form) {
                                            $data[] = array("target_id" => $form->getID(), "target_label" => $form->getTitle());
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if (!empty($data)) {
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No forms were found.")));
                    }
                    break;
                case "get-residents" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = null;
                    }

                    if (isset($request["excluded_target_ids"]) && $tmp_input = clean_input(strtolower($request["excluded_target_ids"]), array("trim", "striptags"))) {
                        $PROCESSED["excluded_target_ids"] = $tmp_input;
                    } else {
                        $PROCESSED["excluded_target_ids"] = 0;
                    }

                    if (isset($request["limit"]) && $tmp_input = clean_input(strtolower($request["limit"]), array("trim", "int"))) {
                        $PROCESSED["limit"] = $tmp_input;
                    } else {
                        $PROCESSED["limit"] = 50;
                    }

                    if (isset($request["offset"]) && $tmp_input = clean_input(strtolower($request["offset"]), array("trim", "int"))) {
                        $PROCESSED["offset"] = $tmp_input;
                    } else {
                        $PROCESSED["offset"] = 0;
                    }

                    if (isset($request["start_date"]) && $tmp_input = clean_input(strtolower($request["start_date"]), array("trim", "int"))) {
                        $PROCESSED["start_date"] = $tmp_input;
                    } else {
                        $PROCESSED["start_date"] = 0;
                    }

                    if (isset($request["end_date"]) && $tmp_input = clean_input(strtolower($request["end_date"]), array("trim", "int"))) {
                        $PROCESSED["end_date"] = $tmp_input;
                    } else {
                        $PROCESSED["end_date"] = 0;
                    }

                    $assessment_user = new Entrada_Utilities_AssessmentUser();
                    $admin = $ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true);
                    $users = $assessment_user->getMyLearners($ENTRADA_USER->getActiveId(), $ENTRADA_USER->getActiveOrganisation(), $admin, $PROCESSED["search_value"], null, $PROCESSED["limit"], $PROCESSED["offset"]);
                    
                    $data = array();

                    if ($users) {
                        foreach ($users as $user) {
                            $data[] = array("target_id" => $user["proxy_id"], "target_label" => $user["firstname"] . " " . $user["lastname"], "lastname" => $user["lastname"], "email" => $user["email"]);
                        }
                    }

                    if ($data) {
                        echo json_encode(array("status" => "success", "data" => $data));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Residents found")));
                    }
                    break;

                case "get-schedule-blocks" :
                    if (isset($request["cperiod_id"]) && $tmp_input = clean_input($request["cperiod_id"], array("trim", "int"))) {
                        $PROCESSED["cperiod_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No curriculum period id provided."));
                    }

                    $data = array();
                    if (!$ERROR) {
                        $blocks = Models_Schedule::fetchAllBlockTemplatesByCPeriodIDBlockTypeID($PROCESSED["cperiod_id"], 3);

                        if ($blocks) {
                            foreach ($blocks as $block) {
                                $data[] = array("target_id" => $block->getID(), "target_label" => $block->getTitle());
                            }
                        }
                    }

                    if (!empty($data)) {
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No blocks were found.")));

                    }
                    break;
            }
            break;
    }
    exit;
}