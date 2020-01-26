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
 * The file that loads the add / edit honorcode
 *
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Samuel Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2017 David Geffen School of Medicine at UCLA. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CONFIGURATION"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration","read",false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    $honorcode_text          = $ENTRADA_SETTINGS->fetchByShortname("honorcode_text", $ORGANISATION_ID);
    $honorcode_use_exam      = $ENTRADA_SETTINGS->fetchByShortname("honorcode_use_exam", $ORGANISATION_ID);


    if ($honorcode_use_exam && $honorcode_use_exam->getValue() == 1) {
        $honorcode_use_exam_selected = "yes";
    } else {
        $honorcode_use_exam_selected = "no";
    }

    load_rte("basic");
    switch ($STEP) {
        case 2 :

            if (isset($_POST["honorcode_text"]) && ($tmp_input = clean_input($_POST["honorcode_text"], array("html", "trim")))) {
                $PROCESSED["honorcode_text"] = $tmp_input;
            } else {
                $PROCESSED["honorcode_text"] = "";
            }

            if (isset($_POST["honorcode_use_exam"]) && $tmp_input = (int)$_POST["honorcode_use_exam"]) {
                $PROCESSED["honorcode_use_exam"] = $tmp_input;
            } else {
                $PROCESSED["honorcode_use_exam"] = 0;
            }
            
            $updated = array();
            
            if ($honorcode_text && is_object($honorcode_text)) {
                if ($PROCESSED["honorcode_text"] != $honorcode_text->getValue()) {
                    // update
                    if ($ENTRADA_SETTINGS->saveValueByShortname("honorcode_text", $PROCESSED["honorcode_text"], $ORGANISATION_ID)) {
                        $updated[] = "honorcode_text";
                    }
                }
            } else {
                // insert
                if ($ENTRADA_SETTINGS->saveValueByShortname("honorcode_text", $PROCESSED["honorcode_text"], $ORGANISATION_ID)) {
                    $updated[] = "honorcode_text";
                }
            }
            
            if ($honorcode_use_exam && is_object($honorcode_use_exam)) {
                if ($PROCESSED["honorcode_use_exam"] != $honorcode_use_exam->getValue()) {
                    // update
                    if ($ENTRADA_SETTINGS->saveValueByShortname("honorcode_use_exam", $PROCESSED["honorcode_use_exam"], $ORGANISATION_ID)) {
                        $updated[] = "honorcode_use_exam";
                    }
                }
            } else {
                // insert
                if ($ENTRADA_SETTINGS->saveValueByShortname("honorcode_use_exam", $PROCESSED["honorcode_use_exam"], $ORGANISATION_ID)) {
                    $updated[] = "honorcode_use_exam";
                }
            }
            
            $url = ENTRADA_URL . "/admin/settings/manage/honorcode?org=" . $ORGANISATION_ID;
            if ($updated && is_array($updated) && !empty($updated)) {
                $success_msg = sprintf(
                    $translate->_("Updated fields: " . implode(",", $updated) . " You will be redirected to the honor code index, please <a href=\"%s\">click here</a> if you do not wish to wait."), $url
                );
                add_success($success_msg);
            } else {
                add_notice("No fields updated");
            }

            $ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
    }
    ?>

    <h1><?php echo $translate->_("Honor Code"); ?></h1>

    <form class="form-horizontal" action="<?php echo ENTRADA_URL . "/admin/settings/manage/honorcode" . "?" . replace_query(array("step" => 2)) . "&org=" . $ORGANISATION_ID; ?>" method="post">
        <div id="settings-details">
            <div class="control-group">
                <label class="control-label" for="honorcode_text"><?php echo $translate->_("Honor Code"); ?></label>
                <div class="controls">
                    <textarea id="honorcode_text" name="honorcode_text"><?php echo ($honorcode_text ? $honorcode_text->getValue() : "" ); ?></textarea>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label"><?php echo $translate->_("Enable Honor Code Disclaimer in Secure Exams"); ?>:</label>
                <div class="controls">
                    <div class="radio">
                        <label for="honorcode_use_exam_yes"><?php echo $translate->_("Yes"); ?>
                            <input name="honorcode_use_exam" id="honorcode_use_exam_yes" type="radio"
                                   value="1" <?php echo($honorcode_use_exam_selected == "yes" ? "checked='checked'" : ""); ?> />
                        </label>
                    </div>
                    <div class="radio">
                        <label for="honorcode_use_exam_no"><?php echo $translate->_("No"); ?>
                            <input name="honorcode_use_exam" id="honorcode_use_exam_no" type="radio"
                                   value="0" <?php echo($honorcode_use_exam_selected == "no" ? "checked='checked'" : ""); ?> />
                        </label>
                    </div>
                </div>
            </div>

            <div class="control-group">
                <div class="controls">
                    <button id="Submit" type="submit" class="btn"><?php echo $translate->_("Update"); ?></button>
                </div>
            </div>
        </div>
    </form>
    <?php
}