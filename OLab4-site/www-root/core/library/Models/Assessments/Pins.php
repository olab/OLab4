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
 * This model handles assessments that have been pinned by a learner
 *
 * @author Organisation: Queens University
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2017 Queens University. All Rights Reserved.
 */

class Models_Assessments_Pins extends Models_Base {

    protected $pin_id;
    protected $aprogress_id;
    protected $dassessment_id;
    protected $proxy_id;
    protected $pin_type;
    protected $pin_value;
    protected $created_by;
    protected $created_date;
    protected $updated_by;
    protected $updated_date;
    protected $deleted_by;
    protected $deleted_date;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name = "cbl_pins";
    protected static $primary_key = "pin_id";
    protected static $default_sort_column = "proxy_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->pin_id;
    }

    public function getPinID() {
        return $this->pin_id;
    }

    public function setPinID($pin_id) {
        $this->pin_id = $pin_id;

        return $this;
    }

    public function getAprogressId() {
        return $this->aprogress_id;
    }

    public function setAprogressId($aprogress_id) {
        $this->aprogress_id = $aprogress_id;
    }

    public function getPinType() {
        return $this->pin_type;
    }

    public function setPinType($pin_type) {
        $this->pin_type = $pin_type;

        return $this;
    }

    public function getPinValue() {
        return $this->pin_value;
    }

    public function setPinValue($pin_value) {
        $this->pin_value = $pin_value;

        return $this;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function setCreatedBy($created_by) {
        $this->created_by = $created_by;

        return $this;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function setCreatedDate($created_date) {
        $this->created_date = $created_date;

        return $this;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;

        return $this;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;

        return $this;
    }

    public function getDeletedBy() {
        return $this->deleted_by;
    }

    public function setDeletedBy($deleted_by) {
        $this->deleted_by = $deleted_by;

        return $this;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;

        return $this;
    }

    public function getProxyId() {
        return $this->proxy_id;
    }

    public function setProxyId($proxy_id) {
        $this->proxy_id = $proxy_id;
    }

    public function getDassessmentId() {
        return $this->dassessment_id;
    }

    public function setDassessmentId($dassessment_id) {
        $this->dassessment_id = $dassessment_id;
    }

    public static function fetchRowByID($pinned_assessment_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "pin_id", "method" => "=", "value" => $pinned_assessment_id)
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "pin_id", "method" => ">=", "value" => 0)));
    }

    public function delete() {
        if (empty($this->deleted_date)) {
            $this->deleted_date = time();
        }

        return $this->update();
    }

    public static function fetchAssessmentByDassessmentID($dassessment_id, $proxy_id, $include_deleted_pins = false) {
        $self = new self();
        if($include_deleted_pins) {
            return $self->fetchRow(array(
                array("key" => "pin_value", "method" => "=", "value" => $dassessment_id),
                array("key" => "pin_type", "method" => "=", "value" => "assessment"),
                array("key" => "proxy_id", "method" => "=", "value" => $proxy_id)
            ));
        } else {
            return $self->fetchRow(array(
                array("key" => "pin_value", "method" => "=", "value" => $dassessment_id),
                array("key" => "pin_type", "method" => "=", "value" => "assessment"),
                array("key" => "proxy_id", "method" => "=", "value" => $proxy_id),
                array("key" => "deleted_date", "method" => "IS", "value" => NULL)
            ));
        }
    }

    public static function fetchItemByDassessmentID($item_id, $dassessment_id, $proxy_id, $include_deleted_pins = false) {
        $self = new self();
        if($include_deleted_pins) {
            return $self->fetchRow(array(
                array("key" => "pin_value", "method" => "=", "value" => $item_id),
                array("key" => "pin_type", "method" => "=", "value" => "item"),
                array("key" => "proxy_id", "method" => "=", "value" => $proxy_id),
                array("key" => "dassessment_id", "method" => "=", "value" => $dassessment_id)
            ));
        } else {
            return $self->fetchRow(array(
                array("key" => "pin_value", "method" => "=", "value" => $item_id),
                array("key" => "pin_type", "method" => "=", "value" => "item"),
                array("key" => "proxy_id", "method" => "=", "value" => $proxy_id),
                array("key" => "deleted_date", "method" => "IS", "value" => NULL),
                array("key" => "dassessment_id", "method" => "=", "value" => $dassessment_id)
            ));
        }
    }

    public static function fetchAllByProxyID($proxy_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "proxy_id", "method" => "=", "value" => $proxy_id),
            array("key" => "deleted_date", "method" => "IS", "value" => NULL),
            array("key" => "pin_type", "method" => "=", "value" => "item")
        ), "=", "AND", "proxy_id");
    }

    public static function fetchAllByPinTypeProxyID($pin_type = "assessment", $proxy_id = 0) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "proxy_id", "method" => "=", "value" => $proxy_id),
            array("key" => "deleted_date", "method" => "IS", "value" => NULL),
            array("key" => "pin_type", "method" => "=", "value" => $pin_type)
        ), "=", "AND", "proxy_id");
    }

    public static function fetchItemByItemID($item_id, $proxy_id, $include_deleted_pins = false, $dassessment_id) {
        $self = new self();
        if($include_deleted_pins) {
            return $self->fetchRow(array(
                array("key" => "pin_value", "method" => "=", "value" => $item_id),
                array("key" => "pin_type", "method" => "=", "value" => "item"),
                array("key" => "proxy_id", "method" => "=", "value" => $proxy_id),
                array("key" => "dassessment_id", "method" => "=", "value" => $dassessment_id)
            ));
        } else {
            return $self->fetchRow(array(
                array("key" => "pin_value", "method" => "=", "value" => $item_id),
                array("key" => "pin_type", "method" => "=", "value" => "item"),
                array("key" => "proxy_id", "method" => "=", "value" => $proxy_id),
                array("key" => "dassessment_id", "method" => "=", "value" => $dassessment_id),
                array("key" => "deleted_date", "method" => "IS", "value" => NULL)
            ));
        }
    }

    /**
     * Return pinned items or assessments based on the progress ID, pin value and pin type.  Optionally ignore deleted pins
     * @param int $pin_value
     * @param string $pin_type
     * @param int $aprogress_id
     * @param bool $ignore_deleted_pins
     * @param null $deleted_date
     * @return bool|Models_Base
     */
    public function fetchRowByTypeAndIDAndAProgressID($pin_value = 0, $pin_type = "", $aprogress_id = 0, $ignore_deleted_pins = false, $deleted_date = NULL) {
        $self = new self();
        $constraints = array(
            array("key" => "pin_value", "method" => "=", "value" => $pin_value),
            array("key" => "pin_type", "method" => "=", "value" => $pin_type),
            array("key" => "aprogress_id", "method" => "=", "value" => $aprogress_id)
        );
        if ($ignore_deleted_pins) {
            $constraints[] = array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"));
        }
        return $self->fetchRow($constraints);
    }
}