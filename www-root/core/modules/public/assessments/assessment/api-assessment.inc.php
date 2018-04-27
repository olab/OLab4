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
 * API to handle change the target of a form.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Don Zuiker <don.zuiker@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_ASSESSMENT"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed('assessment', 'read', false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:" . html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" . html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance.");
    echo display_error();
    application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] does not have access to this module [" . $MODULE . "]");
} else {

    ob_clear_open_buffers();
    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));
    $request = ${"_" . $request_method};

    switch ($request_method) {
        case "POST" :
            switch ($request["method"]) {
                case "save-feedback" :
                    if (isset($_POST["target_record_id"]) && $tmp_input = clean_input($_POST["target_record_id"], array("trim", "int"))) {
                        $PROCESSED["target_record_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No target identifier was provided"));
                    }
                    if (isset($_POST["target_scope"]) && $tmp_input = clean_input($_POST["target_scope"], array("trim", "notags"))) {
                        $PROCESSED["target_scope"] = $tmp_input;
                    } else {
                        add_error($translate->_("No target type was provided"));
                    }
                    if (isset($_POST["actor_id"]) && $tmp_input = clean_input($_POST["actor_id"], array("trim", "int"))) {
                        $PROCESSED["actor_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No user identifier was provided"));
                    }
                    if (isset($_POST["actor_type"]) && $tmp_input = clean_input($_POST["actor_type"], array("trim", "notags"))) {
                        $PROCESSED["actor_type"] = $tmp_input;
                    } else {
                        add_error($translate->_("No user type identifier was provided"));
                    }
                    if (isset($_POST["assessor_id"]) && $tmp_input = clean_input($_POST["assessor_id"], array("trim", "int"))) {
                        $PROCESSED["assessor_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No user identifier was provided"));
                    }
                    if (isset($_POST["assessor_type"]) && $tmp_input = clean_input($_POST["assessor_type"], array("trim", "notags"))) {
                        $PROCESSED["assessor_type"] = $tmp_input;
                    } else {
                        add_error($translate->_("No user type identifier was provided"));
                    }
                    if (isset($_POST["dassessment_id"]) && $tmp_input = clean_input($_POST["dassessment_id"], array("trim", "int"))) {
                        $PROCESSED["dassessment_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No assessment identifier was provided"));
                    }
                    $PROCESSED["target_feedback_response"] = null;
                    if (isset($_POST["target_feedback_response"]) && $tmp_input = clean_input($_POST["target_feedback_response"], array("trim", "striptags"))) {
                        $PROCESSED["target_feedback_response"] = $tmp_input;
                    }
                    if (isset($_POST["target_feedback_question"]) && $tmp_input = clean_input($_POST["target_feedback_question"], array("trim", "striptags"))) {
                        $PROCESSED["target_feedback_question"] = "<strong>$tmp_input</strong>";
                    } else {
                        $PROCESSED["target_feedback_question"] = $translate->_("the assessment feedback question");
                    }
                    if (isset($_POST["assessor_feedback_question"]) && $tmp_input = clean_input($_POST["assessor_feedback_question"], array("trim", "striptags"))) {
                        $PROCESSED["assessor_feedback_question"] = "<strong>$tmp_input</strong>";
                    } else {
                        $PROCESSED["assessor_feedback_question"] = $translate->_("the assessment feedback question");
                    }
                    if (isset($_POST["assessor_feedback_response"]) && $tmp_input = clean_input($_POST["assessor_feedback_response"], array("trim", "striptags"))) {
                        $PROCESSED["assessor_feedback_response"] = $tmp_input;
                    } else {
                        add_error(sprintf($translate->_("Please select a response for %s."), $PROCESSED["assessor_feedback_question"]));
                    }
                    $PROCESSED["comments"] = NULL;
                    if (isset($_POST["feedback_meeting_comments"]) && $tmp_input = clean_input($_POST["feedback_meeting_comments"], array("trim", "striptags"))) {
                        $PROCESSED["comments"] = $tmp_input;
                    }
                    $assessment_api = new Entrada_Assessments_Assessment(
                        array (
                            "dassessment_id" => $PROCESSED["dassessment_id"],
                            "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()
                        )
                    );
                    if (!$ERROR) {
                        if ($PROCESSED["actor_id"] == $PROCESSED["target_record_id"] && $PROCESSED["actor_type"] == $PROCESSED["target_scope"]) {
                            $saved = $assessment_api->updateTargetFeedback(
                                $PROCESSED["assessor_id"],
                                $PROCESSED["assessor_type"],
                                $PROCESSED["target_record_id"],
                                $PROCESSED["target_scope"],
                                ($PROCESSED["target_feedback_response"] == "yes" ? 1 : 0),
                                $PROCESSED["comments"]
                            );
                            if (!$saved) {
                                foreach ($assessment_api->getErrorMessages() as $error_message) {
                                    add_error($error_message);
                                }
                            }
                        } else {
                            $saved = $assessment_api->updateAssessorFeedback(
                                $PROCESSED["assessor_id"],
                                $PROCESSED["assessor_type"],
                                $PROCESSED["target_record_id"],
                                $PROCESSED["target_scope"],
                                ($PROCESSED["assessor_feedback_response"] == "yes" ? 1 : 0),
                                ($ENTRADA_USER->getActiveRole() == "admin")
                            );
                            if (!$saved) {
                                foreach ($assessment_api->getErrorMessages() as $error_message) {
                                    add_error($error_message);
                                }
                            }
                        }
                    }
                    if ($ERROR) {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    } else {
                        echo json_encode(array("status" => "success"));
                    }
                    break;
                case "create-new-progress":
                    if (isset($request["dassessment_id"]) && $tmp_input = clean_input($request["dassessment_id"], array("trim", "int"))) {
                        $PROCESSED["dassessment_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("A problem occurred while attempting to save your selected responses, please try again at a later time."));
                    }
                    if (isset($_POST["target_record_id"]) && $tmp_input = clean_input($_POST["target_record_id"], array("trim", "int"))) {
                        $PROCESSED["target_record_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("An error occurred while attempting to save responses for this target. Please try again later"));
                    }
                    if (isset($_POST["target_type"]) && $tmp_input = clean_input($_POST["target_type"], array("trim", "striptags"))) {
                        $PROCESSED["target_type"] = $tmp_input;
                    } else {
                        add_error($translate->_("An error occurred while attempting to save responses for this target. Please try again later"));
                    }
                    if (!$ERROR) {
                        $assessment_api = new Entrada_Assessments_Assessment(
                            array (
                                "dassessment_id" => $PROCESSED["dassessment_id"],
                                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                                "limit_dataset" => array("assessor", "progress")
                            )
                        );
                        if (!$assessment_api->saveNewAssessmentProgress($PROCESSED["target_record_id"], $PROCESSED["target_type"])) {
                            foreach ($assessment_api->getErrorMessages() as $error_message) {
                                add_error($error_message);
                            }
                        }
                    }
                    if (!$ERROR) {
                        $assessment_api->setDatasetLimit(array("targets"));
                        $data = $assessment_api->fetchAssessmentData();
                        if (!empty($data)) {
                            echo json_encode(array("status" => "success", "data" => array("redirect_url" => $assessment_api->getAssessmentURL($PROCESSED["target_record_id"], $PROCESSED["target_type"]))));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("Error retrieving assessment data."))));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                case "save-responses" :
                    $PROCESSED["dassessment_id"] = null;
                    if (isset($request["dassessment_id"]) && $tmp_input = clean_input($request["dassessment_id"], array("trim", "int"))) {
                        $PROCESSED["dassessment_id"] = $tmp_input;
                    }
                    $PROCESSED["aprogress_id"] = null;
                    if (isset($request["aprogress_id"]) && $tmp_input = clean_input($request["aprogress_id"], array("trim", "int"))) {
                        $PROCESSED["aprogress_id"] = $tmp_input;
                    }
                    $PROCESSED["target_record_id"] = null;
                    if (isset($_POST["target_record_id"]) && $tmp_input = clean_input($_POST["target_record_id"], array("trim", "int"))) {
                        $PROCESSED["target_record_id"] = $tmp_input;
                    }
                    $PROCESSED["target_type"] = null;
                    if (isset($_POST["target_type"]) && $tmp_input = clean_input($_POST["target_type"], array("trim", "striptags"))) {
                        $PROCESSED["target_type"] = $tmp_input;
                    }
                    $progress_id = null;
                    if (!$PROCESSED["dassessment_id"] || !$PROCESSED["target_record_id"] || !$PROCESSED["target_type"]) {
                        add_error($translate->_("Encountered an error when attempting to save your responses."));
                    }
                    if (!$ERROR) {
                        $assessment_api = new Entrada_Assessments_Assessment(
                            array (
                                "dassessment_id" => $PROCESSED["dassessment_id"],
                                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                                "limit_dataset" => array("assessor", "course_owners", "targets") // limit dataset to only that which is required to determine if the user can complete the assessment
                            )
                        );
                        if ($PROCESSED["aprogress_id"]) {
                            // We have a progress ID already, so use it.
                            $progress_id = $PROCESSED["aprogress_id"];
                        }
                        $assessment_api->setAprogressID($progress_id);
                        if ($assessment_api->canUserCompleteAssessment(($ENTRADA_USER->getActiveRole() == "admin"), false, false, $PROCESSED["target_type"], $PROCESSED["target_record_id"])) {
                            // We pass the posted data directly to updateProgressResponses; it will perform the required validation.
                            if (!$assessment_api->updateProgressResponses($_POST, $PROCESSED["target_record_id"], $PROCESSED["target_type"])) {
                                foreach ($assessment_api->getErrorMessages() as $error_message) {
                                    add_error($error_message);
                                }
                            }
                            $progress_id = $assessment_api->getAprogressID();
                        } else {
                            add_error($translate->_("You do not have permission to update this assessment."));
                        }
                    }
                    if (!$ERROR) {
                        echo json_encode(array("status" => "success", "data" => array("saved" => date("g:i:sa", time()), "aprogress_id" => $progress_id)));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                case "save-view-preferences" :
                    if (isset($request["target_status_view"]) && $tmp_input = clean_input($request["target_status_view"], array("trim", "striptags"))) {
                        $PROCESSED["target_status_view"] = $tmp_input;
                    } else {
                        add_error("No target status provided.");
                    }

                    if (isset($request["view"]) && $tmp_input = clean_input($request["view"], array("trim", "striptags"))) {
                        $PROCESSED["view"] = $tmp_input;
                    } else {
                        add_error("No target view provided.");
                    }

                    if (!$ERROR) {
                        $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["target_status_view"] = $PROCESSED["target_status_view"];
                        $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["target_view"] = $PROCESSED["view"];
                        echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully updated view preferences")));
                    }
                    break;
                case "set-curriculum-period":
                    $cperiod_id = null;
                    if (isset($request["cperiod_id"])) {
                        $cperiod_id = clean_input($request["cperiod_id"], array("int"));
                    }

                    $assessments_base = new Entrada_Utilities_Assessments_Base();
                    $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["learners"]["cperiod_id"] = $cperiod_id;
                    $assessments_base->updateAssessmentPreferences("assessments");

                    echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully changed curriculum period setting"), "data" => $cperiod_id));
                    break;
                case "change-target" :
                    $PROCESSED["dassessment_id"] = null;
                    if (isset($request["dassessment_id"]) && $tmp_input = clean_input($request["dassessment_id"], array("trim", "int"))) {
                        $PROCESSED["dassessment_id"] = $tmp_input;
                    }
                    $PROCESSED["aprogress_id"] = null;
                    if (isset($request["aprogress_id"]) && $tmp_input = clean_input($request["aprogress_id"], array("trim", "int"))) {
                        $PROCESSED["aprogress_id"] = $tmp_input;
                    }
                    $PROCESSED["target_record_id"] = null;
                    if (isset($_POST["target_record_id"]) && $tmp_input = clean_input($_POST["target_record_id"], array("trim", "int"))) {
                        $PROCESSED["target_record_id"] = $tmp_input;
                    }
                    $PROCESSED["target_type"] = null;
                    if (isset($_POST["target_type"]) && $tmp_input = clean_input($_POST["target_type"], array("trim", "striptags"))) {
                        $PROCESSED["target_type"] = $tmp_input;
                    }
                    if (!$PROCESSED["target_record_id"] || !$PROCESSED["target_type"]) {
                        add_error($translate->_("No new target provided."));
                    }
                    if (!$PROCESSED["aprogress_id"]) {
                        add_error($translate->_("There are no responses to move."));
                    }
                    if (!$ERROR) {
                        $assessment_api = new Entrada_Assessments_Assessment(
                            array (
                                "dassessment_id" => $PROCESSED["dassessment_id"],
                                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                                "aprogress_id" => $PROCESSED["aprogress_id"],
                                "limit_dataset" => array("progress", "targets")
                            )
                        );
                        if (!$assessment_api->moveAssessmentProgress($PROCESSED["target_record_id"], $PROCESSED["target_type"])) {
                            foreach ($assessment_api->getErrorMessages() as $error_message) {
                                add_error($error_message);
                            }
                        }
                        $new_progress = $assessment_api->getCurrentAssessmentProgress();
                    }
                    if ($ERROR) {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    } else {
                        echo json_encode(array("status" => "success", "data" => array("redirect_url" => $assessment_api->getAssessmentURL($new_progress["target_record_id"], $new_progress["target_type"]))));
                    }
                    break;
                case "send-reminders" :
                    $assessor_ids_set = false;
                    $PROCESSED["assessor_ids"] = array();
                    if (isset($request["assessor_array"]) && is_array($request["assessor_array"])) {
                        foreach ($request["assessor_array"] as $assessor) {
                            $tmp_input = clean_input($assessor, "int");
                            if ($tmp_input) {
                                $PROCESSED["assessor_ids"][] = $tmp_input;
                                $assessor_ids_set = true;
                            }
                        }
                    }
                    $assessment_ids_set = false;
                    $PROCESSED["dassessment_ids"] = array();
                    if (isset($request["dassessment_id_array"]) && is_array($request["dassessment_id_array"])) {
                        foreach ($request["dassessment_id_array"] as $dassessment) {
                            $tmp_input = clean_input($dassessment, "int");
                            if ($tmp_input) {
                                $PROCESSED["dassessment_ids"][] = $tmp_input;
                                $assessment_ids_set = true;
                            }
                        }
                    }
                    $delegation_task_ids_set = false;
                    $PROCESSED["delegations"] = array();
                    if (isset($request["delegator_tasks_array"]) && is_array($request["delegator_tasks_array"])) {
                        foreach ($request["delegator_tasks_array"] as $delegation_task) {
                            if (@$delegation_task["addelegation_id"] && @$delegation_task["adistribution_id"]) {
                                $tmp_addelegation_id = clean_input($delegation_task["addelegation_id"], "int");
                                $tmp_adistribution_id = clean_input($delegation_task["adistribution_id"], "int");
                                if ($tmp_addelegation_id && $tmp_adistribution_id) {
                                    $PROCESSED["delegations"][] = array("addelegation_id" => $tmp_addelegation_id, "adistribution_id" => $tmp_adistribution_id);
                                    $delegation_task_ids_set = true;
                                }
                            }
                        }
                    }
                    $approver_tasks_set = false;
                    $PROCESSED["approver_tasks"] = array();
                    if (isset($request["approver_tasks_array"]) && is_array($request["approver_tasks_array"])) {
                        foreach ($request["approver_tasks_array"] as $approver_task) {
                            if (@$approver_task["dassessment_id"] && @$approver_task["approver_id"]) {
                                $tmp_dassessment_id = clean_input($approver_task["dassessment_id"], "int");
                                $tmp_approver_id = clean_input($approver_task["approver_id"], "int");
                                if ($tmp_dassessment_id && $tmp_approver_id) {
                                    $PROCESSED["approver_tasks"][] = array("dassessment_id" => $tmp_dassessment_id, "approver_id" => $tmp_approver_id);
                                    $approver_tasks_set = true;
                                }
                            }
                        }
                    }

                    // Assessment notifications
                    if ($assessment_ids_set && $assessor_ids_set) {
                        $reminded_elements = array();
                        require_once("Classes/notifications/NotificationUser.class.php");
                        require_once("Classes/notifications/Notification.class.php");
                        global $db;

                        if (empty($PROCESSED["assessor_ids"]) || empty($PROCESSED["dassessment_ids"])) {
                            add_error($translate->_("Unable to process assessment IDs."));
                        } else {
                            foreach ($PROCESSED["assessor_ids"] as $key => $assessor_id) {
                                if (isset($PROCESSED["dassessment_ids"][$key]) && $PROCESSED["dassessment_ids"][$key]) {
                                    $dassessment_id = $PROCESSED["dassessment_ids"][$key];
                                }

                                // Only send a notification id the assessment task has actually been delivered.
                                if (isset($dassessment_id) && $dassessment_id) {
                                    $assessment = Models_Assessments_Assessor::fetchRowByID($dassessment_id);
                                    if ($assessment) {
                                        if ($assessment->getAssessorValue() == $assessor_id) {
                                            // Send a notification to the assessor informing them of the deletion.
                                            $notification_user = NotificationUser::get($assessor_id, "assessment", $dassessment_id, $assessor_id, ($assessment->getAssessorType() == "external" ? "external_assessor_id" : "proxy_id"));
                                            if (!$notification_user) {
                                                $notification_user = NotificationUser::add($assessor_id, "assessment", $dassessment_id, $assessor_id, 1, 0, 0, ($assessment->getAssessorType() == "external" ? "external_assessor_id" : "proxy_id"));
                                            }

                                            $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($assessment->getADistributionID());
                                            if ($distribution_schedule) {
                                                $schedule = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID());
                                            }

                                            if (isset($notification_user) && $notification_user) {
                                                $previous_notification = Models_Assessments_Notification::fetchAllByProxyIDAssessmentTypeForToday($assessor_id, "assessment");
                                                if (!$previous_notification) {
                                                    $notification = Notification::add($notification_user->getID(), $assessor_id, $dassessment_id, true);
                                                    if ($notification) {
                                                        $assessment_notification = new Models_Assessments_Notification(array(
                                                            "adistribution_id" => $assessment->getADistributionID(),
                                                            "assessment_value" => $dassessment_id,
                                                            "assessment_type" => "assessment",
                                                            "notified_value" => $assessor_id,
                                                            "notified_type" => ($assessment->getAssessorType() == "external" ? "external_assessor_id" : "proxy_id"),
                                                            "notification_id" => $notification->getID(),
                                                            "nuser_id" => $notification_user->getID(),
                                                            "notification_type" => "assessor_reminder",
                                                            "schedule_id" => (isset($schedule) && $schedule ? $schedule->getID() : NULL),
                                                            "sent_date" => time()
                                                        ));

                                                        if (!$assessment_notification->insert()) {
                                                            add_error($translate->_("An error occurred while attempting to queue a reminder."));
                                                            application_log("error", "Error encountered while attempting to save history of an assessment notification being sent to a user. DB said " . $db->ErrorMsg());
                                                        }
                                                    }
                                                }
                                            }
                                        } else {
                                            add_error($translate->_("You are attempting to remind an assessor of a task that is not theirs."));
                                        }
                                    } else {
                                        add_error($translate->_("Unable to retrieve assessment based on the provided ID. It is possible it has been deleted."));
                                    }
                                } else {
                                    add_error($translate->_("No assessment ID provided."));
                                }
                            }
                        }
                    }

                    // Delegation task notifications
                    if ($delegation_task_ids_set) {
                        foreach ($PROCESSED["delegations"] as $delegation) {
                            $distribution_delegation = new Entrada_Utilities_Assessments_DistributionDelegation($delegation);
                            $sent_status = $distribution_delegation->sendDelegationReminder();
                            if ($sent_status == false) {
                                add_error($translate->_("Unable to send notification."));
                            }
                        }
                    }

                    // Approver task notifications
                    if ($approver_tasks_set) {
                        foreach ($PROCESSED["approver_tasks"] as $approver_task) {
                            $approver = new Entrada_Utilities_Assessments_Approver($approver_task);
                            $sent_status = $approver->sendApproverReminder();
                            if ($sent_status == false) {
                                add_error($translate->_("Unable to send notification."));
                            }
                        }
                    }

                    if ($ERROR) {
                        echo json_encode(array("status" => "error", "data" => array($ERRORSTR)));
                    } else {
                        echo json_encode(array("status" => "success", "data" => array($translate->_("Successfully sent reminder(s)."))));
                    }

                    break;
                case "add-task" :

                    if (isset($_GET["adistribution_id"]) && $tmp_input = clean_input($_GET["adistribution_id"], array("trim", "int"))) {
                        $PROCESSED["adistribution_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No distribution id was provided."));
                    }

                    if (isset($request["delivery_date"]) && $tmp_input = clean_input($request["delivery_date"], array("trim", "striptags"))) {
                        // Construct a timestamp out of the given date.
                        $PROCESSED["delivery_date"] = strtotime($tmp_input . " 00:00:00");
                        if (!$PROCESSED["delivery_date"]) {
                            add_error($translate->_("The delivery date format was not recognized. Please follow YYYY-MM-DD format."));
                        }
                    } else {
                        add_error($translate->_("No delivery date was provided."));
                    }

                    $PROCESSED["target_ids"] = array();
                    if (isset($request["target_array"]) && is_array($request["target_array"])) {
                        foreach ($request["target_array"] as $target) {
                            $tmp_input = clean_input($target, "int");
                            if ($tmp_input) {
                                $PROCESSED["target_ids"][] = $tmp_input;
                            }
                        }
                    } else {
                        add_error($translate->_("Please provide targets to be assessed."));
                    }

                    $PROCESSED["assessors"] = array();
                    if (isset($request["assessor_array"]) && is_array($request["assessor_array"])) {
                        foreach ($request["assessor_array"] as $key => $assessor) {
                            $tmp_assessor_type = clean_input($assessor["assessor_type"], array("trim", "striptags"));
                            $tmp_assessor_value = clean_input($assessor["assessor_value"], array("trim", "int"));
                            if ($tmp_assessor_type && $tmp_assessor_value) {
                                $PROCESSED["assessors"][$key] = array(
                                    "assessor_type" => $tmp_assessor_type,
                                    "assessor_value" => $tmp_assessor_value
                                );
                            } else {
                                add_error(sprintf($translate->_("Invalid or missing assessor data for assessor number %s"), $key));
                            }
                        }
                    } else {
                        add_error($translate->_("Please provide assessors to evaluate the targets."));
                    }

                    if (!$ERROR) {
                        global $db;
                        $added_tasks = array();
                        $all_tasks_exist = true;
                        $course_contact_model = new Models_Assessments_Distribution_CourseContact();
                        $distribution = Models_Assessments_Distribution::fetchRowByID($PROCESSED["adistribution_id"]);

                        foreach ($PROCESSED["assessors"] as $assessor) {

                            if ($distribution) {
                                $course_contact_model->insertCourseContactRecord($distribution->getCourseID(), $assessor["assessor_value"], $assessor["assessor_type"]);
                            }

                            foreach ($PROCESSED["target_ids"] as $proxy_id) {

                                // Ensure the task does not already exist (meaning the combination of assessment assessor, assessment target and task id with that delivery date), and is not already an additional task.
                                $additional_task_exists = Models_Assessments_AdditionalTask::fetchRowByADistributionIDAssessorTypeAssessorValueTargetIDDeliveryDate($PROCESSED["adistribution_id"], $assessor["assessor_type"], $assessor["assessor_value"], $proxy_id, $PROCESSED["delivery_date"]);

                                $assessment_task_exists = false;
                                $assessment_task = Models_Assessments_Assessor::fetchRowByADistributionIDAssessorTypeAssessorValueDeliveryDate($PROCESSED["adistribution_id"], $assessor["assessor_type"], $assessor["assessor_value"], $PROCESSED["delivery_date"]);

                                if ($assessment_task) {

                                    $assessment_target = Models_Assessments_AssessmentTarget::fetchAllByDistributionIDTargetTypeTargetValueAssessmentID($PROCESSED["adistribution_id"], "proxy_id", $proxy_id, $assessment_task->getID());
                                    if ($assessment_target) {
                                        $assessment_task_exists = true;
                                    } else {
                                        $targets = Models_Assessments_Distribution_Target::getAssessmentTargets($PROCESSED["adistribution_id"], $assessment_task->getID(), null, $assessor["assessor_value"]);
                                        foreach ($targets as $target) {
                                            if ($target["proxy_id"] == $proxy_id) {
                                                $assessment_task_exists = true;
                                            }
                                        }
                                    }
                                }

                                if (!$additional_task_exists && !$assessment_task_exists) {
                                    $all_tasks_exist = false;

                                    $additional_task = new Models_Assessments_AdditionalTask(array(
                                        "adistribution_id" => $PROCESSED["adistribution_id"],
                                        "assessor_type" => $assessor["assessor_type"],
                                        "assessor_value" => $assessor["assessor_value"],
                                        "target_id" => $proxy_id,
                                        "target_type" => "proxy_id", // TODO: Change this when external targets are needed
                                        "delivery_date" => $PROCESSED["delivery_date"],
                                        "created_date" => time(),
                                        "created_by" => $ENTRADA_USER->getActiveID()
                                    ));

                                    if ($additional_task->insert()) {
                                        $added_tasks[] = $additional_task;
                                    } else {
                                        add_error($translate->_("An error occurred while attempting to add a task. Please try again later."));
                                        application_log("error", "Error encountered during creation of an additional. DB said " . $db->ErrorMsg());
                                    }
                                }
                            }
                        }

                        if ($all_tasks_exist) {
                            add_error($translate->_("All of the assessment tasks specified already exist."));
                        }

                        if (!$ERROR) {
                            echo json_encode(array("status" => "success", "data" => $added_tasks));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($ERRORSTR)));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($ERRORSTR)));
                    }

                    break;
                case "update-external-assessor-email" :
                    if (isset($_POST["external_id"]) && $tmp_input = clean_input($_POST["external_id"], array("trim", "int"))) {
                        $PROCESSED["external_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No external assessor id was provided."));
                    }

                    if ((isset($_POST["email"])) && ($_POST["email"] != "")) {
                        if (valid_address(trim($_POST["email"]))) {
                            $PROCESSED["email"] = strtolower(trim($_POST["email"]));
                        } else {
                            add_error($translate->_("The e-mail address you have provided is invalid."));
                        }
                    } else {
                        add_error($translate->_("The e-mail address you have provided is invalid."));
                    }

                    if (!$ERROR) {
                        $external_assessor = Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($PROCESSED["external_id"]);

                        if ($external_assessor) {
                            if (strtolower($external_assessor->getEmail()) != strtolower($PROCESSED["email"])) {
                                $email_history = new Models_Assessments_Distribution_ExternalAssessorEmailHistory();
                                $email_history->insertExternalAssessorEmailHistory($PROCESSED["external_id"], $external_assessor->getEmail());

                                if (!$ERROR) {
                                    $external_assessor->setEmail($PROCESSED["email"]);
                                    if (!$external_assessor->update()) {
                                        add_error($translate->_("Unable to update external assessor email address."));
                                    }
                                }
                            } else {
                                add_error($translate->_("The provide email address must be different from the current email address."));
                            }
                        } else {
                            add_error($translate->_("The external assessor provided was not found."));
                        }
                    }

                    if (!$ERROR) {
                        echo json_encode(array("status" => "success", "data" => $translate->_("Successfully updated email.")));
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($ERRORSTR)));
                    }
                    break;
                case "control-external-assessor-visibility" :
                    if (isset($_POST["external_id"]) && $tmp_input = clean_input($_POST["external_id"], array("trim", "int"))) {
                        $PROCESSED["external_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No external assessor id was provided."));
                    }

                    if (isset($request["card_view"]) && $tmp_input = clean_input($request["card_view"], array("trim", "striptags"))) {
                        $PROCESSED["card_view"] = $tmp_input;
                    } else {
                        add_error("No card view provided.");
                    }

                    if (!$ERROR) {
                        $external_assessor = Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($PROCESSED["external_id"]);

                        if ($external_assessor) {
                            $assessments_base = new Entrada_Utilities_Assessments_Base();

                            if ($PROCESSED["card_view"] == "add") {
                                $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["faculty_hidden_list"][] = $PROCESSED["external_id"];
                            } else {
                                if(($key = array_search($PROCESSED["external_id"], $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["faculty_hidden_list"])) !== false) {
                                    unset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["faculty_hidden_list"][$key]);
                                }
                            }
                            $assessments_base->updateAssessmentPreferences("assessments");
                        } else {
                            add_error($translate->_("The external assessor provided was not found."));
                        }
                    }
                    if (!$ERROR) {
                        echo json_encode(array("status" => "success", "data" => $translate->_("Successfully hide external assessor.")));
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($ERRORSTR)));
                    }
                    break;
                case "generate-pdf":
                    $PROCESSED["pdf_individual_option"] = false;
                    $PROCESSED["task_data"] = array();
                    if (array_key_exists("task_data", $_POST)) {
                        $task_data_string = urldecode($_POST["task_data"]);
                        $task_data = @json_decode($task_data_string, true);
                        if (empty($task_data)) {
                            add_error($translate->_("Invalid task data specified."));
                        } else {
                            $PROCESSED["task_data"] = $task_data;
                        }
                    }
                    if (array_key_exists("pdf_individual_option", $_POST)) {
                        if ($_POST["pdf_individual_option"] == "1") {
                            $PROCESSED["pdf_individual_option"] = true;
                        }
                    }
                    $error_url = Entrada_Utilities::arrayValueOrDefault($_POST, "error_url", ENTRADA_URL . "/assessments?pdf-error=true");
                    $format = $PROCESSED["pdf_individual_option"] ? "pdf" : "zip";

                    $download_token = array_key_exists("pdf_download_token", $_POST) ? $_POST["pdf_download_token"] : null;

                    if (!has_error()) {
                        $pdf_generator = new Entrada_Utilities_Assessments_PDFDownload(
                            array(
                                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                                "actor_type" => "proxy_id",
                                "actor_scope" => "internal"
                            )
                        );

                        // This step is terminal; it clears open buffers and sends out a ZIP or PDF
                        if (!$pdf_generator->sendAssessmentTasks($PROCESSED["task_data"], $download_token, $format)) {
                            // failed to send, redirect
                            ob_clear_open_buffers();
                            Header("Location: $error_url");
                            exit();
                        }

                    } else {
                        display_error(sprintf($translate->_("Unable to generate %s file. PDF generator library path not found."), $format));
                    }
                    break;
                case "reopen-task" :
                    if (isset($request["aprogress_id"]) && $tmp_input = clean_input($request["aprogress_id"], array("trim", "int"))) {
                        $PROCESSED["aprogress_id"] = $tmp_input;
                    } else {
                        add_error("No progress provided.");
                    }

                    if (!$ERROR) {
                        global $db;
                        $progress = Models_Assessments_Progress::fetchRowByID($PROCESSED["aprogress_id"]);
                        if ($progress) {
                            if ($progress->fromArray(array("progress_value" => "inprogress"))->update()) {
                                $statistic = new Models_Assessment_Statistic(array(
                                    "proxy_id" => $ENTRADA_USER->getActiveID(),
                                    "created_date" => time(),
                                    "module" => $MODULE,
                                    "sub_module" => $SUBMODULE,
                                    "action" => "reopen",
                                    "assessment_id" => $progress->getDAssessmentID(),
                                    "distribution_id" => $progress->getAdistributionID(),
                                    "target_id" => $progress->getTargetRecordID(),
                                    "progress_id" => $progress->getID(),
                                    "prune_after" => strtotime("+1 years")
                                ));
                                if (!$statistic->insert()) {
                                    application_log("error", "Unable to insert task reopening progress with ID " . $progress->getID() . ", DB said: " . $db->ErrorMsg());
                                }

                                echo json_encode(array("status" => "success", "data" => array("Successfully set task back to in-progress.")));
                            } else {
                                application_log("error", "Unable to reopen progress with ID " . $progress->getID() . ", DB said: " . $db->ErrorMsg());
                                echo json_encode(array("status" => "error", "data" => array($translate->_("Unable to update progress to reopen. Unable reopen task at this time."))));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("Unable to find progress to reopen. Unable reopen task at this time."))));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("Unable to reopen task at this time."))));
                    }
                    break;
                case "clear-task-progress" :
                    if (isset($request["aprogress_id"]) && $tmp_input = clean_input($request["aprogress_id"], array("trim", "int"))) {
                        $PROCESSED["aprogress_id"] = $tmp_input;
                    } else {
                        add_error("No progress provided.");
                    }

                    if (!$ERROR) {
                        global $db;
                        $progress = Models_Assessments_Progress::fetchRowByID($PROCESSED["aprogress_id"]);
                        if ($progress) {

                            $progress_responses = Models_Assessments_Progress_Response::fetchAllByAprogressID($progress->getID());
                            if ($progress_responses) {
                                foreach ($progress_responses as $progress_response) {
                                    if (!$progress_response->fromArray(array("deleted_date" => time()))->update()) {
                                        application_log("error", "Unable to delete progress responses for progress with ID " . $progress->getID() . ", DB said: " . $db->ErrorMsg());
                                    }
                                }
                            }

                            echo json_encode(array("status" => "success", "data" => array("Successfully set task back to in-progress.")));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("Unable to find progress to clear. Unable clear progress for task at this time."))));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("Unable to clear progress for task at this time."))));
                    }
                    break;
                case "forward-task":

                    // TODO: Move all of this functionality to the Entrada_Assessments_Assessment object

                    if (isset($_POST["dassessment_id"]) && $tmp_input = clean_input($_POST["dassessment_id"], array("trim", "int"))) {
                        $PROCESSED["dassessment_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No assessment id was provided."));
                    }
                    if (isset($_POST["target_type"]) && $tmp_input = clean_input($_POST["target_type"], array("trim", "trimtags"))) {
                        $PROCESSED["target_type"] = $tmp_input;
                    } else {
                        add_error($translate->_("No target type provided."));
                    }
                    if (isset($_POST["target_record_id"]) && $tmp_input = clean_input($_POST["target_record_id"], array("trim", "int"))) {
                        $PROCESSED["target_record_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No target was provided."));
                    }
                    if (isset($_POST["new_assessor_id"]) && $tmp_input = clean_input($_POST["new_assessor_id"], array("trim", "int"))) {
                        $PROCESSED["new_assessor_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No new assessor was provided."));
                    }
                    if (isset($request["new_assessor_type"]) && $tmp_input = clean_input($request["new_assessor_type"], array("trim", "trimtags"))) {
                        if ($tmp_input == "internal" || $tmp_input == "external") {
                            $PROCESSED["new_assessor_type"] = $tmp_input;
                        } else {
                            add_error($translate->_("New assessor type provided was invalid."));
                        }
                    } else {
                        add_error($translate->_("No new assessor type provided."));
                    }
                    if (isset($request["reason_id"]) && $tmp_input = clean_input($request["reason_id"], array("trim", "int"))) {
                        $PROCESSED["reason_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Please indicate why you are forwarding this task."));
                    }
                    if (isset($PROCESSED["reason_id"])) {
                        if (isset($request["reason_notes"]) && $tmp_input = clean_input($request["reason_notes"], array("trim", "trimtags"))) {
                            $PROCESSED["reason_notes"] = $tmp_input;
                        } else {
                            $reason = Models_Assessments_TaskDeletedReason::fetchRowByID($PROCESSED["reason_id"]);
                            if ($reason->getNotesRequired()) {
                                add_error($translate->_("You must provide notes explaining the reasons for the forward."));
                            }
                            $PROCESSED["reason_notes"] = false;
                        }
                    }
                    if ($ERROR) {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        exit;
                    }

                    $current_assessment_task = Models_Assessments_Assessor::fetchRowByID($PROCESSED["dassessment_id"]);
                    if (!$current_assessment_task) {
                        add_error($translate->_("The provided current task was not found."));
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        exit;
                    }

                    // Check if current assessment is inprogress for this target and cancel it.
                    $previous_progress = Models_Assessments_Progress::fetchAllByDassessmentIDAssessorTypeAssessorValueTargetRecordIDTargetType($current_assessment_task->getID(), $current_assessment_task->getAssessorType(), $current_assessment_task->getAssessorValue(), $PROCESSED["target_record_id"], $PROCESSED["target_type"], "inprogress");
                    if ($previous_progress) {
                        foreach ($previous_progress as $progress) {
                            if (!$progress->fromArray(array("updated_date" => time(), "progress_value" => "cancelled", "updated_by" => $ENTRADA_USER->getActiveID()))->update()) {
                                application_log("error", "An error occurred while attempting to cancel an inprogress progress record when forwarding a task DB said: " . $db->ErrorMsg());
                                add_error($translate->_("There as an error removing previous progress for an assessment target."));
                                echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                exit;
                            }
                        }
                    } else {
                        // Preemptively add a cancelled progress record.
                        $current_progress = array(
                            "adistribution_id" => $current_assessment_task->getADistributionID(),
                            "dassessment_id" => $current_assessment_task->getID(),
                            "uuid" => Models_Assessments_Progress::generateUuid(),
                            "assessor_type" => $current_assessment_task->getAssessorType(),
                            "assessor_value" => $current_assessment_task->getAssessorValue(),
                            //"adtarget_id" => Models_Assessments_Distribution_Target::fetchRowByDistributionID($current_assessment_task->getADistributionID())->getID(),
                            "target_record_id" => $PROCESSED["target_record_id"],
                            "target_type" => $PROCESSED["target_type"],
                            "progress_value" => "cancelled",
                            "created_date" => time(),
                            "created_by" => $ENTRADA_USER->getID(),
                            "updated_date" => time(),
                            "updated_by" => $ENTRADA_USER->getID(),
                        );
                        $progress = new Models_Assessments_Progress($current_progress);
                        if (!$progress->insert()) {
                            application_log("error", "Unable to insert cancelled records " . $progress->getID() . " when attempting to delete distribution targets. DB said: " . $db->ErrorMsg());
                            add_error($translate->_("There as an error removing previous progress for an assessment target."));
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                            exit;
                        }
                    }

                    // Add a forwarding statistic.
                    $assessment_forward_statistic = new Models_Assessment_Statistic(array(
                        "proxy_id" => $ENTRADA_USER->getID(),
                        "created_date" => time(),
                        "module" => $MODULE,
                        "sub_module" => $SUBMODULE,
                        "action" => "forward",
                        "assessment_id" => $current_assessment_task->getID(),
                        "distribution_id" => $current_assessment_task->getADistributionID(),
                        "target_id" => $PROCESSED["target_record_id"],
                        "progress_id" => null,
                        "prune_after" => strtotime("+1 years")
                    ));
                    if (!$assessment_forward_statistic->insert()) {
                        application_log("error", "Error encountered while attempting to save history of an assessment forward statistic. DB said " . $db->ErrorMsg());
                    }

                    // Immediately add a new task based on the old one that will be sent to the new assessor.
                    $distribution_assessment = Models_Assessments_Assessor::fetchRowByADistributionIDAssessorTypeAssessorValueDeliveryDate($current_assessment_task->getADistributionID(), $PROCESSED["new_assessor_type"], $PROCESSED["new_assessor_id"], $current_assessment_task->getDeliveryDate());
                    if (!$distribution_assessment) {
                        $distribution_assessment_data = $current_assessment_task->toArray();
                        unset($distribution_assessment_data["dassessment_id"]);
                        $distribution_assessment_data["assessor_value"] = $PROCESSED["new_assessor_id"];
                        $distribution_assessment_data["assessor_type"] = $PROCESSED["new_assessor_type"];
                        $distribution_assessment_data["created_date"] = time();
                        $distribution_assessment_data["created_by"] = $ENTRADA_USER->getID();
                        $distribution_assessment_data["forwarded_from_assessment_id"] = $current_assessment_task->getID();
                        $distribution_assessment_data["forwarded_date"] = time();
                        $distribution_assessment_data["forwarded_by"] = $ENTRADA_USER->getID();
                        unset($distribution_assessment_data["updated_date"]);
                        unset($distribution_assessment_data["updated_by"]);
                        // ADRIAN-TODO: Move this to assessment API object
                        $distribution_assessment = new Models_Assessments_Assessor($distribution_assessment_data);
                        $distribution_assessment = $distribution_assessment->insert();
                    }

                    if (!$distribution_assessment) {
                        add_error($translate->_("An error occurred while attempting to save the new task."));
                        application_log("error", "An error occurred while attempting to save a cbl_distribution_assessments record DB said: " . $db->ErrorMsg());
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        exit;
                    }

                    // Fetch old task's assessment target record so that we can delete it and use it to fill in data.
                    $old_assessment_target = Models_Assessments_AssessmentTarget::fetchRowByDAssessmentIDTargetTypeTargetValue($current_assessment_task->getID(), $PROCESSED["target_type"], $PROCESSED["target_record_id"]);
                    if (!$old_assessment_target) {
                        add_error($translate->_("Could not find existing target while attempting to save the new task."));
                        application_log("error", "Could not find existing target while attempting to save a forwarded assessment record DB said: " . $db->ErrorMsg());
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        exit;
                    }
                    $assessment_target_data = $old_assessment_target->toArray();
                    // Delete the old target.
                    $old_assessment_target->fromArray(array(
                        "deleted_date" => time(),
                        "deleted_by" => $ENTRADA_USER->getID(),
                        "deleted_reason_id" => $PROCESSED["reason_id"],
                        "deleted_reason_notes" => $PROCESSED["reason_notes"]
                    ));
                    if (!$old_assessment_target->update()) {
                        add_error($translate->_("An error occurred while attempting to delete the existing task target."));
                        application_log("error", "An error occurred while attempting to delete an existing cbl_distribution_assessment_target while forwarding record DB said: " . $db->ErrorMsg());
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        exit;
                    }

                    require_once("Classes/notifications/NotificationUser.class.php");
                    require_once("Classes/notifications/Notification.class.php");

                    if ($ENTRADA_USER->getActiveID() != $current_assessment_task->getAssessorValue()) {
                        // Notify original assessor of the deletion.
                        $schedule = false;
                        $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($current_assessment_task->getADistributionID());
                        if ($distribution_schedule) {
                            $schedule = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID());
                        }
                        $notification_user = NotificationUser::get($current_assessment_task->getAssessorValue(), "assessment_task_deleted_forwarded", $current_assessment_task->getID(), $current_assessment_task->getAssessorValue(), "proxy_id");
                        if (!$notification_user) {
                            $notification_user = NotificationUser::add($current_assessment_task->getAssessorValue(), "assessment_task_deleted_forwarded", $current_assessment_task->getID(), $current_assessment_task->getAssessorValue(), 1, 0, 0, "proxy_id");
                        }
                        if (!isset($notification_user) || !$notification_user) {
                            application_log("error", "Error encountered during creation of notification user while attempting to save history of an assessment being deleted by a user. DB said" . $db->ErrorMsg());
                            add_error($translate->_("An error occurred when attempting to notify the current assessor of the task deletion."));
                        }
                        $notification = Notification::add($notification_user->getID(), $current_assessment_task->getAssessorValue(), $current_assessment_task->getID(), $distribution_assessment->getID());
                        if ($notification) {
                            $assessment_notification = new Models_Assessments_Notification(array(
                                "adistribution_id" => $current_assessment_task->getADistributionID(),
                                "assessment_value" => $current_assessment_task->getID(),
                                "assessment_type" => "assessment",
                                "notified_value" => $current_assessment_task->getAssessorValue(),
                                "notified_type" => "proxy_id",
                                "notification_id" => $notification->getID(),
                                "nuser_id" => $notification_user->getID(),
                                "notification_type" => "assessment_task_deleted_forwarded",
                                "schedule_id" => ($schedule ? $schedule->getID() : NULL),
                                "sent_date" => time(),
                            ));
                            if (!$assessment_notification->insert()) {
                                application_log("error", "Error encountered while attempting to save history of an assessment being deleted by a user. DB said" . $db->ErrorMsg());
                                add_error($translate->_("An error occurred when attempting to notify the current assessor of the task deletion."));
                            }
                        } else {
                            application_log("error", "Error encountered while attempting to save history of an assessment being deleted by a user. DB said" . $db->ErrorMsg());
                            add_error($translate->_("An error occurred when attempting to notify the current assessor of the task deletion."));
                        }
                    }

                    // Insert new assessment target to match the new assessment.
                    unset($assessment_target_data["atarget_id"]);
                    $assessment_target_data["dassessment_id"] = $distribution_assessment->getID();
                    $assessment_target_data["created_date"] = time();
                    $assessment_target_data["created_by"] = $ENTRADA_USER->getID();
                    $assessment_target_data["updated_date"] = time();
                    $assessment_target_data["updated_by"] = $ENTRADA_USER->getID();
                    $assessment_target = new Models_Assessments_AssessmentTarget($assessment_target_data);
                    if (!$assessment_target->insert()) {
                        add_error($translate->_("An error occurred while attempting to save the new task target."));
                        application_log("error", "An error occurred while attempting to save a cbl_distribution_assessment_target record DB said: " . $db->ErrorMsg());
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        exit;
                    }

                    // Copy the assessment options
                    $assessment_options_model = new Models_Assessments_Options();
                    $old_options = $assessment_options_model->fetchAllByDassessmentID($current_assessment_task->getID());
                    if (!empty($old_options)) {
                        foreach ($old_options as $option) {
                            $new_option_data = $option->toArray();
                            $new_option_data["daoption_id"] = null;
                            $new_option_data["dassessment_id"] = $distribution_assessment->getID();
                            if (!$assessment_options_model->fromArray($new_option_data)->insert()) {
                                // We're not going to return false here, but we do need to log the failure.
                                application_log("error", "Failed saving assessment option for old assessment id '{$current_assessment_task->getID()}', to new assessment id '{$distribution_assessment->getID()}'");
                            }
                        }
                    }

                    // Notify the new assessor of the forward.
                    $notification_user = NotificationUser::get($distribution_assessment->getAssessorValue(), "assessment_task_forwarded", $distribution_assessment->getID(), $distribution_assessment->getAssessorValue(), "proxy_id");
                    if (!$notification_user) {
                        $notification_user = NotificationUser::add($distribution_assessment->getAssessorValue(), "assessment_task_forwarded", $distribution_assessment->getID(), $distribution_assessment->getAssessorValue(), 1, 0, 0, "proxy_id");
                    }
                    if (!$notification_user) {
                        application_log("error", "Error encountered during creation of notification user while attempting to save history of an assessment being created by a user. DB said" . $db->ErrorMsg());
                        add_error($translate->_("An error occurred when attempting to notify the new assessor of the task creation."));
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        exit;
                    }
                    $notification = Notification::add($notification_user->getID(), $distribution_assessment->getAssessorValue(), $distribution_assessment->getID(), $distribution_assessment->getID());
                    if ($notification) {
                        $assessment_notification = new Models_Assessments_Notification(array(
                            "adistribution_id" => $distribution_assessment->getADistributionID(),
                            "assessment_value" => $distribution_assessment->getID(),
                            "assessment_type" => "assessment",
                            "notified_value" => $distribution_assessment->getAssessorValue(),
                            "notified_type" => "proxy_id",
                            "notification_id" => $notification->getID(),
                            "nuser_id" => $notification_user->getID(),
                            "notification_type" => "assessment_task_forwarded",
                            "sent_date" => time(),
                        ));
                        if (!$assessment_notification->insert()) {
                            application_log("error", "Error encountered while attempting to save history of an assessment being created by a user. DB said" . $db->ErrorMsg());
                            add_error($translate->_("An error occurred when attempting to notify the new assessor of the task creation."));
                        }
                    } else {
                        application_log("error", "Error encountered while attempting to save history of an assessment being created by a user. DB said" . $db->ErrorMsg());
                        add_error($translate->_("An error occurred when attempting to notify the new assessor of the task creation."));
                    }
                    if ($ERROR) {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        exit();
                    }
                    $assessor_name = $translate->_("the assessor");
                    $assessor_email = false;
                    if ($PROCESSED["new_assessor_type"] == "internal") {
                        $assessor_details = Models_User::fetchRowByID($PROCESSED["new_assessor_id"]);
                        if ($assessor_details) {
                            $assessor_name = $assessor_details->getPrefix() . " " . $assessor_details->getFirstname() . " " . $assessor_details->getLastname();
                            $assessor_email = $assessor_details->getEmail();
                        }
                    } else {
                        $external_assessor = Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($PROCESSED["new_assessor_id"]);
                        if ($external_assessor) {
                            $assessor_name = $external_assessor->getFirstname() . " " . $external_assessor->getLastname();
                            $assessor_email = $external_assessor->getEmail();
                        }
                    }
                    echo json_encode(array("status" => "success", "data" => array("assessor_name" => $assessor_name, "assessor_email" => $assessor_email)));
                break;
                case "delete-tasks-by-atarget" :
                    // ADRIAN-TODO: This method is deprecated.
                    // This one is temporary until distributions can properly call the delete-tasks in api-tasks
                    $PROCESSED["atarget_id_list"] = array();
                    if (isset($request["task_data_array"]) && is_array($request["task_data_array"])) {
                        foreach ($request["task_data_array"] as $target) {
                            $temp_list = explode(",", $target);

                            foreach ($temp_list as $atarget_id) {
                                if ($tmp_atarget_id = clean_input($atarget_id, array("trim", "int"))) {
                                    if ($tmp_atarget_id) {
                                        $PROCESSED["atarget_id_list"][] = $tmp_atarget_id;
                                    } else {
                                        add_error($translate->_("Target information not provided."));
                                    }
                                }
                            }
                        }
                    } else {
                        add_error($translate->_("No task data provided."));
                    }

                    $PROCESSED["reason_id"] = null;
                    if (isset($request["reason_id"]) && $tmp_input = clean_input($request["reason_id"], array("trim", "int"))) {
                        $PROCESSED["reason_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Please indicate why you are removing this assessment task from your task list."));
                    }

                    $PROCESSED["reason_notes"] = null;
                    if (isset($PROCESSED["reason_id"])) {
                        if (isset($request["reason_notes"]) && $tmp_input = clean_input($request["reason_notes"], array("trim", "notags"))) {
                            $PROCESSED["reason_notes"] = $tmp_input;
                        } else {
                            $reason = Models_Assessments_TaskDeletedReason::fetchRowByID($PROCESSED["reason_id"]);
                            if ($reason->getNotesRequired()) {
                                add_error($translate->_("Please indicate why you are removing this assessment task from your task list."));
                            }
                            $PROCESSED["reason_notes"] = null;
                        }
                    }

                    $PROCESSED["visible"] = isset($_GET["hide_deleted_task"]) ? 0 : 1;

                    if (!$ERROR) {
                        require_once("Classes/notifications/NotificationUser.class.php");
                        require_once("Classes/notifications/Notification.class.php");

                        $notified_assessors = array();

                        foreach ($PROCESSED["atarget_id_list"] as $atarget_id) {
                            // ADRIAN-TODO: Move this deletion functionality to the assessments API
                            $target = Models_Assessments_AssessmentTarget::fetchRowByID($atarget_id);

                            if ($target) {
                                $target->setVisible($PROCESSED["visible"]);
                                $target->setDeletedReasonID($PROCESSED["reason_id"]);
                                $target->setDeletedReasonNotes($PROCESSED["reason_notes"]);
                                $target->setUpdatedBy($ENTRADA_USER->getActiveID());
                                $target->setUpdatedDate(time());
                                $target->setDeletedBy($ENTRADA_USER->getActiveID());
                                $target->setDeletedDate(time());
                                $target->update();

                                /*
                                $assessment = Models_Assessments_Assessor::fetchRowByID($target->getDassessmentID(), null, true);

                                if ($assessment) {
                                    $assessment->setUpdatedBy($ENTRADA_USER->getActiveID());
                                    $assessment->setUpdatedDate(time());
                                    $assessment->setDeletedDate(time());
                                    $assessment->update();

                                    $post_notify = (isset($_POST["notify"])) ? $_POST["notify"] : false;
                                    $previously_notified = (clean_input($post_notify, array("trim", "striptags")) == "no") ? true : false;
                                    if (isset($notified_assessors[$assessment->getAssessorType()]) && !empty($notified_assessors[$assessment->getAssessorType()])) {
                                        foreach ($notified_assessors[$assessment->getAssessorType()] as $assessor_value) {
                                            if ($assessor_value == $assessment->getAssessorValue()) {
                                                $previously_notified = true;
                                            }
                                        }
                                    }

                                    if (!$previously_notified) {
                                        $notification_user = NotificationUser::get($ENTRADA_USER->getActiveID(), "assessment_task_deleted", $target->getDassessmentID(), $assessment->getAssessorValue(), "proxy_id");
                                        if (!$notification_user) {
                                            $notification_user = NotificationUser::add($ENTRADA_USER->getActiveID(), "assessment_task_deleted", $target->getDassessmentID(), $assessment->getAssessorValue(), 1, 0, 0, "proxy_id");
                                        }

                                        if (isset($notification_user) && $notification_user) {
                                            $notification = Notification::add($notification_user->getID(), $assessment->getAssessorValue(), $assessment->getID(), $atarget_id);

                                            if ($notification) {
                                                $assessment_notification = new Models_Assessments_Notification(array(
                                                    "adistribution_id" => $target->getADistributionID(),
                                                    "assessment_value" => $target->getDassessmentID(),
                                                    "assessment_type" => "assessment",
                                                    "notified_value" => $assessment->getAssessorValue(),
                                                    "notified_type" => "proxy_id",
                                                    "notification_id" => $notification->getID(),
                                                    "nuser_id" => $notification_user->getID(),
                                                    "notification_type" => "assessment_task_deleted",
                                                    "schedule_id" => $assessment->getAssociatedRecordType() == "schedule_id" ? $assessment->getAssociatedRecordID()  : null,
                                                    "sent_date" => time()
                                                ));

                                                if ($assessment_notification->insert()) {
                                                    $notified_assessors[$assessment->getAssessorType()][] = $assessment->getAssessorValue();
                                                } else {
                                                    add_error($translate->_("An error occurred when attempting to notify the assessor of the task deletion."));
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    add_error($translate->_("Can not remove task as the assessment was not found."));
                                }
                                */
                            } else {
                                add_error($translate->_("Target not found."));
                            }
                        }
                    }

                    if (!$ERROR) {
                        echo json_encode(array("status" => "success", "data" => $translate->_("Successfully deleted task(s)."), "dassessment_id" => $target->getDassessmentID()));
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($ERRORSTR)));
                    }
                    break;
                case "delete-tasks-by-future":
                    // TODO: THIS METHOD IS DEPRECATED; api-tasks has a version that should be utilized to accomplish this functionality
                    if (isset($_GET["adistribution_id"]) && $tmp_input = clean_input($_GET["adistribution_id"], array("trim", "int"))) {
                        $PROCESSED["adistribution_id"] = $tmp_input;
                    }

                    $PROCESSED["location"] = false;
                    if (isset($request["location"]) && $tmp_input = clean_input($request["location"], array("trim", "striptags"))) {
                        $PROCESSED["location"] = $tmp_input;
                    } else {
                        add_error($translate->_("No location was provided."));
                    }

                    $PROCESSED["future_tasks"] = array();
                    if ($PROCESSED["location"]) {
                        if (isset($request["task_data_array"])) {
                            foreach ($request["task_data_array"] as $key => $future_task) {
                                if ($PROCESSED["location"] != "progress") {

                                    $future_task_object             = unserialize(json_decode($future_task));
                                    $PROCESSED["adistribution_id"]  = $future_task_object->getDistributionID();
                                    $PROCESSED["future_tasks"][]    = $future_task_object->toArray();
                                } else {

                                    $tmp_assessor_type          = clean_input($future_task["assessor_type"],            array("trim", "striptags"));
                                    $tmp_assessor_value         = clean_input($future_task["assessor_value"],           array("trim", "int"));
                                    $tmp_target_type            = clean_input($future_task["target_type"],              array("trim", "striptags"));
                                    $tmp_target_value           = clean_input($future_task["target_id"],                array("trim", "int"));
                                    $tmp_form_id                = clean_input($future_task["form_id"],                  array("trim", "int"));
                                    $tmp_organisation_id        = clean_input($future_task["organisation_id"],          array("trim", "int"));
                                    $tmp_delivery_date          = clean_input($future_task["delivery_date"],            array("trim", "int"));
                                    $tmp_feedback_required      = clean_input($future_task["feedback_required"],        array("trim", "int"));
                                    $tmp_min_submittable        = clean_input($future_task["min_submittable"],          array("trim", "int"));
                                    $tmp_max_submittable        = clean_input($future_task["max_submittable"],          array("trim", "int"));
                                    $tmp_start_date             = clean_input($future_task["start_date"],               array("trim", "int"));
                                    $tmp_end_date               = clean_input($future_task["end_date"],                 array("trim", "int"));
                                    $tmp_rotation_start_date    = clean_input($future_task["rotation_start_date"],      array("trim", "int"));
                                    $tmp_rotation_end_date      = clean_input($future_task["rotation_end_date"],        array("trim", "int"));
                                    $tmp_associated_record_type = clean_input($future_task["associated_record_type"],   array("trim", "striptags"));
                                    $tmp_associated_record_id   = clean_input($future_task["associated_record_id"],     array("trim", "int"));
                                    $tmp_additional_task        = clean_input($future_task["additional_task"],          array("trim", "bool"));
                                    $tmp_task_type              = clean_input($future_task["task_type"],                array("trim", "striptags"));

                                    if ($tmp_assessor_type
                                        && $tmp_assessor_value
                                        && $tmp_target_value
                                        && $tmp_delivery_date
                                        && $tmp_form_id
                                        && $tmp_organisation_id
                                        && isset($tmp_feedback_required)
                                        && $tmp_min_submittable
                                        && $tmp_max_submittable
                                        && isset($tmp_start_date)
                                        && isset($tmp_end_date)
                                        && $tmp_form_id
                                        && isset($tmp_rotation_start_date)
                                        && isset($tmp_rotation_end_date)
                                        && $tmp_associated_record_type
                                        && isset($tmp_associated_record_id)
                                        && $tmp_task_type
                                        && isset($tmp_additional_task)
                                    ) {
                                        $PROCESSED["future_tasks"][]    = array(
                                            "assessor_type"             => $tmp_assessor_type,
                                            "assessor_value"            => $tmp_assessor_value,
                                            "target_type"               => $tmp_target_type,
                                            "target_value"              => $tmp_target_value,
                                            "delivery_date"             => $tmp_delivery_date,
                                            "form_id"                   => $tmp_form_id,
                                            "organisation_id"           => $tmp_organisation_id,
                                            "feedback_required"         => $tmp_feedback_required,
                                            "min_submittable"           => $tmp_min_submittable,
                                            "max_submittable"           => $tmp_max_submittable,
                                            "start_date"                => $tmp_start_date,
                                            "end_date"                  => $tmp_end_date,
                                            "rotation_start_date"       => $tmp_rotation_start_date,
                                            "rotation_end_date"         => $tmp_rotation_end_date,
                                            "associated_record_type"    => $tmp_associated_record_type,
                                            "associated_record_id"      => $tmp_associated_record_id ? $tmp_associated_record_id : null,
                                            "additional_task"           => $tmp_additional_task,
                                            "task_type"                 => $tmp_task_type
                                        );
                                    } else {
                                        add_error(sprintf($translate->_("Invalid or missing task data for task number %s"), $key));
                                    }
                                }
                            }
                        } else {
                            add_error($translate->_("No task data provided."));
                        }
                    }

                    if (isset($request["reason_id"]) && $tmp_input = clean_input($request["reason_id"], array("trim", "int"))) {
                        $PROCESSED["reason_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Please indicate why you are removing this assessment task from your task list."));
                    }

                    if (isset($PROCESSED["reason_id"])) {
                        if (isset($request["reason_notes"]) && $tmp_input = clean_input($request["reason_notes"], array("trim", "notags"))) {
                            $PROCESSED["reason_notes"] = $tmp_input;
                        } else {
                            $reason = Models_Assessments_TaskDeletedReason::fetchRowByID($PROCESSED["reason_id"]);
                            if ($reason->getNotesRequired()) {
                                add_error($translate->_("Please indicate why you are removing this assessment task from your task list."));
                            }
                            $PROCESSED["reason_notes"] = false;
                        }
                    }

                    $PROCESSED["visible"] = isset($_GET["hide_deleted_task"]) ? 0 : 1;

                    if (!$ERROR) {
                        if (!empty($PROCESSED["future_tasks"])) {
                            global $db;
                            foreach ($PROCESSED["future_tasks"] as $task) {
                                // Check if an assessment exists (it shouldn't if is the first time we are processing this date for this assessor).
                                $assessment = Models_Assessments_Assessor::fetchRowByADistributionIDAssessorTypeAssessorValueDeliveryDateAssociatedRecordIDAssociatedRecordType(
                                    $PROCESSED["adistribution_id"],
                                    $task["assessor_type"],
                                    $task["assessor_value"],
                                    $task["delivery_date"],
                                    $task["associated_record_id"],
                                    $task["associated_record_type"]
                                );

                                if (!$assessment) {
                                    // ADRIAN-TODO: Move this to assessment API object
                                    $assessment = new Models_Assessments_Assessor(array(
                                        "form_id"                   => isset($task["form_id"]) ? $task["form_id"] : null,
                                        "assessment_type_id"        => 1,
                                        "assessment_method_id"      => 1,
                                        "organisation_id"           => isset($task["organisation_id"])          ? $task["organisation_id"]          : null,
                                        "adistribution_id"          => isset($PROCESSED["adistribution_id"])    ? $PROCESSED["adistribution_id"]    : null,
                                        "assessor_type"             => isset($task["assessor_type"])            ? $task["assessor_type"]            : "internal",
                                        "assessor_value"            => $task["assessor_value"],
                                        "associated_record_id"      => isset($task["associated_record_id"])     ? $task["associated_record_id"]     : null,
                                        "associated_record_type"    => isset($task["associated_record_type"])   ? $task["associated_record_type"]   : null,
                                        "number_submitted"          => isset($task["number_submitted"])         ? $task["number_submitted"]         : 0,
                                        "min_submittable"           => isset($task["min_submittable"])          ? $task["min_submittable"]          : 0,
                                        "max_submittable"           => isset($task["max_submittable"])          ? $task["max_submittable"]          : 0,
                                        "feedback_required"         => isset($task["feedback_required"])        ? $task["feedback_required"]        : 0,
                                        "published"                 => 1,
                                        "start_date"                => isset($task["start_date"])               ? $task["start_date"]               : 0,
                                        "end_date"                  => isset($task["end_date"])                 ? $task["end_date"]                 : 0,
                                        "delivery_date"             => isset($task["delivery_date"])            ? $task["delivery_date"]            : null,
                                        "rotation_start_date"       => isset( $task["rotation_start_date"])      ? $task["rotation_start_date"]      : 0,
                                        "rotation_end_date"         => isset($task["rotation_end_date"])        ? $task["rotation_end_date"]        : 0,
                                        "external_hash"             => isset($task["assessor_type"]) && $task["assessor_type"] == "external" ? generate_hash() : null,
                                        "additional_assessment"     => isset($task["additional_task"]) && $task["additional_task"] ? 1 : 0,
                                        "created_date"              => time(),
                                        "created_by"                => $ENTRADA_USER->getActiveID(),
                                        "updated_date"              => null,
                                        "updated_by"                => null,
                                        "deleted_date"              => null
                                    ));
                                    if (!$assessment->insert()) {
                                        application_log("error", "Unable to add future task when deleting, DB said: " . $db->ErrorMsg());
                                    }
                                }

                                if ($assessment) {
                                    // Check if an assessment target exists (it also shouldn't if is the first time we are processing this date for this assessor).
                                    $assessment_target = Models_Assessments_AssessmentTarget::fetchRowByDAssessmentIDTargetTypeTargetValue($assessment->getID(), $task["target_type"], $task["target_value"]);
                                    if (!$assessment_target) {
                                        $assessment_target = new Models_Assessments_AssessmentTarget(array(
                                            "dassessment_id"        => $assessment->getID(),
                                            "adistribution_id"      => isset($PROCESSED["adistribution_id"])    ? $PROCESSED["adistribution_id"]    : null,
                                            "task_type"             => isset($task["task_type"])                ? $task["task_type"]                : "assessment",
                                            "target_type"           => isset($task["target_type"])              ? $task["target_type"]              : "proxy_id",
                                            "target_value"          => isset($task["target_value"])             ? $task["target_value"]             : null,
                                            "delegation_list_id"    => null,
                                            "associated_schedules"  => null,
                                            "created_date"          => time(),
                                            "created_by"            => $ENTRADA_USER->getActiveID(),
                                            "updated_date"          => time(),
                                            "updated_by"            => $ENTRADA_USER->getActiveID(),
                                            "deleted_date"          => time(),
                                            "deleted_reason_id"     => $PROCESSED["reason_id"],
                                            "deleted_reason_notes"  => $PROCESSED["reason_notes"],
                                            "deleted_by"            => $ENTRADA_USER->getActiveID(),
                                            "visible"               => $PROCESSED["visible"]
                                        ));

                                        if (!$assessment_target->insert()) {
                                            add_error($translate->_("Unable to delete target at this time, please try again later."));
                                            application_log("error", "Unable to add future task target when deleting, DB said: " . $db->ErrorMsg());
                                        } else {
                                            // Most tasks will have a snapshot generated before they are deleted.
                                            $future_task_snapshot = Models_Assessments_FutureTaskSnapshot::fetchRowByDistributionIDAssessorTypeAssessorValueTargetTypeTargetValueDeliveryDate(
                                                $PROCESSED["adistribution_id"],
                                                $task["assessor_type"],
                                                $task["assessor_value"],
                                                $task["target_type"],
                                                $task["target_value"],
                                                $task["delivery_date"]
                                            );

                                            if ($future_task_snapshot) {
                                                $future_task_snapshot->setDeletedBy($ENTRADA_USER->getActiveID());
                                                $future_task_snapshot->setDeletedDate(time());
                                                if (!$future_task_snapshot->update()) {
                                                    add_error($translate->_("Unable to delete task at this time, please try again later."));
                                                }
                                            }
                                        }
                                    } else {

                                        // Delete existing target.
                                        $assessment_target->setVisible($PROCESSED["visible"]);
                                        $assessment_target->setDeletedReasonID($PROCESSED["reason_id"]);
                                        $assessment_target->setDeletedReasonNotes($PROCESSED["reason_notes"]);
                                        $assessment_target->setUpdatedBy($ENTRADA_USER->getActiveID());
                                        $assessment_target->setUpdatedDate(time());
                                        $assessment_target->setDeletedBy($ENTRADA_USER->getActiveID());
                                        $assessment_target->setDeletedDate(time());

                                        if (!$assessment_target->update()) {
                                            add_error($translate->_("Unable to delete target at this time, please try again later."));
                                            application_log("error", "Unable to delete target, DB said: " . $db->ErrorMsg());
                                        } else {
                                            $future_task_snapshot = Models_Assessments_FutureTaskSnapshot::fetchRowByDistributionIDAssessorTypeAssessorValueTargetTypeTargetValueDeliveryDate(
                                                $PROCESSED["adistribution_id"],
                                                $task["assessor_type"],
                                                $task["assessor_value"],
                                                $task["target_type"],
                                                $task["target_value"],
                                                $task["delivery_date"]
                                            );

                                            if ($future_task_snapshot) {
                                                $future_task_snapshot->setDeletedBy($ENTRADA_USER->getActiveID());
                                                $future_task_snapshot->setDeletedDate(time());
                                                if (!$future_task_snapshot->update()) {
                                                    add_error($translate->_("Unable to delete task at this time, please try again later."));
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    add_error($translate->_("Unable to delete task at this time, please try again later."));
                                }
                            }

                            if (!$ERROR) {
                                echo json_encode(array("status" => "success", "data" => $translate->_("Successfully deleted task(s).")));
                            } else {
                                echo json_encode(array("status" => "error", "data" => array($ERRORSTR)));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("No valid tasks provided for deletion."))));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($ERRORSTR)));
                    }
                    break;
            }
            break;
        case "GET" :
            switch ($request["method"]) {
                case "get-supervisor-assessment-form":
                    if (isset($request["course_id"]) && $tmp_input = clean_input($request["course_id"], array("trim", "int"))) {
                        $PROCESSED["course_id"] = $tmp_input;
                    } else {
                        add_error("No course provided.");
                    }

                    if ($ERROR) {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    } else {
                        $forms_api = new Entrada_Assessments_Forms(array("actor_proxy_id" => $ENTRADA_USER->getActiveId(), "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()));
                        if ($redirect_url = $forms_api->createDistributionAndRedirect($PROCESSED["course_id"])) {
                            echo json_encode(array("status" => "success", "data" => $redirect_url));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array("No Assessments forms available.")));
                        }
                    }
                    break;
                case "get-objectives" :
                    if (isset($request["objective_id"]) && $tmp_input = clean_input($request["objective_id"], array("trim", "int"))) {
                        $PROCESSED["objective_id"] = $tmp_input;
                    } else {
                        add_error("No objective provided.");
                    }

                    if (isset($request["afelement_id"]) && $tmp_input = clean_input($request["afelement_id"], array("trim", "int"))) {
                        $PROCESSED["afelement_id"] = $tmp_input;
                    } else {
                        add_error("No form element provided.");
                    }

                    if (!$ERROR) {
                        $data = array();

                        $form_element = Models_Assessments_Form_Element::fetchRowByID($PROCESSED["afelement_id"]);
                        if ($form_element) {
                            $data["afelement_objective"] = $form_element->getElementID();
                        }

                        $parent_objective = Models_Objective::fetchRow($PROCESSED["objective_id"]);
                        $objectives = Models_Objective::fetchAllByParentID($ENTRADA_USER->getActiveOrganisation(), $PROCESSED["objective_id"], $active = 1);

                        if ($parent_objective) {
                            $data["objective_parent"] = array("objective_parent_id" => $parent_objective->getID(), "objective_parent_name" => $parent_objective->getName());
                        }

                        if ($objectives) {
                            foreach ($objectives as $objective) {
                                $data["objectives"][] = array("objective_id" => $objective->getID(), "objective_name" => $objective->getName());
                            }
                            echo json_encode(array("status" => "success", "data" => $data));
                        } else {
                            echo json_encode(array("status" => "success", "data" => $data));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                case "get-parent-objectives" :
                    if (isset($request["objective_id"]) && $tmp_input = clean_input($request["objective_id"], array("trim", "int"))) {
                        $PROCESSED["objective_id"] = $tmp_input;
                    } else {
                        add_error("No objective provided.");
                    }

                    if (!$ERROR) {
                        $objective = Models_Objective::fetchRow($PROCESSED["objective_id"]);
                        if ($objective) {
                            $data["objective_parent"] = array("objective_parent_id" => $objective->getID(), "objective_parent_name" => $objective->getName());
                            $objectives = Models_Objective::fetchAllByParentID($ENTRADA_USER->getActiveOrganisation(), $objective->getParent(), $active = 1);
                            if ($objectives) {
                                foreach ($objectives as $objective) {
                                    $data["objectives"][] = array("objective_id" => $objective->getID(), "objective_name" => $objective->getName());
                                }
                            }
                            echo json_encode(array("status" => "success", "data" => $data));
                        } else {
                            add_error($translate->_("No objective found."));
                        }
                    }
                    break;
                case "get-competency-items" :
                    if (isset($request["objective_id"]) && $tmp_input = clean_input($request["objective_id"], array("trim", "int"))) {
                        $PROCESSED["objective_id"] = $tmp_input;
                    } else {
                        add_error("No objective provided.");
                    }

                    if (!$ERROR) {
                        $item = Models_Assessments_Item::fetchFieldNoteItem($PROCESSED["objective_id"]);
                        if ($item) {
                            $data = array("item_id" => $item->getID(), "itemtype_id" => $item->getItemtypeID(), "item_text" => $item->getItemText());

                            $item_responses = Models_Assessments_Item_Response::fetchAllRecordsByItemID($item->getID());

                            if ($item_responses) {
                                $response_data = array();
                                foreach ($item_responses as $item_response) {
                                    $response = array("iresponse_id" => $item_response->getID(), "text" => $item_response->getText());
                                    $ardescriptor = Models_Assessments_Response_Descriptor::fetchRowByIDIgnoreDeletedDate($item_response->getARDescriptorID());

                                    if ($ardescriptor) {
                                        $response["ardescriptor_id"] = $ardescriptor->getID();
                                        $response["descriptor"] = $ardescriptor->getDescriptor();
                                    }

                                    $response_data[] = $response;
                                }

                                $data["responses"] = $response_data;
                            }

                            echo json_encode(array("status" => "success", "data" => $data));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("No item responses found."))));
                        }
                    }
                    break;
            }
            break;
    }
    exit;
}