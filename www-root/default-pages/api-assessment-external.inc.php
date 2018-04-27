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

ob_clear_open_buffers();

// If the user is logged in, redirect them.
if ((isset($_SESSION["isAuthorized"]) && ($_SESSION["isAuthorized"]))) {
    $url = ENTRADA_URL;
    header("Location: $url");
    exit();
}

$request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));
$request = ${"_" . $request_method};

if (!$request_method) {
    add_error("Unable to determine request method.");
    echo json_encode(array("status" => "error", "data" => $ERRORSTR));
    exit();
}
if (!array_key_exists("method", $request)) {
    add_error("Invalid API method.");
    echo json_encode(array("status" => "error", "data" => $ERRORSTR));
    exit();
}

/**
 * Validate common parameters
 **/

$PROCESSED["target_record_id"] = null;
if (isset($request["target_record_id"]) && $tmp_input = clean_input($request["target_record_id"], array("trim", "int"))) {
    $PROCESSED["target_record_id"] = $tmp_input;
}

$PROCESSED["target_type"] = null;
if (isset($request["target_type"]) && $tmp_input = clean_input($request["target_type"], array("trim", "notags"))) {
    $PROCESSED["target_type"] = $tmp_input;
}

$PROCESSED["target_scope"] = null;
if (isset($request["target_scope"]) && $tmp_input = clean_input($request["target_scope"], array("trim", "notags"))) {
    $PROCESSED["target_scope"] = $tmp_input;
}

$PROCESSED["assessor_id"] = null; // Must be derived from the assessment ID/hash combo
$PROCESSED["assessor_type"] = "external";

$PROCESSED["external_hash"] = null;
if (isset($request["external_hash"]) && $tmp_input = clean_input($request["external_hash"], array("trim", "alphanumeric"))) {
    $PROCESSED["external_hash"] = $tmp_input;
}

$PROCESSED["dassessment_id"] = null;
if (isset($request["dassessment_id"]) && $tmp_input = clean_input($request["dassessment_id"], array("trim", "int"))) {
    $PROCESSED["dassessment_id"] = $tmp_input;
}

$PROCESSED["aprogress_id"] = null;
if (isset($request["aprogress_id"]) && $tmp_input = clean_input($request["aprogress_id"], array("trim", "int"))) {
    $PROCESSED["aprogress_id"] = $tmp_input;
}

if (Entrada_Assessments_Assessment::validateExternalHash($PROCESSED["dassessment_id"], $PROCESSED["external_hash"])) {
    $PROCESSED["hash_key_valid"] = true;
} else {
    $PROCESSED["hash_key_valid"] = false;
}

/**
 * Validate the external hash against the dassessment ID.
 **/

$assessment_api = new Entrada_Assessments_Assessment(
    array(
        "dassessment_id" => $PROCESSED["dassessment_id"],
        "external_hash" => $PROCESSED["external_hash"],
    )
);

/**
 * Check posted API command
 **/

if ($request_method == "POST") {

    if (!$PROCESSED["hash_key_valid"]) {
        add_error("The specified access key is invalid.");
        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
        exit();
    }

    switch ($request["method"]) {

        case "create-new-progress":

            if (!$PROCESSED["target_record_id"] || !$PROCESSED["target_type"]) {
                add_error($translate->_("An error occurred while attempting to save responses for this target. Please try again later"));
            }
            if (!$ERROR) {
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
                    echo json_encode(array("status" => "success", "data" => array("redirect_url" => $assessment_api->getAssessmentURL($PROCESSED["target_record_id"], $PROCESSED["target_type"], true))));
                } else {
                    echo json_encode(array("status" => "error", "data" => array($translate->_("Error retrieving assessment data."))));
                }
            } else {
                echo json_encode(array("status" => "error", "data" => $ERRORSTR));
            }
            break;

        case "save-responses":

            if (!$PROCESSED["target_record_id"] || !$PROCESSED["target_type"]) {
                add_error($translate->_("A problem occurred while attempting to save your selected responses, please try again at a later time."));
            }
            $progress_id = null;
            if (!$ERROR) {
                if ($PROCESSED["aprogress_id"]) {
                    // We have a progress ID already, so use it.
                    $progress_id = $PROCESSED["aprogress_id"];
                }
                $assessment_api->setDatasetLimit(array("assessment"));
                $assessment_api->setAprogressID($progress_id);

                // We pass the posted data directly to updateProgressResponses; it will perform the required validation.
                if (!$assessment_api->updateProgressResponses($_POST, $PROCESSED["target_record_id"], $PROCESSED["target_type"])) {
                    foreach ($assessment_api->getErrorMessages() as $error_message) {
                        add_error($error_message);
                    }
                }
                $progress_id = $assessment_api->getAprogressID();
            }
            if (!$ERROR) {
                echo json_encode(array("status" => "success", "data" => array("saved" => date("g:i:sa", time()), "aprogress_id" => $progress_id)));
            } else {
                echo json_encode(array("status" => "error", "data" => $ERRORSTR));
            }
            break;

        case "change-target" :

            if (!$PROCESSED["target_record_id"] || !$PROCESSED["target_type"]) {
                add_error($translate->_("No new target provided."));
            }
            if (!$PROCESSED["aprogress_id"]) {
                add_error($translate->_("There are no responses to move."));
            }
            if (!$ERROR) {
                $assessment_api->setDatasetLimit(array("progress", "targets"));
                $assessment_api->setAprogressID($PROCESSED["aprogress_id"]);
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
                echo json_encode(array("status" => "success", "data" => array("redirect_url" => $assessment_api->getAssessmentURL($new_progress["target_record_id"], $new_progress["target_type"], true))));
            }
            break;

        case "save-feedback" :

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
            $assessment_api->setDatasetLimit(array("assessor"));
            $assessor = $assessment_api->getAssessor();
            if (!$ERROR) {
                $saved = $assessment_api->updateAssessorFeedback(
                    $assessor["assessor_id"],
                    $assessor["type"], // will be "external"
                    $PROCESSED["target_record_id"],
                    $PROCESSED["target_scope"],
                    ($PROCESSED["assessor_feedback_response"] == "yes" ? 1 : 0),
                    false,
                    false,
                    $PROCESSED["target_type"]
                );
                if (!$saved) {
                    add_error($translate->_("A problem occurred while attempting to save your selected feedback option, please try again at a later time."));
                }
            }
            if ($ERROR) {
                echo json_encode(array("status" => "error", "data" => $ERRORSTR));
            } else {
                echo json_encode(array("status" => "success"));
            }
            break;

        default:
            echo json_encode(array("status" => "error", "data" => array($translate->_("Unknown API method."))));
            break;
    }

} else if ($request_method == "GET") {

    switch ($request["method"]) {

        case "get-objectives":
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

        case "get-parent-objectives":
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

        case "get-competency-items":
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

        default:
            echo json_encode(array("status" => "error", "data" => array($translate->_("Unknown API method."))));
            break;
    }

} else {
    // Invalid method
    echo json_encode(array("status" => "error", "data" => array($translate->_("Invalid specified API request."))));
}
exit();