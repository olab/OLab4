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
    global $translate;
    $assessment_user = new Entrada_Utilities_AssessmentUser();

    function datesOverlap($start_one, $end_one, $start_two, $end_two) {
        if ($start_one <= $end_two && $end_one >= $start_two) {
            return false;
        }
        return true;
    }

    function validateDateSingle($date) {
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
                        $_SESSION[APPLICATION_IDENTIFIER][$PROCESSED["current_page"]]["evaluation"]["start_date"] = $PROCESSED["start_date"];
                        $_SESSION[APPLICATION_IDENTIFIER][$PROCESSED["current_page"]]["evaluation"]["end_date"] = $PROCESSED["end_date"];
                        $assessment_user->updateAssessmentPreferences($PROCESSED["current_page"]);
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
                        $_SESSION[APPLICATION_IDENTIFIER][$PROCESSED["current_page"]]["cperiod"] = $PROCESSED["cperiod"];
                        $assessment_user->updateAssessmentPreferences($PROCESSED["current_page"]);
                    }
                    break;

                case "generate-pdf-for-tasks-bulk":

                    $PROCESSED["pdf_individual_option"] = false;
                    if (array_key_exists("pdf_individual_option", $_POST)) {
                        if ($_POST["pdf_individual_option"] == "1") {
                            $PROCESSED["pdf_individual_option"] = true;
                        }
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

                    $PROCESSED["course_ids"] = null;
                    if (isset($request["course_ids"])) {
                        $temp_list = explode(",", $request["course_ids"]);
                        foreach ($temp_list as $course_id) {
                            if ($tmp_input = clean_input($course_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["course_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    $PROCESSED["target_type"] = null;
                    if (isset($request["target_type"]) && $tmp_input = clean_input(strtolower($request["target_type"]), array("trim", "striptags"))) {
                        $PROCESSED["target_type"] = $tmp_input;
                    }

                    $PROCESSED["target_ids"] = null;
                    if (isset($request["target_ids"])) {
                        $temp_list = explode(",", $request["target_ids"]);
                        foreach ($temp_list as $target_id) {
                            if ($tmp_input = clean_input($target_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["target_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    $PROCESSED["form_ids"] = null;
                    if (isset($request["form_ids"])) {
                        $temp_list = explode(",", $request["form_ids"]);
                        foreach ($temp_list as $form_id) {
                            if ($tmp_input = clean_input($form_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["form_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    if (!$PROCESSED["target_type"] || !$PROCESSED["target_ids"]) {
                        add_error($translate->_("No target data provided."));
                        header('Location: ' . $_SERVER['HTTP_REFERER'] . "&error=true");
                    }

                    @set_time_limit(0);
                    @ini_set("memory_limit", "2048M");

                    $tasks = array();

                    $progress_model = new Models_Assessments_Progress();
                    $tasks = $progress_model->fetchAllCompletedProgressByCourseIDsFormIDsTargetTypeTargetIDs($PROCESSED["course_ids"], $PROCESSED["start_date"], $PROCESSED["end_date"], $PROCESSED["target_type"], $PROCESSED["target_ids"], $PROCESSED["form_ids"], array(1));

                    if (!empty($tasks)) {

                        $data = array();
                        foreach ($tasks as $task) {
                            $data[] = array (
                                "dassessment_id" => $task["dassessment_id"],
                                "aprogress_id" => $task["aprogress_id"]
                            );
                        }

                        $error_url = Entrada_Utilities::arrayValueOrDefault($_POST, "error_url", ENTRADA_URL . "/assessments?pdf-error=true");
                        $format = $PROCESSED["pdf_individual_option"] ? "pdf" : "zip";

                        $download_token = array_key_exists("pdf_download_token", $_POST) ? $_POST["pdf_download_token"] : null;

                        $pdf_generator = new Entrada_Utilities_Assessments_PDFDownload(
                            array(
                                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                                "actor_type" => "proxy_id",
                                "actor_scope" => "internal"
                            )
                        );

                        // This step is terminal; it clears open buffers and sends out a ZIP or PDF
                        if (!$pdf_generator->sendAssessmentTasks($data, $download_token, $format)) {
                            // failed to send, redirect
                            ob_clear_open_buffers();
                            Header("Location: $error_url");
                            exit();
                        }
                    } else {
                        add_error($translate->_("No tasks found."));
                        header('Location: ' . $_SERVER['HTTP_REFERER'] . "&error=true");
                    }

                    break;
            }
            break;
        case "GET" :
            switch ($request["method"]) {
                case "get-user-learners" :

                    $PROCESSED["cperiod_ids"] = array();
                    if (isset($request["cperiod_ids"])) {
                        $temp_list = explode(",", $request["cperiod_ids"]);
                        foreach ($temp_list as $cperiod_id) {
                            if ($tmp_input = clean_input($cperiod_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["cperiod_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    $PROCESSED["course_ids"] = array();
                    if (isset($request["course_ids"])) {
                        $temp_list = explode(",", $request["course_ids"]);
                        foreach ($temp_list as $course_id) {
                            if ($tmp_input = clean_input($course_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["course_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    $PROCESSED["course_group_ids"] = array();
                    if (isset($request["course_group_ids"])) {
                        $temp_list = explode(",", $request["course_group_ids"]);
                        foreach ($temp_list as $course_group_id) {
                            if ($tmp_input = clean_input($course_group_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["course_group_ids"][] = $tmp_input;
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
                    $admin = $ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true);

                    $learners = $assessment_user->getMyLearners(
                        $ENTRADA_USER->getActiveId(),
                        $ENTRADA_USER->getActiveOrganisation(),
                        $admin,
                        $PROCESSED["search_value"],
                        null,
                        !empty($PROCESSED["course_group_ids"]) ? null : $PROCESSED["limit"],
                        !empty($PROCESSED["course_group_ids"]) ? null : $PROCESSED["offset"],
                        $PROCESSED["course_ids"]
                    );

                    if (!empty($learners)) {
                        foreach ($learners as $learner) {
                            $include = true;
                            $learner_full_name = $learner["firstname"] . " " . $learner["lastname"];

                            if (!empty($PROCESSED["curriculum_periods"])) {
                                $cperiods_matching = array_intersect($learner["cperiod_ids"], $PROCESSED["cperiod_ids"]);
                                if (@count($cperiods_matching) < 1) {
                                    $include = false;
                                }
                            }

                            if (!empty($PROCESSED["course_group_ids"])) {
                                $found = false;
                                $course_group_audience_model = new Models_Course_Group_Audience();
                                foreach ($PROCESSED["course_group_ids"] as $course_group_id) {
                                    if ($course_group_audience_model->fetchAllByCGroupIDProxyID($course_group_id, $learner["proxy_id"])) {
                                        $found = true;
                                    }
                                }
                                if (!$found) {
                                    $include = false;
                                }

                            }

                            if ($PROCESSED["search_value"]) {
                                if (!strpos(strtolower($learner_full_name), strtolower($PROCESSED["search_value"]))) {
                                    $include = false;
                                }
                            }

                            if ($include) {
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

                case "get-user-targets-for-distribution-reviewer" :

                    $PROCESSED["course_ids"] = array();
                    if (isset($request["course_ids"])) {
                        $temp_list = explode(",", $request["course_ids"]);
                        foreach ($temp_list as $course_id) {
                            if ($tmp_input = clean_input($course_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["course_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }

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

                    $assessment_target_model = new Models_Assessments_AssessmentTarget();
                    $learners = $assessment_target_model->fetchAllProxyIDTargetsForDistributionReviewer($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation(), $PROCESSED["course_ids"], $PROCESSED["start_date"], $PROCESSED["end_date"], $PROCESSED["search_value"], $PROCESSED["limit"], $PROCESSED["offset"]);
                    foreach ($learners as $learner) {
                        $data[] = array("target_id" => $learner["proxy_id"], "target_label" => $learner["fullname"]);
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
                        echo json_encode(array("status" => "error", "data" => $translate->_("no_courses_found")));
                    }
                    break;

                case "get-course-groups" :

                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $PROCESSED["course_ids"] = array();
                    if (isset($request["course_ids"])) {
                        $temp_list = explode(",", $request["course_ids"]);
                        foreach ($temp_list as $course_id) {
                            if ($tmp_input = clean_input($course_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["course_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    if (empty($PROCESSED["course_ids"])) {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Course provided")));
                        exit;
                    }

                    $course_groups = array();
                    $course_group_model = new Models_Course_Group();

                    foreach ($PROCESSED["course_ids"] as $course_id) {
                        $groups = $course_group_model->getGroupsByCourseIDSearchTerm($course_id, $PROCESSED["search_value"]);
                        if ($groups) {
                            foreach ($groups as $group) {
                                $course_groups[$group->getID()] = $group;
                            }
                        }
                    }

                    if ($course_groups) {
                        foreach ($course_groups as $course_group) {
                            $data[] = array("target_id" => $course_group->getID(), "target_label" => $course_group->getGroupName());
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Course Groups found")));
                    }
                    break;

                case "get-distribution-reviewer-courses" :

                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = null;
                    }

                    $data = array();
                    $distribution_model = new Models_Assessments_Distribution_Reviewer();
                    $courses = $distribution_model->getDistributionCoursesForReviewer($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation());

                    if (!empty($courses)) {
                        foreach ($courses as $course) {
                            $data[] = array("target_id" => $course["course_id"], "target_label" => "{$course["course_code"]}: {$course["course_name"]}");
                        }
                    }

                    if (!empty($data)) {
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("no_courses_found")));
                    }
                    break;

                case "get-forms-for-reporting" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $PROCESSED["cperiod_ids"] = null;
                    if (isset($request["cperiod_ids"])) {
                        $temp_list = explode(",", $request["cperiod_ids"]);
                        foreach ($temp_list as $cperiod_id) {
                            if ($tmp_input = clean_input($cperiod_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["cperiod_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    $PROCESSED["course_ids"] = null;
                    if (isset($request["course_ids"])) {
                        $temp_list = explode(",", $request["course_ids"]);
                        foreach ($temp_list as $course_id) {
                            if ($tmp_input = clean_input($course_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["course_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    $PROCESSED["target_type"] = null;
                    if (isset($request["target_type"]) && $tmp_input = clean_input(strtolower($request["target_type"]), array("trim", "striptags"))) {
                        $PROCESSED["target_type"] = $tmp_input;
                    }

                    $PROCESSED["target_ids"] = null;
                    if (isset($request["target_ids"])) {
                        $temp_list = explode(",", $request["target_ids"]);
                        foreach ($temp_list as $target_id) {
                            if ($tmp_input = clean_input($target_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["target_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    $PROCESSED["distributionless"] = false;
                    if (isset($request["distributionless"]) && $request["distributionless"] === "true") {
                        $PROCESSED["distributionless"] = true;
                    }

                    $data = array();
                    if (!$ERROR) {

                        $start_date = null;
                        $end_date = null;
                        if ($PROCESSED["cperiod_ids"]) {
                            foreach ($PROCESSED["cperiod_ids"] as $cperiod_id) {
                                $cperiod = Models_Curriculum_Period::fetchRowByID($cperiod_id);
                                if ($cperiod) {
                                    $start_date = (!$start_date || $start_date > $cperiod->getStartDate() ? $cperiod->getStartDate() : $start_date);
                                    $end_date = (!$end_date || $end_date < $cperiod->getFinishDate() ? $cperiod->getFinishDate() : $end_date);
                                }
                            }
                        }

                        $form_model = new Models_Assessments_Form();
                        $forms = $form_model->fetchAllWithCompletedProgressByCourseIDsTargetTypeTargetIDs(
                            $PROCESSED["search_value"],
                            $PROCESSED["course_ids"],
                            $start_date,
                            $end_date,
                            $PROCESSED["target_type"],
                            $PROCESSED["target_ids"],
                            array(1),
                            $PROCESSED["distributionless"]
                        );
                        if ($forms) {
                            foreach ($forms as $form) {
                                $data[] = array("target_id" => $form["form_id"], "target_label" => $form["title"]);
                            }
                        }
                    }

                    if (!empty($data)) {
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No forms were found.")));
                    }
                    break;

                case "get-distribution-reviewer-forms-for-reporting" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $PROCESSED["cperiod_ids"] = null;
                    if (isset($request["cperiod_ids"])) {
                        $temp_list = explode(",", $request["cperiod_ids"]);
                        foreach ($temp_list as $cperiod_id) {
                            if ($tmp_input = clean_input($cperiod_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["cperiod_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    $PROCESSED["course_ids"] = null;
                    if (isset($request["course_ids"])) {
                        $temp_list = explode(",", $request["course_ids"]);
                        foreach ($temp_list as $course_id) {
                            if ($tmp_input = clean_input($course_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["course_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }

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

                    $PROCESSED["target_type"] = null;
                    if (isset($request["target_type"]) && $tmp_input = clean_input(strtolower($request["target_type"]), array("trim", "striptags"))) {
                        $PROCESSED["target_type"] = $tmp_input;
                    }

                    $PROCESSED["target_ids"] = null;
                    if (isset($request["target_ids"])) {
                        $temp_list = explode(",", $request["target_ids"]);
                        foreach ($temp_list as $target_id) {
                            if ($tmp_input = clean_input($target_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["target_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    $PROCESSED["distribution_ids"] = null;
                    if (isset($request["distribution_ids"])) {
                        $temp_list = explode(",", $request["distribution_ids"]);
                        foreach ($temp_list as $distribution_id) {
                            if ($tmp_input = clean_input($distribution_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["distribution_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    $data = array();
                    if (!$ERROR) {
                        $form_model = new Models_Assessments_Form();
                        $forms = $form_model->fetchAllFormsForDistributionReviewerByTargetTypeTargetValues(
                            $ENTRADA_USER->getActiveID(),
                            $ENTRADA_USER->getActiveOrganisation(),
                            $PROCESSED["course_ids"],
                            $PROCESSED["target_type"],
                            $PROCESSED["target_ids"],
                            $PROCESSED["distribution_ids"],
                            $PROCESSED["start_date"],
                            $PROCESSED["end_date"],
                            $PROCESSED["search_value"]
                        );
                        if ($forms) {
                            foreach ($forms as $form) {
                                $data[] = array("target_id" => $form["form_id"], "target_label" => $form["title"]);
                            }
                        }
                    }

                    if (!empty($data)) {
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No forms were found.")));
                    }
                    break;

                case "get-distributions-for-reporting" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $PROCESSED["cperiod_ids"] = null;
                    if (isset($request["cperiod_ids"])) {
                        $temp_list = explode(",", $request["cperiod_ids"]);
                        foreach ($temp_list as $cperiod_id) {
                            if ($tmp_input = clean_input($cperiod_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["cperiod_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    $PROCESSED["course_ids"] = null;
                    if (isset($request["course_ids"])) {
                        $temp_list = explode(",", $request["course_ids"]);
                        foreach ($temp_list as $course_id) {
                            if ($tmp_input = clean_input($course_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["course_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    $PROCESSED["form_ids"] = null;
                    if (isset($request["form_ids"])) {
                        $temp_list = explode(",", $request["form_ids"]);
                        foreach ($temp_list as $form_ids) {
                            if ($tmp_input = clean_input($form_ids, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["form_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    $PROCESSED["target_type"] = null;
                    if (isset($request["target_type"]) && $tmp_input = clean_input(strtolower($request["target_type"]), array("trim", "striptags"))) {
                        $PROCESSED["target_type"] = $tmp_input;
                    }

                    $PROCESSED["target_ids"] = null;
                    if (isset($request["target_ids"])) {
                        $temp_list = explode(",", $request["target_ids"]);
                        foreach ($temp_list as $target_id) {
                            if ($tmp_input = clean_input($target_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["target_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    $data = array();
                    if (!$ERROR) {

                        $start_date = null;
                        $end_date = null;
                        if ($PROCESSED["cperiod_ids"]) {
                            foreach ($PROCESSED["cperiod_ids"] as $cperiod_id) {
                                $cperiod = Models_Curriculum_Period::fetchRowByID($cperiod_id);
                                if ($cperiod) {
                                    $start_date = (!$start_date || $start_date > $cperiod->getStartDate() ? $cperiod->getStartDate() : $start_date);
                                    $end_date = (!$end_date || $end_date < $cperiod->getFinishDate() ? $cperiod->getFinishDate() : $end_date);
                                }
                            }
                        }

                        $distribution_model = new Models_Assessments_Distribution();
                        $distributions = $distribution_model->fetchAllWithCompletedProgressByCourseIDsFormIDsTargetTypeTargetIDs(
                            $PROCESSED["search_value"],
                            $PROCESSED["course_ids"],
                            $start_date,
                            $end_date,
                            $PROCESSED["form_ids"],
                            $PROCESSED["target_type"],
                            $PROCESSED["target_ids"],
                            array(1)
                        );
                        if ($distributions) {
                            foreach ($distributions as $distribution) {
                                $data[] = array("target_id" => $distribution["adistribution_id"], "target_label" => $distribution["title"]);
                            }
                        }
                    }

                    if (!empty($data)) {
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No distributions were found.")));
                    }
                    break;

                case "get-reviewer-distributions-for-reporting" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $PROCESSED["cperiod_ids"] = null;
                    if (isset($request["cperiod_ids"])) {
                        $temp_list = explode(",", $request["cperiod_ids"]);
                        foreach ($temp_list as $cperiod_id) {
                            if ($tmp_input = clean_input($cperiod_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["cperiod_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    $PROCESSED["course_ids"] = null;
                    if (isset($request["course_ids"])) {
                        $temp_list = explode(",", $request["course_ids"]);
                        foreach ($temp_list as $course_id) {
                            if ($tmp_input = clean_input($course_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["course_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    $PROCESSED["form_ids"] = null;
                    if (isset($request["form_ids"])) {
                        $temp_list = explode(",", $request["form_ids"]);
                        foreach ($temp_list as $form_ids) {
                            if ($tmp_input = clean_input($form_ids, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["form_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    $PROCESSED["target_type"] = null;
                    if (isset($request["target_type"]) && $tmp_input = clean_input(strtolower($request["target_type"]), array("trim", "striptags"))) {
                        $PROCESSED["target_type"] = $tmp_input;
                    }

                    $PROCESSED["target_ids"] = null;
                    if (isset($request["target_ids"])) {
                        $temp_list = explode(",", $request["target_ids"]);
                        foreach ($temp_list as $target_id) {
                            if ($tmp_input = clean_input($target_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["target_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    $data = array();
                    if (!$ERROR) {

                        $start_date = null;
                        $end_date = null;
                        if ($PROCESSED["cperiod_ids"]) {
                            foreach ($PROCESSED["cperiod_ids"] as $cperiod_id) {
                                $cperiod = Models_Curriculum_Period::fetchRowByID($cperiod_id);
                                if ($cperiod) {
                                    $start_date = (!$start_date || $start_date > $cperiod->getStartDate() ? $cperiod->getStartDate() : $start_date);
                                    $end_date = (!$end_date || $end_date < $cperiod->getFinishDate() ? $cperiod->getFinishDate() : $end_date);
                                }
                            }
                        }

                        $distribution_model = new Models_Assessments_Distribution();
                        $distributions = $distribution_model->fetchAllWithCompletedProgressForReviewerByCourseIDsFormIDsTargetTypeTargetIDs(
                            $ENTRADA_USER->getActiveID(),
                            $ENTRADA_USER->getActiveOrganisation(),
                            $PROCESSED["search_value"],
                            $PROCESSED["course_ids"],
                            $start_date,
                            $end_date,
                            $PROCESSED["form_ids"],
                            $PROCESSED["target_type"],
                            $PROCESSED["target_ids"]
                        );
                        if ($distributions) {
                            foreach ($distributions as $distribution) {
                                $data[] = array("target_id" => $distribution["adistribution_id"], "target_label" => $distribution["title"]);
                            }
                        }
                    }

                    if (!empty($data)) {
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No distributions were found.")));
                    }
                    break;

                case "generate-bulk-pdf-reports":

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

                    $PROCESSED["is_evaluation"] = null;
                    if (isset($request["is_evaluation"]) && $tmp_input = clean_input(strtolower($request["is_evaluation"]), array("trim", "striptags"))) {
                        $PROCESSED["is_evaluation"] = $tmp_input;
                    } else {
                        Entrada_Utilities_Flashmessenger::addMessage($translate->_("No task type specified."), "error", $MODULE);
                    }

                    $PROCESSED["target_type"] = null;
                    if (isset($request["target_type"]) && $tmp_input = clean_input(strtolower($request["target_type"]), array("trim", "striptags"))) {
                        $PROCESSED["target_type"] = $tmp_input;
                    } else {
                        Entrada_Utilities_Flashmessenger::addMessage($translate->_("No target type specified."), "error", $MODULE);
                    }

                    $PROCESSED["target_ids"] = null;
                    if (isset($request["target_ids"])) {
                        foreach ($request["target_ids"] as $target_id) {
                            if ($tmp_input = clean_input($target_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["target_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }
                    if (!$PROCESSED["target_ids"]) {
                        Entrada_Utilities_Flashmessenger::addMessage($translate->_("No targets specified."), "error", $MODULE);
                    }

                    $PROCESSED["form_ids"] = null;
                    if (isset($request["form_ids"])) {
                        foreach ($request["form_ids"] as $form_ids) {
                            if ($tmp_input = clean_input($form_ids, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["form_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }
                    if (!$PROCESSED["form_ids"]) {
                        add_error($translate->_("No forms specified."));
                        Entrada_Utilities_Flashmessenger::addMessage($translate->_("No forms specified."), "error", $MODULE);
                    }

                    $PROCESSED["course_ids"] = null;
                    if (isset($request["course_ids"])) {
                        foreach ($request["course_ids"] as $course_id) {
                            if ($tmp_input = clean_input($course_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["course_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    $PROCESSED["distribution_ids"] = null;
                    if (isset($request["distribution_ids"])) {
                        foreach ($request["distribution_ids"] as $distribution_ids) {
                            if ($tmp_input = clean_input($distribution_ids, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["distribution_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    $PROCESSED["reviewer_only"] = false;
                    if (isset($request["reviewer_only"])) {
                        $PROCESSED["reviewer_only"] = ($request["reviewer_only"] === "true") ? true : false;
                    }

                    $PROCESSED["include_comments"] = false;
                    if (isset($request["include_comments"])) {
                        $PROCESSED["include_comments"] = ($request["include_comments"] === "true") ? true : false;
                    }

                    $PROCESSED["include_commenter_id"] = false;
                    if ($PROCESSED["include_comments"]) {
                        if (isset($request["include_commenter_id"])) {
                            $PROCESSED["include_commenter_id"] = ($request["include_commenter_id"] === "true") ? true : false;
                        }
                    }

                    $PROCESSED["include_commenter_name"] = false;
                    if ($PROCESSED["include_comments"]) {
                        if (isset($request["include_commenter_name"])) {
                            $PROCESSED["include_commenter_name"] = ($request["include_commenter_name"] === "true") ? true : false;
                        }
                    }

                    $PROCESSED["description"] = null;
                    if (isset($request["include_description"]) && $request["include_description"] === "true") {
                        if (isset($request["description"]) && $tmp_input = clean_input($request["description"], array("trim", "striptags"))) {
                            $PROCESSED["description"] = $tmp_input;
                        }
                    }

                    $PROCESSED["include_statistics"] = false;
                    if (isset($request["include_statistics"])) {
                        $PROCESSED["include_statistics"] = ($request["include_statistics"] === "true") ? true : false;
                    }

                    $PROCESSED["include_positivity"] = false;
                    if ($PROCESSED["include_statistics"]) {
                        if (isset($request["include_positivity"])) {
                            $PROCESSED["include_positivity"] = ($request["include_positivity"] === "true") ? true : false;
                        }
                    }

                    if (has_error()) {
                        display_error();
                        header('Location: ' . $_SERVER['HTTP_REFERER'] . "&error=true");
                    }

                    $report_html = array();
                    $user_name = array();
                    $form_title = null;

                    foreach ($PROCESSED["form_ids"] as $form_id) {
                        foreach ($PROCESSED["target_ids"] as $target_id) {

                            $full_target_list_details = "<li>" . $translate->_("Report Start Date: ") . (is_null($PROCESSED["start_date"]) ? $translate->_("Not Set") : date("Y-m-d", $PROCESSED["start_date"])) . " " . $translate->_("Report End Date: ") . (is_null($PROCESSED["end_date"]) ? $translate->_("Not Set") : date("Y-m-d", $PROCESSED["end_date"])) . "</li>";
                            $form = Models_Assessments_Form::fetchRowByIDIncludeDeleted($form_id);
                            if ($form) {
                                $form_title[] = $form->getTitle();
                            }

                            $construction = array(
                                "target_value" => $target_id,
                                "target_type" => $PROCESSED["target_type"],
                                "form_id" => $form_id,
                                "start_date" => $PROCESSED["start_date"],
                                "end_date" => $PROCESSED["end_date"],
                                "adistribution_id" => $PROCESSED["distribution_ids"],
                                "course_id" => $PROCESSED["course_ids"],
                                "reviewer_ids" => $PROCESSED["reviewer_only"] ? $ENTRADA_USER->getActiveID() : false
                            );

                            $reporting_utility = new Entrada_Utilities_Assessments_Reports($construction);
                            $report_data = $reporting_utility->generateReport();

                            // Don't bother generating empty data.
                            if (!$report_data || empty($report_data)) {
                                continue;
                            }

                            $target_info = Entrada_Assessments_Base::getTargetInfo($PROCESSED["target_type"], $target_id);
                            $user_name[] = $target_info["name"] ? $target_info["name"] : "";

                            $header_view = new Views_Assessments_Reports_Header(array("class" => "space-below medium"));
                            $report_header_html = $header_view->render(
                                array(
                                    "target_name" => $target_info["name"] ? $target_info["name"] : "",
                                    "form_name" => $form->getTitle(),
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
                                    "is_evaluation" => $PROCESSED["is_evaluation"],
                                    "strip_comments" => !$PROCESSED["include_comments"],
                                    "include_commenter_id" => $PROCESSED["include_commenter_id"],
                                    "include_commenter_name" => $PROCESSED["include_commenter_name"],
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
                        Entrada_Utilities_Flashmessenger::addMessage($translate->_("No data found for the provided date range."), "error", $MODULE);
                        header('Location: ' . $_SERVER['HTTP_REFERER'] . "&error=true");
                    }
                    break;

                case "generate-bulk-event-feedback-pdf-reports":

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

                    $PROCESSED["eventtype_ids"] = null;
                    if (isset($request["eventtype_ids"])) {
                        foreach ($request["eventtype_ids"] as $target_id) {
                            if ($tmp_input = clean_input($target_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["eventtype_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }
                    if (!$PROCESSED["eventtype_ids"]) {
                        Entrada_Utilities_Flashmessenger::addMessage($translate->_("No event types specified."), "error", $MODULE);
                    }

                    $PROCESSED["form_ids"] = null;
                    if (isset($request["form_ids"])) {
                        foreach ($request["form_ids"] as $form_ids) {
                            if ($tmp_input = clean_input($form_ids, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["form_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }
                    if (!$PROCESSED["form_ids"]) {
                        Entrada_Utilities_Flashmessenger::addMessage($translate->_("No forms specified."), "error", $MODULE);
                    }

                    $PROCESSED["course_ids"] = null;
                    if (isset($request["course_ids"])) {
                        foreach ($request["course_ids"] as $course_id) {
                            if ($tmp_input = clean_input($course_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["course_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    $PROCESSED["event_ids"] = null;
                    if (isset($request["event_ids"])) {
                        foreach ($request["event_ids"] as $course_id) {
                            if ($tmp_input = clean_input($course_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["event_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    $PROCESSED["individual_event_option"] = false;
                    if (isset($request["individual_event_option"])) {
                        $PROCESSED["individual_event_option"] = ($request["individual_event_option"] === "true") ? true : false;
                    }

                    $PROCESSED["include_comments"] = false;
                    if (isset($request["include_comments"])) {
                        $PROCESSED["include_comments"] = ($request["include_comments"] === "true") ? true : false;
                    }

                    $PROCESSED["include_commenter_id"] = false;
                    if ($PROCESSED["include_comments"]) {
                        if (isset($request["include_commenter_id"])) {
                            $PROCESSED["include_commenter_id"] = ($request["include_commenter_id"] === "true") ? true : false;
                        }
                    }

                    $PROCESSED["include_commenter_name"] = false;
                    if ($PROCESSED["include_comments"]) {
                        if (isset($request["include_commenter_name"])) {
                            $PROCESSED["include_commenter_name"] = ($request["include_commenter_name"] === "true") ? true : false;
                        }
                    }

                    $PROCESSED["description"] = null;
                    if (isset($request["include_description"]) && $request["include_description"] === "true") {
                        if (isset($request["description"]) && $tmp_input = clean_input($request["description"], array("trim", "striptags"))) {
                            $PROCESSED["description"] = $tmp_input;
                        }
                    }

                    $PROCESSED["include_statistics"] = false;
                    if (isset($request["include_statistics"])) {
                        $PROCESSED["include_statistics"] = ($request["include_statistics"] === "true") ? true : false;
                    }

                    $PROCESSED["include_positivity"] = false;
                    if ($PROCESSED["include_statistics"]) {
                        if (isset($request["include_positivity"])) {
                            $PROCESSED["include_positivity"] = ($request["include_positivity"] === "true") ? true : false;
                        }
                    }

                    $PROCESSED["include_event_info_subheader"] = false;
                    if (isset($request["include_event_info_subheader"])) {
                        $PROCESSED["include_event_info_subheader"] = ($request["include_event_info_subheader"] === "true") ? true : false;
                    }


                    if (has_error()) {
                        header('Location: ' . $_SERVER['HTTP_REFERER'] . "&error=true");
                    }

                    $report_html = array();
                    $user_name = array();
                    $form_title = array();

                    foreach ($PROCESSED["form_ids"] as $form_id) {

                        $associated_record_subheaders = array();
                        $feedback_form_model = new Models_Event_Resource_FeedBackForm();
                        $events = array();

                        /**
                         * Generate extra headers based on specific associated records passed in. If the user wants
                         * subheader info but did not select specific events, we will have to figure out which ones are
                         * applicable based on the event types, which are mandatory.
                         */
                        if ($PROCESSED["include_event_info_subheader"] || $PROCESSED["individual_event_option"]) {

                            if (!isset($PROCESSED["event_ids"]) || empty($PROCESSED["event_ids"])) {
                                $PROCESSED["event_ids"] = array();
                                $assessment_model = new Models_Assessments_Assessor();

                                // Fetch all assessments for events that match the search criteria.
                                $tasks = $assessment_model->fetchAllWithCompletedProgressByFormIDsCourseIDsAssociatedRecords(
                                    null,
                                    $PROCESSED["course_ids"],
                                    $PROCESSED["form_ids"],
                                    $PROCESSED["start_date"],
                                    $PROCESSED["end_date"],
                                    "event_id",
                                    null,
                                    array(1),
                                    true
                                );

                                // Cross reference event feedback tasks and the provided event types.
                                foreach ($PROCESSED["eventtype_ids"] as $eventtype_id) {
                                    foreach ($tasks as $task) {
                                        $event_types = Models_Event_EventType::fetchAllByEventID($task["associated_record_id"]);
                                        if ($event_types) {
                                            /* @var $event_type Models_Event_EventType */
                                            foreach ($event_types as $event_type) {
                                                if ($event_type->getEventTypeID() == $eventtype_id) {
                                                    $PROCESSED["event_ids"][$task["associated_record_id"]] = $task["associated_record_id"];
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            if (isset($PROCESSED["event_ids"]) && !empty($PROCESSED["event_ids"])) {
                                foreach ($PROCESSED["event_ids"] as $associated_record_id) {
                                    // We only want to output events in the subheader that used this particular form.
                                    $feedback_form = $feedback_form_model->fetchRowByEventIDFormID($associated_record_id, $form_id);
                                    if (!$feedback_form) {
                                        continue;
                                    }

                                    $event = Models_Event::fetchRowByID($associated_record_id);
                                    if (!$event) {
                                        continue;
                                    }

                                    $teachers = array();
                                    $event_contacts = Models_Event_Contacts::fetchAllByEventID($event->getID());
                                    if ($event_contacts) {
                                        foreach ($event_contacts as $event_contact) {
                                            if ($event_contact->getContactRole() != "auditor") {
                                                $teacher_user = Models_User::fetchRowByID($event_contact->getProxyID());
                                                if ($teacher_user) {
                                                    $teachers[] = $teacher_user->getFullname(false);
                                                }
                                            }
                                        }
                                    }

                                    $events[$event->getID()] = array(
                                        "title" => $event->getEventTitle(),
                                        "date" => date("Y-m-d", $event->getEventStart()),
                                        "teachers" => $teachers
                                    );
                                }

                                $subheader_view = new Views_Assessments_Reports_Subheaders_LearningEvents(array());
                                $subheader_html = $subheader_view->render(array("events" => $events), false);

                                if ($subheader_html) {
                                    $associated_record_subheaders[] = $subheader_html;
                                }
                            }
                        }

                        /**
                         * Individual events option controls whether or not the report is completely aggregated, or is
                         * output on an individual event basis.
                         */
                        if ($PROCESSED["individual_event_option"]) {
                            foreach ($events as $event_id => $event_data) {

                                $associated_record_subheaders = array();
                                $subheader_view = new Views_Assessments_Reports_Subheaders_LearningEvents(array());
                                $subheader_html = $subheader_view->render(array("events" => array($events[$event_id])), false);

                                if ($subheader_html) {
                                    $associated_record_subheaders[] = $subheader_html;
                                }

                                $user_name[] = $event_data["title"];
                                $full_target_list_details = "<li>" . $translate->_("Report Start Date: ") . (is_null($PROCESSED["start_date"]) ? $translate->_("Not Set") : date("Y-m-d", $PROCESSED["start_date"])) . " " . $translate->_("Report End Date: ") . (is_null($PROCESSED["end_date"]) ? $translate->_("Not Set") : date("Y-m-d", $PROCESSED["end_date"])) . "</li>";
                                $form = Models_Assessments_Form::fetchRowByIDIncludeDeleted($form_id);
                                $form_title[] = $event_data["date"];

                                $construction = array(
                                    "target_value" => $PROCESSED["eventtype_ids"],
                                    "target_type" => "eventtype_id",
                                    "form_id" => $form_id,
                                    "start_date" => $PROCESSED["start_date"],
                                    "end_date" => $PROCESSED["end_date"],
                                    "adistribution_id" => null,
                                    "course_id" => $PROCESSED["course_ids"],
                                    "associated_record_type" => "event_id",
                                    "associated_record_ids" => array($event_id),
                                    "disable_internal_storage" => true
                                );

                                $reporting_utility = new Entrada_Utilities_Assessments_Reports($construction);
                                $report_data = $reporting_utility->generateReport("event_feedback");

                                // Don't bother generating empty data.
                                if (!$report_data || empty($report_data)) {
                                    continue;
                                }

                                // Generate header html
                                $header_view = new Views_Assessments_Reports_Header(array("class" => "space-below medium"));
                                $report_header_html = $header_view->render(
                                    array(
                                        "use_event_feedback_title" => true,
                                        "target_name" => $event_data["title"],
                                        "form_name" => $form->getTitle(),
                                        "enable_pdf_button" => false,
                                        "subheader_html" => $PROCESSED["include_event_info_subheader"] ? $associated_record_subheaders : array(),
                                        "list_info" => $full_target_list_details,
                                        "description" => $PROCESSED["description"]
                                    ),
                                    false
                                );

                                $report_view = new Views_Assessments_Reports_AssessmentReport(array("class" => "space-above space-below medium clearfix"));
                                $report_body_html = $report_view->render(
                                    array(
                                        "report_data" => $report_data,
                                        "is_evaluation" => true,
                                        "strip_comments" => !$PROCESSED["include_comments"],
                                        "include_commenter_id" => $PROCESSED["include_commenter_id"],
                                        "include_commenter_name" => $PROCESSED["include_commenter_name"],
                                        "additional_statistics" => $PROCESSED["include_statistics"],
                                        "include_positivity" => $PROCESSED["include_positivity"]
                                    ),
                                    false
                                );

                                $report_html[] = $report_header_html . $report_body_html;
                            }
                        } else {

                            $user_name[] = $translate->_("Feedback");
                            $full_target_list_details = "<li>" . $translate->_("Report Start Date: ") . (is_null($PROCESSED["start_date"]) ? $translate->_("Not Set") : date("Y-m-d", $PROCESSED["start_date"])) . " " . $translate->_("Report End Date: ") . (is_null($PROCESSED["end_date"]) ? $translate->_("Not Set") : date("Y-m-d", $PROCESSED["end_date"])) . "</li>";
                            $form = Models_Assessments_Form::fetchRowByIDIncludeDeleted($form_id);
                            if ($form) {
                                $form_title[] = $form->getTitle();
                            }

                            $construction = array(
                                "target_value" => $PROCESSED["eventtype_ids"],
                                "target_type" => "eventtype_id",
                                "form_id" => $form_id,
                                "start_date" => $PROCESSED["start_date"],
                                "end_date" => $PROCESSED["end_date"],
                                "adistribution_id" => null,
                                "course_id" => $PROCESSED["course_ids"],
                                "associated_record_type" => "event_id",
                                "associated_record_ids" => $PROCESSED["event_ids"],
                                "disable_internal_storage" => true
                            );

                            $reporting_utility = new Entrada_Utilities_Assessments_Reports($construction);
                            $report_data = $reporting_utility->generateReport("event_feedback");

                            // Don't bother generating empty data.
                            if (!$report_data || empty($report_data)) {
                                continue;
                            }

                            // Generate header html
                            $header_view = new Views_Assessments_Reports_Header(array("class" => "space-below medium"));
                            $report_header_html = $header_view->render(
                                array(
                                    "use_event_feedback_title" => true,
                                    "target_name" => "",
                                    "form_name" => $form->getTitle(),
                                    "enable_pdf_button" => false,
                                    "subheader_html" => $PROCESSED["include_event_info_subheader"] ? $associated_record_subheaders : array(),
                                    "list_info" => $full_target_list_details,
                                    "description" => $PROCESSED["description"]
                                ),
                                false
                            );

                            $report_view = new Views_Assessments_Reports_AssessmentReport(array("class" => "space-above space-below medium clearfix"));
                            $report_body_html = $report_view->render(
                                array(
                                    "report_data" => $report_data,
                                    "is_evaluation" => true,
                                    "strip_comments" => !$PROCESSED["include_comments"],
                                    "include_commenter_id" => $PROCESSED["include_commenter_id"],
                                    "include_commenter_name" => $PROCESSED["include_commenter_name"],
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
                        Entrada_Utilities_Flashmessenger::addMessage($translate->_("No data found for the specified date range."), "error", $MODULE);
                        header('Location: ' . $_SERVER['HTTP_REFERER'] . "&error=true");
                    }
                    break;

                case "get-user-learning-event-types" :

                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $data = array();
                    $event_types = Models_EventType::fetchAllByOrganisationID($ENTRADA_USER->getActiveOrganisation(), 1, $PROCESSED["search_value"]);
                    if ($event_types) {
                        /* @var $event_types Models_EventType */
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

                case "get-user-learning-events" :

                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $PROCESSED["eventtype_ids"] = null;
                    if (isset($request["eventtype_ids"])) {
                        $temp_list = explode(",", $request["eventtype_ids"]);
                        foreach ($temp_list as $eventtype_id) {
                            if ($tmp_input = clean_input($eventtype_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["eventtype_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }

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

                    $PROCESSED["form_ids"] = null;
                    if (isset($request["form_ids"])) {
                        $temp_list = explode(",", $request["form_ids"]);
                        foreach ($temp_list as $form_id) {
                            if ($tmp_input = clean_input($form_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["form_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    $PROCESSED["course_ids"] = null;
                    if (isset($request["course_ids"])) {
                        $temp_list = explode(",", $request["course_ids"]);
                        foreach ($temp_list as $course_id) {
                            if ($tmp_input = clean_input($course_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["course_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    $data = array();
                    if (!$ERROR) {

                        // Fetch all assessments for events that match the search criteria.
                        $assessment_model = new Models_Assessments_Assessor();
                        $tasks = $assessment_model->fetchAllWithCompletedProgressByFormIDsCourseIDsAssociatedRecords(
                            $PROCESSED["search_value"],
                            $PROCESSED["course_ids"],
                            $PROCESSED["form_ids"],
                            $PROCESSED["start_date"],
                            $PROCESSED["end_date"],
                            "event_id",
                            null,
                            array(1),
                            true
                        );

                        if ($tasks) {
                            /* @var $task Models_Assessments_Assessor */
                            foreach ($tasks as $task) {

                                // We need to fetch the event and event types to ensure that is meets the event type criteria, and to fetch associated data.
                                $event = Models_Event::fetchRowByID($task["associated_record_id"]);
                                if (!$event) {
                                    continue;
                                }
                                $event_types = Models_Event_EventType::fetchAllByEventID($event->getID());
                                if (!$event_types) {
                                    continue;
                                }

                                $match = false;
                                /* @var $event_type Models_Event_EventType */
                                foreach ($event_types as $event_type) {
                                    foreach ($PROCESSED["eventtype_ids"] as $eventtype_id) {
                                        if ($event_type->getEventTypeID() == $eventtype_id) {
                                            $match = true;
                                        }
                                    }
                                }
                                if (!$match) {
                                    continue;
                                }

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
                                        $title .= " (" . sprintf($translate->_("Event Teachers: %s"), $teacher_string) . ")";
                                    }
                                }

                                $data[$event->getID()] = array("target_id" => $event->getID(), "target_label" => $title);
                            }
                        }
                    }


                    if (!empty($data)) {
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No learning events were found.")));
                    }
                    break;

                case "get-user-faculty" :
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

                    $PROCESSED["course_ids"] = array();
                    if (isset($request["course_ids"])) {
                        $temp_list = explode(",", $request["course_ids"]);
                        foreach ($temp_list as $course_id) {
                            if ($tmp_input = clean_input($course_id, "int")) {
                                if ($tmp_input) {
                                    $PROCESSED["course_ids"][] = $tmp_input;
                                }
                            }
                        }
                    }

                    $data = array();
                    $admin = $ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true);

                    $assessment_user = new Entrada_Utilities_AssessmentUser();
                    $faculty = $assessment_user->getReportFaculty($ENTRADA_USER->getActiveId(), $ENTRADA_USER->getActiveOrganisation(), $PROCESSED["course_ids"], $admin, $PROCESSED["search_value"], $PROCESSED["limit"], $PROCESSED["offset"]);
                    if ($faculty) {
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
            }
            break;
    }
    exit;
}