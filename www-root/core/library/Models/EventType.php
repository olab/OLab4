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
 * A model for handeling Event Types
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_EventType extends Models_Base {
    protected $eventtype_id,
              $eventtype_title,
              $eventtype_description,
              $eventtype_active,
              $eventtype_order,
              $eventtype_default_enrollment,
              $eventtype_report_calculation,
              $updated_date,
              $updated_by;

    protected static $primary_key = "eventtype_id";
    protected static $table_name = "events_lu_eventtypes";
    protected static $default_sort_column = "eventtype_id";
    
    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }
    
    public function getID () {
        return $this->eventtype_id;
    }
    
    public function getEventTypeTitle () {
        return $this->eventtype_title;
    }
    
    public function getEventTypeDescription () {
        return $this->eventtype_description;
    }
    
    public function getEventTypeActive () {
        return $this->eventtype_active;
    }
    
    public function getEventTypeOrder () {
        return $this->eventtype_order;
    }
    
    public function getEventTypeDefaultEnrollment () {
        return $this->eventtype_default_enrollment;
    }
    
    public function getEventTypeReportCalculation () {
        return $this->eventtype_report_calculation;
    }
    
    public function getUpdatedDate () {
        return $this->updated_date;
    }
    
    public function getUpdatedBy () {
        return $this->updated_by;
    }
    
    public static function get ($eventtype_id = null, $active = 1) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "eventtype_id", "value" => $eventtype_id, "method" => "="),
            array("mode" => "AND", "key" => "eventtype_active", "value" => $active, "method" => "=")
        ));
    }
    
    public function getMappedMedbiqInstructionalMethod () {
        return Models_Event_MapEventsEventType::fetchRowByEventTypeID($this->eventtype_id);
    }
    
    public static function fetchAllByOrganisationID ($organisation_id = null, $active = 1, $search_value = NULL) {
        global $db;
        $event_types = false;
        $AND_SEARCH_LIKE = "";

        if (!is_null($search_value) && $search_value != "") {
            $AND_SEARCH_LIKE = "AND
            (
                a.`eventtype_title` LIKE (". $db->qstr("%". $search_value ."%") .")
            )";
        }
        
        $query = "	SELECT a.* FROM `events_lu_eventtypes` AS a 
                    LEFT JOIN `eventtype_organisation` AS b 
                    ON a.`eventtype_id` = b.`eventtype_id` 
                    LEFT JOIN `".AUTH_DATABASE."`.`organisations` AS c
                    ON c.`organisation_id` = b.`organisation_id` 
                    WHERE b.`organisation_id` = ?
                    AND a.`eventtype_active` = ? 
                    $AND_SEARCH_LIKE 
                    ORDER BY a.`eventtype_order` ASC";
        
        $results = $db->GetAll($query, array($organisation_id, $active));
        if ($results) {
            foreach ($results as $result) {
                $event_types[] = new self($result);
            }
        }
        return $event_types;
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
		if ($db->AutoExecute("`". static::$table_name ."`", $this->toArray(), "UPDATE", "`eventtype_id` = ".$db->qstr($this->getID()))) {
			return true;
		} else {
			return false;
		}
	}
}
