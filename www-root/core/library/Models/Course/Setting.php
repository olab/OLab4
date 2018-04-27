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
 * A model for handling Course Settings
 *
 * @author Organisation: Queen's University
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Models_Course_Setting extends Models_Base {

    protected $csetting_id;
    protected $course_id;
    protected $organisation_id;
    protected $shortname;
    protected $value;
    protected $created_date;
    protected $created_by;
    protected $updated_date;
    protected $updated_by;
    protected $deleted_date;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "course_settings";
    protected static $primary_key = "csetting_id";
    protected static $default_sort_column = "csetting_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->csetting_id;
    }

    public function getCsettingID() {
        return $this->csetting_id;
    }

    public function setCsettingID($csetting_id) {
        $this->csetting_id = $csetting_id;

        return $this;
    }

    public function getCourseID() {
        return $this->course_id;
    }

    public function setCourseID($course_id) {
        $this->course_id = $course_id;

        return $this;
    }

    public function getOrganisationID() {
        return $this->organisation_id;
    }

    public function setOrganisationID($organisation_id) {
        $this->organisation_id = $organisation_id;

        return $this;
    }

    public function getShortname() {
        return $this->shortname;
    }

    public function setShortname($shortname) {
        $this->shortname = $shortname;

        return $this;
    }

    public function getValue() {
        return $this->value;
    }

    public function setValue($value) {
        $this->value = $value;

        return $this;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function setCreatedDate($created_date) {
        $this->created_date = $created_date;

        return $this;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function setCreatedBy($created_by) {
        $this->created_by = $created_by;

        return $this;
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

    public static function fetchRowByID($csetting_id, $deleted_date) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "csetting_id", "method" => "=", "value" => $csetting_id),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllRecords($deleted_date) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))));
    }

    public function delete() {
        if (empty($this->deleted_date)) {
            $this->deleted_date = time();
        }

        return $this->update();
    }

    /**
     * Fetch all course settings for a specific course
     * @param int $course_id
     * @param null $deleted_date
     * @return array
     */
    public static function fetchAllByCourseID($course_id = 0, $deleted_date = null) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "course_id", "method" => "=", "value" => $course_id),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /**
     * Fetch all course settings for a specific course and setting shortname
     * @param int $course_id
     * @param null $deleted_date
     * @return array
     */
    public static function fetchRowByCourseIDShortname($course_id = 0, $shortname = "", $deleted_date = null) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "course_id", "method" => "=", "value" => $course_id),
            array("key" => "shortname", "method" => "=", "value" => $shortname),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

}