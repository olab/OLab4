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
 * The default file that is loaded when /admin/exams/questions is accessed.
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_QUESTIONS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("examquestion", "read", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

    if (isset($_GET["element_type"]) && $tmp_input = clean_input($_GET["element_type"], array("trim", "striptags"))) {
        $PROCESSED["element_type"] = $tmp_input;
    }

    if (isset($_GET["id"]) && $tmp_input = clean_input($_GET["id"], "int")) {
        $PROCESSED["id"] = $tmp_input;
    }
    
    if (isset($_GET["search_questiontype_id"]) && $tmp_input = clean_input($_GET["search_questiontype_id"], "int")) {
        $PROCESSED["search_questiontype_id"] = $tmp_input;
    }

    if (isset($_GET["exam_id"]) && $tmp_input = clean_input($_GET["exam_id"], "int")) {
        $PROCESSED["exam_id"] = $tmp_input;
    }

    if (isset($_GET["group_id"]) && $tmp_input = clean_input($_GET["group_id"], "int")) {
        $PROCESSED["group_id"] = $tmp_input;
    }

    if (isset($_GET["folder_id"])) {
        $tmp_input = clean_input($_GET["folder_id"], "int");
        $PROCESSED["folder_id"] = $tmp_input;
    } else {
        $PROCESSED["folder_id"] = 0;
    }

    $PROCESSED["group_questions"] = array();
    if (isset($_GET["group_questions"])) {
        $group_questions_array = $_GET["group_questions"];
        if ($group_questions_array) {
            $PROCESSED["group_questions"] = array();
            foreach($group_questions_array as $question) {
                $question = clean_input($question, array("int"));
                $PROCESSED["group_questions"][] = $question;
            }
        }
    }
    $PROCESSED["group_descriptors"] = array();
    if (isset($_GET["group_descriptors"])) {
        $group_descriptors_array = $_GET["group_descriptors"];
        if ($group_descriptors_array) {
            $PROCESSED["group_descriptors"] = array();
            foreach($group_descriptors_array as $question) {
                $question = clean_input($question, array("int"));
                $PROCESSED["group_descriptors"][] = $question;
            }
        }
    }

    $SECTION_TEXT = $SUBMODULE_TEXT[$SECTION];

    //updates folder bank based on the passed in folder id
    if (isset($PROCESSED["folder_id"])) {
        $HEAD[] = "<script> var folder_id_get = \"" . $PROCESSED["folder_id"] ."\";</script>";
    }

    if (isset($PROCESSED["element_type"])) {
        $HEAD[] = "<script> var element_type = \"" . $PROCESSED["element_type"] ."\";</script>";
    }

    if (isset($PROCESSED["exam_id"])) {
        $HEAD[] = "<script> var exam_id = \"" . $PROCESSED["exam_id"] ."\";</script>";
    }

    $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = \"". ENTRADA_URL ."\";</script>";
    $HEAD[] = "<script type=\"text/javascript\">var VIEW_PREFERENCE = \"". (isset($PREFERENCES["questions"]["selected_view"]) ? $PREFERENCES["questions"]["selected_view"] : "detail") ."\";</script>";
    $HEAD[] = "<script type=\"text/javascript\">var PREFERENCES = \"". (isset($PREFERENCES) ? $PREFERENCES : "[]") ."\";</script>";
    $HEAD[] = "<script type=\"text/javascript\">var group_questions = \"". (isset($PREFERENCES) ? $PREFERENCES : "[]") ."\";</script>";
    $HEAD[] = "<script type=\"text/javascript\">var API_URL = \"". ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-questions" ."\";</script>";
    $HEAD[] = "<script type=\"text/javascript\">var FOLDER_API_URL = \"". ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-folders" ."\";</script>";
    $HEAD[] = "<script type=\"text/javascript\">var EDIT_URL = \"". ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=edit-exam" ."\";</script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" .  ENTRADA_URL . "/javascript/". $MODULE ."/". $SUBMODULE ."/". $SUBMODULE .".js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" .  ENTRADA_URL . "/javascript/". $MODULE ."/". $SUBMODULE ."/". $MODULE . "-" . $SUBMODULE . "-admin.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" .  ENTRADA_URL . "/javascript/". $MODULE ."/". $SUBMODULE ."/linked-questions.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" .  ENTRADA_URL . "/javascript/jquery/jquery.advancedsearch.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" .  ENTRADA_URL . "/javascript/jquery/jquery.inputselector.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" .  ENTRADA_URL . "/javascript/jquery.growl.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/jquery/jquery.advancedsearch.css?release=". html_encode(APPLICATION_VERSION) ."\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/jquery/jquery.growl.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/jquery/jquery.inputselector.css?release=".html_encode(APPLICATION_VERSION)."\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/" . $MODULE . "/" . $MODULE . ".css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/" . $MODULE . "/questions.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";

    if (isset($_SESSION[APPLICATION_IDENTIFIER]["exams"]["questions"]["selected_filters"]) && !empty($_SESSION[APPLICATION_IDENTIFIER]["exams"]["questions"]["selected_filters"])) {
        $sidebar_html = "";
        foreach ($_SESSION[APPLICATION_IDENTIFIER]["exams"]["questions"]["selected_filters"] as $key => $filter_type) {
            $sidebar_html .= "<span>". ucwords(str_replace("_", " ", $key)) . " Filters</span>";
            $sidebar_html .= "<ul class=\"menu none\">";
            foreach ($filter_type as $target_id => $target_label) {
                $sidebar_html .= "<li>";
                $sidebar_html .= "<a href=\"#\" class=\"remove-target-toggle\" data-id=\"". html_encode($target_id) ."\" data-filter=\"". html_encode($key) ."\">";
                $sidebar_html .= "<img src=\"". ENTRADA_URL ."/images/checkbox-on.gif\" class=\"remove-target-toggle\" data-id=\"". html_encode($target_id) ."\" data_filter=\"". html_encode($key) ."\" />";
                $sidebar_html .= "<span> ". html_encode($target_label) ."</span>";
                $sidebar_html .= "</a>";
                $sidebar_html .= "</li>";
            }
            $sidebar_html .= "</ul>";
        }
        $sidebar_html .= "<a href=\"#\" class=\"clear-filters\">".$translate->_("Clear All Filters")."</a>";

        new_sidebar_item($translate->_("Selected Question Bank Filters"), $sidebar_html, "exam-filters", "open", 2);
    }

    ?>

    <script type="text/javascript">
        var group_questions = {group_questions : [<?php echo implode(",", $PROCESSED["group_questions"]); ?>]};
        var group_descriptors = {group_descriptors : [<?php echo implode(",", $PROCESSED["group_descriptors"]); ?>]};
        current_folder_id = <?php echo (int) $PROCESSED["folder_id"];?>;
        var ajax_in_progress = false;

        /* inactive filters
         course : {
         label : */"<?php //echo $translate->_("Courses"); ?>"/*,
         data_source : "get-user-courses"
         },
         organisation : {
         label : */"<?php //echo $translate->_("Organisations"); ?>"/*,
         data_source : "get-user-organisations"
         }
         */

        jQuery(function($) {
            $("#advanced-search").advancedSearch(
                {
                    api_url : "<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-questions" ; ?>",
                    resource_url: ENTRADA_URL,
                    filters : {
                        curriculum_tag : {
                            label : "<?php echo $translate->_("Curriculum Tag"); ?>",
                            data_source : "get-objectives",
                            secondary_data_source : "get-child-objectives"
                        },
                        author : {
                            label : "<?php echo $translate->_("Question Authors"); ?>",
                            data_source : "get-question-authors"
                        },
                        exam : {
                            label : "<?php echo $translate->_("Exams"); ?>",
                            data_source : "get-user-exams"
                        }
                    },
                    load_data_function: "get_questions",
                    no_results_text: "<?php echo $translate->_("No Questions found matching the search criteria"); ?>",
                    reload_page_flag: true,
                    selected_list_container: $("#search-targets-form"),
                    results_parent: $("#exam-questions-container"),
                    width: 400
                }
            );
        });
    </script>
    <h1><?php echo $SUBMODULE_TEXT["title"]; ?></h1>
    <?php

    switch ($STEP) {
        case 2 :

            if (isset($_POST["element_type"]) && $tmp_input = clean_input($_POST["element_type"], array("trim", "striptags"))) {
                $PROCESSED["element_type"] = $tmp_input;
            }

            if (isset($_POST["id"]) && $tmp_input = clean_input($_POST["id"], "int")) {
                $PROCESSED["id"] = $tmp_input;
            }

            if (isset($_POST["exam_id"]) && $tmp_input = clean_input($_POST["exam_id"], "int")) {
                $PROCESSED["exam_id"] = $tmp_input;
            }

            if (isset($_POST["group_id"]) && $tmp_input = clean_input($_POST["group_id"], "int")) {
                $PROCESSED["group_id"] = $tmp_input;
            }

            if (isset($_POST["questions"]) && is_array($_POST["questions"])) {
                $PROCESSED["questions"] = array_filter($_POST["questions"], function ($id) {
                    return (int) $id;
                });
            }

            if ($PROCESSED["exam_id"] && $PROCESSED["questions"] && $PROCESSED["element_type"] && $PROCESSED["element_type"] == "exam") {
                $SUCCESS = 0;
                $url = ENTRADA_URL . "/admin/" . $MODULE . "/exams?section=edit-exam&exam_id=" . $PROCESSED["exam_id"] . "&element_type=" . $PROCESSED["element_type"];
                $exam = Models_Exam_Exam::fetchRowByID($PROCESSED["exam_id"]);
                if ($exam) {
                    $exam_view = new Views_Exam_Exam($exam);
                    $exam_in_progress = $exam_view->createExamCheck();

                    if ($exam_in_progress) {
                        // first check if there are any valid questions to add, otherwise don't create a new version.
                        $question_already_attached = 0;

                        foreach ($PROCESSED["questions"] as $question_version_id) {
                            $question_version = Models_Exam_Question_Versions::fetchRowByVersionID($question_version_id);
                            if ($question_version) {
                                try {
                                    if (!$exam->hasQuestion($question_version)) {
                                        $question_not_attached++;
                                    }
                                } catch (Exception $e) {
                                    add_error($e->getMessage());
                                }
                            }
                        }

                        if ($question_not_attached > 0) {
                            // Create new version of exam with new questions
                            $exam_elements  = $exam->getExamElements();
                            $exam_authors   = $exam->getExamAuthors();
                            $exam_old       = $exam->toArray();

                            unset($exam_old["exam_id"]);
                            $exam_old["created_date"]   = time();
                            $exam_old["created_by"]     = $ENTRADA_USER->GetID();
                            $exam_old["updated_date"]   = time();
                            $exam_old["updated_by"]     = $ENTRADA_USER->GetID();

                            $exam_new = new Models_Exam_Exam($exam_old);
                            if (!$exam_new->insert()) {
                                add_error($SECTION_TEXT["failed_to_create_exam"]);
                            } else {
                                // Add all the authors and elements from the old exam
                                $PROCESSED["exam_id"] = $exam_new->getID();
                                $url = ENTRADA_URL . "/admin/" . $MODULE . "/exams?section=edit-exam&exam_id=" . $PROCESSED["exam_id"] . "&element_type=" . $PROCESSED["element_type"];
                                if (isset($exam_elements) && is_array($exam_elements)) {
                                    foreach ($exam_elements as $element_old) {
                                        if (isset($element_old) && is_object($element_old)) {
                                            $element_new = $element_old->toArray();
                                            unset($element_new["exam_element_id"]);
                                            $element_new["exam_id"] = $PROCESSED["exam_id"];
                                            $element_new["updated_date"] = time();
                                            $element_new["updated_by"] = $ENTRADA_USER->GetID();
                                            $element_new["allow_comments"] = 0;
                                            $element_new["enable_flagging"] = 0;

                                            $element_new_obj = new Models_Exam_Exam_Element($element_new);
                                            if (!$element_new_obj->insert()) {
                                                add_error($SECTION_TEXT["failed_to_create_element"]);
                                            }
                                        }
                                    }
                                }

                                if (isset($exam_authors) && is_array($exam_authors)) {
                                    foreach ($exam_authors as $author_old) {
                                        if (isset($author_old) && is_object($author_old)) {
                                            $author_new = $author_old->toArray();
                                            unset($author_new["aeauthor_id"]);
                                            $author_new["exam_id"] = $PROCESSED["exam_id"];
                                            $author_new["updated_date"] = time();
                                            $author_new["updated_by"] = $ENTRADA_USER->GetID();

                                            $author_new_obj = new Models_Exam_Exam_Author($author_new);
                                            if (!$author_new_obj->insert()) {
                                                add_error($SECTION_TEXT["failed_to_create_author"]);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    $inserted = 0;
                    foreach ($PROCESSED["questions"] as $question_version_id) {
                        $question_version = Models_Exam_Question_Versions::fetchRowByVersionID($question_version_id);
                        if ($question_version) {
                            try {
                                if (!$exam->hasQuestion($question_version)) {
                                    $exam_element_data = array(
                                        "exam_id" => $PROCESSED["exam_id"],
                                        "element_type" => "question",
                                        "element_id" => $question_version->getID(),
                                        "order" => Models_Exam_Exam_Element::fetchNextOrder($PROCESSED["exam_id"]),
                                        "points" => 1,
                                        "updated_date" => time(),
                                        "updated_by" => $ENTRADA_USER->GetID(),
                                    );
                                    $exam_element = new Models_Exam_Exam_Element($exam_element_data);
                                    if ($exam_element->insert()) {
                                        $inserted++;
                                    } else {
                                        add_error($SECTION_TEXT["failed_to_create"]);
                                    }
                                } else {
                                    add_error($SECTION_TEXT["already_attached"]);
                                }
                            } catch (Exception $e) {
                                add_error($e->getMessage());
                            }
                        }
                    }
                }

                if ($inserted > 0) {
                    if ($exam_in_progress) {
                        Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Successfully created a new exam and added <strong>%d</strong> questions to the exam."), $inserted), "success", $MODULE);
                    } else {
                        Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Successfully added <strong>%d</strong> questions to the exam."), $inserted), "success", $MODULE);
                    }

                    header("Location: ". $url);
                } else {
                    $STEP = 1;
                }
            }

            if ($PROCESSED["group_id"] && $PROCESSED["element_type"] && $PROCESSED["element_type"] == "group" ) {
                $url = ENTRADA_URL . "/admin/" . $MODULE . "/groups?section=edit-group&group_id=" . $PROCESSED["group_id"] . "&exam_id=" . $PROCESSED["exam_id"];
                if (isset($PROCESSED["questions"]) && is_array($PROCESSED["questions"])) {
                    foreach ($PROCESSED["questions"] as $question_version) {
                        $exam_question  = Models_Exam_Question_Versions::fetchRowByVersionID($question_version);
                        $posted         = Models_Exam_Exam_Element::isGroupIdPosted($PROCESSED["group_id"]);

                        if ($posted === false) {
                            //Verify that the question (or any version of it) has not already been attached to the group
                            if ($exam_question && !Models_Exam_Group_Question::fetchRowByQuestionIDGroupID($exam_question->getQuestionID(), $PROCESSED["group_id"])) {
                                $group_question_data = array(
                                    "group_id"          => $PROCESSED["group_id"],
                                    "question_id"       => $exam_question->getQuestionID(),
                                    "version_id"        => $exam_question->getVersionID(),
                                    "order"             => Models_Exam_Group_Question::fetchNextOrder($PROCESSED["group_id"]),
                                );
                                $group_question = new Models_Exam_Group_Question($group_question_data);

                                if ($group_question->insert()) {
                                    // add to exam element also

                                    $exam_elements = Models_Exam_Exam_Element::fetchAllByGroupID($PROCESSED["group_id"]);
                                    if ($exam_elements && is_array($exam_elements) && !empty($exam_elements)) {
                                        $last   = end($exam_elements);
                                        $order  = $last->getOrder();

                                        $new_exam_element = new Models_Exam_Exam_Element(array(
                                            "exam_id"       => $last->getExamID(),
                                            "element_type"  => "question",
                                            "element_id"    => $exam_question->getVersionID(),
                                            "group_id"      => $group_question->getGroupID(),
                                            "order"         => $order + 1,
                                            "points"        => 1,
                                            "updated_date"  => time(),
                                            "updated_by"    => $ENTRADA_USER->getID()
                                        ));

                                        $elements_to_update = Models_Exam_Exam_Element::fetchAllByExamIdOrderGreater($last->getExamID(), $order);

                                        if (!$new_exam_element->insert()) {
                                            $ERROR++;
                                        } else {
                                            if ($elements_to_update && is_array($elements_to_update) && !empty($elements_to_update)) {
                                                foreach ($elements_to_update as $element) {
                                                    $new_order = $element->getOrder();
                                                    $element->setOrder($new_order + 1);
                                                    if (!$element->update()) {
                                                        $ERROR++;
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    $SUCCESS++;
                                } else {
                                    $ERROR++;
                                    add_error($SECTION_TEXT["failed_to_create"]);
                                }
                            }  else {
                                $ERROR++;
                                add_error($SECTION_TEXT["already_attached"]);
                            }
                        } else {
                            $ERROR++;
                            add_error($SECTION_TEXT["group_already_posted"]);
                        }
                    }

                    if (has_success() && !has_error()) {
                        Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Successfully added <strong>%d</strong> questions to the %s."), $SUCCESS, $PROCESSED["element_type"]), "success", $MODULE);
                        header("Location: ". $url);
                    } else {
                        $STEP = 1;
                    }
                }
            }
    }

    switch ($STEP) {
        case 2 :
            if (has_success()) {
                echo display_success();
            }
            if (has_notice()) {
                echo display_notice();
            }
            if (has_error()) {
                echo display_error();
            }
            $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."/".$SUBMODULE."\\'', 5000)";
            break;
        case 1 :

            if ($PROCESSED["element_type"] && $PROCESSED["element_type"] == "exam" && $PROCESSED["exam_id"]) {
                $exam = Models_Exam_Exam::fetchRowByID($PROCESSED["exam_id"]);
                $exam_view = new Views_Exam_Exam($exam);
                $exam_in_progress = $exam_view->createExamCheck();
            }

            $show_details_toggle = 1;
            if (isset($PREFERENCES["questions"]["selected_view"]) && $PREFERENCES["questions"]["selected_view"] == "list") {
                $show_details_toggle = 0;
            }

            if ($exam_in_progress) {
                add_notice($SUBMODULE_TEXT["exam"]["text_error_exam_in_progress"]);
            }


        default :
            if (has_success()) {
                echo display_success();
            }
            if (has_notice()) {
                echo display_notice();
            }
            if (has_error()) {
                echo display_error();
            } ?>
            <script>
                jQuery(document).ready(function($) {
                    $("#add-folder").click(function () {
                        var current_folder = $(".active-folder").data('id');
                        var url = "<?php echo ENTRADA_URL. "/admin/" . $MODULE. "/" . $SUBMODULE . "?section=add-folder&parent_folder_id="?>" + current_folder;
                        window.location = url;
                    });

                    $("#add-question").click(function () {
                        var current_folder = $(".active-folder").data('id');
                        if (!current_folder) {
                            current_folder = 0;
                        }
                        var url = "<?php echo ENTRADA_URL. "/admin/" . $MODULE. "/" . $SUBMODULE . "?section=add-question&folder_id="?>" + current_folder;
                        window.location = url;
                    });

                    $("#folders").on("click", ".folder-edit-btn a", function () {
                        var type = $(this).data('type');
                        folder_id_selected = $(this).data('id');
                        var href = $(this).data('href');
                        if (type === "Delete") {
                            $("#delete-folder-modal").modal("show");
                        } else if (type === "Edit") {
                            var url = "<?php echo ENTRADA_URL. "/admin/" . $MODULE. "/" . $SUBMODULE?>" + href;
                            window.location = url;
                        }
                    });

                    delete_url = "<?php echo ENTRADA_URL. "/admin/" . $MODULE. "/" . $SUBMODULE;?>" + "?section=api-questions";
                    edit_exam = <?php echo (isset($PROCESSED["exam_id"]) && isset($PROCESSED["element_type"])) ? '"add"' : 'false'; ?>
                });

            </script>
            <div id="msgs"></div>
            <div id="exam-question-bank-breadcrumbs" class="bread-crumb-trail">
                <ul class="question-bank-breadcrumbs"><li><span class="bread-separator"><i class="fa fa-angle-right"></i></span><strong><?php echo $translate->_("Index"); ?></strong></li></ul>
            </div>
            <div id="exam-question-bank-container" class="row-fluid">
                <div id="exam-question-bank-tree" class="span12">
                    <div class="row-fluid">
                        <div class="pull-left">
                            <h3 id="exam-question-bank-tree-title">
                                <?php echo $translate->_("Index"); ?>
                            </h3>
                        </div>
                        <div class="pull-right">
                            <div id="question-bank-folder-view-controls" class="btn-group">
                                <a href="#" id="toggle-question-bank" class="btn" title="<?php echo $SUBMODULE_TEXT["folder"]["buttons"]["question_bank_toggle_title"]; ?>"><i id="toggle-question-bank-icon" class="fa fa-2x fa-eye"></i></a>
                            </div>
                            <?php
                            if ($ENTRADA_ACL->amIAllowed("examfolder", "create", false)) {
                                ?>
                                <a id="add-folder" class="btn btn-success pull-right"><i class="add-icon fa fa-plus-circle"></i> <?php echo $SUBMODULE_TEXT["folder"]["buttons"]["add_folder"]; ?></a>
                            <?php
                            }
                            ?>
                        </div>
                    </div>
                    <div id="folders">
                    <?php
                    $folders = Models_Exam_Question_Bank_Folders::fetchAllByParentID(0);
                    if (isset($folders) && is_array($folders)) {
                        ?>
                        <ul id="folder_ul">
                            <?php
                            foreach ($folders as $folder) {
                                if (isset($folder) && is_object($folder)) {
                                    $folder_view = new Views_Exam_Question_Bank_Folder($folder);
                                    echo $folder_view->render();
                                }
                            }
                            ?>
                        </ul>
                    <?php
                    }
                    ?>
                    </div>
                </div>
            </div>

            <div id="exam-questions-container">
                <form id="search-targets-form"></form>
                <form id="exam-search" class="exam-search form-search" action="<?php echo ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?step=2"; ?>" method="POST">
                    <input type="hidden" id="element_type" name="element_type" value="<?php echo (isset($PROCESSED["element_type"]) ? $PROCESSED["element_type"] : ""); ?>" />
                    <input type="hidden" id="id" name="id" value="<?php echo (isset($PROCESSED["id"]) ? $PROCESSED["id"] : ""); ?>" />
                    <input type="hidden" id="exam_id" name="exam_id" value="<?php echo (isset($PROCESSED["exam_id"]) ? $PROCESSED["exam_id"] : ""); ?>" />
                    <input type="hidden" id="group_id" name="group_id" value="<?php echo (isset($PROCESSED["group_id"]) ? $PROCESSED["group_id"] : ""); ?>" />
                    <div id="search-bar" class="search-bar">
                        <div class="row-fluid space-below">
                            <input type="text" id="question-search" placeholder="<?php echo $SUBMODULE_TEXT["placeholders"]["question_bank_search"]?>" class="input-block-level search-icon">
                        </div>
                        <div class="row-fluid">
                            <button id="advanced-search" class="btn" type="button">Advanced Search <i class="fa fa-chevron-down"></i></button>
                        </div>
                        <div class="row-fluid space-below space-above">
                            <div class="pull-left">
                                <div id="question-view-controls" class="btn-group text-right">
                                    <a href="#" data-view="list" id="list-view" class="btn view-toggle" title="<?php echo $SUBMODULE_TEXT["buttons"]["question_list_view_toggle_title"]; ?>">
                                        <i class="fa fa-table"></i>
                                    </a>
                                    <a href="#" data-view="detail" id="detail-view" class="btn view-toggle" title="<?php echo $SUBMODULE_TEXT["buttons"]["question_detail_view_toggle_title"]; ?>">
                                        <i class="fa fa-th-large"></i>
                                    </a>
                                </div>
                                <div class="pull-right text-right" id="sub-folder-search">
                                    <label><?php echo $translate->_("Sub-folders"); ?></label>
                                    <div class="btn-group">
                                        <?php
                                        $subfolder_search = $_SESSION[APPLICATION_IDENTIFIER]["exams"]["questions"]["sub_folder_search"];

                                        if (!isset($subfolder_search)) {
                                            $subfolder_search = "off";
                                            $_SESSION[APPLICATION_IDENTIFIER]["exams"]["questions"]["sub_folder_search"] = $subfolder_search;
                                            preferences_update("exams", $PREFERENCES);
                                        }

                                        ?>
                                        <button class="btn<?php echo ($subfolder_search === "off" ? " btn-success" : ""); ?>" data-value="off">Off</button>
                                        <button class="btn<?php echo ($subfolder_search === "on" ? " btn-success" : ""); ?>" data-value="on">On</button>
                                    </div>
                                </div>
                            </div>
                            <div class="pull-right">
                                <div id="question-bank-view-controls" class="btn-group padding-right">
                                    <a href="#" id="toggle-all-question-bank" class="btn" title="<?php echo $SUBMODULE_TEXT["index"]["text_question_bank_toggle_title"]; ?>">
                                        <i id="toggle-question-bank-icon" class="fa fa-2x fa-eye"></i>
                                    </a>
                                    <a href="#" id="select-all-question-bank" class="btn" title="<?php echo $SUBMODULE_TEXT["index"]["text_question_bank_select_title"]; ?>" >
                                        <i id="select-question-bank-icon" class="fa fa-2x fa-square-o"></i>
                                    </a>
                                </div>
                                <div class="btn-group btn-actions-group">
                                    <button class="btn dropdown-toggle btn-actions" disabled="disabled" data-toggle="dropdown">
                                        <i class="fa fa-wrench"></i> Actions
                                        <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a href="#delete-question-modal" data-toggle="modal"><i class="delete-icon fa fa-trash-o fa-fw"></i> <?php echo $SUBMODULE_TEXT["buttons"]["delete_questions"]; ?></a></li>
                                        <!--                            <li><a href="#move-exam-modal" data-toggle="modal"><i class="move-icon fa fa-arrows-v fa-fw"></i> --><?php //echo $SUBMODULE_TEXT["buttons"]["move_exam"]; ?><!--</a></li>-->
                                        <li><a href="#move-question-modal" data-toggle="modal"><i class="move-icon fa fa-arrows-v fa-fw"></i> <?php echo $SUBMODULE_TEXT["buttons"]["move_questions"]; ?></a></li>
                                        <li class="disabled"><a href="#group-question-modal" data-toggle="modal"><i class="move-icon fa fa-tag fa-fw"></i> <?php echo $SUBMODULE_TEXT["buttons"]["tag_questions"]; ?></a></li>
                                    </ul>
                                </div>
                                <?php
                                if (isset($PROCESSED["element_type"]) && (isset($PROCESSED["exam_id"]) || isset($PROCESSED["group_id"]))) { ?>
                                    <div class="btn-group">
                                        <input type="submit" class="btn btn-success" value="<?php echo $SUBMODULE_TEXT["exam"]["btn_attach_question"]; ?>" />
                                        <button class="btn btn-success dropdown-toggle" data-toggle="dropdown">
                                            <span class="caret"></span>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a href="<?php echo ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=add-question&element_type=" . $PROCESSED["element_type"] . "&exam_id=" . $PROCESSED["exam_id"]; ?>"><?php echo $SUBMODULE_TEXT["exam"]["btn_add_attach_question"]; ?></a></li>
                                        </ul>
                                    </div>
                                <?php
                                } else if ($ENTRADA_ACL->amIAllowed("examquestion", "create", false)) { ?>
                                    <a id="add-question" class="btn btn-success pull-right"><i class="add-icon fa fa-plus-circle"></i> <?php echo $SUBMODULE_TEXT["buttons"]["add_question"]; ?></a>
                                <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <div id="search-container" class="hide space-below medium"></div>
                    <div id="question-summary"></div>
                    <div id="exam-msgs">
                        <div id="exam-questions-loading" class="hide">
                            <p><?php echo $translate->_("Loading Exam Questions..."); ?></p>
                            <img src="<?php echo ENTRADA_URL ."/images/loading.gif" ?>" />
                        </div>
                    </div>
                    <div id="question-table-container" class="hide">
                        <table id="questions-table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th width="5%"></th>
                                    <th width="15%"><?php echo $SUBMODULE_TEXT["index"]["headers"]["question_id"]; ?></th>
                                    <th width="50%"><?php echo $SUBMODULE_TEXT["index"]["headers"]["description"]; ?></th>
                                    <th width="20%"><?php echo $SUBMODULE_TEXT["index"]["headers"]["updated"]; ?></th>
                                    <th width="10%">&nbsp;</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr id="no-questions">
                                    <td colspan="5"><?php echo $translate->_("No Questions to display"); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="question-detail-container" class="hide"></div>
                </form>
                <div id="delete-question-modal" class="modal hide fade">
                    <form id="delete-question-modal-question" class="exam-horizontal" action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-questions"; ?>" method="POST" style="margin:0px;">
                        <input type="hidden" name="step" value="2" />
                        <div class="modal-header"><h3><?php echo $SUBMODULE_TEXT["index"]["title_modal_delete_questions"]; ?></h3></div>
                        <div class="modal-body">
                            <div id="no-questions-selected" class="hide">
                                <p><?php echo $SUBMODULE_TEXT["index"]["text_modal_no_questions_selected"] ?></p>
                            </div>
                            <div id="questions-selected" class="hide">
                                <p><?php echo $SUBMODULE_TEXT["index"]["text_modal_delete_questions"] ?></p>
                                <div id="delete-questions-container"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="row-fluid">
                                <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $DEFAULT_TEXT_LABELS["btn_cancel"]; ?></a>
                                <input id="delete-questions-modal-delete" type="submit" class="btn btn-primary" value="<?php echo $DEFAULT_TEXT_LABELS["btn_delete"]; ?>" />
                            </div>
                        </div>
                    </form>
                </div>
                <?php
                if ($PROCESSED["folder_id"] === 0) {
                    $root_folder = new Models_Exam_Question_Bank_Folders(
                        array(
                            "folder_id" => 0,
                            "folder_title" => "Index",
                            "image_id" => 3
                        )
                    );

                    if ($root_folder && is_object($root_folder)) {
                        $initial_folder_view = new Views_Exam_Question_Bank_Folder($root_folder);
                        if (isset($initial_folder_view) && is_object($initial_folder_view)) {
                            $title              = $initial_folder_view->renderFolderSelectorTitle();
                            $folder_view        = $initial_folder_view->renderSimpleView();
                            $sub_folder_html    = $initial_folder_view->renderFolderSelectorInterface();
                        }
                    }
                } else {
                    $parent_folder = Models_Exam_Question_Bank_Folders::fetchRowByID($PROCESSED["folder_id"]);
                    if (isset($parent_folder) && is_object($parent_folder)) {
                        $parent_folder_view = new Views_Exam_Question_Bank_Folder($parent_folder);
                        if ($parent_folder_view && is_object($parent_folder_view)) {
                            $title              = $parent_folder_view->renderFolderSelectorTitle();
                            $folder_view        = $parent_folder_view->renderSimpleView();
                            $nav                = $parent_folder_view->renderFolderSelectorBackNavigation();
                            $sub_folder_html    = $parent_folder_view->renderFolderSelectorInterface();
                        }
                    }
                }

                ?>
                <div id="move-question-modal" class="modal hide fade">
                    <form id="move-question-modal-question" class="exam-horizontal" action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-questions"; ?>" method="POST" style="margin:0px;">
                        <input type="hidden" name="step" value="2" />
                        <div class="modal-header"><h3><?php echo $SUBMODULE_TEXT["index"]["title_modal_move_questions"]; ?></h3></div>
                        <div class="modal-body">
                            <div id="move-question-msg"></div>
                            <div id="no-questions-selected-move" class="hide">
                                <h3><?php echo $SUBMODULE_TEXT["index"]["text_modal_no_questions_move"] ?></h3>
                            </div>
                            <div id="questions-selected-move" class="hide">
                                <h3><?php echo $SUBMODULE_TEXT["index"]["text_modal_move_questions"] ?></h3>
                                <div id="move-questions-container"></div>
                                <h3><?php echo $SUBMODULE_TEXT["index"]["text_modal_move_destination"] ?></h3>
                                <div id="qbf-selector" class="well">
                                    <div id="qbf-title">
                                        <span class="qbf-title"><?php echo $title;?></span>
                                    </div>
                                    <div id="qbf-nav">
                                        <?php echo $nav;?>
                                    </div>
                                    <span id="qbf-folder-<?php echo $PROCESSED["folder_id"];?>" class="qbf-folder active">
                                        <table>
                                            <?php
                                            echo $sub_folder_html;
                                            ?>
                                        </table>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="row-fluid">
                                <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $DEFAULT_TEXT_LABELS["btn_cancel"]; ?></a>
                                <input id="move-questions-modal-move" type="submit" class="btn btn-primary" value="<?php echo $DEFAULT_TEXT_LABELS["btn_submit"]; ?>" />
                            </div>
                        </div>
                    </form>
                </div>
                <div id="delete-folder-modal" class="modal hide fade">
                    <form id="delete-question-modal-folder" class="exam-horizontal" action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-questions"; ?>" method="POST" style="margin:0px;">
                        <input type="hidden" name="step" value="2" />
                        <div class="modal-header"><h3><?php echo $SUBMODULE_TEXT["index"]["title_modal_delete_folders"]; ?></h3></div>
                        <div class="modal-body">
                            <div id="no-folders-selected" class="hide">
                                <p><?php echo $SUBMODULE_TEXT["index"]["text_modal_no_folder_selected"] ?></p>
                            </div>
                            <div id="folders-selected" class="hide">
                                <p><?php echo $SUBMODULE_TEXT["index"]["text_modal_delete_folders"] ?></p>
                                <div id="delete-folders-container"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="row-fluid">
                                <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $DEFAULT_TEXT_LABELS["btn_cancel"]; ?></a>
                                <input id="delete-folders-modal-delete" type="submit" class="btn btn-primary" value="<?php echo $DEFAULT_TEXT_LABELS["btn_delete"]; ?>" />
                            </div>
                        </div>
                    </form>
                </div>
                <div id="linked-question-modal" class="modal hide fade">
                    <form id="linked-question" class="exam-horizontal" action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-questions"; ?>" method="POST" style="margin:0px;">
                        <input type="hidden" name="step" value="2" />
                        <div class="modal-header"><h3><?php echo $SUBMODULE_TEXT["index"]["title_modal_linked_questions"]; ?></h3></div>
                        <div class="modal-body">
                            This question belongs to the following question groups.
                            <div id="table-question-groups-container"></div>
                        </div>
                        <div class="modal-footer">
                            <div class="row-fluid">
                                <a href="#" class="btn btn-default btn-primary" data-dismiss="modal"><?php echo $DEFAULT_TEXT_LABELS["btn_close"]; ?></a>
                            </div>
                        </div>
                    </form>
                </div>
                <div id="preview-question-modal" class="modal hide fade">
                    <form id="preview-question" class="exam-horizontal" style="margin:0px;">
                        <input type="hidden" name="step" value="2" />
                        <div class="modal-header"><h3><?php echo $SUBMODULE_TEXT["index"]["title_modal_preview_questions"]; ?></h3></div>
                        <div class="modal-body">
                            <h3></h3>
                            <div class="modal-sub-body"></div>
                        </div>
                        <div class="modal-footer">
                            <div class="row-fluid">
                                <a href="#" class="btn btn-default btn-primary" data-dismiss="modal"><?php echo $DEFAULT_TEXT_LABELS["btn_close"]; ?></a>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="row-fluid">
                    <div class="span12">
                        <div id="questions-loaded-display"></div>
                    </div>
                </div>
                <div id="per_page_nav" class="row-fluid">
                    <div class="span5">
                        <button class="btn btn-default" id="load-previous-questions" disabled="disabled">
                            <i class="fa fa-chevron-left"></i>
                            <?php echo $DEFAULT_TEXT_LABELS["btn_previous_page"]; ?>
                        </button>
                    </div>
                    <div class="span2">
                        <button class="btn btn-default" id="number_questions_pp" type="text" class="input-mini input-selector" name="numberquestions_pp" value="50">
                            50 - Per Page
                        </button>
                    </div>
                    <div class="span5">
                        <button class="btn btn-default" id="load-questions">
                            <?php echo $DEFAULT_TEXT_LABELS["btn_next_page"]; ?>
                            <i class="fa fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php
            /*if (isset($_SESSION[APPLICATION_IDENTIFIER]["exams"]["questions"]["selected_filters"]) && !empty($_SESSION[APPLICATION_IDENTIFIER]["exams"]["questions"]["selected_filters"])) {
                echo "<form id=\"search-targets-exam\">";
                foreach ($_SESSION[APPLICATION_IDENTIFIER]["exams"]["questions"]["selected_filters"] as $key => $filter_type) {
                    foreach ($filter_type as $target_id => $target_label) {
                        echo "<input id=\"". html_encode($key) ."_". html_encode($target_id) ."\" class=\"search-target-control " . html_encode($key) . "_search_target_control\" type=\"hidden\" name=\"". html_encode($key) ."[]\" value=\"". html_encode($target_id) ."\" data-label=\"". html_encode($target_label) ."\"/>";
                    }
                }
                echo "</form>";
            }*/
            break;
    }
}