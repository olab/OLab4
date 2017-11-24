<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Script to fill in other script types for each rotation stream.
 * Note that the script requires the rotation templates to exist
 * with the appropriate block types assigned to their blocks.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Belanger <jb301@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

@set_time_limit(0);
@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");
global $db;

// We need a ton of memory for this.
ini_set("memory_limit", "8192M");

// Fetch all drafts and block types.
$drafts = Models_Schedule_Draft::fetchAllRecords();
$block_types = Models_BlockType::fetchAllRecords();

if ($drafts && $block_types) {
    foreach ($drafts as $draft) {

        // Look at all rotation streams.
        $rotations = Models_Schedule::fetchAllByDraftID($draft->getID(), "rotation_stream");

        if ($rotations) {
            foreach ($rotations as $rotation) {

                echo "\nPROCESSING ROTATION: " . $rotation->getID() . "\n";

                $existing_block_type_id = false;

                // Fetch the rotation's blocks and figure out what block type the streams are based on.
                $blocks = Models_Schedule::fetchAllByParentID($rotation->getID());
                if ($blocks) {

                    // Store an example of slot spaces from an existing block.
                    $slot_spaces = false;
                    $example_slots = Models_Schedule_Slot::fetchAllByScheduleID($blocks[0]->getID());
                    if ($example_slots) {
                        $slot_spaces = $example_slots[0]->getSlotSpaces();
                    } else {
                        echo "No slots found for rotation " . $rotation->getID() . "\n";
                    }

                    if ($slot_spaces) {
                        // Compare counts to determine the block type of the existing blocks.
                        foreach ($block_types as $block_type) {
                            if (count($blocks) == $block_type->getNumberOfBlocks()) {
                                $existing_block_type_id = $block_type->getID();
                            }
                        }

                        if ($existing_block_type_id) {

                            // Update old blocks to reference the block type id.
                            foreach ($blocks as $block) {
                                if ($block->fromArray(array("block_type_id" => $existing_block_type_id))->update()) {
                                    echo "Updated block with [ID " . $block->getID() . "] [ORDER " . $block->getOrder() . "] to [TYPE " . $block->getBlockTypeID() . "]\n";
                                }
                            }
                            echo "\n";

                            // Fetch all templates associated with the draft's enrolment period to create missing blocks.
                            $draft_curriculum_period = $draft->getCPeriodID();
                            $curriculum_type = Models_Curriculum_Type::fetchRowByCPeriodID($draft_curriculum_period);

                            if ($curriculum_type) {

                                $curriculum_period = Models_Curriculum_Period::fetchRowByID($draft_curriculum_period);
                                if ($curriculum_period) {

                                    $template_rotations = Models_Schedule::fetchAllTemplatesByCPeriodID($curriculum_period->getCperiodID());
                                    if ($template_rotations) {

                                        // Create child blocks for the rotation stream based on each template, except the existing block type that was just filled in.
                                        foreach ($template_rotations as $template_rotation) {

                                            $children = $template_rotation->getChildren();

                                            if ($children) {

                                                $i = 1;

                                                foreach ($children as $schedule) {

                                                    $schedule = $schedule->toArray();
                                                    if ($schedule["schedule_type"] == "block" && $schedule["block_type_id"] != $existing_block_type_id) {

                                                        unset($schedule["schedule_id"]);
                                                        $schedule["cperiod_id"] = $rotation->getCPeriodID();
                                                        $schedule["draft_id"] = $rotation->getDraftID();
                                                        $schedule["course_id"] = $rotation->getCourseID();
                                                        $schedule["created_date"] = time();
                                                        $schedule["created_by"] = 1;
                                                        $schedule["schedule_type"] = "rotation_block";
                                                        $schedule["schedule_parent_id"] = $rotation->getID();
                                                        $schedule["schedule_order"] = $i;

                                                        $new_child = new Models_Schedule($schedule);
                                                        $child_result = $new_child->insert();

                                                        $slot_data = array(
                                                            "schedule_id" => $child_result->getID(),
                                                            "slot_type_id" => "1",
                                                            "slot_spaces" => $slot_spaces,
                                                            "created_date" => time(),
                                                            "created_by" => 1,
                                                            "updated_date" => time(),
                                                            "updated_by" => 1
                                                        );
                                                        $slot = new Models_Schedule_Slot($slot_data);
                                                        $new_slot = $slot->insert();

                                                        $i++;

                                                        echo "Added block with [ID " . $child_result->getID() . "] [TYPE " . $child_result->getBlockTypeID() . "] [ORDER " . $child_result->getOrder() . "] to rotation " . $child_result->getScheduleParentID() . "\n";
                                                    }
                                                }
                                            } else {
                                                echo "No block templates found within rotation template " . $template_rotation->getID() . $db->getErrorMsg();
                                            }
                                        }
                                    } else {
                                        echo "No templates found to import for the draft " . $draft->getID() . "'s curriculum period." . $db->ErrorMsg() . "\n";
                                    }
                                } else {
                                    echo "No curriculum period found for draft schedule " . $draft->getID()  . $db->ErrorMsg() . "\n";
                                }
                            } else {
                                echo "No curriculum type found for draft schedule " . $draft->getID() . $db->ErrorMsg() . "\n";
                            }
                        } else {
                            echo "COULD NOT IDENTIFY BLOCK TYPE FOR ROTATION " . $rotation->getID() . "\n";
                        }
                    } else {
                        echo "Could not determine an slot spaces for block " . $blocks[0]->getID() . $db->ErrorMsg() . "\n";
                    }
                } else {
                    echo "No blocks found for rotation " . $rotation->getID() . $db->ErrorMsg() . "\n\n";
                }
            }
        } else {
            echo "No rotations found for draft " . $draft->getID() . $db->ErrorMsg() . "\n";
        }
    }
} else {
    echo "Could not fetch drafts or block types " . $db->ErrorMsg() . "\n";
}
