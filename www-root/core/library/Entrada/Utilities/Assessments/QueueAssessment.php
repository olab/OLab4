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
class Entrada_Utilities_Assessments_QueueAssessment extends Entrada_Utilities_Assessments_Base {

    public function run ($verbosity = false) {
        global $ENTRADA_TEMPLATE;

        $this->setVerbose($verbosity);

        $distributions = Models_Assessments_Distribution::fetchAllRecordsByDateRange((time() - (86400 * 60)), time());
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

                $queue_end_time = microtime(true);
                $queue_runtime = sprintf("%.3f", ($queue_end_time - $queue_start_time));

                $colour = ($queue_runtime > 1) ? "red" : "blue";
                $this->verboseOut("Assessments queued for {$distribution->getID()}. Took {$this->cliString($queue_runtime, $colour)} seconds. \n");
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
     * @return bool|Models_Assessments_Assessor
     */
    private function saveAssessment(
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
        $associated_record_type = null
    ) {
        $external_hash = null;
        if ($assessor["assessor_type"] == "external") {
            $external_hash = generate_hash();
        }

        $distribution_assessment = new Models_Assessments_Assessor(array(
            "adistribution_id" => $distribution_id,
            "assessor_type" => $assessor["assessor_type"],
            "assessor_value" => $assessor["assessor_value"],
            "min_submittable" => $min_submittable,
            "max_submittable" => $max_submittable,
            "published" => 1,
            "start_date" => $start_date,
            "end_date" => $end_date,
            "rotation_start_date" => $rotation_start_date,
            "rotation_end_date" => $rotation_end_date,
            "delivery_date" => $delivery_date,
            "external_hash" => $external_hash,
            "additional_assessment" => ($additional ? 1 : 0),
            "created_date" => time(),
            "created_by" => 1,
            "associated_record_id" => $associated_record_id,
            "associated_record_type" => $associated_record_type
        ));

        if ($distribution_assessment->insert()) {
            return $distribution_assessment;
        } else {
            return false;
        }
    }

    /**
     * Create a Distribution Assessment Target record.
     *
     * @param int $distribution_id
     * @param int $dassessment_id
     * @param int $target_value
     * @param string $target_type
     * @return bool
     */
    private function saveAssessmentTarget($distribution_id = null, $dassessment_id = null, $target_value = null, $target_type = "proxy_id") {
        $target = new Models_Assessments_AssessmentTarget(array(
            "adistribution_id" => $distribution_id,
            "dassessment_id" => $dassessment_id,
            "target_type" => $target_type,
            "target_value" => $target_value,
            "created_date" => time(),
            "created_by" => 1,
            "updated_date" => time(),
            "updated_by" => 1
        ));

        if ($target->insert()) {
            return true;
        } else {
            return false;
        }
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
        $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($distribution->getID());
        if ($distribution_schedule) {
            $schedule = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID());
            if ($schedule) {
                $this->verboseOut("Rotation schedule based. ");
                $this->queueAssessmentsRotationSchedule($distribution, $distribution_schedule, $schedule); // ADRIAN-TODO: Move this logic (and all child functions) to AssessmentTask
            } else {
                application_log("error", "Distribution has distribution_schedule record, but the schedule was not found. Unable to create assessments. distribution_id = {$distribution->getID()}, distribution_schedule_id = {$distribution_schedule->getID()}");
            }
        } else {
            $distribution_eventtype = Models_Assessments_Distribution_Eventtype::fetchAllByAdistributionID($distribution->getID());
            if (is_array($distribution_eventtype) && !empty($distribution_eventtype)) {
                $this->verboseOut("Learning Event based. ");
                $this->queueAssessmentsLearningEvent($distribution);
            } else {
                $this->verboseOut("Date range based. ");
                $this->queueAssessmentsDateRange($distribution); // ADRIAN-TODO: Move this logic (and all child functions) to AssessmentTask
            }
        }
        $this->queueAssessmentsAdditional($distribution); // ADRIAN-TODO: Move this logic (and all child functions) to AssessmentTask
        return true;
    }

