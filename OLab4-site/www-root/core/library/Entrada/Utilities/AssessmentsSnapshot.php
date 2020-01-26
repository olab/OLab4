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
        // Leverage distribution progress to determine all tasks. Filter tasks later.
        $distributions = Models_Assessments_Distribution::fetchAllRecordsIgnoreDeletedDate();
        if ($distributions) {
            global $db;
            $future_tasks = $existing_tasks = array();

            foreach ($distributions as $distribution) {
                $delegator = Models_Assessments_Distribution_Delegator::fetchRowByDistributionID($distribution->getID());
                if ($delegator) {

                    // Delegation tasks are never in the future, they already exist, so they can be added directly to the existing tasks array.
                    $distribution_delegation = new Entrada_Utilities_Assessments_DistributionDelegation(array("adistribution_id" => $distribution->getID()));
                    $individual_tasks = $distribution_delegation->buildFlatAssessmentList(true, true);

                    if ($individual_tasks) {
                        foreach ($individual_tasks as $individual_task) {
                            $existing_tasks[] = array(
                                "adistribution_id" => $individual_task[0],
                                "distribution_deleted_date" => $individual_task[1],
                                "distribution_title" => $individual_task[2],
                                "assessor_name" => $individual_task[3],
                                "target_name" => $individual_task[4],
                                "form_title" => $individual_task[5],
                                "schedule_details" => $individual_task[6],
                                "progress_details" => $individual_task[7]
                            );
                        }
                    }
                } else {

                    // Process non-delegation tasks to determine if they should already exist.
                    $distribution_assessment_utility = new Entrada_Utilities_Assessments_DistributionAssessment(array("adistribution_id" => $distribution->getID()));
                    $distribution_assessment_utility->buildAssessmentTaskList(null, null, true);
                    $form_data = $distribution_assessment_utility->getForm();

                    $distribution_data = array("task_list" => $distribution_assessment_utility->getTaskList(), "form_data" => $form_data);
                    $this->processTasks($distribution_data, $distribution, $future_tasks, $existing_tasks);
                }
            }

            // Drop the current snapshots.
            Models_Assessments_FutureTaskSnapshot::truncate();
            Models_Assessments_ExistingTaskSnapshot::truncate();

            // Declare a maximum line limit so we don't exceed the max allowed SQL packet size with massive statements.
            // Theoretically this could still max out, but counting individual characters would be overkill here.
            $max_sql_line_limit = 500;

            if (!empty($future_tasks)) {
                // Split up tasks based on max lines.
                $tasks = array();
                $outer_ctr = $line_ctr = 0;
                foreach ($future_tasks as $future_task) {
                    if ($line_ctr >= $max_sql_line_limit) {
                        $outer_ctr++;
                        $line_ctr = 0;
                    }
                    $tasks[$outer_ctr][$line_ctr] = $future_task;
                    $line_ctr++;
                }

                // Bulk insert task sets.
                $future_task_model = new Models_Assessments_FutureTaskSnapshot();
                foreach ($tasks as $task_set) {
                    if (!$future_task_model->bulkInsertOnDuplicateKeyUpdate($task_set)) {
                        application_log("error", "Unable to create future tasks (AssessmentsSnapshot), DB said: {$db->ErrorMsg()}");
                    }
                }
            }

            if (!empty($existing_tasks)) {
                // Split up tasks based on max lines.
                $tasks = array();
                $outer_ctr = $line_ctr = 0;
                foreach ($existing_tasks as $existing_task) {
                    if ($line_ctr >= $max_sql_line_limit) {
                        $outer_ctr++;
                        $line_ctr = 0;
                    }
                    $tasks[$outer_ctr][$line_ctr] = $existing_task;
                    $line_ctr++;
                }

                // Bulk insert task sets.
                $existing_task_model = new Models_Assessments_ExistingTaskSnapshot();
                foreach ($tasks as $task_set) {
                    if (!$existing_task_model->bulkInsertOnDuplicateKeyUpdate($task_set)) {
                        application_log("error", "Unable to create existing tasks (AssessmentsSnapshot), DB said: {$db->ErrorMsg()}");
                    }
                }
            }
        }
    }

    public function processTasks($distribution_data, $distribution, &$future_tasks, &$existing_tasks) {
        global $translate;

        foreach ($distribution_data["task_list"] as $grouping) {
            foreach ($grouping as $task) {
                // If the task is pending and projected to take place in the future, we need to store it for reference in the "upcoming tasks" list in the public interface. Otherwise it is current.
                if (!empty($task["targets"])) {
                    if ($task["meta"]["delivery_date"] >= time()) {
                        // We only want to store future tasks for active distributions.
                        if (!$distribution->getDeletedDate()) {
                            foreach ($task["targets"] as $target) {

                                $schedule_details = false;
                                if ($task["meta"]["associated_record_type"] == "schedule_id" && $task["meta"]["associated_record_id"]) {
                                    $schedule = Models_Schedule::fetchRowByID($task["meta"]["associated_record_id"]);
                                    $schedule_details = Entrada_Assessments_Base::getConcatenatedBlockString(($target["dassessment_id"] ? $target["dassessment_id"] : false), ($schedule ? $schedule : false), $task["meta"]["start_date"], $task["meta"]["end_date"], $task["meta"]["organisation_id"]);
                                }

                                $future_tasks[] = array(
                                    "assessment_type_id" => 1,
                                    "assessment_method_id" => 1,
                                    "task_type" => $target["task_type"],
                                    "assessor_type" => $task["assessors"][0]["assessor_type"],
                                    "assessor_value" => $task["assessors"][0]["assessor_value"],
                                    "target_type" => $target["target_type"],
                                    "target_value" => $target["target_value"],
                                    "title" => $distribution->getTitle(),
                                    "adistribution_id" => $distribution->getID(),
                                    "organisation_id" => $distribution->getOrganisationID(),
                                    "form_id" => $distribution->getFormID(),
                                    "min_submittable" => $task["meta"]["min_submittable"],
                                    "max_submittable" => $task["meta"]["max_submittable"],
                                    "feedback_required" => $task["meta"]["feedback_required"],
                                    "start_date" => $task["meta"]["start_date"],
                                    "end_date" => $task["meta"]["end_date"],
                                    "rotation_start_date" => $task["meta"]["rotation_start_date"],
                                    "rotation_end_date" => $task["meta"]["rotation_end_date"],
                                    "expiry_date" => $task["meta"]["expiry_date"],
                                    "expiry_notification_date" => $task["meta"]["expiry_notification_date"],
                                    "schedule_details" => $schedule_details,
                                    "delivery_date" => $task["meta"]["delivery_date"],
                                    "associated_record_type" => $task["meta"]["associated_record_type"],
                                    "associated_record_id" => $task["meta"]["associated_record_id"],
                                    "additional_assessment" => $task["meta"]["additional_assessment"],
                                    "created_date" => time(),
                                    "created_by" => 1
                                );
                            }
                        }
                    } else {
                        $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($distribution->getID());
                        $schedule_string = date("Y-m-d", $task["meta"]["delivery_date"]);
                        if ($distribution_schedule) {
                            $schedule = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID());
                            if ($schedule) {
                                $schedule_badge_text = Entrada_Assessments_Base::getConcatenatedBlockString(
                                    null,
                                    $schedule,
                                    $task["meta"]["rotation_start_date"] ? $task["meta"]["rotation_start_date"] : $task["meta"]["start_date"],
                                    $task["meta"]["rotation_end_date"] ? $task["meta"]["rotation_end_date"] : $task["meta"]["end_date"],
                                    $task["meta"]["organisation_id"],
                                    " - ",
                                    ", ",
                                    true,
                                    true
                                );
                                if ($schedule_badge_text) {
                                    $schedule_string .= " ({$schedule_badge_text})";
                                }
                            }
                        }

                        foreach ($task["assessors"] as $assessor) {
                            foreach ($task["targets"] as $target) {

                                // Progress can be a little more complicated than single complete/inprogress value.
                                // Determine where to store the target within the progress array.
                                $progress_counts = array("pending" => 0, "inprogress" => 0, "complete" => 0);
                                $progress_found = false;
                                foreach ($target["progress"] as $progress_value => $target_progress) {
                                    foreach ($target_progress as $progress) {
                                        if (!empty($progress)) {
                                            $progress_found = true;
                                            if ($target["should_exist"] || $progress_value == "complete") {
                                                $progress_counts[$progress_value]++;
                                            }
                                        }
                                    }
                                }
                                // If we did not find any progress, we can safely assume the task is pending.
                                if (!$progress_found) {
                                    if ($target["should_exist"]) {
                                        $progress_counts["pending"]++;
                                    }
                                }

                                $progress_string = "";
                                if (!empty($progress_counts["complete"])) {
                                    $progress_string .= "{$progress_counts["complete"]} " . $translate->_("Complete");
                                }
                                if (!empty($progress_counts["inprogress"])) {
                                    $progress_string .= "{$progress_counts["inprogress"]} " . $translate->_("In Progress");
                                }
                                if (!empty($progress_counts["pending"])) {
                                    $progress_string .= "{$progress_counts["pending"]} " . $translate->_("Pending");
                                }

                                $existing_tasks[] = array(
                                    "adistribution_id" => $distribution->getID(),
                                    "distribution_deleted_date" => $distribution->getDeletedDate(),
                                    "distribution_title" => $distribution->getTitle(),
                                    "assessor_name" => $assessor["name"],
                                    "target_name" => $target["target_name"],
                                    "form_title" => (!empty($distribution_data["form_data"]) ? $distribution_data["form_data"]["title"] : ""),
                                    "schedule_details" => $schedule_string,
                                    "progress_details" => $progress_string
                                );
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

                    $grouped_tasks[] = new Entrada_Utilities_Assessments_DeprecatedAssessmentTask(array(
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