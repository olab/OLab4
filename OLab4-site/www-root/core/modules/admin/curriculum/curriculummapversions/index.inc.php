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
 * @author Organisation: University of British Columbia
 * @author Unit: Faculty of Medicine
 * @author Developer: Matt Simpson <simpson@queensu.ca> and friends.
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 *
 */

if (!defined("PARENT_INCLUDED") || !defined("IN_CURRICULUM_MAP_VERSIONS")) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "read", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    ?>
    <h1><?php echo $translate->_("Curriculum Map Versions"); ?></h1>

    <a class="btn btn-primary pull-right space-below" href="<?php echo ENTRADA_RELATIVE; ?>/admin/curriculum/curriculummapversions?section=add&amp;org=<?php echo $ORGANISATION_ID; ?>"><i class="icon-plus-sign icon-white"></i> <?php echo $translate->_("Add Version"); ?></a>

    <div class="clearfix"></div>

    <?php
    $version = new Models_Curriculum_Map_Versions();
    $results = $version->fetchAllRecords($ORGANISATION_ID);
    if ($results) {
        ?>
        <form action ="<?php echo ENTRADA_URL;?>/admin/curriculum/curriculummapversions?section=delete&amp;org=<?php echo $ORGANISATION_ID; ?>" method="post">
            <table class="table table-striped" summary="<?php echo $translate->_("Curriculum Map Versions"); ?>">
                <colgroup>
                    <col style="width: 3%" />
                    <col style="width: 82%" />
                    <col style="width: 15%" />
                </colgroup>
                <thead>
                    <tr>
                        <th>&nbsp;</th>
                        <th><?php echo $translate->_("Curriculum Map Versions"); ?></th>
                        <th><?php echo $translate->_("Status"); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                foreach ($results as $result) {
                    echo "<tr>\n";
                    echo "  <td><input type=\"checkbox\" name = \"remove_ids[]\" value=\"" . $result["version_id"] . "\" /></td>\n";
                    echo "  <td><a href=\"".ENTRADA_RELATIVE."/admin/curriculum/curriculummapversions?section=edit&amp;org=" . $ORGANISATION_ID . "&amp;id=" . $result["version_id"] . "\">" . $result["title"] . "</a></td>\n";
                    echo "  <td><a href=\"".ENTRADA_RELATIVE."/admin/curriculum/curriculummapversions?section=edit&amp;org=" . $ORGANISATION_ID . "&amp;id=" . $result["version_id"] . "\">" . ucwords($result["status"]) . "</a></td>\n";
                    echo "</tr>\n";
                }
                ?>
                </tbody>
            </table>
            <input type="submit" class="btn btn-danger" value="Delete Selected" />
        </form>
        <?php
    } else {
        add_notice("There are currently no Curriculum Map Versions in this organisation.");
        echo display_notice();
    }
}
