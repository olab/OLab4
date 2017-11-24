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
 * Set of utility methods for generating a list of student grades
 * in the context of an assessment.
 * 
 * @author Organization: bitHeads, Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 * 
 */

class Entrada_Utilities_Assessment_Grade {
	protected $assessment, $student_ids = array(), $course, $raw_student_results, $students, $latest_assignment, $assessment_options, $option_values, $aoption_ids = array(), $resubmissions = array("tracking" => false, "aoption_id" => null), $late_submissions = array("tracking" => false, "aoption_id" => null), $columns = array();

	public function __construct($assessment, $student_ids, $course) {
		$this->assessment = $assessment;
		$this->course = $course;

		$assessment_model = new Models_Gradebook_Assessment();
		$this->raw_student_results = $assessment_model->fetchStudentMarksAndGradeWeightingsPerAssessment(array(array("assessment_id" => $this->assessment["assessment_id"])), $student_ids, "name", true, true);

		if ($this->raw_student_results && is_array($this->raw_student_results)) {

			// We use an array map to set new array of student IDs 
			// so that they match the order of the queried students
			$this->student_ids = array_map(function($student) {
				return $student["id"];
			}, $this->raw_student_results);

			// Create associative array of proxy_id => student_data
			$this->students = array_combine($this->student_ids, $this->raw_student_results);

			// Setup assessment options and columns
	        $this->setupAssessmentOptions();
	        $this->setupColumns();
		}
        
	}

	/**
	 * Sets up the data to check if we are tracking resubmissions and late submissions.
	 * @return void
	 */
	public function setupAssessmentOptions() {
		$this->assessment_options = Models_Gradebook_Assessment_Option::fetchAllByAssessmentID($this->assessment["assessment_id"], true);

		if ($this->assessment_options) {

			foreach($this->assessment_options as $option) {
                $this->aoption_ids[] = $option->getAOptionID();

                if ($option->getOptionID() == 6) {
                    $this->resubmissions["tracking"] = true;
                    $this->resubmissions["aoption_id"] = $option->getAOptionID();
                }

                if ($option->getOptionID() == 5) {
                    $this->late_submissions["tracking"] = true;
                    $this->late_submissions["aoption_id"] = $option->getAOptionID();
                }
            }
		}
	}

	public function setupColumns() {
		global $translate;

		$this->columns = array(
			"learner" => $translate->_("Learner"),
			"student_number" => $translate->_("Student Number"),
			"grade" => $translate->_("Grade"),
			"submitted" => $translate->_("Submitted")
		);

		if ($this->assessment["group_assessment"]) {
			$this->columns["group"] = $translate->_("Group");
		}

		if ($this->isTrackingResubmissions()) {
        	$this->columns["resubmissions"] = $translate->_("Resubmissions");
        }

        if ($this->isTrackingLateSubmissions()) {
        	$this->columns["late_submissions"] = $translate->_("Late Submissions");
        }
	}

	/**
	 * Attaches the group the student is part of, if this is a group assessment
	 * @return void
	 */
	public function attachGroupToEachStudent() {
		if ($this->assessment["group_assessment"]) {
			$assessment_model = new Models_Gradebook_Assessment(array("assessment_id" => $this->assessment["assessment_id"]));
			$audience_groups = $assessment_model->fetchAudienceInGroups();

			if ($audience_groups && is_array($audience_groups)) {
				foreach($audience_groups as $audience_group) {
					if (array_key_exists($audience_group["proxy_id"], $this->students)) {
						$this->students[$audience_group["proxy_id"]]["group"] = $audience_group;
					}
				}
			}
		}
	}

	/**
	 * Attaches the latest assignment each student uploaded, if any
	 * @return void
	 */
	public function attachLatestAssignmentToEachStudent() {
		if ($this->students) {
			foreach($this->students as $proxy_id => $student) {
				$assignment_model = new Models_Assignment_File_Version(array("assignment_id" => $this->assessment["assignment_id"], "proxy_id" => $proxy_id));
                $latest_assignment = $assignment_model->fetchMostRecentAssignmentFile();

                if ($latest_assignment) {
                    $this->students[$proxy_id]["submitted"] = $latest_assignment->getUpdatedDate();
                }
                else {
                    $this->students[$proxy_id]["submitted"] = null;
                }
			}
		}
	}

