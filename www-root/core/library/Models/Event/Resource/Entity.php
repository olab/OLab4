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
 * A model for handling Event Resource Entities
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Event_Resource_Entity extends Models_Base {
    protected $event_resource_entity_id,
            $event_id,
            $entity_type,
            $entity_value,
            $release_date,
            $release_until,
            $updated_date,
            $updated_by,
            $active;
            
    protected $resource,
            $hidden;
    
    protected static $table_name = "event_resource_entities";
    protected static $default_sort_column = "event_resource_entity_id";
    protected static $primary_key = "event_resource_entity_id";
    
    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }
    
    public function getID() {
        return $this->event_resource_entity_id;
    }
    
    public function getEventID() {
        return $this->event_id;
    }
    
    public function getEntityType() {
        return $this->entity_type;
    }
    
    public function getEntityValue() {
        return $this->entity_value;
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
    
    public function getActive() {
        return $this->active;
    }
    
    public function getHidden() {
        return $this->hidden;
    }

    public function setEventId($id) {
        $this->event_id = $id;
    }

    public function setReleaseDate($release_date) {
        $this->release_date = $release_date;
    }

    public function setReleaseUntil($release_until) {
        $this->release_until = $release_until;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;
    }
    
    /* @return bool|Models_Event_Resource_Entity */
    public static function fetchRowByID($event_resource_entity_id = null, $active = 1) {
        $self = new self();
        
        $constraints = array(
            array(
                "key" => "event_resource_entity_id",
                "method" => "=",
                "value" => $event_resource_entity_id,
            ),
            array(
                "mode" => "AND",
                "key" => "active",
                "method" => "=",
                "value" => $active,
            )
        );
        
        return $self->fetchRow($constraints);
    }
    
    /* @return ArrayObject|Models_Event_Resource_Entity[] */
    public function fetchAllRecords($active = 1) {
        $self = new self();
        
        $constraints = array(
            array(
               "key" => "active",
               "value" => $active,
               "method" => "="
            )
        );
        
        return $self->fetchAll($constraints);
    }
    
    /* @return ArrayObject|Models_Event_Resource_Entity[] */
    public static function fetchAllByEventID($event_id = null, $active = 1) {
        $self = new self();
        
        $constraints = array(
            array(
               "key" => "event_id",
               "value" => $event_id,
               "method" => "="
            ),
            array(
               "mode" => "AND", 
               "key" => "active",
               "value" => $active,
               "method" => "="
            )
        );
        
        return $self->fetchAll($constraints);
    }


    /* @return ArrayObject|Models_Event_Resource_Entity[] */
    public static function fetchAllByEntityType($entity_type = null, $active = 1) {
        $self = new self();

        $constraints = array(
            array(
                "key" => "entity_type",
                "value" => $entity_type,
                "method" => "="
            )
        );

        return $self->fetchAll($constraints);
    }
    
    /* @return bool|Models_Event_Resource_Entity */
    public static function fetchRowByEntityTypeEntityValue ($entity_type = null, $entity_value = null, $active = 1) {
        $self = new self();

        $constraints = array(
            array("key" => "entity_type", "value" => $entity_type, "method" => "="),
            array("mode" => "AND", "key" => "entity_value", "value" => $entity_value, "method" => "="),
            array("mode" => "AND", "key" => "active", "value" => $active, "method" => "=")
        );

        return $self->fetchRow($constraints);
    }

    /* @return bool|Models_Event_Resource_Entity */
    public static function fetchRowByEventIdEntityTypeEntityValue ($event_id = null, $entity_type = null, $entity_value = null, $active = 1) {
        $self = new self();

        $constraints = array(
            array("key" => "event_id", "value" => $event_id, "method" => "="),
            array("mode" => "AND", "key" => "entity_type", "value" => $entity_type, "method" => "="),
            array("mode" => "AND", "key" => "entity_value", "value" => $entity_value, "method" => "="),
            array("mode" => "AND", "key" => "active", "value" => $active, "method" => "=")
        );

        return $self->fetchRow($constraints);
    }

    /* @return bool|Models_Event_Resource_Entity */
    public static function fetchRowByEventIDEntityValue ($event_id = null, $entity_value = null) {
        $self = new self();
        
        $constraints = array(
            array(
               "key" => "event_id",
               "value" => $event_id,
               "method" => "="
            ),
            array(
               "mode" => "AND", 
               "key" => "entity_value",
               "value" => $entity_value,
               "method" => "="
            )
        );
        
        return $self->fetchRow($constraints);
    }
    
    /* @return ArrayObject|Models_Event_Resource_Entity[] */
    public static function fetchAllByEventIDReleaseDates($event_id = null, $active = 1) {
        global $db;
        $entities = false;
        
        $query = "  SELECT * FROM `event_resource_entities`
                    WHERE `event_id` = ?
                    AND (`release_date` = 0 OR `release_date` <= ?)
                    AND (`release_until` = 0 OR `release_until` >= ?)
                    AND `active` = ?";
        
        $results = $db->GetAll($query, array($event_id, time(), time(), $active));
        
        if ($results) {
            foreach ($results as $result) {
                $entities[] = new self($result);
            }
        }
        
        return $entities;
    }
    
    public function getResource() {
        if (NULL === $this->resource) {
            $resource = false;
            switch ($this->entity_type) {
                case 1 :
                case 5 :
                case 6 :
                case 11 :
                    $resource = Models_Event_Resource_File::fetchRowByID($this->entity_value);
                    break;
                case 2 :
                    $resource = Models_Event_Resource_Classwork::fetchRowByID($this->entity_value);
                    break;
                case 3 :
                case 7 :
                    $resource = Models_Event_Resource_Link::fetchRowByID($this->entity_value);
                    break;
                case 4 :
                    $resource = Models_Event_Resource_Homework::fetchRowByID($this->entity_value);
                    break;
                case 8 :
                    $resource = Models_Quiz_Attached::fetchRowByID($this->entity_value);
                    break;
                case 9 :
                    $resource = Models_Event_Resource_TextbookReading::fetchRowByID($this->entity_value);
                    break;
                case 10 :
                    $resource = Models_Event_Resource_LtiProvider::fetchRowByID($this->entity_value);
                    break;
                case 12 :
                    $resource = Models_Exam_Post::fetchRowByID($this->entity_value);
                    break;
            }
        }
        $this->resource = $resource;
        return $resource;
    }
    
    public function insert() {
        global $db;
        
        if ($db->AutoExecute(static::$table_name, $this->toArray(), "INSERT")) {
            $this->event_resource_entity_id = $db->Insert_ID();
            return $this;
        } else {
            echo $db->ErrorMsg();
            return false;
        }
    }
    
    public function update() {
        global $db;
        
        if ($db->AutoExecute(static::$table_name, $this->toArray(), "UPDATE", "`event_resource_entity_id` = ".$db->qstr($this->event_resource_entity_id))) {
            return $this;
        } else {
            echo $db->ErrorMsg();
            return false;
        }
    }
    
    public function delete() {
        $this->active = 0;
        $this->update();
    }
}