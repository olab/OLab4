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
 * This is an abstraction layer for assessment forms.
 * This layer wraps all functionality for the scope of a single assessment.
 * This is the main point of interaction between any (pseudo) controller
 * and/or view functionality that needs access to anything related to a single
 * assessment record (i.e., it's progress responses, related form data, how to
 * validate responses, any meta data, etc.).
 *
 * @author Organization: Queen's University
 * @author Unit: Health Sciences, Education Technology Unit
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Entrada_Assessments_Assessment extends Entrada_Assessments_Base {

    protected $dassessment_id         = null;
    protected $aprogress_id           = null;
    protected $external_hash          = null;
    protected $fetch_deleted_targets  = false; // Include deleted targets in the dataset?
    protected $fetch_form_data        = false; // Include form data in the assessment dataset? This will pull in all data from the forms abstraction layer.
    protected $limit_dataset          = array(); // limit the dataset to these properties only (empty array means build entire dataset)
    protected $form_limit_dataset     = array(); // limit the related form's dataset
    protected $rubric_limit_dataset   = array(); // limit the related form's rubrics' dataset
    protected $global_storage         = "Entrada_Assessments_Assessment_Global_Cache"; // Specify the name of the global variable to use -- if it doesn't exist, PHP creates it)
    protected $hooks                  = array(); // Additional execution hooks

    // Worker object
    protected $assessment = null;

    public function __construct($arr = array()) {
        parent::__construct($arr);
        $this->assessment = new Entrada_Assessments_Workers_Assessment($this->buildActorArray($arr));

        // Our list of supported hooks.
        // Functions can be added at call-time to any of the listed categories.
        $this->hooks = array(
            "before-load" => array(
                // Executed before assessment->loadData
            ),
            "after-load" => array(
                // Executed immediately after assessment->loadData
            ),
            "before-update" => array(
                // Executed before assessment->updateProgress
            ),
            "after-update" => array(
                // Executed immediately after assessment->updateProgress
                // This executes after progress update, regardless of updateProgress' result.
            ),
            "before-submit" => array(
                // Executed before assessment->saveData
            ),
            "after-submit" => array(
                // Executed after assessment->saveData
                // This executes after successful submission only.
                // Failures are caught before reaching this hook.
            ),
            "assessment-method-assessment-notify" => array(
                // Executed inside of the assessment method hook. It is only executed if the assessment method hook is defined.
                // This hook handles sending pending assessment notifications.
            ),
            "assessment-method-flagging-notify" => array(
                // Executed inside of the assessment method hook. It is only executed if the assessment method hook is defined.
                // This hook handles sending notifications for flagged items.
            ),
            "assessment-method-statistic-completion" => array(
                // Executed inside of the assessment method hook. It is only executed if the assessment method hook is defined.
                // This hook saves an assessment completion statistic.
            ),
            "assessment-method-statistic-progress-time" => array(
                // Executed inside of the assessment method hook. It is only executed if the assessment method hook is defined.
                // This hook updates the progress record for assessment completion time.
            )
        );
    }

    public function getAssessmentID() {
        return $this->dassessment_id;
    }

    public function getDassessmentID() {
        return $this->dassessment_id;
    }

    public function setAssessmentID($dassessment_id) {
        $this->setDassessmentID($dassessment_id);
    }

    public function setDassessmentID($dassessment_id) {
        if ($this->dassessment_id !== $dassessment_id) {
            $this->dassessment_id = $dassessment_id;
            $this->setWorkerStale("assessment");
        }
    }

    public function isExternal() {
        return $this->assessment->isExternal();
    }

    public function setProgressID($aprogress_id, $set_stale = true) {
        if ($this->aprogress_id !== $aprogress_id) {
            $this->aprogress_id = $aprogress_id;
            if ($set_stale) {
                $this->setWorkerStale("assessment");
            }
        }
    }

    public function setAprogressID($aprogress_id, $set_stale = true) {
        $this->setProgressID($aprogress_id, $set_stale);
    }

    public function setDatasetLimit($limits = array()) {
        $this->limit_dataset = $limits;
        $this->setWorkerStale("assessment");
    }

    public function getProgressID() {
        return $this->aprogress_id;
    }

    public function getAprogressID() {
        return $this->aprogress_id;
    }

    public function getFetchFormData() {
        return $this->fetch_form_data;
    }

    public function setFetchFormData($bool) {
        if ($bool !== $this->fetch_form_data) {
            $this->fetch_form_data = $bool;
            $this->setWorkerStale("assessment");
        }
    }

    public function getFetchDeletedTargets() {
        return $this->fetch_deleted_targets;
    }

    public function setFetchDeletedTargets($bool) {
        if ($bool !== $this->fetch_deleted_targets) {
            $this->fetch_deleted_targets = $bool;
            $this->setWorkerStale("assessment");
        }
    }

    /**
     * Fetch the assessment record for this assessment.
     *
     * @param int|bool $specified_assessment_id
     * @param int|bool $specified_progress_id
     * @return bool|Models_Assessments_Assessor
     */
    public function getAssessmentRecord($specified_assessment_id = false, $specified_progress_id = false) {
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id,
            "fetch_form_data" => $this->fetch_form_data
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        return $this->assessment->getAssessmentRecord();
    }

    /**
     * Validate that the external hash supplied to this object matches the one on the assessment.
     * This uses the model directly to validate the hash. It is meant to be statically called outside
     * the context of the API's dataset.
     *
     * Since this is a static method, we can't call translate here, since it might not exist in the calling context.
     *
     * @param int $dassessment_id
     * @param string $external_hash
     * @return false|Models_Assessments_Assessor
     */
    public static function validateExternalHash($dassessment_id, $external_hash) {
        if (!$dassessment_id) {
            return false;
        }
        if (!$external_hash) {
            return false;
        }
        $assessment_record = Models_Assessments_Assessor::fetchRowByID($dassessment_id, null, true);
        if (!$assessment_record) {
            // Invalid ID
            return false;
        }
        if ($assessment_record->getDeletedDate()) {
            // Deleted record
            return false;
        }
        if ($assessment_record->getExternalHash()
            && $assessment_record->getExternalHash() == $external_hash
        ) {
            // Match
            return $assessment_record;
        }
        return false;
    }

    /**
     * Build an assessment URL string from the dataset.
     * 
     * @param null|int $target_record_id
     * @param null|string $target_type
     * @param bool $allow_external_url
     * @param bool|int $specified_assessment_id
     * @param bool|int $specified_progress_id
     * @return bool|string
     */
    public function getAssessmentURL($target_record_id = null, $target_type = null, $allow_external_url = false, $specified_assessment_id = false, $specified_progress_id = false) {
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id,
            "fetch_form_data" => $this->fetch_form_data
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        return $this->assessment->getAssessmentURL($target_record_id, $target_type, $allow_external_url);
    }

    /**
     * Return whether or not this assessment can be filled out by the current user.
     * It can be completed by the following:
     *   - Site admin
     *   - The assessor
     *   - Course Owner (PA/PC)
     *
     * All others return false.
     *
     * @param bool $is_admin
     * @param bool $specified_assessment_id
     * @param bool $specified_progress_id
     * @param $specified_target_id
     * @param $specified_target_type
     * @return bool
     */
    public function canUserCompleteAssessment($is_admin = false, $specified_assessment_id = false, $specified_progress_id = false, $specified_target_type = null, $specified_target_id = false) {
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id,
            "fetch_form_data" => $this->fetch_form_data
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        return $this->assessment->canActorComplete($is_admin, $specified_target_type, $specified_target_id);
    }

    /**
     * Return whether or not this current user can view the assessment.
     * It can be viewed by the following:
     *   - Site admin
     *   - The assessor
     *   - Course Owner (PA/PC)
     *   - The target when:
     *      - Assessment is completed
     *      - & Assessment is released
     *   - Academic Advisors where the target is one of theirs
     *
     * @param bool $override_primary
     * @param bool $override_secondary
     * @param $specified_target_type
     * @param $specified_target_id
     * @param bool $specified_assessment_id
     * @param bool $specified_progress_id
     * @return bool
     */
    public function canUserViewAssessment($override_primary = false, $override_secondary = false, $specified_target_type = null, $specified_target_id = false, $specified_assessment_id = false, $specified_progress_id = false) {
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id,
            "fetch_form_data" => $this->fetch_form_data
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        return $this->assessment->canActorView($override_primary, $override_secondary, $specified_target_type, $specified_target_id);
    }

    /**
     * Check if the assessment has an associated distribution.
     *
     * @param bool $specified_assessment_id
     * @param bool $specified_progress_id
     * @return bool
     */
    public function hasDistribution($specified_assessment_id = false, $specified_progress_id = false) {
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id,
            "fetch_form_data" => $this->fetch_form_data
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        $assessment_data = $this->assessment->fetchData();
        if ($assessment_data === false) {
            $this->addErrorMessages($this->assessment->getErrorMessages());
            return false;
        }
        return !empty($assessment_data["distribution"]["distribution_record"]);
    }

    /**
     * Fetch the flagged responses selected, return them in an array.
     *
     * @param bool|int $specified_severity_level
     * @param bool $specified_assessment_id
     * @param bool $specified_progress_id
     * @return bool|array
     */
    public function getFlaggedResponsesSelected($specified_severity_level = false, $specified_assessment_id = false, $specified_progress_id = false) {
        global $translate;
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id,
            "fetch_form_data" => $this->fetch_form_data
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        $current_progress = $this->assessment->getCurrentProgress();
        if (empty($current_progress)) {
            $this->addErrorMessage($translate->_("Unable to fetch progress responses."));
            return false;
        }
        $flagged_responses = array();
        foreach ($current_progress["progress_responses"] as $progress) {
            if ($specified_severity_level) {
                if ($progress["flag_response"] == $specified_severity_level) {
                    $flagged_responses[] = $progress;
                }
            } else {
                if ($progress["flag_response"]) {
                    $flagged_responses[] = $progress;
                }
            }
        }
        return $flagged_responses;
    }

    /**
     * Fetch relevant approval data for the current assessment.
     *
     * @param int|bool $specified_assessment_id
     * @param int|bool $specified_progress_id
     * @return array|bool
     */
    public function getAssessmentApprovalData($specified_assessment_id = false, $specified_progress_id = false) {
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id,
            "fetch_form_data" => $this->fetch_form_data
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        return $this->assessment->getApprovalDataForProgress();
    }

    /**
     * Get the current progress data from the dataset for the given/current progress ID.
     *
     * @param bool|int $specified_assessment_id
     * @param bool|int $specified_progress_id
     * @return array|bool
     */
    public function getCurrentAssessmentProgress($specified_assessment_id = false, $specified_progress_id = false) {
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id,
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        return $this->assessment->getCurrentProgress();
    }

    /**
     * Get information about the current target from the dataset.
     *
     * @param bool|int $target_record_id
     * @param bool|string $target_type
     * @param bool|int $specified_assessment_id
     * @param bool|int $specified_progress_id
     * @return array|bool
     */
    public function getCurrentAssessmentTarget($target_record_id = false, $target_type = false, $specified_assessment_id = false, $specified_progress_id = false) {
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id,
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        return $this->assessment->getCurrentTarget($target_record_id, $target_type);
    }

    /**
     * Get the current assessor data.
     *
     * @param bool|int $specified_assessment_id
     * @param bool|int $specified_progress_id
     * @return bool|array
     */
    public function getAssessor($specified_assessment_id = false, $specified_progress_id = false) {
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id,
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        $data = $this->assessment->fetchData();
        if (empty($data)) {
            return false;
        }
        return $data["assessor"];
    }

    /**
     * Get the first associated assessment target record data. Returns the ID of the target and
     * the type, e.g. array("target_value" => 1234 and "target_type" => "proxy_id")
     *
     * This target search is irrespective of aprogress ID.
     *
     * @param bool $specified_assessment_id
     * @param bool $specified_progress_id
     * @return array|bool
     */
    public function getFirstAssessmentTarget($specified_assessment_id = false, $specified_progress_id = false) {
        global $translate;
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id,
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        $assessment_data = $this->assessment->fetchData();
        if ($assessment_data === false) {
            $this->addErrorMessages($this->assessment->getErrorMessages());
            return false;
        }
        if ($this->assessment->isForwarded()) {
            $this->addErrorMessage($translate->_("This task was forwarded to a new assessor."));
            return false;
        }
        if (!is_array($assessment_data["targets"]) || empty($assessment_data["targets"])) {
            // Check if targets had existed, but are now deleted
            if ($this->assessment->getDeletedTargetCount()) {
                $this->addErrorMessage($translate->_("All targets for this assessment have been deleted."));
                return false;
            } else {
                $this->addErrorMessage($translate->_("No targets found for this assessment."));
                return false;
            }
        }

        $first_target = array();
        if ($this->assessment->isDeleted()) {
            // When the assessment is deleted, we want to return the first target we can find that has completed progress.
            foreach ($assessment_data["targets"] as $target) {
                if (!empty($first_target)) {
                    break;
                }
                $all_progress_for_target = $this->assessment->getProgressForTarget($target["target_value"], $target["target_type"]);
                if (empty($all_progress_for_target)) {
                    continue;
                }
                foreach ($all_progress_for_target as $progress_for_target) {
                    if (!$progress_for_target["deleted_date"]
                        && $progress_for_target["progress_value"] == "complete"
                    ) {
                        $first_target = $target;
                        break;
                    }
                }
            }
        } else {
            // Otherwise, we find the first target that is not deleted.
            foreach ($assessment_data["targets"] as $target) {
                if ($target["deleted_date"]) {
                    continue;
                } else {
                    $first_target = $target;
                }
            }
            // If no targets were found, then we try and find the first deleted completed task.
            if (empty($first_target) && count($assessment_data["targets"])) {
                foreach ($assessment_data["targets"] as $target) {
                    if (!empty($first_target)) {
                        break;
                    }
                    $all_progress_for_target = $this->assessment->getProgressForTarget($target["target_value"], $target["target_type"]);
                    if (empty($all_progress_for_target)) {
                        continue;
                    }
                    foreach ($all_progress_for_target as $progress_for_target) {
                        if (!$progress_for_target["deleted_date"]
                            && $progress_for_target["progress_value"] == "complete"
                        ) {
                            $first_target = $target;
                            break;
                        }
                    }
                }
            }
        }
        if (empty($first_target)) {
            if ($this->assessment->getDeletedTargetCount()) {
                $this->addErrorMessage($translate->_("All targets for this assessment have been deleted."));
                return false;
            } else {
                $this->addErrorMessage($translate->_("No targets found for this assessment."));
                return false;
            }
        }
        return array("target_value" => $first_target["target_value"], "target_type" => $first_target["target_type"]);
    }

    /**
     * Fetch the related distribution information for an assessment.
     *
     * @param bool $specified_assessment_id
     * @param bool $specified_progress_id
     * @return bool|array
     */
    public function getDistributionInformation($specified_assessment_id = false, $specified_progress_id = false) {
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id,
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        $assessment_data = $this->assessment->fetchData();
        if ($assessment_data === false) {
            $this->addErrorMessages($this->assessment->getErrorMessages());
            return false;
        }
        return $assessment_data["distribution"];
    }

    /**
     * For the form items of objective type (via the objective selector), fetch the parent-child hierarchy relationships for the
     * current (or specified) progress ID.
     *
     * @param bool|int $specified_assessment_id
     * @param bool|int $specified_progress_id
     * @return array|bool
     */
    public function getSelectedFormElementsObjectiveHierarchy($specified_assessment_id = false, $specified_progress_id = false) {
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id,
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        $objectives = $this->assessment->getSelectedObjectiveHierarchy();
        if ($objectives === false) {
            $this->addErrorMessages($this->assessment->getErrorMessages());
            return false;
        }
        return $objectives;
    }

    /**
     * Fetch a formatted list of assessment targets, with some progress data.
     *
     * @param int|bool $specified_assessment_id
     * @param int|bool $specified_progress_id
     * @return array|bool
     */
    public function getAssessmentTargetList($specified_assessment_id = false, $specified_progress_id = false) {
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id,
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        return $this->assessment->getAssessmentTargetList();
    }

    /**
     * Fetch all the progress within the dataset for a specific target.
     *
     * @param int $target_record_id
     * @param string $target_type
     * @param int|bool $specified_assessment_id
     * @param int|bool $specified_progress_id
     * @return array|bool
     */
    public function getAssessmentProgressForTarget($target_record_id, $target_type, $specified_assessment_id = false, $specified_progress_id = false) {
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id,
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        return $this->assessment->getProgressForTarget($target_record_id, $target_type);
    }

    /**
     * Fetch the feedback for the current target.
     *
     * @param int $target_record_id
     * @param string$target_type
     * @param int|bool $specified_assessment_id
     * @param int|bool $specified_progress_id
     * @return array|bool
     */
    public function getAssessmentFeedbackForTarget($target_record_id, $target_type, $specified_assessment_id = false, $specified_progress_id = false) {
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id,
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        return $this->assessment->getFeedbackForTarget($target_record_id, $target_type);
    }

    /**
     * Count the completed progress records for a given target.
     *
     * @param $target_record_id
     * @param $target_type
     * @param bool $specified_assessment_id
     * @param bool $specified_progress_id
     * @return bool|int
     */
    public function getCountCompleteProgressForTarget($target_record_id, $target_type, $specified_assessment_id = false, $specified_progress_id = false) {
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id,
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        return $this->assessment->getCountCompleteProgress($target_record_id, $target_type);
    }

    /**
     * Fetch the number of pending progress records there are for the current set of assessment targets.
     * This number includes the targets records that have no associated progress records.
     *
     * @param int|bool $specified_assessment_id
     * @param int|bool $specified_progress_id
     * @return bool
     */
    public function getCountUniqueTargetsPending($specified_assessment_id = false, $specified_progress_id = false) {
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id,
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        return $this->assessment->getCountUniqueTargetsPending();
    }

    /**
     * Fetch the number of in-progress progress records there are for the current set of assessment targets.
     *
     * @param int|bool $specified_assessment_id
     * @param int|bool $specified_progress_id
     * @return bool
     */
    public function getCountUniqueTargetsInProgress($specified_assessment_id = false, $specified_progress_id = false) {
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id,
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        return $this->assessment->getCountUniqueTargetsInProgress();
    }

    /**
     * Fetch the number of completed progress records there are for the current set of assessment targets.
     *
     * @param int|null $target_id
     * @param int|null $target_type
     * @param int|bool $specified_assessment_id
     * @param int|bool $specified_progress_id
     * @return bool
     */
    public function getCountUniqueTargetsComplete($target_id = null, $target_type = null, $specified_assessment_id = false, $specified_progress_id = false) {
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id,
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        return $this->assessment->getCountUniqueTargetsComplete($target_id, $target_type);
    }

    /**
     * Determines if the provided assessment is completed overall, taking into account unique targets, min/max attempts, etc.
     *
     * @param bool $specified_assessment_id
     * @param bool $specified_progress_id
     * @return bool
     */
    public function isOverallAssessmentCompleted($specified_assessment_id = false, $specified_progress_id = false) {
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }

        return $this->assessment->isOverallCompleted();
    }

    /**
     * Fetch the first aprogress_id in the dataset.
     *
     * @param null|int $specified_target_id
     * @param null|int $specified_target_type
     * @param string $progress_value
     * @param int|bool $specified_assessment_id
     * @param int|bool $specified_progress_id
     * @return bool|null|int
     */
    public function getLatestProgressID($specified_target_id = null, $specified_target_type = null, $progress_value = null, $specified_assessment_id = false, $specified_progress_id = false) {
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id,
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        $assessment_data = $this->assessment->fetchData();
        if ($assessment_data === false) {
            $this->addErrorMessages($this->assessment->getErrorMessages());
            return false;
        }
        if (empty($assessment_data["progress"])) {
            // Nothing yet
            return null;
        }
        foreach (array_reverse($assessment_data["progress"]) as $progress_data) {
            if ($progress_value) {
                // If specified, we're only considering the given progress_value in our search
                if ($progress_data["deleted_date"]
                    || $progress_data["progress_value"] != $progress_value
                ) {
                    continue;
                }
            } else {
                // Default is to consider all values other than cancelled or null
                if ($progress_data["deleted_date"] ||
                    $progress_data["progress_value"] == "cancelled" ||
                    $progress_data["progress_value"] == null
                ) {
                    continue;
                }
            }
            if ($specified_target_id && $specified_target_type) {
                if ($progress_data["target_record_id"] == $specified_target_id &&
                    $progress_data["target_type"] == $specified_target_type
                ) {
                    return $progress_data["aprogress_id"];
                }
            } else {
                return $progress_data["aprogress_id"];
            }
        }
        // Was unable to find a non-deleted progress record, but null is OK (it will trigger a new response record to be created)
        return null;
    }

    /**
     * Check if an assessment is deleted
     *
     * @param int|bool $specified_assessment_id
     * @param int|bool $specified_progress_id
     * @return bool
     */
    public function isAssessmentDeleted($specified_assessment_id = false, $specified_progress_id = false) {
        $construction = array(
            "dassessment_id" => $this->whichID("dassessment_id", $specified_assessment_id),
            "aprogress_id" => $this->whichID("aprogress_id", $specified_progress_id),
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        return $this->assessment->isDeleted();
    }

    /**
     * Check if an assessment is expired
     *
     * @param int|bool $specified_assessment_id
     * @param int|bool $specified_progress_id
     * @return bool
     */
    public function isAssessmentExpired($specified_assessment_id = false, $specified_progress_id = false) {
        $construction = array(
            "dassessment_id" => $this->whichID("dassessment_id", $specified_assessment_id),
            "aprogress_id" => $this->whichID("aprogress_id", $specified_progress_id),
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        return $this->assessment->isExpired();
    }

    /**
     * Check if this assessment was forwarded to someone.
     *
     * @param bool $specified_assessment_id
     * @param bool $specified_progress_id
     * @return bool
     */
    public function isAssessmentForwarded($specified_assessment_id = false, $specified_progress_id = false) {
        $construction = array(
            "dassessment_id" => $this->whichID("dassessment_id", $specified_assessment_id),
            "aprogress_id" => $this->whichID("aprogress_id", $specified_progress_id),
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        return $this->assessment->isForwarded();
    }

    /**
     * Fetch wether the assessment task for the given progress ID is approved/reviewed by an approver.
     * Return statuses can be "approved", "pending" or "hidden"
     *
     * @param int|bool $specified_assessment_id
     * @param int|bool $specified_progress_id
     * @return bool|string
     */
    public function getAssessmentApprovalStatus($specified_assessment_id = false, $specified_progress_id = false) {
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id,
            "fetch_form_data" => $this->fetch_form_data
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        $approval_data = $this->assessment->getApprovalDataForProgress();
        if (empty($approval_data)) {
            return false;
        }
        return $approval_data["approval_status"];
    }

    /**
     * Check if an assessment is completed, for a specific progress ID.
     *
     * @param int|bool $specified_assessment_id
     * @param int|bool $specified_progress_id
     * @return bool
     */
    public function isAssessmentCompleted($specified_assessment_id = false, $specified_progress_id = false) {
        $construction = array(
            "dassessment_id" => $this->whichID("dassessment_id", $specified_assessment_id),
            "aprogress_id" => $this->whichID("aprogress_id", $specified_progress_id),
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        return $this->assessment->isCompleted();
    }

    /**
     * Return if feedback required.
     *
     * @param int|bool $specified_assessment_id
     * @param int|bool $specified_progress_id
     * @return bool
     */
    public function isAssessmentFeedbackRequired($specified_assessment_id = false, $specified_progress_id = false) {
        $construction = array(
            "dassessment_id" => $this->whichID("dassessment_id", $specified_assessment_id),
            "aprogress_id" => $this->whichID("aprogress_id", $specified_progress_id),
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        return $this->assessment->isFeedbackRequired();
    }

    /**
     * Check if the associated distribution for this assessment is deleted.
     * Returns false when no distribution exists for this assessment.
     *
     * @param bool $specified_assessment_id
     * @param bool $specified_progress_id
     * @return bool
     */
    public function isDistributionDeleted($specified_assessment_id = false, $specified_progress_id = false) {
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id,
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        return $this->assessment->isDistributionDeleted();
    }

    /**
     * Change the target of a progress record; moves progress for one target to another.
     *
     * @param $destination_target_id
     * @param $destination_target_type
     * @param bool|int $specified_assessment_id
     * @param bool|int $specified_progress_id
     * @return bool
     */
    public function moveAssessmentProgress($destination_target_id, $destination_target_type, $specified_assessment_id = false, $specified_progress_id = false) {
        $construction = array(
            "dassessment_id" => $this->whichID("dassessment_id", $specified_assessment_id),
            "aprogress_id" => $this->whichID("aprogress_id", $specified_progress_id)
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        if (!$this->assessment->moveProgress($destination_target_id, $destination_target_type)) {
            $this->addErrorMessages($this->assessment->getErrorMessages());
            return false;
        }
        $this->setAprogressID($this->assessment->getAprogressID());
        $this->setWorkerStale("assessment");
        return true;
    }

    /**
     * Directly update the feedback record for an assessment for the target's feedback. This does not modify the assessor's values.
     *
     * @param $assessor_id
     * @param $assessor_type
     * @param $target_record_id
     * @param $target_scope
     * @param $feedback_response
     * @param $comments
     * @param bool $mark_complete
     * @param bool $specified_assessment_id
     * @param bool $specified_progress_id
     * @return bool
     */
    public function updateTargetFeedback($assessor_id, $assessor_type, $target_record_id, $target_scope, $feedback_response, $comments, $mark_complete = false, $specified_assessment_id = false, $specified_progress_id = false) {
        global $translate;
        $construction = array(
            "dassessment_id" => $this->whichID("dassessment_id", $specified_assessment_id),
            "aprogress_id" => $this->whichID("aprogress_id", $specified_progress_id)
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        // Check if this actor is even supposed to be able to submit feedback
        if ($this->actor_proxy_id != $target_record_id) {
            // Only the target can update target feedback
            $this->addErrorMessage($translate->_("Only the target can update target feedback."));
            return false;
        }
        if (!$this->assessment->updateFeedback(null, $feedback_response, $assessor_id, $assessor_type, $target_record_id, $target_scope, $comments, $mark_complete)) {
            $this->addErrorMessages($this->assessment->getErrorMessages());
            return false;
        }
        return true;
    }

    /**
     * Directly update the feedback record for an assessment for the assessor's feedback. This does not modify the target's values.
     *
     * @param $assessor_id
     * @param $assessor_type
     * @param $target_record_id
     * @param $target_scope
     * @param $feedback_response
     * @param bool $is_admin
     * @param bool $specified_assessment_id
     * @param bool $specified_progress_id
     * @param $specified_target_type
     * @return bool
     */
    public function updateAssessorFeedback($assessor_id, $assessor_type, $target_record_id, $target_scope, $feedback_response, $is_admin = false, $specified_assessment_id = false, $specified_progress_id = false, $specified_target_type = false) {
        global $translate;
        $construction = array(
            "dassessment_id" => $this->whichID("dassessment_id", $specified_assessment_id),
            "aprogress_id" => $this->whichID("aprogress_id", $specified_progress_id)
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        if ($assessor_type == "internal") { // Externals can submit the feedback record without an access check.
            if (!$this->assessment->canActorComplete($is_admin, $specified_target_type, $target_record_id)) {
                $this->addErrorMessage($translate->_("You do not have permission to submit this feedback."));
                return false;
            }
        }
        if (!$this->assessment->updateFeedback($feedback_response, null, $assessor_id, $assessor_type, $target_record_id, $target_scope, null)) {
            $this->addErrorMessages($this->assessment->getErrorMessages());
            return false;
        }
        return true;
    }

    /**
     * Set the approval status for the given assessment.
     *
     * @param string $approval_status
     * @param string|null $comments
     * @param bool $specified_assessment_id
     * @param bool $specified_progress_id
     * @return bool
     */
    public function updateAssessmentApproval($approval_status, $comments, $specified_assessment_id = false, $specified_progress_id = false) {
        $construction = array(
            "dassessment_id" => $this->whichID("dassessment_id", $specified_assessment_id),
            "aprogress_id" => $this->whichID("aprogress_id", $specified_progress_id)
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        if (!$this->assessment->updateApproval($approval_status, $comments)) {
            $this->addErrorMessages($this->assessment->getErrorMessages());
            return false;
        }
        return true;
    }

    /**
     * Create a new assessment record.
     *
     * @param array $assessment_data
     * @param array $target_data
     * @return bool
     */
    public function createAssessment($assessment_data, $target_data = array()) {
        global $translate;
        if (empty($assessment_data)) {
            $this->addErrorMessage($translate->_("Please specify an assessor for this assessment."));
            return false;
        }
        // Create blank assessment object
        if (!$this->buildAssessmentObject(array("dassessment_id" => null, "aprogress_id" => null))) {
            return false;
        }
        if (!$this->assessment->create($assessment_data, $target_data)) {
            $this->addErrorMessages($this->assessment->getErrorMessages());
            return false;
        }
        $this->dassessment_id = $this->assessment->getAssessmentID();
        $this->aprogress_id = null;
        $this->setStale();
        return true;
    }

    /**
     * Create an assessment target record for an existing assessment.
     *
     * @param array $target_data
     * @return bool
     */
    public function createAssessmentTarget($target_data) {
        global $translate;
        if (empty($target_data)) {
            $this->addErrorMessage($translate->_("Please specify a target for this assessment."));
            return false;
        }
        // Create blank assessment object
        if (!$this->buildAssessmentObject(array("dassessment_id" => $this->dassessment_id, "aprogress_id" => null))) {
            return false;
        }
        if (!$this->assessment->createTarget($target_data)) {
            $this->addErrorMessages($this->assessment->getErrorMessages());
            return false;
        }
        $this->dassessment_id = $this->assessment->getAssessmentID();
        $this->aprogress_id = null;
        $this->setStale();
        return true;
    }

    /**
     * Create an assessment option for an existing assessment.
     *
     * @param $mode
     * @param $option_data
     * @param bool $specified_assessment_id
     * @param bool $specified_progress_id
     * @return bool
     */
    public function createAssessmentOptions($mode, $option_data, $specified_assessment_id = false, $specified_progress_id = false) {
        $construction = array(
            "dassessment_id" => $this->whichID("dassessment_id", $specified_assessment_id),
            "aprogress_id" => $this->whichID("aprogress_id", $specified_progress_id)
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        if (!$this->assessment->createAssessmentOptions($mode, $option_data)) {
            $this->addErrorMessages($this->assessment->getErrorMessages());
            return false;
        }
        $this->setStale();
        return true;
    }

    /**
     * Create a new progress record, if possible.
     * This won't create a new progress record if there is already one in progress.
     *
     * @param $target_id
     * @param $target_type
     * @param bool $specified_assessment_id
     * @param bool $specified_progress_id
     * @return bool
     */
    public function saveNewAssessmentProgress($target_id, $target_type, $specified_assessment_id = false, $specified_progress_id = false) {
        global $translate;
        $construction = array(
            "dassessment_id" => $this->whichID("dassessment_id", $specified_assessment_id),
            "aprogress_id" => $this->whichID("aprogress_id", $specified_progress_id)
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        $assessment_data = $this->assessment->fetchData();
        if (empty($assessment_data)) {
            $this->addErrorMessage($translate->_("Unable to fetch assessment dataset (assessment is invalid)."));
            return false;
        }
        if (empty($assessment_data["progress"])) {
            // There's no existing progress, so save a new one and quit.
            if (!$this->assessment->saveNewProgress($target_id, $target_type)) {
                $this->addErrorMessages($this->assessment->getErrorMessages());
                return false;
            } else {
                return true;
            }
        }
        // At this point, we have some progress. So let's find out what the status of it is.
        // If there's one in progress, and the progress ID doesn't match, throw error (don't allow creating a new progress record)
        $progress_count = 0;
        foreach ($assessment_data["progress"] as $progress) {
            if ($progress["target_type"] == $target_type && $progress["target_record_id"] == $target_id) {
                $progress_count++;
                if ($progress["progress_value"] == "inprogress") {
                    $this->addErrorMessage($translate->_("Unable to save new progress: an assessment is already in progress."));
                    return false;
                }
            }
        }
        // Don't create a new record if we're already at our max number of attempts
        if ($progress_count >= $assessment_data["assessment"]["max_submittable"]) {
            $this->addErrorMessage($translate->_("Unable to save new progress: maximum number of attempts reached."));
            return false;
        }
        // If we haven't exited at this point, then attempt to create the progress record.
        if (!$this->assessment->saveNewProgress($target_id, $target_type)) {
            $this->addErrorMessages($this->assessment->getErrorMessages());
            return false;
        } else {
            return true;
        }
    }

    /**
     * Delete a specific assessment record (does not delete target records).
     *
     * @param bool $specified_assessment_id
     * @param bool $specified_progress_id
     * @return bool
     */
    public function deleteAssessment($specified_assessment_id = false, $specified_progress_id = false) {
        $construction = array(
            "dassessment_id" => $this->whichID("dassessment_id", $specified_assessment_id),
            "aprogress_id" => $this->whichID("aprogress_id", $specified_progress_id)
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        $status = $this->assessment->delete();
        if ($status === false) {
            $this->addErrorMessages($this->assessment->getErrorMessages());
        }
        $this->setStale();
        return $status;
    }

    /**
     * Delete a specific assessment by a target.
     *
     * If, when the target is deleted, there are no more active targets, this method will delete the entire assessment, otherwise,
     * this method simply deletes the single target.
     *
     * @param int $atarget_id
     * @param int|null $reason_id
     * @param string|null $reason_note
     * @param bool $specified_assessment_id
     * @param bool $specified_progress_id
     * @return bool
     */
    public function deleteAssessmentByTarget($atarget_id, $reason_id = null, $reason_note = null, $specified_assessment_id = false, $specified_progress_id = false) {
        $construction = array(
            "dassessment_id" => $this->whichID("dassessment_id", $specified_assessment_id),
            "aprogress_id" => $this->whichID("aprogress_id", $specified_progress_id)
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        // Delete the single target record, with a reason
        if (!$this->assessment->deleteTarget($atarget_id, $reason_id, $reason_note)) {
            $this->addErrorMessages($this->assessment->getErrorMessages());
            return false;
        }
        // Fetch an updated dataset (deleteTargets makes the previous set stale)
        $updated_dataset = $this->assessment->fetchData();
        // Check for deleted targets (we might or might not be including deleted targets in the dataset, so
        // we have to safely iterate through the targets (checking each one for deleted dates)
        $all_targets_deleted = false;
        if (empty($updated_dataset["targets"])) {
            $all_targets_deleted = true;
        } else {
            foreach ($updated_dataset["targets"] as $updated_target) {
                if (!$updated_target["deleted_date"]) {
                    return true; // There's at least one target left, so we can exit here.
                }
            }
        }
        // At least one target exists, and we have already successfully deleted a target, so exit.
        if (!$all_targets_deleted) {
            return true;
        }
        // At this point, all targets have been deleted, so we can delete the assessment
        $status = $this->assessment->delete();
        if ($status === false) {
            $this->addErrorMessages($this->assessment->getErrorMessages());
        }
        $this->setStale();
        return $status;
    }

    /**
     * Method to delete a future tasks.
     * When deleting a future task, the corresponding assessment record must be created and immediately deleted.
     *
     * @param $future_task_id
     * @param $deleted_reason_id
     * @param $deleted_reason_notes
     * @return bool
     */
    public function deleteFutureTaskByID($future_task_id, $deleted_reason_id, $deleted_reason_notes) {
        global $translate;
        if (!$future_task_record = Models_Assessments_FutureTaskSnapshot::fetchRowByID($future_task_id)) {
            $this->addErrorMessage($translate->_("Future task record does not exist."));
            return false;
        }
        $future_task_data = $future_task_record->toArray();

        // Check if a task with this exact data exists.
        $existing_assessment = Models_Assessments_Assessor::fetchRowByADistributionIDAssessorTypeAssessorValueDeliveryDateAssociatedRecordIDAssociatedRecordType(
            $future_task_data["adistribution_id"],
            $future_task_data["assessor_type"],
            $future_task_data["assessor_value"],
            $future_task_data["delivery_date"],
            $future_task_data["associated_record_id"],
            $future_task_data["associated_record_type"]
        );
        $single_target_to_delete = array(
            "target_value" => $future_task_data["target_value"],
            "target_type" => $future_task_data["target_type"],
            "task_type" => $future_task_data["task_type"],
            "adistribution_id" => $future_task_data["adistribution_id"],
            "updated_date" => time(),
            "updated_by" => $this->actor_proxy_id,
            "deleted_date" => time(),
            "deleted_by" => $this->actor_proxy_id,
            "deleted_reason_id" => $deleted_reason_id,
            "deleted_reason_notes" => $deleted_reason_notes
        );
        if (!$existing_assessment) {
            // The assessment does not exist, so let's create it and mark the target as deleted.
            // We don't mark the assessment as deleted, however, since more valid targets could be made for this assessment.
            // Future Tasks are limited in scope to distribution-based assessments; they will not need to be created with anything other
            // than the related dates. Assessment method and type information is not required.
            $result = $this->createAssessment(
                array(
                    "adistribution_id" => $future_task_data["adistribution_id"],
                    "assessor_type" => $future_task_data["assessor_type"],
                    "assessor_value" => $future_task_data["assessor_value"],
                    "rotation_start_date" => $future_task_data["rotation_start_date"],
                    "rotation_end_date" => $future_task_data["rotation_end_date"],
                    "delivery_date" => $future_task_data["delivery_date"],
                    "form_id" => $future_task_data["form_id"],
                    "assessment_type_id" => $future_task_data["assessment_type_id"],
                    "organisation_id" => $future_task_data["organisation_id"],
                    "associated_record_id" => $future_task_data["associated_record_id"],
                    "associated_record_type" => $future_task_data["associated_record_type"],
                    "min_submittable" => $future_task_data["min_submittable"],
                    "max_submittable" => $future_task_data["max_submittable"],
                    "feedback_required" => $future_task_data["feedback_required"],
                    "start_date" => $future_task_data["start_date"],
                    "end_date" => $future_task_data["end_date"],
                    "updated_by" => $this->actor_proxy_id,
                    "updated_date" => time()
                ),
                array(
                    $single_target_to_delete
                )
            );
        } else {
            // The assessment already exists, so we only have to delete the one target record.
            $this->setDassessmentID($existing_assessment->getID());
            $result = $this->createAssessmentTarget($single_target_to_delete);
        }
        if (!$result) {
            // Error messages returned via createAssessment
            return false;
        }
        // Delete the future task record
        $future_task_data["deleted_date"] = time();
        $future_task_data["deleted_by"] = $this->actor_proxy_id;
        $future_task_record->fromArray($future_task_data);
        if (!$future_task_record->update()) {
            $this->addErrorMessage($translate->_("This task was deleted, however, we are unable to remove it from your task list at this time."));
            return false;
        }
        return true;
    }

    /**
     * Fetch all assessment related data and return it in a data structure.
     *
     * @param bool $specified_assessment_id
     * @param bool $specified_progress_id
     * @return array|bool
     */
    public function fetchAssessmentData($specified_assessment_id = false, $specified_progress_id = false) {
        $construction = array(
            "dassessment_id" => $this->whichID("dassessment_id", $specified_assessment_id),
            "aprogress_id" => $this->whichID("aprogress_id", $specified_progress_id)
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        $data = $this->assessment->fetchData();
        if ($data === false) {
            $this->addErrorMessages($this->assessment->getErrorMessages());
        }
        return $data;
    }

    /**
     * Directly update progress responses. This is meant to be lightweight to be called via AJAX functionality.
     * updateProgress sanitizes and validates the posted data.
     *
     * @param array $posted_data
     * @param int $target_id
     * @param string $target_type
     * @param bool $dassessment_id
     * @param bool $aprogress_id
     * @return bool
     */
    public function updateProgressResponses($posted_data, $target_id, $target_type, $dassessment_id = false, $aprogress_id = false) {
        global $translate;
        if (!is_array($posted_data) || empty($posted_data)) {
            $this->addErrorMessage($translate->_("No data submitted. Unable to update progress."));
            return false;
        }
        $construction = array(
            "dassessment_id" => $this->whichID("dassessment_id", $dassessment_id),
            "aprogress_id" => $this->whichID("aprogress_id", $aprogress_id),
            "fetch_form_data" => false
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        if (!$this->executeHooks("before-update")) {
            return false;
        }
        $update_status = $this->assessment->updateProgress($posted_data, $target_id, $target_type, false);
        $this->setAprogressID($this->assessment->getAprogressID());
        $hook_status = $this->executeHooks("after-update");
        if (!$update_status || !$hook_status) {
            $this->addErrorMessages($this->assessment->getErrorMessages());
            return false;
        }
        return true;
    }

    /**
     * Save an assessment by handling a $_POSTed array of data, turning it into a dataset, and saving the sanitized/validated dataset.
     * The dataset is the structure returned via fetchAssessmentData().
     *
     * @param array $posted_data
     * @param bool $mark_complete
     * @param bool $create_progress_record
     * @param bool|int $specified_assessment_id
     * @param bool|int $specified_progress_id
     * @return bool
     */
    public function saveAssessmentByPost($posted_data, $mark_complete = false, $create_progress_record = false, $specified_assessment_id = false, $specified_progress_id = false) {
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id,
            "fetch_form_data" => false
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        // Execute pre-load functionality hooks
        if (!$this->executeHooks("before-load")) {
            // We care about what the hooks return; any critical failures in a hook should cause us to bail out, even if our posted data was loaded proeprly.
            return false;
        }

        // Load the posted data into the dataset.
        // The after-load (post-validation) hooks are immediately executed.
        $load_status = $this->assessment->loadData($posted_data, $mark_complete);
        $post_load_status = $this->executeHooks("after-load");

        if (!$load_status || !$post_load_status) {
            $this->addErrorMessages($this->assessment->getErrorMessages());
            return false;
        }
        // Execute pre-submission functionality before saving progress
        if (!$this->executeHooks("before-submit")) {
            return false;
        }
        // If specified, attempt to create a progress record beforehand.
        if ($create_progress_record) {
            if (is_array($posted_data) && array_key_exists("target_record_id", $posted_data) && array_key_exists("target_type", $posted_data)) {
                $target_id = clean_input($posted_data["target_record_id"], array("trim", "int"));
                $target_type = clean_input($posted_data["target_type"], array("trim", "notags"));
                if ($target_id && $target_type) {
                    if (!$this->assessment->saveNewProgress($target_id, $target_type)) {
                        $this->addErrorMessages($this->assessment->getErrorMessages());
                        return false;
                    } else {
                        $this->setAprogressID($this->assessment->getAprogressID());
                        $this->setWorkerStale("assessment");
                    }
                }
            }
        }
        // If we loaded successfully, save the assessment.
        if (!$this->assessment->saveData($mark_complete)) {
            $this->addErrorMessages($this->assessment->getErrorMessages());
            return false;
        }
        // Assessment saved, rebuild dataset
        $assessment_data = $this->assessment->fetchData();
        if (empty($assessment_data)) {
            $this->addErrorMessages($this->assessment->getErrorMessages());
            return false;
        }
        // Execute post-submission functionality
        if (!$this->executeHooks("after-submit")) {
            return false;
        }
        // Saved OK
        return true;
    }

    /**
     * For the given array of $_POST data, apply the given submission ooptions and set the various hooks (according to the hook_options), and save the assessment.
     * This method handles all of the functionality required in order to be able to call saveAssessmentByPost, and if everything passes, calls it.
     *
     * @param array $posted_data
     * @param array $submission_options
     * @param array $hook_options
     * @param bool $user_is_admin
     * @param bool|int $specified_assessment_id
     * @param bool|int|null $specified_progress_id
     * @param $specified_target_type
     * @param $specified_target_id
     * @return bool
     */
    public function handlePostedAssessmentSubmission($posted_data, $submission_options = array(), $hook_options = array(), $user_is_admin = false, $specified_assessment_id = false, $specified_progress_id = false, $specified_target_type = null, $specified_target_id = false) {
        global $translate;
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        $assessment_data = $this->assessment->fetchData();
        if (empty($assessment_data)) {
            $this->addErrorMessage($translate->_("Unable to retrieve assessment data."));
            return false;
        }
        $target_record_id = Entrada_Utilities::arrayValueOrDefault($submission_options, "target_record_id", false);
        $target_type = Entrada_Utilities::arrayValueOrDefault($submission_options, "target_type", false);
        $current_target = $this->assessment->getCurrentTarget($target_record_id, $target_type);
        if (empty($current_target)) {
            $this->addErrorMessage($translate->_("Current assessment target not found."));
            return false;
        }
        $current_progress = $this->assessment->getCurrentProgress();
        $saved = false;
        $actor_can_complete_assessment = $this->assessment->canActorComplete($user_is_admin, $specified_target_type, $specified_target_id);
        $send_flagging_notifications = Entrada_Utilities::arrayValueOrDefault($submission_options, "send_flagging_notifications", false);

        /**
         * Check if the submit button was pressed.
         */
        if (isset($posted_data["submit_form"]) && !empty($posted_data["submit_form"])) {
            $finish_assessment = true;
        } else {
            $finish_assessment = false; // Flag if we're finalizing the assessment (false when saving as draft)
        }

        $processed_post["aprogress_id"] = null;
        if (isset($posted_data["aprogress_id"]) && $tmp_input = clean_input($posted_data["aprogress_id"], array("trim", "int"))) {
            $processed_post["aprogress_id"] = $tmp_input;
        }
        $processed_post["dassessment_id"] = null;
        if (isset($posted_data["dassessment_id"]) && $tmp_input = clean_input($posted_data["dassessment_id"], array("trim", "int"))) {
            $processed_post["dassessment_id"] = $tmp_input;
        }
        $processed_post["target_record_id"] = null;
        if (isset($posted_data["target_record_id"]) && $tmp_input = clean_input($posted_data["target_record_id"], array("trim", "int"))) {
            $processed_post["target_record_id"] = $tmp_input;
        }
        $processed_post["target_record_id"] = null;
        if (isset($posted_data["target_record_id"]) && $tmp_input = clean_input($posted_data["target_record_id"], array("trim", "int"))) {
            $processed_post["target_record_id"] = $tmp_input;
        }

        /**
         * Check if feedback was posted, to handle it outside of the regular assessment submission context.
         */
        $processed_post["target_feedback_response"] = null;
        if (isset($posted_data["target_feedback_response"]) && $tmp_input = clean_input($posted_data["target_feedback_response"], array("trim", "striptags"))) {
            $processed_post["target_feedback_response"] = $tmp_input;
        }
        $processed_post["assessor_feedback_response"] = null;
        if (isset($posted_data["assessor_feedback_response"]) && $tmp_input = clean_input($posted_data["assessor_feedback_response"], array("trim", "striptags"))) {
            $processed_post["assessor_feedback_response"] = $tmp_input;
        }
        $processed_post["feedback_meeting_comments"] = null;
        if (isset($posted_data["feedback_meeting_comments"]) && $tmp_input = clean_input($posted_data["feedback_meeting_comments"], array("trim", "striptags"))) {
            $processed_post["feedback_meeting_comments"] = $tmp_input;
        }
        $processed_post["assessment_cue"] = null;
        if (isset($posted_data["assessment_cue"]) && $tmp_input = clean_input($posted_data["assessment_cue"], array("trim", "striptags"))) {
            $processed_post["assessment_cue"] = $tmp_input;
        }

        /**
         * Get feedback options. This the options array is passed directly to the feedback view object.
         */
        $feedback_options = $this->assessment->getFeedbackOptions($target_record_id, $target_type);
        $feedback_required = $this->assessment->isFeedbackRequired();
        $update_feedback_only = $feedback_options["update_feedback_only"];
        $feedback_pending = $feedback_options["feedback_pending"];

        /**
         * Determine if approval is required
         */
        $approval_data = $this->assessment->getApprovalDataForProgress();
        $approval_required = empty($approval_data) ? false : true;

        /**
         * When finishing an assessment, handle related hook and dependencies before attempting to process assessment submission.
         */
        if ($finish_assessment) {

            $assessment_api = &$this; // Defined here for the closures to access this object.

            if (isset($processed_post["assessment_cue"]) && $processed_post["assessment_cue"]) {
                $this->addHook(
                    "after-submit",
                    function () use ($assessment_api, $processed_post, $progress_id) {
                        $assessment_api->createAssessmentOptions(
                            "individual_json_options", array(
                                "assessment_cue" => array(
                                    "aprogress_id" => $progress_id,
                                    "cue" => $processed_post["assessment_cue"]
                                )
                            )
                        );
                    }
                );
            }

            // Enable the assessment method hook (predefined) to be executed after submission.
            $assessment_method_hook = Entrada_Utilities::arrayValueOrDefault($hook_options, "assessment_method", array());
            if (!empty($assessment_method_hook)) {
                if (Entrada_Utilities::arrayValueOrDefault($hook_options["assessment_method"], "enabled",false)) {
                    $this->addHook(
                        "after-submit",
                        "hookAssessmentMethod"
                    );
                }
            }

            // Add hook to create the completion statistic (adds assessment_statistic.`action` = "submit" ), executed inside of the assessment method hook
            $completion_statistic_hook = Entrada_Utilities::arrayValueOrDefault($hook_options, "completion_statistic", array());
            if (!empty($completion_statistic_hook)) {
                if (Entrada_Utilities::arrayValueOrDefault($hook_options["completion_statistic"], "enabled",false)) {
                    $statistic_module = Entrada_Utilities::arrayValueOrDefault($hook_options["completion_statistic"], "module", "assessments");
                    $statistic_submodule = Entrada_Utilities::arrayValueOrDefault($hook_options["completion_statistic"], "submodule", "assessment");
                    $this->addHook(
                        "assessment-method-statistic-completion",
                        "hookSaveCompletionStatistic",
                        false,
                        $statistic_module,
                        $statistic_submodule
                    );
                }
            }

            // Add a hook to save the completion time for this assessment (updates cbl_assessment_progress.`progress_time`)
            $progress_time_statistic_hook = Entrada_Utilities::arrayValueOrDefault($hook_options, "progress_time_statistic", array());
            if (!empty($progress_time_statistic_hook)) {
                if (Entrada_Utilities::arrayValueOrDefault($hook_options["progress_time_statistic"], "enabled",false)) {
                    $this->addHook(
                        "assessment-method-statistic-progress-time",
                        "hookUpdateProgressCompletionTime",
                        false
                    );
                }
            }

            // Add a hook that will notify users that assessments are pending, after initial completion (assessment method based hook)
            $notify_pending_hook = Entrada_Utilities::arrayValueOrDefault($hook_options, "notify_pending", array());
            if (!empty($notify_pending_hook)) {
                if (Entrada_Utilities::arrayValueOrDefault($hook_options["notify_pending"], "enabled",false)) {
                    $this->addHook(
                        "assessment-method-assessment-notify",
                        "hookSendAssessmentNotifications",
                        false
                    );
                }
            }

            // Check if one more more severity levels have been set in settings table that trigger notifications to program coordinators and/or directors.
            // Notifications are sent out when the assessment method hook is executed.
            $notify_flag_severity_levels_hook = Entrada_Utilities::arrayValueOrDefault($hook_options, "notify_flag_severity_levels", array());
            if (!empty($notify_flag_severity_levels_hook)) {
                if (Entrada_Utilities::arrayValueOrDefault($hook_options["notify_flag_severity_levels"], "enabled",false)) {
                    $notify_directors = Entrada_Utilities::arrayValueOrDefault($hook_options["notify_flag_severity_levels"], "notify_directors", false);
                    $form_objectives = Entrada_Utilities::arrayValueOrDefault($hook_options["notify_flag_severity_levels"], "form_objectives", array());
                    $flag_severity_levels = array();
                    if ($notify_directors) {
                        $flag_severity_levels["flag_severity_notify_directors_and_coordinators"] = $notify_directors;
                    }
                    if (!$update_feedback_only && !empty($flag_severity_levels)) {
                        $this->addHook(
                            "assessment-method-flagging-notify", // on assessment method hook execution, execute this one as well
                            "hookSendFlaggingNotifications",
                            false, // we ignore the return value of this, since we shouldn't fail if a notification doesn't go out.
                            $flag_severity_levels,
                            $form_objectives
                        );
                    }
                }
            }

            // When PIN is required input, use the after-load hook and define a callback to validate the PIN
            $pin_validation_hook = Entrada_Utilities::arrayValueOrDefault($hook_options, "pin_validation", array());
            if (!empty($pin_validation_hook)) {
                if (Entrada_Utilities::arrayValueOrDefault($hook_options["pin_validation"], "enabled",false)) {
                    $pin_is_required = false;

                    // Determine if a PIN is required in order to submit this assessment.
                    if (isset($assessment_data["assessment_method"]["shortname"])) {
                        if ($assessment_data["assessment_method"]["shortname"] == "complete_and_confirm_by_pin") {
                            if (!$this->assessment->isCompleted()) {
                                $pin_is_required = true;
                                $pin_assessor_id = isset($assessment_data["assessment"]["assessment_method_data"]["assessor_value"])
                                    ? $assessment_data["assessment"]["assessment_method_data"]["assessor_value"]
                                    : null;
                                $pin_assessor_type = isset($assessment_data["assessment"]["assessment_method_data"]["assessor_type"])
                                    ? $assessment_data["assessment"]["assessment_method_data"]["assessor_type"]
                                    : null;
                                if (!$pin_assessor_id || !$pin_assessor_type) {
                                    $this->addErrorMessage($translate->_("A PIN is required to submit this assessment, but we couldn't find the user to authenticate the PIN."));
                                } else {
                                    if ($pin_assessor_id == $assessment_data["assessor"]["assessor_id"]
                                        && $pin_assessor_type == $assessment_data["assessor"]["type"]
                                    ) {
                                        // The assessor is the one required to enter a PIN, so we don't need the PIN!
                                        $pin_is_required = false;
                                    }
                                }
                            }
                        }
                    }
                    if ($pin_is_required) {
                        // A PIN is required for this assessment, so define the hook to perform the one-time validation.
                        $this->addHook(
                            "after-load",
                            function () use ($assessment_api, $assessment_data, $processed_post) {
                                global $translate;
                                if (empty($processed_post["dassessment_id"])) {
                                    $assessment_api->addErrorMessage($translate->_("Invalid Assessment ID"));
                                    return false;
                                }
                                if (empty($processed_post["aprogress_id"])) {
                                    $assessment_api->addErrorMessage($translate->_("Invalid Progress ID"));
                                    return false;
                                }
                                $pin_nonce = isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["assessment_nonce"][$processed_post["aprogress_id"]])
                                    ? $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["assessment_nonce"][$processed_post["aprogress_id"]]
                                    : null;
                                unset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["assessment_nonce"][$processed_post["aprogress_id"]]);

                                $pin_assessor_id = isset($assessment_data["assessment"]["assessment_method_data"]["assessor_value"])
                                    ? $assessment_data["assessment"]["assessment_method_data"]["assessor_value"]
                                    : null;

                                // Even if values are null for pin nonce and assessor, we attempt to validate the nonce; let the API throw the error.
                                return $assessment_api->validateAssessorPinNonce($processed_post["dassessment_id"], $processed_post["aprogress_id"], $pin_nonce, $pin_assessor_id);
                            }
                        );
                    }
                }
            }

            // If feedback is required, handle it via after-load hook.
            // The after load hook happens immediately following loadData, and any errors reported by this hook will be aggregated with the loadData errors (if any)
            $feedback_hook = Entrada_Utilities::arrayValueOrDefault($hook_options, "feedback", array());
            if (!empty($feedback_hook)) {
                if (Entrada_Utilities::arrayValueOrDefault($hook_options["feedback"], "enabled",false)) {
                    $feedback_options = $this->assessment->getFeedbackOptions($target_record_id, $target_type);
                    if (!empty($feedback_options)
                        && $feedback_options["feedback_pending"]
                    ) {
                        $this->addHook(
                            "after-load",
                            function () use ($assessment_api, $assessment_data, $current_target, $processed_post, $user_is_admin) {
                                global $translate;
                                if (!$processed_post["assessor_feedback_response"]) {
                                    $assessment_api->addErrorMessage($translate->_("Please add a response for the <strong>Assessment Feedback</strong> question."));
                                    return false;
                                }
                                $assessor_feedback_saved = $assessment_api->updateAssessorFeedback(
                                    $assessment_data["assessor"]["assessor_id"],
                                    $assessment_data["assessor"]["type"],
                                    $current_target["target_record_id"],
                                    $current_target["target_scope"],
                                    $processed_post["assessor_feedback_response"] == "yes" ? 1 : 0,
                                    $user_is_admin
                                );
                                return $assessor_feedback_saved;
                            }
                        );
                    }
                }
            }
        }

        /**
         * Save the assessment progress, marking it as complete if we're "finishing_assessment"
         */
        if ($update_feedback_only) {
            /**
             * Only update feedback
             **/
            if ($feedback_required && !$processed_post["target_feedback_response"]) {
                add_error($translate->_("Please add a response for the <strong>Assessment Feedback</strong> question."));
            } else {
                $this->updateTargetFeedback(
                    $assessment_data["assessor"]["assessor_id"],
                    $assessment_data["assessor"]["type"],
                    $current_target["target_record_id"],
                    $current_target["target_scope"],
                    $processed_post["target_feedback_response"] == "yes" ? 1 : 0,
                    $processed_post["feedback_meeting_comments"],
                    $finish_assessment
                );
                $saved = true;
            }
        } else {
            /**
             * Save the assessment using the posted data, marking it as complete (creating a new progress record if needed).
             **/
            $create_new_progress = false;
            if (empty($current_progress) && !$processed_post["aprogress_id"]) {
                // Attempt to create a new progress record
                $create_new_progress = true;
            }
            $saved = $this->saveAssessmentByPost($posted_data, $finish_assessment, $create_new_progress);
        }

        /**
         * Successfully saved assessment progress. Handle related notifications.
         */
        if ($saved) {
            // If the task requires approval, we must notify each approver of the task submission.
            if (!$update_feedback_only
                && $approval_required
                && $finish_assessment
            ) {
                if (!empty($assessment_data["distribution"])
                    && !empty($assessment_data["distribution"]["distribution_approvers"])
                ) {
                    foreach ($assessment_data["distribution"]["distribution_approvers"] as $approver_record) {
                        $processed_post["notify_proxy_id"] = $approver_record["proxy_id"];
                        $this->queueCompletedNotification(
                            $processed_post["dassessment_id"],
                            $assessment_data["assessment"]["adistribution_id"],
                            $processed_post["notify_proxy_id"],
                            "assessment_submitted_notify_approver",
                            $processed_post["aprogress_id"]
                        );
                    }
                }
            } else {
                // At this point, we are not an approver, we are submitting an assessment. If it's not just feedback, we then notify the target
                if (!$update_feedback_only
                    && $actor_can_complete_assessment
                    && $finish_assessment
                    && $current_target["target_type"] == "proxy_id"
                ) {
                    // The current viewer (the assessor) has saved the assessment, setting progress = complete.
                    // If feedback is enabled, and the target was a person (which it should be, if feedback is enabled), we notify them.
                    if ($feedback_required && !$feedback_pending) {
                        // Only in cases where feedback is required (i.e., feedback_required = 1 on the assessment record)
                        $this->queueCompletedNotification(
                            $processed_post["dassessment_id"],
                            $assessment_data["assessment"]["adistribution_id"],
                            $current_target["target_record_id"],
                            "assessment_submitted_notify_learner",
                            $processed_post["aprogress_id"]
                        );
                    }
                }
            }
            // We've saved the progress, so send out flagging notifications if required.
            if ($finish_assessment && $send_flagging_notifications) {
                $flagged_responses = $this->getFlaggedResponsesSelected();
                if (!$update_feedback_only && !empty($flagged_responses)) {
                    Models_Assessments_Notification::sendFlaggingNotification(
                        $processed_post["target_record_id"],
                        $processed_post["aprogress_id"],
                        $assessment_data["assessor"]["assessor_id"],
                        $assessment_data["assessment"]["adistribution_id"]
                    );
                }
            }
            return true;

        }  else {

            // Failed to save
            return false;
        }
    }

    /**
     * For the given $_POST data, handle the posted elements as an assessment approval.
     *
     * @param array $posted_data
     * @param bool $notify
     * @param bool|int $specified_assessment_id
     * @param bool|int $specified_progress_id
     * @return bool
     */
    public function handlePostedApprovalSubmission($posted_data, $notify = true, $specified_assessment_id = false, $specified_progress_id = false) {
        global $translate;
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        $assessment_data = $this->assessment->fetchData();
        if (empty($assessment_data)) {
            $this->addErrorMessage($translate->_("Unable to retrieve assessment data."));
            return false;
        }
        $approval_data = $this->assessment->getApprovalDataForProgress();
        if (empty($approval_data)) {
            $this->addErrorMessage($translate->_("Unable to retrieve required approval data."));
            return false;
        }
        $current_target = $this->assessment->getCurrentTarget();
        if (empty($current_target)) {
            $this->addErrorMessage($translate->_("Unable to find approval target."));
            return false;
        }
        $processed_post["approval_status"] = null;
        if (array_key_exists("approval_status", $posted_data)) {
            $processed_post["approval_status"] = clean_input($posted_data["approval_status"], array("trim", "notags"));
        }
        $processed_post["hide_assessment_comments"] = null; // "hide_assessment_comments" = The comments as to why the reviewer is choosing to hide the assessment.
        if (array_key_exists("hide_assessment_comments", $posted_data)) {
            $processed_post["hide_assessment_comments"] = clean_input($posted_data["hide_assessment_comments"], array("trim", "notags"));
        }
        $actor_is_approver = ($this->actor_proxy_id == $approval_data["approver_proxy_id"] && $this->actor_type == "proxy_id");
        $saved_approval = false;
        if ($actor_is_approver
            && $approval_data["approval_status"] == "pending"
        ) {
            // Approval hasn't been submitted yet, submit it
            if (!$saved_approval = $this->assessment->updateApproval($processed_post["approval_status"], $processed_post["hide_assessment_comments"])) {
                $this->addErrorMessages($this->assessment->getErrorMessages());
                return false;
            }
        }
        if ($saved_approval) {
            // Approval saved; if there's a learner to notify, notify them if the assessment is completed on them and available to view.
            if ($current_target["target_type"] == "proxy_id"
                && $processed_post["approval_status"] == "approved"
                && $notify
            ) {
                $this->queueCompletedNotification(
                    $assessment_data["assessment"]["dassessment_id"],
                    $assessment_data["assessment"]["adistribution_id"],
                    $current_target["target_record_id"],
                    "assessment_submitted_notify_learner",
                    $this->aprogress_id
                );
            }
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Add an assessment statistic.
     *
     * @param string $module_name
     * @param string $submodule_name
     * @param int $proxy_id
     * @param int $dassessment_id
     * @param int $aprogress_id
     * @param int $target_record_id
     * @param string $statistic_type
     * @param int|null $adistribution_id
     * @param int|null $created_date
     * @param string|null $prune_after
     * @return bool
     */
    public function addAssessmentStatistic($module_name, $submodule_name, $proxy_id, $dassessment_id, $aprogress_id, $target_record_id, $statistic_type = "view", $adistribution_id = null, $created_date = null, $prune_after = null) {
        global $translate;

        // Insert a new statistic record.
        $assessment_statistic = new Models_Assessment_Statistic(array(
            "proxy_id" => $proxy_id,
            "created_date" => $created_date ? $created_date : time(),
            "module" => $module_name,
            "sub_module" => $submodule_name,
            "action" => $statistic_type,
            "assessment_id" => $dassessment_id,
            "progress_id" => $aprogress_id,
            "distribution_id" => $adistribution_id,
            "target_id" => $target_record_id,
            "prune_after" => $prune_after ? $prune_after : strtotime("+1 years")
        ));
        if (!$assessment_statistic->insert()) {
            $this->addErrorMessage($translate->_("Error encountered while attempting to save assessment statistic."));
            application_log("error", "Error encountered while attempting to save history of an assessment statistic.");
            return false;
        }
        return true;
    }

    //-- Assessment rendering functionality --//

    /**
     * Using the supplied configuration data, make a best effort to configure this object in order to be able to render it.
     * This method also updates the given configuration, adding the various bits of information that may have been omitted.
     * In particular, if target_record_id/type were not specified, they will be added to the configuration data.
     *
     * The configuration data is typically the $PROCESSED array, which is built via the various web contexts.
     *
     * @param array $configuration
     * @param bool $specified_assessment_id
     * @param bool $specified_progress_id
     * @return bool
     */
    public function configureForRender(&$configuration = array(), $specified_assessment_id = false, $specified_progress_id = false) {
        global $translate;
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        $assessment_data = $this->assessment->fetchData();
        if (empty($assessment_data)) {
            $this->addErrorMessage($translate->_("Unable to retrieve assessment data."));
            return false;
        }

        /**
         * Ensure we have our defaults (all null if not found).
         */

        $target_record_id = Entrada_Utilities::arrayValueOrDefault($configuration, "target_record_id");
        $target_type = Entrada_Utilities::arrayValueOrDefault($configuration, "target_type");
        $atarget_id = Entrada_Utilities::arrayValueOrDefault($configuration, "atarget_id");
        $aprogress_id = Entrada_Utilities::arrayValueOrDefault($configuration, "aprogress_id");

        /**
         * Find a target to use, whether specified or not (we always need a target_record_id and target_type to continue.
         */

        // Ensure that we have a target_record_id and target_type (we're not checking if they are valid yet, though)
        if (!$target_record_id && !$atarget_id) {
            if ($aprogress_id) {
                foreach ($assessment_data["progress"] as $progress_data) {
                    if ($progress_data["aprogress_id"] == $aprogress_id) {
                        foreach ($assessment_data["targets"] as $target) {
                            if ($target["target_type"] == $progress_data["target_type"]
                                && $target["target_value"] == $progress_data["target_record_id"]
                            ) {
                                $target_record_id = $target["target_value"];
                                $target_type = $target["target_type"];
                            }
                        }
                    }
                }
            } else {
                // No ID specified, so find the first one
                $first_assessment_target = $this->getFirstAssessmentTarget(); // returns target type and value
                if (!$first_assessment_target) {
                    return false;
                }
                $target_record_id = $first_assessment_target["target_value"];
                $target_type = $first_assessment_target["target_type"];
            }

        } else if ($atarget_id) {

            $found = false;
            // A specific target record was supplied
            foreach ($assessment_data["targets"] as $target) {
                if ($target["atarget_id"] == $atarget_id) {
                    $found = true;
                    $target_record_id = $target["target_value"];
                    $target_type = $target["target_type"];
                }
            }
            if (!$found) {
                $this->addErrorMessage($translate->_("Specified target does not exist or was not found."));
                return false;
            }

        } else if ($target_record_id && !$target_type) {
            // Insufficient data
            $this->addErrorMessage($translate->_("Unable to fetch assessment target (missing parameters)."));
            return false;
        }

        /**
         * Get the current valid target from the assessment dataset.
         */

        // Using our target record and type, find it in the dataset
        $current_target = $this->assessment->getCurrentTarget($target_record_id, $target_type);
        if (empty($current_target)) {
            $this->addErrorMessage($translate->_("Invalid target specified.")); // record id/type combo was not found
            return false;
        }

        /**
         * Determine what progress ID to use, if applicable.
         * Validate the progress ID, if specified.
         */

        // Find a progress ID to use, if none specified.
        if (!$aprogress_id) {
            if ($current_target["deleted_date"]) {
                // If the current target is deleted, and we have no specific aprogress_id given to us, we find the last completed progress to show.
                $first_progress_id = $this->getLatestProgressID($target_record_id, $target_type, "complete");
                if ($first_progress_id) {
                    $aprogress_id = $first_progress_id; // null is a valid value
                }
            } else {
                // Otherwise, we find the latest progress, be it completed or inprogress.
                $first_progress_id = $this->getLatestProgressID($target_record_id, $target_type);
                if ($first_progress_id) {
                    $aprogress_id = $first_progress_id; // null is a valid value
                }
            }
        }
        // If we found a progress ID, then reset this object to use it
        if ($aprogress_id) {
            // Verify that the given aprogress_id matches the target and that the given progress ID actually exists
            $found = false;
            foreach ($assessment_data["progress"] as $progress_data) {
                if ($progress_data["aprogress_id"] == $aprogress_id) {
                    if ($progress_data["target_type"] == $target_type
                        && $progress_data["target_record_id"] == $target_record_id
                    ) {
                        $found = true;
                    }
                }
            }
            if ($found) {
                // Progress ID is valid for the target.
                $this->setAprogressID($aprogress_id);
                $refetch = $this->assessment->fetchData();
                if (empty($refetch)) {
                    $this->addErrorMessages($this->assessment->getErrorMessages());
                    return false;
                }

            } else {
                // Note that if an atarget_id or target id/type weren't specified, this logic tries to match against the first target.
                // If there are multiple targets, the type/id combo won't match, and despite the aprogress ID being valid for the assessment, it may not be valid for
                // the given target (the first one).
                $this->addErrorMessage($translate->_("Specified progress ID is invalid."));
                return false;
            }
        }

        // The object has been configured. We can now pull current target and current progress. We can also update the supplied configuration array with the missing data.
        $configuration["target_record_id"] = $target_record_id;
        $configuration["target_type"] = $target_type;
        $configuration["atarget_id"] = $atarget_id;
        $configuration["aprogress_id"] = $aprogress_id;
        return true;
    }

    /**
     * A wrapper for rendering an assessment in one method call. This method will render a
     * functionally complete assessment; all CSS and Javascript included in the render.
     *
     * In order to render an assessment, the target id/type must be known. The API can be leveraged to fetch that information outside of this context,
     * but this particular render method can't render without it. If target_record_id and target_type are false, getCurrentTarget will only know what target
     * to render for the assessment if a progress ID is set. Otherwise, target_record_id and target_type must be specified.
     *
     * @param array $options
     * @param bool $user_is_admin
     * @param bool $echo
     * @param bool|int $target_record_id
     * @param bool|string $target_type
     * @param bool|int $specified_assessment_id
     * @param bool|int $specified_progress_id
     * @return bool|string
     */
    public function renderAssessment($options = array(), $user_is_admin = false, $echo = true, $target_record_id = false, $target_type = false, $specified_assessment_id = false, $specified_progress_id = false) {
        global $translate;
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id,
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        $assessment_data = $this->assessment->fetchData();
        if (empty($assessment_data)) {
            $this->addErrorMessages($this->assessment->getErrorMessages());
            return false;
        }
        if (!empty($assessment_data["form_dataset"])) {
            $form_data = $assessment_data["form_dataset"];
        } else {
            $form_data = Entrada_Utilities::arrayValueOrDefault($options, "form_dataset", array());
        }
        if (empty($form_data)) {
            $this->addErrorMessage($translate->_("Form data not specified."));
            return false;
        }
        $current_target = $this->assessment->getCurrentTarget($target_record_id, $target_type);
        if (empty($current_target)) {
            $this->addErrorMessage($translate->_("Unable to find current target."));
            return false;
        }
        $current_progress = $this->assessment->getCurrentProgress(); // OK to be empty

        /**
         * This should be the global AGENT_CONTACTS array.
         */
        $agent_contacts = Entrada_Utilities::arrayValueOrDefault($options, "agent_contacts", array());

        /**
         * Force the visibility of an assessment from a condition outside of this context (typically, an ACL check).
         * If this is false, then the default rules for visibility will be applied.
         */
        $assessment_visibility_override_primary = Entrada_Utilities::arrayValueOrDefault($options, "assessment_visibility_override_primary", false);
        $assessment_visibility_override_secondary = Entrada_Utilities::arrayValueOrDefault($options, "assessment_visibility_override_secondary", false);

        /**
         * When rendering any form, first perform a consistency check on the rubrics contained therein.
         */
        $perform_rubric_consistency_check = Entrada_Utilities::arrayValueOrDefault($options, "perform_rubric_consistency_check", true);


        /**
         * Makes this render function ignore the view and simply return the view_options. This takes precedence over other render options.
         * Does not obey the echo parameter (simply returns the view options).
         */
        $return_view_options_only = Entrada_Utilities::arrayValueOrDefault($options, "return_view_options_only", false);

        /**
         * You can specify to render the HTML in all cases with this override; however, the HTML will usually be an error message (if applicable).
         */
        $render_html_override = Entrada_Utilities::arrayValueOrDefault($options, "render_html", false);

        /**
         * You can specify to render the assessment data as JSON; this will json_encode the view_options array (which contains the assessment_data dataset).
         * This obeys the echo parameter (optionally displays the encoded json if specified, otherwise just returns it).
         */
        $render_as_json = Entrada_Utilities::arrayValueOrDefault($options, "render_as_json", false);

        /**
         * Build our data options to pass to the view. These are the defaults. They are overridden as necessary.
         */
        $data_options = array();
        $data_options["render_form"] = true;                      // Flag for rendering the form or not.
        $data_options["finish_assessment"] = false;               // For setting the relevant progress record to "complete"
        $data_options["update_feedback_only"] = false;            // True when updating feedback only
        $data_options["assessment_target_list"] = array();        // The list of targets for this assessment, with completion meta data.  Derived from the pre-fetched assessment dataset.
        $data_options["render_submission_buttons"] = false;       // When the page is incomplete and/or submittable
        $data_options["assessment_completed"] = false;            // If the assessment progress record is complete
        $data_options["disabled"] = false;                        // Render the form as disabled?
        $data_options["submit_on_behalf"] = false;                // True when someone other than the assessor indicated is submitting the form
        $data_options["cannot_complete"] = false;                 // True when the form cannot be submitted by the current viewer
        $data_options["actor_is_approver"] = false;               // True when the current viewer/proxy is the approver
        $data_options["actor_is_assessor"] = false;               // True when the current viewer is the assessor indicated by the assessment
        $data_options["is_distribution_deleted"] = false;         // True when the distribution that this assessment is associated with is deleted
        $data_options["show_forms_to_complete_message"] = false;  // Optional: the number of remaining forms to fill out message.
        $data_options["is_assessment_deleted"] = false;           // Has the assessment (target or overall) been deleted?
        $data_options["is_form_deleted"] = false;                 // Has the form been deleted?
        $data_options["can_forward"] = false;                     // Can the viewer forward this task to another assessor?
        $data_options["can_manage"] = false;                      // Can this viewer manage the tasks (reopen completed and clear attempt progress)?
        $data_options["can_delete"] = false;                      // Can this viewer delete this task?
        $data_options["can_download"] = true;                     // Can this viewer download this PDF?
        $data_options["submit_button_text"] = null;               // Optional assessment-specific submit button text
        $data_options["pin_is_required"] = false;                 // A pin is required to submit this assessment
        $data_options["pin_assessor_id"] = null;                  // The ID of the assessor that must enter their pin
        $data_options["pin_access_token"] = null;                 // A CSRF token to prevent multiple submissions.
        $data_options["approval_data"] = array();                 // An array describing the state of the approval (pending/hidden/approved). Derived from the pre-fetched assessment dataset.
        $data_options["approval_required"] = false;               // True when the distribution associated with this assessment requires an approval
        $data_options["approval_pending"] = false;                // True when the approval has not been completed
        $data_options["approval_time"] = 0;                       // The time that the assessment was reviewed/approved
        $data_options["approver_fullname"] = "";                  // The name of the user that reviewed (approved or hid) the assessment (if applciable)

        /**
         * Set assessment dependent options.
         */
        $data_options["perform_rubric_consistency_check"] = $perform_rubric_consistency_check;
        $data_options["feedback_data"] = $this->assessment->getFeedbackForTarget($target_record_id, $target_type);
        $data_options["feedback_required"] = $this->assessment->isFeedbackRequired();
        $data_options["feedback_pending"] = $data_options["feedback_required"]; // default to whether it is required or not.
        $data_options["assessment_expired"] = $this->assessment->isExpired();
        $data_options["approval_data"] = $this->assessment->getApprovalDataForProgress();
        $data_options["assessment_completed"] = $this->assessment->isCompleted();
        $data_options["assessment_target_list"] = $this->assessment->getAssessmentTargetList(); // Get a usable target list, with some supplemental data
        $data_options["actor_can_view_assessment"] = $this->assessment->canActorView($assessment_visibility_override_primary, $assessment_visibility_override_secondary, $target_type, $target_record_id);
        $data_options["actor_can_complete_assessment"] = $this->assessment->canActorComplete($user_is_admin, $target_type, $target_record_id);
        $data_options["objectives"] = $this->assessment->getSelectedObjectiveHierarchy(); // Fetch the selected objective (for the objective selector, or field note related functionality)
        // Fill in the missing data; ensures data defaults and conforms to expected types.
        if ($data_options["objectives"] === false) {
            $data_options["objectives"] = array();
        }
        if (empty($data_options["approval_data"])) {
            $data_options["approval_status"] = false;
        } else {
            $data_options["approval_status"] = $data_options["approval_data"]["approval_status"];
        }

        /**
         * Determine what the actor and/or assessor can do.
         */
        if ($assessment_data["assessor"]["assessor_id"] == $this->actor_proxy_id
            && $assessment_data["assessor"]["type"] == "internal"
        ) {
            $data_options["actor_is_assessor"] = true;
        }
        if (!$data_options["assessment_completed"]
            && $data_options["actor_can_complete_assessment"]
        ) {
            $data_options["render_submission_buttons"] = true;
        }

        /**
         * Check if this assessment is still valid (deleted/expired)
         */
        // Check if the assessment has expired
        if (!$data_options["assessment_completed"]
            && $data_options["assessment_expired"]
        ) {
            $expiry_date = date("Y-m-d h:i A", $assessment_data["assessment"]["expiry_date"]);
            $this->addErrorMessage(sprintf($translate->_("This task expired on %s."), $expiry_date));
            $data_options["render_form"] = false;
        }
        // Check if the form is deleted and has no progress.
        if ($form_data["form"]["deleted_date"]) {
            $add_form_error = false;
            if (empty($current_progress)) {
                $add_form_error = true;
            } else {
                if ($current_progress["progress_value"] != "complete") {
                    // We only notify and restrict if the assessment was in progress or pending, but not completed.
                    $add_form_error = true;
                }
            }
            if ($add_form_error) {
                $this->addErrorMessage($translate->_("This assessment cannot be completed since the form has been deleted."));
                $data_options["render_form"] = false;
            }
        }
        $data_options["has_distribution"] = empty($assessment_data["distribution"]["distribution_record"]) ? false : true;
        $data_options["is_distribution_deleted"] = isset($assessment_data["distribution"]["distribution_record"]["deleted_date"])
            ? $assessment_data["distribution"]["distribution_record"]["deleted_date"]
                ? true
                : false
            : false;
        // Check if the distribution has been deleted
        if ($data_options["has_distribution"] && $data_options["is_distribution_deleted"]) {
            $data_options["disabled"] = true;
            $data_options["render_submission_buttons"] = false;
        }

        /**
         * Set assessment visibility.
         */
        // Set approval statuses when approvals are required.
        if (!empty($data_options["approval_data"])) {
            $data_options["approval_required"] = true;
            $data_options["approval_pending"] = $data_options["approval_data"]["approval_status"] == "pending" ? true : false;
            $data_options["approval_time"] = $data_options["approval_data"]["approval_time"];
            $data_options["approver_fullname"] = $data_options["approval_data"]["approver_fullname"];
            $data_options["actor_is_approver"] = ($this->actor_proxy_id == $data_options["approval_data"]["approver_proxy_id"] && $this->actor_type == "proxy_id");
        }
        // Check if the user can view this assessment at all
        if (!$data_options["actor_can_view_assessment"]) {
            // Can't view -- why not?
            if ($data_options["approval_status"] == "pending"
                && $data_options["approval_required"]
            ) {
                if (!$data_options["assessment_completed"]
                    && $data_options["actor_is_approver"]
                ) {
                    $this->addErrorMessage($translate->_("You cannot review this task until it has been completed."));
                    $data_options["render_form"] = false;
                } else {
                    // Assessment is not approved, but approval is required (and the viewer is not the approver)
                    $this->addErrorMessage($translate->_("This assessment is pending review. It will be accessible if it is approved."));
                    $data_options["render_form"] = false;
                }
            } else {
                // ACL failure
                $this->addErrorMessage($translate->_("You do not have permission to view this assessment."));
                if (!empty($agent_contacts)) {
                    $this->addErrorMessage(sprintf($translate->_("If you believe you are receiving this message in error please contact <a class=\"user-email\" href=\"mailto:%s\">%s</a> for assistance."), html_encode($agent_contacts["administrator"]["email"]), html_encode($agent_contacts["administrator"]["name"])));
                } else {
                    $this->addErrorMessage(sprintf($translate->_("If you believe you are receiving this message in error please contact the system administrator for assistance.")));
                }
                $data_options["render_form"] = false;
            }
        }
        // Check if this user is able to fill out this assessment
        if ($data_options["actor_can_complete_assessment"]) {
            // For an internal assessor, we compare the IDs.
            // IF the user is able to access this assessment, but isn't the explicit defined as the assessor, we submit on behalf of the assessor.
            if ($this->actor_proxy_id != $assessment_data["assessor"]["assessor_id"]
                && $this->actor_type == "proxy_id"
            ) {
                $data_options["submit_on_behalf"] = true;
            }
        } else {
            $data_options["disabled"] = true;
            $data_options["cannot_complete"] = true;
        }

        /**
         * Check if the assessment or the target have been deleted.
         * Still render the form if progress exists and is completed.
         */
        $current_target_has_completed_progress = false;
        $current_target_has_cancelled_progress = false;
        if (!empty($current_progress)) {
            if ($current_progress["progress_value"] == "complete") {
                $current_target_has_completed_progress = true;
            } else if ($current_progress["progress_value"] == "cancelled"
                || $current_progress["progress_value"] == null
            ) {
                $current_target_has_cancelled_progress = true;
            }
        }
        if ($current_target["deleted_date"]
            && !$current_target_has_completed_progress
        ) {
            // Check if this task was forwarded
            if ($this->isAssessmentForwarded()) {
                $this->addErrorMessage($translate->_("This task was forwarded to a new assessor."));
            } else {
                $this->addErrorMessage($translate->_("This assessment target has been deleted."));
            }
            $data_options["is_assessment_deleted"] = true;
            $data_options["render_form"] = false;

        } else if ($assessment_data["assessment"]["deleted_date"]
            && !$current_target_has_completed_progress
        ) {
            $this->addErrorMessage($translate->_("This assessment has been deleted."));
            $data_options["is_assessment_deleted"] = true;
            $data_options["render_form"] = false;

        } else if ($current_target_has_cancelled_progress) {
            $this->addErrorMessage($translate->_("Responses for this assessment have been deleted."));
            $data_options["is_assessment_deleted"] = true;
            $data_options["render_form"] = false;
        }

        /**
         * Set additional assessment controls (delete task/progress, forward)
         */
        // If the task is complete and the user is a course owner, they are a PA for the task and should be able to reopen/clear it.
        if (!empty($assessment_data["distribution"])) {
            foreach ($assessment_data["distribution"]["course_owners"] as $course_owner) {
                if ($course_owner["proxy_id"] == $this->actor_proxy_id
                    && $this->actor_type == "proxy_id"
                ) {
                    $data_options["can_manage"] = true;
                }
            }
        }

        // However if the user is an administrator, they can do whatever they please.
        if ($user_is_admin) {
            $data_options["can_manage"] = true;
        }
        // Only faculty/staff (and admin) can forward.
        if ($data_options["can_manage"]
            || ($data_options["actor_is_assessor"]
                && ($this->actor_group == "faculty"
                    || $this->actor_group == "staff"
                )
            )
        ) {
            $data_options["can_forward"] = true;
        }
        // Only the assessor or PA can delete a task.
        if ($data_options["actor_is_assessor"] || $data_options["can_manage"]) {
            $data_options["can_delete"] = true;
        }

        /**
         * Determine if a PIN is required in order to submit this assessment.
         */
        if (isset($assessment_data["assessment_method"]["shortname"])) {
            if ($assessment_data["assessment_method"]["shortname"] == "complete_and_confirm_by_pin") {
                if (!$data_options["assessment_completed"]) {
                    $data_options["pin_is_required"] = true;
                    $data_options["pin_assessor_id"] = isset($assessment_data["assessment"]["assessment_method_data"]["assessor_value"])
                        ? $assessment_data["assessment"]["assessment_method_data"]["assessor_value"]
                        : null;
                    $data_options["pin_assessor_type"] = isset($assessment_data["assessment"]["assessment_method_data"]["assessor_type"])
                        ? $assessment_data["assessment"]["assessment_method_data"]["assessor_type"]
                        : null;
                    if (!$data_options["pin_assessor_id"] || !$data_options["pin_assessor_type"]) {
                        $this->addErrorMessage($translate->_("A PIN is required to submit this assessment, but we couldn't find the user to authenticate the PIN."));
                    } else {
                        if ($data_options["pin_assessor_id"] == $assessment_data["assessor"]["assessor_id"]
                            && $data_options["pin_assessor_type"] == $assessment_data["assessor"]["type"]
                        ) {
                            // The assessor is the one required to enter a PIN, so we don't need the PIN!
                            $data_options["pin_is_required"] = false;
                        }
                    }
                }
            }
        }

        /**
         * Only render the page if the associated rubrics have passed a consistency check.
         */
        if ($data_options["perform_rubric_consistency_check"]) {
            $forms_api = new Entrada_Assessments_Forms($this->buildActorArray(array("form_id" => $form_data["form"]["form_id"])));
            $consistency_results = $forms_api->formRubricsConsistencyCheck();
            if (!$consistency_results) {
                // Rubric consistency check failed
                $this->addErrorMessage(
                    sprintf(
                        $translate->_("An error was encountered when attempting to display this form.<br /><br />Please contact <a class=\"user-email\" href=\"mailto:%s\">%s</a> for assistance."),
                        html_encode($agent_contacts["administrator"]["email"]),
                        html_encode($agent_contacts["administrator"]["name"])
                    )
                );
                application_log("error", "Assessment executed rubric consistency check AND FAILED! Assessment id: '{$assessment_id}'.");
                $data_options["render_form"] = false;
            }
        }

        /**
         * If, at this point, $data_options["render_form"] is false, we can exit and let the caller deal with the populated errors.
         * The exception is if we're forcing the view to render HTML via the override.
         */
        if (!$render_html_override) {
            if ($data_options["render_form"] === false) {
                return false;
            }
        }

        /**
         * Fetch required data for the task deletion modal.
         */
        if ($deletion_reasons = Models_Assessments_TaskDeletedReason::fetchAllRecordsOrderByOrderID()) {
            $data_options["deletion_reasons"] = $deletion_reasons;
        } else {
            $data_options["deletion_reasons"] = array();
        }

        /**
         * Set the submit button text, if specific button text is required.
         */
        if (isset($assessment_data["assessment_method_meta"]["button_text"])) {
            $data_options["submit_button_text"] = $assessment_data["assessment_method_meta"]["button_text"];
        }

        /**
         * If the assessment is completed, then disable the form.
         */
        if ($data_options["assessment_completed"]) {
            $data_options["disabled"] = true;
        }

        /**
         * Get the options for the feedback widget; the options are passed to the feedback view object via the render_options array.
         */
        $feedback_options = $this->assessment->getFeedbackOptions($target_record_id, $target_type);

        // Add some state options to the feedback options array (which is passed directly to the feedback view) if applicable
        if ($data_options["cannot_complete"]
            && !$data_options["actor_can_complete_assessment"]
            && !$feedback_options["feedback_actor_is_target"]
        ) {
            $feedback_options["edit_state"] = "readonly";
        }
        if ($data_options["is_distribution_deleted"]) {
            $feedback_options["edit_state"] = "readonly";
        }
        // If the feedback view requires submit buttons, enable them
        if ($feedback_options["render_submission_buttons"]) {
            $data_options["render_submission_buttons"] = $feedback_options;
        }

        /**
         * Based on the current set of assessment options, build a list of form-item mutators to pass to the form renderer.
         */
        $form_mutators = $this->assessment->buildFormMutatorListFromAssessmentOptions(true);

        /**
         * Aggregate the given options and pass them to the view.
         */
        $render_options = array(
            "assessment_data" => $assessment_data,
            "dassessment_id" => $this->getDassessmentID(),
            "aprogress_id" => $this->getAprogressID(),
            "form_data" => $form_data,
            "external_hash" => $assessment_data["assessment"]["external_hash"],
            "current_target" => $current_target,
            "current_progress" => $current_progress,
            "feedback_options" => $feedback_options,
            "form_mutators" => $form_mutators
        );
        $actor_options = array(
            // Inherited from this object
            "actor_proxy_id" => $this->actor_proxy_id,
            "actor_organisation_id" => $this->actor_organisation_id,
            "actor_group" => $this->actor_group,
            "actor_type" => $this->actor_type,
            "actor_scope" => $this->actor_scope
        );
        $error_options = array();
        $errors = $this->getErrorMessages();
        if (!empty($errors)) {
            $render_options["assessment_render_mode"] = "error";
            $error_options["assessment_error_messages"] = $errors;
        }
        $view_options = array_merge($render_options, $data_options, $options, $error_options, $actor_options);

        /**
         * Render the assessment (or applicable error message)
         */
        if ($return_view_options_only) {
            // If specified return the view options directly, not encoded, not echoing.
            return $view_options;
        } else if ($render_as_json) {
            // If specified, return the view options (which includes the assessment dataset) as json.
            $encoded = json_encode($view_options);
            if ($echo) {
                echo $encoded;
            }
            return $encoded;
        } else {
            $assessment_view = new Views_Assessments_Assessment();
            if ($echo) {
                // Echo the HTML, return bool
                $assessment_view->render($view_options);
                return true;
            } else {
                // Return the HTML
                return $assessment_view->render($view_options, false);
            }
        }
    }

    /**
     * Render the sidebar entry for the assessor.
     * The sidebar entry only renders if the context calls for it; the assessor should not see themselves in the assessor sidebar. However, the sidebar
     * entry can be forced to render via the show_assessor_override option.
     *
     * @param bool $show_assessor_override
     * @param bool $add_to_sidebar_immediately
     * @param bool|int $specified_assessment_id
     * @param bool|int $specified_progress_id
     * @return bool|string
     */
    public function renderAssessmentSidebarAssessor($show_assessor_override = false, $add_to_sidebar_immediately = true, $specified_assessment_id = false, $specified_progress_id = false) {
        global $translate;
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id,
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        $assessment_data = $this->assessment->fetchData();
        if (empty($assessment_data)) {
            $this->addErrorMessages($this->assessment->getErrorMessages());
            return false;
        }
        $assessor_override_methods = array(
            "complete_and_confirm_by_email",
            "complete_and_confirm_by_pin",
            "send_blank_form",
            "faculty_triggered_assessment"
        );
        if (isset($assessment_data["assessment"]["assessment_method_data"]["assessor_value"])
            && isset($assessment_data["assessment"]["assessment_method_data"]["assessor_type"])
            && !empty($assessment_data["assessment_method"])
            && in_array($assessment_data["assessment_method"]["shortname"], $assessor_override_methods)
        ) {
            $assessor_id = $assessment_data["assessment"]["assessment_method_data"]["assessor_value"];
            $assessor_type = $assessment_data["assessment"]["assessment_method_data"]["assessor_type"];
            $assessor_full_name = $translate->_("Unknown Assessor");
            $assessor_email = "";
            if ($new_assessor = $this->getUserByType($assessor_id, $assessor_type)) {
                $assessor_full_name = "{$new_assessor->getFirstname()} {$new_assessor->getLastname()}";
                $assessor_email = $new_assessor->getEmail();
            }
        } else {
            $assessor_id = $assessment_data["assessor"]["assessor_id"];
            $assessor_type = $assessment_data["assessor"]["type"];
            $assessor_full_name = $assessment_data["assessor"]["full_name"];
            $assessor_email = $assessment_data["assessor"]["email"];
        }
        $actor_is_assessor = ($assessor_id == $this->actor_proxy_id
            && $assessor_type == "internal"
        );

        // Render the assessor sidebar entry in all cases EXCEPT when the assessor_value on the assessment task record matches the current logged in assessor.
        if ($show_assessor_override || !$actor_is_assessor) {
            // Build assessor options for view for sidebar.
            $assessor_sidebar_options = array();
            $assessor_sidebar_options["assessor_full_name"] = $assessor_full_name;
            $assessor_sidebar_options["assessor_email"] = $assessor_email;
            if ($assessor_type == "internal") {
                // Fetch optional data for internal assessors
                if ($assessor_user_object = User::fetchRowByID($assessor_id, $assessment_data["assessment"]["organisation_id"])) {
                    $assessor_sidebar_options["assessor_image_uri"] = Entrada_Utilities::fetchUserDefaultPhotoURL($assessor_user_object->getID(), $assessor_user_object->getPrivacyLevel());
                    $assessor_sidebar_options["assessor_group"] = $translate->_(ucfirst($assessor_user_object->getGroup())); // For different organisations, this will translate the group name to something else if it is specified in the language file.
                    if ($assessor_organisation_object = Models_Organisation::fetchRowByID($assessor_user_object->getOrganisationId())) {
                        $assessor_sidebar_options["assessor_organisation"] = $assessor_organisation_object->getOrganisationTitle();
                    }
                }
            } else if ($assessment_data["assessor"]["type"] == "external") {
                // If, in the future, we store pictures of external assessors, we can supply an assessor_image_uri for the external here.
                $assessor_sidebar_options["assessor_image_uri"] = ENTRADA_URL . "/images/headshot-male.gif";
                $assessor_sidebar_options["assessor_group"] = $translate->_("External Assessor");
            }
            // Render and append
            $sidebar_assessor_view = new Views_Assessments_Sidebar_Assessor();
            $assessor_sidebar_html = $sidebar_assessor_view->render($assessor_sidebar_options, false);
            if ($add_to_sidebar_immediately) {
                assessment_sidebar_item($assessor_sidebar_html, $id = "assessment-assessor-sidebar", $state = "open", $position = SIDEBAR_APPEND);
            }
            return $assessor_sidebar_html;
        }
        return false;
    }

    /**
     * Render the "Attempts" side bar entry for the current assessment target.
     *
     * @param bool $is_admin
     * @param bool $add_to_sidebar_immediately
     * @param bool $target_record_id
     * @param bool $target_type
     * @param bool $specified_assessment_id
     * @param bool $specified_progress_id
     * @return bool|string
     */
    public function renderAssessmentSidebarAttempts($is_admin = false, $add_to_sidebar_immediately = true, $target_record_id = false, $target_type = false, $specified_assessment_id = false, $specified_progress_id = false) {
        global $translate;
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id,
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        $assessment_data = $this->assessment->fetchData();
        if (empty($assessment_data)) {
            $this->addErrorMessages($this->assessment->getErrorMessages());
            return false;
        }
        $approval_data = $this->assessment->getApprovalDataForProgress();
        $actor_is_approver = empty($approval_data)
            ? false
            : ($this->actor_proxy_id == $approval_data["approver_proxy_id"] && $this->actor_type == "proxy_id")
                ? true
                : false;
        $actor_can_complete_assessment = $this->assessment->canActorComplete($is_admin, $target_type, $target_record_id);
        $current_target = $this->assessment->getCurrentTarget($target_record_id, $target_type);
        $current_progress = $this->assessment->getCurrentProgress();

        $attempts_sidebar_html = "";
        if ((!$actor_is_approver
                || ($actor_is_approver
                    && $assessment_data["assessor"]["assessor_id"] == $this->actor_proxy_id
                    && $assessment_data["assessor"]["type"] == "internal"
                )
            )
            && $actor_can_complete_assessment
            && $assessment_data["assessment"]["max_submittable"] > 1
        ) {
            // Approvers (that aren't the assessor) can't start or view different attempts.
            $target_progress = $this->assessment->getProgressForTarget($current_target["target_record_id"], $current_target["target_type"]);
            $sidebar_assessment_attempts_options = array(
                "completed_attempts" => $this->assessment->getCountCompleteProgress($current_target["target_record_id"], $current_target["target_type"]),
                "current_target" => $current_target,
                "min_attempts" => $assessment_data["assessment"]["min_submittable"],
                "max_attempts" => $assessment_data["assessment"]["max_submittable"],
                "assessment_uri" => $assessment_data["meta"]["assessment_uri_internal"],
                "aprogress_id" => !empty($current_progress) ? $current_progress["aprogress_id"] : null,
                "progress" => $target_progress
            );
            $sidebar_assessment_attempts = new Views_Assessments_Sidebar_Attempts();
            if ($attempts_sidebar_html = $sidebar_assessment_attempts->render($sidebar_assessment_attempts_options, false)) {
                if ($add_to_sidebar_immediately) {
                    assessment_sidebar_item($attempts_sidebar_html, $id = "assessment-attempts-sidebar", $state = "open", $position = SIDEBAR_APPEND);
                }
            }
        }
        return $attempts_sidebar_html;
    }

    /**
     * Render a target sidebar entry. Optionally, immediately append it to the sidebar.
     * The sidebar entry for a target is actually comprised of target summary and the target switcher.
     *
     * @param bool $is_admin
     * @param bool $add_to_sidebar_immediately
     * @param bool|int $target_record_id
     * @param bool|string $target_type
     * @param bool|int $specified_assessment_id
     * @param bool|int $specified_progress_id
     * @return string
     */
    public function renderAssessmentSidebarTarget($is_admin = false, $add_to_sidebar_immediately = true, $target_record_id = false, $target_type = false, $specified_assessment_id = false, $specified_progress_id = false) {
        global $translate;
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id,
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        $assessment_data = $this->assessment->fetchData();
        if (empty($assessment_data)) {
            $this->addErrorMessages($this->assessment->getErrorMessages());
            return false;
        }
        $approval_data = $this->assessment->getApprovalDataForProgress();
        $actor_is_approver = empty($approval_data)
            ? false
            : ($this->actor_proxy_id == $approval_data["approver_proxy_id"] && $this->actor_type == "proxy_id")
                ? true
                : false;
        $actor_can_complete_assessment = $this->assessment->canActorComplete($is_admin, $target_type, $target_record_id);
        $actor_is_assessor = ($assessment_data["assessor"]["assessor_id"] == $this->actor_proxy_id
            && $assessment_data["assessor"]["type"] == "internal"
        );
        $current_target = $this->assessment->getCurrentTarget($target_record_id, $target_type);
        $current_progress = $this->assessment->getCurrentProgress();

        /**
         * Render the target(s) side bar entry
         **/

        $sidebar_options = array(
            "assessor_is_viewer" => $actor_is_assessor,
            "target_type" => $current_target["target_type"],
            "target_name" => $current_target["target_name"],
            "assessment_in_progress" => false, // gets set to true below if appropriate
            "delivery_date" => $assessment_data["assessment"]["delivery_date"] ? strftime("%Y-%m-%d", $assessment_data["assessment"]["delivery_date"]) : null,
            "associated_record_type" => $assessment_data["assessment"]["associated_record_type"],
            "associated_record_id" => $assessment_data["assessment"]["associated_record_id"],
        );
        if (!empty($assessment_data["associated_record"])) {
            $sidebar_options["associated_entity_name"] = $assessment_data["associated_record"]["associated_entity_name"];
            $sidebar_options["target_organisation"] = $assessment_data["associated_record"]["associated_organisation_name"];
            $sidebar_options["start_date"] = $assessment_data["associated_record"]["start_date"] ? strftime("%Y-%m-%d", $assessment_data["associated_record"]["start_date"]) : null;
            $sidebar_options["end_date"] = $assessment_data["associated_record"]["end_date"] ? strftime("%Y-%m-%d", $assessment_data["associated_record"]["end_date"]) : null;
        }
        if ($assessment_data["assessment"]["associated_record_type"] == "schedule_id") {
            // Associated record is a schedule, so replace the entity name and dates with the concatenated schedule with block name(s).
            if ($assessment_data["assessment"]["rotation_start_date"] && $assessment_data["assessment"]["rotation_end_date"] && !empty($assessment_data["associated_record"]["source_record"])) {
                $block_string = $this->getConcatenatedBlockString(
                    $assessment_data["assessment"]["dassessment_id"],
                    new Models_Schedule($assessment_data["associated_record"]["source_record"]),
                    $assessment_data["assessment"]["rotation_start_date"],
                    $assessment_data["assessment"]["rotation_end_date"],
                    $assessment_data["associated_record"]["associated_organisation_id"],
                    " - ",
                    ", ",
                    true,
                    true
                );
                $sidebar_options["associated_entity_name"] = $block_string;
                $sidebar_options["start_date"] = strftime("%Y-%m-%d", $assessment_data["assessment"]["rotation_start_date"]);
                $sidebar_options["end_date"] = strftime("%Y-%m-%d", $assessment_data["assessment"]["rotation_end_date"]);
            }
        } else if ($assessment_data["assessment"]["associated_record_type"] == "event_id") {
            // Associated record is an event, so format the timestamps
            $timeframe_strings = Entrada_Utilities_Assessments_DistributionLearningEvent::buildTimeframeStrings($assessment_data["associated_record"]["start_date"], $assessment_data["associated_record"]["end_date"]);
            $sidebar_options["start_date"] = $timeframe_strings["timeframe_start"];
            $sidebar_options["end_date"] = $timeframe_strings["timeframe_end"];
        }
        if ($current_target["target_type"] == "proxy_id") {
            // User-based target, so fetch some user-related data
            if ($target_user_object = User::fetchRowByID($current_target["target_record_id"], $assessment_data["assessment"]["organisation_id"])) { // Sorry: Models_User doesn't have getGroup()
                $sidebar_options["target_photo_uri"] = Entrada_Utilities::fetchUserDefaultPhotoURL($target_user_object->getID(), $target_user_object->getPrivacyLevel());
                $sidebar_options["target_group"] = $translate->_(ucfirst($target_user_object->getGroup())); // Translate for org specific group/role title localization
                $sidebar_options["target_email"] = $target_user_object->getEmail();
                if ($target_organisation_object = Models_Organisation::fetchRowByID($target_user_object->getOrganisationId())) {
                    $sidebar_options["target_organisation"] = $target_organisation_object->getOrganisationTitle();
                }
            }
        }
        if (!empty($current_progress)) {
            // Set the progress flag, if we're "in progress" for this current record/attempt.
            if ($current_progress["progress_value"] == "inprogress") {
                $sidebar_options["assessment_in_progress"] = true;
            }
        }

        // Generate target sidebar HTML
        $sidebar_target_view = new Views_Assessments_Sidebar_Target();
        $target_sidebar_html = $sidebar_target_view->render($sidebar_options, false);

        if ($add_to_sidebar_immediately) {
            assessment_sidebar_item($target_sidebar_html, $id = "assessment-target-sidebar", $state = "open", $position = SIDEBAR_APPEND);
        }
        return $target_sidebar_html;
    }

    public function renderAssessmentTargetSwitcher($is_admin = false, $add_to_sidebar_immediately = true, $target_record_id = false, $target_type = false, $specified_assessment_id = false, $specified_progress_id = false) {
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id,
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        $assessment_data = $this->assessment->fetchData();
        if (empty($assessment_data)) {
            $this->addErrorMessages($this->assessment->getErrorMessages());
            return false;
        }
        $approval_data = $this->assessment->getApprovalDataForProgress();
        $actor_is_approver = empty($approval_data)
            ? false
            : ($this->actor_proxy_id == $approval_data["approver_proxy_id"] && $this->actor_type == "proxy_id")
                ? true
                : false;
        $actor_can_complete_assessment = $this->assessment->canActorComplete($is_admin, $target_type, $target_record_id);
        $actor_is_assessor = ($assessment_data["assessor"]["assessor_id"] == $this->actor_proxy_id
            && $assessment_data["assessor"]["type"] == "internal"
        );
        $current_target = $this->assessment->getCurrentTarget($target_record_id, $target_type);
        $current_progress = $this->assessment->getCurrentProgress();

        // Append the target switcher, if appropriate, to the target sidebar html.
        // Approvers can't switch between targets directly.
        if (count($assessment_data["targets"]) > 1 && !$actor_is_approver && $actor_can_complete_assessment) {
            $target_switcher_options = array(
                "target_name" => $current_target["target_name"],
                "assessor_value" => $assessment_data["assessor"]["assessor_id"],
                "assessor_type" => $assessment_data["assessor"]["type"],
                "dassessment_id" => $assessment_data["assessment"]["dassessment_id"],
                "max_attempts" => $assessment_data["assessment"]["max_submittable"],
                "min_attempts" => $assessment_data["assessment"]["min_submittable"],
                "distribution_deleted_date" => empty($assessment_data["distribution"]) ?
                    null :
                    $assessment_data["distribution"]["distribution_record"]["deleted_date"],
                "external_hash" => $assessment_data["assessment"]["external_hash"],
                "targets" => $this->assessment->getAssessmentTargetList(),
                "targets_pending" => $this->assessment->getCountUniqueTargetsPending(),
                "targets_inprogress" => $this->assessment->getCountUniqueTargetsInProgress(),
                "targets_complete" => $this->assessment->getCountUniqueTargetsComplete(),
                "allow_progress_swap" => $assessment_data["assessment"]["feedback_required"] ? false : true
            );
            $sidebar_target_switcher_view = new Views_Assessments_Sidebar_TargetsSwitcher();
            $target_switcher_html = $sidebar_target_switcher_view->render($target_switcher_options, false);
            return $target_switcher_html;
        }
    }

    /**
     * Build a list of the form mutators to apply to a given form, based on the assessment options for this assessment.
     *
     * @param bool $specified_assessment_id
     * @param bool $specified_progress_id
     * @return array|bool
     */
    public function buildFormMutatorList($specified_assessment_id = false, $specified_progress_id = false) {
        global $translate;
        $assessment_id = $this->whichID("dassessment_id", $specified_assessment_id);
        $progress_id = $this->whichID("aprogress_id", $specified_progress_id);
        $construction = array(
            "dassessment_id" => $assessment_id,
            "aprogress_id" => $progress_id,
        );
        if (!$this->buildAssessmentObject($construction)) {
            return false;
        }
        $assessment_data = $this->assessment->fetchData();
        if (empty($assessment_data)) {
            $this->addErrorMessages($this->assessment->getErrorMessages());
            return false;
        }
        return $this->assessment->buildFormMutatorListFromAssessmentOptions(true);
    }

    //-- PIN handling functionality --//

    /**
     * Validate the assessor PIN nonce.
     *
     * @param int $dassessment_id
     * @param int $aprogress_id
     * @param string $nonce
     * @param int $assessor_id
     * @return bool
     */
    public function validateAssessorPinNonce($dassessment_id, $aprogress_id, $nonce, $assessor_id) {
        global $translate;
        $assessment_object = $this->buildWorkerObject(
            "assessment",
            array(
                "dassessment_id" => $dassessment_id,
                "aprogress_id" => $aprogress_id,
                "limit_dataset" => array("assessment", "assessor", "progress")
            )
        );
        $dataset = $assessment_object->fetchData();
        if (empty($dataset)) {
            $this->addErrorMessage($translate->_("Unable to fetch assessment data."));
            return false;
        }
        // Validate that the nonce matches the assessment/assessor
        // The nonce is the hashed_pin and the latest progress response time.
        $assessor_info = $this->getAssessorPINInfo($assessor_id, true);
        if (empty($assessor_info)) {
            $this->addErrorMessage($translate->_("Failed to validate assessor PIN."));
            return false;
        }
        $latest_progress_response = $assessment_object->getLatestProgressResponse();
        if (empty($latest_progress_response)) {
            // No responses found, so use the progress record's created date instead
            $current_progress = $assessment_object->getCurrentProgress(); // no progress
            if (empty($current_progress)) {
                return false;
            }
            $nonce_time = $current_progress["created_date"];
        } else {
            $nonce_time = $latest_progress_response["created_date"];
        }
        $rebuilt_nonce = sha1($assessor_info["hashed_pin"] . $nonce_time);
        if ($rebuilt_nonce !== $nonce) {
            $this->addErrorMessage($translate->_("Incorrect PIN."));
            return false;
        }
        return true;
    }

    /**
     * Generate a one-time use (nonce) token for a validated assessor PIN.
     *
     * @param $dassessment_id
     * @param $aprogress_id
     * @param $assessor_id
     * @param $assessor_pin
     * @return bool|string
     */
    public function generateAssessorPinNonce($dassessment_id, $aprogress_id, $assessor_id, $assessor_pin) {
        global $translate;
        $assessment_object = $this->buildWorkerObject(
            "assessment",
            array(
                "dassessment_id" => $dassessment_id,
                "aprogress_id" => $aprogress_id,
                "limit_dataset" => array("assessment", "assessor", "progress")
            )
        );
        $dataset = $assessment_object->fetchData();
        if (empty($dataset)) {
            $this->addErrorMessage($translate->_("Unable to fetch assessment data."));
            return false;
        }
        if (!isset($dataset["assessment"]["assessment_method_data"]["assessor_value"])) {
            $this->addErrorMessage($translate->_("This assessment is not configured for PIN access."));
            return false;
        }
        if ($dataset["assessment"]["assessment_method_data"]["assessor_value"] != $assessor_id) {
            $this->addErrorMessage($translate->_("Invalid assessor specified."));
            return false;
        }
        $hashed_pin = $this->validateAssessorPIN($assessor_id, $assessor_pin, true);
        if ($hashed_pin === false) {
            return false;
        }
        // The nonce is based on progress responses.
        // If there are no progress responses, then we use the progress record's created date (the updated date may still be null, so we don't use that).
        $latest_progress_response = $assessment_object->getLatestProgressResponse();
        if (empty($latest_progress_response)) {
            // No responses found, so use the progress record's created date instead
            $current_progress = $assessment_object->getCurrentProgress(); // no progress
            if (empty($current_progress)) {
                return false;
            }
            $nonce_time = $current_progress["created_date"];
        } else {
            $nonce_time = $latest_progress_response["created_date"];
        }
        $nonce = sha1($hashed_pin . $nonce_time);
        return $nonce;
    }

    /**
     * For the given assessor ID, validate that the supplied plain-text PIN, when hashed, matches the stored PIN hash.
     *
     * Optionally, returns the hash on success instead of bool (still returns false on any error).
     *
     * @param $assessor_id
     * @param $assessor_pin
     * @param bool $return_hash
     * @return bool|string
     */
    public function validateAssessorPIN($assessor_id, $assessor_pin, $return_hash = false) {
        global $translate;
        $pin_info = $this->getAssessorPINInfo($assessor_id);
        if (empty($pin_info)) {
            return false;
        }
        $hashed_pin = $this->generatePINHash($assessor_pin, $pin_info["salt"]);
        if ($hashed_pin != $pin_info["hashed_pin"]) {
            $this->addErrorMessage($translate->_("PIN is incorrect."));
            return false;
        }
        if ($return_hash) {
            return $hashed_pin;
        }
        return true;
    }

    /**
     * Generate a hash of a PIN with given value and salt.
     *
     * @param $pin_value
     * @param $salt
     * @return string
     */
    public static function generatePINHash($pin_value, $salt) {
        return sha1($pin_value . $salt);
    }

    /**
     * Fetch the hashed version of the user's PIN and the salt used to create it.
     *
     * @param int $assessor_id
     * @param bool $suppress_error
     * @return array|bool
     */
    protected function getAssessorPINInfo($assessor_id, $suppress_error = false) {
        global $translate;
        $assessor = Models_User::fetchRowByID($assessor_id);
        if (!$assessor) {
            if (!$suppress_error) {
                $this->addErrorMessage($translate->_("Unable to find specified assessor."));
            }
            return false;
        }
        if (!$assessor->getPin()) {
            if (!$suppress_error) {
                $this->addErrorMessage($translate->_("PIN has not been set."));
            }
            return false;
        }
        return array(
            "hashed_pin" => $assessor->getPin(),
            "salt" => $assessor->getSalt()
        );
    }

    //-- Hook handling functionality --//

    /**
     * Enable one of the hooks, or add a new hook.
     *
     * The callback can be a Closure object, or the name of a callable
     * function, be it an internal method or global/public scope function.
     *
     * @param string $hook_timing
     * @param string|closure $callback
     * @param bool $check_execution_status
     * @return bool
     */
    public function addHook($hook_timing, $callback, $check_execution_status = true) {
        global $translate;
        $argv = func_get_args();

        if (!in_array($hook_timing, array_keys($this->hooks))) {
            $this->addErrorMessage(sprintf($translate->_("Failed to add hook of unknown type (%s)."), $hook_timing));
            return false;
        }
        // Clean up the arguments to save in the hook status structure
        array_shift($argv); // remove $hook_timing
        array_shift($argv); // remove $callback
        if (!empty($argv)) {
            array_shift($argv); // remove $check_execution_status, if specified
        }
        $default_struct = $this->buildHookStatusStruct(
            $callback,
            $argv, // can be empty array, otherwise it will contain anything else on the stack.
            $check_execution_status,
            null,
            null
        );
        // Add the hook to our list of executions.
        // Use hook timing to know when to run it.
        $this->hooks[$hook_timing][] = $default_struct;
        return true;
    }

    /**
     * Execute all hooks of a particular type (or all of them), then save the results in the execution status array.
     *
     * @return bool
     */
    private function executeHooks() {
        global $translate;
        $argv = func_get_args();
        if (empty($argv)) {
            $type = null;
        } else {
            $type = array_shift($argv);
        }
        foreach ($this->hooks as $hook_type => $defined_hooks) {
            // Execute a single hook type, OR execute all hooks
            if ($type == $hook_type || $type === null) {
                if (empty($defined_hooks)) {
                    continue;
                }
                foreach ($defined_hooks as $i => $defined_hook) {
                    $result = null;
                    $arguments = $defined_hook["arguments"]; // Merge the run-time arguments with the predefined hook's stored arguments (passed in at declaration time)
                    foreach ($argv as $arg) {
                        $arguments[] = $arg;
                    }
                    if (is_callable($defined_hook["hook_name"])) {
                        $result = call_user_func_array($defined_hook["hook_name"], $arguments);
                        $this->hooks[$hook_type][$i]["execution_status"] = $result;
                        if ($result === false) {
                            $this->hooks[$hook_type][$i]["errors"] = $translate->_("Encountered an error.");
                        }
                    } else if (method_exists($this, $defined_hook["hook_name"])) {
                        $result = call_user_func_array(array($this, $defined_hook["hook_name"]), $arguments);
                        $this->hooks[$hook_type][$i]["execution_status"] = $result;
                        if ($result === false) {
                            $this->hooks[$hook_type][$i]["errors"] = $this->getErrorMessages();
                        }
                    }
                }
            }
        }
        // For each of the executed hooks, check their execution status (if appicable)
        foreach ($this->hooks as $hook_type => $defined_hooks) {
            // Check the execution of the hooks we executed
            if ($type == $hook_type || $type === null) {
                if (empty($defined_hooks)) {
                    continue;
                }
                foreach ($defined_hooks as $i => $defined_hook) {
                    if ($defined_hook["check_execution_status"]) {
                        if (!$this->hooksExecutionStatus($type)) {
                            // If any of the hooks failed, return false.
                            // Errors messages are (should be) added via the hook itself.
                            return false;
                        }
                    }
                }
            }
        }
        // Execution was OK, OR nothing was run.
        return true;
    }

    /**
     * For the given hook type, check if any executions have returned false.
     *
     * @param $type
     * @return bool
     */
    private function hooksExecutionStatus($type) {
        foreach ($this->hooks as $hook_type => $hook_data) {
            if ($hook_type == $type) {
                foreach ($hook_data as $hook_struct) {
                    // NULL = not run, true OK, false error
                    if ($hook_struct["execution_status"] === false) {
                        return false; // error found
                    }
                }
            }
        }
        return true;
    }

    /**
     * Build a default stucture that contains information about a hook, including the status of execution.
     * $hook_name can be a method name, or a closure object.
     *
     * @param null $hook_name
     * @param array $arguments
     * @param bool $check_exec_status
     * @param null $execution_status
     * @param null $errors
     * @return array
     */
    private function buildHookStatusStruct($hook_name = null, $arguments = array(), $check_exec_status = true, $execution_status = null, $errors = null) {
        return array(
            "hook_name" => $hook_name,                         // What are we executing?
            "arguments" => $arguments,                         // Initial arguments for the function to execute
            "check_execution_status" => $check_exec_status,    // Check the status of the executed function at run time?
            "execution_status" => $execution_status,           // NULL = not executed, true = success, false = failure
            "errors" => $errors                                // Error messages, if any.
        );
    }

    //-- Predefined hook methods --//

    /**
     * A test method, always returning false through the hook.
     *
     * @return bool
     */
    protected function hookTestFailure() {
        global $translate;
        $this->addErrorMessage($translate->_("Testing failure state."));
        return false;
    }

    /**
     * Send notifications for pending assessments.
     *
     * @param int $assessor_id
     * @param string $assessor_type
     * @param int|bool $dassessment_id
     * @return bool
     */
    protected function hookSendAssessmentNotifications($assessor_id = null, $assessor_type = "internal", $dassessment_id = false) {
        global $translate;
        if (!$assessor_id) {
            $this->addErrorMessage($translate->_("No assessor to notify, none specified."));
            return false;
        }
        $assessment_data = $this->assessment->fetchData();
        if (empty($assessment_data)) {
            $this->addErrorMessage($translate->_("Unable to fetch assessment data."));
            $this->addErrorMessages($this->assessment->getErrorMessages());
            return false;
        }
        $current_target = $this->getCurrentAssessmentTarget();
        if (empty($current_target)) {
            $this->addErrorMessage($translate->_("Unable to find current target."));
            return false;
        }
        if ($current_target["target_type"] == "proxy_id" && $assessor_type == "internal") {
            $this->queueAssessorNotifications(
                $this->getAssessmentRecord($dassessment_id),
                $assessor_id,
                null,
                1,
                false,
                false,
                false,
                false
            );
        }
        return true;
    }

    /**
     * A predefined hook for sending out flagging notifications.
     *
     * @param array $severity_levels
     * @param array $form_objectives
     * @param int $assessor_id
     * @param string $assessor_type
     * @param int $aprogress_id
     * @return bool
     */
    protected function hookSendFlaggingNotifications($severity_levels = array(), $form_objectives = array(), $assessor_id, $assessor_type = "internal", $aprogress_id = null) {
        global $translate;
        if (empty($severity_levels)) {
            // No severity levels were given.
            $this->addErrorMessage($translate->_("No severity level specified."));
            return false;
        }
        if (empty($form_objectives)) {
            // Nothing to work on. This isn't an error state, per se.
            return true;
        }
        if (!$aprogress_id) {
            $this->addErrorMessage($translate->_("No progress ID specified."));
            return false;
        }
        if ($assessor_type != "proxy_id" && $assessor_type != "internal") {
            $this->addErrorMessage($translate->_("Unsupported assessor type"));
            return false;
        }
        if (!$assessor_id) {
            $this->addErrorMessage($translate->_("No assessor specified."));
            return false;
        }
        // For each of the courses associated with this progress ID, we notify
        $courses = array();
        foreach ($form_objectives as $form_objective) {
            $courses[$form_objective["course_id"]] = $form_objective["course_id"];
        }
        if (empty($courses)) {
            $this->addErrorMessage(sprintf($translate->_("Unable to determine what %s this assessment was for."), $translate->_("Course")));
            return false;
        }
        $this->assessment->setFetchFormData(true);
        $assessment_data = $this->assessment->fetchData();
        if (empty($assessment_data)) {
            $this->addErrorMessage($translate->_("Failed to fetch assessment data."));
            return false;
        }

        $notifications = array();

        // Determine who to notify.
        // Example: $severity_levels = array("flag_severity_notify_directors" => 9990, "flag_severity_notify_coordinators" => 9991, "flag_severity_notify_directors_and_coordinators" => 9999);
        // We have an array of severities given to us, so we find those severities
        foreach ($severity_levels as $notify_type => $severity_level) {
            // Match the severity level with something that is defined in the flag severity table
            $org_flags = Models_Assessments_Flag::fetchAllByOrganisationFlagValue($assessment_data["assessment"]["organisation_id"], $severity_level);
            // Now we have a list of records for this org that match the severity level.
            // So let's find the selected responses that have the primary keys of the specified flags.
            // E.g., find all item responses with flag values of 2 and 3, which directly correspond to flag records primary keys of 2 and 3, which have values of 9999 (matching our specified severity level)
            if (!empty($org_flags)) {
                foreach ($org_flags as $org_flag) {
                    // Fetch all of the responses for this record primary key
                    $flagged_responses = $this->getFlaggedResponsesSelected($org_flag->getID());
                    if (!empty($flagged_responses)) {
                        $notifications[$notify_type][] = $org_flag->getID();
                    }
                }
            }
        }
        if (!empty($notifications)) {
            // For each course, notify the appropriate people.
            foreach ($courses as $course_id) {
                foreach ($notifications as $notify_type => $flag_value) {
                    if ($notify_type == "flag_severity_notify_directors_and_coordinators") {
                        Models_Assessments_Notification::sendCourseDirectorsFlaggingNotification($aprogress_id, $assessor_id, $course_id);
                        Models_Assessments_Notification::sendCourseCoordinatorsFlaggingNotification($aprogress_id, $assessor_id, $course_id);
                    } else {
                        if ($notify_type == "flag_severity_notify_coordinators") {
                            Models_Assessments_Notification::sendCourseCoordinatorsFlaggingNotification($aprogress_id, $assessor_id, $course_id);
                        }
                        if ($notify_type == "flag_severity_notify_directors") {
                            Models_Assessments_Notification::sendCourseDirectorsFlaggingNotification($aprogress_id, $assessor_id, $course_id);
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * A predefined hook for acting on the dataset for the current assessment method.
     * Execute any assessment method related functionality, including any other assessment
     * method related hooks.
     *
     * @return bool
     */
    protected function hookAssessmentMethod() {
        global $translate;
        $this->assessment->setStale();
        $assessment_data = $this->assessment->fetchData();
        if (empty($assessment_data)) {
            $this->addErrorMessage($translate->_("Unable to fetch assessment data. Cannot determine assessment method."));
            return false;
        }
        if (empty($assessment_data["assessment_method"])) {
            return true; // There's no assessment method defined. This isn't an error per se, so we don't return false, but we can't continue without it.
        }

        $original_aprogress_id = $this->assessment->getAprogressID(); // Current aprogress
        $original_dassessment_id = $this->assessment->getDassessmentID();
        $new_aprogress_id = null; // The aprogress of the copied assessment
        $copy_assessment = false;
        $copy_progress = false;
        $progress_override_value = null;
        $current_target = $this->getCurrentAssessmentTarget();
        $add_item_invisibility_option = false;

        // The current assessor (indicated by the assessment record; the person submitting the form)
        $current_assessor_id = $assessment_data["assessor"]["assessor_id"];
        $current_assessor_type = $assessment_data["assessor"]["type"];
        $current_assessor_type_specific = ($current_assessor_type == "internal") ? "proxy_id" : $current_assessor_type;

        // The assessor indicated by the assessment method (the next in the chain, if any)
        $new_assessor_id = null;
        $new_assessor_type = null;

        // This is the user we will be notifying upon submission, if applicable
        $notify_user_id = null;
        $notify_user_type = null;
        $notify_assessment_id = null; // The new assessment to notify for

        // This is the user we will be notifying when flagged responses are submitted
        $flagging_notify_assessor_id = null;
        $flagging_notify_assessor_type = null;

        // The assessors that we will update time-to-complete progress for.
        $progress_time_assessors = array();

        // The assessors that we will add submission statistic records for
        $completion_assessors = array();

        // Determine this assessment's assessor group (e.g., student or faculty)
        $assessor_group = isset($assessment_data["assessment"]["assessment_method_data"]["assessor_group"])
            ? $assessment_data["assessment"]["assessment_method_data"]["assessor_group"]
            : null;

        // Determine who, if any, is going to be the next assessor
        if (isset($assessment_data["assessment"]["assessment_method_data"]["assessor_value"])
            && isset($assessment_data["assessment"]["assessment_method_data"]["assessor_type"])
        ) {
            $new_assessor_id = $assessment_data["assessment"]["assessment_method_data"]["assessor_value"];
            $new_assessor_type = $assessment_data["assessment"]["assessment_method_data"]["assessor_type"];
        }

        $current_assessor_key = "{$current_assessor_id}-{$current_assessor_type}";
        $new_assessor_key = $new_assessor_id ? "{$new_assessor_id}-{$new_assessor_type}" : "";

        // Data to replace in the copied version of the assessment (for the new assessor), if applicable
        $replacements = array(
            "dassessment_id" => null,
            "assessor_value" => $new_assessor_id,
            "assessor_type" => $new_assessor_type
        );

        switch ($assessment_data["assessment_method"]["shortname"]) {

            case "complete_and_confirm_by_email":
                if (!$assessor_group) {
                    $this->addErrorMessage($translate->_("Unable to determine assessor group."));
                    return false;
                }

                // The resident completes the assessment, and the attending gets a copy afterward.
                // The copy is in-progress with the student's data already prepopulated.
                if ($assessor_group == "student") {

                    // For this method we only care about calculating the time-to-completion of the initial completion, not the copied version.
                    // We ignore the progress time of the second (since it could be a review that takes only seconds)

                    // Resident is done their portion, send the attending a copy.
                    $copy_assessment = true;
                    $copy_progress = true;
                    $progress_override_value = "inprogress";
                    $replacements["assessment_method_data"] = json_encode(array("assessor_group" => "faculty"));

                    // For the student, we save both the completion and update the progress time
                    $progress_time_assessors[$current_assessor_key] = array("assessor_id" => $current_assessor_id, "assessor_type" => $current_assessor_type);
                    $completion_assessors[$current_assessor_key] = array("assessor_id" => $current_assessor_id, "assessor_type" => $current_assessor_type);

                    // The faculty is the person to notify
                    $notify_user_id = $new_assessor_id;
                    $notify_user_type = $new_assessor_type;

                } else {

                    // If the assessor group is not the student, we send notifications (if any)
                    $flagging_notify_assessor_id = $current_assessor_id;
                    $flagging_notify_assessor_type = $current_assessor_type;

                    // For the non-student, we only save a completion statistic. The progress time is ignored
                    $completion_assessors[$current_assessor_key] = array("assessor_id" => $current_assessor_id, "assessor_type" => $current_assessor_type);
                }
                break;

            case "complete_and_confirm_by_pin":
                if (!$assessor_group) {
                    $this->addErrorMessage($translate->_("Unable to determine assessor group."));
                    return false;
                }

                // The resident completes the assessment, the attending (who already validated their PIN) gets a completed copy.
                if ($assessor_group == "student") {
                    $copy_assessment = true;
                    $copy_progress = true;
                    $progress_override_value = "complete";
                    $replacements["assessment_method_data"] = json_encode(array("assessor_value" => $new_assessor_id, "assessor_type" => $new_assessor_type, "assessor_group" => "faculty"));
                }

                // We want to add an assessment option to hide invisible items from the original target.
                $add_item_invisibility_option = true;

                // Notify using the PIN owner's assessor ID.
                $flagging_notify_assessor_id = $new_assessor_id;
                $flagging_notify_assessor_type = $new_assessor_type;

                // Save the current assessor's assessment completion statistics.
                $progress_time_assessors[$current_assessor_key] = array("assessor_id" => $current_assessor_id, "assessor_type" => $current_assessor_type);
                $completion_assessors[$current_assessor_key] = array("assessor_id" => $current_assessor_id, "assessor_type" => $current_assessor_type);

                // Save the PIN owner's assessment completion statistics.
                if ($new_assessor_key) {
                    $completion_assessors[$new_assessor_key] = array("assessor_id" => $new_assessor_id, "assessor_type" => $new_assessor_type);
                    $progress_time_assessors[$new_assessor_key] = array("assessor_id" => $new_assessor_id, "assessor_type" => $new_assessor_type);
                }

                break;

            case "double_blind_assessment":
                if (!$assessor_group) {
                    $this->addErrorMessage($translate->_("Unable to determine assessor group."));
                    return false;
                }

                // The resident completes the assessment, the attending gets a blank in-progress copy.
                if ($assessor_group == "student") {
                    $copy_assessment = true;
                    $copy_progress = false;
                    $progress_override_value = "inprogress";
                    $replacements["assessment_method_data"] = json_encode(array("assessor_group" => "faculty"));

                    $flagging_notify_assessor_id = null; // No assessor to act on, since this is an intermediate assessment

                    // Notify the attending that there's an assessment waiting
                    $notify_user_id = $new_assessor_id;
                    $notify_user_type = $new_assessor_type;

                } else {

                    // Notify using the non-student assessor only
                    $flagging_notify_assessor_id = $assessment_data["assessor"]["assessor_id"];
                    $flagging_notify_assessor_type = $assessment_data["assessor"]["type"];

                }

                // Save the current assessor's progress and completion status
                $progress_time_assessors[$current_assessor_key] = array("assessor_id" => $current_assessor_id, "assessor_type" => $current_assessor_type);
                $completion_assessors[$current_assessor_key] = array("assessor_id" => $current_assessor_id, "assessor_type" => $current_assessor_type);

                break;

            default:

                // In all other cases, we save statistics using the current assessor
                $progress_time_assessors[$current_assessor_key] = array("assessor_id" => $current_assessor_id, "assessor_type" => $current_assessor_type);
                $completion_assessors[$current_assessor_key] = array("assessor_id" => $current_assessor_id, "assessor_type" => $current_assessor_type);

                // In all other cases, we assume that the assessment's assessor is the one we are notifying about (that the assessor flagged something about the target)
                $flagging_notify_assessor_id = $current_assessor_id;
                $flagging_notify_assessor_type = $current_assessor_type;
                break;
        }

        $assessments_info = array();
        $assessments_info[$current_assessor_key] = array("dassessment_id" => $original_dassessment_id, "aprogress_id" => $original_aprogress_id);

        /**
         * Add the item invisiblity mutator option, if required, to the current assessment.
         */
        if ($add_item_invisibility_option) {
            $this->assessment->createAssessmentOptions(
                "individual_json_options",
                array(
                    "items_invisible_to" => array(
                        array(
                            "type" => $current_assessor_type_specific, // The type must be "proxy_id" if the assessor is "internal"
                            "value" => $current_assessor_id
                        )
                    )
                )
            );
        }

        /**
         * If required, copy the assessment. This resets this object's internal pointers and dataset to the copy.
         */
        if ($copy_assessment) {
            if (empty($current_target)) {
                $this->addErrorMessage($translate->_("Unable to determine assessment target when attempting copy."));
                return false;
            }

            // Copy the progress and responses to the new assessor specified in assessment method data
            $status = $this->assessment->copy(
                $replacements["assessor_value"],
                $replacements["assessor_type"],
                array(
                    0 => array(
                        "atarget_id" => $current_target["atarget_id"],
                        "target_value" => $current_target["target_record_id"],
                        "target_type" => $current_target["target_type"]
                    )
                ),
                $replacements,
                $copy_progress,
                $progress_override_value
            );
            if (!$status) {
                $this->addErrorMessages($this->assessment->getErrorMessages());
                return false;
            }
            $new_dassessment_id = $this->assessment->getDassessmentID();
            $new_aprogress_id = $this->assessment->getAprogressID();
            $assessments_info[$new_assessor_key] = array("dassessment_id" => $new_dassessment_id, "aprogress_id" => $new_aprogress_id);

            if ($copy_progress) {
                // Add a view statistic for the copy's progress
                $a_statistic = Models_Assessment_Statistic::fetchRowByAssessmentID($original_dassessment_id);
                $this->addAssessmentStatistic(
                    $a_statistic["module"],
                    $a_statistic["sub_module"],
                    $new_assessor_id,
                    $new_dassessment_id,
                    $new_aprogress_id,
                    $current_target["target_record_id"],
                    "view",
                    $assessment_data["assessment"]["adistribution_id"],
                    $a_statistic["created_date"]
                );
            }

            if (!$this->assessment->saveCueAssessmentOption($new_aprogress_id, $new_dassessment_id, $copy_progress)) {
                $this->addErrorMessage($translate->_("Unable to update the cue assessment option"));
            }
        }

        /**
         * If there are assessors completing the assessment, save their statistics
         */
        if (!empty($completion_assessors)) {

            $statistic_data = array();
            foreach ($completion_assessors as $assessor_key => $completion_assessor) {
                // assessor_key = "{assessor_id}-{asessor_type}" -- should match the assessments info
                if (array_key_exists($assessor_key, $assessments_info)) {
                    $statistic_data[] = array(
                        "dassessment_id" => $assessments_info[$assessor_key]["dassessment_id"],
                        "aprogress_id" => $assessments_info[$assessor_key]["aprogress_id"],
                        "target_record_id" => $current_target["target_record_id"],
                        "target_type" => $current_target["target_type"],
                        "assessor_id" => $completion_assessor["assessor_id"],
                        "assessor_type" => $completion_assessor["assessor_type"],
                        "adistribution_id" => $assessment_data["assessment"]["adistribution_id"],
                    );
                }
            }

            // Execute the hook using this aggregated data
            $this->executeHooks(
                "assessment-method-statistic-completion",
                $statistic_data
            );
        }
        
        /**
         * If there are assessors to update progress completion time for, do it.
         */
        if (!empty($progress_time_assessors)) {
            $progress_ids = array();
            $progress_ids[] = $original_aprogress_id;
            if ($new_aprogress_id) {
                $progress_ids[] = $new_aprogress_id;
            }
            $this->executeHooks(
                "assessment-method-statistic-progress-time",
                $progress_ids
            );
        }

        /**
         * If there's an assessor to notify (non-self-assessment), notify them using the assessment method assessment notify hook.
         */
        if ($notify_user_id) {
            if ($notify_user_id != $current_target["target_record_id"]) {
                $this->executeHooks(
                    "assessment-method-assessment-notify",
                    $notify_user_id,
                    $notify_user_type,
                    $this->assessment->getAssessmentID()
                );
            }
        }

        /**
         * If there's an assessor to notify, notify them using the assessment method flagging notify hook.
         */
        if ($flagging_notify_assessor_id) {
            $this->executeHooks(
                "assessment-method-flagging-notify",
                $flagging_notify_assessor_id,
                $flagging_notify_assessor_type,
                $this->assessment->getAprogressID()
            );
        }

        // Finished successfully
        return true;
    }

    /**
     * Update the completion time for the progress record.
     * 
     * @param array $progress_info
     * @param int $ignore_over
     * @return bool
     */
    protected function hookUpdateProgressCompletionTime($progress_info = array(), $ignore_over = 900) {
        global $translate;

        foreach ($progress_info as $progress_id) {
            $total_time = 0;
            if ($progress_end_times = Models_Assessment_Statistic::fetchProgressEndTimes($progress_id)) {
                foreach ($progress_end_times as $end_time) {
                    if ($start_time = Models_Assessment_Statistic::getRelatedStartTime($end_time)) {
                        $session_time = $end_time["created_date"] - $start_time["created_date"];
                        if (!($session_time > $ignore_over)) {
                            $total_time += $session_time;
                        }
                    }
                }
            }
            if (!Models_Assessments_Progress::updateProgressTime($progress_id, $total_time)) {
                $this->addErrorMessage($translate->_("Failed to update progress record completion time."));
                return false;
            }
        }
        return true;
    }

    /**
     * Upon assessment-based completion, insert an assessment completion statistic.
     *
     * @param string $MODULE
     * @param string $SUBMODULE
     * @param array $statistic_data
     * @return bool
     */
    protected function hookSaveCompletionStatistic($MODULE, $SUBMODULE, $statistic_data = array()) {
        foreach ($statistic_data as $statistic) {
            $status = $this->addAssessmentStatistic(
                $MODULE,
                $SUBMODULE,
                $statistic["assessor_id"],
                $statistic["dassessment_id"],
                $statistic["aprogress_id"],
                $statistic["target_record_id"],
                "submit",
                $statistic["adistribution_id"]
            );
            if (!$status) {
                return $status;
            }
        }
        return true;
    }

    //-- Private Methods --//

    /**
     * Build a worker object of the given type.
     *
     * @param $object_type
     * @param array $construction_options
     * @return bool|mixed
     */
    private function buildWorkerObject($object_type, $construction_options = array()) {
        global $translate;
        $options = array(
            "actor_proxy_id" => $this->actor_proxy_id,
            "actor_organisation_id" => $this->actor_organisation_id,
            "actor_type" => $this->actor_type,
            "actor_scope" => $this->actor_scope,
            "actor_group" => $this->actor_group,
            "disable_internal_storage" => $this->disable_internal_storage,
            "global_storage" => $this->global_storage,
            "limit_dataset" => $this->limit_dataset,
            "form_limit_dataset" => $this->form_limit_dataset,
            "rubric_limit_dataset" => $this->rubric_limit_dataset,
            "fetch_deleted_targets" => $this->fetch_deleted_targets
        );
        $options = array_merge($options, $construction_options);
        switch ($object_type) {
            case "assessment":
                // $id is set via the construction_options and not used in this case.
                return new Entrada_Assessments_Workers_Assessment($options);
        }
        $this->addErrorMessage(sprintf($translate->_("Unable to fetch \"%s\" object."), str_replace("_", " ", $object_type)));
        return false;
    }

    /**
     * Build the internal assessment object property if required.
     * dassessment_id and aprogress_id can both be null, but they must be specified nonetheless.
     *
     * @param array $construction_options
     * @return bool
     */
    private function buildAssessmentObject($construction_options = array()) {
        global $translate;
        if (!array_key_exists("dassessment_id", $construction_options) && !array_key_exists("aprogress_id", $construction_options)) {
            $this->addErrorMessage($translate->_("Invalid construction options specified in buildAssessmentObject."));
            return false;
        }
        $dassessment_id = array_key_exists("dassessment_id", $construction_options) ? $construction_options["dassessment_id"] : null;
        $aprogress_id = array_key_exists("aprogress_id", $construction_options) ? $construction_options["aprogress_id"] : null;
        if (!$this->assessment->isStale()
            && $dassessment_id == $this->dassessment_id
            && $this->assessment->getAssessmentID() == $dassessment_id
            && $aprogress_id == $this->aprogress_id
            && $this->assessment->getAprogressID() == $aprogress_id
        ) {
            return true; // already built and not stale
        }
        $more_options["dassessment_id"] = $dassessment_id;
        $more_options["aprogress_id"] = $aprogress_id;
        $more_options["fetch_form_data"] = $this->fetch_form_data;
        $more_options["form_limit_dataset"] = $this->form_limit_dataset;
        $more_options["rubric_limit_dataset"] = $this->rubric_limit_dataset;
        $all_options = array_merge($construction_options, $more_options);
        if (!$new_worker = $this->buildWorkerObject("assessment", $all_options)) {
            // Failed to build a worker. Error is returned in buildWorkerObject method.
            return false;
        }
        $this->dassessment_id = $dassessment_id;
        $this->aprogress_id = $aprogress_id;
        $this->assessment = $new_worker;
        return true; // built successfully
    }
}