	/**
	 * Attaches the assessment option values to each student
	 * @return void
	 */
	public function attachAssessmentOptionValuesToEachStudent() {
		if (count($this->aoption_ids) && $this->students) {
			$this->option_values = Models_Gradebook_Assessment_Option_Value::fetchAllByAOptionIDsStudentIDs($this->aoption_ids, $this->student_ids);

            if ($this->option_values) {

            	// the resubmissions and late submissions are added in separate loops to guarantee the right order
                foreach($this->option_values as $value) {
                    if ($value->getAOptionID() == $this->resubmissions["aoption_id"]) {
                    	$this->students[$value->getProxyID()]["resubmissions"]["aoption_id"] = $value->getAOptionID();
                    	$this->students[$value->getProxyID()]["resubmissions"]["aovalue_id"] = $value->getAovalueID();
                        $this->students[$value->getProxyID()]["resubmissions"]["value"] = $value->getValue();
                    }
                }

                foreach($this->option_values as $value) {
                	if ($value->getAOptionID() == $this->late_submissions["aoption_id"]) {
                        $this->students[$value->getProxyID()]["late_submissions"]["aoption_id"] = $value->getAOptionID();
                        $this->students[$value->getProxyID()]["late_submissions"]["aovalue_id"] = $value->getAovalueID();
                        $this->students[$value->getProxyID()]["late_submissions"]["value"] = $value->getValue();
                    }
                }
            }
		}
	}

	/**
	 * Since we need the same number of columns in each student entry, we supply empty values for missing fields
	 * @return void
	 */
	public function attachExtraDefaultValuesToEachStudent() {
		if ($this->students) {
			foreach($this->students as $proxy_id => $student) {

		        if ($this->resubmissions["tracking"] && !array_key_exists("resubmissions", $this->students[$proxy_id])) {
		        	$this->students[$proxy_id] = $this->array_insert_before("late_submissions", $this->students[$proxy_id], "resubmissions", null);
		        }

		        if ($this->assessment["group_assessment"] && !array_key_exists("group", $this->students[$proxy_id])) {
		        	$this->students[$proxy_id] = $this->array_insert_before("resubmissions", $this->students[$proxy_id], "group", null);
		        }

		        if ($this->late_submissions["tracking"] && !array_key_exists("late_submissions", $this->students[$proxy_id])) {
		            $this->students[$proxy_id]["late_submissions"] = null;
		        }
		    }
		}
	}

	/**
	 * Inserts at a specific spot in an array
	 * @param  string 		$key       	Which key you want to insert before
	 * @param  array  		&$array    	Array in which you want to add a key/value
	 * @param  string 		$new_key   	New Key
	 * @param  any 			$new_value 	New Value
	 * @return array|false       		New array or false if somethign went wrong
	 */
	public function array_insert_before($key, array &$array, $new_key, $new_value) {
		if (array_key_exists($key, $array)) {
	    	$new = array();
	    	foreach ($array as $k => $value) {
		    	if ($k === $key) {
		        	$new[$new_key] = $new_value;
		      	}
		      	$new[$k] = $value;
		    }
		    
		    return $new;
		}
		
		$array[$new_key] = $new_value;
		return $array;
	}

