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
 * This file loads details for any exam post item analysis.
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
    ?>
    <?php
    $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = \"". ENTRADA_URL ."\";</script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/jquery.dataTables.min-1.10.1.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/dataTables.colVis.min.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
    $HEAD[] = "<link rel='stylesheet' type='text/css' href='". ENTRADA_RELATIVE . "/css/jquery/jquery.dataTables.css?release=".html_encode(APPLICATION_VERSION)."'>";
    $HEAD[] = "<link rel='stylesheet' type='text/css' href='". ENTRADA_RELATIVE . "/css/jquery/dataTables.colVis.css?release=".html_encode(APPLICATION_VERSION)."'>";
    
    if (isset($_GET["id"]) && $tmp_input = clean_input($_GET["id"], "int")) {
        $PROCESSED["id"] = $tmp_input;
    }

    $SECTION_TEXT = $SUBMODULE_TEXT[$SECTION];
    $exam = Models_Exam_Exam::fetchRowByID($PROCESSED["id"]);

    if (isset($exam) && is_object($exam)) {
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/exams?section=edit-exam&id=".$exam->getID(), "title" => $exam->getTitle());
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/exams?section=reports&id=".$exam->getID(), "title" => "Reports");
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/exams?section=analysis&id=".$exam->getID(), "title" => "Item Analysis");
        ?>
        <h1 id="exam_title"><?php echo $exam->getTitle(); ?></h1>
        <?php
        $exam_view = new Views_Exam_Exam($exam);
        echo $exam_view->examNavigationTabs($SECTION);

        if ($ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "read")) {
            $all_posts = Models_Exam_Post::fetchAllByExamIDNoPreview($exam->getID());
            $selected_posts = array();
            foreach ($all_posts as $post) {
                if (isset($_POST["post_ids"]) && in_array($post->getID(), $_POST["post_ids"])) {
                    $selected_posts[] = $post;
                }
            }
            if (!isset($_POST["download_csv"])) {
            ?>
            <h2><?php echo $SECTION_TEXT["title"]; ?></h2>
            
            <form class="no-printing" method="POST" action="<?php echo ENTRADA_URL."/admin/exams/exams?".replace_query(); ?>">
                <h3>Options</h3>
                <label class="checkbox">
                    <input type="checkbox" name="point_biserial" value="1"
                        <?php echo isset($_POST["point_biserial"]) ? "checked" : ""; ?> />
                    Point biserial correlation per answer choice
                </label>
                <br />
                <label class="checkbox">
                    <input type="checkbox" name="discrim_index" value="1"
                        <?php echo isset($_POST["discrim_index"]) ? "checked" : ""; ?> />
                    Discrimination index per answer choice
                </label>
                <br />
                <label class="checkbox">
                    <input type="checkbox" name="upper_27" value="1"
                        <?php echo isset($_POST["upper_27"]) ? "checked" : ""; ?> />
                    Upper 27% per answer choice
                </label>
                <br />
                <label class="checkbox">
                    <input type="checkbox" name="lower_27" value="1"
                        <?php echo isset($_POST["lower_27"]) ? "checked" : ""; ?> />
                    Lower 27% per answer choice
                </label>
                <br />
                <label class="checkbox">
                    <input type="checkbox" name="question_text" value="1"
                        <?php echo isset($_POST["question_text"]) ? "checked" : ""; ?> />
                    Question text
                </label>
                <br />
                <label class="checkbox">
                    <input type="checkbox" name="answer_text" value="1"
                        <?php echo isset($_POST["answer_text"]) ? "checked" : ""; ?> />
                    Answer choices text
                </label>
                <br />
                <label class="checkbox">
                    <input type="checkbox" name="rationale" value="1"
                        <?php echo isset($_POST["rationale"]) ? "checked" : ""; ?> />
                    Rationale
                </label>
                <br />
                <h3>Posts</h3>
                <?php
                foreach ($all_posts as $post) {
                    $checked = isset($_POST["post_ids"]) && in_array($post->getID(), $_POST["post_ids"]);
                    echo "<label class=\"checkbox\">\n";
                    echo "<input type=\"checkbox\" name=\"post_ids[]\" value=\"".$post->getID()."\" ".($checked ? "checked" : "")." />\n";
                    echo html_encode($post->getTitle())."(".date("m/d/y", $post->getStartDate()).")\n";
                    echo "</label>\n";
                    echo "<br />\n";
                }
                ?>
                <input class="btn" type="submit" name="download_csv" value="Download CSV" />
                <input class="btn btn-primary" type="submit" name="generate_report" value="Generate Report" />
            </form>
            <?php
            } // end if not download_csv
            if (isset($_POST["generate_report"]) || isset($_POST["download_csv"])) {
                // Calculate shared values
                $submissions = array();
                foreach ($selected_posts as $post) {
                    $submissions = array_merge($submissions, Models_Exam_Progress::fetchAllByPostIDProgressValue($post->getID(), "submitted"));
                }
                // Filter out non multiple choice questions from the exam elements
                $exam_elements_all = Models_Exam_Exam_Element::fetchAllByExamID($exam->getID());
                $exam_elements = array();

                if ($exam_elements_all && is_array($exam_elements_all)) {
                    foreach ($exam_elements_all as $elem) {
                        $question_version = $elem->getQuestionVersion();
                        if (!$question_version) {
                            continue;
                        }
                        $question_type = $question_version->getQuestionType()->getShortname();
                        if (in_array($question_type, array("mc_v", "mc_h"))) {
                            $exam_elements[] = $elem;
                        }
                    }
                }
                $num_questions = count($exam_elements);

                // Sort the submissions by score, get the highest 27% and lowest 27%
                usort($submissions, function($a, $b) { return (int)$a->getExamScore() - (int)$b->getExamScore(); });
                $top_27     = array_map(function($a) { return $a->getID(); }, array_slice($submissions, -count($submissions) * 0.27));
                $bottom_27  = array_map(function($a) { return $a->getID(); }, array_slice($submissions, 0, count($submissions) * 0.27));

                // Use the scores to find measures of central tendency
                $scores         = array_map(function($submission) { return $submission->getExamPoints(); }, $submissions);
                $num_scores     = count($scores);
                $total_points   = $num_scores ? $submissions[0]->getExamValue() : 1;
                $mean           = $num_scores ? array_sum($scores) / $num_scores : false;
                $mean_percent   = $num_scores ? $mean * 100 / $total_points : false;
                if (!$num_scores) {
                    $median = false;
                } else if (0 === $num_scores % 2) {
                    $median = ($scores[($num_scores / 2) - 1] + $scores[$num_scores / 2]) / 2;
                } else {
                    $median = $scores[floor($num_scores / 2)];
                }
                $median_percent = $num_scores ? $median * 100 / $total_points : false;
                if ($num_scores) {
                    $min = $scores[0];
                    $min_percent = $min * 100 / $total_points;
                    $max = $scores[$num_scores - 1];
                    $max_percent = $max * 100 / $total_points;
                } else {
                    $min = false;
                }
                if ($num_scores > 1) {
                    $stdev = sqrt(array_sum(array_map(function($x) use ($mean) { return pow($x - $mean, 2); }, $scores)) / ($num_scores - 1));
                    $stdev_percent = $stdev * 100 / $total_points;
                } else {
                    $stdev = false;
                    $stdev_percent = false;
                }

                $kr20 = Models_Exam_Exam::get_kr20($exam_elements, $scores, $mean);

                // Get all the multiple choice letters we need columns for
                $letters = array();
                foreach ($exam_elements as $elem) {
                    $choices = Models_Exam_Question_Answers::fetchAllRecordsByVersionID($elem->getQuestionVersion()->getID());
                    foreach ($choices as $choice) {
                        $letter = chr(ord("A") + (int)$choice->getOrder() - 1);
                        if (!in_array($letter, $letters)) {
                            $letters[] = $letter;
                        }
                    }
                }
                sort($letters);
            }
            if (isset($_POST["download_csv"])) {
                ob_clear_open_buffers();
                header("Content-type: text/csv");
                header("Content-disposition: attachment; filename=\"report.csv\"");
                $out = fopen("php://output", "w");
                // First we output exam name and aggregate info
                $aggregate_headings = array(
                    "Exam Name", "# Exam Takers", "Mean (points)", "Mean (%)",
                    "Median (points)", "Median (%)", "Min (points)", "Min (%)",
                    "Max (points)", "Max (%)", "Stdev (points)", "Stdev (%)",
                    "KR20"
                );
                $aggregate_data = array(
                    $exam->getTitle(), $num_scores,
                    round($mean, 2), round($mean * 100 / $total_points, 2),
                    round($median, 2), round($median * 100 / $total_points, 2),
                    round($min, 2), round($min * 100 / $total_points, 2),
                    round($max, 2), round($max * 100 / $total_points,2 ),
                    round($stdev, 2), round($stdev * 100 / $total_points, 2),
                    round($kr20, 2)
                );
                fputcsv($out, $aggregate_headings);
                fputcsv($out, $aggregate_data);
                fputcsv($out, array("", ""));
                // Then we output individual question info
                $individual_headings = array("Order", "Difficulty Index", "Upper 27%", "Lower 27%", "Disc. Index", "Point Biserial", "Correct");
                foreach ($letters as $letter) {
                    $individual_headings[] = "$letter (count)";
                }
                foreach ($letters as $letter) {
                    $individual_headings[] = "$letter (%)";
                }
                foreach ($letters as $letter) {
                    if (isset($_POST["point_biserial"])) {
                        $individual_headings[] = "$letter - Point Biserial";
                    }
                    if (isset($_POST["discrim_index"])) {
                        $individual_headings[] = "$letter - Disc. Index";
                    }
                    if (isset($_POST["upper_27"])) {
                        $individual_headings[] = "$letter - Upper 27%";
                    }
                    if (isset($_POST["lower_27"])) {
                        $individual_headings[] = "$letter - Lower 27%";
                    }
                    if (isset($_POST["answer_text"])) {
                        $individual_headings[] = "$letter - Answer Text";
                    }
                }
                if (isset($_POST["question_text"])) {
                    $individual_headings[] = "Question Text";
                }
                if (isset($_POST["rationale"])) {
                    $individual_headings[] = "Rationale";
                }
                fputcsv($out, $individual_headings);
                foreach ($exam_elements as $elem) {
                    $order = $elem->getOrder() + 1;
                    $question_version = $elem->getQuestionVersion();
                    $question_text = $question_version->getQuestionText();
                    $responses = array();
                    foreach ($submissions as $submission) {
                        $new_responses = Models_Exam_Progress_Responses::fetchAllByProgressIDExamIDPostIDProxyIDElementID(
                                $submission->getID(), $exam->getID(), $submission->getPostID(), $submission->getProxyID(), $elem->getID());
                        $responses = array_merge($responses, $new_responses);
                    }
                    $difficulty_index = Models_Exam_Exam::get_difficulty_index($responses);
                    $upper_27_percent = Models_Exam_Exam::get_percent_correct($responses, $top_27, function($a) { return $a->getScore(); });
                    $lower_27_percent = Models_Exam_Exam::get_percent_correct($responses, $bottom_27, function($a) { return $a->getScore(); });
                    $discrim_index = Models_Exam_Exam::get_discrimination_index($responses, $top_27, $bottom_27, function($a) { return $a->getScore(); });
                    $biserial_correlation = Models_Exam_Exam::get_point_biserial_correlation($submissions, $elem, (float)$stdev, function($a) { return $a->getScore(); });
                    $correct_answer = $elem->getQuestionVersion()->getAdjustedMultipleChoiceCorrectText($elem->getID(), $exam->getID());
                    $frequencies = array();
                    foreach ($letters as $letter) {
                        $frequencies[$letter] = 0;
                    }
                    foreach ($responses as $response) {
                        $answers = Models_Exam_Progress_Response_Answers::fetchAllByExamProgressResponseID($response->getID());
                        foreach ($answers as $answer) {
                            $letter = $answer->getResponseElementLetter();
                            $frequencies[$letter]++;
                        }
                    }
                    $individual_data = array(
                        $order, $difficulty_index, $upper_27_percent, $lower_27_percent,
                        $discrim_index, $biserial_correlation, $correct_answer
                    );
                    foreach ($frequencies as $i => $count) {
                        $individual_data[] = $count;
                    }
                    foreach ($frequencies as $i => $count) {
                        $freq = $num_scores ? round(100 * $count / $num_scores, 2) : 0;
                        $individual_data[] = $freq;
                    }
                    foreach ($letters as $letter) {
                        $letter_func = function($a) use ($letter) {
                            $answers = Models_Exam_Progress_Response_Answers::fetchAllByExamProgressResponseID($a->getID());
                            return $answers && $letter === $answers[0]->getResponseElementLetter();
                        };
                        if (isset($_POST["point_biserial"])) {
                            $individual_data[] = Models_Exam_Exam::get_point_biserial_correlation($submissions, $elem, (float)$stdev, $letter_func);
                        }
                        if (isset($_POST["discrim_index"])) {
                            $individual_data[] = Models_Exam_Exam::get_discrimination_index($responses, $top_27, $bottom_27, $letter_func);
                        }
                        if (isset($_POST["upper_27"])) {
                            $individual_data[] = Models_Exam_Exam::get_percent_correct($responses, $top_27, $letter_func);
                        }
                        if (isset($_POST["lower_27"])) {
                            $individual_data[] = Models_Exam_Exam::get_percent_correct($responses, $bottom_27, $letter_func);
                        }
                        if (isset($_POST["answer_text"])) {
                            $choices = Models_Exam_Question_Answers::fetchAllRecordsByVersionID($question_version->getID());
                            foreach ($choices as $choice) {
                                $choice_letter = chr(ord("A") + (int)$choice->getOrder() - 1);
                                if ($choice_letter === $letter) {
                                    $individual_data[] = $choice->getAnswerText();
                                    break;
                                }
                            }
                        }
                    }
                    if (isset($_POST["question_text"])) {
                        $individual_data[] = $question_text;
                    }
                    if (isset($_POST["rationale"])) {
                        $individual_data[] = $question_version->getRationale();
                    }
                    fputcsv($out, $individual_data);
                }
                // Close output and exit so that we don't accidentally output something
                // later in the script
                fclose($out);
                exit;
            } else if (isset($_POST["generate_report"])) {
                echo "<br /><hr /><br />\n";
                if ($submissions) {
                    // Show aggregate analysis
                    echo "<table style=\"width: 100%\">\n";
                    echo "<tr>\n";
                    echo "<td><strong>".$SECTION_TEXT["table_headings"]["num_scores"].":</strong> ".$num_scores."</td>\n";
                    echo "<td><strong>".$SECTION_TEXT["table_headings"]["mean"].":</strong> ".(false === $mean ? "N/A" : round($mean, 2)." (".round($mean_percent, 2)."%)")."</td>\n";
                    echo "<td><strong>".$SECTION_TEXT["table_headings"]["median"].":</strong> ".(false === $median ? "N/A" : round($median, 2)." (".round($median_percent, 2)."%)")."</td>\n";
                    echo "</tr>\n";
                    echo "<tr>\n";
                    echo "<td><strong>".$SECTION_TEXT["table_headings"]["min/max"].":</strong> ".(false === $min ? "N/A" : round($min, 2)."/".round($max, 2)." (".round($min_percent, 2)."%/".round($max_percent, 2)."%)")."</td>\n";
                    echo "<td><strong>".$SECTION_TEXT["table_headings"]["stdev"].":</strong> ".(false === $stdev ? "N/A" : round($stdev, 2)." (".round($stdev_percent, 2)."%)")."</td>\n";
                    echo "<td><strong>".$SECTION_TEXT["table_headings"]["kr20"].":</strong> ".(false === $kr20 ? "N/A" : round($kr20, 2))."</td>\n";
                    echo "</tr>\n";
                    echo "</table>\n";
                    // Show analysis for individual questions
                    if ($exam_elements) {
                        foreach ($exam_elements as $elem) {
                            $order = $elem->getOrder() + 1;
                            $question_version = $elem->getQuestionVersion();
                            $question_text = $question_version->getQuestionText();
                            $responses = array();
                            foreach ($submissions as $submission) {
                                $new_responses = Models_Exam_Progress_Responses::fetchAllByProgressIDExamIDPostIDProxyIDElementID(
                                        $submission->getID(), $exam->getID(), $submission->getPostID(), $submission->getProxyID(), $elem->getID());
                                $responses = array_merge($responses, $new_responses);
                            }
                            $letters = array();
                            $choices = Models_Exam_Question_Answers::fetchAllRecordsByVersionID($question_version->getID());
                            foreach ($choices as $choice) {
                                $letter = chr(ord("A") + (int)$choice->getOrder() - 1);
                                if (!in_array($letter, $letters)) {
                                    $letters[] = $letter;
                                }
                            }
                            sort($letters);

                            $score_func = function($a) { return $a->getScore(); };
                            $difficulty_index = Models_Exam_Exam::get_difficulty_index($responses);
                            $upper_27 = Models_Exam_Exam::get_percent_correct($responses, $top_27, $score_func);
                            $lower_27 = Models_Exam_Exam::get_percent_correct($responses, $bottom_27, $score_func);
                            $discrim_index = Models_Exam_Exam::get_discrimination_index($responses, $top_27, $bottom_27, $score_func);
                            $biserial_correlation = Models_Exam_Exam::get_point_biserial_correlation($submissions, $elem, (float)$stdev, $score_func);

                            $correct_answer = $elem->getQuestionVersion()->getAdjustedMultipleChoiceCorrectText($elem->getID(), $exam->getID());

                            $frequencies = array();
                            foreach ($letters as $letter) {
                                $frequencies[$letter] = 0;
                            }
                            foreach ($responses as $response) {
                                $answers = Models_Exam_Progress_Response_Answers::fetchAllByExamProgressResponseID($response->getID());
                                foreach ($answers as $answer) {
                                    $letter = $answer->getResponseElementLetter();
                                    $frequencies[$letter]++;
                                }
                            }

                            // Output table headers
                            ?>
                            <table class="table table-bordered table-striped" id="analysis-table" style="background: #fff; page-break-inside: avoid;">
                                <thead>
                                <tr>
                                    <th><?php echo $SECTION_TEXT["table_headings"]["order"]; ?></th>
                                    <th><?php echo $SECTION_TEXT["table_headings"]["difficulty_index"]; ?></th>
                                    <th><?php echo $SECTION_TEXT["table_headings"]["upper_27"]; ?></th>
                                    <th><?php echo $SECTION_TEXT["table_headings"]["lower_27"]; ?></th>
                                    <th><?php echo $SECTION_TEXT["table_headings"]["disc_index"]; ?></th>
                                    <th><?php echo $SECTION_TEXT["table_headings"]["point_biserial"]; ?></th>
                                    <th><?php echo $SECTION_TEXT["table_headings"]["correct_answer"]; ?></th>
                                    <?php
                                    foreach ($letters as $letter) {
                                        echo "<th>$letter</th>\n";
                                    }
                                    ?>
                                </tr>
                                </thead>
                                <tbody>
                            <?php

                            // Standard column headers and frequencies
                            echo "<tr>\n";
                            echo "<td><strong>$order</strong></td>\n";
                            echo "<td>$difficulty_index</td>\n";
                            echo "<td>".(false === $upper_27 ? "N/A" : round($upper_27, 2)."%")."</td>\n";
                            echo "<td>".(false === $lower_27 ? "N/A" : round($lower_27, 2)."%")."</td>\n";
                            echo "<td>$discrim_index</td>\n";
                            echo "<td>$biserial_correlation</td>\n";
                            echo "<td>$correct_answer</td>\n";
                            foreach ($frequencies as $i => $count) {
                                $freq = round(100 * $count / $num_scores, 2);
                                echo "<td>$count ($freq%)</td>\n";
                            }
                            echo "</tr>\n";

                            // Individual answer choices point biserial correlation
                            if (isset($_POST["point_biserial"])) {
                                echo "<tr>\n";
                                echo "<td colspan=\"7\" style=\"text-align: right\">Point Biserial</td>\n";
                                foreach ($frequencies as $i => $count) {
                                    $letter_func = function($a) use ($i) {
                                        $answers = Models_Exam_Progress_Response_Answers::fetchAllByExamProgressResponseID($a->getID());
                                        return $answers && $i === $answers[0]->getResponseElementLetter();
                                    };
                                    $biserial = Models_Exam_Exam::get_point_biserial_correlation($submissions, $elem, (float)$stdev, $letter_func);
                                    echo "<td>$biserial</td>\n";
                                }
                                echo "</tr>\n";
                            }

                            // Individual answer choices discrimination index
                            if (isset($_POST["discrim_index"])) {
                                echo "<tr>\n";
                                echo "<td colspan=\"7\" style=\"text-align: right\">Disc. Index</td>\n";
                                foreach ($frequencies as $i => $count) {
                                    $letter_func = function($a) use ($i) {
                                        $answers = Models_Exam_Progress_Response_Answers::fetchAllByExamProgressResponseID($a->getID());
                                        return $answers && $i === $answers[0]->getResponseElementLetter();
                                    };
                                    $discrim = Models_Exam_Exam::get_discrimination_index($responses, $top_27, $bottom_27, $letter_func);
                                    echo "<td>$discrim</td>\n";
                                }
                                echo "</tr>\n";
                            }
                            
                            // Individual answer choices upper 27%
                            if (isset($_POST["upper_27"])) {
                                echo "<tr>\n";
                                echo "<td colspan=\"7\" style=\"text-align: right\">Upper 27%</td>\n";
                                foreach ($frequencies as $i => $count) {
                                    $letter_func = function($a) use ($i) {
                                        $answers = Models_Exam_Progress_Response_Answers::fetchAllByExamProgressResponseID($a->getID());
                                        return $answers && $i === $answers[0]->getResponseElementLetter();
                                    };
                                    $individual_top_27 = Models_Exam_Exam::get_percent_correct($responses, $top_27, $letter_func);
                                    echo "<td>".round($individual_top_27, 2)."%</td>\n";
                                }
                                echo "</tr>\n";
                            }
                            
                            // Individual answer choices lower 27%
                            if (isset($_POST["lower_27"])) {
                                echo "<tr>\n";
                                echo "<td colspan=\"7\" style=\"text-align: right\">Lower 27%</td>\n";
                                foreach ($frequencies as $i => $count) {
                                    $letter_func = function($a) use ($i) {
                                        $answers = Models_Exam_Progress_Response_Answers::fetchAllByExamProgressResponseID($a->getID());
                                        return $answers && $i === $answers[0]->getResponseElementLetter();
                                    };
                                    $individual_bottom_27 = Models_Exam_Exam::get_percent_correct($responses, $bottom_27, $letter_func);
                                    echo "<td>".round($individual_bottom_27, 2)."%</td>\n";
                                }
                                echo "</tr>\n";
                            }

                            // Question text
                            if (isset($_POST["question_text"])) {
                                echo "<tr><td colspan=\"100\">";
                                echo "<strong>".$SECTION_TEXT["table_headings"]["question_text"].":</strong> $question_text";
                                echo "</td></tr>\n";
                            }

                            // Question choices
                            if (isset($_POST["answer_text"])) {
                                echo "<tr><td colspan=\"100\">";
                                $choices = Models_Exam_Question_Answers::fetchAllRecordsByVersionID($question_version->getID());
                                $choices_text = array();
                                foreach ($choices as $choice) {
                                    $letter = chr(ord("A") + (int)$choice->getOrder() - 1);
                                    $choices_text[] = "<strong>$letter.</strong> ".$choice->getAnswerText();
                                }
                                echo implode("<br />", $choices_text);
                                echo "</td></tr>\n";
                            }
                            
                            // Rationale
                            if (isset($_POST["rationale"]) && $question_version->getRationale()) {
                                echo "<tr><td colspan=\"100\">";
                                echo "<strong>Rationale:</strong> ".$question_version->getRationale();
                                echo "</td></tr>\n";
                            }
                            ?>
                                </tbody>
                            </table>
                            <?php
                        }
                    } else {
                        echo display_error($SECTION_TEXT["no_exam_elements_found"]);
                    }
                } else {
                    echo display_error($SECTION_TEXT["no_submissions_found"]);
                }
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