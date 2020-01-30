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
 * This model handles assessment plan containers
 *
 * @author Organisation: Queens University
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2018 Queens University. All Rights Reserved.
 */
class Models_Assessments_PlanContainer extends Models_Base {
    protected $assessment_plan_container_id, $title, $description, $course_id, $cperiod_id, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;
    protected static $table_name = "cbl_assessment_plan_containers";
    protected static $primary_key = "assessment_plan_container_id";
    protected static $default_sort_column = "assessment_plan_container_id";

    public function getID() {
        return $this->assessment_plan_container_id;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getCourseID() {
        return $this->course_id;
    }

    public function getCperiodID() {
        return $this->cperiod_id;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    /**
     * Fetch all assessment plan containers for a specified course and optionally include the
     * curriculum period the container is associated with
     *
     * @param int $course_id
     * @param bool $include_cperiod
     * @param null $deleted_date
     * @return array
     */
    public function fetchAllByCourseID($course_id = 0, $include_cperiod = true, $deleted_date = null) {
        $self = new self();
        $data = array();

        $assessment_plan_containers = $self->fetchAll(array(
            array("key" => "course_id", "value" => $course_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));

        if ($assessment_plan_containers) {
            foreach ($assessment_plan_containers as $assessment_plan_container) {
                $assessment_plan_container_array = $assessment_plan_container->toArray();
                if ($include_cperiod) {
                    $curriculum_period_model = new Models_Curriculum_Period();
                    $curriculum_period = $curriculum_period_model->fetchRowByID($assessment_plan_container->getCperiodID());
                    if ($curriculum_period) {
                        $assessment_plan_container_array["curriculum_period"] = $curriculum_period->toArray();
                    }
                }
                $data[] = $assessment_plan_container_array;
            }
        }

        return $data;
    }

    public function fetchRowByID($assessment_plan_container_id = 0, $deleted_date = null) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "assessment_plan_container_id", "method" => "=", "value" => $assessment_plan_container_id),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public function fetchRowContainerIDCourseID($assessment_plan_container_id = 0, $course_id = 0, $deleted_date = null) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "assessment_plan_container_id", "method" => "=", "value" => $assessment_plan_container_id),
            array("key" => "course_id", "method" => "=", "value" => $assessment_plan_container_id),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }
}