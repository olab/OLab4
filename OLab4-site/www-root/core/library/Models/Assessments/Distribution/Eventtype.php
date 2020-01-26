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
 * A model for handling distribution event types
 *
 * @author Organisation: Queen's University
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Distribution_Eventtype extends Models_Base {
    protected $deventtype_id, $adistribution_id, $eventtype_id;

    protected static $table_name = "cbl_assessment_distribution_eventtypes";
    protected static $primary_key = "deventtype_id";
    protected static $default_sort_column = "deventtype_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->deventtype_id;
    }

    public function getAdistributionID() {
        return $this->adistribution_id;
    }

    public function getEventtypeID() {
        return $this->eventtype_id;
    }

    public static function fetchRowByID($deventtype_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "deventtype_id", "value" => $deventtype_id, "method" => "=")
        ));
    }

    public static function fetchAllByDistributionID($distribution_id = null) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "adistribution_id", "value" => $distribution_id, "method" => "=")
        ));
    }

    public static function fetchAllByEventTypeID($event_type_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "eventtype_id", "value" => $event_type_id, "method" => "=")
        ));
    }

    public static function fetchAllByEventTypeIDGroupedByDistributionID($event_type_id) {
        global $db;
        $event_types = false;

        $query = "  SELECT * FROM `cbl_assessment_distribution_eventtypes` 
                    WHERE `eventtype_id` = ?                
                    GROUP BY `adistribution_id`";

        $results = $db->GetAll($query, array($event_type_id));

        if ($results) {
            foreach ($results as $result) {
                $event_type = new self($result);
                $event_types[] = $event_type;
            }
        }

        return $event_types;
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "deventtype_id", "value" => 0, "method" => ">=")));
    }


    public static function fetchAllByAdistributionID ($id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "adistribution_id", "value" => $id, "method" => "=")
        ));
    }

    public function fetchEventTypes ($distribution_id = null, $active = 1) {
        global $db;
        $eventtypes = array();
        $query = "  SELECT a.*, b.* FROM `cbl_assessment_distribution_eventtypes` AS a
                    JOIN `events_lu_eventtypes` AS b
                    ON a.`eventtype_id` = b.`eventtype_id`
                    WHERE a.`adistribution_id` = ?
                    AND b.`eventtype_active` = ?";

        $results = $db->GetAll($query, array($distribution_id, $active));
        if ($results) {
            foreach ($results as $result) {
                $eventtypes[] = $result;
            }
        }
        return $eventtypes;
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