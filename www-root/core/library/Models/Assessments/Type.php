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
 * Model for Assessment Types
 *
 * @author Organisation: Queen's University
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Type extends Models_Base {

    protected $assessment_type_id;
    protected $title;
    protected $description;
    protected $shortname;
    protected $created_date;
    protected $created_by;
    protected $updated_by;
    protected $updated_date;
    protected $deleted_date;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "cbl_assessment_lu_types";
    protected static $primary_key = "assessment_type_id";
    protected static $default_sort_column = "assessment_type_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->assessment_type_id;
    }

    public function getAssessmentTypeID() {
        return $this->assessment_type_id;
    }

    public function setAssessmentTypeID($assessment_type_id) {
        $this->assessment_type_id = $assessment_type_id;
    }

    public function getTitle() {
        return $this->title;
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function getDescription() {
        return $this->description;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public function getShortname() {
        return $this->shortname;
    }

    public function setShortname($shortname) {
        $this->shortname = $shortname;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function setCreatedDate($created_date) {
        $this->created_date = $created_date;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function setCreatedBy($created_by) {
        $this->created_by = $created_by;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;
    }

    public static function fetchRowByID($assessment_type_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "assessment_type_id", "method" => "=", "value" => $assessment_type_id)
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "assessment_type_id", "method" => ">=", "value" => 0)));
    }

    public static function fetchRowByShortname($shortname) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "shortname", "method" => "=", "value" => $shortname)
        ));
    }

    public static function fetchAssessmentTypeIDByShortname($shortname)
    {
        $self = new self();
        $row = $self->fetchRow(array(
            array("key" => "shortname", "method" => "=", "value" => $shortname)
        ));
        if (empty($row)) {
            return false;
        }
        return $row->getID();
    }
}