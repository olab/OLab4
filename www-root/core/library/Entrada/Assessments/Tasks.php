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
 * This is an abstraction layer for handling multiple assessment-related tasks.
 *
 * Assessment tasks include:
 * - Distribution- and non-Distribution-based Assessments
 * - Distribution Delegations
 * - Assessment Approvals
 * - Future Assessment tasks
 *
 * This object allows the caller to fetch flattened summaries for the assessments for a
 * subject (user) as the target or the assessor (e.g., "Completed on me" or "My Completions").
 * The available modes of fetching are:
 *    - "pending"    : Pending tasks (both those that aren't started, or are incomplete)
 *    - "inprogress" : In-progress/incomplete tasks
 *    - "unstarted"  : Unstarted tasks (not implemented)
 *    - "upcoming"   : Upcoming tasks
 *    - "completed"  : Completed tasks
 *
 * @author Organization: Queen's University
 * @author Unit: Health Sciences, Education Technology Unit
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Entrada_Assessments_Tasks extends Entrada_Assessments_Base {

    protected $global_storage = "Entrada_Assessments_Tasks_Global_Cache";
    protected $filters = array();

    public function __construct($arr = array()) {
        parent::__construct($arr);

        // After construction, set any defaults that might not
        // have been set via construction parameter.
        $this->setRequiredFilterDefaults();
    }

    public function setLimit($limit) {
        $this->filters["limit"] = $limit;
    }

    public function setOffset($offset) {
        $this->filters["offset"] = $offset;
    }

    public function setSortOrder($order) {
        $this->filters["sort_order"] = $order;
    }

    public function setSortColumn($col) {
        $this->filters["sort_column"] = $col;
    }

    public function setFilters($tasks_filters = array()) {
        $this->filters = $tasks_filters;
        $this->setRequiredFilterDefaults();
    }

    //-- Static helpers --//

    /**
     * Fetch the filters from the session for the given type. Return a consistently formatted array containing the relevant data with defaults.
     *
     * @param $filter_type
     * @return array
     */
    public static function getFilterValuesFromSession($filter_type) {
        $distribution_method_filters = isset($_SESSION[APPLICATION_IDENTIFIER][$filter_type]["tasks"]["selected_filters"]["distribution_method"])
            ? $_SESSION[APPLICATION_IDENTIFIER][$filter_type]["tasks"]["selected_filters"]["distribution_method"]
            : array();

        $task_status_filters = isset($_SESSION[APPLICATION_IDENTIFIER][$filter_type]["tasks"]["selected_filters"]["task_status"])
            ? $_SESSION[APPLICATION_IDENTIFIER][$filter_type]["tasks"]["selected_filters"]["task_status"]
            : array();

        $cperiod_filters = isset($_SESSION[APPLICATION_IDENTIFIER][$filter_type]["tasks"]["selected_filters"]["cperiod"])
            ? $_SESSION[APPLICATION_IDENTIFIER][$filter_type]["tasks"]["selected_filters"]["cperiod"]
            : array();

        $course_filters = isset($_SESSION[APPLICATION_IDENTIFIER][$filter_type]["tasks"]["selected_filters"]["course"])
            ? $_SESSION[APPLICATION_IDENTIFIER][$filter_type]["tasks"]["selected_filters"]["course"]
            : array();

        $search_term = isset($_SESSION[APPLICATION_IDENTIFIER][$filter_type]["tasks"]["search_term"])
            ? $_SESSION[APPLICATION_IDENTIFIER][$filter_type]["tasks"]["search_term"]
            : null;

        $start_date = isset($_SESSION[APPLICATION_IDENTIFIER][$filter_type]["tasks"]["start_date"])
            ? $_SESSION[APPLICATION_IDENTIFIER][$filter_type]["tasks"]["start_date"]
            : null;

        $end_date = isset($_SESSION[APPLICATION_IDENTIFIER][$filter_type]["tasks"]["end_date"])
            ? $_SESSION[APPLICATION_IDENTIFIER][$filter_type]["tasks"]["end_date"]
            : null;

        return array(
            "distribution_method" => array_keys($distribution_method_filters),
            "task_status" => array_keys($task_status_filters),
            "cperiod" => array_keys($cperiod_filters),
            "course" => array_keys($course_filters),
            "search_term" => $search_term,
            "start_date" => $start_date,
            "end_date" => $end_date
        );
    }

    /**
     * Generate a localized date string, for the given format.
     * If no format is specified, then we assume that the string is already formatted.
     *
     * @param int $date_start
     * @param int $date_end
     * @param string|null $date_format
     * @return string
     */
    public static function generateDateRangeString($date_start, $date_end, $date_format = null) {
        global $translate;
        if ($date_format) {
            $formatted_date_start = date($date_format, (int)$date_start);
            $formatted_date_end = date($date_format, (int)$date_end);
        } else {
            $formatted_date_start = $date_start;
            $formatted_date_end = $date_end;
        }
        if ($date_start && $date_end) {
            return html_encode(sprintf($translate->_("%s to %s"), $formatted_date_start, $formatted_date_end));
        } else if ($date_start) {
            return html_encode($formatted_date_start);
        } else if ($date_end) {
            return html_encode($formatted_date_end);
        }
        return "";
    }

    /**
     * A method to declare all common javascript translation/api error result strings in one place, used throughout the interfaces that utilize this object.
     */
    public static function addCommonJavascriptTranslations() {
        Entrada_Utilities::addJavascriptTranslation("Unknown Server Error", "default_error_message", "assessments_index");
        Entrada_Utilities::addJavascriptTranslation("Unable to parse JSON data.", "local_parse_error_message", "assessments_index");
        Entrada_Utilities::addJavascriptTranslation("Select All", "btn_select_all", "assessments_index");
        Entrada_Utilities::addJavascriptTranslation("Deselect All", "btn_deselect_all", "assessments_index");
        Entrada_Utilities::addJavascriptTranslation("Please specify the reason for deletion.", "deletion_reason", "assessments_index");
        Entrada_Utilities::addJavascriptTranslation("Please select a reason for deletion.", "please_select_a_reason", "assessments_index");
        Entrada_Utilities::addJavascriptTranslation("Invalid task data.", "invalid_task_data", "assessments_index");
        Entrada_Utilities::addJavascriptTranslation("Invalid reminder targets.", "no_reminder_data", "assessments_index");
    }

    /**
     * Some assessment methods have multiple steps (phases). For instance, complete_and_confirm_by_email assessment method works as follows:
     *   1. An assessment is created, with the target as the assessor
     *   2. On submit, the assessment and progress are duplicated, except for the progress_value being set to "inprogress", and the assessor is changed to a new assessor.
     * In this method, the first assessment is useful for auditing, but isn't a useful datapoint otherwise.
     *
     * This function fetches those method types IDs that follow this multi-assessment behaviour.
     *
     * @return array
     */
    public function fetchMultiPhaseAssessmentMethodTypes() {
        $filtered = array();
        if ($this->isInStorage("multi_phase_assessment_types", "type_storage_id")) {
            return $this->fetchFromStorage("multi_phase_assessment_types", "type_storage_id");
        } else {
            $methods = new Models_Assessments_Method();
            $fetch_methods = true; // Default behaviour is to fetch all multi-step method types (i.e., all methods with phases > 1)
            if ($fetch_all_methods_setting = Entrada_Settings::fetchByShortname("assessment_tasks_show_all_multiphase_assessments", $this->actor_organisation_id)) {
                if ((int)$fetch_all_methods_setting->getValue() == 1) {
                    // If an Entrada Setting overriding the default behaviour is set, then no methods will be excluded (we filter out nothing).
                    $fetch_methods = false;
                }
            }
            if ($fetch_methods) {
                if ($method_records = $methods->fetchMethodsByPhasesGreaterThan(1)) {
                    foreach ($method_records as $method_record) {
                        $filtered[] = $method_record->getID();
                    }
                }
            }
            $this->addToStorage("multi_phase_assessment_types", $filtered, "type_storage_id");
            return $filtered;
        }
    }

    //-- Main point(s) of execution --//

    /**
     * For the various task list types, query the database and return a consistent result set.
     * Task lists are specified by adding the types in the task_list_type array. If "all" is present, then all types are queried.
     *
     * @param array $task_list_type
     * @param int $subject_id
     * @param string $subject_type
     * @param string $subject_scope
     * @param bool $count_only
     * @param bool $format_timestamps
     * @return array
     */
    public function fetchAssessmentTaskList($task_list_type = array("all"), $subject_id, $subject_type = "proxy_id", $subject_scope = "internal", $count_only = false, $format_timestamps = false) {
        $all_target_lists = false;
        $all_assessor_lists = false;
        $fetch_all_lists = false;
        if (in_array("all", $task_list_type)) {
            $fetch_all_lists = true;
        }
        if (in_array("targets", $task_list_type)) {
            $all_target_lists = true;
        }
        if (in_array("assessors", $task_list_type)) {
            $all_assessor_lists = true;
        }
        $tasks = array(
            // Tasks where the subject is the target
            "target" => array(
                "pending" => array(),
                "completed" => array(),
                "inprogress" => array(),
                "unstarted" => array(),
                "upcoming" => array()
            ),
            // Tasks where the subject is the assessor
            "assessor" => array(
                "pending" => array(),
                "completed" => array(),
                "inprogress" => array(),
                "unstarted" => array(),
                "upcoming" => array()
            )
        );

        /**
         * Pending tasks are:
         *   all dassessment tasks, all delegation tasks, all approver tasks
         *   where not complete, not deleted
         *   and assessor = this
         *   ordered by delivery date asc
         *
         * Caveats:
         *    - May or may not have progress
         */
        if ($fetch_all_lists || $all_assessor_lists || in_array("assessor-pending", $task_list_type)) {
            // My inprogress and not started: tasks that exist that may or may not have progress
            $tasks["assessor"]["pending"] = $this->executeTaskQuery("assessor", "pending", $subject_id, $subject_scope, $subject_type, $count_only, $format_timestamps);
        }

        /**
         * Completed tasks are:
         *    all assessment, delegation, approver tasks
         *    where not complete, not deleted,
         *    and progress = "complete"
         *    and assessor = this
         *    ordered by delivery date asc
         */
        if ($fetch_all_lists || $all_assessor_lists || in_array("assessor-completed", $task_list_type)) {
            // My Completed (completed by me): tasks that exist with progress set to complete
            $tasks["assessor"]["completed"] = $this->executeTaskQuery("assessor","completed", $subject_id, $subject_scope, $subject_type, $count_only, $format_timestamps);
        }

        /**
         * InProgress tasks are:
         *   all assessment, delegation, approver tasks
         *   where not complete, not deleted,
         *   and progress = "inprogress"
         *   and assessor = this
         *   ordered by delivery date asc
         */
        if ($fetch_all_lists|| $all_assessor_lists || in_array("assessor-inprogress", $task_list_type)) {
            // My in progress: tasks that exist with progress
            $tasks["assessor"]["inprogress"] = $this->executeTaskQuery("assessor", "inprogress", $subject_id, $subject_scope, $subject_type, $count_only, $format_timestamps);
        }

        /**
         * Unstarted tasks are:
         *   all assessment, delegation, approver tasks
         *   where not complete, not deleted
         *   and have no progress
         *   and assessor = this
         *   ordered by delivery date asc
         */
        if ($fetch_all_lists || $all_assessor_lists || in_array("assessor-unstarted", $task_list_type)) {
            // My Not Started: tasks that exist but have no progress
            $tasks["assessor"]["unstarted"] = $this->executeTaskQuery("assessor", "unstarted", $subject_id, $subject_scope, $subject_type, $count_only, $format_timestamps);
        }

        /**
         * Upcoming tasks are:
         *   all assessments from the ss_future_tasks table
         *   where assessor = this
         */
        if ($fetch_all_lists || $all_assessor_lists || in_array("assessor-upcoming", $task_list_type)) {
            // My Upcoming (future tasks): future tasks
            $tasks["assessor"]["upcoming"] = $this->executeTaskQuery("assessor", "upcoming", $subject_id, $subject_scope, $subject_type, $count_only, $format_timestamps);
        }

        /**
         *  Pending on target are:
         *   All assessments
         *   where progress = inprogress or no progress record
         *   and target = this
         *   order by delivery date desc
         */
        if ($fetch_all_lists || $all_target_lists || in_array("target-pending", $task_list_type)) {
            // Pending on Me: tasks that exist and may or may not have progress
            $tasks["target"]["pending"] = $this->executeTaskQuery("target", "pending", $subject_id, $subject_scope, $subject_type, $count_only, $format_timestamps);
        }

        /**
         * Completed on target are:
         *   All assessments
         *   where progress = complete
         *   and target = this
         *   order by delivery date desc
         * Caveat:
         *    - if approval is required, make sure its approved
         */
        if ($fetch_all_lists || $all_target_lists || in_array("target-completed", $task_list_type)) {
            // Completed On Me: tasks with progress set to "complete"
            $tasks["target"]["completed"] = $this->executeTaskQuery("target", "completed", $subject_id, $subject_scope, $subject_type, $count_only, $format_timestamps);
        }

        /**
         * InProgress on target are:
         *   All assessments
         *   where progress = "inprogress"
         *   and target = this
         *   order by delivery date desc
         */
        if ($fetch_all_lists || $all_target_lists || in_array("target-inprogress", $task_list_type)) {
            // In Progress on Me: tasks that exist that have progress
            $tasks["target"]["inprogress"] = $this->executeTaskQuery("target", "inprogress", $subject_id, $subject_scope, $subject_type, $count_only, $format_timestamps);
        }

        /**
         * Unstarted on target are:
         *   All assessments where no progress
         *   and target = this
         *   order by delivery date desc
         */
        if ($fetch_all_lists || $all_target_lists || in_array("target-unstarted", $task_list_type)) {
            // Not started on Me: tasks that exist but have no progress
            $tasks["target"]["unstarted"] = $this->executeTaskQuery("target", "unstarted", $subject_id, $subject_scope, $subject_type, $count_only, $format_timestamps);
        }

        /**
         * Upcoming tasks on target are:
         *   All assessments from future tasks
         *   Where target = this
         */
        if ($fetch_all_lists || $all_target_lists || in_array("target-upcoming", $task_list_type)) {
            // Upcoming on me: future tasks
            $tasks["target"]["upcoming"] = $this->executeTaskQuery("target", "upcoming", $subject_id, $subject_scope, $subject_type, $count_only, $format_timestamps);
        }

        return $tasks;
    }

    /**
     * Fetch the counts of all the assessment tasks for each list type.
     * If aggregate is specified, then the total is returned as one total value instead of an array of values for each type. This includes all
     * types in one total (e.g., if you specify assessor-completed and target-pending, you would receive one number of all tasks).
     *
     * @param array $task_list_type
     * @param int $subject_id
     * @param string $subject_type
     * @param string $subject_scope
     * @param bool $aggregate
     * @return array|int
     */
    public function fetchAssessmentTaskListCount($task_list_type = array("all"), $subject_id, $subject_type = "proxy_id", $subject_scope = "internal", $aggregate = false) {
       $totals = $this->fetchAssessmentTaskList($task_list_type, $subject_id, $subject_type, $subject_scope, true);
       $single_total_value = 0;
       if ($aggregate) {
           foreach ($totals as $task_scope => $types) {
               foreach ($types as $i => $values) {
                   if (is_array($values)) {
                       foreach ($values as $value_count) {
                           $single_total_value += $value_count;
                       }
                   }
               }
           }
           return $single_total_value;
       } else {
           return $totals;
       }
    }

    /**
     * Based on the count of a specific type (returned by this object's count method) and a supplied limit, determine
     * whether there are more results to show. If there's no limit, then we assume there are no more to load.
     * The count_type must be <subject scope>-<type>, e.g., "assessor-completed" or "target-upcoming"
     *
     * @param $count_type
     * @param $counts
     * @param $limit
     * @return bool
     */
    public function determineMoreToLoad($count_type, $counts, $limit = 0) {
        $count_type_pieces = explode("-", $count_type);
        if (count($count_type_pieces) !== 2) {
            return false;
        }
        $subject = $count_type_pieces[0];
        $type = $count_type_pieces[1];

        $task_count = isset($counts[$subject][$type]["task_count"])
            ? $counts[$subject][$type]["task_count"]
            : 0;

        $load_more = $limit == 0
            ? false
            : $task_count > $limit
                ? true
                : false;

        return $load_more;
    }

    /**
     * `task_targets` is a field with comma and hyphen delimited values.
     * The values, hypen delimited, for each comma delimited value, are:
     *    atarget_id, target_value, target_type
     *
     * An example of how the string is stored:
     *    "44114-1234-proxy_id,44115-456-schedule_id,44116-789-event_id"
     *
     * This is a valid string containing data describing three targets: a user, a
     * schedule, and an event.
     *
     * This method explodes them into arrays.
     *
     * @param $target_list_string
     * @param bool $validate_strict
     * @return array
     */
    public function explodeTargetList($target_list_string, $validate_strict = false) {
        $targets = array();
        $target_strings = explode(",", $target_list_string);
        if (empty($target_strings)) {
            return array();
        }
        foreach ($target_strings as $target_string) {
            $target_details = explode("-", $target_string);
            if (empty($target_details)) {
                continue;
            }
            if (count($target_details) != 3) {
                continue;
            }
            if ($validate_strict) {
                if ($target_details[0] === "NULL") {
                    // The word NULL means the target has no atarget_id, meaning this is either a delegation or future task.
                    $atarget_id = null;
                } else {
                    if (!$atarget_id = clean_input($target_details[0], array("trim", "int"))) {
                        return array(); // Give up; bad input
                    }
                }
                $target_value = clean_input($target_details[1], array("trim", "int"));
                $target_type = clean_input($target_details[2], array("trim", "module"));
                if (!$target_value || !$target_type) {
                    return array(); // Give up; bad input
                }
            } else {
                $atarget_id = $target_details[0];
                $target_value = $target_details[1];
                $target_type = $target_details[2];
            }
            $targets[] = array(
                "atarget_id" => $atarget_id,
                "target_value" => $target_value,
                "target_type" => $target_type
            );
        }
        return $targets;
    }

    //-- Private functionality --//

    /**
     * Set the required filter defaults, if they aren't already set.
     */
    private function setRequiredFilterDefaults() {
        if (!array_key_exists("limit", $this->filters)) {
            $this->filters["limit"] = 25;
        }
        if (!array_key_exists("offset", $this->filters)) {
            $this->filters["offset"] = 0;
        }
        if (!array_key_exists("sort_column", $this->filters)) {
            $this->filters["sort_column"] = 5; // Column #5 is task creation date.
        }
        if (!array_key_exists("sort_order", $this->filters)) {
            $this->filters["sort_order"] = "asc";
        }
    }

    /**
     * Execute the query to fetch all relevant tasks, based on query type.
     *
     * @param string $query_scope
     * @param string $query_type
     * @param int $subject_id
     * @param string $subject_scope
     * @param string $subject_type
     * @param bool $count_only
     * @param bool $format_timestamps
     * @return mixed
     */
    private function executeTaskQuery($query_scope, $query_type, $subject_id, $subject_scope, $subject_type, $count_only, $format_timestamps = false) {
        global $db;

        $task_query = "";
        $constraints = array();
        $results = array();

        // Subselect count strings for later use.
        $SELECT_COUNT_total_targets = $this->buildSubqueryTotalUniqueTargets();
        $SELECT_COUNT_targets_pending = $this->buildSubqueryUniqueTargetsPending();
        $SELECT_COUNT_unique_completions = $this->buildSubqueryUniqueTargetCompletions();
        $SELECT_COUNT_targets_inprogress = $this->buildSubqueryTargetsInProgress();

        if ($count_only) {
            $LIMIT = "";
            $OFFSET = "";
            $ORDER_BY = "";
        } else {
            // Build our limit strings
            $LIMIT = $OFFSET = "";
            if ($this->filters["limit"] && is_numeric($this->filters["offset"])) {
                $LIMIT = "LIMIT {$this->filters["limit"]}\n";
                $OFFSET = "OFFSET {$this->filters["offset"]}";
            }
            // Set our default sort column and ordering
            $sort_column = $this->filters["sort_column"];
            $sort_order = clean_input($this->filters["sort_order"], array("trim", "lower"));
            if ($sort_order !== "asc" && $sort_order !== "desc") {
                $sort_order = "asc";
            }
            // Create our order by clause
            $ORDER_BY = "ORDER BY $sort_column $sort_order";
        }

        // Prepare user filters
        $filter_clauses = $this->prepareUserFilterClauses($query_scope, $query_type, $subject_id, $subject_scope, $subject_type);
        $AND_assessment_user_filter_clauses = empty($filter_clauses["assessment"]) ? "" : implode(" ", $filter_clauses["assessment"]);
        $AND_approval_user_filter_clauses = empty($filter_clauses["approval"]) ? "" : implode(" ", $filter_clauses["approval"]);
        $AND_delegation_user_filter_clauses = empty($filter_clauses["delegation"]) ? "" : implode(" ", $filter_clauses["delegation"]);
        $AND_future_assessment_user_filter_clauses = empty($filter_clauses["future_assessment"]) ? "" : implode(" ", $filter_clauses["future_assessment"]);

        /**
         * When the subject is an assessor (e.g. "My Completed Assessments"):
         *   Fetch all assessments where the subject is the assessor.
         *   Add the three primary queries (assessments, delegations, and approvals) in a UNION with offset/limit.
         *   The 5th column being sorted on is the related record creation dates.
         *
         * When the subject is the target (e.g. "Tasks Completed on Me"):
         *   Fetch all distribution_assessment records where the subject is the target.
         *   This query follows the same pattern and returns the same fields as the assessor-scoped
         *   queries, although not in a union.
         */

        switch ($query_type) {

            case "upcoming": // Future tasks (no assessment record yet, only future task snapshot records)

                if ($query_scope == "assessor") {
                    $task_query = "
                        {$this->buildPrimaryQueryAssessorFutureAssessments($AND_future_assessment_user_filter_clauses, "", $count_only)}
                        $AND_future_assessment_user_filter_clauses
                        $ORDER_BY
                        $LIMIT
                        $OFFSET
                    ";
                    $constraints = array(
                        $subject_id,
                        $subject_scope
                    );

                } else if ($query_scope == "target") {
                    $task_query = "
                        {$this->buildPrimaryQueryTargetFutureAssessments($AND_future_assessment_user_filter_clauses, "", $count_only)}
                        $AND_future_assessment_user_filter_clauses
                        $ORDER_BY
                        $LIMIT
                        $OFFSET
                    ";
                    $constraints = array(
                        $subject_id,
                        $subject_type
                    );

                }
                break;

            case "pending": // Tasks that have either no progress record at all, or progress_value = "inprogress"

                if ($query_scope == "assessor") {

                    $AND_assessment_filter_clauses = "
                        AND ($SELECT_COUNT_total_targets) > 0
                        AND (($SELECT_COUNT_targets_inprogress) > 0 
                            OR ($SELECT_COUNT_targets_pending) > 0)
                        AND da.`deleted_date` IS NULL
                        AND (da.`adistribution_id` IS NULL 
                            OR (ad.`adistribution_id` IS NOT NULL AND ad.`deleted_date` IS NULL))
                        AND (da.`expiry_date` IS NULL OR da.`expiry_date` > UNIX_TIMESTAMP())   
                        $AND_assessment_user_filter_clauses
                    ";
                    $AND_delegation_filter_clauses = "AND ddel.`completed_date` IS NULL $AND_delegation_user_filter_clauses";
                    $AND_approval_filter_clauses = "
                        AND (apa.`approval_status` = 'pending' OR apa.`approval_status` IS NULL)
                        AND (ap.`progress_value` = 'complete')
                        $AND_approval_user_filter_clauses
                    ";

                    $task_query = "
                        {$this->buildPrimaryQueryAssessorAssessments($AND_assessment_filter_clauses, "", $count_only)} 
                        UNION ALL 
                        {$this->buildPrimaryQueryAssessorApprovals($AND_approval_filter_clauses, "", $count_only)}
                        UNION ALL 
                        {$this->buildPrimaryQueryAssessorDelegations($AND_delegation_filter_clauses, "", $count_only)}
                        $ORDER_BY
                        $LIMIT 
                        $OFFSET
                    ";

                    $constraints = array(
                        $subject_id,
                        $subject_scope,
                        $subject_id,
                        $subject_scope,
                        $subject_id,
                        $subject_type
                    );

                } else if ($query_scope == "target") {

                    $AND_assessment_visibility = "";
                    if ($assessment_visibility = $this->buildSubqueryAssessmentVisibility($query_scope, $query_type, $subject_id, $subject_scope, $subject_type)) {
                        $AND_assessment_visibility = "AND ($assessment_visibility) > 0";
                    }
                    $AND_assessment_filter_clauses = "
                        $AND_assessment_visibility
                        AND ($SELECT_COUNT_total_targets) > 0
                        AND (($SELECT_COUNT_targets_inprogress) > 0 
                            OR ($SELECT_COUNT_targets_pending) > 0)
                        $AND_assessment_user_filter_clauses
                        AND da.`deleted_date` IS NULL
                        AND (da.`adistribution_id` IS NULL 
                            OR (ad.`adistribution_id` IS NOT NULL AND ad.`deleted_date` IS NULL))
                        AND (da.`expiry_date` IS NULL OR da.`expiry_date` > UNIX_TIMESTAMP())   
                    ";
                    $task_query = "
                        {$this->buildPrimaryQueryTargetAssessments($AND_assessment_filter_clauses, "", $count_only)}
                        $ORDER_BY 
                        $LIMIT 
                        $OFFSET
                    ";
                    $constraints = array(
                        $subject_id,
                        $subject_type
                    );

                }
                break;

            case "inprogress": // Tasks that have progress records with progress_value = "inprogress"

                $AND_assessment_filter_clauses = "
                    AND ($SELECT_COUNT_total_targets) > 0
                    AND ($SELECT_COUNT_unique_completions) < ($SELECT_COUNT_targets_inprogress)
                    AND da.`deleted_date` IS NULL
                    AND (da.`adistribution_id` IS NULL 
                        OR (ad.`adistribution_id` IS NOT NULL AND ad.`deleted_date` IS NULL))
                    AND (da.`expiry_date` IS NULL OR da.`expiry_date` > UNIX_TIMESTAMP())   
                    $AND_assessment_user_filter_clauses
                ";
                $AND_delegation_filter_clauses = "AND ddel.`completed_date` IS NULL $AND_delegation_user_filter_clauses";
                $AND_approval_filter_clauses = "AND apa.`approval_status` = 'pending' $AND_approval_user_filter_clauses";

                if ($query_scope == "assessor") {
                    $task_query = "
                        {$this->buildPrimaryQueryAssessorAssessments($AND_assessment_filter_clauses, "", $count_only)} 
                        UNION ALL 
                        {$this->buildPrimaryQueryAssessorApprovals($AND_approval_filter_clauses, "", $count_only)}
                        UNION ALL 
                        {$this->buildPrimaryQueryAssessorDelegations($AND_delegation_filter_clauses, "", $count_only)}
                        $ORDER_BY
                        $LIMIT 
                        $OFFSET
                    ";
                    $constraints = array(
                        $subject_id,
                        $subject_scope,
                        $subject_id,
                        $subject_scope,
                        $subject_id,
                        $subject_type
                    );

                } else if ($query_scope == "target") {

                    $AND_assessment_visibility = "";
                    if ($assessment_visibility = $this->buildSubqueryAssessmentVisibility($query_scope, $query_type, $subject_id, $subject_scope, $subject_type)) {
                        $AND_assessment_visibility = "AND ($assessment_visibility) > 0";
                    }
                    $AND_assessment_filter_clauses = "
                        $AND_assessment_visibility
                        AND da.`deleted_date` IS NULL
                        AND (da.`expiry_date` IS NULL OR da.`expiry_date` > UNIX_TIMESTAMP())
                        AND (da.`adistribution_id` IS NULL 
                            OR (ad.`adistribution_id` IS NOT NULL AND ad.`deleted_date` IS NULL))
                    ";
                    $task_query = "
                        {$this->buildPrimaryQueryTargetAssessments($AND_assessment_filter_clauses, "", $count_only)}
                        $ORDER_BY
                        $LIMIT 
                        $OFFSET
                    ";
                    $constraints = array(
                        $subject_id,
                        $subject_type
                    );
                }
                break;

            case "unstarted": // Tasks without progress (aprogress id = null)
                // Not implemented
                break;

            case "completed": // Tasks with progress_value = "complete"

                $AND_only_terminal_assessments = "";
                $method_type_ids = $this->fetchMultiPhaseAssessmentMethodTypes();
                if (is_array($method_type_ids) && !empty($method_type_ids)) {

                    /* This clause excludes the self-assessment portion of multi-phase assessments.
                     * Multi-phase assessments include "complete and confirm by pin", "confirm by email" and any other
                     * assessment method that has multiple steps. By default,  we exclude the first step and only include
                     * the terminal step; the initial step is the assessor filling out an assessment on
                     * themselves and then asking another user to confirm it. On submission, the assessment is copied to a new assessor; we
                     * only want to include the copied (terminal) version of this assessment. */

                    $phased_assessment_method_ids = implode(",", $method_type_ids);
                    $AND_only_terminal_assessments = "
                        AND NOT (da.`assessor_value` = {$db->qstr($subject_id)}
                            AND da.`assessor_type` = {$db->qstr($subject_scope)}
                            AND da.`assessment_method_id` IN ($phased_assessment_method_ids)
                        )
                    ";
                }

                if ($query_scope == "assessor") {

                    $GROUP_clause_override = "GROUP BY da.`dassessment_id`";

                    $AND_assessment_filter_clauses = "
                        AND ($SELECT_COUNT_unique_completions) > 0
                        AND ($SELECT_COUNT_targets_pending) <= 0
                        AND ($SELECT_COUNT_targets_inprogress) <= 0
                        AND ap.`progress_value` = 'complete'
                        $AND_assessment_user_filter_clauses
                        $AND_only_terminal_assessments
                    ";
                    $AND_delegation_filter_clauses = "AND ddel.`completed_date` IS NOT NULL $AND_delegation_user_filter_clauses";
                    $AND_approval_filter_clauses = "AND (apa.`approval_status` = 'approved' OR apa.`approval_status` = 'hidden') $AND_approval_user_filter_clauses";

                    $task_query = "
                        {$this->buildPrimaryQueryAssessorAssessments($AND_assessment_filter_clauses, $GROUP_clause_override, $count_only)} 
                        UNION ALL 
                        {$this->buildPrimaryQueryAssessorApprovals($AND_approval_filter_clauses, $GROUP_clause_override, $count_only)}
                        UNION ALL 
                        {$this->buildPrimaryQueryAssessorDelegations($AND_delegation_filter_clauses, "", $count_only)}
                        $ORDER_BY 
                        $LIMIT 
                        $OFFSET
                    ";
                    $constraints = array(
                        $subject_id,
                        $subject_scope,
                        $subject_id,
                        $subject_scope,
                        $subject_id,
                        $subject_type
                    );

                } else if ($query_scope == "target" ) {

                    $GROUP_clause_override = "GROUP BY ap.`aprogress_id`";

                    $AND_assessment_visibility = "";
                    if ($assessment_visibility = $this->buildSubqueryAssessmentVisibility($query_scope, $query_type, $subject_id, $subject_scope, $subject_type)) {
                        $AND_assessment_visibility = "AND ($assessment_visibility) > 0";
                    }
                    $AND_assessment_filter_clauses = "
                        $AND_assessment_visibility
                        AND ap.`progress_value` = 'complete' 
                        AND ap.`target_record_id` = dat.`target_value`
                        AND ap.`target_type` = dat.`target_type`
                        $AND_assessment_user_filter_clauses
                        $AND_only_terminal_assessments
                    ";

                    $task_query = "
                        {$this->buildPrimaryQueryTargetAssessments($AND_assessment_filter_clauses, $GROUP_clause_override, $count_only)}
                        $ORDER_BY 
                        $LIMIT 
                        $OFFSET
                    ";
                    $constraints = array(
                        $subject_id,
                        $subject_type
                    );
                }
                break;
        }

        if ($task_query) {
            /**
             * Execute the query.
             */
            $debug_condition = false;
            //$debug_condition = (!$count_only && $query_type == "completed" && $query_scope == "assessor");
            if ($debug_condition) { echo "<pre>"; $db->debug = true; }
            $results = $db->GetAll($task_query, $constraints);
            if ($debug_condition) { $db->debug = false; echo "</pre>"; }

            if ($results) {
                if ($count_only) {
                    // Only return one index; the task count
                    $total_tasks = 0;
                    foreach ($results as $result) {
                        $total_tasks += $result["task_count"];
                    }
                    return array("task_count" => $total_tasks);
                } else if (!$count_only) {
                    // Add the missing data and set task data flags.
                    foreach ($results as &$result) {
                        $result = $this->expandTaskData($result, $query_scope, $query_type, $subject_id, $subject_scope, $subject_type, $format_timestamps);
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Fill in the task resultset in with the data that couldn't be joined in the initial query.
     * The goal with this method is to query for missing data, but not do too much heavy lifting. The heavy lifting
     * is done by the initial query.
     *
     * @param $task
     * @param $query_scope
     * @param $query_type
     * @param $subject_id
     * @param $subject_scope
     * @param $subject_type
     * @param bool $format_timestamps
     * @return array
     */
    private function expandTaskData(&$task, $query_scope, $query_type, $subject_id, $subject_scope, $subject_type, $format_timestamps = false) {
        global $translate;
        if (!is_array($task) || empty($task)) {
            return array();
        }
        $task_url = ENTRADA_URL;
        $atarget_id = null;

        $add_target_details = true;
        $show_view_button = true;
        $show_remove_button = true;
        $show_progress_section = true;
        $show_reminder_section = false;
        $show_pdf_section = true;
        $has_progress = true;

        $show_assessor_details = false;
        $show_single_target_details = false;
        $show_delegation_badge = false;
        $show_reviewer_badge = false;
        $task_badge_text = "";
        $schedule_badge_text = "";
        $event_badge_text = "";

        if ($this->actor_proxy_id == $task["task_assessor_value"]
            && ($task["task_assessor_type"] == "internal"
                || $task["task_assessor_type"] == "proxy_id")
        ) {
            $actor_is_assessor = true;
        } else {
            $actor_is_assessor = false;
        }

        if ($this->actor_proxy_id == $subject_id
            && ($subject_type == "proxy_id"
                || $subject_scope == "internal")
        ) {
            $actor_is_subject = true;
        } else {
            $actor_is_subject = false;
        }

        if ($this->actor_proxy_id == $task["approver_id"]) {
            $actor_is_approver = true;
        } else {
            $actor_is_approver = false;
        }

        if ($subject_id == $task["approver_id"]
            && $subject_type == "proxy_id"
        ) {
            $subject_is_approver = true;
        } else {
            $subject_is_approver = false;
        }

        if ($subject_id == $task["task_assessor_value"]
            && $task["task_assessor_type"] == "internal"
            && $subject_type == "proxy_id"
        ) {
            $subject_is_assessor = true;
        } else {
            $subject_is_assessor = false;
        }

        // Fill in the schedule; this block logic is recursive, so must be fetched after the main query.
        if ($task["target_schedule_id"]) {
            $schedule = new Models_Schedule(array(
                "schedule_id" => $task["target_schedule_id"],
                "title" => $task["schedule_title"],
                "schedule_parent_id" => $task["schedule_parent_id"]
            ));
            // This logic is recursive and the result is not cached. Sorry!
            $schedule_badge_text = $this->getConcatenatedBlockString(
                null,
                $schedule,
                $task["rotation_start_date"] ? $task["rotation_start_date"] : $task["task_start_date"],
                $task["rotation_end_date"] ? $task["rotation_end_date"] : $task["task_end_date"],
                $task["organisation_id"],
                " - ",
                ", ",
                true,
                true
            );
        }

        // There's an event ID associated, so configure this task as an event-based one; hide the default task
        // start/end, only show the event start/end time strings. Modify the event timeframe string to show hour difference.
        if ($task["target_event_id"]) {
            $event_badge_text = $task["event_title"];
            $task["task_start_date"] = null;
            $task["task_end_date"] = null;
            $strings = $this->buildTimeframeStrings(
                $task["event_timeframe_start"],
                $task["event_timeframe_end"],
                "M j, Y",
                "g:i A"
            );
            $task["event_timeframe_start"] = $strings["timeframe_start"];
            $task["event_timeframe_end"] = $strings["timeframe_end"];
        }

        // Fill in the assessor data
        $this->populateAssessorData($task);

        // Show PDF downloads only for complete and pending tasks.
        if (!$actor_is_subject
            && $query_type != "completed"
            && $query_type != "pending"
        ) {
            $show_pdf_section = false;
        }

        // Build the URL based on task type and target count.
        // Set some more state variables specific to type.
        switch ($task["task_type"]) {

            case "approval":
                $show_reviewer_badge = true;
                $show_assessor_details = true;
                $show_progress_section = false;
                $show_reminder_section = $actor_is_approver || $subject_is_approver ? false : true;
                $show_remove_button = false; // Can't remove approvals
                $show_pdf_section = true;
                $task_url .= "/assessments/assessment?dassessment_id={$task["dassessment_id"]}&aprogress_id={$task["aprogress_id"]}&atarget_id={$task["task_id"]}";
                $atarget_id = $task["task_id"];
                break;

            case "future_assessment":
                $show_view_button = false;
                $show_remove_button = true;
                $show_progress_section = false;
                $show_reminder_section = false; // Never show reminders for future assessments
                $show_pdf_section = false; // Nothing to download yet!
                if ($query_scope == "target") {
                    $show_assessor_details = true;
                    $show_single_target_details = false;
                } else {
                    $show_assessor_details = false;
                    $show_single_target_details = true;
                }
                break;

            case "assessment":
                if ($query_scope == "assessor") {
                    $show_assessor_details = false;
                    if ($task["total_targets"] > 1) {
                        $task_url .= "/assessments/assessment?dassessment_id={$task["dassessment_id"]}&section=targets";
                    } else {
                        $task_url .= "/assessments/assessment?dassessment_id={$task["dassessment_id"]}";
                    }
                } else if ($query_scope == "target") {
                    $show_progress_section = false;
                    $add_target_details = false;
                    $show_assessor_details = true;
                    $task_url .= "/assessments/assessment?dassessment_id={$task["dassessment_id"]}&aprogress_id={$task["aprogress_id"]}&atarget_id={$task["task_id"]}";
                    $atarget_id = $task["task_id"];
                }
                if ($query_type == "completed") {
                    $show_remove_button = false;
                }
                if ($query_type == "pending" && !$actor_is_assessor) {
                    $show_reminder_section = true;
                }
                break;

            case "delegation":
                $show_delegation_badge = true;
                $show_progress_section = false;
                $show_assessor_details = false;
                $show_reminder_section = true;
                $show_pdf_section = false;
                $has_progress = false;
                if ($query_type == "completed") {
                    $show_remove_button = false;
                }
                $task_url .= "/assessments/delegation?addelegation_id={$task["task_id"]}&adistribution_id={$task["adistribution_id"]}";
                break;
        }

        // In all cases, don't allow reminders on self.
        if ($actor_is_assessor || $actor_is_subject) {
            $show_reminder_section = false;
        }

        // In all cases, disallow reminders if the task is completed
        if ($query_type == "completed") {
            $show_reminder_section = false;
        }

        // When the actor is not the assessor, and the subject is not the assessor, indicate who is
        if (!$actor_is_subject && !$subject_is_assessor) {
            $show_assessor_details = true;
        }

        // Determine if the delivery date is in the future.
        if ($task["delivery_date"] > time()) {
            $task["future_delivery"] = true;
        } else {
            $task["future_delivery"] = false;
        }

        // If there's a form type we want to display a badge for, we do so here
        if ($task["form_type_category"] == "blueprint"
            || $task["form_type_category"] == "cbme_form"
        ) {
            $task_badge_text = $task["form_type_title"];
        }

        $task["task_url"] = $task_url;
        $task["task_badge_text"] = $task_badge_text;
        $task["schedule_badge_text"] = $schedule_badge_text;
        $task["event_badge_text"] = $event_badge_text;
        $task["has_progress"] = $has_progress;
        $task["show_remove_button"] = $show_remove_button;
        $task["show_view_button"] = $show_view_button;
        $task["show_progress_section"] = $show_progress_section;
        $task["show_assessor_details"] = $show_assessor_details;
        $task["show_single_target_details"] = $show_single_target_details;
        $task["show_delegation_badge"] = $show_delegation_badge;
        $task["show_reviewer_badge"] = $show_reviewer_badge;
        $task["show_download_pdf"] = $show_pdf_section;
        $task["show_send_reminders"] = $show_reminder_section;

        // Populate the single target data, if possible
        $this->populateTargetData($task, $atarget_id);
        if (empty($task["single_target_name"])) {
            $single_target_name = $translate->_("the target");
        } else {
            $single_target_name = $task["single_target_name"];
        }

        $task_creator = "";
        if ($task_creator_object = $this->getUserByType($task["assessment_creator"], "internal")) {
            $task_creator = "{$task_creator_object->getFirstname()} {$task_creator_object->getLastname()}";
        }

        // Add a string summary of what this task is, e.g., "2 attempts for 3 individuals"
        $assessor_name = isset($task["assessor_name"]) ? $task["assessor_name"] : $translate->_("Unknown assessor");
        $this->populateTaskDetailString($task, $assessor_name, $single_target_name, $add_target_details, $task_creator);

        // Lastly, format timestamps if specified. Note that this should be the last step, as timestamps are
        // required to be integers for the previous functionality to work correctly.
        if ($format_timestamps) {
            if ($task["task_completion_date"]) {
                $task["task_completion_date"] = date("M j, Y", (int)$task["task_completion_date"]);
            } else {
                $task["task_completion_date"] = null;
            }
            if ($task["task_creation_date"]) {
                $task["task_creation_date"] = date("M j, Y", (int)$task["task_creation_date"]);
            } else {
                $task["task_creation_date"] = null;
            }
            if ($task["task_updated_date"]) {
                $task["task_updated_date"] = date("M j, Y", (int)$task["task_updated_date"]);
            } else {
                $task["task_updated_date"] = null;
            }
            if ($task["delivery_date"]) {
                $task["delivery_date"] = date("M j, Y", (int)$task["delivery_date"]);
            } else {
                $task["delivery_date"] = null;
            }
            if ($task["rotation_start_date"]) {
                $task["rotation_start_date"] = date("M j, Y", (int)$task["rotation_start_date"]);
            } else {
                $task["rotation_start_date"] = null;
            }
            if ($task["rotation_end_date"]) {
                $task["rotation_end_date"] = date("M j, Y", (int)$task["rotation_end_date"]);
            } else {
                $task["rotation_end_date"] = null;
            }
            if ($task["task_start_date"]) {
                $task["task_start_date"] = date("M j, Y", (int)$task["task_start_date"]);
            } else {
                $task["task_start_date"] = null;
            }
            if ($task["task_end_date"]) {
                $task["task_end_date"] = date("M j, Y", (int)$task["task_end_date"]);
            } else {
                $task["task_end_date"] = null;
            }
        }
        return $task;
    }

    /**
     * Add assessor related data to the task array.
     * These queries are all cached via the internal storage mechanism.
     *
     * @param $task
     */
    private function populateAssessorData(&$task) {
        global $translate;
        $assessor_value = $task["task_assessor_value"];
        $assessor_type = $task["task_assessor_type"];
        if ($assessor_value && $assessor_type) {
            if ($assessor = $this->getUserByType($assessor_value, $assessor_type)) {
                $task["assessor_name"] = "{$assessor->getFirstname()} {$assessor->getLastname()}";
                $task["assessor_value"] = $task["task_assessor_value"];
                $task["assessor_type"] = $task["task_assessor_type"];
                if ($assessor_type == "internal") {
                    $storage_key = "{$task["task_assessor_value"]}-{$task["task_assessor_type"]}-{$task["organisation_id"]}";
                    if ($this->isInStorage("user-access", $storage_key)) {
                        $user_access = $this->fetchFromStorage("user-access", $storage_key);
                    } else {
                        $user_access = Models_User_Access::fetchAllByUserIDAppID(
                            $task["task_assessor_value"],
                            $task["organisation_id"]
                        );
                        $this->addToStorage("user-access", $user_access, $storage_key);
                    }
                    if (empty($user_access)) {
                        $task["assessor_group"] = $translate->_("Internal Assessor");
                        $task["assessor_role"] = null;
                    } else {
                        $single_access = end($user_access);
                        $task["assessor_group"] = ucwords(str_replace("_", " ", $translate->_($single_access->getGroup())));
                        $task["assessor_role"] = ucwords(str_replace("_", " ", $translate->_($single_access->getRole())));
                    }
                } else { // external
                    $task["assessor_role"] = null;
                    $task["assessor_group"] = $translate->_("External");
                }
            }
        }
    }

    /**
     * Add target related data to the task array (when there is only one target).
     * These queries are all cached via the internal storage mechanism.
     *
     * @param $task
     * @param int $atarget_id
     */
    private function populateTargetData(&$task, $atarget_id = null) {
        global $translate;
        $targets = $this->explodeTargetList($task["task_targets"]);
        $target_type = null;
        $target_value = null;

        // Find the single target's name, if there's only one target, or if a specific atarget_id is given
        if ($atarget_id) {
            // Find the atarget ID in the targets list
            foreach ($targets as $target) {
                if ($atarget_id == $target["atarget_id"]) {
                    $target_value = $target["target_value"];
                    $target_type = $target["target_type"];
                }
            }
        } else if (count($targets) == 1) {
            $targets = array_shift($targets);
            $atarget_id = $targets["atarget_id"];
            $target_value = $targets["target_value"];
            $target_type = $targets["target_type"];
        }
        $task["single_target_value"] = $target_value;
        $task["single_target_type"] = $target_type;

        if ($target_type == "proxy_id" && $target_value) { // internal
            if ($single_target_record = $this->getUserByType($target_value, $target_type)) {
                $task["single_target_name"] = "{$single_target_record->getFirstname()} {$single_target_record->getLastname()}";
                $storage_key = "{$atarget_id}-{$task["organisation_id"]}";
                if ($this->isInStorage("user-access", $storage_key)) {
                    $user_access = $this->fetchFromStorage("user-access", $storage_key);
                } else {
                    $user_access = Models_User_Access::fetchAllByUserIDAppID(
                        $target_value,
                        $task["organisation_id"]
                    );
                    $this->addToStorage("user-access", $user_access, $storage_key);
                }
                if (empty($user_access)) {
                    $task["single_target_group"] = $translate->_("Internal Assessor");
                    $task["single_target_role"] = null;
                } else {
                    $single_access = end($user_access);
                    $task["single_target_group"] = ucwords(str_replace("_", " ", $translate->_($single_access->getGroup())));
                    $task["single_target_role"] = ucwords(str_replace("_", " ", $translate->_($single_access->getRole())));
                }
            }
        } else if ($target_type == "schedule_id") {
            $task["single_target_name"] = $task["schedule_title"];
        }
    }

    /**
     * Add a string with details about the task to the supplied task array.
     * This method assumes one target and one assessor. It is up to the caller to flatten the single_target_name if necessary.
     *
     * @param array $task
     * @param string $assessor_name
     * @param string $single_target_name
     * @param bool $add_target_summary
     * @param string $task_creator
     * @return bool
     */
    private function populateTaskDetailString(&$task, $assessor_name, $single_target_name, $add_target_summary = true, $task_creator = null) {
        global $translate;

        $task_details = "";
        if ($task["distribution_description"]) {
            $task_desc = trim($task["distribution_description"]);
            $task_desc = rtrim($task_desc, ".");
            if ($task_desc) {
                $task_details .= "<p>{$task_desc}.</p>";
            }
        }
        if (empty($task) || empty($task["task_targets"])) {
            $task["task_details"] = $task_details;
            return false;
        }
        $targets = explode(",", $task["task_targets"]);
        if (empty($targets)) {
            $task["task_details"] = $task_details;
            return false;
        }

        switch ($task["task_type"]) {

            case "future_assessment":
                // Nothing spceial here.
                // Future tasks are always 1 assessor and 1 target. Relevant data is already available to the card view.
                break;

            case "delegation":

                // ADRIAN-TODO: Return to this. This will require a complex query to join the targets and assessors into something usable.
                /*
                $target_count = 1;
                $assessor_count = 1;
                $delegation_type = $translate->_("on-service learners");

                if ($target_count == 1) {
                    $target_grammar = $translate->_("target");
                } else {
                    $target_grammar = $translate->_("targets");

                }
                if ($assessor_count == 1) {
                    $assessor_grammar = $translate->_("assessor");
                } else {
                    $assessor_grammar = $translate->_("assessors");
                }

                //$task_details = $translate->_("Delegation of %s ");
                //$task_details = "Delegation of self assessment with " . count($targets) . " possible assessors.";
                $task_details .= sprintf(
                    $translate->_("<p>Delegation of %s with %s possible %s and %s possible %s.</p>"),
                    $delegation_type,
                    $assessor_count,
                    $assessor_grammar,
                    $target_count,
                    $target_grammar
                );*/
                break;

            case "assessment":
                if ($add_target_summary) {
                    $target_summary_string = $this->buildTargetSummaryString($targets);
                    $detail_slices = array();
                    if ($task["targets_pending"]) {
                        $detail_slices[] = sprintf(
                            "%s %s",
                            $task["targets_pending"] >= 0 ? $task["targets_pending"] : 0,
                            ($task["targets_pending"] == 1) ? $translate->_("completion pending") : $translate->_("completions pending")
                        );
                    }
                    if ($task["targets_in_progress"]) {
                        $detail_slices[] = sprintf(
                            "%s %s",
                            $task["targets_in_progress"] >= 0 ? $task["targets_in_progress"] : 0,
                            ($task["targets_in_progress"] == 1) ? $translate->_("target in progress") : $translate->_("targets in progress")
                        );
                    }
                    if ($task["targets_completed"]) {
                        $detail_slices[] = sprintf(
                            "%s %s",
                            $task["targets_completed"] >= 0 ? $task["targets_completed"] : 0,
                            ($task["targets_completed"] == 1) ? $translate->_("target completed") : $translate->_("targets completed")
                        );
                    }
                    $task_progression_details = $this->buildCommaDelimitedStringFromArray($detail_slices);
                    $task_details .= sprintf($translate->_("<p>%s for %s.</p>"), $task_progression_details, $target_summary_string);
                }
                break;

            case "approval":

                if ($task["task_completion_date"] && $task["approver_name"]) {
                    $task_details .= sprintf(
                        $translate->_("<p>This task completed by %s for %s was reviewed by %s on %s.</p>"),
                        $assessor_name,
                        $single_target_name,
                        $task["approver_name"],
                        date("M j, Y", $task["task_completion_date"])
                    );

                } else {
                    $task_details .= sprintf(
                        $translate->_("<p>Please review this task that was completed by %s for %s on %s.</p>"),
                        $assessor_name,
                        $single_target_name,
                        date("M j, Y", $task["task_updated_date"])
                    );
                }
                break;
        }
        
        /**
         * Triggered forms should indicate by whom.
         */
        if (($task["form_type_category"] == "blueprint"
                || $task["form_type_category"] == "cbme_form"
            )
            && !$task["adistribution_id"]
            && $task_creator
        ) {
            $task_details .= sprintf(
                $translate->_("<p>%s triggered by %s.</p>"),
                $task["form_type_title"],
                $task_creator
            );
        }

        if ($task_details) {
            $task["task_details"] = $task_details;
        }
        return true;
    }

    /**
     * Count each type of target and return the result in an array.
     * Each type has its own count.
     *
     * @param $targets
     * @return array
     */
    private function buildTargetSummaryCounts($targets) {
        $target_summary = array();
        foreach ($targets as $target) {
            $target_info = explode("-", $target);
            if (count($target_info) != 3) {
                continue;
            }
            $atarget_id = $target_info[0];
            $target_value = $target_info[1];
            $target_type = $target_info[2];
            if (!isset($target_summary[$target_type])) {
                $target_summary[$target_type] = 0;
            }
            $target_summary[$target_type]++;
        }
        return $target_summary;
    }

    /**
     * Build a string summarizing the target details.
     *
     * @param array $targets
     * @return mixed|string
     */
    private function buildTargetSummaryString($targets) {
        global $translate;
        $target_counts = $this->buildTargetSummaryCounts($targets);
        $target_list = array();
        if (array_count_values($target_counts)) {
            foreach ($target_counts as $count_type => $summary_count) {
                if ($summary_count == 0) {
                    continue;
                }
                switch ($count_type) {
                    case "schedule_id":
                        $target_list[] = sprintf(
                            "%s %s",
                            $summary_count,
                            ($summary_count == 1)
                                ? $translate->_("rotation")
                                : $translate->_("rotations")
                        );
                        break;
                    case "course_id":
                        $target_list[] = sprintf(
                            "%s %s",
                            $summary_count,
                            ($summary_count == 1)
                                ? $translate->_("course")
                                : $translate->_("courses")
                        );
                        break;
                    case "event_id":
                        $target_list[] = sprintf(
                            "%s %s",
                            $summary_count,
                            ($summary_count == 1)
                                ? $translate->_("event")
                                : $translate->_("events")
                        );
                        break;
                    case "group_id":
                        $target_list[] = sprintf(
                            "%s %s",
                            $summary_count,
                            ($summary_count == 1)
                                ? $translate->_("learner group")
                                : $translate->_("learner groups")
                        );
                        break;
                    case "proxy_id":
                        $target_list[] = sprintf(
                            "%s %s",
                            $summary_count,
                            ($summary_count == 1)
                                ? $translate->_("individual")
                                : $translate->_("individuals")
                        );
                        break;
                    case "external_hash":
                        $target_list[] = sprintf(
                            "%s %s",
                            $summary_count,
                            ($summary_count == 1)
                                ? $translate->_("external user")
                                : $translate->_("external users")
                        );
                        break;
                    case "organisation_id":
                        $target_list[] = sprintf(
                            "%s %s",
                            $summary_count,
                            ($summary_count == 1)
                                ? $translate->_("organisation")
                                : $translate->_("organisations")
                        );
                        break;
                }
            }
        }
        return $this->buildCommaDelimitedStringFromArray($target_list);
    }

    /**
     * Build a comma delimited string from an array, with an "and" when appropriate (e.g., one, two, and three).
     * Adds an oxford comma (a comma after the penultimate item in the list, preceding the "and") by default.
     *
     * @param $list_items
     * @param bool $oxford_comma
     * @return mixed|string
     */
    private function buildCommaDelimitedStringFromArray($list_items, $oxford_comma = true) {
        global $translate;
        if (empty($list_items)) {
            return "";
        }
        if (count($list_items) == 1) {
            return end($list_items);
        } else if (count($list_items) == 2) {
            return implode($translate->_(" and "), $list_items);
        } else {
            $last_item = array_pop($list_items);
            if ($oxford_comma) {
                return sprintf($translate->_("%s, and %s"), implode(", ", $list_items), $last_item);
            } else {
                return sprintf($translate->_("%s and %s"), implode(", ", $list_items), $last_item);
            }
        }
    }

    //-- Sub-select clauses --//

    /**
     * Based on the filters property of this object, build AND/OR clauses for the primary queries.
     * Always returns an array for each of the task types.
     *
     * @param $query_scope
     * @param $query_type
     * @param $subject_id
     * @param $subject_scope,
     * @param $subject_type
     * @return array
     */
    private function prepareUserFilterClauses($query_scope, $query_type, $subject_id, $subject_scope, $subject_type) {
        global $db;
        $filter_clauses = array(
            "assessment" => array(),
            "delegation" => array(),
            "approval" => array(),
            "future_assessment" => array()
        );

        $SELECT_COUNT_distribution_eventtypes = $this->buildSubqueryCountDistributionEventtypes();
        $SELECT_COUNT_targets_type_assessment = $this->buildSubqueryCountAssessmentTargetType("assessment");
        $SELECT_COUNT_targets_type_evaluation = $this->buildSubqueryCountAssessmentTargetType("evaluation");

        $ignore_user_course = false;
        if (isset($this->filters["limit_course"])) {
            $ignore_user_course = true;
        }

        // Configure our user filters, if specified.
        foreach ($this->filters as $filter_option => $filter_values) {
            switch ($filter_option) {
                case "search_term":
                    // Where distribution name, form name, assessor name, delegator name form type, rotation name LIKE
                    if ($term = clean_input($filter_values, array("trim", "striptags"))) {
                        $term = $db->qstr("%$term%");
                        $term_filters = array();
                        $term_filters["form_name"] = "f.`title` LIKE($term)";
                        $term_filters["form_type"] = "ft.`title` LIKE($term)";
                        $term_filters["distribution_name"] = "ad.`title` LIKE($term)";
                        $term_filters["distribution_description"] = "ad.`description` LIKE($term)";
                        $term_filters["assessor_name"] = "({$this->buildSuqueryFetchInternalAssessorName()}) LIKE($term) OR ({$this->buildSubqueryFetchExternalAssessorName()}) LIKE($term)";
                        // TODO: Add something for target names here too?

                        if (!empty($term_filters)) {
                            $term_filter_string = implode(" OR ", $term_filters);
                            $clause = "AND ($term_filter_string)";
                            $filter_clauses["assessment"][] = $clause;
                            $filter_clauses["approval"][] = $clause;

                            unset($term_filters["assessor_name"]); // assessor_name isn't available for the delegation or future assessment queries
                            $term_filter_string = implode(" OR ", $term_filters);
                            $clause = "AND ($term_filter_string)";
                            $filter_clauses["future_assessment"][] = $clause;
                            $filter_clauses["delegation"][] = $clause;
                        }
                    }
                    break;

                case "start_date":
                    // Where delivery date >=
                    if ((int)$filter_values > 0) {
                        $clause = "AND da.`delivery_date` >= {$db->qstr($filter_values)}";

                        $filter_clauses["assessment"][] = $clause;
                        $filter_clauses["approval"][] = $clause;

                        $clause = "AND ddel.`delivery_date` >= {$db->qstr($filter_values)}";
                        $filter_clauses["delegation"][] = $clause;

                        $clause = "AND aft.`delivery_date` >= {$db->qstr($filter_values)}";
                        $filter_clauses["future_assessment"][] = $clause;
                    }
                    break;

                case "end_date":
                    // Where end date <=
                    if ((int)$filter_values > 0) {
                        $clause = "AND da.`delivery_date` <= {$db->qstr($filter_values)}";
                        $filter_clauses["assessment"][] = $clause;
                        $filter_clauses["approval"][] = $clause;

                        $clause = "AND ddel.`delivery_date` <= {$db->qstr($filter_values)}";
                        $filter_clauses["delegation"][] = $clause;

                        $clause = "AND aft.`delivery_date` <= {$db->qstr($filter_values)}";
                        $filter_clauses["future_assessment"][] = $clause;
                    }
                    break;

                case "task_status":
                    // Where progress value is one of the given values
                    if (is_array($filter_values)) {
                        if ($query_type == "future_assessment") {
                            // Filtering by task status is not available for future assessments
                        } else {
                            $task_filter_opts = array();
                            foreach ($filter_values as $task_filter_value) {
                                if ($task_filter_value == "inprogress" || $task_filter_value == "pending") {
                                    $delegation_filter_opts[$task_filter_value] = "ddel.`completed_date` IS NULL";
                                }
                                if ($task_filter_value == "complete") {
                                    $delegation_filter_opts[$task_filter_value] = "ddel.`completed_date` IS NOT NULL";
                                }
                                if ($task_filter_value == "pending") {
                                    $task_filter_opts[$task_filter_value] = "ap.`progress_value` IS NULL";
                                } else {
                                    $task_filter_opts[$task_filter_value] = "ap.`progress_value` = {$db->qstr($task_filter_value)}";
                                }
                            }
                            if (!empty($task_filter_opts)) {
                                $status_clauses = implode(" OR ", $task_filter_opts);
                                $clause = "AND ($status_clauses)";

                                $filter_clauses["assessment"][] = $clause;
                                $filter_clauses["approval"][] = $clause;
                            }
                            if (!empty($delegation_filter_opts)) {
                                $status_clauses = implode(" OR ", $delegation_filter_opts);
                                $clause = "AND ($status_clauses)";
                                $filter_clauses["delegation"][] = $clause;
                            }
                        }
                    }
                    break;

                case "course":
                    if (!$ignore_user_course) {
                        // Where distribution course ID OR dassessment course ID is in the specified list
                        if (is_array($filter_values)) {
                            $ids = array_map(
                                function ($v) {
                                    return clean_input($v, array("trim", "int"));
                                },
                                $filter_values
                            );
                            if (!empty($ids)) {
                                $courses_id_str = implode(",", $ids);
                                $clause = "AND (da.`course_id` IN($courses_id_str) OR ad.`course_id` IN($courses_id_str))";

                                $filter_clauses["assessment"][] = $clause;
                                $filter_clauses["approval"][] = $clause;

                                $clause = "AND (ad.`course_id` IN($courses_id_str))";
                                $filter_clauses["delegation"][] = $clause;
                                $filter_clauses["future_assessment"][] = $clause;
                            }
                        }
                    }
                    break;

                case "limit_course":
                    if (is_array($filter_values)) {
                        $ids = array_map(
                            function ($v) {
                                return clean_input($v, array("trim", "int"));
                            },
                            $filter_values
                        );
                        if (!empty($ids)) {
                            $courses_id_str = implode(",", $ids);
                            $clause = "AND (da.`course_id` IN($courses_id_str) OR ad.`course_id` IN($courses_id_str))";

                            $filter_clauses["assessment"][] = $clause;
                            $filter_clauses["approval"][] = $clause;

                            $clause = "AND (ad.`course_id` IN($courses_id_str))";
                            $filter_clauses["delegation"][] = $clause;
                            $filter_clauses["future_assessment"][] = $clause;
                        }
                    }
                    break;

                case "cperiod":
                    // Where distribution cperiod id IN the specified list
                    if (is_array($filter_values)) {
                        $ids = array_map(
                            function ($v) {
                                return clean_input($v, array("trim", "int"));
                            },
                            $filter_values
                        );
                        if (!empty($ids)) {
                            $cperiod_id_str = implode(",", $ids);
                            $clause = "AND ad.`cperiod_id` IN($cperiod_id_str)";

                            $filter_clauses["assessment"][] = $clause;
                            $filter_clauses["approval"][] = $clause;
                            $filter_clauses["delegation"][] = $clause;
                            $filter_clauses["future_assessment"][] = $clause;
                        }
                    }
                    break;

                case "distribution_method":
                    // Sorry; there's no real association between filter and task type, so in order to determine what
                    // we're filtering on, we have to check against the text in the database.
                    if (is_array($filter_values) && !empty($filter_values)) {
                        $delegations_selected = false;
                        $dist_method_clauses = array();
                        $del_dist_method_clauses = array();
                        foreach ($filter_values as $task_filter_value) {
                            $distribution_method_record = new Models_Assessments_Distribution_Method();
                            $distribution_method_record = $distribution_method_record->fetchRowByID($task_filter_value);
                            if ($distribution_method_record) {
                                $task_filter_value_name = $distribution_method_record->getTitle();
                                switch ($task_filter_value_name) {
                                    case "Date Range":
                                        // Distributions with no delegator, schedule, or event type ID.
                                        $dist_method_clauses[$task_filter_value] = "(ad.`adistribution_id` IS NOT NULL AND ads.`schedule_id` IS NULL AND ($SELECT_COUNT_distribution_eventtypes) = 0)";
                                        break;
                                    case "Delegation":
                                        $delegations_selected = true;
                                        // Distributions with a delegator record
                                        $dist_method_clauses[$task_filter_value] = "0"; // We're filtering by delegations, so we should only show delegations
                                        $del_dist_method_clauses[$task_filter_value] = "ddel.`addelegation_id` IS NOT NULL";
                                        break;
                                    case "Learning Event":
                                        // Distributions with at least one event type record.
                                        $dist_method_clauses[$task_filter_value] = "(($SELECT_COUNT_distribution_eventtypes) > 0)";
                                        break;
                                    case "Rotation Schedule":
                                        // Distributions with a schedule record.
                                        $dist_method_clauses[$task_filter_value] = "(ads.`schedule_id` IS NOT NULL)";
                                        break;
                                }
                            }
                        }
                        if (!empty($dist_method_clauses)) {
                            $clauses_string = implode(" OR ", $dist_method_clauses);
                            $clause = "AND ($clauses_string)";

                            $filter_clauses["assessment"][] = $clause;
                            $filter_clauses["approval"][] = $clause;
                            $filter_clauses["future_assessment"][] = $clause;
                        }
                        if (!$delegations_selected) {
                            $filter_clauses["delegation"][] = "AND 0";
                        } else if (!empty($del_dist_method_clauses)) {
                            $clauses_string = implode(" OR ", $del_dist_method_clauses);
                            $clause = "AND ($clauses_string)";
                            $filter_clauses["delegation"][] = $clause;
                        }
                    }
                    break;

                case "task_type": // This is meant to filter assessment/evaluation task types.

                    if (is_array($filter_values)) {

                        $filtering_assessments = in_array("assessment", $filter_values);
                        $filtering_evaluations = in_array("evaluation", $filter_values);
                        if ($query_scope == "target") {
                            // Filtering assessment or evaluation has a different meaning for when the target is the scope.
                            // We only want to return those target records that match the given type(s)
                            $clauses = array();
                            if ($filtering_assessments) {
                                $clauses["assessment"] = ($query_type == "future_assessment") ? "aft.`task_type` = 'assessment'" : "dat.`task_type` = 'assessment'";
                            }
                            if ($filtering_evaluations) {
                                $clauses["evaluation"] = ($query_type == "future_assessment") ? "aft.`task_type` = 'evaluation'" : "dat.`task_type` = 'evaluation'";
                            }
                            if (!empty($clauses)) {
                                $clauses_string = implode(" OR ", $clauses);
                                $clause = "AND ($clauses_string)";
                                $filter_clauses["assessment"][] = $clause;
                                $filter_clauses["approval"][] = $clause;
                            }
                        } else {
                            // When not in target scope, we only include those tasks that have at least 1 or more of the given type(s)

                            // In the case of the assessor looking at their own completions, we don't filter by task type.
                            // In the future, if we decide to give them this ability, then we'll have to take it into account here.
                            if ($query_type == "completed") {
                                $filtering_assessments = true;
                                $filtering_evaluations = true;
                            }
                            $clauses = array();
                            if ($filtering_assessments) {
                                $clauses["assessment"] = "($SELECT_COUNT_targets_type_assessment) > 0";
                            }
                            if ($filtering_evaluations) {
                                $clauses["evaluation"] = "($SELECT_COUNT_targets_type_evaluation) > 0";
                            }
                            if (!empty($clauses)) {
                                $clauses_string = implode(" OR ", $clauses);
                                $clause = "AND ($clauses_string)";
                                $filter_clauses["assessment"][] = $clause;
                                $filter_clauses["approval"][] = $clause;
                            }
                        }
                    }
                    break;

                case "dassessment_id": // Filtering on a specific assessment ID
                    if ((int)$filter_values > 0) {
                        $clause = "AND da.`dassessment_id` = $filter_values";
                        $filter_clauses["assessment"][] = $clause;
                        $filter_clauses["approval"][] = $clause;
                        $filter_clauses["delegation"][] = "AND 0"; // specified dassessment ID cancels delegations
                        $filter_clauses["future_assessment"][] = "AND 0"; // specified dassessment ID cancels future assessments
                    }
                    break;

                default:
                    // Arbitrary filter is not supported
                    break;
            }
        }
        return $filter_clauses;
    }

    /**
     * Build a subselect clause:
     * Ensure a value of 1 when distribution_assessment.min_submittable is NULL or 0.
     *
     * @return string
     */
    private function buildSubselectClauseMinSubmittable() {
        return "IF(IFNULL(da.`min_submittable`, 1), IFNULL(da.`min_submittable`, 1), 1)";
    }

    /**
     * Build a subselect clause:
     * Determine if the associated distribution has the submittable_by_target field set; if so, attempts = 1 per target, else the minimum is per target.
     * This IF returns NULL if distribution is not set.
     *
     * @return string
     */
    private function buildSubselectClauseTargetSubmittable() {
        return "
            IF (sub_ad.`adistribution_id` IS NOT NULL, 
                (IF (sub_ad.`submittable_by_target` = 1, 1, sub_ad.`min_submittable`)), 
                {$this->buildSubselectClauseMinSubmittable()}
            )";
    }

    //-- Dependent subquery strings --//

    /**
     * Build a subselect clause:
     * Fetch the count of event types for a given distribution.
     *
     * @return string
     */
    private function buildSubqueryCountDistributionEventtypes() {
        return "
            SELECT COUNT(*) 
            FROM `cbl_assessment_distribution_eventtypes` AS ade 
            WHERE 1
              AND ade.`adistribution_id` = ad.`adistribution_id`
        ";
    }

    /**
     * Build a subselect clause: 
     * Fetch the count of targets for an assessment that are of the particular 
     * type (e.g., # of evaluation targets or # of assessment targets).
     * 
     * @param $type
     * @return string
     */
    private function buildSubqueryCountAssessmentTargetType($type = "assessment") {
        if ($type == "assessment" || $type == "evaluation") {
            $AND_type = "AND sub_dat.`task_type` = '$type'";
        } else {
            $AND_type = "";
        }
        return "
            SELECT COUNT(*) 
            FROM `cbl_distribution_assessment_targets` AS sub_dat
            JOIN `cbl_distribution_assessments` AS sub_da
                ON sub_da.`dassessment_id` = sub_dat.`dassessment_id`
            LEFT JOIN `cbl_assessment_progress` AS sub_ap
                ON sub_ap.`dassessment_id` = sub_dat.`dassessment_id`
                AND sub_ap.`target_record_id` = sub_dat.`target_value`
                AND sub_ap.`target_type` = sub_dat.`target_type`
            WHERE 1
                AND sub_dat.`dassessment_id` = da.`dassessment_id`
                $AND_type
                AND (sub_dat.`deleted_date` IS NULL 
                    OR (sub_ap.`progress_value` = 'complete' AND sub_ap.`deleted_date` IS NULL)
                )
        ";
    }

    /**
     * Build a subselect clause:
     * Fetch the count of event types for a given distribution.
     *
     * @return string
     */
    private function buildSubqueryCountDistributionDelegations() {
        return "
            SELECT COUNT(*) 
            FROM `cbl_assessment_distribution_delegations` AS ddel 
            WHERE 1
              AND ddel.`adistribution_id` = ad.`adistribution_id`
        ";
    }

    /**
     * Build a dependent subquery string:
     * Select all unique targets with inprogress progress records for the given assessment.
     *
     * @return string
     */
    private function buildSubqueryUniqueTargetsInProgress() {
        return "
            SELECT DISTINCT(COUNT(*)) 
            FROM `cbl_distribution_assessment_targets` AS sub_dat
            JOIN `cbl_distribution_assessments` as sub_da
                ON sub_da.`dassessment_id` = sub_dat.`dassessment_id`
            LEFT JOIN `cbl_assessment_progress` AS sub_ap 
                ON sub_ap.`dassessment_id` = sub_dat.`dassessment_id`
                AND sub_ap.`target_record_id` = sub_dat.`target_value`
                AND sub_ap.`target_type` = sub_dat.`target_type`
                AND sub_ap.`assessor_value` = sub_da.`assessor_value`
                AND sub_ap.`assessor_type` = sub_da.`assessor_type`
            WHERE 1
                AND sub_dat.`dassessment_id` = da.`dassessment_id`
                AND sub_ap.`aprogress_id` IS NOT NULL 
                AND sub_ap.`progress_value` = 'inprogress'
                AND sub_ap.`deleted_date` IS NULL
        ";
    }

    /**
     * Build a dependent subquery string:
     * Select all unique targets with complete progress for the given assessment.
     *
     * Note that deleted dates are not honoured anywhere, since a completed
     * progress record is valid irrespective of target deletions.
     *
     * @return string
     */
    private function buildSubqueryUniqueTargetCompletions() {
        return "
            SELECT DISTINCT(COUNT(sub_dat.`atarget_id`)) 
            FROM `cbl_distribution_assessment_targets` AS sub_dat
            JOIN `cbl_distribution_assessments` AS sub_da 
                ON sub_da.`dassessment_id` = sub_dat.`dassessment_id`
            JOIN `cbl_assessment_progress` AS sub_ap 
                ON sub_ap.`dassessment_id` = sub_dat.`dassessment_id`
                AND sub_ap.`target_record_id` = sub_dat.`target_value`
                AND sub_ap.`target_type` = sub_dat.`target_type`
                AND sub_ap.`assessor_value` = sub_da.`assessor_value`
                AND sub_ap.`assessor_type` = sub_da.`assessor_type`
            WHERE 1 
                AND sub_dat.`dassessment_id` = da.`dassessment_id`
                AND sub_ap.`progress_value` = 'complete'
        ";
    }

    /**
     * Build a dependent subquery string:
     * Select the count of attempts available for all targets for an assessment.
     *
     * - The total number of attempts is modified by the distribution for an assessment.
     *
     * - Where there is no distribution, the minimum number of target attempts is what is explicitly indicated on the assessment.
     *
     * - In the case of a distribution, the minimum number of target attempts is modified by the distribution's "submittable_by_target" field,
     *   where the minimum number of attemps is the total number of available attempts.
     *
     * - The other consideration is the repeat_targets field, which allows the same target to be assessed repeatedly.
     *   This field is mutually exclusive with the submittable_by_target field. If the repeat_targets field is on, the minimum is used.
     */
    private function buildSubqueryTotalAttempts() {
        return "
            SELECT (COUNT(*) * ({$this->buildSubselectClauseTargetSubmittable()})) AS total_count
            FROM `cbl_distribution_assessment_targets` AS sub_dat
            JOIN `cbl_distribution_assessments` AS sub_da 
                ON sub_da.`dassessment_id` = sub_dat.`dassessment_id`
            LEFT JOIN `cbl_assessment_distributions` AS sub_ad 
                ON sub_ad.`adistribution_id` = sub_da.`adistribution_id`
            WHERE 1
                AND sub_dat.`dassessment_id` = da.`dassessment_id`
                AND sub_dat.`deleted_date` IS NULL
        ";
    }

    /**
     * Build a dependent subquery string:
     * Select the count of all pending targets for an assessment.
     * Pending targets are those that are in progress, not started, and not complete.
     * This is calculated by taking the distinct count of all targets, multiplied by the number of
     * attempts, subtracting the number of unique in progress and unique completed progress.
     *
     * @return string
     */
    private function buildSubqueryUniqueTargetsPending() {
        return "
            SELECT (COUNT(DISTINCT(sub_dat.`atarget_id`)) - ({$this->buildSubqueryUniqueTargetsInProgress()}) - ({$this->buildSubqueryUniqueTargetCompletions()})) AS pending_count
            FROM `cbl_distribution_assessment_targets` AS sub_dat
            JOIN `cbl_distribution_assessments` AS sub_da 
                ON sub_da.`dassessment_id` = sub_dat.`dassessment_id`
            LEFT JOIN `cbl_assessment_distributions` AS sub_ad 
                ON sub_ad.`adistribution_id` = sub_da.`adistribution_id`
            LEFT JOIN `cbl_assessment_progress` AS sub_ap 
                ON sub_ap.`dassessment_id` = sub_dat.`dassessment_id` 
                AND sub_ap.`target_record_id` = sub_dat.`target_value`
                AND sub_ap.`target_type` = sub_dat.`target_type`
                AND sub_ap.`assessor_value` = sub_da.`assessor_value`
                AND sub_ap.`assessor_type` = sub_da.`assessor_type`
            WHERE 1 
                AND sub_dat.`dassessment_id` = sub_da.`dassessment_id`
                AND sub_dat.`dassessment_id` = da.`dassessment_id`
                AND sub_dat.`deleted_date` IS NULL
                AND sub_da.`deleted_date` IS NULL
                AND sub_ap.`deleted_date` IS NULL
        ";
    }

    /**
     * Build a dependent subquery string:
     * find the total number of unique targets for an assessment.
     *
     * @return string
     */
    private function buildSubqueryTotalUniqueTargets() {
        return "
            SELECT COUNT(DISTINCT(sub_dat.`atarget_id`))
            FROM `cbl_distribution_assessment_targets` AS sub_dat
            WHERE 1
                AND sub_dat.`dassessment_id` = da.`dassessment_id`
                AND sub_dat.`deleted_date` IS NULL
        ";
    }

    /**
     * Build a dependent subquery string:
     * Count the number of targets with "inprogress" progress records for the given assessment.
     *
     * @return string
     */
    private function buildSubqueryTargetsInProgress() {
        return "
            SELECT COUNT(*)
            FROM `cbl_distribution_assessment_targets` AS sub_dat
            JOIN `cbl_distribution_assessments` AS sub_da 
                ON sub_da.`dassessment_id` = sub_dat.`dassessment_id`
            JOIN `cbl_assessment_progress` AS sub_ap 
                ON sub_ap.`dassessment_id` = sub_dat.`dassessment_id`
                AND sub_ap.`target_record_id` = sub_dat.`target_value`
                AND sub_ap.`target_type` = sub_dat.`target_type`
                AND sub_ap.`assessor_value` = sub_da.`assessor_value`
                AND sub_ap.`assessor_type` = sub_da.`assessor_type`
            WHERE 1 
                AND sub_dat.`dassessment_id` = sub_da.`dassessment_id`
                AND sub_dat.`dassessment_id` = da.`dassessment_id`
                AND sub_dat.`deleted_date` IS NULL
                AND sub_ap.`progress_value` = 'inprogress'
                AND sub_ap.`deleted_date` IS NULL
        ";
    }

    /**
     * Build a dependent subquery string:
     * Ensure the actor meets the assessment options controlling task visibility.
     *
     * @param $query_scope
     * @param $query_type
     * @param $subject_id
     * @param $subject_scope
     * @param $subject_type
     * @return string
     */
    private function buildSubqueryAssessmentVisibility($query_scope, $query_type, $subject_id, $subject_scope, $subject_type) {
        global $db;
        /**
         * Target Visibility Options
         */
        // Filter target task viewable based on assessment options, falling back on task type (assessments visible, evaluations hidden).
        if ($query_scope == "target"
            && $subject_type == "proxy_id"
            && $subject_id == $this->actor_proxy_id
        ) {

            $target_task_visibility_assessment_option_sq = "  
                SELECT dao.`daoption_id`
                FROM `cbl_distribution_assessment_options` AS dao   
                WHERE dao.`dassessment_id` = da.`dassessment_id`
                AND (dao.`option_name` = 'target_viewable' OR dao.`option_name` = 'target_viewable_percent')
                AND dao.`deleted_date` IS NULL
            ";

            $target_completion_count_sq = "   
                SELECT COUNT(cc_ap.`aprogress_id`) AS `completions`
                FROM cbl_assessment_progress AS cc_ap
                WHERE (cc_ap.`target_type` = sq_at.`target_type` AND cc_ap.`target_record_id` = sq_at.`target_value`)
                AND cc_ap.`dassessment_id` = sq_a.`dassessment_id`
                AND cc_ap.`progress_value` = 'complete'
                AND cc_ap.`deleted_date` IS NULL
            ";

            $target_completion_for_siblings = "
                SELECT SUM(
                  IF(($target_completion_count_sq) > da.`min_submittable`, da.`min_submittable`, ($target_completion_count_sq))
                ) AS `target_completion_count`
                FROM `cbl_distribution_assessment_targets` AS sq_at
                JOIN `cbl_distribution_assessments` AS sq_a
                  ON sq_at.`dassessment_id` = sq_a.`dassessment_id`
                WHERE FIND_IN_SET(sq_a.`dassessment_id`, dao.`assessment_siblings`)
                  AND sq_a.`assessor_value` = {$db->qstr($this->actor_proxy_id)}
            ";

            $target_completion_for_unique_targets = "
                SELECT DISTINCT COUNT(sq2_at.`atarget_id`) AS `unique_target_total_required`
                FROM `cbl_distribution_assessment_targets` AS sq2_at
                JOIN `cbl_distribution_assessments` AS sq2_a
                  ON sq2_a.`dassessment_id` = sq2_at.`dassessment_id`
                WHERE FIND_IN_SET(sq2_a.`dassessment_id`, dao.`assessment_siblings`)
                  AND sq2_a.`assessor_value` = {$db->qstr($this->actor_proxy_id)}
            ";

            $target_task_release_sq = " 
                SELECT DISTINCT COUNT(dao.`daoption_id`)
                FROM `cbl_distribution_assessment_options` AS dao   
                WHERE dao.`dassessment_id` = da.`dassessment_id`
                AND dao.`deleted_date` IS NULL
                AND ((dao.`option_name` = 'target_viewable' AND dao.`option_value` = 'true')
                    OR (dao.`option_name` = 'target_viewable_percent' 
                      AND dao.`option_value` <= (($target_completion_for_siblings) / (($target_completion_for_unique_targets) * da.`min_submittable`) * 100)
                    )
                  )
            ";

            // If there are assessment options, evaluate them and use the result. Otherwise, fallback on assessment (shown) vs. evaluation (hidden).
            $task_type_sq = $this->buildSubqueryCountAssessmentTargetType("assessment");
            return "IF(($target_task_visibility_assessment_option_sq) IS NOT NULL, ($target_task_release_sq), ($task_type_sq))";
        }
        return ""; // Default is to fall through and use the default object's logic (which is, obey the filter that was set)
    }

    /**
     * Build a dependent subquery string:
     * Fetch the approver's name from the auth database.
     *
     * @return string
     */
    private function buildSuqueryFetchApproverName() {
        $auth_database = AUTH_DATABASE;
        return "
            SELECT CONCAT(apa_u.`firstname`, ' ', apa_u.`lastname`) 
            FROM `$auth_database`.`user_data` AS apa_u
            WHERE apa_u.`id` = apa.`approver_id`
        ";
    }

    /**
     * Build a dependent subquery string:
     * Fetch the assessor's name from the auth database
     *
     * @return string
     */
    private function buildSuqueryFetchInternalAssessorName() {
        $auth_database = AUTH_DATABASE;
        return "
            SELECT CONCAT(asri_u.`firstname`, ' ', asri_u.`lastname`) 
            FROM `$auth_database`.`user_data` AS asri_u
            WHERE asri_u.`id` = da.`assessor_value` 
            AND da.`assessor_type` = 'internal'
        ";
    }

    /**
     * Build a dependent subquery string:
     * Fetch the assessor's name from the external_assessors table
     *
     * @return string
     */
    private function buildSubqueryFetchExternalAssessorName() {
        return "
            SELECT CONCAT(asre_u.`firstname`, ' ', asre_u.`lastname`) 
            FROM `cbl_external_assessors` AS asre_u
            WHERE asre_u.`eassessor_id` = da.`assessor_value` 
            AND da.`assessor_type` = 'external'
        ";
    }

    /**
     * Build a dependent subquery string:
     * Fetch the delegator's name from the auth database.
     *
     * @return string
     */
    private function buildSuqueryFetchDelegatorName() {
        $auth_database = AUTH_DATABASE;
        return "
            SELECT CONCAT(del_u.`firstname`, ' ', del_u.`lastname`) 
            FROM `$auth_database`.`user_data` AS del_u
            WHERE del_u.`id` = adel.`delegator_id`
        ";
    }

    /**
     * Build a dependent subquery string:
     * Count the number of targets with completed progress records for the given assessment.
     *
     * @return string
     */
    private function buildSubqueryTargetsCompleted() {
        return "            
            SELECT COUNT(*)
            FROM `cbl_distribution_assessment_targets` AS sub_dat
            JOIN `cbl_distribution_assessments` AS sub_da 
                ON sub_da.`dassessment_id` = sub_dat.`dassessment_id`
            JOIN `cbl_assessment_progress` AS sub_ap 
                ON sub_ap.`dassessment_id` = sub_dat.`dassessment_id`
                AND sub_ap.`target_record_id` = sub_dat.`target_value`
                AND sub_ap.`target_type` = sub_dat.`target_type`
                AND sub_ap.`assessor_value` = sub_da.`assessor_value`
                AND sub_ap.`assessor_type` = sub_da.`assessor_type`
            WHERE 1 
                AND sub_dat.`dassessment_id` = sub_da.`dassessment_id`
                AND sub_dat.`dassessment_id` = da.`dassessment_id`
                AND sub_ap.`progress_value` = 'complete'
                AND sub_ap.`deleted_date` IS NULL
        ";
    }

    /**
     * Build a dependent subquery string:
     * Select all of the targets' primary keys for a given assessment as a single comma delimited string.
     *
     * @return string
     */
    private function buildSubqueryConcatAtargetIDs() {
        return "
            SELECT GROUP_CONCAT(con_dat.`atarget_id` SEPARATOR ',') AS atarget_id_list
            FROM `cbl_distribution_assessment_targets` AS con_dat
            WHERE 1
                AND con_dat.`dassessment_id` = da.`dassessment_id`
                AND con_dat.`deleted_date` IS NULL
            ORDER BY con_dat.`atarget_id`
        ";
    }

    /**
     * Build a dependent subquery string:
     * Select all of the unique target types for an assessment's targets.
     *
     * @return string
     */
    private function buildSubqueryConcatTargets() {
        return "
            SELECT GROUP_CONCAT(CONCAT(con_dat.`atarget_id`, '-', con_dat.`target_value`, '-', con_dat.`target_type`) SEPARATOR ',') AS atarget_type_list
            FROM `cbl_distribution_assessment_targets` AS con_dat
            WHERE 1
                AND con_dat.`dassessment_id` = da.`dassessment_id`
                AND con_dat.`deleted_date` IS NULL
            ORDER BY con_dat.`atarget_id`
        ";
    }

    //-- Primary query strings --//

    /**
     * Build a full (primary) query:
     * Select all delegations for a given delegator (proxy_id).
     * For use in a UNION.
     *
     * @param string $AND_filter_clauses
     * @param string $GROUP_clause_override
     * @param bool $count_only
     * @return string
     */
    private function buildPrimaryQueryAssessorDelegations($AND_filter_clauses = "", $GROUP_clause_override = "", $count_only = false) {
        $GROUP_clause = "";
        if ($GROUP_clause_override) {
            $GROUP_clause = $GROUP_clause_override;
        }
        if ($count_only) {
            $SELECT_fields = " COUNT(*) AS `task_count`";
            $GROUP_clause = "";
        } else {
            $SELECT_fields = "
                ddel.`addelegation_id` AS `task_id`,
                'delegation' AS `task_type`,
                'assessor' AS `task_scope`,
                IF(ddel.`completed_date`, 'complete','incomplete') AS `task_status`,
                ddel.`created_date` AS `task_creation_date`,
                ddel.`completed_date` AS `task_completion_date`,
                ddel.`updated_date` AS `task_updated_date`,
                ddel.`deleted_date`, 
                ddel.`adistribution_id`,
                ad.`description` AS `distribution_description`,
                NULL AS `dassessment_id`,
                NULL AS `assessment_method_id`,
                NULL AS `aprogress_id`,
                ad.`organisation_id`,
                f.`form_id`,
                ft.`category` COLLATE 'utf8_unicode_ci'  AS `form_type_category`,
                ft.`shortname` COLLATE 'utf8_unicode_ci'  AS `form_type_shortname`,
                ft.`title` COLLATE 'utf8_unicode_ci'  AS `form_type_title`,
                IFNULL(ad.`title`, f.`title`) AS `task_title`,
                ddel.`delegator_id` AS `task_assessor_value`,
                'internal' AS `task_assessor_type`,
                NULL AS `task_targets`,
                ddel.`start_date` AS `task_start_date`,
                ddel.`end_date` AS `task_end_date`,
                NULL AS `rotation_start_date`,
                NULL AS `rotation_end_date`,
                NULL AS `encounter_date`,
                ddel.`delivery_date` AS `delivery_date`,
                NULL AS `total_targets`,
                NULL AS `targets_pending`,
                NULL AS `targets_in_progress`,
                NULL AS `targets_completed`,
                NULL AS `associated_record_id`,
                NULL AS `associated_record_type`,
                NULL AS `target_course_id`,
                NULL AS `target_group_id`,
                NULL AS `target_proxy_id`,
                s.`schedule_id` AS `target_schedule_id`,
                s.`schedule_parent_id` AS `schedule_parent_id`,
                s.`title` AS `schedule_title`,
                NULL AS `target_event_id`,
                NULL AS `event_timeframe_start`,
                NULL AS `event_timeframe_end`,
                NULL AS `event_title`,
                ddel.`addelegation_id` AS `addelegation_id`,
                NULL AS `delegator_id`,
                NULL AS `delegator_type`,
                NULL AS `delegator_name`,
                NULL AS `approver_id`,
                NULL AS `approver_name`,
                NULL AS `assessment_creator`
            ";
        }
        return "
             SELECT 
                 $SELECT_fields
             FROM `cbl_assessment_distribution_delegations` AS ddel
             JOIN `cbl_assessment_distributions` AS ad 
                 ON ddel.`adistribution_id` = ad.`adistribution_id`
             JOIN `cbl_assessment_distribution_delegators` AS adel 
                 ON adel.`adistribution_id` = ad.`adistribution_id`
             LEFT JOIN `cbl_assessment_distribution_schedule` AS ads
                 ON ads.`adistribution_id` = ad.`adistribution_id`
             LEFT JOIN `cbl_distribution_assessments` AS da
                 ON da.`dassessment_id` IS NULL
             JOIN `cbl_assessments_lu_forms` AS f 
                 ON f.`form_id` = ad.`form_id`
             LEFT JOIN `cbl_assessments_lu_form_types` AS ft
                 ON ft.`form_type_id` = f.`form_type_id`
             LEFT JOIN `cbl_schedule` AS s 
                 ON s.`schedule_id` = ads.`schedule_id`
             WHERE 1
                 AND ad.`organisation_id` = {$this->actor_organisation_id}
                 AND ddel.`delegator_id` = ?
                 AND ddel.`delegator_type` = ?
                 AND ddel.`deleted_date` IS NULL 
                 AND ad.`deleted_date` IS NULL
                 AND (ad.`visibility_status` = 'visible' OR ad.`visibility_status` IS NULL)
                 $AND_filter_clauses
             $GROUP_clause
        ";
    }

    /**
     * Build a full (primary) query:
     * Select all approval tasks (completed assessments, i.e., those with completed progress records and associated distribution_approver record) for a given proxy.
     * For use in a UNION.
     *
     * @param string $AND_filter_clauses
     * @param string $GROUP_clause_override
     * @param bool $count_only
     * @return string
     */
    private function buildPrimaryQueryAssessorApprovals($AND_filter_clauses = "", $GROUP_clause_override = "", $count_only = false) {
        $auth_database = AUTH_DATABASE;
        $GROUP_clause = "";
        if ($GROUP_clause_override) {
            $GROUP_clause = $GROUP_clause_override;
        }
        if ($count_only) {
            $SELECT_fields = " COUNT(DISTINCT(ap.`aprogress_id`)) AS `task_count`";
            $GROUP_clause = "";
        } else {
            $SELECT_fields = "
                dat.`atarget_id` AS `task_id`,
                'approval' AS `task_type`,
                'assessor' AS `task_scope`,
                apa.`approval_status` AS `task_status`,
                apa.`created_date` AS `task_creation_date`,
                IFNULL(apa.`updated_date`, apa.`created_date`) AS `task_completion_date`,
                ap.`updated_date` AS `task_updated_date`,
                da.`deleted_date`,
                apa.`adistribution_id`,
                ad.`description` AS `distribution_description`,
                da.`dassessment_id`, 
                da.`assessment_method_id`,
                ap.`aprogress_id`,
                da.`organisation_id`,
                f.`form_id`,
                ft.`category`  COLLATE 'utf8_unicode_ci'  AS `form_type_category`,
                ft.`shortname`  COLLATE 'utf8_unicode_ci'  AS `form_type_shortname`,
                ft.`title`  COLLATE 'utf8_unicode_ci'  AS `form_type_title`,
                IFNULL(ad.`title`, f.`title`) AS `task_title`,
                da.`assessor_value` AS `task_assessor_value`,
                da.`assessor_type` AS `task_assessor_type`,
                ({$this->buildSubqueryConcatTargets()}) AS `task_targets`,
                da.`start_date` AS `task_start_date`,
                da.`end_date` AS `task_end_date`,
                da.`rotation_start_date` AS `rotation_start_date`,
                da.`rotation_end_date` AS `rotation_end_date`,
                da.`encounter_date` AS `encounter_date`,
                apa.`created_date` AS `delivery_date`,
                1 AS `total_targets`,
                IF(apa.`approval_status` = 'pending', 1, 0) AS `targets_pending`,
                0 AS `targets_in_progress`,
                IF(apa.`approval_status` = 'pending', 0, 1) AS `targets_completed`,
                da.`associated_record_id`,
                da.`associated_record_type`,
                c.`course_id` AS `target_course_id`,
                g.`group_id` AS `target_group_id`,
                u.`id` AS `target_proxy_id`,
                s.`schedule_id` AS `target_schedule_id`,
                s.`schedule_parent_id` AS `schedule_parent_id`,
                s.`title` AS `schedule_title`,
                e.`event_id` AS `target_event_id`,
                e.`event_start` AS `event_timeframe_start`,
                e.`event_finish` AS `event_timeframe_end`,
                e.`event_title` AS `event_title`,
                NULL AS `addelegation_id`,
                NULL AS `delegator_id`,
                NULL AS `delegator_type`,
                NULL AS `delegator_name`,
                apa.`approver_id` AS `approver_id`,
                (IF (apa.`approver_id`, ({$this->buildSuqueryFetchApproverName()}), NULL)) AS `approver_name`,
                da.`created_by` as `assessment_creator`
            ";
        }
        return "
             SELECT 
                 $SELECT_fields                    
             FROM `cbl_assessment_progress` AS ap 
             JOIN `cbl_assessment_distributions` AS ad
                 ON ad.`adistribution_id` = ap.`adistribution_id`
             JOIN `cbl_assessment_distribution_approvers` AS ada
                 ON ada.`adistribution_id` = ap.`adistribution_id`
				 AND ada.`proxy_id` = ?                 
             LEFT JOIN `cbl_assessment_progress_approvals` AS apa
                 ON ap.`aprogress_id` = apa.`aprogress_id`
             LEFT JOIN `cbl_distribution_assessment_targets` AS dat
                 ON ap.`target_record_id` = dat.`target_value` 
                 AND ap.`target_type` = dat.`target_type`
                 AND ap.`dassessment_id` = dat.`dassessment_id`
             JOIN `cbl_distribution_assessments` AS da 
                 ON da.`dassessment_id` = ap.`dassessment_id`
             LEFT JOIN `cbl_assessment_lu_methods` AS am
                 ON am.`assessment_method_id` = da.`assessment_method_id`
             LEFT JOIN `cbl_assessment_distribution_schedule` AS ads
                 ON ad.`adistribution_id` = ads.`adistribution_id`
             LEFT JOIN `cbl_schedule` AS s 
                 ON da.`associated_record_id` = s.`schedule_id` AND da.`associated_record_type` = 'schedule_id'
             LEFT JOIN `courses` AS c 
                 ON da.`associated_record_id` = c.`course_id` AND da.`associated_record_type` = 'course_id'
             LEFT JOIN `events` AS e 
                 ON da.`associated_record_type` = e.`event_id` AND da.`associated_record_type` = 'event_id'
             LEFT JOIN `groups` AS g 
                 ON da.`associated_record_type` = g.`group_id` AND da.`associated_record_type` = 'group_id'
             LEFT JOIN `$auth_database`.`user_data` AS u 
                 ON da.`associated_record_id` = u.`id` AND da.`associated_record_type` = 'proxy_id'
             LEFT JOIN `cbl_assessment_distribution_delegators` AS adel 
                 ON ad.`adistribution_id` = adel.`adistribution_id`
             JOIN `cbl_assessments_lu_forms` AS f 
                 ON f.`form_id` = ad.`form_id`
             LEFT JOIN `cbl_assessments_lu_form_types` AS ft
                 ON ft.`form_type_id` = f.`form_type_id`
             WHERE 1
                 AND da.`organisation_id` = {$this->actor_organisation_id} 
                 AND dat.`deleted_date` IS NULL
                 AND ? = 'internal'
                 AND (ad.`visibility_status` = 'visible' OR ad.`visibility_status` IS NULL)
                 $AND_filter_clauses
             $GROUP_clause
        ";
    }

    /**
     * Build a full (primary) query:
     * Select all assessments with all related data for a given proxy_id.
     * Columns are required in this order for use in a UNION.
     *
     * @param string $AND_filter_clauses
     * @param string $GROUP_clause_override
     * @param bool $count_only
     * @return string
     */
    private function buildPrimaryQueryAssessorAssessments($AND_filter_clauses = "", $GROUP_clause_override = "", $count_only = false) {
        $auth_database = AUTH_DATABASE;
        $GROUP_clause = "GROUP BY da.`dassessment_id`";
        if ($GROUP_clause_override) {
            $GROUP_clause = $GROUP_clause_override;
        }
        if ($count_only) {
            $SELECT_fields = " COUNT(DISTINCT(da.`dassessment_id`)) AS `task_count`";
            $GROUP_clause = "";
        } else {
            $SELECT_fields = "
                da.`dassessment_id` AS `task_id`,  
                'assessment' AS `task_type`,
                'assessor' AS `task_scope`,
                ap.`progress_value` AS `task_status`,
                da.`created_date` AS `task_creation_date`,
                IF(ap.`progress_value` = 'complete', IFNULL(ap.`updated_date`, ap.`created_date`), NULL) AS `task_completion_date`,
                ap.`updated_date` AS `task_updated_date`,
                da.`deleted_date`,
                da.`adistribution_id`,
                ad.`description` AS `distribution_description`,
                da.`dassessment_id`, 
                da.`assessment_method_id`,
                NULL as `aprogress_id`,
                da.`organisation_id`,
                f.`form_id`,
                ft.`category` COLLATE 'utf8_unicode_ci'  AS `form_type_category`,
                ft.`shortname` COLLATE 'utf8_unicode_ci'  AS `form_type_shortname`,
                ft.`title` COLLATE 'utf8_unicode_ci'  AS `form_type_title`,
                IFNULL(ad.`title`, f.`title`) AS `task_title`,
                da.`assessor_value` AS `task_assessor_value`,
                da.`assessor_type` AS `task_assessor_type`,
                ({$this->buildSubqueryConcatTargets()}) AS `task_targets`,
                da.`start_date` AS `task_start_date`,
                da.`end_date` AS `task_end_date`,
                da.`rotation_start_date` AS `rotation_start_date`,
                da.`rotation_end_date` AS `rotation_end_date`,
                da.`encounter_date` AS `encounter_date`,
                da.`delivery_date` AS `delivery_date`,
                ({$this->buildSubqueryTotalUniqueTargets()}) AS `total_targets`,
                ({$this->buildSubqueryUniqueTargetsPending()}) AS `targets_pending`,
                ({$this->buildSubqueryTargetsInProgress()}) AS `targets_in_progress`,
                ({$this->buildSubqueryTargetsCompleted()}) AS `targets_completed`,
                da.`associated_record_id`,
                da.`associated_record_type`,
                c.`course_id` AS `target_course_id`,
                g.`group_id` AS `target_group_id`,
                u.`id` AS `target_proxy_id`,
                s.`schedule_id` AS `target_schedule_id`,
                s.`schedule_parent_id` AS `schedule_parent_id`,
                s.`title` AS `schedule_title`,
                e.`event_id` AS `target_event_id`,
                e.`event_start` AS `event_timeframe_start`,
                e.`event_finish` AS `event_timeframe_end`,
                e.`event_title` AS `event_title`,
                NULL AS `addelegation_id`,
                adel.`delegator_id` AS `delegator_id`,
                'proxy_id' AS `delegator_type`,
                (IF (adel.`delegator_id`, ({$this->buildSuqueryFetchDelegatorName()}), NULL)) AS `delegator_name`,
                NULL AS `approver_id`,
                NULL AS `approver_name`,
                da.`created_by` as `assessment_creator`
            ";
        }
        return "
             SELECT 
                 $SELECT_fields
             FROM `cbl_distribution_assessments` AS da 
             LEFT JOIN `cbl_assessment_lu_methods` AS am
                 ON am.`assessment_method_id` = da.`assessment_method_id`
             JOIN `cbl_assessments_lu_forms` AS f 
                 ON f.`form_id` = da.`form_id`
             LEFT JOIN `cbl_assessments_lu_form_types` AS ft
                 ON ft.`form_type_id` = f.`form_type_id`
             LEFT JOIN `cbl_assessment_distributions` AS ad 
                 ON ad.`adistribution_id` = da.`adistribution_id`
             LEFT JOIN `cbl_assessment_distribution_schedule` AS ads 
                 ON ads.`adistribution_id` = da.`adistribution_id`
             LEFT JOIN `cbl_assessment_distribution_delegators` AS adel 
                 ON ad.`adistribution_id` = adel.`adistribution_id`
             LEFT JOIN `cbl_schedule` AS s 
                 ON da.`associated_record_id` = s.`schedule_id` AND da.`associated_record_type` = 'schedule_id'
             LEFT JOIN `courses` AS c 
                 ON da.`associated_record_id` = c.`course_id` AND da.`associated_record_type` = 'course_id'
             LEFT JOIN `events` AS e 
                 ON da.`associated_record_id` = e.`event_id` AND da.`associated_record_type` = 'event_id'
             LEFT JOIN `groups` AS g 
                 ON da.`associated_record_id` = g.`group_id` AND da.`associated_record_type` = 'group_id'
             LEFT JOIN `$auth_database`.`user_data` AS u 
                 ON da.`associated_record_id` = u.`id` AND da.`associated_record_type` = 'proxy_id'
             LEFT JOIN `cbl_assessment_progress` AS ap 
                 ON ap.`dassessment_id` = da.`dassessment_id` 
             LEFT JOIN `cbl_assessment_progress_approvals` AS apa 
                 ON apa.`aprogress_id` = ap.`aprogress_id`
             WHERE 1 
                 AND da.`organisation_id` = {$this->actor_organisation_id}
                 AND da.`assessor_value` = ?
                 AND da.`assessor_type` = ?
                 AND (apa.`apapproval_id` IS NULL 
                     OR apa.`approval_status` = 'approved')
                 AND (ad.`visibility_status` = 'visible' OR ad.`visibility_status` IS NULL)
                 $AND_filter_clauses
             $GROUP_clause
        ";
    }

    /**
     * Build a full (primary) query:
     * Select all future task snapshot assessments with all related data for a given proxy_id.
     * Columns are required in this order for use in a UNION.
     *
     * @param string $AND_filter_clauses
     * @param string $GROUP_clause_override
     * @param bool $count_only
     * @return string
     */
    private function buildPrimaryQueryAssessorFutureAssessments($AND_filter_clauses = "", $GROUP_clause_override = "", $count_only = false) {
        $auth_database = AUTH_DATABASE;
        $GROUP_clause = "";
        if ($GROUP_clause_override) {
            $GROUP_clause = $GROUP_clause_override;
        }
        if ($count_only) {
            $SELECT_fields = " COUNT(*) AS `task_count`";
            $GROUP_clause = "";
        } else {
            $SELECT_fields = "
                aft.`future_task_id` AS `task_id`,  
                'future_assessment' AS `task_type`,
                'assessor' AS `task_scope`,
                'incomplete' AS `task_status`,
                aft.`created_date` AS `task_creation_date`,
                NULL AS `task_completion_date`,
                NULL AS `task_updated_date`,
                aft.`deleted_date`,
                ad.`adistribution_id`,
                ad.`description` AS `distribution_description`,
                NULL AS `dassessment_id`, 
                NULL AS `assessment_method_id`,
                NULL as `aprogress_id`,
                ad.`organisation_id`,
                f.`form_id`,
                ft.`category` COLLATE 'utf8_unicode_ci'  AS `form_type_category`,
                ft.`shortname` COLLATE 'utf8_unicode_ci'  AS `form_type_shortname`,
                ft.`title` COLLATE 'utf8_unicode_ci'  AS `form_type_title`,
                IFNULL(ad.`title`, f.`title`) AS `task_title`,
                aft.`assessor_value` AS `task_assessor_value`,
                aft.`assessor_type` AS `task_assessor_type`,
                CONCAT('NULL', '-', aft.`target_value`, '-', aft.`target_type`) AS `task_targets`,
                aft.`start_date` AS `task_start_date`,
                aft.`end_date` AS `task_end_date`,
                aft.`rotation_start_date` AS `rotation_start_date`,
                aft.`rotation_end_date` AS `rotation_end_date`,
                NULL as `encounter_date`,
                aft.`delivery_date` AS `delivery_date`,
                1 AS `total_targets`,
                1 AS `targets_pending`,
                0 AS `targets_in_progress`,
                0 AS `targets_completed`,
                aft.`associated_record_id` AS `associated_record_id`,
                aft.`associated_record_type` AS `associated_record_type`,
                c.`course_id` AS `target_course_id`,
                g.`group_id` AS `target_group_id`,
                u.`id` AS `target_proxy_id`,
                s.`schedule_id` AS `target_schedule_id`,
                s.`schedule_parent_id` AS `schedule_parent_id`,
                s.`title` AS `schedule_title`,
                NULL AS `target_event_id`,
                NULL AS `event_timeframe_start`,
                NULL AS `event_timeframe_end`,
                NULL AS `event_title`,
                NULL AS `addelegation_id`,
                NULL AS `delegator_id`,
                NULL AS `delegator_type`,
                NULL AS `delegator_name`,
                NULL AS `approver_id`,
                NULL AS `approver_name`,
                NULL AS `assessment_creator`
            ";
        }
        return "
             SELECT 
                 $SELECT_fields
             FROM `cbl_assessment_ss_future_tasks` AS aft 
             LEFT JOIN `cbl_assessment_distributions` AS ad 
                 ON ad.`adistribution_id` = aft.`adistribution_id`
             JOIN `cbl_assessments_lu_forms` AS f 
                 ON f.`form_id` = ad.`form_id`
             LEFT JOIN `cbl_assessments_lu_form_types` AS ft
                 ON ft.`form_type_id` = f.`form_type_id`
             LEFT JOIN `cbl_assessment_distribution_schedule` AS ads 
                 ON ads.`adistribution_id` = ad.`adistribution_id`
             LEFT JOIN `cbl_assessment_distribution_delegators` AS adel 
                 ON ad.`adistribution_id` = adel.`adistribution_id`
             LEFT JOIN `cbl_schedule` AS s 
                 ON aft.`associated_record_id` = s.`schedule_id` AND aft.`associated_record_type` = 'schedule_id'
             LEFT JOIN `courses` AS c 
                 ON aft.`associated_record_id` = c.`course_id` AND aft.`associated_record_type` = 'course_id'
             LEFT JOIN `groups` AS g 
                 ON aft.`associated_record_id` = g.`group_id` AND aft.`associated_record_type` = 'group_id'
             LEFT JOIN `$auth_database`.`user_data` AS u 
                 ON aft.`associated_record_id` = u.`id` AND aft.`associated_record_type` = 'proxy_id'
             WHERE 1 
                 AND ad.`organisation_id` = {$this->actor_organisation_id}
                 AND aft.`assessor_value` = ?
                 AND aft.`assessor_type` = ?
                 AND aft.`deleted_date` IS NULL
                 AND (aft.`adistribution_id` IS NULL 
                     OR (ad.`adistribution_id` IS NOT NULL AND ad.`deleted_date` IS NULL))
                 AND (ad.`visibility_status` = 'visible' OR ad.`visibility_status` IS NULL)
                 $AND_filter_clauses
             $GROUP_clause
        ";
    }

    /**
     * Build a full (primary) query:
     * Select all assessments with all related data where the given proxy_id is the target.
     *
     * @param string $AND_filter_clauses
     * @param string $GROUP_clause_override
     * @param bool $count_only
     * @return string
     */
    private function buildPrimaryQueryTargetAssessments($AND_filter_clauses = "", $GROUP_clause_override = "", $count_only = false) {
        $auth_database = AUTH_DATABASE;
        $GROUP_clause = "GROUP BY da.`dassessment_id`, ap.`aprogress_id`";
        if ($GROUP_clause_override) {
            $GROUP_clause = $GROUP_clause_override;
        }
        if ($count_only) {
            $SELECT_fields = " COUNT(DISTINCT(da.`dassessment_id`)) AS `task_count`";
            $GROUP_clause = "";
        } else {
            $SELECT_fields = "
                dat.`atarget_id` AS `task_id`,  
                'assessment' AS `task_type`,
                'target' AS `task_scope`,
                ap.`progress_value` AS `task_status`,
                da.`created_date` AS `task_creation_date`,
                IF(ap.`progress_value` = 'complete', IFNULL(ap.`updated_date`, ap.`created_date`), NULL) AS `task_completion_date`,
                ap.`updated_date` AS `task_updated_date`,
                da.`deleted_date`,
                da.`adistribution_id`,
                ad.`description` AS `distribution_description`,
                da.`dassessment_id`, 
                da.`assessment_method_id`,
                ap.`aprogress_id` as `aprogress_id`,
                da.`organisation_id`,
                f.`form_id`,
                ft.`category` COLLATE 'utf8_unicode_ci'  AS `form_type_category`,
                ft.`shortname` COLLATE 'utf8_unicode_ci'  AS `form_type_shortname`,
                ft.`title`  COLLATE 'utf8_unicode_ci'  AS `form_type_title`,
                IFNULL(ad.`title`, f.`title`) AS `task_title`,
                da.`assessor_value` AS `task_assessor_value`,
                da.`assessor_type` AS `task_assessor_type`,
                ({$this->buildSubqueryConcatTargets()}) AS `task_targets`,
                da.`start_date` AS `task_start_date`,
                da.`end_date` AS `task_end_date`,
                da.`rotation_start_date` AS `rotation_start_date`,
                da.`rotation_end_date` AS `rotation_end_date`,
                da.`encounter_date` AS `encounter_date`,
                da.`delivery_date` AS `delivery_date`,
                ({$this->buildSubqueryTotalUniqueTargets()}) AS `total_targets`,
                ({$this->buildSubqueryUniqueTargetsPending()}) AS `targets_pending`,
                ({$this->buildSubqueryTargetsInProgress()}) AS `targets_in_progress`,
                ({$this->buildSubqueryTargetsCompleted()}) AS `targets_completed`,
                da.`associated_record_id`,
                da.`associated_record_type`,
                c.`course_id` AS `target_course_id`,
                g.`group_id` AS `target_group_id`,
                u.`id` AS `target_proxy_id`,
                s.`schedule_id` AS `target_schedule_id`,
                s.`schedule_parent_id` AS `schedule_parent_id`,
                s.`title` AS `schedule_title`,
                e.`event_id` AS `target_event_id`,
                e.`event_start` AS `event_timeframe_start`,
                e.`event_finish` AS `event_timeframe_end`,
                e.`event_title` AS `event_title`,
                adel.`delegator_id` AS `delegator_id`,
                'proxy_id' AS `delegator_type`,
                NULL AS `addelegation_id`,
                (IF (adel.`delegator_id`, ({$this->buildSuqueryFetchDelegatorName()}), NULL)) AS `delegator_name`,
                NULL AS `approver_id`,
                NULL AS `approver_name`,
                da.`created_by` as `assessment_creator`
            ";
        }
        return "
             SELECT 
                 $SELECT_fields
             FROM `cbl_distribution_assessment_targets` AS dat
             JOIN `cbl_distribution_assessments` AS da
                 ON dat.`dassessment_id` = da.`dassessment_id`
             LEFT JOIN `cbl_assessment_progress` AS ap
                ON da.`dassessment_id` = ap.`dassessment_id`
             LEFT JOIN `cbl_assessment_lu_methods` AS am
                 ON am.`assessment_method_id` = da.`assessment_method_id`
             JOIN `cbl_assessments_lu_forms` AS f 
                 ON f.`form_id` = da.`form_id`
             LEFT JOIN `cbl_assessments_lu_form_types` AS ft
                 ON ft.`form_type_id` = f.`form_type_id`
             LEFT JOIN `cbl_assessment_distributions` AS ad 
                 ON ad.`adistribution_id` = da.`adistribution_id`
             LEFT JOIN `cbl_assessment_distribution_schedule` AS ads 
                 ON ads.`adistribution_id` = da.`adistribution_id`
             LEFT JOIN `cbl_assessment_distribution_delegators` adel 
                 ON ad.`adistribution_id` = adel.`adistribution_id`
             LEFT JOIN `cbl_schedule` AS s 
                 ON da.`associated_record_id` = s.`schedule_id` AND da.`associated_record_type` = 'schedule_id'
             LEFT JOIN `courses` AS c 
                 ON da.`associated_record_id` = c.`course_id` AND da.`associated_record_type` = 'course_id'
             LEFT JOIN `events` AS e 
                 ON da.`associated_record_id` = e.`event_id` AND da.`associated_record_type` = 'event_id'
             LEFT JOIN `groups` AS g 
                 ON da.`associated_record_id` = g.`group_id` AND da.`associated_record_type` = 'group_id'
             LEFT JOIN `$auth_database`.`user_data` AS u 
                 ON da.`associated_record_id` = u.`id` AND da.`associated_record_type` = 'proxy_id'
             LEFT JOIN `cbl_assessment_progress_approvals` AS apa 
                 ON apa.`aprogress_id` = ap.`aprogress_id`
             WHERE 1 
                 AND da.`organisation_id` = {$this->actor_organisation_id}
                 AND dat.`target_value` = ?
                 AND dat.`target_type` = ?
                 AND ((da.`deleted_date` IS NULL AND dat.`deleted_date` IS NULL)
                     OR (ap.`progress_value` = 'complete' AND ap.`deleted_date` IS NULL))
                 AND (da.`adistribution_id` IS NULL 
                     OR (ap.`progress_value` = 'complete' || (ad.`adistribution_id` IS NOT NULL AND ad.`deleted_date` IS NULL)))
                 AND (apa.`apapproval_id` IS NULL 
                     OR apa.`approval_status` = 'approved')
                 AND (ad.`visibility_status` = 'visible' OR ad.`visibility_status` IS NULL)
                 $AND_filter_clauses
             $GROUP_clause 
        ";
    }

    /**
     * Build a full (primary) query:
     * Select all future task snapshot assessments with all related data for a given proxy_id as the target.
     * Columns are required in this order for use in a UNION.
     *
     * @param string $AND_filter_clauses
     * @param string $GROUP_clause_override
     * @param bool $count_only
     * @return string
     */
    private function buildPrimaryQueryTargetFutureAssessments($AND_filter_clauses = "", $GROUP_clause_override = "", $count_only = false) {
        $auth_database = AUTH_DATABASE;
        $GROUP_clause = "";
        if ($GROUP_clause_override) {
            $GROUP_clause = $GROUP_clause_override;
        }
        if ($count_only) {
            $SELECT_fields = " COUNT(*) AS `task_count`";
            $GROUP_clause = "";
        } else {
            $SELECT_fields = "
                aft.`future_task_id` AS `task_id`,  
                'future_assessment' AS `task_type`,
                'target' AS `task_scope`,
                'incomplete' AS `task_status`,
                aft.`created_date` AS `task_creation_date`,
                NULL AS `task_completion_date`,
                NULL AS `task_updated_date`,
                aft.`deleted_date`,
                ad.`adistribution_id`,
                ad.`description` AS `distribution_description`,
                NULL AS `dassessment_id`, 
                NULL AS `assessment_method_id`,
                NULL as `aprogress_id`,
                ad.`organisation_id`,
                f.`form_id`,
                ft.`category` COLLATE 'utf8_unicode_ci'  AS `form_type_category`,
                ft.`shortname` COLLATE 'utf8_unicode_ci'  AS `form_type_shortname`,
                ft.`title` COLLATE 'utf8_unicode_ci'  AS `form_type_title`,
                IFNULL(ad.`title`, f.`title`) AS `task_title`,
                aft.`assessor_value` AS `task_assessor_value`,
                aft.`assessor_type` AS `task_assessor_type`,
                CONCAT('NULL', '-', aft.`target_value`, '-', aft.`target_type`) AS `task_targets`,
                aft.`start_date` AS `task_start_date`,
                aft.`end_date` AS `task_end_date`,
                aft.`rotation_start_date` AS `rotation_start_date`,
                aft.`rotation_end_date` AS `rotation_end_date`,
                NULL as `encounter_date`,
                aft.`delivery_date` AS `delivery_date`,
                1 AS `total_targets`,
                1 AS `targets_pending`,
                0 AS `targets_in_progress`,
                0 AS `targets_completed`,
                aft.`associated_record_id` AS `associated_record_id`,
                aft.`associated_record_type` AS `associated_record_type`,
                c.`course_id` AS `target_course_id`,
                g.`group_id` AS `target_group_id`,
                u.`id` AS `target_proxy_id`,
                s.`schedule_id` AS `target_schedule_id`,
                s.`schedule_parent_id` AS `schedule_parent_id`,
                s.`title` AS `schedule_title`,
                NULL AS `target_event_id`,
                NULL AS `event_timeframe_start`,
                NULL AS `event_timeframe_end`,
                NULL AS `event_title`,
                NULL AS `addelegation_id`,
                NULL AS `delegator_id`,
                NULL AS `delegator_type`,
                NULL AS `delegator_name`,
                NULL AS `approver_id`,
                NULL AS `approver_name`,
                NULL AS `assessment_creator`
            ";
        }
        return "
             SELECT 
                 $SELECT_fields
             FROM `cbl_assessment_ss_future_tasks` AS aft 
             LEFT JOIN `cbl_assessment_distributions` AS ad 
                 ON ad.`adistribution_id` = aft.`adistribution_id`
             JOIN `cbl_assessments_lu_forms` AS f 
                 ON f.`form_id` = ad.`form_id`
             LEFT JOIN `cbl_assessments_lu_form_types` AS ft
                 ON ft.`form_type_id` = f.`form_type_id`
             LEFT JOIN `cbl_assessment_distribution_schedule` AS ads 
                 ON ads.`adistribution_id` = ad.`adistribution_id`
             LEFT JOIN `cbl_assessment_distribution_delegators` AS adel 
                 ON ad.`adistribution_id` = adel.`adistribution_id`
             LEFT JOIN `cbl_schedule` AS s 
                 ON aft.`associated_record_id` = s.`schedule_id` AND aft.`associated_record_type` = 'schedule_id'
             LEFT JOIN `courses` AS c 
                 ON aft.`associated_record_id` = c.`course_id` AND aft.`associated_record_type` = 'course_id'
             LEFT JOIN `groups` AS g 
                 ON aft.`associated_record_id` = g.`group_id` AND aft.`associated_record_type` = 'group_id'
             LEFT JOIN `$auth_database`.`user_data` AS u 
                 ON aft.`associated_record_id` = u.`id` AND aft.`associated_record_type` = 'proxy_id'
             WHERE 1 
                 AND ad.`organisation_id` = {$this->actor_organisation_id}
                 AND aft.`target_value` = ?
                 AND aft.`target_type` = ?
                 AND aft.`deleted_date` IS NULL
                 AND (aft.`adistribution_id` IS NULL 
                     OR (ad.`adistribution_id` IS NOT NULL AND ad.`deleted_date` IS NULL))
                 AND (ad.`visibility_status` = 'visible' OR ad.`visibility_status` IS NULL)
                 $AND_filter_clauses
             $GROUP_clause
        ";
    }

}