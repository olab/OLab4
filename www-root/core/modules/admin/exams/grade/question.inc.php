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
 * File for grading exams by question.
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Robert Fotino <robert.fotino@gmail.com>
 * @copyright Copyright 2016 Regents of The University of California. All Rights Reserved.
 *
 */
// Get the exam element and the post
if (isset($_GET["exam_element_id"])) {
    $EXAM_ELEMENT_ID = (int)$_GET["exam_element_id"];
    $exam_element = Models_Exam_Exam_Element::fetchRowByID($EXAM_ELEMENT_ID);
}
if (isset($_GET["post_id"])) {
    $POST_ID = (int)$_GET["post_id"];
    $exam_post = Models_Exam_Post::fetchRowByExamIDNoPreview($POST_ID);
}
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_GRADE_EXAMS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("examdashboard", "read", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));
    echo display_error();
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} elseif ("fnb" === $exam_element->getQuestionVersion()->getQuestionType()->getShortname() && !$ENTRADA_ACL->amIAllowed("examgradefnb", "update", false)) {
    add_error($translate->_("You do not have permission to grade fill in the blank type questions."));
    echo display_error();
} else {
    // Check for form submission and make sure the exam element and exam post are set.
    if ((isset($_POST["mark_incorrect"]) || isset($_POST["mark_correct"])) && $exam_element && $exam_post) {
        $db->StartTrans();
        if (isset($_POST["mark_incorrect"])) {
            $mark_incorrect = $_POST["mark_incorrect"];
            foreach ($mark_incorrect as $fnb_text_id => $answer_text) {
                $blank = Models_Exam_Question_Fnb_Text::fetchRowByID($fnb_text_id);
                $blank->setDeletedDate(time());
                if (!$blank->update()) {
                    add_error(sprintf($translate->_("Unable to mark an answer \"%s\" as incorrect. Please try again later."), html_encode($answer_text)));
                    $db->FailTrans();
                    break;
                }
            }
        }
        if (isset($_POST["mark_correct"])) {
            $mark_correct = $_POST["mark_correct"];
            foreach ($mark_correct as $qanswer_id => $answers) {
                foreach ($answers as $answer_text) {
                    // Don't add duplicates - if the Fnb_Text for this qanswer_id and answer_text already
                    // exists, do nothing.
                    if (Models_Exam_Question_Fnb_Text::fetchRowByQuestionAnswerIDText($qanswer_id, $answer_text)) {
                        continue;
                    }
                    // Not a duplicate, insert new Fnb_Text
                    $blank = new Models_Exam_Question_Fnb_Text(array(
                        "qanswer_id" => $qanswer_id,
                        "text" => $answer_text,
                        "updated_date" => time(),
                        "updated_by" => $ENTRADA_USER->getProxyID()
                    ));
                    if (!$blank->insert()) {
                        add_error(sprintf($translate->_("Unable to mark an answer \"%s\" as correct. Please try again later."), html_encode($answer_text)));
                        $db->FailTrans();
                        break 2;
                    }
                }
            }
        }
        // Do a regrade (for everyone)
        $progress_attempts = Models_Exam_Progress::fetchAllByPostIDProgressValue($exam_post->getID(), "submitted");
        foreach ($progress_attempts as $progress) {
            $progress_view = new Views_Exam_Progress($progress);
            if (!$progress_view->gradeExam()) {
                add_error($translate->_("Error while regrading exam submissions. Please try again later."));
                $db->FailTrans();
                break;
            }
            // Mark response as graded for this FNB question
            $response = Models_Exam_Progress_Responses::fetchRowByProgressIDElementID($progress->getID(), $exam_element->getID());
            if ($response) {
                $response->setGradedBy($ENTRADA_USER->getProxyID());
                $response->setGradedDate(time());
                if (!$response->update()) {
                    add_error($translate->_("Error while marking a submitted response as graded. Please try again later."));
                    $db->FailTrans();
                    break;
                }
            }
        }
        $db->CompleteTrans();
    }
    
    // If no student is selected, get the first student.
    $submissions = Models_Exam_Grader::fetchGradableSubmissionsForPost($ENTRADA_USER->getActiveID(), $exam_post->getID());
    if (isset($_GET["progress_id"])) {
        $EXAM_PROGRESS_ID = (int)$_GET["progress_id"];
        $exam_progress = Models_Exam_Progress::fetchRowByID($EXAM_PROGRESS_ID);
    } else if (isset($exam_post) && $exam_post) {
        if ($submissions && 1 <= count($submissions)) {
            $exam_progress = $submissions[0];
        }
    }
    
    // Get the response from the current student
    if (isset($exam_progress) && $exam_progress && isset($exam_element) && $exam_element) {
        $current_response = Models_Exam_Progress_Responses::fetchRowByProgressIDElementID($exam_progress->getID(), $exam_element->getID());
        if ($current_response) {
            $grading_student = User::fetchRowByID($current_response->getProxyID());
        }
    }
    
    if (!isset($exam_element) || !$exam_element) {
        $ERROR++;
        $ERRORSTR[] = "You must provide a valid exam element ID.";
        echo display_error();
    } else if (!isset($current_response) || !$current_response || !isset($grading_student) || !$grading_student) {
        $ERROR++;
        $ERRORSTR[] = "Could not find a student's response to grade.";
        echo display_error();
    } else {
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/grade?".replace_query(), "title" => "Question #".($exam_element->getOrder() + 1));
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/jquery/jquery.growl.css?release=".html_encode(APPLICATION_VERSION) . "\" />";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".ENTRADA_URL."/css/exams/grading.css?release=".html_encode(APPLICATION_VERSION)."\" />";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/exams/exams-public-attempt.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" .  ENTRADA_URL . "/javascript/jquery.growl.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/exams/grading.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
        
        // Get the question type
        $question_type = $exam_element->getQuestionVersion()->getQuestionType()->getShortname();
        
        if ("fnb" !== $question_type) {
            $grading_sidebar = new Views_Exam_Grader_Sidebar($current_response, "by-question");
            new_sidebar_item_no_header($grading_sidebar->render(), "exam-grading-sidebar", 10);
            ?>
            <h1><?php echo sprintf($translate->_("Grading %s's Submission for %s"), $grading_student->getFullname(), $exam_post->getTitle()); ?></h1>
            <p>
                <strong><?php echo $translate->_("Submitted on:"); ?></strong> <?php echo date(DEFAULT_DATETIME_FORMAT, $exam_progress->getSubmissionDate()); ?><br />
                <strong><?php echo $translate->_("Attempt:"); ?></strong> <?php echo Models_Exam_Progress::fetchAttemptNumber($exam_progress); ?><br />
                <strong><?php echo $translate->_("Overall Score:"); ?></strong> <span id="student_overall_score"><?php echo $exam_progress->getExamPoints()." / ".$exam_progress->getExamValue()." (".$exam_progress->getExamScore()."%)"; ?></span>
            </p>
            <?php
        } else {
            ?>
            <h1><?php echo sprintf($translate->_("Grading Fill in the Blank Question for %s"), $exam_post->getTitle()); ?></h1>
            <?php
        }
        
        if (has_error()) {
            echo display_error();
        } else if (has_notice()) {
            echo display_notice();
        } else if (has_success()) {
            echo display_success();
        }
        
        // Show the UI for grading the given question
        switch ($current_response->getQuestionType()) {
            case "fnb":
                $view = new Views_Exam_Grader_FnbByQuestion($exam_element);
                break;
            case "short":
            case "essay":
            default:
                $view = new Views_Exam_Grader_Question($current_response);
                break;
        }
        echo $view->render();

        if ("fnb" !== $question_type) {
            echo "<div id=\"control-bar\">\n";
            echo "<div class=\"row-fluid container\">\n";
            echo "<div class=\"span3\"></div>\n";
            echo "<div class=\"span9\">\n";
            echo "<div style=\"text-align:center\">\n";
            echo "<div class=\"btn-group\">\n";
            // If we're not at the first student, show the "First" and "Previous" buttons. Otherwise show disabled buttons.
            if (count($submissions) > 1 && $exam_progress->getID() !== $submissions[0]->getID()) {
                $first_student_url = ENTRADA_URL."/admin/exams/grade?".replace_query(array("progress_id" => $submissions[0]->getID()));
                $prev_student_url = $first_student_url;
                for ($i = 1; $i < count($submissions); $i++) {
                    if ($submissions[$i]->getID() === $exam_progress->getID()) {
                        $prev_student_url = ENTRADA_URL."/admin/exams/grade?".replace_query(array("progress_id" => $submissions[$i-1]->getID()));
                        break;
                    }
                }
                echo "<a class=\"btn\" href=\"$first_student_url\"><i class=\"fa fa-fast-backward\"></i> First Student</a>\n";
                echo "<a class=\"btn\" href=\"$prev_student_url\"><i class=\"fa fa-backward\"></i> Previous Student</a>\n";
            } else {
                echo "<a class=\"btn disabled\"><i class=\"fa fa-fast-backward\"></i> First Student</a>\n";
                echo "<a class=\"btn disabled\"><i class=\"fa fa-backward\"></i> Previous Student</a>\n";
            }
            // If we're not at the last student, show the "Last" and "Next" buttons. Otherwise show disabled buttons.
            if (count($submissions) > 1 && $exam_progress->getID() !== $submissions[count($submissions)-1]->getID()) {
                $last_student_url = ENTRADA_URL."/admin/exams/grade?".replace_query(array("progress_id" => $submissions[count($submissions)-1]->getID()));
                $next_student_url = $last_student_url;
                for ($i = 0; $i < count($submissions) - 1; $i++) {
                    if ($submissions[$i]->getID() === $exam_progress->getID()) {
                        $next_student_url = ENTRADA_URL."/admin/exams/grade?".replace_query(array("progress_id" => $submissions[$i+1]->getID()));
                        break;
                    }
                }
                echo "<a class=\"btn\" href=\"$next_student_url\">Next Student <i class=\"fa fa-forward\"></i></a>\n";
                echo "<a class=\"btn\" href=\"$last_student_url\">Last Student <i class=\"fa fa-fast-forward\"></i></a>\n";
            } else {
                echo "<a class=\"btn disabled\">Next Student <i class=\"fa fa-forward\"></i></a>\n";
                echo "<a class=\"btn disabled\">Last Student <i class=\"fa fa-fast-forward\"></i></a>\n";
            }
            echo "</div>\n";
            echo "</div>\n";
            echo "</div>\n";
            echo "</div>\n";
            echo "</div>\n";
        }
    }
}
