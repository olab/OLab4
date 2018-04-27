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
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016, 2017 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_ASSESSMENTS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed('assessments', 'read', false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:" . html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" . html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance.");

    echo display_error();

    application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] does not have access to this module [" . $MODULE . "]");
    exit;
} else {
    ob_clear_open_buffers();

    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));
    $request = ${"_" . $request_method};
    $assessment_tasks = new Entrada_Assessments_Tasks(array("actor_proxy_id" => $ENTRADA_USER->getActiveId(), "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()));

    switch ($request_method) {
        case "POST" :
            switch ($request["method"]) {
                case "save-assessments-filters" :
                    Entrada_Utilities_Assessments_DeprecatedAssessmentTask::setFilterPreferences($request, "assessments");
                    echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully saved the selected filters")));
                    break;
                case "save-faculty-filters" :
                    Entrada_Utilities_Assessments_DeprecatedAssessmentTask::setFilterPreferences($request, "faculty");
                    echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully saved the selected filters")));
                    break;
                case "save-learner-filters" :
                    Entrada_Utilities_Assessments_DeprecatedAssessmentTask::setFilterPreferences($request, "learner");
                    echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully saved the selected filters")));
                    break;
                case "remove-assessments-filters" :
                    Entrada_Utilities_Assessments_DeprecatedAssessmentTask::removeAllFilters("assessments");
                    echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully removed all filters")));
                    break;
                case "remove-faculty-filters" :
                    Entrada_Utilities_Assessments_DeprecatedAssessmentTask::removeAllFilters("faculty");
                    echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully removed all filters")));
                    break;
                case "remove-learner-filters" :
                    Entrada_Utilities_Assessments_DeprecatedAssessmentTask::removeAllFilters("learner");
                    echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully removed all filters")));
                    break;
                case "get-assessments-metadata":
                    $PROCESSED["assessments"] = array_key_exists("assessments", $request)
                        ? is_array($request["assessments"]) ?
                            $request["assessments"]
                            : array()
                        : array();

                    // Ensure PROCESSED has valid array elements passed in.
                    // We only accept dassessment_id and an optional aprogress_id
                    foreach ($PROCESSED["assessments"] as $i => $given_assessment_meta) {
                        if (!array_key_exists("aprogress_id", $given_assessment_meta)) {
                            $given_assessment_meta["aprogress_id"] = null;
                        }
                        $keys = array_keys($given_assessment_meta);
                        sort($keys);
                        if ($keys != array("aprogress_id", "dassessment_id")) {
                            unset($PROCESSED["assessments"][$i]); // this one is malformed, remove it
                        } else {
                            // Indecies are good, so verify the content
                            $dassessment_id = clean_input($given_assessment_meta["dassessment_id"], array("trim", "int"));
                            if (!$dassessment_id) {
                                unset($PROCESSED["assessments"][$i]); // this one is malformed, remove it
                            } else {
                                $aprogress_id = clean_input($given_assessment_meta["aprogress_id"], array("trim", "int"));
                                $PROCESSED["assessments"][$i]["dassessment_id"] = $dassessment_id ? $dassessment_id : null;
                                $PROCESSED["assessments"][$i]["aprogress_id"] = $aprogress_id ? $aprogress_id : null;
                            }
                        }
                    }
                    if (empty($PROCESSED["assessments"])) {
                        add_error($translate->_("No tasks specified."));
                    }
                    $assessment_meta = array();
                    $assessment_api = new Entrada_Assessments_Assessment(
                        array(
                            "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()
                        )
                    );
                    $assessment_api->setDatasetLimit(array("assessor", "targets", "progress"));

                    // Fetch metadata for assessment
                    if (!has_error()) {
                        foreach ($PROCESSED["assessments"] as $processed_assessment) {
                            $key = "{$processed_assessment["dassessment_id"]}-{$processed_assessment["aprogress_id"]}";
                            $assessment_api->setDassessmentID($processed_assessment["dassessment_id"]);
                            $assessment_api->setAprogressID($processed_assessment["aprogress_id"]);
                            $assessment_data = $assessment_api->fetchAssessmentData();
                            
                            $assessment_meta[$key]["dassessment_id"] = $processed_assessment["dassessment_id"];
                            $assessment_meta[$key]["aprogress_id"] = $processed_assessment["aprogress_id"];
                            $assessment_meta[$key]["delivery_date"] = $assessment_data["assessment"]["delivery_date"];
                            $assessment_meta[$key]["delivery_date_formatted"] = date("Y-m-d", $assessment_data["assessment"]["delivery_date"]);
                            $assessment_meta[$key]["assessor"][$assessment_data["assessor"]["assessor_id"]] = $assessment_data["assessor"]["full_name"];
                            $assessment_meta[$key]["targets"] = array();
                            if (!empty($assessment_data["targets"])) {
                                $targets = array();
                                if ($processed_assessment["aprogress_id"]) {
                                    // If one specific progress ID was given, then we use only that relevant target.
                                    // Otherwise, we'll just use all targets.
                                    $assessment_data["targets"] = array($assessment_api->getCurrentAssessmentTarget());
                                }
                                foreach ($assessment_data["targets"] as $target) {
                                    $assessment_meta[$key]["targets"][$target["atarget_id"]] = $target["target_name"];
                                }
                            }
                        }
                    }
                    if (empty($assessment_meta)) {
                        add_error($translate->_("Unable to fetch assessment meta data."));
                    }
                    if (has_error()) {
                        echo json_encode(array("status" => "error", "data" => array($ERRORSTR)));
                    } else {
                        echo json_encode(array("status" => "success", "data" => $assessment_meta));
                    }
                    break;
                case "delete-tasks":
                    $PROCESSED["task_type"] = null;
                    if (isset($request["task_type"]) && $tmp_input = clean_input($request["task_type"], array("trim", "notags"))) {
                        $PROCESSED["task_type"] = $tmp_input;
                    } else {
                        add_error($translate->_("Task type not specified."));
                    }
                    if (isset($request["task_id"]) && $tmp_input = clean_input($request["task_id"], array("trim", "int"))) {
                        $PROCESSED["task_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Task ID not specified."));
                    }
                    $PROCESSED["dassessment_id"] = null; // Optional; only used for current assessment tasks
                    if (isset($request["dassessment_id"]) && $tmp_input = clean_input($request["dassessment_id"], array("trim", "int"))) {
                        $PROCESSED["dassessment_id"] = $tmp_input;
                    }
                    $PROCESSED["adistribution_id"] = null; // Optional; only used for delegations and future tasks
                    if (isset($request["adistribution_id"]) && $tmp_input = clean_input($request["adistribution_id"], array("trim", "int"))) {
                        $PROCESSED["adistribution_id"] = $tmp_input;
                    }
                    $PROCESSED["reason_id"] = null;
                    if (isset($request["reason_id"]) && $tmp_input = clean_input($request["reason_id"], array("trim", "int"))) {
                        $PROCESSED["reason_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Please indicate why you are removing this assessment task from your task list."));
                    }
                    $PROCESSED["reason_notes"] = null;
                    if (isset($request["reason_notes"])
                        && $tmp_input = clean_input($request["reason_notes"], array("trim", "notags"))
                    ) {
                        $PROCESSED["reason_notes"] = $tmp_input;
                    }
                    // Determine if a reason note is required
                    if ($PROCESSED["reason_id"]) {
                        $reason = Models_Assessments_TaskDeletedReason::fetchRowByID($PROCESSED["reason_id"]);
                        if (!$reason) {
                            add_error($translate->_("Invalid task deletion reason selected."));
                        } else {
                            if ($reason->getNotesRequired() && !$PROCESSED["reason_notes"]) {
                                add_error($translate->_("Please indicate why you are removing this assessment task from your task list."));
                            }
                        }
                    }
                    // Parse the task data given to us.
                    $PROCESSED["task_data"] = array();
                    if (array_key_exists("task_data", $request)) {
                        $PROCESSED["task_data"] = $assessment_tasks->explodeTargetList($request["task_data"], true);
                    }
                    if ($PROCESSED["task_type"] == "assessment") {
                        if (empty($PROCESSED["task_data"])) {
                            add_error($translate->_("Malformed task data"));
                        }
                        if (!$PROCESSED["dassessment_id"]) {
                            add_error($translate->_("No task ID specified."));
                        }
                        if (!has_error()) {
                            // For standard assessments, PROCESSED[task_id] is either the atarget_id or dassessment_id (depending on context)
                            // Instantiate an assessment api object
                            $assessment_api = new Entrada_Assessments_Assessment(
                                array(
                                    "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                                    "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                                    "dassessment_id" => $PROCESSED["dassessment_id"],
                                    "limit_dataset" => array("targets")
                                )
                            );
                            // For assessments, we iterate through the task target list and delete them.
                            foreach ($PROCESSED["task_data"] as $target_data) {
                                if ($target_data["atarget_id"]) {
                                    if (!$assessment_api->deleteAssessmentByTarget($target_data["atarget_id"], $PROCESSED["reason_id"], $PROCESSED["reason_notes"])) {
                                        foreach ($assessment_api->getErrorMessages() as $error_message) {
                                            add_error($error_message);
                                        }
                                    }
                                }
                            }
                        }
                    } else if ($PROCESSED["task_type"] == "future_assessment") {
                        // For future assessments, PROCESSED[task_id] is the future_task_id
                        // Instantiate an assessment api object
                        $assessment_api = new Entrada_Assessments_Assessment(
                            array(
                                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                            )
                        );
                        // For future assessments, we must create the assessment target record as deleted.
                        // We pass the future task ID to the assessment API object to perform the necessary logic for deletions.
                        if (!$assessment_api->deleteFutureTaskByID($PROCESSED["task_id"], $PROCESSED["reason_id"], $PROCESSED["reason_notes"])) {
                            foreach ($assessment_tasks->getErrorMessages() as $error_message) {
                                add_error($error_message);
                            }
                        }
                    } else if ($PROCESSED["task_type"] == "delegation") {
                        // For delegations, PROCESSED[task_id] is the addelegation_id.
                        // We simply invoke the deletion method of the distribution delegation object.
                        $distribution_delegation = new Entrada_Utilities_Assessments_DistributionDelegation(
                            array(
                                "addelegation_id" => $PROCESSED["task_id"],
                                "adistribution_id" => $PROCESSED["adistribution_id"]
                            )
                        );
                        if (!$distribution_delegation->deleteDelegation($ENTRADA_USER->getActiveId(), $PROCESSED["reason_id"], $PROCESSED["reason_notes"])) {
                            foreach ($distribution_delegation->getErrorMessages() as $error_message) {
                                add_error($error_message);
                            }
                        }
                    } else {
                        // Anything else (including completed tasks and approval tasks) are ignored
                        add_error($translate->_("Invalid task deletion request."));
                    }
                    if (has_error()) {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    } else {
                        echo json_encode(array("status" => "success", "data" => array($translate->_("Successfully deleted task."))));
                    }
                    break;
                case "send-reminders":

                    if (isset($request["subject_id"]) && $tmp_input = clean_input($request["subject_id"], array("trim", "int"))) {
                        $PROCESSED["subject_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Subject ID not specified."));
                    }

                    if (isset($request["subject_type"]) && $tmp_input = clean_input($request["subject_type"], array("trim", "module"))) {
                        $PROCESSED["subject_type"] = $tmp_input;
                    } else {
                        add_error($translate->_("Subject type not specified."));
                    }

                    if (has_error()) {
                        exit(json_encode(array("status" => "error", "data" => $ERRORSTR)));
                    }

                    /**
                     * Can send reminders to:
                     *  - Delegations
                     *  - Assessments as a whole (i.e., send to a dassessment_id)
                     *  - Approval for assessment progress
                     */

                    $PROCESSED["delegations_to_remind"] = array();
                    $PROCESSED["approvals_to_remind"] = array();
                    $PROCESSED["assessments_to_remind"] = array();

                    // Parse the task data given to us.
                    if (array_key_exists("reminder_data", $request)) {
                        foreach ($request["reminder_data"] as $reminder_data) {
                            // Validate that the given reminder data actually contains useful data
                            // task_id, task_type, dassessment_id, aprogress_id, adistribution_id
                            if (!array_key_exists("task_type", $reminder_data)) {
                                add_error($translate->_("Malformed reminder data."));
                                exit(json_encode(array("status" => "error", "data" => $ERRORSTR)));
                            }
                            switch ($reminder_data["task_type"]) {
                                case "delegation":
                                    if (!isset($reminder_data["adistribution_id"]) || !isset($reminder_data["task_id"])) {
                                        add_error($translate->_("Invalid delegation data."));
                                    }
                                    $tmp_adistribution_id = clean_input($reminder_data["adistribution_id"], array("trim","int"));
                                    $tmp_addelegation_id = clean_input($reminder_data["task_id"], array("trim","int"));
                                    if (!$tmp_adistribution_id || !$tmp_addelegation_id) {
                                        add_error($translate->_("Invalid delegation IDs."));
                                    }
                                    if (has_error()) {
                                        exit(json_encode(array("status" => "error", "data" => $ERRORSTR)));
                                    }
                                    $PROCESSED["delegations_to_remind"][] = array(
                                        "adistribution_id" => $tmp_adistribution_id,
                                        "addelegation_id" => $tmp_addelegation_id
                                    );
                                    break;

                                case "assessment":
                                    if (!isset($reminder_data["dassessment_id"])) {
                                        add_error($translate->_("Invalid assessment data."));
                                    }
                                    if (!$tmp_dassessment_id = clean_input($reminder_data["dassessment_id"], array("trim","int"))) {
                                        add_error($translate->_("Invalid assessment ID."));
                                    }
                                    if (has_error()) {
                                        exit(json_encode(array("status" => "error", "data" => $ERRORSTR)));
                                    }
                                    $PROCESSED["assessments_to_remind"][] = array(
                                        "dassessment_id" => $tmp_dassessment_id
                                    );
                                    break;

                                case "approval":
                                    if (!isset($reminder_data["dassessment_id"]) || !isset($reminder_data["aprogress_id"])) {
                                        add_error($translate->_("Invalid approval data."));
                                    }
                                    $tmp_dassessment_id = clean_input($reminder_data["dassessment_id"], array("trim","int"));
                                    $tmp_aprogress_id = clean_input($reminder_data["aprogress_id"], array("trim","int"));
                                    if (!$tmp_aprogress_id || !$tmp_dassessment_id) {
                                        add_error($translate->_("Invalid approval IDs."));
                                    }
                                    if (has_error()) {
                                        exit(json_encode(array("status" => "error", "data" => $ERRORSTR)));
                                    }
                                    $PROCESSED["approvals_to_remind"][] = array(
                                        "dassessment_id" => $tmp_dassessment_id,
                                        "aprogress_id" => $tmp_aprogress_id
                                    );
                                    break;

                                default:
                                    add_error($translate->_("Invalid reminder type."));
                                    exit(json_encode(array("status" => "error", "data" => $ERRORSTR)));
                            }
                        }

                        /**
                         * For the parsed data, fire off the notifications
                         **/
                        // Queue the delegation reminders. We're always notifying the delegator.
                        if (!empty($PROCESSED["delegations_to_remind"])) {
                            foreach ($PROCESSED["delegations_to_remind"] as $reminder) {
                                $delegation = Models_Assessments_Distribution_Delegation::fetchRowByID($reminder["addelegation_id"]);
                                $distribution = Models_Assessments_Distribution::fetchRowByID($reminder["adistribution_id"]);
                                if ($delegation
                                    && $distribution
                                    && $delegation->getDelegatorID() == $PROCESSED["subject_id"]
                                    && $delegation->getDelegatorType() == $PROCESSED["subject_type"]
                                ) {
                                    $assessment_tasks->queueDelegatorNotifications(
                                        $distribution,
                                        $delegation,
                                        $delegation->getDelegatorID(),
                                        $distribution->getNotifications(),
                                        false,
                                        true,
                                        true
                                    );
                                }
                            }
                        }
                        // Queue Approval reminders. The reminder is for the current subject (who presumably is the approver)
                        if (!empty($PROCESSED["approvals_to_remind"])) {
                            foreach ($PROCESSED["approvals_to_remind"] as $reminder) {
                                $assessment_api = new Entrada_Assessments_Assessment(
                                    array(
                                        "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                                        "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                                        "dassessment_id" => $reminder["dassessment_id"],
                                        "aprogress_id" => $reminder["aprogress_id"],
                                        "limit_dataset" => array("distribution_approvers")
                                    )
                                );
                                $assessment_data = $assessment_api->fetchAssessmentData();
                                if (!empty($assessment_data["distribution"]["distribution_approvers"])) {
                                    foreach ($assessment_data["distribution"]["distribution_approvers"] as $approver) {
                                        if ($PROCESSED["subject_id"] == $approver["proxy_id"]
                                            && $PROCESSED["subject_type"] == "proxy_id"
                                        ) {
                                            $assessment_tasks->queueApproverNotifications(
                                                $assessment_api->getAssessmentRecord(),
                                                $approver["proxy_id"]
                                            );
                                        }
                                    }
                                }
                            }
                        }
                        // Queue Assessment reminders. The reminder is always for the assessor, irrespective of subject.
                        if (!empty($PROCESSED["assessments_to_remind"])) {
                            foreach ($PROCESSED["assessments_to_remind"] as $reminder) {
                                $assessment_api = new Entrada_Assessments_Assessment(
                                    array(
                                        "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                                        "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                                        "dassessment_id" => $reminder["dassessment_id"],
                                    )
                                );
                                $assessment_data = $assessment_api->fetchAssessmentData();
                                if (!empty($assessment_data)) {
                                    $assessment_tasks->queueAssessorNotifications(
                                        $assessment_api->getAssessmentRecord(),
                                        $assessment_data["assessor"]["assessor_id"],
                                        null,
                                        1,
                                        true,
                                        true,
                                        false,
                                        false
                                    );
                                }
                            }
                        }
                    } else {
                        add_error($translate->_("No reminder data specified."));
                    }
                    if (has_error()) {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    } else {
                        echo json_encode(array("status" => "success", "data" => array($translate->_("Reminders sent."))));
                    }
                    break;
            }
            break;
        case "GET" :
            switch ($request["method"]) {

                case "get-target-list":
                    $PROCESSED["progress_type"] = array_key_exists("progress_type", $request)
                        ? clean_input($request["progress_type"], array("trim", "striptags"))
                        : null;

                    $PROCESSED["dassessment_id"] = array_key_exists("dassessment_id", $request)
                        ? (int)clean_input($request["dassessment_id"], array("trim", "int"))
                        : null;

                    if (!$PROCESSED["progress_type"] || !$PROCESSED["dassessment_id"]) {
                        exit(json_encode(array("status" => "error", "data" => $translate->_("Missing required parameters."))));
                    }
                    $assessment_api = new Entrada_Assessments_Assessment(
                        array(
                            "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                            "dassessment_id" => $PROCESSED["dassessment_id"] ? $PROCESSED["dassessment_id"] : null,
                            "limit_dataset" => array("targets", "progress")
                        )
                    );
                    $assessment_data = $assessment_api->fetchAssessmentData();
                    if (empty($assessment_data)) {
                        exit(json_encode(array("status" => "error", "data" => $assessment_api->getErrorMessages())));
                    }
                    $target_list = $assessment_api->getAssessmentTargetList();
                    if (empty($target_list)) {
                        exit(json_encode(array("status" => "error", "data" => array($translate->_("No targets found.")))));
                    }
                    $relevant_target_names = array();
                    foreach ($target_list as $target_data) {
                        if (in_array($PROCESSED["progress_type"], $target_data["progress"])) {
                            $relevant_target_names[$target_data["atarget_id"]] = $target_data["name"];
                        }
                    }
                    if (empty($relevant_target_names)) {
                        $target_list = array($translate->_("None found"));
                    } else {
                        $target_list = implode(", ", $relevant_target_names);
                    }
                    switch ($PROCESSED["progress_type"]) {
                        case "inprogress":
                            $target_string = sprintf($translate->_("Targets in progress: %s"), $target_list);
                            break;
                        case "complete":
                            $target_string = sprintf($translate->_("Targets complete: %s"), $target_list);
                            break;
                        case "pending":
                            $target_string = sprintf($translate->_("Targets pending: %s"), $target_list);
                            break;
                        default:
                            $target_string = sprintf($translate->_("Targets: %s"), $target_list);
                            break;
                    }
                    echo json_encode(array("status" => "success", "data" => $target_string));
                    break;
                case "get-tasks":

                    // Required params
                    if (isset($request["subject_id"])
                        && $request["subject_id"]
                        && $tmp_input = clean_input($request["subject_id"], array("trim", "int"))
                    ) {
                        $PROCESSED["subject_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Subject ID not set."));
                    }

                    if (isset($request["subject_type"])
                        && $request["subject_type"]
                        && $tmp_input = clean_input($request["subject_type"], array("trim", "notags"))
                    ) {
                        $PROCESSED["subject_type"] = $tmp_input;
                    } else {
                        add_error($translate->_("Subject type not specified."));
                    }

                    if (isset($request["subject_scope"])
                        && $request["subject_scope"]
                        && $tmp_input = clean_input($request["subject_scope"], array("trim", "notags"))
                    ) {
                        $PROCESSED["subject_scope"] = $tmp_input;
                    } else {
                        add_error($translate->_("Subject scope not specified."));
                    }

                    $PROCESSED["fetch_mode"] = null;
                    if (isset($request["fetch_mode"])
                        && $request["fetch_mode"]
                        && $tmp_input = clean_input($request["fetch_mode"], array("trim", "notags"))
                    ) {
                        $PROCESSED["fetch_mode"] = $tmp_input;
                    } else {
                        add_error($translate->_("Mode not specified."));
                    }

                    $PROCESSED["fetch_type"] = null;
                    if (isset($request["fetch_type"])
                        && $request["fetch_type"]
                        && $tmp_input = clean_input($request["fetch_type"], array("trim", "notags"))
                    ) {
                        $PROCESSED["fetch_type"] = $tmp_input;
                    } else {
                        add_error($translate->_("Query type not specified."));
                    }

                    $type_tokens = explode("-", $PROCESSED["fetch_type"]);
                    if (count($type_tokens) != 2) {
                        add_error($translate->_("Invalid query type specified."));
                    }

                    // Optional params

                    $PROCESSED["search_term"] = null;
                    $PROCESSED["limit"] = 9; // Default to 9 at a time
                    $PROCESSED["offset"] = $PROCESSED["limit"]; // default to the current limit
                    $PROCESSED["distribution_methods"] = array();
                    $PROCESSED["task_status"] = array();
                    $PROCESSED["cperiod"] = array();
                    $PROCESSED["course"] = array();
                    $PROCESSED["start_date"] = null;
                    $PROCESSED["end_date"] = null;
                    $PROCESSED["dassessment_id"] = null; // optionally filter by this ID

                    if (isset($request["search_term"])
                        && $tmp_input = clean_input(strtolower($request["search_term"]), array("trim", "striptags"))
                    ) {
                        $PROCESSED["search_term"] = $tmp_input;
                    }

                    if (isset($request["dassessment_id"])
                        && $request["dassessment_id"]
                        && $tmp_input = clean_input($request["dassessment_id"], array("trim", "int"))
                    ) {
                        $PROCESSED["dassessment_id"] = $tmp_input;
                    }

                    if (isset($request["start_date"])
                        && $request["start_date"]
                        && $tmp_input = clean_input(strtotime($request["start_date"] . " 00:00:00"), "int")
                    ) {
                        $PROCESSED["start_date"] = $tmp_input;
                    }

                    if (isset($request["end_date"])
                        && $request["end_date"]
                        && $tmp_input = clean_input(strtotime($request["end_date"] . "23:59:59"), "int")
                    ) {
                        $PROCESSED["end_date"] = $tmp_input;
                    }

                    if (array_key_exists("limit", $request)) {
                        if (is_numeric($request["limit"])) {
                            $PROCESSED["limit"] = (int)$request["limit"];
                        }
                    }

                    if (array_key_exists("offset", $request)) {
                        if (is_numeric($request["offset"])) {
                            $PROCESSED["offset"] = (int)$request["offset"];
                        }
                    }

                    if (isset($request["course"])) {
                        if (is_array($request["course"]) && !empty($request["course"])) {
                            $PROCESSED["course"] = array_map(function ($v) {
                                return clean_input($v, array("trim", "int"));
                            }, $request["course"]);
                        } else {
                            add_error($translate->_("Malformed course IDs"));
                        }
                    }

                    if (isset($request["task_status"])) {
                        if (is_array($request["task_status"]) && !empty($request["task_status"])) {
                            $PROCESSED["task_status"] = array_map(function ($v) {
                                return clean_input($v, array("trim", "notags", "alpha"));
                            }, $request["task_status"]);
                        } else {
                            add_error($translate->_("Malformed task statuses"));
                        }
                    }

                    if (isset($request["cperiod"])) {
                        if (is_array($request["cperiod"]) && !empty($request["cperiod"])) {
                            $PROCESSED["cperiod"] = array_map(function ($v) {
                                return clean_input($v, array("trim", "int"));
                            }, $request["cperiod"]);
                        } else {
                            add_error($translate->_("Malformed cperiod IDs"));
                        }
                    }

                    if (isset($request["distribution_methods"])) {
                        if (is_array($request["distribution_methods"]) && !empty($request["distribution_methods"])) {
                            $PROCESSED["distribution_methods"] = array_map(function ($v) {
                                return clean_input($v, array("trim", "int"));
                            }, $request["distribution_methods"]);
                        } else {
                            add_error($translate->_("Malformed distribution methods"));
                        }
                    }

                    $is_faculty = false;

                    if (!$ERROR) {
                        // Build filter options based on PROCESSED
                        switch ($PROCESSED["fetch_type"]) {
                            case "target-pending":
                            case "target-inprogress":
                            case "target-upcoming":
                            case "target-unstarted":
                            case "assessor-pending":
                            case "assessor-inprogress":
                            case "assessor-upcoming":
                            case "assessor-unstarted":
                                $task_type_filters = array(
                                    "limit" => $PROCESSED["limit"],
                                    "offset" => $PROCESSED["offset"],
                                    "sort_order" => "asc",
                                    "sort_column" => 28 // delivery date
                                );
                                break;
                            case "target-completed":
                            case "assessor-completed":
                                $completed_task_type_filters = array("assessment");
                                if (!$is_faculty) {
                                    // We don't include evaluations for faculty in our "Completed On Me" list.
                                    $completed_task_type_filters[] = "evaluation";
                                }
                                $task_type_filters = array(
                                    "limit" => $PROCESSED["limit"],
                                    "offset" => $PROCESSED["offset"],
                                    "task_type" => $completed_task_type_filters,
                                    "sort_order" => "desc",
                                    "sort_column" => 6 // completed date
                                );
                                break;
                            default:
                                add_error($translate->_("Malformed query type."));
                                break;
                        }
                        switch ($PROCESSED["fetch_mode"]) {
                            case "faculty":
                            case "learner":
                                $task_mode_filters = array(
                                    "distribution_method" => $PROCESSED["distribution_methods"],
                                    "task_status" => $PROCESSED["task_status"],
                                    "cperiod" => $PROCESSED["cperiod"],
                                    "course" => $PROCESSED["course"],
                                    "search_term" => $PROCESSED["search_term"],
                                    "start_date" => $PROCESSED["start_date"],
                                    "end_date" => $PROCESSED["end_date"],
                                    "dassessment_id"  => $PROCESSED["dassessment_id"]
                                );
                                break;
                            case "assessments":
                                $task_mode_filters = array(
                                    "distribution_method" => $PROCESSED["distribution_methods"],
                                    "task_status" => $PROCESSED["task_status"],
                                    "cperiod" => $PROCESSED["cperiod"],
                                    "course" => $PROCESSED["course"],
                                    "search_term" => $PROCESSED["search_term"],
                                    "start_date" => $PROCESSED["start_date"],
                                    "end_date" => $PROCESSED["end_date"],
                                    "dassessment_id"  => $PROCESSED["dassessment_id"],
                                    "task_type" => array("assessment") // "My Assessments" page is limited to assessments (not evaluations)
                                );
                                break;
                            default:
                                // Unknown mode
                                add_error($translate->_("Unknown filter mode."));
                                break;
                        }
                    }
                    $tasks = false;
                    if (!$ERROR) {
                        $assessment_tasks->setFilters(array_merge($task_type_filters, $task_mode_filters));
                        $fetched_tasks = $assessment_tasks->fetchAssessmentTaskList(
                            array($PROCESSED["fetch_type"]),
                            $PROCESSED["subject_id"],
                            $PROCESSED["subject_type"],
                            $PROCESSED["subject_scope"],
                            false,
                            true
                        );
                        $fetched_tasks_count = $assessment_tasks->fetchAssessmentTaskList(
                            array($PROCESSED["fetch_type"]),
                            $PROCESSED["subject_id"],
                            $PROCESSED["subject_type"],
                            $PROCESSED["subject_scope"],
                            true,
                            false

                        );
                        if (array_key_exists($type_tokens[0], $fetched_tasks)) {
                            if (array_key_exists($type_tokens[1], $fetched_tasks[$type_tokens[0]])) {
                                $tasks = $fetched_tasks[$type_tokens[0]][$type_tokens[1]];
                                $tasks_count = $fetched_tasks_count[$type_tokens[0]][$type_tokens[1]];
                            }
                        }
                        if ($tasks === false) {
                            add_error($translate->_("Invalid query type specified."));
                        }
                    }
                    if (!$ERROR) {
                        echo json_encode(array("status" => "success", "tasks" => $tasks, "count" => $tasks_count, "limit" => $PROCESSED["limit"], "offset" => $PROCESSED["offset"]));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                case "get-distribution-methods":
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
                case "get-user-course" :
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
                case "get-task-status-list" :
                    $data = array(
                        array("target_id" => "pending", "target_label" => $translate->_("Pending")),
                        array("target_id" => "inprogress", "target_label" => $translate->_("In Progress")),
                        array("target_id" => "complete", "target_label" => $translate->_("Completed"))
                    );

                    echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));

                    break;
            }
            break;
    }
    exit;
}