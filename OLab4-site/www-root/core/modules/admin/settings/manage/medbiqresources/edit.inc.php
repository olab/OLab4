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
 * @author Unit: MEdTech Unit
 * @author Developer: Andrew Dos-Santos <andrew.dos-santos@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
*/

if (!defined("PARENT_INCLUDED") || !defined("IN_MEDBIQRESOURCES")) {
	exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "update",false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {

	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/manage/medbiqresources?".replace_query(array("section" => "edit"))."&amp;org=".$ORGANISATION_ID, "title" => "Edit Medbiquitous Resource");
	
	if (isset($_GET["medbiq_resource_id"]) && ($medbiq_resource_id = clean_input($_GET["medbiq_resource_id"], array("notags", "trim")))) {
        $PROCESSED["medbiq_resource_id"] = $medbiq_resource_id;
        $medbiq_resource = Models_Medbiq_Resource::fetchRowByID($medbiq_resource_id);
    }

	if (isset($medbiq_resource) && $medbiq_resource) {
        // Error Checking
        switch ($STEP) {
            case 2 :
                /**
                 * Required field "resource" / Resource
                 */
                if (isset($_POST["resource"]) && ($resource = clean_input($_POST["resource"], array("htmlbrackets", "trim")))) {
                    $PROCESSED["resource"] = $resource;
                } else {
                    add_error($translate->_("The <strong>Resource</strong> is a required field."));
                }

                /**
                 * Non-required field "resource_description" / Description
                 */
                if (isset($_POST["resource_description"]) && ($resource_description = clean_input($_POST["resource_description"], array("htmlbrackets", "trim")))) {
                    $PROCESSED["resource_description"] = $resource_description;
                } else {
                    $PROCESSED["resource_description"] = "";
                }

                if (!has_error()) {
                    $medbiq_resource->setResource($PROCESSED["resource"]);
                    $medbiq_resource->setResourceDescription($PROCESSED["resource_description"]);
                    $medbiq_resource->setUpdatedDate(time());
                    $medbiq_resource->setUpdatedBy($ENTRADA_USER->getID());

                    if ($medbiq_resource->update()) {
                        $url = ENTRADA_URL . "/admin/settings/manage/medbiqresources?org=" . $ORGANISATION_ID;
                        $ONLOAD[] = "setTimeout('window.location=\\'" . $url . "\\'', 5000);";

                        add_success(sprintf($translate->_("You have successfully edited <strong>%s</strong> in the system.<br /><br />You will now be redirected to the Medbiquitous Resources index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"%s\" style=\"font-weight: bold\">click here</a> to continue."), html_decode($PROCESSED["resource"]), $url));

                        application_log("success", "Edited Medbiquitous Resource [" . $resource_id . "] in the system.");
                    } else {
                        add_error($translate->_("There was a problem inserting this Medbiquitous Resource into the system. The system administrator was informed of this error; please try again later."));

                        application_log("error", "There was an error inserting an Medbiquitous Resource. Database said: " . $db->ErrorMsg());
                    }
                }

                if (has_error()) {
                    $STEP = 1;
                }
                break;
            case 1 :
            default :
                if ($medbiq_resource) {
                    $PROCESSED["resource"] = $medbiq_resource->getResource();
                    $PROCESSED["resource_description"] = $medbiq_resource->getResourceDescription();
                }
                break;
        }

        // Display Content
        switch ($STEP) {
            case 2 :
                if (has_success()) {
                    echo display_success();
                }

                if (has_notice()) {
                    echo display_notice();
                }

                if (has_error()) {
                    echo display_error();
                }
                break;
            case 1 :
            default:
                if (has_error()) {
                    echo display_error();
                }
                ?>
                <form action="<?php echo ENTRADA_URL . "/admin/settings/manage/medbiqresources" . "?" . replace_query(array("action" => "edit", "step" => 2)) . "&org=" . $ORGANISATION_ID; ?>"
                      method="post">
                    <table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Editing Page">
                        <colgroup>
                            <col style="width: 30%"/>
                            <col style="width: 70%"/>
                        </colgroup>
                        <thead>
                        <tr>
                            <td colspan="2"><h1><?php echo $translate->_("Resource Details"); ?></h1></td>
                        </tr>
                        </thead>
                        <tfoot>
                        <tr>
                            <td colspan="2" style="padding-top: 15px; text-align: right">
                                <input type="button" class="btn" value="Cancel"
                                       onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/settings/manage/medbiqresources?org=<?php echo $ORGANISATION_ID; ?>'"/>
                                <input type="submit" class="btn btn-primary"
                                       value="<?php echo $translate->_("global_button_save"); ?>"/>
                            </td>
                        </tr>
                        </tfoot>
                        <tbody>
                        <tr>
                            <td><label for="resource"
                                       class="form-required"><?php echo $translate->_("Resource:"); ?></label></td>
                            <td><input type="text" id="resource" name="resource"
                                       value="<?php echo((isset($PROCESSED["resource"])) ? html_decode($PROCESSED["resource"]) : ""); ?>"
                                       maxlength="60" style="width: 300px"/></td>
                        </tr>
                        <tr>
                            <td style="vertical-align: top;"><label for="resource_description"
                                                                    class="form-nrequired"><?php echo $translate->_("Description:"); ?></label>
                            </td>
                            <td>
                                <textarea id="resource_description" name="resource_description"
                                          style="width: 98%; height: 200px" rows="20"
                                          cols="70"><?php echo((isset($PROCESSED["resource_description"])) ? html_decode($PROCESSED["resource_description"]) : ""); ?></textarea>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </form>
                <?php
                break;
        }
    } else {
        $url = ENTRADA_URL . "/admin/settings/manage/medbiqresources?org=".$ORGANISATION_ID;
        $ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000);";
        add_error(sprintf($translate->_("You must provide a valid resource ID to edit.<br /><br />You will now be redirected to the Medbiquitous Resources index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"%s\" style=\"font-weight: bold\">click here</a> to continue."), $url));

        echo display_error();

        application_log("notice", $translate->_("Failed to provide Medbiquitous Resources identifer."));
	}
}
