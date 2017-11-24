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
 * A class to handle reporting on assessment forms.
 *
 * The core functionality of this object is to fetch all of the forms
 * of a given form-id submitted on a user, aggregate that data, and return the contents
 * in a view-consumable data structure.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */
class Entrada_Utilities_Assessments_Reports extends Entrada_Utilities_Assessments_Base {
    // Class properties set at construction time.
    protected $target_type = null,
              $target_value = null,
              $target_scope = null,
              $course_id = null,
              $organisation_id = null,
              $adistribution_id = null,
              $form_id = null,
              $cperiod_id = null,
              $group_by_distribution = false,
              $prune_empty_rubrics = true,
              $cleanup_rubrics = true,
              $actor_proxy_id = null, // used for caching
              $start_date = null,
              $end_date = null;

    // Flag for disabling caching; can be set at construction time
    protected $disable_file_caching = true;

    // Internal data structure for report data.
    private   $report_data = array();

    // Zend_Cache object.
    private   $report_cache = null;

    /**
     * Entrada_Utilities_Assessments_Reports constructor.
     *
     * @param null|array() $arr
     */
    public function __construct($arr = null) {
        parent::__construct($arr);
        $this->setZendCache();
    }

    //--- Getters/Setters ---//

    public function getProxyID() {
        return $this->target_value;
    }

    public function getCourseID() {
        return $this->course_id;
    }

    public function getCperiodID() {
        return $this->cperiod_id;
    }

    public function getFormID() {
        return $this->form_id;
    }

    public function getOrganisationID() {
        return $this->organisation_id;
    }

    public function getADistributionID() {
        return $this->adistribution_id;
    }

    public function getDistributionID() {
        return $this->adistribution_id;
    }

    public function getGroupByDistribution() {
        return $this->group_by_distribution;
    }

    //--- Public functionality ---//

