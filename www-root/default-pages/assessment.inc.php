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
 * Module:    External Assessor Assessment (single form)
 * Area:    Default pages
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
 */

if (!defined("PARENT_INCLUDED")) exit;

// If the user is logged in, ensure they have proper permissions.
if ((isset($_SESSION["isAuthorized"]) && ($_SESSION["isAuthorized"]))) {
    $PROCESSED = array();

    if (isset($_POST["adistribution_id"]) && $tmp_input = clean_input($_POST["adistribution_id"], array("trim", "int"))) {
        $PROCESSED["adistribution_id"] = $tmp_input;
    }

    if (isset($_GET["aprogress_id"]) && $tmp_input = clean_input($_GET["aprogress_id"], array("trim", "int"))) {
        $PROCESSED["aprogress_id"] = $tmp_input;
    }

    if (isset($_GET["dassessment_id"]) && $tmp_input = clean_input($_GET["dassessment_id"], array("trim", "int"))) {
        $PROCESSED["dassessment_id"] = $tmp_input;
    }

    if (isset($_GET["adistribution_id"]) && $tmp_input = clean_input($_GET["adistribution_id"], array("trim", "int"))) {
        $PROCESSED["adistribution_id"] = $tmp_input;
    } else {
        add_error($translate->_("An error occurred while attempting to save responses for this target. Please try again later."));
    }

    if (isset($_GET["assessor_value"]) && $tmp_input = clean_input($_GET["assessor_value"], array("trim", "int"))) {
        $PROCESSED["assessor_value"] = $tmp_input;
    } else {
        add_error($translate->_("An error occurred while attempting to save responses for this target. Please try again later."));
    }

    if (isset($_GET["target_record_id"]) && $tmp_input = clean_input($_GET["target_record_id"], array("trim", "int"))) {
        $PROCESSED["target_record_id"] = $tmp_input;
    } elseif (isset($_POST["target_record_id"]) && $tmp_input = clean_input($_POST["target_record_id"], array("trim", "int"))) {
        $PROCESSED["target_record_id"] = $tmp_input;
    }

    if (isset($_GET["external_hash"]) && $tmp_input = clean_input($_GET["external_hash"], array("trim", "striptags"))) {
        $PROCESSED["external_hash"] = $tmp_input;
    } else {
        add_error($translate->_("An error occurred while attempting to save responses for this target. Please try again later."));
    }

    if (isset($_GET["from"]) && $tmp_input = clean_input($_GET["from"], array("trim", "striptags"))) {
        $PROCESSED["from"] = $tmp_input;
    }

    if (isset($PROCESSED["from"]) && $PROCESSED["from"] == "progress") {
        $url = ENTRADA_URL . "/assessment?adistribution_id=" . $PROCESSED["adistribution_id"] . "&target_record_id=" . $PROCESSED["target_record_id"] . "&dassessment_id=" . $PROCESSED["dassessment_id"] . "&assessor_value=" . $PROCESSED["assessor_value"] . (isset($PROCESSED["aprogress_id"]) && $PROCESSED["aprogress_id"] ? "&aprogress_id=" . $PROCESSED["aprogress_id"] : "") . "&external_hash=" . $PROCESSED["external_hash"] . "&from=progress";
        ?>
        <div class="alert alert-warning">
            <?php echo $translate->_("You are attempting to access an external assessment while logged in. If you wish to view or complete this assessment please right click <strong><a href=\"" . $url . "\">here</a></strong>, copy the URL and visit it while logged out."); ?>
        </div>
        <?php
    } else {
        // If the distribution id is set, they will be redirected to targets page for the distribution in the internal assessments module. Otherwise, they will be redirected to the assessments tasks page.
        $url = ENTRADA_URL . "/assessments";
        header("Location: $url");
    }
} else {

    $BREADCRUMB[] = array("url" => ENTRADA_URL . "/assessment", "title" => $translate->_("Assessment Task"));
    $JAVASCRIPT_TRANSLATIONS[] = "current_target = '" . $translate->_("Currently Assessing") . "';";
    $JQUERY[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/assessment-external.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $PROCESSED["objectives"] = array();

    $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = '" . ENTRADA_URL . "'</script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/assessments/rubrics.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/assessments/items.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/assessments/assessments.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/assessments/assessment-form.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";

    if (isset($_GET["aprogress_id"]) && $tmp_input = clean_input($_GET["aprogress_id"], array("trim", "int"))) {
        $PROCESSED["aprogress_id"] = $tmp_input;
    }

    if (isset($_GET["dassessment_id"]) && $tmp_input = clean_input($_GET["dassessment_id"], array("trim", "int"))) {
        $PROCESSED["dassessment_id"] = $tmp_input;

        if (isset($_GET["adistribution_id"]) && $tmp_input = clean_input($_GET["adistribution_id"], array("trim", "int"))) {
            $PROCESSED["adistribution_id"] = $tmp_input;
        } elseif (isset($_POST["adistribution_id"]) && $tmp_input = clean_input($_POST["adistribution_id"], array("trim", "int"))) {
            $PROCESSED["adistribution_id"] = $tmp_input;
        } else {
            add_error($translate->_("An error occurred while attempting to save responses for this target. Please try again later."));
        }

        if (isset($_GET["assessor_value"]) && $tmp_input = clean_input($_GET["assessor_value"], array("trim", "int"))) {
            $PROCESSED["assessor_value"] = $tmp_input;
        } elseif (isset($_POST["assessor_value"]) && $tmp_input = clean_input($_POST["assessor_value"], array("trim", "int"))) {
            $PROCESSED["assessor_value"] = $tmp_input;
        } else {
            add_error($translate->_("An error occurred while attempting to save responses for this target. Please try again later."));
        }

        if (isset($_GET["target_record_id"]) && $tmp_input = clean_input($_GET["target_record_id"], array("trim", "int"))) {
            $PROCESSED["target_record_id"] = $tmp_input;
        } elseif (isset($_POST["target_record_id"]) && $tmp_input = clean_input($_POST["target_record_id"], array("trim", "int"))) {
            $PROCESSED["target_record_id"] = $tmp_input;
        }

        if (isset($_GET["external_hash"]) && $tmp_input = clean_input($_GET["external_hash"], array("trim", "striptags"))) {
            $PROCESSED["external_hash"] = $tmp_input;
        } elseif (isset($_POST["external_hash"]) && $tmp_input = clean_input($_POST["external_hash"], array("trim", "striptags"))) {
            $PROCESSED["external_hash"] = $tmp_input;
        } else {
            add_error($translate->_("An error occurred while attempting to save responses for this target. Please try again later."));
        }

        $PROCESSED["proxy_id"] = $PROCESSED["assessor_value"];

        $distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($PROCESSED["adistribution_id"]);
        if ($distribution && $distribution->getDeletedDate()) {
            add_notice($translate->_("This assessment task's <strong>Distribution</strong> has been deleted and cannot be submitted."));
        }

        if (isset($PROCESSED["target_record_id"])) {
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
        } else {
            $distribution_target = Models_Assessments_Distribution_Target::fetchRowByDistributionID($PROCESSED["adistribution_id"]);
        }

        $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($PROCESSED["adistribution_id"]);
        $assessor_model = new Models_Assessments_Assessor();
        $assessment_record = $assessor_model->fetchRowByID($PROCESSED["dassessment_id"]);

        if ($assessment_record) {
            $assessment_overall_progress = $assessment_record->getOverallProgressDetails($PROCESSED["assessor_value"], true);
            $targets_pending = (isset($assessment_overall_progress["targets_pending"]) && $assessment_overall_progress["targets_pending"] ? $assessment_overall_progress["targets_pending"] : 0);
            $targets_inprogress = (isset($assessment_overall_progress["targets_inprogress"]) && $assessment_overall_progress["targets_inprogress"] ? $assessment_overall_progress["targets_inprogress"] : 0);
            $targets_complete = (isset($assessment_overall_progress["targets_complete"]) && $assessment_overall_progress["targets_complete"] ? $assessment_overall_progress["targets_complete"] : 0);
            $individual_attempts_max = (isset($assessment_overall_progress["max_individual_attempts"]) && $assessment_overall_progress["max_individual_attempts"] ? $assessment_overall_progress["max_individual_attempts"] : 0);
            $overall_attempts_max = (isset($assessment_overall_progress["max_overall_attempts"]) && $assessment_overall_progress["max_overall_attempts"] ? $assessment_overall_progress["max_overall_attempts"] : 0);
            $overall_attempts_completed = (isset($assessment_overall_progress["overall_attempts_completed"]) && $assessment_overall_progress["overall_attempts_completed"] ? $assessment_overall_progress["overall_attempts_completed"] : 0);

            $delegator = (isset($assessment_overall_progress["delegator"]) && $assessment_overall_progress["delegator"] ? $assessment_overall_progress["delegator"] : false);
            $targets = (isset($assessment_overall_progress["targets"]) && $assessment_overall_progress["targets"] ? $assessment_overall_progress["targets"] : 0);
        } else {
            add_error($translate->_("No assessment found for the given assessor and assessment id. It is possible that it has been deleted."));
        }

        switch ($STEP) {

            case 2 :

                if (isset($_POST["form_id"]) && $tmp_input = clean_input($_POST["form_id"], array("trim", "int"))) {
                    $PROCESSED["form_id"] = $tmp_input;
                } else {
                    add_error($translate->_("An error occurred while attempting to save responses for this target. Please try again later."));
                }

                if (!$ERROR) {
                    if (isset($distribution_target) && $distribution_target) {
                        if ($additional_flag) {
                            $adtarget_id = 0;
                        } else {
                            $adtarget_id = $distribution_target->getID();
                        }
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
                                $progress = Models_Assessments_Progress::fetchRowByAdistributionIDAssessorTypeAssessorValueTargetRecordIDDAssessmentID($PROCESSED["adistribution_id"], "external", $PROCESSED["assessor_value"], $PROCESSED["target_record_id"], $PROCESSED["dassessment_id"], "inprogress");
                            }
                            if (isset($progress) && $progress) {
                                $PROCESSED["aprogress_id"] = $progress->getID();
                                $method = "update";
                            }
                        }

                        if ($distribution) {
                            if ($distribution->getFeedbackRequired() && isset($ENTRADA_USER) && $ENTRADA_USER) {
                                if (isset($_POST["feedback_response"]) && $tmp_input = clean_input($_POST["feedback_response"], array("trim", "striptags"))) {
                                    $PROCESSED["feedback_response"] = $tmp_input;
                                } else {
                                    if (isset($_POST["submit_form"])) {
                                        add_error($translate->_("Please select a response for <strong>Did you have an opportunity to meet with this trainee to discuss their performance?</strong>"));
                                    }
                                }
                            }
                        }

                        if (isset($progress) && $progress && $progress->getProgressValue() === "complete") {
                            if (!$ERROR) {
                                if ($PROCESSED["assessor_value"] == $PROCESSED["target_record_id"]) {
                                    // Learner feedback
                                    $assessment_record = Models_Assessments_Assessor::fetchRowByID($PROCESSED["dassessment_id"]);
                                    if ($assessment_record) {
                                        $method = "insert";
                                        $PROCESSED["assessor_type"] = "external";
                                        $PROCESSED["assessor_value"] = $assessment_record->getAssessorValue();
                                        $PROCESSED["target_type"] = "internal";
                                        $PROCESSED["target_value"] = $PROCESSED["target_record_id"];
                                        $PROCESSED["target_feedback"] = ($PROCESSED["feedback_response"] == "yes" ? 1 : 0);
                                        $PROCESSED["target_progress_value"] = "inprogress";
                                        $PROCESSED["progress_value"] = "inprogress";

                                        if (isset($_POST["submit_form"])) {
                                            $PROCESSED["target_progress_value"] = "complete";
                                            $PROCESSED["progress_value"] = "complete";
                                        }

                                        $feedback_record = Models_Assessments_AssessorTargetFeedback::fetchRowByAssessorTarget($PROCESSED["dassessment_id"], "external", $PROCESSED["assessor_value"], "internal", $PROCESSED["target_record_id"]);
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
                                            add_error("A problem occurred while attempting to save your selected feedback option, please try again at a later time.");
                                        }
                                    }
                                } else {
                                    add_error($translate->_("You cannot update a submitted form."));
                                }
                            }
                        } else {
                            $PROCESSED["proxy_id"] = $PROCESSED["assessor_value"];
                            $PROCESSED["progress_value"] = "inprogress";
                            $PROCESSED["target_learning_context_id"] = NULL;
                            $PROCESSED["adtarget_id"] = $adtarget_id;
                            $PROCESSED["assessor_type"] = "external";

                            if ($method === "insert") {
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

                        if (!$ERROR && ($PROCESSED["assessor_value"] != $PROCESSED["target_record_id"]) || ($distribution_target) && $distribution_target->getTargetType() == "self") {
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
                                        $PROCESSED_RESPONSE["assessor_type"] = "external";
                                        $PROCESSED_RESPONSE["assessor_value"] = $PROCESSED["assessor_value"];
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
                                                            case "10" :
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
                                                                        $PROCESSED_RESPONSE["updated_by"] = $PROCESSED["assessor_value"];
                                                                    } else {
                                                                        $response = new Models_Assessments_Progress_Response($PROCESSED_RESPONSE);
                                                                        $PROCESSED_RESPONSE["created_date"] = time();
                                                                        $PROCESSED_RESPONSE["created_by"] = $PROCESSED["assessor_value"];
                                                                    }

                                                                    if (($response_method === "update" && $response->getComments() !== $PROCESSED_RESPONSE["comments"]) || ($response_method === "insert")) {
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
                                                                            $PROCESSED_RESPONSE["created_by"] = $PROCESSED["assessor_value"];

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
                                                                }
                                                                break;
                                                            case "7" :
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
                                                                        $PROCESSED_RESPONSE["updated_by"] = $PROCESSED["assessor_value"];
                                                                    } else {
                                                                        $response = new Models_Assessments_Progress_Response($PROCESSED_RESPONSE);
                                                                        $PROCESSED_RESPONSE["created_date"] = time();
                                                                        $PROCESSED_RESPONSE["created_by"] = $PROCESSED["assessor_value"];
                                                                    }

                                                                    if (($response_method === "update" && $response->getComments() !== $PROCESSED_RESPONSE["comments"]) || ($response_method === "insert")) {
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
                                                                        $PROCESSED_RESPONSE["updated_by"] = $PROCESSED["assessor_value"];
                                                                    } else {
                                                                        $response = new Models_Assessments_Progress_Response($PROCESSED_RESPONSE);
                                                                        $PROCESSED_RESPONSE["created_date"] = time();
                                                                        $PROCESSED_RESPONSE["created_by"] = $PROCESSED["assessor_value"];
                                                                    }

                                                                    if (($response_method === "update" && $response->getComments() !== $PROCESSED_RESPONSE["comments"]) || ($response_method === "insert")) {
                                                                        if (!$response->fromArray($PROCESSED_RESPONSE)->$response_method()) {
                                                                            add_error($translate->_("We were unable to save the form at this time. Please try again at a later time."));
                                                                            application_log("error", "A problem occurred while " . $method . "ing a progress response records DB said: " . $db->ErrorMsg());
                                                                        }
                                                                    }
                                                                }
                                                                break;
                                                        }
                                                    } else {
                                                        if (isset($_POST["submit_form"])) {
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
                                                            $PROCESSED_RESPONSE["updated_by"] = $PROCESSED["assessor_value"];
                                                        } else {
                                                            $response = new Models_Assessments_Progress_Response($PROCESSED_RESPONSE);
                                                            $PROCESSED_RESPONSE["created_date"] = time();
                                                            $PROCESSED_RESPONSE["created_by"] = $PROCESSED["assessor_value"];
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
                                        $PROCESSED["assessor_type"] = "external";
                                        $PROCESSED["assessor_feedback"] = ($PROCESSED["feedback_response"] == "yes" ? 1 : 0);
                                        $PROCESSED["target_type"] = "external";
                                        $PROCESSED["target_value"] = $PROCESSED["target_record_id"];

                                        $feedback_record = Models_Assessments_AssessorTargetFeedback::fetchRowByAssessorTarget($PROCESSED["dassessment_id"], "external", $PROCESSED["assessor_value"], "internal", $PROCESSED["target_record_id"]);
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
                                            add_error("A problem occurred while attempting to save your selected feedback option, please try again at a later time.");
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
                }

                if ($ERROR) {
                    $STEP = 1;
                } else {
                    $url = ENTRADA_URL . "/assessment?adistribution_id=" . $PROCESSED["adistribution_id"]
                        . (isset($schedule) && $schedule ? "&schedule_id=" . html_encode($schedule->getID()) : "")
                        . "&target_record_id=" . $PROCESSED["target_record_id"]
                        . "&aprogress_id=" . $PROCESSED["aprogress_id"]
                        . "&dassessment_id=" . $PROCESSED["dassessment_id"]
                        . "&assessor_value=" . $PROCESSED["assessor_value"]
                        . "&external_hash=" . $PROCESSED["external_hash"];

                    $msg = "Successfully " . ($PROCESSED["progress_value"] == "complete" ? "completed" : "saved") . " form, thank you. You will now be redirected to the completed Assessment page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"" . $url . "\" style=\"font-weight: bold\">click here</a> to continue.";
                    add_success($msg);
                    $ONLOAD[] = "setTimeout('window.location=\\'" . $url . "\\'', 5000)";
                }

                break;
            case 1 :

                if (!$ERROR) {

                    // Check to see if an assessment exists with the id provided.
                    $query = "  SELECT * FROM `cbl_distribution_assessments`
                                WHERE `dassessment_id` = ?
                                AND `assessor_value` = ?";
                    $assessment = $db->GetRow($query, array($PROCESSED["dassessment_id"], $PROCESSED["assessor_value"]));

                    if ($assessment) {

                        // Store distribution id and assessor id in PROCESSED
                        $PROCESSED["adistribution_id"] = $assessment["adistribution_id"];

                        // Ensure the external_hash belongs to an external assessor for the given assessment id.
                        if ($assessment["external_hash"] == $PROCESSED["external_hash"] && $assessment["assessor_type"] == "external") {

                            if ($distribution) {
                                $targets = Models_Assessments_Distribution_Target::getAssessmentTargets($distribution->getID(), $PROCESSED["dassessment_id"], null, $PROCESSED["assessor_value"], true);
                                $delegator = Models_Assessments_Distribution_Delegator::fetchRowByDistributionID($PROCESSED["adistribution_id"]);

                                if ($delegator) {
                                    $targets = Models_Assessments_Distribution_Target::getAssessmentTargets($distribution->getID(), $PROCESSED["dassessment_id"], $PROCESSED["assessor_value"], $PROCESSED["assessor_value"]);
                                }

                                $valid_target = false;

                                if ($targets) {
                                    // If a target was specified, ensure the specified id is a valid target for this assessment.
                                    if (isset($PROCESSED["target_record_id"]) && $PROCESSED["target_record_id"]) {
                                        foreach ($targets as $target) {
                                            if ($target["target_record_id"] == $PROCESSED["target_record_id"]) {
                                                $valid_target = true;
                                            }
                                        }
                                    } else {
                                        // No target id specified, go to first target
                                        if ($targets[0]) {
                                            $PROCESSED["target_record_id"] = $targets[0]["target_record_id"];
                                            $valid_target = true;
                                        }
                                    }

                                    if (!$valid_target) {
                                        add_error($translate->_("No target with specified id found for this assessment."));
                                    }
                                } else {
                                    add_error($translate->_("No targets were found for this assessment."));
                                }

                            } else {
                                add_error($translate->_("No distribution found for this assessment."));
                            }
                        } else {
                            add_error($translate->_("No assessment could be found for an external assessor with the given distribution id."));
                        }

                        if (!$ERROR) {
                            if (isset($PROCESSED["aprogress_id"]) && $PROCESSED["aprogress_id"]) {
                                $progress_record = Models_Assessments_Progress::fetchRowByID($PROCESSED["aprogress_id"]);
                            } else {
                                $progress_record = Models_Assessments_Progress::fetchRowByAdistributionIDAssessorTypeAssessorValueTargetRecordIDDAssessmentID($PROCESSED["adistribution_id"], "external", $PROCESSED["assessor_value"], $PROCESSED["target_record_id"], $PROCESSED["dassessment_id"]);
                            }
                            if ($progress_record) {
                                $PROCESSED["aprogress_id"] = $progress_record->getID();
                                $responses = Models_Assessments_Progress_Response::fetchAllByAprogressID($progress_record->getID());
                                if ($responses) {
                                    foreach ($responses as $response) {
                                        $form_element = Models_Assessments_Form_Element::fetchRowByID($response->getAfelementID());
                                        if ($form_element) {
                                            switch ($form_element->getElementType()) {
                                                case "objective" :
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
                                                    break;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        add_error($translate->_("No assessor found with the given id."));
                    }
                }
                break;
        }
    }

    switch ($STEP) {
        case 2:
            echo display_status_messages();
            break;
        case 1:
            echo display_status_messages();
        default:
            if (isset($PROCESSED["adistribution_id"]) && isset($PROCESSED["assessor_value"])) {

                // If the valid_target flag was set to false when validating the URL in step one, we must check it to
                // ensure that we do not display the form to prevent an invalid target from being completed.
                if (isset($valid_target)) {
                    $continue = $valid_target;
                } else {
                    $continue = true;
                }

                if ($continue && $assessment_record) {
                    if ($distribution) {

                        $deleted_task = Models_Assessments_DeletedTask::fetchRowByADistributionIDAssessorTypeAssessorValueTargetIDDeliveryDate($distribution->getID(), "external", $PROCESSED["assessor_value"], (isset($PROCESSED["target_record_id"]) && $PROCESSED["target_record_id"] ? $PROCESSED["target_record_id"] : $distribution_target->getTargetId()), $assessment_record->getDeliveryDate());
                        if ($deleted_task) {
                            $deleted_by_user = Models_User::fetchRowByID($deleted_task->getCreatedBy());
                            if ($deleted_by_user) {
                                add_error($translate->_("This assessment task has been deleted by " . $deleted_by_user->getFullname(false) . " (<a href=\"mailto:" . $deleted_by_user->getEmail() . "\" target=\"_top\">" . $deleted_by_user->getEmail() . "</a>). Please contact the provided email address if you require more information."));
                            } else {
                                add_error($translate->_("This assessment task has been deleted."));
                            }
                            display_status_messages();
                        } else {

                            if (isset($PROCESSED["aprogress_id"]) && $PROCESSED["aprogress_id"]) {
                                $progress_record = Models_Assessments_Progress::fetchRowByID($PROCESSED["aprogress_id"]);
                            } else {
                                $progress_record = Models_Assessments_Progress::fetchRowByDassessmentIDAssessorTypeAssessorValueTargetRecordID($PROCESSED["dassessment_id"], "external", $PROCESSED["assessor_value"], $PROCESSED["target_record_id"], "inprogress");
                                if ($progress_record) {
                                    $PROCESSED["aprogress_id"] = $progress_record->getID();
                                }
                            }

                            if (!isset($PROCESSED["target_record_id"]) || !$PROCESSED["target_record_id"]) {
                                $PROCESSED["target_record_id"] = $progress_record->getTargetRecordID();
                            }

                            $distribution_target = Models_Assessments_Distribution_Target::fetchRowByDistributionID($PROCESSED["adistribution_id"]);
                            $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($PROCESSED["adistribution_id"]);

                            $form = Models_Assessments_Form::fetchRowByID($distribution->getFormID());

                            $distribution_data = array(
                                "adistribution_id" => $PROCESSED["adistribution_id"],
                                "dassessment_id" => $PROCESSED["dassessment_id"],
                                "target_record_id" => $PROCESSED["target_record_id"],
                                "assessor_value" => $PROCESSED["assessor_value"],
                                "form_id" => $form->getID(),
                                "aprogress_id" => (isset($PROCESSED["aprogress_id"]) && $PROCESSED["aprogress_id"] ? $PROCESSED["aprogress_id"] : 0),
                                "deleted_date" => null
                            );

                            if ($distribution_schedule) {
                                $schedule = Models_Schedule::fetchRowByIDScheduleType($distribution_schedule->getScheduleID(), "rotation_block");

                                if (isset($schedule) && $schedule) {
                                    $rotation_name = "N/A";
                                    if ($schedule->getScheduleType() == "rotation_block") {
                                        $schedule_parent = Models_Schedule::fetchRowByID($schedule->getScheduleParentID());
                                        $rotation_name = $schedule_parent->getTitle();
                                    } else {
                                        $rotation_name = $schedule->getTitle();
                                    }
                                }
                            }

                            /*
                            $assessment_record = Models_Assessments_Assessor::fetchRowByID($PROCESSED["dassessment_id"]);
                            $assessment_overall_progress = $assessment_record->getOverallProgressDetails($PROCESSED["assessor_value"], true);

                            $targets_pending = (isset($assessment_overall_progress["targets_pending"]) && $assessment_overall_progress["targets_pending"] ? $assessment_overall_progress["targets_pending"] : 0);
                            $targets_inprogress = (isset($assessment_overall_progress["targets_inprogress"]) && $assessment_overall_progress["targets_inprogress"] ? $assessment_overall_progress["targets_inprogress"] : 0);
                            $targets_complete = (isset($assessment_overall_progress["targets_complete"]) && $assessment_overall_progress["targets_complete"] ? $assessment_overall_progress["targets_complete"] : 0);
                            $individual_attempts_max = (isset($assessment_overall_progress["max_individual_attempts"]) && $assessment_overall_progress["max_individual_attempts"] ? $assessment_overall_progress["max_individual_attempts"] : 0);
                            $overall_attempts_max = (isset($assessment_overall_progress["max_overall_attempts"]) && $assessment_overall_progress["max_overall_attempts"] ? $assessment_overall_progress["max_overall_attempts"] : 0);
                            $overall_attempts_completed = (isset($assessment_overall_progress["overall_attempts_completed"]) && $assessment_overall_progress["overall_attempts_completed"] ? $assessment_overall_progress["overall_attempts_completed"] : 0);

                            $delegator = (isset($assessment_overall_progress["delegator"]) && $assessment_overall_progress["delegator"] ? $assessment_overall_progress["delegator"] : false);

                            $targets = (isset($assessment_overall_progress["targets"]) && $assessment_overall_progress["targets"] ? $assessment_overall_progress["targets"] : 0);
                            */

                            if ($targets) {
                                switch ($distribution_target->getTargetType()) {
                                    case "schedule_id" :
                                        switch ($distribution_target->getTargetScope()) {
                                            case "self" :
                                                $target = Models_Schedule::fetchRowByID($PROCESSED["target_record_id"]);
                                                if ($target) {
                                                    $sidebar_html = "<h4 class=\"course-target-heading\">" . $translate->_("Currently Assessing:") . "</h4>";
                                                    $sidebar_html .= "<div class=\"user-metadata\">";
                                                    $sidebar_html .= "  <p class=\"course-name\">" . html_encode($target->getTitle()) . "</p>";
                                                    $organisation = Organisation::get($target->getOrganisationID());
                                                    if ($organisation) {
                                                        $sidebar_html .= "  <p class=\"course-organisation\">" . html_encode($organisation->getTitle()) . "</p>";
                                                    }
                                                    $sidebar_html .= "</div>";
                                                    assessment_sidebar_item($sidebar_html, $id = "assessment-sidebar", $state = "open", $position = SIDEBAR_PREPEND);
                                                }
                                                break;
                                            default:
                                                $target_user = User::fetchRowByID($PROCESSED["target_record_id"]);
                                                if ($target_user) {
                                                    $organisation = Organisation::get($target_user->getOrganisationId());
                                                    //$user_photo_details = Entrada_Utilities::fetchUserPhotoDetails($target_user->getID(), $target_user->getPrivacyLevel());
                                                    // $user_photo_details commented out for now bc of an ACL check that happens in Entrada_Utilities::fetchUserPhotoDetails when no instance of $ENTRADA_ACL is available
                                                    $user_photo_details = false;
                                                    $sidebar_html = "<div class=\"user-image\">";
                                                    $sidebar_html .= "  <img src=\"" . ($user_photo_details && isset($user_photo_details["default_photo"]) && isset($user_photo_details[$user_photo_details["default_photo"] . "_url"]) ? $user_photo_details[$user_photo_details["default_photo"] . "_url"] : ENTRADA_URL . "/images/headshot-male.gif") . "\" class=\"img-circle\" />";
                                                    $sidebar_html .= "</div>";
                                                    $sidebar_html .= "<div class=\"user-metadata\">";
                                                    $sidebar_html .= "  <p class=\"user-fullname\">" . html_encode($target_user->getFullname(false)) . "</p>";
                                                    $sidebar_html .= "  <p class=\"user-organisation\">" . html_encode(ucfirst($target_user->getGroup())) . ($organisation ? " <span>&bull;</span> " . html_encode($organisation->getTitle()) : "") . "</p>";
                                                    $sidebar_html .= "  <a class=\"user-email no-space-below\" href=\"#\">" . html_encode($target_user->getEmail()) . "</a>";
                                                    $target_progress_records = Models_Assessments_Progress::fetchAllByDassessmentIDAssessorTypeAssessorValueTargetRecordID($PROCESSED["dassessment_id"], "external", $PROCESSED["assessor_value"], $target_user->getID());
                                                    if ($target_progress_records && @count($target_progress_records) > 1) {
                                                        $sidebar_html .= "<h3 class=\"no-space-below no-space-above\">Attempts</h3>\n";
                                                        $sidebar_html .= "<ul class=\"menu none\">\n";
                                                        $inprogress_shown = false;
                                                        $complete_records = 0;
                                                        foreach ($target_progress_records as $target_progress_record) {
                                                            if ($target_progress_record->getProgressValue() == "complete") {
                                                                $complete_records++;
                                                                $sidebar_html .= ($progress_record && $target_progress_record->getID() == $progress_record->getID() ? "<strong>" : "") . "<li><a href=\"" . ENTRADA_URL . "/assessment?" . replace_query(array("aprogress_id" => $target_progress_record->getID())) . "\">" . sprintf($translate->_("Completed %s"), date("M d/y h:ia", $target_progress_record->getUpdatedDate())) . "</a></li>" . ($progress_record && $target_progress_record->getID() == $progress_record->getID() ? "</strong>" : "");
                                                            }
                                                            if ($target_progress_record->getProgressValue() == "inprogress") {
                                                                $sidebar_html .= ($progress_record && $target_progress_record->getID() == $progress_record->getID() ? "<strong>" : "") . "<li><a href=\"" . ENTRADA_URL . "/assessment?" . replace_query(array("aprogress_id" => $target_progress_record->getID())) . "\">" . sprintf($translate->_("Started %s"), date("M d/y h:ia", $target_progress_record->getCreatedDate())) . "</a></li>" . ($progress_record && $target_progress_record->getID() == $progress_record->getID() ? "</strong>" : "");
                                                                $inprogress_shown = true;
                                                            }
                                                        }
                                                        if (!$inprogress_shown && $individual_attempts_max > $complete_records && $progress_record) {
                                                            $sidebar_html .= "<li><a href=\"" . ENTRADA_URL . "/assessment?" . replace_query(array("aprogress_id" => NULL)) . "\">" . $translate->_("Begin new attempt") . "</a></li>";
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
                                                                    $url = ENTRADA_URL . "/assessment?adistribution_id=" . html_encode($distribution->getID()) . "&schedule_id=" . (isset($schedule) && $schedule ? html_encode($schedule->getID()) : "") . "&target_record_id=" . html_encode($target["target_record_id"]) . "&dassessment_id=" . html_encode($PROCESSED["dassessment_id"]) . "&assessor_value=" . html_encode($PROCESSED["assessor_value"]) . "&external_hash=" . html_encode($PROCESSED["external_hash"]);
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
                                                                    $url = ENTRADA_URL . "/assessment?adistribution_id=" . html_encode($distribution->getID()) . "&schedule_id=" . (isset($schedule) && $schedule ? html_encode($schedule->getID()) : "") . "&target_record_id=" . html_encode($target["target_record_id"]) . (array_key_exists("aprogress_id", $target) ? "&aprogress_id=" . html_encode($target["aprogress_id"]) : "") . "&dassessment_id=" . html_encode($PROCESSED["dassessment_id"]) . "&assessor_value=" . html_encode($PROCESSED["assessor_value"]) . "&external_hash=" . html_encode($PROCESSED["external_hash"]);
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
                                                                    $url = ENTRADA_URL . "/assessment?adistribution_id=" . html_encode($distribution->getID()) . "&schedule_id=" . (isset($schedule) && $schedule ? html_encode($schedule->getID()) : "") . "&target_record_id=" . html_encode($target["target_record_id"]) . (array_key_exists("aprogress_id", $target) ? "&aprogress_id=" . html_encode($target["aprogress_id"]) : "") . "&dassessment_id=" . html_encode($PROCESSED["dassessment_id"]) . "&assessor_value=" . html_encode($PROCESSED["assessor_value"]) . "&external_hash=" . html_encode($PROCESSED["external_hash"]);
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
                                        if ($target_user) {
                                            $organisation = Organisation::get($target_user->getOrganisationId());
                                            //$user_photo_details = Entrada_Utilities::fetchUserPhotoDetails($target_user->getID(), $target_user->getPrivacyLevel());
                                            // $user_photo_details commented out for now bc of an ACL check that happens in Entrada_Utilities::fetchUserPhotoDetails when no instance of $ENTRADA_ACL is available
                                            $user_photo_details = false;
                                            $sidebar_html = "<div class=\"user-image\">";
                                            $sidebar_html .= "  <img src=\"" . ($user_photo_details && isset($user_photo_details["default_photo"]) && isset($user_photo_details[$user_photo_details["default_photo"] . "_url"]) ? $user_photo_details[$user_photo_details["default_photo"] . "_url"] : ENTRADA_URL . "/images/headshot-male.gif") . "\" class=\"img-circle\" />";
                                            $sidebar_html .= "</div>";
                                            $sidebar_html .= "<div class=\"user-metadata\">";
                                            $sidebar_html .= "  <p class=\"user-fullname\">" . html_encode($target_user->getFullname(false)) . "</p>";
                                            $sidebar_html .= "  <p class=\"user-organisation\">" . html_encode(ucfirst($target_user->getGroup())) . ($organisation ? " <span>&bull;</span> " . html_encode($organisation->getTitle()) : "") . "</p>";
                                            $sidebar_html .= "  <a class=\"user-email no-space-below\" href=\"#\">" . html_encode($target_user->getEmail()) . "</a>";
                                            $target_progress_records = Models_Assessments_Progress::fetchAllByDassessmentIDAssessorTypeAssessorValueTargetRecordID($PROCESSED["dassessment_id"], "external", $PROCESSED["assessor_value"], $target_user->getID());
                                            if ($target_progress_records && @count($target_progress_records) >= 1) {
                                                $sidebar_html .= "<h3 class=\"no-space-below no-space-above\">Attempts</h3>\n";
                                                $sidebar_html .= "<ul class=\"menu none\">\n";
                                                $inprogress_shown = false;
                                                $complete_records = 0;
                                                foreach ($target_progress_records as $target_progress_record) {
                                                    if ($target_progress_record->getProgressValue() == "complete") {
                                                        $complete_records++;
                                                        $sidebar_html .= ($progress_record && $target_progress_record->getID() == $progress_record->getID() ? "<strong>" : "") . "<li><a href=\"" . ENTRADA_URL . "/assessment?" . replace_query(array("aprogress_id" => $target_progress_record->getID())) . "&assessor_value=" . html_encode($PROCESSED["assessor_value"]) . "&external_hash=" . html_encode($PROCESSED["external_hash"]) . "\">" . sprintf($translate->_("Completed %s"), date("M d/y h:ia", $target_progress_record->getUpdatedDate())) . "</a></li>" . ($progress_record && $target_progress_record->getID() == $progress_record->getID() ? "</strong>" : "");
                                                    }
                                                    if ($target_progress_record->getProgressValue() == "inprogress") {
                                                        $sidebar_html .= ($progress_record && $target_progress_record->getID() == $progress_record->getID() ? "<strong>" : "") . "<li><a href=\"" . ENTRADA_URL . "/assessment?" . replace_query(array("aprogress_id" => $target_progress_record->getID())) . "&assessor_value=" . html_encode($PROCESSED["assessor_value"]) . "&external_hash=" . html_encode($PROCESSED["external_hash"]) . "\">" . sprintf($translate->_("Started %s"), date("M d/y h:ia", $target_progress_record->getCreatedDate())) . "</a></li>" . ($progress_record && $target_progress_record->getID() == $progress_record->getID() ? "</strong>" : "");
                                                        $inprogress_shown = true;
                                                    }
                                                }
                                                if (!$inprogress_shown && $individual_attempts_max > $complete_records && $progress_record) {
                                                    $sidebar_html .= "<li><a href=\"" . ENTRADA_URL . "/assessment?" . replace_query(array("aprogress_id" => NULL)) . "\">" . $translate->_("Begin new attempt") . "</a></li>";
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
                                                            $url = ENTRADA_URL . "/assessment?adistribution_id=" . html_encode($distribution->getID()) . "&schedule_id=" . (isset($schedule) && $schedule ? html_encode($schedule->getID()) : "") . "&target_record_id=" . html_encode($target["target_record_id"]) . "&dassessment_id=" . html_encode($PROCESSED["dassessment_id"]) . "&assessor_value=" . html_encode($PROCESSED["assessor_value"]) . "&external_hash=" . html_encode($PROCESSED["external_hash"]);
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
                                                            $url = ENTRADA_URL . "/assessment?adistribution_id=" . html_encode($distribution->getID()) . "&schedule_id=" . (isset($schedule) && $schedule ? html_encode($schedule->getID()) : "") . "&target_record_id=" . html_encode($target["target_record_id"]) . (array_key_exists("aprogress_id", $target) ? "&aprogress_id=" . html_encode($target["aprogress_id"]) : "") . "&dassessment_id=" . html_encode($PROCESSED["dassessment_id"]) . "&assessor_value=" . html_encode($PROCESSED["assessor_value"]) . "&external_hash=" . html_encode($PROCESSED["external_hash"]);
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
                                                            $url = ENTRADA_URL . "/assessment?adistribution_id=" . html_encode($distribution->getID()) . "&schedule_id=" . (isset($schedule) && $schedule ? html_encode($schedule->getID()) : "") . "&target_record_id=" . html_encode($target["target_record_id"]) . (array_key_exists("aprogress_id", $target) ? "&aprogress_id=" . html_encode($target["aprogress_id"]) : "") . "&dassessment_id=" . html_encode($PROCESSED["dassessment_id"]) . "&assessor_value=" . html_encode($PROCESSED["assessor_value"]) . "&external_hash=" . html_encode($PROCESSED["external_hash"]);
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
                                                    $sidebar_html = "<h4 class=\"course-target-heading\">" . $translate->_("Currently Assessing:") . "</h4>";
                                                    $sidebar_html .= "<div class=\"user-metadata\">";
                                                    $sidebar_html .= "  <p class=\"course-name\">" . html_encode($target->getCourseName()) . "</p>";
                                                    $organisation = Organisation::get($target->getOrganisationID());
                                                    if ($organisation) {
                                                        $sidebar_html .= "  <p class=\"course-organisation\">" . html_encode($organisation->getTitle()) . "</p>";
                                                    }
                                                    $sidebar_html .= "</div>";
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

                                        $target_count = count($targets);
                                        $target_progress_records = Models_Assessments_Progress::fetchAllByDassessmentIDAssessorTypeAssessorValueTargetRecordID($PROCESSED["dassessment_id"], "external", $assessment_record->getAssessorValue(), $PROCESSED["target_record_id"]);
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
                                                "target_name" => $learning_event_data["target_type"] == "event" ? $learning_event_data["event_name"] : $learning_event_data["user_name"],
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
                            }

                            ?>
                            <div id="msgs"></div>
                            <h1 id="form-heading"><?php echo $form->getTitle(); ?></h1>
                            <?php
                            if ($targets && count($targets) > 1) {
                                echo "<p id=\"targets_remaining\" class=\"muted\">" . sprintf($translate->_("You have <strong>%s</strong> assessments to complete with this form."), (count($targets_inprogress) + count($targets_pending))) . "</p>";
                            } ?>
                            <form id="assessment-form" action="<?php echo ENTRADA_URL . "/assessment?" . replace_query(array("step" => 2)); ?>" method="post">
                                <input type="hidden" id="form_id" name="form_id" value="<?php echo html_encode($form->getID()); ?>"/>
                                <input type="hidden" id="dassessment_id" name="dassessment_id" value="<?php echo html_encode($PROCESSED["dassessment_id"]); ?>"/>
                                <input type="hidden" id="adistribution_id" name="adistribution_id" value="<?php echo html_encode($PROCESSED["adistribution_id"]); ?>"/>
                                <input type="hidden" id="assessor_value" name="assessor_value" value="<?php echo html_encode($PROCESSED["assessor_value"]); ?>"/>
                                <input type="hidden" id="aprogress_id" name="aprogress_id" value="<?php echo html_encode(@$PROCESSED["aprogress_id"]); ?>"/>
                                <input type="hidden" id="organisation_id" name="organisation_id" value="<?php echo html_encode($distribution->getOrganisationID()); ?>"/>
                                <input type="hidden" id="external_hash" name="external_hash" value="<?php echo html_encode($PROCESSED["external_hash"]); ?>"/>
                                <input type="hidden" id="schedule_id" name="schedule_id" value="<?php echo($distribution_schedule ? html_encode($distribution_schedule->getScheduleID()) : ""); ?>"/>
                                <input type="hidden" id="target_record_id" name="target_record_id" value="<?php echo html_encode($PROCESSED["target_record_id"]); ?>"/>
                                <?php
                                echo "<div class=\"row-fluid\">";
                                if ($progress_record && $progress_record->getProgressValue() == "complete") {
                                    $creator = User::fetchRowByID((isset($delegator) && $delegator && $delegator->getDelegatorType() == "proxy_id" && $delegator->getDelegatorID() ? $delegator->getDelegatorID() : $distribution->getCreatedBy()));
                                    if ($creator) {
                                        $msg = sprintf("Thank you for completing this <strong>Assessment</strong>. If you need to make changes, please contact <strong>%s %s (<a class=\"user-email no-space-below\" href=\"#\">%s</a>)</strong>.", html_encode($creator->getFirstname()), html_encode($creator->getLastname()), html_encode($creator->getEmail()));
                                    } else {
                                        $msg = "Thank you for completing this <strong>Assessment</strong>. If you need to make changes, please contact the <strong>Program Administrator</strong> who created the assessment.";
                                    }
                                    ?>
                                    <div class="alert alert-success"><?php echo $translate->_($msg) ?></div>
                                <?php } ?>
                                <div class="row-fluid">
                                    <?php

                                    // Instantiate utility object, with optional parameters.
                                    $form_api = new Entrada_Assessments_Forms(array(
                                            "form_id" => $form->getID(),
                                            "adistribution_id" => @$PROCESSED["adistribution_id"] ? $PROCESSED["adistribution_id"] : null,
                                            "aprogress_id" => @$distribution_data["aprogress_id"] ? @$distribution_data["aprogress_id"] : null,
                                            "dassessment_id" => @$PROCESSED["dassessment_id"] ? $PROCESSED["dassessment_id"] : null,
                                        )
                                    );

                                    // Fetch form data using those params
                                    $disabled = false;
                                    $form_data = $form_api->fetchFormData();
                                    if ($progress_record && $progress_record->getProgressValue() == "complete") {
                                        $disabled = true;
                                    }

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

                                    if (isset($PROCESSED["objectives"]) && !empty($PROCESSED["objectives"])) {
                                        foreach ($PROCESSED["objectives"] as $afelement_id => $objectives) {
                                            foreach ($objectives as $objective) {
                                                echo "<input type=\"hidden\" name=\"afelement_objectives[" . html_encode($afelement_id) . "][]\" value=\"" . html_encode($objective) . "\" class=\"afelement-objective-" . html_encode($afelement_id) . "\" />";
                                            }
                                        }
                                    }
                                    if ((!$progress_record || $progress_record && $progress_record->getProgressValue() != "complete") && !$distribution->getDeletedDate()) { ?>
                                        <div class="row-fluid">
                                            <div class="pull-right">
                                                <input type="submit" id="save-form" class="btn btn-warning" name="save_form_progress" value="<?php echo $translate->_("Save as Draft"); ?>"/>
                                                <span class="or">or</span>
                                                <input class="btn btn-primary" type="submit" id="submit_form" name="submit_form" value="<?php echo $translate->_("Submit"); ?>"/>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </form>
                            <?php
                        }
                    } else { ?>
                        <div class="alert alert-danger"><?php echo $translate->_("No distribution found for this assessment.") ?></div>
                        <?php
                    }
                }
            }

            break;
    }
}
