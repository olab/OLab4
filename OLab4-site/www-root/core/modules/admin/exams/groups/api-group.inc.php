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
 * API to handle interaction with exam question groups.
 *
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Daniel Noji <dnoji@mednet.ucla.edu>
 * @copyright Copyright 2015 UC Regents. All Rights Reserved.
 *
 */

ini_set('post_max_size', '64M');
ini_set('upload_max_filesize', '64M');

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

if ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("examquestion", "create", false)) {
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
    header("Location: ".ENTRADA_URL);
    exit;
} else {
    ob_clear_open_buffers();

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
            $PROCESSED = array();
            if (isset($request["group_id"]) && $tmp_input = clean_input($request["group_id"], "int")) {
                $PROCESSED["group_id"] = $tmp_input;
            }
            switch ($request["method"]) {
                case "add-group" :
                    if ((isset($request["group_title"])) && ($tmp_input = clean_input($request["group_title"], array("trim", "notags")))) {
                        $PROCESSED["group_title"] = $tmp_input;
                    } else {
                        add_error($translate->_("A Group Title is required"));
                    }

                    if ((isset($request["group_description"])) && ($tmp_input = clean_input($request["group_description"], array("trim", "notags")))) {
                        $PROCESSED["group_description"] = $tmp_input;
                    } else {
                        $PROCESSED["group_description"] = "";
                    }

                    if ((isset($request["questions"])) && (is_array($request["questions"]))) {
                        $PROCESSED["questions"] = $request["questions"];
                    } else {
                        $PROCESSED["questions"] = "";
                    }

                    if (!$ERROR) {
                        $PROCESSED["organisation_id"] = $ENTRADA_USER->getActiveOrganisation();
                        $PROCESSED["created_date"]  = time();
                        $PROCESSED["created_by"]    = $ENTRADA_USER->getID();
                        $PROCESSED["updated_date"]  = time();
                        $PROCESSED["updated_by"]    = $ENTRADA_USER->getID();

                        $group = new Models_Exam_Group($PROCESSED);

                        if (!$group->insert()) {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("There was an error while trying to add this question group. The system administrator was informed of this error; please try again later.")));
                        } else {
                            $author = array(
                                "group_id"      => $group->getID(),
                                "author_type"   => "proxy_id",
                                "author_id"     => $ENTRADA_USER->getID(),
                                "created_date"  => time(),
                                "created_by"    => $ENTRADA_USER->getID()
                            );
                            $a = new Models_Exam_Group_Author($author);

                            if (!$a->insert()) {
                                echo json_encode(array("status" => "error", "msg" => $translate->_("There was an error while trying to add an author for this question group. The system administrator was informed of this error; please try again later.")));
                            } else {
                                $PROCESSED["group_id"] = $group->getID();
                                $ENTRADA_LOGGER->log("", "add-group", "group_id", $PROCESSED["group_id"], 4, __FILE__, $ENTRADA_USER->getID());

                                if (is_array($PROCESSED["questions"]) && !empty($PROCESSED["questions"])) {
                                    $errors = array();
                                    $added = array();
                                    $order = 0;
                                    foreach ($PROCESSED["questions"] as $key => $question) {
                                        $question_version_id = clean_input($question["value"], array("trim", "notags", "int"));
                                        if (!$question_version_id) {
                                            $question_version_id = clean_input($question["version_id"], array("trim", "notags", "int"));
                                        }
                                        $question_version = Models_Exam_Question_Versions::fetchRowByVersionID($question_version_id);
                                        if ($question_version) {
                                            $group_question_array = array(
                                                "group_id"      => $group->getID(),
                                                "question_id"   => $question_version->getQuestion()->getID(),
                                                "version_id"    => $question_version->getVersionID(),
                                                "order"         => $order,
                                                "updated_by"    => $ENTRADA_USER->getID(),
                                                "updated_date"  => time()
                                            );
                                            $group_question = new Models_Exam_Group_Question($group_question_array);
                                            if (!$group_question->insert()) {
                                                $errors[] = $question_version->getVersionID();
                                            } else {
                                                $order++;
                                                $added[] = $question_version->getVersionID();
                                            }
                                        } else {
                                            $errors[] = $question_version_id;
                                        }
                                    }
                                    if (!empty($errors)) {
                                        echo json_encode(array("status" => "error", "msg" => $translate->_("Successfully created the group <strong>" . $group->getGroupTitle() . "</strong>, but the following questions could not be added to it."), "data" => $errors));
                                    } else {
                                        echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully created the group <strong>" . $group->getGroupTitle() . "</strong> and added <strong>".count($added)." question(s)</strong>"), "data" => array("group_id" => $group->getGroupID(), "group_title" => $group->getGroupTitle(), "group_questions" => $added)));
                                    }
                                } else {
                                    // There are no questions to add to the new group, so return a success message
                                    echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully created group <strong>" . $group->getGroupTitle() . "</strong>."), "group_id" => $PROCESSED["group_id"], "data" => array("group_id" => $group->getGroupID(), "group_title" => $group->getGroupTitle())));
                                }
                            }
                        }
                    } else {
                        echo json_encode(array("status" => "error", "msg" => $translate->_("Failed to add create the new group."), "data" => $ERRORSTR));
                    }
                break;
                case "add-to-group" :
                    if (!isset($PROCESSED["group_id"])) {
                        add_error($translate->_("A Group ID is required"));
                    }

                    if ((isset($request["questions"])) && (is_array($request["questions"]))) {
                        $PROCESSED["questions"] = $request["questions"];
                    } else {
                        $PROCESSED["questions"] = "";
                        add_error($translate->_("No questions were selected to add to the question group. Please select the questions you would like to add to the question group and try again."));
                    }
                    if (!$ERROR) {
                        $group = Models_Exam_Group::fetchRowByID($PROCESSED["group_id"]);
                        if ($group){
                            if (is_array($PROCESSED["questions"]) && !empty($PROCESSED["questions"])){
                                $errors = array();
                                $added = array();
                                foreach ($PROCESSED["questions"] as $key => $question) {
                                    $question_version_id = clean_input($question["value"], array("trim", "notags", "int"));
                                    if (!$question_version_id) {
                                        $question_version_id = clean_input($question["version_id"], array("trim", "notags", "int"));
                                    }

                                    $exam_element_id = clean_input($question["element_id"], array("trim", "notags", "int"));

                                    $question_version = Models_Exam_Question_Versions::fetchRowByVersionID($question_version_id);
                                    if ($question_version) {
                                        if (!$group->hasQuestion($question_version)) {
                                            $group_question_array = array(
                                                "group_id"      => $group->getID(),
                                                "question_id"   => $question_version->getQuestionID(),
                                                "version_id"    => $question_version->getVersionID(),
                                                "order"         => $key,
                                                "updated_by"    => $ENTRADA_USER->getID(),
                                                "updated_date"  => time()
                                            );
                                            $group_question = new Models_Exam_Group_Question($group_question_array);
                                            if (!$group_question->insert()) {
                                                $errors[] = "<strong>ID: " . $question_version->getQuestionID() . " / Ver: " . $question_version->getVersionID() . "</strong> - An error occurred while attempting to add this question to the group. Please try again.";
                                            } else {
                                                $added[] = $question_version->getVersionID();

                                                if ($exam_element_id) {
                                                    $exam_element = Models_Exam_Exam_Element::fetchRowByID($exam_element_id);
                                                    if ($exam_element) {
                                                        $exam_element->setGroupID($group->getID());
                                                        if (!$exam_element->update()) {
                                                            $errors[] = "<strong>ID: " . $question_version->getQuestionID() . " / Ver: " . $question_version->getVersionID() . "</strong> - unable to update the exam element with the specified group id.";
                                                        }
                                                    }
                                                }
                                            }
                                        } else {
                                            $errors[] = "<strong>ID: " . $question_version->getQuestionID() . " / Ver: " . $question_version->getVersionID() . "</strong> - This question (or another version of it) already belongs to this group.";
                                        }
                                    } else {
                                        $errors[] = "<strong>ID: " . $question_version->getQuestionID() . " / Ver: " . $question_version->getVersionID() . "</strong> - The question version that you are attempting to add to the group could not be found in the system.";
                                    }
                                }
                                if (!empty($errors)) {
                                    echo json_encode(array("status" => "error", "msg" => $translate->_("The following questions could not be added to the group <strong>" . $group->getGroupTitle() . "</strong>."), "data" => $errors));
                                } else {
                                    echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully added <strong>".count($added)." questions</strong> to the group <strong>" . $group->getGroupTitle() . "</strong>"), "data" => array("group_id" => $group->getGroupID(), "group_title" => $group->getGroupTitle(), "group_questions" => $added)));
                                }
                            } else {
                                // There are no questions to add to the new group, so return an error
                                echo json_encode(array("status" => "error", "msg" => $translate->_("There were no questions selected to be added to the group <strong>" . $group->getGroupTitle() . "</strong>"), "group_id" => $PROCESSED["group_id"]));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("The selected question group was not found. Please try again."), "data" => $ERRORSTR));
                        }
                    } else {
                        // There are no questions to add to the new group, so return an error
                        echo json_encode(array("status" => "error", "msg" => $translate->_($ERRORSTR), "group_id" => $PROCESSED["group_id"]));
                    }

                    break;
                case "delete-groups":
                    $PROCESSED["delete_ids"] = array();
                    if (isset($request["delete_ids"]) && is_array($request["delete_ids"])) {
                        foreach ($request["delete_ids"] as $group_id) {
                            $tmp_input = clean_input($group_id, "int");
                            if ($tmp_input) {
                                $PROCESSED["delete_ids"][] = $tmp_input;
                            }
                        }
                    }

                    if (!empty($PROCESSED["delete_ids"])) {
                        $deleted_groups = array();
                        foreach ($PROCESSED["delete_ids"] as $group_id) {
                            $group = Models_Exam_Group::fetchRowByID($group_id);
                            if ($group) {
                                $group->fromArray(array("deleted_date" => time(),
                                                         "updated_date" => time(),
                                                         "updated_by" => $ENTRADA_USER->getActiveID()));
                                if (!$group->update()) {
                                    add_error($translate->_("Unable to delete a Grouped Item."));
                                } else {
                                    $ENTRADA_LOGGER->log("", "delete", "group_id", $group_id, 4, __FILE__, $ENTRADA_USER->getID());
                                    $deleted_groups[] = $group_id;
                                }
                            }
                        }
                        if (!$ERROR) {
                            $success_message = (count($deleted_groups) > 1 ? sprintf($translate->_("Successfully deleted %d Grouped Items."), count($deleted_groups)) : $translate->_("Successfully deleted one Grouped Item."));
                            echo json_encode(array("status" => "success", "msg" => $success_message, "group_ids" => $deleted_groups));
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("There was an error when attempting to delete a Grouped Item.")));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "msg" => $translate->_("Nothing to delete.")));
                    }
                    break;
                case "delete-group-question" :
                    $MODULE_TEXT    = $translate->_("exams");
                    $SECTION_TEXT   = $MODULE_TEXT["groups"]["api"];
                    if (isset($request["questions"]) && is_array($request["questions"])) {
                        foreach ($request["questions"] as $egquestion) {
                            $tmp_input = clean_input($egquestion['version_id'], "int");
                            if ($tmp_input) {
                                $PROCESSED["egquestion_ids"][] = $tmp_input;
                            }
                        }

                        if (isset($request["group_id"]) && $tmp_input = clean_input($request["group_id"], "int")) {
                            $PROCESSED["group_id"] = $tmp_input;
                        }
                        
                        foreach ($PROCESSED["egquestion_ids"] as $question_version_id) {
                            $group_question = Models_Exam_Group_Question::fetchRowByVersionIDGroupID($question_version_id, $PROCESSED["group_id"]);
                            if ($group_question && is_object($group_question)) {
                                $group_id           = $group_question->getGroupID();
                                $group_question_id  = $group_question->getVersionID();
                                $delete = $ENTRADA_ACL->amIAllowed(new ExamQuestionGroupResource($group_id, true), "delete");
                                if ($delete) {
                                    if ($group_question->delete()) {
                                        $exam_elements = Models_Exam_Exam_Element::fetchAllByElementIdGroupIdType($group_question_id, $group_id, "question");
                                        if ($exam_elements && is_array($exam_elements) && !empty($exam_elements)) {
                                            foreach ($exam_elements as $exam_element) {
                                                if ($exam_element && is_object($exam_element)) {
                                                    $exam_element->setGroupID(NULL);
                                                    $exam_element->setUpdatedDate(time());
                                                    $exam_element->setUpdatedBy($ENTRADA_USER->getID());
                                                    if (!$exam_element->update()) {
                                                        $ERROR++;
                                                    }
                                                }
                                            }
                                        }

                                        $SUCCESS++;
                                    } else {
                                        $ERROR++;
                                        $message = $SECTION_TEXT["error"]["01"] . $group_question_id . $SECTION_TEXT["error"]["01b"];
                                        add_error($message);
                                    }
                                } else {
                                    $ERROR++;
                                    $message = $SECTION_TEXT["error"]["01"] . $group_question_id . $SECTION_TEXT["error"]["01b"];
                                    add_error($message);
                                }
                            }
                        }

                        if ($ERROR && is_array($ERROR)) {
                            echo json_encode(array("status" => "error", "data" => $ERROR));
                        } elseif ($SUCCESS) {
                            Entrada_Utilities_Flashmessenger::addMessage($SECTION_TEXT["success"]["01"], "success", "exams");
                            echo json_encode(array("status" => "success", "data" => $SECTION_TEXT["success"]["01"]));
                        }
                    }
                    
                    break;
                case "remove-permission" :
                    if (isset($request["author-id"]) && $tmp_input = clean_input($request["author-id"], "int")) {
                        $PROCESSED["author_id"] = $tmp_input;
                    }

                    if ($PROCESSED["author_id"]) {

                        $author = Models_Exam_Group_Author::fetchRowByID($PROCESSED["author_id"]);
                        if (($author->getAuthorType() == "proxy_id" && $author->getAuthorID() != $ENTRADA_USER->getActiveID()) || $author->getAuthorType() != "proxy_id") {
                            if ($author->fromArray(array("deleted_date" => time(), "updated_date" => time(), "updated_by" => $ENTRADA_USER->getActiveID()))->update()) {
                                $ENTRADA_LOGGER->log("", "remove-permission", "author_id", $PROCESSED["author_id"], 4, __FILE__, $ENTRADA_USER->getID());
                                echo json_encode(array("status" => "success", "data" => $translate->_("Successfully removed author.")));
                            } else {
                                echo json_encode(array("status" => "error", "data" => $translate->_("You can't delete yourself.")));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("You can't delete yourself.")));
                        }

                    } else {
                        echo json_encode(array("status" => "error", "data" => "Author not found."));
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
                        $PROCESSED["group_id"] = $tmp_input;
                    }

                    if ($PROCESSED["member_id"] && $PROCESSED["member_type"] && $PROCESSED["group_id"]) {
                        $a = Models_Exam_Group_Author::fetchRowByGroupIDAuthorIDAuthorType($PROCESSED["group_id"], $PROCESSED["member_id"], $PROCESSED["member_type"]);
                        if ($a) {
                            if ($a->getDeletedDate()) {
                                if ($a->fromArray(array("deleted_date" => NULL))->update()) {
                                    $added++;
                                }
                            } else {
                                application_log("notice", "Group author [".$a->getID()."] is already an active author. API should not have returned this author as an option.");
                            }
                        } else {
                            $a = new Models_Exam_Group_Author(
                                array(
                                    "group_id" => $PROCESSED["group_id"],
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
                case "update-group":

                    if (isset($request["group_id"]) && $tmp_input = clean_input($request["group_id"], "int")) {
                        $PROCESSED["group_id"] = $tmp_input;
                    }

                    if (isset($request["group_title"]) && $tmp_input = clean_input($request["group_title"], array("trim", "striptags"))) {
                        $PROCESSED["group_title"] = $tmp_input;
                    }

                    if (isset($request["group_description"]) && $tmp_input = clean_input($request["group_description"], array("trim", "striptags"))) {
                        $PROCESSED["group_description"] = $tmp_input;
                    }

                    if ($PROCESSED["group_id"]) {
                        $group = Models_Exam_Group::fetchRowByID($PROCESSED["group_id"]);
                        if ($group) {
                            $group->setGroupTitle($PROCESSED["group_title"]);
                            $group->setGroupDescription($PROCESSED["group_description"]);
                            if (!$group->update()) {
                                add_error("Failed to update group");
                            }
                        }
                    }

                    if (has_error()) {
                        echo json_encode(array("status" => "error", "msg" => $translate->_("Failed to update group")));
                    } else {
                        echo json_encode(array("status" => "success", "msg" => $translate->_("Updated Group successfully.")));
                    }
                    break;
                case "update-group-question-order" :

                    if (isset($request["question"]) && is_array($request["question"])) {
                        foreach ($request["question"] as $egquestion_id) {
                            $tmp_input = clean_input($egquestion_id, "int");
                            if ($tmp_input) {
                                $PROCESSED["egquestion_ids"][] = $tmp_input;
                            }
                        }
                    }

                    if (isset($request["group_id"])) {
                        $tmp_input = clean_input($request["group_id"], "int");
                        if ($tmp_input) {
                            $PROCESSED["group_id"] = $tmp_input;
                        }
                    }

                    if (isset($request["exam_id"])) {
                        $tmp_input = clean_input($request["exam_id"], "int");
                        if ($tmp_input) {
                            $PROCESSED["exam_id"] = $tmp_input;
                        }
                    }

                    $exam_elements = Models_Exam_Exam_Element::fetchAllByGroupID($PROCESSED["group_id"]);

                    if ($exam_elements && is_array($exam_elements) && !empty($exam_elements)) {
                        $versions = array();
                        foreach ($exam_elements as $exam_element) {
                            $versions[$exam_element->getOrder()] = $exam_element->getOrder();
                        }

                        $order_start = min($versions);
                    }

                    if (isset($PROCESSED["egquestion_ids"]) && !empty($PROCESSED["egquestion_ids"])) {
                        $i = 1;
                        $new_order = $order_start;

                        foreach ($PROCESSED["egquestion_ids"] as $egquestion_id) {
                            $group_question = Models_Exam_Group_Question::fetchRowByID($egquestion_id);
                            $version_id     = $group_question->getVersionID();
                            if (!$group_question->fromArray(array("order" => $i))->update()) {
                                $ERROR++;
                            } else {
                                $exam_element = Models_Exam_Exam_Element::fetchRowByExamIDElementIDElementType($PROCESSED["exam_id"], $version_id);

                                if ($exam_element && is_object($exam_element)) {
                                    $exam_element->setOrder($new_order);
                                    if (!$exam_element->update()) {
                                        $ERROR++;
                                    }
                                }
                                $new_order++;
                            }
                            $i++;
                            $ENTRADA_LOGGER->log("", "update-group-question-order", "egquestion_id", $group_question->getID(), 4, __FILE__, $ENTRADA_USER->getID());
                        }

                        if ($ERROR) {
                            echo json_encode(array("status" => "error", "data" => $translate->_("Failed to update order")));
                        } else {
                            echo json_encode(array("status" => "success", "data" => $translate->_("Grouped question order successfully updated")));
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
                        Models_Exam_Group::saveFilterPreferences($PROCESSED["filters"]);
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

                    if (isset($PROCESSED["filter_type"]) && isset($PROCESSED["filter_target"])) {
                        unset($_SESSION[APPLICATION_IDENTIFIER]["exams"]["groups"]["selected_filters"][$PROCESSED["filter_type"]][$PROCESSED["filter_target"]]);
                        if (empty($_SESSION[APPLICATION_IDENTIFIER]["exams"]["groups"]["selected_filters"][$PROCESSED["filter_type"]])) {
                            unset($_SESSION[APPLICATION_IDENTIFIER]["exams"]["groups"]["selected_filters"][$PROCESSED["filter_type"]]);
                            if (empty($_SESSION[APPLICATION_IDENTIFIER]["exams"]["groups"]["selected_filters"])) {
                                unset($_SESSION[APPLICATION_IDENTIFIER]["exams"]["groups"]["selected_filters"]);
                            }
                        }
                        echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully removed the selected filter")));
                    } else {
                        echo json_encode(array("status" => "error", "msg" => $ERRORSTR));
                    }
                break;
                case "remove-all-filters" :
                    unset($_SESSION[APPLICATION_IDENTIFIER]["exams"]["groups"]["selected_filters"]);
                    echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully removed all filters")));
                break;
                case "get-question-group-delete-permission" :
                    if (isset($request["group_id"]) && $tmp_input = clean_input($request["group_id"], "int")) {
                        $PROCESSED["group_id"] = $tmp_input;
                    } else {
                        add_error("Group ID is required");
                    }
                    if (isset($PROCESSED["group_id"]) && $PROCESSED["group_id"] != "") {
                        $delete = $ENTRADA_ACL->amIAllowed(new ExamQuestionGroupResource($PROCESSED["group_id"], true), "delete");
                        if ($delete) {
                            $exam_elements = Models_Exam_Exam_Element::fetchAllByGroupID($PROCESSED["group_id"]);
                            if ($exam_elements && is_array($exam_elements) && !empty($exam_elements)) {
                                foreach ($exam_elements as $exam_element)
                                if ($exam_element && is_object($exam_element)) {
                                    $exam_id = $exam_element->getExamID();
                                    $exam_post = Models_Exam_Post::fetchAllByExamIDNoPreview($exam_id);
                                    if ($exam_post && is_array($exam_post) && !empty($exam_post)) {
                                        $delete = false;
                                    }
                                }
                            }
                        }
                    }

                    if ($delete === true) {
                        echo json_encode(array("status" => "success", "group_id" => $PROCESSED["group_id"]));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("Access Denied")));
                    }
                    break;
                default:
                    echo json_encode(array("status" => "error", "data" => $translate->_("Invalid POST method.")));
                break;
            }
            break;
        case "GET" :
            if (isset($request["group_id"]) && $tmp_input = clean_input($request["group_id"], "int")) {
                $PROCESSED["group_id"] = $tmp_input;
            }
            switch ($request["method"]) {
                case "get-groups" :
                    $PROCESSED["filters"] = array();
                    if (isset($_SESSION[APPLICATION_IDENTIFIER]["exams"]["groups"]["selected_filters"])) {
                        $PROCESSED["filters"] = $_SESSION[APPLICATION_IDENTIFIER]["exams"]["groups"]["selected_filters"];
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
                        $PROCESSED["sort_column"] = "group_id";
                    }

                    if (isset($request["itemtype_id"]) && $tmp_input = clean_input(strtolower($request["itemtype_id"]), array("trim", "int"))) {
                        $PROCESSED["itemtype_id"] = $tmp_input;
                    } else {
                        $PROCESSED["itemtype_id"] = 0;
                    }

                    if ($PROCESSED["itemtype_id"] == 12) {
                        $PROCESSED["is_scale"] = 1;
                    } else {
                        $PROCESSED["is_scale"] = 0;
                    }

                    $groups = Models_Exam_Group::fetchAllRecordsBySearchTerm($PROCESSED["search_term"], $PROCESSED["limit"], $PROCESSED["offset"], $PROCESSED["sort_direction"], $PROCESSED["sort_column"], $PROCESSED["filters"]);
                    
                    if ($groups) {
                        $data = array();
                        foreach ($groups as $group) {
                            $data[] = array("group_id" => $group["group_id"], "title" => $group["group_title"], "created_date" => ($group["created_date"] && !is_null($group["created_date"]) ? date("Y-m-d", $group["created_date"]) : "N/A"));
                        }
                        echo json_encode(array("results" => count($data), "data" => array("total_groups" => Models_Exam_Group::countAllRecordsBySearchTerm($PROCESSED["search_term"], $PROCESSED["filters"]), "groups" => $data)));
                    } else {
                        echo json_encode(array("results" => "0", "data" => $translate->_("You currently have no Grouped Items to display. To Add a new Grouped Item click the Add Group button above.")));
                    }
                break;
                case "get-group" :
                    if (isset($request["group_id"]) && $tmp_input = clean_input(strtolower($request["group_id"]), array("trim", "int"))) {
                        $PROCESSED["group_id"] = $tmp_input;
                    } else {
                        $PROCESSED["group_id"] = 0;
                    }

                    $group = Models_Exam_Group::fetchRowByID($PROCESSED["group_id"]);
                    $group_data["group"] = $group->toArray();
                    $group_questions = $group->getGroupQuestions();

                    if (!empty($group_questions)){
                        foreach ($group_questions as $group_question){
                            $question_version = Models_Exam_Question_Versions::fetchRowByQuestionID($group_question->getQuestionID(), $group_question->getVersionID());
                            $question_view = new Views_Exam_Question($question_version);
                            if ($question_view) {
                                $data = array();

                                $question_render_details = $question_view->render($PROCESSED["exam_mode"], NULL, NULL, 'details', false);
                                $question_render_list = $question_view->render($PROCESSED["exam_mode"], NULL, NULL, 'list',  false);
                                if ($question_render_details && $question_render_list) {
                                    $data["html_details"] = $question_render_details;
                                    //$data["html_list"] = $question_render_list; //Removed this for now since it automatically renders all the admin control buttons.
                                    $data["id"] = $question_version->getID();
                                    $data["version"] = $question_version->getVersionCount();
                                    $data["question_code"] = ($question_version->getQuestionCode() ? html_encode($question_version->getQuestionCode()) : "N/A");
                                    $data["question_type"] = $question_version->getQuestionType()->getName();
                                    $data["question_description"] = ($question_version->getQuestionDescription() ? html_encode($question_version->getQuestionDescription()) : "N/A");

                                    $group_data["questions"][] = $data;
                                } else {
                                    $group_data["questions"][] = array($translate->_("Error rendering view for this question."));
                                }
                            } else {
                                $group_data["questions"][] = array($translate->_("This question has no answers."));
                            }
                        }
                    } else {
                        $group_data["questions"][] = array($translate->_("No results"));
                    }

                    echo json_encode(array("status" => "success", "data" => $group_data));

                break;
                case "get-user-groups" :

                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = null;
                    }
                    
                    $authored_groups = Models_Exam_Group_Author::fetchAllByAuthorIDAuthorType($ENTRADA_USER->getID(), "proxy_id", $PROCESSED["search_value"]);


                    if (isset($authored_groups) && is_array($authored_groups)) {
                        $data = array();
                        foreach ($authored_groups as $authored_group) {
                            if (isset($authored_group) && is_object($authored_group)) {
                                $group = $authored_group->getGroup();
                                if ($group) {
                                    $group_title = ($group->getGroupTitle() ? $group->getGroupTitle() : "N/A");
                                }
                                $data[] = array("target_id" => $authored_group->getGroupID(), "target_label" => $group_title);
                            }
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No question groups were found.")));
                    }
                    break;

                case "get-exam-groups" :

                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = null;
                    }

                    if (isset($request["exam_id"])) {
                        $tmp_input = (int)$request["exam_id"];
                        $PROCESSED["exam_id"] = $tmp_input;
                    }

                    $groups = array();

                    $exam_elements_grouped = Models_Exam_Exam_Element::fetchAllByExamIDGrouped($PROCESSED["exam_id"]);
                    if ($exam_elements_grouped && is_array($exam_elements_grouped)) {
                        foreach ($exam_elements_grouped as $element) {
                            if ($element && is_object($element)) {
                                $group_id = $element->getGroupID();
                                if (!array_key_exists($group_id, $groups)) {
                                    $group = Models_Exam_Group::fetchRowByID($group_id);
                                    $groups[$group_id] = $group;
                                }
                            }
                        }
                    }
                    
                    if (isset($groups) && is_array($groups)) {
                        $data = array();
                        foreach ($groups as $group) {
                            if (isset($group) && is_object($group)) {
                                if ($group) {
                                    $group_title = ($group->getGroupTitle() ? $group->getGroupTitle() : "N/A");
                                }
                                $data[] = array("target_id" => $group->getGroupID(), "target_label" => $group_title);
                            }
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No question groups were found.")));
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
                        $PROCESSED["group_id"] = $tmp_input;
                    }

                    $results = Models_Exam_Group_Author::fetchAvailableAuthors($PROCESSED["filter_type"], $PROCESSED["group_id"], $PROCESSED["search_value"]);
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
                case "get-group-authors" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }
                    
                    $authors = Models_Exam_Group_Author::fetchByAuthorTypeProxyID($ENTRADA_USER->getActiveOrganisation(), $PROCESSED["search_value"]);
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