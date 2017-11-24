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
 * API to handle interaction with assessment learning events.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

if((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed('quiz', 'update', false)) {
    $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

    $ERROR++;
    $ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
    exit;
}

$request = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));

$request_var = "_".$request;

$method = clean_input(${$request_var}["method"], array("trim", "striptags"));

switch ($request) {
    case "POST" :
        break;
    case "GET" :
        switch ($method) {
            case "title_search" :
                if(isset(${$request_var}["title"]) && $tmp_input = clean_input(${$request_var}["title"], array("trim", "striptags"))) {
                    $title = $tmp_input;
                } else {
                    add_error("To search for learning quizzes, begin typing the title of the quiz you wish to find in the search box.");
                }

                if(isset(${$request_var}["course_id"]) && $tmp_input = clean_input(${$request_var}["course_id"], array("trim", "int"))) {
                    $course_id = $tmp_input;
                } else {
                    add_error("No course ID provided.");
                }

                if(isset(${$request_var}["assessment_id"]) && $tmp_input = clean_input(${$request_var}["assessment_id"], array("trim", "int"))) {
                    $assessment_id = $tmp_input;
                } else {
                    $assessment_id = "";
                }

                if(isset(${$request_var}["cperiod"]) && $tmp_input = clean_input(${$request_var}["cperiod"], array("trim", "int"))) {
                    $cperiod = $tmp_input;
                } else {
                    add_error("No audience ID provided.");
                }

                if (!$ERROR) {
                    if ($cperiod) {
                        $curriculum_period = Models_Curriculum_Period::fetchRowByID($cperiod);
                    }

                    $e = new Models_Event();

                    if ($curriculum_period) {
                        $quizzes = Models_Quiz::fetchAllQuizzesByTitleForAssessments($title, $assessment_id, $curriculum_period->getStartDate(), $curriculum_period->getFinishDate());
                    } else {
                        $quizzes = Models_Quiz::fetchAllQuizzesByTitleForAssessments($title, $assessment_id);
                    }

                    if ($quizzes) {
                        $quizzes_array = array();
                        foreach ($quizzes as $quiz) {
                            $quizzes_array[] = array(
                                "quiz_id" => $quiz["aquiz_id"],
                                "quiz_title" => $quiz["quiz_title"],
                                "quiz_location" =>$quiz["content_title"],
                                "quiz_questions" => $quiz["question_total"]
                            );
                        }
                        echo json_encode(array("status" => "success", "data" => $quizzes_array));
                    } else {
                        echo json_encode(array("status" => "error", "data" => array("No quizzes found with a title containing <strong>". $title ."</strong>")));
                    }
                } else {
                    echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                }
                break;

            case "get_quiz_display":
                if (isset(${$request_var}["quiz_id"]) && $tmp_input = clean_input(${$request_var}["quiz_id"], array("trim", "int"))) {
                    $quiz_id = $tmp_input;
                } else {
                    add_error("No Quiz ID provided.");
                    exit();
                }

                if (isset(${$request_var}["assessment_id"]) && $tmp_input = clean_input(${$request_var}["assessment_id"], array("trim", "int"))) {
                    $assessment_id = $tmp_input;
                } else {
                    $assessment_id = "";
                }
                
                $query = "SELECT aq.*
                             FROM `attached_quizzes` AS aq
                             JOIN `quizzes` AS b
                             ON aq.`quiz_id` = b.`quiz_id`
                             WHERE aq.`aquiz_id`=".$db->qstr($quiz_id)."
                             GROUP BY aq.`aquiz_id`";
                echo $query;
                $attached_quizzes = $db->GetAll($query);

                foreach ($attached_quizzes as $attached_quiz) {
                    $quiz = Models_Quiz::fetchRowByID($attached_quiz["quiz_id"]);
                    $query = "  SELECT * FROM `quiz_questions`
                                WHERE `quiz_id` = " . $db->qstr($attached_quiz["quiz_id"]) . "
                                AND `questiontype_id` = 1
                                AND `question_active` = 1";
                    $quiz_questions = $db->GetAll($query);

                    echo "<tbody class=\"accordion-toggle\" data-toggle=\"collapse\" data-target=\"#quiz-" . $quiz->getQuizID() . "-container\" data-parent=\"#quiz_list\">\n";
                    echo "  <tr id=\"quiz-" . $quiz->getQuizID() . "\">\n";
                    echo "      <td>&nbsp;</td>\n";
                    echo "      <td>" . html_encode($quiz->getQuizTitle()) . "</td>\n";
                    echo "      <td><i class=\"icon-pencil\"></i>&nbsp;&nbsp;<span id=\"question_count_" . $quiz->getQuizID() . "\">" . count($quiz_questions) . "</span> of " . (int)count($quiz_questions) . "</td>\n";
                    echo "      <td><a data-id=\"".$attached_quiz['aquiz_id']."\" class=\"remove_quiz_btn\" href=\"javascript://\"><i class=\"icon-trash\"></i></a></td>\n";
                    echo "  </tr>\n";
                    echo "</tbody>\n";
                    echo "<tbody id=\"quiz-" . $quiz->getQuizID() . "-container\" class=\"quiz-question-container accordion-body collapse\">\n";
                    echo "  <tr>\n";
                    echo "      <td>&nbsp;</td>\n";
                    echo "      <td colspan=\"3\">\n";
                    echo "          <div class=\"row-fluid wrap\" id=\"quiz-" . $quiz->getQuizID() . "-questions\">\n";
                    $questions_list = array();
                    $questions_array = array();

                    if ($quiz_questions) {
                        foreach ($quiz_questions as $quiz_question) {
                            $questions_list[$quiz_question["qquestion_id"]] = $quiz_question;
                        }
                        if ($assessment_id) {
                            $query = "SELECT a.*, b.`assessment_id` FROM `quiz_questions` AS a
                                        LEFT JOIN `assessment_quiz_questions` AS b
                                        ON a.`qquestion_id` = b.`qquestion_id`
                                        WHERE b.`assessment_id` = " . $db->qstr($assessment_id) . "
                                        AND a.`questiontype_id` = 1
                                        AND a.`question_active` = 1";
                            $quiz_questions = $db->GetAll($query);
                        }

                        if ($quiz_questions) {
                            foreach ($quiz_questions as $quiz_question) {
                                if (isset($quiz_question["assessment_id"]) && $quiz_question["assessment_id"]) {
                                    $questions_array[$quiz_question["qquestion_id"]] = $quiz_question;
                                }
                            }
                            if (!count($questions_array)) {
                                $questions_array = $questions_list;
                            }
                        } else {
                            $questions_array = $questions_list;
                        }
                    }

                    if ($questions_list) {
                        ?>
                        <br/>
                        <div class="quiz-questions row-fluid" id="quiz-content-questions-holder">
                            <ol class="questions" id="quiz-questions-list" style="padding-left: 20px;">
                                <?php
                                foreach ($questions_list as $question) {
                                    echo "<li id=\"question_" . $question["qquestion_id"] . "\" class=\"question\">";
                                    if ($assessment_id) {
                                        echo "<input onclick=\"submitQuizQuestions(" . $quiz->getQuizID() . ")\" type=\"checkbox\" value=\"" . $question["qquestion_id"] . "\" name=\"question_ids[]\"" . (array_key_exists($question["qquestion_id"], $questions_array) || !count($questions_array) ? " checked=\"checked\"" : "") . " style=\"position: absolute; margin-left: -40px;\" />";
                                    } else {
                                        echo "<input type=\"checkbox\" value=\"" . $question["qquestion_id"] . "\" name=\"question_ids[]\"" . (array_key_exists($question["qquestion_id"], $questions_array) || !count($questions_array) ? " checked=\"checked\"" : "") . " style=\"position: absolute; margin-left: -40px;\" />";
                                    }
                                    echo "		" . clean_input($question["question_text"], array("trim", "notags"));
                                    echo "</li>\n";
                                }
                                ?>
                            </ol>
                        </div>
                        <?php
                    } else {
                        add_error("No valid questions were found associated with this quiz.");
                        echo display_error();
                    }
                    echo "          </div>\n";
                    echo "      </td>\n";
                    echo "</tbody>\n";
                }

                break;

            case "delete_quiz":
                if (isset(${$request_var}["aquiz_id"]) && $tmp_input = clean_input(${$request_var}["aquiz_id"], array("trim", "int"))) {
                    $aquiz_id = $tmp_input;
                } else {
                    echo json_encode(array("status" => "error", "data" => "No Quiz ID provided."));
                    exit();
                }

                if (isset(${$request_var}["assessment_id"]) && $tmp_input = clean_input(${$request_var}["assessment_id"], array("trim", "int"))) {
                    $assessment_id = $tmp_input;
                } else {
                    echo json_encode(array("status" => "error", "data" => "No Assessment ID provided."));
                    exit();
                }

                $query = "DELETE FROM `assessment_attached_quizzes` 
                            WHERE `assessment_id` = ".$db->qstr($assessment_id)."
                            AND `aquiz_id` = ".$db->qstr($aquiz_id);
                trigger_error($query);
                if(!$db->Execute($query)) {
                    echo json_encode(array("status" => "error", "data" => "Failed to delete quiz: " .$query));
                    exit();
                }

                echo json_encode(array("status" => "success"));

                break;
        }
        break;
}