    /**
     * Fetch a session-level preference setting. This is specific to the reporting summary being accessed from the assessments module.
     *
     * @param $specified_preference
     * @return null
     */
    public static function getPreferenceFromSession($specified_preference) {
        // Session values are stored as such: $_SESSION[ APPLICATION_IDENTIFIER ][ MODULE ][ SUBMODULE ][ variable ]
        // This preference setting assumes this functionality is using assessments/assessment_reports to store its preferences.
        // This isn't really problematic from a functional standpoint, but might be semantically awkward if this object is used
        // from outside of this module/submodule.
        if (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["assessment_reports"][$specified_preference])) {
            return $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["assessment_reports"][$specified_preference];
        } else {
            return null;
        }
    }

    public static function hasReportAccess(&$ENTRADA_ACL, &$ENTRADA_USER, $proxy_id, $role, $override_acl = null) {

        if ($override_acl !== null) {
            return $override_acl;

        } else if ($role == "learner" && ($ENTRADA_ACL->amIAllowed(new AcademicAdvisorResource($proxy_id), "read", true) || $ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true))) {
            return true;

        } else if ($role == "faculty") {
            $course_owner = false;
            $faculty_is_associated_with_course = false;

            // Check whether the person attempting to view the course is a PA/PD
            $courses = Models_Course::getUserCourses($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation());
            if (is_array($courses)) {
                foreach ($courses as $course) {
                    if (CourseOwnerAssertion::_checkCourseOwner($ENTRADA_USER->getActiveID(), $course->getID())) {
                        $course_owner = true;
                    }
                }
            }

            $admin = $ENTRADA_ACL->amIAllowed("assessmentreportadmin", "read", true);

            // Check if the user is associated with the given facutly
            $assessment_user = new Entrada_Utilities_AssessmentUser();
            $associated_faculty = $assessment_user->getFaculty($ENTRADA_USER->getActiveId(), $ENTRADA_USER->getActiveOrganisation(), $admin, ENTRADA_URL);
            foreach ($associated_faculty as $faculty_person) {
                if ($faculty_person->getProxyID() == $proxy_id) {
                    $faculty_is_associated_with_course = true;
                }
            }

            if ($course_owner && $faculty_is_associated_with_course) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    /**
     * Check if the given ID exists in the current set of unique curriculum period IDs. If it does not exist, use the last one in the set instead.
     *
     * @param $selected_id
     * @return mixed
     */
    public function adjustSelectedCurriculumPeriodID($selected_id) {
        $unique_cperiods = $this->getUniqueCurriculumPeriodData();
        foreach ($unique_cperiods as $cperiod_id => $cperiod_data) {
            if ($selected_id == $cperiod_id) {
                return $selected_id; // It's in there, so we can just exit
            }
        }
        // It's not in the meta data, so we adjust for the last one in the current meta list.
        $last_item = end($unique_cperiods);
        $selected_cperiod_id = $last_item["cperiod_id"];
        return $selected_cperiod_id;
    }

    private function createTargetValueClause($target_ids) {
        if (is_array($target_ids) && !empty($target_ids)) {
            $clean_target_ids = array_map(
                function($val) {
                    return clean_input($val, array("trim", "int"));
                },
                $target_ids
            );
            $imploded = implode(",", $clean_target_ids);
            $AND_target_value = "AND p.`target_record_id` IN ($imploded)";
        } else {
            $target_id = clean_input($target_ids, array("trim", "int"));
            $AND_target_value = "AND p.`target_record_id` = $target_id";
        }
        return $AND_target_value;
    }

    private function createDistributionIDValueClause($distribution_ids) {
        if (is_array($distribution_ids) && !empty($distribution_ids)) {
            $clean_distribution_ids = array_map(
                function($val) {
                    return clean_input($val, array("trim", "int"));
                },
                $distribution_ids
            );
            $imploded = implode(",", $clean_distribution_ids);
            $AND_distribution_id = "AND p.`adistribution_id` IN ($imploded)";
        } else {
            $distribution_id = clean_input($distribution_ids, array("trim", "int"));
            $AND_distribution_id = "AND p.`adistribution_id` = $distribution_id";
        }
        return $AND_distribution_id;
    }

    private function createEventTypeIDValueClause($target_ids) {
        if (is_array($target_ids) && !empty($target_ids)) {
            $clean_target_ids = array_map(
                function($val) {
                    return clean_input($val, array("trim", "int"));
                },
                $target_ids
            );
            $imploded = implode(",", $clean_target_ids);
            $AND_target_value = "AND e.`eventtype_id` IN ($imploded)";
        } else {
            $target_id = clean_input($target_ids, array("trim", "int"));
            $AND_target_value = "AND e.`eventtype_id` = $target_id";
        }
        return $AND_target_value;
    }

    private function createCperiodIDClause($cperiod_ids) {
        $AND_cperiod_value = "";

        if (is_array($cperiod_ids) && !empty($cperiod_ids)) {
            $clean_target_ids = array_map(
                function($val) {
                    return clean_input($val, array("trim", "int"));
                },
                $cperiod_ids
            );
            $imploded = implode(",", $clean_target_ids);
            $AND_cperiod_value = "AND d.`cperiod_id` IN ($imploded)";
        } else if ($cperiod_ids) {
            $cperiod_id = clean_input($cperiod_ids, array("trim", "int"));
            $AND_cperiod_value = "AND d.`cperiod_id` = $cperiod_id";
        }

        return $AND_cperiod_value;
    }

    private function createCourseIDClause($course_ids) {
        $AND_course_value = "";

        if (is_array($course_ids) && !empty($course_ids)) {
            $clean_target_ids = array_map(
                function($val) {
                    return clean_input($val, array("trim", "int"));
                },
                $course_ids
            );
            $imploded = implode(",", $clean_target_ids);
            $AND_course_value = "AND d.`course_id` IN ($imploded)";
        } else if ($course_ids) {
            $course_id = clean_input($course_ids, array("trim", "int"));
            $AND_course_value = "AND d.`course_id` = $course_id";
        }

        return $AND_course_value;
    }

    /**
     * Fetch a summary of the assessments to report on.
     * The return value is cached, so this method can be called multiple times by other methods.
     *
     * @return array
     */
    public function fetchCompletedAssessmentsMeta() {
        global $db;
        $prepared_variables = array();

        $JOIN_events_on_event_id = "";
        $AND_associated_record_type = "";
        $GROUP_by_adtarget_id = "";
        $GROUP_by_proxies = "";

        if (is_array($this->target_value)) {
            $GROUP_by_proxies = "p.`target_record_id`, ";
        }

        $AND_target_value    = $this->createTargetValueClause($this->target_value);
        if (!is_null($this->adistribution_id)) {
            $AND_distribution_id = $this->createDistributionIDValueClause($this->adistribution_id);
        } else {
            $AND_distribution_id = "";
        }

        $AND_eventtype_id    = "";
        $AND_date_greater    = "";
        $AND_date_less       = "";
        $AND_target_course   = "";
        $AND_cperiod_id      = $this->createCperiodIDClause($this->cperiod_id);
        $AND_course_id       = $this->createCourseIDClause($this->course_id);

        $AND_organisation_id = $this->organisation_id  ? "AND d.`organisation_id` = ?" : "";
        $AND_form_id         = $this->form_id          ? "AND d.`form_id` = ?" : "";
        $AND_target_scope    = $this->target_scope     ? "AND adt.`target_scope` = ?" : "";

        $SELECT_additional_fields = $GROUP_by_distribution = "";
        if ($this->group_by_distribution) {
            $SELECT_additional_fields = "p.`adtarget_id`, d.`adistribution_id`, d.`title` AS `distribution_title`, d.`description`,";
            $GROUP_by_distribution =  "d.`adistribution_id`,";
        }

        if (!is_null($this->start_date)) {
            $AND_date_greater = " AND a.`delivery_date` >= ". $db->qstr($this->start_date);
        }

        if (!is_null($this->end_date)) {
            $AND_date_less = "    AND a.`delivery_date` <= ". $db->qstr($this->end_date);
        }

        if ($this->target_type) {
            switch ($this->target_type) {
                case "proxy_id":
                    break;
                case "schedule_id":
                    if ($this->target_scope == "self") {
                        // The schedule is the target
                        $GROUP_by_adtarget_id = "adt.`adtarget_id`, ";
                    }
                    break;
                case "eventtype_id":
                    $JOIN_events_on_event_id = "JOIN `events` AS e ON a.`associated_record_id` = e.`event_id`";
                    $SELECT_additional_fields .= "e.`event_id`, e.`eventtype_id`, d.`adistribution_id`, ";
                    $AND_associated_record_type = "AND a.`associated_record_type` = 'event_id'";

                    // Not using target value but rather distribution ID instead.
                    $AND_target_value = "";
                    $AND_eventtype_id = $this->createEventTypeIDValueClause($this->target_value);
                    $AND_distribution_id = $this->createDistributionIDValueClause($this->adistribution_id);

                    // override distribution grouping
                    $GROUP_by_distribution = "";
                    $this->group_by_distribution = false;
                    break;
                case "course_id":
                    $course_id = clean_input($this->target_value, array("trim", "int"));
                    $AND_course_id = $this->createCourseIDClause($course_id);
                    $AND_target_course = "  AND adt.`target_type` = 'course_id'
                                            AND adt.`target_scope` = 'self'
                                            AND adt.`target_role` = 'any'
                                            AND adt.`target_id` = $course_id";
                    break;
            }
        }

        // Build pseudo-cache key using query variables.
        $cache_key = array(
            $this->target_value,
            $this->target_type,
            $this->organisation_id,
            $this->adistribution_id,
            $this->course_id,
            $this->form_id,
            $this->cperiod_id,
            $this->group_by_distribution,
            $this->target_scope
        );
        $cache_key = md5(serialize($cache_key));

        // Check and see if this exact metadata was queried for already.
        if ($this->isInStorage("completed_assessments_meta", $cache_key)) {
            // Return the cached version
            return $this->fetchFromStorage("completed_assessments_meta", $cache_key);

        } else {

            // Find all completed assessments for the given parameters
            $query = "  SELECT  COUNT(*) AS form_count,
                            d.`form_id`, d.`course_id`, d.`cperiod_id`,
                            cp.`start_date` AS `cperiod_start`, cp.`finish_date` AS `cperiod_end`, cp.`active` AS `cperiod_active`,
                            p.`target_record_id`,
                            $SELECT_additional_fields
                            f.`title` AS `form_title`,
                            adt.`adtarget_id`, adt.`target_scope`

                    FROM `cbl_assessment_progress`              AS p
                    JOIN `cbl_assessment_distributions`         AS d  ON p.`adistribution_id` = d.`adistribution_id`
                    JOIN `cbl_distribution_assessments`         AS a  ON p.`dassessment_id` = a.`dassessment_id` $AND_associated_record_type
                    JOIN `cbl_assessments_lu_forms`             AS f  ON d.`form_id` = f.`form_id`
                    JOIN `curriculum_periods`                   AS cp ON d.`cperiod_id` = cp.`cperiod_id`
                    JOIN `cbl_assessment_distribution_targets`  AS adt ON p.`adtarget_id` = adt.`adtarget_id`
                    $JOIN_events_on_event_id

                    WHERE p.`progress_value` = 'complete'
                    AND   p.`deleted_date` IS NULL
                    $AND_target_value
                    $AND_organisation_id
                    $AND_distribution_id
                    $AND_course_id
                    $AND_form_id
                    $AND_cperiod_id
                    $AND_target_scope
                    $AND_eventtype_id
                    $AND_target_course
                    $AND_date_greater
                    $AND_date_less

                    GROUP BY $GROUP_by_proxies $GROUP_by_adtarget_id cp.`cperiod_id`, $GROUP_by_distribution d.`course_id`, f.`form_id`
                    ORDER BY f.`form_id`";

            foreach (array($this->organisation_id, $this->form_id, $this->target_scope) as $prepare) {
                if ($prepare) {
                    $prepared_variables[] = $prepare;
                }
            }

            $grouped_by_cperiod = array();
            $result_set = $db->GetAll($query, $prepared_variables);
            if (is_array($result_set)) {
                foreach ($result_set as $result) {
                    $grouped_by_cperiod[$result["cperiod_id"]][] = $result;
                }
            }

            // save the result set for later use.
            $this->addToStorage("completed_assessments_meta", $grouped_by_cperiod, $cache_key);
            return $grouped_by_cperiod;
        }
    }

    /**
     * Fetch the unique curriculum period information from the completed assessments meta data and return them in an array.
     *
     * @return array
     */
    public function getUniqueCurriculumPeriodData() {
        $cperiods = array();
        foreach ($this->fetchCompletedAssessmentsMeta() as $cperiod_id => $assessments_for_period) {
            foreach ($assessments_for_period as $meta) {
                $cperiods[$meta["cperiod_id"]] = array(
                    "cperiod_id" => $meta["cperiod_id"],
                    "start_date" => $meta["cperiod_start"],
                    "finish_date" => $meta["cperiod_end"]
                );
            }
        }
        return $cperiods;
    }

    /**
     * Fetch a list of all the completed assessments for the given reporting parameters.
     *
     * @return array
     */
    public function fetchCompletedAssessmentsList() {
        $assessment_list = array();
        foreach ($this->fetchCompletedAssessmentsMeta() as $cperiod_id => $summary) {
            foreach ($summary as $meta) {
                $completions = $this->fetchCompletedProgressData(
                    $meta["target_record_id"],
                    $meta["course_id"],
                    $meta["cperiod_id"],
                    $meta["form_id"],
                    (isset($meta["adistribution_id"])) ? $meta["adistribution_id"] : null // optionally filter by distribution ID
                );
                // For each completed assessment, fetch all the responses and store them in the results array
                foreach ($completions as $completed) {
                    $url = ENTRADA_URL ."/assessments/assessment?adistribution_id={$completed["adistribution_id"]}&target_record_id={$completed["target_record_id"]}&aprogress_id={$completed["aprogress_id"]}&dassessment_id={$completed["dassessment_id"]}";
                    // fetch assessment
                    $assessment = Models_Assessments_Assessor::fetchRowByID($completed["dassessment_id"]);
                    $assessor_name = "";
                    $completed_on = $completed["updated_date"];
                    $deleted = false;
                    if ($assessment) {
                        $deleted = true;
                        $assessor_user = $this->getUserByType($assessment->getAssessorValue(), $assessment->getAssessorType());
                        if ($assessor_user) {
                            $assessor_name = "{$assessor_user->getFirstname()} {$assessor_user->getLastname()}";
                        }
                    }
                    $assessment_list[] = array(
                        "url" => $url,
                        "status" => $deleted,
                        "assessor_name" => $assessor_name,
                        "completed_on" => $completed_on ? $completed_on : 0
                    );
                }
            }
        }
        return $assessment_list;
    }

    /**
     * Generate the report data for the given report parameters, either specified or derived from object properties.
     *
     * @return array
     */
    public function generateReport() {
        if ($cached_report = $this->fetchCachedReport()) {
            $this->report_data = $cached_report;
        } else {
            // Fetch and iterate through the metadata (our report parameters).
            // fetchCompletedAssessmentsMeta() guarantees an array as return value.
            foreach ($this->fetchCompletedAssessmentsMeta() as $cperiod_id => $summary) {
                // The metadata could potentially be multiple records. The summary data is grouped by course_id and form_id (optionally limited by distribution ID).
                foreach ($summary as $meta) {
                    // Seed our internal results array based on the current incarnation of the form. This ensures our report can display
                    // any free-text items and items with no responses that may have been optional.
                    $this->reportDataConfigure($meta["form_id"]);

                    // Fetch all completed progress records. We build the report using progress records as to ensure we find all responses, even if the form or form items have changed.
                    $completions = $this->fetchCompletedProgressData(
                        $meta["target_record_id"],
                        $meta["course_id"],
                        $meta["cperiod_id"],
                        $meta["form_id"],
                        (isset($meta["adistribution_id"])) ? $meta["adistribution_id"] : null, // optionally filter by distribution ID
                        (isset($meta["eventtype_id"])) ? "event" : null
                    );
                    // For each completed assessment, fetch all the responses and store them in the internal report_data array
                    foreach ($completions as $completed) {
                        $responses = $this->fetchAllAssessmentResponsesData($completed["aprogress_id"]);
                        foreach ($responses as $response_data) {
                            if ($response_data["element_type"] == "item") {
                                if ($response_data["rubric_id"]) {
                                    // This form element is a rubric.
                                    // Fetch all of the rubric data and add it to the report data structure.
                                    $rubric = $this->fetchRubricData($response_data["rubric_id"]);
                                    if ($rubric) {
                                        $this->reportDataAddNodeGroupedItem($response_data["rubric_id"], $response_data["element_id"], $response_data["ardescriptor_id"], $response_data["item_response_text"], $response_data["comments"], $rubric);
                                    } else {
                                        application_log("error", "Reports: Failed to fetch data for rubric {$response_data["rubric_id"]}");
                                    }

                                } else if ($response_data["itemtype_shortname"] == "free_text") {
                                    // This form element a free-text comment.
                                    $this->reportDataAddNodeFreeTextComment($response_data["element_id"], $response_data["form_element_order"], $response_data["comments"]);

                                } else {
                                    // This form element is a single item (a scale or multiple choice, or other)
                                    // Fetch all responses (fetchItemResponsesDataByItemElement adds a group_key)
                                    $all_responses = $this->fetchItemResponsesDataByItemElement($response_data["element_id"]);

                                    // Determine if we're using the text of the item or the descriptor to do our comparison on
                                    $compare_on_descriptor = $this->determineUseDescriptorComparison($all_responses);

                                    // Group by the group_key (ensures no duplicate items)
                                    $grouped_responses = $this->groupResponsesByGroupKey($all_responses);

                                    // Add the response data to the report structure
                                    $this->reportDataAddNodeSingleElement(
                                        $response_data["element_id"],
                                        $compare_on_descriptor ? $response_data["response_descriptor"] : $response_data["item_response_text"],
                                        $response_data["comments"],
                                        $grouped_responses
                                    );
                                }

                            } else if ($response_data["element_type"] == "objective") {
                                // Objective set selection
                                // TODO: Support objectives later
                            }
                        }
                    }
                }
            }

            // Cleanup rubrics
            // There are some forms out there that have duplicate responses descriptors for some (but not all) of the responses.
            // The initial report logic aggregates them correctly, but since they appear in different orders, they are added in more than one position, duplicating
            // the response. We prune the extras here.
            if ($this->cleanup_rubrics) {
                foreach ($this->report_data as $type_and_index => $report_node) {
                    $type_and_index_a = explode("-", $type_and_index);
                    $type = $type_and_index_a[0];
                    if ($type == "rubric") {
                        foreach ($report_node["responses"] as $i => $response) {
                            foreach ($response["rubric_response_detail"] as $element_id => $rubric_responses) {
                                foreach ($rubric_responses as $item_order_find => $response_detail_find) {
                                    foreach ($rubric_responses as $item_order_found => $response_detail_found) {
                                        if ($item_order_find == $item_order_found) {
                                            continue;
                                        } else {
                                            if ($response_detail_find["ardescriptor_id"] == $response_detail_found["ardescriptor_id"]) {
                                                // Remove the extraneous grouping
                                                unset($this->report_data[$type_and_index]["responses"][$i]["rubric_response_detail"][$element_id][$item_order_found]);

                                                // Remove them from the local copies, so we don't try and remove it anymore (thereby stopping the appropriate grouping from being removed)
                                                unset($rubric_responses[$item_order_found]);
                                                unset($rubric_responses[$item_order_find]);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Prune empty rubric responses.
            // There may be some empty responses if, by some error, the rubric was edited or the task was erroneously marked as complete.
            if ($this->prune_empty_rubrics) {
                foreach ($this->report_data as $type_and_index => $report_node) {
                    $type_and_index_a = explode("-", $type_and_index);
                    $type = $type_and_index_a[0];
                    if ($type == "rubric") {
                        foreach ($report_node["responses"] as $i => $response) {
                            foreach ($response["rubric_response_detail"] as $element_id => $rubric_responses) {
                                $response_count = 0;
                                foreach ($rubric_responses as $item_order => $response_detail) {
                                    $response_count += $response_detail["count"];
                                }
                                if ($response_count == 0) {
                                    // This rubric element has no responses, so let's prune it.
                                    unset($this->report_data[$type_and_index]["responses"][$i]);
                                }
                            }
                        }
                    }
                }
            }
            // After processing, cache this report
            $this->cacheReport();
        }

        return $this->report_data;
    }

    //--- Private Methods ---//

    /**
     * Iterates through the item responses and determine if we should compare on the item text, or the response descriptors.
     *
     * @param $item_responses
     * @return bool
     */
    private function determineUseDescriptorComparison($item_responses) {
        $use_descriptors = false;
        $unique_texts = false;
        $unique_descriptors = false;

        foreach ($item_responses as $found_index => $response_found) {
            foreach ($item_responses as $find_index => $response_find) {
                if ($response_find["text_sanitized"] != $response_found["text_sanitized"]) {
                    $unique_texts = true;
                }
            }
        }

        foreach ($item_responses as $found_index => $response_found) {
            foreach ($item_responses as $find_index => $response_find) {
                if ($response_find["descriptor_sanitized"] != $response_found["descriptor_sanitized"]) {
                    $unique_descriptors = true;
                }
            }
        }

        if ($unique_texts) {
            $use_descriptors = false;
        } else if ($unique_descriptors) {
            $use_descriptors = true;
        } else if (!$unique_descriptors && !$unique_texts) {
            $use_descriptors = false;
        }

        return $use_descriptors;
    }

    /**
     * Fetch completed assessments on a particular target id.
     *
     * @param $target_record_id
     * @param $course_id
     * @param $cperiod_id
     * @param $form_id
     * @param $distribution_id
     * @return array
     */
    private function fetchCompletedProgressData($target_record_id, $course_id, $cperiod_id, $form_id, $distribution_id = null, $type = null) {
        global $db;

        $AND_date_greater    = "";
        $AND_date_less       = "";

        if (!is_null($this->start_date)) {
            $AND_date_greater = " AND a.`delivery_date` >= ". $db->qstr($this->start_date);
        }

        if (!is_null($this->end_date)) {
            $AND_date_less = "    AND a.`delivery_date` <= ". $db->qstr($this->end_date);
        }

        if ($type == "event") {
            if (!is_null($this->adistribution_id)) {
                $AND_distribution_id = $this->createDistributionIDValueClause($this->adistribution_id);
            } else {
                $AND_distribution_id = "";
            }
        } else {
            if (!is_null($distribution_id)) {
                $AND_distribution_id = $this->createDistributionIDValueClause($distribution_id);
            } else if (!is_null($this->adistribution_id)) {
                $AND_distribution_id = $this->createDistributionIDValueClause($this->adistribution_id);
            } else {
                $AND_distribution_id = "";
            }
        }

        // Fetch all completed assessments (we're looking at the entire history of the form, even if elements have changed).
        // INNER JOIN for only those records that have assessment records.
        $query = "  SELECT  p.`target_record_id`, p.`aprogress_id`, p.`dassessment_id`, p.`adistribution_id`, p.`updated_date`
                    FROM    `cbl_assessment_progress`       AS p
                    JOIN    `cbl_assessment_distributions`  AS d ON p.`adistribution_id` = d.`adistribution_id`
                    JOIN    `cbl_distribution_assessments`  AS a ON p.`dassessment_id` = a.`dassessment_id`
                    WHERE   p.`progress_value` = 'complete'
                    AND     p.`target_record_id` = ?
                    AND     d.`course_id` = ?
                    AND     d.`cperiod_id` = ?
                    AND     d.`form_id` = ?
                    $AND_distribution_id
                    $AND_date_greater
                    $AND_date_less";

        $prepared = array($target_record_id, $course_id, $cperiod_id, $form_id);

        $completions = $db->GetAll($query, $prepared);
        if (!is_array($completions)) {
            return array();
        }
        return $completions;
    }

    /**
     * Fetch all responses for a given progress record.
     *
     * @param $aprogress_id
     * @return array
     */
    private function fetchAllAssessmentResponsesData($aprogress_id) {
        global $db;
        // Fetch all the responses for the completed assessments
        // LEFT JOIN to include rows that may or may not have response descriptors or item responses.
        $query = "SELECT    pr.`aprogress_id`, pr.`form_id`, pr.`adistribution_id`, pr.`assessor_type`,
                            pr.`assessor_value`, pr.`afelement_id`, pr.`iresponse_id`, pr.`comments`,
                            fe.`afelement_id`, fe.`element_id`, fe.`element_type`, fe.`element_text`, fe.`order` AS `form_element_order`, fe.`rubric_id`,
                            i.`item_id`, i.`itemtype_id`, i.`item_code`, i.`item_text`, i.`comment_type`,
                            it.`shortname` AS `itemtype_shortname`, it.`name` AS `itemtype_name`,
                            ir.`text` AS `item_response_text`,
                            rd.`ardescriptor_id`, rd.`descriptor` AS `response_descriptor`
                  FROM      `cbl_assessment_progress_responses`       AS pr
                  LEFT JOIN `cbl_assessment_form_elements`            AS fe  ON fe.`afelement_id`    = pr.`afelement_id`
                  LEFT JOIN `cbl_assessments_lu_items`                AS i   ON i.`item_id`          = fe.`element_id`
                  LEFT JOIN `cbl_assessments_lu_itemtypes`            AS it  ON it.`itemtype_id`     = i.`itemtype_id`
                  LEFT JOIN `cbl_assessments_lu_item_responses`       AS ir  ON ir.`iresponse_id`    = pr.`iresponse_id`
                  LEFT JOIN `cbl_assessments_lu_response_descriptors` AS rd  ON rd.`ardescriptor_id` = ir.`ardescriptor_id`
                  WHERE     pr.`aprogress_id` = ?
                  ORDER BY  fe.`order` ASC";
        $responses = $db->GetAll($query, array($aprogress_id));
        if (!is_array($responses)) {
            return array();
        }
        return $responses;
    }

    /**
     * Fetch all the responses by element ID (for items).
     * This function adds an additional array element containing the sanitized version of the text (no spaces, no tags, and lowercased).
     * Uses local strage (pseudo-caching) for the result set.
     *
     * @param $element_id
     * @return array
     */
    private function fetchItemResponsesDataByItemElement($element_id) {
        global $db;

        if ($this->isInStorage("responses_by_item_element", $element_id)) {
            return $this->fetchFromStorage("responses_by_item_element", $element_id);
        } else {
            $query = "SELECT    *
                      FROM      `cbl_assessments_lu_item_responses` AS ir
                      LEFT JOIN `cbl_assessments_lu_response_descriptors` AS rd  ON rd.`ardescriptor_id` = ir.`ardescriptor_id`
                      WHERE     ir.`item_id` = ?";

            $responses = $db->GetAll($query, array($element_id));
            if (!is_array($responses)) {
                $responses = array();
            }

            // Add sanitized versions of text
            foreach ($responses as $i => $response) {
                $responses[$i]["text_sanitized"] = $this->sanitizeText($response["text"]);
                $responses[$i]["descriptor_sanitized"] = $this->sanitizeText($response["descriptor"]);
            }

            // Add the comparison text, based on whether we are comparing on descriptors or text fields.
            // This is required due to the wonky data input of some forms.
            $compare_on_descriptor = $this->determineUseDescriptorComparison($responses);
            foreach ($responses as $i => $response) {
                $responses[$i]["compare_text"] = $compare_on_descriptor ? $responses[$i]["descriptor_sanitized"] : $responses[$i]["text_sanitized"];
                $responses[$i]["display_text"] = $compare_on_descriptor ? $responses[$i]["descriptor"] : $responses[$i]["text"];
            }

            // Add "group_key" to the array based on textual matches of the santized text
            foreach ($responses as $i => $response) {
                $this->addGroupKeyBySanitizedText($response["compare_text"], $responses, $i);
            }

            $this->addToStorage("responses_by_item_element", $responses, $element_id);
            return $responses;
        }
    }

    /**
     * Fetch the related data for a rubric, including response descriptors.
     * This method returns an array with the following structure:
     *
     * array(
     *    "rubric_title" => string,
     *    "rubric_comment_type" => string,
     *    "response_descriptors" => array,
     *    "rubric_items" => array
     * )
     *
     * @param $rubric_id
     * @return array
     */
    private function fetchRubricData($rubric_id) {
        global $db;
        if ($this->isInStorage("rubric_data", $rubric_id)) {
            return $this->fetchFromStorage("rubric_data", $rubric_id);
        } else {
            $sql_fetch_rubric = "SELECT     *
                                 FROM       `cbl_assessment_rubric_items` AS ri
                                 LEFT JOIN  `cbl_assessments_lu_rubrics`  AS r  ON r.`rubric_id` = ri.`rubric_id`
                                 LEFT JOIN  `cbl_assessments_lu_items`    AS i  ON i.`item_id` = ri.`item_id`
                                 WHERE      ri.`rubric_id` = ?
                                 ORDER BY   ri.`order` ASC";

            $sql_fetch_descriptors = "SELECT    ir.`iresponse_id`, ir.`item_id`, i.`itemtype_id`, ir.`order`, ir.`text`, id.`ardescriptor_id`, id.`descriptor`
                                      FROM      `cbl_assessments_lu_item_responses`       AS ir
                                      LEFT JOIN `cbl_assessments_lu_items`                AS i  ON i.`item_id` = ir.`item_id`
                                      LEFT JOIN `cbl_assessments_lu_response_descriptors` AS id ON id.`ardescriptor_id` = ir.`ardescriptor_id`
                                      WHERE     ir.`item_id` = ?
                                      ORDER BY  ir.`order` ASC";

            $rubric = array(
                "rubric_title" => "",
                "rubric_comment_type" => null,
                "rubric_items" => array(),
                "response_descriptors" => array()
            );
            if ($results = $db->GetAll($sql_fetch_rubric, array($rubric_id))) {
                if (is_array($results)) {
                    foreach ($results as $result) {
                        // Fetch the common data (they'll be constantly overwritten per iteration, but they're all the same, so we don't mind)
                        $rubric["rubric_title"] = $result["rubric_title"];
                        $rubric["rubric_comment_type"] = $result["comment_type"];

                        // Store the rubric item
                        $rubric["rubric_items"][$result["item_id"]] = array(
                            "rubric_id" => $result["rubric_id"],
                            "item_id" => $result["item_id"],
                            "item_text" => $result["item_text"],
                            "sanitized_item_text" => $this->sanitizeText($result["item_text"]),
                            "deleted_date" => $result["deleted_date"],
                            "order" => $result["order"]
                        );
                        // Get the response descriptors, if they haven't been fetched already
                        $descriptors = $db->GetAll($sql_fetch_descriptors, array($result["item_id"])); // fetch by this item; should be the same descriptors for the entire rubric
                        if (is_array($descriptors) && !empty($descriptors)) {
                            foreach ($descriptors as $descriptor) {
                                $rubric["response_descriptors"][$result["item_id"]][$descriptor["order"]] = array(
                                    "order" => $descriptor["order"],
                                    "ardescriptor_id" => $descriptor["ardescriptor_id"],
                                    "descriptor" => $descriptor["descriptor"],
                                    "text" => $descriptor["text"],
                                    "text_sanitized" => $this->sanitizeText($descriptor["text"])
                                );
                            }
                        }
                    }
                }
            }
            $this->addToStorage("rubric_data", $rubric, $rubric_id);
            return $rubric;
        }
    }

    /**
     * Fetch a form element and append the itemtype shortname to the resulting array.
     * Store in local storage pseudo-cache.
     *
     * @param $element_id
     * @return array
     */
    private function fetchItemDataByElementID($element_id) {
        $item_data = array();
        if ($item = $this->fetchStoredResultSet("item", $element_id, "Models_Assessments_Item", "fetchRowByIDIncludeDeleted")) {
            $item_data = $item->toArray();
            $itemtype = $this->fetchStoredResultSet("itemtype", $item_data["itemtype_id"], "Models_Assessments_Itemtype", "fetchRowByID");
            if ($itemtype && !empty($itemtype)) {
                $item_data["itemtype_shortname"] = $itemtype->getShortname();
            } else {
                $item_data["itemtype_shortname"] = "";
            }
        }
        return $item_data;
    }

    /**
     * Wrapper for local storage functionality to fetch and/or store a result set using a static method call all at once.
     *
     * @param $type
     * @param $index
     * @param $model_name
     * @param $static_method
     * @return bool|array
     */
    private function fetchStoredResultSet($type, $index, $model_name, $static_method) {
        if ($this->isInStorage($type, $index)) {
            return $this->fetchFromStorage($type, $index);
        } else {
            $result_set = $model_name::$static_method($index);
            $this->addToStorage($type, $result_set, $index);
            return $result_set;
        }
    }

    /**
     * Group the all_responses result set (obtained from fetchItemResponsesDataByItemElement) by group_key.
     * Format the return array for use with reportDataAddNode* methods.
     *
     * @param $all_responses
     * @return array
     */
    private function groupResponsesByGroupKey($all_responses) {
        $grouped = array();

        foreach ($all_responses as $response) {
            $group_key = $response["group_key"];
            if (!isset($grouped[$group_key])) {
                $grouped[$group_key] = array();
            }
            $grouped[$group_key]["compare_text"] = $response["compare_text"];
            $grouped[$group_key]["display_text"] = $response["display_text"];
            $grouped[$group_key]["text"] = $response["text"];
            $grouped[$group_key]["text_sanitized"] = $response["text_sanitized"];
            $grouped[$group_key]["group_key"] = $response["group_key"];
            $grouped[$group_key]["item_id"] = $response["item_id"];
            $grouped[$group_key]["order"] = $response["order"];
            $grouped[$group_key]["count"] = 0;
        }
        return $grouped;
    }

    /**
     * Standard method for sanitization of text.
     *
     * @param $text
     * @return string
     */
    private function sanitizeText($text) {
        return clean_input($text, array("striptags", "nows", "lowercase"));
    }

    /**
     * Based on the the comparison text, add a group key for each unique string.
     *
     * @param $needle
     * @param $haystack
     * @param $key
     */
    private function addGroupKeyBySanitizedText($needle, &$haystack, $key) {
        // For all instances of needle, add key value to the array for text matches
        foreach ($haystack as $i => $hay) {
            if ($needle == $hay["compare_text"]) {
                if (!isset($haystack[$i]["group_key"])) {
                    $haystack[$i]["group_key"] = $key;
                }
            }
        }
    }

    //--- Report data aggregation functions ---//

    /**
     * Configure the internal report data structure.
     *
     * @param $form_id
     */
    private function reportDataConfigure($form_id) {
        $formatted = array();
        if (empty($this->report_data)) {
            $elements = Models_Assessments_Form_Element::fetchAllByFormID($form_id); // fetch the current incarnation of the form
            if ($elements && !empty($elements)) {
                foreach ($elements as $element) {
                    switch ($element->getElementType()) {
                        case "text": // Free-text label
                            if ($element->getElementText()) {
                                // Only add an element for it if there's text to show.
                                // Default form behaviour ignores empty free text items.
                                $formatted["freetext-{$element->getOrder()}"] = $this->reportDataBuildNode($element->getElementType(), null, $element->getOrder(), $element->getElementText());
                            }
                            break;

                        case "data_source":
                            // Not supported
                            break;

                        case "objective":
                            $index = "objective-{$element->getElementID()}";
                            $formatted[$index] = $this->reportDataBuildNode($element->getElementType(), null, $element->getOrder(), $element->getElementText());
                            break;

                        default:
                        case "item":
                            // Otherwise, we're storing the basic structure. Fetch the element, and if possible, the itemtype_shortname
                            $item_data = $this->fetchItemDataByElementID($element->getElementID());
                            if ($element->getRubricID()) {
                                $index = "rubric-{$element->getRubricID()}";
                                $rubric = $this->fetchStoredResultSet("rubric", $element->getRubricID(), "Models_Assessments_Rubric", "fetchRowByIDIncludeDeleted");
                                if ($rubric) {
                                    $formatted[$index] = $this->reportDataBuildNode($element->getElementType(), null, null, $rubric->getRubricTitle());
                                }
                            } else {
                                $index = "element-{$element->getElementID()}";
                                $short_name = isset($item_data["itemtype_shortname"]) ? $item_data["itemtype_shortname"] : "";
                                $item_text = isset($item_data["item_text"]) ? $item_data["item_text"] : "";
                                $formatted[$index] = $this->reportDataBuildNode($element->getElementType(), $short_name, $element->getOrder(), $item_text);
                            }
                            break;
                    }
                }
            }
            $this->report_data = $formatted;
        }
    }

    /**
     * Build an empty data node to add to our report structure.
     *
     * @param string $element_type
     * @param string $item_type
     * @param int $order
     * @param string $element_text
     * @param array $responses
     * @param array $comments
     * @return array
     */
    private function reportDataBuildNode($element_type, $item_type, $order = null, $element_text = null, $responses = array(), $comments = array()) {
        return array(
            "element_type" => $element_type,                // form_element.element_type: "objective", "text" (free text label), "data_source", "item"
            "item_type" => $item_type,                      // itemtype.shortname: "free_text" (comment), "rubric_line", "horizontal_*", "vertical_*", "fieldnote", etc
            "form_order" => $order,                         // The order this item appears on the form
            "element_text" => $element_text,                // Text associated with the question, can be null
            "responses" => $responses,                      // The list of all possible responses and the count of how many of each were given for that element
            "comments" => $comments,                        // Additional comments
        );
    }

    /**
     * Add a free-text comment to the report data structure.
     *
     * @param int $element_id
     * @param int $order
     * @param string $comment
     */
    private function reportDataAddNodeFreeTextComment($element_id, $order, $comment) {
        $index = "element-$element_id";

        // Create the data node
        if (!isset($this->report_data[$index])) {
            $this->report_data[$index] = $this->reportDataBuildNode("item", "free_text", $order);
        }
        // Add this comment
        if (is_array($this->report_data[$index]["responses"])) {
            $this->report_data[$index]["responses"][] = $comment;
        }
    }

    /**
     * Add a grouped item (a rubric) to the report data structure.
     *
     * @param int $rubric_id
     * @param int $given_element_id
     * @param int $selected_response_descriptor_id
     * @param int $given_response_text
     * @param string $comments
     * @param array $rubric_data
     */
    private function reportDataAddNodeGroupedItem($rubric_id, $given_element_id, $selected_response_descriptor_id, $given_response_text, $comments, $rubric_data) {
        $index= "rubric-$rubric_id";

        // Create the data node if it's not already there
        if (!isset($this->report_data[$index])) {
            $this->report_data[$index] = $this->reportDataBuildNode("item", "rubric_line", null, $rubric_data["rubric_title"]);
        }

        // Add the possible responses data if they haven't been added to this node yet.
        if (empty($this->report_data[$index]["responses"])) {
            $ordinal = 0;
            foreach ($rubric_data["rubric_items"] as $element_id => $rubric_item) {
                $item_data = array();

                // Fill the structure with rubric question specific data
                $item_data["item_id"] = $element_id;
                $item_data["text"] = $rubric_item["item_text"];
                $item_data["text_sanitized"] = $this->sanitizeText($rubric_item["item_text"]);
                $item_data["group_key"] = $ordinal;
                $item_data["order"] = $rubric_item["order"];
                $item_data["rubric_response_detail"] = array();

                // Fill in the response descriptors for that rubric question
                foreach ($rubric_data["response_descriptors"] as $descriptor_element_id => $descriptor) {
                    if ($descriptor_element_id == $element_id) {
                        $item_data["rubric_response_detail"][$descriptor_element_id] = $descriptor;
                        foreach ($item_data["rubric_response_detail"][$descriptor_element_id] as $i => $data) {
                            // add comments and count for each possible response descriptor
                            $item_data["rubric_response_detail"][$descriptor_element_id][$i]["count"] = 0;
                            $item_data["rubric_response_detail"][$descriptor_element_id][$i]["comments"] = array();
                        }
                    }
                }
                $this->report_data[$index]["responses"][$ordinal++] = $item_data;
            }
        }

        // Add this response to the report node
        foreach ($this->report_data[$index]["responses"] as $i => $possible_response) {
            if ($possible_response["item_id"] == $given_element_id) {
                foreach ($possible_response["rubric_response_detail"] as $item_id => $descriptors) {
                    if ($item_id == $given_element_id) {
                        foreach ($descriptors as $item_order => $descriptor) {
                            if ($descriptor["ardescriptor_id"] && ($descriptor["ardescriptor_id"] == $selected_response_descriptor_id)) {
                                $this->report_data[$index]["responses"][$i]["rubric_response_detail"][$item_id][$item_order]["count"]++;
                                if ($comments) {
                                    $this->report_data[$index]["responses"][$i]["rubric_response_detail"][$item_id][$item_order]["comments"][] = $comments;
                                }
                            } else if ($descriptor["text_sanitized"] && ($descriptor["text_sanitized"] == $this->sanitizeText($given_response_text))) {
                                $this->report_data[$index]["responses"][$i]["rubric_response_detail"][$item_id][$item_order]["count"]++;
                                if ($comments) {
                                    $this->report_data[$index]["responses"][$i]["rubric_response_detail"][$item_id][$item_order]["comments"][] = $comments;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Add a single element to the report data structure.
     *
     * @param int $element_id
     * @param string $single_selected_response
     * @param string $comments
     * @param array $all_responses
     */
    private function reportDataAddNodeSingleElement($element_id, $single_selected_response, $comments, $all_responses) {
        $index = "element-$element_id";

        // If this item doesn't exist, add it
        if (!isset($this->report_data[$index])) {
            // Fetch the element
            $element = $this->fetchItemDataByElementID($element_id);
            $this->report_data[$index] = $this->reportDataBuildNode($element_id, $element["itemtype_shortname"], null, $element["item_text"]);
        }
        // Store the all_responses array (if it's not already stored)
        if (empty($this->report_data[$index]["responses"])) {
            $this->report_data[$index]["responses"] = $all_responses;
        }

        // Insert the selected response (and comment) into the responses array
        if (isset($this->report_data[$index])) {
            if (is_array($this->report_data[$index]["responses"])) {
                foreach ($this->report_data[$index]["responses"] as $i => $possible_response) {
                    if ($this->sanitizeText($possible_response["compare_text"]) == $this->sanitizeText($single_selected_response)) {
                        $this->report_data[$index]["responses"][$i]["count"]++;
                        if ($comments) {
                            if (!in_array($comments, $this->report_data[$index]["comments"])) {
                                $this->report_data[$index]["comments"][] = $comments;
                            }
                        }
                    }
                }
            }
        }
    }

    //--- Configure Zend_Cache in order to store this report in the filesystem ---//

    /**
     * Configure an instance of Zend_Cache to store cached reports for 24 hours.
     */
    private function setZendCache() {
        if (!$this->report_cache) {
            $this->report_cache = Zend_Cache::factory(
                "Core",
                "File",
                array(
                    "lifetime" => 86400 * 30, // 30 day cache
                    "automatic_serialization" => true
                ),
                array(
                    "cache_dir" => CACHE_DIRECTORY
                )
            );
        }
    }

    /**
     * Build a cache ID key for reading/writing to cache.
     *
     * @return string
     */
    private function buildReportCacheParams() {
        $completed_forms = array();
        $all_completed_forms_summary = $this->fetchCompletedAssessmentsMeta();
        foreach ($all_completed_forms_summary as $cperiod_id => $completed_summary) {
            $completed_forms[$cperiod_id] = 0;
            foreach ($completed_summary as $completions) {
                $completed_forms[$cperiod_id] += $completions["form_count"];
            }
        }

        $cache_key_array = array(
            "target_value" => is_array($this->target_value) ? implode(",", $this->target_value) : $this->target_value,
            "course_id" => is_array($this->course_id) ? implode(",", $this->course_id) : $this->course_id,
            "organisation_id" => $this->organisation_id,
            "adistribution_id" => is_array($this->adistribution_id) ? implode(",", $this->adistribution_id) : $this->adistribution_id,
            "form_id" => $this->form_id,
            "cperiod_id" => is_array($this->cperiod_id) ? implode(",", $this->cperiod_id) : $this->cperiod_id
        );

        $report_counts = array();
        foreach($completed_forms as $cperiod_id => $form_count) {
            $report_counts[] = $cperiod_id;
            $report_counts[] = $form_count;
        }
        $cache_key_raw = implode("-", $cache_key_array);
        $cache_key = "assessment_report_" . md5($cache_key_raw);

        return array(
            "report_key" => $cache_key,
            "report_key_raw" => $cache_key_raw,
            "report_params" => json_encode($cache_key_array),
            "report_param_hash" => md5(json_encode($cache_key_array)),
            "report_counts" => json_encode($report_counts),
            "report_count_hash" => md5(json_encode($report_counts))
        );
    }

    /**
     * Save the current report result set to cache.
     */
    private function cacheReport() {
        // We can only save a cached version of a report if a viewer proxy ID is set.
        $params = $this->buildReportCacheParams();
        $this->report_cache->save($this->report_data, $params["report_key"]);
        $model_report_cache = new Models_Assessments_Report_Caches(
            array(
                "report_key" => $params["report_key"],
                "report_param_hash" => $params["report_param_hash"],
                "report_meta_hash" => $params["report_count_hash"],
                "target_type" => "proxy_id", // only proxy supported for now.
                "target_value" => is_array($this->target_value) ? implode(",", $this->target_value) : $this->target_value,
                "created_date" => time(),
                "created_by" => $this->actor_proxy_id
            )
        );
        if (!$model_report_cache->insert()) {
            application_log("error", "Assessment Reports: cacheReport - Unable to insert cache record.");
        }
    }

    /**
     * Fetch the cached version of this report.
     *
     * @return bool|array
     */
    private function fetchCachedReport() {
        if ($this->disable_file_caching) {
            return false;
        }
        if (!$this->report_cache) {
            // File cache not configured, can't look it up even though the tracking record exists.
            application_log("error", "fetchCachedReport: Zend_Cache is not configured.");
            return false;
        }

        $params = $this->buildReportCacheParams();

        // Check for this cache file in the DB
        $model_report_cache = new Models_Assessments_Report_Caches();
        if ($cached = $model_report_cache->fetchRowByTargetTypeTargetValueReportMetaHash($this->target_type, is_array($this->target_value) ? implode(",", $this->target_value) : $this->target_value, $params["report_count_hash"])) {
            if ($params["report_count_hash"] == $cached->getReportMetaHash()) {
                // same, load the cached version
                if ($from_filesystem = $this->report_cache->load($params["report_key"])) {
                    return $from_filesystem; // Return the successfully loaded cache (or false if it wasn't found)
                }
            }
        }

        // At this point, either the cache is expired, the cache was removed, the report parameters have changed, or the report was never cached before.
        // Clear the cache file that may still exist, but is expired
        $this->report_cache->remove($params["report_key"]);

        // Clear all cache records in the DB for this viewer/target, since we found no cache for it (make sure we're not storing any extraneous records from manually deleted cache files)
        $model_report_cache->deleteAllByTargetTypeTargetValueReportParamHash($this->target_type, is_array($this->target_value) ? implode(",", $this->target_value) : $this->target_value, $params["report_param_hash"]);
        return false;
    }
}