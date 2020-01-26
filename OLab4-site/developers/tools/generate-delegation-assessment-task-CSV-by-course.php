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
if ($COURSE_ID) {
    $distributions = Models_Assessments_Distribution::fetchAllByCourseID($COURSE_ID);
    if ($distributions) {

        // Create CSV file.
        if (($output_file = fopen("course-{$COURSE_ID}-delegation-assessment-tasks.csv", "w")) !== false) {

            // Create header row.
            $headers = array(
                "Assessor Name",
                "Target Name",
                "Target Type",
                "Delivery Date",
                "Task Status",
                "Updated/Completed Date",
                "Delegation Marked Complete",
                "Distribution Title",
                "Task URL",
            );
            fputcsv($output_file, $headers, ",");

            foreach ($distributions as $distribution) {

                $delegator = Models_Assessments_Distribution_Delegator::fetchRowByDistributionID($distribution->getID());
                if ($delegator) {

                    $distribution_delegation = new Entrada_Utilities_Assessments_DistributionDelegation(array("adistribution_id" => $distribution->getID()));

                    // build the list of all delegation tasks, past, present, and future. Note that this populates the object's internal task list.
                    $data["all_possible_tasks_list"] = $distribution_delegation->buildDelegationTaskList();

                    // Clear the task list (cleans up memory a bit) and store the clean object.
                    $distribution_delegation->clearTaskList();
                    $data["distribution_delegation_utility"] = $distribution_delegation;

                    echo "\n\nProcessing distribution " . $distribution->getID() . ".";
                    if ($data["all_possible_tasks_list"]) {

                        foreach ($data["all_possible_tasks_list"] as $list) {
                            foreach ($list as $date_set) {
                                echo "\nProcessing new date set...";
                                if ($date_set["meta"]["should_exist"]) {
                                    foreach ($date_set["targets"] as $target) {
                                        if ($target["use_members"]) {

                                            $task = array();
                                            unset($assessor);

                                            // Attempt to find a task for this target.
                                            $progress = false;
                                            $potential_progresses = Models_Assessments_Progress::fetchAllByADistributionIDTargetRecordID($distribution->getID(), $target["member_id"]);
                                            if ($potential_progresses) {
                                                echo "\nPotential progress found. Checking...";
                                                if ($potential_progresses) {
                                                    foreach ($potential_progresses as $potential_progress) {
                                                        $potential_assessment = Models_Assessments_Assessor::fetchRowByID($potential_progress->getDAssessmentID());
                                                        if ($potential_assessment) {
                                                            //if ($potential_assessment->getDeliveryDate() == $date_set["meta"]["delivery_date"] || $potential_assessment->getEndDate() == $date_set["meta"]["delivery_date"]) {
                                                            if ($potential_assessment->getStartDate() == $date_set["meta"]["start_date"] && $potential_assessment->getEndDate() == $date_set["meta"]["end_date"]) {
                                                                echo "\nProgress and assessment found";
                                                                $assessment = $potential_assessment;
                                                                $progress = $potential_progress;
                                                                $assessor = Models_User::fetchRowByID($assessment->getAssessorValue());
                                                                continue;
                                                            } else {
                                                                //echo "\nDates don't match " . $potential_assessment->getDeliveryDate() . " - " . $potential_assessment->getEndDate() . " - " . $date_set["meta"]["delivery_date"];
                                                                echo "\nDates don't match " . $potential_assessment->getStartDate() . " - " . $date_set["meta"]["start_date"] . ", " . $potential_assessment->getEndDate() . " - " . $date_set["meta"]["end_date"];
                                                            }
                                                        } else {
                                                            echo "\nProgress " . $potential_progress->getID() . " referenced deleted assessment task " . $potential_progress->getDAssessmentID();
                                                        }
                                                    }
                                                }
                                            } else {
                                                echo "\nNo progress found. Attempting to fetch assessment targets...";
                                                // Attempt to find an assessment target matching this user.
                                                $assessment_targets = Models_Assessments_AssessmentTarget::fetchAllByDistributionIDTargetTypeTargetValue($distribution->getID(), "proxy_id", $target["member_id"]);
                                                if ($assessment_targets) {
                                                    echo "\nPotential targets found. Checking...";
                                                    foreach ($assessment_targets as $assessment_target) {
                                                        $potential_assessment = Models_Assessments_Assessor::fetchRowByID($assessment_target->getDAssessmentID());
                                                        //if ($potential_assessment->getDeliveryDate() == $date_set["meta"]["delivery_date"] || $potential_assessment->getEndDate() == $date_set["meta"]["delivery_date"]) {
                                                        if ($potential_assessment->getStartDate() == $date_set["meta"]["start_date"] && $potential_assessment->getEndDate() == $date_set["meta"]["end_date"]) {
                                                            echo "\nAssessment found";
                                                            $assessment = $potential_assessment;
                                                            $assessor = Models_User::fetchRowByID($assessment->getAssessorValue());
                                                            continue;
                                                        } else {
                                                            //echo "\nDates don't match " . $potential_assessment->getDeliveryDate() . " - " . $potential_assessment->getEndDate() . " - " . $date_set["meta"]["delivery_date"];
                                                            echo "\nDates don't match " . $potential_assessment->getStartDate() . " - " . $date_set["meta"]["start_date"] . ", " . $potential_assessment->getEndDate() . " - " . $date_set["meta"]["end_date"];
                                                        }
                                                    }
                                                } else {
                                                    echo "\nNo assessment targets found for target " . $target["member_id"] . " that should have task.";
                                                }
                                            }

                                            // Output assessor name.
                                            $assessor_name = (isset($assessor) ? $assessor->getFirstName() . " " . $assessor->getLastName() : "N/A");
                                            array_push($task, $assessor_name);

                                            // Output target name.
                                            array_push($task, $target["member_fullname"]);

                                            // Output target type.
                                            $target_type = "Person";
                                            array_push($task, $target_type);

                                            // Output delivery date.
                                            $delivery_date = false;
                                            if ($date_set["meta"]["delivery_date"]) {
                                                $delivery_date = date("Y-m-d", $date_set["meta"]["delivery_date"]);
                                            } else {
                                                $delivery_date = "N/A";
                                            }
                                            array_push($task, $delivery_date);

                                            // Output completion status.
                                            $completion_status = false;
                                            if ($progress) {
                                                $completion_status = $progress->getProgressValue();
                                            } elseif (isset($assessment) && $assessment) {
                                                $completion_status = "pending";
                                            }
                                            array_push($task, $completion_status);

                                            // Output completion date.
                                            $updated_date = "";
                                            if ($progress) {
                                                $updated_date = date("Y-m-d", $progress->getUpdatedDate());
                                            }
                                            array_push($task, $updated_date);
                                            
                                            // Output delegation status.
                                            $delegation_status = $distribution_delegation->getDelegationStatus();
                                            array_push($task, ($delegation_status["completed"] ? "Yes" : "No") );

                                            // Output distribution title.
                                            array_push($task, "[" . $distribution->getID() . "] " . $distribution->getTitle());

                                            // Output task URL.
                                            $url = "";
                                            if (isset($assessment) && $assessment && isset($assessor) && $assessor) {
                                                $url = ENTRADA_URL . "/assessments/assessment?adistribution_id=" . $distribution->getID() . "&target_record_id=" . $target["member_id"] . "&dassessment_id=" . $assessment->getID() . "&assessor_id=" . $assessor->getID() . ($progress ? "&aprogress_id=" . $progress->getID() : "") . "&view=view_as";
                                            }
                                            array_push($task, $url);

                                            // Write the array to the CSV, ASCII character for NULL is chr(0).
                                            if (!fputcsv($output_file, $task, ",")) {
                                                echo "Unable to task to file.\n";
                                            }
                                        } else {

                                            echo "\nNon-schedule based delegation found.";

                                            $task = array();
                                            unset($assessor);

                                            // Attempt to find a task for this target.
                                            $progress = false;
                                            $potential_progresses = Models_Assessments_Progress::fetchAllByADistributionIDTargetRecordID($distribution->getID(), $target["id"]);
                                            if ($potential_progresses) {
                                                echo "\nPotential progress found. Checking...";
                                                if ($potential_progresses) {
                                                    foreach ($potential_progresses as $potential_progress) {
                                                        $potential_assessment = Models_Assessments_Assessor::fetchRowByID($potential_progress->getDAssessmentID());
                                                        if ($potential_assessment) {
                                                            //if ($potential_assessment->getDeliveryDate() == $date_set["meta"]["delivery_date"] || $potential_assessment->getEndDate() == $date_set["meta"]["delivery_date"]) {
                                                            if ($potential_assessment->getStartDate() == $date_set["meta"]["start_date"] && $potential_assessment->getEndDate() == $date_set["meta"]["end_date"]) {
                                                                echo "\nProgress and assessment found";
                                                                $assessment = $potential_assessment;
                                                                $progress = $potential_progress;
                                                                $assessor = Models_User::fetchRowByID($assessment->getAssessorValue());
                                                                continue;
                                                            } else {
                                                                //echo "\nDates don't match " . $potential_assessment->getDeliveryDate() . " - " . $potential_assessment->getEndDate() . " - " . $date_set["meta"]["delivery_date"];
                                                                echo "\nDates don't match " . $potential_assessment->getStartDate() . " - " . $date_set["meta"]["start_date"] . ", " . $potential_assessment->getEndDate() . " - " . $date_set["meta"]["end_date"];
                                                            }
                                                        } else {
                                                            echo "\nProgress " . $potential_progress->getID() . " referenced deleted assessment task " . $potential_progress->getDAssessmentID();
                                                        }
                                                    }
                                                }
                                            } else {
                                                echo "\nNo progress found. Attempting to fetch assessment targets...";
                                                // Attempt to find an assessment target matching this user.
                                                $assessment_targets = Models_Assessments_AssessmentTarget::fetchAllByDistributionIDTargetTypeTargetValue($distribution->getID(), "proxy_id", $target["id"]);
                                                if ($assessment_targets) {
                                                    echo "\nPotential targets found. Checking...";
                                                    foreach ($assessment_targets as $assessment_target) {
                                                        $potential_assessment = Models_Assessments_Assessor::fetchRowByID($assessment_target->getDAssessmentID());
                                                        //if ($potential_assessment->getDeliveryDate() == $date_set["meta"]["delivery_date"] || $potential_assessment->getEndDate() == $date_set["meta"]["delivery_date"]) {
                                                        if ($potential_assessment->getStartDate() == $date_set["meta"]["start_date"] && $potential_assessment->getEndDate() == $date_set["meta"]["end_date"]) {
                                                            echo "\nAssessment found";
                                                            $assessment = $potential_assessment;
                                                            $assessor = Models_User::fetchRowByID($assessment->getAssessorValue());
                                                            continue;
                                                        } else {
                                                            //echo "\nDates don't match " . $potential_assessment->getDeliveryDate() . " - " . $potential_assessment->getEndDate() . " - " . $date_set["meta"]["delivery_date"];
                                                            echo "\nDates don't match " . $potential_assessment->getStartDate() . " - " . $date_set["meta"]["start_date"] . ", " . $potential_assessment->getEndDate() . " - " . $date_set["meta"]["end_date"];
                                                        }
                                                    }
                                                } else {
                                                    echo "\nNo assessment targets found for target " . $target["id"] . " that should have task.";
                                                }
                                            }

                                            // Output assessor name.
                                            $assessor_name = (isset($assessor) ? $assessor->getFirstName() . " " . $assessor->getLastName() : "N/A");
                                            array_push($task, $assessor_name);

                                            // Output target name.
                                            array_push($task, $target["entity_name"]);

                                            // Output target type.
                                            switch ($target["type"]) {
                                                case "proxy_id":
                                                    $target_type = "Person";
                                                    break;
                                                case "schedule_id":
                                                    $target_type = "Schedule";
                                                    break;
                                                case "group_id":
                                                    $target_type = "Group";
                                                    break;
                                                case "course_id":
                                                    $target_type = "Course";
                                                    break;
                                            }
                                            array_push($task, $target_type);

                                            // Output delivery date.
                                            $delivery_date = false;
                                            if ($date_set["meta"]["delivery_date"]) {
                                                $delivery_date = date("Y-m-d", $date_set["meta"]["delivery_date"]);
                                            } else {
                                                $delivery_date = "N/A";
                                            }
                                            array_push($task, $delivery_date);

                                            // Output completion status.
                                            $completion_status = false;
                                            if ($progress) {
                                                $completion_status = $progress->getProgressValue();
                                            } elseif (isset($assessment) && $assessment) {
                                                $completion_status = "pending";
                                            }
                                            array_push($task, $completion_status);

                                            // Output completion date.
                                            $updated_date = "";
                                            if ($progress) {
                                                $updated_date = date("Y-m-d", $progress->getUpdatedDate());
                                            }
                                            array_push($task, $updated_date);

                                            $delegation_status = $distribution_delegation->getDelegationStatus();
                                            // Output delegation status.
                                            array_push($task, ($delegation_status["completed"] ? "Yes" : "No") );

                                            // Output distribution title.
                                            array_push($task, "[" . $distribution->getID() . "] " . $distribution->getTitle());

                                            // Output task URL.
                                            $url = "";
                                            if (isset($assessment) && $assessment && isset($assessor) && $assessor) {
                                                $url = ENTRADA_URL . "/assessments/assessment?adistribution_id=" . $distribution->getID() . "&target_record_id=" . $target["id"] . "&dassessment_id=" . $assessment->getID() . "&assessor_id=" . $assessor->getID() . ($progress ? "&aprogress_id=" . $progress->getID() : "") . "&view=view_as";
                                            }
                                            array_push($task, $url);

                                            // Write the array to the CSV, ASCII character for NULL is chr(0).
                                            if (!fputcsv($output_file, $task, ",")) {
                                                echo "Unable to task to file.\n";
                                            }
                                        }
                                    }
                                } else {
                                    echo "\nNo task should exist for date set.";
                                }
                            }
                        }
                    } else {
                        echo "\nUnable to build distribution progress for distribution " . $distribution->getID() . ".";
                    }
                } else {
                    echo "\n\nDistribution " . $distribution->getID() . " is not delegation based and cannot be processed by this script.";
                }
            }
        } else {
            echo "\nUnable to create output CSV file.";
        }
    } else {
        echo "\nNo distributions found for the given course.";
    }
} else {
    echo "\nPlease provide a valid course ID.";
}

echo "\n\n";