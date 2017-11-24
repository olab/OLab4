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
 * This is the exam feedback page
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 *
 */

if((!defined("PARENT_INCLUDED")) || (!defined("IN_PUBLIC_EXAMS"))) {
    exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
}

if (isset($_GET["progress_id"])) {
    $progress_id = $_GET["progress_id"];
}

$secure_mode = (isset($EXAM_MODE) && $EXAM_MODE === "secure") ? true : false;
if ($secure_mode){
    $entrada_url = ENTRADA_RELATIVE . "/secure";
} else {
    $entrada_url = ENTRADA_RELATIVE;
}

$MODULE_TEXT    = $translate->_("exams");
$DEFAULT        = $translate->_("default");
$SECTION_TEXT   = $MODULE_TEXT["exams"]["feedback"];

$BREADCRUMB[] = array("url" => $entrada_url."/exams/confirmation", "title" => $SECTION_TEXT["title"]);

$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/exams/exams.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/exams/questions.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/exams/exams-public-attempt.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";


switch ($STEP) {
    case 2 :
        if (has_success()) {
            echo display_success();
        }
        break;
    case 1:

    default :
    if (isset($progress_id)) {
        $progress = Models_Exam_Progress::fetchRowByID($progress_id);

        if (isset($progress) && is_object($progress)) {
            if ($progress->getCreatedBy() == $ENTRADA_USER->getID()) {
                if ($progress->getProgressValue() == "submitted") {
                    $progress_view = new Views_Exam_Progress($progress);
                    $post = Models_Exam_Post::fetchRowByID($progress->getPostID());
                    if (isset($post) && is_object($post)) {
                        ?>
                        <h1><?php echo html_encode($post->getTitle());?></h1>
                        <?php
                        $release_score      = $post->getReleaseScore();
                        $release_feedback   = $post->getReleaseFeedback();
                        $score_start_valid  = $post->isScoreStartValid();
                        $score_end_valid    = $post->isScoreEndValid();
                        $start_message      = $post->generateScoreMessage(0);
                        $feedback_message   = $post->generateFeedbackMessage(0);
                        $incorrect          = (int)$post->getReleaseIncorrectResponses();

                        if ($release_score || $release_feedback || $incorrect) {
                            $feedback_array = array(
                                "score"     => ($release_score && ($score_start_valid && $score_end_valid) ? 1 : 0),
                                "feedback"  => ($release_feedback && ($score_start_valid && $score_end_valid) ? 1 : 0),
                                "incorrect" => $incorrect
                            );
                            ?>
                            <div class="well" id="exam_scores_feedback">
                                <h4><?php echo $SECTION_TEXT["title"];?></h4>
                                <hr />
                                <?php
                                if ($start_message && $feedback_message) {
                                    echo "<p>" . $start_message . "<br />" . $feedback_message . "</p>";
                                } else if ($start_message) {
                                    echo "<p>" . $start_message . "</p>";
                                } else if ($feedback_message) {
                                    echo "<p>" . $feedback_message . "</p>";
                                }

                                if ($score_start_valid && $score_end_valid) {
                                    echo $progress_view->getScoreDisplay();
                                }
                                ?>
                            </div>
                            <?php
                            if ($score_start_valid && $score_end_valid || $feedback_start_valid && $feedback_end_valid) {
                                ?>
                                <div id="feedback_scores">
                                    <?php
                                        $exam = Models_Exam_Exam::fetchRowByID($post->getExamID());

                                        if (isset($exam) && is_object($exam)) {
                                            if (!$incorrect) {
                                                $responses = Models_Exam_Progress_Responses::fetchAllByProgressID($progress->getID());
                                            } else {
                                                $responses = $progress->getMissedResponses();
                                            }

                                            if (isset($responses) && is_array($responses)) {
                                                foreach ($responses as $response) {
                                                    $element = Models_Exam_Exam_Element::fetchRowByID($response->getExamElementID());
                                                    if (isset($element) && is_object($element)) {
                                                        switch ($element->getElementType()) {
                                                            case "question" :
                                                                $last_question_type = "question";
                                                                $question = Models_Exam_Question_Versions::fetchRowByVersionID($element->getElementID());
                                                                $question_view = new Views_Exam_Question($question);
                                                                echo $question_view->render(true, NULL, NULL, "details", true, $progress, $response, $feedback_array, true);
                                                                break;
                                                            case "text" :
                                                                $element_view = new Views_Exam_Exam_Element($element);
                                                                echo $element_view->render(true);
                                                                $last_question_type = "text";

                                                                break;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    ?>
                                </div><!-- end feedback div -->
                                <div class="row-fluid">
                                    <?php
                                    if ($secure_mode) {
                                        $exit_url = $entrada_url . "/secure-logout";
                                    } else {
                                        $exit_url = $entrada_url . "/exams?section=post&id=" . $post->getID();
                                    }
                                    ?>
                                    <a class="btn btn-primary pull-right" id="back_to_post" href="<?php echo $exit_url; ?>"><?php echo $DEFAULT["btn_done"];?></a>
                                </div>
                            <?php
                            } // end feedback main if
                        } else {
                            add_error($SECTION_TEXT["errors"]["no_scores_feedback"]);

                            echo display_error();

                            application_log("error", $SECTION_TEXT["errors"]["no_scores_feedback"]);
                        }

                    } else {
                        add_error($SECTION_TEXT["errors"]["invalid_post_id"]);

                        echo display_error();

                        application_log("error", $SECTION_TEXT["errors"]["invalid_post_id"]);
                    }
                } else {
                    add_error($SECTION_TEXT["errors"]["exam_not_submitted"]);

                    echo display_error();

                    application_log("error", $SECTION_TEXT["errors"]["exam_not_submitted"]);
                }
            } else {
                //not your record
                add_error($SECTION_TEXT["errors"]["other_users_record"]);

                echo display_error();

                application_log("error", $SECTION_TEXT["errors"]["other_users_record"]);
            }
        } else {
            add_error($SECTION_TEXT["errors"]["invalid_progress_id"]);

            echo display_error();

            application_log("error", $SECTION_TEXT["errors"]["invalid_progress_id"]);
        }
    } else {
        add_error($SECTION_TEXT["errors"]["invalid_progress_id"]);

        echo display_error();

        application_log("error", $SECTION_TEXT["errors"]["invalid_progress_id"]);
    }
    break;
}
