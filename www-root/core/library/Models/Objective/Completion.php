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
 * A model for handling objectives completion
 *
 * @author Organisation: Queens University
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2017 Queens University. All Rights Reserved.
 */

class Models_Objective_Completion extends Models_Base {

    protected $lo_completion_id;
    protected $proxy_id;
    protected $course_id;
    protected $objective_id;
    protected $created_date;
    protected $created_by;
    protected $created_reason;
    protected $deleted_date;
    protected $deleted_by;
    protected $deleted_reason;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "cbl_learner_objectives_completion";
    protected static $primary_key = "lo_completion_id";
    protected static $default_sort_column = "proxy_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->lo_completion_id;
    }

    public function getLoCompletionID() {
        return $this->lo_completion_id;
    }

    public function setLoCompletionID($lo_completion_id) {
        $this->lo_completion_id = $lo_completion_id;

        return $this;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getCourseID() {
        return $this->course_id;
    }

    public function setProxyID($proxy_id) {
        $this->proxy_id = $proxy_id;

        return $this;
    }

    public function setCourseID($course_id) {
        $this->course_id = $course_id;
    }

    public function getObjectiveID() {
        return $this->objective_id;
    }

    public function setObjectiveID($objective_id) {
        $this->objective_id = $objective_id;

        return $this;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function setCreatedDate($created_date) {
        $this->created_date = $created_date;

        return $this;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function setCreatedBy($created_by) {
        $this->created_by = $created_by;

        return $this;
    }

    public function getCreatedReason() {
        return $this->created_reason;
    }

    public function setCreatedReason($created_reason) {
        $this->created_reason = $created_reason;

        return $this;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;

        return $this;
    }

    public function getDeletedBy() {
        return $this->deleted_by;
    }

    public function setDeletedBy($deleted_by) {
        $this->deleted_by = $deleted_by;

        return $this;
    }

    public function getDeletedReason() {
        return $this->deleted_reason;
    }

    public function setDeletedReason($deleted_reason) {
        $this->deleted_reason = $deleted_reason;

        return $this;
    }

    public static function fetchRowByID($lo_completion_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "lo_completion_id", "method" => "=", "value" => $lo_completion_id)
        ));
    }

    public static function fetchRowByObjectiveID($objective_id, $course_id, $proxy_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "objective_id", "method" => "=", "value" => $objective_id),
            array("key" => "course_id", "method" => "=", "value" => $course_id),
            array("key" => "proxy_id", "method" => "=", "value" => $proxy_id),
            array("key" => "deleted_date", "method" => "IS", "value" => NULL)
        ));
    }

    public static function fetchAllByObjectiveID($objective_id, $course_id, $proxy_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "objective_id", "method" => "=", "value" => $objective_id),
            array("key" => "course_id", "method" => "=", "value" => $course_id),
            array("key" => "proxy_id", "method" => "=", "value" => $proxy_id)
        ), "=", "AND", "created_date", "DESC");
    }

    public static function fetchAllByObjectiveIDAndDate($objective_id, $course_id, $proxy_id, $start_date = null, $end_date = null) {
        global $db;
        $output = array();
        $constraints = array($proxy_id, $course_id, $objective_id);

        $AND_date_clause = "";
        if ($start_date && $end_date) {
            $AND_date_clause = "AND IF (`deleted_date` IS NOT NULL, `deleted_date` BETWEEN ? AND ?, `created_date` BETWEEN ? AND ?)";
            $constraints[] = $start_date;
            $constraints[] = $end_date;
            $constraints[] = $start_date;
            $constraints[] = $end_date;
        } else if ($start_date && !$end_date) {
            $AND_date_clause = "AND IF (`deleted_date` IS NOT NULL, `deleted_date` > ?, `created_date` > ?)";
            $constraints[] = $start_date;
            $constraints[] = $start_date;
        } else if (!$start_date && $end_date) {
            $AND_date_clause = "AND IF (`deleted_date` IS NOT NULL, `deleted_date` < ?, `created_date` < ?)";
            $constraints[] = $end_date;
            $constraints[] = $end_date;
        }
        $query = "SELECT * 
                  FROM `cbl_learner_objectives_completion`
                  WHERE `proxy_id` = ?
                  AND `course_id` = ?
                  AND `objective_id` = ?
                  $AND_date_clause
                  ORDER BY `created_date` DESC";
        $results = $db->GetAll($query, $constraints);
        if ($results) {
            foreach ($results as $result) {
                $output[] = new Models_Objective_Completion($result);
            }
        }
        return $output;
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "lo_completion_id", "method" => ">=", "value" => 0)));
    }

    public function delete() {
        if (empty($this->deleted_date)) {
            $this->deleted_date = time();
        }

        return $this->update();
    }

    public static function setObjectiveAsCompleted($objective_id, $course_id, $proxy_id, $ccmember_proxy_id, $reason = "") {
        global $db;

        // Check if it's completed already
        $query = "SELECT count(*) 
                  FROM `cbl_learner_objectives_completion` 
                  WHERE `proxy_id` = ?
                  AND `course_id` = ?
                  AND `objective_id` = ?
                  AND deleted_date IS NULL";

        $completed = intval($db->getOne($query, array($proxy_id, $course_id, $objective_id)));

        if (!$completed) {
            $self = new self();
            $self->fromArray(array(
                "proxy_id" => $proxy_id,
                "course_id" => $course_id,
                "objective_id" => $objective_id,
                "created_date" => time(),
                "created_by" => $ccmember_proxy_id,
                "created_reason" => $reason
            ));

            return $self->insert();
        }

        return true;
    }

    public static function setObjectiveAsInComplete($objective_id, $course_id, $proxy_id, $ccmember_proxy_id, $reason) {
        global $db;

        // Check if it's completed already
        $query = "SELECT `lo_completion_id` 
                  FROM `cbl_learner_objectives_completion` 
                  WHERE `proxy_id` = ?
                  AND `course_id` = ?
                  AND `objective_id` = ?
                  AND deleted_date IS NULL";

        $record_id = intval($db->getOne($query, array($proxy_id, $course_id, $objective_id)));

        if (!$record_id) {
            return false;
        }

        if (!$self = self::fetchRowByID($record_id)) {
            return false;
        }

        $self->fromArray(array(
            "deleted_date" => time(),
            "deleted_by" => $ccmember_proxy_id,
            "deleted_reason" => $reason
        ));

        return $self->update();
    }

    /**
     * This function fetches a learners current stage
     * @param int $proxy_id
     * @param int $course_id
     * @param shortname
     * @param int $active
     * @param null $deleted_date
     * @return array
     */
    public function fetchCompletedObjectiveByProxyIDShortname($proxy_id = 0, $course_id, $shortname = "stage", $active = 1, $deleted_date = NULL) {
        global $db;
        $params = array($proxy_id, $course_id, $shortname, $active);
        $record = array();
        $query = "  SELECT a.*, b.*, c.* FROM `global_lu_objective_sets` AS a
                    JOIN `global_lu_objectives` AS b
                    ON a.`objective_set_id` = b.`objective_set_id`
                    JOIN `cbl_learner_objectives_completion` AS c
                    ON b.`objective_id` = c.`objective_id`
                    WHERE c.`proxy_id` = ?
                    AND c.`course_id` = ?
                    AND a.`shortname` = ?
                    AND b.`objective_active` = ?
                    AND a.`deleted_date` IS NULL"
                    . ($deleted_date ? " AND c.`deleted_date` <= ?" : " AND c.`deleted_date` IS NULL")
                    . " ORDER BY b.`objective_order` DESC LIMIT 1";

        if ($deleted_date) {
            $params[] = $deleted_date;
        }

        $result = $db->GetRow($query, $params);
        if ($result) {
            $record = $result;
        }

        return $record;
    }

    /**
     * This function returns the next stage for a learner based on current entries in the cbl_learner_objectives_completion table
     * @param int $objective_id
     * @param string $shortname
     * @param int $active
     * @return array
     */
    public function fetchNextObjectiveToComplete($objective_id = 0, $shortname = "", $active = 1) {
        global $db;
        $record = array();
        $query = "  SELECT a.*, b.* FROM `global_lu_objective_sets` AS a
                    JOIN `global_lu_objectives` AS b
                    ON a.`objective_set_id` = b.`objective_set_id`
                    WHERE a.`shortname` = ?
                    AND b.`objective_id` > ?
                    AND b.`objective_active` = ?
                    ORDER BY b.`objective_order` ASC
                    LIMIT 1";
        $result = $db->GetRow($query, array($shortname, $objective_id, $active));
        if ($result) {
            $record = $result;
        }
        return $record;
    }
}