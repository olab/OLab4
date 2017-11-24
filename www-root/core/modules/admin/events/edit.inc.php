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
 * This file is used to edit existing events in the entrada.events table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EVENTS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("event", "update", false)) {

    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

    if (isset($_GET["mode"]) && $_GET["mode"] == "draft") {
        $is_draft                = true;

        $tables["events"]        = "draft_events";
        $tables["audience"]      = "draft_audience";
        $tables["contacts"]      = "draft_contacts";
        $tables["event_types"]   = "draft_eventtypes";

        $devent_id               = (int) $_GET["id"];
        $where_query             = "WHERE `devent_id` = ".$db->qstr($devent_id);
    } else {
        $is_draft                = false;

        $tables["events"]        = "events";
        $tables["audience"]      = "event_audience";
        $tables["contacts"]      = "event_contacts";
        $tables["event_types"]   = "event_eventtypes";
        $where_query             = "WHERE `event_id` = ".$db->qstr($EVENT_ID);
    }

    if ($EVENT_ID) {
        $query = "    SELECT a.*, b.`organisation_id`
                    FROM `".$tables['events']."` AS a
                    LEFT JOIN `courses` AS b
                    ON b.`course_id` = a.`course_id`".
            $where_query;
        $event_info    = $db->GetRow($query);

        if (!$is_draft) {
            $event = Models_Event::get($EVENT_ID);
            $model = new Models_Event_Audience();
        } else {
            $event = Models_Event_Draft_Event::fetchRowByID($EVENT_ID);
            $model = new Models_Event_Draft_Event_Audience();
            $draft = Models_Event_Draft::fetchRowByID($event->getDraftID());
            ($event ? $EVENT_ID = $event->getEventID() : $EVENT_ID = 0);
        }
        $event_start = $event->getEventStart();

        if ($event_info) {
            if ($event_info["recurring_id"]) {
                $recurring_event_array  = Models_Event::getRecurringEventIds($EVENT_ID);
                $recurring_events       = $recurring_event_array["recurring_events"];
                $re_ids                 = $recurring_event_array["recurring_event_ids"];
            } else {
                $recurring_events = false;
            }

            if (!$ENTRADA_ACL->amIAllowed(new EventResource($event_info["event_id"], $event_info["course_id"], $event_info["organisation_id"]), "update")) {
                application_log("error", "A program coordinator attempted to edit an event [".$EVENT_ID."] that they were not the coordinator for.");
                header("Location: ".ENTRADA_URL."/admin/".$MODULE);
                exit;
            } else {
                if ($is_draft) {
                    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/events/drafts", "title" => "Learning Event Draft Schedule");
                    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/events/drafts?section=edit&draft_id=".$draft->getID(), "title" => $draft->getName());
                }
                $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/events?".replace_query(array("section" => "edit", "id" => $EVENT_ID)), "title" => "Editing Event");

                $PROCESSED["associated_faculty"]    = array();
                $PROCESSED["event_audience_type"]   = "course";
                $PROCESSED["associated_cohort_ids"] = array();
                $PROCESSED["associated_cgroup_ids"] = array();
                $PROCESSED["associated_proxy_ids"]  = array();
                $PROCESSED["event_types"]           = array();

                /* Creates arrays used for model loading of audience */
                $cohort_times_o = array();
                $cohort_times_a = array();
                $proxy_times_o  = array();
                $proxy_times_a  = array();
                $cgroup_times_o = array();
                $cgroup_times_a = array();

                if (!$is_draft) {
                    events_subnavigation($event_info, "edit");
                } else {
                    $EVENT_ID = $event_info["event_id"];
                }

                echo "<h1>Editing Event</h1>\n";

                // Error Checking
                switch($STEP) {
                    case 2 :

                        $stats = array();

                        /**
                         * Required field "course_id" / Course
                         */
                        if ((isset($_POST["course_id"])) && ($course_id = clean_input($_POST["course_id"], array("int")))) {
                            $query    = "    SELECT * FROM `courses`
                                        WHERE `course_id` = ".$db->qstr($course_id)."
                                        AND (`course_active` = '1' OR `course_id` = ".$db->qstr($event_info["course_id"]).")";
                            $result    = $db->GetRow($query);
                            if ($result) {
                                if ($ENTRADA_ACL->amIAllowed(new EventResource(null, $course_id, $event_info["organisation_id"]), "create")) {
                                    $PROCESSED["course_id"] = $course_id;
                                } else {
                                    add_error("You do not have permission to add an event for the course you selected. <br /><br />Please re-select the course you would like to place this event into.");
                                    application_log("error", "A program coordinator attempted to add an event to a course [".$course_id."] they were not the coordinator of.");
                                }
                            } else {
                                add_error("The <strong>Course</strong> you selected does not exist.");
                            }
                        } else {
                            add_error("The <strong>Course</strong> field is a required field.");
                        }

                        $stats = array();
                        /**
                         * Required field "event_title" / Event Title.
                         */
                        if ((isset($_POST["event_title"])) && ($event_title = clean_input($_POST["event_title"], array("notags", "trim")))) {
                            $PROCESSED["event_title"] = $event_title;

                            $changed = false;
                            $changed = md5_change_value($EVENT_ID, "event_id", "event_title", $PROCESSED["event_title"], "events");
                            if ($changed) {
                                $stats[] = "Event Title";
                            }
                        } else {
                            add_error("The <strong>Event Title</strong> field is required.");
                        }

                        /**
                         * Non-required field "event_color" / Event Colour.
                         */
                        if ((isset($_POST["event_color"])) && ($event_color = clean_input($_POST["event_color"], array("notags", "trim")))) {
                            $PROCESSED["event_color"] = $event_color;
                        } else {
                            $PROCESSED["event_color"] = null;
                        }

                        /**
                         * Required field "event_start" / Event Date & Time Start (validated through validate_calendars function).
                         */
                        $start_date = Entrada_Utilities::validate_calendars("event", true, false);
                        if ((isset($start_date["start"])) && ((int) $start_date["start"])) {
                            $PROCESSED["event_start"] = (int) $start_date["start"];
                        }

                        $changed = false;
                        $changed = md5_change_value($EVENT_ID, "event_id", "event_start", $PROCESSED["event_start"], "events");
                        if ($changed) {
                            $stats[] = "Event Start";
                        }

                        /**
                        * Non-required field "room_id" / Event Location
                         */

                        if ((isset($_POST["event_location"])) && ($event_location = clean_input($_POST["event_location"], array("notags", "trim")))) {
                            $PROCESSED["event_location"] = $event_location;
                        } else {
                            $PROCESSED["event_location"] = "";
                        }

                        if ((isset($_POST["room_id"])) && ($room_id = clean_input($_POST["room_id"], array("notags", "trim"))) && $room_id != "-1") {
                            $PROCESSED["room_id"] = $room_id;
                            $PROCESSED["event_location"] = "";
                        } else {
                            $PROCESSED["room_id"] = null;
                        }

                        /**
                         * Required fields "eventtype_id" / Event Type
                         */
                        if (isset($_POST["eventtype_duration_order"]) && ($tmp_duration_order = clean_input($_POST["eventtype_duration_order"], "trim")) && isset($_POST["duration_segment"]) && ($tmp_duration_segment = $_POST["duration_segment"])) {
                            $event_types = explode(",", $tmp_duration_order);
                            $eventtype_durations = $tmp_duration_segment;

                            if (is_array($event_types) && !empty($event_types)) {
                                foreach($event_types as $order => $eventtype_id) {
                                    $eventtype_id = clean_input($eventtype_id, array("trim", "int"));
                                    if ($eventtype_id) {
                                        $query = "SELECT `eventtype_title` FROM `events_lu_eventtypes` WHERE `eventtype_id` = ".$db->qstr($eventtype_id);
                                        $eventtype_title = $db->GetOne($query);
                                        if ($eventtype_title) {
                                            if (isset($eventtype_durations[$order])) {
                                                $duration = clean_input($eventtype_durations[$order], array("trim", "int"));

                                                if ($duration <= 0) {
                                                    add_error("The duration of <strong>".html_encode($eventtype_title)."</strong> (".numeric_suffix(($order + 1))." <strong>" . $translate->_("Event Type") . "</strong> entry) must be greater than zero.");
                                                }
                                            } else {
                                                $duration = 0;

                                                add_error("The duration of <strong>".html_encode($eventtype_title)."</strong> (".numeric_suffix(($order + 1))." <strong>" . $translate->_("Event Type") . "</strong> entry) was not provided.");
                                            }

                                            $PROCESSED["event_types"][] = array($eventtype_id, $duration, $eventtype_title);
                                        } else {
                                            add_error("One of the <strong>event types</strong> you specified was invalid.");
                                        }
                                    }
                                }
                            }
                        }

                        if (!isset($PROCESSED["event_types"]) || !is_array($PROCESSED["event_types"]) || empty($PROCESSED["event_types"])) {
                            add_error("The <strong>" . $translate->_("Event Types") . "</strong> field is required.");
                        }

                        /**
                         * Non-required field "associated_faculty" / Associated Faculty (array of proxy ids).
                         * This is actually accomplished after the event is inserted below.
                         */
                        if (isset($_POST["associated_faculty"])) {
                            $associated_faculty = explode(",", $_POST["associated_faculty"]);
                            foreach($associated_faculty as $contact_order => $proxy_id) {
                                if ($proxy_id = clean_input($proxy_id, array("trim", "int"))) {
                                    $PROCESSED["associated_faculty"][(int) $contact_order] = $proxy_id;
                                    $PROCESSED["contact_role"][(int)$contact_order] = $_POST["faculty_role"][(int) $contact_order];
                                    $PROCESSED["display_role"][$proxy_id] = $_POST["faculty_role"][(int) $contact_order];
                                }
                            }
                        }

                        if (isset($_POST["event_audience_type"]) && ($tmp_input = clean_input($_POST["event_audience_type"], "alphanumeric"))) {
                            $PROCESSED["event_audience_type"] = $tmp_input;
                        }

                        /*
                         * Attendance Required/Optional
                         */
                        if (isset($_POST["attendance_required"]) && ($_POST["attendance_required"] == 1)) {
                            $PROCESSED["attendance_required"] = 1;
                        } else {
                            $PROCESSED["attendance_required"] = 0;
                        }
                        
                        $changed = false;
                        $changed = md5_change_value($EVENT_ID, "event_id", "attendance_required", $PROCESSED["attendance_required"], "events");
                        if ($changed) {
                            $stats[] = "Attendance Required";
                        }

                        switch ($PROCESSED["event_audience_type"]) {
                            case "course" :
                                $PROCESSED["associated_course_ids"][] = $PROCESSED["course_id"];
                                break;
                            case "custom" :
                                /**
                                 * Cohorts.
                                 */
                                if (isset($_POST["event_audience_cohorts_custom_times"])) {
                                    $times = unserialize($_POST["event_audience_cohorts_custom_times"]);
                                    // if not an array then it's a JSON object that needs to be typecast and decoded
                                    if (!is_array($times)) {
                                        $times = (array) json_decode($_POST["event_audience_cohorts_custom_times"]);
                                    }
                                    if (is_array($times)) {
                                        foreach ($times as $time) {
                                            //type cast the object to an array
                                            $time = (array)$time;

                                            //type cast array values to int, etc
                                            $type_cast_values = array();
                                            foreach ($time as $key => $values) {
                                                switch($key) {
                                                    case "audience_type":
                                                        $type_cast_values["audience_type"]  = $time["audience_type"];
                                                        break;
                                                    case "audience_value":
                                                        $type_cast_values["audience_value"] = (int)$time["audience_value"];
                                                        break;
                                                    case "custom_time":
                                                        $type_cast_values["custom_time"]    = (int)$time["custom_time"];
                                                        break;
                                                    case "custom_time_start" :
                                                        $type_cast_values["custom_time_start"] = (int)$time["custom_time_start"];
                                                        break;
                                                    case "custom_time_end" :
                                                        $type_cast_values["custom_time_end"] = (int)$time["custom_time_end"];
                                                        break;
                                                }
                                            }

                                            //adds time offset for comparing recurring granular.
                                            if ($type_cast_values["custom_time"]) {
                                                $type_cast_values["start_time_offset"]  = $type_cast_values["custom_time_start"] - $event_start;
                                                $type_cast_values["end_time_offset"]    = $type_cast_values["custom_time_end"] - $event_start;
                                            } else {
                                                $type_cast_values["start_time_offset"]  = 0;
                                                $type_cast_values["end_time_offset"]    = 0;
                                            }
                                            $cohort_times[$time["audience_value"]]      = $type_cast_values;
                                        }
                                    }
                                }

                                if (isset($_POST["event_audience_cohorts"])) {
                                    $associated_audience = explode(",", $_POST["event_audience_cohorts"]);
                                    if (isset($associated_audience) && is_array($associated_audience) && count($associated_audience)) {
                                        foreach($associated_audience as $audience_id) {
                                            if (strpos($audience_id, "cohort") !== false) {
                                                if ($group_id = clean_input(preg_replace("/[a-z_]/", "", $audience_id), array("trim", "int"))) {
                                                    $query = "    SELECT *
                                                                FROM `groups`
                                                                WHERE `group_id` = ".$db->qstr($group_id)."
                                                                AND (`group_type` = 'cohort' OR `group_type` = 'course_list')
                                                                AND `group_active` = 1";
                                                    $result    = $db->GetRow($query);
                                                    if ($result) {
                                                        $audience_type  = "cohort";
                                                        $audience_value = $group_id;
                                                        if (isset($cohort_times) && is_array($cohort_times) && !empty($cohort_times)) {
                                                            $custom_time        = ($cohort_times[$group_id]["custom_time"] ? $cohort_times[$group_id]["custom_time"] : 0);
                                                            $custom_time_start  = ($cohort_times[$group_id]["custom_time_start"] ? $cohort_times[$group_id]["custom_time_start"] : 0);
                                                            $custom_time_end    = ($cohort_times[$group_id]["custom_time_end"] ? $cohort_times[$group_id]["custom_time_end"] : 0);
                                                        } else {
                                                            // todo update these with the event start and end times
                                                            $custom_time        = 0;
                                                            $custom_time_start  = 0;
                                                            $custom_time_end    = 0;
                                                        }

                                                        $PROCESSED["associated_cohort_ids"][] = $audience_value;
                                                        $audience = ($is_draft ? Models_Event_Draft_Event_Audience::fetchRowByDraftEventIdTypeValue($devent_id, $audience_type, $audience_value) : Models_Event_Audience::fetchRowByEventIdTypeValue($EVENT_ID, $audience_type, $audience_value));

                                                        if ($audience) {
                                                            $audience->setUpdatedBy($ENTRADA_USER->getID());
                                                            $audience->setUpdatedDate(time());
                                                            $audience->setCustomTime($custom_time);
                                                            $audience->setCustomTimeStart($custom_time_start);
                                                            $audience->setCustomTimeEnd($custom_time_end);
                                                        } else {
                                                            $audience_arr = array(
                                                                "event_id"          => $EVENT_ID,
                                                                "audience_type"     => $audience_type,
                                                                "audience_value"    => $audience_value,
                                                                "custom_time"       => $custom_time,
                                                                "custom_time_start" => $custom_time_start,
                                                                "custom_time_end"   => $custom_time_end,
                                                                "updated_date"      => time(),
                                                                "updated_by"        => $ENTRADA_USER->getID()
                                                            );
                                                            $audience = new $model($audience_arr);
                                                        }

                                                        if (isset($cohort_times_a) && is_array($cohort_times_a) && !array_key_exists($audience_value, $cohort_times_a)) {
                                                            $audience_array = $audience->toArray();
                                                            $audience_array = Entrada_Utilities::buildAudienceArray($audience_array);
                                                            $cohort_times_o[$audience_value] = $audience;
                                                            $cohort_times_a[$audience_value] = $audience_array;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    ksort($cohort_times_a);
                                }

                                /**
                                 * Course Groups
                                 */

                                if (isset($_POST["event_audience_course_groups_custom_times"])) {
                                    $times = unserialize($_POST["event_audience_course_groups_custom_times"]);
                                    //if not an array then it's a JSON object that needs to be typecast and decoded
                                    if (!is_array($times)) {
                                        $times = (array) json_decode($_POST["event_audience_course_groups_custom_times"]);
                                    }
                                    if (is_array($times)) {
                                        foreach ($times as $time) {
                                            //type cast the object to an array
                                            $time = (array)$time;
                                            //type cast array values to int, etc
                                            $type_cast_values = array();
                                            foreach ($time as $key => $values) {
                                                switch($key) {
                                                    case "audience_type":
                                                        $type_cast_values["audience_type"]  = $time["audience_type"];
                                                        break;
                                                    case "audience_value":
                                                        $type_cast_values["audience_value"] = (int)$time["audience_value"];
                                                        break;
                                                    case "custom_time":
                                                        $type_cast_values["custom_time"]    = (int)$time["custom_time"];
                                                        break;
                                                    case "custom_time_start" :
                                                        $type_cast_values["custom_time_start"] = (int)$time["custom_time_start"];
                                                        break;
                                                    case "custom_time_end" :
                                                        $type_cast_values["custom_time_end"] = (int)$time["custom_time_end"];
                                                        break;
                                                }
                                            }
                                            //adds time offset for comparing recurring granular.
                                            if ($type_cast_values["custom_time"]) {
                                                $type_cast_values["start_time_offset"]  = $type_cast_values["custom_time_start"] - $event_start;
                                                $type_cast_values["end_time_offset"]    = $type_cast_values["custom_time_end"] - $event_start;
                                            } else {
                                                $type_cast_values["start_time_offset"]  = 0;
                                                $type_cast_values["end_time_offset"]    = 0;
                                            }

                                            $cgroup_times[$time["audience_value"]]      = $type_cast_values;
                                        }
                                    }
                                }

                                if (isset($_POST["event_audience_course_groups"]) && isset($PROCESSED["course_id"]) && $PROCESSED["course_id"]) {
                                    $associated_audience = explode(",", $_POST["event_audience_course_groups"]);
                                    if (isset($associated_audience) && is_array($associated_audience) && count($associated_audience)) {
                                        foreach($associated_audience as $audience_id) {
                                            if (strpos($audience_id, "cgroup") !== false) {
                                                if ($cgroup_id = clean_input(preg_replace("/[a-z_]/", "", $audience_id), array("trim", "int"))) {
                                                    $query = "    SELECT *
                                                                FROM `course_groups`
                                                                WHERE `cgroup_id` = ".$db->qstr($cgroup_id)."
                                                                AND `course_id` = ".$db->qstr($PROCESSED["course_id"])."
                                                                AND (`active` = '1' OR `course_id` = ".$db->qstr($event_info["course_id"]).")";
                                                    $result    = $db->GetRow($query);
                                                    if ($result) {
                                                        $audience_type  = "group_id";
                                                        $audience_value = $cgroup_id;
                                                        if (isset($cgroup_times) && is_array($cgroup_times) && !empty($cgroup_times)) {
                                                            $custom_time        = $cgroup_times[$cgroup_id]["custom_time"];
                                                            $custom_time_start  = $cgroup_times[$cgroup_id]["custom_time_start"];
                                                            $custom_time_end    = $cgroup_times[$cgroup_id]["custom_time_end"];
                                                        } else {
                                                            $custom_time        = 0;
                                                            $custom_time_start  = 0;
                                                            $custom_time_end  = 0;
                                                        }

                                                        $PROCESSED["associated_cgroup_ids"][] = $audience_value;
                                                        $audience = ($is_draft ? Models_Event_Draft_Event_Audience::fetchRowByDraftEventIdTypeValue($devent_id, $audience_type, $audience_value) : Models_Event_Audience::fetchRowByEventIdTypeValue($EVENT_ID, $audience_type, $audience_value));
                                                        if ($audience) {
                                                            $audience->setUpdatedBy($ENTRADA_USER->getID());
                                                            $audience->setUpdatedDate(time());
                                                            $audience->setCustomTime($custom_time);
                                                            $audience->setCustomTimeStart($custom_time_start);
                                                            $audience->setCustomTimeEnd($custom_time_end);
                                                        } else {
                                                            $insert = array();
                                                            $insert["event_id"]             = $EVENT_ID;
                                                            $insert["audience_type"]        = $audience_type;
                                                            $insert["audience_value"]       = $audience_value;
                                                            $insert["custom_time"]          = $custom_time;
                                                            $insert["custom_time_start"]    = $custom_time_start;
                                                            $insert["custom_time_end"]      = $custom_time_end;
                                                            $insert["updated_date"]         = time();
                                                            $insert["updated_by"]           = $ENTRADA_USER->getID();
                                                            if ($is_draft) {
                                                                $insert["devent_id"]  = $devent_id;
                                                                $insert["eaudience_id"]  = 0;
                                                                $audience = new Models_Event_Draft_Event_Audience($insert);
                                                            } else {
                                                                $audience = new Models_Event_Audience($insert);
                                                            }
                                                        }
                                                        if (isset($cgroup_times_a) && is_array($cgroup_times_a) && !array_key_exists($audience_value, $cgroup_times_a)) {
                                                            $audience_array = $audience->toArray();
                                                            $audience_array = Entrada_Utilities::buildAudienceArray($audience_array);
                                                            $cgroup_times_o[$audience_value] = $audience;
                                                            $cgroup_times_a[$audience_value] = $audience_array;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    ksort($cgroup_times_a);
                                }

                                /**
                                 * Learners
                                 */

                                if (isset($_POST["event_audience_students_custom_times"])) {
                                    $times = unserialize($_POST["event_audience_students_custom_times"]);
                                    //if not an array then it's a JSON object that needs to be typecast and decoded
                                    if (!is_array($times)) {
                                        $times = (array) json_decode($_POST["event_audience_students_custom_times"]);
                                    }
                                    if (is_array($times)) {
                                        foreach ($times as $time) {
                                            //type cast the object to an array
                                            $time = (array)$time;
                                            //type cast array values to int, etc
                                            $type_cast_values = array();
                                            foreach ($time as $key => $values) {
                                                switch($key) {
                                                    case "audience_type":
                                                        $type_cast_values["audience_type"] = $time["audience_type"];
                                                        break;
                                                    case "audience_value":
                                                        $type_cast_values["audience_value"] = (int)$time["audience_value"];
                                                        break;
                                                    case "custom_time":
                                                        $type_cast_values["custom_time"] = (int)$time["custom_time"];
                                                        break;
                                                    case "custom_time_start" :
                                                        $type_cast_values["custom_time_start"] = (int)$time["custom_time_start"];
                                                        break;
                                                    case "custom_time_end" :
                                                        $type_cast_values["custom_time_end"] = (int)$time["custom_time_end"];
                                                        break;
                                                }
                                            }
                                            //adds time offset for comparing recurring granular.
                                            if ($type_cast_values["custom_time"]) {
                                                $type_cast_values["start_time_offset"]  = $type_cast_values["custom_time_start"] - $event_start;
                                                $type_cast_values["end_time_offset"]    = $type_cast_values["custom_time_end"] - $event_start;
                                            } else {
                                                $type_cast_values["start_time_offset"]  = 0;
                                                $type_cast_values["end_time_offset"]    = 0;
                                            }

                                            $proxy_times[$time["audience_value"]]       = $type_cast_values;
                                        }
                                    }
                                }

                                if (isset($_POST["event_audience_students"])) {
                                    $associated_audience = explode(",", $_POST["event_audience_students"]);
                                    if (isset($associated_audience) && is_array($associated_audience) && count($associated_audience)) {
                                        foreach($associated_audience as $audience_id) {
                                            if (strpos($audience_id, "student") !== false) {
                                                if ($proxy_id = clean_input(preg_replace("/[a-z_]/", "", $audience_id), array("trim", "int"))) {
                                                    $query = "    SELECT a.*
                                                                FROM `".AUTH_DATABASE."`.`user_data` AS a
                                                                LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
                                                                ON a.`id` = b.`user_id`
                                                                WHERE a.`id` = ".$db->qstr($proxy_id)."
                                                                AND b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
                                                                AND b.`account_active` = 'true'
                                                                AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
                                                                AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")";
                                                    $result    = $db->GetRow($query);
                                                    if ($result) {
                                                        $audience_type  = "proxy_id";
                                                        $audience_value = $proxy_id;
                                                        if (isset($proxy_times) && is_array($proxy_times) && !empty($proxy_times)) {
                                                            $custom_time        = $proxy_times[$proxy_id]["custom_time"];
                                                            $custom_time_start  = $proxy_times[$proxy_id]["custom_time_start"];
                                                            $custom_time_end    = $proxy_times[$proxy_id]["custom_time_end"];
                                                        } else {
                                                            $custom_time        = 0;
                                                            $custom_time_start  = 0;
                                                            $custom_time_end    = 0;
                                                        }

                                                        $PROCESSED["associated_proxy_ids"][] = $audience_value;
                                                        $audience = ($is_draft ? Models_Event_Draft_Event_Audience::fetchRowByDraftEventIdTypeValue($devent_id, $audience_type, $audience_value) : Models_Event_Audience::fetchRowByEventIdTypeValue($EVENT_ID, $audience_type, $audience_value));
                                                        if ($audience) {
                                                            $audience->setUpdatedBy($ENTRADA_USER->getID());
                                                            $audience->setUpdatedDate(time());
                                                            $audience->setCustomTime($custom_time);
                                                            $audience->setCustomTimeStart($custom_time_start);
                                                            $audience->setCustomTimeEnd($custom_time_end);
                                                        } else {
                                                            $audience_arr = array(
                                                                "event_id"          => $EVENT_ID,
                                                                "audience_type"     => $audience_type,
                                                                "audience_value"    => $audience_value,
                                                                "custom_time"       => $custom_time,
                                                                "custom_time_start" => $custom_time_start,
                                                                "custom_time_end"   => $custom_time_end,
                                                                "updated_date"      => time(),
                                                                "updated_by"        => $ENTRADA_USER->getID()
                                                            );
                                                            $audience = new $model($audience_arr);
                                                        }
                                                        if (isset($proxy_times_a) && is_array($proxy_times_a) && !array_key_exists($audience_value, $proxy_times_a)) {
                                                            $audience_array = $audience->toArray();
                                                            $audience_array = Entrada_Utilities::buildAudienceArray($audience_array);
                                                            $proxy_times_o[$audience_value] = $audience;
                                                            $proxy_times_a[$audience_value] = $audience_array;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    ksort($proxy_times_a);
                                }
                                break;
                            default :
                                add_error("Unknown event audience type provided. Unable to proceed.");
                                break;
                        }

                        /**
                         * Non-required field "release_date" / Viewable Start (validated through validate_calendars function).
                         * Non-required field "release_until" / Viewable Finish (validated through validate_calendars function).
                         */
                        $viewable_date = Entrada_Utilities::validate_calendars("viewable", false, false);
                        if (isset($viewable_date["start"]) && (int) $viewable_date["start"]) {
                            $PROCESSED["release_date"] = (int) $viewable_date["start"];
                        } else {
                            $PROCESSED["release_date"] = 0;
                        }

                        $changed = false;
                        $changed = md5_change_value($EVENT_ID, "event_id", "release_date", $PROCESSED["release_date"], "events");
                        if ($changed) {
                            $stats[] = "Viewable Start";
                        }

                        if (isset($viewable_date["finish"]) && (int) $viewable_date["finish"]) {
                            $PROCESSED["release_until"] = (int) $viewable_date["finish"];
                        } else {
                            $PROCESSED["release_until"] = 0;
                        }

                        $changed = false;
                        $changed = md5_change_value($EVENT_ID, "event_id", "release_until", $PROCESSED["release_until"], "events");
                        if ($changed) {
                            $stats[] = "Viewable Finish";
                        }

                        if (isset($_POST["post_action"])) {
                            switch($_POST["post_action"]) {
                                case "content" :
                                    $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "content";
                                    break;
                                case "new" :
                                    $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "new";
                                    break;
                                case "copy" :
                                    $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "copy";
                                    break;
                                case "draft" :
                                    $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "draft";
                                    break;
                                case "index" :
                                default :
                                    $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "index";
                                    break;
                            }
                        } else {
                            $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "content";
                        }

                        if (isset($_POST["audience_visible"]) && $tmp_input = clean_input($_POST["audience_visible"], "int")) {
                            $PROCESSED["audience_visible"] = "1";
                        } else {
                            $PROCESSED["audience_visible"] = "0";
                        }

                        if (!$ERROR) {
                            $PROCESSED["updated_date"]   = time();
                            $PROCESSED["updated_by"]     = $ENTRADA_USER->getID();
                            $PROCESSED["event_finish"]   = $PROCESSED["event_start"];
                            $PROCESSED["event_duration"] = 0;
                            foreach($PROCESSED["event_types"] as $event_type) {
                                $PROCESSED["event_finish"] += $event_type[1]*60;
                                $PROCESSED["event_duration"] += $event_type[1];
                            }

                            $PROCESSED["eventtype_id"] = $PROCESSED["event_types"][0][0];

                            if ($db->AutoExecute($tables["events"], $PROCESSED, "UPDATE", str_replace("WHERE", "", $where_query))) {
                                $query = "  SELECT `et`.`eventtype_id`, `et`.`duration`, `et_lu`.`eventtype_title`
                                            FROM `" . $tables["event_types"] . "` AS `et`
                                            JOIN `events_lu_eventtypes` AS `et_lu`
                                            ON `et`.`eventtype_id` = `et_lu`.`eventtype_id`
                                            WHERE " . (!$is_draft ? "`et`.`event_id` = " . $db->qstr($EVENT_ID) : "`et`.`devent_id` = " . $devent_id);

                                $current_event_types = $db->GetAll($query);
                                $current_et         = array();
                                $event_type_remove  = array();
                                $event_type_add     = array();

                                foreach ($current_event_types AS $current_event_type) {
                                    $current_et[] = array(
                                        (int)$current_event_type["eventtype_id"],
                                        (int)$current_event_type["duration"],
                                        $current_event_type["eventtype_title"]
                                    );
                                }

                                //need to check which arrays are set before comparing.
                                if (isset($current_et) && is_array($current_et) && !empty($current_et)) {
                                    //if no event types then remove all current
                                    if (isset($PROCESSED["event_types"]) && is_array($PROCESSED["event_types"]) && !empty($PROCESSED["event_types"])) {
                                        $event_types_diff = compare_mlti_array($current_et, $PROCESSED["event_types"]);
                                        if (isset($event_types_diff) && is_array($event_types_diff)) {
                                            if (!empty($event_types_diff["add"]) && is_array($event_types_diff["add"])) {
                                                foreach ($event_types_diff["add"] as $event_types_diff_add) {
                                                    $event_type_add[] = unserialize($event_types_diff_add);
                                                }
                                            }

                                            if (!empty($event_types_diff["remove"]) && is_array($event_types_diff["remove"])) {
                                                foreach ($event_types_diff["remove"] as $event_types_diff_remove) {
                                                    $event_type_remove[] = unserialize($event_types_diff_remove);
                                                }
                                            }
                                        }
                                    } else {
                                        //remove all as none are set.... not sure this is allowed SP 9-23-2014
                                    }
                                } else {
                                    //add all as none are set.
                                    if (isset($PROCESSED["event_types"]) && is_array($PROCESSED["event_types"]) && !empty($PROCESSED["event_types"])) {
                                        foreach($PROCESSED["event_types"] as $type) {
                                            $event_type_add[] = $type;
                                        }
                                    }
                                }

                                if (isset($event_type_remove) && is_array($event_type_remove)) {
                                    $event_type_remove_count = 0;
                                    foreach ($event_type_remove AS $event_type) {
                                        $query = "  DELETE FROM `" . $tables["event_types"] . "` " . $where_query . "
                                                    AND `eventtype_id` = " . $db->qstr($event_type[0]) . "
                                                    AND `duration` = " . $db->qstr($event_type[1]) . "
                                                    ORDER BY `eeventtype_id` DESC
                                                    LIMIT 1";
                                        if (!$db->Execute($query)) {
                                            add_error("There was an error while trying to remove the selected <strong>" . $translate->_("Event Types") . "</strong> for one of the selected recurring events.<br /><br />The system administrator was informed of this error; please try again later.");

                                            application_log("error", "Unable to delete any eventtype records while editing an event. Database said: ".$db->ErrorMsg());
                                        } else {
                                            $event_type_remove_count++;
                                        }
                                    }
                                }

                                if (isset($event_type_add) && is_array($event_type_add)) {
                                    $event_type_add_count = 0;
                                    foreach ($event_type_add AS $event_type) {
                                        $eventtype_data = array("event_id" => $EVENT_ID, "eventtype_id" => $event_type[0], "duration" => $event_type[1]);
                                        if ($is_draft) {
                                            $eventtype_data["devent_id"] = $devent_id;
                                        }

                                        if (!$db->AutoExecute($tables["event_types"], $eventtype_data, "INSERT")) {
                                            add_error("There was an error while trying to insert the selected <strong>" . $translate->_("Event Type") . "</strong> for this event.<br /><br />The system administrator was informed of this error; please try again later.");

                                            application_log("error", "Unable to insert a new event_eventtype record while adding a new event. Database said: ".$db->ErrorMsg());
                                        } else {
                                            $event_type_add_count++;
                                        }
                                    }
                                }

                                if (isset($event_type_add_count) || isset($event_type_remove_count)) {
                                    //log change
                                    if ($event_type_add_count > 0) {
                                        $stats[] = "Event Type Added";
                                    }
                                    if ($event_type_remove_count > 0) {
                                        $stats[] = "Event Type Removed";
                                    }
                                }

                                /**
                                 * If there are faculty associated with this event, add them
                                 * to the event_contacts table.
                                 */

                                //creates an array of existing contacts
                                //used to compare changes to the faculty
                                $existing_contacts_query = "    SELECT `proxy_id`
                                                                FROM `".$tables["contacts"]."`
                                                                WHERE `event_id` = ".$db->qstr($EVENT_ID);
                                $existing_contacts = $db->GetAll($existing_contacts_query);

                                if (isset($existing_contacts) && is_array($existing_contacts)) {
                                    foreach ($existing_contacts as $existing_contact) {
                                        $existing_contact_array[] = $existing_contact["proxy_id"];
                                    }
                                }

                                $query = "DELETE FROM `".$tables["contacts"]."` ".$where_query;
                                if ($db->Execute($query)) {
                                    if ((is_array($PROCESSED["associated_faculty"])) && (count($PROCESSED["associated_faculty"]))) {
                                        foreach($PROCESSED["associated_faculty"] as $contact_order => $proxy_id) {
                                            $contact_data = array("event_id" => $EVENT_ID, "proxy_id" => $proxy_id,"contact_role" => $PROCESSED["contact_role"][$contact_order], "contact_order" => (int) $contact_order, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID());
                                            if ($is_draft) {
                                                $contact_data["devent_id"] = $devent_id;
                                            }
                                            if (!$db->AutoExecute($tables["contacts"], $contact_data, "INSERT")) {
                                                add_error("There was an error while trying to attach an <strong>Associated Faculty</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.");

                                                application_log("error", "Unable to insert a new event_contact record while adding a new event. Database said: ".$db->ErrorMsg());
                                            }
                                        }
                                    }
                                }

                                // Compares the previous faculty list with the new one
                                $changed = false;
                                // Only try to compare the arrays if faculty are already associated
                                if (is_array($existing_contact_array) && is_array($PROCESSED["associated_faculty"])) {
                                    $changed = compare_array_values($PROCESSED["associated_faculty"], $existing_contact_array);
                                }
                                // If no faculty are set, but a list is set and not empty then set changed to true
                                if (isset($existing_contact_array) && is_array($PROCESSED["associated_faculty"]) && !empty($PROCESSED["associated_faculty"])) {
                                    $changed = true;
                                }
                                if ($changed) {
                                    $stats[] = "Associated Faculty";
                                }

                                if ($is_draft) {
                                    $current_audiences = $model->fetchAllByDraftEventID($devent_id);
                                } else {
                                    $current_audiences = $model->fetchAllByEventID($EVENT_ID);
                                }

                                $current_audience_course    = array();
                                $current_audience_cohort    = array();
                                $current_audience_cgroup    = array();
                                $current_audience_proxy_id  = array();
                                if (isset($current_audiences) && is_array($current_audiences)) {
                                    foreach($current_audiences as $audience) {
                                        if (isset($audience) && is_object($audience)) {
                                            $audience_type  = $audience->getAudienceType();
                                            $audience_value = $audience->getAudienceValue();
                                            $audience_array = $audience->toArray();
                                            $audience_array = Entrada_Utilities::buildAudienceArray($audience_array);

                                            switch ($audience_type) {
                                                case "course_id" :
                                                    $current_audience_course[] = (int)$audience_value;
                                                    break;
                                                case "cohort" :
                                                    $current_audience_cohort[$audience_value] = $audience_array;
                                                    break;
                                                case "group_id" :
                                                    $current_audience_cgroup[$audience_value] = $audience_array;
                                                    break;
                                                case "proxy_id" :
                                                    $current_audience_proxy_id[$audience_value] = $audience_array;
                                                    break;
                                            }
                                        }
                                    }
                                }

                                ksort($current_audience_cohort);
                                ksort($current_audience_cgroup);
                                ksort($current_audience_proxy_id);

                                // Generates add and remove for event audience
                                if (isset($current_audience_course) && is_array($current_audience_course) && isset($PROCESSED["associated_course_ids"]) && is_array($PROCESSED["associated_course_ids"])) {
                                    $diff_course_remove = array_diff($current_audience_course, $PROCESSED["associated_course_ids"]);
                                    $diff_course_add    = array_diff($PROCESSED["associated_course_ids"], $current_audience_course);
                                }

                                $cohort_update = compare_mlti_array($current_audience_cohort, $cohort_times_a, "audience_value");

                                if (isset($cohort_update) && is_array($cohort_update)) {
                                    $cohort_changes = Entrada_Utilities::buildInsertUpdateDelete($cohort_update);
                                }

                                $cgroup_update = compare_mlti_array($current_audience_cgroup, $cgroup_times_a, "audience_value");

                                if (isset($cgroup_update) && is_array($cgroup_update)) {
                                    $cgroup_changes = Entrada_Utilities::buildInsertUpdateDelete($cgroup_update);
                                }

                                $proxy_update = compare_mlti_array($current_audience_proxy_id, $proxy_times_a, "audience_value");

                                if (isset($proxy_update) && is_array($proxy_update)) {
                                    $proxy_changes = Entrada_Utilities::buildInsertUpdateDelete($proxy_update);
                                }

                                $cohort_insert     = $cohort_changes["insert"];
                                $cohort_update     = $cohort_changes["update"];
                                $cohort_delete     = $cohort_changes["delete"];
                                $cgroup_insert     = $cgroup_changes["insert"];
                                $cgroup_update     = $cgroup_changes["update"];
                                $cgroup_delete     = $cgroup_changes["delete"];
                                $proxy_insert      = $proxy_changes["insert"];
                                $proxy_update      = $proxy_changes["update"];
                                $proxy_delete      = $proxy_changes["delete"];

                                if ($diff_course_add || $diff_course_remove || $cohort_insert || $cohort_update || $cohort_delete || $cgroup_insert || $cgroup_update || $cgroup_delete || $proxy_insert || $proxy_update || $proxy_delete) {
                                    $edited_audience = false;
                                    switch ($PROCESSED["event_audience_type"]) {
                                        case "course" :
                                            //clear non course enrollment styles if found.
                                            $query = "  SELECT `eaudience_id` FROM `" . $tables["audience"] . "` " . $where_query . "
                                                        AND `audience_type` NOT IN ('course_id')";
                                            $remove_non_course_enrollment = $db->GetAll($query);

                                            if (isset($remove_non_course_enrollment) && is_array($remove_non_course_enrollment) && !empty($remove_non_course_enrollment)) {
                                                foreach ($remove_non_course_enrollment AS $remove) {
                                                    $query = "  DELETE FROM `" . $tables["audience"] . "` " . $where_query . "
                                                                AND `eaudience_id` = " . $db->qstr($remove["eaudience_id"]);
                                                    if (!$db->Execute($query)) {
                                                        add_error("There was an error while trying to delete the selected <strong>Audience</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.".$db->ErrorMsg());
                                                        application_log("error", "Unable to delete event_audience. Database said: ".$db->ErrorMsg());
                                                    }
                                                }
                                            }
                                            /**
                                             * Course ID (there is only one at this time, but this processes more than 1).
                                             */
                                            if (isset($diff_course_remove) && is_array($diff_course_remove) && !empty($diff_course_remove)) {
                                                foreach ($diff_course_remove as $remove) {
                                                    $query = "  DELETE FROM `" . $tables["audience"] . "` " . $where_query . "
                                                                AND `audience_type` = 'course_id'
                                                                AND `audience_value` = " . $db->qstr($remove);
                                                    if (!$db->Execute($query)) {
                                                        add_error("There was an error while trying to delete the selected <strong>Course ID Audience</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.".$db->ErrorMsg());
                                                        application_log("error", "Unable to delete event_audience. Database said: ".$db->ErrorMsg());
                                                    } else {
                                                        if (!in_array("Course", $stats)) {
                                                            $stats[] = "Course";
                                                        }
                                                    }
                                                }
                                            }

                                            if (isset($diff_course_add) && is_array($diff_course_add) && !empty($diff_course_add)) {
                                                foreach ($diff_course_add as $add) {
                                                    $audience_data = array(
                                                        "event_id"          => $EVENT_ID,
                                                        "audience_type"     => "course_id",
                                                        "audience_value"    => (int) $add,
                                                        "updated_date"      => time(),
                                                        "updated_by"        => $ENTRADA_USER->getID());
                                                    if ($is_draft) {
                                                        $audience_data["devent_id"] = $devent_id;
                                                    }
                                                    if (!$db->AutoExecute($tables["audience"], $audience_data, "INSERT")) {
                                                        add_error("There was an error while trying to insert the selected <strong>Course ID Audience</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.".$db->ErrorMsg());
                                                        application_log("error", "Unable to insert event_audience. Database said: ".$db->ErrorMsg());
                                                    } else {
                                                        if (!in_array("Course", $stats)) {
                                                            $stats[] = "Course";
                                                        }
                                                    }
                                                }
                                            }

                                            break;
                                        case "custom" :
                                            // Remove course_id enrollment if its there.
                                            if (isset($current_audience_course) && is_array($current_audience_course) && !empty($current_audience_course)) {
                                                foreach ($current_audience_course as $remove) {
                                                    $query = "  DELETE FROM `" . $tables["audience"] . "` " . $where_query . "
                                                                AND `audience_type` = 'course_id'
                                                                AND `audience_value` = " . $db->qstr($remove);
                                                    if (!$db->Execute($query)) {
                                                        add_error("There was an error while trying to delete the selected <strong>Course ID Audience</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.".$db->ErrorMsg());
                                                        application_log("error", "Unable to delete event_audience. Database said: ".$db->ErrorMsg());
                                                    } else {
                                                        $edited_audience = true;
                                                    }
                                                }
                                            }

                                            /**
                                             * Cohort
                                             */
                                            if (isset($cohort_insert) && is_array($cohort_insert)) {
                                                foreach ($cohort_insert as $insert) {
                                                    $insert["updated_by"]   = $ENTRADA_USER->getID();
                                                    $insert["updated_date"] = time();
                                                    $insert["event_id"]     = $EVENT_ID;
                                                    if ($is_draft) {
                                                        $insert["devent_id"]  = $devent_id;
                                                        $insert["eaudience_id"]  = 0;
                                                        $audience = new Models_Event_Draft_Event_Audience($insert);
                                                    } else {
                                                        $audience = new Models_Event_Audience($insert);
                                                    }

                                                    if (!$audience->insert()) {
                                                        add_error("There was an error while trying to attach the selected <strong>Cohort</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.");
                                                        application_log("error", "Unable to insert a new event_audience, cohort record while adding a new event. Database said: ".$db->ErrorMsg());
                                                    } else {
                                                        $edited_audience = true;
                                                    }
                                                }
                                            }

                                            if (isset($cohort_delete) && is_array($cohort_delete)) {
                                                foreach ($cohort_delete as $delete) {
                                                    $audience_type  = $delete["audience_type"];
                                                    $audience_value = $delete["audience_value"];
                                                    $audience = ($is_draft ? Models_Event_Draft_Event_Audience::fetchRowByDraftEventIdTypeValue($devent_id, $audience_type, $audience_value) : Models_Event_Audience::fetchRowByEventIdTypeValue($EVENT_ID, $audience_type, $audience_value));
                                                    if ($audience) {
                                                        if (!$audience->delete()) {
                                                            add_error("There was an error while trying to delete the selected <strong>Cohort</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.");
                                                            application_log("error", "Unable to delete an event_audience, cohort record while adding a new event. Database said: ".$db->ErrorMsg());
                                                        } else {
                                                            $edited_audience = true;
                                                        }
                                                    }
                                                }
                                            }

                                            if (isset($cohort_update) && is_array($cohort_update)) {
                                                foreach ($cohort_update as $update) {
                                                    $audience_type      = $update["audience_type"];
                                                    $audience_value     = $update["audience_value"];
                                                    // todo update this with event start and end times if not set
                                                    $custom_time        = ($update["custom_time"] ? $update["custom_time"] : 0);
                                                    $custom_time_start  = ($update["custom_time_start"] ? $update["custom_time_start"] : 0);
                                                    $custom_time_end    = ($update["custom_time_end"] ? $update["custom_time_end"] : 0);
                                                    $audience = ($is_draft ? Models_Event_Draft_Event_Audience::fetchRowByDraftEventIdTypeValue($devent_id, $audience_type, $audience_value) : Models_Event_Audience::fetchRowByEventIdTypeValue($EVENT_ID, $audience_type, $audience_value));
                                                    $audience->setUpdatedBy($ENTRADA_USER->getID());
                                                    $audience->setUpdatedDate(time());
                                                    $audience->setCustomTime($custom_time);
                                                    $audience->setCustomTimeStart($custom_time_start);
                                                    $audience->setCustomTimeEnd($custom_time_end);
                                                    if ($audience) {
                                                        if (!$audience->update()) {
                                                            add_error("There was an error while trying to update the selected <strong>Cohort</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.");
                                                            application_log("error", "Unable to update an event_audience, cohort record while adding a new event. Database said: ".$db->ErrorMsg());
                                                        } else {
                                                            $edited_audience = true;
                                                        }
                                                    }
                                                }
                                            }

                                            /**
                                             * Course Groups
                                             */
                                            if (isset($cgroup_insert) && is_array($cgroup_insert)) {
                                                foreach ($cgroup_insert as $insert) {
                                                    $insert["event_id"]     = $EVENT_ID;
                                                    $insert["updated_by"]   = $ENTRADA_USER->getID();
                                                    $insert["updated_date"] = time();
                                                    if ($is_draft) {
                                                        $insert["devent_id"]  = $devent_id;
                                                        $insert["eaudience_id"]  = 0;
                                                        $audience = new Models_Event_Draft_Event_Audience($insert);
                                                    } else {
                                                        $audience = new Models_Event_Audience($insert);
                                                    }
                                                    if (!$audience->insert()) {
                                                        add_error("There was an error while trying to attach the selected <strong>Course Group</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.");
                                                        application_log("error", "Unable to insert a new event_audience, course group record while adding a new event. Database said: ".$db->ErrorMsg());
                                                    } else {
                                                        $edited_audience = true;
                                                    }
                                                }
                                            }

                                            if (isset($cgroup_delete) && is_array($cgroup_delete)) {
                                                foreach ($cgroup_delete as $delete) {
                                                    $audience_type  = $delete["audience_type"];
                                                    $audience_value = $delete["audience_value"];
                                                    $audience = ($is_draft ? Models_Event_Draft_Event_Audience::fetchRowByDraftEventIdTypeValue($devent_id, $audience_type, $audience_value) : Models_Event_Audience::fetchRowByEventIdTypeValue($EVENT_ID, $audience_type, $audience_value));
                                                    if ($audience) {
                                                        if (!$audience->delete()) {
                                                            add_error("There was an error while trying to delete the selected <strong>Course Group</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.");
                                                            application_log("error", "Unable to delete an event_audience, Course Group record while adding a new event. Database said: ".$db->ErrorMsg());
                                                        } else {
                                                            $edited_audience = true;
                                                        }
                                                    }
                                                }
                                            }

                                            if (isset($cgroup_update) && is_array($cgroup_update)) {
                                                foreach ($cgroup_update as $update) {
                                                    $audience_type      = $update["audience_type"];
                                                    $audience_value     = $update["audience_value"];
                                                    $custom_time        = $update["custom_time"];
                                                    $custom_time_start  = $update["custom_time_start"];
                                                    $custom_time_end    = $update["custom_time_end"];
                                                    $audience = ($is_draft ? Models_Event_Draft_Event_Audience::fetchRowByDraftEventIdTypeValue($devent_id, $audience_type, $audience_value) : Models_Event_Audience::fetchRowByEventIdTypeValue($EVENT_ID, $audience_type, $audience_value));
                                                    $audience->setUpdatedBy($ENTRADA_USER->getID());
                                                    $audience->setUpdatedDate(time());
                                                    $audience->setCustomTime($custom_time);
                                                    $audience->setCustomTimeStart($custom_time_start);
                                                    $audience->setCustomTimeEnd($custom_time_end);
                                                    if ($audience) {
                                                        if (!$audience->update()) {
                                                            add_error("There was an error while trying to update the selected <strong>Course Group</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.");
                                                            application_log("error", "Unable to update an event_audience, Course Group record while adding a new event. Database said: ".$db->ErrorMsg());
                                                        } else {
                                                            $edited_audience = true;
                                                        }
                                                    }
                                                }
                                            }

                                            /**
                                             * Proxy ID
                                             */
                                            if (isset($proxy_insert) && is_array($proxy_insert)) {
                                                foreach ($proxy_insert as $insert) {
                                                    $insert["event_id"]     = $EVENT_ID;
                                                    $insert["updated_by"]   = $ENTRADA_USER->getID();
                                                    $insert["updated_date"] = time();
                                                    if ($is_draft) {
                                                        $insert["devent_id"]  = $devent_id;
                                                        $insert["eaudience_id"]  = 0;
                                                        $audience = new Models_Event_Draft_Event_Audience($insert);
                                                    } else {
                                                        $audience = new Models_Event_Audience($insert);
                                                    }
                                                    if (!$audience->insert()) {
                                                        add_error("There was an error while trying to attach the selected <strong>Proxy ID</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.");
                                                        application_log("error", "Unable to insert a new event_audience, Proxy ID record while adding a new event. Database said: ".$db->ErrorMsg());
                                                    } else {
                                                        $edited_audience = true;
                                                    }
                                                }
                                            }

                                            if (isset($proxy_delete) && is_array($proxy_delete)) {
                                                foreach ($proxy_delete as $delete) {
                                                    $audience_type  = $delete["audience_type"];
                                                    $audience_value = $delete["audience_value"];
                                                    $audience = ($is_draft ? Models_Event_Draft_Event_Audience::fetchRowByDraftEventIdTypeValue($devent_id, $audience_type, $audience_value) : Models_Event_Audience::fetchRowByEventIdTypeValue($EVENT_ID, $audience_type, $audience_value));
                                                    if ($audience) {
                                                        if (!$audience->delete()) {
                                                            add_error("There was an error while trying to delete the selected <strong>Proxy ID</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.");
                                                            application_log("error", "Unable to delete an event_audience, Proxy ID record while adding a new event. Database said: ".$db->ErrorMsg());
                                                        } else {
                                                            $edited_audience = true;
                                                        }
                                                    }
                                                }
                                            }

                                            if (isset($proxy_update) && is_array($proxy_update)) {
                                                foreach ($proxy_update as $update) {
                                                    $audience_type      = $update["audience_type"];
                                                    $audience_value     = $update["audience_value"];
                                                    $custom_time        = $update["custom_time"];
                                                    $custom_time_start  = $update["custom_time_start"];
                                                    $custom_time_end    = $update["custom_time_end"];
                                                    $audience = ($is_draft ? Models_Event_Draft_Event_Audience::fetchRowByDraftEventIdTypeValue($devent_id, $audience_type, $audience_value) : Models_Event_Audience::fetchRowByEventIdTypeValue($EVENT_ID, $audience_type, $audience_value));
                                                    $audience->setUpdatedBy($ENTRADA_USER->getID());
                                                    $audience->setUpdatedDate(time());
                                                    $audience->setCustomTime($custom_time);
                                                    $audience->setCustomTimeStart($custom_time_start);
                                                    $audience->setCustomTimeEnd($custom_time_end);
                                                    if ($audience) {
                                                        if (!$audience->update()) {
                                                            add_error("There was an error while trying to update the selected <strong>Proxy ID</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.");
                                                            application_log("error", "Unable to update an event_audience, proxy id record while adding a new event. Database said: ".$db->ErrorMsg());
                                                        } else {
                                                            $edited_audience = true;
                                                        }
                                                    }
                                                }
                                            }

                                            break;
                                        default :
                                            add_error("There was no audience information provided, so this event is without an audience.");
                                            break;
                                    }

                                    if ($edited_audience == true) {
                                        $insert_value = "Event Audience";
                                        if (!in_array($insert_value, $stats)) {
                                            $stats[] = $insert_value;
                                        }
                                    }

                                    /**
                                     * Remove attendance records for anyone who is no longer a valid audience member of the course.
                                     */
                                    $audience = events_fetch_event_audience_attendance($EVENT_ID);
                                    if ($audience) {
                                        $valid_audience = array();
                                        foreach ($audience as $learner){
                                            $valid_audience[] = $learner["id"];
                                        }

                                        if (!empty($valid_audience)) {
                                            $query = "DELETE FROM `event_attendance` WHERE `event_id` = ".$db->qstr($EVENT_ID)." AND `proxy_id` NOT IN (".implode(",", $valid_audience).")";
                                            $db->Execute($query);
                                        }

                                    } else {
                                        $query = "DELETE FROM `event_attendance` WHERE `event_id` = ".$db->qstr($EVENT_ID);
                                        $db->Execute($query);
                                    }
                                }

                                switch($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
                                    case "content" :
                                        $url    = ENTRADA_URL."/admin/events?section=content&id=".$EVENT_ID;
                                        $msg    = "You will now be redirected to the event content page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
                                        break;
                                    case "new" :
                                        $url    = ENTRADA_URL."/admin/events?section=add";
                                        $msg    = "You will now be redirected to add a new event; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
                                        break;
                                    case "copy" :
                                        $url    = ENTRADA_URL."/admin/events?section=add";
                                        $msg    = "You will now be redirected to add a copy of the last event; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
                                        $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["copy"] = $PROCESSED;
                                        break;
                                    case "draft" :
                                        $url    = ENTRADA_URL."/admin/events/drafts?section=edit&draft_id=".$event_info["draft_id"];
                                        $msg    = "You will now be redirected to the draft managment page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
                                        break;
                                    case "index" :
                                    default :
                                        $url    = ENTRADA_URL."/admin/events";
                                        $msg    = "You will now be redirected to the event index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
                                        break;
                                }

                                if ($recurring_events && isset($_POST["recurring_event_ids"]) && @count($_POST["recurring_event_ids"]) && isset($_POST["update_recurring_fields"]) && @count($_POST["update_recurring_fields"])) {
                                    $query = "    SELECT a.*, b.`organisation_id`
                                            FROM `events` AS a
                                            LEFT JOIN `courses` AS b
                                            ON b.`course_id` = a.`course_id`
                                            WHERE a.`event_id` = ".$db->qstr($EVENT_ID);
                                    $event_info    = $db->GetRow($query);

                                    $updating_recurring_events = array();
                                    $query = "SELECT * FROM `events`
                                            WHERE `recurring_id` = ".$db->qstr($event_info["recurring_id"])."
                                            AND `event_id` != ".$db->qstr($EVENT_ID)."
                                            ORDER BY `event_start` ASC";
                                    $temp_recurring_events = $db->GetAll($query);
                                    if ($temp_recurring_events) {
                                        foreach ($temp_recurring_events as $temp_event) {
                                            if (in_array($temp_event["event_id"], $_POST["recurring_event_ids"])) {
                                                $updating_recurring_events[] = $temp_event;
                                            }
                                        }
                                    }
                                    $PROCESSED_RECURRING_EVENT = array();
                                    unset($PROCESSED_RECURRING_EVENT["recurring_id"]);
                                    if ($updating_recurring_events) {
                                        if (in_array("course", $_POST["update_recurring_fields"])) {
                                            $PROCESSED_RECURRING_EVENT["course_id"] = $PROCESSED["course_id"];
                                        }
                                        if (in_array("event_title", $_POST["update_recurring_fields"])) {
                                            $PROCESSED_RECURRING_EVENT["event_title"] = $PROCESSED["event_title"];
                                        }
                                        if (in_array("event_location", $_POST["update_recurring_fields"])) {
                                            $PROCESSED_RECURRING_EVENT["event_location"] = $PROCESSED["event_location"];
                                        }
                                        
                                        if (in_array("attendance_required", $_POST["update_recurring_fields"])) {
                                            $PROCESSED_RECURRING_EVENT["attendance_required"] = $PROCESSED["attendance_required"];
                                        }

                                        if (in_array("audience_visible", $_POST["update_recurring_fields"])) {
                                            $PROCESSED_RECURRING_EVENT["audience_visible"] = $PROCESSED["audience_visible"];
                                        }

                                        if (in_array("time_release", $_POST["update_recurring_fields"])) {
                                            $PROCESSED_RECURRING_EVENT["release_date"] = $PROCESSED["release_date"];
                                            $PROCESSED_RECURRING_EVENT["release_until"] = $PROCESSED["release_until"];
                                        }

                                        $current_event_start = $PROCESSED["event_start"];

                                        foreach ($updating_recurring_events as $order => $recurring_event) {
                                            $recurring_stats = array();
                                            $recurring_where_query  = "WHERE `event_id` = ".$db->qstr($recurring_event["event_id"]);
                                            $recurring_event_start = $recurring_event["event_start"];
                                            //event type
                                            if (in_array("event_types", $_POST["update_recurring_fields"])) {
                                                $query = "  SELECT `et`.`eventtype_id`, `et`.`duration`, `et_lu`.`eventtype_title`
                                                            FROM `" . $tables["event_types"] . "` AS `et`
                                                            JOIN `events_lu_eventtypes` AS `et_lu`
                                                            ON `et`.`eventtype_id` = `et_lu`.`eventtype_id`
                                                            WHERE `et`.`event_id` = " . $db->qstr($recurring_event["event_id"]);

                                                $current_event_types = $db->GetAll($query);

                                                $current_et         = array();
                                                $event_type_remove  = array();
                                                $event_type_add     = array();

                                                foreach ($current_event_types AS $current_event_type) {
                                                    $current_et[] = array((int)$current_event_type["eventtype_id"], (int)$current_event_type["duration"], $current_event_type["eventtype_title"]);
                                                }

                                                //need to check which arrays are set before comparing.
                                                if (isset($current_et) && is_array($current_et) && !empty($current_et)) {
                                                    //if no event types then remove all current
                                                    if (isset($PROCESSED["event_types"]) && is_array($PROCESSED["event_types"]) && !empty($PROCESSED["event_types"])) {
                                                        $event_types_diff = compare_mlti_array($current_et, $PROCESSED["event_types"]);
                                                        if (isset($event_types_diff) && is_array($event_types_diff)) {
                                                            if (!empty($event_types_diff["add"]) && is_array($event_types_diff["add"])) {
                                                                foreach ($event_types_diff["add"] as $event_types_diff_add) {
                                                                    $event_type_add[] = unserialize($event_types_diff_add);
                                                                }
                                                            }

                                                            if (!empty($event_types_diff["remove"]) && is_array($event_types_diff["remove"])) {
                                                                foreach ($event_types_diff["remove"] as $event_types_diff_remove) {
                                                                    $event_type_remove[] = unserialize($event_types_diff_remove);
                                                                }
                                                            }
                                                        }
                                                    } else {
                                                        //remove all as none are set.... not sure this is allowed SP 9-23-2014
                                                    }
                                                } else {
                                                    //add all as none are set.
                                                    if (isset($PROCESSED["event_types"]) && is_array($PROCESSED["event_types"]) && !empty($PROCESSED["event_types"])) {
                                                        $event_duration = 0;
                                                        foreach($PROCESSED["event_types"] as $type) {
                                                            $event_type_add[] = $type;
                                                            $event_duration = $event_duration + $type[1];
                                                        }
                                                    }
                                                }

                                                if (isset($event_type_remove) && is_array($event_type_remove)) {
                                                    $event_type_remove_count = 0;
                                                    foreach ($event_type_remove AS $event_type) {
                                                        $query = "  DELETE FROM `" . $tables["event_types"] . "` " . $recurring_where_query . "
                                                                    AND `eventtype_id` = " . $db->qstr($event_type[0]) . "
                                                                    AND `duration` = " . $db->qstr($event_type[1]) . "
                                                                    ORDER BY `eeventtype_id` DESC
                                                                    LIMIT 1";
                                                        if (!$db->Execute($query)) {
                                                            add_error("There was an error while trying to remove the selected <strong>" . $translate->_("Event Types") . "</strong> for one of the selected recurring events.<br /><br />The system administrator was informed of this error; please try again later.");
                                                            application_log("error", "Unable to delete any eventtype records while editing an event. Database said: ".$db->ErrorMsg());
                                                        } else {
                                                            $event_type_remove_count++;
                                                        }
                                                    }
                                                }

                                                if (isset($event_type_add) && is_array($event_type_add)) {
                                                    $event_type_add_count = 0;
                                                    foreach ($event_type_add AS $event_type) {
                                                        $eventtype_data = array("event_id" => $recurring_event["event_id"], "eventtype_id" => $event_type[0], "duration" => $event_type[1]);
                                                        if ($is_draft) {
                                                            $eventtype_data["devent_id"] = $devent_id;
                                                        }
                                                        if (!$db->AutoExecute($tables["event_types"], $eventtype_data, "INSERT")) {
                                                            add_error("There was an error while trying to save the selected <strong>" . $translate->_("Event Type") . "</strong> for this event.<br /><br />The system administrator was informed of this error; please try again later.");
                                                            application_log("error", "Unable to insert a new event_eventtype record while adding a new event. Database said: ".$db->ErrorMsg());
                                                        } else {
                                                            $event_type_add_count++;
                                                        }
                                                    }
                                                    $event_duration = $event_duration + $event_type[1];
                                                }


                                                if (isset($event_type_add_count) || isset($event_type_remove_count)) {
                                                    // Log change
                                                    if ($event_type_add_count > 0) {
                                                        $insert_value = "Event Type Added";
                                                        if (!in_array($insert_value, $recurring_stats)) {
                                                            $recurring_stats[] = $insert_value;
                                                        }
                                                    }
                                                    if ($event_type_remove_count > 0) {
                                                        $insert_value = "Event Type Removed";
                                                        if (!in_array($insert_value, $recurring_stats)) {
                                                            $recurring_stats[] = $insert_value;
                                                        }
                                                    }
                                                    
                                                    //now get the total time for the event duration

                                                    if (!isset($event_duration)) {
                                                        if (isset($PROCESSED["event_types"]) && is_array($PROCESSED["event_types"]) && !empty($PROCESSED["event_types"])) {
                                                            $event_duration = 0;
                                                            foreach($PROCESSED["event_types"] as $type) {
                                                                $event_type_add[] = $type;
                                                                $event_duration = $event_duration + $type[1];
                                                            }
                                                        }
                                                    }
                                                    
                                                    //update event with new duration of $event_duration
                                                    $PROCESSED_RECURRING_EVENT["event_duration"] = $event_duration;
                                                    //updates event finish time based on the event duration time.
                                                    $PROCESSED_RECURRING_EVENT["event_finish"] = $recurring_event["event_start"] + $event_duration * 60;

                                                }
                                            }

                                            // Faculty
                                            if (in_array("associated_faculty", $_POST["update_recurring_fields"])) {
                                                /**
                                                 * If there are faculty associated with this event, add them
                                                 * to the event_contacts table.
                                                 */

                                                //creates an array of existing contacts
                                                //used to compare changes to the faculty
                                                $existing_contact_array = array();
                                                $existing_contacts_query = "    SELECT `proxy_id`
                                                                                FROM `".$tables["contacts"]."`
                                                                                WHERE `event_id` = ".$db->qstr($recurring_event["event_id"]);
                                                $existing_contacts = $db->GetAll($existing_contacts_query);
                                                foreach ($existing_contacts as $existing_contact) {
                                                    $existing_contact_array[] = $existing_contact["proxy_id"];
                                                }

                                                $query = "DELETE FROM `".$tables["contacts"]."` ".$recurring_where_query;
                                                if ($db->Execute($query)) {
                                                    $contacts_query = "SELECT * FROM `".$tables["contacts"]."` WHERE `event_id` = ".$db->qstr($EVENT_ID);
                                                    $contacts_results = $db->GetAll($contacts_query);
                                                    foreach ($contacts_results as $contact_result) {
                                                        unset($contact_result["econtact_id"]);
                                                        $contact_result["event_id"] = $recurring_event["event_id"];
                                                        if (!$db->AutoExecute($tables["contacts"], $contact_result, "INSERT")) {
                                                            add_error("There was an error while trying to attach an <strong>Associated Faculty</strong> to one of the selected recurring events.<br /><br />The system administrator was informed of this error; please try again later.");
                                                            application_log("error", "Unable to insert a new event_contact record while updating a recurring event. Database said: ".$db->ErrorMsg());
                                                        }
                                                    }
                                                }

                                                //compares the previous faculty list with the new one
                                                $changed = false;
                                                //only try to compare the arrays if faculty are already associated
                                                if (is_array($existing_contact_array) && is_array($PROCESSED["associated_faculty"])) {
                                                    $changed = compare_array_values($PROCESSED["associated_faculty"], $existing_contact_array);
                                                }
                                                // if no faculty are set, but a list is set and not empty then set changed to true
                                                if (!is_array($existing_contact_array) && is_array($PROCESSED["associated_faculty"]) && !empty($PROCESSED["associated_faculty"])) {
                                                    $changed = true;
                                                }
                                                if ($changed) {
                                                    $insert_value = "Associated Faculty";
                                                    if (!in_array($insert_value, $recurring_stats)) {
                                                        $recurring_stats[] = $insert_value;
                                                    }
                                                }
                                            }

                                            if (in_array("associated_learners", $_POST["update_recurring_fields"])) {
                                                $cohort_times_recur_o   = array();
                                                $cohort_times_recur_a   = array();
                                                $proxy_times_recur_o    = array();
                                                $proxy_times_recur_a    = array();
                                                $cgroup_times_recur_o   = array();
                                                $cgroup_times_recur_a   = array();

                                                $recurring_event_id     = $recurring_event["event_id"];
                                                $recurring_event_start  = $recurring_event["event_start"];

                                                $current_audiences      = Models_Event_Audience::fetchAllByEventID($recurring_event_id);

                                                $current_audience_course    = array();
                                                $current_audience_cohort    = array();
                                                $current_audience_cgroup    = array();
                                                $current_audience_proxy_id  = array();
                                                if (isset($current_audiences) && is_array($current_audiences)) {
                                                    foreach($current_audiences as $audience) {
                                                        if (isset($audience) && is_object($audience)) {
                                                            $audience_type  = $audience->getAudienceType();
                                                            $audience_value = $audience->getAudienceValue();
                                                            $audience_array = $audience->toArray();
                                                            $audience_array = Entrada_Utilities::buildAudienceArray($audience_array);

                                                            switch ($audience_type) {
                                                                case "course_id" :
                                                                    $current_audience_course[] = (int)$audience_value;
                                                                    break;
                                                                case "cohort" :
                                                                    $current_audience_cohort[$audience_value] = $audience_array;
                                                                    break;
                                                                case "group_id" :
                                                                    $current_audience_cgroup[$audience_value] = $audience_array;
                                                                    break;
                                                                case "proxy_id" :
                                                                    $current_audience_proxy_id[$audience_value] = $audience_array;
                                                                    break;
                                                            }
                                                        }
                                                    }
                                                }

                                                ksort($current_audience_cohort);
                                                ksort($current_audience_cgroup);
                                                ksort($current_audience_proxy_id);

                                                // Generates add and remove for event audience
                                                if (isset($current_audience_course) && is_array($current_audience_course) && isset($PROCESSED["associated_course_ids"]) && is_array($PROCESSED["associated_course_ids"])) {
                                                    $diff_course_remove = array_diff($current_audience_course, $PROCESSED["associated_course_ids"]);
                                                    $diff_course_add    = array_diff($PROCESSED["associated_course_ids"], $current_audience_course);
                                                }

                                                $audience_type  = "cohort";
                                                if (isset($cohort_times) && is_array($cohort_times) && !empty($cohort_times)) {
                                                    foreach ($cohort_times as $group_id => $cohort_time_r) {
                                                        $audience_value = $group_id;

                                                        $cohort_time_r["custom_time_start"] = $recurring_event_start + $cohort_time_r["start_time_offset"];
                                                        $cohort_time_r["custom_time_end"]   = $recurring_event_start + $cohort_time_r["end_time_offset"];

                                                        $custom_time       = $cohort_time_r["custom_time"];
                                                        $custom_time_start = $cohort_time_r["custom_time_start"];
                                                        $custom_time_end   = $cohort_time_r["custom_time_end"];

                                                        $audience = Models_Event_Audience::fetchRowByEventIdTypeValue($recurring_event_id, $audience_type, $audience_value);

                                                        if ($audience) {
                                                            $audience->setUpdatedBy($ENTRADA_USER->getID());
                                                            $audience->setUpdatedDate(time());
                                                            $audience->setCustomTime($custom_time);
                                                            $audience->setCustomTimeStart($custom_time_start);
                                                            $audience->setCustomTimeEnd($custom_time_end);
                                                        } else {
                                                            $audience = new Models_Event_Audience(array(
                                                                "event_id"          => $recurring_event_id,
                                                                "audience_type"     => $audience_type,
                                                                "audience_value"    => $audience_value,
                                                                "custom_time"       => $custom_time,
                                                                "custom_time_start" => $custom_time_start,
                                                                "custom_time_end"   => $custom_time_end,
                                                                "updated_date"      => time(),
                                                                "updated_by"        => $ENTRADA_USER->getID()
                                                            ));
                                                        }

                                                        if (isset($cohort_times_recur_a) && is_array($cohort_times_recur_a) && !array_key_exists($audience_value, $cohort_times_recur_a)) {
                                                            $audience_array = $audience->toArray();
                                                            $audience_array = Entrada_Utilities::buildAudienceArray($audience_array);
                                                            $cohort_times_recur_o[$audience_value] = $audience;
                                                            $cohort_times_recur_a[$audience_value] = $audience_array;
                                                        }
                                                    }
                                                }

                                                $audience_type  = "group_id";
                                                if (isset($cgroup_times) && is_array($cgroup_times) && !empty($cgroup_times)) {
                                                    foreach ($cgroup_times as $group_id => $cgroup_time_r) {
                                                        $audience_value = $group_id;

                                                        $cgroup_time_r["custom_time_start"] = $recurring_event_start + $cgroup_time_r["start_time_offset"];
                                                        $cgroup_time_r["custom_time_end"]   = $recurring_event_start + $cgroup_time_r["end_time_offset"];

                                                        $custom_time       = $cgroup_time_r["custom_time"];
                                                        $custom_time_start = $cgroup_time_r["custom_time_start"];
                                                        $custom_time_end   = $cgroup_time_r["custom_time_end"];

                                                        $audience = Models_Event_Audience::fetchRowByEventIdTypeValue($recurring_event_id, $audience_type, $audience_value);

                                                        if ($audience) {
                                                            $audience->setUpdatedBy($ENTRADA_USER->getID());
                                                            $audience->setUpdatedDate(time());
                                                            $audience->setCustomTime($custom_time);
                                                            $audience->setCustomTimeStart($custom_time_start);
                                                            $audience->setCustomTimeEnd($custom_time_end);
                                                        } else {
                                                            $audience = new Models_Event_Audience(array(
                                                                "event_id"          => $recurring_event_id,
                                                                "audience_type"     => $audience_type,
                                                                "audience_value"    => $audience_value,
                                                                "custom_time"       => $custom_time,
                                                                "custom_time_start" => $custom_time_start,
                                                                "custom_time_end"   => $custom_time_end,
                                                                "updated_date"      => time(),
                                                                "updated_by"        => $ENTRADA_USER->getID()
                                                            ));
                                                        }

                                                        if (isset($cgroup_times_recur_a) && is_array($cgroup_times_recur_a) && !array_key_exists($audience_value, $cgroup_times_recur_a)) {
                                                            $audience_array = $audience->toArray();
                                                            $audience_array = Entrada_Utilities::buildAudienceArray($audience_array);
                                                            $cgroup_times_recur_o[$audience_value] = $audience;
                                                            $cgroup_times_recur_a[$audience_value] = $audience_array;
                                                        }
                                                    }
                                                }

                                                $audience_type  = "proxy_id";
                                                if (isset($proxy_times) && is_array($proxy_times) && !empty($proxy_times)) {
                                                    foreach ($proxy_times as $user_id => $proxy_time_r) {
                                                        $audience_value = $user_id;

                                                        $proxy_time_r["custom_time_start"] = $recurring_event_start + $proxy_time_r["start_time_offset"];
                                                        $proxy_time_r["custom_time_end"]   = $recurring_event_start + $proxy_time_r["end_time_offset"];

                                                        $custom_time       = $proxy_time_r["custom_time"];
                                                        $custom_time_start = $proxy_time_r["custom_time_start"];
                                                        $custom_time_end   = $proxy_time_r["custom_time_end"];

                                                        $audience = Models_Event_Audience::fetchRowByEventIdTypeValue($recurring_event_id, $audience_type, $audience_value);

                                                        if ($audience) {
                                                            $audience->setUpdatedBy($ENTRADA_USER->getID());
                                                            $audience->setUpdatedDate(time());
                                                            $audience->setCustomTime($custom_time);
                                                            $audience->setCustomTimeStart($custom_time_start);
                                                            $audience->setCustomTimeEnd($custom_time_end);
                                                        } else {
                                                            $audience = new Models_Event_Audience(array(
                                                                "event_id"          => $recurring_event_id,
                                                                "audience_type"     => $audience_type,
                                                                "audience_value"    => $audience_value,
                                                                "custom_time"       => $custom_time,
                                                                "custom_time_start" => $custom_time_start,
                                                                "custom_time_end"   => $custom_time_end,
                                                                "updated_date"      => time(),
                                                                "updated_by"        => $ENTRADA_USER->getID()
                                                            ));
                                                        }

                                                        if (isset($proxy_times_recur_a) && is_array($proxy_times_recur_a) && !array_key_exists($audience_value, $proxy_times_recur_a)) {
                                                            $audience_array = $audience->toArray();
                                                            $audience_array = Entrada_Utilities::buildAudienceArray($audience_array);
                                                            $proxy_times_recur_o[$audience_value] = $audience;
                                                            $proxy_times_recur_a[$audience_value] = $audience_array;
                                                            }
                                                        }
                                                    }

                                                $cohort_update = compare_mlti_array($current_audience_cohort, $cohort_times_recur_a, "audience_value");

                                                if (isset($cohort_update) && is_array($cohort_update)) {
                                                    $cohort_changes = Entrada_Utilities::buildInsertUpdateDelete($cohort_update);
                                                }

                                                $cgroup_update = compare_mlti_array($current_audience_cgroup, $cgroup_times_recur_a, "audience_value");

                                                if (isset($cgroup_update) && is_array($cgroup_update)) {
                                                    $cgroup_changes = Entrada_Utilities::buildInsertUpdateDelete($cgroup_update);
                                                }

                                                $proxy_update = compare_mlti_array($current_audience_proxy_id, $proxy_times_recur_a, "audience_value");

                                                if (isset($proxy_update) && is_array($proxy_update)) {
                                                    $proxy_changes = Entrada_Utilities::buildInsertUpdateDelete($proxy_update);
                                                }

                                                $cohort_insert     = $cohort_changes["insert"];
                                                $cohort_update     = $cohort_changes["update"];
                                                $cohort_delete     = $cohort_changes["delete"];
                                                $cgroup_insert     = $cgroup_changes["insert"];
                                                $cgroup_update     = $cgroup_changes["update"];
                                                $cgroup_delete     = $cgroup_changes["delete"];
                                                $proxy_insert      = $proxy_changes["insert"];
                                                $proxy_update      = $proxy_changes["update"];
                                                $proxy_delete      = $proxy_changes["delete"];

                                                if ($diff_course_add || $diff_course_remove || $cohort_insert || $cohort_update || $cohort_delete || $cgroup_insert || $cgroup_update || $cgroup_delete || $proxy_insert || $proxy_update || $proxy_delete) {
                                                    $edited_audience = false;
                                                    switch ($PROCESSED["event_audience_type"]) {
                                                        case "course" :
                                                            //clear non course enrollment styles if found.
                                                            $query = "  SELECT `eaudience_id` FROM `" . $tables["audience"] . "` " . $recurring_where_query . "
                                                                        AND `audience_type` NOT IN ('course_id')";
                                                            $remove_non_course_enrollment = $db->GetAll($query);

                                                            if (isset($remove_non_course_enrollment) && is_array($remove_non_course_enrollment) && !empty($remove_non_course_enrollment)) {
                                                                foreach ($remove_non_course_enrollment AS $remove) {
                                                                    $query = "  DELETE FROM `" . $tables["audience"] . "` " . $recurring_where_query . "
                                                                                AND `eaudience_id` = " . $db->qstr($remove["eaudience_id"]);
                                                                    if (!$db->Execute($query)) {
                                                                        add_error("There was an error while trying to delete the selected <strong>Audience</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.".$db->ErrorMsg());
                                                                        application_log("error", "Unable to delete event_audience. Database said: ".$db->ErrorMsg());
                                                                    } else {
                                                                        $edited_audience = true;
                                                                    }
                                                                }
                                                            }
                                                            /**
                                                             * Course ID (there is only one at this time, but this processes more than 1).
                                                             */
                                                            if (isset($diff_course_remove) && is_array($diff_course_remove) && !empty($diff_course_remove)) {
                                                                foreach ($diff_course_remove as $remove) {
                                                                    $query = "  DELETE FROM `" . $tables["audience"] . "` " . $recurring_where_query . "
                                                                                AND `audience_type` = 'course_id'
                                                                                AND `audience_value` = " . $db->qstr($remove);
                                                                    if (!$db->Execute($query)) {
                                                                        add_error("There was an error while trying to delete the selected <strong>Course ID Audience</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.".$db->ErrorMsg());
                                                                        application_log("error", "Unable to delete event_audience. Database said: ".$db->ErrorMsg());
                                                                    } else {
                                                                        $edited_audience = true;
                                                                    }
                                                                }
                                                            }

                                                            if (isset($diff_course_add) && is_array($diff_course_add) && !empty($diff_course_add)) {
                                                                foreach ($diff_course_add as $add) {
                                                                    $audience_data = array(
                                                                        "event_id"          => $recurring_event["event_id"],
                                                                        "audience_type"     => "course_id",
                                                                        "audience_value"    => (int) $add,
                                                                        "updated_date"      => time(),
                                                                        "updated_by"        => $ENTRADA_USER->getID());
                                                                    if ($is_draft) {
                                                                        $audience_data["devent_id"] = $devent_id;
                                                                    }
                                                                    if (!$db->AutoExecute($tables["audience"], $audience_data, "INSERT")) {
                                                                        add_error("There was an error while trying to attach the <strong>Course ID</strong> to one of the selected recurring events.<br /><br />The system administrator was informed of this error; please try again later.");
                                                                        application_log("error", "Unable to insert a new event_audience, course_id record while adding a new event. Database said: ".$db->ErrorMsg());
                                                                    } else {
                                                                        $edited_audience = true;
                                                                    }
                                                                }
                                                            }
                                                            break;
                                                        case "custom" :
                                                            //remove course_id enrollment if its there.
                                                            if (isset($current_audience_course) && is_array($current_audience_course) && !empty($current_audience_course)) {
                                                                foreach ($current_audience_course as $remove) {
                                                                    $query = "  DELETE FROM `" . $tables["audience"] . "` " . $recurring_where_query . "
                                                                                AND `audience_type` = 'course_id'
                                                                                AND `audience_value` = " . $db->qstr($remove);
                                                                    if (!$db->Execute($query)) {
                                                                        add_error("There was an error while trying to delete the selected <strong>Course ID Audience</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.".$db->ErrorMsg());
                                                                        application_log("error", "Unable to delete event_audience. Database said: ".$db->ErrorMsg());
                                                                    } else {
                                                                        $edited_audience = true;
                                                                    }
                                                                }
                                                            }

                                                            /**
                                                             * Cohort
                                                             */
                                                            if (isset($cohort_insert) && is_array($cohort_insert)) {
                                                                foreach ($cohort_insert as $insert) {
                                                                    $insert["updated_by"]   = $ENTRADA_USER->getID();
                                                                    $insert["updated_date"] = time();
                                                                    $insert["event_id"]     = $recurring_event_id;
                                                                    $audience = new Models_Event_Audience($insert);
                                                                    if (!$audience->insert()) {
                                                                        add_error("There was an error while trying to attach the selected <strong>Cohort</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.");
                                                                        application_log("error", "Unable to insert a new event_audience, cohort record while editing an event. Database said: ".$db->ErrorMsg());
                                                                    } else {
                                                                        $edited_audience = true;
                                                                    }
                                                                }
                                                            }

                                                            if (isset($cohort_delete) && is_array($cohort_delete)) {
                                                                foreach ($cohort_delete as $delete) {
                                                                    $audience_type  = $delete["audience_type"];
                                                                    $audience_value = $delete["audience_value"];
                                                                    $audience = Models_Event_Audience::fetchRowByEventIdTypeValue($recurring_event_id, $audience_type, $audience_value);
                                                                    if ($audience) {
                                                                        if (!$audience->delete()) {
                                                                            add_error("There was an error while trying to delete the selected <strong>Cohort</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.");
                                                                            application_log("error", "Unable to delete an event_audience, cohort record while editing an event. Database said: ".$db->ErrorMsg());
                                                                        } else {
                                                                            $edited_audience = true;
                                                                        }
                                                                    }
                                                                }
                                                                    }

                                                            if (isset($cohort_update) && is_array($cohort_update)) {
                                                                foreach ($cohort_update as $update) {
                                                                    // todo update this with event start and end times if not set
                                                                    $audience_type      = $update["audience_type"];
                                                                    $audience_value     = $update["audience_value"];
                                                                    $custom_time        = ($update["custom_time"]       ? $update["custom_time"] : 0);
                                                                    $custom_time_start  = ($update["custom_time_start"] ? $update["custom_time_start"] : 0);
                                                                    $custom_time_end    = ($update["custom_time_end"]   ? $update["custom_time_end"] : 0);
                                                                    $audience = Models_Event_Audience::fetchRowByEventIdTypeValue($recurring_event_id, $audience_type, $audience_value);
                                                                    $audience->setUpdatedBy($ENTRADA_USER->getID());
                                                                    $audience->setUpdatedDate(time());
                                                                    $audience->setCustomTime($custom_time);
                                                                    $audience->setCustomTimeStart($custom_time_start);
                                                                    $audience->setCustomTimeEnd($custom_time_end);
                                                                    if ($audience) {
                                                                        if (!$audience->update()) {
                                                                            add_error("There was an error while trying to update the selected <strong>Cohort</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.");
                                                                            application_log("error", "Unable to update an event_audience, cohort record while editing an event. Database said: ".$db->ErrorMsg());
                                                                        } else {
                                                                            $edited_audience = true;
                                                                        }
                                                                    }
                                                                }
                                                            }

                                                            /**
                                                             * Course Groups
                                                             */

                                                            if (isset($cgroup_insert) && is_array($cgroup_insert)) {
                                                                foreach ($cgroup_insert as $insert) {
                                                                    $insert["event_id"]     = $recurring_event_id;
                                                                    $insert["updated_by"]   = $ENTRADA_USER->getID();
                                                                    $insert["updated_date"] = time();
                                                                    $audience = new Models_Event_Audience($insert);
                                                                    if (!$audience->insert()) {
                                                                        add_error("There was an error while trying to attach the selected <strong>Course Group</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.");
                                                                        application_log("error", "Unable to insert a new event_audience, course group record while editing an event. Database said: ".$db->ErrorMsg());
                                                                    } else {
                                                                        $edited_audience = true;
                                                                    }
                                                                }
                                                                    }

                                                            if (isset($cgroup_delete) && is_array($cgroup_delete)) {
                                                                foreach ($cgroup_delete as $delete) {
                                                                    $audience_type  = $delete["audience_type"];
                                                                    $audience_value = $delete["audience_value"];
                                                                    $audience = Models_Event_Audience::fetchRowByEventIdTypeValue($recurring_event_id, $audience_type, $audience_value);
                                                                    if ($audience) {
                                                                        if (!$audience->delete()) {
                                                                            add_error("There was an error while trying to delete the selected <strong>Course Group</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.");
                                                                            application_log("error", "Unable to delete an event_audience, Course Group record while editing an event. Database said: ".$db->ErrorMsg());
                                                                        } else {
                                                                            $edited_audience = true;
                                                                        }
                                                                    }
                                                                }
                                                            }

                                                            if (isset($cgroup_update) && is_array($cgroup_update)) {
                                                                foreach ($cgroup_update as $update) {
                                                                    $audience_type      = $update["audience_type"];
                                                                    $audience_value     = $update["audience_value"];
                                                                    $custom_time        = $update["custom_time"];
                                                                    $custom_time_start  = $update["custom_time_start"];
                                                                    $custom_time_end    = $update["custom_time_end"];
                                                                    $audience = Models_Event_Audience::fetchRowByEventIdTypeValue($recurring_event_id, $audience_type, $audience_value);
                                                                    $audience->setUpdatedBy($ENTRADA_USER->getID());
                                                                    $audience->setUpdatedDate(time());
                                                                    $audience->setCustomTime($custom_time);
                                                                    $audience->setCustomTimeStart($custom_time_start);
                                                                    $audience->setCustomTimeEnd($custom_time_end);
                                                                    if ($audience) {
                                                                        if (!$audience->update()) {
                                                                            add_error("There was an error while trying to update the selected <strong>Course Group</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.");
                                                                            application_log("error", "Unable to update an event_audience, Course Group record while editing an event. Database said: ".$db->ErrorMsg());
                                                                        } else {
                                                                            $edited_audience = true;
                                                                        }
                                                                    }
                                                                }
                                                            }

                                                            /**
                                                             * Learners
                                                             */

                                                            if (isset($proxy_insert) && is_array($proxy_insert)) {
                                                                foreach ($proxy_insert as $insert) {
                                                                    $insert["event_id"]     = $EVENT_ID;
                                                                    $insert["updated_by"]   = $ENTRADA_USER->getID();
                                                                    $insert["updated_date"] = time();
                                                                    $audience = new Models_Event_Audience($insert);
                                                                    if (!$audience->insert()) {
                                                                        add_error("There was an error while trying to attach the selected <strong>Proxy ID</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.");
                                                                        application_log("error", "Unable to insert a new event_audience, Proxy ID record while editing an event. Database said: ".$db->ErrorMsg());
                                                                    } else {
                                                                        $edited_audience = true;
                                                                    }
                                                                }
                                                            }

                                                            if (isset($proxy_delete) && is_array($proxy_delete)) {
                                                                foreach ($proxy_delete as $delete) {
                                                                    $audience_type  = $delete["audience_type"];
                                                                    $audience_value = $delete["audience_value"];
                                                                    $audience = Models_Event_Audience::fetchRowByEventIdTypeValue($EVENT_ID, $audience_type, $audience_value);
                                                                    if ($audience) {
                                                                        if (!$audience->delete()) {
                                                                            add_error("There was an error while trying to delete the selected <strong>Proxy ID</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.");
                                                                            application_log("error", "Unable to delete an event_audience, Proxy ID record while editing an event. Database said: ".$db->ErrorMsg());
                                                                        } else {
                                                                            $edited_audience = true;
                                                                        }
                                                                    }
                                                                }
                                                                    }

                                                            if (isset($proxy_update) && is_array($proxy_update)) {
                                                                foreach ($proxy_update as $update) {
                                                                    $audience_type      = $update["audience_type"];
                                                                    $audience_value     = $update["audience_value"];
                                                                    $custom_time        = $update["custom_time"];
                                                                    $custom_time_start  = $update["custom_time_start"];
                                                                    $custom_time_end    = $update["custom_time_end"];
                                                                    $audience = Models_Event_Audience::fetchRowByEventIdTypeValue($EVENT_ID, $audience_type, $audience_value);
                                                                    $audience->setUpdatedBy($ENTRADA_USER->getID());
                                                                    $audience->setUpdatedDate(time());
                                                                    $audience->setCustomTime($custom_time);
                                                                    $audience->setCustomTimeStart($custom_time_start);
                                                                    $audience->setCustomTimeEnd($custom_time_end);
                                                                    if ($audience) {
                                                                        if (!$audience->update()) {
                                                                            add_error("There was an error while trying to update the selected <strong>Proxy ID</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.");
                                                                            application_log("error", "Unable to update an event_audience, proxy id record while editing an event. Database said: ".$db->ErrorMsg());
                                                                        } else {
                                                                            $edited_audience = true;
                                                                        }
                                                                    }
                                                                }
                                                            }

                                                            break;
                                                        default :
                                                            add_error("There was no audience information provided, so this event is without an audience.");
                                                            break;
                                                    }

                                                    if ($edited_audience == true) {
                                                        $insert_value = "Event Audience";
                                                        if (!in_array($insert_value, $recurring_stats)) {
                                                            $recurring_stats[] = $insert_value;
                                                        }
                                                    }

                                                    /**
                                                     * Remove attendance records for anyone who is no longer a valid audience member of the course.
                                                     */
                                                    $audience = events_fetch_event_audience_attendance($recurring_event["event_id"]);
                                                    if ($audience) {
                                                        $valid_audience = array();
                                                        foreach ($audience as $learner){
                                                            $valid_audience[] = $learner["id"];
                                                        }

                                                        if (!empty($valid_audience)) {
                                                            $query = "DELETE FROM `event_attendance` WHERE `event_id` = ".$db->qstr($recurring_event["event_id"])." AND `proxy_id` NOT IN (".implode(",", $valid_audience).")";
                                                            $db->Execute($query);
                                                        }

                                                    } else {
                                                        $query = "DELETE FROM `event_attendance` WHERE `event_id` = ".$db->qstr($recurring_event["event_id"]);
                                                        $db->Execute($query);
                                                    }
                                                }
                                            }

                                                if (!has_error() && @array_intersect($_POST["update_recurring_fields"], array("event_title", "event_location", "course", "audience_visible", "attendance_required", "time_release", "event_types"))) {
                                                //checks if the description, objectives, or message has changed before saving.
                                                $changed_course_id = false;
                                                $changed_course_id = md5_change_value($recurring_event["event_id"], "event_id", "course_id", $PROCESSED_RECURRING_EVENT["course_id"], "events");

                                                $changed_event_title = false;
                                                $changed_event_title = md5_change_value($recurring_event["event_id"], "event_id", "event_title", $PROCESSED_RECURRING_EVENT["event_title"], "events");

                                                $changed_event_location = false;
                                                $changed_event_location = md5_change_value($recurring_event["event_id"], "event_id", "room_id", $PROCESSED_RECURRING_EVENT["room_id"], "events");

                                                $changed_audience_visible = false;
                                                $changed_audience_visible = md5_change_value($recurring_event["event_id"], "event_id", "audience_visible", $PROCESSED_RECURRING_EVENT["audience_visible"], "events");

                                                $changed_attendance_required = false;
                                                $changed_attendance_required = md5_change_value($recurring_event["event_id"], "event_id", "attendance_required", $PROCESSED_RECURRING_EVENT["attendance_required"], "events");

                                                $changed_release_date = false;
                                                $changed_release_date = md5_change_value($recurring_event["event_id"], "event_id", "release_date", $PROCESSED_RECURRING_EVENT["release_date"], "events");

                                                $changed_release_until = false;
                                                $changed_release_until = md5_change_value($recurring_event["event_id"], "event_id", "release_until", $PROCESSED_RECURRING_EVENT["release_until"], "events");

                                                    $changed_event_duration = false;
                                                    $changed_event_duration = md5_change_value($recurring_event["event_id"], "event_id", "event_duration", $PROCESSED_RECURRING_EVENT["event_duration"], "events");

                                                if (!$db->AutoExecute("`events`", $PROCESSED_RECURRING_EVENT, "UPDATE", "`event_id` = ".$db->qstr($recurring_event["event_id"]))) {
                                                    add_error("There was an error while trying to save changes to the selected <strong>Recurring Event</strong>.<br /><br />The system administrator was informed of this error; please try again later.");

                                                    application_log("error", "Unable to update an event record while editing a recurring event. Database said: ".$db->ErrorMsg());
                                                } else {
                                                    if ($changed_course_id) {
                                                        $insert_value = "Course";
                                                        if (!in_array($insert_value, $recurring_stats)) {
                                                            $recurring_stats[] = $insert_value;
                                                        }
                                                    }

                                                    if ($changed_event_title) {
                                                        $insert_value = "Event Title";
                                                        if (!in_array($insert_value, $recurring_stats)) {
                                                            $recurring_stats[] = $insert_value;
                                                        }
                                                    }

                                                    if ($changed_event_location) {
                                                        $insert_value = "Event Location";
                                                        if (!in_array($insert_value, $recurring_stats)) {
                                                            $recurring_stats[] = $insert_value;
                                                        }
                                                    }
                                                        
                                                        if ($changed_event_duration) {
                                                            $insert_value = "Event Duration";
                                                            if (!in_array($insert_value, $recurring_stats)) {
                                                                $recurring_stats[] = $insert_value;
                                                            }
                                                        }

                                                    if ($changed_audience_visible) {
                                                        $insert_value = "Audience Display";
                                                        if (!in_array($insert_value, $recurring_stats)) {
                                                            $recurring_stats[] = $insert_value;
                                                        }
                                                    }
                                                    if ($changed_attendance_required) {
                                                        $insert_value = "Attendance Required";
                                                        if (!in_array($insert_value, $recurring_stats)) {
                                                            $recurring_stats[] = $insert_value;
                                                        }
                                                    }

                                                    if ($changed_release_date) {
                                                        $insert_value = "Viewable Start";
                                                        if (!in_array($insert_value, $recurring_stats)) {
                                                            $recurring_stats[] = $insert_value;
                                                        }
                                                    }
                                                    if ($changed_release_until) {
                                                        $insert_value = "Viewable Finish";
                                                        if (!in_array($insert_value, $recurring_stats)) {
                                                            $recurring_stats[] = $insert_value;
                                                        }
                                                    }
                                                }
                                            }

                                            //insert recurring stats
                                            if (!empty($recurring_stats)) {
                                                $stats_string = "[";
                                                $stats_string .= implode(", ", $recurring_stats);
                                                $stats_string .= "].";
                                                history_log($recurring_event["event_id"], "updated event details: " . $stats_string, $PROXY_ID);
                                            }
                                        }

                                        if (!has_error()) {
                                            $query = "	SELECT b.*
                                                    FROM `community_courses` AS a
                                                    LEFT JOIN `community_pages` AS b
                                                    ON a.`community_id` = b.`community_id`
                                                    LEFT JOIN `community_page_options` AS c
                                                    ON b.`community_id` = c.`community_id`
                                                    WHERE c.`option_title` = 'show_history'
                                                    AND c.`option_value` = 1
                                                    AND b.`page_url` = 'course_calendar'
                                                    AND b.`page_active` = 1
                                                    AND a.`course_id` = ".$db->qstr($event_info["course_id"]);
                                            $result = $db->GetRow($query);
                                            if ($result) {
                                                $COMMUNITY_ID = $result["community_id"];
                                                $PAGE_ID = $result["cpage_id"];
                                                communities_log_history($COMMUNITY_ID, $PAGE_ID, $event_info["recurring_id"], "community_history_edit_recurring_events", 1);
                                            }

                                            add_success("You have successfully edited the recurring events associated with <strong>".html_encode($event_info["event_title"])."</strong> in the system.");

                                            communities_log_history($COMMUNITY_ID, $PAGE_ID, $recurring_event["event_id"], "community_history_edit_learning_event", 1);
                                            application_log("success", "Recurring Events [".$event_info["recurring_id"]."] have been modified.");
                                        }
                                    }
                                }

                                if (!$ERROR) {
                                    $query = "    SELECT b.*
                                                FROM `community_courses` AS a
                                                LEFT JOIN `community_pages` AS b
                                                ON a.`community_id` = b.`community_id`
                                                LEFT JOIN `community_page_options` AS c
                                                ON b.`community_id` = c.`community_id`
                                                WHERE c.`option_title` = 'show_history'
                                                AND c.`option_value` = 1
                                                AND b.`page_url` = 'course_calendar'
                                                AND b.`page_active` = 1
                                                AND a.`course_id` = ".$db->qstr($PROCESSED["course_id"]);
                                    $result = $db->GetRow($query);
                                    if ($result) {
                                        $COMMUNITY_ID = $result["community_id"];
                                        $PAGE_ID = $result["cpage_id"];
                                        communities_log_history($COMMUNITY_ID, $PAGE_ID, $EVENT_ID, "community_history_edit_learning_event", 1);
                                    }

                                    add_success("You have successfully edited <strong>".html_encode($PROCESSED["event_title"])."</strong> in the system.<br /><br />".$msg);
                                    $ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";

                                    application_log("success", "Event [".$EVENT_ID."] has been modified.");
                                }

                                if (!empty($stats)) {
                                    $stats_string = "[";
                                    $stats_string .= implode(", ", $stats);
                                    $stats_string .= "].";
                                    history_log($EVENT_ID, "updated event details: " . $stats_string, $PROXY_ID);
                                }
                            } else {
                                add_error("There was a problem updating this event in the system. The system administrator was informed of this error; please try again later.".$db->ErrorMsg());

                                application_log("error", "There was an error updating event_id [".$EVENT_ID."]. Database said: ".$db->ErrorMsg());
                            }
                        }

                        if ($ERROR) {
                            $STEP = 1;
                        }
                        break;
                    case 1 :
                    default :
                        $PROCESSED    = $event_info;

                        /**
                         * Add existing event type segments to the processed array.
                         */
                        $query = "  SELECT a.`eventtype_id`, a.`duration`, b.`eventtype_title`
                                    FROM `".$tables["event_types"]."` AS a
                                    LEFT JOIN `events_lu_eventtypes` AS b
                                    ON b.`eventtype_id` = a.`eventtype_id` ".
                                    $where_query."
                                    ORDER BY a.`eeventtype_id` ASC";

                        $results = $db->GetAll($query);

                        if ($results) {
                            foreach ($results as $contact_order => $result) {
                                $PROCESSED["event_types"][] = array($result["eventtype_id"], $result["duration"], $result["eventtype_title"]);
                            }
                        }

                        /**
                         * Add any existing associated faculty from the event_contacts table
                         * into the $PROCESSED["associated_faculty"] array.
                         */
                        $query = "SELECT * FROM `".$tables["contacts"]."` ".$where_query." ORDER BY `contact_order` ASC";
                        $results = $db->GetAll($query);
                        if ($results) {
                            foreach($results as $contact_order => $result) {
                                $PROCESSED["associated_faculty"][(int) $contact_order] = $result["proxy_id"];
                                $PROCESSED["display_role"][(int)$result["proxy_id"]] = $result["contact_role"];
                            }
                        }

                        if ($is_draft) {
                            $audiences = Models_Event_Draft_Event_Audience::fetchAllByDraftEventID($devent_id);
                        } else {
                            $audiences = Models_Event_Audience::fetchAllByEventID($EVENT_ID);
                        }

                        if (isset($audiences) && is_array($audiences)) {
                            $PROCESSED["event_audience_type"] = "custom";

                            foreach($audiences as $audience) {
                                if (isset($audience) && is_object($audience)) {
                                    $audience_type  = $audience->getAudienceType();
                                    $audience_value = $audience->getAudienceValue();
                                    switch($audience_type) {
                                        case "course_id" :
                                            $PROCESSED["event_audience_type"] = "course";

                                            $PROCESSED["associated_course_ids"] = (int) $audience_value;
                                            break;
                                        case "cohort" :
                                            $PROCESSED["associated_cohort_ids"][] = (int) $audience_value;
                                            if (isset($cohort_times_a) && is_array($cohort_times_a) && !array_key_exists($audience_value, $cohort_times_a)) {
                                                $audience_array = $audience->toArray();
                                                $audience_array = Entrada_Utilities::buildAudienceArray($audience_array);
                                                $cohort_times_o[$audience_value] = $audience;
                                                $cohort_times_a[$audience_value] = $audience_array;
                                            }
                                            break;
                                        case "group_id" :
                                            $PROCESSED["associated_cgroup_ids"][] = (int) $audience_value;
                                            if (isset($cgroup_times_a) && is_array($cgroup_times_a) && !array_key_exists($audience_value, $cgroup_times_a)) {
                                                $audience_array = $audience->toArray();
                                                $audience_array = Entrada_Utilities::buildAudienceArray($audience_array);
                                                $cgroup_times_o[$audience_value] = $audience;
                                                $cgroup_times_a[$audience_value] = $audience_array;
                                            }
                                            break;
                                        case "proxy_id" :
                                            $PROCESSED["associated_proxy_ids"][] = (int) $audience_value;
                                            if (isset($proxy_times_a) && is_array($proxy_times_a) && !array_key_exists($audience_value, $proxy_times_a)) {
                                                $audience_array = $audience->toArray();
                                                $audience_array = Entrada_Utilities::buildAudienceArray($audience_array);
                                                $proxy_times_o[$audience_value] = $audience;
                                                $proxy_times_a[$audience_value] = $audience_array;
                                            }
                                            break;
                                    }
                                }
                            }
                        }
                        break;
                }

                // Display Content
                switch($STEP) {
                    case 2 :
                        display_status_messages();
                        break;
                    case 1 :
                    default :
                        $HEAD[] = "<script type=\"text/javascript\" >var ENTRADA_URL = '". ENTRADA_URL ."';</script>\n";
                        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/eventtypes_list.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
                        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/AutoCompleteList.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
                        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/elementresizer.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
                        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.iris.min.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
                        $HEAD[] = "<script>var DELETE_IMAGE_URL = '".ENTRADA_URL."/images/action-delete.gif';</script>";
                        $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js\"></script>\n";
                        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />\n";
                        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/events/admin/edit.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
                        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/events/time_override.css?release=". html_encode(APPLICATION_VERSION) ."\" />";
                        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/color-picker.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
                        $HEAD[] = "<script type=\"text/javascript\">var EVENT_COLOR_PALETTE = ".json_encode($translate->_("event_color_palette")).";</script>\n";

                        $LASTUPDATED = (isset($event_info["updated_date"]) && $event_info["updated_date"] ? $event_info["updated_date"] : 0);


                        /**
                         * Compiles the full list of faculty members.
                         */
                        $FACULTY_LIST = array();
                        $query = "    SELECT a.`id` AS `proxy_id`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, a.`organisation_id`
                                    FROM `".AUTH_DATABASE."`.`user_data` AS a
                                    LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
                                    ON b.`user_id` = a.`id`
                                    WHERE b.`app_id` = '".AUTH_APP_ID."'
                                    AND (b.`group` = 'faculty' OR (b.`group` = 'resident' AND b.`role` = 'lecturer'))
                                    ORDER BY a.`lastname` ASC, a.`firstname` ASC";
                        $results = $db->GetAll($query);
                        if ($results) {
                            foreach($results as $result) {
                                $FACULTY_LIST[$result["proxy_id"]] = array("proxy_id" => $result["proxy_id"], "fullname" => $result["fullname"], "organisation_id" => $result["organisation_id"]);
                            }
                        }

                        /**
                         * Compiles the list of students.
                         */
                        $STUDENT_LIST = array();
                        $query = "    SELECT a.`id` AS `proxy_id`, b.`role`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, a.`organisation_id`
                                    FROM `".AUTH_DATABASE."`.`user_data` AS a
                                    LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
                                    ON a.`id` = b.`user_id`
                                    WHERE b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
                                    AND b.`account_active` = 'true'
                                    AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
                                    AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")
                                    AND b.`group` = 'student'
                                    AND b.`role` >= '".(date("Y") - ((date("m") < 7) ?  2 : 1))."'
                                    ORDER BY b.`role` ASC, a.`lastname` ASC, a.`firstname` ASC";
                        $results = $db->GetAll($query);
                        if ($results) {
                            foreach($results as $result) {
                                $STUDENT_LIST[$result["proxy_id"]] = array("proxy_id" => $result["proxy_id"], "fullname" => $result["fullname"], "organisation_id" => $result["organisation_id"]);
                            }
                        }

                        /**
                         * Compiles the list of student groups.
                         */
                        $GROUP_LIST = array();
                        $query = "    SELECT *
                                    FROM `course_groups`
                                    WHERE `course_id` = ".$db->qstr($PROCESSED["course_id"])."
                                    AND (`active` = '1' OR `course_id` = ".$db->qstr($event_info["course_id"]).")
                                    ORDER BY LENGTH(`group_name`), `group_name` ASC";
                        $results = $db->GetAll($query);
                        if ($results) {
                            foreach($results as $result) {
                                $GROUP_LIST[$result["cgroup_id"]] = $result;
                            }
                        }

                        /**
                         * Compiles the list of groups.
                         */
                        $COHORT_LIST = array();
                        $query = "    SELECT *
                                    FROM `groups`
                                    WHERE `group_active` = '1'
                                    AND `group_type` = 'cohort'
                                    ORDER BY `group_name` ASC";
                        $results = $db->GetAll($query);
                        if ($results) {
                            foreach($results as $result) {
                                $COHORT_LIST[$result["group_id"]] = $result;
                            }
                        }

                        if ($ERROR) {
                            echo display_error();
                        }

                        $query = "SELECT `organisation_id`, `organisation_title` FROM `".AUTH_DATABASE."`.`organisations` ORDER BY `organisation_title` ASC";
                        $organisation_results = $db->GetAll($query);
                        if ($organisation_results) {
                            $organisations = array();
                            foreach ($organisation_results as $result) {
                                if ($ENTRADA_ACL->amIAllowed("resourceorganisation".$result["organisation_id"], "create")) {
                                    $organisation_categories[$result["organisation_id"]] = array("text" => $result["organisation_title"], "value" => "organisation_".$result["organisation_id"], "category" => true);
                                }
                            }
                        }
                        ?>
                        <form name="editEventForm" id="editEventForm" action="<?php echo ENTRADA_URL; ?>/admin/events?<?php echo replace_query(array("step" => 2)); ?>" method="post" class="form-horizontal">
                            <div class="control-group">
                                <label for="course_id" class="control-label form-required">Select Course:</label>
                                <div class="controls">
                                    <?php
                                    $query = "SELECT `course_id`, `course_name`, `course_code`, `course_active`
                                            FROM `courses`
                                            WHERE `organisation_id` = ".$db->qstr($event_info["organisation_id"])."
                                            AND (`course_active` = '1' OR `course_id` = ".$db->qstr($event_info["course_id"]).")
                                            ORDER BY `course_code`, `course_name` ASC";
                                    $results = $db->GetAll($query);
                                    if ($results) {
                                        ?>
                                        <select id="course_id" name="course_id" style="width: 97%">
                                            <option value="0">-- Select the course this event belongs to --</option>
                                            <?php
                                            foreach($results as $result) {
                                                if ($ENTRADA_ACL->amIAllowed(new EventResource(null, $result["course_id"], $event_info["organisation_id"]), "create")) {
                                                    echo "<option value=\"".(int) $result["course_id"]."\"".(($PROCESSED["course_id"] == $result["course_id"]) ? " selected=\"selected\"" : "").">".html_encode(($result["course_code"] ? $result["course_code"].": " : "").$result["course_name"])."</option>\n";
                                                }
                                            }
                                            ?>
                                        </select>
                                        <script type="text/javascript">
                                            jQuery("#course_id").change(function() {
                                                var course_id = jQuery("#course_id option:selected").val();

                                                if (course_id) {
                                                    jQuery("#course_id_path").load("<?php echo ENTRADA_RELATIVE; ?>/admin/events?section=api-course-path&id=" + course_id);
                                                }

                                                updateAudienceOptions();
                                                generateEventAutocomplete();
                                            });
                                        </script>
                                        <?php
                                    } else {
                                        echo display_error("You do not have any courses availabe in the system at this time, please add a course prior to adding learning events.");
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="control-group">
                                <label for="event_title" class="control-label form-required"><?php echo $translate->_("Event Title"); ?>:</label>
                                <div class="controls">
                                    <div id="course_id_path" class="content-small"><?php echo fetch_course_path($PROCESSED["course_id"]); ?></div>
                                    <input type="text" id="event_title" name="event_title" value="<?php echo html_encode($PROCESSED["event_title"]); ?>" maxlength="255" style="width: 95%; font-size: 150%; padding: 3px" />
                                </div>
                            </div>
                            <div class="control-group">
                                <label for="event_color" class="control-label form-nrequired"><?php echo $translate->_("Event")." ".$translate->_("Colour"); ?>:</label>
                                <div class="controls">
                                    <input type="text" id="event_color" name="event_color" value="<?php echo html_encode(!empty($PROCESSED["event_color"]) ? $PROCESSED["event_color"] : ""); ?>" maxlength="20" class="span3">
                                </div>
                            </div>

                            <?php echo Entrada_Utilities::generate_calendars("event", $translate->_("Event Date & Time"), true, true, ((isset($PROCESSED["event_start"])) ? $PROCESSED["event_start"] : 0)); ?>

                            <?php
                            $event_buildings = events_fetch_all_buildings();

                            if ($event_buildings) {
                                ?>
                            <div class="control-group">
                                <label for="building_id" class="control-label form-nrequired"><? echo $translate->_("Event Location Building"); ?></label>
                                <div class="controls">
                                    <select onchange="loadRooms()" id="building_id" name="building_id">

                                            <?php
                                                foreach ($event_buildings as $building) {
                                                    echo "<option value=\"" . $building['building_id'] . "\">(" . $building['building_code'] . ") " . $building['building_name'] . "</option>\n";
                                                }
                                            ?>

                                        <option value=""><?php echo $translate->_("Other location"); ?></option>
                                    </select>
                                </div>

                            </div>

                            <div id="roomlocation" style="<?php echo (isset($PROCESSED["room_id"]) && $PROCESSED["room_id"] != "" ? "" : "display:none;") ?>" class="control-group">
                                <?php

                                    $event_rooms = events_fetch_all_locations();

                                    if ($event_rooms) {
                                ?>
                                <label for="room_id" class="control-label form-nrequired"><?php echo $translate->_("Event Location Room"); ?>:</label>
                                <div class="controls">
                                    <select id="room_id" name="room_id">
                                            <option value="-1"><?php echo $translate->_("Select room"); ?></option>
                                    </select>
                                </div>
                                <?php
                                    }
                                ?>
                            </div>
                        <?php
                        }
                        ?>

                            <div class="control-group" id="otherlocation">
                                <label for="event_location" class="control-label form-nrequired"><?php echo $translate->_("Event Location"); ?>:</label>

                                <div class="controls">
                                    <input value="<?php echo (isset($PROCESSED["event_location"]) && $PROCESSED["event_location"] != "" ? $PROCESSED["event_location"] : "") ?>" type="text" id="event_location" name="event_location"/>
                                </div>
                            </div>

                            <script>
                                jQuery(document).ready(function(){
                                    <?php
                                    if(isset($PROCESSED["room_id"])) {
                                        echo "room_id = ".$PROCESSED["room_id"].";";
                                    } else {
                                        echo "room_id = 0;";
                                    }
                                    ?>
                                    jQuery.post('<?php echo ENTRADA_URL."/api/api-location-management.inc.php"; ?>',
                                        {
                                            method: "get-room-building-id",
                                            room_id: room_id
                                        }, function(data) {
                                            jsonResponse = jQuery.parseJSON(data);
                                            if (jsonResponse.status == "success") {
                                                jQuery("#building_id").find("option[value=\""+jsonResponse.data[0]+"\"]").attr("selected","selected");
                                                loadRooms(room_id);
                                            } else {
                                                jQuery("#building_id").find("option[value=\"\"]").attr("selected","selected");
                                            }
                                        });
                                });

                                function loadRooms (room_id) {
                                    var building = jQuery("#building_id").val();
                                    switch (building) {
                                        case "":
                                            jQuery("#room_id").html('<option value="-1"><?php echo $translate->_("Select room"); ?></option>');
                                            jQuery("#otherlocation").show();
                                            jQuery("#roomlocation").hide();
                                            break;
                                        default:
                                            jQuery.post('<?php echo ENTRADA_URL."/api/api-location-management.inc.php"; ?>',
                                                {
                                                    method: "get-rooms-by-building",
                                                    building_id: building
                                                }, function(data) {
                                                    jsonResponse = jQuery.parseJSON(data);
                                                    if (jsonResponse.status == "success") {
                                                        jQuery("#otherlocation").hide();
                                                        jQuery("#roomlocation").show();
                                                        jQuery("#event_location").val("");
                                                        jQuery("#room_id").html('<option value="-1"><?php echo $translate->_("Select room"); ?></option>');
                                                        jQuery.each(jsonResponse.data, function(key, value) {
                                                            jQuery("#room_id").append("<option value=\""+value.room_id+"\">"+value.room_name+"</option>");
                                                        });
                                                        jQuery("#room_id").find("option[value=\""+room_id+"\"]").attr("selected","selected");
                                                    } else {
                                                        jQuery("#room_id").html('<option value="-1"><?php echo $translate->_("Select room"); ?></option>');
                                                        jQuery("#otherlocation").show();
                                                        jQuery("#roomlocation").hide();
                                                    }
                                                });
                                            break;
                                    }
                                }
                            </script>

                            <div class="control-group">
                                <label for="eventtype_ids" class="control-label form-required"><?php echo $translate->_("Event Types"); ?>:</label>
                                <div class="controls">
                                    <script>
                                        var event_types = [];
                                    </script>
                                    <?php
                                    $query = "  SELECT a.* FROM `events_lu_eventtypes` AS a
                                                LEFT JOIN `eventtype_organisation` AS b
                                                ON a.`eventtype_id` = b.`eventtype_id`
                                                LEFT JOIN `".AUTH_DATABASE."`.`organisations` AS c
                                                ON c.`organisation_id` = b.`organisation_id`
                                                WHERE b.`organisation_id` = ".$db->qstr($event_info["organisation_id"])."
                                                AND a.`eventtype_active` = '1'
                                                ORDER BY a.`eventtype_title` ASC";
                                    $results = $db->GetAll($query);
                                    if ($results) {
                                        foreach($results as $result) {
                                            $description = nl2br($result["eventtype_description"]);
                                            $description = str_replace(array("\r\n", "\n\r", "\n", "\r"), "", $description);
                                            ?>

                                            <script>
                                                var event_type = [];
                                                event_type["target_id"] = "<?php echo (int) $result["eventtype_id"]; ?>";
                                                event_type["target_label"] = "<?php echo html_encode($result["eventtype_title"]); ?>";
                                                event_type["description"] = "<?php echo addslashes($description); ?>";
                                                event_types.push(event_type);
                                            </script>

                                            <?php
                                        }
                                        ?>

                                        <button id="eventtype_ids" class="btn btn-search-filter" style="min-width: 220px; text-align: left;"><?php echo $translate->_("Browse Event Types"); ?><i class="icon-chevron-down btn-icon pull-right"></i></button>
                                        <?php
                                    } else {
                                        echo display_error("No " . $translate->_("Event Types") . " were found. You will need to add at least one " . $translate->_("Event Type") . " before continuing.");
                                    }
                                    ?>
                                    <div id="duration_notice" style="margin-top: 5px">
                                        <div class="alert alert-info">
                                            <strong>Please Note:</strong> Select all of the different segments taking place within this learning event. When you select an event type it will appear below, and allow you to change the order and duration of each segment.
                                        </div>
                                    </div>

                                    <ol id="duration_container" class="sortableList" style="display: none;">
                                        <?php
                                        if (is_array($PROCESSED["event_types"])) {
                                            foreach ($PROCESSED["event_types"] as $eventtype) {
                                                echo "<li id=\"type_".$eventtype[0]."\" class=\"\">".$eventtype[2]."
                                                    <a href=\"#\" onclick=\"$(this).up().remove(); cleanupList(); return false;\" class=\"remove\"><img src=\"".ENTRADA_URL."/images/action-delete.gif\"></a>
                                                <span class=\"duration_segment_container\">Duration: <input type=\"text\" class=\"input-mini duration_segment\" name=\"duration_segment[]\" onchange=\"cleanupList();\" value=\"".$eventtype[1]."\"> minutes</span>
                                                </li>";
                                            }
                                        }
                                        ?>
                                    </ol>
                                    <div id="total_duration" class="content-small">Total time: 0 minutes.</div>
                                    <input id="eventtype_duration_order" name="eventtype_duration_order" style="display: none;">
                                </div>
                            </div>
                            <div class="control-group">
                                <label for="faculty_name" class="control-label form-nrequired"><?php echo $translate->_("Associated Faculty"); ?>:</label>
                                <div class="controls">
                                    <input type="text" id="faculty_name" name="fullname" autocomplete="off" placeholder="Example: <?php echo html_encode($ENTRADA_USER->getLastname().", ".$ENTRADA_USER->getFirstname()); ?>" class="span5" />
                                    <?php
                                    $ONLOAD[] = "faculty_list = new AutoCompleteList({ type: 'faculty', url: '". ENTRADA_RELATIVE ."/api/personnel.api.php?type=faculty', remove_image: '". ENTRADA_RELATIVE ."/images/action-delete.gif'})";
                                    ?>
                                    <div class="autocomplete" id="faculty_name_auto_complete"></div>
                                    <input type="hidden" id="associated_faculty" name="associated_faculty" />
                                    <input type="button" class="btn" id="add_associated_faculty" value="Add" />
                                    <script type="text/javascript">
                                        jQuery(function(){
                                            jQuery("#faculty_list img.list-cancel-image").live("click", function(){
                                                var proxy_id = jQuery(this).attr("rel");
                                                if ($("faculty_"+proxy_id)) {
                                                    var associated_faculty = jQuery("#associated_faculty").val().split(",");
                                                    var remove_index = associated_faculty.indexOf(proxy_id);

                                                    associated_faculty.splice(remove_index, 1);

                                                    jQuery("#associated_faculty").val(associated_faculty.join());

                                                    $("faculty_"+proxy_id).remove();
                                                }
                                            });
                                        });
                                    </script>
                                    <ul id="faculty_list" class="menu" style="margin-top: 15px">
                                        <?php
                                        if (isset($PROCESSED["associated_faculty"]) && is_array($PROCESSED["associated_faculty"]) && count($PROCESSED["associated_faculty"])) {
                                            foreach ($PROCESSED["associated_faculty"] as $faculty) {
                                                if ((array_key_exists($faculty, $FACULTY_LIST)) && is_array($FACULTY_LIST[$faculty])) {
                                                    ?>
                                                    <li class="user" id="faculty_<?php echo $FACULTY_LIST[$faculty]["proxy_id"]; ?>" style="cursor: move;margin-bottom:10px;width:350px;"><?php echo $FACULTY_LIST[$faculty]["fullname"]; ?><select name ="faculty_role[]" class="input-medium" style="float:right;margin-right:30px;margin-top:-5px;"><option value="teacher" <?php if($PROCESSED["display_role"][$faculty] == "teacher") echo "SELECTED";?>><?php echo $translate->_("Teacher"); ?></option><option value="tutor" <?php if($PROCESSED["display_role"][$faculty] == "tutor") echo "SELECTED";?>>Tutor</option><option value="ta" <?php if($PROCESSED["display_role"][$faculty] == "ta") echo "SELECTED";?>>Teacher's Assistant</option><option value="auditor" <?php if($PROCESSED["display_role"][$faculty] == "auditor") echo "SELECTED";?>>Auditor</option></select><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" rel="<?php echo $FACULTY_LIST[$faculty]["proxy_id"]; ?>" class="list-cancel-image" /></li>
                                                    <?php
                                                }
                                            }
                                        }
                                        ?>
                                    </ul>
                                    <input type="hidden" id="faculty_ref" name="faculty_ref" value="" />
                                    <input type="hidden" id="faculty_id" name="faculty_id" value="" />
                                </div>
                            </div>
                            <div class="control-group">
                                <label class="control-label form-nrequired"><?php echo $translate->_("Audience Options"); ?>:</label>
                                <div class="controls">
                                    <label for="audience_visible" class="checkbox">
                                        <input type="checkbox" name="audience_visible" id="audience_visible" value="1"<?php echo (($event_info["audience_visible"] == "1" || $PROCESSED["audience_visible"] == "1") ? " checked=\"checked\"" : ""); ?> />
                                        Show the audience to the learners
                                        <div class="content-small">This option controls the learner's ability to view who else is in the learning event.</div>
                                    </label>

                                    <label for="attendance_required" class="checkbox">
                                        <input type="checkbox" name="attendance_required" id="attendance_required" value="1"<?php echo (($event_info["attendance_required"] == "1" || $PROCESSED["attendance_required"] == "1") ? "checked=\"checked\"" : ""); ?> />
                                        Learner's attendance at this Learning Event is required
                                        <div class="content-small">This option controls whether or not attendance is required by the Associated Learners.</div>
                                    </label>
                                </div>
                            </div>
                            <?php
                            if ($PROCESSED["course_id"]) {
                                //show audience selector options
                                require_once(ENTRADA_ABSOLUTE."/core/modules/admin/events/api-audience-options.inc.php");
                                $start_event_time   = new DateTime(date("Y-m-d H:i:s", $PROCESSED["event_start"]));
                                $end_event_time     = new DateTime(date("Y-m-d H:i:s", $PROCESSED["event_finish"]));
                                $event_diff         = $end_event_time->diff($start_event_time);

                                // Count the half hour chunks
                                // If more than 2 hours then use hours instead of half hours
                                $half_hours    = ($event_diff->{"h"} * 2 ) + ($event_diff->{"i"} / 30);
                                $event_length  = ($event_diff->{"h"} * 60 ) + $event_diff->{"i"};
                                if ($half_hours > 4) {
                                    $intval    = "PT1H";
                                    $loops     = ($half_hours / 2) - 1;
                                } else {
                                    $intval    = "PT30M";
                                    $loops     = ($half_hours) - 1;
                                }
                                ?>
                                <script>
                                    var proxy_custom_time_array     = <?php echo json_encode($proxy_times_a);?>;
                                    var cohorts_custom_time_array   = <?php echo json_encode($cohort_times_a);?>;
                                    var cgroup_custom_time_array    = <?php echo json_encode($cgroup_times_a);?>;
                                    var event_length_minutes        = <?php echo $event_length;?>;
                                    var start_date                  = new Date("<?php echo $start_event_time->format("Y/m/d H:i:s") ?>");
                                    var end_date                    = new Date("<?php echo $end_event_time->format("Y/m/d H:i:s") ?>");
                                    // Converts milliseconds to minutes
                                    var start_date_mins             = start_date.getTime() / (1000 * 60);
                                    var validation                  = false;

                                    // resets arrays if empty
                                    if ((typeof proxy_custom_time_array && proxy_custom_time_array === "undefined") || proxy_custom_time_array === null) {
                                        proxy_custom_time_array = {};
                                    }
                                    if ((typeof cohorts_custom_time_array && cohorts_custom_time_array === "undefined") || cohorts_custom_time_array === null) {
                                        cohorts_custom_time_array = {};
                                    }
                                    if ((typeof cgroup_custom_time_array && cgroup_custom_time_array === "undefined") || cgroup_custom_time_array === null) {
                                        cgroup_custom_time_array = {};
                                    }


                                    jQuery(document).ready(function() {
                                        // Loops through the array of custom start times
                                        // This sets the start and end time for dates already set
                                        if (proxy_custom_time_array != null) {
                                            jQuery.each(proxy_custom_time_array, function (key, obj) {
                                                start_sliders(obj, event_length_minutes);
                                            });
                                        }

                                        if (cohorts_custom_time_array != null) {
                                            jQuery.each(cohorts_custom_time_array, function (key, obj) {
                                                start_sliders(obj, event_length_minutes);
                                            });
                                        }

                                        if (cgroup_custom_time_array != null) {
                                            jQuery.each(cgroup_custom_time_array, function (key, obj) {
                                                start_sliders(obj, event_length_minutes);
                                            });
                                        }
                                    });
                                </script>
                                <div id="custom_time_container">
                                    <div id="custom-time" data-visibility="hidden">
                                        <div id="custom-time-resize-handle">
                                            <div class="panel-head"><h3>Event Audience Time Override</h3></div>
                                            <div id="event_audience_override">
                                                <table class="table">
                                                    <tr>
                                                        <th class="slider-audience">Event Audience</th>
                                                        <th class="slider-text-time"></th>
                                                        <th class="slider-times">
                                                            <span class="left"><?php echo $start_event_time->format("g:i a");?></span>
                                                            <span class="right"><?php echo $end_event_time->format("g:i a");?></span>
                                                        </th>
                                                    </tr>
                                                    <?php
                                                    if (isset($cohort_times_o) && is_array($cohort_times_o) && !empty($cohort_times_o)) {
                                                        foreach ($cohort_times_o as $audience) {
                                                            if (isset($audience) && is_object($audience)) {
                                                                if ($is_draft) {
                                                                    $cohort_view = new Views_Event_Draft_Audience($audience);
                                                                } else {
                                                                    $cohort_view = new Views_Event_Audience($audience);
                                                                }
                                                                if (isset($cohort_view) && is_object($cohort_view)) {
                                                                    echo $cohort_view->renderTimeRow();
                                                                }
                                                            }
                                                        }
                                                    }

                                                    if (isset($cgroup_times_o) && is_array($cgroup_times_o) && !empty($cgroup_times_o)) {
                                                        foreach ($cgroup_times_o as $audience) {
                                                            if ($is_draft) {
                                                                $cgroup_view = new Views_Event_Draft_Audience($audience);
                                                            } else {
                                                                $cgroup_view = new Views_Event_Audience($audience);
                                                            }
                                                            if (isset($cgroup_view) && is_object($cgroup_view)) {
                                                                echo $cgroup_view->renderTimeRow();
                                                            }
                                                        }
                                                    }

                                                    if (isset($proxy_times_o) && is_array($proxy_times_o) && !empty($proxy_times_o)) {
                                                        foreach ($proxy_times_o as $audience) {
                                                            if ($is_draft) {
                                                                $proxy_view = new Views_Event_Draft_Audience($audience);
                                                            } else {
                                                                $proxy_view = new Views_Event_Audience($audience);
                                                            }
                                                            if (isset($proxy_view) && is_object($proxy_view)) {
                                                                echo $proxy_view->renderTimeRow();
                                                            }
                                                        }
                                                    }
                                                    ?>
                                                </table>
                                                <div style="clear: both;"></div>
                                                <div id="validation_group">
                                                    <button id="warn-overlap" class="btn btn-default">
                                                        <i class="fa fa-2x fa-square-o"></i>
                                                    </button>
                                                <span>
                                                    Warn me if there are overlapping times
                                                </span>
                                                </div>
                                                <input type="button" id="custom-time-close" value="Close" class="btn btn-primary">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>

                            <div class="control-group">
                                <?php if (!$is_draft) { ?>
                                    <tbody>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td colspan="2">
                                            <div id="related_events">
                                                <?php
                                                if (!(int) $PROCESSED["parent_id"]) {
                                                    require_once("modules/admin/events/api-related-events.inc.php");
                                                } else {
                                                    $query = "SELECT * FROM `".$tables["events"]."` WHERE `event_id` = ".$db->qstr($PROCESSED["parent_id"]);
                                                    $related_event = $db->GetRow($query);
                                                    if ($related_event) {
                                                        ?>
                                                        <div style="margin-top: 15px;">
                                                            <div style="width: 21%; position: relative; float: left;">
                                                                <span class="form-nrequired">Parent Event</span>
                                                            </div>
                                                            <div style="width: 72%; float: left;" id="related_events_list">
                                                                <ul class="menu">
                                                                    <li class="community" id="related_event_<?php echo $related_event["event_id"]; ?>" style="margin-bottom: 5px; width: 550px; height: 1.5em;">
                                                                        <a href="<?php echo ENTRADA_URL; ?>/admin/events?id=<?php echo $related_event["event_id"] ?>&section=edit">
                                                                            <div style="width: 300px; position: relative; float:left; margin-left: 15px;">
                                                                                <?php echo $related_event["event_title"]; ?>
                                                                            </div>
                                                                            <div style="float: left;">
                                                                                <?php
                                                                                echo date(DEFAULT_DATE_FORMAT, $related_event["event_start"]);
                                                                                ?>
                                                                            </div>
                                                                        </a>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                        <?php
                                                    }
                                                }
                                                ?>
                                            </div>
                                        </td>
                                    </tr>
                                    </tbody>
                                <?php } ?>
                            </div>
                            <h2>Time Release Options</h2>

                            <?php echo Entrada_Utilities::generate_calendars("viewable", "", true, false, ((isset($PROCESSED["release_date"])) ? $PROCESSED["release_date"] : 0), true, false, ((isset($PROCESSED["release_until"])) ? $PROCESSED["release_until"] : 0)); ?>

                            <div class="control-group">
                                <a class="btn" href="<?php echo ENTRADA_RELATIVE; ?>/admin/events<?php echo (($is_draft) ? "/drafts?section=edit&draft_id=".$event_info["draft_id"] : "" ); ?>">Cancel</a>
                                <div class="pull-right">
                                    <?php
                                    if (!$is_draft) {
                                        ?>
                                        <span class="content-small">After saving:</span>
                                        <select id="post_action" name="post_action">
                                            <option value="content"<?php echo (((!isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) && !$event_info["recurring_id"]) || ($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "content")) ? " selected=\"selected\"" : ""); ?>>Add content to this event</option>
                                            <option value="new"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "new") ? " selected=\"selected\"" : ""); ?>>Add a new event</option>
                                            <option value="copy"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "copy") ? " selected=\"selected\"" : ""); ?>>Duplicate this event</option>
                                            <option value="index"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "index") ? " selected=\"selected\"" : ""); ?>>Return to Manage Events</option>
                                        </select>
                                        <?php
                                    } else {
                                        ?>
                                        <input type="hidden" id="post_action" name="post_action" value="draft" />
                                        <?php
                                    }
                                    ?>
                                    <input type="submit" class="btn btn-primary" value="Save" />
                                </div>
                            </div>

                            <?php
                            if (isset($recurring_events) && $recurring_events) {
                                $sidebar_html = "<div class=\"content-small\">Please select which fields (if any) you would like to apply to related recurring events: </div>\n";
                                $sidebar_html .= "<div class=\"pad-left\">\n";
                                $sidebar_html .= "  <ul class=\"menu none\">\n";
                                $sidebar_html .= "      <li><label for=\"cascade_course\" class=\"checkbox\"><input type=\"checkbox\" value=\"course\" id=\"cascade_course\" onclick=\"toggleRecurringEventField(this.checked, jQuery(this).val())\" class=\"update-recurring-checkbox\" name=\"update_recurring_fields[]\" /> Course</label></li>\n";
                                $sidebar_html .= "      <li><label for=\"cascade_event_title\" class=\"checkbox\"><input type=\"checkbox\" value=\"event_title\" id=\"cascade_event_title\" onclick=\"toggleRecurringEventField(this.checked, jQuery(this).val())\" class=\"update-recurring-checkbox\" name=\"update_recurring_fields[]\" /> Event Title</label></li>\n";
                                $sidebar_html .= "      <li><label for=\"cascade_event_location\" class=\"checkbox\"><input type=\"checkbox\" value=\"event_location\" id=\"cascade_event_location\" onclick=\"toggleRecurringEventField(this.checked, jQuery(this).val())\" class=\"update-recurring-checkbox\" name=\"update_recurring_fields[]\" /> Event Location</label></li>\n";
                                $sidebar_html .= "      <li><label for=\"cascade_event_types\" class=\"checkbox\"><input type=\"checkbox\" value=\"event_types\" id=\"cascade_event_types\" onclick=\"toggleRecurringEventField(this.checked, jQuery(this).val())\" class=\"update-recurring-checkbox\" name=\"update_recurring_fields[]\" />" . $translate->_("Event Types") . "</label></li>\n";
                                $sidebar_html .= "      <li><label for=\"cascade_associated_faculty\" class=\"checkbox\"><input type=\"checkbox\" value=\"associated_faculty\" id=\"cascade_associated_faculty\" onclick=\"toggleRecurringEventField(this.checked, jQuery(this).val())\" class=\"update-recurring-checkbox\" name=\"update_recurring_fields[]\" /> Associated Faculty</label></li>\n";
                                $sidebar_html .= "      <li><label for=\"cascade_audience_visible\" class=\"checkbox\"><input type=\"checkbox\" value=\"audience_visible\" id=\"cascade_audience_visible\" onclick=\"toggleRecurringEventField(this.checked, jQuery(this).val())\" class=\"update-recurring-checkbox\" name=\"update_recurring_fields[]\" /> Audience Display</label></li>\n";
                                $sidebar_html .= "      <li><label for=\"cascade_associated_learners\" class=\"checkbox\"><input type=\"checkbox\" value=\"associated_learners\" id=\"cascade_associated_learners\" onclick=\"toggleRecurringEventField(this.checked, jQuery(this).val())\" class=\"update-recurring-checkbox\" name=\"update_recurring_fields[]\" /> Associated Learners</label></li>\n";
                            $sidebar_html .= "      <li><label for=\"cascade_attendance_required\" class=\"checkbox\"><input type=\"checkbox\" value=\"attendance_required\" id=\"cascade_attendance_required\" onclick=\"toggleRecurringEventField(this.checked, jQuery(this).val())\" class=\"update-recurring-checkbox\" name=\"update_recurring_fields[]\" /> Event Attendance</label></li>\n";
                                $sidebar_html .= "      <li><label for=\"cascade_time_release\" class=\"checkbox\"><input type=\"checkbox\" value=\"time_release\" id=\"cascade_time_release\" onclick=\"toggleRecurringEventField(this.checked, jQuery(this).val())\" class=\"update-recurring-checkbox\" name=\"update_recurring_fields[]\" /> Time Release</label></li>\n";
                                $sidebar_html .= "  </ul>\n";
                                $sidebar_html .= "</div>\n";
                                $sidebar_html .= "<div><strong><a href=\"#recurringEvents\" data-toggle=\"modal\" data-target=\"#recurringEvents\"><i class=\"icon-edit\"></i> <span id=\"recurring_events_count\">".(isset($_POST["recurring_event_ids"]) && @count($_POST["recurring_event_ids"]) ? @count($_POST["recurring_event_ids"]) : @count($recurring_events))."</span> Recurring Events Selected</a></strong></div>";
                                new_sidebar_item("Recurring Events", $sidebar_html, "recurring-events-sidebar");
                                ?>
                                <style type="text/css">
                                    #recurring-events-sidebar.fixed {
                                        position: fixed;
                                        top: 20px;
                                        z-index: 1;
                                        width: 22%;
                                        max-width: 313px;
                                        min-width: 206px;
                                    }
                                </style>
                                <script type="text/javascript">
                                    var shown = false;
                                    jQuery(document).ready(function () {
                                        var top = jQuery("#recurring-events-sidebar").offset().top - parseFloat(jQuery("#recurring-events-sidebar").css("marginTop").replace(/auto/, 100)) + 320;
                                        jQuery(window).scroll(function (event) {
                                            var y = jQuery(this).scrollTop();
                                            if (y >= top) {
                                                jQuery("#recurring-events-sidebar").addClass("fixed");
                                            } else {
                                                jQuery("#recurring-events-sidebar").removeClass("fixed");
                                            }
                                        });
                                        if (jQuery(this).scrollTop() >= top) {
                                            jQuery("#recurring-events-sidebar").addClass("fixed");
                                        }
                                    });

                                    function toggleRecurringEventField(checked, fieldname) {
                                        if (checked && jQuery("#update_" + fieldname).length < 1) {
                                            jQuery("#editEventForm").append("<input type=\"hidden\" name=\"update_recurring_fields[]\" value=\"" + fieldname + "\" id=\"update_\"" + fieldname + "\" />");
                                        } else if (!checked && jQuery("#update_" + fieldname).length >= 1) {
                                            jQuery("#update_" + fieldname).remove();
                                        }
                                    }
                                </script>
                                <div id="recurringEvents" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="Select Associated Recurring Events" aria-hidden="true">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                        <h3>Associated Recurring Events</h3>
                                    </div>
                                    <div class="modal-body">
                                        <div id="display-generic-box" class="alert alert-block alert-info">
                                            <ul>
                                                <li>
                                                    Please select which of the following related recurring events you would like to apply the selected changes to:
                                                </li>
                                            </ul>
                                        </div>
                                        <?php
                                        foreach ($recurring_events as $recurring_event) {
                                            $recurring_event = $recurring_event->toArray();
                                            ?>
                                            <div class="row-fluid">
                                                <span class="span1">
                                                    &nbsp;
                                                </span>
                                                <span class="span1">
                                                    <input type="checkbox" id="recurring_event_<?php echo $recurring_event["event_id"] ?>" class="recurring_events" onclick="jQuery("#recurring_events_count").html(jQuery(".recurring_events:checked").length)" name="recurring_event_ids[]" value="<?php echo $recurring_event["event_id"]; ?>"<?php echo (!isset($_POST["recurring_event_ids"]) || in_array($recurring_event["event_id"], $_POST["recurring_event_ids"]) ? " checked=\"checked\"" : ""); ?> />
                                                </span>
                                                <label class="span10" for="recurring_event_<?php echo $recurring_event["event_id"] ?>">
                                                    <strong class="space-right">
                                                        <?php echo html_encode($recurring_event["event_title"]); ?>
                                                    </strong>
                                                    [<span class="content-small"><?php echo html_encode(date(DEFAULT_DATE_FORMAT, $recurring_event["event_start"])); ?></span>]
                                                </label>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                    <div class="modal-footer">
                                        <a href="#" class="btn" data-dismiss="modal">Close</a>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>
                        </form>

                        <script type="text/javascript">
                            jQuery(document).ready(function ($) {
                                $("#eventtype_ids").advancedSearch({
                                    close_after_select: true,
                                    resource_url: ENTRADA_URL,
                                    filters: {
                                        event_types: {
                                            label: "<?php echo $translate->_("Event Types"); ?>",
                                            data_source: event_types,
                                            mode: "radio",
                                            set_button_text_to_selected_option: false
                                        }
                                    },
                                    no_results_text: "<?php echo $translate->_("No Event Types found matching the search criteria"); ?>",
                                    parent_form: $("#editEventForm"),
                                    width: 300
                                });

                                var popover_options = {
                                    animation: false,
                                    container: "body",
                                    selector: "[rel=\"popover\"]",
                                    html: true,
                                    trigger: "hover",
                                    placement: "left",
                                    content: function () {
                                        var target_id = $(this).attr("data-id");
                                        var index;

                                        for (index = 0; index < event_types.length; index++) {
                                            if (event_types[index]["target_id"] == target_id) {
                                                break;
                                            }
                                        }

                                        return event_types[index]["description"];
                                    }
                                };

                                $("#eventtype_ids").click(function (e) {
                                    $.each($(".search-filter-item"), function (index, value) {
                                        $(this).attr("rel", "popover");
                                    });

                                    $("#editEventForm").on("mouseenter", ".search-filter-item", function (e) {
                                        e.stopPropagation();

                                        $(".popover").remove();
                                        $("[rel=\"popover\"]").popover(popover_options);
                                        $(this).popover("show");
                                    });

                                    $("#editEventForm").on("mouseleave", ".search-filter-item", function (e) {
                                        e.stopPropagation();

                                        if (!$(".search-filter-item:hover").length) {
                                            setTimeout(function () {
                                                if (!$(".popover:hover").length) {
                                                    $(".popover").remove();
                                                }
                                            }, 300);
                                        }
                                    });

                                    $("#editEventForm").on("click", ".search-filter-item", function (e) {
                                        $(".popover").remove();
                                    });
                                });

                                $("body").on("mouseleave", ".popover", function (e) {
                                    e.stopPropagation();

                                    setTimeout(function () {
                                        if (!$(".search-filter-item:hover").length) {
                                            $(".popover").remove();
                                        }
                                    }, 300);
                                });

                                $("body").on("click", ".popover", function (e) {
                                    $(".popover").remove();
                                });

                                $("#editEventForm").on("change", ".search-target-input-control", function () {
                                    if ($(this).is(":checked")) {
                                        var li = $(document.createElement("li")).attr({id: "type_" + this.value}).html($(this).attr("data-label"));
                                        var a = $(document.createElement("a")).attr({href: "#", onclick: "$(this).up().remove(); cleanupList(); return false;"}).addClass("remove");
                                        var img = $(document.createElement("img")).attr({src: ENTRADA_URL + "/images/action-delete.gif"});
                                        var span = $(document.createElement("span")).addClass("duration_segment_container").html("Duration: ");
                                        var input = $(document.createElement("input")).attr({type: "text", name: "duration_segment[]", onchange: "cleanupList();", value: "60"}).addClass("input-mini duration_segment");

                                        a.append(img);
                                        span.append(input).append(" minutes");
                                        li.append(a).append(span);

                                        $("#duration_container").append(li);

                                        cleanupList();

                                        $("#" + $(this).attr("data-filter") + "_" + $(this).val()).remove();
                                    }
                                });
                            });

                            var multiselect = [];
                            var audience_type;

                            function showMultiSelect() {
                                if ($('display-notice-box')) {
                                    $('display-notice-box').hide();
                                }

                                $$('select_multiple_container').invoke('hide');
                                audience_type = $F('audience_type');
                                course_id = $F('course_id');
                                var cohorts = $('event_audience_cohorts').value;
                                var course_groups = $('event_audience_course_groups').value;
                                var students = $('event_audience_students').value;

                                if (multiselect[audience_type]) {
                                    multiselect[audience_type].container.show();
                                } else {
                                    if (audience_type) {
                                        new Ajax.Request("<?php echo ENTRADA_RELATIVE; ?>/admin/events?section=api-audience-selector", {
                                            evalScripts : true,
                                            parameters: {
                                                "options_for" : audience_type,
                                                "course_id" : course_id,
                                                "event_id" : "<?php echo $EVENT_ID; ?>",
                                                "event_audience_cohorts" : cohorts,
                                                "event_audience_course_groups" : course_groups,
                                                "event_audience_students" : students
                                            },
                                            method: "post",
                                            onLoading: function() {
                                                $("options_loading").show();
                                            },
                                            onSuccess: function(response) {
                                                if (response.responseText) {
                                                    $("options_container").insert(response.responseText);

                                                    if ($(audience_type + "_options")) {

                                                        $(audience_type + "_options").addClassName("multiselect-processed");

                                                        multiselect[audience_type] = new Control.SelectMultiple("event_audience_"+audience_type, audience_type + "_options", {
                                                            checkboxSelector: "div.audience table.select_multiple_table tr td input[type=checkbox]",
                                                            nameSelector: "div.audience table.select_multiple_table tr td.select_multiple_name label",
                                                            filter: audience_type + "_select_filter",
                                                            resize: audience_type + "_scroll",
                                                            afterCheck: function(element) {
                                                                var tr = $(element.parentNode.parentNode);
                                                                tr.removeClassName("selected");

                                                                if (element.checked) {
                                                                    tr.addClassName("selected");
                                                                    addAudience(element.id, audience_type);
                                                                } else {
                                                                    removeAudience(element.id, audience_type);
                                                                }
                                                            }
                                                        });

                                                        if ($(audience_type + "_cancel")) {
                                                            $(audience_type + "_cancel").observe("click", function(event) {
                                                                this.container.hide();

                                                                $("audience_type").options.selectedIndex = 0;
                                                                $("audience_type").show();

                                                                return false;
                                                            }.bindAsEventListener(multiselect[audience_type]));
                                                        }

                                                        if ($(audience_type + "_close")) {
                                                            $(audience_type + "_close").observe("click", function(event) {
                                                                this.container.hide();

                                                                $("audience_type").clear();

                                                                return false;
                                                            }.bindAsEventListener(multiselect[audience_type]));
                                                        }

                                                        multiselect[audience_type].container.show();
                                                    }
                                                } else {
                                                    new Effect.Highlight("audience_type", {startcolor: "#FFD9D0", restorecolor: "true"});
                                                    new Effect.Shake("audience_type");
                                                }
                                            },
                                            onError: function() {
                                                alert("There was an error retrieving the requested audience. Please try again.");
                                            },
                                            onComplete: function() {
                                                $("options_loading").hide();
                                            }
                                        });
                                    }
                                }
                                return false;
                            }

                            function addAudience(element, audience_id) {
                                if (!$("audience_" + element)) {
                                    //manipulate data into the correct format
                                    //course group being referred to as "group_id" is confusing and might want to be reworked, but would break existing data in the database
                                    var data = element.split("_");
                                    var type = data[0];
                                    var id  = data[1];
                                    var type_slider = "";

                                    if (type == "group") {
                                        type = "cohort";
                                    }

                                    if (audience_id == "groups") {
                                        audience_id = "cohorts";
                                    }

                                    if (type == "student") {
                                        type_slider = "proxy_id";
                                    } else if (type == "cgroup") {
                                        type_slider = "group_id";
                                    } else {
                                        type_slider = type;
                                    }

                                    var title = $($(type + "_" + id).value + "_label").innerHTML;
                                    var audience_html = "<li class=\"" + (audience_id == "students" ? "user" : "group") + "\" id=\"audience_" + type + "_" + id + "\" data-type=\"" + type_slider + "\" data-value=\"" + id + "\" style=\"cursor: move;\">";
                                    audience_html += title  + "<span class=\"time\"></span>";
                                    audience_html += "<span class=\"badge badge-time-off time-badge\">";
                                    audience_html += "<i class=\"icon-time icon-white custom_time_icon\"></i>";
                                    audience_html += "</span>";
                                    audience_html += "<img src=\"<?php echo ENTRADA_RELATIVE; ?>/images/action-delete.gif\" onclick=\"removeAudience('" + type + "_" + id + "', '" + audience_id + "');\" class=\"list-cancel-image\" />";
                                    audience_html += "</li>";
                                    $("audience_list").innerHTML += audience_html;
                                    $$("#audience_list div").each(function (e) { e.hide(); });

                                    //generate HTML for Event Audience Override
                                    var image_src = "../images/list-" + (audience_id == "students" ? "user" : "community") + ".gif";
                                    var span_class = (audience_id == "students" ? "individual" : "group");

                                    var sliderHTML = "<tr>";
                                    sliderHTML += "<td>";
                                    sliderHTML += "<img src=\"" + image_src + "\">";
                                    sliderHTML += "<span class=\"" + span_class + "\">" + title + "</span>";
                                    sliderHTML += "</td>";
                                    sliderHTML += "<td class=\"slider-text-time\" id=\"" + type_slider + "_time_" + id + "\"></td>";
                                    sliderHTML += "<td>";
                                    sliderHTML += "<div class=\"slider-range\" data-id=\"" + id + "\" data-type=\"" + type_slider + "\"></div>";
                                    sliderHTML += "</td>";
                                    sliderHTML += "</tr>";

                                    //add new item to Event Audience Override table
                                    jQuery("#event_audience_override .table tbody").append(sliderHTML);

                                    //create object for javascript array
                                    var object = {
                                        audience_type       : type_slider,
                                        audience_value      : id,
                                        custom_time         : 0,
                                        custom_time_start   : 0,
                                        custom_time_end     : 0
                                    };

                                    //add element to javascript array at the key "id"
                                    if (object.audience_type == "proxy_id") {
                                        proxy_custom_time_array[id] = object;
                                        start_sliders(object, event_length_minutes);
                                    } else if (object.audience_type == "group_id") {
                                        cgroup_custom_time_array[id] = object;
                                        start_sliders(object, event_length_minutes);
                                    } else {
                                        cohorts_custom_time_array[id] = object;
                                        start_sliders(object, event_length_minutes);
                                    }
                                    Sortable.destroy("audience_list");
                                    Sortable.create("audience_list");
                                }
                            }

                            function removeAudience(element, audience_id) {
                                var data = element.split("_");
                                var type = data[0];
                                var id = data[1];
                                var type_slider = "";

                                if (type == "group") {
                                    type = "cohort";
                                }

                                if (audience_id == "groups") {
                                    audience_id = "cohorts";
                                }

                                if (type == "student") {
                                    type_slider = "proxy_id";
                                } else if (type == "cgroup") {
                                    type_slider = "group_id";
                                } else {
                                    type_slider = type;
                                }

                                //removes element from javascript array at the key "id"
                                if (type_slider == "proxy_id") {
                                    delete proxy_custom_time_array[id];
                                } else if (type_slider == "group_id") {
                                    delete cgroup_custom_time_array[id];
                                } else {
                                    delete cohorts_custom_time_array[id];
                                }

                                //removes event Audience Override HTML
                                var event_audience_html = jQuery(".slider-range[data-type=" + type_slider + "][data-id=" + id + "]").parent().parent();
                                event_audience_html.remove();

                                $("audience_" + type + "_" + id).remove();
                                Sortable.destroy("audience_list");
                                Sortable.create("audience_list");
                                if ($(element)) {
                                    $(element).checked = false;
                                }
                                if (multiselect[audience_type]) {
                                    var tr_element = $(type + "_" + id).parentNode.parentNode;
                                    tr_element.removeClassName("selected");
                                }
                                var audience = $("event_audience_" + audience_id).value.split(",");
                                for (var i = 0; i < audience.length; i++) {
                                    if (audience[i] == type + "_" + id) {
                                        audience.splice(i, 1);
                                        break;
                                    }
                                }
                                $("event_audience_" + audience_id).value = audience.join(",");
                            }

                            function addContact(element, contact_id) {
                                if (!$("contact_"+element)) {
                                    $('contact_list').innerHTML += '<li class="' + (contact_id == 'faculty' ? 'user' : 'group') + '" id="contact_'+element+'" style="cursor: move;">'+$($(element).value+'_label').innerHTML+'<img src="<?php echo ENTRADA_RELATIVE; ?>/images/action-delete.gif" onclick="removeContact(\''+element+'\', \''+contact_id+'\');" class="list-cancel-image" /></li>';
                                    $$("#contact_list div").each(function (e) { e.hide(); });

                                    Sortable.destroy("contact_list");
                                    Sortable.create("contact_list");
                                }
                            }

                            function removeContact(element, contact_id) {
                                $("contact_" + element).remove();
                                Sortable.destroy("contact_list");
                                Sortable.create("contact_list");
                                if ($(element)) {
                                    $(element).checked = false;
                                }
                                if (multiselect[contact_type]) {
                                    var tr_element = $(element).parentNode.parentNode;
                                    tr_element.removeClassName("selected");
                                }

                                var contact = $("event_contact_" + contact_id).value.split(",");

                                for (var i = 0; i < contact.length; i++) {
                                    if (contact[i] == element) {
                                        contact.splice(i, 1);
                                        break;
                                    }
                                }
                                $("event_contact_" + contact_id).value = contact.join(",");
                            }

                            function removeRelatedEvent(event_id) {
                                var updater = new Ajax.Updater("related_events", "<?php echo ENTRADA_URL."/admin/events?section=api-related-events";?>",{
                                    evalScripts: true,
                                    method:"post",
                                    parameters: {
                                        "ajax" : 1,
                                        "course_id" : $("course_id").value,
                                        "event_id" : "<?php echo $EVENT_ID; ?>",
                                        "remove_id" : event_id,
                                        "related_event_ids_clean" : $F("related_event_ids_clean")
                                    },
                                    onLoading: function (transport) {
                                        $("related_events_list").innerHTML = "<br /><br /><span class=\"content-small\" style=\"align: center;\">Loading... <img src=\"<?php echo ENTRADA_URL; ?>/images/indicator.gif\" style=\"vertical-align: middle;\" /></span>";
                                    },
                                    onComplete: function (transport) {
                                        generateEventAutocomplete();
                                    }
                                });
                            }

                            function addRelatedEvent(event_id) {
                                var updater = new Ajax.Updater("related_events", "<?php echo ENTRADA_URL."/admin/events?section=api-related-events";?>",{
                                    evalScripts: true,
                                    method:"post",
                                    parameters: {
                                        "ajax" : 1,
                                        "course_id" : $("course_id").value,
                                        "event_id" : "<?php echo $EVENT_ID; ?>",
                                        "add_id" : event_id,
                                        "related_event_ids_clean" : $F("related_event_ids_clean")
                                    },
                                    onLoading: function (transport) {
                                        $("related_events_list").innerHTML = "<br /><br /><span class=\"content-small\" style=\"align: center;\">Loading... <img src=\"<?php echo ENTRADA_URL; ?>/images/indicator.gif\" style=\"vertical-align: middle;\" /></span>";
                                    },
                                    onComplete: function (transport) {
                                        generateEventAutocomplete();
                                    }
                                });
                            }

                            var events_updater = null;
                            function generateEventAutocomplete() {
                                events_updater = new Ajax.Autocompleter("related_event_title", "events_autocomplete",
                                    "<?php echo ENTRADA_URL; ?>/admin/events?section=api-events-by-title&parent_id=" + $("parent_id").value + "&course_id=" + $("course_id").options[$("course_id").selectedIndex].value,
                                    {
                                        frequency: 0.2,
                                        minChars: 1,
                                        afterUpdateElement: function (text, li) {
                                            addRelatedEvent(li.id);
                                        }
                                    });
                            }

                            function selectEventAudienceOption(type) {
                                if (type == "custom" && !jQuery("#event_audience_type_custom_options").is(":visible")) {
                                    jQuery("#event_audience_type_custom_options").slideDown();
                                } else if (type != "custom" && jQuery("#event_audience_type_custom_options").is(":visible")) {
                                    jQuery("#event_audience_type_custom_options").slideUp();
                                }
                            }

                            function selectEventContactOption(type) {
                                if (type == "custom" && !jQuery("#event_contact_type_custom_options").is(":visible")) {
                                    jQuery("#event_contact_type_custom_options").slideDown();
                                } else if (type != "custom" && jQuery("#event_contact_type_custom_options").is(":visible")) {
                                    jQuery("#event_contact_type_custom_options").slideUp();
                                }
                            }

                            function updateAudienceOptions() {
                                if ($F("course_id") > 0)  {

                                    var selectedCourse = "";

                                    var currentLabel = $("course_id").options[$("course_id").selectedIndex].up().readAttribute("label");

                                    if (currentLabel != selectedCourse) {
                                        selectedCourse = currentLabel;
                                        var cohorts = ($("event_audience_cohorts") ? $("event_audience_cohorts").getValue() : "");
                                        var course_groups = ($("event_audience_course_groups") ? $("event_audience_course_groups").getValue() : "");
                                        var students = ($("event_audience_students") ? $("event_audience_students").getValue() : "");

                                        $("audience-options").show();
                                        $("audience-options").update("<tr><td colspan=\"2\">&nbsp;</td><td><div class=\"content-small\" style=\"vertical-align: middle\"><img src=\"<?php echo ENTRADA_RELATIVE; ?>/images/indicator.gif\" width=\"16\" height=\"16\" alt=\"Please Wait\" title=\"\" style=\"vertical-align: middle\" /> Please wait while <strong>audience options</strong> are being loaded ... </div></td></tr>");

                                        new Ajax.Updater("audience-options", "<?php echo ENTRADA_RELATIVE; ?>/admin/events?section=api-audience-options", {
                                            evalScripts : true,
                                            parameters : {
                                                ajax : 1,
                                                course_id : $F("course_id"),
                                                event_audience_students: students,
                                                event_audience_course_groups: course_groups,
                                                event_audience_cohorts: cohorts
                                            },
                                            onSuccess : function (response) {
                                                if (response.responseText == "") {
                                                    $("audience-options").update("");
                                                    $("audience-options").hide();
                                                }
                                            },
                                            onFailure : function () {
                                                $("audience-options").update("");
                                                $("audience-options").hide();
                                            }
                                        });
                                    }
                                } else {
                                    $("audience-options").update("");
                                    $("audience-options").hide();
                                }
                            }
                        </script>
                        <?php
                        break;
                }
            }
        } else {
            add_error("In order to edit a event you must provide a valid event identifier. The provided ID does not exist in this system.");

            echo display_error();

            application_log("notice", "Failed to provide a valid event identifer when attempting to edit a event.");
        }
    } else {
        add_error("In order to edit a event you must provide the events identifier.");

        echo display_error();

        application_log("notice", "Failed to provide event identifer when attempting to edit a event.");
    }
}
