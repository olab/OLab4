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
 * This file edits a room in the `global_lu_rooms` table.
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
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "create", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:" . html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" . html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance.");

    echo display_error();

    application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] do not have access to this module [" . $MODULE . "]");
} else {
    $BREADCRUMB[]  = array("url" => ENTRADA_URL . "/admin/settings/manage/locations?section=edit&org=" . $ORGANISATION_ID . "&building_id=" . $BUILDING_ID, "title" => "Edit Building");
    $BREADCRUMB[]  = array("url" => ENTRADA_URL . "/admin/settings/manage/locations?" . replace_query(array("section" => "edit-room")) . "&amp;org=" . $ORGANISATION_ID, "title" => "Edit Room");
    $MODULE_TEXT   = $translate->_($MODULE);
    $BUILDING_TEXT = $MODULE_TEXT["building"]["room"];

    if ((isset($_GET["room_id"])) && ($room_id = clean_input($_GET["room_id"], array("int")))) {
        $PROCESSED["room_id"] = (int)$room_id;
    }

    // Error Checking
    switch ($STEP) {
        case 2 :

            /**
             * Required field "building_id" / Building
             */
            if (isset($_POST["building_id"]) && ($building_id = clean_input($_POST["building_id"], "int")) && $building_id != 0) {
                $building = Models_Location_Building::fetchRowByID($building_id);
                if ($building && is_object($building)) {
                    $PROCESSED["building_id"] = $building->getID();
                }
            } else {
                add_error($BUILDING_TEXT["error_msg"]["name"]);
            }

            /**
             * Required field "room_number" / Room Number
             */

            if (isset($_POST["room_number"]) && ($room_number = clean_input($_POST["room_number"], array("notags", "trim"))) && strlen($room_number) <= 20) {
                $PROCESSED["room_number"] = $room_number;
            } else {
                add_error($BUILDING_TEXT["error_msg"]["room_name"]);
            }
            if (isset($_POST["room_name"]) && ($room_name = clean_input($_POST["room_name"], array("notags", "trim")))) {
                $PROCESSED["room_name"] = $room_name;
            } else {
                $PROCESSED["room_name"] = "";
            }

            if (isset($_POST["room_description"]) && ($room_description = clean_input($_POST["room_description"], array("notags", "trim")))) {
                $PROCESSED["room_description"] = $room_description;
            } else {
                $PROCESSED["room_description"] = "";
            }

            if (isset($_POST["room_max_occupancy"]) && ($room_max_occupancy = clean_input($_POST["room_max_occupancy"], array("trim", "numeric", "int")))) {
                $PROCESSED["room_max_occupancy"] = $room_max_occupancy;
            } else {
                $PROCESSED["room_max_occupancy"] = 0;
            }

            if (!$ERROR) {
                $room = Models_Location_Room::fetchRowByID($PROCESSED["room_id"]);
                if ($room && is_object($room)) {
                    $room->fromArray($PROCESSED);

                    if ($room->update()) {
                        $url = ENTRADA_URL . "/admin/settings/manage/locations?section=edit&org=" . $ORGANISATION_ID . "&building_id=" . $PROCESSED["building_id"];

                        $success_message = $BUILDING_TEXT["success_msg"]["success_update_1"] . "<strong>" . html_encode($PROCESSED["room_number"]) . "</strong>" . $BUILDING_TEXT["success_msg"]["success_update_2"];
                        $success_message .= "<br /><br />" . $BUILDING_TEXT["success_msg"]["success_update_3"] . "<strong>" . $BUILDING_TEXT["success_msg"]["success_update_4"] . "</strong>" . $BUILDING_TEXT["success_msg"]["success_update_5"];
                        $success_message .= "<a href=\"" . $url . "\" style = \"font-weight: bold\" >" . $BUILDING_TEXT["success_msg"]["success_update_6"] . "</a>" . $BUILDING_TEXT["success_msg"]["success_update_7"];
                        add_success($success_message);

                        $ONLOAD[] = "setTimeout('window.location=\\'" . $url . "\\'', 5000)";
                        application_log("success", "Building [" . $PROCESSED["room_number"] . "] was changed in the system.");
                    } else {
                        add_error($BUILDING_TEXT["error_msg"]["insert1"]);
                        application_log("error", "There was an error inserting a Room. Database said: " . $db->ErrorMsg());
                    }
                }
            }

            if ($ERROR) {
                $STEP = 1;
            }
            break;
        case 1 :
        default :
            $room = Models_Location_Room::fetchRowByID($PROCESSED["room_id"]);
            if ($room && is_object($room)) {
                $PROCESSED["building_id"] = $room->getBuildingId();
                $PROCESSED["room_number"] = $room->getRoomNumber();
                $PROCESSED["room_name"] = $room->getRoomName();
                $PROCESSED["room_description"] = $room->getRoomDescription();
                $PROCESSED["room_max_occupancy"] = $room->getRoomMaxOccupancy();
            }

            break;
    }

    // Display Content
    switch ($STEP) {
        case 2 :
            if ($SUCCESS) {
                echo display_success();
            }

            if ($NOTICE) {
                echo display_notice();
            }

            if ($ERROR) {
                echo display_error();
            }
            break;
        case 1 :
        default:
            if ($ERROR) {
                echo display_error();
            }
    ?>
        <h1><?php echo $BUILDING_TEXT["edit_room"];?></h1>

    <form class="form-horizontal" action="<?php echo ENTRADA_URL . "/admin/settings/manage/locations" . "?" . replace_query(array("step" => 2)) . "&org=" . $ORGANISATION_ID; ?>" id="room_edit_form" method="post">

        <h2><?php echo $BUILDING_TEXT["room_information"];?></h2>

        <div class="control-group">
            <label for="building_id" class="control-label form-required">
                <?php echo $BUILDING_TEXT["label_building"];?>
            </label>
            <div class="controls">
                <?php
                    $buildings = Models_Location_Building::fetchAllByOrganisationID($ORGANISATION_ID);
                    if ($buildings && is_array($buildings) && !empty($buildings)) {
                        echo "<select class=\"input-large\" id=\"building_id\" name=\"building_id\">\n";
                        echo "<option value=\"0\">" . $BUILDING_TEXT["select_building"] . "</option>\n";
                        foreach ($buildings as $building) {
                            if ($building && is_object($building)) {
                                echo "<option value=\"" . (int)$building->getID() . "\"" . (isset($PROCESSED["building_id"]) && $PROCESSED["building_id"] == $building->getID() ? " selected=\"selected\"" : "") . ">" . html_encode($building->getBuildingName()) . "</option>\n";
                            }
                        }
                        echo "</select>";
                    } else {
                        echo "<input type=\"hidden\" id=\"building_id\" name=\"building_id\" value=\"0\" />\n";
                        echo $BUILDING_TEXT["info_not_available"] . "\n";
                    }
                ?>
            </div>
        </div>

        <div class="control-group">
            <label for="room_name" class="control-label">
                <?php echo $BUILDING_TEXT["label_name"];?>
            </label>
            <div class="controls">
                <input class="input-large" type="text" id="room_name" name="room_name" maxlength="100" value="<?php if (isset($PROCESSED["room_name"])) { echo $PROCESSED["room_name"]; } ?>"/>
            </div>
        </div>

        <div class="control-group">
            <label for="room_number" class="control-label form-required">
                <?php echo $BUILDING_TEXT["label_number"];?>
            </label>
            <div class="controls">
                <input class="input-large" type="text" id="room_number" name="room_number" maxlength="20" value="<?php if (isset($PROCESSED["room_number"])) { echo $PROCESSED["room_number"]; } ?>" />
            </div>
        </div>

        <div class="control-group">
            <label for="room_description" class="control-label">
                <?php echo $BUILDING_TEXT["label_description"];?>
            </label>
            <div class="controls">
                <textarea class="input-large" cols="40" rows="2" id="room_description" name="room_description" maxlength="255"><?php if (isset($PROCESSED["room_description"])) { echo $PROCESSED["room_description"]; } ?></textarea>
            </div>
        </div>

        <div class="control-group">
            <label for="room_max_occupancy" class="control-label">
                <?php echo $BUILDING_TEXT["label_max_occupancy"];?>
            </label>
            <div class="controls">
                <input class="input-large" type="number" id="room_max_occupancy" min="0" name="room_max_occupancy" value="<?php if (isset($PROCESSED["room_max_occupancy"])) { echo $PROCESSED["room_max_occupancy"]; } else { echo 0; } ?>" maxlength="4"/>
            </div>
        </div>

        <div class="control-group">
            <div class="controls">
                <input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/settings/manage/locations?section=edit&org=<?php echo $ORGANISATION_ID; ?>&building_id=<?php echo $PROCESSED["building_id"]; ?>'" />
                <input type="submit" class="btn btn-primary" value="<?php echo $translate->_("global_button_save"); ?>" />
            </div>
        </div>

    </form>
    <?php
    break;
    }
}
