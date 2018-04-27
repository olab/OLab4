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
 * @author Developer (of refactor): Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */
class Entrada_Utilities_Assessments_QueueAssessment extends Entrada_Assessments_Base {

    public function run ($verbosity = false) {
        global $ENTRADA_TEMPLATE;

        $this->setVerbose($verbosity);

        $distributions = Models_Assessments_Distribution::fetchAllRecords();
        if ($distributions) {
            foreach ($distributions as $distribution) {
                $this->clearStorage();
                $queue_start_time = microtime(true);
                $this->verboseOut("Queuing assessments for {$distribution->getID()}. ");
                $ENTRADA_TEMPLATE->setActiveTemplate($distribution->getOrganisationID());
                $delegator = Models_Assessments_Distribution_Delegator::fetchRowByDistributionID($distribution->getID());
                if ($delegator) {
                    $this->verboseOut("Delegation: ");
                    $this->queueDelegationTasks($distribution, $delegator);
                } else {
                    $this->verboseOut("Non-delegation: ");
                    $this->queueNonDelegationTasks($distribution);
                }

                // Adjust expiry where needed.
                $this->processDistributionAssessmentExpiry($distribution);

                $queue_end_time = microtime(true);
                $queue_runtime = sprintf("%.3f", ($queue_end_time - $queue_start_time));
                $colour = ($queue_runtime > 1) ? "red" : "blue";
                $this->verboseOut("Assessments queued for {$distribution->getID()}. Took {$this->cliString($queue_runtime, $colour)} seconds. \n");

                // Process distribution options and apply them as assessment options as necessary. Delegation based
                // distributions already have this done upon confirming delegation assignment and creating assessments.
                if (!$delegator) {
                    $options_start_time = microtime(true);
                    $this->verboseOut("Processing distribution options for {$distribution->getID()}.");
                    $this->processDistributionAssessmentOptions($distribution->getID());
                    $options_end_time = microtime(true);
                    $options_runtime = sprintf("%.3f", ($options_end_time - $options_start_time));
                    $this->verboseOut("Distribution options processed for {$distribution->getID()}. Took {$options_runtime} seconds. \n");
                }
            }
            $this->verboseOut("\n{$this->cliString("Queueing completed", "green")}.\n");
        }
        $stale_start = time() - (86400 * 90);
        $stale_end = time() - (86400 * 14);

        // Unfinished/untouched assessments for 2 weeks back, up to 90 days in the past
        // Keep disabled for now.
        //$this->verboseOut("Queueing stale notifications.\n");
        //$this->queueStaleNotifications($stale_start, $stale_end);
    }

    //-- Assessment record creation --//

    /**
     * Create a Distribution Assessment record.
     *
     * @param Models_Assessments_Distribution $distribution
     * @param null $assessor
     * @param null $distribution_id
     * @param null $min_submittable
     * @param null $max_submittable
     * @param int $start_date
     * @param int $end_date
     * @param int $rotation_start_date
     * @param int $rotation_end_date
     * @param null $delivery_date
     * @param bool $additional
     * @param int $associated_record_id
     * @param string $associated_record_type
     * @param int $course_id
     * @param int|null $expiry_date
     * @param int|null $expiry_notification_date
     * @return bool|Models_Assessments_Assessor
     */
    private function saveAssessment(
        $distribution = null,
        $assessor = null,
        $distribution_id = null,
        $min_submittable = null,
        $max_submittable = null,
        $start_date = 0,
        $end_date = 0,
        $rotation_start_date = 0,
        $rotation_end_date = 0,
        $delivery_date = null,
        $additional = false,
        $associated_record_id = null,
        $associated_record_type = null,
        $course_id = null,
        $expiry_date = null,
        $expiry_notification_date = null
    ) {
        $external_hash = null;
        if ($assessor["assessor_type"] == "external") {
            $external_hash = generate_hash();
        }
        // Insantiate assessment API to create the assessment.
        $assessment_api = new Entrada_Assessments_Assessment(
            array(
                "actor_proxy_id" => 1,
                "actor_organisation_id" => $distribution->getOrganisationID(),
                "limit_dataset" => array("assessor")
            )
        );
        $distribution_assessment = array(
            "adistribution_id" => $distribution_id,
            "assessor_type" => $assessor["assessor_type"],
            "assessor_value" => $assessor["assessor_value"],
            "form_id" => $distribution->getFormID(),
            "organisation_id" => $distribution->getOrganisationID(),
            "min_submittable" => $min_submittable,
            "max_submittable" => $max_submittable,
            "feedback_required" => $distribution->getFeedbackRequired(),
            "start_date" => $start_date,
            "end_date" => $end_date,
            "rotation_start_date" => $rotation_start_date,
            "rotation_end_date" => $rotation_end_date,
            "delivery_date" => $delivery_date,
            "expiry_date" => $expiry_date,
            "expiry_notification_date" => $expiry_notification_date,
            "external_hash" => $external_hash,
            "additional_assessment" => ($additional ? 1 : 0),
            "created_date" => time(),
            "created_by" => 1,
            "associated_record_id" => $associated_record_id,
            "associated_record_type" => $associated_record_type,
            "course_id" => $course_id
        );
        $status = $assessment_api->createAssessment($distribution_assessment);
        if (!$status) {
            foreach ($assessment_api->getErrorMessages() as $error) {
                $this->verboseOut("Failed to create assessment: {$this->cliString($error, "red", "black")} \n");
            }
            return false;
        }
        return $assessment_api->getAssessmentRecord();
    }

