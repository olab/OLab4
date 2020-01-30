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
 * Model for handling report cache file tracking.
 *
 * @author Organisation: Queen's University
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Report_Caches extends Models_Base {
    protected $arcache_id, $report_key, $target_type, $target_value, $created_date, $created_by, $report_param_hash, $proxy_id, $report_meta_hash;

    protected static $table_name = "cbl_assessment_report_caches";
    protected static $primary_key = "arcache_id";
    protected static $default_sort_column = "arcache_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->arcache_id;
    }

    public function getArcacheID() {
        return $this->arcache_id;
    }

    public function getReportKey() {
        return $this->report_key;
    }

    public function getTargetType() {
        return $this->target_type;
    }

    public function getReportParamHash() {
        return $this->report_param_hash;
    }

    public function getReportMetaHash() {
        return $this->report_meta_hash;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getTargetValue() {
        return $this->target_value;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function deleteAllByTargetTypeTargetValueReportMetaHash($target_type, $target_value, $meta_hash) {
        global $db;
        $sql = "DELETE FROM `cbl_assessment_report_caches` WHERE `target_type` = ? AND `target_value` = ? AND `report_meta_hash` = ?";
        $db->query($sql, array($target_type, $target_value, $meta_hash));
    }

    public function deleteAllByTargetTypeTargetValueReportParamHash($target_type, $target_value, $report_param_hash) {
        global $db;
        $sql = "DELETE FROM `cbl_assessment_report_caches` WHERE `target_type` = ? AND `target_value` = ? AND `report_param_hash` = ?";
        $db->query($sql, array($target_type, $target_value, $report_param_hash));
    }

    public function fetchRowByTargetTypeTargetValueReportMetaHash($target_type, $target_value, $meta_hash) {
        return $this->fetchRow(array(
            array("key" => "target_type", "value" => $target_type, "method" => "="),
            array("key" => "target_value", "value" => $target_value, "method" => "="),
            array("key" => "report_meta_hash", "value" => $meta_hash, "method" => "=")
        ));
    }

    public function fetchRowByID($arcache_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "arcache_id", "value" => $arcache_id, "method" => "=")
        ));
    }

    public function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "arcache_id", "value" => 0, "method" => ">=")));
    }
}