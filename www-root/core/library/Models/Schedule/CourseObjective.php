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
 * @author Organisation: Queen's University
 * @author Developer: Joshua Belanger
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Models_Schedule_CourseObjective extends Models_Base {
    protected $sco_id, $course_id, $schedule_id, $objective_id, $likelihood_id, $priority,
        $created_date, $created_by, $updated_date, $updated_by, $updated_by_type, $deleted_date, $deleted_by;

    protected static $table_name = "cbl_schedule_course_objectives";
    protected static $primary_key = "sco_id";
    protected static $default_sort_column = "schedule_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->sco_id;
    }

    public function getCourseID() {
        return $this->course_id;
    }

    public function getScheduleID() {
        return $this->schedule_id;
    }

    public function getObjectiveID() {
        return $this->objective_id;
    }

    public function getLikelihoodID() {
        return $this->likelihood_id;
    }

    public function getPriority() {
        return $this->priority;
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

    public function fetchAllByScheduleIDCourseIDJoinRelevantInfo($schedule_id, $course_id, $deleted_date = NULL) {
        global $db;

        $AND_deleted_date = " AND sco.`deleted_date` " . ($deleted_date ? " IS >= {$deleted_date}" : "IS NULL");

        $query = "SELECT *, gllh.`title` AS `likelihood_title`, glo.`objective_name` AS `objective_title` 
                  FROM {$this::$table_name} AS sco
                  JOIN `global_lu_objectives` AS glo
                  ON glo.`objective_id` = sco.`objective_id`
                  JOIN `global_lu_likelihoods` AS gllh
                  ON sco.`likelihood_id` = gllh.`likelihood_id`
                  WHERE sco.`schedule_id` = ?
                  AND sco.`course_id` = ?
                  {$AND_deleted_date}
                  ORDER BY glo.`objective_code`";

        $results = $db->GetAll($query, array($schedule_id, $course_id));

        return $results;
    }

    public function getCourseRotationsObjectiveID($objective_id, $course_id, $deleted_date = NULL) {
        global $db;

        $AND_deleted_date = " AND s.`deleted_date` " . ($deleted_date ? " IS >= {$deleted_date}" : "IS NULL") .
                            " AND sco.`deleted_date` " . ($deleted_date ? " IS >= {$deleted_date}" : "IS NULL");

        $query = "SELECT s.*
                  FROM {$this::$table_name} AS sco
                  JOIN `cbl_schedule` AS s
                  ON s.`schedule_id` = sco.`schedule_id`
                  WHERE `objective_id` = ?
                  AND sco.`course_id` = ?
                  AND s.`course_id` = ?
                  {$AND_deleted_date}";

        $results = $db->GetAll($query, array($objective_id, $course_id, $course_id));

        return $results;
    }

    public function fetchAllByScheduleIDObjectiveIDCourseID($schedule_id, $objective_id, $course_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "schedule_id", "value" => $schedule_id, "method" => "="),
            array("key" => "objective_id", "value" => $objective_id, "method" => "="),
            array("key" => "course_id", "value" => $course_id, "method" => "="),
            array("key" => "deleted_date", "value" => NULL, "method" => "IS")
        ));
    }

    public function fetchAllByScheduleIDCourseID($course_id, $schedule_id) {
        global $db;
        $query = "SELECT * FROM `cbl_schedule_course_objectives` as a
                  INNER JOIN `global_lu_objectives` as b
                  ON a.`objective_id` = b.`objective_id`
                  INNER JOIN `global_lu_likelihoods` as c
                  ON a.`likelihood_id` = c.`likelihood_id`
                  WHERE a.`course_id` = ?
                  AND a.`schedule_id` = ?
                  AND a.`deleted_date` IS NULL
                  AND b.`objective_active` = 1
                  ORDER BY b.`objective_code`";

        $results = $db->GetAll($query, array($course_id, $schedule_id));
        return $results;
    }

}