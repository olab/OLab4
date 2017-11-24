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
 * A model for handeling Event Event Types
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Event_Draft_EventType extends Models_Base {
    protected   $deventtype_id,
                $eeventtype_id,
                $devent_id,
                $event_id,
                $eventtype_id,
                $duration;

    protected static $table_name           = "draft_eventtypes";
    protected static $primary_key          = "deventtype_id";
    protected static $default_sort_column  = "event_id";
    
    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->deventtype_id;
    }

    public function getDraftEventTypeID () {
        return $this->deventtype_id;
    }
    
    public function getDraftEventID () {
        return $this->devent_id;
    }
    
    public function getEventID () {
        return $this->event_id;
    }
    
    public function getEventTypeID () {
        return $this->eventtype_id;
    }
    
    public function getDuration () {
        return $this->duration;
    }
    
    public static function fetchAllByEventID($event_id = 0) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "event_id", "value" => $event_id, "method" => "=")
        ));
    }

    public static function fetchRowByEventID($event_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "event_id", "value" => $event_id, "method" => "=")
        ));
    }
}