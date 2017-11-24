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
 * @author Developer: Eric Howarth <eric.howarth@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
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

    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/manage/gradingscale?org=".$ORGANISATION_ID."&section=delete&id=".$RECORD_ID, "title" => "Delete Grading Scales");

    echo "<h1>".$translate->_("Delete Grading Scale")."</h1>";

    if (isset($_POST["remove-ids"])) {
        foreach ($_POST["remove-ids"] as $grading_scale_id) {
            if ($tmp_input = clean_input($grading_scale_id, "numeric")) {
                $PROCESSED["delete"][] = $tmp_input;
                $grading_scales[] = Models_Gradebook_Grading_Scale::fetchRowByID($tmp_input);
            }
        }
    }

    switch ($STEP) {
        case 2 :
            if (is_array($grading_scales)) {

                foreach ($grading_scales as $scale) {
                    $scale_error = false;
                    $scale->setDeletedDate(time());
                    $scale->setDeletedBy($ENTRADA_USER->getID());
                    if (!($scale->update())) {
                        $scale_error = true;
                    }

                    $range_error = false;
                    if ($ranges = Models_Gradebook_Grading_Range::fetchAllByScale($scale->getID())) {
                        foreach ($ranges as $range) {
                            $range->setDeletedDate(time());
                            $range->setDeletedBy($ENTRADA_USER->getID());
                            if (!($range->update())) {
                                $range_error = true;
                            }
                        }
                    }
                    if ($scale_error || $range_error) {
                        add_error("Failed to delete Grading Scale [<strong>" . $scale->getTitle() . "</strong>], an Administrator has been informed, please try again later. You will now be redirected to the Grading Scale index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"" . ENTRADA_URL . "/admin/settings/manage/gradingscale?org=" . $ORGANISATION_ID . "\"><strong>click here</strong></a> to continue.");
                    }
                }
                if (!has_error()) {
                    add_success("The selected Grading Scales were deleted from the system. You will now be redirected to the Grading Scale index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"" . ENTRADA_URL . "/admin/settings/manage/gradingscale?org=" . $ORGANISATION_ID . "\"><strong>click here</strong></a> to continue.");
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
            $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings/manage/gradingscale?org=".$ORGANISATION_ID."\\'', 5000)";
            break;
        case 1 :
        default :

            if (isset($grading_scales) && is_array($grading_scales)) { ?>
                <div class="alert alert-info">You have selected the following Grading Scales to be deleted. Please confirm below that you would like to delete them.</div>
                <form action="<?php echo ENTRADA_URL."/admin/settings/manage/gradingscale?org=".$ORGANISATION_ID; ?>&section=delete&step=2" method="POST" id="gradingscale-list">
                    <table class="table table-striped" width="100%" cellpadding="0" cellspacing="0" border="0">
                        <thead>
                        <tr>
                            <th width="5%"></th>
                            <th>Name</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        foreach ($grading_scales as $scale) {
                        ?>
                            <tr class="descriptor" data-id="<?php echo html_encode($scale->getID()); ?>">
                                <td><input class="delete" type="checkbox" name="remove-ids[<?php echo html_encode($scale->getID()); ?>]" value="<?php echo html_encode($scale->getID()); ?>" <?php echo html_encode((in_array($scale->getID(), $PROCESSED["delete"]) ? "checked=\"checked\"" : "")); ?> /></td>
                                <td class="name"><a href="<?php echo ENTRADA_URL."/admin/settings/manage/gradingscale?org=".$ORGANISATION_ID; ?>&section=edit&id=<?php echo html_encode($scale->getID()); ?>"><?php echo html_encode($scale->getTitle()); ?></a></td>
                            </tr>
                        <?php
                        }
                        ?>
                        </tbody>
                    </table>
                    <div class="row-fluid">
                        <a href="<?php echo ENTRADA_URL."/admin/settings/manage/gradingscale?org=".$ORGANISATION_ID; ?>" class="btn" role="button">Cancel</a>
                        <input type="submit" class="btn btn-primary pull-right" value="Delete" />
                    </div>
                </form>
            <?php
            } else {
            ?>
                <div class="alert alert-info">No Grading Scales have been selected to be deleted. Please <a href="<?php echo ENTRADA_URL."/admin/settings/manage/gradingscale?org=".$ORGANISATION_ID; ?>">click here</a> to return to the Grading Scale index.</div>
            <?php
            }

            break;
    }
}
