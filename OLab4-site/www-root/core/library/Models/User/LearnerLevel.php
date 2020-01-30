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
class Models_User_LearnerLevel extends Models_Base {
    protected $user_learner_level_id, $proxy_id, $level_id, $seniority, $course_id, $cbme, $stage_objective_id, $cperiod_id, $start_date, $finish_date, $status_id, $active, $notes,
        $created_date, $created_by, $updated_date, $updated_by, $updated_by_type, $deleted_date, $deleted_by;

    protected static $table_name = "user_learner_levels";
    protected static $primary_key = "user_learner_level_id";
    protected static $default_sort_column = "proxy_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->user_learner_level_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getLevelID() {
        return $this->level_id;
    }

    public function getSeniority() {
        return $this->seniority;
    }

    public function getCBME() {
        return $this->cbme;
    }

    public function getCourseID() {
        return $this->course_id;
    }

    public function getStageObjectiveID() {
        return $this->stage_objective_id;
    }

    public function getCPeriodID() {
        return $this->cperiod_id;
    }

    public function getStartDate() {
        return $this->start_date;
    }

    public function getFinishDate() {
        return $this->finish_date;
    }

    public function getStatusID() {
        return $this->status_id;
    }

    public function getActive() {
        return $this->active;
    }

    public function getNotes() {
        return $this->notes;
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

    public function getUpdatedByType() {
        return $this->updated_by_type;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function fetchAllByProxyID($proxy_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "proxy_id", "value" => $proxy_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public function fetchAllByProxyIDOrderStartDateASC($proxy_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "proxy_id", "value" => $proxy_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ), "=", "AND", "start_date", "ASC");
    }

    /**
     * Fetch all relevant data belonging to the proxy_id's levels joining to the organisations table, learner levels,
     * and learner statuses as necessary.
     *
     * @param int $proxy_id
     * @param int $organisation_id
     * @param int $deleted_date
     * @return bool|array Models_User_LearnerLevel
     */
    public function fetchAllDataByProxyIDOrganisationID($proxy_id, $organisation_id, $deleted_date = NULL) {
        global $db;
        $AND_deleted_date = " AND ull.`deleted_date` " . ($deleted_date ? " IS >= {$deleted_date}" : "IS NULL");

        $query = "SELECT  ull.*, 
                          '' AS `stage_title`, 
                          glll.`title` AS `level_title`,
                          glls.`title` AS `status_title`
                  FROM `{$this::$table_name}` AS ull
                  JOIN `learner_level_organisation` AS llo
                  ON ull.`level_id` = llo.`level_id`
                  JOIN `global_lu_learner_levels` AS glll      
                  ON glll.`level_id` = ull.`level_id`
                  JOIN `global_lu_learner_statuses` AS glls 
                  ON glls.`status_id` = ull.`status_id`
                  WHERE llo.`organisation_id` = ?
                  AND ull.`proxy_id` = ?
                  {$AND_deleted_date}
                  ORDER BY ull.`start_date` DESC, ull.`finish_date` DESC";
        $results = $db->GetAll($query, array($organisation_id, $proxy_id));

        return $results;
    }

    /**
     * Fetch the active learner level for this proxy_id.
     *
     * @param int $proxy_id
     * @param bool $active
     * @return bool|Models_Base
     */
    public function fetchRowByProxyIDActive($proxy_id, $active = true) {
        return $this->fetchRow(array(
            array("key" => "proxy_id", "value" => $proxy_id, "method" => "="),
            array("key" => "active", "value" => $active, "method" => "=")
        ));
    }

    /**
     * Fetch all relevant data belonging to the proxy_id's levels joining to the organisations table, learner levels,
     * and learner statuses as necessary. This is contrained by the provided start and end dates.
     *
     * @param int $proxy_id
     * @param int $organisation_id
     * @param int $deleted_date
     * @param int $start_date
     * @param int $end_date
     * @return bool|array Models_User_LearnerLevel
     */
    public function fetchAllByProxyIDOrganisationIDDateRange($proxy_id, $organisation_id, $start_date, $end_date, $deleted_date = NULL) {
        global $db;
        $AND_deleted_date = " AND ull.`deleted_date` " . ($deleted_date ? " IS >= {$deleted_date}" : "IS NULL");

        $query = "SELECT  ull.*, 
                          '' AS `stage_title`, 
                          glll.`title` AS `level_title`,
                          glls.`title` AS `status_title`
                  FROM `{$this::$table_name}` AS ull
                  JOIN `learner_level_organisation` AS llo
                  ON ull.`level_id` = llo.`level_id`
                  JOIN `global_lu_learner_levels` AS glll      
                  ON glll.`level_id` = ull.`level_id`
                  JOIN `global_lu_learner_statuses` AS glls 
                  ON glls.`status_id` = ull.`status_id`
                  WHERE llo.`organisation_id` = ?
                  AND ull.`proxy_id` = ?
                  AND (
                        (ull.`start_date` >= ? AND ull.`finish_date` <= ?) OR
                        ((ull.`start_date` >= ? AND ull.`start_date` <= ?) AND ull.`finish_date` >= ?) OR
                        (ull.`start_date` <= ? AND (ull.`finish_date` <= ? AND ull.`finish_date` >= ?)) OR
                        (ull.`start_date` <= ? AND ull.`finish_date` >= ?)
                      )
                  {$AND_deleted_date}
                  ORDER BY ull.`start_date` DESC, ull.`finish_date` DESC";
        $results = $db->GetAll($query, array(
            $organisation_id, $proxy_id,
            $start_date, $end_date,
            $start_date, $end_date, $end_date,
            $start_date, $end_date, $start_date,
            $start_date, $end_date
        ));

        return $results;
    }

    public function fetchAllByProxyIDOrganisationIDCourseIDDateCBME($proxy_id, $organisation_id, $course_id, $date, $cbme = NULL, $deleted_date = NULL) {
        global $db;
        $AND_deleted_date = " AND ull.`deleted_date` " . ($deleted_date ? " IS >= {$deleted_date}" : "IS NULL");
        $AND_cbme = isset($cbme) ? " AND ull.`cbme` = {$cbme}" : "";

        $query = "SELECT  ull.*, 
                          '' AS `stage_title`, 
                          glll.`title` AS `level_title`,
                          glls.`title` AS `status_title`
                  FROM `{$this::$table_name}` AS ull
                  JOIN `learner_level_organisation` AS llo
                  ON ull.`level_id` = llo.`level_id`
                  JOIN `global_lu_learner_levels` AS glll      
                  ON glll.`level_id` = ull.`level_id`
                  JOIN `global_lu_learner_statuses` AS glls 
                  ON glls.`status_id` = ull.`status_id`
                  WHERE llo.`organisation_id` = ?
                  AND ull.`proxy_id` = ?
                  AND ? BETWEEN ull.`start_date` AND ull.`finish_date`
                  AND ull.`course_id` = ?
                  {$AND_cbme}
                  {$AND_deleted_date}
                  ORDER BY ull.`start_date` DESC, ull.`finish_date` DESC";

        $results = $db->GetAll($query, array($organisation_id, $proxy_id, $date, $course_id));

        return $results;
    }

    /**
     * Fetch active learner level for the specified proxy_id and organisation_id.
     *
     * @param $proxy_id
     * @param $organisation_id
     * @param null $deleted_date
     * @return bool|array
     */
    public function fetchActiveLevelInfoByProxyIDOrganisationID($proxy_id, $organisation_id, $deleted_date = NULL) {
        global $db;
        $AND_deleted_date = " AND ull.`deleted_date` " . ($deleted_date ? " IS >= {$deleted_date}" : "IS NULL");

        $query = "SELECT * FROM `{$this::$table_name}` AS ull
                  JOIN `learner_level_organisation` AS llo
                  ON ull.`level_id` = llo.`level_id`
                  JOIN `global_lu_learner_levels` AS glll 
                  ON ull.`level_id` = glll.`level_id`
                  WHERE ull.`proxy_id` = ?
                  AND llo.`organisation_id` = ?
                  AND ull.`active` = 1
                  {$AND_deleted_date}";
        $result = $db->GetRow($query, array($proxy_id, $organisation_id));

        return $result;
    }

}