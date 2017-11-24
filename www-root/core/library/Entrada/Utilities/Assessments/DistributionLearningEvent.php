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
 * A class to handle all learning event assessment task related functionality.
  *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

/**
 * Targets:
 *      target_type = eventtype_id
 *      target_value = eventtype_id
 *
 * LEARNER: "Learners who are enrolled in events with the selected event types"
 * target_scope = self
 * target_role = learner
 *
 * FACULTY: "Faculty who taught events with the selected event types"
 * target_scope = self
 * target_role = faculty
 *
 * EVENT: "Events with the selected event types"
 * target_scope = self
 * target_role = any
 *
 *
 * Assessors:
 *      assessor_type = eventtype_id
 *      assessor_scope = attended_learners | all_learners | faculty | proxy_id | external_hash
 *      assessor_role = learner | faculty | any
 *      assessor_value = event type id | proxy id | external assessor id
 *
 *
 * "The assessors for this distribution are learners enrolled in the event"
 * ^-- "Send this assessment to all enrolled learners that attended the event"
 * assessor_scope = attended_learners
 * assessor_role = learner
 * assessor_value = event type id, drill down to find proxy
 *
 *
 * "The assessors for this distribution are learners enrolled in the event"
 * ^-- "Send this assessment to all enrolled learners, even if they did not attend the event "
 * assessor_scope = all_learners
 * assessor_role = learner
 * assessor_value = event type id, drill down to find proxy
 *
 *
 * "The assesors for this distribution are faculty members associated with the event"
 * assessor_scope = faculty
 * assessor_role = faculty
 * assessor_value = eventtype id
 *
 *
 * "Select individuals regardless of role"
 * assessor_type = proxy_id | external_hash
 * assessor_scope = self
 * assessor_role = any
 * assessor_value = proxy | external assessor id
 *
 */
class Entrada_Utilities_Assessments_DistributionLearningEvent extends Entrada_Utilities_Assessments_Base {

    protected $adistribution_id;
    protected $release_date         = false;
    private $distribution           = false;    // A copy of the related distribution object
    private $assessors_proxy_list   = array();  // List of proxy IDs associated with the distribution_assessors record, grouped by event id
    private $event_list             = array();  // The events (objects) for each event type specified in the distribution
    private $error_text             = "";

    public function __construct($arr = null) {
        parent::__construct($arr);
        if ($this->adistribution_id) {
            $this->setAdistributionID($this->adistribution_id, true);
        }
    }

    //--- Getters/Setters ---//

    public function setAdistributionID($id, $fetch_record = true) {
        $this->adistribution_id = $id;
        if ($fetch_record) {
            $this->distribution = Models_Assessments_Distribution::fetchRowByID($this->adistribution_id);
        }
    }

    public function setDistributionID($id, $fetch_record = true) {
        $this->setAdistributionID($id, $fetch_record);
    }

    public function getDistributionID() {
        return $this->adistribution_id;
    }

    public function getAdistributionID() {
        return $this->adistribution_id;
    }

    public function getDistribution() {
        return $this->distribution;
    }

    public function getAssessorsProxyList() {
        return $this->assessors_proxy_list;
    }

    public function getEventList() {
        return $this->event_list;
    }

    /**
     * Clear the object properties.
     */
    public function reset() {
        $this->distribution = false;
        $this->assessors_proxy_list = array();
        $this->event_list = array();
        $this->clearStorage();
        $this->clearTaskList();
    }

    /**
     * Error text is a debug string describing the error. Not meant for user-facing interfaces.
     *
     * @return string
     */
    public function getDebugErrorText() {
        return $this->error_text;
    }

    //--- Public functionality ---//

