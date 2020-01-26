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
 * A class to handle Distribution Assessment functionality. This utility object
 * provides an interface for accessing and manipulating assessments based
 * distribution data. This utility abstracts the creation and manipulation
 * of all assessment related tasks.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */
class Entrada_Utilities_Assessments_DistributionAssessment extends Entrada_Assessments_Base {

    protected $adistribution_id;
    protected $complete_target_list = array();
    protected $distribution, $form;

    public function __construct($arr = null) {
        parent::__construct($arr);
    }

    //--- Getters/Setters ---//

    public function getDistributionID() {
        return $this->adistribution_id;
    }

    public function getAdistributionID() {
        return $this->adistribution_id;
    }

    public function getDistribution() {
        return $this->distribution;
    }

    public function getForm() {
        return $this->form;
    }

    public function setAdistributionID($id) {
        $this->adistribution_id = $id;
    }

    public function setDistributionID($id) {
        $this->adistribution_id = $id;
    }

    public function setDistribution($data) {
        $this->distribution = $data;
    }

    public function setForm($data) {
        $this->form = $data;
    }

    //-- Private --//

    /**
     * Add a set of target to a complete list of unique targets (by date).
     *
     * @param array $target_list
     * @param array $assessor_list
     * @param int $delivery_date
     * @param int $release_date
     * @param int $start_date
     * @param int $end_date
     */
    private function addToTargetList($target_list, $assessor_list, $delivery_date, $release_date, $start_date, $end_date) {
        $hash = md5(serialize($assessor_list));
        $storage_key = "$delivery_date-$release_date-$start_date-$end_date-$hash";
        foreach ($target_list as $target) {
            $target_index = "{$target["target_type"]}-{$target["target_value"]}";
            $this->complete_target_list[$storage_key][$target_index] = $target;
        }
    }

    //--- Public functions ---//

    /**
     * Determine all of the assessment tasks for a distribution, optionally filtering by delivery date.
     * This function clears the existing list before creating a new one.
     *
     * @param int $delivery_start Start of delivery dates to include
     * @param int $delivery_end End of delivery dates to include
     * @param bool $ignore_distribution_deletion
     *
     * @return array|bool
     */
    public function buildAssessmentTaskList($delivery_start = null, $delivery_end = null, $ignore_distribution_deletion = false) {

        if ($ignore_distribution_deletion) {
            $distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($this->getDistributionID());
        } else {
            $distribution = Models_Assessments_Distribution::fetchRowByID($this->getDistributionID());
        }
        if (!$distribution) {
            // Invalid distribution ID.
            return false;
        }
        $this->setDistribution($distribution->toArray());
        $form = Models_Assessments_Form::fetchRowByIDIncludeDeleted($this->distribution["form_id"]);
        $this->setForm($form->toArray());

        $delegator = Models_Assessments_Distribution_Delegator::fetchRowByDistributionID($this->getDistributionID());
        if ($delegator) {
            // Delegation based distribution, not supported here (see: Entrada_Utilities_Assessments_DistributionDelegation).
            return false;
        }

        $distribution_eventtype = Models_Assessments_Distribution_Eventtype::fetchAllByAdistributionID($distribution->getID());
        if (is_array($distribution_eventtype) && !empty($distribution_eventtype)) {
            return false; // Event Type-based distributions are not supported from here (see: Entrada_Utilities_Assessments_DistributionLearningEvent).
        }

        // Clear task list so we can build a new one.
        $this->clearTaskList();

        // Set the default array for this distribution
        $this->task_list[$this->getDistributionID()] = array();

        $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($distribution->getID());
        if ($distribution_schedule) {
            $schedule = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID());
            if ($schedule) {
                $this->buildTaskListByRotationSchedule($distribution, $distribution_schedule, $schedule, $delivery_start, $delivery_end);
            } else {
                application_log("error", "Distribution has distribution_schedule record, but the schedule was not found. Unable to create assessments. distribution_id = {$distribution->getID()}, distribution_schedule_id = {$distribution_schedule->getID()}");
            }
        } else {
            $this->addToTaskListByDateRange($distribution, $delivery_start, $delivery_end);
        }
        $this->addToTaskListAdditionalAssessments($distribution, $delivery_start, $delivery_end);

