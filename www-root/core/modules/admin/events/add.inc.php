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
 * This file is used to add events to the entrada.events table.
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
} elseif (!$ENTRADA_ACL->amIAllowed("event", "create", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/eventtypes_list.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/AutoCompleteList.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.iris.min.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
   	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/color-picker.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
    $HEAD[] = "<script type=\"text/javascript\">var EVENT_COLOR_PALETTE = ".json_encode($translate->_("event_color_palette")).";</script>\n";
	echo "<script language=\"text/javascript\">var DELETE_IMAGE_URL = '".ENTRADA_URL."/images/action-delete.gif';</script>";

	$PROCESSED["associated_faculty"] = array();
	$PROCESSED["event_audience_type"] = "course";
	$PROCESSED["associated_cohort_ids"] = array();
	$PROCESSED["associated_cgroup_ids"] = array();
	$PROCESSED["associated_proxy_ids"] = array();
	$PROCESSED["event_types"] = array();

    /* Creates arrays used for model loading of audience */
    $cohort_times_o = array();
    $cohort_times_a = array();
    $proxy_times_o  = array();
    $proxy_times_a  = array();
    $cgroup_times_o = array();
    $cgroup_times_a = array();

	if (isset($_GET["mode"]) && $_GET["mode"] == "draft") {
		if (isset($_GET["draft_id"]) && (int) $_GET["draft_id"] != 0) {
			$draft_id = (int) $_GET["draft_id"];

			$query = "SELECT `draft_id`, `status`, `name` FROM `drafts` WHERE `draft_id` = ".$db->qstr($draft_id);
			$draft_info = $db->GetAssoc($query);

			if (!empty($draft_info) && array_key_exists($draft_id, $draft_info)) {
				switch ($draft_info[$draft_id]) {
					case "approved" :
						add_error("The specified draft has been approved for importation. To add a new event the draft must be <a href=\"".ENTRADA_URL."/admin/events/drafts?section=status&action=reopen&draft_id=".$draft_id."\">reopened</a>.");
					case "open" :
					default :
						$tables["events"]		= "draft_events";
						$tables["audience"]		= "draft_audience";
						$tables["contacts"]		= "draft_contacts";
						$tables["event_types"]	= "draft_eventtypes";
						$is_draft = true;
                        $model = new Models_Event_Draft_Event_Audience();
					break;
				}
				$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/events/drafts", "title" => "Learning Event Draft Schedule");
                $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/events/drafts?section=edit&draft_id=".$draft_id, "title" => $draft_info[$draft_id]["name"]);
			} else {
				add_error("The specified draft id does not exist.");
			}

		} else {
			add_error("A draft id has not been specified.");
		}
	} else {
		$tables["events"]		= "events";
		$tables["audience"]		= "event_audience";
		$tables["contacts"]		= "event_contacts";
		$tables["objectives"]	= "event_objectives";
		$tables["topics"]		= "event_topics";
		$tables["links"]		= "event_links";
		$tables["files"]		= "event_files";
		$tables["event_types"]	= "event_eventtypes";

        $model = new Models_Event_Audience();
	}

    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/events?".replace_query(array("section" => "add")), "title" => "Adding Event");
	echo "<h1>Adding Event</h1>\n";

	// Error Checking
	switch($STEP) {
		case 2 :
			/**
			 * Required field "course_id" / Course
			 */
			if ((isset($_POST["course_id"])) && ($course_id = clean_input($_POST["course_id"], array("int")))) {
				$query	= "	SELECT * FROM `courses`
							WHERE `course_id` = ".$db->qstr($course_id)."
							AND (`course_active` = '1')";
				$result	= $db->GetRow($query);
				if ($result) {
					if ($ENTRADA_ACL->amIAllowed(new EventResource(null, $course_id, $ENTRADA_USER->getActiveOrganisation()), "create")) {
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

			/**
			 * Required field "event_title" / Event Title.
			 */
			if ((isset($_POST["event_title"])) && ($event_title = clean_input($_POST["event_title"], array("notags", "trim")))) {
				$PROCESSED["event_title"] = $event_title;
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

									if ($duration < LEARNING_EVENT_MIN_DURATION) {
										add_error("The duration of <strong>".html_encode($eventtype_title)."</strong> (".numeric_suffix(($order + 1))." <strong>" . $translate->_("Event Type") . "</strong> entry) must be greater than or equal to ".LEARNING_EVENT_MIN_DURATION.".");
									}
								} else {
									$duration = 0;

									add_error("The duration of <strong>".html_encode($eventtype_title)."</strong> (".numeric_suffix(($order + 1))." <strong>" . $translate->_("Event Type") . "</strong> entry) was not provided.");
								}

								$PROCESSED["event_types"][] = array($eventtype_id, $duration, $eventtype_title);
							} else {
								add_error("One of the <strong>" . $translate->_("Event Types") . "</strong> you specified was invalid.");
							}
						}
					}
				}
			}

			if (!isset($PROCESSED["event_types"]) || !is_array($PROCESSED["event_types"]) || empty($PROCESSED["event_types"])) {
				add_error("The <strong>" . $translate->_("Event Types") . "</strong> field is required.");
			}
            $PROCESSED["recurring_events"] = array();
			if (isset($_POST["recurring_event_start"]) && is_array($_POST["recurring_event_start"]) && !empty($_POST["recurring_event_start"])) {
                foreach ($_POST["recurring_event_start"] as $key => $event_start) {
                    $tmp_recurring_event = array();
					if (isset($_POST["recurring_event_start_time"][$key]) && $tmp_input = clean_input($_POST["recurring_event_start_time"][$key], array("trim", "striptags"))) {
						$time = $tmp_input;
					} else {
						$time = "00:00";
					}
					$time = strtotime($event_start . " " . $time);
					if ($time) {
						$recurring_event_date = $time;
                        $tmp_recurring_event["event_start"] = $time;
					} else {
						add_error("One of the <strong>recurring events</strong> did not have a valid start date, please fill out a Event Start for <strong>Event ".($key+1)."</strong> under the Recurring Events now.");
					}
                    if (isset($_POST["recurring_event_title"][$key]) && ($recurring_event_title = clean_input($_POST["recurring_event_title"][$key], array("notags", "trim")))) {
                        $event_finish = $recurring_event_date;
                        $event_duration = 0;
                        foreach($PROCESSED["event_types"] as $event_type) {
                            $event_finish += $event_type[1]*60;
                            $event_duration += $event_type[1];
                        }
                        $tmp_recurring_event["event_title"] = $recurring_event_title;
                        $tmp_recurring_event["event_finish"] = $event_finish;
                        $tmp_recurring_event["event_duration"] = $event_duration;
                    } else {
                        add_error("One of the <strong>recurring events</strong> did not have a valid title, please fill out a title for <strong>Event ".($key+1)."</strong> under the Recurring Events now.");
                    }
                    $PROCESSED["recurring_events"][] = $tmp_recurring_event;
                }
            }

            if (isset($_POST["parent_event"]) && $tmp_input = clean_input($_POST["parent_event"], "int")) {
                $PROCESSED["parent_event"] = "1";
            } else {
                $PROCESSED["parent_event"] = "0";
            }

			/**
			 * Non-required field "associated_faculty" / Associated Faculty (array of proxy ids).
			 * This is actually accomplished after the event is inserted below.
			 */
			if ((isset($_POST["associated_faculty"]))) {
				$associated_faculty = explode(",", $_POST["associated_faculty"]);
				foreach($associated_faculty as $contact_order => $proxy_id) {
					if ($proxy_id = clean_input($proxy_id, array("trim", "int"))) {
						$PROCESSED["associated_faculty"][(int) $contact_order] = $proxy_id;
						$PROCESSED["contact_role"][(int) $contact_order] = $_POST["faculty_role"][(int)$contact_order];
						$PROCESSED["display_role"][$proxy_id] = $_POST["faculty_role"][(int) $contact_order];
					}
				}
			}

			if (isset($_POST["event_audience_type"]) && ($tmp_input = clean_input($_POST["event_audience_type"], "alphanumeric"))) {
				$PROCESSED["event_audience_type"] = $tmp_input;
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
                        //if not an array then it's a JSON object that needs to be typecast and decoded
                        if (!is_array($times)) {
                            $times = (array) json_decode($_POST["event_audience_cohorts_custom_times"]);
                        }

                        if (is_array($times) && !is_array($times[0])) {
                            $times = (array) json_decode($times[0]);
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
                                    $type_cast_values["start_time_offset"] = $type_cast_values["custom_time_start"] - $PROCESSED["event_start"];
                                    $type_cast_values["end_time_offset"]   = $type_cast_values["custom_time_end"] - $PROCESSED["event_start"];
                                } else {
                                    $type_cast_values["start_time_offset"] = 0;
                                    $type_cast_values["end_time_offset"]   = 0;
                                }
                                $cohort_times[$time["audience_value"]]     = $type_cast_values;
                            }
                        }
                    }

                    if ((isset($_POST["event_audience_cohorts"]))) {
                        $associated_audience = explode(",", $_POST["event_audience_cohorts"]);
                        if ((isset($associated_audience)) && (is_array($associated_audience)) && (count($associated_audience))) {
                            foreach($associated_audience as $audience_id) {
                                if (strpos($audience_id, "cohort") !== false) {
                                    if ($group_id = clean_input(preg_replace("/[a-z_]/", "", $audience_id), array("trim", "int"))) {
                                        $query = "	SELECT *
                                                    FROM `groups`
                                                    WHERE `group_id` = ".$db->qstr($group_id)."
                                                    AND (`group_type` = 'cohort' OR `group_type` = 'course_list')
                                                    AND `group_active` = 1";
                                        $result	= $db->GetRow($query);
                                        if ($result) {
                                            $audience_type  = "cohort";
                                            $audience_value = $group_id;
                                            $PROCESSED["associated_cohort_ids"][] = $audience_value;
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

                                            $audience_arr = array(
                                                "audience_type"     => $audience_type,
                                                "audience_value"    => $audience_value,
                                                "custom_time"       => $custom_time,
                                                "custom_time_start" => $custom_time_start,
                                                "custom_time_end"   => $custom_time_end,
                                                "updated_date"      => time(),
                                                "updated_by"        => $ENTRADA_USER->getID()
                                            );
                                            $audience = new $model($audience_arr);

                                            if (isset($cohort_times_o) && is_array($cohort_times_o) && !array_key_exists($audience_value, $cohort_times_o)) {
                                                $cohort_times_o[$audience_value] = $audience;
                                            }
                                        }
                                    }
                                }
                            }
                        }
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

                        if (is_array($times) && !is_array($times[0])) {
                            $times = (array) json_decode($times[0]);
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
                                    $type_cast_values["start_time_offset"] = $type_cast_values["custom_time_start"] - $PROCESSED["event_start"];
                                    $type_cast_values["end_time_offset"] = $type_cast_values["custom_time_end"] - $PROCESSED["event_start"];
                                } else {
                                    $type_cast_values["start_time_offset"] = 0;
                                    $type_cast_values["end_time_offset"] = 0;
                                }

                                $cgroup_times[$time["audience_value"]] = $type_cast_values;
                            }
                        }
                    }

                    if (isset($_POST["event_audience_course_groups"]) && isset($PROCESSED["course_id"]) && $PROCESSED["course_id"]) {
                        $associated_audience = explode(",", $_POST["event_audience_course_groups"]);
                        if ((isset($associated_audience)) && (is_array($associated_audience)) && (count($associated_audience))) {
                            foreach($associated_audience as $audience_id) {
                                if (strpos($audience_id, "cgroup") !== false) {
                                    if ($cgroup_id = clean_input(preg_replace("/[a-z_]/", "", $audience_id), array("trim", "int"))) {
                                        $query = "	SELECT *
                                                    FROM `course_groups`
                                                    WHERE `cgroup_id` = ".$db->qstr($cgroup_id)."
                                                    AND `course_id` = ".$db->qstr($PROCESSED["course_id"])."
                                                    AND (`active` = '1' OR `course_id` = ".$db->qstr($event_info["course_id"]).")";
                                        $result	= $db->GetRow($query);
                                        if ($result) {
                                            $audience_type  = "group_id";
                                            $audience_value = $cgroup_id;
                                            $PROCESSED["associated_cgroup_ids"][] = $audience_value;

                                            $audience = new Models_Event_Audience(array(
                                                "audience_type"     => $audience_type,
                                                "audience_value"    => $audience_value,
                                                "custom_time"       => $cgroup_times[$cgroup_id]["custom_time"],
                                                "custom_time_start" => $cgroup_times[$cgroup_id]["custom_time_start"],
                                                "custom_time_end"   => $cgroup_times[$cgroup_id]["custom_time_end"],
                                                "updated_date"      => time(),
                                                "updated_by"        => $ENTRADA_USER->getID()
                                            ));
                                            if (isset($cgroup_times_o) && is_array($cgroup_times_o) && !array_key_exists($audience_value, $cgroup_times_o)) {
                                                $cgroup_times_o[$audience_value] = $audience;
                                            }
                                        }
                                    }
                                }
                            }
                        }
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

                        if (is_array($times) && !is_array($times[0])) {
                            $times = (array) json_decode($times[0]);
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
                                    $type_cast_values["start_time_offset"] = $type_cast_values["custom_time_start"] - $PROCESSED["event_start"];
                                    $type_cast_values["end_time_offset"] = $type_cast_values["custom_time_end"] - $PROCESSED["event_start"];
                                } else {
                                    $type_cast_values["start_time_offset"] = 0;
                                    $type_cast_values["end_time_offset"] = 0;
                                }

                                $proxy_times[$time["audience_value"]] = $type_cast_values;
                            }
                        }
                    }

                    if ((isset($_POST["event_audience_students"]))) {
                        $associated_audience = explode(",", $_POST["event_audience_students"]);
                        if ((isset($associated_audience)) && (is_array($associated_audience)) && (count($associated_audience))) {
                            foreach($associated_audience as $audience_id) {
                                if (strpos($audience_id, "student") !== false) {
                                    if ($proxy_id = clean_input(preg_replace("/[a-z_]/", "", $audience_id), array("trim", "int"))) {
                                        $query = "	SELECT a.*
                                                    FROM `".AUTH_DATABASE."`.`user_data` AS a
                                                    LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
                                                    ON a.`id` = b.`user_id`
                                                    WHERE a.`id` = ".$db->qstr($proxy_id)."
                                                    AND b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
                                                    AND b.`account_active` = 'true'
                                                    AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
                                                    AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")";
                                        $result	= $db->GetRow($query);
                                        if ($result) {
                                            $audience_type  = "proxy_id";
                                            $audience_value = $proxy_id;
                                            if (isset($proxy_times) && is_array($proxy_times) && !empty($proxy_times)) {
                                                $custom_time        = $proxy_times[$proxy_id]["custom_time"];
                                                $custom_time_start  = $proxy_times[$proxy_id]["custom_time_start"];
                                                $custom_time_end    = $proxy_times[$proxy_id]["custom_time_end"];
                                            }
                                            $PROCESSED["associated_proxy_ids"][] = $audience_value;

                                            $audience = new Models_Event_Audience(array(
                                                "event_id"          => $EVENT_ID,
                                                "audience_type"     => $audience_type,
                                                "audience_value"    => $audience_value,
                                                "custom_time"       => $custom_time,
                                                "custom_time_start" => $custom_time_start,
                                                "custom_time_end"   => $custom_time_end,
                                                "updated_date"      => time(),
                                                "updated_by"        => $ENTRADA_USER->getID()
                                            ));

                                            if (isset($proxy_times_o) && is_array($proxy_times_o) && !array_key_exists($audience_value, $proxy_times_o)) {
                                                $proxy_times_o[$audience_value] = $audience;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
				break;
				default :
					add_error("Unknown event audience type provided. Unable to proceed.");
				break;
			}

            /*
             * Attendance Required/Optional
             */
            if (isset($_POST["attendance_required"]) && ($_POST["attendance_required"] == 1)) {
                $PROCESSED["attendance_required"] = 1;
            } else {
                $PROCESSED["attendance_required"] = 0;
            }

            /**
			 * Non-required field "release_date" / Viewable Start (validated through validate_calendars function).
			 * Non-required field "release_until" / Viewable Finish (validated through validate_calendars function).
			 */
			$viewable_date = Entrada_Utilities::validate_calendars("viewable", false, false);
			if ((isset($viewable_date["start"])) && ((int) $viewable_date["start"])) {
				$PROCESSED["release_date"] = (int) $viewable_date["start"];
			} else {
				$PROCESSED["release_date"] = 0;
			}
			if ((isset($viewable_date["finish"])) && ((int) $viewable_date["finish"])) {
				$PROCESSED["release_until"] = (int) $viewable_date["finish"];
			} else {
				$PROCESSED["release_until"] = 0;
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
				if ($is_draft) {
					$PROCESSED["draft_id"] = $draft_id;
				}
				$PROCESSED["updated_date"]	= time();
				$PROCESSED["updated_by"]	= $ENTRADA_USER->getID();

				$PROCESSED["event_finish"] = $PROCESSED["event_start"];
				$PROCESSED["event_duration"] = 0;
				foreach($PROCESSED["event_types"] as $event_type) {
					$PROCESSED["event_finish"] += $event_type[1]*60;
					$PROCESSED["event_duration"] += $event_type[1];
				}

				$PROCESSED["eventtype_id"] = $PROCESSED["event_types"][0][0];

				if ($db->AutoExecute($tables["events"], $PROCESSED, "INSERT")) {
					if ($EVENT_ID = $db->Insert_Id()) {
						if ($is_draft) {
							$EVENT_ID = 0;
							$devent_id = $db->Insert_ID();
						};
						foreach($PROCESSED["event_types"] as $event_type) {
							$type_details = array("event_id" => $EVENT_ID, "eventtype_id" => $event_type[0], "duration" => $event_type[1]);
							if ($is_draft) {
								$type_details["devent_id"] = $devent_id;
							}
							if (!$db->AutoExecute($tables["event_types"], $type_details, "INSERT")) {
								add_error("There was an error while trying to save the selected <strong>" . $translate->_("Event Type") . "</strong> for this event.<br /><br />The system administrator was informed of this error; please try again later.");

								application_log("error", "Unable to insert a new event_eventtype record while adding a new event. Database said: ".$db->ErrorMsg());
							}
						}

						/**
						 * If there are faculty associated with this event, add them
						 * to the event_contacts table.
						 */
						if ((is_array($PROCESSED["associated_faculty"])) && (count($PROCESSED["associated_faculty"]))) {
							foreach($PROCESSED["associated_faculty"] as $contact_order => $proxy_id) {
								$contact_details =  array("event_id" => $EVENT_ID, "proxy_id" => $proxy_id, "contact_role"=>$PROCESSED["contact_role"][$contact_order],"contact_order" => (int) $contact_order, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID());
								if ($is_draft) {
									$contact_details["devent_id"] =  $devent_id;
								}
								if (!$db->AutoExecute($tables["contacts"], $contact_details, "INSERT")) {
									add_error("There was an error while trying to attach an <strong>Associated Faculty</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.");

									application_log("error", "Unable to insert a new event_contact record while adding a new event. Database said: ".$db->ErrorMsg());
								}
							}
						}
                        
                        //generates the audience with custom times.
                        $diff_cohort_add = array();
                        $diff_cgroup_add = array();
                        $diff_proxy_id_add = array();
                        $new_audience_cohort = array();
                        $new_audience_cgroup = array();
                        $new_audience_proxy_id = array();

						switch ($PROCESSED["event_audience_type"]) {
							case "course" :
								/**
								 * Course ID (there is only one at this time, but this processes more than 1).
								 */
								if (count($PROCESSED["associated_course_ids"])) {
									foreach($PROCESSED["associated_course_ids"] as $course_id) {
										$audience_details = array(
                                            "event_id" => $EVENT_ID,
                                            "audience_type" => "course_id",
                                            "audience_value" => (int) $course_id,
                                            "updated_date" => time(),
                                            "updated_by" => $ENTRADA_USER->getID());
										if ($is_draft) {
											$audience_details["devent_id"] =  $devent_id;
										}
										if (!$db->AutoExecute($tables["audience"], $audience_details, "INSERT")) {
											add_error("There was an error while trying to attach the <strong>Course ID</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.");

											application_log("error", "Unable to insert a new event_audience, course_id record while adding a new event. Database said: ".$db->ErrorMsg());
										}
									}
								}
							break;
							case "custom" :
								/**
								 * Cohort
								 */
                                if (isset($cohort_times_o) && is_array($cohort_times_o)) {
                                    foreach ($cohort_times_o as $audience) {
                                        if (isset($audience) && is_object($audience)) {
                                            $audience->setEventID($EVENT_ID);

                                            if ($is_draft) {
                                                $draft_audience_array = $audience->toArray();
                                                $draft_audience_array["devent_id"] = $devent_id;
                                                $draft_audience_array["eaudience_id"]  = 0;
                                                $audience = new Models_Event_Draft_Event_Audience($draft_audience_array);
                                            }
                                            if (!$audience->insert()) {
                                                add_error("There was an error while trying to attach the selected <strong>Cohort</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.");
                                                application_log("error", "Unable to insert a new event_audience, cohort record while adding a new event. Database said: ".$db->ErrorMsg());
                                            }
                                        }
                                    }
                                }

								/**
								 * Course Groups
								 */

                                if (isset($cgroup_times_o) && is_array($cgroup_times_o)) {
                                    foreach ($cgroup_times_o as $audience) {
                                        if (isset($audience) && is_object($audience)) {
                                            $audience->setEventID($EVENT_ID);

                                            if ($is_draft) {
                                                $draft_audience_array = $audience->toArray();
                                                $draft_audience_array["devent_id"] = $devent_id;
                                                $draft_audience_array["eaudience_id"]  = 0;
                                                $audience = new Models_Event_Draft_Event_Audience($draft_audience_array);
                                            }
                                            if (!$audience->insert()) {
                                                add_error("There was an error while trying to update the selected <strong>Course Group</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.");
                                                application_log("error", "Unable to update an event_audience, Course Group record while adding a new event. Database said: ".$db->ErrorMsg());
                                            }
                                        }
                                    }
                                }

								/**
								 * Learners
								 */

                                if (isset($proxy_times_o) && is_array($proxy_times_o)) {
                                    foreach ($proxy_times_o as $audience) {
                                        if (isset($audience) && is_object($audience)) {
                                            $audience->setEventID($EVENT_ID);
                                            if ($is_draft) {
                                                $draft_audience_array = $audience->toArray();
                                                $draft_audience_array["devent_id"] = $devent_id;
                                                $draft_audience_array["eaudience_id"]  = 0;
                                                $audience = new Models_Event_Draft_Event_Audience($draft_audience_array);
                                            }
                                            if (!$audience->insert()) {
                                                add_error("There was an error while trying to attach the selected <strong>Proxy ID</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.");
                                                application_log("error", "Unable to insert a new event_audience, Proxy ID record while adding a new event. Database said: ".$db->ErrorMsg());
                                            }
                                        }
                                    }
                                }
                                
							break;
							default :
								add_error("There was no audience information provided, so this event is without an audience.");
							break;
						}
                        
                        if (isset($PROCESSED["recurring_events"]) && @count($PROCESSED["recurring_events"]) && !$ERROR) {
                            if (!$is_draft) {
                                $query = "UPDATE `events` SET `recurring_id` = " . $db->qstr($EVENT_ID) . " WHERE `event_id` = " . $db->qstr($EVENT_ID);
                            } else {
                                $query = "UPDATE `draft_events` SET `recurring_id` = " . $db->qstr($devent_id) . " WHERE `devent_id` = " . $db->qstr($devent_id);
                            }
                            $db->Execute($query);
                            $RECURRING_EVENT_RECORD = array();
                            foreach ($PROCESSED["recurring_events"] AS $PROCESSED_RECURRING) {
                                $PROCESSED_RECURRING = array_merge($PROCESSED, $PROCESSED_RECURRING);
                                $PROCESSED_RECURRING["recurring_id"] = ($is_draft ? $devent_id : $EVENT_ID);
                                $recurring_event_start = $PROCESSED_RECURRING["event_start"];
                                $recurring_event_finish = $PROCESSED_RECURRING["event_finish"];
                                if (isset($PROCESSED["parent_event"]) && $PROCESSED["parent_event"] == 1) {
                                    $PROCESSED_RECURRING["parent_id"] = ($is_draft ? $devent_id : $EVENT_ID);
                                }
                                if ($db->AutoExecute($tables["events"], $PROCESSED_RECURRING, "INSERT")) {
                                    if ($RECURRING_EVENT_ID = $db->Insert_Id()) {
                                        if ($is_draft) {
                                            $RECURRING_EVENT_ID = 0;
                                            $devent_id_recurring = $db->Insert_ID();
                                        } else {
                                            history_log($RECURRING_EVENT_ID, "created this learning event.", $ENTRADA_USER->getID());
                                        }
                                        foreach($PROCESSED["event_types"] as $event_type) {
                                            $type_details = array("event_id" => $RECURRING_EVENT_ID, "eventtype_id" => $event_type[0], "duration" => $event_type[1]);
                                            if ($is_draft) {
                                                $type_details["devent_id"] = $devent_id_recurring;
                                                $type_details["eaudience_id"]  = 0;
                                            }
                                            if (!$db->AutoExecute($tables["event_types"], $type_details, "INSERT")) {
                                                application_log("error", "Unable to insert a new event_eventtype record while adding a new event. Database said: ".$db->ErrorMsg());
                                            }
                                        }

                                        /**
                                         * If there are faculty associated with this event, add them
                                         * to the event_contacts table.
                                         */
                                        if ((is_array($PROCESSED["associated_faculty"])) && (count($PROCESSED["associated_faculty"]))) {
                                            foreach($PROCESSED["associated_faculty"] as $contact_order => $proxy_id) {
                                                $contact_details =  array("event_id" => $RECURRING_EVENT_ID, "proxy_id" => $proxy_id, "contact_role"=>$PROCESSED["contact_role"][$contact_order],"contact_order" => (int) $contact_order, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID());
                                                if ($is_draft) {
                                                    $contact_details["devent_id"] =  $devent_id_recurring;
                                                    $contact_details["eaudience_id"]  = 0;
                                                }
                                                if (!$db->AutoExecute($tables["contacts"], $contact_details, "INSERT")) {
                                                    application_log("error", "Unable to insert a new event_contact record while adding a new event. Database said: ".$db->ErrorMsg());
                                                }
                                            }
                                        }



                                        switch ($PROCESSED["event_audience_type"]) {
                                            case "course" :
                                                /**
                                                 * Course ID (there is only one at this time, but this processes more than 1).
                                                 */
                                                if (count($PROCESSED["associated_course_ids"])) {
                                                    foreach($PROCESSED["associated_course_ids"] as $course_id) {
                                                        $audience_details = array("event_id" => $RECURRING_EVENT_ID, "audience_type" => "course_id", "audience_value" => (int) $course_id, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID());
                                                        if ($is_draft) {
                                                            $audience_details["devent_id"] =  $devent_id_recurring;
                                                            $audience_details["eaudience_id"]  = 0;
                                                        }
                                                        if (!$db->AutoExecute($tables["audience"], $audience_details, "INSERT")) {
                                                            application_log("error", "Unable to insert a new event_audience, course_id record while adding a new event. Database said: ".$db->ErrorMsg());
                                                        }
                                                    }
                                                }
                                            break;
                                            case "custom" :
                                                /**
                                                 * Cohort
                                                 */

                                                if (isset($PROCESSED["associated_cohort_ids"]) && is_array($PROCESSED["associated_cohort_ids"])) {
                                                    if (isset($cohort_times_o) && is_array($cohort_times_o)) {
                                                        foreach ($cohort_times_o as $audience) {
                                                            if (isset($audience) && is_object($audience)) {
                                                                $audience_array = $audience->toArray();
                                                                unset($audience_array["eaudience_id"]);
                                                                if ($is_draft) {
                                                                    $audience_array["devent_id"] = $devent_id_recurring;
                                                                    $audience_array["eaudience_id"] = 0;
                                                                }
                                                                $audience = new $model($audience_array);
                                                                $audience->setEventID($RECURRING_EVENT_ID);
                                                                $custom_time_start = ($audience->getCustomTimeStart() ? $recurring_event_start + ($audience->getCustomTimeStart() - $PROCESSED["event_start"]): $recurring_event_start);
                                                                $custom_time_end   = ($audience->getCustomTimeEnd()   ? $recurring_event_finish + ($audience->getCustomTimeEnd() - $PROCESSED["event_finish"]): $recurring_event_start);
                                                                $audience->setCustomTimeStart($custom_time_start);
                                                                $audience->setCustomTimeEnd($custom_time_end);

                                                                if (!$audience->insert()) {
                                                                    add_error("There was an error while trying to attach the selected <strong>Cohort</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.");
                                                                    application_log("error", "Unable to insert a new event_audience, cohort record while adding a new event. Database said: ".$db->ErrorMsg());
                                                                }
                                                            }
                                                        }
                                                    }
                                                }

                                                /**
                                                 * Course Groups
                                                 */
                                                if (isset($PROCESSED["associated_cgroup_ids"]) && is_array($PROCESSED["associated_cgroup_ids"])) {
                                                    if (isset($cgroup_times_o) && is_array($cgroup_times_o)) {
                                                        foreach ($cgroup_times_o as $audience) {
                                                            if (isset($audience) && is_object($audience)) {
                                                                $audience_array = $audience->toArray();
                                                                unset($audience_array["eaudience_id"]);
                                                                if ($is_draft) {
                                                                    $audience_array["devent_id"] = $devent_id_recurring;
                                                                    $audience_array["eaudience_id"] = 0;
                                                                }
                                                                $audience = new $model($audience_array);
                                                                $audience->setEventID($RECURRING_EVENT_ID);
                                                                $custom_time_start = ($audience->getCustomTimeStart() ? $recurring_event_start + ($audience->getCustomTimeStart() - $PROCESSED["event_start"]): $recurring_event_start);
                                                                $custom_time_end   = ($audience->getCustomTimeEnd()   ? $recurring_event_finish + ($audience->getCustomTimeEnd() - $PROCESSED["event_finish"]): $recurring_event_start);
                                                                $audience->setCustomTimeStart($custom_time_start);
                                                                $audience->setCustomTimeEnd($custom_time_end);

                                                                if (!$audience->insert()) {
                                                                    add_error("There was an error while trying to update the selected <strong>Course Group</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.");
                                                                    application_log("error", "Unable to update an event_audience, Course Group record while adding a new event. Database said: ".$db->ErrorMsg());
                                                                }
                                                            }
                                                        }
                                                    }
                                                }

                                                /**
                                                 * Learners
                                                 */
                                                if (isset($PROCESSED["associated_proxy_ids"]) && is_array($PROCESSED["associated_proxy_ids"])) {

                                                    if (isset($proxy_times_o) && is_array($proxy_times_o)) {
                                                        foreach ($proxy_times_o as $audience) {
                                                            if (isset($audience) && is_object($audience)) {
                                                                $audience_array = $audience->toArray();
                                                                unset($audience_array["eaudience_id"]);
                                                                if ($is_draft) {
                                                                    $audience_array["devent_id"] = $devent_id_recurring;
                                                                    $audience_array["eaudience_id"] = 0;
                                                                }
                                                                $audience = new $model($audience_array);
                                                                $audience->setEventID($RECURRING_EVENT_ID);
                                                                $custom_time_start = ($audience->getCustomTimeStart() ? $recurring_event_start + ($audience->getCustomTimeStart() - $PROCESSED["event_start"]): $recurring_event_start);
                                                                $custom_time_end   = ($audience->getCustomTimeEnd()   ? $recurring_event_finish + ($audience->getCustomTimeEnd() - $PROCESSED["event_finish"]): $recurring_event_start);
                                                                $audience->setCustomTimeStart($custom_time_start);
                                                                $audience->setCustomTimeEnd($custom_time_end);

                                                                if (!$audience->insert()) {
                                                                    add_error("There was an error while trying to attach the selected <strong>Proxy ID</strong> to this event.<br /><br />The system administrator was informed of this error; please try again later.");
                                                                    application_log("error", "Unable to insert a new event_audience, Proxy ID record while adding a new event. Database said: ".$db->ErrorMsg());
                                                                }
                                                            }
                                                        }
                                                    }
                                                }

                                            break;
                                        }
                                    }
                                }
                            }
                        }

						switch($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
							case "content" :
								$url	= ENTRADA_URL."/admin/events?section=content&id=".$EVENT_ID;
								$msg	= "You will now be redirected to the event content page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
							break;
							case "new" :
								$url	= ENTRADA_URL."/admin/events?section=add";
								$msg	= "You will now be redirected to add another new event; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
							break;
							case "copy" :
								$url	= ENTRADA_URL."/admin/events?section=add";
								$msg	= "You will now be redirected to add a copy of the last event; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
								$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["copy"] = $PROCESSED;
							break;
							case "draft" :
								$url	= ENTRADA_URL."/admin/events/drafts?section=edit&draft_id=".$draft_id;
								$msg	= "You will now be redirected to the draft managment page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
							break;
							case "index" :
							default :
								$url	= ENTRADA_URL."/admin/events";
								$msg	= "You will now be redirected to the event index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
							break;
						}

						if (!$ERROR) {
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
										AND a.`course_id` = ".$db->qstr($PROCESSED["course_id"]);
							$result = $db->GetRow($query);
							if($result){
								$COMMUNITY_ID = $result["community_id"];
								$PAGE_ID = $result["cpage_id"];
								communities_log_history($COMMUNITY_ID, $PAGE_ID, $EVENT_ID, "community_history_add_learning_event", 1);
							}

							add_success("You have successfully added <strong>".html_encode($PROCESSED["event_title"])."</strong> to the system.<br /><br />".$msg);
                            
                            //create history once the draft is published or if it's not draft create now
                            if (!$is_draft) {
                                history_log($EVENT_ID, "created this learning event.", $ENTRADA_USER->getID());
                            }
                            
							$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";

							application_log("success", "New event [".$EVENT_ID."] added to the system.");
						}
					}
				} else {
					add_error("There was a problem inserting this event into the system. The system administrator was informed of this error; please try again later.");

					application_log("error", "There was an error inserting a event. Database said: ".$db->ErrorMsg());
				}
			}

			if ($ERROR) {
				$STEP = 1;
			}
		break;
		case 1 :
		default :
			continue;
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
            $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/elementresizer.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
            $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.timepicker.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
            $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js\"></script>\n";
			$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />\n";
            $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/events/admin/edit.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
            $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/events/time_override.css?release=". html_encode(APPLICATION_VERSION) ."\" />";

            if (isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) && $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "copy") {
                $PROCESSED = $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["copy"];
            } else {
                if (isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["copy"])) {
                    unset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["copy"]);
                }
            }

            if (!isset($PROCESSED["course_id"]) || !$PROCESSED["course_id"]) {
                $ONLOAD[] = "selectEventAudienceOption('".$PROCESSED["event_audience_type"]."')";
            }

            /**
             * Compiles the full list of faculty members.
             */
            $FACULTY_LIST = array();
            $query = "SELECT a.`id` AS `proxy_id`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, a.`organisation_id`
                        FROM `".AUTH_DATABASE."`.`user_data` AS a
                        LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
                        ON b.`user_id` = a.`id`
                        WHERE b.`app_id` = '".AUTH_APP_ID."'
                        AND (b.`group` = 'faculty' OR (b.`group` = 'resident' AND b.`role` = 'lecturer'))
                        ORDER BY a.`lastname` ASC, a.`firstname` ASC";
            $results = $db->GetAll($query);
            if ($results) {
                foreach($results as $result) {
                    $FACULTY_LIST[$result["proxy_id"]] = array("proxy_id"=>$result["proxy_id"], "fullname"=>$result["fullname"], "organisation_id"=>$result["organisation_id"]);
                }
            }

            /**
             * Compiles the list of students.
             */
            $STUDENT_LIST = array();
            $query = "SELECT a.`id` AS `proxy_id`, b.`role`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, a.`organisation_id`
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

            if (isset($PROCESSED["course_id"])) {
                /**
                 * Compiles the list of groups.
                 */
                $GROUP_LIST = array();
                $query = "SELECT *
                            FROM `course_groups`
                            WHERE `course_id` = ".$db->qstr($PROCESSED["course_id"])."
                            AND (`active` = '1')
                            ORDER BY LENGTH(`group_name`), `group_name` ASC";
                $results = $db->GetAll($query);
                if ($results) {
                    foreach($results as $result) {
                        $GROUP_LIST[$result["cgroup_id"]] = $result;
                    }
                }
            }

            /**
             * Compiles the list of groups.
             */
            $COHORT_LIST = array();
            $query = "SELECT *
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
                        $organisation_categories[$result["organisation_id"]] = array("text" => $result["organisation_title"], "value" => "organisation_".$result["organisation_id"], "category"=>true);
                    }
                }
            }
            ?>
            <form action="<?php echo ENTRADA_URL; ?>/admin/events?section=add&amp;step=2<?php echo ($is_draft == "true") ? "&mode=draft&draft_id=".$draft_id : ""; ?>" method="post" id="addEventForm" class="form-horizontal">
                <div class="control-group">
                    <label for="course_id" class="control-label form-required">Select Course:</label>
                    <div class="controls">
                        <?php
                        $query = "SELECT `course_id`, `course_name`, `course_code`, `course_active`
                                    FROM `courses`
                                    WHERE `organisation_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation()) . "
                                    AND (`course_active` = '1')
                                    ORDER BY `course_code`, `course_name` ASC";
                        $results = $db->GetAll($query);
                        if ($results) {
                            ?>
                            <select id="course_id" name="course_id" style="width: 97%">
                                <option value="0">-- Select the course this event belongs to --</option>
                                <?php
                                foreach($results as $result) {
                                    if ($ENTRADA_ACL->amIAllowed(new EventResource(null, $result["course_id"], $ENTRADA_USER->getActiveOrganisation()), "create")) {
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
                        <div id="course_id_path" class="content-small"><?php echo (isset($PROCESSED["course_id"]) && $PROCESSED["course_id"] ? fetch_course_path($PROCESSED["course_id"]) : ""); ?></div>
                        <input type="text" id="event_title" name="event_title" value="<?php echo ((isset($PROCESSED["event_title"]) && $PROCESSED["event_title"]) ? html_encode($PROCESSED["event_title"]) : ""); ?>" maxlength="255" style="width: 95%; font-size: 150%; padding: 3px" />
                    </div>
                </div>
                <div class="control-group">
                    <label for="event_color" class="control-label form-nrequired"><?php echo $translate->_("Event")." ".$translate->_("Colour"); ?>:</label>
                    <div class="controls">
                        <input type="text" id="event_color" name="event_color" value="<?php echo html_encode(!empty($PROCESSED["event_color"]) ? $PROCESSED["event_color"] : ""); ?>" maxlength="20" class="span3">
                    </div>
                </div>

                <?php echo Entrada_Utilities::generate_calendars("event", $translate->_("Event Date"), true, true, ((isset($PROCESSED["event_start"])) ? $PROCESSED["event_start"] : 0)); ?>

                <div class="control-group">
                    <label for="repeat_frequency" class="control-label form-nrequired">Repeat Frequency</label>
                    <div class="controls">
                        <select name="repeat_frequency" id="repeat_frequency">
                            <option value="none">None</option>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                        <button class="btn pull-right" type="button" id="rebuild_button" style="display: none;" onclick="jQuery('#repeat_frequency').trigger('change')">Rebuild Recurring Events List</button>
                    </div>
                </div>
                <div class="space-below pad-left large"<?php echo (isset($PROCESSED["recurring_events"]) && @count($PROCESSED["recurring_events"]) ? "" : "style=\"display: none;\""); ?> id="recurring-events-list">
                    <?php 
                    if (isset($PROCESSED["recurring_events"]) && @count($PROCESSED["recurring_events"])) {
                        ?>
                        <h3 class="space-below">Recurring Events</h3>

                        <label for="parent_event" class="checkbox"> 
                            <input type="checkbox" name="parent_event" id="parent_event" value="1"<?php echo ((isset($PROCESSED["parent_event"]) && $PROCESSED["parent_event"] == "1") ? " checked=\"checked\"" : ""); ?> /> 
                            <?php echo $translate->_("Recurring events should be created as child events."); ?>
                        </label>

                        <?php
                        $ONLOAD[] = "jQuery('.inpage-datepicker').datepicker({
                                        dateFormat: 'yy-mm-dd',
                                        maxDate: add_year(new Date('".date("Y-m-d", $PROCESSED["event_start"])."')),
                                        minDate: '".date("Y-m-d", $PROCESSED["event_start"])."'
                                    })";
                        $ONLOAD[] = "jQuery('.timepicker').timepicker({
                                        showPeriodLabels: false
                                    })";
                        $ONLOAD[] = "jQuery('.inpage-add-on').on('click', function() {
                                        if ($(this).siblings('input').is(':enabled')) {
                                            $(this).siblings('input').focus();
                                        }
                                    })";
                        $restricted_date_found = false;
                        foreach ($PROCESSED["recurring_events"] as $key => $recurring_event) {
                            if ($recurring_event["event_start"]) {
                                $restricted_days = Models_RestrictedDays::fetchAll($ENTRADA_USER->getActiveOrganisation());

                                $date_string = date("Y-m-d", $recurring_event["event_start"]);
                                if ($restricted_days) {
                                    foreach ($restricted_days as $restricted_day) {
                                        $restricted_string = date("Y-m-d", $restricted_day->getCalculatedDate(date("Y", $recurring_event["event_start"]), date("n", $recurring_event["event_start"]), $recurring_event["event_start"]));
                                        if ($restricted_string == $date_string) {
                                            $restricted_date_found = true;
                                            break;
                                        }
                                    }
                                    if (isset($restricted_date_found) && $restricted_date_found) {
                                        ?>
                                        <div id="display-error-box" class="alert alert-block alert-error">
                                            <button type="button" class="close" data-dismiss="alert">×</button>
                                            <ul>
                                                <li>
                                                    Each of the highlighted events takes place during a restricted day. Please review to ensure those events take place on the correct date.
                                                </li>
                                            </ul>
                                        </div>
                                        <?php
                                        break;
                                    }
                                }
                            }
                        }
                        foreach ($PROCESSED["recurring_events"] as $key => $recurring_event) {
                            $this_date_restricted = false;
                            if ($recurring_event["event_start"]) {
                                $restricted_days = Models_RestrictedDays::fetchAll($ENTRADA_USER->getActiveOrganisation());

                                $date_string = date("Y-m-d", $recurring_event["event_start"]);
                                if ($restricted_days) {
                                    foreach ($restricted_days as $restricted_day) {
                                        $restricted_string = date("Y-m-d", $restricted_day->getCalculatedDate(date("Y", $recurring_event["event_start"]), date("n", $recurring_event["event_start"]), $recurring_event["event_start"]));
                                        if ($restricted_string == $date_string) {
                                            $this_date_restricted = true;
                                            break;
                                        }
                                    }
                                }
                            }
                            ?>
                            <div id="recurring-event-<?php echo ($key + 1); ?>" class="recurring-event row-fluid pad-above<?php echo ($key % 2 == 0 ? " odd" : "").($this_date_restricted ? " restricted" : ""); ?>">
                                <span class="span3 content-small pad-left">
                                    Event <?php echo ($key + 1); ?>:
                                </span>
                                <span class="span8">
                                    <div class="row-fluid">
                                        <label for="recurring_event_title_<?php echo ($key + 1); ?>" class="span2 form-required"><?php echo $translate->_("Title"); ?>:</label>
                                        <span class="span7">
                                            <input type="text" id="recurring_event_title_<?php echo ($key + 1); ?>" name="recurring_event_title[]" value="<?php echo html_encode($recurring_event["event_title"]); ?>" maxlength="255" style="width: 95%; font-size: 150%; padding: 3px" />
                                        </span>
                                    </div>
                                    <div class="row-fluid">
                                        <label class="span2" for="recurring_event_start_<?php echo ($key + 1); ?>"><?php echo $translate->_("Event Start"); ?>:</label>
                                        <span class="span7">
                                            <div class="input-append">
                                                <input type="text" class="input-small inpage-datepicker" value="<?php echo date("Y-m-d", $recurring_event["event_start"]); ?>" name="recurring_event_start[]" id="recurring_event_start_<?php echo ($key + 1); ?>" />
                                                <span class="add-on pointer inpage-add-on"><i class="icon-calendar"></i></span>
                                            </div>
                                            &nbsp;
                                            <div class="input-append">
                                                <input type="text" class="input-mini timepicker" value="<?php echo date("H:i", $recurring_event["event_start"]); ?>" name="recurring_event_start_time[]" id="recurring_event_start_time_<?php echo ($key + 1); ?>" />
                                                <span class="add-on pointer inpage-add-on"><i class="icon-time"></i></span>
                                            </div>
                                        </span>
                                    </div>
                                </span>
                                <span class="span1 pad-right">
                                    <button type="button" class="close" onclick="removeRecurringEvent('<?php echo ($key + 1); ?>')">×</button>
                                </span>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>

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
                        $query = "	SELECT a.* FROM `events_lu_eventtypes` AS a
                                    LEFT JOIN `eventtype_organisation` AS b
                                    ON a.`eventtype_id` = b.`eventtype_id`
                                    LEFT JOIN `".AUTH_DATABASE."`.`organisations` AS c
                                    ON c.`organisation_id` = b.`organisation_id`
                                    WHERE b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
                                    AND a.`eventtype_active` = '1'
                                    ORDER BY a.`eventtype_order` ASC";
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
                                 <strong>Please Note:</strong> Select all of the different segments taking place within this learning event. When you select an <?php echo $translate->_("Event Type"); ?> it will appear below, and allow you to change the order and duration of each segment.
                             </div>
                        </div>
						<ol id="duration_container" class="sortableList">
                        <?php
                        if (is_array($PROCESSED["event_types"])) {
                            foreach($PROCESSED["event_types"] as $eventtype) {
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
                        <ul id="faculty_list" class="menu" style="margin-top: 15px">
                            <?php
                            if (is_array($PROCESSED["associated_faculty"]) && count($PROCESSED["associated_faculty"])) {
                                foreach ($PROCESSED["associated_faculty"] as $faculty) {
                                    if ((array_key_exists($faculty, $FACULTY_LIST)) && is_array($FACULTY_LIST[$faculty])) {
                                        ?>
                                        <li class="user" id="faculty_<?php echo $FACULTY_LIST[$faculty]["proxy_id"]; ?>" style="cursor: move;margin-bottom:10px;width:350px;"><?php echo $FACULTY_LIST[$faculty]["fullname"]; ?><select name="faculty_role[]" class="input-medium" style="float:right;margin-right:30px;margin-top:-5px;"><option value="teacher" <?php if($PROCESSED["display_role"][$faculty] == "teacher") echo "SELECTED";?>><?php echo $translate->_("Teacher"); ?></option><option value="tutor" <?php if($PROCESSED["display_role"][$faculty] == "tutor") echo "SELECTED";?>>Tutor</option><option value="ta" <?php if($PROCESSED["display_role"][$faculty] == "ta") echo "SELECTED";?>>Teacher's Assistant</option><option value="auditor" <?php if($PROCESSED["display_role"][$faculty] == "auditor") echo "SELECTED";?>>Auditor</option></select><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" onclick="faculty_list.removeItem('<?php echo $FACULTY_LIST[$faculty]["proxy_id"]; ?>');" class="list-cancel-image" /></li>
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
                            <input type="checkbox" name="audience_visible" id="audience_visible" value="1"<?php echo ((!isset($PROCESSED["audience_visible"]) || $PROCESSED["audience_visible"] == "1") ? " checked=\"checked\"" : ""); ?> />
                            Show the audience to the learners
                            <div class="content-small">This option controls the learner's ability to view who else is in the learning event.</div>
                        </label>

                        <label for="attendance_required" class="checkbox">
                            <input type="checkbox" name="attendance_required" id="attendance_required" value="1"<?php echo ((!isset($PROCESSED["attendance_required"]) || $PROCESSED["attendance_required"] == "1") ? " checked=\"checked\"" : ""); ?> />
                            Learner's attendance at this Learning Event is required
                            <div class="content-small">This option controls whether or not attendance is required by the Associated Learners.</div>
                        </label>
                    </div>
                </div>

                <div id="audience-options"<?php echo ((!$PROCESSED["event_audience_type"]) ? " style=\"display: none\"" : ""); ?>>
                    <?php
                    require_once(ENTRADA_ABSOLUTE."/core/modules/admin/events/api-audience-options.inc.php");
                    ?>
                </div>

                <?php
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
                <style>
                    #custom-time {
                        height: 400px;
                        top: -170px;
                    }
                </style>
                <div class="control-group">
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
                                                <span class="left"></span>
                                                <span class="right"></span>
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
                </div>

                <h2>Time Release Options</h2>

                <?php echo Entrada_Utilities::generate_calendars("viewable", "", true, false, ((isset($PROCESSED["release_date"])) ? $PROCESSED["release_date"] : 0), true, false, ((isset($PROCESSED["release_until"])) ? $PROCESSED["release_until"] : 0)); ?>

                <div class="control-group">
                    <?php
                    if ($draft_id) {
                        $url = ENTRADA_RELATIVE."/admin/events/drafts?section=edit&draft_id=".$draft_id;
                    } else {
                        $url = ENTRADA_RELATIVE."/admin/events";
                    }
                    ?>
                    <a class="btn" href="<?php echo $url; ?>">Cancel</a>

                    <div class="pull-right">
                        <?php
                        if (isset($is_draft) && $is_draft) {
                            echo "<input type=\"hidden\" name=\"post_action\" id=\"post_action\" value=\"draft\" />";
                        } else {
                            ?>
                            <span class="content-small">After saving:</span>
                            <select id="post_action" name="post_action">
                                <option value="content"<?php echo (((!isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"])) || ($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "content")) ? " selected=\"selected\"" : ""); ?>>Add content to event</option>
                                <option value="new"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "new") ? " selected=\"selected\"" : ""); ?>>Add another event</option>
                                <option value="copy"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "copy") ? " selected=\"selected\"" : ""); ?>>Add a copy of this event</option>
                                <option value="index"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "index") ? " selected=\"selected\"" : ""); ?>>Return to event list</option>
                            </select>
                            <?php
                        }
                        ?>
                        <input type="submit" class="btn btn-primary" value="Save" />
                    </div>
                </div>
            </form>
            <div id="recurringModal" class="modal hide fade" style="width: 450px;" tabindex="-1" role="dialog" aria-labelledby="recurringModalLabel" aria-hidden="true">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h3 id="recurringModalLabel">Recurring Event Frequency</h3>
                </div>
                <div class="modal-body">
                </div>
                <div class="modal-footer" style="text-align: left;">
                    <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
                    <button class="btn btn-primary pull-right" id="submitFrequency">Set Frequency</button>
                </div>
            </div>
            <div id="messages" rel="popover">
                &nbsp;
            </div>
            <div id="recurring-event-skeleton" style="display: none;">
                <div id="recurring-event-%event_num%" class="recurring-event row-fluid pad-above%event_class%">
                    <span class="span3 content-small pad-left">
                        Event %event_num%:
                    </span>
                    <span class="span8">
                        <div class="row-fluid space-below">
                            <label for="recurring_event_title_%event_num%" class="span2 form-required">Title:</label>
                            <span class="span7">
                                <input type="text" id="recurring_event_title_%event_num%" name="recurring_event_title[]" value="%event_title%" maxlength="255" style="width: 95%; font-size: 150%; padding: 3px" />
                            </span>
                        </div>
                        <div class="row-fluid space-below">
                            <label class="span2" for="recurring_event_start_%event_num%">Event Start:</label>
                            <span class="span7">
                                <div class="input-append">
                                    <input type="text" class="input-small inpage-datepicker" value="%event_date%" onchange="checkEventDate('%event_num%')" name="recurring_event_start[]" id="recurring_event_start_%event_num%" />
                                    <span class="add-on pointer inpage-add-on"><i class="icon-calendar"></i></span>
                                </div>
                                &nbsp;
                                <div class="input-append">
                                    <input type="text" class="input-mini timepicker" value="%event_time%" name="recurring_event_start_time[]" id="recurring_event_start_time_%event_num%" />
                                    <span class="add-on pointer inpage-add-on"><i class="icon-time"></i></span>
                                </div>
                            </span>
                        </div>
                    </span>
                    <span class="span1 pad-right">
                        <button type="button" class="close" onclick="removeRecurringEvent('%event_num%')">&times;</button>
                    </span>
                </div>
            </div>
            <script type="text/javascript">
            var multiselect = [];
            var audience_type;

            function showMultiSelect() {
                $$("select_multiple_container").invoke("hide");
                audience_type = $F("audience_type");
                course_id = $F("course_id");
                var cohorts = $("event_audience_cohorts").value;
                var course_groups = $("event_audience_course_groups").value;
                var students = $("event_audience_students").value;

                if (multiselect[audience_type]) {
                    multiselect[audience_type].container.show();
                } else {
                    if (audience_type) {
                        new Ajax.Request("<?php echo ENTRADA_RELATIVE; ?>/admin/events?section=api-audience-selector", {
                            evalScripts : true,
                            parameters: {
                                "options_for" : audience_type,
                                "course_id" : course_id,
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
                                            checkboxSelector: "table.select_multiple_table tr td input[type=checkbox]",
                                            nameSelector: "table.select_multiple_table tr td.select_multiple_name label",
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
                if (!$("audience_"+element)) {
                    //manipulate data into the correct format
                    //course group being refered to as "group_id" is confusing and might want to be reworked, but would break existing data in the database
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
                        start_sliders(object, <?php echo $event_length?>);
                    } else if (object.audience_type == "group_id") {
                        cgroup_custom_time_array[id] = object;
                        start_sliders(object, <?php echo $event_length?>);
                    } else {
                        cohorts_custom_time_array[id] = object;
                        start_sliders(object, <?php echo $event_length?>);
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
                var event_audeince_html = jQuery(".slider-range[data-type=" + type_slider + "][data-id=" + id + "]").parent().parent();
                event_audeince_html.remove();

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
                var audience = $("event_audience_"+audience_id).value.split(",");
                for (var i = 0; i < audience.length; i++) {
                    if (audience[i] == type + "_" + id) {
                        audience.splice(i, 1);
                        break;
                    }
                }
                $("event_audience_"+audience_id).value = audience.join(",");
            }

            function selectEventAudienceOption(type) {
                if (type == "custom" && !jQuery("#event_audience_type_custom_options").is(":visible")) {
                    jQuery("#event_audience_type_custom_options").slideDown();
                } else if (type != "custom" && jQuery("#event_audience_type_custom_options").is(":visible")) {
                    jQuery("#event_audience_type_custom_options").slideUp();
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


//				var prevDate = '';
//				var prevTime = '00:00 AM';
//				var t = self.setInterval("checkDifference()", 1500);


//				Event.observe('event_audience_type_course','change',checkConflict);
//				Event.observe('associated_grad_year','change',checkConflict);
//				Event.observe('associated_organisation_id','change',checkConflict);
//				Event.observe('student_list','change',checkConflict)
//				Event.observe('eventtype_ids','change',checkConflict)
//				//Event.observe('event_start_date','keyup',checkConflict);


//				function checkDifference(){
//					if($('event_start_date').value !== prevDate){
//						prevDate = $('event_start_date').value;
//						checkConflict();
//					}
//					else if($('event_start_display').innerHTML !== prevTime){
//						prevTime = $('event_start_display').innerHTML;
//						checkConflict();
//					}
//				}
//				function checkConflict(){
//					new Ajax.Request('<?php echo ENTRADA_URL;?>/api/learning-event-conflicts.api.php',
//					{
//						method:'post',
//						parameters: $("addEventForm").serialize(true),
//						onSuccess: function(transport){
//						var response = transport.responseText || null;
//						if(response !==null){
//							var g = new k.Growler();
//							g.smoke(response,{life:7});
//						}
//						},
//						onFailure: function(){ alert('Unable to check if a conflict exists.') }
//					});
//				}

                function add_year(date) {
                    return new Date((date.getFullYear() + 1), date.getMonth(), date.getDate());
                }

                var show_restricted_message = <?php echo (isset($PROCESSED["recurring_events"]) && isset($restricted_date_found) && $restricted_date_found ? "true" : "false"); ?>;

                jQuery(document).ready(function($){
                    var _old_toggle = $.fn.button.prototype.constructor.Constructor.prototype.toggle;

                    $.fn.button.prototype.constructor.Constructor.prototype.toggle = function () {
                        _old_toggle.apply(this);
                        this.$element.trigger("active");
                    }
                    $("html").click(function(e) {
                        $("#repeat_frequency").popover("hide");
                        $("#repeat_frequency").show();
                    });
                    $("#repeat_frequency").popover({
                        trigger: "manual",
                        placement: "right",
                        title: "Error",
                        content: "Please ensure you select a valid event start date prior to selecting a repeat frequency.",
                        template: "<div class=\"popover alert alert-error\"><div class=\"arrow\"></div><div class=\"popover-inner\"><div class=\"popover-content\"><p></p></div></div></div>"
                    }).click(function(e) {
                        $(this).popover("toggle");
                        e.stopPropagation();
                    });
                    $("#repeat_frequency").on("change", function(){
                        if ($("#repeat_frequency").val() != "none") {
                            if ($("#event_start_date").val()) {
                                if (!$("#rebuild_button").is(":visible")) {
                                    $("#rebuild_button").show();
                                }
                                var date = new Date($("#event_start_date").val());
                                date = new Date(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate());
                                var datestring = (date.getTime() / 1000);
                                $("#recurringModal").modal({
                                    remote: "<?php echo ENTRADA_URL ?>/admin/events?section=api-repeat-period&action=select&event_start=" + datestring + "&frequency=" + ($("#repeat_frequency").val() && $("#repeat_frequency").val() != "none" ? $("#repeat_frequency").val() : "daily")
                                });
                            } else {
                                $("#repeat_frequency option").eq(0).prop("selected",true);
                                $("#repeat_frequency").popover("show");
                            }
                        } else {
                            if ($("#rebuild_button").is(":visible")) {
                                $("#rebuild_button").hide();
                            }
                            $("#recurring-events-list").html("");
                        }
                    });
                    $("#recurringModal").on("shown", function () {
                        $(".toggle-days").live("active", function(event) {
                            event.preventDefault();
                            if ($(this).hasClass("active") && $("#weekday_"+ $(this).data("value")).length < 1) {
                                $("#days-container").append("<input type=\"hidden\" value=\"" + $(this).data("value") +  "\" name=\"weekdays[]\" id=\"weekday_" + $(this).data("value") + "\" />");
                            } else if (!$(this).hasClass("active") && $("#weekday_" + $(this).data("value")).length > 0) {
                                $("#weekday_" + $(this).data("value")).remove();
                            }
                        });
                        $(".datepicker").datepicker({
                            dateFormat: "yy-mm-dd",
                            maxDate: add_year(new Date($("#event_start_date").val())),
                            minDate: new Date($("#event_start_date").val())
                        });
                        $(".add-on").on("click", function() {
                            if ($(this).siblings("input").is(":enabled")) {
                                $(this).siblings("input").focus();
                            }
                        });
                    });
                    $("#recurringModal").on("hidden", function () {
                        $(this).data("modal", null);
                    });
                    $("#submitFrequency").on("click", function () {
                        $.ajax({
                            type: "POST",
                            url: "<?php echo ENTRADA_URL ?>/admin/events?section=api-repeat-period&action=results",
                            data: $("#recurring-form").serialize(),
                            success: function (data) {
                                var result = jQuery.parseJSON(data);
                                if (result.status == "success") {
                                    if ($(".inpage-datepicker").length > 0) {
                                        $(".inpage-datepicker").datepicker("destroy");
                                        $(".timepicker").timepicker("destroy");
                                    }
                                    jQuery("#recurring-events-list").html("<h3 class=\"space-below\">Recurring Events</h3>");
                                    jQuery("#recurring-events-list").append("<label for=\"parent_event\" class=\"checkbox\"><input type=\"checkbox\" name=\"parent_event\" id=\"parent_event\" value=\"1\" /> <?php echo $translate->_("Recurring events should be created as child events."); ?></label>");
                                    var events_string = "";
                                    for (var i = 1; i <= result.events.length; i++) {
                                        if (result.events[(i - 1)].restricted && !show_restricted_message) {
                                            jQuery("#recurring-events-list").append("<div id=\"display-error-box\" class=\"alert alert-block alert-error\"><button type=\"button\" class=\"close\" data-dismiss=\"alert\">×</button><ul><li>Each of the highlighted events takes place during a restricted day. Please review to ensure those events take place on the correct date.</li></ul></div>");
                                            show_restricted_message = true;
                                        }
                                        var string = jQuery("#recurring-event-skeleton").html();
                                        var class_string = (result.events[(i - 1)].restricted ? " restricted" : "")+(i % 2 > 0 ? " odd" : "");
                                        string = string.replace(/%event_class%/g, class_string);
                                        string = string.replace(/%event_num%/g, i);
                                        string = string.replace(/%event_title%/g, jQuery("#event_title").val());
                                        string = string.replace(/%event_time%/g, jQuery("#event_start_hour").val() + ":" + jQuery("#event_start_min").val());
                                        string = string.replace(/%event_date%/g, result.events[(i - 1)].date);
                                        events_string = events_string + string;
                                    }
                                    jQuery("#recurring-events-list").append(events_string);
                                    jQuery("#recurring-events-list").show();
                                    $(".inpage-datepicker").datepicker({
                                        dateFormat: "yy-mm-dd",
                                        maxDate: add_year(new Date($("#event_start_date").val())),
                                        minDate: $("#event_start_date").val()
                                    });
                                    $(".timepicker").timepicker({
                                        showPeriodLabels: false
                                    });
                                    $(".inpage-add-on").on("click", function() {
                                        if ($(this).siblings("input").is(":enabled")) {
                                            $(this).siblings("input").focus();
                                        }
                                    });
                                    $("#recurringModal").modal("hide");
                                } else {
                                    $("#error-messages").html(result.message);
                                }
                            },
                            error: function () {
                                alert("error");
                            }
                        });
                    });

					$("#eventtype_ids").advancedSearch({
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
						parent_form: $("#addEventForm"),
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

						$("#addEventForm").on("mouseenter", ".search-filter-item", function (e) {
							e.stopPropagation();

							$(".popover").remove();
							$("[rel=\"popover\"]").popover(popover_options);
							$(this).popover("show");
						});

						$("#addEventForm").on("mouseleave", ".search-filter-item", function (e) {
							e.stopPropagation();

							if (!$(".search-filter-item:hover").length) {
								setTimeout(function () {
									if (!$(".popover:hover").length) {
										$(".popover").remove();
									}
								}, 300);
							}
						});

						$("#addEventForm").on("click", ".search-filter-item", function (e) {
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

					$("#addEventForm").on("change", ".search-target-input-control", function () {
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
                
                function checkEventDate(event_num) {
                    var date = new Date(jQuery("#recurring_event_start_"+event_num).val());
                    var event_date = (date.getTime() / 1000) + (date.getTimezoneOffset() * 60);
                    jQuery.ajax({
                        type: "POST",
                        url: "<?php echo ENTRADA_URL ?>/admin/events?section=api-check-date",
                        data: "event_start="+event_date+"&organisation_id=<?php echo $ENTRADA_USER->getActiveOrganisation(); ?>",
                        success: function (data) {
                            if (data == "Found") {
                                if (!jQuery("#recurring-event-"+event_num).hasClass("restricted")) {
                                    if (!show_restricted_message) {
                                        jQuery("#recurring-events-list h3:first").after("<div id=\"display-error-box\" class=\"alert alert-block alert-error\"><button type=\"button\" class=\"close\" data-dismiss=\"alert\">×</button><ul><li>Each of the highlighted events takes place during a restricted day. Please review to ensure those events take place on the correct date.</li></ul></div>");
                                    }
                                    jQuery("#recurring-event-"+event_num).addClass("restricted");
                                }
                            } else if (jQuery("#recurring-event-"+event_num).hasClass("restricted")) {
                                jQuery("#recurring-event-"+event_num).removeClass("restricted");
                            }
                        },
                        error: function () {
                            alert("error");
                        }
                    });
                }

                function removeRecurringEvent(event_num) {
                    jQuery("#recurring-event-"+event_num).remove();
                    var odd = true;
                    jQuery("#recurring-events-list .recurring-event").each( function () {
                        if (odd) {
                            odd = false;
                            if (!jQuery(this).hasClass("odd")) {
                                jQuery(this).addClass("odd");
                            }
                        } else {
                            odd = true;
                            if (jQuery(this).hasClass("odd")) {
                                jQuery(this).removeClass("odd");
                            }
                        }
                    });
                }
            </script>
            <?php
		break;
	}
}