    /**
     * For this distribution (that creates assessment tasks for learning events), create the list of assessment tasks to create.
     * Default is to only create the list for learning events that need to currently exist for the cperiod associated with the
     * distribution. If specified, the start/end dates can be overridden, ignoring cperiods.
     *
     * @param bool $include_future_events
     * @param int $filter_start_date
     * @param int $filter_end_date
     * @return array|bool
     */
    public function buildLearningEventAssessmentTaskList($include_future_events = false, $filter_start_date = null, $filter_end_date = null) {

        if (!$this->distribution) {
            $this->error_text = "No distribution was set by the constructor.";
            return false;
        }

        // There can be multiple event types specified
        $distribution_event_types = Models_Assessments_Distribution_Eventtype::fetchAllByAdistributionID($this->getDistributionID());
        if (!$distribution_event_types || empty($distribution_event_types)) {
            $this->error_text = "No event types found.";
            return false;
        }

        // There can be multiple target records
        $distribution_targets = Models_Assessments_Distribution_Target::fetchAllByDistributionID($this->getDistributionID());
        if (!$distribution_targets || empty($distribution_targets)) {
            $this->error_text = "No distribution target records.";
            return false;
        }

        // There can be multiple assessor records
        $distribution_assessors = Models_Assessments_Distribution_Assessor::fetchAllByDistributionID($this->getDistributionID());
        if (!$distribution_assessors || empty($distribution_assessors)) {
            $this->error_text = "No distribution assessors.";
            return false;
        }

        // Populate the event list; fetch all the related event data and store them in our internal event list storage array.
        foreach ($distribution_event_types as $distribution_event_type) {
            if ($events = Models_Event::fetchAllByCourseIDEventtypeID($this->distribution->getCourseID(), $distribution_event_type->getEventtypeID())) { // fetch the events, by course.
                foreach ($events as $event) {
                    $this->event_list[$event->getID()] = $event;
                }
            }
        }
        if (empty($this->event_list)) {
            $this->error_text = "Unable to fetch the events.";
            return false;
        }

        // Get the current curriculum period to limit events to
        $curriculum_period = Models_Curriculum_Period::fetchRowByID($this->distribution->getCperiodID());
        if (!$curriculum_period) {
            $this->error_text = "Curriciulm period specified by distribution was not found.";
            return false;
        }

        // Curriculum period start and end dates
        $c_start_date = $curriculum_period->getStartDate();
        $c_end_date = ($include_future_events) ? $curriculum_period->getFinishDate() : time();

        // Override cperiod if filter is specified
        if ($filter_start_date) {
            $c_start_date = $filter_start_date;
        }
        if ($filter_end_date) {
            $c_end_date = $filter_end_date;
        }

        // Prune events outside of curriculum period
        foreach ($this->event_list as $i => $event) {
            $e_start_date = $event->getEventStart();
            $e_end_date = $event->getEventFinish();
            if (($c_start_date <= $e_start_date &&
                $c_start_date <= $e_end_date &&
                $c_end_date >= $e_start_date &&
                $c_end_date >= $e_end_date)
                && $e_end_date >= $this->release_date) {
                // This date is valid
                continue;
            } else {
                // This date is NOT valid, so prune it.
                unset($this->event_list[$i]);
            }
        }
        // Quit if we pruned everything
        if (empty($this->event_list)) {
            $this->error_text = "No events for this date range.";
            return false;
        }

        // Passed argument validation. Now iterate through the list of assessors and build assessment tasks per each.

        $this->resetTaskList($this->getDistributionID());

        // Create the task list per target
        foreach ($distribution_targets as $distribution_target) {

            // Drill-down for each assessor type
            foreach ($distribution_assessors as $distribution_assessor) {

                // Assessors will be derived from the event type ID
                if ($distribution_assessor->getAssessorType() == "eventtype_id") {
                    if ($distribution_assessor->getAssessorRole() == "learner" && ($distribution_assessor->getAssessorScope() == "all_learners" || $distribution_assessor->getAssessorScope() == "attended_learners")) {

                        // Assessors are learners associated with the learning event
                        $this->buildTaskListByAssessorsLearners($distribution_target, $distribution_assessor);

                    } else if ($distribution_assessor->getAssessorRole() == "faculty" && $distribution_assessor->getAssessorScope() == "faculty") {

                        // Assessors are the faculty associated with this learning event
                        $this->buildTaskListByAssessorsFaculty($distribution_target, $distribution_assessor);
                    }

                } else if ($distribution_assessor->getAssessorType() == "proxy_id" && $distribution_assessor->getAssessorScope() == "self") {

                    // Assessor is a specific, arbitrary proxy
                    $this->buildTaskListByAssessorsProxyID($distribution_target, $distribution_assessor);

                } else if ($distribution_assessor->getAssessorType() == "external_hash" && $distribution_assessor->getAssessorScope() == "self") {

                    // Assessor is external
                    $this->buildTaskListByAssessorsExternal($distribution_target, $distribution_assessor);
                }
            }
        }
        return $this->getTaskList();
    }

