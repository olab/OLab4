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
} elseif (!$ENTRADA_ACL->amIAllowed("examdashboard", "read", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));
    echo display_error();
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    ob_clear_open_buffers();
    
    // Make sure we have permission to grade this exam progress response
    $exam_progress_response_id = isset($_POST["exam_progress_response_id"]) ? (int)$_POST["exam_progress_response_id"] : 0;
    $response = Models_Exam_Progress_Responses::fetchRowByID($exam_progress_response_id);
    if (!Models_Exam_Grader::isExamPostGradableBy($exam_post->getID(), $ENTRADA_USER->getActiveId())) {
        echo json_encode(array("messageType" => "error", "message" => "You do not have permission to grade this exam post."));
        exit;
    } else if (!Models_Exam_Grader::isStudentGradableBy($response->getProxyId(), $exam_post->getID(), $ENTRADA_USER->getActiveId())) {
        echo json_encode(array("messageType" => "error", "message" => "You do not have permission to grade this student for this exam post."));
        exit;
    }
    $exam_element = Models_Exam_Exam_Element::fetchRowByID($response->getExamElementID());
    
    $error = false;
    $error_msg = "";
    $comments = isset($_POST["comments"]) ? $_POST["comments"] : "";
    $correct_answers = array();
    
    // Calculate/validate the score based on the question type
    switch ($response->getQuestionType()) {
        case "fnb":
            if (!$ENTRADA_ACL->amIAllowed("examgradefnb", "update", false)) {
                echo json_encode(array("messageType" => "error", "message" => "You do not have permission to grade fill in the blank type questions."));
                exit;
            }
            $graded_blanks = isset($_POST["correct"]) ? $_POST["correct"] : array();
            foreach ($graded_blanks as $blank) {
                $answer = Models_Exam_Progress_Response_Answers::fetchRowByID($blank["epr_answer_id"]);
                if (!$answer) {
                    continue;
                }
                $qanswer_id = $answer->getAnswerElementID();
                $all_fnb_text = Models_Exam_Question_Fnb_Text::fetchAllByQuestionAnswerID($qanswer_id);
                $fnb_correct_text = array_map(function($blank) { return $blank->getText(); }, $all_fnb_text);
                $response_value = $answer->getResponseValue();
                $was_correct = in_array($response_value, $fnb_correct_text);
                $is_correct = "true" === $blank["correct"];
                if ($was_correct === $is_correct) {
                    continue;
                }
                if ($is_correct) {
                    // Add new answer choice
                    $new_fnb_text = new Models_Exam_Question_Fnb_Text(array(
                        "qanswer_id" => $qanswer_id,
                        "text" => $response_value,
                        "updated_date" => time(),
                        "updated_by" => $ENTRADA_USER->getProxyID()
                    ));
                    $new_fnb_text->insert();
                } else {
                    // Remove answer choice
                    $existing_fnb_text = Models_Exam_Question_Fnb_Text::fetchRowByQuestionAnswerIDText($qanswer_id, $response_value);
                    $existing_fnb_text->setDeletedDate(time());
                    $existing_fnb_text->update();
                }
                // Update the list of correct answers
                $all_fnb_text = Models_Exam_Question_Fnb_Text::fetchAllByQuestionAnswerID($qanswer_id);
                $fnb_correct_text = array_map(function($blank) { return $blank->getText(); }, $all_fnb_text);
                $correct_answers[] = array("qanswer_id" => $qanswer_id, "answers" => $fnb_correct_text);
            }
            // Update the score (for everyone)
            $post_id = $response->getPostID();
            $progress_attempts = Models_Exam_Progress::fetchAllByPostIDProgressValue($post_id, "submitted");
            foreach ($progress_attempts as $progress) {
                $progress_view = new Views_Exam_Progress($progress);
                $progress_view->gradeExam();
            }
            // Get the current user's score for the current question
            $score = Models_Exam_Progress_Responses::fetchRowByID($answer->getExamProgressResponseID())->getScore();
            break;
        case "short":
        case "essay":
        default:
            $score = isset($_POST["score"]) ? trim($_POST["score"]) : "";
            if ("" === $score) {
                $error = true;
                $error_msg = "No score provided. Feedback has been saved.";
            } else if (!is_numeric($score)) {
                $error = true;
                $error_msg = "You must provide a numeric score. Feedback has been saved.";
            } else if ($score < 0) {
                $error = true;
                $error_msg = "Score cannot be less than zero. Feedback has been saved.";
            } else if ((int)$exam_element->getAdjustedPoints() < $score) {
                $error = true;
                $error_msg = "Score cannot be greater than the maximum points allowed. Feedback has been saved.";
            }
            break;
    }
    
    // Update the response
    $response->setGraderComments($comments);
    if (!$error) {
        $response->setScore($score);
        $response->setGradedBy($ENTRADA_USER->getActiveId());
        $response->setGradedDate(time());
    }
    $progress = Models_Exam_Progress::fetchRowByID($response->getExamProgressID());
    $db->StartTrans();
    if (!$response->update() || !$progress->updateScore()) {
        $error = true;
        $error_msg = "There was an error grading this question. Please try again later.";
        $db->FailTrans();
    }
    $db->CompleteTrans();
    
    if ($error) {
        echo json_encode(array("messageType" => "error", "message" => "Question ".$response->getQuestionCount().": $error_msg"));
    } else {
        $new_question_score = Models_Exam_Progress_Responses::fetchRowByID($response->getID())->getScore();
        echo json_encode(array(
            "messageType" => "success",
            "message" => "Saved question ".$response->getQuestionCount().".",
            "questionScore" => $new_question_score,
            "examScore" => $progress->getExamPoints()." / ".$progress->getExamValue()." (".$progress->getExamScore()."%)",
            "correct" => $correct_answers
        ));
    }
    exit;
}
