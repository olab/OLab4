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
 * API to handle interaction with form components
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_ROTATION_SCHEDULE"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("rotationschedule", "update", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

    ob_clear_open_buffers();

    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));

    $request = ${"_" . $request_method};

    if ($ENTRADA_USER->getActiveRole() == "admin") {
        if (isset($request["proxy_id"]) && $tmp_input = clean_input($request["proxy_id"], "int")) {
            $PROCESSED["proxy_id"] = $tmp_input;
        } else {
            $PROCESSED["proxy_id"] = $ENTRADA_USER->getActiveID();
        }
    } else {
        $PROCESSED["proxy_id"] = $ENTRADA_USER->getActiveID();
    }

    switch ($request_method) {
        case "POST" :
            switch ($request["method"]) {
                case "new-draft" :
                    if (isset($_POST["draft_title"]) && $tmp_input = clean_input($_POST["draft_title"], array("trim", "striptags"))) {
                        $PROCESSED["draft_title"] = $tmp_input;
                    } else {
                        add_error($translate->_("A draft title is required."));
                    }

                    if (isset($_POST["course_id"]) && $tmp_input = clean_input($_POST["course_id"], "int")) {
                        if (Models_Course::checkCourseOwner($tmp_input, $ENTRADA_USER->getActiveId()) || ($ENTRADA_USER->getActiveRole() == "admin")) {
                            $PROCESSED["course_id"] = $tmp_input;
                        } else {
                            add_error($translate->_("You do not have permission to create a rotation schedule for this course."));
                        }
                    } else {
                        add_error($translate->_("A draft requires a course to be selected."));
                    }

                    if (isset($_POST["cperiod_id"]) && $tmp_input = clean_input($_POST["cperiod_id"], "int")) {
                        $PROCESSED["cperiod_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("A draft requires a curriculum period to be selected."));
                    }

                    if (!$ERROR) {
                        $draft_data = array(
                            "draft_title" => $PROCESSED["draft_title"],
                            "cperiod_id" => $PROCESSED["cperiod_id"],
                            "updated_date" => time(),
                            "updated_by" => $ENTRADA_USER->getActiveID(),
                            "created_date" => time(),
                            "created_by" => $ENTRADA_USER->getActiveID(),
                            "status" => "draft"
                        );

                        if (isset($PROCESSED["course_id"])) {
                            $draft_data["course_id"] = $PROCESSED["course_id"];
                        }

                        $draft = new Models_Schedule_Draft($draft_data);

                        if ($draft->insert()) {
                            $draft_author_data = array(
                                "cbl_schedule_draft_id" => $draft->getID(),
                                "author_value" => $ENTRADA_USER->getActiveID(),
                                "author_type" => "proxy_id",
                                "created_date" => time(),
                                "created_by" => $ENTRADA_USER->getActiveID()
                            );
                            $author = new Models_Schedule_Draft_Author($draft_author_data);
                            if (!$author->insert()) {
                                add_error($translate->_("Failed to add draft author."));
                            }
                            $draft_course_author_data = array(
                                "cbl_schedule_draft_id" => $draft->getID(),
                                "author_value" => $draft->getCourseID(),
                                "author_type" => "course_id",
                                "created_date" => time(),
                                "created_by" => $ENTRADA_USER->getActiveID()
                            );
                            $course_author = new Models_Schedule_Draft_Author($draft_course_author_data);
                            if (!$course_author->insert()) {
                                add_error($translate->_("Failed to add course draft author."));
                            }
                            if (!has_error()) {
                                echo json_encode(array("status" => "success", "data" => $draft->getID()));
                            } else {
                                echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("Failed to add draft")));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                case "save-slot" :
                    ini_set("display_errors", 1);
                    $method = "insert";
                    if (isset($request["slot_id"]) && $tmp_input = clean_input($request["slot_id"], "int")) {
                        $PROCESSED["schedule_slot_id"] = $tmp_input;
                        $method = "update";
                    } else {
                        $PROCESSED["created_date"] = time();
                        $PROCESSED["created_by"] = $ENTRADA_USER->getActiveID();
                    }

                    if (isset($request["slot_type"]) && $tmp_input = clean_input($request["slot_type"], "int")) {
                        $PROCESSED["slot_type_id"] = $tmp_input;
                    }

                    if (isset($request["slot_spaces"]) && $tmp_input = clean_input($request["slot_spaces"], "int")) {
                        $PROCESSED["slot_spaces"] = $tmp_input;
                    }

                    if (isset($request["schedule_id"]) && $tmp_input = clean_input($request["schedule_id"], "int")) {
                        $PROCESSED["schedule_id"] = $tmp_input;
                    }

                    $PROCESSED["course_id"] = NULL;
                    if ($PROCESSED["slot_type_id"] == 2) {
                        if (isset($request["slot_course"]) && $tmp_input = clean_input($request["slot_course"], "int")) {
                            $PROCESSED["course_id"] = $tmp_input;
                        }
                    }

                    if (isset($request["audience"]) && is_array($request["audience"])) {
                        foreach ($request["audience"] as $audience_member) {
                            $tmp_input = clean_input($audience_member, "int");
                            if ($tmp_input) {
                                $PROCESSED["audience"][] = $tmp_input;
                            }
                        }
                    }
                    if (!$ERROR) {
                        $PROCESSED["updated_date"] = time();
                        $PROCESSED["updated_by"] = $ENTRADA_USER->getActiveID();
                        $slot = new Models_Schedule_Slot();
                        if ($PROCESSED["schedule_slot_id"]) {
                            $slot = Models_Schedule_Slot::fetchRowByID($PROCESSED["schedule_slot_id"]);
                        }

                        if ($slot->fromArray($PROCESSED)->{$method}()) {
                            $slot_data = $slot->toArray();
                            $slot_type = $slot->getSlotType();
                            $slot_data["slot_type"] = $slot_type["slot_type_description"];
                            $slot_data["slot_occupants"] = array();
                            if ($PROCESSED["audience"] && is_array($PROCESSED["audience"])) {
                                foreach ($PROCESSED["audience"] as $audience_member) {
                                    $current_member = Models_Schedule_Audience::fetchRowBySlotIDTypeValue($slot->getID(), "proxy_id", $audience_member);
                                    $member = User::fetchRowByID($audience_member);
                                    if ($current_member) {
                                        $slot_data["slot_occupants"][] = array(
                                            "id"        => $current_member->getID(),
                                            "proxy_id"  => $current_member->getAudienceValue(),
                                            "fullname"  => $member->getFullname(false)
                                        );
                                    } else {
                                        $new_member = new Models_Schedule_Audience(array(
                                            "schedule_id"       => $PROCESSED["schedule_id"],
                                            "schedule_slot_id"  => $slot->getID(),
                                            "audience_type"     => "proxy_id",
                                            "audience_value"    => $audience_member
                                        ));
                                        if ($new_member->insert()) {
                                            $slot_data["slot_occupants"][] = array(
                                                "id"        => $new_member->getID(),
                                                "proxy_id"  => $audience_member,
                                                "fullname"  => $member->getFullname(false)
                                            );
                                        }
                                    }
                                }
                            }
                            echo json_encode(array("status" => "success", "data" => $slot_data));
                        }
                    }

                    break;
                case "remove-occupant" :
                    if (isset($request["saudience_id"]) && $tmp_input = clean_input($request["saudience_id"], "int")) {
                        $PROCESSED["saudience_id"] = $tmp_input;
                    }

                    if ($PROCESSED["saudience_id"]) {
                        $audience_member = Models_Schedule_Audience::fetchRowByID($PROCESSED["saudience_id"]);
                        if ($audience_member->fromArray(array("deleted_date" => time()))->update()) {
                            echo json_encode(array("status" => "success", "data" => array($PROCESSED["saudience_id"])));
                        }
                    }
                    break;
                case "shift-blocks" :
                    $day_seconds = 86400;

                    if (isset($request["schedule_id"]) && $tmp_input = clean_input($request["schedule_id"], "int")) {
                        $PROCESSED["schedule_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("An invalid schedule ID has been provided, please try again."));
                    }

                    if (isset($request["number_of_days"]) && $tmp_input = clean_input($request["number_of_days"], "int")) {
                        $PROCESSED["number_of_days"] = $tmp_input;
                    } else {
                        add_error($translate->_("An invalid number of days has been provided, please try again."));
                    }

                    if (isset($request["shift_direction"]) && $tmp_input = clean_input($request["shift_direction"], array("trim", "striptags"))) {
                        $PROCESSED["shift_direction"] = $tmp_input;
                        if ($PROCESSED["shift_direction"] != "future") {
                            $day_seconds = ($day_seconds * -1);
                        }
                    } else {
                        add_error($translate->_("An invalid shift direction has been provided, please try again."));
                    }

                    if (!$ERROR) {
                        $adjusted_slots = array();
                        $slots = Models_Schedule::fetchAllByOrgByTypeByParent($ENTRADA_USER->getActiveOrganisation(), $PROCESSED["schedule_id"]);
                        if ($slots) {
                            foreach ($slots as $slot) {
                                $new_dates = array(
                                    "start_date" => $slot->getStartDate() + ($day_seconds * $PROCESSED["number_of_days"]),
                                    "end_date" => $slot->getEndDate() + ($day_seconds * $PROCESSED["number_of_days"]),
                                );
                                if ($slot->fromArray($new_dates)->update()) {
                                    $adjusted_slots[] = array(
                                        "slot_id" => $slot->getID(),
                                        "start_date" => date($translate->_("Y-m-d"), $new_dates["start_date"]),
                                        "end_date" => date($translate->_("Y-m-d"), $new_dates["end_date"])
                                    );
                                } else {
                                    $ERROR++;
                                    global $db;
                                    application_log("error", "An error occurred while attempting to update a schedule slot [".$slot->getID()."], DB said: ".$db->ErrorMsg());
                                }
                            }
                            if (!$ERROR) {
                                echo json_encode(array("status" => "success", "data" => $adjusted_slots));
                            } else {
                                echo json_encode(array("status" => "error", "data" => array($translate->_("An error occurred while attempting to shift the slots."))));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "data" => array($translate->_("No slots could be found attached to the parent schedule."))));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }

                    break;
                case "import-csv" :
                    ini_set("auto_detect_line_endings", "1");

                    if (isset($request["draft_id"]) && $tmp_input = clean_input($request["draft_id"], "int")) {
                        $PROCESSED["draft_id"] = $tmp_input;
                    }

                    $draft = Models_Schedule_Draft::fetchRowByID($PROCESSED["draft_id"]);

                    $fp = fopen($_FILES["csv"]["tmp_name"], "r");
                    if ($fp) {
                        $error_schedules = array();
                        $line_counter = 1;
                        $i = 0;
                        while (($data = fgetcsv($fp)) !== FALSE) {
                            if ($i > 0) {
                                $staff_number = 0;
                                $current_slots = array();
                                foreach ($data as $index => $code) {
                                    $block = "";
                                    if ($index > 0) {
                                        if (!empty($code)) {
                                            $blocks = Models_Schedule::fetchAllByCodeIndex(strtoupper($code), $index, $draft->getCourseID());
                                            if ($blocks) {
                                                foreach ($blocks as $block) {
                                                    if ($block) {
                                                        $slot = NULL;
                                                        if ($block->getDraftID() == $PROCESSED["draft_id"]) {
                                                            $slot = Models_Schedule_Slot::fetchAllByScheduleID($block->getScheduleID(), "1");
                                                        } else {
                                                            $slot = Models_Schedule_Slot::fetchAllByScheduleID($block->getScheduleID(), "2");
                                                        }

                                                        if ($slot) {
                                                             foreach ($current_slots as $current_slot) {
                                                                 if ($current_slot["schedule_order"] == $index) {
                                                                     $current_audience_membership = Models_Schedule_Audience::fetchRowByID($current_slot["saudience_id"]);
                                                                    break;
                                                                 }
                                                             }

                                                             $audience_membership = Models_Schedule_Audience::fetchRowBySlotIDTypeValue($slot[0]->getID(), "proxy_id", $proxy_id);

                                                            // if (isset($current_audience_membership) && $current_audience_membership && (($current_audience_membership && !$audience_membership) || ($current_audience_membership->getScheduleSlotID() == $slot[0]->getID()))) {
                                                                // $current_audience_membership->fromArray(array("deleted_date" => time()))->update();
                                                            // }

                                                             if (!$audience_membership) {
                                                                 $membership_data["schedule_slot_id"]    = $slot[0]->getID();
                                                                 $membership_data["audience_type"]       = "proxy_id";
                                                                 $membership_data["audience_value"]      = $proxy_id;
                                                                 $membership_data["schedule_id"]         = $block->getID();
                                                                 $audience_membership = new Models_Schedule_Audience($membership_data);
                                                                 if ($audience_membership->insert()) {
                                                                     $SUCCESS++;
                                                                 } else {
                                                                     $error_schedules[$line_counter][$index] = $code;
                                                                 }
                                                             }
                                                        }
                                                    }
                                                }
                                            }
                                        } else {
                                            foreach ($current_slots as $current_slot) {
                                                if ($current_slot["schedule_order"] == $index) {
                                                    $audience_membership = Models_Schedule_Audience::fetchRowByID($current_slot["saudience_id"]);
                                                    if ($audience_membership) {
                                                        $audience_membership->fromArray(array("deleted_date" => time()))->update();
                                                    }
                                                    break;
                                                }
                                            }
                                        }
                                    } else {
                                        $staff_number = clean_input($code, "numeric");
                                        $proxy_id = $ENTRADA_USER->fetchProxyByNumber($staff_number);
                                        $current_slots = Models_Schedule_Audience::fetchAllByProxyID($proxy_id);
                                    }
                                }
                                $line_counter++;
                            }
                            $i++;
                        }
                    } else {
                        Entrada_Utilities_Flashmessenger::addMessage($translate->_("No file was selected"),"error");
                    }

                    $error_lines = array();
                    if (!empty($error_schedules)) {
                        foreach ($error_schedules as $line => $codes) {
                            $line = sprintf($translate->_("Line <strong>%s</strong> contained the following errors: "), $line);
                            foreach ($codes as $row => $code) {
                                $line .= sprintf($translate->_("row <strong>%s</strong> with code <srong>%s</srong> "), $row, $code);
                            }
                            $error_lines[] = $line;
                        }
                    }


                    if (!empty($error_lines)) {
                        Entrada_Utilities_Flashmessenger::addMessage(implode("<br />", $error_lines));
                    } else {
                        if($SUCCESS != 0) {
                            Entrada_Utilities_Flashmessenger::addMessage($translate->_("Successfully imported CSV"));
                        }
                    }

                    header("Location: " . ENTRADA_URL . "/admin/".$MODULE."?section=edit-draft&draft_id=" . $PROCESSED["draft_id"]);
                    exit;

                    break;
                case "import-rotations-csv" :
                    ini_set("auto_detect_line_endings", "1");
                    ini_set("display_errors", 1);

                    $schedule_block_total = 0;

                    if (isset($request["draft_id"]) && $tmp_input = clean_input($request["draft_id"], "int")) {
                        $PROCESSED["draft_id"] = $tmp_input;
                    }

                    if (isset($request["block_type_id"]) && $tmp_input = clean_input($request["block_type_id"], "int")) {
                        $PROCESSED["block_type_id"] = $tmp_input;

                        $blocktypes = Models_BlockType::fetchRowByID($PROCESSED["block_type_id"]);
                        if ($blocktypes) {
                            $schedule_block_total = $blocktypes->getNumberOfBlocks();
                        }
                    } else {
                        add_error($translate->_("An invalid block template was provided."));
                    }

                    if (!$schedule_block_total) {
                        add_error($translate->_("Unable to find the total number of blocks for this block template."));
                    }

                    if (!has_error()) {
                        $draft = Models_Schedule_Draft::fetchRowByID($PROCESSED["draft_id"]);

                        $fp = false;

                        if ($_FILES["rotation-csv"]["tmp_name"]) {
                            $fp = fopen($_FILES["rotation-csv"]["tmp_name"], "r");
                        } else {
                            add_error($translate->_("No CSV file was selected to import."));
                        }

                        $error_schedules = array();
                        $line_counter = 1;
                        $i = 0;

                        if ($fp) {
                            while (($data = fgetcsv($fp)) !== false) {
                                if ($i > 0) {
                                    $staff_number = 0;
                                    $current_slots = array();
                                    $schedule = "";
                                    $fullname = "";
                                    $shortname = "";
                                    $blocks = "";
                                    $slots = "";

                                    foreach ($data as $index => $rotation) {
                                        $fullname = $data[1];
                                        $shortname = $data[0];
                                        $course_data = Models_Course::fetchRowByID($draft->getCourseID());
                                        if ($course_data) {
                                            $course = $course_data;
                                        } else {
                                            $course = "";
                                        }

                                        $duplicate_check = Models_Schedule::fetchDuplicateCheck($fullname, $shortname, $course->getOrganisationID(), $draft->getCourseID(), $draft->getID());
                                        if (!$duplicate_check) {
                                            if (!$schedule) {
                                                $schedule_data["title"] = $fullname;
                                                $schedule_data["code"] = strtoupper($shortname);
                                                $schedule_data["schedule_type"] = "rotation_stream";
                                                $schedule_data["schedule_parent_id"] = 0;
                                                $schedule_data["organisation_id"] = $course->getOrganisationID();
                                                $schedule_data["course_id"] = $draft->getCourseID();
                                                $schedule_data["start_date"] = strtotime("July 1st");
                                                $schedule_data["end_date"] = strtotime("June 30th + 1 year + 23 hours 59 minutes 59 seconds");
                                                $schedule_data["cperiod_id"] = $draft->getCPeriodID();
                                                $schedule_data["block_type_id"] = $PROCESSED["block_type_id"];
                                                $schedule_data["draft_id"] = $draft->getID();
                                                $schedule_data["created_date"] = time();
                                                $schedule_data["created_by"] = $ENTRADA_USER->getActiveID();

                                                $schedule = new Models_Schedule($schedule_data);
                                                if ($schedule->insert()) {
                                                    $count_blocks = 0;
                                                    $blocks["start_date"] = "";
                                                    $blocks["end_date"] = "";
                                                    while ($count_blocks != $schedule_block_total) {
                                                        $count_blocks++;
                                                        if ($count_blocks == 1) {
                                                            $last_id = Models_Schedule::fetchLastID();
                                                            $num_weeks = (52 / $schedule_block_total);
                                                            $blocks["title"] = "Block " . $count_blocks;
                                                            $blocks["schedule_type"] = "rotation_block";
                                                            $blocks["schedule_parent_id"] = $last_id;
                                                            $blocks["organisation_id"] = $course->getOrganisationID();
                                                            $blocks["course_id"] = $draft->getCourseID();
                                                            $blocks["start_date"] = strtotime("July 1st");
                                                            $blocks["end_date"] = strtotime("July 1st + " . $num_weeks . " weeks - 1 week + 5 days + 23 hours 59 minutes 59 seconds");
                                                            $end_date = date("F jS Y H:i:s", $blocks["end_date"]);
                                                            $blocks["cperiod_id"] = $draft->getCPeriodID();
                                                            $blocks["block_type_id"] = $PROCESSED["block_type_id"];
                                                            $blocks["draft_id"] = $draft->getID();
                                                            $blocks["schedule_order"] = $count_blocks;
                                                            $blocks["created_date"] = time();
                                                            $blocks["created_by"] = $ENTRADA_USER->getActiveID();
                                                            $schedule = new Models_Schedule($blocks);
                                                            if ($schedule->insert()) {
                                                                $schedule_id = Models_Schedule::fetchLastID();
                                                                $slots["schedule_id"] = $schedule_id;
                                                                $slots["slot_type"] = "1";
                                                                $slots["slot_type_id"] = "1";
                                                                $slots["slot_spaces"] = "2";
                                                                $slots["course_id"] = $draft->getCourseID();
                                                                $slots["created_date"] = time();
                                                                $slots["created_by"] = $ENTRADA_USER->getActiveID();
                                                                $slot = new Models_Schedule_Slot($slots);
                                                                if (!$slot->insert()) {
                                                                    $error_schedules[$line_counter][$index] = $rotation;
                                                                    add_error("There was a problem importing the new rotations into the rotation schedule.");
                                                                }
                                                            } else {
                                                                $error_schedules[$line_counter][$index] = $rotation;
                                                                add_error("There was a problem importing the new rotations into the rotation schedule.");
                                                            }
                                                        } else {
                                                            $num_weeks = (52 / $schedule_block_total);
                                                            $blocks["title"] = "Block " . $count_blocks;
                                                            $blocks["schedule_type"] = "rotation_block";
                                                            $blocks["schedule_parent_id"] = $last_id;
                                                            $blocks["organisation_id"] = $course->getOrganisationID();
                                                            $blocks["course_id"] = $draft->getCourseID();
                                                            $blocks["start_date"] = strtotime($end_date . " + 1 second");
                                                            $current_start_date = date("F jS Y H:i:s", $blocks["start_date"]);
                                                            $blocks["end_date"] = strtotime($current_start_date . " + " . $num_weeks . " weeks - 1 week + 6 days + 23 hours 59 minutes 59 seconds");
                                                            $end_date = date("F jS Y H:i:s", $blocks["end_date"]);
                                                            $blocks["cperiod_id"] = $draft->getCPeriodID();
                                                            $blocks["block_type_id"] = $PROCESSED["block_type_id"];
                                                            $blocks["draft_id"] = $draft->getID();
                                                            $blocks["schedule_order"] = $count_blocks;
                                                            $blocks["created_date"] = time();
                                                            $blocks["created_by"] = $ENTRADA_USER->getActiveID();
                                                            $schedule = new Models_Schedule($blocks);
                                                            if ($schedule->insert()) {
                                                                $schedule_id = Models_Schedule::fetchLastID();
                                                                $slots["schedule_id"] = $schedule_id;
                                                                $slots["slot_type"] = "1";
                                                                $slots["slot_type_id"] = "1";
                                                                $slots["slot_spaces"] = "2";
                                                                $slots["course_id"] = $draft->getCourseID();
                                                                $slots["created_date"] = time();
                                                                $slots["created_by"] = $ENTRADA_USER->getActiveID();
                                                                $slot = new Models_Schedule_Slot($slots);
                                                                if (!$slot->insert()) {
                                                                    $error_schedules[$line_counter][$index] = $rotation;
                                                                    add_error("There was a problem importing the new rotations into the rotation schedule.");
                                                                }
                                                            } else {
                                                                $error_schedules[$line_counter][$index] = $rotation;
                                                                add_error("There was a problem importing the new rotations into the rotation schedule.");
                                                            }
                                                        }
                                                    }
                                                    $SUCCESS++;
                                                    add_success("Successfully imported rotations CSV");
                                                } else {
                                                    add_error("There was a problem importing your rotations.");
                                                    $error_schedules[$line_counter][$index] = $rotation;
                                                }
                                            } else {
                                                add_error("No schedule has been set.");
                                            }
                                        } else {
                                            add_error("There is a duplicate in your rotations. ");
                                        }
                                    }
                                    $line_counter++;
                                } else {
                                    add_error("There was a problem importing your rotations. ");
                                }
                                $i++;
                            }

                            $error_lines = array();
                            if (!empty($error_schedules)) {
                                foreach ($error_schedules as $line => $codes) {
                                    $line = sprintf($translate->_("Line <strong>%s</strong> contained the following errors: "), $line);
                                    foreach ($codes as $row => $code) {
                                        $line .= sprintf($translate->_("row <strong>%s</strong> with code <srong>%s</srong> "), $row, $code);
                                    }
                                    $error_lines[] = $line;
                                }
                            }

                            Entrada_Utilities_Flashmessenger::addMessage($translate->_("Successfully imported rotations CSV"));

                            if (!empty($error_lines)) {
                                Entrada_Utilities_Flashmessenger::addMessage(implode("<br />", $error_lines));
                            }
                            header("Location: " . ENTRADA_URL . "/admin/" . $MODULE . "?section=edit-draft&draft_id=" . $PROCESSED["draft_id"]);
                            exit;

                        } else {
                            Entrada_Utilities_Flashmessenger::addMessage($translate->_("No file was selected"), "error");
                            header("Location: " . ENTRADA_URL . "/admin/" . $MODULE . "?section=edit-draft&draft_id=" . $PROCESSED["draft_id"]);
                        }
                    }
                    break;
                case "copy-draft-rotations" :

                    if (isset($request["draft_id"]) && $tmp_input = clean_input($request["draft_id"], "int")) {
                        $PROCESSED["draft_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No draft ID to copy to provided."));
                    }

                    if (isset($request["copy_draft_id"]) && $tmp_input = clean_input($request["copy_draft_id"], "int")) {
                        $PROCESSED["copy_draft_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No schedule provided to copy rotations from."));
                    }

                    if (!$ERROR) {

                        $current_draft = Models_Schedule_Draft::fetchRowByID($PROCESSED["draft_id"]);
                        $previous_draft = Models_Schedule_Draft::fetchRowByID($PROCESSED["copy_draft_id"]);

                        if ($current_draft) {
                            if ($previous_draft) {

                                // Fetch all templates associated with the current draft's enrolment period.
                                $curriculum_type = Models_Curriculum_Type::fetchRowByCPeriodID($current_draft->getCPeriodID());
                                if ($curriculum_type) {
                                    $curriculum_period = Models_Curriculum_Period::fetchRowByID($current_draft->getCPeriodID());
                                    if ($curriculum_period) {
                                        $schedules = Models_Schedule::fetchAllTemplatesByCPeriodID($curriculum_period->getCperiodID());
                                        if ($schedules) {
                                            $template_rotation_children = array();
                                            foreach($schedules as $template_rotation) {
                                                $template_rotation_children[$template_rotation->getID()] = $template_rotation->getChildren();
                                            }
                                            // For each rotation in the draft that is being copied from, we need to create a new rotation with the same title in the current draft.
                                            $rotations = Models_Schedule::fetchAllByDraftID($previous_draft->getID(), "rotation_stream");
                                            if ($rotations) {
                                                $unique_parents = array();
                                                $child_block_list = array();
                                                $slot_data_list = array();
                                                foreach ($rotations as $rotation) {
                                                    $slot_spaces = false;
                                                    $slot_type_id = false;
                                                    $slot_course_id = false;
                                                    $block = Models_Schedule::fetchRowByParentID($rotation->getID());
                                                    if ($block) {
                                                        // Attempt to fetch an example of slots from the children of the rotation we are copying from.
                                                        $slot = Models_Schedule_Slot::fetchRowByScheduleID($block->getID());
                                                        if (isset($slot) && $slot) {
                                                            $slot_spaces = $slot->getSlotSpaces();
                                                            $slot_type_id = $slot->getSlotTypeID();
                                                            $slot_course_id = $slot->getCourseID();
                                                        }
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
                                                    $schedule_data["schedule_parent_id"] = 0;
                                                    $schedule_data["draft_id"] = $current_draft->getID();
                                                    $schedule_data["course_id"] = $current_draft->getCourseID();
                                                    $schedule_data["title"] = $rotation->getTitle();
                                                    $schedule_data["code"] = $rotation->getCode();
                                                    $schedule_data["schedule_type"] = $schedule_type_map[$schedule_data["schedule_type"]];
                                                    // Write to copied from to maintain a history of rotations that are cloned.
                                                    $schedule_data["copied_from"] = $rotation->getID();
                                                    $schedule_data["created_date"] = time();
                                                    $schedule_data["created_by"] = $ENTRADA_USER->getActiveId();
                                                    $new_schedule = new Models_Schedule($schedule_data);
                                                    $result = $new_schedule->insert();
                                                    if ($result) {
                                                        // Create child schedules for the new rotation stream based on each template.
                                                        foreach ($schedules as $schedule) {
                                                            $children = $template_rotation_children[$schedule->getID()];
                                                            if ($children) {
                                                                $new_parent_id = $result->getID();
                                                                $i = 1;
                                                                foreach ($children as $child_block) {
                                                                    $child_block_data = $child_block->toArray();
                                                                    unset($child_block_data["schedule_id"]);
                                                                    unset($child_block_data["schedule_parent_id"]);
                                                                    $child_block_data["cperiod_id"] = $schedule_data["cperiod_id"];
                                                                    $child_block_data["draft_id"] = $current_draft->getID();
                                                                    $child_block_data["course_id"] = $current_draft->getCourseID();
                                                                    $child_block_data["created_date"] = time();
                                                                    $child_block_data["created_by"] = $ENTRADA_USER->getActiveID();
                                                                    $child_block_data["schedule_parent_id"] = $new_parent_id;
                                                                    $child_block_data["schedule_type"] = $schedule_type_map[$child_block_data["schedule_type"]];
                                                                    $child_block_data["schedule_order"] = $i++;
                                                                    $new_child = new Models_Schedule($child_block_data);

                                                                    $unique_parents[$new_parent_id] = array(
                                                                        "schedule_parent_id" => $new_parent_id,
                                                                        "slot_type_id" => ($slot_type_id ? $slot_type_id : 1),
                                                                        "slot_spaces" => ($slot_spaces ? $slot_spaces : 2),
                                                                        "course_id" => ($slot_course_id ? $slot_course_id : NULL)
                                                                    );

                                                                    $child_block_list[] = $new_child->createValueString();
                                                                }
                                                            }
                                                        }
                                                    } else {
                                                        add_error($translate->_("An error occurred when attempting attempting to add new rotation schedule."));
                                                    }
                                                }

                                                $max_sql_line_limit = 500;
                                                for($i = 0; $i < ceil(count($child_block_list) / $max_sql_line_limit); $i++) {
                                                    $sliced_array = array_slice($child_block_list, $i * $max_sql_line_limit, $max_sql_line_limit);
                                                    Models_Schedule::addAllSchedules(implode($sliced_array, ","));
                                                }

                                                if ($unique_parents) {
                                                    foreach ($unique_parents as $unique_parent) {
                                                        $created_blocks = Models_Schedule::fetchAllByParentID($unique_parent["schedule_parent_id"]);
                                                        if ($created_blocks) {
                                                            foreach ($created_blocks as $created_block) {
                                                                $slot_data = array(
                                                                    "schedule_id" => $created_block->getID(),
                                                                    "slot_type_id" => $unique_parent["slot_type_id"],
                                                                    "slot_spaces" => $unique_parent["slot_spaces"],
                                                                    "course_id" => $unique_parent["course_id"],
                                                                    "created_date" => time(),
                                                                    "created_by" => $ENTRADA_USER->getActiveID(),
                                                                    "updated_date" => time(),
                                                                    "updated_by" => $ENTRADA_USER->getActiveID()
                                                                );
                                                                $new_slot = new Models_Schedule_Slot($slot_data);
                                                                $slot_data_list[] = $new_slot->createValueString();
                                                            }
                                                        }
                                                    }
                                                }

                                                for ($i = 0; $i < ceil(count($slot_data_list) / $max_sql_line_limit); $i++) {
                                                    $sliced_array = array_slice($slot_data_list, $i * $max_sql_line_limit, $max_sql_line_limit);
                                                    Models_Schedule_Slot::addAllSlots(implode($sliced_array, ","));
                                                }

                                                echo json_encode(array("status" => "success", "data" => array("Successfully copied rotation schedules from " . $previous_draft->getTitle() . ".")));
                                            } else {
                                                add_error($translate->_("No rotations found within the selected schedule."));
                                            }
                                        } else {
                                            add_error($translate->_("No templates found to import for the current draft schedule's curriculum period."));
                                        }
                                    } else {
                                        add_error($translate->_("No curriculum period found for draft schedule."));
                                    }
                                } else {
                                    add_error($translate->_("No curriculum type found for draft schedule."));
                                }
                            } else {
                                add_error($translate->_("Unable to fetch schedule that is being copied from."));
                            }
                        } else {
                            add_error($translate->_("Unable to fetch schedule that is being edited."));
                        }

                        if ($ERROR) {
                            echo json_encode(array("status" => "error", "data" => array($ERRORSTR)));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => array($ERRORSTR)));
                    }

                    break;
                case "release-slots" :

                    if (isset($request["draft_id"]) && $tmp_input = clean_input($request["draft_id"], "int")) {
                        $PROCESSED["draft_id"] = $tmp_input;
                    }

                    if (isset($request["course_ids"]) && is_array($request["course_ids"])) {
                        foreach ($request["course_ids"] as $course_id) {
                            $tmp_input = clean_input($course_id, "int");
                            if ($tmp_input && (Models_Course::checkCourseOwner($tmp_input, $ENTRADA_USER->getActiveId()) || $ENTRADA_USER->getActiveRole() == "admin")) {
                                $PROCESSED["course_ids"][] = $course_id;
                            }
                        }
                    }

                    if ($PROCESSED["draft_id"]) {
                        $schedules = Models_Schedule::fetchAllByDraftID($PROCESSED["draft_id"]);
                        if ($schedules) {
                            foreach ($schedules as $schedule) {
                                $children = $schedule->getChildren();
                                foreach ($children as $child) {
                                    $slots = Models_Schedule_Slot::fetchAllByScheduleID($child->getID());
                                    if ($slots) {
                                        foreach ($slots as $slot) {
                                            if ($slot->getSlotTypeID() == "1") {
                                                $audience = $slot->getAudience();
                                                $audience_difference = $slot->getSlotSpaces() - count($audience);

                                                if ($audience_difference > 0) {

                                                    if (!isset($PROCESSED["course_ids"]) || empty($PROCESSED["course_ids"])) {
                                                        $PROCESSED["course_ids"][] = NULL;
                                                    }

                                                    $slot->fromArray(array("slot_spaces" => count($audience)))->update();

                                                    $off_service_slot_data = $slot->toArray();

                                                    unset($off_service_slot_data["schedule_slot_id"]);
                                                    $off_service_slot_data["slot_type_id"]  = "2";
                                                    $off_service_slot_data["slot_spaces"]   = $audience_difference;
                                                    $off_service_slot_data["created_date"]  = time();
                                                    $off_service_slot_data["created_by"]    = $ENTRADA_USER->getID();
                                                    $off_service_slot_data["updated_date"]  = time();
                                                    $off_service_slot_data["updated_by"]    = $ENTRADA_USER->getID();

                                                    foreach ($PROCESSED["course_ids"] as $i => $course_id) {
                                                        $off_service_slot_data["course_id"]  = $course_id;
                                                        $off_service_slot = new Models_Schedule_Slot($off_service_slot_data);
                                                        if (!$off_service_slot->insert()) {
                                                            $ERROR++;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            if (!$ERROR) {
                                echo json_encode(array("status" => "success", "data" => array($translate->_("Successfully created off service slots."))));
                            } else {
                                echo json_encode(array("status" => "error", "data" => array($translate->_("An error occurred while attempting to create off service slots."))));
                            }
                        }
                    }

                    break;
                case "edit-slot-member" :

                    if (isset($request["block_order"]) && $tmp_input = clean_input($request["block_order"], "int")) {
                        $PROCESSED["block_order"] = $tmp_input;
                    } else {
                        add_error($translate->_("An invalid block order was provided."));
                    }

                    if (isset($request["block_type_id"]) && $tmp_input = clean_input($request["block_type_id"], "int")) {
                        $PROCESSED["block_type_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("An invalid block type id was provided."));
                    }

                    if (isset($request["course_id"]) && $tmp_input = clean_input($request["course_id"], "int")) {
                        $PROCESSED["course_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("An invalid course ID was provided."));
                    }

                    if (isset($request["proxy_id"]) && $tmp_input = clean_input($request["proxy_id"], "int")) {
                        $PROCESSED["proxy_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("An invalid proxy ID was provided."));
                    }

                    if (isset($request["max_repeat_count"]) && $tmp_input = clean_input($request["max_repeat_count"], "int")) {
                        $PROCESSED["max_repeat_count"] = $tmp_input;
                    } else {
                        add_error($translate->_("An invalid max repeat number was provided."));
                    }

                    if (isset($request["repeat_count"]) && $tmp_input = clean_input($request["repeat_count"], "int")) {
                        $PROCESSED["repeat_count"] = $tmp_input;
                        if ($PROCESSED["repeat_count"] < 1 || $PROCESSED["repeat_count"] > $PROCESSED["max_repeat_count"]) {
                            add_error($translate->_("The provided repeat number was not in range."));
                        }
                    } else {
                        add_error($translate->_("An invalid repeat number was provided."));
                    }

                    if (isset($_GET["draft_id"]) && $tmp_input = clean_input($_GET["draft_id"], "int")) {
                        $PROCESSED["draft_id"] = $tmp_input;
                    } else {
                        if (isset($request["draft_id"]) && $tmp_input = clean_input($request["draft_id"], "int")) {
                            $PROCESSED["draft_id"] = $tmp_input;
                        } else {
                            add_error($translate->_("An invalid draft ID was provided."));
                        }
                    }

                    if (!$ERROR) {

                        $code_string = false;

                        $PROCESSED["block_ids"] = array();
                        if (isset($request["block_ids"]) && is_array($request["block_ids"])) {
                            foreach ($request["block_ids"] as $block_id) {
                                $tmp_input = clean_input($block_id, "int");
                                if ($tmp_input) {
                                    $PROCESSED["block_ids"][] = $tmp_input;
                                }
                            }
                        } else {
                            add_error($translate->_("No block ids provided."));
                        }

                        $PROCESSED["slot_type_ids"] = array();
                        if (isset($request["slot_type_ids"]) && is_array($request["slot_type_ids"])) {
                            foreach ($request["slot_type_ids"] as $slot_type_id) {
                                $tmp_input = clean_input($slot_type_id, "int");
                                if ($tmp_input) {
                                    $PROCESSED["slot_type_ids"][] = $tmp_input;
                                }
                            }
                        } else {
                            add_error($translate->_("No slot type ids provided."));
                        }

                        $PROCESSED["original_schedule_ids"] = array();
                        if (isset($request["original_schedule_ids"]) && is_array($request["original_schedule_ids"])) {
                            foreach ($request["original_schedule_ids"] as $rotation_id) {
                                $tmp_input = clean_input($rotation_id, "int");
                                if ($tmp_input) {
                                    $PROCESSED["original_schedule_ids"][] = $tmp_input;
                                }
                            }
                        }

                        $codes = array_fill(0, $PROCESSED["repeat_count"], "");
                        $code_strings = array_fill(0, $PROCESSED["repeat_count"], "");

                        for ($i = 0; $i < count($codes); $i++) {
                            $codes[$i] = array();
                        }

                        // Remove the learner's previous memberships for this block type, position, and draft.
                        $unique_block_ids = array_diff($PROCESSED["original_schedule_ids"], $PROCESSED["block_ids"]);
                        $unique_block_parent_ids = array();

                        foreach ($unique_block_ids as $unique_block_id) {
                            $block_schedule = Models_Schedule::fetchRowByID($unique_block_id);
                            $schedule_parent = Models_Schedule::fetchRowByID($block_schedule->getScheduleParentID());
                            $unique_block_parent_ids[] = $schedule_parent->getID();
                        }
                        $current_audience_membership = Models_Schedule_Audience::fetchAllByProxyID($PROCESSED["proxy_id"]);

                        if ($current_audience_membership) {

                            foreach ($PROCESSED["block_ids"] as $block_id) {

                                $block_schedule = Models_Schedule::fetchRowByID($block_id);

                                for ($i = 0; $i < $PROCESSED["repeat_count"]; $i++) { //Fetch the next block in the sequence
                                    $next_block_schedule = Models_Schedule::fetchRowByParentIDBlockTypeIDScheduleOrder($block_schedule->getScheduleParentID(), $block_schedule->getBlockTypeID(), ($block_schedule->getOrder() + $i));

                                    if ($next_block_schedule) {
                                        Models_Schedule::blockContainsSchedule($current_audience_membership, $PROCESSED["repeat_count"], $PROCESSED["block_order"], $PROCESSED["block_type_id"], $PROCESSED["draft_id"], $next_block_schedule->getID(), $codes, $unique_block_parent_ids);
                                    }
                                }
                            }

                            foreach ($unique_block_ids as $block_id) {

                                $block_schedule = Models_Schedule::fetchRowByID($block_id);

                                for ($i = 0; $i < $PROCESSED["repeat_count"]; $i++) {
                                    $next_block_schedule = Models_Schedule::fetchRowByParentIDBlockTypeIDScheduleOrder($block_schedule->getScheduleParentID(), $block_schedule->getBlockTypeID(), ($block_schedule->getOrder() + $i));

                                    if ($next_block_schedule) {
                                        Models_Schedule::blockContainsSchedule($current_audience_membership, $PROCESSED["repeat_count"], $PROCESSED["block_order"], $PROCESSED["block_type_id"], $PROCESSED["draft_id"], $next_block_schedule->getID(), $codes, $unique_block_parent_ids);
                                    }
                                }
                            }
                        }

                        // Add learner as audience member of all requested blocks.
                        if ($PROCESSED["block_ids"] && $PROCESSED["slot_type_ids"]) {

                            foreach ($PROCESSED["block_ids"] as $key => $block_id) {

                                $block_schedule = Models_Schedule::fetchRowByID($block_id);

                                for ($i = 0; $i < $PROCESSED["repeat_count"]; $i++) {

                                    $next_block_schedule = Models_Schedule::fetchRowByParentIDBlockTypeIDScheduleOrder($block_schedule->getScheduleParentID(), $block_schedule->getBlockTypeID(), ($block_schedule->getOrder() + $i));

                                    if ($next_block_schedule) {
                                        $schedule_parent = Models_Schedule::fetchRowByID($block_schedule->getScheduleParentID());
                                        $slot = Models_Schedule_Slot::fetchAllByScheduleID($next_block_schedule->getID(), $PROCESSED["slot_type_ids"][$key]);
                                        if ($slot) {
                                            $audience_data = array(
                                                "schedule_id" => $slot[0]->getScheduleID(),
                                                "schedule_slot_id" => $slot[0]->getID(),
                                                "audience_type" => "proxy_id",
                                                "audience_value" => $PROCESSED["proxy_id"]
                                            );

                                            $audience_membership = new Models_Schedule_Audience($audience_data);
                                            if ($audience_membership->getAudienceValue()) {
                                                $membership = $audience_membership->insert();
                                                if ($membership && !in_array($schedule_parent->getCode(), $codes[$i])) {
                                                    $codes[$i][] = $schedule_parent->getCode();
                                                }
                                            }
                                        } else {
                                            echo json_encode(array("status" => "error", "data" => $translate->_("There was a problem attempting to schedule the user into this slot.")));
                                        }
                                    } else {
                                        echo json_encode(array("status" => "error", "data" => $translate->_("There was a problem attempting to schedule the user into this schedule.")));
                                    }
                                }
                            }
                        }

                        $ctr = 0;

                        foreach ($codes as $code_list) {
                            sort($code_list);
                            foreach ($code_list as $key => $code) {
                                $code_strings[$ctr] .= $code;
                                if ($key < (count($code_list) - 1)) {
                                    $code_strings[$ctr] .= " / ";
                                }
                            }
                            $ctr++;
                        }

                        echo json_encode(array("status" => "success", "data" => $code_strings));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                case "remove-learner" :

                    $PROCESSED["block_ids"] = array();
                    if (isset($request["block_ids"]) && is_array($request["block_ids"])) {
                        foreach ($request["block_ids"] as $block_id) {
                            $tmp_input = clean_input($block_id, "int");
                            if ($tmp_input) {
                                $PROCESSED["block_ids"][] = $tmp_input;
                            }
                        }
                    } else {
                        add_error($translate->_("No block ids provided."));
                    }

                    $PROCESSED["slot_type_ids"] = array();
                    if (isset($request["slot_type_ids"]) && is_array($request["slot_type_ids"])) {
                        foreach ($request["slot_type_ids"] as $slot_type_id) {
                            $tmp_input = clean_input($slot_type_id, "int");
                            if ($tmp_input) {
                                $PROCESSED["slot_type_ids"][] = $tmp_input;
                            }
                        }
                    } else {
                        add_error($translate->_("No slot type ids provided."));
                    }

                    if (isset($request["course_id"]) && $tmp_input = clean_input($request["course_id"], "int")) {
                        $PROCESSED["course_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("An invalid course ID was provided."));
                    }

                    if (isset($request["proxy_id"]) && $tmp_input = clean_input($request["proxy_id"], "int")) {
                        $PROCESSED["proxy_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("An invalid proxy ID was provided."));
                    }

                    if (!$ERROR) {
                        $output = array();
                        foreach ($PROCESSED["block_ids"] as $key => $block_id) {
                            $schedule = Models_Schedule::fetchRowByID($block_id);
                            if ($schedule) {
                                $slots = Models_Schedule_Slot::fetchAllByScheduleID($schedule->getID(), $PROCESSED["slot_type_ids"][$key]);
                                if ($slots) {
                                    $audience_member = Models_Schedule_Audience::fetchRowBySlotIDTypeValue($slots[0]->getID(), "proxy_id", $PROCESSED["proxy_id"]);
                                    if ($audience_member) {
                                        if ($audience_member->fromArray(array("deleted_date" => time()))->update()) {
                                            $output[] = $schedule->getTitle();
                                        }
                                    }
                                } else {
                                    echo json_encode(array("status" => "error", "data" => $translate->_("Unable to find slot.")));
                                }
                            } else {
                                echo json_encode(array("status" => "error", "data" => sprintf($translate->_("Unable to find schedule with block ID # %s"), $PROCESSED["block_id"])));
                            }
                        }
                        echo json_encode(array("status" => "success", "data" => $translate->_("Successfully removed learner from slot(s)")));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                case "remove-off-service-learner" :
                    if (isset($request["saudience_id"]) && $tmp_input = clean_input($request["saudience_id"], "int")) {
                        $PROCESSED["saudience_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("An invalid audience ID was provided."));
                    }

                    if (!$ERROR) {
                        $audience_membership = Models_Schedule_Audience::fetchRowByID($PROCESSED["saudience_id"]);
                        if ($audience_membership) {
                            if ($audience_membership->fromArray(array("deleted_date" => time()))->update()) {
                                echo json_encode(array("status" => "success", "data" => "Successfully removed audience member"));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "data" => "Valid audience membership ID, but could not find record."));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                case "remove-draft-author" :
                    if (isset($request["draft_id"]) && $tmp_input = clean_input($request["draft_id"], "int")) {
                        $PROCESSED["draft_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("An invalid draft ID was provided."));
                    }

                    if (isset($request["proxy_id"]) && $tmp_input = clean_input($request["proxy_id"], "int")) {
                        $PROCESSED["proxy_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("An invalid user ID was provided."));
                    }

                    if (!$ERROR) {
                        $draft_author_data = array(
                            "cbl_schedule_draft_id" => $PROCESSED["draft_id"],
                            "author_value" => $PROCESSED["proxy_id"],
                            "author_type" => "proxy_id"
                        );

                        $draft_authors = new Models_Schedule_Draft_Author($draft_author_data);
                        $draft_authors_exists = Models_Schedule_Draft_Author::isAuthor($PROCESSED["draft_id"], $PROCESSED["proxy_id"]);
                        $user = User::fetchRowByID($PROCESSED["proxy_id"]);
                        if ($draft_authors_exists) {
                            if ($draft_authors->delete()) {
                                add_success("Successfully updated ");
                                echo json_encode(array("status" => "success", "data" => array("proxy_id" => $PROCESSED["proxy_id"], "draft_id" => $PROCESSED["draft_id"])));

                            } else {
                                add_error("There was a problem adding ".$user->getFirstname()." ".$user->getLastname()." to this draft.");
                            }
                        } else {
                            add_error($user->getFirstname()." ".$user->getLastname()." is not an author of this draft.");
                        }
                    }
                    if ($ERROR) {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                case "add-leave" :
                    if (isset($request["leave_id"]) && $tmp_input = clean_input($request["leave_id"], "int")) {
                        $PROCESSED["leave_id"] = $tmp_input;
                    }

                    if (isset($request["proxy_id"]) && $tmp_input = clean_input($request["proxy_id"], "int")) {
                        $PROCESSED["proxy_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("An invalid user ID was provided."));
                    }

                    if (isset($request["start_date"])) {
                        $tmp_input = clean_input(strtotime($request["start_date"] . (isset($request["start_time"]) ? " " . $request["start_time"] : "")), "int");
                        if ($tmp_input) {
                            $PROCESSED["start_date"] = $tmp_input;
                        } else {
                            add_error($translate->_("Invalid start date was provided."));
                        }
                    } else {
                        add_error($translate->_("Start date was not provided."));
                    }

                    if (isset($request["end_date"]) && $tmp_input = clean_input(strtotime($request["end_date"] . (isset($request["end_time"]) ? " " . $request["end_time"] : "")), "int")) {
                        $PROCESSED["end_date"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid end date was provided."));
                    }

                    if (isset($PROCESSED["start_date"]) && isset($PROCESSED["end_date"]) && ($PROCESSED["start_date"] > $PROCESSED["end_date"])) {
                        add_error($translate->_("The the end date has to come after the start date."));
                    }

                    if (isset($request["days_used"])) {
                        $tmp_input = clean_input($request["days_used"], "int");
                        if (is_int($tmp_input) && $tmp_input >= 1){
                            $PROCESSED["days_used"] = $tmp_input;
                        } else {
                            add_error($translate->_("Total days used must be a number greater than 0."));
                        }
                    } else {
                        add_error($translate->_("Invalid days used provided."));
                    }

                    if (isset($request["weekdays_used"]) && $request["weekdays_used"] != "") {
                        if (intval($request["weekdays_used"]) || $request["weekdays_used"] == "0") {
                            $tmp_input = $request["weekdays_used"] == "0" ? 0 : clean_input($request["weekdays_used"], "int");
                            if ($tmp_input >= 0) {
                                $PROCESSED["weekdays_used"] = $tmp_input;
                            } else {
                                add_error($translate->_("Weekdays used must be a positive number."));
                            }
                        } else {
                            add_error($translate->_("Weekdays used must be a positive number."));
                        }
                    } else {
                        $PROCESSED["weekdays_used"] = null;
                    }

                    if (isset($request["weekend_days_used"]) && $request["weekend_days_used"] != "") {
                        if (intval($request["weekend_days_used"]) || $request["weekend_days_used"] == "0") {
                            $tmp_input = $request["weekend_days_used"] == "0" ? 0 : clean_input($request["weekend_days_used"], "int");
                            if ($tmp_input >= 0) {
                                $PROCESSED["weekend_days_used"] = $tmp_input;
                            } else {
                                add_error($translate->_("Weekend days used must be a positive number."));
                            }
                        } else {
                            add_error($translate->_("Weekend days used must be a positive number."));
                        }
                    } else {
                        $PROCESSED["weekend_days_used"] = null;
                    }

                    if (isset($request["comments"]) && $tmp_input = clean_input($request["comments"], array("trim", "striptags"))) {
                        $PROCESSED["comments"] = $tmp_input;
                    } else {
                        $PROCESSED["comments"] = null;
                    }

                    if (isset($request["leave_type"]) && $tmp_input = clean_input($request["leave_type"], array("trim", "striptags"))) {
                        $leave_type = Models_Leave_Type::fetchRowByID($request["leave_type"]);
                        if ($leave_type) {
                            $PROCESSED["type_id"] = $leave_type->getID();
                        } else {
                            add_error($translate->_("The leave type selected was invalid."));
                        }
                    } else {
                        add_error($translate->_("The leave type selected was invalid."));
                    }

                    if (!$ERROR) {
                        if (isset($PROCESSED["leave_id"])) {
                            $PROCESSED["updated_by"] = $ENTRADA_USER->getActiveID();
                            $PROCESSED["updated_date"] = time();
                            $leave = Models_Leave_Tracking::fetchRowByID($PROCESSED["leave_id"]);
                            $leave->fromArray($PROCESSED);
                            $mode = "update";
                        } else {
                            $PROCESSED["created_by"] = $ENTRADA_USER->getActiveID();
                            $PROCESSED["created_date"] = time();
                            $leave = new Models_Leave_Tracking($PROCESSED);
                            $mode = "insert";
                        }

                        if ($leave->{$mode}()) {
                            $leave_type = Models_Leave_Type::fetchRowByID($leave->getTypeID());
                            echo json_encode(array("status" => "success", "data" => "success"));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("Failed to add leave")));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                case "save-preference" :

                    if (isset($request["section"]) && $tmp_input = clean_input($request["section"], array("trim", "striptags"))) {
                        $PROCESSED["section"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid section"));
                    }

                    if (isset($request["pref_name"]) && $tmp_input = clean_input($request["pref_name"], array("trim", "striptags"))) {
                        $PROCESSED["pref_name"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid preference name"));
                    }

                    if (isset($request["pref_val"]) && $tmp_input = clean_input($request["pref_val"], array("trim", "striptags", "alpha"))) {
                        $PROCESSED["pref_val"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid preference value"));
                    }

                    if (!$ERROR) {
                        $_SESSION[APPLICATION_IDENTIFIER]["rotationschedule"][$PROCESSED["section"]][$PROCESSED["pref_name"]] = $PROCESSED["pref_val"];
                        echo json_encode(array("status" => "success", "data" => true));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }

                    break;
                case "set-curriculum-period":
                    $cperiod_id = null;

                    if (isset($request["cperiod_id"])) {
                        $cperiod_id = clean_input($request["cperiod_id"], array("int"));
                    }

                    $assessments_base = new Entrada_Utilities_Assessments_Base();
                    $_SESSION[APPLICATION_IDENTIFIER]["rotationschedule"]["leave"]["cperiod_id"] = $cperiod_id;
                    $assessments_base->updateAssessmentPreferences("rotationschedule");

                    echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully changed curriculum period setting"), "data" => $cperiod_id));
                    break;
                case "switch-rotation-mapped-objective":

                    if (isset($request["schedule_id"]) && $tmp_input = clean_input($request["schedule_id"], "int")) {
                        $PROCESSED["schedule_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No schedule provided."));
                    }

                    if (isset($request["course_id"]) && $tmp_input = clean_input($request["course_id"], "int")) {
                        $PROCESSED["course_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No course provided."));
                    }

                    if (isset($request["objective_id"]) && $tmp_input = clean_input($request["objective_id"], "int")) {
                        $PROCESSED["objective_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No objective provided."));
                    }

                    if (isset($request["likelihood_id"]) && $tmp_input = clean_input($request["likelihood_id"], "int")) {
                        $PROCESSED["likelihood_id"] = $tmp_input;
                    } else {
                        $PROCESSED["likelihood_id"] = null;
                    }

                    if (!isset($request["priority"]) && $PROCESSED["likelihood_id"]) {
                        add_error($translate->_("No priority provided."));
                    } else if (isset($request["priority"]) && $tmp_input = clean_input($request["priority"], "trim")) {
                        $PROCESSED["priority"] = ($tmp_input == "true") ? true : false;
                    } else {
                        $PROCESSED["priority"] = false;
                    }

                    if (has_error()) {
                        echo json_encode(array("status" => "error", "data" => $ERROR));
                        exit;
                    }

                    $remap = false;
                    $rotation_course_objectives_model = new Models_Schedule_CourseObjective();

                    // Remove previous mapping for this objective and rotation.
                    $previous_mappings = $rotation_course_objectives_model->fetchAllByScheduleIDObjectiveIDCourseID($PROCESSED["schedule_id"], $PROCESSED["objective_id"], $PROCESSED["course_id"]);
                    if ($previous_mappings) {
                        foreach ($previous_mappings as $previous_mapping) {
                            if (!$previous_mapping->fromArray(
                                array(
                                    "deleted_date"  => time(),
                                    "deleted_by"    => $ENTRADA_USER->getActiveID()
                                )
                            )->update()) {
                                add_error($translate->_("Unable to remove previous mapping."));
                                application_log("error", "Unable to remove previous mappings for rotation objectives, DB said " . $db->ErrorMsg());
                            }
                        }
                    }

                    if (has_error()) {
                        echo json_encode(array("status" => "error", "data" => $ERROR));
                        exit;
                    }

                    // If provided with a new likelihood, save it as the new mapping, otherwise we are done.
                    if ($PROCESSED["likelihood_id"]) {
                        $remap = true;
                        $rotation_course_objectives_model = new Models_Schedule_CourseObjective(array(
                            "schedule_id"       => $PROCESSED["schedule_id"],
                            "objective_id"      => $PROCESSED["objective_id"],
                            "course_id"         => $PROCESSED["course_id"],
                            "likelihood_id"     => $PROCESSED["likelihood_id"],
                            "priority"          => $PROCESSED["priority"],
                            "created_date"      => time(),
                            "created_by"        => $ENTRADA_USER->getActiveID()
                        ));
                        if (!$rotation_course_objectives_model->insert()) {
                            add_error($translate->_("Unable to add new mapping."));
                            application_log("error", "Unable to add new mapping for rotation objective, DB said " . $db->ErrorMsg());
                        }
                    }

                    if (has_error()) {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    } else {
                        $msg = $remap ? $translate->_("Successfully mapped objective.") : $translate->_("Successfully un-mapped objective.");
                        echo json_encode(array("status" => "success", "data" => array($msg)));
                    }

                    break;
                case "switch-rotation-mapped-objective-priority":

                    if (isset($request["schedule_id"]) && $tmp_input = clean_input($request["schedule_id"], "int")) {
                        $PROCESSED["schedule_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No schedule provided."));
                    }

                    if (isset($request["course_id"]) && $tmp_input = clean_input($request["course_id"], "int")) {
                        $PROCESSED["course_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No course provided."));
                    }

                    if (isset($request["objective_id"]) && $tmp_input = clean_input($request["objective_id"], "int")) {
                        $PROCESSED["objective_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("No objective provided."));
                    }

                    if (!isset($request["priority"])) {
                        add_error($translate->_("No priority provided."));
                    } else if ($tmp_input = clean_input($request["priority"], "trim")) {
                        $PROCESSED["priority"] = $tmp_input == "true" ? true : false;
                    } else {
                        $PROCESSED["priority"] = false;
                    }

                    if (has_error()) {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        exit;
                    }

                    $rotation_course_objectives_model = new Models_Schedule_CourseObjective();

                    // Fetch and update likelihoods for previous mapping for this objective and rotation.
                    $previous_mappings = $rotation_course_objectives_model->fetchAllByScheduleIDObjectiveIDCourseID($PROCESSED["schedule_id"], $PROCESSED["objective_id"], $PROCESSED["course_id"]);

                    if ($previous_mappings) {
                        foreach ($previous_mappings as $previous_mapping) {
                            if (!$previous_mapping->fromArray(
                                array(
                                    "priority"      => $PROCESSED["priority"],
                                    "updated_date"  => time(),
                                    "updated_by"    => $ENTRADA_USER->getActiveID()
                                )
                            )->update()) {
                                add_error($translate->_("Unable to update priority of previous mapping."));
                                application_log("error", "Unable to update priority of mapping for rotation objectives, DB said " . $db->ErrorMsg());
                            }
                        }
                    } else {
                        add_error($translate->_("You are attempting to add priority to an objective without a likelihood. Please set a likelihood and try again."));
                    }

                    if (has_error()) {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    } else {
                        echo json_encode(array("status" => "success", "data" => array($translate->_("Successfully switched objective priority."))));
                    }

                    break;
                default:
                    echo json_encode(array("status" => "error", "data" => $DEFAULT_TEXT_LABELS["invalid_post_method"]));
                    break;
            }
            break;
        case "GET" :
            switch ($request["method"]) {
                case "export-csv" :
                    if (isset($request["draft_id"]) && $tmp_input = clean_input($request["draft_id"], "int")) {
                        $PROCESSED["draft_id"] = $tmp_input;
                    }

                    if (isset($request["include_off_service"]) && $tmp_input = clean_input($request["include_off_service"], "int")) {
                        $PROCESSED["include_off_service"] = $tmp_input;
                    }

                    if (isset($request["block_type_id"]) && $tmp_input = clean_input($request["block_type_id"], "int")) {
                        $PROCESSED["block_type_id"] = $tmp_input;
                    }

                    if ($PROCESSED["draft_id"]) {
                        $draft = Models_Schedule_Draft::fetchRowByID($PROCESSED["draft_id"]);
                        $csv_data = $draft->getScheduleTable();

                        if (!empty($csv_data)) {
                            $output[0][] = "Name";
                            $i = 1;
                            foreach ($csv_data["blocks"][$PROCESSED["block_type_id"]] as $block) {
                                $output[0][] = $translate->_("Block ") . $i++ . ": " . date("Y-m-d", $block[0]["start_date"]) . " to " . date("Y-m-d", $block[0]["end_date"]);
                            }

                            if (!isset($PROCESSED["include_off_service"]) || $PROCESSED["include_off_service"] !== true) {
                                $members = array_merge($csv_data["on_service_audience"], $csv_data["unscheduled_on_service_audience"]);
                            } else {
                                $members = array_merge($csv_data["on_service_audience"], $csv_data["unscheduled_on_service_audience"], $csv_data["off_service_audience"]);
                            }

                            $i = 1;
                            foreach ($members as $audience_member) {
                                $output[$i][] = $audience_member["name"] . " (" . $audience_member["number"] . ")";
                                foreach ($csv_data["blocks"][$PROCESSED["block_type_id"]] as $block_key => $block) {
                                    $output[$i][] = (isset($audience_member["slots"][$PROCESSED["block_type_id"]][$block_key][0]["code"]) && $audience_member["slots"][$PROCESSED["block_type_id"]][$block_key][0]["code"] ? $audience_member["slots"][$PROCESSED["block_type_id"]][$block_key][0]["code"] : " ");
                                }
                                $i++;
                            }
                        }

                        if (!empty($output)) {
                            ob_clear_open_buffers();

                            header("Pragma: public");
                            header("Expires: 0");
                            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                            header("Content-Type: application/force-download");
                            header("Content-Type: application/octet-stream");
                            header("Content-Type: text/csv");
                            header("Content-Disposition: attachment; filename=\"csv-".date("Y-m-d").".csv\"");
                            header("Content-Transfer-Encoding: binary");

                            $fp = fopen("php://output", "w");

                            foreach ($output as $row) {
                                fputcsv($fp, $row);
                            }

                            fclose($fp);

                            exit;
                        }
                    }

                    break;
                case "get-filtered-audience" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    }

                    if (isset($request["schedule_slot_id"]) && $tmp_input = clean_input($request["schedule_slot_id"], "int")) {
                        $PROCESSED["schedule_slot_id"] = $tmp_input;
                    }

                    $slot = Models_Schedule_Slot::fetchRowByID($PROCESSED["schedule_slot_id"]);

                    if ($slot) {
                        $slot_type = $slot->getSlotType();
                        $schedule = Models_Schedule::fetchRowByID($slot->getScheduleID());

                        if ($slot_type["slot_type_code"] == "OSL") {
                            $results = Models_Schedule_Audience::getSlotMembers($schedule->getCourseID(), $schedule->getOrganisationID(), $schedule->getID(), $PROCESSED["search_value"]);
                        } else {
                            $results = Models_Schedule_Audience::getSlotMembers($schedule->getCourseID(), $schedule->getOrganisationID(), NULL, $PROCESSED["search_value"], false);
                        }

                        if ($results) {
                            $i = 0;
                            foreach ($results as $result) {
                                $output[$i]["fullname"] = $result["fullname"];
                                $output[$i]["id"] = $result["proxy_id"];
                                $output[$i]["email"] = $result["email"];
                                $i++;
                            }
                            echo json_encode(array("status" => "success", "results" => count($output), "data" => $output));
                        } else {
                            echo json_encode(array("results" => "0", "data" => array("No results")));
                        }
                    } else {
                        echo json_encode(array("results" => "0", "data" => array("No results")));
                    }
                    break;
                case "get-slot-data" :
                    if (isset($request["slot_id"]) && $tmp_input = clean_input($request["slot_id"], "int")) {
                        $PROCESSED["slot_id"] = $tmp_input;
                    }

                    if (!empty($PROCESSED["slot_id"])) {
                        $slot = Models_Schedule_Slot::fetchRowByID($PROCESSED["slot_id"]);
                        if ($slot) {
                            $slot_type = $slot->getSlotType();

                            $slot_audience = $slot->getAudience();
                            $slot_occupants = array();
                            if ($slot_audience) {
                                $i = 0;
                                foreach ($slot_audience as $audience_member) {
                                    $member = User::fetchRowByID($audience_member->getAudienceValue());
                                    if ($member) {
                                        $slot_occupants[$i]["id"] = $audience_member->getSaudienceID();
                                        $slot_occupants[$i]["proxy_id"] = $audience_member->getAudienceValue();
                                        $slot_occupants[$i]["fullname"] = $member->getFullname(false);
                                        $i++;
                                    }
                                }
                            }
                            $schedule = Models_Schedule::fetchRowByID($slot->getScheduleID());
                            echo json_encode(array("status" => "success", "data" => array(
                                "schedule_slot_id"  => $slot->getID(),
                                "slot_type_id"      => $slot_type["slot_type_id"],
                                "slot_type"         => $slot_type["slot_type_description"],
                                "slot_spaces"       => $slot->getSlotSpaces(),
                                "slot_occupants"    => $slot_occupants,
                                "start_date"        => date("Y-m-d", $schedule->getStartDate()),
                                "end_date"          => date("Y-m-d", $schedule->getEndDate()),
                                "course_id"         => $slot->getCourseID()
                            )));
                        }
                    }
                    break;
                case "get-slot-blocks" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = null;
                    }
                    
                    if (isset($request["proxy_id"]) && $tmp_input = clean_input($request["proxy_id"], "int")) {
                        $PROCESSED["proxy_id"] = $tmp_input;
                    } else {
                        add_error("Invalid proxy ID");
                    }

                    if (isset($request["draft_id"]) && $tmp_input = clean_input($request["draft_id"], "int")) {
                        $PROCESSED["draft_id"] = $tmp_input;
                    } else {
                        add_error("Invalid draft ID");
                    }

                    if (isset($request["block_type_id"]) && $tmp_input = clean_input($request["block_type_id"], "int")) {
                        $PROCESSED["block_type_id"] = $tmp_input;
                    } else {
                        add_error("Invalid block length");
                    }

                    if (isset($request["block_order"]) && $tmp_input = clean_input($request["block_order"], "int")) {
                        $PROCESSED["block_order"] = $tmp_input;
                    } else {
                        add_error("Invalid block order");
                    }

                    if (!$ERROR) {
                        $schedules = Models_Schedule::fetchAllByDraftIDScheduleTypeBlockTypeIDScheduleOrder($PROCESSED["draft_id"], "rotation_block", $PROCESSED["block_type_id"], $PROCESSED["block_order"]);

                        if ($schedules) {
                            $memberships = Models_Schedule_Audience::fetchAllByProxyIDDraftID($PROCESSED["proxy_id"], $PROCESSED["draft_id"]);
                            $block_type = Models_BlockType::fetchRowByID($PROCESSED["block_type_id"]);
                            $draft = Models_Schedule_Draft::fetchRowByID($PROCESSED["draft_id"]);
                            $cperiod_id = $draft->getCPeriodID();
                            $course_codes = array();
                            $output = array();

                            foreach ($schedules as $schedule) {

                                $member = false;
                                $schedule_parent = Models_Schedule::fetchRowByID($schedule->getScheduleParentID(), $PROCESSED["search_value"]);
                                if($schedule_parent){
                                    if (!array_key_exists($schedule->getDraftID(), $course_codes)) {
                                        $course = Models_Course::fetchRowByID($schedule->getCourseID());
                                        $course_codes[$schedule->getDraftID()] = strtoupper($course->getCourseCode());
                                    }

                                    if ($memberships) {
                                        foreach ($memberships as $membership) {
                                            if ($membership["schedule_id"] == $schedule->getID()) {
                                                $member = true;
                                            }
                                        }
                                    }

                                    $output[] = array(
                                        "block_id" => $schedule->getID(),
                                        "title" => html_encode(($schedule_parent ? $schedule_parent->getTitle() . ": " : "") . $schedule->getTitle() . " (" . $block_type->getName() . ")"),
                                        "occupied" => $member,
                                        "target_id" => $schedule->getID(),
                                        "target_label" => html_encode(($schedule_parent ? $schedule_parent->getTitle() . ": " : "") . $schedule->getTitle() . " (" . $block_type->getName() . ")"),
                                        "rotation_start_date" => date("Y-m-d", $schedule->getStartDate()),
                                        "rotation_end_date" => date("Y-m-d", $schedule->getEndDate()),
                                        "block_type_name" => $block_type->getName(),
                                        "target_parent" => ($schedule_parent ? $schedule_parent->getID() : 0)
                                    );
                                }
                            }

                            // Sort rotations by title.
                            if ($output) {
                                $sort = array();
                                foreach ($output as $key => $out) {
                                    $sort["title"][$key] = $out["title"];
                                }
                                array_multisort($sort["title"], SORT_ASC, $sort["title"], SORT_ASC, $sort["title"], SORT_ASC, $output);
                            }
                            
                            echo json_encode(array("status" => "success", "data" => $output));

                        } else {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("No schedules found.")));
                        }

                    } else {
                        echo json_encode(array("status" => "error", "msg" => $ERRORSTR));
                    }
                    break;
                case "get-courses" :
                    $courses = Models_Course::fetchAllByOrg($ENTRADA_USER->getActiveOrganisation());
                    if ($courses) {
                        $output = array();
                        foreach ($courses as $course) {
                            $output[] = array(
                                "code" => $course->getCourseCode(),
                                "name" => $course->getCourseName(),
                                "course_id" => $course->getID()
                            );
                        }
                        if (!empty($output)) {
                            echo json_encode($output);
                        }
                    }
                    break;
                case "get-learners" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input($request["search_value"], array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    }

                    if ($ENTRADA_USER->getActiveRole() == "admin") {
                        $users = Models_Leave_Tracking::fetchAllBySearchTerm($PROCESSED["search_value"]);
                    } else {
                        $users = Models_Leave_Tracking::fetchAllByMyCourses($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation(), $PROCESSED["search_value"], "search");
                    }

                    if ($users) {
                        foreach ($users as $user) {

                            $u = Models_User::fetchRowByID($user["proxy_id"]);
                            if (isset($u) && $u) {
                                $output[] = array("fullname" => $u->getFullname(false), "id" => $u->getID(), "email" => $u->getEmail());
                            }
                        }
                        if (!empty($output)) {
                            echo json_encode(array("status" => "success", "results" => count($output), "data" => $output));
                        }
                    } else {
                        echo json_encode(array("results" => "0", "data" => array("No results")));
                    }

                    break;
                case "get-draft-authors" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input($request["search_value"], array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    }

                    $users = User::fetchUsersByGroups($PROCESSED["search_value"], "staff", null, AUTH_APP_ID);
                    if ($users) {
                        foreach ($users as $user) {
                                $output[] = array("fullname" => $user["lastname"] . ", " . $user["firstname"], "id" => $user["proxy_id"], "email" => $user["email"]);
                        }
                        if (!empty($output)) {
                            echo json_encode(array("status" => "success", "results" => count($output), "data" => $output));
                        }
                    } else {
                        echo json_encode(array("results" => "0", "data" => array("No results")));
                    }

                    break;
                case "get-leave-data" :
                    if (isset($request["leave_id"]) && $tmp_input = clean_input($request["leave_id"], "int")) {
                        $PROCESSED["leave_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("You provided an invalid leave ID."));
                    }

                    if (!$ERROR) {
                        $leave = Models_Leave_Tracking::fetchRowByID($PROCESSED["leave_id"]);
                        if ($leave) {
                            $leave_type = Models_Leave_Type::fetchRowByID($leave->getTypeID());
                            echo json_encode(array("status" => "success", "data" => array(
                                "start_date"        => date("Y-m-d", $leave->getStartDate()),
                                "start_time"        => date("H:i", $leave->getStartDate()),
                                "end_date"          => date("Y-m-d", $leave->getEndDate()),
                                "end_time"          => date("H:i", $leave->getEndDate()),
                                "leave_type"        => $leave_type->getTypeValue(),
                                "days_used"         => $leave->getDaysUsed() ? $leave->getDaysUsed() : $translate->_("Please update."),
                                "weekdays_used"     => $leave->getWeekdaysUsed(),
                                "weekend_days_used" => $leave->getWeekendDaysUsed(),
                                "comments"          => $leave->getComments(),
                                "leave_type_id"     => $leave_type->getID()
                            )));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("Unable to fetch tracked leave.")));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
                default:
                    echo json_encode(array("status" => "error", "data" => $DEFAULT_TEXT_LABELS["invalid_get_method"]));
                    break;
            }
            break;
        default :
            echo json_encode(array("status" => "error", "data" => $DEFAULT_TEXT_LABELS["invalid_req_method"]));
            break;
    }

    exit;

}