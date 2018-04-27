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
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CONFIGURATION"))) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool)$_SESSION["isAuthorized"]) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "read", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:" . html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" . html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance.");
    echo display_error();
    application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] do not have access to this module [" . $MODULE . "]");
} else {
    $flags = Models_Assessments_Flag::fetchAllByOrganisation($ORGANISATION_ID, "", true, true, false, true);

    $HEAD[] = '<script>var ORGANISATION_ID = "' . $ORGANISATION_ID . '";</script>';
    $HEAD[] = "<script type=\"text/javascript\">var FLAGS_COLOR_PALETTE = " . json_encode($translate->_("flags_color_palette")) . ";</script>\n";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.iris.min.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/color-picker.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>\n";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/settings/flags-admin.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>\n";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/assessments/assessments.css\" />";

    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/color-picker.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>\n";
    ?>
    <h1>Assessment Flag Severity</h1>

    <div class="row-fluid">
        <span class="pull-right">
            <a class="btn btn-success" href="<?php echo ENTRADA_RELATIVE; ?>/admin/settings/manage/flags?section=add&amp;org=<?php echo $ORGANISATION_ID; ?>"><i class="icon-plus-sign icon-white"></i> <?php echo $translate->_("Add New Flag Severity"); ?></a>
        </span>
    </div>
    <br/>

    <?php if (empty($flags)):

        echo display_notice("There are no Flag Severities to display");

    else : ?>

        <form action="<?php echo ENTRADA_URL . "/admin/settings/manage/flags?org=" . $ORGANISATION_ID; ?>&section=delete" method="POST">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th width="5%"></th>
                    <th width="75%"><?php echo $translate->_("Title"); ?></th>
                    <th><?php echo $translate->_("Color"); ?></th>
                    <th><?php echo $translate->_("Value"); ?></th>
                    <th><?php echo $translate->_("Visibility"); ?></th>
                    <th>&nbsp;</th>
                </tr>
                </thead>
                <tbody class="sortable-items">
                <?php foreach ($flags as $flag): ?>
                    <tr class="flags-table-row" data-ordinal="<?php echo $flag->getOrdering(); ?>">
                        <td><input type="checkbox" name="remove-ids[]" value="<?php echo $flag->getID(); ?>"/></td>
                        <td><a href="<?php echo ENTRADA_URL . "/admin/settings/manage/flags?org=" . $ORGANISATION_ID . "&flag=" . $flag->getID(); ?>&section=edit"><?php echo $flag->getTitle(); ?></td>
                        <td>
                            <div class="flags-color-thumb" style="background-color: <?php echo $flag->getColor(); ?>"></div>
                        </td>
                        <td class="text-center"><?php echo $flag->getFlagValue(); ?></td>
                        <td class="text-center"><?php echo $flag->getVisibility(); ?></td>
                        <td class="move-item-response"><a href="#"><i class="icon-move"></i></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <br/>
            <input type="submit" value="Delete Selected" class="btn btn-danger"/>
        </form>
    <?php endif;
}