	/**
	 * Implement display formatting for specific table cells
	 * @return void
	 */
	public function formatDataForDisplayForEachStudent() {
		if ($this->students) {
			foreach($this->students as $proxy_id => $student) {
				$this->students[$proxy_id]["submitted"] = $this->formatSubmissionPresentation($proxy_id, $student);

				$this->students[$proxy_id]["b0grade"] = array();
				$this->students[$proxy_id]["b0grade"]["content"] = $this->formatGradePresentation($proxy_id, $student);
				if (Models_Gradebook_Assessment_Graders::canGradeAssessment($proxy_id, $this->assessment["assessment_id"])) {
					$this->students[$proxy_id]["b0grade"]["class"] = "editable grade-editable";
				}
				$this->students[$proxy_id]["b0grade"]["value"] = $student["b0grade"];

				if ($this->assessment["group_assessment"]) {
					$group_info = $this->students[$proxy_id]["group"];
					$this->students[$proxy_id]["group"] = $group_info["group_name"];
				}

				if ($this->isTrackingResubmissions()) {
					$this->students[$proxy_id]["resubmissions"] = array();
					$this->students[$proxy_id]["resubmissions"]["content"] = $this->formatResubmissionPresentation($proxy_id, $student);
					$this->students[$proxy_id]["resubmissions"]["class"] = "resubmissions editable";
				}

				if ($this->isTrackingLateSubmissions()) {
					$this->students[$proxy_id]["late_submissions"] = array();
					$this->students[$proxy_id]["late_submissions"]["content"] = $this->formatLateSubmissionPresentation($proxy_id, $student);
					$this->students[$proxy_id]["late_submissions"]["class"] = "late-submission";
				}
			}
		}
	}

	/**
	 * Format the grade cell for presentation
	 * @param  int 		$proxy_id
	 * @param  array 	$student 	Array of all queried info for a particular student
	 * @return string   $html
	 */
	public function formatGradePresentation($proxy_id, $student) {
		$html = array();
				 
		$html[] = '<span class="grade pull-left '.($this->assessment["form_id"] ? "open-modal-mark-assignment" : "no-form").' '.($student["group"] ? "in-group in-group-".$student["group"]["cgroup_id"] : "").'" id="grade_'.$this->assessment["assessment_id"].'_'.$proxy_id.'" data-grade-id="'.$student["b0grade_id"].'" data-assessment-id="'.$this->assessment["assessment_id"].'" data-type="grade" data-proxy-id="'.$proxy_id.'" data-course-id="'.$this->course["course_id"].'" data-organisation-id="'.$this->course['organisation_id'].'" data-assignment-id="'.$this->assessment["assignment_id"].'" data-grade-value="'.$student["b0grade"].'" data-formatted-grade="'.format_retrieved_grade($student["b0grade"], $this->assessment).'" data-form-id="'.$this->assessment["form_id"].'" data-assignment-title="'.$this->assessment["assignment_title"].'" data-student-name="'.$student["fullname"].'" '.($student["group"] ? 'data-group-name="'.$student["group"]["group_name"].'" data-group-id="'.$student["group"]["cgroup_id"].'"' : "").'>';
		$html[] = !is_null($student["b0grade"]) ? format_retrieved_grade($student["b0grade"], $this->assessment) : "";
		$html[] = '</span>';
		$html[] = '<span class="gradesuffix pull-left '.(is_null($student["b0grade"]) ? "hide" : "" ).'">';
		$html[] = assessment_suffix($this->assessment);
		$html[] = '</span>';

		return implode("\n", $html);
	}

	/**
	 * Format the resubmissions cell for presentation
	 * @param  int 		$proxy_id
	 * @param  array 	$student 	Array of all queried info for a particular student
	 * @return string   $html
	 */
	public function formatResubmissionPresentation($proxy_id, $student) {
		$html = array();
				 
		$html[] = '<span class="resubmission pull-left '.($this->assessment["form_id"] ? "has-form" : "no-form").'" id="resubmit_'.$this->assessment["assessment_id"].'_'.$proxy_id.'" data-aoption-id="'.$this->resubmissions["aoption_id"].'" data-assessment-id="'.$this->assessment["assessment_id"].'" data-type="resubmission" data-proxy-id="'.$proxy_id.'" data-aovalue-id="'.$student["aovalue_id"].'">';
		$html[] = $student["resubmissions"]["value"] > 0 ? $student["resubmissions"]["value"] : "";
		$html[] = '</span>';

		return implode("\n", $html);
	}

