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
 * API to handle change the target of an external form.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
 */
if((!defined("PARENT_INCLUDED")) || (!defined("IN_EXTERNAL_ASSESSMENT"))) {
    exit;
}

// If the user is logged in, check permissions and redirect them.
if ((isset($_SESSION["isAuthorized"]) && ($_SESSION["isAuthorized"]))) {

    if ($ENTRADA_ACL->amIAllowed("assessments", "read", false)) {
        $url = ENTRADA_URL . "/assessments";
        header("Location: $url");
    } else {
        add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%s\">%s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));
        echo display_error();
        application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] does not have access to this module [" . $MODULE . "]");
    }

} else {

    ob_clear_open_buffers();

    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));

    $request = ${"_" . $request_method};

    if (isset($_GET["view"]) && $tmp_input = clean_input($_GET["view"], array("trim",  "striptags"))) {
        $PROCESSED["view"] = $tmp_input;
    } else  if (isset($_POST["view"]) && $tmp_input = clean_input($_POST["view"], array("trim",  "striptags"))) {
        $PROCESSED["view"] = $tmp_input;
    } else {
        $PROCESSED["view"] = "";
    }


    if (isset($_GET["assessor_value"]) && $tmp_input = clean_input($_GET["assessor_value"], array("trim", "int"))) {
        $PROCESSED["assessor_id"] = $tmp_input;
    } elseif (isset($_POST["assessor_value"]) && $tmp_input = clean_input($_POST["assessor_value"], array("trim", "int"))) {
        $PROCESSED["assessor_value"] = $tmp_input;
    } else {
        add_error($translate->_("An error occurred while attempting to save responses for these targets. Please try again later."));
    }

    if (isset($PROCESSED["assessor_value"]) && $PROCESSED["assessor_value"]) {
        $current_id  = $PROCESSED["assessor_value"];
    }

    if ($request) {
        switch ($request_method) {
            case "POST" :
                switch ($request["method"]) {
                    case "save-responses" :
                        if (isset($request["form_id"]) && $tmp_input = clean_input($request["form_id"], array("trim", "int"))) {
                            $PROCESSED["form_id"] = $tmp_input;
                        } else {
                            add_error($translate->_("A problem occurred while attempting to save your selected responses, please try again at a later time."));
                        }

                        if (isset($request["adistribution_id"]) && $tmp_input = clean_input($request["adistribution_id"], array("trim", "int"))) {
                            $PROCESSED["adistribution_id"] = $tmp_input;
                        } else {
                            add_error($translate->_("A problem occurred while attempting to save your selected responses, please try again at a later time."));
                        }

                        if (isset($_POST["target_record_id"]) && $tmp_input = clean_input($_POST["target_record_id"], array("trim", "int"))) {
                            $PROCESSED["target_record_id"] = $tmp_input;
                        } else {
                            add_error($translate->_("An error occurred while attempting to save responses for this target. Please try again later."));
                        }

                        if (isset($_POST["dassessment_id"]) && $tmp_input = clean_input($_POST["dassessment_id"], array("trim", "int"))) {
                            $PROCESSED["dassessment_id"] = $tmp_input;
                        } else {
                            add_error($translate->_("An error occurred while attempting to save responses for this target. Please try again later."));
                        }

                        if (isset($request["assessor_value"]) && $tmp_input = clean_input($request["assessor_value"], array("trim", "int"))) {
                            $PROCESSED["assessor_value"] = $tmp_input;
                        } else {
                            add_error("No assessor provided.");
                        }

                        if (isset($request["external_hash"]) && $tmp_input = clean_input($request["external_hash"], array("trim", "alphanumeric"))) {
                            $PROCESSED["external_hash"] = $tmp_input;
                        } else {
                            add_error($translate->_("An error occurred while attempting to save responses for this target. Please try again later."));
                        }

                        if (isset($PROCESSED["dassessment_id"]) && $PROCESSED["dassessment_id"] && isset($PROCESSED["assessor_value"]) && $PROCESSED["assessor_value"] && isset($PROCESSED["target_record_id"]) && $PROCESSED["target_record_id"]) {
                            $assessment = Models_Assessments_Assessor::fetchRowByID($PROCESSED["dassessment_id"]);

                            if ($assessment && $assessment->getAssessorType() == "external" && $assessment->getAssessorValue() == $PROCESSED["assessor_value"]) {
                                $PROCESSED["assessor_value"] = $assessment->getAssessorValue();
                                $PROCESSED["assessor_type"] = "external";

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
                                }

                                if ($additional_flag) {
                                    $adtarget_id = 0;
                                } else {
                                    $target = Models_Assessments_Distribution_Target::fetchRowByDistributionID($PROCESSED["adistribution_id"]);
                                    if ($target) {
                                        $adtarget_id = $target->getID();
                                    } else {
                                        add_error($translate->_("An error occurred while attempting to save responses for this target. Please try again later."));
                                    }
                                }
                            } else {
                                add_error($translate->_("Unable to fetch assessor value"));
                            }
                        }

                        if (!$ERROR) {
                            if (isset($request["aprogress_id"]) && $tmp_input = clean_input($request["aprogress_id"], array("trim", "int"))) {
                                $PROCESSED["aprogress_id"] = $tmp_input;
                                $progress = Models_Assessments_Progress::fetchRowByID($PROCESSED["aprogress_id"]);
                            } else {
                                $progress = Models_Assessments_Progress::fetchRowByAdistributionIDAssessorTypeAssessorValueTargetRecordIDDAssessmentID($PROCESSED["adistribution_id"], "external", $current_id, $PROCESSED["target_record_id"], $PROCESSED["dassessment_id"], "inprogress");
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
                                $PROCESSED["assessor_type"] = "external";
                                $PROCESSED["progress_value"] = "inprogress";
                                $PROCESSED["target_learning_context_id"] = NULL;
                                $PROCESSED["adtarget_id"] = $adtarget_id;

                                if ($method === "insert") {
                                    $PROCESSED["uuid"] = Models_Assessments_Progress::generateUuid();
                                    $PROCESSED["created_date"] = time();
                                    $PROCESSED["created_by"] = $PROCESSED["assessor_value"];
                                    $progress = new Models_Assessments_Progress($PROCESSED);
                                } else {
                                    $PROCESSED["updated_date"] = time();
                                    $PROCESSED["updated_by"] = $PROCESSED["assessor_value"];
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
                                        $PROCESSED_RESPONSE["assessor_type"] = "external";
                                        $PROCESSED_RESPONSE["assessor_value"] = $current_id;
                                        $PROCESSED_RESPONSE["afelement_id"] = $element->getID();

                                        switch ($element->getElementType()) {
                                            case "item" :
                                                $item = Models_Assessments_Item::fetchRowByID($element->getElementID());
                                                if ($item) {
                                                    $comments_key = "item-" . $element->getElementID() . "-comments";

                                                    if (isset($_POST[$comments_key]) && ($tmp_input = clean_input($_POST[$comments_key], array("trim", "striptags")))) {
                                                        $PROCESSED_RESPONSE["comments"] = $tmp_input;
                                                    } else {
                                                        $PROCESSED_RESPONSE["comments"] = NULL;
                                                    }

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
                                                            case "8" :
                                                            case "9" :
                                                            case "10" :
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
                                                                    $PROCESSED_RESPONSE["updated_by"] = $current_id;
                                                                } else {
                                                                    $response = new Models_Assessments_Progress_Response($PROCESSED_RESPONSE);
                                                                    $PROCESSED_RESPONSE["created_date"] = time();
                                                                    $PROCESSED_RESPONSE["created_by"] = $current_id;
                                                                }

                                                                if ($response_method === "update" || $response_method === "insert") {
                                                                    if (!$response->fromArray($PROCESSED_RESPONSE)->$response_method()) {
                                                                        add_error($translate->_("We were unable to save the form at this time. Please try again at a later time. " . $db->ErrorMsg()));
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
                                                                        $PROCESSED_RESPONSE["created_by"] = $current_id;

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
                                                                            add_error($translate->_("We were unable to save the form at this time. Please try again at a later time. " . $db->ErrorMsg()));
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
                                                            $PROCESSED_RESPONSE["updated_by"] = $PROCESSED["assessor_value"];
                                                        } else {
                                                            $response = new Models_Assessments_Progress_Response($PROCESSED_RESPONSE);
                                                            $PROCESSED_RESPONSE["created_date"] = time();
                                                            $PROCESSED_RESPONSE["created_by"] = $PROCESSED["assessor_value"];
                                                        }

                                                        if (($response_method === "update" && (int)$response->getIresponseID() !== $PROCESSED_RESPONSE["iresponse_id"]) || ($response_method === "insert")) {
                                                            if (!$response->fromArray($PROCESSED_RESPONSE)->$response_method()) {
                                                                add_error($translate->_("We were unable to save the form at this time. Please try again at a later time. " . $db->ErrorMsg()));
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
                        if (isset($request["distribution_id"]) && $tmp_input = clean_input($request["distribution_id"], array("trim", "int"))) {
                            $PROCESSED["distribution_id"] = $tmp_input;
                        } else {
                            add_error("No distribution ID provided.");
                        }

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

                        if (isset($request["dassessment_id"]) && $tmp_input = clean_input($request["dassessment_id"], array("trim", "int"))) {
                            $API_PROCESSED["dassessment_id"] = $tmp_input;
                        } else {
                            add_error($translate->_("No assessment provided."));
                        }

                        if (isset($request["assessor_value"]) && $tmp_input = clean_input($request["assessor_value"], array("trim", "int"))) {
                            $API_PROCESSED["assessor_value"] = $tmp_input;
                        } else {
                            add_error("No assessor provided.");
                        }

                        if (!$ERROR) {
                            $assessment = Models_Assessments_Assessor::fetchRowByID($API_PROCESSED["dassessment_id"]);
                            if ($assessment && $assessment->getAssessorType() == "external" && $assessment->getAssessorValue() == $API_PROCESSED["assessor_value"]) {

                                $API_PROCESSED["assessor_value"] = $assessment->getAssessorValue();

                                if (!$ERROR) {
                                    $progress = Models_Assessments_Progress::fetchRowByID($API_PROCESSED["aprogress_id"]);
                                    if ($progress) {
                                        $old_target_record_id = $progress->getTargetRecordID();
                                        $progress->fromArray($API_PROCESSED);
                                        if (!$progress->update()) {
                                            add_error("We were unable to update your form.  Please try again.");
                                        } else {
                                            $distribution_assessment = Models_Assessments_Assessor::fetchRowByID($PROCESSED["dassessment_id"]);

                                            if($distribution_assessment) {
                                                $current_task_snapshot = Models_Assessments_CurrentTaskSnapshot::fetchRowByDistributionIDAssessmentIDAssessorValueTargetValueDeliveryDate($distribution_assessment->getADistributionID(), $PROCESSED["dassessment_id"], $distribution_assessment->getAssessorValue(), $PROCESSED["target_record_id"], $distribution_assessment->getDeliveryDate());
                                                if ($current_task_snapshot) {
                                                    $current_task_snapshot->delete();
                                                }
                                            }

                                            $ENTRADA_LOGGER->log("Changed the form target from " . $old_target_record_id . " to " . $API_PROCESSED["target_record_id"], "update-progress", "aprogress_id", $progress->getID(), 4, __FILE__, $API_PROCESSED["assessor_value"]);
                                            echo json_encode(array("status" => "success", "data" => array("target_record_id" => $API_PROCESSED["target_record_id"])));
                                        }

                                    } else {
                                        add_error("No form found to update.");
                                    }

                                }
                                if ($ERROR) {
                                    echo json_encode(array("status" => "error", "msg" => $ERRORSTR));
                                }
                            } else {
                                add_error($translate->_("Could not find assessor value."));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $ERRORSTR));
                        }
                    break;
                }
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

                        if (isset($request["organisation_id"]) && $tmp_input = clean_input($request["organisation_id"], array("trim", "int"))) {
                            $PROCESSED["organisation_id"] = $tmp_input;
                        } else {
                            add_error("No organisation id provided.");
                        }

                        if (!$ERROR) {
                            $data = array();

                            $form_element = Models_Assessments_Form_Element::fetchRowByID($PROCESSED["afelement_id"]);
                            if ($form_element) {
                                $data["afelement_objective"] = $form_element->getElementID();
                            }

                            $parent_objective = Models_Objective::fetchRow($PROCESSED["objective_id"]);
                            $objectives = Models_Objective::fetchAllByParentID($PROCESSED["organisation_id"], $PROCESSED["objective_id"], $active = 1);

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

                        if (isset($request["organisation_id"]) && $tmp_input = clean_input($request["organisation_id"], array("trim", "int"))) {
                            $PROCESSED["organisation_id"] = $tmp_input;
                        } else {
                            add_error("No organisation id provided.");
                        }

                        if (!$ERROR) {
                            $objective = Models_Objective::fetchRow($PROCESSED["objective_id"]);
                            if ($objective) {
                                $data["objective_parent"] = array("objective_parent_id" => $objective->getID(), "objective_parent_name" => $objective->getName());
                                $objectives = Models_Objective::fetchAllByParentID($PROCESSED["organisation_id"], $objective->getParent(), $active = 1);
                                foreach ($objectives as $objective) {
                                    $data["objectives"][] = array("objective_id" => $objective->getID(), "objective_name" => $objective->getName());
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
    } else {
        header("Location: ".ENTRADA_URL);
        exit;
    }
}
