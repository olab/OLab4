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

    if (isset($_GET["draft_id"]) && $tmp_input = clean_input($_GET["draft_id"])) {
        $PROCESSED["draft_id"] = $tmp_input;
    }

    $SECTION_TEXT = $MODULE_TEXT[$SECTION];
    define("EDIT_DRAFT", true);

    if ($PROCESSED["draft_id"]) {
        $draft = Models_Schedule_Draft::fetchRowByID($PROCESSED["draft_id"]);
        ?>
        <h1><?php echo ($draft->getStatus() == "live" ? $translate->_("Edit Rotation Schedule") : $translate->_("Edit Draft Rotation Schedule")); ?></h1>
        <?php
        $draft_authors  = Models_Schedule_Draft_Author::fetchAllByDraftID($PROCESSED["draft_id"]);

        $is_author = false;
        if (Models_Schedule_Draft_Author::isAuthor($draft->getID(), $ENTRADA_USER->getActiveID())) {
            $is_author = true;
        }

        if ($is_author) {
            if ($draft->getStatus() == "draft") {
                $BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/" . $MODULE . "?section=drafts", "title" => $translate->_("My Drafts"));
            }
            $BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/" . $MODULE . "?section=edit-draft&draft_id=" . $PROCESSED["draft_id"], "title" => $draft->getTitle());

            require_once("form.inc.php");
        } else {
            echo display_error($translate->_("You are not able to edit this rotational schedule. Please contact the coordinator responsible for this rotational schedule to be added as an author."));
            ?>
            <div class="row-fluid">
                <a href="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=drafts"; ?>" class="btn btn-default"><?php echo $translate->_("Back"); ?></a>
            </div>
            <?php
        }
    } else {
        echo display_error($translate->_("An invalid draft ID was provided"));
    }

}