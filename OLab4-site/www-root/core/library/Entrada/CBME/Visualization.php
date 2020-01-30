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
 * This is an abstraction layer for the visualization of CBME data.
 *
 * @author Organization: Queen's University
 * @author Unit: Health Sciences, Education Technology Unit
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Entrada_CBME_Visualization extends Entrada_CBME_Base {
    // Required
    protected $datasource_type = null;
    protected $datasource = null;
    protected $dataset = array();
    protected $limit_dataset = array();
    protected $filters = array();
    protected $actor_course_id;
    protected $actor_course_name;
    protected $actor_courses;
    protected $limit_to_current_cperiod = true;
    protected $cperiod_ids = array();
    protected $query_limit = 24;
    protected $scale_trends_assessments_limit = 35;
    protected $query_offset = 0;
    protected $advanced_search_epas = array();
    protected $advanced_search_milestones = array();
    protected $advanced_search_roles = array();
    protected $course_settings = array();
    protected $course_ccmembers = array();      // Competency Committee members
    protected $course_stages = array();
    protected $courses;
    protected $secondary_proxy_id = NULL;

    public function __construct($arr = array()) {
        parent::__construct($arr);

        /**
         * After default construction, instantiate the appropriate implementation
         */
        if ($this->datasource_type) {
            switch ($this->datasource_type) {
                case "progress" :
                    $this->datasource = new Entrada_CBME_Datasource_Progress($this->buildActorArray());
                    break;
                case "warehouse" :
                    $this->datasource = new Entrada_CBME_Datasource_Warehouse($this->buildActorArray());
                    break;
            }
        } else {
            $this->datasource = new Entrada_CBME_Datasource_Progress($this->buildActorArray());
        }

        if ($this->courses) {
            $this->actor_course_id = $this->courses["course_id"];
            $this->actor_course_name = $this->courses["course_name"];
            $this->actor_courses = $this->courses["courses"];
            $this->course_settings = $this->fetchCourseSettings();
            $this->course_ccmembers = $this->fetchCourseCompetencyCommitteeMembers();
        }

        if ($this->filters) {
            $this->filters = $this->sanitizeFilters($this->filters);
        }
    }

    /**
     * Fetch organisation specific standard stages
     *
     * @return array
     */
    public function fetchOrganisationStages() {
        return $this->datasource->fetchStages();
    }

    /*
     * This function returns a defined dataset
     *
     * @return array
     */
    public function fetchData() {
        if (empty($this->dataset)) {
            $this->buildDataset();
        }
        return $this->dataset;
    }

    /**
     * Assemble the related data for this visualization.
     *
     * @return bool
     */
    private function buildDataset() {
        $this->dataset = $this->buildDefaultDataset();

        /**
         * Populate the EPA assessment portion of the dataset
         */
        if (in_array("epa_assessments", $this->limit_dataset)) {
            $epa_objective_set = $this->fetchObjectiveSetByShortname("epa");
            if ($epa_objective_set) {
                $stages = $this->datasource->fetchStages();
                if ($stages) {
                    foreach ($stages as $stage) {
                        $this->dataset["stage_data"][$stage->getID()]["objective_name"] = $stage->getName();
                        $this->dataset["stage_data"][$stage->getID()]["objective_code"] = $stage->getCode();
                        $this->dataset["stage_data"][$stage->getID()]["completed"] = $stage->getCompletionStatus($this->actor_proxy_id, $this->actor_course_id);
                        $epa_progress = $this->datasource->fetchEPAProgress($this->actor_proxy_id, $this->actor_course_id, $epa_objective_set->getID(), $stage->getCode());
                        if ($epa_progress) {
                            $this->dataset["stage_data"][$stage->getID()]["progress"] = $epa_progress;
                        }
                    }
                }
            }
        }

        if (in_array("items", $this->limit_dataset)
            || in_array("course_assessment_tools_charts", $this->limit_dataset)
            || in_array("rating_scales_charts", $this->limit_dataset)
        ) {
            $this->dataset["items"] = $this->fetchItemData();
        }

        if (in_array("item_pins", $this->limit_dataset)) {
            $this->dataset["item_pins"] = $this->fetchItemData(true);
        }

        if (in_array("course_epas", $this->limit_dataset)) {
            $this->dataset["course_epas"] = $this->fetchCourseEPAs();
        }

        if (in_array("mapped_course_epas", $this->limit_dataset)) {
            $this->dataset["mapped_course_epas"] = $this->fetchMappedCourseEPAs();
        }

        if (in_array("course_milestones", $this->limit_dataset)) {
            $this->dataset["course_milestones"] = $this->fetchCourseMilestones();
        }

        if (in_array("course_key_competencies", $this->limit_dataset)) {
            $this->dataset["course_key_competencies"] = $this->fetchCourseKeyCompetencies();
        }

        if (in_array("unmapped_course_key_competencies", $this->limit_dataset)) {
            $this->dataset["unmapped_course_key_competencies"] = $this->fetchUnmappedCourseKeyCompetencies();
        }

        if (in_array("course_enabling_competencies", $this->limit_dataset)) {
            $this->dataset["course_enabling_competencies"] = $this->fetchCourseEnablingCompetencies();
        }

        if (in_array("unmapped_course_enabling_competencies", $this->limit_dataset)) {
            $this->dataset["unmapped_course_enabling_competencies"] = $this->fetchUnmappedCourseEnablingCompetencies();
        }

        /**
         * Populate the cbme assessment portion of the dataset
         */
        if (in_array("assessments", $this->limit_dataset)
            || in_array("course_assessment_tools_charts", $this->limit_dataset)
            || in_array("rating_scales_charts", $this->limit_dataset)
        ) {
            $this->dataset["assessments"] = $this->fetchAssessmentData();
        }

        if (in_array("unread_assessments", $this->limit_dataset)) {
            $this->dataset["assessments"] = $this->fetchAssessmentData(false, false, false, "complete", false, true, true);
        }

        if (in_array("all_unread_assessments", $this->limit_dataset)) {
            $this->dataset["assessments"] = $this->fetchAssessmentData(false, false, false, "complete", false, false, true);
        }

        /**
         * Fetch pinned assessments only
         */
        if (in_array("assessment_pins", $this->limit_dataset)) {
            $this->dataset["assessment_pins"] = $this->fetchAssessmentData(true);
        }

        if (in_array("roles", $this->limit_dataset)) {
            $objective = new Models_Objective();
            $roles = $objective->fetchChildrenByObjectiveSetShortname("role", $this->actor_organisation_id);
            if ($roles) {
                $this->dataset["roles"] = $roles;
            }
        }

        if (in_array("course_assessment_tools", $this->limit_dataset)
                || in_array("course_assessment_tools_charts", $this->limit_dataset)
                || in_array("rating_scales_charts", $this->limit_dataset)
        ) {
            $assessment_tools = $this->fetchCourseAssessmentTools();
            if ($assessment_tools) {
                $this->dataset["course_assessment_tools"] = $assessment_tools;
            }
        }

        if (in_array("rating_scales", $this->limit_dataset) || in_array("rating_scales_charts", $this->limit_dataset)) {
            $rating_scale_data = $this->fetchRatingScaleData($this->actor_organisation_id);
            if ($rating_scale_data) {
                $this->dataset["rating_scales"] = $rating_scale_data;
            }

            $rating_scale_descriptors = array();
            foreach ($rating_scale_data as $id => $rating_scale) {
                $response_descriptors = Models_Assessments_RatingScale_Response::fetchRowsByRatingScaleIDExcludingZeroWeight($id);
                foreach ($response_descriptors as $response_descriptor) {
                    $rating_scale_descriptors[$id][] = $response_descriptor->toArray();
                }
            }

            $this->dataset["rating_scale_response_descriptors"] = $rating_scale_descriptors;
        }

        if (in_array("course_assessment_tools_charts", $this->limit_dataset)) {
            $this->dataset["assessment_tools_charts"] = $this->fetchCourseAssessmentToolsChart();
        }

        if (in_array("rating_scales_charts", $this->limit_dataset)) {
            $this->dataset["rating_scales_charts"] = $this->fetchRatingScalesCharts();
        }

        /**
         * Fetch assessment comments
         */
        if (in_array("assessment_comments", $this->limit_dataset)) {
            /**
             * Fetch CBME assessments for the current user and then add all comments specific to the assessment
             */
            $this->dataset["assessment_comments"] = $this->fetchAssessmentData(false, false, true);
        }

        /**
         * Fetch assessment comments
         */
        if (in_array("assessment_comment_pins", $this->limit_dataset)) {
            /**
             * Fetch CBME assessments for the current user and then add all comments specific to the assessment
             */
            $this->dataset["assessment_comment_pins"] = $this->fetchAssessmentData(false, true, true);
        }

        /**
         * Fetch all selected filter data points to list
         */
        if (in_array("filter_list_data", $this->limit_dataset)) {
            $filter_data = $this->fetchFilterData($this->actor_course_id, $this->filters);
            $this->dataset["filter_list_data"] = $filter_data;
        }

        /**
         * Add the assessment count to the dataset
         */
        if (in_array("total_assessment_count", $this->limit_dataset)) {
            $this->dataset["total_assessment_count"] = $this->fetchAssessmentCount($this->actor_organisation_id, $this->actor_proxy_id, $this->actor_course_id, $this->filters, true, false, false, false, false, false, $this->secondary_proxy_id);
        }

        /**
         * Add the filtered assessment count (without limit and offset) to the dataset
         */
        if (in_array("filtered_assessment_count", $this->limit_dataset)) {
            $this->dataset["filtered_assessment_count"] = $this->fetchAssessmentCount($this->actor_organisation_id, $this->actor_proxy_id, $this->actor_course_id, $this->filters, true, true, false, false, false, false, $this->secondary_proxy_id);
        }

        /**
         * Add the pinned assessment count to the dataset
         */
        if (in_array("total_pinned_assessment_count", $this->limit_dataset)) {
            $this->dataset["total_pinned_assessment_count"] = $this->fetchAssessmentCount($this->actor_organisation_id, $this->actor_proxy_id, $this->actor_course_id, $this->filters, true, false, false, false, false, true, $this->secondary_proxy_id);
        }

        /**
         * Add the pinned filtered assessment count (without limit and offset) to the dataset
         */
        if (in_array("filtered_pinned_assessment_count", $this->limit_dataset)) {
            $this->dataset["filtered_pinned_assessment_count"] = $this->fetchAssessmentCount($this->actor_organisation_id, $this->actor_proxy_id, $this->actor_course_id, $this->filters, true, true, false, false, false, true, $this->secondary_proxy_id);
        }

        /**
         * Add the assessment count to the dataset
         */
        if (in_array("total_assessment_comment_count", $this->limit_dataset)) {
            $this->dataset["total_assessment_comment_count"] = $this->fetchAssessmentCount($this->actor_organisation_id, $this->actor_proxy_id, $this->actor_course_id, $this->filters, true, false, false, false, true, true, $this->secondary_proxy_id);
        }

        /**
         * Add the filtered assessment comment count (without limit and offset) to the dataset
         */
        if (in_array("filtered_assessment_comment_count", $this->limit_dataset)) {
            $this->dataset["filtered_assessment_comment_count"] = $this->fetchAssessmentCount($this->actor_organisation_id, $this->actor_proxy_id, $this->actor_course_id, $this->filters, true, true, false, false, true, false, $this->secondary_proxy_id);
        }

        /**
         * Add the pinned assessment comment count to the dataset
         */
        if (in_array("total_pinned_assessment_comment_count", $this->limit_dataset)) {
            $this->dataset["total_pinned_assessment_comment_count"] = $this->fetchAssessmentCount($this->actor_organisation_id, $this->actor_proxy_id, $this->actor_course_id, $this->filters, true, false, false, true, true, false, $this->secondary_proxy_id);
        }

        /**
         * Add the filtered pinned assessment comment count (without limit and offset) to the dataset
         */
        if (in_array("filtered_pinned_assessment_comment_count", $this->limit_dataset)) {
            $this->dataset["filtered_pinned_assessment_comment_count"] = $this->fetchAssessmentCount($this->actor_organisation_id, $this->actor_proxy_id, $this->actor_course_id, $this->filters, true, true, false, true, true, false, $this->secondary_proxy_id);
        }

        /**
         * Add the item count to the dataset
         */
        if (in_array("item_count", $this->limit_dataset)) {
            $this->dataset["item_count"] = $this->fetchItemCount($this->actor_organisation_id, $this->actor_proxy_id, $this->actor_course_id, $this->filters, true, false, false, false);
        }

        /**
         * Add the filtered assessment count (without limit and offset) to the dataset
         */
        if (in_array("filtered_item_count", $this->limit_dataset)) {
            $this->dataset["filtered_item_count"] = $this->fetchFilteredItemCount($this->actor_organisation_id, $this->actor_proxy_id, $this->actor_course_id, $this->filters, false, true, false, false);
        }

        /**
         * Add the pinned item count (without limit and offset) to the dataset
         */
        if (in_array("pinned_item_count", $this->limit_dataset)) {
            $this->dataset["pinned_item_count"] = $this->fetchPinnedItemCount($this->actor_proxy_id);
        }

        /**
         * Add the filtered pinned assessment count (without limit and offset) to the dataset
         */
        if (in_array("filtered_pinned_item_count", $this->limit_dataset)) {
            $this->dataset["filtered_pinned_item_count"] = $this->fetchFilteredItemCount($this->actor_organisation_id, $this->actor_proxy_id, $this->actor_course_id, $this->filters, false, true, false, true);
        }

        /**
         * Add the pinned assessment count (without limit and offset) to the dataset
         */
        if (in_array("pinned_assessment_count", $this->limit_dataset)) {
            $this->dataset["pinned_assessment_count"] = $this->fetchPinnedCount("assessment", $this->actor_proxy_id);
        }

        /**
         * Add the unread assessment count to the dataset
         */
        if (in_array("unread_assessment_count", $this->limit_dataset)) {
            $filter_array = array("other" => "unread");
            $cbme_assessments = $this->datasource->fetchCBMEAssessments($this->actor_organisation_id, $this->actor_proxy_id, $this->actor_course_id, $filter_array, true, true, $this->query_limit, $this->query_limit, $this->query_offset, false, $this->secondary_proxy_id);
            $this->dataset["unread_assessment_count"] = $cbme_assessments[0]["assessment_count"];
        }

        /**
         * Add the pinned assessment count (without limit and offset) to the dataset
         */
        if (in_array("pending_assessments", $this->limit_dataset)) {
            $this->dataset["assessments"] = $this->fetchAssessmentData(false, false, false, "pending");
        }

        /**
         * Add the pending assessment count (without limit and offset) to the dataset
         */
        if (in_array("pending_assessments_count", $this->limit_dataset)) {
            $this->dataset["pending_assessments_count"] = $this->fetchAssessmentCountByType($this->actor_organisation_id, $this->actor_proxy_id, $this->actor_course_id, $this->filters, true, true, false, false, "pending");
        }

        /**
         * Add the inprogress assessments (without limit and offset) to the dataset
         */
        if (in_array("inprogress_assessments", $this->limit_dataset)) {
            $this->dataset["assessments"] = $this->fetchAssessmentData(false, false, false, "inprogress");
        }

        /**
         * Add the inprogress assessment count (without limit and offset) to the dataset
         */
        if (in_array("inprogress_assessments_count", $this->limit_dataset)) {
            $this->dataset["inprogress_assessments_count"] = $this->fetchAssessmentCountByType($this->actor_organisation_id, $this->actor_proxy_id, $this->actor_course_id, $this->filters, true, true, false, false, "inprogress");
        }

        /**
         * Add the deleted assessments (without limit and offset) to the dataset
         */
        if (in_array("deleted_assessments", $this->limit_dataset)) {
            $this->dataset["assessments"] = $this->fetchAssessmentData(false, false, false, "deleted");
        }

        /**
         * Add the deleted assessment count (without limit and offset) to the dataset
         */
        if (in_array("deleted_assessments_count", $this->limit_dataset)) {
            $this->dataset["deleted_assessments_count"] = $this->fetchAssessmentCountByType($this->actor_organisation_id, $this->actor_proxy_id, $this->actor_course_id, $this->filters, true, true, false, false, "deleted");
        }

        /**
         * Add stages from the course tree to the dataset
         */
        if (in_array("course_stages", $this->limit_dataset)) {
            $this->dataset["course_stages"] = $this->fetchCourseStages($this->actor_course_id);
        }

        if (in_array("query_limit", $this->limit_dataset)) {
            $this->dataset["query_limit"] = $this->query_limit;
        }

        /**
         * Iterate the course EPAs and store them in a format that the advancedSearch widget can consume
         */
        if ($this->dataset["course_epas"]) {
            foreach ($this->dataset["course_epas"] as $epa) {
                array_push($this->advanced_search_epas, array("target_id" => $epa["objective_id"], "target_label" => html_encode($epa["objective_code"] . ": " . substr($epa["objective_name"], 0, 32) . "..."), "target_title" => html_encode($epa["objective_code"] . " " . $epa["objective_name"])));
            }
        }

        /**
         * Iterate the roles and store them in a format that the advancedSearch widget can consume
         */
        if ($this->dataset["roles"]) {
            foreach ($this->dataset["roles"] as $role) {
                array_push($this->advanced_search_roles, array("target_id" => $role->getID(), "target_label" => $role->getCode() . ": " . $role->getName(), "target_title" => $role->getCode() . " " . $role->getName()));
            }
        }

        /**
         * Iterate the course EPAs and store them in a format that the advancedSearch widget can consume
         */
        if ($this->dataset["course_milestones"]) {
            foreach ($this->dataset["course_milestones"] as $milestone) {
                array_push($this->advanced_search_milestones, array("target_id" => $milestone["objective_id"], "target_label" => html_encode($milestone["objective_code"] . ": " . substr($milestone["objective_name"], 0, 25) . "..."), "target_title" => html_encode($milestone["objective_code"] . " " . $milestone["objective_name"])));
            }
        }

        /**
         * Fetch course EPAs that are not in the course objective tree
         */
        if (in_array("unmapped_course_epas", $this->limit_dataset)) {
            $this->dataset["unmapped_course_epas"] = $this->fetchUnmappedCourseEPAs();
        }
    }

    /*
     * This function returns a default dataset
     *
     * @return array
     */
    private function buildDefaultDataset() {
        $default_dataset = array(
            "stage_data"        => array(),
            "assessments"   => array(),
            "items" => array(),
            "assessment_comments" => array(),
            "course_epas"       => array(),
            "mapped_course_epas"       => array(),
            "course_milestones" => array(),
            "course_key_competencies" => array(),
            "course_enabling_competencies" => array(),
            "roles"             => array(),
            "filters"           => array(),
            "course_assessment_tools" => array(),
            "rating_scales" => array(),
            "rating_scale_response_descriptors" => array(),
            "filtered_assessment_count" => 0,
            "total_assessment_count" => 0,
            "assessment_count" => 0,
            "total_assessment_comment_count" => 0,
            "filtered_assessment_comment_count" => 0,
            "filtered_item_count" => 0,
            "item_count" => 0,
            "assessment_pins" => array(),
            "total_pinned_assessment_count" => 0,
            "filtered_pinned_assessment_count" => 0,
            "assessment_comment_pins" => array(),
            "total_pinned_assessment_comment_count" => 0,
            "filtered_pinned_assessment_comment_count" => 0,
            "inprogress_assessments_count" => 0,
            "pending_assessments_count" => 0,
            "deleted_assessments_count" => 0,
            "item_pins" => array(),
            "pinned_item_count" => 0,
            "filtered_pinned_item_count" => 0,
            "selected_course_preference" => array(),
            "course_stages" => array(),
            "query_limit" => 24,
            "unmapped_course_epas" => array(),
            "unmapped_course_key_competencies" => array(),
            "unmapped_course_enabling_competencies" => array(),
        );

        return $default_dataset;
    }

    /**
     * This function fetches a distinct list of form types that the course has created.
     *
     * @return array|boolean
     */
    private function fetchCourseAssessmentTools() {
        global $db;

        $query = "  SELECT DISTINCT a.`form_type_id`, a.`shortname`, a.`title`
                    FROM `cbl_assessments_lu_form_types` AS a
                    JOIN `cbl_assessments_lu_forms` AS b ON a.`form_type_id` = b.`form_type_id`
                    JOIN `cbl_distribution_assessments` AS c ON b.`form_id` = c.`form_id`
                    WHERE a.`deleted_date` IS NULL
                    AND a.`cbme` = 1
                    AND c.`course_id` = ?";

        return $db->GetAll($query, array($this->actor_course_id));
    }

    /**
     * Fetches rating scales and their responses by the provided shortname and organisation
     *
     * @param int $organisation_id the current user's organisation ID
     * @return array
     */
    private function fetchRatingScaleData($organisation_id = 0) {
        $rating_scale_data = array();
        $rating_scales = $this->fetchRatingScales($organisation_id);
        if ($rating_scales) {
            foreach ($rating_scales as $rating_scale) {
                $rating_scale_data[$rating_scale["rating_scale_id"]] = $rating_scale;
                $rating_scale_responses = $this->fetchRatingScaleResponsesByID($rating_scale["rating_scale_id"]);
                if ($rating_scale_responses) {
                    $rating_scale_data[$rating_scale["rating_scale_id"]]["responses"] = $rating_scale_responses;
                }
            }
        }

        return $rating_scale_data;
    }

    /**
     * Fetch Rating Scales by shortname and organisation
     *
     * @param int $organisation_id the current user's organisation_id
     * @return array|boolean
     */
    private function fetchRatingScales($organisation_id) {
        $rating_scale_type_model = new Models_Assessments_RatingScale_Type();
        return $rating_scale_type_model->fetchDashboardVisibleRatingScale($organisation_id);
    }

    /**
     * Fetch Rating Scale responses
     * @param int $rating_scale_id // the rating scale identifier to fetch responses by
     * @return array|boolean
     */
    private function fetchRatingScaleResponsesByID($rating_scale_id = 0) {
        $rating_scale_response_model = new Models_Assessments_RatingScale_Response();
        return $rating_scale_response_model->fetchAllByRatingScaleIDIncludeDescriptor($rating_scale_id);
    }

    /**
     * Sanitize the user provided filter options
     * @param array $filters // the user selected filters
     * @return array
     */
    public function sanitizeFilters($filters = array()) {
        global $translate;

        $PROCESSED["filters"] = array();
        if (isset($filters["search_term"]) && $tmp_input = clean_input($filters["search_term"], array("trim", "striptags"))) {
            $PROCESSED["filters"]["search_term"] = $tmp_input;
            $PROCESSED["filters"]["completed_by"] = $tmp_input;
        }

        if (isset($filters["selected_users"]) && is_array($filters["selected_users"])) {
            foreach ($filters["selected_users"] as $proxy_id) {
                if ($tmp_input = clean_input($proxy_id, array("trim", "int"))) {
                    $PROCESSED["filters"]["selected_users"][] = $tmp_input;
                }
            }
        }

        if (isset($filters["epas"]) && is_array($filters["epas"])) {
            foreach ($filters["epas"] as $epa) {
                if ($tmp_input = clean_input($epa, array("trim", "int"))) {
                    $PROCESSED["filters"]["epas"][] = $tmp_input;
                }
            }
        }

        if (isset($filters["roles"]) && is_array($filters["roles"])) {
            foreach ($filters["roles"] as $role) {
                if ($tmp_input = clean_input($role, array("trim", "int"))) {
                    $PROCESSED["filters"]["roles"][] = $tmp_input;
                }
            }
        }

        $course_stages = $this->fetchCourseStages($this->actor_course_id);
        if ($course_stages) {
            foreach ($course_stages as $stage) {
                if (isset($filters["objective_" . $stage["objective_id"]]) && is_array($filters["objective_" . $stage["objective_id"]])) {
                    foreach ($filters["objective_" . $stage["objective_id"]] as $milestone) {
                        if ($tmp_input = clean_input($milestone, array("trim", "int"))) {
                            $PROCESSED["filters"]["milestones"][$stage["objective_id"]][] = $tmp_input;
                            $PROCESSED["filters"]["selected_milestones"][] = $tmp_input;
                        }
                    }
                }
            }
        }

        if (isset($filters["milestone"])) {
            if ($tmp_input = clean_input($filters["milestone"], array("trim", "int"))) {
                $PROCESSED["filters"]["selected_milestones"][] = $tmp_input;
            }
        }

        if (isset($filters["contextual_variables"]) && is_array($filters["contextual_variables"])) {
            foreach ($filters["contextual_variables"] as $contextual_variable) {
                if ($tmp_input = clean_input($contextual_variable, array("trim", "int"))) {
                    $PROCESSED["filters"]["contextual_variables"][] = $tmp_input;

                    if (isset($filters["objective_". $tmp_input]) && is_array($filters["objective_". $tmp_input])) {
                        foreach ($filters["objective_". $tmp_input] as $contextual_variable_response) {
                            if ($tmp_input = clean_input($contextual_variable_response, array("trim", "int"))) {
                                $PROCESSED["filters"]["contextual_variable_responses"][] = $tmp_input;
                            }
                        }
                    }
                }
            }
        }

        if (isset($PROCESSED["filters"]["roles"]) && isset($PROCESSED["filters"]["roles"])) {
            $PROCESSED["filters"]["role_epas"] = $this->fetchEPAsMappedToRoles(2, $PROCESSED["filters"]["roles"]);
        }

        if (isset($filters["start_date"]) && $filters["start_date"] && $tmp_input = clean_input($filters["start_date"] . " 00:00:00", array("strtotime"))) {
            $PROCESSED["filters"]["start_date"] = $tmp_input;
        }

        if (isset($filters["finish_date"]) && $filters["finish_date"] && $tmp_input = clean_input($filters["finish_date"] . " 23:59:59", array("strtotime"))) {
            $PROCESSED["filters"]["finish_date"] = $tmp_input;
        }

        if (isset($PROCESSED["filters"]["start_date"]) && isset($PROCESSED["filters"]["finish_date"])) {
            if ($PROCESSED["filters"]["finish_date"] < $PROCESSED["filters"]["start_date"]) {
                add_error($translate->_("The selected <strong>finish date</strong> must come after the selected <strong>start date</strong>."));
            }
        }

        if (isset($filters["form_types"]) && is_array($filters["form_types"])) {
            foreach ($filters["form_types"] as $form_type_id) {
                if ($tmp_input = clean_input($form_type_id, array("trim", "int"))) {
                    $PROCESSED["filters"]["form_types"][] = $tmp_input;
                }
            }
        }

        if (isset($filters["rating_scale_id"])) {
            $PROCESSED["filters"]["rating_scale_id"] = array();
            if (is_array($filters["rating_scale_id"])) {
                foreach ($filters["rating_scale_id"] as $rating_scale_id) {
                    if ($tmp_input = clean_input($rating_scale_id, array("trim", "int"))) {
                        $PROCESSED["filters"]["rating_scale_id"][] = $tmp_input;
                    }
                }
            } else {
                if (isset($filters["rating_scale_id"]) && $tmp_input = clean_input($filters["rating_scale_id"], array("trim", "int"))) {
                    $PROCESSED["filters"]["rating_scale_id"][] = $tmp_input;
                }
            }
        }

        if (isset($filters["aprogress_ids"]) && is_array($filters["aprogress_ids"])) {
            $PROCESSED["filters"]["aprogress_ids"] = array();
            foreach ($filters["aprogress_ids"] as $aprogress_id) {
                if ($tmp_input = clean_input($aprogress_id, array("trim", "int"))) {
                    if (!in_array($tmp_input, $PROCESSED["filters"]["aprogress_ids"])) {
                        $PROCESSED["filters"]["aprogress_ids"][] = $tmp_input;
                    }
                }
            }
        }

        if (isset($filters["iresponse_ids"]) && is_array($filters["iresponse_ids"])) {
            $PROCESSED["filters"]["iresponse_ids"] = array();
            foreach ($filters["iresponse_ids"] as $iresponse_id) {
                if ($tmp_input = clean_input($iresponse_id, array("trim", "int"))) {
                    if (!in_array($tmp_input, $PROCESSED["filters"]["iresponse_ids"])) {
                        $PROCESSED["filters"]["iresponse_ids"][] = $tmp_input;
                    }
                }
            }
        }

        if (isset($filters["descriptors"]) && is_array($filters["descriptors"])) {
            $PROCESSED["filters"]["descriptors"] = array();
            foreach ($filters["descriptors"] as $ardescriptor_id) {
                if ($tmp_input = clean_input($ardescriptor_id, array("trim", "int"))) {
                    if (!in_array($tmp_input, $PROCESSED["filters"]["descriptors"])) {
                        $PROCESSED["filters"]["descriptors"][] = $tmp_input;
                    }
                }
            }
        }

        if (isset($filters["sort"]) && $tmp_input = clean_input($filters["sort"], array("trim", "striptags"))) {
            $PROCESSED["filters"]["sort"] = $tmp_input;
        }

        if (isset($filters["triggered_by"]) && $tmp_input = clean_input($filters["triggered_by"], array("trim", "striptags"))) {
            $PROCESSED["filters"]["triggered_by"] = $tmp_input;
        }

        if (isset($filters["other"]) && $tmp_input = clean_input($filters["other"], array("trim", "striptags"))) {
            $PROCESSED["filters"]["other"] = $tmp_input;
        }

        if (isset($filters["limit"]) && $tmp_input = clean_input($filters["limit"], array("trim", "int"))) {
            $PROCESSED["filters"]["limit"] = $tmp_input;
        } else {
            $PROCESSED["filters"]["limit"] = 6;
        }

        if (isset($filters["offset"]) && $tmp_input = clean_input($filters["offset"], array("trim", "int"))) {
            $PROCESSED["filters"]["offset"] = $tmp_input;
        } else {
            $PROCESSED["filters"]["offset"] = 0;
        }

        if (isset($filters["schedule_id"]) && is_array($filters["schedule_id"])) {
            foreach ($filters["schedule_id"] as $experience) {
                intval($experience);
                if ($tmp_input = clean_input($experience, array("trim", "int"))) {
                    $schedule_id = $tmp_input;
                    $schedule = Models_Schedule::fetchRowByID($schedule_id);
                    if ($schedule) {
                        $PROCESSED["filters"]["experience"][] = array("schedule_id" => $schedule->getID(), "start_date" => $schedule->getStartDate(), "end_date" => $schedule->getEndDate());
                    }
                }
            }
        }

        return $PROCESSED["filters"];
    }

    /**
     * Get the current filters
     * @return array
     */
    public function getFilters() {
        return $this->filters;
    }

    /**
     * Get the selected course ID
     * @return int
     */
    public function getCourseID() {
        return $this->actor_course_id;
    }

    /**
     * Get the selected course name
     * @return string
     */
    public function getCourseName() {
        return $this->actor_course_name;
    }

    /**
     * Get the user's course list
     * @return array
     */
    public function getCourses() {
        return $this->actor_courses;
    }

    /**
     * Get the query limit
     * @return int
     */
    public function getQueryLimit() {
        return $this->query_limit;
    }

    /**
     * Get the EPAs formatted for the Advanced Search
     * @return array
     */
    public function getAdvancedSearchEPAs() {
        return $this->advanced_search_epas;
    }

    /**
     * Get the Milestones formatted for the Advanced Search
     * @return array
     */
    public function getAdvancedSearchMilestones() {
        return $this->advanced_search_milestones;
    }

    /**
     * Get the Roles formatted for the Advanced Search
     * @return array
     */
    public function getAdvancedSearchRoles() {
        return $this->advanced_search_roles;
    }

    /**
     * Get course specific settings
     * @return array
     **/
    public function getCourseSettings() {
        return $this->course_settings;
    }

    /**
     * Get course competencies committee members
     * @return array
     */
    public function getCourseCCMembers() {
        return $this->course_ccmembers;
    }

    /**
     * Get the trends scale query limit
     * @return int
     */
    public function getScaleTrendsAssessmentsLimit() {
        return $this->scale_trends_assessments_limit;
    }

    /**
     * Get course stages
     * @return array
     */
    public function getCourseStages() {
        return $this->course_stages;
    }

    /**
     * Use the objective tree for this course to fetch the EPAs that are mapped to the supplied roles
     * @param int $depth // the bottom level depth, defaults to 2 for Roles
     * @param $roles     // the provided role objectives
     * @return array     // an array of mapped EPAs
     */
    private function fetchEPAsMappedToRoles($depth = 2, $roles) {
        // Initialize an objective tree object
        $tree_object = new Entrada_CBME_ObjectiveTree(array(
            "actor_proxy_id" => $this->actor_proxy_id,
            "actor_organisation_id" => $this->actor_organisation_id,
            "course_id" => $this->actor_course_id
        ));

        $epas = array();
        $mapped_epas = $tree_object->fetchTreeNodesAtDepth($depth, null, false, $roles);
        if ($mapped_epas) {
            foreach ($mapped_epas as $epa) {
                $epas[] = clean_input($epa["objective_id"], array("trim", "int"));
            }
        }

        return $epas;
    }

    /**
     * @param int $organisation_id
     * @param int $proxy_id
     * @param int $course_id
     * @param array $filters
     * @param bool $count_flag
     * @param bool $apply_filter_flag
     * @param bool $apply_limit_flag // toggles selected filters
     * @param bool $assessments_with_pinned_comments
     * @param bool $include_comments
     * @param bool $pinned_only
     * @return int
     */
    private function fetchAssessmentCount($organisation_id = 0, $proxy_id = 0, $course_id = 0, $filters = array(), $count_flag = true, $apply_filter_flag = true, $apply_limit_flag = true, $assessments_with_pinned_comments = false, $include_comments = false, $pinned_only = false, $secondary_proxy_id = 0) {
        $count = 0;
        $assessment_count = $this->datasource->fetchCBMEAssessments($organisation_id, $proxy_id, $course_id, $filters, true, $apply_filter_flag, $apply_limit_flag, 24, 0, $pinned_only, $secondary_proxy_id, $assessments_with_pinned_comments, $include_comments);
        if ($assessment_count) {
            $assessment_count = reset($assessment_count);
            $count = $assessment_count["assessment_count"];
        }
        return $count;
    }

    /**
     * Fetch a count of all filtered items
     * @param int $organisation_id
     * @param int $proxy_id
     * @param int $course_id
     * @param array $filters
     * @param bool $count_flag
     * @param bool $apply_filter_flag // toggles selected filters
     * @param bool $apply_limit_flag
     * @param bool $pinned_only
     * @return int
     */
    private function fetchItemCount($organisation_id = 0, $proxy_id = 0, $course_id = 0, $filters = array(), $count_flag = true, $apply_filter_flag = true, $apply_limit_flag = true, $pinned_only = false) {
        $count = 0;
        $item_count = $this->datasource->fetchCBMEAssessmentItemsByOrganisationIDProxyID($organisation_id, $proxy_id, $course_id, $filters, true, $apply_filter_flag, $apply_limit_flag, $this->query_limit, $this->query_offset, $pinned_only);
        if ($item_count) {
            $item_count = reset($item_count);
            $count = $item_count["item_count"];
        }
        return $count;
    }

    /**
     * Fetch all filtered items and count them using PHP
     * @param int $organisation_id
     * @param int $proxy_id
     * @param int $course_id
     * @param array $filters
     * @param bool $count_flag
     * @param bool $apply_filter_flag // toggles selected filters
     * @param bool $apply_limit_flag
     * @param bool $pinned_only
     * @return int
     */
    private function fetchFilteredItemCount($organisation_id = 0, $proxy_id = 0, $course_id = 0, $filters = array(), $count_flag = false, $apply_filter_flag = true, $apply_limit_flag = true, $pinned_only = false) {
        $count = 0;
        $items = $this->datasource->fetchCBMEAssessmentItemsByOrganisationIDProxyID($organisation_id, $proxy_id, $course_id, $filters, $count_flag, $apply_filter_flag, $apply_limit_flag, $this->query_limit, $this->query_offset, $pinned_only);
        if ($items) {
            $count = count($items);
        }
        return $count;
    }

    /**
     * Fetch all pinned items and count them using PHP
     * @param int $proxy_id
     * @return int
     */
    private function fetchPinnedItemCount($proxy_id = 0) {
        $count = 0;
        $items = Models_Assessments_Pins::fetchAllByProxyID($proxy_id);
        if ($items) {
            $count = count($items);
        }
        return $count;
    }

    /**
     * Fetch all user pins by the provided pin_type and return a total count
     * @param string $pin_type
     * @param int $proxy_id
     * @return int
     */
    private function fetchPinnedCount($pin_type = "assessment", $proxy_id = 0) {
        $count = 0;
        $pins = Models_Assessments_Pins::fetchAllByPinTypeProxyID($pin_type, $proxy_id);
        if ($pins) {
            $count = count($pins);
        }
        return $count;
    }

    /**
     * Fetch Course EPAs
     * @return array
     */
    private function fetchCourseEPAs() {
        // Initialize an objective tree object
        $tree_object = new Entrada_CBME_ObjectiveTree(array(
            "actor_proxy_id" => $this->actor_proxy_id,
            "actor_organisation_id" => $this->actor_organisation_id,
            "course_id" => $this->actor_course_id
        ));

        return $tree_object->fetchTreeNodesAtDepth(2, "o.`objective_code`", true);
    }

    /**
     * Fetch unmapped course EPAs from cbme_course_objectives
     * @return array
     */
    private function fetchUnmappedCourseEPAs() {
        $objective_model = new Models_Objective();
        $course_epas = $objective_model->fetchCbmeCourseObjectivesByCode("epa", $this->actor_course_id, "");
        return $course_epas;
    }

    /**
     * Fetch unmapped course Key Competencies from cbme_course_objectives
     * @return array
     */
    private function fetchUnmappedCourseKeyCompetencies() {
        $objective_model = new Models_Objective();
        $course_epas = $objective_model->fetchCbmeCourseObjectivesByCode("kc", $this->actor_course_id, "");
        return $course_epas;
    }

    /**
     * Fetch unmapped course Enabling Competencies from cbme_course_objectives
     * @return array
     */
    private function fetchUnmappedCourseEnablingCompetencies() {
        $objective_model = new Models_Objective();
        $course_epas = $objective_model->fetchCbmeCourseObjectivesByCode("ec", $this->actor_course_id, "");
        return $course_epas;
    }

    /**
     * Fetch Course EPAs
     * @return array
     */
    private function fetchMappedCourseEPAs() {
        $forms_api = new Entrada_Assessments_Forms(array(
            "actor_proxy_id" => $this->actor_proxy_id,
            "actor_organisation_id" => $this->actor_organisation_id
        ));

        return $forms_api->fetchEPANodesTaggedToForms($this->actor_course_id);
    }

    /**
     * Fetch course Milestones
     * @return bool|mixed
     */
    private function fetchCourseMilestones() {
        // Initialize an objective tree object
        $tree_object = new Entrada_CBME_ObjectiveTree(array(
            "actor_proxy_id" => $this->actor_proxy_id,
            "actor_organisation_id" => $this->actor_organisation_id,
            "course_id" => $this->actor_course_id
        ));

        return $tree_object->fetchTreeNodesAtDepth(6, "o.`objective_code`", true);
    }

    /**
     * Fetch rating scale responses for a specific item's rating_scale_id, inlcudes item responses
     * @param int $item_id
     * @return array
     */
    public function fetchRatingScaleResponses($item_id = 0) {
        $rating_scale_response_model = new Models_Assessments_RatingScale_Response();
        $responses = $rating_scale_response_model->fetchAllByItemRatingScaleIDIncludeItemResponses($item_id);
        return $responses;
    }

    /**
     * Fetch assessment tools charts data
     *
     * @return array
     */
    private function fetchCourseAssessmentToolsChart() {
        global $translate;
        $tools_charts = array();

        if (isset($this->dataset["items"]) && is_array($this->dataset["items"])) {
            // Get the assessment tools charts to render
            if (is_array($this->dataset["course_assessment_tools"]) && count($this->dataset["course_assessment_tools"])) {
                foreach ($this->dataset["course_assessment_tools"] as $assessment_tool) {
                    $chart_data = $chart_labels = $chart_data_ids = $scale_response_count = array();
                    foreach ($this->dataset["items"] as $assessment_item) {
                        if ($assessment_item["item_rating_scale_id"]
                            && isset($this->dataset["rating_scale_response_descriptors"][$assessment_item["item_rating_scale_id"]])
                            && count($this->dataset["rating_scale_response_descriptors"][$assessment_item["item_rating_scale_id"]])
                            && isset($this->dataset["assessments"][$assessment_item["dassessment_id"]])
                            && $this->dataset["assessments"][$assessment_item["dassessment_id"]]["form_type"] == $assessment_tool["title"]
                        ) {
                            $scale_responses = $this->dataset["rating_scale_response_descriptors"][$assessment_item["item_rating_scale_id"]];
                            $chart_scale[$assessment_item["item_rating_scale_id"]] = array();
                            foreach ($scale_responses as $scale_response) {
                                $chart_scale[$assessment_item["item_rating_scale_id"]][] = $scale_response["text"];
                                if (!isset($scale_response_count[$assessment_item["item_rating_scale_id"]][$scale_response["text"]])) {
                                    $scale_response_count[$assessment_item["item_rating_scale_id"]][$scale_response["text"]] = 0;
                                }
                            }

                            $chart_data[$assessment_item["item_rating_scale_id"]][] = $assessment_item["response_descriptor"];
                            $chart_data_ids[$assessment_item["item_rating_scale_id"]][] = $assessment_item["dassessment_id"];
                            $scale_response_count[$assessment_item["item_rating_scale_id"]][$assessment_item["response_descriptor"]]++;
                            $chart_labels[$assessment_item["item_rating_scale_id"]][] = $this->dataset["assessments"][$assessment_item["dassessment_id"]]["form_type"];
                        }
                    }

                    if (count($chart_data)) {
                        $chart_no = 0;
                        foreach ($chart_data as $scale_id => $tool_chart_data) {
                            $chart_title = $chart_no++ ? "" : $assessment_tool["title"];
                            $tools_charts[] = array(
                                "chart_id" => "assessment_tool_chart_" . $assessment_tool["form_type_id"] . "_" . $scale_id,
                                "chart_title" => $chart_title,
                                "card_title" => $this->dataset["rating_scales"][$scale_id]["rating_scale_title"],
                                "card_label" => count($tool_chart_data) . " " . $translate->_("Assessments"),
                                "scale" => array_reverse($chart_scale[$scale_id]),
                                "scale_reponse_count" => $scale_response_count[$scale_id],
                                "data" => $tool_chart_data,
                                "data_ids" => $chart_data_ids[$scale_id],
                                "labels" => $chart_labels[$scale_id]
                            );
                        }
                    }
                }
            }
        }
        return $tools_charts;
    }

    /**
     * Fetch the rating scales charts data
     *
     * @return array
     */
    private function fetchRatingScalesCharts() {
        global $translate;
        $scales_charts = array();
        $filters = $this->filters;
        $epas = isset($filters["epas"]) ? $filters["epas"] : array();
        $rating_scale_ids = array();
        if (isset($filters["rating_scale_id"])) {
            if (is_array($filters["rating_scale_id"])) {
                $rating_scale_ids = array_map("intval", $filters["rating_scale_id"]);
            } else {
                $rating_scale_ids[] = intval($filters["rating_scale_id"]);
            }
        }

        if (is_array($this->dataset["rating_scales"]) && count($this->dataset["rating_scales"])) {
            foreach ($this->dataset["rating_scales"] as $rating_scale) {
                if (!empty($rating_scale_ids) && (!in_array( $rating_scale["rating_scale_id"], $rating_scale_ids))) {
                    continue;
                }

                $offset = intval($this->query_offset);
                $filters["rating_scale_id"] = array($rating_scale["rating_scale_id"]);
                $filters["trends"] = true;
                $filters["sort"] = true;
                $chart_data = $chart_labels = $chart_data_ids = $chart_dates = array();
                $chart_scale = $scale_response_count = array();
                if ($scale_responses = Models_Assessments_RatingScale_Response::fetchRowsByRatingScaleIDExcludingZeroWeight($rating_scale["rating_scale_id"])) {
                    foreach ($scale_responses as $scale_response) {
                        $chart_scale[] = $scale_response->getText();
                        $scale_response_count[$scale_response->getText()] = 0;
                    }
                }

                while ($items = $this->datasource->fetchCBMEAssessmentItemsByOrganisationIDProxyID($this->actor_organisation_id, $this->actor_proxy_id, $this->actor_course_id, $filters, false, true, true, $this->query_limit, $offset, false, true)) {
                    $offset += $this->query_limit;
                    foreach ($items as $assessment_item) {
                        if (!in_array($assessment_item["response_descriptor"], $chart_scale)) {
                            continue;
                        }
                        if (count($chart_data) < $this->scale_trends_assessments_limit) {
                            $chart_data[] = $assessment_item["response_descriptor"];
                            $chart_data_ids[] = $assessment_item["dassessment_id"];
                            $chart_labels[] = Models_Assessments_Form_Type::fetchFormTypeByDAssessmentID($assessment_item["dassessment_id"]);
                            if ($assessment_item["encounter_date"] == NULL) {
                                $chart_dates[] = date("M j, Y", $assessment_item["created_date"]);
                            } else {
                                $chart_dates[] = date("M j, Y", $assessment_item["encounter_date"]);
                            }
                        }
                        $scale_response_count[$assessment_item["response_descriptor"]]++;
                    }
                }
                if (count($chart_data)) {
                    $chart_data = array_reverse($chart_data);
                    $chart_data_ids = array_reverse($chart_data_ids);
                    $chart_labels = array_reverse($chart_labels);
                    $chart_dates = array_reverse($chart_dates);

                    $assessments_num = 0;
                    $assessments_num += array_sum(array_map(function($count) {return intval($count);}, $scale_response_count));

                    if (!isset($scales_charts[$rating_scale["rating_scale_shortname"]])) {
                        $scales_charts[$rating_scale["rating_scale_shortname"]] = array(
                            "title" => ($rating_scale["rating_scale_type_description"]) ? $rating_scale["rating_scale_type_description"] : $rating_scale["rating_scale_type_name"],
                            "rating_scale_id" => $rating_scale["rating_scale_id"],
                            "charts" => array()
                        );
                    }
                    $scales_charts[$rating_scale["rating_scale_shortname"]]["charts"][] = array(
                        "rating_scale_id" => $rating_scale["rating_scale_id"],
                        "query_limit" => $this->scale_trends_assessments_limit,
                        "chart_id" => "rating_scale_chart_" . $rating_scale["rating_scale_id"],
                        "chart_title" => $rating_scale["rating_scale_title"],
                        "card_title" => $translate->_("Overall"),
                        "card_label" => $assessments_num . " " . $translate->_("Assessments"),
                        "assessments_count" => $assessments_num,
                        "scale" => array_reverse($chart_scale),
                        "scale_reponse_count" => $scale_response_count,
                        "data" => $chart_data,
                        "data_ids" => $chart_data_ids,
                        "labels" => $chart_labels,
                        "epa" => null,
                        "chart_dates" => $chart_dates
                    );
                }

                /**
                 * Fetch data per EPAs if more than one EPAs are selected.
                 */
                if (count($epas) > 1) {
                    foreach ($epas as $epa) {
                        $filters["epas"] = array($epa);
                        $chart_data = $chart_labels = $chart_data_ids = array();
                        $offset = intval($this->query_offset);
                        if (!($objective = Models_Objective::fetchRow($epa))) {
                            continue;
                        }

                        if ($scale_responses) {
                            $chart_scale = $scale_response_count = array();
                            foreach ($scale_responses as $scale_response) {
                                $chart_scale[] = $scale_response->getText();
                                $scale_response_count[$scale_response->getText()] = 0;
                            }
                        }

                        while($items = $this->datasource->fetchCBMEAssessmentItemsByOrganisationIDProxyID($this->actor_organisation_id, $this->actor_proxy_id, $this->actor_course_id, $filters, false, true, $this->query_limit, $this->query_limit, $offset, false, true)) {
                            $offset += $this->query_limit;
                            foreach ($items as $assessment_item) {
                                if (!in_array($assessment_item["response_descriptor"], $chart_scale)) {
                                    continue;
                                }
                                if (count($chart_data) < $this->scale_trends_assessments_limit) {
                                    $chart_data[] = $assessment_item["response_descriptor"];
                                    $chart_data_ids[] = $assessment_item["dassessment_id"];
                                    $chart_labels[] = Models_Assessments_Form_Type::fetchFormTypeByDAssessmentID($assessment_item["dassessment_id"]);
                                    if ($assessment_item["encounter_date"] == NULL) {
                                        $chart_dates[] = date("M j, Y", $assessment_item["created_date"]);
                                    } else {
                                        $chart_dates[] = date("M j, Y", $assessment_item["encounter_date"]);
                                    }
                                }

                                $scale_response_count[$assessment_item["response_descriptor"]]++;
                            }
                        }

                        if (count($chart_data)) {
                            $chart_data = array_reverse($chart_data);
                            $chart_data_ids = array_reverse($chart_data_ids);
                            $chart_labels = array_reverse($chart_labels);
                            $chart_dates = array_reverse($chart_dates);

                            $assessments_num = 0;
                            $assessments_num += array_sum(array_map(function($count) {return intval($count);}, $scale_response_count));

                            if (!isset($scales_charts[$rating_scale["rating_scale_shortname"]])) {
                                $scales_charts[$rating_scale["rating_scale_shortname"]] = array(
                                    "title" => ($rating_scale["rating_scale_type_description"]) ? $rating_scale["rating_scale_type_description"] : $rating_scale["rating_scale_type_name"],
                                    "charts" => array()
                                );
                            }
                            $scales_charts[$rating_scale["rating_scale_shortname"]]["charts"][] = array(
                                "rating_scale_id" => $rating_scale["rating_scale_id"],
                                "query_limit" => $this->scale_trends_assessments_limit,
                                "chart_id" => "rating_scale_chart_" . $rating_scale["rating_scale_id"] . "_epa_" . $epa,
                                "chart_title" => "",
                                "card_title" => $objective->getCode() . ": " .$objective->getName(),
                                "card_label" => $assessments_num . " " . $translate->_("Assessments"),
                                "assessments_count" => $assessments_num,
                                "scale" => array_reverse($chart_scale),
                                "scale_reponse_count" => $scale_response_count,
                                "data" => $chart_data,
                                "data_ids" => $chart_data_ids,
                                "labels" => $chart_labels,
                                "epa" => $epa,
                                "chart_dates" => $chart_dates
                            );
                        }
                    }

                    $filters["epas"] = $epas;
                }

                $filters["rating_scale_id"] = $rating_scale_ids;
            }
        }

        return $scales_charts;
    }

    /**
     * Fetch stages from a course tree
     * @param int $course_id
     * @return bool|mixed
     */
    private function fetchCourseStages($course_id = 0) {
        // Initialize an objective tree object
        $tree_object = new Entrada_CBME_ObjectiveTree(array(
            "actor_proxy_id" => $this->actor_proxy_id,
            "actor_organisation_id" => $this->actor_organisation_id,
            "course_id" => $course_id
        ));

        return $tree_object->fetchTreeNodesAtDepth(1, "o.`objective_order`", true);
    }

    /**
     * Sort milestones
     * @param $sort_key
     * @return Closure
     */
    public function sortMilestones($sort_key) {
        return function ($a, $b) use ($sort_key) {
            return strnatcmp($a[$sort_key], $b[$sort_key]);
        };
    }

    /**
     * Fetches course settings for a specific course
     * @return array
     **/
    private function fetchCourseSettings() {
        $course_setting_model = new Models_Course_Setting();
        $course_settings_array = array();
        $course_settings = $course_setting_model->fetchAllByCourseID($this->actor_course_id);
        if ($course_settings) {
            foreach ($course_settings as $setting) {
                $course_settings_array[] = $setting->toArray();
            }
        }
        return $course_settings_array;
    }

    private function fetchCourseCompetencyCommitteeMembers() {
        if ($course_contacts = Models_Course_Contact::fetchAllByCourseIDContactType($this->courses["course_id"], "ccmember")) {
            $course_contacts = array_map(function($contact) {
                return $contact->getProxyID();
            }, $course_contacts);

            return $course_contacts;
        }

        return array();
    }

    /**
     * Fetches course settings for a specific course and shortname
     * @param $course_id // the identifier for the course
     * @param $shortname // the course setting shortname
     * @return array
     */
    public function fetchCourseSettingsByShortname($course_id = 0, $shortname = "") {
        $course_setting_model = new Models_Course_Setting();
        $course_setting = $course_setting_model->fetchRowByCourseIDShortname($course_id, $shortname);
        if ($course_setting) {
            $course_setting = $course_setting->toArray();
        }
        return $course_setting;
    }

    /**
     * Determines if a course requires EPAs when triggering assessments
     * @param int $course_id
     * @return bool
     */
    public function courseRequiresEPAs($course_id = 0) {
        $epas_required = true;
        $trigger_assessment_settings = $this->fetchCourseSettingsByShortname($course_id, "trigger_assessment");
        if ($trigger_assessment_settings) {
            $settings = @json_decode($trigger_assessment_settings["value"], true);
            if ($settings) {
                $epas_required = $settings["epas_required"];
            }
        }
        return $epas_required;
    }

    /**
     * Determines if a course has assessment tools when the course has no EPAs
     * @param int $course_id
     * @return bool
     */
    public function courseHasAssessmentToolObjectives($course_id = 0) {
        $has_tool_objectives = false;
        $tool_objective_settings = $this->fetchCourseSettingsByShortname($course_id, "assessment_tools");
        if ($tool_objective_settings) {
            $settings = @json_decode($tool_objective_settings["value"], true);
            if ($settings) {
                $has_tool_objectives = true;
            }
        }
        return $has_tool_objectives;
    }

    /**
     * Fetch an array of assessment methods for a course
     * @param int $course_id
     * @param null $group
     * @return array
     */
    public function fetchCourseAssessmentMethods($course_id = 0, $group = null) {
        $assessment_method_array = array();
        $assessment_method_model = new Models_Assessments_Method();
        $assessment_methods = $assessment_method_model->fetchAllByGroupOrganisationID($group, $this->actor_organisation_id);
        if ($assessment_methods) {
            foreach ($assessment_methods as $assessment_method) {
                $assessment_method["display"] = true;
                $assessment_method_array[$assessment_method["shortname"]] = $assessment_method;
            }
            $assessment_method_settings = $this->fetchCourseSettingsByShortname($course_id, "assessment_methods");
            if ($assessment_method_settings && array_key_exists("value", $assessment_method_settings)) {
                $course_assessment_method_settings = @json_decode($assessment_method_settings["value"]);
                foreach ($assessment_methods as $assessment_method) {
                    if ($course_assessment_method_settings && is_array($course_assessment_method_settings)) {
                        if (in_array($assessment_method["assessment_method_id"], $course_assessment_method_settings)) {
                            $assessment_method_array[$assessment_method["shortname"]]["display"] = true;
                        } else {
                            $assessment_method_array[$assessment_method["shortname"]]["display"] = false;
                        }
                    }
                }
            }
        }
        return $assessment_method_array;
    }

    /**
     * Fetch all EPAs that are mapped to the provided form identifier
     * @param int $organisation_id
     * @param int $form_id
     * @param int $course_id
     * @param int $active
     * @return array
     */
    private function fetchEPAsMappedToForms($organisation_id = 0, $form_id = 0, $course_id = 0, $active = 1) {
        global $db;
        $epas = array();
        $query = "  SELECT b.`objective_code` FROM `global_lu_objective_sets` AS a
                    JOIN `global_lu_objectives` AS b
                    ON a.`objective_set_id` = b.`objective_set_id`
                    JOIN `objective_organisation` AS c
                    ON b.`objective_id` = c.`objective_id`
                    JOIN `cbl_assessment_form_objectives` as d
                    ON c.`objective_id` = d.`objective_id`
                    WHERE a.`shortname` = 'epa'
                    AND a.`deleted_date` IS NULL
                    AND c.`organisation_id` = ?
                    AND d.`form_id` = ?
                    AND d.`course_id` = ?
                    AND b.`objective_active` = ?";
        $results = $db->GetAll($query, array($organisation_id, $form_id, $course_id, $active));
        if ($results) {
            $epas = $results;
        }
        return $epas;
    }

    /**
     * Fetch all EPAs that are mapped to the provided item identifier
     * @param int $organisation_id
     * @param int $item_id
     * @param int $active
     * @return array
     */
    private function fetchEPAsMappedToItems($organisation_id = 0, $item_id = 0, $active = 1) {
        global $db;
        $epas = array();
        $query = "  SELECT b.`objective_code` FROM `global_lu_objective_sets` AS a
                    JOIN `global_lu_objectives` AS b
                    ON a.`objective_set_id` = b.`objective_set_id`
                    JOIN `objective_organisation` AS c
                    ON b.`objective_id` = c.`objective_id`
                    JOIN `cbl_assessment_item_objectives` as d
                    ON c.`objective_id` = d.`objective_id`
                    WHERE a.`shortname` = 'epa'
                    AND a.`deleted_date` IS NULL
                    AND c.`organisation_id` = ?
                    AND d.`item_id` = ?
                    AND b.`objective_active` = ?
                    AND d.`deleted_date` IS NULL";
        $results = $db->GetAll($query, array($organisation_id, $item_id, $active));
        if ($results) {
            $epas = $results;
        }
        return $epas;
    }

    /**
     * Determines if a course can request preceptor access via course settings
     * @param int $course_id
     * @return bool
     */
    public function courseCanRequestPreceptorAccess($course_id = 0) {
        $can_request_access = false;
        $preceptor_request_settings = $this->fetchCourseSettingsByShortname($course_id, "cbme_request_preceptor_access");
        if ($preceptor_request_settings) {
            $settings = @json_decode($preceptor_request_settings["value"], true);
            if ($settings && array_key_exists("enabled", $settings)) {
                $can_request_access = $settings["enabled"];
            }
        }
        return $can_request_access;
    }

    /**
     * This function fetches a learner's stage
     * @param int $proxy_id
     * @param int $course_id
     * @return array
     */
    public function getLearnerStage($proxy_id = 0, $course_id = 0) {
        $learner_level_api = new Entrada_CBME_LearnerLevel(array(
            "actor_organisation_id" => $this->actor_organisation_id,
        ));
        return $learner_level_api->determineLearnerStage($proxy_id, $course_id);
    }

    /**
     * This function fetches a learner's level data
     * @return array
     */
    public function getLearnerLevel() {
        $learner_level_api = new Entrada_CBME_LearnerLevel(array(
            "actor_proxy_id" => $this->actor_proxy_id,
            "actor_organisation_id" => $this->actor_organisation_id,
            "course_id" => $this->actor_course_id
        ));

        return $learner_level_api->fetchLearnerLevel();
    }

    /**
     * Fetch all assessment data
     * @param bool $pinned_only
     * @param bool $assessments_with_pinned_comments
     * @param bool $include_comments
     * @return array
     */
    private function fetchAssessmentData($pinned_only = false, $assessments_with_pinned_comments = false, $include_comments = false, $progress_value = "complete", $count_flag = false, $apply_limit_flag = true, $unread_only = false) {
        $assessment_data = array();
        /**
         * Fetch CBME assessments for the current user
         */
        if ($unread_only) {
            $this->filters["other"] = "unread";
        }
        $cbme_assessments = $this->datasource->fetchCBMEAssessments($this->actor_organisation_id, $this->actor_proxy_id, $this->actor_course_id, $this->filters, $count_flag, true,  $apply_limit_flag, $this->query_limit, $this->query_offset, $pinned_only, $this->secondary_proxy_id, $assessments_with_pinned_comments, $include_comments, $progress_value);
        if ($cbme_assessments) {
            foreach ($cbme_assessments as $assessment) {
                /**
                 * Fetch the assessor for this assessment
                 */
                $assessor = $this->fetchAssessmentAssessor($assessment["dassessment_id"]);

                switch ($assessment["shortname"]) {
                    case "cbme_supervisor" :
                        $comment_shortname = "cbme_supervisor_next_steps";
                        $supervision_level = "CBME-supervisor-entrustment_scale";
                        break;
                    case "cbme_fieldnote" :
                        $comment_shortname = "cbme_fieldnote_next_steps";
                        $supervision_level = "CBME-fieldnote-entrustment_scale";
                        break;
                    case "cbme_procedure" :
                        $comment_shortname = "cbme_procedure_next_steps";
                        $supervision_level = "CBME-procedure-entrustment_scale";
                        break;
                    default:
                        $comment_shortname = "";
                        $supervision_level = "";
                        break;
                }
                /**
                 * Fetch the comment response for this assessment
                 */
                $comment_response = $this->fetchCBMEAssessmentResponse($assessment["form_id"], $comment_shortname, $assessment["aprogress_id"], $assessment["shortname"]);

                /**
                 * Fetch the entrustment response for this assessment
                 */
                $rating_scale_responses = false;
                $selected_iresponse_order = 0;
                $rating_scale_response = null;

                $rating_scale_response = $this->fetchCBMEAssessmentEntrustment($assessment["form_id"], $supervision_level, $assessment["aprogress_id"], $assessment["shortname"]);
                if ($rating_scale_response) {
                    $selected_iresponse_order = $rating_scale_response["order"];
                    /**
                     * Fetch a count of rating scale responses
                     */
                    $rating_scale_responses = $this->fetchRatingScaleResponses($rating_scale_response["item_id"]);
                }

                $mapped_epas = $this->fetchEPAsMappedToForms($this->actor_organisation_id, $assessment["form_id"], $this->actor_course_id);
                if ($mapped_epas) {
                    foreach ($mapped_epas as &$mapped_epa) {
                        $mapped_epa["stage_code"] = strtolower(substr($mapped_epa["objective_code"], 0, 1));
                    }
                }

                if ($assessment["pin_id"] && $assessment["deleted_date"] == NULL) {
                    $is_pinned = true;
                } else {
                    $is_pinned = false;
                }

                $comments = array();
                if ($include_comments) {
                    $comments = $this->datasource->fetchAssessmentComments($assessment["dassessment_id"], $assessment["aprogress_id"], $assessments_with_pinned_comments);
                }

                if ($assessment["deleted_by"]) {
                    $user_model = Models_User::fetchRowByID($assessment["deleted_by"]);
                    $deleted_by = $user_model->getFirstname() . " " . $user_model->getLastName();
                }

                switch($progress_value) {
                    case "complete" :
                        $progress_display_value = "Complete";
                    break;
                    case "inprogress" :
                        $progress_display_value = "In Progress";
                    break;
                    case "pending" :
                        $progress_display_value = "Pending";
                    break;
                    case "deleted" :
                        $progress_display_value = "Deleted";
                    break;
                }

                /**
                 * Build the assessment_data portion of the dataset
                 */
                $assessment_data[] = array(
                    "dassessment_id"                    => $assessment["dassessment_id"],
                    "atarget_id"                        => $assessment["atarget_id"],
                    "aprogress_id"                      => $assessment["aprogress_id"],
                    "form_id"                           => $assessment["form_id"],
                    "form_type"                         => $assessment["form_type"],
                    "form_shortname"                    => $assessment["shortname"],
                    "title"                             => $assessment["title"],
                    "created_date"                      => date("M j, Y", $assessment["created_date"]),
                    "updated_date"                      => ($assessment["updated_date"] ? ($assessment["created_date"] > $assessment["updated_date"] ? date("M j, Y", $assessment["created_date"]) : date("M j, Y", $assessment["updated_date"])) : NULL),
                    "assessor"                          => ($assessor ? $assessor["full_name"] : false),
                    "comment_response"                  => ($comment_response && $comment_response["comments"] ? $comment_response["comments"] : false),
                    "entrustment_response_descriptor"   => ($rating_scale_response && $rating_scale_response["descriptor"] ? $rating_scale_response["descriptor"] : false),
                    "selected_iresponse_order"          => $selected_iresponse_order,
                    "rating_scale_responses"            => $rating_scale_responses,
                    "mapped_epas"                       => $mapped_epas,
                    "is_pinned"                         => $is_pinned,
                    "pin_id"                            => $assessment["pin_id"],
                    "comments"                          => $comments,
                    "read_id"                           => $assessment["read_id"],
                    "progress_value"                    => $progress_display_value,
                    "assessment_method"                 => $assessment["assessment_method"],
                    "assessment_created_date"           => ($assessment["assessment_created_date"] ? date("M j, Y", $assessment["assessment_created_date"]): null),
                    "deleted_reason_notes"              => $assessment["deleted_reason_notes"] == NULL ? $assessment["reason_details"] : $assessment["deleted_reason_notes"],
                    "deleted_date"                      => ($assessment["target_deleted_date"] ? date("M j, Y", $assessment["target_deleted_date"]) : NULL),
                    "deleted_by"                        => isset($deleted_by) ? $deleted_by : NULL,
                    "encounter_date"                    => ($assessment["encounter_date"] ? date("M j, Y", $assessment["encounter_date"]) : NULL),
                    "like_id"                           => $assessment["like_id"],
                    "comment"                           => $assessment["comment"]
                );
            }
        }

        return $assessment_data;
    }

    private function fetchItemData($pinned_only = false) {
        $this->filters["trends"] = false;
        $cbme_assessment_items = $this->datasource->fetchCBMEAssessmentItemsByOrganisationIDProxyID($this->actor_organisation_id, $this->actor_proxy_id, $this->actor_course_id, $this->filters, false, true, true, $this->query_limit, $this->query_offset, $pinned_only, $this->secondary_proxy_id);
        if ($cbme_assessment_items) {
            $assessments_base = new Entrada_Assessments_Base();
            foreach ($cbme_assessment_items as $key => $item) {
                $cbme_assessment_items[$key]["rating_scale_responses"] = array();
                $user = $assessments_base->getUserByType($item["assessor_value"], $item["assessor_type"]);
                if ($user) {
                    $cbme_assessment_items[$key]["assessor"] = $user->getFirstname() . " " . $user->getLastname();
                }

                if (!is_null($item["item_rating_scale_id"])) {
                    $rating_scale_responses = $this->fetchRatingScaleResponses($item["item_id"]);
                    if ($rating_scale_responses) {
                        $cbme_assessment_items[$key]["rating_scale_responses"] = $rating_scale_responses;
                    }
                }

                $mapped_epas = $this->fetchEPAsMappedToItems($this->actor_organisation_id, $item["item_id"]);
                foreach ($mapped_epas as &$mapped_epa) {
                    $mapped_epa["stage_code"] = strtolower(substr($mapped_epa["objective_code"], 0, 1));
                }

                $cbme_assessment_items[$key]["created_date"] = date("M j, Y", $cbme_assessment_items[$key]["created_date"]);
                $cbme_assessment_items[$key]["updated_date"] = ($cbme_assessment_items[$key]["updated_date"] ? date("M j, Y", $cbme_assessment_items[$key]["updated_date"]) : NULL);
                $cbme_assessment_items[$key]["encounter_date"] = ($cbme_assessment_items[$key]["encounter_date"] ? date("M j, Y", $cbme_assessment_items[$key]["encounter_date"]) : NULL);

                $read_model = new Models_Assessments_Read();
                if ($this->secondary_proxy_id != NULL ) {
                    $read_record = $read_model->fetchRowByTypeAndIDAndAProgressID($item["item_id"], "item", $item["aprogress_id"], $this->secondary_proxy_id, true);
                    if (!$read_record) {
                        $cbme_assessment_items[$key]["read_id"] = NULL;
                    }
                } else {
                    $read_record = $read_model->fetchRowByTypeAndIDAndAProgressID($item["item_id"], "item", $item["aprogress_id"], $this->actor_proxy_id, true);
                    if (!$read_record) {
                        $cbme_assessment_items[$key]["read_id"] = NULL;
                    }
                }

                if ($cbme_assessment_items[$key]["pin_id"] && $cbme_assessment_items[$key]["deleted_date"] == NULL) {
                    $is_pinned = true;
                } else {
                    $is_pinned = false;
                }

                $cbme_assessment_items[$key]["mapped_epas"] = $mapped_epas;
                $cbme_assessment_items[$key]["is_pinned"] = $is_pinned;
            }

            return $cbme_assessment_items;
        }
    }

    public function getRotationScheduleAdvancedSearch() {
        /**
         * Fetch the rotation schedules for filtering
         */
        $schedule_model = new Entrada_CBME_RotationSchedule();
        $schedule_filters = array();
        $rotation_schedule_audience_membership = Models_Schedule_Audience::fetchAllByProxyID($this->actor_proxy_id, true);
        if ($rotation_schedule_audience_membership) {
            foreach ($rotation_schedule_audience_membership as $audience) {
                $schedules = $schedule_model->fetchRotations($audience["schedule_parent_id"], null, $this->actor_proxy_id);
                $parent_schedule = Models_Schedule::fetchRowByID($schedules[0]["schedule_parent_id"]);
                if ($schedules) {
                    if (count($schedules) > 1) {
                        foreach ($schedules as $key => $schedule) {
                            if ($schedules[0]["schedule_slot_id"] == $schedules[1]["schedule_slot_id"] - 1) {
                                //Consecutive
                                $start_date = $schedules[0]["start_date"];
                                $end_date = $schedules[1]["end_date"];
                                array_push($schedule_filters, array("schedule" => $schedules[0], "start_date" => $start_date, "end_date" => $end_date, "schedule_title" => $parent_schedule->getTitle()));
                                break;
                            } else {
                                $start_date = $schedule["start_date"];
                                $end_date = $schedule["end_date"];
                                array_push($schedule_filters, array("schedule" => $schedules[0], "start_date" => $start_date, "end_date" => $end_date, "schedule_title" => $parent_schedule->getTitle()));
                            }
                        }
                    } else {
                        array_push($schedule_filters, array("schedule" => $schedules[0], "start_date" => $schedules[0]["start_date"], "end_date" => $schedules[0]["end_date"], "schedule_title" => $parent_schedule->getTitle()));
                    }
                }
            }
        }

        usort($schedule_filters, function ($a, $b) {
            $a = $a['start_date'];
            $b = $b['start_date'];
            if ($a == $b)  {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        });
    }

    /**
     * Fetch the total number of assessments based on the type (pending, inprogress or deleted)
     * @param int $organisation_id
     * @param int $proxy_id
     * @param int $course_id
     * @param array $filters
     * @param bool $count_flag
     * @param bool $apply_filter_flag
     * @param bool $apply_limit_flag
     * @param bool $pinned_only
     * @return mixed
     */
    public function fetchAssessmentCountByType($organisation_id = 0, $proxy_id = 0, $course_id = 0, $filters = array(), $count_flag = true, $apply_filter_flag = true, $apply_limit_flag = true, $pinned_only = false, $progress_type = "", $secondary_proxy_id = 0) {
        $pending_count = $this->datasource->fetchCBMEAssessments($organisation_id, $proxy_id, $course_id, $filters, $count_flag, $apply_filter_flag, $apply_limit_flag, $this->query_limit, $this->query_offset, $pinned_only, $secondary_proxy_id, false, false, $progress_type);
        return $pending_count[0]["assessment_count"];
    }

    /**
     * Determines if a course requires a date of encounter when triggering assessments
     * @param int $course_id
     * @return bool
     */
    public function courseRequiresDateOfEncounter($course_id = 0) {
        $requires_date_of_encounter = true;
        $trigger_assessment_settings = $this->fetchCourseSettingsByShortname($course_id, "trigger_assessment");
        if ($trigger_assessment_settings) {
            $settings = @json_decode($trigger_assessment_settings["value"], true);
            if ($settings && array_key_exists("date_of_encounter_required", $settings)) {
                $requires_date_of_encounter = $settings["date_of_encounter_required"];
            }
        }
        return $requires_date_of_encounter;
    }

    /**
     * This function returns preset EPA filters to be used by the advancedSearch widget for a learner
     * @param string $current_epa_filter_label
     * @param int $proxy_id
     * @param int $course_id
     * @return array
     */
    public function getLearnerEpaFilterPresets($current_epa_filter_label = "", $proxy_id = 0, $course_id = 0) {
        global $translate;
        $preset_filters = array();
        $learner_stage = $this->getLearnerStage($proxy_id, $course_id);
        if ($learner_stage) {
            $preset_filters["current_stage_epas"] = array(
                "selector" => html_encode("stage-". $learner_stage["objective_code"] ."-epas"),
                "label" => $current_epa_filter_label,
                "value" => html_encode($learner_stage["objective_code"]),
                "title" => html_encode($learner_stage["objective_name"]),
                "name" => "current_stage_epas"
            );

            /*
             * Commented out so only the current stage EPA filters are available in the EPA picker in the trigger assessment interface.
             * Can be uncomment to re-enable
            $course_stages = $this->fetchCourseStages($course_id);
            if ($course_stages) {
                foreach ($course_stages as $course_stage) {
                    if ($course_stage["objective_code"] != $learner_stage["objective_code"]) {
                        $preset_filters["stage_". $course_stage["objective_code"] ."_epas"] = array(
                            "selector" => html_encode("stage-". $course_stage["objective_code"] ."-epas"),
                            "label" => html_encode($course_stage["objective_code"]),
                            "value" => html_encode($course_stage["objective_code"]),
                            "title" => html_encode($course_stage["objective_name"]),
                            "name" => html_encode("stage_". $course_stage["objective_code"] ."_epas"),
                            "filter_heading_label" => html_encode($course_stage["objective_name"])
                        );
                    }
                }
            }*/
            $schedule = Models_Schedule::fetchRowByAudienceValueAudienceType($proxy_id, "proxy_id", true, time());
            if ($schedule) {
                $course_objective_model = new Models_Schedule_CourseObjective();
                $objectives = $course_objective_model->fetchAllByScheduleIDCourseID($schedule->getCourseID(), $schedule->getScheduleParentID());
                if ($objectives) {
                    /**
                     * Current Rotation Filter
                     */
                    $epa_list = array();
                    foreach ($objectives as $objective) {
                        $completion = Models_Objective_Completion::fetchRowByObjectiveID($objective["objective_id"], $course_id, $proxy_id);
                        if (!$completion) {
                            $epa_list[] = $objective["objective_code"];
                        }
                    }
                    $preset_filters["current_rotation_epas"] = array(
                        "selector" => html_encode("stage-rotation-epas"),
                        "label" => html_encode($translate->_("Current Rotation EPAs")),
                        "value" => $epa_list,
                        "title" => html_encode($translate->_("EPAs that are tagged to the current rotation that are not yet completed")),
                        "name" => html_encode("stage_rotation_epas"),
                        "filter_heading_label" => html_encode($translate->_("Current Rotation EPAs"))
                    );

                    /**
                     * Priority EPA filter
                     */
                    $priority_epa_list = array();
                    foreach ($objectives as $objective) {
                        if ($objective["priority"] == 1) {
                            $completion = Models_Objective_Completion::fetchRowByObjectiveID($objective["objective_id"], $course_id, $proxy_id);
                            if (!$completion) {
                                $priority_epa_list[] = $objective["objective_code"];
                            }
                        }
                    }
                    $preset_filters["priority_epas"] = array(
                        "selector" => html_encode("priority-epas"),
                        "label" => html_encode($translate->_("Priority EPAs")),
                        "value" => $priority_epa_list,
                        "title" => html_encode($translate->_("EPAs that are tagged to the current rotation that are not yet completed and are marked as a priority")),
                        "name" => html_encode("priority_epas"),
                        "filter_heading_label" => html_encode($translate->_("Priority EPAs"))
                    );
                }
            }
        }


        return $preset_filters;
    }

    /**
     * Fetch Course Key Competencies
     * @return array
     */
    private function fetchCourseKeyCompetencies() {
        // Initialize an objective tree object
        $tree_object = new Entrada_CBME_ObjectiveTree(array(
            "actor_proxy_id" => $this->actor_proxy_id,
            "actor_organisation_id" => $this->actor_organisation_id,
            "course_id" => $this->actor_course_id
        ));

        return $tree_object->fetchTreeNodesAtDepth(4, "o.`objective_code`", true);
    }

    /**
     * Fetch Course Key Competencies
     * @return array
     */
    private function fetchCourseEnablingCompetencies() {
        // Initialize an objective tree object
        $tree_object = new Entrada_CBME_ObjectiveTree(array(
            "actor_proxy_id" => $this->actor_proxy_id,
            "actor_organisation_id" => $this->actor_organisation_id,
            "course_id" => $this->actor_course_id
        ));

        return $tree_object->fetchTreeNodesAtDepth(5, "o.`objective_code`", true);
    }

    /*
     * Return the list of values to be used by the triggered by filter in the CBME filters
     * @return array
     */
    public function getTriggeredByFilter() {
        $triggered_by_groups = $this->datasource->getTriggeredByGroups($this->actor_organisation_id);
        $groups = array();
        if ($triggered_by_groups) {
            foreach ($triggered_by_groups as $group) {
                $groups[] = $group["group"];
            }
            $groups = array_unique($groups);
        }
        return $groups;
    }

    /**
     * Fetch the entrustment item from a specific form
     * @param int $form_id
     * @param $item_code
     * @return array
     */
    public function fetchFormEntrustmentItem($form_id = 0, $item_code = "") {
        global $db;
        $item = array();
        $query = "  SELECT d.* FROM `cbl_assessments_lu_forms` AS a
                    JOIN `cbl_assessments_lu_form_types` AS b
                    ON a.`form_type_id` = b.`form_type_id`
                    JOIN `cbl_assessment_form_elements` AS c
                    ON a.`form_id` = c.`form_id`
                    JOIN `cbl_assessments_lu_items` AS d
                    ON c.`element_id` = d.`item_id`
                    LEFT JOIN `cbl_assessments_lu_item_responses` AS g
                    ON d.`item_id` = g.`item_id`
                    LEFT JOIN `cbl_assessments_lu_response_descriptors` AS h
                    ON g.`ardescriptor_id` = h.`ardescriptor_id`
                    JOIN `cbl_assessment_form_type_meta` AS i
                    ON b.`form_type_id` = i.`form_type_id`
                    WHERE a.`form_id` = ?
                    AND d.`item_code` = ?
                    AND i.`meta_name` = 'show_entrustment'";
        $result = $db->GetRow($query, array($form_id, $item_code));
        if ($result) {
            $item = $result;
        }
        return $item;
    }
}