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
} elseif (!$ENTRADA_ACL->amIAllowed("rotationschedule", "read", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {

    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "?section=drafts", "title" => $translate->_("My Drafts"));

    $PROCESSED["schedule_id"] = null;
    if (isset($_GET["schedule_id"]) && $tmp_input = clean_input($_GET["schedule_id"], "int")) {
        $PROCESSED["schedule_id"] = $tmp_input;
        $PROCESSED["schedule"] = Models_Schedule::fetchRowByID($PROCESSED["schedule_id"]);
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "?section=edit&schedule_id=" . $PROCESSED["schedule_id"], "title" => $PROCESSED["schedule"]->getTitle());
    }

    $PROCESSED["draft_id"] = null;
    if (isset($_GET["draft_id"]) && $tmp_input = clean_input($_GET["draft_id"], "int")) {
        $PROCESSED["draft_id"] = $tmp_input;
        $PROCESSED["draft"] = Models_Schedule_Draft::fetchRowByID($PROCESSED["draft_id"]);
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "?section=edit-draft&draft_id=" . $PROCESSED["draft_id"], "title" => $PROCESSED["draft"]->getTitle());
    }

    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "?section=delete", "title" => $translate->_("Delete Rotation"));

    $PROCESSED["delete"] = array();

    $is_admin = Entrada_Utilities::isCurrentUserSuperAdmin(array(array("resource" => "assessmentreportadmin")));
    if (isset($_POST["delete"]) && is_array($_POST["delete"])) {
        foreach ($_POST["delete"] as $delete) {
            $tmp_input = clean_input($delete, "int");
            if ($tmp_input) {
                $schedule = Models_Schedule::fetchRowByID($tmp_input);
                if (!$schedule) {
                    add_error($translate->_("A specified schedule ID does not exist."));
                } else {
                    $is_author = Entrada_Utilities_ScheduleAuthor::isAuthor(
                        $ENTRADA_USER->getActiveID(),
                        $ENTRADA_USER->getActiveOrganisation(),
                        $schedule->getCourseID(),
                        $schedule->getDraftID()
                    );
                    if ($is_admin || $is_author) {
                        $PROCESSED["delete"][] = $schedule;
                    } else {
                        add_error(sprintf($translate->_("You do not have authorization to delete the rotation schedule <strong>%s</strong>."), $schedule->getTitle()));
                    }
                }
            }
        }
    }
    ?>
    <h1><?php echo $translate->_("Delete Rotation"); ?></h1>
    <?php
    switch ($STEP) {
        case 2 :
            if (!empty($PROCESSED["delete"])) {
                foreach ($PROCESSED["delete"] as $schedule) {
                    if (!$schedule->delete()) {
                        $ERROR++;
                    }
                }
            }
            if (!$ERROR) {
                Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Successfully deleted <strong>%s</strong> schedule blocks."), count($PROCESSED["delete"])));

                if ($PROCESSED["draft_id"]) {
                    $url = ENTRADA_URL . "/admin/" . $MODULE . "?section=edit-draft&draft_id=" . $PROCESSED["draft_id"];
                } else {
                    $url = ENTRADA_URL . "/admin/" . $MODULE . "?section=edit&schedule_id=" . $PROCESSED["schedule_id"];
                }
                header("Location: " . $url);
                exit;
            }
        break;
    }

    switch ($STEP) {
        case 1 :
        default :
            ?>
            <form action="<?php echo ENTRADA_URL."/admin/" . $MODULE . "?section=delete" . ($PROCESSED["draft_id"] ? "&draft_id=" . $PROCESSED["draft_id"] : "&schedule_id=" . $PROCESSED["schedule_id"]); ?>" method="POST">
            <?php
            if (has_error()) {
                echo display_error();
            } else if (empty($PROCESSED["delete"])) {
                echo display_error($translate->_("You have not selected any schedules for deletion."));
            } else {
                echo display_notice($translate->_("You have selected the following schedules for deletion."));
                ?>
                <input type="hidden" name="step" value="2" />
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th width="5%"></th>
                        <th>Name</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($PROCESSED["delete"] as $schedule) { ?>
                        <tr>
                            <td><input type="checkbox" checked="checked" value="<?php echo $schedule->getID(); ?>" name="delete[]" /></td>
                            <td><?php echo $schedule->getTitle(); ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
                <?php
            }
            ?>
                <div class="row-fluid">
                    <a href="<?php echo ENTRADA_URL."/admin/" . $MODULE . "?section=edit-draft&draft_id=" . $PROCESSED["draft_id"]; ?>" class="btn btn-default"><?php echo $translate->_("Back"); ?></a>
                    <?php if (!empty($PROCESSED["delete"])) { ?><input type="submit" class="btn btn-danger pull-right" value="<?php echo $translate->_("Delete"); ?>" /><?php } ?>
                </div>
            </form>
            <?php
        break;
    }
}