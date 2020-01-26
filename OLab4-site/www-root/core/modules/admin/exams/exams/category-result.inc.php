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
 * @author Developer: Samuel Payne <spayne@mednet.ucla.edu>
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

    if (isset($_GET["post_id"]) && $tmp_input = clean_input($_GET["post_id"], "int")) {
        $PROCESSED["post_id"] = $tmp_input;
    }

    $MODULE_TEXT = $translate->_("exams");
    $DEFAULT = $translate->_("default");
    $SECTION_TEXT = $MODULE_TEXT["exams"]["reports"]["category"];


    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_RELATIVE . "/javascript/jquery/jquery.dataTables.min-1.10.1.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_RELATIVE . "/javascript/jquery/dataTables.colVis.min.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<link rel='stylesheet' type='text/css' href='" . ENTRADA_RELATIVE . "/css/jquery/jquery.dataTables.css?release=" . html_encode(APPLICATION_VERSION) . "'>";
    $HEAD[] = "<link rel='stylesheet' type='text/css' href='" . ENTRADA_RELATIVE . "/css/jquery/dataTables.colVis.css?release=" . html_encode(APPLICATION_VERSION) . "'>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/exams/reports/category.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/exams/reports/category-result.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";

    if (isset($PROCESSED["post_id"])) {
        $post = Models_Exam_Post::fetchRowByID($PROCESSED["post_id"]);
        if ($post) {
            $exam = $post->getExam();
            $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/exams?section=edit-exam&id=".$exam->getID(), "title" => $exam->getTitle());
            $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/exams?section=reports&id=".$exam->getID(), "title" => "Reports");
            $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/exams?section=category&id=".$exam->getID(), "title" => "Category");
            $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/exams?section=category&id=".$exam->getID(), "title" => $post->getTitle());
            /**
             * @todo add specific ACL check for the category report
             */
            if ($ENTRADA_ACL->amIAllowed(new ExamResource($ENTRADA_USER->getID(), true), "update")) {
                $category_report = Models_Exam_Category::fetchRowByPostID($post->getID());
                if ($category_report) {

                    $category_report_categories = $category_report->getSets();
                    $category_sets = array();
                    $categories = array();

                    if (!empty($category_report_categories)){
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
                                        $categories[$set][] = $objective_id;
                                    }
                                }
                            }
                        }
                        ?>
                        <h1><?php echo html_encode($post->getTitle()); ?></h1>
                        <h2>Category Performance Summary</h2>

                        <table class="table table-bordered table-striped category-performance-summary">
                            <thead>
                            <tr>
                                <th>Exam Takers</th>
                                <th>Stdev</th>
                                <th>Mean</th>
                                <th>Median</th>
                                <th>Min</th>
                                <th>Max</th>
                                <th>Total Points</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td><?php echo $post->getProgressScoresTotal(); ?></td>
                                <td><?php echo number_format($post->getStandardDeviation(), 2); ?></td>
                                <td><?php echo number_format($post->getMean(), 2); ?></td>
                                <td><?php echo number_format($post->getMedian(), 2); ?></td>
                                <td><?php echo number_format($post->getProgressScoresMin(), 2); ?></td>
                                <td><?php echo number_format($post->getProgressScoresMax(), 2); ?></td>
                                <td><?php echo number_format($post->getExamTotalPoints(), 2); ?></td>
                            </tr>
                            </tbody>
                        </table>
                        <?php
                        $displayed_objectives = array();
                        foreach ($categories as $set_id => $set) {
                            $global_objective = Models_Objective::fetchRow($set_id);
                            if ($global_objective) {
                                echo "<h2>" . $global_objective->getName() . "</h2>";
                            }
                            if (!empty($set)) {
                                ?>
                                <div class="colums">
                                    <span></span>
                                </div>
                                <table class="table table-bordered table-striped objectives-report">
                                    <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th># of Items</th>
                                        <th># Correct</th>
                                        <th>% Correct</th>
                                        <th># Incorrect</th>
                                        <th>% Incorrect</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    foreach ($set as $objective_id) {
                                        $id = $objective_id;
                                        $post_id = $post->getID();
                                        $global_objective = Models_Objective::fetchRow($objective_id);
                                        $category = $post->scoreCategory($global_objective);
                                        if ($global_objective) {
                                            ?>
                                            <tr>
                                                <td><?php echo $global_objective->getName(); ?></td>
                                                <td><?php echo $category->getTotalQuestions(); ?></td>
                                                <td><?php echo $category->getTotalCorrect(); ?></td>
                                                <td><?php echo $category->getPercentCorrect(); ?>%</td>
                                                <td><?php echo $category->getTotalIncorrect(); ?></td>
                                                <td><?php echo $category->getPercentIncorrect(); ?>%</td>
                                            </tr>
                                            <?php
                                        }
                                    }
                                    ?>
                                    </tbody>
                                </table>
                                <?php
                            }
                        }
                    } else {
                        add_error("There were no objective sets selected to display for this category report");
                        echo display_error();
                    }
                } else {
                    add_error($SECTION_TEXT["errors"]["invalid_category_report_id"]);

                    echo display_error();

                    application_log("error", $SECTION_TEXT["errors"]["invalid_category_report_id"]);
                }

            } else {
                add_error(sprintf($translate->_("Your account does not have the permissions required to view this exam category report.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

                echo display_error();

                application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] does not have access to this exam post [" . $post->getID() . "]");
            }
        } else {
            add_error($SECTION_TEXT["errors"]["invalid_post_id"]);

            echo display_error();

            application_log("error", $SECTION_TEXT["errors"]["invalid_post_id"]);
        }
    } else {
        add_error($SECTION_TEXT["errors"]["invalid_post_id"]);

        echo display_error();

        application_log("error", $SECTION_TEXT["errors"]["invalid_post_id"]);
    }
}