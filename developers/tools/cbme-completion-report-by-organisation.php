<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Script to generate a list of all CBME triggered assessments vs number completed. This will only work for individual
 * targets and assessors, no multiple attempts, which should be fine for CBME. There is an array of proxy_ids to exclude.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
@set_time_limit(0);
@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../../www-root/core",
    dirname(__FILE__) . "/../../www-root/core/includes",
    dirname(__FILE__) . "/../../www-root/core/library",
    dirname(__FILE__) . "/../../www-root/core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

/**
 * Usage blurb. When displayed, exits script.
 */
function show_this_usage() {
    echo "\nUsage: cbme-completion-report-by-course.php [options] [organisation_id] [start_timestamp]";
    echo "\n   --usage       Brings up this help screen.";
    echo "\n   --execute     Generate the CSV.";
    echo "\n\n";
    exit();
}

/**
 * Main point of execution
 *
 * @param $argc
 * @param $argv
 */
function run($argc, &$argv) {
    $action = "--usage";
    $organisation_id = $start_date = 0;
    if ($argc > 1 && !empty($argv)) {
        $action = @$argv[1];
        $organisation_id = @$argv[2];
        $start_date = @$argv[3];
    }

    switch ($action) {
        case "--execute":

            if (!$organisation_id || !$start_date) {
                show_this_usage();
            }

            global $db;
            $exclude_proxy_ids = array(
                78171,
                78168,
                78162,
                78065,
                78063,
                78035,
                78034,
                78033,
                78027,
                78021,
                78020,
                78016,
                78015,
                78014,
                78006,
                78001,
                78000,
                77998,
                77997,
                77984,
                77975,
                77939,
                77933,
                77932,
                77928,
                75166
            );

            $exclude_course_ids = array(
                440,
                491,
                492,
                493
            );

            if (($output_file = fopen("cbme-completion-report.csv", "w")) !== false) {

                // Header row.
                fputcsv($output_file, array("Course Name", "Total Count", "Total Completed", "Percentage Complete"));

                $query = "  SELECT * FROM `cbl_distribution_assessments` AS da
                            JOIN `cbl_distribution_assessment_targets` AS dat
                            ON da.`dassessment_id` = dat.`dassessment_id`
                            LEFT JOIN `cbl_assessment_progress` AS ap
                            ON ap.`dassessment_id` = dat.`dassessment_id` AND ap.`target_type` = dat.`target_type` AND ap.`target_record_id` = dat.`target_value`
                            WHERE da.`organisation_id` = ?
                            AND da.`assessment_type_id` = 2
                            AND da.`delivery_date` >= ?
                            AND (da.`assessor_type` != 'internal' OR (da.`assessor_type` = 'internal' AND da.`assessor_value` NOT IN (?)))
                            AND (dat.`target_type` != 'proxy_id' OR (dat.`target_type` = 'proxy_id' AND dat.`target_value` NOT IN (?)))
                            AND (da.`assessment_method_id` IN (5,6) OR (da.`assessment_method_id` NOT IN (5,6) AND dat.`target_type` = 'proxy_id' AND dat.`target_value` != da.`assessor_value` AND da.`assessor_type` = 'internal'))
                            AND da.`course_id` NOT IN (?)";
                $results = $db->GetAll($query, array($organisation_id, $start_date, implode(",", $exclude_proxy_ids), implode(",", $exclude_proxy_ids), implode(",", $exclude_course_ids)));

                if ($results) {
                    $course_assessments = array();
                    foreach ($results as $data) {
                        if ($data["progress_value"] == "complete" && !$data["deleted_date"]) {
                            $course_assessments[$data["course_id"]]["completed"][$data["dassessment_id"]] = $data["dassessment_id"];
                            $course_assessments[$data["course_id"]]["all"][$data["dassessment_id"]] = $data["dassessment_id"];
                        } else {
                            $course_assessments[$data["course_id"]]["all"][$data["dassessment_id"]] = $data["dassessment_id"];
                        }
                    }

                    $course_assessment_counts = array();
                    foreach ($course_assessments as $course_id => $course_data) {
                        $course_assessment_counts[$course_id]["total"] = @count($course_data["all"]);
                        $course_assessment_counts[$course_id]["complete"] = @count($course_data["completed"]);
                    }

                    if (!empty($course_assessment_counts)) {

                        $course_data = array();

                        // Fetch course data for pretty output.
                        foreach ($course_assessment_counts as $course_id => $course_counts) {
                            if (!array_key_exists($course_id, $course_data)) {
                                $course = Models_Course::fetchRowByID($course_id);
                                if ($course) {
                                    $course_data[$course_id] = $course->toArray();
                                }
                            }
                        }

                        $overall_total = 0;
                        $overall_complete = 0;

                        // Output totals row for each course.
                        foreach ($course_assessment_counts as $course_id => $course_counts) {
                            $overall_total += $course_counts["total"];
                            $overall_complete += $course_counts["complete"];
                            $overall_complete_percent = round((($course_counts["complete"] / $course_counts["total"]) * 100), 2);

                            $output = array(
                                $course_data[$course_id]["course_name"],
                                $course_counts["total"],
                                $course_counts["complete"],
                                "{$overall_complete_percent} %"
                            );
                            fputcsv($output_file, $output);
                        }

                        // Output a break and a grand total row.
                        fputcsv($output_file, array());
                        $grand_total_complete_percent = round((($overall_complete / $overall_total) * 100), 2);
                        $output = array(
                            "",
                            "Total Assessments: {$overall_total}",
                            "Total Complete: {$overall_complete}",
                            "{$grand_total_complete_percent} %"
                        );
                        fputcsv($output_file, $output);

                        echo "\nSuccessfully completed report generation.";

                    } else {
                        echo "\nSomething went wrong.";
                    }
                } else {
                    echo "\nNo assessments found." . $db->ErrorMsg();
                }

                fclose($output_file);
            } else {
                echo "\nCould not open file for writing.";
            }

            echo "\n";
            exit;
            break;
        case "--help":
        case "--usage":
        default :
            show_this_usage();
            break;
    }

}

// Execute
run($argc, $argv);