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
 * This file shows the list of questions for an exam post with options to
 * adjust/regrade individual questions for all submissions.
 *
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Robert Fotino <robert.fotino@gmail.com>
 * @copyright Copyright 2016 UC Regents. All Rights Reserved.
 *
 */

// Increases maximum execution time on this page to 5 minutes, because regrading can take a while
set_time_limit(300);

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
    $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = \"". ENTRADA_URL ."\";</script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/" . $MODULE ."/" . $SUBMODULE . "/". $SECTION . ".js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";

    if (isset($_GET["id"]) && $tmp_input = clean_input($_GET["id"], "int")) {
        $PROCESSED["exam_id"] = $tmp_input;
    }

    $exam = Models_Exam_Exam::fetchRowByID($PROCESSED["exam_id"]);
    $SECTION_TEXT = $SUBMODULE_TEXT[$SECTION];

    if ($exam) {
        $exam_view = new Views_Exam_Exam($exam);
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/exams?section=edit-exam&id=".$exam->getID(), "title" => $exam->getTitle());
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/exams?section=adjust&id=".$exam->getID(), "title" => "Adjust Scoring");

        if ($ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "update")) {
            $exam_elements = Models_Exam_Exam_Element::fetchAllByExamID($exam->getID());
            // Handle form submission
            if (isset($_POST["exam_element_id"])) {
                $PROCESSED["exam_element_id"] = (int)$_POST["exam_element_id"];
                // Make sure this element is part of the exam
                $element_in_exam = false;
                foreach ($exam_elements as $element) {
                    if ($PROCESSED["exam_element_id"] == $element->getID()) {
                        $element_in_exam = true;
                    }
                }
                if ($element_in_exam) {
                    $db->StartTrans();
                    // Set up a base adjustment array, can be added onto for different types of adjustments
                    $adjustment_arr = array(
                        "exam_element_id" => $PROCESSED["exam_element_id"],
                        "exam_id" => $PROCESSED["exam_id"],
                        "created_date" => time(),
                        "created_by" => $ENTRADA_USER->getProxyID()
                    );
                    if (isset($_POST["undo_throw_out"])) {
                        if (!Models_Exam_Adjustment::setAllDeletedByElementIDExamIDType($PROCESSED["exam_element_id"], $PROCESSED["exam_id"], "throw_out")) {
                            add_error($translate->_("Failed to undo throwing out of this question. Please try again later."));
                        }
                    } else if (isset($_POST["undo_make_bonus"])) {
                        if (!Models_Exam_Adjustment::setAllDeletedByElementIDExamIDType($PROCESSED["exam_element_id"], $PROCESSED["exam_id"], "make_bonus")) {
                            add_error($translate->_("Failed to undo making bonus question. Please try again alter."));
                        }
                    } else if (isset($_POST["undo_full_credit"])) {
                        if (!Models_Exam_Adjustment::setAllDeletedByElementIDExamIDType($PROCESSED["exam_element_id"], $PROCESSED["exam_id"], "full_credit")) {
                            add_error($translate->_("Failed to undo giving full credit for this question. Please try again later."));
                        }
                    } else if (isset($_POST["mark_correct"]) || isset($_POST["mark_incorrect"])) {
                        $mark_correct = isset($_POST["mark_correct"]);
                        $qanswer_ids = array_keys($mark_correct ? $_POST["mark_correct"] : $_POST["mark_incorrect"]);
                        $qanswer_id = $qanswer_ids[0];
                        $question_answer = Models_Exam_Question_Answers::fetchRowByID($qanswer_id);
                        if ($question_answer) {
                            $new_adjustment = new Models_Exam_Adjustment(array_merge($adjustment_arr, array(
                                "type" => $mark_correct ? "correct" : "incorrect",
                                "value" => $qanswer_id
                            )));
                            $old_adjustment = Models_Exam_Adjustment::fetchRowByElementIDExamIDTypeValue(
                                $PROCESSED["exam_element_id"],
                                $PROCESSED["exam_id"],
                                $mark_correct ? "incorrect" : "correct",
                                $qanswer_id
                            );
                            if ($old_adjustment) {
                                $old_adjustment->setDeletedDate(time());
                                if (!$old_adjustment->update()) {
                                    $db->FailTrans();
                                    add_error($translate->_("Failed to delete old grading adjustment that marked this answer as ".($mark_correct ? "incorrect" : "correct").". Please try again later."));
                                }
                            }
                            if (!$new_adjustment->insert()) {
                                $db->FailTrans();
                                add_error($translate->_("Failed to add grading adjustment that marked this answer as ".($mark_correct ? "correct" : "incorrect").". Please try again later."));
                            }
                        }
                    } else {
                        $delete_other_adjustments = false;
                        if (isset($_POST["update_points"])) {
                            $delete_other_adjustments = true;
                            $adjustment_arr["type"] = "update_points";
                            // Truncate updated points to two decimals
                            $adjustment_arr["value"] = floor((double)$_POST["adjusted_points"] * 100) / 100;
                            $error_str = $translate->_("Failed to update question points. Please try again later.");
                        } else if (isset($_POST["throw_out"])) {
                            $delete_other_adjustments = true;
                            $adjustment_arr["type"] = "throw_out";
                            $error_str = $translate->_("Failed to throw out question. Please try again later.");
                        } else if (isset($_POST["make_bonus"])) {
                            $delete_other_adjustments = true;
                            $adjustment_arr["type"] = "make_bonus";
                            $error_str = $translate->_("Failed to make bonus question. Please try again later.");
                        } else if (isset($_POST["full_credit"])) {
                            $delete_other_adjustments = true;
                            $adjustment_arr["type"] = "full_credit";
                            $error_str = $translate->_("Failed to give full credit for question. Please try again later.");
                        } else {
                            $adjustment_arr = null;
                        }
                        if ($adjustment_arr) {
                            $adjustment = new Models_Exam_Adjustment($adjustment_arr);
                            if ($delete_other_adjustments &&
                                !Models_Exam_Adjustment::setAllDeletedByElementIDExamIDType($adjustment_arr["exam_element_id"],
                                                                                            $adjustment_arr["exam_id"],
                                                                                            $adjustment_arr["type"])) {
                                add_error($error_str);
                                $db->FailTrans();
                            } else if (!$adjustment->insert()) {
                                add_error($error_str);
                                $db->FailTrans();
                            }
                        }
                    }
                    if (!has_error()) {
                        // Do a regrade for all submissions
                        $submissions = array();
                        $all_posts = Models_Exam_Post::fetchAllByExamID($exam->getID());
                        foreach ($all_posts as $post) {
                            $submissions = array_merge($submissions, Models_Exam_Progress::fetchAllByPostIDProgressValue($post->getID(), "submitted"));
                        }
                        foreach ($submissions as $submission) {
                            $submission_view = new Views_Exam_Progress($submission);
                            $regrade = true;
                            if (!$submission_view->gradeExam($regrade)) {
                                add_error($translate->_("Error while regrading exam submissions. Please try again later."));
                                $db->FailTrans();
                                break;
                            }
                        }
                    }
                    $db->CompleteTrans();
                }
            }
            
            ?>

            <h1 id="exam_title"><?php echo $exam->getTitle(); ?></h1>
            <?php
            echo $exam_view->examNavigationTabs($SECTION);
            ?>
            <h2>Adjust Exam Scoring</h2>
            <?php
            foreach ($exam_elements as $element) {
                $adjustments_view = new Views_Exam_Adjustment($element, $exam);
                echo $adjustments_view->render();
            }
        } else {
            add_error(sprintf($translate->_("Your account does not have the permissions required to edit this exam.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

            echo display_error();

            application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this exam [".$PROCESSED["id"]."]");
        }
    } else {
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "/" . $SECTION, "title" => $SECTION_TEXT["title"]);
        ?>
        <h1><?php echo $SUBMODULE_TEXT["exams"]["title"]; ?></h1>
        <?php
        echo display_error($SUBMODULE_TEXT["exams"]["exam_not_found"]);
    }
}