    /**
     * Queue date range based assessments.
     *
     * @param $distribution
     */
    private function queueAssessmentsDateRange($distribution) {
        global $db;

        if (time() >= $distribution->getDeliveryDate()) {
            $assessors = $distribution->getAssessors(null);
            if ($assessors) {
                foreach ($assessors as $assessor) {
                    $distribution_assessment_records = Models_Assessments_Assessor::fetchRowByADistributionIDAssessorTypeAssessorValueDeliveryDate($distribution->getID(), $assessor["assessor_type"], $assessor["assessor_value"], $distribution->getDeliveryDate());
                    if (!$distribution_assessment_records) {
                        $distribution_assessment = $this->saveAssessment($assessor, $distribution->getID(), $distribution->getMinSubmittable(), $distribution->getMaxSubmittable(), $distribution->getStartDate(), $distribution->getEndDate(), 0, 0, $distribution->getDeliveryDate());
                        if ($distribution_assessment) {
                            $this->queueAssessorNotifications($distribution_assessment, $distribution_assessment->getAssessorValue(), null, $distribution->getNotifications());
                        } else {
                            application_log("error", "An error occurred while attempting to save a cbl_distribution_assessments record DB said: " . $db->ErrorMsg());
                        }
                    }
                }
            }
        }
    }

    /**
     * Queue additional tasks added to a distribution.
     *
     * @param $distribution
     */
    private function queueAssessmentsAdditional($distribution) {
        global $db;

        // Check for any additional tasks that need an assessment created.
        $additional_tasks = Models_Assessments_AdditionalTask::fetchAllByADistributionID($distribution->getID());
        if ($additional_tasks) {
            $this->verboseOut("Adding additional assessments. ");
            foreach ($additional_tasks as $additional_task) {
                $distribution_assessment_records = Models_Assessments_Assessor::fetchRowByADistributionIDAssessorTypeAssessorValueDeliveryDate($distribution->getID(), $additional_task->getAssessorType(), $additional_task->getAssessorValue(), $additional_task->getDeliveryDate());
                if (!$distribution_assessment_records) {
                    $distribution_assessment = $this->saveAssessment(array("assessor_type" => $additional_task->getAssessorType(), "assessor_value" => $additional_task->getAssessorValue()), $distribution->getID(), $distribution->getMinSubmittable(), $distribution->getMaxSubmittable(), $distribution->getStartDate(), $additional_task->getDeliveryDate(), 0, 0, $additional_task->getDeliveryDate(), true);
                    if ($distribution_assessment) {
                        $this->queueAssessorNotifications($distribution_assessment, $distribution_assessment->getAssessorValue(), null, $distribution->getNotifications());
                    } else {
                        application_log("error", "An error occurred while attempting to save a cbl_distribution_assessments record for an additional task DB said: " . $db->ErrorMsg());
                    }
                }
            }
        }
    }

