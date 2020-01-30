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
 * A model for handling existing assessment snapshots
 *
 * @author Organisation: Queen's University
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2018 Queen's University. All Rights Reserved.
 */

class Models_Assessments_ExistingTaskSnapshot extends Models_Base {

    protected $existing_task_id;
    protected $adistribution_id;
    protected $distribution_deleted_date;
    protected $distribution_title;
    protected $assessor_name;
    protected $target_name;
    protected $form_title;
    protected $schedule_details;
    protected $progress_details;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "cbl_assessment_ss_existing_tasks";
    protected static $primary_key = "existing_task_id";
    protected static $default_sort_column = "adistribution_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->existing_task_id;
    }

    public function getExistingTaskID() {
        return $this->existing_task_id;
    }

    public function setExistingTaskID($existing_task_id) {
        $this->existing_task_id = $existing_task_id;

        return $this;
    }

    public function getAdistributionID() {
        return $this->adistribution_id;
    }

    public function setAdistributionID($adistribution_id) {
        $this->adistribution_id = $adistribution_id;

        return $this;
    }

    public function getDistributionDeletedDate() {
        return $this->distribution_deleted_date;
    }

    public function setDistributionDeletedDate($distribution_deleted_date) {
        $this->distribution_deleted_date = $distribution_deleted_date;

        return $this;
    }

    public function getDistributionTitle() {
        return $this->distribution_title;
    }

    public function setDistributionTitle($distribution_title) {
        $this->distribution_title = $distribution_title;

        return $this;
    }

    public function getAssessorName() {
        return $this->assessor_name;
    }

    public function setAssessorName($assessor_name) {
        $this->assessor_name = $assessor_name;

        return $this;
    }

    public function getTargetName() {
        return $this->target_name;
    }

    public function setTargetName($target_name) {
        $this->target_name = $target_name;

        return $this;
    }

    public function getFormTitle() {
        return $this->form_title;
    }

    public function setFormTitle($form_title) {
        $this->form_title = $form_title;

        return $this;
    }

    public function getScheduleDetails() {
        return $this->schedule_details;
    }

    public function setScheduleDetails($schedule_details) {
        $this->schedule_details = $schedule_details;

        return $this;
    }

    public function getProgressDetails() {
        return $this->progress_details;
    }

    public function setProgressDetails($progress_details) {
        $this->progress_details = $progress_details;

        return $this;
    }

    public static function fetchRowByID($existing_task_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "existing_task_id", "method" => "=", "value" => $existing_task_id)
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "existing_task_id", "method" => ">=", "value" => 0)));
    }

    public static function truncate() {
        global $db;
        $query = "TRUNCATE `" . static::$table_name . "`";
        if (!$db->Execute($query)) {
            application_log("error", "Unable to truncate " . static::$table_name . ". DB said: " . $db->ErrorMsg());
        }
    }

    public function fetchAllByADistributionIDs($adistribution_ids) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "adistribution_id", "method" => "IN", "value" => $adistribution_ids)));
    }

}