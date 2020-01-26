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
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_WEEKS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("weekcontent", "update", true)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/weeks?section=delete", "title" => $translate->_("Remove Weeks"));

    if (isset($_POST["remove_ids"]) && is_array($_POST["remove_ids"]) && !empty($_POST["remove_ids"])) {
        foreach ($_POST["remove_ids"] as $id) {
            $PROCESSED["remove_ids"][] = (int)$id;
        }
    }

    if ($PROCESSED["remove_ids"]) {
        switch ($STEP) {
        case 2:
            if (Models_Week::removeAllByIDs($PROCESSED["remove_ids"])) {
                $SUCCESS++;
                $SUCCESSSTR[] = "Successfully removed the specified " . $translate->_("Weeks") . " from the system.<br />You will now be redirected to the " . $translate->_("Weeks") ." index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".ENTRADA_URL."/admin/weeks\" style=\"font-weight: bold\">click here</a> to continue.";
            } else {
                $ERROR++;
                $ERRORSTR[] = "An error occurred while removing the specified " . $translate->_("Weeks") . " from the system. The system administrator has been notified. You will now be redirected to the " . $translate->_("Weeks") . " index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".ENTRADA_URL."/admin/weeks\" style=\"font-weight: bold\">click here</a> to continue.";
                application_log("error", "An error occurred while removing the " . $translate->_("Weeks") . " [" . implode(", ", $PROCESSED["remove_ids"]) . "] from the system.");
            }
            if ($SUCCESS) {
                echo display_success();
            }
            if ($NOTICE) {
                echo display_notice();
            }
            $ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/weeks\\'', 5000)";
            break;
        case 1:
        default:
            $weeks = Models_Week::fetchAllByIDs($PROCESSED["remove_ids"]);
            $confirm = function () use ($translate, $weeks) {
                add_notice("Please review the following " . $translate->_("Weeks") . " to ensure that you wish to <strong>remove</strong> them from the system.");
                echo display_notice();
                ?>
                <form action ="<?php echo ENTRADA_URL."/admin/weeks?section=delete&step=2";?>" method="post">
                    <table class="tableList" cellspacing="0" summary="<?php echo $translate->_("List of Weeks"); ?>">
                        <colgroup>
                            <col class="modified"/>
                            <col class="title"/>
                            <col class="general"/>
                        </colgroup>
                        <thead>
                            <tr>
                                <td class="modified">&nbsp;</td>
                                <td class="title"><?php echo $translate->_("Week"); ?></td>
                                <td class="general"> </td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($weeks as $week): ?>
                                <tr>
                                    <td><input type="checkbox" value="<?php echo $week->getID();?>" name ="remove_ids[]" checked="checked"/></td>
                                    <td><?php echo $week->getWeekTitle();?></td>
                                    <td>&nbsp;</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <br />
                    <input type="submit" value="Confirm Removal" class="btn btn-danger"/>
                </form>
                <?php
            };
            $confirm();
            break;
        }
    } else {
        $ERROR++;
        $ERRORSTR[] = "No " . $translate->_("Weeks") . " were selected to be removed. You will now be redirected to the " . $translate->_("Weeks") . " index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".ENTRADA_URL."/admin/weeks\" style=\"font-weight: bold\">click here</a> to continue.";
        echo display_error();
    }
}
/* vim: set expandtab: */
