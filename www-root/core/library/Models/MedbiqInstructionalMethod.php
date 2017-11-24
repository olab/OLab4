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
 * A model for handeling Medbiq Instructional Methods
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_MedbiqInstructionalMethod extends Models_Base {
    protected $instructional_method_id,
              $code,
              $instructional_method,
              $instructional_method_description,
              $active,
              $updated_date,
              $updated_by;

    protected static $primary_key = "instructional_method_id";
    protected static $table_name = "medbiq_instructional_methods";
    protected static $default_sort_column = "instructional_method_id";
    
    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }
    
    public function getID () {
        return $this->instructional_method_id;
    }
    
    public function getCode () {
        return $this->code;
    }
    
    public function getInstructionalMethod () {
        return $this->instructional_method;
    }
    
    public function getInstructionalMethodDescription () {
        return $this->instructional_method_description;
    }
    
    public function getActive () {
        return $this->active;
    }
    
    public function getUpdatedDate () {
        return $this->updated_date;
    }
    
    public function getUpdatedBy () {
        return $this->updated_by;
    }
    
    public static function get ($instructional_method_id = null, $active = 1) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "instructional_method_id", "value" => $instructional_method_id, "method" => "="),
            array("mode" => "AND", "key" => "active", "value" => $active, "method" => "=")
        ));
    }
    
    public static function fetchAllMedbiqInstructionalMethods () {
        $self = new self();
        return $self->fetchAll(array("active" => 1), "=", "AND", "instructional_method");
    }
    
    public function getMappedEventTypes () {
        return Models_Event_MapEventsEventType::fetchAllByInstructionalMethodID($this->instructional_method_id);
    }
    
    public function update() {
		global $db;
		if ($db->AutoExecute("`". static::$table_name ."`", $this->toArray(), "UPDATE", "`instructional_method_id` = ".$db->qstr($this->getID()))) {
			return true;
		} else {
			return false;
		}
	}
    
    public function insert() {
		global $db;
		if ($db->AutoExecute("`". static::$table_name ."`", $this->toArray(), "INSERT")) {
			$this->instructional_method_id = $db->Insert_ID();
			return true;
		} else {
			return false;
		}
	}
    
    public function delete () {
        return false;
    }
}
