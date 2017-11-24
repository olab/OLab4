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
 * @author Unit: School of Medicine
 * @author Developer: Jonatan Caraballo <jch9@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

if ((isset($_SESSION["isAuthorized"])) && ((bool) $_SESSION["isAuthorized"])) {
    ob_clear_open_buffers();

    if (isset($_POST["method"]) && $tmp_input = clean_input($_POST["method"], array("trim", "striptags"))) {
        $method = $tmp_input;
    } else {
        add_error($translate->_("No method supplied."));
    }

    switch ($method) {
        case "get-rooms-by-building":
            if (isset($_POST["building_id"]) && $tmp_input = (int) clean_input($_POST["building_id"], array("trim", "int"))) {
                $building_id = $tmp_input;
            }

            $room_array = array();

            $rooms = Models_Location_Room::fetchAllByBuildingId($building_id);
            if ($rooms) {
                foreach ($rooms as $room) {
                    $room_array[] = array(
                        "room_id" => $room->getRoomId(),
                        "room_name" => $room->getRoomNumber() . ($room->getRoomName() ? " - " . $room->getRoomName() : "")
                    );
                }
            }

            echo json_encode(array("status" => (empty($room_array) ? "error" : "success"), "data" => $room_array));

            break;
        case "get-room-building-id":

            if (isset($_POST["room_id"]) && $tmp_input = (int) clean_input($_POST["room_id"], array("trim", "int"))) {
                $room_id = $tmp_input;
            }

            $rooms = Models_Location_Room::fetchRowByID($room_id);
            $building_id = ($rooms ? $rooms->getBuildingId() : 0);

            echo json_encode(array("status" => ($building_id == 0 ? "error" : "success"), "data" => $building_id));

            break;
    }
} else {
    application_log("error", "Location Management API accessed without valid session_id.");
    echo htmlspecialchars(json_encode(array("error" => "Location Management API accessed without valid session_id.")), ENT_NOQUOTES);
    exit;
}
