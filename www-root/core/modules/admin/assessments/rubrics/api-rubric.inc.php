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
 * API to handle interaction with learning object repository.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

if((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
    header("Location: ".ENTRADA_URL);
    exit;
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
                case "copy-attach-rubric":
                    // Ensure that a new rubric title was entered
                    if (isset($_POST["new_rubric_title"]) && $tmp_input = clean_input($_POST["new_rubric_title"], array("trim", "striptags"))) {
                        $PROCESSED["rubric_title"] = $tmp_input;
                    } else {
                        add_error($translate->_("Sorry, a new title is required."));
                    }

                    // Validate posted rubric identifier
                    if (isset($request["rubric_id"]) && $tmp_input = clean_input($request["rubric_id"], "int")) {
                        $old_rubric_id = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid Grouped Item identifier provided."));
                    }

                    // Validate posted form identifier
                    if (isset($request["form_id"]) && $tmp_input = clean_input($request["form_id"], "int")) {
                        $form_id = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid form identifier provided."));
                    }

                    // Validate posted rubric form referrer (used for redirection)
                    $form_ref = Entrada_Utilities_FormStorageSessionHelper::getFormRef();
                    $form_referrer_data = Entrada_Utilities_FormStorageSessionHelper::fetch($form_ref);
                    if (empty($form_referrer_data)) {
                        add_error($translate->_("Unable to determine what form to attach this copy to."));
                    }

                    if (!$ERROR) {
                        if (!$forms_api->copyRubricAndReplaceOnForm($form_id, $old_rubric_id, $PROCESSED["rubric_title"])) {
                            foreach ($forms_api->getErrorMessages() as $error_message) {
                                add_error($error_message);
                            }
                        }
                    }

                    if ($ERROR) {
                        echo json_encode(array("status" => "error", $translate->_("Failed to copy Grouped Item."), "data" => $ERRORSTR));
                    } else {
                        Entrada_Utilities_Flashmessenger::addMessage("Successfully copied the <strong>Grouped Item</strong> and attached it to the form.", "success", $MODULE);
                        $url = Entrada_Utilities_FormStorageSessionHelper::buildRefURL(ENTRADA_URL . "/admin/assessments/rubrics?section=edit-rubric&rubric_id={$forms_api->getRubricID()}", $form_ref);
                        echo json_encode(array("status" => "success", "url" => $url));
                    }
                    break;
                case "copy-rubric" :

                    // Ensure that a new rubric title was entered
                    if (isset($_POST["new_rubric_title"]) && $tmp_input = clean_input($_POST["new_rubric_title"], array("trim", "striptags"))) {
                        $PROCESSED["rubric_title"] = $tmp_input;
                    } else {
                        add_error($translate->_("Sorry, a new title is required."));
                    }

                    // Validate posted rubric identifier
                    if (isset($request["rubric_id"]) && $tmp_input = clean_input($request["rubric_id"], "int")) {
                        $old_rubric_id = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid Grouped Item identifier provided."));
                    }

                    if (!$ERROR) {
                        if (!$forms_api->copyRubric($old_rubric_id, $PROCESSED["rubric_title"])) {
                            foreach ($forms_api->getErrorMessages() as $error_message) {
                                add_error($error_message);
                            }
                        }
                    }

                    if ($ERROR){
                        echo json_encode(array("status" => "error", $translate->_("Failed to copy Grouped Item."), "data" => $ERRORSTR));
                    } else {
                        Entrada_Utilities_Flashmessenger::addMessage("Successfully copied grouped item.", "success", $MODULE);
                        $url = ENTRADA_URL . "/admin/assessments/rubrics?section=edit-rubric&rubric_id={$forms_api->getRubricID()}";
                        echo json_encode(array("status" => "success", "url" => $url));
                    }
                    break;
                case "create-attach-rubric" :
                    // Check for ref attribute.
                    if (isset($_POST["fref"]) && $tmp_input = clean_input($_POST["fref"], array("trim", "alphanumeric"))) {
                        $PROCESSED["fref"] = $tmp_input;
                    } else {
                        add_error($translate->_("Please specify which form to attach this rubric to."));
                    }

                    // Ensure that a new rubric title was entered
                    if (isset($_POST["rubric_title"]) && $tmp_input = clean_input($_POST["rubric_title"], array("trim", "striptags"))) {
                        $PROCESSED["rubric_title"] = $tmp_input;
                    } else {
                        add_error($translate->_("Sorry, a new title is required."));
                    }

                    // Validate posted form identifier
                    if (isset($request["form_id"]) && $tmp_input = clean_input($request["form_id"], "int")) {
                        $PROCESSED["form_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid form identifier provided."));
                    }

                    if (!$ERROR) {
                        $forms_api->setFormID($PROCESSED["form_id"]);
                        $referrer_data = Entrada_Utilities_FormStorageSessionHelper::fetch($PROCESSED["fref"]);
                        if (empty($referrer_data)) {
                            // Can't create and attach without somewhere to attach to.
                            add_error($translate->_("Please specify which form you wish to attach a new rubric to."));
                        } else {
                            // Create the empty rubric, but don't attach it to the form yet. We will pass the ID along in the referrer.
                            if ($forms_api->createEmptyRubric($PROCESSED["rubric_title"])) {
                                // We only attach it when there are items to display.
                                //$new_ref = $forms_api->sessionStorageAddNewRubricToForm($forms_api->getRubricID(), $referrer_data["form_id"], ENTRADA_URL . "/admin/assessments/forms?section=edit-form&id={$referrer_data["form_id"]}"); // Store that this rubric will be used for the new form
                                //add_error($translate->_("There was an error while trying to add this Grouped Item. The system administrator was informed of this error; please try again later."));
                            } else {
                                foreach ($forms_api->getErrorMessages() as $error_msg) {
                                    add_error($error_msg);
                                }
                            }
                        }
                    }

                    if (!$ERROR) {
                        Entrada_Utilities_Flashmessenger::addMessage("Successfully created new <strong>Grouped Item</strong>. It will be attached to the form once items are added.", "success", $MODULE);
                        $url = Entrada_Utilities_FormStorageSessionHelper::buildRefURL(ENTRADA_URL . "/admin/assessments/rubrics?section=edit-rubric&rubric_id={$forms_api->getRubricID()}", $PROCESSED["fref"]);
                        echo json_encode(array("status" => "success", "url" => $url));
                    } else {
                        echo json_encode(array("status" => "error", $ERRORSTR, "msg" => $ERRORSTR));
                    }
                    break;
                case "add-rubric" :
                    if ((isset($request["rubric_title"])) && ($tmp_input = clean_input($request["rubric_title"], array("trim", "notags")))) {
                        $PROCESSED["rubric_title"] = $tmp_input;
                    } else {
                        add_error($translate->_("A Grouped Item Title is required"));
                    }

                    if ((isset($request["rubric_description"])) && ($tmp_input = clean_input($request["rubric_description"], array("trim", "notags")))) {
                        $PROCESSED["rubric_description"] = $tmp_input;
                    } else {
                        $PROCESSED["rubric_description"] = "";
                    }

                    if (!$ERROR) {
                        if (!$forms_api->createEmptyRubric($PROCESSED["rubric_title"])) {
                            foreach ($forms_api->getErrorMessages() as $error_message) {
                                add_error($error_message);
                            }
                        }
                    }
                    if ($ERROR) {
                        echo json_encode(array("status" => "error", $translate->_("There was an error while trying to add this Grouped Item. The system administrator was informed of this error; please try again later."), "msg" => $ERRORSTR));
                        //echo json_encode(array("status" => "error", $translate->_("Failed to add Grouped Item."), "msg" => $ERRORSTR));
                    } else {
                        $ENTRADA_LOGGER->log("", "add-rubric", "rubric_id", $forms_api->getRubricID(), 4, __FILE__, $ENTRADA_USER->getID());
                        Entrada_Utilities_Flashmessenger::addMessage($translate->_("Successfully created <strong>Grouped Item</strong>."), "success", $MODULE);
                        echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully created Grouped Item."), "rubric_id" => $forms_api->getRubricID()));
                    }
                break;
                case "delete-rubrics":
                    if (isset($request["rubric_id"]) && $tmp_input = clean_input($request["rubric_id"], "int")) {
                        $PROCESSED["rubric_id"] = $tmp_input;
                    }
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
                        $deleted_rubrics = $forms_api->deleteRubrics($PROCESSED["delete_ids"]);
                        $errors = $forms_api->getErrorMessages();

                        if (empty($errors) && !empty($deleted_rubrics)) {
                            echo json_encode(array("status" => "success", "msg" => sprintf($translate->_("Successfully deleted %d Grouped Item(s)."), count($deleted_rubrics)), "rubric_ids" => $deleted_rubrics));
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("There was an error when attempting to delete Grouped Item.")));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "msg" => $translate->_("Nothing to delete.")));
                    }
                    break;
                case "remove-permission" :
                    if (isset($request["arauthor_id"]) && $tmp_input = clean_input($request["arauthor_id"], "int")) {
                        $PROCESSED["arauthor_id"] = $tmp_input;
                    }

                    if ($PROCESSED["arauthor_id"]) {

                        $author = Models_Assessments_Rubric_Author::fetchRowByID($PROCESSED["arauthor_id"]);
                        if (($author->getAuthorType() == "proxy_id" && $author->getAuthorID() != $ENTRADA_USER->getActiveID()) || $author->getAuthorType() != "proxy_id") {
                            if ($author->fromArray(array("deleted_date" => time(), "updated_date" => time(), "updated_by" => $ENTRADA_USER->getActiveID()))->update()) {
                                $ENTRADA_LOGGER->log("", "remove-permission", "arauthor_id", $PROCESSED["arauthor_id"], 4, __FILE__, $ENTRADA_USER->getID());
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
                        $PROCESSED["rubric_id"] = $tmp_input;
                    }

                    if ($PROCESSED["member_id"] && $PROCESSED["member_type"] && $PROCESSED["rubric_id"]) {
                        $added = 0;
                        $a = Models_Assessments_Rubric_Author::fetchRowByRubricIDAuthorIDAuthorType($PROCESSED["rubric_id"], $PROCESSED["member_id"], $PROCESSED["member_type"]);
                        if ($a) {
                            if ($a->getDeletedDate()) {
                                if ($a->fromArray(array("deleted_date" => NULL))->update()) {
                                    $added++;
                                }
                            } else {
                                application_log("notice", "Grouped Item author [".$a->getID()."] is already an active author. API should not have returned this author as an option.");
                            }
                        } else {
                            $a = new Models_Assessments_Rubric_Author(
                                array(
                                    "rubric_id" => $PROCESSED["rubric_id"],
                                    "author_type" => $PROCESSED["member_type"],
                                    "author_id" => $PROCESSED["member_id"],
                                    "created_date" => time(),
                                    "created_by" => $ENTRADA_USER->getActiveID()
                                )
                            );
                            if ($a->insert()) {
                                $added++;
                                $ENTRADA_LOGGER->log("", "add-permission", "arauthor_id", $a->getID(), 4, __FILE__, $ENTRADA_USER->getID());
                            }
                        }

                        if ($added >= 1) {
                            echo json_encode(array("status" => "success", "data" => array("author_id" => $a->getID())));
                        } else {
                            echo json_encode(array("status" => "success", "data" => array($translate->_("Failed to add author"))));
                        }
                    }
                break;
                case "delete-rubric-item":
                    if (isset($request["aritem_id"]) && $tmp_input = clean_input($request["aritem_id"], "int")) {
                        $PROCESSED["aritem_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Please select an item to delete."));
                    }

                    if ($forms_api->deleteRubricItem($PROCESSED["aritem_id"])) {
                        echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully updated Grouped Item."), "rubric_id" => $forms_api->getRubricID()));
                    } else {
                        echo json_encode(array("status" => "error", "msg" => $translate > _("Unable to delete rubric item.")));
                    }
                    break;
                case "edit-rubric-label":
                    if (isset($request["rlabel_id"]) && $tmp_input = clean_input($request["rlabel_id"], "int")) {
                        $PROCESSED["rlabel_id"] = $tmp_input;
                    }
                    if (isset($request["item_id"]) && $tmp_input = clean_input($request["item_id"], "int")) {
                        $PROCESSED["item_id"] = $tmp_input;
                    } else {
                        add_error("An item id is required");
                    }
                    if (isset($request["label"]) && $tmp_input = clean_input($request["label"], array("trim", "notags"))) {
                        $PROCESSED["label"] = $tmp_input;
                    } else {
                        $PROCESSED["label"] = NULL;
                    }
                    if (isset($request["rubric_id"]) && $tmp_input = clean_input($request["rubric_id"], "int")) {
                        $PROCESSED["rubric_id"] = $tmp_input;
                    }
                    if (!$ERROR) {
                        if (isset($PROCESSED["rlabel_id"]) && $PROCESSED["rlabel_id"]) {
                            $rubric_label = Models_Assessments_Rubric_Label::fetchRowByID($PROCESSED["rlabel_id"]);
                            $rubric_label->fromArray($PROCESSED);
                            $method = "update";
                        } else {
                            $PROCESSED["label_type"] = "description";
                            if (!isset($PROCESSED["order"]) || $PROCESSED["order"] == NULL) {
                                $PROCESSED["order"] = 0;
                            }
                            $rubric_label = new Models_Assessments_Rubric_Label($PROCESSED);
                            $method = "insert";
                        }
                        if ($rubric_label->$method()) {
                            echo $PROCESSED["label"];
                            $ENTRADA_LOGGER->log("", "edit-rubric-label", "rlabel_id", $rubric_label->getID(), 4, __FILE__, $ENTRADA_USER->getID());
                        } else {
                            $success_message = ($method == "insert" ? $translate->_("Failed to insert the Grouped Item label.") : $translate->_("Failed to update the Grouped Item label."));
                            echo json_encode(array("status" => "error", "msg" => array($success_message)));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "msg" => $ERRORSTR));
                    }
                    break;
                case "update-rubric-item-order" :
                    if (isset($request["order_array"]) && is_array($request["order_array"])) {
                        foreach ($request["order_array"] as $aritem_id) {
                            $tmp_input = clean_input($aritem_id, "int");
                            if ($tmp_input) {
                                $PROCESSED["aritem_ids"][] = $tmp_input;
                            }
                        }
                    }

                    if (isset($PROCESSED["aritem_ids"]) && !empty($PROCESSED["aritem_ids"])) {
                        $i = 1;
                        foreach ($PROCESSED["aritem_ids"] as $aritem_id) {
                            $rubric_item = Models_Assessments_Rubric_Item::fetchRowByID($aritem_id);
                            if (!$rubric_item->fromArray(array("order" => $i))->update()) {
                                $ERROR++;
                            }
                            $i++;
                            $ENTRADA_LOGGER->log("", "update-rubric-item-order", "aritem_id", $rubric_item->getID(), 4, __FILE__, $ENTRADA_USER->getID());
                        }
                        if ($ERROR) {
                            echo json_encode(array("status" => "error", "data" => $translate->_("Failed to update order")));
                        } else {
                            echo json_encode(array("status" => "success", "data" => $translate->_("Updated order")));
                        }
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
                    
                    if (isset($PROCESSED["filters"])) {
                        $assessments_base = new Entrada_Utilities_Assessments_Base();
                        Models_Assessments_Rubric::saveFilterPreferences($PROCESSED["filters"]);
                        $assessments_base->updateAssessmentPreferences("assessments");
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

                    $assessments_base = new Entrada_Utilities_Assessments_Base();

                    if (isset($PROCESSED["filter_type"]) && isset($PROCESSED["filter_target"])) {
                        unset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["rubrics"]["selected_filters"][$PROCESSED["filter_type"]][$PROCESSED["filter_target"]]);
                        if (empty($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["rubrics"]["selected_filters"][$PROCESSED["filter_type"]])) {
                            unset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["rubrics"]["selected_filters"][$PROCESSED["filter_type"]]);
                            if (empty($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["rubrics"]["selected_filters"])) {
                                unset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["rubrics"]["selected_filters"]);
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
                    unset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["rubrics"]["selected_filters"]);
                    $assessments_base->updateAssessmentPreferences("assessments");
                    echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully removed all filters")));
                break;
                case "update-response-category" :

                    $status = false;
                    if ((isset($request["new_descriptor_id"])) && $tmp_input = clean_input($request["new_descriptor_id"], "int")) {
                        $PROCESSED["new_descriptor_id"] = intval($request["new_descriptor_id"]);
                    }

                    if ((isset($request["position"])) && $tmp_input = clean_input($request["position"], "int")) {
                        $PROCESSED["position"] = intval($request["position"]);
                    }

                    if ((isset($request["rubric_id"])) && $tmp_input = clean_input($request["rubric_id"], "int")) {
                        $PROCESSED["rubric_id"] = intval($request["rubric_id"]);
                    }

                    if (isset($PROCESSED["new_descriptor_id"]) && !empty($PROCESSED["new_descriptor_id"])
                        && isset($PROCESSED["rubric_id"]) && !empty($PROCESSED["rubric_id"])
                        && isset($PROCESSED["position"]) && !empty($PROCESSED["position"])) {

                        $forms_api->setRubricID($PROCESSED["rubric_id"]);
                        if ($status = $forms_api->updateRubricResponseDescriptor($PROCESSED["new_descriptor_id"], $PROCESSED["position"])) {
                            // updated OK, so update the referrer for future item adds

                            $forms_api->clearInternalStorage(); // Clear the internal storage mechanism (in order to fetch a fresh copy of this object)
                            $rubric_data = $forms_api->fetchRubricData($PROCESSED["rubric_id"]);
                            $rubric_ref = Entrada_Utilities_FormStorageSessionHelper::buildRubricRef($PROCESSED["rubric_id"]);
                            $ref_data = Entrada_Utilities_FormStorageSessionHelper::fetch($rubric_ref);
                            $ref_url = ENTRADA_URL . "/admin/assessments/rubrics?section=edit-rubric&rubric_id={$PROCESSED["rubric_id"]}";
                            if (!empty($ref_data)) {
                                $ref_url = $ref_data["referrer_url"];
                            }
                            // Store the new rubric ref
                            Entrada_Utilities_FormStorageSessionHelper::addRubricReferrerData($PROCESSED["rubric_id"], $rubric_data, $ref_url);
                        }

                        if ($status) {
                            echo json_encode(array("status" => "success", "msg" => $translate->_("Response Category has been updated."), "new_descriptor_id" => $PROCESSED["new_descriptor_id"]));
                        } else {
                            $api_errors = $forms_api->getErrorMessages();
                            $error_msg = reset($api_errors);
                            echo json_encode(array("status" => "error", "msg" => $error_msg));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "msg" => $translate->_("Failed to update Response Category.")));
                    }
                break;

                default:
                    echo json_encode(array("status" => "error", "data" => $translate->_("Invalid POST method.")));
                break;
            }
        break;
        case "GET" :
            if (isset($request["rubric_id"]) && $tmp_input = clean_input($request["rubric_id"], "int")) {
                $PROCESSED["rubric_id"] = $tmp_input;
            }
            switch ($request["method"]) {
                case "get-rubrics" :
                    $PROCESSED["filters"] = array();
                    if (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["rubrics"]["selected_filters"])) {
                        $PROCESSED["filters"] = $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["rubrics"]["selected_filters"];
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
                        $PROCESSED["sort_column"] = "rubric_id";
                    }

                    if (isset($request["itemtype_id"]) && $tmp_input = clean_input(strtolower($request["itemtype_id"]), array("trim", "int"))) {
                        $PROCESSED["itemtype_id"] = $tmp_input;
                    } else {
                        $PROCESSED["itemtype_id"] = 0;
                    }

                    if (isset($request["item_id"]) && $tmp_input = clean_input(strtolower($request["item_id"]), array("trim", "int"))) {
                        $PROCESSED["item_id"] = $tmp_input;
                    } else {
                        $PROCESSED["item_id"] = 0;
                    }

                    // TODO: Use shortname instead of hardcoded ID
                    if ($PROCESSED["itemtype_id"] == 12) {
                        $PROCESSED["is_scale"] = 1;
                    } else {
                        $PROCESSED["is_scale"] = 0;
                    }

                    $rubrics = Models_Assessments_Rubric::fetchAllRecordsBySearchTerm($PROCESSED["search_term"], $PROCESSED["limit"], $PROCESSED["offset"], $PROCESSED["sort_direction"], $PROCESSED["sort_column"], $PROCESSED["filters"], $PROCESSED["item_id"]);
                    
                    if ($rubrics) {
                        $data = array();
                        foreach ($rubrics as $rubric) {
                            $data[] = array("rubric_id" => $rubric["rubric_id"], "title" => $rubric["rubric_title"], "created_date" => ($rubric["created_date"] && !is_null($rubric["created_date"]) ? date("Y-m-d", $rubric["created_date"]) : "N/A"));
                        }
                        echo json_encode(array("results" => count($data), "data" => array("total_rubrics" => Models_Assessments_Rubric::countAllRecordsBySearchTerm($PROCESSED["search_term"], $PROCESSED["filters"], $PROCESSED["item_id"]), "rubrics" => $data)));
                    } else {
                        echo json_encode(array("results" => "0", "data" => $translate->_("You currently have no Grouped Items to display. To Add a new Grouped Item click the Add Grouped Item button above.")));

                    }
                break;
                case "get-rubric" :
                    $rubric = Models_Assessments_Rubric::fetchRowByID($PROCESSED["rubric_id"]);
                    $rubric_data["rubric"] = $rubric->toArray();

                    $rubric_elements = Models_Assessments_Rubric_Item::fetchAllRecordsByRubricID($PROCESSED["rubric_id"]);
                    $rubric_data["elements"] = array();
                    if ($rubric_elements) {
                        foreach($rubric_elements as $re) {
                            $rubric_item = Models_Assessments_Item::fetchRowByID($re->getItemID());
                            if ($rubric_item) {
                                $rubric_data["elements"][] = $rubric_item->toArray();
                            }

                            $item_responses = Models_Assessments_Item_Response::fetchAllRecordsByItemID($re->getItemID());
                        }
                    }
                    $rubric_data["count"] = count($rubric_elements);
                    $rubric_data["width"] = count($item_responses);

                    echo json_encode(array("status" => "success", "data" => $rubric_data));

                break;
                case "get-filtered-audience" :

                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = "%".$tmp_input."%";
                    }

                    if (isset($request["filter_type"]) && $tmp_input = clean_input($request["filter_type"], array("trim", "striptags"))) {
                        $PROCESSED["filter_type"] = $tmp_input;
                    }

                    if (isset($request["content_target"]) && $tmp_input = clean_input($request["content_target"], "int")) {
                        $PROCESSED["rubric_id"] = $tmp_input;
                    }

                    $results = Models_Assessments_Rubric_Author::fetchAvailableAuthors($PROCESSED["filter_type"], $PROCESSED["rubric_id"], $PROCESSED["search_value"]);
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
                case "get-rubric-authors" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }
                    
                    $authors = Models_Assessments_Rubric_Author::fetchByAuthorTypeProxyID($ENTRADA_USER->getActiveOrganisation(), $PROCESSED["search_value"]);
                    if ($authors) {
                        $data = array();
                        foreach ($authors as $author) {
                            $author_name = ($author->getAuthorName() ? $author->getAuthorName() : $translate->_("N/A"));
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
    }
    exit;
}