	/**
	 * Format the submitted cell for presentation
	 * @param  int 		$proxy_id
	 * @param  array 	$student 	Array of all queried info for a particular student
	 * @return string   $html
	 */
	public function formatSubmissionPresentation($proxy_id, $student) {
		$html = array();
		if ($student["submitted"]) {
            $html[] = '<span data-submitted-date="' . $student["submitted"] . '" class="' . ($student["submitted"] < $this->assessment["due_date"] ? "color-red" : "") . '">';
            $html[] = date("r", $student["submitted"]);
            $html[] = '</span>';
        }
		return implode("\n", $html);
	}

	/**
	 * Format the late submission cell for presentation
	 * @param  int 		$proxy_id
	 * @param  array 	$student 	Array of all queried info for a particular student
	 * @return string   $html
	 */
	public function formatLateSubmissionPresentation($proxy_id, $student) {
		$html = array();

		$html[] = '<div class="text-center">';
		$html[] = '<input type="checkbox" data-aoption-id="'.$this->late_submissions["aoption_id"].'" data-proxy-id="'.$proxy_id.'" data-aovalue-id="'.$student["late_submissions"]["aovalue_id"].'" '.($student["late_submissions"]["value"] ? 'checked="checked"' : '').'>';
		$html[] = '</div>';

		return implode("\n", $html);
	}

	/**
	 * Returns the full formed student list, ready for display in a table
	 * @return array
	 */
	public function getAssessmentStudents() {
		$this->attachLatestAssignmentToEachStudent();
		$this->attachGroupToEachStudent();
		$this->attachAssessmentOptionValuesToEachStudent();
		$this->attachExtraDefaultValuesToEachStudent();
		$this->formatDataForDisplayForEachStudent();

		return $this->students;
	}

	/**
	 * Checks if this assessment is tracking resubmissions. Must be run after setupAssessmentOptions()
	 * @return boolean
	 */
	public function isTrackingResubmissions() {
		return $this->resubmissions["tracking"];
	}

	/**
	 * Checks if this assessment is tracking late submissions. Must be run after setupAssessmentOptions()
	 * @return boolean
	 */
	public function isTrackingLateSubmissions() {
		return $this->late_submissions["tracking"];
	}

	/**
	 * Reorganizes the column array for display in a datatable
	 * @return array
	 */
	public function getColumnsForDataTable() {
		return array_map(function($key, $column) {

			if ($key == "learner" || $key == "student_number") {
				return array(
	    			"name" => $column,
	    			"width" => "25%"
	    		);
			}
			
			return $column;

    	}, array_keys($this->columns), $this->columns);
	}

	/**
	 * Calculates the number of unentered grades
	 * @return int
	 */
	public function getUnenteredGrades() {
		if ($this->raw_student_results) {

			$unentered = 0;

			foreach($this->raw_student_results as $student) {
				if (!isset($student["b0grade"]) || !$student["b0grade"]) {
					$unentered++;
				}
			}

			return $unentered;
		}
	}

	/**
	 * Get the ratio of unentered grades
	 * @return float
	 */
	public function getUnenteredGradesRatio() {
		return $this->getUnenteredGrades() / count($this->students);
	}

	/**
	 * Get the ratio of entered grades
	 * @return float
	 */
	public function getEnteredGradesRatio() {
		return 1 - $this->getUnenteredGradesRatio();
	}

	/**
	 * Get the ratio of unentered grades
	 * @return float
	 */
	public function getFormattedEnteredGradesRatio() {
		return (count($this->students) - $this->getUnenteredGrades()) . "/" . count($this->students);
	}

	/**
	 * Get the total number of students for that assessment
	 * @return int
	 */
	public function getTotalStudents() {
		return count($this->students);
	}

