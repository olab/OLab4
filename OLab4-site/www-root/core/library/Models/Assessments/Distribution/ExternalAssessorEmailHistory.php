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
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Distribution_ExternalAssessorEmailHistory extends Models_Base {
    protected $id, $eassessor_id, $email, $created_by, $created_date, $updated_by, $updated_date, $deleted_date;
    
    protected static $table_name = "cbl_external_assessor_email_history";
    protected static $primary_key = "id";
    protected static $default_sort_column = "id";

    public function getId() {
        return $this->id;
    }

    public function getExternalAssessorID() {
        return $this->eassessor_id;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setCreatedBy($created_by) {
        $this->created_by = $created_by;
    }

    public function setCreatedDate($created_date) {
        $this->created_date = $created_date;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;
    }

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;
    }

    public function fetchRowByID($id) {
        $self = new self();
        $constraints = array(
            array("key" => "id", "value" => $id, "method" => "=")
        );
        return $self->fetchRow($constraints);
    }

    public function insertExternalAssessorEmailHistory($eassessor_id, $email) {
        global $ENTRADA_USER, $translate;

        $email_history = new Models_Assessments_Distribution_ExternalAssessorEmailHistory(array(
                "eassessor_id" => $eassessor_id,
                "email" => $email,
                "created_by" => $ENTRADA_USER->getActiveID(),
                "created_date" => time(),
                "updated_by" => $ENTRADA_USER->getActiveID(),
                "updated_date" => time(),
                "deleted_date" => NULL
            )
        );

        if (!$email_history->insert()) {
            add_error($translate->_("An error occurred while attempting to insert a email history record"));
        }
    }
}