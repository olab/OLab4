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
 * API to handle interaction with the Exam Question Bank.
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 *
 */

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
} elseif (!$ENTRADA_ACL->amIAllowed("exam", "read", true)) {
	add_error(sprintf($translate->_("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error Please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    ob_clear_open_buffers();
    
	$request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));
	
	$request = ${"_" . $request_method};

    if (isset($request["method"]) && $tmp_input = clean_input($request["method"], array("trim", "striptags"))) {
        $method = $tmp_input;
    } else {
        add_error($translate->_("No method supplied."));
    }
    
    if (!has_error()) {
        switch ($request_method) {
            case "GET" :
                switch ($method) {
                    case "get-questions" :
                        /*
                        Questions from
                        */

                        if (isset($request["exam_mode"]) && $request["exam_mode"] == "false") {
                            $PROCESSED["exam_mode"] = false;
                        } else {
                            $PROCESSED["exam_mode"] = true;
                        }

                        if (isset($request["folder_id"]) && $tmp_input = clean_input(strtolower($request["folder_id"]), array("trim", "int"))) {
                            $PROCESSED["folder_id"] = $tmp_input;
                        } else if ($request["folder_id"] == 0) {
                            $PROCESSED["folder_id"] = 0;
                        } else {
                            add_error($translate->_("A problem occurred while attempting to fetch questions for this folder. Please try again later"));
                        }

                        $PROCESSED["filters"] = array();
                        if (isset($_SESSION[APPLICATION_IDENTIFIER]["exams"]["questions"]["selected_filters"])) {
                            $PROCESSED["filters"] = $_SESSION[APPLICATION_IDENTIFIER]["exams"]["questions"]["selected_filters"];
                        }

                        if (isset($request["search_term"]) && $tmp_input = clean_input(strtolower($request["search_term"]), array("trim", "striptags"))) {
                            $PROCESSED["search_term"] = "%".$tmp_input."%";
                        } else {
                            $PROCESSED["search_term"] = "";
                        }

                        if (isset($request["sort_direction"])) {
                            $tmp_input = clean_input(strtolower($request["sort_direction"]), array("trim", "striptags"));
                            $PROCESSED["sort_direction"] = $tmp_input;
                        } else {
                            $PROCESSED["sort_direction"] = "DESC";
                        }

                        if (isset($request["sort_column"])) {
                            $tmp_input = clean_input(strtolower($request["sort_column"]), array("trim", "striptags"));
                            $PROCESSED["sort_column"] = $tmp_input;
                        } else {
                            $PROCESSED["sort_column"] = "updated_date";
                        }

                        if (isset($request["limit"]) && $tmp_input = clean_input(strtolower($request["limit"]), array("trim", "int"))) {
                            $PROCESSED["limit"] = $tmp_input;
                        } else {
                            $PROCESSED["limit"] = 25;
                        }

                        if (isset($request["folder_id"]) && $tmp_input = clean_input(strtolower($request["folder_id"]), array("trim", "int"))) {
                            $PROCESSED["folder_id"] = $tmp_input;
                        } else {
                            $PROCESSED["folder_id"] = 0;
                        }

                        if (isset($request["offset"]) && $tmp_input = clean_input(strtolower($request["offset"]), array("trim", "int"))) {
                            $PROCESSED["offset"] = $tmp_input;
                        } else {
                            $PROCESSED["offset"] = 0;
                        }

                        if (isset($request["sub_folder_search"]) && $tmp_input = clean_input(strtolower($request["sub_folder_search"]), array("trim", "int"))) {
                            $PROCESSED["sub_folder_search"] = $tmp_input;
                        } else {
                            $PROCESSED["sub_folder_search"] = 0;
                        }

                        if (isset($request["view"]) && $tmp_input = clean_input(strtolower($request["view"]), array("trim", "alpha"))) {
                            $PROCESSED["view"] = $tmp_input;
                        }

                        $PROCESSED["group_width"] = NULL;
                        if (isset($request["group_width"]) && $tmp_input = clean_input(strtolower($request["group_width"]), array("trim", "int"))) {
                            $PROCESSED["group_width"] = $tmp_input;
                        }

                        if (isset($request["active_details"]) && $tmp_input = clean_input(strtolower($request["active_details"]), array("trim", "int"))) {
                            $PROCESSED["active_details"] = $tmp_input;
                        } else {
                            $PROCESSED["active_details"] = 0;
                        }

                        $PROCESSED["search_questiontype_id"] = NULL;
                        if (isset($_GET["search_questiontype_id"])) {
                            $search_question_types_array = $_GET["search_questiontype_id"];
                            if ($search_question_types_array && is_array($search_question_types_array)) {
                                $PROCESSED["search_questiontype_id"] = array();
                                foreach($search_question_types_array as $question_type) {
                                    $PROCESSED["search_questiontype_id"][] = clean_input($question_type, array("int"));
                                }
                            }
                        }

                        $PROCESSED["exam_id"] = NULL;
                        if (isset($request["exam_id"]) && $tmp_input = clean_input(strtolower($request["exam_id"]), array("trim", "int"))) {
                            $PROCESSED["exam_id"] = $tmp_input;
                        }

                        $PROCESSED["group_questions"] = NULL;
                        if (isset($_GET["group_questions"])) {
                            $group_questions_array = $_GET["group_questions"];
                            if ($group_questions_array && is_array($group_questions_array)) {
                                $PROCESSED["group_questions"] = array();
                                foreach($group_questions_array as $question) {
                                    $question = clean_input($question, array("int"));
                                    $PROCESSED["group_questions"][] = $question;
                                }
                            }
                        }

                        $PROCESSED["group_descriptors"] = NULL;
//                        if (isset($_GET["group_descriptors"])) {
//                            $group_descriptors_array = $_GET["group_descriptors"];
//                            if ($group_descriptors_array) {
//                                foreach($group_descriptors_array as $descriptor) {
//                                    $descriptor = clean_input($descriptor, array("int"));
//                                    $PROCESSED["group_descriptors"][] = $descriptor;
//                                }
//                            }
//                        }

                        $PROCESSED["exclude_question_ids"] = NULL;
//                        if (isset($_GET["exclude_question_ids"])) {
//                            $exclude_question_ids_array = $_GET["exclude_question_ids"];
//                            if ($exclude_question_ids_array) {
//                                foreach($exclude_question_ids_array as $descriptor) {
//                                    $descriptor = clean_input($descriptor, array("int"));
//                                    $PROCESSED["exclude_question_ids"][] = $descriptor;
//                                }
//                            }
//                        }

                        $return = array();

                        if (!has_error()) {
                            /*
                             * Question section
                             */

                            $question_versions = Models_Exam_Question_Versions::fetchAllRecordsBySearchTerm(
                                $PROCESSED["search_term"],
                                $PROCESSED["limit"],
                                $PROCESSED["offset"],
                                $PROCESSED["sort_direction"],
                                $PROCESSED["sort_column"],
                                $PROCESSED["group_width"],
                                $PROCESSED["search_questiontype_id"],
                                $PROCESSED["group_questions"],
                                $PROCESSED["group_descriptors"],
                                $PROCESSED["exclude_question_ids"],
                                $PROCESSED["exam_id"],
                                $PROCESSED["filters"],
                                $PROCESSED["folder_id"],
                                $PROCESSED["sub_folder_search"]
                            );

                            $total_questions = Models_Exam_Question_Versions::countAllRecordsBySearchTerm(
                                $PROCESSED["search_term"],
                                $PROCESSED["limit"],
                                $PROCESSED["offset"],
                                $PROCESSED["sort_direction"],
                                $PROCESSED["sort_column"],
                                $PROCESSED["group_width"],
                                $PROCESSED["search_questiontype_id"],
                                $PROCESSED["group_questions"],
                                $PROCESSED["group_descriptors"],
                                $PROCESSED["exclude_question_ids"],
                                $PROCESSED["exam_id"],
                                $PROCESSED["filters"],
                                $PROCESSED["folder_id"],
                                $PROCESSED["sub_folder_search"]
                            );

                            if (isset($question_versions) && is_array($question_versions) && !empty($question_versions) || ($PROCESSED["folder_id"] == 0  && $PROCESSED["sub_folder_search"] == 1)) {
                                $data = array();
                                $data["html_list"] = "";
                                $data["html_details"] = "";
                                $question_count = 0;

                                foreach ($question_versions as $key => $question_version_array) {
                                    $question_version = new Models_Exam_Question_Versions($question_version_array);

                                    if (isset($question_version) && is_object($question_version)) {
                                        $question_view = new Views_Exam_Question($question_version);
                                        if (isset($question_view) && is_object($question_view)) {
                                            $question_count++;

                                            $question_render_details = $question_view->render($PROCESSED["exam_mode"], NULL, NULL, "details", false, NULL, NULL, NULL, 1, $PROCESSED["active_details"]);
                                            $question_render_list = $question_view->render($PROCESSED["exam_mode"], NULL, NULL, "list",  false, NULL, NULL, NULL, 1, $PROCESSED["active_details"]);
                                            if ($question_render_list && $question_render_details) {
                                                $data["html_list"] .= $question_render_list;
                                                $data["html_details"] .= $question_render_details;
                                                $return["status_question"] = "success";
                                                $return["question_data"] = $data;
                                                $return[$question_version->getVersionID()]["is_highest_version"] = 1;
                                            } else {
                                                $return["status_question"] = "error";
                                                $return["status_question_error"] = $translate->_("Error rendering view for this question.");
                                            }
                                        } else {
                                            $return["status_question"] = "error";
                                            $return["status_question_error"] = $translate->_("This question has no view.");
                                        }
                                    }
                                }
                                $return["question_count"] = $question_count;
                                $return["total_questions"] = (int)$total_questions;
                                if ($question_count === 0) {
                                    $return["status_question"] = "notice";
                                    $return["status_question_notice"] = "<p class=\"question-no-results\">No " . $translate->_("questions") . " found in this " . $translate->_("folder") . "</p>";
                                }
                            } else if ($PROCESSED["folder_id"] == 0 ) {
                                $return["status_question"] = "root_folder";
                            } else {
                                $return["status_question"] = "notice";
                                $return["status_question_notice"] = "<p class=\"question-no-results\">No " . $translate->_("questions") . " found in this " . $translate->_("folder") . "</p>";
                            }

                            /*
                             * Breadcrumb section
                             */
                            $path = array();
                            $folder = Models_Exam_Question_Bank_Folders::fetchRowByID($PROCESSED["folder_id"]);

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
                                $index_folder = new Models_Exam_Question_Bank_Folders(array(
                                    "folder_id" => 0,
                                    "parent_folder_id" => 0,
                                    "folder_title" => "Index",
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

                            /*
                             * Sub folder section
                             */
                            $subfolder_html = "";
                            $folders = Models_Exam_Question_Bank_Folders::fetchAllByParentID($PROCESSED["folder_id"]);
                            if (isset($folders) && is_array($folders) && !empty($folders)) {
                                $subfolder_html .= "<ul id=\"folder_ul\">";
                                $folder_count = count($folders);
                                foreach ($folders as $key => $folder) {
                                    if (isset($folder) && is_object($folder)) {
                                        if ($key === 0 && $PROCESSED["folder_id"] != 0) {
                                            $subfolder_html .= Views_Exam_Question_Bank_Folder::renderBackNavigation($parent_parent_folder);
                                        }
                                        $folder_view = new Views_Exam_Question_Bank_Folder($folder);
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
                                $subfolder_html .= Views_Exam_Question_Bank_Folder::renderBackNavigation($parent_parent_folder);
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

                        } else {
//                            $return["status_question"] = "error";
//                            $return["status_question_error"] = $translate->_("Error displaying view for this question.");
//                            //echo json_encode(array("status" => "error", "data" => array($translate->_("Error displaying view for this question."))));
                        }

                        echo json_encode($return);
                    break;
                    case "get-question-details" :
                        if (isset($request["question_id"]) && $tmp_input = clean_input(strtolower($request["question_id"]), array("trim", "int"))) {
                            $PROCESSED["question_id"] = $tmp_input;
                        } else { 
                            add_error($translate->_("A problem occurred while attempting to fetch data for this question. Please try again later."));
                        }

                        if (isset($request["version_id"]) && $tmp_input = clean_input(strtolower($request["version_id"]), array("trim", "int"))) {
                            $PROCESSED["version_id"] = $tmp_input;
                        } else {
                            //uses the question ID to generate the latest revision ID
                            $versions_id = Models_Exam_Question_Versions::getLatestVersionByQuestionID($question["question_id"]);
                            $PROCESSED["version_id"] = $versions_id["version_id"];
                        }

                        if (!$ERROR) {
                            $question = Models_Exam_Question_Versions::fetchRowByID($PROCESSED["question_id"], $PROCESSED["version_id"]);
                            if ($question) {
                                $question_tags = $question->getQuestionTags();
                                $question_answers = $question->getQuestionAnswers();
                                
                                $data = array();
                                $data["question_id"] = $question->getQuestionID();
                                $data["version_id"] = $question->getVersionID();
                                $data["question_code"] = ($question->getQuestionCode() ? $question->getQuestionCode() : "N/A");
                                $data["total_answers"] = count($question_answers);
                                $data["created_date"] = ($question->getCreatedDate() != "0" ? date("Y-m-d", $question->getCreatedDate()) : $translate->_("N/A"));
                                if ($question_tags) {
                                    foreach ($question_tags as $tag) {
                                        $data["tags"][] = array("tag" => $tag->getTag());
                                    }
                                }
                                echo json_encode(array("status" => "success", "data" => $data));
                            } else {
                                echo json_encode(array("status" => "error", "data" => array($translate->_("A problem occurred while attempting to fetch data for this question. Please try again later."))));
                            }
                        } else {
                           echo json_encode(array("status" => "error", "data" => array($ERRORSTR))); 
                        }

                    break;
                    case "get-question-answers" :
                        if (isset($request["question_id"]) && $tmp_input = clean_input(strtolower($request["question_id"]), array("trim", "int"))) {
                            $PROCESSED["question_id"] = $tmp_input;
                        } else {
                            add_error($translate->_("A problem occurred while attempting to fetch data for this question. Please try again later"));
                        }

                        if (isset($request["version_id"]) && $tmp_input = clean_input(strtolower($request["version_id"]), array("trim", "int"))) {
                            $PROCESSED["version_id"] = $tmp_input;
                        } else {
                            //uses the question ID to generate the latest revision ID
                            $versions_id = Models_Exam_Question_Versions::getLatestVersionByQuestionID($question["question_id"]);
                            $PROCESSED["version_id"] = $versions_id["version_id"];
                        }

                        if (!has_error()) {
                            $question_answers = Models_Exam_Question_Answers::fetchAllRecordsByQuestionIDResponseID($PROCESSED["question_id"], $PROCESSED["version_id"]);
                            if ($question_answers) {
                                $data = array();
                                $data["total_answers"] = count($question_answers);
                                
                                foreach ($question_answers as $answer) {
                                    $data["answers"][] = array(
                                        "qanswer_id" => $answer->getID(),
                                        "text" => $answer->getAnswerText(),
                                        "question_id" => $PROCESSED["question_id"],
                                        "version_id" => $answer->getVersionID(),
                                        "answer_rationale" => $answer->getRationale(),
                                        "correct" => $answer->getCorrect(),
                                        "updated_date" => $answer->getUpdatedDate(),
                                        "updated_by" => $answer->getUpdatedBy()
                                    );
                                }

                                echo json_encode(array("status" => "success", "data" => $data));
                            } else {
                                echo json_encode(array("status" => "error", "data" => array($translate->_("This question has no answers."))));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("This question has no answers."))));
                        }
                    break;
                    case "get-question-tags" :
                        if (isset($request["question_id"]) && $tmp_input = clean_input(strtolower($request["question_id"]), array("trim", "int"))) {
                            $PROCESSED["question_id"] = $tmp_input;
                        } else {
                            add_error($translate->_("A problem occurred while attempting to fetch data for this question. Please try again later"));
                        }
                        
                        if (!has_error()) {
                            $tags = Models_Exams_Tag::fetchAllRecordsByQuestionID($PROCESSED["question_id"]);
                            
                            if ($tags) {
                                $data = array();
                                foreach ($tags as $tag) {
                                    $data[] = array("tag" => $tag->getTag());
                                }
                                echo json_encode(array("status" => "success", "data" => $data));
                            } else {
                                echo json_encode(array("status" => "error", "data" => array($translate->_("This question has no tags."))));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("A problem occurred while attempting to fetch data for this question. Please try again later."))));
                        }
                    break;
                    case "get-filtered-audience" :
                        if (isset($request["question_id"]) && $tmp_input = clean_input(strtolower($request["question_id"]), array("trim", "int"))) {
                            $PROCESSED["question_id"] = $tmp_input;
                        }
                        
                        if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                            $PROCESSED["search_value"] = "%".$tmp_input."%";
                        }

                        if (isset($request["filter_type"]) && $tmp_input = clean_input($request["filter_type"], array("trim", "striptags"))) {
                            $PROCESSED["filter_type"] = $tmp_input;
                        }

                        if (isset($request["content_target"]) && $tmp_input = clean_input($request["content_target"], "int")) {
                            $PROCESSED["question_id"] = $tmp_input;
                        }

                        $results = Models_Exam_Question_Authors::fetchAvailableAuthors($PROCESSED["filter_type"], $PROCESSED["question_id"], $PROCESSED["search_value"]);
                        if ($results) {
                            echo json_encode(array("results" => count($results), "data" => $results));
                        } else {
                            echo json_encode(array("results" => "0", "data" => array($translate->_("No results"))));
                        }
                    break;
                    case "get-answer-descriptors" :
                        $descriptors = Models_Exams_Answer_Descriptor::fetchAllByOrganisationIDSystemType($ENTRADA_USER->getActiveOrganisation(), "entrada");

                        if ($descriptors) {
                            $data = array();
                            foreach ($descriptors as $descriptor) {
                                $data[] = array("ardescriptor_id" => $descriptor->getID(), "descriptor" => $descriptor->getDescriptor());
                            }
                            echo json_encode(array("status" => "success", "data" => $data));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("No descriptors found"))));
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
                            echo json_encode(array("status" => "success", "data" => $data, "parent_id" => ($parent_objective ? $parent_objective->getParent() : "0"), "parent_name" => ($parent_objective ? $parent_objective->getName() : "0"), "level_selectable" => 0));
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
                            echo json_encode(array("status" => "success", "data" => $data, "parent_id" => ($parent_objective ? $parent_objective->getParent() : "0"), "parent_name" => ($parent_objective ? $parent_objective->getName() : "0"), "level_selectable" => 0));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No objectives found to display.")));
                        }
                    break;
                    case "get-fieldnote-objectives" :
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
                                $total_child_objectives = (int) Models_Objective::countObjectiveChildren($objective->getID());
                                $data[] = array("target_id" => $objective->getID(), "target_parent" => $objective->getParent(), "target_label" => $objective->getName(), "target_children" => $total_child_objectives, "level_selectable" =>  ($total_child_objectives > 0 ? 0 : 1));
                            }
                            echo json_encode(array("status" => "success", "data" => $data, "parent_id" => ($parent_objective ? $parent_objective->getParent() : "0"), "parent_name" => ($parent_objective ? $parent_objective->getName() : "0")));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No objectives found to display.")));
                        }
                        
                    break;
                    case "get-fieldnote-child-objectives" :
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
                                $total_child_objectives = (int) Models_Objective::countObjectiveChildren($objective->getID());
                                $data[] = array("target_id" => $objective->getID(), "target_parent" => $objective->getParent(), "target_label" => $objective->getName(), "target_children" => $total_child_objectives, "level_selectable" =>  ($total_child_objectives > 0 ? 0 : 1));
                            }
                            echo json_encode(array("status" => "success", "data" => $data, "parent_id" => ($parent_objective ? $parent_objective->getParent() : "0"), "parent_name" => ($parent_objective ? $parent_objective->getName() : "0")));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No objectives found to display.")));
                        }
                    break;
                    case "get-question-authors" :
                        if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                            $PROCESSED["search_value"] = $tmp_input;
                        } else {
                            $PROCESSED["search_value"] = "";
                        }
                        
                        $authors = Models_Exam_Question_Authors::fetchByAuthorTypeProxyID($ENTRADA_USER->getActiveOrganisation(), $PROCESSED["search_value"]);
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

                    case "get-user-exams" :
                        if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                            $PROCESSED["search_value"] = $tmp_input;
                        } else {
                            $PROCESSED["search_value"] = "";
                        }

                        $exams = Models_Exam_Exam::fetchAllByOwner($ENTRADA_USER->getID(), $PROCESSED["search_value"]);
                        if (isset($exams) && is_array($exams)) {
                            $data = array();
                            foreach ($exams as $exam) {
                                if (isset($exam) && is_object($exam)) {
                                    $exam_title = ($exam->getTitle() ? $exam->getTitle() : "N/A");
                                    $data[] = array("target_id" => $exam->getExamID(), "target_label" => $exam_title);
                                }
                            }
                            echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No exams were found.")));
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
                        if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                            $PROCESSED["search_value"] = $tmp_input;
                        } else {
                            $PROCESSED["search_value"] = "";
                        }
                        
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
                    case "get-question-delete-permission" :
                        if (isset($request["questions"]) && is_array($request["questions"])) {
                            $PROCESSED["questions"] = array();
                            foreach ($request["questions"] as $version_id) {
                                $tmp_input = clean_input(strtolower($version_id), array("trim", "int"));
                                $delete = $ENTRADA_ACL->amIAllowed(new ExamQuestionResource($tmp_input, true), "delete");

                                if ($delete === true) {
                                    //get all exams with this question $question
                                    $exam_elements = Models_Exam_Exam_Element::fetchAllByElementIDElementType($version_id, "question");
                                    if (isset($exam_elements) && !empty($exam_elements)) {
                                        $delete = false;
                                    }
                                }

                                $PROCESSED["questions"][] = array(
                                    "question_id"   => $tmp_input,
                                    "delete"        => $delete
                                );
                            }
                        }

                        if (isset($PROCESSED["questions"]) && is_array($PROCESSED["questions"])) {
                            echo json_encode(array("status" => "success", "questions" => $PROCESSED["questions"]));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No questions were found.")));
                        }
                        break;
                    case "get-question-move-permission" :
                        if (isset($request["questions"]) && is_array($request["questions"])) {
                            $PROCESSED["questions"] = array();
                            foreach ($request["questions"] as $version_id) {
                                $tmp_input = clean_input(strtolower($version_id), array("trim", "int"));
                                $move = $ENTRADA_ACL->amIAllowed(new ExamQuestionResource($tmp_input, true), "update");

                                /*
                                 * This section disable
                                 *
                                 * If we want to stop people from moving questions
                                 * once they've been attached to an exam remove this comment
                                 *
                                if ($move === true) {
                                    //get all exams with this question $question
                                    $exam_elements = Models_Exam_Exam_Element::fetchAllByElementIDElementType($version_id, "question");
                                    if (isset($exam_elements) && !empty($exam_elements)) {
                                        $move = false;
                                    }
                                }
                                */

                                $PROCESSED["questions"][] = array(
                                    "question_id"   => $tmp_input,
                                    "move"        => $move
                                );
                            }
                        }

                        if (isset($PROCESSED["questions"]) && is_array($PROCESSED["questions"])) {
                            echo json_encode(array("status" => "success", "questions" => $PROCESSED["questions"]));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No questions were found.")));
                        }
                        break;
                    case "get-linked-questions" :
                        if (isset($request["question_version_id"]) && $tmp_input = clean_input(strtolower($request["question_version_id"]), array("trim", "int"))) {
                            $PROCESSED["question_version_id"] = $tmp_input;
                        } else {
                            $PROCESSED["question_version_id"] = 0;
                        }

                        $group_questions = Models_Exam_Group_Question::fetchAllByVersionID($PROCESSED["question_version_id"]);
                        if (!empty($group_questions)) {
                            $data = array();
                            foreach ($group_questions as $group_question) {
                                $group = $group_question->getGroup();
                                $group_id = ($group->getID() ? $group->getID() : NULL);
                                $group_title = ($group->getGroupTitle() ? $group->getGroupTitle() : "N/A");
                                $group_description = ($group->getGroupDescription() ? $group->getGroupDescription() : "N/A");
                                $updated_date = ($group->getUpdatedDate() ? date("Y-m-d", $group->getUpdatedDate()) : "N/A");
                                
                                $data[] = array("group_id" => $group_id, "group_title" => $group_title, "group_description" => $group_description, "updated_date" => $updated_date);
                            }
                            echo json_encode(array("status" => "success", "data" => $data));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No linked question groups were found")));
                        }
                        break;
                    default:
                        echo json_encode(array("status" => "error", "data" => $translate->_("Invalid GET method.")));
                    break;
                }
            break;    
            case "POST" :
                switch ($method) {
                    case "build-question-answers" :
                        if (isset($request["exam_mode"]) && $request["exam_mode"] == "false") {
                            $PROCESSED["exam_mode"] = false;
                        } else {
                            $PROCESSED["exam_mode"] = true;
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

                        if (!has_error()) {
                            $question_version = Models_Exam_Question_Versions::fetchRowByQuestionID($PROCESSED["question"]["question_id"], $PROCESSED["version_id"]);
                            $question_view = new Views_Exam_Question($question_version);
                            if ($question_view) {
                                $data = array();

                                $question_render_details = $question_view->render($PROCESSED["exam_mode"], NULL, NULL, 'details', false);
                                $question_render_list = $question_view->render($PROCESSED["exam_mode"], NULL, NULL, 'list',  false);
                                if ($question_render_details && $question_render_list) {
                                    $data["html_details"] = $question_render_details;
                                    $data["html_list"] = $question_render_list;
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
                    case "get-answer-row" :
                        global $translate;
                        if (isset($request["answer_number"]) && $tmp_input = clean_input(strtolower($request["answer_number"]), array("trim", "int"))) {
                            $PROCESSED["answer_number"] = $tmp_input;
                        } else if ($request["answer_number"] == 0) {
                            $PROCESSED["answer_number"] = 0;
                        } else {
                            add_error($translate->_("A problem occurred while attempting to fetch answer row for this question. Please try again later"));
                        }

                        if (isset($request["question_type_name"]) && $tmp_input = $request["question_type_name"]) {
                            $PROCESSED["question_type_name"] = $tmp_input;
                        } else {
                            add_error($translate->_("A problem occurred while attempting to get question types. Please try again later"));
                        }

                        if (isset($request["submodule_text"]) && $tmp_input = $request["submodule_text"]) {
                            $PROCESSED["submodule_text"] = $tmp_input;
                        } else {
                            add_error($translate->_("A problem occurred while attempting to get submodule_text. Please try again later"));
                        }

                        $answer = new Models_Exam_Question_Answers(array(
                            "qanswer_id",
                            "question_id",
                            "version_id",
                            "answer_text",
                            "answer_rationale",
                            "correct",
                            "order" => $PROCESSED["answer_number"],
                            "updated_date",
                            "updated_by",
                            "deleted_date"
                        ));

                        $answer_row_view = new Views_Exam_Question_Answer($answer);

                        if (isset($answer_row_view)) {
                            $return["status"] = "success";
                            $return["answer_row"] = $answer_row_view->renderAnswer(NULL, $PROCESSED["question_type_name"]);
                        }

                        echo json_encode($return);
                        break;
                    case "get-stem-row" :
                        global $translate;
                        if (isset($request["stem_number"]) && $tmp_input = clean_input(strtolower($request["stem_number"]), array("trim", "int"))) {
                            $PROCESSED["stem_number"] = $tmp_input;
                        } else if ($request["stem_number"] == 0) {
                            $PROCESSED["stem_number"] = 0;
                        } else {
                            add_error($translate->_("A problem occurred while attempting to fetch answer row for this question. Please try again later"));
                        }

                        if (isset($request["question_type_name"]) && $tmp_input = $request["question_type_name"]) {
                            $PROCESSED["question_type_name"] = $tmp_input;
                        } else {
                            add_error($translate->_("A problem occurred while attempting to get question types. Please try again later"));
                        }

                        if (isset($request["submodule_text"]) && $tmp_input = $request["submodule_text"]) {
                            $PROCESSED["submodule_text"] = $tmp_input;
                        } else {
                            add_error($translate->_("A problem occurred while attempting to get submodule_text. Please try again later"));
                        }

                        $match = new Models_Exam_Question_Match(array(
                            "match_id",
                            "version_id",
                            "match_text",
                            "order" => $PROCESSED["stem_number"],
                            "updated_date",
                            "updated_by",
                            "deleted_date"
                        ));

                        $match_row_view = new Views_Exam_Question_Match($match);

                        if (isset($match_row_view)) {
                            $return["status"] = "success";
                            $return["match_row"] = $match_row_view->renderMatch(NULL, $PROCESSED["question_type_name"]);
                        }

                        echo json_encode($return);
                        break;
                    case "get-question-type-shortname" :
                        if (isset($request["question_type_id"]) && $tmp_input = $request["question_type_id"]) {
                            $PROCESSED["question_type_id"] = $tmp_input;
                        } else {
                            add_error($translate->_("A problem occurred while attempting to fetch data for this question. Please try again later"));
                        }

                        $question_type = Models_Exam_Lu_Questiontypes::fetchRowByID($PROCESSED["question_type_id"]);
                        if ($question_type) {
                            $data ["shortname"] = $question_type->getShortname();
                            echo json_encode(array("status" => "success", "data" => $data));
                        }

                        break;
                    case "view-preference" :
                        if (isset($request["selected_view"]) && $tmp_input = clean_input($request["selected_view"], array("trim", "striptags"))) {
                            $selected_view = $tmp_input;
                        } else {
                            add_error($translate->_("No Question Bank view was selected"));
                        }
                        
                        if (!has_error()) {
                            $_SESSION[APPLICATION_IDENTIFIER]["exams"]["questions"]["selected_view"] = $selected_view;
                            echo json_encode(array("status" => "success", "data" => array($selected_view)));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                    break;
                    case "remove-permission" :
                        if (isset($request["author_id"]) && $tmp_input = clean_input($request["author_id"], "int")) {
                            $PROCESSED["author_id"] = $tmp_input;
                        }

                        if (isset($PROCESSED["author_id"])) {

                            $author = Models_Exam_Question_Authors::fetchRowByID($PROCESSED["author_id"]);
                            if (($author->getAuthorType() == "proxy_id" && $author->getAuthorID() != $ENTRADA_USER->getActiveID()) || $author->getAuthorType() != "proxy_id") {
                                if ($author->fromArray(array("deleted_date" => time(), "updated_date" => time(), "updated_by" => $ENTRADA_USER->getActiveID()))->update()) {
                                    $ENTRADA_LOGGER->log("Question Bank", "remove-permission", "author_id", $PROCESSED["author_id"], 4, __FILE__, $ENTRADA_USER->getID());
                                    echo json_encode(array("status" => "success", $translate->_("success.")));
                                } else {
                                    echo json_encode(array("status" => "error", "You can't delete yourself."));
                                }
                            } else {
                                echo json_encode(array("status" => "error", "data" => $translate->_("You can't delete yourself.")));
                            }

                        } else {
                            echo json_encode(array("status" => "error", "author_id" => $PROCESSED["author_id"]));
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
                            $PROCESSED["version_id"] = $tmp_input;
                        }

                        if ($PROCESSED["member_id"] && $PROCESSED["member_type"] && $PROCESSED["version_id"]) {
                            $added = 0;
                            $a = Models_Exam_Question_Authors::fetchRowByVersionIDAuthorIDAuthorType($PROCESSED["version_id"], $PROCESSED["member_id"], $PROCESSED["member_type"]);
                            if ($a) {
                                if ($a->getDeletedDate()) {
                                    if ($a->fromArray(array("deleted_date" => NULL))->update()) {
                                        $added++;
                                    }
                                } else {
                                    application_log("notice", "Question author [".$a->getID()."] is already an active author. API should not have returned this author as an option.");
                                }
                            } else {
                                $version = Models_Exam_Question_Versions::fetchRowByVersionID($PROCESSED["version_id"]);

                                $a = new Models_Exam_Question_Authors(
                                    array(
                                        "question_id" => $version->getQuestionID(),
                                        "version_id" => $PROCESSED["version_id"],
                                        "author_type" => $PROCESSED["member_type"],
                                        "author_id" => $PROCESSED["member_id"],
                                        "created_date" => time(),
                                        "created_by" => $ENTRADA_USER->getActiveID()
                                    )
                                );
                                if ($a->insert()) {
                                    $ENTRADA_LOGGER->log($translate->_("Question Bank"), "add-permission", "eqauthor_id", $a->getID(), 4, __FILE__, $ENTRADA_USER->getID());
                                    $added++;
                                }
                            }

                            if ($added >= 1) {
                                $author_view = new Views_Exam_Question_Author($a);
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
                                echo json_encode(array("status" => "error", "data" => array("Failed to add author")));
                            }
                        }
                    break;
                    case "update-sub-folder-search-preference" :
                        if (isset($request["action"]) && $tmp_input = clean_input($request["action"], "int")) {
                            $PROCESSED["action"] = $tmp_input;
                        }

                        if ($PROCESSED["action"] === 1) {
                            $action = "on";
                        } else {
                            $action = "off";
                        }

                        $update = Models_Exam_Question_Versions::saveSubFolderSearchPreference($action);
                        if ($update) {
                            echo json_encode(array("status" => "success", "message" => "Updated successfully."));
                        } else {
                            echo json_encode(array("status" => "error", "message" => "Failed to update."));
                        }

                        break;
                    case "move-questions" :
                        if (isset($request["folder"]) && $tmp_input = clean_input($request["folder"], "int")) {
                            $PROCESSED["folder"] = $tmp_input;
                        }

                        $PROCESSED["question_ids"] = array();
                        if (isset($request["question_ids"]) && is_array($request["question_ids"]) && !empty($request["question_ids"])) {
                            foreach ($request["question_ids"] as $key => $question) {
                                $PROCESSED["question_ids"][$key]["version_id"] = $question;
                            }
                        }

                        if (!empty($PROCESSED["question_ids"])) {
                            $moved_questions = array();
                            foreach ($PROCESSED["question_ids"] as $question) {
                                $update = $ENTRADA_ACL->amIAllowed(new ExamQuestionResource($question["version_id"], true), "update");
                                if ($update) {
                                    $version_obj = Models_Exam_Question_Versions::fetchRowByVersionID($question["version_id"]);
                                    if ($version_obj && is_object($version_obj)) {
                                        $question_obj = Models_Exam_Questions::fetchRowByID($version_obj->getQuestionID());
                                        if ($question_obj && is_object($question_obj)) {
                                            $question_obj->setFolderID($PROCESSED["folder"]);
                                            if (!$question_obj->update()) {
                                                add_error($translate->_("Unable to move a question"));
                                            } else {
                                                $ENTRADA_LOGGER->log("", "move", "version_id", $question["version_id"], 4, __FILE__, $ENTRADA_USER->getID());
                                                $moved_questions[] = $question["version_id"];
                                            }
                                        }
                                    }
                                } else {
                                    add_error($translate->_("Unable to move a question, you don't have the correct permission"));
                                }
                            }
                            if (!has_error()) {
                                echo json_encode(array("status" => "success", "msg" => sprintf($translate->_("Successfully moved %d question(s)."), count($moved_questions)), "question_ids" => $moved_questions));
                            } else {
                                echo json_encode(array("status" => "error", "msg" => $translate->_("There was an error when attempting to move a Question.")));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("Nothing to move.")));
                        }

                        break;
                    case "delete-questions" :
                        $PROCESSED["delete_ids"] = array();
                        if ($request["type"] == "single") {
                            if (isset($request["delete_ids"]) && is_array($request["delete_ids"]) && !empty($request["delete_ids"])) {
                                foreach ($request["delete_ids"] as $key => $question) {
                                    $PROCESSED["delete_ids"][$key]["version_id"] = $question;
                                }
                            }
                        } else {
                            if (isset($request["delete_ids"]) && is_array($request["delete_ids"])) {
                                foreach ($request["delete_ids"] as $group_id) {
                                    $tmp_input = clean_input($group_id, "int");
                                    if ($tmp_input) {
                                        $PROCESSED["delete_ids"][] = $tmp_input;
                                    }
                                }
                            }
                        }

                        if (!empty($PROCESSED["delete_ids"])) {
                            $deleted_questions = array();
                            foreach ($PROCESSED["delete_ids"] as $question) {
                                $delete = $ENTRADA_ACL->amIAllowed(new ExamQuestionResource($question["version_id"], true), "delete");
                                if ($delete) {
                                    $question_obj = Models_Exam_Question_Versions::fetchRowByVersionID($question["version_id"]);
                                    if ($question_obj) {
                                        $question_obj->fromArray(array(
                                                "deleted_date" => time(),
                                                "updated_date" => time(),
                                                "updated_by" => $ENTRADA_USER->getActiveID())
                                        );
                                        if (!$question_obj->update()) {
                                            add_error($translate->_("Unable to delete a question"));
                                        } else {
                                            $ENTRADA_LOGGER->log("", "delete", "version_id", $question["version_id"], 4, __FILE__, $ENTRADA_USER->getID());
                                            $deleted_questions[] = $question["version_id"];
                                        }
                                    }
                                } else {
                                    add_error($translate->_("Unable to delete a question, you don't have the correct permission"));
                                }
                            }
                            if (!has_error()) {
                                echo json_encode(array("status" => "success", "msg" => sprintf($translate->_("Successfully deleted %d question(s)."), count($deleted_questions)), "question_ids" => $deleted_questions));
                            } else {
                                echo json_encode(array("status" => "error", "msg" => $translate->_("There was an error when attempting to delete a Question.")));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("Nothing to delete.")));
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

                        if (isset($request["exam"]) && is_array($request["exam"])) {
                            $PROCESSED["filters"]["exam"] = array_filter($request["exam"], function ($exam) {
                                return (int) $exam;
                            });
                        }
                        
                        if (isset($PROCESSED["filters"])) {
                            Models_Exam_Question_Versions::saveFilterPreferences($PROCESSED["filters"]);
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
                            unset($_SESSION[APPLICATION_IDENTIFIER]["exams"]["questions"]["selected_filters"][$PROCESSED["filter_type"]][$PROCESSED["filter_target"]]);
                            if (empty($_SESSION[APPLICATION_IDENTIFIER]["exams"]["questions"]["selected_filters"][$PROCESSED["filter_type"]])) {
                                unset($_SESSION[APPLICATION_IDENTIFIER]["exams"]["questions"]["selected_filters"][$PROCESSED["filter_type"]]);
                                if (empty($_SESSION[APPLICATION_IDENTIFIER]["exams"]["questions"]["selected_filters"])) {
                                    unset($_SESSION[APPLICATION_IDENTIFIER]["exams"]["questions"]["selected_filters"]);
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
                        unset($_SESSION[APPLICATION_IDENTIFIER]["exams"]["questions"]["selected_filters"]);
                        preferences_update("exams", $pref);
                        echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully removed all filters")));
                    break;
                }
            break;
        }
    } else {
        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
    }
    exit;
}