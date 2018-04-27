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
 * The exam that allows users to add and edit exambank exams.
 *
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Daniel Noji <dnoji@mednet.ucla.edu>
 * @copyright Copyright 2015 UC Regents. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("ADD_EXAM") && !defined("EDIT_EXAM"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("exam", "create", false)) {

	add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    load_rte('examadvanced', array('autogrow' => true, 'divarea' => true));

    $MENU_TEXT = $MODULE_TEXT["exams"]["index"]["edit_menu"];
    switch ($STEP) {
        case 2 :

            if (!has_error()) {
                $PROCESSED["organisation_id"] = $ENTRADA_USER->getActiveOrganisation();
                if (!$PROCESSED["exam_id"]) {
                    $PROCESSED["created_date"] = time();
                    $PROCESSED["created_by"] = $ENTRADA_USER->getActiveID();
                }
                $PROCESSED["updated_date"] = time();
                $PROCESSED["updated_by"] = $ENTRADA_USER->getActiveID();
                $PROCESSED["display_questions"] = "all";

                $exam = new Models_Exam_Exam($PROCESSED);
                
                if ($exam->{$METHOD}()) {
                    if ($METHOD == "insert") {
                        if ($exam_in_progress) {
                            $PROCESSED["exam_id"] = $exam->getID();
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
                                            add_error($SECTION_TEXT["failed_to_create_element"] . $element_new['allow_comments']);
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
                        } else {
                            $author = array(
                                "exam_id"       => $exam->getID(),
                                "author_type"   => "proxy_id",
                                "author_id"     => $ENTRADA_USER->getID(),
                                "updated_date"  => time(),
                                "updated_by"    => $ENTRADA_USER->getID(),
                                "created_date"  => time(),
                                "created_by"    => $ENTRADA_USER->getID()
                            );
                            $a = new Models_Exam_Exam_Author($author);
                            $a->insert();

                            $history = new Models_Exam_Creation_History(array(
                                "exam_id" => $exam->getExamID(),
                                "proxy_id" => $ENTRADA_USER->getID(),
                                "action" => "exam_add",
                                "action_resource_id" => NULL,
                                "secondary_action" => NULL,
                                "secondary_action_resource_id" => NULL,
                                "history_message" => "Created exam",
                                "timestamp" => time(),
                            ));

                            if (!$history->insert()) {
                                add_error($translate->_("Failed to insert history log when creating a new Exam."));
                            }
                        }
                    } else {
                        // update exam order
                        $order = $_POST["re_order"];
                        if ($order) {
                            $order = json_decode($order, true);
                            if ($order && is_array($order)) {
                                $new_order_array = Models_Exam_Exam_Element::buildElementOrder($order);
                                if ($new_order_array && is_array($new_order_array)) {
                                    $count = 0;
                                    foreach ($new_order_array as $element_id) {
                                        $element = Models_Exam_Exam_Element::fetchRowByID($element_id);
                                        if ($element && is_object($element)) {
                                            $element->setOrder($count);
                                            if (!$element->update()) {
                                                add_error("Error updating exam element.");
                                            }
                                        }
                                        $count++;
                                    }
                                }
                            }
                        }
                    }

                    Entrada_Utilities_Flashmessenger::addMessage($SUBMODULE_TEXT["exam"]["text_update_success"], "success", $MODULE);

                    $url = ENTRADA_URL."/admin/".$MODULE."/".$SUBMODULE."?section=edit-exam&id=".$exam->getID();
                    header("Location: ".$url);
                } else {
                    add_error($SUBMODULE_TEXT["exam"]["text_error_general"]);
                    $STEP = 1;
                }
                
            } else {
                $STEP = 1;
            }
            
        break;
    }
    
    switch ($STEP) {
        case 2 :
            if (has_success()) {
                echo display_success();
            }
            if (has_error()) {
                echo display_error();
            }
        break;
        case 1 :
            if ($exam) {
                $can_view   = $ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "read");
                $exam_elements = $exam->getExamElements();

                $exam_view = new Views_Exam_Exam($exam);
                $exam_in_progress = $exam_view->createExamCheck();
                $show_details_toggle = 1;
                if (isset($PREFERENCES["exams"]["selected_view"]) && $PREFERENCES["exams"]["selected_view"] == "list") {
                    $show_details_toggle = 0;
                }

                $update_questions_available = array();

                if (!$exam_in_progress) {
                    if ($exam_elements && is_array($exam_elements)) {
                        foreach ($exam_elements as $element) {
                            if ($element && is_object($element)) {
                                if ($element->getElementType() === "question") {
                                    $question_version = $element->getElementID();
                                    $question = Models_Exam_Question_Versions::fetchRowByVersionID($question_version);
                                    if ($question && is_object($question)) {
                                        $question_id = $question->getQuestionID();
                                        $question_current_count = $question->getVersionCount();

                                        $other_versions = $question->fetchAllRelatedVersions();
                                        if ($other_versions && is_array($other_versions) && !empty($other_versions)) {
                                            foreach ($other_versions as $version) {
                                                $version_count = $version->getVersionCount();

                                                if ($question_current_count < $version_count) {
                                                    $temp_array = array(
                                                        "question_id"   => $version->getQuestionID(),
                                                        "version_id"    => $version->getVersionID(),
                                                        "element_id"    => $element->getElementID(),
                                                        "count"         => $version_count
                                                    );

                                                    if ($update_questions_available[$question_id]) {
                                                        if ($update_questions_available[$question_id]["count"] < $version_count ) {
                                                            $update_questions_available[$question_id] = $temp_array;
                                                        }
                                                    } else {
                                                        $update_questions_available[$question_id] = $temp_array;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                if ($exam_in_progress) {
                    add_notice($SUBMODULE_TEXT["exam"]["text_error_exam_in_progress"]);
                }
            }
        default:
            $flash_messages = Entrada_Utilities_Flashmessenger::getMessages($MODULE);
            if ($flash_messages) {
                foreach ($flash_messages as $message_type => $messages) {
                    switch ($message_type) {
                        case "error" :
                            echo display_error($messages);
                        break;
                        case "success" :
                            echo display_success($messages);
                        break;
                        case "notice" :
                        default :
                            echo display_notice($messages);
                        break;
                    }
                }
            }

            if (has_success()) {
                echo display_success();
            }
            if (has_notice()) {
                echo display_notice();
            }
            if (has_error()) {
                echo display_error();
            }

            $HEAD[] = "<script type=\"text/javascript\">var INDEX_TEXT = ". json_encode($SUBMODULE_TEXT["index"]) . "</script>";
            $HEAD[] = "<script type=\"text/javascript\">var EXAM_VIEW_PREFERENCE = \"". (isset($PREFERENCES["exams"]["selected_view"]) ? $PREFERENCES["exams"]["selected_view"] : "detail") ."\";</script>";
            $HEAD[] = "<script type=\"text/javascript\">var VIEW_PREFERENCE = EXAM_VIEW_PREFERENCE;</script>";
            $HEAD[] = "<script type=\"text/javascript\">var API_URL = \"" . ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-exams" ."\";</script>";
            $HEAD[] = "<script type=\"text/javascript\">var submodule_text = JSON.parse('" . json_encode($SUBMODULE_TEXT) . "');</script>";
            $HEAD[] = "<script type=\"text/javascript\">var default_text_labels = JSON.parse('" . json_encode($DEFAULT_TEXT_LABELS) . "');</script>";
            $HEAD[] = "<script type=\"text/javascript\">var exam_in_progress = \"". ($exam_in_progress ? $exam_in_progress : 0 ) ."\";</script>";
            $HEAD[] = "<script type=\"text/javascript\">var exam_id = \"". $PROCESSED["exam_id"] ."\";</script>";
            $HEAD[] = "<script type=\"text/javascript\">var original_exam_id = \"". $PROCESSED["exam_id"] ."\";</script>";
            $HEAD[] = "<script type=\"text/javascript\">var edit_exam = \"edit\";</script>";
            $HEAD[] = "<script type=\"text/javascript\">var update_questions = JSON.parse('" . json_encode($update_questions_available) . "');</script>";
            $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.dataTables.min-1.10.1.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
            $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
            $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.inputselector.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
            $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/" . $MODULE . "/" . $SUBMODULE . "/" . $MODULE . "-" . $MODULE . "-admin.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
            $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/" . $MODULE . "/questions/questions.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
            $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/" . $MODULE . "/questions/linked-questions.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
            $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.inputselector.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
            $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/" . $MODULE . "/" . $MODULE . ".css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
            $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/" . $MODULE . "/groups.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
            $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/" . $MODULE . "/questions.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
            $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.advancedsearch.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
            $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.inputselector.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
            ?>
            <form id="exam-elements" action="<?php echo ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=" . $SECTION . "&exam_id=" . $PROCESSED["exam_id"]; ?>" data-exam-id="<?php echo $PROCESSED["exam_id"]; ?>" class="form-horizontal" method="POST">
                <input type="hidden" name="step" value="2" />
                <input type="hidden" name="re_order" id="re_order" />
                <div id="msgs"></div>
                <?php if (defined("EDIT_EXAM") && EDIT_EXAM === true) { ?>
                    <h2><?php echo $SUBMODULE_TEXT["exam"]["title_exam_questions"]; ?></h2>
                    <div class="row-fluid space-below">
                        <?php
                        $sort_field = "order";
                        $sort_direction = "asc";
                        $exam_elements_html = Views_Exam_Exam::renderExamElements($exam, $sort_field, $sort_direction);
                        if ($exam_elements && !$exam_in_progress) {
                        ?>
<!--                            <a href="#" class="btn btn-danger btn-delete disabled"><i class="icon-trash icon-white"></i> --><?php //echo $DEFAULT_TEXT_LABELS["btn_remove"];?><!--</a>-->
                            <?php /* ?>
                            <a href="#" class="btn btn-default"><i class="icon-download"></i> <?php echo $translate->_("Download"); ?></a>
                            <a href="#preview-modal" data-toggle="modal" class="btn btn-default preview-"><i class="icon-search"></i> <?php echo $translate->_("Preview"); ?></a>
                            <?php */ ?>
                        <?php
                        }
                        ?>
                        <div id="exam-view-controls" class="btn-group">
                            <a href="#" data-view="list" id="list-view" class="btn view-toggle" title="<?php echo $SUBMODULE_TEXT["buttons"]["question_list_view_toggle_title"]; ?>">
                                <i class="fa fa-table"></i>
                            </a>
                            <a href="#" data-view="detail" id="detail-view" class="btn view-toggle" title="<?php echo $SUBMODULE_TEXT["buttons"]["question_detail_view_toggle_title"]; ?>">
                                <i class="fa fa-th-large"></i>
                            </a>
                        </div>
                        <?php
                        if (!$exam_in_progress) {
                            ?>
                            <div class="btn-group pull-right">
                                <a class="btn btn-success"
                                   href="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/questions?element_type=exam&exam_id=" . $PROCESSED["exam_id"]; ?>">
                                    <i class="icon-plus-sign icon-white"></i>
                                    <?php echo $SUBMODULE_TEXT["exam"]["btn_add_single_question"]; ?>
                                </a>
                                <button class="btn btn-success dropdown-toggle" data-toggle="dropdown">
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li<?php echo($show_details_toggle ? "" : " class=\"disabled\""); ?>>
                                        <a href="#" class="add-text" data-exam-id="<?php echo $PROCESSED["exam_id"]; ?>">
                                            <?php echo $SUBMODULE_TEXT["exam"]["btn_add_free_text"]; ?>
                                        </a>
                                    </li>
                                    <li<?php echo ($exam->getDisplayQuestions() !== "page_breaks") ? " class=\"disabled\"" : ""; ?>>
                                        <a href="#" class="add-page-break"
                                           data-exam-id="<?php echo $PROCESSED["exam_id"]; ?>">
                                            <?php echo $SUBMODULE_TEXT["exam"]["btn_add_page_break"]; ?>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div class="btn-group btn-actions-group pull-right">
                                <button class="btn dropdown-toggle btn-actions" disabled="disabled"
                                        data-toggle="dropdown">
                                    <i class="fa fa-wrench"></i> Question Actions
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a href="#delete-exam-question-modal" data-toggle="modal">
                                            <i class="delete-icon fa fa-trash-o fa-fw"></i>
                                            <?php echo $SUBMODULE_TEXT["buttons"]["delete_questions"]; ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#group-question-modal" data-toggle="modal">
                                            <i class="move-icon fa fa-object-group fa-fw"></i>
                                            <?php echo $SUBMODULE_TEXT["buttons"]["group_questions"]; ?>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <?php
                        }

                        if ($exam && $can_view) {
                            ?>
                            <div class="btn-group btn-view-group pull-right">
                                <button class="btn dropdown-toggle" data-toggle="dropdown">
                                    <i class="fa fa-wrench"></i> <?php echo $translate->_("Exam Actions"); ?>
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a href="#copy-exam-modal" data-toggle="modal">
                                            <i class="copy-icon fa fa-files-o fa-fw"></i> <?php echo $SUBMODULE_TEXT["buttons"]["copy_exam"]; ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?php echo ENTRADA_URL . "/admin/exams/exams?section=print-word&id=" . $exam->getID();?>">
                                            <i class="fa fa-file-word-o fa-fw"></i> <?php echo $translate->_("Word Version") ; ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?php echo ENTRADA_URL . "/admin/exams/exams?section=preview&id=" . $exam->getID();?>">
                                            <i class="fa fa-laptop fa-fw"></i> <?php echo $translate->_("Preview Exam"); ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?php echo ENTRADA_URL . "/admin/exams/exams?section=print&id=" . $exam->getID();?>">
                                            <i class="fa fa-print fa-fw"></i> <?php echo $translate->_("Printer Friendly View"); ?>
                                        </a>
                                    </li>
                                    <li id="exam-bank-view-controls">
                                        <a href="#" id="toggle-exam-bank-details" title="<?php echo $SUBMODULE_TEXT["edit-exam"]["labels"]["exam_bank_toggle_title"]; ?>" <?php echo ($show_details_toggle ? "" : "style=\"display:none;\"") ;?>>
                                            <i id="toggle-exam-bank-icon" class="fa fa-eye"></i>
                                            <?php echo $SUBMODULE_TEXT["buttons"]["toggle"]; ?>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                    <?php if ($update_questions_available && is_array($update_questions_available) && !empty($update_questions_available)) {
                    ?>
                    <div id="questions_available" class="alert alert-info">
                        <div class="row-fluid">
                            <span class="span10"><?php echo $SUBMODULE_TEXT["index"]["title_updated_questions_available"]; ?></span>
                            <button id="update_all_questions" class="btn btn-primary span2"><?php echo $SUBMODULE_TEXT["index"]["button_update_all"]; ?></button>
                        </div>
                    </div>
                    <?php
                    }
                    ?>
                    <div id="exam-questions-container" data-exam-id="<?php echo $exam->getID(); ?>">
                        <div id="exam-questions" class="edit-exam<?php echo ($exam->getDisplayQuestions() === "page_breaks") ? " allow-page-breaks" : ""; ?> hide" data-exam-id="<?php echo $exam->getID(); ?>">
                            <?php echo $exam_elements_html["detail_view"]; ?>
                        </div>
                        <div id="linked-question-modal" class="modal hide fade">
                            <form id="linked-question" class="exam-horizontal" action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-questions"; ?>" method="POST" style="margin:0px;">
                                <input type="hidden" name="step" value="2" />
                                <div class="modal-header"><h1><?php echo $SUBMODULE_TEXT["index"]["title_modal_linked_questions"]; ?></h1></div>
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
                        <div id="exam-list-container" class="hide">
                            <table id="exam-list-table" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th class="" id="sort-first-column"></th>
                                        <th class="sort-column" data-type="number" data-field="order" data-direction="asc">
                                            <?php echo $SUBMODULE_TEXT["edit-exam"]["labels"]["number"];?>
                                            <span class="sort-icon pull-right">
                                                <i class="fa fa-sort-amount-asc"></i>
                                            </span>
                                        </th>
                                        <th class="sort-column" data-field="version">
                                            <?php echo $SUBMODULE_TEXT["edit-exam"]["labels"]["id"];?>
                                            <span class="sort-icon pull-right"></span>
                                        </th>
                                        <th class="sort-column" data-field="description">
                                            <?php echo $SUBMODULE_TEXT["edit-exam"]["labels"]["description"];?>
                                            <span class="sort-icon pull-right"></span>
                                        </th>
                                        <th class="sort-column" data-field="update">
                                            <?php echo $SUBMODULE_TEXT["edit-exam"]["labels"]["update"];?>
                                            <span class="sort-icon pull-right"></span>
                                        </th>
                                        <th class="">&nbsp;</th>
                                    </tr>
                                </thead>
                                <tbody id="exam-list-body">
                                <?php echo $exam_elements_html["list_view"]; ?>
                                </tbody>
                            </table>
                        </div>
                        <div id="exam-data-bar" class="exam-data-bar">
                            <h4 id="exam-data-bar-title">Exam Data</h4>
                            <dl class="dl-horizontal">
                                <dt>Questions</dt>
                                <dd id="questions_count">
                                    <?php echo $exam_elements_html["question_count"]; ?>
                                </dd>
                                <dt>Points</dt>
                                <dd id="points_count">
                                    <?php echo $exam_elements_html["point_count"]; ?>
                                </dd>
                            </dl>
                        </div>
                    </div>
                <?php } ?>
                <button id="save_order" class="btn btn-default" disabled="disabled"><?php echo $SUBMODULE_TEXT["buttons"]["btn_reorder"]; ?></button>
            </form>
            <div class="modal large hide fade" id="preview-modal">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h3><?php echo $SUBMODULE_TEXT["edit-exam"]["preview"]; ?></h3>
                </div>
                <div class="modal-body">
                    <div id="exam-preview">

                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn" data-dismiss="modal"><?php echo $SUBMODULE_TEXT["buttons"]["btn_close"]; ?></a>
                </div>
            </div>
            <div class="modal large hide fade" id="add-group-question-modal">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h3><?php echo $SUBMODULE_TEXT["exam"]["label_add_to_group"]; ?></h3>
                </div>
                <div class="modal-body">
                    <div id="form-questions-list">
                        <div id="exam-questions-container">
                            <form id="question-search-container" class="exam-search" action="<?php echo ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?step=2"; ?>" method="POST">
                                <input type="hidden" id="element_type" name="element_type" value="<?php echo (isset($PROCESSED["element_type"]) ? $PROCESSED["element_type"] : ""); ?>" />
                                <input type="hidden" id="id" name="id" value="<?php echo (isset($PROCESSED["id"]) ? $PROCESSED["id"] : ""); ?>" />
                                <input type="hidden" id="exam_id" name="exam_id" value="<?php echo (isset($PROCESSED["exam_id"]) ? $PROCESSED["exam_id"] : ""); ?>" />
                                <div id="search-bar" class="search-bar">
                                    <div class="row-fluid space-below medium">
                                        <div class="input-append space-right">
                                            <input type="text" id="question-search" placeholder="<?php echo $SUBMODULE_TEXT["placeholders"]["question_bank_search"]?>" class="input-large search-icon">
                                            <a href="#" id="advanced-search" class="btn" type="button"><i class="icon-chevron-down"></i></a>
                                        </div>
                                        <div id="question-view-controls" class="input-append btn-group">
                                            <a href="#" data-view="list" id="list-view" class="btn view-toggle" title="<?php echo $SUBMODULE_TEXT["buttons"]["question_list_view_toggle_title"]; ?>"><i class="icon-align-justify"></i></a>
                                            <a href="#" data-view="detail" id="detail-view" class="btn view-toggle" title="<?php echo $SUBMODULE_TEXT["buttons"]["question_detail_view_toggle_title"]; ?>"><i class="icon-th-large"></i></a>
                                        </div>
                                    </div>
                                    <div id="question-summary"></div>
                                </div>
                                <div id="search-container" class="hide space-below medium"></div>
                                <div id="question-summary"></div>
                                <div id="exam-msgs">
                                    <div id="exam-questions-loading" class="hide">
                                        <p><?php echo $SUBMODULE_TEXT["exam"]["text_loading_questions"];?></p>
                                        <img src="<?php echo ENTRADA_URL ."/images/loading.gif" ?>" />
                                    </div>
                                </div>
                                <div id="question-table-container">
                                    <table id="questions-table" class="table table-bordered table-striped hide">
                                        <thead>
                                            <tr>
                                                <th class="q-list-id"><?php echo $SUBMODULE_TEXT["edit-exam"]["labels"]["id"];?></th>
                                                <th class="q-list-code"><?php echo $SUBMODULE_TEXT["edit-exam"]["labels"]["code"];?></th>
                                                <th class="q-list-type"><?php echo $SUBMODULE_TEXT["edit-exam"]["labels"]["type"];?></th>
                                                <th class="q-list-desc"><?php echo $SUBMODULE_TEXT["edit-exam"]["labels"]["description"];?></th>
                                                <th class="q-list-edit"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr id="no-questions">
                                                <td colspan="4"><?php echo $SUBMODULE_TEXT["exam"]["text_no_questions"]; ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div id="question-detail-container" class="hide"></div>
                            </form>
                            <div class="row-fluid">
                                <a id="load-questions" class="btn btn-block"><?php echo $SUBMODULE_TEXT["exam"]["text_load_more_q"];?> <span class="bleh"></span></a>
                            </div>
                            <div id="selected_list_container"></div>
                        </div>
                        <?php
                        if (isset($_SESSION[APPLICATION_IDENTIFIER]["exam"]["questions"]["selected_filters"]) && !empty($_SESSION[APPLICATION_IDENTIFIER]["exam"]["questions"]["selected_filters"])) {
                            echo "<form id=\"search-targets-exam\">";
                            foreach ($_SESSION[APPLICATION_IDENTIFIER]["exam"]["questions"]["selected_filters"] as $key => $filter_type) {
                                foreach ($filter_type as $target_id => $target_label) {
                                    echo "<input id=\"". html_encode($key) ."_". html_encode($target_id) ."\" class=\"search-target-control " . html_encode($key) . "_search_target_control\" type=\"hidden\" name=\"". html_encode($key) ."[]\" value=\"". html_encode($target_id) ."\" data-label=\"". html_encode($target_label) ."\"/>";
                                }
                            }
                            echo "</form>";
                        }
                        ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn" data-dismiss="modal"><?php echo $DEFAULT_TEXT_LABELS["btn_close"]; ?></a>
                </div>
            </div>
            <div id="delete-exam-question-modal" class="modal hide fade">
                <form id="delete-exam-question-modal-question" class="exam-horizontal" action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-questions"; ?>" method="POST" style="margin:0px;">
                    <input type="hidden" name="step" value="2" />
                    <div class="modal-header"><h1><?php echo $SUBMODULE_TEXT["index"]["title_modal_delete_questions"]; ?></h1></div>
                    <div class="modal-body">
                        <?php
                        if ($exam_in_progress) {
                            ?>
                            <p class="alert alert-notice"><?php echo $SUBMODULE_TEXT["exam"]["text_error_exam_in_progress"];?></p>
                        <?php
                        }
                        ?>
                        <h4 id="exam-title-modal"></h4>
                        <div id="no-questions-selected" class="hide">
                            <div class="alert alert-info"><?php echo $SUBMODULE_TEXT["index"]["text_modal_no_questions_selected"] ?></div>
                        </div>
                        <div id="questions-selected" class="hide">
                            <div class="alert alert-warning"><?php echo $SUBMODULE_TEXT["index"]["text_modal_delete_questions"] ?></div>
                            <div id="delete-questions-container"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="row-fluid">
                            <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $DEFAULT_TEXT_LABELS["btn_cancel"]; ?></a>
                            <input id="delete-questions-modal-button" type="button" class="btn btn-primary" value="<?php echo $DEFAULT_TEXT_LABELS["btn_delete"]; ?>" />
                        </div>
                    </div>
                </form>
            </div>
            <div id="group-question-modal" class="modal hide fade">
                <form id="group-question-modal-question" class="exam-horizontal" action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-questions"; ?>" method="POST">
                    <input type="hidden" name="step" value="2" />
                    <div class="modal-header"><h1><?php echo $SUBMODULE_TEXT["index"]["title_modal_group_questions"]; ?></h1></div>
                    <div class="modal-body"></div>
                    <div class="modal-footer"></div>
                </form>
            </div>
            <div id="preview-question-modal" class="modal hide fade">
                <form id="preview-question" class="exam-horizontal" style="margin:0px;">
                    <input type="hidden" name="step" value="2" />
                    <div class="modal-header"><h1><?php echo $SUBMODULE_TEXT["index"]["title_modal_preview_questions"]; ?></h1></div>
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
            <div id="copy-exam-modal" class="modal hide fade" data-href="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=" . $SECTION ;?>">
                <form id="copy-exam-modal-form" class="form-horizontal" action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-exams"; ?>" method="POST" style="margin:0px;">
                    <input type="hidden" name="step" value="2" />
                    <div class="modal-header"><h1><?php echo $SUBMODULE_TEXT["index"]["title_modal_copy_exams"]; ?></h1></div>
                    <div class="modal-body">
                        <div id="no-exams-selected-copy" class="hide">
                            <p><?php echo $SUBMODULE_TEXT["index"]["text_modal_no_exams_selected_c"] ?></p>
                        </div>
                        <div id="exams-selected-copy" class="hide">
                            <p><?php echo $SUBMODULE_TEXT["index"]["text_modal_copy_exams"] ?></p>
                            <div id="copy-exams-container"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="row-fluid">
                            <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $DEFAULT_TEXT_LABELS["btn_cancel"]; ?></a>
                            <input id="copy-exams-modal-button" type="submit" class="btn btn-primary" value="<?php echo $DEFAULT_TEXT_LABELS["btn_copy"]; ?>" />
                        </div>
                    </div>
                </form>
            </div>
            <script type="text/javascript">
                var group_questions = {group_questions : [<?php echo (isset($PROCESSED["group_questions"])) ? implode(",", $PROCESSED["group_questions"]) : ""; ?>]};
                var group_descriptors = {group_descriptors : [<?php echo (isset($PROCESSED["group_descriptors"])) ? implode(",", $PROCESSED["group_descriptors"]): ""; ?>]};

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
                                    data_source : "get-question-permission-types",
                                    secondary_data_source : "get-question-permissions"
                                },
                                course : {
                                    label : "<?php echo $translate->_("Courses"); ?>",
                                    data_source : "get-user-courses"
                                },
                                organisation : {
                                    label : "<?php echo $translate->_("Organisations"); ?>",
                                    data_source : "get-user-organisations"
                                }
                            },
                            load_data_function: "get_questions",
                            no_results_text: "<?php echo $translate->_("No Questions found matching the search criteria"); ?>",
                            reload_page_flag: true,
                            selected_list_container: $("#selected_list_container"),
                            results_parent: $("#exam-questions-container"),
                            width: 400
                        }
                    );
                });
            </script>
            <?php
        break;
    }
}