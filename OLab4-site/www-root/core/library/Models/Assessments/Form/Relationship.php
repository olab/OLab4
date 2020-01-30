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
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Form_Relationship extends Models_Base {
    protected $frelationship_id, $form_id, $first_parent_id, $immediate_parent_id;

    protected static $table_name = "cbl_assessments_lu_form_relationships";
    protected static $primary_key = "frelationship_id";
    protected static $default_sort_column = "frelationship_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->frelationship_id;
    }

    public function getFrelationshipID() {
        return $this->frelationship_id;
    }

    public function getFormID() {
        return $this->form_id;
    }

    public function getFirstParentID() {
        return $this->first_parent_id;
    }

    public function getImmediateParentID() {
        return $this->immediate_parent_id;
    }

    public static function fetchRowByID($frelationship_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "frelationship_id", "value" => $frelationship_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "frelationship_id", "value" => 0, "method" => ">=")));
    }
}