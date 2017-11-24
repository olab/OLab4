<?php

class Entrada_Utilities_Assessments_AssessmentTask extends Entrada_Utilities_Assessments_Base {
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

    public function getTargetInfo() {
        return $this->target_info;
    }

    public function getCompletedDate() {
        return $this->completed_date;
    }

    public static function getAssessmentProgressOnUser($target_record_id = null, $organisation_id = null, $current_section = "assessments", $completed_by_faculty = true, $release_status = 1, $filters = array(), $search_value = null, $start_date = null, $end_date = null, $limit = 0, $offset = 0) {
        global $db;
        $tasks = array(
            "inprogress" => array(),
            "complete" => array()
        );

        $course_id_list = Models_Course::getActiveUserCoursesIDList();

        $AND_course_in = ($current_section == "assessments" || empty($course_id_list) ? " " : "  AND e.`course_id` IN (" . implode(",", $course_id_list) . ") ");
        $AND_cperiod_in = "";
        $AND_course_filter_in = "";
        $AND_title_like = "";
        $AND_date_greater = "";
        $AND_date_less = "";
        $LIMIT = "";
        $OFFSET = "";

        $AND_release_status = "";
        if ($release_status !== null && $release_status !== false) {
            $AND_release_status = "AND (f.`release_status` = '$release_status' OR g.`proxy_id` IS NULL)";
        }

        if ($filters) {
            if (array_key_exists("cperiod", $filters)) {
                $AND_cperiod_in = " AND e.`cperiod_id` IN (" . implode(",", array_keys($filters["cperiod"])) . ") ";
            }

            if (array_key_exists("program", $filters)) {
                $AND_course_filter_in = "  AND e.`course_id` IN (" . implode(",", array_keys($filters["program"])) . ") ";
            }
        }

        if ($search_value != "" && $search_value != null) {
            $AND_title_like = "     AND e.`title` LIKE (". $db->qstr("%". $search_value ."%") .") ";
        }

        if ($start_date != "" && $start_date != null) {
            $AND_date_greater = "   AND b.`delivery_date` >= ". $db->qstr($start_date) . "";
        }

        if ($end_date != "" && $end_date != null) {
            $AND_date_less = "      AND b.`delivery_date` <= ". $db->qstr($end_date) . "";
        }

        if ($limit) {
            $LIMIT = " LIMIT $limit";
        }

        if ($offset) {
            $OFFSET = " OFFSET $offset";
        }

        // Internal Assessments

        $query = "  SELECT a.*, b.`dassessment_id`, b.`delivery_date`, b.`rotation_start_date`, b.`rotation_end_date`, c.`user_id`, c.`group`, c.`role`, d.`firstname`, d.`lastname`, d.`email`, e.`organisation_id` 
                    FROM `cbl_assessment_progress` AS a                    
                    JOIN `cbl_distribution_assessments` AS b
                    ON a.`dassessment_id` = b.`dassessment_id`
                    JOIN `" . AUTH_DATABASE . "`.`user_access` AS c
                    ON a.`assessor_value` =  c.`user_id`
                    JOIN `" . AUTH_DATABASE . "`.`user_data` AS d
                    ON c.`user_id` = d.`id`
                    JOIN `cbl_assessment_distributions` AS e
                    ON a.`adistribution_id` = e.`adistribution_id`
                    LEFT JOIN `cbl_assessment_progress_approvals` AS f
                    ON a.`aprogress_id` = f.`aprogress_id`
                    LEFT JOIN `cbl_assessment_distribution_approvers` AS g
                    ON a.`adistribution_id` = g.`adistribution_id`                  
                    JOIN `courses` AS h
                    ON e.`course_id` = h.`course_id`                
                    WHERE a.`target_record_id` = ?
                    AND a.`assessor_type` = 'internal'
                    AND (a.`progress_value` = 'complete' OR a.`progress_value` = 'inprogress')
                    AND e.`organisation_id` = ?

                    $AND_release_status 
                    $AND_course_in
                    $AND_course_filter_in
                    $AND_cperiod_in
                    $AND_title_like
                    $AND_date_greater
                    $AND_date_less
                    
                    GROUP BY a.`aprogress_id`
                    ORDER BY b.`delivery_date` DESC, b.`rotation_start_date` DESC, b.`rotation_end_date` DESC
                    $LIMIT $OFFSET
                    ";

        $internal_tasks = $db->GetAll($query, array($target_record_id, $organisation_id));
        $target_user = Models_User::fetchRowByID($target_record_id);

        if ($internal_tasks && $target_user) {
            foreach ($internal_tasks as $task) {
                $target_record = Models_Assessments_Distribution_Target::fetchRowByID($task["adtarget_id"]);
                // If no distribution target record was found, it is possible that an additional task created the progress record.
                $additional_task = false;
                if (!$target_record) {
                    $additional_task = Models_Assessments_AdditionalTask::fetchRowDistributionIDTargetID($task["adistribution_id"], $target_record_id);
                }
                if ($target_record || $additional_task) {
                    $target_learner = true;
                    if ($target_record) {
                        if (($target_record->getTargetType() === "schedule_id" && $target_record->getTargetScope() === "self") || ($target_record->getTargetType() === "course_id" && $target_record->getTargetScope() === "self")) {
                            $target_learner = false;
                        }
                    }

                    if ($target_learner) {
                        $distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($task["adistribution_id"]);
                        if ($distribution) {
                            $progress_record = Models_Assessments_Progress::fetchRowByAdistributionIDAssessorTypeAssessorValueTargetRecordID($distribution->getID(), "internal", $task["assessor_value"], $target_record_id, null, $task["dassessment_id"]);
                            if ($progress_record) {
                                $target_user_model = Models_User::fetchRowByID($progress_record->getTargetRecordID());

                                // Ensure the task has not been deleted
                                $deleted_task = Models_Assessments_DeletedTask::fetchRowByADistributionIDAssessorTypeAssessorValueTargetIDDeliveryDate($task["adistribution_id"], "internal", $progress_record->getAssessorValue(), $target_record_id, $task["delivery_date"]);
                                if (!$deleted_task && $target_user_model) {
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
                                    $assessor = Models_Assessments_Assessor::fetchRowByID($task["dassessment_id"]);

                                    if ($assessor && $assessor->getAssociatedRecordType() == "event_id") {
                                        $assessment_sub_type = "learning_event";
                                    } else if (is_null($task["rotation_start_date"]) || $task["rotation_start_date"] == "" || !$task["rotation_end_date"]) {
                                        $assessment_sub_type = "date_range";
                                    }

                                    $tasks[$task["progress_value"]][] = new Entrada_Utilities_Assessments_AssessmentTask(array(
                                        "dassessment_id" => $task["dassessment_id"],
                                        "type" => "assessment",
                                        "assessment_sub_type" => $assessment_sub_type,
                                        "target_id" => $target_record_id,
                                        "title" => $distribution->getTitle(),
                                        "description" => ($distribution->getDescription() ? $distribution->getDescription() : "No details provided."),
                                        "url" => ENTRADA_URL . "/assessments/assessment?adistribution_id=" . html_encode($distribution->getID()) . "&target_record_id=" . html_encode($target_record_id) . "&aprogress_id=" . html_encode($progress_record->getID()) . "&dassessment_id=" . html_encode($task["dassessment_id"]),
                                        "start_date" => $distribution->getStartDate(),
                                        "end_date" => $distribution->getEndDate(),
                                        "delivery_date" => ($task["delivery_date"] ? $task["delivery_date"] : false),
                                        "rotation_start_date" => $task["rotation_start_date"],
                                        "rotation_end_date" => $task["rotation_end_date"],
                                        "schedule_details" => ($schedule_string ? $schedule_string : ""),
                                        "adistribution_id" => $distribution->getID(),
                                        "assessor" => $task["firstname"] . " " . $task["lastname"],
                                        "assessor_type" => $task["assessor_type"],
                                        "assessor_value" => $task["assessor_value"],
                                        "group" => $task["group"],
                                        "role" => $task["role"],
                                        "cperiod_id" => $distribution->getCperiodID(),
                                        "aprogress_id" => $progress_record->getID(),
                                        "completed_date" => $task_completed_date,
                                        "target_names" => array("complete" => $target_user->getFullname(false)),
                                        "target_info" => array(array("name" => $target_user_model->getFullname(false), "proxy_id" => $progress_record->getTargetRecordID(), "progress" => array($progress_record->getProgressValue()), "aprogress_id" => $progress_record->getID()))
                                    ));
                                }
                            }
                        }
                    }
                }
            }
        }

        $AND_course_in = ($current_section == "assessments" ? " " : "  AND d.`course_id` IN (" . implode(",", $course_id_list) . ") ");
        $AND_cperiod_in = "";
        $AND_course_filter_in = "";
        $AND_title_like = "";
        $AND_date_greater = "";
        $AND_date_less = "";
        $LIMIT = "";
        $OFFSET = "";

        if ($filters) {
            if (array_key_exists("cperiod", $filters)) {
                $AND_cperiod_in = " AND d.`cperiod_id` IN (" . implode(",", array_keys($filters["cperiod"])) . ") ";
            }

            if (array_key_exists("program", $filters)) {
                $AND_course_filter_in = "  AND d.`course_id` IN (" . implode(",", array_keys($filters["program"])) . ") ";
            }
        }

        if ($search_value != "" && $search_value != null) {
            $AND_title_like = "     AND d.`title` LIKE (". $db->qstr("%". $search_value ."%") .") ";
        }

        if ($start_date != "" && $start_date != null) {
            $AND_date_greater = "   AND b.`delivery_date` >= ". $db->qstr($start_date) . "";
        }

        if ($end_date != "" && $end_date != null) {
            $AND_date_less = "      AND b.`delivery_date` <= ". $db->qstr($end_date) . "";
        }

        if ($limit) {
            $LIMIT = " LIMIT $limit";
        }

        if ($offset) {
            $OFFSET = " OFFSET $offset";
        }

        // External Assessments

        $query = "  SELECT a.*, b.`dassessment_id`, b.`delivery_date`, b.`rotation_start_date`, b.`rotation_end_date`, c.`eassessor_id`, c.`firstname`, c.`lastname`, c.`email` 
                    FROM `cbl_assessment_progress` AS a
                    JOIN `cbl_distribution_assessments` AS b
                    ON a.`dassessment_id` = b.`dassessment_id`
                    JOIN `cbl_external_assessors` AS c
                    ON a.`assessor_value` =  c.`eassessor_id`
                    JOIN `cbl_assessment_distributions` AS d
                    ON a.`adistribution_id` = d.`adistribution_id`                
                    JOIN `courses` AS e
                    ON d.`course_id` = e.`course_id`                    
                    WHERE a.`target_record_id` = ?
                    AND a.`assessor_type` = 'external'
                    AND (a.`progress_value` = 'complete' OR a.`progress_value` = 'inprogress')
                    
                    $AND_course_in
                    $AND_course_filter_in
                    $AND_cperiod_in
                    $AND_title_like
                    $AND_date_greater
                    $AND_date_less
                    
                    $LIMIT $OFFSET
                    ";

        $external_tasks = $db->GetAll($query, array($target_record_id));

        if ($external_tasks && $target_user) {
            foreach ($external_tasks as $task) {
                $target_record = Models_Assessments_Distribution_Target::fetchRowByID($task["adtarget_id"]);
                // If no distribution target record was found, it is possible that an additional task created the progress record.
                $additional_task = false;
                if (!$target_record) {
                    $additional_task = Models_Assessments_AdditionalTask::fetchRowDistributionIDTargetID($task["adistribution_id"], $target_record_id);
                }
                if ($target_record || $additional_task) {
                    $target_learner = true;
                    if ($target_record) {
                        if (($target_record->getTargetType() === "schedule_id" && $target_record->getTargetScope() === "self") || ($target_record->getTargetType() === "course_id" && $target_record->getTargetScope() === "self")) {
                            $target_learner = false;
                        }
                    }

                    if ($target_learner) {
                        $distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($task["adistribution_id"]);
                        if ($distribution) {
                            $progress_record = Models_Assessments_Progress::fetchRowByAdistributionIDAssessorTypeAssessorValueTargetRecordID($distribution->getID(), "external", $task["assessor_value"], $target_record_id, null, $task["dassessment_id"]);
                            if ($progress_record) {
                                $target_user_model = Models_User::fetchRowByID($progress_record->getTargetRecordID());

                                // Ensure the task has not been deleted
                                $deleted_task = Models_Assessments_DeletedTask::fetchRowByADistributionIDAssessorTypeAssessorValueTargetIDDeliveryDate($task["adistribution_id"], "external", $progress_record->getAssessorValue(), $target_record_id, $task["delivery_date"]);
                                if (!$deleted_task && $target_user_model) {
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
                                    $assessor = Models_Assessments_Assessor::fetchRowByID($task["dassessment_id"]);

                                    if ($assessor && $assessor->getAssociatedRecordType() == "event_id") {
                                        $assessment_sub_type = "learning_event";
                                    } else if (is_null($task["rotation_start_date"]) || $task["rotation_start_date"] == "" || !$task["rotation_end_date"]) {
                                        $assessment_sub_type = "date_range";
                                    }

                                    $tasks[$task["progress_value"]][] = new Entrada_Utilities_Assessments_AssessmentTask(array(
                                        "dassessment_id" => $task["dassessment_id"],
                                        "type" => "assessment",
                                        "assessment_sub_type" => $assessment_sub_type,
                                        "target_id" => $target_record_id,
                                        "title" => $distribution->getTitle(),
                                        "description" => ($distribution->getDescription() ? $distribution->getDescription() : "No details provided."),
                                        "url" => ENTRADA_URL . "/assessments/assessment?adistribution_id=" . html_encode($distribution->getID()) . "&target_record_id=" . html_encode($target_record_id) . "&aprogress_id=" . html_encode($progress_record->getID()) . "&dassessment_id=" . html_encode($task["dassessment_id"]),
                                        "start_date" => $distribution->getStartDate(),
                                        "end_date" => $distribution->getEndDate(),
                                        "delivery_date" => ($task["delivery_date"] ? $task["delivery_date"] : false),
                                        "rotation_start_date" => $task["rotation_start_date"],
                                        "rotation_end_date" => $task["rotation_end_date"],
                                        "schedule_details" => ($schedule_string ? $schedule_string : ""),
                                        "adistribution_id" => $distribution->getID(),
                                        "assessor" => $task["firstname"] . " " . $task["lastname"],
                                        "assessor_type" => $task["assessor_type"],
                                        "assessor_value" => $task["assessor_value"],
                                        "group" => "external",
                                        "role" => "external",
                                        "cperiod_id" => $distribution->getCperiodID(),
                                        "aprogress_id" => $progress_record->getID(),
                                        "completed_date" => $task_completed_date,
                                        "target_names" => array("complete" => $target_user->getFullname(false)),
                                        "target_info" => array(array("name" => $target_user_model->getFullname(false), "proxy_id" => $progress_record->getTargetRecordID(), "progress" => array($progress_record->getProgressValue()), "aprogress_id" => $progress_record->getID()))
                                    ));
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($tasks) {
            // Sort tasks by rotation dates and delivery date.
            foreach ($tasks as $progress_key => $progress_tasks) {
                if ($progress_tasks) {
                    $sort = array();
                    foreach ($progress_tasks as $key => $task) {
                        $sort["delivery_date"][$key] = $task->getDeliveryDate();
                        $sort["rotation_start_date"][$key] = $task->getRotationStartDate();
                        $sort["rotation_end_date"][$key] = $task->getRotationEndDate();
                    }
                    array_multisort($sort["delivery_date"], SORT_DESC, $sort["rotation_start_date"], SORT_DESC, $sort["rotation_end_date"], SORT_DESC, $tasks[$progress_key]);
                }
            }
        }

        return $tasks;
    }

    public static function getAllTasks($proxy_id, $current_section = "assessments", $is_external = false, $exclude_completed = false, $limit = 0, $offset = 0) {
        $tasks = array();

        $assessment_tasks = Entrada_Utilities_Assessments_AssessmentTask::getAllAssessmentTasksByProxyID($proxy_id, $current_section, $is_external, array(), null, null, null, $limit, $offset);
        if ($assessment_tasks) {
            $tasks = array_merge($tasks, $assessment_tasks);
        }

        $delegation_tasks = Entrada_Utilities_Assessments_AssessmentTask::getAllDelegationTasksByProxyID($proxy_id, $current_section, array(), null, null, null, $exclude_completed, $limit, $offset);
        if ($delegation_tasks) {
            $tasks = array_merge($tasks, $delegation_tasks);
        }

        $approver_tasks = Entrada_Utilities_Assessments_AssessmentTask::getAllApproverTasksByProxyID($proxy_id, $current_section, array(), null, null, null, $exclude_completed, $limit, $offset);
        if ($approver_tasks) {
            $tasks = array_merge($tasks, $approver_tasks);
        }

        return $tasks;
    }

    private static function getAllAssessmentTasksByProxyID($proxy_id, $current_section = "assessments",  $is_external = false, $filters = array(), $search_value = null, $start_date = null, $end_date = null, $limit = 0, $offset = 0) {
        $tasks = array();
        $assessment_tasks = Models_Assessments_Assessor::fetchAllByAssessorValue($proxy_id, $current_section, $is_external, $filters, $search_value, $start_date, $end_date, $limit, $offset);
        if ($assessment_tasks) {
            foreach ($assessment_tasks as $assessment) {
                $schedule_string = "";
                $event_string = "";
                $target_type_singular = "";
                $delegator_name = "";
                $event_timeframe_start_string = "";
                $event_timeframe_end_string = "";
                $assessment_sub_type = "rotation_schedule";
                $max_individual_attempts = 0;
                $max_overall_attempts = 0;

                $distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($assessment->getAdistributionID());
                if ($distribution) {
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
                                $target_type_singular = "learner";
                                $target_type_plural = "learners";
                            } elseif ($distribution_target->getTargetType() == "faculty") {
                                $target_type_singular = "faculty member";
                                $target_type_plural = "faculty members";
                            } elseif ($distribution_target->getTargetType() == "self") {
                                $target_type_singular = "self";
                                $target_type_plural = "self";
                            } elseif ($distribution_target->getTargetType() == "eventtype_id") {
                                $target_type_singular = "learning event";
                                $target_type_plural = "learning events";
                            }
                            if (!$target_type_singular && $distribution_target->getTargetType() == "schedule_id" && (in_array($distribution_target->getTargetScope(), array("self", "children")))) {
                                $target_type_singular = "rotation";
                                $target_type_plural = "blocks";
                            }
                        }
                        if (!isset($target_type_singular) || !$target_type_singular) {
                            $target_type_singular = "individual";
                            $target_type_plural = "individuals";
                        }
                    }
                    $targets = Models_Assessments_Distribution_Target::getAssessmentTargets($assessment->getAdistributionID(), $assessment->getID(), $proxy_id, $proxy_id, $is_external);
                    if ($targets) {
                        $url = ENTRADA_URL . "/assessments";
                        $target_id = NULL;
                        $progress_id = NULL;
                        $pending_target_names = array();
                        $inprogress_target_names = array();
                        $complete_target_names = array();
                        $targets_pending = 0;
                        $targets_inprogress = 0;
                        $targets_complete = 0;
                        $completed_attempts = 0;
                        $first_target = false;

                        if ($distribution->getSubmittableByTarget()) {
                            $max_individual_attempts = $distribution->getMaxSubmittable();
                            $max_overall_attempts = ($max_individual_attempts * (@count($targets)));
                        } else {
                            if ($distribution->getRepeatTargets()) {
                                $max_individual_attempts = $distribution->getMaxSubmittable();
                            } else {
                                $max_individual_attempts = 1;
                            }
                            $max_overall_attempts = $distribution->getMaxSubmittable();
                        }

                        foreach ($targets as $target) {
                            $completed_attempts += (isset($target["completed_attempts"]) && $target["completed_attempts"] ? $target["completed_attempts"] : 0);
                            if (array_key_exists("progress", $target) && is_array($target["progress"])) {
                                foreach ($target["progress"] as $progress_value) {
                                    switch ($progress_value) {
                                        case "pending" :
                                            $targets_pending++;
                                            $pending_target_names[] = $target["name"];
                                            break;
                                        case "inprogress" :
                                            $targets_inprogress++;
                                            $first_target = $target;
                                            $inprogress_target_names[] = $target["name"];
                                            break;
                                        case "complete" :
                                            $targets_complete++;
                                            $complete_target_names[] = $target["name"];
                                            break;
                                    }
                                }
                            }
                        }

                        if (count($targets) > 1) {
                                $url = ENTRADA_URL . "/assessments/assessment?section=targets&adistribution_id=" . $distribution->getID() . "&dassessment_id=" . $assessment->getID();
                            } else {
                                if ($first_target) {
                                    $url = ENTRADA_URL . "/assessments/assessment?adistribution_id=" . html_encode($distribution->getID()) . "&target_record_id=" . html_encode($first_target["target_record_id"]) . (isset($first_target["aprogress_id"]) ? "&aprogress_id=" . html_encode($first_target["aprogress_id"]) : "") . "&dassessment_id=" . $assessment->getID();
                                    $target_id = $first_target["target_record_id"];
                                    $progress_id = isset($first_target["aprogress_id"]) ? $first_target["aprogress_id"] : NULL;
                                } else {
                                    $url = ENTRADA_URL . "/assessments/assessment?adistribution_id=" . html_encode($distribution->getID()) . "&target_record_id=" . html_encode($targets[0]["target_record_id"]) . (isset($targets[0]["aprogress_id"]) ? "&aprogress_id=" . html_encode($targets[0]["aprogress_id"]) : "") . "&dassessment_id=" . $assessment->getID();
                                    $target_id = $targets[0]["target_record_id"];
                                    $progress_id = isset($targets[0]["aprogress_id"]) ? $targets[0]["aprogress_id"] : NULL;
                                }
                            }

                        $task_completed_date = NULL;

                        if (!is_null($progress_id)) {
                            $task_progress = Models_Assessments_Progress::fetchRowByID($progress_id);
                            if ($task_progress && $task_progress->getProgressValue() == "complete" && !is_null($task_progress->getUpdatedDate())) {
                                $task_completed_date = $task_progress->getUpdatedDate();
                            }
                        }

                        if ($target_type_singular == "self") {
                            $details_string = ($max_overall_attempts - $completed_attempts) . " self assessment attempts available for completion.";
                        } else {
                            if (@count($targets) > 1) {
                                if ($distribution->getSubmittableByTarget()) {
                                    $details_string = $max_individual_attempts . " attempts available for completion for each " . $target_type_singular . ". " . $completed_attempts . " attempts completed of the " . $max_overall_attempts . " total attempts available for " . count($targets) . " " . $target_type_plural . ".";
                                } else {
                                    $details_string = $completed_attempts . " attempts completed of the " . $max_overall_attempts . " total attempts available for " . count($targets) . " " . $target_type_plural . ".";
                                }
                            } else {
                                $details_string = $completed_attempts . " attempts completed of the " . $max_overall_attempts . " total attempts available for a single " . $target_type_singular . ".";
                            }
                        }

                        $assessor = Models_User::fetchRowByID($assessment->getAssessorValue());

                        if ($distribution->getVisibilityStatus() == "visible" && $assessor) {
                            $tasks[] = new Entrada_Utilities_Assessments_AssessmentTask(array(
                                "dassessment_id" => $assessment->getID(),
                                "published" => $assessment->getPublished(),
                                "type" => "assessment",
                                "title" => $distribution->getTitle(),
                                "description" => ($distribution->getDescription() ? $distribution->getDescription() : "No details provided."),
                                "details" => ($details_string ? $details_string : "No details provided."),
                                "event_details" => $event_string ? $event_string : "",
                                "schedule_details" => ($schedule_string ? $schedule_string : ""),
                                "url" => $url,
                                "target_id" => $target_id,
                                "assessor" => $assessor->getFullname(false),
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
                                    "total" => count($targets),
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
                                "target_info" => $targets
                            ));
                        }
                    }
                }
            }

            if ($tasks) {
                // Sort tasks by rotation dates and delivery date.
                $sort = array();
                foreach ($tasks as $key => $task) {
                    $sort["delivery_date"][$key] = $task->getDeliveryDate();
                    $sort["rotation_start_date"][$key] = $task->getRotationStartDate();
                    $sort["rotation_end_date"][$key] = $task->getRotationEndDate();
                }
                array_multisort($sort["delivery_date"], SORT_DESC, $sort["rotation_start_date"], SORT_DESC, $sort["rotation_end_date"], SORT_DESC, $tasks);
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
                        $tasks[] = new Entrada_Utilities_Assessments_AssessmentTask(array(
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
        global $ENTRADA_USER;
        $tasks = array();

        $distribution_approver = new Models_Assessments_Distribution_Approver();
        $approver_tasks = $distribution_approver->fetchAllByProxyID($proxy_id, $current_section, $filters, $search_value, $limit, $offset);
        if ($approver_tasks) {
            foreach ($approver_tasks as $approver_task) {

                $assessment_tasks = Models_Assessments_Assessor::fetchAllByDistributionID($approver_task->getAdistributionID(), null, null, $start_date, $end_date);
                $distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($approver_task->getAdistributionID());
                if ($assessment_tasks && $distribution) {

                    foreach ($assessment_tasks as $assessment) {
                        $targets = Models_Assessments_Distribution_Target::getAssessmentTargets($assessment->getAdistributionID(), $assessment->getID(), $assessment->getAssessorValue(), $assessment->getAssessorValue(), $assessment->getAssessorType() == "external");
                        if ($targets) {
                            foreach ($targets as $target) {
                                $complete_target_names = array();
                                if (array_key_exists("progress", $target) && is_array($target["progress"]) && isset($target["aprogress_id"])) {
                                    foreach ($target["progress"] as $progress_value) {
                                        if ($progress_value == "complete") {
                                            $event_string = "";
                                            $event_timeframe_start_string = "";
                                            $event_timeframe_end_string = "";
                                            $schedule_string = "";
                                            $delegator_name = "";
                                            $target_id = $target["target_record_id"];
                                            $url = ENTRADA_URL . "/assessments/assessment?adistribution_id=" . html_encode($distribution->getID()) . "&target_record_id=" . html_encode($target["target_record_id"]) . (isset($target["aprogress_id"]) ? "&aprogress_id=" . html_encode($target["aprogress_id"]) : "") . "&dassessment_id=" . $assessment->getID();

                                            $approver_approvals = new Models_Assessments_Distribution_Approvals();
                                            $approver_record = $approver_approvals->fetchRowByProgressIDDistributionID($target["aprogress_id"], $distribution->getAdistributionID());
                                            $assessor_user = Models_User::fetchRowByID($assessment->getAssessorValue());

                                            $include = true;
                                            // Check to see if this record is complete and should be excluded (meaning it has a release).
                                            if ($approver_record && $exclude_completed) {
                                                $include = false;
                                            }

                                            if ($include) {
                                                if (!$approver_record) {
                                                    $url .= ($ENTRADA_USER->getActiveID() == $proxy_id) ? "&approver_task=true" : "&approver_task_review=true";
                                                    $progress = Models_Assessments_Progress::fetchRowByID($target["aprogress_id"]);
                                                    $details_string = "Please review this assessment task that was completed by " . $assessor_user->getFullname(false) . " for " . $target["name"] . " on " . date("M j, Y", $progress->getUpdatedDate());
                                                    $active_user_progress_value = "pending";
                                                } else {
                                                    $url .= "&approver_task_completed=true";
                                                    $released_user = Models_User::fetchRowByID($approver_record->getApproverID());
                                                    $details_string = "This assessment task created by " . $assessor_user->getFullname(false) . " for " . $target["name"] . " was reviewed by " . $released_user->getFullname(false) . " on " . date("M j, Y", $approver_record->getCreatedDate());
                                                    $active_user_progress_value = "complete";
                                                }

                                                $complete_target_names[] = $target["name"];

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

                                                $tasks[] = new Entrada_Utilities_Assessments_AssessmentTask(array(
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
                                                    "assessor" => $assessor_user->getFullname(false),
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
                                                    "aprogress_id" => $target["aprogress_id"],
                                                    "target_names" => array("complete" => implode(", ", $complete_target_names)),
                                                    "target_info" => array(array("name" => $target["name"], "proxy_id" => $target_id, "progress" => array($active_user_progress_value), "aprogress_id" => $target["aprogress_id"]))
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
                $assessment_tasks = Entrada_Utilities_Assessments_AssessmentTask::getAllAssessmentTasksByProxyID($proxy_id, $current_section, $is_external, $filters, $search_value, $start_date, $end_date, $limit, $offset);
                if ($assessment_tasks) {
                    $tasks = array_merge($tasks, $assessment_tasks);
                }
            }

            if ($run_all_queries || $distribution_methods_list["delegation"]) {
                $delegation_tasks = Entrada_Utilities_Assessments_AssessmentTask::getAllDelegationTasksByProxyID($proxy_id, $current_section, $filters, $search_value, $start_date, $end_date, $exclude_completed, $limit, $offset);
                if ($delegation_tasks) {
                    $tasks = array_merge($tasks, $delegation_tasks);
                }
            }

            if ($run_all_queries) {
                $approver_tasks = Entrada_Utilities_Assessments_AssessmentTask::getAllApproverTasksByProxyID($proxy_id, $current_section, $filters, $search_value, $start_date, $end_date, $exclude_completed, $limit, $offset);
                if ($approver_tasks) {
                    $tasks = array_merge($tasks, $approver_tasks);
                }
            }

            if (!$run_all_queries) {
                Entrada_Utilities_Assessments_AssessmentTask::removeTasksBySubType($tasks, $filters);
            }
        } else {
            $tasks = Entrada_Utilities_Assessments_AssessmentTask::getAllTasks($proxy_id, $current_section, $is_external, $exclude_completed, $limit, $offset);
        }

        Entrada_Utilities_Assessments_AssessmentTask::removeTasksByPageTab($tasks, $current_page, $is_external);

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
                if ($task->getType() == "assessment" && !$is_external && $task->getMaxOverallAttempts() > $task->getCompletedAttempts() || $task->getType() == "assessment" && $is_external && !$progress_record || $task->getType() == "assessment" && $is_external && $progress_record && $progress_record->getProgressValue() != "complete") {
                    $keep_task = true;
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
                    $target = clean_input($target, array("int"));

                    switch ($filter_type) {
                        case "program" :
                            $course = Models_Course::get($target);
                            if ($course) {
                                $target_label = $course->getCourseName();
                            }
                            break;
                        case "distribution_method" :
                            $method = $distribution_methods->fetchRowByID($target);
                            $target_label = $translate->_($method->getTitle());
                            break;
                        case "cperiod" :
                            $cperiod = Models_Curriculum_Period::fetchRowByID($target);
                            if ($cperiod) {
                                $target_label = ($cperiod->getCurriculumPeriodTitle() ? $cperiod->getCurriculumPeriodTitle() : date("M j, Y", $cperiod->getStartDate()) . " to " . date("M j, Y", $cperiod->getFinishDate()));
                            }
                            break;
                    }
                    $_SESSION[APPLICATION_IDENTIFIER][$section]["tasks"]["selected_filters"][$filter_type][$target] = $target_label;
                }
            }
        }
    }

    public static function countAllIncompleteAssessmentTasks($proxy_id = null) {
        $incomplete_assessment_task = 0;
        $assessment_tasks = Entrada_Utilities_Assessments_AssessmentTask::getAllTasks($proxy_id);
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
                if (in_array($ENTRADA_USER->getActiveId(), Entrada_Utilities_Assessments_AssessmentTask::getFacultyProxyIDAccessWhitelist())) {
                    $return_override = true;
                } else {
                    $return_override = false;
                }
            } else {
                // otherwise, we check if the viewer actually owns the course
                $return_override = Entrada_Utilities_Assessments_AssessmentTask::isCourseOwner($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation());
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
            if (in_array($ENTRADA_USER->getActiveId(), Entrada_Utilities_Assessments_AssessmentTask::getFacultyProxyIDAccessWhitelist())) {
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

        Entrada_Utilities_Assessments_AssessmentTask::saveFilterPreferences($PROCESSED["filters"], $section);
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
                    FROM `group_members` as a
                    JOIN `course_audience` as b
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
        global $db;
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
    
    public static function getAllTasksForAssociatedLearnersAssociatedFaculty($task_type, $offset = 0, $limit = 10, $count = false, $search_value = null) {
        global $db;
        $task_list = array();

        $LIMIT = $OFFSET = $AND_TITLE_LIKE_TASK = $AND_TITLE_LIKE_DELEGATION = $AND_TITLE_LIKE_APPROVER = "";

        if ($limit && !$count) {
            $LIMIT = " LIMIT $limit";
        }

        if ($offset && !$count) {
            $OFFSET = " OFFSET $offset";
        }

        if (!is_null($search_value) && $search_value != "") {
            $LIMIT = "";
            $OFFSET = "";
            $AND_TITLE_LIKE_TASK        = " AND (b.`title` LIKE (". $db->qstr("%". $search_value ."%") .") OR CONCAT(e.`firstname`, ' ', e.`lastname`) LIKE (". $db->qstr("%". $search_value ."%") .") )";
            $AND_TITLE_LIKE_DELEGATION  = " AND (b.`title` LIKE (". $db->qstr("%". $search_value ."%") .") OR CONCAT(c.`firstname`, ' ', c.`lastname`) LIKE (". $db->qstr("%". $search_value ."%") .") )";
            $AND_TITLE_LIKE_APPROVER    = " AND (b.`title` LIKE (". $db->qstr("%". $search_value ."%") .") OR CONCAT(d.`firstname`, ' ', d.`lastname`) LIKE (". $db->qstr("%". $search_value ."%") .") )";
        }

        $SELECT_COUNT_START = "";
        $QUERY_END = " ORDER BY 11 DESC ";
        if ($count) {
            $SELECT_COUNT_START = " SELECT COUNT(*) FROM ( ";
            $QUERY_END = " ) AS COUNT ";
        }

        $USER_ID_LIST = Entrada_Utilities_Assessments_AssessmentTask::getAssociatedLearnerFacultyProxyList();
        $EXTERNAL_ID_LIST = Entrada_Utilities_Assessments_AssessmentTask::getAssociatedExternalIDList();
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
            SELECT b.`title`, CONCAT(e.`firstname`, ' ', e.`lastname`) AS internal_full_name, CONCAT(f.`firstname`, ' ', f.`lastname`) AS external_full_name, a.`assessor_type`, a.`assessor_value`, a.`adistribution_id`, a.`dassessment_id` AS task_id, d.`progress_value`, d.`aprogress_id`, 'task' AS task_type, a.`delivery_date` AS delivery_date, 0 AS delegation_start, 0 AS delegation_end
            FROM `cbl_distribution_assessments` AS a
            JOIN `cbl_assessment_distributions` AS b
            ON a.`adistribution_id` = b.`adistribution_id`                  
            JOIN `courses` AS c
            ON b.`course_id` = c.`course_id`
            LEFT JOIN `cbl_assessment_progress` AS d  
            ON a.`dassessment_id` = d.`dassessment_id`
            LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS e
            ON a.`assessor_value` = e.`id` 
            LEFT JOIN `cbl_external_assessors` AS f
            ON a.`assessor_value` = f.`eassessor_id`                                         
            WHERE a.`published` = 1 
            AND b.`assessment_type` = '$task_type'
            AND b.`visibility_status` = 'visible'
            AND a.`deleted_date` IS NULL
            AND b.`deleted_date` IS NULL
            AND d.`deleted_date` IS NULL
            AND (d.`progress_value` = 'inprogress' OR d.`progress_value` IS NULL)
            AND b.`course_id` IN ({$COURSE_ID_LIST})
            $AND_ASSESSOR
            $AND_TITLE_LIKE_TASK
            
            UNION ALL 
            
            SELECT b.`title`, CONCAT(c.`firstname`, ' ', c.`lastname`) AS internal_full_name, CONCAT(d.`firstname`, ' ', d.`lastname`) AS external_full_name, 0, a.`delegator_id`, a.`adistribution_id`, a.`addelegation_id` AS task_id, 0, 0, 'delegation' AS task_type, a.`delivery_date` AS delivery_date, a.`start_date` AS delegation_start, a.`end_date` AS delegation_end
            FROM `cbl_assessment_distribution_delegations` AS a                    
            JOIN `cbl_assessment_distributions` AS b  
            ON b.`adistribution_id`  = a.`adistribution_id`                    
            LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS c
            ON a.`delegator_id` = c.`id`
            LEFT JOIN `cbl_external_assessors` AS d
            ON a.`delegator_id` = d.`eassessor_id`   
            WHERE a.`deleted_date` IS NULL
            AND a.`completed_date` IS NULL          
            AND b.`deleted_date` IS NULL
            AND b.`visibility_status` = 'visible'
            AND b.`assessment_type` = '$task_type'
            AND a.`delegator_id` IN ({$USER_ID_LIST})
            AND b.`course_id` IN ({$COURSE_ID_LIST})
            $AND_TITLE_LIKE_DELEGATION

            UNION ALL
            
            SELECT b.`title`, CONCAT(d.`firstname`, ' ', d.`lastname`) AS internal_full_name, CONCAT(g.`firstname`, ' ', g.`lastname`) AS external_full_name, c.`assessor_type`, a.`proxy_id`, a.`adistribution_id`, c.`dassessment_id` AS task_id, 0, e.`aprogress_id`, 'approver' AS task_type, c.`delivery_date` AS delivery_date, 0 AS delegation_start, 0 AS delegation_end
            FROM `cbl_assessment_distribution_approvers` AS a
            JOIN `cbl_assessment_distributions` AS b
            ON a.`adistribution_id` = b.`adistribution_id`
            JOIN `cbl_distribution_assessments` AS c
            ON b.`adistribution_id` = c.`adistribution_id`    
            LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS d
            ON a.`proxy_id` = d.`id`       
            LEFT JOIN `cbl_external_assessors` AS g
            ON a.`proxy_id` = g.`eassessor_id`
            JOIN `cbl_assessment_progress` AS e  
            ON c.`dassessment_id` = e.`dassessment_id`
            LEFT JOIN `cbl_assessment_progress_approvals` AS f
            ON e.`aprogress_id` = f.`aprogress_id`
            WHERE b.`deleted_date` IS NULL
            AND b.`visibility_status` = 'visible'
            AND b.`assessment_type` = '$task_type'
            AND e.`progress_value` = 'complete'
            AND b.`deleted_date` IS NULL
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
                foreach ($task_list as $key => $task) {
                    if ($task["task_type"] == "task") {
                        $targets = Models_Assessments_Distribution_Target::getAssessmentTargets($task["adistribution_id"], $task["task_id"], $task["assessor_value"], $task["assessor_value"], ($task["assessor_type"] == "external" ? true : false));

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
                AND b.`assessment_type` = '$task_type'
                AND a.`delegator_id` = $assessor_id  
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
                AND b.`assessment_type` = '$task_type'
                AND e.`progress_value` = 'complete'
                AND b.`deleted_date` IS NULL
                AND c.`deleted_date` IS NULL
                AND e.`deleted_date` IS NULL
                AND a.`proxy_id` = $assessor_id
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
                JOIN `courses` AS c
                ON b.`course_id` = c.`course_id`
                LEFT JOIN `cbl_assessment_progress` AS d  
                ON a.`dassessment_id` = d.`dassessment_id`                                      
                WHERE a.`published` = 1 
                AND b.`course_id` IN ({$COURSE_ID_LIST})
                AND b.`assessment_type` = '$task_type'
                AND a.`deleted_date` IS NULL
                AND b.`deleted_date` IS NULL
                AND d.`deleted_date` IS NULL
                AND b.`visibility_status` = 'visible'
                AND (d.`progress_value` != 'cancelled' OR d.`progress_value` IS NULL)
                AND a.`assessor_value` = $assessor_id
                AND a.`assessor_type` = '$assessor_type'
                
                
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
}