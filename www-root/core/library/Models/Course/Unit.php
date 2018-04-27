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
 * @author Organisation: University of British Columbia
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 */

class Models_Course_Unit extends Models_Base {
    protected $cunit_id, $unit_code, $unit_title, $unit_description, $course_id, $cperiod_id, $week_id, $unit_order, $updated_date, $updated_by, $created_date, $created_by, $deleted_date;

    protected static $table_name = "course_units";
    protected static $primary_key = "cunit_id";
    protected static $default_sort_column = "unit_order";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function __toString() {
        return $this->getUnitText();
    }

    public function getID() {
        return $this->cunit_id;
    }

    public function getCunitID() {
        return $this->cunit_id;
    }

    public function getUnitCode() {
        return $this->unit_code;
    }

    public function getUnitTitle() {
        return $this->unit_title;
    }

    public function getUnitDescription() {
        return $this->unit_description;
    }

    public function getCourseID() {
        return $this->course_id;
    }

    public function getCperiodID() {
        return $this->cperiod_id;
    }

    public function getWeekID() {
        return $this->week_id;
    }

    public function getUnitOrder() {
        return $this->unit_order;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function getUnitText() {
        if ($this->unit_code) {
            return sprintf("%s: %s", $this->unit_code, $this->unit_title);
        } else {
            return $this->unit_title;
        }
    }

    protected static function activeConstraint() {
        return array("key" => "deleted_date", "value" => null, "method" => "IS");
    }

    public static function fetchRowByID($cunit_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => static::$primary_key, "value" => $cunit_id, "method" => "="),
            static::activeConstraint()
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => static::$primary_key, "value" => 0, "method" => ">="),
            static::activeConstraint()
        ));
    }

    public static function fetchAllByWeekID($week_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "week_id", "value" => $week_id, "method" => "="),
            static::activeConstraint()
        ));
    }

    public static function fetchAllByCourseID($course_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "course_id", "value" => $course_id, "method" => "="),
            static::activeConstraint()
        ));
    }

    public static function fetchAllByCourseIDCperiodID($course_id, $cperiod_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "course_id", "value" => $course_id, "method" => "="),
            array("key" => "cperiod_id", "value" => $cperiod_id, "method" => "="),
            static::activeConstraint()
        ));
    }

    public static function getUnitsByCourseID($course_id, $cperiod_id, $offset, $limit, $sort_column, $sort_direction) {
        global $db;

        $new_sort_direction = strtoupper($sort_direction);
        if ($new_sort_direction != "ASC" && $new_sort_direction != "DESC") {
            throw new InvalidArgumentException("Invalid sort direction ".$sort_direction);
        }

        $sort_columns_array = array(
            "order" => "`a`.`unit_order`",
            "title" => "COALESCE(CONCAT(`a`.`unit_code`, ': ', `a`.`unit_title`), `a`.`unit_title`)",
        );
        if (isset($sort_columns_array[$sort_column])) {
            $new_sort_column = $sort_columns_array[$sort_column];
        } else {
            throw new InvalidArgumentException("Invalid sort column ".$sort_column);
        }

        $order_sql = "ORDER BY ".$new_sort_column." ".$new_sort_direction;

        $query = "
            SELECT `a`.*
            FROM `course_units` AS `a`
            WHERE `a`.`course_id` = ?
            AND (`a`.`cperiod_id` = ? OR `a`.`cperiod_id` IS NULL)
            AND `a`.`deleted_date` IS NULL
            {$order_sql}
            LIMIT ?, ?";
        $results = $db->GetAll($query, array($course_id, $cperiod_id, $offset, $limit));
        if ($results === false) {
            throw new Exception("Error fetching units.");
        } else {
            return $results;
        }
    }

    public static function getTotalCourseUnits($course_id, $cperiod_id) {
        global $db;

        $query = "
            SELECT COUNT(*) AS `total_rows`
            FROM `course_units` AS `a`
            WHERE `a`.`course_id` = ?
            AND (`a`.`cperiod_id` = ? OR `a`.`cperiod_id` IS NULL)
            AND `a`.`deleted_date` IS NULL";
        $result = $db->GetRow($query, array($course_id, $cperiod_id));
        if ($result === false) {
            throw new Exception("Error fetching unit count.");
        } else {
            return $result;
        }
    }

    public static function removeAllByIDs(array $ids) {
        global $db, $ENTRADA_USER;
        if ($ids) {
            $query = "
                UPDATE `" . static::$table_name . "`
                SET `deleted_date` = ?,
                `updated_date` = ?,
                `updated_by` = ?
                WHERE `" . static::$primary_key . "` IN (" . implode(", ", $ids) . ")";
            $time = time();
            $user_id = $ENTRADA_USER->getID();
            if ($db->Execute($query, array($time, $time, $user_id))) {
                return $db->Affected_Rows();
            } else {
                throw new Exception("Error removing units");
            }
        } else {
            return 0;
        }
    }

    public function getEvents() {
        global $db;
        $query = "SELECT * FROM `events` WHERE `cunit_id` = ? ORDER BY `event_start` ASC";
        $results = $db->GetAll($query, $this->getID());
        if ($results === false) {
            throw new Exception("Error getting events for unit " . $this->getID());
        } else {
            $output = array();
            foreach ($results as $result) {
                $output[] = new Models_Event($result);
            }
            return $output;
        }
    }

    public function getAssociatedFaculty() {
        global $db;
        $query = "
            SELECT `proxy_id`
            FROM `course_unit_contacts`
            WHERE `cunit_id` = ?
            ORDER BY `contact_order` ASC";
        $results = $db->GetAll($query, array($this->getID()));
        if ($results === false) {
            throw new Exception($db->ErrorMsg());
        } else {
            return array_map(function ($result) { return $result["proxy_id"]; }, $results);
        }
    }

    public function updateAssociatedFaculty(array $new_proxy_ids) {
        global $db, $ENTRADA_USER;
        $db->BeginTrans();
        $delete = "
            DELETE FROM `course_unit_contacts`
            WHERE `cunit_id` = ?";
        if ($db->Execute($delete, array($this->getID())) === false) {
            $db->RollbackTrans();
            throw new Exception($db->ErrorMsg());
        }
        foreach ($new_proxy_ids as $contact_order => $adding_proxy_id) {
            $insert = "
                INSERT INTO `course_unit_contacts`(`cunit_id`, `proxy_id`, `contact_order`, `updated_date`, `updated_by`)
                VALUES (?, ?, ?, UNIX_TIMESTAMP(), ?)";
            $values = array(
                $this->getID(),
                $adding_proxy_id,
                $contact_order,
                $ENTRADA_USER->getID(),
            );
            if ($db->Execute($insert, $values) === false) {
                $db->RollbackTrans();
                throw new Exception($db->ErrorMsg());
            }
        }
        $db->CommitTrans();
    }

    public function getObjectives() {
        $objective_repository = Models_Repository_Objectives::getInstance();
        $objectives_by_course_unit = $objective_repository->fetchAllByCourseUnitIDs(array($this->getID()));
        if (isset($objectives_by_course_unit[$this->getID()])) {
            return $objectives_by_course_unit[$this->getID()];
        } else {
            return array();
        }
    }

    public function updateObjectives(array $objectives) {
        global $db, $ENTRADA_USER;
        $db->BeginTrans();
        $delete = "DELETE FROM `course_unit_objectives` WHERE `cunit_id` = ?";
        if ($db->Execute($delete, array($this->getID())) === false) {
            $db->RollbackTrans();
            throw new Exception($db->ErrorMsg());
        }
        foreach ($objectives as $objective_order => $objective) {
            $insert = "
                INSERT INTO `course_unit_objectives`(`cunit_id`, `objective_id`, `objective_order`, `updated_date`, `updated_by`)
                VALUES (?, ?, ?, UNIX_TIMESTAMP(), ?)";
            $values = array(
                $this->getID(),
                $objective->getID(),
                $objective_order,
                $ENTRADA_USER->getID(),
            );
            if ($db->Execute($insert, $values) === false) {
                $db->RollbackTrans();
                throw new Exception($db->ErrorMsg());
            }
        }
        $db->CommitTrans();
    }

    public function getCurriculumMapVersion() {
        $version_repository = Models_Repository_CurriculumMapVersions::getInstance();
        $versions = $version_repository->fetchVersionsByCourseIDCperiodID($this->getCourseID(), $this->getCperiodID());
        if ($versions) {
            $first = current($versions);
            return $first;
        } else {
            return null;
        }
    }

    /**
     * @return array[from_objective_id][to_objective_id] = Models_Objective
     */
    public function getLinkedObjectives($version_id, $objectives) {
        $objective_ids = array_map(function (Models_Objective $objective) { return $objective->getID(); }, $objectives);
        $context = new Entrada_Curriculum_Context_Specific_CourseUnit($this->getID());
        $objective_repository = Models_Repository_Objectives::getInstance();
        $objectives_by_version = $objective_repository->fetchLinkedObjectivesByIDs("from", $objective_ids, $version_id, $context);

        // Flatten the array by one level to return linked objectives for this version only
        return $objective_repository->flatten($objectives_by_version);
    }

    public function updateLinkedObjectives(array $objectives, array $linked_objectives, $version_id) {
        $context = new Entrada_Curriculum_Context_Specific_CourseUnit($this->getID());
        return Models_Repository_Objectives::getInstance()->updateLinkedObjectives($objectives, $linked_objectives, $version_id, $context);
    }

    public function getAllowedLinkedObjectives() {
        $objective_repo = Models_Repository_Objectives::getInstance();
        $course_objectives = $objective_repo->flatten($objective_repo->fetchAllByCourseIDsAndCperiodID(array($this->getCourseID()), $this->cperiod_id));
        return $course_objectives;
    }

    public function getAllowedLinkedObjectiveIDs() {
        $objective_repo = Models_Repository_Objectives::getInstance();
        return $objective_repo->groupIDsByTagSet($this->getAllowedLinkedObjectives());
    }

    public function fetchMyUnits() {
        global $db, $ENTRADA_USER;

        $time = time();
        $proxy_id = $ENTRADA_USER->getID();

        if ($ENTRADA_USER->getCohort()) {
            $cohort_subquery = "AND ca2.audience_value = ".$ENTRADA_USER->getCohort();
        } else {
            $cohort_subquery = "";
        }

        $query = "
            SELECT
                t.`curriculum_type_name`,
                w.`week_title`,
                c.`course_code`,
                cu.*,
                COALESCE(ca1.`audience_value`, gm.`proxy_id`) AS `proxy_id`
            FROM `course_units` AS cu
            LEFT JOIN `weeks` AS w ON w.`week_id` = cu.`week_id` AND w.`deleted_date` IS NULL
            INNER JOIN `courses` AS c ON c.`course_id` = cu.`course_id` AND c.`course_active` = 1
            INNER JOIN `curriculum_periods` AS p ON p.`cperiod_id` = cu.`cperiod_id` AND p.`active` = 1
            INNER JOIN `curriculum_lu_types` AS t ON t.`curriculum_type_id` = p.`curriculum_type_id`
            LEFT JOIN `course_audience` AS ca1
                ON ca1.`course_id` = c.`course_id`
                AND ca1.`audience_type` = 'proxy_id'
                AND ca1.`cperiod_id` = p.`cperiod_id`
                AND ca1.`audience_value` = ".$db->qstr($proxy_id)."
                AND ca1.`audience_active` = 1
                AND ".$db->qstr($time)." BETWEEN p.`start_date` AND p.`finish_date`
            LEFT JOIN `course_audience` AS ca2
                ON ca2.`course_id` = c.`course_id`
                AND ca2.`audience_type` = 'group_id'
                AND ca2.`cperiod_id` = p.`cperiod_id`
                AND ca2.`audience_active` = 1
                AND ca1.`caudience_id` IS NULL
            LEFT JOIN `groups` g
                ON g.`group_id` = ca2.`audience_value`
                AND (g.`start_date` <= ".$db->qstr($time)." OR g.`start_date` = 0 OR g.`start_date` IS NULL)
                AND (g.`expire_date` >= ".$db->qstr($time)." OR g.`expire_date` = 0 OR g.`expire_date` IS NULL)
                AND g.`group_active` = 1
            LEFT JOIN `group_members` gm ON gm.`group_id` = g.`group_id` AND gm.`proxy_id` = ".$db->qstr($proxy_id)."
            WHERE cu.`deleted_date` IS NULL
            AND (
                (ca1.`caudience_id` IS NOT NULL AND ca2.`caudience_id` IS NULL) OR
                (ca1.`caudience_id` IS NULL AND gm.`proxy_id` IS NOT NULL) OR
                (ca1.`caudience_id` IS NULL AND gm.`proxy_id` IS NULL AND ".$db->qstr($time)." BETWEEN p.`start_date` AND p.`finish_date`)
            )
            $cohort_subquery
            GROUP BY t.`curriculum_type_name`, w.`week_title`, c.`course_code`, cu.`cunit_id`, `proxy_id`
            ORDER BY t.`curriculum_type_name`, w.`week_order`, c.`course_code`, cu.`unit_order`, `proxy_id`";
        $results = $db->GetAll($query);

        if ($results === false) {
            application_log("error", "Could not query units, DB said: ".$db->ErrorMsg());
            throw new Exception("Could not query units");
        }

        $units = array();

        foreach ($results as $result) {
            $units[$result["curriculum_type_name"]][$result["week_title"]][$result["course_code"]] = new self($result);
        }

        return $units;
    }

    public function getByCohort($cohort_id) {
        global $db, $ENTRADA_USER;

        $time = time();
        $proxy_id = $ENTRADA_USER->getID();

        if ($cohort_id) {
            $cohort_subquery = "AND ca2.audience_value = ".$cohort_id;
        } else {
            $cohort_subquery = "";
        }

        $query = "
            SELECT
                t.`curriculum_type_name`,
                w.`week_title`,
                c.`course_code`,
                cu.*,                
                p.curriculum_period_title                
            FROM `course_units` AS cu
            LEFT JOIN `weeks` AS w ON w.`week_id` = cu.`week_id` AND w.`deleted_date` IS NULL
            INNER JOIN `courses` AS c ON c.`course_id` = cu.`course_id` AND c.`course_active` = 1
            INNER JOIN `curriculum_periods` AS p ON p.`cperiod_id` = cu.`cperiod_id` AND p.`active` = 1
            INNER JOIN `curriculum_lu_types` AS t ON t.`curriculum_type_id` = p.`curriculum_type_id`
            LEFT JOIN `course_audience` AS ca1
                ON ca1.`course_id` = c.`course_id`
                AND ca1.`audience_type` = 'proxy_id'
                AND ca1.`cperiod_id` = p.`cperiod_id`
                AND ca1.`audience_value` = ".$db->qstr($proxy_id)."
                AND ca1.`audience_active` = 1                
            LEFT JOIN `course_audience` AS ca2
                ON ca2.`course_id` = c.`course_id`
                AND ca2.`audience_type` = 'group_id'
                AND ca2.`cperiod_id` = p.`cperiod_id`
                AND ca2.`audience_active` = 1
                AND ca1.`caudience_id` IS NULL
            LEFT JOIN `groups` g
                ON g.`group_id` = ca2.`audience_value`                
                AND g.`group_active` = 1
            LEFT JOIN `group_members` gm ON gm.`group_id` = g.`group_id` AND gm.`proxy_id` = ".$db->qstr($proxy_id)."
            WHERE cu.`deleted_date` IS NULL
            AND (
                (ca1.`caudience_id` IS NOT NULL AND ca2.`caudience_id` IS NULL) OR
                (ca1.`caudience_id` IS NULL AND gm.`proxy_id` IS NOT NULL) OR
                (ca1.`caudience_id` IS NULL AND gm.`proxy_id` IS NULL)
            )
            $cohort_subquery
            GROUP BY t.`curriculum_type_name`, w.`week_title`, c.`course_code`, cu.`cunit_id`, `proxy_id`
            ORDER BY t.`curriculum_type_name`, w.`week_order`, c.`course_code`, cu.`unit_order`, `proxy_id`";

        $results = $db->GetAll($query);

        if ($results === false) {
            application_log("error", "Could not query units, DB said: ".$db->ErrorMsg());
            throw new Exception("Could not query units");
        }

        $units = array();

        foreach ($results as $result) {
            $units[$result["curriculum_type_name"]][$result["week_title"]][$result["course_code"]] = new self($result);
        }

        return $units;
    }
}
