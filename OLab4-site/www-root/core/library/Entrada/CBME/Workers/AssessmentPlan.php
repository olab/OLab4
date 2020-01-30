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
* This model handles assessment plan form objectives
*
* @author Organisation: Queens University
* @author Developer: Josh Dillon <jdillon@queensu.ca>
* @copyright Copyright 2018 Queens University. All Rights Reserved.
*/
class Entrada_CBME_Workers_AssessmentPlan extends Entrada_CBME_Base {
    protected $assessment_plan_container_id = null;
    protected $assessment_plan_container = null;
    protected $start_date = null;
    protected $finish_date = null;
    protected $dataset = array();
    protected $limit_dataset = array();

    public function __construct($arr = array()) {
        parent::__construct($arr);

        if (!is_array($this->limit_dataset)) {
            $this->limit_dataset = array();
        }
        $this->assessment_plan_container = new Models_Assessments_PlanContainer();
    }

    /**
     * Fetch all assessment plan containers for a specified course
     * @param int $course_id
     * @param bool $include_cperiod
     * @return array
     */
    private function fetchAllContainersByCourseID($course_id = 0, $include_cperiod = true) {
        return $this->assessment_plan_container->fetchAllByCourseID($course_id, $include_cperiod);
    }

    /**
     * Get all curriculum periods for a specified organisation
     * @param int $organisation_id
     * @return array
     */
    private function fetchCurriculumPeriods($organisation_id = 0) {
        $curriculum_types = Models_Curriculum_Type::fetchAllByOrg($organisation_id);
        $curriculum_periods = array();
        if ($curriculum_types) {
            foreach ($curriculum_types as $curriculum_type) {
                $periods = Models_Curriculum_Period::fetchAllByCurriculumType($curriculum_type->getID());
                if ($periods) {
                    foreach ($periods as $period) {
                        $curriculum_periods[] = $period->toArray();
                    }
                }
            }
        }
        return $curriculum_periods;
    }

    /**
     * Get the assessment plan containers from the dataset
     * @return array
     */
    public function getAssessmentPlanContainers() {
        $this->fetchData();
        return $this->dataset["assessment_plan_containers"];
    }

    /**
     * Get the curriculum periods from the dataset
     * @return array
     */
    public function getCurriculumPeriods() {
        $this->fetchData();
        return $this->dataset["curriculum_periods"];
    }

    /**
     * Get the assessment plan container from the dataset
     * @return array
     */
    public function getAssessmentPlanContainer() {
        $this->fetchData();
        return $this->dataset["assessment_plan_container"];
    }

    /**
     * Get the EPAs for a course
     * @return array
     */
    public function getCourseEpaObjectives() {
        $this->fetchData();
        return $this->dataset["course_epa_objectives"];
    }

    /**
     * Get the cperiod from each course assessment plan
     * @return array
     */
    public function getAssessmentPlanCperiods() {
        $this->fetchData();
        return $this->dataset["assessment_plan_cperiods"];
    }

    /**
     * This function returns a defined dataset
     * @return array
     */
    public function fetchData() {
        if (empty($this->dataset)) {
            $this->buildDataset();
        } else {
            if (!array_key_exists("is_stale", $this->dataset) || $this->dataset["is_stale"]) {
                $this->buildDataset(); // builds dataset
            }
        }
        return $this->dataset;
    }

    /**
     * Assemble the related data for this visualization.
     * @return bool
     */
    private function buildDataset() {
        $this->dataset = $this->buildDefaultDataset();

        if (in_array("assessment_plan_containers", $this->limit_dataset)) {
            $this->dataset["assessment_plan_containers"] = $this->fetchAllContainersByCourseID($this->course_id);
        }

        if (in_array("curriculum_periods", $this->limit_dataset)) {
            $this->dataset["curriculum_periods"] = $this->fetchCurriculumPeriods($this->actor_organisation_id);
        }

        if (in_array("assessment_plan_container", $this->limit_dataset)) {
            $assessment_plan_container = $this->fetchAssessmentPlanContainer();
            if ($assessment_plan_container) {
                $this->dataset["assessment_plan_container"] = $assessment_plan_container->toArray();
            }
        }

        if (in_array("course_epa_objectives", $this->limit_dataset)) {
            $this->dataset["course_epa_objectives"] = $this->fetchCourseEpaObjectives($this->start_date, $this->finish_date);
        }

        if (in_array("assessment_plan_cperiods", $this->limit_dataset)) {
            $assessment_plan_containers = $this->fetchAllContainersByCourseID($this->course_id, false);
            if ($assessment_plan_containers) {
                foreach ($assessment_plan_containers as $assessment_plan_container) {
                    $this->dataset["assessment_plan_cperiods"][] = $assessment_plan_container["cperiod_id"];
                }
            }
        }
    }

