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
 * A model for handling Event Resource Types
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Event_Resource_Type extends Models_Base {
    protected $event_resource_type_id,
            $resource_type,
            $description,
            $updated_date,
            $updated_by,
            $active;
    
    protected static $table_name = "event_lu_resource_types";
    protected static $default_sort_column = "resource_type";
    protected static $primary_key = "event_resource_type_id";
    
    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }
    
    public function getID () {
        return $this->event_resource_type_id;
    }
    
    public function getResourceType () {
        return $this->resource_type;
    }
    
    public function getDescription () {
        return $this->description;
    }
    
    public function getUpdatedDate () {
        return $this->updated_date;
    }
    
    public function getUpdatedBy () {
        return $this->updated_by;
    }
    
    public function getActive () {
        return $this->active;
    }
    
    public static function fetchAllRecords ($active = 1) {
        $self = new self();
        
        $contraints = array (
            array(
                "key" => "active",
                "value" => $active,
                "method" => "="
            )
        );
        
        return $self->fetchAll($contraints);
    }
}