    /**
     * Queue assessment tasks based on a rotation schedule.
     *
     * @param $distribution
     * @param $distribution_schedule
     * @param $schedule
     */
    private function queueAssessmentsRotationSchedule($distribution, $distribution_schedule, $schedule) {
        $release_date = (is_null($distribution->getReleaseDate()) ? 0 : (int)$distribution->getReleaseDate());

        // This returns whatever was indicated in the distribution and must be expanded, e.g. if on-service learners were
        // specified, this returns the schedule_id, and must be turned into a list of proxy_ids.
        $distribution_targets = Models_Assessments_Distribution_Target::fetchAllByDistributionID($distribution->getID());

        // This always returns a flat, well-defined list of users.
        $assessors = $distribution->getAssessors(null);

        if ($distribution_targets && $assessors) {

            foreach ($distribution_targets as $distribution_target) {

                //echo "Assessor Option: {$distribution->getAssessorOption()} / Dist Target Type & Scope: {$distribution_target->getTargetType()}/{$distribution_target->getTargetScope()}\n";

                // Assessors are a rotation schedule's learners (on/off service)
                if ($distribution->getAssessorOption() == "learner") {

                    if ($distribution_target->getTargetType() == "schedule_id" && $distribution_target->getTargetScope() != "self") {

                        // Create assessment tasks for all the assessors, based on the learners in a rotation schedule. This method expands the rotation into the required list of learner proxy_ids.
                        $this->createAssessmentsForRotationLearnersByTarget($distribution, $distribution_schedule, $schedule, $release_date, $assessors, $distribution_target);

                    } else if ($distribution_target->getTargetType() == "schedule_id" && $distribution_target->getTargetScope() == "self") {

                        // On/off service learners assessing the rotation entity
                        // The rotation itself is the target (the rotation entity, not the users)
                        $this->createAssessmentsForRotationLearnersByAssessor($distribution, $distribution_schedule, $schedule, $release_date, $assessors, $distribution_target, true);

                    } else if ($distribution_target->getTargetType() == "self" && $distribution_target->getTargetScope() == "self") {

                        // Self assessment, use the assessors as targets
                        // fetch the users for a rotation, and use that as the assessors array.
                        $this->createAssessmentsForRotationLearnersBySelf($distribution, $distribution_schedule, $schedule, $release_date, $assessors, $distribution_target);

                    } else if ($distribution_target->getTargetType() == "proxy_id" && $distribution_target->getTargetScope() == "self") {

                        // Create assessment tasks for proxy ids. This is called when an additional user is added to the distribution.
                        $this->createAssessmentsForRotationLearnersByAssessor($distribution, $distribution_schedule, $schedule, $release_date, $assessors, $distribution_target, true);
                    } else {
                        // Default create for proxy id
                        $this->createAssessmentsForRotationLearnersByAssessor($distribution, $distribution_schedule, $schedule, $release_date, $assessors, $distribution_target, true);
                    }

                // Assessors are faculty or individuals
                } else if ($distribution->getAssessorOption() == "faculty" || $distribution->getAssessorOption() == "individual_users") {

                    if ($distribution_target->getTargetType() == "schedule_id" && $distribution_target->getTargetScope() != "self") {

                        // Target is on/off service learners. Faculty or individual (maybe external) assessing on/off service learners
                        $this->createAssessmentsForRotationLearnersByTarget($distribution, $distribution_schedule, $schedule, $release_date, $assessors, $distribution_target);

                    } else if ($distribution_target->getTargetType() == "schedule_id" && $distribution_target->getTargetScope() == "self") {

                        /** INVALID USE CASE */

                        // Faculty/External assessing the rotation itself (rotation is the target -- the rotation entity, not the users)
                        // While semantically correct, since the assessor isn't within the rotation, this functionality returns no results.
                        $this->createAssessmentsForRotationLearnersByAssessor($distribution, $distribution_schedule, $schedule, $release_date, $assessors, $distribution_target);

                    } else if ($distribution_target->getTargetType() == "self" && $distribution_target->getTargetScope() == "self") {

                        /** INVALID USE CASE **/
                        // Self assessment, the assessors are also the targets (e.g. faculty or individual users) -- this is an invalid state. A self assessment isn't possible from this context.

                    } else if ($distribution_target->getTargetType() == "proxy_id" && $distribution_target->getTargetScope() == "self") {

                        // Target is single proxy_id. A single proxy ID from this context can be added as an "additional learner", or a single specified learner.
                        // Check schedule for the target, and then fetch rotations and add assessment if he is within the rotation.
                        // If the specified target is not in the rotiation, this function correctly produces no assessment tasks.
                        //echo "Create assessments for specific proxy ID / type: {$distribution_target->getTargetType()} / scope: {$distribution_target->getTargetScope()} / role: {$distribution_target->getTargetRole()} \n";

                        if ($distribution_target->getTargetRole() == "faculty") {
                            //echo "Role is faculty. Use the assessor's proxy ID to find rotations\n";
                            $this->createAssessmentsForRotationLearnersByAssessor($distribution, $distribution_schedule, $schedule, $release_date, $assessors, $distribution_target, true);
                        } else {
                            // Role is "learner" OR "any", but "any" isn't possible through the editor.
                            //echo "Role is {$distribution_target->getTargetRole()}, use the target's proxy\n";
                            $this->createAssessmentsForRotationLearnersByTarget($distribution, $distribution_schedule, $schedule, $release_date, $assessors, $distribution_target, true); // use the target's proxy ID
                        }

                    } else {

                        /** INVALID USE CASE **/
                        // Anything else is invalid, and should not even be possible from within the editor.
                    }
                }
            }
        }
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

                            $existing_found = Models_Assessments_Assessor::fetchRowByADistributionIDAssessorTypeAssessorValueDeliveryDateAssociatedRecordIDAssociatedRecordType($distribution->getID(), $assessor_type, $assessor_value, $event_data["meta"]["delivery_date"], $event_id, "event_id");
                            if ($existing_found) {
                                continue; // already exists.
                            } else {
                                $saved_assessment = $this->saveAssessment(
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
                                    "event_id"
                                );
                                if ($saved_assessment) {
                                    $assessments_created_count++;
                                    $target_errors = 0;
                                    // Assessment was saved successfully, so create the targets for it.
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
                                        if (!$this->saveAssessmentTarget($distribution->getID(), $saved_assessment->getID(), $target_value, $target_type)) {
                                            $db_errors++;
                                            $target_errors++;
                                            application_log("error", "Error adding to cbl_distribution_assessment_targets for distribution ID: '{$distribution->getID()}' / assessment id: {$saved_assessment->getID()}");
                                        } else {
                                            $assessment_targets_created_count++;
                                        }
                                    }

                                    // Send notification for this newly created assessment
                                    if (!$target_errors) {
                                        $this->queueAssessorNotifications($saved_assessment, $saved_assessment->getAssessorValue(), null, $distribution->getNotifications(), false, true, $create_notification_as_sent);
                                    }
                                } else {
                                    $db_errors++;
                                    application_log("error", "Error adding to cbl_distribution_assessments for distribution ID: '{$distribution->getID()}' (for eventtype based distribution)");
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

    //-- Rotation Schedule Assessment Creation Logic --//

    /**
     * Assign assessment tasks based on the assessors' schedules (assessors are the learners). This would typically be done for faculty being assessed by their respective block-learners.
     *
     * @param $distribution
     * @param $distribution_schedule
     * @param $schedule
     * @param $release_date
     * @param $assessors
     * @param $distribution_target
     * @param $limit_to_assessor
     */
    private function createAssessmentsForRotationLearnersByAssessor($distribution, $distribution_schedule, $schedule, $release_date, $assessors, $distribution_target, $limit_to_assessor = false) {
        foreach ($assessors as $assessor) {
            if ($distribution_schedule->getScheduleType() == "rotation") {

                // Get the rotations of the assessor and create assessments for those dates.
                $rotations = $this->fetchRotations($schedule->getID(), null, $assessor["assessor_value"]);
                if ($rotations) {
                    $this->createAssessmentsByRotationDates($distribution, $distribution_schedule, $schedule, $rotations, $release_date, $assessor, $distribution_target);
                }

            } else if ($distribution_schedule->getScheduleType() == "block") {

                // Fetch blocks and create assessments for the relevant dates.
                if ($schedule->getScheduleType() == "rotation_stream") {
                    $blocks = Models_Schedule::fetchAllByParentID($schedule->getID());
                } else if ($schedule->getScheduleType() == "rotation_block") {
                    $blocks[] = $schedule;
                }
                if ($blocks) {
                    $this->createAssessmentsByLearnerBlocks($distribution, $distribution_schedule, $schedule, $blocks, $release_date, $assessor, $limit_to_assessor);
                }

            } else if ($distribution_schedule->getScheduleType() == "repeat") {

                // For each relevant rotation, create assessment tasks for the specified frequency.
                $rotations = $this->fetchRotations($schedule->getID(), null, $assessor["assessor_value"]);
                if ($rotations) {
                    $this->createAssessmentsByRepeatDates($distribution, $distribution_schedule, $schedule, $rotations, $release_date, $assessor, $distribution_target);
                }
            }
        }
    }

    /**
     * Assign assessment tasks based on the targets' rotation schedules. This would typically be faculty assessing the learners in the rotation.
     * This function can only be executed on assessors that have proxy_ids (not externals or non-person entities).
     *
     * @param $distribution
     * @param $distribution_schedule
     * @param $schedule
     * @param $release_date
     * @param $assessors
     * @param $distribution_target
     * @param bool $use_single_proxy_id
     */
    private function createAssessmentsForRotationLearnersByTarget($distribution, $distribution_schedule, $schedule, $release_date, $assessors, $distribution_target, $use_single_proxy_id = false) {
        foreach ($assessors as $assessor) {

            $proxy_id = null;
            if ($use_single_proxy_id && $distribution_target->getTargetID()) {
                $proxy_id = $distribution_target->getTargetID();
            }

            if ($distribution_schedule->getScheduleType() == "rotation") {
                $rotations = $this->fetchRotations($schedule->getID(), $distribution_target->getTargetScope(), $proxy_id);
                if ($rotations) {
                    $this->createAssessmentsByRotationDates($distribution, $distribution_schedule, $schedule, $rotations, $release_date, $assessor, $distribution_target);
                }

            } else if ($distribution_schedule->getScheduleType() == "block") {

                if ($schedule->getScheduleType() == "rotation_stream") {
                    $blocks = Models_Schedule::fetchAllByParentID($schedule->getID());
                } else if ($schedule->getScheduleType() == "rotation_block") {
                    $blocks[] = $schedule;
                }
                if ($blocks) {
                    $this->createAssessmentsByLearnerBlocks($distribution, $distribution_schedule, $schedule, $blocks, $release_date, $assessor);
                }

            } else if ($distribution_schedule->getScheduleType() == "repeat") {

                $rotations = $this->fetchRotations($schedule->getID(), $distribution_target->getTargetScope(), $proxy_id);
                if ($rotations) {
                    $this->createAssessmentsByRepeatDates($distribution, $distribution_schedule, $schedule, $rotations, $release_date, $assessor, $distribution_target);
                }
            }
        }
    }

    /**
     * Assign assessment tasks based on the targets' rotation schedules, for self assessments.
     *
     * @param $distribution
     * @param $distribution_schedule
     * @param $schedule
     * @param $release_date
     * @param $assessors
     * @param $distribution_target
     * @param bool $use_single_proxy_id
     */
    private function createAssessmentsForRotationLearnersBySelf($distribution, $distribution_schedule, $schedule, $release_date, $assessors, $distribution_target, $use_single_proxy_id = false) {
        foreach ($assessors as $assessor) {

            $proxy_id = null;
            if ($use_single_proxy_id && $distribution_target->getTargetID()) {
                $proxy_id = $distribution_target->getTargetID();
            }

            if ($distribution_schedule->getScheduleType() == "rotation") {
                $rotations = $this->fetchRotations($schedule->getID(), $distribution_target->getTargetScope(), $proxy_id);
                if ($rotations) {
                    $this->createAssessmentsByRotationDatesSelf($distribution, $distribution_schedule, $schedule, $rotations, $release_date, $assessor, $distribution_target);
                }

            } else if ($distribution_schedule->getScheduleType() == "block") {

                if ($schedule->getScheduleType() == "rotation_stream") {
                    $blocks = Models_Schedule::fetchAllByParentID($schedule->getID());
                } else if ($schedule->getScheduleType() == "rotation_block") {
                    $blocks[] = $schedule;
                }
                if ($blocks) {
                    $this->createAssessmentsByLearnerBlocks($distribution, $distribution_schedule, $schedule, $blocks, $release_date, $assessor, true);
                }

            } else if ($distribution_schedule->getScheduleType() == "repeat") {

                $rotations = $this->fetchRotations($schedule->getID(), $distribution_target->getTargetScope(), $proxy_id);
                if ($rotations) {
                    $this->createAssessmentsByRepeatDatesSelf($distribution, $distribution_schedule, $schedule, $rotations, $release_date, $assessor, $distribution_target);
                }
            }
        }
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

    /**
     * Create assessment target records for a corresponding distribution assessment task. This is required when an assessment is created for a rotation schedule.
     *
     * @param $distribution_id
     * @param $distribution_assessment_id
     * @param $target_type
     * @param $target_scope
     * @param $rotation_dates
     * @param $unique_start_date
     * @param $unqiue_end_date
     */
    private function createAssessmentTargetsByRotationDates($distribution_id, $distribution_assessment_id, $target_type, $target_scope, $rotation_dates, $unique_start_date, $unqiue_end_date) {
        // if the dassessment is for a schedule_id target, check the scope and create target records
        if ($target_type == "schedule_id" && ($target_scope == "internal_learners" || $target_scope == "external_learners" || $target_scope == "all_learners")) {
            foreach ($rotation_dates["all_rotation_dates"] as $proxy_id => $user_rotation_dates) {
                foreach ($user_rotation_dates as $user_end_date => $user_rotation_date) {
                    if ($unique_start_date == $user_rotation_date[0] && $unqiue_end_date == $user_rotation_date[1]) {
                        $this->saveAssessmentTarget($distribution_id, $distribution_assessment_id, $proxy_id);
                    }
                }
            }
        }
    }

    /**
     * Create assessment target records for an assessment that is block based.
     *
     * @param $distribution_id
     * @param $dassessment_id
     * @param $block_id
     */
    private function createAssessmentTargetsByBlock($distribution_id, $dassessment_id, $block_id) {
        $distribution_target_records = Models_Assessments_Distribution_Target::fetchAllByDistributionID($distribution_id);
        if ($distribution_target_records) {
            foreach ($distribution_target_records as $distribution_target_record) {
                if ($distribution_target_record->getTargetType() == "schedule_id") {
                    $block_rotations = $this->fetchBlockRotations($block_id, $distribution_target_record->getTargetScope());
                    if ($block_rotations) {
                        foreach ($block_rotations as $block_rotation) {
                            $this->saveAssessmentTarget($distribution_id, $dassessment_id, $block_rotation["audience_value"]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Creates distribution assessment records based on entire rotations (e.g. if someone is scheduled in Block 1 and Block 2, the assessment will encompass and apply to both blocks).
     *
     * @param $distribution
     * @param $distribution_schedule
     * @param $schedule
     * @param $rotations
     * @param $release_date
     * @param $assessor
     * @param $distribution_target
     */
    private function createAssessmentsByRotationDates($distribution, $distribution_schedule, $schedule, $rotations, $release_date, $assessor, $distribution_target) {
        $rotation_dates = $this->getRotationDates($rotations, $distribution->getOrganisationID());
        if ($rotation_dates) {
            foreach ($rotation_dates["unique_rotation_dates"] as $unique_rotation_date) {
                $delivery_date = $this->calculateDateByOffset($distribution_schedule->getDeliveryPeriod(), $distribution_schedule->getPeriodOffset(), $unique_rotation_date[0], $unique_rotation_date[1]);
                if (($release_date <= $delivery_date) && ($delivery_date <= time())) {
                    // check if dassessment records already exist
                    $distribution_assessment_records = Models_Assessments_Assessor::fetchRowByAssessorTypeAssessorValueStartDateEndDate($assessor["assessor_type"], $assessor["assessor_value"], $distribution->getID(), $unique_rotation_date[0], $unique_rotation_date[1]);
                    if (!$distribution_assessment_records) {
                        $distribution_assessment = $this->saveAssessment($assessor, $distribution->getID(), $distribution->getMinSubmittable(), $distribution->getMaxSubmittable(), $unique_rotation_date[0], $unique_rotation_date[1], $unique_rotation_date[0], $unique_rotation_date[1], $delivery_date);
                        if ($distribution_assessment) {
                            $this->createAssessmentTargetsByRotationDates($distribution->getID(), $distribution_assessment->getID(), $distribution_target->getTargetType(), $distribution_target->getTargetScope(), $rotation_dates, $unique_rotation_date[0], $unique_rotation_date[1]);
                            $this->queueAssessorNotifications($distribution_assessment, $distribution_assessment->getAssessorValue(), $schedule->getID(), $distribution->getNotifications());
                        }
                    }
                }
            }
        }
    }

    /**
     * Create assessments for self assessment rotation dates.
     *
     * @param $distribution
     * @param $distribution_schedule
     * @param $schedule
     * @param $rotations
     * @param $release_date
     * @param $assessor
     * @param $distribution_target
     */
    private function createAssessmentsByRotationDatesSelf($distribution, $distribution_schedule, $schedule, $rotations, $release_date, $assessor, $distribution_target) {
        $rotation_dates = $this->getRotationDates($rotations, $distribution->getOrganisationID());
        if ($rotation_dates) {
            // The rotation is assessing itself (the learners are assessing the rotation entity)
            foreach ($rotation_dates["unique_rotation_dates"] as $unique_rotation_date) {
                foreach ($rotation_dates["all_rotation_dates"] as $proxy_id => $block_dates) {
                    foreach ($block_dates as $block_date) {
                        if ($block_date[0] == $unique_rotation_date[0] && $block_date[1] == $unique_rotation_date[1] && $proxy_id == $assessor["assessor_value"]) {
                            $delivery_date = $this->calculateDateByOffset($distribution_schedule->getDeliveryPeriod(), $distribution_schedule->getPeriodOffset(), $unique_rotation_date[0], $unique_rotation_date[1]);
                            if (($release_date <= $delivery_date) && ($delivery_date <= time())) {
                                // check if dassessment records already exist
                                $distribution_assessment_records = Models_Assessments_Assessor::fetchRowByAssessorTypeAssessorValueStartDateEndDate($assessor["assessor_type"], $assessor["assessor_value"], $distribution->getID(), $unique_rotation_date[0], $unique_rotation_date[1]);
                                if (!$distribution_assessment_records) {
                                    $distribution_assessment = $this->saveAssessment($assessor, $distribution->getID(), $distribution->getMinSubmittable(), $distribution->getMaxSubmittable(), $unique_rotation_date[0], $unique_rotation_date[1], $unique_rotation_date[0], $unique_rotation_date[1], $delivery_date);
                                    if ($distribution_assessment) {
                                        $this->createAssessmentTargetsByRotationDates($distribution->getID(), $distribution_assessment->getID(), $distribution_target->getTargetType(), $distribution_target->getTargetScope(), $rotation_dates, $unique_rotation_date[0], $unique_rotation_date[1]);
                                        $this->queueAssessorNotifications($distribution_assessment, $distribution_assessment->getAssessorValue(), $schedule->getID(), $distribution->getNotifications());
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Create assessments for self assessment repeat dates.
     *
     * @param $distribution
     * @param $distribution_schedule
     * @param $schedule
     * @param $rotations
     * @param $release_date
     * @param $assessor
     * @param $distribution_target
     */
    private function createAssessmentsByRepeatDatesSelf($distribution, $distribution_schedule, $schedule, $rotations, $release_date, $assessor, $distribution_target) {
        global $db;
        $rotation_dates = $this->getRotationDates($rotations, $distribution->getOrganisationID());
        if ($rotation_dates) {
            // The rotation is assessing itself (the learners are assessing the rotation entity)
            foreach ($rotation_dates["unique_rotation_dates"] as $unique_rotation_date) {
                foreach ($rotation_dates["all_rotation_dates"] as $proxy_id => $block_dates) {
                    foreach ($block_dates as $block_date) {
                        if ($block_date[0] == $unique_rotation_date[0] && $block_date[1] == $unique_rotation_date[1] && $proxy_id == $assessor["assessor_value"]) {
                            $delivery_date = $this->calculateDateByOffset($distribution_schedule->getDeliveryPeriod(), $distribution_schedule->getPeriodOffset(), $unique_rotation_date[0], $unique_rotation_date[1]);
                            if ($release_date <= $delivery_date) {
                                while ($delivery_date <= time() && $delivery_date <= $unique_rotation_date[1]) {
                                    $distribution_assessment_records = Models_Assessments_Assessor::fetchRowByAssessorTypeAssessorValueStartDateEndDate($assessor["assessor_type"], $assessor["assessor_value"], $distribution->getID(), $delivery_date, $unique_rotation_date[1]);
                                    if (!$distribution_assessment_records) {
                                        $distribution_assessment = $this->saveAssessment($assessor, $distribution->getID(), $distribution->getMinSubmittable(), $distribution->getMaxSubmittable(), $delivery_date, $unique_rotation_date[1], $unique_rotation_date[0], $unique_rotation_date[1], $delivery_date);
                                        if ($distribution_assessment) {
                                            $this->createAssessmentTargetsByRotationDates($distribution->getID(), $distribution_assessment->getID(), $distribution_target->getTargetType(), $distribution_target->getTargetScope(), $rotation_dates, $unique_rotation_date[0], $unique_rotation_date[1]);
                                            $this->queueAssessorNotifications($distribution_assessment, $distribution_assessment->getAssessorValue(), $schedule->getID(), $distribution->getNotifications());
                                        } else {
                                            application_log("error", "An error occurred while attempting to save a cbl_distribution_assessments record DB said: " . $db->ErrorMsg());
                                        }
                                    }
                                    $delivery_date += ($distribution_schedule->getFrequency() * 86400);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Create distribution assessment records based on single rotation blocks.
     *
     * @param $distribution
     * @param $distribution_schedule
     * @param $schedule
     * @param $blocks
     * @param $release_date
     * @param $assessor
     * @param $limit_to_assessor
     */
    private function createAssessmentsByLearnerBlocks($distribution, $distribution_schedule, $schedule, $blocks, $release_date, $assessor, $limit_to_assessor = false) {

        $proxy_id = null;
        if ($limit_to_assessor) {
            $proxy_id = $assessor["assessor_value"];
        }
        foreach ($blocks as $block) {
            $delivery_date = $this->calculateDateByOffset($distribution_schedule->getDeliveryPeriod(), $distribution_schedule->getPeriodOffset(), $block->getStartDate(), $block->getEndDate());
            if (($release_date <= $delivery_date) && ($delivery_date <= time())) {
                $learner_blocks = $this->fetchLearnerBlocks($block->getID(), $proxy_id);
                if ($learner_blocks) {
                    $distribution_assessment_records = Models_Assessments_Assessor::fetchRowByAssessorTypeAssessorValueEndDate($assessor["assessor_type"], $assessor["assessor_value"], $distribution->getID(), $delivery_date);
                    if (!$distribution_assessment_records) {
                        $distribution_assessment = $this->saveAssessment($assessor, $distribution->getID(), $distribution->getMinSubmittable(), $distribution->getMaxSubmittable(), $delivery_date, $delivery_date, $block->getStartDate(), $block->getEndDate(), $delivery_date);
                        if ($distribution_assessment) {
                            $this->createAssessmentTargetsByBlock($distribution->getID(), $distribution_assessment->getID(), $block->getID());
                            $this->queueAssessorNotifications($distribution_assessment, $assessor["assessor_value"], $schedule->getID(), $distribution->getNotifications());
                        }
                    }
                }
            }
        }
    }

    /**
     * Create distribution assessment records based on repeat frequency within a rotation.
     *
     * @param $distribution
     * @param $distribution_schedule
     * @param $schedule
     * @param $rotations
     * @param $release_date
     * @param $assessor
     * @param $distribution_target
     */
    private function createAssessmentsByRepeatDates($distribution, $distribution_schedule, $schedule, $rotations, $release_date, $assessor, $distribution_target) {
        global $db;
        $rotation_dates = $this->getRotationDates($rotations, $distribution->getOrganisationID());
        if ($rotation_dates["unique_rotation_dates"]) {
            foreach ($rotation_dates["unique_rotation_dates"] as $unique_rotation_date) {
                $delivery_date = $this->calculateDateByFrequency($distribution_schedule->getFrequency(), $unique_rotation_date[0]);
                if ($release_date <= $delivery_date) {
                    while ($delivery_date <= time() && $delivery_date <= $unique_rotation_date[1]) {
                        $distribution_assessment_records = Models_Assessments_Assessor::fetchRowByAssessorTypeAssessorValueStartDateEndDate($assessor["assessor_type"], $assessor["assessor_value"], $distribution->getID(), $delivery_date, $unique_rotation_date[1]);
                        if (!$distribution_assessment_records) {
                            $distribution_assessment = $this->saveAssessment($assessor, $distribution->getID(), $distribution->getMinSubmittable(), $distribution->getMaxSubmittable(), $delivery_date, $unique_rotation_date[1], $unique_rotation_date[0], $unique_rotation_date[1], $delivery_date);
                            if ($distribution_assessment) {
                                $this->createAssessmentTargetsByRotationDates($distribution->getID(), $distribution_assessment->getID(), $distribution_target->getTargetType(), $distribution_target->getTargetScope(), $rotation_dates, $unique_rotation_date[0], $unique_rotation_date[1]);
                                $this->queueAssessorNotifications($distribution_assessment, $distribution_assessment->getAssessorValue(), $schedule->getID(), $distribution->getNotifications());
                            } else {
                                application_log("error", "An error occurred while attempting to save a cbl_distribution_assessments record DB said: " . $db->ErrorMsg());
                            }
                        }
                        $delivery_date += ($distribution_schedule->getFrequency() * 86400);
                    }
                }
            }
        }
    }
}