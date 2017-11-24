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
 * A class to handle Distribution Delegation functionality. This utility object
 * provides an interface for accessing and manipulating delegation based
 * distribution data. This utility abstracts the creation and manipulation
 * of all delegation related tasks.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Entrada_Utilities_Assessments_DistributionDelegation extends Entrada_Utilities_Assessments_Base {

    protected $adistribution_id, $addelegation_id;
    private $notifications = array(); // Notifications queue

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

    public function getDelegationID() {
        return $this->addelegation_id;
    }

    public function getAddelegationID() {
        return $this->addelegation_id;
    }

    public function setAddelegationID($id) {
        $this->addelegation_id = $id;
    }

    public function setDelegationID($id) {
        $this->addelegation_id = $id;
    }

    //--- Public functions ---//

    /**
     * Queue a reminder notification directly.
     *
     * @return bool
     */
    public function sendDelegationReminder() {
        $distribution = Models_Assessments_Distribution::fetchRowByID($this->getDistributionID());
        if ($distribution) {
            $delegation = Models_Assessments_Distribution_Delegation::fetchRowByID($this->getDelegationID());
            if ($delegation) {
                $this->queueDelegatorNotifications($distribution, $delegation, $delegation->getDelegatorID(), $distribution->getNotifications(), false, true, false);
                return true; // queued notification
            }
        }
        return false; // did not queue notification
    }

    /**
     * Create a new cbl_assessment_distribution_delegations record.
     *
     * @param int $delegator_id
     * @param string $delegator_type
     * @param int $delivery_date
     * @param int $start_date
     * @param int $end_date
     * @return bool
     */
    public function createDistributionDelegationRecord ($delegator_id, $delegator_type = "proxy_id", $delivery_date, $start_date = null, $end_date = null) {

        // Before creating a distribution task, we must first check if there are targets to assess.
        $has_targets = false;

        $potentials = $this->getDelegationTargetsAndAssessors($start_date, $end_date, false);
        foreach ($potentials as $p) {
            // Filtering by date produced at least 1 result, so we can create a task for it.
            if (!$p["no_targets"]) {
                $has_targets = true;
            }
        }

        if ($has_targets) {
            $construction = array(
                "adistribution_id" => $this->getDistributionID(),
                "delegator_id" => $delegator_id,
                "delegator_type" => $delegator_type,
                "created_by" => $delegator_id,
                "created_date" => time(),
                "delivery_date" => $delivery_date
            );
            if ($start_date && $end_date) {
                $construction["start_date"] = $start_date;
                $construction["end_date"] = $end_date;
            }
            $task = new Models_Assessments_Distribution_Delegation($construction);
            return $task->insert();
        }

        // True is a valid return state; false is only returned on error, i.e., when insert fails.
        return true;
    }


    /**
     * For rotation schedule based distributions, we must be aware of the schedule type. This method is used by expandTargetData() to
     * limit the fetched targets to a specific set of learners. This function, based on schedule type, will fetch those audience IDs.
     * In block-based distributions, we fetch audience IDs for learners that are in the block, regardless of whether their rotation
     * includes multiple blocks (contiguous blocks). Default behaviour for non-block based distributions is to simply check the learner's
     * entire rotation (the contigious blocks).
     *
     * @param $filter_start_date
     * @param $filter_end_date
     * @return null
     */
    private function fetchAudienceIDsForDistributionByScheduleTypeAndFilterDate($filter_start_date, $filter_end_date) {
        $audience_ids = array();

        if ($filter_start_date && $filter_end_date) {
            if ($distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($this->getDistributionID())) {
                if ($distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($this->getDistributionID())) {
                    if ($schedule = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID())) {
                        if ($distribution_schedule->getScheduleType() == "block") {
                            // Block based, used one block only
                            $blocks = array();
                            if ($schedule->getScheduleType() == "rotation_stream") {
                                $blocks = Models_Schedule::fetchAllByParentID($schedule->getID());
                            } else if ($schedule->getScheduleType() == "rotation_block") {
                                $blocks[] = $schedule;
                            }
                            foreach ($blocks as $block) {
                                $learner_blocks = $this->fetchLearnerBlocks($block->getID());
                                foreach ($learner_blocks as $learner_block) {
                                    if ($learner_block["start_date"] == $filter_start_date && $learner_block["end_date"] == $filter_end_date) {
                                        $audience_ids[] = $learner_block["audience_value"];
                                    }
                                }
                            }
                        } else {
                            // Rotation, use contiguous blocks
                            $rotations = $this->fetchRotations($schedule->getID());
                            if ($rotations) {
                                $rotation_dates = $this->getRotationDates($rotations, $distribution->getOrganisationID());
                                foreach ($rotation_dates["all_rotation_dates"] as $proxy_id => $rotation_info) {
                                    foreach ($rotation_info as $end_date => $start_and_end_date) {
                                        if ($start_and_end_date[0] == $filter_start_date && $start_and_end_date[1] == $filter_end_date) {
                                            $audience_ids[] = $proxy_id;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $audience_ids;
    }

    /**
     * Fetch all possible targets and their currently assigned assessors for this delegation. Filter by date.
     *
     * @param int $filter_start_date
     * @param int $filter_end_date
     * @param bool $fetch_assessors
     * @return array
     */
    public function getDelegationTargetsAndAssessors ($filter_start_date = null, $filter_end_date = null, $fetch_assessors = true) {
        global $db;
        $all_expanded = array();

        // Fetch a list of all possible targets
        $query = "SELECT    a.`target_type`, a.`target_scope`, a.`target_role`, a.`target_id`
                  FROM      `cbl_assessment_distribution_targets` AS a
                  WHERE     a.`adistribution_id` = {$this->getDistributionID()}
                  GROUP BY  a.`target_id`";
        $all_targets = $db->GetAll($query);
        if ($all_targets) {
            foreach ($all_targets as $atarget) {

                // Expand the data; get all cohort and course audience and block members. Add an entry into our return data for each one.
                $expanded = $this->expandTargetData($atarget["target_id"], $atarget["target_type"], $atarget["target_scope"], $filter_start_date, $filter_end_date);
                $to_merge = array();

                // Clear off results that returned with no targets due to the date filter.
                foreach ($expanded as $item) {
                    if (!$item["no_targets"]) {
                        $to_merge[] = $item;
                    }
                }

                // The expanded targets array could be 1 or more, so add them all to our return array
                $all_expanded = array_merge($all_expanded, $to_merge);
            }
        }

        // Optionally, add the assessors for each target
        if ($fetch_assessors) {
            if (!empty($all_expanded)) {
                foreach ($all_expanded as $i => $ftarget) {
                    $all_expanded[$i]["assessors"] = $this->expandAssessorDataForTarget($ftarget);
                    if (!empty($all_expanded[$i]["assessors"])) {
                        // Compare the assessors and mark any duplicates assignments.
                        foreach ($all_expanded[$i]["assessors"] as $i_s => $assessor_search) {
                            foreach ($all_expanded[$i]["assessors"] as $i_f => $assessor_find) {
                                if ($assessor_find["addelegation_id"] == $assessor_search["addelegation_id"] &&
                                    $assessor_find["dassessment_id"] == $assessor_search["dassessment_id"] &&
                                    $assessor_find["target_type"] == $assessor_search["target_type"] &&
                                    $assessor_find["target_value"] == $assessor_search["target_value"] &&
                                    $assessor_find["assessor_type"] == $assessor_search["assessor_type"] &&
                                    $assessor_find["assessor_value"] == $assessor_search["assessor_value"] &&
                                    $assessor_find["addassignment_id"] != $assessor_search["addassignment_id"]
                                ) {
                                    // This is a duplicate assignment.
                                    $all_expanded[$i]["assessors"][$i_s]["is_duplicate"] = true;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $all_expanded;
    }

    /**
     * Fetch target data on the single target level. For every ID in the target_ids array, fetch the single target data.
     *
     * @param array $target_ids
     * @return array
     */
    public function getDelegationTargetsByIDs ($target_ids) {
        $delegation_targets = array();

        /* The target ids array is structured as such:
         * [target id] => Array
         *          (
         *             [target_id] => integer
         *             [type] => string (schedule_id/proxy_id/group_id/external_hash)
         *             [scope] => string (self/all_learners/internal/external)
         *          )
         */

        // Sanitize input IDs
        if (is_array($target_ids) && !empty($target_ids)) {
            $sanitized = array();
            foreach ($target_ids as $target_id => $target_data) {
                $clean_id = clean_input($target_id, array("int"));
                if ($clean_id) {
                    $sanitized[$clean_id] = $target_data;
                }
            }
        }

        // Fetch the target users (or entity)
        foreach ($sanitized as $target_id => $target_data) {
            $t_delegation_targets = $this->expandSingleTargetData($target_id, $target_data["type"], $target_data["scope"]);
            $delegation_targets = array_merge($delegation_targets, array($t_delegation_targets));
        }

        return $delegation_targets;
    }

    /**
     * Given targets and assessors data, iterate through them and compare them against current assigned assessments.
     *
     * See buildTargetsAndAssessorsArray() for detailed description of the input arrays.
     *
     * @param array $targets
     * @param array $assessors
     * @return array
     */
    public function findDuplicateDelegatedAssessments ($targets, $assessors) {
        $possible_assessments = $this->buildTargetsAndAssessorsArray($targets, $assessors);
        $assignments = Models_Assessments_Distribution_DelegationAssignment::fetchAllByDelegationID($this->getDelegationID());
        $duplicates = array();

        if (!empty($assignments)) {
            // Check if the possible assessments already exist
            foreach ($possible_assessments as $pa) {
                $target_id = $pa["target"]["target_id"];
                $target_type = $pa["target"]["type"];
                $assessor_id = $pa["assessor"]["assessor_value"];
                $assessor_type = $pa["assessor"]["assessor_type"];

                // Find these combos in the existing data
                foreach ($assignments as $ca) {
                    if ($ca->getTargetValue() == $target_id && $ca->getTargetType() == $target_type &&
                        $ca->getAssessorValue() == $assessor_id && $ca->getAssessorType() == $assessor_type) {
                        $duplicates[] = array(
                            "target" => array(
                                "target_type" => $target_type,
                                "target_value" => $target_id
                            ),
                            "assessor" => array(
                                "assessor_value" => $assessor_id,
                                "assessor_type" => $assessor_type
                            )
                        );
                    }
                }
            }
        }
        return $duplicates;
    }

    /**
     * For this distribution, fetch the possible assessors.
     *
     * @return array
     */
    public function getPossibleAssessors () {
        $assessors = Models_Assessments_Distribution_Assessor::getAssessmentAssessors($this->getDistributionID());
        return $assessors;
    }

    /**
     * Create a string containing the list of block names for a parent schedule record. If no schedule record is given, simply return the distribution name.
     *
     * e.g. "Schedule Name - Block 1, Block 2" or "Date Range" when not a schedule.
     *
     * @param $start_date
     * @param $end_date
     * @param Models_Schedule $schedule_record
     * @param bool $include_schedule_name
     * @return bool|string
     */
    public function getConcatenatedBlockOrDateString ($start_date, $end_date, $schedule_record = null, $include_schedule_name = true) {
        global $translate;
        $schedule_string = "";
        $child_schedules = null;
        $distribution = Models_Assessments_Distribution::fetchRowByID($this->getDistributionID());
        if ($distribution) {
            if (!$schedule_record) { // No specific schedule was specified, so we try and find it.
                $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($this->getDistributionID());
                if ($distribution_schedule) {
                    $schedule_record = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID());
                }
            }
            if ($schedule_record) {
                $schedule_string = parent::getConcatenatedBlockString(null, $schedule_record, $start_date, $end_date, $distribution->getOrganisationID(), " - ", ", ", $include_schedule_name);
            }

            // Not a schedule, so it's a date range.
            if (!$schedule_string) {
                $schedule_string = $translate->_("Date Range");
            }
        }
        return $schedule_string;
    }

    /**
     * Given the descriptive targets and assessors arrays, create the assessment tasks.
     * For each assessor in the pairings, create one distirbution assessor record. For each target, create
     * a distribution assessment target record, and link it to the assessor record.
     *
     * @param int $delegator_id
     * @param array $targets
     * @param array $assessors
     * @return bool
     */
    public function createDelegatedAssessments ($delegator_id, $targets, $assessors) {

        if (empty($targets) || empty($assessors)) {
            application_log("error", "createDelegatedAssessments: No targets and/or assessors specified (distribution:'{$this->adistribution_id}' delegator:'$delegator_id')");
            return false;
        }

        $db_errors = 0;
        $creation_status = false;

        $distribution = Models_Assessments_Distribution::fetchRowByID($this->getDistributionID());

        // Combine the targets and assessors into an array, also fetch the blocks each target is assigned to.
        $possible_assessments = $this->buildTargetsAndAssessorsArray($targets, $assessors, true);

        // Group them by assessor, if applicable
        $use_block_data = true;
        $unsorted_assessments = array();
        foreach ($possible_assessments as $pa) {
            $assessor_id = "{$pa["assessor"]["assessor_type"]}-{$pa["assessor"]["assessor_value"]}";
            $unsorted_assessments[$assessor_id][] = $pa["target"];
            if (empty($pa["target"]["rotation_blocks"])) {
                $use_block_data = false;
            }
        }

        $schedule_id = null;
        $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($this->getDistributionID());
        if ($distribution_schedule && $use_block_data) {
            // For a rotation schedule, if the data is available, we create assessment tasks grouped by block
            $sorted_assessments = array();
            foreach ($unsorted_assessments as $i => $assessment) {
                $sorted_assessments[$i] = $this->groupAssessmentsByBlock($assessment);
            }

            $repeat = $distribution_schedule->getScheduleType() == "repeat" ? true : false;
            // Create the assessments tasks based on the block-sorted array
            foreach ($sorted_assessments as $i => $rstargets) {
                $assessor_details = explode("-", $i);
                $assessor_type = $assessor_details[0];
                $assessor_value = $assessor_details[1];
                $creation_status = $this->createDelegatedAssessmentTaskForRotationSchedule($distribution, $delegator_id, $assessor_type, $assessor_value, $rstargets, $repeat);
                if (!$creation_status) {
                    $db_errors++;
                }
            }
        } else {

            // For Date range, we just create the dassessment records one for each assessor (instead of one for each block group)
            foreach ($unsorted_assessments as $i => $date_targets) {
                $assessor_details = explode("-", $i);
                $assessor_type = $assessor_details[0];
                $assessor_value = $assessor_details[1];

                $creation_status = $this->createDelegatedAssessmentTaskForDateRange($distribution, $delegator_id, $assessor_type, $assessor_value, $date_targets);
                if (!$creation_status) {
                    $db_errors++;
                }
            }
        }

        if ($db_errors) {
            application_log("error", "createDelegatedAssessments: Database errors encountered when creating assessments via delegation (distribution:'{$this->adistribution_id}' delegator:'$delegator_id')");
            return false;
        } else {
            $this->notificationQueueSendAll();
        }

        return $creation_status;
    }

    /**
     * Removes a delegated assessor, along with the targets and distribution assessment task if necessary.
     *
     * @param $deleted_by
     * @param $addassignment_id
     * @param $assessment_id
     * @param $target_type
     * @param $target_id
     * @param $reason_id
     * @param $reason_text
     * @param $notify
     * @return bool
     */
    public function removeDelegatedAssessor ($deleted_by, $addassignment_id, $assessment_id, $target_type, $target_id, $reason_id, $reason_text, $notify = true) {
        $success = false;
        $db_errors = 0;

        $assigned = Models_Assessments_Distribution_DelegationAssignment::fetchRowByID($addassignment_id);
        if ($assigned) {
            if ($notify) {
                $this->notificationQueueAddAssessmentRemoval($assigned->getID(), $assessment_id, $assigned->getAssessorValue(), $assigned->getAssessorType(), null);
            }

            // Mark as deleted
            $assigned->setDeletedDate();
            $assigned->setDeletedReason($reason_text);
            $assigned->setDeletedReasonID($reason_id);
            $assigned->setUpdatedBy($deleted_by);
            $assigned->setUpdatedDate(time());
            $success = $assigned->update();
            if (!$success) {
                $db_errors++;
            }
        }

        // After updating, check the consistency of the distribution assessments + targets tables
        if ($success) {

            // Mark the related targets as deleted
            $dassessment_target = Models_Assessments_AssessmentTarget::fetchRowByDAssessmentIDTargetTypeTargetValue($assessment_id, $target_type, $target_id);
            if (!empty($dassessment_target)) {
                $dassessment_target->setDeletedDate(time());
                $dassessment_target->setUpdatedDate(time());
                $dassessment_target->setUpdatedBy($deleted_by);
                $update_success = $dassessment_target->update();
                if (!$update_success) {
                    $db_errors++;
                    application_log("error", "Unable to update assessment target record as deleted (id = '{$dassessment_target->getID()}', type = '$target_type', target_id = '$target_id')");
                }
            }

            // Then mark the dassessment as deleted if there are no delegation assignments left.
            $dassessment_count = Models_Assessments_Distribution_DelegationAssignment::getCountByAssessmentID($assessment_id);
            if ($dassessment_count === 0) {
                // mark the dassessment task as deleted
                $dassessment = Models_Assessments_Assessor::fetchRowByID($assessment_id);
                if ($dassessment) {
                    $dassessment->setDeletedDate(time());
                    $dassessment->setUpdatedDate(time());
                    $dassessment->setUpdatedBy($deleted_by);
                    $update_success = $dassessment->update();
                    if (!$update_success) {
                        $db_errors++;
                        application_log("error", "Unable to update assessment task record as deleted (id dassessment_id = {$assessment_id})");
                    }
                }

                // Mark the progress records (if any) as deleted
                $progress_records = Models_Assessments_Progress::fetchAllByDassessmentID($dassessment->getID());
                if ($progress_records && !empty($progress_records)) {
                    foreach ($progress_records as $progress_record) {
                        $progress_record->setDeletedDate(time());
                        $progress_record->setUpdatedDate(time());
                        $progress_record->setUpdatedBy($deleted_by);
                        if (!$progress_record->update()) {
                            $db_errors++;
                            application_log("error", "Unable to update assessment progress record as deleted (id = {$progress_record->getID()})");
                        }
                    }
                }
            }
        }

        if ($db_errors) {
            application_log("error", "Database errors encountered when removing records delegation assignment: $addassignment_id, assessment: $assessment_id (distribution:'{$this->adistribution_id}')");
        }

        if (!$db_errors && $success && $notify) {
            $this->notificationQueueSendAll();
        }
        return $success;
    }

    /**
     * Delete a delegated task, clearing the dassessment records and notifying users.
     *
     * This method is a wrapper for removeDelegatedAssessor(), the difference being that it sends out a different notification email.
     * This method is used in the assessments API, the different notification email being one that references the deleted reason specified from the progress page.
     *
     * @param int $user_id
     * @param int $addassignment_id
     * @param string $target_type
     * @param int $target_id
     * @param int $deleted_reason_id
     * @param string $deletion_note
     * @return bool
     */
    public function deleteDelegatedTaskAndChildren($user_id, $addassignment_id, $target_type, $target_id, $deleted_reason_id, $deletion_note) {
        $deleted = false;

        // Find the delegation assignment and mark it deleted.
        $delegation_assignment = Models_Assessments_Distribution_DelegationAssignment::fetchRowByID($addassignment_id);
        if ($delegation_assignment) {
            $dassessment = Models_Assessments_Assessor::fetchRowByID($delegation_assignment->getDassessmentID());
            if ($dassessment) {
                $deleted = $this->removeDelegatedAssessor($user_id, $addassignment_id, $dassessment->getID(), $target_type, $target_id, $deleted_reason_id, $deletion_note, false);

                // Notify the user of the deletion
                $this->notificationQueueAddAssessmentDeletion($addassignment_id, $dassessment->getID(), $dassessment->getAssessorValue(), $dassessment->getAssessorType());
            }
        }

        // Iterate through our notifications queue
        if ($deleted) {
            $this->notificationQueueSendAll();
        }
        return $deleted;
    }

    /**
     * Given the descriptive targets and assessors arrays, create an array containing the target and assessor pairs (and some metadata).
     *
     * @param $targets
     * @param $assessors
     * @param bool $check_for_duplicates
     * @param bool $group_by_target
     * @return array
     */
    public function getTargetAssessorCombinations ($targets, $assessors, $check_for_duplicates = false, $group_by_target = false) {
        $possible_assessments = $this->buildTargetsAndAssessorsArray($targets, $assessors);

        $duplicates = array();
        if ($check_for_duplicates) {
            $duplicates = $this->findDuplicateDelegatedAssessments($targets, $assessors);
        }

        // Build the flattened target+assessor data, marking any duplicates (if there are any)
        foreach ($possible_assessments as $i => $pa) {
            $possible_assessments[$i]["target"] = array_merge($pa["target"], $this->expandSingleTargetData($pa["target"]["target_id"], $pa["target"]["type"], $pa["target"]["scope"]));
            $possible_assessments[$i]["assessor"] = array_merge($pa["assessor"], $this->expandAssessorData($pa["assessor"]["assessor_value"], $pa["assessor"]["assessor_type"]));
            $possible_assessments[$i]["meta"]["target_is_person"] = $possible_assessments[$i]["target"]["use_members"];
            $possible_assessments[$i]["meta"]["is_duplicate"] = 0;

            foreach ($duplicates as $dupe) {
                if ($dupe["target"]["target_value"] == $pa["target"]["target_id"] &&
                    $dupe["target"]["target_type"] == $pa["target"]["type"] &&
                    $dupe["assessor"]["assessor_value"] == $pa["assessor"]["assessor_value"] &&
                    $dupe["assessor"]["assessor_type"] == $pa["assessor"]["assessor_type"]) {
                    $possible_assessments[$i]["meta"]["is_duplicate"] = 1;
                }
            }
        }

        // If specified, rearrange the data, group by targets. Note that duplicate information isn't retained or reliable at this point due to the grouping.
        $grouped = array();
        if ($group_by_target) {
            foreach ($possible_assessments as $pa) {
                $target_id = $pa["target"]["target_id"];
                if (!isset($grouped[$target_id])) {
                    $grouped[$target_id]["target"] = $pa["target"];
                    $grouped[$target_id]["meta"] = $pa["meta"];
                }
                $grouped[$target_id]["assessors"][] = $pa["assessor"];
            }
        }

        if ($group_by_target) {
            return $grouped;
        }
        return $possible_assessments;
    }

    /**
     * Fetch the completed flag and text for a distribution's delegation tracking record.
     *
     * @return array
     */
    public function getDelegationStatus () {
        $status = array("completed" => 0, "completed_reason" => null);
        $delegation = Models_Assessments_Distribution_Delegation::fetchRowByID($this->getDelegationID());
        if ($delegation) {
            $status["completed"] = $delegation->getCompletedDate();
            $status["completed_reason"] = $delegation->getCompletedReason();
        }
        return $status;
    }

    /**
     * Mark a distribution's delegation tracking record as completed.
     *
     * @param int $completed_by
     * @param string $completed_text
     * @return bool
     */
    public function completeDelegation ($completed_by, $completed_text = null ) {
        $completed_text = clean_input($completed_text, array("notags", "trim"));
        $delegation = Models_Assessments_Distribution_Delegation::fetchRowByID($this->getDelegationID());
        if ($delegation) {
            $delegation->setComplete($completed_text, time(), $completed_by, time(), $completed_by);
            return $delegation->update();
        }
        return false;
    }

    /**
     * For this distribution, find the summary of the delegation as a whole (e.g., how many blocks are associated with it, all of the possible assessors, etc)
     */
    public function getDelegationSummary() {
        global $translate;

        $summary = array();
        $summary["creator_name"] = "";
        $summary["creator_role"] = ""; // Not supported by Models_User currently.
        $summary["created_date_string"] = "";
        $summary["date_range_string"] = "";
        $summary["cutoff_date_string"] = "";
        $summary["blocks_string"] = "";
        $summary["delegator_name"] = "";
        $summary["delegator_role"] = "";
        $summary["blocks_string"] = "";

        $start_date = $end_date = 0;
        $schedule = false;
        $distribution = Models_Assessments_Distribution::fetchRowByID($this->getDistributionID());
        if ($distribution) {
            if ($distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($distribution->getID())) {
                $schedule = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID());
            }
            if ($schedule) {
                $rotations = $this->fetchRotations($schedule->getScheduleID());
                if (empty($rotations)) { // This will be empty if the scheduled learners were removed from the schedule
                } else {
                    $date_range = $this->findStartAndEndDateRange($rotations);
                    $start_date = $date_range["earliest_date"];
                    $end_date = $date_range["latest_date"];
                    $block_or_date_string = $this->getConcatenatedBlockOrDateString($start_date, $end_date, $schedule, false);
                    if ($block_or_date_string) {
                        $summary["blocks_string"] = "{$translate->_("Encompassing")}: $block_or_date_string";
                    }
                }
            } else {
                $start_date = $distribution->getStartDate();
                $end_date = $distribution->getEndDate();
            }

            if ($start_date && $end_date) {
                $date_range_string = sprintf("%s %s %s", strftime("%Y-%m-%d", $start_date), $translate->_("to"), strftime("%Y-%m-%d", $end_date));
                $summary["date_range_string"] = "{$translate->_("Dates")}: $date_range_string";
            }

            if ($distribution->getReleaseDate()) {
                $cutoff_string = strftime("%Y-%m-%d", $distribution->getReleaseDate());
                $summary["cutoff_date_string"]  = "{$translate->_("Cut-off date")}: $cutoff_string";
            }

            if ($created_by = Models_User::fetchRowByID($distribution->getCreatedBy())) {
                $summary["creator_name"] = $created_by->getFullname(false);
            }

            $summary["created_date_string"] = strftime("%Y-%m-%d", $distribution->getCreatedDate());

        }
        return $summary;
    }

    /**
     * Create array usable by buildTargetsAndAssessorsArray for the given list of Model_Assessments_Distribution_Target objects.
     *
     * @param array  $target_list
     * @return array
     */
    public function createTargetsArrayFromModel(&$target_list) {
        $targets = array();
        foreach ($target_list as $target) {
            $flat_target = array();
            $flat_target["target_id"] = $target->getTargetID();
            $flat_target["scope"] = $target->getTargetScope();
            $flat_target["type"] = $target->getTargetType();
            $targets[] = $flat_target;
        }
        return $targets;
    }

    /**
     * Create array usable by buildTargetsAndAssessorsArray for the given list of Model_Assessments_Distribution_Assessor objects.
     *
     * @param array $assessor_list
     * @return array
     */
    public function createAssessorsArrayFromModel(&$assessor_list) {
        $assessors = array();
        foreach ($assessor_list as $assessor) {
            $flat_assessor = array();
            $flat_assessor["assessor_value"] = $assessor->getAssessorValue();
            $flat_assessor["assessor_type"] = $assessor->getAssessorType();
            $assessors[] = $flat_assessor;
        }
        return $assessors;
    }

    /**
     * Given an array of Models_Assessment_Distribution_Delegation objects, fetch all the targets and assessors for all the delegations.
     *
     * @param array $delegations
     * @return array
     */
    public function getAssignmentsSummary($delegations) {
        $old_id = $this->getDelegationID(); // preserve the previous setting
        $combos = array();
        foreach ($delegations as $d) {
            $this->setDelegationID($d->getID()); // Set the delegation ID so that getDelegationTargetsAndAssessors is able to fetch related assigned assessors.
            $tar_and_assr = $this->getDelegationTargetsAndAssessors($d->getStartDate(), $d->getEndDate(), true);
            if (!$this->hasNoTargets($tar_and_assr)) {
                $combos[$d->getID()] = $tar_and_assr;
            }
        }
        $this->setDelegationID($old_id); // restore previous
        return $combos;
    }

    /**
     * Search for "no_targets" being true in the array given.
     *
     * @param array $delegations
     * @return bool
     */
    public function hasNoTargets($delegations) {
        $no_results = false;
        foreach ($delegations as $d) {
            if (isset($d["no_targets"])) {
                if ($d["no_targets"]) {
                    $no_results = true;
                }
            }
        }
        return $no_results;
    }

    /**
     * For the given delegation assignment data, add the assessment record data, and relevant user data.
     *
     * @param array $target
     * @param array $assignment
     * @return array
     */
    public function addExpandedDelegationData($target, $assignment) {
        global $translate;

        $assignment["target_email"] = "";
        $assignment["target_number"] = "";
        $assignment["target_name"] = $target["entity_name"];
        if ($target["use_members"]) {
            $assignment["target_name"] = $target["member_fullname"];
            $assignment["target_email"] = $target["member_email"];
            $assignment["target_number"] = $target["member_number"];
        }
        $assignment["assessor_fullname"] = "{$assignment["firstname"]} {$assignment["lastname"]}";
        $assignment["assessor_email"] = "";
        $assignment["assessor_number"] = "";
        $user = $this->getUserByType($assignment["assessor_value"], $assignment["assessor_type"]);
        if ($user) {
            $assignment["assessor_email"] = $user->getEmail();
            if ($assignment["assessor_type"] != "internal") {
                $assignment["assessor_number"] = $translate->_("External");
            } else {
                $assignment["assessor_number"] = $user->getNumber();
            }
        }
        $assignment["delegated_date"] = $assignment["created_date"];
        $assignment["delegated_date_string"] = strftime("%Y-%m-%d", $assignment["created_date"]);
        $assignment_status_string = $translate->_("Not Started");
        $progress_records = Models_Assessments_Progress::fetchAllByDassessmentID($assignment["dassessment_id"], null, null, "DESC");
        if ($progress_records && is_array($progress_records) && !empty($progress_records)) {
            foreach ($progress_records as $progress_record) {
                if ($progress_record) {
                    if ($progress_record->getAssessorValue() == $assignment["assessor_value"] && $progress_record->getTargetRecordID() == $assignment["target_value"]) {
                        if ($progress_record->getProgressValue() == "inprogress") {
                            $assignment_status_string = sprintf($translate->_("In Progress (started %s)"), strftime("%Y-%m-%d", $progress_record->getCreatedDate()));
                        } else if ($progress_record->getProgressValue() == "complete") {
                            $assignment_status_string = $translate->_("Complete");
                            $assignment_status_string .= strftime(" (%Y-%m-%d)", $progress_record->getUpdatedDate());
                        }
                    }
                }
            }
        }
        $assignment["assessment_status_string"] = $assignment_status_string;
        return $assignment;
    }

    /**
     * Given an array of target or assessor data, create a standardized version of it for consumption in a view.
     *
     * @param $user_data
     * @return array
     */
    public function standardizeUserBadgeData($user_data) {
        global $translate;
        $standardized = array();

        if (isset($user_data["use_members"]) && !$user_data["use_members"]) {
            // This is a target entity array.
            $standardized["id"] = $user_data["id"];
            $standardized["type"] = $user_data["type"];
            $standardized["fullname"] = $user_data["entity_name"];
            $standardized["email"] = "";
            $standardized["number"] = "";
        } else {
            if (isset($user_data["entity_name"])) {
                // The only time an entity is specified is if the data supplied is a target data array.
                $standardized["id"] = $user_data["member_id"];
                $standardized["type"] = "internal"; // Note: we currently don't support external targets.
                $standardized["fullname"] = $user_data["member_fullname"];
                $standardized["email"] = $user_data["member_email"];
                $standardized["number"] = $user_data["member_number"];
            } else {
                // Assume it's an assessor array
                $standardized["id"] = $user_data["assessor_value"];
                $standardized["type"] = $user_data["assessor_type"];
                $standardized["fullname"] = $user_data["name"];
                $standardized["email"] = $user_data["email"];
                if ($user_data["assessor_type"] == "internal") {
                    $standardized["number"] = $user_data["number"];
                } else {
                    $standardized["number"] = $translate->_("External");
                }
            }
        }
        return $standardized;
    }

    /**
     * Determine all of the delegation tasks for a distribution. This function clears the existing list before creating a new one.
     */
    public function buildDelegationTaskList() {

        $distribution = Models_Assessments_Distribution::fetchRowByID($this->getDistributionID());
        if (!$distribution) {
            // Invalid distribution ID.
            return false;
        }

        $delegator = Models_Assessments_Distribution_Delegator::fetchRowByDistributionID($this->getDistributionID());
        if (!$delegator) {
            // No delegator for this distribution.
            return false;
        }

        // Clear task list so we can build a new one.
        $this->clearTaskList();

        // Set the default array for this distribution
        $this->task_list[$this->getDistributionID()] = array();

        $release_date = (is_null($distribution->getReleaseDate()) ? 0 : (int) $distribution->getReleaseDate());
        $all_possible_assessors = $this->getPossibleAssessors();

        $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($this->getDistributionID());
        if ($distribution_schedule) {

            // Rotation schedule based distribution
            $schedule = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID());
            if (!$schedule) {
                // There's a problem with the data; a distribution schedule is specified, but no schedule actually exists for it.
                application_log("error", "Tried to find schedule id ({$distribution_schedule->getScheduleID()}) but failed. Distribution with ID ({$this->getDistributionID()}) has malformed schedule association data.");
                return false;
            }
            switch ($distribution_schedule->getScheduleType()) {

                case "rotation" :
                    $distribution_targets = Models_Assessments_Distribution_Target::fetchAllByDistributionID($distribution->getID());
                    if ($distribution_targets) {
                        foreach ($distribution_targets as $distribution_target) {
                            $rotations = $this->fetchRotations($schedule->getID(), $distribution_target->getTargetScope());
                            if ($rotations) {
                                $rotation_dates = $this->getRotationDates($rotations, $distribution->getOrganisationID());
                                if ($rotation_dates["unique_rotation_dates"] && !empty($rotation_dates["unique_rotation_dates"])) {
                                    foreach ($rotation_dates["unique_rotation_dates"] as $unique_rotation_date) {
                                        $delivery_date = $this->calculateDateByOffset($distribution_schedule->getDeliveryPeriod(), $distribution_schedule->getPeriodOffset(), $unique_rotation_date[0], $unique_rotation_date[1]);
                                        $targets = $this->getDelegationTargetsAndAssessors($unique_rotation_date[0], $unique_rotation_date[1], false);
                                        if (!empty($targets)) {
                                            $this->addToTaskList(
                                                $this->getDistributionID(),
                                                $delivery_date,
                                                $release_date,
                                                $unique_rotation_date[0],
                                                $unique_rotation_date[1],
                                                $targets,
                                                $all_possible_assessors,
                                                "delegation",
                                                "dates",
                                                null,
                                                $delegator->getDelegatorID(),
                                                true,
                                                $distribution_schedule->getScheduleType(),
                                                $distribution_schedule->getDeliveryPeriod(),
                                                $distribution_schedule->getPeriodOffset()
                                            );
                                        }
                                    }
                                }
                            }
                        }
                    }
                    break;

                case "block" :
                    $blocks = array();
                    if ($schedule->getScheduleType() == "rotation_stream") {
                        $blocks = Models_Schedule::fetchAllByParentID($schedule->getID());
                    } else if ($schedule->getScheduleType() == "rotation_block") {
                        $blocks[] = $schedule;
                    }
                    foreach ($blocks as $block) {
                        $delivery_date = $this->calculateDateByOffset($distribution_schedule->getDeliveryPeriod(), $distribution_schedule->getPeriodOffset(), $block->getStartDate(), $block->getEndDate());
                        $learner_blocks = $this->fetchLearnerBlocks($block->getID());
                        if (!empty($learner_blocks)) {
                            $targets = $this->getDelegationTargetsAndAssessors($block->getStartDate(), $block->getEndDate(), false);
                            if (!empty($targets)) {
                                $this->addToTaskList(
                                    $this->getDistributionID(),
                                    $delivery_date,
                                    $release_date,
                                    $block->getStartDate(),
                                    $block->getEndDate(),
                                    $targets,
                                    $all_possible_assessors,
                                    "delegation",
                                    "dates",
                                    null,
                                    $delegator->getDelegatorID(),
                                    true,
                                    $distribution_schedule->getScheduleType(),
                                    $distribution_schedule->getDeliveryPeriod(),
                                    $distribution_schedule->getPeriodOffset()
                                );
                            }
                        }
                    }
                    break;

                case "repeat" :
                    $distribution_targets = Models_Assessments_Distribution_Target::fetchAllByDistributionID($distribution->getID());
                    if ($distribution_targets) {
                        foreach ($distribution_targets as $distribution_target) {
                            // If type = "schedule_id" and scope = "self" then the target is the rotation entity, not learners, so we can't fetch rotation dates.
                            if ($distribution_target->getTargetType() == "schedule_id" && $distribution_target->getTargetScope() != "self") {
                                $rotations = $this->fetchRotations($schedule->getID(), $distribution_target->getTargetScope());
                                if ($rotations) {
                                    $rotation_dates = $this->getRotationDates($rotations, $distribution->getOrganisationID());
                                    if ($rotation_dates["unique_rotation_dates"]) {
                                        $full_date_range = $this->findStartAndEndDateRange($rotation_dates["unique_rotation_dates"], false);
                                        foreach ($rotation_dates["unique_rotation_dates"] as $unique_rotation_date) {
                                            $delivery_date = $this->calculateDateByFrequency($distribution_schedule->getFrequency(), $unique_rotation_date[0]);
                                            while ($delivery_date <= $full_date_range["latest_date"] && $delivery_date <= $unique_rotation_date[1]) {
                                                $targets = $this->getDelegationTargetsAndAssessors($unique_rotation_date[0], $unique_rotation_date[1], false);
                                                if (!empty($targets)) {
                                                    $this->addToTaskList(
                                                        $this->getDistributionID(),
                                                        $delivery_date,
                                                        $release_date,
                                                        $unique_rotation_date[0],
                                                        $unique_rotation_date[1],
                                                        $targets,
                                                        $all_possible_assessors,
                                                        "delegation",
                                                        "dates",
                                                        null,
                                                        $delegator->getDelegatorID(),
                                                        true,
                                                        $distribution_schedule->getScheduleType(),
                                                        $distribution_schedule->getDeliveryPeriod(),
                                                        $distribution_schedule->getPeriodOffset()
                                                    );
                                                }
                                                $delivery_date += ($distribution_schedule->getFrequency() * 86400);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    break;

            }
        } else {

            // Date range distribution
            $targets = $this->getDelegationTargetsAndAssessors($distribution->getStartDate(), $distribution->getEndDate(), false);
            if ($targets) {
                $this->addToTaskList(
                    $this->getDistributionID(),
                    $distribution->getDeliveryDate(),
                    $release_date,
                    $distribution->getStartDate(),
                    $distribution->getEndDate(),
                    $targets,
                    $all_possible_assessors,
                    "delegation",
                    "dates",
                    null,
                    $delegator->getDelegatorID(),
                    true
                );
            }
        }
        return $this->getTaskList();
    }

    /**
     * Find all of the completed tasks, result set indexed by primary key (addelegation_id)
     */
    public function fetchCompletedTasks() {
        $reindexed = array();
        $all_completed = Models_Assessments_Distribution_Delegation::fetchAllCompletedByDistributionID($this->adistribution_id);
        foreach ($all_completed as $completed) {
            $reindexed[$completed->getID()] = $completed;
        }
        return $reindexed;
    }

    /**
     * From the list of all delegation tasks (potential or actual) for this distribution build a list of the ones that shouldn't exist (that are upcoming, ones that haven't been created by cron).
     *
     * If specified, this function will recreate the delegation task list, otherwise, it assumes it already exists in a populated state.
     *
     * @param $recreate_task_list
     * @return array
     */
    public function determineUpcomingDelegations($recreate_task_list = false) {
        $upcoming = array();

        if ($recreate_task_list) {
            $full_task_list = $this->buildDelegationTaskList();
        } else {
            $full_task_list = $this->getTaskList();
        }

        $distribution = Models_Assessments_Distribution::fetchRowByID($this->getDistributionID());
        $release_date = (is_null($distribution->getReleaseDate()) ? 0 : (int) $distribution->getReleaseDate());
        if (isset($full_task_list[$this->getDistributionID()])) {
            foreach ($full_task_list[$this->getDistributionID()] as $task) {
                if ($task["meta"]["should_exist"] == false && !$task["meta"]["deleted_date"]) {
                    if ($release_date <= $task["meta"]["delivery_date"]) {
                        $upcoming[] = array(
                            "start_date" => $task["meta"]["start_date"],
                            "end_date" => $task["meta"]["end_date"],
                            "delivery_date" => $task["meta"]["delivery_date"],
                            "targets" => $task["targets"],
                            "assessors" => $task["assessors"]
                        );
                    }
                }
            }
        }
        return $upcoming;
    }

    //--- Private functions ---//

    /**
     * Create a new Distribtion Assessment record (top-level record). Returns false on record creation failure, otherwise returns the new distribution assessment object.
     *
     * @param string $assessor_type
     * @param int $assessor_value
     * @param int $start_date
     * @param int $end_date
     * @param int $delivery_date
     * @param int $min_sub
     * @param int $max_sub
     * @param int $delegator_id
     * @param bool $set_rotation_date
     * @return bool|Models_Assessments_Assessor
     */
    private function createNewDAssessmentRecord ($assessor_type, $assessor_value, $start_date, $end_date, $delivery_date, $min_sub, $max_sub, $delegator_id, $set_rotation_date = false) {

        $construction = array(
            "adistribution_id" => $this->getDistributionID(),
            "assessor_type" => $assessor_type,
            "assessor_value" => $assessor_value,
            "published" => 1,
            "min_submittable" => $min_sub,
            "max_submittable" => $max_sub,
            "start_date" => $start_date,
            "end_date" => $end_date,
            "delivery_date" => $delivery_date,
            "created_date" => time(),
            "created_by" => $delegator_id,
            "updated_date" => time(),
            "updated_by" => $delegator_id
        );
        if ($assessor_type == "external") {
            $construction["external_hash"] = generate_hash();
        }
        if ($set_rotation_date) {
            $construction["rotation_start_date"] = $start_date;
            $construction["rotation_end_date"] = $end_date;
        }
        $dist_assessment = new Models_Assessments_Assessor($construction);
        return $dist_assessment->insert();
    }

    /**
     * Create a new Distribution Assessment Target record.
     *
     * @param int $dassessment_id
     * @param string $target_type
     * @param int $target_id
     * @param int $delegator_id
     * @return bool
     */
    private function createNewDAssessmentTargetRecord ($dassessment_id, $target_type, $target_id, $delegator_id) {
        $dist_assessment_target = new Models_Assessments_AssessmentTarget(array(
            "adistribution_id" => $this->getDistributionID(),
            "dassessment_id" => $dassessment_id,
            "target_type" => $target_type,
            "target_value" => $target_id,
            "created_date" => time(),
            "created_by" => $delegator_id,
            "updated_date" => time(),
            "updated_by" => $delegator_id
        ));
        return $dist_assessment_target->insert();
    }

    /**
     * Create the assessment assignments joins table.
     *
     * @param int $assessment_id
     * @param int $delegator_id
     * @param string $assessor_type
     * @param int $assessor_value
     * @param string $target_type
     * @param int$target_value
     * @return bool
     */
    private function createNewDDelegationAssignmentRecord ($assessment_id, $delegator_id, $assessor_type, $assessor_value, $target_type, $target_value) {
        $delegation_assignment = new Models_Assessments_Distribution_DelegationAssignment(array(
            "addelegation_id" => $this->getDelegationID(),
            "adistribution_id" => $this->getDistributionID(),
            "dassessment_id" => $assessment_id,
            "delegator_id" => $delegator_id,
            "assessor_type" => $assessor_type,
            "assessor_value" => $assessor_value,
            "target_type" => $target_type,
            "target_value" => $target_value,
            "created_by" => $delegator_id,
            "created_date" => time()
        ));
        return $delegation_assignment->insert();
    }

    /**
     * Fetch an existing distribution assessor record.
     *
     * @param $assessor_type
     * @param $assessor_value
     * @param $start_date
     * @param $end_date
     * @return bool|Models_Base
     */
    private function findExistingDAssessmentRecord($assessor_type, $assessor_value, $start_date, $end_date) {
        return Models_Assessments_Assessor::fetchRowByADistributionIDAssessorTypeAssessorValueStartDateEndDate($this->getDistributionID(), $assessor_type, $assessor_value, $start_date, $end_date);
    }

    /**
     * Create the assessment tasks for all of the specified targets for a rotation schedule for a given assessor.
     *
     * @param Models_Assessments_Distribution $distribution
     * @param int $delegator_id
     * @param string $assessor_type
     * @param int $assessor_value
     * @param array $rotation_blocks
     * @param bool $repeat
     * @return bool
     */
    private function createDelegatedAssessmentTaskForRotationSchedule (&$distribution, $delegator_id, $assessor_type, $assessor_value, $rotation_blocks = array(), $repeat = false) {
        if (empty($rotation_blocks)) {
            application_log("error", "No rotation blocks specified when attempting to created delegated assessment for rotation schedule (distribution:'{$this->adistribution_id}' Delegator: $delegator_id)");
            return false;
        }

        $distribution_delegation = Models_Assessments_Distribution_Delegation::fetchRowByID($this->getDelegationID());
        if (!$distribution_delegation) {
            return false;
        }

        $db_failures = 0;
        if (!empty($rotation_blocks)) {
            $block_start_date = $distribution_delegation->getStartDate();
            $block_end_date = $distribution_delegation->getEndDate();
            foreach ($rotation_blocks as $block_ids_str => $rotation_targets) {
                $target_count = count($rotation_targets);
                $min_sub = $distribution->getMinSubmittable();
                $max_sub = $distribution->getMaxSubmittable();
                if ($distribution->getSubmittableByTarget()) {
                    $min_sub *= $target_count;
                    $max_sub *= $target_count;
                }

                if ($block_start_date && $block_end_date) {
                    // Check if there is a dassessment with this start/end date already. Repeat assessments can have the same set of dates, so create new assessments for those.
                    $existing = $this->findExistingDAssessmentRecord($assessor_type, $assessor_value, $block_start_date, $block_end_date);
                    if (!$repeat && !empty($existing)) {
                        $dist_assessment = $existing;
                    } else {
                        // Create top level assessment record
                        $dist_assessment = $this->createNewDAssessmentRecord($assessor_type, $assessor_value, $block_start_date, $block_end_date, time(), $min_sub, $max_sub, $delegator_id, true);
                        if (!$dist_assessment) {
                            $db_failures++;
                        }
                    }

                    if ($dist_assessment) {

                        // Create the child target and assessor assignment records
                        foreach ($rotation_targets as $rotation_target) {
                            $rot_tar_arr = explode("-", $rotation_target);
                            $target_type = $rot_tar_arr[0];
                            $target_scope = $rot_tar_arr[1];
                            $target_id = $rot_tar_arr[2];

                            $assessment_id = $dist_assessment->getID();

                            // If we found an existing dassessment record, then make sure we're not adding a duplicate target to it.
                            $duplicates = $this->findDuplicateDelegationAssignments($assessment_id, $assessor_type, $assessor_value, $target_type, $target_id);
                            if (!empty($duplicates)) {
                                continue; // skip duplicates
                            }

                            if (!$this->createNewDAssessmentTargetRecord($assessment_id, $target_type, $target_id, $delegator_id)) {
                                $db_failures++;
                            }
                            // Create the delegation assignment record for this delegated assessment
                            if ($assessment_id) {
                                $dassignment = $this->createNewDDelegationAssignmentRecord($assessment_id, $delegator_id, $assessor_type, $assessor_value, $target_type, $target_id);
                                if (!$dassignment) {
                                    $db_failures++;
                                } else {
                                    $this->notificationQueueAddAssessmentCreation($dassignment->getID(), $assessment_id, $assessor_value, $assessor_type, null);
                                }
                            }
                        }
                    }
                } else {
                    $db_failures++;
                }
            }
        }

        if ($db_failures) {
            return false;
        }
        return true;
    }

    /**
     * Create the assessment tasks for all of the specified targets for a date range delegation for a given assessor.
     *
     * @param Models_Assessments_Distribution $distribution
     * @param int $delegator_id
     * @param string $assessor_type
     * @param int $assessor_value
     * @param array $date_targets
     * @return bool
     */
    private function createDelegatedAssessmentTaskForDateRange (&$distribution, $delegator_id, $assessor_type, $assessor_value, $date_targets = array()) {
        if (empty($date_targets)) {
            application_log("error", "No targets specified when attempting to created delegated assessment for date range (distribution:'{$this->adistribution_id}' Delegator: $delegator_id)");
            return false;
        }
        $db_failures = 0;
        if (!empty($date_targets)) {

            $target_count = count($date_targets);
            $min_sub = $distribution->getMinSubmittable();
            $max_sub = $distribution->getMaxSubmittable();
            if ($distribution->getSubmittableByTarget()) {
                $min_sub *= $target_count;
                $max_sub *= $target_count;
            }

            $start_date = $distribution->getStartDate();
            $end_date = $distribution->getEndDate();

            $existing = $this->findExistingDAssessmentRecord($assessor_type, $assessor_value, $start_date, $end_date);
            if (!empty($existing)) {
                $dist_assessment = $existing;
            } else {
                // Create top level assessment record
                $dist_assessment = $this->createNewDAssessmentRecord($assessor_type, $assessor_value, $start_date, $end_date, time(), $min_sub, $max_sub, $delegator_id);
                if (!$dist_assessment) {
                    $db_failures++;
                }
            }
            if ($dist_assessment) {
                // Create child targets and assessor assignment records
                foreach ($date_targets as $i => $date_target) {
                    if (!$this->createNewDAssessmentTargetRecord($dist_assessment->getID(), $date_target["type"], $date_target["target_id"], $delegator_id)) {
                        $db_failures++;
                    }
                    $dassignment = $this->createNewDDelegationAssignmentRecord($dist_assessment->getID(), $delegator_id, $assessor_type, $assessor_value, $date_target["type"], $date_target["target_id"]);
                    if (!$dassignment) {
                        $db_failures++;
                    } else {
                        $this->notificationQueueAddAssessmentCreation($dassignment->getID(), $dist_assessment->getID(), $assessor_value, $assessor_type, null);
                    }
                }
            }
        }

        if ($db_failures) {
            return false;
        }
        return true;
    }

    /**
     * Fetch all assignment records for the given delegation/assessor/target combination. This should return one element if there are no duplicates.
     *
     * @param $dassessment_id
     * @param $assessor_type
     * @param $assessor_value
     * @param $target_type
     * @param $target_value
     * @return array
     */
    private function findDuplicateDelegationAssignments($dassessment_id, $assessor_type, $assessor_value, $target_type, $target_value) {
        return Models_Assessments_Distribution_DelegationAssignment::fetchAllByAssessmentID($dassessment_id, $assessor_type, $assessor_value, $target_type, $target_value);
    }

    /**
     * Fetches assessor data in a standard format (whether internal or external assessor).
     *
     * @param $assessor_id
     * @param $assessor_type
     * @return array
     */
    private function expandAssessorData ($assessor_id, $assessor_type) {
        $assessor_data = array();
        switch ($assessor_type) {
            case "internal":
                $a = Models_User::fetchRowByID($assessor_id);
                if (!empty($a)) {
                    $assessor_data["id"] = $assessor_id;
                    $assessor_data["type"] = $assessor_type;
                    $assessor_data["fullname"] = $a->getFullname(false);
                    $assessor_data["firstname"] = $a->getFirstname();
                    $assessor_data["lastname"] = $a->getLastname();
                    $assessor_data["email"] = $a->getEmail();
                    $assessor_data["number"] = $a->getNumber();
                }
                break;
            case "external":
                $a = Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($assessor_id);
                if (!empty($a)) {
                    $assessor_data["id"] = $assessor_id;
                    $assessor_data["type"] = $assessor_type;
                    $assessor_data["fullname"] = $a->getFirstname() ." ". $a->getLastname();
                    $assessor_data["firstname"] = $a->getFirstname();
                    $assessor_data["lastname"] = $a->getLastname();
                    $assessor_data["email"] = $a->getEmail();
                    $assessor_data["number"] = 0;
                }
                break;
            default:
                break;
        }
        return $assessor_data;
    }

    /**
     * For a given target, fetch the relevant assessor data. The target can be external or internal. This functionality does not apply to entities.
     *
     * @param array $target
     * @return array
     */
    private function expandAssessorDataForTarget ($target) {
        global $db;

        // Assessors can be proxy_id or external_hash only. We query for both, and merge the results.
        $query = "SELECT    a.*, a.`target_value` AS `target_id`,
                            u.`firstname` AS `internal_firstname`, u.`lastname` AS `internal_lastname`,
                            e.`firstname` AS `external_firstname`, e.`lastname` AS `external_lastname`
                  FROM      `cbl_assessment_distribution_delegation_assignments` AS a
                  LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS u ON u.`id` = a.`assessor_value`             AND a.`assessor_type` = 'internal'
                  LEFT JOIN `cbl_external_assessors`        AS e ON e.`eassessor_id` = a.`assessor_value`   AND a.`assessor_type` = 'external'
                  WHERE     a.`addelegation_id` = ?
                  AND       a.`adistribution_id` = ?
                  AND       a.`target_value` = ?
                  AND       a.`deleted_date` IS NULL";

        // target[id] is the entity id, target[member id] is the specific member id. This query encompasses both users and non-user entities, so we
        // use the member ID only if it is available (non-user entities don't have member ids).
        $entity_info = $db->GetAll($query, array($this->getDelegationID(), $this->getDistributionID(), isset($target["member_id"]) ? $target["member_id"] : $target["id"]));

        if (is_array($entity_info) && !empty($entity_info)) {
            // Copy the relevant first/last name to the appropriate field based on assessor type.
            foreach ($entity_info as $i => $entity) {
                $entity_info[$i]["is_duplicate"] = false;
                if ($entity["assessor_type"] == "internal") {
                    $entity_info[$i]["firstname"] = $entity["internal_firstname"];
                    $entity_info[$i]["lastname"] = $entity["internal_lastname"];
                } else if ($entity["assessor_type"] == "external") {
                    $entity_info[$i]["firstname"] = $entity["external_firstname"];
                    $entity_info[$i]["lastname"] = $entity["external_lastname"];
                }
            }
        } else {
            $entity_info = array();
        }
        return $entity_info;
    }

    /**
     * Fetches a data structure used to represent distribution targets and assessors. The returned data is used in delegation UIs for displaying target
     * and assessor associations. This data structure contains the same fields regardless of what the target is; external assessors,
     * users (by proxy_id, from schedules, cohorts or courses), or entities (rotations or courses) all return their data within this construct.
     *
     * @param int $target_id
     * @param string $type
     * @param string $scope
     * @param int $filter_start_date
     * @param int $filter_end_date
     * @return array
     */
    private function expandTargetData ($target_id, $type, $scope, $filter_start_date = NULL, $filter_end_date = NULL) {
        global $db;
        $distribution = Models_Assessments_Distribution::fetchRowByID($this->getDistributionID());
        if (!$distribution) {
            // distribution was deleted, or not found. Either way, no target data to expand.
            return array();
        }

        // Our return variable
        $entity_info = array();
        $entity_info["id"] = $target_id;
        $entity_info["type"] = $type;
        $entity_info["scope"] = $scope;
        $entity_info["entity_name"] = false;
        $entity_info["use_members"] = true;
        $entity_info["no_targets"] = false;

        // The members array will contain 1 or more users; external or internal, but all derived from the entity.
        // In the case where an entity has no members, such as for an assessment of a Rotation or Course, use_members is set false, and the members array is left empty.
        $entity_info["members"] = array();

        // Default member info structure
        $default_member_info = array();
        $default_member_info["member_id"] = 0;
        $default_member_info["member_fullname"] = false;
        $default_member_info["member_firstname"] = false;
        $default_member_info["member_lastname"] = false;
        $default_member_info["member_email"] = false;
        $default_member_info["member_number"] = 0;

        switch ($type) {

            case "proxy_id": // Individual user  // target_scope = self / target_role = faculty, any, or learner

                // Fetch the single user
                $user = Models_User::fetchRowByID($target_id);
                $entity_info["entity_name"] = $user->getFullname(false);

                $p_info = $default_member_info;
                $p_info["member_id"] = $user->getID();
                $p_info["member_fullname"] = $user->getFullname(false);
                $p_info["member_firstname"] = $user->getFirstname();
                $p_info["member_lastname"] = $user->getLastname();
                $p_info["member_email"] = $user->getEmail();
                $p_info["member_number"] = $user->getNumber();

                $entity_info["members"][] = $p_info;
                break;

            case "external_hash": // External assessor, multiple records, either date or rotation based // scope is always self
                $external = Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($target_id);
                $entity_info["entity_name"] = "{$external->getFirstname()} {$external->getLastname()}";

                $ex_info = $default_member_info;
                $ex_info["member_id"] = $external->getID();
                $ex_info["member_fullname"] = "{$external->getFirstname()} {$external->getLastname()}";
                $ex_info["member_firstname"] = $external->getFirstname();
                $ex_info["member_lastname"] = $external->getLastname();
                $ex_info["member_email"] = $external->getEmail();

                $entity_info["members"][] = $ex_info;
                break;

            case "course_id":
                $course = Models_Course::fetchRowByID($target_id);
                switch ($scope) {
                    case "self": // date range->select a course (target is the course entity)
                    case "all_learners": // date range->select grouped learners (target is the course entity)
                        $entity_info["use_members"] = false;
                        $entity_info["entity_name"] = "({$course->getCourseCode()}) {$course->getCourseName()}";
                        break;
                }
                break;

            case "group_id": // date range->select grouped learners, course audience (Cohort)
                // target scope should be "all_learners"

                // We're fetching the group as an entity
                $group = Models_Group::fetchRowByID($target_id);

                $entity_info["entity_name"] = $group->getGroupName();
                $entity_info["use_members"] = false;
                break;

            case "schedule_id": // Rotation schedule related targets
                $schedule = Models_Schedule::fetchRowByID($target_id);
                $entity_info["entity_name"] = $schedule->getTitle();

                if ($scope == "self") {
                    // The target is the rotation entity.
                    $entity_info["use_members"] = false;
                } else {
                    // The target is the learners of the rotation. Fetch all of them in context (e.g., for block, or for all, filtered by on/off service and date ranges).
                    $schedule_cut_off_date = (is_null($distribution->getReleaseDate()) ? 0 : (int) $distribution->getReleaseDate());
                    $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($distribution->getID());

                    if ($distribution_schedule) {
                        if (!$schedule->getScheduleParentID()) {
                            $children = $schedule->fetchAllRecords($filter_start_date, $filter_end_date, $schedule->getID(), $schedule->getCourseID());
                            if (!is_array($children)) {
                                // Schedule changed? Something happened with the data where there are no children, or error. Avoid boolean false.
                                $children = array();
                            }
                        } else {
                            $children = array($schedule);
                        }

                        $AND_schedule_id_in = "";

                        $schedule_ids = array();
                        foreach ($children as $child_schedule) {
                            $schedule_ids[] = $child_schedule->getID();
                        }
                        if (!empty($schedule_ids)) {
                            $schedule_ids_str = implode(",",$schedule_ids);
                            $AND_schedule_id_in = "AND a.`schedule_id` IN($schedule_ids_str)";
                        }

                        $audience_ids = $this->fetchAudienceIDsForDistributionByScheduleTypeAndFilterDate($filter_start_date, $filter_end_date);

                        $AND_audience_ids_in = "";
                        if (!empty($audience_ids)) {
                            $audience_ids_str = implode(", ", $audience_ids);
                            $AND_audience_ids_in = "AND b.`audience_value` IN ($audience_ids_str)";
                        }

                        if ($filter_start_date && $filter_end_date) {
                            $AND_date_filter = "AND (
                                                      (a.`start_date` >= ? AND a.`start_date` <= ?) OR
                                                      (a.`end_date` >= ? AND a.`end_date` <= ?) OR
                                                      (a.`start_date` <= ? AND a.`end_date` >= ?)
                                                    )";
                        } else {
                            $AND_date_filter = "";
                        }

                        if ($scope == "internal_learners") {
                            // on service / target_role = learner
                            $AND_slot_type_filter = "AND c.`slot_type_id` = 1";

                        } else if ($scope == "external_learners") {
                            // Off service / target_role = learner
                            $AND_slot_type_filter = "AND c.`slot_type_id` = 2";
                        } else {
                            // case "all_learners", don't specify slot type. // on and off service / target_role = learner
                            $AND_slot_type_filter = "";
                        }

                        $auth_database = AUTH_DATABASE; // makes SQL cleaner
                        $query = "  SELECT    a.`schedule_id`, a.`start_date`, a.`schedule_parent_id`, a.`end_date`,
                                              b.*,
                                              c.`slot_type_id`,
                                              d.`id`, d.`firstname`, d.`lastname`, d.`email`, d.`number`
                                    FROM      `cbl_schedule`  AS a

                                    JOIN      `cbl_schedule_audience` AS b
                                    ON        a.`schedule_id` =  b.`schedule_id`

                                    JOIN      `cbl_schedule_slots` AS c
                                    ON        b.`schedule_slot_id` = c.`schedule_slot_id`

                                    JOIN      `$auth_database`.`user_data` AS d
                                    ON        b.`audience_value` =  d.`id`

                                    WHERE     a.`start_date` >= ?
                                    AND       a.`deleted_date` IS NULL
                                    AND       b.`audience_type` = 'proxy_id'
                                    AND       b.`deleted_date` IS NULL

                                    $AND_audience_ids_in

                                    $AND_date_filter
                                    $AND_slot_type_filter
                                    $AND_schedule_id_in

                                    GROUP BY  b.`audience_value`
                                    ORDER BY  d.`lastname`";

                        $prepared_variables = array();
                        $prepared_variables[] = $schedule_cut_off_date;
                        if ($AND_date_filter) {
                            $prepared_variables[] = $filter_start_date;
                            $prepared_variables[] = $filter_end_date;
                            $prepared_variables[] = $filter_start_date;
                            $prepared_variables[] = $filter_end_date;
                            $prepared_variables[] = $filter_start_date;
                            $prepared_variables[] = $filter_end_date;
                        }

                        $results = $db->GetAll($query, $prepared_variables);
                        if (empty($results)) {
                            $entity_info["no_targets"] = true;
                        }

                        if ($results) {
                            foreach ($results as $user) {
                                $scm_info = $default_member_info;
                                $scm_info["member_id"] = $user["id"];
                                $scm_info["member_fullname"] = "{$user["firstname"]} {$user["lastname"]}";
                                $scm_info["member_firstname"] = $user["firstname"];
                                $scm_info["member_lastname"] = $user["lastname"];
                                $scm_info["member_email"] = $user["email"];
                                $scm_info["member_number"] = $user["number"];

                                $entity_info["members"][] = $scm_info;
                            }
                        }
                    }
                }
                break;

            case "self": // The targets = the assessors
                // Fetch all the selected assessors
                $assessor_list = Models_Assessments_Distribution_Assessor::fetchAllByDistributionID($this->getDistributionID());
                foreach ($assessor_list as $assessor) {
                    if ($assessor->getAssessorType() == "self") {
                        // avoid endless recursion
                        $found = false;
                    } else {
                        $found = $this->expandTargetData($assessor->getAssessorValue(), $assessor->getAssessorType(), $assessor->getAssessorScope());
                    }

                    if ($found) {
                        $entity_info["members"] = $found;
                    }
                }
                break;

            case "cgroup_id": // course group (not used)
            case "organisation_id": // organization (not supported)
            default:
                break;
        }

        $flattened_targets = array();

        if ($entity_info["use_members"] && !empty($entity_info["members"])) {
            foreach ($entity_info["members"] as $member_data) {
                $flattened_targets[] = $this->flattenEntityMemberData($entity_info, $member_data);
            }
        } else { // non-user entity is the target
            $flattened_targets[] = $this->flattenEntityMemberData($entity_info, array());
        }

        /* Flattened entity member data looks like:
         * Array
         *   (
         *       [0] => Array
         *       (
         *           [id] => 5643
         *           [type] => proxy_id
         *           [scope] => self
         *           [use_members] => 1
         *           [entity_name] => Jason Moore
         *           [no_targets] =>
         *           [member_id] => 5643
         *           [member_fullname] => Jason Moore
         *           [member_firstname] => Jason
         *           [member_lastname] => Moore
         *           [member_email] => medtech+5643@qmed.ca
         *           [member_number] => 10036194
         *       )
         *      [n] => Array
         *          ...
         *   )
         *
         * In most cases, it will only have 1 array element, but can have n array elements.
         */
        return $flattened_targets;
    }

    /**
     * For a given target_id (for an external assessor or proxy_id), fetch the standardized expanded target data structure. This returns only 1 single entity (will not be expanded into multiple).
     *
     * @param int $target_id
     * @param string $type
     * @param string $scope
     * @return array
     */
    private function expandSingleTargetData ($target_id, $type, $scope) {

        // Everything should be treated as a proxy ID, except targets that are the group, course and schedule entities (in certain configurations)
        if ($type == "external" || $type == "external_hash") {
            $expanded = $this->expandTargetData($target_id, "external_hash", "self"); // Fetch external user
        } else if ($type == "course_id" && ($scope == "all_learners" || $scope == "self")) {
            $expanded = $this->expandTargetData($target_id, $type, $scope);
        } else if ($type == "group_id" && $scope == "all_learners") {
            $expanded = $this->expandTargetData($target_id, $type, $scope);
        } else if ($type == "schedule_id" && $scope == "self") {
            $expanded = $this->expandTargetData($target_id, $type, $scope);
        } else {
            // All others, treat as single internal users
            $expanded = $this->expandTargetData($target_id, "proxy_id", "self"); // proxy_id
        }

        // The expandTargetData call should return an array with 1 item, so we shift it off and return that.
        if (!empty($expanded))
            return array_shift($expanded);

        return $expanded;
    }

    /**
     * Merge the default information about an entity with a specific member of that entity, flattening the data.
     *
     * @param array $meta
     * @param array $member
     * @return array
     */
    private function flattenEntityMemberData ($meta, $member) {
        $flattened = array();

        $flattened["id"] = $meta["id"];
        $flattened["type"] = $meta["type"];
        $flattened["scope"] = $meta["scope"];
        $flattened["use_members"] = $meta["use_members"];
        $flattened["entity_name"] = $meta["entity_name"];
        $flattened["no_targets"] = $meta["no_targets"];

        return array_merge($flattened, $member);
    }

    /**
     * Given the list of assessments, create a compressed list, grouped by blocks.
     *
     * Assessment data looks like this:
     * Array
     *   (
     *       [0] => Array
     *       (
     *           [target_id] => 5613
     *           [type] => proxy_id
     *           [scope] => self
     *           [rotation_blocks] => Array
     *           (
     *               [0] => Array
     *               (
     *                   [schedule_id] => 2066
     *                   [title] => Block 9
     *                   [start_date] => 1454994000
     *                   [schedule_parent_id] => 2057
     *                   [end_date] => 1457413199
     *                   [audience_type] => proxy_id
     *                   [audience_value] => 5613
     *                   [slot_type_id] => 1
     *               )
     *               ...
     *               [n] => Array
     *               (
     *                  [schedule_id] => 2067
     *                  ...
     *               )
     *           )
     *       )
     *      [n] => Array ...
     * )
     *
     * The resulting transformation looks like:
     * Array
     * (
     *  [2066-2067] => Array
     *  (
     *      [0] => proxy_id-self-5613
     *      [1] => proxy_id-self-5643
     *  )
     *
     *  [2069-2070] => Array
     *  (
     *      [0] => proxy_id-self-6990
     *  )
     *
     *  [2061-2062-2063] => Array
     *  (
     *      [0] => proxy_id-self-5627
     *  )
     * )
     *
     * @param array $assessments
     * @return array
     */
    private function groupAssessmentsByBlock (&$assessments) {
        $block_list = array();
        foreach ($assessments as $assessment) {
            foreach ($assessment["rotation_blocks"] as $rb) {
                $block_list["{$assessment["type"]}-{$assessment["scope"]}-{$assessment["target_id"]}"][] = $rb["schedule_id"];
            }
        }
        $grouped = array();
        foreach ($block_list as $i => $bl) {
            sort($bl);
            $id_str = implode("-", $bl);
            if (isset($grouped[$id_str])) {
                if (!in_array($i, $grouped[$id_str])) {
                    $grouped[$id_str][] = $i;
                }
            } else {
                $grouped[$id_str][] = $i;
            }
        }
        return $grouped;
    }

    /**
     * Given descriptive arrays of targets and assessors data, combine them into a usable, sanitized array structure. Target and Assessor data is posted via HTML form.
     *
     * Targets data looks like:
     * Array
     *(
     *  [n] => Array
     *  (
     *      [target_id] => 3143
     *      [type] => proxy_id
     *      [scope] => self
     *  )
     *  [n+1] => Array ...
     *  ...
     * )
     *
     * Assessor data looks like:
     * Array
     * (
     *  [n] => Array
     *  (
     *      [assessor_value] => 4407
     *      [assessor_type] => internal
     *  )
     *  [n+1] => Array ...
     *  ...
     * )
     *
     * The resulting data structure will look like:
     * Array
     * (
     *  [n] => Array
     *  (
     *      [target] => Array
     *      (
     *          [target_id] => 3143
     *          [type] => proxy_id
     *          [scope] => self
     *          [rotation_blocks] => Array
     *              (
     *              )
     *      )
     *
     *      [assessor] => Array
     *      (
     *          [assessor_value] => 4407
     *          [assessor_type] => internal
     *      )
     * )
     * [n+1] => Array ...
     * ...
     * )
     *
     * The rotation_blocks array will contain each of the specified proxy_id's rotation block information, if any, if the target type is proxy_id and scope is internal.
     *
     * @param array $targets
     * @param array $assessors
     * @param bool $add_block_info
     * @return array
     */
    private function buildTargetsAndAssessorsArray ($targets, $assessors, $add_block_info = false) {
        $possible_assessments = array();

        // Sanitize targets array.
        foreach ($targets as $i => $t_data) {
            $invalid_target_data = true;
            if (isset($t_data["target_id"]) && isset($t_data["type"]) && isset($t_data["scope"])) {
                $targets[$i]["target_id"] = clean_input($t_data["target_id"], array("int"));
                if ($targets[$i]["target_id"]) {
                    $invalid_target_data = false;
                }
                $targets[$i]["rotation_blocks"] = array();
            }
            if ($invalid_target_data) {
                return array(); // exit on invalid data
            }
        }

        // If specified, add the rotation schedule information to each target
        if ($add_block_info) {
            $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($this->getDistributionID());
            if ($distribution_schedule) {
                $schedule = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID());
                // Add rotation block information to the targets array
                if ($schedule) {
                    foreach ($targets as $i => $t) {
                        if ($targets[$i]["type"] == "proxy_id") {
                            $targets[$i]["rotation_blocks"] = $this->fetchRotations($schedule->getID(), null, $t["target_id"]);
                        }
                    }
                }
            }
        }
        foreach ($targets as $i => $t_data) {
            foreach ($assessors as $j => $a_data) {
                $invalid_assessor_data = true;

                if (isset($a_data["assessor_value"]) && isset($a_data["assessor_type"])) {
                    $assessors[$j]["assessor_value"] = clean_input($a_data["assessor_value"], array("int"));
                    if ($assessors[$j]["assessor_value"]) {
                        $invalid_assessor_data = false;
                    }
                }
                if ($invalid_assessor_data) {
                    return array();
                }
                $possible_assessments[] = array("target" => $t_data, "assessor" => $a_data);
            }
        }
        return $possible_assessments;
    }

    //--- (Private) Notifications handling functionality (wrappers) ---//

    /**
     * Add the notification data to our internal queue for a new assessment.
     *
     * @param int $dassignment_id
     * @param int $dassessment_id
     * @param int $assessor_id
     * @param string $assessor_type
     * @param int $schedule_id
     */
    private function notificationQueueAddAssessmentCreation($dassignment_id, $dassessment_id, $assessor_id, $assessor_type, $schedule_id = null) {
        $this->notifications[] = array(
            "delegation_notification_type" => "delegated_assessment",
            "notification_type" => "assessor_start",
            "content_type" => "assessment",
            "delegation_assignment_id" => $dassignment_id,
            "dassessment_id" => $dassessment_id,
            "assessor_id" => $assessor_id,
            "assessor_type" => $assessor_type,
            "adistribution_id" => $this->getDistributionID(),
            "schedule_id" => $schedule_id
        );
    }

    /**
     * Add the notification data to our internal queue for removal of an assessment.
     *
     * @param int $dassignment_id
     * @param int $dassessment_id
     * @param int $assessor_id
     * @param string $assessor_type
     * @param int $schedule_id
     */
    private function notificationQueueAddAssessmentRemoval($dassignment_id, $dassessment_id, $assessor_id, $assessor_type, $schedule_id = null) {
        $this->notifications[] = array(
            "delegation_notification_type" => "delegation_assignment_removal",
            "notification_type" => "assessment_delegation_assignment_removed",
            "content_type" => "assessment_delegation_assignment_removed",
            "delegation_assignment_id" => $dassignment_id,
            "dassessment_id" => $dassessment_id,
            "assessor_id" => $assessor_id,
            "assessor_type" => $assessor_type,
            "adistribution_id" => $this->getDistributionID(),
            "schedule_id" => $schedule_id
        );
    }

    /**
     * Add a deletion notification to the queue.
     *
     * @param $dassignment_id
     * @param $dassessment_id
     * @param $assessor_id
     * @param $assessor_type
     * @param null $schedule_id
     */
    private function notificationQueueAddAssessmentDeletion($dassignment_id, $dassessment_id, $assessor_id, $assessor_type, $schedule_id = null) {
        $this->notifications[] = array(
            "delegation_notification_type" => "delegation_assessment_deleted",
            "notification_type" => "assessment_task_deleted",
            "content_type" => "delegated_assessment_task_deleted",
            "delegation_assignment_id" => $dassignment_id,
            "dassessment_id" => $dassessment_id,
            "assessor_id" => $assessor_id,
            "assessor_type" => $assessor_type,
            "adistribution_id" => $this->getDistributionID(),
            "schedule_id" => $schedule_id
        );
    }

    /**
     * Iterate through our internal queue, send notifications, and clear queue.
     */
    private function notificationQueueSendAll() {
        require_once("Classes/notifications/NotificationUser.class.php");
        require_once("Classes/notifications/Notification.class.php");
        global $db;

        foreach ($this->notifications as $n) {
            if ($n["assessor_type"] == "internal") {
                $notification_assessor_type = "proxy_id";
            } else {
                $notification_assessor_type = "external_assessor_id";
            }

            // From the delegation progress page interface, an assessment assignment (including the related assessment) was deleted
            switch ($n["delegation_notification_type"]) {
                case "delegation_assessment_deleted":
                    $notification_user = NotificationUser::get($n["assessor_id"], $n["content_type"], $n["dassessment_id"], $n["assessor_id"], $notification_assessor_type);
                    if (!$notification_user) {
                        $notification_user = NotificationUser::add($n["assessor_id"], $n["content_type"], $n["dassessment_id"],$n["assessor_id"], 1, 0, 0, $notification_assessor_type);
                    }

                    if ($notification_user) {
                        $notification = Notification::add($notification_user->getID(), $n["assessor_id"], $n["delegation_assignment_id"]);
                        if ($notification) {
                            $assessment_notification = new Models_Assessments_Notification(array(
                                "adistribution_id" => $this->getDistributionID(),
                                "assessment_value" => $n["dassessment_id"],
                                "assessment_type" => "assessment",
                                "notified_value" => $n["assessor_id"],
                                "notified_type" => $notification_assessor_type,
                                "notification_id" => $notification->getID(),
                                "nuser_id" => $notification_user->getID(),
                                "notification_type" => $n["notification_type"],
                                "schedule_id" => $n["schedule_id"],
                                "sent_date" => time()
                            ));
                            if (!$assessment_notification->insert()) {
                                application_log("error", "Error encountered while attempting to save history of an assessment notification being sent to a user. DB said " . $db->ErrorMsg());
                            }
                        }
                    }
                    break;

                // From the delegation interface, an assessment assignment (including the assessment) was deleted.
                case "delegation_assignment_removal":
                    $notification_user = NotificationUser::get($n["assessor_id"], $n["content_type"], $n["dassessment_id"], $n["delegation_assignment_id"], $notification_assessor_type);
                    if (!$notification_user) {
                        $notification_user = NotificationUser::add($n["assessor_id"], $n["content_type"], $n["dassessment_id"],$n["delegation_assignment_id"], 1, 0, 0, $notification_assessor_type);
                    }
                    if ($notification_user) {
                        $notification = Notification::add($notification_user->getID(), $n["assessor_id"], $n["dassessment_id"], $n["delegation_assignment_id"]);
                        if ($notification) {
                            $assessment_notification = new Models_Assessments_Notification(array(
                                "adistribution_id" => $this->getDistributionID(),
                                "assessment_value" => $n["dassessment_id"],
                                "assessment_type" => "assessment",
                                "notified_value" => $n["assessor_id"],
                                "notified_type" => $notification_assessor_type,
                                "notification_id" => $notification->getID(),
                                "nuser_id" => $notification_user->getID(),
                                "notification_type" => $n["notification_type"],
                                "schedule_id" => $n["schedule_id"],
                                "sent_date" => time()
                            ));
                            if (!$assessment_notification->insert()) {
                                application_log("error", "Error encountered while attempting to save history of an assessment notification being sent to a user. DB said " . $db->ErrorMsg());
                            }
                        }
                    }
                    break;

                // From the delegation interface, assessment has been assigned, notify user.
                case "delegated_assessment":
                    $notification_user = NotificationUser::get($n["assessor_id"], $n["content_type"], $n["dassessment_id"], $n["delegation_assignment_id"], $notification_assessor_type);
                    if (!$notification_user) {
                        $notification_user = NotificationUser::add($n["assessor_id"], $n["content_type"], $n["dassessment_id"], $n["delegation_assignment_id"], 1, 0, 0, $notification_assessor_type);
                    }
                    if ($notification_user) {
                        $notification = Notification::add($notification_user->getID(), $n["assessor_id"], $n["dassessment_id"]);
                        if ($notification) {
                            $assessment_notification = new Models_Assessments_Notification(array(
                                "adistribution_id" => $this->getDistributionID(),
                                "assessment_value" => $n["dassessment_id"],
                                "assessment_type" => "assessment",
                                "notified_value" => $n["assessor_id"],
                                "notified_type" => $notification_assessor_type,
                                "notification_id" => $notification->getID(),
                                "nuser_id" => $notification_user->getID(),
                                "notification_type" => $n["notification_type"],
                                "schedule_id" => $n["schedule_id"],
                                "sent_date" => time()
                            ));
                            if (!$assessment_notification->insert()) {
                                application_log("error", "Error encountered while attempting to save history of an assessment notification being sent to a user. DB said " . $db->ErrorMsg());
                            }
                        }
                    }
                    break;
            }
        }
        $this->notifications = array();
    }

}