    /**
     * Based on the distribution target and given associated type (e.g., "proxy_id" or "event_id"), build
     * an array of relevant event target data.
     *
     * @param Models_Assessments_Distribution_Target $distribution_target
     * @param int $target_record_id
     * @param string $associated_type
     * @param int $associated_id
     * @return array
     */
    public function getLearningEventTargetData($distribution_target, $target_record_id, $associated_type, $associated_id) {
        $target_data = array();
        $target_data["target_record_id"] = $target_record_id;
        $target_data["target_type"] = false;

        $target_data["event_name"] = false;
        $target_data["event_start_date"] = false;
        $target_data["event_end_date"] = false;

        $target_data["timeframe_start"] = false;
        $target_data["timeframe_end"] = false;

        $target_data["user_name"] = false;
        $target_data["user_organisation"] = false;
        $target_data["user_organisation_title"] = false;
        $target_data["user_group"] = false;
        $target_data["user_email"] = false;
        $target_data["user_avatar"] = false;
        $target_data["user_group"] = false;

        if ($associated_type == "event_id") {
            if ($associated_event = Models_Event::get($associated_id)) {
                $target_data["event_name"] = $associated_event->getEventTitle();
                $target_data["event_start_date"] = $associated_event->getEventStart();
                $target_data["event_end_date"] = $associated_event->getEventFinish();

                $timeframes = $this->buildTimeframeStrings($target_data["event_start_date"], $target_data["event_end_date"]);
                $target_data["timeframe_start"] = $timeframes["timeframe_start"];
                $target_data["timeframe_end"] = $timeframes["timeframe_end"];
            }
        }

        if ($distribution_target->getTargetType() == "eventtype_id" && $distribution_target->getTargetScope() == "self") {
            switch ($distribution_target->getTargetRole()) {
                case "learner":
                case "faculty":
                    // The organisation and group fetching functionality should be in the Models_User class. Until it is ported over, we fetch it using the old-style class.
                    $target_user = User::fetchRowByID($target_record_id);
                    if ($target_user) {
                        $organisation = Organisation::get($target_user->getActiveOrganisation());
                        $user_photo_details = Entrada_Utilities::fetchUserPhotoDetails($target_user->getID(), $target_user->getPrivacyLevel());
                        if ($user_photo_details && isset($user_photo_details["default_photo"]) && isset($user_photo_details[$user_photo_details["default_photo"] . "_url"])) {
                            $avatar = $user_photo_details[$user_photo_details["default_photo"] . "_url"];
                        } else {
                            $avatar = ENTRADA_URL . "/images/headshot-male.gif";
                        }
                        $target_data["target_type"] = "proxy_id";
                        $target_data["user_organisation_title"] = ($organisation) ? $organisation->getTitle() : "";
                        $target_data["user_avatar"] = $avatar;
                        $target_data["user_group"] = ucfirst($target_user->getGroup());
                        $target_data["user_name"] = "{$target_user->getFirstname()} {$target_user->getLastname()}";
                        $target_data["user_email"] = $target_user->getEmail();
                    }
                    break;
                case "event":
                case "any":
                    $target_data["target_type"] = "event_id";
                    break;
            }
        }
        return $target_data;
    }

