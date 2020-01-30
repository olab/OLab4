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
 * @author Organisation: Queen's University
 * @author Developer: Jordan L <jl250@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Response_Description_Option extends Models_Base {
    protected $rdoption_id, $ardescriptor_id, $option, $option_value, $organisation_id, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "cbl_assessments_lu_response_description_options";
    protected static $primary_key = "rdoption_id";
    protected static $default_sort_column = "rdoption_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->rdoption_id;
    }

    public function getArdescriptorID() {
        return $this->ardescriptor_id;
    }

    public function getOption() {
        return $this->option;
    }

    public function getOptionValue() {
        return $this->option_value;
    }

    public function getOrganisationID() {
        return $this->organisation_id;
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

    public function fetchRowByID($id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "id", "value" => $id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public function fetchRowByIDIgnoreDeletedDate($id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "id", "value" => $id, "method" => "=")
        ));
    }
}