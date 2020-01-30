<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Script to generate a CSV of assessment tasks with a specified status for a specified course.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Belanger <jb301@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

@set_time_limit(0);
@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

require_once("init.inc.php");

$COURSE_ID = (((isset($_SERVER["argv"][1])) && (trim($_SERVER["argv"][1]) != "")) ? trim($_SERVER["argv"][1]) : false);
$PROGRESS_STATUS = (((isset($_SERVER["argv"][2])) && (trim($_SERVER["argv"][2]) != "")) ? trim($_SERVER["argv"][2]) : false);
if ($COURSE_ID) {
    if ($PROGRESS_STATUS && ($PROGRESS_STATUS == "pending" || $PROGRESS_STATUS == "inprogress" || $PROGRESS_STATUS == "complete")) {
        $distributions = Models_Assessments_Distribution::fetchAllByCourseID($COURSE_ID);
        if ($distributions) {

            // Create CSV file.
            if (($output_file = fopen("course-{$COURSE_ID}-{$PROGRESS_STATUS}-assessment-tasks.csv", "w")) !== false) {
                
                // Create header row.
                $headers = array(
                    "Assessor Name",
                    "Target Type",
                    "Target Name",
                    "Delivery Date",
                    "Completed Date",
                    "Distribution Title",
                    "Task URL",
                );
                fputcsv($output_file, $headers, ",");

                foreach ($distributions as $distribution) {

                    $delegation = Models_Assessments_Distribution_Delegator::fetchRowByDistributionID($distribution->getID());
                    if (!$delegation) {

                        echo "\nProcessing distribution " . $distribution->getID() . ".";
                        $progress = new Entrada_Utilities_DistributionProgress();
                        $distribution_progress = $progress->getDistributionProgress($distribution->getID());
                        if ($distribution_progress) {
                            if ($distribution_progress[$PROGRESS_STATUS]) {

                                foreach ($distribution_progress[$PROGRESS_STATUS] as $type) {
                                    foreach ($type as $detail) {
                                        if (is_array($detail["targets"])) {
                                            foreach ($detail["targets"] as $target) {

                                                $task = array();
                                                $progress = false;
                                                if ($target["aprogress_id"]) {
                                                    $progress = Models_Assessments_Progress::fetchRowByID($target["aprogress_id"]);
                                                }

                                                // Output assessor name.
                                                $assessor_name = (isset($detail["assessor_name"]) ? html_encode($detail["assessor_name"]) : "N/A");
                                                array_push($task, $assessor_name);

                                                // Output target type.
                                                $target_type = false;
                                                switch ($target["target_type"]) {
                                                    case "schedule_id":
                                                        $target_type = "Schedule";
                                                        break;
                                                    case "course_id":
                                                        $target_type = "Course";
                                                        break;
                                                    case "proxy_id":
                                                        $target_type = "Person";
                                                        break;
                                                    default:
                                                        $target_type = "N/A";
                                                        break;
                                                }
                                                array_push($task, $target_type);

                                                // Output target name.
                                                array_push($task, $target["target_name"]);

                                                // Output delivery date.
                                                $delivery_date = false;
                                                if (isset($target["delivery_date"]) && $target["delivery_date"]) {
                                                    $delivery_date = date("Y-m-d", $target["delivery_date"]);
                                                } else {
                                                    $delivery_date = "N/A";
                                                }
                                                array_push($task, $delivery_date);

                                                // Output completion date.
                                                $completion_date = "";
                                                if ($progress) {
                                                    $completion_date = date("Y-m-d", $progress->getUpdatedDate());
                                                }
                                                array_push($task, $completion_date);

                                                // Output distribution title.
                                                array_push($task, $distribution->getID() . " " . $distribution->getTitle());

                                                // Output task URL.
                                                $url = "";
                                                if ($target["dassessment_id"]) {
                                                    if ($detail["assessor_type"] == "external" && ($progress && $progress->getProgressValue() != "complete" ? true : (!$progress ? true : false))) {
                                                        $url = ENTRADA_URL . "/assessment?adistribution_id=" . $distribution->getID() . "&target_record_id=" . $target["target_id"] . "&dassessment_id=" . $target["dassessment_id"] . "&assessor_value=" . $detail["assessor_value"] . ($target["aprogress_id"] ? "&aprogress_id=" . $target["aprogress_id"] : "") . "&external_hash=" . $target["external_hash"] . "&from=progress";
                                                    } else {
                                                        $url = ENTRADA_URL . "/assessments/assessment?adistribution_id=" . $distribution->getID() . "&target_record_id=" . $target["target_id"] . "&dassessment_id=" . $target["dassessment_id"] . "&assessor_id=" . $detail["assessor_value"] . ($target["aprogress_id"] ? "&aprogress_id=" . $target["aprogress_id"] : "") . "&view=view_as";
                                                    }
                                                }
                                                array_push($task, $url);

                                                // Write the array to the CSV, ASCII character for NULL is chr(0).
                                                if (!fputcsv($output_file, $task, ",")) {
                                                    echo "Unable to task to file.\n";
                                                }
                                            }
                                        }
                                    }
                                }
                            } else {
                                echo "\nNo tasks with a status of " . $PROGRESS_STATUS . " found.";
                            }
                        } else {
                            echo "\nUnable to build distribution progress for distribution " . $distribution->getID() . ".";
                        }
                    } else {
                        echo "\nDistribution " . $distribution->getID() . " is delegation based and cannot be processed by this script.";
                    }
                }
            } else {
                echo "\nUnable to create output CSV file.";
            }
        } else {
            echo "\nNo distributions found for the given course.";
        }
    } else {
        echo "\nPlease provide a progress status (pending, inprogress, complete).";
    }
} else {
    echo "\nPlease provide a valid course ID.";
}

echo "\n\n";