    /**
     * Fetches a structure describing the status of the learning event assessments (based on the given assessment record's ID).
     * This is called by the Distribution Targets model.
     *
     * @param $distribution_id
     * @param $internal_external
     * @param $user_id
     * @param $assessment
     * @param $distribution_target
     * @return array|bool
     */
    public function getLearningEventAssessmentTargets($distribution_id, $internal_external, $user_id, $assessment, $distribution_target) {
        global $translate;
        $all_targets = array();
        // Target type is not learning event
        if ($distribution_target->getTargetType() != "eventtype_id") {
            return false;
        }

        // Assessment does not link to an event id
        if ($assessment->getAssociatedRecordType() != "event_id") {
            return false;
        }

        // Fetch all target records for this assessment
        $assessment_targets = Models_Assessments_AssessmentTarget::fetchAllByDassessmentID($assessment->getID());
        if (!$assessment_targets) {
            return false;
        }

        foreach ($assessment_targets as $adtarget) {
            $deleted_task = Models_Assessments_DeletedTask::fetchRowByADistributionIDAssessorTypeAssessorValueTargetIDDeliveryDate($distribution_id, $internal_external, $user_id, $adtarget->getTargetValue(), $assessment->getDeliveryDate());
            if (!$deleted_task) {
                $target_data = array();
                $target_data["target_record_id"] = $adtarget->getTargetValue();
                $target_data["adtarget_id"] = $distribution_target->getID();

                if ($adtarget->getTargetType() == "proxy_id") {
                    $target_user = $this->getUserByType($adtarget->getTargetValue(), "internal");
                    $target_data["name"] = "{$target_user->getFirstname()} {$target_user->getLastname()}";
                    $target_data["email"] = $target_user->getEmail();
                    $target_data["number"] = $target_user->getNumber();
                } else {
                    $target_data["name"] = "";
                    $target_data["email"] = "";
                    $target_data["number"] = "";
                }

                $target_data["aprogress_id"] = 0;
                $target_data["completed_aprogress_id"] = 0;
                $target_data["completed_attempts"] = 0;
                $target_data["progress"] = array();

                $progress_records = Models_Assessments_Progress::fetchAllByDassessmentIDAssessorTypeAssessorValueTargetRecordID($assessment->getID(), $assessment->getAssessorType(), $assessment->getAssessorValue(), $adtarget->getTargetValue());
                if ($progress_records) {
                    foreach ($progress_records as $progress_record) {
                        $target_data["aprogress_id"] = $progress_record->getID();
                        if (!in_array($progress_record->getProgressValue(), $target_data["progress"])) {
                            $target_data["progress"][] = $progress_record->getProgressValue();
                        }
                        if ($progress_record->getProgressValue() == "complete") {
                            $target_data["completed_aprogress_id"] = $progress_record->getID();
                            if (!isset($target_data["completed_attempts"])) {
                                $target_data["completed_attempts"] = 0;
                            }
                            $target_data["completed_attempts"]++;
                            $all_targets[] = $target_data;
                        } elseif ($progress_record->getProgressValue() == "inprogress") {
                            $target_data["inprogress_aprogress_id"] = $progress_record->getID();
                            $all_targets[] = $target_data;
                        }
                    }
                }
                if (empty($target_data["progress"])) {
                    $target_data["progress"][] = "pending";
                    $all_targets[] = $target_data;
                }
            }
        }
        return $all_targets;
    }

    //-- Public static methods --//

    /**
     * Build a date-sensitive set of formatted timestamp strings. If the start and end dates are the same, only the date for the first one is
     * included in the return value. If both are different, both full timestamps are returned.
     *
     * Returns array with false values on failure.
     *
     * @param int $start_date
     * @param int $end_date
     * @return array
     */
    public static function buildTimeframeStrings($start_date, $end_date) {
        $timeframe_strings = array("timeframe_start" => false, "timeframe_end" => false);
        if ($start_date && $end_date) {
            $ymd_start_date = date("Y-m-d", $start_date);
            $ymd_end_date = date("Y-m-d", $end_date);
            $hms_start_time = date("H:i", $start_date);
            $hms_end_time = date("H:i", $end_date);
            $timeframe_strings["timeframe_start"] = "$ymd_start_date $hms_start_time";
            if ($ymd_start_date == $ymd_end_date) {
                $timeframe_strings["timeframe_end"] = $hms_end_time;
            } else {
                $timeframe_strings["timeframe_end"] = "$ymd_end_date $hms_end_time";
            }
        }
        return $timeframe_strings;
    }

