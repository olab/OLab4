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

class Models_LearnerStatus extends Models_Base {
    protected $status_id, $title, $description, $percent_active, $created_date, $created_by, $updated_date, $updated_by, $deleted_date, $deleted_by;

    protected static $table_name = "global_lu_learner_statuses";
    protected static $primary_key = "status_id";
    protected static $default_sort_column = "title";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->status_id;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getPercentActive() {
        return $this->percent_active;
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
     * Fetch the learner status that matches the provided title, joining to the organisations table on the org_id provided.
     *
     * @param $title
     * @param $organisation_id
     * @param null $deleted_date
     * @return bool|Models_LearnerStatus
     */
    public function fetchRowByTitleOrganisationID($title, $organisation_id, $deleted_date = NULL) {
        global $db;
        $output = false;
        $AND_deleted_date = " AND ls.`deleted_date` " . ($deleted_date ? " IS >= {$deleted_date}" : "IS NULL");

        $query = "SELECT * FROM `{$this::$table_name}` AS ls
                  JOIN `learner_status_organisation` AS lso
                  ON ls.`status_id` = lso.`status_id`
                  WHERE lso.`organisation_id` = ?
                  AND ls.`title` = ?
                  {$AND_deleted_date}";
        $result = $db->GetRow($query, array($organisation_id, $title));

        if ($result) {
            $output = new Models_LearnerStatus($result);

        }
        return $output;
    }

}