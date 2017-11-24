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
 * This class contains the logic for taking a "snapshot" of all assessment
 * tasks that are currently scheduled to be delivered. This class is a
 * wrapper that uses the DistributionProgress model, trimming out tasks that
 * have already been delivered. It is meant to be invoked in a nightly script
 * to store future task data in a table of flat assessment tasks. Obviously
 * given the nature of the assessment and evaluation system as a whole, this
 * snapshot is not guaranteed to be accurate/up to date if the distributions
 * have been edited since the class was last invoked.
 *
 * @author Organisation: Queen's University
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */
class Entrada_Utilities_AssessmentsSnapshot {

    public function run() {
        // Drop the current snapshots.
        Models_Assessments_FutureTaskSnapshot::truncate();
        Models_Assessments_CurrentTaskSnapshot::truncate();

        // Leverage distribution progress to determine all tasks. Filter tasks later.
        $distributions = Models_Assessments_Distribution::fetchAllRecords();
        if ($distributions) {
            foreach ($distributions as $distribution) {
                $delegator = Models_Assessments_Distribution_Delegator::fetchRowByDistributionID($distribution->getID());
                if (!$delegator) {
                    $progress_object = new Entrada_Utilities_DistributionProgress($distribution->getID());
                    $details = $progress_object->getDistributionProgress($distribution->getID());
                    $this->storeTasks($details, $distribution);
                }
            }
        }
    }

