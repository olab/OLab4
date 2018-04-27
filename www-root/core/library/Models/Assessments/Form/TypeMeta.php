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
 * A model to handle form type meta data
 *
 * @author Organisation: Queen's University
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Form_TypeMeta extends Models_Base {

    protected $form_type_meta_id;
    protected $form_type_id;
    protected $organisation_id;
    protected $meta_name;
    protected $meta_value;
    protected $active;
    protected $created_date;
    protected $created_by;
    protected $updated_date;
    protected $updated_by;
    protected $deleted_date;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "cbl_assessment_form_type_meta";
    protected static $primary_key = "form_type_meta_id";
    protected static $default_sort_column = "form_type_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->form_type_meta_id;
    }

    public function getFormTypeMetaID() {
        return $this->form_type_meta_id;
    }

    public function setFormTypeMetaID($form_type_meta_id) {
        $this->form_type_meta_id = $form_type_meta_id;
    }

    public function getFormTypeID() {
        return $this->form_type_id;
    }

    public function setFormTypeID($form_type_id) {
        $this->form_type_id = $form_type_id;
    }

    public function getOrganisationID() {
        return $this->organisation_id;
    }

    public function setOrganisationID($organisation_id) {
        $this->organisation_id = $organisation_id;
    }

    public function getMetaName() {
        return $this->meta_name;
    }

    public function setMetaName($meta_name) {
        $this->meta_name = $meta_name;
    }

    public function getMetaValue() {
        return $this->meta_value;
    }

    public function setMetaValue($meta_value) {
        $this->meta_value = $meta_value;
    }

    public function getActive() {
        return $this->active;
    }

    public function setActive($active) {
        $this->active = $active;
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

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;
    }

    public static function fetchRowByID($form_type_meta_id, $active) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "form_type_meta_id", "method" => "=", "value" => $form_type_meta_id),
            array("key" => "active", "method" => "=", "value" => $active)
        ));
    }

    public static function fetchAllRecords($active) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "active", "method" => "=", "value" => $active)));
    }

    public static function fetchAllByFormTypeIDOrganisationID($form_type_id, $organisation_id, $active = 1) {
        $self = new self();

        return $self->fetchAll(array(
            array("key" => "active", "method" => "=", "value" => $active),
            array("key" => "organisation_id", "method" => "=", "value" => $organisation_id),
            array("key" => "form_type_id", "method" => "=", "value" => $form_type_id),
            array("key" => "deleted_date", "method" => "IS", "value" => null),
        ));
    }
}