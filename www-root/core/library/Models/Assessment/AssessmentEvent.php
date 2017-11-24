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
 * A model for handeling events linked to assessments
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Assessment_AssessmentEvent extends Models_Base {
    protected $assessment_event_id,
              $assessment_id,
              $event_id,
              $updated_date,
              $updated_by,
              $active;

    protected static $primary_key = "assessment_event_id";
    protected static $table_name = "assessment_events";
    protected static $default_sort_column = "assessment_event_id";
    
    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }
    
    public function getID() {
        return $this->assessment_event_id;
    }
    
    public function getAssessmentID() {
        return $this->assessment_id;
    }
    
    public function getEventID() {
        return $this->event_id;
    }
    
    public function getUpdatedDate() {
        return $this->updated_date;
    }
    
    public function getUpdatedBy() {
        return $this->updated_by;
    }
    
    public function getActive() {
        return $this->active;
    }
    
    public function setActive($active = 1) {
        $this->active = $active;
    }
    
    public static function getEvent($event_id = null) {
        return Models_Event::get($event_id);
    }
    
    public function getAssessment() {
        return Models_Gradebook_Assessment::fetchRowByID($this->assessment_id);
    }

    public static function fetchRowByAssessmentID ($assessment_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "assessment_id", "value" => $assessment_id, "method" => "="),
            array("mode" => "AND", "key" => "active", "value" => 1, "method" => "=")    
        ));
    }
    
    public static function fetchRowByEventID ($event_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "event_id", "value" => $event_id, "method" => "="),
            array("mode" => "AND", "key" => "active", "value" => 1, "method" => "=")    
        ));
    }

    public static function fetchAllAssessmentByEventID ($event_id) {
        global $db;

        $query = "SELECT b.`assessment_id`, b.`name`, c.`course_id`  
                    FROM `assessment_events` AS a
                    LEFT JOIN assessments AS b 
                    ON a.`assessment_id` = b.`assessment_id`
                    LEFT JOIN courses AS c 
                    ON b.`course_id` = c.`course_id`
                    WHERE a.`event_id` = ? 
                    AND a.`active` = 1";

        $results = $db->GetAll($query, array($event_id));

        if ($results) {
            return $results;
        }
        return false;
    }
    
    public function insert() {
		global $db;
		
		if ($db->AutoExecute("`assessment_events`", $this->toArray(), "INSERT")) {
			return true;
		} else {
			return false;
		}
    }
    
    public function update() {
		global $db;
        
		if ($db->AutoExecute("`assessment_events`", $this->toArray(), "UPDATE", "`assessment_event_id` = ".$db->qstr($this->getID()))) {
			return true;
		} else {
			return false;
		}
	}
}
?>
