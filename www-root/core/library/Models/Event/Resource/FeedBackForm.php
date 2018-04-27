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
 * A model for handling Feedback Form Event Resources
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jordan L <jl250@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Models_Event_Resource_FeedBackForm extends Models_Base {
    protected $id,
        $event_id,
        $form_id,
        $required,
        $timeframe,
        $accesses,
        $release_date,
        $release_until,
        $created_by,
        $updated_date,
        $updated_by,
        $deleted_date,
        $deleted_by;

    protected static $table_name = "event_feedback_forms";
    protected static $default_sort_column = "id";
    protected static $primary_key = "id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->id;
    }

    public function getEventID() {
        return $this->event_id;
    }

    public function getFormID() {
        return $this->form_id;
    }

    public function getRequired() {
        return $this->required;
    }

    public function getTimeframe() {
        return $this->timeframe;
    }

    public function getAccesses() {
        return $this->accesses;
    }

    public function getReleaseDate() {
        return $this->release_date;
    }

    public function getReleaseUntil() {
        return $this->release_until;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function getDeletedBy() {
        return $this->deleted_by;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setEventId($event_id) {
        $this->event_id = $event_id;
    }

    public function setFormId($form_id) {
        $this->form_id = $form_id;
    }

    public function setRequired($required) {
        $this->required = $required;
    }

    public function setTimeframe($timeframe) {
        $this->timeframe = $timeframe;
    }

    public function setAccesses($accesses) {
        $this->accesses = $accesses;
    }

    public function setReleaseDate($release_date) {
        $this->release_date = $release_date;
    }

    public function setReleaseUntil($release_until) {
        $this->release_until = $release_until;
    }

    public function setCreatedBy($created_by) {
        $this->created_by = $created_by;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;
    }

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;
    }

    public function setDeletedBy($deleted_by) {
        $this->deleted_by = $deleted_by;
    }

    public static function fetchRowByID($id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "id", "value" => $id, "method" => "="),
            array("key" => "deleted_date", "value" => (isset($deleted_date) ? $deleted_date : NULL), "method" => (isset($deleted_date) ? "<=" : "IS"))
        ));
    }

    public static function fetchRowByEventIDUpdatedDate($event_id, $updated_date, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "event_id", "value" => $event_id, "method" => "="),
            array("key" => "updated_date", "value" => $updated_date, "method" => "="),
            array("key" => "deleted_date", "value" => (isset($deleted_date) ? $deleted_date : NULL), "method" => (isset($deleted_date) ? "<=" : "IS"))
        ));
    }

    public function fetchRowByEventIDFormID($event_id, $form_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "event_id", "value" => $event_id, "method" => "="),
            array("key" => "form_id", "value" => $form_id, "method" => "="),
            array("key" => "deleted_date", "value" => (isset($deleted_date) ? $deleted_date : NULL), "method" => (isset($deleted_date) ? "<=" : "IS"))
        ));
    }

    public function insert() {
        global $db;

        if ($db->AutoExecute(static::$table_name, $this->toArray(), "INSERT")) {
            $this->id = $db->Insert_ID();
            return $this;
        } else {
            return false;
        }
    }

    public function update() {
        global $db;

        if ($db->AutoExecute(static::$table_name, $this->toArray(), "UPDATE", "`id` = ".$db->qstr($this->id))) {
            return $this;
        } else {
            return false;
        }
    }

    public function delete() {
        global $db;

        $query = "DELETE FROM `".static::$table_name."` WHERE `id` = ?";
        if ($db->Execute($query, $this->id)) {
            return true;
        } else {
            return false;
        }
    }
}