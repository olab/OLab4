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
} elseif (!$ENTRADA_ACL->amIAllowed("examfolder", "read", true)) {
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
                    case "get-folder-permissions" :
                        if (isset($request["folder_id"]) && $tmp_input = clean_input(strtolower($request["folder_id"]), array("trim", "int"))) {
                            $PROCESSED["folder_id"] = $tmp_input;
                        } else if ($request["folder_id"] == 0) {
                            $PROCESSED["folder_id"] = 0;
                        } else {
                            add_error($translate->_("A problem occurred while attempting to fetch questions for this folder. Please try again later"));
                        }

                        /*
                         * Anyone with update rights can change the order, if you have update rights on a certain folder then you can edit it's subfolders order.
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

                        echo json_encode(array("status" => "success", "edit_folder" => $return["edit_folder"]));

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

                        $results = Models_Exam_Question_Bank_Folder_Authors::fetchAvailableAuthors($PROCESSED["filter_type"], $PROCESSED["question_id"], $PROCESSED["search_value"]);
                        if ($results) {
                            echo json_encode(array("results" => count($results), "data" => $results));
                        } else {
                            echo json_encode(array("results" => "0", "data" => array($translate->_("No results"))));
                        }
                        break;
                    case "get-question-authors" :
                        if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                            $PROCESSED["search_value"] = $tmp_input;
                        } else {
                            $PROCESSED["search_value"] = "";
                        }

                        $authors = Models_Exam_Question_Bank_Folder_Authors::fetchByAuthorTypeProxyID($ENTRADA_USER->getActiveOrganisation(), $PROCESSED["search_value"]);
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
                    case "get-question-bank-folders" :
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

                        $return = array();

                        if (!$ERROR) {
                            /*
                             * Question section
                             */
                            $question_versions = Models_Exam_Question_Versions::fetchAllByFolderID($PROCESSED["folder_id"]);
                            if (isset($question_versions) && is_array($question_versions) && !empty($question_versions)) {
                                $data = array();
                                $data["html_list"] = "";
                                $data["html_details"] = "";
                                $question_count = 0;

                                foreach ($question_versions as $question_version) {
                                    //have to search for other question versions with a higher version in the same folder.
                                    $unset = 0;
                                    $is_highest_version = $question_version->checkHighestVersion($unset);
                                    if (isset($is_highest_version) && $is_highest_version == 1) {
                                        $question_view = new Views_Exam_Question($question_version);
                                        if (isset($question_view) && is_object($question_view)) {
                                            $question_count++;
                                            $question_render_details = $question_view->render($PROCESSED["exam_mode"], NULL, NULL, 'details', false);
                                            $question_render_list = $question_view->render($PROCESSED["exam_mode"], NULL, NULL, 'list',  false);
                                            if ($question_render_list && $question_render_details) {
                                                $data["html_list"] .= $question_render_list;
                                                $data["html_details"] .= $question_render_details;
                                                $return["status_question"] = "success";
                                                $return["question_data"] = $data;
                                                $return[$question_version->getVersionID()]["is_highest_version"] = $is_highest_version;
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
                            } else if ($PROCESSED["folder_id"] == 0 ) {
                                $return["status_question"] = "root_folder";
                            } else {
                                $return["status_question"] = "notice";
                                $return["status_question_notice"] = "<p>No " . $translate->_("questions") . " found in this " . $translate->_("folder") . "</p>";
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
                    case "get-folder-view" :
                        global $translate;
                        if (isset($request["folder_id"]) && $tmp_input = clean_input(strtolower($request["folder_id"]), array("trim", "int"))) {
                            $PROCESSED["folder_id"] = $tmp_input;
                        } else if ($request["folder_id"] == 0) {
                            $PROCESSED["folder_id"] = 0;
                        } else {
                            add_error($translate->_("A problem occurred while attempting to fetch questions for this folder. Please try again later"));
                        }

                        $folder = Models_Exam_Question_Bank_Folders::fetchRowByID($PROCESSED["folder_id"]);
                        if (isset($folder) && is_object($folder)) {
                            $folder_view = new Views_Exam_Question_Bank_Folder($folder);
                            $folder_render = $folder_view->renderSimpleView();
                        } else {
                            $index_folder = new Models_Exam_Question_Bank_Folders(array(
                                "folder_id" => 0,
                                "parent_folder_id" => 0,
                                "folder_title" => "Index",
                                "image_id" => 3
                            ));
                            $index_folder_view = new Views_Exam_Question_Bank_Folder($index_folder);
                            $folder_render = $index_folder_view->renderSimpleView();
                        }

                        $return["status"] = "success";
                        $return["render"] = $folder_render;

                        echo json_encode($return);

                        break;
                    case "get-sub-folder-selector" :
                        global $translate;
                        if (isset($request["folder_id"]) && $tmp_input = clean_input(strtolower($request["folder_id"]), array("trim", "int"))) {
                            $PROCESSED["folder_id"] = $tmp_input;
                        } else if ($request["folder_id"] == 0) {
                            $PROCESSED["folder_id"] = 0;
                        } else {
                            add_error($translate->_("A problem occurred while attempting to fetch questions for this folder. Please try again later"));
                        }

                        if (isset($request["parent_folder_id"]) && $tmp_input = clean_input(strtolower($request["parent_folder_id"]), array("trim", "int"))) {
                            $PROCESSED["parent_folder_id"] = $tmp_input;
                        } else if ($request["parent_folder_id"] == 0) {
                            $PROCESSED["parent_folder_id"] = 0;
                        } else {
                            add_error($translate->_("A problem occurred while attempting to fetch questions for this folder. Please try again later"));
                        }

                        /*
                         * sub folder section
                         */
                        if ($PROCESSED["folder_id"] === 0) {
                            $folder = new Models_Exam_Question_Bank_Folders(
                                array(
                                    "folder_id"     => 0,
                                    "folder_title"  => "Index",
                                    "image_id"      => 3
                                )
                            );
                        } else {
                            $folder = Models_Exam_Question_Bank_Folders::fetchRowByID($PROCESSED["folder_id"]);
                        }
                        if ($folder && is_object($folder)) {
                            $folder_view = new Views_Exam_Question_Bank_Folder($folder);
                            $sub_folder_html = $folder_view->renderFolderSelectorInterface();
                        }

                        $return["parent_folder_id"] = $PROCESSED["parent_folder_id"];
                        $return["status_folder"]    = "success";
                        $return["subfolder_html"]   = $sub_folder_html;
                        $return["folder_count"]     = $folder_count;

                        /*
                         * nav and nav section
                         */
                        $current_folder = Models_Exam_Question_Bank_Folders::fetchRowByID($PROCESSED["folder_id"]);
                        if (isset($current_folder) && is_object($current_folder)) {
                            $current_folder_view = new Views_Exam_Question_Bank_Folder($current_folder);
                            $nav_html = $current_folder_view->renderFolderSelectorBackNavigation();

                            $title = $current_folder_view->renderFolderSelectorTitle();
                        } else {
                            if (isset($root_folder_view)) {
                                $title = $root_folder_view->renderFolderSelectorTitle();
                            }
                        }

                        $return["status_nav"] = "success";
                        $return["nav_html"] = $nav_html;

                        $return["status_title"] = "success";
                        $return["title_html"] = $title;

                        echo json_encode($return);
                        break;
                    case "get-folder-authors" :
                        if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                            $PROCESSED["search_value"] = $tmp_input;
                        } else {
                            $PROCESSED["search_value"] = "";
                        }

                        $authors = Models_Exam_Question_Bank_Folder_Authors::fetchByAuthorTypeProxyID($ENTRADA_USER->getActiveOrganisation(), $PROCESSED["search_value"]);
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
                    case "get-folder-delete-permission" :
                        // function should accommodate 1 folder or multiple
                        if (isset($request["folder_ids"]) && is_array($request["folder_ids"])) {
                            $PROCESSED["folder_ids"] = $request["folder_ids"];
                        } else if (isset($request["folder_ids"]) && is_string($request["folder_ids"])) {
                            $PROCESSED["folder_ids"] = array($request["folder_ids"]);
                            if ($PROCESSED["folder_ids"] && is_array($PROCESSED["folder_ids"]) && !empty($PROCESSED["folder_ids"])) {
                                foreach ($PROCESSED["folder_ids"] as $folder) {
                                    $question_count = 0;
                                    $tmp_input = clean_input(strtolower($folder), array("trim", "int"));
                                    $delete = $ENTRADA_ACL->amIAllowed(new ExamFolderResource($tmp_input, true), "delete");

                                    if ($delete === true) {
                                        // check if this folder has any questions in it or subfolders
                                        $folder_obj = Models_Exam_Question_Bank_Folders::fetchRowByID($folder);
                                        if ($folder_obj && is_object($folder_obj)) {
                                            // getQuestionCount
                                            $question_count = $folder_obj->getQuestionCount();
                                        }

                                        if ($question_count !== 0) {
                                            $delete = false;
                                        }
                                    }

                                    $PROCESSED["folder_ids"][] = array(
                                        "folder_id"   => $tmp_input,
                                        "delete"      => $delete
                                    );
                                }
                            }
                        }

                        if (isset($PROCESSED["folder_ids"]) && is_array($PROCESSED["folder_ids"])) {
                            echo json_encode(array("status" => "success", "folder_ids" => $PROCESSED["folder_ids"]));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No folders were found.")));
                        }
                        break;
                }
                break;
            case "POST" :
                switch ($method) {
                    case "update-exam-folder-order" :
                        if (isset($request["folder"]) && is_array($request["folder"])) {
                            $PROCESSED["folder_ids"] = array();

                            foreach ($request["folder"] as $order => $folder_id) {
                                $tmp_input = clean_input($folder_id, "int");
                                if ($tmp_input) {
                                    $PROCESSED["folder_ids"][$order] = $tmp_input;
                                }
                            }
                        }

                        $element_order = 0;
                        if (isset($PROCESSED["folder_ids"]) && !empty($PROCESSED["folder_ids"])) {
                            foreach ($PROCESSED["folder_ids"] as $key => $folder_id) {
                                $folder = Models_Exam_Question_Bank_Folders::fetchRowByID($folder_id);
                                if (isset($folder) && is_object($folder)) {
                                    $folder->setFolderOrder($key);
                                    if (!$folder->update()) {
                                        $ERROR++;
                                    }
                                }
                            }
                            if ($ERROR) {
                                echo json_encode(array("status" => "error", "message" => $translate->_("Failed to update order")));
                            } else {
                                echo json_encode(array("status" => "success", "message" => $translate->_("Exam folder order successfully updated")));
                            }
                        }

                        break;
                    case "remove-permission" :
                        if (isset($request["author_id"]) && $tmp_input = clean_input($request["author_id"], "int")) {
                            $PROCESSED["author_id"] = $tmp_input;
                        }

                        if (isset($PROCESSED["author_id"])) {

                            $author = Models_Exam_Question_Bank_Folder_Authors::fetchRowByID($PROCESSED["author_id"]);
                            if (($author->getAuthorType() == "proxy_id" && $author->getAuthorID() != $ENTRADA_USER->getID()) || $author->getAuthorType() != "proxy_id") {
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
                            $PROCESSED["folder_id"] = $tmp_input;
                        }

                        if ($PROCESSED["member_id"] && $PROCESSED["member_type"] && $PROCESSED["folder_id"]) {
                            $added = 0;
                            $a = Models_Exam_Question_Bank_Folder_Authors::fetchRowByFolderIDAuthorIDAuthorType($PROCESSED["folder_id"], $PROCESSED["member_id"], $PROCESSED["member_type"]);
                            if ($a) {
                                if ($a->getDeletedDate()) {
                                    if ($a->fromArray(array("deleted_date" => NULL))->update()) {
                                        $added++;
                                    }
                                } else {
                                    application_log("notice", "Folder author [".$a->getID()."] is already an active author. API should not have returned this author as an option.");
                                }
                            } else {

                                $a = new Models_Exam_Question_Bank_Folder_Authors(
                                    array(
                                        "folder_id"     => $PROCESSED["folder_id"],
                                        "author_type"   => $PROCESSED["member_type"],
                                        "author_id"     => $PROCESSED["member_id"],
                                        "created_date"  => time(),
                                        "created_by"    => $ENTRADA_USER->getActiveID()
                                    )
                                );
                                if ($a->insert()) {
                                    $ENTRADA_LOGGER->log($translate->_("Question Bank"), "add-permission", "eqauthor_id", $a->getID(), 4, __FILE__, $ENTRADA_USER->getID());
                                    $added++;
                                }
                            }

                            if ($added >= 1) {
                                $author_view = new Views_Exam_Question_Bank_Folder_Author($a);
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
                                echo json_encode(array("status" => "error", "data" => array("Failed to add author". serialize($a->toArray()))));
                            }
                        }
                        break;
                    case "delete-folders" :
                        $PROCESSED["delete_ids"] = array();
                        if ($request["type"] == "single") {
                            if ($request["delete_ids"] && is_array($request["delete_ids"])) {
                                foreach ($request["delete_ids"] as $key => $folder) {
                                    $PROCESSED["delete_ids"][$key]["folder_id"] = $folder;
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
                            $deleted_folders = array();
                            foreach ($PROCESSED["delete_ids"] as $folder) {
                                $question_obj = Models_Exam_Question_Versions::fetchRowByQuestionID($question["question_id"], $question["version_id"]);
                                $folder_obj = Models_Exam_Question_Bank_Folders::fetchRowByID($folder["folder_id"]);
                                if ($folder_obj) {
                                    $folder_obj->fromArray(array( "deleted_date" => time(),
                                        "updated_date" => time(),
                                        "updated_by" => $ENTRADA_USER->getActiveID()));
                                    if (!$folder_obj->update()) {
                                        add_error($translate->_("Unable to delete a folder"));
                                    } else {
                                        $ENTRADA_LOGGER->log("", "delete", "folder_id", $folder["folder_id"], 4, __FILE__, $ENTRADA_USER->getID());
                                        $deleted_folders[] = $folder["folder_id"];
                                    }
                                }
                            }
                            if (!has_error()) {
                                echo json_encode(array("status" => "success", "msg" => sprintf($translate->_("Successfully deleted %d folders(s)."), count($deleted_folders)), "folder_ids" => $deleted_folders));
                            } else {
                                echo json_encode(array("status" => "error", "msg" => $translate->_("There was an error when attempting to delete a Folder.")));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("Nothing to delete.")));
                        }
                        break;
                }
                break;
        }
    } else {
        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
    }
    exit;
}