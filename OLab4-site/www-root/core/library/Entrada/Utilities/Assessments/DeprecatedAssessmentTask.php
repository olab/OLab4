<?php

class Entrada_Utilities_Assessments_DeprecatedAssessmentTask extends Entrada_Utilities_Assessments_Base {
    protected $dassessment_id, $type, $title, $description, $details, $assessment_type, $event_details, $event_timeframe_start, $event_timeframe_end, $schedule_details, $adistribution_id, $distribution_deleted_date, $url, $assessor, $assessor_value, $group, $role, $start_date, $end_date, $delivery_date, $rotation_start_date, $rotation_end_date, $targets, $max_overall_attempts, $max_individual_attempts, $completed_attempts, $delegation_completed, $delegation_completed_date, $delegated_by, $target_id, $aprogress_id, $cperiod_id, $assessment_sub_type, $assessor_type, $task_type, $completed_date, $target_names, $target_info;

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getDassessmentID() {
        return $this->dassessment_id;
    }

    public function getType() {
        return $this->type;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getDetails() {
        return $this->details;
    }

    public function getAssessmentType() {
        return $this->assessment_type;
    }

    public function getEventDetails() {
        return $this->event_details;
    }

    public function getEventTimeframeStart() {
        return $this->event_timeframe_start;
    }

    public function getEventTimeframeEnd() {
        return $this->event_timeframe_end;
    }

    public function getScheduleDetails() {
        return $this->schedule_details;
    }

    public function getDistributionID() {
        return $this->adistribution_id;
    }

    public function getDistributionDeletedDate() {
        return $this->distribution_deleted_date;
    }

    public function getUrl() {
        return $this->url;
    }

    public function setUrl($url) {
        $this->url = $url;
    }

    public function getCPeriodID() {
        return $this->cperiod_id;
    }

    public function getAssessor() {
        return $this->assessor;
    }

    public function getAssessorValue() {
        return $this->assessor_value;
    }

    public function getAssessorType() {
        return $this->assessor_type;
    }

    public function getTargetID() {
        return $this->target_id;
    }

    public function getGroup() {
        return $this->group;
    }

    public function getRole() {
        return $this->role;
    }

    public function getStartDate() {
        return $this->start_date;
    }

    public function getEndDate() {
        return $this->end_date;
    }

    public function getDeliveryDate() {
        return $this->delivery_date;
    }

    public function getRotationStartDate() {
        return $this->rotation_start_date;
    }

    public function getRotationEndDate() {
        return $this->rotation_end_date;
    }

    public function getTargets() {
        return $this->targets;
    }

    public function setTargets($targets) {
        $this->targets = $targets;
    }

    public function getMaxOverallAttempts() {
        return $this->max_overall_attempts;
    }

    public function getMaxIndividualAttempts() {
        return $this->max_individual_attempts;
    }

    public function getCompletedAttempts() {
        return $this->completed_attempts;
    }

    public function getDelegationCompleted() {
        return $this->delegation_completed;
    }

    public function getDelegationCompletedDate() {
        return $this->delegation_completed_date;
    }

    public function getDelegatedBy() {
        return $this->delegated_by;
    }

    public function getProgressID() {
        return $this->aprogress_id;
    }

    public function getAssessmentSubType(){
        return $this->assessment_sub_type;
    }

    public function getTaskType(){
        return $this->task_type;
    }

    public function getTotalTargets() {
        return $this->targets["total"];
    }

    public function getTargetsPending() {
        return $this->targets["pending"];
    }

    public function getTargetsInprogress() {
        return $this->targets["inprogress"];
    }

    public function getTargetsComplete() {
        return $this->targets["complete"];
    }

    public function getTargetNamesPending() {
        if (isset($this->target_names["pending"]) && !empty($this->target_names["pending"])) {
            return "Pending target(s): " . $this->target_names["pending"];
        } else {
            return "No pending target(s) found.";
        }
    }

    public function getTargetNamesInprogress() {
        if (isset($this->target_names["inprogress"]) && !empty($this->target_names["inprogress"])) {
            return "Inprogress target(s): " . $this->target_names["inprogress"];
        } else {
            return "No inprogress target(s) found.";
        }
    }

    public function getTargetNamesComplete($prefix = true) {
        if ($prefix) {
            if (isset($this->target_names["complete"]) && !empty($this->target_names["complete"])) {
                return "Target(s) complete: " . $this->target_names["complete"];
            } else {
                return "No target(s) complete found.";
            }
        } else {
            return $this->target_names["complete"];
        }
    }

    public function getTargetNamesPotential() {
        if (isset($this->target_names["potential"]) && !empty($this->target_names["potential"])) {
            return "Potential target(s): " . $this->target_names["potential"];
        } else {
            return "No potential target(s) found.";
        }
    }

    public function getCompletedDate() {
        return $this->completed_date;
    }

    public function getAtargetIDList() {
        $atarget_id_list = array();

        if (isset($this->target_info) && !empty($this->target_info)) {
            foreach ($this->target_info as $key => $target) {
                if (is_array($target)) {
                    if (isset($target["atarget_id"])) {
                        $atarget_id_list[] = $target["atarget_id"];
                    }
                } else {
                    if ($key == "atarget_id") {
                        $atarget_id_list[] = $target;
                    }
                }

            }
        }
        return implode(",", $atarget_id_list);
    }

    public static function getAssessmentProgressOnUser($target_record_id = null, $organisation_id = null, $current_section = "assessments", $approval_status = "approved", $filters = array(), $search_value = null, $start_date = null, $end_date = null, $limit = 0, $offset = 0, $task_type = null) {
        global $db;
        $tasks = array(
            "inprogress" => array(),
            "complete" => array()
        );

        $course_id_list = Models_Course::getActiveUserCoursesIDList();

        $AND_course_in = ($current_section == "assessments" || empty($course_id_list) ? " " : "  AND e.`course_id` IN (" . implode(",", $course_id_list) . ") ");
        $AND_cperiod_in = $AND_course_filter_in = $AND_title_like = $AND_date_greater = $AND_date_less = $LIMIT = $OFFSET = $AND_release_status = $AND_task_type = "";

        if ($approval_status) {
            $AND_release_status = "AND (f.`approval_status` = '$approval_status' OR g.`proxy_id` IS NULL)";
        }

        if ($filters) {
            if (array_key_exists("cperiod", $filters)) {
                $AND_cperiod_in = " AND e.`cperiod_id` IN (" . implode(",", array_keys($filters["cperiod"])) . ") ";
            }

            if (array_key_exists("program", $filters)) {
                $AND_course_filter_in = "  AND e.`course_id` IN (" . implode(",", array_keys($filters["program"])) . ") ";
            }
        }

        if (!is_null($search_value) && $search_value != "") {
            $AND_title_like = "     AND e.`title` LIKE (". $db->qstr("%". $search_value ."%") .") ";
        }

        if (!is_null($start_date) && $start_date != "") {
            $AND_date_greater = "   AND b.`delivery_date` >= ". $db->qstr($start_date);
        }

        if (!is_null($end_date) && $end_date != "") {
            $AND_date_less = "      AND b.`delivery_date` <= ". $db->qstr($end_date);
        }

        if ($limit) {
            $LIMIT = " LIMIT $limit";
        }

        if ($offset) {
            $OFFSET = " OFFSET $offset";
        }

        if (!is_null($task_type) && $task_type != "" && ($task_type === "assessment" || $task_type === "evaluation")) {
            $AND_task_type = " AND e.`assessment_type` = " . $db->qstr($task_type);
        }

        $query = "  SELECT  a.*, b.`dassessment_id`, b.`delivery_date`, b.`rotation_start_date`, b.`rotation_end_date`, i.`atarget_id`,
                            d.`group`, d.`role`, c.`firstname` AS `internal_first_name`, c.`lastname` AS `internal_last_name`, c.`email` AS `internal_email`, j.`firstname` AS `external_first_name`, j.`lastname` AS `external_last_name`, j.`email` AS `external_email`, e.`organisation_id` 
                    FROM `cbl_assessment_progress` AS a                    
                    JOIN `cbl_distribution_assessments` AS b
                    ON a.`dassessment_id` = b.`dassessment_id`
                    LEFT JOIN `" . AUTH_DATABASE . "`.`user_data` AS c
                    ON c.`id` = a.`assessor_value`
                    LEFT JOIN `" . AUTH_DATABASE . "`.`user_access` AS d
                    ON d.`user_id` = c.`id`
                    LEFT JOIN `cbl_external_assessors` AS j
                    ON j.`eassessor_id` = a.`assessor_value` 
                    JOIN `cbl_assessment_distributions` AS e
                    ON a.`adistribution_id` = e.`adistribution_id`
                    LEFT JOIN `cbl_assessment_progress_approvals` AS f
                    ON a.`aprogress_id` = f.`aprogress_id`
                    LEFT JOIN `cbl_assessment_distribution_approvers` AS g
                    ON a.`adistribution_id` = g.`adistribution_id`              
                    JOIN `courses` AS h
                    ON e.`course_id` = h.`course_id`      
                    JOIN `cbl_distribution_assessment_targets` AS i
                    ON i.`dassessment_id` = a.`dassessment_id`    
                    WHERE a.`target_record_id` = ?
                    AND (a.`progress_value` = 'complete' OR (a.`progress_value` = 'inprogress' AND i.`deleted_date` IS NULL))
                    AND e.`organisation_id` = ?
                    AND i.`target_value` = ? 
                    AND i.`target_type` = 'proxy_id'
                    AND e.`visibility_status` = 'visible'

                    $AND_release_status 
                    $AND_course_in
                    $AND_course_filter_in
                    $AND_cperiod_in
                    $AND_title_like
                    $AND_date_greater
                    $AND_date_less
                    $AND_task_type

                    GROUP BY a.`aprogress_id`
                    ORDER BY b.`delivery_date` DESC, b.`rotation_start_date` DESC, b.`rotation_end_date` DESC
                    $LIMIT $OFFSET
                    ";

        $query_tasks = $db->GetAll($query, array($target_record_id, $organisation_id, $target_record_id));
        $target_user = Models_User::fetchRowByID($target_record_id);

        if ($query_tasks && $target_user) {
            foreach ($query_tasks as $task) {

                $distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($task["adistribution_id"]);
                if ($distribution) {

                    $progress_record = Models_Assessments_Progress::fetchRowByID($task["aprogress_id"]);
                    if ($progress_record) {

                        $schedule_string = false;
                        $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($distribution->getID());
                        if ($distribution_schedule) {
                            $schedule_record = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID());
                            if ($schedule_record) {
                                // Attempt to use new logic for schedule fetching based on rotation/block start and end dates.
                                if ($task["rotation_start_date"] && $task["rotation_end_date"]) {
                                    $schedule_string = parent::getConcatenatedBlockString($task["dassessment_id"], $schedule_record, $task["rotation_start_date"], $task["rotation_end_date"], $distribution->getOrganisationID());
                                }
                            }
                        }

                        $task_completed_date = NULL;
                        if ($progress_record->getProgressValue() == "complete" && !is_null($progress_record->getUpdatedDate())) {
                            $task_completed_date = $progress_record->getUpdatedDate();
                        }

                        $assessment_sub_type = "rotation_schedule";
                        $assessment = Models_Assessments_Assessor::fetchRowByID($task["dassessment_id"]);

                        if ($assessment && $assessment->getAssociatedRecordType() == "event_id") {
                            $assessment_sub_type = "learning_event";
                        } else if (is_null($task["rotation_start_date"]) || $task["rotation_start_date"] == "" || !$task["rotation_end_date"]) {
                            $assessment_sub_type = "date_range";
                        }

                        $assessment_api = new Entrada_Assessments_Assessment(array("limit_dataset" => array("assessment")));

                        $tasks[$task["progress_value"]][] = new Entrada_Utilities_Assessments_DeprecatedAssessmentTask(array(
                            "dassessment_id" => $task["dassessment_id"],
                            "type" => "assessment",
                            "assessment_sub_type" => $assessment_sub_type,
                            "title" => $distribution->getTitle(),
                            "description" => ($distribution->getDescription() ? $distribution->getDescription() : "No details provided."),
                            "url" => $assessment_api->getAssessmentURL(null, null, false, $task["dassessment_id"], $progress_record->getID()) . "&atarget_id=" . $task["atarget_id"],
                            "start_date" => $distribution->getStartDate(),
                            "end_date" => $distribution->getEndDate(),
                            "delivery_date" => ($task["delivery_date"] ? $task["delivery_date"] : false),
                            "rotation_start_date" => $task["rotation_start_date"],
                            "rotation_end_date" => $task["rotation_end_date"],
                            "schedule_details" => ($schedule_string ? $schedule_string : ""),
                            "adistribution_id" => $distribution->getID(),
                            "assessor" => ($task["assessor_type"] == "external" ? $task["external_first_name"] . " " . $task["external_last_name"] : $task["internal_first_name"] . " " . $task["internal_last_name"]),
                            "assessor_type" => $task["assessor_type"],
                            "assessor_value" => $task["assessor_value"],
                            "group" => ($task["assessor_type"] == "external" ? "External" : $task["group"]),
                            "role" => ($task["assessor_type"] == "external" ? "External" : $task["role"]),
                            "cperiod_id" => $distribution->getCperiodID(),
                            "aprogress_id" => $progress_record->getID(),
                            "completed_date" => $task_completed_date,
                            "target_names" => array("complete" => $target_user->getFullname(false)),
                            "target_info" => array(array("name" => $target_user->getFullname(false), "proxy_id" => $progress_record->getTargetRecordID(), "progress" => array($progress_record->getProgressValue()), "aprogress_id" => $progress_record->getID())),
                            "target_type" => "proxy_id",
                            "target_id" => $target_record_id,
                        ));
                    }
                }
            }
        }

        return $tasks;
    }

