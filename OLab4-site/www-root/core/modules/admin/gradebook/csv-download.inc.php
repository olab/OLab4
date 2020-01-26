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
 * Download all student marks as CSV
 *
 * @author Organisation: bitHeads Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

if ($ENTRADA_ACL->isUserAuthorized("gradebook", "update", false, array("PARENT_INCLUDED", "IN_GRADEBOOK"))) {

    // Clear buffers to deliver plain-text response
    ob_clear_open_buffers();

    // Sanitize URL parameters
    $url_params = Entrada_Utilities::getCleanUrlParams(array('cperiod_id' => 'int', 'title' => 'trim', 'assessment_id' => 'int'));

    if ($COURSE_ID) {
        if ($url_params['cperiod_id']) {
            // Get the assessments to include. If an assessment_id has been provided, then just generate for that assessment
            if (isset($url_params["assessment_id"])) {
                $model_assessment = Models_Gradebook_Assessment::fetchRowByID($url_params["assessment_id"]);
                $assessment = $model_assessment->toArray();
                $marking_scheme = Models_Gradebook_Assessment_Marking_Scheme::fetchRowByID($model_assessment->getMarkingSchemeID());
                $assessment["handler"] = $marking_scheme->getHandler();
                $assessments = array($assessment);
            } else {
                $model_assessment = new Models_Gradebook_Assessment();
                $assessments = $model_assessment->fetchAssessmentsByCurriculumPeriodIDWithMarkingScheme($COURSE_ID, $url_params['cperiod_id']);
            }

            // Create CSV header row
            $header_row = array($translate->_('Number'), $translate->_('Full Name'));

            // Add each assessment to the header row
            foreach($assessments as $assessment) {
                $short_description = get_marking_scheme_short_description($assessment, $assessment['numeric_grade_points_total']);
                $weighting = $translate->_('Weighting:').' '.$assessment['grade_weighting'].'%';
                $type = $assessment['type'];

                // Add to header row
                $header_row[] = $assessment['name'].' ['.$short_description.'] ['.$weighting.'] ('.$type.')';
            }

            // Process next columns
            $header_row[] = $translate->_('Weighted Total');

            // Get student list
            $course = new Models_Course(array('course_id' => $COURSE_ID));

            // Get array of IDs for next query
            $student_ids = $course->getStudentIDs($url_params['cperiod_id']);

            // Get assessment marks and any grade weighting exceptions for each student
            $assessment_marks = $model_assessment->fetchStudentMarksAndGradeWeightingsPerAssessment($assessments, $student_ids);

            // Set filename
            $filename = date("Y-m-d")."_";
            $filename .= $url_params['title'] ? str_replace(' ', '_', $url_params['title']).'_' : '';
            $filename .= 'cperiod_'.$url_params['cperiod_id'].'_';
            $filename .= $translate->_('gradebook');

            // Output as CSV
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename='.$filename.'.csv');

            $csv = fopen('php://output', 'w');

            // CSV header row
            fputcsv($csv, $header_row);

            // add in assessment marks per student
            foreach($assessment_marks as $i => $student) {

                // start off the row with the student number and name
                $csv_student_row = array($student['number'], $student['fullname']);

                // start with a weighted total of 0
                $weighted_total = 0;

                // for each assessment, get the formatted grade (8/10 instead of 80%) and grade weighting
                foreach($assessments as $i => $assessment) {
                    $csv_student_row['b'.$i.'grade'] = format_retrieved_grade($student['b'.$i.'grade'], $assessment);

                    // Get the grade_weighting either from the student if one exists or the assessment standard weighting
                    $grade_weighting = $student['c'.$i.'weight'] !== null ? $student['c'.$i.'weight'] : $assessment['grade_weighting'];

                    // Add to the weighted total
                    $weighted_total += ($student['b'.$i.'grade'] * $grade_weighting) / 100;
                }

                // Set the weighted total for this row
                $csv_student_row['weighted_total'] = $weighted_total;

                // Add this row to the csv
                fputcsv($csv, $csv_student_row);
            }

            fclose($csv);
        }
        else {
            add_error($translate->_("In order to export a gradebook you must provide a valid curriculum period identifier."));
            echo display_error();
            application_log("notice", $translate->_("Failed to provide curriculum period when attempting to export an gradebook's grades."));
        }
    }
    else {
        add_error($translate->_("In order to export a gradebook you must provide a valid course identifier."));
        echo display_error();
        application_log("notice", $translate->_("Failed to provide course identifier when attempting to export an gradebook's grades."));
    }
}

// Necessary to not expose any template code not caught by ob_clear_open_buffers()
exit;