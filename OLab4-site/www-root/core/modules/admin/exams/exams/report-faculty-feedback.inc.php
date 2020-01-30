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

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EXAMS"))) {
    exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
}

if (isset($_GET["id"]) && $tmp_input = clean_input($_GET["id"], "int")) {
    $PROCESSED["id"] = $tmp_input;
} else {
    add_error("You must provide an exam id.");
    echo display_error();
}

if (isset($_POST["incorrect"])) {
    $incorrect          = 1;
} else {
    $incorrect          = 0;
}

if (isset($_POST["generate_report"])) {
    $show_controls = false;
} else {
    $show_controls = true;
}

if (isset($_GET["progress_id"])) {
    $progress_id = $_GET["progress_id"];
    $show_controls = false;

    $progress = Models_Exam_Progress::fetchRowByID($progress_id);
    if ($progress) {
        $selected_learner_object = Models_User::fetchRowByID($progress->getProxyID());
        $date = date("Y-m-d H:i:s", $progress->getSubmissionDate());
        $selected_learner = $selected_learner_object->getFullname() . " - " . $date;
    }

} elseif (isset($_POST["learners"])) {
    $progress_id = $_POST["learners"];
}

if ($show_controls === false && $progress_id === NULL ) {
    add_notice("You must select a valid learner attempt");
    echo display_notice();
}

$selected_progress = $progress_id;

$MODULE_TEXT    = $translate->_("exams");
$DEFAULT        = $translate->_("default");
$SECTION_TEXT   = $MODULE_TEXT["exams"]["feedback"];

$exam = Models_Exam_Exam::fetchRowByID($PROCESSED["id"]);
if ($exam) {
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/exams?section=edit-exam&id=".$exam->getID(), "title" => $exam->getTitle());
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/exams?section=reports&id=".$exam->getID(), "title" => "Reports");
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/exams?section=report-faculty-feedback&id=".$exam->getID(), "title" => "Learner Feedback Report");

    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/exams/exams.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/exams/questions.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/exams/exams-public-attempt.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/exams/reports/feedback.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";

    $HEAD[] = "<script src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.advancedsearch.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/exams/reports/summary.css?release=".html_encode(APPLICATION_VERSION)."\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.advancedsearch.css?release=".html_encode(APPLICATION_VERSION)."\" />";
    $HEAD[]	= "<script src=\"" . ENTRADA_URL . "/javascript/exams/reports/feedback-admin.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";

    $exam_view = new Views_Exam_Exam($exam);
    echo $exam_view->examNavigationTabs($SECTION);
    ?>
    <script>
        var post_ids = [];
    </script>
    <?php
    $all_posts = Models_Exam_Post::fetchAllByExamIDNoPreview($exam->getID());
    if ($all_posts && is_array($all_posts) && !empty($all_posts)) {
        ?>
        <script type="text/javascript">
            var ENTRADA_URL = "<?php echo ENTRADA_URL; ?>";
            var exam_id = "<?php echo $exam->getID();?>";
            var API_URL = "<?php echo ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-exams"; ?>";
            var submodule_text = JSON.parse('<?php echo json_encode($SUBMODULE_TEXT); ?>');
            var default_text_labels = JSON.parse('<?php echo json_encode($DEFAULT_TEXT_LABELS); ?>');
        </script>

        <div id="search_form" class="no-printing <?php echo ($show_controls || $progress_id == NULL ? "show" : "hide");?>">
            <form method="POST" action="<?php echo ENTRADA_URL . "/admin/exams/exams?" . replace_query(); ?>" id="feedback-form" class="form-horizontal">
                <input type="hidden" name="exam_id" value="<?php echo $exam->getExamID(); ?>" id="exam_id"/>
                <h3>Options</h3>
                <div class="control-group">
                    <label for="incorrect" class="control-label form-nrequired">
                        Show only incorrect
                    </label>
                    <div class="controls">
                        <input type="checkbox" name="incorrect" id="incorrect" <?php echo ($incorrect ? " checked='true'": "") ;?>/>
                    </div>
                </div>
                <div class="control-group">
                    <label for="learners-btn" class="control-label form-nrequired">
                        Browse Learners
                    </label>
                    <div class="controls entrada-search-widget" id="learners-btn-advancedsearch">
                        <button id="learners-btn" class="btn btn-search-filter" type="button">
                            <?php echo ($selected_learner ? $selected_learner : "Browse Learners"); ?>
                            <i class="icon-chevron-down btn-icon pull-right"></i>
                        </button>
                    </div>
                </div>
                <div id="audience-container" class="entrada-search-list">
                </div>
                <input class="btn btn-primary" type="submit" name="generate_report" value="Generate Report"/>
            </form>
        </div>
        <?php
        if (isset($progress_id)) {
            echo "<button class=\"btn btn-default pull-right no-printing\" id=\"show_controls\">Show Controls</button>";
            $progress = Models_Exam_Progress::fetchRowByID($progress_id);
            if (isset($progress) && is_object($progress)) {
                if ($progress->getProgressValue() == "submitted") {
                    $progress_view = new Views_Exam_Progress($progress);
                    $post = Models_Exam_Post::fetchRowByID($progress->getPostID());
                    if (isset($post) && is_object($post)) {
                        ?>
                        <h1><?php echo html_encode($post->getTitle());?></h1>
                        <?php
                        $feedback_array = array(
                            "score"     => 1,
                            "feedback"  => 1,
                            "incorrect" => 0
                        );

                        ?>
                        <div class="well" id="exam_scores_feedback">
                            <h4><?php echo $SECTION_TEXT["title"];?></h4>
                            <hr />
                            <?php echo $progress_view->getScoreDisplay(); ?>
                        </div>
                        <div id="feedback_scores">
                            <?php
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
                            ?>
                        </div><!-- end feedback div -->
                        <?php
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
                add_error($SECTION_TEXT["errors"]["invalid_progress_id"]);

                echo display_error();

                application_log("error", $SECTION_TEXT["errors"]["invalid_progress_id"]);
            }
        }
    }  else {
        // no posts found
        add_error("No Posts found.");
        echo display_error();
    }
    ?>
    <script>
        jQuery(function ($) {
            var parent_form = $("#feedback-form");

            $("#learners-btn").advancedSearch({
                api_url: API_URL,
                resource_url: ENTRADA_URL,
                filters: {
                    learners: {
                        label: "Learners",
                        data_source: "get-feedback-report-audience",
                        mode: "radio",
                        selector_control_name: "learners",
                        api_params: {
                            exam_id: exam_id
                        }
                    }
                },
                no_results_text: "<?php echo $translate->_("No Learners found matching the search criteria"); ?>",
                parent_form: parent_form,
                width: 500
            });

        });
    </script>
    <?php
}