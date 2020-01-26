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
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Harry Brundage <hbrundage@qmed.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_GRADEBOOK"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("gradebook", "update", false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:" . html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" . html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance . ");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	ob_clear_open_buffers();
	if ($COURSE_ID) {

		$course = Models_Course::fetchRowByID($COURSE_ID);
		
		if ($course && $ENTRADA_ACL->amIAllowed(new GradebookResource($course->getID(), $course->getOrganisationID()), "read")) {
						
			if (isset($_GET["cohort"]) && ($tmp_input = clean_input($_GET["cohort"], "int"))) {
				$COHORT = $tmp_input;
			}			
				
			$PREFERENCES = preferences_load("courses");
            $curriculum_periods = Models_Curriculum_Period::fetchAllByCurriculumTypeIDCourseID($course->getCurriculumTypeID(), $course->getID());

            if ($curriculum_periods && is_array($curriculum_periods) && count($curriculum_periods) == 1) {
                $period = $curriculum_periods[0];
                if ($period && is_object($period)) {
                    $PREFERENCES["selected_curriculum_period"] = $period->getID();
                    preferences_update("courses", $PREFERENCES);
                }
            }

			$CPERIOD_ID = $PREFERENCES["selected_curriculum_period"];

			$curriculum_period = Models_Curriculum_Period::fetchRowByID($CPERIOD_ID);

			$model_assessment = new Models_Gradebook_Assessment();
			$assessments = $model_assessment->fetchAssessmentsByCurriculumPeriodIDWithMarkingScheme($COURSE_ID, $CPERIOD_ID);

			if($assessments) {

				// Create CSV header row
				$header_row = array($translate->_("Full Name"), $translate->_("Number"));

				// Add each assessment to the header row
				foreach($assessments as $assessment) {
					$header_row[] = $assessment["name"];
				}

				// Process next columns
				$header_row[] = $translate->_("Weighted Total");

				$editable_class = $ENTRADA_ACL->amIAllowed(new GradebookResource($course->getID(), $course->getOrganisationID()), "update") ? "gradebook_editable" : "gradebook_not_editable";
				// Get search bar
				$search_bar = new Views_Gradebook_SearchBar(array("id" => "search-learners", "class" => "spreadsheet-search-learners", "placeholder" => $translate->_("Search Learners")));
				?>
				<div class="row-fluid">
					<div class="span9" style="padding-left:20px;">
						<h3><?php echo $course->getFullCourseTitle(); ?><span class="spreadsheet-modal-cperiod"><?php echo $curriculum_period->getDateRangeString(); ?></span</h3>
					</div>
					<div class="span1 pull-right text-right" style="padding: 20px 20px 0 0;">
						<span class="gradebook_modal_close btn btn-primary">Close</span>
					</div>
					<div class="span2 pull-right text-right" style="padding-top: 20px;">
						<?php $search_bar->render(); ?>
					</div>
				</div>
				<table id="datatable-student-list" class="spreadsheet-data-table table table-striped table-bordered dataTable no-footer <?php echo $editable_class; ?>" width="100%" role="grid" style="width: 100%;">
				<thead>
                    <tr>
				<?php
				foreach ($header_row as $row) {
    				?>
	       				<th><?php echo $row; ?></th>
    				<?php } ?>
                    </tr>
				</thead>
				<tbody>
				<?php

				// Get student list

				// Get array of IDs for next query
				$student_ids = $course->getStudentIDs($CPERIOD_ID);
				// Get assessment marks and any grade weighting exceptions for each student
				$assessment_marks = $model_assessment->fetchStudentMarksAndGradeWeightingsPerAssessment($assessments, $student_ids, "number", true, true);
				// add in assessment marks per student
                if ($assessment_marks && is_array($assessment_marks)) {
				foreach($assessment_marks as $i => $student) {
					// start off the row with the student number and name
					$csv_student_row = array($student["fullname"], $student["number"]);

					// start with a weighted total of 0
					$weighted_total = 0;

					// for each assessment, get the formatted grade (8/10 instead of 80%) and grade weighting
					foreach($assessments as $i => $assessment) {
						$csv_student_row["b" . $i . "grade"]["mark"] = format_retrieved_grade($student["b" . $i . "grade"], $assessment);
						$csv_student_row["b" . $i . "grade"]["id"] = $student["b" . $i . "grade_id"];
						$csv_student_row["b" . $i . "grade"]["assessment"] = $i;

						// Get the grade_weighting either from the student if one exists or the assessment standard weighting
						$grade_weighting = $student["c" . $i . "weight"] ? $student["c" . $i . "weight"] : $assessment["grade_weighting"];

						// Add to the weighted total
						$weighted_total += ($student["b" . $i . "grade"] * $grade_weighting) / 100;
					}

					// Set the weighted total for this row
					$csv_student_row["weighted_total"] = $weighted_total;

					// Get "has-form" class for table

					echo "<tr>";
					foreach ($csv_student_row as $key => $csv_student_row_arr) {
						if (strpos($key, "grade") == true) {
							$has_form_class = $assessments[$csv_student_row[$key]["assessment"]]["form_id"] ? "has-form" : "no-form";
							?>
							<td class="editable <?php echo $has_form_class;?>">
								<span class="grade pull-left grade-editable <?php echo $assessments[$csv_student_row[$key]["assessment"]]["form_id"] ? "" : "grade-no-form" ;?> "
                                          id="<?php echo "grade_" . $assessments[$csv_student_row[$key]["assessment"]]["assessment_id"] . "_" . $student["id"]; ?>"
									  data-grade-id="<?php echo $csv_student_row[$key]["id"]; ?>"
									  data-assessment-id="<?php echo $assessments[$csv_student_row[$key]["assessment"]]["assessment_id"]; ?>"
									  data-type="grade"
									  data-proxy-id="<?php echo $student["id"]; ?>"
									  data-course-id="<?php echo $assessments[$csv_student_row[$key]["assessment"]]["assessment_id"] ?>"
									  data-organisation-id="<?php echo $ENTRADA_USER->getActiveOrganisation(); ?>"
									  data-assignment-id="<?php echo $assessments[$csv_student_row[$key]["assessment"]]["assignment_id"] ?>"
									  data-grade-value="<?php echo $csv_student_row[$key]["mark"]; ?>"
									  data-formatted-grade="<?php echo $csv_student_row[$key]["mark"]; ?>"
									  data-form-id="<?php echo $assessments[$csv_student_row[$key]["assessment"]]["form_id"]; ?>"
									  data-portfolio-id="<?php echo $assessments[$csv_student_row[$key]["assessment"]]["portfolio_id"]; ?>"
									  data-assignment-title="<?php echo $assessments[$csv_student_row[$key]["assessment"]]["name"]; ?>"
									  data-student-name="<?php echo $student["fullname"]; ?>"><?php echo $csv_student_row_arr["mark"] ?></span>
							<span class="gradesuffix pull-left <?php echo ($csv_student_row_arr["mark"]) ? "" : "hide" ;?>"><?php echo assessment_suffix($assessments[$csv_student_row[$key]["assessment"]]); ?></span>
							</td>
							<?php
						} else {
							?>
							<td><?php echo $csv_student_row_arr ?></td>
							<?php
						}
					}
					echo "</tr>";
                        }
                    }
				?>
				</tbody>
				</table>
				<?php if (count($student_ids) === 0) { ?>
				<div class="display-notice">There are no students in the system for this cohort [<strong><?php echo groups_get_name($COHORT); ?></strong>].</div>
				<?php } ?>
			<?php
			} else {
				echo "<table class=\"gradebook\"></table>";
				add_notice("No assessments could be found for this gradebook for this cohort [" . groups_get_name($COHORT) . "] . ");

				echo display_notice();
			}
		} else {
			echo "<table class=\"gradebook\"></table>";
			
			add_error("In order to edit a course you must provide a valid course identifier. The provided ID does not exist in this system . ");

			echo display_error();

			application_log("notice", "Failed to provide a valid course identifer when attempting to view a gradebook");
		}
	} else {
		echo "<table class=\"gradebook\"></table>";
		
		add_error("In order to edit a course you must provide the courses identifier . ");

		echo display_error();

		application_log("notice", "Failed to provide course identifer when attempting to view a gradebook");
	}
}

exit;