    /**
     * This function returns a default dataset
     * @return array
     */
    private function buildDefaultDataset() {
        $default_dataset = array(
            "is_stale" => false,
            "assessment_plan_containers" => array(),
            "curriculum_periods" => array(),
            "assessment_plan_container" => array(),
            "course_epa_objectives" => array(),
            "assessment_plan_cperiods" => array()
        );
        return $default_dataset;
    }

    /**
     * Mark the current dataset as stale and remove it from global cache.
     */
    public function invalidateDataset() {
        $this->setStale();
    }

    /**
     * Save the assessment plan container
     * @param $cperiod_id
     * @param $title
     * @param $description
     * @param $course_id
     * @param $proxy_id
     * @return bool|int
     */
    public function saveData($cperiod_id, $title, $description, $course_id, $proxy_id) {
        if (!$cperiod_id || !$title || !$course_id || !$proxy_id) {
            return false;
        }

        $this->dataset["assessment_plan_container"]["cperiod_id"] = $cperiod_id;
        $this->dataset["assessment_plan_container"]["title"] = $title;
        $this->dataset["assessment_plan_container"]["description"] = $description;
        $this->dataset["assessment_plan_container"]["course_id"] = $course_id;

        if ($this->assessment_plan_container_id) {
            $this->dataset["assessment_plan_container"]["assessment_plan_container_id"] = $this->assessment_plan_container_id;
            $this->dataset["assessment_plan_container"]["updated_date"] = time();
            $this->dataset["assessment_plan_container"]["updated_by"] = $proxy_id;
            if ($this->updateAssessmentPlanContainer()) {
                return $this->assessment_plan_container_id;
            }
        } else {
            $this->dataset["assessment_plan_container"]["created_date"] = $proxy_id;
            $this->dataset["assessment_plan_container"]["created_by"] = $proxy_id;
            if ($assessment_plan_container_id = $this->saveAssessmentPlanContainer()) {
                return $assessment_plan_container_id;
            }
        }

        return false;
    }

    /**
     * Soft delete the provided assessment plan containers
     * @param array $assessment_plan_containers
     * @return bool
     */
    public function delete($assessment_plan_containers = array()) {
        global $translate, $db;
        if (is_array($assessment_plan_containers) && !empty($assessment_plan_containers)) {
            foreach ($assessment_plan_containers as $assessment_plan_container) {
                if ($tmp_input = clean_input($assessment_plan_container, array("trim", "int"))) {
                    $assessment_plan_container_model = new Models_Assessments_PlanContainer();
                    $assessment_plan_container = $assessment_plan_container_model->fetchRowByID($assessment_plan_container);
                    if ($assessment_plan_container) {
                        if ($assessment_plan_container->fromArray(array("deleted_date" => time()))->update()) {
                            add_success(sprintf($translate->_("Successfully deleted the <strong>%s</strong> assessment plan."), $assessment_plan_container->getTitle()));
                            return true;
                        } else {
                            $this->addErrorMessage(sprintf($translate->_("A problem occurred while attempting to delete the <strong>%s</strong> assessment plan. Please try again later."), $assessment_plan_container->getTitle()));
                            application_log("error", "A problem occurred while attempting to update assessment plan container " . $assessment_plan_container->getID() . ". DB said: " . $db->ErrorMsg());
                        }
                    }
                }
            }
        } else {
            $this->addErrorMessage($translate->_("No assessment plans were selected for removal."));
        }
        return false;
    }

    /**
     * Fetch the entrusment item from a specific form
     * @param int $form_id
     * @return array|bool
     */
    public function fetchFormEntrustmentItem($form_id = 0) {
        $visualization = new Entrada_CBME_Visualization(array(
            "actor_proxy_id" => $this->actor_proxy_id,
            "actor_organisation_id" => $this->actor_organisation_id
        ));

        $item_code = $this->determineEntrustmentItemCode($form_id);
        if(!$item_code) {
            return false;
        }
        return $visualization->fetchFormEntrustmentItem($form_id, $item_code);
    }

    /**
     * Based on the provided $form_id return the shortname of the forms entrustment item
     * @param int $form_id
     * @return bool|string
     */
    private function determineEntrustmentItemCode($form_id = 0) {
        $form_type_model = new Models_Assessments_Form_Type();
        $form_type = $form_type_model->fetchRowByFormID($form_id);
        if (!$form_type) {
            return false;
        }
        $item_code = $this->getRatingScaleItemCode($form_type->getShortname());
        return $item_code;
    }

