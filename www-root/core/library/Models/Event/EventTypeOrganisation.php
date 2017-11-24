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
 * A model for handeling Organisation Event Types
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Event_EventTypeOrganisation extends Models_Base {
    protected $eventtype_id,
              $organisation_id;
    
    protected static $table_name            = "eventtype_organisation";
    protected static $default_sort_column   = "eventtype_id";
    protected static $primary_key           = "eventtype_id";
    
    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }
    
    public function getEventTypeID () {
        return $this->eventtype_id;
    }
    
    public function getOrganisationID () {
        return $this->organisation_id;
    }
    
    public function setEventTypeID ($eventtype_id) {
        $this->eventtype_id = $eventtype_id;
    }
    
    public static function get ($eventtype_id = null) {
        $self = new self();
        return $self->fetchRow(array("eventtype_id" => $eventtype_id));
    }
    
    public function insert() {
		global $db;
		if ($db->AutoExecute("`". static::$table_name ."`", $this->toArray(), "INSERT")) {
			$this->eventtype_id = $db->Insert_ID();
			return true;
		} else {
			return false;
		}
	}
    
    public function update() {
		global $db;
		if ($db->AutoExecute("`". static::$table_name ."`", $this->toArray(), "UPDATE", "`eventtype_id` = ".$db->qstr($this->getID() . " AND `organisation_id` = " .$db->qstr($this->getOrganisationID())))) {
			return true;
		} else {
			return false;
		}
	}
    
    public function delete() {
        global $db;
        $query = "	DELETE FROM `eventtype_organisation`
                    WHERE `eventtype_id` = ?
                    AND `organisation_id` = ?";
        
        if ($db->Execute($query, array($this->eventtype_id, $this->organisation_id))) {
            return true;
        } else {
            return false;
        }
    }
}