    public static function getAllTasks($proxy_id, $current_section = "assessments", $is_external = false, $exclude_completed = false, $limit = 0, $offset = 0) {
        $tasks = array();

        $assessment_tasks = Entrada_Utilities_Assessments_DeprecatedAssessmentTask::getAllAssessmentTasksByProxyID($proxy_id, $current_section, $is_external, array(), null, null, null, $exclude_completed, $limit, $offset);
        if ($assessment_tasks) {
            $tasks = array_merge($tasks, $assessment_tasks);
        }

        $delegation_tasks = Entrada_Utilities_Assessments_DeprecatedAssessmentTask::getAllDelegationTasksByProxyID($proxy_id, $current_section, array(), null, null, null, $exclude_completed, $limit, $offset);
        if ($delegation_tasks) {
            $tasks = array_merge($tasks, $delegation_tasks);
        }

        $approver_tasks = Entrada_Utilities_Assessments_DeprecatedAssessmentTask::getAllApproverTasksByProxyID($proxy_id, $current_section, array(), null, null, null, $exclude_completed, $limit, $offset);
        if ($approver_tasks) {
            $tasks = array_merge($tasks, $approver_tasks);
        }

        return $tasks;
    }

    private static function getAllAssessmentTasksByProxyID($proxy_id, $current_section = "assessments", $is_external = false, $filters = array(), $search_value = null, $start_date = null, $end_date = null, $exclude_completed = false, $limit = 0, $offset = 0) {
        global $ENTRADA_USER;
        global $translate;

        $tasks = array();
        $assessment_tasks = Models_Assessments_Assessor::fetchAllByAssessorValue($proxy_id, $current_section, $is_external, $filters, $search_value, $start_date, $end_date, $exclude_completed, $limit, $offset);
        if ($assessment_tasks) {
            foreach ($assessment_tasks as $assessment) {

                $schedule_string = "";
                $event_string = "";
                $target_type_singular = "";
                $target_type_plural = "";
                $delegator_name = "";
                $event_timeframe_start_string = "";
                $event_timeframe_end_string = "";
                $assessment_sub_type = "rotation_schedule";

                $distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($assessment->getAdistributionID());
                if (($distribution && $distribution->getVisibilityStatus() == "visible")) {

                    $assessments_api = new Entrada_Assessments_Assessment(array(
                        "actor_proxy_id" => $proxy_id,
                        "dassessment_id" => $assessment->getID()
                    ));
                    $assessment_data = $assessments_api->fetchAssessmentData();
                    $assessment_target_list = $assessments_api->getAssessmentTargetList();

                    if ($assessment_data && $assessment_target_list) {
                        $distribution_delegator = Models_Assessments_Distribution_Delegator::fetchRowByDistributionID($distribution->getID());
                        if ($distribution_delegator) {
                            if ($distribution_delegator->getDelegatorType() == "proxy_id") {
                                $delegator_user = Models_User::fetchRowByID($distribution_delegator->getDelegatorID());
                                if ($delegator_user) {
                                    $delegator_name = $delegator_user->getFullname(false);
                                }
                            }
                        }
                        $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($distribution->getID());
                        if ($distribution_schedule) {
                            $schedule_record = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID());
                            if ($schedule_record) {
                                if ($assessment->getStartDate() && $assessment->getEndDate()) {
                                    $schedule_string = parent::getConcatenatedBlockString($assessment->getID(), $schedule_record, $assessment->getStartDate(), $assessment->getEndDate(), $distribution->getOrganisationID());
                                }
                            }
                        }

                        if ($assessment->getAssociatedRecordType() == "event_id") {
                            $associated_event = Models_Event::get($assessment->getAssociatedRecordID());
                            $event_time_strings = "";

                            if ($associated_event) {
                                $event_string = $associated_event->getEventTitle();
                                $event_time_strings = Entrada_Utilities_Assessments_DistributionLearningEvent::buildTimeframeStrings($associated_event->getEventStart(), $associated_event->getEventFinish());
                            }

                            if ($event_time_strings != "" && !empty($event_time_strings)) {
                                $event_timeframe_start_string = $event_time_strings["timeframe_start"];
                                $event_timeframe_end_string = $event_time_strings["timeframe_end"];
                                $assessment_sub_type = "learning_event";
                            }
                        } else if (is_null($assessment->getRotationStartDate()) || $assessment->getRotationStartDate() == "" || !$assessment->getRotationStartDate()) {
                            $assessment_sub_type = "date_range";
                        }

                        $distribution_targets = Models_Assessments_Distribution_Target::fetchAllByDistributionID($assessment->getAdistributionID());
                        if ($distribution_targets) {
                            foreach ($distribution_targets as $distribution_target) {
                                if ($distribution_target->getTargetType() == "learner") {
                                    $target_type_singular = $translate->_("learner");
                                    $target_type_plural = $translate->_("learners");
                                } elseif ($distribution_target->getTargetType() == $translate->_("faculty")) {
                                    $target_type_singular = $translate->_("faculty member");
                                    $target_type_plural = $translate->_("faculty members");
                                } elseif ($distribution_target->getTargetType() == "self") {
                                    $target_type_singular = $translate->_("self");
                                    $target_type_plural = $translate->_("self");
                                } elseif ($distribution_target->getTargetType() == "eventtype_id") {
                                    $target_type_singular = $translate->_("learning event");
                                    $target_type_plural = $translate->_("learning events");
                                }
                                if (!$target_type_singular && $distribution_target->getTargetType() == "schedule_id" && (in_array($distribution_target->getTargetScope(), array("self", "children")))) {
                                    $target_type_singular = $translate->_("rotation");
                                    $target_type_plural = $translate->_("blocks");
                                }
                            }
                            if (!isset($target_type_singular) || !$target_type_singular) {
                                $target_type_singular = $translate->_("individual");
                                $target_type_plural = $translate->_("individuals");
                            }
                        }

                        $target_id = NULL;
                        $progress_id = 0;
                        $pending_target_names = array();
                        $inprogress_target_names = array();
                        $complete_target_names = array();
                        $targets_pending = $assessments_api->getCountUniqueTargetsPending();
                        $targets_inprogress = $assessments_api->getCountUniqueTargetsInProgress();
                        $targets_complete = $assessments_api->getCountUniqueTargetsComplete();
                        $completed_attempts = 0;

                        if ($distribution->getSubmittableByTarget()) {
                            $max_individual_attempts = $distribution->getMaxSubmittable();
                            $max_overall_attempts = ($max_individual_attempts * (@count($assessment_data["targets"])));
                        } else {
                            if ($distribution->getRepeatTargets()) {
                                $max_individual_attempts = $distribution->getMaxSubmittable();
                            } else {
                                $max_individual_attempts = 1;
                            }
                            $max_overall_attempts = $distribution->getMaxSubmittable();
                        }

                        foreach ($assessment_target_list as $target) {
                            if ($target["counts"]["pending"]) {
                                $pending_target_names[] = $target["name"];
                            }
                            if ($target["counts"]["inprogress"]) {
                                $inprogress_target_names[] = $target["name"];
                            }
                            if ($target["counts"]["complete"]) {
                                $completed_attempts += $target["counts"]["complete"];
                                $complete_target_names[] = $target["name"];
                            }
                        }

                        if (count($assessment_data["targets"]) > 1) {
                            $url = $assessments_api->getAssessmentURL(null, null, false, $assessment->getID()) . "&section=targets";


                            $completed_target_ctr = 0;
                            foreach ($assessment_target_list as $target) {
                                if (!is_null($target["complete_aprogress_id"])) {
                                    $completed_target_ctr++;
                                    if ($target["complete_aprogress_id"] > $progress_id) {
                                        $progress_id = $target["complete_aprogress_id"];
                                    }
                                }
                            }

                            if ($completed_target_ctr != count($assessment_data["targets"])) {
                                $progress_id = 0;
                            }
                        } else {
                            $first_target = $assessment_target_list[0];
                            $url = $assessments_api->getAssessmentURL(null, null, false, $assessment->getID()) . "&atarget_id=" . $first_target["atarget_id"];
                            $target_id = $first_target["target_record_id"];
                            $progress_id = ($first_target["complete_aprogress_id"] ? $first_target["complete_aprogress_id"] : $first_target["inprogress_aprogress_id"]);
                        }

                        $task_completed_date = NULL;
                        if ($progress_id) {
                            $task_progress = Models_Assessments_Progress::fetchRowByID($progress_id);
                            if ($task_progress && $task_progress->getProgressValue() == "complete" && !is_null($task_progress->getUpdatedDate())) {
                                $task_completed_date = $task_progress->getUpdatedDate();
                            }
                        }

                        if ($target_type_singular == "self") {
                            $details_string = ($max_overall_attempts - $completed_attempts) . $translate->_(" self assessment attempts available for completion.");
                        } else {
                            if (@count($assessment_data["targets"]) > 1) {
                                if ($distribution->getSubmittableByTarget()) {
                                    $details_string = $max_individual_attempts . $translate->_(" attempts available for completion for each ") . $target_type_singular . ". " . $completed_attempts . $translate->_(" attempts completed of the ") . $max_overall_attempts . $translate->_(" total attempts available for ") . count($assessment_data["targets"]) . " " . $target_type_plural . ".";
                                } else {
                                    $details_string = $completed_attempts . $translate->_(" attempts completed of the ") . $max_overall_attempts . $translate->_(" total attempts available for ") . count($assessment_data["targets"]) . " " . $target_type_plural . ".";
                                }
                            } else {
                                $details_string = $completed_attempts . $translate->_(" attempts completed of the ") . $max_overall_attempts . $translate->_(" total attempts available for a single ") . $target_type_singular . ".";
                            }
                        }


                        $tasks[] = new Entrada_Utilities_Assessments_DeprecatedAssessmentTask(array(
                            "dassessment_id" => $assessment->getID(),
                            "published" => $assessment->getPublished(),
                            "type" => "assessment",
                            "title" => $distribution->getTitle(),
                            "description" => ($distribution->getDescription() ? $distribution->getDescription() : $translate->_("No details provided.")),
                            "details" => ($details_string ? $details_string : $translate->_("No details provided.")),
                            "event_details" => $event_string ? $event_string : "",
                            "schedule_details" => ($schedule_string ? $schedule_string : ""),
                            "url" => $url,
                            "target_id" => $target_id,
                            "assessor" => $assessment_data["assessor"]["full_name"],
                            "assessor_type" => $assessment->getAssessorType(),
                            "assessor_value" => $assessment->getAssessorValue(),
                            "start_date" => $distribution->getStartDate(),
                            "end_date" => $distribution->getEndDate(),
                            "event_timeframe_start" => $event_timeframe_start_string,
                            "event_timeframe_end" => $event_timeframe_end_string,
                            "rotation_start_date" => $assessment->getRotationStartDate(),
                            "rotation_end_date" => $assessment->getRotationEndDate(),
                            "delivery_date" => ($assessment->getDeliveryDate() ? $assessment->getDeliveryDate() : false),
                            "adistribution_id" => $distribution->getID(),
                            "distribution_deleted_date" => $distribution->getDeletedDate(),
                            "delegated_by" => $delegator_name,
                            "targets" => array(
                                "total" => count($assessment_data["targets"]),
                                "pending" => (isset($targets_pending) ? $targets_pending : 0),
                                "inprogress" => (isset($targets_inprogress) ? $targets_inprogress : 0),
                                "complete" => (isset($targets_complete) ? $targets_complete : 0)
                            ),
                            "max_overall_attempts" => (isset($max_overall_attempts) && $max_overall_attempts ? $max_overall_attempts : 1),
                            "max_individual_attempts" => (isset($max_individual_attempts) && $max_individual_attempts ? $max_individual_attempts : 1),
                            "completed_attempts" => (isset($completed_attempts) && $completed_attempts ? $completed_attempts : 0),
                            "assessment_sub_type" => $assessment_sub_type,
                            "aprogress_id" => $progress_id,
                            "completed_date" => $task_completed_date,
                            "target_names" => array("pending" => implode(", ", $pending_target_names), "inprogress" => implode(", ", $inprogress_target_names), "complete" => implode(", ", $complete_target_names)),
                            "target_info" => $assessment_target_list
                        ));

                    }
                } else {
                    $assessments_api = new Entrada_Assessments_Assessment(array(
                        "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                        "dassessment_id" => $assessment->getID()
                    ));
                    $assessment_data = $assessments_api->fetchAssessmentData();
                    $progress = count($assessment_data["progress"]) ? reset($assessment_data["progress"]) : null;

                    if ($assessment_data) {
                        $form_model = new Models_Assessments_Form();
                        $form = $form_model->fetchRowByID($assessment->getFormID());

                        $target = array_shift($assessment_data["targets"]);

                        if ($form) {
                            $form_type_model = new Models_Assessments_Form_Type();
                            $form_type = $form_type_model->fetchRowByFormTypeIDOrganisationID($form->getFormTypeID(), $ENTRADA_USER->getActiveOrganisation());
                            $tasks[] = new Entrada_Utilities_Assessments_DeprecatedAssessmentTask(array(
                                "dassessment_id" => $assessment->getID(),
                                "published" => $assessment->getPublished(),
                                "type" => "assessment",
                                "title" => html_encode($form->getTitle() ? $form->getTitle() : "N/A"),
                                "description" => "",
                                "details" => html_encode($form_type->getTitle() . $translate->_(" Triggered by ") . $target["target_name"]),
                                "event_details" => "",
                                "schedule_details" => html_encode(($form_type ? $form_type->getTitle() : "N/A")),
                                "url" => ENTRADA_URL . "/assessments/assessment?dassessment_id=" . $assessment->getID(),
                                "target_id" => "",
                                "assessor" => "",
                                "assessor_type" => $assessment->getAssessorType(),
                                "assessor_value" => $assessment->getAssessorValue(),
                                "start_date" => $assessment->getCreatedDate(),
                                "end_date" => "",
                                "event_timeframe_start" => "",
                                "event_timeframe_end" => "",
                                "rotation_start_date" => "",
                                "rotation_end_date" => "",
                                "delivery_date" => $assessment->getCreatedDate(),
                                "adistribution_id" => "",
                                "distribution_deleted_date" => "",
                                "delegated_by" => "",
                                "targets" => array(
                                    "total" => "",
                                    "pending" => ($progress==null) ? 1 : 0,
                                    "inprogress" => (($progress && $progress["progress_value"]) == "inprogress" ? 1 : 0),
                                    "complete" => (($progress && $progress["progress_value"]) == "complete" ? 1 : 0),
                                ),
                                "max_overall_attempts" => "1",
                                "max_individual_attempts" => "1",
                                "completed_attempts" => "0",
                                "assessment_sub_type" => "",
                                "aprogress_id" => "",
                                "completed_date" => (($progress && $progress["progress_value"] == "complete") ? ($progress["updated_date"] ? $progress["updated_date"] : $progress["created_date"]) : NULL),
                                "target_names" => array(
                                    "pending" => ((!$progress || $progress["progress_value"] == "pending") ? $target["target_name"] : ""),
                                    "inprogress" => (($progress && $progress["progress_value"]) == "inprogress" ? $target["target_name"] : ""),
                                    "complete" => (($progress && $progress["progress_value"]) == "complete" ? $target["target_name"] : "")
                                ),
                                "target_info" => array(
                                    "name" => $target["target_name"],
                                    "atarget_id" => $target["atarget_id"],
                                    "target_type" => $target["target_type"],
                                    "target_record_id" => $target["target_value"]
                                )
                            ));
                        }
                    }
                }
            }
        }
        return $tasks;
    }

    private static function getAllDelegationTasksByProxyID($proxy_id, $current_section = "assessments", $filters = array(), $search_value = null, $start_date = null, $end_date = null, $exclude_complete = false, $limit = 0, $offset = 0) {
        global $db;
        $tasks = array();

        $course_id_list = Models_Course::getActiveUserCoursesIDList();

        $AND_course_in = ($current_section == "assessments" || empty($course_id_list) ? " " : "  AND b.`course_id` IN (" . implode(",", $course_id_list) . ") ");
        $AND_cperiod_in = "";
        $AND_course_filter_in = "";
        $AND_title_like = "";
        $AND_date_greater = "";
        $AND_date_less = "";
        $AND_not_complete = "";
        $LIMIT = "";
        $OFFSET = "";

        if ($exclude_complete) {
            $AND_not_complete = "   AND a.`completed_date` IS NULL ";
        }

        if ($filters) {
            if (array_key_exists("cperiod", $filters)) {
                $AND_cperiod_in = " AND b.`cperiod_id` IN (" . implode(",", array_keys($filters["cperiod"])) . ") ";
            }

            if (array_key_exists("program", $filters)) {
                $AND_course_filter_in = "  AND b.`course_id` IN (" . implode(",", array_keys($filters["program"])) . ") ";
            }
        }

        if ($search_value != "" && $search_value != null) {
            $AND_title_like = "     AND b.`title` LIKE (". $db->qstr("%". $search_value ."%") .") ";
        }

        if ($start_date != "" && $start_date != null) {
            $AND_date_greater = "   AND a.`delivery_date` >= ". $db->qstr($start_date) . "";
        }

        if ($end_date != "" && $end_date != null) {
            $AND_date_less = "      AND a.`delivery_date` <= ". $db->qstr($end_date) . "";
        }

        if ($limit) {
            $LIMIT = " LIMIT $limit";
        }

        if ($offset) {
            $OFFSET = " OFFSET $offset";
        }

        $query = "  SELECT      a.`addelegation_id`, a.`adistribution_id`, b.`title`, a.`completed_date`, a.`completed_date`, a.`start_date`, a.`end_date`, a.`delivery_date`
                    FROM        `cbl_assessment_distribution_delegations` AS a                    
                    INNER JOIN   `cbl_assessment_distributions`            AS b  
                    ON          b.`adistribution_id`  = a.`adistribution_id`                    
                    INNER JOIN   `cbl_assessment_distribution_delegators`  AS c  
                    ON          c.`delegator_id`      = a.`delegator_id` 
                    AND         c.`adistribution_id`  = a.`adistribution_id`                    
                    JOIN        `courses` AS d
                    ON          b.`course_id` = d.`course_id`                
                    WHERE       a.`delegator_id` = ?
                    AND         a.`deleted_date` IS NULL
                    AND         b.`deleted_date` IS NULL
                    
                    $AND_not_complete
                    $AND_course_in
                    $AND_course_filter_in
                    $AND_cperiod_in
                    $AND_title_like
                    $AND_date_greater
                    $AND_date_less
                    
                    ORDER BY a.`delivery_date` DESC, b.`title` ASC
                    $LIMIT $OFFSET
                    ";

        $delegation_tasks = $db->GetAll($query, array($proxy_id));

        if ($delegation_tasks) {
            foreach ($delegation_tasks as $task) {
                $schedule_string = "";
                $target_type_plural = "";
                $distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($task["adistribution_id"]);
                if ($distribution) {
                    $distribution_targets = Models_Assessments_Distribution_Target::fetchAllByDistributionID($task["adistribution_id"]);
                    if ($distribution_targets) {
                        foreach ($distribution_targets as $distribution_target) {
                            if ($distribution_target->getTargetType() == "proxy_id") {
                                if ($distribution_target->getTargetRole() == "learner") {
                                    $target_type_plural = "learner";
                                } elseif ($distribution_target->getTargetRole() == "faculty") {
                                    $target_type_plural = "faculty member";
                                }
                            } elseif ($distribution_target->getTargetType() == "self") {
                                $target_type_plural = "self";
                            } elseif ($distribution_target->getTargetType() == "schedule_id") {
                                switch ($distribution_target->getTargetScope()) {
                                    case "internal_learners":
                                        $target_type_plural = "on-service learner";
                                        break;
                                    case "external_learners":
                                        $target_type_plural = "off-service learner";
                                        break;
                                    case "all_learners":
                                        $target_type_plural = "rotation learner";
                                        break;
                                    case "self":
                                    default:
                                        $target_type_plural = "rotation";
                                        break;
                                }
                            }
                        }
                        if (!isset($target_type_plural) || !$target_type_plural) {
                            $target_type_plural = "individual";
                        }
                    }
                    $distribution_assessors = Models_Assessments_Distribution_Assessor::fetchAllByDistributionID($task["adistribution_id"]);
                    if ($distribution_assessors) {
                        foreach ($distribution_assessors as $distribution_assessor) {
                            if ($distribution_assessor->getAssessorType() == "learner") {
                                $assessor_type_singular = "learner";
                            } elseif ($distribution_assessor->getAssessorType() == "faculty") {
                                $assessor_type_singular = "faculty member";
                            }
                        }
                        if (!isset($assessor_type_singular) || !$assessor_type_singular) {
                            $assessor_type_singular = "individual";
                        }
                    }
                    $distribution_delegation = new Entrada_Utilities_Assessments_DistributionDelegation(array("adistribution_id" => $distribution->getID()));
                    $targets = $distribution_delegation->getDelegationTargetsAndAssessors($task["start_date"], $task["end_date"], false);
                    $potential_target_names = array();
                    foreach ($targets as $target) {
                        if ($target["use_members"]) {
                            $potential_target_names[] = $target["member_fullname"];
                        } else {
                            $potential_target_names[] = $target["entity_name"];
                        }
                    }
                    $assessors = $distribution_delegation->getPossibleAssessors();
                    if ($target_type_plural == "self") {
                        $details_string = "Delegation of self assessment with " . @count($targets) . " possible assessors.";
                    } else {
                        $details_string = "Delegation of " . $target_type_plural . " assessments with " . @count($assessors) . " possible assessor" . (@count($assessors) > 1 ? "s" : "") . " and " . @count($targets) . " possible target" . (@count($targets) > 1 ? "s" : "") . ". ";
                    }

                    $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($distribution->getID());
                    if ($distribution_schedule) {
                        $schedule_record = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID());
                        if ($schedule_record) {
                            $schedule_string = $distribution_delegation->getConcatenatedBlockOrDateString($task["start_date"], $task["end_date"], $schedule_record);
                        }
                    }

                    $delegation = Models_Assessments_Distribution_Delegation::fetchRowByID($task["addelegation_id"]);
                    if ($delegation) {
                        $tasks[] = new Entrada_Utilities_Assessments_DeprecatedAssessmentTask(array(
                            "dassessment_id" => $task["addelegation_id"],
                            "assessor_value" => $delegation->getDelegatorID(),
                            "type" => "delegation",
                            "title" => $task["title"],
                            "description" => ($distribution->getDescription() ? $distribution->getDescription() : "No details provided."),
                            "details" => ($details_string ? $details_string : "No details provided."),
                            "url" => ENTRADA_URL . "/assessments/delegation?addelegation_id={$task["addelegation_id"]}&adistribution_id={$distribution->getID()}",
                            "start_date" => $distribution->getStartDate(),
                            "end_date" => $distribution->getEndDate(),
                            "adistribution_id" => $distribution->getID(),
                            "distribution_deleted_date" => $distribution->getDeletedDate(),
                            "delivery_date" => $task["delivery_date"],
                            "delegation_completed" => $delegation->getCompletedDate() ? 1 : 0,
                            "delegation_completed_date" => $delegation->getCompletedDate(),
                            "schedule_details" => ($schedule_string ? $schedule_string : ""),
                            "rotation_start_date" => null,
                            "rotation_end_date" => null,
                            "target_names" => array("potential" => implode(", ", $potential_target_names)),
                            "target_info" => $targets
                        ));
                    }
                }
            }
        }
        return $tasks;
    }

    private static function getAllApproverTasksByProxyID($proxy_id, $current_section = "assessments", $filters = array(), $search_value = null, $start_date = null, $end_date = null, $exclude_completed = false, $limit = 0, $offset = 0) {
        $tasks = array();
        $distribution_approver = new Models_Assessments_Distribution_Approver();
        $approver_tasks = $distribution_approver->fetchAllByProxyID($proxy_id, $current_section, $filters, $search_value, $limit, $offset);
        if ($approver_tasks) {
            foreach ($approver_tasks as $approver_task) {

                $assessment_tasks = Models_Assessments_Assessor::fetchAllByDistributionID($approver_task->getAdistributionID(), null, null, $start_date, $end_date);
                $distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($approver_task->getAdistributionID());
                if ($assessment_tasks && $distribution) {
                    foreach ($assessment_tasks as $assessment) {

                        $assessments_api = new Entrada_Assessments_Assessment(array(
                            "actor_proxy_id" => $proxy_id,
                            "fetch_form_data" => false,
                            "dassessment_id" => $assessment->getID()
                        ));
                        $assessment_data = $assessments_api->fetchAssessmentData();
                        if ($assessment_data) {
                            if ($assessment_data["progress"] && @count($assessment_data["progress"]) > 0) {
                                $complete_target_names = array();

                                foreach ($assessment_data["progress"] as $progress) {
                                    if ($progress["progress_value"] == "complete") {

                                        $target = $assessments_api->getCurrentAssessmentTarget($progress["target_record_id"], $progress["target_type"]);
                                        if (!empty($target)) {

                                            $event_string = "";
                                            $event_timeframe_start_string = "";
                                            $event_timeframe_end_string = "";
                                            $schedule_string = "";
                                            $delegator_name = "";
                                            $target_id = $target["target_record_id"];
                                            $complete_target_names[] = $target["target_name"];
                                            $url = $assessments_api->getAssessmentURL($progress["target_record_id"], $progress["target_type"], false, $assessment->getID(), $progress["aprogress_id"]);

                                            $approver_approvals = new Models_Assessments_Distribution_Approvals();
                                            $approver_record = $approver_approvals->fetchRowByProgressIDDistributionID($progress["aprogress_id"], $distribution->getAdistributionID());

                                            $include = true;
                                            // Check to see if this record is complete and should be excluded (meaning it has a release).
                                            if ($approver_record && $exclude_completed) {
                                                $include = false;
                                            }

                                            if ($include) {
                                                if (!$approver_record) {
                                                    $details_string = "Please review this assessment task that was completed by " . $assessment_data["assessor"]["full_name"] . " for " . $target["target_name"] . " on " . date("M j, Y", $progress["updated_date"]);
                                                    $active_user_progress_value = "pending";
                                                } else {
                                                    $released_user = Models_User::fetchRowByID($approver_record->getApproverID());
                                                    $details_string = "This assessment task created by " . $assessment_data["assessor"]["full_name"] . " for " . $target["target_name"] . " was reviewed by " . $released_user->getFullname(false) . " on " . date("M j, Y", $approver_record->getCreatedDate());
                                                    $active_user_progress_value = "complete";
                                                }

                                                $distribution_delegator = Models_Assessments_Distribution_Delegator::fetchRowByDistributionID($distribution->getID());
                                                if ($distribution_delegator) {
                                                    if ($distribution_delegator->getDelegatorType() == "proxy_id") {
                                                        $delegator_user = Models_User::fetchRowByID($distribution_delegator->getDelegatorID());
                                                        $delegator_name = $delegator_user->getFullname(false);
                                                    }
                                                }

                                                $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($distribution->getID());
                                                if ($distribution_schedule) {
                                                    $schedule_record = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID());
                                                    if ($schedule_record) {
                                                        if ($assessment->getStartDate() && $assessment->getEndDate()) {
                                                            $schedule_string = parent::getConcatenatedBlockString($assessment->getID(), $schedule_record, $assessment->getStartDate(), $assessment->getEndDate(), $distribution->getOrganisationID());
                                                        }
                                                    }
                                                }

                                                if ($assessment->getAssociatedRecordType() == "event_id") {
                                                    $associated_event = Models_Event::get($assessment->getAssociatedRecordID());
                                                    $event_string = $associated_event->getEventTitle();
                                                    $event_time_strings = Entrada_Utilities_Assessments_DistributionLearningEvent::buildTimeframeStrings($associated_event->getEventStart(), $associated_event->getEventFinish());
                                                    if (!empty($event_time_strings)) {
                                                        $event_timeframe_start_string = $event_time_strings["timeframe_start"];
                                                        $event_timeframe_end_string = $event_time_strings["timeframe_end"];
                                                    }

                                                }

                                                $tasks[] = new Entrada_Utilities_Assessments_DeprecatedAssessmentTask(array(
                                                    "dassessment_id" => $assessment->getID(),
                                                    "published" => $assessment->getPublished(),
                                                    "type" => "approver",
                                                    "title" => $distribution->getTitle(),
                                                    "description" => ($distribution->getDescription() ? $distribution->getDescription() : "No details provided."),
                                                    "details" => ($details_string ? $details_string : "No details provided."),
                                                    "event_details" => $event_string ? $event_string : "",
                                                    "schedule_details" => ($schedule_string ? $schedule_string : ""),
                                                    "url" => $url,
                                                    "target_id" => $target_id,
                                                    "assessor" => $assessment_data["assessor"]["full_name"],
                                                    "assessor_type" => $assessment->getAssessorType(),
                                                    "assessor_value" => $assessment->getAssessorValue(),
                                                    "start_date" => $distribution->getStartDate(),
                                                    "end_date" => $distribution->getEndDate(),
                                                    "event_timeframe_start" => $event_timeframe_start_string,
                                                    "event_timeframe_end" => $event_timeframe_end_string,
                                                    "rotation_start_date" => $assessment->getRotationStartDate(),
                                                    "rotation_end_date" => $assessment->getRotationEndDate(),
                                                    "delivery_date" => ($assessment->getDeliveryDate() ? $assessment->getDeliveryDate() : false),
                                                    "adistribution_id" => $distribution->getID(),
                                                    "distribution_deleted_date" => $distribution->getDeletedDate(),
                                                    "delegated_by" => $delegator_name,
                                                    "max_overall_attempts" => (isset($max_overall_attempts) && $max_overall_attempts ? $max_overall_attempts : 1),
                                                    "max_individual_attempts" => (isset($max_individual_attempts) && $max_individual_attempts ? $max_individual_attempts : 1),
                                                    "completed_attempts" => (isset($completed_attempts) && $completed_attempts ? $completed_attempts : 0),
                                                    "aprogress_id" => $progress["aprogress_id"],
                                                    "target_names" => array("complete" => implode(", ", $complete_target_names)),
                                                    "target_info" => array(array("name" => $target["target_name"], "proxy_id" => $target_id, "progress" => array($active_user_progress_value), "aprogress_id" => $progress["aprogress_id"]))
                                                ));
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $tasks;
    }

    public static function getAllFilteredTasks($proxy_id, $filters = array(), $search_value = null, $start_date = null, $end_date = null, $current_page = "", $current_section = "assessments", $is_external = false, $exclude_completed = false, $limit = false, $offset = 0) {
        $tasks = array();

        $run_all_queries = !array_key_exists("distribution_method", $filters);
        $distribution_methods_list = array("rotation_schedule" => false, "delegation" => false, "date_range" => false, "learning_event" => false);

        if (!$run_all_queries) {
            $distribution_methods = new Models_Assessments_Distribution_Method();
            foreach ($filters["distribution_method"] as $key => $method_filter) {
                $method = $distribution_methods->fetchRowByID($key);
                if ($method) {
                    $method_title = str_replace(" ", "_", strtolower($method->getTitle()));
                    foreach ($distribution_methods_list as $key => $distribution_methods_item) {
                        if ($method_title === $key) {
                            $distribution_methods_list[$key] = true;
                        }
                    }
                }
            }
        }

        if ($filters || !is_null($search_value) || !is_null($start_date) || !is_null($end_date)) {
            if ($run_all_queries || $distribution_methods_list["rotation_schedule"] || $distribution_methods_list["date_range"] || $distribution_methods_list["learning_event"]) {
                $assessment_tasks = Entrada_Utilities_Assessments_DeprecatedAssessmentTask::getAllAssessmentTasksByProxyID($proxy_id, $current_section, $is_external, $filters, $search_value, $start_date, $end_date, $exclude_completed, $limit, $offset);
                if ($assessment_tasks) {
                    $tasks = array_merge($tasks, $assessment_tasks);
                }
            }

            if ($run_all_queries || $distribution_methods_list["delegation"]) {
                $delegation_tasks = Entrada_Utilities_Assessments_DeprecatedAssessmentTask::getAllDelegationTasksByProxyID($proxy_id, $current_section, $filters, $search_value, $start_date, $end_date, $exclude_completed, $limit, $offset);
                if ($delegation_tasks) {
                    $tasks = array_merge($tasks, $delegation_tasks);
                }
            }

            if ($run_all_queries) {
                $approver_tasks = Entrada_Utilities_Assessments_DeprecatedAssessmentTask::getAllApproverTasksByProxyID($proxy_id, $current_section, $filters, $search_value, $start_date, $end_date, $exclude_completed, $limit, $offset);
                if ($approver_tasks) {
                    $tasks = array_merge($tasks, $approver_tasks);
                }
            }

            if (!$run_all_queries) {
                Entrada_Utilities_Assessments_DeprecatedAssessmentTask::removeTasksBySubType($tasks, $filters);
            }
        } else {
            $tasks = Entrada_Utilities_Assessments_DeprecatedAssessmentTask::getAllTasks($proxy_id, $current_section, $is_external, $exclude_completed, $limit, $offset);
        }

        Entrada_Utilities_Assessments_DeprecatedAssessmentTask::removeTasksByPageTab($tasks, $current_page, $is_external);

        if ($filters && isset($filters["task_status"])) {
            foreach($tasks as $key => $task) {
                $status_arr = array();
                if ($task->getTargetsPending() && array_key_exists("pending", $filters["task_status"])) {
                    $status_arr[] = "pending";
                }
                if ($task->getTargetsInprogress() && array_key_exists("inprogress", $filters["task_status"])) {
                    $status_arr[] = "inprogress";
                }
                if ($task->getTargetsComplete() && array_key_exists("complete", $filters["task_status"])) {
                    $status_arr[] = "complete";
                }

                if (!count($status_arr)) {
                    unset($tasks[$key]);
                }
            }
        }

        return $tasks;
    }

    public static function removeTasksBySubType(&$tasks, $filters) {
        $distribution_methods = new Models_Assessments_Distribution_Method();
        $method_titles = array();

        if ($filters && isset($filters["distribution_method"])) {
            foreach ($filters["distribution_method"] as $key => $method_filter) {
                $method = $distribution_methods->fetchRowByID($key);
                $method_titles[] = str_replace(" ", "_", strtolower($method->getTitle()));
            }

            foreach ($tasks as $key => $task) {
                if (!empty($task)) {
                    if (is_object($task)) {
                        if ($task->getType() == "assessment" && !in_array($task->getAssessmentSubType(), $method_titles)) {
                            unset($tasks[$key]);
                        }
                    } else {
                        if (isset($task["type"]) && isset($task["assessment_sub_type"]) && $task["type"] == "assessment" && !in_array($task["assessment_sub_type"], $method_titles)) {
                            unset($tasks[$key]);
                        }
                    }
                }
            }
        }
    }

    private static function removeTasksByPageTab(&$tasks, $page, $is_external = false) {
        $approver_approvals = new Models_Assessments_Distribution_Approvals();
        $assessor_type = $is_external ? "external" : "internal";

        if ($page == "incomplete") {
            foreach ($tasks as $key => $task) {
                $keep_task = false;
                $progress_record = Models_Assessments_Progress::fetchRowByAdistributionIDAssessorTypeAssessorValueTargetRecordIDDAssessmentID($task->getDistributionID(), $assessor_type, $task->getAssessorValue(), $task->getTargetID(), $task->getDassessmentID());
                if ($task->getType() == "assessment") {
                    if ($progress_record && $progress_record->getProgressValue() != "complete") {
                        $keep_task = true;
                    } else if ($task->getCompletedAttempts() < $task->getMaxOverallAttempts()) {
                        $keep_task = true;
                    }
                } else if ($task->getType() == "delegation" && !$task->getDelegationCompleted()) {
                    $keep_task = true;
                } else if ($task->getType() == "approver") {
                    $approver_record = $approver_approvals->fetchRowByProgressIDDistributionID($task->getProgressID(), $task->getDistributionID());
                    if (!$approver_record) {
                        $keep_task = true;
                    }
                }
                if (!$keep_task) {
                    unset($tasks[$key]);
                }
            }
        } else if ($page == "completed") {
            foreach ($tasks as $key => $task) {
                $keep_task = false;
                $progress_record = Models_Assessments_Progress::fetchRowByAdistributionIDAssessorTypeAssessorValueTargetRecordIDDAssessmentID($task->getDistributionID(), $assessor_type, $task->getAssessorValue(), $task->getTargetID(), $task->getDassessmentID());
                if ($task->getType() == "assessment" && !$is_external && $task->getCompletedAttempts() >= 1 || $task->getType() == "assessment" && $is_external && $progress_record && $progress_record->getProgressValue() == "complete") {
                    $keep_task = true;
                } else if ($task->getType() == "delegation" && $task->getDelegationCompleted()) {
                    $keep_task = true;
                } else if ($task->getType() == "approver") {
                    $approver_record = $approver_approvals->fetchRowByProgressIDDistributionID($task->getProgressID(), $task->getDistributionID());
                    if ($approver_record) {
                        $keep_task = true;
                    }
                }
                if (!$keep_task) {
                    unset($tasks[$key]);
                }
            }
        }
    }

    public static function saveFilterPreferences($filters, $section) {
        global $translate;
        if (!empty($filters)) {
            unset($_SESSION[APPLICATION_IDENTIFIER][$section]["tasks"]["selected_filters"]);
            $distribution_methods = new Models_Assessments_Distribution_Method();

            foreach ($filters as $filter_type => $filter_targets) {
                foreach ($filter_targets as $target) {
                    $target_label = "";

                    switch ($filter_type) {
                        case "program" :
                            $target = clean_input($target, array("int"));
                            $course = Models_Course::get($target);
                            if ($course) {
                                $target_label = $course->getCourseName();
                            }
                            break;
                        case "distribution_method" :
                            $target = clean_input($target, array("int"));
                            $method = $distribution_methods->fetchRowByID($target);
                            $target_label = $translate->_($method->getTitle());
                            break;
                        case "cperiod" :
                            $target = clean_input($target, array("int"));
                            $cperiod = Models_Curriculum_Period::fetchRowByID($target);
                            if ($cperiod) {
                                $target_label = ($cperiod->getCurriculumPeriodTitle() ? $cperiod->getCurriculumPeriodTitle() : date("M j, Y", $cperiod->getStartDate()) . " to " . date("M j, Y", $cperiod->getFinishDate()));
                            }
                            break;
                        case "task_status":
                            $target = clean_input($target, array("trim", "striptags"));
                            switch ($target) {
                                case "pending":
                                    $target_label = $translate->_("Pending");
                                    break;
                                case "inprogress":
                                    $target_label = $translate->_("In Progress");
                                    break;
                                case "complete":
                                    $target_label = $translate->_("Completed");
                                    break;
                            }
                    }
                    $_SESSION[APPLICATION_IDENTIFIER][$section]["tasks"]["selected_filters"][$filter_type][$target] = $target_label;
                }
            }
        }
    }

    public static function countAllIncompleteAssessmentTasks($proxy_id = null) {
        $incomplete_assessment_task = 0;
        $assessment_tasks = Entrada_Utilities_Assessments_DeprecatedAssessmentTask::getAllTasks($proxy_id);
        if ($assessment_tasks) {
            foreach ($assessment_tasks as $task) {
                if (!$task->getDistributionDeletedDate()) {
                    if ($task->getType() == "assessment" && $task->getMaxOverallAttempts() > $task->getCompletedAttempts()) {
                        $incomplete_assessment_task++;
                    } else if ($task->getType() == "delegation" && !$task->getDelegationCompleted()) {
                        $incomplete_assessment_task++;
                    } else if ($task->getType() == "approver") {
                        $approver_approvals = new Models_Assessments_Distribution_Approvals();
                        $approver_record = $approver_approvals->fetchRowByProgressIDDistributionID($task->getProgressID(), $task->getDistributionID());
                        if (!$approver_record) {
                            $incomplete_assessment_task++;
                        }
                    }
                }
            }
        }
        return $incomplete_assessment_task;
    }

    /**
     * Determine whether or not the given user ID owns the courses they are associated with.
     *
     * @param $user_id
     * @param $organisation_id
     * @return bool
     */
    public static function isCourseOwner($user_id, $organisation_id) {
        $course_owner = false;
        if ($courses = Models_Course::getUserCourses($user_id, $organisation_id)) {
            if (is_array($courses) && !empty($courses)) {
                foreach ($courses as $course) {
                    if (CourseOwnerAssertion::_checkCourseOwner($user_id, $course->getID())) {
                        $course_owner = true;
                    }
                }
            }
        }
        return $course_owner;
    }

    /**
     * Return a list of proxy IDs to allow access, regardless of ACL check, to faculty tabs.
     * This is a hook that should be empty until permissioning is sorted out, and then, if whitelisting
     * is required, fetch the whitelist from a DB table.
     *
     * @return array
     */
    public static function getFacultyProxyIDAccessWhitelist () {
        return array();
    }

    /**
     * Determine whether we grant access based on whitelist. This is a hook for a future ACL implementation.
     *
     * Returns false on deny access, true on allow, null on no-action
     *
     * @param $ENTRADA_USER
     * @return bool
     */
    public static function getFacultyAccessOverrideByCourseOwnershipOrWhitelist(&$ENTRADA_USER) {

        $perform_access_check = true; // This is hard override of the faculty access check.
        $limit_to_whitelist = false; // Flag for if we want to limit our access check to ONLY a whitelist. TODO: change this to true when whitelisting is supported via DB table.

        $return_override = null; // Return value: do we allow access override? NULL = no action
        if ($perform_access_check) {
            if ($limit_to_whitelist) {
                // If the proxy is whitelisted, then we treat this viewer as the course owner
                if (in_array($ENTRADA_USER->getActiveId(), Entrada_Utilities_Assessments_DeprecatedAssessmentTask::getFacultyProxyIDAccessWhitelist())) {
                    $return_override = true;
                } else {
                    $return_override = false;
                }
            } else {
                // otherwise, we check if the viewer actually owns the course
                $return_override = Entrada_Utilities_Assessments_DeprecatedAssessmentTask::isCourseOwner($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation());
            }
        }
        return $return_override;
    }

    /**
     * Determine whether we grant access based on whitelist and/or whether the faculty is associated with a given course.
     * This is a hook for a future ACL implementation.
     *
     * Returns false on deny access, true on allow, null on no-action
     *
     * @param $ENTRADA_USER
     * @return bool
     */
    public static function getFacultyAccessOverrideByCourseFacultyOrWhitelist(&$ENTRADA_USER) {
        $limit_to_whitelist = false; // Flag for if we want to limit our access check to ONLY a whitelist. TODO: change this to true when whitelisting is supported via DB table.

        $return_override = null; // Return value: do we allow access override? NULL = no action
        if ($limit_to_whitelist) {
            // If the proxy is whitelisted, then we treat this viewer as the course owner
            if (in_array($ENTRADA_USER->getActiveId(), Entrada_Utilities_Assessments_DeprecatedAssessmentTask::getFacultyProxyIDAccessWhitelist())) {
                $return_override = true;
            } else {
                $return_override = false;
            }
        }
        return $return_override;
    }

    public static function removeAllFilters($section) {
        unset($_SESSION[APPLICATION_IDENTIFIER][$section]["tasks"]["selected_filters"]);
        unset($_SESSION[APPLICATION_IDENTIFIER][$section]["tasks"]["search_term"]);
        unset($_SESSION[APPLICATION_IDENTIFIER][$section]["tasks"]["start_date"]);
        unset($_SESSION[APPLICATION_IDENTIFIER][$section]["tasks"]["end_date"]);
    }

    public static function setFilterPreferences($request, $section) {
        $PROCESSED["filters"] = array();
        if (isset($request["distribution_method"]) && is_array($request["distribution_method"])) {
            $PROCESSED["filters"]["distribution_method"] = array_filter($request["distribution_method"], function ($distribution_method) {
                return (int) $distribution_method;
            });
        }

        if (isset($request["cperiod"]) && is_array($request["cperiod"])) {
            $PROCESSED["filters"]["cperiod"] = array_filter($request["cperiod"], function ($cperiod) {
                return (int) $cperiod;
            });
        }

        if (isset($request["task_status"]) && is_array($request["task_status"])) {
            $PROCESSED["filters"]["task_status"] = array_filter($request["task_status"], function ($task_status) {
                return clean_input(strtolower($task_status), array("trim", "striptags"));
            });
        }

        if (isset($request["program"]) && is_array($request["program"])) {
            $PROCESSED["filters"]["program"] = array_filter($request["program"], function ($program) {
                return (int) $program;
            });
        }

        if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
            if (empty($request["search_value"])) {
                $PROCESSED["search_value"] = "";
            } else {
                if ($tmp_input) {
                    $PROCESSED["search_value"] = $tmp_input;
                } else {
                    $PROCESSED["search_value"] = "";
                }
            }
            $_SESSION[APPLICATION_IDENTIFIER][$section]["tasks"]["search_term"] = $PROCESSED["search_value"];
        }

        if (isset($request["start_date"]) && $tmp_input = clean_input(strtotime($request["start_date"]. " 00:00:00"), "int")) {
            if (empty($request["start_date"])) {
                $PROCESSED["start_date"] = "";
            } else {
                if ($tmp_input) {
                    $PROCESSED["start_date"] = $tmp_input;
                } else {
                    $PROCESSED["start_date"] = "";
                }
            }
            $_SESSION[APPLICATION_IDENTIFIER][$section]["tasks"]["start_date"] = $PROCESSED["start_date"];
        }

        if (isset($request["end_date"]) && $tmp_input = clean_input(strtotime($request["end_date"]. "23:59:59"), "int")) {
            if (empty($request["end_date"])) {
                $PROCESSED["end_date"] = "";
            } else {
                if ($tmp_input) {
                    $PROCESSED["end_date"] = $tmp_input;
                } else {
                    $PROCESSED["end_date"] = "";
                }
            }
            $_SESSION[APPLICATION_IDENTIFIER][$section]["tasks"]["end_date"] = $PROCESSED["end_date"];
        }

        Entrada_Utilities_Assessments_DeprecatedAssessmentTask::saveFilterPreferences($PROCESSED["filters"], $section);
    }

    public static function getAssociatedLearnerFacultyProxyList() {
        global $db;
        $complete_user_list = array();

        $active_user_course_id_list = Models_Course::getActiveUserCoursesIDList();
        if ($active_user_course_id_list) {
            $COURSE_ID_LIST = implode(",", $active_user_course_id_list);

            $query = "  
                    SELECT b.`proxy_id`
                    FROM `course_groups` AS a
                    JOIN `course_group_contacts` AS b
                    ON a.`cgroup_id` = b.`cgroup_id`
                    WHERE a.`course_id` IN ({$COURSE_ID_LIST})
                    AND a.`active` = 1
                    
                    UNION
                    
                    SELECT `audience_value`
                    FROM `course_audience` 
                    WHERE `course_id` IN ({$COURSE_ID_LIST})
                    AND `audience_type` = 'proxy_id'
                    AND `audience_active` = 1
                    
                    UNION
                    
                    SELECT a.`proxy_id`
                    FROM `group_members` AS a
                    JOIN `course_audience` AS b
                    ON a.`group_id` = b.`audience_value`
                    WHERE b.`course_id` IN ({$COURSE_ID_LIST})
                    AND b.`audience_type` = 'group_id'
                    AND b.`audience_active` = 1
                    AND a.`member_active` = 1
                    
                    UNION
                    
                    SELECT `proxy_id` 
                    FROM `course_contacts`
                    WHERE `course_id` IN ({$COURSE_ID_LIST})
                    AND `contact_type` IN ('faculty', 'director', 'pcoordinator', 'associated_faculty')
                    
                    UNION
                    
                    SELECT `assessor_value`
                    FROM `cbl_course_contacts`
                    WHERE `course_id` IN ({$COURSE_ID_LIST})
                    AND `assessor_type` = 'internal'
                    AND `deleted_date` IS NULL
                    ";

            $user_proxy_list = $db->GetAll($query);
            if ($user_proxy_list) {
                foreach ($user_proxy_list as $user_proxy_id) {
                    $complete_user_list[] = (string)$user_proxy_id["proxy_id"];
                }
            }
        }

        return !empty($complete_user_list) ? implode(",", $complete_user_list) : array();
    }

    public static function getAssociatedExternalIDList() {
        $external_list = array();

        $active_user_course_id_list = Models_Course::getActiveUserCoursesIDList();

        if ($active_user_course_id_list) {
            $course_contact_model = new Models_Assessments_Distribution_CourseContact();

            foreach ($active_user_course_id_list as $course_id) {
                $external_contacts = $course_contact_model->fetchAllByCourseID($course_id, "external");

                if ($external_contacts) {
                    foreach ($external_contacts as $external) {
                        $external_list[] = (string)$external["assessor_value"];
                    }
                }
            }
        }

        return !empty($external_list) ? implode(",", $external_list) : array();
    }
    
    public static function getAllTasksForAssociatedLearnersAssociatedFaculty($task_type, $organisation_id, $offset = 0, $limit = 10, $count = false, $search_value = null, $schedule_type = null) {
        global $db;
        $task_list = array();

        $LIMIT = $OFFSET = $AND_TITLE_LIKE_TASK = $AND_TITLE_LIKE_DELEGATION = $AND_TITLE_LIKE_APPROVER = $AND_SCHEDULE_TYPE = "";

        if ($limit && !$count) {
            $LIMIT = " LIMIT $limit";
        }

        if ($offset && !$count) {
            $OFFSET = " OFFSET $offset";
        }

        if (!is_null($search_value) && $search_value != "") {
            $LIMIT = "";
            $OFFSET = "";
            $AND_TITLE_LIKE_TASK        = " AND (b.`title` LIKE (". $db->qstr("%". $search_value ."%") .") OR af.`title` LIKE (". $db->qstr("%". $search_value ."%") .") OR CONCAT(e.`firstname`, ' ', e.`lastname`) LIKE (". $db->qstr("%". $search_value ."%") .") )";
            $AND_TITLE_LIKE_DELEGATION  = " AND (b.`title` LIKE (". $db->qstr("%". $search_value ."%") .") OR CONCAT(c.`firstname`, ' ', c.`lastname`) LIKE (". $db->qstr("%". $search_value ."%") .") )";
            $AND_TITLE_LIKE_APPROVER    = " AND (b.`title` LIKE (". $db->qstr("%". $search_value ."%") .") OR af.`title` LIKE (". $db->qstr("%". $search_value ."%") .") OR CONCAT(d.`firstname`, ' ', d.`lastname`) LIKE (". $db->qstr("%". $search_value ."%") .") )";
        }

        if (isset($schedule_type) && $schedule_type) {
            switch ($schedule_type) {
                case "distribution":
                    $AND_SCHEDULE_TYPE = " AND b.`adistribution_id` IS NOT NULL";
                    break;
                case "triggered":
                    $AND_SCHEDULE_TYPE = " AND b.`adistribution_id` IS NULL";
                    break;
                case "all":
                default:
                    break;
            }
        }

        $AND_ONLY_TERMINAL_ASSESSMENTS = "";
        $assessment_tasks = new Entrada_Assessments_Tasks();
        $method_type_ids = $assessment_tasks->fetchMultiPhaseAssessmentMethodTypes();
        if (is_array($method_type_ids) && !empty($method_type_ids)) {

            /* This clause excludes the self-assessment portion of multi-phase assessments.
             * Multi-phase assessments include "complete and confirm by pin", "confirm by email" and any other
             * assessment method that has multiple steps. By default,  we exclude the first step and only include
             * the terminal step; the initial step is the assessor filling out an assessment on
             * themselves and then asking another user to confirm it. On submission, the assessment is copied to a new assessor; we
             * only want to include the copied (terminal) version of this assessment. */

            $phased_assessment_method_ids = implode(",", $method_type_ids);
            $AND_ONLY_TERMINAL_ASSESSMENTS = "
                        AND NOT (a.`assessor_value` = d.`target_record_id`
                            AND a.`assessor_type` = d.`target_type`
                            AND a.`assessment_method_id` IN ($phased_assessment_method_ids)
                        )
                    ";
        }

        $SELECT_COUNT_START = "";
        $QUERY_END = " ORDER BY 12 DESC ";
        if ($count) {
            $SELECT_COUNT_START = " SELECT COUNT(*) FROM ( ";
            $QUERY_END = " ) AS COUNT ";
        }

        $USER_ID_LIST = Entrada_Utilities_Assessments_DeprecatedAssessmentTask::getAssociatedLearnerFacultyProxyList();
        $EXTERNAL_ID_LIST = Entrada_Utilities_Assessments_DeprecatedAssessmentTask::getAssociatedExternalIDList();
        $COURSE_ID_LIST = null;

        $active_user_course_id_list = Models_Course::getActiveUserCoursesIDList();
        if ($active_user_course_id_list) {
            $COURSE_ID_LIST = implode(",", $active_user_course_id_list);
        }

        if (!empty($USER_ID_LIST) && $USER_ID_LIST != "" && $COURSE_ID_LIST) {
            if (!empty($USER_ID_LIST) && $USER_ID_LIST != "" && !empty($EXTERNAL_ID_LIST) && $EXTERNAL_ID_LIST != "") {
                $AND_ASSESSOR = " AND ((a.`assessor_value` IN ({$USER_ID_LIST}) AND a.`assessor_type` = 'internal') OR (a.`assessor_value` IN ({$EXTERNAL_ID_LIST}) AND a.`assessor_type` = 'external'))";
            } else {
                if (!empty($USER_ID_LIST) && $USER_ID_LIST != "") {
                    $AND_ASSESSOR = " AND a.`assessor_value` IN ({$USER_ID_LIST}) AND a.`assessor_type` = 'internal'";
                }

                if (!empty($EXTERNAL_ID_LIST) && $EXTERNAL_ID_LIST != "") {
                    $AND_ASSESSOR = " AND a.`assessor_value` IN ({$EXTERNAL_ID_LIST}) AND a.`assessor_type` = 'external'";
                }
            }

            $incomplete_tasks_query = $SELECT_COUNT_START;
            $incomplete_tasks_query .= "
            SELECT b.`title` AS `distribution_title`, af.`title` AS `form_title`, CONCAT(e.`firstname`, ' ', e.`lastname`) AS internal_full_name, CONCAT(f.`firstname`, ' ', f.`lastname`) AS external_full_name, a.`assessor_type`, a.`assessor_value`, a.`adistribution_id`, a.`dassessment_id` AS task_id, d.`progress_value`, d.`aprogress_id`, 'task' AS task_type, a.`delivery_date` AS delivery_date, 0 AS delegation_start, 0 AS delegation_end, 0 AS atarget_id
            FROM `cbl_distribution_assessments` AS a
            LEFT JOIN `cbl_assessment_distributions` AS b
            ON a.`adistribution_id` = b.`adistribution_id`                  
            JOIN `courses` AS c
            ON a.`course_id` = c.`course_id`
            JOIN `cbl_assessments_lu_forms` AS af
            ON af.`form_id` = a.`form_id`
            LEFT JOIN `cbl_assessment_progress` AS d  
            ON a.`dassessment_id` = d.`dassessment_id`
            LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS e
            ON a.`assessor_value` = e.`id` 
            LEFT JOIN `cbl_external_assessors` AS f
            ON a.`assessor_value` = f.`eassessor_id`  
            JOIN `cbl_distribution_assessment_targets` AS cat
            ON cat.`dassessment_id` = a.`dassessment_id`                                   
            WHERE a.`published` = 1 
            $AND_ONLY_TERMINAL_ASSESSMENTS
            $AND_SCHEDULE_TYPE
            AND cat.`task_type` = " . $db->qstr($task_type) . "
            AND a.`organisation_id` = ".$db->qstr($organisation_id)."
            AND (b.`visibility_status` IS NULL OR b.`visibility_status` = 'visible')
            AND b.`deleted_date` IS NULL
            AND a.`deleted_date` IS NULL
            AND d.`deleted_date` IS NULL
            AND cat.`deleted_date` IS NULL
            AND (d.`progress_value` = 'inprogress' OR d.`progress_value` IS NULL)
            AND a.`course_id` IN ({$COURSE_ID_LIST})
            $AND_ASSESSOR
            $AND_TITLE_LIKE_TASK
            GROUP BY a.`dassessment_id`
            
            UNION ALL 
            
            SELECT b.`title` AS `distribution_title`, NULL AS `form_title`, CONCAT(c.`firstname`, ' ', c.`lastname`) AS internal_full_name, CONCAT(d.`firstname`, ' ', d.`lastname`) AS external_full_name, 0, a.`delegator_id`, a.`adistribution_id`, a.`addelegation_id` AS task_id, 0, 0, 'delegation' AS task_type, a.`delivery_date` AS delivery_date, a.`start_date` AS delegation_start, a.`end_date` AS delegation_end, 0 AS atarget_id
            FROM `cbl_assessment_distribution_delegations` AS a                    
            LEFT JOIN `cbl_assessment_distributions` AS b  
            ON b.`adistribution_id`  = a.`adistribution_id`                 
            LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS c
            ON a.`delegator_id` = c.`id`
            LEFT JOIN `cbl_external_assessors` AS d
            ON a.`delegator_id` = d.`eassessor_id`   
            WHERE a.`deleted_date` IS NULL
            $AND_SCHEDULE_TYPE
            AND a.`completed_date` IS NULL    
            AND b.`organisation_id` = ".$db->qstr($organisation_id)."      
            AND b.`deleted_date` IS NULL
            AND b.`visibility_status` = 'visible'
            AND b.`assessment_type` = " . $db->qstr($task_type) . "
            AND a.`delegator_id` IN ({$USER_ID_LIST})
            AND b.`course_id` IN ({$COURSE_ID_LIST})
            $AND_TITLE_LIKE_DELEGATION

            UNION ALL
            
            SELECT b.`title` AS `distribution_title`, af.`title` AS `form_title`, CONCAT(d.`firstname`, ' ', d.`lastname`) AS internal_full_name, CONCAT(g.`firstname`, ' ', g.`lastname`) AS external_full_name, c.`assessor_type`, a.`proxy_id`, a.`adistribution_id`, c.`dassessment_id` AS task_id, e.`progress_value`, e.`aprogress_id`, 'approver' AS task_type, c.`delivery_date` AS delivery_date, 0 AS delegation_start, 0 AS delegation_end, h.`atarget_id` AS atarget_id
            FROM `cbl_assessment_distribution_approvers` AS a
            LEFT JOIN `cbl_assessment_distributions` AS b
            ON a.`adistribution_id` = b.`adistribution_id`
            JOIN `cbl_distribution_assessments` AS c
            ON b.`adistribution_id` = c.`adistribution_id`  
            JOIN `cbl_assessments_lu_forms` AS af
            ON af.`form_id` = c.`form_id`  
            LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS d
            ON a.`proxy_id` = d.`id`       
            LEFT JOIN `cbl_external_assessors` AS g
            ON a.`proxy_id` = g.`eassessor_id`
            JOIN `cbl_assessment_progress` AS e  
            ON c.`dassessment_id` = e.`dassessment_id`
            LEFT JOIN `cbl_assessment_progress_approvals` AS f
            ON e.`aprogress_id` = f.`aprogress_id`
            JOIN `cbl_distribution_assessment_targets` AS h
            ON e.`dassessment_id` = h.`dassessment_id`
            AND e.`target_record_id` = h.`target_value`
            AND e.`target_type` = h.`target_type`
            WHERE b.`deleted_date` IS NULL
            $AND_SCHEDULE_TYPE
            AND b.`visibility_status` = 'visible'
            AND b.`assessment_type` = " . $db->qstr($task_type) . "
            AND b.`organisation_id` = ".$db->qstr($organisation_id)."
            AND e.`progress_value` = 'complete'
            AND c.`deleted_date` IS NULL
            AND e.`deleted_date` IS NULL
            AND f.`approver_id` IS NULL
            AND a.`proxy_id` IN ({$USER_ID_LIST})
            AND b.`course_id` IN ({$COURSE_ID_LIST})
            $AND_TITLE_LIKE_APPROVER
             
            $QUERY_END
                               
            $LIMIT $OFFSET
            ";

            $task_list = $db->GetAll($incomplete_tasks_query);

            if ($task_list && !$count) {
                $assessment_api = new Entrada_Assessments_Assessment(array("limit_dataset" => array("targets", "progress")));

                foreach ($task_list as $key => $task) {

                    if (isset($task["distribution_title"]) && $task["distribution_title"]) {
                        $task["title"] = $task["distribution_title"];
                    } else {
                        $task["title"] = (isset($task["form_title"]) && $task["form_title"] ? $task["form_title"] : "");
                    }

                    if ($task["task_type"] == "task") {
                        $targets = $assessment_api->getAssessmentTargetList($task["task_id"]);

                        if (empty($targets)) {
                            unset($task_list[$key]);
                        } else {
                            foreach ($targets as $id => $target) {
                                if ($target["progress"][0] != "pending" && $target["progress"][0] != "inprogress") {
                                    unset($targets[$id]);
                                }
                            }

                            if ($targets && !empty($targets)) {
                                $task_list[$key] = array_merge($task, $targets);
                            } else {
                                unset($task_list[$key]);
                            }
                        }
                    } else if ($task["task_type"] == "approver") {
                        $progress = Models_Assessments_Progress::fetchRowByID($task["aprogress_id"]);
                        if ($progress) {
                            $user = Models_User::fetchRowByID($progress->getTargetRecordID());
                            if (!$user) {
                                unset($task_list[$key]);
                            } else {
                                $release = new Models_Assessments_Distribution_Approvals();
                                if ($release->fetchRowByProgressIDDistributionID($task["aprogress_id"], $task["adistribution_id"])) {
                                    unset($task_list[$key]);
                                } else {
                                    $task_list[$key] = array_merge($task, array("0" => array("target_record_id" => $user->getID(), "name" => $user->getFullname(false))));
                                }
                            }
                        } else {
                            unset($task_list[$key]);
                        }
                    } else {
                        $distribution_delegation = new Entrada_Utilities_Assessments_DistributionDelegation(array("adistribution_id" => $task["adistribution_id"]));
                        $targets_assessors_list = $distribution_delegation->getDelegationTargetsAndAssessors($task["delegation_start"], $task["delegation_end"], false);
                        if ($targets_assessors_list && !empty($targets_assessors_list)) {
                            $task_list[$key] = array_merge($task, $targets_assessors_list);
                        } else {
                            unset($task_list[$key]);
                        }
                    }
                }
            }
        }
        return $task_list ? $task_list : array();
    }

    public static function getAllTasksByAssessorIDAssessorTypeTaskType($assessor_id, $assessor_type, $task_type, $start_date = null, $end_date = null, $add_delegations = false, $add_approver = false, $add_progress_update_date = false) {
        global $db;
        $task_list = array();

        $AND_date_greater = $AND_date_less = $AND_date_greater_approver = $AND_date_less_approver = $UNION_delegation = $UNION_approver = $SELECT_progress_update= "";
        $COURSE_ID_LIST = null;

        $active_user_course_id_list = Models_Course::getActiveUserCoursesIDList();
        if ($active_user_course_id_list) {
            $COURSE_ID_LIST = implode(",", $active_user_course_id_list);
        }

        if ($COURSE_ID_LIST) {
            if ($start_date != "" && !is_null($start_date)) {
                $AND_date_greater = " AND a.`delivery_date` >= " . $db->qstr($start_date);
                $AND_date_greater_approver = " AND c.`delivery_date` >= " . $db->qstr($start_date);
            }

            if ($end_date != "" && !is_null($end_date)) {
                $AND_date_less = " AND a.`delivery_date` <= " . $db->qstr($end_date);
                $AND_date_less_approver = " AND c.`delivery_date` <= " . $db->qstr($end_date);
            }

            if ($add_delegations) {
                $UNION_delegation = "
                UNION ALL 
                
                SELECT 0, 'delegation' AS task_type, a.`delivery_date` AS delivery_date, a.`completed_date` AS completed_date, 0
                FROM `cbl_assessment_distribution_delegations` AS a                    
                JOIN `cbl_assessment_distributions` AS b  
                ON b.`adistribution_id`  = a.`adistribution_id`                    
                WHERE a.`deleted_date` IS NULL
                AND b.`course_id` IN ({$COURSE_ID_LIST})
                AND b.`deleted_date` IS NULL
                AND b.`assessment_type` = " . $db->qstr($task_type) . "
                AND a.`delegator_id` = " . $db->qstr($assessor_id) . "  
                AND b.`visibility_status` = 'visible'
                                  
                $AND_date_greater
                $AND_date_less
                ";
            }

            if ($add_approver) {
                $UNION_approver = "
                UNION ALL
                
                SELECT 0, 'approver' AS task_type, c.`delivery_date` AS delivery_date, f.`created_date` AS completed_date, 0
                FROM `cbl_assessment_distribution_approvers` AS a
                JOIN `cbl_assessment_distributions` AS b
                ON a.`adistribution_id` = b.`adistribution_id`
                JOIN `cbl_distribution_assessments` AS c
                ON b.`adistribution_id` = c.`adistribution_id`                   
                JOIN `cbl_assessment_progress` AS e  
                ON c.`dassessment_id` = e.`dassessment_id`
                LEFT JOIN `cbl_assessment_progress_approvals` AS f
                ON e.`aprogress_id` = f.`aprogress_id`
                WHERE b.`deleted_date` IS NULL
                AND b.`course_id` IN ({$COURSE_ID_LIST})
                AND b.`assessment_type` = " . $db->qstr($task_type) . "
                AND e.`progress_value` = 'complete'
                AND b.`deleted_date` IS NULL
                AND c.`deleted_date` IS NULL
                AND e.`deleted_date` IS NULL
                AND a.`proxy_id` = " . $db->qstr($assessor_id) . "
                AND b.`visibility_status` = 'visible'
                
                $AND_date_greater_approver
                $AND_date_less_approver
                ";
            }

            if ($add_progress_update_date) {
                $SELECT_progress_updated_date = "
                (
                    SELECT f.`updated_date` FROM `cbl_assessment_progress_responses` AS f
                    WHERE d.`aprogress_id` = f.`aprogress_id`
                    ORDER BY f.`updated_date` DESC
                    LIMIT 1
                ) AS progress_updated_date,
                ";

                $UNION_delegation = "";
                $UNION_approver = "";
            }

            if ($assessor_id && $assessor_type) {
                $tasks_query = "
                SELECT
                
                $SELECT_progress_updated_date
                
                d.`progress_value`, 'task' AS task_type, a.`delivery_date` AS delivery_date, d.`updated_date` AS completed_date, a.`rotation_end_date`
                FROM `cbl_distribution_assessments` AS a
                JOIN `cbl_assessment_distributions` AS b
                ON a.`adistribution_id` = b.`adistribution_id`   
                JOIN `cbl_distribution_assessment_targets` AS at
                ON at.`dassessment_id` = a.`dassessment_id`               
                JOIN `courses` AS c
                ON b.`course_id` = c.`course_id`
                LEFT JOIN `cbl_assessment_progress` AS d  
                ON a.`dassessment_id` = d.`dassessment_id`                                      
                WHERE a.`published` = 1 
                AND b.`course_id` IN ({$COURSE_ID_LIST})
                AND b.`assessment_type` = " . $db->qstr($task_type) . "
                AND a.`deleted_date` IS NULL
                AND b.`deleted_date` IS NULL
                AND d.`deleted_date` IS NULL
                AND at.`deleted_date` IS NULL
                AND b.`visibility_status` = 'visible'
                AND (d.`progress_value` != 'cancelled' OR d.`progress_value` IS NULL)
                AND a.`assessor_value` = " . $db->qstr($assessor_id) . "
                AND a.`assessor_type` = " . $db->qstr($assessor_type) . "
                
                
                $UNION_delegation
                
                $UNION_approver
                 
                $AND_date_greater
                $AND_date_less
                
                ORDER BY 3 DESC
                ";

                $task_list = $db->GetAll($tasks_query);
            }
        }
        return $task_list;
    }

    public static function getAllDeletedTasksForAssociatedLearnersAssociatedFaculty($task_type, $organisation_id, $offset = 0, $limit = 10, $count = false, $search_value = null, $start_date = null, $end_date = null, $schedule_type = null) {
        global $db;

        $AND_TARGET_DATE_GREATER = $AND_DELEGATION_DATE_GREATER = $AND_TARGET_DATE_LESS = $AND_DELEGATION_DATE_LESS = $LIMIT = $OFFSET = $AND_TARGET_TITLE_LIKE = $AND_DELEGATION_TITLE_LIKE = $AND_SCHEDULE_TYPE = "";
        $COURSE_ID_LIST = $AND_TARGET_ASSESSOR = $AND_DELEGATION_ASSESSOR = null;

        if ($start_date != "" && $start_date != null) {
            $AND_TARGET_DATE_GREATER = " AND ad.`delivery_date` >= ". $db->qstr($start_date);
            $AND_DELEGATION_DATE_GREATER = " AND a.`delivery_date` >= ". $db->qstr($start_date);
        }

        if ($end_date != "" && $end_date != null) {
            $AND_TARGET_DATE_LESS = " AND ad.`delivery_date` <= ". $db->qstr($end_date);
            $AND_DELEGATION_DATE_LESS = " AND a.`delivery_date` <= ". $db->qstr($end_date);
        }

        if ($limit && !$count) {
            $LIMIT = " LIMIT $limit";
        }

        if ($offset && !$count) {
            $OFFSET = " OFFSET $offset";
        }

        if (!is_null($search_value) && $search_value != "") {
            $LIMIT = $OFFSET = "";
            $AND_TARGET_TITLE_LIKE      = " AND (ad.`title` LIKE (". $db->qstr("%". $search_value ."%") .") OR af.`title` LIKE (". $db->qstr("%". $search_value ."%") .") OR dat.`deleted_reason_notes` COLLATE utf8_unicode_ci  LIKE (". $db->qstr("%". $search_value ."%") .") OR dr.`reason_details` LIKE (". $db->qstr("%". $search_value ."%") .") )";
            $AND_DELEGATION_TITLE_LIKE  = " AND (ad.`title` LIKE (". $db->qstr("%". $search_value ."%") .") OR a.`deleted_reason_notes`   COLLATE utf8_unicode_ci  LIKE (". $db->qstr("%". $search_value ."%") .") OR e.`reason_details`  LIKE (". $db->qstr("%". $search_value ."%") .") )";
        }

        if (isset($schedule_type) && $schedule_type) {
            switch ($schedule_type) {
                case "distribution":
                    $AND_SCHEDULE_TYPE = " AND ad.`adistribution_id` IS NOT NULL";
                    break;
                case "triggered":
                    $AND_SCHEDULE_TYPE = " AND ad.`adistribution_id` IS NULL";
                    break;
                case "all":
                default:
                    break;
            }
        }

        $AND_ONLY_TERMINAL_ASSESSMENTS = "";
        $assessment_tasks = new Entrada_Assessments_Tasks();
        $method_type_ids = $assessment_tasks->fetchMultiPhaseAssessmentMethodTypes();
        if (is_array($method_type_ids) && !empty($method_type_ids)) {

            /* This clause excludes the self-assessment portion of multi-phase assessments.
             * Multi-phase assessments include "complete and confirm by pin", "confirm by email" and any other
             * assessment method that has multiple steps. By default,  we exclude the first step and only include
             * the terminal step; the initial step is the assessor filling out an assessment on
             * themselves and then asking another user to confirm it. On submission, the assessment is copied to a new assessor; we
             * only want to include the copied (terminal) version of this assessment. */

            $phased_assessment_method_ids = implode(",", $method_type_ids);
            $AND_ONLY_TERMINAL_ASSESSMENTS = "
                        AND NOT (da.`assessor_value` = dat.`target_value`
                            AND da.`assessor_type` = dat.`target_type`
                            AND da.`assessment_method_id` IN ($phased_assessment_method_ids)
                        )
                    ";
        }

        $SELECT_COUNT_START = "";
        $QUERY_END = " ORDER BY 12 DESC ";
        if ($count) {
            $SELECT_COUNT_START = " SELECT COUNT(*) FROM ( ";
            $QUERY_END = " ) AS COUNT ";
        }

        $USER_ID_LIST = Entrada_Utilities_Assessments_DeprecatedAssessmentTask::getAssociatedLearnerFacultyProxyList();
        $EXTERNAL_ID_LIST = Entrada_Utilities_Assessments_DeprecatedAssessmentTask::getAssociatedExternalIDList();

        $active_user_course_id_list = Models_Course::getActiveUserCoursesIDList();
        if ($active_user_course_id_list) {
            $COURSE_ID_LIST = implode(",", $active_user_course_id_list);
        }

        if (!empty($USER_ID_LIST) && $USER_ID_LIST != "" && !empty($EXTERNAL_ID_LIST) && $EXTERNAL_ID_LIST != "") {
            $AND_TARGET_ASSESSOR     = " AND ((da.`assessor_value` IN ({$USER_ID_LIST}) AND da.`assessor_type` = 'internal' OR da.`assessor_value` IN ({$EXTERNAL_ID_LIST}) AND da.`assessor_type` = 'external') OR dat.`target_value` IN ({$USER_ID_LIST}))";
            $AND_DELEGATION_ASSESSOR = " AND ((a.`delegator_id` IN ({$USER_ID_LIST}) AND a.`delegator_type` = 'proxy_id') OR (a.`delegator_id` IN ({$EXTERNAL_ID_LIST}) AND a.`delegator_type` = 'external_assessor_id'))";
        } else {
            if (!empty($USER_ID_LIST) && $USER_ID_LIST != "") {
                $AND_TARGET_ASSESSOR     = " AND ((da.`assessor_value` IN ({$USER_ID_LIST}) AND da.`assessor_type` = 'internal') OR dat.`target_value` IN ({$USER_ID_LIST}))";
                $AND_DELEGATION_ASSESSOR = " AND (a.`delegator_id` IN ({$USER_ID_LIST}) AND a.`delegator_type` = 'proxy_id')";
            }

            if (!empty($EXTERNAL_ID_LIST) && $EXTERNAL_ID_LIST != "") {
                $AND_TARGET_ASSESSOR     = " AND ((da.`assessor_value` IN ({$EXTERNAL_ID_LIST}) AND da.`assessor_type` = 'external') OR dat.`target_value` IN ({$USER_ID_LIST}))";
                $AND_DELEGATION_ASSESSOR = " AND (a.`delegator_id` IN ({$EXTERNAL_ID_LIST}) AND a.`delegator_type` = 'external_assessor_id')";
            }
        }

        if ($AND_TARGET_ASSESSOR && $AND_DELEGATION_ASSESSOR && $COURSE_ID_LIST) {
            $deleted_tasks_query = $SELECT_COUNT_START;
            $deleted_tasks_query .= "  
                SELECT a.`addelegation_id` AS deleted_task_id, ad.`title` AS `distribution_title`, NULL AS `form_title`, CONCAT(c.`firstname`, ' ', c.`lastname`) AS internal_full_name, CONCAT(d.`firstname`, ' ', d.`lastname`) AS external_full_name, a.`delegator_type` COLLATE utf8_unicode_ci  AS assessor_type, a.`delegator_id` AS assessor_value, a.`adistribution_id`, a.`deleted_reason_notes` COLLATE utf8_unicode_ci AS deleted_reason_notes, e.`reason_details`, a.`delivery_date`, a.`deleted_date`, 'delegation' AS task_type            
                FROM `cbl_assessment_distribution_delegations` AS a
                JOIN `cbl_assessment_distributions` AS ad
                ON a.`adistribution_id` = ad.`adistribution_id`
                LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS c
                ON a.`delegator_id` = c.`id` 
                LEFT JOIN `cbl_external_assessors` AS d
                ON a.`delegator_id` = d.`eassessor_id`  
                JOIN `cbl_assessment_lu_task_deleted_reasons` AS e
                ON a.`deleted_reason_id` = e.`reason_id`
                WHERE ad.`assessment_type` = " . $db->qstr($task_type) . "
                $AND_SCHEDULE_TYPE
                AND a.`visible` = 1
                AND ad.`organisation_id` = ".$db->qstr($organisation_id)."
                AND a.`deleted_date` IS NOT NULL
                AND a.`deleted_reason_id` IS NOT NULL
                AND ad.`course_id` IN ({$COURSE_ID_LIST})
               
                $AND_DELEGATION_ASSESSOR
                $AND_DELEGATION_TITLE_LIKE
                $AND_DELEGATION_DATE_GREATER
                $AND_DELEGATION_DATE_LESS
                
                UNION ALL
                
                SELECT dat.`atarget_id` AS deleted_task_id, ad.`title` AS `distribution_title`, af.`title` AS `form_title`, CONCAT(ud.`firstname`, ' ', ud.`lastname`) AS internal_full_name, CONCAT(ea.`firstname`, ' ', ea.`lastname`) AS external_full_name, da.`assessor_type` COLLATE utf8_unicode_ci AS assessor_type, da.`assessor_value` AS assessor_value, dat.`adistribution_id`, dat.`deleted_reason_notes` COLLATE utf8_unicode_ci AS deleted_reason_notes, dr.`reason_details`, da.`delivery_date`, dat.`deleted_date`, 'task' AS task_type                
                FROM `cbl_distribution_assessment_targets` AS dat
                LEFT JOIN `cbl_assessment_distributions` AS ad
                ON ad.`adistribution_id` = dat.`adistribution_id`
                JOIN `cbl_distribution_assessments` AS da  
                ON da.`dassessment_id` = dat.`dassessment_id`   
                JOIN `cbl_assessments_lu_forms` AS af
                ON af.`form_id` = da.`form_id`
                LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS ud
                ON ud.`id` = da.`assessor_value` 
                LEFT JOIN `cbl_external_assessors` AS ea
                ON ea.`eassessor_id` = da.`assessor_value` 
                JOIN `cbl_assessment_lu_task_deleted_reasons` AS dr
                ON dr.`reason_id` = dat.`deleted_reason_id`
                WHERE dat.`target_type` = 'proxy_id'
                $AND_ONLY_TERMINAL_ASSESSMENTS
                $AND_SCHEDULE_TYPE
                AND dat.`task_type` = " . $db->qstr($task_type) . "
                AND da.`organisation_id` = ".$db->qstr($organisation_id)."
                AND dat.`visible` = 1
                AND (ad.`visibility_status` IS NULL OR ad.`visibility_status` = 'visible')
                AND dat.`deleted_date` IS NOT NULL
                AND dat.`deleted_reason_id` IS NOT NULL
                AND da.`course_id` IN ({$COURSE_ID_LIST})
               
                $AND_TARGET_ASSESSOR
                $AND_TARGET_TITLE_LIKE
                $AND_TARGET_DATE_GREATER
                $AND_TARGET_DATE_LESS
                
                $QUERY_END
                
                $LIMIT $OFFSET
                ";

            $results = $db->GetAll($deleted_tasks_query);

            if ($results) {
                foreach ($results as $key => $task) {
                    if (isset($task["distribution_title"]) && $task["distribution_title"]) {
                        $task["title"] = $task["distribution_title"];
                    } else {
                        $task["title"] = (isset($task["form_title"]) && $task["form_title"] ? $task["form_title"] : "");
                    }
                    $results[$key] = $task;
                }
            }

            return $results;
        }
        return array();
    }
}