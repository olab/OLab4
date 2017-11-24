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
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Distribution_Schedule extends Models_Base {
    protected $adschedule_id, $adistribution_id, $addelegator_id, $schedule_type, $period_offset, $delivery_period, $schedule_id, $frequency, $start_date, $end_date;

    protected static $table_name = "cbl_assessment_distribution_schedule";
    protected static $primary_key = "adschedule_id";
    protected static $default_sort_column = "adschedule_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->adschedule_id;
    }

    public function getAdscheduleID() {
        return $this->adschedule_id;
    }

    public function getAdistributionID() {
        return $this->adistribution_id;
    }

    public function getAddelegatorID() {
        return $this->addelegator_id;
    }

    public function getScheduleType() {
        return $this->schedule_type;
    }

    public function getPeriodOffset() {
        return $this->period_offset;
    }

    public function getDeliveryPeriod() {
        return $this->delivery_period;
    }

    public function getScheduleID() {
        return $this->schedule_id;
    }

    public function getFrequency() {
        return $this->frequency;
    }

    public function getStartDate() {
        return $this->start_date;
    }

    public function getEndDate() {
        return $this->end_date;
    }

    public static function fetchRowByID($adschedule_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "adschedule_id", "value" => $adschedule_id, "method" => "=")
        ));
    }

    public static function fetchRowByDistributionID($adistribution_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "adschedule_id", "value" => 0, "method" => ">=")));
    }

    public static function fetchAllByScheduleID($schedule_id) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "schedule_id", "value" => $schedule_id, "method" => "=")));
    }

    public function delete() {
        global $db;
        if ($db->Execute("DELETE FROM `".static::$table_name."` WHERE `".static::$primary_key."` = ".$this->getID())) {
            return $this;
        } else {
            application_log("error", "Error deleting  ".get_called_class()." id[" . $this->{static::$primary_key} . "]. DB Said: " . $db->ErrorMsg());
            return false;
        }
    }
}