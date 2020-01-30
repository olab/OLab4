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
 * This is a script to help migrate over the `event_location` strings to their 
 * corresponding `room_id`s. It is a temporary script.
 *
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Robert Fotino <robert.fotino@gmail.com>
 * @copyright Copyright 2014 David Geffen School of Medicine at UCLA. All Rights Reserved.
 *
 */


if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CONFIGURATION"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "read",false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    $MODULE_TEXT   = $translate->_($MODULE);
    $BUILDING_TEXT = $MODULE_TEXT["building"]["migrate"];

    if ($_POST && is_array($_POST) && count($_POST)) {
        foreach ($_POST as $match_info) {
            $room_id  = (int)$match_info["room_id"];
            $location = $match_info["location"];

            if ($room_id > 0) {
                $updated = Models_Event::migrateEventLocations($ORGANISATION_ID, $room_id, $location);
                if ($updated) {
                    add_success($BUILDING_TEXT["match_01"] . $location . $BUILDING_TEXT["match_02"]);
                }
            }
        }
        echo display_success();
    } else {
        $locations = Models_Event::selectEventLocationsWithOutRoomID($ORGANISATION_ID);
        if ($locations && is_array($locations)) {
            $event_locations = array();
            foreach ($locations as $location) {
                $event_locations[] = $location["event_location"];
            }

            $rooms = Models_Location_Room::fetchAllByOrgId($ORGANISATION_ID);

            if ($rooms && is_array($rooms)) {
                $room_names = array();
                foreach ($rooms as $room) {
                    if ($room && is_object($room)) {
                        $room_names[$room->getID()] = $room->getRoomName();
                    }
                }

                $unmapped = array();
                if ($event_locations && is_array($event_locations)) {
                    foreach ($event_locations as $location) {
                        $found = false;
                        if ($room_names && is_array($room_names)) {
                            foreach ($room_names as $room_id => $room_name) {
                                if ($location == $room_name) {
                                    $updated = Models_Event::migrateEventLocations($ORGANISATION_ID, $room_id, $location);
                                    if ($updated) {
                                        add_success($BUILDING_TEXT["match_01"] . $location . " " . $BUILDING_TEXT["match_02"]);
                                        $found = true;
                                    }
                                }
                            }
                            if (!$found) {
                                $unmapped[] = $location;
                            }
                        }
                    }
                }

                if (count($unmapped)) {
                    //The user will have to manually map these remaining ones
                    echo display_success();

                    add_notice($BUILDING_TEXT["manually_match"]);
                    echo display_notice(); ?>

                    <h1>
                        <?php echo $BUILDING_TEXT["label_event_location"];?>
                    </h1>
                    <form name="map-locations" id="map-locations" method="post">
                        <table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="<?php echo $BUILDING_TEXT["adding_page"];?>">
                            <colgroup>
                                <col style="width: 30%" />
                                <col style="width: 70%" />
                            </colgroup>
                            <tfoot>
                                <tr>
                                    <td colspan="2" style="padding-top: 15px; text-align: right">
                                        <input type="submit" class="btn btn-primary" value="Submit" />
                                    </td>
                                </tr>
                            </tfoot>
                            <tbody>
                                <?php
                                $i = 0;
                                foreach ($unmapped as $location) {
                                    echo "<tr>\n";

                                    echo "<td>\n";
                                    echo "<label for=\"$location\" class=\"form-nrequired\">\n";
                                    echo $BUILDING_TEXT["match"] . $location;
                                    echo "</label>\n";
                                    echo "</td>\n";

                                    echo "<td>\n";
                                    echo "<select name=\"unmapped_" . $i . "[room_id]\" id=\"" . $location . "\">\n";
                                    echo "<option value=\"-1\">" . $BUILDING_TEXT["select_room"] . "</option>\n";
                                    if ($room_names && is_array($room_names)) {
                                        foreach ($room_names as $room_id => $room_name) {
                                            echo "<option value=\"" . $room_id . "\">\n";
                                            echo $room_name;
                                            echo "</option>\n";
                                        }
                                    }
                                    echo "</select>\n";
                                    echo "<input type=\"hidden\" name=\"unmapped_" . $i . "[location]\" value=\"" . $location . "\" />";
                                    echo "</td>\n";
                                    echo "</tr>\n";
                                    $i++;
                                }
                                ?>
                            </tbody>
                        </table>
                    </form>
                <?php
                } else {
                    add_success($BUILDING_TEXT["locations_matched"]);
                    echo display_success();
                }
            } else {
                add_error($BUILDING_TEXT["no_room_names"]);
                echo display_error();
            }
        } else {
            add_error($BUILDING_TEXT["no_unmatched"]);
            echo display_error();
        }
    }
}
