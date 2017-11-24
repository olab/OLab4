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
 * This file display the assignments to be graded by the current
 * logged in user
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */


if((!defined("PARENT_INCLUDED")) || (!defined("IN_COURSES"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$COURSE_ID) {
    add_error("No course ID defined.");
    exit;
} else {
    $course = Models_Course::fetchRowByID($COURSE_ID);

    $BREADCRUMB[] = array("url" => ENTRADA_URL . "/" . $MODULE . "?id=".$COURSE_ID, "title" => $course->getCourseName());
    $BREADCRUMB[] = array("url" => ENTRADA_URL . "/" . $MODULE . "?id=".$COURSE_ID . "section=grading", "title" => $translate->_("Grading tasks"));

    /**
     * Get assessments for which the current user is assigned to grade
     */
    $assessments = Models_Gradebook_Assessment::fetchAssessmentsByCourseIdAndGraderId($COURSE_ID, $ENTRADA_USER->getActiveId());
    if (!count($assessments)) {
        add_error("You are not assigned to mark any assessments for this course at the moment.");
        header("Location: ".ENTRADA_URL . "/" . $MODULE . "?id=" . $COURSE_ID);
        exit;
    } else if (count($assessments)==1) {
        header("Location: ".ENTRADA_URL . "/" . $MODULE . "?id=" . $COURSE_ID . "&section=grade&assessment_id=" . $assessments[0]["assessment_id"]);
        exit;
    }
    ?>

    <h2><?php echo $translate->_("Grading Tasks"); ?></h2>

    <?php
    foreach ($assessments as $assessment) :
        $assessment_utlities = new Entrada_Utilities_Assessment_Grade(
            $assessment,
            Models_Gradebook_Assessment_Graders::fetchLearnersProxyIdByAssessmentGrader($assessment["assessment_id"], $ENTRADA_USER->getActiveId()),
            $course->toArray()
        );

        ?>
        <a href="<?php echo ENTRADA_URL."/".$MODULE."?section=grade&id=".$COURSE_ID."&assessment_id=".$assessment["assessment_id"];?>" class="list-group-item table">
            <div class="table-cell-lg">
                <h3 class="title"><?php echo $assessment["name"]; ?></h3>
            </div>
            <div class="table-cell hidden-md hidden-sm hidden-xs">
                <h4 class="label">Due</h4>
            </div>
            <div class="table-cell hidden-md hidden-sm hidden-xs">
                <p><?php echo ($assessment["due_date"]!=0) ? date("m/d/Y", $assessment["due_date"]) : "-"; ?></p>
            </div>
            <div class="table-cell hidden-md hidden-sm hidden-xs">
                <h4 class="label">Submitted</h4>
            </div>
            <div class="table-cell hidden-md hidden-sm hidden-xs">
                <p<?php echo $assessment_utlities->getSubmittedTotal() ? "" : " class=\"disabled\""; ?>><?php echo $assessment_utlities->getSubmittedTotal(); ?>/<?php  echo $assessment_utlities->getTotalStudents(); ?></p>
            </div>
            <div class="table-cell hidden-xs">
                <h4 class="label">Graded</h4>
            </div>
            <div class="table-cell hidden-xs">
                <p<?php echo ($assessment_utlities->getSubmittedTotal() || !$assessment_utlities->getUnenteredGradesRatio()) ? "" : " class=\"disabled\""; ?>><?php echo $assessment_utlities->getFormattedEnteredGradesRatio(); ?></p>
            </div>
            <div class="table-cell">
                <span class="pull-right link-arrow glyphicon glyphicon-chevron-right icon-chevron-right"></span>
            </div>
        </a>
    <?php
    endforeach;

}