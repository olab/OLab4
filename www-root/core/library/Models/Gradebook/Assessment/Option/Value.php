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
 * A model for handling the values associated with gradebook assessment options.
 *
 * @author Organisation: Queen's University
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Gradebook_Assessment_Option_Value extends Models_Base {
    protected $aovalue_id, $aoption_id, $proxy_id, $value;

    protected static $table_name = "assessment_option_values";
    protected static $default_sort_column = "aoption_id";
    protected static $primary_key = "aovalue_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->aovalue_id;
    }

    public function getAovalueID() {
        return $this->aovalue_id;
    }

    public function getAoptionID() {
        return $this->aoption_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getValue() {
        return $this->value;
    }

    public static function fetchRowByID($aovalue_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "aovalue_id", "value" => $aovalue_id, "method" => "=")
        ));
    }

    public static function fetchAllByOptionID($aoption_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "aoption_id", "value" => $aoption_id, "method" => "=")
        ));
    }

    public static function fetchAllByAOptionIDsStudentIDs($aoption_ids = array(), $student_ids = array()) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "aoption_id", "value" => $aoption_ids, "method" => "IN"),
            array("key" => "proxy_id", "value" => $student_ids, "method" => "IN"),
        ));
    }

    public function insert() {
        global $db;

        if ($db->AutoExecute(static::$table_name, $this->toArray(), "INSERT")) {
            $this->aovalue_id = $db->Insert_ID();
            return $this;
        } else {
            return false;
        }

    }

    public function update() {
        global $db;

        if ($db->AutoExecute(static::$table_name, $this->toArray(), "UPDATE", "`aovalue_id` = ".$this->aovalue_id)) {
            return $this;
        } else {
            return false;
        }

    }
}