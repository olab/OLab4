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
 * Module:    Assessment (single form)
 * Area:    Public index
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Don Zuiker <don.zuiker@queensu.ca>
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_ASSESSMENT"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
// @todo figure out ACL } elseif (!$ENTRADA_ACL->amIAllowed(new AssessorResource($PROCESSED["aprogress_id"], $PROCESSED["adistribution_id"]), "update", true)) {
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "read", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a class=\"user-email\" href=\"#\">%s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] does not have access to this module [" . $MODULE . "]");
} else {
    $BREADCRUMB[] = array("url" => ENTRADA_URL . "/" . $MODULE . "/" . $SUBMODULE . "/" . $SECTION, "title" => "Assessment Task");
    $JAVASCRIPT_TRANSLATIONS[] = "current_target = '" . $translate->_("Currently Assessing") . "';";
    $JAVASCRIPT_TRANSLATIONS[] = "hide_assessment_error = '" . $translate->_("Please enter a comment.") . "';";

    $JQUERY[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/assessment.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $PROCESSED["objectives"] = array();

    $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = '" . ENTRADA_URL . "'</script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/assessments/rubrics.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/assessments/items.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/assessments/assessments.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/assessments/assessment-form.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";

    if (isset($_GET["aprogress_id"]) && $tmp_input = clean_input($_GET["aprogress_id"], array("trim", "int"))) {
        $PROCESSED["aprogress_id"] = $tmp_input;
    }

    $PROCESSED["pdf-error"] = false;
    if (isset($_GET["pdf-error"])) {
        $PROCESSED["pdf-error"] = true;
    }

    if (isset($_GET["dassessment_id"]) && $tmp_input = clean_input($_GET["dassessment_id"], array("trim", "int"))) {
        $PROCESSED["dassessment_id"] = $tmp_input;

        if (isset($_GET["view"]) && $tmp_input = clean_input($_GET["view"], array("trim", "striptags"))) {
            $PROCESSED["view"] = $tmp_input;
        } else if (isset($_POST["view"]) && $tmp_input = clean_input($_POST["view"], array("trim", "striptags"))) {
            $PROCESSED["view"] = $tmp_input;
        } else {
            $PROCESSED["view"] = "";
        }

        if (isset($PROCESSED["view"]) && $PROCESSED["view"] == "view_as") {
            if (isset($_GET["assessor_id"]) && $tmp_input = clean_input($_GET["assessor_id"], array("trim", "int"))) {
                $PROCESSED["assessor_id"] = $tmp_input;
            } elseif (isset($_POST["assessor_id"]) && $tmp_input = clean_input($_POST["assessor_id"], array("trim", "int"))) {
                $PROCESSED["assessor_id"] = $tmp_input;
            } else {
                add_error($translate->_("An error occurred while attempting to save responses for these targets. Please try again later."));
            }
        }

        $PROCESSED["proxy_id"] = (isset($PROCESSED["assessor_id"]) && $PROCESSED["assessor_id"] ? $PROCESSED["assessor_id"] : $ENTRADA_USER->getActiveID());

        $disabled = false;
        $organisation = null;
        $targets_pending = 0;
        $targets_inprogress = 0;
        $targets_complete = 0;
        $targets = array();
        $target_name = "";
        $distribution_data = array();
        $learning_event_data = array();
        $utilities_base = new Entrada_Utilities_Assessments_Base();
        $delivery_info_view = new Views_Assessments_Sidebar_DeliveryInfo();

        $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($PROCESSED["adistribution_id"]);
        $assessor_model = new Models_Assessments_Assessor();
        $assessment_record = $assessor_model->fetchRowByID($PROCESSED["dassessment_id"]);
        if ($assessment_record) {
            $assessment_overall_progress = $assessment_record->getOverallProgressDetails($PROCESSED["proxy_id"]);
            $targets_pending = (isset($assessment_overall_progress["targets_pending"]) && $assessment_overall_progress["targets_pending"] ? $assessment_overall_progress["targets_pending"] : 0);
            $targets_inprogress = (isset($assessment_overall_progress["targets_inprogress"]) && $assessment_overall_progress["targets_inprogress"] ? $assessment_overall_progress["targets_inprogress"] : 0);
            $targets_complete = (isset($assessment_overall_progress["targets_complete"]) && $assessment_overall_progress["targets_complete"] ? $assessment_overall_progress["targets_complete"] : 0);
            $individual_attempts_max = (isset($assessment_overall_progress["max_individual_attempts"]) && $assessment_overall_progress["max_individual_attempts"] ? $assessment_overall_progress["max_individual_attempts"] : 0);
            $overall_attempts_max = (isset($assessment_overall_progress["max_overall_attempts"]) && $assessment_overall_progress["max_overall_attempts"] ? $assessment_overall_progress["max_overall_attempts"] : 0);
            $overall_attempts_completed = (isset($assessment_overall_progress["overall_attempts_completed"]) && $assessment_overall_progress["overall_attempts_completed"] ? $assessment_overall_progress["overall_attempts_completed"] : 0);

            $delegator = (isset($assessment_overall_progress["delegator"]) && $assessment_overall_progress["delegator"] ? $assessment_overall_progress["delegator"] : false);

            $targets = (isset($assessment_overall_progress["targets"]) && $assessment_overall_progress["targets"] ? $assessment_overall_progress["targets"] : 0);
        } else {
            add_notice($translate->_("This assessment task has been deleted."));
        }

        if (isset($_GET["target_record_id"]) && $tmp_input = clean_input($_GET["target_record_id"], array("trim", "int"))) {
            $PROCESSED["target_record_id"] = $tmp_input;
        } elseif (isset($_POST["target_record_id"]) && $tmp_input = clean_input($_POST["target_record_id"], array("trim", "int"))) {
            $PROCESSED["target_record_id"] = $tmp_input;
        } elseif (isset($targets) && $targets) {
            // Attempt to find a non-complete target to display.
            $PROCESSED["target_record_id"] = false;
            if (is_array($targets) && count($targets) > 1) {
                foreach ($targets as $target) {
                    if ($target["progress"] != "complete") {
                        $PROCESSED["target_record_id"] = $target["target_record_id"];
                    }
                }
            }
            if (!$PROCESSED["target_record_id"]) {
                $PROCESSED["target_record_id"] = $targets[0]["target_record_id"];
            }
        } else {
            add_error($translate->_("An error occurred while attempting to save responses for this target. Please try again later."));
        }

        $distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($PROCESSED["adistribution_id"]);
        if ($distribution && $distribution->getDeletedDate()) {
            add_notice($translate->_("This assessment task's <strong>Distribution</strong> has been deleted and cannot be submitted."));
        }

        $distribution_target = Models_Assessments_Distribution_Target::fetchRowByDistributionIDTargetID($PROCESSED["adistribution_id"], $PROCESSED["target_record_id"]);
        // Target may be additional (via distribution progress) if there is no DistributionTarget in the database.
        $additional_flag = false;
        if (!$distribution_target) {
            $additional_targets = Models_Assessments_AdditionalTask::fetchAllByADistributionID($PROCESSED["adistribution_id"]);
            foreach ($additional_targets as $additional) {
                if ($additional->getTargetID() == $PROCESSED["target_record_id"]) {
                    $additional_flag = true;
                }
            }
            if ($additional_flag) {
                // We must build a temporary distribution target object so that the sidebar has data to work with. Additional targets are always individual proxy_ids.
                $distribution_target = new Models_Assessments_Distribution_Target(array(
                    "target_id" => $PROCESSED["target_record_id"],
                    "target_type" => "proxy_id",
                    "target_scope" => "any"
                ));
            } else {
                $distribution_target = Models_Assessments_Distribution_Target::fetchRowByDistributionID($PROCESSED["adistribution_id"]);
            }
        }

        // When a PDF fails to generate and/or send, we notify the user via this parameter.
        if ($PROCESSED["pdf-error"]) {
            add_error($translate->_("Unable to create PDF. Please try again."));
        }

        $assessment_statistic = new Models_Assessment_Statistic(array(
            "proxy_id" => $PROCESSED["proxy_id"],
            "created_date" => time(),
            "module" => $MODULE,
            "sub_module" => $SUBMODULE,
            "action" => "view",
            "assessment_id" => $PROCESSED["dassessment_id"],
            "distribution_id" => $PROCESSED["adistribution_id"],
            "target_id" => $PROCESSED["target_record_id"] ? $PROCESSED["target_record_id"] : null,
            "progress_id" => $PROCESSED["aprogress_id"] ? $PROCESSED["aprogress_id"] : null,
            "prune_after" => strtotime("+1 years")
        ));

        if (!$assessment_statistic->insert()) {
            application_log("error", "Error encountered while attempting to save history of an assessment statistic.");
        }

        switch ($STEP) {
            case 3 :
                if (isset($_POST["aprogress_id"]) && $tmp_input = clean_input($_POST["aprogress_id"], array("trim", "int"))) {
                    $PROCESSED["aprogress_id"] = $tmp_input;
                } else {
                    add_error($translate->_("An error occurred while attempting to save the response for this assessment task. Please try again later."));
                }

                if (isset($_POST["adistribution_id"]) && $tmp_input = clean_input($_POST["adistribution_id"], array("trim", "int"))) {
                    $PROCESSED["adistribution_id"] = $tmp_input;
                } else {
                    add_error($translate->_("An error occurred while attempting to save the response for this assessment task. Please try again later."));
                }

                if (isset($_POST["release_status"]) && $tmp_input = clean_input($_POST["release_status"], array("trim", "int"))) {
                    $PROCESSED["release_status"] = $tmp_input;
                } else {
                    add_error($translate->_("An error occurred while attempting to save the response for this assessment task. Please try again later."));
                }

                if ($PROCESSED["release_status"] - 1 == 0) {
                    if (isset($_POST["hide_assessment_comments"]) && $tmp_input = clean_input($_POST["hide_assessment_comments"], array("trim", "striptags"))) {
                        $PROCESSED["hide_assessment_comments"] = $tmp_input;
                    } else {
                        add_error($translate->_("An error occurred while attempting to save the response for this assessment task. Please try again later."));
                    }
                }

                if (!$ERROR) {
                    $release_progress = new Models_Assessments_Distribution_Approvals(array(
                        "aprogress_id" => $PROCESSED["aprogress_id"],
                        "adistribution_id" => $PROCESSED["adistribution_id"],
                        "approver_id" => $ENTRADA_USER->getActiveID(),
                        "release_status" => $PROCESSED["release_status"] - 1,
                        "comments" => $PROCESSED["release_status"] - 1 == 0 ? $PROCESSED["hide_assessment_comments"] : NULL,
                        "created_date" => time(),
                        "created_by" => $ENTRADA_USER->getActiveID()
                    ));

                    $url = ENTRADA_URL . "/assessments";
                    $approver_approvals = new Models_Assessments_Distribution_Approvals();
                    $approver_record = $approver_approvals->fetchRowByProgressIDDistributionID($PROCESSED["aprogress_id"], $PROCESSED["adistribution_id"]);
                    if ($approver_record || !$release_progress->insert()) {
                        add_error(sprintf($translate->_("An error occurred while attempting to insert a approver progress record. It may have already been reviewed by another approver. Please check 'My Completed Tasks'. You will now be redirected to My Assessments. This will happen <strong>automatically</strong> in 5 seconds or <a href=\"%s\" style=\"font-weight: bold\">click here</a> to continue."), $url));
                    } else {
                        add_success(sprintf($translate->_("Successfully reviewed this assessment task, thank you. You will now be redirected to My Assessments. This will happen <strong>automatically</strong> in 5 seconds or <a href=\"%s\" style=\"font-weight: bold\">click here</a> to continue."), $url));

                        if ($distribution && $distribution->getFeedbackRequired()) {

                            $distribution = Models_Assessments_Distribution::fetchRowByID($PROCESSED["adistribution_id"]);

                            if ($PROCESSED["release_status"] - 1 && isset($PROCESSED["target_record_id"])) {
                                $PROCESSED["notify_proxy_id"] = $PROCESSED["target_record_id"];
                                $utilities_base->queueCompletedNotification($PROCESSED["dassessment_id"], $PROCESSED["adistribution_id"], $PROCESSED["notify_proxy_id"], "assessment_submitted_notify_learner", $PROCESSED["aprogress_id"]);
                            }
                        }
                    }

                    $ONLOAD[] = "setTimeout('window.location=\\'" . $url . "\\'', 5000)";
                }
                break;
            case 2 :
                if (isset($_POST["adistribution_id"]) && $tmp_input = clean_input($_POST["adistribution_id"], array("trim", "int"))) {
                    $PROCESSED["adistribution_id"] = $tmp_input;
                } else {
                    add_error($translate->_("An error occurred while attempting to save responses for this target. Please try again later."));
                }

                $targets_map = array();
                if (isset($targets) && $targets) {
                    foreach ($targets as $target) {
                        if (!array_key_exists($target["target_record_id"], $targets_map)) {
                            $targets_map[$target["target_record_id"]] = $target["adtarget_id"];
                        }
                    }
                }

                if (isset($targets_map) && array_key_exists($PROCESSED["target_record_id"], $targets_map)) {
                    $adtarget_id = ($additional_flag ? 0 : $targets_map[$PROCESSED["target_record_id"]]);
                } elseif ($additional_flag) {
                    $adtarget_id = 0;
                } else {
                    add_error($translate->_("An error occurred while attempting to save responses for this target. Please try again later."));
                }

                if (!$ERROR) {
                    $method = "insert";
                    if (isset($_POST["aprogress_id"]) && $tmp_input = clean_input($_POST["aprogress_id"], array("trim", "int"))) {
                        $PROCESSED["aprogress_id"] = $tmp_input;
                        $method = "update";
                        $progress = Models_Assessments_Progress::fetchRowByID($PROCESSED["aprogress_id"]);
                    } else {
                        if (isset($PROCESSED["aprogress_id"]) && $PROCESSED["aprogress_id"]) {
                            $progress = Models_Assessments_Progress::fetchRowByID($PROCESSED["aprogress_id"]);
                        } else {
                            $progress = Models_Assessments_Progress::fetchRowByAdistributionIDAssessorTypeAssessorValueTargetRecordIDDAssessmentID($PROCESSED["adistribution_id"], $assessment_record->getAssessorType(), (isset($PROCESSED["assessor_id"]) && $PROCESSED["assessor_id"] ? $PROCESSED["assessor_id"] : $ENTRADA_USER->getAccessId()), $PROCESSED["target_record_id"], $PROCESSED["dassessment_id"], "inprogress");
                            if (!$progress) {
                                $progress = Models_Assessments_Progress::fetchRowByAdistributionIDAssessorTypeAssessorValueTargetRecordIDDAssessmentID($PROCESSED["adistribution_id"], $assessment_record->getAssessorType(), (isset($PROCESSED["assessor_id"]) && $PROCESSED["assessor_id"] ? $PROCESSED["assessor_id"] : $ENTRADA_USER->getAccessId()), $PROCESSED["target_record_id"], $PROCESSED["dassessment_id"], "complete");
                            }
                        }
                        if (isset($progress) && $progress) {
                            $PROCESSED["aprogress_id"] = $progress->getID();
                            $method = "update";
                        }
                    }

                    $PROCESSED["feedback_response"] = null;
                    if (isset($_POST["feedback_response"]) && $tmp_input = clean_input($_POST["feedback_response"], array("trim", "striptags"))) {
                        $PROCESSED["feedback_response"] = $tmp_input;
                    }

                    $PROCESSED["feedback_meeting_comments"] = null;
                    if (isset($_POST["feedback_meeting_comments"]) && $tmp_input = clean_input($_POST["feedback_meeting_comments"], array("trim", "striptags"))) {
                        $PROCESSED["feedback_meeting_comments"] = $tmp_input;
                    }

                    if ($distribution) {
                        if ($distribution->getFeedbackRequired() && isset($ENTRADA_USER) && $ENTRADA_USER) {
                            // Only validate assessor response for preceptors.
                            if ($PROCESSED["proxy_id"] != $PROCESSED["target_record_id"]) {
                                if (isset($_POST["assessor_feedback_response"]) && $tmp_input = clean_input($_POST["assessor_feedback_response"], array("trim", "striptags"))) {
                                    $PROCESSED["assessor_feedback_response"] = $tmp_input;
                                } else {
                                    if (isset($_POST["submit_form"])) {
                                        add_error($translate->_("Please select a response for <strong>Did you have an opportunity to meet with this trainee to discuss their performance?</strong>"));
                                    }
                                }
                            }
                        }
                    }

                    if (isset($progress) && $progress && $progress->getProgressValue() === "complete") {
                        if (!$ERROR) {
                            if ($PROCESSED["proxy_id"] == $PROCESSED["target_record_id"]) {
                                // Learner feedback
                                $assessment_record = Models_Assessments_Assessor::fetchRowByID($PROCESSED["dassessment_id"]);
                                if ($assessment_record) {
                                    $method = "insert";
                                    $PROCESSED["assessor_type"] = "internal";
                                    $PROCESSED["assessor_value"] = $assessment_record->getAssessorValue();
                                    $PROCESSED["target_type"] = "internal";
                                    $PROCESSED["target_value"] = $PROCESSED["target_record_id"];
                                    $PROCESSED["target_feedback"] = ($PROCESSED["feedback_response"] == "yes" ? 1 : 0);
                                    $PROCESSED["target_progress_value"] = "inprogress";
                                    $PROCESSED["progress_value"] = "inprogress";

                                    if ($PROCESSED["proxy_id"] == $PROCESSED["target_record_id"]) {
                                        $PROCESSED["comments"] = $PROCESSED["feedback_meeting_comments"];
                                    }

                                    if (isset($_POST["submit_form"])) {
                                        $PROCESSED["target_progress_value"] = "complete";
                                        $PROCESSED["progress_value"] = "complete";
                                    }

                                    $feedback_record = Models_Assessments_AssessorTargetFeedback::fetchRowByAssessorTarget($PROCESSED["dassessment_id"], "internal", $PROCESSED["assessor_value"], "internal", $PROCESSED["target_record_id"]);
                                    if ($feedback_record) {
                                        $method = "update";
                                    } else {
                                        $feedback_record = new Models_Assessments_AssessorTargetFeedback();
                                    }

                                    if ($method == "insert") {
                                        $PROCESSED["created_by"] = $PROCESSED["target_record_id"];
                                        $PROCESSED["created_date"] = time();
                                    } else {
                                        $PROCESSED["updated_by"] = $PROCESSED["target_record_id"];
                                        $PROCESSED["updated_date"] = time();
                                    }

                                    if (!$feedback_record->fromArray($PROCESSED)->$method()) {
                                        add_error($translate->_("A problem occurred while attempting to save your selected feedback option, please try again at a later time."));
                                    }
                                }
                            } else {
                                add_error($translate->_("You cannot update a submitted form."));
                            }
                        }
                    } else {
                        $PROCESSED["proxy_id"] = (isset($PROCESSED["assessor_id"]) && $PROCESSED["assessor_id"] ? $PROCESSED["assessor_id"] : $ENTRADA_USER->getActiveID());
                        $PROCESSED["progress_value"] = "inprogress";
                        $PROCESSED["target_learning_context_id"] = NULL;
                        $PROCESSED["adtarget_id"] = $adtarget_id;
                        $PROCESSED["assessor_type"] = "internal";

                        if ($method === "insert") {
                            $PROCESSED["uuid"] = Models_Assessments_Progress::generateUuid();
                            $PROCESSED["assessor_value"] = $PROCESSED["proxy_id"];
                            $PROCESSED["created_date"] = time();
                            $PROCESSED["created_by"] = $ENTRADA_USER->getActiveID();
                            $progress = new Models_Assessments_Progress($PROCESSED);
                        } else {
                            $PROCESSED["updated_date"] = time();
                            $PROCESSED["updated_by"] = $ENTRADA_USER->getActiveID();
                            $progress = Models_Assessments_Progress::fetchRowByID($PROCESSED["aprogress_id"]);
                        }

                        if (!$progress->fromArray($PROCESSED)->$method()) {
                            add_error($translate->_("We were unable to save the form at this time. Please try again at a later time."));
                            application_log("error", "A problem occurred while " . $method . "ing a progress record DB said: " . $db->ErrorMsg());
                        } else {
                            $distribution_assessment = Models_Assessments_Assessor::fetchRowByID($PROCESSED["dassessment_id"]);

                            if($distribution_assessment) {
                                $current_task_snapshot = Models_Assessments_CurrentTaskSnapshot::fetchRowByDistributionIDAssessmentIDAssessorValueTargetValueDeliveryDate($distribution_assessment->getADistributionID(), $PROCESSED["dassessment_id"], $distribution_assessment->getAssessorValue(), $PROCESSED["target_record_id"], $distribution_assessment->getDeliveryDate());
                                if ($current_task_snapshot) {
                                    $current_task_snapshot->delete();
                                }
                            }
                        }
                    }

                    if (!$ERROR && ($PROCESSED["proxy_id"] != $PROCESSED["target_record_id"]) || (($distribution_target) && $distribution_target->getTargetType() == "self")) {
                        $send_flagging_notification = false;
                        $form = Models_Assessments_Form::fetchRowByID($PROCESSED["form_id"]);
                        if ($form) {
                            $elements = Models_Assessments_Form_Element::fetchAllByFormID($form->getID());
                            if ($elements) {
                                foreach ($elements as $element) {
                                    $PROCESSED_RESPONSE = array();
                                    $PROCESSED_RESPONSE["aprogress_id"] = $progress->getID();
                                    $PROCESSED_RESPONSE["form_id"] = $form->getID();
                                    $PROCESSED_RESPONSE["adistribution_id"] = $PROCESSED["adistribution_id"];
                                    $PROCESSED_RESPONSE["assessor_type"] = "internal";
                                    $PROCESSED_RESPONSE["assessor_value"] = $PROCESSED["proxy_id"];
                                    $PROCESSED_RESPONSE["afelement_id"] = $element->getID();

                                    switch ($element->getElementType()) {
                                        case "item" :
                                            $item = Models_Assessments_Item::fetchRowByIDIncludeDeleted($element->getElementID());

                                            if ($item) {
                                                if (is_null($element->getRubricID())) {
                                                    $key = "item-" . $element->getElementID();
                                                } else {
                                                    $key = "rubric-item-" . $element->getRubricID() . "-" . $element->getElementID();
                                                }

                                                if (array_key_exists($key, $_POST)) {
                                                    switch ($item->getItemtypeID()) {
                                                        case "1" :
                                                        case "2" :
                                                        case "3" :
                                                        case "9" :
                                                        case "11" :
                                                        case "12" :
                                                            /*
                                                             * Validates and stores single response items
                                                             */
                                                            if ($tmp_input = clean_input($_POST[$key], array("trim", "int"))) {
                                                                $PROCESSED_RESPONSE["iresponse_id"] = $tmp_input;
                                                                $response_method = "insert";
                                                                $response = Models_Assessments_Progress_Response::fetchRowByAprogressIDAfelementID($progress->getID(), $element->getID());

                                                                $comments_key = "item-" . $element->getElementID() . "-comments";

                                                                if (isset($_POST[$comments_key]) && ($tmp_input = clean_input($_POST[$comments_key], array("trim", "striptags")))) {
                                                                    $PROCESSED_RESPONSE["comments"] = $tmp_input;
                                                                } else {
                                                                    $PROCESSED_RESPONSE["comments"] = NULL;
                                                                    $iresponse = Models_Assessments_Item_Response::fetchRowByID($PROCESSED_RESPONSE["iresponse_id"]);

                                                                    // Check if comments are mandatory
                                                                    if ($item->getCommentType() == "mandatory" || ($item->getCommentType() == "flagged" && $iresponse && $iresponse->getFlagResponse())) {
                                                                        if (isset($_POST["submit_form"])) {
                                                                            add_error(sprintf($translate->_("Please comment on: <strong>%s</strong>"), html_encode($item->getItemText())));
                                                                        }
                                                                    }
                                                                }
                                                                $iresponse = Models_Assessments_Item_Response::fetchRowByID($PROCESSED_RESPONSE["iresponse_id"]);
                                                                if ($iresponse && isset($_POST["submit_form"]) && $distribution->getFlaggingNotifications() != "disabled" && $iresponse->getFlagResponse()) {
                                                                    $send_flagging_notification = true;
                                                                }

                                                                if ($response) {
                                                                    $response_method = "update";
                                                                    $PROCESSED_RESPONSE["updated_date"] = time();
                                                                    $PROCESSED_RESPONSE["updated_by"] = $ENTRADA_USER->getActiveId();
                                                                } else {
                                                                    $response = new Models_Assessments_Progress_Response($PROCESSED_RESPONSE);
                                                                    $PROCESSED_RESPONSE["created_date"] = time();
                                                                    $PROCESSED_RESPONSE["created_by"] = $ENTRADA_USER->getActiveId();
                                                                }

                                                                if (($response_method === "update" && $response->getComments() !== $PROCESSED_RESPONSE["comments"]) || ($response_method === "insert")) {
                                                                    if (!$response->fromArray($PROCESSED_RESPONSE)->$response_method()) {
                                                                        add_error($translate->_("We were unable to save the form at this time. Please try again at a later time."));
                                                                        application_log("error", "A problem occurred while " . $method . "ing a progress response records DB said: " . $db->ErrorMsg());
                                                                    }
                                                                }
                                                            } else {
                                                                if ($item->getMandatory()) {
                                                                    // It is possible to receive an empty response for dropdown items as they default to a blank option.
                                                                    if (isset($_POST["submit_form"])) {
                                                                        add_error(sprintf($translate->_("Please select a response for: <strong>%s</strong>"), html_encode($item->getItemText())));
                                                                    }
                                                                }
                                                            }
                                                            break;
                                                        case "4" :
                                                        case "5" :
                                                        case "6" :
                                                            /*
                                                             * Validates and stores multi response items
                                                             */
                                                            if (is_array($_POST[$key])) {
                                                                foreach ($_POST[$key] as $key => $response) {
                                                                    if ($tmp_input = clean_input($response, array("trim", "int"))) {
                                                                        $PROCESSED_RESPONSE["responses"][] = $tmp_input;
                                                                    }
                                                                }
                                                            }

                                                            if (isset($PROCESSED_RESPONSE["responses"]) && is_array($PROCESSED_RESPONSE["responses"])) {
                                                                $element_responses = Models_Assessments_Progress_Response::fetchAllByAprogressIDAfelementID($progress->getID(), $element->getID());
                                                                $response_error = false;

                                                                if ($element_responses) {
                                                                    foreach ($element_responses as $element_response) {
                                                                        if (!$element_response->delete()) {
                                                                            $response_error = true;
                                                                        }
                                                                    }
                                                                }

                                                                if ($response_error) {
                                                                    add_error($translate->_("A problem occurred while attempting to update the selected responses, please try again at a later time."));
                                                                }

                                                                if (!$response_error) {
                                                                    foreach ($PROCESSED_RESPONSE["responses"] as $response_id) {
                                                                        $PROCESSED_RESPONSE["iresponse_id"] = $response_id;
                                                                        $PROCESSED_RESPONSE["created_date"] = time();
                                                                        $PROCESSED_RESPONSE["created_by"] = $ENTRADA_USER->getActiveId();

                                                                        $comments_key = "item-" . $element->getElementID() . "-comments";

                                                                        if (isset($_POST[$comments_key]) && ($tmp_input = clean_input($_POST[$comments_key], array("trim", "striptags")))) {
                                                                            $PROCESSED_RESPONSE["comments"] = $tmp_input;
                                                                        } else {
                                                                            $PROCESSED_RESPONSE["comments"] = NULL;
                                                                            $iresponse = Models_Assessments_Item_Response::fetchRowByID($PROCESSED_RESPONSE["iresponse_id"]);

                                                                            // Check if comments are mandatory
                                                                            if ($item->getCommentType() == "mandatory" || ($item->getCommentType() == "flagged" && $iresponse && $iresponse->getFlagResponse())) {
                                                                                if (isset($_POST["submit_form"])) {
                                                                                    add_error(sprintf($translate->_("Please comment on: <strong>%s</strong>"), html_encode($item->getItemText())));
                                                                                }
                                                                            }
                                                                        }

                                                                        $iresponse = Models_Assessments_Item_Response::fetchRowByID($PROCESSED_RESPONSE["iresponse_id"]);
                                                                        if ($iresponse && isset($_POST["submit_form"]) && $distribution->getFlaggingNotifications() != "disabled" && $iresponse->getFlagResponse()) {
                                                                            $send_flagging_notification = true;
                                                                        }

                                                                        $response = new Models_Assessments_Progress_Response($PROCESSED_RESPONSE);

                                                                        if (!$response->fromArray($PROCESSED_RESPONSE)->insert()) {
                                                                            add_error($translate->_("We were unable to save the form at this time. Please try again at a later time."));
                                                                            application_log("error", "A problem occurred while " . $method . "ing a progress response records DB said: " . $db->ErrorMsg());
                                                                        }
                                                                    }
                                                                }
                                                            } else {
                                                                if ($item->getMandatory()) {
                                                                    if (isset($_POST["submit_form"])) {
                                                                        add_error(sprintf($translate->_("Please select a response for: <strong>%s</strong>"), html_encode($item->getItemText())));
                                                                    }
                                                                }
                                                            }
                                                            break;
                                                        case "7" :
                                                        case "10" :
                                                            /*
                                                             * Validates and stores free text items
                                                             */
                                                            if ($tmp_input = clean_input($_POST[$key], array("trim", "striptags"))) {
                                                                $PROCESSED_RESPONSE["comments"] = $tmp_input;
                                                                $PROCESSED_RESPONSE["iresponse_id"] = NULL;

                                                                $response_method = "insert";
                                                                $response = Models_Assessments_Progress_Response::fetchRowByAprogressIDAfelementID($progress->getID(), $element->getID());

                                                                if ($response) {
                                                                    $response_method = "update";
                                                                    $PROCESSED_RESPONSE["updated_date"] = time();
                                                                    $PROCESSED_RESPONSE["updated_by"] = $ENTRADA_USER->getActiveId();
                                                                } else {
                                                                    $response = new Models_Assessments_Progress_Response($PROCESSED_RESPONSE);
                                                                    $PROCESSED_RESPONSE["created_date"] = time();
                                                                    $PROCESSED_RESPONSE["created_by"] = $ENTRADA_USER->getActiveId();
                                                                }

                                                                if (($response_method === "update" && $response->getComments() !== $PROCESSED_RESPONSE["comments"]) || ($response_method === "insert")) {
                                                                    if (!$response->fromArray($PROCESSED_RESPONSE)->$response_method()) {
                                                                        add_error($translate->_("We were unable to save the form at this time. Please try again at a later time."));
                                                                        application_log("error", "A problem occurred while " . $method . "ing a progress response records DB said: " . $db->ErrorMsg());
                                                                    }
                                                                }
                                                            } else {
                                                                if ($item->getMandatory()) {
                                                                    if (isset($_POST["submit_form"])) {
                                                                        add_error(sprintf($translate->_("Please select a response for: <strong>%s</strong>"), html_encode($item->getItemText())));
                                                                    }
                                                                }
                                                            }
                                                            break;
                                                        case "8" :
                                                            /*
                                                             * Validates and stores date items
                                                             */
                                                            if ($tmp_input = clean_input($_POST[$key], array("trim", "striptags"))) {
                                                                $PROCESSED_RESPONSE["comments"] = strtotime($tmp_input);
                                                                $PROCESSED_RESPONSE["iresponse_id"] = NULL;

                                                                $response_method = "insert";
                                                                $response = Models_Assessments_Progress_Response::fetchRowByAprogressIDAfelementID($progress->getID(), $element->getID());

                                                                if ($response) {
                                                                    $response_method = "update";
                                                                    $PROCESSED_RESPONSE["updated_date"] = time();
                                                                    $PROCESSED_RESPONSE["updated_by"] = $ENTRADA_USER->getActiveId();
                                                                } else {
                                                                    $response = new Models_Assessments_Progress_Response($PROCESSED_RESPONSE);
                                                                    $PROCESSED_RESPONSE["created_date"] = time();
                                                                    $PROCESSED_RESPONSE["created_by"] = $ENTRADA_USER->getActiveId();
                                                                }

                                                                if (($response_method === "update" && $response->getComments() !== $PROCESSED_RESPONSE["comments"]) || ($response_method === "insert")) {
                                                                    if (!$response->fromArray($PROCESSED_RESPONSE)->$response_method()) {
                                                                        add_error($translate->_("We were unable to save the form at this time. Please try again at a later time."));
                                                                        application_log("error", "A problem occurred while " . $method . "ing a progress response records DB said: " . $db->ErrorMsg());
                                                                    }
                                                                }
                                                            } else {
                                                                if ($item->getMandatory()) {
                                                                    if (isset($_POST["submit_form"])) {
                                                                        add_error(sprintf($translate->_("Please select a response for: <strong>%s</strong>"), html_encode($item->getItemText())));
                                                                    }
                                                                }
                                                            }
                                                            break;
                                                    }
                                                } else {
                                                    if (isset($_POST["submit_form"]) && $item->getMandatory()) {
                                                        add_error(sprintf($translate->_("Please select a response for: <strong>%s</strong>"), html_encode($item->getItemText())));
                                                    }
                                                }
                                            } else {
                                                add_error($translate->_("A problem occurred while attempting to save your selected responses, please try again at a later time."));
                                            }
                                            break;
                                        case "objective" :
                                            $key = "objective-" . $element->getElementID();

                                            if (isset($_POST["afelement_objectives"][$element->getID()]) && is_array($_POST["afelement_objectives"][$element->getID()])) {
                                                foreach ($_POST["afelement_objectives"][$element->getID()] as $objective_id) {
                                                    if ($tmp_input = clean_input($objective_id, array("trim", "int"))) {
                                                        $PROCESSED["objectives"][$element->getID()][] = $tmp_input;
                                                    }
                                                }
                                            }

                                            if (array_key_exists($key, $_POST)) {
                                                if ($tmp_input = clean_input($_POST[$key], array("trim", "int"))) {
                                                    $PROCESSED["objective-" . $element->getID()] = $tmp_input;
                                                    $PROCESSED_RESPONSE["iresponse_id"] = $tmp_input;
                                                    $response_method = "insert";
                                                    $response = Models_Assessments_Progress_Response::fetchRowByAprogressIDAfelementID($progress->getID(), $element->getID());

                                                    if ($response) {
                                                        $response_method = "update";
                                                        $PROCESSED_RESPONSE["updated_date"] = time();
                                                        $PROCESSED_RESPONSE["updated_by"] = $ENTRADA_USER->getActiveId();
                                                    } else {
                                                        $response = new Models_Assessments_Progress_Response($PROCESSED_RESPONSE);
                                                        $PROCESSED_RESPONSE["created_date"] = time();
                                                        $PROCESSED_RESPONSE["created_by"] = $ENTRADA_USER->getActiveId();
                                                    }

                                                    if (($response_method === "update" && (int)$response->getIresponseID() !== $PROCESSED_RESPONSE["iresponse_id"]) || ($response_method === "insert")) {
                                                        if (!$response->fromArray($PROCESSED_RESPONSE)->$response_method()) {
                                                            add_error($translate->_("We were unable to save the form at this time. Please try again at a later time."));
                                                            application_log("error", "A problem occurred while " . $method . "ing a progress response records DB said: " . $db->ErrorMsg());
                                                        }
                                                    }
                                                }
                                            } else {
                                                if (isset($_POST["submit_form"])) {
                                                    $objective = Models_Objective::fetchRow($element->getElementID());
                                                    if ($objective) {
                                                        add_error(sprintf($translate->_("Please select a response for: <strong>%s</strong>"), html_encode($objective->getName())));
                                                    }
                                                }
                                            }
                                            break;
                                    }
                                }
                            }

                            if (isset($PROCESSED["feedback_response"])) {
                                if ($PROCESSED["proxy_id"] != $PROCESSED["target_record_id"]) {
                                    // Preceptor feedback
                                    $method = "insert";
                                    $PROCESSED["assessor_type"] = "internal";
                                    $PROCESSED["assessor_value"] = $PROCESSED["proxy_id"];
                                    $PROCESSED["assessor_feedback"] = ($PROCESSED["feedback_response"] == "yes" ? 1 : 0);
                                    $PROCESSED["target_type"] = "internal";
                                    $PROCESSED["target_value"] = $PROCESSED["target_record_id"];

                                    $feedback_record = Models_Assessments_AssessorTargetFeedback::fetchRowByAssessorTarget($PROCESSED["dassessment_id"], "internal", $PROCESSED["assessor_value"], "internal", $PROCESSED["target_record_id"]);
                                    if ($feedback_record) {
                                        $method = "update";
                                    } else {
                                        $feedback_record = new Models_Assessments_AssessorTargetFeedback();
                                    }

                                    if ($method == "insert") {
                                        $PROCESSED["created_by"] = $PROCESSED["proxy_id"];
                                        $PROCESSED["created_date"] = time();
                                    } else {
                                        $PROCESSED["updated_by"] = $PROCESSED["proxy_id"];
                                        $PROCESSED["updated_date"] = time();
                                    }

                                    if (!$feedback_record->fromArray($PROCESSED)->$method()) {
                                        add_error($translate->_("A problem occurred while attempting to save your selected feedback option, please try again at a later time."));
                                    } else {
                                        if (isset($_POST["submit_form"]) && !$ERROR) {
                                            Models_Assessments_AssessorTargetFeedback::sendFeedbackNotification($PROCESSED["target_record_id"], $PROCESSED["aprogress_id"], $PROCESSED["proxy_id"], $PROCESSED["adistribution_id"]);
                                        }
                                    }
                                }
                            }
                        } else {
                            add_error($translate->_("A problem occurred while attempting to save your selected responses, please try again at a later time."));
                        }
                    }

                    if (!$ERROR) {
                        if (isset($_POST["submit_form"])) {
                            if ($ENTRADA_SETTINGS->read("flagging_notifications", $distribution->getOrganisationID())) {
                                if (isset($send_flagging_notification) && $send_flagging_notification) {
                                    Models_Assessments_Notification::sendFlaggingNotification($PROCESSED["target_record_id"], $PROCESSED["aprogress_id"], $PROCESSED["proxy_id"], $PROCESSED["adistribution_id"]);
                                }
                            }
                            $PROCESSED["progress_value"] = "complete";
                            if (!$progress->fromArray($PROCESSED)->update()) {
                                add_error($translate->_("We were unable to save the form at this time. Please try again at a later time."));
                                application_log("error", "A problem occurred while updating a progress record DB said: " . $db->ErrorMsg());
                            } else {
                                $distribution_assessment = Models_Assessments_Assessor::fetchRowByID($PROCESSED["dassessment_id"]);

                                if($distribution_assessment) {
                                    $current_task_snapshot = Models_Assessments_CurrentTaskSnapshot::fetchRowByDistributionIDAssessmentIDAssessorValueTargetValueDeliveryDate($distribution_assessment->getADistributionID(), $PROCESSED["dassessment_id"], $distribution_assessment->getAssessorValue(), $PROCESSED["target_record_id"], $distribution_assessment->getDeliveryDate());
                                    if ($current_task_snapshot) {
                                        $current_task_snapshot->delete();
                                    }
                                }
                            }
                        }
                    }
                }

                if ($ERROR) {
                    $STEP = 1;
                } else {
                    $distribution = Models_Assessments_Distribution::fetchRowByID($PROCESSED["adistribution_id"]);
                    $url = ENTRADA_URL . "/assessments";

                    if ($distribution && $distribution->getFeedbackRequired() && isset($_POST["submit_form"])) {
                        $progress_record = Models_Assessments_Progress::fetchRowByID($PROCESSED["aprogress_id"]);

                        if ($progress_record) {
                            if ($progress_record->getAssessorValue() == $ENTRADA_USER->getActiveID() || isset($PROCESSED["assessor_id"])) {

                                if (isset($PROCESSED["assessor_id"])) {
                                    $PROCESSED["notify_proxy_id"] = $PROCESSED["assessor_id"];
                                    $utilities_base->queueCompletedNotification($PROCESSED["dassessment_id"], $PROCESSED["adistribution_id"], $PROCESSED["notify_proxy_id"], "assessment_submitted", $PROCESSED["adistribution_id"]);
                                    $url = ENTRADA_URL . "/admin/assessments/distributions?section=progress&adistribution_id=" . $PROCESSED["adistribution_id"];
                                }

                                $distribution_approver = new Models_Assessments_Distribution_Approver();
                                $approver_records = $distribution_approver->fetchAllByDistributionID($PROCESSED["adistribution_id"]);

                                if (count($approver_records) > 0) {
                                    foreach ($approver_records as $approver_record) {
                                        $PROCESSED["notify_proxy_id"] = $approver_record->getProxyID();
                                        $utilities_base->queueCompletedNotification($PROCESSED["dassessment_id"], $PROCESSED["adistribution_id"], $PROCESSED["notify_proxy_id"], "assessment_submitted_notify_approver", $PROCESSED["aprogress_id"]);
                                    }
                                } else {
                                    $assessment_record = Models_Assessments_Assessor::fetchRowByID($PROCESSED["dassessment_id"]);
                                    if ($assessment_record) {
                                        $PROCESSED["notify_proxy_id"] = $PROCESSED["target_record_id"];
                                        $utilities_base->queueCompletedNotification($PROCESSED["dassessment_id"], $PROCESSED["adistribution_id"], $PROCESSED["notify_proxy_id"], "assessment_submitted_notify_learner", $PROCESSED["aprogress_id"]);
                                    }
                                }

                                if (count($targets) > 1) {
                                    $url = ENTRADA_URL . "/assessments/assessment?section=targets&adistribution_id=" . $PROCESSED["adistribution_id"] . "&dassessment_id=" . $PROCESSED["dassessment_id"];
                                }
                            }
                        }
                    }

                    add_success(sprintf($translate->_("Successfully " . ($PROCESSED["progress_value"] == "complete" ? "completed" : "saved") . " form, thank you. You will now be redirected to " . (isset($PROCESSED["assessor_id"]) ? "the Distribution Progress page" : "the Assessments index page") . ". This will happen <strong>automatically</strong> in 5 seconds or <a href=\"%s\" style=\"font-weight: bold\">click here</a> to continue."), $url));
                    $ONLOAD[] = "setTimeout('window.location=\\'" . $url . "\\'', 5000)";
                }
                break;
            case 1 :

                if (isset($_GET["adistribution_id"]) && $tmp_input = clean_input($_GET["adistribution_id"], array("trim", "int"))) {
                    $PROCESSED["adistribution_id"] = $tmp_input;
                } else {
                    add_error($translate->_("An error occurred while attempting to save responses for these targets. Please try again later."));
                }

                if (isset($PROCESSED["target_record_id"]) && $PROCESSED["target_record_id"]) {
                    // Check to ensure the target has not been deleted via distribution progress.
                    if ($assessment_record) {
                        $deleted_task = Models_Assessments_DeletedTask::fetchRowByADistributionIDAssessorTypeAssessorValueTargetIDDeliveryDate($distribution->getID(), "internal", $assessment_record->getAssessorValue(), $PROCESSED["target_record_id"], $assessment_record->getDeliveryDate());
                        if ($deleted_task) {
                            $deleted_by_user = Models_User::fetchRowByID($deleted_task->getCreatedBy());
                            if ($deleted_by_user) {
                                add_error($translate->_("This assessment task has been deleted by " . $deleted_by_user->getFullname(false) . " (<a class=\"user-email\" href=\"#\"" . $deleted_by_user->getEmail() . "\" target=\"_top\">" . $deleted_by_user->getEmail() . "</a>). Please contact the provided email address if you require more information."));
                            } else {
                                add_error($translate->_("This assessment task has been deleted."));
                            }
                        }
                    }
                } else {
                    add_error($translate->_("An error occurred while attempting to save responses for these targets. Please try again later."));
                }

                if (!$ERROR) {
                    if (isset($PROCESSED["aprogress_id"]) && $PROCESSED["aprogress_id"]) {
                        $progress_record = Models_Assessments_Progress::fetchRowByID($PROCESSED["aprogress_id"]);
                    } else {
                        $progress_record = Models_Assessments_Progress::fetchRowByAdistributionIDAssessorTypeAssessorValueTargetRecordID($PROCESSED["adistribution_id"], $assessment_record->getAssessorType(), (isset($PROCESSED["assessor_id"]) ? $PROCESSED["assessor_id"] : $ENTRADA_USER->getActiveId()), $PROCESSED["target_record_id"], "inprogress");
                        if (!$progress_record) {
                            $progress_record = Models_Assessments_Progress::fetchRowByAdistributionIDAssessorTypeAssessorValueTargetRecordID($PROCESSED["adistribution_id"], $assessment_record->getAssessorType(), (isset($PROCESSED["assessor_id"]) ? $PROCESSED["assessor_id"] : $ENTRADA_USER->getActiveId()), $PROCESSED["target_record_id"], "complete");
                        }
                    }
                    if ($progress_record) {
                        $responses = Models_Assessments_Progress_Response::fetchAllByAprogressID($progress_record->getID());
                        if ($responses) {
                            foreach ($responses as $response) {
                                $form_element = Models_Assessments_Form_Element::fetchRowByID($response->getAfelementID());
                                if ($form_element) {
                                    if ($form_element->getElementType() == "objective") {
                                        $item = Models_Assessments_Item::fetchItemByResponseID($response->getIResponseID());
                                        if ($item) {
                                            $item_objective = Models_Assessments_Item_Objective::fetchRowByItemID($item->getID());
                                            if ($item_objective) {
                                                $objective = Models_Objective::fetchRow($item_objective->getObjectiveID());
                                                if ($objective) {
                                                    $item_objective->buildObjectiveList($objective->getParent(), $item_objective->getObjectiveID());
                                                    if (!empty($item_objective->objective_tree)) {
                                                        asort($item_objective->objective_tree);
                                                        foreach ($item_objective->objective_tree as $objective_id) {
                                                            if ($tmp_input = clean_input($objective_id, array("trim", "int"))) {
                                                                $PROCESSED["objectives"][$form_element->getID()][] = $tmp_input;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                break;
        }

        switch ($STEP) {
            case 3:
                echo display_status_messages();
                break;
            case 2:
                echo display_status_messages();
                break;
            case 1:
                echo display_status_messages();
            default:
                if (isset($PROCESSED["adistribution_id"])) {
                    if ((!isset($deleted_task) || !$deleted_task) && $assessment_record) {
                        if (isset($PROCESSED["target_record_id"]) && $PROCESSED["target_record_id"]) {
                            $form = Models_Assessments_Form::fetchRowByID($distribution->getFormID());
                            if (isset($PROCESSED["aprogress_id"]) && $PROCESSED["aprogress_id"]) {
                                $progress_record = Models_Assessments_Progress::fetchRowByID($PROCESSED["aprogress_id"]);
                            } else {
                                if (isset($overall_attempts_completed) && isset($overall_attempts_max) && $overall_attempts_completed < $overall_attempts_max) {
                                    $progress_record = Models_Assessments_Progress::fetchRowByDassessmentIDAssessorTypeAssessorValueTargetRecordID($PROCESSED["dassessment_id"], $assessment_record->getAssessorType(), $assessment_record->getAssessorValue(), $PROCESSED["target_record_id"], "inprogress");
                                    if (!$progress_record) {
                                        $progress_record = Models_Assessments_Progress::fetchRowByDassessmentIDAssessorTypeAssessorValueTargetRecordID($PROCESSED["dassessment_id"], $assessment_record->getAssessorType(), $assessment_record->getAssessorValue(), $PROCESSED["target_record_id"], "complete");
                                    }
                                } else {
                                    $progress_record = Models_Assessments_Progress::fetchRowByDassessmentIDAssessorTypeAssessorValueTargetRecordID($PROCESSED["dassessment_id"], $assessment_record->getAssessorType(), $assessment_record->getAssessorValue(), $PROCESSED["target_record_id"]);
                                }
                                if ($progress_record) {
                                    $PROCESSED["aprogress_id"] = $progress_record->getID();
                                }
                            }

                            $restrict_view = false;
                            // Learners cannot view assessments on them unless it has been completed and released (where applicable).
                            if ($ENTRADA_USER->getActiveID() == $PROCESSED["target_record_id"] &&
                                ($assessment_record->getAssessorValue() != $ENTRADA_USER->getActiveId() &&
                                $assessment_record->getAssessorType() == "proxy_id")) {
                                if (!isset($progress_record) || !$progress_record) {
                                    $restrict_view = true;
                                } else {
                                    if ($progress_record->getProgressValue() != "complete") {
                                        $restrict_view = true;
                                    } else {
                                        $distribution_approver = new Models_Assessments_Distribution_Approver();
                                        $approver_records = $distribution_approver->fetchAllByDistributionID($distribution->getID());
                                        if ($approver_records) {
                                            $release = new Models_Assessments_Distribution_Approvals();
                                            $release = $release->fetchRowByProgressIDDistributionID($progress_record->getID(), $distribution->getID());
                                            if (!$release || $release->getReleaseStatus() != 1) {
                                                $restrict_view = true;
                                            }
                                        }
                                    }
                                }
                            }

                            //if ((!$progress_record && $ENTRADA_ACL->amIAllowed(new AssessorResource($PROCESSED["dassessment_id"]), "create", true)) || ($progress_record && $progress_record->getProgressValue() != "complete" && $ENTRADA_ACL->amIAllowed(new AssessmentProgressResource($PROCESSED["aprogress_id"]), "update", true)) || ($progress_record && $progress_record->getProgressValue() == "complete" && $ENTRADA_ACL->amIAllowed(new AssessmentResultResource($PROCESSED["aprogress_id"]), "read", true))) {
                            if (!$restrict_view) {

                                $distribution_data = array(
                                    "adistribution_id" => $distribution->getID(),
                                    "organisation_id" => $distribution->getOrganisationID(),
                                    "proxy_id" => $PROCESSED["proxy_id"],
                                    "form_id" => $form->getID(),
                                    "assessor_value" => (isset($PROCESSED["assessor_id"]) ? $PROCESSED["assessor_id"] : $ENTRADA_USER->getActiveId()),
                                    "target_record_id" => $PROCESSED["target_record_id"],
                                    "dassessment_id" => $PROCESSED["dassessment_id"]
                                );

                                if ($progress_record) {
                                    $distribution_data["aprogress_id"] = $progress_record->getID();
                                    if (!isset($PROCESSED["target_record_id"]) || !$PROCESSED["target_record_id"]) {
                                        $PROCESSED["target_record_id"] = $progress_record->getTargetRecordID();
                                    }
                                } else {
                                    $distribution_data["aprogress_id"] = 0;
                                }

                                // Do not allow target switching, etc. for externals and users who are not the assessor.
                                if ($assessment_record->getAssessorType() == "external" || $assessment_record->getAssessorValue() != $ENTRADA_USER->getActiveID()) {
                                    $review_mode = true;
                                } else {
                                    $review_mode = false;
                                }

                                $assessor_fullname = "";
                                if (isset($targets) && $targets && !$review_mode) {
                                    switch ($distribution_target->getTargetType()) {
                                        case "schedule_id" :
                                            switch ($distribution_target->getTargetScope()) {
                                                case "self" :
                                                    $target = Models_Schedule::fetchRowByID($PROCESSED["target_record_id"]);
                                                    if ($target) {
                                                        $target_name = html_encode($target->getTitle());
                                                        $sidebar_html = "<h4 class=\"course-target-heading\">" . $translate->_("Currently Assessing:") . "</h4>";
                                                        $sidebar_html .= "<div class=\"user-metadata\">";
                                                        $sidebar_html .= "  <p class=\"course-name\">" . html_encode($target->getTitle()) . "</p>";
                                                        $organisation = Organisation::get($target->getOrganisationID());
                                                        if ($organisation) {
                                                            $sidebar_html .= "  <p class=\"course-organisation\">" . html_encode($organisation->getTitle()) . "</p>";
                                                        }
                                                        $sidebar_html .= "</div>";
                                                        $sidebar_html .= $delivery_info_view->render(array("assessment_record" => $assessment_record, "distribution" => $distribution, "distribution_schedule" => $distribution_schedule), false);
                                                        $target_progress_records = Models_Assessments_Progress::fetchAllByDassessmentIDAssessorTypeAssessorValueTargetRecordID($PROCESSED["dassessment_id"], "internal", ((isset($PROCESSED["assessor_id"]) && $PROCESSED["assessor_id"]) ? $PROCESSED["assessor_id"] : $ENTRADA_USER->getActiveID()), $PROCESSED["target_record_id"]);
                                                        if ($target_progress_records && @count($target_progress_records) > 1) {
                                                            $sidebar_html .= "<h3>Attempts</h3>";
                                                            $sidebar_html .= "<ul class=\"menu none\">\n";
                                                            foreach ($target_progress_records as $target_progress_record) {
                                                                if ($target_progress_record->getProgressValue() == "complete") {
                                                                    $sidebar_html .= (isset($progress_record) && $target_progress_record->getID() == $progress_record->getID() ? "<strong>" : "") . "<li><a href=\"" . ENTRADA_URL . "/assessments/assessment?" . replace_query(array("aprogress_id" => $target_progress_record->getID())) . "\">" . sprintf($translate->_("Completed %s"), date("M d/y h:ia", $target_progress_record->getUpdatedDate())) . "</a></li>" . (isset($progress_record) && $target_progress_record->getID() == $progress_record->getID() ? "</strong>" : "");
                                                                }
                                                                if ($target_progress_record->getProgressValue() == "inprogress") {
                                                                    $sidebar_html .= (isset($progress_record) && $target_progress_record->getID() == $progress_record->getID() ? "<strong>" : "") . "<li><a href=\"" . ENTRADA_URL . "/assessments/assessment?" . replace_query(array("aprogress_id" => $target_progress_record->getID())) . "\">" . sprintf($translate->_("Started %s"), date("M d/y h:ia", $target_progress_record->getCreatedDate())) . "</a></li>" . (isset($progress_record) && $target_progress_record->getID() == $progress_record->getID() ? "</strong>" : "");
                                                                }
                                                            }
                                                            $sidebar_html .= "</ul>\n";
                                                        }

                                                        assessment_sidebar_item($sidebar_html, $id = "assessment-sidebar", $state = "open", $position = SIDEBAR_PREPEND);
                                                    }
                                                    break;
                                                default:
                                                    $target_user = User::fetchRowByID($PROCESSED["target_record_id"]);
                                                    $organisation = null;
                                                    if ($target_user) {
                                                        $target_name = html_encode($target_user->getFullname(false));
                                                        $organisation = Organisation::get($target_user->getOrganisationId());
                                                        $user_photo_details = Entrada_Utilities::fetchUserPhotoDetails($target_user->getID(), $target_user->getPrivacyLevel());

                                                        $sidebar_html = "<div class=\"user-image\">";
                                                        $sidebar_html .= "  <img src=\"" . ($user_photo_details && isset($user_photo_details["default_photo"]) && isset($user_photo_details[$user_photo_details["default_photo"] . "_url"]) ? $user_photo_details[$user_photo_details["default_photo"] . "_url"] : ENTRADA_URL . "/images/headshot-male.gif") . "\"/>";
                                                        $sidebar_html .= "</div>";
                                                        $sidebar_html .= "<div class=\"user-metadata\">";
                                                        $sidebar_html .= "  <p class=\"user-fullname\">" . html_encode($target_user->getFullname(false)) . "</p>";
                                                        $sidebar_html .= "  <p class=\"user-organisation\">" . html_encode(ucfirst($target_user->getGroup())) . ($organisation ? " <span>&bull;</span> " . html_encode($organisation->getTitle()) : "") . "</p>";
                                                        $sidebar_html .= "  <a class=\"user-email\" href=\"#\">" . html_encode($target_user->getEmail()) . "</a>";
                                                        $sidebar_html .= $delivery_info_view->render(array("assessment_record" => $assessment_record, "distribution" => $distribution, "distribution_schedule" => $distribution_schedule), false);
                                                        $target_progress_records = Models_Assessments_Progress::fetchAllByDassessmentIDAssessorTypeAssessorValueTargetRecordID($PROCESSED["dassessment_id"], "internal", ((isset($PROCESSED["assessor_id"]) && $PROCESSED["assessor_id"]) ? $PROCESSED["assessor_id"] : $ENTRADA_USER->getActiveID()), $target_user->getID());
                                                        if ($target_progress_records && @count($target_progress_records) > 1) {
                                                            $sidebar_html .= "<h3 class=\"no-space-below no-space-above\">Attempts</h3>\n";
                                                            $sidebar_html .= "<ul class=\"menu none\">\n";
                                                            $inprogress_shown = false;
                                                            $complete_records = 0;
                                                            foreach ($target_progress_records as $target_progress_record) {
                                                                if ($target_progress_record->getProgressValue() == "complete") {
                                                                    $complete_records++;
                                                                    $sidebar_html .= ($progress_record && $target_progress_record->getID() == $progress_record->getID() ? "<strong>" : "") . "<li><a href=\"" . ENTRADA_URL . "/assessments/assessment?" . replace_query(array("aprogress_id" => $target_progress_record->getID())) . "\">" . sprintf($translate->_("Completed %s"), date("M d/y h:ia", $target_progress_record->getUpdatedDate())) . "</a></li>" . ($progress_record && $target_progress_record->getID() == $progress_record->getID() ? "</strong>" : "");
                                                                }
                                                                if ($target_progress_record->getProgressValue() == "inprogress") {
                                                                    $sidebar_html .= ($progress_record && $target_progress_record->getID() == $progress_record->getID() ? "<strong>" : "") . "<li><a href=\"" . ENTRADA_URL . "/assessments/assessment?" . replace_query(array("aprogress_id" => $target_progress_record->getID())) . "\">" . sprintf($translate->_("Started %s"), date("M d/y h:ia", $target_progress_record->getCreatedDate())) . "</a></li>" . ($progress_record && $target_progress_record->getID() == $progress_record->getID() ? "</strong>" : "");
                                                                    $inprogress_shown = true;
                                                                }
                                                            }
                                                            if (!$inprogress_shown && $individual_attempts_max > $complete_records && $progress_record) {
                                                                $sidebar_html .= "<li><a href=\"" . ENTRADA_URL . "/assessments/assessment?" . replace_query(array("aprogress_id" => NULL)) . "\">" . $translate->_("Begin new attempt") . "</a></li>";
                                                            }
                                                            $sidebar_html .= "</ul>\n";
                                                        }
                                                        if (count($targets) > 1) {
                                                            $sidebar_html .= "  <div class=\"btn-group target-list clearfix\">";
                                                            $sidebar_html .= "      <a class=\"btn dropdown-toggle list-btn\" href=\"#\" data-toggle=\"dropdown\">" . $translate->_("Choose a Target") . "<span class=\"assessment-dropdown-arrow\"></span></a>";
                                                            $sidebar_html .= "      <ul id=\"dropdown-menu\" class=\"dropdown-menu targets-ul\">";
                                                            $sidebar_html .= "          <li class=\"target-search-listitem\">";
                                                            $sidebar_html .= "            <div id=\"target-search-bar\">";
                                                            $sidebar_html .= "                <input class=\"search-icon\" id=\"target-search-input\" type=\"text\" placeholder=\"" . $translate->_("Search Targets...") . "\" />";
                                                            $sidebar_html .= "            </div>";
                                                            $sidebar_html .= "          </li>";
                                                            $sidebar_html .= "          <li id=\"target-pending-listitem-header\" class=\"target-listitem-header\">" . $translate->_("Forms Not Started") . "<span id=\"targets-pending-count\" class=\"badge pending pull-right\">" . $targets_pending . "</span>" . "</li>";

                                                            if ($targets_pending) {
                                                                foreach ($targets as $target) {
                                                                    if (in_array("pending", $target["progress"])) {
                                                                        // TODO url view as and assessor id . ($PROCESSED["view"] && $PROCESSED["view"] == "view_as" && $PROCESSED["assessor_id"] ? "&view=view_as&assessor_id=" . html_encode($PROCESSED["assessor_id"]) : "")
                                                                        $url = ENTRADA_URL . "/assessments/assessment?adistribution_id=" . html_encode($distribution->getID()) . "&schedule_id=" . (isset($schedule) && $schedule ? html_encode($schedule->getID()) : "") . "&target_record_id=" . html_encode($target["target_record_id"]) . "&dassessment_id=" . html_encode($PROCESSED["dassessment_id"]) . ($PROCESSED["view"] && $PROCESSED["view"] == "view_as" && $PROCESSED["assessor_id"] ? "&view=view_as&assessor_id=" . html_encode($PROCESSED["assessor_id"]) : "");
                                                                        $sidebar_html .= "<li class=\"target-listitem target-listitem-pending\">";
                                                                        $sidebar_html .= "  <div class=\"clearfix\">";
                                                                        $sidebar_html .= "      <a class=\"target-name pull-left\" href=\"" . $url . "\">" . html_encode($target["name"]) . "</a>";
                                                                        if (!$distribution->getDeletedDate()) {
                                                                            $sidebar_html .= "      <a class=\"change-target pull-right\" href=\"" . $url . "\" data-toggle=\"tooltip\" data-target-record-id=\"" . html_encode($target["target_record_id"]) . "\" title=\"" . sprintf($translate->_("Change your current target and responses from %s to %s"), html_encode($target_user->getFullname(false)), html_encode($target["name"])) . "\"><i class=\"icon-retweet\"></i></a>";
                                                                        }
                                                                        $sidebar_html .= "  </div>";
                                                                        $sidebar_html .= "</li>";
                                                                    }
                                                                }
                                                            } else {
                                                                $sidebar_html .= "<li id=\"no-target-pending-listitem-header\" class=\"no-target-listitem\">" . $translate->_("No forms not yet started.") . "</li>";
                                                            }

                                                            $sidebar_html .= "          <li id=\"target-inprogress-listitem-header\" class=\"target-listitem-header\">" . $translate->_("Forms In Progress") . "<span id=\"targets-inprogress-count\" class=\"badge inprogress pull-right\">" . $targets_inprogress . "</span>" . "</li>";
                                                            if ($targets_inprogress) {
                                                                foreach ($targets as $target) {
                                                                    if (in_array("inprogress", $target["progress"])) {
                                                                        $url = ENTRADA_URL . "/assessments/assessment?adistribution_id=" . html_encode($distribution->getID()) . "&schedule_id=" . (isset($schedule) && $schedule ? html_encode($schedule->getID()) : "") . "&target_record_id=" . html_encode($target["target_record_id"]) . (array_key_exists("aprogress_id", $target) ? "&aprogress_id=" . html_encode($target["aprogress_id"]) : "") . "&dassessment_id=" . html_encode($PROCESSED["dassessment_id"]) . ($PROCESSED["view"] && $PROCESSED["view"] == "view_as" && $PROCESSED["assessor_id"] ? "&view=view_as&assessor_id=" . html_encode($PROCESSED["assessor_id"]) : "");
                                                                        $sidebar_html .= "<li class=\"target-listitem target-listitem-inprogress\">";
                                                                        $sidebar_html .= "  <div class=\"clearfix\">";
                                                                        $sidebar_html .= "      <a class=\"target-name inprogress pull-left\" href=\"" . $url . "\">" . html_encode($target["name"]) . "</a>";
                                                                        $sidebar_html .= "  </div>";
                                                                        $sidebar_html .= "</li>";
                                                                    }
                                                                }
                                                            } else {
                                                                $sidebar_html .= "<li id=\"no-target-inprogress-listitem-header\" class=\"no-target-listitem\">" . $translate->_("No forms in progress.") . "</li>";
                                                            }

                                                            $sidebar_html .= "          <li id=\"target-complete-listitem-header\" class=\"target-listitem-header\">" . $translate->_("Completed Forms") . "<span id=\"targets-complete-count\" class=\"badge complete pull-right\">" . $targets_complete . "</span>" . "</li>";
                                                            if ($targets_complete) {
                                                                foreach ($targets as $target) {
                                                                    if (in_array("complete", $target["progress"])) {
                                                                        $url = ENTRADA_URL . "/assessments/assessment?adistribution_id=" . html_encode($distribution->getID()) . "&schedule_id=" . (isset($schedule) && $schedule ? html_encode($schedule->getID()) : "") . "&target_record_id=" . html_encode($target["target_record_id"]) . (array_key_exists("aprogress_id", $target) ? "&aprogress_id=" . html_encode($target["aprogress_id"]) : "") . "&dassessment_id=" . html_encode($PROCESSED["dassessment_id"]) . ($PROCESSED["view"] && $PROCESSED["view"] == "view_as" && $PROCESSED["assessor_id"] ? "&view=view_as&assessor_id=" . html_encode($PROCESSED["assessor_id"]) : "");
                                                                        $sidebar_html .= "<li class=\"target-listitem target-listitem-complete\"><a href=\"" . $url . "\">" . html_encode($target["name"]) . "</a></li>";
                                                                    }
                                                                }
                                                            } else {
                                                                $sidebar_html .= "<li id=\"no-target-complete-listitem-header\" class=\"no-target-listitem\">" . $translate->_("No forms completed.") . "</li>";
                                                            }

                                                            $sidebar_html .= "      </ul>";
                                                            $sidebar_html .= "  </div>";
                                                        }
                                                        $sidebar_html .= "</div>";
                                                        assessment_sidebar_item($sidebar_html, $id = "assessment-sidebar", $state = "open", $position = SIDEBAR_PREPEND);
                                                    }
                                                    break;
                                            }
                                            break;
                                        case "proxy_id" :
                                        case "group_id" :
                                        case "cgroup_id" :
                                            $target_user = User::fetchRowByID($PROCESSED["target_record_id"]);
                                            $organisation = null;
                                            if ($target_user) {
                                                $target_name = html_encode($target_user->getFullname(false));
                                                $organisation = Organisation::get($target_user->getOrganisationId());
                                                $user_photo_details = Entrada_Utilities::fetchUserPhotoDetails($target_user->getID(), $target_user->getPrivacyLevel());

                                                $sidebar_html = "<div class=\"user-image\">";
                                                $sidebar_html .= "  <img src=\"" . ($user_photo_details && isset($user_photo_details["default_photo"]) && isset($user_photo_details[$user_photo_details["default_photo"] . "_url"]) ? $user_photo_details[$user_photo_details["default_photo"] . "_url"] : ENTRADA_URL . "/images/headshot-male.gif") . "\"/>";
                                                $sidebar_html .= "</div>";
                                                $sidebar_html .= "<div class=\"user-metadata\">";
                                                $sidebar_html .= "  <p class=\"user-fullname\">" . html_encode($target_user->getFullname(false)) . "</p>";
                                                $sidebar_html .= "  <p class=\"user-organisation\">" . html_encode(ucfirst($target_user->getGroup())) . ($organisation ? " <span>&bull;</span> " . html_encode($organisation->getTitle()) : "") . "</p>";
                                                $sidebar_html .= "  <a class=\"user-email\" href=\"#\">" . html_encode($target_user->getEmail()) . "</a>";
                                                $target_progress_records = Models_Assessments_Progress::fetchAllByDassessmentIDAssessorTypeAssessorValueTargetRecordID($PROCESSED["dassessment_id"], "internal", ((isset($PROCESSED["assessor_id"]) && $PROCESSED["assessor_id"]) ? $PROCESSED["assessor_id"] : $ENTRADA_USER->getActiveID()), $target_user->getID());
                                                $sidebar_html .= $delivery_info_view->render(array("assessment_record" => $assessment_record, "distribution" => $distribution, "distribution_schedule" => $distribution_schedule), false);
                                                if ($target_progress_records && @count($target_progress_records) >= 1) {
                                                    $sidebar_html .= "<h3 class=\"no-space-below no-space-above\">Attempts</h3>\n";
                                                    $sidebar_html .= "<ul class=\"menu none\">\n";
                                                    $inprogress_shown = false;
                                                    $complete_records = 0;
                                                    foreach ($target_progress_records as $target_progress_record) {
                                                        if ($target_progress_record->getProgressValue() == "complete") {
                                                            $complete_records++;
                                                            $sidebar_html .= ($progress_record && $target_progress_record->getID() == $progress_record->getID() ? "<strong>" : "") . "<li><a href=\"" . ENTRADA_URL . "/assessments/assessment?" . replace_query(array("aprogress_id" => $target_progress_record->getID())) . "\">" . sprintf($translate->_("Completed %s"), date("M d/y h:ia", $target_progress_record->getUpdatedDate())) . "</a></li>" . ($progress_record && $target_progress_record->getID() == $progress_record->getID() ? "</strong>" : "");
                                                        }
                                                        if ($target_progress_record->getProgressValue() == "inprogress") {
                                                            $sidebar_html .= ($progress_record && $target_progress_record->getID() == $progress_record->getID() ? "<strong>" : "") . "<li><a href=\"" . ENTRADA_URL . "/assessments/assessment?" . replace_query(array("aprogress_id" => $target_progress_record->getID())) . "\">" . sprintf($translate->_("Started %s"), date("M d/y h:ia", $target_progress_record->getCreatedDate())) . "</a></li>" . ($progress_record && $target_progress_record->getID() == $progress_record->getID() ? "</strong>" : "");
                                                            $inprogress_shown = true;
                                                        }
                                                    }
                                                    if (!$inprogress_shown && $individual_attempts_max > $complete_records && $progress_record) {
                                                        $sidebar_html .= "<li><a href=\"" . ENTRADA_URL . "/assessments/assessment?" . replace_query(array("aprogress_id" => NULL)) . "\">" . $translate->_("Begin new attempt") . "</a></li>";
                                                    }
                                                    $sidebar_html .= "</ul>\n";
                                                }

                                                if (count($targets) > 1) {
                                                    $sidebar_html .= "  <div class=\"btn-group target-list clearfix\">";
                                                    $sidebar_html .= "      <a class=\"btn dropdown-toggle list-btn\" href=\"#\" data-toggle=\"dropdown\">" . $translate->_("Choose a Target") . "<span class=\"assessment-dropdown-arrow\"></span></a>";
                                                    $sidebar_html .= "      <ul id=\"dropdown-menu\" class=\"dropdown-menu targets-ul\">";
                                                    $sidebar_html .= "          <li class=\"target-search-listitem\">";
                                                    $sidebar_html .= "            <div id=\"target-search-bar\">";
                                                    $sidebar_html .= "                <input class=\"search-icon\" id=\"target-search-input\" type=\"text\" placeholder=\"" . $translate->_("Search Targets...") . "\" />";
                                                    $sidebar_html .= "            </div>";
                                                    $sidebar_html .= "          </li>";
                                                    $sidebar_html .= "          <li id=\"target-pending-listitem-header\" class=\"target-listitem-header\">" . $translate->_("Forms Not Started") . "<span id=\"targets-pending-count\" class=\"badge pending pull-right\">" . $targets_pending . "</span>" . "</li>";

                                                    if ($targets_pending) {
                                                        foreach ($targets as $target) {
                                                            if (in_array("pending", $target["progress"])) {
                                                                $url = ENTRADA_URL . "/assessments/assessment?adistribution_id=" . html_encode($distribution->getID()) . "&schedule_id=" . (isset($schedule) && $schedule ? html_encode($schedule->getID()) : "") . "&target_record_id=" . html_encode($target["target_record_id"]) . "&dassessment_id=" . html_encode($PROCESSED["dassessment_id"]) . ($PROCESSED["view"] && $PROCESSED["view"] == "view_as" && $PROCESSED["assessor_id"] ? "&view=view_as&assessor_id=" . html_encode($PROCESSED["assessor_id"]) : "");
                                                                $sidebar_html .= "<li class=\"target-listitem target-listitem-pending\">";
                                                                $sidebar_html .= "  <div class=\"clearfix\">";
                                                                $sidebar_html .= "      <a class=\"target-name pull-left\" href=\"" . $url . "\">" . html_encode($target["name"]) . "</a>";
                                                                if (!$distribution->getDeletedDate()) {
                                                                    $sidebar_html .= "      <a class=\"change-target pull-right\" href=\"" . $url . "\" data-toggle=\"tooltip\" data-target-record-id=\"" . html_encode($target["target_record_id"]) . "\" title=\"" . sprintf($translate->_("Change your current target and responses from %s to %s"), html_encode($target_user->getFullname(false)), html_encode($target["name"])) . "\"><i class=\"icon-retweet\"></i></a>";
                                                                }
                                                                $sidebar_html .= "  </div>";
                                                                $sidebar_html .= "</li>";
                                                            }
                                                        }
                                                    } else {
                                                        $sidebar_html .= "<li id=\"no-target-pending-listitem-header\" class=\"no-target-listitem\">" . $translate->_("No forms not yet started.") . "</li>";
                                                    }

                                                    $sidebar_html .= "          <li id=\"target-inprogress-listitem-header\" class=\"target-listitem-header\">" . $translate->_("Forms In Progress") . "<span id=\"targets-inprogress-count\" class=\"badge inprogress pull-right\">" . $targets_inprogress . "</span>" . "</li>";
                                                    if ($targets_inprogress) {
                                                        foreach ($targets as $target) {
                                                            if (in_array("inprogress", $target["progress"])) {
                                                                $url = ENTRADA_URL . "/assessments/assessment?adistribution_id=" . html_encode($distribution->getID()) . "&schedule_id=" . (isset($schedule) && $schedule ? html_encode($schedule->getID()) : "") . "&target_record_id=" . html_encode($target["target_record_id"]) . (array_key_exists("aprogress_id", $target) ? "&aprogress_id=" . html_encode($target["aprogress_id"]) : "") . "&dassessment_id=" . html_encode($PROCESSED["dassessment_id"]) . ($PROCESSED["view"] && $PROCESSED["view"] == "view_as" && $PROCESSED["assessor_id"] ? "&view=view_as&assessor_id=" . html_encode($PROCESSED["assessor_id"]) : "");
                                                                $sidebar_html .= "<li class=\"target-listitem target-listitem-inprogress\">";
                                                                $sidebar_html .= "  <div class=\"clearfix\">";
                                                                $sidebar_html .= "      <a class=\"target-name inprogress pull-left\" href=\"" . $url . "\">" . html_encode($target["name"]) . "</a>";
                                                                $sidebar_html .= "  </div>";
                                                                $sidebar_html .= "</li>";
                                                            }
                                                        }
                                                    } else {
                                                        $sidebar_html .= "<li id=\"no-target-inprogress-listitem-header\" class=\"no-target-listitem\">" . $translate->_("No forms in progress.") . "</li>";
                                                    }

                                                    $sidebar_html .= "          <li id=\"target-complete-listitem-header\" class=\"target-listitem-header\">" . $translate->_("Completed Forms") . "<span id=\"targets-complete-count\" class=\"badge complete pull-right\">" . $targets_complete . "</span>" . "</li>";
                                                    if ($targets_complete) {
                                                        foreach ($targets as $target) {
                                                            if (in_array("complete", $target["progress"])) {
                                                                $url = ENTRADA_URL . "/assessments/assessment?adistribution_id=" . html_encode($distribution->getID()) . "&schedule_id=" . (isset($schedule) && $schedule ? html_encode($schedule->getID()) : "") . "&target_record_id=" . html_encode($target["target_record_id"]) . (array_key_exists("aprogress_id", $target) ? "&aprogress_id=" . html_encode($target["aprogress_id"]) : "") . "&dassessment_id=" . html_encode($PROCESSED["dassessment_id"]) . ($PROCESSED["view"] && $PROCESSED["view"] == "view_as" && $PROCESSED["assessor_id"] ? "&view=view_as&assessor_id=" . html_encode($PROCESSED["assessor_id"]) : "");
                                                                $sidebar_html .= "<li class=\"target-listitem target-listitem-complete\"><a href=\"" . $url . "\">" . html_encode($target["name"]) . "</a></li>";
                                                            }
                                                        }
                                                    } else {
                                                        $sidebar_html .= "<li id=\"no-target-complete-listitem-header\" class=\"no-target-listitem\">" . $translate->_("No forms completed.") . "</li>";
                                                    }

                                                    $sidebar_html .= "      </ul>";
                                                    $sidebar_html .= "  </div>";
                                                }
                                                $sidebar_html .= "</div>";
                                                assessment_sidebar_item($sidebar_html, $id = "assessment-sidebar", $state = "open", $position = SIDEBAR_PREPEND);
                                            }
                                            break;
                                        case "course_id" :
                                            switch ($distribution_target->getTargetScope()) {
                                                case "self" :
                                                    $target = Models_Course::fetchRowByID($PROCESSED["target_record_id"]);
                                                    if ($target) {
                                                        $target_name = html_encode($target->getCourseName());
                                                        $sidebar_html = "<h4 class=\"course-target-heading\">" . $translate->_("Currently Assessing:") . "</h4>";
                                                        $sidebar_html .= "<div class=\"user-metadata\">";
                                                        $sidebar_html .= "  <p class=\"course-name\">" . html_encode($target->getCourseName()) . "</p>";
                                                        $organisation = Organisation::get($target->getOrganisationID());
                                                        if ($organisation) {
                                                            $sidebar_html .= "  <p class=\"course-organisation\">" . html_encode($organisation->getTitle()) . "</p>";
                                                        }
                                                        $sidebar_html .= "</div>";
                                                        $sidebar_html .= $delivery_info_view->render(array("assessment_record" => $assessment_record, "distribution" => $distribution, "distribution_schedule" => $distribution_schedule), false);
                                                        assessment_sidebar_item($sidebar_html, $id = "assessment-sidebar", $state = "open", $position = SIDEBAR_PREPEND);
                                                    }
                                                    break;
                                                default :
                                                    break;
                                            }
                                            break;
                                        case "eventtype_id":
                                            $distribution_learning_event = new Entrada_Utilities_Assessments_DistributionLearningEvent(array("adistribution_id" => $distribution->getID()));
                                            $learning_event_data = $distribution_learning_event->getLearningEventTargetData(
                                                $distribution_target,
                                                $PROCESSED["target_record_id"],
                                                $assessment_record->getAssociatedRecordType(),
                                                $assessment_record->getAssociatedRecordID()
                                            );
                                            $target_name = $learning_event_data["target_type"] == "event_id" ? $learning_event_data["event_name"] : $learning_event_data["user_name"];
                                            $target_count = count($targets);
                                            $target_progress_records = Models_Assessments_Progress::fetchAllByDassessmentIDAssessorTypeAssessorValueTargetRecordID($PROCESSED["dassessment_id"], "internal", ((isset($PROCESSED["assessor_id"]) && $PROCESSED["assessor_id"]) ? $PROCESSED["assessor_id"] : $ENTRADA_USER->getActiveID()), $PROCESSED["target_record_id"]);
                                            $include_target_heading = $target_count > 1 ? false : true;
                                            foreach ($target_progress_records as $tpr) {
                                                if ($tpr->getProgressValue() == "complete") {
                                                    $include_target_heading = true; // it's a completed record, so override the heading, if necessary
                                                }
                                            }

                                            $sidebar_target_view = new Views_Assessments_Sidebar_EventTarget();
                                            $target_sidebar_html = $sidebar_target_view->render(
                                                array(
                                                    "learning_event_data" => $learning_event_data,
                                                    "delivery_date" => $assessment_record->getDeliveryDate(),
                                                    "include_target_heading" => $include_target_heading,
                                                    "include_assessing_heading" => !$include_target_heading
                                                ),
                                                false
                                            );
                                            $sidebar_targets_switcher = new Views_Assessments_Sidebar_TargetsSwitcher();
                                            $target_switcher_html = $sidebar_targets_switcher->render(
                                                array(
                                                    "distribution" => $distribution,
                                                    "target_name" => $target_name,
                                                    "assessment_record" => $assessment_record,
                                                    "targets" => $targets,
                                                    "targets_pending" => $targets_pending,
                                                    "targets_inprogress" => $targets_inprogress,
                                                    "targets_complete" => $targets_complete
                                                ),
                                                false
                                            );
                                            assessment_sidebar_item($target_sidebar_html . $target_switcher_html, $id = "assessment-sidebar", $state = "open", $position = SIDEBAR_PREPEND);
                                            break;
                                    }
                                } else {
                                    switch ($distribution_target->getTargetType()) {
                                        case "eventtype_id":
                                            $distribution_learning_event = new Entrada_Utilities_Assessments_DistributionLearningEvent(array("adistribution_id" => $distribution->getID()));
                                            $sidebar_eventtarget_view = new Views_Assessments_Sidebar_EventTarget();
                                            $learning_event_data = $distribution_learning_event->getLearningEventTargetData($distribution_target, $PROCESSED["target_record_id"], $assessment_record->getAssociatedRecordType(), $assessment_record->getAssociatedRecordID());
                                            $target_name = $learning_event_data["target_type"] == "event_id" ? $learning_event_data["event_name"] : $learning_event_data["user_name"];
                                            $target_sidebar_html = $sidebar_eventtarget_view->render(
                                                array(
                                                    "learning_event_data" => $learning_event_data,
                                                    "delivery_date" => $assessment_record->getDeliveryDate()
                                                ),
                                                false
                                            );
                                            assessment_sidebar_item($target_sidebar_html, $id = "assessment-sidebar", $state = "open", $position = SIDEBAR_PREPEND);
                                            break;
                                        case "proxy_id" :
                                            $target_user = User::fetchRowByID($PROCESSED["target_record_id"]);
                                            $organisation = null;
                                            if ($target_user) {
                                                $target_name = html_encode($target_user->getFullname(false));
                                                $organisation = Organisation::get($target_user->getOrganisationId());
                                                $user_photo_details = Entrada_Utilities::fetchUserPhotoDetails($target_user->getID(), $target_user->getPrivacyLevel());

                                                $sidebar_html = "<h3>" . $translate->_("Target") . ": </h3>";
                                                $sidebar_html .= "<div class=\"user-image\">";
                                                $sidebar_html .= "  <img src=\"" . ($user_photo_details && isset($user_photo_details["default_photo"]) && isset($user_photo_details[$user_photo_details["default_photo"] . "_url"]) ? $user_photo_details[$user_photo_details["default_photo"] . "_url"] : ENTRADA_URL . "/images/headshot-male.gif") . "\"/>";
                                                $sidebar_html .= "</div>";
                                                $sidebar_html .= "<div class=\"user-metadata\">";
                                                $sidebar_html .= "  <p class=\"user-fullname\">" . html_encode($target_user->getFullname(false)) . "</p>";
                                                $sidebar_html .= "  <p class=\"user-organisation\">" . html_encode(ucfirst($target_user->getGroup())) . ($organisation ? " <span>&bull;</span> " . html_encode($organisation->getTitle()) : "") . "</p>";
                                                $sidebar_html .= "  <a class=\"user-email\" href=\"#\">" . html_encode($target_user->getEmail()) . "</a>";
                                                $sidebar_html .= "</div>";
                                                $sidebar_html .= $delivery_info_view->render(array("assessment_record" => $assessment_record, "distribution" => $distribution, "distribution_schedule" => $distribution_schedule), false);
                                                $target_progress_records = Models_Assessments_Progress::fetchAllByDassessmentIDAssessorTypeAssessorValueTargetRecordID($PROCESSED["dassessment_id"], "internal", ((isset($PROCESSED["assessor_id"]) && $PROCESSED["assessor_id"]) ? $PROCESSED["assessor_id"] : $ENTRADA_USER->getActiveID()), $target_user->getID());
                                                if ($target_progress_records && @count($target_progress_records) >= 1) {
                                                    $sidebar_html .= "<h3 class=\"no-space-below no-space-above\">Attempts</h3>\n";
                                                    $sidebar_html .= "<ul class=\"menu none\">\n";
                                                    $inprogress_shown = false;
                                                    $complete_records = 0;
                                                    foreach ($target_progress_records as $target_progress_record) {
                                                        if ($target_progress_record->getProgressValue() == "complete") {
                                                            $complete_records++;
                                                            $sidebar_html .= ($progress_record && $target_progress_record->getID() == $progress_record->getID() ? "<strong>" : "") . "<li><a href=\"" . ENTRADA_URL . "/assessments/assessment?" . replace_query(array("aprogress_id" => $target_progress_record->getID())) . "\">" . sprintf($translate->_("Completed %s"), date("M d/y h:ia", $target_progress_record->getUpdatedDate())) . "</a></li>" . ($progress_record && $target_progress_record->getID() == $progress_record->getID() ? "</strong>" : "");
                                                        }
                                                        if ($target_progress_record->getProgressValue() == "inprogress") {
                                                            $sidebar_html .= ($progress_record && $target_progress_record->getID() == $progress_record->getID() ? "<strong>" : "") . "<li><a href=\"" . ENTRADA_URL . "/assessments/assessment?" . replace_query(array("aprogress_id" => $target_progress_record->getID())) . "\">" . sprintf($translate->_("Started %s"), date("M d/y h:ia", $target_progress_record->getCreatedDate())) . "</a></li>" . ($progress_record && $target_progress_record->getID() == $progress_record->getID() ? "</strong>" : "");
                                                            $inprogress_shown = true;
                                                        }
                                                    }
                                                    if (!$inprogress_shown && (isset($individual_attempts_max) && $individual_attempts_max > $complete_records && $progress_record)) {
                                                        $sidebar_html .= "<li><a href=\"" . ENTRADA_URL . "/assessments/assessment?" . replace_query(array("aprogress_id" => NULL)) . "\">" . $translate->_("Begin new attempt") . "</a></li>";
                                                    }
                                                    $sidebar_html .= "</ul>\n";
                                                }
                                                assessment_sidebar_item($sidebar_html, $id = "assessment-target-sidebar", $state = "open", $position = SIDEBAR_PREPEND);
                                            }
                                            break;
                                        case "group_id" :
                                        case "cgroup_id" :
                                        case "schedule_id" :
                                            switch ($distribution_target->getTargetScope()) {
                                                case "self" :
                                                    $target = Models_Schedule::fetchRowByID($PROCESSED["target_record_id"]);
                                                    if ($target) {
                                                        $target_name = html_encode($target->getTitle());
                                                        $sidebar_html = "<h4 class=\"course-target-heading\">" . $translate->_("Currently Assessing:") . "</h4>";
                                                        $sidebar_html .= "<div class=\"user-metadata\">";
                                                        $sidebar_html .= "  <p class=\"course-name\">" . html_encode($target->getTitle()) . "</p>";
                                                        $organisation = Organisation::get($target->getOrganisationID());
                                                        if ($organisation) {
                                                            $sidebar_html .= "  <p class=\"course-organisation\">" . html_encode($organisation->getTitle()) . "</p>";
                                                        }
                                                        $sidebar_html .= "</div>";
                                                        $sidebar_html .= $delivery_info_view->render(array("assessment_record" => $assessment_record, "distribution" => $distribution, "distribution_schedule" => $distribution_schedule), false);
                                                        $target_progress_records = Models_Assessments_Progress::fetchAllByDassessmentIDAssessorTypeAssessorValueTargetRecordID($PROCESSED["dassessment_id"], "internal", ((isset($PROCESSED["assessor_id"]) && $PROCESSED["assessor_id"]) ? $PROCESSED["assessor_id"] : $ENTRADA_USER->getActiveID()), $PROCESSED["target_record_id"]);
                                                        if ($target_progress_records && @count($target_progress_records) > 1) {
                                                            $sidebar_html .= "<h3>Attempts</h3>";
                                                            $sidebar_html .= "<ul class=\"menu none\">\n";
                                                            foreach ($target_progress_records as $target_progress_record) {
                                                                if ($target_progress_record->getProgressValue() == "complete") {
                                                                    $sidebar_html .= (isset($progress_record) && $target_progress_record->getID() == $progress_record->getID() ? "<strong>" : "") . "<li><a href=\"" . ENTRADA_URL . "/assessments/assessment?" . replace_query(array("aprogress_id" => $target_progress_record->getID())) . "\">" . sprintf($translate->_("Completed %s"), date("M d/y h:ia", $target_progress_record->getUpdatedDate())) . "</a></li>" . (isset($progress_record) && $target_progress_record->getID() == $progress_record->getID() ? "</strong>" : "");
                                                                }
                                                                if ($target_progress_record->getProgressValue() == "inprogress") {
                                                                    $sidebar_html .= (isset($progress_record) && $target_progress_record->getID() == $progress_record->getID() ? "<strong>" : "") . "<li><a href=\"" . ENTRADA_URL . "/assessments/assessment?" . replace_query(array("aprogress_id" => $target_progress_record->getID())) . "\">" . sprintf($translate->_("Started %s"), date("M d/y h:ia", $target_progress_record->getCreatedDate())) . "</a></li>" . (isset($progress_record) && $target_progress_record->getID() == $progress_record->getID() ? "</strong>" : "");
                                                                }
                                                            }
                                                            $sidebar_html .= "</ul>\n";
                                                        }
                                                        assessment_sidebar_item($sidebar_html, $id = "assessment-sidebar", $state = "open", $position = SIDEBAR_PREPEND);
                                                    }
                                                    break;
                                                case "internal_learners":
                                                case "external_learners":
                                                case "all_learners":
                                                    $target_user = User::fetchRowByID($PROCESSED["target_record_id"]);
                                                    $organisation = null;
                                                    if ($target_user) {
                                                        $target_name = html_encode($target_user->getFullname(false));
                                                        $organisation = Organisation::get($target_user->getOrganisationId());
                                                        $user_photo_details = Entrada_Utilities::fetchUserPhotoDetails($target_user->getID(), $target_user->getPrivacyLevel());

                                                        $sidebar_html = "<h3>" . $translate->_("Target") . ": </h3>";
                                                        $sidebar_html .= "<div class=\"user-image\">";
                                                        $sidebar_html .= "  <img src=\"" . ($user_photo_details && isset($user_photo_details["default_photo"]) && isset($user_photo_details[$user_photo_details["default_photo"] . "_url"]) ? $user_photo_details[$user_photo_details["default_photo"] . "_url"] : ENTRADA_URL . "/images/headshot-male.gif") . "\"/>";
                                                        $sidebar_html .= "</div>";
                                                        $sidebar_html .= "<div class=\"user-metadata\">";
                                                        $sidebar_html .= "  <p class=\"user-fullname\">" . html_encode($target_user->getFullname(false)) . "</p>";
                                                        $sidebar_html .= "  <p class=\"user-organisation\">" . html_encode(ucfirst($target_user->getGroup())) . ($organisation ? " <span>&bull;</span> " . html_encode($organisation->getTitle()) : "") . "</p>";
                                                        $sidebar_html .= "  <a class=\"user-email\" href=\"#\">" . html_encode($target_user->getEmail()) . "</a>";
                                                        $sidebar_html .= "</div>";
                                                        $sidebar_html .= $delivery_info_view->render(array("assessment_record" => $assessment_record, "distribution" => $distribution, "distribution_schedule" => $distribution_schedule), false);
                                                        $target_progress_records = Models_Assessments_Progress::fetchAllByDassessmentIDAssessorTypeAssessorValueTargetRecordID($PROCESSED["dassessment_id"], "internal", ((isset($PROCESSED["assessor_id"]) && $PROCESSED["assessor_id"]) ? $PROCESSED["assessor_id"] : $ENTRADA_USER->getActiveID()), $target_user->getID());
                                                        if ($target_progress_records && @count($target_progress_records) >= 1) {
                                                            $sidebar_html .= "<h3 class=\"no-space-below no-space-above\">Attempts</h3>\n";
                                                            $sidebar_html .= "<ul class=\"menu none\">\n";
                                                            $inprogress_shown = false;
                                                            $complete_records = 0;
                                                            foreach ($target_progress_records as $target_progress_record) {
                                                                if ($target_progress_record->getProgressValue() == "complete") {
                                                                    $complete_records++;
                                                                    $sidebar_html .= ($progress_record && $target_progress_record->getID() == $progress_record->getID() ? "<strong>" : "") . "<li><a href=\"" . ENTRADA_URL . "/assessments/assessment?" . replace_query(array("aprogress_id" => $target_progress_record->getID())) . "\">" . sprintf($translate->_("Completed %s"), date("M d/y h:ia", $target_progress_record->getUpdatedDate())) . "</a></li>" . ($progress_record && $target_progress_record->getID() == $progress_record->getID() ? "</strong>" : "");
                                                                }
                                                                if ($target_progress_record->getProgressValue() == "inprogress") {
                                                                    $sidebar_html .= ($progress_record && $target_progress_record->getID() == $progress_record->getID() ? "<strong>" : "") . "<li><a href=\"" . ENTRADA_URL . "/assessments/assessment?" . replace_query(array("aprogress_id" => $target_progress_record->getID())) . "\">" . sprintf($translate->_("Started %s"), date("M d/y h:ia", $target_progress_record->getCreatedDate())) . "</a></li>" . ($progress_record && $target_progress_record->getID() == $progress_record->getID() ? "</strong>" : "");
                                                                    $inprogress_shown = true;
                                                                }
                                                            }
                                                            if (!$inprogress_shown && (isset($individual_attempts_max) && $individual_attempts_max > $complete_records && $progress_record)) {
                                                                $sidebar_html .= "<li><a href=\"" . ENTRADA_URL . "/assessments/assessment?" . replace_query(array("aprogress_id" => NULL)) . "\">" . $translate->_("Begin new attempt") . "</a></li>";
                                                            }
                                                            $sidebar_html .= "</ul>\n";
                                                        }
                                                        assessment_sidebar_item($sidebar_html, $id = "assessment-target-sidebar", $state = "open", $position = SIDEBAR_PREPEND);
                                                    }
                                                    break;
                                                default:
                                                    break;
                                            }
                                            break;
                                        case "course_id" :
                                            switch ($distribution_target->getTargetScope()) {
                                                case "self" :
                                                    $target = Models_Course::fetchRowByID($PROCESSED["target_record_id"]);
                                                    if ($target) {
                                                        $target_name = html_encode($target->getCourseName());
                                                        $sidebar_html = "<h4 class=\"course-target-heading\">" . $translate->_("Currently Assessing:") . "</h4>";
                                                        $sidebar_html .= "<div class=\"user-metadata\">";
                                                        $sidebar_html .= "  <p class=\"course-name\">" . html_encode($target->getCourseName()) . "</p>";
                                                        $organisation = Organisation::get($target->getOrganisationID());
                                                        if ($organisation) {
                                                            $sidebar_html .= "  <p class=\"course-organisation\">" . html_encode($organisation->getTitle()) . "</p>";
                                                        }
                                                        $sidebar_html .= "</div>";
                                                        $sidebar_html .= $delivery_info_view->render(array("assessment_record" => $assessment_record, "distribution" => $distribution, "distribution_schedule" => $distribution_schedule), false);
                                                        assessment_sidebar_item($sidebar_html, $id = "assessment-target-sidebar", $state = "open", $position = SIDEBAR_PREPEND);
                                                    }
                                                    break;
                                                default :
                                                    break;
                                            }
                                            break;
                                    }

                                    if ($progress_record) {
                                        $assessor_user = null;
                                        $organisation = null;
                                        $user_photo_details = false;
                                        $group = "";
                                        if ($progress_record->getAssessorType() == "internal") {
                                            $assessor_user = User::fetchRowByID($progress_record->getAssessorValue());
                                            if ($assessor_user) {
                                                $organisation = Organisation::get($assessor_user->getOrganisationId());
                                                $user_photo_details = Entrada_Utilities::fetchUserPhotoDetails($assessor_user->getID(), $assessor_user->getPrivacyLevel());
                                                $assessor_fullname = $assessor_user->getFullname(false);
                                                $group = ucfirst($assessor_user->getGroup());
                                            }
                                        } else {
                                            $assessor_user = Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($progress_record->getAssessorValue());
                                            if ($assessor_user) {
                                                $assessor_fullname = $assessor_user->getFirstname() . " " . $assessor_user->getLastname();
                                            }
                                            $group = $translate->_("External Assessor");
                                        }

                                        if ($assessor_user) {
                                            $sidebar_html = "<h3>" . $translate->_("Assessor") . ": </h3>";
                                            $sidebar_html .= "<div class=\"user-image\">";
                                            $sidebar_html .= "  <img src=\"" . ($user_photo_details && isset($user_photo_details["default_photo"]) && isset($user_photo_details[$user_photo_details["default_photo"] . "_url"]) ? $user_photo_details[$user_photo_details["default_photo"] . "_url"] : ENTRADA_URL . "/images/headshot-male.gif") . "\"/>";
                                            $sidebar_html .= "</div>";
                                            $sidebar_html .= "<div class=\"user-metadata\">";
                                            $sidebar_html .= "  <p class=\"user-fullname\">" . html_encode($assessor_fullname) . "</p>";
                                            $sidebar_html .= "  <p class=\"user-organisation\">" . html_encode($group) . ($organisation ? " <span>&bull;</span> " . html_encode($organisation->getTitle()) : "") . "</p>";
                                            $sidebar_html .= "  <a class=\"user-email\" href=\"#\">" . html_encode($assessor_user->getEmail()) . "</a>";
                                            $sidebar_html .= "</div>";
                                            $target_progress_records = Models_Assessments_Progress::fetchAllByDassessmentIDAssessorTypeAssessorValueTargetRecordID($PROCESSED["dassessment_id"], "internal", ((isset($PROCESSED["assessor_id"]) && $PROCESSED["assessor_id"]) ? $PROCESSED["assessor_id"] : $ENTRADA_USER->getActiveID()), $PROCESSED["target_record_id"]);
                                            if ($target_progress_records && @count($target_progress_records) >= 1) {
                                                $sidebar_html .= "<h3 class=\"no-space-below no-space-above\">Attempts</h3>\n";
                                                $sidebar_html .= "<ul class=\"menu none\">\n";
                                                $inprogress_shown = false;
                                                $complete_records = 0;
                                                foreach ($target_progress_records as $target_progress_record) {
                                                    if ($target_progress_record->getProgressValue() == "complete") {
                                                        $complete_records++;
                                                        $sidebar_html .= ($progress_record && $target_progress_record->getID() == $progress_record->getID() ? "<strong>" : "") . "<li><a href=\"" . ENTRADA_URL . "/assessments/assessment?" . replace_query(array("aprogress_id" => $target_progress_record->getID())) . "\">" . sprintf($translate->_("Completed %s"), date("M d/y h:ia", $target_progress_record->getUpdatedDate())) . "</a></li>" . ($progress_record && $target_progress_record->getID() == $progress_record->getID() ? "</strong>" : "");
                                                    }
                                                    if ($target_progress_record->getProgressValue() == "inprogress") {
                                                        $sidebar_html .= ($progress_record && $target_progress_record->getID() == $progress_record->getID() ? "<strong>" : "") . "<li><a href=\"" . ENTRADA_URL . "/assessments/assessment?" . replace_query(array("aprogress_id" => $target_progress_record->getID())) . "\">" . sprintf($translate->_("Started %s"), date("M d/y h:ia", $target_progress_record->getCreatedDate())) . "</a></li>" . ($progress_record && $target_progress_record->getID() == $progress_record->getID() ? "</strong>" : "");
                                                        $inprogress_shown = true;
                                                    }
                                                }
                                                if (!$inprogress_shown && isset($individual_attempts_max) && $individual_attempts_max > $complete_records && $progress_record) {
                                                    $sidebar_html .= "<li><a href=\"" . ENTRADA_URL . "/assessments/assessment?" . replace_query(array("aprogress_id" => NULL)) . "\">" . $translate->_("Begin new attempt") . "</a></li>";
                                                }
                                                $sidebar_html .= "</ul>\n";
                                            }
                                            assessment_sidebar_item($sidebar_html, $id = "assessment-assessor-sidebar", $state = "open", $position = SIDEBAR_PREPEND);
                                        }
                                    }
                                }

                                if ($distribution_schedule) {
                                    $schedule = Models_Schedule::fetchRowByIDScheduleType($distribution_schedule->getScheduleID(), "rotation_block");
                                }

                                if (isset($schedule) && $schedule) {
                                    $rotation_name = $translate->_("N/A");
                                    if ($schedule->getScheduleType() == "rotation_block") {
                                        $schedule_parent = Models_Schedule::fetchRowByID($schedule->getScheduleParentID());
                                        $rotation_name = $schedule_parent->getTitle();
                                    } else {
                                        $rotation_name = $schedule->getTitle();
                                    }
                                }

                                $hide_from_approver = false;
                                if ($ENTRADA_USER->getActiveId() != $assessment_record->getAssessorValue()) {
                                    $approver_model = new Models_Assessments_Distribution_Approver();
                                    $approver = $approver_model->fetchRowByProxyIDDistributionID($ENTRADA_USER->getActiveId(), $PROCESSED["adistribution_id"]);
                                    $hide_from_approver = ($approver) ? true : false;
                                }

                                if (isset($_GET["generate-pdf"])) {
                                    $assessment_user = new Entrada_Utilities_AssessmentUser();
                                    $assessment_user->cacheUserCardPhotos(array(array("id" => $PROCESSED["target_record_id"])));

                                    $organisation = Models_Organisation::fetchRowByID($distribution_data["organisation_id"]);
                                    if ($organisation) {
                                        $cache = new Entrada_Utilities_Cache();
                                        $cache->cacheImage(ENTRADA_ABSOLUTE . "/templates/{$organisation->getTemplate()}/images/organisation-logo.png", "organisation_logo_{$distribution_data["organisation_id"]}", "image/png");
                                    }

                                    $completed_date = "";
                                    $assessor_name = "";
                                    $progress_record = Models_Assessments_Progress::fetchRowByID($distribution_data["aprogress_id"]);
                                    if ($progress_record) {
                                        if ($progress_record->getProgressValue() == "complete") {
                                            $completed_date = $progress_record->getUpdatedDate();
                                        }

                                        if ($progress_assessor = $utilities_base->getUserByType($progress_record->getAssessorValue(), $progress_record->getAssessorType())) {
                                            $assessor_name = "{$progress_assessor->getFirstname()} {$progress_assessor->getLastname()}";
                                        }
                                    }

                                    $event_name = $event_timeframe_start = $event_timeframe_end = null;
                                    if (!empty($learning_event_data)) {
                                        $event_timeframe_start = $learning_event_data["timeframe_start"];
                                        $event_timeframe_end = $learning_event_data["timeframe_end"];
                                        $event_name = $learning_event_data["event_name"];
                                    }

                                    $header = $delivery_info_view->render(
                                        array(
                                            "assessment_record" => $assessment_record,
                                            "distribution" => $distribution,
                                            "distribution_schedule" => $distribution_schedule,
                                            "is_pdf" => true,
                                            "target_name" => $target_name,
                                            "event_name" => $event_name,
                                            "assessor_name" => $assessor_name,
                                            "completed_date" => $completed_date,
                                            "timeframe_start" => $event_timeframe_start,
                                            "timeframe_end" => $event_timeframe_end
                                        ),
                                        false
                                    );

                                    // Instantiate utility object, with optional parameters.
                                    $forms_api = new Entrada_Assessments_Forms(array(
                                            "form_id" => $form->getID(),
                                            "adistribution_id" => @$PROCESSED["adistribution_id"] ? $PROCESSED["adistribution_id"] : null,
                                            "aprogress_id" => @$distribution_data["aprogress_id"] ? @$distribution_data["aprogress_id"] : null,
                                            "dassessment_id" => @$PROCESSED["dassessment_id"] ? $PROCESSED["dassessment_id"] : null,
                                            "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                                            "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()
                                        )
                                    );

                                    // Fetch form data using those params
                                    $form_data = $forms_api->fetchFormData();

                                    // Render the form in PDF mode
                                    $form_view = new Views_Assessments_Forms_Form(array("mode" => "pdf"));
                                    $assessment_html = $form_view->render(array(
                                            "form_id" => $form->getID(),
                                            "disabled" => false,
                                            "form_elements" => $form_data["elements"],
                                            "progress" => $form_data["progress"],
                                            "rubrics" => $form_data["rubrics"],
                                            "aprogress_id" => @$distribution_data["aprogress_id"] ? @$distribution_data["aprogress_id"] : null,
                                            "public" => true,
                                            "objectives" => $PROCESSED["objectives"]
                                        ),
                                        false
                                    );

                                    if ($assessment_html && $distribution->getFeedbackRequired()) {

                                        $feedback_record = Models_Assessments_AssessorTargetFeedback::fetchRowByAssessorTarget($PROCESSED["dassessment_id"], "internal", $assessment_record->getAssessorValue(), "internal", $PROCESSED["target_record_id"]);

                                        // Append the feedback, if it's specified
                                        $feedback_view = new Views_Assessments_Forms_Sections_Feedback();
                                        $assessment_html .= $feedback_view->render(
                                            array(
                                                "target_record_id" => $distribution_data["target_record_id"],
                                                "distribution" => $distribution,
                                                "hide_from_approver" => $hide_from_approver,
                                                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                                                "progress_record" => $progress_record,
                                                "assessment_record" => $assessment_record,
                                                "feedback_record" => $feedback_record,
                                                "is_pdf" => true
                                            )
                                        );
                                    }

                                    if ($assessment_html) {
                                        $target_user = User::fetchRowByID($PROCESSED["target_record_id"]);
                                        $assessment_pdf = new Entrada_Utilities_Assessments_HTMLForPDFGenerator();
                                        if ($assessment_pdf->configure()) {
                                            $html = $assessment_pdf->generateAssessmentHTML($assessment_html, $distribution_data["organisation_id"], $form->getTitle(), $header, $PROCESSED["target_record_id"]);
                                            $filename = $assessment_pdf->buildFilename("{$form->getTitle()} $target_name $assessor_name", "pdf");
                                            if (!$assessment_pdf->send($filename, $html)) {
                                                // Unable to send, so redirect away from this page and show an error.
                                                ob_clear_open_buffers();
                                                $error_url = $assessment_pdf->buildURI("/assessments/assessment/", $_SERVER["REQUEST_URI"]);
                                                $error_url = str_replace("generate-pdf=", "pdf-error=", $error_url);
                                                Header("Location: $error_url");
                                                die();
                                            }
                                        } else {
                                            echo display_error(array($translate->_("Unable to generate PDF. Library path is not set.")));
                                            application_log("error", "Library path is not set for wkhtmltopdf. Please ensure the webserver can access this utility.");
                                        }
                                    }
                                }

                                /* Not PDF generation mode, so render page from this point on. */

                                $submit_on_behalf = false;
                                $approver_approvals = new Models_Assessments_Distribution_Approvals();
                                $has_been_released = $approver_approvals->fetchRowByProgressIDDistributionID($PROCESSED["aprogress_id"], $PROCESSED["adistribution_id"]);
                                $progress_record = Models_Assessments_Progress::fetchRowByID($PROCESSED["aprogress_id"]);

                                // Instantiate utility object, with optional parameters.
                                $form_api = new Entrada_Assessments_Forms(array(
                                        "form_id" => $form->getID(),
                                        "adistribution_id" => @$PROCESSED["adistribution_id"] ? $PROCESSED["adistribution_id"] : null,
                                        "aprogress_id" => @$distribution_data["aprogress_id"] ? @$distribution_data["aprogress_id"] : null,
                                        "dassessment_id" => @$PROCESSED["dassessment_id"] ? $PROCESSED["dassessment_id"] : null,
                                        "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                                        "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()
                                    )
                                );

                                // Fetch form data using those params
                                $form_data = $form_api->fetchFormData();
                                if ($progress_record && $progress_record->getProgressValue() == "complete") {
                                    $disabled = true;
                                }

                                $consistency_results = $form_api->formRubricsConsistencyCheck();
                                if ($consistency_results) {
                                    // Only render the page if the associated rubrics have passed a consistency check

                                    if ($has_been_released && $progress_record->getTargetRecordID() != $ENTRADA_USER->getActiveID() && !$distribution->getDeletedDate()):
                                        $released_user = Models_User::fetchRowByID($has_been_released->getApproverID());
                                        ?>
                                        <div class="alert alert-info text-center">
                                            <strong>
                                                <?php echo sprintf($translate->_("This assessment task was reviewed by %s on %s"), $released_user->getFullname(false), date("Y-m-d", $has_been_released->getCreatedDate())); ?>
                                            </strong>
                                        </div>
                                    <?php endif; ?>

                                    <div id="msgs"></div>
                                    <h1 id="form-heading"><?php echo $form->getTitle(); ?></h1>
                                    <div class="space-below medium row">
                                        <a href="?adistribution_id=<?php echo $PROCESSED["adistribution_id"] ?>&target_record_id=<?php echo $PROCESSED["target_record_id"]; ?>&aprogress_id=<?php echo $PROCESSED["aprogress_id"]; ?>&dassessment_id=<?php echo $PROCESSED["dassessment_id"]; ?>&generate-pdf=true"
                                           name="generate-pdf"
                                           class="btn btn-success pull-right"><i class="icon-download-alt icon-white"></i> <?php echo $translate->_("Download PDF"); ?>
                                        </a>
                                    </div>
                                    <?php
                                    if (isset($targets) && $targets && count($targets) > 1 && (!isset($review_mode) || !$review_mode)) {
                                        echo "<p id=\"targets_remaining\" class=\"muted\">" . sprintf($translate->_("You have <strong>%s</strong> assessments to complete with this form."), ($overall_attempts_max - $overall_attempts_completed)) . "</p>";
                                    } ?>
                                    <form id="assessment-form" action="<?php echo ENTRADA_URL . "/assessments/assessment?" . replace_query(array("step" => 2)); ?>" method="post">
                                        <input type="hidden" id="form_id" name="form_id" value="<?php echo html_encode($form->getID()); ?>"/>
                                        <input type="hidden" id="adistribution_id" name="adistribution_id" value="<?php echo html_encode($PROCESSED["adistribution_id"]); ?>"/>
                                        <input type="hidden" id="aprogress_id" name="aprogress_id" value="<?php echo html_encode($PROCESSED["aprogress_id"]); ?>"/>
                                        <input type="hidden" id="dassessment_id" name="dassessment_id" value="<?php echo(isset($PROCESSED["dassessment_id"]) ? html_encode($PROCESSED["dassessment_id"]) : "0"); ?>"/>
                                        <input type="hidden" id="schedule_id" name="schedule_id" value="<?php echo($distribution_schedule ? html_encode($distribution_schedule->getScheduleID()) : ""); ?>"/>
                                        <input type="hidden" id="target_record_id" name="target_record_id" value="<?php echo html_encode($PROCESSED["target_record_id"]); ?>"/>
                                        <input type="hidden" id="assessor_id" name="assessor_id" value="<?php echo(isset($PROCESSED["assessor_id"]) ? html_encode($PROCESSED["assessor_id"]) : ""); ?>"/>
                                        <input type="hidden" id="view" name="view" value="<?php echo(isset($PROCESSED["view"]) ? html_encode($PROCESSED["view"]) : ""); ?>"/>

                                        <div class="row-fluid">
                                            <?php

                                            if ($assessment_record->getAssessorValue() != $ENTRADA_USER->getActiveId()) {
                                                $disabled = true;
                                            }
                                            $incomplete = (!$progress_record || ($progress_record && $progress_record->getProgressValue() != "complete") ? true : false);

                                            if ((isset($PROCESSED["assessor_id"]) && $PROCESSED["assessor_id"]) && isset($PROCESSED["view"]) && $PROCESSED["view"] == "view_as") {
                                                $submit_on_behalf = true;
                                                $disabled = false;
                                            }

                                            if (!$incomplete) {
                                                $disabled = true;
                                            }

                                            if ($incomplete) {
                                                if ($disabled) {
                                                    ?>
                                                    <div class="alert alert-warning">
                                                        <ul>
                                                            <li>
                                                                <?php
                                                                echo $translate->_("You cannot complete this assessment because you are not this task's <strong>Assessor</strong>.");
                                                                ?>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                    <?php
                                                } elseif (isset($PROCESSED["assessor_id"]) && $PROCESSED["assessor_id"]) {
                                                    if (!isset($assessor_user) || !$assessor_user) {
                                                        $assessor_user = User::fetchRowByID($PROCESSED["assessor_id"]);
                                                    }
                                                }
                                            }
                                            if ($submit_on_behalf && $incomplete): ?>
                                            <div class="alert alert-warning">
                                                <ul>
                                                    <li>
                                                        <?php
                                                        if ($assessor_user) {
                                                            $name = $assessor_user->getFullname(false) . " (<a class=\"user-email\" href=\"#\"" . $assessor_user->getEmail() . "\" target =\"_top\">" . $assessor_user->getEmail() . "</a>)";
                                                        } else {
                                                            $name = "the assessor";
                                                        }
                                                        $format = $translate->_("You are submitting this assessment <strong>on behalf of</strong> %s.");
                                                        echo sprintf($format, $name);
                                                        ?>
                                                    </li>
                                                </ul>
                                            </div>
                                            <?php endif;

                                            $form_view = new Views_Assessments_Forms_Form(array("mode" => "assessment"));
                                            $form_view->render(
                                                array(
                                                    "form_id" => $form->getID(),
                                                    "disabled" => $disabled,
                                                    "form_elements" => $form_data["elements"],
                                                    "progress" => $form_data["progress"],
                                                    "rubrics" => $form_data["rubrics"],
                                                    "aprogress_id" => @$distribution_data["aprogress_id"] ? @$distribution_data["aprogress_id"] : null,
                                                    "public" => true,
                                                    "objectives" => $PROCESSED["objectives"]
                                                )
                                            );

                                            if ($distribution && $distribution->getFeedbackRequired()) {

                                                // This will not render when the related data is not present.
                                                $feedback_record = Models_Assessments_AssessorTargetFeedback::fetchRowByAssessorTarget($PROCESSED["dassessment_id"], "internal", $assessment_record->getAssessorValue(), "internal", $PROCESSED["target_record_id"]);

                                                $feedback_options = array(
                                                    "target_record_id" => $distribution_data["target_record_id"],
                                                    "distribution" => $distribution,
                                                    "hide_from_approver" => $hide_from_approver,
                                                    "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                                                    "progress_record" => $progress_record,
                                                    "assessment_record" => $assessment_record,
                                                    "feedback_record" => $feedback_record,
                                                );
                                                if ($feedback_record && $submit_on_behalf) {
                                                    if ($feedback_record->getAssessorFeedback()) {
                                                        $feedback_options["disabled"] = true;
                                                    }
                                                }

                                                // Append the feedback, if it's specified
                                                $feedback_view = new Views_Assessments_Forms_Sections_Feedback();
                                                $feedback_view->render($feedback_options);
                                            }


                                            if (isset($PROCESSED["objectives"]) && !empty($PROCESSED["objectives"])) {
                                                foreach ($PROCESSED["objectives"] as $afelement_id => $objectives) {
                                                    foreach ($objectives as $objective) {
                                                        echo "<input type=\"hidden\" name=\"afelement_objectives[" . html_encode($afelement_id) . "][]\" value=\"" . html_encode($objective) . "\" class=\"afelement-objective-" . html_encode($afelement_id) . "\" />";
                                                    }
                                                }
                                            }

                                            if (!$disabled && (!$progress_record || $progress_record && $progress_record->getProgressValue() != "complete") && !$distribution->getDeletedDate()) { ?>
                                                <div class="row-fluid">
                                                    <div class="pull-right">
                                                        <input type="submit" id="save-form" class="btn btn-warning" name="save_form_progress" value="<?php echo $translate->_("Save as Draft"); ?>"/>
                                                        <span class="or">or</span>
                                                        <input class="btn btn-primary" type="submit" id="submit_form" name="submit_form" value="<?php echo $translate->_("Submit"); ?>"/>
                                                    </div>
                                                </div>
                                                <?php
                                            }

                                            $distribution_approver = new Models_Assessments_Distribution_Approver();
                                            $approver_record = $distribution_approver->fetchRowByProxyIDDistributionID($ENTRADA_USER->getActiveId(), $PROCESSED["adistribution_id"]);
                                            $approver_approvals = new Models_Assessments_Distribution_Approvals();
                                            $approvals_record = $approver_approvals->fetchRowByProgressIDDistributionID($PROCESSED["aprogress_id"], $PROCESSED["adistribution_id"]);
                                            if ($approver_record && !$approvals_record && $disabled && isset($_GET["approver_task"]) && $progress_record && $progress_record->getProgressValue() == "complete" && !$distribution->getDeletedDate()) { ?>
                                                <div class="row-fluid">
                                                    <div class="pull-right">
                                                        <input type="button" id="hide_form" class="btn btn-warning" name="hide_form" value="<?php echo $translate->_("Hide Form"); ?>"/>
                                                        <span class="or">or</span>
                                                        <input type="button" id="release_form" class="btn btn-primary" name="release_form" value="<?php echo $translate->_("Release Form"); ?>"/>
                                                    </div>
                                                </div>
                                                <?php
                                            }
                                            ?>
                                        </div>
                                    </form>
                                    <?php
                                    $hide_assessment_task_modal = new Views_Assessments_Modals_HideAssessmentTask();
                                    $hide_assessment_task_modal->render();
                                } else {
                                    // Rubric consistency check failed
                                    echo display_error(sprintf($translate->_("An error was encountered when attempting to display this form.<br /><br />Please contact <a class=\"user-email\" href=\"#\">%s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));
                                    application_log("error", "Assessment executed rubric consistency check AND FAILED! Assessment id: '{$assessment_record->getID()}'.");
                                }
                            } else {
                                echo display_error($translate->_("You do not have permission to view this assessment."));
                            }
                        } else {
                            echo display_error($translate->_("Unfortunately, no target record ID was provided."));
                        }
                    }
                }
                break;
        }
    } else {
        add_error($translate->_("Unfortunately, no valid assessment ID was provided."));
        echo display_error();
    }
}