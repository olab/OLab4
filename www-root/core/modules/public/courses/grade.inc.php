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
require_once("Entrada/gradebook/handlers.inc.php");

if((!defined("PARENT_INCLUDED")) || (!defined("IN_COURSES"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$COURSE_ID) {
    add_error("No course ID defined.");
    exit;
} else {
    if (isset($_GET["assessment_id"]) && $tmp_input = clean_input($_GET["assessment_id"], array("trim", "int"))) {
        $ASSESSMENT_ID = $tmp_input;
    } else {
        add_error("No assessment id");
        exit;
    }

    $course = Models_Course::fetchRowByID($COURSE_ID);
    $assessment_model = new Models_Gradebook_Assessment(array("assessment_id" => $ASSESSMENT_ID));

    $BREADCRUMB[] = array("url" => ENTRADA_URL . "/" . $MODULE . "?id=".$COURSE_ID, "title" => $course->getCourseName());
    $BREADCRUMB[] = array("url" => ENTRADA_URL . "/" . $MODULE . "?id=".$COURSE_ID . "&section=grading", "title" => $translate->_("Grading tasks"));
    $BREADCRUMB[] = array("url" => ENTRADA_URL . "/" . $MODULE . "?id=".$COURSE_ID . "&section=grade&assessment_id=".$ASSESSMENT_ID, "title" => $assessment_model->getName());

    $learners = Models_Gradebook_Assessment_Graders::fetchLearnersProxyIdByAssessmentGrader($assessment_model->getAssessmentId(), $ENTRADA_USER->getActiveId());

    array_unshift($HEAD,
        // Mark assignments modal and page-specific
        '<link href="'.ENTRADA_URL.'/css/gradebook/mark-assignment.css" rel="stylesheet" />',
        '<link href="'.ENTRADA_URL.'/css/assessments/assessments.css" rel="stylesheet" />',
        '<script src="'.ENTRADA_URL.'/javascript/gradebook/mark-assignment.js"></script>'
    );

    if (!count($learners)) {
        add_error("You are not assigned to grade any learners for that assessment.");
        exit();
    }

    $assessment = $assessment_model->fetchAssessmentByIDWithMarkingSchemeMetaAndAssignment();

    $assessment_utlities = new Entrada_Utilities_Assessment_Grade(
        $assessment,
        $learners,
        $course->toArray()
    );

    $students = $assessment_utlities->getAssessmentStudents();

    // Generate modal for marking assignments
    $mark_assignment_modal = new Views_Gradebook_Modal(array(
        "id" 	=> "modal-mark-assignment",
        "class" => "modal-mark-assignment fullscreen-modal",
        "success_button" => array(
            "text" => $translate->_("Save assignment marks"),
            "class" => "btn-primary btn-save-assignment"
        ),
        "dismiss_button" => $translate->_("Close")
    ));



    if ($assessment["form_id"]) {
        $JQUERY[] = "<script>var FORM_ID = ".$assessment["form_id"].", ENTRADA_URL = '".ENTRADA_URL."';</script>";
    }

    // Get "has-form" class for table
    $has_form_class = $assessment["form_id"] ? " class=\"has-form\"" : "";

    ?>
    <h2><?php echo $translate->_("Students to be Graded"); ?></h2>

    <div<?php echo $has_form_class;?>>
    <?php
        foreach ($students as $proxy_id => $student) :
            $user = Models_User::fetchRowByID($proxy_id);
            ?>
            <a href="#modal-mark-assignment" class="list-group-item table editable grade-editable">
                <div class="grade hide <?php echo $assessment["form_id"] ? "open-modal-mark-assignment" : "no-form"; ?>" id="grade_<?php echo $assessment["assessment_id"]."_".$proxy_id; ?>" data-grade-id="<?php echo $student["b0grade_id"]; ?>" data-assessment-id="<?php echo $assessment["assessment_id"]; ?>" data-type="grade" data-proxy-id="<?php echo $proxy_id; ?>" data-course-id="<?php echo $COURSE_ID; ?>" data-organisation-id="<?php echo $course->getOrganisationID();?>" data-assignment-id="<?php echo $assessment["assignment_id"]; ?>" data-grade-value="<?php echo $student["b0grade"]; ?>" data-formatted-grade="<?php echo format_retrieved_grade($student["b0grade"]["value"], $assessment); ?>" data-form-id="<?php echo $assessment["form_id"]; ?>" data-assignment-title="<?php echo $assessment["name"]; ?>" data-student-name="<?php echo $user->getFullname(); ?>"></div>
                <div class="table-cell">
                    <span class="circle circle-img-xs">
                        <span class="glyphicon glyphicon-user icon-user"></span>
                    </span>
                </div>
                <div class="table-cell-lg">
                    <div class="table-cell-lg">
                        <h3 class="title"><?php echo $user->getFullname(); ?></h3>
                    </div>
                </div>
                <div class="table-cell hidden-xs">
                    <h4 class="label">Student #</h4>
                </div>
                <div class="table-cell hidden-xs">
                    <p><?php echo $user->getNumber(); ?></p>
                </div>
                <div class="table-cell">
                    <span class="pull-right link-arrow glyphicon glyphicon-chevron-right icon-chevron-right"></span>
                </div>
            </a>
        <?php
        endforeach;
    ?>
    </div>

    <?php
    if ($assessment["form_id"]) {
        // Mark assignment
        $mark_assignment_modal->setHeaderContent('<div class="pull-left selector-documents"></div>');
        $mark_assignment_modal->setBody('
					<div class="loading"><img src="'.ENTRADA_URL.'/images/loading.gif" alt="Loading..." /></div>
			      	<div class="container-fluid">
			      		<div class="file"></div>
			        	<div class="marking-scheme"></div>
			      	</div>
				');
        $mark_assignment_modal->setFooterContent('
					<ul class="inline">
						<li>
							<strong class="calculated-grade-text">'.$translate->_("Calculated Grade: ").'<span class="calculated-grade"></span></strong>
						</li>
						<li>
							<div class="custom-grade form-inline">
								<input type="checkbox" name="custom-grade" id="custom-grade" class="custom-grade">
								<label for="custom-grade">Custom Grade</label>
								<input type="text" id="custom-grade-value" class="custom-grade-value" name="custom-grade-value" value="">
								<span class="assessment-suffix">'.assessment_suffix($assessment).'</span>
							</div>
						</li>
					</ul>
				');
        $mark_assignment_modal->render();
    }
}