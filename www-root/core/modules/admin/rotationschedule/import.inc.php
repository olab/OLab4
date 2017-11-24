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
 * @author Organisation: Queen"s University
 * @author Unit: MEdTech Unit
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen"s University. All Rights Reserved.
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
    <h1><?php echo $translate->_("Add Blocks"); ?></h1>
    <?php

    if (isset($_GET["draft_id"]) && $tmp_input = clean_input($_GET["draft_id"], "int")) {
        $PROCESSED["draft_id"] = $tmp_input;
        $draft = Models_Schedule_Draft::fetchRowByID($PROCESSED["draft_id"]);
        $course = Models_Course::fetchRowByID($draft->getCourseID());
    }

    if ($draft) {
        if (Models_Schedule_Draft_Author::isAuthor($draft->getID(), $ENTRADA_USER->getActiveID())) {
            $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "?section=drafts", "title" => $translate->_("My Drafts"));
            $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "?section=edit-draft&draft_id=" . $PROCESSED["draft_id"], "title" => $draft->getTitle());
            $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "?section=import&draft_id=" . $PROCESSED["draft_id"], "title" => $translate->_("Add Rotation"));

            switch ($STEP) {
                case 2 :

                    if (isset($_POST["course_id"]) && $tmp_input = clean_input($_POST["course_id"], "int")) {
                        $PROCESSED["course_id"] = $tmp_input;
                    }

                    /*
                    if (isset($_POST["auto_assign"]) && $tmp_input = clean_input($_POST["auto_assign"], "int")) {
                        $PROCESSED["auto_assign"] = $tmp_input;
                    }
                    */

                    /*if (isset($_POST["spaces"]) && $tmp_input = clean_input($_POST["spaces"], "int")) {
                        $PROCESSED["spaces"] = $tmp_input;
                    }*/

                    if (isset($_POST["title"]) && $tmp_input = clean_input($_POST["title"], array("trim", "striptags"))) {
                        $PROCESSED["title"] = $tmp_input;
                    } else {
                        add_error("Creating a rotation requires a name.");
                    }

                    if (isset($_POST["code"]) && $tmp_input = clean_input($_POST["code"], array("trim", "underscores", "module"))) {
                        $PROCESSED["code"] = strtoupper($tmp_input);
                    } else {
                        add_error("Creating a rotation requires a shortname.");
                    }

                    if (!$ERROR) {

                        // Fetch all templates associated with the draft's enrolment period.
                        $draft_curriculum_period = $draft->getCPeriodID();
                        $curriculum_type = Models_Curriculum_Type::fetchRowByCPeriodID($draft_curriculum_period);

                        if ($curriculum_type) {
                            $curriculum_period = Models_Curriculum_Period::fetchRowByID($draft_curriculum_period);
                            if ($curriculum_period) {

                                $schedules = Models_Schedule::fetchAllTemplatesByCPeriodID($curriculum_period->getCperiodID());

                                if ($schedules && !$ERROR) {
                                    $template_rotation_children = array();

                                    foreach($schedules as $template_rotation) {
                                        $template_rotation_children[$template_rotation->getID()] = $template_rotation->getChildren();
                                    }
                                    $schedule_type_map = array(
                                        "stream" => "rotation_stream",
                                        "block" => "rotation_block"
                                    );
                                    // Create rotation stream
                                    $schedule_data = $schedules[0]->toArray();
                                    unset($schedule_data["schedule_id"]);
                                    unset($schedule_data["schedule_parent_id"]);
                                    unset($schedule_data["block_type_id"]);
                                    $schedule_data["schedule_parent_id"] = "0";
                                    $schedule_data["draft_id"] = $PROCESSED["draft_id"];
                                    $schedule_data["course_id"] = $PROCESSED["course_id"];
                                    $schedule_data["title"] = isset($PROCESSED["title"]) ? $PROCESSED["title"] : $schedule_data["title"];
                                    $schedule_data["code"] = $PROCESSED["code"];
                                    $schedule_data["schedule_type"] = $schedule_type_map[$schedule_data["schedule_type"]];
                                    $new_schedule = new Models_Schedule($schedule_data);
                                    $block_audience = Models_Schedule_Audience::getSlotMembers($PROCESSED["course_id"], $ENTRADA_USER->getActiveOrganisation());
                                    $child_block_list = array();
                                    $slot_data_list = array();
                                    $default_slot_spaces = 2;//ceil((count($block_audience) / count($children)));
                                    $result = $new_schedule->insert();
                                    if ($result) {
                                        $new_parent_id = $result->getID();
                                        // Create child schedules for the new rotation stream based on each template.
                                        foreach ($schedules as $schedule) {
                                            $children = $template_rotation_children[$schedule->getID()];
                                            if ($children) {
                                                $i = 1;
                                                foreach ($children as $child_block) {
                                                    $child_block_data = $child_block->toArray();
                                                    unset($child_block_data["schedule_id"]);
                                                    unset($child_block_data["schedule_parent_id"]);
                                                    $child_block_data["cperiod_id"] = $schedule_data["cperiod_id"];
                                                    $child_block_data["draft_id"] = $PROCESSED["draft_id"];
                                                    $child_block_data["course_id"] = $PROCESSED["course_id"];
                                                    $child_block_data["created_date"] = time();
                                                    $child_block_data["created_by"] = $ENTRADA_USER->getActiveID();
                                                    $child_block_data["schedule_parent_id"] = $new_parent_id;
                                                    $child_block_data["schedule_type"] = $schedule_type_map[$child_block_data["schedule_type"]];
                                                    $child_block_data["schedule_order"] = $i++;
                                                    $new_child = new Models_Schedule($child_block_data);

                                                    $child_block_list[] = $new_child->createValueString();
                                                }
                                            }
                                        }
                                        Models_Schedule::addAllSchedules(implode($child_block_list, ","));
                                        $created_blocks = Models_Schedule::fetchAllByParentID($new_parent_id);
                                        if ($created_blocks) {
                                            foreach ($created_blocks as $created_block) {
                                                $slot_data = array(
                                                    "schedule_id" => $created_block->getID(),
                                                    "slot_type_id" => "1",
                                                    "slot_spaces" => $default_slot_spaces,//(isset($PROCESSED["spaces"]) && $PROCESSED["spaces"] ? $PROCESSED["spaces"] : $default_slot_spaces),
                                                    "created_date" => time(),
                                                    "created_by" => $ENTRADA_USER->getActiveID(),
                                                    "updated_date" => time(),
                                                    "updated_by" => $ENTRADA_USER->getActiveID()
                                                );
                                                $new_slot = new Models_Schedule_Slot($slot_data);
                                                $slot_data_list[] = $new_slot->createValueString();
                                                /*
                                                if ($child_result && $PROCESSED["auto_assign"]) {
                                                    shuffle($block_audience);
                                                    foreach ($block_audience as $audience_member) {
                                                        $slot_id = Models_Schedule_Slot::fetchRandomSlotID($new_parent_id, $audience_member["proxy_id"]);
                                                        $audience_data = array(
                                                            "schedule_id" => $slot_id["schedule_id"],
                                                            "schedule_slot_id" => $slot_id["schedule_slot_id"],
                                                            "audience_type" => "proxy_id",
                                                            "audience_value" => $audience_member["proxy_id"]
                                                        );
                                                        $slot_audience = new Models_Schedule_Audience($audience_data);
                                                        $slot_audience->insert();
                                                    }
                                                }
                                                */
                                            }
                                            Models_Schedule_Slot::addAllSlots(implode($slot_data_list, ","));
                                        }
                                    } else {
                                        add_error($translate->_("An error occurred when attempting attempting to add new rotation schedule."));
                                    }
                                    // Redirect after all schedules have been processed.
                                    header("Location: " . ENTRADA_URL . "/admin/" . $MODULE . "?section=edit-draft&draft_id=" . $PROCESSED["draft_id"]);
                                    exit;
                                } else {
                                    add_error($translate->_("No templates found to import for the draft schedule's curriculum period."));
                                    $STEP = 1;
                                }
                            } else {
                                add_error($translate->_("No curriculum period found for draft schedule."));
                                $STEP = 1;
                            }
                        } else {
                            add_error($translate->_("No curriculum type found for draft schedule."));
                            $STEP = 1;
                        }
                    } else {
                        $STEP = 1;
                    }
                    break;
            }

            switch ($STEP) {
                case 2 :
                    break;
                case 1 :
                default :
                    if ($ERROR) {
                        echo display_error();
                    }
                    ?>
                    <script type="text/javascript">
                        jQuery(function($) {
                            $(".auto-assign").on("change", function(e) {
                                if ($(this).is(":checked")) {
                                    $("#slot-type-list")
                                } else {

                                }
                            });
                        });
                    </script>
                    <h1><?php echo $SECTION_TEXT["title"]; ?></h1>
                    <form action="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>?section=<?php echo $SECTION; ?>&draft_id=<?php echo $PROCESSED["draft_id"]; ?>" method="POST" class="form-horizontal">
                        <input type="hidden" name="step" value="2" />
                        <div class="control-group">
                            <label class="control-label" for="title"><?php echo $translate->_("Rotation Name"); ?></label>
                            <div class="controls">
                                <input type="text" id="title" name="title" value="<?php echo (isset($PROCESSED["title"]) ? $PROCESSED["title"] : ""); ?>" />
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label" for="title"><?php echo $translate->_("Shortname"); ?></label>
                            <div class="controls">
                                <div class="input-prepend span12">
                                    <span class="add-on"><?php echo strtoupper($course->getCourseCode()); ?>-</span><input type="text" id="code" name="code" value="<?php echo (isset($PROCESSED["code"]) ? $PROCESSED["code"] : ""); ?>" />
                                </div>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label" for="course-id"><?php echo $translate->_("Course"); ?></label>
                            <div class="controls">
                                <input type="hidden" readonly="readonly" name="course_id" value="<?php echo $course->getID(); ?>" />
                                <input type="text" readonly="readonly" value="<?php echo $course->getCourseCode() . " - " . $course->getCourseName(); ?>" />
                            </div>
                        </div>
                        <div class="control-group hide">
                            <label class="control-label" for="title"><?php echo $translate->_("Default spaces per block"); ?></label>
                            <div class="controls">
                                <input type="number" id="spaces" name="spaces" value="<?php echo (isset($PROCESSED["spaces"]) ? (int)$PROCESSED["spaces"] : 2); ?>" /> <span class="content-small muted">
                            </div>
                        </div>
                        <!--
                        <div class="control-group">
                            <label class="control-label" for="auto-assign"><?php echo $translate->_("Auto-assign"); ?></label>
                            <div class="controls">
                                <input type="checkbox" id="auto-assign" name="auto_assign" value="1" />
                            </div>
                        </div>
                        -->
                        <div class="control-group hide" id="slot-type-list">
                            <label class="control-label" for="slot-types"><?php echo $translate->_("Slot Audience Types"); ?></label>
                            <div class="controls">
                                <?php
                                $slot_types = Models_Schedule_Slot::getSlotTypes();
                                if ($slot_types) {
                                    foreach($slot_types as $slot_type) {
                                        ?>
                                        <div class="span3">
                                            <input type="checkbox" value="<?php echo $slot_type["slot_type_id"]; ?>" name="slot_types[]" /> <?php echo $slot_type["slot_type_description"]; ?>
                                        </div>
                                        <?php
                                    }
                                }
                                ?>
                            </div>
                        </div>
                        <div class="row-fluid">
                            <a href="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=edit-draft&draft_id=" . $PROCESSED["draft_id"]; ?>" class="btn btn-default"><?php echo $translate->_("Back"); ?></a>
                            <input type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("Save"); ?>" />
                        </div>
                    </form>
                    <?php
                    break;
            }
        } else {
            echo display_error($translate->_("You are attempting to work on a draft that you do not have access to. Please contact the program assistant responsible for this rotational schedule to be added as an author."));
            ?>
            <div class="row-fluid">
                <a href="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=drafts"; ?>" class="btn btn-default"><?php echo $translate->_("Back"); ?></a>
            </div>
            <?php
        }
    } else {
        echo display_error($translate->_("You are attempting to work on a draft that you do not have access to. Please contact the program assistant responsible for this rotational schedule to be added as an author."));
        ?>
        <div class="row-fluid">
            <a href="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=drafts"; ?>" class="btn btn-default"><?php echo $translate->_("Back"); ?></a>
        </div>
        <?php
    }

}