    /**
     * Return the appropriate rating scale item_code based on the $form_type_shortname
     * @param $form_type_shortname
     * @return string
     */
    private function getRatingScaleItemCode($form_type_shortname) {
        $item_code = "";
        switch ($form_type_shortname) {
            case "cbme_supervisor" :
                $item_code = "CBME-supervisor-entrustment_scale";
                break;
            case "cbme_fieldnote" :
                $item_code = "CBME-fieldnote-entrustment_scale";
                break;
            case "cbme_procedure" :
                $item_code = "CBME-procedure-entrustment_scale";
                break;
            case "cbme_ppa_form" :
                break;
            case "cbme_rubric" :
                break;
        }
        return $item_code;
    }

    /**
     * Fetch the responses for an entrustment item
     * @param int $item_id
     * @return array
     */
    public function fetchFormEntrustmentItemResponses($item_id = 0) {
        $visualization = new Entrada_CBME_Visualization(array(
            "actor_proxy_id" => $this->actor_proxy_id,
            "actor_organisation_id" => $this->actor_organisation_id
        ));

        return $visualization->fetchRatingScaleResponses($item_id);
    }

    /**
     * Update an assessment plan container
     * @return bool
     */
    private function updateAssessmentPlanContainer() {
        global $db, $translate;
        $assessment_plan_container_model = new Models_Assessments_PlanContainer();
        $assessment_plan_container = $assessment_plan_container_model->fetchRowByID($this->assessment_plan_container_id);
        if ($assessment_plan_container) {
            $this->dataset["assessment_plan_container"]["created_date"] = $assessment_plan_container->getCreatedDate();
            $this->dataset["assessment_plan_container"]["created_by"] = $assessment_plan_container->getCreatedBy();
            if (!$assessment_plan_container->fromArray($this->dataset["assessment_plan_container"])->update()) {
                $this->addErrorMessage($translate->_("A problem occurred while attempting to update this Assessment Plan. Please try again at a later time."));
                application_log("error", "A problem occurred while attempting to update assessment plan container " . $this->assessment_plan_container_id . ". DB said: " . $db->ErrorMsg());
            } else {
                return true;
            }
        }
        return false;
    }

    /**
     * Save an assessment plan container
     * @return bool|int
     */
    private function saveAssessmentPlanContainer() {
        global $db, $translate;
        $assessment_plan_container_model = new Models_Assessments_PlanContainer();
        if (!$assessment_plan_container_model->fromArray($this->dataset["assessment_plan_container"])->insert()) {
            $this->addErrorMessage($translate->_("A problem occurred while attempting to save this Assessment Plan. Please try again at a later time."));
            application_log("error", "A problem occurred while attempting to save an assessment plan container. DB said: " . $db->ErrorMsg());
        } else {
            return $assessment_plan_container_model->getID();
        }
        return false;
    }

    /**
     * Fetch an assessment plan container
     * @return array
     */
    private function fetchAssessmentPlanContainer() {
        $assessment_plan_container = $this->assessment_plan_container->fetchRowByID($this->assessment_plan_container_id);
        return $assessment_plan_container;
    }

    /**
     * Fetch course specific EPAs from the objective tree
     * @param null $active_from
     * @param null $active_until
     * @return array|bool|mixed
     */
    private function fetchCourseEpaObjectives($active_from = null, $active_until = null) {
        /**
         * instantiate the assessment plan model
         */
        $assessment_plan_model = new Models_Assessments_Plan();

        /**
         * Initialize an objective tree object
         */
        $tree_object = new Entrada_CBME_ObjectiveTree(array(
            "actor_proxy_id" => $this->actor_proxy_id,
            "actor_organisation_id" => $this->actor_organisation_id,
            "course_id" => $this->course_id
        ));

        /**
         * Fetch course EPAs from the course objective tree
         */
        $tree_epas = $tree_object->fetchTreeNodesByObjectiveSetShortname("epa", null, false, $active_from, $active_until);
        if ($tree_epas) {
            foreach ($tree_epas as &$tree_epa) {
                /**
                 * Add the assessment_plan_id to each tree EPA
                 */
                $assessment_plan = $assessment_plan_model->fetchRowByObjectiveID($tree_epa["objective_id"]);
                if ($assessment_plan) {
                    $tree_epa["assessment_plan_id"] = $assessment_plan->getID();
                    $tree_epa["assessment_plan_published"] = $assessment_plan->getPublished();
                } else {
                    $tree_epa["assessment_plan_id"] = false;
                    $tree_epa["assessment_plan_published"] = 0;
                }
            }
        }
        return $tree_epas;
    }

