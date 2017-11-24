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

class Models_Event_Resource_File extends Models_Base {
    protected $efile_id,
            $event_id,
            $required,
            $timeframe,
            $file_category,
            $file_type,
            $file_size,
            $file_name,
            $file_title,
            $file_notes,
            $access_method,
            $accesses,
            $release_date,
            $release_until,
            $updated_date,
            $updated_by;
    
    protected static $table_name = "event_files";
    protected static $default_sort_column = "efile_id";
    protected static $primary_key = "efile_id";
    
    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }
    
    public function getID() {
        return $this->efile_id;
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
    
    public function getFileCategory() {
        return $this->file_category;
    }
    
    public function getFileType() {
        return $this->file_type;
    }
    
    public function getFileSize() {
        return $this->file_size;
    }
    
    public function getFileName() {
        return $this->file_name;
    }
    
    public function getFileTitle() {
        return $this->file_title;
    }
    
    public function getFileNotes() {
        return $this->file_notes;
    }
    
    public function getAccessMethod() {
        return $this->access_method;
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
    
    /* @return bool|Models_Event_Resource_File */
    public static function fetchRowByID ($id = null) {
        $self = new self();
        
        $contraints = array (
            array(
                "key" => "efile_id",
                "value" => $id,
                "method" => "="
            ),
        );
        
        return $self->fetchRow($contraints);
    }
    
    /* @return bool|Models_Event_Resource_File */
    public static function fetchRowByEventIDName ($event_id = null, $file_name = null) {
        $self = new self();
        
        $constraints = array (
            array("key" => "event_id", "value" => $event_id, "method" => "="),
            array("key" => "file_name", "value" => $file_name, "method" => "=")
        );

        return $self->fetchRow($constraints);
    }

    /* @return bool|Models_Event_Resource_File */
    public static function fetchRowByEventIDNameUpdate($event_id = null, $file_name = null, $updated_date = null) {
        $self = new self();
        
        $contraints = array (
            array("key" => "event_id", "value" => $event_id, "method" => "="),
            array("key" => "file_name", "value" => $file_name, "method" => "="),
            array("key" => "updated_date", "value" => $updated_date, "method" => "=")
        );
        
        return $self->fetchRow($contraints);
    }
    
    /* @return bool|Models_Statistic */
    public function getViewed() {
        global $db;
        global $ENTRADA_USER;
        
        $query = "	SELECT `statistic_id`, `proxy_id`, `module`, `action`, `action_field`, `action_value`, `prune_after`, MAX(`timestamp`) AS `timestamp`
                    FROM `statistics`
                    WHERE `proxy_id` = ?
                    AND `action` = 'file_download'
                    AND `action_field` = 'file_id'
                    AND `action_value` = ?";
        
        $result = $db->GetRow($query, array($ENTRADA_USER->getActiveId(), $this->efile_id));
        if ($result) {
            return new Models_Statistic($result);
        } else {
            return false;
        }
    }
    
    public function insert() {
        global $db;
        
        if ($db->AutoExecute(static::$table_name, $this->toArray(), "INSERT")) {
            $this->efile_id = $db->Insert_ID();
            return $this;
        } else {
            return false;
        }
    }
    
    public function update() {
        global $db;
        
        if ($db->AutoExecute(static::$table_name, $this->toArray(), "UPDATE", "`efile_id` = ".$db->qstr($this->efile_id))) {
            return $this;
        } else {
            return false;
        }
    }
    
    public function delete() {
        global $db;
        
        $query = "DELETE FROM `".static::$table_name."` WHERE `efile_id` = ?";
        if ($db->Execute($query, $this->efile_id)) {
            return true;
        } else {
            return false;
        }
    }
}