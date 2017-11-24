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
 * A model for handling CBME Course Objectives
 *
 * @author Organisation: Queen's University
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_CBME_CourseObjective extends Models_Base {
    protected $cbme_course_objective_id, $objective_id, $course_id, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "cbme_course_objectives";
    protected static $primary_key = "cbme_course_objective_id";
    protected static $default_sort_column = "objective_id";

    public function getID () {
        return $this->cbme_course_objective_id;
    }

    public function getObjectiveID () {
        return $this->objective_id;
    }

    public function getCourseID () {
        return $this->course_id;
    }

    public function getCreatedDate () {
        return $this->created_date;
    }

    public function getCreatedBy () {
        return $this->created_by;
    }

    public function getUpdatedDate () {
        return $this->updated_date;
    }

    public function getUpdatedBy () {
        return $this->updated_by;
    }

    public function getDeletedDate () {
        return $this->deleted_date;
    }

    public function fetchAllByObjectiveSetIDOrgIDCourseID ($objective_set_id = null, $organisation_id = null, $course_id = null, $active = 1) {
        global $db;
        $objectives = array();

        $query = "  SELECT a.*, b.*, c.* FROM `global_lu_objectives` AS a
                    JOIN `objective_organisation` AS b
                    ON a.`objective_id` = b.`objective_id`
                    JOIN `cbme_course_objectives` AS c
                    ON b.`objective_id` = c.`objective_id`
                    WHERE a.`objective_set_id` = ?
                    AND a.`objective_parent` != 0
                    AND b.`organisation_id` = ?
                    AND c.`course_id` = ?
                    AND a.`objective_active` = ?";

        $results = $db->GetAll($query, array($objective_set_id, $organisation_id, $course_id, $active));
        if ($results) {
            foreach ($results as $result) {
                $objective = new Models_Objective();
                $objectives[] = $objective->fromArray($result);
            }
        }

        return $objectives;
    }
}