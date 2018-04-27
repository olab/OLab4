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

class Models_LearnerLevel extends Models_Base {
    protected $level_id, $title, $description, $created_date, $created_by, $updated_date, $updated_by, $deleted_date, $deleted_by;

    protected static $table_name = "global_lu_learner_levels";
    protected static $primary_key = "level_id";
    protected static $default_sort_column = "title";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->level_id;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getDescription() {
        return $this->description;
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

    public function fetchRowByID($level_id) {
        return $this->fetchRow(array(
            array("key" => "level_id", "value" => $level_id, "method" => "=")
        ));
    }

    /**
     * Fetch the learner level that matches the provided title, joining to the organisations table on the org_id provided.
     *
     * @param string $title
     * @param int $organisation_id
     * @param int $deleted_date
     * @return bool|Models_LearnerLevel
     */
    public function fetchRowByTitleOrganisationID($title, $organisation_id, $deleted_date = NULL) {
        global $db;
        $output = false;
        $AND_deleted_date = " AND ll.`deleted_date` " . ($deleted_date ? " IS >= {$deleted_date}" : "IS NULL");

        $query = "SELECT * FROM `{$this::$table_name}` AS ll
                  JOIN `learner_level_organisation` AS llo
                  ON ll.`level_id` = llo.`level_id`
                  WHERE llo.`organisation_id` = ?
                  AND ll.`title` = ?
                  {$AND_deleted_date}";
        $result = $db->GetRow($query, array($organisation_id, $title));

        if ($result) {
            $output = new Models_LearnerLevel($result);
        }
        return $output;
    }

    /**
     * Fetch the learner levels joining to the organisations table on the org_id provided.
     *
     * @param $organisation_id
     * @param null $deleted_date
     * @return bool|Models_LearnerLevel
     */
    public function fetchAllByOrganisationID($organisation_id, $deleted_date = NULL) {
        global $db;
        $AND_deleted_date = " AND ll.`deleted_date` " . ($deleted_date ? " IS >= {$deleted_date}" : "IS NULL");

        $query = "SELECT * FROM `{$this::$table_name}` AS ll
                  JOIN `learner_level_organisation` AS llo
                  ON ll.`level_id` = llo.`level_id`
                  WHERE llo.`organisation_id` = ?
                  {$AND_deleted_date}";
        $result = $db->GetAll($query, array($organisation_id));

        return $result;
    }

}