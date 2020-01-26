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
 * A class to handle recording and fetching statistics related to an assessment
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jordan L <jl250@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Assessment_Statistic extends Models_Base {
    
    protected $assessment_statistic_id, $proxy_id, $created_date, $module, $sub_module, $action, $assessment_id, $distribution_id, $target_id, $progress_id, $prune_after;

    protected static $primary_key = "assessment_statistic_id";
    protected static $table_name = "assessment_statistics";
    protected static $default_sort_column = "created_date";
    
    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->assessment_statistic_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function getModule() {
        return $this->module;
    }

    public function getSubModule() {
        return $this->sub_module;
    }

    public function getAction() {
        return $this->action;
    }

    public function getAssessmentID() {
        return $this->assessment_id;
    }

    public function getDistributionID() {
        return $this->distribution_id;
    }

    public function getTargetID() {
        return $this->target_id;
    }

    public function getProgressID() {
        return $this->progress_id;
    }

    public function getPruneAfter() {
        return $this->prune_after;
    }

    public function fetchRowByID($statistic_id) {
        $self = new self();
        return $self->fetchRow(array("statistic_id" => $statistic_id));
    }

    public function fetchAllByProxyID($proxy_id = NULL) {
        $self = new self();
        $constraints[] = array("mode" => "AND", "key" => "proxy_id", "value" => (is_null($proxy_id) ? $this->proxy_id : $proxy_id), "method" => "=");
        return $self->fetchAll($constraints);
    }

    /**
     * Fetch the latest statistic for the given assessment
     *
     * @param $dassessment_id
     * @return mixed
     */
    public static function fetchRowByAssessmentID($dassessment_id) {
        global $db;
        $query = "SELECT * FROM `assessment_statistics` WHERE `assessment_id` = ? ORDER BY `created_date` DESC LIMIT 1";
        return $db->GetRow($query, array($dassessment_id));
    }

    /**
     * Fetch all records that marks a saving point for a progress record
     *
     * @param $aprogress_id
     * @return mixed
     */
    public static function fetchProgressEndTimes($aprogress_id) {
        global $db;

        $query = "SELECT * 
                  FROM `assessment_statistics`
                  WHERE `progress_id` = ?
                  AND `action` = 'submit'
                  ORDER BY `created_date`";

        return $db->GetAll($query, array($aprogress_id));
    }

    /**
     * Find and return the start time record associated with saving record
     *
     * @param $end_record
     * @return mixed
     */
    public static function getRelatedStartTime($end_record) {
        global $db;

        $params = array(
            $end_record["assessment_id"],
            $end_record["proxy_id"],
            $end_record["target_id"],
            $end_record["created_date"]
        );

        $query = "SELECT *
                  FROM `assessment_statistics`
                  WHERE `action` = 'view'
                  AND `assessment_id` = ?
                  AND `proxy_id` = ?
                  AND `target_id` = ?
                  AND `created_date` < ?
                  ORDER BY `created_date` DESC";

        return $db->GetRow($query, $params);
    }
}