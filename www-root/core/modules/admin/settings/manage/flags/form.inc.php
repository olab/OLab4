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

if((!defined("PARENT_INCLUDED")) || (!defined("IN_CONFIGURATION"))) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "update", false)) {
    $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."\\'', 15000)";

    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $HEAD[] = '<script>var ENTRADA_URL = "'.ENTRADA_URL.'";</script>';
    $HEAD[] = "<script type=\"text/javascript\">var FLAGS_COLOR_PALETTE = ".json_encode($translate->_("flags_color_palette")).";</script>\n";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.iris.min.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/color-picker.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/settings/flags-admin.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";

    if (isset($FLAG_ID) && (int) $FLAG_ID) {
        $flag = Models_Assessments_Flag::fetchRowByID($FLAG_ID);
    } else {
        $flag = new Models_Assessments_Flag();
    }

    switch ($STEP) {
        case 2 :
            if (isset($_POST["flag_title"]) && $tmp_input = clean_input($_POST["flag_title"], array("trim", "striptags"))) {
                if (strlen($tmp_input) <= 255) {
                    $PROCESSED["title"] = $tmp_input;
                } else {
                    add_error("The flag Title is too long.  Please specify a title that is 255 characters or less.");
                }
            } else {
                add_error("The Title is required.");
            }

            if (isset($_POST["flag_description"]) && $tmp_input = clean_input($_POST["flag_description"], array("trim", "striptags"))) {
                $PROCESSED["description"] = $tmp_input;
            } else {
                $PROCESSED["description"] = "";
            }

            if (isset($_POST["flag_color"]) && $tmp_input = clean_input($_POST["flag_color"], array("trim", "striptags"))) {
                $PROCESSED["color"] = $tmp_input;
            } else {
                add_error("The Color is required.");
            }

            if (isset($_POST["flag_visibility"]) && $tmp_input = clean_input($_POST["flag_visibility"], array("trim", "striptags"))) {
                $PROCESSED["visibility"] = $tmp_input;
            } else {
                add_error("The Visibility is required.");
            }

            if (isset($_POST["flag_value"]) && $tmp_input = clean_input($_POST["flag_value"], array("trim", "striptags", "int"))) {
                $PROCESSED["flag_value"] = $tmp_input;
            } else {
                add_error("Flag value is required.");
            }

            if (isset($_POST["flag_ordering"]) && $tmp_input = clean_input($_POST["flag_ordering"], array("trim", "striptags"))) {
                $PROCESSED["ordering"] = intval($tmp_input);
            } else {
                $PROCESSED["ordering"] = null;
            }

            if (!$ERROR) {
                $PROCESSED["organisation_id"] = $ORGANISATION_ID;
                if (!$PROCESSED["ordering"]) {
                    // Fetch next available ordering
                    $PROCESSED["ordering"] = Models_Assessments_Flag::fetchNextAvailableOrder($ORGANISATION_ID);
                }

                if (isset($FLAG_ID) && (int) $FLAG_ID) {
                    $PROCESSED["updated_date"] = time();
                    $PROCESSED["updated_by"] = $ENTRADA_USER->getID();

                    if (!$flag->fromArray($PROCESSED)->update()) {
                        add_error("There was a problem updating this assessment flag severity. The system administrator was informed of this error; please try again later.");
                        application_log("error", "There was an error updating an assessment flag severity. Database said: ".$db->ErrorMsg());
                    } else {
                        add_success("Successful update of Flag Severity [<strong>". $flag->getTitle()."</strong>]. You will now be redirected to the Flags Type page, please <a href=\"".ENTRADA_URL."/admin/settings/manage/flags?org=".$ORGANISATION_ID."\">click here</a> if you do not wish to wait.");
                    }
                } else {
                    $PROCESSED["created_date"] = time();
                    $PROCESSED["created_by"] = $ENTRADA_USER->getID();

                    if (!$flag->fromArray($PROCESSED)->insert()) {
                        add_error("There was a problem inserting this assessment flag severity. The system administrator was informed of this error; please try again later.");
                        application_log("error", "There was an error inserting an assessment flag severity. Database said: ".$db->ErrorMsg());
                    } else {
                        add_success("Successful insertion of Flag Severity [<strong>". $flag->getTitle()."</strong>]. You will now be redirected to the Flags Type page, please <a href=\"".ENTRADA_URL."/admin/settings/manage/flags?org=".$ORGANISATION_ID."\">click here</a> if you do not wish to wait.");
                    }
                }
            }

            if ($ERROR) {
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
                $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings/manage/flags?org=".$ORGANISATION_ID."\\'', 5000)";
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
            <form id="flag_type_form" class="form-horizontal" action="<?php echo ENTRADA_URL . "/admin/settings/manage/flags?org=".$ORGANISATION_ID; ?>&section=<?php echo defined("EDIT_FLAG_TYPE") ? "edit&flag=".$FLAG_ID : "add"; ?>&step=2" method="POST">
                <div class="control-group">
                    <label class="control-label form-required" for="flag_title"><?php echo $translate->_("Title"); ?></label>
                    <div class="controls">
                        <input type="text" id="flag_title" name="flag_title" class="span8" value="<?php echo (!empty($flag) ? $flag->getTitle() : ""); ?>" required/>
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label form-required" for="flag_title"><?php echo $translate->_("Flag Value"); ?></label>
                    <div class="controls">
                        <input type="text" id="flag_value" name="flag_value" class="span8" value="<?php echo (!empty($flag) ? $flag->getFlagValue() : ""); ?>" required/>
                    </div>
                </div>
                <div class="control-group">
                    <label for="flag_ordering" class="control-label"><?php echo $translate->_("Ordering"); ?></label>
                    <div class="controls">
                        <input type="text" id="flag_ordering" name="flag_ordering" class="span8" value="<?php echo (!empty($flag) ? $flag->getOrdering() : ""); ?>" />
                        <p><?php echo $translate->_("Leave blank for next available ordering."); ?></p>
                    </div>
                </div>
                <div class="control-group">
                    <label for="flag_description" class="form-nrequired control-label"><?php echo $translate->_("Flag Description"); ?></label>
                    <div class="controls">
                        <textarea id="flag_description" name="flag_description" class="span10 expandable"><?php echo (!empty($flag) ? $flag->getDescription() : ""); ?></textarea>
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label form-required" for="flag_color"><?php echo $translate->_("Colour"); ?></label>
                    <div class="controls">
                        <input type="text" id="flag_color" name="flag_color" class="span3" value="<?php echo (!empty($flag) ? $flag->getColor() : ""); ?>" required/>
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label form-required" for="flag_visibility"><?php echo $translate->_("Visibility"); ?></label>
                    <div class="controls">
                        <select id="flag_visibility" name="flag_visibility" class="span6"  required>
                            <option value="Default"<?php echo (!empty($flag) && $flag->getVisibility() == "Default") ? " selected" : ""; ?>><?php echo $translate->_("Default"); ?></option>
                            <option value="Admin"<?php echo (!empty($flag) && $flag->getVisibility() == "Admin") ? " selected" : ""; ?>><?php echo $translate->_("Admin"); ?></option>
                            <option value="Public"<?php echo (!empty($flag) && $flag->getVisibility() == "Public") ? " selected" : ""; ?>><?php echo $translate->_("Public"); ?></option>
                        </select>
                    </div>
                </div>
                <div class="row-fluid">
                    <a href="<?php echo ENTRADA_URL."/admin/settings/manage/flags?org=".$ORGANISATION_ID; ?>" class="btn" type="button"><?php echo $translate->_("Cancel"); ?></a>
                    <input type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("Save"); ?>" />
                </div>

            </form>
            <?php
        break;
    }
}
