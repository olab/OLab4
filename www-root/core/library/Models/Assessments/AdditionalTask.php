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
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Assessments_AdditionalTask extends Models_Base {
    protected $additional_task_id, $adistribution_id, $target_id, $assessor_value, $assessor_type, $delivery_date, $created_by, $created_date, $deleted_by, $deleted_date;

    protected static $table_name = "cbl_assessment_additional_tasks";
    protected static $primary_key = "additional_task_id";
    protected static $default_sort_column = "additional_task_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->additional_task_id;
    }

    public function getADistributionID() {
        return $this->adistribution_id;
    }

    public function getTargetID() {
        return $this->target_id;
    }

    public function getADAssessorID() {
        return $this->assessor_value;
    }

    public function getAssessorType() {
        return $this->assessor_type;
    }

    public function getAssessorValue() {
        return $this->assessor_value;
    }

    public function getDeliveryDate() {
        return $this->delivery_date;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function getDeletedBy() {
        return $this->deleted_by;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public static function fetchRowByID($additional_task_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "additional_task_id", "value" => $additional_task_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchRowDistributionIDTargetID($adistribution_id, $target_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "="),
            array("key" => "target_id", "value" => $target_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchRowByADistributionIDAssessorTypeAssessorValueTargetIDDeliveryDate($adistribution_id, $assessor_type, $assessor_value, $target_id, $delivery_date, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "="),
            array("key" => "assessor_type", "value" => $assessor_type, "method" => "="),
            array("key" => "assessor_value", "value" => $assessor_value, "method" => "="),
            array("key" => "target_id", "value" => $target_id, "method" => "="),
            array("key" => "delivery_date", "value" => $delivery_date, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllByADistributionID($adistribution_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
                array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "="),
                array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
            )
        );
    }

    public static function fetchAllByADistributionIDAssessorTypeAssessorValueDeliveryDate($adistribution_id, $assessor_type, $assessor_value, $delivery_date, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "="),
            array("key" => "assessor_type", "value" => $assessor_type, "method" => "="),
            array("key" => "assessor_value", "value" => $assessor_value, "method" => "="),
            array("key" => "delivery_date", "value" => $delivery_date, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))));
    }
}