    /**
     * Using related assessment, progress, and deleted task records, build a string that best represents the status of the assessment.
     *
     * If debug_verbose is set true, the string has some additional information appended to it that indicates how the string was dervied.
     *
     * @param Models_Assessments_Assessor $assessment
     * @param Models_Assessments_Progress $progress
     * @param Models_Assessments_DeletedTask $deleted_task
     * @param array Models_Assessments_TaskDeletedReason $deleted_task_reasons
     * @param bool $debug_verbose (Debug flag)
     * @return string
     */
    public static function buildAssessmentProgressString($assessment, $progress, $deleted_task, $deleted_task_reasons, $debug_verbose = false) {
        global $translate;
        $status_string = "";
        if ($deleted_task) {
            $status_string = sprintf($translate->_("Deleted on %s"), strftime("%Y-%m-%d", $deleted_task->getCreatedDate()));
            $status_string .= ($debug_verbose) ? " (by UI) " : "";
            foreach ($deleted_task_reasons as $reason) {
                if ($reason->getID() == $deleted_task->getDeletedReasonID()) {
                    if ($reason->getNotesRequired()) {
                        $status_string .= sprintf("<br/>%s: %s<br/>%s: %s", $translate->_("Reason"), $reason->getDetails(), $translate->_("Note"), $deleted_task->getDeletedReasonNotes());
                    } else {
                        $status_string .= sprintf("<br/>%s: %s", $translate->_("Reason"), $reason->getDetails());
                    }
                }
            }

        } else if ($assessment->getDeletedDate()) {
            if ($assessment->getDeletedDate() == 1) {
                // Assessment was deleted by administrator on an unknown date
                $status_string = $translate->_("Deleted by administrator");
            } else {
                $status_string = sprintf($translate->_("Deleted on %s"), strftime("%Y-%m-%d", $assessment->getDeletedDate()));
                $status_string .= ($debug_verbose) ? " (by assessment record)" : "";
            }

        } else if (!$progress) {
            $status_string = $translate->_("Not started");
            if ($assessment) {
                $status_string .= ($debug_verbose) ? " (Assessment ID: {$assessment->getID()})" : "";
            } else {
                $status_string .= ($debug_verbose) ? " (Assessment ID: None)" : "";
            }

        } else {
            switch ($progress->getProgressValue()) {
                case "inprogress":
                    $status_string = sprintf("%s (%s %s)", $translate->_("In Progress"), $translate->_("Started"), strftime("%Y-%m-%d", $progress->getCreatedDate()));
                    $status_string .= ($debug_verbose) ? " (progress ID: {$progress->getID()})" : "";
                    break;
                case "complete":
                    $status_string = sprintf($translate->_("Completed on %s"), strftime("%Y-%m-%d", $progress->getUpdatedDate()));
                    $status_string .= ($debug_verbose) ? " (progress ID: {$progress->getID()})" : "";
                    break;
                case "cancelled":
                    $status_string = sprintf($translate->_("Deleted on %s"), strftime("%Y-%m-%d", $progress->getUpdatedDate()));
                    $status_string .= ($debug_verbose) ? " (by progress cancellation)" : "";
                    break;
                default: // null
                    break;
            }
        }
        return $status_string;
    }

    //--- Private methods ---//

    /**
     * Insert the contents of $new_array into $proxy_array, preserving and/or overwriting keys.
     *
     * @param $proxy_array
     * @param $new_array
     */
    private function mergeProxyArrays(&$proxy_array, $new_array) {
        foreach ($new_array as $i => $data) {
            $proxy_array[$i] = $data;
        }
    }

    /**
     * For a given user id, fetch the object and store the flattened version in the $proxy_list array.
     *
     * @param array $proxy_list
     * @param int $user_id
     */
    private function addUserDataByUser(&$proxy_list, $user_id) {
        if ($this->isInStorage("proxy", $user_id)) {
            $proxy_list["$user_id-internal"] = $this->fetchFromStorage("proxy", $user_id);
        } else {
            if ($user = Models_User::fetchRowByID($user_id)) {
                $proxy_list["$user_id-internal"] = array(
                    "firstname" => $user->getFirstname(),
                    "lastname" => $user->getLastname(),
                    "proxy_id" => $user->getID(),
                    "email" => $user->getEmail(),
                    "number" => $user->getNumber()
                );
                $this->addToStorage("proxy", $proxy_list["$user_id-internal"], $user_id);
            }
        }
    }

    /**
     * For a given external user id, fetch the external assessor object and store a flattened version of it in the $proxy_list array.
     *
     * @param $proxy_list
     * @param $external_assessor_id
     */
    private function addUserDataByExternalUser(&$proxy_list, $external_assessor_id) {
        global $translate;
        if ($this->isInStorage("external", $external_assessor_id)) {
            $proxy_list["$external_assessor_id-external"] = $this->fetchFromStorage("external", $external_assessor_id);
        } else if ($user = Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($external_assessor_id)) {
            $proxy_list["$external_assessor_id-external"] = array(
                "firstname" => $user->getFirstname(),
                "lastname" => $user->getLastname(),
                "proxy_id" => $user->getID(),
                "email" => $user->getEmail(),
                "number" => $translate->_("External")
            );
            $this->addToStorage("external", $proxy_list["$external_assessor_id-external"], $external_assessor_id);
        }
    }

