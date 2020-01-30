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

if((!defined("PARENT_INCLUDED")) || (!defined("IN_ASSESSMENT_RESPONSE_CATEGORIES"))) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "update", false)) {
    $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."\\'', 15000)";

    $ERROR++;
    $ERRORSTR[]	= $translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    if (isset($RECORD_ID) && $RECORD_ID) {
        $descriptor = Models_Assessments_Response_Descriptor::fetchRowByID($RECORD_ID);
        if (!$descriptor) {
            add_error($translate->_("No category was found with that identifier."));
        }
    } else {
        $descriptor = new Models_Assessments_Response_Descriptor();
    }

    switch ($STEP) {
        case 2 :
            if (isset($_POST["descriptor"]) && $tmp_input = clean_input($_POST["descriptor"], array("trim", "striptags"))) {
                if (strlen($tmp_input) <= 200) {
                    $PROCESSED["descriptor"] = $tmp_input;
                } else {
                    add_error($translate->_("The category name is too long.  Please specify a category that is 200 characters or less."));
                }
            } else {
                add_error($translate->_("The category is required. Please specify a name for the category."));
            }

            if (isset($_POST["reportable"]) && ($reportable = clean_input($_POST["reportable"], array("trim", "int")))) {
                $PROCESSED["reportable"] = 1;
            } else {
                $PROCESSED["reportable"] = 0;
            }

            $PROCESSED["organisation_id"] = $ORGANISATION_ID;
            $PROCESSED["updated_date"] = time();
            $PROCESSED["updated_by"] = $ENTRADA_USER->getID();

            if (defined("ADD_ASSESSMENT_RESPONSE_CATEGORY")) {
                $PROCESSED["created_date"] = time();
                $PROCESSED["created_by"] = $ENTRADA_USER->getID();
                $PROCESSED["order"] = Models_Assessments_Response_Descriptor::fetchNextOrder();
            }

            if ($descriptor) {
                $descriptor->fromArray($PROCESSED);
            } else {
                add_error($translate->_("Sorry, that category was not found."));
            }

            if (!has_error()) {


                if (defined("ADD_ASSESSMENT_RESPONSE_CATEGORY")) {
                    if ($descriptor->insert()) {
                        add_success($translate->_("Successfully added the Assessment Response Category [<strong>" . $descriptor->getDescriptor() . "</strong>]. You will now be redirected to the Asessment Response Categories index, please <a href=\"" . ENTRADA_URL . "/admin/settings/manage/assessmentresponsecategories?org=" . $ORGANISATION_ID . "\">click here</a> if you do not wish to wait."));
                    } else {
                        $ONLOAD[] = "setTimeout('window.location=\\'" . ENTRADA_URL . "/admin/settings/manage/assessmentresponsecategories?org=" . $ORGANISATION_ID . "\\'', 5000)";
                        add_error($translate->_("An error occurred while attempting to add the Assessment Response Category [<strong>" . $descriptor->getDescriptor() . "</strong>]. A system administrator has been informed, please try again later.<br /><br />You will now be redirected to the Assessment Response Category index, please <a href=\"" . ENTRADA_URL . "/admin/settings/manage/assessmentresponsecategories?org=" . $ORGANISATION_ID . "\">click here</a> if you do not wish to wait."));
                    }
                } else {
                    if (isset($RECORD_ID)) {
                        if ($descriptor->fromArray($PROCESSED)->update()) {
                            add_success($translate->_("Successfully updated the Assessment Response Category [<strong>" . $descriptor->getDescriptor(false) . "</strong>]. You will now be redirected to the Assessment Response Categories index, please <a href=\"" . ENTRADA_URL . "/admin/settings/manage/assessmentresponsecategories?org=" . $ORGANISATION_ID . "\">click here</a> if you do not wish to wait."));
                        } else {
                            $ONLOAD[] = "setTimeout('window.location=\\'" . ENTRADA_URL . "/admin/settings/manage/assessmentresponsecategories?org=" . $ORGANISATION_ID . "\\'', 5000)";
                            add_error($translate->_("An error occurred while attempting to update the Assessment Response Category [<strong>" . $descriptor->getDescriptor() . "</strong>]. A system administrator has been informed, please try again later.<br /><br />You will now be redirected to the Assessment Respons Categories index, please <a href=\"" . ENTRADA_URL . "/admin/settings/manage/assessmentresponsecategories?org=" . $ORGANISATION_ID . "\">click here</a> if you do not wish to wait."));
                        }
                    }
                }
            } else {
                $STEP = 1;
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
                $ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings/manage/assessmentresponsecategories?org=".$ORGANISATION_ID."\\'', 5000)";
            }
            if ($NOTICE) {
                echo display_notice();
            }
            break;
        case 1 :
        default :
            if ($ERROR) {
                echo display_error();
            }
            if ($SUCCESS) {
                echo display_success();
            }
            if ($NOTICE) {
                echo display_notice();
            }
            ?>
            <form action="<?php echo ENTRADA_URL . "/admin/settings/manage/assessmentresponsecategories?org=".$ORGANISATION_ID; ?>&section=<?php echo defined("EDIT_ASSESSMENT_RESPONSE_CATEGORY") ? "edit&id=".$RECORD_ID : "add"; ?>&step=2" method="POST" class="form-horizontal">
                <div class="control-group">
                    <label class="control-label form-required" for="descriptor"><?php echo $translate->_("Category")?></label>
                    <div class="controls">
                        <input name="descriptor" id="descriptor" type="text" class="span6" value="<?php echo (isset($descriptor) && $descriptor && $descriptor->getDescriptor() ? $descriptor->getDescriptor() : ""); ?>" />
                    </div>
                    <div class="controls">
                        <label class="checkbox"><input type="checkbox" value="1" name="reportable" <?php echo ($descriptor->getReportable() === NULL || $descriptor->getReportable() ? " checked=\"checked\"" : ""); ?> /> <?php echo $translate->_("Include this response category in reports.") ?>
                    </div>
                </div>
                <div class="row-fluid">
                    <a href="<?php echo ENTRADA_URL."/admin/settings/manage/assessmentresponsecategories?org=".$ORGANISATION_ID; ?>" class="btn"><?php echo $translate->_("Cancel")?></a>
                    <input type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("Save")?>" />
                </div>
            </form>
            <?php

        break;
    }
}