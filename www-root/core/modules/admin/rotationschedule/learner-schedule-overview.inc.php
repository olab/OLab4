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
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_ROTATION_SCHEDULE"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("rotationschedule", "read", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {

    if (isset($_GET["draft_id"]) && $tmp_input = clean_input($_GET["draft_id"], "int")) {
        $PROCESSED["draft_id"] = $tmp_input;
        $draft = Models_Schedule_Draft::fetchRowByID($PROCESSED["draft_id"]);
    }

    if ($draft->getStatus() == "draft") {
        $BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/" . $MODULE . "?section=drafts", "title" => $translate->_("My Drafts"));
    }
    $BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/" . $MODULE . "?section=edit-draft&draft_id=" . $PROCESSED["draft_id"], "title" => $draft->getTitle());

    if (isset($_GET["proxy_id"]) && $tmp_input = clean_input($_GET["proxy_id"], "int")) {
        $PROCESSED["proxy_id"] = $tmp_input;
        $learner = User::fetchRowByID($PROCESSED["proxy_id"]);
    }

    if (isset($PROCESSED["proxy_id"]) && isset($learner)) {
        $BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/" . $MODULE . "?section=learner-schedule-overview&proxy_id=".$PROCESSED["proxy_id"]."draft_id=" . $PROCESSED["draft_id"], "title" => "Rotation Schedule Overview");
        ?>
        <h1><?php echo sprintf($translate->_("Rotation Schedule Overview for %s"), $learner->getFullname(false)); ?></h1>
        <?php

        Views_Schedule_UserInterfaces::renderFullLearnerSchedule($PROCESSED["proxy_id"], $PROCESSED["draft_id"], $learner->getFullname(false), true);

    } else {

    }
    ?>
    <div class="row-fluid space-above">
        <a href="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=edit-draft&draft_id=" . $PROCESSED["draft_id"]; ?>" class="btn btn-defaut"><?php echo $translate->_("Back"); ?></a>
    </div>
    <?php
}