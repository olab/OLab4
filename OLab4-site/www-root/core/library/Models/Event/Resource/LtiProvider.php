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
 * A model for handling LTI Provider Event Resources
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Event_Resource_LtiProvider extends Models_Base {
    protected $id,
            $event_id,
            $is_required,
            $valid_from,
            $valid_until,
            $timeframe,
            $launch_url,
            $lti_key,
            $lti_secret,
            $lti_params,
            $lti_title,
            $lti_notes,
            $updated_date,
            $updated_by;
    
    protected static $table_name = "event_lti_consumers";
    protected static $default_sort_column = "id";
    protected static $primary_key = "id";
    
    public function getID() {
        return $this->id;
    }
    
    public function getEventID() {
        return $this->event_id;
    }
    
    public function getRequired() {
        return $this->is_required;
    }
    
    public function getValidFrom() {
        return $this->valid_from;
    }
    
    public function getValidUntil() {
        return $this->valid_until;
    }
    
    public function getTimeframe() {
        return $this->timeframe;
    }
    
    public function getLaunchUrl() {
        return $this->launch_url;
    }
    
    public function getLtiKey() {
        return $this->lti_key;
    }
    
    public function getLtiSecret() {
        return $this->lti_secret;
    }
    
    public function getLtiParams() {
        return $this->lti_params;
    }
    
    public function getLtiTitle() {
        return $this->lti_title;
    }
    
    public function getLtiNotes() {
        return $this->lti_notes;
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

    /* @return ArrayObject|Models_Event_Resource_LtiProvider[] */
    public static function fetchAllByEventID($event_id = null) {
        $self = new self();
        
        $contraints = array (
            array(
                "key" => "event_id",
                "method" => "=",
                "value" => $event_id
            ),
        );
        
        return $self->fetchAll($contraints);
    }

    /* @return bool|Models_Event_Resource_LtiProvider */
    public static function fetchRowByID ($id = null) {
        $self = new self();
        
        $contraints = array (
            array(
                "key" => "id",
                "value" => $id,
                "method" => "="
            ),
        );
        
        return $self->fetchRow($contraints);
    }

    /* @return bool|Models_Event_Resource_LtiProvider */
    public static function fetchRowByEventIdTitleUpdate($event_id = null, $lti_title = null, $update_date = null) {
        $self = new self();

        $constraints = array(
            array("key" => "event_id", "value" => $event_id, "method" => "="),
            array("key" => "lti_title", "value" => $lti_title, "method" => "="),
            array("key" => "update_date", "value" => $update_date, "method" => "=")
        );

        return $self->fetchRow($constraints);
    }
    
    public function insert() {
        global $db;
        
        if ($db->AutoExecute(static::$table_name, $this->toArray(), "INSERT")) {
            $this->id = $db->Insert_ID();
            return $this;
        } else {
            echo $db->ErrorMsg(); exit;
            return false;
        }
    }
    
    public function update() {
        global $db;
        
        if ($db->AutoExecute(static::$table_name, $this->toArray(), "UPDATE", "`id` = ".$db->qstr($this->id))) {
            return $this;
        } else {
            return false;
        }
    }
    
    public function delete() {
        global $db;
        
        $query = "DELETE FROM `".static::$table_name."` WHERE `id` = ?";
        if ($db->Execute($query, $this->id)) {
            return true;
        } else {
            return false;
        }
    }       
}