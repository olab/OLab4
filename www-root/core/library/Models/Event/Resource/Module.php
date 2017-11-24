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
 * A model for handling Learning Module Event Resources
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Event_Resource_Module extends Models_Base {
    protected $event_resource_module_id,
            $event_id,
            $required,
            $timeframe,
            $proxify,
            $link,
            $module_title,
            $module_notes,
            $accesses,
            $release_date,
            $release_until,
            $updated_date,
            $updated_by;
    
    protected static $table_name = "event_resource_modules";
    protected static $default_sort_column = "event_resource_module_id";
    protected static $primary_key = "event_resource_module_id";
    
    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }
    
    public function getID() {
        return $this->event_resource_module_id;
    }
    
    public function getEventID() {
        return $this->event_id;
    }
    
    public function getRequired() {
        return $this->required;
    }
    
    public function getTimeframe() {
        return $this->timeframe;
    }
    
    public function getProxify() {
        return $this->proxify;
    }
    
    public function getLink() {
        return $this->link;
    }
    
    public function getModuleTitle() {
        return $this->module_title;
    }
    
    public function getModuleNotes() {
        return $this->module_notes;
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
    
    public function getActive() {
        return $this->active;
    }
    
    public static function fetchRowByID ($id = null) {
        $self = new self();
        
        $contraints = array (
            array(
                "key" => "event_resource_module_id",
                "value" => $id,
                "method" => "="
            ),
        );
        
        return $self->fetchRow($contraints);
    }
    
    public function insert() {
        global $db;
        
        if ($db->AutoExecute(static::$table_name, $this->toArray(), "INSERT")) {
            $this->event_resource_module_id = $db->Insert_ID();
            return $this;
        } else {
            echo $db->ErrorMsg();
            return false;
        }
    }
    
    public function update() {
        global $db;
        
        if ($db->AutoExecute(static::$table_name, $this->toArray(), "UPDATE", "`event_resource_module_id` = ".$db->qstr($this->event_resource_module_id))) {
            return $this;
        } else {
            return false;
        }
    }
    
    public function delete() {
        global $db;
        
        $query = "DELETE FROM `".static::$table_name."` WHERE `event_resource_module_id` = ?";
        if ($db->Execute($query, $this->event_resource_module_id)) {
            return true;
        } else {
            return false;
        }
    }
}