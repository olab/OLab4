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
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

if((!defined("PARENT_INCLUDED")) || (!defined("IN_DESCRIPTORS"))) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "update", false)) {
    $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."\\'', 15000)";

    $ERROR++;
    $ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    if (isset($RECORD_ID) && $RECORD_ID) {
        $descriptor = Models_Evaluation_ResponseDescriptor::fetchByID($RECORD_ID);
    } else {
        $descriptor = new Models_Evaluation_ResponseDescriptor();
    }

    switch ($STEP) {
        case 2 :
            if (isset($_POST["descriptor"]) && $tmp_input = clean_input($_POST["descriptor"], array("trim", "striptags"))) {
                if (strlen($tmp_input) <= 256) {
                    $PROCESSED["descriptor"] = $tmp_input;
                } else {
                    add_error("The descriptor too long.  Please specify a descriptor that is 256 characters or less.");
                }
            } else {
                add_error("The descriptor is required.");
            }

            /**
             * Required field "order" / Descriptor Order
             */
            if (isset($_POST["order"]) && ($order = clean_input($_POST["order"], array("trim", "int")))) {
                $PROCESSED["order"] = $order;
            } else {
                add_error("The order is required. Please select the order which this descriptor should appear in.");
            }

            /**
             * Required field "reportable" / Include in Reports
             */
            if (isset($_POST["reportable"]) && ($reportable = clean_input($_POST["reportable"], array("trim", "int")))) {
                $PROCESSED["reportable"] = 1;
            } else {
                $PROCESSED["reportable"] = 0;
            }

            $PROCESSED["organisation_id"] = $ORGANISATION_ID;
            $PROCESSED["updated_date"] = time();
            $PROCESSED["updated_by"] = $ENTRADA_USER->getID();
            $PROCESSED["active"] = 1;

            $descriptor->fromArray($PROCESSED);
            if (!has_error()) {
                $existing_descriptors = Models_Evaluation_ResponseDescriptor::fetchAllByOrganisation($ORGANISATION_ID);
                foreach ($existing_descriptors as $existing_descriptor) {
                    if ($existing_descriptor->getOrder() >= $PROCESSED["order"] && (!$descriptor->getOrder() || $existing_descriptor->getOrder() < $descriptor->getOrder())) {
                        $descriptor_array = $existing_descriptor->toArray();
                        $descriptor_array["order"]++;
                        $existing_descriptor->fromArray($descriptor_array)->update();
                    }
                }
                if (defined("ADD_DESCRIPTOR")) {
                    if ($descriptor->insert()) {
                        add_success("Successfully added the Evaluation Response Descriptor [<strong>". $descriptor->getDescriptor()."</strong>]. You will now be redirected to the Evaluation Response Descriptors index, please <a href=\"".ENTRADA_URL."/admin/settings/manage/descriptors?org=".$ORGANISATION_ID."\">click here</a> if you do not wish to wait.");
                    } else {
                        $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings/manage/descriptors?org=".$ORGANISATION_ID."\\'', 5000)";
                        add_error("An error occurred while attempting to add the Evaluation Response Descriptor [<strong>".  $descriptor->getDescriptor()."</strong>]. A system administrator has been informed, please try again later.<br /><br />You will now be redirected to the Evaluation Response Descriptors index, please <a href=\"".ENTRADA_URL."/admin/settings/manage/descriptors?org=".$ORGANISATION_ID."\">click here</a> if you do not wish to wait.");
                    }
                } else {
                    if (isset($RECORD_ID)) {
                        if ($descriptor->fromArray($PROCESSED)->update()) {
                            add_success("Successfully updated the Evaluation Response Descriptor [<strong>". $descriptor->getDescriptor(false)."</strong>]. You will now be redirected to the Evaluation Response Descriptor index, please <a href=\"".ENTRADA_URL."/admin/settings/manage/descriptors?org=".$ORGANISATION_ID."\">click here</a> if you do not wish to wait.");
                        } else {
                            $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings/manage/descriptors?org=".$ORGANISATION_ID."\\'', 5000)";
                            add_error("An error occurred while attempting to update the Evaluation Response Descriptor [<strong>". $descriptor->getDescriptor()."</strong>]. A system administrator has been informed, please try again later.<br /><br />You will now be redirected to the Evaluation Response Descriptors index, please <a href=\"".ENTRADA_URL."/admin/settings/manage/descriptors?org=".$ORGANISATION_ID."\">click here</a> if you do not wish to wait.");
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
                $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/settings/manage/descriptors?org=".$ORGANISATION_ID."\\'', 5000)";
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
            <form action="<?php echo ENTRADA_URL . "/admin/settings/manage/descriptors?org=".$ORGANISATION_ID; ?>&section=<?php echo defined("EDIT_DESCRIPTOR") ? "edit&id=".$RECORD_ID : "add"; ?>&step=2" method="POST" class="form-horizontal">
                <div class="control-group">
                    <label class="control-label form-required" for="descriptor">Descriptor</label>
                    <div class="controls">
                        <input name="descriptor" id="descriptor" type="text" class="span6" value="<?php echo (isset($descriptor) && $descriptor && $descriptor->getDescriptor() ? $descriptor->getDescriptor() : ""); ?>" />
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label form-required" for="order">Order</label>
                    <div class="controls">
                        <select name="order" id="order" class="span6">
                            <option value="">-- Please select the display order --</option>
                            <?php
                            $descriptors = Models_Evaluation_ResponseDescriptor::fetchAllByOrganisation($ORGANISATION_ID);
                            if ($descriptors && @count($descriptors)) {
                                $max_count = count($descriptors);
                                $count = 0;
                                foreach ($descriptors as $descriptor) {
                                    $count++;
                                    if (!$RECORD_ID || $RECORD_ID != $descriptor->getID()) {
                                        if ($count < $max_count) {
                                            echo "<option value=\"".$count."\">Before ".$descriptor->getDescriptor()."</option>";
                                        } else {
                                            echo "<option value=\"".$count."\">Before ".$descriptor->getDescriptor()."</option>";
                                            echo "<option value=\"".($count + 1)."\">After ".$descriptor->getDescriptor()."</option>";
                                        }
                                    } else {
                                        echo "<option value=\"".($count)."\" selected=\"selected\">Do not change</option>";
                                    }
                                }
                            } else {
                                echo "<option value=\"1\">First</option>\n";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="control-group">
                    <div class="controls">
                        <label class="checkbox"><input type="checkbox" value="1" name="reportable" <?php echo ($descriptor->getReportable() === NULL || $descriptor->getReportable() ? " checked=\"checked\"" : ""); ?> /> Include this response descriptor in reports.
                    </div>
                </div>
                <div class="row-fluid">
                    <a href="<?php echo ENTRADA_URL."/admin/settings/manage/descriptors?org=".$ORGANISATION_ID; ?>" class="btn">Cancel</a>
                    <input type="submit" class="btn btn-primary pull-right" value="Save" />
                </div>
            </form>
            <?php
        break;
    }
}