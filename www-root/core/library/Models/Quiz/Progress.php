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
 * A model to handle quiz progress
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Quiz_Progress extends Models_Base {
    
    protected $qprogress_id, $aquiz_id, $content_type, $content_id, $quiz_id, $proxy_id, $progress_value, $quiz_score, $quiz_value, $updated_date, $updated_by;
    
    protected static $table_name = "quiz_progress";
    protected static $default_sort_column = "qprogress_id";
    protected static $primary_key = "qprogress_id";
    
    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }
    
    public static function fetchRowByID($qprogress_id) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "qprogress_id", "value" => $qprogress_id, "method" => "=", "mode" => "AND")
            )
        );
    }

    public static function fetchRowByAQuizID($aquiz_id) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "aquiz_id", "value" => $aquiz_id, "method" => "=", "mode" => "AND")
            )
        );
    }

    public static function fetchAllRecords($aquiz_id) {
        $self = new self();

        $constraints = array(
            array(
                "mode"      => "AND",
                "key"       => "aquiz_id",
                "value"     => $aquiz_id,
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
    
    public static function fetchRowByAquizIDProxyID($aquiz_id, $proxy_id, $progress_value = "inprogress") {
        global $db;
        
        $output = false;
        
        $query = "SELECT *
                    FROM `quiz_progress`
                    WHERE `aquiz_id` = ?
                    AND `proxy_id` = ?
                    AND `progress_value` = ?
                    ORDER BY `updated_date` ASC";
        $result = $db->GetRow($query, array($aquiz_id, $proxy_id, $progress_value));
        if ($result) {
            $output = new self($result);
        }
        
        return $output;
    }
    
    public static function fetchAllByAquizIDProxyID($aquiz_id = null, $proxy_id = null) {
        global $db;
        $progress_records = false;
        
        $query = "	SELECT *
                    FROM `quiz_progress`
                    WHERE `aquiz_id` = ?
                    AND `proxy_id` = ?";
        
        $results = $db->GetAll($query, array($aquiz_id, $proxy_id));
        if ($results) {
            foreach ($results as $result) {
                $progress_records[] = new self($result);
            }
        }
        
        return $progress_records;
    }
    
    public static function getDistinctAttempts ($aquiz_id) {
        global $db;
        $completed_attempts = $db->GetOne("SELECT COUNT(DISTINCT `proxy_id`) FROM `quiz_progress` WHERE `progress_value` = 'complete' AND `aquiz_id` = ".$db->qstr($aquiz_id));
        return $completed_attempts;
    }
    
    public function getQprogressID() {
        return $this->qprogress_id;
    }

    public function getAquizID() {
        return $this->aquiz_id;
    }

    public function getContentType() {
        return $this->content_type;
    }

    public function getContentID() {
        return $this->content_id;
    }

    public function getQuizID() {
        return $this->quiz_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getProgressValue() {
        return $this->progress_value;
    }

    public function setProgressValue($value) {
        $this->progress_value = $value;
    }

    public function getQuizScore() {
        return $this->quiz_score;
    }

    public function getQuizValue() {
        return $this->quiz_value;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }
    
    public function insert() {
        global $db;
        
        if ($db->AutoExecute(static::$table_name, $this->toArray(), "INSERT")) {
            $this->qprogress_id = $db->Insert_ID();
            return $this;
        } else {
            return false;
        }
    }
    
    public function update() {
        global $db;
        
        if ($db->AutoExecute(static::$table_name, $this->toArray(), "UPDATE", "`qprogress_id` = ".$db->qstr($this->qprogress_id))) {
            return $this;
        } else {
            return false;
        }
    }
    
    public function delete() {
        global $db;
        
        $query = "DELETE FROM `".static::$table_name."` WHERE `qprogress_id` = ?";
        if ($db->Execute($query, $this->qprogress_id)) {
            return true;
        } else {
            return false;
        }
    }
    
}