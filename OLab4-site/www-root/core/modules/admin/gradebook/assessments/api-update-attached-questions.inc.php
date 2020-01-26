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
 * This API file returns an HTML table of the possible audience information
 * based on the selected course.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */
if (!defined("IN_GRADEBOOK")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("gradebook", "update", false)) {
	add_error("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] do not have access to this module [".$MODULE."]");
} else {    
    /**
     * Clears all open buffers so we can return a plain response for the Javascript.
     */
    ob_clear_open_buffers();
    $PROCESSED = array();
    
    if ($ASSESSMENT_ID) {
        $query = "SELECT * FROM `assessments`
                    WHERE `assessment_id` = ".$db->qstr($ASSESSMENT_ID)."
                    AND `active` = '1'";
        $assessment = $db->GetRow($query);
        if ($assessment) {
            if (isset($_GET["aquiz_id"]) && ($tmp_input = clean_input($_GET["aquiz_id"], "int"))) {
                $PROCESSED["aquiz_id"] = $tmp_input;
                $query = "SELECT a.* FROM `quizzes` AS a
                            JOIN `attached_quizzes` AS b
                            ON a.`quiz_id` = b.`quiz_id`
                            WHERE b.`aquiz_id` = ".$db->qstr($PROCESSED["aquiz_id"]);
                $quiz = $db->GetRow($query);
            }
            if ($quiz) {
                $temp_ids = array();
                $QUESTIONS = array();
                $QUESTIONS_LIST = array();
                if ($STEP == 2) {
                    $question_ids_string = "";
                    if(isset($_POST["question_ids"]) && @count($_POST["question_ids"])) {
                        foreach($_POST["question_ids"] as $question_id) {
                            $question_id = (int) trim($question_id);
                            if($question_id) {
                                $temp_ids[] = $question_id;
                            }
                        }
                        $query = "SELECT * FROM `quiz_questions`
                                    WHERE `quiz_id` = ".$db->qstr($quiz["quiz_id"])."
                                    AND `questiontype_id` = 1";
                        $quiz_questions = $db->GetAll($query);
                        if ($quiz_questions) {
                            foreach ($quiz_questions as $quiz_question) {
                                if (array_search($quiz_question["qquestion_id"], $temp_ids) !== false) {
                                    $QUESTIONS[$quiz_question["qquestion_id"]] = $quiz_question;
                                }
                                $QUESTIONS_LIST[$quiz_question["qquestion_id"]] = $quiz_question;
                                $question_ids_string .= ($question_ids_string ? ", " : "").$db->qstr($quiz_question["qquestion_id"]);
                            }
                            if (count($QUESTIONS)) {
                                $added_questions = 0;

                                $query = "SELECT * FROM `assessment_quiz_questions` 
                                            WHERE `assessment_id` = ".$db->qstr($ASSESSMENT_ID)."
                                            AND `aquiz_id` = ".$db->qstr($PROCESSED["aquiz_id"])."
                                            AND `qquestion_id` IN (".$question_ids_string.")";
                                $existing_questions = $db->GetAll($query);

                                $query = "DELETE FROM `assessment_quiz_questions` 
                                            WHERE `assessment_id` = ".$db->qstr($ASSESSMENT_ID)."
                                            AND `aquiz_id` = ".$db->qstr($PROCESSED["aquiz_id"])."
                                            AND `qquestion_id` IN (".$question_ids_string.")";
                                $db->Execute($query);

                                foreach ($QUESTIONS as $question) {
                                    if (!$db->AutoExecute("assessment_quiz_questions", array("aquiz_id" => $PROCESSED["aquiz_id"], "assessment_id" => $ASSESSMENT_ID, "qquestion_id" => $question["qquestion_id"]), "INSERT")) {
                                        application_log("error", "Unable to insert a new assessment_quiz_question record while updating an assessment. Database said: ".$db->ErrorMsg());
                                    } else {
                                        $added_questions++;
                                    }
                                }

                                if ($added_questions) {
                                    echo "<input type=\"hidden\" id=\"new_questions_count\" value=\"".$added_questions."\" />";
                                    $SUCCESS++;
                                    $SUCCESSSTR[] = "You have successfully updated which <strong>Quiz Questions</strong> from <strong>".$quiz["quiz_title"]."</strong> are attached to this assessment. There are now <strong id=\"questions-count\">".html_encode($added_questions)."</strong> attached questions from this quiz.";
                                    echo display_success();
                                    exit;
                                } else {
                                    foreach ($existing_questions as $existing_question) {
                                        if (!$db->AutoExecute("assessment_quiz_questions", array("aquiz_id" => $PROCESSED["aquiz_id"], "assessment_id" => $ASSESSMENT_ID, "qquestion_id" => $existing_question["qquestion_id"]), "INSERT")) {
                                            application_log("error", "Unable to re-insert an assessment_quiz_question record while rolling back updates to an assessment. Database said: ".$db->ErrorMsg());
                                        }
                                    }
                                    add_error("There was an error while trying to attach the selected <strong>Quiz Questions</strong> for this assessment.<br /><br />The system administrator was informed of this error; please try again later.");
                                    echo display_error();
                                }
                            } else {
                                $ERROR++;
                                $ERRORSTR[] = "You must select at least 1 valid question to associate with this assessment.";

                                application_log("notice", "Assessment quiz question api page accessed without providing any question id's to attach while on 'step' 2.");
                            }
                        }
                    } else {
                        $ERROR++;
                        $ERRORSTR[] = "You must select at least 1 question to associate with this assessment by checking the checkbox to the left the question.";

                        application_log("notice", "Assessment quiz question api page accessed without providing any question id's to attach while on 'step' 2.");
                    }
                }
            }
        }
    }
    exit;
}