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
 * This file is part of the building management feature and lists the buildings
 * in the system for the currently active organisation.
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

    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/manage/locations?org=".$ORGANISATION['organisation_id'], "title" => "Manage Rooms");
    $HEAD[] = "<link href=\"".ENTRADA_URL."/css/tabpane.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />\n";
    $HEAD[] = "<style type=\"text/css\">.dynamic-tab-pane-control .tab-page { height:auto; }</style>\n";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/tabpane/tabpane.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";

    $MODULE_TEXT   = $translate->_($MODULE);
    $BUILDING_TEXT = $MODULE_TEXT["building"]["building"]["manage"];
    $ROOM_TEXT     = $MODULE_TEXT["building"]["room"]["manage"];

    ?>
    <h1><?php echo $BUILDING_TEXT["manage_rooms"]?></h1>
            <!-- Rooms for the buildings -->
            <div class="row-fluid">
                <div class="pull-right">
                    <a id="add_new_room" href="<?php echo ENTRADA_URL; ?>/admin/settings/manage/locations?section=add-room&amp;org=<?php echo $ORGANISATION_ID;?>" class="btn btn-primary">Add New Room</a>
                </div>
            </div><br/>
            <?php
            $rooms = Models_Location_Room::fetchAllByOrgId($ORGANISATION_ID);
            if ($rooms && is_array($rooms) && !empty($rooms)) {
            ?>
               <div class="row-fluid">
                <form action ="<?php echo ENTRADA_URL;?>/admin/settings/manage/locations?section=delete-room&amp;org=<?php echo $ORGANISATION_ID;?>" method="post">
                    <table class="tableList" cellspacing="0" cellpadding="1" border="0" summary="<?php echo $ROOM_TEXT["label_list"];?>">
                        <colgroup>
                            <col class="modified"/>
                            <col class="title" />
                            <col class="active" />
                        </colgroup>
                        <thead>
                            <tr>
                                <td class="modified">&nbsp;</td>
                                <td class="title" width="650">
                                    <?php echo $ROOM_TEXT["label_room"];?>
                                </td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                foreach ($rooms as $room) {
                                    if ($room && is_object($room)) {
                                        $building = $room->getBuilding();
                                        $link_url = ENTRADA_URL."/admin/settings/manage/locations?section=edit-room&amp;org=".$ORGANISATION_ID."&amp;room_id=" . $room->getID();
                                        $link_text = $building->getBuildingCode() . " " . $room->getRoomNumber() . " (" . $building->getBuildingName() . ")";
                                        echo "<tr>\n";
                                        echo "<td>\n";
                                        echo "<input type=\"checkbox\" name = \"remove_ids[]\" value=\"" . $room->getID() . "\"/>\n";
                                        echo "</td>\n";
                                        echo "<td>\n";
                                        echo "<a href=\"" . $link_url . "\">\n";
                                        echo $link_text;
                                        echo "</a>\n";
                                        echo "</td>\n";
                                        echo "</tr>\n";
                                    }
                                }
                            ?>
                        </tbody>
                    </table>
                    <br />
                    <input type="submit" class="btn btn-danger" value="<?php echo $ROOM_TEXT["delete_selected"];?>" />
                </form>
               </div>
            <?php
            } else {
                add_notice($ROOM_TEXT["no_rooms"] . $translate->_("organisation"));
                echo display_notice();
            }
            ?>
<?php
}