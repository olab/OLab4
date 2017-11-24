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
 * A model to handle quiz contacts
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Quiz_Contact extends Models_Base {

    protected $qcontact_id, $quiz_id, $proxy_id, $updated_date, $updated_by;
    
    protected static $table_name = "quiz_contacts";
    protected static $default_sort_column = "qcontact_id";
    protected static $primary_key = "qcontact_id";
    
    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }
    
    public static function fetchRowByID($question_id) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "question_id", "value" => $question_id, "method" => "=", "mode" => "AND")
            )
        );
    }

    public static function fetchAllRecords($quiz_id) {
        $self = new self();

        $constraints = array(
            array(
                "mode"      => "AND",
                "key"       => "quiz_id",
                "value"     => $quiz_id,
                "method"    => "="
            )
        );

        $objs = $self->fetchAll($constraints, "=", "AND", static::$default_sort_column, $sort_order);
        $output = array();

        if (!empty($objs)) {
            foreach ($objs as $o) {
                $output[] = $o;
            }
        }

        return $output;
    }
    
    public static function deleteContacts($quiz_id) {
        global $db;
        $query = "DELETE FROM `quiz_contacts` WHERE `quiz_id` = ".$db->qstr($quiz_id);
        return $db->Execute($query);
    }
    
    public function getQcontactID() {
        return $this->qcontact_id;
    }

    public function getQuizID() {
        return $this->quiz_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function insert() {
        global $db;
        if ($db->autoExecute(static::$table_name, $this->toArray(), "INSERT")) {
            $this->qcontact_id = $db->Insert_ID();
            return $this;
        } else {
            return false;
        }
    }
    
    public function update() {
        global $db;
        if ($db->autoExecute(static::$table_name, $this->toArray(), "UPDATE", "`qcontact_id` = ".$this->qcontact_id)) {
            return $this;
        } else {
            return false;
        }
    }
    
    public function delete() {
        global $db;
        
        $query = "DELETE FROM `".static::$table_name."` WHERE `qcontact_id` = ?";
        if ($db->Execute($query, $this->qcontact_id)) {
            return true;
        } else {
            return false;
        }
    }
    
}
