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
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Robert Fotino <robert.fotino@gmail.com>
 * @copyright Copyright 2015 Regents of the University of California. All Rights Reserved.
 */
require_once("Classes/users/UserPhoto.class.php");
require_once("Classes/users/UserPhotos.class.php");

class Views_Exam_Grader_Sidebar extends Views_Deprecated_Base {
    /**
     * Constructs a new sidebar with the given type, either "student" for grading
     * by student, or "question" for grading by question.
     * 
     * @param Models_Exam_Progress|Models_Exam_Progress_Responses $obj
     * @param string $type
     */
    public function __construct($obj, $type = "by-student") {
        switch ($type) {
            case "by-student":
                $this->exam_progress = $obj;
                $this->type = "by-student";
                break;
            case "by-question":
            default:
                $this->exam_progress = Models_Exam_Progress::fetchRowByID($obj->getExamProgressID());
                $this->progress_response = $obj;
                $this->type = "by-question";
                break;
        }
    }
    
    /**
     * Renders the HTML for this sidebar item and returns it as a string.
     * 
     * @global $ENTRADA_USER
     * @return string
     */
    public function render() {
        global $ENTRADA_USER;
        
        // Get the current user's photo and organisation
        $user = User::fetchRowByID($this->exam_progress->getProxyId(), null, null, 1);
        $photos = UserPhotos::get($user->getProxyId());
        $photo_url = null;
        foreach ($photos as $photo) {
            if ($photo->isActive()) {
                $photo_url = $photo->getThumbnail();
            }
        }
        if (null == $photo_url) {
            $photo_url = webservice_url("photo");
        }
        $organisation = Organisation::get($user->getOrganisationId());
        
        // Separate the submissions into pending, in progress, and complete
        $submissions_all = Models_Exam_Grader::fetchGradableSubmissionsForPost($ENTRADA_USER->getActiveId(), $this->exam_progress->getPostId());
        $submissions_pending = array();
        $submissions_in_progress = array();
        $submissions_completed = array();
        switch ($this->type) {
            case "by-student":
                $gradable_question_num = Models_Exam_Grader::fetchGradableQuestionCount($this->exam_progress->getPostId());
                foreach ($submissions_all as $submission) {
                    $graded_question_num = Models_Exam_Grader::fetchGradedQuestionCount($submission->getID());
                    if (0 === $graded_question_num) {
                        $submissions_pending[] = $submission;
                    } else if ($gradable_question_num === $graded_question_num) {
                        $submissions_completed[] = $submission;
                    } else {
                        $submissions_in_progress[] = $submission;
                    }
                }
                break;
            case "by-question":
            default:
                foreach ($submissions_all as $submission) {
                    $response = Models_Exam_Progress_Responses::fetchRowByProgressIDElementID($submission->getID(), $this->progress_response->getExamElementID());
                    if (null === $response->getGradedBy()) {
                        $submissions_pending[] = $submission;
                    } else {
                        $submissions_completed[] = $submission;
                    }
                }
                break;
        }
        
        $html = "<div id=\"grading-sidebar-active-user-image\">\n";
        $html .= "<img src=\"$photo_url\" />\n";
        $html .= "</div>\n";
        $html .= "<div id=\"grading-sidebar-user-metadata\">\n";
        $html .= "<p id=\"grading-sidebar-user-fullname\">".html_encode($user->getFullname(false))."</p>\n";
        $html .= "<p id=\"grading-sidebar-user-organisation\">".html_encode(ucfirst($user->getGroup()))." <span>&bull;</span> ".html_encode($organisation->getTitle())."</p>\n";
        $html .= "<a id=\"grading-sidebar-user-email\" href=\"#\">".html_encode($user->getEmail())."</a>\n";
        $html .= "</div>\n";
        
        // Show the dropdown
        $html .= "<div id=\"grading-sidebar-dropdown-wrapper\">\n";
        $html .= "<a href=\"#\" id=\"grading-sidebar-dropdown-link\">Choose a Student <i class=\"fa fa-chevron-down\"></i></a>\n";
        $html .= "<div id=\"grading-sidebar-dropdown-content\" style=\"display:none\">\n";
        // Output the submissions that are pending grading
        $html .= "<div class=\"grading-sidebar-dropdown-category\" id=\"grading-sidebar-dropdown-pending\">Pending Grading <span class=\"badge\">".count($submissions_pending)."</span></div>\n";
        if (count($submissions_pending) > 0) {
            foreach ($submissions_pending as $submission) {
                $student = User::fetchRowById($submission->getProxyId(), null, null, 1);
                $url = ENTRADA_URL."/admin/exams/grade?".replace_query(array("progress_id" => $submission->getID()));
                $attempt_num = Models_Exam_Progress::fetchAttemptNumber($submission);
                $html .= "<a href=\"$url\" class=\"grading-sidebar-student\">".$student->getFullname(false).($attempt_num > 1 ? " ($attempt_num)" : "")."</a>\n";
            }
        } else {
            $html .= "<div class=\"grading-sidebar-no-students\">No students are pending grading.</div>\n";
        }
        // Output the submissions that are in progress (only relevant if grading by student)
        if ("by-student" === $this->type) {
            $html .= "<div class=\"grading-sidebar-dropdown-category\" id=\"grading-sidebar-dropdown-in-progress\">Grading In Progress <span class=\"badge\">".count($submissions_in_progress)."</span></div>\n";
            if (count($submissions_in_progress) > 0) {
                foreach ($submissions_in_progress as $submission) {
                    $student = User::fetchRowById($submission->getProxyId(), null, null, 1);
                    $url = ENTRADA_URL."/admin/exams/grade?".replace_query(array("progress_id" => $submission->getID()));
                    $attempt_num = Models_Exam_Progress::fetchAttemptNumber($submission);
                    $html .= "<a href=\"$url\" class=\"grading-sidebar-student\">".$student->getFullname(false).($attempt_num > 1 ? " ($attempt_num)" : "")."</a>\n";
                }
            } else {
                $html .= "<div class=\"grading-sidebar-no-students\">No students have grading in progress.</div>\n";
            }
        }
        // Output the submissions that are complete
        $html .= "<div class=\"grading-sidebar-dropdown-category\" id=\"grading-sidebar-dropdown-complete\">Completed Grading <span class=\"badge\">".count($submissions_completed)."</span></div>\n";
        if (count($submissions_completed) > 0) {
            foreach ($submissions_completed as $submission) {
                $student = User::fetchRowById($submission->getProxyId(), null, null, 1);
                $url = ENTRADA_URL."/admin/exams/grade?".replace_query(array("progress_id" => $submission->getID()));
                $attempt_num = Models_Exam_Progress::fetchAttemptNumber($submission);
                $html .= "<a href=\"$url\" class=\"grading-sidebar-student\">".$student->getFullname(false).($attempt_num > 1 ? " ($attempt_num)" : "")."</a>\n";
            }
        } else {
            $html .= "<div class=\"grading-sidebar-no-students\">No students are completely graded.</div>\n";
        }
        // End dropdown content
        $html .= "</div>\n";
        // End dropdown wrapper
        $html .= "</div>\n";
        
        return $html;
    }
}