    /**
     * For a given group id, fetch the members of the group and store the flattened data in the $proxy_list array.
     *
     * @param array $proxy_list
     * @param int $group_id
     */
    private function addUserDataByGroup(&$proxy_list, $group_id) {
        if ($this->isInStorage("group", $group_id)) {
            // If we've already encountered this group, then we already encountered the proxies as well.
            foreach ($this->fetchFromStorage("group", $group_id) as $group_member_id) {
                $proxy_list["$group_member_id-internal"] = $this->fetchFromStorage("proxy", $group_member_id);
            }

        } else {
            // we want to set this element regardless of whether we actually find group members in the subsequent call.
            // An empty array being found prevents subsequent fetches
            $this->addToStorage("group", array(), $group_id);

            // We haven't encountered this group before, but we might have encountered the proxy.
            if ($group = Models_Group_Member::getUsersByGroupID($group_id)) {

                $group_member_ids = array();
                foreach ($group as $user) {
                    $group_member_ids[] = $user->getID(); // we store the ID of the user in the group

                    if ($this->isInStorage("proxy", $user->getID())) {
                        $proxy_list["{$user->getID()}-internal"] = $this->fetchFromStorage("proxy", $user->getID());
                    } else {
                        $proxy_list["{$user->getID()}-internal"] = array(
                            "firstname" => $user->getFirstname(),
                            "lastname" => $user->getLastname(),
                            "proxy_id" => $user->getID(),
                            "email" => $user->getEmail(),
                            "number" => $user->getNumber()
                        );
                        $this->addToStorage("proxy", $proxy_list["{$user->getID()}-internal"], $user->getID());
                    }
                }
                $this->addToStorage("group", $group_member_ids, $group_id);
            }
        }
    }

    /**
     * For a given audience type, fetch all of the proxy IDs contained therein.
     *
     * @param string $audience_type
     * @param int $audience_value
     * @return array
     */
    private function fetchProxyListByAudienceType ($audience_type, $audience_value) {
        $proxy_list = array();
        switch ($audience_type) {
            case "proxy_id":
                // Single proxy id
                $this->addUserDataByUser($proxy_list, $audience_value);
                break;

            case "cohort":
                // Everyone from a cohort or group (both fetched from groups table)
                $this->addUserDataByGroup($proxy_list, $audience_value);
                break;

            case "course_id":
                if ($this->isInStorage("course_audience", $audience_value)) {
                    $course_audiences = $this->fetchFromStorage("course_audience", $audience_value);
                } else {
                    $course_audience_model = new Models_Course_Audience();
                    $course_audiences = $course_audience_model->fetchAllByCourseIDCperiodID($audience_value, $this->distribution->getCperiodID());
                    $this->addToStorage("course_audience", $course_audiences, $audience_value);
                }

                // Everyone in a course
                if ($course_audiences)  {
                    foreach ($course_audiences as $course_audience) {
                        if ($course_audience->getAudienceType() == "proxy_id") {
                            $this->addUserDataByUser($proxy_list, $course_audience->getAudienceValue());

                        } else if ($course_audience->getAudienceType() == "group_id") {
                            $this->addUserDataByGroup($proxy_list, $course_audience->getAudienceValue());
                        }
                    }
                }
                break;

            case "cgroup_id":
                // NOTE: Due to inconsistencies in the front-end, the cgroup_id is not actually used. group_id accomplishes the cgroup_id purpose.
                break;

            case "group_id":
                // Everyone is a small group / course group. As noted above, this should probably be linked via cgroup_id.
                if ($this->isInStorage("course_group_audience", $audience_value)) {
                    $course_group_audience = $this->fetchFromStorage("course_group_audience", $audience_value);
                } else {
                    $course_group_audience = Models_Course_Group_Audience::fetchAllByCGroupID($audience_value);
                    $this->addToStorage("course_group_audience", $course_group_audience, $audience_value);
                }
                // Everyone in a course group
                if ($course_group_audience) {
                    foreach ($course_group_audience as $user) {
                        $this->addUserDataByUser($proxy_list, $user->getProxyID());
                    }
                }
                break;

            case "grad_year":
            case "organisation_id":
                // Not supported
                break;
        }
        return $proxy_list;
    }

    /**
     * Fetch all of the non-auditor course contacts for a given event.
     *
     * @param $event_id
     * @return array
     */
    private function fetchEventTeachers($event_id) {
        $proxy_list = array();
        if ($this->isInStorage("contacts", $event_id)) {
            $contacts = $this->fetchFromStorage("contacts", $event_id);
        } else {
            $contacts = Models_Event_Contacts::fetchAllByEventID($event_id);
            $this->addToStorage("contacts", $contacts, $event_id);
        }
        if ($contacts) {
            foreach ($contacts as $contact) {
                // Currently, anything other than auditors are considered teachers.
                if ($contact->getContactRole() != "auditor") {
                    $this->addUserDataByUser($proxy_list, $contact->getProxyID());
                }
            }
        }
        return $proxy_list;
    }

