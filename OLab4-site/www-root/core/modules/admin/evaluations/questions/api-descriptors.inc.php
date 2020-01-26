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

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EVALUATIONS"))) {
    /**
     * @exception 0: Unable to start processing request.
     */
    echo 0;
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    /**
     * @exception 0: Unable to start processing request.
     */
    echo 0;
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("evaluationquestion", "update", false)) {

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");

    /**
     * @exception 0: Unable to start processing request.
     */
    echo 0;
    exit;
} else {
    ob_clear_open_buffers();
    if (isset($_GET["response_number"]) && ($tmp_input = clean_input($_GET["response_number"], "int"))) {
        $RESPONSE_NUMBER = $tmp_input;
    } else {
        $RESPONSE_NUMBER = false;
    }
    if (isset($_GET["erdescriptor_id"]) && ($tmp_input = clean_input($_GET["erdescriptor_id"], "int"))) {
        $erdescriptor_id = $tmp_input;
    } else {
        $erdescriptor_id = false;
    }
    if (isset($_GET["organisation_id"]) && ($tmp_input = clean_input($_GET["organisation_id"], "int"))) {
        $ORGANISATION_ID = $tmp_input;
    } else {
        $ORGANISATION_ID = false;
    }

    if ($RESPONSE_NUMBER && $ORGANISATION_ID) {
        $descriptors = Models_Evaluation_ResponseDescriptor::fetchAllByOrganisation($ORGANISATION_ID);
        ?>
        <div class="pull-right cursor-pointer close" style="margin-top: -15px;" onclick="modalDescriptorDialog.close()">&times;</div>
        <div class="row-fluid">
            <span class="span1 offset1">&nbsp;</span>
            <span class="span10">
                <strong>
                    Descriptors
                </strong>
            </span>
        </div>
        <?php
        if ($descriptors && @count($descriptors)) {
            foreach ($descriptors as $descriptor) {
                ?>
                <div class="row-fluid">
                    <span class="span1 offset1">
                        <input type="radio" id="response_descriptor_id_<?php echo $descriptor->getID(); ?>" name="response_descriptor_id" value="<?php echo $descriptor->getID(); ?>" onclick="jQuery('#response_descriptor_<?php echo $RESPONSE_NUMBER; ?>').val(<?php echo $descriptor->getID(); ?>)"<?php echo ($erdescriptor_id == $descriptor->getID() ? " checked=\"checked\"" : ""); ?> />
                    </span>
                    <span class="span10">
                        <label for="response_descriptor_id_<?php echo $descriptor->getID(); ?>">
                            <?php
                            echo $descriptor->getDescriptor();
                            ?>
                        </label>
                    </span>
                </div>
                <?php
            }
        } else {
            echo display_notice("No evaluation response descriptors were found in the system.");
        }
        ?>
        <div class="pull-right space-above">
            <a class="btn btn-small btn-primary" onclick="modalDescriptorDialog.close()">Close</a>
        </div>
        <?php
    } else {
        /**
         * @exception 400: Cannot fetch descriptors because no id was provided.
         */
        echo 400;
        exit;
    }
};
exit;