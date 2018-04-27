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
class Entrada_CBME_AssessmentPlan extends Entrada_CBME_Base {

    protected $assessment_plan = null; // Worker object
    protected $cperiod_id = null;
    protected $start_date;
    protected $finish_date;
    protected $assessment_plan_container_id = null;
    protected $limit_dataset = array();

    public function __construct($arr = array()) {
        parent::__construct($arr);

        /**
         * Instantiate the assessment plan worker object
         */
        $worker_options = array(
            "course_id" => $this->course_id,
            "limit_dataset" => $this->limit_dataset,
            "assessment_plan_container_id" => $this->assessment_plan_container_id,
            "cperiod_id" => $this->cperiod_id,
            "start_date" => $this->start_date,
            "finish_date" => $this->finish_date
        );

        $this->assessment_plan = new Entrada_CBME_Workers_AssessmentPlan($this->buildActorArray($worker_options));
    }

    /**
     * Get the assessment plan containers from the worker object
     * @return array
     */
    public function getAssessmentPlanContainers() {
        return $this->assessment_plan->getAssessmentPlanContainers();
    }

    /**
     * Get the cperiod from each course assessment plan
     * @return mixed
     */
    public function getAssessmentPlanCperiods() {
        return $this->assessment_plan->getAssessmentPlanCperiods();
    }

    /**
     * Get as assessment plan container from the worker object
     * @return array
     */
    public function getAssessmentPlanContainer() {
        return $this->assessment_plan->getAssessmentPlanContainer();
    }

    /**
     * Get the curriculum periods from the worker object
     * @return array
     */
    public function getCurriculumPeriods() {
        return $this->assessment_plan->getCurriculumPeriods();
    }

    /**
     * Save or update an assessment plan container
     * @param $cperiod_id
     * @param $title
     * @param $description
     * @param $course_id
     * @param $proxy_id
     * @return bool
     */
    public function saveAssessmentPlanContainer($cperiod_id, $title, $description, $course_id, $proxy_id) {
        return $this->assessment_plan->saveData($cperiod_id, $title, $description, $course_id, $proxy_id);
    }

    /**
     * Get any errors form the worker
     * @return array
     */
    public function getErrors() {
        return $this->assessment_plan->getErrorMessages();
    }

    /**
     * Get course specific EPA objectives
     * @return mixed
     */
    public function getEpas() {
        return $this->assessment_plan->getCourseEpaObjectives();
    }

    /**
     * Call the worker delete method
     * @param array $assessment_plan_containers
     * @return bool
     */
    public function delete($assessment_plan_containers = array()) {
        return $this->assessment_plan->delete($assessment_plan_containers);
    }

    /**
     * Fetch the form entrustment item
     * @param int $form_id
     * @return array|bool
     */
    public function fetchFormEntrustmentItem($form_id = 0) {
        return $this->assessment_plan->fetchFormEntrustmentItem($form_id);
    }

    /**
     * Fetch the responses for the entrustment item
     * @param int $item_id
     * @return array
     */
    public function fetchFormEntrustmentItemResponses($item_id = 0) {
        return $this->assessment_plan->fetchFormEntrustmentItemResponses($item_id);
    }

    /**
     * This function checks if an assessment container belongs to a course
     * @param int $assessment_plan_container_id
     * @param int $course_id
     * @return bool
     */
    public function assessmentPlanBelongsToCourse($assessment_plan_container_id = 0, $course_id = 0) {
        return $this->assessment_plan->assessmentPlanBelongsToCourse($assessment_plan_container_id, $course_id);
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
        return $this->assessment_plan->canAccessPlanRequirements($objective_id, $cbme_objective_tree_id, $course_id, $organisation_id);
    }

    /**
     * Get all valid assessment plan tools
     * @param $objective_tree_id
     * @param $course_id
     */
    public function getAssessmentPlanTools($objective_tree_id, $course_id) {
        return $this->assessment_plan->fetchAssessmentPlanTools($objective_tree_id, $course_id);
    }
}