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

// This might take a while, so we set the maximum execution time to 5 minutes
set_time_limit(300);

if(!defined("PARENT_INCLUDED")) {
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
    
    $sub_navigation = Views_Exam_Exam::GetQuestionsSubnavigation("migrateresponses");
    echo $sub_navigation;
    ?>
    <h1><?php echo $translate->_("Migrate ExamSoft Responses"); ?></h1>
    <?php
    // Error checking
    if (2 == $STEP) {
        $db->StartTrans();
        $submissions = array();
        if (!isset($_FILES["responses"])) {
            add_error($translate->_("You must choose a responses file."));
        } else if ($_FILES["questions"]["error"]) {
            add_error($translate->_("There was an error uploading the provided responses file."));
        } else {
            ini_set("auto_detect_line_endings", true);
            $file_handle = fopen($_FILES["responses"]["tmp_name"], "r");
            if ($file_handle) {
                $headings = fgetcsv($file_handle);
                $answer_key = fgetcsv($file_handle);
                $item_ids = fgetcsv($file_handle);
                if (!$headings || !$answer_key || !$item_ids || "Item ID / Rev" !== $item_ids[0]) {
                    add_error($translate->_("The responses file is not in the correct format."));
                } else {
                    if ("" === $item_ids[count($item_ids) - 1]) {
                        $examsoft_exam_id = (int)$item_ids[count($item_ids) - 2];
                        $final_answer_index = count($answer_key) - 3;
                    } else {
                        $examsoft_exam_id = (int)$item_ids[count($item_ids) - 1];
                        $final_answer_index = count($answer_key) - 2;
                    }
                    $exam = Models_Exam_Exam::fetchRowByExamsoftExamID($examsoft_exam_id);
                    if ($exam) {
                        $posts = Models_Exam_Post::fetchAllByExamIDNoPreview($exam->getID());
                        if ($posts) {
                            $post = $posts[0];
                        }
                    }
                    if (!$exam) {
                        add_error($translate->_("No exam found that matches these responses."));
                    } else if (!$post) {
                        add_error($translate->_("No exam post found that matches these responses."));
                    } else {
                        $exam_elements = Models_Exam_Exam_Element::fetchAllByExamIDElementType($exam->getID());
                        // Add scoring adjustments as needed
                        for ($i = 3; $i < $final_answer_index; $i++) {
                            $col_num = $i + 1;
                            $answer = $answer_key[$i];
                            // Ignore non multiple choice columns
                            if (1 !== strlen($answer)) {
                                continue;
                            }
                            // Get the relevant exam element
                            $examsoft_question_id_and_version = explode("/", str_replace(" ", "", $item_ids[$i]));
                            $examsoft_question_id = $examsoft_question_id_and_version[0];
                            $exam_element = null;
                            foreach ($exam_elements as $elem) {
                                $elem_examsoft_id_and_version = explode("/", $elem->getQuestionVersion()->getExamsoftID());
                                $elem_examsoft_id = $elem_examsoft_id_and_version[0];
                                if ($examsoft_question_id === $elem_examsoft_id) {
                                    $exam_element = $elem;
                                    break;
                                }
                            }
                            if (!$exam_element) {
                                add_notice(sprintf($translate->_("Could not find exam element for column %d."), $col_num));
                                continue;
                            }
                            // Normalize T/F to A/B for true/false questions
                            $exam_question_answers = $exam_element->getQuestionVersion()->getQuestionAnswers();
                            if (2 === count($exam_question_answers)) {
                                if ("T" === $answer) {
                                    $answer = "A";
                                } else if ("F" === $answer) {
                                    $answer = "B";
                                }
                            }
                            $correct_letter = $answer;
                            $correct_order = ord(strtolower($correct_letter)) - ord("a") + 1;
                            foreach ($exam_question_answers as $question_answer) {
                                $adj_array = array(
                                    "exam_id" => $exam->getID(),
                                    "exam_element_id" => $exam_element->getID(),
                                    "post_id" => $post->getID(),
                                    "value" => $question_answer->getID(),
                                    "created_date" => time(),
                                    "created_by" => $ENTRADA_USER->getProxyID()
                                );
                                $adj = null;
                                if ($question_answer->getCorrect() && $question_answer->getOrder() != $correct_order) {
                                    $adj = new Models_Exam_Adjustment(array_merge($adj_array, array("type" => "incorrect")));
                                } else if (!$question_answer->getCorrect() && $question_answer->getOrder() == $correct_order) {
                                    $adj = new Models_Exam_Adjustment(array_merge($adj_array, array("type" => "correct")));
                                }
                                if ($adj && !$adj->insert()) {
                                    add_error(sprintf($translate->_("Error inserting grading adjustment in column %d.")), $i);
                                    $db->FailTrans();
                                    break 2;
                                }
                            }
                        }
                        // Import each student row
                        $row_num = 3;
                        while (($row = fgetcsv($file_handle)) && null !== $row[0]) {
                            $row_num++;
                            $student_id = $row[0];
                            if (!$student_id) {
                                add_notice(sprintf($translate->_("No student ID found for row %d."), $row_num));
                                continue;
                            }
                            $student = Models_User::fetchRowByNumber($student_id);
                            if (!$student) {
                                add_notice(sprintf($translate->_("Could not find student with number: %d."), $student_id));
                                continue;
                            }
                            $student_name = $student->getFullName();
                            $progress_record = new Models_Exam_Progress(array(
                                "post_id" => $post->getID(),
                                "exam_id" => $exam->getID(),
                                "proxy_id" => $student->getProxyID(),
                                "progress_value" => "submitted",
                                "menu_open" => 0,
                                "created_date" => time(),
                                "created_by" => $student->getProxyID(),
                                "updated_date" => time(),
                                "updated_by" => $student->getProxyID(),
                                "started_date" => time()
                            ));
                            if (!$progress_record->insert()) {
                                add_error($translate->_("Failed to insert exam progress record."));
                                $db->FailTrans();
                                break;
                            }
                            $submissions[] = $progress_record;
                            for ($i = 3; $i < $final_answer_index; $i++) {
                                $col_num = $i + 1;
                                $examsoft_question_id = str_replace(" ", "", $item_ids[$i]);
                                $exam_element = null;
                                foreach ($exam_elements as $elem) {
                                    if ($examsoft_question_id === $elem->getQuestionVersion()->getExamsoftID()) {
                                        $exam_element = $elem;
                                        break;
                                    }
                                }
                                if (!$exam_element) {
                                    add_notice(sprintf($translate->_("Could not find exam element for column %d."), $col_num));
                                    continue;
                                }
                                $exam_question_answers = $exam_element->getQuestionVersion()->getQuestionAnswers();
                                $answer = $answer_key[$i];
                                $is_essay = "N/A - Essay" === $answer;
                                // Fill in the blank will always be followed by a column headed "Q# Pts" where the original
                                // column heading is "Q#" (here # represents an integer)
                                $is_fitb = !$is_essay && 1 !== strlen($answer) && $headings[$i]." Pts" === $headings[$i + 1];
                                $is_true_false = 2 === count($exam_question_answers) && ("T" === $answer || "F" === $answer);
                                $response_value = $row[$i];
                                // Normalize response value from T/F to A/B
                                if ($is_true_false) {
                                    if ("T" === strtoupper($response_value)) {
                                        $response_value = "A";
                                    } else if ("F" === strtoupper($response_value)) {
                                        $response_value = "B";
                                    }
                                }
                                $response_record = new Models_Exam_Progress_Responses(array(
                                    "exam_progress_id" => $progress_record->getID(),
                                    "exam_id" => $exam->getID(),
                                    "post_id" => $post->getID(),
                                    "proxy_id" => $student->getProxyID(),
                                    "exam_element_id" => $exam_element->getID(),
                                    "epr_order" => $i - 2,
                                    "question_count" => $i - 2,
                                    "question_type" => $exam_element->getQuestionVersion()->getQuestionType()->getShortname(),
                                    "view_date" => time(),
                                    "created_date" => time(),
                                    "created_by" => $ENTRADA_USER->getProxyID(),
                                    "updated_date" => time(),
                                    "updated_by" => $ENTRADA_USER->getProxyID()
                                ));
                                if ($is_essay) {
                                    if ("" !== $row[$i + 1]) {
                                        $response_record->setGradedBy($ENTRADA_USER->getProxyID());
                                        $response_record->setGradedDate(time());
                                        $response_record->setScore($row[$i + 1]);
                                    }
                                }
                                if (!$response_record->insert()) {
                                    add_error($translate->_("Failed to insert exam progress response record."));
                                    $db->FailTrans();
                                    break 2;
                                }
                                if ($is_essay) {
                                    $response_answer_record = new Models_Exam_Progress_Response_Answers(array(
                                        "epr_id" => $response_record->getID(),
                                        "response_value" => $response_value,
                                        "created_date" => time(),
                                        "created_by" => $ENTRADA_USER->getProxyID(),
                                        "updated_date" => time(),
                                        "updated_by" => $ENTRADA_USER->getProxyID()
                                    ));
                                    if (!$response_answer_record->insert()) {
                                        add_error(sprintf($translate->_("Failed to insert response answer record for column %d for student %s."), $col_num, $student_name));
                                        $db->FailTrans();
                                        break 2;
                                    }
                                } else if ($is_fitb) {
                                    usort($exam_question_answers, function($a, $b) { return $a->getOrder() - $b->getOrder(); });
                                    $all_responses = array_filter(explode("~", $response_value));
                                    if (count($all_responses) !== count($exam_question_answers)) {
                                        add_notice(sprintf($translate->_("User responses do not match number of blanks for column %d for student %s."), $col_num, $student_name));
                                    }
                                    for ($j = 0; $j < min(count($all_responses), count($exam_question_answers)); $j++) {
                                        $answer = $exam_question_answers[$j];
                                        $response = $all_responses[$j];
                                        $response_answer_record = new Models_Exam_Progress_Response_Answers(array(
                                            "epr_id" => $response_record->getID(),
                                            "eqa_id" => $answer->getID(),
                                            "response_element_order" => $answer->getOrder(),
                                            "response_value" => $response,
                                            "created_date" => time(),
                                            "created_by" => $ENTRADA_USER->getProxyID(),
                                            "updated_date" => time(),
                                            "updated_by" => $ENTRADA_USER->getProxyID()
                                        ));
                                        if (!$response_answer_record->insert()) {
                                            add_error(sprintf($translate->_("Failed to insert response answer record in column %d for student %s."), $col_num, $student_name));
                                            $db->FailTrans();
                                            break 3;
                                        }
                                    }
                                } else {
                                    $all_response_values = explode(",", $response_value);
                                    foreach ($all_response_values as $one_response_value) {
                                        $target_order = ord(strtolower($one_response_value)) - ord("a") + 1;
                                        $target_answer = null;
                                        foreach ($exam_question_answers as $answer) {
                                            if ($answer->getOrder() == $target_order) {
                                                $target_answer = $answer;
                                                break;
                                            }
                                        }
                                        if (!$target_answer) {
                                            add_notice(sprintf($translate->_("Failed to find question answer row for column %d for student %s."), $col_num, $student_name));
                                        } else {
                                            $response_answer_record = new Models_Exam_Progress_Response_Answers(array(
                                                "epr_id" => $response_record->getID(),
                                                "eqa_id" => $target_answer->getID(),
                                                "response_element_order" => $target_answer->getOrder(),
                                                "response_element_letter" => strtoupper($one_response_value),
                                                "response_value" => 1,
                                                "created_date" => time(),
                                                "created_by" => $ENTRADA_USER->getProxyID(),
                                                "updated_date" => time(),
                                                "updated_by" => $ENTRADA_USER->getProxyID()
                                            ));
                                            if (!$response_answer_record->insert()) {
                                                add_error(sprintf($translate->_("Failed to insert response answer record.")));
                                                $db->FailTrans();
                                                break 3;
                                            }
                                        }
                                    }
                                }
                                // Skip over points row for essay and fitb
                                if ($is_essay || $is_fitb) {
                                    $i++;
                                }
                            }
                        }
                        // Grade everything we just inserted
                        foreach ($submissions as $submission) {
                            $submission_view = new Views_Exam_Progress($submission);
                            if (!$submission_view->gradeExam()) {
                                add_error($translate->_("Error while grading exam submissions. Please try again later."));
                                $db->FailTrans();
                                break;
                            }
                        }
                    }
                }
                fclose($file_handle);
            } else {
                add_error($translate->_("Error opening responses file for reading."));
            }
        }
        if (has_error()) {
            $STEP = 1;
            $db->FailTrans();
        } else {
            add_success(sprintf($translate->_("Successfully imported responses from %d students."), count($submissions)));
        }
        $db->CompleteTrans();
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
    ?>
    <form class="form form-horizontal" method="post" enctype="multipart/form-data">
        <input type="hidden" name="step" value="2" />
        <div class="control-group">
            <div class="control-label">
                <label for="responses" class="form-required"><?php echo $translate->_("Responses File (in CSV format)"); ?>:</label>
            </div>
            <div class="controls">
                <input type="file" name="responses" id="responses" />
                <script type="text/javascript">
                    jQuery("#responses").filestyle({
                        icon: true,
                        buttonText: " Find File"
                    });
                </script>
            </div>
        </div>
        <input type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("Import Responses"); ?>" />
    </form>
    <?php
}