    /**
     * Build the internal task list where the assessors are the faculty associated with a learning event.
     *
     * @param $distribution_target
     * @param $distribution_assessor
     */
    private function buildTaskListByAssessorsFaculty($distribution_target, $distribution_assessor) {
        $relevant_events = array();
        foreach ($this->event_list as $event_id => $event) {
            if ($event->getEventTypeID() == $distribution_target->getTargetID()) {
                $relevant_events[] = $event_id;
            }
        }
        foreach ($relevant_events as $event_id) {
            if (!isset($this->assessors_proxy_list[$event_id])) {
                $this->assessors_proxy_list[$event_id] = array();
            }
            // The only valid scope is "faculty"
            if ($distribution_assessor->getAssessorScope() == "faculty") {
                $this->assessors_proxy_list[$event_id] = $this->fetchEventTeachers($event_id);
            }
        }
        $this->addToTaskListUsingAssessorProxyList($distribution_target);
    }

    /**
     * Build the internal task list where the assessors are arbitrary proxy IDs.
     *
     * @param $distribution_target
     * @param $distribution_assessor
     */
    private function buildTaskListByAssessorsProxyID($distribution_target, $distribution_assessor) {
        $relevant_events = array();
        foreach ($this->event_list as $event_id => $event) {
            if ($event->getEventTypeID() == $distribution_target->getTargetID()) {
                $relevant_events[] = $event_id;
            }
        }
        foreach ($relevant_events as $event_id) {
            if (!isset($this->assessors_proxy_list[$event_id])) {
                $this->assessors_proxy_list[$event_id] = array();
            }
            $this->addUserDataByUser($this->assessors_proxy_list[$event_id], $distribution_assessor->getAssessorValue());
        }
        $this->addToTaskListUsingAssessorProxyList($distribution_target);
    }

    /**
     * Build the internal task list where the assessors are arbitrary external assessors.
     *
     * @param $distribution_target
     * @param $distribution_assessor
     */
    private function buildTaskListByAssessorsExternal($distribution_target, $distribution_assessor) {
        $relevant_events = array();
        foreach ($this->event_list as $event_id => $event) {
            if ($event->getEventTypeID() == $distribution_target->getTargetID()) {
                $relevant_events[] = $event_id;
            }
        }
        foreach ($relevant_events as $event_id) {
            if (!isset($this->assessors_proxy_list[$event_id])) {
                $this->assessors_proxy_list[$event_id] = array();
            }
            $this->addUserDataByExternalUser($this->assessors_proxy_list[$event_id], $distribution_assessor->getAssessorValue());
        }
        $this->addToTaskListUsingAssessorProxyList($distribution_target);
    }

    /**
     * Build the internal task list where the assessors are the learners associated with a learning event.
     * This can be be either "all_learners" (all of the learners enrolled) or "attended_learners" (only the ones that attended).
     *
     * @param $distribution_target
     * @param $distribution_assessor
     */
    private function buildTaskListByAssessorsLearners($distribution_target, $distribution_assessor) {
        // Build our assessor proxy list based on event scope.
        $relevant_events = array();
        foreach ($this->event_list as $event_id => $event) {
            if ($event->getEventTypeID() == $distribution_target->getTargetID()) {
                $relevant_events[] = $event_id;
            }
        }
        foreach ($relevant_events as $event_id) {
            if (!isset($this->assessors_proxy_list[$event_id])) {
                $this->assessors_proxy_list[$event_id] = array();
            }
            if ($distribution_assessor->getAssessorScope() == "all_learners") {
                // In the case where we want all of the learners for an event type, we have to fetch the event audience, and expand that to get down to all of the proxies
                // There can be multiple audiences for a single event

                if ($this->isInStorage("event_audience", $event_id)) {
                    $audiences = $this->fetchFromStorage("event_audience", $event_id);
                } else {
                    $audiences = Models_Event_Audience::fetchAllByEventID($event_id);
                    $this->addToStorage("event_audience", $audiences, $event_id);
                }
                if ($audiences) {
                    foreach ($audiences as $audience) {
                        // Add proxies based on the audience type.
                        $this->mergeProxyArrays(
                            $this->assessors_proxy_list[$event_id],
                            $this->fetchProxyListByAudienceType($audience->getAudienceType(), $audience->getAudienceValue())
                        );
                    }
                }

            } else if ($distribution_assessor->getAssessorScope() == "attended_learners") {
                // Attendance is a list of proxies
                if ($this->isInStorage("event_attendance", $event_id)) {
                    $attended = $this->fetchFromStorage("event_attendance", $event_id);
                } else {
                    $attended = Models_Event_Attendance::fetchAllByPrimaryKeyByEventID($event_id);
                    $this->addToStorage("event_attendance", $attended, $event_id);
                }
                if ($attended) {
                    foreach ($attended as $attendee) {
                        // Add proxies only for simple proxy IDs. The fetchProxyListByAudienceType() function will do the job, although it is meant to run using the Models_Event_Audience model.
                        // Since this is just a list of proxies, we're OK to do this.
                        $this->mergeProxyArrays(
                            $this->assessors_proxy_list[$event_id],
                            $this->fetchProxyListByAudienceType("proxy_id", $attendee->getProxyID())
                        );
                    }
                }
            }
        }
        // Now that the assessors_proxy_list is populated, we can build the task list.
        $this->addToTaskListUsingAssessorProxyList($distribution_target);
    }

