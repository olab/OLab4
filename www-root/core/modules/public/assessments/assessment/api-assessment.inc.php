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

    if (isset($_GET["view"]) && $tmp_input = clean_input($_GET["view"], array("trim", "striptags"))) {
        $PROCESSED["view"] = $tmp_input;
    } elseif (isset($_POST["view"]) && $tmp_input = clean_input($_POST["view"], array("trim", "striptags"))) {
        $PROCESSED["view"] = $tmp_input;
    } else {
        $PROCESSED["view"] = "";
    }

    if ($PROCESSED["view"] == "view_as") {
        if (isset($_GET["assessor_id"]) && $tmp_input = clean_input($_GET["assessor_id"], array("trim", "int"))) {
            $PROCESSED["assessor_id"] = $tmp_input;
        } elseif (isset($_POST["assessor_id"]) && $tmp_input = clean_input($_POST["assessor_id"], array("trim", "int"))) {
            $PROCESSED["assessor_id"] = $tmp_input;
        } elseif (isset($request["assessor_id"]) && $tmp_input = clean_input($request["assessor_id"], array("trim", "int"))) {
            $PROCESSED["assessor_id"] = $tmp_input;
        } else {
            add_error($translate->_("An error occurred while attempting to save responses for these targets. Please try again later."));
        }
    }

    if ($PROCESSED["view"] == "view_as" && isset($PROCESSED["assessor_id"]) && $PROCESSED["assessor_id"]) {
        $current_id = $PROCESSED["assessor_id"];
    } else {
        $current_id = $ENTRADA_USER->getActiveId();
    }

    switch ($request_method) {
        case "POST" :
            switch ($request["method"]) {
                case "save-feedback" :
                    if (isset($_POST["assessor_feedback_response"]) && $tmp_input = clean_input($_POST["assessor_feedback_response"], array("trim", "striptags"))) {
                        $PROCESSED["assessor_feedback_response"] = $tmp_input;
                    } else {
                        add_error($translate->_("Please select a response for <strong>Did you have an opportunity to meet with this trainee to discuss their performance?</strong>"));
                    }

                    if (isset($_POST["target_record_id"]) && $tmp_input = clean_input($_POST["target_record_id"], array("trim", "int"))) {
                        $PROCESSED["target_record_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No target identifier was provided"));
                    }

                    if (isset($_POST["proxy_id"]) && $tmp_input = clean_input($_POST["proxy_id"], array("trim", "int"))) {
                        $PROCESSED["proxy_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No identifier was provided"));
                    }

                    if (isset($_POST["dassessment_id"]) && $tmp_input = clean_input($_POST["dassessment_id"], array("trim", "int"))) {
                        $PROCESSED["dassessment_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No assessment identifier was provided"));
                    }

                    if (isset($_POST["feedback_response"]) && $tmp_input = clean_input($_POST["feedback_response"], array("trim", "striptags"))) {
                        $PROCESSED["feedback_response"] = $tmp_input;
                    } else {

                    }

                    if (isset($_POST["feedback_meeting_comments"]) && $tmp_input = clean_input($_POST["feedback_meeting_comments"], array("trim", "striptags"))) {
                        $PROCESSED["comments"] = $tmp_input;
                    } else {
                        $PROCESSED["comments"] = NULL;
                    }

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
                                    echo json_encode(array("status" => "error", "data" => $translate->_("A problem occurred while attempting to save your selected feedback option, please try again at a later time. ")));
                                } else {
                                    echo json_encode(array("status" => "success"));
                                }
                            }
                        } else {
                            $assessment_record = Models_Assessments_Assessor::fetchRowByID($PROCESSED["dassessment_id"]);
                            // Preceptor feedback
                            $method = "insert";
                            $PROCESSED["assessor_type"] = "internal";
                            $PROCESSED["assessor_value"] = $assessment_record->getAssessorValue();
                            $PROCESSED["assessor_feedback"] = ($PROCESSED["assessor_feedback_response"] == "yes" ? 1 : 0);
                            $PROCESSED["target_type"] = "internal";
                            $PROCESSED["target_value"] = $PROCESSED["target_record_id"];

                            $feedback_record = Models_Assessments_AssessorTargetFeedback::fetchRowByAssessorTarget($PROCESSED["dassessment_id"], "internal", $PROCESSED["assessor_value"], "internal", $PROCESSED["target_record_id"]);
                            if ($feedback_record) {
                                $method = "update";
                            } else {
                                $feedback_record = new Models_Assessments_AssessorTargetFeedback();
                            }

                            if ($method == "insert") {
                                $PROCESSED["created_by"] = $ENTRADA_USER->getActiveId();
                                $PROCESSED["created_date"] = time();
                            } else {
                                $PROCESSED["updated_by"] = $ENTRADA_USER->getActiveId();
                                $PROCESSED["updated_date"] = time();
                            }

                            if (!$feedback_record->fromArray($PROCESSED)->$method()) {
                                echo json_encode(array("status" => "error", "data" => $translate->_("A problem occurred while attempting to save your selected feedback option, please try again at a later time.")));
                            } else {
                                echo json_encode(array("status" => "success", "method" => $method));
                            }
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                case "save-responses" :
                    if (isset($request["form_id"]) && $tmp_input = clean_input($request["form_id"], array("trim", "int"))) {
                        $PROCESSED["form_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("A problem occurred while attempting to save your selected responses, please try again at a later time."));
                    }

                    if (isset($request["adistribution_id"]) && $tmp_input = clean_input($request["adistribution_id"], array("trim", "int"))) {
                        $PROCESSED["adistribution_id"] = $tmp_input;
                        $deleted_distribution = Models_Assessments_Distribution::fetchRowByID($PROCESSED["adistribution_id"], time());
                        if ($deleted_distribution) {
                            add_error($translate->_("The assessment cannot be saved or submitted as the associated <strong>Distribution</strong> has been deleted."));
                        }
                    } else {
                        add_error($translate->_("A problem occurred while attempting to save your selected responses, please try again at a later time."));
                    }

                    if (isset($request["dassessment_id"]) && $tmp_input = clean_input($request["dassessment_id"], array("trim", "int"))) {
                        $PROCESSED["dassessment_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("A problem occurred while attempting to save your selected responses, please try again at a later time."));
                    }

                    if (isset($request["assessor_id"]) && $tmp_input = clean_input($request["assessor_id"], array("trim", "int"))) {
                        $PROCESSED["assessor_id"] = $tmp_input;
                    } else {
                        $PROCESSED["assessor_id"] = $ENTRADA_USER->getActiveId();
                    }

                    if (isset($_POST["target_record_id"]) && $tmp_input = clean_input($_POST["target_record_id"], array("trim", "int"))) {
                        $PROCESSED["target_record_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("An error occurred while attempting to save responses for this target. Please try again later"));
                    }

                    if (isset($PROCESSED["target_record_id"]) && $PROCESSED["target_record_id"]) {
                        // Do not assign an assessment distribution target id if the assessment is additional only, as one will not exist.
                        $target = Models_Assessments_Distribution_Target::fetchRowByDistributionIDTargetID($PROCESSED["adistribution_id"], $PROCESSED["target_record_id"]);
                        $additional_flag = false;
                        if (!$target) {
                            $additional_targets = Models_Assessments_AdditionalTask::fetchAllByADistributionID($PROCESSED["adistribution_id"]);
                            foreach ($additional_targets as $additional) {
                                if ($additional->getTargetID() == $PROCESSED["target_record_id"]) {
                                    $additional_flag = true;
                                }
                            }
                            if (!$additional_flag) {
                                $target = Models_Assessments_Distribution_Target::fetchRowByDistributionID($PROCESSED["adistribution_id"]);
                            }
                        }

                        if ($additional_flag) {
                            $adtarget_id = 0;
                        } else {
                            if ($target) {
                                $adtarget_id = $target->getID();
                            } else {
                                add_error($translate->_("An error occurred while attempting to save responses for this target. Please try again later."));
                            }
                        }
                    }

                    if (!$ERROR) {
                        if (isset($request["aprogress_id"]) && $tmp_input = clean_input($request["aprogress_id"], array("trim", "int"))) {
                            $PROCESSED["aprogress_id"] = $tmp_input;
                            $progress = Models_Assessments_Progress::fetchRowByID($PROCESSED["aprogress_id"]);
                        } else {
                            $progress = Models_Assessments_Progress::fetchRowByAdistributionIDAssessorTypeAssessorValueTargetRecordIDDAssessmentID($PROCESSED["adistribution_id"], "internal", $current_id, $PROCESSED["target_record_id"], $PROCESSED["dassessment_id"], "inprogress");
                            if (isset($progress) && $progress) {
                                $PROCESSED["aprogress_id"] = $progress->getID();
                            }
                        }

                        $method = "insert";
                        if (isset($PROCESSED["aprogress_id"]) && $PROCESSED["aprogress_id"]) {
                            $method = "update";
                        }

                        if (isset($progress) && $progress && $progress->getProgressValue() === "complete") {
                            add_error($translate->_("You cannot update a submitted form."));
                        } else {
                            $PROCESSED["assessor_type"] = "internal";
                            $PROCESSED["assessor_value"] = $current_id;
                            $PROCESSED["progress_value"] = "inprogress";
                            $PROCESSED["target_learning_context_id"] = NULL;
                            $PROCESSED["adtarget_id"] = $adtarget_id;

                            if ($method === "insert") {
                                $PROCESSED["uuid"] = Models_Assessments_Progress::generateUuid();
                                $PROCESSED["created_date"] = time();
                                $PROCESSED["created_by"] = $ENTRADA_USER->getID();
                                $progress = new Models_Assessments_Progress($PROCESSED);
                            } else {
                                $PROCESSED["updated_date"] = time();
                                $PROCESSED["updated_by"] = $ENTRADA_USER->getID();
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
                    }

                    if (!$ERROR) {
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
                                    $PROCESSED_RESPONSE["assessor_value"] = $current_id;
                                    $PROCESSED_RESPONSE["afelement_id"] = $element->getID();
                                    switch ($element->getElementType()) {
                                        case "item" :
                                            $item = Models_Assessments_Item::fetchRowByID($element->getElementID());
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

                                                                $response = Models_Assessments_Progress_Response::fetchRowByAprogressIDAfelementID($progress->getID(), $element->getID());

                                                                if ($response) {
                                                                    $response_method = "update";
                                                                    $PROCESSED_RESPONSE["updated_date"] = time();
                                                                    $PROCESSED_RESPONSE["updated_by"] = $ENTRADA_USER->getID();
                                                                } else {
                                                                    $response = new Models_Assessments_Progress_Response($PROCESSED_RESPONSE);
                                                                    $PROCESSED_RESPONSE["created_date"] = time();
                                                                    $PROCESSED_RESPONSE["created_by"] = $ENTRADA_USER->getID();
                                                                }

                                                                if ($response_method === "update" || $response_method === "insert") {
                                                                    if (!$response->fromArray($PROCESSED_RESPONSE)->$response_method()) {
                                                                        add_error($translate->_("We were unable to save the form at this time. Please try again at a later time."));
                                                                        application_log("error", "A problem occurred while " . $method . "ing a progress response records DB said: " . $db->ErrorMsg());
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
                                                                        $PROCESSED_RESPONSE["created_by"] = $ENTRADA_USER->getID();

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

                                                                        $response = new Models_Assessments_Progress_Response($PROCESSED_RESPONSE);

                                                                        if (!$response->fromArray($PROCESSED_RESPONSE)->insert()) {
                                                                            add_error($translate->_("We were unable to save the form at this time. Please try again at a later time. "));
                                                                            application_log("error", "A problem occurred while " . $method . "ing a progress response records DB said: " . $db->ErrorMsg());
                                                                        }
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
                                                                    $PROCESSED_RESPONSE["updated_by"] = $ENTRADA_USER->getID();
                                                                } else {
                                                                    $response = new Models_Assessments_Progress_Response($PROCESSED_RESPONSE);
                                                                    $PROCESSED_RESPONSE["created_date"] = time();
                                                                    $PROCESSED_RESPONSE["created_by"] = $ENTRADA_USER->getID();
                                                                }

                                                                if (($response_method === "update") || ($response_method === "insert")) {
                                                                    if (!$response->fromArray($PROCESSED_RESPONSE)->$response_method()) {
                                                                        add_error($translate->_("We were unable to save the form at this time. Please try again at a later time."));
                                                                        application_log("error", "A problem occurred while " . $method . "ing a progress response records DB said: " . $db->ErrorMsg());
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
                                                                    $PROCESSED_RESPONSE["updated_by"] = $current_id;
                                                                } else {
                                                                    $response = new Models_Assessments_Progress_Response($PROCESSED_RESPONSE);
                                                                    $PROCESSED_RESPONSE["created_date"] = time();
                                                                    $PROCESSED_RESPONSE["created_by"] = $current_id;
                                                                }

                                                                if (($response_method === "update") || ($response_method === "insert")) {
                                                                    if (!$response->fromArray($PROCESSED_RESPONSE)->$response_method()) {
                                                                        add_error($translate->_("We were unable to save the form at this time. Please try again at a later time."));
                                                                        application_log("error", "A problem occurred while " . $method . "ing a progress response records DB said: " . $db->ErrorMsg());
                                                                    }
                                                                }
                                                            }
                                                            break;
                                                    }
                                                }
                                            } else {
                                                add_error($translate->_("A problem occurred while attempting to save your selected responses, please try again at a later time."));
                                            }
                                            break;
                                        case "objective" :
                                            $key = "objective-" . $element->getElementID();

                                            if (array_key_exists($key, $_POST)) {
                                                if ($tmp_input = clean_input($_POST[$key], array("trim", "int"))) {
                                                    $PROCESSED_RESPONSE["iresponse_id"] = $tmp_input;
                                                    $response_method = "insert";
                                                    $response = Models_Assessments_Progress_Response::fetchRowByAprogressIDAfelementID($progress->getID(), $element->getID());

                                                    if ($response) {
                                                        $response_method = "update";
                                                        $PROCESSED_RESPONSE["updated_date"] = time();
                                                        $PROCESSED_RESPONSE["updated_by"] = $ENTRADA_USER->getID();
                                                    } else {
                                                        $response = new Models_Assessments_Progress_Response($PROCESSED_RESPONSE);
                                                        $PROCESSED_RESPONSE["created_date"] = time();
                                                        $PROCESSED_RESPONSE["created_by"] = $ENTRADA_USER->getID();
                                                    }

                                                    if (($response_method === "update" && (int)$response->getIresponseID() !== $PROCESSED_RESPONSE["iresponse_id"]) || ($response_method === "insert")) {
                                                        if (!$response->fromArray($PROCESSED_RESPONSE)->$response_method()) {
                                                            add_error($translate->_("We were unable to save the form at this time. Please try again at a later time. "));
                                                            application_log("error", "A problem occurred while " . $method . "ing a progress response records DB said: " . $db->ErrorMsg());
                                                        }
                                                    }
                                                }
                                            }
                                            break;
                                    }
                                }
                            }
                        } else {
                            add_error($translate->_("A problem occurred while attempting to save your selected responses, please try again at a later time."));
                        }
                    }

                    if (!$ERROR) {
                        echo json_encode(array("status" => "success", "data" => array("saved" => date("g:i:sa", time()), "aprogress_id" => $progress->getID())));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                case "save-view-preferences" :
                    /*
                    if (isset($request["distribution_id"]) && $tmp_input = clean_input($request["distribution_id"], array("trim", "int"))) {
                        $PROCESSED["distribution_id"] = $tmp_input;
                    } else {
                        add_error("No distribution ID provided.");
                    }*/

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
                    if (isset($request["aprogress_id"]) && $tmp_input = clean_input($request["aprogress_id"], array("trim", "int"))) {
                        $API_PROCESSED["aprogress_id"] = $tmp_input;
                    } else {
                        add_error("No target form provided.");
                    }
                    if (isset($request["target_record_id"]) && $tmp_input = clean_input($request["target_record_id"], array("trim", "int"))) {
                        $API_PROCESSED["target_record_id"] = $tmp_input;
                    } else {
                        add_error("No new target provided.");
                    }

                    if (!$ERROR) {
                        $progress = Models_Assessments_Progress::fetchRowByID($API_PROCESSED["aprogress_id"]);
                        if ($progress) {
                            $old_target_record_id = $progress->getTargetRecordID();
                            if ($ENTRADA_ACL->amIAllowed(new AssessmentProgressResource($API_PROCESSED["aprogress_id"]), "update", true)) {
                                $progress->fromArray($API_PROCESSED);
                                if (!$progress->update()) {
                                    add_error("We were unable to update your form.  Please try again.");
                                } else {
                                    $distribution_assessment = Models_Assessments_Assessor::fetchRowByID($progress->getDAssessmentID());

                                    if($distribution_assessment) {
                                        $current_task_snapshot = Models_Assessments_CurrentTaskSnapshot::fetchRowByDistributionIDAssessmentIDAssessorValueTargetValueDeliveryDate($distribution_assessment->getADistributionID(), $progress->getDAssessmentID(), $distribution_assessment->getAssessorValue(), $PROCESSED["target_record_id"], $distribution_assessment->getDeliveryDate());
                                        if ($current_task_snapshot) {
                                            $current_task_snapshot->delete();
                                        }
                                    }

                                    $ENTRADA_LOGGER->log("Changed the form target from " . $old_target_record_id . " to " . $API_PROCESSED["target_record_id"], "update-progress", "aprogress_id", $progress->getID(), 4, __FILE__, $ENTRADA_USER->getID());
                                    echo json_encode(array("status" => "success", "data" => array("target_record_id" => $API_PROCESSED["target_record_id"])));
                                }
                            } else {
                                add_error("You are not allowed to change the target of this form.");
                            }
                        } else {
                            add_error("No form found to update.");
                        }

                    }
                    if ($ERROR) {
                        echo json_encode(array("status" => "error", "msg" => $ERRORSTR));
                    }
                    break;
                case "delete-delegated-tasks":

                    $test_failures = 0;
                    if (isset($request["task_data_array"]) && is_array($request["task_data_array"])) {
                        foreach ($request["task_data_array"] as $i => $task_data_array) {
                            if (isset($task_data_array["addassignment_id"]) && $tmp_input = clean_input($task_data_array["addassignment_id"], array("trim", "int"))) {
                                $PROCESSED["task_data"][$i]["addassignment_id"] = $tmp_input;
                            } else {
                                $test_failures++;
                            }
                            if (isset($task_data_array["addelegation_id"]) && $tmp_input = clean_input($task_data_array["addelegation_id"], array("trim", "int"))) {
                                $PROCESSED["task_data"][$i]["addelegation_id"] = $tmp_input;
                            } else {
                                $test_failures++;
                            }
                            if (isset($task_data_array["target_id"]) && $tmp_input = clean_input($task_data_array["target_id"], array("trim", "int"))) {
                                $PROCESSED["task_data"][$i]["target_id"] = $tmp_input;
                            } else {
                                $test_failures++;
                            }
                            if (isset($task_data_array["dassessment_id"]) && $tmp_input = clean_input($task_data_array["dassessment_id"], array("trim", "int"))) {
                                $PROCESSED["task_data"][$i]["dassessment_id"] = $tmp_input;
                            } else {
                                $test_failures++;
                            }
                            if (isset($task_data_array["target_type"]) && $tmp_input = clean_input($task_data_array["target_type"], array("trim", "notags"))) {
                                $PROCESSED["task_data"][$i]["target_type"] = $tmp_input;
                            } else {
                                $test_failures++;
                            }
                        }
                    }

                    if ($test_failures) {
                        add_error($translate->_("Invalid task data."));
                    }

                    if (isset($_GET["adistribution_id"]) && $tmp_input = clean_input($_GET["adistribution_id"], array("trim", "int"))) {
                        $PROCESSED["adistribution_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No distribution id was provided."));
                    }

                    if (isset($request["reason_id"]) && $tmp_input = clean_input($request["reason_id"], array("trim", "int"))) {
                        $PROCESSED["reason_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Please indicate why you are removing this assessment task from your task list."));
                    }

                    if (isset($PROCESSED["reason_id"])) {
                        if (isset($request["reason_notes"]) && $tmp_input = clean_input($request["reason_notes"], array("trim", "trimtags"))) {
                            $PROCESSED["reason_notes"] = $tmp_input;
                        } else {
                            $reason = Models_Assessments_TaskDeletedReason::fetchRowByID($PROCESSED["reason_id"]);
                            if ($reason->getNotesRequired()) {
                                add_error($translate->_("Please indicate why you are removing this assessment task from your task list."));
                            }
                            $PROCESSED["reason_notes"] = false;
                        }
                    }

                    $errors = 0;
                    $return_data = array();

                    if ($ERROR) {
                        $errors++;
                        $return_data = $ERRORSTR;
                    } else {
                        if (!empty($PROCESSED["task_data"])) {
                            foreach ($PROCESSED["task_data"] as $task_data) {
                                $distribution_delegation = new Entrada_Utilities_Assessments_DistributionDelegation(array(
                                    "adistribution_id" => $PROCESSED["adistribution_id"],
                                    "addelegation_id" => $task_data["addelegation_id"])
                                );
                                if ($distribution_delegation->deleteDelegatedTaskAndChildren($current_id, $task_data["addassignment_id"], $task_data["target_type"], $task_data["target_id"], $PROCESSED["reason_id"], $PROCESSED["reason_notes"]) == false) {
                                    $errors++;
                                }
                            }
                            if ($errors) {
                                add_error($translate->_("Unable to remove tasks."));
                            }
                        }
                    }
                    if ($errors) {
                        echo json_encode(array("status" => "error", "data" => array($ERRORSTR)));
                    } else {
                        echo json_encode(array("status" => "success", "data" => array($return_data)));
                    }
                break;
                case "delete-tasks" :
                    if (isset($_GET["adistribution_id"]) && $tmp_input = clean_input($_GET["adistribution_id"], array("trim", "int"))) {
                        $PROCESSED["adistribution_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No distribution id was provided."));
                    }

                    $PROCESSED["task_data"] = array();
                    if (isset($request["task_data_array"]) && is_array($request["task_data_array"])) {
                        foreach ($request["task_data_array"] as $key => $task) {

                            $tmp_assessor_type = clean_input($task["assessor_type"], array("trim", "striptags"));
                            $tmp_assessor_value = clean_input($task["assessor_value"], array("trim", "int"));
                            $tmp_target_id = clean_input($task["target_id"], array("trim", "int"));
                            $tmp_assessment_id = clean_input($task["assessment_id"], array("trim", "int"));
                            $tmp_delivery_date = clean_input($task["delivery_date"], array("trim", "int"));
                            if ($tmp_assessor_type && $tmp_assessor_value && $tmp_target_id && $tmp_delivery_date) {
                                $PROCESSED["task_data"][$key] = array(
                                    "assessor_type" => $tmp_assessor_type,
                                    "assessor_value" => $tmp_assessor_value,
                                    "target_id" => $tmp_target_id,
                                    "assessment_id" => $tmp_assessment_id,
                                    "delivery_date" => $tmp_delivery_date
                                );
                            } else {
                                add_error(sprintf($translate->_("Invalid or missing task data for task number %s"), $key));
                            }
                        }
                    } else {
                        add_error($translate->_("No task data provided."));
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
                        if (!empty($PROCESSED["task_data"])) {
                            global $db;
                            $deleted_tasks = array();
                            $notified_assessors = array();
                            require_once("Classes/notifications/NotificationUser.class.php");
                            require_once("Classes/notifications/Notification.class.php");

							foreach ($PROCESSED["task_data"] as $key => $task) {
                                // Regardless of the tasks previous status, add the task to the deleted tasks table.
                                $deleted_task = new Models_Assessments_DeletedTask(array(
                                    "adistribution_id" => $PROCESSED["adistribution_id"],
                                    "assessor_type" => $task["assessor_type"],
                                    "assessor_value" => $task["assessor_value"],
                                    "target_id" => $task["target_id"],
                                    "delivery_date" => $task["delivery_date"],
                                    "deleted_reason_id" => $PROCESSED["reason_id"],
                                    "deleted_reason_notes" => $PROCESSED["reason_notes"],
                                    "visible" => $PROCESSED["visible"],
                                    "created_date" => time(),
                                    "created_by" => $ENTRADA_USER->getActiveID()
                                ));

                                // Store the newly created deleted task to use it's ID for notification.
                                $deleted_task = $deleted_task->insert();

                                if ($deleted_task) {
                                    $deleted_tasks[] = $task;

                                    $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($PROCESSED["adistribution_id"]);
                                    if ($distribution_schedule) {
                                        $schedule = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID());
                                    }

                                    if ($task["assessment_id"]) {

                                        // Delete the current task snapshot
                                        $distribution_assessment = Models_Assessments_Assessor::fetchRowByID($task["assessment_id"]);
                                        if($distribution_assessment) {
                                            $current_task_snapshot = Models_Assessments_CurrentTaskSnapshot::fetchRowByDistributionIDAssessmentIDAssessorValueTargetValueDeliveryDate($distribution_assessment->getADistributionID(), $task["assessment_id"], $distribution_assessment->getAssessorValue(), $task["target_id"], $distribution_assessment->getDeliveryDate());
                                            if ($current_task_snapshot) {
                                                $current_task_snapshot->delete();
                                            }
                                        }

                                        // Delete progress for the assessment task.
                                        $progress = Models_Assessments_Progress::fetchRowByAdistributionIDAssessorTypeAssessorValueTargetRecordIDDAssessmentID($PROCESSED["adistribution_id"], $deleted_task->getAssessorType(), $deleted_task->getAssessorValue(), $task["target_id"], $task["assessment_id"]);
                                        if ($progress) {
                                            if (!$progress->fromArray(array("deleted_date" => time(), "deleted_by" => $ENTRADA_USER->getActiveID()))->update()) {
                                                application_log("error", "Unable to delete progress record " . $progress->getID() . " when attempting to delete distribution targets. DB said: " . $db->ErrorMsg());
                                                add_error($translate->_("There as an error removing previous progress for an assessment target. Please try again later."));
                                            }
                                        }
                                    }

                                    // Do not send duplicate notifications to an assessor.
                                    $post_notify = (isset($_POST["notify"])) ? $_POST["notify"] : false;
                                    $previously_notified = (clean_input($post_notify, array("trim", "striptags")) == 'no') ? true : false;
                                    if (isset($notified_assessors[$task["assessor_type"]]) && !empty($notified_assessors[$task["assessor_type"]])) {
                                        foreach ($notified_assessors[$task["assessor_type"]] as $assessor_value) {
                                            if ($assessor_value == $task["assessor_value"]) {
                                                $previously_notified = true;
                                            }
                                        }
                                    }

                                    // Only send a notification if the task has been delivered, meaning there is an assessment id associate with the task.
                                    if (!$previously_notified && ($task["assessment_id"])) {

                                        // Send a notification to the assessor informing them of the deletion.
                                        $notification_user = NotificationUser::get($task["assessor_value"], "assessment_task_deleted", $task["assessment_id"], $task["assessor_value"], "proxy_id");
                                        if (!$notification_user) {
                                            $notification_user = NotificationUser::add($task["assessor_value"], "assessment_task_deleted", $task["assessment_id"], $task["assessor_value"], 1, 0, 0, "proxy_id");
                                        }

                                        if (isset($notification_user) && $notification_user) {
                                            $notification = Notification::add($notification_user->getID(), $task["assessor_value"], $task["assessment_id"], $deleted_task->getID());
                                            if ($notification) {
                                                $assessment_notification = new Models_Assessments_Notification(array(
                                                    "adistribution_id" => $PROCESSED["adistribution_id"],
                                                    "assessment_value" => $task["assessment_id"],
                                                    "assessment_type" => "assessment",
                                                    "notified_value" => $task["assessor_value"],
                                                    "notified_type" => "proxy_id",
                                                    "notification_id" => $notification->getID(),
                                                    "nuser_id" => $notification_user->getID(),
                                                    "notification_type" => "assessment_task_deleted",
                                                    "schedule_id" => (isset($schedule) && $schedule ? $schedule->getID() : NULL),
                                                    "sent_date" => time()
                                                ));
                                                if ($assessment_notification->insert()) {
                                                    $notified_assessors[$task["assessor_type"]][] = $task["assessor_value"];
                                                } else {
                                                    application_log("error", "Error encountered while attempting to save history of an assessment being deleted by a user. DB said" . $db->ErrorMsg());
                                                    add_error($translate->_("An error occurred when attempting to notify the assessor of the task deletion."));
                                                }
                                            } else {
                                                application_log("error", "Error encountered while attempting to save history of an assessment being deleted by a user. DB said" . $db->ErrorMsg());
                                                add_error($translate->_("An error occurred when attempting to notify the assessor of the task deletion."));

                                            }
                                        } else {
                                            application_log("error", "Error encountered during creation of notification user while attempting to save history of an assessment being deleted by a user. DB said" . $db->ErrorMsg());
                                            add_error($translate->_("An error occurred when attempting to notify the assessor of the task deletion."));
                                        }
                                    }

                                } else {
                                    add_error($translate->_("An error occurred while attempting to delete a task. Please try again later."));
                                    application_log("error", "Error encountered during creation of notification user while attempting to save history of an assessment being deleted by a user. DB said" . $db->ErrorMsg());
                                }
                            }

                            if (!$ERROR) {
                                echo json_encode(array("status" => "success", "data" => $deleted_tasks));
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
				case "delete-task" :
					if (isset($_GET["adistribution_id"]) && $tmp_input = clean_input($_GET["adistribution_id"], array("trim", "int"))) {
						$PROCESSED["adistribution_id"] = $tmp_input;
					} else {
						add_error($translate->_("No distribution id was provided."));
					}

                    $PROCESSED["visible"] = isset($_GET["hide_deleted_task"]) ? 0 : 1;

					$PROCESSED["task_data"] = array();
					if (isset($request["task_data_array"]) && is_array($request["task_data_array"])) {
						foreach ($request["task_data_array"] as $key => $task) {
                            $tmp_assessor_type = $tmp_assessor_value = $tmp_target_id = $tmp_assessment_id = $tmp_task_type = $tmp_delivery_date = false;

						    if (isset($task["assessor_type"])) {
                                $tmp_assessor_type = clean_input($task["assessor_type"], array("trim", "striptags"));
                            }

                            if (isset($task["assessor_value"])) {
                                $tmp_assessor_value = clean_input($task["assessor_value"], array("trim", "int"));
                            }

                            if (isset($task["target_id"])) {
                                $tmp_target_id = clean_input($task["target_id"], array("trim", "int"));
                            }

                            if (isset($task["assessment_id"])) {
                                $tmp_assessment_id = clean_input($task["assessment_id"], array("trim", "int"));
                            }
                            
                            if (isset($task["task_type"])) {
                                $tmp_task_type = clean_input($task["task_type"], array("trim", "striptags"));
                            }

                            if (isset($task["delivery_date"])) {
                                $tmp_delivery_date = clean_input($task["delivery_date"], array("trim", "int"));
                            }

                            if ($tmp_assessor_type && $tmp_assessor_value) {
                                $PROCESSED["task_data"][$key] = array(
                                    "assessor_type" => $tmp_assessor_type,
                                    "assessor_value" => $tmp_assessor_value,
                                    "target_id" => $tmp_target_id,
                                    "assessment_id" => $tmp_assessment_id,
                                    "task_type" => $tmp_task_type,
                                    "delivery_date" => $tmp_delivery_date
                                );
                            } else {
                                add_error($translate->_("Invalid or missing task data for task."));
                            }
						}
					} else {
						add_error($translate->_("No task data provided."));
					}

					if (isset($request["reason_id"]) && $tmp_input = clean_input($request["reason_id"], array("trim", "int"))) {
						$PROCESSED["reason_id"] = $tmp_input;
					} else {
						add_error($translate->_("Please indicate why you are removing this assessment task from your task list."));
					}

					if (isset($PROCESSED["reason_id"])) {
						if (isset($request["reason_notes"]) && $tmp_input = clean_input($request["reason_notes"], array("trim", "trimtags"))) {
							$PROCESSED["reason_notes"] = $tmp_input;
						} else {
							$reason = Models_Assessments_TaskDeletedReason::fetchRowByID($PROCESSED["reason_id"]);
							if ($reason->getNotesRequired()) {
								add_error($translate->_("You must provide notes explaining the reasons for the deletion."));
							}
							$PROCESSED["reason_notes"] = false;
						}
					}

					if (!$ERROR) {
						if (!empty($PROCESSED["task_data"])) {
                            $task_distribution = Models_Assessments_Distribution::fetchRowByID($PROCESSED["adistribution_id"]);
                            $user_organisations = $ENTRADA_USER->getAllOrganisations();
                            $organisation_found = false;

                            foreach ($user_organisations as $org_key => $organisation) {
                                if ($org_key == $task_distribution->getOrganisationID()) {
                                    global $db;
                                    require_once("Classes/notifications/NotificationUser.class.php");
                                    require_once("Classes/notifications/Notification.class.php");

                                    $organisation_found = true;
                                    $deleted_tasks = array();
                                    $notified_assessors = array();
                                    $time = time();

                                    foreach ($PROCESSED["task_data"] as $key => $task) {
                                        // check if it's a single assessment of group of assessments
                                        if ($task["target_id"] > 0 || $task["task_type"] && $task["task_type"] == "delegation") {
                                            // it's a single assessment
                                            if ($task["assessment_id"]) {
                                                $current_task_snapshot = Models_Assessments_CurrentTaskSnapshot::fetchRowByDistributionIDAssessmentIDAssessorValueTargetValueDeliveryDate($PROCESSED["adistribution_id"], $task["assessment_id"], $task["assessor_value"], $task["target_id"], $task["delivery_date"]);
                                                if ($current_task_snapshot) {
                                                    $current_task_snapshot->delete();
                                                }

                                                $delegation_assignment = Models_Assessments_Distribution_DelegationAssignment::fetchRowByDistributionIDAssessmentIDAssessorValueTargetValue($PROCESSED["adistribution_id"], $task["assessment_id"], $task["assessor_value"], $task["target_id"], $task["delivery_date"]);
                                                if ($delegation_assignment) {
                                                    $delegation_assignment->setDeletedReasonID($PROCESSED["reason_id"]);
                                                    $delegation_assignment->setDeletedReason($PROCESSED["reason_notes"]);
                                                    $delegation_assignment->setDeletedDate();
                                                    $delegation_assignment->setUpdatedDate(time());
                                                    $delegation_assignment->setUpdatedBy($ENTRADA_USER->getID());
                                                    $delegation_assignment->update();
                                                }
                                                
                                                if ($task["task_type"] && $task["task_type"] == "delegation") {
                                                    $delegation = Models_Assessments_Distribution_Delegation::fetchRowByID($task["assessment_id"]);
                                                    if ($delegation) {
                                                        $delegation->setDeletedDate();
                                                        $delegation->setDeletedBy($ENTRADA_USER->getID());
                                                        $delegation->setUpdatedDate(time());
                                                        $delegation->setUpdatedBy($ENTRADA_USER->getID());
                                                        $delegation->update();
                                                    }
                                                }
                                            } else {
                                                $future_task_snapshot = Models_Assessments_FutureTaskSnapshot::fetchRowByDistributionIDAssessorValueTargetValueDeliveryDate($PROCESSED["adistribution_id"], $task["assessor_value"], $task["target_id"], $task["delivery_date"]);
                                                if ($future_task_snapshot) {
                                                    $future_task_snapshot->delete();
                                                }
                                            }

                                            $dt = Models_Assessments_DeletedTask::fetchRowByADistributionIDAssessorTypeAssessorValueTargetIDDeliveryDate($PROCESSED["adistribution_id"], $task["assessor_type"], $task["assessor_value"], $task["target_id"], $task["delivery_date"]);
                                            if (!$dt) {
                                                // Regardless of the tasks previous status, add the task to the deleted tasks table.
                                                $deleted_task = new Models_Assessments_DeletedTask(array(
                                                    "adistribution_id" => $PROCESSED["adistribution_id"],
                                                    "assessor_type" => $task["assessor_type"],
                                                    "assessor_value" => $task["assessor_value"],
                                                    "target_id" => $task["target_id"],
                                                    "delivery_date" => $task["delivery_date"],
                                                    "deleted_reason_id" => $PROCESSED["reason_id"],
                                                    "deleted_reason_notes" => $PROCESSED["reason_notes"],
                                                    "visible" => $PROCESSED["visible"],
                                                    "created_date" => $time,
                                                    "created_by" => $ENTRADA_USER->getID(),
                                                ));

                                                // Store the newly created deleted task to use it's ID for notification.
                                                $deleted_task = $deleted_task->insert();

                                                $current_assessment_tasks = Models_Assessments_CurrentTaskSnapshot::fetchAllByDistributionID($PROCESSED["adistribution_id"]);
                                                $current_delegation_tasks = Models_Assessments_Distribution_DelegationAssignment::fetchAllByDistributionID($PROCESSED["adistribution_id"]);

                                                if ($task["assessment_id"] && count($current_assessment_tasks) == 0 && count($current_delegation_tasks) == 0) {//if there is none targets if its an assessment or delegation task
                                                    if ($deleted_task) {
                                                        $previously_notified = false;
                                                        $schedule = false;

                                                        // update deleted_date field in cbl_distribution_assessments table
                                                        if (!$task["task_type"] || $task["task_type"] != "delegation") {
                                                            $distributions = Models_Assessments_Assessor::fetchRowByID($PROCESSED["task_data"][0]["assessment_id"]);
                                                            $distributions->fromArray(array("deleted_date" => time()));
                                                            $distributions->update();

                                                            $deleted_tasks[] = $task;

                                                            $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($PROCESSED["adistribution_id"]);
                                                            if ($distribution_schedule) {
                                                                $schedule = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID());
                                                            }

                                                            // Do not send duplicate notifications to an assessor.
                                                            $previously_notified = (clean_input($_POST["notify"], array("trim", "striptags")) == 'no') ? true : false;
                                                            if (isset($notified_assessors[$task["assessor_type"]]) && !empty($notified_assessors[$task["assessor_type"]])) {
                                                                foreach ($notified_assessors[$task["assessor_type"]] as $assessor_value) {
                                                                    if ($assessor_value == $task["assessor_value"]) {
                                                                        $previously_notified = true;
                                                                    }
                                                                }
                                                            }
                                                        }

                                                        // Only send a notification if the task has been delivered, meaning there is an assessment id associate with the task.
                                                        if (!$previously_notified && ($task["assessment_id"])) {
                                                            // Send a notification to the assessor informing them of the deletion.
                                                            $content_type = $task["task_type"] && $task["task_type"] == "delegation" ? "delegation_task_deleted" : "assessment_task_deleted";
                                                            $notification_user = NotificationUser::get($task["assessor_value"], $content_type, $task["assessment_id"], $task["assessor_value"], "proxy_id");
                                                            if (!$notification_user) {
                                                                $notification_user = NotificationUser::add($task["assessor_value"], $content_type, $task["assessment_id"], $task["assessor_value"], 1, 0, 0, "proxy_id");
                                                            }

                                                            if (isset($notification_user) && $notification_user) {
                                                                $notification = Notification::add($notification_user->getID(), $task["assessor_value"], $task["assessment_id"], $deleted_task->getID());
                                                                if ($notification) {
                                                                    $assessment_notification = new Models_Assessments_Notification(array(
                                                                        "adistribution_id" => $PROCESSED["adistribution_id"],
                                                                        "assessment_value" => $task["assessment_id"],
                                                                        "assessment_type" => $task["task_type"] && $task["task_type"] == "delegation" ?  "delegation" : "assessment",
                                                                        "notified_value" => $task["assessor_value"],
                                                                        "notified_type" => "proxy_id",
                                                                        "notification_id" => $notification->getID(),
                                                                        "nuser_id" => $notification_user->getID(),
                                                                        "notification_type" => $content_type,
                                                                        "schedule_id" => (isset($schedule) && $schedule ? $schedule->getID() : NULL),
                                                                        "sent_date" => $time,
                                                                    ));
                                                                    if ($assessment_notification->insert()) {
                                                                        $notified_assessors[$task["assessor_type"]][] = $task["assessor_value"];
                                                                    } else {
                                                                        application_log("error", "Error encountered while attempting to save history of an assessment being deleted by a user. DB said" . $db->ErrorMsg());
                                                                        add_error($translate->_("An error occurred when attempting to notify the assessor of the task deletion."));
                                                                    }
                                                                } else {
                                                                    application_log("error", "Error encountered while attempting to save history of an assessment being deleted by a user. DB said" . $db->ErrorMsg());
                                                                    add_error($translate->_("An error occurred when attempting to notify the assessor of the task deletion."));
                                                                }
                                                            } else {
                                                                application_log("error", "Error encountered during creation of notification user while attempting to save history of an assessment being deleted by a user. DB said" . $db->ErrorMsg());
                                                                add_error($translate->_("An error occurred when attempting to notify the assessor of the task deletion."));
                                                            }
                                                        }

                                                    } else {
                                                        add_error($translate->_("An error occurred while attempting to delete a task. Please try again later."));
                                                        application_log("error", "Error encountered during creation of notification user while attempting to save history of an assessment being deleted by a user. DB said" . $db->ErrorMsg());
                                                    }
                                                }
                                            }
                                        } else {
                                            // it's a multiple assessments group

                                            $target_tasks = Models_Assessments_Distribution_Target::getAssessmentTargets($PROCESSED["adistribution_id"], $task['assessment_id']);
                                            $progress_tasks = Models_Assessments_Progress::fetchAllByDistributionIDAssessmentID($PROCESSED["adistribution_id"], $task['assessment_id']);

                                            $inprogress_tasks = array();
                                            $completed_tasks = array();
                                            $pending_tasks = array();

                                            foreach ($target_tasks as $target_task) {
                                                $flag_new = true;
                                                foreach ($progress_tasks as $progress_task) {
                                                    if ($target_task['target_record_id'] == $progress_task->getTargetRecordID()) {
                                                        $flag_new = false;
                                                        if ($progress_task->getProgressValue() == "complete") {
                                                            $completed_tasks[] = $target_task;
                                                        } elseif ($progress_task->getProgressValue() == "inprogress") {
                                                            $inprogress_tasks[] = $target_task;
                                                        }
                                                    }
                                                }
                                                if ($flag_new) {
                                                    $pending_tasks[] = $target_task;
                                                }
                                            }
                                            // delete pending tasks and update progress table
                                            foreach ($inprogress_tasks as $current_task) {
                                                if ($task["assessment_id"]) {
                                                    $current_task_snapshot = Models_Assessments_CurrentTaskSnapshot::fetchRowByDistributionIDAssessmentIDAssessorValueTargetValueDeliveryDate($PROCESSED["adistribution_id"], $task["assessment_id"], $task["assessor_value"], $current_task["target_record_id"], $task["delivery_date"]);
                                                    if ($current_task_snapshot) {
                                                        $current_task_snapshot->delete();
                                                    }
                                                } else {
                                                    $future_task_snapshot = Models_Assessments_FutureTaskSnapshot::fetchRowByDistributionIDAssessorValueTargetValueDeliveryDate($PROCESSED["adistribution_id"], $task["assessor_value"], $current_task["target_record_id"], $task["delivery_date"]);
                                                    if ($future_task_snapshot) {
                                                        $future_task_snapshot->delete();
                                                    }
                                                }

                                                $dt = Models_Assessments_DeletedTask::fetchRowByADistributionIDAssessorTypeAssessorValueTargetIDDeliveryDate($PROCESSED["adistribution_id"], $task["assessor_type"], $task["assessor_value"], $current_task['target_record_id'], $task["delivery_date"]);
                                                if (!$dt) {
                                                    // Regardless of the tasks previous status, add the task to the deleted tasks table.
                                                    $deleted_task = new Models_Assessments_DeletedTask(array(
                                                        "adistribution_id" => $PROCESSED["adistribution_id"],
                                                        "assessor_type" => $task["assessor_type"],
                                                        "assessor_value" => $task["assessor_value"],
                                                        "target_id" => $current_task['target_record_id'],
                                                        "delivery_date" => $task["delivery_date"],
                                                        "deleted_reason_id" => $PROCESSED["reason_id"],
                                                        "deleted_reason_notes" => $PROCESSED["reason_notes"],
                                                        "visible" => $PROCESSED["visible"],
                                                        "created_date" => $time,
                                                        "created_by" => $ENTRADA_USER->getActiveID(),
                                                    ));

                                                    // Store the newly created deleted task to use it's ID for notification.
                                                    $deleted_task = $deleted_task->insert();
                                                    // Delete progress for the assessment task.
                                                    $progress = Models_Assessments_Progress::fetchRowByAdistributionIDAssessorTypeAssessorValueTargetRecordIDDAssessmentID($PROCESSED["adistribution_id"], $task["assessor_type"], $task["assessor_value"], $current_task['target_record_id'], $task["assessment_id"]);
                                                    if ($progress) {
                                                        if (!$progress->fromArray(array("deleted_date" => $time, "progress_value" => 'cancelled', "deleted_by" => $ENTRADA_USER->getActiveID()))->update()) {
                                                            application_log("error", "Unable to delete progress record " . $progress->getID() . " when attempting to delete distribution targets. DB said: " . $db->ErrorMsg());
                                                            add_error($translate->_("There as an error removing previous progress for an assessment target. Please try again later."));
                                                        } else {
                                                            $distribution_assessment = Models_Assessments_Assessor::fetchRowByID($task["assessment_id"]);

                                                            if($distribution_assessment) {
                                                                $current_task_snapshot = Models_Assessments_CurrentTaskSnapshot::fetchRowByDistributionIDAssessmentIDAssessorValueTargetValueDeliveryDate($distribution_assessment->getADistributionID(), $task["assessment_id"], $distribution_assessment->getAssessorValue(), $current_task['target_record_id'], $distribution_assessment->getDeliveryDate());
                                                                if ($current_task_snapshot) {
                                                                    $current_task_snapshot->delete();
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }

                                            // delete inprogress tasks and update progress table
                                            foreach ($pending_tasks as $current_task) {
                                                if ($task["assessment_id"]) {
                                                    $current_task_snapshot = Models_Assessments_CurrentTaskSnapshot::fetchRowByDistributionIDAssessmentIDAssessorValueTargetValueDeliveryDate($PROCESSED["adistribution_id"], $task["assessment_id"], $task["assessor_value"], $current_task["target_record_id"], $task["delivery_date"]);
                                                    if ($current_task_snapshot) {
                                                        $current_task_snapshot->delete();
                                                    }
                                                } else {
                                                    $future_task_snapshot = Models_Assessments_FutureTaskSnapshot::fetchRowByDistributionIDAssessorValueTargetValueDeliveryDate($PROCESSED["adistribution_id"], $task["assessor_value"], $current_task["target_record_id"], $task["delivery_date"]);
                                                    if ($future_task_snapshot) {
                                                        $future_task_snapshot->delete();
                                                    }
                                                }

                                                $dt = Models_Assessments_DeletedTask::fetchRowByADistributionIDAssessorTypeAssessorValueTargetIDDeliveryDate($PROCESSED["adistribution_id"], $task["assessor_type"], $task["assessor_value"], $current_task['target_record_id'], $task["delivery_date"]);
                                                if (!$dt) {
                                                    // Regardless of the tasks previous status, add the task to the deleted tasks table.
                                                    $deleted_task = new Models_Assessments_DeletedTask(array(
                                                        "adistribution_id" => $PROCESSED["adistribution_id"],
                                                        "assessor_type" => $task["assessor_type"],
                                                        "assessor_value" => $task["assessor_value"],
                                                        "target_id" => $current_task['target_record_id'],
                                                        "delivery_date" => $task["delivery_date"],
                                                        "deleted_reason_id" => $PROCESSED["reason_id"],
                                                        "deleted_reason_notes" => $PROCESSED["reason_notes"],
                                                        "visible" => $PROCESSED["visible"],
                                                        "created_date" => $time,
                                                        "created_by" => $ENTRADA_USER->getID(),
                                                    ));

                                                    // Store the newly created deleted task to use it's ID for notification.
                                                    $deleted_task = $deleted_task->insert();

                                                    // Delete progress for the assessment task.
                                                    $current_progress = array(
                                                        "adistribution_id" => $PROCESSED["adistribution_id"],
                                                        "dassessment_id" => $task["assessment_id"],
                                                        "uuid" => Models_Assessments_Progress::generateUuid(),
                                                        "assessor_type" => 'internal',
                                                        "assessor_value" => $task["assessor_value"],
                                                        "adtarget_id" => Models_Assessments_Distribution_Target::fetchRowByDistributionID($PROCESSED["adistribution_id"])->getID(),
                                                        "target_record_id" => $current_task['target_record_id'],
                                                        "progress_value" => 'cancelled',
                                                        "created_date" => $time,
                                                        "created_by" => $ENTRADA_USER->getID(),
                                                        "updated_date" => $time,
                                                        "updated_by" => $ENTRADA_USER->getID(),
                                                    );
                                                    $progress = new Models_Assessments_Progress($current_progress);
                                                    if (!$progress->insert()) {
                                                        application_log("error", "Unable to insert cancelled records " . $progress->getID() . " when attempting to delete distribution targets. DB said: " . $db->ErrorMsg());
                                                        add_error($translate->_("There as an error removing previous progress for an assessment target. Please try again later."));
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    if (!$ERROR) {
                                        echo json_encode(array("status" => "success", "data" => $deleted_tasks));
                                    } else {
                                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                    }
                                    break;
                                }
                            }

                            if (!$organisation_found) {
                                $created_by = Models_User::fetchRowByID($task_distribution->getCreatedBy());
                                echo json_encode(array("status" => "error", "data" => array($translate->_("Only the author (" . $created_by->getFullname(false) . ") of the task or the assessor can delete this task."))));
                            }
						} else {
							echo json_encode(array("status" => "error", "data" => array($translate->_("No valid tasks provided for deletion."))));
						}
					} else {
						echo json_encode(array("status" => "error", "data" => $ERRORSTR));
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
                                            // Check to see if the assessor is external.
                                            $external_assessor = Models_Assessments_Distribution_Assessor::fetchRowByExternalAssessorIDDistributionID($assessor_id, $assessment->getADistributionID());

                                            // Send a notification to the assessor informing them of the deletion.
                                            $notification_user = NotificationUser::get($assessor_id, "assessment", $dassessment_id, $assessor_id, ($external_assessor ? "external_assessor_id" : "proxy_id"));
                                            if (!$notification_user) {
                                                $notification_user = NotificationUser::add($assessor_id, "assessment", $dassessment_id, $assessor_id, 1, 0, 0, ($external_assessor ? "external_assessor_id" : "proxy_id"));
                                            }

                                            $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($assessment->getADistributionID());
                                            if ($distribution_schedule) {
                                                $schedule = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID());
                                            }

                                            if (isset($notification_user) && $notification_user) {
                                                $notification = Notification::add($notification_user->getID(), $assessor_id, $dassessment_id, true);
                                                if ($notification) {
                                                    $assessment_notification = new Models_Assessments_Notification(array(
                                                        "adistribution_id" => $assessment->getADistributionID(),
                                                        "assessment_value" => $dassessment_id,
                                                        "assessment_type" => "assessment",
                                                        "notified_value" => $assessor_id,
                                                        "notified_type" => ($external_assessor ? "external_assessor_id" : "proxy_id"),
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
                        $api_status = "error";
                        $api_message = $ERRORSTR;
                    } else {
                        $api_status = "success";
                        $api_message = $translate->_("Successfully sent reminder(s).");
                    }
                    echo json_encode(array("status" => $api_status, "data" => array($api_message)));

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
                    if (isset($_POST["task_data"])) {
                        //The string is being encoded twice (once by the post and another by the stringfy), so it needs to be decoded twice.
                        $json = json_decode($_POST["task_data"], true);
                        $json = json_decode($json, true);

                        if (isset($_GET["current-location"]) && $tmp_input = clean_input(strtolower($_GET["current-location"]), array("trim", "striptags"))) {
                            $PROCESSED["current_location"] = $tmp_input;
                        } else {
                            $PROCESSED["current_location"] = "";
                        }

                        foreach ($json as $i => $task_data_array) {
                            if (isset($task_data_array["target_id"]) && $tmp_input = clean_input($task_data_array["target_id"], array("trim", "int"))) {
                                $PROCESSED["task_data"][$i]["target_id"] = $tmp_input;
                            } else {
                                add_error($translate->_("Invalid target id provided."));
                            }

                            if (isset($task_data_array["dassessment_id"]) && $tmp_input = clean_input($task_data_array["dassessment_id"], array("trim", "int"))) {
                                $PROCESSED["task_data"][$i]["dassessment_id"] = $tmp_input;
                            } else {
                                add_error($translate->_("Invalid assessment id provided."));
                            }

                            if (isset($task_data_array["assessor_value"]) && $tmp_input = clean_input($task_data_array["assessor_value"], array("trim", "int"))) {
                                $PROCESSED["task_data"][$i]["assessor_value"] = $tmp_input;
                            } else {
                                add_error($translate->_("Invalid assessor value provided."));
                            }

                            if (isset($task_data_array["assessor_name"]) && $tmp_input = clean_input($task_data_array["assessor_name"], array("trim", "notags"))) {
                                $PROCESSED["task_data"][$i]["assessor_name"] = $tmp_input;
                            } else {
                                add_error($translate->_("Invalid assessor name provided."));
                            }

                            if (isset($task_data_array["target_name"]) && $tmp_input = clean_input($task_data_array["target_name"], array("trim", "notags"))) {
                                $PROCESSED["task_data"][$i]["target_name"] = $tmp_input;
                            } else {
                                add_error($translate->_("Invalid target name provided."));
                            }

                            if (isset($task_data_array["aprogress_id"]) && $tmp_input = clean_input($task_data_array["aprogress_id"], array("trim", "int"))) {
                                $PROCESSED["task_data"][$i]["aprogress_id"] = $tmp_input;
                            } else {
                                $PROCESSED["task_data"][$i]["aprogress_id"] = null;
                            }

                            if (isset($task_data_array["adistribution_id"]) && $tmp_input = clean_input($task_data_array["adistribution_id"], array("trim", "int"))) {
                                $PROCESSED["task_data"][$i]["adistribution_id"] = $tmp_input;
                            } else {
                                add_error($translate->_("No distribution id was provided."));
                            }
                        }
                    } else {
                        add_error($translate->_("No target data provided."));
                    }

                    if (!$ERROR) {
                        $pdf_generator = new Entrada_Utilities_Assessments_PDFDownload();
                        $pdf_generator->prepareDownloadMultiple($PROCESSED);
                    }
                    break;
                case "generate-pdf-for-tasks":
                    if (isset($_POST["task_data"])) {
                        //The string is being encoded twice (once by the post and another by the stringfy), so it needs to be decoded twice.
                        $json = json_decode($_POST["task_data"], true);
                        $json = json_decode($json, true);

                        if (isset($_GET["current-location"]) && $tmp_input = clean_input(strtolower($_GET["current-location"]), array("trim", "striptags"))) {
                            $PROCESSED["current_location"] = $tmp_input;
                        } else {
                            $PROCESSED["current_location"] = "";
                        }

                        foreach ($json as $i => $task_data_array) {
                            if (isset($task_data_array["target_id"]) && $tmp_input = clean_input($task_data_array["target_id"], array("trim", "int"))) {
                                $PROCESSED["task_data"][$i]["target_id"] = $tmp_input;
                            } else {
                                add_error($translate->_("Invalid target id provided."));
                            }

                            if (isset($task_data_array["dassessment_id"]) && $tmp_input = clean_input($task_data_array["dassessment_id"], array("trim", "int"))) {
                                $PROCESSED["task_data"][$i]["dassessment_id"] = $tmp_input;
                            } else {
                                add_error($translate->_("Invalid assessment id provided."));
                            }

                            if (isset($task_data_array["assessor_value"]) && $tmp_input = clean_input($task_data_array["assessor_value"], array("trim", "int"))) {
                                $PROCESSED["task_data"][$i]["assessor_value"] = $tmp_input;
                            } else {
                                add_error($translate->_("Invalid assessor value provided."));
                            }

                            if (isset($task_data_array["assessor_name"]) && $tmp_input = clean_input($task_data_array["assessor_name"], array("trim", "notags"))) {
                                $PROCESSED["task_data"][$i]["assessor_name"] = $tmp_input;
                            } else {
                                add_error($translate->_("Invalid assessor name provided."));
                            }

                            if (isset($task_data_array["target_name"]) && $tmp_input = clean_input($task_data_array["target_name"], array("trim", "notags"))) {
                                $PROCESSED["task_data"][$i]["target_name"] = $tmp_input;
                            } else {
                                add_error($translate->_("Invalid target name provided."));
                            }

                            if (isset($task_data_array["adistribution_id"]) && $tmp_input = clean_input($task_data_array["adistribution_id"], array("trim", "int"))) {
                                $PROCESSED["task_data"][$i]["adistribution_id"] = $tmp_input;
                            } else {
                                add_error($translate->_("No distribution id was provided."));
                            }

                            if (isset($task_data_array["aprogress_id"]) && $tmp_input = clean_input($task_data_array["aprogress_id"], array("trim", "int"))) {
                                $PROCESSED["task_data"][$i]["aprogress_id"] = $tmp_input;
                            } else {
                                $PROCESSED["task_data"][$i]["aprogress_id"] = null;
                            }
                        }
                    } else {
                        add_error($translate->_("No target data provided."));
                    }

                    if (!$ERROR) {
                        $pdf_generator = new Entrada_Utilities_Assessments_PDFDownload();
                        $pdf_generator->prepareDownloadSingle($PROCESSED);
                    } else {
                        display_error($translate->_("Unable to create ZIP archive. PDF generator library path not found."));
                    }
                    break;
            }
            break;
        case "GET" :
            switch ($request["method"]) {
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