    /**
     * Create a Distribution Assessment Target record.
     *
     * @param Models_Assessments_Distribution $distribution
     * @param int $distribution_id
     * @param int $dassessment_id
     * @param int $target_value
     * @param string $target_type
     * @return bool
     */
    private function saveAssessmentTarget($distribution, $distribution_id = null, $dassessment_id = null, $target_value = null, $target_type = "proxy_id") {
        $assessment_api = new Entrada_Assessments_Assessment(
            array(
                "actor_proxy_id" => 1,
                "actor_organisation_id" => $distribution->getOrganisationID(),
                "limit_dataset" => array("assessment"),
                "dassessment_id" => $dassessment_id
            )
        );
        $result = $assessment_api->createAssessmentTarget(array(
            "adistribution_id" => $distribution_id,
            "dassessment_id" => $dassessment_id,
            "target_type" => $target_type,
            "target_value" => $target_value,
            "task_type" => $distribution->getAssessmentType()
        ));
        if (!$result) {
            foreach ($assessment_api->getErrorMessages() as $error) {
                $this->verboseOut("Failed to create assessment target: {$this->cliString($error, "red", "black")} \n");
            }
            return false;
        }
        return true;
    }

    /**
     * Send notifications for stale assessments (those that fall between the date range).
     *
     * @param int $lower_date_range
     * @param int $upper_date_range
     */
    private function queueStaleNotifications($lower_date_range, $upper_date_range) {

        /* Assessment Notifications */

        // Find all the assessment records that were created that have no progress for them within the date window.
        $assessments_to_notify = array();
        $assessments = Models_Assessments_Assessor::fetchAllByDeliveryDateRange($lower_date_range, $upper_date_range);
        if ($assessments) {
            foreach ($assessments as $assessment) {
                $progress_records = Models_Assessments_Progress::fetchAllByDassessmentID($assessment->getID());
                if ($progress_records) {
                    // Add the incomplete progress records to the list of notifications to send, if they're not already deleted
                    foreach ($progress_records as $progress) {
                        if ($progress->getProgressValue() == "inprogress") {
                            $assessments_to_notify[$progress->getDassessmentID()] = array("distribution_id" => $assessment->getADistributionID(), "assessor_value" => $assessment->getAssessorValue(), "assessor_type" => $assessment->getAssessorType());
                        }
                    }
                } else {
                    // Otherwise, there's no progress record for the distribution assessment record, so we will notify the assessor for the entire thing (if there are multiple targets)
                    $assessments_to_notify[$assessment->getID()] = array("distribution_id" => $assessment->getADistributionID(), "assessor_value" => $assessment->getAssessorValue(), "assessor_type" => $assessment->getAssessorType());
                }

                // Prune the deleted tasks. Unfortunately, the deleted tasks table doesn't link to dassessment_id, so we have to match and prune this way.
                $deleted_tasks = Models_Assessments_DeletedTask::fetchAllByAdistributionID($assessment->getADistributionID());
                foreach ($deleted_tasks as $dt) {
                    if ($dt->getDeliveryDate() == $assessment->getDeliveryDate() &&
                        $dt->getAssessorValue() == $assessment->getAssessorValue() &&
                        $dt->getAssessorType() == $assessment->getAssessorType()
                    ) {
                        // task is deleted, prune it from our list
                        unset($assessments_to_notify[$assessment->getID()]);
                    }
                }
            }
        }

        // For all of the collected stale assessments, queue up the notifications.
        foreach ($assessments_to_notify as $dassessment_id => $notify_info) {
            $dassessment = Models_Assessments_Assessor::fetchRowByID($dassessment_id);
            $distribution = Models_Assessments_Distribution::fetchRowByID($notify_info["distribution_id"]);
            if ($dassessment && $distribution) {
                $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($distribution->getID());
                $schedule_id = null;
                if ($distribution_schedule) {
                    $schedule_id = $distribution_schedule->getScheduleID();
                }
                $this->queueAssessorNotifications($dassessment, $dassessment->getAssessorValue(), $schedule_id, $distribution->getNotifications(), true);
            }
        }

        /* Delegation Notifications */

        // Find all delegation tasks created within the window that have not been dismissed via the "complete" button
        $delegations_to_notify = array();
        $delegations = Models_Assessments_Distribution_Delegation::fetchAllByCreatedDateIncomplete($lower_date_range, $upper_date_range);
        foreach ($delegations as $delegation) {
            $delegations_to_notify[$delegation->getID()] = array("distribution_id" => $delegation->getDistributionID(), "delegator_id" => $delegation->getDelegatorID());
        }

        // For all collected stale delegations, queue up those notifications (as reminders)
        foreach ($delegations_to_notify as $delegation_id => $notify_info) {
            $distribution = Models_Assessments_Distribution::fetchRowByID($notify_info["distribution_id"]);
            $delegation = Models_Assessments_Distribution_Delegation::fetchRowByID($delegation_id);
            if ($distribution && $delegation) {
                $this->queueDelegatorNotifications($distribution, $delegation, $notify_info["delegator_id"], $distribution->getNotifications(), false, true);
            }
        }
    }

