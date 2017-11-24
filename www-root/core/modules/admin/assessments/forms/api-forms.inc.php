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
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_FORMS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false) &&
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

                    require(ENTRADA_ABSOLUTE."/core/library/Entrada/gradebook/handlers.inc.php");

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
                case "copy-form" :

                    // Ensure that a new form title was entered
                    if (isset($_POST["new_form_title"]) && $tmp_input = clean_input($_POST["new_form_title"], array("trim", "striptags"))) {
                        $PROCESSED["title"] = $tmp_input;
                    } else {
                        add_error($translate->_("Sorry, a new form title is required."));
                    }

                    // Validate posted form identifier
                    if (isset($request["form_id"]) && $tmp_input = clean_input($request["form_id"], "int")) {
                        $old_form_id = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid form identifier provided."));
                    }

                    if (!$ERROR) {

                        $old_form = Models_Assessments_Form::fetchRowByID($old_form_id);

                        // Create a new form to copy to
                        $PROCESSED["organisation_id"] = $ENTRADA_USER->getActiveOrganisation();
                        $PROCESSED["created_date"] = time();
                        $PROCESSED["updated_date"] = time();
                        $PROCESSED["created_by"] = $ENTRADA_USER->getActiveID();
                        $PROCESSED["updated_by"] = $ENTRADA_USER->getActiveID();
                        $PROCESSED["description"] = $old_form->getDescription();
                        $form = new Models_Assessments_Form($PROCESSED);

                        if ($form->insert()) {

                            // Retrieve all authors from the previous form
                            $authors = Models_Assessments_Form_Author::fetchAllByFormID($old_form_id, $ENTRADA_USER->getActiveOrganisation());
                            if ($authors) {
                                // Insert copies of the authors with the newly created form's ID
                                foreach ($authors as $author) {

                                    $author_data = array(
                                        "form_id"               => $form->getID(),
                                        "author_type"           => $author->getAuthorType(),
                                        "author_id"             => $author->getAuthorId(),
                                        "created_date"          => time(),
                                        "created_by"            => $ENTRADA_USER->getActiveId(),
                                        "updated_date"          => $author->getUpdatedDate(),
                                        "updated_by"            => $author->getUpdatedBy()
                                    );

                                    $author = new Models_Assessments_Form_Author($author_data);
                                    if (!$author->insert()) {
                                        add_error($translate->_("An error occured while adding an author to the form."));
                                    }
                                }
                            }

                            // Retrieve the elements from the previous form
                            $elements = Models_Assessments_Form_Element::fetchAllByFormID($old_form_id);
                            if ($elements) {
                                // Insert copies of elements with the newly created form's ID
                                foreach ($elements as $element) {

                                    if ($element->getElementId() && $element->getElementType() == "item") {
                                        if (!$item_record = Models_Assessments_Item::fetchRowByID($element->getElementId())) {
                                            continue; // it's been deleted, so skip it (don't copy it to the new form)
                                        }
                                    }

                                    $element_data = array(
                                        "form_id"           => $form->getID(),
                                        "element_type"      => $element->getElementType(),
                                        "element_id"        => $element->getElementId(),
                                        "element_text"      => $element->getElementText(),
                                        "rubric_id"         => $element->getRubricId(),
                                        "order"             => $element->getOrder(),
                                        "allow_comments"    => $element->getAllowComments(),
                                        "enable_flagging"   => $element->getEnableFlagging(),
                                        "updated_date"      => time(),
                                        "updated_by"        => $ENTRADA_USER->getActiveId()
                                    );

                                    $element = new Models_Assessments_Form_Element($element_data);
                                    if (!$element->insert()) {
                                        add_error($translate->_("An error occurred while adding an element to a form."));
                                    }
                                }
                            }
                            
                            if (!$ERROR) {
                                Entrada_Utilities_Flashmessenger::addMessage("Successfully copied form", "success", $MODULE);
                                $url = ENTRADA_URL."/admin/assessments/forms?section=edit-form&form_id=".$form->getID();
                                echo json_encode(array("status" => "success", "url" => $url));
                            } else {
                                echo json_encode(array("status" => "error", "msg" => $ERRORSTR));
                            }

                        } else {
                            echo json_encode(array("status" => "error", "msg" => $ERRORSTR));
                        }

                    } else {
                        echo json_encode(array("status" => "error", "msg" => $ERRORSTR));
                    }

                    break;
                case "update-form-element-order":

                    if (isset($request["form_id"]) && $tmp_input = clean_input($request["form_id"], "int")) {
                        $PROCESSED["form_id"] = $tmp_input;
                    }

                    $PROCESSED["order_array"] = array();
                    if (isset($request["order_array"]) && is_array($request["order_array"])) {
                        foreach ($request["order_array"] as $form_element_order) {
                            $PROCESSED["order_array"][] = array(
                                "afelement_id"      => (int) $form_element_order["afelement_id"],
                            );
                        }
                    }

                    if (!empty($PROCESSED["order_array"]) && isset($PROCESSED["form_id"]) && !empty($PROCESSED["form_id"])) {
                        $failed_update = 0;
                        $order = 0;
                        foreach ($PROCESSED["order_array"] as $order_update) {
                            $element = Models_Assessments_Form_Element::fetchRowByID($order_update["afelement_id"]);
                            if (!$element->getRubricID()) {
                                if ($element->fromArray(array("order" => $order, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getActiveID()))->update()) {
                                    $order++;
                                } else {
                                    $failed_update++;
                                }
                            } else {
                                $rubric_items = Models_Assessments_Form_Element::fetchAllByFormIDRubricID($PROCESSED["form_id"], $element->getRubricID());
                                if ($rubric_items) {
                                    foreach ($rubric_items as $element) {
                                        if ($element->fromArray(array("order" => $order, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getActiveID()))->update()) {
                                            $order++;
                                        } else {
                                            $failed_update++;
                                        }
                                    }
                                }
                            }
                        }

                        if ($failed_update > 0) {
                            echo json_encode(array("status" => "error"));
                        } else {
                            echo json_encode(array("status" => "success"));
                        }
                    }

                break;
                case "delete-form-elements" :
                    $PROCESSED["delete_ids"] = array();
                    if (isset($request["delete_array"]) && is_array($request["delete_array"])) {
                        foreach ($request["delete_array"] as $afelement_id) {
                            $tmp_input = clean_input($afelement_id, "int");
                            if ($tmp_input) {
                                $PROCESSED["delete_ids"][] = $tmp_input;
                            }
                        }
                    }

                    if (!empty($PROCESSED["delete_ids"])) {
                        $deleted_elements = array();
                        foreach ($PROCESSED["delete_ids"] as $afelement_id) {
                            if (!in_array($afelement_id, $deleted_elements)) {
                                $form_element = Models_Assessments_Form_Element::fetchRowByID($afelement_id);
                                if ($form_element) {
                                    $rubric_id = $form_element->getRubricID();
                                    $form_id = $form_element->getFormID();
                                    if ($form_element->fromArray(array("deleted_date" => time(), "updated_date" => time(), "updated_by" => $ENTRADA_USER->getActiveID()))->update()) {
                                        $deleted_elements[] = $afelement_id;
                                        if ($rubric_id) {
                                            $sibling_elements = Models_Assessments_Form_Element::fetchAllByFormIDRubricID($form_id, $rubric_id);
                                            if ($sibling_elements) {
                                                foreach ($sibling_elements as $element) {
                                                    $deleted_elements[] = $element->getID();
                                                    if (!$element->fromArray(array("deleted_date" => time(), "updated_date" => time(), "updated_by" => $ENTRADA_USER->getActiveID()))->update()) {
                                                        $ERROR++;
                                                    }
                                                }
                                            }
                                        }
                                    } else {
                                        $ERROR++;
                                    }
                                }
                            }
                        }
                        if (!$ERROR) {
                            echo json_encode(array("status" => "success", "data" => $deleted_elements));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("Invalid form element ID.")));
                    }

                break;
                case "remove-permission" :
                    if (isset($request["afauthor_id"]) && $tmp_input = clean_input($request["afauthor_id"], "int")) {
                        $PROCESSED["afauthor_id"] = $tmp_input;
                    }

                    if ($PROCESSED["afauthor_id"]) {

                        $author = Models_Assessments_Form_Author::fetchRowByID($PROCESSED["afauthor_id"]);
                        if (($author->getAuthorType() == "proxy_id" && $author->getAuthorID() != $ENTRADA_USER->getActiveID()) || $author->getAuthorType() != "proxy_id") {
                            if ($author->fromArray(array("deleted_date" => time(), "updated_date" => time(), "updated_by" => $ENTRADA_USER->getActiveID()))->update()) {
                                echo json_encode(array("status" => "success", $translate->_("success.")));
                            } else {
                                echo json_encode(array("status" => "error", $translate->_("You can't delete yourself.")));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("You can't delete yourself.")));
                        }

                    } else {
                        echo json_encode(array("status" => "error"));
                    }
                break;
                case "update-form-primitives":
                    $PROCESSED["form_id"] = null;
                    if (isset($request["form_id"]) && $tmp_input = clean_input($request["form_id"], array("trim", "int"))) {
                        $PROCESSED["form_id"] = $tmp_input;
                    }
                    $PROCESSED["form_title"] = null;
                    if (isset($request["form_title"]) && $tmp_input = clean_input($request["form_title"], array("trim", "notags"))) {
                        $PROCESSED["form_title"] = $tmp_input;
                    }
                    $PROCESSED["form_description"] = null; // optional
                    if (isset($request["form_description"]) && $tmp_input = clean_input($request["form_description"], array("trim", "notags"))) {
                        $PROCESSED["form_description"] = $tmp_input;
                    }

                    // Validate that the required fields exist
                    if (!$PROCESSED["form_id"]) {
                        add_error($translate->_("Please specify a form to update."));
                    }
                    if (!$PROCESSED["form_title"]) {
                        add_error($translate->_("Please specify a form title."));
                    }

                    if (!$ERROR) {
                        $forms_api->setFormID($PROCESSED["form_id"]);
                        $forms_api->updateFormPrimitives($PROCESSED["form_title"], $PROCESSED["form_description"]);
                        $errors = $forms_api->getErrorMessages();
                        if (is_array($errors)) {
                            foreach ($errors as $error) {
                                add_error($error);
                            }
                        }
                    }

                    if ($ERROR) {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    } else {
                        echo json_encode(array("status" => "success", "data" => array($translate->_("Successfully updated form."))));
                    }
                break;
                case "add-permission" :
                    if (isset($request["member_id"]) && $tmp_input = clean_input($request["member_id"], "int")) {
                        $PROCESSED["member_id"] = $tmp_input;
                    }

                    if (isset($request["member_type"]) && $tmp_input = clean_input($request["member_type"], array("trim", "striptags"))) {
                        $PROCESSED["member_type"] = $tmp_input;
                    }

                    if (isset($request["content_target"]) && $tmp_input = clean_input($request["content_target"], "int")) {
                        $PROCESSED["form_id"] = $tmp_input;
                    }

                    if ($PROCESSED["member_id"] && $PROCESSED["member_type"] && $PROCESSED["form_id"]) {
                        $added = 0;
                        $a = Models_Assessments_Form_Author::fetchRowByFormIDAuthorIDAuthorType($PROCESSED["form_id"], $PROCESSED["member_id"], $PROCESSED["member_type"]);
                        if ($a) {
                            if ($a->getDeletedDate()) {
                                if ($a->fromArray(array("deleted_date" => NULL))->update()) {
                                    $added++;
                                }
                            } else {
                                application_log("notice", "Form author [".$a->getID()."] is already an active author. API should not have returned this author as an option.");
                            }
                        } else {
                            $a = new Models_Assessments_Form_Author(
                                array(
                                    "form_id"       => $PROCESSED["form_id"],
                                    "author_type"   => $PROCESSED["member_type"],
                                    "author_id"     => $PROCESSED["member_id"],
                                    "updated_date"  => time(),
                                    "updated_by"    => $ENTRADA_USER->getActiveID(),
                                    "created_date"  => time(),
                                    "created_by"    => $ENTRADA_USER->getActiveID()
                                )
                            );
                            if ($a->insert()) {
                                $added++;
                            }
                        }

                        if ($added >= 1) {
                            echo json_encode(array("status" => "success", "data" => array("author_id" => $a->getID())));
                        } else {
                            echo json_encode(array("status" => "success", "data" => array($translate->_("Failed to add author"))));
                        }
                    }
                break;
                case "add-objective-element" :
                    if (isset($request["form_id"]) && $tmp_input = clean_input($request["form_id"], "int")) {
                        $PROCESSED["form_id"] = $tmp_input;
                    }

                    if ($PROCESSED["form_id"]) {
                        $element_data = array(
                            "form_id"           => $PROCESSED["form_id"],
                            "element_type"      => "objective",
                            "order"             => Models_Assessments_Form_Element::fetchNextOrder($PROCESSED["form_id"]),
                            "allow_comments"    => "1",
                            "enable_flagging"   => "0",
                            "updated_date"      => time(),
                            "updated_by"        => $ENTRADA_USER->getActiveId()
                        );

                        $element = new Models_Assessments_Form_Element($element_data);
                        if ($element->insert()) {
                            echo json_encode(array("status" => "success", "data" => array("afelement_id" => $element->getID())));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("Error creating element."))));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("Invalid form id."))));
                    }
                break;
                case "save-objective-element" :
                    if (isset($request["afelement_id"]) && $tmp_input = clean_input($request["afelement_id"], "int")) {
                        $PROCESSED["afelement_id"] = $tmp_input;
                    }

                    if (isset($request["element_id"]) && $tmp_input = clean_input($request["element_id"], array("trim", "int"))) {
                        $PROCESSED["element_id"] = $tmp_input;
                    }

                    if (isset($PROCESSED["element_id"]) && isset($PROCESSED["afelement_id"])) {
                        $element = Models_Assessments_Form_Element::fetchRowByID($PROCESSED["afelement_id"]);
                        if ($element->fromArray(array("element_id" => $PROCESSED["element_id"]))->update()) {
                            echo json_encode(array("status" => "success", "data" => array("afelement_id" => $element->getID())));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to update text element"))));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("Invalid text element ID"))));
                    }
                break;
                case "add-text" :
                    if (isset($request["form_id"]) && $tmp_input = clean_input($request["form_id"], "int")) {
                        $PROCESSED["form_id"] = $tmp_input;
                    }

                    if ($PROCESSED["form_id"]) {
                        $element_data = array(
                            "form_id" => $PROCESSED["form_id"],
                            "element_type"      => "text",
                            "element_text"      => "",
                            "order"             => Models_Assessments_Form_Element::fetchNextOrder($PROCESSED["form_id"]),
                            "allow_comments"    => "1",
                            "enable_flagging"   => "0",
                            "updated_date"      => time(),
                            "updated_by"        => $ENTRADA_USER->getActiveId()
                        );

                        $element = new Models_Assessments_Form_Element($element_data);
                        if ($element->insert()) {
                            echo json_encode(array("status" => "success", "data" => array("afelement_id" => $element->getID())));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("Error creating element."))));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("Invalid form id."))));
                    }
                break;
                case "save-text-element" :
                    if (isset($request["afelement_id"]) && $tmp_input = clean_input($request["afelement_id"], "int")) {
                        $PROCESSED["afelement_id"] = $tmp_input;
                    }

                    if (isset($request["element_text"]) && $tmp_input = clean_input($request["element_text"], array("trim", "allowedtags"))) {
                        $PROCESSED["element_text"] = $tmp_input;
                    }

                    if (isset($PROCESSED["element_text"]) && isset($PROCESSED["afelement_id"])) {
                        $element = Models_Assessments_Form_Element::fetchRowByID($PROCESSED["afelement_id"]);
                        if ($element->fromArray(array("element_text" => $PROCESSED["element_text"]))->update()) {
                            echo json_encode(array("status" => "success", "data" => array("afelement_id" => $element->getID())));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to update text element"))));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("Invalid text element ID"))));
                    }
                break;
                case "delete-forms" :
                    $PROCESSED["delete_ids"] = array();
                    if (isset($request["delete_ids"]) && is_array($request["delete_ids"])) {
                        foreach ($request["delete_ids"] as $rubric_id) {
                            $tmp_input = clean_input($rubric_id, "int");
                            if ($tmp_input) {
                                $PROCESSED["delete_ids"][] = $tmp_input;
                            }
                        }
                    }

                    if (!empty($PROCESSED["delete_ids"])) {
                        $deleted_forms = array();
                        foreach ($PROCESSED["delete_ids"] as $form_id) {
                            $form = Models_Assessments_Form::fetchRowByID($form_id);
                            if ($form) {
                                $form->fromArray(array("deleted_date" => time(),
                                                       "updated_date" => time(),
                                                       "updated_by" => $ENTRADA_USER->getActiveID()));
                                if (!$form->update()) {
                                    add_error($translate->_("Unable to delete a form"));
                                } else {
                                    $ENTRADA_LOGGER->log("", "delete", "form_id", $form_id, 4, __FILE__, $ENTRADA_USER->getID());
                                    $deleted_forms[] = $form_id;
                                }
                            }
                        }
                        if (!$ERROR) {
                            echo json_encode(array("status" => "success", "msg" => sprintf($translate->_("Successfully deleted %d form(s)."), count($deleted_forms)), "form_ids" => $deleted_forms));
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("There was an error when attempting to delete a Form.")));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "msg" => $translate->_("Nothing to delete.")));
                    }
                break;
                case "set-filter-preferences" :
                    if (isset($request["curriculum_tag"]) && is_array($request["curriculum_tag"])) {
                        $PROCESSED["filters"]["curriculum_tag"] = array_filter($request["curriculum_tag"], function ($curriculum_tag) {
                            return (int) $curriculum_tag;
                        });
                    }

                    if (isset($request["author"]) && is_array($request["author"])) {
                        $PROCESSED["filters"]["author"] = array_filter($request["author"], function ($author) {
                            return (int) $author;
                        });
                    }

                    if (isset($request["course"]) && is_array($request["course"])) {
                        $PROCESSED["filters"]["course"] = array_filter($request["course"], function ($course) {
                            return (int) $course;
                        });
                    }

                    if (isset($request["organisation"]) && is_array($request["organisation"])) {
                        $PROCESSED["filters"]["organisation"] = array_filter($request["organisation"], function ($organisation) {
                            return (int) $organisation;
                        });
                    }

                    foreach (array("curriculum_tag", "author", "course", "organisation") as $filter_type) {
                        Entrada_Utilities_AdvancedSearchHelper::cleanupSessionFilters($request, $MODULE, $SUBMODULE, $filter_type);
                    }

                    if (isset($PROCESSED["filters"])) {
                        $assessments_base = new Entrada_Utilities_Assessments_Base();
                        Models_Assessments_Form::saveFilterPreferences($PROCESSED["filters"]);
                        $assessments_base->updateAssessmentPreferences("assessments");
                        echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully saved the selected filters")));
                    } else {
                        echo json_encode(array("status" => "error", "msg" => $translate->_("Filters were unable to be saved")));
                    }
                break;
                case "remove-filter" :
                    if (isset($request["filter_type"]) && $tmp_input = clean_input($request["filter_type"], array("trim", "striptags"))) {
                        $PROCESSED["filter_type"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid filter type provided."));
                    }

                    if (isset($request["filter_target"]) && $tmp_input = clean_input($request["filter_target"], array("trim", "int"))) {
                        $PROCESSED["filter_target"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid filter target provided."));
                    }

                    $assessments_base = new Entrada_Utilities_Assessments_Base();

                    if (isset($PROCESSED["filter_type"]) && isset($PROCESSED["filter_target"])) {
                        unset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["forms"]["selected_filters"][$PROCESSED["filter_type"]][$PROCESSED["filter_target"]]);
                        if (empty($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["forms"]["selected_filters"][$PROCESSED["filter_type"]])) {
                            unset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["forms"]["selected_filters"][$PROCESSED["filter_type"]]);
                            if (empty($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["forms"]["selected_filters"])) {
                                unset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["forms"]["selected_filters"]);
                            }
                        }

                        $assessments_base->updateAssessmentPreferences("assessments");
                        echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully removed the selected filter")));
                    } else {
                        echo json_encode(array("status" => "error", "msg" => $ERRORSTR));
                    }
                break;
                case "remove-all-filters" :
                    $assessments_base = new Entrada_Utilities_Assessments_Base();
                    unset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["forms"]["selected_filters"]);
                    $assessments_base->updateAssessmentPreferences("assessments");
                    echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully removed all filters")));
                break;
                case "attach-form-to-assessment":
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
                    $assessment->setFormID($PROCESSED["form_id"]);

                    $update = $assessment->update(array("form_id"));

                    if ($update) {
                        $response = array("status" => "success", "msg" => $translate->_("Update successful."));
                    }
                    else {
                        $response = array("status" => "error", "msg" => $ERRORSTR);
                    }

                    echo json_encode($response);
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
                /*
                case "save-assessment-proxy-scores":

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

                    // scores
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
                        add_error($translate->_("Invalid scores provided."));
                    }

                    // comments
                    if (isset($request["comments"])) {
                        if (is_array($request["comments"])) {
                            foreach($request["comments"] as $gafelement_id => $comment) {
                                $key_input = clean_input($gafelement_id, array("int"));
                                $comment_input = clean_input($comment, array("trim", "striptags"));

                                if (is_int($key_input) && is_string($comment_input)) {
                                    $PROCESSED["comments"][$key_input] = $comment_input;
                                }
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
                    if ($PROCESSED["assessment_id"] && $PROCESSED["proxy_id"] && $PROCESSED["scores"]) {
                        
                        $model = new Models_Assessment_Grade_Form_Element(array('proxy_id' => $PROCESSED["proxy_id"], 'assessment_id' => $PROCESSED["assessment_id"]));
                    
                        // Delete all by proxy and assessment id
                        $delete = $model->deleteAllByProxyIDAssessmentID();

                        // Insert new scores
                        $model->insertNewScores($PROCESSED["scores"]);
                    }

                    // insert comments into the Assessment Grade Form Comments table for this assessment and learner
                    if ($PROCESSED["assessment_id"] && $PROCESSED["proxy_id"] && $PROCESSED["comments"]) {

                        $model = new Models_Assessment_Grade_Form_Comment(array('proxy_id' => $PROCESSED["proxy_id"], 'assessment_id' => $PROCESSED["assessment_id"]));

                        // Delete all by proxy and assessment id
                        $delete = $model->deleteAllByProxyIDAssessmentID();

                        // Insert new scores
                        $model->insertNewComments($PROCESSED["comments"]);
                    }

                    require(ENTRADA_ABSOLUTE."/core/library/Entrada/gradebook/handlers.inc.php");

                    // assessment
                    $assessment_model = new Models_Gradebook_Assessment(array("assessment_id" => $PROCESSED["assessment_id"]));
                    $assessment = $assessment_model->fetchAssessmentByIDWithMarkingScheme();

                    if (isset($PROCESSED["custom_grade"])) {
                        $storage_grade = get_storage_grade($PROCESSED["custom_grade"], $assessment);
                    }
                    elseif (isset($PROCESSED["calculated_grade"])) {
                        $storage_grade = $PROCESSED["calculated_grade"];
                    }

                    if (isset($storage_grade)) {

                        $threshold_notified = $storage_grade < $assessment["grade_threshold"] ? 0 : 1;

                        // assessment grade
                        $assessment_grade_model = new Models_Assessment_Grade(array("assessment_id" => $PROCESSED["assessment_id"], "proxy_id" => $PROCESSED["proxy_id"], "threshold_notified" => $threshold_notified));
                        $assessment_grade = $assessment_grade_model->fetchRowByAssessmentIDProxyID();
                        
                        if ($assessment_grade) {
                            $assessment_grade->setValue($storage_grade);
                            $dbresult = $assessment_grade->update();
                        }
                        else {
                            $assessment_grade_model->setValue($storage_grade);
                            $dbresult = $assessment_grade_model->insert();
                        }
                    }

                    if ($dbresult) {
                        $result = array("status" => "success", "result" => $result);
                    }
                    else {
                        $result = array("status" => "error", "msg" => $ERRORSTR);
                    }

                    echo json_encode($result);

                break;
                */
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
                case "get-form-preview" :

                    if (isset($request["form_id"]) && $tmp_input = clean_input($request["form_id"], "int")) {
                        $PROCESSED["form_id"] = $tmp_input;
                    }
                    // TODO: Implement a form preview form render.

                break;
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

                    require(ENTRADA_ABSOLUTE."/core/library/Entrada/gradebook/handlers.inc.php");

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

                    require(ENTRADA_ABSOLUTE."/core/library/Entrada/gradebook/handlers.inc.php");

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