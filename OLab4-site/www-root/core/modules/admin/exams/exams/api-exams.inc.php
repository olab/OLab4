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
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EXAMS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("exam", "create", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

    ob_clear_open_buffers();

    $request_method = strtoupper(clean_input($_SERVER["REQUEST_METHOD"], "alpha"));

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
                    case "update-sub-folder-search-preference" :
                        if (isset($request["action"]) && $tmp_input = clean_input($request["action"], "int")) {
                            $PROCESSED["action"] = $tmp_input;
                        }

                        if ($PROCESSED["action"] === 1) {
                            $action = "on";
                        } else {
                            $action = "off";
                        }

                        global $PREFERENCES;
                        $success = 0;

                        if (!empty($action)) {
                            $_SESSION[APPLICATION_IDENTIFIER]["exams"]["exams"]["sub_folder_search"] = $action;

                            if (preferences_update("exams", $PREFERENCES)) {
                                $success = 1;
                            }
                        }

                        if ($success) {
                            echo json_encode(array("status" => "success", "message" => "Updated successfully."));
                        } else {
                            echo json_encode(array("status" => "error", "message" => "Failed to update."));
                        }

                        break;
                case "exam-wizard" :

                    if (isset($request["step"]) && $tmp_input = clean_input($request["step"], array("trim", "int"))) {
                        $PROCESSED["step"] = $tmp_input;
                    } else {
                        add_error($translate->_("No Step provided."));
                    }

                    if (isset($request["next_step"]) && $tmp_input = clean_input($request["next_step"], array("trim", "int"))) {
                        $PROCESSED["next_step"] = $tmp_input;
                    }

                    if (isset($request["post_id"]) && $tmp_input = clean_input($request["post_id"], array("trim", "int"))) {
                        $post_id = $tmp_input;
                    } else {
                        $post_id = null;
                    }

                    if (isset($PROCESSED["step"])) {
                        switch ($PROCESSED["step"]) {
                            case 1 :
                                $exam_controller = new Controllers_Exam_Post($request, false, true, 1);
                                $PROCESSED = $exam_controller->getValidatedData();

                                $next_step = 2;
                                break;
                            case 2 :
                                $exam_controller = new Controllers_Exam_Post($request, false, true, array(1, 2));
                                $PROCESSED = $exam_controller->getValidatedData();

                                if (isset($PROCESSED['secure']) && $PROCESSED['secure'] == 1) {
                                    $next_step = 3;
                                    $previous_step = 2;
                                } else {
                                    $next_step = 4;
                                    $previous_step = 2;
                                }
                                break;
                            case 3 :
                                $exam_controller = new Controllers_Exam_Post($request, false, true, array(1, 2, 3));
                                $PROCESSED = $exam_controller->getValidatedData();

                                $next_step = 4;

                                if (isset($PROCESSED['secure']) && $PROCESSED['secure'] == 1) {
                                    $previous_step = 3;
                                } else {
                                    $previous_step = 2;
                                }

                                break;
                            case 4 :
                                $exam_controller = new Controllers_Exam_Post($request, false, true, array(1, 2, 3, 4));
                                $PROCESSED = $exam_controller->getValidatedData();

                                $next_step = 5;

                                break;
                            case 5 :
                                $exam_controller = new Controllers_Exam_Post($request, false, true, array(1, 2, 3, 4, 5));
                                $PROCESSED = $exam_controller->getValidatedData();

                                $next_step = 6;

                                break;
                            case 6:
                                $exam_controller = new Controllers_Exam_Post($request, false, true, array(1, 2, 3, 4, 5, 6));
                                $PROCESSED = $exam_controller->getValidatedData();

                                if (!has_error()) {
                                    if ($exam_controller->save()) {
                                        $post_id = $exam_controller->getPost()->getID();
                                        /**
                                         * Process the Secure Access files and keys
                                         */
                                        if (isset($request["secure_file"]) && is_array($request["secure_file"])) {
                                            $PROCESSED["secure_file"] = $request["secure_file"];
                                        }
                                        if (isset($PROCESSED["secure_file"])) {
                                            foreach ($PROCESSED["secure_file"] as $secure_file) {
                                                if (isset($secure_file['file']) && $secure_file !== "") {
                                                    $secure_key["resource_type"] = "exam_post";
                                                    $secure_key["resource_id"] = $post_id;
                                                    $file = new Models_Secure_AccessFiles($secure_file);
                                                    if (!$file->insert()) {
                                                        application_log("error", "An error occurred while attempting to save the secure file for this exam. DB:" . $db->ErrorMsg());
                                                    }
                                                }
                                            }
                                        }

                                        if (isset($request["secure_key"]) && is_array($request["secure_key"])) {
                                            $PROCESSED["secure_key"] = $request["secure_key"];
                                        }

                                        if (isset($PROCESSED["secure_key"])) {
                                            foreach ($PROCESSED["secure_key"] as $secure_key) {
                                                if (isset($secure_key['key']) && $secure_key !== "") {
                                                    $secure_key["resource_type"] = "exam_post";
                                                    $secure_key["resource_id"] = $exam_controller->getPost()->getID();
                                                    $key = new Models_Secure_AccessKeys($secure_key);
                                                    if (!$key->insert()) {
                                                        application_log("error", "An error occurred while attempting to save secure keys for this exam. DB:" . $db->ErrorMsg());
                                                    }
                                                }
                                            }
                                        }

                                        //Remove the files that are no longer present
                                        if (isset($request["secure_file_delete"]) && is_array($request["secure_file_delete"])) {
                                            $PROCESSED["secure_file_delete"] = $request["secure_file_delete"];
                                        }

                                        if (isset($PROCESSED["secure_file_delete"])) {
                                            foreach ($PROCESSED["secure_file_delete"] as $i => $secure_file_delete) {
                                                $file_to_delete = Models_Secure_AccessFiles::fetchRowByID($secure_file_delete);

                                                if ($file_to_delete) {
                                                    if ($ENTRADA_ACL->amIAllowed(new SecureAccessFileResource($file_to_delete->getID()), "delete")) {
                                                        if ($file_to_delete->delete()) {
                                                            $deleted_file[$i]['id'] = $file_to_delete->getID();
                                                            $deleted_file[$i]['file'] = $file_to_delete->getFileName();
                                                            $deleted_file[$i]['updated_date'] = $file_to_delete->getUpdatedDate();
                                                        } else {
                                                            $error_file[$i]['id'] = $file_to_delete->getID();
                                                            $error_file[$i]['file'] = $file_to_delete->getFileName();
                                                            $error_file[$i]['updated_date'] = $file_to_delete->getUpdatedDate();
                                                            application_log("error", "An error occurred while attempting to delete secure file for this exam. DB:" . $db->ErrorMsg());
                                                        }
                                                    } else {
                                                        $restricted_file[$i]['id'] = $file_to_delete->getID();
                                                        $restricted_file[$i]['file'] = $file_to_delete->getFileName();
                                                        $restricted_file[$i]['updated_date'] = $file_to_delete->getUpdatedDate();
                                                    }
                                                }
                                            }
                                        }

                                        //Remove the keys that are no longer present
                                        if (isset($request["secure_key_delete"]) && is_array($request["secure_key_delete"])) {
                                            $PROCESSED["secure_key_delete"] = $request["secure_key_delete"];
                                        }

                                        if (isset($PROCESSED["secure_key_delete"])) {
                                            foreach ($PROCESSED["secure_key_delete"] as $i => $secure_key_delete) {
                                                $key_to_delete = Models_Secure_AccessKeys::fetchRowByID($secure_key_delete);

                                                if ($key_to_delete) {
                                                    if ($ENTRADA_ACL->amIAllowed(new SecureAccessKeyResource($key_to_delete->getID()), "delete")) {
                                                        if ($key_to_delete->delete()) {
                                                            $deleted_keys[$i]['id'] = $key_to_delete->getID();
                                                            $deleted_keys[$i]['key'] = $key_to_delete->getKey();
                                                            $deleted_keys[$i]['version'] = $key_to_delete->getVersion();
                                                        } else {
                                                            $error_keys[$i]['id'] = $key_to_delete->getID();
                                                            $error_keys[$i]['key'] = $key_to_delete->getKey();
                                                            $error_keys[$i]['version'] = $key_to_delete->getVersion();
                                                            application_log("error", "An error occurred while attempting to delete secure keys for this exam. DB:" . $db->ErrorMsg());
                                                        }
                                                    } else {
                                                        $restricted_keys[$i]['id'] = $key_to_delete->getID();
                                                        $restricted_keys[$i]['key'] = $key_to_delete->getKey();
                                                        $restricted_keys[$i]['version'] = $key_to_delete->getVersion();
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                $next_step = 7;
                                break;
                            default :
                                add_error($translate->_("Invalid step provided"));
                                break;
                        }

                        if (!$ERROR) {
                            if (isset($PROCESSED["next_step"])) {
                                $step = $PROCESSED["next_step"];
                            } else {
                                $step = $next_step;
                            }

                            $validation_rules = $exam_controller->getValidationRules();
                            $data_with_rules = array();

                            foreach ($PROCESSED as $key => $exam_detail) {
                                $data_with_rules[$key]["value"] = $exam_detail;
                                $data_with_rules[$key]["label"] = $validation_rules[$key]["label"];
                                if ($step == 6) {
                                    $data_with_rules[$key]["display"] = $exam_controller->displayData($validation_rules[$key], $exam_detail);
                                }
                            }

                            $data = array("step" => $step, "post" => $data_with_rules, "rules" => $validation_rules, "post_id" => $post_id);

                            if (isset($previous_step)) {
                                $data["previous_step"] = $previous_step;
                            }

                            echo json_encode(array("status" => "success", "data" => $data));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                    }
                    break;
                case "update-exam-element-order" :

                    if (isset($request["element"]) && is_array($request["element"])) {
                        foreach ($request["element"] as $exam_element_id) {
                            $tmp_input = clean_input($exam_element_id, "int");
                            if ($tmp_input) {
                                $PROCESSED["exam_element_ids"][] = $tmp_input;
                            }
                        }
                    }
                    if (isset($request["type"]) && is_array($request["type"])) {
                        $tmp_input = clean_input($request["type"], "string");
                        if ($tmp_input) {
                            $PROCESSED["type"] = $tmp_input;
                        }
                    }

                    $element_order = 0;
                    if (isset($PROCESSED["exam_element_ids"]) && !empty($PROCESSED["exam_element_ids"])) {
                        foreach ($PROCESSED["exam_element_ids"] as $key => $exam_element_id) {
                            $exam_element = Models_Exam_Exam_Element::fetchRowByID($exam_element_id);
                            $exam_id = $exam_element->getExamID();
                            $group_id = $exam_element->getGroupID();

                            if ($PROCESSED["type"] == "group") {
                                $group_questions = Models_Exam_Group_Question::fetchAllByGroupID($group_id);
                                foreach($group_questions as $group_question) {}
                            }

                            if (isset($group_id) && $group_id > 0) {
                                $exam_group_elements = Models_Exam_Exam_Element::fetchAllByExamIDGroupID($exam_id, $group_id);
                                foreach ($exam_group_elements as $exam_group_element) {
                                    $exam_group_element->setOrder($element_order);
                                    $exam_group_element->update();
                                    $element_order++;
                                }
                            } else {
                                if (!$exam_element->fromArray(array("order" => $element_order))->update()) {
                                    $ERROR++;
                                }

                                $element_order++;
                                $exam_element->update();
                            }

                            $ENTRADA_LOGGER->log("", "update-exam-element-order", "exam_element_id", $exam_element->getID(), 4, __FILE__, $ENTRADA_USER->getID());
                        }

                        if ($exam_id) {
                            $exam = Models_Exam_Exam::fetchRowByID($exam_id);
                            if ($exam) {
                                $exam->setUpdatedDate(time());
                                $exam->setUpdatedBy($ENTRADA_USER->getID());
                                if (!$exam->update()) {
                                    echo json_encode(array("status" => "error", "data" => $translate->_("Failed to update exam.")));
                                }

                                $history = new Models_Exam_Creation_History(array(
                                    "exam_id" => $exam->getExamID(),
                                    "proxy_id" => $ENTRADA_USER->getID(),
                                    "action" => "exam_element_order",
                                    "action_resource_id" => NULL,
                                    "secondary_action" => NULL,
                                    "secondary_action_resource_id" => NULL,
                                    "history_message" => NULL,
                                    "timestamp" => time(),
                                ));

                                if (!$history->insert()) {
                                    echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to insert history log for Edit Exam."))));
                                }
                            }
                        }

                        if ($ERROR) {
                            echo json_encode(array("status" => "error", "data" => $translate->_("Failed to update order")));
                        } else {
                            echo json_encode(array("status" => "success", "data" => $translate->_("Exam element order successfully updated")));
                        }
                    }

                    break;
                case "update-exam-element-group-order" :

                    if (isset($request["element"]) && is_array($request["element"])) {
                        foreach ($request["element"] as $exam_element_id) {
                            $tmp_input = clean_input($exam_element_id, "int");
                            if ($tmp_input) {
                                $PROCESSED["exam_element_ids"][] = $tmp_input;
                            }
                        }
                    }
                    if (isset($request["group_id"]) && $tmp_input = clean_input($request["group_id"], "int")) {
                        if ($tmp_input) {
                            $PROCESSED["group_id"] = $tmp_input;
                        }
                    }

                    /*
                     * Update the Group Order
                     */
                    if (isset($PROCESSED["exam_element_ids"]) && !empty($PROCESSED["exam_element_ids"])) {
                        $exam_id = false;
                        foreach ($PROCESSED["exam_element_ids"] as $key => $exam_element_id) {
                            $exam_element = Models_Exam_Exam_Element::fetchRowByID($exam_element_id);
                            $group_question = Models_Exam_Group_Question::fetchRowByVersionIDGroupID($exam_element->getElementID(), $PROCESSED["group_id"]);
                            $exam_id = $exam_element->getExamID();
                            if ($group_question) {
                                $group_question->fromArray(array("order" => $key));
                                if ($group_question->update()){
                                    $ENTRADA_LOGGER->log("", "update-exam-element-group-order", "egquestion_id", $group_question->getID(), 4, __FILE__, $ENTRADA_USER->getID());
                                } else {
                                    $ERROR++;
                                }
                            }
                        }

                        /*
                         * Update the order in the exam
                         */
                        if ($exam_id) {
                            $exam_elements = Models_Exam_Exam_Element::fetchAllByExamID($exam_id);
                            if ($exam_elements) {
                                $order = 0;
                                $sorted_groups = array();
                                foreach($exam_elements as $key=>$exam_element) {
                                    if (NULL !== $exam_element->getGroupID()) {
                                        if (!in_array($exam_element->getGroupID(), $sorted_groups)) {
                                            $group_questions = Models_Exam_Group_Question::fetchAllByGroupID($exam_element->getGroupID());
                                            foreach ($group_questions as $group_question) {
                                                $exam_group_element = Models_Exam_Exam_Element::fetchRowByElementIDExamIDGroupIDElementType($group_question->getVersionID(), $exam_id, $group_question->getGroupID(), "question");
                                                if ($exam_group_element) {
                                                    $exam_group_element->setOrder($order);
                                                    if ($exam_group_element->update()) {
                                                        $ENTRADA_LOGGER->log("", "update-exam-element-group-order", "exam_element_id", $exam_group_element->getID(), 4, __FILE__, $ENTRADA_USER->getID());

                                                        $history = new Models_Exam_Creation_History(array(
                                                            "exam_id" => $exam->getExamID(),
                                                            "proxy_id" => $ENTRADA_USER->getID(),
                                                            "action" => "exam_element_group_edit",
                                                            "action_resource_id" => $group_question->getGroupID(),
                                                            "secondary_action" => "version_id",
                                                            "secondary_action_resource_id" => $group_question->getVersionID(),
                                                            "history_message" => NULL,
                                                            "timestamp" => time(),
                                                        ));

                                                        if (!$history->insert()) {
                                                            echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to insert history log for Edit Exam."))));
                                                        }

                                                    } else {
                                                        $ERROR++;
                                                        echo json_encode(array("status" => "error", "data" => $translate->_("Error updating the exam element order for element " . $exam_group_element->getElementID())));
                                                    }
                                                } else {
                                                    $ERROR++;
                                                    echo json_encode(array("status" => "error", "data" => $translate->_("Error loading the exam element " . $exam_group_element->getElementID())));
                                                }
                                                $order++;
                                            }
                                            $sorted_groups[] = $exam_element->getGroupID();
                                        }

                                    } else {
                                        $exam_element->setOrder($order);
                                        $exam_element->update();
                                        $ENTRADA_LOGGER->log("", "update-exam-element-group-order", "exam_element_id", $exam_element->getID(), 4, __FILE__, $ENTRADA_USER->getID());
                                        $order++;
                                    }
                                }
                            }
                        }
                    }

                    if ($ERROR) {
                        echo json_encode(array("status" => "error", "data" => $translate->_("Failed to update order")));
                    } else {
                        echo json_encode(array("status" => "success", "data" => $translate->_("Exam element order successfully updated")));
                    }

                    break;
                case "delete-exam-elements" :
                    if (isset($request["exam_in_progress"])) {
                        $tmp_input = clean_input(strtolower($request["exam_in_progress"]), array("int"));
                        $PROCESSED["exam_in_progress"] = $tmp_input;
                    }

                    if (isset($request["exam_id"]) && $tmp_input = clean_input($request["exam_id"], "int")) {
                        $PROCESSED["exam_id"] = $tmp_input;
                        $exam = Models_Exam_Exam::fetchRowByID($PROCESSED["exam_id"]);
                    }

                    $PROCESSED["element_ids"] = array();
                    if (isset($request["element_ids"]) && is_array($request["element_ids"])) {
                        foreach ($request["element_ids"] as $exam_element_id) {
                            $tmp_input = clean_input($exam_element_id, "int");
                            if ($tmp_input) {
                                $PROCESSED["delete_ids"][] = $tmp_input;
                            }
                        }
                    }

                    if (!empty($PROCESSED["delete_ids"])) {
                        $deleted_elements = array();
                        foreach ($PROCESSED["delete_ids"] as $exam_element_id) {
                            if (!in_array($exam_element_id, $deleted_elements)) {
                                $exam_element = Models_Exam_Exam_Element::fetchRowByID($exam_element_id);
                                if ($exam_element && is_object($exam_element)) {
                                    $exam_element->setUpdatedBy($ENTRADA_USER->getActiveID());
                                    $exam_element->setUpdatedDate(time());
                                    $exam_element->setDeletedDate(time());

                                    if (!$exam_element->update()) {
                                        $ERROR++;
                                    }

                                    $history = new Models_Exam_Creation_History(array(
                                        "exam_id" => $exam->getExamID(),
                                        "proxy_id" => $ENTRADA_USER->getID(),
                                        "action" => "exam_element_delete",
                                        "action_resource_id" => $exam_element_id,
                                        "secondary_action" => "version_id",
                                        "secondary_action_resource_id" => $exam_element->getElementID(),
                                        "history_message" => NULL,
                                        "timestamp" => time(),
                                    ));

                                    if (!$history->insert()) {
                                        echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to insert history log for Edit Exam."))));
                                    }

                                    if ($exam_element->getGroupID() != NULL && $exam_element->getGroupID() > 0) {
                                        $group_question = Models_Exam_Group_Question::fetchRowByVersionIDGroupID($exam_element->getElementID(), $exam_element->getGroupID());
                                        if ($group_question && is_object($group_question)) {
                                            $group_question->setUpdatedBy($ENTRADA_USER->getActiveID());
                                            $group_question->setUpdatedDate(time());
                                            $group_question->setDeletedDate(time());
                                            if (!$group_question->update()) {
                                                $ERROR++;
                                            }

                                            $history = new Models_Exam_Creation_History(array(
                                                "exam_id" => $exam->getExamID(),
                                                "proxy_id" => $ENTRADA_USER->getID(),
                                                "action" => "exam_element_group_delete",
                                                "action_resource_id" => $exam_element->getGroupID(),
                                                "secondary_action" => "version_id",
                                                "secondary_action_resource_id" => $exam_element->getElementID(),
                                                "history_message" => NULL,
                                                "timestamp" => time(),
                                            ));

                                            if (!$history->insert()) {
                                                echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to insert history log for Edit Exam."))));
                                            }
                                        }

                                        $deleted_elements[] = array("element" => $exam_element->getID(), "group" => $exam_element->getGroupID());
                                    } else {
                                        $deleted_elements[] = $exam_element->getID();
                                    }
                                }
                            }
                        }

                        if ($exam && is_object($exam)) {
                            $exam_elements = Models_Exam_Exam_Element::fetchAllByExamID($exam->getID(), "order");
                            if ($exam_elements && is_array($exam_elements)) {
                                $count = 0;
                                foreach ($exam_elements as $element) {
                                    if ($element && is_object($element)) {
                                        $element->setOrder($count);
                                        if (!$element->update()) {
                                            $ERROR++;
                                        } else {
                                            $count++;
                                        }
                                    }
                                }
                            }

                            $exam->setUpdatedDate(time());
                            $exam->setUpdatedBy($ENTRADA_USER->getID());
                            if (!$exam->update()) {
                                // error updating exam
                                echo json_encode(array("status" => "error", "data" => array($translate->_("Error updating exam."))));
                            }
                        }

                        $url = ENTRADA_URL . "/admin/" . $MODULE . "/exams?section=edit-exam&exam_id=" . $PROCESSED["exam_id"];

                        if (!$ERROR) {
                            echo json_encode(array("status" => "success", "data" => $deleted_elements, "exam_id" => $PROCESSED["exam_id"], "edit_url" => $url));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("Invalid exam element ID.")));
                    }

                    break;
                case "delete-exam-group-element" :
                    if (isset($request["element"]) && $tmp_input = clean_input($request["element"], "int")) {
                        if ($tmp_input) {
                            $PROCESSED["delete_id"] = $tmp_input;
                        }
                    }

                    if ($PROCESSED["delete_id"]) {
                        $deleted_elements = array();
                        if (!in_array($PROCESSED["delete_id"], $deleted_elements)) {
                            $form_element = Models_Exam_Exam_Element::fetchRowByID($PROCESSED["delete_id"]);
                            if ($form_element) {
                                $data       = array();
                                $return     = array();
                                $updated_elements = array();
                                $updated_html = array();
                                $exam       = $form_element->getExam();
                                $group_id   = $form_element->getGroupID();
                                $form_element->setUpdatedDate(time());
                                $form_element->setUpdatedBy($ENTRADA_USER->getActiveID());
                                $group_question = Models_Exam_Group_Question::fetchRowByVersionIDGroupID($form_element->getElementID(), $group_id);
                                if ($group_question && is_object($group_question)) {
                                    $order_adjusted = $group_question->getOrder() + 1;

                                    if ($group_id != NULL && $group_id > 0) {
                                        $deleted_elements[] = array(
                                            "element"   => $form_element->getID(),
                                            "group"     => $group_id
                                        );

                                        $exam_elements  = Models_Exam_Exam_Element::fetchAllByGroupID($group_id);
                                        $update_order   = false;
                                        if ($exam_elements && is_array($exam_elements) && !empty($exam_elements)) {

                                            $num_questions = count($exam_elements);

                                            foreach ($exam_elements as $key => $exam_element) {
                                                $order_removed_item = (int)$form_element->getOrder();
                                                $order_current_item = (int)$exam_element->getOrder();
                                                if ($key === 0) {
                                                    $order_of_first = $order_current_item;
                                                }
                                                if (($order_of_first + $key) < $order_removed_item ) {
                                                    $new_order = $order_current_item + 1;
                                                    $exam_element->setOrder($new_order);

                                                    if (!$exam_element->update()) {
                                                        $ERROR++;
                                                    } else {
                                                        $updated_elements[$exam_element->getExamElementID()] = $exam_element->getElementID();
                                                    }
                                                }
                                            }
                                            $form_element->setOrder($order_of_first);
                                            $updated_elements[$form_element->getExamElementID()] = $form_element->getElementID();
                                        }

                                        // remove group_id from exam_element
                                        $form_element->setGroupID(NULL);

                                        $history = new Models_Exam_Creation_History(array(
                                            "exam_id" => $exam->getExamID(),
                                            "proxy_id" => $ENTRADA_USER->getID(),
                                            "action" => "exam_element_group_delete",
                                            "action_resource_id" => $group_id,
                                            "secondary_action" => "version_id",
                                            "secondary_action_resource_id" => $form_element->getElementID(),
                                            "history_message" => NULL,
                                            "timestamp" => time(),
                                        ));

                                        if (!$history->insert()) {
                                            echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to insert history log for Edit Exam."))));
                                        }

                                    } else {
                                        $deleted_elements[] = $form_element->getID();
                                    }
                                }

                                if (!$form_element->update()) {
                                    $ERROR++;
                                }

                                //Delete from the Group
                                if ($group_question && is_object($group_question)) {
                                    $group_question->setUpdatedBy($ENTRADA_USER->getActiveID());
                                    if (!$group_question->delete()) {
                                        $ERROR++;
                                    } else {
                                        // update order for group members
                                        $group_questions = Models_Exam_Group_Question::fetchAllByGroupID($group_id);
                                        $order = 0;
                                        if ($group_questions && is_array($group_questions) && !empty($group_questions)) {
                                            foreach ($group_questions as $group_question) {
                                                if ($group_question && is_object($group_question)) {
                                                    $group_question->setOrder($order);
                                                    if (!$group_question->update()) {
                                                        $ERROR++;
                                                    } else {
                                                        $order++;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    $ERROR++;
                                }

                                if ($updated_elements && is_array($updated_elements) && !empty($updated_elements)) {
                                    foreach ($updated_elements as $key => $updated_element) {
                                        $question_version = Models_Exam_Question_Versions::fetchRowByVersionID($updated_element);
                                        if ($question_version && is_object($question_version) && $exam && is_object($exam)) {
                                            if (isset($question_version) && is_object($question_version)) {
                                                $question_view      = new Views_Exam_Question($question_version);
                                                $form_element       = Models_Exam_Exam_Element::fetchRowByID($key);
                                                $exam_element_view  = "";
                                                $list_view_html     = "";

                                                if ($form_element->getGroupID() != NULL) {
                                                    $group = Models_Exam_Group::fetchRowByID($form_element->getGroupID());
                                                    if ($group && is_object($group)) {
                                                        $group_question     = Models_Exam_Group_Question::fetchRowByVersionIDGroupID($question_version->getVersionID(), $group->getGroupID());

                                                        $data_attr_array["question-id"]         = $question_version->getQuestionID();
                                                        $data_attr_array["version-count"]       = $question_version->getVersionCount();
                                                        $data_attr_array["version-id"]          = $question_version->getID();
                                                        $data_attr_array["element-id"]          = "group-id-" . $group->getID();
                                                        $data_attr_array["sortable-element-id"] = "element_" . $form_element->getID();
                                                        $data_attr_array["group-id"]            = $group->getID();

                                                        $data_attr_element_array[$form_element->getQuestionVersion()->getID()] = $form_element->getID();
                                                        $group_view         = new Views_Exam_Group($group);
                                                        $control_array = array();

                                                        $exam_element_view   = $group_view->renderSingleGroupQuestion($question_version, $display_mode = false, $control_array, $data_attr_element_array, $group_question, "details");
                                                        $list_view_html      = $group_view->renderSingleGroupQuestion($question_version, $display_mode = false, $control_array, $data_attr_element_array, $group_question, "list");
                                                    }
                                                } else {
                                                    $data_attr_array    = $question_view->buildDataAttrArray($question_version, $form_element);
                                                    $control_array      = $question_view->buildExamHeaderEditButton($question_version, $form_element, "details", $exam->getID());
                                                    $exam_element_view  = $question_view->render(false, $control_array, $data_attr_array, "details", false);
                                                    $control_array      = $question_view->buildExamHeaderEditButton($question_version, $form_element, "list", $exam->getID());
                                                    $list_view_html     = $question_view->render(false, $control_array, $data_attr_array, "list", false);
                                                }

                                                $html = array(
                                                    "details"   => $exam_element_view,
                                                    "list"      => $list_view_html
                                                );

                                                $updated_html[$key] = $html;
                                            }
                                        }
                                    }
                                } else {
                                    $question_version = $form_element->getQuestionVersion();
                                    if ($question_version && is_object($question_version) && $exam && is_object($exam)) {
                                        if (isset($question_version) && is_object($question_version)) {
                                            $question_view = new Views_Exam_Question($question_version);

                                            $data_attr_array    = $question_view->buildDataAttrArray($question_version, $form_element);
                                            $control_array      = $question_view->buildExamHeaderEditButton($question_version, $form_element, "details", $exam->getID());
                                            $exam_element_view  = $question_view->render(false, $control_array, $data_attr_array, "details", false);
                                            $control_array      = $question_view->buildExamHeaderEditButton($question_version, $form_element, "list", $exam->getID());
                                            $list_view_html     = $question_view->render(false, $control_array, $data_attr_array, "list", false);

                                            $html = array(
                                                "details"   => $exam_element_view,
                                                "list"      => $list_view_html
                                            );

                                            $updated_html[$form_element->getExamElementID()] = $html;
                                        }
                                    }
                                }

                            } else {
                                $ERROR++;
                            }
                        }
                        if (!$ERROR) {
                            echo json_encode(array("status" => "success", "data" => $deleted_elements, "updated_html" => $updated_html));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("Error deleting the exam element ID ".$PROCESSED["delete_id"]." in the database.")));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("Invalid exam element ID.")));
                    }

                    break;
                case "remove-permission" :
                    if (isset($request["author_id"]) && $tmp_input = clean_input($request["author_id"], "int")) {
                        $PROCESSED["author_id"] = $tmp_input;
                    }

                    if ($PROCESSED["author_id"]) {

                        $author = Models_Exam_Exam_Author::fetchRowByID($PROCESSED["author_id"]);
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
                case "add-permission" :
                    if (isset($request["member_id"]) && $tmp_input = clean_input($request["member_id"], "int")) {
                        $PROCESSED["member_id"] = $tmp_input;
                    }

                    if (isset($request["member_type"]) && $tmp_input = clean_input($request["member_type"], array("trim", "striptags"))) {
                        $PROCESSED["member_type"] = $tmp_input;
                    }

                    if (isset($request["content_target"]) && $tmp_input = clean_input($request["content_target"], "int")) {
                        $PROCESSED["exam_id"] = $tmp_input;
                    }

                    if ($PROCESSED["member_id"] && $PROCESSED["member_type"] && $PROCESSED["exam_id"]) {
                        $a = Models_Exam_Exam_Author::fetchRowByExamIDAuthorIDAuthorType($PROCESSED["exam_id"], $PROCESSED["member_id"], $PROCESSED["member_type"]);
                        if ($a) {
                            if ($a->getDeletedDate()) {
                                if ($a->fromArray(array("deleted_date" => NULL))->update()) {
                                    $added++;
                                }
                            } else {
                                application_log("notice", "Exam author [".$a->getID()."] is already an active author. API should not have returned this author as an option.");
                            }
                        } else {
                            $a = new Models_Exam_Exam_Author(
                                array(
                                    "exam_id"       => $PROCESSED["exam_id"],
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
                            $author_view = new Views_Exam_Exam_Author($a);
                            if (isset($author_view)) {
                                $author_view_render = $author_view->render(0);
                            } else {
                                $author_view_render = NULL;
                            }

                            $author_array = array(
                                "status" => "success",
                                "data" => array(
                                    "author_id" => $a->getID(),
                                    "view_html" => $author_view_render
                                )
                            );

                            echo json_encode($author_array);
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to add author"))));
                        }
                    }
                    break;
                case "add-text" :
                    if (isset($request["exam_id"]) && $tmp_input = clean_input($request["exam_id"], "int")) {
                        $PROCESSED["exam_id"] = $tmp_input;
                    }
                    $success = 0;
                    if ($PROCESSED["exam_id"]) {
                        $element_data = array(
                            "exam_id"           => $PROCESSED["exam_id"],
                            "element_type"      => "text",
                            "element_text"      => "",
                            "order"             => Models_Exam_Exam_Element::fetchNextOrder($PROCESSED["exam_id"]),
                            "points"            => "NULL",
                            "allow_comments"    => "1",
                            "enable_flagging"   => "0",
                            "updated_date"      => time(),
                            "updated_by"        => $ENTRADA_USER->getActiveId()
                        );

                        $element = new Models_Exam_Exam_Element($element_data);
                        if ($element->insert()) {
                            $element_view = new Views_Exam_Exam_Element($element);
                            $list_display = $element_view->renderListDisplay();
                            $details_display = $element_view->render();
                            $success = 1;
                        }

                        $exam = $element->getExam();
                        if ($exam) {
                            $exam->setUpdatedDate(time());
                            $exam->setUpdatedBy($ENTRADA_USER->getID());
                            if (!$exam->update()) {
                                // error updating exam
                                echo json_encode(array("status" => "error", "data" => array($translate->_("Error updating exam."))));
                            }

                            $history = new Models_Exam_Creation_History(array(
                                "exam_id" => $exam->getExamID(),
                                "proxy_id" => $ENTRADA_USER->getID(),
                                "action" => "exam_element_add",
                                "action_resource_id" => $element->getID(),
                                "secondary_action" => NULL,
                                "secondary_action_resource_id" => NULL,
                                "history_message" => "Added Free Text",
                                "timestamp" => time(),
                            ));

                            if (!$history->insert()) {
                                echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to insert history log for Edit Exam."))));
                            }

                            $history = new Models_Exam_Creation_History(array(
                                "exam_id" => $exam->getExamID(),
                                "proxy_id" => $ENTRADA_USER->getID(),
                                "action" => "exam_edit",
                                "action_resource_id" => NULL,
                                "secondary_action" => NULL,
                                "secondary_action_resource_id" => NULL,
                                "history_message" => NULL,
                                "timestamp" => time(),
                            ));

                            if (!$history->insert()) {
                                echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to insert history log for Edit Exam."))));
                            }
                        }

                        if ($success) {
                            echo json_encode(array("status" => "success", "data" => array("exam_element_id" => $element->getID(), "list_display" => $list_display, "details_display" => $details_display )));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("Error creating element."))));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("Invalid form id."))));
                    }
                break;
                case "add-page-break" :
                    if (isset($request["exam_id"]) && $tmp_input = clean_input($request["exam_id"], "int")) {
                        $PROCESSED["exam_id"] = $tmp_input;
                    }
                    $success = 0;
                    if ($PROCESSED["exam_id"]) {
                        $element_data = array(
                            "exam_id" => $PROCESSED["exam_id"],
                            "element_type"      => "page_break",
                            "order"             => Models_Exam_Exam_Element::fetchNextOrder($PROCESSED["exam_id"]),
                            "points"            => "NULL",
                            "allow_comments"    => "1",
                            "enable_flagging"   => "0",
                            "updated_date"      => time(),
                            "updated_by"        => $ENTRADA_USER->getActiveId()
                        );

                        $element = new Models_Exam_Exam_Element($element_data);

                        if ($element->insert()) {
                            $element_view = new Views_Exam_Exam_Element($element);
                            $list_display = $element_view->renderListDisplay();
                            $details_display = $element_view->render();
                            $success = 1;
                        }

                        $exam = Models_Exam_Exam::fetchRowByID($PROCESSED["exam_id"]);
                        if ($exam) {
                            $exam->setUpdatedDate(time());
                            $exam->setUpdatedBy($ENTRADA_USER->getID());
                            if (!$exam->update()) {
                                // error updating exam
                                echo json_encode(array("status" => "error", "data" => array($translate->_("Error updating exam."))));
                            }

                            $history = new Models_Exam_Creation_History(array(
                                "exam_id" => $exam->getExamID(),
                                "proxy_id" => $ENTRADA_USER->getID(),
                                "action" => "exam_element_add",
                                "action_resource_id" => $element->getID(),
                                "secondary_action" => NULL,
                                "secondary_action_resource_id" => NULL,
                                "history_message" => "Added Page Break",
                                "timestamp" => time(),
                            ));

                            if (!$history->insert()) {
                                echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to insert history log for Edit Exam."))));
                            }

                            $history = new Models_Exam_Creation_History(array(
                                "exam_id" => $exam->getExamID(),
                                "proxy_id" => $ENTRADA_USER->getID(),
                                "action" => "exam_edit",
                                "action_resource_id" => NULL,
                                "secondary_action" => NULL,
                                "secondary_action_resource_id" => NULL,
                                "history_message" => NULL,
                                "timestamp" => time(),
                            ));

                            if (!$history->insert()) {
                                echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to insert history log for Edit Exam."))));
                            }
                        }

                        if ($success) {
                            echo json_encode(array("status" => "success", "data" => array("exam_element_id" => $element->getID(), "list_display" => $list_display, "details_display" => $details_display )));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("Error creating element."))));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("Invalid form id."))));
                    }
                    break;
                case "save-text-element" :
                    if (isset($request["exam_element_id"]) && $tmp_input = clean_input($request["exam_element_id"], "int")) {
                        $PROCESSED["exam_element_id"] = $tmp_input;
                    }

                    if (isset($request["element_text"]) && $tmp_input = clean_input($request["element_text"], array("trim", "allowedtags"))) {
                        $PROCESSED["element_text"] = $tmp_input;
                    }

                    if (isset($PROCESSED["element_text"]) && isset($PROCESSED["exam_element_id"])) {
                        $element = Models_Exam_Exam_Element::fetchRowByID($PROCESSED["exam_element_id"]);

                        if ($element) {
                            $exam = Models_Exam_Exam::fetchRowByID($element->getExamID());
                            if ($exam) {
                                $exam->setUpdatedDate(time());
                                $exam->setUpdatedBy($ENTRADA_USER->getID());
                                if (!$exam->update()) {
                                    // error updating exam
                                    echo json_encode(array("status" => "error", "data" => array($translate->_("Error updating exam."))));
                                }

                                $history = new Models_Exam_Creation_History(array(
                                    "exam_id" => $exam->getExamID(),
                                    "proxy_id" => $ENTRADA_USER->getID(),
                                    "action" => "exam_element_edit",
                                    "action_resource_id" => $element->getID(),
                                    "secondary_action" => NULL,
                                    "secondary_action_resource_id" => NULL,
                                    "history_message" => "Edited Free Text",
                                    "timestamp" => time(),
                                ));

                                if (!$history->insert()) {
                                    echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to insert history log for Edit Exam."))));
                                }

                                $history = new Models_Exam_Creation_History(array(
                                    "exam_id" => $exam->getExamID(),
                                    "proxy_id" => $ENTRADA_USER->getID(),
                                    "action" => "exam_edit",
                                    "action_resource_id" => NULL,
                                    "secondary_action" => NULL,
                                    "secondary_action_resource_id" => NULL,
                                    "history_message" => NULL,
                                    "timestamp" => time(),
                                ));

                                if (!$history->insert()) {
                                    echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to insert history log for Edit Exam."))));
                                }
                            }
                        }

                        if ($element->fromArray(array("element_text" => $PROCESSED["element_text"]))->update()) {
                            echo json_encode(array("status" => "success", "data" => array("exam_element_id" => $element->getID())));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to update text element"))));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("Invalid text element ID"))));
                    }
                break;
                case "save-points" :
                    if (isset($request["exam_element_id"]) && $tmp_input = clean_input($request["exam_element_id"], "int")) {
                        $PROCESSED["exam_element_id"] = $tmp_input;
                    }

                    if (isset($request["points"]) && $tmp_input = clean_input($request["points"], array("int"))) {
                        $PROCESSED["points"] = $tmp_input;
                    }

                    if (isset($PROCESSED["points"]) && $PROCESSED["points"] != "") {
                        $element = Models_Exam_Exam_Element::fetchRowByID($PROCESSED["exam_element_id"]);
                        if ($element) {
                            $exam = Models_Exam_Exam::fetchRowByID($element->getExamID());
                            if ($exam) {
                                $exam->setUpdatedDate(time());
                                $exam->setUpdatedBy($ENTRADA_USER->getID());
                                if (!$exam->update()) {
                                    // error updating exam
                                    echo json_encode(array("status" => "error", "data" => array($translate->_("Error updating exam."))));
                                }

                                $history = new Models_Exam_Creation_History(array(
                                    "exam_id" => $element->getExamID(),
                                    "proxy_id" => $ENTRADA_USER->getID(),
                                    "action" => "exam_element_points",
                                    "action_resource_id" => $element->getID(),
                                    "secondary_action" => "points",
                                    "secondary_action_resource_id" => $PROCESSED["points"],
                                    "history_message" => NULL,
                                    "timestamp" => time(),
                                ));

                                if (!$history->insert()) {
                                    echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to insert history log for Edit Exam."))));
                                }

                                $history = new Models_Exam_Creation_History(array(
                                    "exam_id" => $element->getExamID(),
                                    "proxy_id" => $ENTRADA_USER->getID(),
                                    "action" => "exam_edit",
                                    "action_resource_id" => NULL,
                                    "secondary_action" => NULL,
                                    "secondary_action_resource_id" => NULL,
                                    "history_message" => NULL,
                                    "timestamp" => time(),
                                ));

                                if (!$history->insert()) {
                                    echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to insert history log for Edit Exam."))));
                                }
                            }
                        }
                        if ($element->fromArray(array("points" => $PROCESSED["points"]))->update()) {
                            echo json_encode(array("status" => "success", "data" => array("exam_element_id" => $element->getID())));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to update point value"))));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("Invalid text element ID"))));
                    }
                    break;
                case "save-scoring" :
                    if (isset($request["exam_element_id"]) && $tmp_input = clean_input($request["exam_element_id"], "int")) {
                        $PROCESSED["exam_element_id"] = $tmp_input;
                    }

                    if (isset($request["not_scored"]) && $request["not_scored"] != "") {
                        $PROCESSED["not_scored"] = $request["not_scored"];
                    }

                    if (isset($PROCESSED["not_scored"]) && $PROCESSED["not_scored"] != "") {
                        $element = Models_Exam_Exam_Element::fetchRowByID($PROCESSED["exam_element_id"]);
                        if ($element->fromArray(array("not_scored" => $PROCESSED["not_scored"]))->update()) {

                            $history = new Models_Exam_Creation_History(array(
                                "exam_id" => $element->getExamID(),
                                "proxy_id" => $ENTRADA_USER->getID(),
                                "action" => "exam_element_points",
                                "action_resource_id" => $element->getID(),
                                "secondary_action" => "scoring",
                                "secondary_action_resource_id" => $PROCESSED["not_scored"],
                                "history_message" => NULL,
                                "timestamp" => time(),
                            ));

                            if (!$history->insert()) {
                                echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to insert history log for Edit Exam."))));
                            }

                            $history = new Models_Exam_Creation_History(array(
                                "exam_id" => $element->getExamID(),
                                "proxy_id" => $ENTRADA_USER->getID(),
                                "action" => "exam_edit",
                                "action_resource_id" => NULL,
                                "secondary_action" => NULL,
                                "secondary_action_resource_id" => NULL,
                                "history_message" => NULL,
                                "timestamp" => time(),
                            ));

                            if (!$history->insert()) {
                                echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to insert history log for Edit Exam."))));
                            }

                            echo json_encode(array("status" => "success", "data" => array("exam_element_id" => $element->getID())));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to update scoring"))));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("Invalid text element ID"))));
                    }
                    break;
                case "delete-exams" :
                    $PROCESSED["delete_ids"] = array();
                    if (isset($request["delete_ids"]) && is_array($request["delete_ids"])) {
                        foreach ($request["delete_ids"] as $exam_id) {
                            $tmp_input = clean_input($exam_id, "int");
                            if ($tmp_input) {
                                $PROCESSED["delete_ids"][] = $tmp_input;
                            }
                        }
                    }

                    if (!empty($PROCESSED["delete_ids"])) {
                        $deleted_exams = array();
                        foreach ($PROCESSED["delete_ids"] as $exam_id) {
                            $exam = Models_Exam_Exam::fetchRowByID($exam_id);
                            if ($exam) {
                                $exam->fromArray(array("deleted_date" => time(),
                                                       "updated_date" => time(),
                                                       "updated_by" => $ENTRADA_USER->getActiveID()));
                                if (!$exam->update()) {
                                    add_error($translate->_("Unable to delete an exam"));
                                } else {
                                    $ENTRADA_LOGGER->log("", "delete", "exam_id", $exam_id, 4, __FILE__, $ENTRADA_USER->getID());
                                    $deleted_exams[] = $exam_id;

                                    $history = new Models_Exam_Creation_History(array(
                                        "exam_id" => $exam->getExamID(),
                                        "proxy_id" => $ENTRADA_USER->getID(),
                                        "action" => "exam_delete",
                                        "action_resource_id" => NULL,
                                        "secondary_action" => NULL,
                                        "secondary_action_resource_id" => NULL,
                                        "history_message" => NULL,
                                        "timestamp" => time(),
                                    ));

                                    if (!$history->insert()) {
                                        echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to insert history log for Edit Exam."))));
                                    }
                                }
                            }
                        }
                        if (!$ERROR) {
                            echo json_encode(array("status" => "success", "msg" => sprintf($translate->_("Successfully deleted %d exam(s)."), count($deleted_exams)), "exam_ids" => $deleted_exams));
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("There was an error when attempting to delete an Exam.")));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "msg" => $translate->_("Nothing to delete.")));
                    }
                    break;
                case "copy-exams";
                    $PROCESSED["copy_ids"] = array();
                    if (isset($request["copy_ids"]) && is_array($request["copy_ids"])) {
                        foreach ($request["copy_ids"] as $exam_id) {
                            $tmp_input = clean_input($exam_id, "int");
                            if ($tmp_input) {
                                $PROCESSED["copy_ids"][] = $tmp_input;
                            }
                        }
                    }

                    $exams_copied = 0;
                    $exam_view_data = array();
                    $new_exam_ids = array();

                    if (!empty($PROCESSED["copy_ids"])) {
                        foreach ($PROCESSED["copy_ids"] as $exam_id) {
                            $exam = Models_Exam_Exam::fetchRowByID($exam_id);
                            if ($exam) {
                                $exam_array = $exam->toArray();
                                if (isset($exam_array) && is_array($exam_array)) {
                                    $old_exam_id = $exam_array["exam_id"];
                                    unset($exam_array["exam_id"]);
                                    $exam_array["updated_date"] = time();
                                    $exam_array["updated_by"]   = $ENTRADA_USER->getID();
                                    $exam_array["title"] = "Copy of " . $exam_array["title"];
                                    $new_exam = new Models_Exam_Exam($exam_array);
                                    if (!$new_exam->insert()) {
                                        // Error
                                        application_log("error", "Error inserting new exam, db said : " . $db->ErrorMsg());
                                    } else {
                                        $history = new Models_Exam_Creation_History(array(
                                            "exam_id" => $new_exam->getID(),
                                            "proxy_id" => $ENTRADA_USER->getID(),
                                            "action" => "exam_copy",
                                            "action_resource_id" => $old_exam_id,
                                            "secondary_action" => NULL,
                                            "secondary_action_resource_id" => NULL,
                                            "history_message" => NULL,
                                            "timestamp" => time(),
                                        ));

                                        if (!$history->insert()) {
                                            echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to insert history log for Edit Exam."))));
                                        }

                                        $new_exam_id = $new_exam->getID();
                                        $new_exam_ids[] = $new_exam_id;
                                        $exams_copied++;
                                        // Proceed with inserting the exam elements and the exam authors
                                        $exam_authors   = Models_Exam_Exam_Author::fetchAllByExamID($exam_id);
                                        $exam_elements  = Models_Exam_Exam_Element::fetchAllByExamID($exam_id);
                                        $exam_files     = Models_Exam_Exam_File::fetchAllByExamId($exam_id);

                                        if ($exam_files && is_array($exam_files)) {
                                            foreach ($exam_files as $file) {
                                                if ($file && is_object($file)) {
                                                    $file_array = $file->toArray();
                                                    unset($file_array["file_id"]);
                                                    $new_file = new Models_Exam_Exam_File($file_array);
                                                    $new_file->setExamID($new_exam_id);
                                                    if (!$new_file->insert()) {
                                                        application_log("error", "Error inserting new exam file, db said: " . $db->ErrorMsg());
                                                    } else {
                                                        $EFILE_ID = $file->getID();

                                                        if ((@is_dir(EXAM_STORAGE_PATH)) && (@is_writable(EXAM_STORAGE_PATH))) {
                                                            if (@file_exists(EXAM_STORAGE_PATH . "/" . $EFILE_ID)) {
                                                                // file exists try to copy it
                                                                $source     = EXAM_STORAGE_PATH . "/" . $EFILE_ID;
                                                                $destination = EXAM_STORAGE_PATH . "/" . $new_file->getID();
                                                                if (!copy($source, $destination)) {
                                                                    add_error("The exam file was not successfully saved. The administrators has been informed of this error, please try again later.");
                                                                }
                                                            }
                                                        } else {
                                                            add_error("The new file was not successfully saved. The administrators has been informed of this error, please try again later.");
                                                            application_log("error", "The directory is not writable for exam files.");
                                                        }
                                                    }
                                                }
                                            }
                                        }

                                        if (isset($exam_authors) && is_array($exam_authors)) {
                                            foreach ($exam_authors as $author) {
                                                $author_array = $author->toArray();
                                                if (isset($author_array) && is_array($author_array)) {
                                                    unset($author_array["aeauthor_id"]);
                                                    $author_array["exam_id"] = $new_exam_id;
                                                    $new_author = new Models_Exam_Exam_Author($author_array);
                                                    if (!$new_author->insert()) {
                                                        // Error
                                                        application_log("error", "Error inserting new exam author, db said : " . $db->ErrorMsg());
                                                    }
                                                }
                                            }
                                        }

                                        if (isset($exam_elements) && is_array($exam_elements)) {
                                            $group_link = array();
                                            foreach ($exam_elements as $element) {
                                                if ($element && is_object($element)) {
                                                    $update_group = false;
                                                    if ($element->getElementType() === "question" && $element->getGroupID() != NULL) {
                                                        $update_group = true;
                                                        // group detected, duplicate group
                                                        $group = Models_Exam_Group::fetchRowByID($element->getGroupID());
                                                        if ($group && is_object($group)) {
                                                            if (!array_key_exists($element->getGroupID(), $group_link)) {
                                                                $new_group = Models_Exam_Group::duplicateGroup($group);
                                                                $group_link[$element->getGroupID()] = $new_group->getID();
                                                            }
                                                        }
                                                    }

                                                    $element_array = $element->toArray();
                                                    if (isset($element_array) && is_array($element_array)) {
                                                        unset($element_array["exam_element_id"]);
                                                        if ($update_group) {
                                                            $new_group_id = $group_link[$element->getGroupID()];
                                                            $element_array["group_id"] = $new_group_id;
                                                        }

                                                        $element_array["exam_id"] = $new_exam_id;
                                                        $new_element = new Models_Exam_Exam_Element($element_array);
                                                        if (!$new_element->insert()) {
                                                            // Error
                                                            application_log("error", "Error inserting new exam element, db said : " . $db->ErrorMsg());
                                                        } else {
                                                            $history = new Models_Exam_Creation_History(array(
                                                                "exam_id" => $new_exam->getID(),
                                                                "proxy_id" => $ENTRADA_USER->getID(),
                                                                "action" => "exam_element_add",
                                                                "action_resource_id" => $new_element->getExamElementID(),
                                                                "secondary_action" => "version_id",
                                                                "secondary_action_resource_id" => $new_element->getElementID(),
                                                                "history_message" => NULL,
                                                                "timestamp" => time(),
                                                            ));

                                                            if (!$history->insert()) {
                                                                echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to insert history log for Edit Exam."))));
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }

                                        // Get the HTML for the new exam row
                                        $exam_view = new Views_Exam_Exam($new_exam);
                                        $exam_view_data[] = $exam_view->render();
                                    }
                                }
                            }
                        }

                        if (!$ERROR) {
                            echo json_encode(array("status" => "success", "msg" => sprintf($translate->_("Successfully copied %d exam(s)."), count($exams_copied)), "exam_view_data" => $exam_view_data, "new_exam_id" => (count($new_exam_ids) === 1 ? $new_exam_id : 0)));
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("There was an error when attempting to copy an Exam.")));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "msg" => $translate->_("Nothing to copy.")));
                    }
                    break;
                case "move-exams":

                    if (isset($request["destination_folder_id"]) && $tmp_input = clean_input($request["destination_folder_id"], array("trim", "int"))) {
                        $PROCESSED["destination_folder_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No destination folder provided."));
                    }

                    if (!$ERROR) {
                        $PROCESSED["move_ids"] = array();
                        if (isset($request["move_ids"]) && is_array($request["move_ids"])) {
                            foreach ($request["move_ids"] as $exam_id) {
                                $tmp_input = clean_input($exam_id, "int");
                                if ($tmp_input) {
                                    $PROCESSED["move_ids"][] = $tmp_input;
                                }
                            }
                        }

                        if (!empty($PROCESSED["move_ids"])) {
                            $moved_exams = array();
                            foreach ($PROCESSED["move_ids"] as $exam_id) {
                                $exam = Models_Exam_Exam::fetchRowByID($exam_id);
                                if ($exam) {

                                    $exam->fromArray(array(
                                        "folder_id" => $PROCESSED["destination_folder_id"],
                                        "updated_date" => time(),
                                        "updated_by" => $ENTRADA_USER->getActiveID()));
                                    if (!$exam->update()) {
                                        add_error($translate->_("Unable to move an exam"));
                                    } else {
                                        $ENTRADA_LOGGER->log("", "move", "exam_id", $exam_id, 4, __FILE__, $ENTRADA_USER->getID());
                                        $moved_exams[] = $exam_id;

                                        $history = new Models_Exam_Creation_History(array(
                                            "exam_id" => $exam->getExamID(),
                                            "proxy_id" => $ENTRADA_USER->getID(),
                                            "action" => "exam_move",
                                            "action_resource_id" => $PROCESSED["destination_folder_id"],
                                            "secondary_action" => NULL,
                                            "secondary_action_resource_id" => NULL,
                                            "history_message" => NULL,
                                            "timestamp" => time(),
                                        ));

                                        if (!$history->insert()) {
                                            echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to insert history log for Edit Exam."))));
                                        }
                                    }
                                }
                            }
                            if (!$ERROR) {
                                echo json_encode(array("status" => "success", "msg" => sprintf($translate->_("Successfully moved %d exam(s)."), count($moved_exams)), "exam_ids" => $moved_exams));
                            } else {
                                echo json_encode(array("status" => "error", "msg" => $translate->_("There was an error when attempting to move an Exam.")));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("Nothing to move.")));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "msg" => $translate->_("No destination folder provided.")));
                    }


                    break;
                case "set-filter-preferences" :
                    if (isset($request)) {
                        foreach ($request as $title => $filter) {
                            if (substr($title, 0, 14) === "curriculum_tag") {
                                if (isset($request[$title]) && is_array($request[$title])) {
                                    $PROCESSED["filters"]["curriculum_tag"] = array_filter($request[$title], function ($curriculum_tag) {
                                        return (int) $curriculum_tag;
                                    });
                                }
                            }
                        }
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

                    if (isset($PROCESSED["filters"])) {
                        Models_Exam_Exam::saveFilterPreferences($PROCESSED["filters"]);
                        echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully saved the selected filters")));
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

                    $pref = $_SESSION[APPLICATION_IDENTIFIER]["exams"];

                    if (isset($PROCESSED["filter_type"]) && isset($PROCESSED["filter_target"])) {
                        unset($_SESSION[APPLICATION_IDENTIFIER]["exams"]["exams"]["selected_filters"][$PROCESSED["filter_type"]][$PROCESSED["filter_target"]]);
                        if (empty($_SESSION[APPLICATION_IDENTIFIER]["exams"]["exams"]["selected_filters"][$PROCESSED["filter_type"]])) {
                            unset($_SESSION[APPLICATION_IDENTIFIER]["exams"]["exams"]["selected_filters"][$PROCESSED["filter_type"]]);
                            if (empty($_SESSION[APPLICATION_IDENTIFIER]["exams"]["exams"]["selected_filters"])) {
                                unset($_SESSION[APPLICATION_IDENTIFIER]["exams"]["exams"]["selected_filters"]);
                            }
                        }
                        preferences_update("exams", $pref);
                        echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully removed the selected filter")));
                    } else {
                        echo json_encode(array("status" => "error", "msg" => $ERRORSTR));
                    }
                    break;
                case "remove-all-filters" :

                    $pref = $_SESSION[APPLICATION_IDENTIFIER]["exams"];
                    unset($_SESSION[APPLICATION_IDENTIFIER]["exams"]["exams"]["selected_filters"]);
                    preferences_update("exams", $pref);
                    echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully removed all filters")));
                    break;
                case "reopen-progress" :
                    if (isset($request["exam_progress_id"]) && $tmp_input = clean_input($request["exam_progress_id"], array("trim", "int"))) {
                        $PROCESSED["exam_progress_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid Progress ID"));
                    }

                    if (isset($PROCESSED["exam_progress_id"])) {
                        $progress = Models_Exam_Progress::fetchRowByID($PROCESSED["exam_progress_id"]);
                        if (isset($progress) && is_object($progress)) {
                            $progress->setProgressValue("inprogress");
                            $progress->setSubmissionDate(NULL);
                            $progress->setUpdatedDate(time());
                            $progress->setUpdateBy($PROCESSED["proxy_id"]);
                            if ($progress->update()) {
                                $history = new Models_Exam_Creation_History(array(
                                    "exam_id" => $progress->getExamID(),
                                    "proxy_id" => $ENTRADA_USER->getID(),
                                    "action" => "reopen_progress",
                                    "action_resource_id" => $progress->getID(),
                                    "secondary_action" => NULL,
                                    "secondary_action_resource_id" => NULL,
                                    "history_message" => NULL,
                                    "timestamp" => time(),
                                ));

                                if (!$history->insert()) {
                                    echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to insert history log for Edit Exam."))));
                                }
                                echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully updated the re-open the progress record.")));
                            } else {
                                echo json_encode(array("status" => "error", "msg" => $translate->_("Failed to updated the re-open the progress record.")));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("This progress record could not be found.")));
                        }
                    }

                    break;
                case "delete-progress" :
                    if (isset($request["exam_progress_id"]) && $tmp_input = clean_input($request["exam_progress_id"], array("trim", "int"))) {
                        $PROCESSED["exam_progress_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid Progress ID"));
                    }

                    if (isset($PROCESSED["exam_progress_id"])) {
                        $progress = Models_Exam_Progress::fetchRowByID($PROCESSED["exam_progress_id"]);
                        if (isset($progress) && is_object($progress)) {
                            $progress->setDeleteDate(time());
                            $progress->setUpdatedDate(time());
                            $progress->setProgressValue("inprogress");
                            $progress->setUpdateBy($PROCESSED["proxy_id"]);
                            $progress->setSubmissionDate(null);
                            if ($progress->update()) {
                                $progress_view = new Views_Exam_Progress($progress);
                                $progress_view_data = $progress_view->renderAdminRow("data");
                                $history = new Models_Exam_Creation_History(array(
                                    "exam_id" => $progress->getExamID(),
                                    "proxy_id" => $ENTRADA_USER->getID(),
                                    "action" => "delete_progress",
                                    "action_resource_id" => $progress->getID(),
                                    "secondary_action" => NULL,
                                    "secondary_action_resource_id" => NULL,
                                    "history_message" => NULL,
                                    "timestamp" => time(),
                                ));

                                if (!$history->insert()) {
                                    echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to insert history log for Edit Exam."))));
                                }
                                echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully deleted the progress record."), "row_data" => $progress_view_data));
                            } else {
                                echo json_encode(array("status" => "error", "msg" => $translate->_("Failed to delete the progress record.")));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("This progress record could not be found.")));
                        }
                    }
                    break;
                case "courses-from-posts" :
                    if (isset($request["exam_id"]) && $tmp_input = clean_input($request["exam_id"], array("trim", "int"))) {
                        $PROCESSED["exam_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid Exam ID"));
                    }

                    $posts = Models_Exam_Post::fetchAllByExamIDNoPreview($PROCESSED["exam_id"]);
                    $html = "";

                    if (isset($posts) && is_array($posts) && !empty($posts)) {
                        foreach ($posts as $post) {
                            if (isset($post) && is_object($post)) {
                                $course = $post->getCourse();
                            }
                            if (isset($course) && is_object($course)) {
                                $html .= "<p>" . $course->getCourseCode() . " - " . $course->getCourseName() . "</p>";
                            }
                        }
                        echo json_encode(array("status" => "success", "html" => $html));
                    } else {
                       //no posts found
                        echo json_encode(array("status" => "error", "msg" => $translate->_("No Posts Found")));
                    }
                    break;
                case "view-preference" :
                    if (isset($request["selected_view"]) && $tmp_input = clean_input($request["selected_view"], array("trim", "striptags"))) {
                        $selected_view = $tmp_input;
                    } else {
                        add_error($translate->_("No Exam view was selected"));
                    }

                    if (!$ERROR) {
                        $_SESSION[APPLICATION_IDENTIFIER]["exams"]["exams"]["selected_view"] = $selected_view;
                        echo json_encode(array("status" => "success", "data" => array($selected_view)));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                case "attach-grouped-questions" :
                    if (isset($request["exam_id"]) && $tmp_input = clean_input($request["exam_id"], "int")) {
                        $PROCESSED["exam_id"] = $tmp_input;
                    }
                    if (isset($request["group_id"]) && $tmp_input = clean_input($request["group_id"], "int")) {
                        $PROCESSED["group_id"] = $tmp_input;
                    }
                    if (isset($request["replace"]) && $tmp_input = clean_input($request["replace"], "int")) {
                        $PROCESSED["replace"] = $tmp_input;
                    }
                    $exam = Models_Exam_Exam::fetchRowByID($PROCESSED["exam_id"]);
                    if ($exam && is_object($exam)) {
                        if ($PROCESSED["group_id"]) {
                            $exam_group = Models_Exam_Group::fetchRowByID($PROCESSED["group_id"]);
                            if ($exam_group && is_object($exam_group)) {
                                $exam_elements_group = Models_Exam_Exam_Element::fetchAllByGroupID($exam_group->getID());
                                if ($exam_elements_group && is_array($exam_elements_group) && !empty($exam_elements_group)) {
                                    // already attached to another exam so duplicate the group
                                    $exam_group = Models_Exam_Group::duplicateGroup($exam_group);
                                }

                                if ($exam_group && is_object($exam_group)) {
                                    $exam_group_questions = $exam_group->getGroupQuestions();
                                }
                            }

                            $replace_questions = array();
                            $start_order = NULL;
                            $inserted = 0;

                            if ($exam_group_questions) {
                                //Loop through and make sure none of the grouped question already exist on the exam before we start adding them
                                foreach ($exam_group_questions as $exam_group_question) {
                                    $group_question_version = $exam_group_question->getQuestionVersion();
                                    $question_version_id    = $group_question_version->getVersionID();
                                    if ($exam->hasQuestion($group_question_version)) {
                                        $replace_exam_element = Models_Exam_Exam_Element::fetchRowByExamIDElementIDElementType($exam->getID(), $question_version_id);
                                        if ($replace_exam_element) {
                                            $start_order = $replace_exam_element->getOrder();
                                            $replace_exam_element->setGroupID($exam_group->getID());
                                            $replace_exam_element->setUpdatedBy($ENTRADA_USER->GetID());
                                            $replace_exam_element->setUpdatedDate(time());
                                            $replace_exam_element->setOrder($start_order);
                                            $replace_exam_element->update();
                                            $start_order++;
                                            $inserted++;
                                        }
                                    } else {
                                        $exam_element_data = array(
                                            "element_type" => "question",
                                            "element_id" => $exam_group_question->getQuestionVersion()->getVersionID(),
                                            "group_id" => $exam_group->getID(),
                                            "points" => 1,
                                            "allow_comments" => 0,
                                            "enable_flagging" => 0,
                                            "updated_date" => time(),
                                            "updated_by" => $ENTRADA_USER->GetID()
                                        );
                                        $exam_element = new Models_Exam_Exam_Element($exam_element_data);
                                        try {
                                            $exam->getExamElements();
                                            if ($exam->addElement($exam_element, $start_order)) {
                                                $inserted++;
                                                $SUCCESS++;
                                                $start_order++;
                                            }
                                        } catch(Exception $e) {
                                            $ERROR++;
                                            add_error($translate->_("One of the grouped questions could not be added to the form: ".$e->getMessage()));
                                            echo json_encode(array("status" => "error", "data" => $translate->_("One of the grouped questions <strong>(ID: ".$exam_element->getQuestionID(). " / Ver: ".$exam_element->getVersionCount().")</strong> could not be added to the form: <br /> ".$e->getMessage())));
                                        }
                                    }
                                }

                                if (!$ERROR) {

                                    $history = new Models_Exam_Creation_History(array(
                                        "exam_id" => $exam->getExamID(),
                                        "proxy_id" => $ENTRADA_USER->getID(),
                                        "action" => "exam_element_group_add",
                                        "action_resource_id" => $exam_group_question->getGroupID(),
                                        "secondary_action" => "version_id",
                                        "secondary_action_resource_id" => $exam_group_question->getQuestionVersion()->getVersionID(),
                                        "history_message" => NULL,
                                        "timestamp" => time(),
                                    ));

                                    if (!$history->insert()) {
                                        echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to insert history log for Edit Exam."))));
                                    }

                                    Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Successfully added the group <strong>" . $exam_group->getGroupTitle() . "</strong> containing <strong>%d</strong> questions to the exam."), $inserted), "success", "exams");
                                    echo json_encode(array("status" => "success", "data" => $translate->_("Successfully added the group to the exam")));
                                }
                            } else {
                                $ERROR++;
                                echo json_encode(array("status" => "error", "data" => array($translate->_("The question group <strong>" . $exam_group->getGroupTitle() . "</strong> was not added to the exam because it is empty!"))));
                            }
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("Invalid form id."))));
                    }
                    break;
                case "build-question-answers" :
                    if (isset($request["exam_mode"]) && $request["exam_mode"] == "false") {
                        $PROCESSED["exam_mode"] = false;
                    } else {
                        $PROCESSED["exam_mode"] = true;
                    }

                    if (isset($request["element_id"]) && $tmp_input = clean_input($request["element_id"], "int")) {
                        $PROCESSED["element_id"] = $tmp_input;
                    }

                    if (isset($request["question"]) && $tmp_input = $request["question"]) {
                        $PROCESSED["question"] = $tmp_input;
                    } else {
                        add_error($translate->_("A problem occurred while attempting to fetch data for this question. Please try again later"));
                    }

                    if (isset($request["version_id"]) && $tmp_input = clean_input(strtolower($request["version_id"]), array("trim", "int"))) {
                        $PROCESSED["version_id"] = $tmp_input;
                    } else {
                        //uses the question ID to generate the latest revision ID
                        $versions_id = Models_Exam_Question_Versions::getLatestVersionByQuestionID($PROCESSED["question"]["question_id"]);
                        $PROCESSED["version_id"] = $versions_id;
                    }

                    if (!$ERROR) {
                        $question_version   = Models_Exam_Question_Versions::fetchRowByQuestionID($PROCESSED["question"]["question_id"], $PROCESSED["version_id"]);
                        $exam_element       = Models_Exam_Exam_Element::fetchRowByID($PROCESSED["element_id"]);
                        if ($exam_element) {
                            $exam_element->setUpdatedBy($ENTRADA_USER->getID());
                            $exam_element->setUpdatedDate(time());
                            $exam_element->setElementID($PROCESSED["version_id"]);
                            if ($exam_element->update()) {
                                $history = new Models_Exam_Creation_History(array(
                                    "exam_id" => $exam_element->getExamID(),
                                    "proxy_id" => $ENTRADA_USER->getID(),
                                    "action" => "exam_element_edit",
                                    "action_resource_id" => $PROCESSED["element_id"],
                                    "secondary_action" => "version_id",
                                    "secondary_action_resource_id" => $PROCESSED["version_id"],
                                    "history_message" => NULL,
                                    "timestamp" => $exam_element->getUpdatedDate(),
                                ));

                                if (!$history->insert()) {
                                    echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to insert history log for Edit Exam."))));
                                }

                                $exam = $exam_element->getExam();
                                if ($exam) {
                                    $exam->setUpdatedDate(time());
                                    $exam->setUpdatedBy($ENTRADA_USER->getID());
                                    if (!$exam->update()) {
                                        // error updating exam
                                        echo json_encode(array("status" => "error", "data" => array($translate->_("Error updating exam."))));
                                    }

                                    $history = new Models_Exam_Creation_History(array(
                                        "exam_id" => $exam_element->getExamID(),
                                        "proxy_id" => $ENTRADA_USER->getID(),
                                        "action" => "exam_edit",
                                        "action_resource_id" => NULL,
                                        "secondary_action" => NULL,
                                        "secondary_action_resource_id" => NULL,
                                        "history_message" => NULL,
                                        "timestamp" => time(),
                                    ));

                                    if (!$history->insert()) {
                                        echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to insert history log for Edit Exam."))));
                                    }
                                }
                            } else {
                                // error updating exam element
                                echo json_encode(array("status" => "error", "data" => array($translate->_("Error updating exam element."))));
                            }
                        }
                        $question_view      = new Views_Exam_Question($question_version);
                        if ($question_view) {
                            $data = array();

                            $data_attr_array    = $question_view->buildDataAttrArray($question_version, $exam_element);
                            $control_array      = $question_view->buildExamHeaderEditButton($question_version, $exam_element, "details");
                            $question_details   = $question_view->render(false, $control_array, $data_attr_array, "details", false);

                            $control_array      = $question_view->buildExamHeaderEditButton($question_version, $exam_element, "list");
                            $question_list      = $question_view->render(false, $control_array, $data_attr_array, "list", false);

                            if ($question_details && $question_list) {
                                $data["html_details"]   = $question_details;
                                $data["html_list"]      = $question_list;
                                echo json_encode(array("status" => "success", "data" => $data));
                            } else {
                                echo json_encode(array("status" => "error", "data" => array($translate->_("Error rendering view for this question."))));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("This question has no answers."))));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("Error displaying view for this question."))));
                    }
                    break;
                case "update-group-question" :
                    if (isset($request["exam_mode"]) && $request["exam_mode"] == "false") {
                        $PROCESSED["exam_mode"] = false;
                    } else {
                        $PROCESSED["exam_mode"] = true;
                    }

                    if (isset($request["element_id"]) && $tmp_input = clean_input($request["element_id"], "int")) {
                        $PROCESSED["element_id"] = $tmp_input;
                    }

                    if (isset($request["question"]) && $tmp_input = $request["question"]) {
                        $PROCESSED["question"] = $tmp_input;
                    } else {
                        add_error($translate->_("A problem occurred while attempting to fetch data for this question. Please try again later"));
                    }

                    if (isset($request["version_id"]) && $tmp_input = clean_input(strtolower($request["version_id"]), array("trim", "int"))) {
                        $PROCESSED["version_id"] = $tmp_input;
                    } else {
                        //uses the question ID to generate the latest revision ID
                        $versions_id = Models_Exam_Question_Versions::getLatestVersionByQuestionID($PROCESSED["question"]["question_id"]);
                        $PROCESSED["version_id"] = $versions_id;
                    }

                    if (isset($request["group_id"]) && $tmp_input = clean_input($request["group_id"], "int")) {
                        $PROCESSED["group_id"] = $tmp_input;
                    }

                    if (!$ERROR) {
                        $question_version   = Models_Exam_Question_Versions::fetchRowByQuestionID($PROCESSED["question"]["question_id"], $PROCESSED["version_id"]);
                        $exam_element       = Models_Exam_Exam_Element::fetchRowByID($PROCESSED["element_id"]);
                        if ($exam_element && is_object($exam_element)) {
                            $original_version_id = $exam_element->getElementID();
                            $exam_element->setUpdatedBy($ENTRADA_USER->getID());
                            $exam_element->setUpdatedDate(time());
                            $exam_element->setElementID($PROCESSED["version_id"]);
                            if ($exam_element->update()) {
                                $exam = $exam_element->getExam();
                                if ($exam) {
                                    $exam->setUpdatedDate(time());
                                    $exam->setUpdatedBy($ENTRADA_USER->getID());
                                    if (!$exam->update()) {
                                        // error updating exam
                                        echo json_encode(array("status" => "error", "data" => array($translate->_("Error updating exam."))));
                                    }

                                    $history = new Models_Exam_Creation_History(array(
                                        "exam_id" => $exam_element->getExamID(),
                                        "proxy_id" => $ENTRADA_USER->getID(),
                                        "action" => "exam_edit",
                                        "action_resource_id" => NULL,
                                        "secondary_action" => NULL,
                                        "secondary_action_resource_id" => NULL,
                                        "history_message" => NULL,
                                        "timestamp" => time(),
                                    ));

                                    if (!$history->insert()) {
                                        echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to insert history log for Edit Exam."))));
                                    }
                                }

                                if ($PROCESSED["group_id"]) {
                                    $group_question = Models_Exam_Group_Question::fetchRowByVersionIDGroupID($original_version_id, $PROCESSED["group_id"]);
                                    if ($group_question && is_object($group_question)) {
                                        $group_question->setVersionId($PROCESSED["version_id"]);
                                        $group_question->setUpdatedBy($ENTRADA_USER->getID());
                                        $group_question->setUpdatedDate(time());
                                        if (!$group_question->update()) {
                                            // error updating exam
                                            echo json_encode(array("status" => "error", "data" => array($translate->_("Error updating group question."))));
                                        }

                                        $history = new Models_Exam_Creation_History(array(
                                            "exam_id" => $exam_element->getExamID(),
                                            "proxy_id" => $ENTRADA_USER->getID(),
                                            "action" => "exam_element_group_edit",
                                            "action_resource_id" => $PROCESSED["group_id"],
                                            "secondary_action" => "version_id",
                                            "secondary_action_resource_id" => $group_question->getVersionID(),
                                            "history_message" => NULL,
                                            "timestamp" => time(),
                                        ));

                                        if (!$history->insert()) {
                                            echo json_encode(array("status" => "error", "data" => array($translate->_("Failed to insert history log for Edit Exam."))));
                                        }
                                    }
                                }

                            } else {
                                // error updating exam element
                                echo json_encode(array("status" => "error", "data" => array($translate->_("Error updating exam element."))));
                            }
                        }

                        $question_view      = new Views_Exam_Question($question_version);
                        if ($question_view && is_object($question_version) && $exam_element && is_object($exam_element)) {
                            $data = array();

                            $group = Models_Exam_Group::fetchRowByID($PROCESSED["group_id"]);
                            if ($group && is_object($group)) {
                                $data_attr_array["question-id"]         = $question_version->getQuestionID();
                                $data_attr_array["version-count"]       = $question_version->getVersionCount();
                                $data_attr_array["version-id"]          = $question_version->getID();
                                $data_attr_array["element-id"]          = "group-id-" . $group->getID();
                                $data_attr_array["sortable-element-id"] = "element_" . $exam_element->getID();
                                $data_attr_array["group-id"]            = $group->getID();

                                $data_attr_element_array[$exam_element->getQuestionVersion()->getID()] = $exam_element->getID();
                                $group_view         = new Views_Exam_Group($group);
                                $control_array = array();

                                $question_details   = $group_view->renderSingleGroupQuestion($question_version, $display_mode = false, $control_array, $data_attr_element_array, $group_question, "details");
                                $question_list      = $group_view->renderSingleGroupQuestion($question_version, $display_mode = false, $control_array, $data_attr_element_array, $group_question, "list");
                            }

                            if ($question_details && $question_list) {
                                $data["html_details"]   = $question_details;
                                $data["html_list"]      = $question_list;
                                echo json_encode(array("status" => "success", "data" => $data));
                            } else {
                                echo json_encode(array("status" => "error", "data" => array($translate->_("Error rendering view for this question."))));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("This question has no answers."))));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($translate->_("Error displaying view for this question."))));
                    }
                    break;
                case "post-preview":
                    if (isset($request["exam_id"]) && $tmp_input = clean_input($request["exam_id"], "int")) {
                        $PROCESSED["exam_id"] = $tmp_input;
                    }
                    if (isset($request["backtrack"]) && $tmp_input = clean_input($request["backtrack"], "int")) {
                        $PROCESSED["backtrack"] = $tmp_input;
                    }
                    if (isset($request["secure"]) && $tmp_input = clean_input($request["secure"], "int")) {
                        $PROCESSED["secure"] = $tmp_input;
                    }
                    if (isset($request["score_review"]) && $tmp_input = clean_input($request["score_review"], "int")) {
                        $PROCESSED["score_review"] = $tmp_input;
                    }
                    if (isset($request["feedback_review"]) && $tmp_input = clean_input($request["feedback_review"], "int")) {
                        $PROCESSED["feedback_review"] = $tmp_input;
                    }
                    if (isset($request["max_attempts"]) && $tmp_input = clean_input($request["max_attempts"], "int")) {
                        $PROCESSED["max_attempts"] = $tmp_input;
                    }

                    $PROCESSED["target_type"]               = "preview";
                    $PROCESSED["mandatory"]                 = 0;
                    $PROCESSED["mark_faculty_review"]       = 0;
                    $PROCESSED["hide_exam"]                 = 0;
                    $PROCESSED["auto_save"]                 = 0;
                    $PROCESSED["auto_submit"]               = 0;
                    $PROCESSED["use_time_limit"]            = 0;
                    $PROCESSED["use_exam_start_date"]       = 0;
                    $PROCESSED["use_exam_end_date"]         = 0;
                    $PROCESSED["use_exam_submission_date"]  = 0;
                    $PROCESSED["use_re_attempt_threshold"]  = 0;
                    $PROCESSED["score_start_time"]          = time();
                    $PROCESSED["score_end_date"]            = strtotime("+1 week");
                    $PROCESSED["feedback_start_date"]       = time();
                    $PROCESSED["feedback_end_date"]         = strtotime("+1 week");
                    $PROCESSED["created_date"]              = time();
                    $PROCESSED["updated_date"]              = time();
                    $PROCESSED["created_by"]                = $ENTRADA_USER->getID();
                    $PROCESSED["updated_by"]                = $ENTRADA_USER->getID();

                    $exam = Models_Exam_Exam::fetchRowByID($PROCESSED["exam_id"]);
                    if ($exam && is_object($exam)) {
                        $PROCESSED["target_id"] = $exam->getID() . "_" . time();

                        $exam_post = new Models_Exam_Post($PROCESSED);
                        if (!$exam_post->insert()) {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("Error creating post for this exam."))));
                        } else {
                            echo json_encode(array("status" => "success"));
                        }
                    }

                    break;
                case "preview-settings":
                    $exam_id = $use_calculator = $use_self_timer = null;

                    if (isset($request["exam_id"]) && $tmp_input = clean_input($request["exam_id"], "int")) {
                        $exam_id = $tmp_input;
                    }

                    if (isset($request["use_calculator"])) {
                        $use_calculator = $request["use_calculator"] == 'true' ? true : false;
                    }

                    if (isset($request["use_self_timer"])) {
                        $use_self_timer = $request["use_self_timer"] == 'true' ? true : false;
                    }

                    // We have to have the exam_id and at least one of the settings must be changed.
                    if ($exam_id && $exam_id >= 1 && ($use_calculator !== null || $use_self_timer !== null)) {
                        $post = Models_Exam_Post::fetchRowByExamIDType($exam_id, "preview");

                        if ($post) {
                            if ($use_calculator !== null) {
                                $post->setUseCalculator($use_calculator);
                            }

                            if ($use_self_timer !== null) {
                                $post->setUseSelfTimer($use_self_timer);
                            }

                            if (! $post->update()) {
                                echo json_encode(array(
                                    "status" => "error",
                                    "data" => array($translate->_("Error updating preview settings."))
                                ));
                            }

                            echo json_encode(array(
                                "status" => "success",
                                "use_calculator" => (bool) $post->getUseCalculator(),
                                "use_self_timer" => (bool) $post->getUseSelfTimer(),
                            ));
                        } else {
                            echo json_encode(array(
                                "status" => "error",
                                "data" => "Post not found.",
                            ));
                        }

                    } else {
                        echo json_encode(array(
                            "status" => "error",
                            "data" => "Invalid params.",
                        ));
                    }

                    break;
                case "add-file":
                    if (isset($request["upload"]) && $tmp_input = clean_input($request["upload"], array("trim", "alpha"))) {
                        $PROCESSED["upload"] = "upload";
                    }

                    if (isset($request["exam_id"]) && $tmp_input = clean_input($request["exam_id"], array("trim", "int"))) {
                        $PROCESSED["exam_id"] = $tmp_input;
                    } else {
                        add_error("Invalid Exam ID supplied.");
                    }

                    if (isset($request["file_id"]) && $tmp_input = clean_input($request["file_id"], array("trim", "int"))) {
                        $PROCESSED["file_id"] = $tmp_input;
                    }

                    if (isset($PROCESSED["exam_id"])) {
                        if (isset($request["exam_files_file_title_value"]) && $tmp_input = clean_input($request["exam_files_file_title_value"], array("trim", "striptags"))) {
                            if ($tmp_input != "undefined") {
                                $PROCESSED["file_title"] = $tmp_input;
                            } else {
                                $PROCESSED["file_title"] = "";
                            }
                        } else {
                            $PROCESSED["file_title"] = "";
                        }
                    } else {
                        $PROCESSED["file_title"] = "";
                    }

                    if (!has_error()) {
                        if (isset($PROCESSED["file_id"])) {

                        } else {
                            $method = "insert";
                        }

                        if (isset($_FILES["file"]["name"])) {
                            switch ($_FILES["file"]["error"]) {
                                case 0 :
                                    $PROCESSED["file_size"] = (int) trim($_FILES["file"]["size"]);
                                    $PROCESSED["file_name"] = useable_filename(trim($_FILES["file"]["name"]));
                                    $PROCESSED["file_type"] = trim($_FILES["file"]["type"]);

                                    $finfo                  = new finfo(FILEINFO_MIME);
                                    $type                   = $finfo->file($_FILES["file"]["tmp_name"]);
                                    $type_array             = explode(";", $type);
                                    $mimetype               = $type_array[0];

                                    $PROCESSED["file_mimetype"]		= strtolower(trim($_FILES["file"]["type"]));

                                    switch ($PROCESSED["file_mimetype"]) {
                                        case "application/x-forcedownload":
                                        case "application/x-download":
                                        case "application/octet-stream":
                                        case "\"application/octet-stream\"":
                                        case "application/download":
                                        case "application/force-download":
                                            $PROCESSED["file_mimetype"] = $mimetype;
                                        break;
                                    }
                                    $VALID = array(
                                        "application/pdf"
                                    );

                                    if (!in_array($PROCESSED["file_mimetype"], $VALID)) {
                                        add_error("The provided file was not a valid file type. Please make sure your file is a PDF.");
                                    }

                                    break;
                                case 1 :
                                case 2 :
                                    add_error("The uploaded file exceeds the allowed file size limit.");
                                    break;
                                case 3 :
                                    add_error("The file that uploaded did not complete the upload process or was interrupted. Please try again.");
                                    break;
                                case 4 :
                                    add_error("You did not select a file on your computer to upload. Please select a local file.");
                                    break;
                                case 5 :
                                    add_error("A problem occurred while attempting to upload the file; the Administrator has been informed of this error, please try again later.");
                                    break;
                                case 6 :
                                case 7 :
                                    add_error("Unable to store the new file on the server; the Administrator has been informed of this error, please try again later.");
                                    break;
                            }
                        } else {
                            add_error("You did not select a file on your computer to upload. Please select a local file.");
                        }

                        if (!has_error()) {
                            $PROCESSED["updated_date"]  = time();
                            $PROCESSED["updated_by"]    = $ENTRADA_USER->getID();


                            $file = new Models_Exam_Exam_File($PROCESSED);

                            if ($file->$method()) {
                                last_updated("event", $PROCESSED["event_id"]);

                                $EFILE_ID = $file->getID();

                                if ((@is_dir(EXAM_STORAGE_PATH)) && (@is_writable(EXAM_STORAGE_PATH))) {
                                    if (@file_exists(EXAM_STORAGE_PATH . "/" . $EFILE_ID)) {
                                        application_log("notice", "File ID [" . $EFILE_ID . "] already existed and was overwritten with newer file.");
                                    }

                                    if (@move_uploaded_file($_FILES["file"]["tmp_name"], EXAM_STORAGE_PATH . "/" . $EFILE_ID)) {
                                        application_log("success", "File ID " . $EFILE_ID . " was successfully added to the database and filesystem for event [" . $PROCESSED["event_id"] . "].");
                                    } else {
                                        add_error("The new file was not successfully saved. The administrators has been informed of this error, please try again later.");
                                        application_log("error", "The move_uploaded_file function failed to move temporary file over to final location.");
                                    }
                                } else {
                                    add_error("The new file was not successfully saved. The administrators has been informed of this error, please try again later.");
                                    application_log("error", "The directory is not writable for exam files.");
                                }

                                if (!has_error()) {
                                    if (isset($PROCESSED["upload"])) {
                                        if ($method == "insert") {
                                            history_log($PROCESSED["event_id"], "added " . ($PROCESSED["file_title"] == "" ? $PROCESSED["file_name"] : $PROCESSED["file_title"]) . " exam file.", $ENTRADA_USER->getID());
                                        } else {
                                            history_log($PROCESSED["event_id"], "updated " . ($PROCESSED["file_title"] == "" ? $PROCESSED["file_name"] : $PROCESSED["file_title"]) . " exam file.", $ENTRADA_USER->getID());
                                        }

                                        add_success("Successfully Saved file.");
                                    } else {
                                        if ($method == "insert") {
                                            history_log($PROCESSED["event_id"], "added " . ($PROCESSED["file_title"] == "" ? $PROCESSED["file_name"] : $PROCESSED["file_title"]) . " exam file.", $ENTRADA_USER->getID());
                                        } else {
                                            history_log($PROCESSED["event_id"], "updated " . ($PROCESSED["file_title"] == "" ? $PROCESSED["file_name"] : $PROCESSED["file_title"]) . " exam file.", $ENTRADA_USER->getID());
                                        }

                                        $PROCESSED["next_step"] = 2;
                                        application_log("success", "Successfully added Event Resource " . $file->getID() . " to event " . $PROCESSED["event_id"]);
                                        //now we update the other recurring events

                                        if (!has_error()) {
                                            echo json_encode(array("status" => "success", "data" => array("next_step" => $PROCESSED["next_step"])));
                                        } else {
                                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                        }
                                    }
                                } else {
                                    echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                }
                            }
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }

                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                case "delete-file":
                    if (isset($request["file_id"]) && $tmp_input = clean_input($request["file_id"], array("trim", "int"))) {
                        $PROCESSED["file_id"] = $tmp_input;
                    } else {
                        add_error("Invalid File ID supplied.");
                    }

                    if (!has_error()) {
                        $file = Models_Exam_Exam_File::fetchRowByID($PROCESSED["file_id"]);
                        if ($file && is_object($file)) {
                            $file->setDeletedDate(time());
                            $file->setUpdatedBy($ENTRADA_USER->getID());
                            $file->setUpdatedDate(time());
                            if (!$file->update()) {
                                add_error("Error Deleting file");
                                echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                            } else {
                                echo json_encode(array("status" => "success", "data" => array("Successfully deleted exam file.")));
                            }
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                case "update-category-scoring":
                    if (isset($request["post_id"]) && $tmp_input = clean_input(strtolower($request["post_id"]), array("trim", "int"))) {
                        $PROCESSED["post_id"] = $tmp_input;
                    } else {
                        $PROCESSED["post_id"] = 0;
                    }

                    $post = Models_Exam_Post::fetchRowByID($PROCESSED["post_id"]);
                    if ($post && is_object($post)) {
                        $scored = $post->scoreCategories();
                        if ($scored === true) {
                            echo json_encode(array("status" => "success"));
                        } else {
                            echo json_encode(array("status" => "error", "data" => "Error updating category score"));
                        }
                    }
                    break;
                case "save-grader-settings":
                    if (isset($request["post_id"]) && $tmp_input = clean_input(strtolower($request["post_id"]), array("trim", "int"))) {
                        $PROCESSED["post_id"] = $tmp_input;
                    } else {
                        $PROCESSED["post_id"] = 0;
                    }
                    if (isset($request["graders"]) && $PROCESSED["post_id"]) { 
                        $ret = false;
                        $ret = Models_Exam_Grader::deleteAllGraderGroupsByPost($PROCESSED["post_id"]);

                        if ($ret) {
                            foreach ($request["graders"] as $grader_id) {
                                if (isset($request["grader_".$grader_id])) {
                                    // syncGroup($post_id, $cgroup_id, $new_grader_proxy_ids)
                                    $ret = Models_Exam_Grader::syncGroupsGrader($PROCESSED["post_id"], $request["grader_".$grader_id], $grader_id);
                                    if (!$ret) break;
                                }
                            }       
                        }

                        if ($ret) {
                            echo json_encode(array("status" => "success"));
                        } else {
                            echo json_encode(array("status" => "error", "data" => "Error updating learners to grader settings."));
                        }

                    } else {
                        echo json_encode(array("status" => "error", "data" => "Error reading post data"));
                    }
                    break;
                case "delete-all-grader-settings":
                    if (isset($request["post_id"]) && $tmp_input = clean_input(strtolower($request["post_id"]), array("trim", "int"))) {
                        $PROCESSED["post_id"] = $tmp_input;
                        $ret = false;
                        $ret = Models_Exam_Grader::deleteAllGraderGroupsByPost($PROCESSED["post_id"]);

                        if ($ret) {
                            echo json_encode(array("status" => "success"));
                        } else {
                            echo json_encode(array("status" => "error", "data" => "Error updating learners to grader settings."));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => "Error reading post data"));
                    }
                    break;
                default:
                    echo json_encode(array("status" => "error", "data" => $translate->_("Invalid POST method.")));
                break;
            }
        break;
        case "GET" :
            switch ($request["method"]) {
                case "get-exams" :
                    $PROCESSED["filters"] = array();
                    if (isset($_SESSION[APPLICATION_IDENTIFIER]["exams"]["exams"]["selected_filters"])) {
                        $PROCESSED["filters"] = $_SESSION[APPLICATION_IDENTIFIER]["exams"]["exams"]["selected_filters"];
                    }

                    if ($_SESSION[APPLICATION_IDENTIFIER]["exams"]["exams"]["sub_folder_search"] == "on") {
                        $PROCESSED["sub_folder_search"] = 1;
                    } else {
                        $PROCESSED["sub_folder_search"] = 0;
                    }

                    if (isset($request["search_term"]) && $tmp_input = clean_input(strtolower($request["search_term"]), array("trim", "striptags"))) {
                        $PROCESSED["search_term"] = "%".$tmp_input."%";
                    } else {
                        $PROCESSED["search_term"] = "";
                    }

                    if (isset($request["folder_id"]) && $tmp_input = clean_input(strtolower($request["folder_id"]), array("trim", "int"))) {
                        $PROCESSED["folder_id"] = $tmp_input;
                    } else {
                        $PROCESSED["folder_id"] = 0;
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
                        $PROCESSED["sort_direction"] = "DESC";
                    }
                    
                    if (isset($request["sort_column"]) && $tmp_input = clean_input(strtolower($request["sort_column"]), array("trim", "int"))) {
                        $PROCESSED["sort_column"] = $tmp_input;
                    } else {
                        $PROCESSED["sort_column"] = "exam_id";
                    }

                    $exams          = Models_Exam_Exam::fetchAllRecordsBySearchTerm($PROCESSED["search_term"], $PROCESSED["limit"], $PROCESSED["offset"], $PROCESSED["sort_direction"], null, $PROCESSED["filters"], $PROCESSED["folder_id"], $PROCESSED["sub_folder_search"]);
                    $total_exams    = Models_Exam_Exam::countAllRecordsBySearchTerm($PROCESSED["search_term"], $PROCESSED["filters"], $PROCESSED["folder_id"], $PROCESSED["sub_folder_search"]);

                    /*
                     * Breadcrumb section
                     */
                    $return = array();
                    $path   = array();
                    $folder = Models_Exam_Bank_Folders::fetchRowByID($PROCESSED["folder_id"]);

                    if (isset($folder) && is_object($folder)) {
                        $breadcrumbs_html = $folder->getBreadcrumbsByFolderID();
                        if ($breadcrumbs_html) {
                            $return["status_breadcrumbs"] = "success";
                            $return["breadcrumb_data"] = $breadcrumbs_html;
                        } else {
                            $return["status_breadcrumbs"] = "error";
                            $return["status_breadcrumbs_error"] = $translate->_("Error fetching breadcrumbs for this folder(". $PROCESSED["folder_id"].")");
                        }
                    } else if ($PROCESSED["folder_id"] == 0) {
                        $index_folder = new Models_Exam_Bank_Folders(array(
                            "folder_id" => 0,
                            "parent_folder_id" => 0,
                            "folder_title" => "Index",
                            "folder_type" => "exam"
                        ));

                        $breadcrumbs_html = $index_folder->getBreadcrumbsByFolderID();
                        if ($breadcrumbs_html) {
                            $return["status_breadcrumbs"] = "success";
                            $return["breadcrumb_data"] = $breadcrumbs_html;
                        } else {
                            $return["status_breadcrumbs"] = "success";
                            $return["status_breadcrumbs_error"] = $translate->_("Error fetching breadcrumbs for this folder.");
                        }

                    } else {
                        $return["status_breadcrumbs"] = "error";
                        $return["status_breadcrumbs_error"] = $translate->_("Error fetching data for this folder.");
                    }

                    /*
                     * Title section
                     */
                    if (isset($folder) && is_object($folder)) {
                        $title = $folder->getFolderTitle();
                        $parent_parent_folder = $folder->getParentFolderID();
                        $return["title"] = $title;
                    } else {
                        $return["title"] = "Index";
                        $parent_parent_folder = 0;
                    }

                    /**
                     * Sub folder section
                     */
                    $subfolder_html = "";
                    $folders = Models_Exam_Bank_Folders::fetchAllByParentID($PROCESSED["folder_id"], "exam");
                    if (isset($folders) && is_array($folders) && !empty($folders)) {
                        $subfolder_html .= "<ul id=\"folder_ul\">";
                        $folder_count = count($folders);
                        foreach ($folders as $key => $folder) {
                            if (isset($folder) && is_object($folder)) {
                                if ($key === 0 && $PROCESSED["folder_id"] != 0) {
                                    $subfolder_html .= Views_Exam_Bank_Folder::renderBackNavigation($parent_parent_folder);
                                }
                                $folder_view = new Views_Exam_Bank_Folder($folder);
                                $subfolder_html .= $folder_view->render();
                                if ($folder_count === $key + 1) {
                                    $subfolder_html .= "</ul>";
                                }
                            }
                        }
                        $return["status_folder"] = "success";
                        $return["subfolder_html"] = $subfolder_html;
                    } else {
                        $subfolder_html .= "<ul>";
                        $subfolder_html .= Views_Exam_Bank_Folder::renderBackNavigation($parent_parent_folder);
                        $subfolder_html .= "</ul>";
                        $return["status_folder"] = "success";
                        $return["subfolder_html"] = $subfolder_html;
                    }

                    /*
                     * Folder sort check
                     */

                    if (!$ENTRADA_ACL->amIAllowed("examfolder", "update", true)) {
                        $edit_folder = $ENTRADA_ACL->amIAllowed(new ExamFolderResource($PROCESSED["folder_id"], true), "update");
                        if ($edit_folder) {
                            $return["edit_folder"] = 1;
                        } else {
                            $return["edit_folder"] = 0;
                        }
                    } else {
                        $return["edit_folder"] = 1;
                    }

                    if ($exams) {
                        $data = array();
                        foreach ($exams as $exam) {
                            $exam_model = Models_Exam_Exam::fetchRowByID($exam["exam_id"]);
                            if (isset($exam_model) && is_object($exam_model)) {
                                $exam_view = new Views_Exam_Exam($exam_model);
                                $data[] = $exam_view->render();
                            }
                        }
                    }

                    $return["exams"] = $data;
                    $return["total_forms"] = $total_exams;

                    echo json_encode(array("results" => count($data), "data" => $return));

                break;
                case "get-exam-elements" :
                    if (isset($request["exam_id"]) && $tmp_input = clean_input($request["exam_id"], "int")) {
                        $PROCESSED["exam_id"] = $tmp_input;
                    }

                    if (isset($request["sort_field"]) && $tmp_input = clean_input($request["sort_field"], array("trim", "striptags"))) {
                        $PROCESSED["sort_field"] = $tmp_input;
                    }

                    if (isset($request["sort_direction"]) && $tmp_input = clean_input($request["sort_direction"], array("trim", "striptags"))) {
                        $PROCESSED["sort_direction"] = $tmp_input;
                    }

                    if ($PROCESSED["exam_id"]) {
                        $exam = Models_Exam_Exam::fetchRowByID($PROCESSED["exam_id"]);
                        if ($exam && is_object($exam)) {
                            $exam_elements_html = Views_Exam_Exam::getExamElementOrder($exam, $PROCESSED["sort_field"], $PROCESSED["sort_direction"]);
                        }
                    }

                    if ($exam_elements_html) {
                        echo json_encode(array("status" => "success", "list_view_order" => $exam_elements_html["list_view_order"]));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No elements found to display.")));

                    }

                    break;
                case "get-version-preview" :
                    if (isset($request["version_id"]) && $tmp_input = clean_input($request["version_id"], "int")) {
                        $PROCESSED["version_id"] = $tmp_input;
                    }

                    if (isset($request["answer_correct"]) && $tmp_input = clean_input($request["answer_correct"], "int")) {
                        $PROCESSED["answer_correct"] = $tmp_input;
                    } else {
                        $PROCESSED["answer_correct"] = NULL;
                    }

                    if (isset($request["answer_incorrect"]) && $tmp_input = clean_input($request["answer_incorrect"], "int")) {
                        $PROCESSED["answer_incorrect"] = $tmp_input;
                    } else {
                        $PROCESSED["answer_incorrect"] = NULL;
                    }

                    $correct = array();
                    if ($PROCESSED["answer_correct"]) {
                        $correct[$PROCESSED["answer_correct"]] = "correct";
                    }

                    if ($PROCESSED["answer_incorrect"]) {
                        $correct[$PROCESSED["answer_incorrect"]] = "incorrect";
                    }

                    if ($PROCESSED["version_id"]) {
                        $version = Models_Exam_Question_Versions::fetchRowByVersionID($PROCESSED["version_id"]);
                        if ($version && is_object($version)) {
                            $version_view = new Views_Exam_Question($version);
                            $version_html = $version_view->render(false, array(), NULL, "details", false, NULL, NULL, NULL, 1, 1, $correct);
                        }
                    }

                    if ($version_html) {
                        echo json_encode(array("status" => "success", "html" => $version_html));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No elements found to display.")));
                    }

                    break;
                case "get-group-preview":
                    if (isset($request["group_id"]) && $tmp_input = clean_input($request["group_id"], "int")) {
                        $PROCESSED["group_id"] = $tmp_input;
                    }

                    if ($PROCESSED["group_id"]) {
                        $group = Models_Exam_Group::fetchRowByID($PROCESSED["group_id"]);
                        if ($group && is_object($group)) {
                            $group_version = new Views_Exam_Group($group);
                            $group_version_html = $group_version->render();
                        }
                    }

                    if ($group_version_html) {
                        echo json_encode(array("status" => "success", "html" => $group_version_html));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No elements found to display.")));
                    }

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

                    $results = Models_Exam_Exam_Author::fetchAvailableAuthors($PROCESSED["filter_type"], $PROCESSED["exam_id"], $PROCESSED["search_value"]);
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
                case "get-exam-authors" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }
                    
                    $authors = Models_Exam_Exam_Author::fetchByAuthorTypeProxyID($ENTRADA_USER->getActiveOrganisation(), $PROCESSED["search_value"]);
                    if ($authors) {
                        $data = array();
                        foreach ($authors as $author) {
                            $author_name = ($author->getAuthorName(true) ? $author->getAuthorName(true) : "N/A");
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
                case "get-user-courses-academic-year" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $courses = array();
                    $user_courses = Models_Course::getUserCourses($ENTRADA_USER->getID(), $ENTRADA_USER->getActiveOrganisation());

                    if ($user_courses && is_array($user_courses) && !empty($user_courses)) {
                        $data = array();
                        foreach ($user_courses as $course_obj) {
                            if ($course_obj && is_object($course_obj)) {
                                $course = $course_obj->toArray();
                                $data[] = array("target_id" => $course["course_id"], "target_label" => $course["course_code"] . " - " . $course["course_name"], "target_parent" => 0, "target_children" => "1");
                            }
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "parent_id" => "0", "parent_name" => "0", "level_selectable" => false));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No courses were found.")));
                    }
                    break;
                case "get-user-events" :
                    // We use a dot to separate the ID from the requested filter.
                    // I.E. course.95 will return all periods in the course which has the ID 95.
                    // We do this because we're using the same API method for all filters.
                    // Also, the advancedSearch component only supports 2 different data sources by default.

                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    // If the parent id is zero, we're at the root.
                    if ($request["parent_id"] == "0") {
                        $current_type = "root";
                        $parent_id = 0;
                    } else {
                        // Gets the separates the filter type and id. (filter.id).
                        $current_type = explode(".", $request["parent_id"])[0];
                        $parent_id = explode(".", $request["parent_id"])[1];
                    }

                    $CASE_ROOT = "root";
                    $CASE_COURSE = "course";
                    $CASE_CPERIOD = "cperiod";

                    switch ($current_type) {
                        case $CASE_ROOT :
                            // Root, will show a list of courses.
                            $user_courses = Models_Course::getUserCourses($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation(), $PROCESSED["search_value"], 1, true);
                            if ($user_courses && is_array($user_courses) && !empty($user_courses)) {
                                $data = array();

                                foreach ($user_courses as $course) {
                                    $data[] = array(
                                        "target_id" => "course." . $course->getID(),
                                        "target_label" => $course->getCourseCode() . " - " . $course->getCourseName(),
                                        "target_parent" => 0,
                                        "target_children" => 1,
                                        "level_selectable" => 0, // Do not let the user select this level. (Hides checkboxes/radio buttons).
                                    );
                                }
                                if ($PROCESSED["context"] == "search" && $PROCESSED["search_value"]) {
                                    $search_value = $PROCESSED["search_value"];
                                    $data = array_filter($data, function ($element) use ($search_value) {
                                        $pos = stripos($element["target_label"], $search_value);
                                        return $pos !== false;
                                    });
                                }
                                echo json_encode(array(
                                    "status" => "success",
                                    "data" => $data,
                                    "parent_id" => "0",
                                    "parent_name" => "0",
                                ));
                            } else {
                                echo json_encode(array("status" => "error", "data" => $translate->_("No courses were found.")));
                            }
                            break;
                        // Received a course_id, will show a list of all curriculum periods attached to that course.
                        case $CASE_COURSE :
                            $cperiods = Models_Curriculum_Period::fetchAllByCourseID($parent_id);
                            $data = [];
                            if ($cperiods) {
                                foreach ($cperiods as $cperiod) {
                                    $start_date = date(DEFAULT_DATE_FORMAT, $cperiod->getStartDate());
                                    $finish_date = date(DEFAULT_DATE_FORMAT, $cperiod->getFinishDate());
                                    $period_label = $start_date . " - " . $finish_date;
                                    $period_label .= $cperiod->getCurriculumPeriodTitle() != "" ? ": " . $cperiod->getCurriculumPeriodTitle() : "";
                                    array_push($data, array(
                                        "target_id" => "cperiod." . $cperiod->getId() . "." . $parent_id,
                                        "target_label" => $period_label,
                                        "target_parent" => $parent_id,
                                        "target_children" => 1,
                                        "level_selectable" => 0,
                                    ));
                                }
                            }

                            // We also add an extra option 'Show All Events' so the user can browse events
                            // that are schedule on date not contained in a curriculum period.
                            // We will always have this option, even if there are no curriculum periods for a course.
                            array_push($data, array(
                                "target_id" => "cperiod.all." . $parent_id, // Flag to display all curriculum periods.
                                "target_label" => "Show All Events",
                                "target_parent" => $parent_id,
                                "target_children" => 1,
                                "level_selectable" => 0,
                            ));

                            echo json_encode(array(
                                "status" => "success",
                                "data" => $data,
                                "parent_id" => 0, // Go back to courses...
                                "parent_name" => "Curriculum Periods",
                                "level_selectable" => 0
                            ));
                            break;
                        // Received a curriculum period_id, will return a list of events
                        // from a course within the curriculum period start and end dates.
                        case $CASE_CPERIOD :
                            // This has a third section which is the course id (cperiod.period_id.course_id)
                            // We'll use this to pass it back to advanced search, so we can go back to the previous level.
                            $course_id = explode(".", $request["parent_id"])[2];

                            if ($parent_id == "all") {
                                $learning_events = Models_Event::fetchAllByCourseID($course_id);
                            } else {
                                $cperiod = Models_Curriculum_Period::fetchRowByID($parent_id);
                                $learning_events = Models_Event::fetchAllByCourseIdDates($course_id, $cperiod->getStartDate(), $cperiod->getFinishDate());
                            }

                            if ($learning_events) {
                                foreach ($learning_events as $event_obj) {
                                    $event = $event_obj->toArray();
                                    $event_start = date(DEFAULT_DATETIME_FORMAT, $event["event_start"]);
                                    $data[] = array(
                                        "target_id" => $event["event_id"],
                                        "target_label" => $event_start . " - " . $event["event_title"]
                                    );
                                }
                                if ($PROCESSED["context"] == "search" && $PROCESSED["search_value"]) {
                                    $search_value = $PROCESSED["search_value"];
                                    $data = array_filter($data, function ($element) use ($search_value) {
                                        $pos = stripos($element["target_label"], $search_value);
                                        return $pos !== false;
                                    });
                                }
                                $course = $event_obj->getCourse();
                                echo json_encode(array(
                                    "status" => "success",
                                    "data" => $data,
                                    "level_selectable" => 1,
                                    "parent_name" => "Events in ".$course->getCourseCode() . " - " . $course->getCourseName(),
                                    "parent_id" => "course." . $course_id,
                                ));
                            } else {
                                echo json_encode(array(
                                    "status" => "error",
                                    "data" => $translate->_("No learning events were found for the selected period."),
                                ));
                            }
                            break;
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
                case "get-user-exams" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $exams = Models_Exam_Exam::fetchAllByOwner($ENTRADA_USER->getActiveID(), $PROCESSED["search_value"]);

                    if (isset($exams) && is_array($exams) && !empty($exams)) {
                        $data = array();
                        foreach ($exams as $exam) {
                            $data[] = array("target_id" => $exam->getID(), "target_label" => $exam->getTitle());
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("results" => "0", "data" => $translate->_("No forms found.")));
                    }
                    break;
                case "get-exam-grade-books":
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    if (isset($request["course_id"])) {
                        $tmp_input = (int)$request["course_id"];
                        $PROCESSED["course_id"] = $tmp_input;
                    }

                    // Get the exam grade book id for your org.
                    $meta = Models_Gradebook_Assessment_LuMeta::fetchRowByOrganisationIdTypeTitle($ENTRADA_USER->getActiveOrganisation(), "exam", "Computer Exam Module");
                    if (isset($meta) && is_object($meta)) {
                        if ($PROCESSED["course_id"]) {
                            $grade_books = Models_Gradebook_Assessment::fetchAllByCourseIdMetaId($PROCESSED["course_id"], $meta->getID());
                            if ($grade_books) {
                                $data = array();
                                foreach ($grade_books as $grade_book) {
                                    $data[] = array("target_id" => $grade_book->getAssessmentID(), "target_label" => $grade_book->getName());
                                }
                                echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                            } else {
                                echo json_encode(array("results" => "0", "data" => $translate->_("No Grade Books found in this course.")));
                            }
                        } else {
                            echo json_encode(array("results" => "0", "data" => $translate->_("No Course ID")));
                        }
                    } else {
                        echo json_encode(array("results" => "0", "data" => $translate->_("No Exam Grade Book types for your organisation")));
                    }

                    break;
                case "get-exam-grade-books-by-event":
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    if (isset($request["event_id"])) {
                        $tmp_input = (int)$request["event_id"];
                        $PROCESSED["event_id"] = $tmp_input;
                    } else {
                        add_error("Missing event id.");
                    }

                    if (!has_error()) {
                        $event = Models_Event::fetchRowByID($PROCESSED["event_id"]);
                        if ($event && is_object($event)) {
                            $cperiod = $event->getCurriculumPeriod();
                            $course = $event->getCourse();
                        }
                    }

                    //get the exam grade book id for your org
                    if ($cperiod && is_object($cperiod)) {
                        if ($course && is_object($course)) {
                            $event = Models_Event::fetchRowByID($PROCESSED["event_id"]);
                            if ($event) {
                                $grade_books = Models_Gradebook_Assessment::fetchAssessmentsByCurriculumPeriodIdTitle($course->getID(), $cperiod->getID(), $PROCESSED["search_value"]);
                                if ($grade_books) {
                                    $data = array();
                                    foreach ($grade_books as $grade_book) {
                                        $data[] = array("target_id" => $grade_book->getAssessmentID(), "target_label" => $grade_book->getName());
                                    }
                                    echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                                } else {
                                    echo json_encode(array("status" => "error", "data" => $translate->_("No GradeBooks found in this course.")));
                                }
                            } else {
                                echo json_encode(array("status" => "error", "data" => $translate->_("No GradeBooks found in this course.")));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("Event is not associated to any course.")));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("Event date is not associated to any curriculum period.")));
                    }

                    break;
                case "get-users-course":
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    if (isset($request["event_id"])) {
                        $tmp_input = (int)$request["event_id"];
                        $PROCESSED["event_id"] = $tmp_input;
                        if ($PROCESSED["event_id"]) {
                            $event = Models_Event::fetchRowByID($PROCESSED["event_id"]);
                            if ($event) {
                                $course_id = $event->getCourseID();
                            }
                        }
                    }

                    $filtered_audience  = array();
                    $audience_members = Models_Course::fetchAllContactsByCourseIDContactTypeSearchTerm($course_id, "director", $PROCESSED["search_value"]);

                    if (isset($audience_members) && is_array($audience_members) && !empty($audience_members)) {
                        foreach ($audience_members as $key => $member) {
                            if (!array_key_exists($key, $filtered_audience)) {
                                $user = Models_User::fetchRowByID($member["proxy_id"]);
                                if ($user) {
                                    $filtered_audience[$key] = array("target_id" => $member["proxy_id"], "target_label" => $user->getFirstname() . " " . $user->getLastname());
                                }
                            }
                        }
                    }

                    if ($filtered_audience) {
                        echo json_encode(array("status" => "success", "data" => $filtered_audience));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Directors found.")));
                    }
                    break;
                case "get-exception-audience":
                    $event_start = 0;

                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    if (isset($request["event_id"])) {
                        $tmp_input = (int)$request["event_id"];
                        $PROCESSED["event_id"] = $tmp_input;
                        if ($PROCESSED["event_id"]) {
                            $event = Models_Event::fetchRowByID($PROCESSED["event_id"]);
                            if ($event) {
                                $event_start = $event->getEventStart();
                            }
                        }
                    }

                    $filtered_audience  = array();
                    $audience_members = Models_Event_Attendance::fetchAllByEventID($PROCESSED["event_id"], $event_start, $PROCESSED["search_value"]);
                    if (isset($audience_members) && is_array($audience_members) && !empty($audience_members)) {
                        foreach ($audience_members as $key => $member) {
                            if (!array_key_exists($key, $filtered_audience)) {
                                $student = User::fetchRowByID($key);
                                if ($student) {
                                     $filtered_audience[$key] = array("target_id" => $key, "target_label" => $student->getFullName());
                                }
                            }
                        }
                    }

                    if ($filtered_audience) {
                        echo json_encode(array("status" => "success", "data" => $filtered_audience, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No learners found.")));
                    }
                    break;
                case "get-feedback-report-audience" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    if (isset($request["exam_id"])) {
                        $tmp_input = (int)$request["exam_id"];
                        $PROCESSED["exam_id"] = $tmp_input;
                    }

                    $audience           = array();
                    $searched_users     = array();
                    $filtered_audience  = array();

                    $all_posts = Models_Exam_Post::fetchAllByExamIDNoPreview($PROCESSED["exam_id"]);
                    if ($all_posts && is_array($all_posts) && !empty($all_posts)) {
                        $selected_posts = array();
                        $post_ids = array();
                        foreach ($all_posts as $post) {
                            $all_post_ids[] = $post->getID();
                        }
                    }

                    if ($all_post_ids && is_array($all_post_ids) && !empty($all_post_ids)) {
                        $PROCESSED["post_ids"] = $all_post_ids;

                        $completed_records = array();

                        if ($PROCESSED["post_ids"] && is_array($PROCESSED["post_ids"]) && !empty($PROCESSED["post_ids"])) {
                            foreach ($PROCESSED["post_ids"] as $post_id) {
                                $post = Models_Exam_Post::fetchRowByID($post_id);
                                if ($post && is_object($post)) {
                                    $progress_records = Models_Exam_Progress::fetchAllByPostIDProgressValue($post->getID(), "submitted");
                                    if ($progress_records && is_array($progress_records) && !empty($progress_records)) {
                                        foreach ($progress_records as $record) {
                                            if ($record && is_object($record)) {
                                                $proxy_id = $record->getProxyID();
                                                $progress_id = $record->getExamProgressID();

                                                if (!in_array($progress_id, $completed_records)) {
                                                    $user = Models_User::fetchRowByID($proxy_id);
                                                    if ($user && is_object($user)) {
                                                        $completed_records[$progress_id] = array(
                                                            "progress_id" => $progress_id,
                                                            "proxy_id" => $proxy_id,
                                                            "name"  => $user->getName("%l, %f"),
                                                            "date" => $record->getSubmissionDate()
                                                        );
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    // Views_Exam_Exam_Sort
                                    $sort_field = "name";
                                    $sort_direction = "asc";

                                    $sort = new Views_Exam_Exam_Sort($sort_field, $sort_direction);
                                    $completed_records = $sort->sort_field_alpha($completed_records);

                                    if ($PROCESSED["search_value"] != "") {
                                        $filter_users = User::fetchUsersBySearchQueryGroup($PROCESSED["search_value"]);
                                        if (isset($filter_users) && is_array($filter_users) && !empty($filter_users)) {
                                            foreach ($filter_users as $user) {
                                                if (isset($user) && is_object($user)) {
                                                    $user_id = $user->getID();
                                                    if (!in_array($user_id, $searched_users)) {
                                                        $searched_users[] = (int)$user_id;
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    if (isset($completed_records) && is_array($completed_records) && !empty($completed_records)) {
                                        $count = 0;
                                        foreach ($completed_records as $record) {
                                            $progress_id = $record["progress_id"];
                                            $id = $record["proxy_id"];
                                            $name = $record["name"];
                                            $date = date("Y-m-d H:i:s", $record["date"]);

                                            if ($PROCESSED["search_value"] != "") {
                                                if (in_array($id, $searched_users)) {
                                                    // name is a searched user match
                                                    $filtered_audience[$count] = array("target_id" => $progress_id, "target_label" => $name . " - " . $date);
                                                }
                                            } else {
                                                $filtered_audience[$count] = array("target_id" => $progress_id, "target_label" => $name . " - " . $date);
                                            }
                                            $count++;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if ($filtered_audience) {
                        echo json_encode(array("status" => "success", "data" => $filtered_audience, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No learners found.")));
                    }
                    break;
                case "get-assigned-learners":
                    
                    if (isset($request["post_id"])) {
                        $tmp_input = (int)$request["post_id"];
                        $PROCESSED["post_id"] = $tmp_input;
                        if ($PROCESSED["post_id"]) {
                            $learners = Models_Exam_Grader::fetchAssignedStudents($PROCESSED["post_id"]);
                        }
                    }

                    $output = array();

                    foreach ($learners as $learner) {
                        $output[] = $learner->getID();
                    }

                    if ($output) {
                        echo json_encode(array("status" => "success", "data" => $output));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No learners found.")));
                    }
                    break;
                case "get-assigned-groups":
                    $output = array();
                    $groups = array();

                    if (isset($request["post_id"])) {
                        $PROCESSED["post_id"] = (int)$request["post_id"];
                        if ($PROCESSED["post_id"]) {
                            $groups = Models_Exam_Grader::fetchAssignedCourseGroups($PROCESSED["post_id"]);
                        }
                    }

                    foreach ($groups as $group) {
                        $output[] = $group->getCgroupID();
                    }

                    if ($output) {
                        echo json_encode(array("status" => "success", "data" => $output));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No groups found.")));
                    }
                    break;
                case "get-category-report-audience" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $audience           = array();
                    $searched_users     = array();
                    $filtered_audience  = array();
                    if (isset($request["post_ids"])) {
                        if ($request["post_ids"] && is_string($request["post_ids"])) {
                            $PROCESSED["post_ids"] = array((int)$request["post_ids"]);
                        } elseif ($tmp_input && is_array($request["post_ids"])) {
                            // @todo add array proccessing
                        }

                        $completed_audience = array();

                        if ($PROCESSED["post_ids"] && is_array($PROCESSED["post_ids"]) && !empty($PROCESSED["post_ids"])) {
                            foreach ($PROCESSED["post_ids"] as $post_id) {
                                $post = Models_Exam_Post::fetchRowByID($post_id);
                                if ($post && is_object($post)) {
                                    $progress_records = Models_Exam_Progress::fetchAllByPostIDProgressValue($post->getID(), "submitted");
                                    if ($progress_records && is_array($progress_records) && !empty($progress_records)) {
                                        foreach ($progress_records as $record) {
                                            $proxy_id = $record->getProxyID();
                                            if (!in_array($proxy_id, $completed_audience)) {
                                                $completed_audience[] = $proxy_id;
                                            }
                                        }
                                    }

                                    if ($PROCESSED["search_value"] != "") {
                                        $filter_users = User::fetchUsersBySearchQueryGroup($PROCESSED["search_value"]);
                                        if (isset($filter_users) && is_array($filter_users) && !empty($filter_users)) {
                                            foreach ($filter_users as $user) {
                                                if (isset($user) && is_object($user)) {
                                                    $user_id = $user->getID();
                                                    if (!in_array($user_id, $searched_users)) {
                                                        $searched_users[] = (int)$user_id;
                                                    }
                                                }
                                            }

                                            if (isset($completed_audience) && is_array($completed_audience) && !empty($completed_audience)) {
                                                foreach ($completed_audience as $member) {
                                                    $member_obj = User::fetchRowByID($member);
                                                    if (isset($member_obj) && is_object($member_obj)) {
                                                        $name = $member_obj->getName("%l, %f");
                                                        $id = $member;
                                                        if (in_array($id, $searched_users)) {
                                                            // name is a searched user match
                                                            if (!array_key_exists($id, $filtered_audience)) {
                                                                $filtered_audience[$name] = array("target_id" => $id, "target_label" => $name);
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    } else {
                                        if (isset($completed_audience) && is_array($completed_audience) && !empty($completed_audience)) {
                                            foreach ($completed_audience as $member) {
                                                $member_obj = User::fetchRowByID($member);
                                                if (isset($member_obj) && is_object($member_obj)) {
                                                    $name = $member_obj->getName("%l, %f");
                                                    $id = $member;

                                                    if (!array_key_exists($id, $filtered_audience)) {
                                                        $filtered_audience[$name] = array("target_id" => $id, "target_label" => $name);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }



                    if ($filtered_audience) {
                        ksort($filtered_audience);

                        $filtered_audience_reordered = array();

                        foreach ($filtered_audience as $audience) {
                            $filtered_audience_reordered[] = $audience;
                        }

                        echo json_encode(array("status" => "success", "data" => $filtered_audience_reordered, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No learners found.")));
                    }
                    break;
                case "get-category-report-sets" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $audience           = array();
                    $searched_users     = array();
                    $categories         = array();
                    $filtered_objectives  = array();
                    if (isset($request["exam_id"])) {
                        $tmp_input = (int)$request["exam_id"];
                        $PROCESSED["exam_id"]   = $tmp_input;

                        if ($PROCESSED["exam_id"]) {
                            $exam = Models_Exam_Exam::fetchRowByID($PROCESSED["exam_id"]);
                            if ($exam && is_object($exam)) {
                                $exam_elements = Models_Exam_Exam_Element::fetchAllByExamIDElementType($exam->getExamID(), "question");

                                if (isset($exam_elements) && is_array($exam_elements) && !empty($exam_elements)) {
                                    foreach ($exam_elements as $element) {
                                        if ($element && is_object($element) && $element->getNotScored() == 0) {
                                            $correct        = 0;
                                            $user_point     = 0;
                                            $question       = Models_Exam_Question_Versions::fetchRowByVersionID($element->getElementID());
                                            if ($question && is_object($question)) {
                                                $type = $question->getQuestionType()->getShortname();
                                                if ($type != "text") {

                                                    $curriculum_tags = Models_Exam_Question_Objectives::fetchAllRecordsByQuestionID($question->getQuestionID());
                                                    if ($curriculum_tags && is_array($curriculum_tags) && !empty($curriculum_tags)) {
                                                        foreach ($curriculum_tags as $tag) {
                                                            if ($tag && is_object($tag)) {
                                                                $objective_id = $tag->getObjectiveID();
                                                                $global_objective = Models_Objective::fetchRow($objective_id);

                                                                if ($global_objective && is_object($global_objective)) {
                                                                    // get the parent...
                                                                    $parent_id = (int) $global_objective->getParent();
                                                                    if ($parent_id > 0) {
                                                                        $parent_objective = Models_Objective::fetchRow($parent_id);
                                                                        if ($parent_objective && is_object($parent_objective)) {
                                                                            $parent_parent_id = (int) $parent_objective->getParent();
                                                                            if ($parent_parent_id > 0) {

                                                                            } else if ($parent_parent_id == 0) {
                                                                                $set_parent  = $parent_objective;
                                                                                $set = $set_parent->getID();
                                                                            }
                                                                        }
                                                                    } else if ($parent_id == 0) {
                                                                        $set_parent  = $global_objective;
                                                                        $set = $set_parent->getID();
                                                                    }
                                                                }

                                                                if (!array_key_exists($set, $categories)) {
                                                                    $categories[$set] = $set_parent;
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }

                                if ($PROCESSED["search_value"] != "") {
                                    if (isset($categories) && is_array($categories) && !empty($categories)) {
                                        foreach ($categories as $id => $objective) {
                                            if (isset($objective) && is_object($objective)) {
                                                $name = $objective->getName();

                                                if (!array_key_exists($id, $filtered_objectives)) {
                                                    $filtered_objectives[$id] = array("target_id" => $id, "target_label" => $name);
                                                }
                                            }
                                        }
                                    }
                                    // get the filtered objectives by search value
                                } else {
                                    if (isset($categories) && is_array($categories) && !empty($categories)) {
                                        foreach ($categories as $id => $objective) {
                                            if (isset($objective) && is_object($objective)) {
                                                $name = $objective->getName();

                                                if (!array_key_exists($id, $filtered_objectives)) {
                                                    $filtered_objectives[$id] = array("target_id" => $id, "target_label" => $name);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if ($filtered_objectives) {
                        $filtered_audience_reordered = array();

                        foreach ($filtered_objectives as $objective) {
                            $filtered_objectives_reordered[] = $objective;
                        }

                        echo json_encode(array("status" => "success", "data" => $filtered_objectives_reordered, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("results" => "0", "data" => $translate->_("No curriculum tags found.")));
                    }
                    break;
                case "get-post-data" :
                    if (isset($request["post_id"]) && $tmp_input = clean_input(strtolower($request["post_id"]), array("trim", "int"))) {
                        $PROCESSED["post_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Please provide a <strong>Post ID</strong>."));
                    }

                    if ($PROCESSED["post_id"]) {
                        $post = Models_Exam_Post::fetchRowByID($PROCESSED["post_id"]);
                        $exam = Models_Exam_Exam::fetchRowByID($post->getExamID());
                        if ($post) {

                            $data = $post->toArray();

                            $data["time_limit_hours"] = (int)floor($data["time_limit"] / 60);
                            $data["time_limit_mins"] = (int)$data["time_limit"] % 60;
                            $data["exam_title"] = $exam->getTitle();

                            $grade_book_assessment = Models_Gradebook_Assessment::fetchRowByID($post->getGradeBook());
                            if ($grade_book_assessment && is_object($grade_book_assessment)) {
                                $data["grade_book_title"] = $grade_book_assessment->getName();
                            }

                            $secure_file = $post->getSecureAccessFile();
                            if ($secure_file) {
                                $data["secure_access_file"]['id'] = $secure_file->getID();
                                $data["secure_access_file"]['file_name'] = $secure_file->getFileName();
                                $data["secure_access_file"]['updated_date'] = date(DEFAULT_DATETIME_FORMAT, $secure_file->getUpdatedDate());
                            }

                            $secure_keys = $post->getSecureAccessKeys();
                            if ($secure_keys) {
                                foreach($secure_keys as $key => $secure_key){
                                    $data["secure_access_keys"][$key]['id'] = $secure_key->getID();
                                    $data["secure_access_keys"][$key]['key'] = $secure_key->getKey();
                                    $data["secure_access_keys"][$key]['version'] = $secure_key->getVersion();
                                }
                            }

                            //exceptions
                            $exam_exceptions = $post->getExamExceptions();
                            if (isset($exam_exceptions) && is_array($exam_exceptions) && !empty($exam_exceptions)) {
                                foreach ($exam_exceptions as $exam_exception) {
                                    if (isset($exam_exception) && is_object($exam_exception)) {
                                        $proxy_id = $exam_exception->getProxyID();
                                        $user = User::fetchRowByID($proxy_id);
                                        $label = $user->getName("%l, %f");
                                        $data["exam_exceptions"][$proxy_id]["use_exception_max_attempts"] = $exam_exception->getUseExceptionMaxAttempts();
                                        $data["exam_exceptions"][$proxy_id]["max_attempts"] = $exam_exception->getAttempts();
                                        $data["exam_exceptions"][$proxy_id]["exception_start_date"] = $exam_exception->getStartDate();
                                        $data["exam_exceptions"][$proxy_id]["exception_end_date"] = $exam_exception->getEndDate();
                                        $data["exam_exceptions"][$proxy_id]["exception_submission_date"] = $exam_exception->getSubmissionDate();
                                        $data["exam_exceptions"][$proxy_id]["use_exception_start_date"] = $exam_exception->getUseStartDate();
                                        $data["exam_exceptions"][$proxy_id]["use_exception_end_date"] = $exam_exception->getUseEndDate();
                                        $data["exam_exceptions"][$proxy_id]["use_exception_submission_date"] = $exam_exception->getUseSubmissionDate();
                                        $data["exam_exceptions"][$proxy_id]["exception_time_factor"] = $exam_exception->getExceptionTimeFactor();
                                        $data["exam_exceptions"][$proxy_id]["use_exception_time_factor"] = $exam_exception->getUseExceptionTimeFactor();
                                        $data["exam_exceptions"][$proxy_id]["excluded"] = $exam_exception->getExcluded();
                                        $data["exam_exceptions"][$proxy_id]["label"] = $label;
                                    }
                                }
                            }
                        }
                    }

                    if ($data) {
                        echo json_encode(array("status" => "success", "data" => $data));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Post Data Found")));
                    }
                    break;
                case "get-exam-post-row" :

                    if (isset($request["post_id"]) && $tmp_input = clean_input(strtolower($request["post_id"]), array("trim", "int"))) {
                        $PROCESSED["post_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Please provide a <strong>Post ID</strong>."));
                    }

                    if ($PROCESSED["post_id"]) {
                        $post = Models_Exam_Post::fetchRowByID($PROCESSED["post_id"]);
                        $exam = Models_Exam_Exam::fetchRowByID($post->getExamID());
                        if (isset($post) && is_object($post)) {
                            $post_view = new Views_Exam_Post($post);
                            if (isset($post_view) && is_object($post_view)) {
                                $post_view_row = $post_view->renderEventPostAdminRow();
                            }
                        }
                    }

                    if ($post_view) {
                        echo json_encode(array("status" => "success", "post_view" => $post_view_row));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Post Data Found")));
                    }
                    break;

                case "get-secure-keys" :
                    if (isset($request["post_id"]) && $tmp_input = clean_input(strtolower($request["post_id"]), array("trim", "int"))) {
                        $PROCESSED["post_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Please provide a <strong>Post ID</strong>."));
                    }
                    if (isset($PROCESSED["post_id"]) && $PROCESSED["post_id"] !== "") {
                        $post = Models_Exam_Post::fetchRowByID($PROCESSED["post_id"]);
                        if ($post) {
                            $data = array();
                            $secure_keys = $post->getSecureAccessKeys();
                            if ($secure_keys){
                                foreach($secure_keys as $key => $secure_key){
                                    $data["secure_access_keys"][$key]['id'] = $secure_key->getID();
                                    $data["secure_access_keys"][$key]['key'] = $secure_key->getKey();
                                    $data["secure_access_keys"][$key]['version'] = $secure_key->getVersion();
                                }
                            }
                        }
                    }

                    if (isset($data)) {
                        echo json_encode(array("status" => "success", "data" => $data));
                    } else {
                        echo json_encode(array("status" => "empty", "data" => $translate->_("Please add a <strong>Secure Key</strong> for this exam")));
                    }
                    break;
                case "get-post-exceptions" :
                    if (isset($request["post_id"]) && $tmp_input = clean_input(strtolower($request["post_id"]), array("trim", "int"))) {
                        $PROCESSED["post_id"] = $tmp_input;
                    } else if (isset($request["event_id"]) && $tmp_input = clean_input(strtolower($request["event_id"]), array("trim", "int"))) {
                        $PROCESSED["event_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Please provide a <strong>Post ID</strong> or <strong>Event ID</strong>."));
                    }

                    $data["exam_exceptions"] = array();

                    // Get the current exceptions...
                    if (isset($PROCESSED["post_id"]) && $PROCESSED["post_id"] > 0) {
                        $post = Models_Exam_Post::fetchRowByID($PROCESSED["post_id"]);
                        if ($post) {
                            //exceptions
                            $exam_exceptions = $post->getExamExceptions();
                            if (isset($exam_exceptions) && is_array($exam_exceptions) && !empty($exam_exceptions)) {
                                foreach ($exam_exceptions as $exam_exception) {
                                    if (isset($exam_exception) && is_object($exam_exception)) {
                                        $proxy_id = $exam_exception->getProxyID();
                                        $user = User::fetchRowByID($proxy_id);
                                        $label = $user->getName("%l, %f");
                                        $data["exam_exceptions"][$proxy_id]["label"] = $label;
                                        $data["exam_exceptions"][$proxy_id]["use_exception_max_attempts"] = $exam_exception->getUseExceptionMaxAttempts();
                                        $data["exam_exceptions"][$proxy_id]["max_attempts"] = $exam_exception->getAttempts();
                                        $data["exam_exceptions"][$proxy_id]["exception_start_date"] = $exam_exception->getStartDate();
                                        $data["exam_exceptions"][$proxy_id]["exception_end_date"] = $exam_exception->getEndDate();
                                        $data["exam_exceptions"][$proxy_id]["exception_submission_date"] = $exam_exception->getSubmissionDate();
                                        $data["exam_exceptions"][$proxy_id]["use_exception_start_date"] = $exam_exception->getUseStartDate();
                                        $data["exam_exceptions"][$proxy_id]["use_exception_end_date"] = $exam_exception->getUseEndDate();
                                        $data["exam_exceptions"][$proxy_id]["use_exception_submission_date"] = $exam_exception->getUseSubmissionDate();
                                        $data["exam_exceptions"][$proxy_id]["exception_time_factor"] = $exam_exception->getExceptionTimeFactor();
                                        $data["exam_exceptions"][$proxy_id]["use_exception_time_factor"] = $exam_exception->getUseExceptionTimeFactor();
                                        $data["exam_exceptions"][$proxy_id]["excluded"] = $exam_exception->getExcluded();
                                        $data["exam_exceptions"][$proxy_id]["selected"] = 1;
                                    }
                                }
                            }
                        }
                    }

                    // Add remaining exam takers to the list.
                    if (isset($post) && $post) {
                        $event = $post->getEvent();
                    } else if (isset($PROCESSED["event_id"]) && $PROCESSED["event_id"] > 0) {
                        $event = Models_Event::fetchRowByID($PROCESSED["event_id"]);
                    }

                    if ($event) {
                        $audience_members = Models_Event_Attendance::fetchAllByEventID($event->getID(), $event->getEventStart());

                        foreach ($audience_members as $proxy_id => $member) {
                            if (! array_key_exists($proxy_id, $data["exam_exceptions"])) {
                                $student = User::fetchRowByID($proxy_id);

                                if ($student) {
                                    $data["exam_exceptions"][$proxy_id]["label"] = $student->getName("%l, %f");
                                    $data["exam_exceptions"][$proxy_id]["use_exception_max_attempts"] = 0;
                                    $data["exam_exceptions"][$proxy_id]["max_attempts"] = 1;
                                    $data["exam_exceptions"][$proxy_id]["exception_start_date"] = 0;
                                    $data["exam_exceptions"][$proxy_id]["exception_end_date"] = 0;
                                    $data["exam_exceptions"][$proxy_id]["exception_submission_date"] = 0;
                                    $data["exam_exceptions"][$proxy_id]["use_exception_start_date"] = 0;
                                    $data["exam_exceptions"][$proxy_id]["use_exception_end_date"] = 0;
                                    $data["exam_exceptions"][$proxy_id]["use_exception_submission_date"] = 0;
                                    $data["exam_exceptions"][$proxy_id]["exception_time_factor"] = 0;
                                    $data["exam_exceptions"][$proxy_id]["use_exception_time_factor"] = 0;
                                    $data["exam_exceptions"][$proxy_id]["excluded"] = 0;
                                    $data["exam_exceptions"][$proxy_id]["selected"] = 0;
                                }
                            }
                        }
                    }

                    if (has_error()) {
                        echo json_encode([
                            "status" => "error",
                            "data" => $ERRORSTR
                        ]);
                    } else if (isset($data) && count($data["exam_exceptions"]) > 0) {
                        echo json_encode(array(
                            "status" => "success",
                            "data" => $data
                        ));
                    } else {
                        echo json_encode(array(
                            "status" => "empty",
                            "data" => $translate->_("No audience members were found for the event attached to this post.")
                        ));
                    }

                    break;
                case "get-secure-file" :
                    if (isset($request["post_id"]) && $tmp_input = clean_input(strtolower($request["post_id"]), array("trim", "int"))) {
                        $PROCESSED["post_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Please provide a <strong>Post ID</strong>."));
                    }
                    if (isset($PROCESSED["post_id"]) && $PROCESSED["post_id"] !== "") {
                        $post = Models_Exam_Post::fetchRowByID($PROCESSED["post_id"]);

                        if ($post) {
                            $data = array();
                            $secure_file = $post->getSecureAccessFile();
                            if ($secure_file){
                                $data["secure_access_file"]['id'] = $secure_file->getID();
                                $data["secure_access_file"]['file_name'] = $secure_file->getFileName();
                                $data["secure_access_file"]['updated_date'] = $secure_file->getUpdatedDate();
                            }
                        }
                    }

                    if (isset($data)) {
                        echo json_encode(array("status" => "success", "data" => $data));
                    } else {
                        echo json_encode(array("status" => "empty", "data" => $translate->_("Please add a <strong>Secure File</strong> for this exam")));
                    }
                    break;

                case "get-exam-files" :
                    if (isset($request["exam_id"]) && $tmp_input = clean_input(strtolower($request["exam_id"]), array("trim", "int"))) {
                        $PROCESSED["exam_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Please provide a <strong>Exam ID</strong>."));
                    }

                    $html = "";

                    if (!has_error()) {
                        $files = Models_Exam_Exam_File::fetchAllByExamId($request["exam_id"]);

                        if ($files && is_array($files) && !empty($files)) {

                            foreach ($files as $file) {
                                if ($file && is_object($file)) {
                                    $file_view = new Views_Exam_Exam_File($file);
                                    if ($file_view && is_object($file_view)) {
                                        $html .= $file_view->renderRow();
                                    }
                                }
                            }
                        }
                    }

                    echo json_encode(array("status" => "success", "html" => $html));

                    break;
                case "get-exam-delete-permission" :
                    if (isset($request["exam_ids"]) && is_array($request["exam_ids"]) && !empty($request["exam_ids"])) {
                        foreach ($request["exam_ids"] as $exam_id) {
                            $tmp_input = clean_input(strtolower($exam_id), array("int"));
                            $PROCESSED["exam_ids"][] = $tmp_input;
                        }
                    } else {
                        $PROCESSED["exam_ids"] = 0;
                    }

                    $permission = array();

                    if (isset($PROCESSED["exam_ids"]) && is_array($PROCESSED["exam_ids"]) && !empty($PROCESSED["exam_ids"])) {
                        foreach ($PROCESSED["exam_ids"] as $exam_id) {
                            $exam = Models_Exam_Exam::fetchRowByID($exam_id);
                            $permission[$exam_id] = 0;
                            if (isset($exam) && is_object($exam)) {
                                $can_delete = $ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "delete");
                                if ($can_delete) {
                                    // The user can delete this exam, but confirm that no one has taken it yet.
                                    $post = Models_Exam_Post::fetchAllByExamIDNoPreview($exam->getID());
                                    if (isset($post) && is_array($post) && !empty($post)) {
                                        // This user can not delete this exam
                                    } else {
                                        // This exam can be deleted safely
                                        $permission[$exam_id] = 1;
                                    }
                                }
                            }
                        }

                        echo json_encode(array("status" => "success", "delete_permission" => $permission));

                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("Invalid exam id.")));
                    }
                    break;
                case "get-exam-copy-permission" :
                    if (isset($request["exam_ids"]) && is_array($request["exam_ids"]) && !empty($request["exam_ids"])) {
                        foreach ($request["exam_ids"] as $exam_id) {
                            $tmp_input = clean_input(strtolower($exam_id), array("int"));
                            $PROCESSED["exam_ids"][] = $tmp_input;
                        }
                    } else {
                        $PROCESSED["exam_ids"] = 0;
                    }

                    $permission = array();

                    if (isset($PROCESSED["exam_ids"]) && is_array($PROCESSED["exam_ids"]) && !empty($PROCESSED["exam_ids"])) {
                        foreach ($PROCESSED["exam_ids"] as $exam_id) {
                            $exam = Models_Exam_Exam::fetchRowByID($exam_id);
                            $permission[$exam_id] = 0;
                            if (isset($exam) && is_object($exam)) {
                                $can_view   = $ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "read");
                                if ($can_view) {
                                    // The user can copy this exam.
                                    $permission[$exam_id] = 1;
                                }
                            }
                        }

                        echo json_encode(array("status" => "success", "copy_permission" => $permission));

                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("Invalid exam id.")));
                    }
                    break;
                case "get-exam-move-permission" :
                    if (isset($request["exam_ids"]) && is_array($request["exam_ids"]) && !empty($request["exam_ids"])) {
                        foreach ($request["exam_ids"] as $exam_id) {
                            $tmp_input = clean_input(strtolower($exam_id), array("int"));
                            $PROCESSED["exam_ids"][] = $tmp_input;
                        }
                    } else {
                        $PROCESSED["exam_ids"] = 0;
                    }

                    $permission = array();

                    if (isset($PROCESSED["exam_ids"]) && is_array($PROCESSED["exam_ids"]) && !empty($PROCESSED["exam_ids"])) {
                        foreach ($PROCESSED["exam_ids"] as $exam_id) {
                            $exam = Models_Exam_Exam::fetchRowByID($exam_id);
                            $permission[$exam_id] = 0;
                            if (isset($exam) && is_object($exam)) {
                                if (isset($exam) && is_object($exam)) {
                                    $can_update   = $ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "update");
                                    if ($can_update) {
                                        // The user can move this exam.
                                        $permission[$exam_id] = 1;
                                    }
                                }
                            }
                        }

                        echo json_encode(array("status" => "success", "move_permission" => $permission));

                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("Invalid exam id.")));
                    }
                    break;
                case "get-exam-element-delete-permission" :
                    if (isset($request["exam_id"])) {
                        $tmp_input = clean_input(strtolower($request["exam_id"]), array("int"));
                        $exam_id = $tmp_input;
                    }

                    $permission = array();

                    $exam = Models_Exam_Exam::fetchRowByID($exam_id);
                    $permission[$exam_id] = 0;
                    if (isset($exam) && is_object($exam)) {
                        if (isset($exam) && is_object($exam)) {
                            $can_update   = $ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "update");
                            if ($can_update) {
                                // The user can move this exam.
                                $permission[$exam_id] = 1;
                            }
                        }
                        echo json_encode(array("status" => "success", "permission" => $permission));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("Invalid exam id.")));
                    }

                    break;

                 case "get-course-groups" :
                    if (isset($request["course_id"])) {
                        $course_id = clean_input(strtolower($request["course_id"]), array("int"));
                    }

                    $ret = array();

                    $PREFERENCES = preferences_load('courses');
                    $cperiod_id = $PREFERENCES["selected_curriculum_period"];

                    $course_groups = Models_Course_Group::fetchAllByCourseIDCperiodID($course_id, $cperiod_id);

                    foreach ($course_groups as $course_group) {
                        $ret[] = $course_group->toArray();
                    }

                    usort($ret, function($a, $b) { return strcasecmp($a['group_name'], $b['group_name']); });

                    if ($ret) {
                        echo json_encode(array("status" => "success", "data" => $ret));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No course group found.")));
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