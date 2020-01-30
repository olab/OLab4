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
 * A model for handling Assessment Type Organisations
 *
 * @author Organisation: Queen's University
 * @author Developer: Thaisa Almeida <trda@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Type_Organisation extends Models_Base {
    protected $atype_organisation_id, $assessment_type_id, $organisation_id, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "cbl_assessment_type_organisations";
    protected static $primary_key = "atype_organisation_id";
    protected static $default_sort_column = "atype_organisation_id";

    public function getID () {
        return $this->atype_organisation_id;
    }

    public function getAssessmentTypeID () {
        return $this->assessment_type_id;
    }

    public function getOrganisationID () {
        return $this->organisation_id;
    }

    public function getCreatedDate () {
        return $this->created_date;
    }

    public function getCreatedBy () {
        return $this->created_by;
    }

    public function getUpdatedDate () {
        return $this->updated_date;
    }

    public function getUpdatedBy () {
        return $this->updated_by;
    }

    public function getDeletedDate () {
        return $this->deleted_date;
    }

    public static function fetchRowByOrganisationAssessmentTypeID($organisation_id, $assessment_type_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "organisation_id", "method" => "=", "value" => $organisation_id),
            array("key" => "assessment_type_id", "method" => "=", "value" => $assessment_type_id)
        ));
    }
}