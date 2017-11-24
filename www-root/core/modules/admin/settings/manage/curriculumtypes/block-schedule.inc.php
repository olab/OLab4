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

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CONFIGURATION"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "create",false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {

    ?>
    <h1>Curriculum Period Block Schedules</h1>
    <?php

    if (isset($_GET["type_id"]) && $tmp_input = clean_input($_GET["type_id"], "int")) {
        $PROCESSED["type_id"] = $tmp_input;
    }

    if (isset($_GET["cperiod_id"]) && $tmp_input = clean_input($_GET["cperiod_id"], "int")) {
        $PROCESSED["cperiod_id"] = $tmp_input;
    }

    $curriculum_period = Models_Curriculum_Period::fetchRowByID($PROCESSED["cperiod_id"]);
    if ($curriculum_period) {
        $curriculum_type = Models_Curriculum_Type::fetchRowByID($curriculum_period->getCurriculumTypeID());

        if ($curriculum_type) {
            $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/".$MODULE."/".$SUBMODULE."/curriculumtypes?".replace_query(array("section" => "edit"))."&amp;org=".$ORGANISATION_ID."&amp;type_id=".$PROCESSED["type_id"], "title" => $curriculum_type->getCurriculumTypeName());
            $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/".$MODULE."/".$SUBMODULE."/curriculumtypes?".replace_query(array("section" => "edit"))."&amp;org=".$ORGANISATION_ID."&amp;type_id=".$PROCESSED["type_id"], "title" => "Block Schedule");

            $schedules = Models_Schedule::fetchAllTemplatesByCPeriodID($PROCESSED["cperiod_id"]);

            if (isset($_POST["delete"]) && is_array($_POST["delete"])) {
                $schedules = array();
                foreach ($_POST["delete"] as $delete) {
                    $tmp_input = clean_input($delete, "int");
                    if ($delete) {
                        $schedules[] = Models_Schedule::fetchRowByID($delete);
                    }
                }
            }

            switch ($STEP) {
                case 3 :
                    if ($schedules) {
                        foreach ($schedules as $schedule) {
                            // The delete function sets the deleted_date for the schedule and its children.
                            $schedule->delete();
                        }
                        $schedules = Models_Schedule::fetchAllByCPeriod($PROCESSED["cperiod_id"]);
                        $STEP = 1;
                    }
                    break;
                case 2 :
                    add_notice("You have selected the following block schedules to be deleted, please confirm below you wish to proceed.");
                    break;
                case 1 :
                default:
                    break;
            }


            ?>
            <?php if ($NOTICE) {
                echo display_notice();
            } else { ?>
                <div class="well">
                    Block schedules are used in the creation of clinical schedules. You can edit an existing block schedule and modify the block dates to suit your clinical scheduling needs, or create a new block schedule and allow the system to split it up based on a selected number of weeks. Block schedule durations are tied to their curriculum periods.
                </div>
            <?php } ?>
            <form action="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>/<?php echo $SUBMODULE; ?>/curriculumtypes?section=block-schedule&org=<?php echo $ORGANISATION_ID; ?>&cperiod_id=<?php echo $PROCESSED["cperiod_id"]; ?>" method="POST">
                <input type="hidden" name="step" value="<?php echo $STEP + 1; ?>" />
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th width="5%"></th>
                        <th>Block Schedule Name</th>
                        <th width="14%">Block Length</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    if ($schedules) {
                        foreach ($schedules as $schedule) {
                            $children = $schedule->getChildren();
                            $block_type = Models_BlockType::fetchRowByID($schedule->getBlockTypeID());
                            $url = ENTRADA_URL."/admin/".$MODULE."/".$SUBMODULE."/curriculumtypes?section=edit-schedule&org=".$ORGANISATION_ID."&id=".$schedule->getID()."&cperiod_id=".$PROCESSED["cperiod_id"];;
                            ?>
                            <tr>
                                <td><input type="checkbox" name="delete[]" value="<?php echo $schedule->getID(); ?>" <?php echo $STEP == "2" ? "checked=\"checked\"" : ""; ?> /></td>
                                <td><a href="<?php echo $url; ?>"><?php echo $schedule->getTitle(); ?></a></td>
                                <td><a href="<?php echo $url; ?>"><?php echo $block_type ? $block_type->getNumberOfBlocks() : ""; ?></a></td>
                            </tr>
                        <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="3">There are no block schedules for this curriculum period, please add one using the button below.</td>
                        </tr>
                    <?php
                    }
                    ?>
                    </tbody>
                </table>
                <div class="row-fluid space-above">
                    <input type="submit" class="btn btn-danger" value="<?php echo $translate->_("Delete"); ?>" />
                    <a href="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>/<?php echo $SUBMODULE; ?>/curriculumtypes?section=add-schedule&org=<?php echo $ORGANISATION_ID; ?>&schedule_parent_id=0&cperiod_id=<?php echo $PROCESSED["cperiod_id"]; ?>" class="btn btn-primary pull-right"><?php echo $translate->_("New"); ?></a>
                </div>
            </form>
            <?php
        }
    } else {
        echo display_error(sprintf($translate->_("An invalid curriculum type was selected, please <a href=\"%s\">click here</a> to return to the curriculum layout page."), ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "/curriculumtypes?section=edit&amp;org=" . $ORGANISATION_ID . "&amp;type_id=" . $PROCESSED["type_id"]));
    }
}