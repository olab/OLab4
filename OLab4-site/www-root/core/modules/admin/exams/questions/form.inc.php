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
 * The form that allows users to add and edit formbank questions.
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("ADD_QUESTION") && !defined("EDIT_QUESTION"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("examquestion", "create", false)) {
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

    if (isset($_GET["group_id"]) && $tmp_input = clean_input($_GET["group_id"], "int")) {
        $PROCESSED["group_id"] = $tmp_input;
    }

    if (isset($_GET["folder_id"]) && $tmp_input = clean_input($_GET["folder_id"], "int")) {
        $PROCESSED["folder_id"] = $tmp_input;
    } else if (!isset($PROCESSED["folder_id"])) {
        $PROCESSED["folder_id"] = 0;
    }

    if (isset($_GET["exam_id"]) && $tmp_input = clean_input($_GET["exam_id"], "int")) {
        $PROCESSED["exam_id"] = $tmp_input;
        $_SESSION["exam_id"] = $PROCESSED["exam_id"];
    } else if (isset($_SESSION["exam_id"])) {
        $PROCESSED["exam_id"] = $_SESSION["exam_id"];
    }

    $SECTION_TEXT = $SUBMODULE_TEXT[$SECTION];

    //This form can load two steps
    // - Step 2 Saves a new form version
    //
    switch ($STEP) {
        case 2 :
            if (isset($_POST["element_type"]) && $tmp_input = clean_input($_POST["element_type"], array("trim", "striptags"))) {
                $PROCESSED["element_type"] = $tmp_input;
            }

            if (isset($_POST["id"]) && $tmp_input = clean_input($_POST["id"], "int")) {
                $PROCESSED["id"] = $tmp_input;
            }

            if (isset($_POST["version_id"]) && $tmp_input = clean_input($_POST["version_id"], "int")) {
                $PROCESSED["version_id"] = $tmp_input;
            }

            if (isset($_POST["group_id"]) && $tmp_input = clean_input($_POST["group_id"], "int")) {
                $PROCESSED["group_id"] = $tmp_input;
            }

            if (isset($_POST["questiontype_id"]) && $tmp_input = clean_input($_POST["questiontype_id"], array("trim", "int"))) {
                $PROCESSED["questiontype_id"] = $tmp_input;
            } else {
                if (isset($PROCESSED["group_id"]) && !$PROCESSED["group_id"] || isset($PROCESSED["exam_id"]) && !$PROCESSED["exam_id"]) {
                    add_error($translate->_("You must select an <strong>Question Type</strong> for this question."));
                }
            }

            $text_questiontype = Models_Exam_Lu_Questiontypes::fetchRowByShortname("text");
            $text_questiontype_id = $text_questiontype->getID();

            if (isset($_POST["question_text"]) && $tmp_input = clean_input($_POST["question_text"], array("trim", "html"))) {
                $PROCESSED["question_text"] = $tmp_input;
            } else {
                $PROCESSED["question_text"] = "";
                add_error($translate->_("You must provide <strong>Question Text</strong> for this question."));
            }

            if (isset($_POST["question_description"]) && $tmp_input = clean_input($_POST["question_description"], array("trim"))) {
                $PROCESSED["question_description"] = $tmp_input;
            } else {
                $PROCESSED["question_description"] = "";
            }

            if (isset($_POST["question_rationale"]) && $tmp_input = clean_input($_POST["question_rationale"], array("trim", "html"))) {
                $PROCESSED["question_rationale"] = $tmp_input;
            } else {
                $PROCESSED["question_rationale"] = "";
            }

            if (isset($_POST["question_correct_text"]) && $tmp_input = clean_input($_POST["question_correct_text"], array("trim"))) {
                $PROCESSED["question_correct_text"] = $tmp_input;
            } else {
                $PROCESSED["question_correct_text"] = "";
            }

            if (isset($_POST["question_code"]) && $tmp_input = clean_input($_POST["question_code"], array("trim"))) {
                $PROCESSED["question_code"] = $tmp_input;
            } else {
                $PROCESSED["question_code"] = "";
            }

            if (isset($_POST["grading_scheme"]) && $tmp_input = clean_input($_POST["grading_scheme"], array("trim"))) {
                $PROCESSED["grading_scheme"] = $tmp_input;
            } else {
                $PROCESSED["grading_scheme"] = "partial";
            }

            if (isset($_POST["folder_id"]) && $tmp_input = clean_input($_POST["folder_id"], array("trim", "numeric"))) {
                $PROCESSED["folder_id"] = $tmp_input;
            } elseif (isset($_POST["folder_id"]) && $_POST["folder_id"] != "") {
                add_error($translate->_("No questions are allowed in the <strong>Index Folder</strong>, please provide another <strong>Folder</strong>."));
            } else {
                add_error($translate->_("You must provide a <strong>Folder</strong> for this question."));
            }

            if (isset($_POST["correct-answer"]) && is_string($_POST["correct-answer"])) {
                $PROCESSED["correct"] = json_decode($_POST["correct-answer"]);
                if (is_string($PROCESSED["correct"])) {
                    $PROCESSED["correct"] = json_decode($PROCESSED["correct"]);
                }
            }

            if (isset($_POST["locked_answers_orders"]) && is_string($_POST["locked_answers_orders"])) {
                $PROCESSED["locked_answers_orders"] = json_decode($_POST["locked_answers_orders"]);
                if (is_string($PROCESSED["locked_answers_orders"])) {
                    $PROCESSED["locked_answers_orders"] = json_decode($PROCESSED["locked_answers_orders"]);
                }
            }

            if (isset($_POST["correct_answers_fnb"]) && is_string($_POST["correct_answers_fnb"]) && !empty($_POST["correct_answers_fnb"])) {
                $temp_correct_answer = json_decode($_POST["correct_answers_fnb"], true);
                if (isset($temp_correct_answer) && is_array($temp_correct_answer)) {
                    foreach ($temp_correct_answer as $key => $correct_answers) {
                        foreach ($correct_answers as $i => $item) {
                            $temp_correct_answer[$key][$i] = trim($item);
                        }
                    }
                    $PROCESSED["correct_answers_fnb"] = $temp_correct_answer;
                }
            }

            if (isset($_POST["match_stem_correct"]) && is_string($_POST["match_stem_correct"]) && $_POST["match_stem_correct"] != "") {
                $temp_correct_answer = json_decode($_POST["match_stem_correct"], true);
                if (isset($temp_correct_answer) && is_string($temp_correct_answer)) {
                    $temp_correct_answer = json_decode($temp_correct_answer);
                }

                if (isset($temp_correct_answer) && is_array($temp_correct_answer)) {
                    $PROCESSED["match_stem_correct"] = array();
                    foreach ($temp_correct_answer as $correct_answer) {
                        $PROCESSED["match_stem_correct"][$correct_answer["stem_order"]] = $correct_answer["stem_correct"];
                    }
                }
            }

            if (isset($_POST["answers_fnb_order"]) && is_string($_POST["answers_fnb_order"]) && !empty($_POST["answers_fnb_order"])) {
                $temp_answers_fnb_order = json_decode($_POST["answers_fnb_order"], true);
                $PROCESSED["answers_fnb_order"] = array();
                if (isset($temp_answers_fnb_order) && is_array($temp_answers_fnb_order)) {
                    foreach ($temp_answers_fnb_order as $new_order => $old_order) {
                        if ($new_order != $old_order) {
                            //order has changed so update
                            $PROCESSED["answers_fnb_order"][$new_order] = $old_order;
                        }
                    }
                }
            }

            $question_answer_types = Models_Exam_Lu_Questiontypes::fetchAllRecords();
            if (isset($question_answer_types) && is_array($question_answer_types)) {
                $types_exclude_array = array();
                $types_array = array();
                foreach ($question_answer_types as $type_obj) {
                    if (isset($type_obj) && is_object($type_obj)) {
                        $type = $type_obj->toArray();
                    }

                    if ($type["shortname"] == "text" || $type["shortname"] == "short" || $type["shortname"] == "essay") {
                        $types_exclude_array[] = $type["questiontype_id"];
                    }

                    if ($type["shortname"] === "match") {
                        $match_type_id = $type_obj->getID();
                    }
                    $types_array[] = $type["questiontype_id"];
                }
            }

            if (in_array($PROCESSED["questiontype_id"], $types_array)) {
                $PROCESSED["correct_answer_key"] = array();
                if (isset($_POST["question_answers"]) && is_array($_POST["question_answers"])) {
                    $answer_count = 1;
                    foreach ($_POST["question_answers"] as $key => $answer) {
                        if (!in_array($PROCESSED["questiontype_id"] , $types_exclude_array) && "" === clean_input($answer, array("trim", "html"))) {
                            add_error($translate->_("You must provide text for <strong>Answer " . $key . "</strong>."));
                        }

                        if (in_array($key, $PROCESSED["correct"])) {
                            $PROCESSED["correct_answer_key"][] = $answer_count;
                        }

                        $PROCESSED["answers"][$answer_count] = clean_input($answer, array("trim", "html"));
                        $PROCESSED["weight"][$answer_count] = clean_input($_POST["question_answer_weight"][$key], array("trim", "striptags"));
                        $PROCESSED["answer_rationale"][$answer_count] = clean_input($_POST["question_answer_rationale"][$key], array("trim", "striptags"));
                        $answer_count++;
                    }
                }
            }

            // Generates the match item stems.
            if ($PROCESSED["questiontype_id"] == $match_type_id) {
                if (isset($_POST["question_item_stems"]) && is_array($_POST["question_item_stems"])) {
                    $answer_count = 1;
                    foreach ($_POST["question_item_stems"] as $key => $match) {
                        if ("" === clean_input($answer, array("trim", "html"))) {
                            add_error($translate->_("You must provide text for <strong>Match Item Stem " . $key . "</strong>."));
                        }
                        $PROCESSED["question_item_stems"][$answer_count] = clean_input($match, array("trim", "html"));
                        $answer_count++;
                    }
                }
            }

            $current_match_array = array();
            $current_match_correct_array = array();
            $current_match_stems = Models_Exam_Question_Match::fetchAllRecordsByVersionID($PROCESSED["version_id"]);
            if (isset($current_match_stems) && is_array($current_match_stems) && !empty($current_match_stems)) {
                foreach ($current_match_stems as $current_match_stem) {
                    $current_match_array[$current_match_stem->getOrder()] = $current_match_stem->getMatchText();
                    $correct = Models_Exam_Question_Match_Correct::fetchRowByMatchID($current_match_stem->getID());
                    if (isset($correct) && is_object($correct)) {
                        $current_match_correct_array[$current_match_stem->getOrder()] = (int) $correct->getCorrect();
                    }
                }
            }

            $PROCESSED["objective_ids"] = array();
            if ((isset($_POST["objective_ids_1"])) && (is_array($_POST["objective_ids_1"]))) {
                foreach ($_POST["objective_ids_1"] as $objective_id) {
                    $objective_id = clean_input($objective_id, array("trim", "int"));
                    if ($objective_id && isset($PROCESSED["objective_ids"]) && @count($PROCESSED["objective_ids"])) {
                        foreach ($PROCESSED["objective_ids"] as $temp_objective_id) {
                            if ($temp_objective_id == $objective_id) {
                                add_error($translate->_("You cannot have more than one identical <strong>objective</strong> associated with an question."));
                            }
                        }
                    }
                    $PROCESSED["objective_ids"][] = $objective_id;
                }
            }

            if (!has_error()) {
                $PROCESSED["organisation_id"] = $ENTRADA_USER->getActiveOrganisation();
                $PROCESSED["created_date"] = time();
                $PROCESSED["updated_date"] = time();
                $PROCESSED["created_by"] = $ENTRADA_USER->getActiveID();
                $PROCESSED["updated_by"] = $ENTRADA_USER->getActiveID();

                if (defined("EDIT_QUESTION") && EDIT_QUESTION === true) {
                    $question_changed       = false;
                    $question_version_changed = false;
                    $answer_changed         = false;
                    $match_changed          = false;
                    $match_stem_changed     = false;
                    $question_used          = false;
                    $update_current_version = true;
                    $new_version            = false;

                    if (isset($question_version) && is_object($question_version)) {
                        $short_name = $question_version->getQuestionType()->getShortname();

                        if ((int)$question_version->getFolderID() != (int)$PROCESSED["folder_id"]) {
                            $question_changed = true;
                        }

                        // Check if the old version is the same as the new versions
                        if ((int)$question_version->getQuestionType()->getID() != (int)$PROCESSED["questiontype_id"]) {
                            $question_version_changed = true;
                        }

                        if ($question_version->getQuestionText() != $PROCESSED["question_text"]) {
                            $question_version_changed = true;
                        }

                        if ($question_version->getQuestionDescription() != $PROCESSED["question_description"]) {
                            $question_version_changed = true;
                        }

                        if ($question_version->getRationale() != $PROCESSED["question_rationale"]) {
                            $question_version_changed = true;
                        }

                        if ($question_version->getCorrectText() != $PROCESSED["question_correct_text"]) {
                            $question_version_changed = true;
                        }

                        if ($question_version->getQuestionCode() != $PROCESSED["question_code"]) {
                            $question_version_changed = true;
                        }

                        if ($question_version->getGradingScheme() != $PROCESSED["grading_scheme"]) {
                            $question_version_changed = true;
                        }

                        if ($current_match_array != $PROCESSED["question_item_stems"]) {
                            $match_stem_changed = true;
                            $match_diff_add = array_diff_assoc($PROCESSED["question_item_stems"], $current_match_array);
                            $match_diff_rem = array_diff_assoc($current_match_array, $PROCESSED["question_item_stems"]);
                        }

                        if ($current_match_correct_array != $PROCESSED["match_stem_correct"]) {
                            $match_correct_changed  = true;
                            $match_correct_add      = array_diff_assoc($PROCESSED["match_stem_correct"], $current_match_correct_array);
                            $match_correct_remove   = array_diff_assoc($current_match_correct_array, $PROCESSED["match_stem_correct"]);
                        }

                        $old_answers = $question_version->getQuestionAnswers();
                        if (isset($old_answers) && is_array($old_answers)) {
                            if (isset($PROCESSED["answers"]) && is_array($PROCESSED["answers"])) {
                                if (count($PROCESSED["answers"]) != count($old_answers)) {
                                    $answer_changed = true;
                                }
                            }

                            if ($old_answers) {
                                $ind = 0;
                                $fnb_diff_add = array();
                                $fnb_diff_rem = array();
                                foreach ($old_answers as $key => $answer) {
                                    $answer_count = $ind + 1;


                                    if ((string)$answer->getAnswerText() != (string)$PROCESSED["answers"][$answer_count]) {
                                        $answer_changed = true;
                                        break;
                                    } else if (isset($PROCESSED["correct"][$ind]) && $answer->getCorrect() != $PROCESSED["correct"][$ind]) {
                                        $answer_changed = true;
                                        break;
                                    } else if (isset($PROCESSED["weight"][$ind]) && $answer->getWeight() != $PROCESSED["weight"][$ind]) {
                                        $answer_changed = true;
                                        break;
                                    }else if (isset($PROCESSED["locked_answers_orders"])){
                                        $this_locked = in_array($ind, $PROCESSED["locked_answers_orders"]) ? 1 : 0;
                                        $locked = $answer->getLocked() ? 1 : 0;
                                        if($this_locked != $locked){
                                            $answer_changed = true;
                                            break;
                                        }
                                    }

                                    if ((int)$answer->getOrder() != (int)$key + 1) {
                                        $answer_changed = true;
                                    }

                                    // Checks if the answer changed for the FNB
                                    if ($short_name === "fnb") {
                                        $answer_view = new Views_Exam_Question_Answer($answer);
                                        $answer_array = $answer_view->compileFnbArray();
                                        if (isset($PROCESSED["correct_answers_fnb"][$answer_count]) && is_array($PROCESSED["correct_answers_fnb"][$answer_count]) && isset($answer_array) && is_array($answer_array)) {
                                            $diff_add = array_diff($PROCESSED["correct_answers_fnb"][$answer_count], $answer_array);
                                            if (isset($diff_add) && is_array($diff_add) && !empty($diff_add)) {
                                                $answer_changed = true;
                                                $fnb_diff_add[$answer_count] = $diff_add;
                                            }
                                            $diff_rem = array_diff($answer_array, $PROCESSED["correct_answers_fnb"][$answer_count]);
                                            if (isset($diff_rem) && is_array($diff_rem) && !empty($diff_rem)) {
                                                $answer_changed = true;
                                                $fnb_diff_rem[$answer_count] = $diff_rem;
                                            }
                                        }
                                    }

                                    $ind++;
                                }
                            }
                        } else {
                            // Check if answer are added but weren't set originally
                            if (isset($PROCESSED["answers"]) && is_array($PROCESSED["answers"])) {
                                $answer_changed = true;
                            }
                        }

                        if (isset($PROCESSED["answers_fnb_order"]) && is_array($PROCESSED["answers_fnb_order"]) && !empty($PROCESSED["answers_fnb_order"])) {
                            $answer_changed = true;
                        }

                        $question_view = new Views_Exam_Question($question_version);
                    }
                }

                switch ($METHOD) {
                    case "insert":
                        $question = new Models_Exam_Questions(
                            array(
                                "question_id" => $PROCESSED["question_id"],
                                "folder_id" => $PROCESSED["folder_id"]
                            )
                        );
                        break;
                    case "update":
                        $question = Models_Exam_Questions::fetchRowByID($PROCESSED["question_id"]);

                        if ($question_changed === true) {
                            if ($question && is_object($question)) {
                                $question->setFolderID($PROCESSED["folder_id"]);
                            }
                        }

                        break;
                }

                if ($question->{$METHOD}()) {
                    if ($METHOD == "insert") {
                        $question->setQuestionID($db->Insert_ID());

                        $question_version = new Models_Exam_Question_Versions(
                            array(
                                "question_id"           => $question->getQuestionID(),
                                "version_count"         => 1,
                                "questiontype_id"       => $PROCESSED["questiontype_id"],
                                "question_text"         => $PROCESSED["question_text"],
                                "question_description"  => $PROCESSED["question_description"],
                                "question_rationale"    => $PROCESSED["question_rationale"],
                                "question_correct_text" => $PROCESSED["question_correct_text"],
                                "question_code"         => $PROCESSED["question_code"],
                                "grading_scheme"        => $PROCESSED["grading_scheme"],
                                "organisation_id"       => $PROCESSED["organisation_id"],
                                "created_date"          => $PROCESSED["created_date"],
                                "created_by"            => $ENTRADA_USER->getID(),
                                "updated_date"          => $PROCESSED["updated_date"],
                                "updated_by"            => $ENTRADA_USER->getID()
                            )
                        );

                        if (!$question_version->insert()) {
                            add_error($translate->_("An error occurred while attempting to insert the question version for question id: ") . $question->getQuestionID());
                        } else {
                            $question_version->setVersionID($db->Insert_ID());
                            $short_name = $question_version->getQuestionType()->getShortname();

                            $question_author = new Models_Exam_Question_Authors(
                                array(
                                    "question_id"   => $question_version->getQuestionID(),
                                    "version_id"    => $question_version->getVersionID(),
                                    "author_type"   => "proxy_id",
                                    "author_id"     => $ENTRADA_USER->getActiveID(),
                                    "created_date"  => time(),
                                    "created_by"    => $ENTRADA_USER->getID()
                                )
                            );

                            if (!$question_author->insert()) {
                                add_error($translate->_("An error occurred while attempting to save the question author"));
                            }

                            if (isset($PROCESSED["answers"]) && is_array($PROCESSED["answers"]) && $short_name != "fnb") {
                                $question_answers = $question_version->getQuestionAnswers();
                                if ($question_answers) {
                                    foreach ($question_answers as $answer) {
                                        $answer->fromArray(array("deleted_date" => time()))->update();
                                    }
                                }
                                $order = 1;
                                foreach ($PROCESSED["answers"]  as $key => $answer) {
                                    if (isset($PROCESSED["correct"]) && is_array($PROCESSED["correct"]) && in_array($key, $PROCESSED["correct"])) {
                                        $PROCESSED_RESPONSE["correct"] = 1;
                                    } else {
                                        $PROCESSED_RESPONSE["correct"] = 0;
                                    }

                                    if (isset($PROCESSED["weight"]) && is_array($PROCESSED["weight"]) && array_key_exists($key, $PROCESSED["weight"])) {
                                        $PROCESSED_RESPONSE["weight"] = $PROCESSED["weight"][$key];
                                    } else {
                                        $PROCESSED_RESPONSE["weight"] = 0;
                                    }

                                    if (isset($PROCESSED["answer_rationale"]) && is_array($PROCESSED["answer_rationale"]) && array_key_exists($key, $PROCESSED["answer_rationale"])) {
                                        $PROCESSED_RESPONSE["answer_rationale"] = $PROCESSED["answer_rationale"][$key];
                                    } else {
                                        $PROCESSED_RESPONSE["answer_rationale"] = NULL;
                                    }

                                    if (isset($PROCESSED["locked_answers_orders"]) && in_array($key, $PROCESSED["locked_answers_orders"])){
                                        $PROCESSED_RESPONSE["locked"] = 1;
                                    }else{
                                        $PROCESSED_RESPONSE["locked"] = 0;
                                    }

                                    $answer = new Models_Exam_Question_Answers(
                                        array(
                                            "question_id"       => $question_version->getQuestionID(),
                                            "version_id"        => $question_version->getVersionID(),
                                            "answer_text"       => $answer,
                                            "answer_rationale"  => $PROCESSED_RESPONSE["answer_rationale"],
                                            "correct"           => $PROCESSED_RESPONSE["correct"],
                                            "weight"            => $PROCESSED_RESPONSE["weight"],
                                            "order"             => $order,
                                            "updated_date"      => $PROCESSED["created_date"],
                                            "updated_by"        => $ENTRADA_USER->getID(),
                                            "locked"            => $PROCESSED_RESPONSE["locked"],
                                        )
                                    );

                                    if (!$answer->insert()) {
                                        add_error($translate->_("An error occurred while attempting to insert one of the question answers, database said: " . $db->ErrorMsg()));
                                    }
                                    $order ++;
                                }
                            }

                            if ($short_name == "fnb") {
                                $question_text_array = explode("_?_", $question_version->getQuestionText());

                                if (isset($question_text_array) && is_array($question_text_array)) {
                                    $question_parts_count = count($question_text_array);
                                    foreach ($question_text_array as $order => $question_part) {
                                        $order = $order + 1;
                                        if ($question_part != "" && $question_parts_count > $order) {
                                            $answer = new Models_Exam_Question_Answers(
                                                array(
                                                    "question_id"       => $question_version->getQuestionID(),
                                                    "version_id"        => $question_version->getVersionID(),
                                                    "order"             => $order,
                                                    "updated_date"      => $PROCESSED["created_date"],
                                                    "updated_by"        => $ENTRADA_USER->getID()
                                                )
                                            );

                                            if (!$answer->insert()) {
                                                add_error($translate->_("An error occurred while attempting to create part of the fnb, database said: " . $db->ErrorMsg()));
                                            } else {
                                                if (isset($PROCESSED["correct_answers_fnb"]) && is_array($PROCESSED["correct_answers_fnb"])) {
                                                    foreach ($PROCESSED["correct_answers_fnb"][$order] as $correct_answers_fnb) {
                                                        if (isset($correct_answers_fnb) && is_string($correct_answers_fnb)) {
                                                            $Fnb_text_obj = new Models_Exam_Question_Fnb_Text(
                                                                array(
                                                                    "qanswer_id"        => $answer->getID(),
                                                                    "text"              => $correct_answers_fnb,
                                                                    "updated_date"      => $PROCESSED["created_date"],
                                                                    "updated_by"        => $ENTRADA_USER->getID()
                                                                )
                                                            );

                                                            if (!$Fnb_text_obj->insert()) {
                                                                add_error($translate->_("An error occurred while attempting to create part of the fnb text, database said: " . $db->ErrorMsg()));
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }

                                if (isset($PROCESSED["answers_fnb_order"]) && is_array($PROCESSED["answers_fnb_order"]) && !empty($PROCESSED["answers_fnb_order"])) {
                                    $order_update = array();
                                    foreach ($PROCESSED["answers_fnb_order"] as $new_order => $old_order) {
                                        //get all old order objects, then loop through them and update
                                        // this should insure that we don't change the order before we have selected all of them.
                                        $answer = Models_Exam_Question_Answers::fetchRowByVersionIDOrder($question_version->getVersionID(), $old_order);
                                        if (isset($answer) && is_object($answer)) {
                                            $answer->setOrder($new_order);
                                            $order_update[] = $answer;
                                        }
                                    }
                                    if (isset($order_update) && is_array($order_update) && !empty($order_update)) {
                                        foreach($order_update as $answer) {
                                            if (!$answer->update()) {
                                                add_error($translate->_("An error occurred while attempting to update fnb answer order, database said: " . $db->ErrorMsg()));
                                            }
                                        }
                                    }
                                }
                            }

                            if (isset($PROCESSED["question_item_stems"]) && is_array($PROCESSED["question_item_stems"]) && !empty($PROCESSED["question_item_stems"])) {
                                foreach ($PROCESSED["question_item_stems"] as $order => $stem) {
                                    $match = new Models_Exam_Question_Match(
                                        array(
                                            "version_id"    => $question_version->getVersionID(),
                                            "match_text"    => $stem,
                                            "order"         => $order,
                                            "updated_date"  => $PROCESSED["created_date"],
                                            "updated_by"    => $ENTRADA_USER->getID()
                                        )
                                    );
                                    if (!$match->insert()) {
                                        add_error($translate->_("An error occurred while attempting to insert one of the question match stems, database said: " . $db->ErrorMsg()));
                                    } else {
                                        $match_correct_value = $PROCESSED["match_stem_correct"][$order];
                                        $qanwser = Models_Exam_Question_Answers::fetchRowByVersionIDOrder($question_version->getVersionID(), $order);

                                        $match_correct = new Models_Exam_Question_Match_Correct(
                                            array(
                                                "match_id"      => $match->getID(),
                                                "qanswer_id"    => $qanwser->getID(),
                                                "correct"       => $match_correct_value,
                                                "updated_date"  => $PROCESSED["created_date"],
                                                "updated_by"    => $ENTRADA_USER->getID()
                                            )
                                        );

                                        if (!$match_correct->insert()) {
                                            add_error($translate->_("An error occurred while attempting to insert one of the question match correct answers, database said: " . $db->ErrorMsg()));
                                        }
                                    }
                                }
                            }
                        }
                    } else if ($METHOD = "update") {
                        // If the question is flagged from the ExamSoft import, unflag the question here
                        if ($question_version->getExamsoftFlagged()) {
                            $question_version->setExamsoftFlagged(false);
                            if (!$question_version->update()) {
                                add_error($translate->_("Failed to unflag exam question."));
                                application_log("Error", "Failed to unflag exam question. DB said: ".$db->ErrorMsg());
                            }
                        }

                        if ($question_version_changed == true || $answer_changed == true || $match_stem_changed == true || $match_correct_changed == true) {
                            // then use version_id for exam_questions and exam_question_answers
                            $old_question_version   = $question_version;
                            $version_count          = $old_question_version->getHighestVersionNumber(0);

                            $question_version = new Models_Exam_Question_Versions(
                                array(
                                    "question_id"           => $old_question_version->getQuestionID(),
                                    "version_count"         => $version_count + 1,
                                    "questiontype_id"       => $PROCESSED["questiontype_id"],
                                    "question_text"         => $PROCESSED["question_text"],
                                    "question_description"  => $PROCESSED["question_description"],
                                    "question_rationale"    => $PROCESSED["question_rationale"],
                                    "question_correct_text" => $PROCESSED["question_correct_text"],
                                    "question_code"         => $PROCESSED["question_code"],
                                    "grading_scheme"        => $PROCESSED["grading_scheme"],
                                    "organisation_id"       => $PROCESSED["organisation_id"],
                                    "examsoft_id"           => $PROCESSED["examsoft_id"],
                                    "created_date"          => $old_question_version->getCreatedDate(),
                                    "created_by"            => $old_question_version->getCreatedBy(),
                                    "updated_date"          => $PROCESSED["updated_date"],
                                    "updated_by"            => $PROCESSED["updated_by"],
                                    "examsoft_images_added" => $old_question_version->getExamsoftImagesAdded()
                                )
                            );

                            if (!$question_version->insert()) {
                                add_error($translate->_("An error occurred while attempting to insert the question version, database said: " . $db->ErrorMsg()));
                                application_log("error", $db->ErrorMsg());
                            } else {
                                $question_version->setVersionID($db->Insert_ID());
                                
                                $old_question_authors = Models_Exam_Question_Authors::fetchAllByVersionID($old_question_version->getVersionID());
                                if ($old_question_authors && is_array($old_question_authors) && !empty($old_question_authors)) {
                                    foreach ($old_question_authors as $old_author) {
                                        $question_author = new Models_Exam_Question_Authors(
                                            array(
                                                "question_id"   => $question_version->getQuestionID(),
                                                "version_id"    => $question_version->getVersionID(),
                                                "author_type"   => $old_author->getAuthorType(),
                                                "author_id"     => $old_author->getAuthorID(),
                                                "created_date"  => $old_author->getCreatedDate(),
                                                "created_by"    => $old_author->getCreatedBy()
                                            )
                                        );

                                        if (!$question_author->insert()) {
                                            add_error($translate->_("An error occurred while attempting to save the question author"));
                                        }
                                    }
                                }

                                $current_author_exists = Models_Exam_Question_Authors::fetchRowByVersionIDAuthorIDAuthorType($question_version->getVersionID(), $ENTRADA_USER->getActiveID(), "proxy_id");
                                if (!$current_author_exists) {
                                    $question_author = new Models_Exam_Question_Authors(
                                        array(
                                            "question_id"   => $question_version->getQuestionID(),
                                            "version_id"    => $question_version->getVersionID(),
                                            "author_type"   => "proxy_id",
                                            "author_id"     => $ENTRADA_USER->getActiveID(),
                                            "created_date"  => time(),
                                            "created_by"    => $ENTRADA_USER->getID()
                                        )
                                    );

                                    if (!$question_author->insert()) {
                                        add_error($translate->_("An error occurred while attempting to save the question author"));
                                    }
                                }

                                if (isset($PROCESSED["answers"]) && is_array($PROCESSED["answers"])) {
                                    $order = 1;
                                    foreach ($PROCESSED["answers"]  as $key => $answer) {
                                        if (isset($PROCESSED["correct_answer_key"]) && is_array($PROCESSED["correct_answer_key"]) && in_array($key, $PROCESSED["correct_answer_key"])) {
                                            $PROCESSED_RESPONSE["correct"] = 1;
                                        } else {
                                            $PROCESSED_RESPONSE["correct"] = 0;
                                        }

                                        if (isset($PROCESSED["weight"]) && is_array($PROCESSED["weight"]) && array_key_exists($key, $PROCESSED["weight"])) {
                                            $PROCESSED_RESPONSE["weight"] = $PROCESSED["weight"][$key];
                                        } else {
                                            $PROCESSED_RESPONSE["weight"] = 0;
                                        }

                                        if (isset($PROCESSED["answer_rationale"]) && is_array($PROCESSED["answer_rationale"]) && array_key_exists($key, $PROCESSED["answer_rationale"])) {
                                            $PROCESSED_RESPONSE["answer_rationale"] = $PROCESSED["answer_rationale"][$key];
                                        } else {
                                            $PROCESSED_RESPONSE["answer_rationale"] = NULL;
                                        }

                                        if (isset($PROCESSED["locked_answers_orders"]) && in_array($key, $PROCESSED["locked_answers_orders"])){
                                            $PROCESSED_RESPONSE["locked"] = 1;
                                        }else{
                                            $PROCESSED_RESPONSE["locked"] = 0;
                                        }

                                        $answer = new Models_Exam_Question_Answers(
                                            array(
                                                "question_id"       => $question_version->getQuestionID(),
                                                "version_id"        => $question_version->getVersionID(),
                                                "answer_text"       => $answer,
                                                "answer_rationale"  => $PROCESSED_RESPONSE["answer_rationale"],
                                                "correct"           => $PROCESSED_RESPONSE["correct"],
                                                "weight"            => $PROCESSED_RESPONSE["weight"],
                                                "order"             => $order,
                                                "updated_date"      => $PROCESSED["created_date"],
                                                "updated_by"        => $ENTRADA_USER->getID(),
                                                "locked"            => $PROCESSED_RESPONSE["locked"],
                                            )
                                        );
                                        if (!$answer->insert()) {
                                            add_error($translate->_("An error occurred while attempting to create one of the question answers, database said: " . $db->ErrorMsg()));
                                        }
                                        $order ++;
                                    }
                                }

                                if ($short_name == "fnb") {
                                    $question_text_array = explode("_?_", $question_version->getQuestionText());

                                    if (isset($question_text_array) && is_array($question_text_array)) {
                                        $question_parts_count = count($question_text_array);
                                        foreach ($question_text_array as $order => $question_part) {
                                            $order = $order + 1;
                                            if ($question_part != "" && $question_parts_count > $order) {
                                                $answer = new Models_Exam_Question_Answers(
                                                    array(
                                                        "question_id"       => $question_version->getQuestionID(),
                                                        "version_id"        => $question_version->getVersionID(),
                                                        "order"             => $order,
                                                        "updated_date"      => $PROCESSED["created_date"],
                                                        "updated_by"        => $ENTRADA_USER->getID()
                                                    )
                                                );

                                                if (!$answer->insert()) {
                                                    add_error($translate->_("An error occurred while attempting to create part of the fnb, database said: " . $db->ErrorMsg()));
                                                } else {
                                                    if (isset($PROCESSED["correct_answers_fnb"]) && is_array($PROCESSED["correct_answers_fnb"])) {
                                                        foreach ($PROCESSED["correct_answers_fnb"][$order] as $correct_answers_fnb) {
                                                            if (isset($correct_answers_fnb) && is_string($correct_answers_fnb)) {
                                                                $Fnb_text_obj = new Models_Exam_Question_Fnb_Text(
                                                                    array(
                                                                        "qanswer_id"        => $answer->getID(),
                                                                        "text"              => $correct_answers_fnb,
                                                                        "updated_date"      => $PROCESSED["created_date"],
                                                                        "updated_by"        => $ENTRADA_USER->getID()
                                                                    )
                                                                );

                                                                if (!$Fnb_text_obj->insert()) {
                                                                    add_error($translate->_("An error occurred while attempting to create part of the fnb text, database said: " . $db->ErrorMsg()));
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    if (isset($PROCESSED["answers_fnb_order"]) && is_array($PROCESSED["answers_fnb_order"]) && !empty($PROCESSED["answers_fnb_order"])) {
                                        $order_update = array();
                                        foreach ($PROCESSED["answers_fnb_order"] as $new_order => $old_order) {
                                            //get all old order objects, then loop through them and update
                                            // this should insure that we don't change the order before we have selected all of them.
                                            $answer = Models_Exam_Question_Answers::fetchRowByVersionIDOrder($question_version->getVersionID(), $old_order);
                                            if (isset($answer) && is_object($answer)) {
                                                $answer->setOrder($new_order);
                                                $order_update[] = $answer;
                                            }
                                        }
                                        if (isset($order_update) && is_array($order_update) && !empty($order_update)) {
                                            foreach($order_update as $answer) {
                                                if (!$answer->update()) {
                                                    add_error($translate->_("An error occurred while attempting to update fnb answer order, database said: " . $db->ErrorMsg()));
                                                }
                                            }
                                        }
                                    }
                                } else if ($short_name == "match") {
                                    if (isset($PROCESSED["question_item_stems"]) && is_array($PROCESSED["question_item_stems"]) && !empty($PROCESSED["question_item_stems"])) {
                                        foreach ($PROCESSED["question_item_stems"] as $order => $stem) {
                                            $match = new Models_Exam_Question_Match(
                                                array(
                                                    "version_id"        => $question_version->getVersionID(),
                                                    "match_text"        => $stem,
                                                    "order"             => $order,
                                                    "updated_date"      => $PROCESSED["created_date"],
                                                    "updated_by"        => $ENTRADA_USER->getID()
                                                )
                                            );
                                            if (!$match->insert()) {
                                                add_error($translate->_("An error occurred while attempting to insert one of the question match stems, database said: " . $db->ErrorMsg()));
                                            } else {
                                                $match_correct_value = $PROCESSED["match_stem_correct"][$order];
                                                $qanwser    = Models_Exam_Question_Answers::fetchRowByVersionIDOrder($question_version->getVersionID(), $match_correct_value);
                                                if ($qanwser && is_object($qanwser)) {
                                                    $match_correct = new Models_Exam_Question_Match_Correct(
                                                        array(
                                                            "match_id"      => $match->getID(),
                                                            "qanswer_id"    => $qanwser->getID(),
                                                            "correct"       => $match_correct_value,
                                                            "updated_date"  => $PROCESSED["created_date"],
                                                            "updated_by"    => $ENTRADA_USER->getID()
                                                        )
                                                    );

                                                    if (!$match_correct->insert()) {
                                                        add_error($translate->_("An error occurred while attempting to insert one of the question match correct answers, database said: " . $db->ErrorMsg()));
                                                    }
                                                }

                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    $existing_question_objectives = Models_Exam_Question_Objectives::fetchAllRecordsByQuestionID($question_version->getQuestionID());

                    $existing_question_objective_ids = array();
                    foreach ($existing_question_objectives as $existing_question_objective) {
                        $existing_question_objective_ids[] = $existing_question_objective->getObjectiveID();
                        if (!in_array($existing_question_objective->getObjectiveID(), $PROCESSED["objective_ids"])) {
                            $tmp_objective_array = $existing_question_objective->toArray();
                            $tmp_objective_array["updated_date"] = time();
                            $tmp_objective_array["updated_by"]   = $ENTRADA_USER->getID();
                            $tmp_objective_array["deleted_date"] = time();
                            if (!$existing_question_objective->fromArray($tmp_objective_array)->update()) {
                                application_log("error", "Unable to deactivate an objective [".$existing_question_objective->getObjectiveID()."] associated with an exam question [".$question_version->getQuestionID()."]");
                            }
                        }
                    }
                    /**
                     * Add the question objectives to the evaluation_question_objectives table.
                     */
                    if ((isset($PROCESSED["objective_ids"])) && (@count($PROCESSED["objective_ids"]))) {
                        foreach ($PROCESSED["objective_ids"] as $objective_id) {
                            if (!in_array($objective_id, $existing_question_objective_ids)) {
                                $PROCESSED_OBJECTIVE = array(
                                    "question_id"   => $question_version->getQuestionID(),
                                    "objective_id"  => $objective_id,
                                    "created_date"  => time(),
                                    "created_by"    => $ENTRADA_USER->getID()
                                );
                                $question_objective = new Models_Exam_Question_Objectives($PROCESSED_OBJECTIVE);
                                if (!$question_objective->insert()) {
                                    add_error($translate->_("There was an error while trying to attach an <strong>Objective</strong> to this question.<br /><br />The system administrator was informed of this error; please try again later."));
                                    application_log("error", "Unable to insert a new exam_question_objectives record while managing an question [" . $question_version->getQuestionID() . "]. Database said: " . $db->ErrorMsg());
                                }
                            }
                        }
                    }

                    if (!has_error()) {

                        if ((isset($PROCESSED["exam_id"]) || isset($PROCESSED["group_id"])) && isset($PROCESSED["element_type"])) {
                            $SUCCESS = 0;

                            switch ($PROCESSED["element_type"]) {
                                case "exam" :
                                    $url = ENTRADA_URL."/admin/".$MODULE."/exams?section=edit-exam&id=".$PROCESSED["exam_id"];

                                    $exam_element_data = array(
                                        "exam_id"           => $PROCESSED["exam_id"],
                                        "element_type"      => "question",
                                        "element_id"        => $question_version->getVersionID(),
                                        "order"             => Models_Exam_Exam_Element::fetchNextOrder($PROCESSED["exam_id"]),
                                        "allow_comments"    => 1,
                                        "enable_flagging"   => 0,
                                        "updated_date"      => time(),
                                        "updated_by"        => $ENTRADA_USER->GetID()
                                    );

                                    $exam_element = new Models_Exam_Exam_Element($exam_element_data);

                                    if ($exam_element->insert()) {
                                        $SUCCESS++;
                                    } else {
                                        add_error($SUBMODULE_TEXT["failed_to_create"]);
                                    }
                                    break;
                                case "group" :
                                    $group_id = $PROCESSED["group_id"] ? $PROCESSED["group_id"] : $PROCESSED["id"];
                                    $url = ENTRADA_URL."/admin/".$MODULE."/groups?section=edit-group&group_id=".$group_id."&exam_id=".$PROCESSED["exam_id"];

                                    $already_attached = Models_Exam_Group_Question::fetchRowByQuestionIDGroupID($question_version->getQuestionID(), $group_id);

                                    if (!$already_attached) {

                                        $posted = Models_Exam_Exam_Element::isGroupIdPosted($PROCESSED["group_id"]);
                                        if ($posted === false) {
                                        $order = Models_Exam_Group_Question::fetchNextOrder($group_id);
                                        if (!$order) {
                                            $order = 1;
                                        }
                                        if (isset($clone_question) && $clone_question) {
                                            $old_question_group = Models_Exam_Group_Question::fetchRowByQuestionIDGroupID($old_question["question_id"], $group_id);
                                            if ($old_group_question) {
                                                $order = $old_group_question["order"];
                                            }
                                        }
                                        $group_question_data = array(
                                            "group_id"          => $group_id,
                                            "question_id"       => $question_version->getQuestionID(),
                                            "version_id"        => $question_version->getVersionID(),
                                            "order"             => $order,
                                        );
                                        $group_question = new Models_Exam_Group_Question($group_question_data);

                                        if ($group_question->insert()) {
                                            $ENTRADA_LOGGER->log("Group Questions", "add-new-group-question", "arquestion_id", $group_question->getID(), 4, __FILE__, $ENTRADA_USER->getID());
                                            $SUCCESS++;

                                                $exam_elements = Models_Exam_Exam_Element::fetchAllByGroupID($PROCESSED["group_id"]);
                                                if ($exam_elements && is_array($exam_elements) && !empty($exam_elements)) {
                                                    $last   = end($exam_elements);
                                                    $order  = $last->getOrder();

                                                    $new_exam_element = new Models_Exam_Exam_Element(array(
                                                        "exam_id"       => $last->getExamID(),
                                                        "element_type"  => "question",
                                                        "element_id"    => $question_version->getVersionID(),
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
                                        } else {
                                            add_error($SECTION_TEXT["failed_to_create"]);
                                        }
                                        } else {
                                            add_error($SECTION_TEXT["group_already_posted"]);
                                        }
                                    }
                                    break;
                            }

                            if (has_success()) {
                                Entrada_Utilities_Flashmessenger::addMessage("Successfully added <strong>".$SUCCESS."</strong> questions to the " . $PROCESSED["element_type"] . ".", "success", $MODULE);
                                header("Location: ". $url);
                            }
                        }

                        if (isset ($clone_question) && $clone_question) {
                            $url = ENTRADA_URL."/admin/exams/questions/?section=edit-question&id=".$question_version->getQuestionID();
                            $success_msg = sprintf($translate->_("The question has successfully been added. You will be redirected to the NEW question. Please <a href=\"%s\">click here</a> if you do not wish to wait."), $url);
                        } elseif (isset($PROCESSED["group_id"])) {
                            $url = ENTRADA_URL."/admin/exams/groups?section=edit-group&group_id=".$PROCESSED["group_id"]."&exam_id=".$PROCESSED["exam_id"];
                            $success_msg = sprintf($translate->_("The question has been successfully saved. You will be redirected back to the Grouped Question. Please <a href=\"%s\">click here</a> if you do not wish to wait."), $url);
                        } elseif ($METHOD == "update") {
                            if ($PROCESSED["exam_id"]) {
                                $url = ENTRADA_URL."/admin/".$MODULE."/".$MODULE . "?section=edit-exam&id=" . $PROCESSED["exam_id"];
                                $msg = $SUBMODULE_TEXT["edit-question"]["success_msg_04"] . "<a href=\"%s\">" . $SUBMODULE_TEXT["edit-question"]["success_msg_02"] . "</a>" . $SUBMODULE_TEXT["edit-question"]["success_msg_03"];
                            } else {
                                $url = ENTRADA_URL."/admin/".$MODULE."/".$SUBMODULE . "?folder_id=" . $question_version->getFolderID();
                                $msg = $SUBMODULE_TEXT["edit-question"]["success_msg_01"] . "<a href=\"%s\">" . $SUBMODULE_TEXT["edit-question"]["success_msg_02"] . "</a>" . $SUBMODULE_TEXT["edit-question"]["success_msg_03"];
                            }
                            $success_msg = sprintf($msg, $url);
                        } else {
                            if ($PROCESSED["element_type"] == "exam" && $PROCESSED["exam_id"]) {
                                // User comes from the exam page (Add & Attach). Redirect to exam page.
                                $url = ENTRADA_URL."/admin/exams/exams?section=edit-exam&id=".$PROCESSED["exam_id"];
                                $success_msg = sprintf($translate->_("The question has successfully been added. You will be redirected to the exam questions page, please <a href=\"%s\">click here</a> if you do not wish to wait."), $url);
                            } else {
                                // Redirect to question folder.
                                $url = ENTRADA_URL."/admin/".$MODULE."/".$SUBMODULE . "?folder_id=" . $question_version->getFolderID();
                                $success_msg = sprintf($translate->_("The question has successfully been added. You will be redirected to the question bank index, please <a href=\"%s\">click here</a> if you do not wish to wait."), $url);
                            }
                        }

                        add_success($success_msg);
                        $ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
                    }
                } else {
                    add_error($translate->_("An error occurred while attempting to update the question."));

                    $STEP = 1;
                }

            } else {
                $STEP = 1;
            }

            break;
        case 1 :
            if (isset($question_version) && is_object($question_version)) {
                $PROCESSED["objective_ids"] = array();
                $objectives = $question_version->getQuestionObjectives();
                if ($objectives) {
                    foreach ($objectives as $objective) {
                        $PROCESSED["objective_ids"][] = $objective->getObjectiveID();
                    }
                }
                break;
            }
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
            if ($question_version && is_object($question_version)) {
                $question_view          = new Views_Exam_Question($question_version);
                $update_current_version = $question_view->createNewVersionCheck();
                $answers                = $question_version->getQuestionAnswers();
                $question_type          = $question_version->getQuestionType();
                $PROCESSED['locked_answers_orders'] = array();
                if ($question_type && is_object($question_type)) {
                    $short_name             = $question_type->getShortname();
                    if (isset($answers) && is_array($answers)) {
                        $count = 1;
                        $PROCESSED["answers"]       = array();
                        $PROCESSED["answers_fnb"]   = array();
                        foreach ($answers as $answer) {
                            switch($short_name) {
                                case "fnb":
                                    $answer_view    = new Views_Exam_Question_Answer($answer);
                                    $answer_array   = $answer_view->compileFnbArray();
                                    $fnb_answers    = true;
                                    $PROCESSED["answers_fnb"][$count] = $answer_array;
                                    break;
                                default:
                                    $PROCESSED["answers"][$count] = $answer->getAnswerText();
                                    $PROCESSED["correct"][$count] = $answer->getCorrect();
                                    $PROCESSED["locked"][$count] = $answer->getLocked();
                                    $PROCESSED["weight"][$count]  = $answer->getWeight();
                                    $PROCESSED["answer_rationale"][$count] = $answer->getRationale();
                                    break;
                            }
                            if($answer->getLocked()) {
                                array_push($PROCESSED['locked_answers_orders'], $count);
                            }

                            $count++;
                        }
                    }
                }
                if ($fnb_answers) {
                    $PROCESSED["answers"] = $PROCESSED["answers_fnb"];
                }

                if ($short_name === "match") {
                    $match_stems = $question_version->getMatchStems();
                    if (isset($match_stems) && is_array($match_stems)) {
                        foreach ($match_stems as $stem) {
                            if (isset($stem) && is_object($stem)) {
                                $PROCESSED["question_item_stems"][$stem->getOrder()] = $stem;
                            }
                        }
                    }
                }
            }

            $question_in_use = false;
            $group_questions_check = false;
            if (defined("EDIT_QUESTION") && EDIT_QUESTION) {
                if (isset($PROCESSED["group_id"])) {
                    $group_questions_check = Models_Exam_Group_Question::fetchAllByVersionID($PROCESSED["id"]);
                    if ($group_questions_check && count($group_questions_check) == 1 && $group_questions_check[0]->getGroupID() == $PROCESSED["group_id"]) {
                        $group_questions_check = false;
                    }
                }
            }

            $exam_questions_check = false;
            if (isset($PROCESSED["id"]) && $PROCESSED["element_type"] == "question") {
                $exam_questions_check = Models_Exam_Exam_Element::fetchRowByElementIDElementType($PROCESSED["id"], "question");
            }

            $exam_group_check = false;
            if (isset($PROCESSED["group_id"])) {
                $exam_group_check = Models_Exam_Exam_Element::fetchAllByGroupID($PROCESSED["group_id"]);
            }

            if (($exam_questions_check || $group_questions_check || $exam_group_check) && (defined("EDIT_QUESTION") && EDIT_QUESTION))  {
                $question_in_use = true;
            }

            if (defined("EDIT_QUESTION") && EDIT_QUESTION) {
                if ($question_in_use && isset($PROCESSED["group_id"])) {
                    add_notice(sprintf($translate->_("You are editing a question that belongs to a Group already. If you make any changes to this question, it will be copied and the new question (with changes) will be attached to your Group. Please note that you cannot edit the Answer Categories here. If you need to edit the Answer Categories <a href=\"%s\">click here</a> to load this Group Question independently of the Group it is already attached to."), ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=edit-question&id=".$PROCESSED["id"]));
                } else if ($question_in_use) {
                    add_notice($translate->_("You are editing an question that belongs to a Exam already. If you make any changes to this question, a new question will be automatically be created."));
                }
            } else {
                // add question
                if ($exam_group_check && isset($PROCESSED["group_id"])) {
                    add_notice($translate->_("You are adding a question to a Group that is already attached to an exam."));
                }
            }

            $PROCESSED["group_questions"] = array();
            if (isset($_GET["group_questions"])) {
                $group_questions_array = $_GET["group_questions"];
                if ($group_questions_array) {
                    $PROCESSED["group_questions"] = array();
                    foreach($group_questions_array as $tmp_input) {
                        $tmp_input = clean_input($tmp_input, array("int"));
                        if ($tmp_input) {
                            $PROCESSED["group_questions"][] = $tmp_input;
                        }
                    }
                }
            }

            if (!empty($PROCESSED["group_questions"])) {
                $group_question_string = "&amp;group_questions[]=".implode('&amp;group_questions[]=', array_map('urlencode', $PROCESSED["group_questions"]));
            }
            if ($PROCESSED["group_questions"]) {
                $group_question = Models_Exam_Questions::fetchRowByID($PROCESSED["group_questions"][0]);
                $PROCESSED["questiontype_id"] = $group_question->getQuestionTypeID();
            }
        default:
            if (has_success()) {
                echo display_success();
            }
            if (has_notice()) {
                echo display_notice();
            }
            if (has_error()) {
                echo display_error();
            }

            /**
             * Load the rich text editor.
             */
            load_rte('examadvanced', array('autogrow' => true, 'divarea' => true));

            $HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/objectives.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
            $HEAD[] = "<script type=\"text/javascript\">var API_URL = \"". ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-questions" ."\";</script>";
            $HEAD[] = "<script type=\"text/javascript\">var FOLDER_API_URL = \"". ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-folders" ."\";</script>";
            $HEAD[] = "<script type=\"text/javascript\">var SITE_URL = '".ENTRADA_URL."';</script>";
            $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.audienceselector.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
            $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.audienceselector.css?release=". html_encode(APPLICATION_VERSION) ."\" />";


            $restrict_to_folder = false;
            $restrict_folder_ids = array();
            $restrict_folder_array_children = array();
            $access_folder_allowed_array = array();

            $group = $ENTRADA_USER->getActiveGroup();

            if ($group === "student") {
                $allowed_folders = Models_Exam_Bank_Folders::fetchAllByTypeAuthor("question", $ENTRADA_USER->getID());

                if ($allowed_folders && is_array($allowed_folders) && !empty($allowed_folders)) {
                    foreach ($allowed_folders as $folder) {
                        $restrict_folder_ids[] = (int)$folder->getID();
                    }
                }

                if ($restrict_folder_ids && is_array($restrict_folder_ids) && !empty($restrict_folder_ids)) {
                    foreach ($restrict_folder_ids as $folder_restricted) {
                        if (!in_array($folder_restricted, $restrict_folder_array_children)) {
                            $restrict_folder_array_children[(int)$folder_restricted] = (int)$folder_restricted;
                        }
                        $restrict_folder_array_children = Models_Exam_Bank_Folders::getChildrenFolders($folder_restricted, $restrict_folder_array_children, "question");
                    }
                }
            }

            $initial_folders = Models_Exam_Bank_Folders::fetchAllByParentID($PROCESSED["folder_id"], "question");

            if ($PROCESSED["folder_id"] === 0) {
                $root_folder = new Models_Exam_Bank_Folders(
                    array(
                        "folder_id" => 0,
                        "folder_title" => "Index",
                        "image_id" => 3,
                        "folder_type" => "question"
                    )
                );

                $initial_folder_view = new Views_Exam_Bank_Folder($root_folder);
                if (isset($initial_folder_view) && is_object($initial_folder_view)) {
                    $title = $initial_folder_view->renderFolderSelectorTitle();
                    $folder_view = $initial_folder_view->renderSimpleView();
                }
            } else {
                $parent_folder = Models_Exam_Bank_Folders::fetchRowByID($PROCESSED["folder_id"]);
                if (isset($parent_folder) && is_object($parent_folder)) {
                    $parent_folder_view = new Views_Exam_Bank_Folder($parent_folder);
                    $title = $parent_folder_view->renderFolderSelectorTitle();
                    $folder_view = $parent_folder_view->renderSimpleView();
                }

                $nav = $parent_folder_view->renderFolderSelectorBackNavigation();
            }

            $question_types = Models_Exam_Lu_Questiontypes::fetchAllRecords();
            $question_type  = Models_Exam_Lu_Questiontypes::fetchRowByID($PROCESSED["questiontype_id"]);

            if ($question_type) {
                $shortname = $question_type->getShortname();

                switch ($shortname) {
                    case "mc_h_m":
                    case "mc_v_m":
                        $custom_grading_allowed = true;
                        break;
                    case "fnb" :
                        break;
                    default :
                        $custom_grading_allowed = false;
                        break;
                }
            }

            ?>
            <link rel="stylesheet" type="text/css" href="<?php echo ENTRADA_URL . "/css/" . $MODULE . "/" . $MODULE . ".css"; ?>" />
            <script type="text/javascript">
                var ENTRADA_URL         = "<?php echo ENTRADA_URL; ?>";
                var submodule_text      = JSON.parse('<?php echo json_encode($SUBMODULE_TEXT); ?>');
                var default_text_labels = JSON.parse('<?php echo json_encode($DEFAULT_TEXT_LABELS); ?>');
                var ajax_in_progress    = false;
                var existing_question   = false;
                var fnb_answers         = {};

                function in_array(needle, list){
                    return list.indexOf(needle) > -1;
                }

                function validate_question() {
                    var selected_question_type = jQuery("#question-type").val();
                    var validated = false;
                    jQuery.ajax({
                        url: API_URL,
                        async: false,
                        data: "method=get-question-type-shortname&question_type_id=" + selected_question_type,
                        type: "POST",
                        success: function(data) {
                            var response = JSON.parse(data);
                            if (response.status == "success") {
                                var shortname = response.data.shortname;
                                var can_have_correct_answer_set = ["mc_v", "mc_h", "mc_v_m", "mc_h_m", "drop_m", "drop_s"];
                                if(in_array(shortname, can_have_correct_answer_set) && correct_answer.length < 1){
                                    var response = confirm("This question has no correct answer. Are you sure you want to save it anyway?");
                                    if(response){
                                        // User wants to save question without answers anyway
                                        validated = true;
                                    }else{
                                        // User doesn't want to save question without answers
                                        validated = false;
                                    }
                                } else if (shortname == "fnb") {
                                    // For fill in the blank question we need to verify if there's the same number of answers to the same
                                    // amount of blank spaces
                                    var question_text = jQuery("#question-text").val();
                                    var answers_obj = JSON.parse(jQuery("#correct_answers_fnb").val());
                                    var blank_spaces = (question_text.match(/_\?_/g) || []).length;
                                    var answer_count = 0;
                                    for (var answer in answers_obj) {
                                        if (answers_obj[answer].length ) {
                                            answer_count += 1;
                                        }
                                    }
                                    if (answer_count == blank_spaces) {
                                        validated = true;
                                    } else {
                                        alert(
                                            "The number of answers should be the same as the number of blank spaces.\n" +
                                            "Number answers: " + answer_count + "\n" +
                                            "Number of blank spaces: " + blank_spaces
                                        );
                                        validated = false;
                                    }
                                } else {
                                    // Question isn't MC or it's MC but have answers defined
                                    validated = true;
                                }
                            }
                        }
                    });
                    return validated;
                }
            </script>
            <?php
            if (isset($PROCESSED["answers_fnb"])) { ?>
                <script>
                    fnb_answers = JSON.parse('<?php echo json_encode($PROCESSED["answers_fnb"]); ?>');
                    if (!jQuery.isEmptyObject(fnb_answers)) {
                        existing_question = true;
                        var temp_values = JSON.stringify(fnb_answers);
                        jQuery(document).ready(function() {
                            jQuery("#correct_answers_fnb").val(temp_values);
                        });
                    }
                </script>
            <?php } ?>

            <script src="<?php echo ENTRADA_URL; ?>/javascript/<?php echo $MODULE; ?>/<?php echo $SUBMODULE; ?>/<?php echo $MODULE; ?>-<?php echo $SUBMODULE; ?>-admin.js"></script>

            <form id="question-exam" action="<?php echo ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?step=2&section=" . $SECTION . ($METHOD == "update" ? "&id=" . $PROCESSED["question_id"] : ""); ?><?php echo isset($group_question_string) ? $group_question_string : ""; ?>" class="form-horizontal" method="POST" onsubmit="return validate_question()">
                <input type="hidden" name="answers" id="answers" value="2" />
                <input type="hidden" name="element_type" value="<?php echo (isset($PROCESSED["element_type"]) ? $PROCESSED["element_type"] : ""); ?>" />
                <input type="hidden" name="id" value="<?php echo (isset($PROCESSED["id"]) ? $PROCESSED["id"] : ""); ?>" />
                <input type="hidden" name="question_id" id="question_id" value="<?php echo (isset($PROCESSED["id"]) ? $PROCESSED["id"] : ""); ?>" />
                <input type="hidden" name="version_id" value="<?php echo (isset($PROCESSED["version_id"]) ? $PROCESSED["version_id"] : ""); ?>" />
                <input type="hidden" name="group_id" value="<?php echo (isset($PROCESSED["group_id"]) ? $PROCESSED["group_id"] : ""); ?>" />
                <input type="hidden" name="folder_id" id="folder_id" value="<?php echo (isset($PROCESSED["folder_id"]) ? $PROCESSED["folder_id"] : ""); ?>" />
                <input type="hidden" name="exam_id" id="exam_id" value="<?php echo (isset($PROCESSED["exam_id"]) ? $PROCESSED["exam_id"] : ""); ?>">
                <input type="hidden" name="correct-answer" id="correct-answer-input" />
                <input type="hidden" name="correct_answers_fnb" id="correct_answers_fnb" />
                <input type="hidden" name="answers_fnb_order" id="answers_fnb_order" />
                <input type="hidden" name="match_stem_correct" id="match_stem_correct" />
                <input type="hidden" name="locked_answers_orders" id="locked-answers-input"/>
                <h2 class="collapsable" title="Question Stem">Question Stem</h2>
                <div id="question-stem">
                    <div class="row-fluid">
                        <div class="span12">
                            <label class="form-required" for="question-text"><?php echo $SUBMODULE_TEXT["exam"]["label_question_text"]; ?></label>
                        </div>
                    </div>
                    <div class="row-fluid">
                        <div class="span12">
                            <textarea id="question-text" name="question_text" class="span11"><?php echo (isset($PROCESSED["question_text"]) ? $PROCESSED["question_text"] : ""); ?></textarea>
                        </div>
                    </div>
                    <div class="control-group">

                        <div class="controls">

                        </div>
                    </div>
                    <div id="fnb-stem-visual" class="hide">
                        <label class="control-label form-required" for="question-text"><?php echo $SUBMODULE_TEXT["exam"]["label_question_text"]; ?></label>
                        <div class="controls">
                            <div id="fnb_editor" class="well">
                                <p>Visual Editor</p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <button id="update-fnb-stem" class="btn btn-primary pull-right hide">FNB Preview</button>
                        <div class="clear"></div>
                    </div>
                </div>
                <h2 title="Question Information Section" class="collapsable<?php echo (defined("EDIT_QUESTION") && EDIT_QUESTION) ? " collapsed" : "";?>">Question Information</h2>
                <div id="question-information-section">
                    <?php
                    if ($question_types) {
                        ?>
                        <div class="control-group" id="question_type_group">
                            <label class="control-label form-required" for="question-type"><?php echo $SUBMODULE_TEXT["exam"]["label_question_type"]; ?></label>
                            <div class="controls">
                                <?php
                                if (!$question_in_use && (defined("ADD_QUESTION") && ADD_QUESTION)) {
                                    ?>
                                    <select id="question-type" name="questiontype_id" class="span11" <?php echo (isset($question_in_use) && $question_in_use ? "disabled=\"disabled\"" : "") ?>>
                                        <?php
                                        foreach ($question_types as $question_type) {
                                            if ($question_type->getID() == $PROCESSED["questiontype_id"]) {
                                                $selected_qt = "selected=\"selected\"";

                                            } else {
                                                $selected_qt = "";
                                            }
                                            ?>
                                            <option value="<?php echo $question_type->getID(); ?>" <?php echo $selected_qt ?>><?php echo $question_type->getName(); ?></option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                <?php
                                } elseif (defined("EDIT_QUESTION") && EDIT_QUESTION) {
                                    $question_type = Models_Exam_Lu_Questiontypes::fetchRowByID($PROCESSED["questiontype_id"]);
                                    if ($question_type) {
                                        echo "<input type=\"text\" value=\"".$question_type->getName()."\" readonly=\"readonly\" />";
                                        echo "<input id=\"question-type\" name=\"questiontype_id\" type=\"hidden\" value=".$PROCESSED["questiontype_id"]." />";
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    <?php
                    }
                    ?>
                    <div class="control-group" id="question_description_group">
                        <label class="control-label form-nrequired" for="question_description"><?php echo $SUBMODULE_TEXT["exam"]["label_question_description"]; ?></label>
                        <div class="controls">
                            <textarea id="question_description" name="question_description" class="expandable span11"><?php echo (isset($PROCESSED["question_description"]) ? $PROCESSED["question_description"] : ""); ?></textarea>
                        </div>
                    </div>
                    <div class="control-group" id="question_rationale_group">
                        <label class="control-label form-nrequired" for="question_rationale"><?php echo $SUBMODULE_TEXT["exam"]["label_rationale"]; ?></label>
                        <div class="controls">
                            <textarea id="question_rationale" name="question_rationale" class="span11"><?php echo (isset($PROCESSED["question_rationale"]) ? $PROCESSED["question_rationale"] : ""); ?></textarea>
                        </div>
                    </div>
                    <div class="control-group" id="question_correct_text_group">
                        <label class="control-label form-nrequired" for="question_correct_text" title="" data-toggle="tooltip" data-original-title="<?=$SUBMODULE_TEXT["edit-question"]["correct_text_tooltip_text"]?>"><?php echo $SUBMODULE_TEXT["exam"]["label_question_correct_text"]; ?> <i class="icon-question-sign"></i></label>
                        <div class="controls">
                            <textarea id="question_correct_text" name="question_correct_text" class="expandable span11"><?php echo (isset($PROCESSED["question_correct_text"]) ? $PROCESSED["question_correct_text"] : ""); ?></textarea>
                        </div>
                    </div>
                    <div class="control-group" id="question_code_group">
                        <label class="control-label" for="question-code"><?php echo $SUBMODULE_TEXT["exam"]["label_question_code"]; ?></label>
                        <div class="controls">
                            <input class="span11" type="text" name="question_code" id="question-code" value="<?php echo (isset($PROCESSED["question_code"]) ? $PROCESSED["question_code"] : ""); ?>"/>
                        </div>
                    </div>
                    <div id="custom_grading" class="<?php echo ($custom_grading_allowed ? "control-group": "control-group hide")?>">
                        <label class="control-label" for="grading_scheme"><?php echo $SUBMODULE_TEXT["exam"]["label_grading_scheme"]; ?></label>
                        <div class="controls">
                            <select name="grading_scheme" id="grading_scheme">
                                <option value="partial" <?php echo (isset($PROCESSED["grading_scheme"]) && $PROCESSED["grading_scheme"] == "partial" ? "selected=\"selected\"" : ""); ?>>Partial</option>
                                <option value="full" <?php echo (isset($PROCESSED["grading_scheme"]) && $PROCESSED["grading_scheme"] == "full" ? "selected=\"selected\"" : ""); ?>>All or none</option>
                                <option value="penalty" <?php echo (isset($PROCESSED["grading_scheme"]) && $PROCESSED["grading_scheme"] == "penalty" ? "selected=\"selected\"" : ""); ?>>Partial with additional penalty</option>
                            </select>
                        </div>
                    </div>

                    <div class="control-group" id="folder_id_group">
                        <label class="control-label form-required" for="folder_id"><?php echo $SUBMODULE_TEXT["folder"]["label_folder_parent_id"]; ?></label>
                        <div class="controls">
                            <div id="selected-parent-folder">
                                <?php echo $folder_view;?>
                                <a href="#parent-folder-modal" data-toggle="modal" class="btn btn-success" id="select_parent_folder_button"><?php echo $translate->_("Select Parent Folder"); ?></a>
                            </div>
                        </div>
                    </div>
                    <?php if (defined("EDIT_QUESTION") && EDIT_QUESTION === true) { ?>
                        <script type="text/javascript">
                            jQuery(function($) {
                                $("#contact-selector").audienceSelector({
                                    "filter"        : "#contact-type",
                                    "target"        : ".author-list",
                                    "content_type"  : "question-author",
                                    "content_style" : "exam",
                                    "delete_icon"   : "fa fa-2x fa-times-circle",
                                    "content_target" : "<?php echo $PROCESSED["version_id"]; ?>",
                                    "api_url"       : "<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-questions" ; ?>",
                                    "delete_attr"   : "data-author-id"
                                });
                            });
                        </script>
                        <div class="control-group exam-authors">
                            <label class="control-label" for="contact-selector"><?php echo $SUBMODULE_TEXT["exam"]["label_question_permissions"]; ?></label>
                            <div class="controls">
                                <input class="span6" type="text" name="contact_select" id="contact-selector" />
                                <select class="span5" name="contact_type" id="contact-type" class="span3">
                                    <?php foreach ($DEFAULT_TEXT_LABELS["contact_types"] as $contact_type => $contact_type_name) { ?>
                                        <option value="<?php echo $contact_type; ?>"><?php echo $contact_type_name; ?></option>
                                    <?php } ?>
                                </select>
                                <?php
                                $type_array     = array("organisation_id", "course_id", "proxy_id");
                                $folder_authors = Models_Exam_Bank_Folder_Authors::fetchAllInheritedByFolderID($PROCESSED["folder_id"]);
                                $authors        = Models_Exam_Question_Authors::fetchAllByVersionIdGroupedByType($PROCESSED["version_id"]);

                                foreach ($type_array as $type) {
                                    echo $html = Views_Exam_Question_Author::renderTypeUL($type, $folder_authors[$type], $authors[$type]);
                                }
                                ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
                <div id="item-section" class="hide">
                    <h2>Item Stem</h2>
                    <div class="row-fluid space-below">
                        <a href="#delete-item-stem-modal" data-toggle="modal" class="btn btn-danger space-right"><i class="icon-minus-sign icon-white"></i> <?php echo $translate->_("Delete"); ?></a>
                        <div class="pull-right">
                            <span><a class="btn btn-success pull-right add-item-stem"><i class="icon-plus-sign icon-white"></i> <?php echo $SUBMODULE_TEXT["buttons"]["add_stem"]; ?></a><span>
                            <div class="clear"></div>
                        </div>
                    </div>
                    <div id="item-stem-table">
                        <?php
                        if (!has_error()) {
                            if (isset($PROCESSED["question_item_stems"]) && is_array($PROCESSED["question_item_stems"])) {
                                foreach ($PROCESSED["question_item_stems"] as $order => $match) {
                                    $match_view = new Views_Exam_Question_Match($match);
                                    echo $match_view->renderMatch(NULL, $shortname);
                                }
                            }
                        }
                        ?>
                    </div>
                </div>
                <div id="answer-section">
                    <h2>Question Answers</h2>
                    <?php
                    if (!$question_in_use) {

                        ?>
                        <div class="row-fluid space-below">
                            <a href="#delete-answers-modal" data-toggle="modal" class="btn btn-danger space-right"><i class="icon-minus-sign icon-white"></i> <?php echo $translate->_("Delete"); ?></a>

                            <div class="pull-right">
                                <span><a href="#" class="btn show-all-details"><i class="icon-eye-open"></i></a></span>
                                <span><a class="btn btn-success pull-right add-answer"><i class="icon-plus-sign icon-white"></i> <?php echo $SUBMODULE_TEXT["buttons"]["add_answer"]; ?></a><span>
                                <div class="clear"></div>
                            </div>
                        </div>
                    <?php
                    }
                    ?>
                    <div id="answer-table">
                        <?php

                        if (isset($PROCESSED["answers"]) && is_array($PROCESSED["answers"])) {
                            foreach ($PROCESSED["answers"] as $key => $answer) {
                                if (isset($answers) && is_array($answers)) {
                                    //get view for answer
                                    $row = $answers[$key - 1];
                                    $row_view = new Views_Exam_Question_Answer($row);
                                    echo $row_view->renderAnswer(NULL, $shortname);
                                }
                            }
                        }
                        ?>
                    </div>
                </div>
                <div class="row-fluid">
                    <div class="span12">
                        <div id="objectives_1_list" class="hidden">
                            <?php
                            $objective_ids_string = "";
                            $obj_displayed = array();
                            if (isset($PROCESSED["objective_ids"]) && @count($PROCESSED["objective_ids"])) {
                                foreach ($PROCESSED["objective_ids"] as $objective_id) {
                                    if (!in_array($objective_id, $obj_displayed)) {
                                        $obj_displayed[] = $objective_id;
                                        $objective_ids_string .= ($objective_ids_string ? "," : "").((int)$objective_id);
                                        ?>
                                        <input type="hidden" class="objective_ids_1" id="objective_ids_1_<?php echo $objective_id; ?>" name="objective_ids_1[]" value="<?php echo $objective_id; ?>" />
                                        <?php
                                    }
                                }
                            }
                            ?>
                            <input type="hidden" name="objective_ids_string_1" id="objective_ids_string_1" value="<?php echo ($objective_ids_string ? $objective_ids_string : ""); ?>" />
                            <input type="hidden" id="qrow" value="1" />
                        </div>
                        <a href="#objective-modal" data-toggle="modal" class="btn btn-success pull-right space-above"><i class="icon-plus-sign icon-white"></i> <?php echo $translate->_("Add Curriculum Tag"); ?></a>
                        <?php
                        $question_identifier = 1;
                        echo Views_Exam_Question_Objective::renderTaggedObjectivesList($objective_ids_string);
                        ?>
                        <div id="objective-modal" class="modal hide fade">
                            <div class="modal-header"><h1><?php echo $SUBMODULE_TEXT["exam"]["label_question_objectives"]; ?></h1></div>
                            <div class="modal-body">
                                <?php
                                echo Views_Exam_Question_Objective::renderObjectiveControls(($question_version && $question_version->getOrganisationID() ? $question_version->getOrganisationID() : $ENTRADA_USER->getActiveOrganisation()), 1, $PROCESSED["objective_ids"]);
                                ?>
                            </div>
                            <div class="modal-footer">
                                <div class="row-fluid">
                                    <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $DEFAULT_TEXT_LABELS["btn_done"]; ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row-fluid space-above">
                    <?php
                    $url = ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE;
                    if (isset($PROCESSED["element_type"]) && $PROCESSED["element_type"] == "group" && isset($PROCESSED["group_id"]) && $PROCESSED["group_id"]) {
                        $url = ENTRADA_URL."/admin/" . $MODULE . "/groups?section=edit-group&group_id=".$PROCESSED["group_id"];
                    }
                    ?>
                    <a href="<?php echo $url; ?>" class="btn btn-default"><?php echo $DEFAULT_TEXT_LABELS["btn_back"]; ?></a>
                    <input type="submit" class="btn btn-primary pull-right" id="btn-save" value="<?php echo $DEFAULT_TEXT_LABELS["btn_save"]; ?>" />
                </div>
                <div id="parent-folder-modal" class="modal hide fade">
                    <div class="modal-header">
                        <h3>Select a parent folder</h3>
                    </div>
                    <div class="modal-body">
                        <div class="qbf-selector">
                            <div id="qbf-title">
                                <span class="qbf-title">
                                    <?php echo $title;?>
                                </span>
                            </div>
                            <div id="qbf-nav">
                                <?php echo $nav;?>
                            </div>
                            <div id="qbf-folder-<?php echo $PROCESSED["folder_id"];?>" class="qbf-folder active">
                                <table>
                                    <?php
                                    if (isset($initial_folders) && is_array($initial_folders) && !empty($initial_folders)) {
                                        // Provides restrictions for limiting folders students can access
                                        if ($restrict_folder_array_children && is_array($restrict_folder_array_children) && !empty($restrict_folder_array_children)) {
                                            foreach ($initial_folders as $folder) {
                                                if ($folder && is_object($folder)) {
                                                    if (in_array($folder->getID(), $restrict_folder_array_children)) {
                                                        $access_folder_allowed_array[] = $folder;
                                                    }
                                                }
                                            }
                                        } else {
                                            $access_folder_allowed_array = $initial_folders;
                                        }

                                        if ($PROCESSED["folder_id"] == 0) {
                                            echo $initial_folder_view->renderFolderSelectorRow();
                                        }

                                        if ($access_folder_allowed_array && is_array($access_folder_allowed_array)) {
                                            foreach ($access_folder_allowed_array as $folder) {
                                                if (is_object($folder)) {
                                                    if ($folder->getID() == $PROCESSED["folder_id"]) {
                                                        $selected = true;
                                                    } else {
                                                        $selected = false;
                                                    }
                                                    $folder_view = new Views_Exam_Bank_Folder($folder);
                                                    echo $folder_view->renderFolderSelectorRow($selected, true);
                                                }
                                            }
                                        }
                                    } else {
                                        //no folder create yet so just show the index
                                        if ($PROCESSED["folder_id"] == 0) {
                                            echo $initial_folder_view->renderFolderSelectorRow();
                                        }
                                    }
                                    ?>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div id="qpf-confirm">
                            <button class="btn btn-default pull-left" id="cancel-folder-move"><?php echo $DEFAULT_TEXT_LABELS["btn_cancel"]; ?></button>
                            <button class="btn btn-primary pull-right" id="confirm-folder-move" data-type="question"><?php echo $DEFAULT_TEXT_LABELS["btn_done"]; ?></button>
                        </div>
                    </div>
                </div>
                <div id="delete-answers-modal" class="modal hide fade">
                    <form id="delete-answers-modal-question" class="exam-horizontal" action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-questions"; ?>" method="POST" style="margin:0px;">
                        <input type="hidden" name="step" value="2" />
                        <div class="modal-header"><h1><?php echo $SUBMODULE_TEXT["answers"]["title_modal_delete_answers"]; ?></h1></div>
                        <div class="modal-body">
                            <div id="msg-answer-remove" class="hide">
                                <p><?php echo $SUBMODULE_TEXT["answers"]["text_modal_question_versions_used_already"] ?></p>
                            </div>
                            <div id="no-answers-selected" class="hide">
                                <p><?php echo $SUBMODULE_TEXT["answers"]["text_modal_no_answers_selected"] ?></p>
                            </div>
                            <div id="answers-selected" class="hide">
                                <p><?php echo $SUBMODULE_TEXT["answers"]["text_modal_delete_answers"] ?></p>
                                <div id="delete-answers-container"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="row-fluid">
                                <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $DEFAULT_TEXT_LABELS["btn_cancel"]; ?></a>
                                <input id="delete-answers-modal-delete" type="submit" class="btn btn-primary" value="<?php echo $DEFAULT_TEXT_LABELS["btn_delete"]; ?>" />
                            </div>
                        </div>
                    </form>
                </div>

                <div id="delete-item-stem-modal" class="modal hide fade">
                    <form id="delete-item-stem-modal-question" class="exam-horizontal" action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-questions"; ?>" method="POST" style="margin:0px;">
                        <input type="hidden" name="step" value="2" />
                        <div class="modal-header"><h1><?php echo $SUBMODULE_TEXT["answers"]["title_modal_delete_answers"]; ?></h1></div>
                        <div class="modal-body">
                            <div id="msg-match-remove" class="hide">
                                <p><?php echo $SUBMODULE_TEXT["answers"]["text_modal_question_versions_used_already"] ?></p>
                            </div>
                            <div id="no-match-selected" class="hide">
                                <p><?php echo $SUBMODULE_TEXT["answers"]["text_modal_no_answers_selected"] ?></p>
                            </div>
                            <div id="match-selected" class="hide">
                                <p><?php echo $SUBMODULE_TEXT["answers"]["text_modal_delete_answers"] ?></p>
                                <div id="delete-match-container"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="row-fluid">
                                <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $DEFAULT_TEXT_LABELS["btn_cancel"]; ?></a>
                                <input id="delete-match-modal-delete" type="submit" class="btn btn-primary" value="<?php echo $DEFAULT_TEXT_LABELS["btn_delete"]; ?>" />
                            </div>
                        </div>
                    </form>
                </div>

                <div id="add-correct-answers-modal" class="modal hide fade">
                    <div class="modal-header"><h1><?php echo $SUBMODULE_TEXT["answers"]["fnb"]["correct_answers"]; ?></h1></div>
                    <div class="modal-body">
                        <p><?php echo $SUBMODULE_TEXT["answers"]["fnb"]["add-correct-text"]; ?><span id="add-correct-text"></span></p>
                        <label for="add-correct-answer"><?php echo $SUBMODULE_TEXT["answers"]["fnb"]["add_correct"]; ?>: </label>
                        <input id="add-correct-answer" />
                    </div>
                    <div class="modal-footer">
                        <div class="row-fluid">
                            <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $DEFAULT_TEXT_LABELS["btn_cancel"]; ?></a>
                            <input id="correct-answers-modal-add-1" type="submit" class="btn btn-primary" value="<?php echo $SUBMODULE_TEXT["answers"]["fnb"]["add_correct_close"]; ?>" />
                            <input id="correct-answers-modal-add-more" type="submit" class="btn btn-primary" value="<?php echo $SUBMODULE_TEXT["answers"]["fnb"]["add_more"]; ?>" />
                        </div>
                    </div>
                </div>

            </form>

            <script>
                jQuery(document).ready(function () {
                    jQuery('#question-exam').submit(function() {
                        // Gets the folder ID.
                        var folder_id = jQuery('#folder_id').val();

                        // Check to see if the user is trying to save the question to the index folder.
                        if (folder_id == 0) {
                            alert('No questions are allowed in the Index Folder, please provide another Folder.');
                            jQuery('#parent-folder-modal').modal('show');
                            return false;
                        }
                    });
                });
            </script>
            <?php
            break;
    }
}
