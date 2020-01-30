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
    $HEAD[] = "<link href=\"".ENTRADA_URL."/css/tabpane.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />\n";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/tabpane/tabpane.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";

    $MODULE_TEXT   = $translate->_($MODULE);
    $BUILDING_TEXT = $MODULE_TEXT["building"]["building"]["manage"];
    $ROOM_TEXT     = $MODULE_TEXT["building"]["room"]["manage"];

    $buildings = Models_Location_Building::fetchAllByOrganisationID($ORGANISATION_ID);
    ?>
    <h1><?php echo $BUILDING_TEXT["manage_locations"]?></h1>
    <h2><?php echo $BUILDING_TEXT["manage_buildings"]?></h2>

    <div class="row-fluid space-below medium">
        <div class="pull-right">

            <?php
            if ($buildings) {
               echo "<button type=\"submit\" class=\"btn btn-danger space-right\"><i class=\"icon-minus-sign icon-white\"></i> " . $BUILDING_TEXT["delete_selected"] . "</button>";
            }
            ?>

            <a id="add_new_building" href="<?php echo ENTRADA_URL; ?>/admin/settings/manage/locations?section=add&amp;org=<?php echo $ORGANISATION_ID;?>" class="pull-right btn btn-success"><i class="icon-plus-sign icon-white"></i>  <?php echo $BUILDING_TEXT["add_building"];?></a>
        </div>
    </div>
    <?php
    if ($buildings) {
        ?>
        <form action ="<?php echo ENTRADA_URL;?>/admin/settings/manage/locations?section=delete&amp;org=<?php echo $ORGANISATION_ID;?>" method="post">
            <table class="table table-bordered table-striped" cellspacing="0" cellpadding="1" border="0" summary="<?php echo $BUILDING_TEXT["label_list"];?>">
                <colgroup>
                    <col class="modified"/>
                    <col class="title" />
                    <col class="active" />
                </colgroup>
                <thead>
                    <tr>
                        <th class="modified span1">&nbsp;</th>
                        <th class="title span11">
                            <?php echo $BUILDING_TEXT["label_building"];?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        foreach ($buildings as $building) {
                            if ($building && is_object($building)) {
                                echo "<tr>";
                                echo "<td><input type=\"checkbox\" name=\"remove_ids[]\" value=\"" . $building->getID() . "\"/></td>";
                                echo "<td><a href=\"" . ENTRADA_URL . "/admin/settings/manage/locations?section=edit&amp;org=" . $ORGANISATION_ID . "&amp;building_id=" . $building->getID() . "\">" . $building->getBuildingName() . " (" . $building->getBuildingCode() . ")</a></td>";
                                echo "</tr>";
                            }
                        }
                    ?>
                </tbody>
            </table>
        </form>
        <?php
    } else {
        add_notice($BUILDING_TEXT["no_buildings"] . $translate->_("organisation"));
        echo display_notice();
    }
}