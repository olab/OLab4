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
 * The summary report for faculty
 *
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Samuel Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2016 UC Regents. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EXAMS") && !defined("IN_EXAMS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("exam", "create", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.dataTables.min-1.10.1.js?release=".html_encode(APPLICATION_VERSION).""."\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/chart_js/Chart.min.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.advancedsearch.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
    $HEAD[]	= "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/exams/reports/summary.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";

    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.advancedsearch.css?release=".html_encode(APPLICATION_VERSION)."\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/exams/reports/summary.css?release=".html_encode(APPLICATION_VERSION)."\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" media=\"print\" href=\"" . ENTRADA_URL . "/css/exams/reports/summary-print.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";

    if (isset($_GET["id"]) && $tmp_input = clean_input($_GET["id"], "int")) {
        $PROCESSED["id"] = $tmp_input;
    }

    $tags = $_POST["tags"];

    $SECTION_TEXT = $SUBMODULE_TEXT["section"];

    $exam = Models_Exam_Exam::fetchRowByID($PROCESSED["id"]);

    if ($exam && is_object($exam)) {
        $exam_id = $exam->getExamID();
        $exam_view = new Views_Exam_Exam($exam);
        echo $exam_view->examNavigationTabs($SECTION);

        echo "<h2>" . $SECTION_TEXT["title"] . "</h2>";
        echo "<h1 id=\"main-title\">Summary Performance Report</h1>";

        if (has_success()) {
            echo display_success();
        }

        if (has_error()) {
            echo display_error();
            echo display_success();
        }

        if ($ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "read")) {
            $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/exams?section=edit-exam&id=".$exam->getID(), "title" => $exam->getTitle());
            $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/exams?section=reports&id=".$exam->getID(), "title" => "Reports");
            $all_posts = Models_Exam_Post::fetchAllByExamIDNoPreview($exam->getID());
            if ($all_posts && is_array($all_posts) && !empty($all_posts)) {
                $selected_posts = array();
                $post_ids = array();
                foreach ($all_posts as $post) {
                    if (isset($_POST["post_ids"]) && in_array($post->getID(), $_POST["post_ids"])) {
                        $selected_posts[] = $post;
                        $post_ids[] = $post->getID();
                    }
                }
                ?>
                <script type="text/javascript">
                    var ENTRADA_URL = "<?php echo ENTRADA_URL; ?>";
                    var API_URL = "<?php echo ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-exams"; ?>";
                    var submodule_text = JSON.parse('<?php echo json_encode($SUBMODULE_TEXT); ?>');
                    var default_text_labels = JSON.parse('<?php echo json_encode($DEFAULT_TEXT_LABELS); ?>');
                </script>
                <?php
                $exam_performance       = 1;
                $category_performance   = 1;
                $at_risk                = 1;
                $item_analysis          = 1;
                if (isset($_POST["generate_report"])) {
                    if (!isset($_POST["exam_performance"])) {
                        $exam_performance = 0;
                    }
                    if (!isset($_POST["category_performance"])) {
                        $category_performance = 0;
                    }
                    if (!isset($_POST["at_risk"])) {
                        $at_risk = 0;
                    }

                    if (!isset($_POST["item_analysis"])) {
                        $item_analysis = 0;
                    }
                }
                ?>
                <div id="search_form" class="no-printing <?php echo (isset($_POST["generate_report"]) ? "hide" : "show");?>">
                    <form method="POST" action="<?php echo ENTRADA_URL . "/admin/exams/exams?" . replace_query(); ?>" id="category-form" class="form-horizontal">
                        <input type="hidden" name="exam_id" value="<?php echo $exam->getExamID(); ?>" id="exam_id"/>
                        <h3>Options</h3>
                        <label class="checkbox">
                            <input type="checkbox" name="exam_performance" value="1"
                            <?php echo ($exam_performance == 1) ? "checked" : ""; ?> />
                            <span class="report-label">Exam Performance</span>
                        </label>

                        <label class="checkbox">
                            <input type="checkbox" name="category_performance" value="1"
                                <?php echo ($category_performance == 1) ? "checked" : ""; ?> />
                            <span class="report-label">Curriculum Tag Performance</span>
                        </label>

                        <label class="checkbox">
                            <input type="checkbox" name="at_risk" value="1"
                                <?php echo ($at_risk == 1) ? "checked" : ""; ?> />
                            <span class="report-label">Lowest 27 % of Learners</span>
                        </label>

                        <label class="checkbox">
                            <input type="checkbox" name="item_analysis" value="1"
                                <?php echo ($item_analysis == 1) ? "checked" : ""; ?> />
                            <span class="report-label">Item Analysis</span>
                        </label>

                        <div class="control-group">
                            <label for="sets-btn" class="control-label form-required">
                                Browse Curriculum Tags
                            </label>

                            <div class="controls entrada-search-widget" id="sets-btn-advancedsearch">
                                <button id="sets-btn" class="btn btn-search-filter" type="button">
                                    Browse Curriculum Tags
                                    <i class="icon-chevron-down btn-icon pull-right"></i>
                                </button>
                            </div>
                        </div>
                        <div id="curriculum-tag-container" class="entrada-search-list">
                            <?php
                            if ($tags && is_array($tags) && !empty($tags)) {
                                $displayed = array();
                                foreach ($tags as $set_id) {
                                    if ($set_id != 0 && !in_array($set_id, $displayed)) {
                                        $tag = Models_Objective::fetchRow($set_id);
                                        $displayed[] = $set_id;
                                        ?>
                                        <div id="tags-list-container">
                                            <ul id="tags-list">
                                                <li id="tags-list-<?php echo $tag->getID();?>" class="selected-list-item" data-id="<?php echo $tag->getID();?>" data-parent="" data-filter="tags">
                                                    <?php echo $tag->getName();?>
                                                    <span class="pull-right selected-item-container">
                                                    <span class="selected-item-label">Curriculum Tag Set</span>
                                                    <span class="remove-list-item">×</span>
                                                </span>
                                                </li>
                                            </ul>
                                        </div>
                                        <?php
                                    }
                                }
                            }
                            ?>
                        </div>
                        <h3>Posts</h3>

                        <?php
                        foreach ($all_posts as $post) {
                            $checked = isset($_POST["post_ids"]) && in_array($post->getID(), $_POST["post_ids"]);
                            echo "<label class=\"checkbox\">\n";
                            echo "<input type=\"checkbox\" name=\"post_ids[]\" value=\"" . $post->getID() . "\" " . ($checked ? "checked" : "") . " />\n";
                            echo html_encode($post->getTitle()) . "(" . date("m/d/y", $post->getStartDate()) . ")\n";
                            echo "</label>\n";
                            echo "<br />\n";
                        }
                        ?>
                        <input class="btn btn-primary" type="submit" name="generate_report" value="Generate Report"/>
                    </form>
                </div>
                <?php

                if (isset($_POST["generate_report"])) {
                    echo "<button class=\"btn btn-default pull-right no-printing\" id=\"show_controls\">Show Controls</button>";

                    $category_report_categories         = array();
                    $exam_analysis_tables               = "";

                    $exam_elements = Models_Exam_Exam_Element::fetchAllByExamIDElementType($exam_id, "question");

                    // Display scores and key
                    if ($post_ids && is_array($post_ids) && !empty($post_ids)) {
                        $submissions    = Models_Exam_Progress::fetchAllStudentsByPostIDsProgressValue(implode($post_ids, ","), "submitted");
                        $top_27         = array_map(function($a) { return $a->getID(); }, array_slice($submissions, -count($submissions) * 0.27));
                        $bottom_27      = array_map(function($a) { return $a->getID(); }, array_slice($submissions, 0, count($submissions) * 0.27));

                        // Use the scores to find measures of central tendency
                        $histograms = array();
                        $histogram_scores = array();
                        $histogram_label = array();
                        count($submissions);
                        if ($submissions && is_array($submissions)) {
                            foreach ($submissions as $submission) {
                                $points         = $submission->getExamPoints();
                                $possible       = $submission->getExamValue();
                                $score          = number_format(($points / $possible * 100), 2);
                                $scores[$submission->getProxyID()] = $score;

                                if ($scores < 40) {
                                    $histograms["<40"] = $histograms["<40"] + 1;
                                } else if ($score < 49) {
                                    $histograms["40-49"] = $histograms["40-49"] + 1;
                                } else if ($score < 59) {
                                    $histograms["50-59"] = $histograms["50-59"] + 1;
                                } else if ($score < 69) {
                                    $histograms["60-69"] = $histograms["60-69"] + 1;
                                } else if ($score < 79) {
                                    $histograms["70-79"] = $histograms["70-79"] + 1;
                                } else if ($score < 89) {
                                    $histograms["80-89"] = $histograms["80-89"] + 1;
                                } else {
                                    $histograms[">90"] = $histograms[">90"] + 1;
                                }
                            }
                        }

                        foreach ($histograms as $label => $score) {
                            $histogram_label[] = $label;
                            $histogram_scores[] = $score;
                        }

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
                            $min_percent = reset($scores);
                            $max_percent = end($scores);
                        } else {
                            $min = false;
                        }

                        if ($num_scores > 1) {
                            $stdev = sqrt(array_sum(array_map(function ($x) use ($mean) {
                                    return pow($x - $mean, 2);
                                }, $scores)) / ($num_scores - 1));
                            $stdev_percent = $stdev * 100 / $total_points;
                        } else {
                            $stdev = false;
                            $stdev_percent = false;
                        }

                        $kr20 = Models_Exam_Exam::get_kr20($exam_elements, $scores, $mean);

                        if (!is_numeric($kr20)) {
                            // this is to insure we always have a number for this.
                            $kr20 = 0;
                        }

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


                    if (isset($exam_elements) && is_array($exam_elements) && !empty($exam_elements)) {
                        foreach ($exam_elements as $element) {
                            if ($element && is_object($element) && $element->getNotScored() == 0) {
                                $question       = Models_Exam_Question_Versions::fetchRowByVersionID($element->getElementID());
                                if ($question && is_object($question)) {
                                    $type = $question->getQuestionType()->getShortname();
                                    if ($type != "text") {
                                        $curriculum_tags = Models_Exam_Question_Objectives::fetchAllRecordsByQuestionID($question->getQuestionID());
                                        if ($curriculum_tags && is_array($curriculum_tags) && !empty($curriculum_tags)) {
                                            foreach ($curriculum_tags as $tag) {
                                                if ($tag && is_object($tag)) {
                                                    $objective_id = $tag->getObjectiveID();
                                                    $global_objective = Models_Objective::fetchRow($objective_id);
                                                    if ($global_objective && is_object($global_objective)) {
                                                        // get the parent...
                                                        $parent_id = (int) $global_objective->getParent();
                                                        if ($parent_id > 0) {
                                                            $parent_objective = Models_Objective::fetchRow($parent_id);
                                                            if ($parent_objective && is_object($parent_objective)) {
                                                                $parent_parent_id = (int) $parent_objective->getParent();
                                                                if ($parent_parent_id > 0) {

                                                                } else if ($parent_parent_id == 0) {
                                                                    $set_parent  = $parent_objective;
                                                                    $set = $set_parent->getID();
                                                                }
                                                            }
                                                        } else if ($parent_id == 0) {
                                                            $set_parent  = $global_objective;
                                                            $set = $set_parent->getID();
                                                        }
                                                    }

                                                    if (!array_key_exists($set, $category_report_categories)) {
                                                        $category_report_categories[$set] = $set_parent;
                                                    }
                                                }
                                            }
                                        }

                                        if ($item_analysis) {
                                            $options = array();
                                            $exam_analysis_tables .= $exam_view->renderExamAnalysisTable($element, $submissions, $top_27, $bottom_27, $stdev, $num_scores, $options);
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if (isset($category_report_categories) && is_array($category_report_categories) && !empty($category_report_categories)) {
                        $category_sets = array();
                        $categories = array();

                        foreach ($category_report_categories as $id => $objective) {
                            $category_sets[] = $id;
                        }
                        $curriculum_tags = Models_Exam_Question_Objectives::fetchAllByExamID($exam_id);
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
                    }

                    ?>
                    <h3 id="exam-title">
                        <?php echo html_encode($post->getTitle()); ?>
                    </h3>
                    <?php
                    if ($exam_performance) {
                        ?>
                        <h2 class="summary_header">Exam Performance</h2>
                        <div class="row-fluid">
                            <div class="span6 row-fluid" id="score-container">
                                <div class="span12 row-fluid well">
                                    <div class="span4 header">
                                        <div class="score_large">
                                            <?php echo number_format($mean, 2); ?> %
                                        </div>
                                        <div class="score_title">
                                            Average Score
                                        </div>
                                        <div class="score_details">
                                            (<?php echo number_format($mean, 2); ?>/100)
                                        </div>
                                    </div>
                                    <div class="span4 header">
                                        <div class="score_large">
                                            <?php echo number_format($min_percent, 2); ?> %
                                        </div>
                                        <div class="score_title">
                                            Low Score
                                        </div>
                                        <div class="score_details">
                                            (<?php echo number_format($min_percent, 2); ?>/100)
                                        </div>
                                    </div>
                                    <div class="span4 header">
                                        <div class="score_large">
                                            <?php echo number_format($max_percent, 2); ?> %
                                        </div>
                                        <div class="score_title">
                                            High Score
                                        </div>
                                        <div class="score_details">
                                            (<?php echo number_format($max_percent, 2); ?>/100)
                                        </div>
                                    </div>
                                </div>
                                <div class="span12" id="kr20_div">
                                    <div id="kr20_div_charts">
                                        <canvas id="chart_kr20" class="exam-charts" width="450"
                                                height="120"></canvas>
                                        <canvas id="chart_kr20_2" class="exam-charts" width="450"
                                                height="120"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="span6">
                                <canvas id="chart_histogram" class="exam-charts" width="250" height="150"></canvas>
                            </div>
                        </div>
                        <script>
                            var ctx                 = document.getElementById("chart_histogram");
                            var chart_kr20          = document.getElementById("chart_kr20");
                            var histogram_label     = <?php echo json_encode($histogram_label); ?>;
                            var histogram_scores    = <?php echo json_encode($histogram_scores); ?>;
                            var kr20_value          = [<?php echo number_format($kr20, 2);?>];
                            var chart_kr20_ctx      = chart_kr20.getContext("2d");
                            var gradient            = chart_kr20_ctx.createLinearGradient(0, 0, 400, 0);
                            var gradient_hover      = chart_kr20_ctx.createLinearGradient(0, 0, 400, 0);

                            gradient.addColorStop(0.3, "rgba(244, 66, 66, 1)");
                            gradient.addColorStop(.76, "rgba(244, 217, 66, 1)");
                            gradient.addColorStop(.90, 'rgba(50,174,50,1)');
                            gradient.addColorStop(1, 'rgba(28,140,21,1)');

                            gradient_hover.addColorStop(0.3, "rgba(244, 66, 66, 0.75)");
                            gradient_hover.addColorStop(.76, "rgba(244, 217, 66, 0.75)");
                            gradient_hover.addColorStop(.90, 'rgba(50,174,50,0.75)');
                            gradient_hover.addColorStop(1, 'rgba(28,140,21,0.75)');

                            var myBarChart = new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels: histogram_label,
                                    datasets: [{
                                        label: "Percent Correct",
                                        data: histogram_scores,
                                        borderWidth: 2,
                                        backgroundColor: "rgba(255, 201, 0, 0.7)",
                                        borderColor: "rgba(255, 177, 0, 1)",
                                        hoverBackgroundColor: "rgba(255, 201, 0, 0.4)"
                                    }]
                                },
                                options: {
                                    title: {
                                        display: true,
                                        fontSize: 16,
                                        text: "Student Performance Histogram                                   ",
                                        position: "top"
                                    },
                                    legend: {
                                        display: false
                                    }
                                }
                            });

                            var kr20_barchart = new Chart(chart_kr20, {
                                type: 'horizontalBar',
                                data: {
                                    labels: ["kr20"],
                                    datasets: [{
                                        label: "KR 20: " + kr20_value,
                                        data: kr20_value,
                                        borderWidth: 2,
                                        backgroundColor: gradient,
                                        borderColor: "rgba(0, 0, 0, 1)",
                                        hoverBackgroundColor: gradient_hover
                                    }]
                                },
                                options: {
                                    title: {
                                        display: true,
                                        fontSize: 16,
                                        text: "Assessment Score Reliability (KR-20)                        ",
                                        position: "top"
                                    },
                                    scales: {
                                        xAxes: [{
                                            ticks: {
                                                min: 0,
                                                max: 1
                                            }
                                        }]
                                    },
                                    legend: {
                                        display: false
                                    }
                                }
                            });

                            chart_kr20_2            = document.getElementById("chart_kr20_2");
                            chart_kr20_2_ctx        = chart_kr20_2.getContext("2d");
                            chart_kr20_2_ctx.font   = "14px sans-serif";
                            chart_kr20_2_ctx.fillText("KR 20: " + kr20_value, 360, 20);
                        </script>
                        <?php
                    } // End Exam Performance

                    if ($category_performance) {
                        ?>
                        <h2 class="summary_header">Curriculum Tag Performance</h2>
                        <?php

                        if ($tags && is_array($tags) && !empty($tags)) {
                            if ($categories && is_array($categories) && !empty($categories)) {
                                $loop = 1;
                                foreach ($categories as $set_id => $set) {
                                    if (in_array($set_id, $tags)) {
                                        $loop++;
                                        $global_objective = Models_Objective::fetchRow($set_id);
                                        if ($global_objective) {
                                            echo "<h2 class=\"" . ($loop !== 1 ? "print-break" : "") . "\">" . $global_objective->getName() . "</h2>";
                                        }
                                        $set_names = array();
                                        $user_values = array();
                                        $class_values = array();
                                        $min_values = array();
                                        $max_values = array();
                                        if (!empty($set)) {
                                            ?>
                                            <canvas id="chart-<?php echo $set_id; ?>" class="exam-charts"></canvas>
                                            <script>
                                                var category_ctx = document.getElementById("chart-<?php echo $set_id;?>");
                                            </script>
                                            <table class="table table-bordered table-striped objectives-report">
                                                <thead>
                                                <tr>
                                                    <th>Curriculum Tag</th>
                                                    <th>Graph</th>
                                                    <th>Questions</th>
                                                    <th>Class Percent</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <?php
                                                ksort($set);
                                                foreach ($set as $objective) {
                                                    $post_category = $post->scoreCategory($objective);
                                                    $average = number_format($post_category->getAverage(), 2);
                                                    $min = number_format($post_category->getMin());
                                                    $max = number_format($post_category->getMax());

                                                    $percent_correct = $post_category->getPercentCorrect();
                                                    $total_possible = $post_category->getTotalQuestions();

                                                    $set_names[] = $objective->getName();
                                                    $class_values[] = $average;
                                                    $min_values[] = $min;
                                                    $max_values[] = $max;
                                                    ?>
                                                    <tr>
                                                        <td>
                                                            <?php echo $objective->getName(); ?>
                                                        </td>
                                                        <td>
                                                            <div class="slider-container">
                                                                <div class="slider-min-score<?php echo ($min >= 85 ? " min-dark" : ""); ?>"
                                                                     style="left: <?php echo ($min < 85 ? $min - 1 : 85); ?>%;">
                                                                    <span>
                                                                        Min: <?php echo $min; ?>
                                                                    </span>
                                                                </div>
                                                                <div class="slider-average-score"
                                                                     style="left: <?php echo $average - 1; ?>%;">
                                                                    <span class="fa-stack">
                                                                        <i class="fa fa-minus fa-rotate-90"></i>
                                                                        <i class="fa fa-stack-1x fa-users"></i>
                                                                    </span>
                                                                </div>
                                                                <div class="exam-slider">
                                                                    <canvas id="slider-<?php echo $objective->getID(); ?>"
                                                                            class="slider-canvas" data-min="<?php echo $min; ?>"
                                                                            data-max="<?php echo $max; ?>"
                                                                            data-id="<?php echo $objective->getID(); ?>"
                                                                            width="450" height="25"></canvas>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="category-question-count">
                                                            <?php echo($post_category ? $total_possible : "NA"); ?>
                                                        </td>
                                                        <td>
                                                            <?php echo($post_category ? $average . "%" : "NA"); ?>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                }
                                                ?>
                                                </tbody>
                                            </table>
                                            <div class="row-fluid" id="legend">
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
                                                    <canvas id="slider-legend" width="40" height="15"></canvas>
                                                    <span>Score Range</span>
                                                </div>
                                            </div>
                                            <script>
                                                var set_names = <?php echo json_encode($set_names); ?>;
                                                var class_values = <?php echo json_encode($class_values); ?>;
                                                var min_values = <?php echo json_encode($min_values); ?>;
                                                var max_values = <?php echo json_encode($max_values); ?>;
                                                var myRadarChart = new Chart(category_ctx, {
                                                    type: 'radar',
                                                    defaultFontSize: 14,
                                                    data: {
                                                        labels: set_names,
                                                        datasets: [
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

                                                jQuery(document).ready(function ($) {
                                                    $(".slider-canvas").each(function (key, value) {
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
                                                        canvas_obj.lineWidth = 1;
                                                        canvas_obj.stroke();
                                                        canvas_obj.fillStyle = "rgb(255,255,255)";
                                                        canvas_obj.fill();

                                                        // Builds the start section
                                                        if (calculated_min < 10 && calculated_max != 0) {
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

                                                    var canvas_obj = document.getElementById("slider-legend").getContext("2d");
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
                                                });
                                            </script>
                                            <?php
                                        }
                                    }
                                }
                            } else {
                                add_error("No Categories found linked to questions in this exam.");
                                echo display_error();
                            }
                        } else {
                            add_error("No Curriculum Tags chosen.");
                            echo display_error();
                        }

                    } // End Category Performance

                    if ($at_risk) {
                    ?>
                        <h2 class="summary_header print-break">Lowest 27 % of Learners</h2>
                        <table class="table table-bordered table-striped at-risk-report">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Learner</th>
                                <th>Score</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            if ($scores && is_array($scores) && !empty($scores)) {
                                $number_responses = count($scores);
                                $limit = (int)number_format(($number_responses * .27), 0);

                                $count = 1;
                                foreach ($scores as $key => $score) {
                                    if ($count <= $limit) {
                                        $user = Models_User::fetchRowByID($key);
                                        if ($user && is_object($user)) {
                                            $number = str_pad($user->getNumber(), 9, "0", STR_PAD_LEFT);
                                            ?>
                                            <tr>
                                                <td><?php echo $number; ?></td>
                                                <td><?php echo $user->getName(); ?></td>
                                                <td><?php echo $score; ?></td>
                                            </tr>
                                            <?php
                                        }
                                    }
                                    $count++;
                                }
                            }
                            ?>
                            </tbody>
                        </table>
                        <?php
                    } // End At Risk Section

                    if ($item_analysis) {
                        echo "<h2 class=\"summary_header print-break\">Item Analysis</h2>";
                        echo $exam_analysis_tables;
                    }
                }
                ?>
                <script>
                    jQuery(function ($) {
                        $("#sets-btn").advancedSearch({
                            api_url: API_URL,
                            resource_url: ENTRADA_URL,
                            filter_component_label: "Curriculum Tag Set",
                            filters: {
                                tags: {
                                    label: "Curriculum Tags",
                                    data_source: "get-category-report-sets",
                                    mode: "checkbox",
                                    select_all_enabled: true,
                                    api_params: {
                                        exam_id: function () {
                                            var exam_id = $("input[name=\"exam_id\"]");
                                            return exam_id.val();
                                        }
                                    }
                                }
                            },
                            control_class: "curriculum-selector",
                            no_results_text: "No Curriculum Tags Found.",
                            parent_form: $("#category-form"),
                            list_data: {
                                selector: "#curriculum-tag-container",
                                background_value: "url(../../images/list-community.gif) no-repeat scroll 0 4px transparent"
                            },
                            width: 500,
                            modal: false
                        });
                    });
                </script>
                <?php
            } else {
                // no posts found
                add_error("No Posts found.");
                echo display_error();
            }

        }
    } else {
        // exam not valid
    }
}