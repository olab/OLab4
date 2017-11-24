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
 * API to gather task information
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jordan lackey <jl250@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
if((!defined("PARENT_INCLUDED")) || (!defined("IN_ASSESSMENTS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed('assessments', 'read', false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
    exit;
} else {
    ob_clear_open_buffers();

    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));
	
	$request = ${"_" . $request_method};

    function add_delegated_tasks($PROXY_ID, $current_section = "assessments", $filters, $search_value, $start_date, $end_date, $limit = 0, $offset = 0) {
        $tasks = array();

        $delegation_assignments = Models_Assessments_Distribution_DelegationAssignment::fetchAllByTargetValue($PROXY_ID, $current_section, $filters, $search_value, $start_date, $end_date, $limit, $offset);
        $progress_records = Models_Assessments_Progress::fetchAllByTargetRecordID($PROXY_ID);
        if ($delegation_assignments && $progress_records) {
            foreach ($delegation_assignments as $key => $delegation_assignment) {
                foreach ($progress_records as $progress_record) {
                    if ($delegation_assignment->getAdistributionID() == $progress_record->getAdistributionID() && $delegation_assignment->getDassessmentID() == $progress_record->getDassessmentID() && $progress_record->getProgressValue() != "inprogress") {
                        unset($delegation_assignments[$key]);
                    }
                }
            }
        }

        if ($delegation_assignments) {
            foreach ($delegation_assignments as $delegation_assignment) {
                $distribution = Models_Assessments_Distribution::fetchRowByID($delegation_assignment->getAdistributionID());
                $assessment_utility = new Entrada_Utilities_Assessments_Base();
                $assessor = $assessment_utility->getUserByType($delegation_assignment->getAssessorValue(), ($delegation_assignment->getAssessorType() == "external" ? "external" : false));

                $assessment = Models_Assessments_Assessor::fetchRowByID($delegation_assignment->getDassessmentID());

                $schedule_details = "";
                $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($delegation_assignment->getAdistributionID());
                if ($distribution_schedule && $assessment) {
                    $schedule_record = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID());
                    if ($schedule_record) {
                        $schedule_details = Entrada_Utilities_Assessments_Base::getConcatenatedBlockString($delegation_assignment->getDassessmentID(), $schedule_record, $assessment->getRotationStartDate(), $assessment->getRotationEndDate(), $distribution->getOrganisationID());
                    }
                }

                $tasks[] = new Entrada_Utilities_Assessments_AssessmentTask(array(
                    "type" => "delegation",
                    "title" => $distribution->getTitle(),
                    "description" => $distribution->getDescription() ? $distribution->getDescription() : "No details provided.",
                    "start_date" => $assessment->getStartDate() ? $assessment->getStartDate() : false,
                    "end_date" => $assessment->getEndDate() ? $assessment->getEndDate() : false,
                    "adistribution_id" => $delegation_assignment->getAdistributionID(),
                    "distribution_deleted_date" => $distribution->getDeletedDate() ? $distribution->getDeletedDate() : false,
                    "delivery_date" => $assessment->getDeliveryDate() ? $assessment->getDeliveryDate() : $delegation_assignment->getCreatedDate() ? $delegation_assignment->getCreatedDate() : false,
                    "schedule_details" => $schedule_details ? $schedule_details : false,
                    "rotation_start_date" => $assessment->getRotationStartDate() ? $assessment->getRotationStartDate() : false,
                    "rotation_end_date" => $assessment->getRotationEndDate() ? $assessment->getRotationEndDate() : false,
                    "dassessment_id" => $delegation_assignment->getDassessmentID(),
                    "target_id" => $delegation_assignment->getTargetValue(),
                    "assessor_value" => $delegation_assignment->getAssessorValue(),
                    "assessor" => $assessor->getFirstname() . " " . $assessor->getLastName(),
                    "assessor_type" => $delegation_assignment->getAssessorType()
                ));
            }
        }

        return $tasks;
    }

    switch ($request_method) {
        case "POST" :
            switch ($request["method"]) {
                case "save-assessments-filters" :
                    Entrada_Utilities_Assessments_AssessmentTask::setFilterPreferences($request, "assessments");
                    echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully saved the selected filters")));
                    break;
                case "save-faculty-filters" :
                    Entrada_Utilities_Assessments_AssessmentTask::setFilterPreferences($request, "faculty");
                    echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully saved the selected filters")));
                    break;
                case "save-learner-filters" :
                    Entrada_Utilities_Assessments_AssessmentTask::setFilterPreferences($request, "learner");
                    echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully saved the selected filters")));
                    break;
                case "remove-assessments-filters" :
                    Entrada_Utilities_Assessments_AssessmentTask::removeAllFilters("assessments");
                    echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully removed all filters")));
                    break;
                case "remove-faculty-filters" :
                    Entrada_Utilities_Assessments_AssessmentTask::removeAllFilters("faculty");
                    echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully removed all filters")));
                    break;
                case "remove-learner-filters" :
                    Entrada_Utilities_Assessments_AssessmentTask::removeAllFilters("learner");
                    echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully removed all filters")));
                    break;
            }
        break;
        case "GET" :
            switch ($request["method"]) {
                case "get-tasks" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = null;
                    }

                    if (isset($request["start_date"]) && $request["start_date"] != "" && $tmp_input = clean_input(strtotime($request["start_date"]. " 00:00:00"), "int")) {
                        if ($tmp_input) {
                            $PROCESSED["start_date"] = $tmp_input;
                        } else {
                            $PROCESSED["start_date"] = null;
                        }
                    } else {
                        $PROCESSED["start_date"] = null;
                    }

                    if (isset($request["end_date"]) && $request["end_date"] != "" && $tmp_input = clean_input(strtotime($request["end_date"]. "23:59:59"), "int")) {
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
                        $PROCESSED["current_page"] = "";
                    }

                    if (isset($request["current_section"]) && $tmp_input = clean_input(strtolower($request["current_section"]), array("trim", "striptags"))) {
                        $PROCESSED["current_section"] = $tmp_input;
                    } else {
                        $PROCESSED["current_section"] = "";
                    }

                    if (isset($request["proxy_id"]) && $request["proxy_id"] != "" && $tmp_input = clean_input($request["proxy_id"], "int")) {
                        $PROCESSED["proxy_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Proxy id not set."));
                    }

                    if (isset($request["org_id"]) && $request["org_id"] != "" && $tmp_input = clean_input($request["org_id"], "int")) {
                        $PROCESSED["org_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Organisation not set."));
                    }

                    if (isset($request["is_external"]) && $request["is_external"] != "" && $tmp_input = clean_input($request["is_external"], "bool")) {
                        $PROCESSED["is_external"] = $tmp_input;
                    } else {
                        $PROCESSED["is_external"] = false;
                    }

                    $filters = array();
                    if ($PROCESSED["current_section"] != "" && isset($_SESSION[APPLICATION_IDENTIFIER][$PROCESSED["current_section"]]["tasks"]["selected_filters"])) {
                        $filters = $_SESSION[APPLICATION_IDENTIFIER][$PROCESSED["current_section"]]["tasks"]["selected_filters"];
                    }

                    if (isset($request["limit"]) && $request["limit"] != "" && $tmp_input = clean_input($request["limit"], "int")) {
                        $PROCESSED["limit"] = $tmp_input;
                    } else {
                        $PROCESSED["limit"] = 0;
                    }

                    if (isset($request["offset"]) && $request["offset"] != "" && $tmp_input = clean_input($request["offset"], "int")) {
                        $PROCESSED["offset"] = $tmp_input;
                    } else {
                        $PROCESSED["offset"] = 0;
                    }

                    $tasks = false;
                    switch ($PROCESSED["current_page"]) {
                        case "completed_on_me":
                            $progress_tasks = Entrada_Utilities_Assessments_AssessmentTask::getAssessmentProgressOnUser($PROCESSED["proxy_id"], $PROCESSED["org_id"], $PROCESSED["current_section"], true, 1, $filters, $PROCESSED["search_value"], $PROCESSED["start_date"], $PROCESSED["end_date"]);
                            $tasks = $progress_tasks["complete"];
                            Entrada_Utilities_Assessments_AssessmentTask::removeTasksBySubType($tasks, $filters);
                            break;
                        case "completed":
                            $tasks = Entrada_Utilities_Assessments_AssessmentTask::getAllFilteredTasks($PROCESSED["proxy_id"], $filters, $PROCESSED["search_value"], $PROCESSED["start_date"], $PROCESSED["end_date"], $PROCESSED["current_page"], $PROCESSED["current_section"], $PROCESSED["is_external"], false, $PROCESSED["limit"], $PROCESSED["offset"]);
                            break;
                        case "incomplete":
                            $tasks = Entrada_Utilities_Assessments_AssessmentTask::getAllFilteredTasks($PROCESSED["proxy_id"], $filters, $PROCESSED["search_value"], $PROCESSED["start_date"], $PROCESSED["end_date"], $PROCESSED["current_page"], $PROCESSED["current_section"], $PROCESSED["is_external"], true);
                            break;
                        case "pending" :
                            $pending_task = Models_Assessments_CurrentTaskSnapshot::fetchAllByTargetTypeTargetValueSortDeliveryDateRotationDatesDesc("proxy_id", $PROCESSED["proxy_id"], $PROCESSED["current_section"], $filters, $PROCESSED["search_value"], $PROCESSED["start_date"], $PROCESSED["end_date"]);
                            if ($pending_task) {
                                $tasks = Entrada_Utilities_AssessmentsSnapshot::groupTasksObjects($pending_task);
                                Entrada_Utilities_Assessments_AssessmentTask::removeTasksBySubType($tasks, $filters);
                            }

                            $delegated_tasks = add_delegated_tasks($PROCESSED["proxy_id"], $PROCESSED["current_section"], $filters, $PROCESSED["search_value"], $PROCESSED["start_date"], $PROCESSED["end_date"]);
                            if ($tasks) {
                                $tasks = array_merge($tasks, $delegated_tasks);
                            } else if ($delegated_tasks) {
                                $tasks = $delegated_tasks;
                            }

                            $progress_tasks = Entrada_Utilities_Assessments_AssessmentTask::getAssessmentProgressOnUser($PROCESSED["proxy_id"], $PROCESSED["org_id"], $PROCESSED["current_section"], true, 1, $filters, $PROCESSED["search_value"], $PROCESSED["start_date"], $PROCESSED["end_date"]);
                            if ($progress_tasks["inprogress"]) {
                                if ($tasks) {
                                    $tasks = array_merge($tasks, $progress_tasks["inprogress"]);
                                } else {
                                    $tasks = $progress_tasks["inprogress"];
                                }
                                Entrada_Utilities_Assessments_AssessmentTask::removeTasksBySubType($tasks, $filters);
                            }
                            break;
                        case "upcoming" :
                            $upcoming_tasks = Models_Assessments_FutureTaskSnapshot::fetchAllByTargetTypeTargetValueSortDeliveryDateRotationDatesDesc("proxy_id", $PROCESSED["proxy_id"], $PROCESSED["current_section"], $filters, $PROCESSED["search_value"], $PROCESSED["start_date"], $PROCESSED["end_date"]);
                            if ($upcoming_tasks) {
                                $tasks = Entrada_Utilities_AssessmentsSnapshot::groupTasksObjects($upcoming_tasks);
                                Entrada_Utilities_Assessments_AssessmentTask::removeTasksBySubType($tasks, $filters);
                            }
                            break;
                        case "future" :
                            $future_tasks = Models_Assessments_FutureTaskSnapshot::fetchAllByAssessorTypeAssessorValueSortDeliveryDateRotationDatesDesc($PROCESSED["proxy_id"], $PROCESSED["current_section"], $filters, $PROCESSED["search_value"], $PROCESSED["start_date"], $PROCESSED["end_date"], $PROCESSED["is_external"]);
                            if ($future_tasks) {
                                $tasks = Entrada_Utilities_AssessmentsSnapshot::groupTasksObjects($future_tasks);
                                Entrada_Utilities_Assessments_AssessmentTask::removeTasksBySubType($tasks, $filters);
                            }
                            break;
                    }

                    $data = array();
                    if (!$ERROR && $tasks) {
                        foreach ($tasks as $task) {
                            $targets = false;
                            if (is_array($task->getTargets())) {
                                $targets = $task->getTargets();
                            }

                            $progress_id = 0;
                            if ($PROCESSED["is_external"]) {
                                $progress_record = Models_Assessments_Progress::fetchRowByAdistributionIDAssessorTypeAssessorValueTargetRecordIDDAssessmentID($task->getDistributionID(), "external", $task->getAssessorValue(), $task->getTargetID(), $task->getDassessmentID());
                                if ($task->getType() == "assessment" && $progress_record && $progress_record->getProgressValue() == "complete") {
                                    $progress_id = $progress_record->getID();
                                    $task->setUrl($task->getUrl() . "&aprogress_id=" . $progress_record->getID());
                                }
                            } else {
                                $progress_id = $task->getProgressID();
                            }

                            $assessor_name = $task->getAssessor();
                            if (is_null($assessor_name) || !$assessor_name || $task->getType() == "approver") {
                                if ($task->getAssessorValue()) {
                                    $assessor = Models_User::fetchRowByID($task->getType() == "approver" ? $PROCESSED["proxy_id"] : $task->getAssessorValue());
                                    if ($assessor) {
                                        $assessor_name = $assessor->getFullname(false);
                                    }
                                }
                            }

                            $completed_date = null;
                            if ($task->getType() == "delegation") {
                                $delegation = Models_Assessments_Distribution_Delegation::fetchRowByID($task->getDassessmentID());
                                if ($delegation) {
                                    $completed_date = $delegation->getCompletedDate() ? html_encode(date("M j, Y", $delegation->getCompletedDate())) : false;
                                }
                            } else {
                                $completed_date = $task->getCompletedDate() ? html_encode(date("M j, Y", $task->getCompletedDate())) : false;
                            }

                            $data[] = array(
                                "type" => $task->getType(),
                                "dassessment_id" => $task->getDassessmentID(),
                                "delivery_date" => $task->getDeliveryDate() ? html_encode(date("M j, Y", $task->getDeliveryDate())) : false,
                                "delivery_date_timestamp" => $task->getDeliveryDate() ? $task->getDeliveryDate() : false,
                                "target_id" => $task->getTargetID(),
                                "title" => $task->getTitle(),
                                "description" => $task->getDescription(),
                                "details" => $task->getDetails(),
                                "assessor_value" => $task->getType() == "approver" ? $PROCESSED["proxy_id"] : $task->getAssessorValue(),
                                "adistribution_id" => $task->getDistributionID(),
                                "url" => $task->getUrl(),
                                "rotation_start_date" => $task->getRotationStartDate() ? html_encode(date("M j, Y", $task->getRotationStartDate())) : false,
                                "rotation_end_date" => $task->getRotationEndDate() ? html_encode(date("M j, Y", $task->getRotationEndDate())) : false,
                                "start_date" => $task->getStartDate() ? html_encode(date("M j, Y", $task->getStartDate())) : false,
                                "end_date" => $task->getEndDate() ? html_encode(date("M j, Y", $task->getEndDate())) : false,
                                "schedule_details" => $task->getScheduleDetails() ? $task->getScheduleDetails() : false,
                                "targets" => $task->getTargets(),
                                "targets_pending" => $targets && array_key_exists("pending", $targets) ? $targets["pending"] : false,
                                "targets_inprogress" => $targets && array_key_exists("inprogress", $targets) ? $targets["inprogress"] : false,
                                "total_targets" => $targets && array_key_exists("total", $targets) ? $targets["total"] : false,
                                "target_attempts" => $task->getCompletedAttempts(),
                                "completed_targets" => $targets && array_key_exists("complete", $targets) ? $targets["complete"] : false,
                                "delegated_by" => $task->getDelegatedBy(),
                                "delegated_date" => $task->getDelegationCompletedDate() ? html_encode(date("M j, Y", $task->getDelegationCompletedDate())) : false,
                                "event_start_date" => $task->getEventTimeframeStart() ? $task->getEventTimeframeStart() : false,
                                "event_end_date" => $task->getEventTimeframeEnd() ? $task->getEventTimeframeEnd() : false,
                                "event_details" => $task->getEventDetails() ? $task->getEventDetails() : false,
                                "assessor" => $assessor_name ? $assessor_name : "",
                                "assessor_type" => $task->getAssessorType(),
                                "group" => $task->getGroup() ? $task->getGroup() : "",
                                "role" => $task->getRole() ? str_replace("_", " ", $task->getRole()) : "",
                                "aprogress_id" => $progress_id,
                                "target_names_pending" => $task->getTargetNamesPending(),
                                "target_names_inprogress" => $task->getTargetNamesInprogress(),
                                "target_names_complete" => $task->getTargetNamesComplete(),
                                "target_names_potential" => $task->getTargetNamesPotential(),
                                "target_info" => json_encode($task->getTargetInfo()),
                                "completed_date" => $completed_date
                            );
                        }
                    }

                    if (!$ERROR && count($data) >= 1) {
                        echo json_encode(array("status" => "success", "data" => $data));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No tasks were found.")));
                    }
                    break;
                case "get-distribution-methods" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $data = array();
                    $distribution_methods = new Models_Assessments_Distribution_Method();
                    $methods = $distribution_methods->fetchAllRecords();

                    foreach ($methods as $method) {
                        $data[] = array("target_id" => $method->getID(), "target_label" => $translate->_($method->getTitle()));
                    }

                    echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));

                    break;
                case "get-user-cperiod" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $user_curriculum_types = Models_Curriculum_Type::fetchAllByOrg($ENTRADA_USER->getActiveOrganisation());
                    if ($user_curriculum_types) {
                        $data = array();
                        foreach ($user_curriculum_types as $curriculum_type) {
                            $curriculum_periods = Models_Curriculum_Period::fetchAllByCurriculumTypeSearchTerm($curriculum_type->getID(), $PROCESSED["search_value"]);
                            if ($curriculum_periods) {
                                foreach ($curriculum_periods as $curriculum_period) {
                                    $data[] = array("target_id" => $curriculum_period->getCperiodID(), "target_label" => ($curriculum_period->getCurriculumPeriodTitle() ? $curriculum_period->getCurriculumPeriodTitle() : date("M j, Y", $curriculum_period->getStartDate()) . " to " . date("M j, Y", $curriculum_period->getFinishDate())));
                                }
                            }
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No curriculum periods were found.")));
                    }
                    break;
                case "get-user-program" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $user_courses = Models_Course::getUserCourses($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation(), $PROCESSED["search_value"]);
                    if ($user_courses) {
                        $data = array();
                        foreach ($user_courses as $course) {
                            $data[] = array("target_id" => $course->getID(), "target_label" => $course->getCourseName());
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No programs were found.")));
                    }
                    break;
                case "get-task-types" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $data = array();
                    $data[] = array("target_id" => 1, "target_label" => $translate->_("Assessment of Learner"));
                    $data[] = array("target_id" => 2, "target_label" => $translate->_("Evaluation of Faculty"));
                    $data[] = array("target_id" => 3, "target_label" => $translate->_("Evaluation of Event"));
                    $data[] = array("target_id" => 4, "target_label" => $translate->_("Evaluation of Rotation"));
                    $data[] = array("target_id" => 5, "target_label" => $translate->_("Reviewer"));

                    echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));

                    break;
            }
            break;
    }
    exit;
}