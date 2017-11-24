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
 * Aggregates learner comments for questions on this exam.
 *
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Robert Fotino <robert.fotino@gmail.com>
 * @copyright Copyright 2016 UC Regents. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EXAMS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("exam", "create", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    if (isset($_GET["id"]) && $tmp_input = clean_input($_GET["id"], "int")) {
        $PROCESSED["id"] = $tmp_input;
    }

    $exam = Models_Exam_Exam::fetchRowByID($PROCESSED["id"]);
    $SECTION_TEXT = $SUBMODULE_TEXT[$SECTION];

    if ($exam) {
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/exams?section=edit-exam&id=".$exam->getID(), "title" => $exam->getTitle());
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/exams?section=reports&id=".$exam->getID(), "title" => "Reports");
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/exams?section=learner-comments&id=".$exam->getID(), "title" => "Learner Comments");
        if ($ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "read")) {
            ?>
            <h1 id="exam_title"><?php echo $exam->getTitle(); ?></h1>
            <?php
            $exam_view = new Views_Exam_Exam($exam);
            echo $exam_view->examNavigationTabs($SECTION);
            ?>
            <h2>Learner Comments Report</h2>
            <?php
            $exam_elements = Models_Exam_Exam_Element::fetchAllByExamIDElementType($exam->getID());
            $exam_elements_with_comments = array();
            foreach ($exam_elements as $elem) {
                $comments = array();
                $responses = Models_Exam_Progress_Responses::fetchAllByExamElementID($elem->getID());
                foreach ($responses as $response) {
                    $comment = $response->getLearnerComments();
                    if ($comment && $response->getMarkFacultyReview()) {
                        $comments[] = array("date" => $response->getUpdatedDate(), "text" => $comment);
                    }
                }
                if ($comments) {
                    $exam_elements_with_comments[] = $elem;
                    // Output the question view and the learner comments
                    $question_view = new Views_Exam_Question($elem->getQuestionVersion());
                    echo $question_view->renderLearnerCommentsFacultyView($elem, $comments);
                }
            }
            if (!$exam_elements_with_comments) {
                add_notice("No exam elements found with comments marked for faculty review.");
                echo display_notice();
            }
        } else {
            add_error(sprintf($translate->_("Your account does not have the permissions required to view this exam.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

            echo display_error();

            application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this exam [".$PROCESSED["id"]."]");
        }
    } else {
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "/" . $SECTION, "title" => $SECTION_TEXT["title"]);
        ?>
        <h1><?php echo $SUBMODULE_TEXT["exams"]["title"]; ?></h1>
        <?php
        echo display_error($SECTION_TEXT["exam_not_found"]);
    }
}