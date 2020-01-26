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
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
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
    $drafts = Models_Schedule_Draft::fetchAllByProxyID($ENTRADA_USER->getActiveID());

    if (isset($_GET["draft_id"]) && $tmp_input = clean_input($_GET["draft_id"], "int")) {
        $PROCESSED["current_draft_id"] = $tmp_input;
    }

    if (isset($_POST["delete"])) {
        $method = "delete";
    } else if (isset($_POST["publish"])){
        $method = "publish";
    }

    if (isset($_POST["draft_ids"]) && is_array($_POST["draft_ids"]) && !empty($_POST["draft_ids"])) {
        foreach ($_POST["draft_ids"] as $draft_id) {
            $tmp_input = clean_input($draft_id);
            if ($tmp_input) {
                $PROCESSED["draft_ids"][] = $tmp_input;
            }
        }
    } else {
        if (isset($method) && empty($_POST["draft_ids"])) {
            add_error(sprintf($translate->_("Please check off the drafts you wish to %s."), $method));
            unset($method);
            $STEP = 1;
        }
    }

    switch ($STEP) {
        case 3 :
            if (isset($PROCESSED["draft_ids"]) && !empty($PROCESSED["draft_ids"])) {
                if ($method == "delete") {
                    foreach ($PROCESSED["draft_ids"] as $draft_id) {
                        $draft = Models_Schedule_Draft::fetchRowByID($draft_id);
                        if ($draft && !$draft->fromArray(array("status" => "draft"))->update()) {
                            $ERROR++;
                        }
                    }
                }
                if (!$ERROR) {
                    // TODO: Fix this so it localizes in all instances instead of using a token ($method."ed" is not translatable)
                    add_success(
                        sprintf(
                            $translate->_("Successfully %s <strong>%s</strong> rotation schedule."),
                            ($method == "delete" ? $translate->_("withdrew") : $method . "ed"),
                            count($PROCESSED["draft_ids"])
                        )
                    );
                    $STEP = 1;
                    $drafts = Models_Schedule_Draft::fetchAllByProxyID($ENTRADA_USER->getActiveID());
                    unset($method);
                }
            }
            break;
        case 2 :
            if (isset($PROCESSED["draft_ids"]) && !empty($PROCESSED["draft_ids"])) {
                $drafts = array();
                foreach ($PROCESSED["draft_ids"] as $draft_id) {
                    $drafts[] = Models_Schedule_Draft::fetchRowByID($draft_id);
                }
                add_notice(sprintf($translate->_("You have chosen the following schedules to be withdrawn. Please confirm by clicking on the <strong>Withdraw Rotation Schedule</strong> button below.")));
            }
            break;
    }
    ?>
    <h1><?php echo $translate->_("Rotation Schedule"); ?></h1>
    <?php
    Views_Schedule_UserInterfaces::renderScheduleNavTabs($SECTION);
    $drafts = array();
    $is_admin = Entrada_Utilities::isCurrentUserSuperAdmin(array(array("resource" => "assessmentreportadmin")));
    if ($is_admin) {
        $all_drafts = Models_Schedule_Draft::fetchAllByOrg($ENTRADA_USER->getActiveOrganisation(), "live", "draft_title");
        if (is_array($all_drafts)) {
            foreach ($all_drafts as $schedule_draft_record) {
                $drafts[$schedule_draft_record->getID()] = array($schedule_draft_record);
            }
        }
    } else {
        $courses = Models_Course::getUserCourses($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation());
        if ($courses) {
            foreach ($courses as $course) {
                $schedule_draft = Models_Schedule_Draft::fetchAllByProxyIDCourseID($ENTRADA_USER->getActiveID(), $course->getID(), "live");
                foreach ($schedule_draft as $schedule_draft_record) {
                    $drafts[$schedule_draft_record->getID()] = array($schedule_draft_record);
                }
            }
        }
    }
    $user_drafts = Models_Schedule_Draft::fetchAllByProxyID($ENTRADA_USER->getActiveID(), "live");
    if ($user_drafts) {
        foreach ($user_drafts as $user_draft) {
            $drafts[$user_draft->getID()] = array($user_draft);
        }
    }
    if ($NOTICE) {
        echo display_notice();
    }
    if ($SUCCESS) {
        echo display_success();
    }
    if ($ERROR) {
        echo display_error();
    }
    ?>
    <form class="form-horizontal" id="my-drafts" method="POST" action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . ""; ?>">
        <input type="hidden" name="step" value="<?php echo $STEP + 1; ?>" />
        <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th width="5%"></th>
                <th width="40%"><?php echo $translate->_("Title"); ?></th>
                <th width="35%"><?php echo $translate->_("Course"); ?></th>
                <th width="20%"><?php echo $translate->_("Created Date"); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php
        if ($drafts) {
            foreach ($drafts as $course_drafts) {
                foreach ($course_drafts as $draft) {
                    $course = Models_Course::fetchRowByID($draft->getCourseID());
                    $selected = isset($PROCESSED["draft_ids"]) && in_array($draft->getID(), $PROCESSED["draft_ids"]) && $STEP == 2;
                    if ($STEP == 1 || $selected) {
                        ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="draft_ids[]" value="<?php echo $draft->getID(); ?>" <?php echo $selected ? "checked=\"checked\"" : ""; ?> />
                            </td>
                            <td>
                                <a href="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>?section=edit-draft&draft_id=<?php echo $draft->getID(); ?>"><?php echo $draft->getTitle(); ?></a>
                            </td>
                            <td>
                                <a href="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>?section=edit-draft&draft_id=<?php echo $draft->getID(); ?>"><?php echo $course ? $course->getCourseCode() . " - " . $course->getCourseName() : ""; ?></a>
                            </td>
                            <td>
                                <a href="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>?section=edit-draft&draft_id=<?php echo $draft->getID(); ?>"><?php echo $draft ? date("Y-m-d", $draft->getCreatedDate()) : ""; ?></a>
                            </td>
                        </tr>
                        <?php
                    }
                }
            }
        } else {
        ?>
            <tr>
                <td colspan="4"><?php echo $translate->_("There are no rotation schedules in the system."); ?></td>
            </tr>
        <?php
        }
        ?>
        </tbody>
    </table>
        <div class="row-fluid">
            <?php
            if (isset($method)) {
                $url = ENTRADA_URL . "/admin/" . $MODULE . "";
                if (isset($PROCESSED["current_draft_id"])) {
                    $url = ENTRADA_URL . "/admin/".$MODULE."?draft_id=" . $PROCESSED["current_draft_id"];
                }
                ?>
                <a href="<?php echo $url; ?>" class="btn btn-default"><?php echo $translate->_("Back"); ?></a>
            <?php
            }
            if ($drafts) {
                if (!isset($method) || $method == "delete") { ?>
                <input type="submit" name="delete" class="btn btn-danger <?php echo isset($method) && $method == "delete" ? "pull-right" : ""; ?>" value="<?php echo $translate->_("Withdraw Rotation Schedule"); ?>"/>
                <?php }
            }?>
        </div>
    </form>

<?php
}