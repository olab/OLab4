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
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2011 Queen's University. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_ROTATION_SCHEDULE"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("rotationschedule", "read",false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {

    $SECTION_TEXT = $MODULE_TEXT[$SECTION];

    ?>
    <h1><?php echo $translate->_("Rotation Schedule"); ?></h1>
    <?php

    if (isset($_GET["schedule_id"]) && $tmp_input = clean_input($_GET["schedule_id"], "int")) {
        $PROCESSED["schedule_id"] = $tmp_input;
    }

    if ($SECTION == "edit") {
        $SCHEDULE = Models_Schedule::fetchRowByID($PROCESSED["schedule_id"]);
    } else {
        $parent_schedule = Models_Schedule::fetchRowByID($PROCESSED["schedule_id"]);
        $SCHEDULE = new Models_Schedule();
        $SCHEDULE->fromArray(array("draft_id" => $parent_schedule->getDraftID()));
    }

    $is_author = false;
    if (Models_Schedule_Draft_Author::isAuthor($SCHEDULE->getDraftID(), $ENTRADA_USER->getActiveID())) {
        $is_author = true;
    }

    if ($is_author) {
        require_once("form-schedule.inc.php");
    } else {
        echo display_error($translate->_("You are attempting to edit a schedule that you do not have access to. Please contact the program assistant responsible for this rotational schedule to be added as an author."));
        ?>
        <div class="row-fluid">
            <a href="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=drafts"; ?>" class="btn btn-default"><?php echo $translate->_("Back"); ?></a>
        </div>
        <?php
    }

}