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
 * A model for handling disclaimer triggers
 *
 * @author Organisation: Queens University
 * @author Developer: Jonatan Caraballo <jch9@queensu.ca>
 * @copyright Copyright 2017 Queens University. All Rights Reserved.
 */

class Models_Disclaimer_Trigger extends Models_Base {

    protected $disclaimer_trigger_id;
    protected $disclaimer_id;
    protected $disclaimer_trigger_type;
    protected $disclaimer_trigger_value;
    protected $updated_date;
    protected $updated_by;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "disclaimer_trigger";
    protected static $primary_key = "disclaimer_trigger_id";
    protected static $default_sort_column = "disclaimer_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->disclaimer_trigger_id;
    }

    public function getDisclaimerTriggerID() {
        return $this->disclaimer_trigger_id;
    }

    public function setDisclaimerTriggerID($disclaimer_trigger_id) {
        $this->disclaimer_trigger_id = $disclaimer_trigger_id;
    }

    public function getDisclaimerID() {
        return $this->disclaimer_id;
    }

    public function setDisclaimerID($disclaimer_id) {
        $this->disclaimer_id = $disclaimer_id;
    }

    public function getDisclaimerTriggerType() {
        return $this->disclaimer_trigger_type;
    }

    public function setDisclaimerTriggerType($disclaimer_trigger_type) {
        $this->disclaimer_trigger_type = $disclaimer_trigger_type;
    }

    public function getDisclaimerTriggerValue() {
        return $this->disclaimer_trigger_value;
    }

    public function setDisclaimerTriggerValue($disclaimer_trigger_value) {
        $this->disclaimer_trigger_value = $disclaimer_trigger_value;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;
    }

    public static function fetchRowByID($disclaimer_trigger_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "disclaimer_trigger_id", "method" => "=", "value" => $disclaimer_trigger_id)
        ));
    }

    public static function fetchRowByDisclaimerID($disclaimer_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "disclaimer_id", "method" => "=", "value" => $disclaimer_id)
        ));
    }

    public static function fetchRowByDisclaimerIDTriggerTypeTriggerValue($disclaimer_id, $disclaimer_trigger_type, $disclaimer_trigger_value) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "disclaimer_id", "method" => "=", "value" => $disclaimer_id),
            array("key" => "disclaimer_trigger_type", "method" => "=", "value" => $disclaimer_trigger_type),
            array("key" => "disclaimer_trigger_value", "method" => "=", "value" => $disclaimer_trigger_value)
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "disclaimer_trigger_id", "method" => ">=", "value" => 0)));
    }

    public static function fetchAllByDisclaimerID($disclaimer_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "disclaimer_id", "method" => "=", "value" => $disclaimer_id)
        ));
    }

    public function deleteByDisclaimerID($disclaimer_id) {
        global $db;
        if (isset($disclaimer_id)) {
            $query = "DELETE FROM `" . static::$table_name . "` WHERE `disclaimer_id` = ?";

            if ($db->Execute($query, array($disclaimer_id))) {
                return true;
            } else {
                application_log("error", "Failed to Delete Disclaimer Trigger with Disclaimer ID[" . $disclaimer_id . "].  DB Said: " . $db->ErrorMsg());
                return false;
            }
        } else {
            return false;
        }
    }

}