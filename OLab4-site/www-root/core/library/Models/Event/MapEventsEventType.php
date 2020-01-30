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
 * A model for handling mapped event types
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Event_MapEventsEventType extends Models_Base {
    protected $map_events_eventtypes_id,
              $fk_instructional_method_id,
              $fk_eventtype_id,
              $updated_date,
              $updated_by;

    protected static $table_name            = "map_events_eventtypes";
    protected static $default_sort_column   = "map_events_eventtypes_id";
    protected static $primary_key           = "map_events_eventtypes_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }
    
    public function getID () {
        return $this->map_events_eventtypes_id;
    }
    
    public function getInstructionalMethodID () {
        return $this->fk_instructional_method_id;
    }
    
    public function getEventTypeID () {
        return $this->fk_eventtype_id;
    }
    
    public function getUpdatedDate () {
        return $this->updated_date;
    }
    
    public function getUpdatedBy () {
        return $this->updated_by;
    }
    
    public static function fetchAllByInstructionalMethodID ($instructional_method_id = null) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "fk_instructional_method_id", "value" => $instructional_method_id, "method" => "=")
        ));
    }
    
    public static function fetchRowByEventTypeID ($event_type_id = null) {
        $self = new self();
        return $self->fetchRow(array("fk_eventtype_id" => $event_type_id));
    }
    
    public static function fetchAllByEventTypeID ($event_type_id = null) {
        $self = new self();
        return $self->fetchAll(array("fk_eventtype_id" => $event_type_id));
    }
    
    public function getInstructionalMethod () {
        return Models_MedbiqInstructionalMethod::get($this->fk_instructional_method_id);
    }
    
    public function update() {
		global $db;
		if ($db->AutoExecute("`". static::$table_name ."`", $this->toArray(), "UPDATE", "`map_events_eventtypes_id` = ".$db->qstr($this->getID()))) {
			return true;
		} else {
			return false;
		}
	}
    
    public function insert() {
		global $db;
		if ($db->AutoExecute("`". static::$table_name ."`", $this->toArray(), "INSERT")) {
			$this->map_events_eventtypes_id = $db->Insert_ID();
			return true;
		} else {
			return false;
		}
	}
    
    public function delete () {
		global $db;
		
		$query = "DELETE FROM `". static::$table_name ."` WHERE `map_events_eventtypes_id` = ?";
		$result = $db->Execute($query, array($this->getID()));
		
		return $result;
	}
}
