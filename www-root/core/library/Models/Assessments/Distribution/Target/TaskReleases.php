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
 * 
 *
 * @author Organisation: Queen's University
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Distribution_Target_TaskReleases extends Models_Base {

    protected $adt_task_release_id;
    protected $adistribution_id;
    protected $target_option;
    protected $unique_targets;
    protected $percent_threshold;
    protected $created_date;
    protected $created_by;
    protected $updated_date;
    protected $updated_by;
    protected $deleted_date;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "cbl_assessment_distribution_target_task_releases";
    protected static $primary_key = "adt_task_release_id";
    protected static $default_sort_column = "adt_task_release_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->adt_task_release_id;
    }

    public function getAdtTaskReleaseID() {
        return $this->adt_task_release_id;
    }

    public function setAdtTaskReleaseID($adt_task_release_id) {
        $this->adt_task_release_id = $adt_task_release_id;

        return $this;
    }

    public function getAdistributionID() {
        return $this->adistribution_id;
    }

    public function setAdistributionID($adistribution_id) {
        $this->adistribution_id = $adistribution_id;

        return $this;
    }

    public function getTargetOption() {
        return $this->target_option;
    }

    public function setTargetOption($target_option) {
        $this->target_option = $target_option;

        return $this;
    }

    public function getUniqueTargets() {
        return $this->unique_targets;
    }

    public function setUniqueTargets($unique_targets) {
        $this->unique_targets = $unique_targets;

        return $this;
    }

    public function getPercentThreshold() {
        return $this->percent_threshold;
    }

    public function setPercentThreshold($percent_threshold) {
        $this->percent_threshold = $percent_threshold;

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

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;

        return $this;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;

        return $this;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;

        return $this;
    }

    public static function fetchRowByID($adt_task_release_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "adt_task_release_id", "method" => "=", "value" => $adt_task_release_id)
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "adt_task_release_id", "method" => ">=", "value" => 0)));
    }

    public function fetchAllByADistributionID($adistribution_id, $deleted_date = null) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "adistribution_id", "method" => "=", "value" => $adistribution_id),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : null), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public function fetchRowByADistributionID($adistribution_id, $deleted_date = null) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "adistribution_id", "method" => "=", "value" => $adistribution_id),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : null), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public function delete() {
        if (empty($this->deleted_date)) {
            $this->deleted_date = time();
        }

        return $this->update();
    }

}