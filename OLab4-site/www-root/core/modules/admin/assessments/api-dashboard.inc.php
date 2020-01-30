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
 * API to gather assessments dashboard information
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

    switch ($request_method) {
        case "POST":
            switch ($request["method"]) {
                case "hide-deleted-tasks":
                    $PROCESSED["deleted_task_ids"] = $PROCESSED["deleted_task_types"] = array();

                    if (isset($_POST["deleted_task_ids"]) && is_array($_POST["deleted_task_ids"]) && !empty($_POST["deleted_task_ids"])) {
                        $PROCESSED["deleted_task_ids"] = array_map(
                            function ($val) {
                                return clean_input($val, array("trim", "int"));
                            },
                            $_POST["deleted_task_ids"]
                        );
                    }

                    if (isset($_POST["deleted_task_types"]) && is_array($_POST["deleted_task_types"]) && !empty($_POST["deleted_task_types"])) {
                        $PROCESSED["deleted_task_types"] = array_map(
                            function ($val) {
                                return clean_input(strtolower($val), array("trim", "striptags"));
                            },
                            $_POST["deleted_task_types"]
                        );
                    }

                    if (!empty($PROCESSED["deleted_task_ids"]) && !empty($PROCESSED["deleted_task_types"])) {
                        $ctr = 0;
                        foreach ($_POST["deleted_task_ids"] as $deleted_task_id) {
                            $deleted_task = false;

                            if ($PROCESSED["deleted_task_types"][$ctr++] === "task") {
                                $deleted_task = Models_Assessments_AssessmentTarget::fetchRowByID($deleted_task_id, time());
                            } else {
                                $deleted_task = Models_Assessments_Distribution_Delegation::fetchRowByID($deleted_task_id);
                            }

                            if ($deleted_task) {
                                $deleted_task->setVisible(0);
                                if (!$deleted_task->update()) {
                                    add_error($translate->_("Unable to hide task(s)."));
                                    break;
                                }
                            } else {
                                add_error($translate->_("Deleted task not found. Unable to hide task(s)."));
                            }
                        }
                    } else {
                        add_error($translate->_("No task id list not provided."));
                    }

                    if (!$ERROR) {
                        echo json_encode(array("status" => "success", "data" => $translate->_("Successfully hid deleted task(s).")));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
            }
            break;
        case "GET" :
            switch ($request["method"]) {
                case "get-outstanding-tasks":
                    if (isset($_GET["task_type"]) && $tmp_input = clean_input(strtolower($_GET["task_type"]), array("trim", "striptags"))) {
                        $PROCESSED["task_type"] = $tmp_input;
                    } else {
                        add_error($translate->_("Task type not set."));
                    }

                    $PROCESSED["offset"] = 0;
                    if (isset($_GET["offset"]) && $tmp_input = clean_input($_GET["offset"], array("trim", "int"))) {
                        $PROCESSED["offset"] = $tmp_input;
                    }

                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $PROCESSED["schedule_type"] = "all";
                    if (isset($_GET["schedule_type"]) && $tmp_input = clean_input(strtolower($_GET["schedule_type"]), array("trim", "striptags"))) {
                        $PROCESSED["schedule_type"] = $tmp_input;
                    }

                    $incomplete_tasks = array();
                    if (!$ERROR) {
                        $incomplete_tasks = Entrada_Utilities_Assessments_DeprecatedAssessmentTask::getAllTasksForAssociatedLearnersAssociatedFaculty($PROCESSED["task_type"], $ENTRADA_USER->getActiveOrganisation(), $PROCESSED["offset"], 100, false, $PROCESSED["search_value"], $PROCESSED["schedule_type"]);
                        if ($incomplete_tasks) {
                            foreach ($incomplete_tasks as $key => $incomplete_task) {
                                $incomplete_tasks[$key]["full_name"] = $incomplete_task["assessor_type"] == "external" ? $incomplete_task["external_full_name"] : $incomplete_task["internal_full_name"];

                                if ($incomplete_task["task_type"] == "task") {
                                    $ctr = 0;
                                    $atarget_id_list = array();

                                    while (isset($incomplete_task[$ctr]) && isset($incomplete_task[$ctr]["atarget_id"])) {
                                        $atarget_id_list[] = $incomplete_task[$ctr++]["atarget_id"];
                                    }

                                    $incomplete_tasks[$key]["atarget_id"] = implode(",", $atarget_id_list);
                                }
                            }
                        }
                    }

                    if (!$ERROR && is_array($incomplete_tasks) && !empty($incomplete_tasks)) {
                        echo json_encode(array("status" => "success", "data" => array($incomplete_tasks, null)));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No tasks were found.")));
                    }
                    break;
                case "get-upcoming-tasks":
                    if (isset($_GET["task_type"]) && $tmp_input = clean_input(strtolower($_GET["task_type"]), array("trim", "striptags"))) {
                        $PROCESSED["task_type"] = $tmp_input;
                    } else {
                        add_error($translate->_("Task type not set."));
                    }

                    $PROCESSED["offset"] = 0;
                    if (isset($_GET["offset"]) && $tmp_input = clean_input($_GET["offset"], array("trim", "int"))) {
                        $PROCESSED["offset"] = $tmp_input;
                    }

                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $upcoming_tasks = array();
                    if (!$ERROR) {
                        $upcoming_tasks = Models_Assessments_FutureTaskSnapshot::fetchAllFutureTasksForAssociatedLearnersAssociatedFaculty($PROCESSED["task_type"], $ENTRADA_USER->getActiveOrganisation(), $PROCESSED["offset"], 100, false, $PROCESSED["search_value"]);

                        if ($upcoming_tasks) {
                            $upcoming_task_array_list = array();
                            $serialized_upcoming_task_list = array();

                            foreach ($upcoming_tasks as $key => $upcoming_task) {
                                $upcoming_task_array = $upcoming_task->toArray();

                                $upcoming_task_array["target_name"] = $upcoming_task->getTarget();

                                $distribution = Models_Assessments_Distribution::fetchRowByID($upcoming_task->getDistributionID());
                                if ($distribution) {
                                    $upcoming_task_array["title"] = $distribution->getTitle();
                                }

                                if ($upcoming_task->getAssessorType() == "external") {
                                    $external_user = Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($upcoming_task->getAssessorValue());
                                    if ($external_user) {
                                        $upcoming_task_array["full_name"] = $external_user->getFirstname() . " " . $external_user->getLastname();
                                    }
                                } else {
                                    $internal_user = Models_User::fetchRowByID($upcoming_task->getAssessorValue());
                                    if ($internal_user) {
                                        $upcoming_task_array["full_name"] = $internal_user->getFullname(false);
                                    }
                                }

                                $upcoming_task_array_list[] = $upcoming_task_array;
                                $serialized_upcoming_task_list[] = json_encode(serialize($upcoming_task));
                            }
                        }
                    }

                    if (!$ERROR && is_array($upcoming_tasks) && !empty($upcoming_tasks)) {
                        echo json_encode(array("status" => "success", "data" => array($upcoming_task_array_list, $serialized_upcoming_task_list)));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No tasks were found.")));
                    }
                    break;
                case "get-deleted-tasks":
                    if (isset($_GET["task_type"]) && $tmp_input = clean_input(strtolower($_GET["task_type"]), array("trim", "striptags"))) {
                        $PROCESSED["task_type"] = $tmp_input;
                    } else {
                        add_error($translate->_("Task type not set."));
                    }

                    $PROCESSED["offset"] = 0;
                    if (isset($_GET["offset"]) && $tmp_input = clean_input($_GET["offset"], array("trim", "int"))) {
                        $PROCESSED["offset"] = $tmp_input;
                    }

                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $PROCESSED["schedule_type"] = "all";
                    if (isset($_GET["schedule_type"]) && $tmp_input = clean_input(strtolower($_GET["schedule_type"]), array("trim", "striptags"))) {
                        $PROCESSED["schedule_type"] = $tmp_input;
                    }

                    $deleted_tasks = array();
                    if (!$ERROR) {
                        $deleted_tasks = Entrada_Utilities_Assessments_DeprecatedAssessmentTask::getAllDeletedTasksForAssociatedLearnersAssociatedFaculty($PROCESSED["task_type"], $ENTRADA_USER->getActiveOrganisation(), $PROCESSED["offset"], 100, false, $PROCESSED["search_value"], null, null, $PROCESSED["schedule_type"]);
                        if ($deleted_tasks) {
                            foreach ($deleted_tasks as $key => $deleted_task) {
                                $deleted_tasks[$key]["full_name"] = $deleted_task["assessor_type"] == "external" || $deleted_task["assessor_type"] == "external_assessor_id" ? $deleted_task["external_full_name"] : $deleted_task["internal_full_name"];
                            }
                        }
                    }

                    if (!$ERROR && is_array($deleted_tasks) && !empty($deleted_tasks)) {
                        echo json_encode(array("status" => "success", "data" => array($deleted_tasks, null)));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No tasks were found.")));
                    }
                    break;
            }
            break;
    }
    exit;
}