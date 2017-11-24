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
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_ASSESSMENT_RESPONSE_CATEGORIES"))) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "delete", false)) {
    add_error($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance."));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {

    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/manage/assessmentresponsecategories?org=".$ORGANISATION_ID."&section=delete&id=".$RECORD_ID, "title" => $translate->_("Delete Assessment Response Categories"));

    echo "<h1>".$translate->_("Delete Assessment Response Categories")."</h1>";

    if (isset($_POST["checked"])) {
        foreach ($_POST["checked"] as $descriptor_id) {
            if ($tmp_input = clean_input($descriptor_id, "numeric")) {
                $PROCESSED["delete"][] = $tmp_input;
                $descriptors[] =  Models_Assessments_Response_Descriptor::fetchRowByID($tmp_input);
            }
        }
    }

    switch ($STEP) {
        case 2 :
            if (isset($descriptors) && is_array($descriptors)) {
                foreach ($descriptors as $descriptor) {
                    $descriptor_data = $descriptor->toArray();
                    $descriptor_data["deleted_date"] = time();

                    if ($descriptor->fromArray($descriptor_data)->update()) {
                        add_statistic("assessment_response_category", "delete", "erdescriptor_id", $descriptor->getID(), $ENTRADA_USER->getID());

                        if (!$ERROR) {
                            add_success($translate->_("Successfully deleted an Assessment Response Category [<strong>".$descriptor->getDescriptor()."</strong>]."));
                        }
                    } else {
                        add_error($translate->_("Failed to delete a Assessment Response Category [<strong>".$descriptor->getDescriptor()."</strong>], an Administrator has been informed, please try again later. You will now be redirected to the Assessment Response Categories index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".ENTRADA_URL."/admin/settings/manage/assessmentresponsecategories?org=".$ORGANISATION_ID."\"><strong>click here</strong></a> to continue."));
                        application_log("error", "Failed to delete Assessment Response Category, DB said: ".$db->ErrorMsg());
                    }
                }
            } else {
                add_success($translate->_("No Assessment Response Categories were selected, so no Assessment Response Categories were deleted. You will now be redirected to the Assessment Response Categories index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".ENTRADA_URL."/admin/settings/manage/assessmentresponsecategories?org=".$ORGANISATION_ID."\"><strong>click here</strong></a> to continue."));
            }
            break;
    }

    switch ($STEP) {
        case 2 :
            if ($ERROR) {
                echo display_error();
            }
            if ($SUCCESS) {
                add_success($translate->_("You will now be redirected to the Assessment Response Categories index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".ENTRADA_URL."/admin/settings/manage/assessmentresponsecategories?org=".$ORGANISATION_ID."\"><strong>click here</strong></a> to continue."));
                echo display_success();
            }
            $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings/manage/assessmentresponsecategories?org=".$ORGANISATION_ID."\\'', 5000)";
            break;
        case 1 :
        default :

            if (isset($descriptors) && is_array($descriptors)) { ?>
                <div class="alert alert-info"><?php echo $translate->_("You have selected the following Assessment Response Categories to be deleted. Please confirm below that you would like to delete them.")?></div>
                <form action="<?php echo ENTRADA_URL."/admin/settings/manage/assessmentresponsecategories?org=".$ORGANISATION_ID; ?>&section=delete&step=2" method="POST" id="descriptors-list">
                    <table class="table table-striped table-bordered" width="100%" cellpadding="0" cellspacing="0" border="0">
                        <thead>
                        <tr>
                            <th width="5%"></th>
                            <th><?php echo $translate->_("Name")?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($descriptors as $descriptor) { ?>
                            <tr class="descriptor" data-id="<?php echo html_encode($descriptor->getID()); ?>">
                                <td><input type="checkbox" name="checked[<?php echo html_encode($descriptor->getID()); ?>]" value="<?php echo html_encode($descriptor->getID()); ?>" <?php echo html_encode((in_array($descriptor->getID(), $PROCESSED["delete"]) ? "checked=\"checked\"" : "")); ?> /></td>
                                <td class="name"><a href="<?php echo ENTRADA_URL."/admin/settings/manage/assessmentresponsecategories?org=".$ORGANISATION_ID; ?>&section=edit&id=<?php echo html_encode($descriptor->getID()); ?>"><?php echo html_encode($descriptor->getDescriptor()); ?></a></td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                    <div class="row-fluid">
                        <a href="<?php echo ENTRADA_URL."/admin/settings/manage/assessmentresponsecategories?org=".$ORGANISATION_ID; ?>" class="btn" role="button"><?php echo $translate->_("Cancel")?></a>
                        <input type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("Delete")?>" />
                    </div>
                </form>
            <?php } else { ?>
                <div class="alert alert-info"><?php echo $translate->_("No Assessment Response Categories have been selected to be deleted. You will now be redirected to the Assessment Response Categories index.")?></div>
            <?php $ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings/manage/assessmentresponsecategories?org=".$ORGANISATION_ID."\\'', 5000)"; }

            break;
    }
}