    public function storeTasks($details, $distribution) {
        global $db;
        
        // If the task is pending and projected to take place in the future, we need to store it for reference in the "upcoming tasks" list in the public interface. Otherwise it is current.
        if (isset($details["pending"])) {
            foreach ($details["pending"] as $type) {
                foreach ($type as $detail) {
                    if (is_array($detail["targets"])) {
                        foreach ($detail["targets"] as $target) {

                            $rotation_start_date = 0;
                            $rotation_end_date = 0;
                            $schedule_details = false;

                            if ($target["parent_schedule"]) {

                                $schedule_details = $target["parent_schedule"]["title"];

                                if ($target["child_schedules"]) {
                                    $rotation_start_date = $target["child_schedules"][0]["start_date"];
                                    $rotation_end_date = $target["child_schedules"][@count($target["child_schedules"]) - 1]["end_date"];

                                    /*
                                    $schedule_details .= " - ";
                                    foreach ($target["child_schedules"] as $key => $child_schedule) {
                                        $schedule_details .= $child_schedule["title"] . ($key < @count($target["child_schedules"]) - 1 ? ", " : "");
                                    }
                                    */
                                } else {
                                    $rotation_start_date = $target["parent_schedule"]["start_date"];
                                    $rotation_end_date = $target["parent_schedule"]["end_date"];
                                }
                            }

                            if ($target["delivery_date"] >= time()) {
                                
                                $future_task = new Models_Assessments_FutureTaskSnapshot(array(
                                    "adistribution_id"      => $distribution->getID(),
                                    "assessor_type"         => $detail["assessor_type"],
                                    "assessor_value"        => $detail["assessor_value"],
                                    "target_type"           => $target["target_type"],
                                    "target_value"          => $target["target_id"],
                                    "title"                 => $distribution->getTitle(),
                                    "rotation_start_date"   => $rotation_start_date,
                                    "rotation_end_date"     => $rotation_end_date,
                                    "schedule_details"      => $schedule_details,
                                    "delivery_date"         => $target["delivery_date"],
                                    "created_date"          => time(),
                                    "created_by"            => 1
                                ));
                                if (!$future_task->insert()) {
                                    application_log("error", "There was a problem saving a future task snapshot, DB said: " . $db->ErrorMsg());
                                }

                            } else {

                                $current_task = new Models_Assessments_CurrentTaskSnapshot(array(
                                    "adistribution_id"      => $distribution->getID(),
                                    "dassessment_id"        => (isset($target["dassessment_id"]) && $target["dassessment_id"] ? $target["dassessment_id"] : NULL),
                                    "assessor_type"         => $detail["assessor_type"],
                                    "assessor_value"        => $detail["assessor_value"],
                                    "target_type"           => $target["target_type"],
                                    "target_value"          => $target["target_id"],
                                    "title"                 => $distribution->getTitle(),
                                    "rotation_start_date"   => $rotation_start_date,
                                    "rotation_end_date"     => $rotation_end_date,
                                    "schedule_details"      => $schedule_details,
                                    "delivery_date"         => $target["delivery_date"],
                                    "created_date"          => time(),
                                    "created_by"            => 1
                                ));
                                if (!$current_task->insert()) {
                                    application_log("error", "There was a problem saving a current task snapshot, DB said: " . $db->ErrorMsg());
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    // This ugly function is the result of the need to store task snapshots as flat a assessor/target record.
    // It will process an array of current/future snapshots and construct a proper array of assessment tasks with the targets grouped up.
    public static function groupTasks($tasks) {
        if ($tasks) {
            $grouped_tasks = array();
            if (is_array($tasks)) {
                foreach ($tasks as $task) {

                    $distribution = Models_Assessments_Distribution::fetchRowByID($task->getDistributionID());
                    $match_found = false;

                    // ID is either future or current task ID, depending on the type of task.
                    if (get_class($task) == "Models_Assessments_FutureTaskSnapshot") {
                        $id_type = "future_task_id";
                    } else {
                        $id_type = "current_task_id";
                    }

                    // Search for an identical task already added to the grouped tasks.
                    foreach ($grouped_tasks as $ctr => $grouped_task) {
                        if (
                            $grouped_task["adistribution_id"]       == $task->getDistributionID() &&
                            $grouped_task["assessor_type"]          == $task->getAssessorType() &&
                            $grouped_task["assessor_value"]         == $task->getAssessorValue() &&
                            $grouped_task["title"]                  == $task->getTitle() &&
                            $grouped_task["rotation_start_date"]    == $task->getRotationStartDate() &&
                            $grouped_task["rotation_end_date"]      == $task->getRotationEndDate() &&
                            $grouped_task["delivery_date"]          == $task->getDeliveryDate() &&
                            $grouped_task["schedule_details"]       == $task->getScheduleDetails() &&
                            (array_key_exists("dassessment_id", $grouped_task) ? ($grouped_task["dassessment_id"] ? ($grouped_task["dassessment_id"] == $task->getDAssessmentID() ? true : false) : false) : false)
                        ) {
                            // Add the individual task's target to the found grouped task's targets array.
                            $match_found = true;
                            $grouped_tasks[$ctr]["targets"][] = array(
                                $id_type            => $task->getID(),
                                "target_type"       => $task->getTargetType(),
                                "target_value"      => $task->getTargetValue(),
                                "name"              => $task->getTarget(),
                                "group"             => ($task->getTargetType() == "proxy_id" && $distribution ? $task->getTargetGroup($distribution->getOrganisationID()) : ""),
                                "role"              => ($task->getTargetType() == "proxy_id" && $distribution ? $task->getTargetRole($distribution->getOrganisationID()) : "")
                            );
                        }
                    }

                    // If no match was found in the existing grouped tasks, add a new one.
                    if (!$match_found) {
                        $assessment_sub_type = "rotation_schedule";
                        $assessor = Models_Assessments_Assessor::fetchRowByID($id_type == "current_task_id" ? $task->getDAssessmentID() : false);

                        if ($assessor && $assessor->getAssociatedRecordType() == "event_id") {
                            $assessment_sub_type = "learning_event";
                        } else if (is_null($task->getRotationStartDate()) || $task->getRotationStartDate() == "" || !$task->getRotationEndDate()) {
                            $assessment_sub_type = "date_range";
                        }

                        $grouped_tasks[] = array(
                            "adistribution_id"      => $task->getDistributionID(),
                            "type"                  => "assessment",
                            "assessment_sub_type"   => $assessment_sub_type,
                            "assessor_type"         => $task->getAssessorType(),
                            "assessor_value"        => $task->getAssessorValue(),
                            "dassessment_id"        => ($id_type == "current_task_id" ? $task->getDAssessmentID() : false),
                            "assessor_group"        => ($task->getAssessorType() == "internal" && $distribution ? $task->getAssessorGroup($distribution->getOrganisationID()) : ""),
                            "assessor_role"         => ($task->getAssessorType() == "internal" && $distribution ? $task->getAssessorRole($distribution->getOrganisationID()) : ""),
                            "title"                 => $task->getTitle(),
                            "rotation_start_date"   => $task->getRotationStartDate(),
                            "rotation_end_date"     => $task->getRotationEndDate(),
                            "delivery_date"         => $task->getDeliveryDate(),
                            "schedule_details"      => $task->getScheduleDetails(),
                            "targets" => array(
                                array(
                                    $id_type            => $task->getID(),
                                    "target_type"       => $task->getTargetType(),
                                    "target_value"      => $task->getTargetValue(),
                                    "name"              => $task->getTarget(),
                                    "group"             => ($task->getTargetType() == "proxy_id" && $distribution ? $task->getTargetGroup($distribution->getOrganisationID()) : ""),
                                    "role"              => ($task->getTargetType() == "proxy_id" && $distribution ? $task->getTargetRole($distribution->getOrganisationID()) : "")
                                )
                            )
                        );
                    }
                }
            }

            return $grouped_tasks;
        }
        return false;
    }

    public static function groupTasksObjects($tasks) {
        $grouped_tasks = array();
        if ($tasks && is_array($tasks)) {
            foreach ($tasks as $task) {

                $distribution = Models_Assessments_Distribution::fetchRowByID($task->getDistributionID());
                $match_found = false;

                // ID is either future or current task ID, depending on the type of task.
                if (get_class($task) == "Models_Assessments_FutureTaskSnapshot") {
                    $id_type = "future_task_id";
                } else {
                    $id_type = "current_task_id";
                }

                // Search for an identical task already added to the grouped tasks.
                foreach ($grouped_tasks as $ctr => $grouped_task) {
                    if (
                        $grouped_task->getDistributionID()          == $task->getDistributionID() &&
                        $grouped_task->getAssessorType()            == $task->getAssessorType() &&
                        $grouped_task->getAssessorValue()           == $task->getAssessorValue() &&
                        $grouped_task->getTitle()                   == $task->getTitle() &&
                        $grouped_task->getRotationStartDate()       == $task->getRotationStartDate() &&
                        $grouped_task->getRotationEndDate()         == $task->getRotationEndDate() &&
                        $grouped_task->getDeliveryDate()            == $task->getDeliveryDate() &&
                        $grouped_task->getScheduleDetails()         == $task->getScheduleDetails()  &&
                        ($grouped_task->getDAssessmentID() ? ($grouped_task->getDAssessmentID() == $task->getDAssessmentID() ? true : false) : false)
                    ) {
                        // Add the individual task's target to the found grouped task's targets array.
                        $match_found = true;
                        $targets = $grouped_tasks[$ctr]->getTargets();
                        $targets[] = array(
                            $id_type        => $task->getID(),
                            "target_type"   => $task->getTargetType(),
                            "target_value"  => $task->getTargetValue(),
                            "name"          => $task->getTarget(),
                            "group"         => ($task->getTargetType() == "proxy_id" && $distribution ? $task->getTargetGroup($distribution->getOrganisationID()) : ""),
                            "role"          => ($task->getTargetType() == "proxy_id" && $distribution ? $task->getTargetRole($distribution->getOrganisationID()) : "")
                        );
                        $grouped_tasks[$ctr]->setTargets($targets);
                    }
                }

                // If no match was found in the existing grouped tasks, add a new one.
                if (!$match_found) {
                    if ($task->getAssessorType() == "internal") {
                        $assessor = Models_User::fetchRowByID($task->getAssessorValue());
                        $assessor_name = $assessor->getFullname(false);
                    } else {
                        $assessor = Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($task->getAssessorValue());
                        $assessor_name = $assessor->getFirstname() . " " . $assessor->getLastName();
                    }

                    $assessment_sub_type = "rotation_schedule";
                    $assessor = Models_Assessments_Assessor::fetchRowByID($id_type == "current_task_id" ? $task->getDAssessmentID() : false);

                    if ($assessor && $assessor->getAssociatedRecordType() == "event_id") {
                        $assessment_sub_type = "learning_event";
                    } else if (is_null($task->getRotationStartDate()) || $task->getRotationStartDate() == "" || !$task->getRotationEndDate()) {
                        $assessment_sub_type = "date_range";
                    }

                    $grouped_tasks[] = new Entrada_Utilities_Assessments_AssessmentTask(array(
                        "type"                  => "assessment",
                        "assessment_sub_type"   => $assessment_sub_type,
                        "adistribution_id"      => $task->getDistributionID(),
                        "assessor"              => $assessor ? $assessor_name : false,
                        "assessor_type"         => $task->getAssessorType(),
                        "assessor_value"        => $task->getAssessorValue(),
                        "dassessment_id"        => ($id_type == "current_task_id" ? $task->getDAssessmentID() : false),
                        "group"                 => ($task->getAssessorType() == "internal" && $distribution ? $task->getAssessorGroup($distribution->getOrganisationID()) : ""),
                        "role"                  => ($task->getAssessorType() == "internal" && $distribution ? $task->getAssessorRole($distribution->getOrganisationID()) : ""),
                        "title"                 => $task->getTitle(),
                        "rotation_start_date"   => $task->getRotationStartDate(),
                        "rotation_end_date"     => $task->getRotationEndDate(),
                        "delivery_date"         => $task->getDeliveryDate(),
                        "schedule_details"      => $task->getScheduleDetails(),
                        "targets" => array(
                            array(
                                $id_type            => $task->getID(),
                                "target_type"       => $task->getTargetType(),
                                "target_value"      => $task->getTargetValue(),
                                "name"              => $task->getTarget(),
                                "group"             => ($task->getTargetType() == "proxy_id" && $distribution ? $task->getTargetGroup($distribution->getOrganisationID()) : ""),
                                "role"              => ($task->getTargetType() == "proxy_id" && $distribution ? $task->getTargetRole($distribution->getOrganisationID()) : "")
                            )
                        )
                    ));
                }
            }
        }
        return $grouped_tasks;
    }

}