    //-- Main Logic --//

    /**
     * Iterate through distributions and create assessment tasks for delegation based distributions that require them, notifying where applicable.
     *
     * @param $distribution
     * @param $delegator
     * @param bool $mark_notifications_as_sent
     * @return bool
     */
    private function queueDelegationTasks($distribution, $delegator, $mark_notifications_as_sent = false) {
        $this->verboseOut("Generating delegation tasks via buildDelegationTaskList. ");
        $task_count = 0;

        $assessors = $distribution->getAssessors(null);
        if (empty($assessors)) {
            $this->verboseOut("No assessors found. ");
            return false;
        }

        $distribution_delegation = new Entrada_Utilities_Assessments_DistributionDelegation(array("adistribution_id" => $distribution->getID()));
        $task_list = $distribution_delegation->buildDelegationTaskList();

        // The task list is grouped by distribution ID, then by a key generated by the relevant dates
        // The default storage key is: $delivery_date-$release_date-$start_date-$end_date
        // The task list takes care of the logic of whether a task should exist or not, we simply iterate through it and create the ones that "should exist"
        if ($task_list && !empty($task_list)) {
            foreach ($task_list as $distribution_id => $task) {
                foreach ($task as $task_index => $task_data) {
                    // This flag takes all states into account; if the task was deleted or otherwise deactivated, then should_exist will be set false.
                    if ($task_data["meta"]["should_exist"] && !$task_data["meta"]["deleted_date"]) {
                        // The task should exist. Does it exist already?
                        if (empty($task_data["current_record"])) {
                            // The task does not exist, so create it.
                            $delegation_task = $this->createDistributionDelegationTask($distribution->getID(), $delegator->getDelegatorID(), $task_data["meta"]["delivery_date"], $task_data["meta"]["start_date"], $task_data["meta"]["end_date"]);
                            if ($delegation_task) {
                                $task_count++;
                                $this->queueDelegatorNotifications($distribution, $delegation_task, $delegator->getDelegatorID(), $distribution->getNotifications(), $mark_notifications_as_sent);
                            } else {
                                application_log("error", "Failed to create delegation task for {$distribution->getID()}");
                            }
                        }
                    }
                }
            }
        }
        $this->verboseOut("Created $task_count delegation tasks. ");
    }

