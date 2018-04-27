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
 * This section is loaded when an individual wants to attempt a quiz.
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

if (isset($_GET["exam_progress_id"])) {
    $progress_id = $_GET["exam_progress_id"];
}

$secure_mode = (isset($EXAM_MODE) && $EXAM_MODE === "secure") ? true : false;
if ($secure_mode){
    $entrada_url = ENTRADA_RELATIVE . "/secure";
} else {
    $entrada_url = ENTRADA_RELATIVE;
}

$BREADCRUMB[] = array("url" => $entrada_url."/exams/confirmation", "title" => "Submission Confirmation");

$MODULE_TEXT    = $translate->_($MODULE);
$SECTION_TEXT   = $MODULE_TEXT["attempt"];
$DEFAULT        = $translate->_("default");

$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/exams/exams.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/exams/questions.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/exams/exams-public-attempt.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/jquery/jquery.growl.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";

$HEAD[] = "<script type=\"text/javascript\" src=\"" .  ENTRADA_URL . "/javascript/jquery.growl.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/". $MODULE ."/" . $MODULE . "/feedback.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
$HEAD[] = "<script type=\"text/javascript\">var SECTION_TEXT = ". json_encode($SECTION_TEXT) . "</script>";

// Error checking
switch ($STEP) {
    case 2 :

        if (has_error()) {
            $STEP = 1;
        }
        break;
    case 1 :
    default :
        continue;
        break;
}

switch ($STEP) {
    case 2 :
        if (has_success()) {
            echo display_success();
        }
        break;
    case 1:

    default :
        $score_feedback = 0;
        if (isset($progress_id)) {
            $progress = Models_Exam_Progress::fetchRowByID($progress_id);
            if (isset($progress) && is_object($progress)) {
                $progress_view = new Views_Exam_Progress($progress);
                $post = Models_Exam_Post::fetchRowByID($progress->getPostID());

                if (isset($post) && is_object($post)) {
                    echo "<h1>".html_encode($post->getTitle())."</h1>";
                    echo "<div class=\"message-confirmation\">";
                    if ($progress->getProgressValue() === "submitted") {
                        ?>
                        <div class="alert alert-success">
                            <p><?php echo $SECTION_TEXT["text"]["submit_success"];?></p>
                        <?php

                        $release_score          = $post->getReleaseScore();
                        $release_feedback       = $post->getReleaseFeedback();
                        $score_start_valid      = $post->isScoreStartValid();
                        $score_end_valid        = $post->isScoreEndValid();
                        $start_message          = $post->generateScoreMessage(0);
                        $feedback_message       = $post->generateFeedbackMessage(0);
                        $incorrect_message      = $post->generateIncorrectFeedbackMessage();
                        $href                   = $entrada_url . "/exams?section=feedback&progress_id=" . $progress->getID();

                        if ($release_score || $release_feedback) {
                            $score_feedback = 1;
                            $well = "<div class=\"well\" id=\"exam_scores_feedback\">";
                            if ($start_message || $feedback_message || $release_score) {
                                $well .= "<h4>" . $SECTION_TEXT["feedback"]["title"] . "</h4>";
                                $well .= "<hr />";
                            }
                            if ($start_message && $feedback_message) {
                                $well .= "<p>" . $start_message . "<br />" . $feedback_message . "</p>";
                            } else if ($start_message) {
                                $well .= "<p>" . $start_message . "</p>";
                            } else if ($incorrect_message) {
                                $well .= "<p>" . $incorrect_message . "</p>";
                            } else if ($feedback_message) {
                                $well .= "<p>" . $feedback_message . "</p>";
                            }

                            if ($score_start_valid && $score_end_valid) {
                                $well .= $progress_view->getScoreDisplay();
                            }

                            $well .= "</div>";
                            if ($release_feedback && $score_start_valid && $score_end_valid) {
                                echo "<p>" . $SECTION_TEXT["text"]["submit_auto_forward"] . " " . $SECTION_TEXT["text"]["review_link_01"] . " <strong><a href=\"" . $href . "\">" . $SECTION_TEXT["text"]["review_link_02"] . "</a></strong> " . $SECTION_TEXT["text"]["review_link_03"] . "</p>";
                                $ONLOAD[] = "setTimeout('window.location=\\'" . $href . "\\'', 5000)";
                            }
                        } else {
                            echo "<p>" .  $SECTION_TEXT["text"]["no_scores_feedback"] . "</p>";
                        }
                        echo "</div>";
                        if ($score_feedback) {
                            echo $well;
                        }
                    } else {

                        echo "<div class=\"alert alert-error\">";
                        echo "<p>" . $SECTION_TEXT["text"]["submit_fail"] . "</p>";
                        echo "<p> Click <a href=\"" . $entrada_url . "/exams?section=attempt&action=resume&id=" . $post->getID() . "&progress_id=" . $progress->getID() . "\">here</a> to try to submit again </p>";
                        echo "</div>";
                    }
                    echo "</div>";
                    echo "<div class=\"row-fluid\">";
                    if ($secure_mode){
                        $exit_url = $entrada_url . "/secure-logout";
                    } else {
                        $exit_url = $entrada_url . "/exams?section=post&id=" . $post->getID();
                    }
                    echo "<a class=\"btn btn-primary pull-right\" id=\"back_to_post\" href=\"" . $exit_url . "\">" . $DEFAULT["btn_done"] . "</a>";
                    echo "</div>";
                }
            } else {
                add_error($SECTION_TEXT["text"]["id_invalid"]);

                echo display_error();

                application_log("error", $SECTION_TEXT["text"]["id_invalid"]);
            }

        } else {
            add_error($SECTION_TEXT["text"]["no_id_error"]);

            echo display_error();

            application_log("error", $SECTION_TEXT["text"]["no_post_id_error"]);
        }
        break;
}
