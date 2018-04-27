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
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "delete", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {

    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/manage/flags?org=".$ORGANISATION_ID."&section=delete", "title" => "Delete Flag Severity");

    echo "<h1>".$translate->_("Delete Flag Severity")."</h1>";

    if (isset($_POST["remove-ids"])) {
        foreach ($_POST["remove-ids"] as $flag_id) {
            if ($tmp_input = clean_input($flag_id, "numeric")) {
                $PROCESSED["delete"][] = $tmp_input;
                $flags[] = Models_Assessments_Flag::fetchRowByID($tmp_input);
            }
        }
    }

    switch ($STEP) {
        case 2 :
            if (is_array($flags)) {

                foreach ($flags as $flag) {
                    $flag_error = false;
                    if (!$flag->delete()) {
                        $flag_error = true;
                    }

                    if ($flag_error) {
                        add_error("Failed to delete Flag Severity [<strong>" . $flag->getTitle() . "</strong>], an Administrator has been informed, please try again later. You will now be redirected to the Flag Severity index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"" . ENTRADA_URL . "/admin/settings/manage/flags?org=" . $ORGANISATION_ID . "\"><strong>click here</strong></a> to continue.");
                    }
                }
                if (!has_error()) {
                    add_success("The selected Flag Severity were deleted from the system. You will now be redirected to the Flag Severity index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"" . ENTRADA_URL . "/admin/settings/manage/flags?org=" . $ORGANISATION_ID . "\"><strong>click here</strong></a> to continue.");
                }
            }
            break;
    }

    switch ($STEP) {
        case 2 :
            if ($ERROR) {
                echo display_error();
            }
            if ($SUCCESS) {
                echo display_success();
            }
            $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings/manage/flags?org=".$ORGANISATION_ID."\\'', 5000)";
            break;
        case 1 :
        default :

            if (isset($flags) && is_array($flags)) { ?>
                <div class="alert alert-info">You have selected the following Flag Severit<?php echo count($flags) > 1 ? "ies" : "y"; ?> to be deleted. Please confirm below that you would like to delete them.</div>
                <form action="<?php echo ENTRADA_URL."/admin/settings/manage/flags?org=".$ORGANISATION_ID; ?>&section=delete&step=2" method="POST" id="flags-list">
                    <table class="table table-striped" width="100%" cellpadding="0" cellspacing="0" border="0">
                        <thead>
                        <tr>
                            <th width="5%"></th>
                            <th>Name</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        foreach ($flags as $flag) {
                        ?>
                            <tr class="descriptor" data-id="<?php echo html_encode($flag->getID()); ?>">
                                <td><input class="delete" type="checkbox" name="remove-ids[<?php echo html_encode($flag->getID()); ?>]" value="<?php echo html_encode($flag->getID()); ?>" <?php echo html_encode((in_array($flag->getID(), $PROCESSED["delete"]) ? "checked=\"checked\"" : "")); ?> /></td>
                                <td><a href="<?php echo ENTRADA_URL."/admin/settings/manage/flags?org=".$ORGANISATION_ID; ?>&section=edit&flag=<?php echo html_encode($flag->getID()); ?>"><?php echo html_encode($flag->getTitle()); ?></a></td>
                            </tr>
                        <?php
                        }
                        ?>
                        </tbody>
                    </table>
                    <div class="row-fluid">
                        <a href="<?php echo ENTRADA_URL."/admin/settings/manage/flags?org=".$ORGANISATION_ID; ?>" class="btn" role="button">Cancel</a>
                        <input type="submit" class="btn btn-primary pull-right" value="Delete" />
                    </div>
                </form>
            <?php
            } else {
            ?>
                <div class="alert alert-info">No Flag Severities have been selected to be deleted. Please <a href="<?php echo ENTRADA_URL."/admin/settings/manage/flags?org=".$ORGANISATION_ID; ?>">click here</a> to return to the Flag Severity index.</div>
            <?php
            }

            break;
    }
}