    /**
     * Iterate through distributions and create assessment tasks for non-delegation based distributions that require them, notifying where applicable.
     *
     * @param $distribution
     * @return bool
     */
    private function queueNonDelegationTasks($distribution) {

        $distribution_eventtype = Models_Assessments_Distribution_Eventtype::fetchAllByAdistributionID($distribution->getID());
        if (is_array($distribution_eventtype) && !empty($distribution_eventtype)) {
            $this->verboseOut("Learning Event based. ");
            $this->queueAssessmentsLearningEvent($distribution);

        } else {
            $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($distribution->getID());
            $schedule = false;
            if ($distribution_schedule) {
                $schedule = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID());
                if ($schedule) {
                    $this->verboseOut("Rotation schedule based. ");
                } else {
                    application_log("error", "Distribution has distribution_schedule record, but the schedule was not found. Unable to create assessments. distribution_id = {$distribution->getID()}, distribution_schedule_id = {$distribution_schedule->getID()}");
                }
            } else {
                $this->verboseOut("Date range based. ");
            }

            $this->queueRotationDateRangeAssessments($distribution, $schedule);
        }
        return true;
    }

    /**
     * Queue rotation schedule and date range based assessments.
     *
     * @param Models_Assessments_Distribution $distribution
     * @param Models_Schedule $schedule
     */
    private function queueRotationDateRangeAssessments($distribution, $schedule) {
        $db_errors = 0;

        $this->verboseOut("Queuing assessments. ");
        // Fetch the list of assessment tasks to create.
        $distribution_assessment_utility = new Entrada_Utilities_Assessments_DistributionAssessment(array("adistribution_id" => $distribution->getID()));

        $this->verboseOut("START building assessment task list for {$distribution->getID()}. ");
        $distribution_assessment_utility->buildAssessmentTaskList(null, time());
        $task_list = $distribution_assessment_utility->getTaskList();
        $this->verboseOut("FINISHED building assessment task list for {$distribution->getID()}. ");

        // Count the tasks and targets to be created.
        $possible_task_count = 0;
        $possible_task_target_count = 0;
        if ($task_list && !empty($task_list)) {
            foreach ($task_list[$distribution->getID()] as $task) {
                if ($task["meta"]["should_exist"]) {
                    $possible_task_count++;
                    $possible_task_target_count += @count($task["targets"]);
                }
            }
        }
        $this->verboseOut("Will create approximately $possible_task_count assessments with $possible_task_target_count targets. **NOTE: This is approximate. If targets are deleted, there will be fewer targets created than this approximation.** ");

        $assessments_created_count = 0;
        $assessment_targets_created_count = 0;

        if ($task_list && !empty($task_list)) {
            foreach ($task_list[$distribution->getID()] as $task) {
                // Ensure the task should exist, meaning we are past the delivery date, release date, and it is not deleted.
                if ($task["meta"]["should_exist"]) {

                    $assessment = false;
                    // If there is no current record, the task does not exist yet.
                    if (!$task["current_record"] || empty($task["current_record"])) {
                        // Create an assessment task for each assessor (for these "normal" assessments, there will only be one assessor).
                        foreach ($task["assessors"] as $assessor) {
                            $assessment = $this->saveAssessment(
                                $distribution,
                                $assessor,
                                $distribution->getID(),
                                $task["meta"]["min_submittable"],
                                $task["meta"]["max_submittable"],
                                $task["meta"]["start_date"],
                                $task["meta"]["end_date"],
                                $task["meta"]["rotation_start_date"],
                                $task["meta"]["rotation_end_date"],
                                $task["meta"]["delivery_date"],
                                $task["meta"]["additional_assessment"],
                                $task["meta"]["associated_record_id"],
                                $task["meta"]["associated_record_type"],
                                $task["meta"]["course_id"],
                                $task["meta"]["expiry_date"],
                                $task["meta"]["expiry_notification_date"]
                            );
                            if ($assessment) {
                                $assessments_created_count++;
                                $this->queueAssessorNotifications($assessment, $assessment->getAssessorValue(), ($schedule ? $schedule->getID() : null), $distribution->getNotifications());
                            } else {
                                $db_errors++;
                                application_log("error", "Error adding to cbl_distribution_assessments for distribution ID: '{$distribution->getID()}'.");
                            }
                        }
                    } else {
                        $assessment = $task["current_record"];
                    }

                    // We could not reliably determine or create the assessment and cannot continue.
                    if (!$assessment) {
                        continue;
                    }

                    // Process the targets for each assessment that was found/created.
                    foreach ($task["targets"] as $target) {
                        // Ensure the target should exist, meaning it is not deleted.
                        if ($target["should_exist"]) {
                            // If there is no current record, the target does not exist yet.
                            if (!$target["current_record"]) {
                                if ($this->saveAssessmentTarget($distribution, $distribution->getID(), $assessment->getID(), $target["target_value"], $target["target_type"])) {
                                    $assessment_targets_created_count++;
                                } else {
                                    $db_errors++;
                                    application_log("error", "Error adding to cbl_distribution_assessment_targets for distribution ID: '{$distribution->getID()}' / assessment id: {$assessment->getID()}");
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($db_errors) {
            application_log("error", "Errors (total: $db_errors) when attempting to add cbl_distribution_assessments records and/or cbl_distribution_assessment_targets for distribution ID: '{$distribution->getID()}'");
        }

        $this->verboseOut("Assessments created: $assessments_created_count (target records: $assessment_targets_created_count). ");
    }

    /**
     * Queue assessments for Eventtype Type distributions
     *
     * @param array $distribution
     * @param bool $create_notification_as_sent
     */
    private function queueAssessmentsLearningEvent($distribution, $create_notification_as_sent = false) {
        $db_errors = 0;

        $release_date = (is_null($distribution->getReleaseDate()) ? 0 : (int)$distribution->getReleaseDate());

        $this->verboseOut("Queuing assessments learning event. ");
        // Fetch the list of assessment tasks to create
        $distribution_learning_event  = new Entrada_Utilities_Assessments_DistributionLearningEvent(array("adistribution_id" => $distribution->getID(), "release_date" => $release_date));

        $this->verboseOut("START building learning event assessment task list for {$distribution->getID()}. ");
        $tasks_to_create = $distribution_learning_event->buildLearningEventAssessmentTaskList();
        $this->verboseOut("FINISHED building learning event assessment task list for {$distribution->getID()}. ");

        $possible_task_count = 0;
        $possible_task_target_count = 0;
        if ($tasks_to_create && !empty($tasks_to_create)) {
            foreach ($tasks_to_create as $event_groupings) {
                foreach ($event_groupings as $event_id => $event_data) {
                    $possible_task_count += $event_data["meta"]["assessor_count"];
                    $possible_task_target_count += $event_data["meta"]["assessor_count"] * $event_data["meta"]["target_count"];
                }
            }
        }
        $this->verboseOut("Will create approximately $possible_task_count assessments with $possible_task_target_count targets. **NOTE: This is approximate. If there are no targets or assessors, there will be fewer tasks created than this approximation.** ");

        $assessments_created_count = 0;
        $assessment_targets_created_count = 0;
        $events_count = 0;

        if ($tasks_to_create && !empty($tasks_to_create)) {
            foreach ($tasks_to_create as $event_groupings) {
                foreach ($event_groupings as $event_id => $event_data) {
                    if ($event_data["meta"]["target_count"] && $event_data["meta"]["assessor_count"] && $event_data["meta"]["target_type"] == "eventtype_id") {
                        $events_count++;
                        // For each assessor, create an assessment task
                        foreach ($event_data["assessors"] as $assessor_key => $assessor) {
                            $assessor_key_a = explode("-", $assessor_key);
                            $assessor_value = $assessor_key_a[0]; // A proxy id or external assessor id
                            $assessor_type = $assessor_key_a[1];  // "internal" or "external"

                            // Check to see if the task already exists.
                            $assessment = Models_Assessments_Assessor::fetchRowByADistributionIDAssessorTypeAssessorValueAssociatedRecordIDAssociatedRecordType($distribution->getID(), $assessor_type, $assessor_value, $event_id, "event_id");
                            if (!$assessment) {
                                // Create a new task.
                                $assessment = $this->saveAssessment(
                                    $distribution,
                                    array(
                                        "assessor_value" => $assessor_value,
                                        "assessor_type" => $assessor_type
                                    ),
                                    $distribution->getID(),
                                    $distribution->getMinSubmittable(),
                                    $distribution->getMaxSubmittable(),
                                    $event_data["meta"]["start_date"],
                                    $event_data["meta"]["end_date"],
                                    0,
                                    0,
                                    $event_data["meta"]["delivery_date"],
                                    false,
                                    $event_id,
                                    "event_id",
                                    $distribution->getCourseID(),
                                    $event_data["meta"]["expiry_date"],
                                    $event_data["meta"]["expiry_notification_date"]
                                );
                                if ($assessment) {
                                    $assessments_created_count++;
                                    // Send notification for this newly created assessment
                                    $this->queueAssessorNotifications($assessment, $assessment->getAssessorValue(), null, $distribution->getNotifications(), false, true, $create_notification_as_sent);
                                } else {
                                    $db_errors++;
                                    application_log("error", "Error adding to cbl_distribution_assessments for distribution ID: '{$distribution->getID()}' (for eventtype based distribution)");
                                }
                            }

                            // We could not reliably determine or create the assessment and cannot continue.
                            if (!$assessment) {
                                continue;
                            }

                            // Assessment was created or fetched successfully, so create missing targets for it.
                            foreach ($event_data["targets"] as $target_key => $target_data) {
                                $target_key_a = explode("-", $target_key);
                                $target_value = $target_key_a[0];   // proxy ID or event ID
                                $target_type = $target_type_postfix = $target_key_a[1]; // Default is proxy_id. External targets are not implemented (but supported here).
                                switch ($target_type_postfix) {
                                    case "internal":
                                        $target_type = "proxy_id";
                                        break;
                                    case "external":
                                        $target_type = "external_hash";
                                        break;
                                    case "eventtype":
                                        $target_type = "event_id";
                                        break;
                                }

                                // Check if the target already exists.
                                $target_exists = Models_Assessments_AssessmentTarget::fetchRowByDAssessmentIDTargetTypeTargetValueIncludeDeleted($assessment->getID(), $target_type, $target_value);
                                if ($target_exists) {
                                    continue;
                                }

                                if (!$this->saveAssessmentTarget($distribution, $distribution->getID(), $assessment->getID(), $target_value, $target_type)) {
                                    $db_errors++;
                                    application_log("error", "Error adding to cbl_distribution_assessment_targets for distribution ID: '{$distribution->getID()}' / assessment id: {$assessment->getID()}");
                                } else {
                                    $assessment_targets_created_count++;
                                }
                            }
                        }
                    }
                }
            }
        }
        if ($db_errors) {
            application_log("error", "Errors (total: $db_errors) when attempting to add cbl_distribution_assessments records and/or cbl_distribution_assessment_targets for distribution ID: '{$distribution->getID()}'");
        }

        $this->verboseOut("$events_count learning events processed. Assessments created: $assessments_created_count (target records: $assessment_targets_created_count) ");
    }

    //-- Assessment task creation logic --//

    /**
     * Create a delegation task record.
     *
     * @param int $distribution_id
     * @param int $delegator_id
     * @param int $start_date
     * @param int $end_date
     * @param int $delivery_date
     * @return bool
     */
    private function createDistributionDelegationTask($distribution_id, $delegator_id, $delivery_date, $start_date = null, $end_date = null) {
        // Check if this exact delegation already exists
        $exists = Models_Assessments_Distribution_Delegation::fetchAllByDistributionIDDelegatorIDDeliveryDateStartDateEndDate($distribution_id, $delegator_id, $delivery_date, $start_date, $end_date);
        if (!empty($exists)) {
            return true; // It's not an error state, it exists so we ignore it.
        } else {
            $distribution_delegation = new Entrada_Utilities_Assessments_DistributionDelegation(array("adistribution_id" => $distribution_id));

            // If there are no assessors for the delegation task, this returns true, but doesn't actually create a task.
            // NOTE: When it comes time to support external users as delegators, the delegator type should be passed in via parameter (in place of "proxy_id")
            return $distribution_delegation->createDistributionDelegationRecord($delegator_id, "proxy_id", $delivery_date, $start_date, $end_date);
        }
    }

}