    /**
     * Add entries to the internal task list using the internal lists of proxy IDs (already populated).
     *
     * @param Models_Assessments_Distribution_Target $distribution_target
     */
    private function addToTaskListUsingAssessorProxyList($distribution_target) {

        // For all of the proxies we found for each event that had related learners
        foreach ($this->assessors_proxy_list as $event_id => $proxy_list) {

            // Get the targets for this event.
            $target_list = $this->buildTargetsProxyListByEvent($event_id, $distribution_target);

            foreach ($proxy_list as $proxy_id => $user) {
                // In the future, we can set the delivery date here by using an offset, similar to $this->calculateDateByOffset()
                // For now, the end date is always the end of the event + 1 day.
                $delivery_date = $this->event_list[$event_id]->getEventFinish() + 86400;
                $release_date = (is_null($this->distribution->getReleaseDate()) ? 0 : (int) $this->distribution->getReleaseDate());

                // Add this would-be task to the internal task list
                $this->addToTaskList(
                    $this->getDistributionID(),
                    $delivery_date, // This date determines whether this task "should_exist" or not. Release date is taken into account, if one exists.
                    $release_date,
                    $this->event_list[$event_id]->getEventStart(),
                    $this->event_list[$event_id]->getEventFinish(),
                    $target_list,
                    $this->assessors_proxy_list[$event_id],
                    "learning_event_assessment",
                    "event_id",
                    $event_id,
                    null,
                    false,
                    null,
                    0,
                    0,
                    $distribution_target->getTargetType()
                );
            }
        }
    }

    /**
     * Get a list of target proxy IDs by event, for the given target type. This applies to learners and faculty; no target records are required for the event itself.
     *
     * NOTE: $distribution_target->getTargetScope() is always == "self"
     *
     * @param $event_id
     * @param $distribution_target
     * @return array
     */
    private function buildTargetsProxyListByEvent($event_id, $distribution_target) {
        $proxy_list = array();
        if ($distribution_target->getTargetType() == "eventtype_id") {
            switch ($distribution_target->getTargetRole()) {
                // Target is all learners enrolled in event (whether we have a record of their attendance or not)
                // Target record target_value will be a proxy ID
                case "learner":
                    if ($this->isInStorage("eventtype_audience_by_learner", $event_id)) {
                        $audiences = $this->fetchFromStorage("eventtype_audience_by_learner", $event_id);
                    } else {
                        $audiences = Models_Event_Audience::fetchAllByEventID($event_id);
                        $this->addToStorage("eventtype_audience_by_learner", $audiences, $event_id);
                    }
                    if ($audiences) {
                        foreach ($audiences as $audience) {
                            $t_list = $this->fetchProxyListByAudienceType($audience->getAudienceType(), $audience->getAudienceValue());
                            $this->mergeProxyArrays($proxy_list, $t_list);
                        }
                    }
                    break;

                // Target is all faculty teaching the event
                // Target record target_value will be a proxy ID
                case "faculty":
                    $contacts_proxies = $this->fetchEventTeachers($event_id); // fetch event teachers uses storage mechanism
                    $this->mergeProxyArrays($proxy_list, $contacts_proxies);
                    break;

                // Target is the event itself
                // Target record target_value will be an event ID
                case "event":
                case "any":
                default:
                    // Add the event record as the target.
                    $proxy_list["$event_id-eventtype"] = $event_id;
                    break;
            }
        }
        return $proxy_list;
    }
}