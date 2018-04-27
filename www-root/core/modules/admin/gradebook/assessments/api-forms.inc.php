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
 * API to handle interaction with form components
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>, Adrian Mellognio <adrian.mellogno@queensu.ca>
 * @copyright Copyright 2014, 2016 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_ASSESSMENTS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("gradebook", "update", false) &&
    !(isset($_GET["assessment_id"]) && isset($_GET["proxy_id"]) && ($assessment_id = clean_input($_GET["assessment_id"], array("trim", "int"))) && ($proxy_id = clean_input($_GET["proxy_id"], array("trim", "int"))) && Models_Gradebook_Assessment_Graders::canGradeAssessment($proxy_id, $assessment_id))) {
    
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

    ob_clear_open_buffers();

    $forms_api = new Entrada_Assessments_Forms(array("actor_proxy_id" => $ENTRADA_USER->getActiveId(), "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()));

    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));
	
	$request = ${"_" . $request_method};
	
    if ($ENTRADA_USER->getActiveRole() == "admin") {
        if (isset($request["proxy_id"]) && $tmp_input = clean_input($request["proxy_id"], "int")) {
            $PROCESSED["proxy_id"] = $tmp_input;
        } else {
            $PROCESSED["proxy_id"] = $ENTRADA_USER->getActiveID();
        }
    } else {
        $PROCESSED["proxy_id"] = $ENTRADA_USER->getActiveID();
    }
    
    switch ($request_method) {
        case "POST" :
            switch ($request["method"]) {
                case "save-assessment-proxy-scores":

                    if (!$ENTRADA_ACL->amIAllowed("assessments", "update", false) && !(isset($request["assessment_id"]) && 
                        isset($request["proxy_id"]) && ($assessment_id = clean_input($request["assessment_id"], array("trim", "int"))) && 
                        ($proxy_id = clean_input($request["proxy_id"], array("trim", "int"))) && 
                        Models_Gradebook_Assessment_Graders::canGradeAssessment($proxy_id, $assessment_id))) {

                        echo json_encode(array("status" => "error", "msg" => "assessment update not permitted"));
                        break;
                    }

                    $is_admin = $ENTRADA_USER->getActiveRole() == "admin";
                    $is_director = false;
                    $course_directors = Models_Course_Contact::fetchAllByCourseIDContactType($COURSE_ID, "director");
                    
                    foreach ($course_directors as $course_director) {
                        if ($course_director->getProxyID() == $ENTRADA_USER->getID()) {
                            $is_director = true;
                        }
                    }

                    // assessment id
                    if (isset($request["assessment_id"]) && $tmp_input = clean_input($request["assessment_id"], array("int"))) {
                        $PROCESSED["assessment_id"] = $tmp_input;
                    } else {
                        echo json_encode(array("status" => "error", "msg" => "assessment id is invalid"));
                        break;
                    }

                    // proxy id
                    if (isset($request["proxy_id"]) && $tmp_input = clean_input($request["proxy_id"], array("int"))) {
                        $PROCESSED["proxy_id"] = $tmp_input;
                    } else {
                        echo json_encode(array("status" => "error", "msg" => "proxy id is invalid"));
                        break;
                    }

                    $assessment_row = Models_Gradebook_Assessment::fetchRowByID($PROCESSED["assessment_id"]);
                    $assessment_details = $assessment_row->toArray();
                    $form_model = new Models_Assessments_Form(array('form_id' => $assessment_details['form_id']));
                    $existing_items = $form_model->getCompleteFormData($PROCESSED["assessment_id"], $PROCESSED["proxy_id"]);

                    // scores
                    $found_invalid_score = false;

                    if (isset($request["scores"])) {
                        if (is_array($request["scores"])) {
                            foreach($request["scores"] as $gairesponse_id => $score) {
                                $key_input = clean_input($gairesponse_id, array("int"));
                                $score_input = clean_input($score, array("float"));

                                if (is_int($key_input) && is_float($score_input)) {
                                    $PROCESSED["scores"][$key_input] = $score_input;
                                }
                            }
                        }
                    } else {
                        $found_invalid_score = true;
                        echo json_encode(array("status" => "error", "msg" => "comment is invalid"));
                        break;
                    }

                    // comments
                    $found_invalid_comment = false;

                    if (isset($request["comments"]) && is_array($request["comments"])) {
                        foreach($request["comments"] as $gafelement_id => $comment) {
                           
                            $key_input = clean_input($gafelement_id, array("int"));
                            $comment_input = clean_input($comment, array("trim", "striptags"));

                            if (is_int($key_input) && is_string($comment_input)) {
                                $PROCESSED["comments"][$key_input] = $comment_input;
                            } else {
                                $found_invalid_comment = true;
                                echo json_encode(array("status" => "error", "msg" => "comment is invalid"));
                                break;
                            }
                        }
                    }

                    // custom grade
                    if (isset($request["custom_grade"])) {
                        $PROCESSED["custom_grade"] = clean_input($request["custom_grade"], array("trim"));
                    }

                    // calculated grade
                    if (isset($request["calculated_grade"])) {
                        $PROCESSED["calculated_grade"] = clean_input($request["calculated_grade"], array("float"));
                    }

                    // insert scores into the Assessment Grade Form Elements table for this assessment and learner
                    $found_unpermitted_item_score_change = false;
                    $found_unfilled_item = false;
                    if ($PROCESSED["assessment_id"] && $PROCESSED["proxy_id"] && $PROCESSED["scores"]) {
                        // for non admin / non course director, the item scores must NOT be modified.
                        if (!$is_admin && !$is_director) {             

                            foreach($existing_items as $item) {
                                $found_checked_item_score = false;

                                foreach($item["item"]["item_responses"] AS $item_response) {
                                    
                                    if (isset($PROCESSED["scores"][$item_response["gairesponse_id"]]) && !is_null($PROCESSED["scores"][$item_response["gairesponse_id"]])) {
                                        $found_checked_item_score = true;
                                        $new_score = $PROCESSED["scores"][$item_response["gairesponse_id"]];
                                        $item_response_score = $item_response["item_response_score"];
                                        $proxy_score = $item_response["proxy_score"];

                                        if (is_null($proxy_score)) {
                                            if ($new_score != $item_response_score) {
                                                $found_unpermitted_item_score_change = true;
                                                break;
                                            }
                                        } else {
                                            if ($new_score != $proxy_score) {
                                                $found_unpermitted_item_score_change = true;
                                                break;
                                            }
                                        }
                                    }
                                }

                                if (!$found_checked_item_score) {
                                    $found_unfilled_item = true;
                                }
                            }
                        }

                        if ($found_unpermitted_item_score_change) {
                            echo json_encode(array("status" => "error", "msg" => "assessment item score change not permitted"));
                            break;
                        }

                        if ($found_unfilled_item) {
                            echo json_encode(array("status" => "error", "msg" => "each rubric item requires a selection"));
                            break;
                        }
                    }

                    // insert comments into the Assessment Grade Form Comments table for this assessment and learner
                    $found_unfilled_required_comment = false;

                    if ($PROCESSED["assessment_id"] && $PROCESSED["proxy_id"] && $PROCESSED["comments"] && $PROCESSED["scores"]) {
                        $form_element_model = new Models_Assessments_Form_Element(array('form_id' => $assessment_details['form_id']));
                        $form_elements = $form_element_model->fetchAllFormElementsRubricsItemsItemTypes($PROCESSED["assessment_id"], $PROCESSED["proxy_id"]);  

                        foreach ($form_elements as $current_element) {

                            if (($comment_input = $PROCESSED["comments"][$current_element["gafelement_id"]]) !== null) {
                                
                                if ($current_element["comment_type"] == "mandatory") {
                                    
                                    if (empty($comment_input)) {
                                        $found_unfilled_required_comment = true;
                                        break;
                                    }
                                } else if ($current_element["comment_type"] == "flagged") {
                                    $item_responses = Models_Assessments_Item_Response::fetchAllByItemIDsWithResponseDescriptors(array($current_element["item_id"]), $PROCESSED["assessment_id"], $PROCESSED["proxy_id"]);
                                 
                                    foreach ($item_responses as $item_response) {

                                        if (isset($PROCESSED["scores"][$item_response["gairesponse_id"]]) && !empty($PROCESSED["scores"][$item_response["gairesponse_id"]]) && $item_response["flag_response"] && empty($comment_input)) {
                                            $found_unfilled_required_comment = true;
                                            break;
                                        }
                                    }
                                }   
                            }
                        }

                        if ($found_unfilled_required_comment) {
                            echo json_encode(array("status" => "error", "msg" => "required comment is not filled"));
                            break;
                        }
                    }

                    if (isset($PROCESSED["custom_grade"])) {
                        $assessment = $assessment_row->fetchAssessmentByIDWithMarkingScheme();
                        $storage_grade = get_storage_grade($PROCESSED["custom_grade"], $assessment);
                    } else if (isset($PROCESSED["calculated_grade"])) {
                        $storage_grade = $PROCESSED["calculated_grade"];
                    }
                    $found_unpermitted_custom_score_change = false;

                    if (isset($storage_grade)) {
                        $threshold_notified = $storage_grade < $assessment_details["grade_threshold"] ? 0 : 1;
                        $assessment_grade_model = new Models_Assessment_Grade(array("assessment_id" => $PROCESSED["assessment_id"], "proxy_id" => $PROCESSED["proxy_id"], "threshold_notified" => $threshold_notified));
                        $assessment_grade = $assessment_grade_model->fetchRowByAssessmentIDProxyID();
              
                        // $assssment_grade should be null if we have a new rubric?  try to grade a new student assessment to verify this.
                        if ($assessment_grade) {
                            $existing_grade = $assessment_grade->getValue();
                        } else {
                            $existing_grade = null;
                        }

                        $calculated_grade = Entrada_Utilities::calculate_grade_from_items($existing_items);
                        // for non admin /non course director, if a custom_grade is submitted, it must be the same as the existing grade.
                        $existing_custom_grade = null;
                        
                        if ($calculated_grade != $existing_grade) {
                        // $existing_grade must have been an Custom Grade submitted by an admin or by a course director
                            $existing_custom_grade = $existing_grade;
                        }

                        if (isset($PROCESSED["custom_grade"]) && !is_null($PROCESSED["custom_grade"])) {

                            if (!$is_admin && !$is_director) {

                               if ($PROCESSED["custom_grade"] !== $existing_custom_grade) { 
                                    $found_unpermitted_custom_score_change = true;
                                    $custom_score_error = "update or insert custom score is not permitted.";
                               }
                            }
                        } else {

                            if (!$is_admin && !$is_director) {
                                
                                if(!is_null($existing_custom_grade)) {
                                    $found_unpermitted_custom_score_change = true;
                                    $custom_score_error = "unset custom score is not permitted.";
                                }
                            }
                        }

                        if ($found_unpermitted_custom_score_change) {
                            echo json_encode(array("status" => "error", "msg" =>  $custom_score_error));
                            break;
                        } 
                    }

                    if (!$found_invalid_score && !$found_invalid_comment && !$found_unpermitted_item_score_change && !$found_unfilled_item && !$found_unfilled_required_comment && !$found_unpermitted_custom_score_change) {
                        // update/insert item scores
                        $model = new Models_Assessment_Grade_Form_Element(array('proxy_id' => $PROCESSED["proxy_id"], 'assessment_id' => $PROCESSED["assessment_id"]));
                        $delete = $model->deleteAllByProxyIDAssessmentID();
                        $model->insertNewScores($PROCESSED["scores"]);

                        // update/insert item comments
                        $model = new Models_Assessment_Grade_Form_Comment(array('proxy_id' => $PROCESSED["proxy_id"], 'assessment_id' => $PROCESSED["assessment_id"]));
                        $delete = $model->deleteAllByProxyIDAssessmentID();
                        $model->insertNewComments($PROCESSED["comments"]);

                        // update/insert custom score
                        if ($assessment_grade) {
                            $assessment_grade->setValue($storage_grade);
                            $dbresult = $assessment_grade->update();
                        } else {
                            $assessment_grade_model->setValue($storage_grade);
                            $dbresult = $assessment_grade_model->insert();
                        }
                    } else {
                        $dbresult = false;
                    }

                    if ($dbresult) {
                        $result = array("status" => "success", "result" => $dbresult);
                    } else {
                        $result = array("status" => "error", "msg" => "scores and comments submit failed.");
                    }

                    echo json_encode($result);

                break;
                case "remove-form-from-assessment":
                    if (isset($request["assessment_id"]) && $tmp_input = clean_input($request["assessment_id"], array("int"))) {
                        $PROCESSED["assessment_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid assessment_id provided."));
                    }

                    if (isset($request["form_id"]) && $tmp_input = clean_input($request["form_id"], array("int"))) {
                        $PROCESSED["form_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid form_id provided."));
                    }

                    $assessment = new Models_Gradebook_Assessment(array("assessment_id" => $PROCESSED["assessment_id"]));
                    $assessment->setFormID(null);

                    $update = $assessment->update(array("form_id"));

                    if ($update) {
                        $response = array("status" => "success", "msg" => $translate->_("Update successful."));
                    }
                    else {
                        $response = array("status" => "error", "msg" => $ERRORSTR);
                    }

                    echo json_encode($response);
                break;
                case "remove-portfolio-from-assessment":
                    if (isset($request["assessment_id"]) && $tmp_input = clean_input($request["assessment_id"], array("int"))) {
                        $PROCESSED["assessment_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid assessment_id provided."));
                    }
                    $assessment = new Models_Gradebook_Assessment(array("assessment_id" => $PROCESSED["assessment_id"]));
                    $assessment->setEportfolioID(null);

                    $update = $assessment->update(array("portfolio_id"));

                    if ($update) {
                        $response = array("status" => "success", "msg" => $translate->_("Update successful."));
                    }
                    else {
                        $response = array("status" => "error", "msg" => $ERRORSTR);
                    }
                    echo json_encode($response);
                break;
                case "add_grade_exception":

                    // assessment id
                    if (isset($request["assessment_id"]) && $tmp_input = clean_input($request["assessment_id"], array("int"))) {
                        $PROCESSED["assessment_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid assessment_id provided."));
                    }

                    // proxy id
                    if (isset($request["proxy_id"]) && $tmp_input = clean_input($request["proxy_id"], array("int"))) {
                        $PROCESSED["proxy_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid proxy_id provided."));
                    }

                    $exception = new Models_Assessment_Exception(array("assessment_id" => $PROCESSED["assessment_id"], "proxy_id" => $PROCESSED["proxy_id"], "grade_weighting" => 0));

                    $result = $exception->insert();

                    if ($result !== false) {
                        echo json_encode(array("status" => "success", "result" => $result->toArray()));
                    }
                    else {
                        echo json_encode(array("status" => "fail"));
                    }

                break;
                case "remove_grade_exception":

                    // aexception id
                    if (isset($request["aexception_id"]) && $tmp_input = clean_input($request["aexception_id"], array("int"))) {
                        $PROCESSED["aexception_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid aexception_id provided."));
                    }

                    // assessment id
                    if (isset($request["assessment_id"]) && $tmp_input = clean_input($request["assessment_id"], array("int"))) {
                        $PROCESSED["assessment_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid assessment_id provided."));
                    }

                    $exception = new Models_Assessment_Exception(array("aexception_id" => $PROCESSED["aexception_id"], "assessment_id" => $PROCESSED["assessment_id"]));
                    $result = $exception->delete();

                    if ($result !== false) {
                        echo json_encode(array("status" => "success", "result" => $result));
                    }
                    else {
                        echo json_encode(array("status" => "fail"));
                    }

                break;
                case "save_grade_exception":

                    // aexception id
                    if (isset($request["aexception_id"]) && $tmp_input = clean_input($request["aexception_id"], array("int"))) {
                        $PROCESSED["aexception_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid aexception_id provided."));
                    }

                    // assessment id
                    if (isset($request["assessment_id"]) && $tmp_input = clean_input($request["assessment_id"], array("int"))) {
                        $PROCESSED["assessment_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid assessment_id provided."));
                    }

                    // proxy id
                    if (isset($request["proxy_id"]) && $tmp_input = clean_input($request["proxy_id"], array("int"))) {
                        $PROCESSED["proxy_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid proxy_id provided."));
                    }

                    // grade weighting
                    if (isset($request["grade_weighting"]) && $tmp_input = clean_input($request["grade_weighting"], array("int"))) {
                        $PROCESSED["grade_weighting"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid grade_weighting provided."));
                    }

                    $exception = new Models_Assessment_Exception(array("aexception_id" => $PROCESSED["aexception_id"], "assessment_id" => $PROCESSED["assessment_id"], "proxy_id" => $PROCESSED["proxy_id"], "grade_weighting" => $PROCESSED["grade_weighting"]));
                    $result = $exception->update();

                    if ($result) {
                        echo json_encode(array("status" => "success", "result" => $result->toArray()));
                    }
                    else {
                        echo json_encode(array("status" => "fail"));
                    }

                break;
                default:
                    echo json_encode(array("status" => "error", "data" => $translate->_("Invalid POST method.")));
                break;
            }
        break;
        case "GET" :
            switch ($request["method"]) {
                case "get-forms" :
                    $PROCESSED["filters"] = array();
                    if (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["forms"]["selected_filters"])) {
                        $PROCESSED["filters"] = $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["forms"]["selected_filters"];
                    }

                    if (isset($request["search_term"]) && $tmp_input = clean_input(strtolower($request["search_term"]), array("trim", "striptags"))) {
                        $PROCESSED["search_term"] = "%".$tmp_input."%";
                    } else {
                        $PROCESSED["search_term"] = "";
                    }

                    if (isset($request["limit"]) && $tmp_input = clean_input(strtolower($request["limit"]), array("trim", "int"))) {
                        $PROCESSED["limit"] = $tmp_input;
                    } else {
                        $PROCESSED["limit"] = 50;
                    }

                    if (isset($request["offset"]) && $tmp_input = clean_input(strtolower($request["offset"]), array("trim", "int"))) {
                        $PROCESSED["offset"] = $tmp_input;
                    } else {
                        $PROCESSED["offset"] = 0;
                    }
                    
                    if (isset($request["sort_direction"]) && $tmp_input = clean_input(strtolower($request["sort_direction"]), array("trim", "int"))) {
                        $PROCESSED["sort_direction"] = $tmp_input;
                    } else {
                        $PROCESSED["sort_direction"] = "ASC";
                    }
                    
                    if (isset($request["sort_column"]) && $tmp_input = clean_input(strtolower($request["sort_column"]), array("trim", "int"))) {
                        $PROCESSED["sort_column"] = $tmp_input;
                    } else {
                        $PROCESSED["sort_column"] = "form_id";
                    }

                    if (isset($request["date_format"]) && $tmp_input = clean_input(strtolower($request["date_format"]), array("trim", "striptags"))) {
                        $PROCESSED["date_format"] = $tmp_input;
                    } else {
                        $PROCESSED["date_format"] = "";
                    }

                    if (isset($request["rubric_id"]) && $tmp_input = clean_input(strtolower($request["rubric_id"]), array("trim", "int"))) {
                        $PROCESSED["rubric_id"] = $tmp_input;
                    } else {
                        $PROCESSED["rubric_id"] = null;
                    }

                    if (isset($request["item_id"]) && $tmp_input = clean_input(strtolower($request["item_id"]), array("trim", "int"))) {
                        $PROCESSED["item_id"] = $tmp_input;
                    } else {
                        $PROCESSED["item_id"] = null;
                    }

                    $forms = Models_Assessments_Form::fetchAllRecordsBySearchTerm($PROCESSED["search_term"], $PROCESSED["limit"], $PROCESSED["offset"], $PROCESSED["sort_direction"], $PROCESSED["sort_column"], $PROCESSED["filters"], $PROCESSED["rubric_id"], $PROCESSED["item_id"]);
                    
                    if ($forms) {
                        $data = array();

                        $date_format = $PROCESSED["date_format"] == "list" ? "D M d/y h:ia" : "Y-m-d";

                        foreach ($forms as $form) {
                            $data[] = array("form_id" => $form["form_id"], "title" => $form["title"], "created_date" => ($form["created_date"] && !is_null($form["created_date"]) ? date($date_format, $form["created_date"]) : $translate->_("N/A")), "item_count" => $form["item_count"]);
                        }
                        echo json_encode(array("results" => count($data), "data" => array("total_forms" => Models_Assessments_Form::countAllRecordsBySearchTerm($PROCESSED["search_term"], $PROCESSED["filters"], $PROCESSED["rubric_id"], $PROCESSED["item_id"]), "forms" => $data)));
                    } else {
                        echo json_encode(array("results" => "0", "data" => $SUBMODULE_TEXT["index"]["no_forms_found"]));

                    }
                break;
                case "get-form-data" : 

                    if (isset($request["form_id"]) && $tmp_input = clean_input($request["form_id"], "int")) {
                        $PROCESSED["form_id"] = $tmp_input;
                    }

                    $form_model = new Models_Assessments_Form(array('form_id' => $PROCESSED["form_id"]));
                    $results = $form_model->getCompleteFormData($PROCESSED["form_id"]);

                    if ($results) {
                        echo json_encode(array("results" => count($results), "data" => $results));
                    } else {
                        echo json_encode(array("results" => "0", "data" => array($translate->_("No results"))));
                    }

                break;
                case "get-rendered-form" :

                    if (isset($request["portfolio_id"]) && $tmp_input = clean_input($request["portfolio_id"], "int")) {
                        $PROCESSED["portfolio_id"] = $tmp_input;
                    }

                    if (isset($request["form_id"]) && $tmp_input = clean_input($request["form_id"], "int")) {
                        $PROCESSED["form_id"] = $tmp_input;
                    }

                    if (isset($request["assessment_id"]) && $tmp_input = clean_input($request["assessment_id"], "int")) {
                        $PROCESSED["assessment_id"] = $tmp_input;
                    }

                    if (isset($request["proxy_id"]) && $tmp_input = clean_input($request["proxy_id"], "int")) {
                        $PROCESSED["proxy_id"] = $tmp_input;
                    }
                    else {
                        $PROCESSED["proxy_id"] = null;
                    }

                    if (isset($request["edit_scores"]) && $tmp_input = clean_input($request["edit_scores"], "bool")) {
                        $PROCESSED["edit_scores"] = $tmp_input;
                    }

                    if (isset($request["edit_weights"]) && $tmp_input = clean_input($request["edit_weights"], "bool")) {
                        $PROCESSED["edit_weights"] = $tmp_input;
                    }

                    if (isset($request["edit_comments"]) && $tmp_input = clean_input($request["edit_comments"], "bool")) {
                        $PROCESSED["edit_comments"] = $tmp_input;
                    }

                    $form_model = new Models_Assessments_Form(array("form_id" => $PROCESSED["form_id"]));
                    $results = $form_model->getCompleteFormData($PROCESSED["assessment_id"], $PROCESSED["proxy_id"]);

                    if ($results) {
                        // Outputs fully formed html of a form
                        $form_view = new Views_Gradebook_Assessments_Form(array("data" => $results, "edit_weights" => $PROCESSED["edit_weights"], "edit_scores" => $PROCESSED["edit_scores"], "edit_comments" => $PROCESSED["edit_comments"]));
                        $form_view->render(array(), true);
                    } else {
                        echo $translate->_('Form could not be loaded.');
                    }

                break;
                case "get-rendered-assignment-portfolio-selector" :
                    $PROCESSED = Entrada_Utilities::getCleanUrlParams(array(
                        "portfolio_id" => "int",
                        "proxy_id" => "int"
                    ));
                    $portfolio_selector_view = new Views_Gradebook_Assessments_Portfolio_Menu(array("portfolio_id" => $PROCESSED["portfolio_id"],
                        "proxy_id" => $PROCESSED["proxy_id"]));
                    $portfolio_selector_view->render(array(), true);

                break;
                case "get-rendered-assignment-portfolio" :
                    $PROCESSED = Entrada_Utilities::getCleanUrlParams(array(
                        "portfolio_id" => "int",
                        "proxy_id" => "int"
                    ));
                    $portfolio_view = new Views_Gradebook_Assessments_Portfolio(array("portfolio_id" => $PROCESSED["portfolio_id"],
                        "proxy_id" => $PROCESSED["proxy_id"]));
                    $portfolio_view->render(array(), true);
                break;
                case "get-rendered-assignment-file" : 

                    $PROCESSED = Entrada_Utilities::getCleanUrlParams(array(
                        "afversion_id" => "int",
                        "proxy_id" => "int",
                        "organisation_id" => "int",
                    ));

                    // Get File Info
                    $models_file_version = new Models_Assignment_File_Version(array("afversion_id" => $PROCESSED["afversion_id"]));
                    $file = $models_file_version->fetchRowByIDWithFileType();

                    // Get User Info
                    $models_user_access = new Models_User_Access(array(
                        "user_id" => $PROCESSED["proxy_id"],
                        "organisation_id" => $PROCESSED["organisation_id"],
                        "group" => "student"
                    ));
                    $user = $models_user_access->fetchRowByUserIDOrganisationIDGroup();

                    // If both are not false, present file
                    if ($file && $user) {
                        $gradebook_file_view = new Views_Gradebook_File(array(
                            "data" => array_merge(
                                $file,
                                array(
                                    "organisation_id" => $PROCESSED["organisation_id"],
                                    "user_private_hash" => $user->getPrivateHash()
                                )
                            )
                        ));
                        $gradebook_file_view->render(array(), true);
                    }
                    else {
                        echo $translate->_("No files found.");
                    }                    

                break;
                case "get-rendered-file-selector" : 

                    if (isset($request["proxy_id"])) {
                        if (is_array($request["proxy_id"])) {
                            foreach($request["proxy_id"] as $proxy_id) {
                                $PROCESSED["proxy_ids"][] = clean_input($proxy_id, "int");
                            }
                        }
                        else {
                            $PROCESSED["proxy_id"] = clean_input($request["proxy_id"], "int");
                        }
                    }

                    if (isset($request["assignment_id"]) && $tmp_input = clean_input($request["assignment_id"], "int")) {
                        $PROCESSED["assignment_id"] = $tmp_input;
                    }

                    if (isset($request["course_id"]) && $tmp_input = clean_input($request["course_id"], "int")) {
                        $PROCESSED["course_id"] = $tmp_input;
                    }

                    if ($PROCESSED["proxy_ids"]) {
                        $version = new Models_Assignment_File_Version(array("assignment_id" => $PROCESSED["assignment_id"]));
                        $results = $version->fetchAllMostRecentAssignmentFiles($PROCESSED["proxy_ids"]);
                    }
                    else {
                        $version = new Models_Assignment_File_Version(array("proxy_id" => $PROCESSED["proxy_id"], "assignment_id" => $PROCESSED["assignment_id"]));
                        $results = $version->fetchAllMostRecentAssignmentFiles();
                    }

                    if ($results) {
                        $options = array();

                        foreach($results as $i => $result) {
                            $options[$i]["text"] = $result["file_filename"];
                            $options[$i]["value"] = $result["afversion_id"];
                        }

                        $select_view = new Views_Gradebook_Select(array("id" => "student-files", "label" => $translate->_("Current File:"), "options" => $options));
                        $select_view->render(array(), true);
                    }

                break;
                case "get-student-grade" : 

                    $PROCESSED = Entrada_Utilities::getCleanUrlParams(array(
                        "assessment_id" => "int",
                        "grade" => "float",
                    ));

                    $assessment_model = new Models_Gradebook_Assessment(array("assessment_id" => $PROCESSED["assessment_id"]));
                    $assessment = $assessment_model->fetchAssessmentByIDWithMarkingScheme();

                    $result = array(
                        "complete_grade" => format_retrieved_grade($PROCESSED["grade"], $assessment) . assessment_suffix($assessment), 
                        "formatted_grade" => format_retrieved_grade($PROCESSED["grade"], $assessment),
                    );

                    echo json_encode($result);

                break;
                case "get-storage-grade" : 

                    $PROCESSED = Entrada_Utilities::getCleanUrlParams(array(
                        "assessment_id" => "int",
                        "grade" => "string",
                    ));

                    $assessment_model = new Models_Gradebook_Assessment(array("assessment_id" => $PROCESSED["assessment_id"]));
                    $assessment = $assessment_model->fetchAssessmentByIDWithMarkingScheme();    

                    $result = array(
                        "storage_grade" => get_storage_grade($PROCESSED["grade"], $assessment) 
                    );

                    echo json_encode($result);

                break;
                case "get-grade-exceptions" : 

                    $PROCESSED = Entrada_Utilities::getCleanUrlParams(array(
                        "assessment_id" => "int",
                    ));

                    $assessment_model = new Models_Gradebook_Assessment(array("assessment_id" => $PROCESSED["assessment_id"]));
                    $grade_weightings = $assessment_model->fetchGradeWeightingExceptions();

                    if ($grade_weightings) {
                        $results = array(
                            "status" => "success", 
                            "results" => $grade_weightings
                        );
                    }
                    else {
                        $results = array(
                            "status" => "error"
                        );
                    }

                    echo json_encode($results);

                break;
                // @TODO is this needed for gradebook?
                case "get-filtered-audience" :
                    
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = "%".$tmp_input."%";
                    }
                    
                    if (isset($request["filter_type"]) && $tmp_input = clean_input($request["filter_type"], array("trim", "striptags"))) {
                        $PROCESSED["filter_type"] = $tmp_input;
                    }

                    if (isset($request["content_target"]) && $tmp_input = clean_input($request["content_target"], "int")) {
                        $PROCESSED["form_id"] = $tmp_input;
                    }

                    $results = Models_Assessments_Form_Author::fetchAvailableAuthors($PROCESSED["filter_type"], $PROCESSED["form_id"], $PROCESSED["search_value"]);
                    if ($results) {
                        echo json_encode(array("results" => count($results), "data" => $results));
                    } else {
                        echo json_encode(array("results" => "0", "data" => array($translate->_("No results"))));
                    }
                break;
                // @TODO is this needed for gradebook?
                case "get-objectives" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    if (isset($request["parent_id"]) && $tmp_input = clean_input(strtolower($request["parent_id"]), array("trim", "int"))) {
                        $PROCESSED["parent_id"] = $tmp_input;
                    } else {
                        $PROCESSED["parent_id"] = 0;
                    }

                    $parent_objective = Models_Objective::fetchRow($PROCESSED["parent_id"]);
                    $objectives = Models_Objective::fetchByOrganisationSearchValue($ENTRADA_USER->getActiveOrganisation(), $PROCESSED["search_value"], $PROCESSED["parent_id"]);

                    if ($objectives) {
                        $data = array();
                        foreach ($objectives as $objective) {
                            $data[] = array("target_id" => $objective->getID(), "target_parent" => $objective->getParent(), "target_label" => $objective->getName(), "target_children" => Models_Objective::countObjectiveChildren($objective->getID()));
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "parent_id" => ($parent_objective ? $parent_objective->getParent() : "0"), "parent_name" => ($parent_objective ? $parent_objective->getName() : "0"), "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No objectives found to display.")));
                    }

                break;
                // @TODO is this needed for gradebook?
                case "get-child-objectives" :
                    if (isset($request["parent_id"]) && $tmp_input = clean_input(strtolower($request["parent_id"]), array("trim", "int"))) {
                        $PROCESSED["parent_id"] = $tmp_input;
                    } else {
                        $PROCESSED["parent_id"] = 0;
                    }

                    $parent_objective = Models_Objective::fetchRow($PROCESSED["parent_id"]);
                    $child_objectives = Models_Objective::fetchAllByParentID($ENTRADA_USER->getActiveOrganisation(), $PROCESSED["parent_id"]);

                    if ($child_objectives) {
                        $data = array();
                        foreach ($child_objectives as $objective) {
                            $data[] = array("target_id" => $objective->getID(), "target_parent" => $objective->getParent(), "target_label" => $objective->getName(), "target_children" => Models_Objective::countObjectiveChildren($objective->getID()));
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "parent_id" => ($parent_objective ? $parent_objective->getParent() : "0"), "parent_name" => ($parent_objective ? $parent_objective->getName() : "0"), "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No objectives found to display.")));
                    }
                break;
                // @TODO is this needed for gradebook?
                case "get-form-authors" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }
                    
                    $authors = Models_Assessments_Form_Author::fetchByAuthorTypeProxyID($ENTRADA_USER->getActiveOrganisation(), $PROCESSED["search_value"]);
                    if ($authors) {
                        $data = array();
                        foreach ($authors as $author) {
                            $author_name = ($author->getAuthorName() ? $author->getAuthorName() : "N/A");
                            $data[] = array("target_id" => $author->getAuthorID(), "target_label" => $author_name);
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No authors were found.")));
                    }
                break;
                // @TODO is this needed for gradebook?
                case "get-user-courses" :
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
                        echo json_encode(array("status" => "error", "data" => $translate->_("No courses were found.")));
                    }
                break;
                // @TODO is this needed for gradebook?
                case "get-user-organisations" :
                    $user_organisations = $ENTRADA_USER->getAllOrganisations();
                    if ($user_organisations) {
                        $data = array();
                        foreach ($user_organisations as $key => $organisation) {
                            $data[] = array("target_id" => $key, "target_label" => $organisation);
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No organisations were found.")));
                    }
                break;
                default:
                    echo json_encode(array("status" => "error", "data" => $translate->_("Invalid GET method.")));
                break;
            }
        break;
        default :
            echo json_encode(array("status" => "error", "data" => $translate->_("Invalid request method.")));
        break;
    }

    exit;

}