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
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Distribution_Delegation extends Models_Base {
    protected $addelegation_id, $adistribution_id, $delegator_id, $delegator_type, $completed_by, $completed_date, $completed_reason, $created_by, $created_date, $updated_date, $updated_by, $start_date, $end_date, $delivery_date, $deleted_date, $deleted_by;

    protected static $table_name = "cbl_assessment_distribution_delegations";
    protected static $primary_key = "addelegation_id";
    protected static $default_sort_column = "addelegation_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->addelegation_id;
    }

    public function getDelegatorID() {
        return $this->delegator_id;
    }

    public function getDistributionID() {
        return $this->adistribution_id;
    }

    public function getAdistributionID() {
        return $this->adistribution_id;
    }

    public function getDelegatorType() {
        return $this->delegator_type;
    }

    public function getStartDate() {
        return $this->start_date;
    }

    public function getEndDate() {
        return $this->end_date;
    }

    public function getCompletedBy() {
        return $this->completed_by;
    }

    public function getCompletedDate() {
        return $this->completed_date;
    }

    public function getDeletedBy() {
        return $this->deleted_by;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function getDeliveryDate() {
        return $this->delivery_date;
    }

    public function getCompletedReason() {
        return $this->completed_reason;
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

    public function setCompletedReason ($reason_text = NULL) {
        $this->completed_reason = $reason_text;
    }

    public function setCompleted ($completed_by = NULL, $completed_date = NULL) {
        $this->completed_by = $completed_by;
        if ($completed_date && is_int($completed_date)) {
            $this->completed_date = $completed_date;
        } else {
            $this->completed_date = time();
        }
    }

    /**
     * Method to set all completion related variables at once.
     *
     * @param string $completed_reason
     * @param int $completed_date
     * @param int $completed_by
     * @param int $updated_date
     * @param int $updated_by
     */
    public function setComplete ($completed_reason = NULL, $completed_date = NULL, $completed_by = NULL, $updated_date = NULL, $updated_by = NULL) {
        $this->completed_reason = $completed_reason;

        if (!$this->completed_by) {
            $this->completed_by = $completed_by;
        }
        if ($completed_date && is_int($completed_date)) {
            $this->completed_date = $completed_date;
        } else {
            $this->completed_date = time();
        }
        if ($updated_date && is_int($updated_date)) {
            $this->updated_date = $updated_date;
        } else {
            $this->updated_date = time();
        }
        if ($updated_by && is_int($updated_by)) {
            $this->updated_by = $updated_by;
        }
    }

    public function setUpdatedBy($id) {
        $this->updated_by = $id;
    }

    public function setUpdatedDate($updated = null) {
        $this->updated_date = ($updated) ? $updated : time();
    }

    public function setDeletedBy($id) {
        $this->deleted_by = $id;
    }

    public function setDeletedDate($deleted_date = null) {
        $this->deleted_date = ($deleted_date === null) ? time() : $deleted_date;
    }

    public static function fetchRowByID($addelegation_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "addelegation_id", "value" => $addelegation_id, "method" => "=")
        ));
    }

    public static function fetchRowByDistributionIDDelegatorIDStartDateEndDate ($distribution_id, $delegator_id, $start_date = null, $end_date = null) {
        $self = new self();
        $result = $self->fetchAllByDistributionIDDelegatorIDStartDateEndDate ($distribution_id, $delegator_id, $start_date, $end_date);
        if ($result && !empty($result)) {
            return array_shift($result);
        }
        return false;
    }

    public static function fetchRowByDistributionIDDelegatorIDDeliveryDateStartDateEndDate ($distribution_id, $delegator_id, $delivery_date, $start_date, $end_date) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "adistribution_id", "value" => $distribution_id, "method" => "="),
            array("key" => "delegator_id", "value" => $delegator_id, "method" => "="),
            array("key" => "delivery_date", "value" => $delivery_date, "method" => "="),
            array("key" => "start_date", "value" => $start_date, "method" => "="),
            array("key" => "end_date", "value" => $end_date, "method" => "=")
        ));
    }

    public static function fetchAllByDistributionIDDelegatorIDDeliveryDateStartDateEndDate ($distribution_id, $delegator_id, $delivery_date, $start_date = null, $end_date = null) {
        $self = new self();

        $fetch = array(
            array("key" => "adistribution_id", "value" => $distribution_id, "method" => "="),
            array("key" => "delegator_id", "value" => $delegator_id, "method" => "="),
            array("key" => "delivery_date", "value" => $delivery_date, "method" => "=")
        );

        if ($start_date == null) {
            $fetch[] = array("key" => "start_date", "value" => null, "method" => "IS");
        } else {
            $fetch[] = array("key" => "start_date", "value" => $start_date, "method" => "=");

        }

        if ($end_date == null) {
            $fetch[] = array("key" => "end_date", "value" => null, "method" => "IS");
        } else {
            $fetch[] = array("key" => "end_date", "value" => $end_date, "method" => "=");
        }

        return $self->fetchAll($fetch);
    }

    public static function fetchAllByDistributionIDDelegatorIDStartDateEndDate ($distribution_id, $delegator_id, $start_date = null, $end_date = null) {
        $self = new self();

        $fetch = array(
            array("key" => "adistribution_id", "value" => $distribution_id, "method" => "="),
            array("key" => "delegator_id", "value" => $delegator_id, "method" => "=")
        );

        if ($start_date == null) {
            $fetch[] = array("key" => "start_date", "value" => null, "method" => "IS");
        } else {
            $fetch[] = array("key" => "start_date", "value" => $start_date, "method" => "=");

        }

        if ($end_date == null) {
            $fetch[] = array("key" => "end_date", "value" => null, "method" => "IS");
        } else {
            $fetch[] = array("key" => "end_date", "value" => $end_date, "method" => "=");
        }

        return $self->fetchAll($fetch);
    }

    public static function fetchAllByDistributionIDDelegatorIDStartDateEndDateIncomplete ($distribution_id, $delegator_id, $start_date, $end_date) {
        $self = new self();

        $fetch = array(
            array("key" => "adistribution_id", "value" => $distribution_id, "method" => "="),
            array("key" => "delegator_id", "value" => $delegator_id, "method" => "="),
            array("key" => "completed_date", "value" => null, "method" => "IS"),
            array("key" => "start_date", "value" => $start_date, "method" => ">="),
            array("key" => "end_date", "value" => $end_date, "method" => "<=")
        );
        return $self->fetchAll($fetch);
    }

    public static function fetchAllByCreatedDateIncomplete ($lower_range, $upper_range) {
        $self = new self();

        $fetch = array(
            array("key" => "created_date", "value" => $lower_range, "method" => ">="),
            array("key" => "created_date", "value" => $upper_range, "method" => "<="),
            array("key" => "completed_date", "value" => null, "method" => "IS")
        );
        return $self->fetchAll($fetch);
    }

    public static function fetchAllCompletedByDistributionID($adistribution_id, $include_deleted = null) {
        $self = new self();
        $options = array(
            array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "="),
            array("key" => "completed_date", "value" => null, "method" => "IS NOT")
        );
        if ($include_deleted) {
            $options[] = array("key" => "deleted_date", "value" => 1, "method" => ">=");
        }
        return $self->fetchAll($options);
    }

    public static function fetchAllByDistributionID($adistribution_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "addelegation_id", "value" => 0, "method" => ">=")));
    }

    public static function fetchAllByAddelegatorID ($proxy_id = null) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "addelegator_id", "value" => $proxy_id, "method" => "=")));
    }

    public function delete() {
        global $db;
        if ($db->Execute("DELETE FROM `".static::$table_name."` WHERE `".static::$primary_key."` = ".$this->getID())) {
            return $this;
        } else {
            application_log("error", "Error deleting  ".get_called_class()." id[" . $this->{static::$primary_key} . "]. DB Said: " . $db->ErrorMsg());
            return false;
        }
    }
}