    /**
     * This function checks if an assessment container belongs to a course
     * @param int $assessment_plan_container_id
     * @param int $course_id
     * @return bool
     */
    public function assessmentPlanBelongsToCourse($assessment_plan_container_id = 0, $course_id = 0) {
        $belongs_to_course = false;
        $assessment_plan_container_model = new Models_Assessments_PlanContainer();
        $plan_container = $assessment_plan_container_model->fetchRowByID($assessment_plan_container_id);
        if ($plan_container) {
            if ($plan_container->getCourseID() == $course_id) {
                $belongs_to_course = true;
            }
        }
        return $belongs_to_course;
    }

    /**
     * This function checks if an objective_id belongs to a course
     * @param $objective_id
     * @return bool
     */
    private function objectiveBelongsToCourseAndIsEpa($objective_id) {
        /**
         * Initialize an objective tree object
         */
        $tree_object = new Entrada_CBME_ObjectiveTree(array(
            "actor_proxy_id" => $this->actor_proxy_id,
            "actor_organisation_id" => $this->actor_organisation_id,
            "course_id" => $this->course_id
        ));

        $belongs_to_course = false;
        $tree_objectives = $tree_object->findNodesByObjectiveID($objective_id);
        if ($tree_objectives) {
            foreach ($tree_objectives as $objective) {
                if ($this->objectiveIsEpa($objective->getObjectiveID())) {
                    $belongs_to_course = true;
                }
            }
        }
        return $belongs_to_course;
    }

    /**
     * Checks if a $cbme_objective_tree_id belongs to the specified course
     * @param int $cbme_objective_tree_id
     * @param int $course_id
     * @param int $organisation_id
     * @return bool
     */
    private function objectiveTreeIDBelongsToCourse($cbme_objective_tree_id , $course_id, $organisation_id) {
        $belongs_to_course = false;
        $objective_tree_model = new Models_CBME_ObjectiveTree();
        $objective_tree_row = $objective_tree_model->fetchRowByID($cbme_objective_tree_id);
        if ($objective_tree_row) {
            if ($objective_tree_row->getCourseID() == $course_id && $objective_tree_row->getOrganisationID() == $organisation_id) {
                $belongs_to_course = true;
            }
        }
        return $belongs_to_course;
    }

    /**
     * This function checks if assessment plan requirements can be accessed
     * @param $objective_id
     * @param $cbme_objective_tree_id
     * @param $course_id
     * @param $organisation_id
     * @return bool
     */
    public function canAccessPlanRequirements($objective_id, $cbme_objective_tree_id, $course_id, $organisation_id) {
        if (!$this->objectiveTreeIDBelongsToCourse($cbme_objective_tree_id, $course_id, $organisation_id)) {
            return false;
        }
        if (!$this->objectiveBelongsToCourseAndIsEpa($objective_id)) {
            return false;
        }
        return true;
    }

    /**
     * Check if an objective is an EPA.
     * @param $objective_id
     * @return bool
     */
    private function objectiveIsEpa($objective_id) {

        global $db;
        $objective_is_epa = false;
        $query = "  SELECT a.`shortname` FROM `global_lu_objective_sets` AS a
                    JOIN `global_lu_objectives` AS b
                    ON a.`objective_set_id` = b.`objective_set_id`
                    WHERE a.`shortname` = 'epa'
                    AND b.`objective_id` = ?";
        $result = $db->GetRow($query, array($objective_id));
        if ($result) {
            $objective_is_epa = true;
        }
        return $objective_is_epa;
    }

    /**
     * Fetch all valid assessment plan tools
     * @param $objective_tree_id
     * @param $course_id
     */
    public function fetchAssessmentPlanTools($objective_tree_id, $course_id) {
        $forms_api = new Entrada_Assessments_Forms(array(
            "actor_proxy_id" => $this->actor_proxy_id,
            "actor_organisation_id" => $this->actor_organisation_id,
        ));

        $forms_tagged = $forms_api->fetchFormsTaggedToTreeBranch($objective_tree_id, $course_id, null);
        $assessment_tool_data = array();
        if ($forms_tagged) {
            foreach ($forms_tagged as $form) {
                if ($form["form_type_shortname"] != "cbme_procedure") {
                    if ($this->isValidAssessmentPlanTool($form["form_id"], $form["form_type_shortname"])) {
                        $assessment_tool_data[] = array("target_id" => $form["form_id"], "target_label" => $form["title"]);
                    }
                }
            }
        }
        return $assessment_tool_data;
    }

    public function isValidAssessmentPlanTool($form_id, $form_type_shortname) {
        $valid_tool = false;
        $forms_model = new Models_Assessments_Form();
        $item_code = $this->getRatingScaleItemCode($form_type_shortname);
        $rating_scale_item = $forms_model->fetchFormGlobalRatingScaleItems($form_id, $item_code);
        if ($rating_scale_item) {
            $valid_tool = true;
        }
        return $valid_tool;
    }
}