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
 * 
 *
 * @author Organisation: Queen's University
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Form_Type extends Models_Base {
    protected $form_type_id, $category, $shortname, $title, $description, $course_related, $active, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "cbl_assessments_lu_form_types";
    protected static $primary_key = "form_type_id";
    protected static $default_sort_column = "form_type_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->form_type_id;
    }

    public function getFormTypeID() {
        return $this->form_type_id;
    }

    public function getShortname() {
        return $this->shortname;
    }

    public function getCategory() {
        return $this->category;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getCourseRelated() {
        return $this->course_related;
    }

    public function getActive() {
        return $this->active;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public static function fetchRowByID($form_type_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "form_type_id", "value" => $form_type_id, "method" => "="),
        ));
    }

    public static function fetchRowByShortname($shortname) {
        $self = new self();
        return $self->fetchRow(array(array("key" => "shortname", "value" => $shortname, "method" => "=")));
    }

    public static function fetchAllRecords($active) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "active", "value" => $active, "method" => "=")));
    }

    /**
     * Fetch all form types that are valid for the given organisation ID.
     *
     * @param $organisation_id
     * @return bool|object array
     */
    public static function fetchAllByOrganisationID($organisation_id) {
        global $db;
        $objects = array();
        $query = "SELECT ft.*
                  FROM `cbl_assessments_form_type_organisation` AS fto
                  JOIN `cbl_assessments_lu_form_types` AS ft ON ft.`form_type_id` = fto.`form_type_id`
                  WHERE fto.`organisation_id` = ?
                  AND ft.`active` = 1
                  AND ft.`deleted_date` IS NULL
                  GROUP BY fto.`form_type_id`";
        if ($resultset = $db->GetAll($query, array($organisation_id))) {
            foreach ($resultset as $result) {
                $object = new self();
                $objects[$result["form_type_id"]] = $object->fromArray($result);
            }
        }
        return $objects;
    }

    /**
     * Fetch all form types that are valid for the given organisation ID.
     *
     * @param $form_type_id
     * @param $organisation_id
     * @return bool|object array
     */
    public static function fetchAllByFormTypeIDOrganisationID($form_type_id, $organisation_id) {
        global $db;
        $query = "SELECT ft.*
                  FROM `cbl_assessments_form_type_organisation` AS fto
                  JOIN `cbl_assessments_lu_form_types` AS ft ON ft.`form_type_id` = fto.`form_type_id`
                  WHERE fto.`organisation_id` = ?
                  AND ft.`active` = 1
                  AND ft.`deleted_date` IS NULL
                  AND ft.`form_type_id` = ?
                  GROUP BY fto.`form_type_id`";
        $object = false;
        if ($resultset = $db->GetRow($query, array($organisation_id, $form_type_id))) {
            $object = new self();
            $object = $object->fromArray($resultset);
        }
        return $object;
    }

    /**
     * Fetch all form types that are valid for the given organisation ID and specified category.
     *
     * @param $organisation_id
     * @param $category
     * @return array
     */
    public static function fetchAllByOrganisationIDCategory($organisation_id, $category) {
        global $db;

        $objects = array();
        $query = "SELECT ft.*
                  FROM `cbl_assessments_form_type_organisation` AS fto
                  JOIN `cbl_assessments_lu_form_types` AS ft ON ft.`form_type_id` = fto.`form_type_id`
                  WHERE fto.`organisation_id` = ?
                  AND ft.category = ?
                  AND ft.`active` = 1
                  AND ft.`deleted_date` IS NULL
                  GROUP BY fto.`form_type_id`";
        if ($resultset = $db->GetAll($query, array($organisation_id, $category))) {
            foreach ($resultset as $result) {
                $object = new self();
                $objects[$result["form_type_id"]] = $object->fromArray($result);
            }
        }
        return $objects;
    }

    /**
     * Fetch a form type that is valid for the given organisation ID.
     *
     * @param $form_type_id
     * @param $organisation_id
     * @return bool|object array
     */
    public static function fetchRowByFormTypeIDOrganisationID($form_type_id, $organisation_id) {
        global $db;
        $query = "SELECT ft.*
                  FROM `cbl_assessments_form_type_organisation` AS fto
                  JOIN `cbl_assessments_lu_form_types` AS ft ON ft.`form_type_id` = fto.`form_type_id`
                  WHERE fto.`organisation_id` = ?
                  AND ft.`active` = 1
                  AND ft.`deleted_date` IS NULL
                  AND ft.`form_type_id` = ?
                  GROUP BY fto.`form_type_id`";
        $object = false;
        if ($resultset = $db->GetRow($query, array($organisation_id, $form_type_id))) {
            $object = new self();
            $object = $object->fromArray($resultset);
        }
        return $object;
    }

    /**
     * Fetch the form type for a form with specified ID
     *
     * @param $form_id
     * @return bool|Models_Base
     */
    public static function fetchRowByFormID($form_id)
    {
        global $db;

        $query = "SELECT a.* 
                  FROM `cbl_assessments_lu_form_types` AS a
                  JOIN `cbl_assessments_lu_forms` AS b
                  WHERE a.`form_type_id` = b.`form_type_id`
                  AND b.`form_id` = ?";

        if ($result = $db->getRow($query, array($form_id))) {
            $self = new self();
            return $self->fromArray($result);
        }

        return false;
    }

    /*
     * Return the form type title for a for associated with an assessment
     *
     * @param $dassessment_id
     * @return mixed
     */
    public static function fetchFormTypeByDAssessmentID($dassessment_id) {
        global $db;

        $query = "SELECT t1.`title` 
                  FROM `cbl_assessments_lu_form_types` AS t1
                  JOIN `cbl_assessments_lu_forms` AS t2 ON t1.`form_type_id` = t2.`form_type_id`
                  JOIN `cbl_distribution_assessments` AS t3 ON t2.`form_id` = t3.`form_id`
                  WHERE t3.`dassessment_id` = ?";

        return  $db->getOne($query, array($dassessment_id));
    }

    /**
     * Fetch all form types that are valid for the given categories.
     *
     * @param $categories
     * @return array
     */
    public static function fetchAllByCategories($categories = []) {
        global $db;

        $objects = [];
        $query = "SELECT ft.*
                  FROM `cbl_assessments_lu_form_types` AS ft
                  WHERE ft.category IN ('" . implode("','", $categories) . "')
                  AND ft.`active` = 1
                  AND ft.`deleted_date` IS NULL";

        if ($resultset = $db->GetAll($query)) {
            foreach ($resultset as $result) {
                $object = new self();
                $objects[] = $object->fromArray($result);
            }
        }
        if (empty($objects)) {
            return false;
        }

        return $objects;
    }
}