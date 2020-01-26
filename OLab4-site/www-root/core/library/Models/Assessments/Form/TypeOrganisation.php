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

class Models_Assessments_Form_TypeOrganisation extends Models_Base {
    protected $aftype_organisation_id, $organisation_id, $form_type_id, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "cbl_assessments_form_type_organisation";
    protected static $primary_key = "aftype_organisation_id";
    protected static $default_sort_column = "aftype_organisation_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->aftype_organisation_id;
    }

    public function getAftypeOrganisationID() {
        return $this->aftype_organisation_id;
    }

    public function getOrganisationID() {
        return $this->organisation_id;
    }

    public function getFormTypeID() {
        return $this->form_type_id;
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

    public static function fetchRowByID($aftype_org_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "aftype_org_id", "value" => $aftype_org_id, "method" => "=")
        ));
    }

    public static function fetchRowByOrganisationTypeID($organisation_id, $form_type_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "organisation_id", "method" => "=", "value" => $organisation_id),
            array("key" => "form_type_id", "method" => "=", "value" => $form_type_id)
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "aftype_org_id", "value" => 0, "method" => ">=")));
    }

    public static function fetchAllByFormTypeID($form_type_id) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "form_type_id", "value" => $form_type_id, "method" => "=")));
    }
}