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
 * This is an abstraction layer for assessment related functionality.
 * It encapsulates validation and submission of progress data for
 * assessment tasks.
 *
 * NOTE: The base class includes actor_proxy_id and actor_organisation_id, but they are not
 * necessarily leveraged by this class in all cases, as external users
 * (proxy/organisation ID-less users) are able to submit assessment data
 * without user accounts.
 *
 * @author Organization: Queen's University
 * @author Unit: Health Sciences, Education Technology Unit
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Entrada_Assessments_Workers_Assessment extends Entrada_Assessments_Base {

    protected $dassessment_id        = null;
    protected $aprogress_id          = null;  // Used to pre-load a form with is_selected data (for view consumption)
    protected $fetch_form_data       = false; // Include the form data in the dataset?
    protected $fetch_deleted_targets = false; // Include deleted assessment targets in the dataset?
    protected $limit_dataset         = array();
    protected $form_limit_dataset    = array();
    protected $rubric_limit_dataset  = array();
    private   $interim_progress      = array(); // Temporary storage for validated response submission.

    public function __construct($arr) {
        parent::__construct($arr);
        if (!is_array($this->limit_dataset)) {
            $this->limit_dataset = array();
        }
        if (!is_array($this->form_limit_dataset)) {
            $this->form_limit_dataset = array();
        }
        if (!is_array($this->rubric_limit_dataset)) {
            $this->rubric_limit_dataset = array();
        }
        if (!$this->dassessment_id && $this->aprogress_id) {
            // Progress ID is set, but assessment ID isn't. So let's find the dassessment_id to use.
            if ($progress_record = Models_Assessments_Progress::fetchRowByIDIncludeDeleted($this->aprogress_id)) {
                $this->dassessment_id = $progress_record->getDAssessmentID();
            }
        }
    }

    public function getAssessmentID() {
        return $this->dassessment_id;
    }

    public function setAssessmentID($id) {
        $this->setDassessmentID($id);
    }

    public function getDassessmentID() {
        return $this->dassessment_id;
    }

    public function setDassessmentID($id) {
        $this->dassessment_id = $id;
        $this->setStale();
    }

    public function getAprogressID() {
        return $this->aprogress_id;
    }

    public function setAprogressID($id, $set_stale = true) {
        $this->aprogress_id = $id;
        if ($set_stale) {
            $this->setStale();
        }
    }

    public function getExternalHash() {
        if (isset($this->dataset["assessment"]["external_hash"])) {
            return $this->dataset["assessment"]["external_hash"];
        }
        return "";
    }

    public function getProgressID() {
        return $this->aprogress_id;
    }

    public function setProgressID($id, $set_stale = true) {
        $this->setAprogressID($id, $set_stale);
    }

    public function setFetchFormData($bool) {
        if ($this->fetch_form_data !== $bool) {
            $this->fetch_form_data = $bool;
            $this->setStale();
        }
    }

    public function getFetchFormData() {
        return $this->fetch_form_data;
    }

    public function setFormLimitDataset($limits) {
        if ($this->form_limit_dataset !== $limits) {
            $this->form_limit_dataset = $limits;
            $this->setStale();
        }
    }

    public function getFormLimitDataset() {
        return $this->form_limit_dataset;
    }

    public function setRubricLimitDataset($limits) {
        if ($this->rubric_limit_dataset !== $limits) {
            $this->rubric_limit_dataset = $limits;
            $this->setStale();
        }
    }

    public function getRubricLimitDataset() {
        return $this->rubric_limit_dataset;
    }

    /**
     * For a given string of JSON, fetch the value at the given index, assuming that the json respresents an object that can be parsed into an array.
     * Optionally, check for a specific value and return as boolean result.
     * OR fetch the value at $secondary_index as an index of the first array.
     *
     * @param string $json_string
     * @param string $primary_index
     * @param mixed $specified_value
     * @param string $secondary_index
     * @return bool|mixed|null
     */
    public static function getAttributesFromJson($json_string, $primary_index = null, $specified_value = null, $secondary_index = null) {
        if (!$json_string) {
            return null;
        }
        // Decode our array. If there is no primary_index given, return the entire array.
        $decoded = @json_decode($json_string, true);
        if (!is_array($decoded)) {
            return null;
        }
        if (!$primary_index) {
            return $decoded;
        }
        // Check for the value at the given index (it can be a value or an array)
        $primary = Entrada_Utilities::arrayValueOrDefault($decoded, $primary_index, array());
        if (empty($primary)) {
            return $primary;
        }
        // Check if the specified value exists inside of of the primary array (if it is one).
        if ($specified_value) {
            if (is_array($primary) && in_array($specified_value, $primary)) {
                return true;
            } else {
                return false;
            }
        }
        // If we haven't found what we're looking for, check if there's something at $decoded[$primary_index][$secondary_index] and return that.
        $secondary = Entrada_Utilities::arrayValueOrDefault($decoded[$primary_index], $secondary_index, array());
        if (empty($secondary)) {
            return $primary;
        } else {
            // If we've been given a specific value, check if that exists inside the presumed array at $decoded[$primary_index]
            return $secondary;
        }
    }


    /**
     * For the current dataset, create the object version of the assessment record.
     *
     * @return bool|Models_Assessments_Assessor
     */
    public function getAssessmentRecord() {
        $this->fetchData();
        if (empty($this->dataset["assessment"])) {
            return false;
        }
        $record = new Models_Assessments_Assessor();
        $record->fromArray($this->dataset["assessment"]);
        return $record;
    }

    /**
     * Build a fully qualified assessment URL.
     * The default behaviour for this method is to build the URL as an internal link.
     * If $allow_external is true, when an assessment is external, the external_hash will be added,
     * and the URL adjusted to the public (external) version of the assessment.
     *
     * @param int $target_record_id
     * @param string $target_type
     * @param bool $allow_external
     * @return bool|string
     */
    public function getAssessmentURL($target_record_id = null, $target_type = null, $allow_external = false) {
        $this->fetchData();
        if (empty($this->dataset)) {
            return false;
        }
        $str_target_id = $str_progress_id = "";
        if ($target_record_id && $target_type) {
            foreach ($this->dataset["targets"] as $target_data) {
                if ($target_data["target_type"] == $target_type && $target_data["target_value"] == $target_record_id) {
                    $str_target_id = "&atarget_id={$target_data["atarget_id"]}";
                }
            }
        }
        if ($this->aprogress_id) {
            $str_progress_id = "&aprogress_id={$this->aprogress_id}";
        }
        $base_assessment_url = $this->dataset["meta"]["assessment_uri_internal"];
        if ($allow_external) {
            if ($this->dataset["meta"]["is_external"]) {
                $base_assessment_url = $this->dataset["meta"]["assessment_uri_external"];
            }
        }
        return $base_assessment_url . $str_progress_id . $str_target_id;
    }

    /**
     * Calculate the number of targets with pending progress records (that is, no progress records).
     *
     * @return int
     */
    public function getCountUniqueTargetsPending() {
        return $this->countUniquePendingTargets();
    }

    /**
     * Fetch the number of targets with at least one in progress record (not the number of in-progress records).
     *
     * @param int|null $target_id
     * @param int|null $target_type
     * @return int
     */
    public function getCountUniqueTargetsInProgress($target_id = null, $target_type = null) {
        return $this->countUniqueTargetsWithProgressValue($target_id, $target_type, "inprogress", false);
    }

    /**
     * Fetch the number of targets with at least one completed progress record.
     *
     * @param int|null $target_id
     * @param int|null $target_type
     * @return int
     */
    public function getCountUniqueTargetsComplete($target_id = null, $target_type = null) {
        return $this->countUniqueTargetsWithProgressValue($target_id, $target_type, "complete");
    }

    /**
     * Get the count of how many completed progress records the given target has.
     *
     * @param $target_id
     * @param $target_type
     * @return int
     */
    public function getCountCompleteProgress($target_id, $target_type) {
        $this->fetchData();
        if (empty($this->dataset)) {
            return 0;
        }
        $current_target = $this->getCurrentTarget($target_id, $target_type);
        if (empty($current_target)) {
            return 0; // not found
        }
        $count = 0;
        foreach ($this->dataset["progress"] as $progress) {
            if ($progress["target_record_id"] == $target_id
                && $progress["target_type"] == $target_type
                && $progress["progress_value"] == "complete"
            ) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Return whether the current assessment is completed.
     *
     * @return bool
     */
    public function isOverallCompleted() {
        $this->fetchData();
        if (empty($this->dataset)) {
            return false;
        }
        $min_attempts = $this->dataset["assessment"]["min_submittable"];

        // If any of the targets do not have enough completed progress to meet the minimum attempts, return false.
        $progress_summary = $this->buildTargetProgressSummaryData();
        foreach ($progress_summary as $target_type => $target_value) {

            // Ensure this target is not deleted.
            $should_process = true;
            foreach ($this->dataset["targets"] as $target_data) {
                if ($target_data["target_type"] == $target_type
                    && $target_data["target_value"] == $target_value
                    && $target_data["deleted_date"]
                ) {
                    $should_process = false;
                }
            }
            if (!$should_process) {
                continue;
            }
            $completion_count = 0;
            foreach ($target_value as $progress_data) {
                foreach ($progress_data as $progress) {
                    if ($progress["progress_value"] == "complete") {
                        $completion_count++;
                    }
                }
            }
            if ($completion_count < $min_attempts) {
                return false;
            }
        }
        return true;
    }

    /**
     * Build an array describing the current assessment target.
     * This also returns a custom scope value which indicates if the target is internal or external.
     *
     * proxy_id|schedule_id|event_id|course_id = "internal"
     * external_hash|external = "external"
     *
     * @param int|bool $target_record_id
     * @param string|bool $target_type
     * @return array
     */
    public function getCurrentTarget($target_record_id = false, $target_type = false) {
        $this->fetchData();
        if (!empty($this->dataset)) {
            if ($target_record_id !== false) {
                // Find this target in the dataset
                foreach ($this->dataset["targets"] as $target) {
                    if ($target["target_value"] == $target_record_id
                        && $target["target_type"] == $target_type
                    ) {
                        return array(
                            "atarget_id" => $target["atarget_id"],
                            "target_type" => $target["target_type"],
                            "target_record_id" => $target["target_value"],
                            "target_name" => $target["target_name"],
                            "task_type" => $target["task_type"],
                            "target_scope" => $target["target_type"] == "external" || $target["target_type"] == "external_hash"
                                ? "external"
                                : "internal",
                            "deleted_date" => $target["deleted_date"],
                            "deleted_reason_id" => $target["deleted_reason_id"],
                            "deleted_reason_notes" => $target["deleted_reason_notes"]
                        );
                    }
                }
            } else {
                // Otherwise, find based on progress
                if ($this->aprogress_id) {
                    // Find the current target based on our current progress
                    foreach ($this->dataset["progress"] as $progress) {
                        if ($progress["aprogress_id"] == $this->aprogress_id) {
                            // Found a progress record, so match the target
                            foreach ($this->dataset["targets"] as $target) {
                                if ($target["target_value"] == $progress["target_record_id"]
                                    && $target["target_type"] == $progress["target_type"]
                                ) {
                                    return array(
                                        "atarget_id" => $target["atarget_id"],
                                        "target_type" => $target["target_type"],
                                        "target_record_id" => $target["target_value"],
                                        "target_name" => $target["target_name"],
                                        "task_type" => $target["task_type"],
                                        "target_scope" => $target["target_type"] == "external" || $target["target_type"] == "external_hash"
                                            ? "external"
                                            : "internal",
                                        "deleted_date" => $target["deleted_date"],
                                        "deleted_reason_id" => $target["deleted_reason_id"],
                                        "deleted_reason_notes" => $target["deleted_reason_notes"]
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }
        // Didn't find one, or one wasn't specified.
        return array();
    }

    /**
     * Fetch the current progress record that matches the current aprogress_id from the dataset.
     *
     * @return array
     */
    public function getCurrentProgress() {
        $this->fetchData();
        if (!empty($this->dataset) && $this->aprogress_id) {
            foreach ($this->dataset["progress"] as $progress) {
                if ($progress["aprogress_id"] == $this->aprogress_id) {
                    return $progress;
                }
            }
        }
        return array();
    }

    /**
     * Fetch the latest progress response record for the current assessment.
     *
     * @param bool $exclude_deleted
     * @return array|bool
     */
    public function getLatestProgressResponse($exclude_deleted = true) {
        $this->fetchData();
        if (empty($this->dataset) || !$this->aprogress_id) {
            return false;
        }
        $current_progress = $this->getCurrentProgress();
        if (empty($current_progress)) {
            return array();
        }
        if (!array_key_exists("progress_responses", $current_progress)) {
            return array();
        }
        $latest_progress = array();
        foreach ($current_progress["progress_responses"] as $progress) {
            if (empty($latest_progress)) {
                if ($progress["deleted_date"] && $exclude_deleted) {
                    continue;
                }
                $latest_progress = $progress;
            } else {
                if ($latest_progress["created_date"] < $progress["created_date"]) {
                    if ($exclude_deleted && $progress["deleted_date"]) {
                        continue;
                    } else {
                        $latest_progress = $progress;
                    }
                }
            }
        }
        return $latest_progress;
    }

    /**
     * Returns the number of targets for this assessment that have been deleted.
     *
     * @return int
     */
    public function getDeletedTargetCount() {
        if (empty($this->dataset)) {
            return 0;
        }
        return $this->dataset["meta"]["deleted_target_count"];
    }

    /**
     * Fetch the feedback for the target of the assessment. This is limited in scope to assessments, not progress.
     * That means, one feedback record per target & assessor per dassessment_id.
     *
     * @param $target_id
     * @param $target_type
     * @return array
     */
    public function getFeedbackForTarget($target_id, $target_type) {
        $this->fetchData();
        if (empty($this->dataset)) {
            return array();
        }
        $current_target = $this->getCurrentTarget($target_id, $target_type);
        if (empty($current_target)) {
            return array();
        }
        foreach ($this->dataset["feedback"] as $feedback) {
            if ($this->dataset["assessor"]["type"] == $feedback["assessor_type"] &&
                $this->dataset["assessor"]["assessor_id"] == $feedback["assessor_value"] &&
                $current_target["target_record_id"] == $feedback["target_value"] &&
                $current_target["target_scope"] == $feedback["target_type"]
            ) {
                return $feedback;
            }
        }
        return array();
    }

    /**
     * Fetch all of the progress data for a specific target.
     *
     * @param $target_id
     * @param $target_type
     * @return array
     */
    public function getProgressForTarget($target_id, $target_type) {
        $this->fetchData();
        if (empty($this->dataset)) {
            return array();
        }
        $collected_progress = array();
        foreach ($this->dataset["progress"] as $progress_data) {
            if ($progress_data["target_type"] == $target_type && $progress_data["target_record_id"] == $target_id) {
                $current_target = $this->getCurrentTarget($target_id, $target_type);
                $progress_data["atarget_id"] = null;
                if (!empty($current_target)) {
                    $progress_data["atarget_id"] = $current_target["atarget_id"];
                }
                $collected_progress[] = $progress_data;
            }
        }
        return $collected_progress;
    }

    /**
     * Get the relevant approval data from the dataset for this progress ID, if any.
     *
     * @return array
     */
    public function getApprovalDataForProgress() {
        $this->fetchData();
        if (empty($this->dataset)) {
            return array();
        }
        if (!$this->aprogress_id) {
            return array();
        }
        if (empty($this->dataset["distribution"])) {
            return array();
        }
        if (empty($this->dataset["distribution"]["distribution_approvers"])) {
            return array();
        }
        $approval_data = array();
        $approval_data["aprogress_id"] = $this->aprogress_id;
        $approval_data["approval_status"] = "pending";
        $approval_data["approval_time"] = 0;
        $approval_data["approver_proxy_id"] = null;
        $approval_data["approver_fullname"] = null;

        // Populate the array with data from the dataset.
        foreach ($this->dataset["distribution"]["distribution_approvers"] as $approver) {
            if ($this->actor_proxy_id && $this->actor_proxy_id == $approver["proxy_id"]) {
                // This actor is a potential approver, so return their name in the array
                $approval_data["approver_proxy_id"] = $approver["proxy_id"];
                $approval_data["approver_fullname"] = $this->fetchUserFullname($approver["proxy_id"]);
            }
        }
        foreach ($this->dataset["distribution"]["distribution_approvals"] as $approval) {
            if ($approval["aprogress_id"] == $this->aprogress_id) {
                $approval_data["approval_status"] = $approval["approval_status"];
                $approval_data["approval_time"] = $approval["created_date"];
                // This progress record has been approved, so return the actual approver's name.
                $approval_data["approver_proxy_id"] = $approval["approver_id"];
                $approval_data["approver_fullname"] = $this->fetchUserFullname($approval["approver_id"]);
            }
        }
        return $approval_data;
    }

    /**
     * Build an array describing the state of the feedback that may or may not be present for the current target for this assessment.
     * This array can be passed directly to the feedback view object to render it in the appropriate state.
     *
     * @param bool $target_record_id
     * @param bool $target_type
     * @return array
     */
    public function getFeedbackOptions($target_record_id = false, $target_type = false) {
        $this->fetchData();
        if (empty($this->dataset)) {
            return array();
        }
        $current_target = $this->getCurrentTarget($target_record_id, $target_type);
        if (empty($current_target)) {
            return array();
        }
        $approval_data = $this->getApprovalDataForProgress();
        if (!empty($approval_data)) {
            $actor_is_approver = ($this->actor_proxy_id == $approval_data["approver_proxy_id"] && $this->actor_scope == "internal");
        } else {
            $actor_is_approver = false;
        }
        $feedback_data = $this->getFeedbackForTarget($target_record_id, $target_type);
        $feedback_required = $this->isFeedbackRequired();
        $feedback_pending = $feedback_required;
        $render_submission_buttons = false;
        $update_feedback_only = false;

        $feedback_options = array();
        $feedback_options["actor_id"] = $this->actor_proxy_id;
        $feedback_options["actor_type"] = $this->actor_type;
        $feedback_options["assessor_id"] = $this->dataset["assessor"]["assessor_id"];
        $feedback_options["assessor_type"] = $this->dataset["assessor"]["type"];
        $feedback_options["assessment_complete"] = $this->isCompleted();
        $feedback_options["feedback_actor_is_target"] = false;
        $feedback_options["include_preceptor_label"] = false;
        $feedback_options["hide_target_comments"] = true;
        if (!empty($feedback_data)) {
            $feedback_options["assessor_feedback"] = $feedback_data["assessor_feedback"];
            $feedback_options["target_feedback"] = $feedback_data["target_feedback"];
            $feedback_options["comments"] = $feedback_data["comments"];
            $feedback_options["target_progress_value"] = $feedback_data["target_progress_value"];
        } else {
            $feedback_options["assessor_feedback"] = null;
            $feedback_options["target_feedback"] = null;
            $feedback_options["comments"] = null;
            $feedback_options["target_progress_value"] = null;
        }

        if ($feedback_required) {
            if ($current_target["target_record_id"] == $this->actor_proxy_id
                && $current_target["target_scope"] == "internal"
                && $this->actor_scope == "internal"
            ) {
                $feedback_options["feedback_actor_is_target"] = true;
            }
            // Determine at what state the feedback window is in, and what data to display.
            if (empty($feedback_data)) {
                $feedback_pending = true;
            } else {
                if ($feedback_options["feedback_actor_is_target"]
                    && $feedback_data["target_progress_value"] != "complete"
                ) {
                    $feedback_pending = true;
                } else {
                    $feedback_pending = false;
                }
            }
            if (!$feedback_pending
                && $feedback_data["target_progress_value"] == "complete"
            ) {
                $feedback_options["include_preceptor_label"] = true;
            }
            if ($actor_is_approver
                || ($this->actor_proxy_id == $this->dataset["assessor"]["assessor_id"]
                    && $this->actor_scope == "internal"
                    && $this->dataset["assessor"]["type"] == "internal"
                )
            ) {
                // The assessor (and approver) never get to see target comments
                $feedback_options["hide_target_comments"] = true;
            }
        }

        if ($current_target["target_scope"] == $this->dataset["assessor"]["type"]
            && $current_target["target_record_id"] == $this->dataset["assessor"]["assessor_id"]
        ) {
            // Disallow feedback on self.
            $feedback_required = false;
            $feedback_pending = false;
            // Allow the target to view their own comments
            $feedback_options["include_preceptor_label"] = true;
            $feedback_options["hide_target_comments"] = false;
        } else {
            if ($this->isCompleted()
                && $feedback_required
                && $current_target["target_record_id"] == $this->actor_proxy_id
                && $current_target["target_scope"] == "internal"
                && $this->actor_scope == "internal"
            ) {
                // The current logged in user is the target, and feedback is required.
                if (empty($feedback_data)) {
                    $render_submission_buttons = true;
                    $update_feedback_only = true;
                } else {
                    $feedback_options["include_preceptor_label"] = true;
                    $feedback_options["hide_target_comments"] = false;
                    if ($feedback_data["target_progress_value"] != "complete") {
                        $render_submission_buttons = true;
                        $update_feedback_only = true;
                    } else {
                        $render_submission_buttons = false;
                        $update_feedback_only = false;
                    }
                }
            }
        }
        $feedback_options["render_submission_buttons"] = $render_submission_buttons;
        $feedback_options["feedback_required"] = $feedback_required;
        $feedback_options["feedback_pending"] = $feedback_pending;
        $feedback_options["update_feedback_only"] = $update_feedback_only;
        return $feedback_options;
    }

    /**
     * Save an assessment option for the assessment cue
     * @param $new_aprogress_id
     * @param $new_dassessment_id
     * @param $copy_progress
     * @return boolean
     */
    public function saveCueAssessmentOption($new_aprogress_id, $new_dassessment_id, $copy_progress) {
        if (!$copy_progress) {
            $new_aprogress_id = null;
        }
        $assessment_options_model = new Models_Assessments_Options();
        $assessment_option = $assessment_options_model->fetchRowByDAssessmentIDOptionName($new_dassessment_id, "assessment_cue");

        if ($assessment_option) {
            $option_value = $assessment_option->getOptionValue();
            $options_array = json_decode($option_value, true);
            if ($options_array) {
                $assessment_option->setOptionValue(json_encode(array("aprogress_id" => $new_aprogress_id, "cue" => $options_array["cue"])));
                if (!$assessment_option->update()) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Build a list of targets for a given assessment. This list describes the state of the progress for each target.
     * The list is based on the number of attempts available.
     * This logic takes into account and removes target pending attempts when those targets are in-progress.
     *
     * @return array
     */
    public function getAssessmentTargetList() {
        $this->fetchData();
        if (empty($this->dataset)) {
            return array();
        }
        $available_targets = array();
        $progress_summary = $this->buildTargetProgressSummaryData();

        // Build the entire list of targets
        foreach ($progress_summary as $target_type => $targets) {
            foreach ($targets as $target_record_id => $target_progress_data) {
                $target_deleted_date = null;
                $tmp_target_progress = array();
                $complete_aprogress_id = $inprogress_aprogress_id = null;
                $atarget_id = null;
                foreach ($target_progress_data as $target_progress) {
                    $atarget_id = $target_progress["atarget_id"]; // OK to be overwritten, they're all the same value
                    $tmp_target_progress[] = $target_progress["progress_value"];
                }
                $target_name = "";
                foreach ($this->dataset["targets"] as $target_record) {
                    if ($target_record["target_value"] == $target_record_id && $target_record["target_type"] == $target_type) {
                        $target_name = $target_record["target_name"];
                        $target_deleted_date = $target_record["deleted_date"];
                    }
                }
                $counts = array_count_values($tmp_target_progress);
                if (!array_key_exists("complete", $counts)) {
                    $counts["complete"] = 0;
                }
                if (!array_key_exists("inprogress", $counts)) {
                    $counts["inprogress"] = 0;
                }
                if (!array_key_exists("pending", $counts)) {
                    $counts["pending"] = 0;
                }
                if ($counts["inprogress"] > 0) {
                    // Find the earliest inprogress record
                    foreach (array_reverse($target_progress_data) as $target_progress) {
                        if ($target_progress["progress_value"] == "inprogress") {
                            $inprogress_aprogress_id = $target_progress["aprogress_id"];
                        }
                    }
                }
                if ($counts["complete"] > 0) {
                    // Find the latest complete record
                    foreach ($target_progress_data as $target_progress) {
                        if ($target_progress["progress_value"] == "complete") {
                            $complete_aprogress_id = $target_progress["aprogress_id"];
                        }
                    }
                }
                $available_targets[] = array(
                    "name" => $target_name,
                    "atarget_id" => $atarget_id,
                    "complete_aprogress_id" => $complete_aprogress_id,
                    "inprogress_aprogress_id" => $inprogress_aprogress_id,
                    "target_type" => $target_type,
                    "target_record_id" => $target_record_id,
                    "progress" => $tmp_target_progress,
                    "counts" => $counts,
                    "deleted_date" => $target_deleted_date
                );
            }
        }
        return $available_targets;
    }

    /**
     * Update the current progress record with the new target ID/type.
     * This effectively moves progress from one record to another.
     *
     * @param int $destination_target_id
     * @param string $destination_target_type
     * @return bool
     */
    public function moveProgress($destination_target_id, $destination_target_type) {
        global $translate;
        if (!$this->aprogress_id) {
            $this->addErrorMessage($translate->_("Unable to move responses without a valid progress ID."));
            return false;
        }
        $this->fetchData();
        if (empty($this->dataset)) {
            $this->addErrorMessage($translate->_("Failed to fetch assessment data."));
            return false;
        }
        if (empty($this->dataset["progress"])) {
            $this->addErrorMessage($translate->_("No progress data found."));
            return false;
        }
        if ($this->dataset["assessment"]["feedback_required"]) {
            $this->addErrorMessage($translate->_("Unable to moved progress when feedback is enabled."));
            return false;
        }
        $current_progress = $this->getCurrentProgress();
        if (empty($current_progress)) {
            $this->addErrorMessage($translate->_("The specified target does not have any responses to move."));
            return false;
        }
        // Check if the destination is in progress already (if so, deny move)
        $destination_progress = $this->getProgressForTarget($destination_target_id, $destination_target_type);
        if (!empty($destination_progress)) {
            // There's some progress for this target, check if any of it is "in progress"
            foreach ($destination_progress as $destination_progress_data) {
                if ($destination_progress_data["progress_value"] == "inprogress") {
                    $this->addErrorMessage($translate->_("This target already has an in-progress assessment.")); // Do we actually want this to be limited to 1 in progress at a time?
                    return false;
                }
            }
        }
        // Change the target of the given progress record if we're safe to do so.
        $current_progress["target_record_id"] = $destination_target_id;
        $current_progress["target_type"] = $destination_target_type;
        $new_progress = new Models_Assessments_Progress($current_progress);
        if (!$new_progress->update()) {
            global $db;
            application_log("error", "Failed to update progress record: {$this->aprogress_id}. DB said: " . $db->ErrorMsg());
            $this->addErrorMessage($translate->_("Failed to move progress responses."));
            return false;
        }
        return true;
    }

    /**
     * Create a new progress record.
     *
     * @param $target_id
     * @param $target_type
     * @return bool
     */
    public function saveNewProgress($target_id, $target_type) {
        global $translate;
        $this->fetchData();
        if (empty($this->dataset)) {
            $this->addErrorMessage($translate->_("Failed to fetch assessment data."));
            return false;
        }
        if (empty($this->dataset["assessor"])) {
            $this->addErrorMessage($translate->_("Cannot create new progress record. Dataset does not contain valid assessor."));
            return false;
        }
        $progress_record = new Models_Assessments_Progress();
        $progress_record_data = $progress_record->toArray();
        $progress_record_data["dassessment_id"] = $this->dassessment_id;
        $progress_record_data["adistribution_id"] = $this->dataset["meta"]["adistribution_id"];
        $progress_record_data["uuid"] = $progress_record->generateUuid();
        $progress_record_data["assessor_type"] = $this->dataset["assessor"]["type"];
        $progress_record_data["assessor_value"] = $this->dataset["assessor"]["assessor_id"];
        $progress_record_data["target_record_id"] = $target_id;
        $progress_record_data["target_type"] = $target_type;
        $progress_record_data["progress_value"] = "inprogress";
        $progress_record_data["created_date"] = time();
        $progress_record_data["created_by"] = $this->actor_proxy_id;
        $progress_record->fromArray($progress_record_data);
        if (!$progress_record->insert()) {
            application_log("error", "Failed to insert new progress record: dassessment_id = {$this->dassessment_id}");
            $this->addErrorMessage($translate->_("Unable to create new progress record."));
            return false;
        }
        $this->setAprogressID($progress_record->getID());
        return true;
    }

    /**
     * Save the current interim progress data to the database.
     * This method wraps updateProgress and implements the load/save pattern used in forms_api.
     *
     * @param bool $mark_complete
     * @return bool
     */
    public function saveData($mark_complete = false) {
        global $translate;
        if (!$this->dassessment_id) {
            $this->addErrorMessage($translate->_("Unable to save data without assessment identifier."));
            return false;
        }
        $this->fetchData();
        if (empty($this->dataset)) {
            $this->addErrorMessage($translate->_("Invalid assessment (empty dataset)"));
            return false;
        }
        if (empty($this->interim_progress)) {
            $this->addErrorMessage($translate->_("Unable to submit responses (new responses not found)."));
            return false;
        }
        $current_target = $this->getCurrentTarget();
        if (empty($current_target)) {
            $this->addErrorMessage($translate->_("Current target not found."));
            return false;
        }
        return $this->updateProgress($this->interim_progress, $current_target["target_record_id"], $current_target["target_type"], $mark_complete, true);
    }

    /**
     * This is a psuedo-load. The dataset isn't being populated, but rather, interim progress is being stored in a separate array.
     * Progress is saved via updateProgress (saveData is a wrapper for updateProgress).
     *
     * If $validate_full is true, this method will perform full sanitization/validation, ignoring the mark_complete flag.
     *
     * @param $data_to_load
     * @param bool $full_validation
     * @return bool
     */
    public function loadData($data_to_load, $full_validation = false) {
        global $translate;
        if (!is_array($data_to_load) || empty($data_to_load)) {
            $this->addErrorMessage($translate->_("Unable to load empty dataset."));
            return false;
        }
        // Sanitize the data; the load/save pattern assumes we're posting/finalizing submission.
        $sanitized = $this->sanitizeData($data_to_load, !$full_validation); // full validation = !$partial_update
        if (!$sanitized || empty($sanitized)) {
            // Sanitization sets error messages
            $this->interim_progress = array();
            return false;
        }
        $this->interim_progress = $sanitized;
        return true;
    }

    /**
     * Sanitize and validate the posted data, returning a data structure containing clean data.
     * Sanitization is lightweight and does not leverage the abstraction layer dataset
     * as it is too expensive to for repeated calls via AJAX.
     *
     * The posted data is what is produced by a form generated on an assessment page and generally looks like:
     *   $posted_data["form_id"] = int
     *   $posted_data["adistribution_id"] = int
     *   $posted_data["aprogress_id"] = int
     *   $posted_data["dassessment_id"] = int
     *   $posted_data["target_record_id"] = int
     *   $posted_data["item-40787"] = iresponse ID
     *   $posted_data["item-40787-comments"] = string (related to the item)
     *   $posted_data["rubric-item-6000-40789"] = iresponse ID (rubric item has rubric ID preceding the item ID in the index)
     *   $posted_data["item-40789-comments"] = string (related to the rubric item (note: does not have rubric- prefix))
     *   $posted_data["item-40100-comments"] = string (stand-alone comment, e.g. free text field)
     *
     * @param array $posted_data
     * @param bool $partial_update
     * @param bool $add_item_errors
     * @return array|bool
     */
    public function sanitizeData($posted_data, $partial_update = true, $add_item_errors = true) {
        global $translate;
        if (!$this->dassessment_id) {
            $this->addErrorMessage($translate->_("No assessment ID specified."));
            return false;
        }
        if (!$assessment_record = $this->fetchAssessmentRecord($this->dassessment_id)) {
            $this->addErrorMessage($translate->_("Assessment record not found."));
            return false;
        }
        $form_id = $assessment_record->getFormID();
        if (!$form_id) {
            $this->addErrorMessage($translate->_("No form is associated with this assessment."));
            return false;
        }
        $form = Models_Assessments_Form::fetchRowByIDIncludeDeleted($form_id);
        if (!$form) {
            $this->addErrorMessage($translate->_("Invalid form."));
            return false;
        }
        if ($form->getDeletedDate()) {
            $this->addErrorMessage($translate->_("This form has been deleted."));
            return false;
        }
        $elements = Models_Assessments_Form_Element::fetchAllByFormID($form->getID());
        if (empty($elements)) {
            $this->addErrorMessage($translate->_("The specified form has no elements."));
            return false;
        }
        // If the assessment method for a particular group indicates that we allow validation to be skipped, set the flag.
        if (isset($this->dataset["assessment_method_meta"]["skip_validation"])
            && $this->dataset["assessment_method_meta"]["skip_validation"]
        ) {
            $skip_validation = true;
        } else {
            $skip_validation = false;
        }

        // Determine if this actor is blacklisted by the assessment for specific items (this effectively hides particular items from users)
        $skipped_invisible_responses = array();
        $apply_invisibility_mutator = false;
        $assessment_mutator_list = $this->buildFormMutatorListFromAssessmentOptions();
        if (!empty($assessment_mutator_list)) {
            if (array_key_exists("invisible", $assessment_mutator_list)
                && is_array($assessment_mutator_list["invisible"])
            ) {
                foreach ($assessment_mutator_list["invisible"] as $assessment_mutator_parameters) {
                    if ($assessment_mutator_parameters["type"] == $this->actor_type
                        && $assessment_mutator_parameters["value"] == $this->actor_proxy_id
                    ) {
                        $apply_invisibility_mutator = true;
                    }
                }
            }
        }
        $validated_data = array();
        foreach ($elements as $element) {
            $sanitized = array();
            $sanitized["element"]["afelement_id"] = $element->getID();
            $sanitized["element"]["response_type"] = $element->getElementType();
            $sanitized["element"]["element_type"] = $element->getElementType();
            $sanitized["element"]["element_id"] = $element->getElementID();
            $sanitized["element"]["is_mandatory"] = false;
            $sanitized["element"]["itemtype_shortname"] = null;
            $sanitized["responses"] = array();
            switch ($element->getElementType()) {
                case "item" :
                    if (!$item = Models_Assessments_Item::fetchRowByIDIncludeDeleted($element->getElementID())) {
                        if ($add_item_errors) {
                            $this->addErrorMessage(sprintf($translate->_("Item does not exist (%s)."), $element->getID()));
                        }
                        break;
                    }
                    if (!$itemtype_shortname = Entrada_Assessments_Workers_Item::fetchItemtypeShortnameByItemtypeID($item->getItemtypeID())) {
                        if ($add_item_errors) {
                            $this->addErrorMessage(sprintf($translate->_("Invalid item type (%s)."), $item->getItemtypeID()));
                        }
                        break;
                    }
                    if ($element->getRubricID()) {
                        // Since this item is part of a rubric, we adjust the "response_type" token.
                        // This is only for debug/audit purposes
                        $sanitized["element"]["response_type"] = "rubric";
                        $key = "rubric-item-{$element->getRubricID()}-{$element->getElementID()}";
                    } else {
                        $key = "item-{$element->getElementID()}";
                    }
                    $sanitized["element"]["itemtype_shortname"] = $itemtype_shortname;
                    $sanitized["element"]["is_mandatory"] = $item->getMandatory() ? true : false;
                    if ($skip_validation) {
                        // Override the mandatory setting if the skip validation flag is set
                        $is_mandatory = false;
                    } else {
                        $is_mandatory = $sanitized["element"]["is_mandatory"];
                    }
                    // Check if this item has an invisibility mutator, and if we're supposed to apply it.
                    if (in_array("invisible", Entrada_Assessments_Forms::buildItemMutatorListFromItemObject($item))
                        && $apply_invisibility_mutator
                    ) {
                        // Skip validation of invisible items
                        $is_mandatory = false;
                        $skipped_invisible_responses[] = $item->getID();
                    }
                    switch ($itemtype_shortname) {
                        case "horizontal_multiple_choice_single" :
                        case "vertical_multiple_choice_single" :
                        case "selectbox_single" :
                        case "user" : // not supported on front-end
                        case "rubric_line" :
                        case "scale" :
                            $sanitized["responses"] = $this->validateAndSanitizeSingleResponseItem($posted_data, $key, $item->getID(), $item->getCommentType(), $item->getItemText(), $item->getRatingScaleID(), $partial_update, $is_mandatory);
                            break;
                        case "horizontal_multiple_choice_multiple" :
                        case "vertical_multiple_choice_multiple" :
                        case "selectbox_multiple" :
                            $sanitized["responses"] = $this->validateAndSanitizeMultiResponseItem($posted_data, $key, $item->getID(), $item->getCommentType(), $item->getItemText(), $partial_update, $is_mandatory);
                            break;
                        case "free_text" :
                            $sanitized["responses"] = $this->validateAndSanitizeFreeTextItem($posted_data, $key, $item->getItemText(), $partial_update, $is_mandatory);
                            break;
                        case "numeric" :
                            $sanitized["responses"] = $this->validateAndSanitizeNumericItem($posted_data, $key, $item->getItemText(), $partial_update, $is_mandatory);
                            break;
                        case "date" :
                            $sanitized["responses"] = $this->validateAndSanitizeDateItem($posted_data, $key, $item->getItemText(), $partial_update, $is_mandatory);
                            break;
                        default:
                            // Not supported
                            if ($add_item_errors) {
                                $this->addErrorMessage(sprintf($translate->_("Unsupported item type (\"%s\")."), $itemtype_shortname ));
                            }
                            break;
                    }
                    break;
                case "objective" :
                    $key = "objective-{$element->getElementID()}";
                    if ($objective = Models_Objective::fetchRow($element->getElementID())) {
                        $objective_name = $objective->getName();
                    } else {
                        $objective_name = $translate->_("Objective Selector");
                    }
                    // Objective selectors are always "no comments" and "mandatory"
                    $sanitized["element"]["is_mandatory"] = true;
                    $sanitized["responses"] = $this->validateAndSanitizeObjectiveItem($posted_data, $key, $objective_name, $partial_update);
                    break;
            }
            $validated_data[$element->getID()] = $sanitized;
        }
        // Ensure that we are able to make the updates with the sanitized data
        foreach ($validated_data as $response_data) {
            if ($response_data["element"]["element_type"] == "item"
                && in_array($response_data["element"]["element_id"], $skipped_invisible_responses)
            ) {
                // Validation for this one was intentionally skipped.
                continue;
            }
            if ($response_data["responses"] === false) {
                // Validation failed on a response
                return false;
            }
            if (empty($response_data["responses"])
                && $response_data["element"]["is_mandatory"]
                && !$partial_update
                && !$skip_validation
            ) {
                // This is not a partial update, and a response is missing
                return false;
            }
        }
        return $validated_data;
    }

    /**
     * Save an approval record for the current assessment.
     *
     * @param string $approval_status
     * @param string|null $comments
     * @return bool
     */
    public function updateApproval($approval_status, $comments = null) {
        global $translate, $db;
        $this->fetchData();
        if (empty($this->dataset)) {
            $this->addErrorMessage($translate->_("Unable to fetch assessment data. Failed to set assessment approval status."));
            return false;
        }
        if (!$this->aprogress_id) {
            $this->addErrorMessage($translate->_("Invalid progress ID for assessment."));
            return false;
        }
        $approval_data = $this->getApprovalDataForProgress();
        if (empty($approval_data)) {
            $this->addErrorMessage($translate->_("This assessment does not require review."));
            return false;
        }
        if (empty($this->dataset["distribution"])) {
            $this->addErrorMessage($translate->_("This assessment cannot be reviewed."));
            return false;
        }
        if ($approval_data["approval_status"] != "pending") {
            $this->addErrorMessage($translate->_("This assessment has already been reviewed."));
            return false;
        }

        // First, check if the record already exists. If so, we update it. Otherwise, create it.
        $approval_found = new Models_Assessments_Distribution_Approvals();
        if ($approval_found = $approval_found->fetchRowByProgressIDDistributionID($this->aprogress_id, $this->dataset["distribution"]["distribution_record"]["adistribution_id"])) {
            $approval_found_data = $approval_found->toArray();
            $approval_found_data["approver_id"] = $this->actor_proxy_id;
            $approval_found_data["updated_date"] = time();
            $approval_found_data["updated_by"] = $this->actor_proxy_id;
            $approval_found_data["approval_status"] = $approval_status;
            if ($comments) {
                $approval_found_data["comments"] = $comments;
            }
            if (!$approval_found->fromArray($approval_found_data)->update()) {
                application_log("error", "Failed to update approval record for aprogress_id: {$this->aprogress_id}, db said: ". $db->ErrorMsg());
                $this->addErrorMessage($translate->_("There was an error updating the approval status for this assessment."));
                return false;
            }
            return true;

        } else {
            $data = array();
            $data["aprogress_id"] = $this->aprogress_id;
            $data["adistribution_id"] = $this->dataset["distribution"]["distribution_record"]["adistribution_id"];
            $data["approver_id"] = $approval_data["approver_proxy_id"];
            $data["approval_status"] = $approval_status;
            $data["comments"] = $comments;
            $data["created_date"] = time();
            $data["created_by"] = $this->actor_proxy_id;
            $approval_model = new Models_Assessments_Distribution_Approvals($data);
            if (!$approval_model->insert()) {
                application_log("error", "Failed to insert approval record for aprogress_id: {$this->aprogress_id}, db said: ". $db->ErrorMsg());
                $this->addErrorMessage($translate->_("There was an error updating the approval status for this assessment."));
                return false;
            }
            return true;

        }
    }

    /**
     * Save feedback data; creates and updates feedback as necessary.
     *
     * @param $assessor_feedback_response
     * @param $target_feedback_response
     * @param $assessor_id
     * @param $assessor_type
     * @param $target_id
     * @param $target_type
     * @param $comments
     * @param bool $finish_feedback
     * @return bool
     */
    public function updateFeedback($assessor_feedback_response, $target_feedback_response, $assessor_id, $assessor_type, $target_id, $target_type, $comments, $finish_feedback = false) {
        global $translate, $db;

        if ($assessor_feedback_response === null && $target_feedback_response === null) {
            $this->addErrorMessage($translate->_("Invalid feedback type specified."));
            return false;
        }
        if (!$assessment_record = $this->fetchAssessmentRecord($this->dassessment_id)) {
            $this->addErrorMessage($translate->_("Invalid assessment specified."));
            return false;
        }
        if ($this->isDistributionDeleted()) {
            $this->addErrorMessage($translate->_("This assessment's distribution has been deleted. Feedback can not be submitted."));
            return false;
        }
        if ($existing_feedback_record = Models_Assessments_AssessorTargetFeedback::fetchRowByAssessorTarget($this->dassessment_id, $assessor_type, $assessor_id, $target_type, $target_id)) {
            $method = "update";
            $feedback_record_data = $existing_feedback_record->toArray();
            $feedback_record_data["updated_date"] = time();
            $feedback_record_data["updated_by"] = $this->actor_proxy_id;
            if ($assessor_feedback_response !== null) {
                $feedback_record_data["assessor_feedback"] = $assessor_feedback_response;
            }
            if ($target_feedback_response !== null) {
                $feedback_record_data["target_progress_value"] = $finish_feedback ? "complete" : "inprogress";
                $feedback_record_data["target_feedback"] = $target_feedback_response;
                $feedback_record_data["comments"] = $comments;
            }

        } else {
            $method = "insert";
            $feedback_record = new Models_Assessments_AssessorTargetFeedback();
            $feedback_record_data = $feedback_record->toArray();
            $feedback_record_data["assessor_type"] = $assessor_type;
            $feedback_record_data["assessor_value"] = $assessor_id;
            $feedback_record_data["target_type"] = $target_type;
            $feedback_record_data["target_value"] = $target_id;
            $feedback_record_data["dassessment_id"] = $this->dassessment_id;
            $feedback_record_data["created_date"] = time();
            $feedback_record_data["created_by"] = $this->actor_proxy_id;
            if ($assessor_feedback_response !== null) {
                $feedback_record_data["assessor_feedback"] = $assessor_feedback_response;
            }
            if ($target_feedback_response !== null) {
                $feedback_record_data["target_progress_value"] = $finish_feedback ? "complete" : "inprogress";
                $feedback_record_data["target_feedback"] = $target_feedback_response;
                $feedback_record_data["comments"] = $comments;
            }
        }
        $new_record = new Models_Assessments_AssessorTargetFeedback($feedback_record_data);
        if ($method == "update") {
            if (!$new_record->update()) {
                $this->addErrorMessage($translate->_("An error occurred when attempting to save feedback."));
                application_log("error", "Failed to update Target feedback. DB said: ". $db->ErrorMsg() );
                return false;
            }
        } else {
            if (!$new_record->insert()) {
                $this->addErrorMessage($translate->_("An error occurred when attempting to save feedback."));
                application_log("error", "Failed to insert new Target feedback record. DB said: ". $db->ErrorMsg());
                return false;
            }
        }
        return true;
    }

    /**
     * Given some posted data, update the progress responses for a given assessment and progress ID.
     * If no progress ID is specified, a new one is created and this object is assigned to that value.
     * The posted data is sanitized before use (see sanitizeData() for what that data generally looks like).
     *
     * If $limit_inprogress is true, then progress will only be updated if one progress record for a given
     * assessment target is present.
     *
     * @param array $posted_data
     * @param int $target_id
     * @param string $target_type
     * @param bool $mark_complete
     * @param bool $posted_is_validated
     * @param bool $limit_inprogress
     * @return bool
     */
    public function updateProgress($posted_data, $target_id, $target_type, $mark_complete = false, $posted_is_validated = false, $limit_inprogress = true) {
        global $translate, $db;
        if (!$this->dassessment_id) {
            $this->addErrorMessage($translate->_("No assessment specified."));
            return false;
        }
        if (!$assessment_record = $this->fetchAssessmentRecord($this->dassessment_id)) {
            $this->addErrorMessage($translate->_("Unable to fetch assessment record."));
            return false;
        }
        if (!$assessment_target_record = Models_Assessments_AssessmentTarget::fetchRowByDAssessmentIDTargetTypeTargetValue($this->dassessment_id, $target_type, $target_id)) {
            $this->addErrorMessage($translate->_("Unable to fetch assessment target record."));
            return false;
        }
        if ($limit_inprogress) {
            // We must check and see if there's an in-progress record already.
            // We want there to only be one in-progress at a time (we artificially enforce this limitation here)
            $progress_records = Models_Assessments_Progress::fetchAllByDassessmentIDAssessorTypeAssessorValueTargetRecordIDTargetType(
                $assessment_record->getID(),
                $assessment_record->getAssessorType(),
                $assessment_record->getAssessorValue(),
                $target_id,
                $target_type,
                "inprogress"
            );
            if (!empty($progress_records)) {
                foreach ($progress_records as $found_progress_record) {
                    if ($found_progress_record->getID() != $this->aprogress_id
                        && !$found_progress_record->getDeletedDate()
                    ) {
                        $this->addErrorMessage($translate->_("An assessment task for this target is already in progress."));
                        return false;
                    }
                }
            }
        }
        if ($posted_is_validated) {
            // The posted data is already validated and sanitized, so use it directly.
            $sanitized = $posted_data;
        } else {
            // Otherwise, we sanitized what was given to us.
            $sanitized = $this->sanitizeData($posted_data, !$mark_complete); // If mark_complete then this is not a partial update
            if (!$sanitized || empty($sanitized)) {
                // sanitizeData will populate error messages
                return false;
            }
        }

        // Fetch or create a progress record for this progress data
        if ($this->aprogress_id) {
            // From this context, the aprogress_id should return a progress record that contains the target ID.
            // If the specified target ID does not match the progress record's target ID, then we assume the caller is wrong.
            if (!$progress_record = Models_Assessments_Progress::fetchRowByID($this->aprogress_id)) {
                $this->addErrorMessage($translate->_("Invalid progress record specified."));
                return false;
            }
            if ($progress_record->getProgressValue() == "complete") {
                $this->addErrorMessage($translate->_("This form has been submitted and cannot be updated."));
                return false;
            }
            if ($progress_record->getTargetRecordID() != $target_id || $progress_record->getTargetType() != $target_type) {
                $this->addErrorMessage($translate->_("The specified target does not match the current progress record."));
                return false;
            }
            $progress_record_data = $progress_record->toArray();
            $progress_record_data["updated_date"] = time();
            $progress_record_data["updated_by"] = $this->actor_proxy_id; // this is null when external (null means that we must assume the assessor_value/type was the user that did the updating)
            $progress_record->fromArray($progress_record_data);
            if (!$progress_record->update()) {
                $this->addErrorMessage($translate->_("Unable to update progress record."));
                application_log("error", "Failed to update progress record. DB said: " . $db->ErrorMsg());
                return false;
            }
            $aprogress_id = $this->aprogress_id;
        } else {
            $progress_construction = array(
                "dassessment_id" => $this->dassessment_id,
                "adistribution_id" => $assessment_record->getADistributionID(),
                "form_id" => $assessment_record->getFormID(),
                "assessor_type" => $assessment_record->getAssessorType(),
                "assessor_value" => $assessment_record->getAssessorValue(),
                "uuid" => Models_Assessments_Progress::generateUuid(),
                "progress_value" => "inprogress",
                "created_date" => time(),
                "created_by" => $this->actor_proxy_id, // will be null when an external assessor does the updating
                "target_record_id" => $target_id,
                "target_type" => $assessment_target_record->getTargetType()
            );
            $progress_record = new Models_Assessments_Progress($progress_construction);
            if (!$progress_record->insert()) {
                $this->addErrorMessage($translate->_("Unable to create new progress record."));
                application_log("error", "Failed to create progress record. DB said: " . $db->ErrorMsg());
                return false;
            }
            $aprogress_id = $progress_record->getID();
        }

        // Fetch all the current response records and mark them as deleted if they are not included in our posted dataset (meaning that the
        // user removed it from their answers on the form).
        $all_progress_responses = Models_Assessments_Progress_Response::fetchAllByAprogressIDIncludeDeleted($aprogress_id);
        $progress_response_updates = array();
        foreach ($all_progress_responses as $all_progress_response) {
            $progress_response_update = array(
                "afelement_id" => $all_progress_response->getAfelementID(),
                "iresponse_id" => $all_progress_response->getIresponseID(),
                "progress_response_record" => $all_progress_response,
                "to_delete" => true
            );
            // Find the corresponding progress response in the sanitized responses. Anything not there will be soft-deleted.
            foreach ($sanitized as $sanitized_responses_data) {
                if ($sanitized_responses_data["element"]["afelement_id"] == $all_progress_response->getAfelementID()) {
                    foreach ($sanitized_responses_data["responses"] as $sanitized_response) {
                        if ($sanitized_response["iresponse_id"] == $all_progress_response->getIresponseID()) {
                            $progress_response_update["to_delete"] = false; // found it
                        }
                    }
                }
            }
            $progress_response_updates[] = $progress_response_update;
        }

        $insert_values = array();
        $update_values = array();

        // Delete the records that aren't part of our sanitized set
        foreach ($progress_response_updates as $removal) {
            if ($removal["to_delete"]) {
                $progress_response_to_delete = $removal["progress_response_record"];
                $progress_response_to_delete->setDeletedDate(time());
                $update_values[] = $progress_response_to_delete->toArray();
            }
        }
        // Build single large insert and update statements for the new relevant records from our sanitized set.
        foreach ($sanitized as $sanitized_response_data) {
            if (!empty($sanitized_response_data["responses"])) {
                foreach ($sanitized_response_data["responses"] as $response_data) {
                    $response_record = false;
                    foreach ($all_progress_responses as $all_response) {
                        if ($all_response->getAfelementID() == $sanitized_response_data["element"]["afelement_id"] &&
                            $all_response->getIresponseID() == $response_data["iresponse_id"]
                        ) {
                            $response_record = $all_response;
                            break;
                        }
                    }
                    if ($response_record) {
                        // Use the existing record, undeleting it if necessary (by removing the deleted_date).
                        // Add to update statement values.
                        $response_record_data = $response_record->toArray();
                        $response_record_data["updated_date"] = time();
                        $response_record_data["updated_by"] = $this->actor_proxy_id;
                        $response_record_data["deleted_date"] = null;
                        $response_record_data["comments"] = array_key_exists("comments", $response_data) ? $response_data["comments"] : null;
                        $update_values[] = $response_record_data;
                    } else {
                        // Create new record.
                        // Add to insert statement values.
                        $response_construction = array(
                            "aprogress_id" => $aprogress_id,
                            "form_id" => $assessment_record->getFormID(),
                            "iresponse_id" => $response_data["iresponse_id"],
                            "afelement_id" => $sanitized_response_data["element"]["afelement_id"],
                            "comments" => array_key_exists("comments", $response_data) ? $response_data["comments"] : null,
                            "created_date" => time(),
                            "created_by" => $this->actor_proxy_id,
                            "assessor_type" => $assessment_record->getAssessorType(),
                            "assessor_value" => $assessment_record->getAssessorValue(),
                            "adistribution_id" => $assessment_record->getADistributionID()
                        );
                        $insert_values[] = $response_construction;
                    }
                }
            }
        }

        // Commit the inserts and updates.
        $values = array_merge($insert_values, $update_values);
        if (!empty($values)) {
            $progress_response_model = new Models_Assessments_Progress_Response();
            $result = $progress_response_model->bulkInsertOnDuplicateKeyUpdate($values);
            if (!$result) {
                $this->addErrorMessage($translate->_("Unable to save responses."));
                application_log("error", "Failed to insert or update progress response records. DB said: " . $db->ErrorMsg());
                return false;
            }
        }


        // Save/update the progress ID
        $this->aprogress_id = $aprogress_id;

        // Responses are inserted. If we're marking as complete, we set the progress record to "complete"
        if ($aprogress_id && $progress_record && $mark_complete) {
            $progress_record_data = $progress_record->toArray();
            $progress_record_data["progress_value"] = "complete";
            $progress_record_data["updated_date"] = time();
            $progress_record_data["updated_by"] = $this->actor_proxy_id; // null when external assessor
            $progress_record->fromArray($progress_record_data);
            if (!$progress_record->update()) {
                $this->addErrorMessage($translate->_("Failed to update progress record."));
                application_log("error", "Failed to update progress record. DB said: " . $db->ErrorMsg());
                return false;
            }
        }
        return true;
    }

    /**
     * Create a new assessment record and associated target records.
     * The assessment data given should correspond to those of the assessments_assessor model.
     *
     * @param array $assessment_record_data
     * @param array $assessment_targets_data
     * @return bool
     */
    public function create($assessment_record_data, $assessment_targets_data = array()) {
        global $translate, $db;
        if (!$this->validateActor()) {
            return false;
        }

        if (!is_array($assessment_record_data)
            || !is_array($assessment_targets_data)
            || empty($assessment_record_data)
        ) {
            $this->addErrorMessage($translate->_("Unable to create assessment. Required data missing."));
            return false;
        }

        if (!isset($assessment_record_data["form_id"])) {
            $this->addErrorMessage($translate->_("Form ID is required."));
            return false;
        }

        if (!isset($assessment_record_data["assessor_value"])) {
            $this->addErrorMessage($translate->_("Assessor value is required."));
            return false;
        }

        // Default assessment record creation settings
        $default_assessment_data = array(

            /* Primary key, null to create new record */
            "dassessment_id" => null,

            /* Required, but set null as placeholder */
            "assessor_value" => null,
            "form_id" => null,
            "course_id" => null,

            /* Should be set, if the assessor_value is not a proxy_id (i.e., external_hash) */
            "assessor_type" => "internal", // or "external"

            /* Should be set when the target is not a proxy_id */
            "associated_record_id" => null,
            "associated_record_type" => "proxy_id",

            /* These are default values, but may be different depending on
             * organisation. If so, they should be specified */
            "assessment_type_id" => 1,
            "assessment_method_id" => 1,
            "organisation_id" => $this->actor_organisation_id,

            /* Optional, defaults are OK in most cases. */
            "assessment_method_data" => null,
            "adistribution_id" => null,
            "number_submitted" => null,
            "min_submittable" => null,
            "max_submittable" => null,
            "feedback_required" => 0,
            "published" => 1,
            "start_date" => 0,
            "end_date" => 0,
            "rotation_start_date" => null,
            "rotation_end_date" => null,
            "expiry_date" => null,
            "expiry_notification_date" => null,
            "external_hash" => null,
            "forwarded_from_assessment_id" => null,
            "forwarded_date" => null,
            "forwarded_by" => null,
            "additional_assessment" => 0,
            "encounter_date" => null,
            "delivery_date" => time(),
            "created_date" => time(),
            "created_by" => $this->actor_proxy_id,
            "updated_date" => null,
            "updated_by" => null,
            "deleted_date" => null
        );
        $assessment_data = array_merge($default_assessment_data, $assessment_record_data);
        $assessment_model = new Models_Assessments_Assessor();
        $assessment_model->fromArray($assessment_data);
        if (!$assessment_model->insert()) {
            $this->addErrorMessage($translate->_("Failed to add new assessment record."));
            application_log("error", "Unable to insert new assessment record! DB said: " . $db->ErrorMsg());
            return false;
        }
        // Successfully created a new assessment record from the given data. Set the object stale so that subsequent fetches provide fresh datasets.
        $this->setStale();
        $this->dassessment_id = $assessment_model->getID();

        // Create assessment targets. This step is optional; they can be be added later.
        // However, and assessment is not valid until the targets are defined.
        if (!empty($assessment_targets_data)) {
            foreach ($assessment_targets_data as $target) {
                if (!$this->createTarget($target)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Create an assessment target for the current assessment ID.
     * Will always default to atarget_id = null.
     *
     * @param $target_data
     * @return bool
     */
    public function createTarget($target_data) {
        global $translate, $db;
        if (!$this->validateActor()) {
            return false;
        }
        if (!$this->dassessment_id) {
            $this->addErrorMessage($translate->_("Cannot create assessment without a valid assessment ID"));
            return false;
        }
        if (!is_array($target_data) || empty($target_data)) {
            $this->addErrorMessage($translate->_("No target data supplied."));
            return false;
        }
        if (!isset($target_data["target_value"])) {
            $this->addErrorMessage($translate->_("Target value is required."));
            return false;
        }
        $default_target_data = array(

            /* Primary key, null to create a new record */
            "atarget_id" => null,

            /* Required */
            "target_value" => null,

            /* Should be set if target is not a proxy ID */
            "target_type" => "proxy_id",

            /* Required, but default is OK */
            "task_type" => "assessment",
            "dassessment_id" => $this->dassessment_id,
            "created_date" => time(),
            "created_by" => $this->actor_proxy_id,

            /* Optional */
            "adistribution_id" => null,
            "updated_date" => null,
            "updated_by" => null,
            "deleted_date" => null,
            "deleted_by" => null,
            "deleted_reason_id" => null,
            "deleted_reason_notes" => null,
            "visible" => 1,

            /* Obsolete fields */
            "delegation_list_id" => null,
            "associated_schedules" => null
        );
        // Copy the given data into the default, and create that record.
        $target_merged = array_merge($default_target_data, $target_data);
        $target_merged["atarget_id"] = null; // in case target_data included an atarget_id.
        $target_model = new Models_Assessments_AssessmentTarget($target_merged);
        if (!$target_model->insert()) {
            $this->addErrorMessage($translate->_("Unable to save target record"));
            application_log("error", "Unable to insert new assessment record! DB said: " . $db->ErrorMsg());
            return false;
        }

        $this->createAssessmentOptions("target", $target_model->toArray());

        $this->setStale();
        return true;
    }

    /**
     * Create assessment options for the current assessment.
     *
     * @param string $mode The context from which to apply assessment options
     * @param null $data Optional additional data
     * @return bool success
     */
    public function createAssessmentOptions($mode, $data = null) {
        global $translate, $db;
        if (!$this->validateActor()) {
            return false;
        }
        $this->fetchData();
        if (empty($this->dataset)) {
            $this->addErrorMessage($translate->_("Unable to fetch dataset."));
            return false;
        }
        switch ($mode) {
            case "target":
                // Assessment options to be applied from target creation context.
                if (is_array($data)
                    && array_key_exists("task_type", $data)
                    && $data["task_type"] == "evaluation"
                ) {
                    $assessment_option_model = new Models_Assessments_Options();
                    // Evaluation data (identifiable info) visibility for the task's organisation.
                    $evaluation_setting = Entrada_Settings::fetchByShortname("evaluation_data_visible", $this->dataset["assessment"]["organisation_id"]);
                    if ($evaluation_setting) {
                        $exists = $assessment_option_model->fetchRowByDAssessmentIDOptionName($this->dassessment_id, "data_visible", ($evaluation_setting->getValue() == 1 ? "true" : "false"));
                        if (!$exists) {
                            $option_data = array(
                                "dassessment_id"        => $this->dassessment_id,
                                "adistribution_id"      => $this->dataset["assessment"]["adistribution_id"],
                                "option_name"           => "data_visible",
                                "option_value"          => "false",
                                "assessment_siblings"   => null,
                                "created_date"          => time(),
                                "created_by"            => $this->actor_proxy_id,
                                "updated_date"          => null,
                                "updated_by"            => null,
                                "deleted_date"          => null
                            );
                            if (!$assessment_option_model->fromArray($option_data)->insert()) {
                                $this->addErrorMessage($translate->_("Unable to save assessment option record"));
                                application_log("error", "Unable to insert new assessment option record. DB said: " . $db->ErrorMsg());
                                return false;
                            }
                        }
                    }
                }
                break;
            case "individual_json_options":
                /**
                 * In this context, we are applying arbitrary assessment options to the assessment, encoded in json.
                 * The data is presumed indexed by the name of the option.
                 */
                if (is_array($data) && !empty($data)) {
                    foreach ($data as $option_name => $option_value) {
                        $status = $this->insertAssessmentOptionRecord(
                            array(
                                "option_name" => $option_name,
                                "option_value" => json_encode($option_value)
                            )
                        );
                        if (!$status) {
                            $this->addErrorMessage($translate->_("Unable to create assessment option."));
                            return false;
                        }
                    }
                }
                break;

            default:
                break;
        }
        return true;
    }

    /**
     * Duplicate the current assessment record and targets (or specific targets).
     * Resets the internal pointers and dataset to the newly copied assessment.
     *
     * The targets array is a multidimensional array containing target_value, target_type and atarget_id.
     *
     * Optionally, copy the progress records to the new assessment as well.
     *
     * @param int $assessor_id
     * @param string $assessor_type
     * @param array $targets
     * @param array $replacement_data
     * @param bool $copy_progress
     * @param bool|string $progress_value_override
     * @param bool $copy_assessment_options
     * @return bool
     */
    public function copy($assessor_id, $assessor_type, $targets = array(), $replacement_data = array(), $copy_progress = true, $progress_value_override = false, $copy_assessment_options = true) {
        global $translate;
        if (!$this->validateActor()) {
            return false;
        }
        $assessment_data = $this->fetchData();
        if (empty($assessment_data)) {
            $this->addErrorMessage($translate->_("Unable to fetch assessment data to copy."));
            return false;
        }

        $original_assessment_id = $this->dassessment_id;

        $merged_data = array_merge($assessment_data["assessment"], $replacement_data);

        // Make sure assessment_method_data is a JSON string
        if (isset($merged_data["assessment_method_data"]) && is_array($merged_data["assessment_method_data"])) {
            $merged_data["assessment_method_data"] = json_encode($merged_data["assessment_method_data"]);
        }

        // Overwrite some of the data, regardless of whether it was given to us via replacement_data array.
        $merged_data["assessor_value"] = $assessor_id;
        $merged_data["assessor_type"] = $assessor_type;
        $merged_data["dassessment_id"] = null; // clear assessment ID since we're making a new one

        // We don't allow duplicating the previous record's timestamps
        $merged_data["created_date"] = time();
        $merged_data["created_by"] = $this->actor_proxy_id;
        $merged_data["updated_date"] = null;
        $merged_data["updated_by"] = null;

        // Create the assessment record and target record(s)
        $this->create($merged_data, $targets); // Create the assessment
        // $this->dassessment_id is set by create()

        // Create an assessment linkage (since we created this assessment from an existing one)
        $assessments_link_model = new Models_Assessments_Link();
        $assessment_link = array(
            "originating_id" => $original_assessment_id,
            "linked_id" => $this->dassessment_id, // current dassessment_id is the copy
            "created_date" => time(),
            "created_by" => $this->actor_proxy_id,
            "updated_date" => null,
            "updated_by" => null
        );
        if (!$assessments_link_model->fromArray($assessment_link)->insert()) {
            $this->addErrorMessage($translate->_("Failed to create assessment link."));
            return false;
        }

        // Copy the progress for the given targets.
        if ($copy_progress) {
            // If no target ids were specified, we copy ALL progress
            // Otherwise, we copy only the specific progress records for the given targets
            foreach ($assessment_data["progress"] as $progress) {
                if (!empty($targets)) {
                    $found = false;
                    foreach ($targets as $specified_target) {
                        // Target type not defined? FIX
                        if ($specified_target["target_type"] == $progress["target_type"]
                            && $specified_target["target_value"] == $progress["target_record_id"]
                        ) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        // The current progress record isn't in our list of targets we want to copy.
                        continue;
                    }
                }
                $new_progress = $progress;
                $new_progress["aprogress_id"] = null;
                $new_progress["dassessment_id"] = $this->dassessment_id;
                $new_progress["uuid"] = Models_Assessments_Progress::generateUuid();
                $new_progress["created_date"] = time();
                $new_progress["created_by"] = $this->actor_proxy_id;
                $new_progress["updated_date"] = null;
                $new_progress["updated_by"] = null;
                $new_progress["progress_value"] = $progress_value_override ? $progress_value_override : $progress["progress_value"];
                $new_progress["assessor_value"] = $assessor_id;
                $new_progress["assessor_type"] = $assessor_type;

                $progress_record = new Models_Assessments_Progress($new_progress);
                if (!$progress_record->insert()) {
                    $this->addErrorMessage($translate->_("Unable to copy progress record."));
                    return false;
                }
                $this->aprogress_id = $progress_record->getID(); // will setStale() when done

                // Copy all of the progress responses.
                foreach ($progress["progress_responses"] as $progress_response) {
                    $new_progress_response = $progress_response;
                    $new_progress_response["epresponse_id"] = null;
                    $new_progress_response["aprogress_id"] = $progress_record->getID();
                    $new_progress_response["assessor_type"] = $assessor_type;
                    $new_progress_response["assessor_value"] = $assessor_id;
                    $new_progress_response["created_date"] = time();
                    $new_progress_response["created_by"] = $this->actor_proxy_id;
                    $new_progress_response["updated_date"] = null;
                    $new_progress_response["updated_by"] = null;
                    $progress_response_record = new Models_Assessments_Progress_Response($new_progress_response);

                    if (!$progress_response_record->insert()) {
                        $this->addErrorMessage($translate->_("Failed to copy progress response record."));
                        return false;
                    }
                }
            }
        }
        // Copy any assessment options for the previous assessment to this one.
        if ($copy_assessment_options) {
            $assessment_options_model = new Models_Assessments_Options();
            $old_options = $assessment_options_model->fetchAllByDassessmentID($original_assessment_id);
            if (!empty($old_options)) {
                foreach ($old_options as $option) {
                    $new_option_data = $option->toArray();
                    $new_option_data["daoption_id"] = null;
                    $new_option_data["dassessment_id"] = $this->dassessment_id;
                    if (!$assessment_options_model->fromArray($new_option_data)->insert()) {
                        // We're not going to return false here, but we do need to log the failure.
                        application_log("error", "Failed saving assessment option for old assessment id '$original_assessment_id', to new assessment id '{$this->dassessment_id}'");
                    }
                }
            }
        }
        $this->setStale(); // flag dataset refresh
        return true;
    }

    /**
     * Delete this assessment. Marks the current assessment record as deleted (does not delete targets).
     *
     * @return bool
     */
    public function delete() {
        global $translate;
        if (!$this->validateActor()) {
            return false;
        }
        $this->fetchData();
        if (empty($this->dataset)) {
            return false;
        }
        $assessment_data = $this->dataset["assessment"];
        $assessment_data["assessment_method_data"] = json_encode($assessment_data["assessment_method_data"]);
        $assessment_record = new Models_Assessments_Assessor($assessment_data);
        $assessment_record->setDeletedDate(time());
        $assessment_record->setUpdatedBy($this->actor_proxy_id);
        $assessment_record->setUpdatedDate(time());
        if (!$assessment_record->update()) {
            $this->addErrorMessage($translate->_("Failed to delete assessment"));
            error_log("error", "Failed to delete assessment :" . $this->dataset["assessment"]["dassessment_id"]);
            return false;
        }
        return true;
    }

    /**
     * Delete a single assessment target (mark its record deleted). Does not delete the overall
     * assessment when there are no targets left.
     *
     * @param $atarget_id
     * @param $reason_id
     * @param $reason_text
     * @return bool
     */
    public function deleteTarget($atarget_id, $reason_id = null, $reason_text = null) {
        global $translate;
        if (!$this->validateActor()) {
            return false;
        }
        $this->fetchData();
        if (empty($this->dataset)) {
            return false;
        }
        foreach ($this->dataset["targets"] as $target) {
            if ($target["atarget_id"] == $atarget_id) {
                // delete this target.
                $target["deleted_date"] = time();
                $target["deleted_by"] = $this->actor_proxy_id;
                $target["updated_date"] = time();
                $target["updated_by"] = $this->actor_proxy_id;
                $target["deleted_reason_id"] = $reason_id;
                $target["deleted_reason_notes"] = $reason_text;
                $target_record = new Models_Assessments_AssessmentTarget($target);
                if (!$target_record->update()) {
                    $this->addErrorMessage($translate->_("Unable to delete target."));
                    return false;
                } else {
                    $this->setStale();
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Fetch all related data points, return in a data structure.
     *
     * @return false|array
     */
    public function fetchData() {
        if (empty($this->dataset)) {
            $this->dataset = $this->buildDataset(); // can return empty array
        } else {
            if (!array_key_exists("is_stale", $this->dataset) || $this->dataset["is_stale"]) {
                $this->dataset = $this->buildDataset();
            }
        }
        return $this->dataset;
    }

    /**
     * Return whether this is deleted or not.
     *
     * @return bool
     */
    public function isDeleted() {
        $this->fetchData();
        if (empty($this->dataset)) {
            return false;
        }
        if ($this->dataset["meta"]["is_deleted"]) {
            return true;
        }
        if ($this->aprogress_id) {
            $current_progress = $this->getCurrentProgress();
            if (!empty($current_progress)) {
                if ($current_progress["progress_value"] == "cancelled" || $current_progress["progress_value"] == null) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Return whether this assessment has expired or not.
     *
     * @return bool
     */
    public function isExpired() {
        $this->fetchData();
        if (empty($this->dataset)) {
            return false;
        }
        if ($this->dataset["meta"]["is_expired"]) {
            return true;
        }
        return false;
    }

    /**
     * Return whether this assessment has been forwarded or not.
     * If another assessment record has this ID as their forwarded_from_assessment_id, then this record has been forwarded.
     *
     * @return bool
     */
    public function isForwarded() {
        $this->fetchData();
        if (empty($this->dataset)) {
            return false;
        }
        if (!empty($this->dataset["forwarded"])) {
            return true;
        }
        return false;
    }

    /**
     * Return whether a distribution is deleted or not.
     * Returns true when distribution record exists and is deleted.
     * Returns false when distribution either exists and is not deleted, or does not
     * exist at all (distribution-less assessments technically don't have deleted distributions).
     *
     * @return bool
     */
    public function isDistributionDeleted() {
        $this->fetchData();
        if (empty($this->dataset)) {
            return false;
        }
        if (isset($this->dataset["distribution"]["distribution_record"])) {
            if (is_array($this->dataset["distribution"]["distribution_record"]) &&
                array_key_exists("deleted_date", $this->dataset["distribution"]["distribution_record"])
            ) {
                if ($this->dataset["distribution"]["distribution_record"]["deleted_date"]) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Return whether the current assessment is completed (for the given progress ID).
     * If no progress ID is given, then the assessment is considered to be incomplete.
     *
     * @param int|bool $specified_progress_id
     * @return bool
     */
    public function isCompleted($specified_progress_id = false) {
        $this->fetchData();
        if (empty($this->dataset)) {
            return false;
        }
        if ($specified_progress_id) {
            foreach ($this->dataset["progress"] as $progress_data) {
                if ($progress_data["progress_value"] == "complete" && $progress_data["aprogress_id"] == $specified_progress_id) {
                    return true;
                }
            }
        } else {
            if (!$this->aprogress_id) {
                return false;
            }
            foreach ($this->dataset["progress"] as $progress_data) {
                if ($progress_data["progress_value"] == "complete" &&
                    $progress_data["aprogress_id"] == $this->aprogress_id
                ) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Fetch feedback required flag.
     *
     * @return bool
     */
    public function isFeedbackRequired() {
        $this->fetchData();
        if (empty($this->dataset)) {
            return false;
        }
        if ($this->dataset["assessment"]["feedback_required"]) {
            return true;
        }
        return false;
    }

    /**
     * Return whether the current assessment is external or not.
     * By default, it is not external until an external assessor is saved.
     *
     * @return bool
     */
    public function isExternal() {
        $this->fetchData();
        if (empty($this->dataset)) {
            return false;
        }
        return isset($this->dataset["assessment"]["external_hash"]) ? true : false;
    }

    /**
     * Build a list of form mutators based on the assessment options for this assessment.
     * These mutators affect the behaviour of, and which items are rendered and validated on, the form.
     *
     * @param bool $mutator_names_only
     * @param bool $limit_to_actor
     * @return array
     */
    public function buildFormMutatorListFromAssessmentOptions($mutator_names_only = false, $limit_to_actor = true) {
        if (!$this->dassessment_id) {
            return array();
        }
        $assessment_data = $this->fetchData();
        if (empty($assessment_data)) {
            return array();
        }
        $mutators = array();
        foreach ($assessment_data["assessment_options"] as $assessment_option) {

            /**
             * We're looking specificially for assessment options that pertain to form or item mutation.
             * They correspond to the item attributes field on the item record.
             */
            switch ($assessment_option["option_name"]) {

                case "items_invisible_to":

                    /**
                     * This assesment option enables "item invisibility" mutator:
                     * Only items with "invisible" as a mutator (specified in their attributes field) are affected by this.
                     */
                    $decoded_options = json_decode(@$assessment_option["option_value"], true);
                    if (is_array($decoded_options) && !empty($decoded_options)) {
                        foreach ($decoded_options as $decoded_option) {
                            if (array_key_exists("type", $decoded_option)
                                && array_key_exists("value", $decoded_option)
                            ) {
                                $option_type = $decoded_option["type"];
                                $option_value = $decoded_option["value"];
                                if ($limit_to_actor) {
                                    if ($option_type == $this->actor_type
                                        && ($option_value == $this->actor_proxy_id)
                                    ) {
                                        // Valid entry found, store it in the "invisible" mutators list.
                                        $mutators["invisible"][] = array(
                                            "type" => $option_type,
                                            "value" => $option_value
                                        );
                                    }
                                } else {
                                    // Valid entry found, store it in the "invisible" mutators list.
                                    $mutators["invisible"][] = array(
                                        "type" => $option_type,
                                        "value" => $option_value
                                    );
                                }
                            }
                        }
                    }
                    break;

                /**
                 * We can support more mutators here.
                 */

                default:
                    break;
            }
        }
        if (!empty($mutators) && $mutator_names_only) {
            return array_keys($mutators);
        }
        return $mutators;
    }

    /**
     * Return whether this actor_proxy_id can complete the assessment.
     * External assessors use a different method to determine if
     * they can complete the assessment.
     *
     * An actor can complete the assessment if they are the following:
     *   - Site admin
     *   - The assessor
     *   - Course Owner (PA/PC) if distribution-based
     *
     * All others return false.
     *
     * @param bool $is_admin
     * @param $specified_target_id
     * @param $specified_target_type
     * @return bool
     */
    public function canActorComplete($is_admin = false, $specified_target_type = null, $specified_target_id = false) {
        global $translate;
        // The actor is administrator
        if ($is_admin) {
            return true;
        }
        $this->fetchData();
        if (empty($this->dataset)) {
            return false; // No dataset, can't complete.
        }
        if (empty($this->dataset["assessor"])) {
            $this->addErrorMessage($translate->_("No assessor found for this assessment."));
            return false;
        }
        // This actor is a course owner for the related distribution
        foreach ($this->dataset["distribution"]["course_owners"] as $owner) {
            if ($this->actor_proxy_id == $owner["proxy_id"]) {
                return true;
            }
        }
        // This actor is the assessor.
        if ($this->actor_proxy_id == $this->dataset["assessor"]["assessor_id"]) {
            return true;
        }
        //Check if the actor is the target
        $current_target = $this->getCurrentTarget($specified_target_id, $specified_target_type);
        $actor_is_not_target = ($current_target["target_type"] != "proxy_id"
            || ($current_target["target_type"] == "proxy_id" && $current_target["target_record_id"] != $this->actor_proxy_id));

        // Check if this actor is a course owner for the assessment.
        foreach ($this->dataset["assessment_course_owners"] as $course_owner_data) {
            if ($actor_is_not_target) {
                if ($course_owner_data["proxy_id"] == $this->actor_proxy_id) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Return whether this actor_proxy_id can view the assessment.
     * External assessors use a different method to determine if
     * they can view the assessment.
     *
     * An actor can view this assessment if they are:
     *   - Site admin
     *   - The assessor
     *   - Course Owner (PA/PC)
     *   - The target when:
     *      - Assessment is completed
     *      - & Assessment is released
     *   - Academic Advisors where the target is one of theirs
     *
     * @param bool $dependency_override_primary
     * @param bool $dependency_override_secondary
     * @param $specified_target_type
     * @param $specified_target_id
     * @return bool
     */
    public function canActorView($dependency_override_primary = false, $dependency_override_secondary = false, $specified_target_type = null, $specified_target_id = false) {
        if ($dependency_override_primary) {
            // We override this check if a dependency outside of this scope calls for it.
            // This can be that the caller is an admin, etc.
            return true;
        }
        if (!$this->actor_proxy_id) {
            // Actor proxy ID is not set; this actor is an external user.
            // We return false here, as this check should only be applied when actor_proxy_id is set.
            return false;
        }
        $this->fetchData();
        if (empty($this->dataset)) {
            return false; // No dataset, can't view without it.
        }
        // This actor is the assessor.
        if ($this->dataset["assessor"]["assessor_id"] == $this->actor_proxy_id) {
            return true;
        }

        $current_target = $this->getCurrentTarget($specified_target_id, $specified_target_type);
        $actor_is_not_target = ($current_target["target_type"] != "proxy_id"
            || ($current_target["target_type"] == "proxy_id" && $current_target["target_record_id"] != $this->actor_proxy_id));

        // The actor is a reviewer for the distribution (flagged responses).
        foreach ($this->dataset["distribution"]["distribution_reviewers"] as $reviewer_data) {
            if ($actor_is_not_target) {
                if ($reviewer_data["proxy_id"] == $this->actor_proxy_id) {
                    return true;
                }
            }
        }

        // Check assessment options, which take priority over the rest of the options that follow.
        $target_visibility_processed = false;
        if (!empty($this->dataset["assessment_options"])) {
            foreach ($this->dataset["assessment_options"] as $assessment_option) {

                if ($assessment_option["deleted_date"]) {
                    continue;
                }
                // There should always be an option value, otherwise this is invalid data.
                if (!isset($assessment_option["option_value"])) {
                    return false;
                }

                // "Catch all" data visibility options.
                // data_visible being false means everyone but the assessor and admins cannot see the data.
                if ($assessment_option["option_name"] == "data_visible"
                    && $assessment_option["option_value"] == "false"
                    && $this->actor_proxy_id != $this->dataset["assessor"]["assessor_id"]
                ) {
                    return false;
                }

                // Target self-visibility options.
                if ($current_target["target_type"] == "proxy_id" &&
                    $current_target["target_record_id"] == $this->actor_proxy_id
                ) {
                    if ($assessment_option["option_name"] == "target_viewable") {
                        $target_visibility_processed = true;
                        if ($assessment_option["option_value"] == "false") {
                            return false;
                        }
                    }

                    if ($assessment_option["option_name"] == "target_viewable_percent") {
                        $target_visibility_processed = true;

                        $assessment_option_model = new Models_Assessments_Options();
                        $completion_percentage = $assessment_option_model->fetchTargetOptionProgressPercentage($assessment_option["daoption_id"], $this->actor_proxy_id);
                        if ($completion_percentage < $assessment_option["option_value"]) {
                            return false;
                        }
                    }
                }
            }
        }

        if ($dependency_override_secondary) {
            if ($actor_is_not_target) {
                // We override this check if a dependency outside of this scope calls for it.
                // This can be that the caller is an academic resource or other reason.
                return true;
            }
        }

        // Check if this actor is a course owner for the related distribution.
        foreach ($this->dataset["distribution"]["course_owners"] as $course_owner_data) {
            if ($actor_is_not_target) {
                if ($course_owner_data["proxy_id"] == $this->actor_proxy_id) {
                    return true;
                }
            }
        }
        // Check if this actor is a course owner for the assessment.
        foreach ($this->dataset["assessment_course_owners"] as $course_owner_data) {
            if ($actor_is_not_target) {
                if ($course_owner_data["proxy_id"] == $this->actor_proxy_id) {
                    return true;
                }
            }
        }

        // Without a progress ID, there's nothing to view, if we're at this stage.
        // Targets cannot view the assessment that is in progress.
        $current_progress = $this->getCurrentProgress();
        if (empty($current_progress)) {
            return false;
        }
        if ($current_progress["progress_value"] == "complete") {
            if (empty($current_target)) {
                return false;
            }
            $approvers_count = 0;

            // Check if the current actor is the approver. This only applies to distribution-based assessments.
            if (!empty($this->dataset["distribution"])) {
                $approvers_count = count($this->dataset["distribution"]["distribution_approvers"]);
                foreach ($this->dataset["distribution"]["distribution_approvers"] as $approver) {
                    if ($this->dataset["distribution"]["distribution_record"]["adistribution_id"] == $approver["adistribution_id"]) {
                        // there is an approver for this distribution
                        if ($approver["proxy_id"] == $this->actor_proxy_id) {
                            return true; // found him
                        }
                    }
                }
            }

            // Check if the current actor is the target;
            if ($current_target["target_type"] == "proxy_id" &&
                $current_target["target_record_id"] == $this->actor_proxy_id
            ) {
                // People can never see evaluations submitted on them.
                if (!$target_visibility_processed && $current_target["task_type"] == "evaluation") {
                    return false;
                }

                // Only allow access if it's been released
                if (empty($this->dataset["distribution"])) {
                    // No distribution-based extra information to check. At this point, the target can view the completed assessment.
                    return true;
                } else {
                    // Distribution-based: check if there is an approval pending
                    if ($approvers_count == 0) {
                        return true;
                    } else {
                        if (empty($this->dataset["distribution"]["distribution_approvals"])) {
                            // Approvers haven't completed their reviews yet.
                            return false;
                        } else {
                            // There have been some approvals made, so check if this progress record was approved.
                            foreach ($this->dataset["distribution"]["distribution_approvals"] as $approval) {
                                if ($approval["aprogress_id"] == $current_progress["aprogress_id"]) {
                                    if ($approval["approval_status"] == "approved") {
                                        // The progress record has been reviewed/approved
                                        return true;
                                    } else {
                                        // Otherwise, approval status is "pending" or "hidden"
                                        return false;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * Fetch the objectives that are currently selected on this assessment, for the current progress ID.
     * No progress ID = no selections.
     *
     * @return array|bool
     */
    public function getSelectedObjectiveHierarchy() {
        if (!$this->aprogress_id) {
            return array(); // No progress ID, no data to return (it's OK to return empty array here).
        }
        $this->fetchData();
        if (empty($this->dataset)) {
            return false; // Failed to fetch dataset
        }
        $found_objectives = array();
        // Find any relevant objectives selected.
        foreach ($this->dataset["progress"] as $progress_data) {
            if ($progress_data["aprogress_id"] == $this->aprogress_id) {
                foreach ($progress_data["progress_responses"] as $response_data) {
                    if (!$form_element = Models_Assessments_Form_Element::fetchRowByID($response_data["afelement_id"])) {
                        continue;
                    }
                    if ($form_element->getElementType() != "objective") {
                        continue;
                    }
                    if (!$item = Models_Assessments_Item::fetchItemByResponseID($response_data["iresponse_id"])) {
                        continue; // item not found (maybe deleted?)
                    }
                    if (!$item_objective = Models_Assessments_Item_Objective::fetchRowByItemID($item->getID())) {
                        continue; // Item Objective not found
                    }
                    if (!$objective = Models_Objective::fetchRow($item_objective->getObjectiveID())) {
                        continue; // Objective not found
                    }
                    $item_objective->buildObjectiveList($objective->getParent(), $item_objective->getObjectiveID());
                    if (!empty($item_objective->objective_tree)) {
                        asort($item_objective->objective_tree);
                        foreach ($item_objective->objective_tree as $objective_id) {
                            if ($tmp_input = clean_input($objective_id, array("trim", "int"))) {
                                $found_objectives[$form_element->getID()][] = $tmp_input;
                            }
                        }
                    }
                }
            }
        }
        return $found_objectives;
    }

    /**
     * Determine if this assessment is deleted.
     *
     * @param int|bool $specified_id
     * @return bool
     */
    protected function determineDeleted($specified_id = false) {
        if ($id = $this->whichID("dassessment_id", $specified_id)) {
            if ($assessment_record = $this->fetchAssessmentRecord($specified_id)) {
                if ($assessment_record->getDeletedDate()) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Determine if this assessment is expired.
     *
     * @param int|bool $specified_id
     * @return bool
     */
    protected function determineExpired($specified_id = false) {
        if ($id = $this->whichID("dassessment_id", $specified_id)) {
            if ($assessment_record = $this->fetchAssessmentRecord($specified_id)) {
                if ($assessment_record->getExpiryDate() && ($assessment_record->getExpiryDate() <= time())) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Determine whether this assessment is external or not.
     *
     * @param int|bool $specified_id
     * @return bool
     */
    protected function determineExternal($specified_id = false) {
        if ($id = $this->whichID("dassessment_id", $specified_id)) {
            if ($assessment_record = $this->fetchAssessmentRecord($specified_id)) {
                if ($assessment_record->getExternalHash()) {
                    return true;
                }
            }
        }
        return false;
    }

    //-- Private Dataset methods --//

    /**
     * Assemble the related data for a specified assessment.
     * Fetches data based on the specified assessment_id.
     *
     * @return bool|array
     */
    private function buildDataset() {
        global $translate;
        if (!$this->dassessment_id) {
            return array();
        }
        $assessment_id = $this->dassessment_id;
        $assessment_record = $this->fetchAssessmentRecord($assessment_id);
        if (!$assessment_record) {
            // Record does not exist.
            application_log("error", "buildDataset: attempted fetch row for assessment with invalid ID (dassessment_id = '{$assessment_id}')");
            $this->addErrorMessage($translate->_("Assessment was not found."));
            return false;
        }
        $assessment_data = $this->buildDefaultAssessmentStructure($assessment_id);
        $assessment_data["meta"]["dassessment_id"] = $assessment_id;
        $assessment_data["meta"]["form_id"] = $assessment_record->getFormID();
        $assessment_data["meta"]["adistribution_id"] = $assessment_record->getAdistributionID();
        $assessment_data["meta"]["external_hash"] = $assessment_record->getExternalHash();
        $assessment_data["meta"]["deleted_target_count"] = Models_Assessments_AssessmentTarget::fetchCountDeletedTargets($assessment_id);

        // Store assessment record
        $assessment_data["assessment"] = $assessment_record->toArray();
        if ($assessment_data["assessment"]["assessment_method_data"]) {
            $assessment_data["assessment"]["assessment_method_data"] = json_decode($assessment_data["assessment"]["assessment_method_data"], true);
        }

        // Fetch assessment type record
        if ($assessment_data["assessment"]["assessment_method_id"]) {
            $assessment_data["assessment_method"] = $this->fetchDatasetDataAssessmentMethod($assessment_data["assessment"]["assessment_method_id"]);
            if (empty($this->limit_dataset) || in_array("assessment_method", $this->limit_dataset)) {

                if (isset($assessment_data["assessment"]["assessment_method_data"]["assessor_group"])) {
                     $assessor_group = $assessment_data["assessment"]["assessment_method_data"]["assessor_group"];

                    // Fetch the method meta if we have a group to fetch it for.
                    $method_meta = Models_Assessments_Method_Meta::fetchRowByAssessmentMethodIDGroup(
                        $assessment_data["assessment"]["assessment_method_id"],
                        $assessor_group
                    );
                    if ($method_meta) {
                        $assessment_data["assessment_method_meta"] = $method_meta->toArray();
                    }
                }
            }
        }

        // The assessment type
        $assessment_data["assessment_type"] = $this->fetchDatasetDataAssessmentType($assessment_record->getAssessmentTypeID());

        // The record associated with the assessment, e.g. an event record, a user data record, a schedule record etc.
        $assessment_data["associated_record"] = $this->fetchDatasetDataAssessmentAssociatedRecord($assessment_record->getAssociatedRecordID(), $assessment_record->getAssociatedRecordType());

        // Fetch the creator of this assessment (proxy_id referenced by created_by field)
        $assessment_data["creator"] = $this->fetchDatasetAssessmentCreator($assessment_id);

        // Fetch the assessor
        $assessment_data["assessor"] = $this->fetchDatasetDataAssessorUser($assessment_record->getAssessorValue(), $assessment_record->getAssessorType(), $assessment_record->getExternalHash());

        // Progress (must fetch before the targets)
        $assessment_data["progress"] = $this->fetchDatasetDataAssessmentProgress($assessment_id);

        // Assessment target records (must fetch after progress in order to be able to prune them)
        // Note: Previously, inference was used to determine which targets applied to certain types of assessments.
        // Post-migration, all assessments are decoupled from their distributions and are assumed to have assessment target records.
        $assessment_data["targets"] = $this->fetchDatasetDataAssessmentTargets($assessment_id); // includes deleted targets
        if (!$this->fetch_deleted_targets) {
            // If we're not explicitly keeping deleted targets, we must prune the deleted targets from the dataset
            $assessment_data["targets"] = $this->pruneDeletedTargets($assessment_data["targets"], $assessment_data["progress"]);
        }

        // Fetch feedback on a per-assessment basis (not per attempt/progress)
        $assessment_data["feedback"] = $this->fetchDatasetDataAssessmentFeedback($assessment_id);

        // Build distribution data
        $assessment_data["distribution"] = $this->fetchDatasetDataDistribution($assessment_id, $assessment_record->getADistributionID());

        // Fetch the linked assessments
        $assessment_data["linked_assessments"] = $this->fetchDatasetDataLinkedAssessments($assessment_id);

        // Fetch all assessment records that have this dassessment_id in their forwarded_from_dassessment_id
        $assessment_data["forwarded"] = $this->fetchDatasetDataForwarded($assessment_id);

        // Fetch course owners
        $assessment_data["assessment_course_owners"] = $this->fetchDatasetAssessmentCourseOwners($assessment_record->getCourseID());

        // Optionally fetch the form this assessment uses (via the Form abstraction)
        $assessment_data["form_dataset"] = $this->fetchDatasetDataForm($assessment_record->getFormID());

        // Fetch the assessment options
        $assessment_data["assessment_options"] = $this->fetchDatasetDataAssessmentOptions($assessment_id);

        // Build assessment links
        // Note that the external has will be empty and the external URL will be invalid for internal assessments.
        $assessment_data["meta"]["assessment_uri_external"] = ENTRADA_URL . "/assessment/?dassessment_id={$assessment_id}&external_hash={$assessment_record->getExternalHash()}";
        $assessment_data["meta"]["assessment_uri_internal"] = ENTRADA_URL . "/assessments/assessment?dassessment_id={$assessment_id}";
        return $assessment_data;
    }

    /**
     * Create an array that contains the progress value for each of the targets.
     * Grouped by target type and target record ID. The progress value records are
     * stored in natural order.
     *
     * This will create an entry for all types, for all record IDs, and will produce
     * a value of "pending" when no progress is found.
     *
     * Resulting example array, assuming max submittable = 2:
     *   array(
     *      "proxy_id" => array(
     *         "1234" => array(
     *              0 => array("progress_value" => "complete", ... ),
     *              1 => array("progress_value" => "inprogress", ... )
     *         ),
     *         "2345" => array(
     *              0 => array("progress_value" => "complete", ... )
     *              1 => array("progress_value" => "complete", ... )
     *         ),
     *         "5678" => array(
     *              0 => array("progress_value" => "inprogress", ... )
     *              1 => array("progress_value" => "pending", ... )
     *         )
     *         ...
     *      ),
     *      "schedule_id" => array(
     *         "4215" => array(
     *              0 => array( "progress_value" => "inprogress", ...),
     *              1 => array( "progress_value" => "pending", ...),
     *         ),
     *         ...
     *      ),
     *      ...
     *      // repeat for n number of types. Typically, there is only one type
     *   )
     *
     * @return array
     */
    private function buildTargetProgressSummaryData() {
        $this->fetchData();
        if (empty($this->dataset)) {
            return array();
        }
        $target_dataset = array();

        $max_attempts = $this->dataset["assessment"]["max_submittable"];
        if (!$max_attempts) {
            $max_attempts = 1;
        }

        // For all of the targets, create the max number of attempts and set them to pending.
        // After all have been created, fill in the number of completed and inprogress attempts.

        // Add pending targets (no progress records)
        foreach ($this->dataset["targets"] as $target) {
            $target_type = $target["target_type"];
            $target_id = $target["target_value"];
            $atarget_id = $target["atarget_id"];
            if (!isset($target_dataset[$target_type][$target_id])) {
                for ($x = 0; $x < $max_attempts; $x++) {
                    $target_dataset[$target_type][$target_id][] = array(
                        "atarget_id" => $atarget_id,
                        "target_type" => $target_type,
                        "target_record_id" => $target_id,
                        "aprogress_id" => null,
                        "progress_value" => "pending",
                        "target_deleted_date" => $target["deleted_date"]
                    );
                }
            }
        }
        // Update the pending targets with their actual progress (if any)
        foreach ($target_dataset as $target_type => $target_dataset_data) {
            foreach ($target_dataset_data as $target_id => $target_summaries) {
                foreach ($this->dataset["progress"] as $progress_data) {
                    if ($progress_data["target_record_id"] == $target_id && $progress_data["target_type"] == $target_type) {
                        // Found a progress record for this target, so find a current summary record and replace its value
                        foreach ($target_dataset[$target_type][$target_id] as $i => $summary_data) {
                            if ($summary_data["progress_value"] == "pending") {
                                $target_dataset[$target_type][$target_id][$i]["progress_value"] = $progress_data["progress_value"];
                                $target_dataset[$target_type][$target_id][$i]["aprogress_id"] = $progress_data["aprogress_id"];
                                break; // update the one, and quit this for loop
                            }
                        }
                    }
                }
            }
        }
        return $target_dataset;
    }

    //-- Count dataset values --//

    /**
     * For every target, get a count of which ones have the specified progress_value. This counts targets, not the the progress.
     *
     * e.g. given: countUniqueTargetsWithProgressValue("complete")
     *  - if there are 2 targets, and one has a completed progress record, this function returns 1
     *  - if there are 2 targets, and both have a completed progress record, this function return 2
     *  - if there are 2 targets, and both have multiple completed progress records, this function returns 2
     *
     * @param $target_id
     * @param $target_type
     * @param $progress_value
     * @param bool $include_deleted
     * @return int
     */
    private function countUniqueTargetsWithProgressValue($target_id, $target_type, $progress_value, $include_deleted = true) {
        $limit_to = null;
        if ($target_id && $target_type) {
            $current_target = $this->getCurrentTarget($target_id, $target_type);
            if (!empty($current_target)) {
                $limit_to = $current_target["atarget_id"];
            }
        }
        $progress_summary = $this->buildTargetProgressSummaryData();
        $counts = array();
        foreach ($progress_summary as $target_type => $target_data) {
            foreach ($target_data as $target_id => $progress_summaries) {
                foreach ($progress_summaries as $progress_summary) {
                    if (!$include_deleted && $progress_summary["target_deleted_date"]) {
                        continue;
                    }
                    if ($limit_to && $progress_summary["atarget_id"] != $limit_to) {
                        continue;
                    }
                    if ($progress_summary["progress_value"] == $progress_value) {
                        if (!isset($counts[$progress_summary["atarget_id"]])) {
                            $counts[$progress_summary["atarget_id"]] = 0;
                        }
                        $counts[$progress_summary["atarget_id"]]++;
                    }
                }
            }
        }
        return count(array_keys($counts));
    }

    /**
     * For every target, get a count of each unique target that have "pending" assessments (assessments without progress records).
     * This takes into account the "inprogress" status; if a progress record is marked as "inprogress" it cannot have a "pending"
     * task; inprogress and pending are mutually exclusive, with inprogress being an overriding priority.
     *
     * @return int
     */
    private function countUniquePendingTargets() {
        $progress_summary = $this->buildTargetProgressSummaryData();
        $counts = array();
        foreach ($progress_summary as $target_type => $target_data) {
            foreach ($target_data as $target_id => $progress_summaries) {
                foreach ($progress_summaries as $progress_summary) {
                    if ($progress_summary["target_deleted_date"]) {
                        // Ignore deleted targets. They are by definition not pending.
                        continue;
                    }
                    if ($progress_summary["progress_value"] == "inprogress") {
                        unset($counts[$progress_summary["atarget_id"]]);
                        break;
                    } else if ($progress_summary["progress_value"] == "pending") {
                        if (!isset($counts[$progress_summary["atarget_id"]])) {
                            $counts[$progress_summary["atarget_id"]] = 0;
                        }
                        $counts[$progress_summary["atarget_id"]]++;
                    }
                }
            }
        }
        return count(array_keys($counts));
    }

    /**
     * For all of the current assessment targets, prune ones that are deleted that do not have completed progress records.
     *
     * @param array $all_targets
     * @param array $all_progress
     * @return array
     */
    private function pruneDeletedTargets(&$all_targets, &$all_progress) {
        $do_not_prune = array();
        $new_targets = array();

        // Find targets that we do not want to prune first.
        foreach ($all_targets as $target) {
            if (!$target["deleted_date"]) {
                // It's not deleted, so we don't prune it.
                $do_not_prune[$target["atarget_id"]] = $target["atarget_id"];
                continue;
            }
            // This target is deleted, so unless we find some completed progress for it, we will prune it.
            $target_record_id = $target["target_value"];
            $target_type = $target["target_type"];

            foreach ($all_progress as $progress) {
                if ($progress["target_type"] == $target_type
                    && $progress["target_record_id"] == $target_record_id
                    && $progress["progress_value"] == "complete"
                ) {
                    // Don't prune this one because it has a completed progress record.
                    $do_not_prune[$target["atarget_id"]] = $target["atarget_id"];
                }
            }
        }

        // Prune anything that isn't in our list.
        foreach ($all_targets as $target) {
            if (in_array($target["atarget_id"], $do_not_prune)) {
                $new_targets[] = $target;
            }
        }
        return $new_targets;
    }

    //-- Dataset Population --//

    /**
     * Fetch the form and all form-related data (via the Forms worker).
     *
     * @param $form_id
     * @return array
     */
    private function fetchDatasetDataForm($form_id) {
        $form_data = array();
        if ($form_id && $this->fetch_form_data) {
            $construction = array("form_id" => $form_id, "aprogress_id" => $this->aprogress_id);
            if ($this->form_limit_dataset) {
                $construction = array_merge($construction, array("limit_dataset" => $this->form_limit_dataset));
            }
            if ($this->rubric_limit_dataset) {
                $construction = array_merge($construction, array("rubric_limit_dataset" => $this->rubric_limit_dataset));
            }
            $form_object = new Entrada_Assessments_Workers_Form($this->buildActorArray($construction));
            $form_data = $form_object->fetchData();
        }
        return $form_data;
    }

    /**
     * Build progress data array, with associated progress responses.
     * Unlike other dataset data chunks, progress is not ordered by progress ID, because new progress records (with no
     * ID) can be mixed in with the old in the new datasets. However, this mixing doesn't happen yet; when full dataset
     * commit capability (instead of direct progress updating) is ever implemented, this natural ordering behaviour will
     * be necessary.
     *
     * @param $dassessment_id
     * @return array
     */
    private function fetchDatasetDataAssessmentProgress($dassessment_id) {
        $progress_data = array();
        if (!empty($this->limit_dataset) && !in_array("progress", $this->limit_dataset)) {
            return $progress_data;
        }
        if ($dassessment_id) {
            // Fetch all the progress records and progress response records
            if ($progress_records = Models_Assessments_Progress::fetchAllByDassessmentID($dassessment_id)) {
                foreach ($progress_records as $progress_record) {
                    $single_progress_data = $progress_record->toArray();
                    $single_progress_data["progress_responses"] = array();
                    if ($progress_responses = Models_Assessments_Progress_Response::fetchAllByAprogressIDJoinIresponse($progress_record->getID())) {
                        foreach ($progress_responses as $progress_response) {
                            $single_progress_data["progress_responses"][] = $progress_response;
                        }
                    }
                    $progress_data[] = $single_progress_data;
                }
            }
        }
        return $progress_data;
    }

    /**
     * Fetch the assessment type by type ID.
     *
     * @param $assessment_method_id
     * @return array
     */
    private function fetchDatasetDataAssessmentMethod($assessment_method_id) {
        $type_data = array();
        if (!empty($this->limit_dataset) && !in_array("assessment_method", $this->limit_dataset)) {
            return $type_data;
        }
        if ($assessment_method_id) {
            $assessment_method_record = new Models_Assessments_Method();
            if ($assessment_method_record = $assessment_method_record->fetchRowByID($assessment_method_id)) {
                $type_data = $assessment_method_record->toArray();
            }
        }
        return $type_data;
    }

    /**
     * Fetch the which assessments were created based on this one (forwarded to).
     *
     * @param $dassessment_id
     * @return array
     */
    private function fetchDatasetDataForwarded($dassessment_id) {
        $data = array();
        if (!empty($this->limit_dataset) && !in_array("forwarded", $this->limit_dataset)) {
            return $data;
        }
        if ($dassessment_id) {
            $data = Models_Assessments_Assessor::fetchForwardedAssessmentData($dassessment_id);
            if (empty($data)) {
                return array();
            }
        }
        return $data;
    }

    /**
     * Fetch the associated record. In the case of proxy ID, there is no associated record per se; the target records will link directly to
     * each proxy. There can be multiple proxies per assessment so linking to one in particular is meaningless.
     *
     * @param $record_id
     * @param $record_type
     * @return array
     */
    private function fetchDatasetDataAssessmentAssociatedRecord($record_id, $record_type) {
        $associated_record = array();
        if (!empty($this->limit_dataset) && !in_array("associated_record", $this->limit_dataset)) {
            return $associated_record;
        }
        switch ($record_type) {
            case "event_id":
                if ($event = Models_Event::fetchRowByID($record_id)) {
                    $associated_record["associated_entity_name"] = $event->getEventTitle();
                    $associated_record["associated_organisation_id"] = $event->getOrganisationID();
                    $associated_record["associated_organisation_name"] = $this->fetchOrganisationName($event->getOrganisationID());
                    $associated_record["start_date"] = $event->getEventStart();
                    $associated_record["end_date"] = $event->getEventFinish();
                    $associated_record["source_record"] = $event->toArray();
                }
                break;
            case "schedule_id":
                if ($schedule = Models_Schedule::fetchRowByID($record_id)) {
                    $associated_record["associated_entity_name"] = $schedule->getTitle();
                    $associated_record["associated_organisation_id"] = $schedule->getOrganisationID();
                    $associated_record["associated_organisation_name"] = $this->fetchOrganisationName($schedule->getOrganisationID());
                    $associated_record["start_date"] = $schedule->getStartDate();
                    $associated_record["end_date"] = $schedule->getEndDate();
                    $associated_record["source_record"] = $schedule->toArray();
                }
                break;
            case "course_id":
                if ($course = Models_Course::fetchRowByID($record_id)) {
                    $associated_record["associated_entity_name"] = $course->getCourseName();
                    $associated_record["associated_organisation_id"] = $course->getOrganisationID();
                    $associated_record["associated_organisation_name"] = $this->fetchOrganisationName($course->getOrganisationID());
                    $associated_record["start_date"] = null;
                    $associated_record["end_date"] = null;
                    $associated_record["source_record"] = $course->toArray();
                }
                break;
            // Note: Other types will have to be supported here in the future.

            case "proxy_id":
            default:
                // Proxy ID or anything else do not require a special fetch (default of empty array is suitable).
                break;
        }
        return $associated_record;
    }

    /**
     * Fetch the assessment_type record
     *
     * @param $assessment_type_id
     * @return array
     */
    private function fetchDatasetDataAssessmentType($assessment_type_id) {
        if (!empty($this->limit_dataset) && !in_array("assessment_type", $this->limit_dataset)) {
            return array();
        }
        $assessment_type = Models_Assessments_Type::fetchRowByID($assessment_type_id);
        if ($assessment_type) {
            return $assessment_type->toArray();
        }
        return array();
    }

    /**
     * Fetch all of the feedback records for a given assessment.
     * Note: feedback is stored on a per-assessment basis, not per-progress.
     *
     * @param $dassessment_id
     * @return array
     */
    private function fetchDatasetDataAssessmentFeedback($dassessment_id) {
        $feedback = array();
        if (!empty($this->limit_dataset) && !in_array("feedback", $this->limit_dataset)) {
            return $feedback;
        }
        if ($feedback_records = Models_Assessments_AssessorTargetFeedback::fetchAllByDassessmentID($dassessment_id)) {
            foreach ($feedback_records as $feedback_record) {
                $feedback[] = $feedback_record->toArray();
            }
        }
        return $feedback;
    }

    /**
     * Fetch the targets for the given assessment.
     *
     * @param $dassessment_id
     * @return array
     */
    private function fetchDatasetDataAssessmentTargets($dassessment_id) {
        $targets = array();
        if (!empty($this->limit_dataset) && !in_array("targets", $this->limit_dataset)) {
            return $targets;
        }
        if ($dassessment_id) {
            // Store assessment target records, with a summary of their progress.
            $assessment_targets = Models_Assessments_AssessmentTarget::fetchAllByDassessmentIDIncludeDeleted($dassessment_id);
            if ($assessment_targets) {
                foreach ($assessment_targets as $assessment_target) {
                    $atarget_id = $assessment_target->getID();
                    $target_record_id = $assessment_target->getTargetValue();

                    $targets[$atarget_id] = $assessment_target->toArray();
                    $targets[$atarget_id]["target_name"] = null;

                    // Fetch the name of the target, based on its type.
                    switch ($assessment_target->getTargetType()) {
                        case "event_id":
                            if ($event = Models_Event::fetchRowByID($target_record_id)) {
                                $targets[$atarget_id]["target_name"] = $event->getEventTitle();
                            }
                            break;
                        case "schedule_id":
                            if ($schedule = Models_Schedule::fetchRowByID($target_record_id)) {
                                $targets[$atarget_id]["target_name"] = $schedule->getTitle();
                            }
                            break;
                        case "proxy_id":
                            if ($user = $this->getUserByType($target_record_id, "internal")) {
                                $targets[$atarget_id]["target_name"] = "{$user->getFirstname()} {$user->getLastname()}";
                            }
                            break;
                        case "group_id":
                            if ($group = Models_Group::fetchRowByID($target_record_id)) {
                                $targets[$atarget_id]["target_name"] = $group->getGroupName();
                            }
                            break;
                        case "cgroup_id":
                            if ($cgroup = Models_Course_Group::fetchRowByID($target_record_id)) {
                                $targets[$atarget_id]["target_name"] = $cgroup->getGroupName();
                            }
                            break;
                        case "course_id":
                            if ($course = Models_Course::fetchRowByID($target_record_id)) {
                                $targets[$atarget_id]["target_name"] = $course->getCourseName();
                            }
                            break;
                        case "organisation_id":
                            if ($organisation = Organisation::get($target_record_id)) {
                                $targets[$atarget_id]["target_name"] = $organisation->getTitle();
                            }
                            break;
                        case "external":
                        case "external_hash":
                            if ($user = $this->getUserByType($target_record_id, "external")) {
                                $targets[$atarget_id]["target_name"] = "{$user->getFirstname()} {$user->getLastname()}";
                            }
                            break;
                    }
                }
            }
        }
        return $targets;
    }

    /**
     * Fetch the creator of the assessment. This assumes the user that creates the assessment is internal.
     * If not, this will return array().
     *
     * @param int $dassessment_id
     * @return array
     */
    private function fetchDatasetAssessmentCreator($dassessment_id) {
        if (!empty($this->limit_dataset) && !in_array("creator", $this->limit_dataset)) {
            return array();
        }
        if (!$assessment_record = $this->fetchAssessmentRecord($dassessment_id)) {
            return array();
        }
        if ($user = $this->fetchUserRecord($assessment_record->getCreatedBy(), "internal")) {
            return $user->toArray();
        }
        return array();
    }

    /**
     * Build an array describing an internal or external assessor.
     *
     * @param $assessor_id
     * @param $assessor_type
     * @param null $external_hash
     * @return array
     */
    private function fetchDatasetDataAssessorUser($assessor_id, $assessor_type, $external_hash = null) {
        global $translate;
        $assessor_data = array();
        if (!empty($this->limit_dataset) && !in_array("assessor", $this->limit_dataset)) {
            return $assessor_data;
        }
        if ($assessor = $this->getUserByType($assessor_id, $assessor_type)) {
            $assessor_data["assessor_id"] = $assessor_id;
            $assessor_data["type"] = ($external_hash) ? "external" : "internal";
            $assessor_data["full_name"] = "{$assessor->getFirstname()} {$assessor->getLastname()}";
            $assessor_data["email"] = $assessor->getEmail();
            $assessor_data["assessor_user_record"] = $assessor->toArray();
        } else {
            // We must have an assessor. If there was no record returned, we return a generic array.
            // Note that progress updates and submission will still use the assessor_id given, we just don't know who it is.
            $assessor_data["assessor_id"] = null;
            $assessor_data["type"] = null;
            $assessor_data["full_name"] = $translate->_("Unknown Assessor");
            $assessor_data["email"] = "";
            $assessor_data["assessor_user_record"] = array();
        }
        return $assessor_data;
    }

    /**
     * Compile the distribution related data for the specified assessment and distribution.
     * 
     * @param $dassessment_id
     * @param $adistribution_id
     * @return array
     */
    private function fetchDatasetDataDistribution($dassessment_id, $adistribution_id) {
        $distribution_data = $this->buildDefaultDistributionDataStructure();
        $fetch_all = false;
        if (empty($this->limit_dataset) || in_array("distribution", $this->limit_dataset)) {
            $fetch_all = true;
        }
        if ($adistribution_id) {
            // Fetch the distribution related data, if the assessment was created by a distribution
            $distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($adistribution_id);
            if ($distribution) {
                if ($fetch_all || in_array("distribution_record", $this->limit_dataset)) {
                    $distribution_data["distribution_record"] = $distribution->toArray();
                }
                if ($fetch_all || in_array("course_owners", $this->limit_dataset)) {
                    $distribution_data["course_owners"] = Models_Course::fetchCourseOwnerList($distribution->getCourseID());
                }
                if ($fetch_all || in_array("distribution_assessors", $this->limit_dataset)) {
                    if ($dist_assessors = Models_Assessments_Distribution_Assessor::fetchAllByDistributionID($adistribution_id)) {
                        $distribution_data["meta"]["assessors"] = $this->buildAssessorsSummary($dist_assessors);
                        foreach ($dist_assessors as $dist_assessor) {
                            $distribution_data["distribution_assessors"][$dist_assessor->getID()] = $dist_assessor->toArray();
                        }
                    }
                }
                if ($fetch_all || in_array("distribution_targets", $this->limit_dataset)) {
                    if ($dist_targets = Models_Assessments_Distribution_Target::fetchAllByDistributionID($adistribution_id)) {
                        $distribution_data["meta"]["targets"] = $this->buildTargetsSummary($dist_targets);
                        foreach ($dist_targets as $dist_target) {
                            $distribution_data["distribution_targets"][$dist_target->getID()] = $dist_target->toArray();
                        }
                    }
                }
                if ($fetch_all || in_array("distribution_schedule", $this->limit_dataset)) {
                    if ($distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($adistribution_id)) {
                        $distribution_data["distribution_schedule"] = $distribution_schedule->toArray();
                        if ($schedule = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID())) {
                            $distribution_data["schedule"] = $schedule->toArray();
                        }
                    }
                }
                if ($fetch_all || in_array("distribution_approvers", $this->limit_dataset)) {
                    $approver_model = new Models_Assessments_Distribution_Approver();
                    if ($approvers = $approver_model->fetchAllByDistributionID($adistribution_id)) {
                        foreach ($approvers as $approver) {
                            $distribution_data["distribution_approvers"][$approver->getID()] = $approver->toArray();
                        }
                    }
                }
                if ($fetch_all || in_array("distribution_approvals", $this->limit_dataset)) {
                    $approvals_model = new Models_Assessments_Distribution_Approvals();
                    if ($approvals = $approvals_model->fetchAllByDistributionID($adistribution_id)) {
                        foreach ($approvals as $approval) {
                            $distribution_data["distribution_approvals"][$approval->getID()] = $approval->toArray();
                        }
                    }
                }
                if ($fetch_all || in_array("distribution_creator", $this->limit_dataset)) {
                    if ($creator_record = $this->fetchUserRecord($distribution->getCreatedBy(), "internal")) {
                        $distribution_data["distribution_creator"] = $creator_record->toArray();
                    }
                }
                if ($fetch_all || in_array("distribution_reviewers", $this->limit_dataset)) {
                    $reviewers_model = new Models_Assessments_Distribution_Reviewer();
                    if ($reviewers = $reviewers_model->fetchAllByDistributionID($adistribution_id)) {
                        foreach ($reviewers as $reviewer) {
                            $distribution_data["distribution_reviewers"][$reviewer->getID()] = $reviewer->toArray();
                        }
                    }
                }
            }
        }
        if ($dassessment_id) {
            if ($fetch_all || in_array("delegations", $this->limit_dataset)) {
                // Fetch the assignment records created by the delegator of this assessment
                // Also fetch the delegator(s)
                $delegation_assignments = Models_Assessments_Distribution_DelegationAssignment::fetchAllByAssessmentID($dassessment_id);
                if (is_array($delegation_assignments) && !empty($delegation_assignments)) {
                    foreach ($delegation_assignments as $delegation_assignment) {
                        $distribution_data["delegation_assignments"][$delegation_assignment->getID()] = $delegation_assignment->toArray();
                        if ($delegation_assignment->getDelegatorID()) {
                            $delegator_data = array();
                            $distribution_data["meta"]["has_delegators"] = true;
                            if ($delegator = $this->getUserByType($delegation_assignment->getDelegatorID(), "proxy_id")) {
                                $delegator_data = $delegator->toArray();
                            }
                            // If we ever support external delegators, this is where we would support it.
                            $distribution_data["delegators"][$delegation_assignment->getDelegatorID()] = array(
                                "type" => "proxy_id",
                                "delegator_id" => $delegation_assignment->getDelegatorID(),
                                "full_name" => "{$delegator_data["firstname"]} {$delegator_data["lastname"]}",
                                "delegator" => $delegator_data
                            );
                        }
                    }
                }
            }
        }
        return $distribution_data;
    }

    /**
     * Fetch the assessment course owners, based on the assessment course id.
     *
     * @param $course_id
     * @return array
     */
    private function fetchDatasetAssessmentCourseOwners($course_id) {
        $course_owners = array();
        if (!empty($this->limit_dataset) && !in_array("course_owners", $this->limit_dataset)) {
            return $course_owners;
        }

        if ($course_id) {
            $course_owners = Models_Course::fetchCourseOwnerList($course_id);
        }

        return $course_owners;
    }

    /**
     * Fetch the assessment linkage; parent and child associations of where the assessment was created.
     *
     * @param $assessment_id
     * @return array
     */
    private function fetchDatasetDataLinkedAssessments($assessment_id) {
        $links = array("parent_of" => array(), "child_of" => array());
        if (!empty($this->limit_dataset) && !in_array("linked_assessments", $this->limit_dataset)) {
            return $links;
        }
        if ($is_origin = Models_Assessments_Link::fetchAllByOriginatingID($assessment_id)) {
            foreach ($is_origin as $origination) {
                $links["parent_of"][] = $origination->getLinkedID();
            }
        }
        if ($copied_from = Models_Assessments_Link::fetchAllByLinkedID($assessment_id)) {
            foreach ($copied_from as $copy) {
                $links["child_of"][] = $copy->getOriginatingID();
            }
        }
        return $links;
    }

    /**
     * Fetch the assessment options.
     *
     * @param $assessment_id
     * @return array
     */
    private function fetchDatasetDataAssessmentOptions($assessment_id) {
        $options = array();
        if (!empty($this->limit_dataset) && !in_array("assessment_options", $this->limit_dataset)) {
            return $options;
        }
        $assesment_options_model = new Models_Assessments_Options();
        $assesment_options = $assesment_options_model->fetchAllByDassessmentID($assessment_id);
        if (is_array($assesment_options)) {
            foreach ($assesment_options as $assesment_option) {
                $options[] = $assesment_option->toArray();
            }
        }
        return $options;
    }

    //-- Item submission validation routines --//

    /**
     * Validate and sanitize numeric item (takes a comment and make it a number).
     *
     * @param array $posted_data
     * @param string $post_index
     * @param string $item_text
     * @param bool $is_partial_update
     * @param bool $is_mandatory
     * @return array|bool
     */
    private function validateAndSanitizeNumericItem($posted_data, $post_index, $item_text, $is_partial_update = false, $is_mandatory = true) {
        global $translate;
        $data = array();
        $error_please_add = sprintf($translate->_("Please enter a <strong>numeric value</strong> for <strong>%s</strong>"), html_encode($item_text));
        if (!array_key_exists($post_index, $posted_data)) {
            if ($is_partial_update || !$is_mandatory) {
                // For a partial update, we can ignore the fact that nothing was posted to us
                return array();
            } else {
                $this->addErrorMessage($error_please_add);
                return false;
            }
        }
        $sanitized_string = clean_input($posted_data[$post_index], array("trim", "striptags"));
        if (empty($sanitized_string) && $sanitized_string !== 0) {
            $sanitized = "no_value";
        } else {
            if (is_numeric($sanitized_string)) {
                $sanitized = "passed";
            } else {
                $sanitized = "failed";
            }
        }
        switch ($sanitized) {
            case "no_value":
                if ($is_partial_update) {
                    // No string was specified, but we're doing a partial update, so we can ignore this.
                    // With this set as an empty array, the cleanup routine will get rid of the extraneous records.
                    return array();
                } else if (!$is_mandatory) {
                    // Not mandatory, and not a partial update, can return empty (OK)
                    return array();
                } else {
                    // No value specified, this is mandatory and not a partial update, so throw error.
                    $this->addErrorMessage($error_please_add);
                    return false;
                }

            case "failed":
                if ($is_partial_update) {
                    // For a partial update, we store the saved data regardless -- we can't set the progress to "complete"
                    // unless it passes validation, but we can store any interim values.
                    $data["comments"] = $sanitized_string;
                    $data["iresponse_id"] = NULL;
                    return array($data); // Return an array containing the single free text item
                } else {
                    // In all other cases, return error if input is bad
                    $this->addErrorMessage($error_please_add);
                    return false;
                }

            case "passed":
                // Passed validation, so store the value
                $data["comments"] = $sanitized_string;
                $data["iresponse_id"] = NULL;
                return array($data); // Return an array containing the single free text item
        }
    }

    /**
     * Validate and sanitize the form element response as a date item (takes a YYYY-MM-DD and turns it into unix timestamp).
     *
     * @param array $posted_data
     * @param string $post_index
     * @param string $item_text
     * @param bool $is_partial_update
     * @param bool $is_mandatory
     * @return array|bool
     */
    private function validateAndSanitizeDateItem($posted_data, $post_index, $item_text, $is_partial_update = false, $is_mandatory = true) {
        global $translate;
        $data = array();
        $error_please_add = sprintf($translate->_("Please enter a valid date in <strong>YYYY-MM-DD</strong> format for <strong>%s</strong>"), html_encode($item_text));
        if (!array_key_exists($post_index, $posted_data)) {
            if ($is_partial_update || !$is_mandatory) {
                // For a partial update, we can ignore the fact that nothing was posted to us
                return array();
            }
            $this->addErrorMessage($error_please_add);
            return false;
        }
        $date_value = clean_input($posted_data[$post_index], array("trim", "notags"));
        $dt = DateTime::createFromFormat("Y-m-d", $date_value);
        if ($dt === false || array_sum($dt->getLastErrors())) {
            $sanitized_string = null;
        } else {
            $sanitized_string = $dt->getTimestamp();
        }
        if (!$sanitized_string) {
            if ($is_partial_update) {
                // No string was specified, but we're doing a partial update, so we can ignore this.
                // With this set as an empty array, the cleanup routine will get rid of the extraneous records.
                return array();
            } else if (!$is_mandatory && !empty($date_value)) {
                $this->addErrorMessage($error_please_add);
                return false;
            } else if (!$is_mandatory) {
                // If this item is not mandatory, we can ignore it for now.
                return array();
            } else {
                // Otherwise, it's mandatory, it's not a partial update, so they need to specify a response.
                $this->addErrorMessage($error_please_add);
                return false;
            }
        }
        $data["comments"] = $sanitized_string;
        $data["iresponse_id"] = NULL;
        return array($data); // Return an array containing the single free text item
    }

    /**
     * Validate and sanitize the form element response as a free text comment.
     *
     * @param array $posted_data
     * @param string $post_index
     * @param string $item_text
     * @param bool $is_partial_update
     * @param bool $is_mandatory
     * @return array|bool
     */
    private function validateAndSanitizeFreeTextItem($posted_data, $post_index, $item_text, $is_partial_update = false, $is_mandatory = true) {
        global $translate;
        $data = array();
        $error_please_add = sprintf($translate->_("Please add a response for <strong>%s</strong>"), html_encode($item_text));
        if (!array_key_exists($post_index, $posted_data)) {
            if ($is_partial_update) {
                // For a partial update, we can ignore the fact that nothing was posted to us
                return array();
            }
            $this->addErrorMessage($error_please_add);
            return false;
        }
        if (!$sanitized_string = clean_input($posted_data[$post_index], array("trim", "striptags"))) {
            if ($is_partial_update) {
                // No string was specified, but we're doing a partial update, so we can ignore this.
                // With this set as an empty array, the cleanup routine will get rid of the extraneous records.
                return array();
            } else if (!$is_mandatory) {
                // If this item is not mandatory, we can ignore it for now.
                return array();
            } else {
                // Otherwise, it's mandatory, it's not a partial update, so they need to specify a response.
                $this->addErrorMessage($error_please_add);
                return false;
            }
        }
        $data["comments"] = $sanitized_string;
        $data["iresponse_id"] = NULL;
        return array($data); // Return an array containing the single free text item
    }

    /**
     * Validate and sanitize the form element response as a single-response item.
     *
     * @param $posted_data
     * @param $post_index
     * @param $item_id
     * @param $item_comment_type
     * @param $item_text
     * @param bool $is_partial_update
     * @param bool $is_mandatory
     * @return array|bool
     */
    private function validateAndSanitizeSingleResponseItem($posted_data, $post_index, $item_id, $item_comment_type, $item_text, $rating_scale_id, $is_partial_update = false, $is_mandatory = true) {
        global $translate;
        $data = array();
        $error_please_add = sprintf($translate->_("Please select a response for <strong>%s</strong>"), html_encode($item_text));
        if (!array_key_exists($post_index, $posted_data)) {
            if ($is_partial_update || !$is_mandatory) {
                // For a partial update, we can ignore the fact that nothing was posted to us
                return array();
            }
            $this->addErrorMessage($error_please_add);
            return false;
        }
        $sanitized = clean_input($posted_data[$post_index], array("trim", "int"));
        if (!$sanitized) {
            if ($is_partial_update) {
                // No string was specified, but we're doing a partial update, so we can ignore this.
                // With this set as an empty array, the cleanup routine will get rid of the extraneous records.
                return array();
            } else if (!$is_mandatory) {
                // If this item is not mandatory, we can ignore it for now.
                return array();
            } else {
                // Otherwise, it's mandatory, it's not a partial update, so they need to specify a response.
                $this->addErrorMessage($error_please_add);
                return false;
            }
        }
        $data["iresponse_id"] = $sanitized;
        $comments_key = "item-{$item_id}-comments";
        $iresponse = $this->fetchItemResponseRecord($data["iresponse_id"]); // might not exist
        if (!$iresponse) {
            $this->addErrorMessage($translate->_("Error accessing item response."));
            return false;
        }
        // Get the weight of that response based on the rating scale ID
        if (!$scale_response = Models_Assessments_RatingScale_Response::fetchRowByRatingScaleARDescriptorID($rating_scale_id, $iresponse->getARDescriptorID())) {
            $weight = null;
        } else {
            $weight = $scale_response->getWeight();
        }

        if (isset($posted_data[$comments_key]) && ($sanitized = clean_input($posted_data[$comments_key], array("trim", "striptags")))) {
            $data["comments"] = $sanitized;
        } else {
            $data["comments"] = NULL;
        }

        // Check if comments are mandatory
        if (($item_comment_type == "mandatory" && $weight !== '0') || ($item_comment_type == "flagged" && $iresponse && $iresponse->getFlagResponse())) {
            if (!$is_partial_update && !$data["comments"]) {
                add_error(sprintf($translate->_("Please comment on: <strong>%s</strong>"), html_encode($item_text)));
                return false;
            }
        }
        return array($data); // return an array containing the one response (since this is a single response item)
    }

    /**
     * Validate and sanitize the form element response as a multiple-response item.
     *
     * @param $posted_data
     * @param $post_index
     * @param $item_id
     * @param $item_comment_type
     * @param $item_text
     * @param bool $is_partial_update
     * @param bool $is_mandatory
     * @return array|bool
     */
    private function validateAndSanitizeMultiResponseItem($posted_data, $post_index, $item_id, $item_comment_type, $item_text, $is_partial_update = false, $is_mandatory = true) {
        global $translate;
        $return_data = array();
        $error_please_select = sprintf($translate->_("Please select a response for <strong>%s</strong>"), html_encode($item_text));
        if (!array_key_exists($post_index, $posted_data)) {
            if ($is_partial_update || !$is_mandatory) {
                // For a partial update, we can ignore the fact that nothing was posted to us
                return array();
            }
            $this->addErrorMessage($error_please_select);
            return false;
        }
        if (!is_array($posted_data[$post_index])) {
            $this->addErrorMessage($translate->_("Malformed submitted progress data."));
            return false;
        }
        $selected_responses = array();
        foreach ($posted_data[$post_index] as $key => $response) {
            if ($tmp_input = clean_input($response, array("trim", "int"))) {
                $selected_responses[] = $tmp_input;
            }
        }
        if (empty($selected_responses)) {
            if ($is_partial_update) {
                return array();
            } else if (!$is_mandatory) {
                return array();
            } else {
                // mandatory and not a partial, so error
                $this->addErrorMessage($error_please_select);
                return false;
            }
        }
        // At this point, we have selected responses, so let's add sanitize
        foreach ($selected_responses as $response_id) {
            $data = array();
            $data["iresponse_id"] = $response_id;
            $comments_key = "item-{$item_id}-comments";
            $iresponse = $this->fetchItemResponseRecord($data["iresponse_id"]);
            if (!$iresponse) {
                $this->addErrorMessage($translate->_("Error accessing item response."));
                return false;
            }
            if (isset($posted_data[$comments_key]) && ($tmp_input = clean_input($posted_data[$comments_key], array("trim", "striptags")))) {
                $data["comments"] = $tmp_input;
            } else {
                $data["comments"] = NULL;
            }
            // Check if comments are mandatory
            if ($item_comment_type == "mandatory" || ($item_comment_type == "flagged" && $iresponse && $iresponse->getFlagResponse())) {
                if (!$is_partial_update && !$data["comments"]) {
                    $this->addErrorMessage(sprintf($translate->_("Please comment on: <strong>%s</strong>"), html_encode($item_text)));
                    return false;
                }
            }
            $return_data[] = $data;
        }
        return $return_data; // return an array containing all of the responses for this multi-response item
    }

    /**
     * Validate and sanitize the form element response as an objective selection response item.
     *
     * @param $posted_data
     * @param $post_index
     * @param $objective_name
     * @param bool $is_partial_update
     * @return array|bool
     */
    private function validateAndSanitizeObjectiveItem($posted_data, $post_index, $objective_name, $is_partial_update = false) {
        global $translate;
        $error_please_select = sprintf($translate->_("Please select a response for <strong>%s</strong>"), html_encode($objective_name));
        if (!array_key_exists($post_index, $posted_data)) {
            if ($is_partial_update) {
                // For a partial update, we can ignore the fact that nothing was posted to us
                return array();
            }
            $this->addErrorMessage($error_please_select);
            return false;
        }
        if (!$tmp_input = clean_input($posted_data[$post_index], array("trim", "int"))) {
            $this->addErrorMessage($error_please_select);
            return false;
        }
        $data["iresponse_id"] = $tmp_input;
        return array($data); // return an array containing the one response (since this is a single objective ID selection)
    }

    //-- Wrappers for fetching/writing single records --//

    /**
     * Fetch an assessment record, optionally caching it in local storage.
     *
     * @param $dassessment_id
     * @param bool $cached
     * @return bool|Models_Assessments_Assessor
     */
    private function fetchAssessmentRecord($dassessment_id, $cached = true) {
        if (!$cached) {
            return Models_Assessments_Assessor::fetchRowByID($dassessment_id, null, true);
        }
        if ($this->isInStorage("assessment_record", $dassessment_id)) {
            return $this->fetchFromStorage("assessment_record", $dassessment_id);
        } else {
            $assessment_record = Models_Assessments_Assessor::fetchRowByID($dassessment_id, null, true);
            $this->addToStorage("assessment_record", $assessment_record, $dassessment_id);
            return $assessment_record;
        }
    }

    /**
     * Fetch the the full name of a user (either internal or external)
     *
     * @param $user_id
     * @param string $user_scope
     * @param bool $use_local_cache
     * @return string
     */
    private function fetchUserFullname($user_id, $user_scope = "internal", $use_local_cache = true) {
        global $translate;
        if ($user_record = $this->fetchUserRecord($user_id, $user_scope, $use_local_cache)) {
            return "{$user_record->getFirstName()} {$user_record->getLastName()}";
        } else {
            return $translate->_("Unknown Person");
        }
    }

    /**
     * Fetch a user record (cached).
     *
     * @param int $user_id
     * @param string $user_scope
     * @param bool $use_local_cache
     * @return bool|Models_User
     */
    private function fetchUserRecord($user_id, $user_scope = "internal", $use_local_cache = true) {
        $storage_index = "{$user_scope}-{$user_id}";
        $storage_type = "user-$user_scope-record";
        if ($this->isInStorage($storage_type, $storage_index) && $use_local_cache) {
            return $this->fetchFromStorage($storage_type, $storage_index);
        } else {
            $user = $this->getUserByType($user_id, $user_scope);
            $this->addToStorage($storage_type, $user, $storage_index);
            return $user;
        }
    }

    /**
     * Fetch the name of the given organisation.
     *
     * @param $organisation_id
     * @param bool $cached
     * @return bool|mixed|null|string
     */
    private function fetchOrganisationName($organisation_id, $cached = true) {
        if (!$cached) {
            if ($organisation = Organisation::get($organisation_id)) {
                return $organisation->getTitle();
            }
        }
        if ($this->isInStorage("organisation_name", $organisation_id)) {
            return $this->fetchFromStorage("organisation_name", $organisation_id);
        } else {
            $organisation_name = null;
            $organisation = Organisation::get($organisation_id);
            if ($organisation) {
                $organisation_name = $organisation->getTitle();
            }
            $this->addToStorage("organisation_name", $organisation_name, $organisation_id);
            return $organisation_name;
        }
    }

    /**
     * Fetch an item response record from local storage cache.
     * Optionally fetch it directly (without cache).
     *
     * @param int $iresponse_id
     * @param bool $cached
     * @return bool|Models_Assessments_Item_Response
     */
    private function fetchItemResponseRecord($iresponse_id, $cached = true) {
        if (!$cached) {
            return Models_Assessments_Item_Response::fetchRowByID($iresponse_id);
        }
        if ($this->isInStorage("item_response_record", $iresponse_id)) {
            return $this->fetchFromStorage("item_response_record", $iresponse_id);
        } else {
            $record = Models_Assessments_Item_Response::fetchRowByID($iresponse_id);
            $this->addToStorage("item_response_record", $record, $iresponse_id);
            return $record;
        }
    }

    /**
     * Fetch the form ID, if possible/applicable, for an assessment.
     *
     * @param int|bool $specified_id
     * @return bool|null|int
     */
    private function fetchFormID($specified_id = false) {
        if ($id = $this->whichID("dassessment_id", $specified_id)) {
            if ($assessment_record = $this->fetchAssessmentRecord($id)) {
                return $assessment_record->getFormID();
            }
        }
        return null;
    }

    /**
     * Save a new assessment option record.
     *
     * @param array $option_data
     * @return bool
     */
    private function insertAssessmentOptionRecord($option_data = array()) {
        global $translate, $db;
        $this->fetchData();
        if (empty($this->dataset)) {
            return false;
        }
        $option_value = Entrada_Utilities::arrayValueOrDefault($option_data, "option_value", null);
        $option_name = Entrada_Utilities::arrayValueOrDefault($option_data, "option_name", null);
        if (!$option_name) {
            $this->addErrorMessage($translate->_("Unable to create assessment option without option name."));
            return false;
        }
        $assessment_option_model = new Models_Assessments_Options();
        // Configure default options array
        $default_option_data = array(
            "dassessment_id"        => $this->dassessment_id,
            "adistribution_id"      => $this->dataset["assessment"]["adistribution_id"],
            "option_name"           => $option_name,
            "option_value"          => $option_value,
            "assessment_siblings"   => null,
            "created_date"          => time(),
            "created_by"            => $this->actor_proxy_id,
            "updated_date"          => null,
            "updated_by"            => null,
            "deleted_date"          => null
        );
        // Merge the defaults with the given option data (overwrites our defaults, but that's OK)
        $merged = array_merge($default_option_data, $option_data);
        if (!$assessment_option_model->fromArray($merged)->insert()) {
            $this->addErrorMessage($translate->_("Unable to save assessment option record"));
            application_log("error", "Unable to insert new assessment option record. DB said: " . $db->ErrorMsg());
            return false;
        }
        return true;
    }


    //-- Default Dataset Structure Creation --//

    /**
     * Build the default data structure to encompass the related distribution data, if any, for an assessment.
     *
     * @return array
     */
    private function buildDefaultDistributionDataStructure() {
        return array(
            "meta" => array(
                "assessors" => array(),
                "targets" => array(),
                "has_delegators" => false
            ),
            "distribution_record" => array(),
            "course_owners" => array(),
            "delegators" => array(),
            "delegation_assignments" => array(),
            "distribution_assessors" => array(),
            "distribution_targets" => array(),
            "distribution_schedule" => array(),
            "distribution_approvers" => array(),
            "distribution_approvals" => array(),
            "distribution_creator" => array(),
            "distribution_reviewers" => array(),
            "schedule" => array()
        );
    }

    /**
     * Create the default dataset structure.
     * Specifying null as the ID will create a purely default (empty) set, whereas specifying
     * an ID will fetch some metadata, seeding the result set.
     *
     * @param int|bool $specified_id
     * @return array
     */
    private function buildDefaultAssessmentStructure($specified_id = false) {
        $dassessment_id = $this->whichID("dassessment_id", $specified_id);
        $default_struct = array();
        $default_struct["is_stale"] = false;
        $default_struct["meta"] = array();
        $default_struct["meta"]["dassessment_id"] = $dassessment_id;
        $default_struct["meta"]["form_id"] = $this->fetchFormID($dassessment_id);
        $default_struct["meta"]["is_external"] = $this->determineExternal($dassessment_id);
        $default_struct["meta"]["is_deleted"] = $this->determineDeleted($dassessment_id);
        $default_struct["meta"]["is_expired"] = $this->determineExpired($dassessment_id);
        $default_struct["meta"]["deleted_target_count"] = 0;
        $default_struct["meta"]["assessment_uri_internal"] = null;
        $default_struct["meta"]["assessment_uri_external"] = null;
        $default_struct["meta"]["property_cleanup"] = array();
        $default_struct["assessment"] = array();
        $default_struct["assessment_method"] = array();
        $default_struct["assessment_method_meta"] = array();
        $default_struct["assessment_type"] = array();
        $default_struct["assessment_options"] = array();
        $default_struct["assessment_course_owners"] = array();
        $default_struct["linked_assessments"] = array();
        $default_struct["forwarded"] = array(); // The records that were created from this assessment (forwarded to)
        $default_struct["associated_record"] = array(); // record referred to by assessment->associated_record_id
        $default_struct["creator"] = array(); // The user record of the creator of the assessment task
        $default_struct["assessor"] = array();
        $default_struct["targets"] = array();
        $default_struct["progress"] = array(); // Includes progress responses
        $default_struct["feedback"] = array(); // Assessor and Target feedback
        $default_struct["distribution"] = $this->buildDefaultDistributionDataStructure(); // Empty array structure for all distribution related data
        $default_struct["form_dataset"] = array();
        return $default_struct;
    }
}
