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
 * A model for handling Gradebook Grading Range entries
 *
 * @author Organisation: 
 * @author Developer: Eric Howarth <eric.howarth@queensu.ca>
 * @copyright Copyright 2016 . All Rights Reserved.
 */

class Models_Gradebook_Grading_Range extends Models_Base {
    protected $agrange_id,
              $agscale_id,
              $numeric_grade_min,
              $letter_grade,
              $gpa,
              $notes,
              $updated_date,
              $updated_by,
              $deleted_date,
              $deleted_by;

    protected static $table_name = "assessment_grading_range";
    protected static $primary_key = "agrange_id";
    protected static $default_sort_column = "numeric_grade_min";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->agrange_id;
    }

    public function getAgrangeID() {
        return $this->agrange_id;
    }

    public function getAgscaleID() {
        return $this->agscale_id;
    }

    public function setAgscaleID($agscale_id) {
        $this->agscale_id = $agscale_id;
        return $this;
    }

    public function getNumericGradeMin() {
        return $this->numeric_grade_min;
    }

    public function getLetterGrade() {
        return $this->letter_grade;
    }

    public function getGpa() {
        return $this->gpa;
    }

    public function getNotes() {
        return $this->notes;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;
        return $this;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;
        return $this;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;
        return $this;
    }

    public function getDeletedBy() {
        return $this->deleted_by;
    }

    public function setDeletedBy($deleted_by) {
        $this->deleted_by = $deleted_by;
        return $this;
    }

    public static function fetchRowByID($agrange_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "agrange_id", "value" => $agrange_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "agrange_id", "value" => 0, "method" => ">=")));
    }

    /**
     * Return all Models_Gradebook_Grading_Range entries for a given Models_Gradebook_Grading_Scale
     * 
     * @param null $agscale_id
     *
     * @return array of Models_Gradebook_Grading_Range objects
     */
    public static function fetchAllByScale($agscale_id = null) {
        $constraints[] = array("key" => "deleted_date", "method" => "IS", "value" => null);
        if (isset($agscale_id) && (int) $agscale_id > 0) {
            $constraints[] = array("key" => "agscale_id", "method" => "=", "value" => $agscale_id);
        }
        $self = new self();
        return $self->fetchAll($constraints);
    }

    
    /**
     * Return the range corresponding to the numeric percentage grade for the given Models_Gradebook_Grading_Scale
     * 
     * @param $agscale_id
     * @param $percent
     *
     * @return bool|Models_Gradebook_Grading_Range
     */
    public static function getRangeForNumericGrade($agscale_id, $percent) {
        global $db;

        $query = "SELECT * FROM `".static::$database_name."`.`".static::$table_name."`
                  WHERE `deleted_date` IS null
                  AND `agscale_id` = ?
                  AND ? >= `numeric_grade_min` 
                  ORDER BY `numeric_grade_min` DESC
                  LIMIT 1";
        $results = $db->GetRow($query, array($agscale_id, $percent));
        if ($results) {
            return new self($results);
        } else {
            return false;
        }
    }

    /**
     * Purge records for a given scale
     */
    public static function deleteByScale($agscale_id) {
        global $db;
        $agscale_id = (int) $agscale_id;
        if (!empty($agscale_id)) {
            $query = "DELETE FROM `".static::$database_name."`.`".static::$table_name."`
                      WHERE `agscale_id` = ?
                      AND `deleted_date` IS null";
            $result = $db->Execute($query, array($agscale_id));
            if ($result) {
                return true;
            }
        }
        return false;
    }
}
