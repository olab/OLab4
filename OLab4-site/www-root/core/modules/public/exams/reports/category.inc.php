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
 * @copyright Copyright 2016 Regents of The University of California. All Rights Reserved.
 *
 */

if((!defined("PARENT_INCLUDED")) || (!defined("IN_PUBLIC_EXAMS"))) {
    exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
}

if (isset($_GET["progress_id"]) && $tmp_input = clean_input($_GET["progress_id"], "int")) {
    $PROCESSED["progress_id"] = $tmp_input;
}

$MODULE_TEXT    = $translate->_("exams");
$DEFAULT        = $translate->_("default");
$SECTION_TEXT   = $MODULE_TEXT["exams"]["reports"]["category"];

if (isset($PROCESSED["progress_id"])) {
    $progress = Models_Exam_Progress::fetchRowByID($PROCESSED["progress_id"]);

    if ($progress) {
        if ($progress->getCreatedBy() == $ENTRADA_USER->getID()) {
            if ($progress->getProgressValue() == "submitted") {
                $post = Models_Exam_Post::fetchRowByID($progress->getPostID());
                if ($post) {
                    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/jquery.dataTables.min-1.10.1.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
                    $HEAD[] = "<link rel='stylesheet' type='text/css' href='". ENTRADA_RELATIVE . "/css/jquery/jquery.dataTables.css?release=".html_encode(APPLICATION_VERSION)."'>";
                    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/exams/reports/category.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
                    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" media=\"print\" href=\"" .  ENTRADA_URL . "/css/exams/reports/category-print.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
                    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/exams/reports/category.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
                    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/chart_js/Chart.min.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";

                    $BREADCRUMB[] = array("url" => ENTRADA_URL."/exams?section=post&id=" . $post->getID(), "title" => $post->getTitle());
                    $BREADCRUMB[] = array("url" => ENTRADA_URL."/exams/reports&section=category", "title" => $SECTION_TEXT["title"]);

                    $display = false;

                    $category_report = Models_Exam_Category::fetchRowByPostID($post->getID());
                    if ($category_report) {
                        if ($category_report->isReportReleased() && $category_report->isUserInAudience($ENTRADA_USER)) {
                            $display = true;
                        }
                    }

                    if ($display) {
                        $category_report_categories = $category_report->getSets();
                        $category_sets = array();
                        $categories = array();

                        if (!empty($category_report_categories)) {
                            foreach ($category_report_categories as $category_report_category) {
                                $category_sets[] = $category_report_category->getObjectiveSetID();
                            }
                            $curriculum_tags = Models_Exam_Question_Objectives::fetchAllByPostID($post->getID());
                            if (!empty($curriculum_tags)) {
                                foreach ($curriculum_tags as $tag) {
                                    $objective_id = $tag->getObjectiveID();
                                    $global_objective = Models_Objective::fetchRow($objective_id);
                                    if ($global_objective) {
                                        $set_parent = $global_objective->getRoot(); //Look into creating a stored procedure for this
                                        $set = $set_parent->getID();
                                        if (in_array($set, $category_sets)) {
                                            $categories[$set][$global_objective->getName()] = $global_objective;
                                        }
                                    }
                                }
                            }

                            // Display scores and key
                            $submissions = Models_Exam_Progress::fetchAllStudentsByPostIDProgressValue($post->getID(), "submitted");

                            // Filter out non multiple choice questions from the exam elements
                            $exam_elements_all  = Models_Exam_Exam_Element::fetchAllByExamID($post->getExamID());
                            $exam_elements      = array();

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
                            ?>
                            <h2 id="main-title">Curriculum Tag Performance Report</h2>
                            <h1 id="exam-title"><?php echo html_encode($post->getTitle());?></h1>
                            <div class="row-fluid" id="score-container">
                                <div class="span3"></div>
                                <div class="span6">
                                    <div class="row-fluid well">
                                        <div class="span6 header">
                                            <div class="score_large">
                                                <?php echo number_format($progress->getExamScore(), 2);?> %
                                            </div>
                                            <div class="score_title">
                                                My Score
                                            </div>
                                            <div class="score_details">
                                                (<?php echo number_format($progress->getExamPoints(), 2);?>/<?php echo $total_points;?>)
                                            </div>
                                        </div>
                                        <div class="span6 header">
                                            <div class="score_large">
                                                <?php echo number_format($mean_percent, 2);?> %
                                            </div>
                                            <div class="score_title">
                                                Average Score
                                            </div>
                                            <div class="score_details">
                                                (<?php echo number_format($mean, 2);?>/<?php echo $total_points;?>)
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="span3"></div>
                            </div>
                            <table class="table table-bordered table-striped">
                                <thead>
                                <tr>
                                    <th>Questions</th>
                                    <th>StdDev</th>
                                    <th>Mean</th>
                                    <th>Median</th>
                                    <th>Kr20</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td><?php echo $num_questions; ?></td>
                                    <td><?php echo number_format($stdev_percent, 2); ?></td>
                                    <td><?php echo number_format($mean, 2); ?></td>
                                    <td><?php echo number_format($median, 2); ?></td>
                                    <td><?php echo (($kr20 != "N/A") ? number_format($kr20, 2) : $kr20); ?></td>
                                </tr>
                                </tbody>
                            </table>
                            <?php
                        }
                        ?>
                        <h2 class="summary_header">Curriculum Tag Performance</h2>
                        <?php
                        $set_ids = array();
                        foreach ($categories as $set_id => $sets) {
                            $global_objective = Models_Objective::fetchRow($set_id);
                            $set_ids[] = $set_id;
                            if ($global_objective) {
                                echo "<h2>" . $global_objective->getName() . "</h2>";
                            }
                            $set_names      = array();
                            $user_values    = array();
                            $class_values   = array();
                            $min_values     = array();
                            $max_values     = array();
                            if (!empty($sets)) {
                                ?>
                                <canvas id="chart-<?php echo $set_id;?>" class="exam-charts"></canvas>
                                <script>
                                     var ctx = document.getElementById("chart-<?php echo $set_id;?>");
                                </script>
                                <table class="table table-bordered table-striped objectives-report">
                                    <thead>
                                        <tr>
                                            <th>Curriculum Tag</th>
                                            <th>Graph</th>
                                            <th>Correct</th>
                                            <th>My Percent</th>
                                            <th>Class Percent</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    ksort($sets);
                                    foreach ($sets as $objective) {
                                        if ($objective && is_object($objective)) {
                                            $category_result_detail = $post->scoreProgressCategoryResultDetail($progress, $objective);
                                            if ($category_result_detail && is_object($category_result_detail)) {
                                                $post_category  = $post->scoreCategory($objective);
                                                $score          = number_format($category_result_detail->getScore(), 2);
                                                $average        = number_format($post_category->getAverage(), 2);
                                                $min            = number_format($post_category->getMin());
                                                $max            = number_format($post_category->getMax());

                                                $name_lgn = strlen($objective->getName());
                                                if ($name_lgn > 59) {
                                                    $set_names[]    = substr($objective->getName(), 0, 60) . "...";
                                                } else {
                                                    $set_names[]    = $objective->getName();
                                                }
                                                $user_values[]  = $score;
                                                $class_values[] = $average;
                                                $min_values[]   = $min;
                                                $max_values[]   = $max;

                                                // generates the Status levels
                                                $status = 0;
                                                if ($score >= $average && $score >= 70 ) {
                                                    $status = 3;
                                                } else if (($score > $average && $score >= 50 && $score <= 70) || ($score < $average && $score >= 70) ) {
                                                    $status = 2;
                                                } else if ($score <= 50 || ($score <= 70 && $score < $average)) {
                                                    $status = 1;
                                                }
                                                ?>
                                                <tr>
                                                    <td>
                                                        <?php echo $objective->getName(); ?>
                                                    </td>
                                                    <td>
                                                        <div class="slider-container">
                                                            <div class="slider-my-score" style="left: <?php echo $score - 1;?>%;">
                                                                <span class="fa-stack">
                                                                    <i class="fa fa-minus fa-rotate-90"></i>
                                                                    <i class="fa fa-user"></i>
                                                                </span>
                                                            </div>
                                                            <div class="slider-min-score<?php echo ($min >= 85 ? " min-dark" : ""); ?>" style="left: <?php echo ($min < 85 ? $min - 1 : 85); ?>%;">
                                                                <span>
                                                                    Min: <?php echo $min;?>
                                                                </span>
                                                            </div>
                                                            <div class="slider-average-score" style="left: <?php echo $average - 1;?>%;">
                                                                <span class="fa-stack">
                                                                    <i class="fa fa-minus fa-rotate-90"></i>
                                                                    <i class="fa fa-stack-1x fa-users"></i>
                                                                </span>
                                                            </div>
                                                            <div class="exam-slider">
                                                                <canvas id="slider-<?php echo $objective->getID();?>" class="slider-canvas" data-min="<?php echo $min;?>" data-max="<?php echo $max;?>" data-id="<?php echo $objective->getID();?>" width="450" height="25"></canvas>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="category-question-count">
                                                        <?php echo $category_result_detail->getValue() . " / " . $category_result_detail->getPossibleValue();?>
                                                    </td>
                                                    <td>
                                                        <?php echo $score;?>%
                                                    </td>
                                                    <td>
                                                        <?php echo ($post_category ? $average  . "%" : "NA");?>
                                                    </td>
                                                    <td class="star-level">
                                                        <?php
                                                        if ($status) {
                                                            switch ($status) {
                                                                case 1:
                                                                    echo "<span class=\"fa-stack star-container\">\n";
                                                                    echo "<i class=\"fa fa-star fa-stack-2x\"></i>\n";
                                                                    echo "<i class=\"fa fa-star fa-stack-1x level-1 \"></i>\n";
                                                                    echo "<i class=\"fa fa-star-o fa-stack-1x star-fill\"></i>\n";
                                                                    echo "</span>\n";
                                                                    break;
                                                                case 2:
                                                                    echo "<span class=\"fa-stack star-container\">\n";
                                                                    echo "<i class=\"fa fa-star fa-stack-2x\"></i>\n";
                                                                    echo "<i class=\"fa fa-star fa-stack-1x star-fill\"></i>\n";
                                                                    echo "<i class=\"fa fa-star-half-o fa-stack-1x level-2\"></i>\n";
                                                                    echo "</span>\n";
                                                                    break;
                                                                case 3:
                                                                    echo "<span class=\"fa-stack star-container\">\n";
                                                                    echo "<i class=\"fa fa-star fa-stack-2x\"></i>\n";
                                                                    echo "<i class=\"fa fa-star fa-stack-1x level-3\"></i>\n";
                                                                    echo "</span>\n";
                                                                    break;
                                                            }
                                                        }
                                                        ?>

                                                    </td>
                                                </tr>
                                                <?php
                                            }
                                        }
                                    }
                                    ?>
                                    </tbody>
                                </table>
                                <div class="row-fluid" id="legend">
                                    <div class="span2">
                                        <div class="slider-my-score">
                                            <span class="fa-stack">
                                                <i class="fa fa-minus fa-rotate-90"></i>
                                                <i class="fa fa-user"></i>
                                            </span>
                                        </div>
                                        <span>My Score</span>
                                    </div>
                                    <div class="span2">
                                        <div class="slider-average-score">
                                            <span class="fa-stack">
                                                <i class="fa fa-minus fa-rotate-90"></i>
                                                <i class="fa fa-stack-1x fa-users"></i>
                                            </span>
                                        </div>
                                        <span>Average/Mean</span>
                                    </div>
                                    <div class="span2">
                                        <canvas id="slider-legend-<?php echo $set_id;?>" width="40" height="15"></canvas>
                                        <span>Score Range</span>
                                    </div>
                                    <div class="span2 star-level">
                                        <span class="fa-stack star-container">
                                            <i class="fa fa-star fa-stack-2x"></i>
                                            <i class="fa fa-star fa-stack-1x level-3"></i>
                                        </span>
                                        <span>Great</span>
                                    </div>
                                    <div class="span2 star-level">
                                        <span class="fa-stack star-container">
                                            <i class="fa fa-star fa-stack-2x"></i>
                                            <i class="fa fa-star fa-stack-1x star-fill"></i>
                                            <i class="fa fa-star-half-o fa-stack-1x level-2"></i>
                                        </span>
                                        <span>Moderate</span>
                                    </div>
                                    <div class="span2 star-level">
                                        <span class="fa-stack star-container">
                                            <i class="fa fa-star fa-stack-2x"></i>
                                            <i class="fa fa-star fa-stack-1x level-1"></i>
                                            <i class="fa fa-star-o fa-stack-1x star-fill"></i>
                                        </span>
                                        <span>Poor</span>
                                    </div>
                                </div>
                                <script>
                                    var set_names = <?php echo json_encode($set_names); ?>;
                                    var user_values = <?php echo json_encode($user_values); ?>;
                                    var class_values = <?php echo json_encode($class_values); ?>;
                                    var min_values = <?php echo json_encode($min_values); ?>;
                                    var max_values = <?php echo json_encode($max_values); ?>;
                                    var myRadarChart = new Chart(ctx, {
                                        type: 'radar',
                                        defaultFontSize: 14,
                                        data: {
                                            labels: set_names,
                                            datasets: [{
                                                label: "Learner",
                                                data: user_values,
                                                borderWidth: 2,
                                                backgroundColor: "rgba(255, 238, 53,0.4)",
                                                borderColor: 'rgba(255, 238, 53, .8)'
                                            },
                                            {
                                                label: "Class Average",
                                                data: class_values,
                                                borderWidth: 2,
                                                backgroundColor: 'rgba(53, 97, 255, 0.4)',
                                                borderColor: 'rgba(53, 97, 255, 0.8)'
                                            },
                                            {
                                                label: "Class Min Scores",
                                                data: min_values,
                                                hidden: true,
                                                borderWidth: 2,
                                                backgroundColor: 'rgba(249, 73, 42, 0.4)',
                                                borderColor: 'rgba(249, 73, 42, 0.8)'
                                            },
                                            {
                                                label: "Class Max Scores",
                                                data: max_values,
                                                hidden: true,
                                                borderWidth: 2,
                                                backgroundColor: 'rgba(115, 249, 42, 0.4)',
                                                borderColor: 'rgba(115, 249, 42, 0.8)'
                                            }]
                                        },
                                        options: {
                                            scale: {
                                                ticks: {
                                                    beginAtZero: true,
                                                    max: 100
                                                }
                                            },
                                            maintainAspectRatio: true
                                        }
                                    });

                                    jQuery(document).ready(function($) {
                                        $(".slider-canvas").each(function(key, value) {
                                            var min = $(value).data("min");
                                            var max = $(value).data("max");
                                            var calculated_min = (min / 100) * 420;
                                            var calculated_max = (max / 100) * 420;

                                            var id = $(value).data("id");

                                            var canvas_obj = document.getElementById("slider-" + id).getContext("2d");
                                                canvas_obj.beginPath();
                                                canvas_obj.moveTo(10, 2);
                                                canvas_obj.lineTo(410, 2);
                                                canvas_obj.quadraticCurveTo(419, 2, 419, 10);
                                                canvas_obj.lineTo(419, 15);
                                                canvas_obj.quadraticCurveTo(419, 20, 410, 20);
                                                canvas_obj.lineTo(10, 20);
                                                canvas_obj.quadraticCurveTo(2, 20, 2, 15);
                                                canvas_obj.lineTo(2, 10);
                                                canvas_obj.quadraticCurveTo(0, 2, 10, 2);
                                                canvas_obj.lineWidth=1;
                                                canvas_obj.stroke();
                                                canvas_obj.fillStyle = "rgb(255,255,255)";
                                                canvas_obj.fill();

                                            // Builds the start section
                                            if (calculated_min < 10  && calculated_max != 0) {
                                                canvas_obj.beginPath();
                                                canvas_obj.moveTo(10, 20);
                                                canvas_obj.quadraticCurveTo(2, 20, 2, 15);
                                                canvas_obj.lineTo(2, 10);
                                                canvas_obj.quadraticCurveTo(0, 2, 10, 2);
                                                var my_gradient = canvas_obj.createLinearGradient(0, 0, 0, 20);
                                                my_gradient.addColorStop(0, "rgba(5, 117, 147, 0.4)");
                                                my_gradient.addColorStop(1, "rgba(5, 117, 147, 1)");
                                                canvas_obj.fillStyle = my_gradient;
                                                canvas_obj.fill();
                                            }

                                            if (min !== 100) {
                                                // Builds the middle section
                                                canvas_obj.beginPath();
                                                if (calculated_min >= 10) {
                                                    canvas_obj.moveTo(calculated_min, 2);
                                                    canvas_obj.lineTo(calculated_min, 20);
                                                } else {
                                                    canvas_obj.moveTo(10, 2);
                                                    canvas_obj.lineTo(10, 20);
                                                }

                                                if (calculated_max <= 410) {
                                                    canvas_obj.lineTo(calculated_max, 20);
                                                    canvas_obj.lineTo(calculated_max, 2);
                                                } else {
                                                    canvas_obj.lineTo(410, 20);
                                                    canvas_obj.lineTo(410, 2);
                                                }

                                                if (calculated_min >= 10) {
                                                    canvas_obj.lineTo(calculated_min, 2);
                                                } else {
                                                    canvas_obj.lineTo(10, 2);
                                                }

                                                var my_gradient = canvas_obj.createLinearGradient(0, 0, 0, 20);
                                                my_gradient.addColorStop(0, "rgba(5, 117, 147, 0.4)");
                                                my_gradient.addColorStop(1, "rgba(5, 117, 147, 1)");
                                                canvas_obj.fillStyle = my_gradient;
                                                canvas_obj.fill();

                                                // Builds the end section
                                                if (calculated_max > 410) {
                                                    canvas_obj.beginPath();
                                                    canvas_obj.lineTo(410, 2);
                                                    canvas_obj.quadraticCurveTo(419, 2, 419, 10);
                                                    canvas_obj.lineTo(419, 15);
                                                    canvas_obj.quadraticCurveTo(419, 20, 410, 20);
                                                    var my_gradient = canvas_obj.createLinearGradient(0, 0, 0, 20);
                                                    my_gradient.addColorStop(0, "rgba(5, 117, 147, 0.4)");
                                                    my_gradient.addColorStop(1, "rgba(5, 117, 147, 1)");
                                                    canvas_obj.fillStyle = my_gradient;
                                                    canvas_obj.fill();
                                                }
                                            }
                                        });
                                    });
                                </script>
                                <?php
                            }
                        }

                        if ($set_ids && is_array($set_ids)) {
                            foreach ($set_ids as $set_id) {
                                ?>
                                <script>
                                    var set_id = <?php echo $set_id;?>;
                                    var canvas_obj = document.getElementById("slider-legend-" + set_id).getContext("2d");
                                    canvas_obj.beginPath();
                                    canvas_obj.moveTo(10, 2);
                                    canvas_obj.lineTo(10, 20);
                                    canvas_obj.lineTo(40, 20);
                                    canvas_obj.lineTo(40, 2);
                                    canvas_obj.lineTo(10, 2);

                                    var my_gradient = canvas_obj.createLinearGradient(0, 0, 0, 20);
                                    my_gradient.addColorStop(0, "rgba(5, 117, 147, 0.4)");
                                    my_gradient.addColorStop(1, "rgba(5, 117, 147, 1)");
                                    canvas_obj.fillStyle = my_gradient;
                                    canvas_obj.fill();
                                </script>
                                <?php
                            }
                        }
                    } else {
                        add_error("The Curriculum Tag report is unavailable to view.");
                        echo display_error();
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