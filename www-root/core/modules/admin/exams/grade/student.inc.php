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
 * The default file that is loaded when /admin/exams/grade is accessed.
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Robert Fotino <robert.fotino@gmail.com>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_GRADE_EXAMS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("examdashboard", "read", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));
    echo display_error();
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    if (isset($_GET["progress_id"])) {
        $EXAM_PROGRESS_ID = (int)$_GET["progress_id"];
        $exam_progress = Models_Exam_Progress::fetchRowByID($EXAM_PROGRESS_ID);
        if ($exam_progress) {
            $grading_student = User::fetchRowByID($exam_progress->getProxyId(), null, null, 1);
        }
    }
    
    if (!isset($grading_student) || !$grading_student) {
        $ERROR++;
        $ERRORSTR[] = "You must provide a valid progress ID.";
        echo display_error();
    } else if (!Models_Exam_Grader::isStudentGradableBy($grading_student->getProxyId(), $exam_post->getID(), $ENTRADA_USER->getActiveId())) {
        $ERROR++;
        $ERRORSTR[] = "You do not have access to grade this student's submission for this exam.";
        echo display_error();
    } else {
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/grade?".replace_query(), "title" => $grading_student->getFullname());
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/jquery/jquery.growl.css?release=".html_encode(APPLICATION_VERSION) . "\" />";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".ENTRADA_URL."/css/exams/grading.css?release=".html_encode(APPLICATION_VERSION)."\" />";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/exams/exams-public-attempt.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" .  ENTRADA_URL . "/javascript/jquery.growl.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/exams/grading.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
        
        $grading_sidebar = new Views_Exam_Grader_Sidebar($exam_progress);
        new_sidebar_item_no_header($grading_sidebar->render(), "exam-grading-sidebar", 10);
        
        $gradable_responses = Models_Exam_Grader::fetchGradableResponses($exam_progress->getID());
        ?>

        <h1><?php echo sprintf($translate->_("Grading %s's Submission for %s"), $grading_student->getFullname(), $exam_post->getTitle()); ?></h1>
        <p>
            <strong><?php echo $translate->_("Submitted on:"); ?></strong> <?php echo date(DEFAULT_DATE_FORMAT, $exam_progress->getSubmissionDate()); ?><br />
            <strong><?php echo $translate->_("Attempt:"); ?></strong> <?php echo Models_Exam_Progress::fetchAttemptNumber($exam_progress); ?><br />
            <strong><?php echo $translate->_("Score:"); ?></strong> <span id="student_overall_score"><?php echo $exam_progress->getExamPoints()." / ".$exam_progress->getExamValue()." (".$exam_progress->getExamScore()."%)"; ?></span>
        </p>
        
        <?php
        if (has_error()) {
            echo display_error();
        } else if (has_notice()) {
            echo display_notice();
        } else if (has_success()) {
            echo display_success();
        }
        
        if (0 === count($gradable_responses)) {
            echo "<div class=\"alert\">No short answer or essay questions found for this exam. Nothing to grade.</div>\n";
        } else {
            // Show the UI for grading each question
            foreach ($gradable_responses as $response) {
                switch ($response->getQuestionType()) {
                    case "fnb":
                        $view = new Views_Exam_Grader_FnbQuestion($response);
                        break;
                    case "short":
                    case "essay":
                    default:
                        $view = new Views_Exam_Grader_Question($response);
                        break;
                }
                echo $view->render();
            }
            
            $submissions = Models_Exam_Grader::fetchGradableSubmissionsForPost($ENTRADA_USER->getActiveId(), $exam_post->getID());
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
            echo "<a style=\"margin-left: 20px\" class=\"btn btn-primary\" id=\"grading-save-all-btn\">Save All</a>\n";
            echo "</div>\n";
            echo "</div>\n";
            echo "</div>\n";
            echo "</div>\n";
        }
    }
}
