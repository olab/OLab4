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
 * A model to handle interaction with the top level of the event draft system.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */
class Models_Event_Draft extends Models_Base {
    
    protected $draft_id, $status, $name, $description, $created, $preserve_elements;

    protected static $table_name                = "drafts";
    protected static $default_sort_column       = "name";
    protected static $primary_key               = "draft_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }
    
    public function getDraftID() {
        return $this->draft_id;
    }
    
    public function getID() {
        return $this->draft_id;
    }
    
    public function getStatus() {
        return $this->status;
    }
    
    public function getName() {
        return $this->name;
    }
    
    public function getDescription() {
        return $this->description;
    }
    
    public function getCreated() {
        return $this->created;
    }
    
    public function getPreserveElements() {
        return $this->preserve_elements;
    }
    
    public static function fetchRowByID($id = 0) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "draft_id", "value" => $id, "method" => "=", "mode" => "AND")
            )
        );
    }

    public static function fetchAllRecords($status = "open") {
        $self = new self();

        $constraints = array(
            array(
                "mode"      => "AND",
                "key"       => "status",
                "value"     => $status,
                "method"    => "="
            )
        );

        $objs = $self->fetchAll($constraints, "=", "AND", $sort_col, $sort_order);
        $output = array();

        if (!empty($objs)) {
            foreach ($objs as $o) {
                $output[] = $o;
            }
        }

        return $output;
    }
    
    public static function fetchAllByProxyID($proxy_id) {
        global $db;
        
        $output = false;
        
        $query = "SELECT a.*
                    FROM `drafts` AS a
                    JOIN `draft_creators` AS b
                    ON b.`draft_id` = a.`draft_id`
                    WHERE b.`proxy_id` = ?
                    AND a.`status` <> 'closed'";
        $results = $db->GetAll($query, array($proxy_id));
        if ($results) {
            $output = array();
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }
        
        return $output;
    }

    public function update() {
        global $db;
        
        if ($db->AutoExecute(static::$table_name, $this->toArray(), "UPDATE", "`draft_id` = ".$this->draft_id)) {
            return $this;
        } else {
            return false;
        }
        
    }
    
    public function insert() {
        global $db;
        
        if ($db->AutoExecute(static::$table_name, $this->toArray(), "INSERT")) {
            return $this;
        } else {
            return false;
        }
    }

    public function deleteCreators() {
        global $db;
        
        $query = "DELETE FROM `draft_creators` WHERE `draft_id` = ?";
        $results = $db->Execute($query, array($this->draft_id));
        if ($results) {
            return $this;
        } else {
            return false;
        }
    }
    
    public function deleteOptions() {
        global $db;
        
        $query = "DELETE FROM `draft_options` WHERE `draft_id` = ?";
        $results = $db->Execute($query, array($this->draft_id));
        if ($results) {
            return $this;
        } else {
            return false;
        }
    }
}