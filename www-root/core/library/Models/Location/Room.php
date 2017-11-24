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
 * A model for handling Rooms.
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2016 Regents of The University of California. All Rights Reserved.
 */

class Models_Location_Room extends Models_Base {
    protected   $room_id,
                $building_id,
                $room_number,
                $room_name,
                $room_description,
                $room_max_occupancy,
                $building;

    protected static $table_name            = "global_lu_rooms";
    protected static $primary_key           = "room_id";
    protected static $default_sort_column   = "room_number";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->room_id;
    }

    public function getRoomId() {
        return $this->room_id;
    }

    public function getBuildingId() {
        return $this->building_id;
    }

    public function getRoomNumber() {
        return $this->room_number;
    }

    public function getRoomName() {
        return $this->room_name;
    }
    public function getRoomDescription() {
        return $this->room_description;
    }
    public function getRoomMaxOccupancy() {
        return $this->room_max_occupancy;
    }

    /* @return bool|Models_Location_Building */
    public function getBuilding() {
        if (NULL === $this->building) {
            $this->building = Models_Location_Building::fetchRowByID($this->getBuildingId());
        }

        return $this->building;
    }

    /* @return bool|Models_Location_Room */
    public static function fetchRowByID($room_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "room_id", "value" => $room_id, "method" => "=")
        ));
    }

    /* @return bool|Models_Location_Room */
    public static function fetchRowByNumber($room_number, $building_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "room_number", "value" => $room_number, "method" => "="),
            array("key" => "building_id", "value" => $building_id, "method" => "=")
        ));
    }

    /* @return ArrayObject|Models_Location_Room[] */
    public static function fetchAllByOrgId($organisation_id) {
        global $db;

        $items = false;

        $query = "  SELECT `global_lu_rooms`.`room_id`, `global_lu_rooms`.`building_id`, `global_lu_rooms`.`room_number`, IF(`global_lu_rooms`.`room_name` IS NULL,CONCAT(`global_lu_buildings`.`building_code`, ' ', `global_lu_rooms`.`room_number`),`global_lu_rooms`.`room_name`) AS `room_name`,`global_lu_rooms`.`room_description`,`global_lu_rooms`.`room_max_occupancy`
                    FROM `global_lu_buildings`, `global_lu_rooms`
                    WHERE `global_lu_rooms`.`building_id` = `global_lu_buildings`.`building_id`
                    AND `global_lu_buildings`.`organisation_id` = " . $db->qstr($organisation_id)."
                    ORDER BY `room_name` ASC";
        $results = $db->GetAll($query);

        if ($results) {
            foreach ($results as $result) {
                $item = new self($result);
                $items[] = $item;
            }
        }

        return $items;
    }

    /* @return ArrayObject|Models_Location_Room[] */
    public static function fetchAllByBuildingId($building_id) {
        global $db;

        $items = false;

        $query = "  SELECT `global_lu_rooms`.`room_id`, `global_lu_rooms`.`building_id`, `global_lu_rooms`.`room_number`, IF(`global_lu_rooms`.`room_name` IS NULL,CONCAT(`global_lu_buildings`.`building_code`, ' ', `global_lu_rooms`.`room_number`),`global_lu_rooms`.`room_name`) AS `room_name`,`global_lu_rooms`.`room_description`,`global_lu_rooms`.`room_max_occupancy`
                    FROM `global_lu_buildings`, `global_lu_rooms`
                    WHERE `global_lu_rooms`.`building_id` = `global_lu_buildings`.`building_id`
                    AND `global_lu_rooms`.`building_id` = " . $db->qstr($building_id)."
                    ORDER BY `room_name` ASC";
        $results = $db->GetAll($query);

        if ($results) {
            foreach ($results as $result) {
                $item = new self($result);
                $items[] = $item;
            }
        }

        return $items;
    }

    /* @return ArrayObject|Models_Location_Room[] */
    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "room_id", "value" => 0, "method" => ">=")));
    }
}