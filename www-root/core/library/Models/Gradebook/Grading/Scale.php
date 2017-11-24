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
 * A model for handling Gradebook Grading Scales
 *
 * @author Organisation: 
 * @author Developer: Eric Howarth <eric.howarth@queensu.ca>
 * @copyright Copyright 2016 . All Rights Reserved.
 */

class Models_Gradebook_Grading_Scale extends Models_Base {
    protected $agscale_id,
              $organisation_id,
              $title,
              $applicable_from,
              $updated_date,
              $updated_by,
              $deleted_date,
              $deleted_by;

    protected static $table_name = "assessment_grading_scale";
    protected static $primary_key = "agscale_id";
    protected static $default_sort_column = "agscale_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->agscale_id;
    }

    public function getAgscaleID() {
        return $this->agscale_id;
    }

    public function getOrganisationID() {
        return $this->organisation_id;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getApplicableFrom() {
        return $this->applicable_from;
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

    public static function fetchRowByID($agscale_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "agscale_id", "method" => "=", "value" => $agscale_id ),
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "agscale_id", "method" => ">=", "value" => null)));
    }

    /**
     * Find all the grading scales for an organisation
     *
     * @param null $organisation_id
     *
     * @return array of Models_Gradebook_Grading_Scale objects
     */
    public static function fetchAllByOrganisationID($organisation_id = null) {
        $constraints[] = array("key" => "deleted_date", "method" => "IS", "value" => null);
        if (isset($organisation_id) && (int) $organisation_id > 0) {
            $constraints[] = array("key" => "organisation_id", "method" => "=", "value" => $organisation_id);
        }
        $self = new self();
        return $self->fetchAll($constraints);
    }

    /**
     * Find the default grading scale for an organisation (has no applicable_from date)
     *
     * @param null $organisation_id
     *
     * @return bool|Models_Gradebook_Grading_Scale
     */
    public static function fetchDefaultScaleForOrganisation($organisation_id) {
        if (isset($organisation_id) && (int) $organisation_id > 0) {
            $constraints[] = array("key" => "deleted_date",    "method" => "IS", "value" => null);
            $constraints[] = array("key" => "organisation_id", "method" => "=",  "value" => $organisation_id);
            $constraints[] = array("key" => "applicable_from", "method" => "IS", "value" => null);
            $self = new self();
            return $self->fetchRow($constraints);
        } else {
            return false;
        }
    }

    /**
     * Add the default grading scale for an organisation if it does not already exist
     * Used in migrations and when adding a new organisation to the system
     *
     * @param null $organisation_id
     *
     * @return bool|Models_Gradebook_Grading_Scale
     */
    public static function addDefaultScaleForOrganisation($organisation_id) {
        global $ENTRADA_USER;

        if (isset($organisation_id) && (int)$organisation_id > 0) {
            if (!self::fetchDefaultScaleForOrganisation($organisation_id)) {
                $organisation = Models_Organisation::fetchRowByID($organisation_id);
                $new_scale = new self(array("organisation_id" => $organisation_id,
                                            "title" => "Default Grading Scale for " . $organisation->getOrganisationTitle(),
                                            "updated_date" => time(),
                                            "updated_by" => (!empty($ENTRADA_USER) ? $ENTRADA_USER->getID() : 1)
                                      ));
                if ($new_scale && $new_scale->insert()) {
                    $ranges = array(
                        array("agscale_id" => $new_scale->getID(), "numeric_grade_min" => 90, "letter_grade" => "A+", "gpa" => "4.30", "updated_date" => time(), "updated_by" => (!empty($ENTRADA_USER) ? $ENTRADA_USER->getID() : 1)),
                        array("agscale_id" => $new_scale->getID(), "numeric_grade_min" => 85, "letter_grade" => "A",  "gpa" => "4.00", "updated_date" => time(), "updated_by" => (!empty($ENTRADA_USER) ? $ENTRADA_USER->getID() : 1)),
                        array("agscale_id" => $new_scale->getID(), "numeric_grade_min" => 80, "letter_grade" => "A-", "gpa" => "3.70", "updated_date" => time(), "updated_by" => (!empty($ENTRADA_USER) ? $ENTRADA_USER->getID() : 1)),
                        array("agscale_id" => $new_scale->getID(), "numeric_grade_min" => 77, "letter_grade" => "B+", "gpa" => "3.30", "updated_date" => time(), "updated_by" => (!empty($ENTRADA_USER) ? $ENTRADA_USER->getID() : 1)),
                        array("agscale_id" => $new_scale->getID(), "numeric_grade_min" => 73, "letter_grade" => "B",  "gpa" => "3.00", "updated_date" => time(), "updated_by" => (!empty($ENTRADA_USER) ? $ENTRADA_USER->getID() : 1)),
                        array("agscale_id" => $new_scale->getID(), "numeric_grade_min" => 70, "letter_grade" => "B-", "gpa" => "2.70", "updated_date" => time(), "updated_by" => (!empty($ENTRADA_USER) ? $ENTRADA_USER->getID() : 1)),
                        array("agscale_id" => $new_scale->getID(), "numeric_grade_min" => 67, "letter_grade" => "C+", "gpa" => "2.30", "updated_date" => time(), "updated_by" => (!empty($ENTRADA_USER) ? $ENTRADA_USER->getID() : 1)),
                        array("agscale_id" => $new_scale->getID(), "numeric_grade_min" => 63, "letter_grade" => "C",  "gpa" => "2.00", "updated_date" => time(), "updated_by" => (!empty($ENTRADA_USER) ? $ENTRADA_USER->getID() : 1)),
                        array("agscale_id" => $new_scale->getID(), "numeric_grade_min" => 60, "letter_grade" => "C-", "gpa" => "1.70", "updated_date" => time(), "updated_by" => (!empty($ENTRADA_USER) ? $ENTRADA_USER->getID() : 1)),
                        array("agscale_id" => $new_scale->getID(), "numeric_grade_min" => 57, "letter_grade" => "D+", "gpa" => "1.30", "updated_date" => time(), "updated_by" => (!empty($ENTRADA_USER) ? $ENTRADA_USER->getID() : 1)),
                        array("agscale_id" => $new_scale->getID(), "numeric_grade_min" => 53, "letter_grade" => "D",  "gpa" => "1.00", "updated_date" => time(), "updated_by" => (!empty($ENTRADA_USER) ? $ENTRADA_USER->getID() : 1)),
                        array("agscale_id" => $new_scale->getID(), "numeric_grade_min" => 50, "letter_grade" => "D-", "gpa" => "0.70", "updated_date" => time(), "updated_by" => (!empty($ENTRADA_USER) ? $ENTRADA_USER->getID() : 1)),
                        array("agscale_id" => $new_scale->getID(), "numeric_grade_min" => 0,  "letter_grade" => "F",  "gpa" => "0.00", "updated_date" => time(), "updated_by" => (!empty($ENTRADA_USER) ? $ENTRADA_USER->getID() : 1))
                    );
                    
                    foreach ($ranges as $range) {
                        $new_range = new Models_Gradebook_Grading_Range($range);
                        if ($new_range) {
                            $new_range->insert();
                        }
                    }
                    return $new_scale;
                }
            }
        }
        return false;
    }

    /**
     * Determine the grading scale to use, based on the scale in force at the given date.
     *
     * If no date is provided, it will find the grading scale valid at the current date
     *
     * @param null $at_date - find the grading scale valid at this date
     * @param $organisation - the organisation to search grading scales
     *
     * @return bool|Models_Gradebook_Grading_Scale
     *
     */
    public static function getApplicableGradingScale($organisation, $at_date = null) {
        global $db;
        
        if (!isset($organisation)) {
            return false;
        }

        if (isset($at_date) && (int) $at_date > 0) {
            $use_date = $at_date;
        } else {
            $use_date = time();
        }

        $query = "SELECT * FROM `".static::$database_name."`.`".static::$table_name."`
                  WHERE `deleted_date` IS null
                  AND `organisation_id` = ?
                  AND (`applicable_from` IS null OR ? >= `applicable_from`)
                  ORDER BY `applicable_from` DESC
                  LIMIT 1";
        $result = $db->GetRow($query, array($organisation, $use_date));
        if ($result) {
            return new self($result);
        } else {
            return false;
        }
    }

    /**
     * Get the letter grade corresponding to a percentage grade in the current object's table
     *
     * @param $percent
     *
     * @return bool|string
     */
    public function getLetterGradeForNumeric($percent) {
        $range = Models_Gradebook_Grading_Range::getRangeForNumericGrade($this->agscale_id, $percent);
        if ($range) {
            return $range->getLetterGrade();
        } else {
            return false;
        }
    }

    /**
     * Get the GPA corresponding to a percentage grade in the current object's table
     *
     * @param $percent
     *
     * @return bool|string
     */
    public function getGpaForNumeric($percent) {
        $range = Models_Gradebook_Grading_Range::getRangeForNumericGrade($this->agscale_id, $percent);
        if ($range) {
            return $range->getGpa();
        } else {
            return false;
        }
    }

    /**
     * Get the Notes corresponding to a percentage grade in the current object's table
     *
     * @param $percent
     *
     * @return bool|string
     */
    public function getNotesForNumeric($percent) {
        $range = Models_Gradebook_Grading_Range::getRangeForNumericGrade($this->agscale_id, $percent);
        if ($range) {
            return $range->getNotes();
        } else {
            return false;
        }
    }
}
