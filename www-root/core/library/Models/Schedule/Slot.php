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

class Models_Schedule_Slot extends Models_Base {

    protected $schedule_slot_id;
    protected $schedule_id;
    protected $slot_type_id;
    protected $slot_min_spaces = 0;
    protected $slot_spaces;
    protected $strict_spaces = 0;
    protected $course_id;
    protected $created_date;
    protected $created_by;
    protected $deleted_date;
    protected $updated_date;
    protected $updated_by;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "cbl_schedule_slots";
    protected static $primary_key = "schedule_slot_id";
    protected static $default_sort_column = "schedule_slot_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->schedule_slot_id;
    }

    public function getScheduleSlotID() {
        return $this->schedule_slot_id;
    }

    public function setScheduleSlotID($schedule_slot_id) {
        $this->schedule_slot_id = $schedule_slot_id;

        return $this;
    }

    public function getScheduleID() {
        return $this->schedule_id;
    }

    public function setScheduleID($schedule_id) {
        $this->schedule_id = $schedule_id;

        return $this;
    }

    public function getSlotTypeID() {
        return $this->slot_type_id;
    }

    public function setSlotTypeID($slot_type_id) {
        $this->slot_type_id = $slot_type_id;

        return $this;
    }

    public function getSlotMinSpaces() {
        return $this->slot_spaces;
    }

    public function setSlotMinSpaces($slot_min_spaces) {
        $this->slot_min_spaces = $slot_min_spaces;

        return $this;
    }

    public function getSlotSpaces() {
        return $this->slot_spaces;
    }

    public function setSlotSpaces($slot_spaces) {
        $this->slot_spaces = $slot_spaces;

        return $this;
    }

    public function getStrictSpaces() {
        return $this->strict_spaces;
    }

    public function setStrictSpaces($strict_spaces) {
        $this->strict_spaces = $strict_spaces;

        return $this;
    }

    public function getCourseID() {
        return $this->course_id;
    }

    public function setCourseID($course_id) {
        $this->course_id = $course_id;

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


    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;

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

    public function getSlotType() {
        global $db;
        $query = "SELECT * FROM `cbl_schedule_slot_types` WHERE `slot_type_id` = ?";
        return $db->GetRow($query, $this->slot_type_id);
    }

    public static function getSlotTypes() {
        global $db;
        $query = "SELECT * FROM `cbl_schedule_slot_types`";
        return $db->GetAll($query);
    }

    public static function fetchSlotTypeIDByCode($slot_type_code) {
        global $db;
        $query = "SELECT `slot_type_id` FROM `cbl_schedule_slot_types` WHERE `slot_type_code` = ?";
        return $db->GetOne($query, ($slot_type_code));
    }

    public function getAudience() {
        return Models_Schedule_Audience::fetchAllBySlotID($this->schedule_slot_id);
    }

    public static function fetchRowByID($schedule_slot_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "schedule_slot_id", "value" => $schedule_slot_id, "method" => "="),
            array("key" => "deleted_date", "value" => NULL, "method" => "IS"),
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "deleted_date", "value" => NULL, "method" => "IS"),));
    }

    public static function fetchAllByScheduleID($schedule_id, $slot_type_id = NULL) {
        $self = new self();

        $params = array(
            array("key" => "schedule_id", "value" => $schedule_id, "method" => "="),
            array("key" => "deleted_date", "value" => NULL, "method" => "IS")
        );

        if (!is_null($slot_type_id)) {
            $params[] = array("key" => "slot_type_id", "value" => $slot_type_id, "method" => "=");
        }

        return $self->fetchAll($params);
    }

    public static function fetchRandomSlotID($schedule_id, $proxy_id) {
        global $db;

        $current_slots_query = "SELECT `start_date`, `end_date`
                                    FROM `cbl_schedule_audience` AS a
                                    JOIN `cbl_schedule` AS b ON a.`schedule_id` = b.`schedule_id`
                                    WHERE a.`audience_value` = ?";
        $current_slots = $db->GetAll($current_slots_query, array($proxy_id));

        $query = "SELECT a.`schedule_slot_id`, a.`schedule_id`, a.`slot_spaces`, COUNT(c.`audience_value`) AS `current_audience`, b.`start_date`, b.`end_date`
                    FROM `cbl_schedule_slots` AS a
                    JOIN `cbl_schedule` AS b
                    ON a.`schedule_id` = b.`schedule_id`
                    LEFT JOIN `cbl_schedule_audience` AS c
                    ON a.`schedule_slot_id` = c.`schedule_slot_id`
                    WHERE b.`schedule_parent_id` = ?
                    GROUP BY a.`schedule_slot_id`
                    HAVING `current_audience` < a.`slot_spaces`
                    ORDER BY RAND()";
        $results = $db->GetAll($query, array($schedule_id));
        if ($results) {
            foreach ($results as $key => $result) {
                foreach ($current_slots as $slot) {
                    if ($result["start_date"] >= $slot["start_date"] && $result["end_date"] <= $slot["end_date"]) {
                        unset($results[$key]);
                        break;
                    }
                }
            }
        }

        $return = array_values($results);
        $return = $return[0];
        return $return;
    }

    public static function fetchRowByScheduleID($schedule_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "schedule_id", "value" => $schedule_id, "method" => "=")
        ));
    }

    public static function addAllSlots($values) {
        global $db;

        $query = "INSERT INTO `" . DATABASE_NAME . "`.`cbl_schedule_slots` (`schedule_slot_id`, `schedule_id`, `slot_type_id`, `slot_min_spaces`, `slot_spaces`,
                  `strict_spaces`, `course_id`, `created_date`, `created_by`, `deleted_date`, `updated_date`, `updated_by`) VALUES " . $values;

        $db->Execute($query);
    }

    public function createValueString() {
        $values_array = array();

        $values_array[] = "null";
        $values_array[] = $this->schedule_id ? $this->schedule_id : "null";
        $values_array[] = $this->slot_type_id ? $this->slot_type_id : "null";
        $values_array[] = $this->slot_min_spaces ? $this->slot_min_spaces : "null";
        $values_array[] = $this->slot_spaces ? $this->slot_spaces : "null";
        $values_array[] = $this->strict_spaces ? $this->strict_spaces : "null";
        $values_array[] = $this->course_id ? $this->course_id : "null";
        $values_array[] = $this->created_date ? $this->created_date : "null";
        $values_array[] = $this->created_by ? $this->created_by : "null";
        $values_array[] = $this->deleted_date ? $this->deleted_date : "null";
        $values_array[] = $this->updated_date ? $this->updated_date : "null";
        $values_array[] = $this->updated_by ? $this->updated_by : "null";

        return "(" . implode($values_array, ",") . ")";
    }

    public function delete() {
        if (empty($this->deleted_date)) {
            $this->deleted_date = time();
        }

        return $this->update();
    }
}