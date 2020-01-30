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

class Models_Assessments_Distribution_Delegator extends Models_Base {
    protected $addelegator_id, $adistribution_id, $delegator_type, $delegator_id, $start_date, $end_date;

    protected static $table_name = "cbl_assessment_distribution_delegators";
    protected static $primary_key = "addelegator_id";
    protected static $default_sort_column = "addelegator_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->addelegator_id;
    }

    public function getAddelegatorID() {
        return $this->addelegator_id;
    }

    public function getAdistributionID() {
        return $this->adistribution_id;
    }

    public function getDelegatorType() {
        return $this->delegator_type;
    }

    public function getDelegatorID() {
        return $this->delegator_id;
    }

    public function getStartDate() {
        return $this->start_date;
    }

    public function getEndDate() {
        return $this->end_date;
    }

    public static function fetchRowByID($addelegator_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "addelegator_id", "value" => $addelegator_id, "method" => "=")
        ));
    }

    public static function fetchRowByDistributionID($adistribution_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "adistribution_id", "value" => $adistribution_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "addelegator_id", "value" => 0, "method" => ">=")));
    }

    public static function fetchAllByDelegatorID ($proxy_id = null) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "delegator_id", "value" => $proxy_id, "method" => "=")));
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