	public function getSubmittedTotal() {
		$submitted = 0;

		foreach($this->students as $proxy_id => $student) {
			$assignment_model = new Models_Assignment_File_Version(array("assignment_id" => $this->assessment["assignment_id"], "proxy_id" => $proxy_id));
			$latest_assignment = $assignment_model->fetchMostRecentAssignmentFile();

			if ($latest_assignment) {
				$submitted++;
			}
		}

		return $submitted;
	}

	/**
	 * Generates graph handler-specific data and stats
	 * @return array
	 */
	public function renderGraphDataJSON() {
		if ($this->raw_student_results) {
			if ($this->assessment["handler"] == "Boolean" || $this->assessment["handler"] == "IncompleteComplete") {
				return $this->renderBooleanGraphDataJSON();
			}
			else if ($this->assessment["handler"] == "Percentage" || $this->assessment["handler"] == "Numeric") {
				return $this->renderPercentageGraphDataJSON();
			}
		}
	}

	/**
	 * Generates graph data and stats for boolean-type handlers
	 * @return array
	 */
	public function renderBooleanGraphDataJSON() {
		$grades = array();

		foreach($this->raw_student_results as $i => $student) {
			if (!is_null($student["b0grade"])) {

				// This hard-coded logic should be in gradebook/handlers
				if ($student["b0grade"] > 50) {
					$grades[0]++;
				}
				else {
					$grades[1]++;
				}
			}
		}

		$grade_data = array();
		foreach($grades as $key => $value) {
			$grade_data[] = array($key, $value);
		}

		$xticks = array(
			array(
				"v" => 0,
				"label" => $this->assessment["handler"] == "IncompleteComplete" ? "Complete" : "Pass"
			),
			array(
				"v" => 1,
				"label" => $this->assessment["handler"] == "IncompleteComplete" ? "Incomplete" : "Fail"
			)
		);

		return array(
			"chart_type" => "pie",
			"data" => json_encode($grade_data),
			"xTicks" => json_encode($xticks),
		);
	}

	/**
	 * Generates graph data and stats for percentage-type handlers
	 * @return array
	 */
	public function renderPercentageGraphDataJSON() {
		
		$grades = array();

		$sum = 0;
		$entered = 0;

		$grade_values = array();
		foreach($this->raw_student_results as $i => $student) {
			if (!is_null($student["b0grade"])) {
				$sum += $student["b0grade"];
				$entered++;
				$grade_values[] = $student["b0grade"];

				$key = floor($student["b0grade"] / 10);
				$grades[$key]++;
			}
		}

        $mean = ($entered > 0 ? $sum / $entered : 0);
        $variance = 0.0;
        foreach ($grade_values as $value) {
            $variance += pow($value - $mean, 2);
        }

		$grade_data = array();
		foreach ($grades as $key => $grade) {
			$grade_data[] = array($key, $grade);
		}
		sort($grade_values);

		$xticks = array(
			array(
				"v" => 0,
				"label" => "0s"
			),
			array(
				"v" => 1,
				"label" => "10s"
			),
			array(
				"v" => 2,
				"label" => "20s"
			),
			array(
				"v" => 3,
				"label" => "30s"
			),
			array(
				"v" => 4,
				"label" => "40s"
			),
			array(
				"v" => 5,
				"label" => "50s"
			),
			array(
				"v" => 6,
				"label" => "60s"
			),
			array(
				"v" => 7,
				"label" => "70s"
			),
			array(
				"v" => 8,
				"label" => "80s"
			),
			array(
				"v" => 9,
				"label" => "90s"
			),
			array(
				"v" => 10,
				"label" => "100"
			),
		);

		return array(
			"chart_type" => "bar",
			"data" => json_encode($grade_data),
			"xTicks" => json_encode($xticks),
			"mean" => number_format($mean, 0),
			"median" => $grade_values[floor(count($grade_values) / 2)],
            "standard_deviation" => ($entered > 0 ? number_format(sqrt($variance / $entered), 2) : 0),
			"grade_values" => $grade_values
		);
	}
}
