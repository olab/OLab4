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
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 */

/**
 * This Model is for an obsolete database table.
 */
class Models_Assessments_AssessmentAssessor extends Models_Base {
    protected $aassessor_id, $dassessment_id, $adistribution_id, $assessor_type, $assessor_value, $delegation_list_id, $published, $created_date, $created_by, $updated_date, $updated_by, $deleted_date;

    protected static $table_name = "cbl_distribution_assessment_assessors";
    protected static $primary_key = "aassessor_id";
    protected static $default_sort_column = "aassessor_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->aassessor_id;
    }

    public function getDAssessmentID() {
        return $this->dassessment_id;
    }

    public function getADistributionID() {
        return $this->adistribution_id;
    }

    public function getAssessorType() {
        return $this->assessor_type;
    }

    public function getAssessorValue() {
        return $this->assessor_value;
    }

    public function getDelegationListID() {
        return $this->delegation_list_id;
    }

    public function getPublished() {
        return $this->published;
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

    public function setDeletedDate ($deleted_date) {
        $this->deleted_date = $deleted_date;
        return $this;
    }

    public static function fetchAllByDistributionIDAssessorTypeAssessorValue ($distribution_id = null, $assessor_type = "proxy_id", $assessor_value = null, $delegation_list_id = null, $deleted_date = null) {
        $self = new self();
        return $self->fetchAll(array(
                array("key" => "adistribution_id", "method" => "=", "value" => $distribution_id),
                array("key" => "assessor_type", "method" => "=", "value" => $assessor_type),
                array("key" => "assessor_value", "method" => "=", "value" => $assessor_value),
                array("key" => "delegation_list_id", "method" => "=", "value" => ($delegation_list_id ? $delegation_list_id : NULL), "method" => ($delegation_list_id ? "=" : "IS")),
                array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
            )
        );
    }

    public static function fetchRowByDistributionIDAssessorTypeAssessorValue ($distribution_id = null, $assessor_value = null) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "adistribution_id", "method" => "=", "value" => $distribution_id),
                array("key" => "assessor_type", "method" => "=", "value" => "proxy_id"),
                array("key" => "assessor_value", "method" => "=", "value" => $assessor_value),
            )
        );
    }

    public static function fetchRowByDistributionIDDAssessmentIDAssessorValue ($distribution_id = null, $dassessment_id = null, $assessor_value = null) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "adistribution_id", "method" => "=", "value" => $distribution_id),
                array("key" => "dassessment_id", "method" => "=", "value" => $dassessment_id),
                array("key" => "assessor_value", "method" => "=", "value" => $assessor_value),
            )
        );
    }

    public static function fetchAllByDistributionIDListID ($distribution_id, $delegation_list_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
                array("key" => "adistribution_id", "method" => "=", "value" => $distribution_id),
                array("key" => "delegation_list_id", "method" => "=", "value" => $delegation_list_id, "method" => "="),
                array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
            )
        );
    }

    public static function fetchAllByAssessorTypeAssessorValue ($distribution_id = null, $assessor_type = "proxy_id", $assessor_value = null, $deleted_date = null) {
        $self = new self();
        return $self->fetchAll(array(
                array("key" => "adistribution_id", "method" => "=", "value" => $distribution_id),
                array("key" => "assessor_type", "method" => "=", "value" => $assessor_type),
                array("key" => "assessor_value", "method" => "=", "value" => $assessor_value),
                array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
            )
        );
    }

    public static function fetchAllByDistributionID ($distribution_id, $deleted_date = NULL, $published = NULL) {
        $self = new self();
        $constraints = array(
            array("key" => "adistribution_id", "method" => "=", "value" => $distribution_id),
            array("key" => "delegation_list_id", "method" => "IS NOT", "value" => null),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        );
        if ($published || $published === false) {
            $constraints[] = array("key" => "published", "method" => "=", "value" => ($published ? "1" : "0"));
        }
        return $self->fetchAll($constraints);
    }

    public static function fetchAllByAssessmentID ($dassessment_id = null, $deleted_date = null) {
        $self = new self();
        return $self->fetchAll(array(
                array("key" => "dassessment_id", "method" => "=", "value" => $dassessment_id),
                array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
            )
        );
    }

    public function getAssessorName() {
        global $db;
        $name = "N/A";
        if ($this->getAssessorType() == "external_hash") {
            $query = "SELECT `firstname`, `lastname`
                        FROM `cbl_external_assessors`
                        WHERE `eassessor_id` = ?";
        } else {
            $query = "SELECT `firstname`, `lastname`
                        FROM `" . AUTH_DATABASE . "`.`user_data`
                        WHERE `id` = ?";
        }

        $result = $db->GetRow($query, array($this->getAssessorValue()));
        if ($result) {
            $name = $result["firstname"] . " " . $result["lastname"];
        }

        return $name;
    }

    public function getAssessorEmail() {
        global $db;
        $email = "N/A";
        if ($this->getAssessorType() == "external_hash") {
            $query = "SELECT `email`
                        FROM `cbl_external_assessors`
                        WHERE `eassessor_id` = ?";
        } else {
            $query = "SELECT `email`
                        FROM `" . AUTH_DATABASE . "`.`user_data`
                        WHERE `id` = ?";
        }

        $result = $db->GetRow($query, array($this->getAssessorValue()));
        if ($result) {
            $email = $result["email"];
        }

        return $email;
    }

    public static function getMaxDelegationListIDByDistributionID($distribution_id) {
        global $db;

        $output = 1;

        $query = "SELECT MAX(`delegation_list_id`)
                    FROM `" . static::$database_name . "`.`" . static::$table_name . "`
                    WHERE `adistribution_id` = ?";

        $list_id = $db->GetOne($query, array($distribution_id));
        if (((int)$list_id)) {
            $output = ((int)$list_id);
        }

        return $output;
    }
}