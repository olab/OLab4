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
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Robert Fotino <robert.fotino@gmail.com>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 */

if (!defined("PARENT_INCLUDED")) {
    exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("examquestion", "update", false)) {
    add_error(sprintf($translate->_("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));
    echo display_error();
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/questions?section=import", "title" => $translate->_(""));
    $HEAD[] = "<script type='text/javascript' src='" . ENTRADA_URL . "/javascript/bootstrap-filestyle.min.js?release=".html_encode(APPLICATION_VERSION)."'></script>";
    
    $sub_navigation = Views_Exam_Exam::GetQuestionsSubnavigation("migrate");
    echo $sub_navigation;
    ?>
    <h1><?php echo $translate->_("Migrate ExamSoft Questions"); ?></h1>
    <?php
    // Error checking
    switch($STEP) {
        case 3:
            $parsed = json_decode($_POST["questions"], true);
            $db->StartTrans();
            // Import questions
            $num_questions_imported = 0;
            foreach ($parsed["questions"] as &$question) {
                if (isset($question["question_id"]) || Models_Exam_Question_Versions::fetchRowByExamsoftID($question["examsoft_id"])) {
                    $question["new_question"] = false;
                } else {
                    $question["new_question"] = true;
                    // Check whether the question should be flagged for further
                    // review. This occurs if the type is essay, or if the question
                    // stem contains the phrase "Attachment:".
                    if ("essay" === $question["attributes"]["type"] ||
                        false !== strpos($question["stem"], "Attachment:")) {
                        $question["examsoft_flagged"] = 1;
                    }
                    $question_id = Models_Exam_Question_Parser::import($question, $question["num"]);
                    if (!$question_id) {
                        $db->FailTrans();
                        break;
                    } else {
                        $question["question_id"] = $question_id;
                        $num_questions_imported++;
                    }
                }
            }
            unset($question);
            // Create question groups
            foreach ($parsed["questions"] as $question) {
                $group_title = isset($question["group"]) ? $question["group"] : false;
                if ($group_title) {
                    $group_author_id = isset($question["author_id"]) ? $question["author_id"] : $ENTRADA_USER->getProxyID();
                    if ($last_title != $group_title) {
                        $group_record = new Models_Exam_Group(array(
                            "organisation_id"   => $ENTRADA_USER->getOrganisationID(),
                            "group_title"       => $group_title,
                            "created_date"      => time(),
                            "created_by"        => $group_author_id
                        ));
                        if (!$group_record->insert()) {
                            add_error($translate->_("Error creating a group question."));
                            $db->FailTrans();
                            break;
                        }

                        $group_author_record = new Models_Exam_Group_Author(array(
                            "group_id"      => $group_record->getID(),
                            "author_type"   => "proxy_id",
                            "author_id"     => $group_author_id,
                            "created_date"  => time(),
                            "created_by"    => $group_author_id
                        ));
                        if (!$group_author_record->insert()) {
                            add_error($translate->_("Error adding author to a group question."));
                            $db->FailTrans();
                            break;
                        }
                        $order = 1;
                        foreach ($parsed["questions"] as $group_q) {
                            if (isset($group_q["group"]) && $group_q["group"] === $group_title) {
                                $question_version = Models_Exam_Question_Versions::fetchRowByVersionID($group_q["question_id"]);
                                $group_question_record = new Models_Exam_Group_Question(array(
                                    "group_id"      => $group_record->getID(),
                                    "question_id"   => $question_version->getQuestionID(),
                                    "version_id"    => $group_q["question_id"],
                                    "order"         => $order,
                                    "updated_by"    => $group_author_id,
                                    "updated_date"  => time()
                                ));
                                if (!$group_question_record->insert()) {
                                    add_error($translate->_("Error adding question to group."));
                                    $db->FailTrans();
                                    break 2;
                                }
                                $order++;
                            }
                        }
                    }
                    $last_title = $group_title;
                }
            }
            if (!has_error()) {
                // Import exam
                $exam = new Models_Exam_Exam(array(
                    "organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                    "title" => $parsed["exam"]["name"],
                    "display_questions" => "all",
                    "random" => 0,
                    "examsoft_exam_id" => $parsed["exam"]["examsoft_exam_id"],
                    "created_date" => time(),
                    "created_by" => $ENTRADA_USER->getProxyID(),
                    "updated_date" => time(),
                    "updated_by" => $ENTRADA_USER->getProxyID()
                ));
                if (!$exam->insert()) {
                    add_error($translate->_("Error creating new exam."));
                    $db->FailTrans();
                }
                // Extract exam authors from question authors
                $exam_author_ids = array();
                foreach ($parsed["questions"] as $question) {
                    if (isset($question["author_id"])) {
                        if (!in_array($question["author_id"], $exam_author_ids)) {
                            $exam_author_ids[] = $question["author_id"];
                        }
                    } else if (!in_array($ENTRADA_USER->getProxyID(), $exam_author_ids)) {
                        $exam_author_ids[] = $ENTRADA_USER->getProxyID();
                    }
                }
                foreach ($exam_author_ids as $exam_author_id) {
                    $exam_author = new Models_Exam_Exam_Author(array(
                        "exam_id" => $exam->getID(),
                        "author_type" => "proxy_id",
                        "author_id" => $exam_author_id,
                        "created_date" => time(),
                        "created_by" => $exam_author_id,
                        "updated_date" => time(),
                        "updated_by" => $exam_author_id
                    ));
                    if (!$exam_author->insert()) {
                        add_error($translate->_("Error adding an exam author."));
                        $db->FailTrans();
                        break;
                    }
                }
                // Add exam elements
                foreach ($parsed["questions"] as $i => $question) {
                    // Find group_id for this question in this exam, if applicable
                    $group_id = null;
                    if (isset($question["group"])) {
                        $group_questions = Models_Exam_Group_Question::fetchAllByVersionID($question["question_id"]);
                        if ($group_questions && is_array($group_questions)) {
                            krsort($group_questions);
                            foreach ($group_questions as $group_question) {
                                $group = Models_Exam_Group::fetchRowByID($group_question->getGroupID());
                                if ($group && $question["group"] === $group->getGroupTitle()) {
                                    $group_id = $group->getID();
                                    break;
                                }
                            }
                        }
                    }
                    $exam_element = new Models_Exam_Exam_Element(array(
                        "exam_id" => $exam->getID(),
                        "element_type" => "question",
                        "element_id" => $question["question_id"],
                        "group_id" => $group_id,
                        "order" => $i,
                        "points" => $question["weight"],
                        "updated_date" => time(),
                        "updated_by" => $ENTRADA_USER->getProxyID()
                    ));
                    if (!$exam_element->insert()) {
                        add_error($translate->_("Error adding an exam element."));
                        $db->FailTrans();
                        break;
                    }
                }
            }
            if (!has_error()) {
                // Add successes
                add_success(sprintf($translate->_("Imported %d new questions."), $num_questions_imported));
                add_success(sprintf($translate->_("Created exam '%s' with %d questions."), html_encode($parsed["exam"]["name"]), count($parsed["questions"])));
            }
            $db->CompleteTrans();
            break;
        case 2:
            if (!isset($_POST["folder_id"]) || !$_POST["folder_id"]) {
                add_error($translate->_("You must choose a folder to import the questions into."));
            } else if (!Models_Exam_Question_Bank_Folders::fetchRowByID($_POST["folder_id"])) {
                add_error($translate->_("You must provide a valid folder to import the questions into."));
            } else {
                $folder_id = (int)$_POST["folder_id"];
            }
            if (!isset($_FILES["questions"])) {
                add_error($translate->_("You must choose a questions file."));
            } else if ($_FILES["questions"]["error"]) {
                add_error($translate->_("There was an error uploading the provided questions file."));
            } else {
                $question_text = file_get_contents($_FILES["questions"]["tmp_name"]);
                // Check if the file is RTF, DOC, or DOCX. These files start with a specific signature.
                $rtf_start = '{\rtf1';
                $doc_start = chr(0xD0).chr(0xCF).chr(0x11).chr(0xE0).chr(0xA1).chr(0xB1).chr(0x1A).chr(0xE1);
                $docx_start = 'PK'.chr(0x03).chr(0x04).chr(0x14).chr(0x00).chr(0x06).chr(0x00);
                if (substr($question_text, 0, strlen($rtf_start)) === $rtf_start) {
                    add_error(sprintf($translate->_("%s file format was detected, make sure the questions file is plain text."), "RTF"));
                } else if (substr($question_text, 0, strlen($doc_start)) === $doc_start) {
                    add_error(sprintf($translate->_("%s file format was detected, make sure the questions file is plain text."), "DOC"));
                } else if (substr($question_text, 0, strlen($docx_start)) === $docx_start) {
                    add_error(sprintf($translate->_("%s file format was detected, make sure the questions file is plain text."), "DOCX"));
                } else {
                    // File is not RTF, DOC, or DOCX, so we assume it is plain text.
                    $parsed = Models_Exam_Question_Parser::parseExamsoft($question_text);
                    if (is_array($parsed)) {
                        if (!isset($parsed["questions"]) || 0 === count($parsed["questions"])) {
                            add_error($translate->_("No questions found."));
                        } else if (!has_error()) {
                            foreach ($parsed["questions"] as $index => &$question) {
                                Models_Exam_Question_Parser::validate($question, $index + 1, $folder_id);
                                $existing_question = Models_Exam_Question_Versions::fetchRowByExamsoftID($question["examsoft_id"]);
                                if ($existing_question) {
                                    $question["question_id"] = $existing_question->getID();
                                }
                            }
                            unset($question);
                        }
                        if (!isset($parsed["exam"]["name"])) {
                            $parsed["exam"]["name"] = $_FILES["questions"]["name"];
                        }
                    }
                }
            }
            if (has_error()) {
                $STEP = 1;
            }
            break;
        case 1:
        default:
            // Do nothing
            break;
    }

    // Display content
    if (has_error()) {
        echo display_error();
    }
    if (has_notice()) {
        echo display_notice();
    }
    if (has_success()) {
        echo display_success();
    }
    switch ($STEP) {
        case 2:
            ?>
            <div class="alert alert-warning">
                <?php echo $translate->_("Please review the exam and questions below before confirming the import."); ?>
            </div>
            <?php
            // Show a confirmation before importing the questions.
            $num_new_questions = array_reduce($parsed["questions"], function($c, $i) { return $c + (isset($i["question_id"]) ? 0 : 1); }, 0);
            $total_points = array_reduce($parsed["questions"], function($c, $i) { return $c + $i["weight"]; }, 0);
            echo "<h3><strong>Exam Name:</strong> ".$parsed["exam"]["name"]."<br />\n";
            echo "<strong>New Questions:</strong> ".$num_new_questions."<br />\n";
            echo "<strong>Total Questions:</strong> ".count($parsed["questions"])."<br />\n";
            echo "<strong>Total Points:</strong> ".$total_points."</h3>\n";
            foreach ($parsed["questions"] as $question) {
                $question_view = new Views_Exam_Question_ImportPreview($question);
                echo $question_view->render();
            }
            ?>
            <form class="form" method="post">
                <input type="hidden" name="step" value="3" />
                <input type="hidden" name="questions" value="<?php echo html_encode(json_encode($parsed)); ?>" />
                <input type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("Confirm Exam Import"); ?>" />
            </form>
            <?php
            break;
        case 1:
        case 3:
            ?>
            <form class="form form-horizontal" method="post" enctype="multipart/form-data">
                <input type="hidden" name="step" value="2" />
                <div class="control-group">
                    <div class="control-label">
                        <label for="folder_id" class="form-required"><?php echo $translate->_("Folder"); ?>:</label>
                    </div>
                    <div class="controls">
                        <select id="folder_id" name="folder_id">
                            <option value="0">-- <?php echo $translate->_("Select a folder"); ?> --</option>
                            <?php
                            function output_folder_options($parent_folder_id, $prefix = "/") {
                                $folders = Models_Exam_Question_Bank_Folders::fetchAllByParentID($parent_folder_id);
                                foreach ($folders as $folder) {
                                    $folder_path = $prefix.$folder->getFolderTitle();
                                    echo "<option value=\"".$folder->getID()."\">".html_encode($folder_path)."</option>\n";
                                    output_folder_options($folder->getID(), $folder_path."/");
                                }
                            }
                            output_folder_options(0);
                            ?>
                        </select>
                    </div>
                </div>
                <div class="control-group">
                    <div class="control-label">
                        <label for="questions" class="form-required"><?php echo $translate->_("Questions file (in plain text format)"); ?>:</label>
                    </div>
                    <div class="controls">
                        <input type="file" name="questions" id="questions" />
                        <script type="text/javascript">
                            jQuery("#questions").filestyle({
                                icon: true,
                                buttonText: " Find File"
                            });
                        </script>
                    </div>
                </div>
                <input type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("Import Questions"); ?>" />
            </form>
            <?php
            break;
    }
}