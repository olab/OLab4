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
 * This file deletes a room from the `global_lu_rooms` table.
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
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "delete", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    $MODULE_TEXT   = $translate->_($MODULE);
    $BUILDING_TEXT = $MODULE_TEXT["building"]["room"]["delete"];
    ?>
    <h1>
        <?php echo $BUILDING_TEXT["title"];?>
    </h1>
    <?php
    $BREADCRUMB[]  = array("url" => ENTRADA_URL . "/admin/settings/manage/locations?section=edit&org=" . $ORGANISATION_ID . "&building_id=" . $BUILDING_ID, "title" => "Edit Building");
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/manage/locations?section=delete-room&amp;org=".$ORGANISATION['organisation_id'], "title" => "Delete Rooms");

    if (isset($_POST["remove_ids"]) && is_array($_POST["remove_ids"]) && !empty($_POST["remove_ids"])) {
        foreach ($_POST["remove_ids"] as $id) {
            $PROCESSED["remove_ids"][] = (int)$id;            
        }
    }
    
    if ($PROCESSED["remove_ids"]) {
        switch ($STEP) {
            case 2:
                foreach ($PROCESSED["remove_ids"] as $id) {
                    $query = "DELETE FROM `global_lu_rooms`
                              WHERE `room_id` = ".$db->qstr($id);
                    if (!$db->Execute($query)) {
                        add_error("Error Deleting Room");
                    }
                }

                if (!has_error()) {
                    $url = ENTRADA_URL . "/admin/settings/manage/locations?section=edit&org=" . $ORGANISATION_ID . "&building_id=" . $BUILDING_ID;
                    $success_message = $BUILDING_TEXT["success_msg"]["success_add_1"] . "[<strong>" . count($PROCESSED["remove_ids"]) . "</strong>]" . $BUILDING_TEXT["success_msg"]["success_add_2"];
                    $success_message .= "<br /><br />" . $BUILDING_TEXT["success_msg"]["success_add_3"] . "<strong>" . $BUILDING_TEXT["success_msg"]["success_add_4"] . "</strong>" . $BUILDING_TEXT["success_msg"]["success_add_5"];
                    $success_message .= "<a href=\"" . $url . "\" style = \"font-weight: bold\" >" . $BUILDING_TEXT["success_msg"]["success_add_6"] . "</a>" . $BUILDING_TEXT["success_msg"]["success_add_7"];
                    add_success($success_message);
                    echo display_success();
                } else {
                    echo display_error();
                }
                
                $ONLOAD[] = "setTimeout('window.location=\\'". $url ."\\'', 5000)";
                break;
            case 1:
            default:
                add_notice($BUILDING_TEXT["notice_msg"]["review"]);
                echo display_notice();
            ?>

            <form action ="<?php echo ENTRADA_URL."/admin/settings/manage/locations/?section=delete-room&org=".$ORGANISATION_ID."&building_id=".$BUILDING_ID."&step=2";?>" method="post">
                <table class="tableList" cellspacing="0" summary="<?php echo $BUILDING_TEXT["label_list"];?>">
                    <colgroup>
                        <col class="modified"/>
                        <col class="title"/>
                    </colgroup>
                    <thead>
                        <tr>
                            <td class="modified">&nbsp;</td>
                            <td class="title">
                                <?php echo $BUILDING_TEXT["label_room"];?>
                            </td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($PROCESSED["remove_ids"] as $id) {
                            $room = Models_Location_Room::fetchRowByID($id);
                            if ($room && is_object($room)) {
                                $building = $room->getBuilding();
                                ?>
                                <tr>
                                    <td><input id="room_<?php echo $id;?>" type="checkbox" value="<?php echo $id;?>" name="remove_ids[]" checked="checked"/></td>
                                    <td><label for="room_<?php echo $id;?>"><?php echo $building->getBuildingCode() . " " . $room->getRoomNumber() . " " .  $building->getBuildingName();?></label></td>
                                </tr>
                            <?php }
                            }
                        ?>
                    </tbody>
                </table>
                <br />
                <input type="submit" value="<?php echo $BUILDING_TEXT["label_delete"];?>" class="btn btn-danger"/>
            </form>
            <?php
            break;
        }
    } else {
        $url = ENTRADA_URL . "/admin/settings/manage/locations?section=edit&org=" . $ORGANISATION_ID . "&building_id=" . $BUILDING_ID;
        $error_message .= $BUILDING_TEXT["error_msg"]["error_add_1"] . "<strong>" . $BUILDING_TEXT["error_msg"]["error_add_2"] . "</strong>" . $BUILDING_TEXT["error_add_3"]["success_add_3"];
        $error_message .= "<a href=\"" . $url . "\" style = \"font-weight: bold\" >" . $BUILDING_TEXT["error_msg"]["error_add_4"] . "</a>" . $BUILDING_TEXT["error_msg"]["error_add_5"];
        add_error($error_message);

        echo display_error();
        $ONLOAD[] = "setTimeout('window.location=\\'". $url ."\\'', 5000)";
    }
}