        // Add the complete list of targets to each unique assessment. 
        if (!empty($this->task_list)) {
            foreach ($this->complete_target_list as $index => $target_list) {
                foreach ($target_list as $target) {
                    $this->task_list[$this->getDistributionID()][$index]["targets"][] = $target;
                }
            }
        }
        return $this->getTaskList();
    }

    /**
     * From the list of all assessment tasks (potential or actual) for this distribution build a list of the ones that have targets that are pending (that are incomplete or ones that haven't been created but should exist).
     *
     * If specified, this function will recreate the assessment task list, otherwise, it assumes it already exists in a populated state.
     *
     * @param $recreate_task_list
     * @return array
     */
    public function determinePendingTasks($recreate_task_list = false) {
        $pending = array();

        if ($recreate_task_list) {
            $full_task_list = $this->buildAssessmentTaskList();
        } else {
            $full_task_list = $this->getTaskList();
        }
        $distribution = Models_Assessments_Distribution::fetchRowByID($this->getDistributionID());
        $release_date = (is_null($distribution->getReleaseDate()) ? 0 : (int)$distribution->getReleaseDate());
        if (!isset($full_task_list[$this->getDistributionID()])) {
            return $pending;
        }
        foreach ($full_task_list[$this->getDistributionID()] as $task) {
            if ($task["meta"]["deleted_date"]) {
                continue;
            }
            if ($release_date > $task["meta"]["delivery_date"]) {
                continue;
            }
            if (empty($task["targets"])) {
                continue;
            }
            $targets = array();
            foreach ($task["targets"] as $target) {
                if ($target["progress"]) {
                    if (empty($target["progress"]["inprogress"]) && empty($target["progress"]["complete"])) {
                        $targets[] = $target;
                    }
                } else {
                    $targets[] = $target;
                }
            }
            if (!empty($targets)) {
                $pending_task = $task;
                $pending_task["targets"] = $targets;
                $pending[] = $pending_task;
            }
        }
        return $pending;
    }

    /**
     * From the list of all assessment tasks for this distribution build a list of the ones that have targets that are in progress.
     *
     * If specified, this function will recreate the assessment task list, otherwise, it assumes it already exists in a populated state.
     *
     * @param $recreate_task_list
     * @return array
     */
    public function determineInprogressTasks($recreate_task_list = false) {
        $in_progress = array();

        if ($recreate_task_list) {
            $full_task_list = $this->buildAssessmentTaskList();
        } else {
            $full_task_list = $this->getTaskList();
        }
        $distribution = Models_Assessments_Distribution::fetchRowByID($this->getDistributionID());
        $release_date = (is_null($distribution->getReleaseDate()) ? 0 : (int)$distribution->getReleaseDate());
        if (!isset($full_task_list[$this->getDistributionID()])) {
            return $in_progress;
        }
        foreach ($full_task_list[$this->getDistributionID()] as $task) {
            if ($task["meta"]["deleted_date"]) {
                continue;
            }
            if ($release_date > $task["meta"]["delivery_date"]) {
                continue;
            }
            if (empty($task["targets"])) {
                continue;
            }
            $targets = array();
            foreach ($task["targets"] as $target) {
                if (!empty($target["progress"]["inprogress"])) {
                    $targets[] = $target;
                }
            }
            if (!empty($targets)) {
                $in_progress_task = $task;
                $in_progress_task["targets"] = $targets;
                $in_progress[] = $in_progress_task;
            }
        }
        return $in_progress;
    }

    /**
     * From the list of all assessment tasks for this distribution build a list of the ones with targets that are complete.
     *
     * If specified, this function will recreate the assessment task list, otherwise, it assumes it already exists in a populated state.
     *
     * @param $recreate_task_list
     * @return array
     */
    public function determineCompleteTasks($recreate_task_list = false) {
        $complete = array();

        if ($recreate_task_list) {
            $full_task_list = $this->buildAssessmentTaskList();
        } else {
            $full_task_list = $this->getTaskList();
        }
        $distribution = Models_Assessments_Distribution::fetchRowByID($this->getDistributionID());
        $release_date = (is_null($distribution->getReleaseDate()) ? 0 : (int)$distribution->getReleaseDate());
        if (!isset($full_task_list[$this->getDistributionID()])) {
            return $complete;
        }
        foreach ($full_task_list[$this->getDistributionID()] as $task) {
            if ($task["meta"]["deleted_date"]) {
                continue;
            }
            if ($release_date > $task["meta"]["delivery_date"]) {
                continue;
            }
            if (empty($task["targets"])) {
                continue;
            }
            $targets = array();
            foreach ($task["targets"] as $target) {
                if (!empty($target["progress"]["complete"])) {
                    $targets[] = $target;
                }
            }
            if (!empty($targets)) {
                $complete_task = $task;
                $complete_task["targets"] = $targets;
                $complete[] = $complete_task;
            }
        }
        return $complete;
    }

    /**
     * From the list of all assessment tasks for this distribution build a list of tasks grouped by assessor.
     *
     * If specified, this function will recreate the assessment task list, otherwise, it assumes it already exists in a populated state.
     *
     * @param $recreate_task_list
     * @return array
     */
    public function getTargetListGroupedByProgressValueAssessor($recreate_task_list = false) {
        if ($recreate_task_list) {
            $this->buildAssessmentTaskList();
        } else {
            $this->getTaskList();
        }

        $progress_list = array("pending" => array(), "inprogress" => array(), "complete" => array());
        $progress_counts = array("pending" => 0, "inprogress" => 0, "complete" => 0);

        $assessor_list = $this->getUniqueAssessors();
        foreach ($assessor_list as $type) {
            foreach ($type as $assessor_value => $assessor_info) {
                $target_list = $this->getTargetsByAssessor($assessor_info);
                if (empty($target_list)) {
                    continue;
                }
                foreach ($target_list as $target) {
                    // Determine where to store the target within the progress array.
                    $progress_found = false;
                    foreach ($target["progress"] as $progress_value => $target_progress) {
                        foreach ($target_progress as $progress) {
                            if (!empty($progress)) {
                                // Store the assessor, it doesn't matter if we overwrite the previous assessor entry.
                                $progress_list[$progress_value][$assessor_info["assessor_type"]][$assessor_info["assessor_value"]]["assessor"] = $assessor_info;

                                // Initialize targets if they have not been set previously.
                                if (!array_key_exists("targets", $progress_list[$progress_value][$assessor_info["assessor_type"]][$assessor_info["assessor_value"]])) {
                                    $progress_list[$progress_value][$assessor_info["assessor_type"]][$assessor_info["assessor_value"]]["targets"] = array();
                                }

                                // Append new target.
                                $progress_list[$progress_value][$assessor_info["assessor_type"]][$assessor_info["assessor_value"]]["targets"][] = $target;
                                $progress_found = true;
                                if ($target["should_exist"] || $progress_value == "complete") {
                                    $progress_counts[$progress_value]++;
                                }
                            }
                        }
                    }
                    // If we did not find any progress, we can safely assume the task is pending.
                    if (!$progress_found) {
                        // Store the assessor, it doesn't matter if we overwrite the previous assessor entry.
                        $progress_list["pending"][$assessor_info["assessor_type"]][$assessor_info["assessor_value"]]["assessor"] = $assessor_info;

                        // Initialize targets if they have not been set previously.
                        if (!array_key_exists("targets", $progress_list["pending"][$assessor_info["assessor_type"]][$assessor_info["assessor_value"]])) {
                            $progress_list["pending"][$assessor_info["assessor_type"]][$assessor_info["assessor_value"]]["targets"] = array();
                        }

                        // Append new target.
                        $progress_list["pending"][$assessor_info["assessor_type"]][$assessor_info["assessor_value"]]["targets"][] = $target;
                        if ($target["should_exist"]) {
                            $progress_counts["pending"]++;
                        }
                    }
                }
            }
        }
        $list = array("progress" => $progress_list, "counts" => $progress_counts);
        return $list;
    }

    /**
     * Parse out a list of unique assessors grouped by internal and external, indexed by their ID.
     *
     * The array that will be returned will look like this:
     * array(
     *      ["internal"] => array(
     *          [73301] => array(
     *              $assessor
     *          )
     *      )
     *  )
     *
     * @return array $assessors
     */
    public function getUniqueAssessors() {
        $task_list = $this->getTaskList();
        $assessors = array("internal" => array(), "external" => array());
        foreach ($task_list as $tasks) {
            foreach ($tasks as $task) {
                foreach ($task["assessors"] as $assessor) {
                    if (!array_key_exists($assessor["assessor_value"], $assessors[$assessor["assessor_type"]])) {
                        $assessors[$assessor["assessor_type"]][$assessor["assessor_value"]] = $assessor;
                    }
                }
            }
        }
        // Sort assessors by name.
        foreach ($assessors as $assessor_type => $assessor_set) {
            uasort($assessors[$assessor_type], function ($a, $b) {
                return $a["name"] > $b["name"];
            });
        }
        return $assessors;
    }

    /**
     * Parse out a list of targets for the provided assessor.
     *
     * @param $assessor
     *
     * @return array $targets
     */
    public function getTargetsByAssessor($assessor) {
        $task_list = $this->getTaskList();
        $targets = array();

        foreach ($task_list as $tasks) {
            foreach ($tasks as $task) {
                foreach ($task["assessors"] as $task_assessor) {
                    // Check to see if the assessor matches the one we are searching for.
                    if ($task_assessor["assessor_type"] == $assessor["assessor_type"] && $task_assessor["assessor_value"] == $assessor["assessor_value"]) {
                        foreach ($task["targets"] as $task_target) {
                            // We also want to return the task's meta data with the target so we have a complete picture of the assessment.
                            $task_target["meta"] = $task["meta"];
                            $targets[] = $task_target;
                        }
                    }
                }
            }
        }
        $delivery_dates = $target_names = array();
        foreach ($targets as $target) {
            $delivery_dates[] = $target["meta"]["delivery_date"];
            $target_names[] = $target["target_name"];
        }
        array_multisort($delivery_dates, SORT_ASC, $target_names, SORT_ASC, $targets);
        return $targets;
    }

    /**
     * From the list of all assessment tasks for this distribution build a list of tasks grouped by delivery date.
     *
     * If specified, this function will recreate the assessment task list, otherwise, it assumes it already exists in a populated state.
     *
     * @param $recreate_task_list
     * @return array
     */
    public function getTaskListGroupedByDeliveryDate($recreate_task_list = false) {
        if ($recreate_task_list) {
            $full_task_list = $this->buildAssessmentTaskList();
        } else {
            $full_task_list = $this->getTaskList();
        }
        $group_task_list = array();
        foreach ($full_task_list[$this->getDistributionID()] as $task) {
            $group_task_list[$task["meta"]["delivery_date"]][] = $task;
        }
        return $group_task_list;
    }

    /**
     * From the list of all assessment tasks for this distribution, build a list of flat arrays of assessor->target->delivery_date tasks.
     *
     * If specified, this function will recreate the assessment task list, otherwise, it assumes it already exists in a populated state.
     *
     * @param $format_timestamps
     * @param $recreate_task_list
     * @param $include_schedule_info
     * @return array
     */
    public function getFlatTaskList($format_timestamps = true, $recreate_task_list = false, $include_schedule_info = false) {
        if ($recreate_task_list) {
            $full_task_list = $this->buildAssessmentTaskList();
        } else {
            $full_task_list = $this->getTaskList();
        }

        $list = array();

        if (!array_key_exists($this->getDistributionID(), $full_task_list)) {
            return $list;
        }

        global $translate;
        $form_data = $this->getForm();

        foreach ($full_task_list[$this->getDistributionID()] as $task) {

            $timestamp = ($format_timestamps ? date("Y-m-d", $task["meta"]["delivery_date"]) : $task["meta"]["delivery_date"]);

            // Pull in schedule information if requested.
            if ($include_schedule_info) {
                $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($this->getAdistributionID());
                if ($distribution_schedule) {
                    $schedule = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID());
                    if ($schedule) {
                        $schedule_badge_text = $this->getConcatenatedBlockString(
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
                            $timestamp .= " ({$schedule_badge_text})";
                        }
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

                    $flattened_task = array(
                        $this->distribution["adistribution_id"],
                        $this->distribution["deleted_date"],
                        $this->distribution["title"],
                        $assessor["name"],
                        $target["target_name"],
                        ($form_data ? $form_data["title"] : ""),
                        $timestamp,
                        $progress_string
                    );
                    $list[] = $flattened_task;
                }
            }
        }

        return $list;
    }

    //--- Private functions ---//

    /**
     * Determine date range based assessments.
     *
     * @param $distribution
     * @param $delivery_start
     * @param $delivery_end
     */
    private function addToTaskListByDateRange($distribution, $delivery_start = null, $delivery_end = null) {
        $assessors = $distribution->getAssessors(null);
        if (!$assessors || empty($assessors)) {
            return;
        }
        if ((!isset($delivery_start) || $distribution->getDeliveryDate() >= $delivery_start) &&
            (!isset($delivery_end) || $distribution->getDeliveryDate() <= $delivery_end)
        ) {
            foreach ($assessors as $assessor) {
                // Check if dassessment record already exists.
                $distribution_assessment_records = Models_Assessments_Assessor::fetchRowByADistributionIDAssessorTypeAssessorValueDeliveryDate(
                    $distribution->getID(),
                    $assessor["assessor_type"],
                    $assessor["assessor_value"],
                    $distribution->getDeliveryDate()
                );
                $distribution_targets = Models_Assessments_Distribution_Target::fetchAllByDistributionID($distribution->getID());
                $associated_record_id = null;
                $associated_record_type = null;
                foreach ($distribution_targets as $distribution_target) {
                    switch ($distribution_target->getTargetType()) {
                        case "course_id":
                        case "schedule_id":
                        case "group_id":
                            $associated_record_type = $distribution_target->getTargetType();
                            $associated_record_id = $distribution_target->getTargetID();
                            break;
                        case "proxy_id":
                        case "self":
                            $associated_record_type = "proxy_id";
                            break;
                        case "external_hash":
                            $associated_record_type = "external_hash";
                            break;
                    }
                    if ($associated_record_id) {
                        // We found a specific associated record ID, so we will use it as the parent of the targets.
                        break;
                    }
                }
                $targets = array();
                // For every distribution target (e.g., a schedule_id, group_id, proxy_id etc), determine what the targets should be.
                foreach ($distribution_targets as $distribution_target) {
                    if ($distribution_target->getTargetType() == "self" && $distribution_target->getTargetScope() == "self") {
                        // Target is the assessor
                        if ($assessor["assessor_type"] == "internal") { // Sorry, no support for external self assessments!

                            $eligible = true;
                            if ($distribution_target->getTargetRole() == "learner") {
                                $eligible = $this->isEligibleTarget($distribution, "proxy_id", $assessor["assessor_value"], $distribution->getDeliveryDate());
                            }

                            if ($distribution->getExcludeSelfAssessments()) {
                                $eligible = false;
                            }

                            if ($eligible) {
                                $target_list = array($assessor);
                                $targets[] = $this->buildAssessmentTarget(
                                    $distribution,
                                    $distribution->getID(),
                                    $distribution_assessment_records,
                                    array(
                                        "target_value" => $assessor["assessor_value"],
                                        "target_type" => "proxy_id",
                                        "name" => $target_list[0]["name"]
                                    )
                                );
                            }
                        }
                    } else {
                        // Targets are whatever the distribution target record references, so find the specifics
                        $target_list = $this->getTargetList($distribution_target);
                        if (empty($target_list)) {
                            continue;
                        }
                        foreach ($target_list as $assessment_target) {

                            $eligible = true;
                            if ($distribution_target->getTargetRole() == "learner") {
                                $eligible = $this->isEligibleTarget($distribution, $assessment_target["target_type"], $assessment_target["target_value"], $distribution->getDeliveryDate());
                            }

                            if ($distribution->getExcludeSelfAssessments() && $assessment_target["target_type"] == "proxy_id") {
                                if ($assessment_target["target_value"] == $assessor["assessor_value"]) {
                                    $eligible = false;
                                }
                            }

                            if ($eligible) {
                                $targets[] = $this->buildAssessmentTarget(
                                    $distribution,
                                    $distribution->getID(),
                                    $distribution_assessment_records,
                                    array(
                                        "target_value" => $assessment_target["target_value"],
                                        "target_type" => $assessment_target["target_type"],
                                        "name" => $assessment_target["name"],
                                        "target_group" => $assessment_target["target_group"]
                                    )
                                );
                            }
                        }
                    }
                }
                if ($targets) {

                    $expiry_date = ($distribution->getExpiryOffset() ? ($distribution->getDeliveryDate() + $distribution->getExpiryOffset()) : null);
                    $expiry_notification_date = ($expiry_date && $distribution->getExpiryNotificationOffset() ? ($expiry_date - $distribution->getExpiryNotificationOffset()) : null);

                    $this->addToTaskList(
                        $this->getDistributionID(),
                        $distribution->getDeliveryDate(),
                        $distribution->getReleaseDate(),
                        $expiry_date,
                        $expiry_notification_date,
                        $distribution->getStartDate(),
                        $distribution->getEndDate(),
                        array(),
                        array($assessor),
                        "assessment",
                        "assessment",
                        null,
                        null,
                        false,
                        0,
                        0,
                        0,
                        $targets[0]["target_type"],
                        $associated_record_type,
                        $associated_record_id,
                        $distribution_assessment_records,
                        0,
                        0,
                        $distribution->getMinSubmittable(),
                        $distribution->getMaxSubmittable()
                    );
                    $this->addToTargetList($targets, array($assessor), $distribution->getDeliveryDate(), $distribution->getReleaseDate(), $distribution->getStartDate(), $distribution->getEndDate());
                }
            }
        }
    }

    /**
     * Determine any additional tasks added to a distribution.
     *
     * @param $distribution
     * @param $delivery_start
     * @param $delivery_end
     */
    private function addToTaskListAdditionalAssessments($distribution, $delivery_start = null, $delivery_end = null) {
        // Check for any additional tasks that need an assessment created.
        $additional_tasks = Models_Assessments_AdditionalTask::fetchAllByADistributionID($distribution->getID());
        if (empty($additional_tasks)) {
            return;
        }
        foreach ($additional_tasks as $additional_task) {
            if ((!isset($delivery_start) || $additional_task->getDeliveryDate() >= $delivery_start) &&
                (!isset($delivery_end) || $additional_task->getDeliveryDate() <= $delivery_end)
            ) {
                // Check if dassessment record already exists.
                $distribution_assessment_records = Models_Assessments_Assessor::fetchRowByADistributionIDAssessorTypeAssessorValueDeliveryDate(
                    $distribution->getID(),
                    $additional_task->getAssessorType(),
                    $additional_task->getAssessorValue(),
                    $additional_task->getDeliveryDate()
                );
                $associated_record_id = $additional_task->getTargetID();
                $associated_record_type = $additional_task->getTargetType();
                if ($associated_record_type == "proxy_id") {
                    $associated_record_id = null;
                }

                // For now, the only target type the interface allows is an individual proxy_id. If this changes, this target name code will need to be adjusted.
                $target_name = false;
                if ($additional_task->getTargetType() == "proxy_id") {
                    $target_user = Models_User::fetchRowByID($additional_task->getTargetID());
                    $target_name = "{$target_user->getFirstname()} {$target_user->getLastname()}";
                }

                $targets = array(
                    $this->buildAssessmentTarget(
                        $distribution,
                        $distribution->getID(),
                        $distribution_assessment_records,
                        array(
                            "target_value" => $additional_task->getTargetID(),
                            "target_type" => $additional_task->getTargetType(),
                            "name" => $target_name,
                        )
                    )
                );

                if ($targets) {

                    $assessor = $this->getUserByType($additional_task->getAssessorValue(), $additional_task->getAssessorType());
                    if (!$assessor) {
                        continue;
                    }
                    $assessor = $assessor->toArray();
                    $assessor = array(
                        "name" => "{$assessor["firstname"]} {$assessor["lastname"]}",
                        "proxy_id" => $additional_task->getAssessorType() == "external" ? $assessor["eassessor_id"] : $assessor["id"],
                        "number" => $additional_task->getAssessorType() == "external" ? 0 :$assessor["number"],
                        "email" => $assessor["email"],
                        "assessor_type" => $additional_task->getAssessorType(),
                        "assessor_value" => $additional_task->getAssessorType() == "external" ? $assessor["eassessor_id"] : $assessor["id"]
                    );

                    $expiry_date = ($distribution->getExpiryOffset() ? ($additional_task->getDeliveryDate() + $distribution->getExpiryOffset()) : null);
                    $expiry_notification_date = ($expiry_date && $distribution->getExpiryNotificationOffset() ? ($expiry_date - $distribution->getExpiryNotificationOffset()) : null);

                    $this->addToTaskList(
                        $this->getDistributionID(),
                        $additional_task->getDeliveryDate(),
                        $distribution->getReleaseDate(),
                        $expiry_date,
                        $expiry_notification_date,
                        $distribution->getStartDate(),
                        $additional_task->getDeliveryDate(),
                        array(),
                        array($assessor),
                        "assessment",
                        "assessment",
                        null,
                        null,
                        false,
                        0,
                        0,
                        0,
                        $targets[0]["target_type"],
                        $associated_record_type,
                        $associated_record_id,
                        $distribution_assessment_records,
                        0,
                        0,
                        $distribution->getMinSubmittable(),
                        $distribution->getMaxSubmittable(),
                        true
                    );
                    $this->addToTargetList($targets, array($assessor), $additional_task->getDeliveryDate(), $distribution->getReleaseDate(), $distribution->getStartDate(), $additional_task->getDeliveryDate());
                }
            }
        }
    }

    /**
     * Generate assessment task list based on a rotation schedule.
     *
     * @param $distribution
     * @param $distribution_schedule
     * @param $schedule
     * @param $delivery_start
     * @param $delivery_end
     */
    private function buildTaskListByRotationSchedule($distribution, $distribution_schedule, $schedule, $delivery_start = null, $delivery_end = null) {
        $schedule_class = new Entrada_CBME_RotationSchedule();
        $release_date = (is_null($distribution->getReleaseDate()) ? 0 : (int)$distribution->getReleaseDate());

        // This returns whatever was indicated in the distribution and must be expanded, e.g. if on-service learners were
        // specified, this returns the schedule_id, and must be turned into a list of proxy_ids.
        $distribution_targets = Models_Assessments_Distribution_Target::fetchAllByDistributionID($distribution->getID());

        // This always returns a flat, well-defined list of users.
        $assessors = $distribution->getAssessors(null);

        if ($distribution_targets && $assessors) {

            foreach ($distribution_targets as $distribution_target) {

                // Assessors are a rotation schedule's learners (on/off service)
                if ($distribution->getAssessorOption() == "learner") {

                    if ($distribution_target->getTargetType() == "proxy_id" && $distribution_target->getTargetScope() == "self") {

                        // Create assessment tasks for proxy ids. This is called when a specific or an additional user is added to the distribution.
                        // We check if the distribution target is part of the specified rotation. If so, use that user's schedule to create tasks, if not, try to use the assessor's schedule. If neither, then produce no list.
                        if ($distribution_target->getTargetRole() == "faculty") {

                            $this->buildTaskListForRotationLearnersByAssessor($distribution, $distribution_schedule, $schedule, $release_date, $assessors, $distribution_target, true, $delivery_start, $delivery_end);

                        } else if ($distribution_target->getTargetRole() == "learner") {
                            // Role is learner, so fetch by the target
                            $this->buildTaskListForRotationLearnersByTarget($distribution, $distribution_schedule, $schedule, $release_date, $assessors, $distribution_target, true, $delivery_start, $delivery_end);

                        } else { // target role is "any"
                            // Try and figure it out based on the user's rotations (if any)
                            $learner_blocks = $schedule_class->fetchRotations($distribution_schedule->getScheduleID(), $distribution_target->getTargetScope(), $distribution_target->getTargetID());
                            if (empty($learner_blocks)) {
                                // No rotations found, so build list by assessor
                                $this->buildTaskListForRotationLearnersByAssessor($distribution, $distribution_schedule, $schedule, $release_date, $assessors, $distribution_target, true, $delivery_start, $delivery_end);
                            } else {
                                // Rotations found, build list by target
                                $this->buildTaskListForRotationLearnersByTarget($distribution, $distribution_schedule, $schedule, $release_date, $assessors, $distribution_target, true, $delivery_start, $delivery_end);
                            }
                        }

                    } else if ($distribution_target->getTargetType() == "external_hash" && $distribution_target->getTargetScope() == "self") {

                        // Create assessment tasks for external faculty members. This is called when a specific or an additional external faculty is added to the distribution.
                        // Use the assessor's schedule.
                        if ($distribution_target->getTargetRole() == "faculty") {

                            $this->buildTaskListForRotationLearnersByAssessor($distribution, $distribution_schedule, $schedule, $release_date, $assessors, $distribution_target, true, $delivery_start, $delivery_end);

                        }
                    } else if ($distribution_target->getTargetType() == "schedule_id" && $distribution_target->getTargetScope() != "self") {

                        // Create assessment tasks for all the assessors, based on the learners in a rotation schedule. This method expands the rotation into the required list of learner proxy_ids.
                        $this->buildTaskListForRotationLearnersByTarget($distribution, $distribution_schedule, $schedule, $release_date, $assessors, $distribution_target, false, $delivery_start, $delivery_end);

                    } else if ($distribution_target->getTargetType() == "schedule_id" && $distribution_target->getTargetScope() == "self") {

                        // On/off service learners assessing the rotation entity
                        // The rotation itself is the target (the rotation entity, not the users)
                        $this->buildTaskListForRotationLearnersByAssessor($distribution, $distribution_schedule, $schedule, $release_date, $assessors, $distribution_target, true, $delivery_start, $delivery_end);

                    } else if ($distribution_target->getTargetType() == "self" && $distribution_target->getTargetScope() == "self") {

                        // Self assessment, use the assessors as targets
                        // fetch the users for a rotation, and use that as the assessors array.
                        $this->buildTaskListForRotationLearnersBySelf($distribution, $distribution_schedule, $schedule, $release_date, $assessors, $distribution_target, false, $delivery_start, $delivery_end);

                    } else {

                        // Default create for proxy id
                        $this->buildTaskListForRotationLearnersByAssessor($distribution, $distribution_schedule, $schedule, $release_date, $assessors, $distribution_target, true, $delivery_start, $delivery_end);
                    }

                    // Assessors are faculty or individuals
                } else if ($distribution->getAssessorOption() == "faculty" || $distribution->getAssessorOption() == "individual_users") {

                    if ($distribution_target->getTargetType() == "schedule_id" && $distribution_target->getTargetScope() != "self") {

                        // Target is on/off service learners. Faculty or individual (maybe external) assessing on/off service learners
                        $this->buildTaskListForRotationLearnersByTarget($distribution, $distribution_schedule, $schedule, $release_date, $assessors, $distribution_target, false, $delivery_start, $delivery_end);

                    } else if ($distribution_target->getTargetType() == "schedule_id" && $distribution_target->getTargetScope() == "self") {

                        // Faculty/External assessing the rotation itself (rotation is the target -- the rotation entity, not the users)
                        // Since the faculty isn't a learner, they will assess all of the blocks/rotation
                        $this->buildTaskListForRotationLearnersByAssessor($distribution, $distribution_schedule, $schedule, $release_date, $assessors, $distribution_target, false, $delivery_start, $delivery_end);

                    } else if ($distribution_target->getTargetType() == "self" && $distribution_target->getTargetScope() == "self") {

                        /** INVALID USE CASE **/

                        // Self assessment, the assessors are also the targets (e.g. faculty or individual users) -- this is an invalid state. A self assessment isn't possible from this context.
                        // The actual functionality supports this configuration, however. If the target was configured as "self" and the assessor as "schedule learners", a real task list would be
                        // produced. However, since we are catching it here, we won't produce a list.

                    } else if ($distribution_target->getTargetType() == "proxy_id" && $distribution_target->getTargetScope() == "self") {

                        // Target is single proxy_id. A single proxy ID from this context can be added as an "additional learner", or a single specified learner.
                        // Check schedule for the target, and then fetch rotations and add assessment if he is within the rotation.
                        // If the specified target is not in the rotation, this function correctly produces no assessment tasks.

                        if ($distribution_target->getTargetRole() == "faculty") {

                            // Role is "faculty"
                            $this->buildTaskListForRotationLearnersByAssessor($distribution, $distribution_schedule, $schedule, $release_date, $assessors, $distribution_target, true, $delivery_start, $delivery_end);

                        } else {

                            // Role is "learner" OR "any", but "any" isn't possible through the editor.
                            $this->buildTaskListForRotationLearnersByTarget($distribution, $distribution_schedule, $schedule, $release_date, $assessors, $distribution_target, true, $delivery_start, $delivery_end); // use the target's proxy ID
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
     * Determine assessment tasks based on the assessors' schedules (assessors are the learners). This would typically be done for faculty being assessed by their respective block-learners.
     *
     * @param $distribution
     * @param $distribution_schedule
     * @param $schedule
     * @param $release_date
     * @param $assessors
     * @param $distribution_target
     * @param $limit_to_assessor
     * @param $delivery_start
     * @param $delivery_end
     */
    private function buildTaskListForRotationLearnersByAssessor($distribution, $distribution_schedule, $schedule, $release_date, $assessors, $distribution_target, $limit_to_assessor = false, $delivery_start = null, $delivery_end = null) {
        $schedule_class = new Entrada_CBME_RotationSchedule();
        foreach ($assessors as $assessor) {
            if ($distribution_schedule->getScheduleType() == "rotation") {
                if ($limit_to_assessor) {
                    // Get the rotations of the assessor and create assessments for those dates.
                    $rotations = $schedule_class->fetchRotations($schedule->getID(), null, $assessor["assessor_value"]);
                } else {
                    $rotations = $schedule_class->fetchRotations($schedule->getID());
                }
                if ($rotations) {
                    $this->addToTaskListByRotationDates(
                        $distribution,
                        $distribution_schedule,
                        $schedule,
                        $rotations,
                        $release_date,
                        $assessor,
                        false,
                        $distribution_target,
                        $delivery_start,
                        $delivery_end
                    );
                }
            } else if ($distribution_schedule->getScheduleType() == "block") {
                $blocks = false;
                // Fetch blocks and create assessments for the relevant dates.
                if ($schedule->getScheduleType() == "rotation_stream") {
                    $blocks = Models_Schedule::fetchAllByParentID($schedule->getID());
                } else if ($schedule->getScheduleType() == "rotation_block") {
                    $blocks[] = $schedule;
                }
                if ($blocks) {
                    $this->addToTaskListByRotationLearnerBlocks(
                        $distribution,
                        $distribution_schedule,
                        $schedule,
                        $blocks,
                        $release_date,
                        $assessor,
                        $limit_to_assessor,
                        $distribution_target,
                        $delivery_start,
                        $delivery_end
                    );
                }
            } else if ($distribution_schedule->getScheduleType() == "repeat") {
                // For each relevant rotation, create assessment tasks for the specified frequency.
                if ($limit_to_assessor) {
                    $rotations = $schedule_class->fetchRotations($schedule->getID(), null, $assessor["assessor_value"]);
                } else {
                    $rotations = $schedule_class->fetchRotations($schedule->getID());
                }
                if ($rotations) {
                    $this->addToTaskListByRotationRepeatDates(
                        $distribution,
                        $distribution_schedule,
                        $schedule,
                        $rotations,
                        $release_date,
                        $assessor,
                        $distribution_target,
                        $delivery_start,
                        $delivery_end
                    );
                }
            }
        }
    }

    /**
     * Determine assessment tasks based on the targets' rotation schedules. This would typically be faculty assessing the learners in the rotation.
     * This function can only be executed on assessors that have proxy_ids (not externals or non-person entities).
     *
     * @param $distribution
     * @param $distribution_schedule
     * @param $schedule
     * @param $release_date
     * @param $assessors
     * @param $distribution_target
     * @param bool $use_single_proxy_id
     * @param $delivery_start
     * @param $delivery_end
     */
    private function buildTaskListForRotationLearnersByTarget($distribution, $distribution_schedule, $schedule, $release_date, $assessors, $distribution_target, $use_single_proxy_id = false, $delivery_start = null, $delivery_end = null) {
        $schedule_class = new Entrada_CBME_RotationSchedule();
        foreach ($assessors as $assessor) {
            $proxy_id = null;
            if ($use_single_proxy_id && $distribution_target->getTargetID()) {
                $proxy_id = $distribution_target->getTargetID();
            }
            if ($distribution_schedule->getScheduleType() == "rotation") {
                $rotations = $schedule_class->fetchRotations($schedule->getID(), $distribution_target->getTargetScope(), $proxy_id);
                if ($rotations) {
                    $this->addToTaskListByRotationDates(
                        $distribution,
                        $distribution_schedule,
                        $schedule,
                        $rotations,
                        $release_date,
                        $assessor,
                        true,
                        $distribution_target,
                        $delivery_start,
                        $delivery_end
                    );
                }
            } else if ($distribution_schedule->getScheduleType() == "block") {
                $blocks = false;
                if ($schedule->getScheduleType() == "rotation_stream") {
                    $blocks = Models_Schedule::fetchAllByParentID($schedule->getID());
                } else if ($schedule->getScheduleType() == "rotation_block") {
                    $blocks[] = $schedule;
                }
                if ($blocks) {
                    $this->addToTaskListByRotationLearnerBlocks(
                        $distribution,
                        $distribution_schedule,
                        $schedule,
                        $blocks,
                        $release_date,
                        $assessor,
                        false,
                        $distribution_target,
                        $delivery_start,
                        $delivery_end,
                        $use_single_proxy_id
                    );
                }
            } else if ($distribution_schedule->getScheduleType() == "repeat") {
                $rotations = $schedule_class->fetchRotations(
                    $schedule->getID(),
                    $distribution_target->getTargetScope(),
                    $proxy_id
                );
                if ($rotations) {
                    $this->addToTaskListByRotationRepeatDates(
                        $distribution,
                        $distribution_schedule,
                        $schedule,
                        $rotations,
                        $release_date,
                        $assessor,
                        $distribution_target,
                        $delivery_start,
                        $delivery_end,
                        false,
                        true
                    );
                }
            }
        }
    }

    /**
     * Determine assessment tasks based on the targets' rotation schedules, for self assessments.
     *
     * @param $distribution
     * @param $distribution_schedule
     * @param $schedule
     * @param $release_date
     * @param $assessors
     * @param $distribution_target
     * @param bool $use_single_proxy_id
     * @param $delivery_start
     * @param $delivery_end
     */
    private function buildTaskListForRotationLearnersBySelf($distribution, $distribution_schedule, $schedule, $release_date, $assessors, $distribution_target, $use_single_proxy_id = false, $delivery_start = null, $delivery_end = null) {
        $schedule_class = new Entrada_CBME_RotationSchedule();
        foreach ($assessors as $assessor) {
            $proxy_id = null;
            if ($use_single_proxy_id && $distribution_target->getTargetID()) {
                $proxy_id = $distribution_target->getTargetID();
            }
            if ($distribution_schedule->getScheduleType() == "rotation") {
                $rotations = $schedule_class->fetchRotations($schedule->getID(), $distribution_target->getTargetScope(), $proxy_id);
                if ($rotations) {
                    $this->addToTaskListByRotationDatesSelf(
                        $distribution,
                        $distribution_schedule,
                        $schedule,
                        $rotations,
                        $release_date,
                        $assessor,
                        $distribution_target,
                        $delivery_start,
                        $delivery_end
                    );
                }
            } else if ($distribution_schedule->getScheduleType() == "block") {
                $blocks = false;
                if ($schedule->getScheduleType() == "rotation_stream") {
                    $blocks = Models_Schedule::fetchAllByParentID($schedule->getID());
                } else if ($schedule->getScheduleType() == "rotation_block") {
                    $blocks[] = $schedule;
                }
                if ($blocks) {
                    $this->addToTaskListByRotationLearnerBlocksSelf(
                        $distribution,
                        $distribution_schedule,
                        $schedule,
                        $blocks,
                        $release_date,
                        $assessor,
                        $distribution_target,
                        $delivery_start,
                        $delivery_end
                    );
                }
            } else if ($distribution_schedule->getScheduleType() == "repeat") {
                $rotations = $schedule_class->fetchRotations($schedule->getID(), $distribution_target->getTargetScope(), $proxy_id);
                if ($rotations) {
                    $this->addToTaskListByRotationRepeatDatesSelf(
                        $distribution,
                        $distribution_schedule,
                        $schedule,
                        $rotations,
                        $release_date,
                        $assessor,
                        $distribution_target,
                        $delivery_start,
                        $delivery_end
                    );
                }
            }
        }
    }

    /**
     * Determine distribution assessment records based on entire rotations (e.g. if someone is scheduled in Block 1 and Block 2, the assessment will encompass and apply to both blocks).
     *
     * @param $distribution
     * @param $distribution_schedule
     * @param $schedule
     * @param $rotations
     * @param $release_date
     * @param $assessor
     * @param $distribution_target
     * @param $delivery_start
     * @param $delivery_end
     * @param $limit_to_target
     */
    private function addToTaskListByRotationDates($distribution, $distribution_schedule, $schedule, $rotations, $release_date, $assessor, $limit_to_target = false, $distribution_target, $delivery_start = null, $delivery_end = null) {
        $schedule_class = new Entrada_CBME_RotationSchedule();
        $rotation_dates = $schedule_class->getRotationDates($rotations, $distribution->getOrganisationID());
        if (!$rotation_dates || empty($rotation_dates)) {
            return;
        }
        $target_list = $this->getTargetList($distribution_target);
        if (empty($target_list)) {
            return;
        }
        foreach ($rotation_dates["unique_rotation_dates"] as $unique_rotation_date) {
            $delivery_date = $schedule_class->calculateDateByOffset(
                $distribution_schedule->getDeliveryPeriod(),
                $distribution_schedule->getPeriodOffset(),
                $unique_rotation_date[0],
                $unique_rotation_date[1]
            );
            if ((!isset($delivery_start) || $delivery_date >= $delivery_start) &&
                (!isset($delivery_end) || $delivery_date <= $delivery_end)
            ) {
                // Check if dassessment record already exists.
                $distribution_assessment_records = Models_Assessments_Assessor::fetchRowByAssessorTypeAssessorValueStartDateEndDate(
                    $assessor["assessor_type"],
                    $assessor["assessor_value"],
                    $distribution->getID(),
                    $unique_rotation_date[0],
                    $unique_rotation_date[1]
                );
                if ($distribution_target->getTargetScope() == "self" && $distribution_target->getTargetType() == "schedule_id") {
                    // Target is the rotation.
                    $targets = array(
                        $this->buildAssessmentTarget(
                            $distribution,
                            $distribution->getID(),
                            $distribution_assessment_records,
                            array(
                                "target_value" => $distribution_target->getTargetID(),
                                "target_type" => "schedule_id",
                                "name" => $target_list[0]["name"],
                                "target_group" => $target_list[0]["target_group"]
                            )
                        )
                    );
                } else {
                    // Targets are people within the schedule.
                    $targets = $this->buildAssessmentTargetsByRotationDates(
                        $distribution,
                        $distribution->getID(),
                        $distribution_assessment_records,
                        $rotation_dates,
                        $unique_rotation_date[0],
                        $unique_rotation_date[1],
                        $target_list,
                        $limit_to_target,
                        $distribution_target,
                        $delivery_date
                    );
                }
                if ($targets) {

                    $expiry_date = ($distribution->getExpiryOffset() ? ($delivery_date + $distribution->getExpiryOffset()) : null);
                    $expiry_notification_date = ($expiry_date && $distribution->getExpiryNotificationOffset() ? ($expiry_date - $distribution->getExpiryNotificationOffset()) : null);

                    $this->addToTaskList(
                        $this->getDistributionID(),
                        $delivery_date,
                        $release_date,
                        $expiry_date,
                        $expiry_notification_date,
                        $unique_rotation_date[0],
                        $unique_rotation_date[1],
                        array(),
                        array($assessor),
                        "assessment",
                        "assessment",
                        null,
                        null,
                        false,
                        $distribution_schedule->getScheduleType(),
                        $distribution_schedule->getDeliveryPeriod(),
                        $distribution_schedule->getPeriodOffset(),
                        $targets[0]["target_type"],
                        "schedule_id",
                        $schedule->getID(),
                        $distribution_assessment_records,
                        $unique_rotation_date[0],
                        $unique_rotation_date[1],
                        $distribution->getMinSubmittable(),
                        $distribution->getMaxSubmittable()
                    );
                    $this->addToTargetList($targets, array($assessor), $delivery_date, $release_date, $unique_rotation_date[0], $unique_rotation_date[1]);
                }
            }
        }
    }

    /**
     * Determine assessments for self assessment rotation dates.
     *
     * @param $distribution
     * @param $distribution_schedule
     * @param $schedule
     * @param $rotations
     * @param $release_date
     * @param $assessor
     * @param $distribution_target
     * @param $delivery_start
     * @param $delivery_end
     */
    private function addToTaskListByRotationDatesSelf($distribution, $distribution_schedule, $schedule, $rotations, $release_date, $assessor, $distribution_target, $delivery_start = null, $delivery_end = null) {
        $schedule_class = new Entrada_CBME_RotationSchedule();
        $rotation_dates = $schedule_class->getRotationDates($rotations, $distribution->getOrganisationID());
        if (!$rotation_dates || empty($rotation_dates)) {
            return;
        }
        if (!isset($assessor["proxy_id"])) {
            return;
        }
        $target_list = array(
            array(
                "adistribution_id" => $distribution->getID(),
                "dassessment_id" => null,
                "proxy_id" => $assessor["assessor_value"],
                "target_type" => "proxy_id",
                "target_value" => $assessor["assessor_value"],
                "name" => $assessor["name"]
            )
        );
        // The rotation is assessing itself (the learners are assessing the rotation entity)
        foreach ($rotation_dates["unique_rotation_dates"] as $unique_rotation_date) {
            foreach ($rotation_dates["all_rotation_dates"] as $proxy_id => $block_dates) {
                foreach ($block_dates as $block_date) {
                    if ($block_date[0] == $unique_rotation_date[0] &&
                        $block_date[1] == $unique_rotation_date[1] &&
                        $proxy_id == $assessor["assessor_value"]
                    ) {
                        $delivery_date = $schedule_class->calculateDateByOffset(
                            $distribution_schedule->getDeliveryPeriod(),
                            $distribution_schedule->getPeriodOffset(),
                            $unique_rotation_date[0],
                            $unique_rotation_date[1]
                        );
                        if ((!isset($delivery_start) || $delivery_date >= $delivery_start) &&
                            (!isset($delivery_end) || $delivery_date <= $delivery_end)
                        ) {
                            // Check if dassessment records already exist
                            $distribution_assessment_records = Models_Assessments_Assessor::fetchRowByAssessorTypeAssessorValueStartDateEndDate(
                                $assessor["assessor_type"],
                                $assessor["assessor_value"],
                                $distribution->getID(),
                                $unique_rotation_date[0],
                                $unique_rotation_date[1]
                            );
                            $targets = $this->buildAssessmentTargetsByRotationDates(
                                $distribution,
                                $distribution->getID(),
                                $distribution_assessment_records,
                                $rotation_dates,
                                $unique_rotation_date[0],
                                $unique_rotation_date[1],
                                $target_list,
                                true,
                                $distribution_target,
                                $delivery_date
                            );
                            if ($targets) {

                                $expiry_date = ($distribution->getExpiryOffset() ? ($delivery_date + $distribution->getExpiryOffset()) : null);
                                $expiry_notification_date = ($expiry_date && $distribution->getExpiryNotificationOffset() ? ($expiry_date - $distribution->getExpiryNotificationOffset()) : null);

                                $this->addToTaskList(
                                    $this->getDistributionID(),
                                    $delivery_date,
                                    $release_date,
                                    $expiry_date,
                                    $expiry_notification_date,
                                    $unique_rotation_date[0],
                                    $unique_rotation_date[1],
                                    array(),
                                    array($assessor),
                                    "assessment",
                                    "assessment",
                                    null,
                                    null,
                                    false,
                                    $distribution_schedule->getScheduleType(),
                                    $distribution_schedule->getDeliveryPeriod(),
                                    $distribution_schedule->getPeriodOffset(),
                                    $targets[0]["target_type"],
                                    "schedule_id",
                                    $schedule->getID(),
                                    $distribution_assessment_records,
                                    $unique_rotation_date[0],
                                    $unique_rotation_date[1],
                                    $distribution->getMinSubmittable(),
                                    $distribution->getMaxSubmittable()
                                );
                                $this->addToTargetList($targets, array($assessor), $delivery_date, $release_date, $unique_rotation_date[0], $unique_rotation_date[1]);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Determine assessments for self assessment block dates.
     *
     * @param $distribution
     * @param $distribution_schedule
     * @param $schedule
     * @param $blocks
     * @param $release_date
     * @param $assessor
     * @param $distribution_target
     * @param $delivery_start
     * @param $delivery_end
     */
    private function addToTaskListByRotationLearnerBlocksSelf($distribution, $distribution_schedule, $schedule, $blocks, $release_date, $assessor, $distribution_target, $delivery_start = null, $delivery_end = null) {
        $schedule_class = new Entrada_CBME_RotationSchedule();
        if (empty($blocks)) {
            return;
        }
        if (!isset($assessor["proxy_id"])) {
            return;
        }
        $proxy_id = $assessor["proxy_id"];
        $single_target = array(
            "adistribution_id" => $distribution->getID(),
            "dassessment_id" => null,
            "proxy_id" => $assessor["assessor_value"],
            "target_type" => "proxy_id",
            "target_value" => $assessor["assessor_value"],
            "name" => $assessor["name"]
        );
        // The rotation is assessing itself (the learners are assessing the rotation entity)
        foreach ($blocks as $block) {
            $learner_blocks = $schedule_class->fetchLearnerBlocks($block->getID(), $proxy_id);
            if (!$learner_blocks || empty($learner_blocks)) {
                continue;
            }
            $delivery_date = $schedule_class->calculateDateByOffset(
                $distribution_schedule->getDeliveryPeriod(),
                $distribution_schedule->getPeriodOffset(),
                $block->getStartDate(),
                $block->getEndDate()
            );
            if ((!isset($delivery_start) || $delivery_date >= $delivery_start) &&
                (!isset($delivery_end) || $delivery_date <= $delivery_end)
            ) {
                // Check if dassessment records already exist
                $distribution_assessment_records = Models_Assessments_Assessor::fetchRowByAssessorTypeAssessorValueStartDateEndDate(
                    $assessor["assessor_type"],
                    $assessor["assessor_value"],
                    $distribution->getID(),
                    $block->getStartDate(),
                    $block->getEndDate()
                );
                // Targets are people within the schedule.
                $targets = $this->buildAssessmentTargetsByBlockSelf(
                    $distribution,
                    $distribution->getID(),
                    $distribution_assessment_records,
                    $block->getID(),
                    $distribution_target,
                    $single_target,
                    $delivery_date
                );
                if ($targets) {

                    $expiry_date = ($distribution->getExpiryOffset() ? ($delivery_date + $distribution->getExpiryOffset()) : null);
                    $expiry_notification_date = ($expiry_date && $distribution->getExpiryNotificationOffset() ? ($expiry_date - $distribution->getExpiryNotificationOffset()) : null);

                    $this->addToTaskList(
                        $this->getDistributionID(),
                        $delivery_date,
                        $release_date,
                        $expiry_date,
                        $expiry_notification_date,
                        $block->getStartDate(),
                        $block->getEndDate(),
                        array(),
                        array($assessor),
                        "assessment",
                        "assessment",
                        null,
                        null,
                        false,
                        $distribution_schedule->getScheduleType(),
                        $distribution_schedule->getDeliveryPeriod(),
                        $distribution_schedule->getPeriodOffset(),
                        $targets[0]["target_type"],
                        "schedule_id",
                        $schedule->getID(),
                        $distribution_assessment_records,
                        $block->getStartDate(),
                        $block->getEndDate(),
                        $distribution->getMinSubmittable(),
                        $distribution->getMaxSubmittable()
                    );
                    $this->addToTargetList($targets, array($assessor), $delivery_date, $release_date, $block->getStartDate(), $block->getEndDate());
                }
            }
        }
    }

    /**
     * Determine assessments for self assessment repeat dates.
     *
     * @param $distribution
     * @param $distribution_schedule
     * @param $schedule
     * @param $rotations
     * @param $release_date
     * @param $assessor
     * @param $distribution_target
     * @param $delivery_start
     * @param $delivery_end
     */
    private function addToTaskListByRotationRepeatDatesSelf($distribution, $distribution_schedule, $schedule, $rotations, $release_date, $assessor, $distribution_target, $delivery_start = null, $delivery_end = null) {
        $schedule_class = new Entrada_CBME_RotationSchedule();
        $rotation_dates = $schedule_class->getRotationDates($rotations, $distribution->getOrganisationID());
        if (!$rotation_dates || empty($rotation_dates)) {
            return;
        }
        $target_list = array(
            array(
                "adistribution_id" => $distribution->getID(),
                "dassessment_id" => null,
                "target_type" => "proxy_id",
                "target_value" => $assessor["assessor_value"],
                "name" => $assessor["name"]
            )
        );
        // The rotation is assessing itself (the learners are assessing the rotation entity)
        foreach ($rotation_dates["unique_rotation_dates"] as $unique_rotation_date) {
            foreach ($rotation_dates["all_rotation_dates"] as $proxy_id => $block_dates) {
                foreach ($block_dates as $block_date) {
                    if ($block_date[0] == $unique_rotation_date[0] &&
                        $block_date[1] == $unique_rotation_date[1] &&
                        $proxy_id == $assessor["assessor_value"]
                    ) {
                        $delivery_date = $schedule_class->calculateDateByFrequency($distribution_schedule->getFrequency(), $unique_rotation_date[0]);
                        while ($delivery_date <= $unique_rotation_date[1]) {
                            if ((!isset($delivery_start) || $delivery_date >= $delivery_start) &&
                                (!isset($delivery_end) || $delivery_date <= $delivery_end)
                            ) {
                                // Check if dassessment record already exists.
                                $distribution_assessment_records = Models_Assessments_Assessor::fetchRowByAssessorTypeAssessorValueStartDateEndDate(
                                    $assessor["assessor_type"],
                                    $assessor["assessor_value"],
                                    $distribution->getID(),
                                    $delivery_date,
                                    $unique_rotation_date[1]
                                );
                                $targets = $this->buildAssessmentTargetsByRotationDates(
                                    $distribution,
                                    $distribution->getID(),
                                    $distribution_assessment_records,
                                    $rotation_dates,
                                    $unique_rotation_date[0],
                                    $unique_rotation_date[1],
                                    $target_list,
                                    true,
                                    $distribution_target,
                                    $delivery_date
                                );
                                if ($targets) {

                                    $expiry_date = ($distribution->getExpiryOffset() ? ($delivery_date + $distribution->getExpiryOffset()) : null);
                                    $expiry_notification_date = ($expiry_date && $distribution->getExpiryNotificationOffset() ? ($expiry_date - $distribution->getExpiryNotificationOffset()) : null);

                                    $this->addToTaskList(
                                        $this->getDistributionID(),
                                        $delivery_date,
                                        $release_date,
                                        $expiry_date,
                                        $expiry_notification_date,
                                        $delivery_date,
                                        $unique_rotation_date[1],
                                        array(),
                                        array($assessor),
                                        "assessment",
                                        "assessment",
                                        null,
                                        null,
                                        false,
                                        $distribution_schedule->getScheduleType(),
                                        $distribution_schedule->getDeliveryPeriod(),
                                        $distribution_schedule->getPeriodOffset(),
                                        $targets[0]["target_type"],
                                        "schedule_id",
                                        $schedule->getID(),
                                        $distribution_assessment_records,
                                        $unique_rotation_date[0],
                                        $unique_rotation_date[1],
                                        $distribution->getMinSubmittable(),
                                        $distribution->getMaxSubmittable()
                                    );
                                    $this->addToTargetList($targets, array($assessor), $delivery_date, $release_date, $delivery_date, $unique_rotation_date[1]);
                                }
                            }
                            $delivery_date += ($distribution_schedule->getFrequency() * 86400);
                        }
                    }
                }
            }
        }
    }

    /**
     * Determine distribution assessment records based on single rotation blocks.
     *
     * @param $distribution
     * @param $distribution_schedule
     * @param $schedule
     * @param $blocks
     * @param $release_date
     * @param $assessor
     * @param $limit_to_assessor
     * @param $distribution_target
     * @param $delivery_start
     * @param $delivery_end
     * @param $limit_to_first_target
     */
    private function addToTaskListByRotationLearnerBlocks($distribution, $distribution_schedule, $schedule, $blocks, $release_date, $assessor, $limit_to_assessor = false, $distribution_target, $delivery_start = null, $delivery_end = null, $limit_to_first_target = false) {
        $proxy_id = null;
        $schedule_class = new Entrada_CBME_RotationSchedule();
        if ($limit_to_assessor) {
            $proxy_id = $assessor["assessor_value"];
        }
        $target_list = $this->getTargetList($distribution_target);
        if (empty($target_list)) {
            return;
        }
        if ($limit_to_first_target) {
            if ($target_list[0]["target_type"] == "proxy_id") {
                $proxy_id = $target_list[0]["target_value"];
            }
        }
        foreach ($blocks as $block) {
            $delivery_date = $schedule_class->calculateDateByOffset(
                $distribution_schedule->getDeliveryPeriod(),
                $distribution_schedule->getPeriodOffset(),
                $block->getStartDate(),
                $block->getEndDate()
            );
            if ((!isset($delivery_start) || $delivery_date >= $delivery_start) &&
                (!isset($delivery_end) || $delivery_date <= $delivery_end)
            ) {
                $learner_blocks = $schedule_class->fetchLearnerBlocks($block->getID(), $proxy_id);
                if (empty($learner_blocks)) {
                    continue;
                }
                // Check if dassessment record already exists.
                $distribution_assessment_records = Models_Assessments_Assessor::fetchRowByAssessorTypeAssessorValueEndDate(
                    $assessor["assessor_type"],
                    $assessor["assessor_value"],
                    $distribution->getID(),
                    $delivery_date
                );
                if ($distribution_target->getTargetScope() == "self" && $distribution_target->getTargetType() == "schedule_id") {
                    // Target is the rotation
                    $targets = array(
                        $this->buildAssessmentTarget(
                            $distribution,
                            $distribution->getID(),
                            $distribution_assessment_records,
                            array(
                                "target_value" => $distribution_target->getTargetID(),
                                "target_type" => "schedule_id",
                                "name" => $target_list[0]["name"]
                            )
                        )
                    );
                } else if ($distribution_target->getTargetScope() == "self" && $distribution_target->getTargetType() == "proxy_id") {
                    // Target is an individual.
                    $targets = array();

                    $eligible = true;
                    if ($distribution_target->getTargetRole() == "learner") {
                        $eligible = $this->isEligibleTarget($distribution, "proxy_id", $distribution_target->getTargetID(), $delivery_date);
                    }

                    if ($distribution->getExcludeSelfAssessments() ) {
                        if ($distribution_target->getTargetID() == $assessor["assessor_value"]) {
                            $eligible = false;
                        }
                    }

                    if ($eligible) {
                        $targets[] = $this->buildAssessmentTarget(
                            $distribution,
                            $distribution->getID(),
                            $distribution_assessment_records,
                            array(
                                "target_value" => $distribution_target->getTargetID(),
                                "target_type" => "proxy_id",
                                "name" => $target_list[0]["name"]
                            )
                        );
                    }

                } else if ($distribution_target->getTargetScope() == "self" && $distribution_target->getTargetType() == "external_hash") {
                    // Target is an external
                    $targets = array(
                        $this->buildAssessmentTarget(
                            $distribution,
                            $distribution->getID(),
                            $distribution_assessment_records,
                            array(
                                "target_value" => $distribution_target->getTargetID(),
                                "target_type" => "external_hash",
                                "name" => $target_list[0]["name"]
                            )
                        )
                    );
                } else {
                    // Targets are people within the schedule.
                    $targets = $this->buildAssessmentTargetsByBlock(
                        $distribution,
                        $distribution->getID(),
                        $distribution_assessment_records,
                        $block->getID(),
                        $distribution_target,
                        $target_list,
                        $delivery_date
                    );
                }
                if ($targets) {

                    $expiry_date = ($distribution->getExpiryOffset() ? ($delivery_date + $distribution->getExpiryOffset()) : null);
                    $expiry_notification_date = ($expiry_date && $distribution->getExpiryNotificationOffset() ? ($expiry_date - $distribution->getExpiryNotificationOffset()) : null);

                    $this->addToTaskList(
                        $this->getDistributionID(),
                        $delivery_date,
                        $release_date,
                        $expiry_date,
                        $expiry_notification_date,
                        $delivery_date,
                        $delivery_date,
                        array(),
                        array($assessor),
                        "assessment",
                        "assessment",
                        null,
                        null,
                        false,
                        $distribution_schedule->getScheduleType(),
                        $distribution_schedule->getDeliveryPeriod(),
                        $distribution_schedule->getPeriodOffset(),
                        $targets[0]["target_type"],
                        "schedule_id",
                        $schedule->getID(),
                        $distribution_assessment_records,
                        $block->getStartDate(),
                        $block->getEndDate(),
                        $distribution->getMinSubmittable(),
                        $distribution->getMaxSubmittable()
                    );
                    $this->addToTargetList($targets, array($assessor), $delivery_date, $release_date, $delivery_date, $delivery_date);
                }
            }
        }
    }

    /**
     * Determine distribution assessment records based on repeat frequency within a rotation.
     *
     * @param $distribution
     * @param $distribution_schedule
     * @param $schedule
     * @param $rotations
     * @param $release_date
     * @param $assessor
     * @param $distribution_target
     * @param $delivery_start
     * @param $delivery_end
     * @param $limit_to_assessor
     * @param $limit_to_first_target
     */
    private function addToTaskListByRotationRepeatDates($distribution, $distribution_schedule, $schedule, $rotations, $release_date, $assessor, $distribution_target, $delivery_start = null, $delivery_end = null, $limit_to_assessor = false, $limit_to_first_target = false) {
        $schedule_class = new Entrada_CBME_RotationSchedule();
        if ($limit_to_assessor) {
            $target_list = array(
                array(
                    "adistribution_id" => $distribution->getID(),
                    "dassessment_id" => null,
                    "proxy_id" => $assessor["assessor_value"],
                    "target_type" => "proxy_id",
                    "target_value" => $assessor["assessor_value"],
                    "name" => $assessor["name"]
                )
            );
        } else {
            $target_list = $this->getTargetList($distribution_target);
        }
        if (empty($target_list)) {
            return;
        }
        $rotation_dates = $schedule_class->getRotationDates($rotations, $distribution->getOrganisationID());
        if ($rotation_dates["unique_rotation_dates"]) {
            foreach ($rotation_dates["unique_rotation_dates"] as $unique_rotation_date) {
                $delivery_date = $schedule_class->calculateDateByFrequency($distribution_schedule->getFrequency(), $unique_rotation_date[0]);
                while ($delivery_date <= $unique_rotation_date[1]) {
                    if ((!isset($delivery_start) || $delivery_date >= $delivery_start) &&
                        (!isset($delivery_end) || $delivery_date <= $delivery_end)
                    ) {
                        // Check if dassessment record already exists.
                        $distribution_assessment_records = Models_Assessments_Assessor::fetchRowByAssessorTypeAssessorValueStartDateEndDate(
                            $assessor["assessor_type"],
                            $assessor["assessor_value"],
                            $distribution->getID(),
                            $delivery_date,
                            $unique_rotation_date[1]
                        );
                        if ($distribution_target->getTargetScope() == "self" &&
                            $distribution_target->getTargetType() == "schedule_id"
                        ) {
                            // Target is the rotation
                            $targets = array(
                                $this->buildAssessmentTarget(
                                    $distribution,
                                    $distribution->getID(),
                                    $distribution_assessment_records,
                                    array(
                                        "target_value" => $distribution_target->getTargetID(),
                                        "target_type" => "schedule_id",
                                        "name" => $target_list[0]["name"],
                                        "target_group" => $target_list[0]["target_group"]
                                    )
                                )
                            );
                        } else {
                            $targets = $this->buildAssessmentTargetsByRotationDates(
                                $distribution,
                                $distribution->getID(),
                                $distribution_assessment_records,
                                $rotation_dates,
                                $unique_rotation_date[0],
                                $unique_rotation_date[1],
                                $target_list,
                                $limit_to_first_target,
                                $distribution_target,
                                $delivery_date
                            );
                        }
                        if ($targets) {

                            $expiry_date = ($distribution->getExpiryOffset() ? ($delivery_date + $distribution->getExpiryOffset()) : null);
                            $expiry_notification_date = ($expiry_date && $distribution->getExpiryNotificationOffset() ? ($expiry_date - $distribution->getExpiryNotificationOffset()) : null);

                            $this->addToTaskList(
                                $this->getDistributionID(),
                                $delivery_date,
                                $release_date,
                                $expiry_date,
                                $expiry_notification_date,
                                $delivery_date,
                                $unique_rotation_date[1],
                                array(),
                                array($assessor),
                                "assessment",
                                "assessment",
                                null,
                                null,
                                false,
                                $distribution_schedule->getScheduleType(),
                                $distribution_schedule->getDeliveryPeriod(),
                                $distribution_schedule->getPeriodOffset(),
                                $targets[0]["target_type"],
                                "schedule_id",
                                $schedule->getID(),
                                $distribution_assessment_records,
                                $unique_rotation_date[0],
                                $unique_rotation_date[1],
                                $distribution->getMinSubmittable(),
                                $distribution->getMaxSubmittable()
                            );
                            $this->addToTargetList($targets, array($assessor), $delivery_date, $release_date, $delivery_date, $unique_rotation_date[1]);
                        }
                    }
                    $delivery_date += ($distribution_schedule->getFrequency() * 86400);
                }
            }
        }
    }

    /**
     * Create assessment target records for a corresponding distribution assessment task. This is required when an assessment is created for a rotation schedule.
     *
     * @param Models_Assessments_Distribution $distribution
     * @param $distribution_id
     * @param Models_Assessments_Distribution_Assessor $dassessment
     * @param $rotation_dates
     * @param $unique_start_date
     * @param $unique_end_date
     * @param $target_list
     * @param bool $limit_to_target
     * @param $distribution_target
     * @param $delivery_date
     *
     * @return array $targets
     */
    private function buildAssessmentTargetsByRotationDates($distribution, $distribution_id, $dassessment, $rotation_dates, $unique_start_date, $unique_end_date, $target_list, $limit_to_target = false, $distribution_target, $delivery_date = null) {
        $targets = array();
        foreach ($rotation_dates["all_rotation_dates"] as $proxy_id => $user_rotation_dates) {
            foreach ($user_rotation_dates as $user_end_date => $user_rotation_date) {
                if ($unique_start_date == $user_rotation_date[0] &&
                    $unique_end_date == $user_rotation_date[1]
                ) {
                    foreach ($target_list as $target) {

                        $eligible = true;
                        if ($distribution_target->getTargetRole() == "learner") {
                            $eligible = $this->isEligibleTarget($distribution, $target["target_type"], $target["target_value"], $delivery_date);
                        }

                        if ($distribution->getExcludeSelfAssessments() && $target["target_type"] == "proxy_id") {
                            if ($target["target_value"] == $dassessment->getAssessorValue()) {
                                $eligible = false;
                            }
                        }

                        if ($eligible) {
                            if ($limit_to_target) {
                                if ($target["target_value"] == $proxy_id && $target["target_type"] == "proxy_id") {
                                    $targets[] = $this->buildAssessmentTarget($distribution, $distribution_id, $dassessment, $target);
                                }
                            } else {
                                $targets[] = $this->buildAssessmentTarget($distribution, $distribution_id, $dassessment, $target);
                            }
                        }
                    }
                }
            }
        }
        return $targets;
    }

    /**
     * Create assessment target records for an assessment that is block based.
     *
     * @param Models_Assessments_Distribution $distribution
     * @param $distribution_id
     * @param Models_Assessments_Distribution_Assessor $dassessment
     * @param $block_id
     * @param Models_Assessments_Distribution_Target $distribution_target_record
     * @param array $target_list
     * @param $delivery_date
     *
     * @return array $targets
     */
    private function buildAssessmentTargetsByBlock($distribution, $distribution_id, $dassessment, $block_id, $distribution_target_record, $target_list, $delivery_date = null) {
        $targets = array();
        $schedule_class = new Entrada_CBME_RotationSchedule();
        if ($distribution_target_record->getTargetType() == "schedule_id") {
            $block_rotations = $schedule_class->fetchBlockRotations($block_id, $distribution_target_record->getTargetScope());
            if ($block_rotations) {
                foreach ($block_rotations as $block_rotation) {

                    $eligible = true;
                    if ($distribution_target_record->getTargetRole() == "learner") {
                        $eligible = $this->isEligibleTarget($distribution, "proxy_id", $block_rotation["audience_value"], $delivery_date);
                    }

                    if ($distribution->getExcludeSelfAssessments()) {
                        if ($block_rotation["audience_value"] == $dassessment->getAssessorValue()) {
                            $eligible = false;
                        }
                    }

                    if ($eligible) {
                        $target_info = array(
                            "target_value" => $block_rotation["audience_value"],
                            "target_type" => "proxy_id"
                        );
                        // Attempt to match this target to access expanded information.
                        foreach ($target_list as $target) {
                            if ($target["target_type"] == "proxy_id" && $target["target_value"] == $block_rotation["audience_value"]) {
                                $target_info["target_group"] = $target["target_group"];
                                $target_info["name"] = $target["name"];
                            }
                        }
                        $targets[] = $this->buildAssessmentTarget($distribution, $distribution_id, $dassessment, $target_info);
                    }
                }
            }
        }
        return $targets;
    }

    /**
     * Build assessment target list for a block-based self-assessment, that is, one assessment per each of the learner's blocks, where the
     * target and assessor is the learner.
     *
     * @param Models_Assessments_Distribution $distribution
     * @param $distribution_id
     * @param Models_Assessments_Distribution_Assessor $dassessment
     * @param $block_id
     * @param Models_Assessments_Distribution_Target $distribution_target_record
     * @param array $target
     * @param $delivery_date
     *
     * @return array $targets
     */
    private function buildAssessmentTargetsByBlockSelf($distribution, $distribution_id, $dassessment, $block_id, $distribution_target_record, $target, $delivery_date = null) {
        $targets = array();
        $schedule_class = new Entrada_CBME_RotationSchedule();
        $block_rotations = $schedule_class->fetchBlockRotations($block_id, $distribution_target_record->getTargetScope());
        if ($block_rotations) {
            foreach ($block_rotations as $block_rotation) {

                $eligible = true;
                if ($distribution_target_record->getTargetRole() == "learner") {
                    $eligible = $this->isEligibleTarget($distribution, "proxy_id", $block_rotation["audience_value"], $delivery_date);
                }

                if ($distribution->getExcludeSelfAssessments()) {
                    if ($block_rotation["audience_value"] == $dassessment->getAssessorValue()) {
                        $eligible = false;
                    }
                }

                if ($eligible) {
                    $target_info = array(
                        "target_value" => $block_rotation["audience_value"],
                        "target_type" => "proxy_id"
                    );
                    // Attempt to match this target to access expanded information.
                    if ($target["target_type"] == "proxy_id" && $target["target_value"] == $block_rotation["audience_value"]) {
                        //$target_info["target_group"] = $target["target_group"];
                        $target_info["name"] = $target["name"];
                    } else {
                        continue;
                    }
                    $targets[] = $this->buildAssessmentTarget($distribution, $distribution_id, $dassessment, $target_info);
                }
            }
        }
        return $targets;
    }

    /**
     * Extrapolates targets from a distribution target record.
     * Optionally store the result set in local storage for re-use, in order to minimize db hits for the distribution_target.
     *
     * @param Models_Assessments_Distribution_Target $distribution_target
     * @param bool $use_local_storage
     * @return array $targets
     */
    private function getTargetList($distribution_target, $use_local_storage = true) {
        $index = md5(serialize($distribution_target));
        if ($this->isInStorage("target-list", $index) && $use_local_storage) {
            return $this->fetchFromStorage("target-list", $index);
        } else {
            $targets = Models_Assessments_Distribution_Target::getDistributionAssessmentTargetsByScope(
                $distribution_target->getAdistributionID(),
                $distribution_target->getTargetId(),
                $distribution_target->getTargetType(),
                $distribution_target->getTargetScope(),
                $distribution_target->getTargetRole()
            );
            if ($use_local_storage) {
                $this->addToStorage("target-list", $targets, $index);
            }
            return $targets;
        }
    }

    /**
     * Create a standard target array. Optionally find the matching assessment target record for the provided assessment ID.
     *
     * @param Models_Assessments_Distribution $distribution
     * @param int $distribution_id
     * @param bool Models_Assessments_Distribution_Assessor $dassessment
     * @param array $target
     * @param bool $find_current_record
     *
     * @return array $target
     */
    private function buildAssessmentTarget($distribution, $distribution_id, $dassessment = null, $target, $find_current_record = true) {
        $deleted_date = null;
        $current_record = array();
        $progress = array("inprogress" => array(), "complete" => array());
        $should_exist = true;

        if ($find_current_record && isset($dassessment) && $dassessment) {
            $target_found = Models_Assessments_AssessmentTarget::fetchRowByDAssessmentIDTargetTypeTargetValueIncludeDeleted(
                $dassessment->getID(),
                $target["target_type"],
                $target["target_value"]
            );
            if ($target_found) {
                $current_record[] = $target_found;
                // If the target was deleted, the task "should not exist".
                if ($target_found->getDeletedDate()) {
                    $should_exist = false;
                }
            }
            $progress_found = Models_Assessments_Progress::fetchAllByDassessmentIDAssessorTypeAssessorValueTargetTypeTargetRecordID(
                $dassessment->getID(),
                $dassessment->getAssessorType(),
                $dassessment->getAssessorValue(),
                $target["target_type"],
                $target["target_value"]
            );
            if ($progress_found) {
                foreach ($progress_found as $progress_record) {
                    $progress[$progress_record->getProgressValue()][] = $progress_record;
                }
            }
        }
        $target = array(
            "adistribution_id" => $distribution_id,
            "dassessment_id" => (isset($dassessment) && $dassessment ? $dassessment->getID() : null),
            "target_type" => $target["target_type"],
            "target_value" => $target["target_value"],
            "target_name" => isset($target["name"]) ? $target["name"] : null,
            "target_group" => isset($target["target_group"]) ? $target["target_group"] : null,
            "task_type" => $distribution->getAssessmentType(),
            "should_exist" => $should_exist,
            "current_record" => $current_record,
            "progress" => $progress
        );
        return $target;
    }

}